@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Find a provider" icon="ri-store-2-line" subtitle="Verified companies, riders and shoppers, best first." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6"><div class="input-group"><span class="input-group-text"><i class="ri-search-line"></i></span>
            <input name="q" value="{{ $q }}" class="form-control" placeholder="Type @ and a handle, e.g. @swift or @musa, or a name" autocomplete="off"></div></div>
        <div class="col-6 col-md-3"><select name="kind" class="form-select"><option value="">Everyone</option>@foreach(['company' => 'Delivery companies', 'rider' => 'Riders', 'shopper' => 'Shoppers'] as $k => $label)<option value="{{ $k }}" @selected($kind === $k)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3"><button class="btn btn-primary w-100">Search</button></div>
    </form>

    @if($found !== null)
    <div class="row g-3">
        @forelse($found as $r)
        <div class="col-md-6 col-xl-4"><x-cb.card>
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="fw-semibold fs-5">{{ $r['name'] }} @if($r['online'])<span class="badge bg-success align-middle" style="font-size:10px">online</span>@endif</div>
                    <div class="text-primary small fw-semibold">{{ '@'.$r['handle'] }}</div></div>
                <span class="badge bg-light text-dark">{{ $r['kind_label'] }}</span>
            </div>
            <div class="text-muted small my-2">{{ $r['subtitle'] }}</div>
            <div class="small mb-3">{{ $r['rating'] ? number_format($r['rating'], 1).' ★ ('.$r['rating_count'].')' : 'New' }}@if($r['jobs'] !== null) · {{ $r['jobs'] }} jobs @endif @if($r['distance_km']) · about {{ $r['distance_km'] }} km away @endif</div>
            @if($r['kind'] !== 'rider' || empty($r['book']['driver_handle']))<a href="{{ route('provider.public', $r['handle']) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm w-100 mb-2">View profile and reviews</a>
            @else<a href="{{ route('provider.public', $r['company_handle']) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm w-100 mb-2">About {{ $r['company'] }}</a>@endif
            <a href="{{ route('account.request.new', array_filter(['provider' => $r['book']['operator_id'], 'driver' => $r['book']['driver_handle']])) }}" class="btn btn-primary btn-sm w-100">Ask {{ $r['book']['driver_handle'] ? '@'.$r['handle'] : $r['name'] }} for a price</a>
        </x-cb.card></div>
        @empty
        <div class="col-12 text-center text-muted py-5">Nobody matches “{{ $q }}”. Check the @handle, or <a href="{{ route('account.providers') }}">browse by service</a>.</div>
        @endforelse
    </div>
    @else
    <form method="GET" class="mb-3"><select name="service_type_id" class="form-select w-auto" onchange="this.form.submit()">
        @foreach($serviceTypes as $t)<option value="{{ $t->id }}" @selected($sid == $t->id)>{{ $t->name }}</option>@endforeach
    </select></form>
    <div class="row g-3">
        @forelse($rows as $p)
        <div class="col-md-6 col-xl-4"><x-cb.card>
            <div class="d-flex justify-content-between"><div class="fw-semibold fs-5">{{ $p['name'] }}</div><span class="badge bg-light text-dark">{{ ucfirst($p['tier']) }}</span></div>
            <div class="text-muted small mb-2">{{ $p['headline'] }}</div>
            <div class="small mb-3">{{ $p['rating_count'] ? number_format($p['rating_avg'], 1).' ★ ('.$p['rating_count'].')' : 'New' }} · {{ $p['jobs_completed'] }} jobs</div>
            <a href="{{ route('provider.public', $p['public_slug']) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm w-100 mb-2">View profile and reviews</a>
            <a href="{{ route('account.request.new', ['provider' => $p['id'], 'service_type_id' => $sid]) }}" class="btn btn-primary btn-sm w-100">Ask {{ $p['name'] }} for a price</a>
        </x-cb.card></div>
        @empty
        <div class="col-12 text-center text-muted py-5">No providers listed for this service yet. You can still <a href="{{ route('account.request.new', ['service_type_id' => $sid]) }}">send an open request</a>.</div>
        @endforelse
    </div>
    @endif
</div></div></div>
@endsection
