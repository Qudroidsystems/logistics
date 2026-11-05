{{--
    Address search for website forms, through the backend (/geo/search, /geo/place; provider in config/maps.php).
    Include once per page, then: AddressSearch.attach(inputEl, boxEl, {onPick: fn(place), onClear: fn, near: fn -> [lat,lng],
    item: 'div' | 'button', itemClass: '...', saved: [{label, line1, lat, lng}] shown when the box is empty}). place = {lat, lng, label, city?, postal_code?}.
    With Nominatim (testing only) searches run on Enter, not as you type, as its usage policy requires.
--}}
@once
<script>
window.AddressSearch = (function () {
    var SEARCH = @json(route('geo.search')), PLACE = @json(route('geo.place')), REVERSE = @json(route('geo.reverse'));
    var TYPE_AHEAD = @json(app(\App\Modules\Maps\Geocoder::class)->typeAhead());
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }
    function token() { try { return crypto.randomUUID(); } catch (e) { return String(Date.now()) + Math.random().toString(16).slice(2); } }
    function attach(input, box, opts) {
        opts = opts || {};
        var tag = opts.item || 'div', cls = opts.itemClass || '', items = [], timer = null, session = token(), seq = 0;
        function show(html) { box.innerHTML = html; box.style.display = html ? 'block' : 'none'; }
        function line(text, muted) { return '<' + tag + (tag === 'button' ? ' type="button"' : '') + ' class="' + cls + (muted ? ' text-muted' : '') + '" data-none="1">' + esc(text) + '</' + tag + '>'; }
        function run() {
            var q = input.value.trim();
            if (q.length < 3) { show(''); return; }
            var my = ++seq, near = opts.near ? opts.near() : null;
            var url = SEARCH + '?q=' + encodeURIComponent(q) + '&session=' + session + (near ? '&lat=' + near[0] + '&lng=' + near[1] : '');
            show(line('Searching…', true));
            fetch(url, {headers: {'Accept': 'application/json'}}).then(function (r) { return r.json(); }).then(function (j) {
                if (my !== seq) return;
                items = (j && j.results) || [];
                show(items.length ? items.map(function (r, i) {
                    return '<' + tag + (tag === 'button' ? ' type="button"' : '') + ' class="' + cls + '" data-i="' + i + '">' + esc(r.label) + '</' + tag + '>';
                }).join('') : line('No match. Try the street number, street and city.', true));
            }).catch(function () { show(line('Address search is not available right now.', true)); });
        }
        var saved = opts.saved || [];
        function showSaved() {
            if (!saved.length || input.value.trim() !== '') return;
            show('<' + tag + ' class="' + cls + ' text-muted small" data-none="1">Your saved places</' + tag + '>' + saved.map(function (s, i) {
                return '<' + tag + (tag === 'button' ? ' type="button"' : '') + ' class="' + cls + '" data-s="' + i + '"><strong>' + esc(s.label || s.line1) + '</strong>' + (s.label ? ' · ' + esc(s.line1) : '') + '</' + tag + '>';
            }).join(''));
        }
        input.addEventListener('focus', showSaved);
        input.setAttribute('autocomplete', 'off');
        input.addEventListener('input', function () {
            if (opts.onClear) opts.onClear();
            clearTimeout(timer);
            if (TYPE_AHEAD) { timer = setTimeout(run, 300); }
            else { show(input.value.trim().length >= 3 ? line('Press Enter to search', true) : ''); }
            if (input.value.trim() === '') showSaved();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); run(); }
        });
        box.addEventListener('click', function (e) {
            var sv = e.target.closest('[data-s]');
            if (sv) { var s = saved[+sv.getAttribute('data-s')]; input.value = s.line1; show(''); if (opts.onPick) opts.onPick({lat: s.lat, lng: s.lng, label: s.line1}); return; }
            var el = e.target.closest('[data-i]'); if (!el) return;
            var r = items[+el.getAttribute('data-i')]; if (!r) return;
            input.value = r.label; show('');
            var done = function (p) { session = token(); if (opts.onPick) opts.onPick(p); };
            if (r.lat !== null && r.lat !== undefined) { done({lat: r.lat, lng: r.lng, label: r.label}); return; }
            fetch(PLACE + '?id=' + encodeURIComponent(r.id) + '&session=' + session, {headers: {'Accept': 'application/json'}})
                .then(function (x) { return x.json().then(function (j) { return {ok: x.ok, j: j}; }); })
                .then(function (res) { if (res.ok) done(res.j); else show(line(res.j.message || 'Pick the address again.', true)); })
                .catch(function () { show(line('Address search is not available right now.', true)); });
        });
        document.addEventListener('click', function (e) { if (!box.contains(e.target) && e.target !== input) box.style.display = 'none'; });
    }
    /** The browser's position, turned into an address by the server. done({lat, lng, label}) or fail(message). */
    function locate(done, fail) {
        if (!navigator.geolocation) { fail('Your browser cannot share its location. Type the address instead.'); return; }
        navigator.geolocation.getCurrentPosition(function (pos) {
            var lat = pos.coords.latitude, lng = pos.coords.longitude;
            fetch(REVERSE + '?lat=' + lat + '&lng=' + lng, {headers: {'Accept': 'application/json'}}).then(function (r) { return r.json(); })
                .then(function (j) { done({lat: lat, lng: lng, label: (j && j.label) || ''}); })
                .catch(function () { done({lat: lat, lng: lng, label: ''}); });
        }, function () { fail('We could not get your location. Allow location for this site, or type the address.'); }, {enableHighAccuracy: true, timeout: 10000, maximumAge: 60000});
    }
    return {attach: attach, locate: locate, typeAhead: TYPE_AHEAD};
})();
</script>
@endonce
