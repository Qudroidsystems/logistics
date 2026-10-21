@extends('public._layout', ['title' => 'Your application · '.$brand['name']])
@section('content')
<div class="container py-5" style="max-width: 620px">
    <div class="pub-card text-center">
        @if($a->status === 'new')
            <div class="display-6">✓</div>
            <h1 class="h4 fw-bold">Thanks, {{ \Illuminate\Support\Str::before($a->name, ' ') }}. We've got your application.</h1>
            <p class="text-muted">We usually reply within 3 business days by email. Keep this page's link if you want to check back.</p>
            <div class="small text-muted">Sent {{ \Illuminate\Support\Carbon::parse($a->created_at)->diffForHumans() }}</div>
        @elseif($a->status === 'approved')
            <h1 class="h4 fw-bold">You're approved. Welcome aboard!</h1>
            <p class="text-muted mb-0">Check your email for how to sign in, then open the driver app and go online.</p>
        @else
            <h1 class="h4 fw-bold">We can't go ahead right now.</h1>
            <p class="text-muted mb-0">Thank you for applying. We've emailed you about it. You're welcome to apply again later.</p>
        @endif
    </div>
</div>
@endsection
