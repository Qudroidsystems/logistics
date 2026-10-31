{{--
    Address search for website forms, through the backend (/geo/search, /geo/place; provider in config/maps.php).
    Include once per page, then: AddressSearch.attach(inputEl, boxEl, {onPick: fn(place), onClear: fn, near: fn -> [lat,lng],
    item: 'div' | 'button', itemClass: '...'}). place = {lat, lng, label, city?, postal_code?}.
    With Nominatim (testing only) searches run on Enter, not as you type, as its usage policy requires.
--}}
@once
<script>
window.AddressSearch = (function () {
    var SEARCH = @json(route('geo.search')), PLACE = @json(route('geo.place'));
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
        input.setAttribute('autocomplete', 'off');
        input.addEventListener('input', function () {
            if (opts.onClear) opts.onClear();
            clearTimeout(timer);
            if (TYPE_AHEAD) { timer = setTimeout(run, 300); }
            else { show(input.value.trim().length >= 3 ? line('Press Enter to search', true) : ''); }
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); run(); }
        });
        box.addEventListener('click', function (e) {
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
    return {attach: attach, typeAhead: TYPE_AHEAD};
})();
</script>
@endonce
