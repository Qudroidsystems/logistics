@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$m->display_name" icon="ri-store-3-line" :subtitle="'Merchant ID '.$m->merchant_code">
        <x-slot:pills><span class="cb-meta-pill">{{ $m->status }}</span></x-slot:pills>
    </x-cb.hero>
    @include('merchant._flash')

    @if($m->status !== 'verified')
    <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>This account is {{ $m->status }}. New deliveries cannot be booked until it is verified.</div></div>
    @endif
    @if(! $hasKey || ! $hasHook)
    <x-cb.card title="Finish connecting" icon="ri-plug-line" class="mb-3">
        <ol class="small mb-2">
            <li>@if($hasKey)<span class="text-success">Done:</span> @endif Create a test API key.</li>
            <li>@if($hasHook)<span class="text-success">Done:</span> @endif Add the address where we send delivery updates.</li>
            <li>Try a quote and a test delivery with the <a href="{{ route('developers.partner') }}" target="_blank" rel="noopener">API reference</a>.</li>
        </ol>
        <a href="{{ route('merchant.api') }}" class="btn btn-sm btn-primary">API and webhooks</a>
    </x-cb.card>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Orders" :value="$stats['orders']" icon="ri-shopping-bag-3-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="On the road" :value="$stats['on_road']" icon="ri-truck-line" accent="sky" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Delivered" :value="$stats['done']" icon="ri-check-double-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Failed" :value="$stats['failed']" icon="ri-error-warning-line" accent="rose" /></div>
    </div>

    <x-cb.card title="Recent orders" icon="ri-list-check" :count="count($orders)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th>Your order ID</th><th>Delivery</th><th>Payment</th><th class="text-end">Total</th><th>Placed</th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr>
                    <td class="fw-semibold">{{ $o->order_number }}</td>
                    <td class="small">{{ $o->external_order_id ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $o->delivery ?? 'not started') }}</span></td>
                    <td><span class="badge bg-{{ $o->payment_status === 'paid' ? 'success' : 'secondary' }}">{{ $o->payment_status }}</span></td>
                    <td class="text-end">{{ $naira($o->total) }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($o->created_at)->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No orders yet. Orders appear here when you book deliveries through the API.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
