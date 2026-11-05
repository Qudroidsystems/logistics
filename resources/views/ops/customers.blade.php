@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Customers" icon="ri-user-heart-line" subtitle="Find a customer by name, email, phone, order number or tracking code, then see everything about them on one page." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')
    <form method="GET" action="{{ route('ops.customers') }}" class="d-flex gap-2 mb-3" style="max-width: 560px">
        <input name="q" class="form-control" placeholder="Name, email, phone, order or tracking code" value="{{ $q }}" autofocus>
        <button class="btn btn-primary">Search</button>
    </form>
    <x-cb.card :title="$q === '' ? 'Recently active' : 'Results'" icon="ri-group-line" :count="count($rows)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Customer</th><th>Phone</th><th class="text-center">Orders</th><th>Last order</th><th>Plan</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><a href="{{ route('ops.customer', $r->public_id) }}" class="fw-semibold">{{ $r->name }}</a>@if($r->is_disabled) <span class="badge bg-danger-subtle text-danger">Suspended</span>@endif
                        <div class="small text-muted">{{ $r->email }}</div></td>
                    <td class="small">{{ $r->phone_number ? \App\Support\Phone::display($r->phone_number) : '-' }}</td>
                    <td class="text-center">{{ $r->orders }}</td>
                    <td class="small">{{ $r->last_order ? \Illuminate\Support\Carbon::parse($r->last_order)->diffForHumans() : '-' }}</td>
                    <td class="small">{{ $r->plan ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No customers found.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
