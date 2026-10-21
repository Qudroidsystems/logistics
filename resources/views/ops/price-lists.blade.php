@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => $k === null ? '—' : Money::symbol().number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); $market = \App\Support\Platform::marketplace(); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Price lists" icon="ri-price-tag-3-line" subtitle="The fixed prices behind instant quotes for business customers: base fee, per km, weight, extra stops, and weekend, rush and fuel surcharges." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')
    <x-cb.card title="Price lists" icon="ri-list-check" :count="count($plans)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Name</th>@if($market)<th>Provider</th>@endif<th>Service</th><th>City</th><th>Vehicle</th><th class="text-end">Rules</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($plans as $p)
                <tr><td class="fw-semibold">{{ $p->name }} <span class="text-muted small">v{{ $p->version }}</span></td>@if($market)<td>{{ $p->provider }}</td>@endif<td>{{ $p->service }}</td><td>{{ $p->city ?: 'All' }}</td><td>{{ $p->vehicle ?: 'Any' }}</td>
                    <td class="text-end">{{ $p->rules }}</td><td><span class="badge {{ $p->status === 'active' ? 'bg-success' : 'bg-light text-dark' }}">{{ $p->status }}</span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('ops.price-list', $p->id) }}">Open</a></td></tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No price lists yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
    <x-cb.card title="New price list" icon="ri-add-circle-line" class="mt-3">
        <form method="POST" action="{{ route('ops.price-list.store') }}" class="row g-2">@csrf
            <div class="col-md-4"><label class="form-label small">Name</label><input name="name" class="form-control" required placeholder="Standard bike delivery"></div>
            <div class="col-md-4"><label class="form-label small">Provider</label><select name="operator_id" class="form-select">@foreach($operators as $o)<option value="{{ $o->id }}">{{ $o->display_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label small">Service</label><select name="service_type_id" class="form-select">@foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small">City</label><select name="city_id" class="form-select"><option value="">All cities</option>@foreach($cities as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small">Vehicle</label><select name="vehicle_type_id" class="form-select"><option value="">Any vehicle</option>@foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">Minimum ({{ Money::symbol() }})</label><input name="min_fee_naira" type="number" step="0.01" min="0" class="form-control" required value="1000"></div>
            <div class="col-md-2"><label class="form-label small">Maximum ({{ Money::symbol() }})</label><input name="max_fee_naira" type="number" step="0.01" min="0" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small">Round up to ({{ Money::symbol() }})</label><input name="rounding_naira" type="number" step="0.01" min="0.01" class="form-control" value="50"></div>
            <div class="col-12"><button class="btn btn-primary">Create draft</button></div>
        </form>
    </x-cb.card>
</div></div></div>
@endsection
