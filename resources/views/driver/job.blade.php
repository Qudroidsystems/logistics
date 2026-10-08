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

            @if($job['live'] && $isNext && in_array($job['status'], ['heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff', 'returning'], true))
            <hr>
            <form method="POST" action="{{ route('driver.stop.complete', [$job['shipment'], $s['id']]) }}" enctype="multipart/form-data" class="stop-form">@csrf
                <input type="hidden" name="lat" class="geo-lat"><input type="hidden" name="lng" class="geo-lng">
                @if($s['type'] === 'dropoff')
                    @if($s['needs_code'])<label class="form-label small">Delivery code from the receiver</label><input name="otp" inputmode="numeric" maxlength="12" class="form-control form-control-lg mb-2" required>@endif
                    <label class="form-label small">Receiver's name (optional)</label><input name="recipient_name" class="form-control mb-2" maxlength="120">
                    @unless($s['needs_code'])<label class="form-label small">Photo of the delivery</label><input type="file" name="photo" accept="image/*" capture="environment" class="form-control mb-2" required>@endunless
                @endif
                <button class="btn btn-success btn-lg w-100">{{ $s['type'] === 'pickup' ? 'Picked up' : ($s['type'] === 'return' ? 'Parcel handed back' : 'Delivered') }}</button>
                <div class="small text-muted mt-1">You must be at the stop. Your location is checked.</div>
            </form>
            @endif
        </x-cb.card>
    @endforeach
    @if($job['live'])
    <x-cb.card title="Something wrong?" icon="ri-error-warning-line">
        <form method="POST" action="{{ route('driver.job.issue', $job['shipment']) }}" class="mb-3">@csrf
            <select name="reason" class="form-select mb-2" required>
                <option value="">What went wrong?</option>
                @foreach($issues as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <input name="note" class="form-control mb-2" maxlength="500" placeholder="Tell your company more (optional)">
            <button class="btn btn-outline-warning w-100">Tell my company</button>
        </form>
        @if($job['status'] === 'at_dropoff')
        <form method="POST" action="{{ route('driver.job.fail', $job['shipment']) }}" class="mb-3" onsubmit="return confirm('Close this delivery as failed? The agreement terms will be applied.')">@csrf
            <div class="small text-muted mb-1">Cannot hand over the parcel? You can only do this after waiting at the drop-off for the time in the agreement.</div>
            <select name="reason" class="form-select mb-2" required>
                <option value="">Why could you not deliver?</option>
                @foreach($failReasons as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <input name="note" class="form-control mb-2" maxlength="500" placeholder="Anything else to add (optional)">
            <button class="btn btn-danger w-100">Delivery failed</button>
        </form>
        @endif
        @if(in_array($job['status'], ['assigned', 'heading_to_pickup', 'at_pickup'], true))
        <form method="POST" action="{{ route('driver.job.release', $job['shipment']) }}" onsubmit="return confirm('Hand this job back to your dispatcher?')">@csrf
            <select name="reason" class="form-select mb-2" required>
                <option value="">Why can you not do this job?</option>
                @foreach($issues as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <button class="btn btn-outline-danger w-100">I cannot do this job</button>
        </form>
        @endif
    </x-cb.card>
    @endif
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
