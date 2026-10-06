@extends('layouts.master')

@section('content')
<style>
:root {
    --ss-primary: #1e3a5f;
    --ss-accent:  #2563eb;
    --ss-success: #16a34a;
    --ss-warning: #d97706;
    --ss-danger:  #dc2626;
    --ss-muted:   #6b7280;
    --ss-border:  #e2e8f0;
    --ss-bg:      #f8fafc;
    --ss-card:    #ffffff;
    --ss-radius:  10px;
    --ss-shadow:  0 1px 4px rgba(0,0,0,.08);
}

.stat-card { background:var(--ss-card); border:1px solid var(--ss-border); border-radius:var(--ss-radius); padding:14px 18px; box-shadow:var(--ss-shadow); transition:transform .15s; }
.stat-card:hover { transform:translateY(-2px); }
.stat-card .stat-value { font-size:22px; font-weight:700; color:var(--ss-primary); }
.stat-card .stat-label { font-size:11px; color:var(--ss-muted); margin-top:2px; }
.stat-card .stat-icon  { font-size:28px; opacity:.15; float:right; margin-top:-6px; }

.rp-select { border:1.5px solid var(--ss-border); border-radius:8px; padding:9px 12px; font-size:13px; width:100%; background:#fff; }
.rp-generate-btn { background:var(--ss-primary); border:none; color:#fff; border-radius:8px; padding:11px 22px; font-weight:600; }
.rp-generate-btn:hover { opacity:.9; color:#fff; }

#reportTable { font-size:12.5px; }
#reportTable thead tr { background:var(--ss-primary); color:#fff; }
#reportTable thead th { padding:10px 8px; font-weight:600; white-space:nowrap; border:none; }
#reportTable tbody td { padding:6px 8px; vertical-align:middle; border-bottom:1px solid var(--ss-border); }
#reportTable tbody tr:hover { background:#f0f6ff; }

.row-good { background:#f0fdf4 !important; }
.row-bad  { background:#fef2f2 !important; }
.row-warn { background:#fffbeb !important; }

.metric-pill { display:inline-block; padding:3px 10px; border-radius:20px; font-weight:700; font-size:12px; }
.rp-scope-badge { font-size:11px; padding:4px 10px; border-radius:20px; font-weight:600; }
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Error!</strong>
            <ul class="mb-0 mt-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @foreach(['success','status','warning','error'] as $bag)
        @if(session($bag))
            <div class="alert alert-{{ $bag === 'status' ? 'success' : ($bag === 'error' ? 'danger' : $bag) }} alert-dismissible fade show">
                {{ session($bag) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

<div class="row"><div class="col-12"><div class="card border-0 shadow-sm mb-4">
    <div class="card-header d-flex align-items-center flex-wrap gap-2 py-3" style="background:var(--ss-primary);">
        <h5 class="mb-0 text-white fw-semibold flex-grow-1">
            <i class="ri-bar-chart-2-line me-2"></i>{{ $pagetitle }}
        </h5>
        @if(!$isWholeSchoolAccess)
            <span class="rp-scope-badge" style="background:#fef3c7;color:#d97706;">
                <i class="ri-user-line me-1"></i>Your own reports only
            </span>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Report Type</label>
                <select class="rp-select" id="reportType">
                    <option value="teacher_workload">Teacher Workload</option>
                    <option value="class_schedule">Class Schedule Summary</option>
                    @if($isWholeSchoolAccess)
                        <option value="room_utilization">Room Utilization</option>
                        <option value="conflict_analysis">Conflict Analysis</option>
                        <option value="subject_distribution">Subject Distribution</option>
                    @endif
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Session</label>
                <select class="rp-select" id="reportSessionId">
                    <option value="">Current / Latest Session</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Term</label>
                <select class="rp-select" id="reportTermId">
                    <option value="">All Terms</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="rp-generate-btn w-100" onclick="generateReport()">
                    <i class="ri-file-chart-line me-2"></i>Generate Report
                </button>
            </div>
        </div>
    </div>
</div></div></div>

<div class="row" id="reportPreview" style="display:none"><div class="col-12"><div class="card border-0 shadow-sm mb-4">
    <div class="card-header d-flex align-items-center flex-wrap gap-2 py-3" style="background:var(--ss-primary);">
        <div class="flex-grow-1">
            <h5 class="mb-0 text-white fw-semibold">
                <i class="ri-file-list-3-line me-2"></i><span id="previewTitle">Report Preview</span>
                <span class="badge bg-white text-primary ms-2" id="reportRowCount">0</span>
            </h5>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width:200px;">
                <span class="input-group-text bg-white border-0"><i class="ri-search-line text-muted"></i></span>
                <input type="text" class="form-control border-0 ps-1" id="reportSearchInput" placeholder="Filter rows…">
            </div>
            <button type="button" class="btn btn-sm btn-warning" onclick="downloadCurrentReport('csv')">
                <i class="ri-file-excel-line me-1"></i>CSV
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="downloadCurrentReport('pdf')">
                <i class="ri-file-pdf-2-line me-1"></i>PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="reportSummaryCards" class="row g-2 mb-3" style="display:none"></div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle mb-0" id="reportTable">
                <thead><tr id="reportTableHead"></tr></thead>
                <tbody id="reportTableBody"></tbody>
            </table>
        </div>
    </div>
</div></div></div>

<div class="row"><div class="col-12"><div class="card border-0 shadow-sm">
    <div class="card-header py-3" style="background:var(--ss-primary);">
        <h6 class="mb-0 text-white fw-semibold"><i class="ri-history-line me-2"></i>Saved Reports</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Report Name</th><th>Type</th><th>Session</th><th>Term</th><th>Generated By</th><th>Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                    @php
                        $typeNames = [
                            'teacher_workload' => 'Teacher Workload', 'room_utilization' => 'Room Utilization',
                            'class_schedule' => 'Class Schedule', 'conflict_analysis' => 'Conflict Analysis',
                            'subject_distribution' => 'Subject Distribution'
                        ];
                    @endphp
                    <tr>
                        <td class="fw-medium">{{ $report->report_name }}</td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ $typeNames[$report->report_type] ?? $report->report_type }}</span></td>
                        <td>{{ $report->session->session ?? 'N/A' }}</td>
                        <td>{{ $report->term->term ?? 'All' }}</td>
                        <td>{{ $report->generator->name ?? 'N/A' }}</td>
                        <td>{{ $report->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" onclick="viewSavedReport({{ $report->id }})" title="View"><i class="ri-eye-line"></i></button>
                            <button class="btn btn-sm btn-outline-warning" onclick="downloadSavedReport({{ $report->id }}, 'csv')" title="CSV"><i class="ri-file-excel-line"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="downloadSavedReport({{ $report->id }}, 'pdf')" title="PDF"><i class="ri-file-pdf-line"></i></button>
                            @can('Delete timetable reports')
                            <button class="btn btn-sm btn-outline-secondary" onclick="deleteReport({{ $report->id }})" title="Delete"><i class="ri-delete-bin-line"></i></button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No reports generated yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3 p-3">{{ $reports->links('pagination::bootstrap-5') }}</div>
    </div>
</div></div></div>

</div></div></div>

<script>
let currentReportId   = null;
let currentReportRows = [];
let currentReportType = null;

const REPORT_ROUTES = {
    generate: '{{ route("timetable.reports.generate") }}',
    download: '{{ url("/timetable-reports/download") }}',
    show:     '{{ url("/timetable-reports") }}',
    destroy:  '{{ url("/timetable-reports") }}',
};
const CSRF = '{{ csrf_token() }}';

function showToast(msg, type = 'info') {
    const colors = { success:'#16a34a', warning:'#d97706', danger:'#dc2626', info:'#2563eb' };
    const id = 'toast_' + Date.now();
    document.body.insertAdjacentHTML('beforeend',
        `<div id="${id}" class="toast align-items-center border-0 text-white show" role="alert"
          style="position:fixed;bottom:20px;right:20px;z-index:99999;background:${colors[type]||colors.info};min-width:280px;border-radius:10px;">
          <div class="d-flex p-3"><div class="me-auto">${msg}</div>
          <button class="btn-close btn-close-white ms-2" onclick="this.closest('.toast').remove()"></button></div></div>`);
    setTimeout(() => document.getElementById(id)?.remove(), 4500);
}

function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const sessionId  = document.getElementById('reportSessionId').value;
    const termId     = document.getElementById('reportTermId').value;

    Swal.fire({ title: 'Generating Report…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch(REPORT_ROUTES.generate, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ report_type: reportType, session_id: sessionId || null, term_id: termId || null, format: 'json' })
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (!data.success) { showToast(data.message || 'Failed to generate report', 'danger'); return; }

        currentReportId   = data.report.id;
        currentReportType = reportType;

        renderReportSummary(data.summary || []);
        renderReportTable(data.data, reportType);

        document.getElementById('reportPreview').style.display = '';
        document.getElementById('reportPreview').scrollIntoView({ behavior: 'smooth', block: 'start' });
    })
    .catch(() => { Swal.close(); showToast('Failed to generate report', 'danger'); });
}

function renderReportSummary(summary) {
    const el = document.getElementById('reportSummaryCards');
    if (!summary || !summary.length) { el.style.display = 'none'; el.innerHTML = ''; return; }
    el.style.display = '';
    el.innerHTML = summary.map(s => `
        <div class="col-md-4">
            <div class="stat-card text-center h-100">
                <div class="stat-value">${s.value}</div>
                <div class="stat-label">${s.label}</div>
            </div>
        </div>`).join('');
}

function rowClassFor(reportType, row) {
    if (reportType === 'class_schedule') {
        const pct = parseFloat(row.completion_percent);
        return pct >= 90 ? 'row-good' : pct < 50 ? 'row-bad' : 'row-warn';
    }
    if (reportType === 'conflict_analysis') return 'row-bad';
    return '';
}

function renderReportTable(data, reportType) {
    const rows = (reportType === 'conflict_analysis') ? (data.conflicts || []) : (data || []);
    currentReportRows = rows;

    const head = document.getElementById('reportTableHead');
    const body = document.getElementById('reportTableBody');
    document.getElementById('reportRowCount').textContent = rows.length;
    document.getElementById('previewTitle').textContent =
        document.getElementById('reportType').options[document.getElementById('reportType').selectedIndex].text + ' — Preview';

    if (!rows.length) {
        head.innerHTML = '';
        body.innerHTML = `<tr><td class="text-center text-muted py-4">
            <i class="ri-inbox-line ri-2x d-block mb-2"></i>No data available for this report and scope.
        </td></tr>`;
        return;
    }

    const headers = Object.keys(rows[0]);
    head.innerHTML = headers.map(h => `<th>${h.replace(/_/g, ' ').toUpperCase()}</th>`).join('');

    body.innerHTML = rows.map(row => {
        const cls = rowClassFor(reportType, row);
        const cells = headers.map(h => {
            let v = row[h];
            if (typeof v === 'object') v = JSON.stringify(v);
            if (typeof v === 'string' && v.endsWith('%')) {
                const pct = parseFloat(v);
                const color = pct >= 70 ? '#16a34a' : pct >= 40 ? '#d97706' : '#dc2626';
                return `<td><span class="metric-pill" style="background:${color}18;color:${color};">${v}</span></td>`;
            }
            return `<td>${v !== null && v !== undefined && v !== '' ? v : '—'}</td>`;
        }).join('');
        return `<tr class="${cls}">${cells}</tr>`;
    }).join('');
}

document.getElementById('reportSearchInput')?.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    let visible = 0;
    document.querySelectorAll('#reportTableBody tr').forEach(tr => {
        const show = !q || tr.textContent.toLowerCase().includes(q);
        tr.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('reportRowCount').textContent = visible;
});

function downloadCurrentReport(format) {
    if (!currentReportId) return showToast('Generate a report first.', 'warning');
    window.open(`${REPORT_ROUTES.download}/${currentReportId}?format=${format}`, '_blank');
}

function viewSavedReport(id) {
    fetch(`${REPORT_ROUTES.show}/${id}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return showToast(data.message || 'Failed to load report', 'danger');
            currentReportId   = data.report.id;
            currentReportType = data.report.report_type;
            document.getElementById('reportType').value = data.report.report_type;
            renderReportSummary(data.summary || []);
            renderReportTable(data.report.data, data.report.report_type);
            document.getElementById('reportPreview').style.display = '';
            document.getElementById('reportPreview').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
}

function downloadSavedReport(id, format) {
    window.open(`${REPORT_ROUTES.download}/${id}?format=${format}`, '_blank');
}

function deleteReport(id) {
    Swal.fire({
        title: 'Delete Report?', text: 'This action cannot be undone!', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it!'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(`${REPORT_ROUTES.destroy}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 800); }
            else showToast(data.message, 'danger');
        });
    });
}

if (typeof Swal === 'undefined') {
    const s = document.createElement('script'); s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11'; document.head.appendChild(s);
}
</script>
@endsection