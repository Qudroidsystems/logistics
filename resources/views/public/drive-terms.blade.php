@extends('public._layout', ['title' => 'Driver terms · '.$brand['name']])
@section('content')
<div class="container py-5" style="max-width: 760px">
    <div class="pub-card">
        <h1 class="h3 fw-bold">Driver terms</h1>
        <p class="small text-muted">Version {{ $version }}</p>
        <p>These terms apply when you apply to drive with {{ $brand['name'] }} and, if you are approved, while you deliver with us.</p>
        <h2 class="h6 fw-bold mt-4">Your application</h2>
        <p>The information and documents you send must be true and your own. We use them to decide on your application and, if you are approved, to keep your driver record. We keep licence and policy numbers only in a form that lets us check them, plus their last four characters.</p>
        <h2 class="h6 fw-bold mt-4">Checks</h2>
        <p>With your permission we may check that your licence is valid and look at your driving record. We may decline an application for any lawful reason.</p>
        <h2 class="h6 fw-bold mt-4">Driving with us</h2>
        <ul>
            <li>You choose when to go online and which offers to accept.</li>
            <li>Keep your licence, vehicle registration and insurance valid, and tell us when any of them changes. Your insurance must allow the way you use your vehicle for deliveries.</li>
            <li>Follow traffic laws, handle parcels with care, and use the delivery proofs the app asks for.</li>
            <li>Earnings for each job are shown before you accept it and are paid to the account you add in the app.</li>
        </ul>
        <h2 class="h6 fw-bold mt-4">Text messages</h2>
        <p>If you tick the box, we will text you about your application and, once you're driving, about jobs. Message and data rates may apply. Reply STOP to any message to stop, or HELP for help.</p>
        <h2 class="h6 fw-bold mt-4">Ending</h2>
        <p>You can stop driving at any time. We may pause or end your access if your documents expire or for safety or conduct reasons.</p>
        <p class="small text-muted mt-4 mb-0">This is a general template. Have it reviewed by a lawyer for your province before you rely on it.</p>
    </div>
</div>
@endsection
