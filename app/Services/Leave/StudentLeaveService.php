<?php

namespace App\Services\Leave;

use App\Models\StudentLeaveRequest;
use App\Services\Messaging\PortalNotifier;
use App\Services\Parents\ParentAccountService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Student leave of absence. Flow: class teacher recommends → principal approves
 * (straight to principal if the class has no class teacher). School days are
 * counted Mon–Fri, excluding full-day holidays.
 */
class StudentLeaveService
{
    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('student_leave_requests');
    }

    public function schoolDays($start, $end): int
    {
        $s = Carbon::parse($start)->startOfDay(); $e = Carbon::parse($end)->startOfDay();
        if ($e->lt($s)) return 0;
        $holidays = Schema::hasTable('holidays')
            ? DB::table('holidays')->whereBetween('date', [$s->toDateString(), $e->toDateString()])->pluck('date')
                ->map(fn ($d) => Carbon::parse($d)->toDateString())->all()
            : [];
        $n = 0;
        foreach (CarbonPeriod::create($s, $e) as $d) if (!$d->isWeekend() && !in_array($d->toDateString(), $holidays, true)) $n++;
        return max(1, $n);
    }

    /** Current class / term / session for a student, from student_current_term. */
    public function placement(int $studentId): array
    {
        $row = DB::table('student_current_term')->where('studentId', $studentId)->where('is_current', true)->first();
        return [
            'class_id' => $row->schoolclassId ?? null,
            'term_id' => $row->termId ?? optional(\App\Models\Schoolterm::current())->id,
            'session_id' => $row->sessionId ?? DB::table('schoolsession')->where('status', true)->value('id'),
        ];
    }

    /** users.id of the class teacher for a student's current class (or null). */
    public function classTeacherUserId(array $placement): ?int
    {
        if (empty($placement['class_id'])) return null;
        $q = DB::table('classteacher')->where('schoolclassid', $placement['class_id']);
        if (!empty($placement['term_id'])) $q->where('termid', $placement['term_id']);
        if (!empty($placement['session_id'])) $q->where('sessionid', $placement['session_id']);
        $id = $q->value('staffid');
        // fall back to any class teacher of that class if the exact term/session row is missing
        if (!$id) $id = DB::table('classteacher')->where('schoolclassid', $placement['class_id'])->value('staffid');
        return $id ? (int) $id : null;
    }

    public function approvers(): array
    {
        try { return \App\Models\User::permission('Approve student leave')->pluck('id')->all(); } catch (\Throwable $e) { return []; }
    }

    public function overlaps(int $studentId, string $start, string $end, ?int $ignoreId = null): bool
    {
        return StudentLeaveRequest::where('student_id', $studentId)
            ->whereIn('status', ['pending_teacher', 'pending_principal', 'approved'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('start_date', '<=', $end)->where('end_date', '>=', $start)->exists();
    }

    public function submit(object $student, array $d, int $byUserId, string $requesterType): StudentLeaveRequest
    {
        $placement = $this->placement((int) $student->id);
        $teacher = $this->classTeacherUserId($placement);
        $status = $teacher ? 'pending_teacher' : 'pending_principal';

        $req = StudentLeaveRequest::create([
            'student_id' => $student->id, 'requested_by' => $byUserId, 'requester_type' => $requesterType,
            'class_id' => $placement['class_id'], 'term_id' => $placement['term_id'], 'session_id' => $placement['session_id'],
            'reason_type' => $d['reason_type'], 'reason' => $d['reason'],
            'start_date' => $d['start_date'], 'end_date' => $d['end_date'],
            'days' => $this->schoolDays($d['start_date'], $d['end_date']),
            'attachment' => $d['attachment'] ?? null, 'contact_phone' => $d['contact_phone'] ?? null, 'status' => $status,
        ]);

        $name = $this->studentName($student);
        $when = Carbon::parse($d['start_date'])->format('d M') . ' – ' . Carbon::parse($d['end_date'])->format('d M Y');
        $targets = $teacher ? [$teacher] : $this->approvers();
        $this->notify($targets, "Leave request: {$name}", "{$name} — " . ($req->reasonLabel()) . ", {$when} ({$req->days} school day(s)). Please review.", route('student-leave.approvals'), 'sleave:new:' . $req->id);
        return $req;
    }

    public function recommend(StudentLeaveRequest $req, int $teacherUserId, bool $ok, ?string $note): void
    {
        $req->update(['status' => $ok ? 'pending_principal' : 'rejected', 'teacher_id' => $teacherUserId, 'teacher_at' => now(), 'teacher_note' => $note]);
        if ($ok) {
            $this->notify($this->approvers(), 'Student leave recommended', $this->studentName($req->student) . "'s leave was recommended by the class teacher and needs approval.", route('student-leave.approvals'), 'sleave:rec:' . $req->id);
        } else {
            $this->notifyRequester($req, 'Leave not recommended', 'The class teacher did not recommend the leave request' . ($note ? ": \"$note\"" : '.'));
        }
    }

    public function decide(StudentLeaveRequest $req, int $approverUserId, bool $ok, ?string $note): void
    {
        $req->update(['status' => $ok ? 'approved' : 'rejected', 'approver_id' => $approverUserId, 'approved_at' => now(), 'approver_note' => $note]);
        $when = $req->start_date->format('d M') . ' – ' . $req->end_date->format('d M Y');
        $this->notifyRequester($req, $ok ? 'Leave approved' : 'Leave not approved',
            ($ok ? "Leave for {$when} is approved." : "Leave for {$when} was not approved.") . ($note ? " Note: \"$note\"" : ''));
        // Also tell the class teacher so they can plan around the absence.
        if ($ok && $req->teacher_id) {
            $this->notify([$req->teacher_id], 'Student will be away', $this->studentName($req->student) . " has approved leave {$when}.", route('student-leave.records'), 'sleave:ct:' . $req->id);
        }
    }

    public function onLeave($date)
    {
        $d = Carbon::parse($date)->toDateString();
        return StudentLeaveRequest::with('student')->where('status', 'approved')
            ->where('start_date', '<=', $d)->where('end_date', '>=', $d)->get();
    }

    protected function studentName($student): string
    {
        return trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? '')) ?: ('Student #' . ($student->id ?? ''));
    }

    /** Notify the student (if they have a login) and every linked parent. */
    protected function notifyRequester(StudentLeaveRequest $req, string $title, string $body): void
    {
        $ids = [];
        $studentUserId = DB::table('studentRegistration')->where('id', $req->student_id)->value('userid');
        if ($studentUserId) $ids[] = (int) $studentUserId;
        foreach (ParentAccountService::parentUserIds([(int) $req->student_id]) as $pid) $ids[] = (int) $pid;
        $this->notify(array_unique($ids), $title, $body, route('student-leave.mine'), 'sleave:done:' . $req->id . ':' . $req->status);
    }

    protected function notify(array $userIds, string $title, string $body, string $url, string $key): void
    {
        $userIds = array_values(array_filter($userIds));
        if (!$userIds || !class_exists(PortalNotifier::class)) return;
        try { PortalNotifier::toUsers($userIds, $title, $body, $url, 'system', $key); } catch (\Throwable $e) {}
    }
}
