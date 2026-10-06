<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use App\Models\StaffPayProfile;
use App\Models\StaffSalaryStructure;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Builds a payroll month: one payroll_run per staff member plus a permanent
 * copy of every payslip line (payroll_run_lines).
 */
class PayrollEngine
{
    /** Fixed salary-structure columns → payslip lines. */
    public const COMPONENTS = [
        'basic_salary'        => ['BASIC', 'Basic salary', true, true],     // code, label, pensionable, is_basic
        'housing_allowance'   => ['HOUSING', 'Housing allowance', true, false],
        'transport_allowance' => ['TRANSPORT', 'Transport allowance', true, false],
        'meal_allowance'      => ['MEAL', 'Meal allowance', false, false],
        'medical_allowance'   => ['MEDICAL', 'Medical allowance', false, false],
        'utility_allowance'   => ['UTILITY', 'Utility allowance', false, false],
        'other_allowances'    => ['OTHER', 'Other allowances', false, false],
    ];

    public function structureFor(int $staffId, PayrollPeriod $period): ?StaffSalaryStructure
    {
        return StaffSalaryStructure::where('staff_id', $staffId)->where('is_active', true)
            ->where('effective_from', '<=', $period->end_date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $period->start_date))
            ->orderByDesc('effective_from')->first();
    }

    public function profileFor(int $staffId): StaffPayProfile
    {
        return StaffPayProfile::firstOrCreate(['staff_id' => $staffId]);
    }

    /** Share of the month worked, from employment start / exit dates. */
    public function proration(PayrollPeriod $period, ?string $start, ?string $exit): float
    {
        $ps = Carbon::parse($period->start_date)->startOfDay();
        $pe = Carbon::parse($period->end_date)->startOfDay();
        $from = $start ? max($ps, Carbon::parse($start)->startOfDay()) : $ps;
        $to   = $exit ? min($pe, Carbon::parse($exit)->startOfDay()) : $pe;
        if ($to->lt($from)) return 0.0;
        $days = $ps->diffInDays($pe) + 1;
        $worked = $from->diffInDays($to) + 1;
        return $worked >= $days ? 1.0 : round($worked / $days, 4);
    }

    /** Earnings lines for the calculator from a salary structure. */
    public function earnings(StaffSalaryStructure $s): array
    {
        $lines = [];
        foreach (self::COMPONENTS as $col => [$code, $label, $pensionable, $basic]) {
            $amt = (float) ($s->$col ?? 0);
            if ($amt > 0) $lines[] = ['code' => $code, 'label' => $label, 'amount' => $amt, 'taxable' => true, 'pensionable' => $pensionable, 'basic' => $basic];
        }
        $custom = $s->custom_allowances;
        if (is_string($custom)) $custom = json_decode($custom, true);
        foreach ((array) $custom as $i => $c) {
            $amt = (float) ($c['amount'] ?? 0);
            if ($amt <= 0) continue;
            $lines[] = [
                'code' => 'CUSTOM_' . Str::upper(Str::slug($c['name'] ?? ('item' . $i), '_')),
                'label' => $c['name'] ?? 'Allowance', 'amount' => $amt,
                'taxable' => !array_key_exists('taxable', $c) || (bool) $c['taxable'],
                'pensionable' => (bool) ($c['pensionable'] ?? false),
            ];
        }
        return $lines;
    }

    /** Loan / advance repayments due this month. */
    public function loanDeductions(int $staffId, PayrollPeriod $period): array
    {
        if (!Schema::hasTable('loans_advances')) return [];
        $rows = DB::table('loans_advances')->where('staff_id', $staffId)->where('status', 'active')->where('balance', '>', 0)
            ->where(fn ($q) => $q->whereNull('first_repayment_date')->orWhere('first_repayment_date', '<=', $period->end_date))
            ->when(Schema::hasColumn('loans_advances', 'paused_until'), fn ($q) => $q->where(fn ($w) => $w->whereNull('paused_until')->orWhere('paused_until', '<', $period->start_date)))
            ->when(Schema::hasColumn('loans_advances', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->get();
        $out = [];
        foreach ($rows as $l) {
            $amt = min((float) $l->monthly_repayment, (float) $l->balance);
            if ($amt <= 0) continue;
            $type = strtolower((string) ($l->type ?? ''));
            $isAdvance = $type === 'advance';
            $out[] = ['code' => $isAdvance ? 'ADVANCE' : 'LOAN', 'label' => ($isAdvance ? 'Salary advance' : ($type === 'cooperative' ? 'Cooperative loan' : 'Loan repayment')) . ($l->reference_no ? " ({$l->reference_no})" : ''),
                      'amount' => $amt, 'meta' => ['loan_id' => $l->id]];
        }
        return $out;
    }

    /** Cooperative savings deduction (phase 6). */
    public function coopDeductions(int $staffId): array
    {
        return class_exists(\App\Services\Loans\CoopService::class) ? app(\App\Services\Loans\CoopService::class)->deductionFor($staffId) : [];
    }

    /** Calculate one staff member for a period (no saving). */
    public function calculate(object $staff, PayrollPeriod $period): array
    {
        $profile = $this->profileFor((int) $staff->id);
        if ($profile->pay_status === 'exited' && $profile->exit_date && Carbon::parse($profile->exit_date)->lt(Carbon::parse($period->start_date))) {
            return ['skip' => 'Left before this month'];
        }

        // Pay comes from the salary scale (grade + step) or from an individual salary structure.
        $scale = app(SalaryScaleService::class);
        $earnLines = null; $structure = null; $placement = null; $source = null;
        if (($profile->salary_source ?? 'structure') === 'grade' && SalaryScaleService::available()) {
            if ($g = $scale->earnings((int) $staff->id, $period->end_date)) { $earnLines = $g['lines']; $placement = $g['placement']; $source = 'grade'; }
        }
        if (!$earnLines && ($structure = $this->structureFor((int) $staff->id, $period))) {
            $earnLines = $this->earnings($structure); $source = 'structure';
        }
        if (!$earnLines) return ['skip' => ($profile->salary_source ?? '') === 'grade' ? 'No grade/step placement or scale amounts' : 'No active salary structure'];

        $rates = StatutoryRates::on($period->end_date);
        $prorate = $this->proration($period, $staff->date_of_employment ?? null, $profile->exit_date?->toDateString());
        if ($prorate <= 0) return ['skip' => 'Not employed in this month'];

        $items = SalaryScaleService::available()
            ? $scale->staffItems((int) $staff->id, $period->start_date, $prorate, $earnLines)
            : ['earnings' => [], 'deductions' => []];

        // Approved unpaid leave in the month reduces pay (by working days).
        if (class_exists(\App\Services\Leave\LeaveService::class) && \App\Services\Leave\LeaveService::available()) {
            $leave = app(\App\Services\Leave\LeaveService::class);
            $unpaid = $leave->unpaidDays((int) $staff->id, $period->start_date, $period->end_date);
            if ($unpaid > 0) {
                $workDays = max(1, $leave->workingDays($period->start_date, $period->end_date));
                $regular = array_sum(array_map(fn ($l) => (float) $l['amount'], $earnLines)) * $prorate;
                $items['earnings'][] = ['code' => 'UNPAID_LEAVE', 'label' => 'Unpaid leave (' . rtrim(rtrim(number_format($unpaid, 1), '0'), '.') . ' day(s))',
                    'amount' => -round(min($regular, $regular / $workDays * $unpaid), 2), 'taxable' => true, 'pensionable' => false, 'no_proration' => true];
            }
        }

        // Attendance deductions and approved duty claims (phase 7).
        $attWarnings = [];
        if (class_exists(AttendancePayService::class)) {
            $regularMonthly = array_sum(array_map(fn ($l) => (float) $l['amount'], $earnLines)) * $prorate;
            $ap = app(AttendancePayService::class)->adjustments($staff, $period, $regularMonthly);
            foreach ($ap['lines'] as $l) $items['earnings'][] = $l;
            $attWarnings = $ap['warnings'];
        }

        $calc = PayrollCalculator::compute(array_merge($earnLines, $items['earnings']), [
            'paye' => $profile->paye_enabled, 'pension' => $profile->pension_enabled, 'nhf' => $profile->nhf_enabled, 'nhia' => $profile->nhia_enabled,
            'annual_rent' => $profile->annual_rent, 'other_reliefs_annual' => $profile->other_reliefs_annual,
        ], $rates, $prorate, array_merge($this->loanDeductions((int) $staff->id, $period), $this->coopDeductions((int) $staff->id), $items['deductions']));

        $warnings = array_merge(array_map(fn ($g) => 'Missing ' . $g, $profile->gaps()), $attWarnings);
        $minWage = (float) ($rates['limits']['minimum_wage_monthly'] ?? 0);
        $regularGross = collect($calc['lines'])->where('type', 'earning')->where('one_off', false)->sum('amount');
        if ($minWage > 0 && $prorate >= 1 && $regularGross < $minWage && $profile->employment_type === 'full_time') {
            $warnings[] = 'Regular pay is below the minimum wage (₦' . number_format($minWage) . ')';
        }
        foreach ($calc['trimmed'] as $t) $warnings[] = "{$t['code']} reduced by ₦" . number_format($t['amount'], 2) . ' to keep the minimum take-home';
        if ($profile->pay_status === 'hold') $warnings[] = 'Pay on hold' . ($profile->hold_reason ? ': ' . $profile->hold_reason : '');

        return ['structure' => $structure, 'placement' => $placement, 'source' => $source, 'profile' => $profile, 'calc' => $calc, 'warnings' => $warnings];
    }

    /** (Re)build every run for a draft/processing period. */
    public function process(PayrollPeriod $period, ?int $userId = null): array
    {
        if (!in_array($period->status, ['draft', 'processing'], true)) {
            throw new \RuntimeException('Only a draft or unapproved payroll can be recalculated.');
        }

        $staffList = DB::table('staffbioinfo as s')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->when(Schema::hasColumn('staffbioinfo', 'status'), fn ($q) => $q->where(fn ($w) => $w->whereNull('s.status')->orWhereIn('s.status', ['active'])))
            ->when(Schema::hasColumn('staffbioinfo', 'deleted_at'), fn ($q) => $q->whereNull('s.deleted_at'))
            ->get(['s.*', 'u.name as user_name']);

        $skipped = [];
        $totals = array_fill_keys(['gross', 'paye', 'employee_pension', 'employer_pension', 'nhf', 'other', 'net', 'employer_cost'], 0.0);
        $loanTotal = 0.0; $count = 0;

        DB::transaction(function () use ($period, $staffList, $userId, &$skipped, &$totals, &$loanTotal, &$count) {
            $old = PayrollRun::where('payroll_period_id', $period->id)->pluck('id');
            PayrollRunLine::whereIn('payroll_run_id', $old)->delete();
            PayrollRun::whereIn('id', $old)->delete();

            foreach ($staffList as $staff) {
                $r = $this->calculate($staff, $period);
                if (isset($r['skip'])) { $skipped[] = ['staff_id' => $staff->id, 'name' => $staff->user_name, 'reason' => $r['skip']]; continue; }

                $c = $r['calc']; $s = $r['structure']; $p = $r['profile'];
                $L = collect($c['lines']);
                $amt = fn ($code) => $L->where('code', $code)->sum('amount');
                $loans = $L->whereIn('code', ['LOAN'])->sum('amount');
                $adv   = $L->whereIn('code', ['ADVANCE'])->sum('amount');
                $fixed = ['BASIC', 'HOUSING', 'TRANSPORT', 'MEAL', 'MEDICAL', 'UTILITY', 'OTHER', 'OVERTIME', 'BONUS'];
                $known = ['PAYE', 'PENSION_EE', 'NHF', 'NHIA_EE', 'LOAN', 'ADVANCE', 'UNION_DUES', 'COOPERATIVE'];

                $run = PayrollRun::create([
                    'payroll_period_id' => $period->id, 'staff_id' => $staff->id, 'salary_structure_id' => $s?->id,
                    'basic_salary' => $amt('BASIC'), 'housing_allowance' => $amt('HOUSING'), 'transport_allowance' => $amt('TRANSPORT'),
                    'meal_allowance' => $amt('MEAL'), 'medical_allowance' => $amt('MEDICAL'), 'utility_allowance' => $amt('UTILITY'),
                    'other_allowances' => $amt('OTHER'),
                    'custom_allowances' => $L->where('type', 'earning')->filter(fn ($l) => !in_array($l['code'], $fixed, true))->map(fn ($l) => ['name' => $l['label'], 'amount' => $l['amount']])->values()->all(),
                    'overtime_pay' => $amt('OVERTIME'), 'bonus' => $amt('BONUS'), 'commission' => 0,
                    'total_earnings' => $c['gross'], 'paye_tax' => $c['paye'], 'employee_pension' => $c['employee_pension'],
                    'employer_pension' => $c['employer_pension'], 'nhf' => $c['nhf'], 'nsitf' => $c['nsitf'],
                    'loan_repayment' => $loans, 'advance_repayment' => $adv,
                    'loan_details' => collect($c['lines'])->whereIn('code', ['LOAN', 'ADVANCE'])->map(fn ($l) => ['loan_id' => $l['meta']['loan_id'] ?? null, 'amount' => $l['amount']])->values()->all(),
                    'union_dues' => $amt('UNION_DUES'), 'cooperative_deductions' => $amt('COOPERATIVE'),
                    'other_deductions' => $L->where('type', 'deduction')->filter(fn ($l) => !in_array($l['code'], $known, true))->map(fn ($l) => ['name' => $l['label'], 'amount' => $l['amount']])->values()->all(),
                    'total_deductions' => $c['total_deductions'], 'net_pay' => $c['net'],
                    'bank_name' => $p->bank_name, 'account_number' => $p->maskedAccount(), 'account_name' => $p->account_name,
                    'payment_status' => 'pending', 'status' => 'draft', 'processed_by' => $userId,
                    'taxable_income' => $c['taxable_gross'], 'annual_chargeable' => $c['tax']['chargeable'], 'rent_relief' => $c['tax']['reliefs']['rent'] ?? 0,
                    'pension_base' => $c['pension_base'], 'nhia' => $c['nhia'], 'employer_nhia' => $c['employer_nhia'], 'itf' => $c['itf'],
                    'employer_cost' => $c['employer_cost'], 'proration' => $c['proration'], 'tax_rule' => $c['tax']['rule'],
                    'tax_breakdown' => $c['tax'], 'tax_state' => $p->tax_state, 'tin' => $p->tin, 'pfa_name' => $p->pfa_name, 'rsa_pin' => $p->rsa_pin,
                    'warnings' => $r['warnings'],
                ]);

                foreach ($c['lines'] as $i => $l) {
                    PayrollRunLine::create([
                        'payroll_run_id' => $run->id, 'payroll_period_id' => $period->id, 'staff_id' => $staff->id,
                        'code' => $l['code'], 'label' => $l['label'], 'type' => $l['type'], 'amount' => $l['amount'],
                        'taxable' => (bool) ($l['taxable'] ?? false), 'pensionable' => (bool) ($l['pensionable'] ?? false), 'sort' => $i, 'meta' => $l['meta'] ?? null,
                    ]);
                }

                $count++;
                $totals['gross'] += $c['gross']; $totals['paye'] += $c['paye']; $totals['employee_pension'] += $c['employee_pension'];
                $totals['employer_pension'] += $c['employer_pension']; $totals['nhf'] += $c['nhf']; $totals['net'] += $c['net'];
                $totals['employer_cost'] += $c['employer_cost']; $loanTotal += $loans + $adv;
                $totals['other'] += $c['other_deductions'] - ($loans + $adv) + $c['nhia'];
            }

            $period->update([
                'total_gross_pay' => round($totals['gross'], 2), 'total_employee_pension' => round($totals['employee_pension'], 2),
                'total_employer_pension' => round($totals['employer_pension'], 2), 'total_tax' => round($totals['paye'], 2),
                'total_nhf' => round($totals['nhf'], 2), 'total_loan_deductions' => round($loanTotal, 2),
                'total_other_deductions' => round($totals['other'], 2), 'total_net_pay' => round($totals['net'], 2),
                'total_employer_cost' => round($totals['employer_cost'], 2), 'staff_count' => $count,
                'status' => 'processing', 'processed_by' => $userId, 'processed_at' => now(),
            ]);
        });

        return ['count' => $count, 'skipped' => $skipped, 'totals' => $totals];
    }

    /** Approve (the preparer can't approve their own payroll unless allowed). */
    public function approve(PayrollPeriod $period, int $userId): void
    {
        if ($period->status !== 'processing') throw new \RuntimeException('Calculate the payroll before approving it.');
        $rates = StatutoryRates::on($period->end_date);
        if (!empty($rates['limits']['require_different_approver']) && (int) $period->processed_by === $userId) {
            throw new \RuntimeException('The person who prepared this payroll cannot approve it.');
        }
        DB::transaction(function () use ($period, $userId) {
            PayrollRun::where('payroll_period_id', $period->id)->update(['status' => 'approved']);
            $period->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
        });
        $this->prepareRemittances($period, $userId);
        $this->postLedger($period->fresh());
    }

    /** Lock: no more changes; verification codes issued; loan balances reduced once. */
    public function lock(PayrollPeriod $period, int $userId): void
    {
        if (!in_array($period->status, ['approved', 'paid'], true)) throw new \RuntimeException('Only an approved payroll can be locked.');
        if ($period->locked_at) return;

        $loanSvc = class_exists(\App\Services\Loans\LoanService::class) && \App\Services\Loans\LoanService::available();
        DB::transaction(function () use ($period, $userId, $loanSvc) {
            foreach (PayrollRun::where('payroll_period_id', $period->id)->get() as $run) {
                if (!$run->verify_code) $run->update(['verify_code' => strtoupper(Str::random(10))]);
                if (!$loanSvc && Schema::hasTable('loans_advances')) {
                    foreach ((array) $run->loan_details as $ld) {
                        if (empty($ld['loan_id']) || empty($ld['amount'])) continue;
                        DB::table('loans_advances')->where('id', $ld['loan_id'])->update([
                            'balance' => DB::raw('GREATEST(0, balance - ' . (float) $ld['amount'] . ')'), 'updated_at' => now(),
                        ]);
                        DB::table('loans_advances')->where('id', $ld['loan_id'])->where('balance', '<=', 0)->update(['status' => 'completed']);
                    }
                }
            }
            $period->update(['status' => 'locked', 'locked_by' => $userId, 'locked_at' => now()]);
        });
        $this->prepareRemittances($period->fresh(), $userId);
        $this->afterLock($period->fresh(), $userId);
    }

    /** Loans, cooperative savings, duty claims and the ledger follow a locked month. Never blocks locking. */
    protected function afterLock(PayrollPeriod $period, int $userId): void
    {
        $steps = [
            fn () => class_exists(\App\Services\Loans\LoanService::class) && \App\Services\Loans\LoanService::available() ? app(\App\Services\Loans\LoanService::class)->payrollDeducted($period, $userId) : null,
            fn () => class_exists(\App\Services\Loans\CoopService::class) && \App\Services\Loans\CoopService::available() ? app(\App\Services\Loans\CoopService::class)->payrollContributions($period, $userId) : null,
            fn () => class_exists(AttendancePayService::class) ? app(AttendancePayService::class)->markClaimsPaid($period) : null,
            fn () => $this->postLedger($period),
        ];
        foreach ($steps as $i => $step) {
            try { $step(); } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Payroll after-lock step failed', ['period' => $period->id, 'step' => $i, 'error' => $e->getMessage()]);
            }
        }
    }

    /** Salary cost and liabilities into the general ledger (phase 9). Idempotent. */
    protected function postLedger(PayrollPeriod $period): void
    {
        if (!class_exists(\App\Services\Accounting\LedgerPoster::class)) return;
        try {
            app(\App\Services\Accounting\LedgerPoster::class)->payrollAccrual($period);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Payroll not posted to ledger', ['period' => $period->id, 'error' => $e->getMessage()]);
        }
    }

    /** PAYE / pension / NHF… owed for the month (phase 4). Never blocks approval. */
    protected function prepareRemittances(PayrollPeriod $period, int $userId): void
    {
        if (!Schema::hasTable('statutory_remittances')) return;
        try {
            app(StatutoryRemittanceService::class)->generate($period->fresh(), $userId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Remittances not prepared', ['period' => $period->id, 'error' => $e->getMessage()]);
        }
    }
}
