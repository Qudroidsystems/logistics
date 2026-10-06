{{-- resources/views/finance/my-pay/loans.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Loans & Advances" icon="ri-hand-coin-line" subtitle="Apply for a salary advance, staff loan or cooperative loan — repaid automatically from your salary." />
    @include('finance.my-pay._nav', ['section' => 'loans'])

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    @foreach($guaranteeRequests as $g)
        <div class="cb-banner info"><i class="ri-shake-hands-line"></i><div class="flex-grow-1">You were named as guarantor for a {{ strtolower($g->typeLabel()) }} of <b>{{ $m($g->amount) }}</b> ({{ $g->reference_no }}). If the borrower stops paying, the school may recover the balance from you.</div>
            <form method="POST" action="{{ route('my-pay.loans.guarantee', $g) }}" class="d-flex gap-2">@csrf
                <button name="answer" value="accept" class="action-btn btn-go">Accept</button><button name="answer" value="decline" class="action-btn btn-open">Decline</button>
            </form>
        </div>
    @endforeach

    @php $open = $myLoans->whereIn('status', ['active']); @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Owed now" :value="$m($open->sum('balance'))" icon="ri-scales-3-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Monthly deduction" :value="$m($open->sum('monthly_repayment'))" icon="ri-calendar-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Last net pay" :value="$m($pay['net'])" icon="ri-wallet-3-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Cooperative savings" :value="$m($savings)" icon="ri-safe-2-line" accent="teal" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="My applications" icon="ri-file-list-3-line" :count="$myLoans->count()" :flush="true">
                @if($myLoans->isEmpty())
                    <div class="empty-state"><i class="ri-hand-coin-line"></i><h6>No loans yet</h6><p>Use the form to apply.</p></div>
                @else
                    <ul class="list-group list-group-flush">
                    @foreach($myLoans as $l)
                        @php [$lbl, $cls] = $l->label(); @endphp
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div><b>{{ $l->typeLabel() }}</b> · {{ $l->reference_no }} <span class="status-pill {{ $cls }}">{{ $lbl }}</span>
                                    <div class="small text-muted">{{ $m($l->amount) }} · {{ $m($l->monthly_repayment) }} × {{ $l->repayment_months }} month(s){{ $l->interest_rate > 0 ? ' · ' . $l->interest_rate . '% p.a.' : '' }} · applied {{ $l->created_at->format('d M Y') }}</div>
                                    @if($l->rejection_reason)<div class="small text-danger">{{ $l->rejection_reason }}</div>@endif
                                    @if($l->isPaused())<div class="small text-warning">Deductions paused until {{ $l->paused_until->format('M Y') }}</div>@endif
                                </div>
                                <div class="text-end">
                                    @if($l->status === 'active')<div class="fw-bold">{{ $m($l->balance) }}</div><div class="small text-muted">left</div>@endif
                                    @if($l->status === 'pending')<form method="POST" action="{{ route('my-pay.loans.cancel', $l) }}" onsubmit="return confirm('Withdraw this application?')">@csrf<button class="action-btn btn-open">Withdraw</button></form>@endif
                                </div>
                            </div>
                            @if(in_array($l->status, ['active', 'completed']))
                                <div class="progress-track mt-2"><div class="progress-fill" style="width:{{ $l->progress() }}%"></div></div>
                                <details class="small mt-1"><summary>Repayment schedule</summary>
                                    <table class="table table-sm mb-0 mt-1"><thead><tr><th>#</th><th>Due</th><th class="text-end">Amount</th><th>Status</th></tr></thead><tbody>
                                    @foreach($l->repaymentSchedule as $i)<tr><td>{{ $i->installment_no }}</td><td>{{ $i->due_date->format('M Y') }}</td><td class="text-end">{{ $m($i->amount) }}</td><td>{{ ucfirst($i->status) }}</td></tr>@endforeach
                                    </tbody></table>
                                </details>
                            @endif
                        </li>
                    @endforeach
                    </ul>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-5">
            <x-cb.card title="Apply" icon="ri-add-circle-line">
                @if(!$rules['enabled'])
                    <div class="small text-muted">Loan applications are closed at the moment.</div>
                @else
                <form method="POST" action="{{ route('my-pay.loans.apply') }}" id="loanForm">@csrf
                    <label class="form-label small">Type</label>
                    <select name="type" class="form-select mb-2" required>
                        <option value="advance" @selected(old('type') === 'advance')>Salary advance (up to {{ $rules['max_advance_percent'] }}% of net pay, no interest)</option>
                        <option value="loan" @selected(old('type') === 'loan')>Staff loan (up to {{ $rules['max_loan_multiple'] }}× monthly gross)</option>
                        @if($savings > 0)<option value="cooperative" @selected(old('type') === 'cooperative')>Cooperative loan (up to {{ $rules['coop_loan_multiple'] }}× savings)</option>@endif
                    </select>
                    <div class="row g-2">
                        <div class="col-7"><label class="form-label small">Amount (₦)</label><input type="number" name="amount" step="0.01" min="1" class="form-control" value="{{ old('amount') }}" required></div>
                        <div class="col-5"><label class="form-label small">Months to repay</label><input type="number" name="months" min="1" max="60" class="form-control" value="{{ old('months', 1) }}" required></div>
                    </div>
                    <label class="form-label small mt-2">Purpose</label>
                    <textarea name="purpose" class="form-control" rows="2" required maxlength="500">{{ old('purpose') }}</textarea>
                    <div id="guarantorBox" class="mt-2" style="display:none">
                        <label class="form-label small">Guarantor (a colleague)</label>
                        <select name="guarantor_staff_id" class="form-select"><option value="">— choose —</option>@foreach($colleagues as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                    </div>
                    <div id="quote" class="cb-banner info mt-3 mb-2" style="display:none"></div>
                    <div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="agree" value="1" id="agree" required><label class="form-check-label" for="agree">I agree that repayments are deducted from my salary until the balance is cleared, and any balance may be recovered from my final entitlements if I leave.</label></div>
                    <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-send-plane-line"></i>Submit application</button>
                </form>
                @endif
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@if($rules['enabled'])
<script>
(function () {
    const f = document.getElementById('loanForm'), box = document.getElementById('quote'), g = document.getElementById('guarantorBox');
    const money = v => '₦' + Number(v).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    let t;
    function run() {
        const amount = f.amount.value, months = f.months.value;
        if (!amount || !months) { box.style.display = 'none'; return; }
        fetch('{{ route('my-pay.loans.quote') }}?' + new URLSearchParams({type: f.type.value, amount, months}), {headers: {'Accept': 'application/json'}})
            .then(r => r.json()).then(q => {
                if (!q || q.monthly === undefined) return;
                g.style.display = q.needs_guarantor ? '' : 'none';
                let h = '<div><b>' + money(q.monthly) + '</b> a month for ' + q.months + ' month(s). Total to repay ' + money(q.total) + (q.interest > 0 ? ' (interest ' + money(q.interest) + ')' : '') + '.';
                if (q.issues && q.issues.length) h += '<ul class="mb-0 mt-1 text-danger">' + q.issues.map(i => '<li>' + i.replace(/</g, '&lt;') + '</li>').join('') + '</ul>';
                box.innerHTML = h + '</div>'; box.style.display = '';
            }).catch(() => {});
    }
    ['input', 'change'].forEach(ev => f.addEventListener(ev, () => { clearTimeout(t); t = setTimeout(run, 350); }));
    run();
})();
</script>
@endif
@endsection
