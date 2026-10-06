<?php

namespace App\Http\Controllers;

use App\Services\Leave\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Staff leave: apply, HOD recommendation, principal approval, records. */
class LeaveController extends Controller
{
    public function __construct(protected LeaveService $svc)
    {
        $this->middleware('permission:Recommend leave|Approve leave')->only(['approvals', 'act']);
        $this->middleware('permission:View leave records|Manage leave types')->only(['records']);
        $this->middleware('permission:Manage leave types')->only(['storeType', 'updateType', 'adjust', 'saveReminders']);
        $this->middleware('permission:Approve leave|View leave records')->only(['markResumed']);
    }

    protected function me(Request $request): object
    {
        $s = $this->svc->staffFor($request->user()->id);
        abort_unless($s, 404, 'No staff record is linked to your account.');
        return $s;
    }

    public function index(Request $request)
    {
        $s = $this->me($request);
        $year = (int) ($request->get('year') ?: now()->year);
        $mine = DB::table('leave_requests as r')->join('leave_types as t', 't.id', '=', 'r.leave_type_id')
            ->leftJoin('users as rl', 'rl.id', '=', 'r.relief_staff_id')
            ->where('r.staff_id', $s->id)->orderByDesc('r.start_date')->limit(50)
            ->get(['r.*', 't.name as type', 't.color', 'rl.name as relief_name']);
        $u = $request->user();
        return view('leave.index', [
            'pagetitle' => 'My Leave', 's' => $s, 'year' => $year, 'mine' => $mine,
            'balances' => $this->svc->balances((int) $s->id, $year, $s->gender ?? null),
            'types' => $this->svc->types($s->gender ?? null),
            'colleagues' => DB::table('staffbioinfo as st')->join('users as us', 'us.id', '=', 'st.userid')->where('st.id', '!=', $s->id)->orderBy('us.name')->pluck('us.name', 'us.id'),
            'hasHod' => (bool) $this->svc->hodsFor($s),
            'queue' => ($u->can('Recommend leave') || $u->can('Approve leave')) ? $this->queueCount($request) : 0,
        ]);
    }

    public function store(Request $request)
    {
        $s = $this->me($request);
        $d = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id', 'start_date' => 'required|date|after_or_equal:' . now()->subDays(14)->toDateString(),
            'end_date' => 'required|date|after_or_equal:start_date', 'half_day' => 'nullable|boolean', 'reason' => 'required|string|min:5|max:2000',
            'relief_staff_id' => 'nullable|exists:users,id', 'contact_phone' => 'nullable|string|max:30', 'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'handover_note' => 'nullable|string|max:3000',
        ]);
        $type = DB::table('leave_types')->find($d['leave_type_id']);
        $d['days'] = $this->svc->workingDays($d['start_date'], $d['end_date'], $request->boolean('half_day'));
        if ($d['days'] <= 0) return back()->withInput()->with('error', 'Those dates are all weekends or school holidays.');
        if ($this->svc->overlaps((int) $s->id, $d['start_date'], $d['end_date'])) return back()->withInput()->with('error', 'You already have leave requested or approved for some of those days.');
        if ($type->requires_document && !$request->hasFile('attachment')) return back()->withInput()->with('error', $type->name . ' needs a supporting document (e.g. medical note).');
        $bal = $this->svc->balances((int) $s->id, Carbon::parse($d['start_date'])->year, $s->gender ?? null)->firstWhere('id', $type->id);
        if ($bal && $bal->limited && $d['days'] > $bal->remaining) {
            return back()->withInput()->with('error', "You have {$bal->remaining} day(s) of {$type->name} left this year, but asked for {$d['days']}.");
        }
        if ($request->hasFile('attachment')) $d['attachment'] = $request->file('attachment')->store('leave/' . $s->id, 'local');
        $d['half_day'] = $request->boolean('half_day');

        $id = $this->svc->submit($s, $d);
        if (!empty($d['handover_note']) && \Illuminate\Support\Facades\Schema::hasColumn('leave_requests', 'handover_note')) {
            DB::table('leave_requests')->where('id', $id)->update(['handover_note' => $d['handover_note']]);
        }
        return redirect()->route('leave.index')->with('success', "Leave request sent ({$d['days']} working day(s)). You'll be notified when it's decided.");
    }

    public function cancel(Request $request, int $id)
    {
        $s = $this->me($request);
        $r = DB::table('leave_requests')->where('id', $id)->where('staff_id', $s->id)->first();
        abort_unless($r, 404);
        $can = in_array($r->status, ['pending_hod', 'pending_principal'], true) || ($r->status === 'approved' && Carbon::parse($r->start_date)->isFuture());
        if (!$can) return back()->with('error', 'This leave can no longer be cancelled here. Ask the principal.');
        DB::table('leave_requests')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
        return back()->with('success', 'Leave request cancelled.');
    }

    /** Staff confirms they are back at work. */
    public function resume(Request $request, int $id)
    {
        $s = $this->me($request);
        $r = DB::table('leave_requests')->where('id', $id)->where('staff_id', $s->id)->where('status', 'approved')->first();
        abort_unless($r, 404);
        DB::table('leave_requests')->where('id', $id)->update(['resumed_at' => now(), 'resume_source' => 'staff', 'updated_at' => now()]);
        $mgr = array_values(array_unique(array_merge($this->svc->hodsFor($s), $this->svc->approvers())));
        if ($mgr && class_exists(\App\Services\Messaging\PortalNotifier::class)) {
            \App\Services\Messaging\PortalNotifier::toUsers($mgr, $s->name . ' is back', 'Confirmed resumption after leave on ' . now()->format('D j M, h:i A') . '.', route('leave.records'), 'system', 'leave:back:' . $id);
        }
        return back()->with('success', 'Welcome back! Your resumption has been recorded.');
    }

    /** Principal / records officer marks someone as resumed. */
    public function markResumed(Request $request, int $id)
    {
        DB::table('leave_requests')->where('id', $id)->whereNull('resumed_at')->update(['resumed_at' => now(), 'resume_source' => 'admin', 'updated_at' => now()]);
        return back()->with('success', 'Marked as resumed.');
    }

    /** Ask to extend an approved leave (creates a linked request). */
    public function extend(Request $request, int $id)
    {
        $s = $this->me($request);
        $r = DB::table('leave_requests')->where('id', $id)->where('staff_id', $s->id)->where('status', 'approved')->first();
        abort_unless($r, 404);
        $d = $request->validate(['end_date' => 'required|date|after:' . $r->end_date, 'reason' => 'required|string|min:5|max:1000', 'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $start = Carbon::parse($r->end_date)->addDay()->toDateString();
        $days = $this->svc->workingDays($start, $d['end_date']);
        if ($days <= 0) return back()->with('error', 'The extra days are all weekends or holidays.');
        if ($this->svc->overlaps((int) $s->id, $start, $d['end_date'])) return back()->with('error', 'You already have leave in that period.');
        $newId = $this->svc->submit($s, ['leave_type_id' => $r->leave_type_id, 'start_date' => $start, 'end_date' => $d['end_date'], 'days' => $days,
            'reason' => 'Extension: ' . $d['reason'], 'relief_staff_id' => $r->relief_staff_id, 'contact_phone' => $r->contact_phone,
            'attachment' => $request->hasFile('attachment') ? $request->file('attachment')->store('leave/' . $s->id, 'local') : null]);
        if (\Illuminate\Support\Facades\Schema::hasColumn('leave_requests', 'extension_of')) DB::table('leave_requests')->where('id', $newId)->update(['extension_of' => $id]);
        return back()->with('success', "Extension requested ({$days} more working day(s)).");
    }

    public function saveReminders(Request $request)
    {
        $s = \App\Services\Leave\LeaveReminderService::settings();
        $s->is_active = $request->boolean('is_active');
        $s->config = array_merge($s->config, [
            'channels' => array_values(array_intersect((array) $request->input('channels', []), ['sms', 'whatsapp', 'email'])),
            'countdown' => collect(explode(',', (string) $request->input('countdown', '5,3')))->map(fn ($v) => (int) trim($v))->filter(fn ($v) => $v > 1 && $v < 60)->unique()->values()->all(),
            'notify_cover' => $request->boolean('notify_cover'), 'notify_managers_on_resume' => $request->boolean('notify_managers_on_resume'),
            'overdue_after_days' => max(1, min(10, (int) $request->input('overdue_after_days', 1))), 'auto_resume_on_login' => $request->boolean('auto_resume_on_login'),
        ]);
        $s->save();
        return back()->with('success', 'Leave reminder settings saved.');
    }

    protected function hodDept(Request $request): ?string
    {
        $me = $this->svc->staffFor($request->user()->id);
        return $me && trim((string) $me->department) !== '' ? strtolower(trim($me->department)) : null;
    }

    protected function queueQuery(Request $request)
    {
        $u = $request->user();
        $dept = $u->can('Recommend leave') ? $this->hodDept($request) : null;
        return DB::table('leave_requests as r')->join('staffbioinfo as s', 's.id', '=', 'r.staff_id')
            ->where(function ($q) use ($u, $dept) {
                $q->whereRaw('1=0');
                if ($dept) $q->orWhere(fn ($w) => $w->where('r.status', 'pending_hod')->whereRaw('LOWER(TRIM(s.department)) = ?', [$dept])->where('r.user_id', '!=', $u->id));
                if ($u->can('Approve leave')) $q->orWhere('r.status', 'pending_principal')->orWhere(fn ($w) => $w->where('r.status', 'pending_hod')->where('r.created_at', '<=', now()->subDays(3)));
            });
    }

    protected function queueCount(Request $request): int
    {
        return $this->queueQuery($request)->count();
    }

    public function approvals(Request $request)
    {
        $pending = $this->queueQuery($request)->join('users as u', 'u.id', '=', 'r.user_id')->join('leave_types as t', 't.id', '=', 'r.leave_type_id')
            ->leftJoin('users as rl', 'rl.id', '=', 'r.relief_staff_id')->leftJoin('users as h', 'h.id', '=', 'r.hod_id')
            ->orderBy('r.start_date')->get(['r.*', 'u.name', 's.department', 't.name as type', 't.color', 't.paid', 'rl.name as relief_name', 'h.name as hod_name']);
        foreach ($pending as $p) {
            $b = $this->svc->balances((int) $p->staff_id, Carbon::parse($p->start_date)->year)->firstWhere('id', $p->leave_type_id);
            $p->remaining = $b?->remaining; $p->limited = $b?->limited;
            $p->classes = class_exists(\App\Services\Leave\LeaveReminderService::class) ? app(\App\Services\Leave\LeaveReminderService::class)->affectedClasses((int) $p->user_id, $p->start_date, $p->end_date) : [];
            $p->clash = DB::table('leave_requests as o')->join('staffbioinfo as os', 'os.id', '=', 'o.staff_id')->join('users as ou', 'ou.id', '=', 'o.user_id')
                ->where('o.status', 'approved')->where('o.id', '!=', $p->id)->whereRaw('LOWER(TRIM(os.department)) = ?', [strtolower(trim((string) $p->department))])
                ->where('o.start_date', '<=', $p->end_date)->where('o.end_date', '>=', $p->start_date)->pluck('ou.name')->implode(', ');
        }
        $recent = DB::table('leave_requests as r')->join('users as u', 'u.id', '=', 'r.user_id')->join('leave_types as t', 't.id', '=', 'r.leave_type_id')
            ->where(fn ($q) => $q->where('r.hod_id', $request->user()->id)->orWhere('r.approver_id', $request->user()->id))
            ->orderByDesc('r.updated_at')->limit(15)->get(['r.*', 'u.name', 't.name as type']);
        return view('leave.approvals', ['pagetitle' => 'Leave Approvals', 'pending' => $pending, 'recent' => $recent,
            'onLeave' => $this->svc->onLeave(now())]);
    }

    public function act(Request $request, int $id)
    {
        $d = $request->validate(['decision' => 'required|in:yes,no', 'note' => 'nullable|string|max:500']);
        $r = DB::table('leave_requests')->find($id);
        abort_unless($r, 404);
        $u = $request->user(); $ok = $d['decision'] === 'yes';
        if ($d['decision'] === 'no' && !$request->filled('note')) return back()->with('error', 'Please give a reason when declining.');

        if ($r->status === 'pending_principal' || ($r->status === 'pending_hod' && $u->can('Approve leave'))) {
            abort_unless($u->can('Approve leave'), 403);
            $this->svc->decide($r, $u->id, $ok, $d['note'] ?? null);
            return back()->with('success', $ok ? 'Leave approved.' : 'Leave declined.');
        }
        if ($r->status === 'pending_hod') {
            abort_unless($u->can('Recommend leave') && (int) $r->user_id !== (int) $u->id, 403);
            $this->svc->recommend($r, $u->id, $ok, $d['note'] ?? null);
            return back()->with('success', $ok ? 'Recommended — sent to the principal.' : 'Not recommended.');
        }
        return back()->with('error', 'This request has already been decided.');
    }

    public function attachment(Request $request, int $id)
    {
        $r = DB::table('leave_requests')->find($id);
        $u = $request->user();
        abort_unless($r && $r->attachment && ((int) $r->user_id === (int) $u->id || $u->can('Recommend leave') || $u->can('Approve leave') || $u->can('View leave records')), 404);
        abort_unless(Storage::disk('local')->exists($r->attachment), 404);
        return Storage::disk('local')->response($r->attachment);
    }

    public function records(Request $request)
    {
        $q = DB::table('leave_requests as r')->join('users as u', 'u.id', '=', 'r.user_id')->join('leave_types as t', 't.id', '=', 'r.leave_type_id')
            ->join('staffbioinfo as s', 's.id', '=', 'r.staff_id')
            ->when($request->filled('status'), fn ($x) => $x->where('r.status', $request->status))
            ->when($request->filled('type'), fn ($x) => $x->where('r.leave_type_id', $request->type))
            ->when($request->filled('search'), fn ($x) => $x->where('u.name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('month'), fn ($x) => $x->where('r.start_date', '<=', Carbon::parse($request->month . '-01')->endOfMonth())->where('r.end_date', '>=', $request->month . '-01'))
            ->orderByDesc('r.start_date');
        $week = collect(range(0, 6))->map(fn ($i) => ['date' => now()->startOfWeek()->addDays($i), 'people' => $this->svc->onLeave(now()->startOfWeek()->addDays($i))]);
        return view('leave.records', [
            'pagetitle' => 'Leave Records', 'rows' => $q->select('r.*', 'u.name', 's.department', 't.name as type', 't.color')->paginate(30)->withQueryString(),
            'reminders' => class_exists(\App\Services\Leave\LeaveReminderService::class) ? \App\Services\Leave\LeaveReminderService::settings() : null,
            'notBack' => \Illuminate\Support\Facades\Schema::hasColumn('leave_requests', 'resumed_at')
                ? DB::table('leave_requests as r')->join('users as u', 'u.id', '=', 'r.user_id')->where('r.status', 'approved')->whereNull('r.resumed_at')
                    ->where('r.end_date', '<', now()->subDay()->toDateString())->where('r.end_date', '>=', now()->subDays(30)->toDateString())->orderBy('r.end_date')->get(['r.id', 'r.end_date', 'u.name'])
                : collect(),
            'types' => DB::table('leave_types')->orderBy('name')->get(), 'week' => $week,
            'staff' => DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->orderBy('u.name')->pluck('u.name', 's.id'),
            'stats' => [
                'today' => $this->svc->onLeave(now())->count(),
                'pending' => DB::table('leave_requests')->whereIn('status', ['pending_hod', 'pending_principal'])->count(),
                'year_days' => (float) DB::table('leave_requests')->where('status', 'approved')->whereYear('start_date', now()->year)->sum('days'),
            ],
        ]);
    }

    public function exportRecords(Request $request)
    {
        abort_unless($request->user()->can('View leave records'), 403);
        $rows = DB::table('leave_requests as r')->join('users as u', 'u.id', '=', 'r.user_id')
            ->join('leave_types as t', 't.id', '=', 'r.leave_type_id')
            ->join('staffbioinfo as s', 's.id', '=', 'r.staff_id')
            ->when($request->filled('status'), fn ($x) => $x->where('r.status', $request->status))
            ->when($request->filled('type'), fn ($x) => $x->where('r.leave_type_id', $request->type))
            ->when($request->filled('search'), fn ($x) => $x->where('u.name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('month'), fn ($x) => $x->where('r.start_date', '<=', Carbon::parse($request->month . '-01')->endOfMonth())->where('r.end_date', '>=', $request->month . '-01'))
            ->orderBy('r.start_date')
            ->get(['u.name', 's.department', 't.name as type', 't.paid', 'r.start_date', 'r.end_date', 'r.days', 'r.half_day', 'r.status', 'r.approver_note']);

        return response()->streamDownload(function () use ($rows) {
            $o = fopen('php://output', 'w');
            fputcsv($o, ['Staff', 'Department', 'Leave type', 'Paid', 'From', 'To', 'Days', 'Half day', 'Status', 'Note']);
            foreach ($rows as $r) {
                fputcsv($o, [$r->name, $r->department, $r->type, $r->paid ? 'Yes' : 'No',
                    Carbon::parse($r->start_date)->toDateString(), Carbon::parse($r->end_date)->toDateString(),
                    $r->days, $r->half_day ? 'Yes' : 'No', ucwords(str_replace('_', ' ', $r->status)), $r->approver_note]);
            }
            fclose($o);
        }, 'staff-leave-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function hrBalances(Request $request)
    {
        abort_unless($request->user()->can('View leave records') || $request->user()->can('Manage leave types'), 403);
        $year = (int) ($request->get('year') ?: now()->year);

        $types = DB::table('leave_types')->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'color', 'days_per_year', 'carry_over_max']);
        $limited = $types->where('days_per_year', '>', 0)->values();

        $staff = DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')
            ->when($request->filled('search'), fn ($x) => $x->where('u.name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('department'), fn ($x) => $x->where('s.department', $request->department))
            ->orderBy('u.name')->select('s.id', 's.gender', 's.department', 'u.name')
            ->paginate(25)->withQueryString();

        $rows = collect($staff->items())->map(function ($st) use ($year) {
            $st->balances = $this->svc->balances((int) $st->id, $year, $st->gender ?? null)->keyBy('id');
            return $st;
        });

        return view('leave.balances', [
            'pagetitle' => 'Leave Balances', 'year' => $year, 'rows' => $rows, 'staff' => $staff,
            'types' => $limited,
            'departments' => DB::table('staffbioinfo')->whereNotNull('department')->where('department', '!=', '')
                ->distinct()->orderBy('department')->pluck('department'),
            'carryTypes' => $types->where('carry_over_max', '>', 0)->pluck('name')->all(),
        ]);
    }

    public function storeType(Request $request)
    {
        $d = $this->typeData($request);
        DB::table('leave_types')->insert($d + ['created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Leave type added.');
    }

    public function updateType(Request $request, int $id)
    {
        DB::table('leave_types')->where('id', $id)->update($this->typeData($request, $id) + ['updated_at' => now()]);
        return back()->with('success', 'Leave type saved.');
    }

    protected function typeData(Request $request, ?int $id = null): array
    {
        $d = $request->validate(['name' => 'required|string|max:80', 'code' => 'required|alpha_dash|max:20|unique:leave_types,code' . ($id ? ",$id" : ''),
            'days_per_year' => 'required|numeric|min:0|max:366', 'gender' => 'nullable|in:male,female', 'carry_over_max' => 'nullable|numeric|min:0|max:60', 'color' => 'nullable|string|max:20']);
        return ['name' => $d['name'], 'code' => strtoupper($d['code']), 'days_per_year' => $d['days_per_year'], 'gender' => $d['gender'] ?? null,
            'carry_over_max' => $d['carry_over_max'] ?? 0, 'color' => $d['color'] ?? '#0f766e', 'paid' => $request->boolean('paid'),
            'requires_document' => $request->boolean('requires_document'), 'is_active' => $request->boolean('is_active', true)];
    }

    /** Add or remove days for one staff member (e.g. carried over, extra granted). */
    public function adjust(Request $request)
    {
        $d = $request->validate(['staff_id' => 'required|exists:staffbioinfo,id', 'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2020|max:2100', 'days' => 'required|numeric|between:-366,366|not_in:0', 'note' => 'required|string|max:255']);
        DB::table('leave_adjustments')->insert($d + ['created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Balance adjusted.');
    }
}
