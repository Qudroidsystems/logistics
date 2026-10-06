{{-- resources/views/finance/staff/payments-show.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold" style="color: #1e3a5f;">
                        <i class="ri-eye-line me-2"></i>Payment Details
                    </h4>
                    <p class="text-muted">Reference: {{ $payment->payment_reference }}</p>
                </div>
                <div>
                    <a href="{{ route('staff.payments.index') }}" class="btn btn-light">
                        <i class="ri-arrow-left-line me-1"></i>Back to List
                    </a>
                    @if($payment->payment_status !== 'paid' && $payment->payment_status !== 'reversed')
                    <a href="{{ route('staff.payments.edit', $payment->id) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i>Edit
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr><th width="40%">Staff Name:</th><td>{{ $payment->staff->user->name ?? 'N/A' }}</td></tr>
                        <tr><th>Staff ID:</th><td>{{ $payment->staff->employmentid ?? 'N/A' }}</td></tr>
                        <tr><th>Department:</th><td>{{ $payment->staff->department ?? 'N/A' }}</td></tr>
                        <tr><th>Payment Reference:</th><td><code>{{ $payment->payment_reference }}</code></td></tr>
                        <tr><th>Payment Type:</th><td>{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</td></tr>
                        <tr><th>Payment Method:</th><td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
                    <tr>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr><th width="40%">Amount:</th><td class="text-success fw-bold">₦{{ number_format($payment->amount, 2) }}</td></tr>
                        <tr><th>Payment Date:</th><td>{{ $payment->payment_date->format('d F, Y') }}</td></tr>
                        <tr><th>Status:</th><td>
                            @if($payment->payment_status == 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($payment->payment_status == 'processed')
                                <span class="badge bg-info">Processed</span>
                            @elseif($payment->payment_status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($payment->payment_status == 'reversed')
                                <span class="badge bg-secondary">Reversed</span>
                            @else
                                <span class="badge bg-danger">{{ ucfirst($payment->payment_status) }}</span>
                            @endif
                        </td></tr>
                        @if($payment->bank_name)
                        <tr><th>Bank:</th><td>{{ $payment->bank_name }}</td></tr>
                        <tr><th>Account:</th><td>{{ $payment->account_number }}</td></tr>
                        @endif
                        @if($payment->transaction_ref)
                        <tr><th>Transaction Ref:</th><td>{{ $payment->transaction_ref }}</td></tr>
                        @endif
                        <tr><th>Created By:</th><td>{{ $payment->createdBy->name ?? 'System' }}</td></tr>
                        <tr><th>Created At:</th><td>{{ $payment->created_at->format('d F, Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="border-top pt-3">
                        <h6>Purpose</h6>
                        <p>{{ $payment->purpose }}</p>
                    </div>
                    @if($payment->notes)
                    <div class="border-top pt-3 mt-2">
                        <h6>Notes</h6>
                        <p>{{ $payment->notes }}</p>
                    </div>
                    @endif
                    @if($payment->reversal_reason)
                    <div class="border-top pt-3 mt-2">
                        <h6 class="text-danger">Reversal Reason</h6>
                        <p class="text-danger">{{ $payment->reversal_reason }}</p>
                        <small>Reversed by: {{ $payment->reversedBy->name ?? 'Unknown' }} on {{ $payment->reversed_at ? $payment->reversed_at->format('d F, Y H:i') : 'N/A' }}</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
