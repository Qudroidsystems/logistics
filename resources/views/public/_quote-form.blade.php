{{--
    The instant price form. Needs $options (InstantQuoteService::options()) and $embed (true inside an iframe:
    "Book this" then opens in a new tab). Prices come from POST /api/v1/public/quote.
--}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .qf { --qf-accent: {{ $brand['primary_color'] ?? '#0F766E' }}; }
    .qf .qf-card { background: #fff; border-radius: 20px; box-shadow: 0 12px 40px rgba(15, 23, 42, .10); padding: 22px; }
    .qf label.qf-l { font-weight: 600; font-size: 13px; margin-bottom: 4px; display: block; color: #334155; }
    .qf .qf-addr { position: relative; }
    .qf .qf-sug { position: absolute; z-index: 1000; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.08); max-height: 240px; overflow-y: auto; display: none; }
    .qf .qf-sug div { padding: 8px 12px; cursor: pointer; font-size: 14px; }
    .qf .qf-sug div:hover, .qf .qf-sug div.on { background: #f1f5f9; }
    .qf .qf-seg { display: flex; flex-wrap: wrap; gap: 8px; }
    .qf .qf-seg input { display: none; }
    .qf .qf-seg label { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 8px 12px; cursor: pointer; font-size: 14px; flex: 1 1 0; min-width: 90px; text-align: center; margin: 0; }
    .qf .qf-seg label small { display: block; color: #64748b; font-size: 11px; }
    .qf .qf-seg input:checked + label { border-color: var(--qf-accent); background: color-mix(in srgb, var(--qf-accent) 8%, white); color: #0f172a; }
    .qf #qfMap { height: 200px; border-radius: 14px; margin-top: 10px; }
    .qf .qf-btn { background: var(--qf-accent); border: 0; color: #fff; border-radius: 12px; padding: 13px; font-weight: 700; width: 100%; font-size: 16px; }
    .qf .qf-btn:disabled { opacity: .6; }
    .qf .qf-price { font-size: 2.2rem; font-weight: 800; line-height: 1; }
    .qf .qf-line { display: flex; justify-content: space-between; font-size: 14px; padding: 3px 0; color: #475569; }
    .qf .qf-err { color: #dc2626; font-size: 14px; margin-top: 8px; }
</style>

<div class="qf">
    @if(! $options['enabled'])
        <div class="qf-card"><strong>Instant prices are coming soon.</strong><p class="mb-0 text-muted small">Sign in to ask for a quote.</p></div>
    @else
    <div class="qf-card" id="qfCard">
        @if(count($options['centres'] ?? []))
        <div class="qf-seg mb-3">
            <input type="radio" name="qfFirst" id="qfFirstPickup" value="pickup" checked><label for="qfFirstPickup">We pick it up<small>from your address</small></label>
            <input type="radio" name="qfFirst" id="qfFirstStore" value="store"><label for="qfFirstStore">I'll drop it at a store<small>{{ count($options['centres']) }} partner {{ count($options['centres']) === 1 ? 'store' : 'stores' }}</small></label>
        </div>
        <div class="mb-3" id="qfStoreBox" hidden>
            <label class="qf-l" for="qfStore">Drop-off store</label>
            <select id="qfStore" class="form-select"></select>
            <div class="small text-muted mt-1" id="qfStoreHours"></div>
        </div>
        @endif
        <div class="mb-3 qf-addr" id="qfFromBox">
            <label class="qf-l" for="qfFrom">Pickup address</label>
            <input id="qfFrom" class="form-control" autocomplete="off" placeholder="Start typing an address">
            <div class="qf-sug" id="qfFromSug"></div>
        </div>
        <div class="mb-2 qf-addr">
            <label class="qf-l" for="qfTo">Drop-off address</label>
            <input id="qfTo" class="form-control" autocomplete="off" placeholder="Where is it going?">
            <div class="qf-sug" id="qfToSug"></div>
        </div>
        <div id="qfMap"></div>

        <label class="qf-l mt-3">When</label>
        <div class="qf-seg mb-2">
            @foreach($options['services'] as $i => $s)
                <input type="radio" name="qfService" id="qfS{{ $s['code'] }}" value="{{ $s['code'] }}" @checked($i === 0)><label for="qfS{{ $s['code'] }}">{{ $s['label'] }}<small>{{ $s['hint'] }}</small></label>
            @endforeach
        </div>
        <input type="datetime-local" id="qfAt" class="form-control mb-2" hidden>

        <label class="qf-l mt-2">Item size</label>
        <div class="qf-seg mb-2">
            @foreach($options['sizes'] as $i => $s)
                <input type="radio" name="qfSize" id="qfZ{{ $s['code'] }}" value="{{ $s['code'] }}" @checked($i === 0)><label for="qfZ{{ $s['code'] }}">{{ $s['label'] }}<small>{{ $s['hint'] }}</small></label>
            @endforeach
        </div>

        @if(count($options['vehicles']))
        <label class="qf-l mt-2">Vehicle</label>
        <div class="qf-seg mb-2">
            @foreach($options['vehicles'] as $i => $v)
                <input type="radio" name="qfVehicle" id="qfV{{ $v['code'] }}" value="{{ $v['code'] }}" @checked($i === 0)><label for="qfV{{ $v['code'] }}">{{ $v['name'] }}<small>up to {{ $v['max_kg'] }} kg</small></label>
            @endforeach
        </div>
        @endif

        <div class="form-check form-switch my-3"><input class="form-check-input" type="checkbox" id="qfFragile"><label class="form-check-label" for="qfFragile">Fragile, handle with care</label></div>

        <button class="qf-btn" id="qfGo" type="button">Get my price</button>
        <div class="qf-err" id="qfErr" hidden></div>

        <div id="qfResult" class="mt-3 pt-3 border-top" hidden>
            <div class="d-flex justify-content-between align-items-end mb-2">
                <div><div class="text-muted small">Your price</div><div class="qf-price" id="qfTotal"></div></div>
                <div class="text-end small text-muted" id="qfTrip"></div>
            </div>
            <div id="qfLines" class="mb-2"></div>
            <div class="small text-muted mb-3" id="qfValid"></div>
            <a class="qf-btn d-block text-center text-decoration-none" id="qfBook" href="#" @if($embed) target="_blank" rel="noopener" @endif>Book this delivery</a>
            <div class="small text-muted mt-2 text-center">You pay on the next step{{ config('region.country') === 'CA' ? ', by card or Interac e-Transfer' : '' }}.</div>
        </div>
    </div>
    @endif
</div>

@if($options['enabled'])
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var API = @json(url('/api/v1/public/quote')), BOOK = @json(url('/book')), COUNTRY = @json(strtolower((string) config('region.country')));
    var CENTRE = [{{ (float) config('region.map.lat') }}, {{ (float) config('region.map.lng') }}], ZOOM = {{ (int) config('region.map.zoom') }};
    var points = {from: null, to: null}, markers = {};
    var CENTRES = @json($options['centres'] ?? []), useStore = false;
    var map = L.map('qfMap', {zoomControl: false, attributionControl: true}).setView(CENTRE, ZOOM);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
    var $ = function (id) { return document.getElementById(id); };
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }

    function place(which, lat, lng, label) {
        points[which] = {lat: lat, lng: lng, line1: label};
        if (markers[which]) { markers[which].setLatLng([lat, lng]); }
        else {
            markers[which] = L.marker([lat, lng], {draggable: !(which === 'from' && useStore), title: which === 'from' ? 'Pickup' : 'Drop-off'}).addTo(map);
            markers[which].on('dragend', function (e) { var p = e.target.getLatLng(); points[which].lat = p.lat; points[which].lng = p.lng; reset(); });
        }
        var all = Object.keys(markers).map(function (k) { return markers[k].getLatLng(); });
        if (all.length === 2) { map.fitBounds(L.latLngBounds(all), {padding: [30, 30]}); } else { map.setView([lat, lng], 14); }
        reset();
    }

    function suggest(inputId, boxId, which) {
        var input = $(inputId), box = $(boxId), timer, items = [];
        input.addEventListener('input', function () {
            points[which] = null; reset();
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 4) { box.style.display = 'none'; return; }
            timer = setTimeout(function () {
                var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&addressdetails=0&q=' + encodeURIComponent(q) + (COUNTRY ? '&countrycodes=' + COUNTRY : '');
                fetch(url, {headers: {'Accept': 'application/json'}}).then(function (r) { return r.json(); }).then(function (rows) {
                    items = rows || [];
                    box.innerHTML = items.length ? items.map(function (r, i) { return '<div data-i="' + i + '">' + esc(r.display_name) + '</div>'; }).join('') : '<div class="text-muted">No match. Try the street and city.</div>';
                    box.style.display = 'block';
                }).catch(function () { box.style.display = 'none'; });
            }, 450);
        });
        box.addEventListener('click', function (e) {
            var i = e.target.getAttribute('data-i');
            if (i === null) return;
            var r = items[+i];
            input.value = r.display_name;
            box.style.display = 'none';
            place(which, parseFloat(r.lat), parseFloat(r.lon), r.display_name);
            if (which === 'to' && useStore) fillStores();
        });
        document.addEventListener('click', function (e) { if (!box.contains(e.target) && e.target !== input) box.style.display = 'none'; });
    }
    suggest('qfFrom', 'qfFromSug', 'from');
    suggest('qfTo', 'qfToSug', 'to');

    // "I'll drop it at a store": the trip starts at the chosen store; nearest to the drop-off first.
    function km(a, b) { var r = Math.PI / 180, x = (b.lng - a.lng) * r * Math.cos((a.lat + b.lat) * r / 2), y = (b.lat - a.lat) * r; return Math.sqrt(x * x + y * y) * 6371; }
    function fillStores() {
        var sel = $('qfStore'); if (!sel) return;
        var keep = sel.value, list = CENTRES.slice();
        if (points.to) list.sort(function (a, b) { return km(a, points.to) - km(b, points.to); });
        sel.innerHTML = list.map(function (c) { return '<option value="' + esc(c.id) + '">' + esc(c.name) + ' · ' + esc(c.address) + (points.to ? ' (' + km(c, points.to).toFixed(1) + ' km away)' : '') + '</option>'; }).join('');
        if (keep) sel.value = keep;
        pickStore();
    }
    function pickStore() {
        var c = CENTRES.filter(function (x) { return x.id === $('qfStore').value; })[0];
        if (!c) return;
        $('qfStoreHours').textContent = c.hours ? 'Open ' + c.hours : '';
        place('from', c.lat, c.lng, c.name + ', ' + c.address);
    }
    document.querySelectorAll('input[name="qfFirst"]').forEach(function (r) {
        r.addEventListener('change', function () {
            useStore = val('qfFirst') === 'store';
            $('qfStoreBox').hidden = !useStore; $('qfFromBox').hidden = useStore;
            points.from = null; if (markers.from) { map.removeLayer(markers.from); delete markers.from; }
            if (useStore) fillStores(); else $('qfFrom').value = '';
            reset();
        });
    });
    if ($('qfStore')) $('qfStore').addEventListener('change', pickStore);

    function val(name) { var el = document.querySelector('input[name="' + name + '"]:checked'); return el ? el.value : null; }
    document.querySelectorAll('input[name="qfService"]').forEach(function (r) { r.addEventListener('change', function () { $('qfAt').hidden = val('qfService') !== 'scheduled'; reset(); }); });
    document.querySelectorAll('#qfCard input').forEach(function (el) { if (el.type !== 'text') el.addEventListener('change', reset); });

    function reset() { $('qfResult').hidden = true; $('qfErr').hidden = true; }
    function error(m) { $('qfErr').textContent = m; $('qfErr').hidden = false; }

    $('qfGo').addEventListener('click', function () {
        if (!points.from) return error(useStore ? 'Choose a store.' : 'Choose the pickup address from the list.');
        if (!points.to) return error('Choose the drop-off address from the list.');
        if (val('qfService') === 'scheduled' && !$('qfAt').value) return error('Choose the pickup date and time.');
        var body = {pickup: useStore ? null : points.from, dropoff_centre: useStore ? $('qfStore').value : null, dropoff: points.to, service: val('qfService'), size: val('qfSize'), fragile: $('qfFragile').checked, vehicle: val('qfVehicle'), pickup_at: val('qfService') === 'scheduled' ? $('qfAt').value : null};
        $('qfGo').disabled = true; $('qfGo').textContent = 'Working out your price…'; reset();
        fetch(API, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json'}, body: JSON.stringify(body)})
            .then(function (r) { return r.json().then(function (j) { return {ok: r.ok, j: j}; }); })
            .then(function (res) {
                if (!res.ok) return error(res.j.message || 'We could not price this. Check the addresses.');
                var q = res.j;
                $('qfTotal').textContent = q.total_text;
                $('qfTrip').innerHTML = esc(q.distance_km) + ' km<br>about ' + esc(q.duration_min) + ' min drive';
                $('qfLines').innerHTML = q.lines.map(function (l) { return '<div class="qf-line"><span>' + esc(l.label) + '</span><span>' + esc(l.text) + '</span></div>'; }).join('')
                    + (q.tax ? '<div class="qf-line"><span>' + esc(q.tax_name) + '</span><span>' + esc(formatMoney(q.tax)) + '</span></div>' : '');
                $('qfValid').textContent = 'This price is held for 10 minutes.' + (q.pickup_at ? ' Pickup ' + new Date(q.pickup_at).toLocaleString() + '.' : '');
                $('qfBook').href = BOOK + '/' + encodeURIComponent(q.quote);
                $('qfResult').hidden = false;
            })
            .catch(function () { error('We could not reach the server. Try again.'); })
            .finally(function () { $('qfGo').disabled = false; $('qfGo').textContent = 'Get my price'; });
    });
    function formatMoney(minor) { return @json(\App\Support\Money::symbol()) + (minor / 100).toLocaleString(@json((string) config('region.locale')), {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
})();
</script>
@endif
