{{-- resources/views/finance/payroll/remittances.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $T = \App\Models\StatutoryRemittance::TYPES; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Government Remittances" icon="ri-government-line" subtitle="PAYE per state, pension per PFA, NHF and others — what's owed from each payroll month, and proof of payment." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($stats['overdue_count'])<div class="cb-banner warning"><i class="ri-alarm-warning-line"></i><div><strong>{{ $stats['overdue_count'] }} overdue</strong> — {{ $m($stats['overdue']) }} past its due date. Late payment can attract penalties and interest.</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat :label="'Owed in ' . $year" :value="$m($stats['due'])" icon="ri-file-list-3-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Paid" :value="$m($stats['paid'])" icon="ri-checkbox-circle-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Still to pay" :value="$m($stats['outstanding'])" icon="ri-time-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Overdue" :value="$m($stats['overdue'])" icon="ri-alarm-warning-line" :accent="$stats['overdue'] > 0 ? 'rose' : 'green'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <x-cb.card title="Remittances" icon="ri-list-check-2" :count="$rows->count()" :flush="true">
                <form class="cb-toolbar gap-2" method="GET">
                    <select name="year" class="cb-select" onchange="this.form.submit()">@foreach($years as $y)<option @selected($y == $year)>{{ $y }}</option>@endforeach</select>
                    <select name="type" class="cb-select" onchange="this.form.submit()"><option value="">All types</option>@foreach($T as $k => [$l])<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
                    <select name="status" class="cb-select" onchange="this.form.submit()"><option value="">All</option><option value="unpaid" @selected(request('status') === 'unpaid')>Not fully paid</option><option value="overdue" @selected(request('status') === 'overdue')>Overdue</option></select>
                </form>
                @if($rows->isEmpty())
                    <div class="empty-state"><i class="ri-government-line"></i><h6>Nothing yet</h6><p>Remittances are prepared automatically when a payroll month is approved, or use "Prepare" on the right.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Month</th><th>Type</th><th>Paid to</th><th class="text-end">Staff</th><th class="text-end">Amount</th><th>Due</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($rows as $r)
                            <tr class="{{ $r->isOverdue() ? 'table-danger' : '' }}">
                                <td>{{ $r->period->period_name ?? '—' }}</td>
                                <td><i class="{{ $T[$r->type][1] }}"></i> {{ $T[$r->type][0] }}</td>
                                <td>{{ $r->authority }} @if(str_contains($r->authority, 'not set'))<span class="status-pill st-warning">Fix staff details</span>@endif
                                    @if($r->needs_review)<div class="small text-danger">Payroll changed after payment — check</div>@endif</td>
                                <td class="text-end">{{ $r->staff_count }}</td>
                                <td class="text-end fw-bold">{{ $m($r->amount_due) }}@if($r->status === 'partial')<div class="small text-muted">paid {{ $m($r->amount_paid) }}</div>@endif</td>
                                <td class="small {{ $r->isOverdue() ? 'text-danger fw-bold' : '' }}">{{ $r->due_date?->format('d M Y') }}</td>
                                <td>@if($r->status === 'paid')<span class="status-pill st-paid">Paid {{ $r->paid_at?->format('d M') }}</span>@elseif($r->status === 'partial')<span class="status-pill st-pending">Part paid</span>@elseif($r->isOverdue())<span class="status-pill st-danger">Overdue</span>@else<span class="status-pill st-muted">Pending</span>@endif</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('payroll.remittances.schedule', $r) }}" class="action-btn btn-open" title="Download schedule"><i class="ri-file-excel-2-line"></i></a>
                                    <a href="{{ route('payroll.remittances.show', $r) }}" class="action-btn btn-go"><i class="ri-eye-line"></i>Open</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-3">
            @can('Manage remittances')
            <x-cb.card title="Prepare for a month" icon="ri-refresh-line">
                <form method="POST" action="{{ route('payroll.remittances.generate') }}">@csrf
                    <select name="payroll_period_id" class="form-select form-select-sm mb-2" required>@foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->period_name }}</option>@endforeach</select>
                    <button class="action-btn btn-go w-100 justify-content-center"><i class="ri-refresh-line"></i>Prepare / refresh</button>
                </form>
                <div class="small text-muted mt-2">Already-paid items are never changed; they're flagged if the payroll no longer matches.</div>
            </x-cb.card>
            @endcan
            <x-cb.card :title="'By type · ' . $year" icon="ri-pie-chart-line">
                @foreach($byType as $t => $x)
                    <div class="d-flex justify-content-between small mb-1"><span>{{ $T[$t][0] }}</span><span>{{ $m($x['paid']) }} / {{ $m($x['due']) }}</span></div>
                    <div class="progress-track mb-2"><div class="progress-fill" style="width:{{ $x['due'] > 0 ? min(100, round($x['paid'] / $x['due'] * 100)) : 0 }}%"></div></div>
                @endforeach
            </x-cb.card>
            <div class="small text-muted">Due dates follow the rules on <a href="{{ route('payroll.employer') }}">Employer Details</a> (default: PAYE by the 10th of the next month; pension within 7 working days of pay day). Confirm them with your state tax office and PFA.</div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
