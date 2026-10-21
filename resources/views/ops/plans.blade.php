@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Business plans" icon="ri-vip-crown-2-line" subtitle="Monthly plans with deliveries included. Fees come from customers' wallets on the day the month starts; edits apply from each customer's next renewal." :back="route('ops.dashboard')" back-label="Operations">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ (int) ($counts['active'] ?? 0) }} active</span>
            <span class="cb-meta-pill">{{ (int) ($counts['past_due'] ?? 0) }} past due</span>
            <span class="cb-meta-pill">{{ Money::format($mrr, true) }} a month</span>
            <span class="cb-meta-pill"><a class="text-reset" href="{{ route('business') }}" target="_blank">Public prices page</a></span>
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    <div class="row g-3 mb-3">
        @foreach($plans as $p)
            <div class="col-lg-4">
                <x-cb.card :title="$p['name'].($p['active'] ? '' : ' (hidden)')" icon="ri-vip-crown-line" class="h-100">
                    <form method="POST" action="{{ route('ops.plan.update', $p['id']) }}" class="row g-2">@csrf
                        <div class="col-8"><label class="form-label small mb-0">Name</label><input name="name" class="form-control form-control-sm" value="{{ $p['name'] }}" required></div>
                        <div class="col-4"><label class="form-label small mb-0">Order</label><input name="sort" type="number" class="form-control form-control-sm" value="{{ $loop->index + 1 }}"></div>
                        <div class="col-12"><label class="form-label small mb-0">Tagline</label><input name="tagline" class="form-control form-control-sm" value="{{ $p['tagline'] }}"></div>
                        <div class="col-6"><label class="form-label small mb-0">Price / month ({{ Money::symbol() }})</label><input name="price" class="form-control form-control-sm" value="{{ number_format($p['price'] / 100, 2, '.', '') }}" required></div>
                        <div class="col-6"><label class="form-label small mb-0">Deliveries included</label><input name="included_deliveries" type="number" class="form-control form-control-sm" value="{{ $p['included'] }}" required></div>
                        <div class="col-6"><label class="form-label small mb-0">Each extra ({{ Money::symbol() }})</label><input name="overage_price" class="form-control form-control-sm" value="{{ $p['overage_price'] !== null ? number_format($p['overage_price'] / 100, 2, '.', '') : '' }}" placeholder="normal price"></div>
                        <div class="col-6"><label class="form-label small mb-0">Covers up to (km)</label><input name="max_km" type="number" class="form-control form-control-sm" value="{{ $p['max_km'] }}" placeholder="any distance"></div>
                        <div class="col-12"><label class="form-label small mb-0">Features (one per line)</label><textarea name="features" rows="3" class="form-control form-control-sm">{{ implode("\n", $p['features']) }}</textarea></div>
                        <div class="col-12 small">
                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="priority_dispatch" value="1" id="pd{{ $p['id'] }}" @checked($p['priority'])><label class="form-check-label" for="pd{{ $p['id'] }}">Priority dispatch</label></div>
                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="contact_only" value="1" id="co{{ $p['id'] }}" @checked($p['contact_only'])><label class="form-check-label" for="co{{ $p['id'] }}">"Talk to us" only</label></div>
                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="active" value="1" id="ac{{ $p['id'] }}" @checked($p['active'])><label class="form-check-label" for="ac{{ $p['id'] }}">On sale</label></div>
                        </div>
                        <div class="col-12"><button class="btn btn-sm btn-primary w-100">Save {{ $p['name'] }}</button></div>
                    </form>
                </x-cb.card>
            </div>
        @endforeach
    </div>

    <x-cb.card title="Customers on a plan" icon="ri-team-line" :count="count($subs)" :flush="true">
        <x-slot:tools>
            @foreach(['live' => 'Active and past due', 'cancelled' => 'Cancelled', 'all' => 'All'] as $k => $v)
                <a href="{{ route('ops.plans', ['status' => $k]) }}" class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-light' }}">{{ $v }}</a>
            @endforeach
        </x-slot:tools>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Customer</th><th>Plan</th><th>Status</th><th class="text-center">Used</th><th>Month ends</th><th class="text-end">Fee</th></tr></thead>
            <tbody>
            @forelse($subs as $s)
                <tr>
                    <td>{{ $s->customer }}<div class="small text-muted">{{ $s->email }}</div></td>
                    <td>{{ $s->plan }}@if($s->cancel_at_period_end)<div class="small text-muted">Ends at month end</div>@endif</td>
                    <td><span class="badge {{ ['active' => 'bg-success-subtle text-success', 'past_due' => 'bg-warning-subtle text-warning', 'cancelled' => 'bg-light text-muted'][$s->status] ?? 'bg-light' }}">{{ str_replace('_', ' ', ucfirst($s->status)) }}</span></td>
                    <td class="text-center">{{ $s->used }} / {{ $s->period_included }}</td>
                    <td class="small">{{ \Illuminate\Support\Carbon::parse($s->current_period_end)->format('j M Y') }}</td>
                    <td class="text-end">{{ Money::format((int) $s->period_price, true) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No one yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
