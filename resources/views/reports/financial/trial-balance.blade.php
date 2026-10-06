@extends('layouts.master')

@section('content')
@include('components.report-styles')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1><i class="ri-table-line me-2"></i>Trial Balance</h1>
                <p>As at {{ \Carbon\Carbon::parse($asAtDate)->format('d F, Y') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ri-printer-line"></i> Print</button>
                <button class="btn btn-light btn-sm" onclick="exportToExcel()"><i class="ri-file-excel-line"></i> Excel</button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="ri-table-line me-2"></i>Trial Balance</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="trialBalanceTable">
                    <thead class="table-light">
                        <tr><th>Account Code</th><th>Account Name</th><th>Account Type</th><th class="text-end">Debit (₦)</th><th class="text-end">Credit (₦)</th><th class="text-end">Balance (₦)</th></tr>
                    </thead>
                    <tbody id="tbBody"><tr><td colspan="6" class="text-center py-4 text-muted">Loading...</td></tr></tbody>
                    <tfoot class="table-light"><tr><td colspan="3" class="fw-bold">TOTALS</td><td class="text-end fw-bold" id="totalDebit">₦0.00</td><td class="text-end fw-bold" id="totalCredit">₦0.00</td><td id="balanceDiff"></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
function loadTrialBalance() {
    $.ajax({
        url: '{{ route("reports.financial.trial-balance") }}',
        type: 'GET',
        data: { ajax: true, as_at_date: '{{ $asAtDate }}' },
        success: function(response) {
            if (response.success && response.data) {
                const data = response.data;
                let html = '';
                data.trialBalance.forEach(item => {
                    html += `<tr>
                        <td>${item.account_code}</td>
                        <td>${item.account_name}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary">${item.account_type}</span></td>
                        <td class="text-end">${item.debit > 0 ? '₦' + Number(item.debit).toLocaleString() : '—'}</td>
                        <td class="text-end">${item.credit > 0 ? '₦' + Number(item.credit).toLocaleString() : '—'}</td>
                        <td class="text-end fw-semibold ${item.balance > 0 ? 'text-primary' : (item.balance < 0 ? 'text-danger' : '')}">₦${Math.abs(item.balance).toLocaleString()}</td>
                    </tr>`;
                });
                if (!html) html = '<tr><td colspan="6" class="text-center text-muted">No trial balance data</td></tr>';
                $('#tbBody').html(html);
                $('#totalDebit').text('₦' + Number(data.totalDebit || 0).toLocaleString());
                $('#totalCredit').text('₦' + Number(data.totalCredit || 0).toLocaleString());
                const diff = (data.totalDebit || 0) - (data.totalCredit || 0);
                if (Math.abs(diff) > 0.01) $('#balanceDiff').html(`<span class="text-danger ms-3">Difference: ₦${Math.abs(diff).toLocaleString()}</span>`);
                else $('#balanceDiff').html('<span class="text-success ms-3">✓ Balanced</span>');
            }
        }
    });
}

function exportToExcel() {
    let url = new URL(window.location.href);
    url.searchParams.set('format', 'excel');
    window.open(url.toString(), '_blank');
}

$(document).ready(function() { loadTrialBalance(); });
</script>
@endsection
