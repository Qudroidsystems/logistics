<?php

namespace App\Services\Audit;

use App\Models\FinancialAuditLog;
use App\Models\FinancialAuditReview;
use App\Support\FinanceSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The accountant's audit brain. Reads the books (ledger, vouchers, payments)
 * and surfaces exceptions an auditor checks for, plus the numbers for the audit
 * dashboard. Every detector is defensive: a missing table/column yields no
 * findings rather than an error, so the audit page never breaks the portal.
 */
class FinancialAuditService
{
    public static function available(): bool
    {
        return Schema::hasTable('financial_audit_logs');
    }

    // ── Dashboard numbers ────────────────────────────────────────────────

    public function summary(string $from, string $to): array
    {
        return [
            'monthly'        => $this->monthlyIncomeExpense(12),
            'totals'         => $this->incomeExpenseTotals($from, $to),
            'expense_by_cat' => $this->expenseByCategory($from, $to),
            'top_actors'     => $this->topActors($from, $to),
            'exception_counts' => $this->exceptionCounts(),
            'change_volume'  => $this->changeVolume($from, $to),
        ];
    }

    /** Income vs expense for the last $n months (posted journals). */
    public function monthlyIncomeExpense(int $n = 12): array
    {
        if (!Schema::hasTable('journal_entry_lines') || !Schema::hasTable('chart_of_accounts')) return [];
        $start = Carbon::now()->startOfMonth()->subMonths($n - 1);
        $rows = DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('chart_of_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('e.status', 'posted')
            ->where('e.entry_date', '>=', $start->toDateString())
            ->whereIn('a.account_type', ['income', 'expense'])
            ->selectRaw("DATE_FORMAT(e.entry_date,'%Y-%m') ym, a.account_type,
                SUM(l.credit) credit, SUM(l.debit) debit")
            ->groupBy('ym', 'a.account_type')->get();

        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $m = $start->copy()->addMonths($i);
            $out[$m->format('Y-m')] = ['label' => $m->format('M'), 'income' => 0.0, 'expense' => 0.0];
        }
        foreach ($rows as $r) {
            if (!isset($out[$r->ym])) continue;
            if ($r->account_type === 'income') $out[$r->ym]['income'] += (float) $r->credit - (float) $r->debit;
            else $out[$r->ym]['expense'] += (float) $r->debit - (float) $r->credit;
        }
        return array_values($out);
    }

    public function incomeExpenseTotals(string $from, string $to): array
    {
        $income = 0.0; $expense = 0.0;
        if (Schema::hasTable('journal_entry_lines') && Schema::hasTable('chart_of_accounts')) {
            $rows = DB::table('journal_entry_lines as l')
                ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
                ->join('chart_of_accounts as a', 'a.id', '=', 'l.account_id')
                ->where('e.status', 'posted')->whereBetween('e.entry_date', [$from, $to])
                ->whereIn('a.account_type', ['income', 'expense'])
                ->selectRaw("a.account_type, SUM(l.credit) credit, SUM(l.debit) debit")
                ->groupBy('a.account_type')->get();
            foreach ($rows as $r) {
                if ($r->account_type === 'income') $income += (float) $r->credit - (float) $r->debit;
                else $expense += (float) $r->debit - (float) $r->credit;
            }
        }
        return ['income' => $income, 'expense' => $expense, 'net' => $income - $expense];
    }

    public function expenseByCategory(string $from, string $to): array
    {
        if (!Schema::hasTable('expense_vouchers')) return [];
        $q = DB::table('expense_vouchers as v')
            ->leftJoin('expense_categories as c', 'c.id', '=', 'v.expense_category_id')
            ->whereIn('v.status', ['approved', 'paid'])
            ->whereBetween('v.expense_date', [$from, $to])
            ->selectRaw("COALESCE(c.name,'Uncategorised') name, SUM(v.amount) total")
            ->groupBy('name')->orderByDesc('total')->limit(8)->get();
        return $q->map(fn ($r) => ['name' => $r->name, 'total' => (float) $r->total])->all();
    }

    public function topActors(string $from, string $to): array
    {
        if (!self::available()) return [];
        return DB::table('financial_audit_logs')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw('user_id, user_name, COUNT(*) actions, SUM(COALESCE(amount,0)) total')
            ->groupBy('user_id', 'user_name')->orderByDesc('actions')->limit(8)->get()
            ->map(fn ($r) => ['name' => $r->user_name ?: ('User #' . $r->user_id), 'actions' => (int) $r->actions, 'total' => (float) $r->total])->all();
    }

    public function changeVolume(string $from, string $to): array
    {
        if (!self::available()) return ['created' => 0, 'updated' => 0, 'deleted' => 0];
        $rows = DB::table('financial_audit_logs')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw('event, COUNT(*) c')->groupBy('event')->pluck('c', 'event');
        return ['created' => (int) ($rows['created'] ?? 0), 'updated' => (int) ($rows['updated'] ?? 0), 'deleted' => (int) ($rows['deleted'] ?? 0)];
    }

    // ── Exceptions ───────────────────────────────────────────────────────

    public function exceptionCounts(): array
    {
        $all = $this->exceptions();
        $open = $all->where('status', 'open');
        return [
            'total' => $all->count(),
            'open'  => $open->count(),
            'by_kind' => $open->groupBy('kind')->map->count()->all(),
        ];
    }

    /**
     * All detected exceptions, each merged with its review status.
     * @param array $filter ['kind'=>..., 'include_cleared'=>bool]
     */
    public function exceptions(array $filter = []): Collection
    {
        $items = collect();
        foreach ([
            'unbalanced'      => 'unbalancedEntries',
            'closed_period'   => 'closedPeriodPostings',
            'deletion'        => 'deletions',
            'post_approval'   => 'postApprovalEdits',
            'duplicate'       => 'duplicatePayments',
            'large'           => 'largeTransactions',
            'reversal'        => 'reversals',
            'self_approved'   => 'selfApproved',
        ] as $kind => $method) {
            if (!empty($filter['kind']) && $filter['kind'] !== $kind) continue;
            try { $items = $items->merge($this->{$method}()); } catch (\Throwable $e) {}
        }

        // merge review status
        $reviews = FinancialAuditReview::get()->keyBy(fn ($r) => $r->kind . '|' . $r->ref_key);
        $items = $items->map(function ($x) use ($reviews) {
            $r = $reviews->get($x['kind'] . '|' . $x['key']) ?? null;
            $x['status'] = $r->status ?? 'open';
            $x['review_note'] = $r->note ?? null;
            return $x;
        });

        if (empty($filter['include_cleared'])) {
            $items = $items->where('status', '!=', 'cleared');
        }
        return $items->sortByDesc('date')->values();
    }

    protected function largeThreshold(): float
    {
        try { $v = FinanceSettings::get('expenses')['second_approval_threshold'] ?? null; }
        catch (\Throwable $e) { $v = null; }
        return (float) ($v ?: 500000);
    }

    protected function closedUntil(): ?string
    {
        try { return FinanceSettings::get('accounting')['books_closed_until'] ?? null; }
        catch (\Throwable $e) { return null; }
    }

    protected function unbalancedEntries(): array
    {
        if (!Schema::hasTable('journal_entries') || !Schema::hasTable('journal_entry_lines')) return [];
        $rows = DB::table('journal_entries as e')
            ->join('journal_entry_lines as l', 'l.journal_entry_id', '=', 'e.id')
            ->where('e.status', 'posted')
            ->groupBy('e.id', 'e.entry_no', 'e.entry_date', 'e.description')
            ->havingRaw('ROUND(SUM(l.debit),2) <> ROUND(SUM(l.credit),2)')
            ->selectRaw('e.id, e.entry_no, e.entry_date, e.description, SUM(l.debit) d, SUM(l.credit) c')
            ->limit(200)->get();
        return $rows->map(fn ($r) => [
            'kind' => 'unbalanced', 'key' => (string) $r->id, 'severity' => 'high',
            'title' => 'Unbalanced journal entry ' . $r->entry_no,
            'detail' => 'Debits ' . number_format($r->d, 2) . ' ≠ Credits ' . number_format($r->c, 2) . '. ' . $r->description,
            'amount' => abs((float) $r->d - (float) $r->c), 'date' => Carbon::parse($r->entry_date)->toDateString(),
            'ref' => $r->entry_no, 'user' => null,
        ])->all();
    }

    protected function closedPeriodPostings(): array
    {
        $closed = $this->closedUntil();
        if (!$closed || !Schema::hasTable('journal_entries')) return [];
        // Entries dated within a closed period but created after it was closed.
        $rows = DB::table('journal_entries')
            ->whereDate('entry_date', '<=', $closed)
            ->whereColumn('created_at', '>', 'entry_date')
            ->where('created_at', '>', Carbon::parse($closed)->endOfDay())
            ->orderByDesc('created_at')->limit(200)
            ->get(['id', 'entry_no', 'entry_date', 'description', 'created_at']);
        return $rows->map(fn ($r) => [
            'kind' => 'closed_period', 'key' => (string) $r->id, 'severity' => 'high',
            'title' => 'Entry into a closed period: ' . $r->entry_no,
            'detail' => 'Dated ' . Carbon::parse($r->entry_date)->format('d M Y') . ' (books closed to ' . Carbon::parse($closed)->format('d M Y') . '), recorded ' . Carbon::parse($r->created_at)->format('d M Y'),
            'amount' => null, 'date' => Carbon::parse($r->created_at)->toDateString(), 'ref' => $r->entry_no, 'user' => null,
        ])->all();
    }

    protected function deletions(): array
    {
        if (!self::available()) return [];
        $rows = FinancialAuditLog::where('event', 'deleted')->orderByDesc('created_at')->limit(200)->get();
        return $rows->map(fn ($r) => [
            'kind' => 'deletion', 'key' => (string) $r->id, 'severity' => 'high',
            'title' => 'Deleted ' . strtolower($r->model_label ?? 'record') . ' ' . ($r->ref ?: ''),
            'detail' => ($r->amount ? '₦' . number_format($r->amount, 2) . '. ' : '') . 'Removed by ' . ($r->user_name ?: 'someone'),
            'amount' => $r->amount, 'date' => optional($r->created_at)->toDateString(), 'ref' => $r->ref, 'user' => $r->user_name,
        ])->all();
    }

    protected function postApprovalEdits(): array
    {
        if (!self::available()) return [];
        $rows = FinancialAuditLog::where('event', 'updated')
            ->whereIn('auditable_type', ['ExpenseVoucher', 'JournalEntry', 'LoanAdvance'])
            ->orderByDesc('created_at')->limit(300)->get();
        $out = [];
        foreach ($rows as $r) {
            $ch = $r->changes ?? [];
            $touchesMoney = array_intersect(array_keys($ch), ['amount', 'expense_date', 'entry_date', 'debit', 'credit', 'account_id', 'total_amount']);
            // an edit that changes money/date on an approvable record — flag for review
            if (!$touchesMoney) continue;
            $out[] = [
                'kind' => 'post_approval', 'key' => (string) $r->id, 'severity' => 'medium',
                'title' => 'Financial edit on ' . strtolower($r->model_label ?? 'record') . ' ' . ($r->ref ?: ''),
                'detail' => 'Changed ' . implode(', ', array_keys($ch)) . ' — by ' . ($r->user_name ?: 'someone'),
                'amount' => $r->amount, 'date' => optional($r->created_at)->toDateString(), 'ref' => $r->ref, 'user' => $r->user_name,
            ];
        }
        return $out;
    }

    protected function duplicatePayments(): array
    {
        if (!Schema::hasTable('student_bill_payment_record')) return [];
        // Same student bill + same amount + same day recorded more than once.
        $rows = DB::table('student_bill_payment_record')
            ->selectRaw('student_bill_payment_id, amount_paid, DATE(created_at) d, COUNT(*) n, MIN(id) minid')
            ->whereNotNull('amount_paid')->where('amount_paid', '>', 0)
            ->groupBy('student_bill_payment_id', 'amount_paid', 'd')
            ->havingRaw('COUNT(*) > 1')->limit(200)->get();
        return $rows->map(fn ($r) => [
            'kind' => 'duplicate', 'key' => $r->student_bill_payment_id . ':' . $r->amount_paid . ':' . $r->d,
            'severity' => 'medium',
            'title' => 'Possible duplicate fee payment',
            'detail' => $r->n . ' payments of ₦' . number_format($r->amount_paid, 2) . ' on ' . $r->d . ' for bill #' . $r->student_bill_payment_id,
            'amount' => (float) $r->amount_paid, 'date' => $r->d, 'ref' => '#' . $r->student_bill_payment_id, 'user' => null,
        ])->all();
    }

    protected function largeTransactions(): array
    {
        if (!Schema::hasTable('expense_vouchers')) return [];
        $t = $this->largeThreshold();
        $rows = DB::table('expense_vouchers')->where('amount', '>=', $t)
            ->whereIn('status', ['submitted', 'approved', 'paid'])
            ->orderByDesc('amount')->limit(100)
            ->get(['id', 'voucher_no', 'amount', 'expense_date', 'description', 'status']);
        return $rows->map(fn ($r) => [
            'kind' => 'large', 'key' => (string) $r->id, 'severity' => 'low',
            'title' => 'Large expense: ' . $r->voucher_no . ' (₦' . number_format($r->amount, 2) . ')',
            'detail' => ucfirst($r->status) . ' — ' . \Illuminate\Support\Str::limit($r->description, 80) . ' (threshold ₦' . number_format($t, 0) . ')',
            'amount' => (float) $r->amount, 'date' => Carbon::parse($r->expense_date)->toDateString(), 'ref' => $r->voucher_no, 'user' => null,
        ])->all();
    }

    protected function reversals(): array
    {
        if (!Schema::hasTable('journal_entries') || !Schema::hasColumn('journal_entries', 'reversed_at')) return [];
        $rows = DB::table('journal_entries')->whereNotNull('reversed_at')
            ->orderByDesc('reversed_at')->limit(200)
            ->get(['id', 'entry_no', 'entry_date', 'reversal_reason', 'reversed_at']);
        return $rows->map(fn ($r) => [
            'kind' => 'reversal', 'key' => (string) $r->id, 'severity' => 'low',
            'title' => 'Reversed entry ' . $r->entry_no,
            'detail' => 'Reversed ' . Carbon::parse($r->reversed_at)->format('d M Y') . ($r->reversal_reason ? ' — ' . $r->reversal_reason : ''),
            'amount' => null, 'date' => Carbon::parse($r->reversed_at)->toDateString(), 'ref' => $r->entry_no, 'user' => null,
        ])->all();
    }

    protected function selfApproved(): array
    {
        if (!Schema::hasTable('expense_vouchers') || !Schema::hasColumn('expense_vouchers', 'requested_by') || !Schema::hasColumn('expense_vouchers', 'approved_by')) return [];
        $rows = DB::table('expense_vouchers')->whereNotNull('approved_by')
            ->whereColumn('requested_by', 'approved_by')
            ->orderByDesc('approved_at')->limit(100)
            ->get(['id', 'voucher_no', 'amount', 'expense_date']);
        return $rows->map(fn ($r) => [
            'kind' => 'self_approved', 'key' => (string) $r->id, 'severity' => 'high',
            'title' => 'Voucher approved by its creator: ' . $r->voucher_no,
            'detail' => 'Same person requested and approved ₦' . number_format($r->amount, 2) . ' — segregation-of-duties breach',
            'amount' => (float) $r->amount, 'date' => Carbon::parse($r->expense_date)->toDateString(), 'ref' => $r->voucher_no, 'user' => null,
        ])->all();
    }

    // ── Review workflow ──────────────────────────────────────────────────

    public function review(string $kind, string $key, string $status, ?string $note, int $userId): void
    {
        FinancialAuditReview::updateOrCreate(
            ['kind' => $kind, 'ref_key' => $key],
            ['status' => $status, 'note' => $note, 'reviewed_by' => $userId, 'reviewed_at' => now()]
        );
    }

    public const KINDS = [
        'unbalanced' => 'Unbalanced entries', 'closed_period' => 'Closed-period postings',
        'deletion' => 'Deleted records', 'post_approval' => 'Post-record financial edits',
        'duplicate' => 'Duplicate payments', 'large' => 'Large transactions',
        'reversal' => 'Reversals', 'self_approved' => 'Self-approved vouchers',
    ];
}
