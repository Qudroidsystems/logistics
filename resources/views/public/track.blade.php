@extends('public._layout', ['title' => 'Track a delivery'])
@section('content')
<div class="container py-5" style="max-width: 560px">
    <div class="pub-card">
        <h1 class="h4 fw-bold">Track a delivery</h1>
        <p class="text-muted small">Enter the tracking code from your confirmation. Add the last 4 digits of the sender's or recipient's phone to see the driver on the map.</p>
        <form method="get" action="{{ route('track.lookup') }}">
            <div class="row g-2">
                <div class="col-sm-7"><label class="form-label small fw-semibold" for="code">Tracking code</label><input id="code" name="code" class="form-control text-uppercase" required maxlength="32" value="{{ $code }}" autofocus></div>
                <div class="col-sm-5"><label class="form-label small fw-semibold" for="p4">Phone, last 4</label><input id="p4" name="phone4" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="1234"></div>
            </div>
            <button class="btn btn-brand w-100 mt-3">Track</button>
        </form>

        @if($result === false)
            <div class="alert alert-warning mt-3 mb-0">We couldn't find a delivery with code <strong>{{ $code }}</strong>. Check the code and try again.</div>
        @elseif(is_array($result))
            <div class="mt-4 p-3 rounded-3" style="background:#f1f5f9">
                <div class="small text-muted">Delivery {{ $code }}</div>
                <div class="fs-4 fw-bold">{{ $result['status'] }}</div>
                <div class="small text-muted">Updated {{ $result['updated'] }}</div>
            </div>
            @if($phoneTried ?? false)
                <div class="small text-muted mt-2">That phone number doesn't match this delivery, or live tracking isn't available yet.</div>
            @endif
        @endif
    </div>
</div>
@endsection
