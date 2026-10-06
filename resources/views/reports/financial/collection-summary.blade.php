@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@include('components.report-styles')

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="report-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div><h1><i class="ri-funds-line me-2"></i>Collection Summary</h1><p>School fee collection performance analysis</p></div>
            <div><button class="btn btn-light btn-sm" onclick="window.print()"><i class="ri-printer-line"></i> Print</button></div>
        </div>
    </div>

    <div class="row g-3 mb-4" id="statsRow" style="display:none;">
        <div class="col-md-4"><div class="stat-card"><div class="stat-value" id="totalStudents">0</div><div class="stat-label">Total Students</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-value text-success" id="totalCollected">₦0</div><div class="stat-label">Total Collected</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-value text-warning" id="totalRate">0%</div><div class="stat-label">Collection Rate</div></div></div>
    </div>

    <div class="filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-md-4"><label class="filter-label">Class</label><select class="form-select" id="classFilter"><option value="">All Classes</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->display_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="filter-label">Term</label><select class="form-select" id="termFilter"><option value="">All Terms</option>@foreach($terms as $term)<option value="{{ $term->id }}">{{ $term->term }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="filter-label">Session</label><select class="form-select" id="sessionFilter"><option value="">All Sessions</option>@foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->session }}</option>@endforeach</select></div>
        </div>
        <div class="row mt-3"><div class="col-12"><button class="btn btn-primary" id="loadReportBtn"><i class="ri-search-line me-1"></i>Load Report</button></div></div>
    </div>

    <div class="card border-0 shadow-sm" id="tableCard" style="display:none;">
        <div class="card-header bg-white py-3 border-bottom"><h5 class="mb-0 fw-semibold"><i class="ri-table-line me-2"></i>Collection by Class</h5></div>
        <div class="card-body"><div class="table-responsive"><table class="table report-table w-100" id="collectionTable"><thead><tr><th>Class</th><th>Term</th><th>Session</th><th>Students</th><th class="text-end">Expected (₦)</th><th class="text-end">Collected (₦)</th><th class="text-end">Outstanding (₦)</th><th>Rate</th></tr></thead></table></div></div>
    </div>

</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    let table = $('#collectionTable').DataTable({ processing: true, serverSide: true, ajax: { url: '{{ route("reports.financial.collection-summary") }}', data: function(d) { d.class_id = $('#classFilter').val(); d.term_id = $('#termFilter').val(); d.session_id = $('#sessionFilter').val(); } }, columns: [{ data: 'class' }, { data: 'term' }, { data: 'session' }, { data: 'student_count' }, { data: 'total_expected', className: 'text-end' }, { data: 'total_collected', className: 'text-end' }, { data: 'total_outstanding', className: 'text-end' }, { data: 'collection_rate' }], drawCallback: function() { const data = table.ajax.json(); if(data && data.data) { let students=0, collected=0, expected=0; data.data.forEach(r=>{ students+=parseInt(r.student_count); collected+=parseFloat(r.total_collected); expected+=parseFloat(r.total_expected); }); const rate = expected>0?((collected/expected)*100).toFixed(1):0; $('#totalStudents').text(students); $('#totalCollected').text('₦'+collected.toLocaleString()); $('#totalRate').text(rate+'%'); $('#statsRow, #tableCard').show(); } } });
    $('#loadReportBtn').on('click', () => table.ajax.reload());
});
</script>
@endsection
