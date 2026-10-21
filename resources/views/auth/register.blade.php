<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title>Create an account | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link id="fontsLink" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <script src="{{ asset('theme/layouts/assets/js/layout.js')}}"></script>
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css">
    <link href="{{ asset('theme/layouts/assets/css/app.min.css')}}" rel="stylesheet" type="text/css">
    <style>
        body { background: #f1f5f9; }
        .reg-card { border-radius: 28px; border: 0; box-shadow: 0 20px 60px rgba(15, 23, 42, .12); }
        .reg-side { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 28px 0 0 28px; color: #fff; }
        @media (max-width: 991px) { .reg-side { border-radius: 28px 28px 0 0; } }
        .apple-input { border-radius: 12px !important; border: 1.5px solid #e2e8f0 !important; background: #f8fafc !important; font-size: 16px !important; padding: 12px 16px !important; }
        .apple-input:focus { border-color: #4f8ef7 !important; background: #fff !important; box-shadow: 0 0 0 4px rgba(79, 142, 247, .1) !important; }
        .apple-button { background: #4f8ef7 !important; border: none !important; border-radius: 12px !important; padding: 14px 20px !important; font-weight: 600 !important; font-size: 16px !important; }
        .intent { border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 12px 14px; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; height: 100%; }
        .intent:hover { border-color: #cbd5e1; }
        .btn-check:checked + .intent { border-color: #4f8ef7; background: #eff6ff; }
        .intent i { font-size: 22px; color: #4f8ef7; }
    </style>
</head>
<body>
<section class="d-flex align-items-center justify-content-center min-vh-100 py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card reg-card mb-0">
                    <div class="row g-0">
                        <div class="col-lg-4 reg-side p-4 p-lg-5 d-flex flex-column justify-content-between">
                            <div>
                                <h3 class="text-white">Create your account</h3>
                                <p class="opacity-75">One account for sending parcels, running a delivery business or driving for a company.</p>
                            </div>
                            <ul class="list-unstyled opacity-75 small mb-0 mt-4">
                                <li class="mb-2"><i class="ri-checkbox-circle-line me-1"></i> Book and track deliveries</li>
                                @if($marketplace)<li class="mb-2"><i class="ri-checkbox-circle-line me-1"></i> List your company, or ride on your own</li>@endif
                                <li><i class="ri-checkbox-circle-line me-1"></i> Join the company that invited you</li>
                            </ul>
                        </div>
                        <div class="col-lg-8">
                            <div class="p-4 p-lg-5">
                                @if ($errors->any())
                                    <div class="alert alert-danger mb-4" role="alert" style="border-left: 4px solid #ef4444;">
                                        <ul class="mb-0 ps-3 small">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('register') }}" id="regForm">
                                    @csrf
                                    <label class="form-label fw-medium">I am here to</label>
                                    @php $chosen = old('intent', $intent); @endphp
                                    <div class="row g-2 mb-4">
                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="intent" id="iCustomer" value="customer" @checked($chosen === 'customer')>
                                            <label class="intent" for="iCustomer"><i class="ri-box-3-line"></i><span><strong>Send things</strong><br><span class="small text-muted">Book and track deliveries</span></span></label>
                                        </div>
                                        @if($marketplace)
                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="intent" id="iProvider" value="provider" @checked($chosen === 'provider')>
                                            <label class="intent" for="iProvider"><i class="ri-truck-line"></i><span><strong>Deliver</strong><br><span class="small text-muted">A company, a rider or a shopper</span></span></label>
                                        </div>
                                        @endif
                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="intent" id="iTeam" value="team" @checked($chosen === 'team')>
                                            <label class="intent" for="iTeam"><i class="ri-team-line"></i><span><strong>Join a team</strong><br><span class="small text-muted">A company invited me</span></span></label>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
                                            <input id="name" name="name" class="form-control apple-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="name" maxlength="120">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input id="email" type="email" name="email" class="form-control apple-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email" maxlength="190">
                                            <div class="form-text" id="teamHint" @if($chosen !== 'team') hidden @endif>Use the email address the company sent the invitation to.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone number</label>
                                            <input id="phone" name="phone" class="form-control apple-input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" autocomplete="tel" maxlength="20">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="referral_code" class="form-label">Referral code</label>
                                            <input id="referral_code" name="referral_code" class="form-control apple-input" value="{{ old('referral_code', request('ref')) }}" maxlength="16">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                            <input id="password" type="password" name="password" class="form-control apple-input @error('password') is-invalid @enderror" required autocomplete="new-password" minlength="8">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="password_confirmation" class="form-label">Confirm password <span class="text-danger">*</span></label>
                                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control apple-input" required autocomplete="new-password" minlength="8">
                                        </div>
                                        <div class="col-12 form-check ms-1">
                                            <input class="form-check-input" type="checkbox" name="offers" value="1" id="offers" @checked(old('offers'))>
                                            <label class="form-check-label small" for="offers">Send me offers and news</label>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary w-100 apple-button mt-4" type="submit">Create account</button>
                                </form>
                                <p class="text-center text-muted mt-4 mb-0">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.querySelectorAll('input[name="intent"]').forEach(function (r) {
        r.addEventListener('change', function () { document.getElementById('teamHint').hidden = this.value !== 'team'; });
    });
</script>
</body>
</html>
