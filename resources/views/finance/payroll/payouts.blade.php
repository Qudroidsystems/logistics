{{-- resources/views/finance/payroll/payouts.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Salary Payments" icon="ri-bank-card-line" subtitle="Send net pay straight to staff bank accounts — prepared by one person, released by another.">
        <x-slot:pills>
            @if($gateway['ready'])
                <span class="cb-meta-pill"><i class="ri-shield-check-line"></i>Paystack {{ $gateway['mode'] === 'live' ? 'LIVE' : 'TEST mode' }}</span>
                @if($gateway['balance'] !== null)<span class="cb-meta-pill"><i class="ri-wallet-3-line"></i>Balance {{ $m($gateway['balance']) }}</span>@endif
            @else
                <span class="cb-meta-pill"><i class="ri-error-warning-line"></i>Paystack not ready — manual bank upload only</span>
            @endif
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($gateway['ready'] && $gateway['mode'] !== 'live')<div class="cb-banner info"><i class="ri-flask-line"></i><div><strong>Test mode:</strong> transfers are simulated by Paystack — no real money moves. Switch to live keys in Payment Gateways when you're ready.</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Paid this month" :value="$m($stats['paid_month'])" icon="ri-checkbox-circle-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="With the bank" :value="$m($stats['in_flight'])" icon="ri-time-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Failed transfers" :value="$stats['failed']" icon="ri-close-circle-line" :accent="$stats['failed'] ? 'rose' : 'green'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Waiting for release" :value="$stats['drafts']" icon="ri-hourglass-line" accent="teal" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <x-cb.card title="Payment batches" icon="ri-stack-line" :count="$batches->total()" :flush="true">
                <x-slot:tools>
                    <form method="GET"><select name="status" class="cb-select" onchange="this.form.submit()"><option value="">All</option>@foreach(\App\Models\PayoutBatch::STATUS as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></form>
                </x-slot:tools>
                @if($batches->isEmpty())
                    <div class="empty-state"><i class="ri-bank-card-line"></i><h6>No payments yet</h6><p>Approve a payroll month, then choose it on the right to pay staff.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Batch</th><th>For</th><th>Method</th><th class="text-end">Staff</th><th class="text-end">Amount</th><th>Progress</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($batches as $b)
                            @php [$lbl, $cls] = $b->label(); @endphp
                            <tr>
                                <td><strong>{{ $b->reference }}</strong><div class="small text-muted">{{ $b->created_at->format('d M Y') }} · {{ $b->preparer->name ?? '—' }}</div></td>
                                <td>{{ $b->period->period_name ?? $b->note }}</td>
                                <td>{{ $b->provider === 'paystack' ? 'Paystack' : 'Bank upload' }}@if($b->mode === 'test' && $b->provider === 'paystack') <span class="status-pill st-muted">test</span>@endif</td>
                                <td class="text-end">{{ $b->item_count }}</td>
                                <td class="text-end fw-bold">{{ $m($b->total_amount) }}</td>
                                <td style="min-width:120px"><div class="progress-track"><div class="progress-fill" style="width:{{ $b->item_count ? round($b->success_count / $b->item_count * 100) : 0 }}%"></div></div><div class="small text-muted">{{ $b->success_count }}/{{ $b->item_count }} paid @if($b->failed_count)· <span class="text-danger">{{ $b->failed_count }} failed</span>@endif</div></td>
                                <td><span class="status-pill {{ $cls }}">{{ $lbl }}</span></td>
                                <td class="text-end"><a href="{{ route('payroll.payouts.show', $b) }}" class="action-btn btn-go"><i class="ri-eye-line"></i>Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                    <div class="p-3">{{ $batches->links() }}</div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-3">
            @canany(['Approve payroll', 'Release salary payments'])
            <x-cb.card title="Pay a payroll month" icon="ri-send-plane-line">
                @if($periods->isEmpty())
                    <div class="small text-muted">No approved payroll month yet.</div>
                @else
                    <form method="GET" onsubmit="this.action='{{ url('payroll/payouts/month') }}/'+this.period.value; return true;">
                        <select name="period" class="form-select form-select-sm mb-2">@foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->period_name }} ({{ ucfirst($p->status) }})</option>@endforeach</select>
                        <select name="provider" class="form-select form-select-sm mb-2"><option value="paystack" @disabled(!$gateway['ready'])>Paystack Transfers</option><option value="manual" @selected(!$gateway['ready'])>Bank upload (manual)</option></select>
                        <button class="action-btn btn-go w-100 justify-content-center"><i class="ri-arrow-right-line"></i>Continue</button>
                    </form>
                @endif
            </x-cb.card>
            @endcanany
            <x-cb.card title="How it works" icon="ri-information-line">
                <ol class="small ps-3 mb-0">
                    <li>Approve the payroll month.</li>
                    <li>Prepare a batch — staff with missing or unverified bank details are listed so you can fix them.</li>
                    <li>A different authorised person releases it with their password.</li>
                    <li>Statuses update automatically from Paystack; failed transfers can be retried in a new batch.</li>
                </ol>
            </x-cb.card>
            @if($gateway['ready'])<div class="small text-muted">Set your Paystack webhook URL to <code>{{ route('webhook.paystack') }}</code> so transfer results arrive instantly.</div>@endif
        </div>
    </div>
</div>
</div>
</div>
@endsection
