<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollStatutoryRate;
use App\Models\StaffPayProfile;
use App\Services\Payment\PaystackGateway;
use App\Services\Payroll\PayrollCalculator;
use App\Services\Payroll\PayrollEngine;
use App\Services\Payroll\StatutoryRates;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll setup: statutory rates (with effective dates) and staff pay profiles.
 */
class PayrollSetupController extends Controller
{
    public const STATES = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu',
        'FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau',
        'Rivers','Sokoto','Taraba','Yobe','Zamfara'];

    public function __construct()
    {
        $this->middleware('permission:Manage payroll settings')->only(['rates', 'storeRate']);
        $this->middleware('permission:Manage staff pay profiles|Manage payroll settings')->only(['profiles', 'editProfile', 'saveProfile', 'verifyAccount', 'preview']);
    }

    // ── Statutory rates ─────────────────────────────────────────────────

    public function rates()
    {
        $rows = PayrollStatutoryRate::orderBy('code')->orderByDesc('effective_from')->get()->groupBy('code');
        return view('finance.payroll.rates', [
            'pagetitle' => 'Payroll Rates & Tax Bands',
            'groups' => $rows, 'current' => StatutoryRates::on(now()),
        ]);
    }

    public function storeRate(Request $request)
    {
        $code = $request->validate(['code' => 'required|in:' . implode(',', array_keys(PayrollStatutoryRate::CODES))])['code'];
        $v = $request->validate(['name' => 'required|string|max:150', 'effective_from' => 'required|date']);

        $config = match ($code) {
            'paye' => $this->payeConfig($request),
            'pension' => ['employee' => $this->pct($request, 'employee'), 'employer' => $this->pct($request, 'employer'), 'base' => 'basic + housing + transport'],
            'nhf' => ['rate' => $this->pct($request, 'rate'), 'base' => 'basic'],
            'nhia' => ['employee' => $this->pct($request, 'employee'), 'employer' => $this->pct($request, 'employer'), 'base' => $request->input('base') === 'gross' ? 'gross' : 'basic'],
            'nsitf', 'itf' => ['employer' => $this->pct($request, 'employer')],
            'limits' => [
                'min_net_pct' => $this->pct($request, 'min_net_pct'),
                'minimum_wage_monthly' => (float) $request->input('minimum_wage_monthly', 0),
                'require_different_approver' => $request->boolean('require_different_approver'),
            ],
        };
        if (is_string($config)) return back()->withErrors(['bands' => $config])->withInput();

        $from = Carbon::parse($v['effective_from'])->toDateString();
        DB::transaction(function () use ($code, $v, $from, $config, $request) {
            // End the version that was open on that date; drop any later ones (they're replaced).
            PayrollStatutoryRate::where('code', $code)->where('effective_from', '>=', $from)->delete();
            PayrollStatutoryRate::where('code', $code)->where('effective_from', '<', $from)
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from))
                ->update(['effective_to' => Carbon::parse($from)->subDay()->toDateString()]);
            PayrollStatutoryRate::create(['code' => $code, 'name' => $v['name'], 'effective_from' => $from, 'effective_to' => null,
                                          'config' => $config, 'updated_by' => $request->user()->id]);
        });

        return back()->with('success', PayrollStatutoryRate::CODES[$code] . " rates saved, effective {$from}. Payroll months already approved are not changed.");
    }

    protected function pct(Request $r, string $field): float
    {
        return round(max(0, min(100, (float) $r->input($field, 0))) / 100, 6);
    }

    protected function payeConfig(Request $r): array|string
    {
        $tos = (array) $r->input('band_to', []); $rates = (array) $r->input('band_rate', []);
        $bands = []; $prev = 0;
        foreach ($rates as $i => $rate) {
            if ($rate === null || $rate === '') continue;
            $to = ($tos[$i] ?? '') === '' ? null : (float) str_replace(',', '', $tos[$i]);
            if ($to !== null && $to <= $prev) return 'Each band must end higher than the one before.';
            $bands[] = ['to' => $to, 'rate' => round((float) $rate / 100, 6)];
            if ($to === null) break;
            $prev = $to;
        }
        if (!$bands || end($bands)['to'] !== null) return 'The last band must have no upper limit (leave "up to" empty).';
        return [
            'code' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $r->input('rule_code', 'CUSTOM'))) ?: 'CUSTOM',
            'method' => 'nta2025',
            'rent_relief_rate' => $this->pct($r, 'rent_relief_rate'),
            'rent_relief_cap' => (float) $r->input('rent_relief_cap', 0),
            'bands' => $bands,
        ];
    }

    // ── Staff pay profiles ──────────────────────────────────────────────

    public function profiles(Request $request)
    {
        $staff = DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')
            ->leftJoin('staff_pay_profiles as p', 'p.staff_id', '=', 's.id')
            ->when(Schema::hasColumn('staffbioinfo', 'deleted_at'), fn ($q) => $q->whereNull('s.deleted_at'))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('u.name', 'like', '%' . $request->search . '%')->orWhere('s.employmentid', 'like', '%' . $request->search . '%')))
            ->orderBy('u.name')
            ->get(['s.id', 's.employmentid', 'u.name', Schema::hasColumn('staffbioinfo', 'status') ? 's.status' : DB::raw("'active' as status"), 'p.id as profile_id']);

        $profiles = StaffPayProfile::whereIn('staff_id', $staff->pluck('id'))->get()->keyBy('staff_id');
        $gross = DB::table('staff_salary_structures')->where('is_active', true)->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString()))
            ->selectRaw('staff_id, MAX(basic_salary + housing_allowance + transport_allowance + meal_allowance + medical_allowance + utility_allowance + other_allowances) as gross')
            ->groupBy('staff_id')->pluck('gross', 'staff_id');

        $rows = $staff->map(function ($s) use ($profiles, $gross) {
            $p = $profiles[$s->id] ?? new StaffPayProfile(['staff_id' => $s->id]);
            $s->profile = $p; $s->gaps = $p->gaps(); $s->gross = $gross[$s->id] ?? null;
            if ($s->gross === null) array_unshift($s->gaps, 'salary structure');
            return $s;
        });
        if ($request->get('filter') === 'incomplete') $rows = $rows->filter(fn ($r) => $r->gaps)->values();
        if ($request->get('filter') === 'hold') $rows = $rows->filter(fn ($r) => $r->profile->pay_status === 'hold')->values();

        return view('finance.payroll.profiles', [
            'pagetitle' => 'Staff Pay Profiles', 'rows' => $rows,
            'complete' => $rows->filter(fn ($r) => !$r->gaps)->count(),
        ]);
    }

    public function editProfile(int $staff, PaystackGateway $paystack)
    {
        $s = DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.id', $staff)->first(['s.*', 'u.name', 'u.email']);
        abort_unless($s, 404);
        $scale = app(\App\Services\Payroll\SalaryScaleService::class);
        $hasScale = \App\Services\Payroll\SalaryScaleService::available();
        return view('finance.payroll.profile-edit', [
            'pagetitle' => 'Pay Profile — ' . $s->name, 's' => $s,
            'grades' => $hasScale ? \App\Models\SalaryGrade::where('is_active', true)->orderBy('sort')->orderBy('code')->get() : collect(),
            'placement' => $hasScale ? $scale->placement($staff, now()) : null,
            'gradeHistory' => $hasScale ? DB::table('staff_grade_history as h')->join('salary_grades as g', 'g.id', '=', 'h.grade_id')->where('h.staff_id', $staff)
                ->orderByDesc('h.effective_from')->orderByDesc('h.id')->limit(8)->get(['h.*', 'g.code']) : collect(),
            'structure' => DB::table('staff_salary_structures')->where('staff_id', $staff)->where('is_active', true)->whereNull('deleted_at')->orderByDesc('effective_from')->first(),
            'staffItems' => $hasScale ? DB::table('staff_pay_items as s')->join('pay_items as i', 'i.id', '=', 's.pay_item_id')->where('s.staff_id', $staff)
                ->where(fn ($q) => $q->whereNull('s.to_month')->orWhere('s.to_month', '>=', now()->subMonths(3)->startOfMonth()->toDateString()))
                ->orderByDesc('s.from_month')->get(['s.*', 'i.name', 'i.type', 'i.calc', 'i.default_amount', 'i.default_rate']) : collect(),
            'payItems' => $hasScale ? \App\Models\PayItem::where('is_active', true)->orderBy('type')->orderBy('name')->get() : collect(),
            'p' => StaffPayProfile::firstOrNew(['staff_id' => $staff]),
            'banks' => $paystack->banks(), 'states' => self::STATES,
            'pfas' => ['Access Pensions','ARM Pension','AXA Mansard Pensions','CardinalStone Pensions','Crusader Sterling Pensions','FCMB Pensions','Fidelity Pension',
                       'Guaranty Trust Pension','Leadway Pensure','Nigerian University Pension','NLPC Pension','NPF Pensions','OAK Pensions','Pensions Alliance (PAL)',
                       'Premium Pension','Radix Pension','Stanbic IBTC Pension','Tangerine APT Pensions','Trustfund Pensions','Veritas Glanvills Pensions'],
        ]);
    }

    public function saveProfile(Request $request, int $staff, PaystackGateway $paystack)
    {
        $d = $request->validate([
            'employment_type' => 'required|in:' . implode(',', array_keys(StaffPayProfile::TYPES)),
            'bank_code' => 'nullable|string|max:20', 'account_number' => 'nullable|digits:10',
            'tin' => 'nullable|string|max:30', 'tax_state' => 'nullable|string|max:40',
            'pfa_name' => 'nullable|string|max:100', 'rsa_pin' => 'nullable|string|max:30', 'nhf_number' => 'nullable|string|max:30',
            'annual_rent' => 'nullable|numeric|min:0|max:1000000000', 'other_reliefs_annual' => 'nullable|numeric|min:0|max:1000000000',
            'pay_status' => 'required|in:active,hold,exited', 'hold_reason' => 'nullable|string|max:255', 'exit_date' => 'nullable|date',
        ]);

        $p = StaffPayProfile::firstOrNew(['staff_id' => $staff]);
        $p->fill([
            'employment_type' => $d['employment_type'], 'tin' => $d['tin'] ?? null, 'tax_state' => $d['tax_state'] ?? null,
            'pfa_name' => $d['pfa_name'] ?? null, 'rsa_pin' => $d['rsa_pin'] ?? null, 'nhf_number' => $d['nhf_number'] ?? null,
            'paye_enabled' => $request->boolean('paye_enabled'), 'pension_enabled' => $request->boolean('pension_enabled'),
            'nhf_enabled' => $request->boolean('nhf_enabled'), 'nhia_enabled' => $request->boolean('nhia_enabled'),
            'annual_rent' => $d['annual_rent'] ?? 0, 'other_reliefs_annual' => $d['other_reliefs_annual'] ?? 0,
            'pay_status' => $d['pay_status'], 'hold_reason' => $d['pay_status'] === 'hold' ? ($d['hold_reason'] ?? null) : null,
            'exit_date' => $d['exit_date'] ?? null, 'updated_by' => $request->user()->id,
        ]);

        $msg = 'Pay profile saved.';
        $newNumber = $d['account_number'] ?? null;
        $bankChanged = ($d['bank_code'] ?? null) !== $p->bank_code;
        if ($newNumber || $bankChanged) {
            $number = $newNumber ?: $p->accountNumber();
            $bank = collect($paystack->banks())->firstWhere('code', $d['bank_code'] ?? '');
            $p->bank_code = $d['bank_code'] ?? null;
            $p->bank_name = $bank['name'] ?? ($p->bank_code ? $p->bank_name : null);
            $p->setAccount($number);
            // Any change to bank or number needs a fresh check and a new Paystack payee.
            $p->account_verified_at = null;
            $p->paystack_recipient_code = null;
            $p->account_name = null;
            if ($number && $p->bank_code) {
                $res = $paystack->resolveAccount($number, $p->bank_code);
                if ($res['ok']) {
                    $p->account_name = $res['account_name'];
                    $p->account_verified_at = now();
                    $msg .= ' Account verified: ' . $res['account_name'] . '.';
                } else {
                    $msg .= ' The account could not be verified (' . $res['message'] . '). Check the number and bank.';
                }
            }
            if ($p->exists) $this->notifyBankChange($staff, $p);
        }
        $p->save();

        return redirect()->route('payroll.profiles.edit', $staff)->with('success', $msg);
    }

    /** AJAX: look up the account name before saving. */
    public function verifyAccount(Request $request, PaystackGateway $paystack)
    {
        $d = $request->validate(['bank_code' => 'required|string|max:20', 'account_number' => 'required|digits:10']);
        return response()->json($paystack->resolveAccount($d['account_number'], $d['bank_code']));
    }

    /** Explain this month's calculation for one staff member. */
    public function preview(Request $request, int $staff, PayrollEngine $engine)
    {
        $s = DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.id', $staff)->first(['s.*', 'u.name']);
        abort_unless($s, 404);
        $month = Carbon::parse($request->get('month', now()->format('Y-m')) . '-01');
        $period = new PayrollPeriod(['start_date' => $month->copy()->startOfMonth(), 'end_date' => $month->copy()->endOfMonth(), 'status' => 'draft']);
        $r = $engine->calculate($s, $period);

        return view('finance.payroll.preview', ['pagetitle' => 'Pay Calculation — ' . $s->name, 's' => $s, 'r' => $r, 'month' => $month]);
    }

    /** SMS/bell to the staff member when their bank details change (fraud check). */
    protected function notifyBankChange(int $staffId, StaffPayProfile $p): void
    {
        try {
            $user = DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.id', $staffId)->first(['u.id', 'u.name', 'u.phone_number', 's.phonenumber']);
            if (!$user) return;
            $text = "Your salary bank details were changed to {$p->bank_name} ****{$p->account_last4} on " . now()->format('d M Y H:i') . '. If this was not you, contact the bursar immediately.';
            if (class_exists(\App\Services\Messaging\PortalNotifier::class)) {
                \App\Services\Messaging\PortalNotifier::toUsers([$user->id], 'Salary bank details changed', $text, null, 'payment', 'bank-change:' . $staffId . ':' . now()->timestamp);
            }
            $phone = \App\Services\Messaging\MessagingService::normalizePhone($user->phone_number ?: $user->phonenumber);
            $msg = app(\App\Services\Messaging\MessagingService::class);
            if ($phone && $msg->enabled('sms')) $msg->send('sms', $phone, $text, ['name' => $user->name]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Bank change alert failed', ['staff' => $staffId, 'error' => $e->getMessage()]);
        }
    }
}
