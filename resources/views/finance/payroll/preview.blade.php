{{-- resources/views/finance/payroll/preview.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $naira = fn ($v) => '₦' . number_format((float) $v, 2);
    $c = $r['calc'] ?? null;
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v * 100, 2), '0'), '.') . '%';
    $R = ['pension' => 'Pension contributions', 'nhf' => 'NHF', 'nhia' => 'Health insurance', 'rent' => 'Rent relief', 'other' => 'Other allowed reliefs', 'cra' => 'Consolidated relief (old rules)'];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$s->name" icon="ri-calculator-line" :subtitle="'How pay is worked out for ' . $month->format('F Y')" :back="route('payroll.profiles.edit', $s->id)" back-label="Pay profile">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2"><input type="month" name="month" class="form-control form-control-sm" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" aria-label="Month"></form>
        </x-slot:actions>
    </x-cb.hero>

    @if(isset($r['skip']))
        <div class="cb-card"><div class="empty-state"><i class="ri-information-line"></i><h6>Not paid this month</h6><p>{{ $r['skip'] }}.</p></div></div>
    @else
        @foreach($r['warnings'] as $w)<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $w }}</div></div>@endforeach
        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6"><x-cb.stat label="Gross pay" :value="$naira($c['gross'])" icon="ri-money-dollar-circle-line" accent="teal" :hint="$c['proration'] < 1 ? ('Part month: ' . round($c['proration'] * 100) . '%') : null" /></div>
            <div class="col-md-3 col-6"><x-cb.stat label="PAYE" :value="$naira($c['paye'])" icon="ri-government-line" accent="violet" :hint="'Effective rate ' . $c['tax']['effective_rate'] . '%'" /></div>
            <div class="col-md-3 col-6"><x-cb.stat label="Take-home" :value="$naira($c['net'])" icon="ri-wallet-3-line" accent="green" /></div>
            <div class="col-md-3 col-6"><x-cb.stat label="Cost to school" :value="$naira($c['employer_cost'])" icon="ri-building-line" accent="amber" /></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <x-cb.card title="Payslip lines" icon="ri-file-list-3-line" :flush="true">
                    <table class="table table-sm align-middle mb-0"><tbody>
                        @foreach(['earning' => 'Earnings', 'deduction' => 'Deductions', 'employer' => 'Paid by the school (not deducted)'] as $type => $head)
                            @php $ls = collect($c['lines'])->where('type', $type); @endphp
                            @if($ls->isNotEmpty())
                                <tr class="table-light"><th colspan="2">{{ $head }}</th></tr>
                                @foreach($ls as $l)<tr><td>{{ $l['label'] }}</td><td class="text-end {{ $type === 'deduction' ? 'text-danger' : '' }}">{{ $naira($l['amount']) }}</td></tr>@endforeach
                            @endif
                        @endforeach
                        <tr class="fw-bold"><td>Take-home pay</td><td class="text-end">{{ $naira($c['net']) }}</td></tr>
                    </tbody></table>
                </x-cb.card>
            </div>
            <div class="col-lg-6">
                <x-cb.card title="How the tax was worked out ({{ $c['tax']['rule'] }})" icon="ri-government-line" :flush="true">
                    <table class="table table-sm align-middle mb-0"><tbody>
                        <tr><td>Taxable pay for the year (monthly × 12{{ $c['proration'] < 1 ? ', full-month basis' : '' }})</td><td class="text-end">{{ $naira($c['tax']['annual_gross']) }}</td></tr>
                        @foreach($c['tax']['reliefs'] as $k => $v)<tr><td class="ps-3 text-muted">less {{ $R[$k] ?? $k }}</td><td class="text-end text-muted">−{{ $naira($v) }}</td></tr>@endforeach
                        <tr class="fw-semibold"><td>Chargeable income</td><td class="text-end">{{ $naira($c['tax']['chargeable']) }}</td></tr>
                        @foreach($c['tax']['bands'] as $b)
                            <tr><td class="ps-3 small">{{ $naira($b['amount']) }} at {{ $pct($b['rate']) }} <span class="text-muted">({{ $naira($b['from']) }} – {{ $b['to'] === null ? 'above' : $naira($b['to']) }})</span></td><td class="text-end small">{{ $naira($b['tax']) }}</td></tr>
                        @endforeach
                        <tr class="fw-semibold"><td>Tax for the year</td><td class="text-end">{{ $naira($c['tax']['annual_tax']) }}</td></tr>
                        <tr class="fw-bold"><td>PAYE this month (÷ 12{{ $c['proration'] < 1 ? ' × part month' : '' }})</td><td class="text-end">{{ $naira($c['paye']) }}</td></tr>
                    </tbody></table>
                </x-cb.card>
            </div>
        </div>
    @endif
</div>
</div>
</div>
@endsection
