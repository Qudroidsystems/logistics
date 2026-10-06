@extends('layouts.master')

@section('content')
@include('components.report-styles')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1><i class="ri-bank-line me-2"></i>Balance Sheet</h1>
                <p>As at {{ \Carbon\Carbon::parse($asAtDate)->format('d F, Y') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-light btn-sm" onclick="window.print()">
                    <i class="ri-printer-line"></i> Print
                </button>
                <button class="btn btn-light btn-sm" onclick="exportToPDF()">
                    <i class="ri-file-pdf-line"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold"><i class="ri-bank-line me-2 text-primary"></i>Assets</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Account Name</th><th class="text-end">Balance (₦)</th></tr>
                            </thead>
                            <tbody id="assetsTableBody">
                                <tr><td colspan="2" class="text-center py-4 text-muted">Loading...</td></tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr><td class="fw-bold">Total Assets</td>
                                    <td class="text-end fw-bold" id="totalAssets">₦0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold"><i class="ri-hand-coin-line me-2 text-success"></i>Liabilities & Equity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Account Name</th><th class="text-end">Balance (₦)</th></tr>
                            </thead>
                            <tbody>
                                <tr><td class="fw-semibold">Liabilities</td><td></td></tr>
                                <tr id="noLiabilities"><td colspan="2" class="text-center py-2 text-muted">No liabilities</td></tr>
                                <tr><td class="fw-semibold pt-3">Equity</td><td></td></tr>
                                <tr id="noEquity"><td colspan="2" class="text-center py-2 text-muted">No equity</td></tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr><td class="fw-bold">Total Liabilities & Equity</td>
                                    <td class="text-end fw-bold" id="totalLiabilitiesEquity">₦0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
function loadBalanceSheet() {
    $.ajax({
        url: '{{ route("reports.financial.balance-sheet") }}',
        type: 'GET',
        data: { ajax: true, as_at_date: '{{ $asAtDate }}' },
        success: function(response) {
            if (response.success && response.data) {
                const data = response.data;
                let assetsHtml = '';
                data.assets.forEach(asset => {
                    assetsHtml += `<tr><td>${asset.account_name}</td><td class="text-end">₦${Number(asset.balance || 0).toLocaleString()}</td></tr>`;
                });
                if (assetsHtml === '') assetsHtml = '<tr><td colspan="2" class="text-center text-muted">No assets recorded</td></tr>';
                $('#assetsTableBody').html(assetsHtml);
                $('#totalAssets').text('₦' + Number(data.total_assets || 0).toLocaleString());

                let liabilitiesHtml = '';
                data.liabilities.forEach(liability => {
                    liabilitiesHtml += `<tr><td>${liability.account_name}</td><td class="text-end">₦${Number(liability.balance || 0).toLocaleString()}</td></tr>`;
                });
                if (liabilitiesHtml) {
                    $('#noLiabilities').hide();
                    $('#noLiabilities').after(liabilitiesHtml);
                }

                let equityHtml = '';
                data.equity.forEach(eq => {
                    equityHtml += `<tr><td>${eq.account_name}</td><td class="text-end">₦${Number(eq.balance || 0).toLocaleString()}</td></tr>`;
                });
                if (equityHtml) {
                    $('#noEquity').hide();
                    $('#noEquity').after(equityHtml);
                }

                const total = (data.total_liabilities || 0) + (data.total_equity || 0);
                $('#totalLiabilitiesEquity').text('₦' + total.toLocaleString());
            }
        }
    });
}

function exportToPDF() {
    let url = new URL(window.location.href);
    url.searchParams.set('format', 'pdf');
    window.open(url.toString(), '_blank');
}

$(document).ready(function() {
    loadBalanceSheet();
});
</script>
@endsection
