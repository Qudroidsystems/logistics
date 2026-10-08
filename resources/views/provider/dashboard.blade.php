@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$op->display_name" icon="ri-store-2-line" :subtitle="str_replace('_', ' ', $op->type).' · you are '.str_replace('_', ' ', $role)">
        <x-slot:pills>
            <span class="cb-meta-pill">Account: {{ $op->status }}</span>
            @if($profile)<span class="cb-meta-pill">Tier: {{ $profile->tier }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>
    @include('provider._flash')

    @if($op->status !== 'active')
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>
            @if($op->status === 'suspended') Your account is suspended. Contact support to find out why.
            @elseif(($app->status ?? 'draft') === 'submitted') Your application is with our team. We will tell you as soon as it is reviewed.
            @elseif(($app->status ?? '') === 'rejected') Your application was not approved. @if($app->review_note) Reason: {{ $app->review_note }} @endif
            @else Your account is not live yet. @if(($app->status ?? '') === 'needs_changes' && $app->review_note) We need changes: {{ $app->review_note }} @endif
            @endif
        </div></div>
    @endif

    @if(count($missing))
        <x-cb.card title="Before you can submit your application" icon="ri-list-check-2" class="mb-3">
            <ul class="mb-2">@foreach($missing as $m)<li>{{ ucfirst($m) }}</li>@endforeach</ul>
            @if(Route::has('provider.onboarding'))<a href="{{ route('provider.onboarding') }}" class="btn btn-primary btn-sm">Continue setup</a>@endif
        </x-cb.card>
    @endif

    <div class="row g-3 mb-3">
        @if($nav['money'])<div class="col-6 col-lg-3"><x-cb.stat label="Wallet balance" :value="$naira($balance)" icon="ri-wallet-3-line" accent="teal" /></div>@endif
        <div class="col-6 col-lg-3"><x-cb.stat label="Jobs in progress" :value="$active" icon="ri-route-line" accent="sky" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Jobs completed" :value="$done" icon="ri-checkbox-circle-line" accent="violet" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Rating" :value="($profile && $profile->rating_count) ? number_format($profile->rating_avg, 1).' / 5' : '—'" icon="ri-star-line" accent="amber" :hint="($profile->rating_count ?? 0).' reviews'" /></div>
    </div>

    @if($score)
    <x-cb.card title="How customers see you" icon="ri-medal-line">
        <div class="row text-center">
            <div class="col-6 col-md-3"><div class="fs-4 fw-semibold">{{ round($score->score) }}</div><div class="small text-muted">Score out of 100</div></div>
            <div class="col-6 col-md-3"><div class="fs-4 fw-semibold">{{ round($score->completion_rate * 100) }}%</div><div class="small text-muted">Jobs completed</div></div>
            <div class="col-6 col-md-3"><div class="fs-4 fw-semibold">{{ round($score->dispute_rate * 100) }}%</div><div class="small text-muted">Jobs disputed</div></div>
            <div class="col-6 col-md-3"><div class="fs-4 fw-semibold">{{ ucfirst($profile->tier ?? 'verified') }}</div><div class="small text-muted">Tier</div></div>
        </div>
    </x-cb.card>
    @endif
</div></div></div>
@endsection
