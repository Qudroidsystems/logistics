@extends('parent.auth.layout')
@section('title', 'Reset your password')
@section('body')
    <p class="small text-muted">Enter the phone number the school has for you. We'll send a 6-digit code by SMS.</p>
    <form method="POST" action="{{ route('parent.forgot.send') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="phone">Phone number</label>
            <input type="tel" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" placeholder="e.g. 08031234567" required autofocus autocomplete="tel">
        </div>
        <button class="btn btn-primary w-100">Send code</button>
    </form>
@endsection
