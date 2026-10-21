<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? $brand['name'] }}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
    :root { --brand: {{ $brand['primary_color'] }}; --brand2: {{ $brand['secondary_color'] }}; }
    body { background: #f6f7f9; color: #0f172a; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
    .pub-nav { background: #fff; border-bottom: 1px solid #e5e7eb; }
    .pub-nav .brand { font-weight: 800; color: #0f172a; text-decoration: none; display: flex; align-items: center; gap: 8px; }
    .pub-nav .brand img { height: 32px; }
    .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
    .btn-brand:hover { background: var(--brand2); border-color: var(--brand2); color: #fff; }
    .pub-card { background: #fff; border-radius: 18px; box-shadow: 0 8px 30px rgba(15, 23, 42, .07); padding: 22px; }
    footer.pub { color: #64748b; font-size: 14px; }
    .wa-float { position: fixed; right: 18px; bottom: 18px; z-index: 1050; width: 58px; height: 58px; border-radius: 50%; background: #25D366; color: #fff;
        display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,.2); text-decoration: none; }
    .wa-float:hover { color: #fff; transform: scale(1.05); }
    .wa-float svg { width: 30px; height: 30px; fill: currentColor; }
</style>
</head>
<body>
@unless($embed ?? false)
<nav class="pub-nav">
    <div class="container d-flex align-items-center justify-content-between py-2 gap-2">
        <a class="brand" href="{{ route('landing') }}">@if($brand['logo_url'])<img src="{{ $brand['logo_url'] }}" alt="">@endif{{ $brand['name'] }}</a>
        <div class="d-flex gap-2 align-items-center">
            <a class="btn btn-sm btn-link text-decoration-none d-none d-md-inline" href="{{ route('business') }}">For business</a>
            <a class="btn btn-sm btn-link text-decoration-none d-none d-sm-inline" href="{{ route('track.lookup') }}">Track</a>
            @auth
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('account.dashboard') }}">My account</a>
            @else
                <a class="btn btn-sm btn-link text-decoration-none" href="{{ route('login') }}">Sign in</a>
                <a class="btn btn-sm btn-brand" href="{{ route('register', ['as' => 'customer']) }}">Sign up</a>
            @endauth
        </div>
    </div>
</nav>
@endunless
@yield('content')
@unless($embed ?? false)
@php
    $wa = preg_replace('/\D/', '', (string) config('booking.whatsapp'));
    $cPhone = config('booking.contact_phone');
    $cEmail = config('booking.contact_email');
@endphp
@if($cPhone || $cEmail || $wa)
<section class="container mt-4">
    <div class="pub-card d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><div class="fw-bold">Questions? Talk to a person.</div><div class="small text-muted">We're happy to help with quotes, business accounts and anything about your delivery.</div></div>
        <div class="d-flex flex-wrap gap-2">
            @if($wa)<a class="btn btn-success" href="https://wa.me/{{ $wa }}?text={{ rawurlencode((string) config('booking.whatsapp_message')) }}" target="_blank" rel="noopener">WhatsApp us</a>@endif
            @if($cPhone)<a class="btn btn-outline-dark" href="tel:{{ preg_replace('/[^\d+]/', '', (string) \App\Support\Phone::international((string) $cPhone) ?: (string) $cPhone) }}">Call {{ \App\Support\Phone::display((string) $cPhone) }}</a>@endif
            @if($cEmail)<a class="btn btn-outline-dark" href="mailto:{{ $cEmail }}">Email us</a>@endif
        </div>
    </div>
</section>
@endif
@if($wa)
<a class="wa-float" href="https://wa.me/{{ $wa }}?text={{ rawurlencode((string) config('booking.whatsapp_message')) }}" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
    <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3C9 3 3.3 8.6 3.3 15.6c0 2.5.7 4.9 2 7L3 29l6.6-2.2c2 1.1 4.2 1.7 6.4 1.7 7 0 12.7-5.6 12.7-12.6S23 3 16 3zm0 23.1c-2 0-4-.6-5.7-1.6l-.4-.2-3.9 1.3 1.3-3.8-.3-.4c-1.1-1.8-1.7-3.8-1.7-5.8C5.3 9.8 10.1 5 16 5s10.7 4.8 10.7 10.6S21.9 26.1 16 26.1zm5.9-7.9c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.3-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.4.3-.6.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.7s1.2 3.2 1.4 3.4c.2.2 2.4 3.6 5.7 5 .8.3 1.4.5 1.9.7.8.2 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4z"/></svg>
</a>
@endif
<footer class="pub container py-4 mt-4 border-top d-flex flex-wrap justify-content-between gap-2">
    <span>&copy; {{ date('Y') }} {{ $brand['name'] }}</span>
    <span class="d-flex gap-3"><a href="{{ route('track.lookup') }}">Track a delivery</a><a href="{{ route('partners') }}">Become a drop-off partner</a><a href="{{ route('business') }}">Business plans</a><a href="{{ route('drive') }}">Drive with us</a></span>
</footer>
@endunless
</body>
</html>
