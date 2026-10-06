{{-- Minimal guest layout for parent password reset pages. --}}
@php $school = \App\Models\SchoolInformation::getActiveSchool() ?? \App\Models\SchoolInformation::first(); @endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ $school->school_name ?? config('app.name') }}</title>
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css') }}" rel="stylesheet">
    <style>
        body{background:linear-gradient(135deg,#0f766e 0%,#115e59 55%,#134e4a 100%);min-height:100vh;display:flex;align-items:center;font-family:Poppins,system-ui,sans-serif}
        .pp-box{max-width:420px;width:100%;margin:24px auto;background:#fff;border-radius:18px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:32px}
        .pp-box .btn-primary{background:#0f766e;border-color:#0f766e}
        .pp-logo{height:56px;margin-bottom:12px}
    </style>
</head>
<body>
<div class="container px-3">
    <div class="pp-box">
        <div class="text-center mb-3">
            @if($school && method_exists($school, 'getLogoUrlAttribute'))<img src="{{ $school->getLogoUrlAttribute() }}" alt="" class="pp-logo">@endif
            <h5 class="mb-1">@yield('title')</h5>
            <p class="text-muted small mb-0">{{ $school->school_name ?? config('app.name') }} parent portal</p>
        </div>
        @if(session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        @yield('body')
        <div class="text-center mt-3 small"><a href="{{ route('login') }}" class="text-muted">Back to sign in</a></div>
    </div>
</div>
</body>
</html>
