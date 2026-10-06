<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <title>{{ $pagetitle }} | Vite-ESchool 2.0</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="school management software" name="description">
    <meta content="" name="author">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic Favicon from School Logo -->
    @php
        $activeSchool = App\Models\SchoolInformation::getActiveSchool();
        $faviconUrl = $activeSchool ? $activeSchool->getLogoWithFallbackAttribute() : asset('theme/layouts/assets/images/favicon.ico');
    @endphp
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
    @if (Route::is('student-id-cards.*'))     @include('layouts.pages-assets.css.users-list-css') @endif
    @if (Route::is('student.payments.*'))     @include('layouts.pages-assets.css.users-list-css') @endif
    @if (Route::is('profile.*'))              @include('layouts.pages-assets.css.users-list-css') @endif
    @if (Route::is('roles.*'))                @include('layouts.pages-assets.css.roles-list-css') @endif
    @if (Route::is('permissions.*'))          @include('layouts.pages-assets.css.permission-list-css') @endif
    @if (Route::is('session.*'))              @include('layouts.pages-assets.css.session-list-css') @endif
    @if (Route::is('school-information.*'))   @include('layouts.pages-assets.css.schoolinformation-list-css') @endif
    @if (Route::is('admin.school-info.*'))    @include('layouts.pages-assets.css.schoolinformation-list-css') @endif
    @if (Route::is('term.*'))                 @include('layouts.pages-assets.css.term-list-css') @endif
    @if (Route::is('schoolhouse.*'))          @include('layouts.pages-assets.css.schoolhouse-list-css') @endif
    @if (Route::is('schoolarm.*'))            @include('layouts.pages-assets.css.arm-list-css') @endif
    @if (Route::is('classcategories.*'))      @include('layouts.pages-assets.css.classcategory-list-css') @endif
    @if (Route::is('schoolclass.*'))          @include('layouts.pages-assets.css.schoolclass-list-css') @endif
    @if (Route::is('classteacher.*'))         @include('layouts.pages-assets.css.classteacher-list-css') @endif
    @if (Route::is('subject.*'))              @include('layouts.pages-assets.css.subject-list-css') @endif
    @if (Route::is('subjects.*'))             @include('layouts.pages-assets.css.subject-list-css') @endif
    @if (Route::is('subjectteacher.*'))       @include('layouts.pages-assets.css.subjectteacher-list-css') @endif
    @if (Route::is('subjectclass.*'))         @include('layouts.pages-assets.css.subjectclass-list-css') @endif
    @if (Route::is('schoolbill.*'))           @include('layouts.pages-assets.css.schoolbill-list-css') @endif
    @if (Route::is('schoolbilltermsession.*'))@include('layouts.pages-assets.css.schoolbilltermsession-list-css') @endif
    @if (Route::is('student.*'))              @include('layouts.pages-assets.css.student-list-css') @endif
    @if (Route::is('studentbatchindex'))      @include('layouts.pages-assets.css.student-list-css') @endif
    @if (Route::is('myclass.*'))              @include('layouts.pages-assets.css.myclass-list-css') @endif
    @if (Route::is('mysubject.*'))            @include('layouts.pages-assets.css.mysubject-list-css') @endif
    @if (Route::is('viewstudent'))            @include('layouts.pages-assets.css.viewstudent-list-css') @endif
    @if (Route::is('studentreports.*'))       @include('layouts.pages-assets.css.studentreport-list-css') @endif
    @if (Route::is('studentmockreports.*'))   @include('layouts.pages-assets.css.studentreport-list-css') @endif
    @if (Route::is('broadsheet*'))            @include('layouts.pages-assets.css.broadsheet-list-css') @endif
    @if (Route::is('subjectoperation.*'))     @include('layouts.pages-assets.css.subjectoperation-list-css') @endif
    @if (Route::is('subjects.subjectinfo'))   @include('layouts.pages-assets.css.subjectinfo-list-css') @endif
    @if (Route::is('myresultroom.*'))         @include('layouts.pages-assets.css.myresultroom-list-css') @endif
    @if (Route::is('subjectscoresheet'))      @include('layouts.pages-assets.css.subjectscoresheet-list-css') @endif
    @if (Route::is('subassessment.*'))        @include('layouts.pages-assets.css.subjectscoresheet-list-css') @endif
    @if (Route::is('assessment.*'))           @include('layouts.pages-assets.css.subjectscoresheet-list-css') @endif
    @if (Route::is('assessments'))            @include('layouts.pages-assets.css.subjectscoresheet-list-css') @endif
    @if (Route::is('subjectscoresheet-mock.*'))@include('layouts.pages-assets.css.subjectscoresheet-mock-list-css') @endif
    @if (Route::is('studentresults*'))        @include('layouts.pages-assets.css.studentresults-list-css') @endif
    @if (Route::is('schoolpayment*'))         @include('layouts.pages-assets.css.schoolpayment-list-css') @endif
    @if (Route::is('analysis*'))              @include('layouts.pages-assets.css.analysis-list-css') @endif
    @if (Route::is('exams*'))                 @include('layouts.pages-assets.css.exams-list-css') @endif
    @if (Route::is('questions*'))             @include('layouts.pages-assets.css.questions-list-css') @endif
    @if (Route::is('cbt*'))                   @include('layouts.pages-assets.css.cbt-list-css') @endif
    @if (Route::is('classbroadsheet.*'))      @include('layouts.pages-assets.css.classbroadsheet-list-css') @endif
    @if (Route::is('principalscomment.*'))    @include('layouts.pages-assets.css.principalscomment-list-css') @endif
    @if (Route::is('myprincipalscomment.*'))  @include('layouts.pages-assets.css.myprincipalscomment-list-css') @endif
    @if (Route::is('compulsorysubjectclass.*'))@include('layouts.pages-assets.css.compulsorysubjectclass-list-css') @endif
    @if (Route::is('subjectvetting.*'))       @include('layouts.pages-assets.css.subjectvettings-list-css') @endif
    @if (Route::is('mocksubjectvetting.*'))   @include('layouts.pages-assets.css.mocksubjectvettings-list-css') @endif
    @if (Route::is('mysubjectvettings.*'))    @include('layouts.pages-assets.css.mysubjectvettings-list-css') @endif
    @if (Route::is('mymocksubjectvettings.*'))@include('layouts.pages-assets.css.mymocksubjectvettings-list-css') @endif
    @if (Route::is('timetable.*'))            @include('layouts.pages-assets.css.timetable-list-css') @endif
    @if (Route::is('rooms.*'))                @include('layouts.pages-assets.css.rooms-list-css') @endif
    @if (Route::is('promotions.*'))           @include('layouts.pages-assets.css.promotions-list-css') @endif
    @if (Route::is('attendance.*'))           @include('layouts.pages-assets.css.attendance-list-css') @endif
    @if (Route::is('device-mappings.*'))      @include('layouts.pages-assets.css.attendance-list-css') @endif
    @if (Route::is('staff-attendance.*'))     @include('layouts.pages-assets.css.attendance-list-css') @endif
    @if (Route::is('transcript.*'))           @include('layouts.pages-assets.css.attendance-list-css') @endif
    @if (Route::is('admin.score-entry.*'))    @include('layouts.pages-assets.css.adminscoreentry-list-css') @endif
    @if (Route::is('admin.scholarship.*') || Route::is('admin.discount.*') || Route::is('sibling.*') ||
        Route::is('payment.*') || Route::is('reports.financial.*') || Route::is('reports.analysis.*') ||
        Route::is('payroll.*') || Route::is('staff.payments.*'))
        @include('layouts.pages-assets.css.finance-list-css')
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
    <div class="app-menu navbar-menu">

        <!-- LOGO -->
        <div class="navbar-brand-box">
            @php
                use App\Models\SchoolInformation;
                $schoolInfo = SchoolInformation::getActiveSchool();
                $schoolName = $schoolInfo?->school_name ?? config('app.name', 'School System');
                $defaultLogo      = asset('theme/layouts/assets/images/logo-dark.png');
                $defaultLogoLight = asset('theme/layouts/assets/images/logo-light.png');
            @endphp

            <a href="{{ url('/') }}" class="logo logo-dark">
                <span class="logo-sm">
                    <img src="{{ $schoolInfo?->getLogoUrlAttribute() ?? $defaultLogo }}" alt="{{ $schoolName }}"
                         style="height:80px;width:auto;border-radius:10px;object-fit:contain;padding:3px;background:rgb(39,38,38);">
                </span>
                <span class="logo-lg">
                    <img src="{{ $schoolInfo?->getLogoUrlAttribute() ?? $defaultLogo }}" alt="{{ $schoolName }}"
                         style="height:80px;width:auto;border-radius:12px;object-fit:contain;padding:2px;background:rgb(37,36,36);">
                </span>
            </a>
            <a href="{{ url('/') }}" class="logo logo-light">
                <span class="logo-sm">
                    <img src="{{ $schoolInfo?->getLogoUrlAttribute() ?? $defaultLogoLight }}" alt="{{ $schoolName }}"
                         style="height:45px;width:auto;border-radius:10px;object-fit:contain;padding:3px;background:rgb(40,39,39);">
                </span>
                <span class="logo-lg">
                    <img src="{{ $schoolInfo?->getLogoUrlAttribute() ?? $defaultLogoLight }}" alt="{{ $schoolName }}"
                         style="height:80px;width:auto;border-radius:12px;object-fit:contain;padding:2px;background:rgb(37,36,36);">
                </span>
            </a>

            <button type="button" class="btn btn-sm p-0 fs-3xl header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                <i class="ri-record-circle-line"></i>
            </button>
        </div>

        <!-- NAV (scrollable) -->
        <div id="scrollbar">
            <div class="container-fluid">
                <div id="two-column-menu"></div>
                <ul class="navbar-nav" id="navbar-nav">

                    <li class="menu-title"><span data-key="t-menu">Menu</span></li>

                    {{-- MOVED_ADMIN_TOP: Dashboards + Users first --}}
                    {{-- Dashboard --}}
                    @unless(auth()->user()->hasRole('Parent') && !auth()->user()->can('dashboard'))
                    <li class="nav-item">
                        <a class="nav-link menu-link collapsed" href="#sidebarDashboards" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarDashboards">
                            <i class="ph-gauge"></i> <span data-key="t-dashboards">Dashboards</span>
                        </a>
                        <div class="collapse menu-dropdown" id="sidebarDashboards">
                            <ul class="nav nav-sm flex-column">
                                @feature('dashboard')
                                <li class="nav-item">
                                    <a href="{{ route('dashboard') }}" class="nav-link" data-key="t-analytics">Administration Analytics</a>
                                </li>
                                @endfeature
                                @can('View management dashboard')
                                <li class="nav-item">
                                    <a href="{{ route('management.dashboard') }}" class="nav-link">Management Overview</a>
                                </li>
                                @endcan
                                @can('finance dashboard')
                                <li class="nav-item">
                                    <a href="dashboard-crm.html" class="nav-link" data-key="t-crm">Finance Analytics</a>
                                </li>
                                @endcan
                                @can('academics dashboard')
                                <li class="nav-item">
                                    <a href="index.html" class="nav-link" data-key="t-ecommerce">Academics Analytics</a>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    @endunless

                    {{-- USERS & PRIVILEGES --}}
                    @if(auth()->user()->can('View user') || auth()->user()->can('View role') || auth()->user()->can('View user-account'))
                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">USERS & PRIVILEDGES</span></li>
                    @endif

                    @can('View user')
                        <li class="nav-item">
                            <a class="nav-link menu-link collapsed" href="#sidebarusers" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarusers">
                                <i class="ph-user-circle"></i> <span data-key="t-authentication">User Managements</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarusers">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('users.index') }}" class="nav-link" data-key="t-signin">Users</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endcan

                    {{-- My Account --}}
                    <li class="nav-item">
                        <a class="nav-link menu-link collapsed" href="#sidebaraccount" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebaraccount">
                            <i class="ph-address-book"></i> <span data-key="t-pages">My Account</span>
                        </a>
                        <div class="collapse menu-dropdown" id="sidebaraccount">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('users.overview', ['id' => Auth::id()]) }}" class="nav-link">
                                        <i class="ri-profile-line me-2"></i> My Profile
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}" class="nav-link">
                                        <i class="ri-settings-3-line me-2"></i> Account Settings
                                    </a>
                                </li>
                                @if(Auth::user()->isStaff())
                                <li class="nav-item">
                                    <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#employmentInfo" class="nav-link ps-4">
                                        <i class="ri-building-line me-2"></i> Employment Details
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#qualifications" class="nav-link ps-4">
                                        <i class="ri-graduation-cap-line me-2"></i> Academic Qualifications
                                    </a>
                                </li>
                                @endif
                                @if(Auth::user()->isStudent())
                                <li class="nav-item">
                                    <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#studentInfo" class="nav-link ps-4">
                                        <i class="ri-user-line me-2"></i> Student Details
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#parentInfo" class="nav-link ps-4">
                                        <i class="ri-parent-line me-2"></i> Parent Information
                                    </a>
                                </li>
                                @endif
                                <li class="nav-item">
                                    <a href="{{ route('profile.settings', ['id' => Auth::id()]) }}#security" class="nav-link ps-4">
                                        <i class="ri-lock-password-line me-2"></i> Change Password
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    @can('View role')
                        <li class="nav-item">
                            <a class="nav-link menu-link collapsed" href="#sidebarroles" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarroles">
                                <i class="ph-address-book"></i> <span data-key="t-pages">Roles And Permissions</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarroles">
                                <ul class="nav nav-sm flex-column">
                                    @can('View role')
                                        <li class="nav-item"><a href="{{ route('roles.index') }}" class="nav-link">Roles</a></li>
                                    @endcan
                                    @can('View permission')
                                        <li class="nav-item"><a href="{{ route('permissions.index') }}" class="nav-link">Permissions</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endcan


                    {{-- PARENT PORTAL --}}
                    @role('Parent')
                        @php $ppKids = \App\Services\Parents\ParentAccountService::children(auth()->user()); @endphp
                        <li class="nav-item">
                            <a href="{{ route('parent.dashboard') }}" class="nav-link menu-link {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
                                <i class="ri-parent-line"></i> <span>My Children</span>
                            </a>
                        </li>
                        @foreach($ppKids as $k)
                            <li class="nav-item">
                                <a href="#sidebarChild{{ $k->id }}" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarChild{{ $k->id }}">
                                    <i class="ri-user-smile-line"></i> <span>{{ $k->firstname }}</span>
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarChild{{ $k->id }}">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item"><a href="{{ route('parent.results', $k->id) }}" class="nav-link">Results</a></li>
                                        <li class="nav-item"><a href="{{ route('parent.fees', $k->id) }}" class="nav-link">Fees &amp; Payments</a></li>
                                        <li class="nav-item"><a href="{{ route('parent.attendance', $k->id) }}" class="nav-link">Attendance</a></li>
                                        <li class="nav-item"><a href="{{ route('parent.timetable', $k->id) }}" class="nav-link">Timetable</a></li>
                                        @if(Route::has('lms.parent.child'))<li class="nav-item"><a href="{{ route('lms.parent.child', $k->id) }}" class="nav-link">Learning</a></li>@endif
                                    </ul>
                                </div>
                            </li>
                        @endforeach
                        @if(Route::has('student-leave.mine'))
                        <li class="nav-item">
                            <a href="{{ route('student-leave.mine') }}" class="nav-link menu-link {{ request()->routeIs('student-leave.mine') ? 'active' : '' }}"><i class="ri-calendar-event-line"></i> <span>Leave of Absence</span></a>
                        </li>
                        @if(Route::has('calendar.index'))
                        <li class="nav-item">
                            <a href="{{ route('calendar.index') }}" class="nav-link menu-link {{ request()->routeIs('calendar.index') ? 'active' : '' }}"><i class="ri-calendar-2-line"></i> <span>School Calendar</span></a>
                        </li>
                        @endif
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('notifications.index') }}" class="nav-link menu-link"><i class="ri-notification-3-line"></i> <span>Notices</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('parent.password') }}" class="nav-link menu-link"><i class="ri-lock-password-line"></i> <span>Change Password</span></a>
                        </li>
                    @endrole

                    {{-- MY PAY (staff self-service) --}}
                    @php $myStaffId = \Illuminate\Support\Facades\DB::table('staffbioinfo')->where('userid', auth()->id())->value('id'); @endphp
                    @if($myStaffId)
                        <li class="nav-item">
                            <a href="{{ route('my-pay.index') }}" class="nav-link menu-link {{ request()->routeIs('my-pay.*') ? 'active' : '' }}"><i class="ri-wallet-3-line"></i> <span>My Pay</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('leave.index') }}" class="nav-link menu-link {{ request()->routeIs('leave.*') ? 'active' : '' }}"><i class="ri-calendar-event-line"></i> <span>My Leave</span></a>
                        </li>
                    @endif

                    @if(($myStaffId || auth()->user()->can('Manage school calendar') || auth()->user()->can('View school calendar')) && Route::has('calendar.index'))
                        <li class="nav-item">
                            <a href="{{ route('calendar.index') }}" class="nav-link menu-link {{ request()->routeIs('calendar.index') ? 'active' : '' }}"><i class="ri-calendar-2-line"></i> <span>School Calendar</span></a>
                        </li>
                    @endif
                    @if(auth()->user()->can('View activity log') || auth()->user()->can('View online staff'))
                        <li class="nav-item">
                            <a href="#sidebarActivity" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarActivity"><i class="ri-shield-user-line"></i> <span>Staff Monitoring</span></a>
                            <div class="collapse menu-dropdown" id="sidebarActivity"><ul class="nav nav-sm flex-column">
                                <li class="nav-item"><a href="{{ route('online-staff.index') }}" class="nav-link">Who's Online</a></li>
                                @can('View activity log')<li class="nav-item"><a href="{{ route('activity.index') }}" class="nav-link">Activity Log</a></li>@endcan
                                @canany(['View leave records', 'Manage leave types'])<li class="nav-item"><a href="{{ route('leave.records') }}" class="nav-link">Leave Records</a></li>@endcanany
                                @canany(['View leave records', 'Manage leave types'])@if(Route::has('leave.balances'))<li class="nav-item"><a href="{{ route('leave.balances') }}" class="nav-link">Leave Balances</a></li>@endif@endcanany
                            </ul></div>
                        </li>
                    @endif

                    @canany(['Recommend student leave', 'Approve student leave', 'View student leave records'])
                    @if(Route::has('student-leave.approvals'))
                        <li class="nav-item">
                            <a href="#sidebarStudentLeave" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarStudentLeave"><i class="ri-user-follow-line"></i> <span>Student Leave</span></a>
                            <div class="collapse menu-dropdown" id="sidebarStudentLeave"><ul class="nav nav-sm flex-column">
                                @canany(['Recommend student leave', 'Approve student leave'])<li class="nav-item"><a href="{{ route('student-leave.approvals') }}" class="nav-link">Approvals</a></li>@endcanany
                                @can('View student leave records')<li class="nav-item"><a href="{{ route('student-leave.records') }}" class="nav-link">Records</a></li>@endcan
                            </ul></div>
                        </li>
                    @endif
                    @endcanany

                    @canany(['View leave records', 'View student leave records', 'Approve leave', 'Approve student leave'])
                    @if(Route::has('leave.board'))
                        <li class="nav-item">
                            <a href="{{ route('leave.board') }}" class="nav-link menu-link {{ request()->routeIs('leave.board') ? 'active' : '' }}"><i class="ri-team-line"></i> <span>Who's Away</span></a>
                        </li>
                    @endif
                    @endcanany

                    {{-- STUDENT & PARENTS --}}
                    @if(auth()->user()->can('View student') || auth()->user()->can('Create student-bulk-upload') || auth()->user()->can('View parent') || auth()->user()->can('View id card'))
                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-apps">STUDENT & PARENTS</span></li>
                    @endif

                    @feature('students')
                    @if(auth()->user()->can('View student') || auth()->user()->can('Create student-bulk-upload'))
                        <li class="nav-item">
                            <a href="#sidebarStudentmanagement" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarStudentmanagement">
                                <i class="ph-storefront"></i> <span>Student Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarStudentmanagement">
                                <ul class="nav nav-sm flex-column">
                                    @can('View student')
                                        <li class="nav-item"><a href="{{ route('student.index') }}" class="nav-link">All Students</a></li>
                                    @endcan
                                    @can('Create student-bulk-upload')
                                        <li class="nav-item"><a href="{{ route('studentbatchindex') }}" class="nav-link">Batch Student Registration</a></li>
                                    @endcan
                                    @can('View id card')
                                        <li class="nav-item"><a href="{{ route('student-id-cards.index') }}" class="nav-link">ID Card Generator</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    @if(auth()->user()->can('View student assessments') || auth()->user()->can('View student payments'))
                        <li class="menu-title"><i class="ph-graduation-cap"></i> <span>STUDENT PORTAL</span></li>
                    @endif

                    @feature('exams')
                    @can('View student assessments')
                        <li class="nav-item">
                            <a href="#sidebarAssessments" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAssessments">
                                <i class="ph-graduation-cap"></i> <span>Assessments</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarAssessments">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('assessments') }}" class="nav-link">My Assessments</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @if(auth()->user()->student_id && Route::has('lms.learn.index'))
                    <li class="nav-item">
                        <a href="{{ route('lms.learn.index') }}" class="nav-link menu-link {{ request()->routeIs('lms.learn.*') ? 'active' : '' }}"><i class="ri-graduation-cap-line"></i> <span>My Learning</span></a>
                    </li>
                    @endif

                    <li class="nav-item">
                        <a href="#sidebarPayment" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPayment">
                            <i class="ph-graduation-cap"></i> <span>Payments</span>
                        </a>
                        <div class="collapse menu-dropdown" id="sidebarPayment">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item"><a href="{{ route('student.payments') }}" class="nav-link">My Payments</a></li>
                                <li class="nav-item"><a href="{{ route('student.fees.pay') }}" class="nav-link">Pay School Fees</a></li>
                            </ul>
                        </div>
                    </li>

                    @if(auth()->user()->student_id && Route::has('student-leave.mine'))
                    <li class="nav-item">
                        <a href="{{ route('student-leave.mine') }}" class="nav-link menu-link {{ request()->routeIs('student-leave.mine') ? 'active' : '' }}"><i class="ri-calendar-event-line"></i> <span>Leave of Absence</span></a>
                    </li>
                    @endif

                    @if(auth()->user()->student_id && Route::has('calendar.index'))
                    <li class="nav-item">
                        <a href="{{ route('calendar.index') }}" class="nav-link menu-link {{ request()->routeIs('calendar.index') ? 'active' : '' }}"><i class="ri-calendar-2-line"></i> <span>School Calendar</span></a>
                    </li>
                    @endif

                    @can('View parent')
                        <li class="nav-item">
                            <a href="#sidebarParent" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarParent">
                                <i class="ph-storefront"></i> <span>Parent Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarParent">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('parent.index') }}" class="nav-link">All Parents</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan

                    {{-- SUBJECT REGISTRATION --}}
                    @feature('subjects')
                    @if(auth()->user()->can('View my-class') || auth()->user()->can('View my-subject'))
                        <li class="menu-title"><i class="ph-folder-open"></i> <span>SUBJECT REGISTRATION</span></li>
                        <li class="nav-item">
                            <a href="#sidebarsubjectoperaton" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarsubjectoperaton">
                                <i class="ph-folder-open"></i> <span>Subject Registration</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarsubjectoperaton">
                                <ul class="nav nav-sm flex-column">
                                    @can('View my-class')
                                        <li class="nav-item"><a href="{{ route('subjectoperation.index') }}" class="nav-link">Student Subject Registration</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    {{-- EXAMS AND CBT --}}
                    @if(auth()->user()->can('View exam') || auth()->user()->can('View cbt-exam'))
                        <li class="menu-title"><i class="ph-graduation-cap"></i> <span>EXAMS AND CBT</span></li>
                    @endif

                    @feature('exams')
                    @can('View exam')
                        <li class="nav-item">
                            <a href="#sidebarExams" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarExams">
                                <i class="ph-graduation-cap"></i> <span>Exams Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarExams">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('exams.index') }}" class="nav-link">All Examinations</a></li>
                                    @can('View question')
                                        <li class="nav-item"><a href="{{ route('questions.all') }}" class="nav-link">Questions Management</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @feature('cbt')
                    @can('View cbt-exam')
                        <li class="nav-item">
                            <a href="#sidebarCBT" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCBT">
                                <i class="ph-graduation-cap"></i> <span>CBT Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarCBT">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('cbt.index') }}" class="nav-link">CBT Exercise</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    {{-- TIMETABLE --}}
                    @feature('timetable')
                    @if(auth()->user()->can('View timetable') || auth()->user()->can('View my timetable'))
                        <li class="nav-item">
                            <a href="#sidebartimetable" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebartimetable">
                                <i class="ph-calendar"></i> <span>Timetable Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebartimetable">
                                <ul class="nav nav-sm flex-column">
                                    @can('View timetable')
                                        <li class="nav-item"><a href="{{ route('timetable.index') }}" class="nav-link">Admin Timetable</a></li>
                                    @endcan
                                    @can('View my timetable')
                                        <li class="nav-item"><a href="{{ route('timetable.teacher') }}" class="nav-link">My Timetable</a></li>
                                    @endcan
                                    @can('View rooms')
                                        <li class="nav-item"><a href="{{ route('rooms.index') }}" class="nav-link">Room Management</a></li>
                                    @endcan
                                    @can('View exam timetable')
                                        <li class="nav-item"><a href="{{ route('exam-timetable.index') }}" class="nav-link">Exam Timetable</a></li>
                                    @endcan
                                    @can('View holidays')
                                        <li class="nav-item"><a href="{{ route('holidays.index') }}" class="nav-link">Holidays</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    {{-- CLASSES & RECORDS --}}
                    @if(auth()->user()->can('View my-class') || auth()->user()->can('View my-subject') || auth()->user()->can('View my-subject-vettings') || auth()->user()->can('View my-mock-subject-vettings') || auth()->user()->can('View myresult-room') || auth()->user()->can('View student-report') || auth()->user()->can('View student-mock-report') || auth()->user()->can('View my-principals-comment'))
                        <li class="menu-title"><i class="ph-folder-open"></i> <span>CLASSES & RECORDS</span></li>
                    @endif

                    @feature('classes')
                    @if(auth()->user()->can('View my-class') || auth()->user()->can('View my-subject') || auth()->user()->can('View my-subject-vettings') || auth()->user()->can('View my-mock-subject-vettings') || auth()->user()->can('View my-principals-comment'))
                        <li class="nav-item">
                            <a href="#sidebarClasses" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarClasses">
                                <i class="ph-folder-open"></i> <span>Classes & Subjects</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarClasses">
                                <ul class="nav nav-sm flex-column">
                                    @can('View my-class')
                                        <li class="nav-item"><a href="{{ route('myclass.index') }}" class="nav-link">My Class</a></li>
                                    @endcan
                                    @can('View my-subject')
                                        <li class="nav-item"><a href="{{ route('mysubject.index') }}" class="nav-link">My Subject</a></li>
                                    @endcan
                                    @can('View my-subject-vettings')
                                        <li class="nav-item"><a href="{{ route('mysubjectvettings.index') }}" class="nav-link">Subjects to Vet</a></li>
                                    @endcan
                                    @can('View my-mock-subject-vettings')
                                        <li class="nav-item"><a href="{{ route('mymocksubjectvettings.index') }}" class="nav-link">Mock Subjects to Vet</a></li>
                                    @endcan
                                    @can('View my-principals-comment')
                                        <li class="nav-item"><a href="{{ route('myprincipalscomment.index') }}" class="nav-link">Principal's Comment</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    {{-- ATTENDANCE --}}
                    @feature('attendance')
                    @if(auth()->user()->can('View attendance-register') || 
                        auth()->user()->can('View attendance-class-summary') || 
                        auth()->user()->can('View attendance-student-report') ||
                        auth()->user()->can('View device-mappings') ||
                        auth()->user()->can('View staff-attendance'))
                        <li class="menu-title"><i class="ph-calendar-check"></i> <span>ATTENDANCE</span></li>
                        <li class="nav-item">
                            <a href="#sidebarAttendance" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAttendance">
                                <i class="ph-calendar-check"></i> <span>Attendance</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarAttendance">
                                <ul class="nav nav-sm flex-column">
                                    @can('View attendance-register')
                                        <li class="nav-item"><a href="{{ route('attendance.my-classes') }}" class="nav-link">Mark Attendance</a></li>
                                    @endcan
                                    
                                    
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    {{-- RECORDS AND RESULTS --}}
                    @feature('results')
                    @if(auth()->user()->can('View myresult-room') || auth()->user()->can('View student-report') || auth()->user()->can('View student-mock-report') || auth()->user()->can('View admin-score-entry') || auth()->user()->can('View report-approvals') || auth()->user()->can('Submit report cards') || auth()->user()->can('Approve report cards'))
                        <li class="nav-item">
                            <a href="#sidebarRecords" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRecords">
                                <i class="ph-folder-open"></i> <span>Records and Results</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarRecords">
                                <ul class="nav nav-sm flex-column">
                                    @can('View myresult-room')
                                        <li class="nav-item"><a href="{{ route('myresultroom.index') }}" class="nav-link">Terminal Records</a></li>
                                    @endcan
                                    @canany(['View report-approvals', 'Submit report cards', 'Approve report cards'])
                                        <li class="nav-item"><a href="{{ route('report-approvals.index') }}" class="nav-link">Report Card Approval</a></li>
                                    @endcanany
                                    @can('View student-report')
                                        <li class="nav-item"><a href="{{ route('studentreports.index') }}" class="nav-link">Terminal Result Reports</a></li>
                                        <li class="nav-item"><a href="{{ route('broadsheet.index') }}" class="nav-link">Terminal Result Broadsheet</a></li>
                                    @endcan
                                    @can('Manage broadsheet ranking')
                                        <li class="nav-item"><a href="{{ route('broadsheet.ranking.index') }}" class="nav-link {{ request()->routeIs('broadsheet.ranking.*') ? 'active' : '' }}">Broadsheet Ranking</a></li>
                                    @endcan
                                    @can('View student-mock-report')
                                        <li class="nav-item"><a href="{{ route('studentmockreports.index') }}" class="nav-link">Mock Result Reports</a></li>
                                    @endcan
                                    @can('View admin-score-entry')
                                        <li class="nav-item"><a href="{{ route('admin.score-entry.index') }}" class="nav-link">Admin Score Entry</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    {{-- TRANSCRIPTS --}}
                    @feature('transcripts')
                    @if(auth()->user()->can('View student-transcript') || auth()->user()->can('Preview student-transcript') || auth()->user()->can('Download student-transcript'))
                        <li class="menu-title"><i class="ph-folder-open"></i> <span>TRANSCRIPTS</span></li>
                        <li class="nav-item">
                            <a href="#sidebarTranscript" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTranscript">
                                <i class="ri-file-text-line"></i> <span>Transcript</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarTranscript">
                                <ul class="nav nav-sm flex-column">
                                    @can('View student-transcript')
                                        <li class="nav-item"><a href="{{ route('transcript.index') }}" class="nav-link">Generate Transcript</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    {{-- PROMOTION MANAGEMENT --}}
                    @feature('promotions')
                    @if(auth()->user()->can('View myresult-room') || auth()->user()->can('View student-report') || auth()->user()->can('View student-mock-report'))
                        <li class="menu-title"><i class="ri-more-fill"></i> <span>PROMOTION MANAGEMENT</span></li>
                        <li class="nav-item">
                            <a href="#sidebarPromotions" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPromotions">
                                <i class="ph-folder-open"></i> <span>Promotion Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarPromotions">
                                <ul class="nav nav-sm flex-column">
                                    @can('View myresult-room')
                                        <li class="nav-item"><a href="{{ route('promotions.index') }}" class="nav-link">Student Promotion</a></li>
                                        <li class="nav-item"><a href="{{ route('promotion-settings.index') }}" class="nav-link">Promotion Settings</a></li>
                                        <li class="nav-item"><a href="{{ route('promotion.templates.index') }}" class="nav-link">Rule Templates</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    {{-- BURSARY & FINANCE --}}
                    @if(auth()->user()->can('View school-payment') || auth()->user()->can('View analysis') ||
                        auth()->user()->can('View scholarship') || auth()->user()->can('View discount') ||
                        auth()->user()->can('View sibling groups') || auth()->user()->can('View financial reports') ||
                        auth()->user()->can('View payroll') || auth()->user()->can('View staff payments'))
                        <li class="menu-title"><i class="ri-more-fill"></i> <span>BURSARY & FINANCE</span></li>
                    @endif

                    @feature('finance')
                    @can('View school-payment')
                        <li class="nav-item">
                            <a href="#sidebarStudentpayments" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarStudentpayments">
                                <i class="ph-storefront"></i> <span>Student Payments</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarStudentpayments">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('schoolpayment.index') }}" class="nav-link">Student Bill</a></li>
                                    <li class="nav-item"><a href="{{ route('payment.index') }}" class="nav-link">Payment Portal</a></li>
                                    @can('View online-fee-payments')<li class="nav-item"><a href="{{ route('online-fees.index') }}" class="nav-link">Online Payments</a></li>@endcan
                                    @can('View instalment-plans')<li class="nav-item"><a href="{{ route('instalment-plans.index') }}" class="nav-link">Instalment Plans</a></li>@endcan
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature


                    @feature('scholarships')
                    @can('View scholarship')
                        <li class="nav-item">
                            <a href="#sidebarScholarship" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarScholarship">
                                <i class="ph-graduation-cap"></i> <span>Scholarship Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarScholarship">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('admin.scholarship.index') }}" class="nav-link">All Scholarships</a></li>
                                    @can('Create scholarship')
                                        <li class="nav-item"><a href="{{ route('admin.scholarship.create') }}" class="nav-link">Create Scholarship</a></li>
                                    @endcan
                                    <li class="nav-item"><a href="{{ route('admin.scholarship.assignments') }}" class="nav-link">Scholarship Assignments</a></li>
                                    <li class="nav-item"><a href="{{ route('admin.scholarship.applications') }}" class="nav-link">Applications</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @feature('scholarships')
                    @can('View discount')
                        <li class="nav-item">
                            <a href="#sidebarDiscount" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarDiscount">
                                <i class="ph-tag"></i> <span>Discount Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarDiscount">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('admin.discount.index') }}" class="nav-link">All Discounts</a></li>
                                    @can('Create discount')
                                        <li class="nav-item"><a href="{{ route('admin.discount.create') }}" class="nav-link">Create Discount</a></li>
                                    @endcan
                                    <li class="nav-item"><a href="{{ route('admin.discount.assignments') }}" class="nav-link">Discount Assignments</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @feature('finance')
                    @can('View sibling groups')
                        <li class="nav-item">
                            <a href="#sidebarSibling" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSibling">
                                <i class="ph-users"></i> <span>Sibling Groups</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSibling">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('sibling.index') }}" class="nav-link">All Family Groups</a></li>
                                    <li class="nav-item"><a href="{{ route('sibling.create') }}" class="nav-link">Create Family Group</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @feature('expenses')
                    @if(auth()->user()->canany(['Create expenses', 'Approve expenses', 'Pay expenses', 'Manage budgets', 'Manage assets', 'Approve purchase requests', 'View financial reports']))
                        <li class="nav-item">
                            <a href="#sidebarExpenses" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarExpenses">
                                <i class="ri-receipt-line"></i> <span>Expenses &amp; Assets</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarExpenses">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('finance.expenses') }}" class="nav-link">Expenses</a></li>
                                    <li class="nav-item"><a href="{{ route('finance.purchases') }}" class="nav-link">Purchase Requests</a></li>
                                    @can('Create expenses')<li class="nav-item"><a href="{{ route('finance.vendors') }}" class="nav-link">Vendors</a></li>@endcan
                                    @canany(['Manage budgets', 'View financial reports'])<li class="nav-item"><a href="{{ route('finance.budgets') }}" class="nav-link">Budgets</a></li>@endcanany
                                    @canany(['Manage assets', 'View financial reports'])<li class="nav-item"><a href="{{ route('finance.assets') }}" class="nav-link">Fixed Assets</a></li>@endcanany
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endfeature

                    @can('Manage payment gateways')
                        <li class="nav-item">
                            <a href="{{ route('admin.payment-gateways.index') }}" class="nav-link">
                                <i class="ph-credit-card"></i> <span>Payment Gateways</span>
                            </a>
                        </li>
                    @endcan

                    @can('Manage maintenance mode')
                        <li class="nav-item">
                            <a href="{{ route('maintenance.settings') }}" class="nav-link">
                                <i class="ri-tools-line"></i> <span>Maintenance Mode</span>
                                @php $mmActive = false; try { $mmActive = \App\Models\MaintenanceSetting::current()->is_active; } catch (\Throwable $e) {} @endphp
                                @if($mmActive)<span class="badge bg-danger ms-1">ON</span>@endif
                            </a>
                        </li>
                    @endcan

                    @can('Manage feature flags')
                        <li class="nav-item">
                            <a href="{{ route('feature-flags.index') }}" class="nav-link">
                                <i class="ri-toggle-line"></i> <span>Module Access</span>
                            </a>
                        </li>
                    @endcan

                    @can('Manage backups')
                    @if(Route::has('admin.backups.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.backups.index') }}" class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                                <i class="ri-database-2-line"></i> <span>Database Backups</span>
                            </a>
                        </li>
                    @endif
                    @endcan

                    @canany(['Manage courses', 'Grade coursework'])
                    @if(Route::has('lms.courses.index'))
                        <li class="nav-item">
                            <a href="#sidebarElearning" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarElearning">
                                <i class="ri-book-open-line"></i> <span>E-Learning</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarElearning">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('lms.courses.index') }}" class="nav-link">Courses</a></li>
                                    <li class="nav-item"><a href="{{ route('lms.courses.create') }}" class="nav-link">New Course</a></li>
                                    @if(Route::has('lms.bank.index'))<li class="nav-item"><a href="{{ route('lms.bank.index') }}" class="nav-link">Question Bank</a></li>@endif
                                </ul>
                            </div>
                        </li>
                    @endif
                    @endcanany

                    @canany(['Manage topics', 'Verify topics'])
                    <li class="nav-item">
                        <a href="#sidebarCurriculum" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCurriculum">
                            <i class="ri-booklet-line"></i> <span>Curriculum</span>
                        </a>
                        <div class="collapse menu-dropdown" id="sidebarCurriculum">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item"><a href="{{ route('curriculum.topics.index') }}" class="nav-link">Topics &amp; Scheme</a></li>
                                <li class="nav-item"><a href="{{ route('curriculum.topics.coverage') }}" class="nav-link">Coverage</a></li>
                                @can('Manage topics')<li class="nav-item"><a href="{{ route('curriculum.reps.index') }}" class="nav-link">Class Reps</a></li>@endcan
                                @can('Manage teaching methods')<li class="nav-item"><a href="{{ route('curriculum.methods.index') }}" class="nav-link">Teaching Methods</a></li>@endcan
                            </ul>
                        </div>
                    </li>
                    @endcanany

                    @canany(['Track topics', 'Manage topics', 'Verify topics'])
                    <li class="nav-item"><a href="{{ route('curriculum.progress.index') }}" class="nav-link menu-link {{ request()->routeIs('curriculum.progress.*') ? 'active' : '' }}"><i class="ri-task-line"></i> <span>My Topics</span></a></li>
                    @canany(['Write lesson notes', 'Review lesson notes'])
                    <li class="nav-item"><a href="{{ route('curriculum.notes.index') }}" class="nav-link menu-link {{ request()->routeIs('curriculum.notes.*') ? 'active' : '' }}"><i class="ri-booklet-line"></i> <span>Lesson Notes</span></a></li>
                    @endcanany
                    @endcanany

                    @role('Class Rep')
                    <li class="nav-item"><a href="{{ route('curriculum.reps.confirm') }}" class="nav-link menu-link {{ request()->routeIs('curriculum.reps.confirm') ? 'active' : '' }}"><i class="ri-shield-check-line"></i> <span>Confirm Topics</span></a></li>
                    @endrole

                    @canany(['Write exam papers', 'Vet exam papers', 'Manage exam bank'])
                    <li class="nav-item">
                        <a href="#sidebarExamVetting" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarExamVetting">
                            <i class="ri-file-list-3-line"></i> <span>Paper Exams &amp; Vetting</span>
                        </a>
                        <div class="collapse menu-dropdown" id="sidebarExamVetting">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item"><a href="{{ route('exam.papers.index') }}" class="nav-link {{ request()->routeIs('exam.papers.*') ? 'active' : '' }}">Exam Papers</a></li>
                                @can('Vet exam papers')<li class="nav-item"><a href="{{ route('exam.vet.queue') }}" class="nav-link {{ request()->routeIs('exam.vet.*') ? 'active' : '' }}">Vetting Queue</a></li>@endcan
                                @canany(['Manage exam bank', 'Write exam papers'])<li class="nav-item"><a href="{{ route('exam.bank.index') }}" class="nav-link {{ request()->routeIs('exam.bank.*') ? 'active' : '' }}">Question Bank</a></li>@endcanany
                                <li class="nav-item"><a href="{{ route('exam.coverage.index') }}" class="nav-link {{ request()->routeIs('exam.coverage.index') ? 'active' : '' }}">Term-end Analysis</a></li>
                            </ul>
                        </div>
                    </li>
                    @endcanany

                    @canany(['Manage certificate templates', 'Generate certificates', 'View certificate audit'])
                    @if(Route::has('certificates.index'))
                        <li class="nav-item">
                            <a href="{{ route('certificates.index') }}" class="nav-link {{ request()->routeIs('certificates.*') ? 'active' : '' }}">
                                <i class="ri-award-line"></i> <span>Certificates</span>
                            </a>
                        </li>
                    @endif
                    @endcanany

                    @canany(['Manage certificate templates', 'Generate certificates', 'View certificate audit'])
                    @if(Route::has('certificates.index'))
                        <li class="nav-item">
                            <a href="{{ route('certificates.index', ['kind' => 'testimonial']) }}" class="nav-link {{ request()->routeIs('certificates.*') && request('kind')==='testimonial' ? 'active' : '' }}">
                                <i class="ri-file-user-line"></i> <span>Testimonials</span>
                            </a>
                        </li>
                    @endif
                    @endcanany

                    @feature('accounting')
                    @can('View financial reports')
                        <li class="nav-item">
                            <a href="#sidebarAccounting" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAccounting">
                                <i class="ph-chart-line"></i> <span>Accounting & Reports</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarAccounting">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('accounting.dashboard') }}" class="nav-link">Accounting Overview</a></li>
                                    <li class="nav-item"><a href="{{ route('accounting.journals') }}" class="nav-link">Journal</a></li>
                                    <li class="nav-item"><a href="{{ route('accounting.ledger') }}" class="nav-link">General Ledger</a></li>
                                    <li class="nav-item"><a href="{{ route('accounting.income-statement') }}" class="nav-link">Income &amp; Expenditure</a></li>
                                    <li class="nav-item"><a href="{{ route('accounting.balance-sheet') }}" class="nav-link">Balance Sheet</a></li>
                                    <li class="nav-item"><a href="{{ route('accounting.trial-balance') }}" class="nav-link">Trial Balance</a></li>
                                    <li class="nav-item"><a href="{{ route('accounting.cash-flow') }}" class="nav-link">Cash Flow</a></li>
                                    <li class="nav-item"><a href="{{ route('accounting.accounts') }}" class="nav-link">Chart of Accounts</a></li>
                                    <li class="nav-item"><a href="{{ route('reports.financial.debtors') }}" class="nav-link">Student Debtors List</a></li>
                                    @can('View result-access')
                                    <li class="nav-item"><a href="{{ route('result-access.index') }}" class="nav-link">Result Access Control</a></li>
                                    @endcan
                                    <li class="nav-item"><a href="{{ route('reports.financial.collection-summary') }}" class="nav-link">Collection Summary</a></li>
                                    <li class="nav-item"><a href="{{ route('reports.analysis.index') }}" class="nav-link">Class Analysis</a></li>
                                    <li class="nav-item"><a href="{{ route('reports.analysis.school-wide') }}" class="nav-link">School-Wide Analysis</a></li>
                                    @can('View financial audit')@if(Route::has('finance.audit.dashboard'))
                                    <li class="nav-item"><a href="{{ route('finance.audit.dashboard') }}" class="nav-link">Financial Audit</a></li>
                                    @endif@endcan
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @feature('payroll')
                    @can('View payroll')
                        <li class="nav-item">
                            <a href="#sidebarPayroll" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPayroll">
                                <i class="ph-money"></i> <span>Payroll Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarPayroll">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('payroll.periods') }}" class="nav-link">Payroll Periods</a></li>
                                    @canany(['Manage staff pay profiles', 'Manage payroll settings'])<li class="nav-item"><a href="{{ route('payroll.profiles') }}" class="nav-link">Staff Pay Profiles</a></li>@endcanany
                                    <li class="nav-item"><a href="{{ route('payroll.scales') }}" class="nav-link">Salary Scales</a></li>
                                    <li class="nav-item"><a href="{{ route('payroll.items') }}" class="nav-link">Allowances &amp; Deductions</a></li>
                                    <li class="nav-item"><a href="{{ route('payroll.reviews') }}" class="nav-link">Salary Reviews</a></li>
                                    @can('Manage payroll settings')<li class="nav-item"><a href="{{ route('payroll.rates') }}" class="nav-link">Rates &amp; Tax Bands</a></li>@endcan
                                    @can('Manage payroll settings')<li class="nav-item"><a href="{{ route('payroll.employer') }}" class="nav-link">Employer Details</a></li>@endcan
                                    <li class="nav-item"><a href="{{ route('payroll.remittances') }}" class="nav-link">Government Remittances</a></li>
                                    @canany(['View payroll', 'Release salary payments'])<li class="nav-item"><a href="{{ route('payroll.payouts') }}" class="nav-link">Salary Payments</a></li>@endcanany
                                    @canany(['Manage staff loans', 'Approve staff loans', 'View payroll'])<li class="nav-item"><a href="{{ route('payroll.loans') }}" class="nav-link">Loans &amp; Advances</a></li>@endcanany
                                    @canany(['Manage cooperative', 'View payroll'])<li class="nav-item"><a href="{{ route('payroll.coop') }}" class="nav-link">Staff Cooperative</a></li>@endcanany
                                    <li class="nav-item"><a href="{{ route('payroll.attendance-pay') }}" class="nav-link">Attendance &amp; Pay</a></li>
                                    @canany(['Approve duty claims', 'View payroll'])<li class="nav-item"><a href="{{ route('payroll.claims') }}" class="nav-link">Extra Duty Claims</a></li>@endcanany
                                    <li class="nav-item"><a href="{{ route('payroll.summary') }}" class="nav-link">Payroll Summary</a></li>
                                    <li class="nav-item"><a href="{{ route('payroll.statutory') }}" class="nav-link">Statutory Report</a></li>
                                    <li class="nav-item"><a href="{{ route('payroll.salary-structures') }}" class="nav-link">Salary Structures</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @feature('payroll')
                    @can('View staff payments')
                        <li class="nav-item">
                            <a href="#sidebarStaffPayments" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarStaffPayments">
                                <i class="ph-wallet"></i> <span>Staff Payments</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarStaffPayments">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('staff.payments.index') }}" class="nav-link">All Payments</a></li>
                                    <li class="nav-item"><a href="{{ route('staff.payments.dashboard') }}" class="nav-link">My Payments</a></li>
                                    <li class="nav-item"><a href="{{ route('my-pay.loans') }}" class="nav-link">My Loans</a></li>
                                    <li class="nav-item"><a href="{{ route('my-pay.claims') }}" class="nav-link">Extra Duty Claims</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    {{-- COMMUNICATION --}}
                    @if(auth()->user()->can('View notices') || auth()->user()->can('Create notices') || auth()->user()->can('Manage notification settings') || auth()->user()->can('View result-sends') || auth()->user()->can('Manage parent contacts') || auth()->user()->can('Manage parent accounts'))
                        <li class="menu-title"><i class="ri-more-fill"></i> <span>COMMUNICATION</span></li>
                    @endif

                    @feature('communication')
                    @can('View notices')
                        <li class="nav-item">
                            <a href="#sidebarNotices" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarNotices">
                                <i class="ri-megaphone-line"></i> <span>School Notices</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarNotices">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('notices.index') }}" class="nav-link">All Notices</a></li>
                                    @can('Create notices')
                                        <li class="nav-item"><a href="{{ route('notices.create') }}" class="nav-link">New Notice</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @feature('communication')
                    @can('View result-sends')
                        <li class="nav-item">
                            <a href="#sidebarResultSends" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarResultSends">
                                <i class="ri-mail-send-line"></i> <span>Send Results</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarResultSends">
                                <ul class="nav nav-sm flex-column">
                                    @can('Create result-sends')
                                        <li class="nav-item"><a href="{{ route('result-sends.create') }}" class="nav-link">Send Report Cards</a></li>
                                    @endcan
                                    <li class="nav-item"><a href="{{ route('result-sends.index') }}" class="nav-link">Sending History</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                    @endfeature

                    @can('Manage parent contacts')
                        <li class="nav-item">
                            <a href="{{ route('parent-contacts.index') }}" class="nav-link">
                                <i class="ri-contacts-book-2-line"></i> <span>Parent Contacts</span>
                            </a>
                        </li>
                    @endcan

                    @can('Manage parent accounts')
                        <li class="nav-item">
                            <a href="{{ route('parent-accounts.index') }}" class="nav-link">
                                <i class="ri-parent-line"></i> <span>Parent Portal Accounts</span>
                            </a>
                        </li>
                    @endcan

                    @can('Manage notification settings')
                        <li class="nav-item">
                            <a href="{{ route('notices.automations') }}" class="nav-link">
                                <i class="ri-robot-2-line"></i> <span>Automatic Messages</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('notices.settings') }}" class="nav-link">
                                <i class="ri-settings-3-line"></i> <span>Notification Settings</span>
                            </a>
                        </li>
                    @endcan

                    {{-- SCHOOL BASIC SETTINGS --}}
                    @if(auth()->user()->can('View schoolinformation') || auth()->user()->can('View session') || auth()->user()->can('View term') || auth()->user()->can('View schoolhouse') || auth()->user()->can('View school-arm') || auth()->user()->can('View class-category') || auth()->user()->can('View school-class') || auth()->user()->can('View class-teacher') || auth()->user()->can('View subjects') || auth()->user()->can('View subject-teacher') || auth()->user()->can('View subject-class') || auth()->user()->can('View compulsory-subject') || auth()->user()->can('View principals-comment') || auth()->user()->can('View school-bills') || auth()->user()->can('View school-bill-for-term-session'))
                        <li class="menu-title"><i class="ri-more-fill"></i> <span>SCHOOL BASIC SETTINGS</span></li>
                    @endif

                    @can('View schoolinformation')
                        <li class="nav-item">
                            <a href="#sidebarSchoolInfo" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSchoolInfo">
                                <i class="ph-file-text"></i> <span>School Information</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSchoolInfo">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('school-information.index') }}" class="nav-link">School Information</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan

                    @if(auth()->user()->can('View session') || auth()->user()->can('View term') || auth()->user()->can('View schoolhouse'))
                        <li class="nav-item">
                            <a href="#sidebarSession" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSession">
                                <i class="ph-file-text"></i> <span>Session Term & House</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSession">
                                <ul class="nav nav-sm flex-column">
                                    @can('View session')     <li class="nav-item"><a href="{{ route('session.index') }}"    class="nav-link">School Session</a></li> @endcan
                                    @can('View term')        <li class="nav-item"><a href="{{ route('term.index') }}"       class="nav-link">School Term</a></li>    @endcan
                                    @can('View schoolhouse') <li class="nav-item"><a href="{{ route('schoolhouse.index') }}" class="nav-link">School House</a></li> @endcan
                                </ul>
                            </div>
                        </li>
                    @endif

                    {{-- HOUSES, CLUBS & SPORTS --}}
                    @if(auth()->user()->can('View schoolhouse') || auth()->user()->can('Update schoolhouse') || auth()->user()->can('Award house points') || auth()->user()->can('View club') || auth()->user()->can('Update club') || auth()->user()->can('View sport') || auth()->user()->can('Update sport'))
                        <li class="nav-item">
                            <a href="#sidebarActivities" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarActivities">
                                <i class="ri-trophy-line"></i> <span>Houses, Clubs &amp; Sports</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarActivities">
                                <ul class="nav nav-sm flex-column">
                                    @canany(['View schoolhouse', 'Update schoolhouse', 'Award house points'])<li class="nav-item"><a href="{{ route('houses.index') }}" class="nav-link">Houses &amp; Points</a></li>@endcanany
                                    @canany(['View club', 'Update club'])<li class="nav-item"><a href="{{ route('activities.index', 'club') }}" class="nav-link">Clubs &amp; Members</a></li>@endcanany
                                    @canany(['View sport', 'Update sport'])<li class="nav-item"><a href="{{ route('activities.index', 'sport') }}" class="nav-link">Sports &amp; Members</a></li>@endcanany
                                    @canany(['Update club', 'Update sport'])<li class="nav-item"><a href="{{ route('activities.student') }}" class="nav-link">By Student</a></li>@endcanany
                                    @can('View club')<li class="nav-item"><a href="{{ route('club.index') }}" class="nav-link">Create Clubs</a></li>@endcan
                                    @can('View sport')<li class="nav-item"><a href="{{ route('sport.index') }}" class="nav-link">Create Sports</a></li>@endcan
                                </ul>
                            </div>
                        </li>
                    @endif

                    @if(auth()->user()->can('View school-arm') || auth()->user()->can('View class-category') || auth()->user()->can('View school-class') || auth()->user()->can('View class-teacher'))
                        <li class="nav-item">
                            <a href="#sidebarClassessettings" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarClassessettings">
                                <i class="ph-file-text"></i> <span>Classes</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarClassessettings">
                                <ul class="nav nav-sm flex-column">
                                    @can('View school-arm')     <li class="nav-item"><a href="{{ route('schoolarm.index') }}"        class="nav-link">Class Arm</a></li>      @endcan
                                    @can('View class-category') <li class="nav-item"><a href="{{ route('classcategories.index') }}"  class="nav-link">Class Category</a></li> @endcan
                                    @can('View school-class')   <li class="nav-item"><a href="{{ route('schoolclass.index') }}"      class="nav-link">Class Name</a></li>     @endcan
                                    @can('View class-teacher')  <li class="nav-item"><a href="{{ route('classteacher.index') }}"     class="nav-link">Class Teacher</a></li>  @endcan
                                </ul>
                            </div>
                        </li>
                    @endif

                    @if(auth()->user()->can('View subjects') || auth()->user()->can('View subject-teacher') || auth()->user()->can('View subject-class') || auth()->user()->can('View compulsory-subject'))
                        <li class="nav-item">
                            <a href="#sidebarSub" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSub">
                                <i class="ph-file-text"></i> <span>Subject</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSub">
                                <ul class="nav nav-sm flex-column">
                                    @can('View subjects')           <li class="nav-item"><a href="{{ route('subject.index') }}"               class="nav-link">Subject</a></li>                          @endcan
                                    @can('View subject-teacher')    <li class="nav-item"><a href="{{ route('subjectteacher.index') }}"         class="nav-link">Assign Subject Teacher</a></li>           @endcan
                                    @can('View subject-class')      <li class="nav-item"><a href="{{ route('subjectclass.index') }}"           class="nav-link">Assign Class Subject</a></li>             @endcan
                                    @can('View compulsory-subject') <li class="nav-item"><a href="{{ route('compulsorysubjectclass.index') }}" class="nav-link">Assign Compulsory Subject to classes</a></li> @endcan
                                </ul>
                            </div>
                        </li>
                    @endif

                    {{-- ATTENDANCE ADMIN --}}
                    @if(auth()->user()->can('View attendance-settings') || auth()->user()->can('View attendance-holidays') || 
                        auth()->user()->can('View attendance-school-report') || auth()->user()->can('View device-mappings') || 
                        auth()->user()->can('View staff-attendance'))
                        <li class="menu-title"><i class="ri-more-fill"></i> <span>ATTENDANCE ADMIN</span></li>
                        <li class="nav-item">
                            <a href="#sidebarAttendanceAdmin" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAttendanceAdmin">
                                <i class="ph-calendar-check"></i> <span>Attendance Admin</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarAttendanceAdmin">
                                <ul class="nav nav-sm flex-column">
                                    @can('View attendance-settings')
                                        <li class="nav-item"><a href="{{ route('attendance.settings') }}" class="nav-link">Term Settings</a></li>
                                    @endcan
                                    @can('View attendance-holidays')
                                        <li class="nav-item"><a href="{{ route('attendance.holidays') }}" class="nav-link">Holidays & Breaks</a></li>
                                    @endcan
                                    @can('View attendance-school-report')
                                        <li class="nav-item"><a href="{{ route('attendance.school-report') }}" class="nav-link">School Report</a></li>
                                    @endcan
                                    @can('View device-mappings')
                                        <li class="nav-item"><a href="{{ route('device-mappings.index') }}" class="nav-link">Device Mappings</a></li>
                                    @endcan
                                    @can('View staff-attendance')
                                        <li class="nav-item"><a href="{{ route('staff-attendance.index') }}" class="nav-link">Staff Attendance</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    @endif

                    @can('View principals-comment')
                        <li class="nav-item">
                            <a href="#sidebarPrincipal" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPrincipal">
                                <i class="ph-file-text"></i> <span>Principal's Comments</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarPrincipal">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('principalscomment.index') }}" class="nav-link">Assign Staff</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan

                    @can('View subjects')
                        <li class="nav-item">
                            <a href="#sidebarSubjectvetting" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSubjectvetting">
                                <i class="ph-file-text"></i> <span>Terminal Subject Vettings</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarSubjectvetting">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('subjectvetting.index') }}" class="nav-link">Assign Subjects to Staff</a></li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a href="#mocksidebarSubjectvetting" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="mocksidebarSubjectvetting">
                                <i class="ph-file-text"></i> <span>Mock Subject Vettings</span>
                            </a>
                            <div class="collapse menu-dropdown" id="mocksidebarSubjectvetting">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('mocksubjectvetting.index') }}" class="nav-link">Assign Subjects to Staff</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan

                    @if(auth()->user()->can('View school-bills') || auth()->user()->can('View school-bill-for-term-session'))
                        <li class="nav-item">
                            <a href="#sidebarBills" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarBills">
                                <i class="ph-file-text"></i> <span>School Bills</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarBills">
                                <ul class="nav nav-sm flex-column">
                                    @can('View school-bills')                 <li class="nav-item"><a href="{{ route('schoolbill.index') }}"            class="nav-link">Bills</a></li>       @endcan
                                    @can('View school-bill-for-term-session') <li class="nav-item"><a href="{{ route('schoolbilltermsession.index') }}" class="nav-link">Apply Bills</a></li> @endcan
                                </ul>
                            </div>
                        </li>
                    @endif

                    {{-- ADMIN TOOLS --}}
                    @can('View admin-score-entry')
                        <li class="menu-title"><i class="ri-more-fill"></i> <span>ADMIN TOOLS</span></li>
                        <li class="nav-item">
                            <a href="#sidebarAdminTools" class="nav-link menu-link collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAdminTools">
                                <i class="ph-wrench"></i> <span>Score Management</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarAdminTools">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item"><a href="{{ route('admin.score-entry.index') }}" class="nav-link">Admin Score Entry</a></li>
                                    <li class="nav-item"><a href="{{ route('admin.score-entry.lock-management') }}" class="nav-link">Lock Management</a></li>
                                    <li class="nav-item"><a href="{{ route('admin.score-entry.student-result-manager') }}" class="nav-link">Student Result Manager</a></li>
                                </ul>
                            </div>
                        </li>
                    @endcan

                </ul>
            </div>
        </div><!-- /scrollbar -->

        <!-- ===== SIDEBAR LOGOUT FOOTER ===== -->
        @auth
        <div class="sidebar-footer">
            @php
                $sidebarUser = Auth::user();
                $sidebarIsStudent = $sidebarUser->hasRole('student');
                $sidebarSrc = null;
                if ($sidebarIsStudent) {
                    $stu = \App\Models\Student::find($sidebarUser->student_id);
                    if ($stu?->picture) {
                        $bn = basename($stu->picture);
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('student_avatars/' . $bn))
                            $sidebarSrc = asset('storage/student_avatars/' . $bn);
                    }
                } else {
                    if ($sidebarUser->avatar) {
                        $bn = basename($sidebarUser->avatar);
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('staff_avatars/' . $bn))
                            $sidebarSrc = asset('storage/staff_avatars/' . $bn);
                    }
                }
                $sidebarInitials = collect(explode(' ', $sidebarUser->name))
                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                    ->take(2)->implode('');
            @endphp

            <div class="sidebar-footer-user">
                @if($sidebarSrc)
                    <img src="{{ $sidebarSrc }}" alt="{{ $sidebarUser->name }}">
                @else
                    <span class="sidebar-footer-avatar-initials">{{ $sidebarInitials }}</span>
                @endif
                <div class="sidebar-footer-user-info">
                    <div class="sidebar-footer-user-name">{{ $sidebarUser->name }}</div>
                    <div class="sidebar-footer-user-role">{{ $sidebarUser->roles->first()->name ?? 'User' }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form">
                @csrf
                <button type="submit" class="sidebar-logout-btn">
                    <i class="mdi mdi-logout"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
        @endauth

        <div class="sidebar-background"></div>
    </div><!-- /app-menu -->

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
                        use App\Models\Student;
                        use Illuminate\Support\Facades\Storage;
                        use Illuminate\Support\Facades\Auth;

                        $userdata  = Auth::user();
                        $isStudent = $userdata->hasRole('student');
                        $fullName  = $userdata->name ?? 'User';
                        $initials  = collect(explode(' ', $fullName))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');
                        $srcPath   = null;

                        if ($isStudent) {
                            $student        = Student::where('id', $userdata->student_id)->first();
                            $studentPicture = $student?->picture;
                            if ($studentPicture) {
                                $basename = basename($studentPicture);
                                if (Storage::disk('public')->exists('student_avatars/' . $basename))
                                    $srcPath = asset('storage/student_avatars/' . $basename);
                            }
                        } else {
                            if ($userdata->avatar) {
                                $basename = basename($userdata->avatar);
                                if (Storage::disk('public')->exists('staff_avatars/' . $basename))
                                    $srcPath = asset('storage/staff_avatars/' . $basename);
                            }
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
                                                'admin'     => ['bg'=>'#405189','light'=>'#eef2ff'],
                                                'teacher'   => ['bg'=>'#0a9396','light'=>'#e0f2fe'],
                                                'student'   => ['bg'=>'#e76f51','light'=>'#fff0ed'],
                                                'bursar'    => ['bg'=>'#2a9d8f','light'=>'#e6f7f5'],
                                                'principal' => ['bg'=>'#6a0572','light'=>'#f3e8ff'],
                                                'parent'    => ['bg'=>'#e9c46a','light'=>'#fefce8'],
                                                'staff'     => ['bg'=>'#457b9d','light'=>'#e8f0fe'],
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
                            @if(!$isStudent)<a class="dropdown-item" href="{{ route('users.overview', $userdata->id) }}"><i class="mdi mdi-account-circle me-2"></i>My Profile</a>@endif
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
                    {{ now()->year }} © {{ $schoolInfo->school_name ?? 'ViteSchools' }}
                    <span class="text-muted">· Powered by
                        <a href="https://viteschools.ng" target="_blank" rel="noopener noreferrer" class="fw-semibold">ViteSchools.ng</a>
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
                <div style="margin-top:16px;font-size:12px;opacity:0.4;">Popular: Students, Classes, Payments, Exams</div>
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

        /* ── Students ── */
        {title:'All Students',                          url:'{{ route("student.index") }}',                                  icon:'mdi-school',                  category:'Students',            keywords:['pupils','learners','students','list']},
        {title:'Batch Student Registration',            url:'{{ route("studentbatchindex") }}',                              icon:'mdi-account-multiple-plus',   category:'Students',            keywords:['bulk','import','upload','register','batch']},
        {title:'ID Card Generator',                     url:'{{ route("student-id-cards.index") }}',                         icon:'mdi-card-account-details',    category:'Students',            keywords:['id','card','identity','print']},

        /* ── Parents ── */
        {title:'All Parents',                           url:'{{ route("parent.index") }}',                                   icon:'mdi-account-group',           category:'Parents',             keywords:['guardian','parent','family','mother','father']},

        /* ── Student Portal ── */
        {title:'My Learning',                             url:'{{ route("lms.learn.index") }}',                              icon:'mdi-school-outline',          category:'Student Portal',      keywords:['course','lesson','learn','elearning','lms','video']},
        {title:'Courses (E-Learning)',                   url:'{{ route("lms.courses.index") }}',                            icon:'mdi-book-open-page-variant',  category:'Academics',           keywords:['course','lms','elearning','lesson','quiz','assignment','teach']},
        {title:'My Assessments',                        url:'{{ route("assessments") }}',                                    icon:'mdi-clipboard-list',          category:'Student Portal',      keywords:['test','quiz','cbt','assessment']},
        {title:'Pay School Fees',                       url:'{{ route("student.fees.pay") }}',                           icon:'mdi-credit-card-outline',     category:'Student Portal',      keywords:['pay','fees','paystack','online','card','transfer']},
        {title:'My Payments',                           url:'{{ route("student.payments") }}',                               icon:'mdi-cash-multiple',           category:'Student Portal',      keywords:['fees','invoice','payment','student']},

        /* ── Subject Registration ── */
        {title:'Student Subject Registration',          url:'{{ route("subjectoperation.index") }}',                         icon:'mdi-book-plus',               category:'Subject Registration',keywords:['register','subject','enroll','course']},

        /* ── Exams & CBT ── */
        {title:'All Examinations',                      url:'{{ route("exams.index") }}',                                    icon:'mdi-clipboard-text',          category:'Exams & CBT',         keywords:['exam','test','examination']},
        {title:'Questions Management',                  url:'{{ route("questions.all") }}',                                  icon:'mdi-help-circle',             category:'Exams & CBT',         keywords:['questions','bank','quiz']},
        {title:'CBT Exercise',                          url:'{{ route("cbt.index") }}',                                      icon:'mdi-monitor',                 category:'Exams & CBT',         keywords:['cbt','computer','online','test']},

        /* ── Timetable ── */
        {title:'Admin Timetable',                       url:'{{ route("timetable.index") }}',                                icon:'mdi-table-clock',             category:'Timetable',           keywords:['schedule','timetable','period','admin']},
        {title:'My Timetable',                          url:'{{ route("timetable.teacher") }}',                              icon:'mdi-calendar-clock',          category:'Timetable',           keywords:['schedule','timetable','teacher','my']},
        {title:'Room Management',                       url:'{{ route("rooms.index") }}',                                    icon:'mdi-door',                    category:'Timetable',           keywords:['room','hall','venue','classroom']},
        {title:'Exam Timetable',                        url:'{{ route("exam-timetable.index") }}',                           icon:'mdi-calendar-text',           category:'Timetable',           keywords:['exam','schedule','timetable']},
        {title:'Holidays',                              url:'{{ route("holidays.index") }}',                                 icon:'mdi-beach',                   category:'Timetable',           keywords:['holiday','break','vacation','leave']},

        /* ── Classes & Records ── */
        {title:'My Class',                              url:'{{ route("myclass.index") }}',                                  icon:'mdi-google-classroom',        category:'Classes & Records',   keywords:['class','register','students','my']},
        {title:'My Subject',                            url:'{{ route("mysubject.index") }}',                                icon:'mdi-book-open',               category:'Classes & Records',   keywords:['subject','teach','course','my']},
        {title:'Subjects to Vet',                       url:'{{ route("mysubjectvettings.index") }}',                        icon:'mdi-check-decagram',          category:'Classes & Records',   keywords:['vet','verify','approve','subject']},
        {title:'Mock Subjects to Vet',                  url:'{{ route("mymocksubjectvettings.index") }}',                    icon:'mdi-check-decagram',          category:'Classes & Records',   keywords:['mock','vet','verify','approve']},
        {title:'Principal\'s Comment',                  url:'{{ route("myprincipalscomment.index") }}',                      icon:'mdi-comment-text',            category:'Classes & Records',   keywords:['principal','comment','remark','report']},

        /* ── Attendance ── */
        {title:'Mark Attendance',                       url:'{{ route("attendance.my-classes") }}',                          icon:'mdi-clipboard-check',         category:'Attendance',          keywords:['attendance','present','absent','mark']},
        {title:'Attendance Term Settings',              url:'{{ route("attendance.settings") }}',                            icon:'mdi-cog',                     category:'Attendance Admin',    keywords:['attendance','settings','term','configure']},
        {title:'Attendance Holidays & Breaks',          url:'{{ route("attendance.holidays") }}',                            icon:'mdi-calendar-remove',         category:'Attendance Admin',    keywords:['attendance','holiday','break']},
        {title:'Attendance School Report',              url:'{{ route("attendance.school-report") }}',                       icon:'mdi-chart-bar',               category:'Attendance Admin',    keywords:['attendance','report','school','summary']},
        {title:'Device Mappings',                       url:'{{ route("device-mappings.index") }}',                          icon:'mdi-devices',                  category:'Attendance Admin',    keywords:['device','map','attendance','hardware','biometric']},
        {title:'Staff Attendance',                      url:'{{ route("staff-attendance.index") }}',                        icon:'mdi-account-clock',            category:'Attendance Admin',    keywords:['staff','attendance','clock','in','out','time']},

        /* ── Records & Results ── */
        {title:'Terminal Records',                      url:'{{ route("myresultroom.index") }}',                             icon:'mdi-file-chart',              category:'Records & Results',   keywords:['result','terminal','record','scores']},
        {title:'Terminal Result Reports',               url:'{{ route("studentreports.index") }}',                           icon:'mdi-file-document',           category:'Records & Results',   keywords:['report','result','terminal','card']},
        {title:'Terminal Result Broadsheet',            url:'{{ route("broadsheet.index") }}',                               icon:'mdi-table-large',             category:'Records & Results',   keywords:['broadsheet','class','result','terminal']},
        {title:'Broadsheet Web View',                   url:'{{ route("broadsheet.web-view") }}',                            icon:'mdi-table-eye',               category:'Records & Results',   keywords:['broadsheet','view','class','result','web']},
        {title:'Mock Result Reports',                   url:'{{ route("studentmockreports.index") }}',                       icon:'mdi-file-document-outline',   category:'Records & Results',   keywords:['mock','result','report']},
        {title:'Admin Score Entry',                     url:'{{ route("admin.score-entry.index") }}',                        icon:'mdi-clipboard-edit',          category:'Records & Results',   keywords:['score','entry','admin','marks','results']},

        /* ── Admin Tools (Score Management) ── */
        {title:'Score Lock Management',                 url:'{{ route("admin.score-entry.lock-management") }}',              icon:'mdi-lock-open-check',         category:'Admin Tools',         keywords:['lock','unlock','scoresheet','security','admin']},
        {title:'Student Result Manager',                url:'{{ route("admin.score-entry.student-result-manager") }}',       icon:'mdi-account-edit',            category:'Admin Tools',         keywords:['student','result','edit','admin','manage','scores']},

        /* ── Transcripts ── */
        {title:'Generate Transcript',                   url:'{{ route("transcript.index") }}',                               icon:'mdi-file-account',            category:'Transcripts',         keywords:['transcript','certificate','generate','print']},

        /* ── Promotions ── */
        {title:'Student Promotion',                     url:'{{ route("promotions.index") }}',                               icon:'mdi-arrow-up-circle',         category:'Promotions',          keywords:['promote','promotion','class','next','move']},
        {title:'Promotion Settings',                    url:'{{ route("promotion-settings.index") }}',                       icon:'mdi-tune',                    category:'Promotions',          keywords:['promotion','settings','rules','criteria','configure']},
        {title:'Promotion Rule Templates',              url:'{{ route("promotion.templates.index") }}',                      icon:'mdi-content-copy',            category:'Promotions',          keywords:['promotion','template','rules','preset']},

        /* ── Finance ── */
        {title:'Student Bill',                          url:'{{ route("schoolpayment.index") }}',                            icon:'mdi-receipt',                 category:'Finance',             keywords:['bill','fees','invoice','payment','student']},
        {title:'Payment Portal',                        url:'{{ route("payment.index") }}',                                  icon:'mdi-wallet',                  category:'Finance',             keywords:['pay','portal','fees','transaction']},
        {title:'Online Payments',                       url:'{{ route("online-fees.index") }}',                           icon:'mdi-web',                     category:'Finance',             keywords:['online','pay','paystack','flutterwave','internet']},
        {title:'All Scholarships',                      url:'{{ route("admin.scholarship.index") }}',                        icon:'mdi-medal',                   category:'Finance',             keywords:['scholarship','award','bursary','fund']},
        {title:'Create Scholarship',                    url:'{{ route("admin.scholarship.create") }}',                       icon:'mdi-medal-outline',           category:'Finance',             keywords:['scholarship','create','new','add']},
        {title:'Scholarship Assignments',               url:'{{ route("admin.scholarship.assignments") }}',                  icon:'mdi-account-star',            category:'Finance',             keywords:['scholarship','assign','student']},
        {title:'Scholarship Applications',              url:'{{ route("admin.scholarship.applications") }}',                 icon:'mdi-file-check',              category:'Finance',             keywords:['scholarship','application','apply']},
        {title:'All Discounts',                         url:'{{ route("admin.discount.index") }}',                           icon:'mdi-tag-multiple',            category:'Finance',             keywords:['discount','reduction','fee','concession']},
        {title:'Create Discount',                       url:'{{ route("admin.discount.create") }}',                          icon:'mdi-tag-plus',                category:'Finance',             keywords:['discount','create','new','add']},
        {title:'Discount Assignments',                  url:'{{ route("admin.discount.assignments") }}',                     icon:'mdi-account-tag',             category:'Finance',             keywords:['discount','assign','student']},
        {title:'All Family Groups (Sibling)',           url:'{{ route("sibling.index") }}',                                  icon:'mdi-account-group',           category:'Finance',             keywords:['sibling','family','group','discount']},
        {title:'Create Family Group',                   url:'{{ route("sibling.create") }}',                                 icon:'mdi-account-multiple-plus',   category:'Finance',             keywords:['sibling','family','group','create']},
        @if(Route::has("maintenance.settings")){title:'Maintenance Mode',                      url:'{{ route("maintenance.settings") }}',                            icon:'mdi-wrench',                  category:'System',              keywords:['maintenance','offline','downtime','lock','close portal']},@endif
        @if(Route::has("feature-flags.index")){title:'Module Access',                         url:'{{ route("feature-flags.index") }}',                             icon:'mdi-toggle-switch',           category:'System',              keywords:['module','feature','flag','remote','enable','disable','sidebar']},@endif
        @if(Route::has("admin.backups.index")){title:'Database Backups',                     url:'{{ route("admin.backups.index") }}',                            icon:'mdi-database',                category:'System',              keywords:['backup','database','sql','dump','restore','schedule','export']},@endif
        @if(Route::has("certificates.index")){title:'Certificates',                          url:'{{ route("certificates.index") }}',                              icon:'mdi-certificate',             category:'System',              keywords:['certificate','award','graduation','qr','verify','print','template']},@endif
        @if(Route::has("certificates.index")){title:'Testimonials',                          url:'{{ route("certificates.index", ["kind"=>"testimonial"]) }}',      icon:'mdi-file-account',            category:'System',              keywords:['testimonial','leaving','character','reference','conduct','qr']},@endif
        @if(Route::has("student-leave.approvals")){title:'Student Leave Approvals',              url:'{{ route("student-leave.approvals") }}',                         icon:'mdi-account-check',           category:'Staff',               keywords:['student','leave','absence','approve','recommend','permission']},@endif
        @if(Route::has("student-leave.records")){title:'Student Leave Records',                 url:'{{ route("student-leave.records") }}',                           icon:'mdi-file-document-multiple',  category:'Staff',               keywords:['student','leave','absence','records','export','history']},@endif
        @if(Route::has("leave.board")){title:'Who\'s Away (Leave Board)',                url:'{{ route("leave.board") }}',                                     icon:'mdi-calendar-account',        category:'Staff',               keywords:['leave','away','absent','calendar','board','staff','student','today']},@endif
        @if(Route::has("leave.balances")){title:'Leave Balances',                        url:'{{ route("leave.balances") }}',                                  icon:'mdi-scale-balance',           category:'Staff',               keywords:['leave','balance','entitlement','remaining','carry','over','allowance']},@endif
        @if(Route::has("calendar.index")){title:'School Calendar',                       url:'{{ route("calendar.index") }}',                                  icon:'mdi-calendar-month',          category:'General',             keywords:['calendar','event','activity','term','holiday','reminder','schedule']},@endif
        {title:'Payment Gateways',                      url:'{{ route("admin.payment-gateways.index") }}',                   icon:'mdi-credit-card',             category:'Finance',             keywords:['gateway','paystack','flutterwave','online','configure']},

        /* ── Accounting & Reports ── */
        @if(Route::has("finance.audit.dashboard")){title:'Financial Audit',                       url:'{{ route("finance.audit.dashboard") }}',                        icon:'mdi-shield-search',           category:'Accounting',          keywords:['audit','financial','exception','trail','fraud','review','accountant']},@endif
        {title:'Balance Sheet',                         url:'{{ route("reports.financial.balance-sheet") }}',                icon:'mdi-scale-balance',           category:'Accounting',          keywords:['balance','sheet','financial','report']},
        {title:'Income Statement',                      url:'{{ route("reports.financial.income-statement") }}',             icon:'mdi-chart-line',              category:'Accounting',          keywords:['income','profit','loss','statement','p&l']},
        {title:'Trial Balance',                         url:'{{ route("reports.financial.trial-balance") }}',                icon:'mdi-calculator',              category:'Accounting',          keywords:['trial','balance','ledger','accounts']},
        {title:'Cash Flow',                             url:'{{ route("reports.financial.cash-flow") }}',                    icon:'mdi-cash-sync',               category:'Accounting',          keywords:['cash','flow','liquidity','report']},
        @can('View notices')
        {title:'School Notices',                        url:'{{ route("notices.index") }}',                                  icon:'mdi-bullhorn',                category:'Communication',       keywords:['notice','sms','whatsapp','email','parents','announcement','exam','holiday','midterm','ca test']},
        @endcan
        @can('Manage parent contacts')
        {title:'Parent Contacts',                       url:'{{ route("parent-contacts.index") }}',                          icon:'mdi-card-account-phone',      category:'Communication',       keywords:['parent','phone','email','contact','whatsapp','csv','import']},
        @endcan
        @can('Manage notification settings')
        {title:'Automatic Messages',                    url:'{{ route("notices.automations") }}',                            icon:'mdi-robot',                   category:'Communication',       keywords:['absence','absent','alert','fee reminder','birthday','automatic']},
        @endcan
        @can('Create result-sends')
        {title:'Send Results to Parents',               url:'{{ route("result-sends.create") }}',                            icon:'mdi-email-send',              category:'Communication',       keywords:['result','report card','send','parents','whatsapp','email','sms']},
        @endcan
        @can('Create notices')
        {title:'New Notice',                            url:'{{ route("notices.create") }}',                                 icon:'mdi-bullhorn-outline',        category:'Communication',       keywords:['send','sms','broadcast','parents','notify']},
        @endcan
        {title:'Student Debtors List',                  url:'{{ route("reports.financial.debtors") }}',                      icon:'mdi-account-alert',           category:'Accounting',          keywords:['debtor','outstanding','arrears','owe','unpaid']},
        @can('View instalment-plans')
        {title:'Instalment Plans',                      url:'{{ route("instalment-plans.index") }}',                         icon:'mdi-calendar-clock',          category:'Accounting',          keywords:['instalment','installment','plan','part','payment','schedule']},
        @endcan
        @can('View result-access')
        {title:'Result Access Control',                 url:'{{ route("result-access.index") }}',                            icon:'mdi-lock-open-check',         category:'Accounting',          keywords:['result','access','owing','debt','block','withhold','exception']},
        @endcan
        {title:'Collection Summary',                    url:'{{ route("reports.financial.collection-summary") }}',           icon:'mdi-cash-register',           category:'Accounting',          keywords:['collection','summary','receipts','income']},
        {title:'Class Payment Analysis',                url:'{{ route("reports.analysis.index") }}',                         icon:'mdi-chart-bar',               category:'Accounting',          keywords:['analysis','class','payment','report']},
        {title:'School-Wide Payment Analysis',          url:'{{ route("reports.analysis.school-wide") }}',                   icon:'mdi-chart-donut',             category:'Accounting',          keywords:['analysis','school','wide','payment','report']},

        /* ── Payroll ── */
        {title:'Payroll Periods',                       url:'{{ route("payroll.periods") }}',                                icon:'mdi-calendar-clock',          category:'Payroll',             keywords:['payroll','period','month','salary']},
        {title:'Payroll Summary',                       url:'{{ route("payroll.summary") }}',                                icon:'mdi-cash-multiple',           category:'Payroll',             keywords:['payroll','summary','total','staff']},
        {title:'Statutory Report',                      url:'{{ route("payroll.statutory") }}',                              icon:'mdi-file-certificate',        category:'Payroll',             keywords:['statutory','tax','pension','nhis','paye']},
        {title:'Salary Structures',                     url:'{{ route("payroll.salary-structures") }}',                      icon:'mdi-bank',                    category:'Payroll',             keywords:['salary','structure','grade','pay']},
        @if(Route::has("payroll.payouts")){title:'Salary Payments',                       url:'{{ route("payroll.payouts") }}',                                icon:'mdi-bank-transfer',           category:'Payroll',             keywords:['pay','transfer','paystack','salary','payout']},@endif
        @if(Route::has("payroll.loans")){title:'Loans & Advances',                      url:'{{ route("payroll.loans") }}',                                  icon:'mdi-hand-coin',               category:'Payroll',             keywords:['loan','advance','staff']},@endif
        @if(Route::has("payroll.coop")){title:'Staff Cooperative',                     url:'{{ route("payroll.coop") }}',                                   icon:'mdi-piggy-bank',              category:'Payroll',             keywords:['cooperative','savings','coop']},@endif
        @if(Route::has("finance.expenses")){title:'Expenses',                              url:'{{ route("finance.expenses") }}',                               icon:'mdi-receipt',                 category:'Finance',             keywords:['expense','voucher','spend']},@endif
        @if(Route::has("finance.budgets")){title:'Budgets',                               url:'{{ route("finance.budgets") }}',                                icon:'mdi-scale-balance',           category:'Finance',             keywords:['budget','plan']},@endif
        @if(Route::has("finance.assets")){title:'Fixed Assets',                          url:'{{ route("finance.assets") }}',                                 icon:'mdi-archive',                 category:'Finance',             keywords:['asset','depreciation','register']},@endif
        @if(Route::has("accounting.dashboard")){title:'Accounting',                            url:'{{ route("accounting.dashboard") }}',                           icon:'mdi-book-open-variant',       category:'Finance',             keywords:['ledger','journal','accounting','balance sheet']},@endif

        /* ── Staff Payments ── */
        {title:'All Staff Payments',                    url:'{{ route("staff.payments.index") }}',                           icon:'mdi-cash-check',              category:'Staff Payments',      keywords:['staff','payment','salary','payslip']},
        {title:'My Staff Payments',                     url:'{{ route("staff.payments.dashboard") }}',                       icon:'mdi-wallet-outline',          category:'Staff Payments',      keywords:['my','payment','salary','payslip']},

        /* ── School Settings ── */
        {title:'School Information',                    url:'{{ route("school-information.index") }}',                       icon:'mdi-domain',                  category:'School Settings',     keywords:['school','info','name','address','logo']},
        {title:'School Session',                        url:'{{ route("session.index") }}',                                  icon:'mdi-calendar-range',          category:'School Settings',     keywords:['session','year','academic']},
        {title:'School Term',                           url:'{{ route("term.index") }}',                                     icon:'mdi-calendar',                category:'School Settings',     keywords:['term','semester','period']},
        {title:'School House',                          url:'{{ route("schoolhouse.index") }}',                              icon:'mdi-home-group',              category:'School Settings',     keywords:['house','group','dormitory']},
        {title:'Class Arm',                             url:'{{ route("schoolarm.index") }}',                                icon:'mdi-table-chair',             category:'School Settings',     keywords:['arm','stream','class','division']},
        {title:'Class Category',                        url:'{{ route("classcategories.index") }}',                          icon:'mdi-format-list-bulleted',    category:'School Settings',     keywords:['category','class','type','level']},
        {title:'Class Name',                            url:'{{ route("schoolclass.index") }}',                              icon:'mdi-google-classroom',        category:'School Settings',     keywords:['class','name','jss','sss','primary']},
        {title:'Class Teacher',                         url:'{{ route("classteacher.index") }}',                             icon:'mdi-human-male-board',        category:'School Settings',     keywords:['class','teacher','form','tutor']},

        /* ── Subjects ── */
        {title:'Subjects',                              url:'{{ route("subject.index") }}',                                  icon:'mdi-book-open-variant',       category:'Subjects',            keywords:['subject','course','topic','list']},
        {title:'Assign Subject Teacher',                url:'{{ route("subjectteacher.index") }}',                           icon:'mdi-account-tie',             category:'Subjects',            keywords:['assign','subject','teacher','staff']},
        {title:'Assign Class Subject',                  url:'{{ route("subjectclass.index") }}',                             icon:'mdi-book-plus',               category:'Subjects',            keywords:['assign','class','subject','course']},
        {title:'Assign Compulsory Subjects',            url:'{{ route("compulsorysubjectclass.index") }}',                   icon:'mdi-book-lock',               category:'Subjects',            keywords:['compulsory','subject','class','assign','mandatory']},

        /* ── Vettings & Comments ── */
        {title:'Terminal Subject Vettings',             url:'{{ route("subjectvetting.index") }}',                           icon:'mdi-check-decagram',          category:'Vettings',            keywords:['vet','verify','terminal','subject','approve']},
        {title:'Mock Subject Vettings',                 url:'{{ route("mocksubjectvetting.index") }}',                       icon:'mdi-check-decagram',          category:'Vettings',            keywords:['mock','vet','verify','subject','approve']},
        {title:'Principal\'s Comments (Admin)',         url:'{{ route("principalscomment.index") }}',                        icon:'mdi-comment-text-multiple',   category:'Vettings',            keywords:['principal','comment','remark','assign','staff']},

        /* ── School Bills ── */
        {title:'School Bills',                          url:'{{ route("schoolbill.index") }}',                               icon:'mdi-file-document-outline',   category:'School Bills',        keywords:['bill','fee','levy','school','charge']},
        {title:'Apply Bills to Term/Session',           url:'{{ route("schoolbilltermsession.index") }}',                    icon:'mdi-file-check',              category:'School Bills',        keywords:['apply','bill','term','session','assign']},
    ];

    /* ── category colours ── */
    var CAT_COLORS = {
        'Dashboards':'#4f8ef7','Users & Privileges':'#405189','Students':'#e76f51','My Account':'#2a9d8f',
        'School Settings':'#6a0572','Subjects':'#e9c46a','Classes & Records':'#0a9396','Records & Results':'#457b9d',
        'Promotions':'#2a9d8f','Finance':'#10b981','Payroll':'#e76f51','Staff Payments':'#e76f51',
        'Exams & CBT':'#f4a261','Timetable':'#4f8ef7','Attendance':'#e9c46a','Attendance Admin':'#e76f51',
        'Accounting':'#10b981','Transcripts':'#457b9d','Admin Tools':'#ef4444','Parents':'#6c757d',
        'Student Portal':'#20c997','Subject Registration':'#a8dadc','Vettings':'#e63946','School Bills':'#457b9d'
    };

    /* ── popular / suggestion chips ── */
    var POPULAR = [
        {label:'Students',   q:'students'},
        {label:'Payments',   q:'payment'},
        {label:'Results',    q:'results'},
        {label:'Exams',      q:'exam'},
        {label:'Timetable',  q:'timetable'},
        {label:'Attendance', q:'attendance'},
        {label:'Promotions', q:'promotion'},
        {label:'Payroll',    q:'payroll'},
        {label:'Reports',    q:'report'},
        {label:'Broadsheet', q:'broadsheet'},
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
@if (Route::is('dashboard'))               @include('layouts.pages-assets.js.dashboard-list-js') @endif
@if (Route::is('users.*'))                 @include('layouts.pages-assets.js.users-list-js') @endif
@if (Route::is('student-id-cards.*'))      @include('layouts.pages-assets.js.idcard-list-js') @endif
@if (Route::is('student.payments.*'))      @include('layouts.pages-assets.js.studentpayment-list-js') @endif
@if (Route::is('profile.*'))               @include('layouts.pages-assets.js.users-list-js') @endif
@if (Route::is('roles.*'))                 @include('layouts.pages-assets.js.role-list-js') @endif
@if (Route::is('permissions.*'))           @include('layouts.pages-assets.js.permissions-list-js') @endif
@if (Route::is('session.*'))               @include('layouts.pages-assets.js.session-list-js') @endif
@if (Route::is('term.*'))                  @include('layouts.pages-assets.js.term-list-js') @endif
@if (Route::is('school-information.*'))    @include('layouts.pages-assets.js.schoolinformation-list-js') @endif
@if (Route::is('admin.school-info.*'))     @include('layouts.pages-assets.js.schoolinformation-list-js') @endif
@if (Route::is('schoolhouse.*'))           @include('layouts.pages-assets.js.schoolhouse-list-js') @endif
@if (Route::is('schoolarm.*'))             @include('layouts.pages-assets.js.arm-list-js') @endif
@if (Route::is('classcategories.*'))       @include('layouts.pages-assets.js.classcategory-list-js') @endif
@if (Route::is('schoolclass.*'))           @include('layouts.pages-assets.js.schoolclass-list-js') @endif
@if (Route::is('classteacher.*'))          @include('layouts.pages-assets.js.classteacher-list-js') @endif
@if (Route::is('subject.*'))               @include('layouts.pages-assets.js.subject-list-js') @endif
{{-- subjects.index is Subject Registration: it has its own scripts, and subject-list.init.js
     (Subjects page) overrides its filterData(), hijacks pagination and loads Bootstrap twice. --}}
@if (Route::is('subjects.*') && !Route::is('subjects.index')) @include('layouts.pages-assets.js.subject-list-js') @endif
@if (Route::is('subjectteacher.*'))        @include('layouts.pages-assets.js.subjectteacher-list-js') @endif
@if (Route::is('subjectclass.*'))          @include('layouts.pages-assets.js.subjectclass-list-js') @endif
@if (Route::is('schoolbill.*'))            @include('layouts.pages-assets.js.schoolbill-list-js') @endif
@if (Route::is('schoolbilltermsession.*')) @include('layouts.pages-assets.js.schoolbilltermsession-list-js') @endif
@if (Route::is('student.*'))               @include('layouts.pages-assets.js.student-list-js') @endif
@if (Route::is('studentbatchindex'))       @include('layouts.pages-assets.js.studentbatch-list-js') @endif
@if (Route::is('myclass.*'))               @include('layouts.pages-assets.js.myclass-list-js') @endif
@if (Route::is('mysubject.*'))             @include('layouts.pages-assets.js.mysubject-list-js') @endif
@if (Route::is('viewstudent'))             @include('layouts.pages-assets.js.viewstudent-list-js') @endif
@if (Route::is('studentreports.*'))        @include('layouts.pages-assets.js.studentreport-list-js') @endif
@if (Route::is('broadsheet.*'))            @include('layouts.pages-assets.js.studentreport-list-js') @endif
@if (Route::is('studentmockreports.*'))    @include('layouts.pages-assets.js.studentmockreport-list-js') @endif
@if (Route::is('subjectoperation.*'))      @include('layouts.pages-assets.js.subjectoperation-list-js') @endif
@if (Route::is('subjects.subjectinfo'))    @include('layouts.pages-assets.js.subjectinfo-list-js') @endif
@if (Route::is('myresultroom.*'))          @include('layouts.pages-assets.js.myresultroom-list-js') @endif
@if (Route::is('assessment.*'))            @include('layouts.pages-assets.js.subjectscoresheet-list-js') @endif
@if (Route::is('assessments'))             @include('layouts.pages-assets.js.studentassessment-list-js') @endif
@if (Route::is('subjectscoresheet'))       @include('layouts.pages-assets.js.subjectscoresheet-list-js') @endif
@if (Route::is('subjectscoresheet-mock.*'))@include('layouts.pages-assets.js.subjectscoresheet-mock-list-js') @endif
@if (Route::is('studentresults*'))         @include('layouts.pages-assets.js.studentresults-list-js') @endif
@if (Route::is('schoolbill*'))             @include('layouts.pages-assets.js.schoolbill-list-js') @endif
@if (Route::is('schoolpayment*'))          @include('layouts.pages-assets.js.schoolpayment-list-js') @endif
@if (Route::is('analysis*'))               @include('layouts.pages-assets.js.analysis-list-js') @endif
@if (Route::is('exams*'))                  @include('layouts.pages-assets.js.exams-list-js') @endif
@if (Route::is('questions*'))              @include('layouts.pages-assets.js.questions-list-js') @endif
@if (Route::is('cbt*'))                    @include('layouts.pages-assets.js.cbt-list-js') @endif
@if (Route::is('classbroadsheet.*'))       @include('layouts.pages-assets.js.classbroadsheet-list-js') @endif
@if (Route::is('principalscomment.*'))     @include('layouts.pages-assets.js.principalscomment-list-js') @endif
@if (Route::is('myprincipalscomment.*'))   @include('layouts.pages-assets.js.myprincipalscomment-list-js') @endif
@if (Route::is('compulsorysubjectclass.*'))@include('layouts.pages-assets.js.compulsorysubjectclass-list-js') @endif
@if (Route::is('subjectvetting.*'))        @include('layouts.pages-assets.js.subjectvetting-list-js') @endif
@if (Route::is('mocksubjectvetting.*'))    @include('layouts.pages-assets.js.mocksubjectvetting-list-js') @endif
@if (Route::is('mysubjectvettings.*'))     @include('layouts.pages-assets.js.mysubjectvettings-list-js') @endif
@if (Route::is('mymocksubjectvettings.*')) @include('layouts.pages-assets.js.mysubjectvettings-list-js') @endif
@if (Route::is('timetable.*'))             @include('layouts.pages-assets.js.timetable-list-js') @endif
@if (Route::is('rooms.*'))                 @include('layouts.pages-assets.js.rooms-list-js') @endif
@if (Route::is('promotions.*'))            @include('layouts.pages-assets.js.promotions-list-js') @endif
@if (Route::is('attendance.*'))            @include('layouts.pages-assets.js.attendance-list-js') @endif
@if (Route::is('device-mappings.*'))       @include('layouts.pages-assets.js.attendance-list-js') @endif
@if (Route::is('staff-attendance.*'))      @include('layouts.pages-assets.js.attendance-list-js') @endif
@if (Route::is('transcript.*'))            @include('layouts.pages-assets.js.attendance-list-js') @endif
@if (Route::is('admin.score-entry.*'))     @include('layouts.pages-assets.js.adminscoreentry-list-js') @endif
@if (Route::is('admin.scholarship.*') || Route::is('admin.discount.*') || Route::is('sibling.*') ||
    Route::is('payment.*') || Route::is('reports.financial.*') || Route::is('reports.analysis.*') ||
    Route::is('payroll.*') || Route::is('staff.payments.*'))
    @include('layouts.pages-assets.js.scholarship-list-js')
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