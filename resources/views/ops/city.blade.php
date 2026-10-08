@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$city->name" icon="ri-map-pin-2-line" subtitle="Cities and the zones inside them." :back="route('ops.cities')" back-label="Cities" />
    @include('ops._flash')

    <x-cb.card title="City" icon="ri-building-line">
        <form method="POST" action="{{ route('ops.city.update', $city->id) }}" class="row g-3">
            @csrf @method('PUT')
            <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $city->name) }}" required></div>
            <div class="col-md-4"><label class="form-label">State or region</label><input name="region" class="form-control" value="{{ old('region', $city->region) }}"></div>
            <div class="col-md-4"><label class="form-label">Status</label>
                <select name="launch_status" class="form-select">@foreach($statuses as $s)<option value="{{ $s }}" @selected(old('launch_status', $city->launch_status) === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
            </div>
            @can('Update zone')<div class="col-12"><button class="btn btn-primary">Save city</button></div>@endcan
        </form>
    </x-cb.card>

    <x-cb.card title="Zones" icon="ri-focus-3-line" :count="count($zones)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Type</th><th class="text-end">Area (km²)</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($zones as $z)
                <tr>
                    <td>{{ $z->name }}</td>
                    <td class="text-muted">{{ str_replace('_', ' ', $z->type) }}</td>
                    <td class="text-end">{{ $z->area_km2 }}</td>
                    <td><span class="badge {{ $z->active ? 'bg-success' : 'bg-secondary' }}">{{ $z->active ? 'On' : 'Off' }}</span></td>
                    <td class="text-end">
                        @can('Update zone')
                        <form method="POST" action="{{ route('ops.zone.toggle', $z->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $z->active ? 'Switch off' : 'Switch on' }}</button></form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No zones yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    @can('Create zone')
    <x-cb.card title="Add a zone" icon="ri-add-circle-line">
        <form method="POST" action="{{ route('ops.zone.store', $city->id) }}" class="row g-3">
            @csrf
            <div class="col-md-6"><label class="form-label">Zone name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
            <div class="col-md-6"><label class="form-label">Type</label>
                <select name="type" class="form-select">@foreach($types as $t)<option value="{{ $t }}" @selected(old('type', 'service') === $t)>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>@endforeach</select>
            </div>
            <div class="col-12">
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="shape" id="shape-circle" value="circle" checked><label class="btn btn-outline-primary btn-sm" for="shape-circle">Circle</label>
                    <input type="radio" class="btn-check" name="shape" id="shape-polygon" value="polygon"><label class="btn btn-outline-primary btn-sm" for="shape-polygon">Draw outline</label>
                </div>
            </div>
            <div class="col-12"><label class="form-label" id="map-hint">Click the map to set the zone centre</label><div id="zone-map" style="height:300px;border-radius:8px"></div></div>
            <div id="circle-fields" class="col-12"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Latitude</label><input name="lat" id="lat" class="form-control" value="{{ old('lat', $city->lat) }}"></div>
                <div class="col-md-4"><label class="form-label">Longitude</label><input name="lng" id="lng" class="form-control" value="{{ old('lng', $city->lng) }}"></div>
                <div class="col-md-4"><label class="form-label">Radius (km)</label><input name="radius_km" id="radius" class="form-control" value="{{ old('radius_km', 5) }}"></div>
            </div></div>
            <div id="poly-fields" class="col-12" style="display:none">
                <input type="hidden" name="points" id="points">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="poly-undo">Undo last point</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="poly-clear">Start over</button>
                <span class="small text-muted ms-2" id="poly-count">0 points</span>
            </div>
            <div class="col-12"><button class="btn btn-primary">Add zone</button></div>
        </form>
    </x-cb.card>
    @endcan
</div></div></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    if (typeof L === 'undefined') return;
    var la = document.getElementById('lat'), ln = document.getElementById('lng'), rd = document.getElementById('radius');
    if (!la) return;
    var start = [parseFloat(la.value), parseFloat(ln.value)];
    var map = L.map('zone-map').setView(start, 11);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
    var pin = L.marker(start).addTo(map);
    var ring = L.circle(start, {radius: (parseFloat(rd.value) || 5) * 1000}).addTo(map);
    var shape = 'circle', pts = [], poly = L.polygon([], {color: '#e8590c'}).addTo(map), dots = L.layerGroup().addTo(map);
    var circleBox = document.getElementById('circle-fields'), polyBox = document.getElementById('poly-fields');
    var hint = document.getElementById('map-hint'), field = document.getElementById('points'), count = document.getElementById('poly-count');
    function move(ll) { pin.setLatLng(ll); ring.setLatLng(ll); la.value = ll.lat.toFixed(6); ln.value = ll.lng.toFixed(6); }
    function redraw() {
        poly.setLatLngs(pts); dots.clearLayers();
        pts.forEach(function (p) { L.circleMarker(p, {radius: 4, color: '#e8590c'}).addTo(dots); });
        field.value = JSON.stringify(pts.map(function (p) { return [+p[0].toFixed(6), +p[1].toFixed(6)]; }));
        count.textContent = pts.length + (pts.length === 1 ? ' point' : ' points');
    }
    function setShape(s) {
        shape = s;
        var isPoly = s === 'polygon';
        circleBox.style.display = isPoly ? 'none' : ''; polyBox.style.display = isPoly ? '' : 'none';
        hint.textContent = isPoly ? 'Click the map to outline the zone, one corner at a time (at least 3 points)' : 'Click the map to set the zone centre';
        if (isPoly) { map.removeLayer(pin); map.removeLayer(ring); } else { pin.addTo(map); ring.addTo(map); }
        // Only the active shape's fields are sent.
        [la, ln, rd].forEach(function (el) { el.disabled = isPoly; }); field.disabled = !isPoly;
    }
    map.on('click', function (e) {
        if (shape === 'polygon') { pts.push([e.latlng.lat, e.latlng.lng]); redraw(); } else { move(e.latlng); }
    });
    rd.addEventListener('input', function () { ring.setRadius((parseFloat(rd.value) || 0) * 1000); });
    document.getElementById('shape-circle').addEventListener('change', function () { setShape('circle'); });
    document.getElementById('shape-polygon').addEventListener('change', function () { setShape('polygon'); });
    document.getElementById('poly-undo').addEventListener('click', function () { pts.pop(); redraw(); });
    document.getElementById('poly-clear').addEventListener('click', function () { pts = []; redraw(); });
    setShape('circle');
})();
</script>
@endsection
