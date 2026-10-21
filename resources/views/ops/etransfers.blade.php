@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="e-Transfers" icon="ri-exchange-dollar-line" subtitle="Interac e-Transfers waiting to be matched. When one arrives in the inbox, find its reference (in the message) and mark it received: the delivery goes ahead straight away." :back="route('ops.dashboard')" back-label="Operations">
        <x-slot:pills>
            <span class="cb-meta-pill">Customers send to {{ $to['email'] ?: 'not set' }}</span>
            <span class="cb-meta-pill">Held {{ $to['hold_hours'] }} hours</span>
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')
    @unless($ready)
        <div class="alert alert-warning">Interac e-Transfer is not switched on. Fill it in and switch it on under <a href="{{ route('admin.payment-gateways.index') }}">Payment Gateways</a> to offer it at checkout.</div>
    @endunless

    <x-cb.card title="Waiting" icon="ri-time-line" :count="count($rows)" :flush="true" class="mb-3">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Reference</th><th>Customer</th><th>For</th><th class="text-end">Amount</th><th>Started</th>@if($canConfirm)<th style="min-width:330px"></th>@endif</tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><span class="fw-semibold font-monospace">{{ $r->reference }}</span>
                        @if($r->customer_sent_at)<div><span class="badge bg-info-subtle text-info">Customer says sent {{ \Illuminate\Support\Carbon::parse($r->customer_sent_at)->diffForHumans() }}</span></div>@endif
                        @if($r->sender_name)<div class="small text-muted">From: {{ $r->sender_name }}</div>@endif
                        @if($r->status === 'abandoned')<div><span class="badge bg-warning-subtle text-warning">Booking closed: will go to their wallet</span></div>@endif
                    </td>
                    <td>{{ $r->name }}<div class="small text-muted">{{ $r->email }}@if($r->phone_number) · {{ \App\Support\Phone::display($r->phone_number) }}@endif</div></td>
                    <td class="small">{{ $r->agreement ? 'Delivery '.$r->agreement : 'Wallet top-up' }}</td>
                    <td class="text-end fw-semibold">{{ Money::format((int) $r->amount, true) }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r->created_at)->diffForHumans() }}</td>
                    @if($canConfirm)
                    <td>
                        <form method="POST" action="{{ route('ops.etransfer.confirm', $r->id) }}" class="d-flex gap-1 mb-1">@csrf
                            <div class="input-group input-group-sm" style="max-width:9rem"><span class="input-group-text">{{ Money::symbol() }}</span><input name="amount" class="form-control" value="{{ number_format(((int) $r->amount) / 100, 2, '.', '') }}" required></div>
                            <input name="interac_ref" class="form-control form-control-sm" placeholder="Interac ref (optional)" maxlength="60">
                            <button class="btn btn-sm btn-success text-nowrap">Received</button>
                        </form>
                        <form method="POST" action="{{ route('ops.etransfer.reject', $r->id) }}" class="d-flex gap-1" onsubmit="return confirm('Reject this payment? The customer will be told.')">@csrf
                            <input name="reason" class="form-control form-control-sm" placeholder="Why (e.g. wrong amount)" maxlength="300" required>
                            <button class="btn btn-sm btn-outline-danger text-nowrap">Reject</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Nothing waiting.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    <x-cb.card title="Recently handled" icon="ri-history-line" :count="count($recent)" :flush="true">
        <table class="table table-sm align-middle mb-0"><tbody>
        @forelse($recent as $r)
            <tr>
                <td class="font-monospace small">{{ $r->reference }}</td><td>{{ $r->name }}</td><td class="text-end">{{ Money::format((int) $r->amount, true) }}</td>
                <td>@if($r->confirmed_at)<span class="badge bg-success-subtle text-success">{{ $r->applied_to === 'wallet' ? 'To wallet' : 'Received' }}</span>@else<span class="badge bg-danger-subtle text-danger">Rejected</span> <span class="small text-muted">{{ $r->reason }}</span>@endif</td>
                <td class="small text-muted">{{ $r->staff }} · {{ \Illuminate\Support\Carbon::parse($r->confirmed_at ?? $r->rejected_at)->diffForHumans() }}@if($r->interac_ref) · {{ $r->interac_ref }}@endif</td>
            </tr>
        @empty
            <tr><td class="text-center text-muted py-3">Nothing yet.</td></tr>
        @endforelse
        </tbody></table>
    </x-cb.card>
</div></div></div>
@endsection
