{{-- resources/views/accounting/cash-flow.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => ($x < 0 ? '(' : '') . number_format(abs((float) $x), 2) . ($x < 0 ? ')' : ''); $L = ['operating' => 'Day-to-day running', 'investing' => 'Buying / selling assets', 'financing' => 'Capital & funding']; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Cash Flow" icon="ri-exchange-dollar-line" :subtitle="\Carbon\Carbon::parse($cf['from'])->format('d M Y') . ' – ' . \Carbon\Carbon::parse($cf['to'])->format('d M Y') . ' · where cash came from and went'" />
    @include('accounting._nav', ['section' => 'cash'])
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Opening cash" :value="'₦' . $m($cf['opening'])" icon="ri-bank-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="From running the school" :value="'₦' . $m($cf['operating'])" icon="ri-school-line" :accent="$cf['operating'] >= 0 ? 'green' : 'rose'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Net change" :value="'₦' . $m($cf['net'])" icon="ri-exchange-dollar-line" :accent="$cf['net'] >= 0 ? 'green' : 'rose'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Closing cash" :value="'₦' . $m($cf['closing'])" icon="ri-safe-2-line" accent="amber" /></div>
    </div>
    <x-cb.card title="Statement" icon="ri-file-chart-line" :flush="true">
        <x-slot:tools>@include('accounting._range', ['mode' => 'range', 'from' => $cf['from'], 'to' => $cf['to'], 'noCsv' => true])</x-slot:tools>
        <table class="table table-sm mb-0">
            <tr><th>Opening cash & bank</th><th class="text-end">{{ $m($cf['opening']) }}</th></tr>
            @foreach($L as $k => $label)
                <tr class="table-light"><th colspan="2">{{ $label }}</th></tr>
                @forelse($cf['groups'][$k] as $name => $amt)<tr><td class="ps-4">{{ $name }}</td><td class="text-end {{ $amt < 0 ? 'text-danger' : 'text-success' }}">{{ $m($amt) }}</td></tr>@empty<tr><td class="ps-4 text-muted" colspan="2">No movements</td></tr>@endforelse
                <tr><td class="text-end fw-bold">Net cash from {{ strtolower($label) }}</td><td class="text-end fw-bold">{{ $m($cf[$k]) }}</td></tr>
            @endforeach
            <tr class="table-primary"><th>Closing cash & bank</th><th class="text-end">{{ $m($cf['closing']) }}</th></tr>
        </table>
    </x-cb.card>
    <div class="small text-muted">Brackets are money going out. Each cash movement is grouped by the main account on the other side of the entry.</div>
</div>
</div>
</div>
@endsection
