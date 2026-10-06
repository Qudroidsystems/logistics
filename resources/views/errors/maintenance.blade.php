@php
    $school = \App\Models\SchoolInformation::getActiveSchool() ?? \App\Models\SchoolInformation::first();
    $name = $school->school_name ?? config('app.name', 'School Portal');
    $logo = $school->logo_url ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $m->title ?: 'Down for maintenance' }} · {{ $name }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; padding: 24px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); color: #e2e8f0;
        }
        .card {
            max-width: 520px; width: 100%; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 18px; padding: 40px 32px; text-align: center; backdrop-filter: blur(6px);
            box-shadow: 0 24px 60px rgba(0,0,0,.35);
        }
        .logo { width: 72px; height: 72px; object-fit: contain; border-radius: 14px; background: #fff; padding: 8px; margin-bottom: 20px; }
        .icon { font-size: 46px; margin-bottom: 12px; }
        h1 { font-size: 1.5rem; margin: 0 0 6px; color: #fff; }
        .school { font-size: .9rem; letter-spacing: .04em; text-transform: uppercase; opacity: .7; margin-bottom: 22px; }
        p { line-height: 1.6; margin: 0 0 16px; opacity: .92; }
        .contact { margin-top: 24px; font-size: .9rem; opacity: .8; }
        .contact a { color: #7dd3fc; }
        .foot { margin-top: 28px; font-size: .8rem; opacity: .55; }
        .btn { display: inline-block; margin-top: 22px; padding: 10px 22px; border-radius: 10px; background: #14b8a6; color: #062925;
               font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        @if($logo)<img src="{{ $logo }}" alt="{{ $name }}" class="logo">@else<div class="icon">🛠️</div>@endif
        <div class="school">{{ $name }}</div>
        <h1>{{ $m->title ?: 'We\'ll be back shortly' }}</h1>
        <p>{{ $m->message ?: 'The portal is temporarily unavailable while we carry out maintenance. Please check back soon.' }}</p>
        @if($m->contact_info)
            <div class="contact"><i>Need help?</i><br>{{ $m->contact_info }}</div>
        @endif
        <a href="{{ url('/login') }}" class="btn">Staff sign in</a>
        <div class="foot">&copy; {{ date('Y') }} {{ $name }}</div>
    </div>
</body>
</html>
