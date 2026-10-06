{{-- resources/views/parent/password.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Change password" icon="ri-lock-password-line"
               :subtitle="$forced ? 'You signed in with a temporary password. Choose your own to continue.' : 'Update the password you use to sign in.'" />

    @if(session('warning'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('warning') }}</div></div>@endif

    <div class="row"><div class="col-lg-6">
        <x-cb.card title="New password" icon="ri-key-2-line">
            <form method="POST" action="{{ route('parent.password.update') }}">
                @csrf @method('PUT')
                @unless($forced)
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endunless
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                    <div class="form-text">At least 8 characters, with letters and numbers.</div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
                </div>
                <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save password</button>
            </form>
        </x-cb.card>
    </div></div>
</div>
</div>
</div>
@endsection
