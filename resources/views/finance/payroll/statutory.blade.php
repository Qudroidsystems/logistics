{{-- resources/views/finance/payroll/statutory.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $m = fn ($v) => '₦' . number_format((float) $v, 2);
    $cell = function ($r, $type, $amount) use ($remit, $m) {
        $x = $remit[$r->id][$type] ?? null;
        $state = !$x ? '' : ((float) $x->paid + 0.009 >= (float) $x->due ? 'paid' : ((float) $x->paid > 0 ? 'part' : 'due'));
        $icon = ['paid' => '<i class="ri-checkbox-circle-fill text-success" title="Paid"></i>', 'part' => '<i class="ri-contrast-2-line text-warning" title="Part paid"></i>', 'due' => '<i class="ri-time-line text-danger" title="Not paid"></i>'][$state] ?? '';
        return $m($amount) . ' ' . $icon;
    };
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Statutory Summary" icon="ri-government-line" :subtitle="'PAYE, pension and NHF deducted in ' . $year . ', and whether each was paid over'">
        <x-slot:actions>
            <form method="GET"><select name="year" class="cb-select" onchange="this.form.submit()" aria-label="Year">@foreach($years as $y)<option @selected($y == $year)>{{ $y }}</option>@endforeach</select></form>
            <a href="{{ route('payroll.remittances', ['year' => $year]) }}" class="cb-hero-btn"><i class="ri-bank-card-line"></i>Record payments</a>
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="PAYE" :value="$m($rows->sum('total_tax'))" icon="ri-government-line" accent="violet" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Pension — staff" :value="$m($rows->sum('total_employee_pension'))" icon="ri-user-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Pension — school" :value="$m($rows->sum('total_employer_pension'))" icon="ri-building-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="NHF" :value="$m($rows->sum('total_nhf'))" icon="ri-home-4-line" accent="sky" /></div>
    </div>

    <x-cb.card title="Month by month" icon="ri-calendar-line" :count="$rows->count()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-government-line"></i><h6>No approved months in {{ $year }}</h6></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Month</th><th class="text-end">PAYE</th><th class="text-end">Pension (staff + school)</th><th class="text-end">NHF</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr><td>{{ $r->period_name }}</td>
                        <td class="text-end">{!! $cell($r, 'paye', $r->total_tax) !!}</td>
                        <td class="text-end">{!! $cell($r, 'pension', $r->total_employee_pension + $r->total_employer_pension) !!}</td>
                        <td class="text-end">{!! $cell($r, 'nhf', $r->total_nhf) !!}</td>
                        <td class="text-end fw-bold">{{ $m($r->total_tax + $r->total_employee_pension + $r->total_employer_pension + $r->total_nhf) }}</td></tr>
                @endforeach
                <tr class="fw-bold table-light"><td>Total</td><td class="text-end">{{ $m($rows->sum('total_tax')) }}</td><td class="text-end">{{ $m($rows->sum('total_employee_pension') + $rows->sum('total_employer_pension')) }}</td><td class="text-end">{{ $m($rows->sum('total_nhf')) }}</td><td class="text-end">{{ $m($rows->sum('total_tax') + $rows->sum('total_employee_pension') + $rows->sum('total_employer_pension') + $rows->sum('total_nhf')) }}</td></tr>
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    <div class="small text-muted"><i class="ri-checkbox-circle-fill text-success"></i> paid · <i class="ri-contrast-2-line text-warning"></i> part paid · <i class="ri-time-line text-danger"></i> not yet paid. Details and receipts are on the Remittances page.</div>
</div>
</div>
</div>
@endsection
