@extends('layouts.master')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" rel="preload" as="script">
@include('components.report-styles')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero">
        <div><h1><i class="ri-gift-line me-2"></i>Scholarship & Discount Impact Report</h1><p>Analysis of scholarship and discount impact on school revenue</p></div>
    </div>

    <div class="row g-3 mb-4" id="statsRow" style="display:none;">
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-primary" id="totalScholarships">0</div><div class="stat-label">Total Scholarships</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-success" id="totalDiscounts">0</div><div class="stat-label">Total Discounts</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-info" id="totalBeneficiaries">0</div><div class="stat-label">Beneficiaries</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-warning" id="totalSavings">₦0</div><div class="stat-label">Total Savings</div></div></div>
    </div>

    <div class="row g-3" id="chartsRow" style="display:none;">
        <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-header bg-white py-3 border-bottom"><h5 class="mb-0 fw-semibold"><i class="ri-award-line me-2"></i>Scholarship Distribution</h5></div><div class="card-body"><canvas id="scholarshipChart" height="250"></canvas></div></div></div>
        <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-header bg-white py-3 border-bottom"><h5 class="mb-0 fw-semibold"><i class="ri-price-tag-3-line me-2"></i>Discount Distribution</h5></div><div class="card-body"><canvas id="discountChart" height="250"></canvas></div></div></div>
    </div>

    <div class="card border-0 shadow-sm mt-3" id="tableCard" style="display:none;">
        <div class="card-header bg-white py-3 border-bottom"><h5 class="mb-0 fw-semibold"><i class="ri-table-line me-2"></i>Impact by Class</h5></div>
        <div class="card-body"><div class="table-responsive"><table class="table table-hover w-100" id="impactTable"><thead class="table-light"><tr><th>Class</th><th>Scholarship Students</th><th class="text-end">Scholarship Value (₦)</th><th>Discount Students</th><th class="text-end">Discount Value (₦)</th><th class="text-end">Total Savings (₦)</th></tr></thead><tbody><tr><td colspan="6" class="text-center py-4 text-muted">Loading...</td></tr></tbody></table></div></div>
    </div>

</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let scholarshipChart, discountChart;

function loadImpactData() {
    $.ajax({ url: '{{ route("reports.financial.scholarship-impact") }}', type: 'GET', data: { ajax: true }, success: function(response) {
        if (response.success) { updateStats(response.data); updateCharts(response.data); updateTable(response.data); $('#statsRow, #chartsRow, #tableCard').show(); }
    }});
}

function updateStats(d) { $('#totalScholarships').text(d.total_scholarships||0); $('#totalDiscounts').text(d.total_discounts||0); $('#totalBeneficiaries').text(d.total_beneficiaries||0); $('#totalSavings').text('₦'+(d.total_savings||0).toLocaleString()); }
function updateCharts(d) {
    if(scholarshipChart) scholarshipChart.destroy(); if(discountChart) discountChart.destroy();
    scholarshipChart = new Chart($('#scholarshipChart'), { type: 'pie', data: { labels: Object.keys(d.scholarship_by_type||{}), datasets: [{ data: Object.values(d.scholarship_by_type||{}), backgroundColor: ['#2563eb','#16a34a','#d97706','#dc2626','#8b5cf6'] }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });
    discountChart = new Chart($('#discountChart'), { type: 'pie', data: { labels: Object.keys(d.discount_by_type||{}), datasets: [{ data: Object.values(d.discount_by_type||{}), backgroundColor: ['#10b981','#f59e0b','#ef4444','#06b6d4'] }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });
}
function updateTable(d) { let html=''; if(d.impact_by_class && d.impact_by_class.length) { d.impact_by_class.forEach(item=>{ html+=`<tr><td>${item.class}</td><td>${item.scholarship_students||0}</td><td class="text-end">₦${(item.scholarship_value||0).toLocaleString()}</td><td>${item.discount_students||0}</td><td class="text-end">₦${(item.discount_value||0).toLocaleString()}</td><td class="text-end text-success">₦${((item.scholarship_value||0)+(item.discount_value||0)).toLocaleString()}</td></tr>`; }); } else { html='<tr><td colspan="6" class="text-center text-muted">No impact data available</td></tr>'; } $('#impactTable tbody').html(html); }
$(document).ready(function() { loadImpactData(); });
</script>
@endsection
