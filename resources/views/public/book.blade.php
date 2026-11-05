@extends('public._layout', ['title' => 'Book your delivery', 'noindex' => true])
@section('content')
@php
    $svc = collect($options['services'])->firstWhere('code', $q['service']);
    $size = collect($options['sizes'])->firstWhere('code', $q['size']);
    $veh = collect($options['vehicles'])->firstWhere('code', $q['vehicle']);
    $tz = config('app.timezone');
    $at = $q['pickup_at'] ? \Illuminate\Support\Carbon::parse($q['pickup_at'])->setTimezone($tz) : null;
    $expired = \Illuminate\Support\Carbon::parse($q['expires_at'])->isPast();
@endphp
<div class="container py-4" style="max-width: 960px">
    <a href="{{ route('landing') }}" class="text-decoration-none small">&larr; Change the addresses</a>
    <h1 class="h3 fw-bold mt-2 mb-3">Book your delivery</h1>

    @foreach(['error' => 'danger', 'info' => 'info', 'success' => 'success'] as $k => $c)
        @if(session($k))<div class="alert alert-{{ $c }}">{{ session($k) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @if($q['booked'])
        <div class="alert alert-success">This price has already been booked. <a href="{{ route('account.dashboard') }}">See your deliveries</a>.</div>
    @else
    <div class="row g-4">
        <div class="col-lg-7 order-2 order-lg-1">
            <form method="post" action="{{ route('book.store', $q['quote']) }}" class="pub-card" onsubmit="if (window.cbTrack) window.cbTrack('booking_submitted', {mode: (event.submitter && event.submitter.value) || 'instant'})">
                @csrf
                @guest
                    <div class="d-flex justify-content-between align-items-baseline">
                        <h2 class="h6 fw-bold text-uppercase text-muted">About you</h2>
                        <a class="small" href="{{ route('login') }}">Have an account? Sign in</a>
                    </div>
                    @if(session('sign_in'))<div class="alert alert-info small py-2">That email already has an account. <a href="{{ route('login') }}">Sign in</a> and you'll come straight back to this price.</div>@endif
                    <div class="row g-2 mb-1">
                        <div class="col-12"><label class="form-label small fw-semibold" for="gn">Your name</label><input id="gn" name="guest_name" class="form-control" required maxlength="120" autocomplete="name" value="{{ old('guest_name') }}"></div>
                        <div class="col-sm-6"><label class="form-label small fw-semibold" for="ge">Email</label><input id="ge" name="guest_email" type="email" class="form-control" required maxlength="160" autocomplete="email" value="{{ old('guest_email') }}"></div>
                        <div class="col-sm-6"><label class="form-label small fw-semibold" for="gp">Mobile</label><input id="gp" name="guest_phone" type="tel" class="form-control" required maxlength="24" autocomplete="tel" placeholder="{{ config('region.phone_example') }}" value="{{ old('guest_phone') }}"></div>
                    </div>
                    <div class="form-text mb-2">No password needed now: we'll email you a link to set one, so you can track this delivery and book again.</div>
                    <div class="form-check small"><input class="form-check-input" type="checkbox" name="terms" value="1" id="gt" @checked(old('terms')) required><label class="form-check-label" for="gt">I accept the <a href="{{ route('terms') }}" target="_blank">terms</a> and <a href="{{ route('privacy') }}" target="_blank">privacy policy</a>.</label></div>
                    <div class="form-check small mb-4"><input class="form-check-input" type="checkbox" name="guest_offers" value="1" id="go" @checked(old('guest_offers'))><label class="form-check-label" for="go">Email me occasional offers (you can stop any time).</label></div>
                @endguest
                @if($q['centre'])
                    <h2 class="h6 fw-bold text-uppercase text-muted">You drop it at</h2>
                    <div class="p-3 rounded-3 mb-4" style="background:#f1f5f9">
                        <div class="fw-semibold">{{ $q['centre']['name'] }}</div>
                        <div class="small">{{ $q['centre']['address'] }}</div>
                        @if($q['centre']['hours'])<div class="small text-muted">Open {{ $q['centre']['hours'] }}</div>@endif
                        <div class="small mt-2">After you pay, we'll send you a code. Take the parcel to the counter and give them the code. A driver collects it from there.</div>
                        <input type="hidden" name="pickup_name" value="{{ $me?->name }}">
                    </div>
                @else
                <h2 class="h6 fw-bold text-uppercase text-muted">Pickup</h2>
                <p class="small mb-2">{{ $q['pickup']['line1'] ?? '' }}</p>
                <div class="row g-2 mb-2">
                    <div class="col-sm-6"><label class="form-label small fw-semibold" for="pn">Contact name</label><input id="pn" name="pickup_name" class="form-control" required maxlength="120" value="{{ old('pickup_name', $me?->name) }}" placeholder="{{ $me ? '' : 'Leave empty to use your name' }}" @guest data-optional="1" @endguest></div>
                    <div class="col-sm-6"><label class="form-label small fw-semibold" for="pp">Phone</label><input id="pp" name="pickup_phone" type="tel" class="form-control" required maxlength="24" placeholder="{{ config('region.phone_example') }}" value="{{ old('pickup_phone', $me?->phone_number) }}" @guest data-optional="1" @endguest></div>
                </div>
                <textarea name="pickup_instructions" class="form-control mb-4" rows="2" maxlength="300" placeholder="Unit, buzzer, where to find the item (optional)">{{ old('pickup_instructions') }}</textarea>
                @endif

                <h2 class="h6 fw-bold text-uppercase text-muted">Drop-off</h2>
                <p class="small mb-2">{{ $q['dropoff']['line1'] ?? '' }}</p>
                <div class="row g-2 mb-2">
                    <div class="col-sm-6"><label class="form-label small fw-semibold" for="dn">Recipient name</label><input id="dn" name="dropoff_name" class="form-control" required maxlength="120" value="{{ old('dropoff_name') }}"></div>
                    <div class="col-sm-6"><label class="form-label small fw-semibold" for="dp">Recipient phone</label><input id="dp" name="dropoff_phone" type="tel" class="form-control" required maxlength="24" placeholder="{{ config('region.phone_example') }}" value="{{ old('dropoff_phone') }}"></div>
                </div>
                <textarea name="dropoff_instructions" class="form-control mb-4" rows="2" maxlength="300" placeholder="Leave at the door, call on arrival… (optional)">{{ old('dropoff_instructions') }}</textarea>

                <h2 class="h6 fw-bold text-uppercase text-muted">The item</h2>
                <input name="description" class="form-control mb-3" required maxlength="200" placeholder="What are we delivering? e.g. a box of documents" value="{{ old('description') }}">

                @if($q['service'] === 'scheduled')
                    <label class="form-label small fw-semibold" for="at">Pickup time</label>
                    <input id="at" type="datetime-local" name="pickup_at" class="form-control mb-1" value="{{ old('pickup_at', $at?->format('Y-m-d\TH:i')) }}">
                    <div class="form-text mb-3">Changing the time may change the price; we'll show you before you pay.</div>
                @endif

                <button class="btn btn-brand btn-lg w-100 mt-2">Continue to payment</button>
                @if(($options['offers'] ?? false) && ! $q['centre'])
                    <div class="text-center small text-muted my-2">or</div>
                    <button name="mode" value="offers" class="btn btn-outline-dark w-100">Get offers from providers instead</button>
                    <div class="small text-muted text-center mt-1">Providers set their own price for this trip. You see each offer next to the {{ $q['total_text'] }} instant price and choose.</div>
                @endif
                <div class="small text-muted text-center mt-2">You won't be charged until you pay on the next page.</div>
            </form>
        </div>

        <div class="col-lg-5 order-1 order-lg-2">
            <div class="pub-card">
                <div class="text-muted small">Your price</div>
                <div class="display-6 fw-bold">{{ $q['total_text'] }}</div>
                <div class="small text-muted mb-3">{{ $q['distance_km'] }} km · about {{ $q['duration_min'] }} min drive · {{ $q['city'] }}</div>
                @foreach($q['lines'] as $l)
                    <div class="d-flex justify-content-between small py-1"><span>{{ $l['label'] }}</span><span>{{ $l['text'] }}</span></div>
                @endforeach
                @if($q['tax'])<div class="d-flex justify-content-between small py-1"><span>{{ $q['tax_name'] }}</span><span>{{ Money::format($q['tax'], true) }}</span></div>@endif
                <hr>
                <dl class="row small mb-0">
                    <dt class="col-5 fw-normal text-muted">When</dt><dd class="col-7">{{ $svc['label'] ?? $q['service'] }}@if($at)<br>{{ $at->format('D j M, g:i a') }}@endif</dd>
                    <dt class="col-5 fw-normal text-muted">Size</dt><dd class="col-7">{{ $size['label'] ?? $q['size'] }}</dd>
                    @if($veh)<dt class="col-5 fw-normal text-muted">Vehicle</dt><dd class="col-7">{{ $veh['name'] }}</dd>@endif
                    @if($q['fragile'])<dt class="col-5 fw-normal text-muted">Handling</dt><dd class="col-7">Fragile</dd>@endif
                </dl>
                <div class="small mt-3 {{ $expired ? 'text-warning' : 'text-muted' }}">
                    {{ $expired ? 'This price has expired. We will check it again when you continue.' : 'Price held until '.\Illuminate\Support\Carbon::parse($q['expires_at'])->setTimezone($tz)->format('g:i a').'.' }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@guest
<script>
// Guests: the pickup contact defaults to the person booking, so those two boxes are optional.
document.querySelectorAll('[data-optional="1"]').forEach(function (el) { el.required = false; });
</script>
@endguest
@endsection
