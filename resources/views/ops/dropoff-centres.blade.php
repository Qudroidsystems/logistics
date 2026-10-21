@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Drop-off centres" icon="ri-store-2-line" subtitle="Partner stores where senders hand in parcels and drivers collect them. Approve new stores, set what they earn, and record when you pay them." :back="route('ops.dashboard')" back-label="Operations">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ $centres->where('status', 'active')->count() }} active</span>
            <span class="cb-meta-pill">{{ count($applications) }} to review</span>
            <span class="cb-meta-pill"><a class="text-reset" href="{{ route('partners') }}" target="_blank">Public sign-up form</a></span>
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    <x-cb.card title="Applications to review" icon="ri-inbox-line" :count="count($applications)" class="mb-3">
        @forelse($applications as $a)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <div class="fw-semibold">{{ $a->business_name }} <span class="text-muted small">{{ \App\Http\Controllers\PartnerApplicationController::BUSINESS_TYPES[$a->business_type] ?? '' }}</span></div>
                        <div class="small">{{ $a->line1 }}, {{ $a->city }} {{ $a->subdivision }} {{ $a->postal_code }}</div>
                        <div class="small text-muted">{{ $a->contact_name }} · {{ $a->email }} · {{ \App\Support\Phone::display($a->phone) }}</div>
                        @if($a->opening_hours)<div class="small text-muted">Hours: {{ $a->opening_hours }}</div>@endif
                        @if($a->message)<div class="small mt-1 fst-italic">“{{ $a->message }}”</div>@endif
                    </div>
                    <div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($a->created_at)->diffForHumans() }}</div>
                </div>
                <form method="post" action="{{ route('ops.dropoff.approve', $a->id) }}" class="row g-2 mt-2 align-items-end">@csrf
                    <div class="col-md-3"><label class="form-label small mb-0">Store name</label><input name="name" class="form-control form-control-sm" value="{{ $a->business_name }}"></div>
                    <div class="col-md-2"><label class="form-label small mb-0">Latitude</label><input name="lat" class="form-control form-control-sm" value="{{ $a->lat }}" required></div>
                    <div class="col-md-2"><label class="form-label small mb-0">Longitude</label><input name="lng" class="form-control form-control-sm" value="{{ $a->lng }}" required></div>
                    <div class="col-md-2"><label class="form-label small mb-0">City</label><select name="city_id" class="form-select form-select-sm"><option value=""></option>@foreach($cities as $id => $n)<option value="{{ $id }}" @selected(strcasecmp($n, $a->city) === 0)>{{ $n }}</option>@endforeach</select></div>
                    <div class="col-md-1"><label class="form-label small mb-0">Per parcel</label><input name="commission_flat" class="form-control form-control-sm" placeholder="{{ Money::symbol() }}1.50"></div>
                    <div class="col-md-1"><label class="form-label small mb-0">+ % price</label><input name="commission_pct" class="form-control form-control-sm" placeholder="0"></div>
                    <div class="col-md-1"><button class="btn btn-sm btn-success w-100">Approve</button></div>
                </form>
                <form method="post" action="{{ route('ops.dropoff.decline', $a->id) }}" class="d-flex gap-2 mt-2" onsubmit="return confirm('Decline this application?')">@csrf
                    <input name="note" class="form-control form-control-sm" placeholder="Why (kept for the record)" maxlength="300" required style="max-width: 420px">
                    <button class="btn btn-sm btn-outline-danger">Decline</button>
                </form>
            </div>
        @empty
            <div class="text-muted small">No applications waiting.</div>
        @endforelse
    </x-cb.card>

    <x-cb.card title="Stores" icon="ri-store-2-line" :count="count($centres)" :flush="true" class="mb-3">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Store</th><th>Commission</th><th class="text-center">Expected</th><th class="text-center">At counter</th><th class="text-center">Handled</th><th class="text-end">Owed</th><th style="min-width: 260px"></th></tr></thead>
            <tbody>
            @forelse($centres as $c)
                <tr>
                    <td><div class="fw-semibold">{{ $c->name }} <span class="badge bg-light text-dark font-monospace">{{ $c->code }}</span> @if($c->status !== 'active')<span class="badge bg-warning-subtle text-warning">Paused</span>@endif</div>
                        <div class="small text-muted">{{ $c->line1 }}@if($c->city) · {{ $c->city }}@endif</div></td>
                    <td class="small">{{ \App\Modules\DropOff\DropOffService::describeCommission((int) $c->commission_flat, (int) $c->commission_bp) }}</td>
                    <td class="text-center">{{ $c->expected }}</td><td class="text-center">{{ $c->at_counter }}</td><td class="text-center">{{ $c->handled }}</td>
                    <td class="text-end fw-semibold">{{ Money::format((int) $c->owed, true) }}</td>
                    <td>
                        @if($c->owed > 0)
                        <form method="post" action="{{ route('ops.dropoff.paid', $c->id) }}" class="d-flex gap-1 mb-1" onsubmit="return confirm('Mark everything owed to this store as paid?')">@csrf
                            <input name="reference" class="form-control form-control-sm" placeholder="Payment ref (e-Transfer…)" required maxlength="60">
                            <button class="btn btn-sm btn-success text-nowrap">Paid</button>
                        </form>
                        @endif
                        <details class="small"><summary>Edit</summary>
                            <form method="post" action="{{ route('ops.dropoff.update', $c->id) }}" class="row g-1 mt-1">@csrf
                                <div class="col-12"><input name="name" class="form-control form-control-sm" value="{{ $c->name }}" required></div>
                                <div class="col-12"><input name="line1" class="form-control form-control-sm" value="{{ $c->line1 }}" required></div>
                                <div class="col-6"><input name="lat" class="form-control form-control-sm" value="{{ $c->lat }}" required title="Latitude"></div>
                                <div class="col-6"><input name="lng" class="form-control form-control-sm" value="{{ $c->lng }}" required title="Longitude"></div>
                                <div class="col-12"><input name="opening_hours" class="form-control form-control-sm" value="{{ $c->opening_hours }}" placeholder="Opening hours"></div>
                                <div class="col-6"><input name="phone" class="form-control form-control-sm" value="{{ $c->phone }}" placeholder="Phone"></div>
                                <div class="col-6"><select name="city_id" class="form-select form-select-sm"><option value=""></option>@foreach($cities as $id => $n)<option value="{{ $id }}" @selected($c->city_id == $id)>{{ $n }}</option>@endforeach</select></div>
                                <div class="col-6"><input name="commission_flat" class="form-control form-control-sm" value="{{ number_format($c->commission_flat / 100, 2, '.', '') }}" title="Per parcel"></div>
                                <div class="col-6"><input name="commission_pct" class="form-control form-control-sm" value="{{ $c->commission_bp / 100 }}" title="% of delivery price"></div>
                                <div class="col-6"><select name="status" class="form-select form-select-sm"><option value="active" @selected($c->status === 'active')>Active</option><option value="paused" @selected($c->status === 'paused')>Paused</option></select></div>
                                <div class="col-6"><button class="btn btn-sm btn-primary w-100">Save</button></div>
                            </form>
                            <form method="post" action="{{ route('ops.dropoff.staff', $c->id) }}" class="d-flex gap-1 mt-1">@csrf
                                <input name="email" type="email" class="form-control form-control-sm" placeholder="Add staff by account email" required>
                                <button class="btn btn-sm btn-outline-secondary">Add</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No stores yet. Approve an application above.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    @if(count($decided))
    <x-cb.card title="Recently decided" icon="ri-history-line" :flush="true">
        <div class="table-responsive"><table class="table table-sm mb-0">
            @foreach($decided as $a)<tr><td>{{ $a->business_name }}</td><td class="small">{{ $a->city }}</td><td><span class="badge {{ $a->status === 'approved' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ ucfirst($a->status) }}</span></td><td class="small text-muted">{{ $a->decision_note }}</td><td class="small text-muted">{{ $a->decided_at ? \Illuminate\Support\Carbon::parse($a->decided_at)->diffForHumans() : '' }}</td></tr>@endforeach
        </table></div>
    </x-cb.card>
    @endif
</div></div></div>
@endsection
