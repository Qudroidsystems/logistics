@extends('layouts.master')

@php $naira = fn ($k) => $k === null ? '—' : Money::symbol().number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Promo codes" icon="ri-coupon-3-line" subtitle="Codes your customers type before paying. They only work on your jobs, and the discount comes out of what you are paid for the job." />
    @include('provider._flash')

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Your codes" icon="ri-coupon-3-line" :count="count($promos)" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Code</th><th>Discount</th><th>Limits</th><th>Valid</th><th class="text-end">Used</th><th></th></tr></thead>
                    <tbody>
                    @forelse($promos as $p)
                        <tr class="{{ $p->active ? '' : 'text-muted' }}">
                            <td class="fw-semibold">{{ $p->code }} @unless($p->active)<span class="badge bg-light text-dark">paused</span>@endunless</td>
                            <td class="small">{{ $types[$p->type] ?? $p->type }}: {{ $p->type === 'fixed' ? $naira($p->value) : ($p->type === 'free_delivery' ? 'whole delivery price' : rtrim(rtrim(number_format($p->value / 100, 2), '0'), '.').'%') }}@if($p->cap !== null) (max {{ $naira($p->cap) }})@endif</td>
                            <td class="small">{{ $p->per_user_limit }} per customer{{ $p->usage_limit ? ', '.$p->usage_limit.' in total' : '' }}{{ $p->min_order !== null ? ', min '.$naira($p->min_order) : '' }}{{ $p->budget !== null ? ', budget '.$naira($p->budget) : '' }}</td>
                            <td class="small">{{ $p->valid_from ? \Illuminate\Support\Carbon::parse($p->valid_from)->format('d M') : 'now' }} to {{ $p->valid_to ? \Illuminate\Support\Carbon::parse($p->valid_to)->format('d M Y') : 'no end' }}</td>
                            <td class="text-end">{{ $p->used }}<div class="small text-muted">{{ $naira($p->given) }} off</div></td>
                            <td class="text-end"><form method="POST" action="{{ route('provider.promos.toggle', $p->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $p->active ? 'Pause' : 'Switch on' }}</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No codes yet. Create one to bring customers back.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </x-cb.card>
        </div>
        <div class="col-lg-4">
            <x-cb.card title="New code" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('provider.promos.store') }}" class="row g-2">@csrf
                    <div class="col-12"><label class="form-label small">Code</label><input name="code" class="form-control text-uppercase" maxlength="32" required value="{{ old('code') }}" placeholder="SAVE500"></div>
                    <div class="col-12"><label class="form-label small">Kind</label><select name="type" class="form-select">@foreach($types as $k => $v)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label small">Percent or {{ Money::symbol() }} amount</label><input name="value" type="number" step="0.01" min="0" class="form-control" value="{{ old('value') }}"></div>
                    <div class="col-6"><label class="form-label small">Most off one job ({{ Money::symbol() }})</label><input name="cap_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('cap_naira') }}"></div>
                    <div class="col-6"><label class="form-label small">Minimum price ({{ Money::symbol() }})</label><input name="min_order_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('min_order_naira') }}"></div>
                    <div class="col-6"><label class="form-label small">Budget ({{ Money::symbol() }})</label><input name="budget_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('budget_naira') }}"></div>
                    <div class="col-6"><label class="form-label small">Uses per customer</label><input name="per_user_limit" type="number" min="1" class="form-control" value="{{ old('per_user_limit', 1) }}"></div>
                    <div class="col-6"><label class="form-label small">Total uses</label><input name="usage_limit" type="number" min="1" class="form-control" value="{{ old('usage_limit') }}"></div>
                    <div class="col-6"><label class="form-label small">Starts</label><input name="valid_from" type="datetime-local" class="form-control" value="{{ old('valid_from') }}"></div>
                    <div class="col-6"><label class="form-label small">Ends</label><input name="valid_to" type="datetime-local" class="form-control" value="{{ old('valid_to') }}"></div>
                    <div class="col-12"><button class="btn btn-primary w-100">Create code</button></div>
                </form>
                <p class="small text-muted mt-2 mb-0">A discount never takes more than your share of a job. The platform fee is still worked out on the agreed price.</p>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
