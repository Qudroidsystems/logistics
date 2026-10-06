{{-- resources/views/payment/receipt.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.receipt-box {
    max-width: 600px;
    margin: auto;
    padding: 30px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
@media print {
    .no-print { display: none; }
    .receipt-box { box-shadow: none; padding: 0; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="text-end no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary"><i class="ri-printer-line me-1"></i>Print</button>
        <a href="{{ route('payment.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Back</a>
    </div>

    <div class="receipt-box">
        <div class="text-center mb-4">
            <h3>PAYMENT RECEIPT</h3>
            <p><strong>Receipt No:</strong> {{ $receiptData['receipt_no'] ?? $batch->batch_no }}</p>
            <p><strong>Date:</strong> {{ $batch->created_at->format('d F, Y H:i') }}</p>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <p><strong>Student Name:</strong> {{ $batch->student->firstname }} {{ $batch->student->lastname }}</p>
                <p><strong>Admission No:</strong> {{ $batch->student->admissionNo }}</p>
                <p><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $batch->payment_method)) }}</p>
                <p><strong>Reference:</strong> {{ $batch->reference_no ?? 'N/A' }}</p>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr><th>Item</th><th class="text-end">Amount (₦)</th></tr>
            </thead>
            <tbody>
                @foreach($batch->items as $item)
                <tr>
                    <td>{{ $item->schoolBill->title }} ({{ $item->adjusted_amount > 0 ? 'Adjusted Amount' : 'Original' }})</td>
                    <td class="text-end">{{ number_format($item->amount_paid, 2) }}</td>
                </tr>
                @endforeach
                <tr class="table-active"><th>Total Paid</th><th class="text-end">₦{{ number_format($batch->total_amount, 2) }}</th></tr>
            </tbody>
        </table>

        <div class="text-center mt-4">
            <p><strong>Thank you for your payment!</strong></p>
            <p class="text-muted small">This is a computer-generated receipt and requires no signature.</p>
        </div>
    </div>

</div>
</div>
</div>
@endsection
