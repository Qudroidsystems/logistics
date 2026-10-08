@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Phone number" icon="ri-smartphone-line" subtitle="We text you delivery updates and codes here." :back="$back" back-label="Back" />
    @include('account._flash')
    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Your number" icon="ri-smartphone-line">
                <form method="POST" action="{{ route($area.'.phone.save') }}">@csrf
                    <label class="form-label small">Mobile number</label>
                    <input type="tel" name="phone" class="form-control mb-2" placeholder="0803 123 4567" value="{{ old('phone', $u->phone_number) }}" required maxlength="24">
                    <button class="btn btn-primary w-100">Save number</button>
                </form>
                @if($u->phone_number)
                    <div class="mt-3 small">
                        @if($verified)<span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line"></i> Confirmed</span>
                        @else<span class="badge bg-warning-subtle text-warning"><i class="ri-error-warning-line"></i> Not confirmed yet</span>@endif
                    </div>
                @endif
            </x-cb.card>
        </div>
        @if($u->phone_number && ! $verified)
        <div class="col-lg-6">
            <x-cb.card title="Confirm it" icon="ri-shield-check-line">
                @unless($smsLive)<div class="cb-banner info mb-2"><i class="ri-information-line"></i><div>Texting is not switched on yet, so no code will arrive. Please check back soon.</div></div>@endunless
                <form method="POST" action="{{ route($area.'.phone.send') }}" class="mb-3">@csrf
                    <button class="btn btn-outline-primary w-100">Text me a code</button>
                </form>
                <form method="POST" action="{{ route($area.'.phone.verify') }}">@csrf
                    <label class="form-label small">6-digit code</label>
                    <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control mb-2" autocomplete="one-time-code" required>
                    <button class="btn btn-primary w-100">Confirm</button>
                </form>
            </x-cb.card>
        </div>
        @endif
    </div>
</div></div></div>
@endsection
