@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="My orders" icon="ri-shopping-bag-3-line" subtitle="Everything you have booked." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <x-cb.card title="Orders" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools><form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach(['all' => 'All', 'active' => 'On the way', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
        </select></form></x-slot:tools>
        <table class="table table-hover align-middle mb-0"><thead><tr><th>Order</th><th>Provider</th><th>Status</th><th class="text-end">Total</th><th>Placed</th></tr></thead><tbody>
        @forelse($rows as $o)
            <tr><td><a href="{{ route('account.order', $o->public_id) }}" class="fw-semibold">{{ $o->order_number }}</a></td><td>{{ $o->provider }}</td><td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $o->status) }}</span></td><td class="text-end">{{ $naira($o->total) }}</td><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($o->created_at)->diffForHumans() }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr>
        @endforelse
        </tbody></table>
    </x-cb.card>
</div></div></div>
@endsection
