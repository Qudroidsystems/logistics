{{-- resources/views/reports/school-wide-analysis.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --report-primary: #1e3a5f;
    --report-accent: #2563eb;
    --report-success: #16a34a;
    --report-warning: #d97706;
    --report-danger: #dc2626;
}

.dashboard-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    transition: all 0.2s;
}
.dashboard-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,.1); }
.kpi-value { font-size: 32px; font-weight: 800; }
.kpi-label { font-size: 13px; color: #6b7280; margin-top: 4px; }
.chart-container { height: 300px; position: relative; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero" style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 24px;">
        <h1 class="text-white"><i class="ri-pie-chart-line me-2"></i>{{ $pagetitle }}</h1>
        <p class="text-white-50 mb-0">Comprehensive financial overview across all classes, terms, and sessions.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-value text-primary" id="totalRevenue">₦0</div>
                        <div class="kpi-label">Total Revenue</div>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                        <i class="ri-money-dollar-circle-line fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-value text-success" id="totalCollected">₦0</div>
                        <div class="kpi-label">Amount Collected</div>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                        <i class="ri-wallet-line fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-value text-warning" id="collectionRate">0%</div>
                        <div class="kpi-label">Collection Rate</div>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                        <i class="ri-percent-line fs-2 text-warning"></i>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 6px;">
                    <div class="progress-bar bg-success" id="collectionProgress" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="dashboard-card">
                <h5 class="fw-semibold mb-3"><i class="ri-bar-chart-line me-2"></i>Monthly Collection Trend</h5>
                <canvas id="monthlyChart" class="chart-container"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card">
                <h5 class="fw-semibold mb-3"><i class="ri-pie-chart-line me-2"></i>Payment Method Distribution</h5>
                <canvas id="paymentMethodChart" class="chart-container"></canvas>
            </div>
        </div>
        <div class="col-md-12 mt-3">
            <div class="dashboard-card">
                <h5 class="fw-semibold mb-3"><i class="ri-building-line me-2"></i>Class Performance</h5>
                <div class="table-responsive">
                    <table class="table table-hover" id="classPerformanceTable">
                        <thead>
                            <tr><th>Class</th><th>Students</th><th>Expected (₦)</th><th>Collected (₦)</th><th>Outstanding (₦)</th><th>Rate</th></tr>
                        </thead>
                        <tbody id="classPerformanceBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
let monthlyChart, paymentMethodChart;

$(document).ready(function() {
    loadSchoolWideData();
});

function loadSchoolWideData() {
    $.ajax({
        url: '{{ route("reports.analysis.school-wide") }}',
        type: 'GET',
        data: { ajax: true },
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#totalRevenue').text('₦' + data.total_revenue?.toLocaleString() || '₦0');
                $('#totalCollected').text('₦' + data.total_collected?.toLocaleString() || '₦0');
                $('#collectionRate').text(data.collection_rate + '%');
                $('#collectionProgress').css('width', data.collection_rate + '%');

                if (data.monthly_trend) updateMonthlyChart(data.monthly_trend);
                if (data.payment_methods) updatePaymentMethodChart(data.payment_methods);
                if (data.class_performance) updateClassTable(data.class_performance);
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to load school-wide data', 'error');
        }
    });
}

function updateMonthlyChart(data) {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    if (monthlyChart) monthlyChart.destroy();
    monthlyChart = new Chart(ctx, {
        type: 'line',
        data: { labels: data.labels, datasets: [{ label: 'Amount Collected (₦)', data: data.values, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.1)', fill: true, tension: 0.4 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
}

function updatePaymentMethodChart(data) {
    const ctx = document.getElementById('paymentMethodChart').getContext('2d');
    if (paymentMethodChart) paymentMethodChart.destroy();
    paymentMethodChart = new Chart(ctx, {
        type: 'doughnut',
        data: { labels: data.labels, datasets: [{ data: data.values, backgroundColor: ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#8b5cf6'] }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
}

function updateClassTable(classes) {
    const tbody = $('#classPerformanceBody');
    tbody.empty();
    classes.forEach(c => {
        tbody.append(`<tr><td>${c.class}</td><td>${c.students}</td><td>${c.expected?.toLocaleString()}</td><td>${c.collected?.toLocaleString()}</td><td class="text-danger">${c.outstanding?.toLocaleString()}</td><td><div class="progress" style="height:6px;width:80px"><div class="progress-bar bg-success" style="width:${c.rate}%"></div></div><small>${c.rate}%</small></td></tr>`);
    });
}
</script>
@endsection
