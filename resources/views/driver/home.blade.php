@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Today" icon="ri-steering-2-line" :subtitle="$d->company">
        <x-slot:pills><span class="cb-meta-pill">{{ str_replace('_', ' ', $d->availability) }}</span></x-slot:pills>
    </x-cb.hero>
    @include('driver._flash')

    <x-cb.card title="Your status" icon="ri-toggle-line">
        @if($d->availability === 'on_job')
            <span class="text-muted">You are on a job. Finish it to change your status.</span>
        @else
        <form method="POST" action="{{ route('driver.availability') }}" class="d-flex gap-2">@csrf
            @foreach(['online' => 'Go online', 'break' => 'Take a break', 'offline' => 'Go offline'] as $v => $label)
                <button name="availability" value="{{ $v }}" class="btn {{ $d->availability === $v ? 'btn-primary' : 'btn-outline-primary' }} flex-fill">{{ $label }}</button>
            @endforeach
        </form>
        @endif
    </x-cb.card>

    @if(count($jobs))
    <x-cb.card title="Your job" icon="ri-route-line" :count="count($jobs)">
        @foreach($jobs as $j)
            <a href="{{ route('driver.job', $j['shipment']) }}" class="d-block p-3 border rounded mb-2 text-decoration-none text-reset">
                <div class="d-flex justify-content-between"><strong>{{ str_replace('_', ' ', ucfirst($j['status'])) }}</strong>@if($j['payout_amount'])<span>{{ $naira($j['payout_amount']) }}</span>@endif</div>
                <div class="small text-muted">{{ $j['pickup'] }} to {{ $j['dropoff'] }}</div>
            </a>
        @endforeach
    </x-cb.card>
    @endif

    <x-cb.card title="Job offers" icon="ri-notification-3-line" :count="count($offers)">
        @forelse($offers as $o)
            <div class="p-3 border rounded mb-2">
                <div class="d-flex justify-content-between"><strong>{{ $o['payout_offered'] ? $naira($o['payout_offered']) : 'Delivery job' }}</strong><span class="small text-muted"><span class="offer-left" data-left="{{ $o['seconds_left'] }}">{{ $o['seconds_left'] }}</span>s left</span></div>
                <div class="small text-muted mb-2">{{ $o['pickup'] }} to {{ $o['dropoff'] }}</div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('driver.offer.accept', $o['id']) }}" class="flex-fill">@csrf<button class="btn btn-success w-100">Accept</button></form>
                    <form method="POST" action="{{ route('driver.offer.decline', $o['id']) }}" class="flex-fill">@csrf<button class="btn btn-outline-secondary w-100">Decline</button></form>
                </div>
            </div>
        @empty
            <span class="text-muted">{{ $d->availability === 'online' ? 'No offers right now. This page checks for new ones.' : 'Go online to receive offers.' }}</span>
        @endforelse
    </x-cb.card>
</div></div></div>
<script>
(function () {
    document.querySelectorAll('.offer-left').forEach(function (el) {
        var n = parseInt(el.dataset.left, 10);
        setInterval(function () { n = Math.max(0, n - 1); el.textContent = n; }, 1000);
    });
    // Online drivers see new offers without pressing anything.
    @if($d->availability === 'online')
    setTimeout(function () { location.reload(); }, 12000);
    @endif
})();
</script>
@endsection
