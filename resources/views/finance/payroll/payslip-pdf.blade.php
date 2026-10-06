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
@media print {
    .no-print { display: none; }
    .payslip-box { box-shadow: none; padding: 0; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="text-end no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary"><i class="ri-printer-line me-1"></i>Print</button>
        <a href="{{ url()->previous() }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Back</a>
        <a href="{{ route('payroll.run.show', $payrollRun->id) }}?download=1" class="btn btn-success"><i class="ri-download-line me-1"></i>Download PDF</a>
    </div>

    <div class="payslip-box">
        <div class="text-center mb-4">
            @if($schoolInfo && $schoolInfo->logo_url)
                <img src="{{ $schoolInfo->logo_url }}" alt="Logo" height="60">
            @endif
            <h3 class="mt-2">PAYSLIP</h3>
            <p>{{ $payrollRun->payrollPeriod->period_name }} | {{ $payrollRun->payrollPeriod->year }}</p>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><th width="40%">Staff Name:</th><td>{{ $payrollRun->staff->user->name ?? 'N/A' }}</td></tr>
                    <tr><th>Staff ID:</th><td>{{ $payrollRun->staff->employmentid ?? 'N/A' }}</td></tr>
                    <tr><th>Department:</th><td>{{ $payrollRun->staff->department ?? 'N/A' }}</td></tr>
                    <tr><th>Position:</th><td>{{ $payrollRun->staff->position ?? 'N/A' }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><th width="40%">Bank:</th><td>{{ $payrollRun->bank_name ?? $payrollRun->staff->bank_name ?? 'N/A' }}</td></tr>
                    <tr><th>Account No:</th><td>{{ $payrollRun->account_number ?? $payrollRun->staff->account_number ?? 'N/A' }}</td></tr>
                    <tr><th>Payment Date:</th><td>{{ $payrollRun->payrollPeriod->payment_date->format('d M, Y') }}</td></tr>
                    <tr><th>Status:</th><td><span class="badge bg-{{ $payrollRun->payment_status == 'paid' ? 'success' : 'warning' }}">{{ ucfirst($payrollRun->payment_status) }}</span></td></tr>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h5 class="border-bottom pb-2">Earnings</h5>
                <table class="table table-sm">
                    @foreach($earnings as $earning)
                    <tr>
                        <td>{{ $earning['name'] }}</td>
                        <td class="text-end">₦{{ number_format($earning['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="table-active">
                        <th>Total Earnings</th>
                        <th class="text-end">₦{{ number_format($payrollRun->total_earnings, 2) }}</th>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5 class="border-bottom pb-2">Deductions</h5>
                <table class="table table-sm">
                    @foreach($deductions as $deduction)
                    <tr>
                        <td>{{ $deduction['name'] }}</td>
                        <td class="text-end">₦{{ number_format($deduction['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="table-active">
                        <th>Total Deductions</th>
                        <th class="text-end">₦{{ number_format($payrollRun->total_deductions, 2) }}</th>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <div class="alert alert-success text-center">
                    <h4 class="mb-0">NET PAY: ₦{{ number_format($payrollRun->net_pay, 2) }}</h4>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <h5 class="border-bottom pb-2">Employer Contributions</h5>
                <table class="table table-sm">
                    @foreach($employerContributions as $contribution)
                    <tr>
                        <td>{{ $contribution['name'] }}</td>
                        <td class="text-end">₦{{ number_format($contribution['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
            <div class="col-md-6 text-center">
                <div class="mt-4">
                    <hr>
                    <small>Authorized Signature</small>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">This is a computer-generated payslip. No signature required.</small>
        </div>
    </div>
</div>
</div>
</div>
@endsection
