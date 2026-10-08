<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<title>Track delivery {{ $snap['tracking_code'] }}</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    :root { --bg:#f6f7f9; --card:#fff; --ink:#1b2430; --muted:#6b7686; --line:#e3e7ee; --brand:#1d6fdc; --ok:#1e9e5a; }
    @media (prefers-color-scheme: dark) { :root { --bg:#12161c; --card:#1b212a; --ink:#e8edf4; --muted:#93a0b2; --line:#2a323d; --brand:#5aa2ff; --ok:#43c283; } }
    * { box-sizing: border-box; }
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:var(--bg); color:var(--ink); }
    .wrap { max-width:640px; margin:0 auto; padding:16px; }
    .card { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:16px; margin-bottom:12px; }
    h1 { font-size:1.15rem; margin:0 0 4px; }
    .muted { color:var(--muted); font-size:.9rem; }
    #map { height:300px; border-radius:12px; border:1px solid var(--line); }
    .status { font-size:1.35rem; font-weight:700; margin:0 0 4px; }
    .eta { color:var(--brand); font-weight:600; }
    ol { list-style:none; margin:0; padding:0; }
    li.stop { display:flex; gap:10px; padding:10px 0; border-top:1px solid var(--line); }
    li.stop:first-child { border-top:0; }
    .dot { width:12px; height:12px; border-radius:50%; background:var(--line); margin-top:5px; flex:none; }
    .dot.done { background:var(--ok); }
    .dot.next { background:var(--brand); }
    .stale { color:#b26a00; }
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <p class="status" id="status">Loading</p>
        <div class="muted">Tracking code <strong>{{ $snap['tracking_code'] }}</strong></div>
        <div id="driver" class="muted" style="margin-top:6px"></div>
    </div>
    <div id="map"></div>
    <div class="card" style="margin-top:12px"><ol id="stops"></ol></div>
    <p class="muted" style="text-align:center">This page updates by itself.</p>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var url = @json(url('/api/v1/track/'.$token));
    var first = @json($snap);
    var LABELS = {
        created: 'Order placed', awaiting_dispatch: 'Finding a rider', offered: 'Finding a rider', no_supply: 'Finding a rider', unassigned: 'Finding a rider',
        assigned: 'Rider assigned', heading_to_pickup: 'Rider is heading to pickup', at_pickup: 'Rider is at the pickup',
        picked_up: 'Picked up', in_transit: 'On the way to you', at_dropoff: 'Rider has arrived',
        delivered: 'Delivered', confirmed: 'Delivered', completed: 'Delivered', cancelled: 'Cancelled'
    };
    var map = L.map('map', {zoomControl: true}).setView([9.08, 8.67], 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
    var stopMarkers = [], rider = null, fitted = false, timer = null;

    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; }

    function render(s) {
        document.getElementById('status').textContent = LABELS[s.status] || String(s.status).replace(/_/g, ' ');
        var d = s.driver, el = document.getElementById('driver');
        if (d) {
            var line = esc(d.first_name) + (d.vehicle ? ' · ' + esc(d.vehicle) : '');
            if (d.eta_minutes) line += ' · <span class="eta">about ' + d.eta_minutes + ' min away</span>';
            if (d.seconds_since_update != null && d.seconds_since_update > 120) line += ' · <span class="stale">location a few minutes old</span>';
            el.innerHTML = line;
        } else { el.textContent = ''; }

        var next = s.stops.find(function (x) { return x.status !== 'completed'; });
        document.getElementById('stops').innerHTML = s.stops.map(function (x) {
            var cls = x.status === 'completed' ? 'done' : (next && next.seq === x.seq ? 'next' : '');
            return '<li class="stop"><span class="dot ' + cls + '"></span><div><strong>' + (x.type === 'pickup' ? 'Pickup' : 'Delivery') + '</strong><div class="muted">' + esc(x.address) + '</div></div></li>';
        }).join('');

        stopMarkers.forEach(function (m) { map.removeLayer(m); }); stopMarkers = [];
        var pts = [];
        s.stops.forEach(function (x) {
            if (!x.lat && !x.lng) return;
            stopMarkers.push(L.circleMarker([x.lat, x.lng], {radius: 8, color: x.type === 'pickup' ? '#1d6fdc' : '#1e9e5a', fillOpacity: .85}).addTo(map).bindTooltip(x.type === 'pickup' ? 'Pickup' : 'Delivery'));
            pts.push([x.lat, x.lng]);
        });
        if (d && d.lat) {
            var ll = [d.lat, d.lng];
            if (rider) rider.setLatLng(ll); else rider = L.marker(ll).addTo(map).bindTooltip('Rider');
            pts.push(ll);
        } else if (rider) { map.removeLayer(rider); rider = null; }
        if (pts.length && (!fitted || d)) { map.fitBounds(pts, {padding: [40, 40], maxZoom: 16}); fitted = true; }
    }

    function done(s) { return ['delivered', 'confirmed', 'completed', 'cancelled'].indexOf(s.status) !== -1; }

    function poll() {
        fetch(url, {headers: {'Accept': 'application/json'}, cache: 'no-store'})
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function (s) { render(s); if (!done(s)) timer = setTimeout(poll, Math.max(5, s.poll_after_seconds || 8) * 1000); })
            .catch(function () { timer = setTimeout(poll, 15000); });
    }
    render(first);
    if (!done(first)) timer = setTimeout(poll, Math.max(5, first.poll_after_seconds || 8) * 1000);
})();
</script>
</body>
</html>
