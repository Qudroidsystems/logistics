@extends('layouts.master')

@section('content')
@php $selType = old('type', 'parcel'); @endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Ask for a delivery" icon="ri-add-circle-line" subtitle="Tell providers what you need moved. They reply with prices, and you choose." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    @if(count($cities) === 0)<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>No city has been set up yet, so requests cannot be sent. An administrator needs to add one first.</div></div>@endif

    <form method="POST" action="{{ route('account.request.create') }}" id="reqForm" enctype="multipart/form-data">@csrf
        @if($provider)<input type="hidden" name="provider" value="{{ $provider->id }}"><div class="cb-banner info"><i class="ri-send-plane-line"></i><div>This request goes only to <strong>{{ $provider->display_name }}</strong>.</div></div>@endif
        <div class="row g-3">
            <div class="col-lg-5">
                <x-cb.card title="What" icon="ri-box-3-line">
                    <label class="form-label small">Kind of job</label>
                    <select name="type" class="form-select mb-2" id="typeSel" required>
                        @foreach(['parcel' => 'Send a parcel', 'errand' => 'Run an errand', 'shopping' => 'Shop for me', 'freight' => 'Freight', 'moving' => 'Moving', 'bulk' => 'Bulk delivery'] as $k => $v)<option value="{{ $k }}" @selected($selType === $k)>{{ $v }}</option>@endforeach
                    </select>
                    <label class="form-label small">Service</label>
                    <select name="service_type_id" class="form-select mb-2" required>
                        @foreach($serviceTypes as $t)<option value="{{ $t->id }}" @selected(old('service_type_id', request('service_type_id')) == $t->id)>{{ $t->name }}</option>@endforeach
                    </select>
                    <label class="form-label small">City</label>
                    <select name="city_id" class="form-select mb-2" id="citySel" required>
                        @foreach($cities as $c)<option value="{{ $c->id }}" data-lat="{{ $c->lat }}" data-lng="{{ $c->lng }}" @selected(old('city_id') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                    <label class="form-label small">Vehicle <span class="text-muted">(optional)</span></label>
                    <select name="vehicle_type_id" class="form-select mb-2"><option value="">Any</option>@foreach($vehicleTypes as $v)<option value="{{ $v->id }}" @selected(old('vehicle_type_id') == $v->id)>{{ $v->name }}</option>@endforeach</select>
                    <label class="form-label small" id="itemsLabel">What is being sent? <span class="text-muted">(one item per line)</span></label>
                    <textarea name="items" rows="3" class="form-control mb-2" maxlength="2000">{{ old('items') }}</textarea>
                    <div id="shopBox" class="d-none">
                        <label class="form-label small">Most you want to spend on goods (₦)</label>
                        <input type="number" step="0.01" min="1" name="budget_cap_naira" class="form-control mb-2" value="{{ old('budget_cap_naira') }}">
                        <label class="form-label small">If something is unavailable</label>
                        <select name="substitution_policy" class="form-select mb-2">@foreach(['ask' => 'Ask me first', 'allow' => 'Pick a close substitute', 'none' => 'Skip it'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small">Budget from (₦)</label><input type="number" step="0.01" min="0" name="budget_min_naira" class="form-control" value="{{ old('budget_min_naira') }}"></div>
                        <div class="col-6"><label class="form-label small">to (₦)</label><input type="number" step="0.01" min="0" name="budget_max_naira" class="form-control" value="{{ old('budget_max_naira') }}"></div>
                    </div>
                    <label class="form-label small mt-2">Needed by <span class="text-muted">(optional)</span></label>
                    <input type="datetime-local" name="needed_by" class="form-control" value="{{ old('needed_by') }}">
                    <label class="form-label small mt-2">When should it be collected?</label>
                    <div class="btn-group w-100 mb-2" role="group">
                        <input type="radio" class="btn-check" name="when" value="now" id="whenNow" @checked(old('when', 'now') === 'now')><label class="btn btn-outline-secondary btn-sm" for="whenNow">As soon as possible</label>
                        <input type="radio" class="btn-check" name="when" value="later" id="whenLater" @checked(old('when') === 'later')><label class="btn btn-outline-secondary btn-sm" for="whenLater">Schedule for later</label>
                    </div>
                    <input type="datetime-local" name="pickup_at" id="pickupAt" class="form-control mb-2 {{ old('when') === 'later' ? '' : 'd-none' }}" value="{{ old('pickup_at') }}" min="{{ now()->addMinutes(30)->format('Y-m-d\\TH:i') }}" max="{{ now()->addDays(30)->format('Y-m-d\\TH:i') }}">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="rush" value="1" id="rushBox" @checked(old('rush'))><label class="form-check-label small" for="rushBox">Rush job: I need it moved as fast as possible</label></div>
                </x-cb.card>
                <x-cb.card title="Photos and documents" icon="ri-attachment-2" class="mt-3">
                    <p class="small text-muted mb-2">Optional. A photo of the item, a waybill or an invoice helps providers price it. Up to {{ \App\Modules\Booking\RequestAttachments::MAX_FILES }} files, 10 MB each.</p>
                    <input type="file" name="files[]" class="form-control" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                </x-cb.card>
                <x-cb.card title="How should the hand-over be confirmed?" icon="ri-shield-check-line" class="mt-3">
                    <p class="small text-muted mb-2">The provider's own checks always apply. Tick anything extra you want; it becomes part of the agreement and cannot be dropped later.</p>
                    @foreach($proofOptions as $k => $label)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="proofs[]" value="{{ $k }}" id="pf_{{ $k }}" @checked(in_array($k, old('proofs', []), true))><label class="form-check-label small" for="pf_{{ $k }}">{{ $label }}</label></div>
                    @endforeach
                    <label class="form-label small mt-2">Only these people may receive it <span class="text-muted">(optional, comma separated)</span></label>
                    <input name="authorised_receivers" class="form-control" placeholder="e.g. Ada Obi, Musa Bello" value="{{ old('authorised_receivers') }}">
                </x-cb.card>
            </div>

            <div class="col-lg-7">
                <x-cb.card title="Where" icon="ri-map-pin-line">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <div class="fw-semibold small mb-1"><i class="ri-map-pin-2-fill text-success"></i> Pickup</div>
                            @if(count($savedAddresses))<select class="form-select form-select-sm mb-1 saved-pick" data-kind="p"><option value="">Choose a saved place…</option>@foreach($savedAddresses as $sa)<option value="{{ $loop->index }}">{{ $sa['label'] ?: $sa['line1'] }}</option>@endforeach</select>@endif
                            <input name="pickup[line1]" class="form-control mb-1" placeholder="Address or landmark" value="{{ old('pickup.line1') }}" required>
                            <input name="pickup[contact_name]" class="form-control mb-1" placeholder="Contact name" value="{{ old('pickup.contact_name') }}">
                            <input name="pickup[contact_phone]" class="form-control mb-1" placeholder="Contact phone" value="{{ old('pickup.contact_phone') }}">
                            <input type="hidden" name="pickup[lat]" id="pLat" value="{{ old('pickup.lat') }}"><input type="hidden" name="pickup[lng]" id="pLng" value="{{ old('pickup.lng') }}">
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold small mb-1"><i class="ri-map-pin-2-fill text-danger"></i> Drop-off</div>
                            @if(count($savedAddresses))<select class="form-select form-select-sm mb-1 saved-pick" data-kind="d"><option value="">Choose a saved place…</option>@foreach($savedAddresses as $sa)<option value="{{ $loop->index }}">{{ $sa['label'] ?: $sa['line1'] }}</option>@endforeach</select>@endif
                            <input name="dropoff[line1]" class="form-control mb-1" placeholder="Address or landmark" value="{{ old('dropoff.line1') }}" required>
                            <input name="dropoff[contact_name]" class="form-control mb-1" placeholder="Contact name" value="{{ old('dropoff.contact_name') }}">
                            <input name="dropoff[contact_phone]" class="form-control mb-1" placeholder="Contact phone" value="{{ old('dropoff.contact_phone') }}">
                            <input type="hidden" name="dropoff[lat]" id="dLat" value="{{ old('dropoff.lat') }}"><input type="hidden" name="dropoff[lng]" id="dLng" value="{{ old('dropoff.lng') }}">
                        </div>
                    </div>
                    <div id="extraStops"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="addStop"><i class="ri-add-line"></i> Add another stop</button>
                    <div class="form-text mb-2">Extra stops go between the pickup and the final drop-off, in the order you add them.</div>
                    <div class="btn-group mb-2" role="group">
                        <button type="button" class="btn btn-sm btn-outline-success" id="setP">Mark pickup on the map</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="setD">Mark drop-off on the map</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="locMe">Use my location for pickup</button>
                    </div>
                    <div id="map" style="height:340px;border-radius:8px"></div>
                    <div class="form-text" id="mapHint">Choose a button, then tap the map where the place is. The address above is what the rider reads; the map pin is what the price is based on.</div>
                </x-cb.card>
                <button class="btn btn-primary btn-lg mt-3 w-100" id="sendBtn">Send request</button>
            </div>
        </div>
    </form>
</div></div></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var city = $('citySel').selectedOptions[0];
    var start = [parseFloat(city.dataset.lat) || 9.082, parseFloat(city.dataset.lng) || 8.6753], zoom = city.dataset.lat ? 13 : 6;
    var map = L.map('map').setView(start, zoom);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    var pins = {}, mode = null;
    function pin(kind, lat, lng) {
        var colour = kind === 'p' ? '#198754' : '#dc3545';
        if (pins[kind]) { pins[kind].setLatLng([lat, lng]); }
        else { pins[kind] = L.circleMarker([lat, lng], {radius: 9, color: colour, fillColor: colour, fillOpacity: .8}).addTo(map); }
        $(kind + 'Lat').value = lat.toFixed(6); $(kind + 'Lng').value = lng.toFixed(6);
    }
    ['p', 'd'].forEach(function (k) { var a = parseFloat($(k + 'Lat').value), b = parseFloat($(k + 'Lng').value); if (a && b) { pin(k, a, b); } });
    function choose(k, text) { mode = k; $('mapHint').textContent = text; }
    $('setP').onclick = function () { choose('p', 'Tap the map to place the pickup pin.'); };
    $('setD').onclick = function () { choose('d', 'Tap the map to place the drop-off pin.'); };
    map.on('click', function (e) { if (mode) { pin(mode, e.latlng.lat, e.latlng.lng); $('mapHint').textContent = 'Pin placed. Tap again to move it.'; } });
    $('locMe').onclick = function () {
        if (!navigator.geolocation) { $('mapHint').textContent = 'Your browser cannot share its location. Mark the pickup on the map instead.'; return; }
        navigator.geolocation.getCurrentPosition(function (p) { pin('p', p.coords.latitude, p.coords.longitude); map.setView([p.coords.latitude, p.coords.longitude], 15); },
            function () { $('mapHint').textContent = 'We could not get your location. Mark the pickup on the map instead.'; });
    };
    $('citySel').onchange = function () { var o = this.selectedOptions[0]; if (o.dataset.lat) { map.setView([parseFloat(o.dataset.lat), parseFloat(o.dataset.lng)], 13); } };
    function shop() { var s = $('typeSel').value === 'shopping'; $('shopBox').classList.toggle('d-none', !s); $('itemsLabel').firstChild.textContent = s ? 'Shopping list ' : 'What is being sent? '; }
    $('typeSel').onchange = shop; shop();
    // Saved places fill the address, contact and pin in one go.
    var saved = @json($savedAddresses);
    document.querySelectorAll('.saved-pick').forEach(function (sel) {
        sel.onchange = function () {
            var a = saved[this.value], k = this.dataset.kind, side = k === 'p' ? 'pickup' : 'dropoff';
            if (!a) return;
            document.querySelector('[name="' + side + '[line1]"]').value = a.line1;
            document.querySelector('[name="' + side + '[contact_name]"]').value = a.contact_name || '';
            document.querySelector('[name="' + side + '[contact_phone]"]').value = a.contact_phone || '';
            pin(k, a.lat, a.lng); map.setView([a.lat, a.lng], 15);
        };
    });
    // Schedule for later.
    document.querySelectorAll('[name="when"]').forEach(function (r) { r.onchange = function () { $('pickupAt').classList.toggle('d-none', this.value !== 'later'); $('pickupAt').required = this.value === 'later'; }; });
    // Extra stops: each has its own pin, numbered on the map.
    var stopN = 0, stopPins = {};
    $('addStop').onclick = function () {
        if (document.querySelectorAll('.extra-stop').length >= {{ \App\Modules\Booking\StopList::MAX_STOPS - 2 }}) { return; }
        var i = stopN++, row = document.createElement('div');
        row.className = 'extra-stop border rounded p-2 mb-2';
        row.innerHTML = '<div class="d-flex gap-2 mb-1"><select name="stops[' + i + '][type]" class="form-select form-select-sm" style="max-width:140px"><option value="dropoff">Drop-off</option><option value="pickup">Pickup</option></select>'
            + '<input name="stops[' + i + '][line1]" class="form-control form-control-sm" placeholder="Address or landmark" required>'
            + '<button type="button" class="btn btn-sm btn-outline-secondary mark">Pin</button><button type="button" class="btn btn-sm btn-outline-danger del">&times;</button></div>'
            + '<div class="d-flex gap-2"><input name="stops[' + i + '][contact_name]" class="form-control form-control-sm" placeholder="Contact name"><input name="stops[' + i + '][contact_phone]" class="form-control form-control-sm" placeholder="Contact phone"></div>'
            + '<input type="hidden" name="stops[' + i + '][lat]" id="s' + i + 'Lat"><input type="hidden" name="stops[' + i + '][lng]" id="s' + i + 'Lng">';
        row.querySelector('.mark').onclick = function () { choose('s' + i, 'Tap the map to place stop ' + (i + 1) + '.'); };
        row.querySelector('.del').onclick = function () { if (stopPins['s' + i]) { map.removeLayer(stopPins['s' + i]); } row.remove(); };
        $('extraStops').appendChild(row);
        choose('s' + i, 'Tap the map to place the new stop.');
    };
    var basePin = pin;
    pin = function (kind, lat, lng) {
        if (kind.charAt(0) !== 's') { return basePin(kind, lat, lng); }
        if (stopPins[kind]) { stopPins[kind].setLatLng([lat, lng]); }
        else { stopPins[kind] = L.circleMarker([lat, lng], {radius: 7, color: '#0d6efd', fillColor: '#0d6efd', fillOpacity: .8}).addTo(map); }
        $(kind + 'Lat').value = lat.toFixed(6); $(kind + 'Lng').value = lng.toFixed(6);
    };
    $('reqForm').onsubmit = function (e) {
        var unpinned = Array.prototype.some.call(document.querySelectorAll('.extra-stop input[id$="Lat"]'), function (x) { return !x.value; });
        if (unpinned) { e.preventDefault(); $('mapHint').textContent = 'Please place a pin for every extra stop (use its Pin button).'; $('mapHint').classList.add('text-danger'); return false; }
        if (!$('pLat').value || !$('dLat').value) { e.preventDefault(); $('mapHint').textContent = 'Please mark both the pickup and the drop-off on the map.'; $('mapHint').classList.add('text-danger'); return false; }
        $('sendBtn').disabled = true;
    };
})();
</script>
@endsection
