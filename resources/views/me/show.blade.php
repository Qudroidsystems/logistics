@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="My account" icon="ri-user-settings-line" subtitle="Your name, email, photo and password." :back="$back" back-label="Back" />
    @include('account._flash')
    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Your details" icon="ri-user-line">
                <form method="POST" action="{{ route($area.'.me.profile') }}" enctype="multipart/form-data">@csrf
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="{{ $u->avatar_url }}" alt="" class="rounded-circle" width="56" height="56" style="object-fit:cover">
                        <input type="file" name="avatar" accept="image/*" class="form-control form-control-sm">
                    </div>
                    <label class="form-label small">Full name</label>
                    <input type="text" name="name" class="form-control mb-2" value="{{ old('name', $u->name) }}" required maxlength="150">
                    <label class="form-label small">Email</label>
                    <input type="email" name="email" class="form-control mb-1" value="{{ old('email', $u->email) }}" required maxlength="190">
                    <div class="small mb-3">
                        @if($emailVerified)<span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line"></i> Confirmed</span>
                        @else<span class="badge bg-warning-subtle text-warning"><i class="ri-error-warning-line"></i> Not confirmed yet</span>@endif
                    </div>
                    <button class="btn btn-primary w-100">Save</button>
                </form>
                <div class="mt-3 small d-flex justify-content-between align-items-center">
                    <span><i class="ri-smartphone-line"></i> {{ $u->phone_number ?: 'No phone number yet' }}
                        @if($u->phone_number && $phoneVerified)<span class="badge bg-success-subtle text-success ms-1">Confirmed</span>@endif</span>
                    <a href="{{ route($area.'.phone') }}">{{ $u->phone_number ? 'Change' : 'Add' }}</a>
                </div>
            </x-cb.card>
        </div>
        <div class="col-lg-6">
            <x-cb.card title="Password" icon="ri-lock-password-line">
                <form method="POST" action="{{ route($area.'.me.password') }}">@csrf
                    <label class="form-label small">Current password</label>
                    <input type="password" name="current_password" class="form-control mb-2" autocomplete="current-password" required>
                    <label class="form-label small">New password</label>
                    <input type="password" name="password" class="form-control mb-1" autocomplete="new-password" minlength="8" required>
                    <div class="form-text mb-2">At least 8 characters, with letters and numbers.</div>
                    <label class="form-label small">Repeat new password</label>
                    <input type="password" name="password_confirmation" class="form-control mb-3" autocomplete="new-password" required>
                    <button class="btn btn-primary w-100">Change password</button>
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
