@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Live fleet" icon="ri-map-2-line" subtitle="Where your drivers are right now. Positions refresh by themselves; tap a driver to see the road they took in the last hour." :back="route('provider.dashboard')" back-label="Dashboard">
        <x-slot:actions><a class="cb-hero-btn" href="{{ route('provider.dispatch') }}"><i class="ri-layout-grid-line me-1"></i>Dispatch board</a></x-slot:actions>
    </x-cb.hero>
    @include('provider._flash')
    @include('partials.fleet-map')
</div></div></div>
@endsection
