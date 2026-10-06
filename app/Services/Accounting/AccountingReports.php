<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Trial balance, income statement, balance sheet, cash flow and account ledgers from posted journals. */
class AccountingReports
{
    /** [account_id => ['dr' => x, 'cr' => y]] for posted entries in a date range. */
    public function sums(?string $from, string $to): Collection
    {
        return DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->whereIn('e.status', ['posted', 'reversed'])->where('e.entry_date', '<=', $to)
            ->when($from, fn ($q) => $q->where('e.entry_date', '>=', $from))
            ->groupBy('l.account_id')->selectRaw('l.account_id, SUM(l.debit) dr, SUM(l.credit) cr')->get()->keyBy('account_id');
    }

    public function accounts(): Collection
    {
        return ChartOfAccount::orderBy('account_code')->get();
    }

    /** Signed balance in the account's natural direction. */
    protected function natural(object $a, ?object $s): float
    {
        if (!$s) return 0.0;
        $net = (float) $s->dr - (float) $s->cr;
        return round(in_array($a->account_type, ['asset', 'expense']) ? $net : -$net, 2);
    }

    public function trialBalance(string $asAt): array
    {
        $sums = $this->sums(null, $asAt);
        $rows = [];
        foreach ($this->accounts() as $a) {
            $s = $sums[$a->id] ?? null;
            if (!$s) continue;
            $net = round((float) $s->dr - (float) $s->cr, 2);
            if ($net == 0.0) continue;
            $rows[] = ['account' => $a, 'debit' => $net > 0 ? $net : 0, 'credit' => $net < 0 ? -$net : 0];
        }
        return ['rows' => $rows, 'debit' => round(array_sum(array_column($rows, 'debit')), 2), 'credit' => round(array_sum(array_column($rows, 'credit')), 2), 'as_at' => $asAt];
    }

    public function incomeStatement(string $from, string $to): array
    {
        $sums = $this->sums($from, $to);
        $income = []; $staff = []; $depr = []; $other = [];
        foreach ($this->accounts() as $a) {
            if (!in_array($a->account_type, ['income', 'expense'])) continue;
            $v = $this->natural($a, $sums[$a->id] ?? null);
            if ($v == 0.0) continue;
            $row = ['account' => $a, 'amount' => $v];
            if ($a->account_type === 'income') $income[] = $row;
            elseif (in_array($a->account_code, ['5000', '5001', '5050'])) $staff[] = $row;
            elseif (in_array($a->account_code, ['5080', '5081'])) $depr[] = $row;
            else $other[] = $row;
        }
        $t = fn ($r) => round(array_sum(array_column($r, 'amount')), 2);
        $totalExp = $t($staff) + $t($other) + $t($depr);
        return ['income' => $income, 'staff' => $staff, 'other' => $other, 'depreciation' => $depr,
                'total_income' => $t($income), 'total_staff' => $t($staff), 'total_other' => $t($other), 'total_depreciation' => $t($depr),
                'total_expenses' => round($totalExp, 2), 'surplus' => round($t($income) - $totalExp, 2), 'from' => $from, 'to' => $to];
    }

    public function balanceSheet(string $asAt): array
    {
        $sums = $this->sums(null, $asAt);
        $sec = ['current' => [], 'fixed' => [], 'liabilities' => [], 'equity' => []];
        $surplus = 0.0;
        foreach ($this->accounts() as $a) {
            $v = $this->natural($a, $sums[$a->id] ?? null);
            if ($a->account_type === 'income') { $surplus += $v; continue; }
            if ($a->account_type === 'expense') { $surplus -= $v; continue; }
            if ($v == 0.0) continue;
            $row = ['account' => $a, 'amount' => $v]; // accumulated depreciation (1110) comes out negative: a deduction
            if ($a->account_type === 'asset') $sec[str_starts_with($a->account_code, '11') ? 'fixed' : 'current'][] = $row;
            elseif ($a->account_type === 'liability') $sec['liabilities'][] = $row;
            else $sec['equity'][] = $row;
        }
        $t = fn ($x) => round(array_sum(array_column($x, 'amount')), 2);
        $assets = $t($sec['current']) + $t($sec['fixed']);
        $equity = $t($sec['equity']) + round($surplus, 2);
        return $sec + ['total_current' => $t($sec['current']), 'total_fixed' => $t($sec['fixed']), 'total_assets' => round($assets, 2),
                'total_liabilities' => $t($sec['liabilities']), 'surplus' => round($surplus, 2), 'total_equity' => round($equity, 2),
                'difference' => round($assets - $t($sec['liabilities']) - $equity, 2), 'as_at' => $asAt];
    }

    public function cashAccounts(): Collection
    {
        return ChartOfAccount::where('account_type', 'asset')->where(fn ($q) => $q->where('is_bank_account', true)->orWhereIn('account_code', ['1010', '1020']))->get();
    }

    public function cashFlow(string $from, string $to): array
    {
        $cashIds = $this->cashAccounts()->pluck('id')->all();
        $acc = $this->accounts()->keyBy('id');
        $opening = 0.0;
        foreach ($this->sums(null, Carbon::parse($from)->subDay()->toDateString()) as $id => $s) if (in_array($id, $cashIds)) $opening += (float) $s->dr - (float) $s->cr;

        $lines = DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->whereIn('e.status', ['posted', 'reversed'])->whereBetween('e.entry_date', [$from, $to])
            ->whereIn('l.journal_entry_id', DB::table('journal_entry_lines')->whereIn('account_id', $cashIds ?: [0])->select('journal_entry_id'))
            ->get(['l.journal_entry_id', 'l.account_id', 'l.debit', 'l.credit'])->groupBy('journal_entry_id');

        $groups = ['operating' => [], 'investing' => [], 'financing' => []];
        foreach ($lines as $entryLines) {
            $cash = 0.0; $counter = null; $big = -1;
            foreach ($entryLines as $l) {
                if (in_array($l->account_id, $cashIds)) { $cash += (float) $l->debit - (float) $l->credit; continue; }
                $mag = abs((float) $l->debit - (float) $l->credit);
                if ($mag > $big) { $big = $mag; $counter = $acc[$l->account_id] ?? null; }
            }
            if (round($cash, 2) == 0.0 || !$counter) continue;
            $g = str_starts_with($counter->account_code, '11') ? 'investing' : ($counter->account_type === 'equity' ? 'financing' : 'operating');
            $label = $counter->account_name;
            $groups[$g][$label] = round(($groups[$g][$label] ?? 0) + $cash, 2);
        }
        $tot = fn ($g) => round(array_sum($groups[$g]), 2);
        $net = $tot('operating') + $tot('investing') + $tot('financing');
        foreach ($groups as &$g) arsort($g);
        unset($g);
        return ['groups' => $groups, 'operating' => $tot('operating'), 'investing' => $tot('investing'), 'financing' => $tot('financing'),
                'net' => round($net, 2), 'opening' => round($opening, 2), 'closing' => round($opening + $net, 2), 'from' => $from, 'to' => $to];
    }

    public function ledger(int $accountId, string $from, string $to): array
    {
        $a = ChartOfAccount::findOrFail($accountId);
        $dir = in_array($a->account_type, ['asset', 'expense']) ? 1 : -1;
        $o = $this->sums(null, Carbon::parse($from)->subDay()->toDateString())[$accountId] ?? null;
        $opening = $o ? round(((float) $o->dr - (float) $o->cr) * $dir, 2) : 0.0;
        $rows = DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->whereIn('e.status', ['posted', 'reversed'])->where('l.account_id', $accountId)->whereBetween('e.entry_date', [$from, $to])
            ->orderBy('e.entry_date')->orderBy('e.id')->get(['e.id as entry_id', 'e.entry_no', 'e.entry_date', 'e.description', 'e.entry_type', 'l.debit', 'l.credit', 'l.narration']);
        $run = $opening;
        foreach ($rows as $r) { $run = round($run + ((float) $r->debit - (float) $r->credit) * $dir, 2); $r->balance = $run; }
        return ['account' => $a, 'opening' => $opening, 'rows' => $rows, 'closing' => $run, 'debit' => $rows->sum('debit'), 'credit' => $rows->sum('credit'), 'from' => $from, 'to' => $to];
    }

    /** Headline figures for the accounting dashboard. */
    public function dashboard(): array
    {
        $today = now()->toDateString();
        $fyStart = $this->yearStart();
        $sums = $this->sums(null, $today);
        $cash = $this->cashAccounts()->map(fn ($a) => ['account' => $a, 'balance' => $this->natural($a, $sums[$a->id] ?? null)]);
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonthsNoOverflow($i);
            $is = $this->incomeStatement($m->toDateString(), $m->copy()->endOfMonth()->toDateString());
            $months[] = ['label' => $m->format('M'), 'income' => $is['total_income'], 'expenses' => $is['total_expenses']];
        }
        return ['cash' => $cash, 'cash_total' => round($cash->sum('balance'), 2), 'ytd' => $this->incomeStatement($fyStart, $today), 'months' => $months, 'fy_start' => $fyStart];
    }

    public function yearStart(): string
    {
        $mmdd = \App\Support\FinanceSettings::get('accounting')['financial_year_start'] ?? '09-01';
        $start = Carbon::parse(now()->year . '-' . $mmdd);
        return ($start->gt(now()) ? $start->subYear() : $start)->toDateString();
    }
}
