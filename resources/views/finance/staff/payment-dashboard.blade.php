{{-- resources/views/finance/staff/payment-dashboard.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.dashboard-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s;
}
.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
.stat-value {
    font-size: 28px;
    font-weight: 700;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold" style="color: #1e3a5f;">
                        <i class="ri-briefcase-line me-2"></i>{{ $pagetitle }}
                    </h4>
                    <p class="text-muted">Welcome back, {{ $staff->user->name ?? 'Staff' }}</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="text-success mb-2"><i class="ri-money-dollar-circle-line fs-1"></i></div>
                <div class="stat-value text-success">₦{{ number_format($stats['total_paid'], 2) }}</div>
                <div class="text-muted">Total Payments Received</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="text-warning mb-2"><i class="ri-time-line fs-1"></i></div>
                <div class="stat-value text-warning">₦{{ number_format($stats['total_pending'], 2) }}</div>
                <div class="text-muted">Pending Payments</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card text-center">
                <div class="text-primary mb-2"><i class="ri-file-copy-line fs-1"></i></div>
                <div class="stat-value text-primary">{{ $stats['payment_count'] }}</div>
                <div class="text-muted">Total Transactions</div>
            </div>
        </div>
    </div>

    {{-- Active Loans Section --}}
    @if($loanSummary['total_deduction'] > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold"><i class="ri-bank-line me-2"></i>Active Loan Deductions</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Monthly Deduction:</strong> ₦{{ number_format($loanSummary['total_deduction'], 2) }}
            </div>
            @if(!empty($loanSummary['loans']))
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Loan Reference</th><th>Original Amount</th><th>Balance</th><th>Monthly Payment</th><th>Remaining Months</th></tr>
                    </thead>
                    <tbody>
                        @foreach($loanSummary['loans'] as $loan)
                        <tr>
                            <td>{{ $loan['reference_no'] }}</td>
                            <td>₦{{ number_format($loan['original_amount'], 2) }}</td>
                            <td>₦{{ number_format($loan['balance'], 2) }}</td>
                            <td>₦{{ number_format($loan['monthly_repayment'], 2) }}</td>
                            <td>{{ $loan['remaining_months'] }} months</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Payment History Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold"><i class="ri-history-line me-2"></i>Recent Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="paymentsTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $payment->payment_reference }}</td>
                            <td class="text-success">₦{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_date->format('d M, Y') }}</td>
                            <td><span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</span></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td>
                                @if($payment->payment_status == 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($payment->payment_status == 'processed')
                                    <span class="badge bg-info">Processed</span>
                                @elseif($payment->payment_status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($payment->payment_status) }}</span>
                                @endif
                            </td>
                            <td>{{ Str::limit($payment->purpose, 50) }}</td>
                        </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No payment records found.</td></td>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $payments->links() }}
        </div>
    </div>

    {{-- Payroll History Section --}}
    @if($payrollHistory->isNotEmpty())
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold"><i class="ri-file-pdf-line me-2"></i>Payslip History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Gross Pay</th>
                            <th>Net Pay</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrollHistory as $run)
                        <tr>
                            <td>{{ $run->payrollPeriod->period_name }}</td>
                            <td>₦{{ number_format($run->total_earnings, 2) }}</td>
                            <td class="text-success">₦{{ number_format($run->net_pay, 2) }}</td>
                            <td>{{ $run->paid_at ? $run->paid_at->format('d M, Y') : 'N/A' }}</td>
                            <td><span class="badge bg-success">Paid</span></td>
                            <td>
                                <a href="{{ route('staff.payments.payslip', $run->id) }}" class="btn btn-sm btn-info" target="_blank">
                                    <i class="ri-file-pdf-line me-1"></i>View
                                </a>
                                <a href="{{ route('staff.payments.payslip.download', $run->id) }}" class="btn btn-sm btn-success">
                                    <i class="ri-download-line me-1"></i>Download
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
</div>
</div>
@endsection
