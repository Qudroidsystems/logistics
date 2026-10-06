{{-- resources/views/studentreports/index.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style>
:root {
    --bill-primary: #1e3a5f;
    --bill-accent:  #2563eb;
    --bill-success: #16a34a;
    --bill-warning: #d97706;
    --bill-danger:  #dc2626;
    --bill-muted:   #6b7280;
    --bill-border:  #e2e8f0;
    --bill-bg:      #f8fafc;
    --bill-radius:  12px;
    --bill-shadow:  0 2px 8px rgba(0,0,0,.08);
}

@keyframes spin      { to { transform: rotate(360deg); } }
@keyframes readyGlow {
    0%,100% { box-shadow: 0 4px 12px rgba(37,99,235,.35); }
    50%     { box-shadow: 0 4px 12px rgba(37,99,235,.35), 0 0 0 5px rgba(37,99,235,.16); }
}

/* ── Hero ──────────────────────────────────────────────── */
.bill-hero {
    background: linear-gradient(135deg, var(--bill-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--bill-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.bill-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.bill-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.bill-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.bill-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--bill-border);
    border-radius:var(--bill-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--bill-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--bill-primary); }
.stat-card .stat-label { font-size:12px; color:var(--bill-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Filter card ───────────────────────────────────────── */
.filter-card {
    background:#fff; border:1px solid var(--bill-border);
    border-top:3px solid var(--bill-accent);
    border-radius:var(--bill-radius); padding:20px 24px 16px;
    margin-bottom:24px; box-shadow:var(--bill-shadow);
}
.filter-title {
    display:flex; align-items:center; gap:8px;
    font-size:13px; font-weight:700; color:var(--bill-primary);
    margin-bottom:14px;
}
.filter-title i { color:var(--bill-accent); }
.filter-hint {
    display:flex; align-items:center; gap:6px;
    margin-top:14px; padding-top:12px;
    border-top:1px dashed var(--bill-border);
    font-size:12px; color:var(--bill-muted);
}
.filter-hint i { color:var(--bill-accent); }
.filter-hint strong { color:var(--bill-primary); }

/* Same-height controls in the filter row */
.filter-card .form-select,
.filter-card .form-control { height:42px; }
.filter-card .btn { height:42px; }

.btn-icon-flex {
    display:inline-flex; align-items:center; justify-content:center; gap:6px;
}

/* ── Search button — obvious call to action ───────────── */
.btn.search-btn {
    background:linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    border:none; color:#fff;
    min-width:158px; padding:9px 22px;
    font-size:13.5px; font-weight:700; letter-spacing:.2px;
    border-radius:9px; cursor:pointer;
    box-shadow:0 4px 12px rgba(37,99,235,.35);
    transition:transform .15s, box-shadow .15s;
}
.btn.search-btn:hover {
    color:#fff; transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(37,99,235,.45);
    animation:none;
}
.btn.search-btn:active {
    transform:translateY(0);
    box-shadow:0 2px 6px rgba(37,99,235,.35);
}
.btn.search-btn:focus-visible { box-shadow:0 0 0 3px rgba(37,99,235,.4); }
.btn.search-btn.is-ready { animation:readyGlow 2.2s ease-in-out infinite; }
.btn.search-btn .btn-spinner {
    display:none; width:15px; height:15px;
    border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%;
    animation:spin .7s linear infinite;
}
.btn.search-btn.is-loading { pointer-events:none; opacity:.85; animation:none; }
.btn.search-btn.is-loading .btn-spinner { display:inline-block; }
.btn.search-btn.is-loading .btn-icon    { display:none; }

.btn.reset-btn {
    background:#fff; color:var(--bill-muted);
    border:1.5px solid var(--bill-border);
    border-radius:9px; font-weight:600; font-size:13px;
    transition:all .15s;
}
.btn.reset-btn:hover {
    color:var(--bill-danger); border-color:#fecaca; background:#fef2f2;
}

/* ── Table ─────────────────────────────────────────────── */
.bill-table th {
    background:var(--bill-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.bill-table td {
    padding:12px 16px; vertical-align:middle;
    border-bottom:1px solid var(--bill-border); font-size:13px;
}
.bill-table tr:hover td { background:#eff6ff; }
.bill-table tbody tr.table-active > td { background:#eff6ff; box-shadow:none; }
.bill-table tbody tr.table-active > td:first-child { box-shadow:inset 3px 0 0 var(--bill-accent); }

/* ── Form controls ─────────────────────────────────────── */
.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select {
    border:1.5px solid var(--bill-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--bill-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
    outline:none;
}

/* ── Modal ─────────────────────────────────────────────── */
.bill-modal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, var(--bill-primary) 0%, #2563eb 100%);
    padding:22px 28px; position:relative; overflow:hidden;
}
.modal-hero-bar::before {
    content:''; position:absolute; top:-30px; right:-30px;
    width:120px; height:120px; background:rgba(255,255,255,.07); border-radius:50%;
}
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

/* ── Alert banner ──────────────────────────────────────── */
#selectionAlert {
    border-radius:var(--bill-radius);
    font-size:13px; font-weight:500;
}

/* ── Grade basis toggle ───────────────────────────────────── */
.grade-basis-option {
    border: 1.5px solid var(--bill-border);
    border-radius: 10px;
    padding: 12px 14px;
    cursor: pointer;
    transition: border-color .15s, background .15s;
    height: 100%;
}
.grade-basis-option:hover { border-color: var(--bill-accent); }
.grade-basis-option.active {
    border-color: var(--bill-accent);
    background: #eff6ff;
}
.grade-basis-option .form-check-input { margin-top: 3px; }
.grade-basis-option .gb-title { font-weight: 700; font-size: 13px; color: var(--bill-primary); }
.grade-basis-option .gb-desc  { font-size: 11.5px; color: var(--bill-muted); margin-top: 2px; }

/* ── Small screens ─────────────────────────────────────── */
@media (max-width: 991.98px) {
    .filter-actions { width:100%; flex-wrap:wrap; }
    .filter-actions .btn { flex:1; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Fixed selection alert --}}
    <div id="selectionAlert" class="alert alert-info alert-dismissible fade show"
         role="alert"
         style="display:none; position:fixed; top:0; left:0; right:0; z-index:1050; margin:0 auto; max-width:90%;">
        <span id="selectionAlertText">No selections made.</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    {{-- Hero --}}
    <div class="bill-hero" style="margin-top:16px;">
        <h1><i class="ri-bar-chart-2-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Filter, view and export student academic reports by class, session and term.</p>
    </div>

    {{-- Flash messages --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('status') || session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') ?? session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value" id="statTotal">{{ $allstudents ? $allstudents->total() : 0 }}</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-men-line"></i></div>
                <div class="stat-value text-primary" id="statMale">—</div>
                <div class="stat-label">Male Students</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-women-line"></i></div>
                <div class="stat-value text-success" id="statFemale">—</div>
                <div class="stat-label">Female Students</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-file-chart-line"></i></div>
                <div class="stat-value text-warning" id="statSelected">0</div>
                <div class="stat-label">Selected for Export</div>
            </div>
        </div>
    </div>

    {{-- Filter card --}}
    <div class="filter-card">
        <div class="filter-title">
            <i class="ri-filter-3-line"></i> Find Students
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="idclass"><i class="ri-graduation-cap-line me-1"></i>Class</label>
                <select class="form-select" id="idclass" name="schoolclassid">
                    <option value="ALL">— Select Class —</option>
                    @foreach ($schoolclasses as $class)
                        <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label" for="idsession"><i class="ri-calendar-line me-1"></i>Session</label>
                <select class="form-select" id="idsession" name="sessionid">
                    <option value="ALL">— Select Session —</option>
                    @foreach ($schoolsessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2" id="termSelectContainer" style="display:none;">
                <label class="form-label" for="idterm"><i class="ri-time-line me-1"></i>Term</label>
                <select class="form-select" id="idterm" name="termid">
                    <option value="ALL">— Select Term —</option>
                    <option value="1">First Term</option>
                    <option value="2">Second Term</option>
                    <option value="3">Third Term</option>
                </select>
            </div>
            <div class="col-sm-6 col-lg">
                <label class="form-label" for="searchInput"><i class="ri-search-line me-1"></i>Search</label>
                <input type="text" class="form-control" id="searchInput"
                       name="search" placeholder="Name, admission no…" autocomplete="off">
            </div>
            <div class="col-12 col-lg-auto">
                <div class="d-flex gap-2 filter-actions">
                    <button type="button" class="btn search-btn btn-icon-flex" id="searchBtn"
                            onclick="filterData()" title="Load students for the selected class and session">
                        <i class="ri-search-line btn-icon"></i>
                        <span class="btn-spinner"></span>
                        <span class="btn-label">Click to Search</span>
                    </button>
                    <button type="button" class="btn reset-btn btn-icon-flex" id="resetBtn"
                            onclick="resetFilters()" title="Clear all filters">
                        <i class="ri-refresh-line"></i> Reset
                    </button>
                    <button type="button" class="btn btn-success btn-icon-flex"
                            id="printAllBtn" style="display:none;" onclick="printAllResults()">
                        <i class="ri-printer-line"></i> Print Selected
                    </button>
                </div>
            </div>
        </div>

        <div class="filter-hint">
            <i class="ri-information-line"></i>
            <span>Choose a class and session, then click <strong>Click to Search</strong>. Pick a term to export PDF reports.</span>
        </div>
    </div>

    {{-- Student table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--bill-primary)">
                    <i class="ri-group-line me-2"></i>Students
                    <span class="badge bg-primary ms-2" id="studentcount">{{ $allstudents ? $allstudents->total() : 0 }}</span>
                </h5>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bill-table w-100 mb-0" id="studentListTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                            </th>
                            <th>Admission No</th>
                            <th>Picture</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Other Name</th>
                            <th>Gender</th>
                            <th>Class</th>
                            <th>Arm</th>
                            <th>Session</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        @include('studentreports.partials.student_rows')
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3" id="pagination-container">
                {{ $allstudents ? $allstudents->links('pagination::bootstrap-5') : '' }}
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- Image View Modal --}}
<div id="imageViewModal" class="modal fade bill-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-image-line me-2"></i>Student Image</h5>
            </div>
            <div class="modal-body text-center p-4">
                <img id="enlargedImage" src="" alt="Student Image"
                     class="img-fluid rounded" style="max-height:420px;"
                     onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
            </div>
        </div>
    </div>
</div>

{{-- Column Selection Modal --}}
<div class="modal fade bill-modal" id="columnSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-layout-column-line me-2"></i>Select Columns for PDF Report</h5>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info d-flex align-items-center gap-2"
                     style="border-radius:8px; font-size:13px;">
                    <i class="ri-information-line fs-5"></i>
                    <span>Select the columns to include in the PDF report. Class, Session, and Term must be selected first.</span>
                </div>

                <div id="columnSelectionLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading columns...</span>
                    </div>
                    <p class="mt-2 text-muted" style="font-size:13px;">Loading column options...</p>
                </div>

                <div id="columnSelectionForm" style="display:none;">
                    <div class="row g-3">

                        {{-- Grade Basis Toggle --}}
                        <div class="col-12">
                            <div class="card border" style="border-radius:var(--bill-radius);">
                                <div class="card-header bg-white fw-semibold"
                                     style="font-size:13px; color:var(--bill-primary);">
                                    <i class="ri-medal-line me-1"></i>Grade Basis
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="grade-basis-option active d-flex gap-2 mb-0" id="gbOptionTotal">
                                                <input class="form-check-input" type="radio" name="gradeBasis"
                                                       id="gradeBasisTotal" value="total" checked>
                                                <span>
                                                    <span class="gb-title d-block">Term Total (current)</span>
                                                    <span class="gb-desc d-block">Grades each subject off the raw score entered for this term.</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="grade-basis-option d-flex gap-2 mb-0" id="gbOptionCumAve">
                                                <input class="form-check-input" type="radio" name="gradeBasis"
                                                       id="gradeBasisCumAve" value="cum_ave">
                                                <span>
                                                    <span class="gb-title d-block">Cumulative Average</span>
                                                    <span class="gb-desc d-block">Grades each subject off Cum Ave (cumulative sum ÷ term number) instead.</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border" style="border-radius:var(--bill-radius);">
                                <div class="card-header bg-white fw-semibold"
                                     style="font-size:13px; color:var(--bill-primary);">
                                    <i class="ri-user-line me-1"></i>Student Information
                                </div>
                                <div class="card-body">
                                    <div class="row" id="studentInfoColumns"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border" style="border-radius:var(--bill-radius);">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center"
                                     style="font-size:13px; color:var(--bill-primary);">
                                    <span class="fw-semibold"><i class="ri-pencil-ruler-line me-1"></i>Assessments</span>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="selectAllAssessments">
                                        <label class="form-check-label" for="selectAllAssessments"
                                               style="font-size:12px;">Select All</label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row" id="assessmentColumns"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border" style="border-radius:var(--bill-radius);">
                                <div class="card-header bg-white fw-semibold"
                                     style="font-size:13px; color:var(--bill-primary);">
                                    <i class="ri-bar-chart-line me-1"></i>Scores &amp; Metrics
                                </div>
                                <div class="card-body">
                                    <div class="row" id="scoreColumns"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border" style="border-radius:var(--bill-radius);">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center"
                                     style="font-size:13px; color:var(--bill-primary);">
                                    <span class="fw-semibold"><i class="ri-award-line me-1"></i>GPA/CGPA Metrics</span>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="selectAllGPAMetrics">
                                        <label class="form-check-label" for="selectAllGPAMetrics"
                                               style="font-size:12px;">Select All</label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row" id="gpaColumns"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border" style="border-radius:var(--bill-radius);">
                                <div class="card-header bg-white fw-semibold"
                                     style="font-size:13px; color:var(--bill-primary);">
                                    <i class="ri-more-line me-1"></i>Other Information
                                </div>
                                <div class="card-body">
                                    <div class="row" id="otherColumns"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveColumnSelection" disabled>
                    <i class="ri-file-pdf-line me-1"></i>Apply &amp; Generate PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    console.log("Script loaded at", new Date().toISOString());

    // ── Visibility helpers ──────────────────────────────────────────────

    function updateSelectionAlert() {
        const classSelect   = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect    = document.getElementById("idterm");
        const checked       = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const alert         = document.getElementById("selectionAlert");
        const alertText     = document.getElementById("selectionAlertText");

        let parts = [];
        if (classSelect.value !== 'ALL')
            parts.push(`Class: ${classSelect.options[classSelect.selectedIndex].text}`);
        if (sessionSelect.value !== 'ALL')
            parts.push(`Session: ${sessionSelect.options[sessionSelect.selectedIndex].text}`);
        if (termSelect.value !== 'ALL')
            parts.push(`Term: ${termSelect.options[termSelect.selectedIndex].text}`);
        parts.push(`Students Selected: ${checked.length}`);

        document.getElementById('statSelected').textContent = checked.length;

        if (classSelect.value !== 'ALL' && sessionSelect.value !== 'ALL') {
            alert.style.display = 'block';
            alertText.innerText = parts.join(' | ');
        } else {
            alert.style.display = 'none';
            alertText.innerText = 'No selections made.';
        }
    }

    /** Search button stays visible; it glows once class + session are both chosen. */
    function updateSearchButtonVisibility() {
        const classValue   = document.getElementById("idclass").value;
        const sessionValue = document.getElementById("idsession").value;
        document.getElementById("searchBtn").classList.toggle(
            'is-ready', classValue !== 'ALL' && sessionValue !== 'ALL'
        );
        updateSelectionAlert();
    }

    /** Spinner + "Searching…" label while a search request is running. */
    function setSearchLoading(isLoading) {
        const btn = document.getElementById('searchBtn');
        btn.classList.toggle('is-loading', isLoading);
        btn.querySelector('.btn-label').textContent = isLoading ? 'Searching…' : 'Click to Search';
    }

    // Only controls term dropdown visibility — never touches print button
    function updateTermSelectVisibility() {
        const studentCount = parseInt(document.getElementById("studentcount").innerText) || 0;
        document.getElementById("termSelectContainer").style.display =
            studentCount > 0 ? 'block' : 'none';
        updateSelectionAlert();
    }

    // Sole owner of print button visibility
    function updatePrintButtonVisibility() {
        const termValue = document.getElementById("idterm").value;
        const checked   = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const show      = termValue !== 'ALL' && checked.length > 0;
        document.getElementById("printAllBtn").style.display = show ? 'inline-flex' : 'none';
        updateSelectionAlert();
    }

    // ── Filter / search ─────────────────────────────────────────────────

    function filterData() {
        if (typeof axios === 'undefined') {
            Swal.fire({ icon: "error", title: "Configuration Error", text: "Axios library is missing." });
            return;
        }

        const classValue   = document.getElementById("idclass").value;
        const sessionValue = document.getElementById("idsession").value;
        const termValue    = document.getElementById("idterm").value;
        const searchValue  = (document.getElementById("searchInput").value || '').trim();

        if (classValue === 'ALL' || sessionValue === 'ALL') {
            resetTable();
            Swal.fire({ icon: "warning", title: "Missing Selection",
                        text: "Please select a valid class and session." });
            return;
        }

        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML =
            '<tr><td colspan="10" class="text-center py-4">' +
            '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading...</td></tr>';

        setSearchLoading(true);

        axios.get('{{ route("studentreports.index") }}', {
            params: { search: searchValue, schoolclassid: classValue,
                      sessionid: sessionValue, termid: termValue },
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            document.getElementById('studentTableBody').innerHTML =
                response.data.tableBody ||
                '<tr><td colspan="10" class="text-center py-4 text-muted">No students found.</td></tr>';
            document.getElementById('pagination-container').innerHTML =
                response.data.pagination || '';

            const count = response.data.studentCount || '0';
            document.getElementById('studentcount').innerText = count;
            document.getElementById('statTotal').innerText    = count;

            setupPaginationLinks();
            setupCheckboxListeners();
            updateTermSelectVisibility();
            // Newly loaded rows have no checkboxes ticked yet,
            // so the print button stays hidden until the user checks some.
            updatePrintButtonVisibility();

            if (!response.data.tableBody ||
                response.data.tableBody.includes('No students found') ||
                response.data.tableBody.includes('Select class and session')) {
                Swal.fire({ icon: "info", title: "No Results",
                            text: "No students found for the selected filters." });
            }
        }).catch(function (error) {
            console.error("AJAX error:", error);
            tableBody.innerHTML =
                '<tr><td colspan="10" class="text-center text-danger py-4">' +
                'Error loading data. Please try again.</td></tr>';
            Swal.fire({ icon: "error", title: "Error",
                        text: error.response?.data?.message || "Failed to fetch student data." });
        }).finally(function () {
            setSearchLoading(false);
        });
    }

    function resetTable() {
        document.getElementById('studentTableBody').innerHTML =
            '<tr><td colspan="10" class="text-center py-4 text-muted">' +
            'Select class and session, then click Search to view students.</td></tr>';
        document.getElementById('pagination-container').innerHTML = '';
        document.getElementById('studentcount').innerText = '0';
        document.getElementById('statTotal').innerText    = '0';
        document.getElementById('printAllBtn').style.display     = 'none';
        document.getElementById('termSelectContainer').style.display = 'none';
        const checkAll = document.getElementById('checkAll');
        if (checkAll) checkAll.checked = false;
        updateSelectionAlert();
    }

    function resetFilters() {
        document.getElementById('idclass').value     = 'ALL';
        document.getElementById('idsession').value   = 'ALL';
        document.getElementById('idterm').value      = 'ALL';
        document.getElementById('searchInput').value = '';
        resetTable();
        updateSearchButtonVisibility();
    }

    // ── Print / PDF ─────────────────────────────────────────────────────

    function printAllResults() {
        const classValue   = document.getElementById("idclass").value;
        const sessionValue = document.getElementById("idsession").value;
        const termValue    = document.getElementById("idterm").value;
        const checked      = document.querySelectorAll('tbody input[name="chk_child"]:checked');
        const selectedIds  = Array.from(checked).map(cb => cb.value);

        if (classValue === 'ALL' || sessionValue === 'ALL' || termValue === 'ALL') {
            Swal.fire({ icon: "warning", title: "Missing Selection",
                        text: "Please select a valid class, session, and term." });
            return;
        }
        if (selectedIds.length === 0) {
            Swal.fire({ icon: "warning", title: "No Students Selected",
                        text: "Please select at least one student to generate the PDF." });
            return;
        }

        const columnModal = new bootstrap.Modal(document.getElementById('columnSelectionModal'));
        columnModal.show();
        loadColumnOptions(classValue, sessionValue, termValue, selectedIds);
    }

    function loadColumnOptions(classId, sessionId, termId, studentIds) {
        const loader  = document.getElementById('columnSelectionLoader');
        const form    = document.getElementById('columnSelectionForm');
        const saveBtn = document.getElementById('saveColumnSelection');

        loader.style.display  = 'block';
        form.style.display    = 'none';
        saveBtn.disabled      = true;

        window.currentPrintParams = { classId, sessionId, termId, studentIds };

        fetch('{{ route("studentreports.column-options") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ schoolclassid: classId, sessionid: sessionId, termid: termId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                populateColumnOptions(data.columns);
                loader.style.display = 'none';
                form.style.display   = 'block';
                saveBtn.disabled     = false;
            } else {
                Swal.fire({ icon: "error", title: "Error",
                            text: data.message || "Failed to load column options." });
                bootstrap.Modal.getInstance(
                    document.getElementById('columnSelectionModal')).hide();
            }
        })
        .catch(error => {
            console.error('Error loading column options:', error);
            Swal.fire({ icon: "error", title: "Network Error",
                        text: "Failed to load column options. Please try again." });
            bootstrap.Modal.getInstance(
                document.getElementById('columnSelectionModal')).hide();
        });
    }

    function populateColumnOptions(columns) {
        ['studentInfoColumns','assessmentColumns','scoreColumns',
         'gpaColumns','otherColumns'].forEach(id => {
            document.getElementById(id).innerHTML = '';
        });

        function renderCheckboxes(containerId, data, extraClass) {
            if (!data) return;
            const container = document.getElementById(containerId);
            Object.entries(data).forEach(([key, config]) => {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-md-4 col-sm-6 mb-2';
                const subText = config.has_sub_assessments
                    ? '<small class="text-muted d-block">Has sub-assessments</small>' : '';
                colDiv.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input column-checkbox ${extraClass || ''}"
                               type="checkbox" id="col_${key}" data-column="${key}"
                               ${config.default ? 'checked' : ''}>
                        <label class="form-check-label" for="col_${key}" style="font-size:13px;">
                            ${config.label}${subText}
                        </label>
                    </div>`;
                container.appendChild(colDiv);
            });
        }

        renderCheckboxes('studentInfoColumns', columns.student_info);
        renderCheckboxes('assessmentColumns',  columns.assessments,  'assessment-checkbox');
        renderCheckboxes('scoreColumns',       columns.scores);
        renderCheckboxes('gpaColumns',         columns.gpa_metrics,  'gpa-checkbox');
        renderCheckboxes('otherColumns',       columns.other);

        document.getElementById('selectAllAssessments').addEventListener('change', function () {
            document.querySelectorAll('.assessment-checkbox')
                    .forEach(cb => cb.checked = this.checked);
        });
        document.getElementById('selectAllGPAMetrics').addEventListener('change', function () {
            document.querySelectorAll('.gpa-checkbox')
                    .forEach(cb => cb.checked = this.checked);
        });
    }

    // ── Grade basis toggle visuals ──────────────────────────────────────
    function refreshGradeBasisVisuals() {
        const totalChecked = document.getElementById('gradeBasisTotal').checked;
        document.getElementById('gbOptionTotal').classList.toggle('active', totalChecked);
        document.getElementById('gbOptionCumAve').classList.toggle('active', !totalChecked);
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('gradeBasisTotal')?.addEventListener('change', refreshGradeBasisVisuals);
        document.getElementById('gradeBasisCumAve')?.addEventListener('change', refreshGradeBasisVisuals);
    });

    document.getElementById('saveColumnSelection').addEventListener('click', function () {
        const selectedColumns = [];
        document.querySelectorAll('.column-checkbox:checked')
                .forEach(cb => selectedColumns.push(cb.dataset.column));

        if (selectedColumns.length === 0) {
            Swal.fire({ icon: "warning", title: "No Columns Selected",
                        text: "Please select at least one column to include in the PDF." });
            return;
        }

        const params = window.currentPrintParams;
        const gradeBasisEl = document.querySelector('input[name="gradeBasis"]:checked');
        const gradeBasis = gradeBasisEl ? gradeBasisEl.value : 'total';
        bootstrap.Modal.getInstance(document.getElementById('columnSelectionModal')).hide();

        Swal.fire({
            title: 'Generating PDF',
            html: `
                <p><strong>Class:</strong> ${document.getElementById('idclass').options[document.getElementById('idclass').selectedIndex].text}</p>
                <p><strong>Session:</strong> ${document.getElementById('idsession').options[document.getElementById('idsession').selectedIndex].text}</p>
                <p><strong>Term:</strong> ${document.getElementById('idterm').options[document.getElementById('idterm').selectedIndex].text}</p>
                <p><strong>Grade Basis:</strong> ${gradeBasis === 'cum_ave' ? 'Cumulative Average' : 'Term Total'}</p>
                <p><strong>Students Selected:</strong> ${params.studentIds.length}</p>
                <p><strong>Columns Selected:</strong> ${selectedColumns.length}</p>
                <p>Generating PDF… Please wait.</p>`,
            icon: 'info',
            showCancelButton: false,
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("studentreports.exportClassResultsPdf") }}';
        form.target = '_blank';

        const addInput = (name, value) => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = name;
            input.value = value;
            form.appendChild(input);
        };

        addInput('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        addInput('schoolclassid',   params.classId);
        addInput('sessionid',       params.sessionId);
        addInput('termid',          params.termId);
        addInput('response_method', 'inline');
        addInput('grade_basis',     gradeBasis);
        params.studentIds.forEach((id,  i) => addInput(`studentIds[${i}]`,      id));
        selectedColumns.forEach((col,   i) => addInput(`selectedColumns[${i}]`, col));

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(() => Swal.close(), 2000);
    });

    // ── Pagination ──────────────────────────────────────────────────────

    function setupPaginationLinks() {
        document.querySelectorAll('#pagination-container a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                if (this.href && !this.classList.contains('disabled')) loadPage(this.href);
            });
        });
    }

    function loadPage(url) {
        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML =
            '<tr><td colspan="10" class="text-center py-4">' +
            '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading...</td></tr>';

        axios.get(url, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            document.getElementById('studentTableBody').innerHTML =
                response.data.tableBody ||
                '<tr><td colspan="10" class="text-center py-4 text-muted">No students found.</td></tr>';
            document.getElementById('pagination-container').innerHTML =
                response.data.pagination || '';

            const count = response.data.studentCount || '0';
            document.getElementById('studentcount').innerText = count;
            document.getElementById('statTotal').innerText    = count;

            setupPaginationLinks();
            setupCheckboxListeners();
            updateTermSelectVisibility();
            updatePrintButtonVisibility();
        }).catch(function (error) {
            console.error("Page load error:", error);
            tableBody.innerHTML =
                '<tr><td colspan="10" class="text-center text-danger py-4">' +
                'Error loading data. Please try again.</td></tr>';
            Swal.fire({ icon: "error", title: "Error",
                        text: error.response?.data?.message || "Failed to fetch student data." });
        });
    }

    // ── Checkboxes ──────────────────────────────────────────────────────

    function setupCheckboxListeners() {
        const checkAll   = document.getElementById("checkAll");
        const checkboxes = document.querySelectorAll('tbody input[name="chk_child"]');

        if (checkAll) {
            // Remove any old listener before adding a fresh one
            const freshCheckAll = checkAll.cloneNode(true);
            checkAll.parentNode.replaceChild(freshCheckAll, checkAll);

            freshCheckAll.addEventListener("change", function () {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                    cb.closest("tr").classList.toggle("table-active", this.checked);
                });
                updatePrintButtonVisibility();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener("change", function () {
                this.closest("tr").classList.toggle("table-active", this.checked);
                const checkedCount = document.querySelectorAll('tbody input[name="chk_child"]:checked').length;
                const allCount     = document.querySelectorAll('tbody input[name="chk_child"]').length;
                const ca           = document.getElementById("checkAll");
                if (ca) ca.checked = checkedCount === allCount && allCount > 0;
                updatePrintButtonVisibility();
            });
        });
    }

    // ── Boot ────────────────────────────────────────────────────────────

    document.addEventListener("DOMContentLoaded", function () {
        setupCheckboxListeners();
        updateSearchButtonVisibility();

        const classSelect   = document.getElementById("idclass");
        const sessionSelect = document.getElementById("idsession");
        const termSelect    = document.getElementById("idterm");

        classSelect.addEventListener("change", function () {
            termSelect.value = 'ALL';
            updateSearchButtonVisibility();
            resetTable();
        });

        sessionSelect.addEventListener("change", function () {
            termSelect.value = 'ALL';
            updateSearchButtonVisibility();
            resetTable();
        });

        termSelect.addEventListener("change", function () {
            // When term changes, re-run filter then let checkbox state decide print button
            if (this.value !== 'ALL') {
                filterData();
            } else {
                document.getElementById("printAllBtn").style.display = 'none';
                updateSelectionAlert();
            }
        });

        // Keyboard search trigger
        document.getElementById('searchInput').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') filterData();
        });

        const imageModal = document.getElementById('imageViewModal');
        if (imageModal) {
            imageModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('enlargedImage').src =
                    btn.getAttribute('data-image') ||
                    '{{ asset('storage/student_avatars/unnamed.jpg') }}';
            });
        }
    });
</script>
@endsection
