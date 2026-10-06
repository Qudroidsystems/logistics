<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\StaffDutyClaim;
use App\Services\Payroll\AttendancePayService;
use App\Support\FinanceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Attendance → pay rules, the monthly impact report, and extra-duty claim approvals. */
class AttendancePayController extends Controller
{
    public function __construct(protected AttendancePayService $svc)
    {
        $this->middleware('permission:View payroll|Approve duty claims|Manage payroll settings')->only(['index', 'claims']);
        $this->middleware('permission:Manage payroll settings')->only(['saveSettings']);
        $this->middleware('permission:Approve duty claims')->only(['decide', 'storeClaim']);
    }

    protected function staff()
    {
        return DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->orderBy('u.name')->get(['s.id', 'u.name', 's.date_of_employment']);
    }

    public function index(Request $request)
    {
        $periods = PayrollPeriod::orderByDesc('start_date')->limit(18)->get();
        $period = $request->filled('period') ? PayrollPeriod::find($request->period) : ($periods->firstWhere(fn ($p) => $p->start_date <= now() && $p->end_date >= now()) ?? $periods->first());
        $S = $this->svc->settings();
        $rows = collect();
        if ($period) {
            $gross = DB::table('payroll_runs')->where('payroll_period_id', $period->id)->pluck('total_earnings', 'staff_id');
            foreach ($this->staff() as $st) {
                $sum = $this->svc->summary((int) $st->id, $period, $st->date_of_employment ?? null);
                $exempt = in_array((int) $st->id, array_map('intval', (array) $S['exempt_staff']), true);
                $g = (float) ($gross[$st->id] ?? 0);
                $rate = $sum['working_days'] ? $g / $sum['working_days'] : 0;
                $days = !$exempt && $sum['has_data'] && $S['deduct_absence'] ? max(0, $sum['absent'] - (int) $S['grace_absences']) : 0;
                $lateDays = !$exempt && $sum['has_data'] && (int) $S['lates_per_day'] > 0 ? intdiv($sum['late'], (int) $S['lates_per_day']) : 0;
                $rows->push((object) ($sum + ['id' => $st->id, 'name' => $st->name, 'exempt' => $exempt, 'est' => round($rate * ($days + $lateDays), 2), 'days' => $days, 'late_days' => $lateDays]));
            }
        }
        return view('finance.payroll.attendance-pay', [
            'pagetitle' => 'Attendance & Pay', 'periods' => $periods, 'period' => $period, 'rows' => $rows, 'settings' => $S, 'staff' => $this->staff(),
            'pendingClaims' => StaffDutyClaim::where('status', 'pending')->count(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $d = $request->validate([
            'lates_per_day' => 'required|integer|min:0|max:30', 'grace_absences' => 'required|integer|min:0|max:10',
            'exempt_staff' => 'nullable|array', 'exempt_staff.*' => 'integer',
            'rates.extra_lesson' => 'required|numeric|min:0', 'rates.overtime_hour' => 'required|numeric|min:0', 'rates.weekend_duty' => 'required|numeric|min:0',
        ]);
        FinanceSettings::put('attendance_pay', [
            'enabled' => $request->boolean('enabled'), 'deduct_absence' => $request->boolean('deduct_absence'), 'overtime_taxable' => $request->boolean('overtime_taxable'),
            'lates_per_day' => (int) $d['lates_per_day'], 'grace_absences' => (int) $d['grace_absences'],
            'exempt_staff' => array_map('intval', $d['exempt_staff'] ?? []),
            'rates' => array_map('floatval', $d['rates']) + ['other' => 0],
        ], (int) auth()->id());
        return back()->with('success', 'Attendance pay rules saved. They apply the next time a payroll month is calculated.');
    }

    public function claims(Request $request)
    {
        $status = $request->get('status', 'pending');
        $claims = StaffDutyClaim::query()->leftJoin('staffbioinfo as s', 's.id', '=', 'staff_duty_claims.staff_id')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->select('staff_duty_claims.*', 'u.name as staff_name')
            ->when($status !== 'all', fn ($q) => $q->where('staff_duty_claims.status', $status))
            ->latest('work_date')->paginate(40)->withQueryString();
        return view('finance.payroll.claims', [
            'pagetitle' => 'Extra Duty Claims', 'claims' => $claims, 'status' => $status, 'staff' => $this->staff(), 'rates' => $this->svc->settings()['rates'],
            'totals' => StaffDutyClaim::groupBy('status')->selectRaw('status, COUNT(*) n, SUM(amount) amt')->get()->keyBy('status'),
        ]);
    }

    public function decide(Request $request)
    {
        $d = $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer', 'action' => 'required|in:approve,reject', 'reason' => 'nullable|required_if:action,reject|string|max:255']);
        $me = (int) auth()->id();
        $mine = DB::table('staffbioinfo')->where('userid', $me)->value('id');
        $q = StaffDutyClaim::whereIn('id', $d['ids'])->where('status', 'pending')->when($mine, fn ($x) => $x->where('staff_id', '!=', $mine));
        $n = $d['action'] === 'approve'
            ? $q->update(['status' => 'approved', 'approved_by' => $me, 'approved_at' => now()])
            : $q->update(['status' => 'rejected', 'approved_by' => $me, 'approved_at' => now(), 'rejection_reason' => $d['reason']]);
        return back()->with('success', "{$n} claim(s) " . ($d['action'] === 'approve' ? 'approved — they will be paid in the next payroll.' : 'declined.'));
    }

    public function storeClaim(Request $request)
    {
        $d = $request->validate([
            'staff_id' => 'required|integer|exists:staffbioinfo,id', 'work_date' => 'required|date|before_or_equal:today',
            'type' => 'required|in:' . implode(',', array_keys(StaffDutyClaim::TYPES)), 'quantity' => 'required|numeric|min:0.5|max:100',
            'description' => 'required|string|max:255', 'amount' => 'nullable|numeric|min:0',
        ]);
        $rate = $this->svc->rate($d['type']);
        $amount = $d['type'] === 'other' ? (float) ($d['amount'] ?? 0) : round($rate * (float) $d['quantity'], 2);
        if ($amount <= 0) return back()->with('error', 'Enter an amount.');
        StaffDutyClaim::create($d + ['rate' => $d['type'] === 'other' ? $amount : $rate, 'amount' => $amount, 'status' => 'approved',
            'submitted_by' => auth()->id(), 'approved_by' => auth()->id(), 'approved_at' => now()]);
        return back()->with('success', 'Duty recorded and approved.');
    }
}
