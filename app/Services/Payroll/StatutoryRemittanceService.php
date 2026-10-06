<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\StatutoryRemittance;
use App\Support\PayrollDocs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds what is owed to each government body for a payroll month and tracks
 * payments. PAYE is grouped by the staff member's state, pension by PFA.
 */
class StatutoryRemittanceService
{
    /** Due date rules (editable on Employer Details). */
    public function dueDate(string $type, PayrollPeriod $period): Carbon
    {
        $e = PayrollDocs::employer();
        $pay = Carbon::parse($period->payment_date ?: $period->end_date);
        if ($type === 'pension') {
            $days = (int) ($e['due_pension_working_days'] ?? 7);
            $d = $pay->copy();
            while ($days > 0) { $d->addDay(); if (!$d->isWeekend()) $days--; }
            return $d;
        }
        $day = (int) ($e['due_' . $type . '_day'] ?? $e['due_other_day'] ?? 10);
        $next = Carbon::parse($period->end_date)->addMonthNoOverflow()->startOfMonth();
        return $next->copy()->day(min($day, $next->daysInMonth));
    }

    /** Create/refresh the remittances for a month (paid ones are never changed, only flagged). */
    public function generate(PayrollPeriod $period, ?int $userId = null): array
    {
        if (!in_array($period->status, PayrollDocs::PUBLISHED, true)) throw new \RuntimeException('Approve the payroll month first.');

        $runs = DB::table('payroll_runs as r')->join('staffbioinfo as s', 's.id', '=', 'r.staff_id')->leftJoin('users as u', 'u.id', '=', 's.userid')
            ->leftJoin('staff_pay_profiles as p', 'p.staff_id', '=', 'r.staff_id')
            ->where('r.payroll_period_id', $period->id)
            ->get(['r.*', 'u.name', 's.employmentid', 'p.nhf_number']);

        $groups = [];
        $add = function (string $type, string $authority, object $r, float $ee, float $er, array $meta) use (&$groups) {
            if ($ee + $er <= 0) return;
            $groups[$type][$authority][] = ['run' => $r, 'ee' => round($ee, 2), 'er' => round($er, 2), 'meta' => $meta];
        };
        foreach ($runs as $r) {
            $base = ['name' => $r->name, 'staff_no' => $r->employmentid];
            $add('paye', $r->tax_state ?: 'State not set', $r, (float) $r->paye_tax, 0, $base + ['tin' => $r->tin, 'gross' => (float) $r->total_earnings, 'taxable' => (float) $r->taxable_income]);
            $add('pension', $r->pfa_name ?: 'PFA not set', $r, (float) $r->employee_pension, (float) $r->employer_pension, $base + ['rsa_pin' => $r->rsa_pin, 'base' => (float) ($r->pension_base ?? 0)]);
            $add('nhf', 'Federal Mortgage Bank (NHF)', $r, (float) $r->nhf, 0, $base + ['nhf_number' => $r->nhf_number, 'basic' => (float) $r->basic_salary]);
            $add('nhia', 'NHIA / HMO', $r, (float) ($r->nhia ?? 0), (float) ($r->employer_nhia ?? 0), $base);
            $add('nsitf', 'NSITF', $r, 0, (float) $r->nsitf, $base + ['gross' => (float) $r->total_earnings]);
            $add('itf', 'Industrial Training Fund', $r, 0, (float) ($r->itf ?? 0), $base + ['gross' => (float) $r->total_earnings]);
        }

        $made = 0; $flagged = 0;
        DB::transaction(function () use ($groups, $period, $userId, &$made, &$flagged) {
            $keep = [];
            foreach ($groups as $type => $byAuth) {
                foreach ($byAuth as $authority => $items) {
                    $ee = round(array_sum(array_column($items, 'ee')), 2); $er = round(array_sum(array_column($items, 'er')), 2);
                    $rem = StatutoryRemittance::firstOrNew(['type' => $type, 'payroll_period_id' => $period->id, 'authority' => $authority]);
                    if ($rem->exists && $rem->status !== 'pending') {
                        if (abs($rem->amount_due - ($ee + $er)) > 0.009) { $rem->update(['needs_review' => true]); $flagged++; }
                        $keep[] = $rem->id;
                        continue;
                    }
                    $rem->fill(['staff_count' => count($items), 'employee_amount' => $ee, 'employer_amount' => $er, 'amount_due' => round($ee + $er, 2),
                                'due_date' => $this->dueDate($type, $period)->toDateString(), 'recorded_by' => $rem->recorded_by ?? $userId]);
                    $rem->save();
                    $keep[] = $rem->id;
                    DB::table('statutory_remittance_lines')->where('remittance_id', $rem->id)->delete();
                    $rows = array_map(fn ($i) => ['remittance_id' => $rem->id, 'payroll_run_id' => $i['run']->id, 'staff_id' => $i['run']->staff_id,
                        'employee_amount' => $i['ee'], 'employer_amount' => $i['er'], 'amount' => round($i['ee'] + $i['er'], 2), 'meta' => json_encode($i['meta']),
                        'created_at' => now(), 'updated_at' => now()], $items);
                    foreach (array_chunk($rows, 200) as $chunk) DB::table('statutory_remittance_lines')->insert($chunk);
                    $made++;
                }
            }
            // Pending remittances that no longer apply (e.g. a staff member's state changed) are removed.
            $stale = StatutoryRemittance::where('payroll_period_id', $period->id)->where('status', 'pending')->whereNotIn('id', $keep ?: [0])->pluck('id');
            DB::table('statutory_remittance_lines')->whereIn('remittance_id', $stale)->delete();
            StatutoryRemittance::whereIn('id', $stale)->delete();
        });

        return ['created' => $made, 'flagged' => $flagged];
    }

    public function recordPayment(StatutoryRemittance $rem, array $d, int $userId, bool $notifyStaff = true): void
    {
        $paid = round($rem->amount_paid + (float) $d['amount'], 2);
        $rem->update([
            'amount_paid' => $paid, 'status' => $paid + 0.009 >= $rem->amount_due ? 'paid' : 'partial',
            'paid_at' => $d['paid_at'], 'reference' => trim(($rem->reference ? $rem->reference . '; ' : '') . ($d['reference'] ?? ''), '; '),
            'payment_method' => $d['payment_method'] ?? $rem->payment_method, 'evidence' => $d['evidence'] ?? $rem->evidence,
            'notes' => $d['notes'] ?? $rem->notes, 'needs_review' => false, 'recorded_by' => $userId,
        ]);

        // General ledger: clear the liability against the bank.
        if (class_exists(\App\Services\Accounting\LedgerPoster::class)) {
            try {
                app(\App\Services\Accounting\LedgerPoster::class)->remittancePaid($rem, (float) $d['amount'], $d['paid_at'], $d['reference'] ?? null, $rem->id . ':' . $paid);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Remittance not posted to ledger', ['remittance' => $rem->id, 'error' => $e->getMessage()]);
            }
        }

        if ($notifyStaff && $rem->status === 'paid' && in_array($rem->type, ['pension', 'nhf'], true) && class_exists(\App\Services\Messaging\PortalNotifier::class)) {
            $period = $rem->period;
            $userIds = DB::table('statutory_remittance_lines as l')->join('staffbioinfo as s', 's.id', '=', 'l.staff_id')
                ->where('l.remittance_id', $rem->id)->pluck('s.userid')->map(fn ($v) => (int) $v)->filter()->all();
            $what = $rem->type === 'pension' ? 'pension contribution was paid to ' . $rem->authority : 'NHF contribution was paid';
            \App\Services\Messaging\PortalNotifier::toUsers($userIds, ucfirst($rem->type === 'pension' ? 'Pension' : 'NHF') . ' paid — ' . ($period->period_name ?? ''),
                "Your {$period->period_name} {$what} on " . Carbon::parse($d['paid_at'])->format('d M Y') . '.', route('my-pay.pension'), 'payment', 'remit:' . $rem->id);
        }
    }

    /** CSV rows in the layout each body usually asks for. */
    public function schedule(StatutoryRemittance $rem): array
    {
        $lines = DB::table('statutory_remittance_lines')->where('remittance_id', $rem->id)->get()
            ->map(fn ($l) => (object) (array_merge((array) $l, ['m' => json_decode($l->meta ?? '[]', true) ?: []])))
            ->sortBy(fn ($l) => $l->m['name'] ?? '')->values();
        $e = PayrollDocs::employer();
        $split = fn ($name) => [trim((string) strstr((string) $name . ' ', ' ', true)), trim((string) strstr((string) $name, ' '))];

        return match ($rem->type) {
            'paye' => [['S/N', 'Staff ID', 'Name', 'TIN', 'Gross pay', 'Taxable pay', 'PAYE'],
                $lines->values()->map(fn ($l, $i) => [$i + 1, $l->m['staff_no'] ?? '', $l->m['name'] ?? '', $l->m['tin'] ?? '', $l->m['gross'] ?? '', $l->m['taxable'] ?? '', $l->amount])->all()],
            'pension' => [['S/N', 'RSA PIN', 'Surname', 'Other names', 'Staff ID', 'Employer code', 'Pensionable pay', 'Employee (8%)', 'Employer (10%)', 'Total'],
                $lines->values()->map(function ($l, $i) use ($split, $e) { [$first, $rest] = $split($l->m['name'] ?? '');
                    return [$i + 1, $l->m['rsa_pin'] ?? '', $rest ?: $first, $rest ? $first : '', $l->m['staff_no'] ?? '', $e['pension_employer_code'] ?? '', $l->m['base'] ?? '', $l->employee_amount, $l->employer_amount, $l->amount]; })->all()],
            'nhf' => [['S/N', 'NHF number', 'Name', 'Staff ID', 'Employer code', 'Basic salary', 'Contribution (2.5%)'],
                $lines->values()->map(fn ($l, $i) => [$i + 1, $l->m['nhf_number'] ?? '', $l->m['name'] ?? '', $l->m['staff_no'] ?? '', $e['nhf_employer_code'] ?? '', $l->m['basic'] ?? '', $l->amount])->all()],
            default => [['S/N', 'Staff ID', 'Name', 'Employee', 'Employer', 'Total'],
                $lines->values()->map(fn ($l, $i) => [$i + 1, $l->m['staff_no'] ?? '', $l->m['name'] ?? '', $l->employee_amount, $l->employer_amount, $l->amount])->all()],
        };
    }

    /** Pending items due within $days or already late. */
    public function dueSoon(int $days = 3)
    {
        return StatutoryRemittance::with('period:id,period_name')->where('status', '!=', 'paid')
            ->whereDate('due_date', '<=', now()->addDays($days)->toDateString())->orderBy('due_date')->get();
    }
}
