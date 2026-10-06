{{-- resources/views/classteacher/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --ct-primary:  #1e3a5f;
    --ct-accent:   #2563eb;
    --ct-success:  #16a34a;
    --ct-warning:  #d97706;
    --ct-danger:   #dc2626;
    --ct-muted:    #6b7280;
    --ct-border:   #e2e8f0;
    --ct-bg:       #f8fafc;
    --ct-radius:   12px;
    --ct-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.ct-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #0f766e 60%, #0891b2 100%);
    border-radius: var(--ct-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.ct-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.ct-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.ct-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.ct-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--ct-border);
    border-radius:var(--ct-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--ct-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--ct-primary); }
.stat-card .stat-label { font-size:12px; color:var(--ct-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.ct-table th {
    background:var(--ct-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.ct-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--ct-border); font-size:13px;
}
.ct-table tr:hover td { background:#f0fdfa; }

/* ── Badges ──────────────────────────────────────────────── */
.ct-badge {
    display:inline-flex; align-items:center;
    padding:3px 9px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.ct-badge-term    { background:#dbeafe; color:#2563eb; }
.ct-badge-session { background:#ccfbf1; color:#0f766e; }
.ct-badge-class   { background:#fef3c7; color:#d97706; }

/* ── Avatar ──────────────────────────────────────────────── */
.teacher-avatar {
    width:36px; height:36px; border-radius:50%;
    object-fit:cover; border:2px solid var(--ct-border);
    cursor:pointer; transition:border-color .15s;
    display:block; flex-shrink:0;
}
.teacher-avatar:hover { border-color:var(--ct-accent); }

/* Initials placeholder — shown when no real photo exists */
.avatar-initials {
    width:36px; height:36px; border-radius:50%;
    background:linear-gradient(135deg, #1e3a5f 0%, #0891b2 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:700; font-size:13px; letter-spacing:.5px;
    border:2px solid var(--ct-border);
    cursor:pointer; flex-shrink:0; user-select:none;
    transition:border-color .15s, transform .15s;
}
.avatar-initials:hover { border-color:var(--ct-accent); transform:scale(1.08); }

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--ct-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--ct-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--ct-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--ct-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--ct-accent) !important;
    border-color:var(--ct-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.ct-modal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, #1e3a5f 0%, #0f766e 100%);
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
    border:1.5px solid var(--ct-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--ct-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Checkbox / radio groups ─────────────────────────────── */
.checkbox-scroll {
    max-height:220px; overflow-y:auto;
    border:1.5px solid var(--ct-border); border-radius:8px;
    padding:10px 14px; background:#fafbfc;
}
.checkbox-scroll .form-check { padding:5px 0; border-bottom:1px solid #f0f0f0; }
.checkbox-scroll .form-check:last-child { border-bottom:none; }
.checkbox-scroll .form-check-label { font-size:13px; cursor:pointer; }
.checkbox-scroll .form-check-input:checked {
    background-color:var(--ct-accent); border-color:var(--ct-accent);
}

.inline-check-group {
    display:flex; flex-wrap:wrap; gap:8px;
    padding:10px 14px;
    border:1.5px solid var(--ct-border); border-radius:8px;
    background:#fafbfc;
}
.inline-check-group .form-check { margin:0; }
.inline-check-group .form-check-label { font-size:13px; cursor:pointer; }
.inline-check-group .form-check-input:checked {
    background-color:var(--ct-accent); border-color:var(--ct-accent);
}

.select-all-bar {
    background:#eff6ff; border:1.5px solid #bfdbfe;
    border-radius:8px; padding:7px 12px; margin-bottom:6px;
    display:flex; align-items:center; gap:8px;
    font-size:12px; font-weight:600; color:var(--ct-accent);
    cursor:pointer;
}

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#ct-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#ct-page-loader.active { opacity:1; visibility:visible; }
.ct-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px;
}
.ct-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0; border-top-color:var(--ct-accent);
    border-radius:50%; animation:ct-spin .75s linear infinite;
}
@keyframes ct-spin { to { transform:rotate(360deg); } }
.ct-loader-label { font-size:14px; font-weight:600; color:var(--ct-primary); margin-bottom:12px; }
.ct-progress-wrap {
    width:160px; height:5px; background:#e2e8f0;
    border-radius:99px; overflow:hidden; margin:0 auto;
}
.ct-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--ct-accent), #0d9488);
    border-radius:99px; transition:width .35s ease;
}

/* ── Modal body loading overlay ──────────────────────────── */
.modal-body-loader {
    position:absolute; inset:0; z-index:10;
    background:rgba(255,255,255,.82); backdrop-filter:blur(2px);
    display:flex; align-items:center; justify-content:center;
    border-radius:0 0 16px 16px;
    opacity:0; visibility:hidden; transition:opacity .18s, visibility .18s;
}
.modal-body-loader.active { opacity:1; visibility:visible; }
.modal-body-loader .inner { display:flex; flex-direction:column; align-items:center; gap:10px; }
.modal-body-loader .mbl-spinner {
    width:36px; height:36px; border:3px solid #e2e8f0;
    border-top-color:var(--ct-accent); border-radius:50%;
    animation:ct-spin .7s linear infinite;
}
.modal-body-loader .mbl-text { font-size:13px; font-weight:600; color:var(--ct-primary); }

/* ── Toast notifications ─────────────────────────────────── */
#ct-toast-stack {
    position:fixed; bottom:24px; right:24px; z-index:10000;
    display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none;
}
.ct-toast {
    pointer-events:all; background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--ct-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.ct-toast.show { transform:translateX(0); }
.ct-toast.ct-toast-success { border-left-color:var(--ct-success); }
.ct-toast.ct-toast-error   { border-left-color:var(--ct-danger);  }
.ct-toast.ct-toast-warning { border-left-color:var(--ct-warning); }
.ct-toast .ct-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.ct-toast-success .ct-toast-icon { color:var(--ct-success); }
.ct-toast-error   .ct-toast-icon { color:var(--ct-danger);  }
.ct-toast-warning .ct-toast-icon { color:var(--ct-warning); }
.ct-toast .ct-toast-body { flex:1; }
.ct-toast .ct-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.ct-toast .ct-toast-msg   { font-size:12px; color:var(--ct-muted); line-height:1.4; }
.ct-toast .ct-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--ct-muted); font-size:16px; line-height:1; padding:0; flex-shrink:0;
}

/* ── Button loading state ────────────────────────────────── */
.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:''; position:absolute; inset:0; margin:auto;
    width:16px; height:16px; border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%; animation:ct-spin .65s linear infinite;
}
.btn-loading.btn-outline-secondary::after,
.btn-loading.btn-outline-danger::after { border-top-color:currentColor; }
.btn-loading.btn-light::after { border-top-color:#374151; }

/* ── Edit info note ──────────────────────────────────────── */
.edit-info-note {
    background:#eff6ff; border:1px solid #bfdbfe;
    border-radius:8px; padding:10px 14px;
    font-size:12px; color:#2563eb; margin-bottom:16px;
}
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="ct-page-loader">
    <div class="ct-loader-card">
        <div class="ct-loader-spinner"></div>
        <div class="ct-loader-label" id="ct-loader-label">Processing…</div>
        <div class="ct-progress-wrap">
            <div class="ct-progress-bar" id="ct-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast stack ═══ --}}
<div id="ct-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="ct-hero">
        <h1><i class="ri-user-star-line me-2"></i>Class Teacher Management</h1>
        <p>Assign class teachers and manage responsibilities across terms and sessions.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-settings-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-line"></i></div>
                <div class="stat-value text-primary" id="statTeachers">—</div>
                <div class="stat-label">Unique Teachers</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-book-open-line"></i></div>
                <div class="stat-value text-success" id="statClasses">—</div>
                <div class="stat-label">Classes Assigned</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-event-line"></i></div>
                <div class="stat-value text-warning" id="statActive">—</div>
                <div class="stat-label">Active Sessions</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--ct-primary)">
                    <i class="ri-team-line me-2"></i>Class Teacher Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create class-teacher')
                    <button class="btn btn-primary" id="createAssignmentBtn">
                        <i class="ri-add-line me-1"></i>Create Assignment
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> assignment(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table ct-table w-100 mb-0" id="classTeachersTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Teacher</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Session</th>
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

{{-- ═══════════════════════ CREATE MODAL ════════════════════ --}}
<div class="modal fade ct-modal" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-user-add-line me-2"></i>Create Assignment</h5>
            </div>
            <form id="createForm" autocomplete="off">
                @csrf
                <div class="modal-body-loader" id="create-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="create-modal-loader-text">Saving…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    {{-- Teacher --}}
                    <div class="mb-3">
                        <label class="form-label">Class Teacher <span class="text-danger">*</span></label>
                        <select id="create-staffid" class="form-select" required>
                            <option value="">— Select Teacher —</option>
                            @foreach ($subjectteachers as $teacher)
                                <option value="{{ $teacher->userid }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Classes --}}
                    <div class="mb-3">
                        <label class="form-label">Class(es) <span class="text-danger">*</span></label>
                        <div class="select-all-bar" id="create-select-all-bar">
                            <input type="checkbox" class="form-check-input" id="create-select-all-classes">
                            <label for="create-select-all-classes" class="mb-0">Select All Classes</label>
                        </div>
                        <div class="checkbox-scroll" id="create-class-list">
                            @foreach ($schoolclass->sortBy('schoolclass') as $class)
                                <div class="form-check">
                                    <input class="form-check-input create-class-cb" type="checkbox"
                                           value="{{ $class->id }}"
                                           id="create-cls-{{ $class->id }}">
                                    <label class="form-check-label" for="create-cls-{{ $class->id }}">
                                        {{ $class->schoolclass }}
                                        @if ($class->schoolarm)
                                            <span class="text-muted">({{ $class->schoolarm }})</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <span id="create-class-count">0</span> class(es) selected
                        </small>
                    </div>

                    {{-- Term --}}
                    <div class="mb-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <div class="inline-check-group">
                            @foreach ($schoolterms as $term)
                                <div class="form-check">
                                    <input class="form-check-input create-term-rb" type="radio"
                                           name="create_termid"
                                           id="create-term-{{ $term->id }}"
                                           value="{{ $term->id }}">
                                    <label class="form-check-label" for="create-term-{{ $term->id }}">
                                        {{ $term->term }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Session --}}
                    <div class="mb-3">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <div class="inline-check-group">
                            @foreach ($schoolsessions as $session)
                                <div class="form-check">
                                    <input class="form-check-input create-session-rb" type="radio"
                                           name="create_sessionid"
                                           id="create-session-{{ $session->id }}"
                                           value="{{ $session->id }}">
                                    <label class="form-check-label" for="create-session-{{ $session->id }}">
                                        {{ $session->session }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="alert alert-danger d-none" id="create-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="create-save-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Save Assignment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade ct-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Assignment</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-assignment-id">
                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    <div class="edit-info-note">
                        <i class="ri-information-line me-1"></i>
                        Saving will replace all existing class assignments for this teacher, term, and session.
                    </div>

                    {{-- Teacher --}}
                    <div class="mb-3">
                        <label class="form-label">Class Teacher <span class="text-danger">*</span></label>
                        <select id="edit-staffid" class="form-select" required>
                            <option value="">— Select Teacher —</option>
                            @foreach ($subjectteachers as $teacher)
                                <option value="{{ $teacher->userid }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Classes --}}
                    <div class="mb-3">
                        <label class="form-label">Class(es) <span class="text-danger">*</span></label>
                        <div class="select-all-bar" id="edit-select-all-bar">
                            <input type="checkbox" class="form-check-input" id="edit-select-all-classes">
                            <label for="edit-select-all-classes" class="mb-0">Select All Classes</label>
                        </div>
                        <div class="checkbox-scroll" id="edit-class-list">
                            @foreach ($schoolclass->sortBy('schoolclass') as $class)
                                <div class="form-check">
                                    <input class="form-check-input edit-class-cb" type="checkbox"
                                           value="{{ $class->id }}"
                                           id="edit-cls-{{ $class->id }}">
                                    <label class="form-check-label" for="edit-cls-{{ $class->id }}">
                                        {{ $class->schoolclass }}
                                        @if ($class->schoolarm)
                                            <span class="text-muted">({{ $class->schoolarm }})</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Term --}}
                    <div class="mb-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <div class="inline-check-group">
                            @foreach ($schoolterms as $term)
                                <div class="form-check">
                                    <input class="form-check-input edit-term-rb" type="radio"
                                           name="edit_termid"
                                           id="edit-term-{{ $term->id }}"
                                           value="{{ $term->id }}">
                                    <label class="form-check-label" for="edit-term-{{ $term->id }}">
                                        {{ $term->term }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Session --}}
                    <div class="mb-3">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <div class="inline-check-group">
                            @foreach ($schoolsessions as $session)
                                <div class="form-check">
                                    <input class="form-check-input edit-session-rb" type="radio"
                                           name="edit_sessionid"
                                           id="edit-session-{{ $session->id }}"
                                           value="{{ $session->id }}">
                                    <label class="form-check-label" for="edit-session-{{ $session->id }}">
                                        {{ $session->session }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Assignment</span>
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

{{-- ═══════════════════════ IMAGE PREVIEW MODAL ═════════════ --}}
<div class="modal fade" id="imageViewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:360px">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-semibold">Staff Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-2 pb-4">
                <img id="preview-image" src="" alt="Staff"
                     class="rounded-circle mb-3"
                     style="width:160px;height:160px;object-fit:cover;border:4px solid var(--ct-border);">
                <p id="preview-staffname" class="fw-semibold mb-0" style="color:var(--ct-primary)"></p>
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
    // LOADING HELPERS  (identical pattern to subjectteacher blade)
    // =========================================================================

    const PageLoader = {
        _prog: 0, _timer: null,
        show(label = 'Processing…') {
            $('#ct-loader-label').text(label);
            $('#ct-progress-bar').css('width', '0%');
            $('#ct-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#ct-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#ct-progress-bar').css('width', '100%');
            setTimeout(() => $('#ct-page-loader').removeClass('active'), 350);
        },
    };

    function showModalLoader(id, text) {
        $('#' + id + '-modal-loader-text').text(text || 'Processing…');
        $('#' + id + '-modal-loader').addClass('active');
    }
    function hideModalLoader(id) { $('#' + id + '-modal-loader').removeClass('active'); }

    function btnLoad($btn, label) {
        $btn.data('original-html', $btn.html())
            .prop('disabled', true).addClass('btn-loading');
        if (label) $btn.html('<span class="btn-text">' + label + '</span>');
    }
    function btnReset($btn) {
        var orig = $btn.data('original-html');
        if (orig) $btn.html(orig);
        $btn.prop('disabled', false).removeClass('btn-loading');
    }

    function toast(type, title, msg, duration) {
        duration = duration || 4000;
        var icons = {
            success: 'ri-checkbox-circle-fill',
            error:   'ri-close-circle-fill',
            warning: 'ri-alert-fill',
            info:    'ri-information-fill'
        };
        var id  = 'ct-toast-' + Date.now();
        var $el = $([
            '<div class="ct-toast ct-toast-' + type + '" id="' + id + '">',
            '  <span class="ct-toast-icon"><i class="' + (icons[type] || icons.info) + '"></i></span>',
            '  <div class="ct-toast-body">',
            '    <div class="ct-toast-title">' + title + '</div>',
            msg ? '    <div class="ct-toast-msg">' + msg + '</div>' : '',
            '  </div>',
            '  <button class="ct-toast-close" onclick="$(\'#' + id + '\').remove()">×</button>',
            '</div>'
        ].join(''));
        $('#ct-toast-stack').append($el);
        setTimeout(function() { $el.addClass('show'); }, 20);
        if (duration > 0) {
            setTimeout(function() {
                $el.removeClass('show');
                setTimeout(function() { $el.remove(); }, 350);
            }, duration);
        }
    }

    function showError(selector, msg) {
        $(selector).removeClass('d-none')
            .html('<i class="ri-error-warning-line me-1"></i>' + msg);
    }

    // =========================================================================
    // DATATABLE  (server-side, renders avatar with initials fallback)
    // =========================================================================

    // Avatar base URL — used inside DataTables render callback
    var AVATAR_BASE = '{{ asset("storage/staff_avatars/") }}';
    var AVATAR_DEFAULT = '{{ asset("storage/staff_avatars/unnamed.jpg") }}';

    var table = $('#classTeachersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("classteacher.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load assignments. Please refresh.');
            }
        },
        columns: [
            // Checkbox
            {
                data: 'id', orderable: false, searchable: false,
                render: function(data) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' + data + '">';
                }
            },
            // Row index
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            // Teacher — rendered with proper avatar / initials fallback
            { data: 'teacher_info', orderable: false },
            // Class badge
            { data: 'class_info', orderable: false },
            // Term badge
            { data: 'term', orderable: false },
            // Session badge
            { data: 'session', orderable: false },
            // Date
            { data: 'formatted_date', orderable: false },
            // Actions
            { data: 'action', orderable: false, searchable: false },
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing:      '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search:          '',
            searchPlaceholder: 'Search assignments…',
            lengthMenu:      'Show _MENU_ entries',
            info:            'Showing _START_–_END_ of _TOTAL_ assignments',
            infoEmpty:       'No assignments found',
            zeroRecords:     'No matching assignments',
            emptyTable:      'No class teacher assignments created yet',
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
        $.get('{{ route("classteacher.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statTeachers').text(data.stats.unique_teachers);
                $('#statClasses').text(data.stats.unique_classes);
                $('#statActive').text(data.stats.active_sessions);
            }
        }).fail(function() {
            $('#statTotal, #statTeachers, #statClasses, #statActive').text('—');
        });
    }
    loadStats();

    // =========================================================================
    // IMAGE PREVIEW MODAL
    // The controller's `data()` method renders <img> tags with data-* attrs.
    // We delegate here to handle both real avatars and initials placeholders.
    // =========================================================================

    $(document).on('click', '.ct-avatar-trigger', function() {
        var img  = $(this).data('image');      // full asset URL or empty
        var name = $(this).data('staffname') || 'Unknown';
        var has  = $(this).data('has-image');  // 'true' / 'false'

        if (has === 'true' && img) {
            $('#preview-image').attr('src', img).show();
        } else {
            // Show default unnamed placeholder
            $('#preview-image').attr('src', AVATAR_DEFAULT).show();
        }
        $('#preview-staffname').text(name);
        $('#imageViewModal').modal('show');
    });

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
    // SELECT-ALL CLASSES
    // =========================================================================

    $('#create-select-all-classes').on('change', function() {
        $('.create-class-cb').prop('checked', this.checked);
        updateCreateCount();
    });

    $('#edit-select-all-classes').on('change', function() {
        $('.edit-class-cb').prop('checked', this.checked);
    });

    function updateCreateCount() {
        $('#create-class-count').text($('.create-class-cb:checked').length);
    }

    $('#create-class-list').on('change', '.create-class-cb', function() {
        updateCreateCount();
        updateCreateBtn();
    });

    // =========================================================================
    // CREATE MODAL — guard button
    // =========================================================================

    function updateCreateBtn() {
        var ok = $('#create-staffid').val() !== '' &&
                 $('.create-class-cb:checked').length > 0 &&
                 $('.create-term-rb:checked').length > 0 &&
                 $('.create-session-rb:checked').length > 0;
        $('#create-save-btn').prop('disabled', !ok);
    }

    $('#create-staffid').on('change', updateCreateBtn);
    $('#createModal').on('change', '.create-term-rb, .create-session-rb', updateCreateBtn);

    // ── Open CREATE ───────────────────────────────────────────
    $('#createAssignmentBtn').on('click', function() {
        $('#create-staffid').val('');
        $('.create-class-cb, #create-select-all-classes').prop('checked', false);
        $('.create-term-rb, .create-session-rb').prop('checked', false);
        $('#create-class-count').text(0);
        $('#create-save-btn').prop('disabled', true);
        $('#create-error-msg').addClass('d-none').html('');
        hideModalLoader('create');
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-assignment', function() {
        var id        = $(this).data('id');
        var staffid   = $(this).data('staffid');
        var termid    = $(this).data('termid');
        var sessionid = $(this).data('sessionid');

        $('#edit-assignment-id').val(id);
        $('#edit-staffid').val(staffid);
        $('.edit-class-cb, #edit-select-all-classes').prop('checked', false);
        $('.edit-term-rb, .edit-session-rb').prop('checked', false);

        $('#edit-term-' + termid).prop('checked', true);
        $('#edit-session-' + sessionid).prop('checked', true);

        $('#edit-error-msg').addClass('d-none').html('');
        hideModalLoader('edit');
        btnReset($('#edit-update-btn'));

        // Load existing class assignments for this teacher / term / session
        $.get('{{ url("classteacher/assignments") }}/' + staffid + '/' + termid + '/' + sessionid,
            function(res) {
                if (res.success && res.classIds) {
                    $.each(res.classIds, function(i, classId) {
                        $('#edit-cls-' + classId).prop('checked', true);
                    });
                }
            }
        );

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // =========================================================================
    // SUBMIT: CREATE
    // =========================================================================

    $('#createForm').on('submit', function(e) {
        e.preventDefault();

        var staffid   = $('#create-staffid').val();
        var classids  = $('.create-class-cb:checked').map(function() { return this.value; }).get();
        var termid    = $('.create-term-rb:checked').val();
        var sessionid = $('.create-session-rb:checked').val();

        if (!staffid)       { showError('#create-error-msg', 'Please select a teacher.');         return; }
        if (!classids.length){ showError('#create-error-msg', 'Please select at least one class.'); return; }
        if (!termid)        { showError('#create-error-msg', 'Please select a term.');             return; }
        if (!sessionid)     { showError('#create-error-msg', 'Please select a session.');          return; }

        btnLoad($('#create-save-btn'), 'Saving…');
        showModalLoader('create', 'Saving assignment(s)…');
        $('#create-error-msg').addClass('d-none').html('');

        $.ajax({
            url:  '{{ route("classteacher.store") }}',
            type: 'POST',
            data: {
                staffid:        staffid,
                'schoolclassid[]': classids,
                termid:         termid,
                sessionid:      sessionid,
                _token:         CSRF,
            },
            traditional: true,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                if (res.success) {
                    $('#createModal').modal('hide');
                    toast('success', 'Saved!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('create');
                    btnReset($('#create-save-btn'));
                    updateCreateBtn();
                    showError('#create-error-msg', res.message || 'Could not save assignment.');
                }
            },

            error: function(xhr) {
                hideModalLoader('create');
                btnReset($('#create-save-btn'));
                updateCreateBtn();
                var json = xhr.responseJSON;
                var msg = (json && json.message) ||
                          (json && json.errors && Object.values(json.errors).flat().join(', ')) ||
                          'An error occurred.';
                showError('#create-error-msg', msg);
                toast('error', 'Failed', msg);
            },
        });
    });

    // =========================================================================
    // SUBMIT: EDIT
    // =========================================================================

    $('#editForm').on('submit', function(e) {
        e.preventDefault();

        var id        = $('#edit-assignment-id').val();
        var staffid   = $('#edit-staffid').val();
        var classids  = $('.edit-class-cb:checked').map(function() { return this.value; }).get();
        var termid    = $('.edit-term-rb:checked').val();
        var sessionid = $('.edit-session-rb:checked').val();

        if (!staffid)        { showError('#edit-error-msg', 'Please select a teacher.');         return; }
        if (!classids.length){ showError('#edit-error-msg', 'Please select at least one class.'); return; }
        if (!termid)         { showError('#edit-error-msg', 'Please select a term.');             return; }
        if (!sessionid)      { showError('#edit-error-msg', 'Please select a session.');          return; }

        btnLoad($('#edit-update-btn'), 'Updating…');
        showModalLoader('edit', 'Saving changes…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url:  '{{ url("classteacher") }}/' + id,
            type: 'POST',
            data: {
                staffid:           staffid,
                'schoolclassid[]': classids,
                termid:            termid,
                sessionid:         sessionid,
                _token:            CSRF,
                _method:           'PUT',
            },
            traditional: true,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                if (res.success) {
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('edit');
                    btnReset($('#edit-update-btn'));
                    showError('#edit-error-msg', res.message || 'Could not update assignment.');
                }
            },

            error: function(xhr) {
                hideModalLoader('edit');
                btnReset($('#edit-update-btn'));
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

    $(document).on('click', '.delete-assignment', function() {
        deleteId = $(this).data('id');
        $('#delete-item-title').text($(this).data('title') || 'this assignment');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url:  '{{ url("classteacher") }}/' + deleteId,
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
        var ids = $('.row-checkbox:checked').map(function() { return this.value; }).get();
        if (!ids.length) return;

        Swal.fire({
            title: 'Delete ' + ids.length + ' assignment(s)?',
            text:  'This will permanently remove the selected class teacher assignments.',
            icon:  'warning',
            showCancelButton:    true,
            confirmButtonColor:  '#dc2626',
            confirmButtonText:   'Yes, delete all',
            cancelButtonText:    'Cancel',
        }).then(function(result) {
            if (!result.isConfirmed) return;

            PageLoader.show('Deleting assignments…');

            $.ajax({
                url:  '{{ route("classteacher.bulk-destroy") }}',
                type: 'POST',
                data: { ids: ids, _token: CSRF },
                traditional: true,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },

                success: function(res) {
                    PageLoader.hide();
                    if (res.success) {
                        toast('success', 'Deleted!', res.message);
                        table.ajax.reload();
                        loadStats();
                        $('#selectAll').prop('checked', false);
                        updateBulkBar();
                    } else {
                        toast('error', 'Failed', res.message || 'Could not delete assignments.');
                    }
                },

                error: function() {
                    PageLoader.hide();
                    toast('error', 'Error', 'Failed to delete selected assignments.');
                },
            });
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection
