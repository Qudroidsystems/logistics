@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Orders" icon="ri-shopping-bag-3-line" subtitle="Search by order number or tracking code." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <x-cb.card title="Orders" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input name="q" value="{{ $f['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Order or tracking code">
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">Any payment</option>
                    @foreach(['pending','paid','failed','refunded'] as $s)<option value="{{ $s }}" @selected(($f['payment_status'] ?? '') === $s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </x-slot:tools>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Order</th><th>Provider</th><th>Delivery</th><th>Payment</th><th class="text-end">Total</th><th>Placed</th></tr></thead>
                <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td><a href="{{ route('ops.order', $r['order_id']) }}" class="fw-semibold">{{ $r['order_number'] }}</a><div class="small text-muted">{{ $r['tracking_code'] }}</div></td>
                        <td>{{ $r['provider'] ?? '—' }}</td>
                        <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $r['status'] ?? 'no delivery') }}</span></td>
                        <td><span class="badge bg-{{ $r['payment_status'] === 'paid' ? 'success' : 'secondary' }}">{{ $r['payment_status'] }}</span></td>
                        <td class="text-end">{{ $naira($r['total']) }}</td>
                        <td class="text-muted small">{{ \Illuminate\Support\Carbon::parse($r['created_at'])->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No orders match.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-cb.card>
</div></div></div>
@endsection
