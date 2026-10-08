@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Dispatch board" icon="ri-layout-grid-line" subtitle="Who needs a driver, who is free, and what is on the road." :back="route('provider.dashboard')" back-label="Dashboard" />
    @include('provider._flash')

    <x-cb.card title="Needs a driver" icon="ri-user-received-line" :count="count($waiting)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th>Route</th><th>Waiting</th><th style="min-width:260px">Give to</th></tr></thead>
            <tbody>
            @forelse($waiting as $j)
                <tr>
                    <td><a href="{{ route('provider.job', $j->public_id) }}" class="fw-semibold">{{ $j->order_number }}</a>
                        @if($j->needs_manual_dispatch)<div><span class="badge bg-warning text-dark">Handed back or no match</span></div>@endif</td>
                    <td class="small">{{ $line($j->id, 'pickup') }} to {{ $line($j->id, 'dropoff') }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($j->updated_at)->diffForHumans(null, true) }}</td>
                    <td>
                        <form method="POST" action="{{ route('provider.job.assign', $j->public_id) }}" class="d-flex gap-2">@csrf
                            <select name="driver_user_id" class="form-select form-select-sm" required>
                                <option value="">Choose a driver</option>
                                @foreach($drivers as $d)<option value="{{ $d['user_id'] }}">{{ $d['name'] }} · {{ $d['active_jobs'] }}/{{ $d['max_active_jobs'] }} · {{ str_replace('_', ' ', $d['availability']) }}</option>@endforeach
                            </select>
                            <button class="btn btn-sm btn-primary">Assign</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Nothing is waiting for a driver.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    <x-cb.card title="On the road" icon="ri-route-line" :count="count($moving)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th>Driver</th><th>Status</th><th>Route</th></tr></thead>
            <tbody>
            @forelse($moving as $j)
                <tr>
                    <td><a href="{{ route('provider.job', $j->public_id) }}" class="fw-semibold">{{ $j->order_number }}</a>
                        @if(($issues[$j->id] ?? 0) > 0)<div><span class="badge bg-danger">Driver reported a problem</span></div>@endif</td>
                    <td>{{ $driverOf[$j->id] ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $j->status) }}</span></td>
                    <td class="small">{{ $line($j->id, 'pickup') }} to {{ $line($j->id, 'dropoff') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No jobs on the road.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    <x-cb.card title="Your drivers" icon="ri-steering-2-line" :count="count($drivers)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Driver</th><th>Status</th><th class="text-end">Jobs</th></tr></thead>
            <tbody>
            @forelse($drivers as $d)
                <tr><td>{{ $d['name'] }}</td><td><span class="badge {{ $d['availability'] === 'online' ? 'bg-success' : 'bg-light text-dark' }}">{{ str_replace('_', ' ', $d['availability']) }}</span></td><td class="text-end">{{ $d['active_jobs'] }} / {{ $d['max_active_jobs'] }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No approved drivers yet. Approve one on the Team page.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
<script>setTimeout(function () { location.reload(); }, 30000);</script>
@endsection
