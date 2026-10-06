@extends('layouts.master')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" rel="preload" as="script">
<style>
.report-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: 12px;
    padding: 28px 32px;
    margin-bottom: 24px;
}
.report-hero h1 { font-size: 22px; font-weight: 700; color: #fff; margin: 0; }
.report-hero p { font-size: 13px; color: rgba(255,255,255,.75); margin: 5px 0 0; }
.stat-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.stat-value { font-size: 28px; font-weight: 700; }
.filter-bar {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero">
        <h1><i class="ri-gift-line me-2"></i>Scholarship & Discount Impact Analysis</h1>
        <p>Analysis of scholarship and discount impact on school revenue</p>
    </div>

    <div class="row g-3 mb-4" id="statsRow" style="display: none;">
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-primary" id="totalScholarships">0</div><div class="stat-label">Scholarships</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-success" id="totalDiscounts">0</div><div class="stat-label">Discounts</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-info" id="totalBeneficiaries">0</div><div class="stat-label">Beneficiaries</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-warning" id="totalSavings">₦0</div><div class="stat-label">Total Savings</div></div></div>
    </div>

    <div class="filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-md-5"><label>Term</label><select class="form-select" id="term_id"><option value="">All Terms</option>@foreach($terms as $term)<option value="{{ $term->id }}">{{ $term->term }}</option>@endforeach</select></div>
            <div class="col-md-5"><label>Session</label><select class="form-select" id="session_id"><option value="">All Sessions</option>@foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->session }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100" id="loadReportBtn">Load</button></div>
        </div>
    </div>

    <div class="row g-3" id="chartsRow" style="display: none;">
        <div class="col-md-6"><div class="card"><div class="card-header"><h5>Scholarship Distribution</h5></div><div class="card-body"><canvas id="scholarshipChart" height="250"></canvas></div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-header"><h5>Discount Distribution</h5></div><div class="card-body"><canvas id="discountChart" height="250"></canvas></div></div></div>
    </div>

</div>
</div>
</div>

<script>
let scholarshipChart, discountChart;

$('#loadReportBtn').on('click', function() {
    $.ajax({
        url: '{{ route("reports.analysis.scholarship-impact") }}',
        type: 'GET',
        data: { term_id: $('#term_id').val(), session_id: $('#session_id').val(), ajax: true },
        success: function(r) {
            if (r.success) {
                $('#totalScholarships').text(r.data.total_scholarships || 0);
                $('#totalDiscounts').text(r.data.total_discounts || 0);
                $('#totalBeneficiaries').text(r.data.total_beneficiaries || 0);
                $('#totalSavings').text('₦' + (r.data.total_savings || 0).toLocaleString());
                if (scholarshipChart) scholarshipChart.destroy();
                if (discountChart) discountChart.destroy();
                scholarshipChart = new Chart($('#scholarshipChart'), { type: 'pie', data: { labels: Object.keys(r.data.scholarship_by_type || {}), datasets: [{ data: Object.values(r.data.scholarship_by_type || {}), backgroundColor: ['#2563eb','#16a34a','#d97706','#dc2626'] }] } });
                discountChart = new Chart($('#discountChart'), { type: 'pie', data: { labels: Object.keys(r.data.discount_by_type || {}), datasets: [{ data: Object.values(r.data.discount_by_type || {}), backgroundColor: ['#10b981','#f59e0b','#ef4444','#06b6d4'] }] } });
                $('#statsRow, #chartsRow').show();
            }
        }
    });
});
</script>
@endsection
