{{-- resources/views/accounting/trial-balance.blade.php --}}
@extends('layouts.master')

@section('content')
@php $z = fn ($x) => $x > 0 ? number_format((float) $x, 2) : ''; $ok = abs($tb['debit'] - $tb['credit']) < 0.01; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Trial Balance" icon="ri-scales-line" :subtitle="'As at ' . \Carbon\Carbon::parse($tb['as_at'])->format('d F Y')" />
    @include('accounting._nav', ['section' => 'trial'])
    <div class="cb-banner {{ $ok ? 'info' : 'warning' }}"><i class="ri-{{ $ok ? 'checkbox-circle' : 'error-warning' }}-line"></i><div>{{ $ok ? 'Debits equal credits — the books balance.' : 'Debits and credits differ by ₦' . number_format(abs($tb['debit'] - $tb['credit']), 2) . '. Check recent manual entries.' }}</div></div>
    <x-cb.card title="Balances" icon="ri-scales-line" :count="count($tb['rows'])" :flush="true">
        <x-slot:tools>@include('accounting._range', ['mode' => 'asat', 'asAt' => $tb['as_at']])</x-slot:tools>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit ₦</th><th class="text-end">Credit ₦</th></tr></thead>
            <tbody>@forelse($tb['rows'] as $r)<tr><td><code>{{ $r['account']->account_code }}</code></td><td><a href="{{ route('accounting.ledger', ['account' => $r['account']->id, 'to' => $tb['as_at']]) }}">{{ $r['account']->account_name }}</a></td><td class="small text-muted">{{ ucfirst($r['account']->account_type) }}</td><td class="text-end">{{ $z($r['debit']) }}</td><td class="text-end">{{ $z($r['credit']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted p-4">No posted entries yet.</td></tr>@endforelse</tbody>
            <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end">{{ number_format($tb['debit'], 2) }}</th><th class="text-end">{{ number_format($tb['credit'], 2) }}</th></tr></tfoot>
        </table></div>
    </x-cb.card>
</div>
</div>
</div>
@endsection
