@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Order '.$order->order_number" icon="ri-route-line" :subtitle="'Tracking '.$s->tracking_code" :back="route('provider.jobs')" back-label="Jobs">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ str_replace('_', ' ', $s->status) }}</span>
            <span class="cb-meta-pill">{{ $naira($order->total) }}</span>
        </x-slot:pills>
    </x-cb.hero>
    @include('provider._flash')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="If the delivery fails" icon="ri-file-list-3-line" class="mb-3">
                @foreach($terms as $line)<p class="small mb-2">{{ $line }}</p>@endforeach
                <div class="small text-muted">Agreed with the customer when the job was booked.</div>
            </x-cb.card>
            @include('partials.proofs')
            <x-cb.card title="Parcels" icon="ri-qr-code-line" class="mb-3">
                @forelse($packages as $p)
                    <div class="d-flex justify-content-between border-bottom py-1"><span>{{ $p['seq'] }}. {{ $p['description'] }}@if($p['quantity'] > 1) ×{{ $p['quantity'] }}@endif @if($p['fragile'])<span class="badge bg-warning-subtle text-warning">fragile</span>@endif</span><code>{{ $p['barcode'] }}</code></div>
                @empty
                    <span class="text-muted">No parcels recorded.</span>
                @endforelse
                @if(count($packages))<a class="btn btn-sm btn-outline-primary mt-2" target="_blank" href="{{ route('provider.job.labels', $s->public_id) }}"><i class="ri-printer-line"></i> Print labels</a>@endif
            </x-cb.card>
            <x-cb.card title="Timeline" icon="ri-time-line" :flush="true">
                <table class="table mb-0"><tbody>
                @forelse($timeline as $e)
                    <tr><td>{{ $e->seq }}</td><td>{{ str_replace('_', ' ', $e->type) }}</td><td class="text-muted small">{{ $e->actor_type }}</td><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($e->created_at)->format('d M H:i') }}</td></tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No events yet.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>
        <div class="col-lg-4">
            @if($assignable)
            <x-cb.card :title="$driver ? 'Change the driver' : 'Who will do this job?'" icon="ri-user-received-line" class="mb-3">
                @if($driver)<p class="mb-2">Now with <strong>{{ $driver->name }}</strong> ({{ $driver->status }}).</p>@endif
                @if(count($drivers))
                <form method="POST" action="{{ route('provider.job.assign', $s->public_id) }}">@csrf
                    <select name="driver_user_id" class="form-select mb-2" required>
                        <option value="">Choose a driver</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d['user_id'] }}" @disabled($driver && $driver->user_id == $d['user_id'])>{{ $d['name'] }} · {{ $d['active_jobs'] }}/{{ $d['max_active_jobs'] }} jobs · {{ str_replace('_', ' ', $d['availability']) }}</option>
                        @endforeach
                    </select>
                    @if($driver)<input name="reason" class="form-control mb-2" placeholder="Why the change? (optional)" maxlength="120">@endif
                    <button class="btn btn-primary w-100">{{ $driver ? 'Reassign' : 'Assign' }}</button>
                    <div class="form-text">The driver is told straight away. Any automatic offers for this job stop.</div>
                </form>
                @else
                    <p class="text-muted mb-0">No approved drivers yet. @if($op->type === 'company')Approve a driver on the Team page.@else Your own account must be approved before you can take jobs.@endif</p>
                @endif
            </x-cb.card>
            @endif

            @if($cancellable)
            <x-cb.card title="Can't do this job?" icon="ri-close-circle-line">
                <p class="small text-muted">You can back out until you have collected the goods. The customer is refunded in full and this counts against your cancellation record.</p>
                <form method="POST" action="{{ route('provider.job.cancel', $s->public_id) }}" onsubmit="return confirm('Cancel this job? The customer will be refunded.')">@csrf
                    <select name="reason" class="form-select mb-2" required>
                        <option value="">Choose a reason</option>
                        @foreach(['vehicle_problem' => 'Vehicle problem', 'unavailable' => 'No longer available', 'cannot_reach' => 'Cannot reach the address', 'other' => 'Other'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                    <button class="btn btn-outline-danger w-100">Cancel job</button>
                </form>
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
