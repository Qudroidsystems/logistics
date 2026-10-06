{{-- resources/views/payment/online-success.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm text-center p-4">
                <div class="mb-4">
                    <i class="ri-checkbox-circle-fill text-success" style="font-size: 64px;"></i>
                </div>
                <h2 class="mb-3">Payment Successful!</h2>
                <p class="text-muted mb-4">Your payment has been processed successfully.</p>

                <div class="alert alert-info">
                    <strong>Transaction Reference:</strong> {{ $payment->reference }}
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3">
                            <small class="text-muted">Amount Paid</small>
                            <h4 class="mb-0 text-success">₦{{ number_format($payment->amount, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3">
                            <small class="text-muted">Payment Date</small>
                            <h4 class="mb-0">{{ $payment->payment_date ? $payment->payment_date->format('d M, Y') : now()->format('d M, Y') }}</h4>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('payment.receipt', $batch->id) }}" class="btn btn-primary">
                        <i class="ri-file-pdf-line me-2"></i>Download Receipt
                    </a>
                    <a href="{{ route('payment.index') }}" class="btn btn-light ms-2">
                        <i class="ri-home-line me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>
@endsection
