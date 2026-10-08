@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Job" icon="ri-route-line" :subtitle="str_replace('_', ' ', ucfirst($job['status']))" :back="route('driver.home')" back-label="Today">
        <x-slot:pills>@if($job['payout_amount'])<span class="cb-meta-pill">{{ $naira($job['payout_amount']) }}</span>@endif</x-slot:pills>
    </x-cb.hero>
    @include('driver._flash')

    @if($job['live'] && $job['status'] === 'assigned')
        <form method="POST" action="{{ route('driver.job.start', $job['shipment']) }}" class="mb-3">@csrf<button class="btn btn-primary btn-lg w-100">Start trip to pickup</button></form>
    @endif

    @foreach($job['stops'] as $s)
        @php $isNext = $next && $next['id'] === $s['id']; @endphp
        <x-cb.card :title="ucfirst($s['type']).' '.$s['seq']" icon="{{ $s['type'] === 'pickup' ? 'ri-map-pin-line' : 'ri-flag-line' }}">
            <div class="d-flex justify-content-between"><strong>{{ $s['line1'] }}</strong><span class="badge bg-light text-dark">{{ $s['status'] }}</span></div>
            @if($s['landmark'])<div class="small text-muted">Near {{ $s['landmark'] }}</div>@endif
            @if($s['instructions'])<div class="small mt-1">{{ $s['instructions'] }}</div>@endif
            @if($s['contact_name'] || $s['contact_phone'])
                <div class="small mt-1">{{ $s['contact_name'] }} @if($s['contact_phone'])<a href="tel:{{ $s['contact_phone'] }}">{{ $s['contact_phone'] }}</a>@endif</div>
            @endif
            <a class="btn btn-sm btn-outline-secondary mt-2" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $s['lat'] }},{{ $s['lng'] }}"><i class="ri-navigation-line"></i> Directions</a>

            @if($job['live'] && $isNext && in_array($job['status'], ['heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'], true))
            <hr>
            <form method="POST" action="{{ route('driver.stop.complete', [$job['shipment'], $s['id']]) }}" enctype="multipart/form-data" class="stop-form">@csrf
                <input type="hidden" name="lat" class="geo-lat"><input type="hidden" name="lng" class="geo-lng">
                @if($s['type'] === 'dropoff')
                    @if($s['needs_code'])<label class="form-label small">Delivery code from the receiver</label><input name="otp" inputmode="numeric" maxlength="12" class="form-control form-control-lg mb-2" required>@endif
                    <label class="form-label small">Receiver's name (optional)</label><input name="recipient_name" class="form-control mb-2" maxlength="120">
                    @unless($s['needs_code'])<label class="form-label small">Photo of the delivery</label><input type="file" name="photo" accept="image/*" capture="environment" class="form-control mb-2" required>@endunless
                @endif
                <button class="btn btn-success btn-lg w-100">{{ $s['type'] === 'pickup' ? 'Picked up' : 'Delivered' }}</button>
                <div class="small text-muted mt-1">You must be at the stop. Your location is checked.</div>
            </form>
            @endif
        </x-cb.card>
    @endforeach
</div></div></div>

@if($job['live'])
<script>
(function () {
    var last = null, token = @json(csrf_token()), url = @json(route('driver.location'));
    function ping() {
        if (!last) return;
        fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({pings: [{lat: last.latitude, lng: last.longitude, at: new Date().toISOString(), accuracy: last.accuracy, speed: last.speed, heading: last.heading}]})})
            .catch(function () {});
    }
    function fill() {
        document.querySelectorAll('.geo-lat').forEach(function (e) { if (last) e.value = last.latitude; });
        document.querySelectorAll('.geo-lng').forEach(function (e) { if (last) e.value = last.longitude; });
    }
    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(function (p) { last = p.coords; fill(); }, function () {}, {enableHighAccuracy: true, maximumAge: 5000});
        setInterval(ping, 10000);
    }
    document.querySelectorAll('.stop-form').forEach(function (f) {
        f.addEventListener('submit', function (ev) {
            if (!last) { ev.preventDefault(); alert('Waiting for your location. Allow location access, then try again.'); }
            else { fill(); }
        });
    });
})();
</script>
@endif
@endsection
