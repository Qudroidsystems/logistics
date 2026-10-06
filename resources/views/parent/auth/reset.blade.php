@extends('parent.auth.layout')
@section('title', 'Enter your code')
@section('body')
    <p class="small text-muted">We sent a code to <strong>{{ $phone }}</strong>. It expires in 10 minutes.
        <a href="{{ route('parent.forgot') }}">Use another number</a></p>
    <form method="POST" action="{{ route('parent.reset.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="code">6-digit code</label>
            <input type="text" name="code" id="code" class="form-control text-center fs-4" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code">
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password">
            <div class="form-text">At least 8 characters, with letters and numbers.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
        <button class="btn btn-primary w-100">Reset password</button>
    </form>
@endsection
