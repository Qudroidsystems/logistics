@extends('layouts.master')

@section('content')
@php $selType = old('type', 'parcel'); @endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Ask for a delivery" icon="ri-add-circle-line" subtitle="Tell providers what you need moved. They reply with prices, and you choose." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    @if(count($cities) === 0)<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>No city has been set up yet, so requests cannot be sent. An administrator needs to add one first.</div></div>@endif

    <form method="POST" action="{{ route('account.request.create') }}" id="reqForm">@csrf
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
                </x-cb.card>
            </div>

            <div class="col-lg-7">
                <x-cb.card title="Where" icon="ri-map-pin-line">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <div class="fw-semibold small mb-1"><i class="ri-map-pin-2-fill text-success"></i> Pickup</div>
                            <input name="pickup[line1]" class="form-control mb-1" placeholder="Address or landmark" value="{{ old('pickup.line1') }}" required>
                            <input name="pickup[contact_name]" class="form-control mb-1" placeholder="Contact name" value="{{ old('pickup.contact_name') }}">
                            <input name="pickup[contact_phone]" class="form-control mb-1" placeholder="Contact phone" value="{{ old('pickup.contact_phone') }}">
                            <input type="hidden" name="pickup[lat]" id="pLat" value="{{ old('pickup.lat') }}"><input type="hidden" name="pickup[lng]" id="pLng" value="{{ old('pickup.lng') }}">
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold small mb-1"><i class="ri-map-pin-2-fill text-danger"></i> Drop-off</div>
                            <input name="dropoff[line1]" class="form-control mb-1" placeholder="Address or landmark" value="{{ old('dropoff.line1') }}" required>
                            <input name="dropoff[contact_name]" class="form-control mb-1" placeholder="Contact name" value="{{ old('dropoff.contact_name') }}">
                            <input name="dropoff[contact_phone]" class="form-control mb-1" placeholder="Contact phone" value="{{ old('dropoff.contact_phone') }}">
                            <input type="hidden" name="dropoff[lat]" id="dLat" value="{{ old('dropoff.lat') }}"><input type="hidden" name="dropoff[lng]" id="dLng" value="{{ old('dropoff.lng') }}">
                        </div>
                    </div>
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
    $('reqForm').onsubmit = function (e) {
        if (!$('pLat').value || !$('dLat').value) { e.preventDefault(); $('mapHint').textContent = 'Please mark both the pickup and the drop-off on the map.'; $('mapHint').classList.add('text-danger'); return false; }
        $('sendBtn').disabled = true;
    };
})();
</script>
@endsection
