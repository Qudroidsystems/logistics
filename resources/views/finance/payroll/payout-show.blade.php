{{-- resources/views/finance/payroll/payout-show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); [$lbl, $cls] = $batch->label(); $canRelease = auth()->user()->can('Release salary payments'); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Payout ' . $batch->reference" icon="ri-bank-card-line" :subtitle="$batch->note" :back="route('payroll.payouts')" back-label="Salary payments">
        <x-slot:actions>
            @if($canRelease && $batch->provider === 'manual')<a href="{{ route('payroll.payouts.schedule', $batch) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Bank file</a>@endif
            @if($canRelease && $batch->provider === 'paystack' && in_array($batch->status, ['processing', 'partial']))<form method="POST" action="{{ route('payroll.payouts.refresh', $batch) }}" class="d-inline">@csrf<button class="cb-hero-btn"><i class="ri-refresh-line"></i>Check status</button></form>@endif
            @if($canRelease && $batch->failed_count > 0)<form method="POST" action="{{ route('payroll.payouts.retry', $batch) }}" class="d-inline" onsubmit="return confirm('Move failed transfers into a new batch?')">@csrf<button class="cb-hero-btn"><i class="ri-restart-line"></i>Retry failed</button></form>@endif
            @if($canRelease && $batch->status === 'draft')<form method="POST" action="{{ route('payroll.payouts.cancel', $batch) }}" class="d-inline" onsubmit="return confirm('Cancel this batch?')">@csrf<button class="cb-hero-btn"><i class="ri-close-line"></i>Cancel</button></form>@endif
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill"><span class="status-pill {{ $cls }}">{{ $lbl }}</span></span>
            <span class="cb-meta-pill"><i class="ri-bank-line"></i>{{ $batch->provider === 'paystack' ? 'Paystack' . ($batch->mode === 'test' ? ' (test)' : '') : 'Bank upload' }}</span>
            <span class="cb-meta-pill"><i class="ri-user-line"></i>Prepared by {{ $batch->preparer->name ?? '—' }}</span>
            @if($batch->released_at)<span class="cb-meta-pill"><i class="ri-send-plane-line"></i>Released by {{ $batch->releaser->name ?? '—' }} · {{ $batch->released_at->format('d M, H:i') }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Total" :value="$m($batch->total_amount)" icon="ri-money-dollar-circle-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Paid" :value="$batch->success_count . ' / ' . $batch->item_count" icon="ri-checkbox-circle-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Failed" :value="$batch->failed_count" icon="ri-close-circle-line" :accent="$batch->failed_count ? 'rose' : 'green'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Amount paid" :value="$m($items->filter->isPaid()->sum('amount'))" icon="ri-wallet-3-line" accent="amber" /></div>
    </div>

    @if($batch->status === 'draft' && $canRelease)
        <x-cb.card title="Release this batch" icon="ri-lock-password-line">
            @if((int) $batch->prepared_by === (int) auth()->id())
                <div class="cb-banner warning mb-0"><i class="ri-user-shared-line"></i><div>You prepared this batch. Another person with “Release salary payments” must release it.</div></div>
            @else
                <form method="POST" action="{{ route('payroll.payouts.release', $batch) }}" class="row g-2 align-items-end" onsubmit="return confirm('Send {{ $m($batch->total_amount) }} to {{ $batch->item_count }} staff now?')">@csrf
                    <div class="col-md-5"><label class="form-label small">Confirm with your password</label><input type="password" name="password" class="form-control" required autocomplete="current-password"></div>
                    <div class="col-md-4"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-send-plane-fill"></i>{{ $batch->provider === 'paystack' ? 'Send ' . $m($batch->total_amount) : 'Mark as sent to bank' }}</button></div>
                    <div class="col-md-3 small text-muted">{{ $batch->provider === 'paystack' ? 'Money leaves your Paystack balance.' : 'Then upload the bank file to your bank.' }}</div>
                </form>
            @endif
        </x-cb.card>
    @endif

    <form method="POST" action="{{ route('payroll.payouts.manual', $batch) }}" id="manualForm">@csrf
    <x-cb.card title="Transfers" icon="ri-list-check-2" :count="$items->count()" :flush="true">
        @if($canRelease && in_array($batch->status, ['processing', 'partial', 'failed']))
        <x-slot:tools>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" name="reference" class="form-control form-control-sm" placeholder="Bank / receipt reference" style="max-width:200px" required>
                <button class="action-btn btn-open" onclick="return confirm('Mark the ticked transfers as paid?')"><i class="ri-check-double-line"></i>Mark ticked as paid</button>
            </div>
        </x-slot:tools>
        @endif
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th style="width:36px"></th><th>Staff</th><th>Bank</th><th class="text-end">Amount</th><th>Status</th><th>Reference</th><th></th></tr></thead>
            <tbody>
            @foreach($items as $it)
                @php [$il, $ic] = $it->label(); @endphp
                <tr class="{{ in_array($it->status, ['failed', 'reversed']) ? 'table-danger' : '' }}">
                    <td>@if(!in_array($it->status, \App\Models\PayoutItem::FINAL) || $it->status === 'failed')<input type="checkbox" name="items[]" value="{{ $it->id }}" class="form-check-input">@endif</td>
                    <td>{{ $names[$it->staff_id] ?? ('Staff #' . $it->staff_id) }}@if($it->purpose !== 'salary')<div class="small text-muted">{{ ucfirst($it->purpose) }}</div>@endif</td>
                    <td class="small">{{ $it->bank_name }} ····{{ $it->account_last4 }}<div class="text-muted">{{ $it->account_name }}</div></td>
                    <td class="text-end fw-bold">{{ $m($it->amount) }}</td>
                    <td><span class="status-pill {{ $ic }}">{{ $il }}</span>@if($it->failure_reason)<div class="small text-danger">{{ $it->failure_reason }}</div>@endif @if($it->paid_at)<div class="small text-muted">{{ $it->paid_at->format('d M H:i') }}</div>@endif</td>
                    <td class="small text-muted">{{ $it->reference }}</td>
                    <td>
                        @if($it->status === 'otp' && $canRelease)
                            <div class="d-flex gap-1"><input form="otp{{ $it->id }}" name="otp" class="form-control form-control-sm" placeholder="OTP" style="width:80px" required><button form="otp{{ $it->id }}" class="action-btn btn-go">OK</button></div>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </x-cb.card>
    </form>
    @foreach($items->where('status', 'otp') as $it)
        <form method="POST" action="{{ route('payroll.payouts.otp', $it) }}" id="otp{{ $it->id }}">@csrf</form>
    @endforeach
</div>
</div>
</div>
@endsection
