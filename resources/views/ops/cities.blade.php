@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Cities" icon="ri-map-pin-2-line" subtitle="Where the service runs. Customers and providers can only use cities listed here." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <x-cb.card title="Cities" icon="ri-building-line" :count="count($cities)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>City</th><th>Region</th><th>Status</th><th class="text-end">Zones</th><th></th></tr></thead>
            <tbody>
            @forelse($cities as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td class="text-muted">{{ $c->region ?: '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $c->launch_status }}</span></td>
                    <td class="text-end">{{ $c->active_zones }} / {{ $c->zone_count }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('ops.city', $c->id) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No cities yet. Add the first one below.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    @can('Create zone')
    <x-cb.card title="Add a city" icon="ri-add-circle-line">
        <form method="POST" action="{{ route('ops.city.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4"><label class="form-label">City name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
            <div class="col-md-4"><label class="form-label">State or region</label><input name="region" class="form-control" value="{{ old('region') }}"></div>
            <div class="col-md-4"><label class="form-label">Status</label>
                <select name="launch_status" class="form-select">@foreach($statuses as $s)<option value="{{ $s }}" @selected(old('launch_status', 'live') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
            </div>
            <div class="col-12"><label class="form-label">Click the map to set the city centre</label><div id="city-map" style="height:300px;border-radius:8px"></div></div>
            <div class="col-md-4"><label class="form-label">Latitude</label><input name="lat" id="lat" class="form-control" value="{{ old('lat') }}" required></div>
            <div class="col-md-4"><label class="form-label">Longitude</label><input name="lng" id="lng" class="form-control" value="{{ old('lng') }}" required></div>
            <div class="col-md-4"><label class="form-label">Service radius (km)</label><input name="radius_km" class="form-control" value="{{ old('radius_km', 15) }}" required></div>
            <div class="col-12"><button class="btn btn-primary">Add city and its first service zone</button></div>
        </form>
    </x-cb.card>
    @endcan
</div></div></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    if (typeof L === 'undefined') return;
    var la = document.getElementById('lat'), ln = document.getElementById('lng');
    var start = [parseFloat(la.value) || 9.08, parseFloat(ln.value) || 8.67];
    var map = L.map('city-map').setView(start, la.value ? 11 : 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
    var pin = la.value ? L.marker(start).addTo(map) : null;
    map.on('click', function (e) {
        if (pin) pin.setLatLng(e.latlng); else pin = L.marker(e.latlng).addTo(map);
        la.value = e.latlng.lat.toFixed(6); ln.value = e.latlng.lng.toFixed(6);
    });
})();
</script>
@endsection
