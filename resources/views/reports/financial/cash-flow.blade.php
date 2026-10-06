@extends('layouts.master')

@section('content')
@include('components.report-styles')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1><i class="ri-bar-chart-2-line me-2"></i>Cash Flow Statement</h1>
                <p>{{ \Carbon\Carbon::parse($startDate)->format('d F, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F, Y') }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ri-printer-line"></i> Print</button>
                <button class="btn btn-light btn-sm" onclick="exportToPDF()"><i class="ri-file-pdf-line"></i> PDF</button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="ri-bar-chart-2-line me-2"></i>Cash Flow Analysis</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <tbody>
                        <tr class="table-light"><td colspan="2" class="fw-semibold">Operating Activities</td></tr>
                        <tr><td class="ps-4">Net Cash from Operations</td><td class="text-end fw-semibold" id="operatingCash">₦0.00</td></tr>
                        <tr class="table-light"><td colspan="2" class="fw-semibold">Investing Activities</td></tr>
                        <tr><td class="ps-4">Net Cash from Investing</td><td class="text-end fw-semibold" id="investingCash">₦0.00</td></tr>
                        <tr class="table-light"><td colspan="2" class="fw-semibold">Financing Activities</td></tr>
                        <tr><td class="ps-4">Net Cash from Financing</td><td class="text-end fw-semibold" id="financingCash">₦0.00</td></tr>
                        <tr class="table-secondary"><td class="fw-bold">Net Cash Flow</td><td class="text-end fw-bold fs-5" id="netCashFlow">₦0.00</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
function loadCashFlow() {
    $.ajax({
        url: '{{ route("reports.financial.cash-flow") }}',
        type: 'GET',
        data: { ajax: true, start_date: '{{ $startDate }}', end_date: '{{ $endDate }}' },
        success: function(response) {
            if (response.success && response.data) {
                const data = response.data;
                const format = n => '₦' + Math.abs(n || 0).toLocaleString();
                $('#operatingCash').html((data.operating_activities >= 0 ? '' : '-') + format(data.operating_activities));
                $('#investingCash').html((data.investing_activities >= 0 ? '' : '-') + format(data.investing_activities));
                $('#financingCash').html((data.financing_activities >= 0 ? '' : '-') + format(data.financing_activities));
                const net = data.net_cash_flow || 0;
                $('#netCashFlow').html((net >= 0 ? '+' : '-') + format(net)).removeClass('text-success text-danger').addClass(net >= 0 ? 'text-success' : 'text-danger');
            }
        }
    });
}

function exportToPDF() {
    let url = new URL(window.location.href);
    url.searchParams.set('format', 'pdf');
    window.open(url.toString(), '_blank');
}

$(document).ready(function() { loadCashFlow(); });
</script>
@endsection
