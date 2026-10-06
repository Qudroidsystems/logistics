{{-- resources/views/finance/budgets/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $m = fn ($x) => '₦' . number_format((float) $x, 2);
    $lines = collect($report['rows'])->mapWithKeys(fn ($r) => [$r['line']->line_type === 'payroll' ? 'payroll' : $r['line']->expense_category_id => $r['line']->amount]);
    $canEdit = auth()->user()->can('Manage budgets') && $budget->status !== 'closed';
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$budget->name" icon="ri-scales-3-line" :subtitle="$budget->start_date->format('d M Y') . ' – ' . $budget->end_date->format('d M Y')" :back="route('finance.budgets')" back-label="Budgets">
        <x-slot:actions>
            <a href="{{ route('finance.budgets.export', $budget) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Excel</a>
            @can('Manage budgets')
                @foreach(['active' => ['Activate', 'ri-play-line'], 'closed' => ['Close', 'ri-lock-line'], 'draft' => ['Back to draft', 'ri-draft-line']] as $st => [$l, $i])
                    @if($budget->status !== $st)<form method="POST" action="{{ route('finance.budgets.status', $budget) }}" class="d-inline">@csrf<input type="hidden" name="status" value="{{ $st }}"><button class="cb-hero-btn"><i class="{{ $i }}"></i>{{ $l }}</button></form>@endif
                @endforeach
            @endcan
        </x-slot:actions>
        <x-slot:pills><span class="cb-meta-pill">{{ ucfirst($budget->status) }}</span><span class="cb-meta-pill"><i class="ri-money-dollar-circle-line"></i>{{ $m($report['total_budget']) }}</span></x-slot:pills>
    </x-cb.hero>
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="Budget vs actual" icon="ri-bar-chart-horizontal-line" :flush="true">@include('finance.budgets._report', ['report' => $report, 'm' => $m])</x-cb.card>
        </div>
        <div class="col-xl-5">
            <x-cb.card title="Budget lines" icon="ri-edit-line" :flush="true">
                <form method="POST" action="{{ route('finance.budgets.lines', $budget) }}">@csrf
                    <div class="table-responsive" style="max-height:620px"><table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Category</th><th style="width:150px">Amount ₦</th></tr></thead>
                        <tbody>
                            <tr class="table-light"><td><b>Staff salaries (payroll cost)</b></td><td><input type="number" step="1000" min="0" name="payroll" value="{{ $lines['payroll'] ?? '' }}" class="form-control form-control-sm text-end" @disabled(!$canEdit)></td></tr>
                            @foreach($categories as $c)
                                <tr><td class="small">{{ $c->name }}</td><td><input type="number" step="1000" min="0" name="lines[{{ $c->id }}]" value="{{ $lines[$c->id] ?? '' }}" class="form-control form-control-sm text-end" @disabled(!$canEdit)></td></tr>
                            @endforeach
                        </tbody>
                    </table></div>
                    @if($canEdit)<div class="p-3"><button class="action-btn btn-go w-100 justify-content-center"><i class="ri-save-line"></i>Save lines</button><div class="small text-muted mt-1">Leave a box empty for no budget on that category.</div></div>@endif
                </form>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
