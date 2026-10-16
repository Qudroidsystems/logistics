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
            @if(!empty($s['receiver_reply']))<div class="alert alert-info py-1 px-2 small mt-2 mb-0"><i class="ri-chat-1-line"></i> Receiver: <strong>{{ $s['receiver_reply']['label'] }}</strong>@if($s['receiver_reply']['note']) "{{ $s['receiver_reply']['note'] }}"@endif</div>@endif
            @if($s['instructions'])<div class="small mt-1">{{ $s['instructions'] }}</div>@endif
            @if($s['contact_name'] || $s['contact_phone'])
                <div class="small mt-1">{{ $s['contact_name'] }} @if($s['contact_phone'])<a href="tel:{{ $s['contact_phone'] }}">{{ $s['contact_phone'] }}</a>@endif</div>
            @endif
            <a class="btn btn-sm btn-outline-secondary mt-2" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $s['lat'] }},{{ $s['lng'] }}"><i class="ri-navigation-line"></i> Directions</a>

            @if($job['live'] && $isNext && in_array($job['status'], ['heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff', 'returning'], true))
            <hr>
            @php $req = array_values(array_diff($s['requires'], $s['waived'])); $stage = $s['type'] === 'pickup' ? 'pickup' : 'dropoff'; @endphp
            @if($s['requires'])
                <div class="small mb-2"><strong>Needed here:</strong> {{ implode(', ', $s['requires_labels']) }}</div>
            @endif
            @if(in_array('scan', $req, true) && count($job['packages']))
                @php $done = collect($job['packages'])->where($stage === 'pickup' ? 'scanned_pickup' : 'scanned_dropoff', true)->count(); @endphp
                <form method="POST" action="{{ route('driver.job.scan', $job['shipment']) }}" class="input-group mb-2">@csrf
                    <input type="hidden" name="stage" value="{{ $stage }}">
                    <input name="code" class="form-control" placeholder="Parcel code (PK…)" autocapitalize="characters" required>
                    <button class="btn btn-outline-primary">Scan</button>
                </form>
                <div class="small text-muted mb-2">{{ $done }} of {{ count($job['packages']) }} parcels scanned.</div>
            @endif
            <form method="POST" action="{{ route('driver.stop.complete', [$job['shipment'], $s['id']]) }}" enctype="multipart/form-data" class="stop-form">@csrf
                <input type="hidden" name="lat" class="geo-lat"><input type="hidden" name="lng" class="geo-lng">
                @if(in_array('code', $req, true))<label class="form-label small">Delivery code from the receiver</label><input name="otp" inputmode="numeric" maxlength="12" class="form-control form-control-lg mb-2" required>@endif
                @if($s['type'] === 'dropoff' || in_array('receiver_name', $req, true))
                    <label class="form-label small">Receiver's name @unless(in_array('receiver_name', $req, true))(optional)@endunless</label>
                    <input name="recipient_name" class="form-control mb-2" maxlength="120" @if(in_array('receiver_name', $req, true)) required @endif>
                    @if(!empty($job['authorised_receivers']) && in_array('receiver_name', $req, true))<div class="small text-muted mb-2">Only: {{ implode(', ', $job['authorised_receivers']) }}</div>@endif
                @endif
                @if(($s['cod_amount'] ?? 0) > 0)
                    <label class="form-label small">Cash collected (₦). Collect exactly {{ \App\Modules\Cod\CodService::naira((int) $s['cod_amount']) }}</label>
                    <input name="cod_naira" type="number" step="0.01" min="0" inputmode="decimal" class="form-control form-control-lg mb-2" required>
                @endif
                @if(in_array('receiver_id', $req, true))
                    <div class="row g-2 mb-2">
                        <div class="col-7"><select name="id_type" class="form-select" required><option value="">Receiver's ID</option>@foreach(['nin' => 'NIN', 'drivers_licence' => "Driver's licence", 'voters_card' => "Voter's card", 'passport' => 'Passport', 'staff_id' => 'Staff ID', 'other' => 'Other'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                        <div class="col-5"><input name="id_last4" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="Last 4" required></div>
                    </div>
                @endif
                @if(in_array('photo', $req, true))<label class="form-label small">Photo of the hand-over</label><input type="file" name="photo" accept="image/*" capture="environment" class="form-control mb-2" required>@endif
                @if(in_array('signature', $req, true))
                    <label class="form-label small">Receiver signs here</label>
                    <canvas class="sig-pad border rounded w-100 mb-1" height="160" style="touch-action:none;background:#fff"></canvas>
                    <input type="file" name="signature" class="d-none sig-file">
                    <button type="button" class="btn btn-sm btn-link p-0 mb-2 sig-clear">Clear signature</button>
                @endif
                <button class="btn btn-success btn-lg w-100">{{ $s['type'] === 'pickup' ? 'Picked up' : ($s['type'] === 'return' ? 'Parcel handed back' : 'Delivered') }}</button>
                <div class="small text-muted mt-1">You must be at the stop. Your location is checked.</div>
            </form>
            @foreach($s['exceptions'] as $e)
                <div class="small mt-2"><span class="badge {{ $e['status'] === 'approved' ? 'bg-success' : ($e['status'] === 'declined' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ ucfirst($e['status']) }}</span> Exception for {{ strtolower($e['label']) }}@if($e['decision_note']): {{ $e['decision_note'] }}@endif</div>
            @endforeach
            @if($req)
            <details class="mt-2"><summary class="small">Cannot provide one of these?</summary>
                <form method="POST" action="{{ route('driver.stop.exception', [$job['shipment'], $s['id']]) }}" class="mt-2">@csrf
                    <select name="method" class="form-select mb-2" required>@foreach($req as $m)<option value="{{ $m }}">{{ \App\Modules\Verification\VerificationPolicy::LABELS[$m] ?? $m }}</option>@endforeach</select>
                    <input name="reason" class="form-control mb-2" minlength="10" maxlength="1000" placeholder="What happened?" required>
                    <button class="btn btn-outline-secondary w-100">Ask my dispatcher to approve</button>
                </form>
            </details>
            @endif
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
    // Signature pad: drawn on a canvas, sent as a PNG file with the form.
    document.querySelectorAll('.stop-form').forEach(function (f) {
        var c = f.querySelector('.sig-pad'), drew = false;
        if (c) {
            c.width = c.offsetWidth;
            var g = c.getContext('2d'), down = false;
            g.lineWidth = 2.5; g.lineCap = 'round';
            function pt(e) { var r = c.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; }
            c.addEventListener('pointerdown', function (e) { down = true; var p = pt(e); g.beginPath(); g.moveTo(p[0], p[1]); });
            c.addEventListener('pointermove', function (e) { if (!down) return; var p = pt(e); g.lineTo(p[0], p[1]); g.stroke(); drew = true; });
            window.addEventListener('pointerup', function () { down = false; });
            f.querySelector('.sig-clear').addEventListener('click', function () { g.clearRect(0, 0, c.width, c.height); drew = false; });
        }
        f.addEventListener('submit', function (ev) {
            if (!last) { ev.preventDefault(); alert('Waiting for your location. Allow location access, then try again.'); return; }
            fill();
            if (c && !f.dataset.signed) {
                ev.preventDefault();
                if (!drew) { alert('Ask the receiver to sign first.'); return; }
                c.toBlob(function (b) {
                    var dt = new DataTransfer();
                    dt.items.add(new File([b], 'signature.png', {type: 'image/png'}));
                    f.querySelector('.sig-file').files = dt.files;
                    f.dataset.signed = '1';
                    f.submit();
                }, 'image/png');
            }
        });
    });
})();
</script>
@endif
@endsection
