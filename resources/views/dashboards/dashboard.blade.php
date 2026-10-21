@extends('layouts.master')

{{-- The platform owner's dashboard. Providers see their own business at /provider. --}}
@section('content')
@php
    $naira = fn ($k) => '₦'.number_format(((int) $k) / 100);
    $u = auth()->user();
    $link = fn ($name, ...$p) => Route::has($name) ? route($name, ...$p) : null;
    $attention = array_values(array_filter([
        $u->can('View delivery') && $k['waiting_rider_long'] ? ['Deliveries waiting over 15 minutes for a rider', $k['waiting_rider_long'], 'danger', $link('ops.orders', ['status' => 'awaiting_dispatch'])] : null,
        $u->can('View delivery') && $k['late'] ? ['Deliveries running late', $k['late'], 'danger', $link('ops.orders')] : null,
        $u->can('View dispute') ? ['Open disputes', $k['disputes'], $k['disputes'] ? 'danger' : 'secondary', $link('ops.disputes')] : null,
        \App\Support\Platform::marketplace() && $u->can('View kyc') ? ['Provider applications to review', $k['providers_pending'], $k['providers_pending'] ? 'warning' : 'secondary', $link('ops.applications')] : null,
        $u->can('Approve withdrawal') ? ['Payout requests to approve ('.$naira($k['payouts_waiting_amount']).')', $k['payouts_waiting'], $k['payouts_waiting'] ? 'warning' : 'secondary', $link('ops.payouts')] : null,
        $u->can('View settlement') ? ['Weekly statements to approve', $k['settlements_draft'], $k['settlements_draft'] ? 'warning' : 'secondary', $link('ops.settlements')] : null,
        $u->can('View vendor') ? ['Shops asking for a live API key', $k['live_keys'], $k['live_keys'] ? 'warning' : 'secondary', $link('ops.merchants')] : null,
        $u->can('Manage fraud flags') ? ['Risk events to review', $k['risk'], $k['risk'] ? 'warning' : 'secondary', $link('ops.risk')] : null,
        $u->can('View delivery') && $k['failed_today'] ? ['Failed delivery attempts today', $k['failed_today'], 'warning', $link('ops.failed')] : null,
    ]));
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="config('app.name')" icon="ri-building-4-line" subtitle="Platform overview: the whole marketplace, every provider and shop.">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ now()->format('l j M, H:i') }}</span>
            <span class="cb-meta-pill">{{ $users['online'] }} people online</span>
        </x-slot:pills>
    </x-cb.hero>
    @include('partials.flash')

    {{-- Money --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-cb.stat label="Sales today" :value="$naira($k['sales_today'])" icon="ri-money-dollar-circle-line" accent="violet" :hint="$k['paid_today'].' paid of '.$k['orders_today'].' orders'" /></div>
        <div class="col-6 col-xl-3"><x-cb.stat label="Commission today" :value="$naira($k['commission_today'])" icon="ri-percent-line" accent="teal" :hint="'This month '.$naira($k['commission_month'])" /></div>
        <div class="col-6 col-xl-3"><x-cb.stat label="Sales this month" :value="$naira($k['sales_month'])" icon="ri-line-chart-line" accent="sky" :hint="$k['orders_month'].' paid orders'" /></div>
        <div class="col-6 col-xl-3"><x-cb.stat label="Held in escrow" :value="$naira($k['escrow'])" icon="ri-safe-2-line" accent="amber" :hint="'Payouts in transit '.$naira($k['payouts_in_transit'])" /></div>
    </div>

    {{-- Deliveries and the marketplace --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2"><x-cb.stat label="In progress" :value="$k['in_progress']" icon="ri-route-line" accent="sky" :hint="$k['delivered_today'].' delivered today'" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Waiting for a rider" :value="$k['waiting_rider']" icon="ri-time-line" :accent="$k['waiting_rider_long'] ? 'rose' : 'teal'" :hint="$k['late'] ? $k['late'].' running late' : 'none late'" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Riders online" :value="$k['riders_online']" icon="ri-steering-2-line" accent="teal" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Providers live" :value="$k['providers_active']" icon="ri-truck-line" accent="violet" :hint="$k['providers_pending'].' waiting · '.$k['providers_suspended'].' suspended'" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Shops (API)" :value="$k['merchants']" icon="ri-store-3-line" accent="amber" :hint="$k['merchant_orders_today'].' orders today'" /></div>
        <div class="col-6 col-xl-2"><x-cb.stat label="Customers" :value="number_format($k['customers'])" icon="ri-group-line" accent="sky" :hint="'+'.$k['customers_new'].' this week'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            @if($u->hasAnyPermission(['View reports', 'View financial report']))@include('backoffice._revenue-card', ['operatorId' => null, 'reportsUrl' => route('ops.reports', 'daily')])@endif

            <div class="row g-3">
                <div class="col-md-6">
                    <x-cb.card title="Top providers this month" icon="ri-trophy-line" :flush="true">
                        <table class="table table-sm align-middle mb-0"><tbody>
                        @forelse($topProviders as $p)
                            <tr>
                                <td>@if(Route::has('ops.provider'))<a href="{{ route('ops.provider', $p->public_id) }}">{{ $p->display_name }}</a>@else{{ $p->display_name }}@endif<div class="small text-muted">{{ $p->jobs }} jobs</div></td>
                                <td class="text-end">{{ $naira($p->sales) }}<div class="small text-muted">fee {{ $naira($p->fee) }}</div></td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-3">No completed jobs this month yet.</td></tr>
                        @endforelse
                        </tbody></table>
                    </x-cb.card>
                </div>
                <div class="col-md-6">
                    <x-cb.card title="Top shops this month" icon="ri-store-3-line" :flush="true">
                        <table class="table table-sm align-middle mb-0"><tbody>
                        @forelse($topMerchants as $m)
                            <tr>
                                <td>@can('View vendor')<a href="{{ route('ops.merchant', $m->id) }}">{{ $m->display_name }}</a>@else{{ $m->display_name }}@endcan<div class="small text-muted">{{ $m->orders }} deliveries</div></td>
                                <td class="text-end">{{ $naira($m->sales) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-3">No shop deliveries this month yet.</td></tr>
                        @endforelse
                        </tbody></table>
                    </x-cb.card>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <x-cb.card title="Needs your attention" icon="ri-alarm-warning-line" :flush="true" class="mb-3">
                <div class="list-group list-group-flush">
                    @forelse($attention as [$label, $n, $tone, $url])
                        @if($url)
                            <a href="{{ $url }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">{{ $label }} <span class="badge bg-{{ $tone }}">{{ $n }}</span></a>
                        @else
                            <div class="list-group-item d-flex justify-content-between align-items-center">{{ $label }} <span class="badge bg-{{ $tone }}">{{ $n }}</span></div>
                        @endif
                    @empty
                        <div class="list-group-item text-muted">Nothing waiting.</div>
                    @endforelse
                </div>
            </x-cb.card>

            <x-cb.card title="Quick links" icon="ri-flashlight-line" class="mb-3">
                <div class="d-grid gap-2">
                    @can('View delivery')<a href="{{ route('ops.orders') }}" class="btn btn-light text-start"><i class="ri-shopping-bag-3-line me-1"></i> All orders</a>@endcan
                    @if(\App\Support\Platform::marketplace() && $u->hasAnyPermission(['View vendor', 'View driver', 'View shopper']))<a href="{{ route('ops.providers') }}" class="btn btn-light text-start"><i class="ri-truck-line me-1"></i> Providers</a>@endif
                    @if($u->hasAnyPermission(['Manage commission', 'Manage pricing rules']))<a href="{{ route('ops.settings') }}" class="btn btn-light text-start"><i class="ri-percent-line me-1"></i> Commission and settings</a>@endif
                    @can('View settlement')<a href="{{ route('ops.settlements') }}" class="btn btn-light text-start"><i class="ri-bank-line me-1"></i> Weekly statements</a>@endcan
                    @if($u->hasAnyPermission(['View reports', 'View operations report', 'View financial report']))<a href="{{ route('ops.reports') }}" class="btn btn-light text-start"><i class="ri-bar-chart-box-line me-1"></i> Reports</a>@endif
                </div>
            </x-cb.card>

            @if(count($recent))
            <x-cb.card title="Recent activity" icon="ri-history-line" :flush="true">
                <div class="list-group list-group-flush small">
                    @foreach($recent as $r)
                        <div class="list-group-item"><span class="text-muted">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d M H:i') }}</span> · <strong>{{ $r->name ?? 'System' }}</strong> {{ $r->description }}</div>
                    @endforeach
                </div>
            </x-cb.card>
            @endif
        </div>
    </div>

    @if($u->hasAnyPermission(['View reports', 'View operations report']))<div class="mt-3">@include('backoffice._drivers-card', ['operatorId' => null, 'reportsUrl' => route('ops.reports', 'drivers')])</div>@endif
</div></div></div>
@endsection
