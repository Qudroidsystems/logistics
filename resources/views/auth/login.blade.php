<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title>Sign In | {{ $school->school_name ?? 'Vite-ESchool' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="school App" name="description">
    <meta content="Themesbrand" name="author">
    @php
        $schoolInfo = App\Models\SchoolInformation::getActiveSchool();
    @endphp
     <!-- App favicon - Using School Logo -->
    @if($schoolInfo && $schoolInfo->getLogoUrlAttribute())
        <link rel="shortcut icon" href="{{ $schoolInfo->getLogoUrlAttribute() }}">
        <link rel="icon" type="image/png" href="{{ $schoolInfo->getLogoUrlAttribute() }}">
        <!-- Apple Touch Icon (for iOS) -->
        <link rel="apple-touch-icon" href="{{ $schoolInfo->getLogoUrlAttribute() }}">
    @else
        <link rel="shortcut icon" href="{{ asset('theme/layouts/assets/images/favicon.ico') }}">
        <link rel="icon" type="image/png" href="{{ asset('theme/layouts/assets/images/logo-dark.png') }}">
    @endif
    <!-- Fonts css load -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link id="fontsLink" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">

    <!-- Layout config Js -->
    <script src="{{ asset('theme/layouts/assets/js/layout.js')}}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css">
    <!-- Icons Css -->
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css">
    <!-- App Css-->
    <link href="{{ asset('theme/layouts/assets/css/app.min.css')}}" rel="stylesheet" type="text/css">
    <!-- custom Css-->
    <link href="{{ asset('theme/layouts/assets/css/custom.min.css')}}" rel="stylesheet" type="text/css">

    <style>
        /* =====================================================
           APPLE OS STYLE LOGIN PAGE
           ===================================================== */

        /* Smooth page entrance animation */
        @keyframes pageFadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
            20%, 40%, 60%, 80% { transform: translateX(4px); }
        }

        @keyframes fieldPulse {
            0%, 100% { border-color: #e2e8f0; box-shadow: 0 0 0 0 rgba(79, 142, 247, 0); }
            50% { border-color: #4f8ef7; box-shadow: 0 0 0 4px rgba(79, 142, 247, 0.15); }
        }

        @keyframes successCheck {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes orbitClockwise {
            from { transform: rotate(0deg) translate(120px, 0) rotate(0deg); }
            to { transform: rotate(360deg) translate(120px, 0) rotate(-360deg); }
        }

        @keyframes orbitCounterClockwise {
            from { transform: rotate(0deg) translate(120px, 0) rotate(0deg); }
            to { transform: rotate(-360deg) translate(120px, 0) rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }

        @keyframes fadeOut {
            to { opacity: 0; transform: translateX(20px); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Page container animation */
        .auth-page-wrapper {
            animation: pageFadeInUp 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
        }

        /* Card entrance animation */
        .card {
            animation: fadeInScale 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
            border-radius: 28px !important;
            overflow: hidden;
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.98);
        }

        /* Left panel animation */
        .auth-card {
            animation: slideInLeft 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
            border-radius: 0 !important;
        }

        /* Right panel animation */
        .col-xxl-6 {
            animation: slideInRight 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
            animation-delay: 0.1s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        /* Apple-style input fields */
        .apple-input {
            border-radius: 12px !important;
            border: 1.5px solid #e2e8f0 !important;
            background: #f8fafc !important;
            transition: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1) !important;
            font-size: 16px !important;
            padding: 12px 16px !important;
        }

        .apple-input:focus {
            border-color: #4f8ef7 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(79, 142, 247, 0.1), 0 2px 8px rgba(0, 0, 0, 0.05) !important;
            outline: none !important;
        }

        .apple-input:hover {
            border-color: #cbd5e1 !important;
            background: #ffffff !important;
        }

        /* Apple-style button */
        .apple-button {
            background: #4f8ef7 !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 14px 20px !important;
            font-weight: 600 !important;
            font-size: 16px !important;
            transition: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1) !important;
            position: relative;
            overflow: hidden;
        }

        .apple-button:hover {
            background: #3b7ae3 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 142, 247, 0.35);
        }

        .apple-button:active {
            transform: translateY(1px);
        }

        /* Shake animation for error */
        .shake-field {
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        /* Field pulse on focus */
        .field-pulse {
            animation: fieldPulse 0.6s ease;
        }

        /* Label styling */
        .form-label {
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 6px;
        }

        /* Checkbox styling */
        .form-check-input {
            border-radius: 6px !important;
            border: 1.5px solid #cbd5e1 !important;
            transition: all 0.2s ease;
        }

        .form-check-input:checked {
            background-color: #4f8ef7 !important;
            border-color: #4f8ef7 !important;
        }

        /* Error message styling */
        .invalid-feedback {
            font-size: 12px;
            margin-top: 6px;
            animation: slideInRight 0.3s ease;
        }

        /* Password toggle button */
        .password-addon {
            border-radius: 0 12px 12px 0 !important;
            padding: 0 16px !important;
            transition: opacity 0.2s ease;
        }

        .password-addon:hover {
            opacity: 0.7;
        }

        /* Loading state on button */
        .apple-button.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .apple-button.loading::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            top: 50%;
            left: 50%;
            margin-left: -9px;
            margin-top: -9px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        .apple-button.loading span {
            opacity: 0;
        }

        /* Effect circles */
        .effect-circle-1,
        .effect-circle-2,
        .effect-circle-3 {
            transition: all 0.3s ease;
        }

        .effect-circle-1 {
            animation: pulse 2s infinite;
        }

        /* Auth effect main container */
        .auth-effect-main {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto;
        }

        /* Avatar orbit animations */
        .auth-user-list {
            position: absolute;
            width: 100%;
            height: 100%;
            list-style: none;
            padding: 0;
            margin: 0;
            top: 0;
            left: 0;
        }

        .auth-user-list li {
            position: absolute;
            width: 50px;
            height: 50px;
            transform-origin: center center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .auth-user-list li:nth-child(1) {
            animation: orbitClockwise 12s linear infinite;
            transform: translate(120px, 0);
        }

        .auth-user-list li:nth-child(2) {
            animation: orbitCounterClockwise 14s linear infinite;
            transform: rotate(72deg) translate(115px, 0);
        }

        .auth-user-list li:nth-child(3) {
            animation: orbitClockwise 10s linear infinite;
            transform: rotate(144deg) translate(125px, 0);
        }

        .auth-user-list li:nth-child(4) {
            animation: orbitCounterClockwise 11s linear infinite;
            transform: rotate(216deg) translate(118px, 0);
        }

        .auth-user-list li:nth-child(5) {
            animation: orbitClockwise 13s linear infinite;
            transform: rotate(288deg) translate(122px, 0);
        }

        .auth-user-list li:hover {
            animation-play-state: paused !important;
            transform: scale(1.2) !important;
            z-index: 10;
        }

        .auth-user-list li:hover .avatar-title {
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.7);
        }

        /* Avatar styling */
        .avatar-sm {
            width: 50px;
            height: 50px;
        }

        .avatar-title {
            width: 100%;
            height: 100%;
            overflow: hidden;
            border: 2px solid white;
            transition: box-shadow 0.3s ease;
            border-radius: 50%;
        }

        .avatar-title img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Tooltip styling */
        .avatar-tooltip {
            background-color: #1e293b;
            color: #fff;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 500;
        }

        /* School logo styling */
        .school-login-logo {
            height: 55px;
            width: auto;
            border-radius: 14px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .school-login-logo:hover {
            transform: scale(1.02);
        }

        /* Logo container animation */
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
            animation: fadeInScale 0.5s ease;
        }

        /* No staff message styling */
        .no-staff-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }

        .no-staff-icon {
            font-size: 48px;
            margin-bottom: 10px;
            animation: fadeInScale 0.5s ease;
        }

        /* Toast notification */
        .login-success {
            position: fixed;
            top: 24px;
            right: 24px;
            color: white;
            padding: 16px 20px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            animation: successCheck 0.4s cubic-bezier(0.34, 1.3, 0.64, 1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            max-width: 420px;
            min-width: 300px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .login-success.success { background: linear-gradient(135deg, #10b981, #059669); }
        .login-success.error { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .login-success.info { background: linear-gradient(135deg, #4f8ef7, #3b7ae3); }
        .login-success.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }

        .login-success .toast-icon {
            font-size: 22px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .login-success .toast-content {
            flex: 1;
        }

        .login-success .toast-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .login-success .toast-message {
            font-size: 12px;
            opacity: 0.92;
            line-height: 1.4;
        }

        /* Gold border highlight for session expired */
        .session-expired-highlight {
            border-left: 4px solid #f59e0b !important;
            transition: all 0.5s ease;
        }

        .session-expired-highlight-remove {
            border-left: 4px solid transparent !important;
            transition: all 0.5s ease;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .auth-effect-main { width: 200px; height: 200px; }
            .auth-user-list li { width: 40px; height: 40px; }
            .auth-user-list li:nth-child(1) { transform: translate(80px, 0); }
            .auth-user-list li:nth-child(2) { transform: rotate(72deg) translate(78px, 0); }
            .auth-user-list li:nth-child(3) { transform: rotate(144deg) translate(82px, 0); }
            .auth-user-list li:nth-child(4) { transform: rotate(216deg) translate(79px, 0); }
            .auth-user-list li:nth-child(5) { transform: rotate(288deg) translate(81px, 0); }
            @keyframes orbitClockwise {
                from { transform: rotate(0deg) translate(80px, 0) rotate(0deg); }
                to { transform: rotate(360deg) translate(80px, 0) rotate(-360deg); }
            }
            @keyframes orbitCounterClockwise {
                from { transform: rotate(0deg) translate(80px, 0) rotate(0deg); }
                to { transform: rotate(-360deg) translate(80px, 0) rotate(360deg); }
            }
            .school-login-logo { height: 40px; }
            .login-success { top: 12px; right: 12px; left: 12px; max-width: none; min-width: auto; }
        }
    </style>
</head>

<body>
    @php
        use App\Models\SchoolInformation;
        use App\Models\User;

        $schoolInfo = SchoolInformation::getActiveSchool();

        // Get recently active staff users (based on updated_at - last 7 days)
        $recentStaff = User::whereHas('roles', function($query) {
                $query->where('name', 'staff');
            })
            ->with(['staffPicture'])
            ->where('updated_at', '>=', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        if($recentStaff->isEmpty()) {
            $recentStaff = collect([]);
        }
    @endphp

    <section class="auth-page-wrapper position-relative d-flex align-items-center justify-content-center min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-11">
                    <div class="card mb-0 border-0 shadow-lg">
                        <div class="row g-0 align-items-center">
                            <div class="col-xxl-5">
                                <div class="card auth-card bg-secondary h-100 border-0 shadow-none d-none d-sm-block mb-0">
                                    <div class="card-body py-5 d-flex justify-content-between flex-column">
                                        <div class="text-center">
                                            <h3 class="text-white" style="animation: fadeInScale 0.6s ease;">Start your journey with us.</h3>
                                            <p class="text-white opacity-75 fs-base">It makes school operations SEAMLESS...</p>
                                        </div>

                                        <div class="auth-effect-main my-5 position-relative rounded-circle d-flex align-items-center justify-content-center mx-auto">
                                            <div class="effect-circle-1 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center" style="animation: pulse 2s infinite;">
                                                <div class="effect-circle-2 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center">
                                                    <div class="effect-circle-3 mx-auto rounded-circle position-relative text-white fs-4xl d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px);">
                                                        <span class="text-primary ms-1" style="font-weight: 600;">ViteSchool 2.0</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <ul class="auth-user-list list-unstyled">
                                                @if($recentStaff->isNotEmpty())
                                                    @foreach($recentStaff as $index => $staff)
                                                        <li style="animation-delay: {{ $index * 0.2 }}s;">
                                                            <a href="javascript:void(0)"
                                                               class="avatar-sm d-inline-block"
                                                               data-bs-toggle="tooltip"
                                                               data-bs-placement="top"
                                                               title="{{ $staff->name }}"
                                                               onclick="fillStaffCredentials('{{ $staff->email }}')">
                                                                <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                                    @php
                                                                        $avatarUrl = $staff->avatar
                                                                            ? asset('storage/staff_avatars/' . $staff->avatar)
                                                                            : ($staff->staffPicture?->picture
                                                                                ? asset('storage/staff_avatars/' . $staff->staffPicture->picture)
                                                                                : asset('theme/layouts/assets/images/users/avatar-default.jpg'));
                                                                    @endphp

                                                                    <img src="{{ $avatarUrl }}"
                                                                         alt="{{ $staff->name }}"
                                                                         class="img-fluid"
                                                                         style="width: 100%; height: 100%; object-fit: cover;"
                                                                         onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/users/avatar-default.jpg') }}'">
                                                                </div>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                @else
                                                    <div class="no-staff-message">
                                                        <i class="ri-user-line no-staff-icon"></i>
                                                        <p class="text-white opacity-75">No active staff</p>
                                                    </div>
                                                @endif
                                            </ul>
                                        </div>

                                        <div class="text-center">
                                            <p class="text-white opacity-75 mb-0 mt-3">
                                                © <script>document.write(new Date().getFullYear())</script> {{ $schoolInfo?->school_name ?? 'Viteschools.ng' }}.  with <i class="mdi mdi-heart text-danger"></i> by Qudroid Systems
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end col-->

                            <div class="col-xxl-6 mx-auto">
                                <div class="card mb-0 border-0 shadow-none mb-0" style="background: transparent;">
                                    <div class="card-body p-sm-5 m-lg-4" id="loginCardBody">
                                        <!-- School Logo on Login Form -->
                                        <div class="logo-container">
                                            @if($schoolInfo?->school_logo)
                                                <img src="{{ $schoolInfo->getLogoUrlAttribute() }}"
                                                     alt="{{ $schoolInfo->school_name }}"
                                                     class="school-login-logo"
                                                     onerror="this.onerror=null; this.src='{{ asset('theme/layouts/assets/images/logo-dark.png') }}'">
                                            @else
                                                <img src="{{ asset('theme/layouts/assets/images/logo-dark.png') }}"
                                                     alt="School Logo"
                                                     class="school-login-logo">
                                            @endif
                                        </div>

                                        <div class="text-center mt-2">
                                            <h5 class="fs-2xl fw-semibold" style="animation: fadeInScale 0.5s ease;">{{ $schoolInfo?->school_name ?? 'TopClass College' }} Portal</h5>
                                            <p class="text-muted">Sign in to continue</p>
                                        </div>

                                        <div class="p-2 mt-3">
                                            <form method="POST" action="{{ route('login') }}" id="loginForm">
                                                @csrf

                                                {{-- ===================================================== --}}
                                                {{-- SESSION EXPIRED ALERT - This displays the warning box --}}
                                                {{-- ===================================================== --}}
                                                @if(session('session_expired') || session('error') || request()->boolean('expired'))
                                                    <div id="sessionExpiredAlert" class="alert alert-warning alert-dismissible fade show mb-4" role="alert" style="border-left: 4px solid #f59e0b; background: #fffbeb;">
                                                        <div class="d-flex align-items-center">
                                                            <i class="ri-alert-line me-2" style="font-size: 18px; color: #d97706;"></i>
                                                            <div>
                                                                <strong style="color: #92400e;">Session Expired</strong>
                                                                <p class="mb-0 small" style="color: #78350f;">{{ session('error') ?? 'Your session has expired. Please login again.' }}</p>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                    </div>
                                                @endif

                                                {{-- Display validation errors --}}
                                                @if ($errors->any())
                                                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-left: 4px solid #ef4444;">
                                                        <div class="d-flex align-items-center">
                                                            <i class="ri-error-warning-line me-2" style="font-size: 18px;"></i>
                                                            <div>
                                                                <strong>Please fix the following errors:</strong>
                                                                <ul class="mb-0 mt-1 ps-3 small">
                                                                    @foreach ($errors->all() as $error)
                                                                        <li>{{ $error }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                    </div>
                                                @endif

                                                <div class="mb-4">
                                                    <label for="email" class="form-label">Email or phone number <span class="text-danger">*</span></label>
                                                    <div class="position-relative">
                                                        <input type="text"
                                                               class="form-control apple-input @error('email') is-invalid @enderror"
                                                               id="email"
                                                               name="email"
                                                               placeholder="Email, or parent's phone number"
                                                               value="{{ old('email') }}"
                                                               required
                                                               autocomplete="username"
                                                               autofocus>
                                                        @error('email')
                                                            <span class="invalid-feedback" role="alert">
                                                                <strong>{{ $message }}</strong>
                                                            </span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                                                        <span class="small">
                                                        @if (Route::has('password.request'))
                                                            <a href="{{ route('password.request') }}" class="text-muted text-decoration-none">Forgot password?</a>
                                                        @endif
                                                        @if (Route::has('parent.forgot'))
                                                            · <a href="{{ route('parent.forgot') }}" class="text-muted text-decoration-none">Parent reset by SMS</a>
                                                        @endif
                                                        </span>
                                                    </div>
                                                    <div class="position-relative auth-pass-inputgroup">
                                                        <input type="password"
                                                               id="password"
                                                               class="form-control apple-input pe-5 @error('password') is-invalid @enderror"
                                                               name="password"
                                                               autocomplete="current-password"
                                                               placeholder="Enter your password"
                                                               required>
                                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon">
                                                            <i class="ri-eye-fill align-middle"></i>
                                                        </button>
                                                    </div>
                                                    @error('password')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                <div class="form-check mb-4">
                                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="remember">Remember me</label>
                                                </div>

                                                <div class="mt-4">
                                                    <button class="btn btn-primary w-100 apple-button" type="submit" id="loginButton">
                                                        <span>Sign In</span>
                                                    </button>
                                                </div>
                                            </form>

                                            <div class="text-center mt-4">
                                                <p class="mb-0 text-muted small">Don't have an account? <a href="{{ route('register') }}" class="fw-semibold text-primary text-decoration-none">Sign Up</a></p>
                                            </div>
                                        </div>
                                    </div><!-- end card body -->
                                </div><!-- end card -->
                            </div>
                            <!--end col-->
                        </div>
                        <!--end row-->
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->
        </div>
        <!--end container-->
    </section>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{ asset('theme/layouts/assets/libs/simplebar/simplebar.min.js')}}"></script>
    <script src="{{ asset('theme/layouts/assets/js/plugins.js')}}"></script>

    <script>
        // =====================================================
        // TOAST NOTIFICATION FUNCTION
        // =====================================================
        function showToast(title, message, type = 'info') {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.login-success');
            existingToasts.forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = 'login-success ' + type;

            const icons = {
                success: 'ri-checkbox-circle-line',
                error: 'ri-close-circle-line',
                info: 'ri-information-line',
                warning: 'ri-alert-line'
            };

            toast.innerHTML = `
                <i class="${icons[type] || icons.info} toast-icon"></i>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            `;

            document.body.appendChild(toast);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.4s ease forwards';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 400);
            }, 5000);

            // Allow manual dismiss by clicking
            toast.addEventListener('click', function() {
                this.style.animation = 'fadeOut 0.3s ease forwards';
                setTimeout(() => {
                    if (this.parentNode) {
                        this.remove();
                    }
                }, 300);
            });
        }

        // =====================================================
        // APPLE OS STYLE LOGIN PAGE - MAIN SCRIPT
        // =====================================================

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    template: '<div class="tooltip avatar-tooltip" role="tooltip"><div class="tooltip-inner"></div></div>'
                });
            });

            // Avatar hover pause animation
            const avatarItems = document.querySelectorAll('.auth-user-list li');
            avatarItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.animationPlayState = 'paused';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.animationPlayState = 'running';
                });
            });

            // Field interactions
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');

            // Add focus pulse animation to fields
            function addFieldPulse(inputElement) {
                if (!inputElement) return;
                inputElement.addEventListener('focus', function() {
                    this.classList.add('field-pulse');
                    setTimeout(() => {
                        this.classList.remove('field-pulse');
                    }, 600);
                });
            }

            if (emailInput) addFieldPulse(emailInput);
            if (passwordInput) addFieldPulse(passwordInput);

            // Shake animation for error
            function shakeElement(element) {
                if (!element) return;
                element.classList.add('shake-field');
                if (window.navigator && window.navigator.vibrate) {
                    window.navigator.vibrate(100);
                }
                setTimeout(() => {
                    element.classList.remove('shake-field');
                }, 400);
            }

            // Check for existing errors on page load
            @if($errors->any())
                @if($errors->has('email'))
                    if (emailInput) shakeElement(emailInput);
                @endif
                @if($errors->has('password'))
                    if (passwordInput) shakeElement(passwordInput);
                @endif
                // Show error toast for login failure
                showToast('Login Failed', 'Invalid email or password. Please try again.', 'error');
            @endif

            // =====================================================
            // SESSION EXPIRED HANDLING - MAIN FEATURE
            // =====================================================
            @if(session('session_expired') || request()->boolean('expired'))
                // Show beautiful toast notification
                showToast(
                    'Session Expired',
                    '{{ session('error') ?? "Your session has expired. Please login again." }}',
                    'warning'
                );

                // Auto-dismiss the session expired alert after 5 seconds
                const sessionAlert = document.getElementById('sessionExpiredAlert');
                if (sessionAlert) {
                    setTimeout(() => {
                        const bsAlert = bootstrap.Alert.getInstance(sessionAlert);
                        if (bsAlert) {
                            bsAlert.close();
                        }
                    }, 5000);
                }

                // Add gold border highlight to the login form
                const loginCard = document.getElementById('loginCardBody');
                if (loginCard) {
                    loginCard.classList.add('session-expired-highlight');
                    setTimeout(() => {
                        loginCard.classList.remove('session-expired-highlight');
                        loginCard.classList.add('session-expired-highlight-remove');
                    }, 3000);
                }
            @endif

            // Real-time validation - remove error on input
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                    if (errorDiv && !errorDiv.innerHTML.includes('credentials')) {
                        errorDiv.remove();
                    }
                });
            }

            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                    if (errorDiv && !errorDiv.innerHTML.includes('credentials')) {
                        errorDiv.remove();
                    }
                });
            }

            // Email validation on blur
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    const email = this.value.trim();
                    const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
                    if (email && !emailRegex.test(email)) {
                        this.classList.add('is-invalid');
                        let errorDiv = this.parentElement.querySelector('.invalid-feedback');
                        if (!errorDiv) {
                            errorDiv = document.createElement('span');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.setAttribute('role', 'alert');
                            this.parentElement.appendChild(errorDiv);
                        }
                        errorDiv.innerHTML = '<strong>Please enter a valid email address.</strong>';
                        shakeElement(this);
                    }
                });
            }

            // Password toggle functionality
            const passwordAddon = document.getElementById('password-addon');
            if (passwordAddon && passwordInput) {
                passwordAddon.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.className = type === 'password' ? 'ri-eye-fill align-middle' : 'ri-eye-off-fill align-middle';
                    }
                });
            }

            // Form submission - show loading state
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    if (loginButton) {
                        loginButton.classList.add('loading');
                        loginButton.querySelector('span').textContent = 'Signing In...';
                        loginButton.disabled = true;
                    }
                });
            }

            // Clear session expired flag from URL (prevents showing on refresh)
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                if (url.searchParams.has('session_expired')) {
                    url.searchParams.delete('session_expired');
                    window.history.replaceState({}, document.title, url.toString());
                }
            }
        });

        // Staff credential fill function
        function fillStaffCredentials(email) {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            if (emailInput) {
                emailInput.value = email;
                emailInput.classList.remove('is-invalid');
                emailInput.dispatchEvent(new Event('focus'));
                emailInput.style.transition = 'all 0.3s ease';
                emailInput.style.backgroundColor = '#e8f0fe';
                setTimeout(() => {
                    emailInput.style.backgroundColor = '';
                }, 500);
            }

            if (passwordInput) {
                passwordInput.focus();
            }

            showToast('Staff Selected', 'Email filled. Enter your password to continue.', 'info');
        }
    </script>
</body>
</html>