<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\Accounting\AccountingReports;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\LedgerPoster;
use App\Support\FinanceSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** General ledger: dashboard, chart of accounts, journals, ledgers and financial statements. */
class AccountingController extends Controller
{
    public function __construct(protected AccountingReports $reports)
    {
        $this->middleware('permission:View financial reports|Post journal entries|Manage chart of accounts');
        $this->middleware('permission:Manage chart of accounts')->only(['saveAccount']);
        $this->middleware('permission:Post journal entries')->only(['journalCreate', 'journalStore', 'journalPost', 'journalReverse', 'syncFees', 'catchUp']);
        $this->middleware('permission:Close accounting period')->only(['saveSettings']);
    }

    protected function range(Request $r): array
    {
        $from = $r->get('from') ?: $this->reports->yearStart();
        $to = $r->get('to') ?: now()->toDateString();
        return [Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString()];
    }

    public function dashboard()
    {
        $unpostedPayroll = \App\Models\PayrollPeriod::whereIn('status', ['approved', 'paid', 'locked'])
            ->whereNotIn('id', DB::table('ledger_postings')->where('source', 'payroll_accrual')->pluck('source_key'))->count();
        $feeTo = now()->subDay()->toDateString();
        $unpostedFees = \Illuminate\Support\Facades\Schema::hasTable('student_bill_payment_record')
            ? DB::table('student_bill_payment_record')->whereDate('created_at', '<=', $feeTo)->whereDate('created_at', '>=', now()->subDays(90))
                ->whereNotIn('id', DB::table('ledger_postings')->where('source', 'fee_record')->select(DB::raw('CAST(source_key AS UNSIGNED)')))->count()
            : 0;
        return view('accounting.dashboard', [
            'pagetitle' => 'Accounting', 'd' => $this->reports->dashboard(), 'settings' => FinanceSettings::get('accounting'),
            'recent' => JournalEntry::with('lines')->latest('id')->limit(8)->get(),
            'unposted' => ['payroll' => $unpostedPayroll, 'fees' => $unpostedFees],
            'accounts' => ChartOfAccount::where('account_type', 'asset')->orderBy('account_code')->get(),
        ]);
    }

    // ── Chart of accounts ─────────────────────────────────────────
    public function accounts()
    {
        $sums = $this->reports->sums(null, now()->toDateString());
        $accounts = ChartOfAccount::orderBy('account_code')->get()->map(function ($a) use ($sums) {
            $s = $sums[$a->id] ?? null;
            $a->balance = $s ? round(((float) $s->dr - (float) $s->cr) * (in_array($a->account_type, ['asset', 'expense']) ? 1 : -1), 2) : 0;
            return $a;
        });
        return view('accounting.accounts', ['pagetitle' => 'Chart of Accounts', 'accounts' => $accounts->groupBy('account_type')]);
    }

    public function saveAccount(Request $request)
    {
        $d = $request->validate([
            'id' => 'nullable|integer|exists:chart_of_accounts,id',
            'account_code' => 'required|string|max:20|unique:chart_of_accounts,account_code,' . ($request->id ?: 'NULL'),
            'account_name' => 'required|string|max:150', 'account_type' => 'required|in:asset,liability,equity,income,expense',
            'bank_name' => 'nullable|string|max:120', 'bank_account_no' => 'nullable|string|max:20', 'description' => 'nullable|string|max:255',
        ]);
        $d['normal_balance'] = in_array($d['account_type'], ['asset', 'expense']) ? 'debit' : 'credit';
        $d['is_bank_account'] = $request->boolean('is_bank_account');
        $d['is_active'] = $request->boolean('is_active', true);
        ChartOfAccount::updateOrCreate(['id' => $d['id'] ?? null], collect($d)->except('id')->all());
        return back()->with('success', 'Account saved.');
    }

    // ── Journals ──────────────────────────────────────────────────
    public function journals(Request $request)
    {
        [$from, $to] = $this->range($request);
        $entries = JournalEntry::with(['lines.account:id,account_code,account_name', 'creator:id,name'])
            ->whereBetween('entry_date', [$from, $to])
            ->when($request->filled('type'), fn ($q) => $q->where('entry_type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('entry_no', 'like', "%{$request->q}%")->orWhere('description', 'like', "%{$request->q}%")))
            ->latest('entry_date')->latest('id')->paginate(30)->withQueryString();
        return view('accounting.journals', ['pagetitle' => 'Journal', 'entries' => $entries, 'from' => $from, 'to' => $to]);
    }

    public function journalCreate()
    {
        return view('accounting.journal-form', ['pagetitle' => 'New journal entry',
            'accounts' => ChartOfAccount::where('is_active', true)->orderBy('account_code')->get(), 'closed' => FinanceSettings::get('accounting')['books_closed_until']]);
    }

    public function journalStore(Request $request)
    {
        $closed = FinanceSettings::get('accounting')['books_closed_until'];
        $d = $request->validate([
            'entry_date' => 'required|date' . ($closed ? '|after:' . $closed : ''), 'entry_type' => 'required|in:journal,adjustment,opening,contra,receipt,payment,petty_cash',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2', 'lines.*.account_id' => 'required|integer|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0', 'lines.*.credit' => 'nullable|numeric|min:0', 'lines.*.narration' => 'nullable|string|max:255',
        ], ['entry_date.after' => 'The books are closed up to ' . $closed . '. Choose a later date.']);
        $lines = collect($d['lines'])->map(fn ($l) => ['account_id' => (int) $l['account_id'], 'debit' => round((float) ($l['debit'] ?? 0), 2), 'credit' => round((float) ($l['credit'] ?? 0), 2), 'narration' => $l['narration'] ?? null])
            ->filter(fn ($l) => $l['debit'] > 0 || $l['credit'] > 0)->values()->all();
        foreach ($lines as $l) if ($l['debit'] > 0 && $l['credit'] > 0) return back()->withInput()->with('error', 'A line can have a debit or a credit, not both.');
        if (count($lines) < 2) return back()->withInput()->with('error', 'Enter at least two lines.');
        $dr = array_sum(array_column($lines, 'debit')); $cr = array_sum(array_column($lines, 'credit'));
        if (abs($dr - $cr) > 0.009) return back()->withInput()->with('error', 'Debits (₦' . number_format($dr, 2) . ') and credits (₦' . number_format($cr, 2) . ') must be equal.');

        $svc = app(AccountingService::class);
        $je = $svc->createJournalEntry(['entry_date' => $d['entry_date'], 'entry_type' => $d['entry_type'], 'description' => $d['description']], $lines);
        if ($request->input('action') === 'post') $svc->postEntry($je->id);
        return redirect()->route('accounting.journals.show', $je)->with('success', $request->input('action') === 'post' ? 'Journal posted.' : 'Saved as draft — post it when checked.');
    }

    public function journalShow(JournalEntry $entry)
    {
        $entry->load(['lines.account', 'creator:id,name', 'approver:id,name']);
        $reversal = JournalEntry::where('entry_type', 'reversal')->where('reference_type', JournalEntry::class)->where('reference_id', $entry->id)->first();
        $source = $entry->reference_type && class_exists($entry->reference_type) ? $this->sourceLink($entry) : null;
        return view('accounting.journal-show', ['pagetitle' => $entry->entry_no, 'e' => $entry, 'reversal' => $reversal, 'source' => $source]);
    }

    protected function sourceLink(JournalEntry $e): ?array
    {
        return match ($e->reference_type) {
            \App\Models\ExpenseVoucher::class => ['Expense voucher', route('finance.expenses.show', $e->reference_id)],
            \App\Models\PayrollPeriod::class => ['Payroll month', route('payroll.month.show', $e->reference_id)],
            \App\Models\LoanAdvance::class => ['Staff loan', route('payroll.loans.show', $e->reference_id)],
            \App\Models\FixedAsset::class => ['Asset', route('finance.assets.show', $e->reference_id)],
            JournalEntry::class => ['Original entry', route('accounting.journals.show', $e->reference_id)],
            default => null,
        };
    }

    public function journalPost(JournalEntry $entry)
    {
        try { app(AccountingService::class)->postEntry($entry->id); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', 'Posted.');
    }

    public function journalReverse(Request $request, JournalEntry $entry)
    {
        $d = $request->validate(['reason' => 'required|string|max:255']);
        try { $rev = app(AccountingService::class)->reverseEntry($entry->id, $d['reason']); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return redirect()->route('accounting.journals.show', $rev)->with('success', 'Reversed. This reversing entry cancels the original.');
    }

    // ── Reports ───────────────────────────────────────────────────
    public function ledger(Request $request)
    {
        [$from, $to] = $this->range($request);
        $accounts = ChartOfAccount::orderBy('account_code')->get();
        $accountId = (int) ($request->get('account') ?: ChartOfAccount::where('account_code', '1020')->value('id') ?: $accounts->first()?->id);
        $data = $accountId ? $this->reports->ledger($accountId, $from, $to) : null;
        if ($data && $request->get('format') === 'csv') {
            return $this->csv('ledger-' . $data['account']->account_code . '.csv', ['Date', 'Entry', 'Description', 'Debit', 'Credit', 'Balance'],
                collect([[$from, '', 'Opening balance', '', '', $data['opening']]])->merge($data['rows']->map(fn ($r) => [$r->entry_date, $r->entry_no, $r->description . ($r->narration ? ' — ' . $r->narration : ''), $r->debit, $r->credit, $r->balance]))->all());
        }
        return view('accounting.ledger', ['pagetitle' => 'General Ledger', 'accounts' => $accounts, 'data' => $data, 'from' => $from, 'to' => $to]);
    }

    public function trialBalance(Request $request)
    {
        $asAt = $request->get('as_at') ?: now()->toDateString();
        $tb = $this->reports->trialBalance($asAt);
        if ($request->get('format') === 'csv') {
            return $this->csv('trial-balance-' . $asAt . '.csv', ['Code', 'Account', 'Type', 'Debit', 'Credit'],
                array_merge(array_map(fn ($r) => [$r['account']->account_code, $r['account']->account_name, $r['account']->account_type, $r['debit'], $r['credit']], $tb['rows']), [['', 'Total', '', $tb['debit'], $tb['credit']]]));
        }
        return view('accounting.trial-balance', ['pagetitle' => 'Trial Balance', 'tb' => $tb]);
    }

    public function incomeStatement(Request $request)
    {
        [$from, $to] = $this->range($request);
        $is = $this->reports->incomeStatement($from, $to);
        $prev = null;
        if ($request->boolean('compare')) {
            $days = Carbon::parse($from)->diffInDays(Carbon::parse($to));
            $prev = $this->reports->incomeStatement(Carbon::parse($from)->subDays($days + 1)->toDateString(), Carbon::parse($from)->subDay()->toDateString());
        }
        if ($request->get('format') === 'csv') {
            $rows = [['INCOME', '']];
            foreach ($is['income'] as $r) $rows[] = [$r['account']->account_name, $r['amount']];
            $rows[] = ['Total income', $is['total_income']]; $rows[] = ['EXPENSES', ''];
            foreach (array_merge($is['staff'], $is['other'], $is['depreciation']) as $r) $rows[] = [$r['account']->account_name, $r['amount']];
            $rows[] = ['Total expenses', $is['total_expenses']]; $rows[] = ['SURPLUS / (DEFICIT)', $is['surplus']];
            return $this->csv('income-statement.csv', ['Line', 'Amount'], $rows);
        }
        return view('accounting.income-statement', ['pagetitle' => 'Income & Expenditure', 'is' => $is, 'prev' => $prev]);
    }

    public function balanceSheet(Request $request)
    {
        $asAt = $request->get('as_at') ?: now()->toDateString();
        $bs = $this->reports->balanceSheet($asAt);
        if ($request->get('format') === 'csv') {
            $rows = [];
            foreach (['current' => 'CURRENT ASSETS', 'fixed' => 'FIXED ASSETS', 'liabilities' => 'LIABILITIES', 'equity' => 'EQUITY'] as $k => $l) {
                $rows[] = [$l, '']; foreach ($bs[$k] as $r) $rows[] = [$r['account']->account_name, $r['amount']];
            }
            $rows[] = ['Accumulated surplus', $bs['surplus']]; $rows[] = ['Total assets', $bs['total_assets']]; $rows[] = ['Total liabilities + equity', $bs['total_liabilities'] + $bs['total_equity']];
            return $this->csv('balance-sheet-' . $asAt . '.csv', ['Line', 'Amount'], $rows);
        }
        return view('accounting.balance-sheet', ['pagetitle' => 'Balance Sheet', 'bs' => $bs]);
    }

    public function cashFlow(Request $request)
    {
        [$from, $to] = $this->range($request);
        return view('accounting.cash-flow', ['pagetitle' => 'Cash Flow', 'cf' => $this->reports->cashFlow($from, $to)]);
    }

    // ── Settings & catch-up ───────────────────────────────────────
    public function saveSettings(Request $request)
    {
        $d = $request->validate(['books_closed_until' => 'nullable|date|before_or_equal:today', 'financial_year_start' => 'required|date_format:m-d',
                                 'bank_account' => 'required|exists:chart_of_accounts,account_code', 'cash_account' => 'required|exists:chart_of_accounts,account_code']);
        FinanceSettings::put('accounting', $d, (int) auth()->id());
        return back()->with('success', 'Accounting settings saved.' . (!empty($d['books_closed_until']) ? ' Nothing can now be posted on or before ' . Carbon::parse($d['books_closed_until'])->format('d M Y') . '.' : ''));
    }

    public function syncFees()
    {
        try { $r = app(LedgerPoster::class)->syncFees(); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', $r['days'] ? "Posted ₦" . number_format($r['amount'], 2) . " of fee receipts across {$r['days']} day(s)." : 'Fee receipts are up to date.');
    }

    public function catchUp()
    {
        try { $r = app(LedgerPoster::class)->catchUp(); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', "Posted: {$r['payroll']} payroll month(s), {$r['payouts']} salary payment(s), {$r['expenses']} expense(s), {$r['fees']} day(s) of fees.");
    }

    protected function csv(string $name, array $head, array $rows)
    {
        return response()->streamDownload(function () use ($head, $rows) {
            $o = fopen('php://output', 'w'); fputcsv($o, $head); foreach ($rows as $r) fputcsv($o, $r); fclose($o);
        }, $name, ['Content-Type' => 'text/csv']);
    }
}
