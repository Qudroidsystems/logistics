@extends('public._layout', ['title' => 'Business plans · '.$brand['name']])
@section('content')
<section style="background: linear-gradient(135deg, var(--brand2), var(--brand));" class="text-white">
    <div class="container py-5 text-center" style="max-width: 860px">
        <h1 class="display-6 fw-bold">Plans for businesses</h1>
        <p class="lead opacity-75 mb-0">A monthly plan with deliveries included. Book on the website or the app, track every parcel, and pay one fixed amount.</p>
    </div>
</section>
<div class="container py-5" style="max-width: 1100px">
    <div class="row g-4 justify-content-center">
        @forelse($plans as $p)
            <div class="col-md-6 col-lg-4">
                <div class="pub-card h-100 d-flex flex-column {{ $p['priority'] && ! $p['contact_only'] ? 'border border-2' : '' }}" @if($p['priority'] && ! $p['contact_only']) style="border-color: var(--brand) !important" @endif>
                    <div class="d-flex justify-content-between align-items-start">
                        <h2 class="h4 fw-bold mb-1">{{ $p['name'] }}</h2>
                        @if($p['priority'] && ! $p['contact_only'])<span class="badge" style="background: var(--brand)">Most popular</span>@endif
                    </div>
                    <p class="text-muted small">{{ $p['tagline'] }}</p>
                    @if($p['contact_only'])
                        <div class="fs-3 fw-bold mb-3">Custom pricing</div>
                    @else
                        <div class="mb-1"><span class="display-6 fw-bold">{{ $p['price_text'] }}</span><span class="text-muted">/month</span></div>
                        <div class="small text-muted mb-3">{{ $p['included'] }} deliveries included{{ $p['overage_text'] ? ', then '.$p['overage_text'].' each' : '' }}</div>
                    @endif
                    <ul class="list-unstyled small flex-grow-1">
                        @foreach($p['features'] as $f)<li class="mb-2">✓ {{ $f }}</li>@endforeach
                        @if($p['max_km'] && ! $p['contact_only'])<li class="mb-2 text-muted">Covers trips up to {{ $p['max_km'] }} km</li>@endif
                    </ul>
                    @if($p['contact_only'])
                        @if($salesEmail)<a class="btn btn-outline-dark w-100" href="mailto:{{ $salesEmail }}?subject={{ rawurlencode($p['name'].' plan') }}">Talk to us</a>@endif
                        @if($salesPhone)<div class="small text-center mt-2">or call {{ \App\Support\Phone::display($salesPhone) }}</div>@endif
                    @else
                        <a class="btn btn-brand w-100" href="{{ route('account.plan', ['plan' => $p['code']]) }}">Choose {{ $p['name'] }}</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted">Business plans are coming soon.</div>
        @endforelse
    </div>
    <div class="text-center small text-muted mt-4">The monthly fee is taken from your account wallet, which you can top up by card{{ config('region.country') === 'CA' ? ' or Interac e-Transfer' : '' }}. Cancel any time; your plan runs to the end of the month you paid for.</div>
</div>
@endsection
