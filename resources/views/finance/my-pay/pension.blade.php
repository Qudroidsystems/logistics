{{-- resources/views/finance/my-pay/pension.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $t = $data['totals']; $dl = array_filter(['year' => $year, 'from' => $year ? null : substr($from, 0, 7), 'to' => $year ? null : substr($to, 0, 7)]) + $q; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Pension History" icon="ri-shield-user-line" :subtitle="($data['pfa'] ?? 'Pension') . ($data['rsa'] ? ' · ' . $data['rsa'] : '') . ' · ' . $label">
        <x-slot:actions>
            <a href="{{ route('my-pay.pension', $dl + ['format' => 'pdf']) }}" target="_blank" class="cb-hero-btn"><i class="ri-file-pdf-2-line"></i>PDF</a>
            <a href="{{ route('my-pay.pension', $dl + ['format' => 'csv']) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Excel</a>
        </x-slot:actions>
    </x-cb.hero>
    @include('finance.my-pay._nav', ['section' => 'pension'])
    <div class="row g-3 mb-3">
        <div class="col-md-4"><x-cb.stat label="Your contributions" :value="$m($t['employee'])" icon="ri-user-line" accent="teal" /></div>
        <div class="col-md-4"><x-cb.stat label="School's contributions" :value="$m($t['employer'])" icon="ri-building-line" accent="violet" /></div>
        <div class="col-md-4"><x-cb.stat label="Total for the period" :value="$m($t['total'])" icon="ri-safe-2-line" accent="green" /></div>
    </div>
    <x-cb.card title="Month by month" icon="ri-calendar-line" :count="$data['rows']->count()" :flush="true">
        @if($data['rows']->isEmpty())
            <div class="empty-state"><i class="ri-shield-user-line"></i><h6>No pension records for this period</h6></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Month</th><th class="text-end">Pensionable pay</th><th class="text-end">Yours (8%)</th><th class="text-end">School (10%)</th><th class="text-end">Total</th><th class="text-end">Running total</th><th>Paid to your PFA</th></tr></thead>
                <tbody>
                @foreach($data['rows'] as $r)
                    <tr><td>{{ $r->month }}</td><td class="text-end">{{ $m($r->base) }}</td><td class="text-end">{{ $m($r->employee) }}</td><td class="text-end">{{ $m($r->employer) }}</td><td class="text-end fw-bold">{{ $m($r->total) }}</td><td class="text-end">{{ $m($r->running) }}</td>
                        <td>@if($r->remitted === null)<span class="text-muted small">—</span>@elseif($r->remitted)<span class="status-pill st-paid">Paid {{ $r->remitted_on }}</span>@else<span class="status-pill st-pending">Not yet</span>@endif</td></tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    <div class="small text-muted">Compare this with the statement from your pension company. "Paid to your PFA" fills in once the bursary records the monthly pension payments.</div>
</div>
</div>
</div>
@endsection
