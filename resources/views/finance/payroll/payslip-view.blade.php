{{-- resources/views/finance/payroll/payslip-view.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $q = $q ?? []; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Payslip — ' . $run->period_name" icon="ri-file-list-3-line" :subtitle="($staff->name ?? '') . ' · ' . ($staff->employmentid ?? '')"
               :back="$admin ? route('payroll.month.show', $run->payroll_period_id) : route('my-pay.index', $q)" :back-label="$admin ? 'Payroll month' : 'My Pay'">
        <x-slot:actions>
            <a href="{{ $admin ? route('payroll.month.payslip.pdf', $run->id) : route('my-pay.payslip.pdf', ['run' => $run->id] + $q) }}" target="_blank" class="cb-hero-btn"><i class="ri-file-pdf-2-line"></i>PDF</a>
            @unless($admin)<a href="{{ route('my-pay.payslip.pdf', ['run' => $run->id, 'download' => 1] + $q) }}" class="cb-hero-btn"><i class="ri-download-2-line"></i>Download</a>@endunless
        </x-slot:actions>
    </x-cb.hero>

    @unless($final)<div class="cb-banner warning"><i class="ri-time-line"></i><div>Provisional — this month isn't locked yet, so figures may still change.</div></div>@endunless

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Gross pay" :value="$m($earnings->sum('amount'))" icon="ri-money-dollar-circle-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Deductions" :value="$m($deductions->sum('amount'))" icon="ri-subtract-line" accent="rose" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Net pay" :value="$m($run->net_pay)" icon="ri-wallet-3-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="PAYE this year" :value="$m($ytd['paye'])" icon="ri-government-line" accent="violet" /></div>
    </div>
    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Earnings" icon="ri-add-circle-line" :flush="true"><table class="table table-sm mb-0"><tbody>
                @foreach($earnings as $l)<tr><td>{{ $l->label }}</td><td class="text-end">{{ $m($l->amount) }}</td></tr>@endforeach
                <tr class="fw-bold"><td>Gross pay</td><td class="text-end">{{ $m($earnings->sum('amount')) }}</td></tr>
            </tbody></table></x-cb.card>
            @if($employerLines->isNotEmpty())
                <x-cb.card title="Paid by the school for you" icon="ri-building-line" :flush="true"><table class="table table-sm mb-0"><tbody>
                    @foreach($employerLines as $l)<tr><td>{{ $l->label }}</td><td class="text-end">{{ $m($l->amount) }}</td></tr>@endforeach
                </tbody></table></x-cb.card>
            @endif
        </div>
        <div class="col-lg-6">
            <x-cb.card title="Deductions" icon="ri-indeterminate-circle-line" :flush="true"><table class="table table-sm mb-0"><tbody>
                @forelse($deductions as $l)<tr><td>{{ $l->label }}</td><td class="text-end text-danger">{{ $m($l->amount) }}</td></tr>@empty<tr><td class="text-muted">None</td></tr>@endforelse
                <tr class="fw-bold"><td>Net pay</td><td class="text-end">{{ $m($run->net_pay) }}</td></tr>
            </tbody></table></x-cb.card>
            @if($tax)
                <x-cb.card title="How PAYE was worked out" icon="ri-government-line" :flush="true"><table class="table table-sm mb-0"><tbody>
                    <tr><td>Taxable pay for the year</td><td class="text-end">{{ $m($tax['annual_gross'] ?? 0) }}</td></tr>
                    @foreach(($tax['reliefs'] ?? []) as $k => $val)<tr class="text-muted"><td class="ps-3">less {{ ['pension' => 'pension', 'nhf' => 'NHF', 'nhia' => 'health insurance', 'rent' => 'rent relief', 'other' => 'other reliefs', 'cra' => 'CRA'][$k] ?? $k }}</td><td class="text-end">−{{ $m($val) }}</td></tr>@endforeach
                    <tr><td>Chargeable income</td><td class="text-end">{{ $m($tax['chargeable'] ?? 0) }}</td></tr>
                    @foreach(($tax['bands'] ?? []) as $b)<tr class="small"><td class="ps-3">{{ $m($b['amount']) }} at {{ rtrim(rtrim(number_format($b['rate'] * 100, 2), '0'), '.') }}%</td><td class="text-end">{{ $m($b['tax']) }}</td></tr>@endforeach
                    <tr><td>Tax for the year</td><td class="text-end">{{ $m($tax['annual_tax'] ?? 0) }}</td></tr>
                    <tr class="fw-bold"><td>PAYE this month</td><td class="text-end">{{ $m($run->paye_tax) }}</td></tr>
                </tbody></table></x-cb.card>
            @endif
        </div>
    </div>
    @if($verifyUrl)<div class="small text-muted">Verification code: {{ $run->verify_code }}</div>@endif
</div>
</div>
</div>
@endsection
