<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use App\Models\ExpenseVoucher;
use App\Models\FixedAsset;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use App\Services\Finance\BudgetService;
use App\Services\Finance\ExpenseService;
use App\Support\FinanceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Expense vouchers, vendors and expense categories. */
class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $svc)
    {
        $this->middleware('permission:Create expenses|Approve expenses|Pay expenses|View financial reports')->only(['index', 'show', 'receipt', 'export']);
        $this->middleware('permission:Create expenses')->only(['create', 'store', 'edit', 'update', 'submit', 'cancel']);
        $this->middleware('permission:Approve expenses')->only(['approve', 'reject']);
        $this->middleware('permission:Pay expenses')->only(['pay']);
        $this->middleware('permission:Create expenses|Manage chart of accounts')->only(['vendors', 'saveVendor']);
        $this->middleware('permission:Manage chart of accounts|Manage budgets')->only(['categories', 'saveCategory', 'saveExpenseSettings']);
    }

    protected function query(Request $r)
    {
        return ExpenseVoucher::with(['category:id,name', 'vendor:id,name', 'requester:id,name'])
            ->when($r->filled('status'), fn ($q) => $r->status === 'mine' ? $q->where('requested_by', auth()->id()) : $q->where('status', $r->status))
            ->when($r->filled('category'), fn ($q) => $q->where('expense_category_id', $r->category))
            ->when($r->filled('month'), fn ($q) => $q->where('expense_date', 'like', $r->month . '%'))
            ->when($r->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('voucher_no', 'like', "%{$r->q}%")->orWhere('payee_name', 'like', "%{$r->q}%")->orWhere('description', 'like', "%{$r->q}%")));
    }

    public function index(Request $request)
    {
        $vouchers = $this->query($request)->latest('expense_date')->latest('id')->paginate(25)->withQueryString();
        $month = now()->format('Y-m');
        $byCat = ExpenseVoucher::where('status', 'paid')->where('expense_date', 'like', $month . '%')->groupBy('expense_category_id')
            ->selectRaw('expense_category_id, SUM(amount) amt')->orderByDesc('amt')->limit(6)->pluck('amt', 'expense_category_id');
        return view('finance.expenses.index', [
            'pagetitle' => 'Expenses', 'vouchers' => $vouchers, 'categories' => ExpenseCategory::active()->orderBy('name')->get(),
            'catNames' => ExpenseCategory::pluck('name', 'id'), 'byCat' => $byCat,
            'stats' => [
                'month' => ExpenseVoucher::where('status', 'paid')->where('expense_date', 'like', $month . '%')->sum('amount'),
                'last_month' => ExpenseVoucher::where('status', 'paid')->where('expense_date', 'like', now()->subMonthNoOverflow()->format('Y-m') . '%')->sum('amount'),
                'awaiting' => ExpenseVoucher::where('status', 'submitted')->count(),
                'to_pay' => ExpenseVoucher::where('status', 'approved')->sum('amount'),
            ],
        ]);
    }

    protected function formData(?ExpenseVoucher $v = null, ?PurchaseRequest $pr = null): array
    {
        return [
            'v' => $v, 'pr' => $pr, 'categories' => ExpenseCategory::active()->orderBy('name')->get(), 'vendors' => Vendor::active()->orderBy('name')->get(),
            'staff' => DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->orderBy('u.name')->get(['s.id', 'u.name']),
            'banks' => ChartOfAccount::where('account_type', 'asset')->where(fn ($q) => $q->where('is_bank_account', true)->orWhereIn('account_code', ['1010']))->orderBy('account_code')->get(),
            'settings' => $this->svc->settings(),
        ];
    }

    public function create(Request $request)
    {
        $pr = $request->filled('pr') ? PurchaseRequest::where('status', 'approved')->find($request->pr) : null;
        return view('finance.expenses.form', ['pagetitle' => 'New expense'] + $this->formData(null, $pr));
    }

    protected function validated(Request $request): array
    {
        $d = $request->validate([
            'expense_date' => 'required|date|before_or_equal:today', 'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'vendor_id' => 'nullable|integer|exists:vendors,id', 'staff_id' => 'nullable|integer|exists:staffbioinfo,id',
            'payee_name' => 'required|string|max:150', 'description' => 'required|string|max:500', 'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:' . implode(',', array_keys(ExpenseVoucher::METHODS)), 'reference' => 'nullable|string|max:80',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120', 'capitalise' => 'nullable|boolean',
            'asset_account' => 'nullable|in:' . implode(',', array_keys(FixedAsset::CLASSES)), 'asset_life_months' => 'nullable|integer|min:1|max:600',
            'purchase_request_id' => 'nullable|integer|exists:purchase_requests,id',
        ]);
        if ($request->hasFile('receipt')) $d['receipt_path'] = $request->file('receipt')->store('expense-receipts', 'local');
        unset($d['receipt']);
        $d['capitalise'] = $request->boolean('capitalise');
        return $d;
    }

    public function store(Request $request)
    {
        $d = $this->validated($request);
        try {
            $v = $this->svc->create($d, (int) auth()->id(), $request->input('action') !== 'draft');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        if ($v->purchase_request_id) PurchaseRequest::where('id', $v->purchase_request_id)->update(['expense_voucher_id' => $v->id]);
        return redirect()->route('finance.expenses.show', $v)->with('success', $v->status === 'draft' ? 'Saved as draft.' : 'Submitted for approval.');
    }

    public function show(ExpenseVoucher $voucher)
    {
        $voucher->load(['category', 'vendor', 'requester:id,name', 'approver:id,name', 'secondApprover:id,name', 'payer:id,name', 'purchaseRequest', 'asset']);
        $staffName = $voucher->staff_id ? DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.id', $voucher->staff_id)->value('u.name') : null;
        return view('finance.expenses.show', [
            'pagetitle' => $voucher->voucher_no, 'v' => $voucher, 'staffName' => $staffName,
            'budget' => in_array($voucher->status, ['draft', 'submitted', 'approved', 'rejected']) ? app(BudgetService::class)->checkVoucher($voucher) : null,
            'banks' => $this->formData()['banks'],
            'journal' => class_exists(\App\Models\JournalEntry::class) ? \App\Models\JournalEntry::where('reference_type', ExpenseVoucher::class)->where('reference_id', $voucher->id)->first() : null,
        ]);
    }

    public function edit(ExpenseVoucher $voucher)
    {
        abort_unless(in_array($voucher->status, ['draft', 'rejected']), 403, 'This voucher can no longer be edited.');
        return view('finance.expenses.form', ['pagetitle' => 'Edit ' . $voucher->voucher_no] + $this->formData($voucher));
    }

    public function update(Request $request, ExpenseVoucher $voucher)
    {
        $d = $this->validated($request);
        try {
            $this->svc->update($voucher, $d);
            if ($request->input('action') !== 'draft') $this->svc->submit($voucher->fresh(), (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('finance.expenses.show', $voucher)->with('success', 'Saved.');
    }

    public function submit(ExpenseVoucher $voucher) { return $this->run(fn () => $this->svc->submit($voucher, (int) auth()->id()), 'Submitted for approval.'); }
    public function approve(ExpenseVoucher $voucher)
    {
        try { $msg = $this->svc->approve($voucher, (int) auth()->id()); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', $msg);
    }
    public function reject(Request $request, ExpenseVoucher $voucher)
    {
        $d = $request->validate(['reason' => 'required|string|max:255']);
        return $this->run(fn () => $this->svc->reject($voucher, (int) auth()->id(), $d['reason']), 'Declined and returned to the requester.');
    }
    public function cancel(ExpenseVoucher $voucher) { return $this->run(fn () => $this->svc->cancel($voucher), 'Cancelled.'); }

    public function pay(Request $request, ExpenseVoucher $voucher)
    {
        $d = $request->validate(['payment_method' => 'required|in:' . implode(',', array_keys(ExpenseVoucher::METHODS)), 'paid_from' => 'nullable|string|max:10',
                                 'reference' => 'nullable|string|max:80', 'paid_on' => 'nullable|date|before_or_equal:today']);
        try { $batch = $this->svc->pay($voucher, (int) auth()->id(), $d); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return $batch ? redirect()->route('payroll.payouts.show', $batch)->with('success', 'Payout prepared — another authorised person must release it.')
                      : back()->with('success', 'Payment recorded and posted to the ledger.');
    }

    public function receipt(ExpenseVoucher $voucher)
    {
        abort_unless($voucher->receipt_path && Storage::disk('local')->exists($voucher->receipt_path), 404);
        return Storage::disk('local')->response($voucher->receipt_path);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->orderBy('expense_date')->get();
        return response()->streamDownload(function () use ($rows) {
            $o = fopen('php://output', 'w');
            fputcsv($o, ['Voucher', 'Date', 'Category', 'Payee', 'Description', 'Amount', 'Method', 'Reference', 'Status', 'Raised by']);
            foreach ($rows as $v) fputcsv($o, [$v->voucher_no, $v->expense_date->toDateString(), $v->category->name ?? '', $v->payee_name, $v->description,
                number_format($v->amount, 2, '.', ''), ExpenseVoucher::METHODS[$v->payment_method] ?? $v->payment_method, $v->reference, $v->status, $v->requester->name ?? '']);
            fclose($o);
        }, 'expenses-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ── Vendors ───────────────────────────────────────────────────
    public function vendors(Request $request)
    {
        $vendors = Vendor::withCount('vouchers')->withSum(['vouchers' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%'))->orderBy('name')->get();
        return view('finance.expenses.vendors', ['pagetitle' => 'Vendors & Suppliers', 'vendors' => $vendors, 'edit' => $request->filled('edit') ? Vendor::find($request->edit) : null]);
    }

    public function saveVendor(Request $request)
    {
        $d = $request->validate([
            'id' => 'nullable|integer|exists:vendors,id', 'name' => 'required|string|max:150', 'contact_person' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30', 'email' => 'nullable|email|max:120', 'address' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:120', 'account_number' => 'nullable|digits_between:10,10', 'account_name' => 'nullable|string|max:150',
            'tin' => 'nullable|string|max:30', 'category' => 'nullable|string|max:60',
        ]);
        $d['is_active'] = $request->boolean('is_active', true);
        Vendor::updateOrCreate(['id' => $d['id'] ?? null], collect($d)->except('id')->all());
        return redirect()->route('finance.vendors')->with('success', 'Vendor saved.');
    }

    // ── Expense categories ───────────────────────────────────────
    public function categories()
    {
        return view('finance.expenses.categories', [
            'pagetitle' => 'Expense Categories', 'categories' => ExpenseCategory::with('account')->orderBy('code')->get(),
            'accounts' => ChartOfAccount::where('account_type', 'expense')->where('is_active', true)->orderBy('account_code')->get(),
            'settings' => $this->svc->settings(),
        ]);
    }

    public function saveCategory(Request $request)
    {
        $d = $request->validate(['id' => 'nullable|integer|exists:expense_categories,id', 'code' => 'required|string|max:50', 'name' => 'required|string|max:150',
                                 'account_id' => 'nullable|integer|exists:chart_of_accounts,id', 'description' => 'nullable|string|max:255']);
        $d['is_active'] = $request->boolean('is_active', true);
        ExpenseCategory::updateOrCreate(['id' => $d['id'] ?? null], collect($d)->except('id')->all());
        return back()->with('success', 'Category saved.');
    }

    public function saveExpenseSettings(Request $request)
    {
        $d = $request->validate(['second_approval_above' => 'required|numeric|min:0', 'receipt_required_above' => 'required|numeric|min:0']);
        FinanceSettings::put('expenses', $d + ['block_over_budget' => $request->boolean('block_over_budget')], (int) auth()->id());
        return back()->with('success', 'Expense controls saved.');
    }

    protected function run(callable $fn, string $ok)
    {
        try { $fn(); } catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', $ok);
    }
}
