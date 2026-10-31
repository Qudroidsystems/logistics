@php
    $brand ??= \App\Models\BrandSetting::forApp('customer');
    $ca = config('region.country') === 'CA';
    $contact = config('booking.contact_email') ?: config('mail.from.address');
@endphp
@extends('public._layout', ['title' => 'Privacy policy · '.$brand['name']])
@section('content')
<div class="container py-5" style="max-width: 820px">
<div class="pub-card legal">
<h1 class="h3 fw-bold">Privacy policy</h1>
<p class="small text-muted">Last updated 31 October 2026</p>
<p>This policy explains what {{ $brand['name'] }} ("we") collects when you use our website, the customer app and the driver app, why, who we share it with, and the choices you have. {{ $ca ? 'We follow Canada\'s Personal Information Protection and Electronic Documents Act (PIPEDA) and Canada\'s anti-spam law (CASL).' : 'We follow the Nigeria Data Protection Act 2023.' }}</p>

<h2 class="h5 fw-bold mt-4">What we collect</h2>
<ul>
    <li><strong>Account details:</strong> your name, email, phone number, password (stored scrambled, never readable) and, if you add one, a photo.</li>
    <li><strong>Delivery details:</strong> pickup and drop-off addresses, contact names and numbers you enter, what you're sending, notes, photos you attach, and saved places.</li>
    <li><strong>Proof of delivery:</strong> photos, signatures, receiver names and codes the driver records at hand-over.</li>
    <li><strong>Payments:</strong> amounts, what was paid for and how. Card numbers are handled by our payment processors and never reach our servers. For Interac e-Transfer we keep the sender name and reference you give us.</li>
    <li><strong>Location:</strong> customers, only when you choose "use my location" on a map. Drivers, while they are online in the driver app, including in the background, so we can offer nearby jobs and show customers where their parcel is. We keep detailed location history for 60 days.</li>
    <li><strong>Messages and calls:</strong> in-app chat between you, the driver and dispatch. Calls through our number bridge connect without showing either side's number; we keep a record that the call happened, not its content.</li>
    <li><strong>Drivers and partner stores:</strong> what you send when applying (licence, insurance, vehicle registration, driver's abstract, business details) and the checks we make.</li>
    <li><strong>Device details:</strong> app version, device type and a notification token so we can send you updates.</li>
</ul>

<h2 class="h5 fw-bold mt-4">Why we use it</h2>
<ul>
    <li>To price, book, dispatch, track and complete deliveries, and to show proof of delivery.</li>
    <li>To take payments, pay drivers and partner stores, issue receipts and invoices, and handle refunds and disputes.</li>
    <li>To send you texts, emails and app notifications about your deliveries and account.</li>
    <li>To keep the service safe: preventing fraud, checking drivers, and investigating problems.</li>
    <li>To send offers, only if you agreed to receive them. You can stop at any time with the unsubscribe link or in your account.</li>
</ul>

<h2 class="h5 fw-bold mt-4">Who we share it with</h2>
<ul>
    <li><strong>The people doing your delivery:</strong> the driver and, in our marketplace, the delivery company you booked get the addresses, contact names, numbers and notes they need.</li>
    <li><strong>Service providers</strong> who work for us under contract: hosting, payment processing (for example Stripe and Interac), text and WhatsApp messaging, email, maps and address search, and push notifications.</li>
    <li><strong>Authorities</strong>, when the law requires it or to protect people's safety.</li>
</ul>
<p>We don't sell your personal information. Some providers may store data outside {{ config('region.country_name') }}; they must protect it to the same standard.</p>

<h2 class="h5 fw-bold mt-4">How long we keep it</h2>
<p>We keep your account while you use it. Detailed driver location history is removed after 60 days. Documents from driver applications we decline are removed after 90 days. Records of orders, payments and invoices are kept for as long as tax and accounting law requires{{ $ca ? ' (generally six years in Canada)' : '' }}, even after an account is deleted, but without your name and contact details.</p>

<h2 class="h5 fw-bold mt-4">Your choices and rights</h2>
<ul>
    <li>See and correct your details in your account, or ask us for a copy of what we hold.</li>
    <li>Turn off marketing messages at any time.</li>
    <li>Delete your account in the app (Account → Delete my account) or at <a href="{{ route('delete-account') }}">{{ route('delete-account') }}</a>.</li>
    <li>Withdraw consent for location sharing in your phone's settings (the driver app can't offer jobs without it).</li>
</ul>
@if($contact)<p>Questions or requests: <a href="mailto:{{ $contact }}">{{ $contact }}</a>. We reply within 30 days.@if($ca) If you aren't satisfied, you can contact the Office of the Privacy Commissioner of Canada.@endif</p>@endif

<h2 class="h5 fw-bold mt-4">Security</h2>
<p>We protect your information with encrypted connections, access limited to staff who need it, two-step sign-in for staff, and audit logs. No system is perfectly secure; if a breach puts you at risk we will tell you and the regulator as the law requires.</p>

<h2 class="h5 fw-bold mt-4">Changes</h2>
<p>We'll post any changes here and tell you in the app or by email if they matter.</p>
<p class="small text-muted mt-4 mb-0">This is a template drafted for {{ $brand['name'] }}. Have it reviewed by a lawyer before you rely on it.</p>
</div>
</div>
@endsection
