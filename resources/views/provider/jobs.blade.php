@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Jobs" icon="ri-route-line" subtitle="Deliveries and errands assigned to you." :back="route('provider.dashboard')" back-label="Dashboard" />
    @include('provider._flash')
    <x-cb.card title="Jobs" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(['active' => 'In progress', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'all' => 'All'] as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select></form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Order</th><th>Status</th><th class="text-end">Value</th><th>Created</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><a href="{{ route('provider.job', $r->public_id) }}" class="fw-semibold">{{ $r->order_number }}</a><div class="small text-muted">{{ $r->tracking_code }}</div></td>
                    <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $r->status) }}</span></td>
                    <td class="text-end">{{ $naira($r->total) }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r->created_at)->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No jobs here.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
