<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PayrollPeriod;
use App\Support\FinanceSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Turns what happens elsewhere in the portal into double-entry journals:
 * payroll, salary payouts, remittances, loans, cooperative, expenses,
 * depreciation, asset disposals and school-fee receipts.
 * Every posting is keyed (ledger_postings) so it can never happen twice.
 */
class LedgerPoster
{
    protected array $ids = [];

    public static function available(): bool
    {
        return Schema::hasTable('ledger_postings') && Schema::hasTable('journal_entries') && Schema::hasTable('chart_of_accounts')
            && DB::table('chart_of_accounts')->exists();
    }

    public function account(string $code): ?int
    {
        return $this->ids[$code] ??= ChartOfAccount::where('account_code', $code)->value('id');
    }

    public function isPosted(string $source, string $key): bool
    {
        return DB::table('ledger_postings')->where('source', $source)->where('source_key', $key)->exists();
    }

    /** First date that is still open for posting. */
    public function openDate($date): string
    {
        $d = Carbon::parse($date)->toDateString();
        $closed = FinanceSettings::get('accounting')['books_closed_until'] ?? null;
        return $closed && $d <= $closed ? Carbon::parse($closed)->addDay()->toDateString() : $d;
    }

    /**
     * Post a balanced journal. $lines: [[code, debit, credit, narration?, staff_id?]].
     * Returns the entry, or null if already posted / nothing to post.
     */
    public function post(string $source, string $key, $date, string $type, string $description, array $lines, ?object $ref = null): ?JournalEntry
    {
        if (!self::available() || $this->isPosted($source, $key)) return null;

        $rows = [];
        foreach ($lines as $l) {
            [$code, $dr, $cr] = [$l[0], round((float) $l[1], 2), round((float) $l[2], 2)];
            if ($dr == 0 && $cr == 0) continue;
            if ($dr < 0) { $cr += -$dr; $dr = 0; }
            if ($cr < 0) { $dr += -$cr; $cr = 0; }
            $id = $this->account($code);
            if (!$id) throw new \RuntimeException("Account {$code} is missing from the chart of accounts. Run the database seeder.");
            $rows[] = ['account_id' => $id, 'debit' => $dr, 'credit' => $cr, 'narration' => $l[3] ?? null, 'staff_id' => $l[4] ?? null];
        }
        if (!$rows) return null;
        $diff = round(array_sum(array_column($rows, 'debit')) - array_sum(array_column($rows, 'credit')), 2);
        if (abs($diff) > 0.02) throw new \RuntimeException("Journal for {$source} {$key} does not balance ({$diff}).");
        if ($diff != 0) $rows[count($rows) - 1][$diff > 0 ? 'credit' : 'debit'] += abs($diff); // rounding

        $postDate = $this->openDate($date);
        return DB::transaction(function () use ($source, $key, $postDate, $date, $type, $description, $rows, $ref) {
            $svc = app(AccountingService::class);
            $je = $svc->createJournalEntry([
                'entry_date' => $postDate, 'entry_type' => $type,
                'description' => $description . ($postDate !== Carbon::parse($date)->toDateString() ? ' (dated ' . Carbon::parse($date)->format('d M Y') . '; books closed)' : ''),
                'reference_id' => $ref?->id ?? null, 'reference_type' => $ref ? get_class($ref) : null,
            ], $rows);
            $svc->postEntry($je->id);
            DB::table('ledger_postings')->insert(['source' => $source, 'source_key' => $key, 'journal_entry_id' => $je->id, 'created_at' => now(), 'updated_at' => now()]);
            return $je->fresh();
        });
    }

    protected function bank(): string { return FinanceSettings::get('accounting')['bank_account'] ?: '1020'; }
    protected function cash(): string { return FinanceSettings::get('accounting')['cash_account'] ?: '1010'; }

    // ── Payroll ─────────────────────────────────────────────────────
    public function payrollAccrual(PayrollPeriod $period): ?JournalEntry
    {
        if (!in_array($period->status, ['approved', 'paid', 'locked'], true)) return null;
        $col = fn ($c) => Schema::hasColumn('payroll_runs', $c) ? (float) DB::table('payroll_runs')->where('payroll_period_id', $period->id)->sum($c) : 0.0;
        $gross = $col('total_earnings'); if ($gross <= 0) return null;
        $net = $col('net_pay'); $paye = $col('paye_tax'); $eePen = $col('employee_pension'); $erPen = $col('employer_pension');
        $nhf = $col('nhf'); $nhia = $col('nhia'); $erNhia = $col('employer_nhia'); $nsitf = $col('nsitf'); $itf = $col('itf');
        $loans = $col('loan_repayment') + $col('advance_repayment'); $coop = $col('cooperative_deductions');
        $employerCost = $col('employer_cost');
        $erStat = $employerCost > $gross ? $employerCost - $gross : $erPen + $erNhia + $nsitf + $itf;

        $lines = [
            ['5000', $gross, 0, 'Gross salaries'],
            ['5001', $erStat, 0, 'Employer pension, NHIA, NSITF, ITF'],
            ['2100', 0, $paye, 'PAYE'], ['2110', 0, $eePen, 'Employee pension'], ['2111', 0, $erPen, 'Employer pension'],
            ['2120', 0, $nhf, 'NHF'], ['2150', 0, $nhia + $erNhia, 'NHIA'], ['2130', 0, $nsitf, 'NSITF'], ['2155', 0, $itf, 'ITF'],
            ['1050', 0, $loans, 'Staff loan & advance recoveries'], ['2160', 0, $coop, 'Cooperative savings'],
            ['2020', 0, $net, 'Net pay owed to staff'],
        ];
        $dr = $gross + $erStat; $cr = $paye + $eePen + $erPen + $nhf + $nhia + $erNhia + $nsitf + $itf + $loans + $coop + $net;
        $rest = round($dr - $cr, 2);
        if (abs($rest) >= 0.01) $lines[] = ['2170', $rest < 0 ? -$rest : 0, $rest > 0 ? $rest : 0, 'Other payroll deductions (union dues, etc.)'];

        return $this->post('payroll_accrual', (string) $period->id, $period->end_date, 'payroll', 'Payroll ' . $period->period_name, $lines, $period);
    }

    public function salaryPaid($item): ?JournalEntry
    {
        $bank = $item->batch?->provider === 'manual' ? $this->bank() : $this->bank();
        return $this->post('payout', (string) $item->id, $item->paid_at ?? now(), 'payment', 'Salary paid · ' . ($item->account_name ?: 'staff') . ' · ' . $item->reference,
            [['2020', $item->amount, 0, 'Net pay', $item->staff_id], [$bank, 0, $item->amount, 'Transfer ' . $item->reference]], $item);
    }

    public function remittancePaid(object $rem, float $amount, $date, ?string $reference = null, ?string $key = null): ?JournalEntry
    {
        $map = ['paye' => [['2100', 1]], 'pension' => [['2110', null], ['2111', null]], 'nhf' => [['2120', 1]], 'nhia' => [['2150', 1]], 'nsitf' => [['2130', 1]], 'itf' => [['2155', 1]]];
        $lines = [];
        if ($rem->type === 'pension' && ((float) $rem->employee_amount + (float) $rem->employer_amount) > 0) {
            $share = $amount / ((float) $rem->employee_amount + (float) $rem->employer_amount);
            $lines[] = ['2110', round($rem->employee_amount * $share, 2), 0, 'Employee pension'];
            $lines[] = ['2111', round($amount - round($rem->employee_amount * $share, 2), 2), 0, 'Employer pension'];
        } else {
            $lines[] = [$map[$rem->type][0][0] ?? '2170', $amount, 0, strtoupper($rem->type) . ' · ' . $rem->authority];
        }
        $lines[] = [$this->bank(), 0, $amount, $reference];
        return $this->post('remittance', $key ?: ($rem->id . ':' . md5($amount . $date . $reference)), $date, 'payment',
            strtoupper($rem->type) . ' remitted to ' . $rem->authority, $lines, $rem);
    }

    // ── Loans & cooperative ────────────────────────────────────────
    public function loanDisbursed($loan): ?JournalEntry
    {
        $out = $loan->disbursement_method === 'cash' ? $this->cash() : $this->bank();
        $interest = round((float) $loan->total_repayable - (float) $loan->amount, 2);
        return $this->post('loan_out', (string) $loan->id, $loan->disbursed_at ?? now(), 'payment', $loan->typeLabel() . ' ' . $loan->reference_no . ' paid out', [
            ['1050', (float) $loan->total_repayable ?: (float) $loan->amount, 0, 'Receivable from staff', $loan->staff_id],
            [$out, 0, $loan->amount, $loan->disbursement_reference],
            ['4095', 0, max(0, $interest), 'Loan interest'],
        ], $loan);
    }

    public function loanRepaidDirect($rep): ?JournalEntry
    {
        $loan = $rep->loan;
        $debit = match ($rep->source) { 'cash' => $this->cash(), 'waiver' => '5120', default => $this->bank() };
        return $this->post('loan_repay', (string) $rep->id, $rep->paid_on, $rep->source === 'waiver' ? 'adjustment' : 'receipt',
            ($rep->source === 'waiver' ? 'Write-off ' : 'Repayment ') . ($loan->reference_no ?? ''), [[$debit, $rep->amount, 0, $rep->reference], ['1050', 0, $rep->amount, null, $loan->staff_id ?? null]], $rep);
    }

    public function coopTransaction($t): ?JournalEntry
    {
        $amt = abs((float) $t->amount);
        $lines = match ($t->type) {
            'contribution' => [[$this->cash(), $amt, 0], ['2160', 0, $amt, 'Member savings', $t->staff_id]],
            'withdrawal'   => [['2160', $amt, 0, 'Member withdrawal', $t->staff_id], [$this->bank(), 0, $amt]],
            'dividend'     => [['5130', $amt, 0], ['2160', 0, $amt, 'Dividend credited', $t->staff_id]],
            default        => [],
        };
        return $lines ? $this->post('coop', (string) $t->id, $t->txn_date, $t->type === 'withdrawal' ? 'payment' : 'journal', 'Cooperative ' . $t->type, $lines, $t) : null;
    }

    // ── Expenses & assets ──────────────────────────────────────────
    public function expensePaid($v): ?JournalEntry
    {
        $code = $v->capitalise ? ($v->asset_account ?: '1102') : (optional($v->category?->account)->account_code ?: '5110');
        $from = $v->paid_from ?: ($v->payment_method === 'cash' ? $this->cash() : $this->bank());
        return $this->post('expense', (string) $v->id, $v->paid_at ?? $v->expense_date, $v->payment_method === 'cash' ? 'petty_cash' : 'payment',
            $v->voucher_no . ' · ' . $v->payee_name . ' · ' . \Illuminate\Support\Str::limit($v->description, 80),
            [[$code, $v->amount, 0, $v->description], [$from, 0, $v->amount, $v->reference]], $v);
    }

    public function depreciation(string $period, array $rows): ?JournalEntry
    {
        $total = round(array_sum(array_column($rows, 'amount')), 2);
        return $this->post('depreciation', $period, Carbon::parse($period . '-01')->endOfMonth(), 'depreciation', 'Depreciation ' . Carbon::parse($period . '-01')->format('F Y'),
            [['5080', $total, 0, count($rows) . ' asset(s)'], ['1110', 0, $total]]);
    }

    public function assetRegistered($a): ?JournalEntry
    {
        if ($a->expense_voucher_id) return null; // bought through a voucher — already in the books
        $acc = min((float) $a->accumulated_depreciation, (float) $a->cost);
        return $this->post('asset_open', (string) $a->id, now(), 'opening', 'Opening balance · ' . $a->asset_tag . ' ' . $a->name,
            [[$a->account_code, $a->cost, 0], ['1110', 0, $acc], ['3010', 0, round($a->cost - $acc, 2), 'Existing asset brought into the books']], $a);
    }

    public function assetDisposed($a): ?JournalEntry
    {
        $proceeds = (float) $a->disposal_amount; $acc = (float) $a->accumulated_depreciation; $nbv = round($a->cost - $acc, 2);
        $gain = round($proceeds - $nbv, 2);
        return $this->post('asset_out', (string) $a->id, $a->disposal_date ?? now(), 'adjustment', ucfirst($a->status) . ' · ' . $a->asset_tag . ' ' . $a->name, [
            ['1110', $acc, 0], [$this->bank(), $proceeds, 0, 'Sale proceeds'], [$a->account_code, 0, $a->cost],
            $gain >= 0 ? ['4100', 0, $gain, 'Gain on disposal'] : ['5081', -$gain, 0, 'Loss on disposal'],
        ], $a);
    }

    // ── School fees ────────────────────────────────────────────────
    /** Post fee receipts (one journal per day and channel) that are not yet in the ledger. */
    public function syncFees(?string $from = null, ?string $to = null): array
    {
        if (!Schema::hasTable('student_bill_payment_record')) return ['days' => 0, 'amount' => 0];
        $to = $to ?: now()->subDay()->toDateString();
        $from = $from ?: Carbon::parse($to)->subDays(90)->toDateString();
        $hasRev = Schema::hasColumn('student_bill_payment_record', 'is_reversal');
        $hasCh = Schema::hasColumn('student_bill_payment_record', 'payment_channel');

        $posted = DB::table('ledger_postings')->where('source', 'fee_record')->pluck('source_key')->flip();
        $recs = DB::table('student_bill_payment_record')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->get(array_merge(['id', 'amount_paid', 'created_at'], $hasRev ? ['is_reversal'] : [], $hasCh ? ['payment_channel'] : []))
            ->reject(fn ($r) => isset($posted[(string) $r->id]));

        $groups = $recs->groupBy(fn ($r) => substr((string) $r->created_at, 0, 10) . '|' . ($hasCh && str_contains(strtolower((string) $r->payment_channel), 'cash') ? 'cash' : 'bank'));
        $days = 0; $sum = 0.0;
        foreach ($groups as $k => $rows) {
            [$date, $ch] = explode('|', $k);
            $amt = round($rows->sum(fn ($r) => (float) preg_replace('/[^\d.\-]/', '', (string) $r->amount_paid) * ($hasRev && $r->is_reversal ? -1 : 1)), 2);
            if ($amt == 0.0) { $this->markRecords($rows, null); continue; }
            $dr = $ch === 'cash' ? $this->cash() : $this->bank();
            $je = $this->post('fees_day', $date . ':' . $ch . ':' . $rows->min('id') . '-' . $rows->max('id'), $date, 'receipt',
                'School fees received ' . Carbon::parse($date)->format('d M Y') . ' (' . $rows->count() . ' payment' . ($rows->count() > 1 ? 's' : '') . ', ' . $ch . ')',
                [[$dr, $amt, 0], ['4000', 0, $amt]]);
            if ($je) { $this->markRecords($rows, $je->id); $days++; $sum += $amt; }
        }
        return ['days' => $days, 'amount' => round($sum, 2), 'pending' => 0];
    }

    protected function markRecords($rows, ?int $jeId): void
    {
        $now = now();
        foreach ($rows->chunk(500) as $chunk) {
            DB::table('ledger_postings')->insertOrIgnore($chunk->map(fn ($r) => ['source' => 'fee_record', 'source_key' => (string) $r->id, 'journal_entry_id' => $jeId ?? 0, 'created_at' => $now, 'updated_at' => $now])->values()->all());
        }
    }

    /** Post anything that was missed (e.g. months approved before the ledger was switched on). */
    public function catchUp(): array
    {
        $done = ['payroll' => 0, 'payouts' => 0, 'expenses' => 0, 'fees' => 0];
        foreach (PayrollPeriod::whereIn('status', ['approved', 'paid', 'locked'])->get() as $p) {
            try { if ($this->payrollAccrual($p)) $done['payroll']++; } catch (\Throwable $e) { Log::warning('catch-up payroll', ['p' => $p->id, 'e' => $e->getMessage()]); }
        }
        if (Schema::hasTable('payout_items')) {
            foreach (\App\Models\PayoutItem::whereIn('status', ['success', 'manual'])->where('purpose', 'salary')->get() as $i) {
                try { if ($this->salaryPaid($i)) $done['payouts']++; } catch (\Throwable $e) {}
            }
        }
        if (Schema::hasTable('expense_vouchers')) {
            foreach (\App\Models\ExpenseVoucher::with('category.account')->where('status', 'paid')->get() as $v) {
                try { if ($this->expensePaid($v)) $done['expenses']++; } catch (\Throwable $e) {}
            }
        }
        $done['fees'] = $this->syncFees()['days'];
        return $done;
    }
}
