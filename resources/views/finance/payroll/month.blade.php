{{-- resources/views/finance/payroll/month.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $m = fn ($v) => '₦' . number_format((float) $v, 2);
    $S = ['draft' => ['Not calculated', 'st-muted'], 'processing' => ['Calculated — awaiting approval', 'st-pending'], 'approved' => ['Approved', 'st-info'], 'paid' => ['Paid', 'st-paid'], 'locked' => ['Locked', 'st-paid']];
    $v = $variance;
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$period->period_name" icon="ri-money-dollar-box-line" :subtitle="\Carbon\Carbon::parse($period->start_date)->format('d M') . ' – ' . \Carbon\Carbon::parse($period->end_date)->format('d M Y') . ($period->payment_date ? ' · pay date ' . \Carbon\Carbon::parse($period->payment_date)->format('d M Y') : '')"
               :back="route('payroll.periods')" back-label="Payroll periods">
        <x-slot:actions>
            @if(in_array($period->status, ['draft', 'processing']))
                @can('Process payroll')<form method="POST" action="{{ route('payroll.month.calculate', $period) }}" class="d-inline" onsubmit="this.querySelector('button').disabled=true">@csrf<button class="cb-hero-btn"><i class="ri-calculator-line"></i>{{ $period->status === 'draft' ? 'Calculate' : 'Recalculate' }}</button></form>@endcan
            @endif
            @if($period->status === 'processing')
                @can('Approve payroll')<form method="POST" action="{{ route('payroll.month.approve', $period) }}" class="d-inline" onsubmit="return confirm('Approve this payroll? Staff will be able to see their payslips.')">@csrf<button class="cb-hero-btn"><i class="ri-checkbox-circle-line"></i>Approve</button></form>@endcan
            @endif
            @if(in_array($period->status, ['approved', 'paid']) && !$period->locked_at)
                @can('Approve payroll')<form method="POST" action="{{ route('payroll.month.lock', $period) }}" class="d-inline" onsubmit="return confirm('Lock this month? It can no longer be changed.')">@csrf<button class="cb-hero-btn"><i class="ri-lock-line"></i>Lock</button></form>@endcan
            @endif
            @if($runs->isNotEmpty())<a href="{{ route('payroll.month.register', $period) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Register</a>@endif
            @if(in_array($period->status, ['approved', 'paid', 'locked']))
                @can('Approve payroll')<a href="{{ route('payroll.month.bank', $period) }}" class="cb-hero-btn"><i class="ri-bank-line"></i>Bank schedule</a>@endcan
                @canany(['Approve payroll', 'Release salary payments'])@if(\Illuminate\Support\Facades\Route::has('payroll.payouts.create'))<a href="{{ route('payroll.payouts.create', $period) }}" class="cb-hero-btn"><i class="ri-send-plane-line"></i>Pay staff</a>@endif @endcanany
            @endif
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-flag-line"></i>{{ $S[$period->status][0] ?? $period->status }}</span>
            @if($period->processed_by)<span class="cb-meta-pill"><i class="ri-user-line"></i>Prepared: {{ $users[$period->processed_by] ?? '—' }}</span>@endif
            @if($period->approved_by)<span class="cb-meta-pill"><i class="ri-user-star-line"></i>Approved: {{ $users[$period->approved_by] ?? '—' }}</span>@endif
            @if($period->locked_at)<span class="cb-meta-pill"><i class="ri-lock-line"></i>Locked {{ $period->locked_at->format('d M Y') }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-xl-2 col-md-4 col-6"><x-cb.stat label="Staff" :value="$period->staff_count ?: $runs->count()" icon="ri-team-line" accent="teal" /></div>
        <div class="col-xl-2 col-md-4 col-6"><x-cb.stat label="Gross pay" :value="$m($period->total_gross_pay)" icon="ri-money-dollar-circle-line" accent="sky" /></div>
        <div class="col-xl-2 col-md-4 col-6"><x-cb.stat label="PAYE" :value="$m($period->total_tax)" icon="ri-government-line" accent="violet" /></div>
        <div class="col-xl-2 col-md-4 col-6"><x-cb.stat label="Pension (staff + school)" :value="$m($period->total_employee_pension + $period->total_employer_pension)" icon="ri-shield-user-line" accent="amber" /></div>
        <div class="col-xl-2 col-md-4 col-6"><x-cb.stat label="Net pay" :value="$m($period->total_net_pay)" icon="ri-wallet-3-line" accent="green" /></div>
        <div class="col-xl-2 col-md-4 col-6"><x-cb.stat label="Cost to school" :value="$m($period->total_employer_cost ?: $period->total_gross_pay)" icon="ri-building-line" accent="rose" /></div>
    </div>

    @if($warnCount)<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $warnCount }} staff have warnings (missing details, holds, minimum wage…). <a href="{{ request()->fullUrlWithQuery(['filter' => 'warnings']) }}">Show them</a></div></div>@endif
    @if($holds && in_array($period->status, ['approved', 'paid', 'locked']))<div class="cb-banner info"><i class="ri-pause-circle-line"></i><div>{{ count($holds) }} staff are on hold — they are marked "do not pay" in the bank schedule.</div></div>@endif

    @if($v['previous'] && $runs->isNotEmpty())
        <x-cb.card :title="'What changed since ' . $v['previous']->period_name" icon="ri-git-compare-line">
            <div class="row g-2 mb-2 small">
                <div class="col-md-3">Net pay: <b>{{ $m($v['net_before']) }}</b> → <b>{{ $m($v['net_after']) }}</b> <span class="{{ $v['net_after'] >= $v['net_before'] ? 'text-danger' : 'text-success' }}">({{ $v['net_after'] >= $v['net_before'] ? '+' : '' }}{{ $m($v['net_after'] - $v['net_before']) }})</span></div>
                <div class="col-md-3">New this month: <b>{{ count($v['new']) }}</b></div>
                <div class="col-md-3">Not paid this month: <b>{{ count($v['left']) }}</b></div>
                <div class="col-md-3">Pay changed noticeably: <b>{{ count($v['changed']) }}</b></div>
            </div>
            @if($v['new'])<div class="small mb-1"><span class="status-pill st-paid">New</span> {{ collect($v['new'])->map(fn ($x) => $x['name'])->implode(', ') }}</div>@endif
            @if($v['left'])<div class="small mb-1"><span class="status-pill st-muted">Missing</span> {{ collect($v['left'])->map(fn ($x) => $x['name'])->implode(', ') }}</div>@endif
            @if($v['changed'])
                <div class="table-responsive mt-2"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Staff</th><th class="text-end">Last month</th><th class="text-end">This month</th><th class="text-end">Change</th><th>Why</th></tr></thead>
                    <tbody>
                    @foreach(array_slice($v['changed'], 0, 25) as $c)
                        <tr><td>{{ $c['name'] }}</td><td class="text-end">{{ $m($c['before']) }}</td><td class="text-end">{{ $m($c['after']) }}</td>
                            <td class="text-end fw-bold {{ $c['diff'] > 0 ? 'text-success' : 'text-danger' }}">{{ $c['diff'] > 0 ? '+' : '' }}{{ $m($c['diff']) }}</td>
                            <td class="small">{{ collect($c['why'])->map(fn ($w) => $w['label'] . ' ' . ($w['diff'] > 0 ? '+' : '') . number_format($w['diff'], 2))->implode(' · ') }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
                <div class="small text-muted mt-1">Changes under ₦1,000 or 5% are not listed.</div>
            @endif
        </x-cb.card>
    @endif

    <x-cb.card title="Staff" icon="ri-list-check-2" :count="$runs->count()" :flush="true">
        <x-slot:tools>
            @if(in_array($period->status, ['approved', 'paid', 'locked']))
                @can('Approve payroll')
                    <form method="POST" action="{{ route('payroll.month.send', $period) }}" class="d-flex gap-2 align-items-center" onsubmit="return confirm('Send payslips to all staff?')">@csrf
                        <label class="form-check small mb-0"><input type="checkbox" class="form-check-input" name="email" value="1" checked> <span class="form-check-label">also email PDF</span></label>
                        <button class="action-btn btn-go"><i class="ri-send-plane-line"></i>Send payslips{{ $sent ? " ({$sent} sent)" : '' }}</button>
                    </form>
                @endcan
            @endif
        </x-slot:tools>
        <form class="cb-toolbar gap-2" method="GET">
            <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Name or staff ID">
            <select name="filter" class="cb-select" onchange="this.form.submit()"><option value="">All</option><option value="warnings" @selected(request('filter') === 'warnings')>With warnings</option></select>
        </form>
        @if($runs->isEmpty())
            <div class="empty-state"><i class="ri-calculator-line"></i><h6>Not calculated yet</h6><p>Click Calculate. Staff need a salary structure or a grade placement.</p></div>
        @else
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Staff</th><th class="text-end">Gross</th><th class="text-end">PAYE</th><th class="text-end">Pension</th><th class="text-end">Other</th><th class="text-end">Net</th><th></th></tr></thead>
                <tbody>
                @foreach($runs as $r)
                    <tr>
                        <td><strong>{{ $r->name }}</strong> @if(in_array($r->staff_id, $holds))<span class="status-pill st-danger">Hold</span>@endif
                            <div class="small text-muted">{{ $r->employmentid }}{{ ($r->proration ?? 1) < 1 ? ' · part month ' . round($r->proration * 100) . '%' : '' }}</div>
                            @foreach($r->warn as $w)<div class="small text-warning"><i class="ri-error-warning-line"></i> {{ $w }}</div>@endforeach</td>
                        <td class="text-end">{{ $m($r->total_earnings) }}</td>
                        <td class="text-end">{{ $m($r->paye_tax) }}</td>
                        <td class="text-end">{{ $m($r->employee_pension) }}</td>
                        <td class="text-end">{{ $m($r->total_deductions - $r->paye_tax - $r->employee_pension) }}</td>
                        <td class="text-end fw-bold">{{ $m($r->net_pay) }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('payroll.month.payslip', $r->id) }}" class="action-btn btn-open" title="Payslip"><i class="ri-file-list-3-line"></i></a>
                            <a href="{{ route('my-pay.index', ['staff' => $r->staff_id]) }}" class="action-btn btn-open" title="Pay history"><i class="ri-history-line"></i></a>
                            <a href="{{ route('payroll.profiles.edit', $r->staff_id) }}" class="action-btn btn-open" title="Pay profile"><i class="ri-user-settings-line"></i></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
@endsection
