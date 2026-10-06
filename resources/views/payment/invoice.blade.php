{{-- resources/views/payment/invoice.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.invoice-box {
    max-width: 800px;
    margin: auto;
    padding: 30px;
    border: 1px solid #eee;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
    background: white;
}
@media print {
    .no-print { display: none; }
    .invoice-box { box-shadow: none; padding: 0; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="text-end no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary"><i class="ri-printer-line me-1"></i>Print</button>
        <a href="{{ url()->previous() }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Back</a>
    </div>

    <div class="invoice-box">
        <div class="text-center mb-4">
            @if($schoolInfo->logo_url)
                <img src="{{ $schoolInfo->logo_url }}" alt="Logo" height="60">
            @endif
            <h3>{{ $schoolInfo->school_name ?? 'School Name' }}</h3>
            <p>{{ $schoolInfo->school_address ?? 'School Address' }}</p>
            <h4>TAX INVOICE</h4>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <strong>Invoice No:</strong> {{ $invoiceNumber }}<br>
                <strong>Date:</strong> {{ date('d F, Y') }}<br>
                <strong>Due Date:</strong> {{ date('d F, Y', strtotime('+7 days')) }}
            </div>
            <div class="col-6 text-end">
                <strong>Student:</strong> {{ $student->firstname }} {{ $student->lastname }}<br>
                <strong>Admission No:</strong> {{ $student->admissionNo }}<br>
                <strong>Class:</strong> {{ $student->schoolclass }} {{ $student->arm }}
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr><th>#</th><th>Description</th><th>Amount (₦)</th></tr>
            </thead>
            <tbody>
                @foreach($paidBills as $index => $bill)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $bill['title'] }}</td>
                    <td class="text-end">{{ number_format($bill['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-active"><th colspan="2" class="text-end">Total Paid:</th><td class="text-end">₦{{ number_format($summary['total_paid'], 2) }}</td></tr>
                <tr><th colspan="2" class="text-end">Total Savings:</th><td class="text-end text-success">₦{{ number_format($summary['total_savings'], 2) }}</td></tr>
                <tr><th colspan="2" class="text-end">Outstanding:</th><td class="text-end text-danger">₦{{ number_format($summary['total_outstanding'], 2) }}</td></tr>
            </tfoot>
        </table>

        <div class="mt-4">
            <p><strong>Amount in words:</strong> {{ \App\Helpers\NumberToWords::convert($summary['total_paid']) }} Naira Only</p>
            <p><strong>Thank you for your payment!</strong></p>
        </div>

        <div class="row mt-5">
            <div class="col-6 text-center">
                <hr><small>Student/Guardian Signature</small>
            </div>
            <div class="col-6 text-center">
                <hr><small>Authorized Signatory</small>
            </div>
        </div>
    </div>

</div>
</div>
</div>
@endsection
