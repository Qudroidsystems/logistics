@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Pickup points" icon="ri-store-2-line" subtitle="Shops or offices where your riders leave parcels and receivers collect them with their delivery code." />
    @include('provider._flash')

    <x-cb.card title="Parcels waiting to be collected" icon="ri-inbox-archive-line" class="mb-3" :flush="true">
        <table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th>Point</th><th>Receiver</th><th>Keep until</th><th style="min-width:320px">Hand over</th></tr></thead>
            <tbody>
            @forelse($waiting as $w)
                <tr>
                    <td><a href="{{ route('provider.job', $w['public_id']) }}">{{ $w['order_number'] }}</a><div class="small text-muted">{{ $w['tracking_code'] }}</div></td>
                    <td>{{ $w['point'] }} <span class="text-muted small">{{ $w['code'] }}</span></td>
                    <td>{{ $w['contact_name'] ?: 'Receiver' }}<div class="small text-muted">{{ $w['contact_phone'] }}</div></td>
                    <td>{{ \Illuminate\Support\Carbon::parse($w['hold_until'])->format('j M') }}@if($w['overdue']) <span class="badge bg-danger">past</span>@endif</td>
                    <td>
                        <form method="POST" action="{{ route('provider.points.collect', $w['public_id']) }}" class="d-flex gap-1">@csrf
                            <input name="code" class="form-control form-control-sm" placeholder="Receiver's code" inputmode="numeric" maxlength="12" required style="max-width:130px">
                            <input name="receiver_name" class="form-control form-control-sm" placeholder="Name (optional)" maxlength="120">
                            <button class="btn btn-sm btn-success">Hand over</button>
                        </form>
                        @if($w['overdue'])
                            <form method="POST" action="{{ route('provider.points.not_collected', $w['public_id']) }}" class="mt-1" onsubmit="return confirm('Close this delivery as not collected? The failed-delivery terms in the agreement apply.')">@csrf
                                <button class="btn btn-sm btn-link text-danger p-0">Nobody came: close as not collected</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No parcels are waiting at your points.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-cb.card>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Your points" icon="ri-map-pin-line" :flush="true">
                <table class="table align-middle mb-0"><tbody>
                @forelse($points as $p)
                    <tr>
                        <td>
                            <strong>{{ $p->name }}</strong> <code>{{ $p->code }}</code> @if($p->status !== 'active')<span class="badge bg-secondary">paused</span>@endif
                            <div class="small text-muted">{{ $p->line1 }}@if($p->opening_hours) · {{ $p->opening_hours }}@endif · keeps parcels {{ $p->hold_days }} days</div>
                            <details class="mt-1"><summary class="small">Edit</summary>
                                <form method="POST" action="{{ route('provider.points.update', $p->public_id) }}" class="row g-1 mt-1">@csrf @method('PUT')
                                    @include('provider._pickup-point-fields', ['v' => $p])
                                    <div class="col-12"><button class="btn btn-sm btn-primary">Save</button></div>
                                </form>
                            </details>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('provider.points.status', $p->public_id) }}">@csrf
                                <input type="hidden" name="active" value="{{ $p->status === 'active' ? 0 : 1 }}">
                                <button class="btn btn-sm btn-outline-secondary">{{ $p->status === 'active' ? 'Pause' : 'Resume' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No pickup points yet. Add your first one.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>
        <div class="col-lg-5">
            <x-cb.card title="Add a pickup point" icon="ri-add-line" class="mb-3">
                <form method="POST" action="{{ route('provider.points.store') }}" class="row g-1">@csrf
                    @include('provider._pickup-point-fields', ['v' => null])
                    <div class="col-12"><button class="btn btn-primary w-100 mt-1">Add point</button></div>
                </form>
            </x-cb.card>
            <x-cb.card title="How it works" icon="ri-information-line">
                <ul class="small mb-0">
                    <li>On a new booking or in a bulk sheet (column <code>pickup_point</code>, the point's code), choose a point instead of the receiver's address.</li>
                    <li>The rider leaves the parcel with a photo. The receiver is texted the point's address and a code that lasts as long as the parcel is kept.</li>
                    <li>When they come, type their code here. That completes the delivery.</li>
                    <li>If nobody comes in time, close it as not collected (the agreement's failed-delivery terms apply), then book a return from the job page.</li>
                </ul>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
