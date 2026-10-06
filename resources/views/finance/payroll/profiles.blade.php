{{-- resources/views/finance/payroll/profiles.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Staff Pay Profiles" icon="ri-bank-card-line" subtitle="Bank account, TIN, pension and which deductions apply to each staff member.">
        <x-slot:actions>
            @can('Manage payroll settings')<a href="{{ route('payroll.rates') }}" class="cb-hero-btn"><i class="ri-percent-line"></i>Rates & tax bands</a>@endcan
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6"><x-cb.stat label="Staff" :value="$rows->count()" icon="ri-team-line" accent="teal" /></div>
        <div class="col-md-4 col-6"><x-cb.stat label="Ready to pay" :value="$complete" icon="ri-checkbox-circle-line" accent="green" /></div>
        <div class="col-md-4 col-6"><x-cb.stat label="Need details" :value="$rows->count() - $complete" icon="ri-error-warning-line" accent="amber" /></div>
    </div>

    <x-cb.card title="Staff" icon="ri-list-check-2" :count="$rows->count()" :flush="true">
        <form class="cb-toolbar gap-2" method="GET">
            <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Name or staff ID">
            <select name="filter" class="cb-select" onchange="this.form.submit()" aria-label="Filter">
                <option value="">All staff</option>
                <option value="incomplete" @selected(request('filter') === 'incomplete')>Missing details</option>
                <option value="hold" @selected(request('filter') === 'hold')>Pay on hold</option>
            </select>
        </form>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Staff</th><th class="text-end">Monthly gross</th><th>Bank</th><th>Deductions</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    @php $p = $r->profile; @endphp
                    <tr>
                        <td><strong>{{ $r->name }}</strong><div class="small text-muted">{{ $r->employmentid ?: '—' }} · {{ \App\Models\StaffPayProfile::TYPES[$p->employment_type ?? 'full_time'] ?? 'Full-time' }}</div></td>
                        <td class="text-end">{{ $r->gross !== null ? '₦' . number_format($r->gross, 2) : '—' }}</td>
                        <td class="small">
                            @if($p->account_last4)
                                {{ $p->bank_name }} · {{ $p->maskedAccount() }}
                                <div>@if($p->account_verified_at)<span class="text-success"><i class="ri-shield-check-line"></i> {{ $p->account_name }}</span>@else<span class="text-warning">Not verified</span>@endif</div>
                            @else <span class="text-muted">None</span> @endif
                        </td>
                        <td class="small">
                            @foreach(['paye_enabled' => 'PAYE', 'pension_enabled' => 'Pension', 'nhf_enabled' => 'NHF', 'nhia_enabled' => 'NHIA'] as $f => $l)
                                @if($p->$f ?? ($f === 'paye_enabled' || $f === 'pension_enabled'))<span class="term-chip">{{ $l }}</span>@endif
                            @endforeach
                        </td>
                        <td>
                            @if(($p->pay_status ?? 'active') === 'hold')<span class="status-pill st-danger">On hold</span>
                            @elseif(($p->pay_status ?? 'active') === 'exited')<span class="status-pill st-muted">Left</span>
                            @elseif($r->gaps)<span class="status-pill st-warning" title="{{ implode(', ', $r->gaps) }}">Missing {{ count($r->gaps) }}</span>
                            @else<span class="status-pill st-paid">Ready</span>@endif
                            @if($r->gaps)<div class="small text-muted">{{ implode(', ', array_slice($r->gaps, 0, 3)) }}</div>@endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('payroll.profiles.preview', $r->id) }}" class="action-btn btn-open" title="See this month's calculation"><i class="ri-calculator-line"></i></a>
                            <a href="{{ route('payroll.profiles.edit', $r->id) }}" class="action-btn btn-go"><i class="ri-edit-line"></i>Edit</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-cb.card>
</div>
</div>
</div>
@endsection
