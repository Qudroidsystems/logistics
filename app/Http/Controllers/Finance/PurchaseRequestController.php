<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;

/** Anyone can request items; approvers decide; the bursary turns approved requests into expense vouchers. */
class PurchaseRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Approve purchase requests')->only(['approve', 'reject']);
        $this->middleware('permission:Create expenses')->only(['received']);
    }

    protected function canSeeAll(): bool
    {
        return auth()->user()->canany(['Approve purchase requests', 'Create expenses', 'View financial reports']);
    }

    public function index(Request $request)
    {
        $q = PurchaseRequest::with(['requester:id,name', 'vendor:id,name', 'category:id,name'])
            ->when(!$this->canSeeAll() || $request->get('scope') === 'mine', fn ($x) => $x->where('requested_by', auth()->id()))
            ->when($request->filled('status'), fn ($x) => $x->where('status', $request->status));
        return view('finance.purchases.index', [
            'pagetitle' => 'Purchase Requests', 'requests' => $q->latest()->paginate(25)->withQueryString(),
            'categories' => ExpenseCategory::active()->orderBy('name')->get(), 'vendors' => Vendor::active()->orderBy('name')->get(),
            'seeAll' => $this->canSeeAll(),
            'counts' => PurchaseRequest::groupBy('status')->selectRaw('status, COUNT(*) n, SUM(estimated_total) amt')->get()->keyBy('status'),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'title' => 'required|string|max:180', 'department' => 'nullable|string|max:100', 'needed_by' => 'nullable|date|after_or_equal:today',
            'expense_category_id' => 'nullable|integer|exists:expense_categories,id', 'vendor_id' => 'nullable|integer|exists:vendors,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1', 'items.*.description' => 'required|string|max:200', 'items.*.qty' => 'required|numeric|min:0.01', 'items.*.unit_price' => 'required|numeric|min:0',
        ]);
        $items = array_values(array_map(fn ($i) => ['description' => $i['description'], 'qty' => (float) $i['qty'], 'unit_price' => (float) $i['unit_price'],
                                                    'total' => round((float) $i['qty'] * (float) $i['unit_price'], 2)], $d['items']));
        $pr = PurchaseRequest::create(collect($d)->except('items')->all() + [
            'pr_no' => 'PR-' . now()->format('ym') . '-' . str_pad((string) (PurchaseRequest::where('pr_no', 'like', 'PR-' . now()->format('ym') . '-%')->count() + 1), 4, '0', STR_PAD_LEFT),
            'requested_by' => auth()->id(), 'items' => $items, 'estimated_total' => array_sum(array_column($items, 'total')), 'status' => 'submitted',
        ]);
        if (class_exists(\App\Services\Messaging\PortalNotifier::class)) {
            try {
                $ids = \App\Models\User::permission('Approve purchase requests')->pluck('id')->all();
                if ($ids) \App\Services\Messaging\PortalNotifier::toUsers($ids, 'Purchase request', $pr->pr_no . ': ' . $pr->title . ' (₦' . number_format($pr->estimated_total, 2) . ')', route('finance.purchases.show', $pr), 'system');
            } catch (\Throwable $e) {}
        }
        return redirect()->route('finance.purchases.show', $pr)->with('success', 'Request submitted.');
    }

    public function show(PurchaseRequest $purchase)
    {
        abort_unless($this->canSeeAll() || (int) $purchase->requested_by === (int) auth()->id(), 403);
        $purchase->load(['requester:id,name', 'approver:id,name', 'vendor', 'category', 'voucher']);
        return view('finance.purchases.show', ['pagetitle' => $purchase->pr_no, 'pr' => $purchase]);
    }

    public function approve(PurchaseRequest $purchase)
    {
        if ($purchase->status !== 'submitted') return back()->with('error', 'Already decided.');
        if ((int) $purchase->requested_by === (int) auth()->id()) return back()->with('error', 'You cannot approve your own request.');
        $purchase->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->tell($purchase, 'Purchase request approved', $purchase->pr_no . ' was approved.');
        return back()->with('success', 'Approved. The bursary can now raise the expense voucher.');
    }

    public function reject(Request $request, PurchaseRequest $purchase)
    {
        $d = $request->validate(['reason' => 'required|string|max:255']);
        if ($purchase->status !== 'submitted') return back()->with('error', 'Already decided.');
        $purchase->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now(), 'rejection_reason' => $d['reason']]);
        $this->tell($purchase, 'Purchase request declined', $purchase->pr_no . ': ' . $d['reason']);
        return back()->with('success', 'Declined.');
    }

    public function received(PurchaseRequest $purchase)
    {
        $purchase->update(['status' => 'received', 'received_at' => now()]);
        $this->tell($purchase, 'Items received', $purchase->pr_no . ' has been received by the store.');
        return back()->with('success', 'Marked as received.');
    }

    public function cancel(PurchaseRequest $purchase)
    {
        abort_unless((int) $purchase->requested_by === (int) auth()->id() && $purchase->status === 'submitted', 403);
        $purchase->update(['status' => 'closed', 'rejection_reason' => 'Withdrawn by requester']);
        return back()->with('success', 'Request withdrawn.');
    }

    protected function tell(PurchaseRequest $pr, string $title, string $body): void
    {
        if (!class_exists(\App\Services\Messaging\PortalNotifier::class)) return;
        try { \App\Services\Messaging\PortalNotifier::toUsers([(int) $pr->requested_by], $title, $body, route('finance.purchases.show', $pr), 'system'); } catch (\Throwable $e) {}
    }
}
