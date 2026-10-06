{{-- resources/views/compulsorysubjectclass/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --cs-primary:  #1e3a5f;
    --cs-accent:   #2563eb;
    --cs-success:  #16a34a;
    --cs-warning:  #d97706;
    --cs-danger:   #dc2626;
    --cs-muted:    #6b7280;
    --cs-border:   #e2e8f0;
    --cs-bg:       #f8fafc;
    --cs-radius:   12px;
    --cs-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.cs-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #7c3aed 60%, #4f46e5 100%);
    border-radius: var(--cs-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.cs-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.cs-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.cs-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.cs-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--cs-border);
    border-radius:var(--cs-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--cs-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--cs-primary); }
.stat-card .stat-label { font-size:12px; color:var(--cs-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.cs-table th {
    background:var(--cs-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.cs-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--cs-border); font-size:13px;
}
.cs-table tr:hover td { background:#f5f3ff; }

/* ── Badges ──────────────────────────────────────────────── */
.cs-badge {
    display:inline-flex; align-items:center;
    padding:3px 9px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.cs-badge-session { background:#ccfbf1; color:#0f766e; }
.cs-badge-all-terms { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.cs-badge-grade { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.cs-badge-pass-avg { background:#dbeafe; color:#2563eb; border:1px solid #bfdbfe; }
.cs-badge-term-first  { background:#dcfce7; color:#16a34a; }
.cs-badge-term-second { background:#dbeafe; color:#2563eb; }
.cs-badge-term-third  { background:#fee2e2; color:#dc2626; }
.cs-badge-term-other  { background:#f3f4f6; color:#6b7280; }

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--cs-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--cs-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--cs-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--cs-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--cs-accent) !important;
    border-color:var(--cs-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.cs-modal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, #1e3a5f 0%, #7c3aed 100%);
    padding:22px 28px; position:relative; overflow:hidden;
}
.modal-hero-bar::before {
    content:''; position:absolute; top:-30px; right:-30px;
    width:120px; height:120px; background:rgba(255,255,255,.07); border-radius:50%;
}
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select {
    border:1.5px solid var(--cs-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--cs-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Checkbox group ──────────────────────────────────────── */
.checkbox-scroll {
    max-height:300px; overflow-y:auto;
    border:1.5px solid var(--cs-border); border-radius:8px;
    padding:10px 14px; background:#fafbfc;
}
.checkbox-scroll .form-check { padding:5px 0; border-bottom:1px solid #f0f0f0; }
.checkbox-scroll .form-check:last-child { border-bottom:none; }
.checkbox-scroll .form-check-label { font-size:13px; cursor:pointer; }
.checkbox-scroll .form-check-input:checked {
    background-color:var(--cs-accent); border-color:var(--cs-accent);
}

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#cs-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#cs-page-loader.active { opacity:1; visibility:visible; }

.cs-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22);
    min-width:220px;
}
.cs-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0;
    border-top-color:var(--cs-accent);
    border-radius:50%;
    animation:cs-spin .75s linear infinite;
}
@keyframes cs-spin { to { transform:rotate(360deg); } }

.cs-loader-label {
    font-size:14px; font-weight:600;
    color:var(--cs-primary); margin-bottom:12px;
}
.cs-progress-wrap {
    width:160px; height:5px;
    background:#e2e8f0; border-radius:99px; overflow:hidden;
    margin:0 auto;
}
.cs-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--cs-accent), #7c3aed);
    border-radius:99px;
    transition:width .35s ease;
}

/* ── Modal body loading overlay ──────────────────────────── */
.modal-body-loader {
    position:absolute; inset:0; z-index:10;
    background:rgba(255,255,255,.82);
    backdrop-filter:blur(2px);
    display:flex; align-items:center; justify-content:center;
    border-radius:0 0 16px 16px;
    opacity:0; visibility:hidden;
    transition:opacity .18s, visibility .18s;
}
.modal-body-loader.active { opacity:1; visibility:visible; }
.modal-body-loader .inner {
    display:flex; flex-direction:column;
    align-items:center; gap:10px;
}
.modal-body-loader .mbl-spinner {
    width:36px; height:36px;
    border:3px solid #e2e8f0;
    border-top-color:var(--cs-accent);
    border-radius:50%;
    animation:cs-spin .7s linear infinite;
}
.modal-body-loader .mbl-text {
    font-size:13px; font-weight:600; color:var(--cs-primary);
}

/* ── Toast notifications ──────────────────────────────────── */
#cs-toast-stack {
    position:fixed; bottom:24px; right:24px;
    z-index:10000; display:flex;
    flex-direction:column-reverse; gap:10px;
    pointer-events:none;
}
.cs-toast {
    pointer-events:all;
    background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--cs-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.cs-toast.show { transform:translateX(0); }
.cs-toast.cs-toast-success { border-left-color:var(--cs-success); }
.cs-toast.cs-toast-error   { border-left-color:var(--cs-danger);  }
.cs-toast.cs-toast-warning { border-left-color:var(--cs-warning); }
.cs-toast .cs-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.cs-toast-success .cs-toast-icon { color:var(--cs-success); }
.cs-toast-error   .cs-toast-icon { color:var(--cs-danger);  }
.cs-toast-warning .cs-toast-icon { color:var(--cs-warning); }
.cs-toast .cs-toast-body { flex:1; }
.cs-toast .cs-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.cs-toast .cs-toast-msg   { font-size:12px; color:var(--cs-muted); line-height:1.4; }
.cs-toast .cs-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--cs-muted); font-size:16px; line-height:1;
    padding:0; flex-shrink:0;
}

/* ── Button loading state ────────────────────────────────── */
.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:'';
    position:absolute; inset:0;
    margin:auto; width:16px; height:16px;
    border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff;
    border-radius:50%;
    animation:cs-spin .65s linear infinite;
}
.btn-loading.btn-outline-secondary::after,
.btn-loading.btn-outline-danger::after { border-top-color:currentColor; }
.btn-loading.btn-light::after { border-top-color:#374151; }

/* ── Pass average card ───────────────────────────────────── */
.pass-avg-card {
    background:#fff; border:1px solid var(--cs-border);
    border-radius:10px; padding:14px 16px; height:100%;
}
.pac-label {
    font-size:12px; font-weight:700; color:var(--cs-primary);
    margin-bottom:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.pac-input-row {
    display:flex; align-items:center; gap:6px;
}
.pac-input {
    width:80px!important; flex-shrink:0;
    font-size:13px; padding:6px 8px!important;
    border-radius:8px; border:1.5px solid var(--cs-border)!important;
}
.pac-input:focus {
    border-color:var(--cs-accent)!important;
    outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1)!important;
}
.pac-unit {
    font-size:13px; color:var(--cs-muted); font-weight:600; flex-shrink:0;
}
.pac-save-btn {
    flex-shrink:0; padding:5px 10px!important; font-size:12px!important;
}
.pac-status {
    margin-top:6px; min-height:20px; font-size:11px;
}
.pac-badge-set {
    background:#fef3c7; color:#92400e;
    border:1px solid #fde68a; padding:2px 8px;
    border-radius:20px; font-size:11px; font-weight:700; display:inline-block;
}
.pac-badge-none { color:var(--cs-muted); }

/* ── Info banner ──────────────────────────────────────────── */
.info-banner {
    background:#eff6ff; border:1px solid #bfdbfe;
    border-radius:10px; padding:12px 16px; margin-bottom:20px;
    display:flex; align-items:center; gap:10px;
}
.info-banner i { font-size:20px; color:#2563eb; }
.info-banner .text { font-size:13px; color:#1e40af; }
.info-banner .text strong { display:block; margin-bottom:4px; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="cs-page-loader">
    <div class="cs-loader-card">
        <div class="cs-loader-spinner"></div>
        <div class="cs-loader-label" id="cs-loader-label">Processing…</div>
        <div class="cs-progress-wrap">
            <div class="cs-progress-bar" id="cs-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast notification stack ═══ --}}
<div id="cs-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="cs-hero">
        <h1><i class="ri-book-open-line me-2"></i>Compulsory Subject Class Management</h1>
        <p>Manage subjects that students must pass for promotion to the next class level.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-book-open-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value text-primary" id="statClasses">—</div>
                <div class="stat-label">Total Classes</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-line"></i></div>
                <div class="stat-value text-success" id="statSessions">—</div>
                <div class="stat-label">Sessions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-star-line"></i></div>
                <div class="stat-value text-warning" id="statClassesWithRules">—</div>
                <div class="stat-label">Classes with Rules</div>
            </div>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="info-banner">
        <i class="ri-information-line"></i>
        <div class="text">
            <strong>About Compulsory Subjects</strong>
            These are core subjects that students MUST pass to be promoted. Set a minimum passing grade per subject and configure the minimum overall average per class below.
        </div>
    </div>

    {{-- Promotion Pass Average Panel --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h5 class="mb-0 fw-semibold" style="color:var(--cs-primary)">
                        <i class="ri-percent-line me-2"></i>Promotion Pass Average — Per Class
                    </h5>
                    <div class="small text-muted mt-1">Minimum overall % a student must achieve to be promoted. Leave blank to disable the threshold.</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach ($schoolclasses as $cls)
                    @php
                        $existing = $classPassAverages->get($cls->id);
                        $current  = $existing ? $existing->promotion_pass_average : null;
                    @endphp
                    <div class="col-md-3 mb-3">
                        <div class="pass-avg-card">
                            <div class="pac-label" title="{{ $cls->schoolclass }}{{ $cls->arm ? ' ('.$cls->arm.')' : '' }}">
                                {{ $cls->schoolclass }}{{ $cls->arm ? ' ('.$cls->arm.')' : '' }}
                            </div>
                            <div class="pac-input-row">
                                <input type="number"
                                       class="form-control pac-input"
                                       id="pac_{{ $cls->id }}"
                                       min="0" max="100" step="0.5"
                                       placeholder="e.g. 40"
                                       value="{{ $current !== null ? number_format((float)$current, 1) : '' }}">
                                <span class="pac-unit">%</span>
                                <button type="button"
                                        class="btn btn-primary btn-sm pac-save-btn"
                                        data-classid="{{ $cls->id }}"
                                        data-classname="{{ $cls->schoolclass }}{{ $cls->arm ? ' ('.$cls->arm.')' : '' }}">
                                    <i class="ri-save-line"></i>
                                </button>
                            </div>
                            <div class="pac-status" id="pac_status_{{ $cls->id }}">
                                @if($current !== null)
                                    <span class="pac-badge-set">
                                        <i class="ri-checkbox-circle-line me-1"></i>{{ number_format((float)$current, 1) }}% set
                                    </span>
                                @else
                                    <span class="pac-badge-none">No threshold set</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--cs-primary)">
                    <i class="ri-list-check me-2"></i>Compulsory Subject Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create compulsory-subject')
                    <button class="btn btn-primary" id="createCsBtn">
                        <i class="ri-add-line me-1"></i>Add Compulsory Subject
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> record(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table cs-table w-100 mb-0" id="compulsoryTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Session</th>
                            <th>Min Grade</th>
                            <th>Promotion Avg</th>
                            <th>Last Updated</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- ═══════════════════════ ADD MODAL ═══════════════════════ --}}
<div class="modal fade cs-modal" id="addModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Add Compulsory Subject</h5>
            </div>
            <form id="addForm" autocomplete="off">
                @csrf
                <div class="modal-body-loader" id="add-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="add-modal-loader-text">Saving…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select id="add-classid" class="form-select" required>
                                <option value="">— Select Class —</option>
                                @foreach ($schoolclasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->schoolclass }}{{ $class->arm ? ' ('.$class->arm.')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Term</label>
                            <select id="add-termid" class="form-select">
                                <option value="">— All Terms —</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}">{{ $term->term }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Leave blank to apply to all terms</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Session</label>
                            <select id="add-sessionid" class="form-select">
                                <option value="">— Any Session —</option>
                                @foreach ($sessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0">Select Subjects <span class="text-danger">*</span></label>
                            <span class="small text-muted" id="add-gradeScaleInfo"></span>
                        </div>
                        <div class="checkbox-scroll" id="add-subjectList">
                            <div class="text-center text-muted py-4">
                                <i class="ri-arrow-up-line"></i> Select a class above to load its subjects.
                            </div>
                        </div>
                        <div class="form-text mt-2">Per subject, optionally pick the minimum grade the student must achieve.</div>
                    </div>

                    <div class="alert alert-danger d-none" id="add-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Compulsory Subject(s)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade cs-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Compulsory Subject</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-id">
                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select id="edit-classid" class="form-select" required>
                                <option value="">— Select Class —</option>
                                @foreach ($schoolclasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->schoolclass }}{{ $class->arm ? ' ('.$class->arm.')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <select id="edit-subjectid" class="form-select" required>
                                <option value="">— Select Subject —</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Term</label>
                            <select id="edit-termid" class="form-select">
                                <option value="">— All Terms —</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}">{{ $term->term }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Session</label>
                            <select id="edit-sessionid" class="form-select">
                                <option value="">— Any Session —</option>
                                @foreach ($sessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Minimum Passing Grade</label>
                        <select id="edit-minGrade" class="form-select">
                            <option value="">— No minimum set —</option>
                        </select>
                        <div class="form-text">Grade scale is determined by the class category.</div>
                    </div>

                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ DELETE MODAL ════════════════════ --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Removal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Remove <strong id="delete-item-title"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                    <i class="ri-delete-bin-line me-1"></i><span class="btn-text">Delete</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    const CSRF = $('meta[name="csrf-token"]').attr('content');
    let deleteId = null;

    // =========================================================================
    // LOADING HELPERS
    // =========================================================================

    const PageLoader = {
        _prog: 0, _timer: null,
        show(label = 'Processing…') {
            $('#cs-loader-label').text(label);
            $('#cs-progress-bar').css('width', '0%');
            $('#cs-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#cs-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#cs-progress-bar').css('width', '100%');
            setTimeout(() => $('#cs-page-loader').removeClass('active'), 350);
        },
    };

    function showModalLoader(id, text = 'Processing…') {
        $(`#${id}-modal-loader-text`).text(text);
        $(`#${id}-modal-loader`).addClass('active');
    }
    function hideModalLoader(id) {
        $(`#${id}-modal-loader`).removeClass('active');
    }

    function btnLoad(selector, loadingText = '') {
        const $btn = $(selector);
        // Store original HTML only if not already stored
        if (!$btn.data('original-html')) {
            $btn.data('original-html', $btn.html());
        }
        $btn.prop('disabled', true).addClass('btn-loading');
        if (loadingText) {
            $btn.html(`<span class="btn-text">${loadingText}</span>`);
        }
        return $btn;
    }

    function btnReset(selector) {
        const $btn = $(selector);
        const orig = $btn.data('original-html');
        // Only reset if we have the original HTML
        if (orig) {
            $btn.html(orig);
            $btn.removeData('original-html');
        }
        $btn.prop('disabled', false).removeClass('btn-loading');
    }

    function toast(type, title, msg, duration = 4000) {
        const icons = {
            success: 'ri-checkbox-circle-fill',
            error:   'ri-close-circle-fill',
            warning: 'ri-alert-fill',
            info:    'ri-information-fill',
        };
        const id  = 'cs-toast-' + Date.now();
        const $el = $(`
            <div class="cs-toast cs-toast-${type}" id="${id}">
                <span class="cs-toast-icon"><i class="${icons[type] || icons.info}"></i></span>
                <div class="cs-toast-body">
                    <div class="cs-toast-title">${title}</div>
                    ${msg ? `<div class="cs-toast-msg">${msg}</div>` : ''}
                </div>
                <button class="cs-toast-close" onclick="$('#${id}').remove()">×</button>
            </div>
        `);
        $('#cs-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        if (duration > 0) {
            setTimeout(() => {
                $el.removeClass('show');
                setTimeout(() => $el.remove(), 350);
            }, duration);
        }
    }

    function showError(selector, msg) {
        $(selector).removeClass('d-none').html(
            `<i class="ri-error-warning-line me-1"></i>${msg}`
        );
    }

    // =========================================================================
    // DATATABLE (server-side)
    // =========================================================================

    var table = $('#compulsoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("compulsorysubjectclass.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load records. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'subject_info', orderable: false },
            { data: 'class_info', orderable: false },
            { data: 'term_info', orderable: false },
            { data: 'session_info', orderable: false },
            { data: 'min_grade_info', orderable: false },
            { data: 'pass_avg_info', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing:      '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search:          '',
            searchPlaceholder: 'Search records…',
            lengthMenu:      'Show _MENU_ entries',
            info:            'Showing _START_–_END_ of _TOTAL_ records',
            infoEmpty:       'No records found',
            zeroRecords:     'No matching records',
            emptyTable:      'No compulsory subject assignments yet',
        },
        order: [[1, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            bindCheckboxes();
            $('#totalBadge').text(this.api().page.info().recordsTotal);
        },
    });

    // =========================================================================
    // STATS
    // =========================================================================

    function loadStats() {
        $.get('{{ route("compulsorysubjectclass.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statClasses').text(data.stats.total_classes);
                $('#statSessions').text(data.stats.total_sessions);
                $('#statClassesWithRules').text(data.stats.classes_with_rules);
            }
        }).fail(function() {
            $('#statTotal, #statClasses, #statSessions, #statClassesWithRules').text('—');
        });
    }
    loadStats();

    // =========================================================================
    // CHECKBOXES & BULK BAR
    // =========================================================================

    function bindCheckboxes() {
        $('.row-checkbox').off('change').on('change', updateBulkBar);
    }
    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkBar();
    });
    function updateBulkBar() {
        var count = $('.row-checkbox:checked').length;
        $('#bulkBar').toggleClass('show', count > 0);
        $('#bulkCount').text(count);
        $('#bulkDeleteBtn').toggleClass('d-none', count === 0);
        if (count === 0) $('#selectAll').prop('checked', false);
    }

    // =========================================================================
    // PASS AVERAGE SAVE
    // =========================================================================

    $(document).on('click', '.pac-save-btn', function () {
        const classId   = $(this).data('classid');
        const className = $(this).data('classname') || 'this class';
        const $input    = $('#pac_' + classId);
        const $status   = $('#pac_status_' + classId);
        const val       = $input.val().trim();
        const btn       = $(this);

        if (val !== '' && (isNaN(val) || parseFloat(val) < 0 || parseFloat(val) > 100)) {
            Swal.fire('Invalid Input', 'Please enter a value between 0 and 100, or leave blank to disable.', 'warning');
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '{{ route("compulsorysubjectclass.updatePassAverage") }}',
            type: 'POST',
            data: {
                schoolclassid: classId,
                promotion_pass_average: val !== '' ? parseFloat(val) : null,
                _token: CSRF,
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    if (res.saved_value !== null && res.saved_value !== undefined) {
                        $input.val(res.saved_value.toFixed(1));
                        $status.html(`<span class="pac-badge-set"><i class="ri-checkbox-circle-line me-1"></i>${res.saved_value.toFixed(1)}% set</span>`);
                    } else {
                        $input.val('');
                        $status.html('<span class="pac-badge-none">No threshold set</span>');
                    }
                    toast('success', 'Saved!', res.message);
                } else {
                    toast('error', 'Error', res.message || 'Failed to update.');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'An error occurred.';
                toast('error', 'Error', msg);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ri-save-line"></i>');
            }
        });
    });

    $(document).on('keydown', '.pac-input', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).closest('.pass-avg-card').find('.pac-save-btn').trigger('click');
        }
    });

    // =========================================================================
    // LOAD SUBJECTS FOR ADD
    // =========================================================================

    function loadSubjectsForAdd() {
        const classId   = $('#add-classid').val();
        const termId    = $('#add-termid').val();
        const sessionId = $('#add-sessionid').val();
        const $list     = $('#add-subjectList');

        if (!classId) {
            $list.html('<div class="text-center text-muted py-4"><i class="ri-arrow-up-line"></i> Select a class above to load its subjects.</div>');
            $('#add-gradeScaleInfo').text('');
            return;
        }

        $list.html('<div class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading subjects…</div>');
        $('#add-gradeScaleInfo').text('');

        $.ajax({
            url: '{{ route("compulsorysubjectclass.subjectsByClass") }}',
            type: 'GET',
            data: { classid: classId, termid: termId, sessionid: sessionId },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(data) {
                if (!data.success) {
                    $list.html(`<div class="text-center text-danger py-4">${data.message || 'Failed to load subjects.'}</div>`);
                    return;
                }

                var currentGrades = data.grade_scale || [];

                if (data.category) {
                    var type = data.category.is_senior ? 'Senior' : 'Junior';
                    var info = data.category.name + ' (' + type + ' — grades: ' + currentGrades.join(', ') + ')';
                    if (data.pass_average !== null && data.pass_average !== undefined) {
                        info += ' | Min avg: ' + data.pass_average + '%';
                    }
                    $('#add-gradeScaleInfo').text(info);
                }

                if (!data.subjects || !data.subjects.length) {
                    $list.html('<div class="text-center text-muted py-4">No subjects are assigned to this class for the selected term/session.</div>');
                    return;
                }

                var html = '';
                $.each(data.subjects, function(index, sub) {
                    var checked = sub.assigned ? 'checked' : '';
                    var gradeOpts = '<option value="">— None —</option>';
                    $.each(currentGrades, function(i, g) {
                        var sel = sub.min_grade && String(sub.min_grade) === String(g) ? 'selected' : '';
                        gradeOpts += `<option value="${escapeHtml(g)}" ${sel}>${escapeHtml(g)}</option>`;
                    });

                    html += `
                    <div class="form-check">
                        <input class="form-check-input subject-checkbox" type="checkbox"
                               id="add_s_${sub.id}" value="${sub.id}" ${checked}>
                        <label class="form-check-label" for="add_s_${sub.id}">
                            <strong>${escapeHtml(sub.subject)}</strong>
                            <small class="text-muted">(${escapeHtml(sub.subject_code)})</small>
                            <span class="text-muted ms-2"><i class="ri-user-line"></i> ${escapeHtml(sub.teacher)}</span>
                            ${sub.assigned ? '<span class="text-warning ms-2">Already assigned</span>' : ''}
                        </label>
                        <select class="grade-select form-select form-select-sm d-inline-block ms-2" style="width:auto;display:inline-block!important;" data-subject-id="${sub.id}">
                            ${gradeOpts}
                        </select>
                    </div>`;
                });
                $list.html(html);
                updateAddBtn();
            },
            error: function() {
                $list.html('<div class="text-center text-danger py-4">Failed to load subjects. Please try again.</div>');
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"]/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m];
        });
    }

    $('#add-classid, #add-termid, #add-sessionid').on('change', loadSubjectsForAdd);

    // =========================================================================
    // ADD MODAL — guard button
    // =========================================================================

    function updateAddBtn() {
        var ok = $('#add-classid').val() !== '' &&
                 $('.subject-checkbox:checked').length > 0;
        $('#add-btn').prop('disabled', !ok);
    }

    $(document).on('change', '.subject-checkbox', updateAddBtn);

    // ── Open ADD ─────────────────────────────────────────────────
    $('#createCsBtn').on('click', function() {
        // Reset form fields
        $('#add-classid').val('');
        $('#add-termid').val('');
        $('#add-sessionid').val('');
        $('#add-subjectList').html('<div class="text-center text-muted py-4"><i class="ri-arrow-up-line"></i> Select a class above to load its subjects.</div>');
        $('#add-gradeScaleInfo').text('');
        $('#add-btn').prop('disabled', true);
        $('#add-error-msg').addClass('d-none').html('');
        hideModalLoader('add');
        
        // CRITICAL FIX: Reset the button state
        var $btn = $('#add-btn');
        var origHtml = '<i class="ri-save-line me-1"></i><span class="btn-text">Add Compulsory Subject(s)</span>';
        $btn.html(origHtml);
        $btn.prop('disabled', true).removeClass('btn-loading');
        $btn.removeData('original-html');
        
        new bootstrap.Modal(document.getElementById('addModal')).show();
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-cs-btn', function() {
        var id        = $(this).data('id');
        var classId   = $(this).data('class-id');
        var subjectId = $(this).data('subject-id');
        var termId    = $(this).data('term-id') || '';
        var sessionId = $(this).data('session-id') || '';
        var minGrade  = $(this).data('min-grade') || '';

        $('#edit-id').val(id);
        $('#edit-classid').val(classId);
        $('#edit-termid').val(termId);
        $('#edit-sessionid').val(sessionId);
        $('#edit-error-msg').addClass('d-none').html('');
        hideModalLoader('edit');
        
        // Reset edit button
        var $editBtn = $('#edit-update-btn');
        var origHtml = '<i class="ri-save-line me-1"></i><span class="btn-text">Update</span>';
        $editBtn.html(origHtml);
        $editBtn.prop('disabled', false).removeClass('btn-loading');
        $editBtn.removeData('original-html');

        // Load subjects for edit
        loadEditSubjects(classId, termId, sessionId, subjectId, minGrade);

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    function loadEditSubjects(classId, termId, sessionId, selectedSubjectId, selectedGrade) {
        const $subSel   = $('#edit-subjectid');
        const $gradeSel = $('#edit-minGrade');

        $subSel.html('<option value="">Loading…</option>').prop('disabled', true);
        $gradeSel.html('<option value="">Loading…</option>').prop('disabled', true);

        if (!classId) {
            $subSel.html('<option value="">— Select Subject —</option>').prop('disabled', false);
            $gradeSel.html('<option value="">— No minimum set —</option>').prop('disabled', false);
            return;
        }

        $.ajax({
            url: '{{ route("compulsorysubjectclass.subjectsByClass") }}',
            type: 'GET',
            data: { classid: classId, termid: termId, sessionid: sessionId },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(data) {
                if (!data.success) {
                    $subSel.html('<option value="">Error loading subjects</option>').prop('disabled', false);
                    return;
                }

                var subOpts = '<option value="">— Select Subject —</option>';
                $.each(data.subjects || [], function(i, s) {
                    var sel = String(s.id) === String(selectedSubjectId) ? 'selected' : '';
                    subOpts += `<option value="${s.id}" ${sel}>${escapeHtml(s.subject)} (${escapeHtml(s.subject_code)})</option>`;
                });
                $subSel.html(subOpts).prop('disabled', false);

                var grades = data.grade_scale || [];
                var gradeOpts = '<option value="">— No minimum set —</option>';
                $.each(grades, function(i, g) {
                    var sel = String(selectedGrade) === String(g) ? 'selected' : '';
                    gradeOpts += `<option value="${escapeHtml(g)}" ${sel}>${escapeHtml(g)}</option>`;
                });
                $gradeSel.html(gradeOpts).prop('disabled', false);
            },
            error: function() {
                $subSel.html('<option value="">Error</option>').prop('disabled', false);
            }
        });
    }

    $('#edit-classid, #edit-termid, #edit-sessionid').on('change', function() {
        var classId   = $('#edit-classid').val();
        var termId    = $('#edit-termid').val();
        var sessionId = $('#edit-sessionid').val();
        loadEditSubjects(classId, termId, sessionId, '', '');
    });

    // =========================================================================
    // SUBMIT: ADD
    // =========================================================================

    $('#addForm').on('submit', function(e) {
        e.preventDefault();

        var classId   = $('#add-classid').val();
        var termId    = $('#add-termid').val();
        var sessionId = $('#add-sessionid').val();
        var subjectIds = [];
        var minGrades = {};

        $('.subject-checkbox:checked').each(function() {
            var sid = $(this).val();
            subjectIds.push(sid);
            var grade = $(`.grade-select[data-subject-id="${sid}"]`).val();
            if (grade) {
                minGrades[sid] = grade;
            }
        });

        if (!classId) {
            showError('#add-error-msg', 'Please select a class.');
            return;
        }
        if (subjectIds.length === 0) {
            showError('#add-error-msg', 'Please select at least one subject.');
            return;
        }

        var $btn = $('#add-btn');
        btnLoad('#add-btn', 'Adding…');
        showModalLoader('add', 'Adding compulsory subject(s)…');
        $('#add-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("compulsorysubjectclass.store") }}',
            type: 'POST',
            data: {
                schoolclassid: classId,
                subjectId: subjectIds,
                termid: termId || null,
                sessionid: sessionId || null,
                min_grades: minGrades,
                _token: CSRF,
            },
            traditional: true,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                hideModalLoader('add');
                
                if (res.success) {
                    // Reset button before hiding modal
                    btnReset('#add-btn');
                    // Reset to original state
                    var origHtml = '<i class="ri-save-line me-1"></i><span class="btn-text">Add Compulsory Subject(s)</span>';
                    $('#add-btn').html(origHtml);
                    $('#add-btn').prop('disabled', true).removeClass('btn-loading');
                    $('#add-btn').removeData('original-html');
                    
                    $('#addModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    btnReset('#add-btn');
                    showError('#add-error-msg', res.message || 'Could not add compulsory subject.');
                }
            },

            error: function(xhr) {
                hideModalLoader('add');
                btnReset('#add-btn');
                var json = xhr.responseJSON;
                var msg = (json && json.message) ||
                          (json && json.errors && Object.values(json.errors).flat().join(', ')) ||
                          'An error occurred.';
                showError('#add-error-msg', msg);
                toast('error', 'Failed', msg);
            },
        });
    });

    // =========================================================================
    // SUBMIT: EDIT
    // =========================================================================

    $('#editForm').on('submit', function(e) {
        e.preventDefault();

        var id        = $('#edit-id').val();
        var classId   = $('#edit-classid').val();
        var subjectId = $('#edit-subjectid').val();
        var termId    = $('#edit-termid').val();
        var sessionId = $('#edit-sessionid').val();
        var minGrade  = $('#edit-minGrade').val();

        if (!classId) {
            showError('#edit-error-msg', 'Please select a class.');
            return;
        }
        if (!subjectId) {
            showError('#edit-error-msg', 'Please select a subject.');
            return;
        }

        btnLoad('#edit-update-btn', 'Updating…');
        showModalLoader('edit', 'Updating record…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url: `{{ url('compulsorysubjectclass') }}/${id}`,
            type: 'POST',
            data: {
                schoolclassid: classId,
                subjectId: subjectId,
                termid: termId || null,
                sessionid: sessionId || null,
                min_grade: minGrade || null,
                _token: CSRF,
                _method: 'PUT',
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                if (res.success) {
                    // Reset button before hiding modal
                    btnReset('#edit-update-btn');
                    var origHtml = '<i class="ri-save-line me-1"></i><span class="btn-text">Update</span>';
                    $('#edit-update-btn').html(origHtml);
                    $('#edit-update-btn').removeData('original-html');
                    
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('edit');
                    btnReset('#edit-update-btn');
                    showError('#edit-error-msg', res.message || 'Could not update.');
                }
            },

            error: function(xhr) {
                hideModalLoader('edit');
                btnReset('#edit-update-btn');
                var json = xhr.responseJSON;
                var msg = (json && json.message) ||
                          (json && json.errors && Object.values(json.errors).flat().join(', ')) ||
                          'An error occurred.';
                showError('#edit-error-msg', msg);
                toast('error', 'Failed', msg);
            },
        });
    });

    // =========================================================================
    // DELETE: SINGLE
    // =========================================================================

    $(document).on('click', '.delete-cs-btn', function() {
        deleteId = $(this).data('id');
        var subject = $(this).data('name') || 'this subject';
        var cls = $(this).data('class') || 'this class';
        $('#delete-item-title').text(subject + ' from ' + cls);
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: `{{ url('compulsorysubjectclass') }}/${deleteId}`,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                $('#deleteModal').modal('hide');
                if (res.success) {
                    toast('success', 'Deleted!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    toast('error', 'Cannot Delete', res.message);
                    Swal.fire({ icon:'error', title:'Cannot Delete',
                        text: res.message, confirmButtonColor:'#2563eb' });
                }
            },

            error: function(xhr) {
                $('#deleteModal').modal('hide');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete.';
                toast('error', 'Error', msg);
                Swal.fire('Error!', msg, 'error');
            },

            complete: function() {
                btnReset($btn);
                deleteId = null;
            },
        });
    });

    // =========================================================================
    // DELETE: BULK
    // =========================================================================

    function doBulkDelete() {
        var ids = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        
        if (ids.length === 0) {
            toast('warning', 'No Selection', 'Please select at least one record to delete.');
            return;
        }

        Swal.fire({
            title: 'Delete ' + ids.length + ' record(s)?',
            html: 'This will permanently remove the selected compulsory subject assignments.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting records…');
                    
                    $.ajax({
                        url: '{{ route("compulsorysubjectclass.bulkDestroy") }}',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            ids: ids,
                            _token: CSRF
                        }),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function(res) {
                            PageLoader.hide();
                            if (res.success) {
                                resolve(res);
                            } else {
                                reject(res.message || 'Failed to delete records');
                            }
                        },
                        error: function(xhr) {
                            PageLoader.hide();
                            var errorMsg = 'An error occurred while deleting.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            reject(errorMsg);
                        }
                    });
                });
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                toast('success', 'Deleted!', result.value.message || 'Records deleted successfully.');
                table.ajax.reload();
                loadStats();
                $('#selectAll').prop('checked', false);
                updateBulkBar();
            }
        }).catch(function(error) {
            toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete records.');
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection