<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title>Sign-in code | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <style>
        body { background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Poppins, sans-serif; }
        .tf-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.08); padding: 32px; width: 100%; max-width: 400px; margin: 16px; }
        .tf-code { font-size: 28px; letter-spacing: 8px; text-align: center; font-variant-numeric: tabular-nums; }
    </style>
</head>
<body>
<div class="tf-card">
    <div class="text-center mb-3"><i class="ri-shield-keyhole-line" style="font-size:40px;color:#4f46e5"></i></div>
    <h4 class="text-center mb-2">Enter your sign-in code</h4>
    <p class="text-muted text-center small mb-4" id="tf-hint">Open your authenticator app and type the 6-digit code for {{ config('app.name') }}.</p>

    <form method="POST" action="{{ route('two-factor.verify') }}" autocomplete="off">
        @csrf
        <input type="text" name="code" id="tf-input" class="form-control tf-code @error('code') is-invalid @enderror" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
               autocomplete="one-time-code" placeholder="000000" autofocus required>
        @error('code')<div class="invalid-feedback text-center">{{ $message }}</div>@enderror
        <button class="btn btn-primary w-100 mt-3">Continue</button>
    </form>

    <div class="text-center mt-3 small">
        <a href="#" id="tf-switch">Lost your phone? Use a recovery code</a>
    </div>
    <div class="text-center mt-2 small"><a href="{{ route('login') }}" class="text-muted">Back to sign in</a></div>
</div>
<script>
    document.getElementById('tf-switch').addEventListener('click', function (e) {
        e.preventDefault();
        var i = document.getElementById('tf-input');
        i.removeAttribute('pattern'); i.setAttribute('maxlength', '11'); i.setAttribute('inputmode', 'text'); i.setAttribute('placeholder', 'XXXXX-XXXXX');
        i.classList.remove('tf-code'); i.value = ''; i.focus();
        document.getElementById('tf-hint').textContent = 'Type one of the recovery codes you saved when you turned on two-step sign-in. Each works once.';
        this.style.display = 'none';
    });
</script>
</body>
</html>
