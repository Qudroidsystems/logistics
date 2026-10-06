{{-- resources/views/accounting/income-statement.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $m = fn ($x) => number_format((float) $x, 2);
    $pv = function ($code) use ($prev) {
        if (!$prev) return null;
        foreach (array_merge($prev['income'], $prev['staff'], $prev['other'], $prev['depreciation']) as $r) if ($r['account']->account_code === $code) return $r['amount'];
        return 0;
    };
    $section = function ($title, $rows, $total, $prevTotal) use ($m, $pv, $prev) {
        $h = '<tr class="table-light"><th colspan="' . ($prev ? 3 : 2) . '">' . e($title) . '</th></tr>';
        foreach ($rows as $r) $h .= '<tr><td class="ps-4"><a href="' . route('accounting.ledger', ['account' => $r['account']->id]) . '">' . e($r['account']->account_name) . '</a></td><td class="text-end">' . $m($r['amount']) . '</td>' . ($prev ? '<td class="text-end text-muted">' . $m($pv($r['account']->account_code)) . '</td>' : '') . '</tr>';
        return $h . '<tr><td class="text-end fw-bold">Total ' . e(strtolower($title)) . '</td><td class="text-end fw-bold">' . $m($total) . '</td>' . ($prev ? '<td class="text-end text-muted fw-bold">' . $m($prevTotal) . '</td>' : '') . '</tr>';
    };
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Income & Expenditure" icon="ri-line-chart-line" :subtitle="\Carbon\Carbon::parse($is['from'])->format('d M Y') . ' – ' . \Carbon\Carbon::parse($is['to'])->format('d M Y')" />
    @include('accounting._nav', ['section' => 'income'])
    <div class="row g-3 mb-3">
        <div class="col-md-4"><x-cb.stat label="Income" :value="'₦' . $m($is['total_income'])" icon="ri-arrow-up-circle-line" accent="green" /></div>
        <div class="col-md-4"><x-cb.stat label="Expenditure" :value="'₦' . $m($is['total_expenses'])" icon="ri-arrow-down-circle-line" accent="rose" :hint="$is['total_income'] > 0 ? 'Staff costs ' . round($is['total_staff'] / $is['total_income'] * 100) . '% of income' : null" /></div>
        <div class="col-md-4"><x-cb.stat :label="$is['surplus'] >= 0 ? 'Surplus' : 'Deficit'" :value="'₦' . $m(abs($is['surplus']))" icon="ri-scales-3-line" :accent="$is['surplus'] >= 0 ? 'green' : 'rose'" /></div>
    </div>
    <x-cb.card title="Statement" icon="ri-file-chart-line" :flush="true">
        <x-slot:tools>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                @include('accounting._range', ['mode' => 'range', 'from' => $is['from'], 'to' => $is['to']])
                <a href="{{ request()->fullUrlWithQuery(['compare' => $prev ? 0 : 1]) }}" class="btn btn-sm btn-light d-print-none">{{ $prev ? 'Hide' : 'Compare with' }} previous period</a>
            </div>
        </x-slot:tools>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th></th><th class="text-end">₦</th>@if($prev)<th class="text-end text-muted">Previous ₦</th>@endif</tr></thead>
            <tbody>
                {!! $section('Income', $is['income'], $is['total_income'], $prev['total_income'] ?? 0) !!}
                {!! $section('Staff costs', $is['staff'], $is['total_staff'], $prev['total_staff'] ?? 0) !!}
                {!! $section('Running costs', $is['other'], $is['total_other'], $prev['total_other'] ?? 0) !!}
                @if($is['depreciation'] || ($prev['depreciation'] ?? false)){!! $section('Depreciation', $is['depreciation'], $is['total_depreciation'], $prev['total_depreciation'] ?? 0) !!}@endif
            </tbody>
            <tfoot>
                <tr><th class="text-end">Total expenditure</th><th class="text-end">{{ $m($is['total_expenses']) }}</th>@if($prev)<th class="text-end text-muted">{{ $m($prev['total_expenses']) }}</th>@endif</tr>
                <tr class="{{ $is['surplus'] >= 0 ? 'table-success' : 'table-danger' }}"><th class="text-end">{{ $is['surplus'] >= 0 ? 'SURPLUS' : 'DEFICIT' }} FOR THE PERIOD</th><th class="text-end">{{ $m($is['surplus']) }}</th>@if($prev)<th class="text-end">{{ $m($prev['surplus']) }}</th>@endif</tr>
            </tfoot>
        </table></div>
    </x-cb.card>
</div>
</div>
</div>
@endsection
