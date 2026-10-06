{{-- resources/views/finance/my-pay/tax.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $t = $data['totals']; $dl = array_filter(['year' => $year, 'from' => $year ? null : substr($from, 0, 7), 'to' => $year ? null : substr($to, 0, 7)]) + $q; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Tax History" icon="ri-government-line" :subtitle="'PAYE deducted from your pay · ' . $label">
        <x-slot:actions>
            <a href="{{ route('my-pay.tax', $dl + ['format' => 'pdf']) }}" target="_blank" class="cb-hero-btn"><i class="ri-file-pdf-2-line"></i>PDF</a>
            <a href="{{ route('my-pay.tax', $dl + ['format' => 'csv']) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Excel</a>
            @if($year)<a href="{{ route('my-pay.certificate', ['year' => $year] + $q) }}" target="_blank" class="cb-hero-btn"><i class="ri-award-line"></i>Certificate</a>@endif
        </x-slot:actions>
    </x-cb.hero>
    @include('finance.my-pay._nav', ['section' => 'tax'])

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Gross pay" :value="$m($t['gross'])" icon="ri-money-dollar-circle-line" accent="teal" :hint="$t['months'] . ' month(s)'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Pension + NHF + NHIA" :value="$m($t['pension'] + $t['nhf'] + $t['nhia'])" icon="ri-shield-user-line" accent="amber" hint="deducted before tax" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="PAYE deducted" :value="$m($t['paye'])" icon="ri-government-line" accent="violet" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Effective tax rate" :value="($t['taxable'] > 0 ? round($t['paye'] / $t['taxable'] * 100, 2) : 0) . '%'" icon="ri-percent-line" accent="sky" /></div>
    </div>

    <x-cb.card title="Month by month" icon="ri-calendar-line" :count="$t['months']" :flush="true">
        @if($data['rows']->isEmpty())
            <div class="empty-state"><i class="ri-government-line"></i><h6>No records for this period</h6></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Month</th><th class="text-end">Gross</th><th class="text-end">Taxable</th><th class="text-end">Pension</th><th class="text-end">NHF</th><th class="text-end">Rent relief (yr)</th><th class="text-end">Chargeable (yr)</th><th class="text-end">PAYE</th><th>State</th></tr></thead>
                <tbody>
                @foreach($data['rows'] as $r)
                    <tr><td>{{ $r->month }} <small class="text-muted">{{ $r->rule }}</small></td><td class="text-end">{{ $m($r->gross) }}</td><td class="text-end">{{ $m($r->taxable) }}</td><td class="text-end">{{ $m($r->pension) }}</td><td class="text-end">{{ $m($r->nhf) }}</td>
                        <td class="text-end">{{ $r->rent_relief_annual ? $m($r->rent_relief_annual) : '—' }}</td><td class="text-end">{{ $m($r->chargeable_annual) }}</td><td class="text-end fw-bold">{{ $m($r->paye) }}</td><td class="small">{{ $r->state ?: '—' }}</td></tr>
                @endforeach
                <tr class="fw-bold table-light"><td>Total</td><td class="text-end">{{ $m($t['gross']) }}</td><td class="text-end">{{ $m($t['taxable']) }}</td><td class="text-end">{{ $m($t['pension']) }}</td><td class="text-end">{{ $m($t['nhf']) }}</td><td></td><td></td><td class="text-end">{{ $m($t['paye']) }}</td><td></td></tr>
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    <div class="small text-muted">"Chargeable (yr)" is the yearly income tax was worked out on that month, after reliefs. PAYE each month is one twelfth of the tax on that figure (plus tax on any one-off payment).</div>
</div>
</div>
</div>
@endsection
