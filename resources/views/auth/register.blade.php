<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <title>Sign Up | Vite-ESchool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="school App" name="description">
    <meta content="Themesbrand" name="author">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('theme/layouts/assets/images/favicon.ico')}}">

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
           APPLE OS STYLE REGISTRATION PAGE
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

        /* Password strength container */
        .password-strength-container {
            transition: all 0.3s ease;
        }

        /* Password requirement item */
        .password-requirement {
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirement.valid {
            color: #10b981;
        }

        .password-requirement.valid i {
            color: #10b981;
        }

        .password-requirement.invalid {
            color: #94a3b8;
        }

        .password-requirement i {
            font-size: 14px;
            transition: all 0.2s ease;
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

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Avatar orbit animations */
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
        }

        .avatar-title img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Effect circles */
        .effect-circle-1,
        .effect-circle-2,
        .effect-circle-3 {
            transition: all 0.3s ease;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }

        /* Responsive */
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
        }

        /* Success toast */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            animation: successCheck 0.4s cubic-bezier(0.34, 1.3, 0.64, 1);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toast-notification.error {
            background: #ef4444;
        }

        @keyframes fadeOut {
            to { opacity: 0; transform: translateX(20px); }
        }
    </style>
</head>

<body>

    <section class="auth-page-wrapper py-5 position-relative d-flex align-items-center justify-content-center min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-11">
                    <div class="card mb-0 border-0 shadow-lg">
                        <div class="row g-0 align-items-center">
                            <div class="col-xxl-5">
                                <div class="card auth-card bg-secondary h-100 border-0 shadow-none d-none d-sm-block mb-0">
                                    <div class="card-body py-5 d-flex justify-content-between flex-column">
                                        <div class="text-center">
                                            <h3 class="text-white" style="animation: fadeInScale 0.6s ease;">Join our community</h3>
                                            <p class="text-white opacity-75 fs-base">Create your account and get started</p>
                                        </div>

                                        <div class="auth-effect-main my-5 position-relative rounded-circle d-flex align-items-center justify-content-center mx-auto">
                                            <div class="effect-circle-1 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center" style="animation: pulse 2s infinite;">
                                                <div class="effect-circle-2 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center">
                                                    <div class="effect-circle-3 mx-auto rounded-circle position-relative text-white fs-4xl d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px);">
                                                        Welcome to <span class="text-primary ms-1">Vite-ESchool</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <ul class="auth-user-list list-unstyled">
                                                <li>
                                                    <div class="avatar-sm d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" title="John Doe">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-1.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="avatar-sm d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" title="Jane Smith">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-2.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="avatar-sm d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" title="Mike Johnson">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-3.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="avatar-sm d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" title="Sarah Wilson">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-4.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="avatar-sm d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" title="David Brown">
                                                        <div class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                                            <img src="{{ asset('theme/layouts/assets/images/users/avatar-5.jpg')}}" alt="" class="img-fluid">
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="text-center">
                                            <p class="text-white opacity-75 mb-0 mt-3">
                                                &copy; <script>document.write(new Date().getFullYear())</script> Vite-ESchool. Created with <i class="mdi mdi-heart text-danger"></i> by Qudroid Systems
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6 mx-auto">
                                <div class="card mb-0 border-0 shadow-none mb-0" style="background: transparent;">
                                    <div class="card-body p-sm-5 m-lg-4">
                                        <div class="text-center mt-2">
                                            <h5 class="fs-2xl fw-semibold" style="animation: fadeInScale 0.5s ease;">Create your free account</h5>
                                            <p class="text-muted">Get started with your learning journey</p>
                                        </div>
                                        <div class="p-2 mt-4">
                                            <form method="POST" action="{{ route('register') }}" id="registerForm">
                                                @csrf

                                                <div class="mb-4">
                                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                    <input id="email" type="email" class="form-control apple-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="your@email.com">
                                                    @error('email')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                <div class="mb-4">
                                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                    <input id="name" type="text" class="form-control apple-input @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="John Doe">
                                                    @error('name')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                <div class="mb-4">
                                                    <label class="form-label" for="password-input">Password <span class="text-danger">*</span></label>
                                                    <div class="position-relative">
                                                        <input type="password"
                                                               class="form-control apple-input password-input pe-5 @error('password') is-invalid @enderror"
                                                               onpaste="return false"
                                                               placeholder="Create a strong password"
                                                               id="password-input"
                                                               name="password"
                                                               autocomplete="new-password"
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

                                                <div id="password-contain" class="p-3 bg-light mb-4 rounded-3 password-strength-container" style="border-radius: 12px;">
                                                    <h5 class="fs-sm fw-semibold mb-2">Password must contain:</h5>
                                                    <p id="pass-length" class="password-requirement invalid mb-2">
                                                        <i class="ri-close-circle-fill"></i>
                                                        <span>Minimum <b>8 characters</b></span>
                                                    </p>
                                                    <p id="pass-lower" class="password-requirement invalid mb-2">
                                                        <i class="ri-close-circle-fill"></i>
                                                        <span>At least <b>lowercase</b> letter (a-z)</span>
                                                    </p>
                                                    <p id="pass-upper" class="password-requirement invalid mb-2">
                                                        <i class="ri-close-circle-fill"></i>
                                                        <span>At least <b>uppercase</b> letter (A-Z)</span>
                                                    </p>
                                                    <p id="pass-number" class="password-requirement invalid mb-0">
                                                        <i class="ri-close-circle-fill"></i>
                                                        <span>At least <b>number</b> (0-9)</span>
                                                    </p>
                                                </div>

                                                <div class="mt-4">
                                                    <button class="btn btn-primary w-100 apple-button" type="submit" id="registerButton">
                                                        <span>Create Account</span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="mt-4 text-center">
                                            <p class="mb-0">Already have an account? <a href="{{ route('login') }}" class="fw-semibold text-primary text-decoration-none">Sign In</a></p>
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
        // APPLE OS STYLE REGISTRATION PAGE WITH ANIMATIONS
        // =====================================================

        document.addEventListener('DOMContentLoaded', function() {

            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // =====================================================
            // FIELD INTERACTIONS
            // =====================================================

            const emailInput = document.getElementById('email');
            const nameInput = document.getElementById('name');
            const passwordInput = document.getElementById('password-input');
            const registerForm = document.getElementById('registerForm');
            const registerButton = document.getElementById('registerButton');

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

            addFieldPulse(emailInput);
            addFieldPulse(nameInput);
            addFieldPulse(passwordInput);

            // =====================================================
            // PASSWORD STRENGTH CHECKER (Real-time)
            // =====================================================

            function checkPasswordStrength(password) {
                const lengthValid = password.length >= 8;
                const lowerValid = /[a-z]/.test(password);
                const upperValid = /[A-Z]/.test(password);
                const numberValid = /[0-9]/.test(password);

                // Update UI elements
                updateRequirement('pass-length', lengthValid);
                updateRequirement('pass-lower', lowerValid);
                updateRequirement('pass-upper', upperValid);
                updateRequirement('pass-number', numberValid);

                return lengthValid && lowerValid && upperValid && numberValid;
            }

            function updateRequirement(elementId, isValid) {
                const element = document.getElementById(elementId);
                if (!element) return;

                const icon = element.querySelector('i');
                if (isValid) {
                    element.classList.remove('invalid');
                    element.classList.add('valid');
                    if (icon) {
                        icon.className = 'ri-checkbox-circle-fill';
                    }
                } else {
                    element.classList.remove('valid');
                    element.classList.add('invalid');
                    if (icon) {
                        icon.className = 'ri-close-circle-fill';
                    }
                }
            }

            // Real-time password validation
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                });
            }

            // =====================================================
            // SHAKE ANIMATION ON ERROR (Apple OS Style)
            // =====================================================

            function shakeElement(element) {
                if (!element) return;
                element.classList.add('shake-field');

                // Vibration for mobile devices
                if (window.navigator && window.navigator.vibrate) {
                    window.navigator.vibrate(100);
                }

                setTimeout(() => {
                    element.classList.remove('shake-field');
                }, 400);
            }

            // =====================================================
            // REAL-TIME VALIDATION (Apple Style)
            // =====================================================

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
                    } else if (email && emailRegex.test(email)) {
                        this.classList.remove('is-invalid');
                        const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                        if (errorDiv && !errorDiv.innerHTML.includes('has already been taken')) {
                            errorDiv.remove();
                        }
                    }
                });
            }

            // Name validation on blur
            if (nameInput) {
                nameInput.addEventListener('blur', function() {
                    const name = this.value.trim();
                    if (name && name.length < 2) {
                        this.classList.add('is-invalid');
                        let errorDiv = this.parentElement.querySelector('.invalid-feedback');
                        if (!errorDiv) {
                            errorDiv = document.createElement('span');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.setAttribute('role', 'alert');
                            this.parentElement.appendChild(errorDiv);
                        }
                        errorDiv.innerHTML = '<strong>Please enter your full name.</strong>';
                        shakeElement(this);
                    } else if (name && name.length >= 2) {
                        this.classList.remove('is-invalid');
                        const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                        if (errorDiv) errorDiv.remove();
                    }
                });
            }

            // Remove error styling on input
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                    if (errorDiv && !errorDiv.innerHTML.includes('already been taken')) {
                        errorDiv.remove();
                    }
                });
            }

            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                    if (errorDiv) errorDiv.remove();
                });
            }

            // =====================================================
            // FORM SUBMIT WITH VALIDATION
            // =====================================================

            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    let hasError = false;

                    // Validate email
                    const email = emailInput?.value.trim() || '';
                    const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
                    if (!email || !emailRegex.test(email)) {
                        if (emailInput) {
                            emailInput.classList.add('is-invalid');
                            shakeElement(emailInput);
                        }
                        hasError = true;
                    }

                    // Validate name
                    const name = nameInput?.value.trim() || '';
                    if (!name || name.length < 2) {
                        if (nameInput) {
                            nameInput.classList.add('is-invalid');
                            shakeElement(nameInput);
                        }
                        hasError = true;
                    }

                    // Validate password
                    const password = passwordInput?.value || '';
                    if (!password || !checkPasswordStrength(password)) {
                        if (passwordInput) {
                            passwordInput.classList.add('is-invalid');
                            shakeElement(passwordInput);

                            // Show custom error message
                            let errorDiv = passwordInput.parentElement.querySelector('.invalid-feedback');
                            if (!errorDiv) {
                                errorDiv = document.createElement('span');
                                errorDiv.className = 'invalid-feedback';
                                errorDiv.setAttribute('role', 'alert');
                                passwordInput.parentElement.appendChild(errorDiv);
                            }
                            errorDiv.innerHTML = '<strong>Please meet all password requirements.</strong>';
                        }
                        hasError = true;
                    }

                    if (hasError) {
                        e.preventDefault();
                        showToast('Validation Error', 'Please check your input and try again', 'error');
                        return false;
                    }

                    // Add loading state
                    if (registerButton) {
                        registerButton.classList.add('loading');
                    }
                });
            }

            // =====================================================
            // PASSWORD TOGGLE FUNCTIONALITY
            // =====================================================

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

            // =====================================================
            // TOAST NOTIFICATION (Apple Style)
            // =====================================================

            function showToast(title, message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = 'toast-notification ' + (type === 'error' ? 'error' : '');
                toast.innerHTML = `
                    <i class="mdi mdi-${type === 'success' ? 'check-circle' : 'alert-circle'}"></i>
                    <div>
                        <div style="font-weight: 600; font-size: 13px;">${title}</div>
                        <div style="font-size: 11px; opacity: 0.9;">${message}</div>
                    </div>
                `;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // =====================================================
            // CHECK FOR EXISTING ERRORS ON PAGE LOAD
            // =====================================================

            @if($errors->any())
                @if($errors->has('email'))
                    if (emailInput) shakeElement(emailInput);
                @endif
                @if($errors->has('name'))
                    if (nameInput) shakeElement(nameInput);
                @endif
                @if($errors->has('password'))
                    if (passwordInput) shakeElement(passwordInput);
                @endif
            @endif

            // =====================================================
            // AVATAR HOVER PAUSE ANIMATION
            // =====================================================

            const avatarItems = document.querySelectorAll('.auth-user-list li');
            avatarItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.animationPlayState = 'paused';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.animationPlayState = 'running';
                });
            });

            // =====================================================
            // INITIAL PASSWORD STRENGTH CHECK (if value exists)
            // =====================================================

            if (passwordInput && passwordInput.value) {
                checkPasswordStrength(passwordInput.value);
            }
        });
    </script>

</body>
</html>
