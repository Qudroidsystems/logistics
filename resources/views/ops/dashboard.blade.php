@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Operations" icon="ri-truck-line" subtitle="What needs attention right now." />
    @include('ops._flash')

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Orders today" :value="$d['orders_today']" icon="ri-shopping-bag-3-line" accent="teal" :hint="$d['paid_orders_today'].' paid'" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Sales today" :value="$naira($d['gmv_today'])" icon="ri-money-dollar-circle-line" accent="violet" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Deliveries in progress" :value="$d['active_shipments']" icon="ri-route-line" accent="sky" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Active providers" :value="$d['active_providers']" icon="ri-team-line" accent="amber" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4"><x-cb.stat label="Held in escrow" :value="$naira($d['escrow_held'])" icon="ri-safe-2-line" accent="teal" /></div>
        <div class="col-6 col-lg-4"><x-cb.stat label="Commission earned" :value="$naira($d['commission_earned'])" icon="ri-percent-line" accent="violet" /></div>
        <div class="col-6 col-lg-4"><x-cb.stat label="Payouts in transit" :value="$naira($d['payouts_in_transit'])" icon="ri-send-plane-line" accent="sky" /></div>
    </div>

    <x-cb.card title="Needs attention" icon="ri-alarm-warning-line">
        <div class="list-group list-group-flush">
            @can('View dispute')<a href="{{ route('ops.disputes') }}" class="list-group-item d-flex justify-content-between align-items-center">Open disputes <span class="badge bg-{{ $d['open_disputes'] ? 'danger' : 'secondary' }}">{{ $d['open_disputes'] }}</span></a>@endcan
            @can('View kyc')<a href="{{ route('ops.applications') }}" class="list-group-item d-flex justify-content-between align-items-center">Provider applications waiting <span class="badge bg-{{ $d['kyc_waiting'] ? 'warning' : 'secondary' }}">{{ $d['kyc_waiting'] }}</span></a>@endcan
            <div class="list-group-item d-flex justify-content-between align-items-center">Payout requests waiting approval <span class="badge bg-{{ $d['payouts_waiting_approval'] ? 'warning' : 'secondary' }}">{{ $d['payouts_waiting_approval'] }}</span></div>
            <div class="list-group-item d-flex justify-content-between align-items-center">Open risk events <span class="badge bg-{{ $d['open_risk_events'] ? 'warning' : 'secondary' }}">{{ $d['open_risk_events'] }}</span></div>
        </div>
    </x-cb.card>
</div></div></div>
@endsection
