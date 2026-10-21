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

    @php
        $t = $today;
        $todo = array_values(array_filter([
            $nav['jobs'] && $t['waiting'] ? ['Jobs waiting for a rider'.($t['waiting_long'] ? ' ('.$t['waiting_long'].' over 15 min)' : ''), $t['waiting'], $t['waiting_long'] ? 'danger' : 'warning', route('provider.dispatch')] : null,
            $nav['jobs'] && $t['late'] ? ['Jobs running late', $t['late'], 'danger', route('provider.jobs')] : null,
            $nav['jobs'] && $t['exceptions'] ? ['Delivery problems reported by riders', $t['exceptions'], 'warning', route('provider.jobs')] : null,
            ($nav['requests'] ?? false) && $t['requests'] ? ['Customer requests waiting for your offer', $t['requests'], 'warning', route('provider.requests')] : null,
            $t['docs_expiring'] ? ['Documents expiring within 30 days', $t['docs_expiring'], 'warning', Route::has('provider.onboarding') ? route('provider.onboarding') : null] : null,
            $nav['money'] && $t['payouts_waiting'] ? ['Payouts on their way to your bank', $naira($t['payouts_waiting']), 'info', route('provider.wallet')] : null,
        ]));
    @endphp

    {{-- Today --}}
    <div class="row g-3 mb-3">
        @if($nav['jobs'])<div class="col-6 col-xl-2"><x-cb.stat label="Waiting for a rider" :value="$t['waiting']" icon="ri-time-line" :accent="$t['waiting_long'] ? 'rose' : 'amber'" :hint="$t['manual'] ? $t['manual'].' need you to assign' : 'auto-dispatch is offering them'" /></div>@endif
        <div class="col-6 col-xl-2"><x-cb.stat label="Jobs in progress" :value="$active" icon="ri-route-line" accent="sky" :hint="$t['late'] ? $t['late'].' running late' : 'none late'" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="New today" :value="$t['new_today']" icon="ri-inbox-archive-line" accent="violet" :hint="$t['shop_jobs_today'] ? $t['shop_jobs_today'].' from shops' : $t['delivered_today'].' delivered'" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Riders online" :value="$t['riders_online'].' / '.$t['riders']" icon="ri-steering-2-line" accent="teal" /></div>
        @if($nav['money'])<div class="col-6 col-xl-2"><x-cb.stat label="Wallet balance" :value="$naira($balance)" icon="ri-wallet-3-line" accent="teal" :hint="'Earned today '.$naira($t['earned_today'])" /></div>@endif
        <div class="col-6 col-xl-2"><x-cb.stat label="Rating" :value="($profile && $profile->rating_count) ? number_format($profile->rating_avg, 1).' / 5' : '—'" icon="ri-star-line" accent="amber" :hint="($profile->rating_count ?? 0).' reviews · '.$done.' jobs done'" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <x-cb.card title="Latest jobs" icon="ri-route-line" :flush="true">
                <x-slot:tools>@if($nav['jobs'])<a href="{{ route('provider.jobs') }}" class="btn btn-sm btn-light">All jobs</a>@endif</x-slot:tools>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>
                @forelse($recentJobs as $j)
                    <tr>
                        <td>@if($nav['jobs'])<a href="{{ route('provider.job', $j->public_id) }}" class="fw-semibold">{{ $j->order_number }}</a>@else<span class="fw-semibold">{{ $j->order_number }}</span>@endif
                            <div class="small text-muted">{{ $j->tracking_code }}{{ $j->shop ? ' · from '.$j->shop : '' }}</div></td>
                        <td class="small">{{ $j->rider ?? '—' }}</td>
                        <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($j->created_at)->diffForHumans() }}</td>
                        <td class="text-end"><span class="badge bg-{{ in_array($j->status, ['delivered', 'completed']) ? 'success' : (in_array($j->status, ['awaiting_dispatch', 'offered']) ? 'warning' : (in_array($j->status, ['cancelled', 'failed_attempt', 'returned']) ? 'danger' : 'info')) }}">{{ str_replace('_', ' ', $j->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td class="text-center text-muted py-4">No jobs yet. @if($nav['jobs'])<a href="{{ route('provider.booking.new') }}">Book one</a> or wait for customers and shops to choose you.@endif</td></tr>
                @endforelse
                </tbody></table></div>
            </x-cb.card>
        </div>
        <div class="col-lg-4">
            <x-cb.card title="Needs your attention" icon="ri-alarm-warning-line" :flush="true" class="mb-3">
                <div class="list-group list-group-flush">
                    @forelse($todo as [$label, $n, $tone, $url])
                        <a href="{{ $url ?? '#' }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">{{ $label }} <span class="badge bg-{{ $tone }}">{{ $n }}</span></a>
                    @empty
                        <div class="list-group-item text-muted">All clear.</div>
                    @endforelse
                </div>
            </x-cb.card>
            <x-cb.card title="Quick actions" icon="ri-flashlight-line" class="mb-3">
                <div class="d-grid gap-2">
                    @if($nav['jobs'])<a href="{{ route('provider.booking.new') }}" class="btn btn-primary text-start"><i class="ri-add-box-line me-1"></i> New booking</a>
                    <a href="{{ route('provider.dispatch') }}" class="btn btn-light text-start"><i class="ri-layout-grid-line me-1"></i> Dispatch board</a>@endif
                    @if($nav['team'])<a href="{{ route('provider.team') }}" class="btn btn-light text-start"><i class="ri-team-line me-1"></i> Riders and team</a>@endif
                    @if($nav['money'])<a href="{{ route('provider.wallet') }}" class="btn btn-light text-start"><i class="ri-wallet-3-line me-1"></i> Wallet and payouts</a>
                    <a href="{{ route('provider.statements') }}" class="btn btn-light text-start"><i class="ri-file-list-2-line me-1"></i> Statements</a>@endif
                </div>
            </x-cb.card>
            @if($commission)
            <x-cb.card title="Platform fee" icon="ri-percent-line">
                <div class="fs-4 fw-semibold">{{ $commission['percent'] }}%</div>
                <div class="small text-muted">of each delivery price{{ $commission['min'] ? ', at least '.$naira($commission['min']).' a job' : '' }}{{ $commission['special'] ? ' · your agreed rate' : '' }}.</div>
            </x-cb.card>
            @endif
        </div>
    </div>

    @if($nav['money'] && $op->status === 'active')@include('backoffice._revenue-card', ['operatorId' => (int) $op->id, 'reportsUrl' => route('provider.reports', 'daily')])@endif
    @if($nav['jobs'] && $op->status === 'active')@include('backoffice._drivers-card', ['operatorId' => (int) $op->id, 'reportsUrl' => route('provider.reports', 'drivers')])@endif

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
@include('partials.auto-refresh', ['seconds' => 60])
@endsection
