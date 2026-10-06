{{-- resources/views/finance/payroll/periods.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $m = fn ($v) => '₦' . number_format((float) $v, 2);
    $S = ['draft' => ['Not calculated', 'st-muted', 'ri-draft-line'], 'processing' => ['Awaiting approval', 'st-pending', 'ri-time-line'], 'approved' => ['Approved', 'st-info', 'ri-checkbox-circle-line'], 'paid' => ['Paid', 'st-paid', 'ri-bank-card-line'], 'locked' => ['Locked', 'st-paid', 'ri-lock-line']];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Payroll Months" icon="ri-calendar-check-line" subtitle="Create a month, calculate, approve and lock. Open a month to see every staff member's pay.">
        <x-slot:actions>
            <a href="{{ route('payroll.summary', ['year' => $year]) }}" class="cb-hero-btn"><i class="ri-bar-chart-2-line"></i>Summary</a>
            @can('View payroll')<a href="{{ route('payroll.remittances') }}" class="cb-hero-btn"><i class="ri-government-line"></i>Remittances</a>@endcan
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat :label="'Gross pay ' . $year" :value="$m($ytd['gross'])" icon="ri-money-dollar-circle-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat :label="'Net pay ' . $year" :value="$m($ytd['net'])" icon="ri-wallet-3-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat :label="'PAYE ' . $year" :value="$m($ytd['paye'])" icon="ri-government-line" accent="violet" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Months still open" :value="$ytd['open']" icon="ri-time-line" :accent="$ytd['open'] ? 'amber' : 'green'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <x-cb.card :title="'Months in ' . $year" icon="ri-calendar-2-line" :count="$periods->count()" :flush="true">
                <form class="cb-toolbar" method="GET"><select name="year" class="cb-select" onchange="this.form.submit()" aria-label="Year">@foreach($years as $y)<option @selected($y == $year)>{{ $y }}</option>@endforeach</select></form>
                @if($periods->isEmpty())
                    <div class="empty-state"><i class="ri-calendar-check-line"></i><h6>No payroll months in {{ $year }}</h6><p>Create the first month on the right.</p></div>
                @else
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Month</th><th>Status</th><th class="text-end">Staff</th><th class="text-end">Gross</th><th class="text-end">PAYE</th><th class="text-end">Net pay</th><th>Pay date</th><th></th></tr></thead>
                        <tbody>
                        @foreach($periods as $p)
                            @php [$sl, $sc, $si] = $S[$p->status] ?? [$p->status, 'st-muted', 'ri-question-line']; @endphp
                            <tr style="cursor:pointer" onclick="if(!event.target.closest('a,button'))location='{{ route('payroll.month.show', $p) }}'">
                                <td><strong>{{ $p->period_name }}</strong>@if($p->approved_by)<div class="small text-muted">approved by {{ $users[$p->approved_by] ?? '—' }}</div>@endif</td>
                                <td><span class="status-pill {{ $sc }}"><i class="{{ $si }}"></i> {{ $sl }}</span></td>
                                <td class="text-end">{{ $p->staff_count ?: '—' }}</td>
                                <td class="text-end">{{ $p->total_gross_pay > 0 ? $m($p->total_gross_pay) : '—' }}</td>
                                <td class="text-end">{{ $p->total_tax > 0 ? $m($p->total_tax) : '—' }}</td>
                                <td class="text-end fw-bold">{{ $p->total_net_pay > 0 ? $m($p->total_net_pay) : '—' }}</td>
                                <td class="small">{{ $p->payment_date?->format('d M Y') }}</td>
                                <td class="text-end"><a href="{{ route('payroll.month.show', $p) }}" class="action-btn btn-go"><i class="ri-arrow-right-line"></i>Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-3">
            @can('Process payroll')
            <x-cb.card title="New payroll month" icon="ri-add-line">
                <form method="POST" action="{{ route('payroll.periods.create') }}">@csrf
                    <label class="small">Month</label><input type="month" name="month" class="form-control form-control-sm mb-2" value="{{ $suggest->format('Y-m') }}" required>
                    <label class="small">Pay date</label><input type="date" name="payment_date" class="form-control form-control-sm mb-3" value="{{ $suggest->copy()->day(min(25, $suggest->daysInMonth))->toDateString() }}" required>
                    <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-add-line"></i>Create month</button>
                </form>
            </x-cb.card>
            @endcan
            <x-cb.card title="The steps" icon="ri-route-line">
                <ol class="small ps-3 mb-0">
                    <li class="mb-1">Check <a href="{{ route('payroll.profiles') }}">pay profiles</a> and allowances.</li>
                    <li class="mb-1"><b>Calculate</b> — review warnings and "what changed".</li>
                    <li class="mb-1"><b>Approve</b> — staff see payslips; remittances are prepared.</li>
                    <li class="mb-1">Pay staff (bank schedule), then <b>Lock</b>.</li>
                    <li>Send payslips and pay PAYE / pension on time.</li>
                </ol>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
