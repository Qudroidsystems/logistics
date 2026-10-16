@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="New booking" icon="ri-add-box-line" subtitle="Take a delivery for a customer who called, walked in or sent a message." :back="$back" back-label="Back" />
    @if(request()->routeIs('provider.*'))@include('provider._flash')@else @include('ops._flash')@endif

    @if(session('booked'))
        <div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>
            Booking {{ session('booked') }} is saved and waiting for payment.
            @if(session('payment_url'))
                Send the customer this link: <a href="{{ session('payment_url') }}" target="_blank" rel="noopener" class="fw-semibold text-break" id="payLink">{{ session('payment_url') }}</a>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('payLink').href); this.textContent='Copied'">Copy link</button>
            @endif
        </div></div>
    @endif
    @if(count($cities) === 0)<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>No city has been set up yet, so bookings cannot be taken. Add one under Cities and zones first.</div></div>@endif

    <form method="POST" action="{{ $action }}" id="bookForm">@csrf
        <div class="row g-3">
            <div class="col-lg-5">
                <x-cb.card title="Customer" icon="ri-user-3-line">
                    <div class="form-text mb-2">We look the customer up by phone, then email. A new customer gets an account they can sign in to later.</div>
                    <input name="customer[phone]" class="form-control mb-2" placeholder="Phone number" value="{{ old('customer.phone') }}" inputmode="tel" autocomplete="off">
                    <input name="customer[email]" type="email" class="form-control mb-2" placeholder="Email (needed for a payment link)" value="{{ old('customer.email') }}" autocomplete="off">
                    <input name="customer[name]" class="form-control" placeholder="Full name (for a new customer)" value="{{ old('customer.name') }}" autocomplete="off">
                </x-cb.card>

                <x-cb.card title="The job" icon="ri-box-3-line">
                    @if($providers)
                        <label class="form-label small">Provider</label>
                        <select name="provider" class="form-select mb-2" id="providerSel" required>
                            <option value="">Choose who does the delivery</option>
                            @foreach($providers as $p)<option value="{{ $p->public_id }}" @selected(old('provider') === $p->public_id)>{{ $p->display_name }}</option>@endforeach
                        </select>
                    @endif
                    <label class="form-label small">Service</label>
                    <select name="service_type_id" class="form-select mb-2" required>
                        @foreach($serviceTypes as $t)<option value="{{ $t->id }}" @selected(old('service_type_id') == $t->id)>{{ $t->name }}</option>@endforeach
                    </select>
                    <label class="form-label small">City</label>
                    <select name="city_id" class="form-select mb-2" id="citySel" required>
                        @foreach($cities as $c)<option value="{{ $c->id }}" data-lat="{{ $c->lat }}" data-lng="{{ $c->lng }}" @selected(old('city_id') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                    <label class="form-label small">Vehicle <span class="text-muted">(optional)</span></label>
                    <select name="vehicle_type_id" class="form-select mb-2"><option value="">Any</option>@foreach($vehicleTypes as $v)<option value="{{ $v->id }}" @selected(old('vehicle_type_id') == $v->id)>{{ $v->name }}</option>@endforeach</select>
                    <label class="form-label small">What is being sent <span class="text-muted">(one parcel per line)</span></label>
                    <textarea name="items" rows="3" class="form-control mb-2" maxlength="2000" placeholder="Box of shoes&#10;Envelope">{{ old('items') }}</textarea>
                    <label class="form-label small">Total weight in kg <span class="text-muted">(optional)</span></label>
                    <input type="number" step="0.1" min="0" name="weight_kg" class="form-control mb-2" value="{{ old('weight_kg') }}">
                    <label class="form-label small">Cash on delivery (₦) <span class="text-muted">(optional)</span></label>
                    <input type="number" step="0.01" min="0" name="cod_naira" class="form-control mb-1" value="{{ old('cod_naira') }}" placeholder="Goods money to collect from the receiver">
                    <div class="form-text mb-2">The rider collects this at the door and hands it in at your office; you pay it to the sender.</div>
                    <label class="form-label small">Note for the team <span class="text-muted">(optional)</span></label>
                    <input name="note" class="form-control" maxlength="500" value="{{ old('note') }}">
                </x-cb.card>

                <x-cb.card title="Price and payment" icon="ri-money-dollar-circle-line">
                    <label class="form-label small">Price (₦)</label>
                    <div class="input-group mb-1">
                        <input type="number" step="0.01" min="1" name="price_naira" id="priceIn" class="form-control" value="{{ old('price_naira') }}" placeholder="Leave empty to use the price list">
                        <button type="button" class="btn btn-outline-primary" id="priceBtn">Work out price</button>
                    </div>
                    <div class="form-text mb-2" id="priceHint">Uses the price list for this service and city, from the pins on the map.</div>
                    <label class="form-label small">How the customer pays</label>
                    @foreach($payments as $k => $label)
                        @continue($k === 'account' && empty($businessAccounts))
                        <div class="form-check"><input class="form-check-input" type="radio" name="payment" id="pay_{{ $k }}" value="{{ $k }}" @checked(old('payment', 'link') === $k)><label class="form-check-label" for="pay_{{ $k }}">{{ $label }}</label></div>
                    @endforeach
                    @if(! empty($businessAccounts))
                        <select name="business_account" class="form-select form-select-sm mt-1" id="bizAccount">
                            <option value="">Business account to bill</option>
                            @foreach($businessAccounts as $ba)<option value="{{ $ba->public_id }}" @selected(old('business_account') === $ba->public_id)>{{ $ba->name }}</option>@endforeach
                        </select>
                    @endif
                    <div class="form-text">Cash and bank transfer mean you already have the money (or will collect it at pickup). On account goes on the business's monthly invoice. Either way the job goes to dispatch straight away.</div>
                    @if(count($drivers))
                        <label class="form-label small mt-3">Give it to <span class="text-muted">(optional)</span></label>
                        <select name="driver_user_id" class="form-select">
                            <option value="">Offer it to drivers automatically</option>
                            @foreach($drivers as $d)<option value="{{ $d['user_id'] }}" @selected(old('driver_user_id') == $d['user_id'])>{{ $d['name'] }} · {{ $d['active_jobs'] }}/{{ $d['max_active_jobs'] }} · {{ str_replace('_', ' ', $d['availability']) }}</option>@endforeach
                        </select>
                        <div class="form-text">Not for a payment link: a link booking goes to dispatch once it is paid.</div>
                    @endif
                </x-cb.card>
            </div>

            <div class="col-lg-7">
                <x-cb.card title="Where" icon="ri-map-pin-line">
                    <div class="row g-2 mb-2">
                        @foreach(['pickup' => ['Pickup', 'success', 'p'], 'dropoff' => ['Drop-off', 'danger', 'd']] as $k => [$label, $colour, $short])
                            <div class="col-md-6">
                                <div class="fw-semibold small mb-1"><i class="ri-map-pin-2-fill text-{{ $colour }}"></i> {{ $label }}</div>
                                @if($k === 'dropoff' && count($pickupPoints ?? []))
                                    <select name="dropoff[pickup_point]" id="dPoint" class="form-select mb-1">
                                        <option value="">To the receiver's address</option>
                                        @foreach($pickupPoints as $pp)<option value="{{ $pp->code }}" data-line="{{ $pp->name }}, {{ $pp->line1 }}" data-lat="{{ $pp->lat }}" data-lng="{{ $pp->lng }}" @selected(old('dropoff.pickup_point') === $pp->code)>To pickup point {{ $pp->name }} ({{ $pp->code }})</option>@endforeach
                                    </select>
                                @endif
                                <input name="{{ $k }}[line1]" class="form-control mb-1" placeholder="Address" value="{{ old($k.'.line1') }}" required maxlength="255">
                                <input name="{{ $k }}[landmark]" class="form-control mb-1" placeholder="Landmark (optional)" value="{{ old($k.'.landmark') }}" maxlength="255">
                                <input name="{{ $k }}[contact_name]" class="form-control mb-1" placeholder="Contact name" value="{{ old($k.'.contact_name') }}" maxlength="120">
                                <input name="{{ $k }}[contact_phone]" class="form-control mb-1" placeholder="Contact phone" value="{{ old($k.'.contact_phone') }}" maxlength="24" inputmode="tel">
                                <input name="{{ $k }}[instructions]" class="form-control mb-1" placeholder="Instructions for the driver" value="{{ old($k.'.instructions') }}" maxlength="500">
                                <input type="hidden" name="{{ $k }}[lat]" id="{{ $short }}Lat" value="{{ old($k.'.lat') }}"><input type="hidden" name="{{ $k }}[lng]" id="{{ $short }}Lng" value="{{ old($k.'.lng') }}">
                            </div>
                        @endforeach
                    </div>
                    <div class="btn-group mb-2 flex-wrap" role="group">
                        <button type="button" class="btn btn-sm btn-outline-success" id="setP">Mark pickup on the map</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="setD">Mark drop-off on the map</button>
                    </div>
                    <div id="map" style="height:380px;border-radius:8px"></div>
                    <div class="form-text" id="mapHint">Choose a button, then tap the map where the place is. The address is what the driver reads; the pin is what the price and the route use.</div>
                </x-cb.card>
                <button class="btn btn-primary btn-lg mt-3 w-100" id="saveBtn">Save booking</button>
            </div>
        </div>
    </form>
</div></div></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var city = $('citySel').selectedOptions[0] || {dataset: {}};
    var map = L.map('map').setView([parseFloat(city.dataset.lat) || 9.082, parseFloat(city.dataset.lng) || 8.6753], city.dataset.lat ? 13 : 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    var pins = {}, mode = null;
    function pin(k, lat, lng) {
        var colour = k === 'p' ? '#198754' : '#dc3545';
        if (pins[k]) { pins[k].setLatLng([lat, lng]); } else { pins[k] = L.circleMarker([lat, lng], {radius: 9, color: colour, fillColor: colour, fillOpacity: .8}).addTo(map); }
        $(k + 'Lat').value = lat.toFixed(6); $(k + 'Lng').value = lng.toFixed(6);
    }
    ['p', 'd'].forEach(function (k) { var a = parseFloat($(k + 'Lat').value), b = parseFloat($(k + 'Lng').value); if (a && b) { pin(k, a, b); } });
    // A pickup point fills in the drop-off; the receiver's name and phone stay, so they get the collection code.
    if ($('dPoint')) { $('dPoint').addEventListener('change', function () {
        var o = this.selectedOptions[0];
        if (o && o.value) { document.querySelector('[name="dropoff[line1]"]').value = o.dataset.line; pin('d', parseFloat(o.dataset.lat), parseFloat(o.dataset.lng)); map.setView([parseFloat(o.dataset.lat), parseFloat(o.dataset.lng)], 14); }
    }); }
    $('setP').onclick = function () { mode = 'p'; $('mapHint').textContent = 'Tap the map to place the pickup pin.'; };
    $('setD').onclick = function () { mode = 'd'; $('mapHint').textContent = 'Tap the map to place the drop-off pin.'; };
    map.on('click', function (e) { if (mode) { pin(mode, e.latlng.lat, e.latlng.lng); $('mapHint').textContent = 'Pin placed. Tap again to move it.'; } });
    $('citySel').onchange = function () { var o = this.selectedOptions[0]; if (o.dataset.lat) { map.setView([parseFloat(o.dataset.lat), parseFloat(o.dataset.lng)], 13); } };

    $('priceBtn').onclick = function () {
        if (!$('pLat').value || !$('dLat').value) { $('priceHint').textContent = 'Mark both pins on the map first.'; return; }
        var data = new FormData($('bookForm'));
        $('priceHint').textContent = 'Working it out…';
        fetch(@json($priceUrl), {method: 'POST', body: data, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (r) { return r.json().then(function (j) { return {ok: r.ok, j: j}; }); })
            .then(function (res) {
                if (!res.ok) { $('priceHint').textContent = res.j.message || 'The price could not be worked out.'; return; }
                $('priceIn').value = res.j.naira;
                $('priceHint').textContent = 'From the price list: about ' + res.j.distance_km + ' km, ' + res.j.minutes + ' min by road. You can change it.';
            })
            .catch(function () { $('priceHint').textContent = 'The price could not be worked out. Type it instead.'; });
    };
    $('bookForm').onsubmit = function (e) {
        if (!$('pLat').value || !$('dLat').value) { e.preventDefault(); $('mapHint').textContent = 'Please mark both the pickup and the drop-off on the map.'; $('mapHint').classList.add('text-danger'); return false; }
        $('saveBtn').disabled = true;
    };
})();
</script>
@endsection
