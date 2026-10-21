@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Live fleet" icon="ri-map-2-line" subtitle="Drivers of every provider, live. Pick a provider to see only theirs; tap a driver to see the road they took in the last hour." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')
    @include('partials.fleet-map')
</div></div></div>
@endsection
