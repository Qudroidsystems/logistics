{{-- resources/views/finance/loans/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); [$lbl, $cls] = $loan->label(); $S = \App\Models\LoanRepayment::SOURCES; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$loan->typeLabel() . ' · ' . $staffName" icon="ri-hand-coin-line" :subtitle="$loan->reference_no . ($loan->purpose ? ' — ' . \Illuminate\Support\Str::limit($loan->purpose, 90) : '')" :back="route('payroll.loans')" back-label="Loans">
        <x-slot:actions>
            @can('View payroll')<a href="{{ route('my-pay.deductions', ['staff' => $loan->staff_id]) }}" class="cb-hero-btn"><i class="ri-history-line"></i>Pay history</a>@endcan
            @if(in_array($loan->status, ['pending', 'approved']))@can('Manage staff loans')<form method="POST" action="{{ route('payroll.loans.cancel', $loan) }}" class="d-inline" onsubmit="return confirm('Cancel this loan?')">@csrf<button class="cb-hero-btn"><i class="ri-close-line"></i>Cancel</button></form>@endcan @endif
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill"><span class="status-pill {{ $cls }}">{{ $lbl }}</span></span>
            <span class="cb-meta-pill"><i class="ri-user-line"></i>Entered by {{ $loan->createdBy->name ?? '—' }} · {{ $loan->created_at->format('d M Y') }}</span>
            @if($loan->approvedBy)<span class="cb-meta-pill"><i class="ri-checkbox-circle-line"></i>{{ $loan->status === 'rejected' ? 'Declined' : 'Approved' }} by {{ $loan->approvedBy->name }}</span>@endif
            @if($guarantorName)<span class="cb-meta-pill"><i class="ri-shake-hands-line"></i>Guarantor: {{ $guarantorName }} ({{ $loan->guarantor_status ?? 'pending' }})</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($loan->notes)<div class="cb-banner info"><i class="ri-sticky-note-line"></i><div>{{ $loan->notes }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Amount" :value="$m($loan->amount)" icon="ri-money-dollar-circle-line" accent="teal" :hint="$loan->interest_rate > 0 ? $loan->interest_rate . '% p.a. flat' : 'No interest'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Monthly" :value="$m($loan->monthly_repayment)" icon="ri-calendar-line" accent="amber" :hint="$loan->repayment_months . ' month(s)'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Repaid" :value="$m($loan->repaid())" icon="ri-refund-2-line" accent="green" :hint="$loan->progress() . '%'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Balance" :value="$m($loan->balance)" icon="ri-scales-3-line" :accent="$loan->balance > 0 ? 'rose' : 'green'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            @if($check)
            <x-cb.card title="Affordability check" icon="ri-shield-check-line">
                <div class="row small g-2">
                    <div class="col-md-4">Last gross pay<br><b>{{ $m($check['pay']['gross']) }}</b></div>
                    <div class="col-md-4">Existing repayments<br><b>{{ $m($check['current_repayments']) }}</b>/month</div>
                    <div class="col-md-4">Repayment limit<br><b>{{ $m($check['deduction_cap']) }}</b>/month</div>
                </div>
                @if($check['issues'])<ul class="text-danger small mt-2 mb-0">@foreach($check['issues'] as $i)<li>{{ $i }}</li>@endforeach</ul>@else<div class="text-success small mt-2"><i class="ri-checkbox-circle-line"></i> Within all loan rules.</div>@endif
            </x-cb.card>
            @endif

            <x-cb.card title="Repayment schedule" icon="ri-calendar-2-line" :count="$loan->repaymentSchedule->count()" :flush="true">
                @if($loan->repaymentSchedule->isEmpty())
                    <div class="empty-state"><i class="ri-calendar-2-line"></i><h6>Not started</h6><p>The schedule is created when the money is paid out.</p></div>
                @else
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                        <thead><tr><th>#</th><th>Due</th><th class="text-end">Amount</th><th class="text-end">Principal</th><th class="text-end">Interest</th><th class="text-end">Paid</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($loan->repaymentSchedule as $i)
                            <tr class="{{ $i->status === 'due' && $i->due_date->isPast() ? 'table-warning' : '' }}"><td>{{ $i->installment_no }}</td><td>{{ $i->due_date->format('M Y') }}</td><td class="text-end">{{ $m($i->amount) }}</td><td class="text-end">{{ $m($i->principal) }}</td><td class="text-end">{{ $m($i->interest) }}</td><td class="text-end">{{ $m($i->paid_amount) }}</td><td>{{ ucfirst($i->status) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>

            <x-cb.card title="Repayments received" icon="ri-refund-2-line" :count="$loan->repayments->count()" :flush="true">
                @if($loan->repayments->isEmpty())
                    <div class="p-3 small text-muted">None yet. Salary deductions are recorded when each payroll month is locked.</div>
                @else
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Date</th><th>How</th><th>Reference</th><th class="text-end">Amount</th><th>By</th></tr></thead>
                        <tbody>@foreach($loan->repayments as $r)<tr><td>{{ $r->paid_on->format('d M Y') }}</td><td>{{ $S[$r->source] ?? $r->source }}</td><td class="small">{{ $r->reference }} {{ $r->note }}</td><td class="text-end fw-bold">{{ $m($r->amount) }}</td><td class="small">{{ $r->recorder->name ?? 'System' }}</td></tr>@endforeach</tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>

        <div class="col-xl-4">
            @if($loan->status === 'pending')
                @can('Approve staff loans')
                <x-cb.card title="Decide" icon="ri-scales-line">
                    <form method="POST" action="{{ route('payroll.loans.approve', $loan) }}" class="small">@csrf
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" value="{{ $loan->amount }}" class="form-control form-control-sm"></div>
                            <div class="col-6"><label class="form-label">Months</label><input type="number" name="months" value="{{ $loan->repayment_months }}" class="form-control form-control-sm"></div>
                            <div class="col-6"><label class="form-label">Interest % p.a.</label><input type="number" step="0.01" name="rate" value="{{ $loan->interest_rate }}" class="form-control form-control-sm"></div>
                            <div class="col-6"><label class="form-label">First deduction</label><input type="month" name="first_month" class="form-control form-control-sm" min="{{ now()->format('Y-m') }}"></div>
                        </div>
                        <button class="action-btn btn-primary-cb w-100 justify-content-center mb-2"><i class="ri-check-line"></i>Approve</button>
                    </form>
                    <form method="POST" action="{{ route('payroll.loans.reject', $loan) }}" class="d-flex gap-2">@csrf
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason for declining" required>
                        <button class="action-btn btn-open">Decline</button>
                    </form>
                </x-cb.card>
                @endcan
            @endif

            @if($loan->status === 'approved')
                @can('Manage staff loans')
                <x-cb.card title="Pay out" icon="ri-send-plane-line">
                    <form method="POST" action="{{ route('payroll.loans.disburse', $loan) }}" class="small">@csrf
                        <select name="method" class="form-select form-select-sm mb-2" onchange="document.getElementById('dref').style.display=this.value==='paystack'?'none':''">
                            <option value="paystack">Paystack transfer (needs release)</option><option value="transfer">Already paid by bank transfer</option><option value="cash">Already paid in cash</option>
                        </select>
                        <input id="dref" type="text" name="reference" class="form-control form-control-sm mb-2" placeholder="Bank / receipt reference" style="display:none">
                        <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-send-plane-line"></i>Pay {{ $m($loan->amount) }}</button>
                    </form>
                </x-cb.card>
                @endcan
            @endif
            @if($payout)<div class="cb-banner info"><i class="ri-bank-card-line"></i><div>Payout <a href="{{ route('payroll.payouts.show', $payout) }}">{{ $payout->reference }}</a> — {{ $payout->label()[0] }}</div></div>@endif

            @if($loan->status === 'active')
                @can('Manage staff loans')
                <x-cb.card title="Record a repayment" icon="ri-refund-2-line">
                    <form method="POST" action="{{ route('payroll.loans.repay', $loan) }}" class="small">@csrf
                        <div class="row g-2 mb-2"><div class="col-6"><input type="number" step="0.01" name="amount" max="{{ $loan->balance }}" class="form-control form-control-sm" placeholder="Amount" required></div>
                        <div class="col-6"><select name="source" class="form-select form-select-sm"><option value="cash">Cash</option><option value="transfer">Bank transfer</option></select></div></div>
                        <input type="date" name="paid_on" class="form-control form-control-sm mb-2" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
                        <input type="text" name="reference" class="form-control form-control-sm mb-2" placeholder="Receipt / reference">
                        <button class="action-btn btn-go w-100 justify-content-center">Save repayment</button>
                    </form>
                </x-cb.card>
                <x-cb.card title="Pause deductions" icon="ri-pause-circle-line">
                    <form method="POST" action="{{ route('payroll.loans.pause', $loan) }}" class="d-flex gap-2 small">@csrf
                        <input type="month" name="until" class="form-control form-control-sm" value="{{ $loan->paused_until?->format('Y-m') }}" min="{{ now()->format('Y-m') }}">
                        <button class="action-btn btn-open">{{ $loan->paused_until ? 'Update' : 'Pause' }}</button>
                    </form>
                    @if($loan->paused_until)<form method="POST" action="{{ route('payroll.loans.pause', $loan) }}" class="mt-2">@csrf<button class="action-btn btn-go w-100 justify-content-center">Resume now</button></form>@endif
                    <div class="small text-muted mt-1">Skips the salary deduction up to and including that month; later instalments move back.</div>
                </x-cb.card>
                @endcan
                @can('Approve staff loans')
                <x-cb.card title="Write off" icon="ri-delete-back-2-line">
                    <form method="POST" action="{{ route('payroll.loans.writeoff', $loan) }}" class="d-flex gap-2" onsubmit="return confirm('Write off {{ $m($loan->balance) }}? This cannot be undone.')">@csrf
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" required><button class="action-btn btn-open">Write off</button>
                    </form>
                </x-cb.card>
                @endcan
            @endif
        </div>
    </div>
</div>
</div>
</div>
@endsection
