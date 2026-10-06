{{-- resources/views/online-fees/transaction.blade.php
     Status + receipt for one online fee payment. --}}
@extends('layouts.master')

@section('content')
@php
    $k2n   = fn ($k) => '₦' . number_format(((int) $k) / 100, 2);
    $p     = $payment;
    $state = $p->status === 'success' ? 'success' : ($p->status === 'pending' ? 'pending' : ($p->status === 'amount_mismatch' ? 'review' : 'failed'));
    $icons = ['success' => 'ri-checkbox-circle-fill', 'pending' => 'ri-loader-4-line', 'review' => 'ri-error-warning-fill', 'failed' => 'ri-close-circle-fill'];
    $titles = [
        'success' => 'Payment successful',
        'pending' => 'Confirming your payment…',
        'review'  => 'Payment needs review',
        'failed'  => $p->status === 'abandoned' ? 'Payment not completed' : 'Payment failed',
    ];
    $name  = $student ? trim($student->lastname . ' ' . $student->firstname . ' ' . ($student->othername ?? '')) : '—';
    $isOwn = (int) (auth()->user()->student_id ?? 0) === (int) $p->student_id;
    $retry = $isOwn
        ? route('student.fees.pay', ['term_id' => $p->term_id, 'session_id' => $p->session_id])
        : (auth()->user()->can('Create online-fee-payments') ? route('online-fees.pay-for', ['student' => $p->student_id, 'term_id' => $p->term_id, 'session_id' => $p->session_id]) : null);
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="no-print">
        <x-cb.hero title="Online Payment" icon="ri-secure-payment-line" :subtitle="'Reference ' . $p->reference"
                   :back="$isOwn ? route('student.payments', ['term_id' => $p->term_id, 'session_id' => $p->session_id]) : route('online-fees.index')"
                   :back-label="$isOwn ? 'My Payments' : 'Online payments'">
            <x-slot:actions>
                @if($p->status === 'success')
                    <button type="button" class="cb-hero-btn" onclick="window.print()"><i class="ri-printer-line"></i>Print receipt</button>
                @endif
                @if($isStaff && !$p->posted_at && auth()->user()->can('Update online-fee-payments'))
                    <form method="POST" action="{{ route('online-fees.verify', $p->reference) }}" class="d-inline">@csrf
                        <button class="cb-hero-btn"><i class="ri-refresh-line"></i>Re-check with {{ $p->gateway === 'opay' ? 'OPay' : 'Paystack' }}</button>
                    </form>
                @endif
            </x-slot:actions>
        </x-cb.hero>

        @foreach(['success', 'error'] as $f)
            @if(session($f))<div class="cb-banner {{ $f === 'success' ? 'info' : 'warning' }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
        @endforeach
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="cb-card of-receipt">
                <div class="of-status of-{{ $state }}">
                    <i class="{{ $icons[$state] }} {{ $state === 'pending' ? 'of-spin' : '' }}"></i>
                    <div>
                        <h4>{{ $titles[$state] }}</h4>
                        <p class="mb-0">
                            @switch($state)
                                @case('success') {{ $k2n($p->paid_kobo) }} received{{ $p->paid_at ? ' on ' . $p->paid_at->format('d M Y, g:i a') : '' }} and applied to the bills below. @break
                                @case('pending') We're waiting for {{ $p->gateway === 'opay' ? 'OPay' : 'Paystack' }} to confirm this payment. This page updates by itself; you can also come back later. @break
                                @case('review') {{ $p->failure_reason }} The bursary will resolve this. @break
                                @default {{ $p->failure_reason ?: 'No money was taken for this attempt.' }}
                            @endswitch
                        </p>
                    </div>
                    <div class="of-status-amount">{{ $k2n($p->amount_kobo) }}</div>
                </div>

                <div class="cb-card-body">
                    <div class="d-none d-print-flex align-items-center gap-3 mb-3">
                        @if($school && $school->school_logo)<img src="{{ asset('storage/' . $school->school_logo) }}" alt="" style="height:54px" onerror="this.remove()">@endif
                        <div><h5 class="mb-0">{{ $school->school_name ?? '' }}</h5><small>{{ $school->school_address ?? '' }}</small></div>
                        <div class="ms-auto text-end"><strong>PAYMENT RECEIPT</strong><br><small>{{ $p->reference }}</small></div>
                    </div>

                    <div class="row g-3 of-meta">
                        <div class="col-sm-6"><span>Student</span><strong>{{ $name }}</strong><small>{{ $student->admissionNo ?? '' }}</small></div>
                        <div class="col-sm-6"><span>Reference</span><strong><code>{{ $p->reference }}</code></strong><small>{{ $p->gateway === 'opay' ? 'OPay' : 'Paystack' }}{{ $p->mode === 'live' ? '' : ' (test mode)' }}</small></div>
                        <div class="col-sm-6"><span>Paid by</span><strong>{{ $p->payer->name ?? ucfirst($p->payer_type) }}</strong><small>{{ $p->email }}</small></div>
                        <div class="col-sm-6"><span>Channel</span><strong>{{ $p->channel ? ucwords(str_replace('_', ' ', $p->channel)) : '—' }}</strong><small>Started {{ $p->created_at->format('d M Y, g:i a') }}</small></div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="cb-table mb-0">
                            <thead><tr><th>Bill</th><th>Term</th><th class="text-end">Amount</th>@if($p->posted_at)<th class="text-end">Applied</th>@endif</tr></thead>
                            <tbody>
                            @foreach($p->items->sortByDesc('is_arrear') as $it)
                                <tr>
                                    <td><div class="fw-semibold">{{ $it->title }}</div>@if($it->is_arrear)<span class="status-pill st-warning">Arrear</span>@endif</td>
                                    <td>{{ $labels['terms'][$it->term_id] ?? '' }} · {{ $labels['sessions'][$it->session_id] ?? '' }}<br><small class="text-muted">{{ $labels['classes'][$it->class_id] ?? '' }}</small></td>
                                    <td class="text-end">{{ $k2n($it->amount_kobo) }}</td>
                                    @if($p->posted_at)<td class="text-end">{{ $k2n($it->applied_kobo) }}</td>@endif
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot><tr>
                                <th colspan="2">Total</th>
                                <th class="text-end">{{ $k2n($p->amount_kobo) }}</th>
                                @if($p->posted_at)<th class="text-end">{{ $k2n($p->applied_kobo) }}</th>@endif
                            </tr></tfoot>
                        </table>
                    </div>

                    @if($p->unapplied_kobo > 0)
                        <div class="cb-banner warning mt-3 mb-0"><i class="ri-error-warning-line"></i>
                            <div>{{ $k2n($p->unapplied_kobo) }} of this payment was not applied because the bill had already been settled. The bursary has been flagged to refund it or move it to another bill.</div></div>
                    @endif

                    @if($isStaff && $p->gateway_fee_kobo > 0)
                        <p class="small text-muted mt-3 mb-0 no-print">{{ $p->gateway === 'opay' ? 'OPay' : 'Paystack' }} fee (paid by the school): {{ $k2n($p->gateway_fee_kobo) }} · net settlement {{ $k2n($p->paid_kobo - $p->gateway_fee_kobo) }}</p>
                    @endif

                    <div class="d-flex flex-wrap gap-2 mt-4 no-print">
                        @if(in_array($state, ['failed']) && $retry)
                            <a href="{{ $retry }}" class="action-btn btn-primary-cb"><i class="ri-refresh-line"></i>Try again</a>
                        @endif
                        @if($state === 'success' && $retry)
                            <a href="{{ $retry }}" class="action-btn btn-open"><i class="ri-bill-line"></i>See remaining balance</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<style>
.of-status { display: flex; align-items: center; gap: 16px; padding: 22px 24px; border-bottom: 1px solid var(--cb-border); }
.of-status > i { font-size: 44px; line-height: 1; }
.of-status h4 { margin: 0 0 4px; font-weight: 700; color: var(--cb-heading); }
.of-status p { color: var(--cb-muted); font-size: 13px; }
.of-status-amount { margin-left: auto; font-size: 22px; font-weight: 800; color: var(--cb-heading); white-space: nowrap; }
.of-success > i { color: #16a34a; } .of-success { background: rgba(22,163,74,.06); }
.of-pending > i { color: #d97706; } .of-pending { background: rgba(217,119,6,.06); }
.of-review  > i { color: #d97706; } .of-review  { background: rgba(217,119,6,.08); }
.of-failed  > i { color: #dc2626; } .of-failed  { background: rgba(220,38,38,.05); }
.of-spin { animation: ofSpin 1.2s linear infinite; }
@keyframes ofSpin { to { transform: rotate(360deg); } }
.of-meta span { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--cb-muted); }
.of-meta strong { display: block; color: var(--cb-heading); }
.of-meta small { color: var(--cb-muted); }
@media (max-width: 576px) { .of-status { flex-wrap: wrap; } .of-status-amount { margin-left: 0; } }
@media print {
    .app-menu, #page-topbar, .footer, .no-print, .vertical-overlay { display: none !important; }
    .main-content { margin: 0 !important; } .page-content { padding: 0 !important; }
    .of-receipt { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

@if($state === 'pending')
<script>
(function () {
    const url = @json(route('online-fees.status', $p->reference));
    let tries = 0;
    const tick = async () => {
        tries++;
        try {
            const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const j = await r.json();
            if (j.status && j.status !== 'pending') { window.location.reload(); return; }
        } catch (e) {}
        if (tries < 30) setTimeout(tick, tries < 10 ? 4000 : 10000);
    };
    setTimeout(tick, 3000);
})();
</script>
@endif
@endsection
