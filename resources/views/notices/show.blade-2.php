{{-- resources/views/finance/purchases/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); [$lbl, $cls] = $pr->label(); $mine = (int) $pr->requested_by === (int) auth()->id(); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$pr->title" icon="ri-shopping-cart-2-line" :subtitle="$pr->pr_no . ' · ' . ($pr->requester->name ?? '') . ($pr->department ? ' · ' . $pr->department : '')" :back="route('finance.purchases')" back-label="Purchase requests">
        <x-slot:actions>
            <button type="button" class="cb-hero-btn" onclick="window.print()"><i class="ri-printer-line"></i>Print</button>
            @if($mine && $pr->status === 'submitted')<form method="POST" action="{{ route('finance.purchases.cancel', $pr) }}" class="d-inline">@csrf<button class="cb-hero-btn"><i class="ri-close-line"></i>Withdraw</button></form>@endif
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill"><span class="status-pill {{ $cls }}">{{ $lbl }}</span></span>
            @if($pr->needed_by)<span class="cb-meta-pill"><i class="ri-calendar-line"></i>Needed by {{ $pr->needed_by->format('d M Y') }}</span>@endif
            @if($pr->approver)<span class="cb-meta-pill"><i class="ri-user-line"></i>{{ $pr->status === 'rejected' ? 'Declined' : 'Approved' }} by {{ $pr->approver->name }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($pr->rejection_reason)<div class="cb-banner warning"><i class="ri-close-circle-line"></i><div>{{ $pr->rejection_reason }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Items" icon="ri-list-check-2" :count="count($pr->items)" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Total</th></tr></thead>
                    <tbody>@foreach($pr->items as $i)<tr><td>{{ $i['description'] }}</td><td class="text-end">{{ $i['qty'] }}</td><td class="text-end">{{ $m($i['unit_price']) }}</td><td class="text-end">{{ $m($i['total']) }}</td></tr>@endforeach</tbody>
                    <tfoot><tr><th colspan="3" class="text-end">Estimated total</th><th class="text-end">{{ $m($pr->estimated_total) }}</th></tr></tfoot>
                </table></div>
            </x-cb.card>
            @if($pr->notes)<x-cb.card title="Why it's needed" icon="ri-chat-quote-line"><div class="small">{{ $pr->notes }}</div></x-cb.card>@endif
        </div>
        <div class="col-xl-4">
            <x-cb.card title="Details" icon="ri-information-line">
                <div class="small">Category: <b>{{ $pr->category->name ?? '—' }}</b><br>Vendor: <b>{{ $pr->vendor->name ?? '—' }}</b><br>Raised: {{ $pr->created_at->format('d M Y H:i') }}
                    @if($pr->voucher)<br>Voucher: <a href="{{ route('finance.expenses.show', $pr->voucher) }}">{{ $pr->voucher->voucher_no }}</a> ({{ $pr->voucher->label()[0] }})@endif
                    @if($pr->received_at)<br>Received: {{ $pr->received_at->format('d M Y') }}@endif</div>
            </x-cb.card>
            @if($pr->status === 'submitted')
                @can('Approve purchase requests')
                    @if(!$mine)
                    <x-cb.card title="Decide" icon="ri-scales-line">
                        <form method="POST" action="{{ route('finance.purchases.approve', $pr) }}" class="mb-2">@csrf<button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-check-line"></i>Approve</button></form>
                        <form method="POST" action="{{ route('finance.purchases.reject', $pr) }}" class="d-flex gap-2">@csrf<input name="reason" class="form-control form-control-sm" placeholder="Reason" required><button class="action-btn btn-open">Decline</button></form>
                    </x-cb.card>
                    @endif
                @endcan
            @endif
            @can('Create expenses')
                @if($pr->status === 'approved' && !$pr->expense_voucher_id)<a href="{{ route('finance.expenses.create', ['pr' => $pr->id]) }}" class="action-btn btn-primary-cb w-100 justify-content-center mb-2"><i class="ri-receipt-line"></i>Raise expense voucher</a>@endif
                @if(in_array($pr->status, ['approved', 'ordered']))<form method="POST" action="{{ route('finance.purchases.received', $pr) }}">@csrf<button class="action-btn btn-open w-100 justify-content-center"><i class="ri-inbox-archive-line"></i>Mark items received</button></form>@endif
            @endcan
        </div>
    </div>
</div>
</div>
</div>
@endsection
