@php $brand ??= \App\Models\BrandSetting::forApp('customer'); @endphp
@extends('public._layout', ['title' => 'Delete your account · '.$brand['name']])
@section('content')
<div class="container py-5" style="max-width: 640px">
<div class="pub-card">
    @if(session('deleted'))
        <h1 class="h4 fw-bold">Your account has been deleted.</h1>
        <p class="text-muted mb-0">Your name, email, phone number, photo and saved places are gone, and you've been signed out everywhere. Records of past payments are kept, without your details, for as long as accounting law requires.</p>
    @else
    <h1 class="h4 fw-bold">Delete your {{ $brand['name'] }} account</h1>
    <p>You can delete your account from the customer or driver app (<strong>Account → Delete my account</strong>) or here.</p>
    <h2 class="h6 fw-bold mt-3">What happens</h2>
    <ul class="small">
        <li>Your name, email, phone number, photo, saved places, devices and sign-ins are deleted straight away.</li>
        <li>A business plan or recurring deliveries you have are stopped.</li>
        <li>Records of past orders, payments and invoices stay, without your details, because tax law requires them.</li>
        <li>This can't be undone. You can sign up again later with the same email.</li>
    </ul>
    @auth
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if(count($blockers ?? []))
            <div class="alert alert-warning"><strong>First:</strong><ul class="mb-0">@foreach($blockers as $b)<li>{{ $b }}</li>@endforeach</ul></div>
        @else
        <form method="post" action="{{ route('delete-account.destroy') }}" class="mt-3">@csrf
            <label class="form-label small fw-semibold" for="pw">Your password</label>
            <input id="pw" type="password" name="password" class="form-control mb-2" required autocomplete="current-password">
            <label class="form-label small fw-semibold" for="rs">Why are you leaving? (optional)</label>
            <input id="rs" name="reason" class="form-control mb-2" maxlength="300">
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="confirm" value="1" id="cf" required><label class="form-check-label small" for="cf">I understand this deletes my account for good.</label></div>
            <button class="btn btn-danger w-100">Delete my account</button>
        </form>
        @endif
    @else
        <a class="btn btn-dark w-100 mt-2" href="{{ route('login') }}">Sign in to delete your account</a>
        <p class="small text-muted mt-2 mb-0">Can't sign in? Email {{ config('booking.contact_email') ?: config('mail.from.address') }} from the address on your account and we'll delete it within 30 days.</p>
    @endauth
    @endif
</div>
</div>
@endsection
