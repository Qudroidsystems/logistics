@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Past jobs" icon="ri-history-line" subtitle="Your finished deliveries." :back="route('driver.home')" back-label="Today" />
    <x-cb.card title="Finished" icon="ri-check-double-line" :count="count($rows)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Route</th><th class="text-end">Pay</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr><td class="small">{{ $r['pickup'] }} to {{ $r['dropoff'] }}</td><td class="text-end">{{ $r['payout_amount'] ? $naira($r['payout_amount']) : '—' }}</td><td class="small text-muted">{{ $r['completed_at'] ? \Illuminate\Support\Carbon::parse($r['completed_at'])->format('d M H:i') : '' }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No finished jobs yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
