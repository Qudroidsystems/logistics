@php
    $brand ??= \App\Models\BrandSetting::forApp('customer');
    $ca = config('region.country') === 'CA';
    $contact = config('booking.contact_email') ?: config('mail.from.address');
@endphp
@extends('public._layout', ['title' => 'Terms of service · '.$brand['name']])
@section('content')
<div class="container py-5" style="max-width: 820px">
<div class="pub-card legal">
<h1 class="h3 fw-bold">Terms of service</h1>
<p class="small text-muted">Last updated 31 October 2026</p>
<p>These terms apply when you use {{ $brand['name'] }}'s website or customer app to send parcels. By creating an account or booking a delivery you agree to them and to our <a href="{{ route('privacy') }}">privacy policy</a>. Drivers have their own <a href="{{ route('drive.terms') }}">driver terms</a>.</p>

<h2 class="h5 fw-bold mt-4">Your account</h2>
<p>Give accurate details and keep your password private. You're responsible for bookings made from your account. You must be old enough to enter a contract where you live.</p>

<h2 class="h5 fw-bold mt-4">Prices and payment</h2>
<ul>
    <li>The price we show is held for 10 minutes and includes the taxes shown. If it changes before you book, we show you the new price first.</li>
    <li>You pay when you book, by card, from your wallet{{ $ca ? ', by Interac e-Transfer' : '' }}, or with a business plan. An e-Transfer booking goes ahead once we receive the money.</li>
    <li>Business plans renew monthly from your wallet until you cancel; a cancelled plan runs to the end of the month you paid for. Unused deliveries don't carry over.</li>
</ul>

<h2 class="h5 fw-bold mt-4">What you can send</h2>
<p>Describe your item honestly and pack it so it survives normal handling. Don't send anything illegal, dangerous (including weapons, explosives, flammable liquids and drugs), alive, perishable without telling us, cash, or items of exceptional value. Drivers may refuse an item that looks unsafe or doesn't match the description.</p>

<h2 class="h5 fw-bold mt-4">Pickup and delivery</h2>
<ul>
    <li>Someone must be at the pickup and drop-off, or you must give clear instructions. Waiting time beyond a few minutes may be charged.</li>
    <li>If we can't deliver after a reasonable attempt, we may return the item to the pickup or a partner store, and the fees in your booking apply.</li>
    <li>When you drop a parcel at a partner store, our responsibility starts when the store scans it in.</li>
</ul>

<h2 class="h5 fw-bold mt-4">Cancellations and refunds</h2>
<p>You can cancel before a driver picks up. The cancellation fee shown on the order applies once a driver is on the way. Refunds go back to your wallet or original payment method.</p>

<h2 class="h5 fw-bold mt-4">Loss and damage</h2>
<p>If an item is lost or damaged while with us, tell us within 7 days through "Get help" on the order. Unless you bought extra cover, our liability is limited to the lower of the item's value and {{ \App\Support\Money::format((int) config('booking.liability_limit'), true) }} per delivery, and we aren't liable for indirect losses. Nothing in these terms limits rights you have by law as a consumer.</p>

<h2 class="h5 fw-bold mt-4">Ending your account</h2>
<p>You can delete your account at any time (see the <a href="{{ route('delete-account') }}">delete account page</a>). We may suspend accounts used for fraud, abuse of drivers or staff, or sending prohibited items.</p>

<h2 class="h5 fw-bold mt-4">Disputes and law</h2>
<p>Contact us first and we'll try to sort it out. These terms are governed by the laws of {{ $ca ? 'Ontario and the federal laws of Canada that apply there' : 'the Federal Republic of Nigeria' }}.</p>
@if($contact)<p>Contact: <a href="mailto:{{ $contact }}">{{ $contact }}</a></p>@endif
<p class="small text-muted mt-4 mb-0">This is a template drafted for {{ $brand['name'] }}. Have it reviewed by a lawyer before you rely on it, especially the liability amount.</p>
</div>
</div>
@endsection
