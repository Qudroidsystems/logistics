@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Find a provider" icon="ri-store-2-line" subtitle="Verified companies, riders and shoppers, best first." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <form method="GET" class="mb-3"><select name="service_type_id" class="form-select w-auto" onchange="this.form.submit()">
        @foreach($serviceTypes as $t)<option value="{{ $t->id }}" @selected($sid == $t->id)>{{ $t->name }}</option>@endforeach
    </select></form>
    <div class="row g-3">
        @forelse($rows as $p)
        <div class="col-md-6 col-xl-4"><x-cb.card>
            <div class="d-flex justify-content-between"><div class="fw-semibold fs-5">{{ $p['name'] }}</div><span class="badge bg-light text-dark">{{ ucfirst($p['tier']) }}</span></div>
            <div class="text-muted small mb-2">{{ $p['headline'] }}</div>
            <div class="small mb-3">{{ $p['rating_count'] ? number_format($p['rating_avg'], 1).' ★ ('.$p['rating_count'].')' : 'New' }} · {{ $p['jobs_completed'] }} jobs</div>
            <a href="{{ route('account.request.new', ['provider' => $p['id'], 'service_type_id' => $sid]) }}" class="btn btn-primary btn-sm w-100">Ask {{ $p['name'] }} for a price</a>
        </x-cb.card></div>
        @empty
        <div class="col-12 text-center text-muted py-5">No providers listed for this service yet. You can still <a href="{{ route('account.request.new', ['service_type_id' => $sid]) }}">send an open request</a>.</div>
        @endforelse
    </div>
</div></div></div>
@endsection
