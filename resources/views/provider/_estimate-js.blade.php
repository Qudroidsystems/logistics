{{-- Calls the server calculator and draws the result. Used on the pricing page and the request page. --}}
<script>
window.qEstimate = (function () {
    var esc = function (t) { var d = document.createElement('div'); d.textContent = String(t == null ? '' : t); return d.innerHTML; };
    var naira = function (k) { return '₦' + Math.round((k || 0) / 100).toLocaleString('en-NG'); };
    var label = {fuel: 'Fuel', maintenance: 'Maintenance', labour: 'Labour', other: 'Other per-job costs', job_expenses: 'Tolls and parking', return_trip: 'Empty return trip',
        loading: 'Loading', waiting: 'Waiting time', fragile: 'Fragile handling', insurance: 'Insurance'};
    var methodName = {per_km: 'Per-kilometre rate', cost_plus: 'Cost plus markup', higher_of: 'Higher of rate and cost plus markup'};
    function rows(obj) {
        return Object.keys(obj || {}).map(function (k) { return '<tr><td>' + (label[k] || k) + '</td><td class="text-end">' + naira(obj[k]) + '</td></tr>'; }).join('');
    }
    function render(el, r) {
        var warn = r.warning === 'loss'
            ? '<div class="cb-banner warning mb-2"><i class="ri-error-warning-line"></i><div>At this price the job loses money: after the platform fee you would earn ' + naira(r.provider_net) + ' against ' + naira(r.operating_cost) + ' in costs.</div></div>'
            : r.warning === 'thin_margin' ? '<div class="cb-banner warning mb-2"><i class="ri-error-warning-line"></i><div>The margin on this job is thin (' + (r.margin_bp / 100).toFixed(1) + '% of the price).</div></div>' : '';
        var routeNote = r.route.provider === 'osrm' ? 'road route' : r.route.provider === 'manual' ? 'distance you entered' : 'estimated from straight-line distance (no road route available)';
        el.innerHTML = warn +
            '<div class="d-flex justify-content-between align-items-baseline"><span class="text-muted">Suggested price</span><span class="fs-3 fw-bold">' + naira(r.suggested_price) + '</span></div>' +
            '<div class="small text-muted mb-2">' + (r.distance_m / 1000).toFixed(1) + ' km, about ' + Math.max(1, Math.round(r.duration_s / 60)) + ' min (' + routeNote + '). ' + methodName[r.method] + ', profile "' + esc(r.profile.name) + '" v' + r.profile.version + '.' + (r.min_fee_applied ? ' Your minimum fee applied.' : '') + '</div>' +
            '<table class="table table-sm mb-2"><tbody>' + rows(r.costs) +
            '<tr class="fw-semibold"><td>Operating cost</td><td class="text-end">' + naira(r.operating_cost) + '</td></tr>' + rows(r.surcharges) +
            '<tr><td>By your per-km rate</td><td class="text-end">' + naira(r.per_km_price) + '</td></tr>' +
            '<tr><td>By cost plus markup</td><td class="text-end">' + naira(r.cost_plus_price) + '</td></tr>' +
            '<tr><td>Platform fee</td><td class="text-end">' + naira(r.platform_fee) + '</td></tr>' +
            '<tr class="fw-semibold"><td>You receive</td><td class="text-end">' + naira(r.provider_net) + '</td></tr>' +
            '<tr class="fw-semibold"><td>Your margin</td><td class="text-end">' + naira(r.margin) + ' (' + (r.margin_bp / 100).toFixed(1) + '%)</td></tr>' +
            '</tbody></table><div class="small text-muted">Saved as estimate ' + r.estimate + '. Later changes to your profile do not alter it.</div>';
    }
    function run(url, body, el, done) {
        el.innerHTML = '<div class="text-muted">Working it out…</div>';
        fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}, body: JSON.stringify(body), credentials: 'same-origin'})
            .then(function (res) { return res.json().then(function (j) { return {ok: res.ok, j: j}; }); })
            .then(function (x) {
                if (!x.ok) { el.innerHTML = '<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>' + esc(x.j.message || 'That could not be worked out.') + '</div></div>'; return; }
                render(el, x.j); if (done) { done(x.j); }
            })
            .catch(function () { el.innerHTML = '<div class="cb-banner warning"><i class="ri-wifi-off-line"></i><div>No connection to the server. Try again when you are back online.</div></div>'; });
    }
    return {run: run, naira: naira};
})();
</script>
