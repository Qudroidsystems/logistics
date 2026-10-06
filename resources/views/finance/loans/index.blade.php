{{-- resources/views/finance/loans/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Staff Loans & Advances" icon="ri-hand-coin-line" subtitle="Applications, approvals, pay-outs and salary-deducted repayments.">
        <x-slot:actions>
            @can('Manage cooperative')<a href="{{ route('payroll.coop') }}" class="cb-hero-btn"><i class="ri-safe-2-line"></i>Cooperative</a>@endcan
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Outstanding" :value="$m($stats['outstanding'])" icon="ri-scales-3-line" accent="amber" :hint="$stats['active'] . ' active'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Awaiting approval" :value="$stats['pending']" icon="ri-hourglass-line" :accent="$stats['pending'] ? 'rose' : 'green'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Approved, not paid out" :value="$m($stats['to_disburse'])" icon="ri-send-plane-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Recovered this month" :value="$m($stats['recovered_month'])" icon="ri-refund-2-line" accent="green" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Loans" icon="ri-list-check-2" :count="$loans->total()" :flush="true">
                <form class="cb-toolbar gap-2 flex-wrap" method="GET">
                    <input type="search" name="q" class="cb-search" placeholder="Name or reference" value="{{ request('q') }}">
                    <select name="status" class="cb-select" onchange="this.form.submit()"><option value="">All statuses</option><option value="open" @selected(request('status') === 'open')>Open</option>@foreach(\App\Models\LoanAdvance::STATUS as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
                    <select name="type" class="cb-select" onchange="this.form.submit()"><option value="">All types</option>@foreach(\App\Models\LoanAdvance::TYPES as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
                </form>
                @if($loans->isEmpty())
                    <div class="empty-state"><i class="ri-hand-coin-line"></i><h6>No loans</h6><p>Staff apply from My Pay › Loans, or record one here.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Staff</th><th>Type</th><th class="text-end">Amount</th><th class="text-end">Monthly</th><th style="min-width:130px">Repaid</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($loans as $l) @php [$lbl, $cls] = $l->label(); @endphp
                            <tr>
                                <td><strong>{{ $l->staff_name ?? '—' }}</strong><div class="small text-muted">{{ $l->reference_no }} · {{ $l->created_at->format('d M Y') }}</div></td>
                                <td>{{ $l->typeLabel() }}</td>
                                <td class="text-end">{{ $m($l->amount) }}</td>
                                <td class="text-end">{{ $m($l->monthly_repayment) }}<div class="small text-muted">× {{ $l->repayment_months }}</div></td>
                                <td>@if(in_array($l->status, ['active', 'completed', 'written_off']))<div class="progress-track"><div class="progress-fill" style="width:{{ $l->progress() }}%"></div></div><div class="small text-muted">{{ $m($l->balance) }} left</div>@else — @endif</td>
                                <td><span class="status-pill {{ $cls }}">{{ $lbl }}</span>@if($l->isPaused())<div class="small text-warning">paused</div>@endif</td>
                                <td class="text-end"><a href="{{ route('payroll.loans.show', $l->id) }}" class="action-btn btn-go"><i class="ri-eye-line"></i>Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                    <div class="p-3">{{ $loans->links() }}</div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            @can('Manage staff loans')
            <x-cb.card title="Record an application" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('payroll.loans.store') }}" id="adminLoan">@csrf
                    <select name="staff_id" class="form-select form-select-sm mb-2" required><option value="">Staff member…</option>@foreach($staff as $s)<option value="{{ $s->id }}" @selected(old('staff_id') == $s->id)>{{ $s->name }}</option>@endforeach</select>
                    <select name="type" class="form-select form-select-sm mb-2">@foreach(\App\Models\LoanAdvance::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <div class="row g-2 mb-2"><div class="col-7"><input type="number" name="amount" step="0.01" min="1" class="form-control form-control-sm" placeholder="Amount ₦" required></div><div class="col-5"><input type="number" name="months" min="1" max="60" value="1" class="form-control form-control-sm" placeholder="Months" required></div></div>
                    <input type="text" name="purpose" class="form-control form-control-sm mb-2" placeholder="Purpose" maxlength="500">
                    <select name="guarantor_staff_id" class="form-select form-select-sm mb-2"><option value="">Guarantor (optional)</option>@foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                    <div id="adminQuote" class="small mb-2"></div>
                    <div class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="override" value="1" id="ov"><label for="ov" class="form-check-label">Record even if it breaks a rule (noted on the loan)</label></div>
                    <button class="action-btn btn-go w-100 justify-content-center"><i class="ri-save-line"></i>Save for approval</button>
                </form>
            </x-cb.card>
            @endcan
            @canany(['Manage payroll settings', 'Approve staff loans'])
            <x-cb.card title="Loan rules" icon="ri-settings-3-line">
                <form method="POST" action="{{ route('payroll.loans.rules') }}" class="small">@csrf
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="enabled" value="1" @checked($rules['enabled']) id="en"><label for="en" class="form-check-label">Accept applications</label></div>
                    @foreach([
                        'max_advance_percent' => 'Advance limit (% of net pay)', 'max_advance_months' => 'Advance repaid within (months)',
                        'max_loan_multiple' => 'Loan limit (× monthly gross)', 'max_loan_months' => 'Longest loan (months)',
                        'max_deduction_percent' => 'All repayments ≤ (% of gross)', 'default_interest_rate' => 'Interest (% per year, flat)',
                        'min_service_months' => 'Minimum service (months)', 'guarantor_above' => 'Guarantor needed above (₦, 0 = never)',
                        'coop_loan_multiple' => 'Cooperative loan limit (× savings)',
                    ] as $k => $l)
                        <div class="d-flex justify-content-between align-items-center mb-1 gap-2"><label class="mb-0">{{ $l }}</label><input type="number" step="0.01" min="0" name="{{ $k }}" value="{{ $rules[$k] }}" class="form-control form-control-sm" style="width:100px"></div>
                    @endforeach
                    <div class="form-check mb-2 mt-2"><input class="form-check-input" type="checkbox" name="one_active_per_type" value="1" @checked($rules['one_active_per_type']) id="one"><label for="one" class="form-check-label">One open loan of each type per person</label></div>
                    <button class="action-btn btn-open w-100 justify-content-center"><i class="ri-save-line"></i>Save rules</button>
                </form>
            </x-cb.card>
            @endcanany
        </div>
    </div>
</div>
</div>
</div>
@can('Manage staff loans')
<script>
(function () {
    const f = document.getElementById('adminLoan'), out = document.getElementById('adminQuote'); let t;
    const money = v => '₦' + Number(v).toLocaleString('en-NG', {minimumFractionDigits: 2});
    function run() {
        if (!f.staff_id.value || !f.amount.value || !f.months.value) { out.innerHTML = ''; return; }
        fetch('{{ route('payroll.loans.quote') }}?' + new URLSearchParams({staff_id: f.staff_id.value, type: f.type.value, amount: f.amount.value, months: f.months.value}), {headers: {Accept: 'application/json'}})
            .then(r => r.json()).then(q => {
                if (q.monthly === undefined) return;
                out.innerHTML = '<b>' + money(q.monthly) + '</b>/month · total ' + money(q.total) + ' · last gross ' + money(q.pay.gross)
                    + (q.issues.length ? '<ul class="text-danger mb-0 ps-3">' + q.issues.map(i => '<li>' + i.replace(/</g, '&lt;') + '</li>').join('') + '</ul>' : ' <span class="text-success">✓ within rules</span>');
            });
    }
    ['input', 'change'].forEach(e => f.addEventListener(e, () => { clearTimeout(t); t = setTimeout(run, 400); }));
})();
</script>
@endcan
@endsection
