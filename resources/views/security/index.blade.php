@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid" style="max-width:900px">
    <x-cb.hero title="Sign-in security" icon="ri-shield-keyhole-line" subtitle="Two-step sign-in asks for a code from your phone after your password, so a stolen password alone cannot open your account." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('warning'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('warning') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    @if($codes)
    <x-cb.card title="Your recovery codes" icon="ri-key-2-line" class="mb-3">
        <p class="mb-2">If you lose your phone, each of these codes signs you in once instead of the app code. <strong>They are shown only now.</strong> Print them or keep them in a password manager.</p>
        <div class="row g-2 mb-2" style="font-family:ui-monospace,monospace;font-size:15px">
            @foreach($codes as $c)<div class="col-6 col-md-3"><code style="user-select:all">{{ $c }}</code></div>@endforeach
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard && navigator.clipboard.writeText(@js(implode("\n", $codes)))"><i class="ri-file-copy-line"></i> Copy</button>
    </x-cb.card>
    @endif

    @if($enabled)
        <x-cb.card title="Two-step sign-in is on" icon="ri-checkbox-circle-line">
            <p class="mb-1">On since {{ \Carbon\Carbon::parse($since)->format('d M Y') }}. You have <strong>{{ $codesLeft }}</strong> recovery {{ $codesLeft === 1 ? 'code' : 'codes' }} left.</p>
            @if($required)<p class="text-muted small mb-3">{{ $required }}</p>@endif
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('security.recovery-codes') }}">@csrf
                        <label class="form-label small">New recovery codes (the old ones stop working)</label>
                        <div class="d-flex gap-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="Your password" required autocomplete="current-password">
                        <button class="btn btn-sm btn-outline-primary text-nowrap">Make new codes</button></div>
                    </form>
                </div>
                @unless($required)
                <div class="col-md-6">
                    <form method="POST" action="{{ route('security.two-factor.disable') }}" onsubmit="return confirm('Turn off two-step sign-in?')">@csrf @method('DELETE')
                        <label class="form-label small">Turn it off</label>
                        <div class="d-flex gap-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="Your password" required autocomplete="current-password">
                        <button class="btn btn-sm btn-outline-danger text-nowrap">Turn off</button></div>
                    </form>
                </div>
                @endunless
            </div>
            @error('password')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </x-cb.card>
    @elseif($secret)
        <x-cb.card title="Set up your authenticator app" icon="ri-qr-code-line">
            <div class="row g-4 align-items-center">
                <div class="col-md-5 text-center">
                    <div style="display:inline-block;background:#fff;padding:8px;border-radius:8px">{!! $qr !!}</div>
                    <div class="small text-muted mt-2">Can't scan? Type this key:</div>
                    <code style="user-select:all;word-break:break-all">{{ trim(chunk_split($secret, 4, ' ')) }}</code>
                </div>
                <div class="col-md-7">
                    <ol class="ps-3 mb-3">
                        <li>Install an authenticator app on your phone (Google Authenticator, Microsoft Authenticator, Authy or 1Password).</li>
                        <li>In the app, add an account and scan this QR code.</li>
                        <li>Type the 6-digit code the app shows.</li>
                    </ol>
                    <form method="POST" action="{{ route('security.two-factor.confirm') }}" autocomplete="off">@csrf
                        <div class="d-flex gap-2">
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" inputmode="numeric" maxlength="7" placeholder="123456" autocomplete="one-time-code" required autofocus style="max-width:160px;letter-spacing:4px">
                            <button class="btn btn-primary">Turn on</button>
                        </div>
                        @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </form>
                </div>
            </div>
        </x-cb.card>
    @else
        <x-cb.card title="Two-step sign-in is off" icon="ri-shield-line">
            @if($required)<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $required }}</div></div>@endif
            <p>You will need an authenticator app on your phone. It takes about a minute.</p>
            <form method="POST" action="{{ route('security.two-factor.start') }}">@csrf<button class="btn btn-primary"><i class="ri-shield-keyhole-line"></i> Set up two-step sign-in</button></form>
        </x-cb.card>
    @endif
</div></div></div>
@endsection
