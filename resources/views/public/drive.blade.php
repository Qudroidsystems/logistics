@extends('public._layout', ['title' => 'Drive with us · '.$brand['name']])
@section('content')
<section style="background: linear-gradient(135deg, var(--brand2), var(--brand));" class="text-white">
    <div class="container py-5" style="max-width: 980px">
        <h1 class="display-6 fw-bold">Drive with us</h1>
        <p class="lead opacity-75 mb-0">Deliver parcels around {{ config('region.country_name') }} on your own schedule. Use your own car, get paid weekly, and choose the jobs you take.</p>
    </div>
</section>
<div class="container py-4" style="max-width: 980px">
    @if(! $open)
        <div class="pub-card text-center py-5"><h2 class="h5 fw-bold">We're not taking new drivers right now.</h2><p class="text-muted mb-0">Check back soon.</p></div>
    @else
    <div class="row g-4">
        <div class="col-lg-4 order-lg-2">
            <div class="pub-card">
                <h2 class="h6 fw-bold">What you need</h2>
                <ul class="small text-muted ps-3 mb-3">
                    <li>A valid driver's licence (a photo or scan)</li>
                    <li>For your own vehicle: insurance and registration</li>
                    <li>A smartphone for the driver app</li>
                    <li>Optional: a recent driver's abstract</li>
                </ul>
                <h2 class="h6 fw-bold">What happens next</h2>
                <ol class="small text-muted ps-3 mb-0">
                    <li>We check your documents (usually within 3 business days).</li>
                    <li>We may call you for a quick chat.</li>
                    <li>Once you're approved, you sign in to the driver app and go online.</li>
                </ol>
            </div>
        </div>
        <div class="col-lg-8 order-lg-1">
            <form method="post" action="{{ route('drive.store') }}" enctype="multipart/form-data" class="pub-card" id="dform">
                @csrf
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">

                <h2 class="h6 fw-bold text-uppercase text-muted">About you</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="nm">Full name (as on your licence)</label><input id="nm" name="name" class="form-control" required maxlength="120" value="{{ old('name', auth()->user()?->name) }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="ph">Mobile number</label><input id="ph" name="phone" type="tel" class="form-control" required maxlength="24" placeholder="{{ config('region.phone_example') }}" value="{{ old('phone', auth()->user()?->phone_number) }}"></div>
                    <div class="col-12"><label class="form-label small fw-semibold" for="em">Email</label><input id="em" name="email" type="email" class="form-control" required maxlength="160" value="{{ old('email', auth()->user()?->email) }}"><div class="form-text">You'll sign in to the driver app with this.</div></div>
                    <div class="col-md-5"><label class="form-label small fw-semibold" for="ci">City</label><input id="ci" name="city" class="form-control" required maxlength="80" value="{{ old('city') }}"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="sd">{{ config('region.subdivision_label', 'Province') }}</label>
                        <select id="sd" name="subdivision" class="form-select"><option value=""></option>@foreach($subdivisions as $k => $v)<option value="{{ $k }}" @selected(old('subdivision') === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold" for="pc">Postal code</label><input id="pc" name="postal_code" class="form-control text-uppercase" maxlength="12" value="{{ old('postal_code') }}"></div>
                </div>

                <h2 class="h6 fw-bold text-uppercase text-muted">Driving</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="ex">Delivery or driving-for-work experience</label>
                        <select id="ex" name="experience" class="form-select" required><option value="">Choose…</option>@foreach($experience as $k => $v)<option value="{{ $k }}" @selected(old('experience') === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold d-block">When can you drive?</label>
                        @foreach($availability as $k => $v)<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="availability[]" value="{{ $k }}" id="av{{ $k }}" @checked(in_array($k, old('availability', []), true))><label class="form-check-label small" for="av{{ $k }}">{{ $v }}</label></div>@endforeach</div>
                    <div class="col-12"><label class="form-label small fw-semibold" for="en">Tell us about it (optional)</label><textarea id="en" name="experience_notes" class="form-control" rows="2" maxlength="500" placeholder="e.g. 2 years with a courier company, food delivery…">{{ old('experience_notes') }}</textarea></div>
                </div>

                <h2 class="h6 fw-bold text-uppercase text-muted">Driver's licence</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label small fw-semibold" for="lc">Class</label><input id="lc" name="licence_class" class="form-control text-uppercase" required maxlength="12" placeholder="{{ config('region.country') === 'CA' ? 'G' : '' }}" value="{{ old('licence_class') }}"></div>
                    <div class="col-md-5"><label class="form-label small fw-semibold" for="ln">Licence number</label><input id="ln" name="licence_number" class="form-control" required maxlength="30" autocomplete="off" value="{{ old('licence_number') }}"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="le">Expiry date</label><input id="le" name="licence_expires_on" type="date" class="form-control" required value="{{ old('licence_expires_on') }}"></div>
                    <div class="col-12"><label class="form-label small fw-semibold" for="lf">Photo or scan of your licence</label><input id="lf" name="licence_file" type="file" class="form-control" required accept="image/*,application/pdf"><div class="form-text">JPG, PNG or PDF, up to 8 MB.</div></div>
                </div>

                <h2 class="h6 fw-bold text-uppercase text-muted">Vehicle</h2>
                <div class="mb-3">
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_vehicle" value="yes" id="hvY" @checked(old('has_vehicle', 'yes') === 'yes')><label class="form-check-label" for="hvY">I'll use my own vehicle</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="has_vehicle" value="no" id="hvN" @checked(old('has_vehicle') === 'no')><label class="form-check-label" for="hvN">I need a vehicle</label></div>
                </div>
                <div id="vehBox" class="row g-3 mb-4">
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="vt">Type</label><select id="vt" name="vehicle_type" class="form-select">@foreach($vehicles as $code => $name)<option value="{{ $code }}" @selected(old('vehicle_type') === $code)>{{ $name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="vm">Make</label><input id="vm" name="vehicle_make" class="form-control" maxlength="40" placeholder="Toyota" value="{{ old('vehicle_make') }}"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="vo">Model</label><input id="vo" name="vehicle_model" class="form-control" maxlength="40" placeholder="Corolla" value="{{ old('vehicle_model') }}"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="vy">Year</label><input id="vy" name="vehicle_year" type="number" min="1990" max="{{ date('Y') + 1 }}" class="form-control" value="{{ old('vehicle_year') }}"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="pl">Licence plate</label><input id="pl" name="plate" class="form-control text-uppercase" maxlength="12" value="{{ old('plate') }}"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold" for="ic">Insurance company</label><input id="ic" name="insurance_company" class="form-control" maxlength="80" value="{{ old('insurance_company') }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="ip">Policy number</label><input id="ip" name="insurance_policy" class="form-control" maxlength="40" autocomplete="off" value="{{ old('insurance_policy') }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="ie">Insurance expiry</label><input id="ie" name="insurance_expires_on" type="date" class="form-control" value="{{ old('insurance_expires_on') }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="if">Insurance card (pink slip)</label><input id="if" name="insurance_file" type="file" class="form-control" accept="image/*,application/pdf"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold" for="rf">Vehicle registration</label><input id="rf" name="registration_file" type="file" class="form-control" accept="image/*,application/pdf"></div>
                </div>
                <div class="mb-4"><label class="form-label small fw-semibold" for="af">Driver's abstract (optional)</label><input id="af" name="abstract_file" type="file" class="form-control" accept="image/*,application/pdf"><div class="form-text">Speeds up your approval if you have a recent one.</div></div>

                <h2 class="h6 fw-bold text-uppercase text-muted">Permissions</h2>
                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="sms_consent" value="1" id="sms" @checked(old('sms_consent'))><label class="form-check-label small" for="sms">Text me about my application and, once I'm driving, about delivery jobs. Message and data rates may apply. Reply STOP to opt out at any time.</label></div>
                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="background_check_consent" value="1" id="bg" @checked(old('background_check_consent')) required><label class="form-check-label small" for="bg">I agree to {{ $brand['name'] }} checking my licence and driving record.</label></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="terms" value="1" id="tm" @checked(old('terms')) required><label class="form-check-label small" for="tm">I have read and accept the <a href="{{ route('drive.terms') }}" target="_blank">driver terms</a>.</label></div>

                <button class="btn btn-brand btn-lg w-100">Send my application</button>
            </form>
        </div>
    </div>
    @endif
</div>
<script>
(function () {
    var box = document.getElementById('vehBox'); if (!box) return;
    function sync() {
        var own = document.getElementById('hvY').checked;
        box.style.display = own ? '' : 'none';
        ['vm', 'vo', 'vy', 'pl', 'ic', 'ip', 'ie', 'if', 'rf'].forEach(function (id) { document.getElementById(id).required = own; });
    }
    document.querySelectorAll('input[name="has_vehicle"]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();
})();
</script>
@endsection
