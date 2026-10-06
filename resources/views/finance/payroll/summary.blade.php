{{-- resources/views/finance/payroll/summary.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $t = $totals; $max = max(1, $rows->max(fn ($r) => (float) ($r->total_employer_cost ?: $r->total_gross_pay))); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Payroll Summary" icon="ri-bar-chart-2-line" :subtitle="'Approved and locked months in ' . $year">
        <x-slot:actions>
            <form method="GET"><select name="year" class="cb-select" onchange="this.form.submit()" aria-label="Year">@foreach($years as $y)<option @selected($y == $year)>{{ $y }}</option>@endforeach</select></form>
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Total cost to school" :value="$m($t['cost'])" icon="ri-building-line" accent="rose" :hint="$prevCost > 0 ? (($t['cost'] >= $prevCost ? '+' : '') . round(($t['cost'] - $prevCost) / $prevCost * 100, 1) . '% vs ' . ($year - 1)) : null" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Gross pay" :value="$m($t['gross'])" icon="ri-money-dollar-circle-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Net pay to staff" :value="$m($t['net'])" icon="ri-wallet-3-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="PAYE + pension + NHF" :value="$m($t['paye'] + $t['pension_ee'] + $t['pension_er'] + $t['nhf'])" icon="ri-government-line" accent="violet" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Month by month" icon="ri-calendar-line" :count="$rows->count()" :flush="true">
                @if($rows->isEmpty())
                    <div class="empty-state"><i class="ri-bar-chart-2-line"></i><h6>No approved months in {{ $year }}</h6></div>
                @else
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Month</th><th style="min-width:140px">Cost to school</th><th class="text-end">Gross</th><th class="text-end">PAYE</th><th class="text-end">Pension (staff/school)</th><th class="text-end">NHF</th><th class="text-end">Net</th></tr></thead>
                        <tbody>
                        @foreach($rows as $r)
                            @php $cost = (float) ($r->total_employer_cost ?: $r->total_gross_pay); @endphp
                            <tr>
                                <td><a href="{{ route('payroll.month.show', $r) }}">{{ $r->period_name }}</a></td>
                                <td><div class="d-flex align-items-center gap-2"><div class="progress-track flex-grow-1" title="{{ $m($cost) }}"><div class="progress-fill" style="width:{{ round($cost / $max * 100) }}%"></div></div><small>{{ number_format($cost / 1000) }}k</small></div></td>
                                <td class="text-end">{{ $m($r->total_gross_pay) }}</td>
                                <td class="text-end">{{ $m($r->total_tax) }}</td>
                                <td class="text-end">{{ $m($r->total_employee_pension) }} / {{ $m($r->total_employer_pension) }}</td>
                                <td class="text-end">{{ $m($r->total_nhf) }}</td>
                                <td class="text-end fw-bold">{{ $m($r->total_net_pay) }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-bold table-light"><td>Total</td><td>{{ $m($t['cost']) }}</td><td class="text-end">{{ $m($t['gross']) }}</td><td class="text-end">{{ $m($t['paye']) }}</td><td class="text-end">{{ $m($t['pension_ee']) }} / {{ $m($t['pension_er']) }}</td><td class="text-end">{{ $m($t['nhf']) }}</td><td class="text-end">{{ $m($t['net']) }}</td></tr>
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card title="Where the money went" icon="ri-pie-chart-line">
                @php $parts = ['Net pay to staff' => $t['net'], 'PAYE' => $t['paye'], 'Pension (staff + school)' => $t['pension_ee'] + $t['pension_er'], 'NHF' => $t['nhf'], 'Loans recovered' => $t['loans']]; $sum = max(1, array_sum($parts)); @endphp
                @foreach($parts as $l => $v)
                    <div class="d-flex justify-content-between small mb-1"><span>{{ $l }}</span><span>{{ $m($v) }} <span class="text-muted">({{ round($v / $sum * 100) }}%)</span></span></div>
                    <div class="progress-track mb-2"><div class="progress-fill" style="width:{{ round($v / $sum * 100) }}%"></div></div>
                @endforeach
            </x-cb.card>
            @if($byDept->isNotEmpty())
                <x-cb.card title="By department" icon="ri-building-2-line" :flush="true">
                    <table class="table table-sm align-middle mb-0"><thead><tr><th>Department</th><th class="text-end">Staff</th><th class="text-end">Cost</th></tr></thead><tbody>
                        @foreach($byDept as $d)<tr><td>{{ $d->dept }}</td><td class="text-end">{{ $d->staff }}</td><td class="text-end">{{ $m($d->cost) }}</td></tr>@endforeach
                    </tbody></table>
                </x-cb.card>
            @endif
        </div>
    </div>
</div>
</div>
</div>
@endsection
