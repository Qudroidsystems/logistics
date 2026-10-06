{{-- resources/views/reports/class-analysis.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
.report-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 24px;
    overflow: hidden;
}
.report-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: white;
    padding: 15px 20px;
}
.stat-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s;
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
.progress {
    height: 8px;
    border-radius: 10px;
    background-color: #e2e8f0;
}
.progress-bar {
    background: linear-gradient(90deg, #2563eb, #16a34a);
    border-radius: 10px;
    transition: width 0.5s ease;
}
.completion-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.completion-high { background: #dcfce7; color: #16a34a; }
.completion-medium { background: #fef3c7; color: #d97706; }
.completion-low { background: #fee2e2; color: #dc2626; }
.filter-bar {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}
.filter-label {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 8px;
    color: #1e3a5f;
}
.filter-label .required {
    color: #dc2626;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold">Class Analysis Report</h4>
                    <p class="text-muted">Analyze fee collection by class, term, and session</p>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-outline-secondary me-2">
                        <i class="ri-printer-line"></i> Print
                    </button>
                    <button onclick="exportToExcel()" class="btn btn-success me-2">
                        <i class="ri-file-excel-line"></i> Excel
                    </button>
                    <button onclick="exportToPDF()" class="btn btn-danger">
                        <i class="ri-file-pdf-line"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <div class="filter-label">Class <span class="required">*</span></div>
                <select class="form-select" id="classSelect">
                    <option value="">-- Select Class --</option>
                    @foreach($classes ?? [] as $class)
                        <option value="{{ $class->id }}">{{ $class->display_name ?? $class->schoolclass . ' ' . ($class->arm ?? '') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="filter-label">Term <span class="required">*</span></div>
                <select class="form-select" id="termSelect">
                    <option value="">-- Select Term --</option>
                    @foreach($terms ?? [] as $term)
                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="filter-label">Session <span class="required">*</span></div>
                <select class="form-select" id="sessionSelect">
                    <option value="">-- Select Session --</option>
                    @foreach($sessions ?? [] as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" id="loadReportBtn">
                    <i class="ri-search-line me-1"></i>Load Report
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="row g-3 mb-4" id="statsRow" style="display: none;">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-primary mb-2"><i class="ri-group-line fs-1"></i></div>
                <h3 id="totalStudents">0</h3>
                <small class="text-muted">Total Students</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-success mb-2"><i class="ri-money-dollar-circle-line fs-1"></i></div>
                <h3 id="totalBilled">₦0</h3>
                <small class="text-muted">Total Billed</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-warning mb-2"><i class="ri-wallet-line fs-1"></i></div>
                <h3 id="totalPaid">₦0</h3>
                <small class="text-muted">Total Paid</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-info mb-2"><i class="ri-percent-line fs-1"></i></div>
                <h3 id="collectionRate">0%</h3>
                <small class="text-muted">Collection Rate</small>
            </div>
        </div>
    </div>

    <!-- Progress Summary Card -->
    <div class="report-card" id="summaryCard" style="display: none;">
        <div class="report-header">
            <h5 class="mb-0"><i class="ri-bar-chart-line me-2"></i>Collection Summary</h5>
        </div>
        <div class="p-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Collection Progress</label>
                        <div class="progress mt-1">
                            <div id="collectionProgressBar" class="progress-bar" style="width: 0%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Paid: <span id="progressPaidAmount">₦0</span></small>
                            <small class="text-muted" id="progressText">0%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fw-bold text-primary fs-4" id="summaryStudents">0</div>
                            <small class="text-muted">Total Students</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-success fs-4" id="summaryFullyPaid">0</div>
                            <small class="text-muted">Fully Paid</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-warning fs-4" id="summaryPartial">0</div>
                            <small class="text-muted">Partial</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="report-card mt-3" id="tableCard" style="display: none;">
        <div class="report-header">
            <h5 class="mb-0"><i class="ri-table-line me-2"></i>Student Payment Details</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="analysisTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Student Name</th>
                        <th>Admission No</th>
                        <th class="text-end">Total Billed (₦)</th>
                        <th class="text-end">Total Paid (₦)</th>
                        <th class="text-end">Outstanding (₦)</th>
                        <th>Status</th>
                        <th width="80">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="fw-bold">Total</td>
                        <td class="text-end fw-bold" id="footTotalBilled">₦0.00</td>
                        <td class="text-end fw-bold" id="footTotalPaid">₦0.00</td>
                        <td class="text-end fw-bold" id="footTotalOutstanding">₦0.00</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var analysisTable;
var currentFilters = {};

$(document).ready(function() {
    // Initialize DataTable without AJAX initially
    analysisTable = $('#analysisTable').DataTable({
        processing: true,
        serverSide: false, // Start with client-side, we'll reload with server-side when needed
        pageLength: 25,
        ordering: true,
        order: [[5, 'desc']],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '50px' },
            { data: 'student_name', render: function(data) { return '<div class="fw-semibold">' + escapeHtml(data) + '</div>'; } },
            { data: 'admission_no' },
            { data: 'total_billed', className: 'text-end', render: function(data) { return '₦' + parseFloat(data || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 }); } },
            { data: 'total_paid', className: 'text-end', render: function(data) { return '<span class="text-success">₦' + parseFloat(data || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 }) + '</span>'; } },
            { data: 'outstanding', className: 'text-end', render: function(data) { var amount = parseFloat(data || 0); return amount > 0 ? '<span class="text-danger">₦' + amount.toLocaleString('en-NG', { minimumFractionDigits: 2 }) + '</span>' : '₦0.00'; } },
            { data: 'status', orderable: false, render: function(data) { return getStatusBadge(data); } },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            emptyTable: '<div class="text-center py-5 text-muted"><i class="ri-inbox-line d-block mb-2 fs-1"></i>No data available. Please select filters and click Load Report.</div>',
            processing: '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 mb-0">Loading data...</p></div>'
        }
    });

    $('#loadReportBtn').on('click', function() {
        var classId = $('#classSelect').val();
        var termId = $('#termSelect').val();
        var sessionId = $('#sessionSelect').val();

        if (!classId || !termId || !sessionId) {
            Swal.fire('Warning', 'Please select class, term, and session', 'warning');
            return;
        }

        currentFilters = {
            class_id: classId,
            term_id: termId,
            session_id: sessionId
        };

        loadReportData();
    });
});

function getStatusBadge(status) {
    if (status === 'Fully Paid') {
        return '<span class="completion-badge completion-high"><i class="ri-checkbox-circle-line me-1"></i>Fully Paid</span>';
    } else if (status === 'Partial') {
        return '<span class="completion-badge completion-medium"><i class="ri-time-line me-1"></i>Partial</span>';
    } else {
        return '<span class="completion-badge completion-low"><i class="ri-error-warning-line me-1"></i>No Payment</span>';
    }
}

function loadReportData() {
    // Show loading
    analysisTable.clear().draw();
    $('#statsRow, #summaryCard, #tableCard').hide();

    $.ajax({
        url: '{{ route("reports.analysis.class") }}',
        type: 'GET',
        data: {
            class_id: currentFilters.class_id,
            term_id: currentFilters.term_id,
            session_id: currentFilters.session_id,
            ajax: true
        },
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            if (response && response.data) {
                // Add row index
                var dataWithIndex = response.data.map(function(item, index) {
                    return {
                        ...item,
                        DT_RowIndex: index + 1,
                        action: '<a href="/payment/details/' + item.student_id + '/' + item.class_id + '/' + item.term_id + '/' + item.session_id + '" class="btn btn-sm btn-outline-primary" target="_blank" title="View Payment Details"><i class="ri-eye-line"></i></a>'
                    };
                });

                analysisTable.clear();
                analysisTable.rows.add(dataWithIndex);
                analysisTable.draw();

                updateStats(response);
                $('#statsRow, #summaryCard, #tableCard').show();
                updateFooterTotals();
            } else {
                analysisTable.clear().draw();
                Swal.fire('Info', 'No data found for the selected filters', 'info');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            var errorMsg = 'Failed to load data. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            Swal.fire('Error', errorMsg, 'error');
            analysisTable.clear().draw();
        }
    });
}

function updateStats(response) {
    var totalStudents = response.data ? response.data.length : 0;
    var totalBilled = 0;
    var totalPaid = 0;
    var totalOutstanding = 0;
    var fullyPaidCount = 0;
    var partialCount = 0;

    if (response.data && response.data.length) {
        response.data.forEach(function(row) {
            var billed = parseFloat(row.total_billed) || 0;
            var paid = parseFloat(row.total_paid) || 0;
            var outstanding = parseFloat(row.outstanding) || 0;

            totalBilled += billed;
            totalPaid += paid;
            totalOutstanding += outstanding;

            if (outstanding <= 0 && paid > 0) {
                fullyPaidCount++;
            } else if (paid > 0) {
                partialCount++;
            }
        });
    }

    var collectionRate = totalBilled > 0 ? ((totalPaid / totalBilled) * 100).toFixed(1) : 0;

    $('#totalStudents').text(totalStudents.toLocaleString());
    $('#totalBilled').text('₦' + totalBilled.toLocaleString('en-NG', { minimumFractionDigits: 2 }));
    $('#totalPaid').text('₦' + totalPaid.toLocaleString('en-NG', { minimumFractionDigits: 2 }));
    $('#collectionRate').text(collectionRate + '%');

    $('#summaryStudents').text(totalStudents);
    $('#summaryFullyPaid').text(fullyPaidCount);
    $('#summaryPartial').text(partialCount);

    var progressWidth = Math.min(100, collectionRate);
    $('#collectionProgressBar').css('width', progressWidth + '%');
    $('#progressText').text(collectionRate + '%');
    $('#progressPaidAmount').text('₦' + totalPaid.toLocaleString('en-NG', { minimumFractionDigits: 0 }));

    if (collectionRate >= 70) {
        $('#collectionProgressBar').css('background', 'linear-gradient(90deg, #16a34a, #22c55e)');
    } else if (collectionRate >= 40) {
        $('#collectionProgressBar').css('background', 'linear-gradient(90deg, #d97706, #f59e0b)');
    } else {
        $('#collectionProgressBar').css('background', 'linear-gradient(90deg, #dc2626, #ef4444)');
    }
}

function updateFooterTotals() {
    var totalBilled = 0;
    var totalPaid = 0;
    var totalOutstanding = 0;

    analysisTable.rows().every(function() {
        var data = this.data();
        totalBilled += parseFloat(data.total_billed) || 0;
        totalPaid += parseFloat(data.total_paid) || 0;
        totalOutstanding += parseFloat(data.outstanding) || 0;
    });

    $('#footTotalBilled').text('₦' + totalBilled.toLocaleString('en-NG', { minimumFractionDigits: 2 }));
    $('#footTotalPaid').text('₦' + totalPaid.toLocaleString('en-NG', { minimumFractionDigits: 2 }));
    $('#footTotalOutstanding').text('₦' + totalOutstanding.toLocaleString('en-NG', { minimumFractionDigits: 2 }));
}

function exportToExcel() {
    if (!currentFilters.class_id || !currentFilters.term_id || !currentFilters.session_id) {
        Swal.fire('Warning', 'Please load report data first', 'warning');
        return;
    }
    var url = '{{ route("reports.analysis.class.export", ["excel"]) }}?class_id=' + currentFilters.class_id + '&term_id=' + currentFilters.term_id + '&session_id=' + currentFilters.session_id;
    window.open(url, '_blank');
}

function exportToPDF() {
    if (!currentFilters.class_id || !currentFilters.term_id || !currentFilters.session_id) {
        Swal.fire('Warning', 'Please load report data first', 'warning');
        return;
    }
    var url = '{{ route("reports.analysis.class.export", ["pdf"]) }}?class_id=' + currentFilters.class_id + '&term_id=' + currentFilters.term_id + '&session_id=' + currentFilters.session_id;
    window.open(url, '_blank');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}
</script>
@endsection
