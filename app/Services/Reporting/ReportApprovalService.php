<?php

namespace App\Services\Reporting;

use App\Models\MessagingSetting;
use App\Models\ReportApproval;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\User;
use App\Services\Messaging\PortalNotifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Report card approval. When "require approval" is on, students and parents
 * only see a term's results once the class's report cards are approved.
 */
class ReportApprovalService
{
    protected array $releaseCache = [];

    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('report_approvals');
    }

    public static function settings(): MessagingSetting
    {
        return MessagingSetting::firstOrCreate(
            ['channel' => 'report_approval'],
            ['driver' => 'auto', 'is_active' => false, 'config' => ['notify' => true, 'require_comments' => false]]
        );
    }

    public static function enforced(): bool
    {
        return self::available() && (bool) self::settings()->is_active;
    }

    public function record(int $classId, int $termId, int $sessionId): ReportApproval
    {
        return ReportApproval::firstOrCreate(
            ['schoolclass_id' => $classId, 'term_id' => $termId, 'session_id' => $sessionId],
            ['status' => 'pending']
        );
    }

    /** Class a student's results sit in for a session (from the broadsheet). */
    public function studentClass(int $studentId, int $termId, int $sessionId): ?int
    {
        $id = DB::table('broadsheet_records as br')->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->where('br.student_id', $studentId)->where('br.session_id', $sessionId)->where('b.term_id', $termId)
            ->orderByDesc('br.id')->value('br.schoolclass_id');
        return $id ? (int) $id : null;
    }

    /** May students/parents see this term's result? */
    public function isReleased(int $studentId, int $termId, int $sessionId): bool
    {
        if (!self::enforced()) return true;
        $key = "$studentId:$termId:$sessionId";
        if (isset($this->releaseCache[$key])) return $this->releaseCache[$key];

        $classId = $this->studentClass($studentId, $termId, $sessionId);
        if (!$classId) return $this->releaseCache[$key] = false;

        return $this->releaseCache[$key] = ReportApproval::where('schoolclass_id', $classId)->where('term_id', $termId)
            ->where('session_id', $sessionId)->where('status', 'approved')->exists();
    }

    /**
     * Every class with results this term, with readiness figures and status.
     */
    public function board(int $termId, int $sessionId, ?array $onlyClassIds = null): Collection
    {
        $base = DB::table('broadsheet_records as br')->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->where('br.session_id', $sessionId)->where('b.term_id', $termId);
        if ($onlyClassIds !== null) $base->whereIn('br.schoolclass_id', $onlyClassIds ?: [0]);

        $rows = (clone $base)->groupBy('br.schoolclass_id')
            ->get([
                'br.schoolclass_id as class_id',
                DB::raw('COUNT(DISTINCT br.student_id) as students'),
                DB::raw('COUNT(*) as entries'),
                DB::raw('SUM(b.vettedstatus = 1) as vetted'),
                DB::raw('COUNT(DISTINCT br.subject_id) as subjects'),
            ])->keyBy('class_id');

        $comments = DB::table('studentpersonalityprofiles')
            ->where('termid', $termId)->where('sessionid', $sessionId)
            ->whereIn('schoolclassid', $rows->keys()->all() ?: [0])
            ->groupBy('schoolclassid')
            ->get([
                'schoolclassid as class_id',
                DB::raw("COUNT(DISTINCT CASE WHEN TRIM(COALESCE(classteachercomment,'')) <> '' THEN studentid END) as teacher"),
                DB::raw("COUNT(DISTINCT CASE WHEN TRIM(COALESCE(principalscomment,'')) <> '' THEN studentid END) as principal"),
            ])->keyBy('class_id');

        $names = DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->whereIn('c.id', $rows->keys()->all() ?: [0])
            ->get(['c.id', DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as name")])
            ->pluck('name', 'id');

        $teachers = DB::table('classteacher as ct')->join('users as u', 'u.id', '=', 'ct.staffid')
            ->where('ct.sessionid', $sessionId)->whereIn('ct.schoolclassid', $rows->keys()->all() ?: [0])
            ->orderByRaw('ct.termid = ? DESC', [$termId])
            ->get(['ct.schoolclassid', 'u.name'])->groupBy('schoolclassid')
            ->map(fn ($g) => $g->pluck('name')->unique()->implode(', '));

        $approvals = ReportApproval::with(['submitter:id,name', 'reviewer:id,name'])
            ->where('term_id', $termId)->where('session_id', $sessionId)
            ->whereIn('schoolclass_id', $rows->keys()->all() ?: [0])->get()->keyBy('schoolclass_id');

        return $rows->map(function ($r) use ($comments, $names, $teachers, $approvals) {
            $c = $comments[$r->class_id] ?? null;
            $r->name        = $names[$r->class_id] ?? ('Class #' . $r->class_id);
            $r->teacher     = $teachers[$r->class_id] ?? null;
            $r->vetted_pct  = $r->entries ? (int) floor($r->vetted / $r->entries * 100) : 0;
            $r->t_comments  = (int) ($c->teacher ?? 0);
            $r->p_comments  = (int) ($c->principal ?? 0);
            $r->approval    = $approvals[$r->class_id] ?? null;
            $r->status      = $r->approval->status ?? 'pending';
            $r->ready       = (int) $r->vetted >= (int) $r->entries;
            return $r;
        })->sortBy('name', SORT_NATURAL)->values();
    }

    public function readiness(int $classId, int $termId, int $sessionId): ?object
    {
        return $this->board($termId, $sessionId, [$classId])->first();
    }

    public function isClassTeacher(User $user, int $classId, int $sessionId): bool
    {
        return DB::table('classteacher')->where('staffid', $user->id)->where('schoolclassid', $classId)->where('sessionid', $sessionId)->exists();
    }

    public function canSubmit(User $user, int $classId, int $sessionId): bool
    {
        return $user->can('Approve report cards')
            || ($user->can('Submit report cards') && $this->isClassTeacher($user, $classId, $sessionId));
    }

    // ── Actions ──────────────────────────────────────────────────────────

    public function submit(ReportApproval $a, User $by, ?string $note): void
    {
        $a->fill(['status' => 'submitted', 'submitted_by' => $by->id, 'submitted_at' => now(), 'submit_note' => $note,
                  'snapshot' => $this->snapshot($a)]);
        $a->log('submitted', $by->id, $note);
        $a->save();

        try { $approvers = User::permission('Approve report cards')->pluck('id')->all(); } catch (\Throwable $e) { $approvers = []; }
        PortalNotifier::toUsers($approvers, 'Report cards awaiting approval',
            $this->label($a) . ' was submitted by ' . $by->name . ($note ? ": \"$note\"" : '.'),
            route('report-approvals.show', $a), 'result', 'ra-submit:' . $a->id . ':' . $a->submitted_at->timestamp);
    }

    public function approve(ReportApproval $a, User $by, ?string $note): void
    {
        $a->fill(['status' => 'approved', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => $note,
                  'snapshot' => $this->snapshot($a)]);
        $a->log('approved', $by->id, $note);
        $a->save();

        if (!empty(self::settings()->config['notify'])) {
            $ids = DB::table('broadsheet_records as br')->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
                ->where('br.schoolclass_id', $a->schoolclass_id)->where('br.session_id', $a->session_id)->where('b.term_id', $a->term_id)
                ->distinct()->pluck('br.student_id')->map(fn ($v) => (int) $v)->all();
            [$term, $session] = $this->period($a);
            PortalNotifier::toStudents($ids, 'Report card released',
                "The {$term} report card for {$session} is now available.",
                route('notifications.index'), 'result', "ra-release:{$a->schoolclass_id}:{$a->term_id}:{$a->session_id}");
        }
        if ($a->submitted_by && $a->submitted_by !== $by->id) {
            PortalNotifier::toUsers([$a->submitted_by], 'Report cards approved', $this->label($a) . ' was approved by ' . $by->name . '.',
                route('report-approvals.show', $a), 'result', 'ra-approved:' . $a->id . ':' . $a->reviewed_at->timestamp);
        }
    }

    public function returnToTeacher(ReportApproval $a, User $by, string $note): void
    {
        $a->fill(['status' => 'returned', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => $note]);
        $a->log('returned', $by->id, $note);
        $a->save();

        $notify = array_filter(array_unique(array_merge(
            [$a->submitted_by],
            DB::table('classteacher')->where('schoolclassid', $a->schoolclass_id)->where('sessionid', $a->session_id)->pluck('staffid')->map(fn ($v) => (int) $v)->all()
        )));
        PortalNotifier::toUsers($notify, 'Report cards returned', $this->label($a) . " needs changes: \"$note\"",
            route('report-approvals.show', $a), 'result', 'ra-return:' . $a->id . ':' . $a->reviewed_at->timestamp);
    }

    /** Take back an approval (results hidden again while enforced). */
    public function reopen(ReportApproval $a, User $by, ?string $note): void
    {
        $a->fill(['status' => 'pending', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => $note]);
        $a->log('reopened', $by->id, $note);
        $a->save();
    }

    public function label(ReportApproval $a): string
    {
        $name = DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')->where('c.id', $a->schoolclass_id)
            ->selectRaw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_label")->value('class_label');
        [$term, $session] = $this->period($a);
        return trim("{$name} — {$term}, {$session}");
    }

    public function period(ReportApproval $a): array
    {
        return [Schoolterm::where('id', $a->term_id)->value('term') ?? '', Schoolsession::where('id', $a->session_id)->value('session') ?? ''];
    }

    protected function snapshot(ReportApproval $a): array
    {
        $r = $this->readiness($a->schoolclass_id, $a->term_id, $a->session_id);
        return $r ? ['students' => $r->students, 'entries' => $r->entries, 'vetted' => (int) $r->vetted,
                     'teacher_comments' => $r->t_comments, 'principal_comments' => $r->p_comments, 'at' => now()->toDateTimeString()] : [];
    }
}
