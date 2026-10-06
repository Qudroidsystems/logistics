@extends('layouts.master')
@section('content')
<div class="container py-5" style="max-width:480px">
    <h4 class="mb-3">Choose a new password</h4>
    <p class="text-muted">Your account was created with a temporary password. Please set your own to continue.</p>
    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Save password</button>
    </form>
</div>
@endsection
