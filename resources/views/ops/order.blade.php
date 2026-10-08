@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Order '.$o['order']['order_number']" icon="ri-shopping-bag-3-line" :subtitle="($o['order']['customer']['name'] ?? '').' · '.($o['order']['customer']['email'] ?? '')" :back="route('ops.orders')" back-label="Orders">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ $naira($o['order']['total']) }}</span>
            <span class="cb-meta-pill">Payment: {{ $o['order']['payment_status'] }}</span>
            @if($o['shipment'])<span class="cb-meta-pill">Delivery: {{ str_replace('_', ' ', $o['shipment']['status']) }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Delivery timeline" icon="ri-time-line" :flush="true">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Event</th><th>By</th><th>When</th></tr></thead>
                    <tbody>
                    @forelse($o['timeline'] as $e)
                        <tr><td>{{ $e['seq'] }}</td><td>{{ str_replace('_', ' ', $e['type']) }}@if($e['to_status']) <span class="text-muted small">→ {{ str_replace('_', ' ', $e['to_status']) }}</span>@endif</td><td>{{ $e['actor_type'] }}</td><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($e['created_at'])->format('d M H:i') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No delivery events yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-cb.card>
        </div>
        <div class="col-lg-5">
            <x-cb.card title="Provider" icon="ri-truck-line">
                {{ $o['shipment']['provider'] ?? 'Not assigned yet' }}
                @if($o['shipment'])<div class="small text-muted">Tracking {{ $o['shipment']['tracking_code'] }}</div>@endif
            </x-cb.card>
            <div class="mt-3">@include('partials.proofs')</div>
            <x-cb.card title="Escrow" icon="ri-safe-2-line" class="mt-3">
                @if($o['escrow'])
                    Held {{ $naira($o['escrow']['amount']) }} · {{ $o['escrow']['status'] }}
                    <div class="small text-muted">Released {{ $naira($o['escrow']['released_amount']) }} · Refunded {{ $naira($o['escrow']['refunded_amount']) }}</div>
                @else <span class="text-muted">No escrow on this order.</span> @endif
            </x-cb.card>
            <x-cb.card title="Refunds" icon="ri-refund-2-line" class="mt-3">
                @forelse($o['refunds'] as $r)
                    <div class="d-flex justify-content-between"><span>{{ $naira($r['amount']) }} <span class="text-muted small">{{ $r['reason_code'] }}</span></span><span class="badge bg-light text-dark">{{ $r['status'] }}</span></div>
                @empty <span class="text-muted">None.</span> @endforelse
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
