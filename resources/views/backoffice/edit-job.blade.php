@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Edit job '.$order" icon="ri-edit-2-line" subtitle="Fix an address, a contact or the directions. The price stays as agreed." :back="$back" back-label="Back to the job" />
    @if(request()->routeIs('provider.*'))@include('provider._flash')@else @include('ops._flash')@endif

    @if(! $can['pickup'] && ! $can['dropoff'])
        <div class="cb-banner warning"><i class="ri-lock-line"></i><div>This job is {{ str_replace('_', ' ', $s->status) }}, so its details can no longer be changed.</div></div>
    @elseif(! $can['pickup'])
        <div class="cb-banner info"><i class="ri-information-line"></i><div>The goods have been collected, so only the drop-off can still change.</div></div>
    @endif

    <form method="POST" action="{{ $action }}">@csrf @method('PUT')
        <div class="row g-3">
            <div class="col-lg-6">
                @foreach(['pickup' => ['Pickup', 'success', 'p'], 'dropoff' => ['Drop-off', 'danger', 'd']] as $k => [$label, $colour, $short])
                    @php $stop = $$k; $open = $can[$k] && $stop && $stop->status === 'pending'; @endphp
                    @if($stop)
                    <x-cb.card :title="$label" icon="ri-map-pin-2-fill">
                        <fieldset @disabled(! $open)>
                            <label class="form-label small">Address</label>
                            <input name="{{ $k }}[line1]" class="form-control mb-2" value="{{ old($k.'.line1', $stop->line1) }}" required maxlength="255">
                            <label class="form-label small">Landmark</label>
                            <input name="{{ $k }}[landmark]" class="form-control mb-2" value="{{ old($k.'.landmark', $stop->landmark) }}" maxlength="255">
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label small">Contact name</label><input name="{{ $k }}[contact_name]" class="form-control mb-2" value="{{ old($k.'.contact_name', $stop->contact_name) }}" maxlength="120"></div>
                                <div class="col-6"><label class="form-label small">Contact phone</label><input name="{{ $k }}[contact_phone]" class="form-control mb-2" value="{{ old($k.'.contact_phone', $stop->contact_phone) }}" maxlength="24"></div>
                            </div>
                            <label class="form-label small">Instructions for the driver</label>
                            <textarea name="{{ $k }}[instructions]" class="form-control" rows="2" maxlength="500">{{ old($k.'.instructions', $stop->instructions) }}</textarea>
                            <input type="hidden" name="{{ $k }}[lat]" id="{{ $short }}Lat" value="{{ old($k.'.lat', $stop->lat) }}"><input type="hidden" name="{{ $k }}[lng]" id="{{ $short }}Lng" value="{{ old($k.'.lng', $stop->lng) }}">
                            @if($open)<button type="button" class="btn btn-sm btn-outline-{{ $colour }} mt-2" data-move="{{ $short }}">Move the {{ strtolower($label) }} pin</button>@endif
                        </fieldset>
                    </x-cb.card>
                    @endif
                @endforeach
            </div>
            <div class="col-lg-6">
                <x-cb.card title="Map" icon="ri-map-2-line">
                    <div id="map" style="height:320px;border-radius:8px"></div>
                    <div class="form-text" id="mapHint">Moving a pin changes the route the driver follows. It does not change the price.</div>
                </x-cb.card>
                @if(count($packages))
                <x-cb.card title="Parcels" icon="ri-box-3-line">
                    <fieldset @disabled(! $can['pickup'])>
                    @foreach($packages as $p)
                        <div class="row g-2 mb-2 align-items-end">
                            <div class="col-7"><label class="form-label small">{{ $p->barcode }}</label><input name="packages[{{ $p->public_id }}][description]" class="form-control" value="{{ old('packages.'.$p->public_id.'.description', $p->description) }}" maxlength="255"></div>
                            <div class="col-5"><label class="form-label small">Weight (kg)</label><input type="number" step="0.1" min="0" name="packages[{{ $p->public_id }}][weight_kg]" class="form-control" value="{{ old('packages.'.$p->public_id.'.weight_kg', $p->weight_g !== null ? $p->weight_g / 1000 : '') }}"></div>
                        </div>
                    @endforeach
                    </fieldset>
                </x-cb.card>
                @endif
                @if($can['pickup'] || $can['dropoff'])<button class="btn btn-primary btn-lg w-100">Save changes</button>@endif
            </div>
        </div>
    </form>
</div></div></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var pts = ['p', 'd'].filter(function (k) { return $(k + 'Lat') && $(k + 'Lat').value; });
    if (!pts.length) { return; }
    var map = L.map('map'), pins = {}, mode = null, bounds = [];
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    pts.forEach(function (k) {
        var ll = [parseFloat($(k + 'Lat').value), parseFloat($(k + 'Lng').value)], c = k === 'p' ? '#198754' : '#dc3545';
        pins[k] = L.circleMarker(ll, {radius: 9, color: c, fillColor: c, fillOpacity: .8}).addTo(map); bounds.push(ll);
    });
    bounds.length > 1 ? map.fitBounds(bounds, {padding: [30, 30]}) : map.setView(bounds[0], 15);
    document.querySelectorAll('[data-move]').forEach(function (b) { b.onclick = function () { mode = b.dataset.move; $('mapHint').textContent = 'Tap the map where the place really is.'; }; });
    map.on('click', function (e) {
        if (!mode) { return; }
        pins[mode].setLatLng(e.latlng); $(mode + 'Lat').value = e.latlng.lat.toFixed(6); $(mode + 'Lng').value = e.latlng.lng.toFixed(6);
        $('mapHint').textContent = 'Pin moved. Save to keep it.';
    });
})();
</script>
@endsection
