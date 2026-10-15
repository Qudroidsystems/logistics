@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => $k === null ? '—' : '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); $market = \App\Support\Platform::marketplace(); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Promo codes and discounts" icon="ri-coupon-3-line" subtitle="Codes customers type before paying, and standing discounts for business customers. The platform pays for both; providers still receive the agreed price." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <x-cb.card title="Promo codes" icon="ri-coupon-3-line" :count="count($promos)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Code</th><th>Discount</th><th>Limits</th>@if($market)<th>Provider</th>@endif<th>Valid</th><th class="text-end">Used</th><th></th></tr></thead>
            <tbody>
            @forelse($promos as $p)
                <tr class="{{ $p->active ? '' : 'text-muted' }}">
                    <td class="fw-semibold">{{ $p->code }} @unless($p->active)<span class="badge bg-light text-dark">paused</span>@endunless</td>
                    <td class="small">{{ $types[$p->type] ?? $p->type }}: {{ $p->type === 'fixed' ? $naira($p->value) : ($p->type === 'free_delivery' ? 'whole delivery price' : rtrim(rtrim(number_format($p->value / 100, 2), '0'), '.').'%') }}@if($p->cap !== null) (max {{ $naira($p->cap) }})@endif</td>
                    <td class="small">{{ $p->per_user_limit }} per customer{{ $p->usage_limit ? ', '.$p->usage_limit.' in total' : '' }}{{ $p->min_order !== null ? ', min '.$naira($p->min_order) : '' }}{{ $p->budget !== null ? ', budget '.$naira($p->budget).' ('.$naira($p->budget_spent).' spent)' : '' }}</td>
                    @if($market)<td class="small">{{ $p->provider ?: 'Any' }}</td>@endif
                    <td class="small">{{ $p->valid_from ? \Illuminate\Support\Carbon::parse($p->valid_from)->format('d M') : 'now' }} to {{ $p->valid_to ? \Illuminate\Support\Carbon::parse($p->valid_to)->format('d M Y') : 'no end' }}</td>
                    <td class="text-end">{{ $p->used }}</td>
                    <td class="text-end">@can('Update promo code')<form method="POST" action="{{ route('ops.promo.toggle', $p->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $p->active ? 'Pause' : 'Switch on' }}</button></form>@endcan</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No promo codes yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    @can('Create promo code')
    <div class="row g-3 mt-1">
    <div class="col-lg-6">
    <x-cb.card title="New promo code" icon="ri-add-circle-line">
        <form method="POST" action="{{ route('ops.promo.store') }}" class="row g-2">@csrf
            <div class="col-md-6"><label class="form-label small">Code</label><input name="code" class="form-control text-uppercase" maxlength="32" required value="{{ old('code') }}" placeholder="WELCOME10"></div>
            <div class="col-md-6"><label class="form-label small">Kind</label><select name="type" class="form-select">@foreach($types as $k => $v)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label small">Percent or ₦ amount</label><input name="value" type="number" step="0.01" min="0" class="form-control" value="{{ old('value') }}"></div>
            <div class="col-md-4"><label class="form-label small">Most off one job (₦)</label><input name="cap_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('cap_naira') }}"></div>
            <div class="col-md-4"><label class="form-label small">Minimum price (₦)</label><input name="min_order_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('min_order_naira') }}"></div>
            <div class="col-md-4"><label class="form-label small">Uses per customer</label><input name="per_user_limit" type="number" min="1" class="form-control" value="{{ old('per_user_limit', 1) }}"></div>
            <div class="col-md-4"><label class="form-label small">Total uses</label><input name="usage_limit" type="number" min="1" class="form-control" value="{{ old('usage_limit') }}"></div>
            <div class="col-md-4"><label class="form-label small">Budget (₦)</label><input name="budget_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('budget_naira') }}"></div>
            <div class="col-md-6"><label class="form-label small">Starts</label><input name="valid_from" type="datetime-local" class="form-control" value="{{ old('valid_from') }}"></div>
            <div class="col-md-6"><label class="form-label small">Ends</label><input name="valid_to" type="datetime-local" class="form-control" value="{{ old('valid_to') }}"></div>
            @if($market)<div class="col-12"><label class="form-label small">Only with this provider (optional)</label><select name="operator_id" class="form-select"><option value="">Any provider</option>@foreach($operators as $o)<option value="{{ $o->id }}">{{ $o->display_name }}</option>@endforeach</select></div>@endif
            <div class="col-12"><button class="btn btn-primary">Create code</button></div>
        </form>
    </x-cb.card>
    </div>
    <div class="col-lg-6">
    <x-cb.card title="Corporate discounts" icon="ri-building-4-line" :count="count($corporate)">
        <table class="table table-sm align-middle">
            @forelse($corporate as $c)
                <tr class="{{ $c->active ? '' : 'text-muted' }}"><td><div class="fw-semibold">{{ $c->name }}</div><div class="small">{{ $c->merchant ?: $c->user_email }}{{ $market && $c->provider ? ' · with '.$c->provider : '' }}</div></td>
                    <td class="text-end">{{ rtrim(rtrim(number_format($c->rate_bp / 100, 2), '0'), '.') }}%@if($c->cap !== null)<div class="small text-muted">max {{ $naira($c->cap) }}</div>@endif</td>
                    <td class="text-end">@can('Update promo code')<form method="POST" action="{{ route('ops.corporate.toggle', $c->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $c->active ? 'Pause' : 'Switch on' }}</button></form>@endcan</td></tr>
            @empty
                <tr><td class="text-muted">None yet. A corporate discount comes off every delivery for that business or customer, automatically.</td></tr>
            @endforelse
        </table>
        <form method="POST" action="{{ route('ops.corporate.store') }}" class="row g-2">@csrf
            <div class="col-md-8"><input name="name" class="form-control" placeholder="Name, e.g. Acme Ltd account" required value="{{ old('name') }}"></div>
            <div class="col-md-4"><input name="rate" type="number" step="0.01" min="0.01" max="100" class="form-control" placeholder="% off" required value="{{ old('rate') }}"></div>
            <div class="col-md-6"><select name="merchant_id" class="form-select"><option value="">Business account (API merchant)…</option>@foreach($merchants as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><input name="user_email" type="email" class="form-control" placeholder="or a customer's email" value="{{ old('user_email') }}"></div>
            <div class="col-md-6"><input name="cap_naira" type="number" step="0.01" min="0" class="form-control" placeholder="Most off one job (₦)"></div>
            <div class="col-md-6"><input name="valid_to" type="date" class="form-control" title="Ends (optional)"></div>
            @if($market)<div class="col-12"><select name="operator_id" class="form-select"><option value="">With any provider</option>@foreach($operators as $o)<option value="{{ $o->id }}">{{ $o->display_name }}</option>@endforeach</select></div>@endif
            <div class="col-12"><button class="btn btn-outline-primary">Add corporate discount</button></div>
        </form>
    </x-cb.card>
    </div>
    </div>
    @endcan
</div></div></div>
@endsection
