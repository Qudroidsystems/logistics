@extends('public._layout', ['title' => 'Become a drop-off partner · '.$brand['name']])
@section('content')
<section style="background: linear-gradient(135deg, var(--brand2), var(--brand));" class="text-white">
    <div class="container py-5" style="max-width: 980px">
        <h1 class="display-6 fw-bold">Become a drop-off partner</h1>
        <p class="lead opacity-75 mb-0">Let senders hand in parcels at your counter. A driver collects them, and you earn on every parcel, with more people walking through your door.</p>
    </div>
</section>
<div class="container py-4" style="max-width: 980px">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="pub-card">
                <h2 class="h6 fw-bold">How it works</h2>
                <ol class="small text-muted ps-3 mb-0">
                    <li class="mb-2">A sender books online and chooses your store.</li>
                    <li class="mb-2">They bring the parcel. You type its code on the counter screen.</li>
                    <li class="mb-2">A driver collects it, usually the same day. You mark it handed over.</li>
                    <li>You're paid your commission every month.</li>
                </ol>
            </div>
        </div>
        <div class="col-lg-8">
            @if($sent)
                <div class="pub-card text-center py-5">
                    <div class="display-6">✓</div>
                    <h2 class="h4 fw-bold">Thanks, we've got your application.</h2>
                    <p class="text-muted mb-0">We'll be in touch within a few business days. Once you're approved, you'll get an email to sign in to your counter screen.</p>
                </div>
            @else
            <form method="post" action="{{ route('partners.store') }}" class="pub-card" id="pform">
                @csrf
                @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                <div class="row g-3">
                    <div class="col-md-7"><label class="form-label small fw-semibold" for="bn">Business name</label><input id="bn" name="business_name" class="form-control" required maxlength="120" value="{{ old('business_name') }}"></div>
                    <div class="col-md-5"><label class="form-label small fw-semibold" for="bt">Type of business</label>
                        <select id="bt" name="business_type" class="form-select"><option value="">Choose…</option>@foreach($types as $k => $v)<option value="{{ $k }}" @selected(old('business_type') === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="cn">Your name</label><input id="cn" name="contact_name" class="form-control" required maxlength="120" value="{{ old('contact_name') }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="ph">Phone</label><input id="ph" name="phone" type="tel" class="form-control" required maxlength="24" placeholder="{{ config('region.phone_example') }}" value="{{ old('phone') }}"></div>
                    <div class="col-12"><label class="form-label small fw-semibold" for="em">Email</label><input id="em" name="email" type="email" class="form-control" required maxlength="160" value="{{ old('email') }}"><div class="form-text">We'll send your sign-in to this address.</div></div>
                    <div class="col-12 position-relative"><label class="form-label small fw-semibold" for="ad">Store address</label><input id="ad" name="line1" class="form-control" required maxlength="200" autocomplete="off" value="{{ old('line1') }}" placeholder="Street and number">
                        <div id="adSug" class="list-group position-absolute w-100 shadow-sm" style="z-index:10; display:none"></div></div>
                    <div class="col-md-5"><label class="form-label small fw-semibold" for="ci">City</label><input id="ci" name="city" class="form-control" required maxlength="80" value="{{ old('city') }}"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="sd">{{ config('region.subdivision_label', 'Province') }}</label>
                        <select id="sd" name="subdivision" class="form-select"><option value=""></option>@foreach($subdivisions as $k => $v)<option value="{{ $k }}" @selected(old('subdivision') === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold" for="pc">Postal code</label><input id="pc" name="postal_code" class="form-control text-uppercase" maxlength="12" value="{{ old('postal_code') }}"></div>
                    <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}"><input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
                    <div class="col-12"><label class="form-label small fw-semibold" for="oh">Opening hours</label><input id="oh" name="opening_hours" class="form-control" maxlength="160" placeholder="e.g. Mon–Sat 9am–9pm, Sun 11am–6pm" value="{{ old('opening_hours') }}"></div>
                    <div class="col-12"><label class="form-label small fw-semibold" for="ms">Anything else? (optional)</label><textarea id="ms" name="message" class="form-control" rows="3" maxlength="1000">{{ old('message') }}</textarea></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="consent" value="1" id="cs" @checked(old('consent')) required><label class="form-check-label small" for="cs">You can contact me by email, phone or text about this application.</label></div></div>
                </div>
                <button class="btn btn-brand btn-lg w-100 mt-4">Send my application</button>
            </form>
            @endif
        </div>
    </div>
</div>
@unless($sent)
<script>
(function () {
    var input = document.getElementById('ad'), box = document.getElementById('adSug'), timer, items = [];
    var COUNTRY = @json(strtolower((string) config('region.country')));
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }
    input.addEventListener('input', function () {
        document.getElementById('lat').value = ''; document.getElementById('lng').value = '';
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 5) { box.style.display = 'none'; return; }
        timer = setTimeout(function () {
            fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&countrycodes=' + COUNTRY + '&q=' + encodeURIComponent(q), {headers: {'Accept': 'application/json'}})
                .then(function (r) { return r.json(); }).then(function (rows) {
                    items = rows || [];
                    box.innerHTML = items.map(function (r, i) { return '<button type="button" class="list-group-item list-group-item-action small" data-i="' + i + '">' + esc(r.display_name) + '</button>'; }).join('');
                    box.style.display = items.length ? 'block' : 'none';
                }).catch(function () { box.style.display = 'none'; });
        }, 450);
    });
    box.addEventListener('click', function (e) {
        var i = e.target.getAttribute('data-i'); if (i === null) return;
        var r = items[+i], a = r.address || {};
        input.value = [a.house_number, a.road].filter(Boolean).join(' ') || r.display_name.split(',')[0];
        document.getElementById('ci').value = a.city || a.town || a.village || a.municipality || '';
        if (a.postcode) document.getElementById('pc').value = a.postcode;
        document.getElementById('lat').value = r.lat; document.getElementById('lng').value = r.lon;
        box.style.display = 'none';
    });
})();
</script>
@endunless
@endsection
