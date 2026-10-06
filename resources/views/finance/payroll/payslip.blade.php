{{-- resources/views/finance/payroll/payslip.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.payslip-box {
    max-width: 800px;
    margin: auto;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 30px;
}
@media print { .no-print { display: none; } .payslip-box { box-shadow: none; padding: 0; } }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="text-end no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary"><i class="ri-printer-line me-1"></i>Print</button>
        <a href="{{ route('payroll.periods') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Back</a>
    </div>

    <div class="payslip-box">
        <div class="text-center mb-4">
            <h3>PAYSLIP</h3>
            <p>{{ $payrollRun->payrollPeriod->period_name }} | {{ $payrollRun->payrollPeriod->year }}</p>
        </div>

        <div class="row mb-4">
            <div class="col-6"><strong>Staff Name:</strong> {{ $payrollRun->staff->user->name ?? 'N/A' }}</div>
            <div class="col-6"><strong>Staff ID:</strong> {{ $payrollRun->staff->employmentid ?? 'N/A' }}</div>
            <div class="col-6"><strong>Bank:</strong> {{ $payrollRun->bank_name ?? $payrollRun->staff->bank_name ?? 'N/A' }}</div>
            <div class="col-6"><strong>Account:</strong> {{ $payrollRun->account_number ?? $payrollRun->staff->account_number ?? 'N/A' }}</div>
        </div>

        <div class="row">
            <div class="col-6">
                <h5>Earnings</h5>
                <table class="table table-sm">
                    @foreach($earnings as $earning)
                    <tr><td>{{ $earning['name'] }}</td><td class="text-end">₦{{ number_format($earning['amount'], 2) }}</td></tr>
                    @endforeach
                    <tr class="table-active"><th>Total Earnings</th><th class="text-end">₦{{ number_format($payrollRun->total_earnings, 2) }}</th></tr>
                </table>
            </div>
            <div class="col-6">
                <h5>Deductions</h5>
                <table class="table table-sm">
                    @foreach($deductions as $deduction)
                    <tr><td>{{ $deduction['name'] }}</td><td class="text-end">₦{{ number_format($deduction['amount'], 2) }}</td></tr>
                    @endforeach
                    <tr class="table-active"><th>Total Deductions</th><th class="text-end">₦{{ number_format($payrollRun->total_deductions, 2) }}</th></tr>
                </table>
            </div>
        </div>

        <div class="alert alert-success text-center mt-3">
            <h4>NET PAY: ₦{{ number_format($payrollRun->net_pay, 2) }}</h4>
        </div>

        <div class="text-center mt-3"><small>This is a computer-generated payslip. No signature required.</small></div>
    </div>
</div>
</div>
</div>
@endsection
