@extends('layouts.master')

@section('content')
@php
    $naira = fn ($k) => '₦'.number_format(((int) $k) / 100);
    $tone = fn ($s) => match ($s) { 'active', 'approved', 'delivered', 'paid', 'verified' => 'success', 'suspended', 'rejected', 'failed', 'cancelled' => 'danger', 'pending', 'submitted', 'requested', 'awaiting_dispatch' => 'warning', default => 'secondary' };
    $canSuspend = auth()->user()->hasAnyPermission(['Suspend vendor', 'Suspend driver', 'Suspend shopper']);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$op->display_name" icon="ri-truck-line" :subtitle="ucfirst(str_replace('_', ' ', $op->type)).($op->legal_name && $op->legal_name !== $op->display_name ? ' · '.$op->legal_name : '')" :back="route('ops.providers')" back-label="Providers">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ $op->status }}</span>
            @if($profile)<span class="cb-meta-pill">Tier: {{ $profile->tier ?? '—' }}</span><span class="cb-meta-pill">{{ $profile->listed ? 'Listed' : 'Not listed' }} · {{ $profile->availability_status ?? '—' }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2"><x-cb.stat label="In progress" :value="$k['in_progress']" icon="ri-route-line" accent="sky" :hint="$k['waiting'].' waiting for a rider'" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Delivered (all time)" :value="$k['delivered']" icon="ri-checkbox-circle-line" accent="teal" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Sales this month" :value="$naira($k['sales_month'])" icon="ri-line-chart-line" accent="violet" :hint="$k['jobs_month'].' jobs'" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Commission this month" :value="$naira($k['fee_month'])" icon="ri-percent-line" accent="amber" :hint="'All time '.$naira($k['fee_all'])" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Wallet" :value="$naira($k['wallet'])" icon="ri-wallet-3-line" accent="teal" :hint="$k['payouts_waiting'] ? $naira($k['payouts_waiting']).' asked to withdraw' : null" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Rating" :value="($profile && $profile->rating_count) ? number_format($profile->rating_avg, 1).' / 5' : '—'" icon="ri-star-line" accent="sky" :hint="$score ? 'Score '.round($score->score).'/100' : null" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-cb.card title="Standing" icon="ri-shield-check-line" class="mb-3">
                <dl class="row small mb-2">
                    <dt class="col-6">Application</dt><dd class="col-6"><span class="badge bg-{{ $tone($app->status ?? 'draft') }}">{{ $app->status ?? 'none' }}</span></dd>
                    @if($app && $app->review_note)<dt class="col-6">Last note</dt><dd class="col-6">{{ $app->review_note }}</dd>@endif
                    @if($score)
                    <dt class="col-6">Completion</dt><dd class="col-6">{{ round($score->completion_rate * 100) }}%</dd>
                    <dt class="col-6">On time</dt><dd class="col-6">{{ $score->on_time_rate !== null ? round($score->on_time_rate * 100).'%' : '—' }}</dd>
                    <dt class="col-6">Disputed</dt><dd class="col-6">{{ round($score->dispute_rate * 100) }}%</dd>
                    @endif
                    <dt class="col-6">Joined</dt><dd class="col-6">{{ \Illuminate\Support\Carbon::parse($op->created_at)->format('j M Y') }}</dd>
                </dl>
                <div class="d-flex flex-wrap gap-2">
                    @can('View kyc')@if(\App\Support\Platform::marketplace())<a href="{{ route('ops.application', $op->public_id) }}" class="btn btn-sm btn-light">Application</a>@endif @endcan
                    <form method="POST" action="{{ route('ops.provider.refresh', $op->public_id) }}">@csrf<button class="btn btn-sm btn-outline-secondary"><i class="ri-refresh-line"></i> Recalculate score</button></form>
                    @if($canSuspend && $op->status === 'active')
                        <form method="POST" action="{{ route('ops.provider.suspend', $op->public_id) }}" onsubmit="var r=prompt('Reason for suspending {{ addslashes($op->display_name) }}?'); if(!r){return false;} this.querySelector('[name=reason]').value=r;">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-outline-danger">Suspend</button></form>
                    @elseif($canSuspend && $op->status === 'suspended')
                        <form method="POST" action="{{ route('ops.provider.reinstate', $op->public_id) }}">@csrf<button class="btn btn-sm btn-success">Reinstate</button></form>
                    @endif
                </div>
            </x-cb.card>

            <x-cb.card title="Commission" icon="ri-percent-line" class="mb-3">
                @if($commission)
                    <div class="fs-4 fw-semibold">{{ $commission['percent'] }}%</div>
                    <div class="small text-muted">{{ $commission['scope'] }}@if($commission['min']) · at least {{ $naira($commission['min']) }} a job @endif</div>
                @else
                    <div class="text-muted small">No fee is set: the platform takes nothing from this provider's jobs.</div>
                @endif
                @can('Manage commission')<a href="{{ route('ops.settings') }}" class="btn btn-sm btn-light mt-2">Set a special fee</a>@endcan
            </x-cb.card>

            @if(count($shops))
            <x-cb.card title="Runs deliveries for these shops" icon="ri-store-3-line" class="mb-3">
                @foreach($shops as $s)<div class="small">@can('View vendor')<a href="{{ route('ops.merchant', $s->id) }}">{{ $s->display_name }}</a>@else{{ $s->display_name }}@endcan</div>@endforeach
            </x-cb.card>
            @endif

            <x-cb.card title="Payouts" icon="ri-bank-line" :flush="true">
                <table class="table table-sm mb-0"><tbody>
                @forelse($payouts as $p)
                    <tr><td class="small">{{ \Illuminate\Support\Carbon::parse($p->created_at)->format('j M') }}</td><td class="text-end">{{ $naira($p->amount) }}</td><td class="text-end"><span class="badge bg-{{ $tone($p->status) }}">{{ $p->status }}</span></td></tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No payouts yet.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>

        <div class="col-lg-8">
            <x-cb.card title="Recent jobs" icon="ri-route-line" :flush="true" class="mb-3">
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>
                @forelse($jobs as $j)
                    <tr>
                        <td>@can('View delivery')<a href="{{ route('ops.order', $j->order_id) }}">{{ $j->order_number }}</a>@else{{ $j->order_number }}@endcan<div class="small text-muted">{{ $j->tracking_code }}{{ $j->shop ? ' · '.$j->shop : '' }}{{ $j->is_test ? ' · test' : '' }}</div></td>
                        <td class="small">{{ \Illuminate\Support\Carbon::parse($j->created_at)->format('j M H:i') }}</td>
                        <td class="text-end">{{ $naira($j->total) }}</td>
                        <td class="text-end"><span class="badge bg-{{ $tone($j->status) }}">{{ str_replace('_', ' ', $j->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No jobs yet.</td></tr>
                @endforelse
                </tbody></table></div>
            </x-cb.card>

            <div class="row g-3">
                <div class="col-md-6">
                    <x-cb.card title="Team" icon="ri-team-line" :flush="true">
                        <table class="table table-sm mb-0"><tbody>
                        @forelse($members as $m)
                            <tr><td>{{ $m->name }}<div class="small text-muted">{{ $m->email }}</div></td><td class="text-end small">{{ str_replace('_', ' ', $m->role) }}@if($m->status !== 'active')<div><span class="badge bg-secondary">{{ $m->status }}</span></div>@endif</td></tr>
                        @empty
                            <tr><td class="text-center text-muted py-3">Nobody yet.</td></tr>
                        @endforelse
                        </tbody></table>
                    </x-cb.card>
                </div>
                <div class="col-md-6">
                    <x-cb.card title="Riders" icon="ri-steering-2-line" :flush="true">
                        <table class="table table-sm mb-0"><tbody>
                        @forelse($riders as $r)
                            <tr>
                                <td>{{ $r->name }}<div class="small text-muted">{{ $r->last_seen_at ? 'seen '.\Illuminate\Support\Carbon::parse($r->last_seen_at)->diffForHumans() : 'never online' }}</div></td>
                                <td class="text-end small"><span class="badge bg-{{ in_array($r->availability, ['online', 'on_job']) ? 'success' : 'secondary' }}">{{ str_replace('_', ' ', $r->availability) }}</span>@if($r->status !== 'active')<div><span class="badge bg-{{ $tone($r->status) }}">{{ $r->status }}</span></div>@endif</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-3">No riders yet.</td></tr>
                        @endforelse
                        </tbody></table>
                    </x-cb.card>
                </div>
            </div>

            @can('View kyc')
            <x-cb.card title="Documents" icon="ri-file-shield-2-line" :flush="true" class="mt-3">
                <table class="table table-sm mb-0"><tbody>
                @forelse($documents as $d)
                    <tr>
                        <td>@if(\App\Support\Platform::marketplace())<a href="{{ route('ops.document', $d->public_id) }}" target="_blank">{{ ucwords(str_replace('_', ' ', $d->doc_type)) }}</a>@else{{ ucwords(str_replace('_', ' ', $d->doc_type)) }}@endif</td>
                        <td class="small">{{ $d->expires_on ? 'expires '.\Illuminate\Support\Carbon::parse($d->expires_on)->format('j M Y') : '' }}</td>
                        <td class="text-end"><span class="badge bg-{{ $tone($d->status) }}">{{ $d->status }}</span></td>
                    </tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No documents uploaded.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
            @endcan
        </div>
    </div>
</div></div></div>
@endsection
