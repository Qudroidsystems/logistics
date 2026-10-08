@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Failed deliveries" icon="ri-error-warning-line" subtitle="Receiver not reached, parcel refused or wrong address." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Failed" :value="$total" icon="ri-error-warning-line" accent="rose" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Of all deliveries" :value="$rate.'%'" icon="ri-percent-line" accent="sky" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Charged to customers" :value="$naira($charged)" icon="ri-money-dollar-circle-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Refunded" :value="$naira($refunded)" icon="ri-refund-2-line" accent="sky" /></div>
    </div>

    <x-cb.card title="Why they fail" icon="ri-bar-chart-horizontal-line" class="mb-3">
        @forelse($byReason as $k => $n)
            <div class="d-flex justify-content-between small mb-1"><span>{{ $reasons[$k] ?? ucfirst(str_replace('_', ' ', (string) $k)) }}</span><strong>{{ $n }}</strong></div>
            <div class="progress mb-2" style="height:6px"><div class="progress-bar" style="width:{{ $total ? round($n * 100 / $total) : 0 }}%"></div></div>
        @empty
            <div class="text-muted small">No failed deliveries in this period.</div>
        @endforelse
        @if($total)<div class="small text-muted mt-2">{{ $returnTrips }} of {{ $total }} had the parcel taken back to the sender.</div>@endif
    </x-cb.card>

    <x-cb.card title="Deliveries" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <select name="days" class="form-select form-select-sm">@foreach($daysOptions as $d => $label)<option value="{{ $d }}" @selected($days === $d)>{{ $label }}</option>@endforeach</select>
                <select name="reason" class="form-select form-select-sm"><option value="">Any reason</option>@foreach($reasons as $k => $label)<option value="{{ $k }}" @selected(($f['reason'] ?? '') === $k)>{{ $label }}</option>@endforeach</select>
                <input name="provider" value="{{ $f['provider'] ?? '' }}" class="form-control form-control-sm" placeholder="Provider">
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Order</th><th>Provider</th><th>Reason</th><th class="text-end">Charged</th><th class="text-end">Refunded</th><th>Parcel</th><th>When</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><a href="{{ route('ops.order', $r->order_id) }}" class="fw-semibold">{{ $r->order }}</a>@if($r->merchant) <span class="badge bg-light text-dark">merchant</span>@endif</td>
                    <td>{{ $r->provider ?? '—' }}</td>
                    <td class="small">{{ $reasons[$r->reason] ?? ucfirst(str_replace('_', ' ', (string) $r->reason)) }}</td>
                    <td class="text-end">{{ $r->charged ? $naira($r->charged) : '—' }}</td>
                    <td class="text-end">{{ $r->refunded ? $naira($r->refunded) : '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $r->status === 'returned' ? 'returned' : ($r->status === 'returning' ? 'on its way back' : ($r->returning ? 'returning' : 'not returned')) }}</span></td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r->at)->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Nothing to show.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
