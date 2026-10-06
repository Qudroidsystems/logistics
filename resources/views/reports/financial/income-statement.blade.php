@extends('layouts.master')

@section('content')
@include('components.report-styles')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1><i class="ri-bar-chart-line me-2"></i>Income Statement</h1>
                <p>{{ \Carbon\Carbon::parse($startDate)->format('d F, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F, Y') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ri-printer-line"></i> Print</button>
                <button class="btn btn-light btn-sm" onclick="exportToPDF()"><i class="ri-file-pdf-line"></i> PDF</button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold text-success"><i class="ri-money-dollar-circle-line me-2"></i>Income</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Account Name</th><th class="text-end">Amount (₦)</th></tr></thead>
                            <tbody id="incomeBody"><tr><td colspan="2" class="text-center py-4 text-muted">Loading...</td></tr></tbody>
                            <tfoot class="table-light"><tr><td class="fw-bold">Total Income</td><td class="text-end fw-bold text-success" id="totalIncome">₦0.00</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold text-danger"><i class="ri-expenses-line me-2"></i>Expenses</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Account Name</th><th class="text-end">Amount (₦)</th></tr></thead>
                            <tbody id="expensesBody"><tr><td colspan="2" class="text-center py-4 text-muted">Loading...</td></tr></tbody>
                            <tfoot class="table-light"><tr><td class="fw-bold">Total Expenses</td><td class="text-end fw-bold text-danger" id="totalExpenses">₦0.00</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm" id="profitCard" style="display:none;">
                <div class="card-body text-center py-4">
                    <h3 class="mb-0"><span id="profitLabel">Net Profit</span> <span id="profitAmount" class="text-success">₦0.00</span></h3>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
function loadIncomeStatement() {
    $.ajax({
        url: '{{ route("reports.financial.income-statement") }}',
        type: 'GET',
        data: { ajax: true, start_date: '{{ $startDate }}', end_date: '{{ $endDate }}' },
        success: function(response) {
            if (response.success && response.data) {
                const data = response.data;
                let incomeHtml = '';
                data.income.forEach(inc => { incomeHtml += `<tr><td>${inc.account_name}</td><td class="text-end">₦${Number(inc.amount || 0).toLocaleString()}</td></tr>`; });
                if (!incomeHtml) incomeHtml = '<tr><td colspan="2" class="text-center text-muted">No income recorded</td></tr>';
                $('#incomeBody').html(incomeHtml);
                $('#totalIncome').text('₦' + Number(data.total_income || 0).toLocaleString());

                let expensesHtml = '';
                data.expenses.forEach(exp => { expensesHtml += `<tr><td>${exp.account_name}</td><td class="text-end">₦${Number(exp.amount || 0).toLocaleString()}</td></tr>`; });
                if (!expensesHtml) expensesHtml = '<tr><td colspan="2" class="text-center text-muted">No expenses recorded</td></tr>';
                $('#expensesBody').html(expensesHtml);
                $('#totalExpenses').text('₦' + Number(data.total_expenses || 0).toLocaleString());

                const netProfit = data.net_profit || 0;
                const isProfit = netProfit >= 0;
                $('#profitLabel').text(isProfit ? 'Net Profit' : 'Net Loss');
                $('#profitAmount').text('₦' + Math.abs(netProfit).toLocaleString()).removeClass('text-success text-danger').addClass(isProfit ? 'text-success' : 'text-danger');
                $('#profitCard').show();
            }
        }
    });
}

function exportToPDF() {
    let url = new URL(window.location.href);
    url.searchParams.set('format', 'pdf');
    window.open(url.toString(), '_blank');
}

$(document).ready(function() { loadIncomeStatement(); });
</script>
@endsection
