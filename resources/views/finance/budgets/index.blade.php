{{-- resources/views/finance/budgets/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Budgets" icon="ri-scales-3-line" subtitle="Plan spending per session or term and track it against actual payments." />
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    @if($active && $report)
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Budget" :value="$m($report['total_budget'])" icon="ri-scales-3-line" accent="teal" :hint="$active->name" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Spent" :value="$m($report['total_actual'])" icon="ri-money-dollar-circle-line" accent="amber" :hint="($report['total_budget'] > 0 ? round($report['total_actual'] / $report['total_budget'] * 100) : 0) . '% used'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Committed" :value="$m($report['total_committed'])" icon="ri-hourglass-line" accent="rose" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Period elapsed" :value="$report['elapsed'] . '%'" icon="ri-time-line" accent="green" :hint="$active->start_date->format('d M') . ' – ' . $active->end_date->format('d M Y')" /></div>
    </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            @if($active && $report)
            <x-cb.card :title="'Active · ' . $active->name" icon="ri-bar-chart-horizontal-line" :flush="true">
                <x-slot:tools><a href="{{ route('finance.budgets.show', $active) }}" class="action-btn btn-go"><i class="ri-edit-line"></i>Open</a></x-slot:tools>
                @include('finance.budgets._report', ['report' => $report, 'm' => $m])
            </x-cb.card>
            @endif
            <x-cb.card title="All budgets" icon="ri-list-check-2" :count="$budgets->count()" :flush="true">
                @if($budgets->isEmpty())
                    <div class="empty-state"><i class="ri-scales-3-line"></i><h6>No budgets yet</h6><p>Create one for this session on the right.</p></div>
                @else
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Name</th><th>Period</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>@foreach($budgets as $b)<tr><td><strong>{{ $b->name }}</strong></td><td class="small">{{ $b->start_date->format('d M Y') }} – {{ $b->end_date->format('d M Y') }}</td><td class="text-end">{{ $m($b->lines_sum_amount) }}</td>
                        <td><span class="status-pill {{ ['active' => 'st-paid', 'draft' => 'st-muted', 'closed' => 'st-info'][$b->status] ?? 'st-muted' }}">{{ ucfirst($b->status) }}</span></td>
                        <td class="text-end"><a href="{{ route('finance.budgets.show', $b) }}" class="action-btn btn-go"><i class="ri-eye-line"></i>Open</a></td></tr>@endforeach</tbody>
                </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            @can('Manage budgets')
            <x-cb.card title="New budget" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('finance.budgets.store') }}" class="small">@csrf
                    <input name="name" class="form-control form-control-sm mb-2" placeholder="e.g. 2026/2027 Session" required>
                    <div class="row g-2 mb-2"><div class="col-6"><label class="form-label">From</label><input type="date" name="start_date" class="form-control form-control-sm" required></div><div class="col-6"><label class="form-label">To</label><input type="date" name="end_date" class="form-control form-control-sm" required></div></div>
                    @if($budgets->isNotEmpty())
                    <div class="row g-2 mb-2"><div class="col-8"><select name="copy_from" class="form-select form-select-sm"><option value="">Start empty</option>@foreach($budgets as $b)<option value="{{ $b->id }}">Copy {{ $b->name }}</option>@endforeach</select></div>
                        <div class="col-4"><input type="number" name="uplift" step="0.5" class="form-control form-control-sm" placeholder="+ %" title="Increase copied amounts by %"></div></div>
                    @endif
                    <button class="action-btn btn-go w-100 justify-content-center">Create</button>
                </form>
            </x-cb.card>
            @endcan
            <div class="small text-muted">“Committed” = vouchers submitted or approved but not yet paid. Bars turn amber when spending runs ahead of the calendar, red when over budget.</div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
