{{-- resources/views/subject/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sub-primary:  #1e3a5f;
    --sub-accent:   #2563eb;
    --sub-success:  #16a34a;
    --sub-warning:  #d97706;
    --sub-danger:   #dc2626;
    --sub-muted:    #6b7280;
    --sub-border:   #e2e8f0;
    --sub-bg:       #f8fafc;
    --sub-radius:   12px;
    --sub-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.sub-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #7c3aed 100%);
    border-radius: var(--sub-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.sub-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.sub-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.sub-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sub-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--sub-border);
    border-radius:var(--sub-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sub-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sub-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sub-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.sub-table th {
    background:var(--sub-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.sub-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--sub-border); font-size:13px;
}
.sub-table tr:hover td { background:#eff6ff; }

/* ── Badges ──────────────────────────────────────────────── */
.sub-badge {
    display:inline-flex; align-items:center;
    padding:3px 10px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.sub-badge-remark {
    background:#f0fdf4; color:#16a34a;
    border:1px solid #bbf7d0;
}

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--sub-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--sub-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--sub-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--sub-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--sub-accent) !important;
    border-color:var(--sub-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.sub-modal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, var(--sub-primary) 0%, #7c3aed 100%);
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
    border:1.5px solid var(--sub-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--sub-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Multi-row add subjects ──────────────────────────────── */
.multi-head, .multi-row {
    display:grid;
    grid-template-columns:30px 1.6fr 1fr 1.2fr 38px;
    gap:8px; align-items:start;
}
.multi-head {
    font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.4px; color:var(--sub-muted); padding:0 4px 6px 0;
}
.multi-rows { max-height:340px; overflow-y:auto; padding:2px 6px 2px 0; }
.multi-row { margin-bottom:8px; }
.multi-row .row-num {
    width:26px; height:26px; margin-top:7px; border-radius:50%;
    background:#eff6ff; color:var(--sub-accent);
    font-size:11px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
}
.multi-row .form-control { padding:8px 10px; }
.multi-row .form-control.is-invalid {
    border-color:var(--sub-danger);
    box-shadow:0 0 0 3px rgba(220,38,38,.1);
    background-image:none;
}
.multi-row .row-remove { height:36px; padding:0; }
.multi-actions { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-top:10px; }
.multi-actions .ready-note { margin-left:auto; font-size:12px; color:var(--sub-muted); }
.multi-actions .ready-note strong { color:var(--sub-primary); }
.multi-with-teacher .multi-head,
.multi-with-teacher .multi-row { grid-template-columns:30px 1.4fr .9fr 1fr 1.3fr 38px; }
.multi-row .form-select { padding:8px 10px; }
.multi-row .form-select.is-invalid {
    border-color:var(--sub-danger);
    box-shadow:0 0 0 3px rgba(220,38,38,.1);
    background-image:none;
}
.multi-actions .form-select-sm { max-width:230px; font-size:12px; padding:5px 10px; }

.assign-panel {
    margin-top:14px; padding:14px 16px;
    border:1.5px solid #bae6fd; background:#f0f9ff; border-radius:10px;
}
.assign-panel .assign-title { font-size:12px; font-weight:700; color:var(--sub-primary); margin-bottom:10px; }
.assign-panel .assign-title i { color:#0891b2; margin-right:4px; }
.assign-panel .assign-label {
    font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.4px; color:var(--sub-muted); margin:8px 0 4px;
}
.assign-panel .assign-group { display:flex; flex-wrap:wrap; gap:6px 16px; }
.assign-panel .form-check { margin:0; }
.assign-panel .form-check-label { font-size:13px; cursor:pointer; }
.assign-panel .form-check-input:checked { background-color:var(--sub-accent); border-color:var(--sub-accent); }
.assign-panel.is-invalid-panel { border-color:var(--sub-danger); background:#fef2f2; }

@media (max-width:576px) {
    .multi-head { display:none; }
    .multi-row,
    .multi-with-teacher .multi-row { grid-template-columns:26px 1fr 38px; }
    .multi-row .row-num { grid-column:1; grid-row:1; }
    .multi-row .row-subject,
    .multi-row .row-code,
    .multi-row .row-remark,
    .multi-row .row-teacher { grid-column:2; }
    .multi-row .row-remove { grid-column:3; grid-row:1; }
}

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#sub-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#sub-page-loader.active { opacity:1; visibility:visible; }

.sub-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22);
    min-width:220px;
}
.sub-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0;
    border-top-color:var(--sub-accent);
    border-radius:50%;
    animation:sub-spin .75s linear infinite;
}
@keyframes sub-spin { to { transform:rotate(360deg); } }

.sub-loader-label {
    font-size:14px; font-weight:600;
    color:var(--sub-primary); margin-bottom:12px;
}
.sub-progress-wrap {
    width:160px; height:5px;
    background:#e2e8f0; border-radius:99px; overflow:hidden;
    margin:0 auto;
}
.sub-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--sub-accent), #7c3aed);
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
    border-top-color:var(--sub-accent);
    border-radius:50%;
    animation:sub-spin .7s linear infinite;
}
.modal-body-loader .mbl-text {
    font-size:13px; font-weight:600; color:var(--sub-primary);
}

/* ── Toast notifications ──────────────────────────────────── */
#sub-toast-stack {
    position:fixed; bottom:24px; right:24px;
    z-index:10000; display:flex;
    flex-direction:column-reverse; gap:10px;
    pointer-events:none;
}
.sub-toast {
    pointer-events:all;
    background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--sub-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.sub-toast.show { transform:translateX(0); }
.sub-toast.sub-toast-success { border-left-color:var(--sub-success); }
.sub-toast.sub-toast-error   { border-left-color:var(--sub-danger);  }
.sub-toast.sub-toast-warning { border-left-color:var(--sub-warning); }
.sub-toast .sub-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.sub-toast-success .sub-toast-icon { color:var(--sub-success); }
.sub-toast-error   .sub-toast-icon { color:var(--sub-danger);  }
.sub-toast-warning .sub-toast-icon { color:var(--sub-warning); }
.sub-toast .sub-toast-body { flex:1; }
.sub-toast .sub-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.sub-toast .sub-toast-msg   { font-size:12px; color:var(--sub-muted); line-height:1.4; }
.sub-toast .sub-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--sub-muted); font-size:16px; line-height:1;
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
    animation:sub-spin .65s linear infinite;
}
.btn-loading.btn-outline-secondary::after,
.btn-loading.btn-outline-danger::after { border-top-color:currentColor; }
.btn-loading.btn-light::after { border-top-color:#374151; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="sub-page-loader">
    <div class="sub-loader-card">
        <div class="sub-loader-spinner"></div>
        <div class="sub-loader-label" id="sub-loader-label">Processing…</div>
        <div class="sub-progress-wrap">
            <div class="sub-progress-bar" id="sub-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast notification stack ═══ --}}
<div id="sub-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="sub-hero">
        <h1><i class="ri-book-2-line me-2"></i>Subject Management</h1>
        <p>Create and manage all subjects offered across your school.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-book-2-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Subjects</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-barcode-line"></i></div>
                <div class="stat-value text-primary" id="statShowing">—</div>
                <div class="stat-label">Showing Now</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-star-line"></i></div>
                <div class="stat-value text-success" id="statWithTeachers">—</div>
                <div class="stat-label">With Teachers</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-line"></i></div>
                <div class="stat-value text-warning" id="statRecent">—</div>
                <div class="stat-label">Recent Updates (30d)</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sub-primary)">
                    <i class="ri-list-check me-2"></i>All Subjects
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create subjects')
                    <button class="btn btn-primary" id="createSubjectBtn">
                        <i class="ri-add-line me-1"></i>Create Subjects
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> subject(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table sub-table w-100 mb-0" id="subjectsTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Subject Code</th>
                            <th>Remark</th>
                            <th>Usage</th>
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

{{-- ═══════════════════════ ADD MODAL (multi-row) ═══════════════════════ --}}
<div class="modal fade sub-modal" id="addSubjectModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-circle-line me-2"></i>Add Subjects</h5>
            </div>
            <form id="add-subject-form" autocomplete="off"
                  class="@can('Create subject-teacher') multi-with-teacher @endcan">
                @csrf
                <div class="modal-body-loader" id="add-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="add-modal-loader-text">Saving…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    <div class="multi-head">
                        <div>#</div>
                        <div>Subject Name <span class="text-danger">*</span></div>
                        <div>Code <span class="text-danger">*</span></div>
                        <div>Remark <span class="text-danger">*</span></div>
                        @can('Create subject-teacher')
                        <div>Teacher <span class="text-muted fw-normal">(optional)</span></div>
                        @endcan
                        <div></div>
                    </div>

                    <div class="multi-rows" id="add-rows"></div>

                    <div class="multi-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-row-btn">
                            <i class="ri-add-line me-1"></i>Add Row
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-5-rows-btn">
                            <i class="ri-add-line me-1"></i>Add 5 Rows
                        </button>
                        @can('Create subject-teacher')
                        <select id="add-teacher-all" class="form-select form-select-sm">
                            <option value="">Set teacher for all rows…</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->userid }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        @endcan
                        <span class="ready-note">
                            <strong id="add-ready-count">0</strong> subject(s) ready
                            <span id="add-assign-note"></span>
                        </span>
                    </div>

                    @can('Create subject-teacher')
                    {{-- Shown only once at least one row has a teacher --}}
                    <div class="assign-panel d-none" id="assign-panel">
                        <div class="assign-title">
                            <i class="ri-user-star-line"></i>Teacher assignment — applies to every row that has a teacher
                        </div>

                        <div class="assign-label">Term(s) <span class="text-danger">*</span></div>
                        <div class="assign-group">
                            @foreach ($terms as $term)
                                <div class="form-check">
                                    <input class="form-check-input add-term-checkbox" type="checkbox"
                                           id="add-term-{{ $term->id }}" value="{{ $term->id }}">
                                    <label class="form-check-label" for="add-term-{{ $term->id }}">{{ $term->term }}</label>
                                </div>
                            @endforeach
                        </div>

                        <div class="assign-label">Session <span class="text-danger">*</span></div>
                        <div class="assign-group">
                            @foreach ($schoolsessions as $session)
                                <div class="form-check">
                                    <input class="form-check-input add-session-radio" type="radio" name="add_sessionid"
                                           id="add-session-{{ $session->id }}" value="{{ $session->id }}">
                                    <label class="form-check-label" for="add-session-{{ $session->id }}">{{ $session->session }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endcan

                    <small class="text-muted d-block mt-2">
                        Tip: press <kbd>Enter</kbd> in the Remark field to jump to the next row. Completely empty rows are ignored.
                    </small>

                    <div class="alert alert-danger d-none mt-3 mb-0" id="add-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Subject</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade sub-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Subject</h5>
            </div>
            <form id="edit-subject-form" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-id">
                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">
                    <div class="mb-3">
                        <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="edit-subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" id="edit-subject-code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark <span class="text-danger">*</span></label>
                        <input type="text" name="remark" id="edit-remark" class="form-control" required>
                    </div>
                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Subject</span>
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
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete subject <strong id="delete-subject-name"></strong>?</p>
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
    const MAX_ROWS = 50;
    const CAN_ASSIGN = @can('Create subject-teacher') true @else false @endcan;
    const TEACHERS = @can('Create subject-teacher')
        @json($staffs->map(fn ($s) => ['id' => $s->userid, 'name' => $s->name])->values())
    @else [] @endcan;
    let deleteId = null;

    // =========================================================================
    // LOADING HELPERS
    // =========================================================================

    const PageLoader = {
        _prog: 0, _timer: null,
        show(label = 'Processing…') {
            $('#sub-loader-label').text(label);
            $('#sub-progress-bar').css('width', '0%');
            $('#sub-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#sub-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#sub-progress-bar').css('width', '100%');
            setTimeout(() => $('#sub-page-loader').removeClass('active'), 350);
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
        $btn.data('original-html', $btn.html())
            .prop('disabled', true)
            .addClass('btn-loading');
        if (loadingText) {
            $btn.html(`<span class="btn-text">${loadingText}</span>`);
        }
        return $btn;
    }
    function btnReset(selector) {
        const $btn = $(selector);
        const orig = $btn.data('original-html');
        if (orig) $btn.html(orig);
        $btn.prop('disabled', false).removeClass('btn-loading');
    }

    function toast(type, title, msg, duration = 4000) {
        const icons = {
            success: 'ri-checkbox-circle-fill',
            error:   'ri-close-circle-fill',
            warning: 'ri-alert-fill',
            info:    'ri-information-fill',
        };
        const id  = 'sub-toast-' + Date.now() + Math.floor(Math.random() * 1000);
        const $el = $(`
            <div class="sub-toast sub-toast-${type}" id="${id}">
                <span class="sub-toast-icon"><i class="${icons[type] || icons.info}"></i></span>
                <div class="sub-toast-body">
                    <div class="sub-toast-title">${title}</div>
                    ${msg ? `<div class="sub-toast-msg">${msg}</div>` : ''}
                </div>
                <button class="sub-toast-close" onclick="$('#${id}').remove()">×</button>
            </div>
        `);
        $('#sub-toast-stack').append($el);
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
    // SAFETY NET — reset loading state whenever a modal closes
    // =========================================================================

    $('#addSubjectModal').on('hidden.bs.modal', function () {
        btnReset('#add-btn');
        hideModalLoader('add');
        updateCreateBtn();
    });
    $('#editModal').on('hidden.bs.modal', function () {
        btnReset('#update-btn');
        hideModalLoader('edit');
    });
    $('#addSubjectModal').on('shown.bs.modal', function () {
        $('#add-rows .row-subject').first().trigger('focus');
    });

    // =========================================================================
    // DATATABLE (server-side)
    // =========================================================================

    var table = $('#subjectsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("subject.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load subjects. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'subject_info', orderable: false },
            { data: 'code_info', orderable: false },
            { data: 'remark_info', orderable: false },
            { data: 'usage_count', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing:      '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search:          '',
            searchPlaceholder: 'Search subjects…',
            lengthMenu:      'Show _MENU_ entries',
            info:            'Showing _START_–_END_ of _TOTAL_ subjects',
            infoEmpty:       'No subjects found',
            zeroRecords:     'No matching subjects',
            emptyTable:      'No subjects created yet',
        },
        order: [[2, 'asc']],
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
        $.get('{{ route("subject.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statShowing').text(data.stats.showing);
                $('#statWithTeachers').text(data.stats.with_teachers);
                $('#statRecent').text(data.stats.recently_updated);
            }
        }).fail(function() {
            $('#statTotal, #statShowing, #statWithTeachers, #statRecent').text('—');
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
    // ADD MODAL — MULTI-ROW
    // =========================================================================

    function rowValues($r) {
        return {
            subject:      $r.find('.row-subject').val().trim(),
            subject_code: $r.find('.row-code').val().trim(),
            remark:       $r.find('.row-remark').val().trim(),
            staffid:      CAN_ASSIGN ? ($r.find('.row-teacher').val() || '') : '',
        };
    }

    // values a freshly added row inherits from the row above it
    function carryFrom($last) {
        if (!$last || !$last.length) return {};
        var v = rowValues($last);
        return { remark: v.remark, staffid: v.staffid };
    }

    function renumberRows() {
        var $rows = $('#add-rows .multi-row');
        $rows.each(function (i) { $(this).find('.row-num').text(i + 1); });
        // never allow removing the last remaining row
        $rows.find('.row-remove').prop('disabled', $rows.length === 1);
        var full = $rows.length >= MAX_ROWS;
        $('#add-row-btn, #add-5-rows-btn').prop('disabled', full);
    }

    function addRow(vals) {
        if ($('#add-rows .multi-row').length >= MAX_ROWS) return null;
        vals = vals || {};
        var $r = $(
            '<div class="multi-row">' +
                '<div class="row-num"></div>' +
                '<input type="text" class="form-control row-subject" placeholder="e.g. Mathematics">' +
                '<input type="text" class="form-control row-code" placeholder="e.g. MTH101">' +
                '<input type="text" class="form-control row-remark" placeholder="e.g. Core Subject">' +
                (CAN_ASSIGN ? '<select class="form-select row-teacher"></select>' : '') +
                '<button type="button" class="btn btn-sm btn-outline-danger row-remove" title="Remove row">' +
                    '<i class="ri-close-line"></i>' +
                '</button>' +
            '</div>'
        );
        $r.find('.row-subject').val(vals.subject || '');
        $r.find('.row-code').val(vals.subject_code || '');
        $r.find('.row-remark').val(vals.remark || '');
        if (CAN_ASSIGN) {
            var $sel = $r.find('.row-teacher');
            $sel.append($('<option>').val('').text('— None —'));
            TEACHERS.forEach(function (t) { $sel.append($('<option>').val(t.id).text(t.name)); });
            $sel.val(vals.staffid ? String(vals.staffid) : '');
        }
        $('#add-rows').append($r);
        renumberRows();
        updateCreateBtn();
        return $r;
    }

    function updateCreateBtn() {
        var ready = 0, assigned = 0, anyTeacher = false;
        $('#add-rows .multi-row').each(function () {
            var v = rowValues($(this));
            if (v.staffid) anyTeacher = true;
            if (v.subject && v.subject_code && v.remark) {
                ready++;
                if (v.staffid) assigned++;
            }
        });
        $('#add-ready-count').text(ready);
        $('#add-assign-note').text(assigned > 0 ? ' · ' + assigned + ' with a teacher' : '');
        $('#assign-panel').toggleClass('d-none', !anyTeacher);
        $('#add-btn').prop('disabled', ready === 0);
        if (!$('#add-btn').hasClass('btn-loading')) {
            var label = ready > 1 ? 'Add ' + ready + ' Subjects' : 'Add Subject';
            if (assigned > 0) label += ' & Assign';
            $('#add-btn .btn-text').text(label);
        }
    }

    // typing clears the red highlight and refreshes the button
    $('#add-rows').on('input change', 'input, select', function () {
        $(this).removeClass('is-invalid');
        updateCreateBtn();
    });

    // clear the red state on the term/session panel once the user picks something
    $('#assign-panel').on('change', 'input', function () {
        $('#assign-panel').removeClass('is-invalid-panel');
    });

    // "Set teacher for all rows"
    $('#add-teacher-all').on('change', function () {
        var id = $(this).val();
        if (!id) return;
        $('#add-rows .row-teacher').val(id).removeClass('is-invalid');
        $(this).val('');
        updateCreateBtn();
    });

    // remove a row
    $('#add-rows').on('click', '.row-remove', function () {
        $(this).closest('.multi-row').remove();
        renumberRows();
        updateCreateBtn();
    });

    // Enter in Remark → next row (create one if this is the last)
    $('#add-rows').on('keydown', '.row-remark', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var $row  = $(this).closest('.multi-row');
        var $next = $row.next('.multi-row');
        if (!$next.length) {
            $next = addRow(carryFrom($row));   // carry remark + teacher forward
        }
        if ($next) $next.find('.row-subject').trigger('focus');
    });

    $('#add-row-btn').on('click', function () {
        var $r = addRow(carryFrom($('#add-rows .multi-row').last()));
        if ($r) {
            $r.find('.row-subject').trigger('focus');
            $('#add-rows').scrollTop($('#add-rows')[0].scrollHeight);
        }
    });

    $('#add-5-rows-btn').on('click', function () {
        var carry  = carryFrom($('#add-rows .multi-row').last());
        var $first = null;
        for (var i = 0; i < 5; i++) {
            var $r = addRow(carry);
            if ($r && !$first) $first = $r;
        }
        if ($first) $first.find('.row-subject').trigger('focus');
        $('#add-rows').scrollTop($('#add-rows')[0].scrollHeight);
    });

    // ── Open CREATE ───────────────────────────────────────────
    $('#createSubjectBtn').on('click', function() {
        $('#add-rows').empty();
        addRow(); addRow(); addRow();
        $('.add-term-checkbox').prop('checked', false);
        $('.add-session-radio').prop('checked', false);
        $('#assign-panel').addClass('d-none').removeClass('is-invalid-panel');
        $('#add-teacher-all').val('');
        $('#add-error-msg').addClass('d-none').html('');
        btnReset('#add-btn');
        updateCreateBtn();
        hideModalLoader('add');
        new bootstrap.Modal(document.getElementById('addSubjectModal')).show();
    });

    // Map server-side row errors back onto the matching rows
    function applyRowErrors(errors, rowEls, fallbackMsg) {
        var fieldSel = { subject: '.row-subject', subject_code: '.row-code', remark: '.row-remark', staffid: '.row-teacher' };
        var lines = [];

        Object.keys(errors || {}).forEach(function (key) {
            var msg = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            var m = key.match(/^subjects\.(\d+)\.(subject|subject_code|remark|staffid)$/);
            if (!m) {
                lines.push(msg);
                if (key === 'termid' || key.indexOf('termid.') === 0 || key === 'sessionid') {
                    $('#assign-panel').addClass('is-invalid-panel');
                }
                return;
            }
            var $r = rowEls[parseInt(m[1], 10)];
            if (!$r) { lines.push(msg); return; }
            $r.find(fieldSel[m[2]]).addClass('is-invalid');
            lines.push('Row ' + $r.find('.row-num').text() + ': ' + msg);
        });

        showError('#add-error-msg', lines.length ? lines.join('<br>') : (fallbackMsg || 'An error occurred.'));
        var $bad = $('#add-rows .is-invalid').first();
        if ($bad.length) $bad[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    // =========================================================================
    // SUBMIT: CREATE (many)
    // =========================================================================

    $('#add-subject-form').on('submit', function(e) {
        e.preventDefault();

        var payload = [];
        var rowEls  = [];
        var partial = false;

        $('#add-rows .multi-row').each(function () {
            var $r = $(this);
            var v  = rowValues($r);
            var filled = [v.subject, v.subject_code, v.remark].filter(Boolean).length;

            $r.find('input').removeClass('is-invalid');
            if (filled === 0) return;                       // blank row → ignored
            if (filled < 3) {                               // partly filled → flag it
                partial = true;
                $r.find('input').each(function () {
                    if (!$(this).val().trim()) $(this).addClass('is-invalid');
                });
                return;
            }
            payload.push(v);
            rowEls.push($r);
        });

        if (partial) {
            showError('#add-error-msg', 'Some rows are incomplete — fill in all three fields or remove the row.');
            return;
        }
        if (!payload.length) {
            showError('#add-error-msg', 'Add at least one subject.');
            return;
        }

        // teacher assignment (optional) — needs terms + session when any row has a teacher
        var withTeacher = CAN_ASSIGN && payload.some(function (p) { return !!p.staffid; });
        var termids     = [];
        var sessionid   = '';
        if (withTeacher) {
            termids   = $('.add-term-checkbox:checked').map(function () { return this.value; }).get();
            sessionid = $('.add-session-radio:checked').val() || '';
            if (!termids.length || !sessionid) {
                $('#assign-panel').addClass('is-invalid-panel');
                showError('#add-error-msg', 'Choose at least one term and a session for the teacher assignment.');
                return;
            }
        }

        btnLoad('#add-btn', 'Adding…');
        showModalLoader('add', 'Saving ' + payload.length + ' subject(s)…');
        $('#add-error-msg').addClass('d-none').html('');

        $.ajax({
            url:     '{{ route("subject.store") }}',
            type:    'POST',
            // no "traditional" → jQuery sends subjects[0][subject]=… so PHP gets a real nested array
            data:    withTeacher
                       ? { subjects: payload, termid: termids, sessionid: sessionid, _token: CSRF }
                       : { subjects: payload, _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },

            success: function(res) {
                if (res.success) {
                    // reset BEFORE hiding so the next open is clean
                    hideModalLoader('add');
                    btnReset('#add-btn');

                    $('#addSubjectModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('add');
                    btnReset('#add-btn');
                    updateCreateBtn();
                    showError('#add-error-msg', res.message || 'Could not add subjects.');
                }
            },

            error: function(xhr) {
                hideModalLoader('add');
                btnReset('#add-btn');
                updateCreateBtn();
                var json = xhr.responseJSON;
                if (json && json.errors) {
                    applyRowErrors(json.errors, rowEls, json.message);
                } else {
                    var msg = (json && json.message) || 'An error occurred.';
                    showError('#add-error-msg', msg);
                    toast('error', 'Failed', msg);
                }
            },
        });
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-subject-btn', function() {
        var id      = $(this).data('id');
        var subject = $(this).data('subject');
        var code    = $(this).data('code');
        var remark  = $(this).data('remark');

        $('#edit-id').val(id);
        $('#edit-subject').val(subject);
        $('#edit-subject-code').val(code);
        $('#edit-remark').val(remark);

        $('#edit-error-msg').addClass('d-none').html('');
        hideModalLoader('edit');
        btnReset('#update-btn');

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // =========================================================================
    // SUBMIT: EDIT
    // =========================================================================

    $('#edit-subject-form').on('submit', function(e) {
        e.preventDefault();

        var id      = $('#edit-id').val();
        var subject = $('#edit-subject').val().trim();
        var code    = $('#edit-subject-code').val().trim();
        var remark  = $('#edit-remark').val().trim();

        if (!subject || !code || !remark) {
            showError('#edit-error-msg', 'All fields are required.');
            return;
        }

        btnLoad('#update-btn', 'Updating…');
        showModalLoader('edit', 'Saving changes…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url:     `{{ url('subject') }}/${id}`,
            type:    'POST',
            data:    { subject, subject_code: code, remark, _token: CSRF, _method: 'PUT' },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                if (res.success) {
                    hideModalLoader('edit');
                    btnReset('#update-btn');
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('edit');
                    btnReset('#update-btn');
                    showError('#edit-error-msg', res.message || 'Could not update.');
                }
            },

            error: function(xhr) {
                hideModalLoader('edit');
                btnReset('#update-btn');
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

    $(document).on('click', '.delete-subject-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-subject-name').text($(this).data('subject') || 'this subject');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: `{{ url('subject') }}/${deleteId}`,
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
            toast('warning', 'No Selection', 'Please select at least one subject to delete.');
            return;
        }

        Swal.fire({
            title: 'Delete ' + ids.length + ' subject(s)?',
            html: 'This will permanently remove the selected subjects.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting subjects…');

                    // Send as JSON with proper array format
                    $.ajax({
                        url: '{{ route("subject.bulk-destroy") }}',
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
                                reject(res.message || 'Failed to delete subjects');
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
                toast('success', 'Deleted!', result.value.message || 'Subjects deleted successfully.');
                table.ajax.reload();
                loadStats();
                $('#selectAll').prop('checked', false);
                updateBulkBar();
            }
        }).catch(function(error) {
            toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete subjects.');
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection
