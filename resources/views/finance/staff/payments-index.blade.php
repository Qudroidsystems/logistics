{{-- resources/views/finance/staff/payments-index.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $m = fn ($v) => '₦' . number_format((float) $v, 2);
    $types = ['salary' => 'Salary', 'bonus' => 'Bonus', 'loan_disbursement' => 'Loan disbursement', 'reimbursement' => 'Reimbursement', 'advance' => 'Salary advance', 'other' => 'Other'];
    $pill = ['pending' => 'st-pending', 'processed' => 'st-info', 'paid' => 'st-paid', 'failed' => 'st-danger', 'reversed' => 'st-muted'];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Staff Payments" icon="ri-hand-coin-line" subtitle="Salaries, bonuses, advances, reimbursements and other payments made to staff.">
        <x-slot:actions>
            @can('Create staff payment')<a href="{{ route('staff.payments.create') }}" class="cb-hero-btn"><i class="ri-add-line"></i>Record payment</a>@endcan
            @can('View payroll')<a href="{{ route('payroll.periods') }}" class="cb-hero-btn"><i class="ri-calendar-check-line"></i>Payroll months</a>@endcan
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Paid this month" :value="$m($stats['month_paid'])" icon="ri-checkbox-circle-line" accent="green" :hint="$stats['month_count'] . ' payment(s)'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Awaiting payment" :value="$m($stats['pending'])" icon="ri-time-line" accent="amber" :hint="$stats['pending_count'] . ' payment(s)'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat :label="'Paid in ' . now()->year" :value="$m($stats['year_paid'])" icon="ri-calendar-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Reversed" :value="$stats['reversed']" icon="ri-refund-2-line" accent="rose" /></div>
    </div>

    <x-cb.card title="Payments" icon="ri-list-check-2" :count="$payments->total()" :flush="true">
        <form class="cb-toolbar gap-2 flex-wrap" method="GET">
            <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Staff name, ID or reference">
            <input type="month" name="month" class="form-control form-control-sm" style="width:160px" value="{{ request('month') }}" aria-label="Month">
            <select name="type" class="cb-select" aria-label="Type"><option value="">All types</option>@foreach($types as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
            <select name="status" class="cb-select" aria-label="Status"><option value="">All statuses</option>@foreach($pill as $k => $c)<option value="{{ $k }}" @selected(request('status') === $k)>{{ ucfirst($k) }}</option>@endforeach</select>
            <button class="action-btn btn-open"><i class="ri-filter-3-line"></i>Filter</button>
            @if(request()->hasAny(['search', 'month', 'type', 'status']))<a href="{{ route('staff.payments.index') }}" class="small">Clear</a>@endif
        </form>

        @if($payments->isEmpty())
            <div class="empty-state"><i class="ri-hand-coin-line"></i><h6>No payments found</h6><p>Salaries paid through payroll and one-off payments you record appear here.</p></div>
        @else
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Date</th><th>Staff</th><th>Type</th><th class="text-end">Amount</th><th>Method</th><th>Reference</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($payments as $p)
                    <tr>
                        <td class="text-nowrap">{{ optional($p->payment_date)->format('d M Y') ?? '—' }}</td>
                        <td><strong>{{ $p->staff->user->name ?? '—' }}</strong><div class="small text-muted">{{ $p->staff->employmentid ?? '' }}</div></td>
                        <td><span class="term-chip">{{ $types[$p->payment_type] ?? ucfirst($p->payment_type) }}</span>@if($p->purpose)<div class="small text-muted text-truncate" style="max-width:220px">{{ $p->purpose }}</div>@endif</td>
                        <td class="text-end fw-bold">{{ $m($p->amount) }}</td>
                        <td class="small">{{ ucfirst(str_replace('_', ' ', (string) $p->payment_method)) }}</td>
                        <td class="small text-muted">{{ $p->payment_reference }}</td>
                        <td><span class="status-pill {{ $pill[$p->payment_status] ?? 'st-muted' }}">{{ ucfirst($p->payment_status) }}</span></td>
                        <td class="text-end text-nowrap">
                            @if($p->payroll_run_id)<a href="{{ route('payroll.month.payslip', $p->payroll_run_id) }}" class="action-btn btn-open" title="Payslip"><i class="ri-file-list-3-line"></i></a>@endif
                            @can('Update staff payment')
                                @if(in_array($p->payment_status, ['pending', 'processed']))
                                    <button type="button" class="action-btn btn-go sp-paid" data-id="{{ $p->id }}" title="Mark as paid"><i class="ri-check-line"></i></button>
                                @endif
                            @endcan
                            @can('Reverse staff payment')
                                @if(!in_array($p->payment_status, ['reversed', 'paid']))
                                    <button type="button" class="action-btn btn-open sp-reverse" data-id="{{ $p->id }}" title="Reverse"><i class="ri-refund-2-line"></i></button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="p-3">{{ $payments->links() }}</div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
<script>
(function () {
    const token = document.querySelector('meta[name=csrf-token]')?.content;
    const post = async (url, body) => {
        const r = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token}, body: JSON.stringify(body || {})});
        const d = await r.json().catch(() => ({}));
        if (!r.ok || d.success === false) throw new Error(d.message || (d.errors ? Object.values(d.errors).flat()[0] : 'Failed'));
        return d;
    };
    document.querySelectorAll('.sp-paid').forEach(b => b.addEventListener('click', async () => {
        if (!confirm('Mark this payment as paid?')) return;
        try { await post(@json(url('/staff/payments/mark-paid')) + '/' + b.dataset.id); location.reload(); } catch (e) { alert(e.message); }
    }));
    document.querySelectorAll('.sp-reverse').forEach(b => b.addEventListener('click', async () => {
        const reason = prompt('Reason for reversing (at least 10 characters):');
        if (!reason) return;
        try { await post(@json(url('/staff/payments/reverse')) + '/' + b.dataset.id, {reason}); location.reload(); } catch (e) { alert(e.message); }
    }));
})();
</script>
@endsection
