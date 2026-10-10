@extends('layouts.master')

@section('content')
@php
    $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0);
    $methodNames = ['higher_of' => 'Higher of rate and cost plus markup', 'per_km' => 'Per-kilometre rate', 'cost_plus' => 'Cost plus markup'];
    $e = $editing;
    $nv = fn ($f, $default = '') => old($f, $e ? rtrim(rtrim(number_format($e->{$f} / 100, 2, '.', ''), '0'), '.') : $default);
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Pricing calculator" icon="ri-calculator-line" subtitle="Enter your running costs once. The calculator works out what any trip costs you and what to charge for it." />
    @include('provider._flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Work out a price" icon="ri-route-line">
                @if($profiles->isEmpty())
                    <p class="text-muted mb-0">Add a cost profile first{{ $canEdit ? ' (on the right)' : '; ask an owner or admin to set one up' }}. It holds your fuel price, fuel use, labour, maintenance and rates.</p>
                @else
                <div class="row g-2 mb-2">
                    <div class="col-sm-6"><label class="form-label small">Cost profile</label>
                        <select id="cProfile" class="form-select">@foreach($profiles as $p)<option value="{{ $p->public_id }}">{{ $p->name }}{{ $p->is_default ? ' (default)' : '' }}</option>@endforeach</select></div>
                    <div class="col-sm-6"><label class="form-label small">Distance</label>
                        <select id="cMode" class="form-select"><option value="map">Pick the stops on the map</option><option value="km">Type the distance</option></select></div>
                </div>
                <div id="kmBox" class="row g-2 mb-2 d-none">
                    <div class="col-6"><label class="form-label small">Kilometres</label><input id="cKm" type="number" step="0.1" min="0.1" class="form-control" placeholder="18"></div>
                    <div class="col-6"><label class="form-label small">Driving time (min, optional)</label><input id="cMin" type="number" min="1" class="form-control"></div>
                </div>
                <div id="mapBox">
                    <div id="map" style="height:300px;border-radius:8px"></div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <div class="form-text" id="mapHint">Tap the map for the pickup, then each drop-off in order (up to 10 stops).</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearStops">Clear</button>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-sm-4"><label class="form-label small">Waiting (min)</label><input id="cWait" type="number" min="0" class="form-control" value="0"></div>
                    <div class="col-sm-4"><label class="form-label small">Tolls and parking (₦)</label><input id="cExp" type="number" min="0" class="form-control" value="0"></div>
                    <div class="col-sm-4"><label class="form-label small">Declared value (₦)</label><input id="cVal" type="number" min="0" class="form-control" value="0"></div>
                </div>
                <div class="d-flex flex-wrap gap-3 my-2">
                    <label class="form-check"><input class="form-check-input" type="checkbox" id="cRound"> <span class="form-check-label">Return to the pickup</span></label>
                    <label class="form-check"><input class="form-check-input" type="checkbox" id="cFragile"> <span class="form-check-label">Fragile</span></label>
                    <label class="form-check"><input class="form-check-input" type="checkbox" id="cLoading"> <span class="form-check-label">Needs loading help</span></label>
                </div>
                <button class="btn btn-primary w-100 mb-3" id="calcBtn">Calculate</button>
                <div id="result"></div>
                @endif
            </x-cb.card>

            <x-cb.card title="Recent estimates" icon="ri-history-line">
                @if($recent->isEmpty())<span class="text-muted">None yet.</span>@else
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>When</th><th>Profile</th><th class="text-end">Km</th><th class="text-end">Cost</th><th class="text-end">Price</th><th class="text-end">You receive</th></tr></thead>
                    <tbody>@foreach($recent as $r)<tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d M H:i') }}@if($r->request) <a href="{{ route('provider.request', $r->request) }}" class="small">request</a>@endif</td>
                        <td>{{ $r->profile }} v{{ $r->profile_version }}</td><td class="text-end">{{ number_format($r->distance_m / 1000, 1) }}</td>
                        <td class="text-end">{{ $naira($r->operating_cost) }}</td><td class="text-end">{{ $naira($r->suggested_price) }}</td><td class="text-end">{{ $naira($r->provider_net) }}</td>
                    </tr>@endforeach</tbody>
                </table></div>@endif
            </x-cb.card>
        </div>

        <div class="col-lg-5">
            <x-cb.card title="Your cost profiles" icon="ri-gas-station-line">
                @forelse($profiles as $p)
                    <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                        <div><div class="fw-semibold">{{ $p->name }} @if($p->is_default)<span class="badge bg-success-subtle text-success">default</span>@endif</div>
                            <div class="small text-muted">{{ $methodNames[$p->pricing_method] ?? $p->pricing_method }} · {{ $naira($p->rate_per_km) }}/km · fuel {{ $naira($p->fuel_price_per_litre) }}/L at {{ (float) $p->fuel_l_per_100km }} L/100 km · markup {{ $p->markup_bp / 100 }}% · v{{ $p->version }}</div></div>
                        @if($canEdit)<div class="d-flex gap-1">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('provider.pricing', ['edit' => $p->public_id]) }}">Edit</a>
                            <form method="POST" action="{{ route('provider.pricing.retire', $p->public_id) }}" onsubmit="return confirm('Remove this profile? Saved estimates keep their figures.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form>
                        </div>@endif
                    </div>
                @empty
                    <span class="text-muted">No profiles yet.</span>
                @endforelse
            </x-cb.card>

            @if($canEdit)
            <x-cb.card :title="$e ? 'Edit '.$e->name : 'New cost profile'" icon="ri-edit-line">
                <form method="POST" action="{{ $e ? route('provider.pricing.update', $e->public_id) : route('provider.pricing.save') }}">@csrf @if($e) @method('PUT') @endif
                    <div class="row g-2">
                        <div class="col-7"><label class="form-label small">Name</label><input name="name" class="form-control" required maxlength="80" value="{{ old('name', $e->name ?? '') }}" placeholder="Motorbike, Lagos"></div>
                        <div class="col-5"><label class="form-label small">Vehicle</label><select name="vehicle_type_id" class="form-select"><option value="">Any</option>@foreach($vehicleTypes as $v)<option value="{{ $v->id }}" @selected((int) old('vehicle_type_id', $e->vehicle_type_id ?? 0) === $v->id)>{{ $v->name }}</option>@endforeach</select></div>
                        <div class="col-12"><label class="form-label small">How to price</label><select name="pricing_method" class="form-select">@foreach($methods as $m)<option value="{{ $m }}" @selected(old('pricing_method', $e->pricing_method ?? 'higher_of') === $m)>{{ $methodNames[$m] }}</option>@endforeach</select></div>

                        <div class="col-12 fw-semibold small mt-2">Your rate card</div>
                        <div class="col-4"><label class="form-label small">Base fee (₦)</label><input name="base_fee" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('base_fee') }}"></div>
                        <div class="col-4"><label class="form-label small">Per km (₦)</label><input name="rate_per_km" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('rate_per_km') }}"></div>
                        <div class="col-4"><label class="form-label small">Minimum (₦)</label><input name="min_fee" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('min_fee') }}"></div>

                        <div class="col-12 fw-semibold small mt-2">Running costs</div>
                        <div class="col-6"><label class="form-label small">Fuel price (₦/litre)</label><input name="fuel_price_per_litre" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('fuel_price_per_litre') }}"></div>
                        <div class="col-6"><label class="form-label small">Fuel use (litres/100 km)</label><input name="fuel_l_per_100km" type="number" step="0.01" min="0" class="form-control" value="{{ old('fuel_l_per_100km', $e ? (float) $e->fuel_l_per_100km : '') }}"></div>
                        <div class="col-6"><label class="form-label small">Labour per job (₦)</label><input name="labour_per_job" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('labour_per_job') }}"></div>
                        <div class="col-6"><label class="form-label small">Labour per hour (₦)</label><input name="labour_per_hour" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('labour_per_hour') }}"></div>
                        <div class="col-6"><label class="form-label small">Maintenance per km (₦)</label><input name="maintenance_per_km" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('maintenance_per_km') }}"></div>
                        <div class="col-6"><label class="form-label small">Other per job (₦)</label><input name="other_per_job" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('other_per_job') }}"></div>
                        <div class="col-6"><label class="form-label small">Empty return (% of trip)</label><input name="return_trip_bp" type="number" step="0.01" min="0" max="100" class="form-control" value="{{ old('return_trip_bp', $e ? $e->return_trip_bp / 100 : '') }}"></div>
                        <div class="col-6"><label class="form-label small">Markup (%)</label><input name="markup_bp" type="number" step="0.01" min="0" class="form-control" value="{{ old('markup_bp', $e ? $e->markup_bp / 100 : '') }}"></div>

                        <div class="col-12 fw-semibold small mt-2">Extra charges to the customer</div>
                        <div class="col-6"><label class="form-label small">Loading (₦)</label><input name="loading_fee" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('loading_fee') }}"></div>
                        <div class="col-6"><label class="form-label small">Waiting per minute (₦)</label><input name="waiting_per_minute" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('waiting_per_minute') }}"></div>
                        <div class="col-6"><label class="form-label small">Fragile (₦)</label><input name="fragile_surcharge" type="number" step="0.01" min="0" class="form-control" value="{{ $nv('fragile_surcharge') }}"></div>
                        <div class="col-6"><label class="form-label small">Insurance (% of value)</label><input name="insurance_bp" type="number" step="0.01" min="0" class="form-control" value="{{ old('insurance_bp', $e ? $e->insurance_bp / 100 : '') }}"></div>
                        <div class="col-6"><label class="form-label small">Round prices up to (₦)</label><input name="rounding_kobo" type="number" step="0.01" min="0.01" class="form-control" value="{{ $nv('rounding_kobo', '50') }}"></div>
                        <div class="col-6 d-flex align-items-end"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1" @checked(old('is_default', $e->is_default ?? false))> <span class="form-check-label">Use by default</span></label></div>
                    </div>
                    <div class="form-text my-2">Tax and the platform fee are worked out separately. Saving a change creates a new version; estimates already made keep the figures they used.</div>
                    <button class="btn btn-primary w-100">{{ $e ? 'Save changes' : 'Save profile' }}</button>
                    @if($e)<a class="btn btn-link w-100" href="{{ route('provider.pricing') }}">Cancel</a>@endif
                </form>
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>

@include('provider._estimate-js')
@if($profiles->isNotEmpty())
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var start = [{{ $centre->lat ?? 9.082 }}, {{ $centre->lng ?? 8.6753 }}];
    var map = L.map('map').setView(start, {{ $centre ? 13 : 6 }});
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    var stops = [], marks = [], line = null;
    function redraw() {
        marks.forEach(function (m) { map.removeLayer(m); }); marks = [];
        if (line) { map.removeLayer(line); line = null; }
        stops.forEach(function (s, i) {
            var c = i === 0 ? '#198754' : '#dc3545';
            marks.push(L.circleMarker([s.lat, s.lng], {radius: 9, color: c, fillColor: c, fillOpacity: .8}).bindTooltip(i === 0 ? 'Pickup' : 'Stop ' + i, {permanent: true, direction: 'top'}).addTo(map));
        });
        if (stops.length > 1) { line = L.polyline(stops.map(function (s) { return [s.lat, s.lng]; }), {dashArray: '6 6', color: '#0d9488'}).addTo(map); }
        $('mapHint').textContent = stops.length === 0 ? 'Tap the map for the pickup, then each drop-off in order (up to 10 stops).' : stops.length === 1 ? 'Now tap the drop-off.' : stops.length + ' stops. The price uses the road route between them.';
    }
    map.on('click', function (e) { if (stops.length < 10) { stops.push({lat: +e.latlng.lat.toFixed(6), lng: +e.latlng.lng.toFixed(6)}); redraw(); } });
    $('clearStops').onclick = function () { stops = []; redraw(); };
    $('cMode').onchange = function () { var km = this.value === 'km'; $('kmBox').classList.toggle('d-none', !km); $('mapBox').classList.toggle('d-none', km); if (!km) { setTimeout(function () { map.invalidateSize(); }, 50); } };
    $('calcBtn').onclick = function () {
        var body = {profile: $('cProfile').value, round_trip: $('cRound').checked, fragile: $('cFragile').checked, loading: $('cLoading').checked,
            wait_minutes: parseInt($('cWait').value || '0', 10), job_expenses_naira: parseFloat($('cExp').value || '0'), declared_value_naira: parseFloat($('cVal').value || '0')};
        if ($('cMode').value === 'km') { body.distance_km = parseFloat($('cKm').value); if ($('cMin').value) { body.duration_min = parseFloat($('cMin').value); } }
        else if (stops.length < 2) { $('result').innerHTML = '<div class="text-danger">Tap at least a pickup and a drop-off on the map.</div>'; return; }
        else { body.stops = stops; }
        qEstimate.run('{{ route('provider.pricing.estimate') }}', body, $('result'));
    };
})();
</script>
@endif
@endsection
