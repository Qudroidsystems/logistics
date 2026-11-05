{{--
    Website analytics (config/services.php 'analytics'). window.cbTrack(event, props) records a funnel step:
    price_shown, book_clicked, booking_submitted. Plausible sets no cookies and loads straight away; Google Analytics 4
    loads only after the visitor accepts (Quebec's Law 25 and PIPEDA expect consent for tracking cookies).
--}}
@php $an = config('services.analytics'); @endphp
<script>window.cbTrack = function () {};</script>
@if(($an['driver'] ?? null) === 'plausible' && ! empty($an['plausible_domain']))
    <script defer data-domain="{{ $an['plausible_domain'] }}" src="https://plausible.io/js/script.js"></script>
    <script>window.plausible = window.plausible || function () { (window.plausible.q = window.plausible.q || []).push(arguments); };
        window.cbTrack = function (e, p) { window.plausible(e, p ? {props: p} : undefined); };</script>
@elseif(($an['driver'] ?? null) === 'ga4' && ! empty($an['ga_id']))
    <div id="cbConsent" style="position:fixed;left:12px;right:12px;bottom:12px;z-index:1060;max-width:560px;display:none" class="bg-white border rounded-3 shadow p-3 small">
        We use analytics cookies to see which pages help people book, and nothing else. <a href="{{ route('privacy') }}">Privacy policy</a>
        <div class="mt-2 d-flex gap-2"><button type="button" class="btn btn-sm btn-dark" id="cbYes">Accept</button><button type="button" class="btn btn-sm btn-outline-secondary" id="cbNo">No thanks</button></div>
    </div>
    <script>
    (function () {
        var ID = @json($an['ga_id']), KEY = 'cb_analytics', choice = null;
        try { choice = localStorage.getItem(KEY); } catch (e) {}
        function load() {
            var s = document.createElement('script'); s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ID); document.head.appendChild(s);
            window.dataLayer = window.dataLayer || []; window.gtag = function () { dataLayer.push(arguments); };
            gtag('js', new Date()); gtag('config', ID, {anonymize_ip: true});
            window.cbTrack = function (e, p) { gtag('event', e, p || {}); };
        }
        function save(v) { try { localStorage.setItem(KEY, v); } catch (e) {} document.getElementById('cbConsent').style.display = 'none'; }
        if (choice === 'yes') { load(); }
        else if (choice !== 'no') {
            document.getElementById('cbConsent').style.display = 'block';
            document.getElementById('cbYes').onclick = function () { save('yes'); load(); };
            document.getElementById('cbNo').onclick = function () { save('no'); };
        }
    })();
    </script>
@endif
