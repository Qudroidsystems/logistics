@extends('public._layout', ['title' => $brand['name'].' · Same-day delivery, instant price'])
@section('content')
<section style="background: linear-gradient(135deg, var(--brand2), var(--brand));" class="text-white">
    <div class="container py-5">
        <div class="row g-4 align-items-start">
            <div class="col-lg-6 pt-lg-4">
                <h1 class="display-5 fw-bold mb-3">Deliveries across {{ config('region.country_name') }}, priced in seconds.</h1>
                <p class="lead opacity-75 mb-4">{{ $brand['tagline'] ?: 'Same-day, next-day or scheduled. Enter two addresses, see your price, book and track your driver live.' }}</p>
                <ul class="list-unstyled opacity-75 mb-4">
                    <li class="mb-2">✓ Upfront price, no surprises</li>
                    <li class="mb-2">✓ Live tracking and proof of delivery</li>
                    <li class="mb-2">✓ Pay by card{{ config('region.country') === 'CA' ? ' or Interac e-Transfer' : ' or bank transfer' }}</li>
                </ul>
                <form class="d-flex gap-2 bg-white rounded-3 p-2" style="max-width: 420px" method="get" action="{{ route('track.lookup') }}">
                    <input class="form-control border-0" name="code" placeholder="Tracking code" aria-label="Tracking code" required>
                    <button class="btn btn-dark">Track</button>
                </form>
            </div>
            <div class="col-lg-6">
                @include('public._quote-form')
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="row g-4 text-center">
        <div class="col-md-4"><div class="pub-card h-100"><div class="fs-2">1</div><h5 class="fw-bold">Get a price</h5><p class="text-muted mb-0">Pickup, drop-off, size and when. The price is held for 10 minutes.</p></div></div>
        <div class="col-md-4"><div class="pub-card h-100"><div class="fs-2">2</div><h5 class="fw-bold">Book and pay</h5><p class="text-muted mb-0">Add the contact names and pay online. Your delivery is sent to a nearby driver.</p></div></div>
        <div class="col-md-4"><div class="pub-card h-100"><div class="fs-2">3</div><h5 class="fw-bold">Track it live</h5><p class="text-muted mb-0">Follow the driver on the map and get a photo when it's delivered.</p></div></div>
    </div>
    <div class="row g-4 mt-2">
        <div class="col-md-4"><div class="pub-card h-100"><h5 class="fw-bold">For businesses</h5><p class="text-muted">Send parcels every week? A monthly plan includes your deliveries at a fixed price, with priority dispatch on Growth.</p><a class="btn btn-outline-dark" href="{{ route('business') }}">See business plans</a></div></div>
        <div class="col-md-4"><div class="pub-card h-100"><h5 class="fw-bold">Run a store?</h5><p class="text-muted">Become a drop-off point. Senders hand parcels in at your counter, and you earn on each one.</p><a class="btn btn-outline-dark" href="{{ route('partners') }}">Become a partner</a></div></div>
        <div class="col-md-4"><div class="pub-card h-100"><h5 class="fw-bold">Drive with us</h5><p class="text-muted">Have a car and a licence? Pick up deliveries on your own schedule.</p><a class="btn btn-outline-dark" href="{{ route('drive') }}">Apply to drive</a></div></div>
    </div>
</section>
@php $cities = \App\Http\Controllers\SeoController::cities(); @endphp
@if($cities->count())
<section class="container pb-4">
    <h2 class="h6 fw-bold text-muted text-uppercase">Where we deliver</h2>
    <div class="d-flex flex-wrap gap-2">@foreach($cities as $c)<a class="btn btn-sm btn-outline-secondary" href="{{ route('city', $c->slug) }}">{{ $c->name }}</a>@endforeach</div>
</section>
@endif
<script type="application/ld+json">
{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $brand['name'], 'url' => route('landing'),
    'logo' => $brand['logo_url'], 'contactPoint' => array_filter(['@type' => 'ContactPoint', 'contactType' => 'customer service',
    'telephone' => config('booking.contact_phone'), 'email' => config('booking.contact_email')])], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endsection
