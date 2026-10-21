@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => $k === null ? '' : Money::symbol().number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun']; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$p->name" icon="ri-price-tag-3-line" :subtitle="$p->provider.' · '.$p->service.' · '.($p->city ?: 'all cities')" :back="route('ops.price-lists')" back-label="Price lists">
        <x-slot:pills><span class="cb-meta-pill">{{ $p->status }}</span><span class="cb-meta-pill">v{{ $p->version }}</span><span class="cb-meta-pill">min {{ $naira($p->min_fee) }}</span></x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')
    <form method="POST" action="{{ route('ops.price-list.status', $p->id) }}" class="mb-3 d-flex gap-2">@csrf
        @foreach(['active' => 'Switch on', 'draft' => 'Back to draft', 'retired' => 'Retire'] as $st => $label)@if($p->status !== $st)<button name="status" value="{{ $st }}" class="btn btn-sm {{ $st === 'active' ? 'btn-success' : 'btn-outline-secondary' }}">{{ $label }}</button>@endif @endforeach
    </form>
    <x-cb.card title="Rules" icon="ri-list-settings-line" :count="count($rules)" :flush="true">
        <table class="table align-middle mb-0"><thead><tr><th>Rule</th><th>Amount</th><th>Rate</th><th>When</th><th class="text-end">Order</th><th></th></tr></thead><tbody>
        @forelse($rules as $r)
            @php $c = $r->condition ? json_decode($r->condition, true) : []; @endphp
            <tr><td>{{ $kinds[$r->kind] ?? $r->kind }}</td><td>{{ $naira($r->amount) }}</td><td>{{ $r->rate_bp ? rtrim(rtrim(number_format($r->rate_bp / 100, 2), '0'), '.').'%' : '' }}</td>
                <td class="small">{{ isset($c['from_km']) || isset($c['to_km']) ? ($c['from_km'] ?? 0).' to '.($c['to_km'] ?? '∞').' km' : '' }}{{ isset($c['hours']) ? 'hours '.implode(', ', $c['hours']) : '' }}{{ isset($c['days']) ? implode(', ', array_map(fn ($d) => $dayNames[$d] ?? $d, $c['days'])) : ($r->kind === 'weekend' ? 'Sat, Sun' : '') }}{{ isset($c['sizes']) ? implode(', ', $c['sizes']) : '' }}{{ isset($c['vehicles']) ? implode(', ', $c['vehicles']) : '' }}</td>
                <td class="text-end">{{ $r->priority }}</td>
                <td class="text-end"><form method="POST" action="{{ route('ops.price-list.rule.delete', [$p->id, $r->id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No rules yet. Start with a base fee and a per-km rate.</td></tr>
        @endforelse
        </tbody></table>
    </x-cb.card>
    <x-cb.card title="Add a rule" icon="ri-add-circle-line" class="mt-3">
        <p class="small text-muted">Amounts are added first; percentage surcharges (night, peak hour, weekend, rush, fuel) then apply to the running total in the order given. Rush applies only when the customer asks for a rush job.</p>
        <form method="POST" action="{{ route('ops.price-list.rule', $p->id) }}" class="row g-2">@csrf
            <div class="col-md-4"><select name="kind" class="form-select">@foreach($kinds as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-2"><input name="amount_naira" type="number" step="0.01" min="0" class="form-control" placeholder="{{ Money::symbol() }} amount"></div>
            <div class="col-md-2"><input name="rate" type="number" step="0.01" min="0" class="form-control" placeholder="% rate"></div>
            <div class="col-md-2"><input name="priority" type="number" min="0" max="100" class="form-control" placeholder="Order" value="0"></div>
            <div class="col-md-1"><input name="from_km" type="number" step="0.1" min="0" class="form-control" placeholder="From km"></div>
            <div class="col-md-1"><input name="to_km" type="number" step="0.1" min="0" class="form-control" placeholder="To km"></div>
            <div class="col-md-4"><input name="hours" class="form-control" placeholder="Hours for night/peak, e.g. 21,22,23,0,1"></div>
            <div class="col-md-6 d-flex align-items-center gap-2 small">Weekend days: @foreach($dayNames as $n => $d)<label><input type="checkbox" name="days[]" value="{{ $n }}" @checked($n >= 6)> {{ $d }}</label>@endforeach</div>
            <div class="col-md-6 d-flex align-items-center gap-2 small">Item sizes: @foreach(\App\Modules\Booking\InstantQuoteService::sizes() as $k => $s)<label><input type="checkbox" name="sizes[]" value="{{ $k }}"> {{ $s['label'] }}</label>@endforeach</div>
            <div class="col-md-6 d-flex align-items-center gap-2 small flex-wrap">Vehicles: @foreach(\Illuminate\Support\Facades\DB::table('vehicle_types')->where('active', true)->orderBy('id')->pluck('name', 'code') as $code => $name)<label><input type="checkbox" name="vehicles[]" value="{{ $code }}"> {{ $name }}</label>@endforeach</div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Add rule</button></div>
        </form>
    </x-cb.card>
</div></div></div>
@endsection
