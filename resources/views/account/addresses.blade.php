@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Saved addresses" icon="ri-map-pin-user-line" subtitle="Places you send from or to often. Pick one when you book instead of typing it again." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Your places" icon="ri-bookmark-line" :count="count($addresses)" :flush="true">
                <table class="table align-middle mb-0"><tbody>
                @forelse($addresses as $a)
                    <tr><td><div class="fw-semibold">{{ $a['label'] ?: 'Address' }} @if($a['is_default'])<span class="badge bg-primary-subtle text-primary">Default</span>@endif</div>
                            <div class="small">{{ $a['line1'] }}</div>
                            @if($a['landmark'] || $a['instructions'])<div class="small text-muted">{{ trim(($a['landmark'] ?? '').' '.($a['instructions'] ?? '')) }}</div>@endif</td>
                        <td class="text-end"><form method="POST" action="{{ route('account.address.delete', $a['id']) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr>
                @empty
                    <tr><td class="text-center text-muted py-4">Nothing saved yet. Add your home or office on the right.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>
        <div class="col-lg-6">
            <x-cb.card title="Add a place" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('account.address.save') }}" class="row g-2" id="addrForm">@csrf
                    <div class="col-md-4"><input name="label" class="form-control" placeholder="Name, e.g. Home" maxlength="60" value="{{ old('label') }}"></div>
                    <div class="col-md-8"><input name="line1" class="form-control" placeholder="Address" required value="{{ old('line1') }}"></div>
                    <div class="col-md-6"><input name="landmark" class="form-control" placeholder="Landmark (optional)" value="{{ old('landmark') }}"></div>
                    <div class="col-md-6"><input name="instructions" class="form-control" placeholder="Directions for the rider (optional)" value="{{ old('instructions') }}"></div>
                    <div class="col-md-6"><input name="contact_name" class="form-control" placeholder="Contact name (optional)" value="{{ old('contact_name') }}"></div>
                    <div class="col-md-6"><input name="contact_phone" class="form-control" placeholder="Contact phone (optional)" value="{{ old('contact_phone') }}"></div>
                    <input type="hidden" name="lat" id="aLat" value="{{ old('lat') }}"><input type="hidden" name="lng" id="aLng" value="{{ old('lng') }}">
                    <div class="col-12"><div id="amap" style="height:260px;border-radius:8px"></div><div class="form-text" id="aHint">Tap the map where the place is.</div></div>
                    <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDef"><label class="form-check-label small" for="isDef">Use as my default pickup</label></div>
                    <div class="col-12"><button class="btn btn-primary">Save place</button></div>
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    if (typeof L === 'undefined') return;
    var c = @json($cities->first());
    var la = document.getElementById('aLat'), ln = document.getElementById('aLng');
    var map = L.map('amap').setView([parseFloat(la.value) || (c ? c.lat : 9.08), parseFloat(ln.value) || (c ? c.lng : 8.67)], c ? 13 : 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
    var pin = null;
    map.on('click', function (e) {
        if (pin) pin.setLatLng(e.latlng); else pin = L.marker(e.latlng).addTo(map);
        la.value = e.latlng.lat.toFixed(6); ln.value = e.latlng.lng.toFixed(6);
        document.getElementById('aHint').textContent = 'Pin placed. Tap again to move it.';
    });
    document.getElementById('addrForm').onsubmit = function (e) { if (!la.value) { e.preventDefault(); document.getElementById('aHint').textContent = 'Please tap the map to place the pin.'; } };
})();
</script>
@endsection
