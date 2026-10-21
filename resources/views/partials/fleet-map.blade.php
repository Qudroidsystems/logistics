{{--
    Live fleet map. Needs: $liveUrl (JSON from FleetService::snapshot), $trailUrl and $jobUrl with __ID__ placeholders,
    $jobKey ('id' = shipment public id, 'order_id' = order public id), $showCompany, and optionally $providers (staff console).
--}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #fleetMap { height: calc(100vh - 260px); min-height: 420px; border-radius: 12px; }
    .fleet-list { max-height: calc(100vh - 380px); min-height: 300px; overflow-y: auto; }
    .fleet-row { cursor: pointer; padding: 10px 14px; border-bottom: 1px solid var(--vz-border-color, #e9ebec); }
    .fleet-row:hover, .fleet-row.active { background: rgba(64, 81, 137, .06); }
    .fleet-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; flex: none; }
    .fleet-chip { cursor: pointer; user-select: none; }
    .fleet-chip.off { opacity: .45; }
    .fleet-label { background: #fff; border: 0; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,.25); font-size: 11px; font-weight: 600; padding: 1px 6px; }
    .fleet-label::before { display: none; }
</style>

<div class="row g-3">
    <div class="col-xl-8">
        <x-cb.card :flush="true">
            <div id="fleetMap"></div>
        </x-cb.card>
        <div class="small text-muted mt-2" id="fleetUpdated">Loading drivers…</div>
    </div>
    <div class="col-xl-4">
        <x-cb.card title="Drivers" icon="ri-steering-2-line" :flush="true">
            <div class="p-3 border-bottom">
                @if(!empty($providers))
                    <select id="fleetProvider" class="form-select form-select-sm mb-2" aria-label="Provider">
                        <option value="">Every provider</option>
                        @foreach($providers as $p)<option value="{{ $p->public_id }}">{{ $p->display_name }}</option>@endforeach
                    </select>
                @endif
                <input id="fleetSearch" type="search" class="form-control form-control-sm mb-2" placeholder="Search name, @handle or plate">
                <div class="d-flex flex-wrap gap-1" id="fleetChips"></div>
            </div>
            <div class="fleet-list" id="fleetList"></div>
        </x-cb.card>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var LIVE = @json($liveUrl), TRAIL = @json($trailUrl), JOB = @json($jobUrl), JOB_KEY = @json($jobKey ?? 'id');
    var SHOW_COMPANY = @json((bool) ($showCompany ?? false)), POLL = {{ max(5, (int) config('tracking.fleet_poll_seconds', 10)) }} * 1000;
    var STATES = {
        on_job: {label: 'On a job', color: '#405189'},
        online: {label: 'Online', color: '#0ab39c'},
        'break': {label: 'On a break', color: '#f7b84b'},
        no_signal: {label: 'No signal', color: '#878a99'},
        offline: {label: 'Offline', color: '#ced4da'}
    };
    var hidden = {offline: true};
    var map = L.map('fleetMap', {zoomControl: true}).setView([6.5244, 3.3792], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    var markers = {}, drivers = [], selected = null, trail = null, fitted = false, timer = null, busy = false;

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }
    function ago(s) { if (s == null) return 'never'; if (s < 60) return s + ' s ago'; if (s < 3600) return Math.round(s / 60) + ' min ago'; if (s < 86400) return Math.round(s / 3600) + ' h ago'; return Math.round(s / 86400) + ' d ago'; }
    function query() {
        var p = new URLSearchParams(), q = document.getElementById('fleetSearch').value.trim(), pv = document.getElementById('fleetProvider');
        if (q) p.set('q', q);
        if (pv && pv.value) p.set('provider', pv.value);
        return p.toString();
    }

    function chips(counts) {
        var html = Object.keys(STATES).map(function (k) {
            return '<span class="badge fleet-chip ' + (hidden[k] ? 'off' : '') + '" data-state="' + k + '" style="background:' + STATES[k].color + '">' + STATES[k].label + ' ' + (counts[k] || 0) + '</span>';
        }).join('');
        if (counts.speeding) html += '<span class="badge bg-danger">Speeding ' + counts.speeding + '</span>';
        document.getElementById('fleetChips').innerHTML = html;
    }

    function popup(d) {
        var h = '<div class="fw-semibold">' + esc(d.name) + (d.handle ? ' <span class="text-muted">@' + esc(d.handle) + '</span>' : '') + '</div>';
        if (SHOW_COMPANY) h += '<div class="small">' + esc(d.company) + '</div>';
        h += '<div class="small">' + STATES[d.state].label + (d.speed_kmh != null ? ' · ' + d.speed_kmh + ' km/h' : '') + (d.speeding ? ' <span class="text-danger fw-semibold">speeding</span>' : '') + '</div>';
        if (d.vehicle) h += '<div class="small text-muted">' + esc(d.vehicle) + '</div>';
        if (d.battery_pct != null) h += '<div class="small text-muted">Phone battery ' + d.battery_pct + '%</div>';
        h += '<div class="small text-muted">Last position ' + ago(d.seen_ago_s) + '</div>';
        if (d.job && d.job[JOB_KEY]) h += '<a class="small" href="' + JOB.replace('__ID__', encodeURIComponent(d.job[JOB_KEY])) + '">' + esc(d.job.order || 'Job') + ' · ' + esc(String(d.job.status).replace(/_/g, ' ')) + '</a><br>';
        if (d.phone) h += '<a class="small" href="tel:' + esc(d.phone) + '">Call ' + esc(d.phone) + '</a>';
        return h;
    }

    function render(data) {
        drivers = data.drivers;
        chips(data.counts);
        var seen = {}, bounds = [];
        drivers.forEach(function (d) {
            if (d.lat == null || hidden[d.state]) return;
            seen[d.id] = true;
            var style = {radius: d.state === 'on_job' ? 9 : 7, color: d.speeding ? '#f06548' : '#fff', weight: d.speeding ? 4 : 2, fillColor: STATES[d.state].color, fillOpacity: .95};
            var m = markers[d.id];
            if (!m) {
                m = markers[d.id] = L.circleMarker([d.lat, d.lng], style).addTo(map);
                m.bindTooltip(esc(d.name.split(' ')[0]), {permanent: true, direction: 'right', offset: [8, 0], className: 'fleet-label'});
                m.on('click', function () { pick(d.id, false); });
            } else {
                m.setLatLng([d.lat, d.lng]).setStyle(style);
            }
            m.bindPopup(popup(d));
            bounds.push([d.lat, d.lng]);
        });
        Object.keys(markers).forEach(function (id) { if (!seen[id]) { map.removeLayer(markers[id]); delete markers[id]; } });
        if (!fitted && bounds.length) { map.fitBounds(bounds, {padding: [40, 40], maxZoom: 15}); fitted = true; }
        list();
        document.getElementById('fleetUpdated').textContent = data.counts.total + ' driver' + (data.counts.total === 1 ? '' : 's') +
            (data.counts.unplaced ? ' (' + data.counts.unplaced + ' never sent a position)' : '') + ' · speed limit ' + data.speed_limit_kmh + ' km/h · updated ' + new Date(data.at).toLocaleTimeString();
    }

    function list() {
        var rows = drivers.filter(function (d) { return !hidden[d.state]; });
        var order = {on_job: 0, online: 1, 'break': 2, no_signal: 3, offline: 4};
        rows.sort(function (a, b) { return (b.speeding - a.speeding) || (order[a.state] - order[b.state]) || a.name.localeCompare(b.name); });
        document.getElementById('fleetList').innerHTML = rows.length ? rows.map(function (d) {
            return '<div class="fleet-row d-flex align-items-start ' + (selected === d.id ? 'active' : '') + '" data-id="' + esc(d.id) + '">' +
                '<span class="fleet-dot mt-1" style="background:' + STATES[d.state].color + '"></span><div class="flex-grow-1">' +
                '<div class="d-flex justify-content-between"><span class="fw-semibold">' + esc(d.name) + '</span>' +
                (d.speed_kmh != null ? '<span class="small ' + (d.speeding ? 'text-danger fw-semibold' : 'text-muted') + '">' + d.speed_kmh + ' km/h</span>' : '') + '</div>' +
                '<div class="small text-muted">' + STATES[d.state].label + (SHOW_COMPANY ? ' · ' + esc(d.company) : '') + (d.job ? ' · ' + esc(d.job.order || '') : '') + ' · ' + ago(d.seen_ago_s) + '</div>' +
                (d.status !== 'active' ? '<div class="small text-warning">Profile ' + esc(d.status) + '</div>' : '') +
                '</div></div>';
        }).join('') : '<div class="text-center text-muted py-4 small">No drivers match. Offline drivers are hidden; tap "Offline" above to show them.</div>';
    }

    function pick(id, pan) {
        selected = id;
        list();
        var d = drivers.find(function (x) { return x.id === id; });
        if (trail) { map.removeLayer(trail); trail = null; }
        if (!d) return;
        if (markers[id]) { if (pan) map.setView(markers[id].getLatLng(), Math.max(map.getZoom(), 15)); markers[id].openPopup(); }
        fetch(TRAIL.replace('__ID__', encodeURIComponent(id)) + '?minutes=60', {headers: {'Accept': 'application/json'}})
            .then(function (r) { return r.ok ? r.json() : {points: []}; })
            .then(function (t) {
                if (selected !== id || !t.points || t.points.length < 2) return;
                trail = L.polyline(t.points.map(function (p) { return [p.lat, p.lng]; }), {color: STATES[d.state].color, weight: 4, opacity: .7}).addTo(map);
            });
    }

    function poll() {
        if (busy) return;
        busy = true;
        fetch(LIVE + (query() ? '?' + query() : ''), {headers: {'Accept': 'application/json'}, cache: 'no-store'})
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(render)
            .catch(function () { document.getElementById('fleetUpdated').textContent = 'Could not refresh. Retrying…'; })
            .finally(function () { busy = false; });
    }
    function start() { clearInterval(timer); timer = setInterval(function () { if (!document.hidden) poll(); }, POLL); }

    document.getElementById('fleetChips').addEventListener('click', function (e) {
        var s = e.target.getAttribute('data-state');
        if (!s) return;
        hidden[s] = !hidden[s];
        poll();
    });
    document.getElementById('fleetList').addEventListener('click', function (e) {
        var row = e.target.closest('.fleet-row');
        if (row) pick(row.getAttribute('data-id'), true);
    });
    var t;
    document.getElementById('fleetSearch').addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { fitted = false; poll(); }, 350); });
    var pv = document.getElementById('fleetProvider');
    if (pv) pv.addEventListener('change', function () { fitted = false; poll(); });
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
    poll();
    start();
})();
</script>
