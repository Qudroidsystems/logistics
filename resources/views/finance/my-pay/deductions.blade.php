{{-- resources/views/finance/my-pay/deductions.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $dl = array_filter(['year' => $year, 'from' => $year ? null : substr($from, 0, 7), 'to' => $year ? null : substr($to, 0, 7)]) + $q; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Deductions History" icon="ri-indeterminate-circle-line" :subtitle="'NHF, loans, cooperative, union dues and others · ' . $label">
        <x-slot:actions><a href="{{ route('my-pay.deductions', $dl + ['format' => 'csv']) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Excel</a></x-slot:actions>
    </x-cb.hero>
    @include('finance.my-pay._nav', ['section' => 'deductions'])
    <x-cb.card title="Month by month" icon="ri-calendar-line" :flush="true">
        @if(!$data['codes'])
            <div class="empty-state"><i class="ri-indeterminate-circle-line"></i><h6>No other deductions in this period</h6><p>PAYE and pension are on their own tabs.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Month</th>@foreach($data['codes'] as $c => $l)<th class="text-end">{{ $l }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach($data['rows'] as $r)<tr><td>{{ $r['month'] }}</td>@foreach($data['codes'] as $c => $l)<td class="text-end">{{ isset($r[$c]) ? $m($r[$c]) : '—' }}</td>@endforeach</tr>@endforeach
                <tr class="fw-bold table-light"><td>Total</td>@foreach($data['codes'] as $c => $l)<td class="text-end">{{ $m($data['totals'][$c] ?? 0) }}</td>@endforeach</tr>
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($data['loans']->isNotEmpty())
        <x-cb.card title="Loans & advances" icon="ri-hand-coin-line" :flush="true">
            <table class="table align-middle mb-0"><thead><tr><th>Reference</th><th>Type</th><th class="text-end">Amount</th><th class="text-end">Monthly</th><th class="text-end">Balance</th><th>Status</th></tr></thead><tbody>
                @foreach($data['loans'] as $l)<tr><td>{{ $l->reference_no }}</td><td>{{ ucfirst($l->type) }}</td><td class="text-end">{{ $m($l->amount) }}</td><td class="text-end">{{ $m($l->monthly_repayment) }}</td><td class="text-end fw-bold">{{ $m($l->balance) }}</td><td><span class="status-pill {{ $l->status === 'active' ? 'st-pending' : 'st-paid' }}">{{ ucfirst($l->status) }}</span></td></tr>@endforeach
            </tbody></table>
        </x-cb.card>
    @endif
</div>
</div>
</div>
@endsection
