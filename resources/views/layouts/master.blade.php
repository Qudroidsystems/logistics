<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <title>{{ $pagetitle }} | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="city commerce, shopping and logistics platform" name="description">
    <meta content="" name="author">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    @php $faviconUrl = asset('theme/layouts/assets/images/favicon.ico'); @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/png" href="{{ $faviconUrl }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link id="fontsLink" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/bold/style.css">
    <link href="{{ asset('theme/layouts/assets/fonts/materialdesignicons-webfont.woff2') }}?v=6.5.95" rel="stylesheet" type="font/woff2">

    <!-- Layout CSS — load BEFORE layout.js -->
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/custom.min.css') }}" rel="stylesheet">

    <!-- layout.js must run AFTER the CSS above -->
    <script src="{{ asset('theme/layouts/assets/js/layout.js') }}"></script>

    <!-- NProgress -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css"/>
    <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js"></script>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- jQuery (needed by Select2 and some page scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* =====================================================
           NPROGRESS
           ===================================================== */
        #nprogress .bar  { background: #4f8ef7 !important; height: 3px !important; box-shadow: 0 0 8px rgba(79,142,247,.6) !important; }
        #nprogress .peg  { box-shadow: none !important; }
        #nprogress .spinner { display: none !important; }

        /* =====================================================
           PAGINATION
           ===================================================== */
        .pagination-wrap .page-item { margin: 0 5px; }
        .pagination-wrap .page-link { padding: 5px 10px; }
        .pagination-wrap .active .page-link { background-color: #007bff; color: white; }
        .pagination-wrap .disabled .page-link { pointer-events: none; opacity: 0.5; }

        /* =====================================================
           SPINNER
           ===================================================== */
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }

        /* =====================================================
           MISC
           ===================================================== */
        .form-check-input:checked { background-color: #405189; border-color: #405189; }
        .swal2-toast { font-size: 14px !important; }
        .swal2-container.swal2-top-end { top: 70px !important; }
        .table tbody tr { transition: background-color .15s ease; }
        .table tbody tr:hover { background-color: rgba(67,97,238,.05); }
        .modal.fade  .modal-dialog { transform: translate(0,-50px); transition: transform .3s ease-out; }
        .modal.show  .modal-dialog { transform: translate(0,0); }

        /* =====================================================
           SIDEBAR LAYOUT — flex column so footer pins to bottom
           ===================================================== */
        .app-menu {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 250px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        #scrollbar {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            padding-bottom: 8px;
        }
        #scrollbar::-webkit-scrollbar { width: 4px; }
        #scrollbar::-webkit-scrollbar-track  { background: rgba(255,255,255,.05); border-radius: 4px; }
        #scrollbar::-webkit-scrollbar-thumb  { background: rgba(255,255,255,.2);  border-radius: 4px; }
        #scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.3); }
        .bg-light::-webkit-scrollbar { width: 6px; }
        .bg-light::-webkit-scrollbar-track  { background: #f1f1f1; border-radius: 10px; }
        .bg-light::-webkit-scrollbar-thumb  { background: #888; border-radius: 10px; }
        .bg-light::-webkit-scrollbar-thumb:hover { background: #555; }
        .navbar-menu .container-fluid { padding: 0; }
        #navbar-nav { padding-bottom: 8px; }

        /* =====================================================
           SIDEBAR LOGOUT FOOTER — pushed down with margin-top auto
           ===================================================== */
        .sidebar-footer {
            flex-shrink: 0;
            border-top: 1px solid rgba(255,255,255,.1);
            padding: 20px 16px 24px;
            margin-top: auto;
            background: inherit;
        }
        .sidebar-footer-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .sidebar-footer-user img {
            width: 36px; height: 36px;
            border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(255,255,255,.18);
            flex-shrink: 0;
        }
        .sidebar-footer-avatar-initials {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #405189;
            color: #fff;
            font-size: 13px; font-weight: 600;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,.18);
        }
        .sidebar-footer-user-info { min-width: 0; flex: 1; }
        .sidebar-footer-user-name {
            font-size: 13px; font-weight: 600; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-footer-user-role {
            font-size: 11px; color: rgba(255,255,255,.45);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-logout-btn {
            display: flex; align-items: center; gap: 9px;
            width: 100%; padding: 9px 14px;
            border-radius: 8px;
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.22);
            color: #f87171; font-size: 13px; font-weight: 500;
            cursor: pointer; text-decoration: none;
            transition: background .2s, border-color .2s, color .2s;
        }
        .sidebar-logout-btn:hover {
            background: rgba(239,68,68,.24);
            border-color: rgba(239,68,68,.45);
            color: #fca5a5;
        }
        .sidebar-logout-btn i { font-size: 17px; flex-shrink: 0; }

        /* =====================================================
           SIDEBAR NAV ACTIVE STATES
           ===================================================== */
        #navbar-nav .menu-dropdown { overflow: hidden; }
        #navbar-nav .nav-link.menu-link .ri-arrow-down-s-line { transition: transform .25s ease; display: inline-block; }
        #navbar-nav .nav-link.menu-link[aria-expanded="true"] .ri-arrow-down-s-line { transform: rotate(180deg); }

        #navbar-nav .nav-link.menu-link.nav-active-parent {
            color: #fff !important;
            background: rgba(79,142,247,.18) !important;
            border-left: 3px solid #4f8ef7;
            padding-left: calc(1.3rem - 3px);
        }
        #navbar-nav .nav-link.menu-link.nav-active-parent i { color: #4f8ef7 !important; }

        #navbar-nav .nav-sm .nav-link.nav-active-child { color: #7eb8fb !important; font-weight: 500; }
        #navbar-nav .nav-sm .nav-link.nav-active-child::before {
            content: ''; display: inline-block;
            width: 5px; height: 5px; border-radius: 50%;
            background: #4f8ef7; margin-right: 8px;
            box-shadow: 0 0 0 3px rgba(79,142,247,.25);
            vertical-align: middle; flex-shrink: 0;
            animation: dotPop .25s ease;
        }
        @keyframes dotPop { from{transform:scale(0);opacity:0} to{transform:scale(1);opacity:1} }

        /* =====================================================
           SIDEBAR HOVER TRANSITIONS + RIPPLE
           ===================================================== */
        #navbar-nav .nav-link { position: relative; overflow: hidden; transition: color .18s, background-color .18s, padding-left .18s; }
        .nav-ripple {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,.18);
            transform: scale(0); animation: ripple-anim .55s linear;
            pointer-events: none; z-index: 0;
        }
        @keyframes ripple-anim { to{transform:scale(5);opacity:0} }

        /* =====================================================
           BACK TO TOP
           ===================================================== */
        #back-to-top { opacity:0; visibility:hidden; transform:translateY(12px); transition:opacity .3s,transform .3s,visibility .3s; }
        #back-to-top.show { opacity:1; visibility:visible; transform:translateY(0); }
        #back-to-top:hover { transform:translateY(-3px) !important; }

        /* =====================================================
           PAGE FADE-IN
           ===================================================== */
        .page-content { animation: pageFadeIn .35s ease; }
        @keyframes pageFadeIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

        /* =====================================================
           TOPBAR
           ===================================================== */
        #page-topbar .header-item { transition: color .2s ease, background-color .2s ease; }
        .header-profile-user-enhanced { transition: transform .25s ease, box-shadow .25s ease; }
        .header-profile-user-enhanced:hover { transform: scale(1.07); box-shadow: 0 0 0 3px rgba(79,142,247,.35) !important; }

        .topbar-user .dropdown-menu {
            min-width: 220px;
            z-index: 9999 !important;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 8px !important;
            box-shadow: 0 8px 32px rgba(0,0,0,.18);
        }

        /* =====================================================
           MOBILE SIDEBAR
           ===================================================== */
        .vertical-overlay {
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(0,0,0,.45);
            display: none;
        }
        body.vertical-sidebar-enable .vertical-overlay { display: block; }

        @media (max-width: 1024.98px) {
            .app-menu {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: none;
            }
            body.vertical-sidebar-enable .app-menu {
                transform: translateX(0);
                box-shadow: 4px 0 24px rgba(0,0,0,.35);
            }
        }
        @media (min-width: 1025px) {
            body.vertical-sidebar-enable .app-menu { transform: none; }
        }

        /* =====================================================
           FINANCE MODULE
           ===================================================== */
        .finance-stat-card { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); border-radius: 12px; padding: 20px; color: white; transition: transform .3s,box-shadow .3s; }
        .finance-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(102,126,234,.35); }
        .payment-progress { height: 8px; border-radius: 4px; background: #e2e8f0; }
        .payment-progress-bar { height: 100%; border-radius: 4px; transition: width .4s ease; }
        .scholarship-card { border-left: 4px solid #10b981; transition: transform .2s,box-shadow .2s; }
        .scholarship-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .payroll-table th { background: #1e293b; color: white; }

        /* =====================================================
           CARD / BUTTON MICRO-INTERACTIONS
           ===================================================== */
        .card { transition: box-shadow .25s, transform .25s; }
        .card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        .btn  { transition: transform .15s, box-shadow .15s; }
        .btn:active { transform: scale(.97); }

        /* =====================================================
           SIDEBAR STAGGER
           ===================================================== */
        #navbar-nav > li { animation: navItemFadeIn .4s ease both; }
        #navbar-nav > li:nth-child(1)  { animation-delay:.02s }
        #navbar-nav > li:nth-child(2)  { animation-delay:.04s }
        #navbar-nav > li:nth-child(3)  { animation-delay:.06s }
        #navbar-nav > li:nth-child(4)  { animation-delay:.08s }
        #navbar-nav > li:nth-child(5)  { animation-delay:.10s }
        #navbar-nav > li:nth-child(6)  { animation-delay:.12s }
        #navbar-nav > li:nth-child(7)  { animation-delay:.14s }
        #navbar-nav > li:nth-child(8)  { animation-delay:.16s }
        #navbar-nav > li:nth-child(9)  { animation-delay:.18s }
        #navbar-nav > li:nth-child(10) { animation-delay:.20s }
        #navbar-nav > li:nth-child(11) { animation-delay:.22s }
        #navbar-nav > li:nth-child(12) { animation-delay:.24s }
        #navbar-nav > li:nth-child(n+13) { animation-delay:.26s }
        @keyframes navItemFadeIn { from{opacity:0;transform:translateX(-8px)} to{opacity:1;transform:translateX(0)} }

        /* =====================================================
           DROPDOWN ANIMATIONS
           ===================================================== */
        .dropdown-menu { animation: dropdownFadeIn .25s cubic-bezier(.4,0,.2,1); transform-origin: top right; }
        @keyframes dropdownFadeIn { from{opacity:0;transform:translateY(-10px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }

        /* =====================================================
           PRINT
           ===================================================== */
        @media print { .no-print{display:none!important} body{padding:0;margin:0} }

        /* =====================================================
           SPOTLIGHT SEARCH — APPLE STYLE DARK
           ===================================================== */
        @keyframes spotlightOverlayFadeIn  { from{background:rgba(0,0,0,.2);backdrop-filter:blur(0)} to{background:rgba(0,0,0,.85);backdrop-filter:blur(20px)} }
        @keyframes spotlightOverlayFadeOut { from{background:rgba(0,0,0,.85);backdrop-filter:blur(20px)} to{background:rgba(0,0,0,.2);backdrop-filter:blur(0)} }
        @keyframes spotlightModalBounceIn  { 0%{opacity:0;transform:translateY(-40px) scale(.96)} 40%{opacity:.9;transform:translateY(8px) scale(1.01)} 70%{opacity:.95;transform:translateY(-2px) scale(.99)} 100%{opacity:1;transform:translateY(0) scale(1)} }
        @keyframes spotlightModalFadeOut   { 0%{opacity:1;transform:translateY(0) scale(1)} 100%{opacity:0;transform:translateY(-20px) scale(.95)} }
        @keyframes resultBounceIn { 0%{opacity:0;transform:translateX(-16px) scale(.96)} 60%{opacity:.8;transform:translateX(4px) scale(1.01)} 100%{opacity:1;transform:translateX(0) scale(1)} }
        @keyframes resultGlowPulse { 0%{box-shadow:0 0 0 0 rgba(79,142,247,.5)} 70%{box-shadow:0 0 0 8px rgba(79,142,247,0)} 100%{box-shadow:0 0 0 0 rgba(79,142,247,0)} }
        @keyframes loadingSpin    { 0%{transform:rotate(0)} 100%{transform:rotate(360deg)} }
        @keyframes historySlideIn { from{opacity:0;transform:translateX(-12px)} to{opacity:1;transform:translateX(0)} }
        @keyframes typingDot      { 0%,60%,100%{transform:translateY(0);opacity:.4} 30%{transform:translateY(-5px);opacity:1} }
        @keyframes suggestFadeIn  { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:translateY(0)} }

        .spotlight-result-item { animation:resultBounceIn .35s cubic-bezier(.34,1.3,.64,1) forwards; opacity:0; }
        .spotlight-result-item:nth-child(1){animation-delay:.00s}
        .spotlight-result-item:nth-child(2){animation-delay:.03s}
        .spotlight-result-item:nth-child(3){animation-delay:.06s}
        .spotlight-result-item.top-match { animation:resultBounceIn .4s cubic-bezier(.34,1.3,.64,1) forwards,resultGlowPulse .6s ease .3s; border-left:3px solid #4f8ef7; background:linear-gradient(90deg,rgba(79,142,247,.12) 0%,transparent 100%); }
        .spotlight-history-item { animation:historySlideIn .25s ease forwards; opacity:0; animation-fill-mode:forwards; }
        .spotlight-history-item:nth-child(1){animation-delay:.00s}
        .spotlight-history-item:nth-child(2){animation-delay:.04s}
        .spotlight-history-item:nth-child(3){animation-delay:.08s}
        .spotlight-suggest-chip { animation:suggestFadeIn .2s ease forwards; opacity:0; }
        .spotlight-suggest-chip:nth-child(1){animation-delay:.00s}
        .spotlight-suggest-chip:nth-child(2){animation-delay:.04s}
        .spotlight-suggest-chip:nth-child(3){animation-delay:.08s}
        .spotlight-suggest-chip:nth-child(4){animation-delay:.12s}
        .spotlight-suggest-chip:nth-child(5){animation-delay:.16s}
        .typing-dot { display:inline-block; animation:typingDot 1.4s infinite ease-in-out; }
        .typing-dot:nth-child(2){animation-delay:.2s}
        .typing-dot:nth-child(3){animation-delay:.4s}

        .search-tooltip { position:absolute; bottom:-38px; left:0; background:#1c1c1e; color:#fff; font-size:12px; padding:6px 12px; border-radius:10px; white-space:nowrap; opacity:0; transition:opacity .2s; pointer-events:none; z-index:100; backdrop-filter:blur(8px); border:0.5px solid rgba(255,255,255,0.1); font-weight:500; }
        .search-tooltip kbd { background:rgba(255,255,255,0.15); color:#fff; padding:2px 8px; border-radius:6px; font-size:11px; margin:0 2px; }
        kbd { background:rgba(0,0,0,0.08); border-radius:6px; padding:2px 8px; font-size:11px; font-family:monospace; }

        /* Spotlight trigger */
        #spotlight-trigger { background:rgba(0,0,0,0.6) !important; border:1px solid rgba(255,255,255,0.15) !important; border-radius:12px !important; padding:8px 16px !important; min-width:260px !important; backdrop-filter:blur(8px) !important; }
        #spotlight-trigger span { color:#ffffff !important; opacity:0.9 !important; font-weight:500 !important; }
        #spotlight-trigger kbd { background:rgba(255,255,255,0.2) !important; color:#ffffff !important; border:none !important; }

        /* Ensure modals don't conflict */
        .modal { z-index: 1055 !important; }
        .modal-backdrop { z-index: 1050 !important; }
    </style>

    <!-- Route-specific CSS includes -->
    @if (Route::is('dashboard'))              @include('layouts.pages-assets.css.users-list-css') @endif
    @if (Route::is('users.*'))                @include('layouts.pages-assets.css.users-list-css') @endif
    @if (Route::is('profile.*'))              @include('layouts.pages-assets.css.users-list-css') @endif
    @if (Route::is('roles.*'))                @include('layouts.pages-assets.css.roles-list-css') @endif
    @if (Route::is('permissions.*'))          @include('layouts.pages-assets.css.permission-list-css') @endif
    @if (Route::is('admin.scholarship.*') || Route::is('admin.discount.*') || Route::is('sibling.*') ||
        Route::is('payment.*') || Route::is('reports.financial.*') || Route::is('reports.analysis.*') ||
        Route::is('payroll.*') || Route::is('staff.payments.*'))
    @endif

    {{-- CB UI design system (hero / stat / card look used across the portal).
         Loaded LAST so page-specific CSS partials above can't override it.
         Remove class="cb-ui" from <body> to switch the global skin off. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/cb-ui.css') }}?v={{ @filemtime(public_path('css/cb-ui.css')) }}" rel="stylesheet">
</head>

<body class="cb-ui">
<div id="layout-wrapper">

    <!-- ========== SIDEBAR ========== -->
    @include('layouts.partials.sidebar')

    <div class="vertical-overlay" id="vertical-overlay"></div>

    <!-- ========== TOPBAR ========== -->
    <header id="page-topbar">
        <div class="layout-width">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box horizontal-logo">
                        <a href="index.html" class="logo logo-dark">
                            <span class="logo-sm"><img src="{{ asset('theme/layouts/assets/images/logo-sm.png')}}" alt="" height="22"></span>
                            <span class="logo-lg"><img src="{{ asset('theme/layouts/assets/images/logo-dark.png')}}" alt="" height="22"></span>
                        </a>
                        <a href="index.html" class="logo logo-light">
                            <span class="logo-sm"><img src="{{ asset('theme/layouts/assets/images/logo-sm.png')}}" alt="" height="22"></span>
                            <span class="logo-lg"><img src="{{ asset('theme/layouts/assets/images/logo-light.png')}}" alt="" height="22"></span>
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger shadow-none" id="topnav-hamburger-icon">
                        <span class="hamburger-icon"><span></span><span></span><span></span></span>
                    </button>
                    <div class="d-none d-md-inline-flex align-items-center" style="position:relative;">
                        <button type="button" id="spotlight-trigger" style="display:flex;align-items:center;gap:8px;border-radius:10px;padding:7px 14px;cursor:pointer;transition:all .2s;min-width:220px;">
                            <i class="mdi mdi-magnify" style="font-size:16px;opacity:.8;"></i>
                            <span style="font-size:13px;flex:1;text-align:left;">Search everything…</span>
                            <div style="display:flex;gap:4px;"><kbd style="font-size:10px;padding:2px 6px;border-radius:4px;">⌘</kbd><kbd style="font-size:10px;padding:2px 6px;border-radius:4px;">K</kbd></div>
                        </button>
                        <div class="search-tooltip">Press <kbd>⌘K</kbd> or <kbd>Ctrl+K</kbd> to search</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-1">
                    <!-- Notifications bell -->
                    @include('layouts.partials.online-badge')
                    @include('layouts.partials.notification-bell')

                    <!-- Theme Toggle -->
                    <div class="position-relative" id="theme-toggle-wrapper">
                        <button type="button" id="theme-toggle-btn" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle" style="width:38px;height:38px;">
                            <i id="theme-icon" class="bi bi-sun align-middle fs-3xl"></i>
                        </button>
                        <div id="theme-dropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;min-width:170px;background:var(--vz-dropdown-bg,#fff);border:1px solid var(--vz-border-color,#e9ebec);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:9999;overflow:hidden;padding:6px;">
                            <a href="javascript:void(0)" class="theme-mode-item d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none" data-mode="light"><i class="bi bi-sun"></i> Light</a>
                            <a href="javascript:void(0)" class="theme-mode-item d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none" data-mode="dark"><i class="bi bi-moon"></i> Dark</a>
                            <a href="javascript:void(0)" class="theme-mode-item d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none" data-mode="auto"><i class="bi bi-moon-stars"></i> Auto</a>
                        </div>
                    </div>

                    <!-- ===== USER DROPDOWN ===== -->
                    @php
                        use App\Models\User as UserModel;
                        use Illuminate\Support\Facades\Storage;
                        use Illuminate\Support\Facades\Auth;

                        $userdata  = Auth::user();
                        $fullName  = $userdata->name ?? 'User';
                        $initials  = collect(explode(' ', $fullName))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');
                        $srcPath   = null;

                        if ($userdata->avatar) {
                            $basename = basename($userdata->avatar);
                            if (Storage::disk('public')->exists('avatars/' . $basename))
                                $srcPath = asset('storage/avatars/' . $basename);
                        }

                        $userRoles = $userdata->roles->pluck('name');
                    @endphp

                    <div class="dropdown position-relative ms-sm-3 header-item topbar-user" id="user-dropdown-wrapper">
                        <button type="button" id="user-menu-btn" class="btn shadow-none p-0" style="background:transparent;border:none;">
                            <span class="d-flex align-items-center gap-2">
                                <span style="display:inline-block;width:42px;height:42px;flex-shrink:0;position:relative;">
                                    @if($srcPath)
                                        <img id="topbar-avatar-img" src="{{ $srcPath }}" alt="{{ $fullName }}" style="width:42px;height:42px;border-radius:10px;object-fit:cover;" onerror="this.style.display='none';document.getElementById('topbar-avatar-fallback').style.display='flex';">
                                        <span id="topbar-avatar-fallback" style="display:none;width:42px;height:42px;border-radius:10px;background:#405189;color:#fff;align-items:center;justify-content:center;">{{ $initials }}</span>
                                    @else
                                        <span style="display:flex;width:42px;height:42px;border-radius:10px;background:#405189;color:#fff;align-items:center;justify-content:center;">{{ $initials }}</span>
                                    @endif
                                </span>
                                <span class="d-none d-xl-flex flex-column align-items-start ms-1"><span class="fw-medium" style="font-size:13px;">{{ $userdata->name }}</span></span>
                            </span>
                        </button>

                        <div id="user-dropdown" class="dropdown-menu dropdown-menu-end" style="display:none;position:absolute;top:calc(100% + 8px);right:0;min-width:220px;background:var(--vz-dropdown-bg,#fff);border-radius:12px;z-index:9999;">
                            <div class="dropdown-header"><h6 class="mb-0">Welcome back!</h6><small class="text-muted">{{ $userdata->name }}</small></div>
                            <div class="dropdown-divider"></div>
                            <div class="px-3 py-2">
                                <div class="small text-muted mb-2 text-uppercase" style="font-size:10px;">Your Roles</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($userRoles as $roleName)
                                        @php
                                            $roleColors = [
                                                'super admin' => ['bg'=>'#405189','light'=>'#eef2ff'],
                                                'dispatcher'  => ['bg'=>'#0a9396','light'=>'#e0f2fe'],
                                                'finance manager' => ['bg'=>'#2a9d8f','light'=>'#e6f7f5'],
                                                'support agent'   => ['bg'=>'#e76f51','light'=>'#fff0ed'],
                                            ];
                                            $rk    = strtolower($roleName);
                                            $color = $roleColors[$rk]['bg']    ?? '#6c757d';
                                            $bg    = $roleColors[$rk]['light'] ?? '#f8fafc';
                                        @endphp
                                        <span style="display:inline-block;font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px;background:{{ $bg }};color:{{ $color }};">{{ $roleName }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('users.overview', $userdata->id) }}"><i class="mdi mdi-account-circle me-2"></i>My Profile</a>
                            <a class="dropdown-item" href="{{ route('profile.settings', ['id' => $userdata->id]) }}"><i class="mdi mdi-cog me-2"></i>Account Settings</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}" id="topbar-logout-form">@csrf<a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('topbar-logout-form').submit();"><i class="mdi mdi-logout me-2"></i>Logout</a></form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title">Profile Photo</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="enlargedImage" src="" alt="Profile" class="img-fluid rounded-3" style="max-height:400px;">
                </div>
            </div>
        </div>
    </div>

    @include('layouts.partials.maintenance-banner')

    @yield('content')

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    {{ now()->year }} © {{ config('app.name') }}
                    <span class="text-muted">· Powered by <a href="https://qudroids.com" target="_blank" rel="noopener noreferrer" class="fw-semibold">Qudroid Systems</a>
                    </span>
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end">
                        Created by
                        <a href="https://qudroids.com" target="_blank" rel="noopener noreferrer" class="fw-semibold">Qudroid Systems</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>

<button class="btn btn-dark btn-icon" id="back-to-top"><i class="bi bi-caret-up fs-3xl"></i></button>
<div id="preloader"><div id="status"><div class="spinner-border text-primary avatar-sm" role="status"><span class="visually-hidden">Loading...</span></div></div></div>

<!-- Customizer trigger -->
<div class="customizer-setting d-none d-md-block">
    <div class="btn btn-info p-2 text-uppercase rounded-end-0 shadow-lg"
         data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas"
         aria-controls="theme-settings-offcanvas">
        <i class="bi bi-gear mb-1"></i> Customizer
    </div>
</div>

<!-- Theme Settings Offcanvas -->
<div class="offcanvas offcanvas-end border-0" tabindex="-1" id="theme-settings-offcanvas">
    <div class="d-flex align-items-center bg-primary bg-gradient p-3 offcanvas-header">
        <div class="me-2">
            <h5 class="mb-1 text-white">Theme Customizer</h5>
            <p class="text-white text-opacity-75 mb-0">Customize your experience</p>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" id="customizerclose-btn"
                data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div data-simplebar class="h-100">
            <div class="p-4">
                <h6 class="fs-md mb-1">Color Scheme</h6>
                <p class="text-muted fs-sm">Choose Light or Dark Scheme.</p>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="form-check card-radio">
                            <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-mode-light" value="light">
                            <label class="form-check-label p-0 bg-transparent" for="layout-mode-light">
                                <img src="{{ asset('theme/layouts/assets/images/custom-theme/light-mode.png') }}" alt="" class="img-fluid">
                            </label>
                        </div>
                        <h5 class="fs-sm text-center fw-medium mt-2">Light</h5>
                    </div>
                    <div class="col-6">
                        <div class="form-check card-radio dark">
                            <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-mode-dark" value="dark">
                            <label class="form-check-label p-0 bg-transparent" for="layout-mode-dark">
                                <img src="{{ asset('theme/layouts/assets/images/custom-theme/dark-mode.png') }}" alt="" class="img-fluid">
                            </label>
                        </div>
                        <h5 class="fs-sm text-center fw-medium mt-2">Dark</h5>
                    </div>
                </div>
                <div id="sidebar-color">
                    <h6 class="mt-4 fs-md mb-1">Sidebar Color</h6>
                    <p class="text-muted fs-sm">Choose a color of Sidebar.</p>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidebar" id="sidebar-color-light" value="light">
                                <label class="form-check-label p-0 avatar-md w-100" for="sidebar-color-light">
                                    <span class="d-flex gap-1 h-100"><span class="flex-shrink-0"><span class="bg-white border-end d-flex h-100 flex-column gap-1 p-1"><span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span><span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span></span></span><span class="flex-grow-1"><span class="d-flex h-100 flex-column"><span class="bg-light d-block p-1"></span></span></span></span>
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Light</h5>
                        </div>
                        <div class="col-4">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidebar" id="sidebar-color-dark" value="dark">
                                <label class="form-check-label p-0 avatar-md w-100" for="sidebar-color-dark">
                                    <span class="d-flex gap-1 h-100"><span class="flex-shrink-0"><span class="bg-primary d-flex h-100 flex-column gap-1 p-1"><span class="d-block p-1 px-2 bg-soft-light rounded mb-2"></span><span class="d-block p-1 px-2 pb-0 bg-soft-light"></span></span></span><span class="flex-grow-1"><span class="d-flex h-100 flex-column"><span class="bg-light d-block p-1"></span></span></span></span>
                                </label>
                            </div>
                            <h5 class="fs-sm text-center fw-medium mt-2">Dark</h5>
                        </div>
                    </div>
                </div>
                <div style="display:none;">
                    <input type="radio" id="topbar-color-light" name="data-topbar" value="light">
                    <input type="radio" id="topbar-color-dark"  name="data-topbar" value="dark">
                </div>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer border-top p-3 text-center">
        <div class="row">
            <div class="col-6">
                <button type="button" class="btn btn-light w-100" id="reset-layout">Reset</button>
            </div>
        </div>
    </div>
</div>

<!-- ========== SPOTLIGHT MODAL ========== -->
<div id="spotlight-overlay" style="display:none;position:fixed;inset:0;z-index:1060;align-items:flex-start;justify-content:center;padding-top:12vh;">
    <div id="spotlight-box" style="width:100%;max-width:720px;margin:0 24px;background:rgba(28,28,30,0.98);border:1px solid rgba(255,255,255,0.12);border-radius:32px;box-shadow:0 32px 80px rgba(0,0,0,0.6),0 0 0 0.5px rgba(255,255,255,0.08);overflow:hidden;backdrop-filter:blur(20px);">
        <div style="display:flex;align-items:center;gap:16px;padding:20px 24px;border-bottom:0.5px solid rgba(255,255,255,0.1);">
            <i class="mdi mdi-magnify" style="font-size:28px;color:#4f8ef7;flex-shrink:0;"></i>
            <input id="spotlight-input" type="text" placeholder="Search everything..." autocomplete="off"
                   style="flex:1;background:transparent;border:none;outline:none;font-size:1.5rem;font-weight:500;color:#fff;caret-color:#4f8ef7;padding:8px 0;">
            <div style="display:flex;gap:8px;">
                <button id="spotlight-clear-history" style="display:none;background:rgba(255,255,255,0.08);border:none;border-radius:10px;padding:6px 12px;color:rgba(255,255,255,0.6);font-size:12px;font-weight:500;cursor:pointer;">Clear History</button>
                <kbd id="spotlight-esc" style="font-size:13px;padding:5px 12px;border-radius:10px;background:rgba(255,255,255,0.08);border:0.5px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.6);cursor:pointer;">ESC</kbd>
            </div>
        </div>
        <div id="spotlight-results" style="max-height:540px;overflow-y:auto;padding:12px 0;">
            <!-- Suggestion chips shown while typing -->
            <div id="spotlight-suggestions" style="display:none;padding:10px 24px 0;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.35);margin-bottom:8px;">Suggestions</div>
                <div id="spotlight-suggestion-chips" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
                <div style="height:1px;background:rgba(255,255,255,.06);margin:14px -4px 0;"></div>
            </div>
            <!-- History section -->
            <div id="spotlight-history-section" style="display:none;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 24px 8px;">
                    <span style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.4);">Recent Searches</span>
                    <button id="spotlight-clear-history-btn" style="background:transparent;border:none;color:rgba(255,255,255,0.4);font-size:12px;cursor:pointer;">Clear All</button>
                </div>
                <div id="spotlight-history-list"></div>
                <div style="height:1px;background:rgba(255,255,255,0.06);margin:12px 20px;"></div>
            </div>
            <!-- Empty / default state -->
            <div id="spotlight-empty" style="padding:48px 24px;text-align:center;color:rgba(255,255,255,0.35);">
                <i class="mdi mdi-lightning-bolt" style="font-size:48px;display:block;margin-bottom:16px;opacity:0.4;"></i>
                <span style="font-size:15px;">Start typing to search…</span>
                <div style="margin-top:16px;font-size:12px;opacity:0.4;">Popular: Users, Roles, Permissions</div>
            </div>
            <ul id="spotlight-list" style="list-style:none;margin:0;padding:0;display:none;"></ul>
            <div id="spotlight-loading" style="display:none;padding:48px;text-align:center;">
                <div style="display:inline-block;width:32px;height:32px;border:2px solid rgba(255,255,255,0.15);border-top-color:#4f8ef7;border-radius:50%;animation:loadingSpin 0.7s linear infinite;"></div>
                <div style="margin-top:16px;font-size:13px;color:rgba(255,255,255,0.45);">Searching<span class="typing-dot">.</span><span class="typing-dot">.</span><span class="typing-dot">.</span></div>
            </div>
        </div>
        <div style="padding:14px 24px;border-top:0.5px solid rgba(255,255,255,0.07);display:flex;gap:24px;font-size:12px;color:rgba(255,255,255,0.35);flex-wrap:wrap;">
            <span><kbd style="background:rgba(255,255,255,0.1);border-radius:5px;padding:2px 8px;">⌘K</kbd> / <kbd style="background:rgba(255,255,255,0.1);border-radius:5px;padding:2px 8px;">Ctrl+K</kbd> open</span>
            <span><kbd style="background:rgba(255,255,255,0.1);border-radius:5px;padding:2px 8px;">↑↓</kbd> navigate</span>
            <span><kbd style="background:rgba(255,255,255,0.1);border-radius:5px;padding:2px 8px;">↵</kbd> open</span>
            <span><kbd style="background:rgba(255,255,255,0.1);border-radius:5px;padding:2px 8px;">ESC</kbd> close</span>
        </div>
    </div>
</div>

<!-- =====================================================
     SCRIPTS
     ===================================================== -->
<script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/js/app.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/js/plugins.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
(function () {
    'use strict';

    /* ── helpers ── */
    function qs(sel, ctx)  { return (ctx || document).querySelector(sel); }
    function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    /* ── manual dropdown factory ── */
    function makeDropdown(btnId, panelId) {
        var btn = document.getElementById(btnId), panel = document.getElementById(panelId);
        if (!btn || !panel) return;
        function open()  { panel.style.display = 'block'; btn.setAttribute('aria-expanded','true'); }
        function close() { panel.style.display = 'none';  btn.setAttribute('aria-expanded','false'); }
        btn.addEventListener('click', function(e){ e.stopPropagation(); panel.style.display === 'none' ? open() : close(); });
        document.addEventListener('click', function(e){ if (!btn.contains(e.target) && !panel.contains(e.target)) close(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
    }

    /* ── theme ── */
    function initTheme() {
        var html   = document.documentElement;
        var iconEl = document.getElementById('theme-icon');
        var ICON   = { light:'bi bi-sun align-middle fs-3xl', dark:'bi bi-moon align-middle fs-3xl', auto:'bi bi-moon-stars align-middle fs-3xl' };

        function applyMode(mode) {
            var scheme = mode === 'auto' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : mode;
            html.setAttribute('data-bs-theme', scheme);
            html.setAttribute('data-topbar', scheme === 'dark' ? 'dark' : 'light');
            if (iconEl) iconEl.className = ICON[mode] || ICON.light;
            localStorage.setItem('app-theme', mode);
            var r = document.getElementById(scheme === 'dark' ? 'layout-mode-dark' : 'layout-mode-light');
            if (r) r.checked = true;
            qsa('.theme-mode-item').forEach(function(a){
                a.style.fontWeight = a.getAttribute('data-mode') === mode ? '600' : '';
                a.style.color      = a.getAttribute('data-mode') === mode ? 'var(--vz-primary,#405189)' : '';
            });
        }
        applyMode(localStorage.getItem('app-theme') || 'light');
        qsa('.theme-mode-item').forEach(function(a){
            a.addEventListener('click', function(e){ e.preventDefault(); applyMode(a.getAttribute('data-mode')); document.getElementById('theme-dropdown').style.display='none'; });
        });
        qsa('[name="data-bs-theme"]').forEach(function(r){ r.addEventListener('change', function(){ applyMode(r.value); }); });
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(){
            if (localStorage.getItem('app-theme') === 'auto') applyMode('auto');
        });
    }

    /* ── NProgress ── */
    function initNProgress() {
        if (typeof NProgress === 'undefined') return;
        NProgress.configure({ showSpinner: false, speed: 400, minimum: 0.1 });
        qsa('a[href]').forEach(function(a){
            var h = a.getAttribute('href') || '';
            if (h && h !== '#' && !h.startsWith('javascript') && !h.startsWith('mailto') && !h.startsWith('tel')
                && !a.hasAttribute('data-bs-toggle') && !a.hasAttribute('data-bs-dismiss') && a.getAttribute('target') !== '_blank') {
                a.addEventListener('click', function(){ NProgress.start(); });
            }
        });
        window.addEventListener('pageshow', function(){ NProgress.done(); });
        window.addEventListener('load',     function(){ NProgress.done(); });
    }

    /* ── active sidebar ── */
    function initActiveSidebar() {
        var cur = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
        var best = null, bestLen = -1;
        // Consider every real sidebar link (top-level direct links AND submenu children).
        qsa('#navbar-nav a.nav-link[href]').forEach(function(link){
            var href = link.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript') === 0) return;
            var lp;
            try { lp = (new URL(link.href, window.location.origin).pathname || '/').replace(/\/+$/, '') || '/'; }
            catch(e){ return; }
            var match = (lp === cur) || (lp.length > 1 && cur.indexOf(lp + '/') === 0);
            if (match && lp.length > bestLen) { best = link; bestLen = lp.length; }
        });
        if (!best) return;
        best.classList.add('active');
        if (best.closest('.nav-sm')) best.classList.add('nav-active-child');
        else best.classList.add('nav-active-parent');
        var col = best.closest('.collapse');
        if (col) {
            col.classList.add('show');
            var tog = qs('[data-bs-target="#'+col.id+'"],[href="#'+col.id+'"]');
            if (tog) {
                tog.setAttribute('aria-expanded','true');
                tog.classList.remove('collapsed');
                tog.classList.add('nav-active-parent','active');
            }
        }
        setTimeout(function(){ try { best.scrollIntoView({behavior:'smooth',block:'nearest'}); } catch(e){} }, 350);
    }

    /* ── ripple ── */
    function initRipple() {
        qsa('#navbar-nav .nav-link').forEach(function(link){
            link.addEventListener('click', function(e){
                if (link.hasAttribute('data-bs-toggle')) return;
                var r = document.createElement('span');
                var rect = link.getBoundingClientRect();
                var s = Math.max(rect.width, rect.height);
                r.className = 'nav-ripple';
                r.style.cssText = 'width:'+s+'px;height:'+s+'px;left:'+(e.clientX-rect.left-s/2)+'px;top:'+(e.clientY-rect.top-s/2)+'px;';
                link.appendChild(r);
                setTimeout(function(){ r.parentNode && r.parentNode.removeChild(r); }, 650);
            });
        });
    }

    /* ── back to top ── */
    function initBackToTop() {
        var btn = document.getElementById('back-to-top');
        if (!btn) return;
        window.addEventListener('scroll', function(){ btn.classList.toggle('show', window.scrollY > 300); }, {passive:true});
        btn.addEventListener('click', function(){ window.scrollTo({top:0,behavior:'smooth'}); });
    }

    /* ── hamburger / mobile sidebar ── */
    function initHamburger() {
        var ham     = document.getElementById('topnav-hamburger-icon');
        var overlay = document.getElementById('vertical-overlay');
        var body    = document.body;

        function closeSidebar() { body.classList.remove('vertical-sidebar-enable'); }
        function openSidebar()  { body.classList.add('vertical-sidebar-enable'); }

        if (ham) {
            var freshHam = ham.cloneNode(true);
            ham.parentNode.replaceChild(freshHam, ham);
            document.getElementById('topnav-hamburger-icon').addEventListener('click', function(e){
                e.preventDefault(); e.stopPropagation();
                body.classList.contains('vertical-sidebar-enable') ? closeSidebar() : openSidebar();
            });
        }
        if (overlay) overlay.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape' && body.classList.contains('vertical-sidebar-enable')) closeSidebar();
        });
    }

    /* ── image modal ── */
    function initImageModal() {
        var modal = document.getElementById('imageViewModal');
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function(e){
            var img = document.getElementById('enlargedImage');
            var src = e.relatedTarget ? e.relatedTarget.getAttribute('data-image') : null;
            if (img && src) img.src = src;
        });
    }

    /* ── search tooltip ── */
    function initSearchTooltip() {
        var btn = document.getElementById('spotlight-trigger');
        var tip = qs('.search-tooltip');
        if (!btn || !tip) return;
        btn.addEventListener('mouseenter', function(){ tip.style.opacity='1'; });
        btn.addEventListener('mouseleave', function(){ tip.style.opacity='0'; });
    }

    /* ── reset layout ── */
    function initReset() {
        var btn = document.getElementById('reset-layout');
        if (btn) btn.addEventListener('click', function(){ sessionStorage.clear(); localStorage.removeItem('app-theme'); location.reload(); });
    }

    /* ── INIT ── */
    document.addEventListener('DOMContentLoaded', function(){
        initTheme();
        makeDropdown('theme-toggle-btn', 'theme-dropdown');
        makeDropdown('user-menu-btn',    'user-dropdown');
        initActiveSidebar();
        initRipple();
        initBackToTop();
        initHamburger();
        initImageModal();
        initSearchTooltip();
        initReset();
        initNProgress();
    });
})();
</script>

<!-- =====================================================
     SPOTLIGHT SEARCH — full registry with all routes
     ===================================================== -->
<script>
(function(){
    'use strict';

    /* ─────────────────────────────────────────────────────────────────
       COMPLETE PAGE REGISTRY — every navigable route in the system
       ───────────────────────────────────────────────────────────────── */
    var STATIC_PAGES = [
        /* ── Dashboards ── */
        {title:'Administration Dashboard',              url:'{{ route("dashboard") }}',                                      icon:'mdi-gauge',                  category:'Dashboards',          keywords:['home','analytics','overview','admin']},

        /* ── Users & Privileges ── */
        {title:'User Management',                       url:'{{ route("users.index") }}',                                    icon:'mdi-account-group',           category:'Users & Privileges',  keywords:['staff','accounts','login','users']},
        {title:'Roles',                                 url:'{{ route("roles.index") }}',                                    icon:'mdi-shield-account',          category:'Users & Privileges',  keywords:['permissions','access','roles']},
        {title:'Permissions',                           url:'{{ route("permissions.index") }}',                              icon:'mdi-lock',                    category:'Users & Privileges',  keywords:['access','rights','permissions']},

        /* ── My Account ── */
        {title:'My Profile',                            url:'{{ route("users.overview", ["id" => Auth::id()]) }}',           icon:'mdi-account-circle',          category:'My Account',          keywords:['profile','bio','account']},
        {title:'Account Settings',                      url:'{{ route("profile.settings", ["id" => Auth::id()]) }}',         icon:'mdi-cog',                     category:'My Account',          keywords:['settings','password','avatar']},

    ];

    /* ── category colours ── */
    var CAT_COLORS = {
        'Dashboards':'#4f8ef7','Users & Privileges':'#405189','My Account':'#2a9d8f','Platform':'#6a0572'
    };

    /* ── popular / suggestion chips ── */
    var POPULAR = [
        {label:'Users', q:'users'},
        {label:'Roles', q:'roles'},
        {label:'Permissions', q:'permissions'},
    ];

    /* ── history helpers ── */
    var HISTORY_KEY = 'spotlight_search_history';
    function getHistory(){ try{ return JSON.parse(localStorage.getItem(HISTORY_KEY)||'[]'); }catch(e){ return []; } }
    function saveHistory(h){ localStorage.setItem(HISTORY_KEY, JSON.stringify(h.slice(0,10))); }
    function addHistory(query, result){
        if (!query || query.trim().length < 2) return;
        var h = getHistory();
        var item = {query:query.trim(), url:result.url, title:result.title, icon:result.icon, category:result.category, ts:Date.now()};
        var idx = h.findIndex(function(x){ return x.url===result.url; });
        if (idx !== -1) h.splice(idx,1);
        h.unshift(item);
        saveHistory(h);
    }

    /* ── fuzzy / scored search ── */
    function scoreMatch(page, q) {
        var lq   = q.toLowerCase().trim();
        var lt   = page.title.toLowerCase();
        var lc   = page.category.toLowerCase();
        var lk   = (page.keywords||[]).join(' ').toLowerCase();
        var score = 0;
        if (lt === lq)                   score += 100;
        if (lt.startsWith(lq))           score += 60;
        if (lt.includes(lq))             score += 40;
        if (lc.includes(lq))             score += 20;
        if (lk.includes(lq))             score += 30;
        /* word-level partial: each word of query must match something */
        var words = lq.split(/\s+/).filter(Boolean);
        if (words.length > 1) {
            var allMatch = words.every(function(w){ return lt.includes(w)||lc.includes(w)||lk.includes(w); });
            if (allMatch) score += 35;
        }
        /* fuzzy: check if query chars appear in order in title */
        if (score === 0 && lq.length >= 2) {
            var ti = 0;
            for (var ci = 0; ci < lq.length; ci++) {
                var found = lt.indexOf(lq[ci], ti);
                if (found === -1) { ti = -1; break; }
                ti = found + 1;
            }
            if (ti !== -1) score += 10;
        }
        return score;
    }

    function searchStatic(q) {
        var scored = [];
        STATIC_PAGES.forEach(function(p){
            var s = scoreMatch(p, q);
            if (s > 0) scored.push({page:p, score:s});
        });
        scored.sort(function(a,b){ return b.score - a.score; });
        return scored.map(function(x){ return x.page; }).slice(0,18);
    }

    /* ── suggestion chips based on partial input ── */
    function getSuggestions(q) {
        if (!q || q.length < 1) return POPULAR;
        var lq = q.toLowerCase();
        var matchedCats = {};
        STATIC_PAGES.forEach(function(p){
            var lt = p.title.toLowerCase(), lk = (p.keywords||[]).join(' ').toLowerCase();
            if (lt.includes(lq) || lk.includes(lq) || p.category.toLowerCase().includes(lq)) {
                matchedCats[p.category] = (matchedCats[p.category]||0) + 1;
            }
        });
        var cats = Object.keys(matchedCats).sort(function(a,b){ return matchedCats[b]-matchedCats[a]; }).slice(0,6);
        if (cats.length === 0) return POPULAR.slice(0,5);
        return cats.map(function(c){ return {label:c, q:c}; });
    }

    /* ── DOM refs ── */
    var overlay   = document.getElementById('spotlight-overlay');
    var box       = document.getElementById('spotlight-box');
    var input     = document.getElementById('spotlight-input');
    var emptyEl   = document.getElementById('spotlight-empty');
    var loadEl    = document.getElementById('spotlight-loading');
    var list      = document.getElementById('spotlight-list');
    var trigger   = document.getElementById('spotlight-trigger');
    var escBtn    = document.getElementById('spotlight-esc');
    var histSec   = document.getElementById('spotlight-history-section');
    var histList  = document.getElementById('spotlight-history-list');
    var clearBtn  = document.getElementById('spotlight-clear-history-btn');
    var clearMain = document.getElementById('spotlight-clear-history');
    var suggestEl = document.getElementById('spotlight-suggestions');
    var chipWrap  = document.getElementById('spotlight-suggestion-chips');

    var ajaxTimer = null, activeIndex = -1, currentResults = [];

    /* ── open / close ── */
    function open(){
        if(!overlay) return;
        overlay.style.display='flex';
        overlay.style.animation='spotlightOverlayFadeIn 0.25s ease forwards';
        if(box) box.style.animation='spotlightModalBounceIn 0.35s cubic-bezier(0.34,1.3,0.64,1) forwards';
        setTimeout(function(){ if(input) input.focus(); },100);
        renderHistory();
        renderChips(getSuggestions(''));
        if(clearMain) clearMain.style.display = getHistory().length>0?'block':'none';
    }
    function close(){
        if(box) box.style.animation='spotlightModalFadeOut 0.2s ease forwards';
        if(overlay) overlay.style.animation='spotlightOverlayFadeOut 0.2s ease forwards';
        setTimeout(function(){
            if(overlay) overlay.style.display='none';
            if(input) input.value='';
            showEmpty(true);
        },200);
    }

    function showEmpty(withChips){
        if(emptyEl){ emptyEl.style.display='block'; }
        if(loadEl) loadEl.style.display='none';
        if(list){ list.style.display='none'; list.innerHTML=''; }
        if(clearMain) clearMain.style.display = getHistory().length>0?'block':'none';
        renderHistory();
        if(withChips) renderChips(getSuggestions(''));
        currentResults=[]; activeIndex=-1;
    }

    function showLoading(){
        if(emptyEl) emptyEl.style.display='none';
        if(loadEl) loadEl.style.display='block';
        if(list) list.style.display='none';
        if(histSec) histSec.style.display='none';
        if(suggestEl) suggestEl.style.display='none';
    }

    /* ── suggestion chips ── */
    function renderChips(suggestions) {
        if (!chipWrap || !suggestEl) return;
        chipWrap.innerHTML = '';
        if (!suggestions || !suggestions.length) { suggestEl.style.display='none'; return; }
        suggestEl.style.display = 'block';
        suggestions.forEach(function(s, i){
            var btn = document.createElement('button');
            btn.className = 'spotlight-suggest-chip';
            btn.style.cssText = 'background:rgba(79,142,247,.12);border:1px solid rgba(79,142,247,.3);border-radius:20px;padding:5px 14px;color:rgba(255,255,255,.8);font-size:12px;font-weight:500;cursor:pointer;transition:all .15s;white-space:nowrap;animation-delay:'+(i*0.04)+'s;opacity:0;';
            btn.textContent = s.label;
            btn.addEventListener('mouseenter', function(){ btn.style.background='rgba(79,142,247,.25)'; btn.style.borderColor='rgba(79,142,247,.6)'; });
            btn.addEventListener('mouseleave', function(){ btn.style.background='rgba(79,142,247,.12)'; btn.style.borderColor='rgba(79,142,247,.3)'; });
            btn.addEventListener('click', function(){
                if (input) { input.value = s.q; input.focus(); }
                performSearch(s.q);
            });
            chipWrap.appendChild(btn);
        });
    }

    /* ── history ── */
    function renderHistory(){
        var h = getHistory();
        if (h.length > 0 && (!input || !input.value.trim())) {
            if(histSec) histSec.style.display='block';
            if(histList) histList.innerHTML='';
            if(clearMain) clearMain.style.display='block';
            h.forEach(function(item, idx){
                var div = document.createElement('div');
                div.className = 'spotlight-history-item';
                div.style.cssText = 'display:flex;align-items:center;gap:14px;padding:10px 24px;cursor:pointer;transition:background .15s;border-radius:10px;margin:0 16px;';
                var c = CAT_COLORS[item.category]||'#4f8ef7';
                div.innerHTML = '<span style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:'+c+'22;"><i class="'+(item.icon||'mdi-history')+' mdi" style="font-size:16px;color:'+c+';"></i></span>'
                    + '<span style="flex:1;min-width:0;"><span style="display:block;font-size:14px;font-weight:500;color:rgba(255,255,255,.9);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+escHtml(item.title)+'</span>'
                    + '<span style="display:block;font-size:11px;color:rgba(255,255,255,.4);margin-top:2px;">'+escHtml(item.category)+'</span></span>'
                    + '<button class="hist-remove" style="background:transparent;border:none;color:rgba(255,255,255,.35);cursor:pointer;font-size:13px;padding:6px 10px;border-radius:6px;">✕</button>';
                div.querySelector('.hist-remove').addEventListener('click', function(e){
                    e.stopPropagation();
                    var his = getHistory(); his.splice(idx,1); saveHistory(his); renderHistory();
                    if (!input || !input.value.trim()) showEmpty(true);
                });
                div.addEventListener('mouseenter', function(){ div.style.background='rgba(255,255,255,.04)'; });
                div.addEventListener('mouseleave', function(){ div.style.background=''; });
                div.addEventListener('click', function(){ if(input){ input.value=item.query; performSearch(item.query); } });
                if(histList) histList.appendChild(div);
            });
        } else {
            if(histSec) histSec.style.display='none';
            if(clearMain) clearMain.style.display='none';
        }
    }

    /* ── HTML escaping ── */
    function escHtml(str){
        if (!str) return '';
        return str.replace(/[&<>"']/g, function(m){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
        });
    }

    /* ── highlight matched chars in title ── */
    function highlightText(title, q){
        if (!q) return escHtml(title);
        var lq = q.toLowerCase(), lt = title.toLowerCase();
        var idx = lt.indexOf(lq);
        if (idx === -1) return escHtml(title);
        return escHtml(title.substring(0,idx))
            + '<mark style="background:rgba(79,142,247,.35);color:#a5c8ff;border-radius:3px;padding:0 2px;">' + escHtml(title.substring(idx,idx+lq.length)) + '</mark>'
            + escHtml(title.substring(idx+lq.length));
    }

    /* ── perform search ── */
    function performSearch(query){
        if (!query || !query.trim()) { showEmpty(true); return; }
        var sr = searchStatic(query);
        if (sr.length > 0) {
            renderResults(sr, query);
            renderChips(getSuggestions(query));
        } else {
            showLoading();
        }
        clearTimeout(ajaxTimer);
        ajaxTimer = setTimeout(function(){
            if (query.length < 2) return;
            fetch('{{ url("/api/search") }}?q='+encodeURIComponent(query)+'&_token={{ csrf_token() }}',{
                headers:{'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}
            }).then(function(r){ return r.ok ? r.json() : {results:[]}; })
              .then(function(d){
                  if (!input || input.value.trim() !== query) return;
                  var remote = d.results || [];
                  var seen = {};
                  var merged = sr.concat(remote).filter(function(r){ if(seen[r.url]) return false; seen[r.url]=true; return true; });
                  renderResults(merged, query);
              }).catch(function(){});
        }, 280);
    }

    /* ── render results ── */
    function renderResults(results, query){
        if(loadEl) loadEl.style.display='none';
        if(emptyEl) emptyEl.style.display='none';
        if(list){ list.innerHTML=''; list.style.display='block'; }
        if(histSec) histSec.style.display='none';
        activeIndex = -1; currentResults = results;

        if (!results.length) {
            if(emptyEl){
                emptyEl.innerHTML = '<i class="mdi mdi-magnify-close" style="font-size:42px;display:block;margin-bottom:16px;opacity:.4;"></i>'
                    + '<span style="font-size:15px;">No results for "'+escHtml(query)+'"</span>'
                    + '<div style="margin-top:12px;font-size:12px;opacity:.4;">Try a different term or browse from the sidebar</div>';
                emptyEl.style.display='block';
            }
            if(list) list.style.display='none';
            renderChips(POPULAR);
            return;
        }

        /* Group by category */
        var groups = {}, order = [];
        results.forEach(function(r){
            if (!groups[r.category]) { groups[r.category]=[]; order.push(r.category); }
            groups[r.category].push(r);
        });

        var globalIdx = 0;
        order.forEach(function(cat){
            /* category header */
            var hdr = document.createElement('li');
            hdr.style.cssText = 'padding:12px 24px 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.35);';
            hdr.textContent = cat;
            list.appendChild(hdr);

            groups[cat].forEach(function(r){
                var li = document.createElement('li');
                var isTop = (globalIdx === 0);
                li.className = 'spotlight-result-item' + (isTop ? ' top-match' : '');
                li.setAttribute('data-idx', globalIdx);
                li.style.cssText = 'display:flex;align-items:center;gap:14px;padding:12px 24px;cursor:pointer;transition:all .2s;border-radius:10px;margin:4px 12px;';
                var c = CAT_COLORS[r.category] || '#4f8ef7';
                li.innerHTML = '<span style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:'+c+'22;">'
                    + '<i class="'+(r.icon||'mdi-chevron-right')+' mdi" style="font-size:18px;color:'+c+';"></i></span>'
                    + '<span style="flex:1;min-width:0;">'
                    + '<span class="result-title" style="display:block;font-size:15px;font-weight:500;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                    + highlightText(r.title, query)
                    + '</span>'
                    + '<span style="display:block;font-size:12px;color:rgba(255,255,255,.4);margin-top:2px;">'+escHtml(r.category)+'</span></span>'
                    + '<i class="mdi mdi-arrow-right" style="font-size:16px;color:rgba(255,255,255,.25);flex-shrink:0;transition:transform .2s;"></i>';

                var idx = globalIdx;
                li.addEventListener('mouseenter', function(){ li.style.background='rgba(79,142,247,.12)'; activeIndex=idx; });
                li.addEventListener('mouseleave', function(){ li.style.background=(activeIndex===idx)?'rgba(79,142,247,.18)':''; });
                li.addEventListener('click', function(){ addHistory(input?input.value:'', r); saveHistory(getHistory()); window.location.href=r.url; });
                list.appendChild(li);
                globalIdx++;
            });
        });
    }

    /* ── keyboard navigation ── */
    function highlightItem(items){
        items.forEach(function(li, i){
            var active = (i === activeIndex);
            li.style.background = active ? 'rgba(79,142,247,.18)' : '';
            var t   = li.querySelector('.result-title');
            if (t)   t.style.color = active ? '#4f8ef7' : '#fff';
            var arr = li.querySelector('.mdi-arrow-right');
            if (arr) arr.style.transform = active ? 'translateX(6px)' : 'translateX(0)';
            if (active) li.scrollIntoView({block:'nearest'});
        });
    }

    /* ── event listeners ── */
    if (trigger)   trigger.addEventListener('click', open);
    if (escBtn)    escBtn.addEventListener('click', close);
    if (clearBtn)  clearBtn.addEventListener('click', function(){ localStorage.removeItem(HISTORY_KEY); renderHistory(); showEmpty(true); });
    if (clearMain) clearMain.addEventListener('click', function(){ localStorage.removeItem(HISTORY_KEY); renderHistory(); showEmpty(true); });
    if (overlay)   overlay.addEventListener('click', function(e){ if (e.target===overlay) close(); });

    document.addEventListener('keydown', function(e){
        if ((e.metaKey||e.ctrlKey) && e.key==='k'){ e.preventDefault(); overlay&&overlay.style.display==='flex' ? close() : open(); }
        if (e.key==='Escape' && overlay && overlay.style.display==='flex') close();
    });

    if (input) {
        input.addEventListener('keydown', function(e){
            var items = list ? Array.prototype.slice.call(list.querySelectorAll('li[data-idx]')) : [];
            if (e.key==='ArrowDown') { e.preventDefault(); activeIndex=Math.min(activeIndex+1,items.length-1); highlightItem(items); }
            else if (e.key==='ArrowUp') { e.preventDefault(); activeIndex=Math.max(activeIndex-1,0); highlightItem(items); }
            else if (e.key==='Enter' && activeIndex>=0 && currentResults[activeIndex]) {
                addHistory(input.value, currentResults[activeIndex]); saveHistory(getHistory());
                window.location.href = currentResults[activeIndex].url;
            }
        });
        input.addEventListener('input', function(){
            var q = this.value.trim();
            if (!q) {
                showEmpty(true);
                renderHistory();
                if(clearMain) clearMain.style.display = getHistory().length>0 ? 'block' : 'none';
                return;
            }
            if(clearMain) clearMain.style.display = 'none';
            renderChips(getSuggestions(q));
            performSearch(q);
        });
    }

    renderHistory();
})();
</script>

<!-- =====================================================
     ROUTE-SPECIFIC JS INCLUDES
     ===================================================== -->
@if (Route::is('users.*'))                 @include('layouts.pages-assets.js.users-list-js') @endif
@if (Route::is('profile.*'))               @include('layouts.pages-assets.js.users-list-js') @endif
@if (Route::is('roles.*'))                 @include('layouts.pages-assets.js.role-list-js') @endif
@if (Route::is('permissions.*'))           @include('layouts.pages-assets.js.permissions-list-js') @endif
{{-- subjects.index is Subject Registration: it has its own scripts, and subject-list.init.js
     (Subjects page) overrides its filterData(), hijacks pagination and loads Bootstrap twice. --}}
@if (Route::is('admin.scholarship.*') || Route::is('admin.discount.*') || Route::is('sibling.*') ||
    Route::is('payment.*') || Route::is('reports.financial.*') || Route::is('reports.analysis.*') ||
    Route::is('payroll.*') || Route::is('staff.payments.*'))
@endif

<script>
/* Expired session on an AJAX / fetch call (HTTP 419) → go to the login page
   instead of leaving the page half-working. Full-page posts are redirected
   by the server already. */
(function () {
    var loginUrl = @json(route('login'));
    var sent = false;
    function toLogin() {
        if (sent) return; sent = true;
        window.location.href = loginUrl + (loginUrl.indexOf('?') === -1 ? '?' : '&') + 'expired=1';
    }
    if (window.jQuery) {
        jQuery(document).ajaxError(function (_e, xhr) { if (xhr && xhr.status === 419) toLogin(); });
    }
    if (window.fetch) {
        var _fetch = window.fetch;
        window.fetch = function () {
            return _fetch.apply(this, arguments).then(function (res) {
                if (res && res.status === 419) toLogin();
                return res;
            });
        };
    }
    if (window.axios && window.axios.interceptors) {
        window.axios.interceptors.response.use(null, function (err) {
            if (err && err.response && err.response.status === 419) toLogin();
            return Promise.reject(err);
        });
    }
})();
</script>
</body>
</html>