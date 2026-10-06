<?php

namespace App\Services\Leave;

use App\Models\User;
use App\Services\Messaging\PortalNotifier;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff leave. Days are counted as working days (Mon–Fri, excluding school
 * holidays). Flow: staff → HOD (same department, "Recommend leave") →
 * Principal ("Approve leave"). No HOD → straight to the principal.
 */
class LeaveService
{
    public const STATUS = [
        'pending_hod' => ['Waiting for HOD', 'st-pending'], 'pending_principal' => ['Waiting for principal', 'st-info'],
        'approved' => ['Approved', 'st-paid'], 'rejected' => ['Not approved', 'st-danger'], 'cancelled' => ['Cancelled', 'st-muted'],
    ];

    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('leave_requests');
    }

    /** Working days between two dates (weekends and full-day holidays skipped). */
    public function workingDays($start, $end, bool $halfDay = false): float
    {
        $s = Carbon::parse($start)->startOfDay(); $e = Carbon::parse($end)->startOfDay();
        if ($e->lt($s)) return 0;
        $holidays = Schema::hasTable('holidays')
            ? DB::table('holidays')->whereBetween('date', [$s->toDateString(), $e->toDateString()])
                ->where(fn ($q) => Schema::hasColumn('holidays', 'is_full_day') ? $q->where('is_full_day', true)->orWhereNull('is_full_day') : $q)
                ->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all()
            : [];
        $n = 0;
        foreach (CarbonPeriod::create($s, $e) as $d) if (!$d->isWeekend() && !in_array($d->toDateString(), $holidays, true)) $n++;
        return $halfDay && $n === 1 ? 0.5 : (float) $n;
    }

    public function staffFor(int $userId): ?object
    {
        return DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.userid', $userId)->first(['s.*', 'u.name', 'u.email']);
    }

    public function types(?string $gender = null): Collection
    {
        $g = strtolower((string) $gender);
        return DB::table('leave_types')->where('is_active', true)->orderBy('name')->get()
            ->filter(fn ($t) => !$t->gender || !$g || str_starts_with($g, substr($t->gender, 0, 1)))->values();
    }

    /** Entitlement, used, pending and remaining days for each type in a year. */
    public function balances(int $staffId, int $year, ?string $gender = null): Collection
    {
        $types = $this->types($gender);
        $used = DB::table('leave_requests')->where('staff_id', $staffId)->whereYear('start_date', $year)
            ->whereIn('status', ['approved', 'pending_hod', 'pending_principal'])
            ->groupBy('leave_type_id', 'status')->selectRaw('leave_type_id, status, SUM(days) d')->get()->groupBy('leave_type_id');
        $adj = DB::table('leave_adjustments')->where('staff_id', $staffId)->where('year', $year)->groupBy('leave_type_id')
            ->selectRaw('leave_type_id, SUM(days) d')->pluck('d', 'leave_type_id');

        return $types->map(function ($t) use ($used, $adj) {
            $u = $used[$t->id] ?? collect();
            $approved = (float) $u->where('status', 'approved')->sum('d');
            $pending = (float) $u->whereIn('status', ['pending_hod', 'pending_principal'])->sum('d');
            $entitled = (float) $t->days_per_year + (float) ($adj[$t->id] ?? 0);
            $t->entitled = $entitled; $t->approved = $approved; $t->pending = $pending;
            $t->limited = (float) $t->days_per_year > 0;
            $t->remaining = $t->limited ? round($entitled - $approved - $pending, 1) : null;
            return $t;
        });
    }

    /** HODs for a staff member: same department, holding "Recommend leave". */
    public function hodsFor(object $staff): array
    {
        $dept = trim((string) ($staff->department ?? ''));
        if ($dept === '') return [];
        try { $hods = User::permission('Recommend leave')->pluck('id')->all(); } catch (\Throwable $e) { return []; }
        return DB::table('staffbioinfo')->whereIn('userid', $hods)->whereRaw('LOWER(TRIM(department)) = ?', [strtolower($dept)])
            ->where('userid', '!=', $staff->userid)->pluck('userid')->map(fn ($v) => (int) $v)->all();
    }

    public function approvers(): array
    {
        try { return User::permission('Approve leave')->pluck('id')->all(); } catch (\Throwable $e) { return []; }
    }

    /** Overlaps with another open/approved request? */
    public function overlaps(int $staffId, string $start, string $end, ?int $ignoreId = null): bool
    {
        return DB::table('leave_requests')->where('staff_id', $staffId)->whereIn('status', ['pending_hod', 'pending_principal', 'approved'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('start_date', '<=', $end)->where('end_date', '>=', $start)->exists();
    }

    public function submit(object $staff, array $d): int
    {
        $hods = $this->hodsFor($staff);
        $status = $hods ? 'pending_hod' : 'pending_principal';
        $id = DB::table('leave_requests')->insertGetId([
            'staff_id' => $staff->id, 'user_id' => $staff->userid, 'leave_type_id' => $d['leave_type_id'],
            'start_date' => $d['start_date'], 'end_date' => $d['end_date'], 'half_day' => !empty($d['half_day']), 'days' => $d['days'],
            'reason' => $d['reason'], 'attachment' => $d['attachment'] ?? null, 'relief_staff_id' => $d['relief_staff_id'] ?? null,
            'contact_phone' => $d['contact_phone'] ?? null, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $type = DB::table('leave_types')->where('id', $d['leave_type_id'])->value('name');
        $when = Carbon::parse($d['start_date'])->format('d M') . ' – ' . Carbon::parse($d['end_date'])->format('d M Y');
        $this->notify($hods ?: $this->approvers(), "Leave request: {$staff->name}", "{$type}, {$when} ({$d['days']} day(s)). Please review.", 'leave:new:' . $id);
        return $id;
    }

    public function recommend(object $req, int $hodId, bool $ok, ?string $note): void
    {
        DB::table('leave_requests')->where('id', $req->id)->update([
            'status' => $ok ? 'pending_principal' : 'rejected', 'hod_id' => $hodId, 'hod_at' => now(), 'hod_note' => $note, 'updated_at' => now(),
        ]);
        if ($ok) $this->notify($this->approvers(), 'Leave recommended: ' . $this->name($req->user_id), 'The HOD recommended this leave. It needs your approval.', 'leave:hod:' . $req->id);
        else $this->notify([$req->user_id], 'Leave not recommended', 'Your HOD did not recommend your leave request' . ($note ? ": \"$note\"" : '.'), 'leave:hodno:' . $req->id);
    }

    public function decide(object $req, int $approverId, bool $ok, ?string $note): void
    {
        DB::table('leave_requests')->where('id', $req->id)->update([
            'status' => $ok ? 'approved' : 'rejected', 'approver_id' => $approverId, 'approved_at' => now(), 'approver_note' => $note, 'updated_at' => now(),
        ]);
        $when = Carbon::parse($req->start_date)->format('d M') . ' – ' . Carbon::parse($req->end_date)->format('d M Y');
        $this->notify([$req->user_id], $ok ? 'Leave approved' : 'Leave not approved', ($ok ? "Your leave for {$when} is approved." : "Your leave for {$when} was not approved.") . ($note ? " Note: \"$note\"" : ''), 'leave:done:' . $req->id);
        if ($ok && $req->relief_staff_id) {
            $this->notify([$req->relief_staff_id], 'You are covering for ' . $this->name($req->user_id), "They are on leave {$when}. Please cover their classes/duties.", 'leave:relief:' . $req->id);
        }
    }

    /** Unpaid leave days inside a date range (for payroll). */
    public function unpaidDays(int $staffId, $from, $to): float
    {
        if (!self::available()) return 0;
        $rows = DB::table('leave_requests as r')->join('leave_types as t', 't.id', '=', 'r.leave_type_id')
            ->where('r.staff_id', $staffId)->where('r.status', 'approved')->where('t.paid', false)
            ->where('r.start_date', '<=', Carbon::parse($to)->toDateString())->where('r.end_date', '>=', Carbon::parse($from)->toDateString())
            ->get(['r.start_date', 'r.end_date', 'r.half_day']);
        $n = 0.0;
        foreach ($rows as $r) {
            $s = max(Carbon::parse($r->start_date), Carbon::parse($from)); $e = min(Carbon::parse($r->end_date), Carbon::parse($to));
            $n += $this->workingDays($s, $e, (bool) $r->half_day);
        }
        return $n;
    }

    /** Staff on approved leave on a date. */
    public function onLeave($date): Collection
    {
        $d = Carbon::parse($date)->toDateString();
        return DB::table('leave_requests as r')->join('leave_types as t', 't.id', '=', 'r.leave_type_id')->join('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('users as rl', 'rl.id', '=', 'r.relief_staff_id')
            ->where('r.status', 'approved')->where('r.start_date', '<=', $d)->where('r.end_date', '>=', $d)
            ->orderBy('u.name')->get(['r.*', 'u.name', 't.name as type', 't.color', 'rl.name as relief_name']);
    }

    protected function name(int $userId): string
    {
        return (string) DB::table('users')->where('id', $userId)->value('name');
    }

    protected function notify(array $userIds, string $title, string $body, string $key): void
    {
        if (!$userIds || !class_exists(PortalNotifier::class)) return;
        try { PortalNotifier::toUsers($userIds, $title, $body, route('leave.index'), 'system', $key); } catch (\Throwable $e) {}
    }
}
