{{-- resources/views/accounting/balance-sheet.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => number_format((float) $x, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Balance Sheet" icon="ri-bank-line" :subtitle="'Statement of financial position as at ' . \Carbon\Carbon::parse($bs['as_at'])->format('d F Y')" />
    @include('accounting._nav', ['section' => 'balance'])
    @if(abs($bs['difference']) >= 0.01)<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>Out of balance by ₦{{ $m($bs['difference']) }} — check the trial balance.</div></div>@endif
    <div class="d-flex justify-content-end mb-2">@include('accounting._range', ['mode' => 'asat', 'asAt' => $bs['as_at']])</div>
    <div class="row g-3">
        <div class="col-xl-6">
            <x-cb.card title="What the school owns" icon="ri-building-4-line" :flush="true">
                <table class="table table-sm mb-0">
                    <tr class="table-light"><th colspan="2">Fixed assets</th></tr>
                    @foreach($bs['fixed'] as $r)<tr><td class="ps-4">{{ $r['account']->account_name }}</td><td class="text-end {{ $r['amount'] < 0 ? 'text-danger' : '' }}">{{ $r['amount'] < 0 ? '(' . $m(-$r['amount']) . ')' : $m($r['amount']) }}</td></tr>@endforeach
                    <tr><td class="text-end fw-bold">Net book value</td><td class="text-end fw-bold">{{ $m($bs['total_fixed']) }}</td></tr>
                    <tr class="table-light"><th colspan="2">Current assets</th></tr>
                    @foreach($bs['current'] as $r)<tr><td class="ps-4">{{ $r['account']->account_name }}</td><td class="text-end">{{ $m($r['amount']) }}</td></tr>@endforeach
                    <tr><td class="text-end fw-bold">Total current assets</td><td class="text-end fw-bold">{{ $m($bs['total_current']) }}</td></tr>
                    <tr class="table-primary"><th>TOTAL ASSETS</th><th class="text-end">₦{{ $m($bs['total_assets']) }}</th></tr>
                </table>
            </x-cb.card>
        </div>
        <div class="col-xl-6">
            <x-cb.card title="What the school owes, and its funds" icon="ri-hand-coin-line" :flush="true">
                <table class="table table-sm mb-0">
                    <tr class="table-light"><th colspan="2">Liabilities</th></tr>
                    @forelse($bs['liabilities'] as $r)<tr><td class="ps-4">{{ $r['account']->account_name }}</td><td class="text-end">{{ $m($r['amount']) }}</td></tr>@empty<tr><td class="ps-4 text-muted" colspan="2">None</td></tr>@endforelse
                    <tr><td class="text-end fw-bold">Total liabilities</td><td class="text-end fw-bold">{{ $m($bs['total_liabilities']) }}</td></tr>
                    <tr class="table-light"><th colspan="2">Funds / equity</th></tr>
                    @foreach($bs['equity'] as $r)<tr><td class="ps-4">{{ $r['account']->account_name }}</td><td class="text-end">{{ $m($r['amount']) }}</td></tr>@endforeach
                    <tr><td class="ps-4">Accumulated surplus / (deficit)</td><td class="text-end">{{ $m($bs['surplus']) }}</td></tr>
                    <tr><td class="text-end fw-bold">Total funds</td><td class="text-end fw-bold">{{ $m($bs['total_equity']) }}</td></tr>
                    <tr class="table-primary"><th>TOTAL LIABILITIES & FUNDS</th><th class="text-end">₦{{ $m($bs['total_liabilities'] + $bs['total_equity']) }}</th></tr>
                </table>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
