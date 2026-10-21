@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Business plan" icon="ri-vip-crown-2-line" subtitle="A monthly plan with deliveries included. The fee comes from your wallet." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')

    @if($current)
        @php $pct = $current['included'] ? min(100, round($current['used'] / $current['included'] * 100)) : 0; @endphp
        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <x-cb.card :title="$current['name'].' plan'" icon="ri-vip-crown-2-line">
                    @if($current['status'] === 'past_due')
                        <div class="alert alert-warning">We couldn't take this month's {{ $current['price_text'] }} from your wallet (balance {{ Money::format($balance, true) }}). Until it's paid, deliveries are charged at the normal price.
                            <form method="POST" action="{{ route('account.plan.pay') }}" class="d-inline">@csrf <button class="btn btn-sm btn-warning ms-2">Pay now</button></form>
                            <a href="{{ route('account.wallet') }}" class="ms-2">Top up</a></div>
                    @endif
                    <div class="d-flex justify-content-between mb-1"><span><strong>{{ $current['used'] }}</strong> of {{ $current['included'] }} deliveries used</span><span class="text-muted">{{ $current['left'] }} left</span></div>
                    <div class="progress mb-3" style="height: 10px"><div class="progress-bar" style="width: {{ $pct }}%"></div></div>
                    <dl class="row small mb-0">
                        <dt class="col-5 fw-normal text-muted">This month</dt><dd class="col-7">{{ \Illuminate\Support\Carbon::parse($current['period_start'])->format('j M') }} to {{ \Illuminate\Support\Carbon::parse($current['period_end'])->format('j M Y') }}</dd>
                        <dt class="col-5 fw-normal text-muted">Monthly fee</dt><dd class="col-7">{{ $current['price_text'] }}</dd>
                        <dt class="col-5 fw-normal text-muted">Extra deliveries</dt><dd class="col-7">{{ $current['overage_text'] ? $current['overage_text'].' each' : 'Normal price' }}</dd>
                        @if($current['max_km'])<dt class="col-5 fw-normal text-muted">Covers</dt><dd class="col-7">Trips up to {{ $current['max_km'] }} km</dd>@endif
                        @if($current['priority'])<dt class="col-5 fw-normal text-muted">Dispatch</dt><dd class="col-7">Priority</dd>@endif
                        <dt class="col-5 fw-normal text-muted">Next</dt>
                        <dd class="col-7">
                            @if($current['cancel_at_period_end']) Ends {{ \Illuminate\Support\Carbon::parse($current['period_end'])->format('j M') }}
                            @elseif($current['next_plan']) Switches to {{ $current['next_plan'] }} on {{ \Illuminate\Support\Carbon::parse($current['period_end'])->format('j M') }}
                            @else Renews {{ \Illuminate\Support\Carbon::parse($current['period_end'])->format('j M') }} from your wallet ({{ Money::format($balance, true) }} now) @endif
                        </dd>
                    </dl>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="{{ route('landing') }}" class="btn btn-primary">Book a delivery</a>
                        @if($current['cancel_at_period_end'])
                            <form method="POST" action="{{ route('account.plan.resume') }}">@csrf <button class="btn btn-outline-success">Keep my plan</button></form>
                        @else
                            <form method="POST" action="{{ route('account.plan.cancel') }}" onsubmit="return confirm('Cancel your plan? It runs to the end of this month.')">@csrf <button class="btn btn-outline-danger">Cancel plan</button></form>
                        @endif
                    </div>
                </x-cb.card>
            </div>
            <div class="col-lg-5">
                <x-cb.card title="This month's deliveries" icon="ri-list-check-2" :flush="true">
                    <div class="table-responsive"><table class="table table-sm mb-0">
                        @forelse($uses as $u)
                            <tr><td>{{ $u->order_number }}</td><td class="small">{{ $u->kind === 'included' ? 'Included' : 'Extra (plan rate)' }}@if($u->returned) <span class="text-muted">· cancelled, given back</span>@endif</td><td class="small text-muted text-end">{{ \Illuminate\Support\Carbon::parse($u->created_at)->format('j M') }}</td></tr>
                        @empty
                            <tr><td class="text-muted small p-3">None yet.</td></tr>
                        @endforelse
                    </table></div>
                </x-cb.card>
            </div>
        </div>
    @endif

    <h5 class="mb-3">{{ $current ? 'Change plan' : 'Choose a plan' }}</h5>
    <div class="row g-3">
        @foreach($plans as $p)
            <div class="col-md-4">
                <x-cb.card :title="$p['name']" icon="ri-vip-crown-line" class="h-100 {{ request('plan') === $p['code'] ? 'border border-primary' : '' }}">
                    <p class="small text-muted">{{ $p['tagline'] }}</p>
                    @if($p['contact_only'])
                        <div class="fs-5 fw-bold mb-2">Custom pricing</div>
                    @else
                        <div class="fs-4 fw-bold">{{ $p['price_text'] }}<span class="fs-6 text-muted fw-normal">/month</span></div>
                        <div class="small text-muted mb-2">{{ $p['included'] }} deliveries{{ $p['overage_text'] ? ' · extras '.$p['overage_text'] : '' }}{{ $p['priority'] ? ' · priority dispatch' : '' }}</div>
                    @endif
                    <ul class="small ps-3 mb-3">@foreach($p['features'] as $f)<li>{{ $f }}</li>@endforeach</ul>
                    @if($p['contact_only'])
                        @if($salesEmail)<a class="btn btn-outline-secondary w-100" href="mailto:{{ $salesEmail }}?subject={{ rawurlencode($p['name'].' plan') }}">Talk to us</a>@endif
                    @elseif(! $current)
                        <form method="POST" action="{{ route('account.plan.subscribe') }}" onsubmit="return confirm('Start {{ $p['name'] }}? {{ $p['price_text'] }} will be taken from your wallet now.')">@csrf
                            <input type="hidden" name="plan" value="{{ $p['code'] }}">
                            <button class="btn btn-primary w-100" @disabled($balance < $p['price'])>Start for {{ $p['price_text'] }}</button>
                        </form>
                        @if($balance < $p['price'])<div class="small text-muted mt-1">Your wallet has {{ Money::format($balance, true) }}. <a href="{{ route('account.wallet') }}">Top it up</a> first.</div>@endif
                    @elseif($current['plan'] === $p['code'])
                        <div class="small text-success fw-semibold">Your current plan</div>
                    @else
                        <form method="POST" action="{{ route('account.plan.change') }}">@csrf
                            <input type="hidden" name="plan" value="{{ $p['code'] }}">
                            <button class="btn btn-outline-primary w-100">Switch at renewal</button>
                        </form>
                    @endif
                </x-cb.card>
            </div>
        @endforeach
    </div>
</div></div></div>
@endsection
