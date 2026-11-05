@extends('public._layout', [
    'title' => 'Same-day delivery in '.$city->name.' · '.$brand['name'],
    'description' => 'Courier and same-day delivery in '.$city->name.': instant price, a driver in minutes, live tracking and proof of delivery. Get your price now.',
])
@section('content')
<section style="background: linear-gradient(135deg, var(--brand2), var(--brand));" class="text-white">
    <div class="container py-5">
        <div class="row g-4 align-items-start">
            <div class="col-lg-6 pt-lg-4">
                <h1 class="display-6 fw-bold mb-3">Same-day delivery in {{ $city->name }}</h1>
                <p class="lead opacity-75">Parcels, documents and business deliveries across {{ $city->name }} and nearby. See your price before you book, then follow your driver live.</p>
                <ul class="list-unstyled opacity-75 mb-0">
                    <li class="mb-2">✓ Same-day, next-day or a time you choose</li>
                    <li class="mb-2">✓ Priced on the real road route, no surprises</li>
                    <li class="mb-2">✓ Photo and signature when it's delivered</li>
                </ul>
            </div>
            <div class="col-lg-6">@include('public._quote-form')</div>
        </div>
    </div>
</section>
<section class="container py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <h2 class="h4 fw-bold">Delivery in {{ $city->name }}, the simple way</h2>
            <p class="text-muted">Enter the two addresses for an instant price based on the road route. Book in a minute and pay online. A nearby driver collects the parcel and you can watch them on the map until it's delivered.</p>
            <h3 class="h6 fw-bold mt-4">Questions people ask</h3>
            <dl>
                <dt class="mt-3">How fast is same-day delivery in {{ $city->name }}?</dt><dd class="text-muted">Most same-day trips are picked up within the hour. You'll see the driver's arrival time once one is on the way.</dd>
                <dt class="mt-3">How is the price worked out?</dt><dd class="text-muted">From the road distance and time, the size of the item and when you need it. You see the full price before you book.</dd>
                <dt class="mt-3">I send parcels every week. Is there a plan?</dt><dd class="text-muted">Yes: <a href="{{ route('business') }}">business plans</a> include a number of deliveries each month at a fixed price.</dd>
            </dl>
        </div>
        <div class="col-lg-5">
            @if(count($stores))
            <div class="pub-card">
                <h2 class="h6 fw-bold">Drop-off points near {{ $city->name }}</h2>
                <ul class="list-unstyled small mb-0">
                    @foreach(array_slice($stores, 0, 8) as $s)<li class="py-1 border-top"><strong>{{ $s['name'] }}</strong><br><span class="text-muted">{{ $s['address'] }}@if($s['hours']) · {{ $s['hours'] }}@endif</span></li>@endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</section>
<script type="application/ld+json">
{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Service', 'serviceType' => 'Courier and same-day delivery', 'name' => 'Same-day delivery in '.$city->name,
    'provider' => ['@type' => 'Organization', 'name' => $brand['name'], 'url' => route('landing')], 'areaServed' => ['@type' => 'City', 'name' => $city->name],
    'url' => route('city', $city->slug)], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endsection
