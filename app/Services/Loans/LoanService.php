<?php

namespace App\Services\Loans;

use App\Models\LoanAdvance;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Support\FinanceSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Staff loans, salary advances and cooperative loans:
 * eligibility → application → approval → disbursement → payroll repayments.
 */
class LoanService
{
    public static function available(): bool
    {
        return Schema::hasTable('loans_advances') && Schema::hasTable('loan_repayments') && Schema::hasColumn('loans_advances', 'total_repayable');
    }

    public function rules(): array
    {
        return FinanceSettings::get('loans');
    }

    /** Latest monthly gross and net for a staff member (from their last payroll). */
    public function payFor(int $staffId): array
    {
        $run = PayrollRun::where('staff_id', $staffId)->whereIn('status', ['approved', 'paid'])->latest('id')->first()
            ?? PayrollRun::where('staff_id', $staffId)->latest('id')->first();
        return ['gross' => (float) ($run->total_earnings ?? 0), 'net' => (float) ($run->net_pay ?? 0), 'has_history' => (bool) $run];
    }

    public function coopSavings(int $staffId): float
    {
        return Schema::hasTable('coop_transactions') ? (float) DB::table('coop_transactions')->where('staff_id', $staffId)->sum('amount') : 0.0;
    }

    /** Interest, total and monthly figures for a loan. */
    public function quote(float $amount, int $months, float $rate): array
    {
        $months = max(1, $months);
        $interest = round($amount * $rate / 100 * $months / 12, 2);
        $total = round($amount + $interest, 2);
        $monthly = ceil($total / $months * 100) / 100;
        return ['amount' => round($amount, 2), 'months' => $months, 'rate' => $rate, 'interest' => $interest, 'total' => $total, 'monthly' => round($monthly, 2)];
    }

    /** Check an application against the rules. Returns quote + list of problems. */
    public function eligibility(int $staffId, string $type, float $amount, int $months, ?int $ignoreLoanId = null): array
    {
        $R = $this->rules();
        $pay = $this->payFor($staffId);
        $rate = $type === 'advance' ? 0.0 : (float) $R['default_interest_rate'];
        $q = $this->quote($amount, $months, $rate);
        $issues = [];

        if (!$R['enabled']) $issues[] = 'Staff loans are switched off.';
        if ($amount <= 0) $issues[] = 'Enter an amount.';
        if (!$pay['has_history']) $issues[] = 'No payroll history yet, so affordability cannot be checked.';

        $open = LoanAdvance::where('staff_id', $staffId)->open()->when($ignoreLoanId, fn ($x) => $x->where('id', '!=', $ignoreLoanId))->get();
        if ($R['one_active_per_type'] && $open->where('type', $type)->count()) $issues[] = 'You already have an open ' . strtolower(LoanAdvance::TYPES[$type] ?? $type) . '.';

        if ($type === 'advance') {
            if ($months > (int) $R['max_advance_months']) $issues[] = "Advances are repaid within {$R['max_advance_months']} month(s).";
            $max = round($pay['net'] * (float) $R['max_advance_percent'] / 100, 2);
            if ($pay['has_history'] && $amount > $max) $issues[] = 'Maximum advance is ₦' . number_format($max, 2) . " ({$R['max_advance_percent']}% of net pay).";
        } elseif ($type === 'cooperative') {
            $max = round($this->coopSavings($staffId) * (float) $R['coop_loan_multiple'], 2);
            if ($amount > $max) $issues[] = 'Maximum cooperative loan is ₦' . number_format($max, 2) . " ({$R['coop_loan_multiple']}× your savings).";
            if ($months > (int) $R['max_loan_months']) $issues[] = "Maximum repayment period is {$R['max_loan_months']} months.";
        } else {
            $max = round($pay['gross'] * (float) $R['max_loan_multiple'], 2);
            if ($pay['has_history'] && $amount > $max) $issues[] = 'Maximum loan is ₦' . number_format($max, 2) . " ({$R['max_loan_multiple']}× monthly gross).";
            if ($months > (int) $R['max_loan_months']) $issues[] = "Maximum repayment period is {$R['max_loan_months']} months.";
        }

        $current = (float) $open->whereIn('status', ['active', 'approved', 'disbursing'])->sum('monthly_repayment');
        $cap = round($pay['gross'] * (float) $R['max_deduction_percent'] / 100, 2);
        if ($pay['has_history'] && $current + $q['monthly'] > $cap) {
            $issues[] = 'Monthly repayments would be ₦' . number_format($current + $q['monthly'], 2) . ", above the {$R['max_deduction_percent']}% limit (₦" . number_format($cap, 2) . ').';
        }

        $start = DB::table('staffbioinfo')->where('id', $staffId)->value(Schema::hasColumn('staffbioinfo', 'date_of_employment') ? 'date_of_employment' : 'created_at');
        if ($start && (int) $R['min_service_months'] > 0 && Carbon::parse($start)->diffInMonths(now()) < (int) $R['min_service_months']) {
            $issues[] = "Staff need at least {$R['min_service_months']} months of service.";
        }

        $needsGuarantor = $type === 'loan' && (float) $R['guarantor_above'] > 0 && $amount > (float) $R['guarantor_above'];
        return $q + ['issues' => $issues, 'ok' => !$issues, 'pay' => $pay, 'current_repayments' => $current, 'deduction_cap' => $cap, 'needs_guarantor' => $needsGuarantor];
    }

    public function apply(int $staffId, array $d, int $userId, bool $override = false): LoanAdvance
    {
        $type = $d['type'];
        $e = $this->eligibility($staffId, $type, (float) $d['amount'], (int) $d['months']);
        if (!$e['ok'] && !$override) throw new \RuntimeException(implode(' ', $e['issues']));
        if ($e['needs_guarantor'] && empty($d['guarantor_staff_id'])) throw new \RuntimeException('A guarantor is required for this amount.');

        $loan = LoanAdvance::create([
            'staff_id' => $staffId, 'type' => $type, 'reference_no' => $this->reference($type),
            'amount' => $e['amount'], 'interest_rate' => $e['rate'], 'repayment_months' => $e['months'],
            'monthly_repayment' => $e['monthly'], 'total_repayable' => $e['total'], 'balance' => $e['total'],
            'purpose' => $d['purpose'] ?? null, 'attachment' => $d['attachment'] ?? null, 'status' => 'pending',
            'guarantor_staff_id' => $d['guarantor_staff_id'] ?? null, 'guarantor_status' => !empty($d['guarantor_staff_id']) ? 'pending' : null,
            'created_by' => $userId, 'notes' => $override && !$e['ok'] ? 'Entered by bursary despite: ' . implode(' ', $e['issues']) : null,
        ]);
        $this->notifyApprovers($loan);
        if ($loan->guarantor_staff_id) $this->notifyStaff((int) $loan->guarantor_staff_id, 'Loan guarantee request',
            'You have been named as guarantor for a ' . strtolower($loan->typeLabel()) . ' of ₦' . number_format($loan->amount, 2) . '. Please respond in My Pay › Loans.');
        return $loan;
    }

    protected function reference(string $type): string
    {
        $p = ['loan' => 'LN', 'advance' => 'ADV', 'cooperative' => 'CPL'][$type] ?? 'LN';
        do { $ref = $p . '-' . now()->format('ym') . '-' . strtoupper(Str::random(4)); } while (LoanAdvance::withTrashed()->where('reference_no', $ref)->exists());
        return $ref;
    }

    public function guarantorRespond(LoanAdvance $loan, bool $accept): void
    {
        $loan->update(['guarantor_status' => $accept ? 'accepted' : 'declined']);
        $this->notifyStaff((int) $loan->staff_id, 'Guarantor ' . ($accept ? 'accepted' : 'declined'),
            'Your guarantor has ' . ($accept ? 'accepted' : 'declined') . ' your ' . strtolower($loan->typeLabel()) . ' ' . $loan->reference_no . '.');
    }

    /** Approve (optionally changing amount / months / rate / start month). */
    public function approve(LoanAdvance $loan, int $userId, array $changes = []): void
    {
        if ($loan->status !== 'pending') throw new \RuntimeException('Only a pending application can be approved.');
        if ((int) $loan->created_by === $userId && (int) DB::table('staffbioinfo')->where('id', $loan->staff_id)->value('userid') === $userId) {
            throw new \RuntimeException('You cannot approve your own loan.');
        }
        if ($loan->guarantor_staff_id && $loan->guarantor_status !== 'accepted') throw new \RuntimeException('The guarantor has not accepted yet.');

        $q = $this->quote((float) ($changes['amount'] ?? $loan->amount), (int) ($changes['months'] ?? $loan->repayment_months), (float) ($changes['rate'] ?? $loan->interest_rate));
        $loan->update([
            'amount' => $q['amount'], 'repayment_months' => $q['months'], 'interest_rate' => $q['rate'],
            'monthly_repayment' => $q['monthly'], 'total_repayable' => $q['total'], 'balance' => $q['total'],
            'first_repayment_date' => !empty($changes['first_month']) ? Carbon::parse($changes['first_month'] . '-01')->toDateString() : null,
            'status' => 'approved', 'approved_by' => $userId, 'approved_at' => now(), 'approval_date' => now()->toDateString(),
        ]);
        $this->notifyStaff((int) $loan->staff_id, ucfirst($loan->typeLabel()) . ' approved',
            'Your ' . strtolower($loan->typeLabel()) . ' of ₦' . number_format($q['amount'], 2) . " was approved. Repayment: ₦" . number_format($q['monthly'], 2) . " × {$q['months']} month(s).");
    }

    public function reject(LoanAdvance $loan, int $userId, string $reason): void
    {
        if (!in_array($loan->status, ['pending', 'approved'], true)) throw new \RuntimeException('This loan can no longer be declined.');
        $loan->update(['status' => 'rejected', 'rejection_reason' => $reason, 'approved_by' => $userId, 'approved_at' => now()]);
        $this->notifyStaff((int) $loan->staff_id, ucfirst($loan->typeLabel()) . ' declined', 'Your application ' . $loan->reference_no . ' was declined: ' . $reason);
    }

    public function cancel(LoanAdvance $loan): void
    {
        if (!in_array($loan->status, ['pending', 'approved'], true)) throw new \RuntimeException('Only a loan that has not been paid out can be cancelled.');
        $loan->update(['status' => 'cancelled']);
    }

    /** Pay the money out: Paystack (a payout that another person releases), or already paid by cash / bank. */
    public function disburse(LoanAdvance $loan, string $method, int $userId, ?string $reference = null): ?\App\Models\PayoutBatch
    {
        if ($loan->status !== 'approved') throw new \RuntimeException('Approve the loan before paying it out.');
        if ($method === 'paystack') {
            $batch = app(\App\Services\Payroll\PayoutService::class)->prepareSingle('loan', $loan->id, (int) $loan->staff_id, (float) $loan->amount, $userId,
                $loan->typeLabel() . ' ' . $loan->reference_no);
            $loan->update(['status' => 'disbursing', 'disbursement_method' => 'paystack', 'disbursement_reference' => $batch->reference]);
            return $batch;
        }
        $loan->update(['disbursement_method' => $method]);
        $this->disbursed($loan->id, $reference ?: strtoupper($method) . '-' . now()->format('ymdHi'));
        return null;
    }

    /** Money has reached the staff member: start repayments. */
    public function disbursed(int $loanId, ?string $reference = null): void
    {
        $loan = LoanAdvance::find($loanId);
        if (!$loan || $loan->status === 'active') return;
        DB::transaction(function () use ($loan, $reference) {
            $first = $loan->first_repayment_date ?: now()->addMonthNoOverflow()->startOfMonth();
            $loan->update(['status' => 'active', 'disbursed_at' => now(), 'disbursement_reference' => $reference ?: $loan->disbursement_reference,
                           'first_repayment_date' => Carbon::parse($first)->startOfMonth()->toDateString()]);
            $this->buildSchedule($loan->fresh());
        });
        if (class_exists(\App\Services\Accounting\LedgerPoster::class)) {
            try { app(\App\Services\Accounting\LedgerPoster::class)->loanDisbursed($loan->fresh()); } catch (\Throwable $e) {}
        }
        $this->notifyStaff((int) $loan->staff_id, ucfirst($loan->typeLabel()) . ' paid out',
            '₦' . number_format($loan->amount, 2) . ' has been paid to you. Deductions start ' . Carbon::parse($loan->fresh()->first_repayment_date)->format('F Y') . '.');
    }

    public function disbursementFailed(int $loanId, string $reason): void
    {
        $loan = LoanAdvance::where('id', $loanId)->where('status', 'disbursing')->first();
        if ($loan) $loan->update(['status' => 'approved', 'notes' => trim(($loan->notes ?? '') . ' Payout failed: ' . mb_substr($reason, 0, 120))]);
    }

    public function buildSchedule(LoanAdvance $loan): void
    {
        LoanRepaymentSchedule::where('loan_id', $loan->id)->delete();
        $months = max(1, (int) $loan->repayment_months);
        $total = (float) $loan->total_repayable ?: (float) $loan->amount;
        $interestTotal = round($total - (float) $loan->amount, 2);
        $due = Carbon::parse($loan->first_repayment_date ?: now()->addMonthNoOverflow()->startOfMonth())->endOfMonth();
        $left = $total; $intLeft = $interestTotal;
        for ($i = 1; $i <= $months; $i++) {
            $amt = $i === $months ? round($left, 2) : min(round((float) $loan->monthly_repayment, 2), round($left, 2));
            $int = $i === $months ? round($intLeft, 2) : round($interestTotal / $months, 2);
            LoanRepaymentSchedule::create([
                'loan_id' => $loan->id, 'installment_no' => $i, 'due_date' => $due->toDateString(),
                'amount' => $amt, 'interest' => $int, 'principal' => round($amt - $int, 2), 'status' => 'due',
            ]);
            $left -= $amt; $intLeft -= $int;
            $due = $due->copy()->addMonthNoOverflow()->endOfMonth();
            if ($left <= 0.004) break;
        }
    }

    /** Record money received against a loan; allocates to instalments in order. */
    public function recordRepayment(LoanAdvance $loan, float $amount, string $source, ?int $userId = null, ?int $periodId = null, ?string $reference = null, ?string $date = null, ?string $note = null): ?LoanRepayment
    {
        $amount = round(min($amount, (float) $loan->balance), 2);
        if ($amount <= 0) return null;
        if ($source === 'payroll' && $periodId && LoanRepayment::where('loan_id', $loan->id)->where('payroll_period_id', $periodId)->exists()) return null;

        $rep = DB::transaction(function () use ($loan, $amount, $source, $userId, $periodId, $reference, $date, $note) {
            $rep = LoanRepayment::create([
                'loan_id' => $loan->id, 'amount' => $amount, 'source' => $source, 'payroll_period_id' => $periodId,
                'reference' => $reference, 'paid_on' => $date ?: now()->toDateString(), 'note' => $note, 'recorded_by' => $userId,
            ]);
            $left = $amount;
            foreach (LoanRepaymentSchedule::where('loan_id', $loan->id)->whereIn('status', ['due', 'partial'])->orderBy('installment_no')->get() as $inst) {
                if ($left <= 0) break;
                $need = round($inst->amount - $inst->paid_amount, 2);
                $take = min($need, $left);
                $paid = round($inst->paid_amount + $take, 2);
                $inst->update(['paid_amount' => $paid, 'status' => $paid >= $inst->amount - 0.004 ? ($source === 'waiver' ? 'waived' : 'paid') : 'partial',
                               'paid_date' => $rep->paid_on, 'transaction_reference' => $reference]);
                $left = round($left - $take, 2);
            }
            $balance = round(max(0, (float) $loan->balance - $amount), 2);
            $loan->update(['balance' => $balance, 'status' => $balance <= 0.004 ? ($source === 'waiver' ? 'written_off' : 'completed') : $loan->status]);
            return $rep;
        });

        if (in_array($source, ['cash', 'transfer', 'waiver'], true) && class_exists(\App\Services\Accounting\LedgerPoster::class)) {
            try { app(\App\Services\Accounting\LedgerPoster::class)->loanRepaidDirect($rep); } catch (\Throwable $e) {}
        }
        if ($loan->fresh()->status === 'completed') {
            $this->notifyStaff((int) $loan->staff_id, ucfirst($loan->typeLabel()) . ' fully repaid', 'Congratulations — ' . $loan->reference_no . ' is fully repaid.');
        }
        return $rep;
    }

    /** Called when a payroll month is locked: record each salary deduction once. */
    public function payrollDeducted(PayrollPeriod $period, ?int $userId = null): int
    {
        $n = 0;
        foreach (PayrollRun::where('payroll_period_id', $period->id)->get() as $run) {
            foreach ((array) $run->loan_details as $ld) {
                if (empty($ld['loan_id']) || empty($ld['amount'])) continue;
                $loan = LoanAdvance::find($ld['loan_id']);
                if ($loan && $this->recordRepayment($loan, (float) $ld['amount'], 'payroll', $userId, $period->id, $period->period_name, Carbon::parse($period->end_date)->toDateString())) $n++;
            }
        }
        return $n;
    }

    public function pause(LoanAdvance $loan, ?string $untilMonth, int $userId): void
    {
        $until = $untilMonth ? Carbon::parse($untilMonth . '-01')->endOfMonth()->toDateString() : null;
        $loan->update(['paused_until' => $until, 'notes' => trim(($loan->notes ?? '') . ' ' . ($until ? "Paused to {$until}" : 'Resumed') . ' by user #' . $userId . ' on ' . now()->toDateString())]);
        if ($until) {
            // Push unpaid instalments back by the number of paused months.
            $first = LoanRepaymentSchedule::where('loan_id', $loan->id)->whereIn('status', ['due', 'partial'])->orderBy('installment_no')->first();
            if ($first && $first->due_date->lte(Carbon::parse($until))) {
                $shift = (int) ceil($first->due_date->floatDiffInMonths(Carbon::parse($until))) + 1;
                foreach (LoanRepaymentSchedule::where('loan_id', $loan->id)->whereIn('status', ['due', 'partial'])->get() as $i) {
                    $i->update(['due_date' => $i->due_date->copy()->addMonthsNoOverflow($shift)->endOfMonth()->toDateString()]);
                }
            }
        }
    }

    public function writeOff(LoanAdvance $loan, int $userId, string $reason): void
    {
        if ($loan->status !== 'active') throw new \RuntimeException('Only an active loan can be written off.');
        $this->recordRepayment($loan, (float) $loan->balance, 'waiver', $userId, null, null, null, $reason);
    }

    // ── notifications ───────────────────────────────────────────────
    protected function notifyStaff(int $staffId, string $title, string $body): void
    {
        $uid = DB::table('staffbioinfo')->where('id', $staffId)->value('userid');
        if (!$uid || !class_exists(\App\Services\Messaging\PortalNotifier::class)) return;
        try { \App\Services\Messaging\PortalNotifier::toUsers([(int) $uid], $title, $body, route('my-pay.loans'), 'system'); } catch (\Throwable $e) {}
    }

    protected function notifyApprovers(LoanAdvance $loan): void
    {
        if (!class_exists(\App\Services\Messaging\PortalNotifier::class)) return;
        try {
            $ids = \App\Models\User::permission('Approve staff loans')->pluck('id')->all();
            if ($ids) \App\Services\Messaging\PortalNotifier::toUsers($ids, 'New ' . strtolower($loan->typeLabel()) . ' request',
                'A ' . strtolower($loan->typeLabel()) . ' of ₦' . number_format($loan->amount, 2) . ' is waiting for approval.', route('payroll.loans.show', $loan), 'system');
        } catch (\Throwable $e) {}
    }
}
