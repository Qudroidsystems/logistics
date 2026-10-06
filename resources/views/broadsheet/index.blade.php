{{--
    broadsheet/index.blade.php (updated)
    Key changes:
      1. Added Promotion column group to the column modal
      2. Updated renderColumnForm to include promotion columns
      3. Fixed web view display issues
--}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --bs-pri:    #1e3a5f;
    --bs-acc:    #2563eb;
    --bs-success:#16a34a;
    --bs-warn:   #d97706;
    --bs-danger: #dc2626;
    --bs-muted:  #6b7280;
    --bs-border: #e2e8f0;
    --bs-bg:     #f8fafc;
    --bs-card:   #ffffff;
    --bs-radius: 12px;
    --bs-shadow: 0 2px 8px rgba(0,0,0,.09);
}

.bsg-hero {
    background: linear-gradient(135deg, var(--bs-pri) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--bs-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.bsg-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.bsg-hero h1 { font-size: 22px; font-weight: 700; color: white; margin: 0 0 6px; }
.bsg-hero p  { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; }

.step-card {
    background: var(--bs-card);
    border: 1px solid var(--bs-border);
    border-radius: var(--bs-radius);
    box-shadow: var(--bs-shadow);
    padding: 22px 24px;
    margin-bottom: 20px;
    position: relative;
}
.step-card .step-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px;
    background: var(--bs-pri); color: white;
    border-radius: 50%; font-weight: 700; font-size: 14px;
    margin-right: 10px; flex-shrink: 0;
}
.step-card .step-title {
    font-size: 15px; font-weight: 600; color: var(--bs-pri);
    display: flex; align-items: center; margin-bottom: 16px;
}
.step-card .step-title .step-subtitle {
    font-size: 12px; font-weight: 400; color: var(--bs-muted); margin-left: 8px;
}
.step-card.active {
    border-color: var(--bs-acc);
    box-shadow: 0 0 0 3px rgba(37,99,235,.1), var(--bs-shadow);
}

.bsg-select {
    border: 1.5px solid var(--bs-border);
    border-radius: 8px; padding: 10px 14px; font-size: 13px;
    width: 100%; background: #fff; color: #374151;
    transition: border-color .15s; appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23374151' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat; background-position: right 12px center; background-size: 14px;
    padding-right: 36px;
}
.bsg-select:focus { outline: none; border-color: var(--bs-acc); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }

.student-preview-card {
    background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
    border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; display: none;
}
.student-preview-card.visible { display: block; }
.preview-stat { text-align: center; padding: 10px; }
.preview-stat .val { font-size: 28px; font-weight: 700; color: var(--bs-pri); display: block; }
.preview-stat .lbl { font-size: 11px; color: var(--bs-muted); text-transform: uppercase; letter-spacing: .5px; }

.col-group { border: 1px solid var(--bs-border); border-radius: 10px; padding: 12px 16px; margin-bottom: 12px; }
.col-group-header {
    font-size: 12px; font-weight: 600; color: var(--bs-pri); margin-bottom: 10px;
    display: flex; align-items: center; justify-content: space-between;
}
.col-item { display: flex; align-items: center; padding: 4px 0; font-size: 12.5px; color: #374151; }
.col-item input[type=checkbox] { width: 15px; height: 15px; margin-right: 8px; accent-color: var(--bs-acc); flex-shrink: 0; cursor: pointer; }

.export-btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 13px 24px; border-radius: 10px; font-size: 14px; font-weight: 600;
    cursor: pointer; transition: transform .15s, box-shadow .15s; border: none; width: 100%;
}
.export-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.15); }
.export-btn.pdf   { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; }
.export-btn.excel { background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); color: white; }
.export-btn i     { font-size: 20px; }

.paper-btn {
    border: 2px solid var(--bs-border); border-radius: 8px; padding: 8px 14px;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s;
    background: white; color: var(--bs-muted);
}
.paper-btn.active { border-color: var(--bs-acc); background: #eff6ff; color: var(--bs-acc); }

#loadingOverlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 9999;
    align-items: center; justify-content: center;
}
#loadingOverlay.active { display: flex; }
.loading-box {
    background: white; border-radius: 16px; padding: 36px 48px;
    text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.loading-box .spinner-grow { width: 3rem; height: 3rem; }

@media (max-width: 768px) {
    .bsg-hero { padding: 20px; }
    .bsg-hero h1 { font-size: 18px; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @foreach(['success','error','warning'] as $bag)
        @if(session($bag))
            <div class="alert alert-{{ $bag === 'error' ? 'danger' : $bag }} alert-dismissible fade show">
                {{ session($bag) }}<button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    <div class="bsg-hero">
        <h1><i class="ri-file-chart-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Select a class, session and term to generate a professional academic broadsheet in PDF, Excel or Web View format.</p>
    </div>

    <div class="row g-4">

        {{-- LEFT --}}
        <div class="col-lg-4">

            <div class="step-card" id="step1Card">
                <div class="step-title">
                    <span class="step-badge">1</span>
                    Select Class &amp; Session
                    <span class="step-subtitle">Required</span>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12.5px;color:#374151;">
                        Class / Arm <span class="text-danger">*</span>
                    </label>
                    <select class="bsg-select" id="classSelect">
                        <option value="">— Select class —</option>
                        @foreach($schoolclasses as $cls)
                            <option value="{{ $cls->id }}">
                                {{ $cls->schoolclass }}{{ $cls->arm ? ' ' . $cls->arm : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12.5px;color:#374151;">
                        Session <span class="text-danger">*</span>
                    </label>
                    <select class="bsg-select" id="sessionSelect">
                        <option value="">— Select session —</option>
                        @foreach($schoolsessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12.5px;color:#374151;">
                        Term <span class="text-danger">*</span>
                    </label>
                    <select class="bsg-select" id="termSelect">
                        <option value="">— Select term —</option>
                        @foreach($schoolterms as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="student-preview-card" id="studentPreview">
                    <div class="row g-0">
                        <div class="col-4 preview-stat">
                            <span class="val" id="previewCount">0</span>
                            <span class="lbl">Students</span>
                        </div>
                        <div class="col-4 preview-stat" style="border-left:1px solid #bfdbfe;border-right:1px solid #bfdbfe;">
                            <span class="val text-success" id="previewSubjects">-</span>
                            <span class="lbl">Subjects</span>
                        </div>
                        <div class="col-4 preview-stat">
                            <span class="val text-warning" id="previewAssessments">-</span>
                            <span class="lbl">Assessments</span>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary w-100 mt-3" id="loadColumnsBtn" disabled
                    onclick="openColumnModal();">
                    <i class="ri-settings-3-line me-2"></i>Configure Columns &amp; Export
                </button>
            </div>


            {{-- All Classes Broadsheet --}}
            <div class="step-card mt-3" id="allClassesCard">
                <div class="step-title">
                    <span class="step-badge" style="background:#7c3aed;">★</span>
                    All Classes Broadsheet
                    <span class="step-subtitle">Combined arms</span>
                </div>
                <p style="font-size:12px;color:#6b7280;margin-bottom:14px;">
                    Generate a single broadsheet combining all arms of a class level (e.g. all JSS 1 arms), sorted alphabetically by student name.
                </p>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12.5px;color:#374151;">
                        Class Group <span class="text-danger">*</span>
                    </label>
                    <select class="bsg-select" id="classGroupSelect">
                        <option value="">— Loading class groups… —</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12.5px;color:#374151;">
                        Session <span class="text-danger">*</span>
                    </label>
                    <select class="bsg-select" id="classGroupSession">
                        <option value="">— Select session —</option>
                        @foreach($schoolsessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12.5px;color:#374151;">
                        Term <span class="text-danger">*</span>
                    </label>
                    <select class="bsg-select" id="classGroupTerm">
                        <option value="">— Select term —</option>
                        @foreach($schoolterms as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="allClassesPreview" class="student-preview-card mb-3" style="display:none;">
                    <div class="row g-0">
                        <div class="col-6 preview-stat">
                            <span class="val" id="allClassesArms">0</span>
                            <span class="lbl">Arms Found</span>
                        </div>
                        <div class="col-6 preview-stat" style="border-left:1px solid #bfdbfe;">
                            <span class="val text-success" id="allClassesStudents">0</span>
                            <span class="lbl">Total Students</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" id="allClassesWebBtn" disabled onclick="doAllClassesExport('web')">
                        <i class="ri-global-line me-1"></i>Web View
                    </button>
                    <button class="btn btn-danger flex-grow-1" id="allClassesPdfBtn" disabled onclick="doAllClassesExport('pdf')">
                        <i class="ri-file-pdf-line me-1"></i>PDF
                    </button>
                </div>
            </div>

            {{-- Best Students --}}
            <div class="step-card">
                <div class="step-title">
                    <span class="step-badge" style="background:#b7791f;"><i class="ri-medal-line" style="font-size:15px;"></i></span>
                    Best Students
                    <span class="step-subtitle">Across classes &amp; arms</span>
                </div>
                <p style="font-size:12px;color:#6b7280;margin-bottom:14px;">
                    Top students overall, per class, per arm and in each subject — for any mix of classes and arms.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('broadsheet.best-students') }}" class="btn btn-warning flex-grow-1">
                        <i class="ri-medal-line me-1"></i>Open report
                    </a>
                    @if(Route::has('broadsheet.ranking.index'))
                        <a href="{{ route('broadsheet.ranking.index') }}" class="btn btn-outline-secondary" title="Ranking settings" aria-label="Ranking settings">
                            <i class="ri-settings-3-line"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="step-card" id="step2Card">
                <div class="step-title">
                    <span class="step-badge">2</span>
                    PDF Settings
                    <span class="step-subtitle">For PDF export only</span>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12.5px;color:#374151;">Paper Size</label>
                    <div class="d-flex gap-2 flex-wrap" id="paperSizeGroup">
                        @foreach(['A4','A3','A2','A1'] as $size)
                            <button class="paper-btn {{ $size === 'A3' ? 'active' : '' }}"
                                data-size="{{ $size }}" onclick="selectPaperSize(this)">{{ $size }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" id="paperSize" value="A3">
                </div>
                <div>
                    <label class="form-label fw-semibold" style="font-size:12.5px;color:#374151;">Orientation</label>
                    <div class="d-flex gap-2">
                        <button class="paper-btn" data-orient="portrait" onclick="selectOrientation(this)">
                            <i class="ri-file-line me-1"></i>Portrait
                        </button>
                        <button class="paper-btn active" data-orient="landscape" onclick="selectOrientation(this)">
                            <i class="ri-file-4-line me-1"></i>Landscape
                        </button>
                    </div>
                    <input type="hidden" id="orientation" value="landscape">
                </div>
            </div>

        </div>

        {{-- RIGHT --}}
        <div class="col-lg-8">

            <div class="step-card">
                <div class="step-title">
                    <span class="step-badge" style="background:#16a34a;"><i class="ri-information-line" style="font-size:15px;"></i></span>
                    What's in the Broadsheet
                </div>
                <div class="row g-3">
                    @php
                    $features = [
                        ['ri-school-line','#2563eb','School Header','School name, address, logo, motto and contact details.'],
                        ['ri-user-line','#16a34a','Student Details','Admission number, full name and optional gender column.'],
                        ['ri-clipboard-line','#d97706','Dynamic Assessments','All assessments (CA1, CA2, Exam…) pulled from class config.'],
                        ['ri-bar-chart-line','#7c3aed','Subject Scores','Total, BF, Cum, Grade, Position and Class Average per subject.'],
                        ['ri-trophy-line','#dc2626','Class Statistics','Per-subject class average, highest, lowest, pass/fail count.'],
                        ['ri-calculator-line','#0891b2','GPA / CGPA','Optional GPA and CGPA columns from current term grade points.'],
                        ['ri-global-line','#1d6fa4','Web View','Interactive scrollable view with smart locate/filter toolbar.'],
                        ['ri-pen-line','#374151','Signatures','Class teacher, HOD, VP and Principal signature rows.'],
                        ['ri-medal-line','#7c3aed','Promotion Status','Student promotion recommendations based on performance.'],
                    ];
                    @endphp
                    @foreach($features as $f)
                    <div class="col-md-6 col-xl-3">
                        <div style="padding:12px;background:#f8fafc;border-radius:10px;border:1px solid var(--bs-border);height:100%;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                <i class="{{ $f[0] }}" style="font-size:18px;color:{{ $f[1] }};"></i>
                                <strong style="font-size:12px;color:#1e3a5f;">{{ $f[2] }}</strong>
                            </div>
                            <p style="font-size:11px;color:#6b7280;margin:0;line-height:1.4;">{{ $f[3] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="step-card">
                <div class="step-title">
                    <span class="step-badge" style="background:#d97706;"><i class="ri-lightbulb-line" style="font-size:15px;"></i></span>
                    Quick Tips
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex gap-3 align-items-start">
                            <span style="font-size:20px;">📐</span>
                            <div>
                                <strong style="font-size:12.5px;color:#1e3a5f;">Paper Size Guide</strong>
                                <p style="font-size:11.5px;color:#6b7280;margin-top:3px;">Use <strong>A3 Landscape</strong> for up to ~8 subjects, <strong>A2</strong> for 8–15 subjects, <strong>A1</strong> for larger classes.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3 align-items-start">
                            <span style="font-size:20px;">🌐</span>
                            <div>
                                <strong style="font-size:12.5px;color:#1e3a5f;">Web View</strong>
                                <p style="font-size:11.5px;color:#6b7280;margin-top:3px;">Interactive view with scroll, zoom and smart locate. Find top students, failures, missing scores and more instantly.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3 align-items-start">
                            <span style="font-size:20px;">🎯</span>
                            <div>
                                <strong style="font-size:12.5px;color:#1e3a5f;">Smart Locate</strong>
                                <p style="font-size:11.5px;color:#6b7280;margin-top:3px;">In web view, use the Locate dropdown to highlight: top 5/10 students, failures, below-average, missing scores, by subject and more.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3 align-items-start">
                            <span style="font-size:20px;">🔒</span>
                            <div>
                                <strong style="font-size:12.5px;color:#1e3a5f;">WAEC Standard</strong>
                                <p style="font-size:11.5px;color:#6b7280;margin-top:3px;">Grades follow the WAEC/NECO 9-point scale (A1–F9) with grade key printed at the top of every broadsheet.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- COLUMN MODAL --}}
    <div class="modal fade" id="columnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-0" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);">
                    <div>
                        <h5 class="modal-title text-white fw-bold">
                            <i class="ri-layout-column-line me-2"></i>Configure Broadsheet Columns
                        </h5>
                        <p class="text-white-50 small mb-0">Choose which columns appear, then select export format.</p>
                    </div>
                    <button class="btn-close btn-close-white ms-3" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div id="colModalLoader" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;"></div>
                        <p class="text-muted">Loading column options…</p>
                    </div>

                    <div id="colModalForm" style="display:none;">

                        <div class="d-flex gap-2 flex-wrap mb-4" id="colSummaryPills">
                            <span class="badge bg-primary-subtle text-primary px-3 py-2" id="pillClass">Class: —</span>
                            <span class="badge bg-success-subtle text-success px-3 py-2" id="pillSession">Session: —</span>
                            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2" id="pillTerm">Term: —</span>
                            <span class="badge bg-info-subtle text-info px-3 py-2" id="pillStudents">0 students</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="col-group">
                                    <div class="col-group-header">
                                        <span>Student Info</span>
                                        <a href="#" class="text-primary text-decoration-none" style="font-size:11px;" onclick="toggleGroup('studentInfoCols');return false;">Toggle</a>
                                    </div>
                                    <div id="studentInfoCols"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="col-group">
                                    <div class="col-group-header">
                                        <span>Assessments</span>
                                        <a href="#" class="text-primary text-decoration-none" style="font-size:11px;" onclick="toggleGroup('assessmentCols');return false;">Toggle</a>
                                    </div>
                                    <div id="assessmentCols"><p class="text-muted small">Loading…</p></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="col-group">
                                    <div class="col-group-header">
                                        <span>Scores &amp; Metrics</span>
                                        <a href="#" class="text-primary text-decoration-none" style="font-size:11px;" onclick="toggleGroup('scoreCols');return false;">Toggle</a>
                                    </div>
                                    <div id="scoreCols"></div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="col-group">
                                    <div class="col-group-header">
                                        <span>GPA</span>
                                        <a href="#" class="text-primary text-decoration-none" style="font-size:11px;" onclick="toggleGroup('gpaCols');return false;">Toggle</a>
                                    </div>
                                    <div id="gpaCols"></div>
                                </div>
                            </div>
                        </div>

                        {{-- NEW: Promotion Column Group --}}
                        <div class="row g-3 mt-1">
                            <div class="col-md-12">
                                <div class="col-group" style="border-color:#7c3aed;">
                                    <div class="col-group-header" style="color:#5b21b6;">
                                        <span>🎓 Promotion Columns</span>
                                        <a href="#" class="text-decoration-none" style="color:#7c3aed;font-size:11px;" onclick="toggleGroup('promoCols');return false;">Toggle</a>
                                    </div>
                                    <div id="promoCols"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid var(--bs-border);">
                            <h6 class="fw-bold mb-3" style="color:#1e3a5f;"><i class="ri-download-line me-2"></i>Export Format</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <button class="export-btn" style="background:linear-gradient(135deg,#1d6fa4,#2a8fc9);color:white;" onclick="doExport('web');">
                                        <i class="ri-global-line" style="font-size:20px;"></i>
                                        <div class="text-start">
                                            <div>Open Web View</div>
                                            <div style="font-size:11px;opacity:.8;font-weight:400;">Interactive · Scroll · Zoom · Locate</div>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button class="export-btn pdf" onclick="doExport('pdf');">
                                        <i class="ri-file-pdf-line"></i>
                                        <div class="text-start">
                                            <div>Download PDF</div>
                                            <div style="font-size:11px;opacity:.8;font-weight:400;"><span id="pdfSizeLabel">A3 · Landscape</span></div>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button class="export-btn excel" onclick="doExport('excel');">
                                        <i class="ri-file-excel-line"></i>
                                        <div class="text-start">
                                            <div>Download Excel</div>
                                            <div style="font-size:11px;opacity:.8;font-weight:400;">.xlsx · All selected columns</div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <small class="text-muted me-auto">
                        <i class="ri-information-line me-1"></i>
                        The broadsheet includes ALL students in the selected class and session.
                    </small>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden export form --}}
    <form id="exportForm" method="POST" target="_blank" style="display:none;">
        @csrf
        <input type="hidden" name="schoolclassid" id="ef_class">
        <input type="hidden" name="sessionid"     id="ef_session">
        <input type="hidden" name="termid"        id="ef_term">
        <input type="hidden" name="paper_size"    id="ef_paper">
        <input type="hidden" name="orientation"   id="ef_orient">
        <div id="ef_columns"></div>
    </form>

</div>
</div>
</div>

<div id="loadingOverlay">
    <div class="loading-box">
        <div class="spinner-grow text-primary mb-3"></div>
        <div class="fw-bold" style="color:#1e3a5f;font-size:15px;" id="loadingMsg">Generating Broadsheet…</div>
        <p class="text-muted small mt-1 mb-0">This may take a few seconds. Please wait.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const ROUTES = {
    columnOptions  : '{{ route("broadsheet.column-options") }}',
    studentPreview : '{{ route("broadsheet.student-preview") }}',
    exportPdf      : '{{ route("broadsheet.export.pdf") }}',
    exportExcel    : '{{ route("broadsheet.export.excel") }}',
    webView        : '{{ route("broadsheet.web-view") }}',
    allClassesWeb  : '{{ route("broadsheet.all-classes.web") }}',
    allClassesPdf  : '{{ route("broadsheet.all-classes.pdf") }}',
    classGroups    : '{{ route("broadsheet.class-groups") }}',
};

let columnData   = null;
let studentCount = 0;
let debounceTimer= null;

/* ── Paper size & orientation ── */
function selectPaperSize(btn) {
    document.querySelectorAll('[data-size]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('paperSize').value = btn.dataset.size;
    updatePdfLabel();
}
function selectOrientation(btn) {
    document.querySelectorAll('[data-orient]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('orientation').value = btn.dataset.orient;
    updatePdfLabel();
}
function updatePdfLabel() {
    const size   = document.getElementById('paperSize').value;
    const orient = document.getElementById('orientation').value;
    const lbl    = document.getElementById('pdfSizeLabel');
    if (lbl) lbl.textContent = size + ' · ' + (orient === 'landscape' ? 'Landscape' : 'Portrait');
}

/* ── Auto-refresh preview on select change ── */
['classSelect','sessionSelect','termSelect'].forEach(id => {
    document.getElementById(id).addEventListener('change', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkAndLoadPreview, 300);
    });
});

function checkAndLoadPreview() {
    const classId   = document.getElementById('classSelect').value;
    const sessionId = document.getElementById('sessionSelect').value;
    const termId    = document.getElementById('termSelect').value;
    const loadBtn   = document.getElementById('loadColumnsBtn');

    if (!classId || !sessionId || !termId) {
        document.getElementById('studentPreview').classList.remove('visible');
        loadBtn.disabled = true;
        document.getElementById('previewCount').textContent       = '0';
        document.getElementById('previewSubjects').textContent    = '-';
        document.getElementById('previewAssessments').textContent = '-';
        return;
    }

    fetch(ROUTES.studentPreview, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body   : JSON.stringify({ schoolclassid: classId, sessionid: sessionId, termid: termId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            studentCount = data.count;
            document.getElementById('previewCount').textContent       = data.count;
            document.getElementById('previewSubjects').textContent    = data.subject_count    ?? '-';
            document.getElementById('previewAssessments').textContent = data.assessment_count ?? '-';
            document.getElementById('studentPreview').classList.add('visible');
            loadBtn.disabled = data.count === 0;
        }
    })
    .catch(err => {
        console.error('Preview error:', err);
    });
}

/* ── Open column modal ── */
function openColumnModal() {
    const classId   = document.getElementById('classSelect').value;
    const sessionId = document.getElementById('sessionSelect').value;
    const termId    = document.getElementById('termSelect').value;

    if (!classId || !sessionId || !termId) {
        Swal.fire({ icon: 'warning', title: 'Incomplete Selection', text: 'Please select class, session and term first.' });
        return;
    }

    const classText   = document.getElementById('classSelect').selectedOptions[0]?.text ?? '';
    const sessionText = document.getElementById('sessionSelect').selectedOptions[0]?.text ?? '';
    const termText    = document.getElementById('termSelect').selectedOptions[0]?.text ?? '';

    document.getElementById('pillClass').textContent    = 'Class: ' + classText;
    document.getElementById('pillSession').textContent  = sessionText;
    document.getElementById('pillTerm').textContent     = termText;
    document.getElementById('pillStudents').textContent = studentCount + ' students';

    const modal = new bootstrap.Modal(document.getElementById('columnModal'));
    modal.show();

    document.getElementById('colModalLoader').style.display = 'block';
    document.getElementById('colModalForm').style.display   = 'none';

    fetch(ROUTES.columnOptions, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body   : JSON.stringify({ schoolclassid: classId, sessionid: sessionId, termid: termId }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message || 'Failed to load columns');
        columnData = data.columns;
        renderColumnForm(data.columns);
        document.getElementById('colModalLoader').style.display = 'none';
        document.getElementById('colModalForm').style.display   = 'block';
        updatePdfLabel();

        const asmCount = Object.keys(data.columns.assessments || {}).length;
        document.getElementById('previewAssessments').textContent = asmCount;
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        bootstrap.Modal.getInstance(document.getElementById('columnModal'))?.hide();
    });
}

/* ── Render column checkboxes (UPDATED with promotion group) ── */
function renderColumnForm(columns) {
    renderGroup('studentInfoCols', columns.student_info ?? {});
    renderGroup('assessmentCols',  columns.assessments  ?? {});
    renderGroup('scoreCols',       columns.scores       ?? {});
    renderGroup('gpaCols',         columns.gpa_metrics  ?? {});

    // NEW: Render promotion columns
    if (columns.promotion) {
        renderGroup('promoCols', columns.promotion);
    }
}

function renderGroup(containerId, items) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    Object.entries(items).forEach(([key, cfg]) => {
        const div = document.createElement('div');
        div.className = 'col-item';
        div.innerHTML = `
            <input type="checkbox" class="col-checkbox" data-key="${key}" id="chk_${key}" ${cfg.default ? 'checked' : ''}>
            <label for="chk_${key}" style="cursor:pointer;">
                ${cfg.label}
                ${cfg.max_score ? '<small style="color:#9ca3af;"> (' + cfg.max_score + ')</small>' : ''}
                ${cfg.note ? '<small style="color:#f59e0b;"> (' + cfg.note + ')</small>' : ''}
            </label>`;
        container.appendChild(div);
    });
}

function toggleGroup(containerId) {
    const checkboxes = document.querySelectorAll(`#${containerId} .col-checkbox`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}

/* ══════════════════════════════════════════
   DO EXPORT
   type = 'pdf' | 'excel' | 'web'
══════════════════════════════════════════ */
function doExport(type) {
    const classId   = document.getElementById('classSelect').value;
    const sessionId = document.getElementById('sessionSelect').value;
    const termId    = document.getElementById('termSelect').value;

    if (!classId || !sessionId || !termId) {
        Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Please select class, session and term.' });
        return;
    }

    const selectedCols = Array.from(document.querySelectorAll('.col-checkbox:checked')).map(cb => cb.dataset.key);

    if (selectedCols.length === 0) {
        Swal.fire({ icon: 'warning', title: 'No Columns', text: 'Please select at least one column.' });
        return;
    }

    const form = document.getElementById('exportForm');

    // Route map
    const actionMap = {
        pdf   : ROUTES.exportPdf,
        excel : ROUTES.exportExcel,
        web   : ROUTES.webView,
    };
    form.action = actionMap[type];
    form.target = '_blank';   // always open in new tab

    document.getElementById('ef_class').value   = classId;
    document.getElementById('ef_session').value = sessionId;
    document.getElementById('ef_term').value    = termId;
    document.getElementById('ef_paper').value   = document.getElementById('paperSize').value;
    document.getElementById('ef_orient').value  = document.getElementById('orientation').value;

    // Clear a classgroup left over from an earlier All Classes export
    const cgLeft = document.getElementById('ef_classgroup');
    if (cgLeft) cgLeft.value = '';

    // Selected columns
    const colDiv = document.getElementById('ef_columns');
    colDiv.innerHTML = '';
    selectedCols.forEach((col, i) => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = `selectedColumns[${i}]`;
        inp.value = col;
        colDiv.appendChild(inp);
    });

    // Loading overlay (only for pdf/excel, web view opens new tab quickly)
    if (type !== 'web') {
        const overlay = document.getElementById('loadingOverlay');
        document.getElementById('loadingMsg').textContent =
            type === 'pdf' ? 'Generating PDF Broadsheet…' : 'Generating Excel Broadsheet…';
        overlay.classList.add('active');
        setTimeout(() => overlay.classList.remove('active'), 10000);
    }

    form.submit();
    bootstrap.Modal.getInstance(document.getElementById('columnModal'))?.hide();
}

// ── All Classes Broadsheet ────────────────────────────────────────────
// Load class groups on page load
fetch(ROUTES.classGroups)
    .then(r => r.json())
    .then(data => {
        const sel = document.getElementById('classGroupSelect');
        sel.innerHTML = '<option value="">— Select class group —</option>';
        (data.groups || []).forEach(g => {
            const opt = document.createElement('option');
            opt.value = g;
            opt.textContent = g;
            sel.appendChild(opt);
        });
    });

['classGroupSelect','classGroupSession','classGroupTerm'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', checkAllClassesPreview);
});

function checkAllClassesPreview() {
    const group     = document.getElementById('classGroupSelect').value;
    const sessionId = document.getElementById('classGroupSession').value;
    const termId    = document.getElementById('classGroupTerm').value;
    const webBtn    = document.getElementById('allClassesWebBtn');
    const pdfBtn    = document.getElementById('allClassesPdfBtn');
    const preview   = document.getElementById('allClassesPreview');

    if (!group || !sessionId || !termId) {
        if (webBtn) webBtn.disabled = true;
        if (pdfBtn) pdfBtn.disabled = true;
        if (preview) preview.style.display = 'none';
        return;
    }

    // Fetch a quick preview: count arms and students
    fetch(ROUTES.studentPreview, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body   : JSON.stringify({ classgroup: group, sessionid: sessionId, termid: termId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('allClassesArms').textContent     = data.arms_count    ?? '—';
            document.getElementById('allClassesStudents').textContent = data.count         ?? '—';
            if (preview) preview.style.display = 'block';
            if (webBtn) webBtn.disabled = false;
            if (pdfBtn) pdfBtn.disabled = false;
        }
    })
    .catch(() => {});
}

function doAllClassesExport(type) {
    const group     = document.getElementById('classGroupSelect').value;
    const sessionId = document.getElementById('classGroupSession').value;
    const termId    = document.getElementById('classGroupTerm').value;

    if (!group || !sessionId || !termId) {
        Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Please select class group, session and term.' });
        return;
    }

    const form   = document.getElementById('exportForm');
    const routes = {
        web : ROUTES.allClassesWeb,
        pdf : ROUTES.allClassesPdf,
    };

    form.action = routes[type];
    form.target = '_blank';

    // Clear class-specific fields
    document.getElementById('ef_class').value   = '';
    document.getElementById('ef_session').value = sessionId;
    document.getElementById('ef_term').value    = termId;
    document.getElementById('ef_paper').value   = document.getElementById('paperSize').value;
    document.getElementById('ef_orient').value  = document.getElementById('orientation').value;

    // Add classgroup hidden input
    let cgInput = document.getElementById('ef_classgroup');
    if (!cgInput) {
        cgInput = document.createElement('input');
        cgInput.type = 'hidden';
        cgInput.id   = 'ef_classgroup';
        cgInput.name = 'classgroup';
        document.getElementById('exportForm').appendChild(cgInput);
    }
    cgInput.value = group;

    // Default all columns selected for combined view
    const colDiv = document.getElementById('ef_columns');
    colDiv.innerHTML = '';

    if (type !== 'web') {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            document.getElementById('loadingMsg').textContent = 'Generating Combined Broadsheet…';
            overlay.classList.add('active');
            setTimeout(() => overlay.classList.remove('active'), 15000);
        }
    }

    form.submit();
}
</script>
@endsection
