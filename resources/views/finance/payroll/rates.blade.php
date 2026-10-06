{{-- resources/views/finance/payroll/rates.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v * 100, 4), '0'), '.') . '%';
    $naira = fn ($v) => '₦' . number_format((float) $v);
    $C = \App\Models\PayrollStatutoryRate::CODES;
    $paye = $current['paye'] ?? [];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Payroll Rates & Tax Bands" icon="ri-percent-line" subtitle="PAYE bands, pension, NHF and other rates, each with the date it starts. Changing a rate never alters payroll months already approved." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="PAYE in force today" icon="ri-government-line">
                <div class="small text-muted mb-2">{{ $paye['code'] ?? '' }} — tax-free first band, rent relief {{ $pct($paye['rent_relief_rate'] ?? 0) }} of rent up to {{ $naira($paye['rent_relief_cap'] ?? 0) }}. Pension, NHF and NHIA contributions are deducted before tax.</div>
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Annual chargeable income</th><th class="text-end">Rate</th></tr></thead>
                    <tbody>
                    @php $from = 0; @endphp
                    @foreach($paye['bands'] ?? [] as $b)
                        <tr><td>{{ $naira($from) }} – {{ $b['to'] === null ? 'and above' : $naira($b['to']) }}</td><td class="text-end fw-bold">{{ $pct($b['rate']) }}</td></tr>
                        @php $from = $b['to']; @endphp
                    @endforeach
                    </tbody>
                </table>
            </x-cb.card>

            @foreach($groups as $code => $list)
                <x-cb.card :title="$C[$code] ?? $code" icon="ri-history-line" :flush="true">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Version</th><th>From</th><th>To</th><th>Settings</th></tr></thead>
                        <tbody>
                        @foreach($list as $r)
                            @php $cfg = $r->config; $isNow = $r->effective_from->lte(now()) && (!$r->effective_to || $r->effective_to->gte(now())); @endphp
                            <tr class="{{ $isNow ? 'table-active' : '' }}">
                                <td>{{ $r->name }} @if($isNow)<span class="status-pill st-paid">In force</span>@endif</td>
                                <td class="small">{{ $r->effective_from->format('d M Y') }}</td>
                                <td class="small">{{ $r->effective_to?->format('d M Y') ?? '—' }}</td>
                                <td class="small text-muted">
                                    @switch($code)
                                        @case('paye') {{ count($cfg['bands'] ?? []) }} bands, top rate {{ $pct(collect($cfg['bands'] ?? [])->last()['rate'] ?? 0) }} @break
                                        @case('pension') Staff {{ $pct($cfg['employee'] ?? 0) }} · School {{ $pct($cfg['employer'] ?? 0) }} @break
                                        @case('nhf') {{ $pct($cfg['rate'] ?? 0) }} of basic @break
                                        @case('nhia') Staff {{ $pct($cfg['employee'] ?? 0) }} · School {{ $pct($cfg['employer'] ?? 0) }} of {{ $cfg['base'] ?? 'basic' }} @break
                                        @case('limits') Min take-home {{ $pct($cfg['min_net_pct'] ?? 0) }} · Min wage {{ $naira($cfg['minimum_wage_monthly'] ?? 0) }} · Separate approver: {{ !empty($cfg['require_different_approver']) ? 'yes' : 'no' }} @break
                                        @default School {{ $pct($cfg['employer'] ?? 0) }} of gross
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </x-cb.card>
            @endforeach
        </div>

        <div class="col-xl-5">
            <x-cb.card title="Set a new rate" icon="ri-edit-2-line">
                <form method="POST" action="{{ route('payroll.rates.store') }}" id="rateForm">@csrf
                    <select name="code" class="form-select form-select-sm mb-2" id="rateCode" aria-label="What to change">
                        @foreach($C as $k => $l)<option value="{{ $k }}" @selected(old('code') === $k)>{{ $l }}</option>@endforeach
                    </select>
                    <div class="row g-2 mb-2">
                        <div class="col-7"><input name="name" class="form-control form-control-sm" placeholder="Name, e.g. Finance Act 2027" value="{{ old('name') }}" required aria-label="Name"></div>
                        <div class="col-5"><input name="effective_from" type="date" class="form-control form-control-sm" value="{{ old('effective_from', now()->startOfMonth()->toDateString()) }}" required aria-label="Starts"></div>
                    </div>

                    <div data-for="paye">
                        <div class="row g-2 mb-2">
                            <div class="col-4"><input name="rule_code" class="form-control form-control-sm" placeholder="Code" value="{{ old('rule_code', $paye['code'] ?? 'NTA2025') }}" aria-label="Rule code"></div>
                            <div class="col-4"><div class="input-group input-group-sm"><input name="rent_relief_rate" type="number" step="0.01" class="form-control" value="{{ old('rent_relief_rate', ($paye['rent_relief_rate'] ?? 0.2) * 100) }}" aria-label="Rent relief %"><span class="input-group-text">%</span></div><small class="text-muted">rent relief</small></div>
                            <div class="col-4"><input name="rent_relief_cap" type="number" class="form-control form-control-sm" value="{{ old('rent_relief_cap', $paye['rent_relief_cap'] ?? 500000) }}" aria-label="Rent relief cap"><small class="text-muted">relief cap ₦</small></div>
                        </div>
                        <table class="table table-sm mb-2" id="bandRows"><thead><tr><th>Up to (₦ a year)</th><th style="width:110px">Rate %</th></tr></thead><tbody>
                            @foreach(($paye['bands'] ?? []) as $b)
                                <tr><td><input name="band_to[]" class="form-control form-control-sm" value="{{ $b['to'] }}" placeholder="empty = no limit"></td><td><input name="band_rate[]" type="number" step="0.01" class="form-control form-control-sm" value="{{ $b['rate'] * 100 }}"></td></tr>
                            @endforeach
                        </tbody></table>
                        <button type="button" class="btn btn-sm btn-light mb-2" onclick="document.querySelector('#bandRows tbody').insertAdjacentHTML('beforeend','<tr><td><input name=&quot;band_to[]&quot; class=&quot;form-control form-control-sm&quot;></td><td><input name=&quot;band_rate[]&quot; type=&quot;number&quot; step=&quot;0.01&quot; class=&quot;form-control form-control-sm&quot;></td></tr>')">+ band</button>
                    </div>
                    <div data-for="pension nhia"><div class="row g-2 mb-2">
                        <div class="col-6"><label class="small">Staff %</label><input name="employee" type="number" step="0.01" class="form-control form-control-sm" value="{{ ($current['pension']['employee'] ?? 0.08) * 100 }}"></div>
                        <div class="col-6"><label class="small">School %</label><input name="employer" type="number" step="0.01" class="form-control form-control-sm" value="{{ ($current['pension']['employer'] ?? 0.10) * 100 }}"></div>
                    </div></div>
                    <div data-for="nhia"><select name="base" class="form-select form-select-sm mb-2"><option value="basic">% of basic</option><option value="gross">% of gross</option></select></div>
                    <div data-for="nhf"><label class="small">Rate % of basic</label><input name="rate" type="number" step="0.01" class="form-control form-control-sm mb-2" value="{{ ($current['nhf']['rate'] ?? 0.025) * 100 }}"></div>
                    <div data-for="nsitf itf"><label class="small">School % of gross pay</label><input name="employer" type="number" step="0.01" class="form-control form-control-sm mb-2" value="1"></div>
                    <div data-for="limits">
                        <label class="small">Minimum take-home (% of gross) after loans etc.</label><input name="min_net_pct" type="number" step="0.01" class="form-control form-control-sm mb-2" value="{{ ($current['limits']['min_net_pct'] ?? 0.3333) * 100 }}">
                        <label class="small">National minimum wage (₦ a month)</label><input name="minimum_wage_monthly" type="number" class="form-control form-control-sm mb-2" value="{{ $current['limits']['minimum_wage_monthly'] ?? 70000 }}">
                        <label class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="require_different_approver" value="1" @checked(!empty($current['limits']['require_different_approver']))> <span class="form-check-label">The person who prepares payroll cannot approve it</span></label>
                    </div>
                    <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save new version</button>
                    <div class="small text-muted mt-2">Saving ends the current version the day before the new start date. Check figures against the latest law or your tax adviser before saving.</div>
                </form>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
<script>
(function () {
    const sel = document.getElementById('rateCode');
    const show = () => document.querySelectorAll('#rateForm [data-for]').forEach(el => {
        const on = el.dataset.for.split(' ').includes(sel.value);
        el.style.display = on ? '' : 'none';
        el.querySelectorAll('input,select').forEach(i => i.disabled = !on);
    });
    sel.addEventListener('change', show); show();
})();
</script>
@endsection
