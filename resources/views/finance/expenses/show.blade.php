{{-- resources/views/finance/expenses/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); [$lbl, $cls] = $v->label(); $M = \App\Models\ExpenseVoucher::METHODS; $me = (int) auth()->id(); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$v->voucher_no . ' · ' . $m($v->amount)" icon="ri-receipt-line" :subtitle="$v->payee_name . ' — ' . $v->description" :back="route('finance.expenses')" back-label="Expenses">
        <x-slot:actions>
            @if($v->receipt_path)<a href="{{ route('finance.expenses.receipt', $v) }}" target="_blank" class="cb-hero-btn"><i class="ri-attachment-2"></i>Receipt</a>@endif
            <button type="button" class="cb-hero-btn" onclick="window.print()"><i class="ri-printer-line"></i>Print</button>
            @can('Create expenses')
                @if(in_array($v->status, ['draft', 'rejected']))<a href="{{ route('finance.expenses.edit', $v) }}" class="cb-hero-btn"><i class="ri-edit-line"></i>Edit</a>
                    <form method="POST" action="{{ route('finance.expenses.submit', $v) }}" class="d-inline">@csrf<button class="cb-hero-btn"><i class="ri-send-plane-line"></i>Submit</button></form>@endif
                @if(!in_array($v->status, ['paid', 'paying', 'cancelled']))<form method="POST" action="{{ route('finance.expenses.cancel', $v) }}" class="d-inline" onsubmit="return confirm('Cancel this voucher?')">@csrf<button class="cb-hero-btn"><i class="ri-close-line"></i>Cancel</button></form>@endif
            @endcan
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill"><span class="status-pill {{ $cls }}">{{ $lbl }}</span></span>
            <span class="cb-meta-pill"><i class="ri-price-tag-3-line"></i>{{ $v->category->name ?? 'No category' }}</span>
            <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $v->expense_date->format('d M Y') }}</span>
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($v->rejection_reason && $v->status === 'rejected')<div class="cb-banner warning"><i class="ri-close-circle-line"></i><div>Declined: {{ $v->rejection_reason }}</div></div>@endif
    @if($budget && !$budget['ok'])<div class="cb-banner warning"><i class="ri-scales-3-line"></i><div>{{ $budget['message'] }}</div></div>@elseif($budget && isset($budget['left']))<div class="cb-banner info"><i class="ri-scales-3-line"></i><div>Within budget — ₦{{ number_format($budget['left'] - $v->amount, 2) }} will remain on this line.</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Voucher" icon="ri-file-list-3-line">
                <div class="row g-3 small">
                    <div class="col-md-4"><div class="text-muted">Payee</div><b>{{ $v->payee_name }}</b>@if($v->vendor)<div>{{ $v->vendor->phone }}</div>@endif @if($staffName)<div>Staff refund: {{ $staffName }}</div>@endif</div>
                    <div class="col-md-4"><div class="text-muted">Amount</div><b class="fs-5">{{ $m($v->amount) }}</b></div>
                    <div class="col-md-4"><div class="text-muted">Payment</div><b>{{ $M[$v->payment_method] ?? $v->payment_method }}</b>@if($v->reference)<div>Ref: {{ $v->reference }}</div>@endif</div>
                    <div class="col-12"><div class="text-muted">Description</div>{{ $v->description }}</div>
                    @if($v->purchaseRequest)<div class="col-md-6"><div class="text-muted">Purchase request</div><a href="{{ route('finance.purchases.show', $v->purchaseRequest) }}">{{ $v->purchaseRequest->pr_no }}</a></div>@endif
                    @if($v->asset)<div class="col-md-6"><div class="text-muted">Asset</div><a href="{{ route('finance.assets.show', $v->asset) }}">{{ $v->asset->asset_tag }}</a></div>@elseif($v->capitalise)<div class="col-md-6"><div class="text-muted">Asset</div>Will be added to the register when paid</div>@endif
                    @if($journal)<div class="col-md-6"><div class="text-muted">Ledger</div><a href="{{ route('accounting.journals.show', $journal) }}">{{ $journal->entry_no }}</a></div>@endif
                </div>
            </x-cb.card>
            <x-cb.card title="Trail" icon="ri-route-line">
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="ri-edit-line text-muted"></i> Raised by <b>{{ $v->requester->name ?? '—' }}</b> · {{ $v->created_at->format('d M Y H:i') }}</li>
                    @if($v->approver)<li class="mb-2"><i class="ri-check-line text-success"></i> Approved by <b>{{ $v->approver->name }}</b> · {{ $v->approved_at?->format('d M Y H:i') }}</li>@endif
                    @if($v->needs_second_approval)<li class="mb-2"><i class="ri-check-double-line {{ $v->secondApprover ? 'text-success' : 'text-muted' }}"></i> Second approval {!! $v->secondApprover ? 'by <b>' . e($v->secondApprover->name) . '</b> · ' . $v->second_approved_at?->format('d M Y H:i') : '— waiting' !!}</li>@endif
                    @if($v->paid_at && $v->status === 'paid')<li><i class="ri-bank-card-line text-success"></i> Paid by <b>{{ $v->payer->name ?? '—' }}</b> · {{ $v->paid_at->format('d M Y') }}{{ $v->paid_from ? ' from account ' . $v->paid_from : '' }}</li>@endif
                </ul>
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            @if($v->status === 'submitted')
                @can('Approve expenses')
                <x-cb.card title="Approve" icon="ri-scales-line">
                    @if((int) $v->requested_by === $me)
                        <div class="small text-muted">You raised this voucher, so someone else must approve it.</div>
                    @elseif($v->approved_by && (int) $v->approved_by === $me)
                        <div class="small text-muted">You gave the first approval. A different approver must give the second.</div>
                    @else
                        <form method="POST" action="{{ route('finance.expenses.approve', $v) }}" class="mb-2">@csrf<button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-check-line"></i>{{ $v->approved_by ? 'Give second approval' : 'Approve' }}</button></form>
                        <form method="POST" action="{{ route('finance.expenses.reject', $v) }}" class="d-flex gap-2">@csrf<input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" required><button class="action-btn btn-open">Decline</button></form>
                    @endif
                </x-cb.card>
                @endcan
            @endif
            @if($v->status === 'approved')
                @can('Pay expenses')
                <x-cb.card title="Record payment" icon="ri-bank-card-line">
                    <form method="POST" action="{{ route('finance.expenses.pay', $v) }}" class="small">@csrf
                        <select name="payment_method" class="form-select form-select-sm mb-2">@foreach($M as $k => $l)@if($k !== 'paystack' || $v->staff_id)<option value="{{ $k }}" @selected($v->payment_method === $k)>{{ $l }}</option>@endif @endforeach</select>
                        <select name="paid_from" class="form-select form-select-sm mb-2">@foreach($banks as $b)<option value="{{ $b->account_code }}">{{ $b->account_code }} · {{ $b->account_name }}</option>@endforeach</select>
                        <input type="text" name="reference" class="form-control form-control-sm mb-2" placeholder="Transfer / cheque no." value="{{ $v->reference }}">
                        <input type="date" name="paid_on" class="form-control form-control-sm mb-2" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
                        <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-check-double-line"></i>Mark paid · {{ $m($v->amount) }}</button>
                    </form>
                </x-cb.card>
                @endcan
            @endif
            @if($v->status === 'paying')<div class="cb-banner info"><i class="ri-bank-card-line"></i><div>Paystack payout {{ $v->reference }} is waiting to be released or confirmed.</div></div>@endif
        </div>
    </div>
</div>
</div>
</div>
@endsection
