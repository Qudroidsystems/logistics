{{-- resources/views/subjectteacher/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --st-primary:  #1e3a5f;
    --st-accent:   #2563eb;
    --st-success:  #16a34a;
    --st-warning:  #d97706;
    --st-danger:   #dc2626;
    --st-muted:    #6b7280;
    --st-border:   #e2e8f0;
    --st-bg:       #f8fafc;
    --st-radius:   12px;
    --st-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.st-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #0891b2 60%, #0d9488 100%);
    border-radius: var(--st-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.st-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.st-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.st-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.st-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--st-border);
    border-radius:var(--st-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--st-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--st-primary); }
.stat-card .stat-label { font-size:12px; color:var(--st-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.st-table th {
    background:var(--st-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.st-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--st-border); font-size:13px;
}
.st-table tr:hover td { background:#f0f9ff; }

/* ── Badges ──────────────────────────────────────────────── */
.st-badge {
    display:inline-flex; align-items:center;
    padding:3px 9px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.st-badge-session { background:#ccfbf1; color:#0f766e; }

/* ── Term badges ─────────────────────────────────────────── */
.st-badge-term {
    margin:1px 2px;
}
.st-badge-term-first  { background:#dcfce7; color:#16a34a; }
.st-badge-term-second { background:#dbeafe; color:#2563eb; }
.st-badge-term-third  { background:#fee2e2; color:#dc2626; }
.st-badge-term-other  { background:#f3f4f6; color:#6b7280; }

/* ── Avatar ──────────────────────────────────────────────── */
.teacher-avatar {
    width:36px; height:36px; border-radius:50%;
    object-fit:cover; border:2px solid var(--st-border);
    cursor:pointer; transition:border-color .15s;
}
.teacher-avatar:hover { border-color:var(--st-accent); }

.avatar-initials {
    width:36px; height:36px; border-radius:50%;
    background:linear-gradient(135deg, #1e3a5f 0%, #0891b2 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:700; font-size:13px; letter-spacing:.5px;
    border:2px solid var(--st-border);
    cursor:pointer; flex-shrink:0; user-select:none;
    transition:border-color .15s, transform .15s;
}
.avatar-initials:hover { border-color:var(--st-accent); transform:scale(1.08); }

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--st-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--st-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--st-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--st-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--st-accent) !important;
    border-color:var(--st-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.st-modal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, #1e3a5f 0%, #0891b2 100%);
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
    border:1.5px solid var(--st-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--st-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Checkbox / radio scroll area ────────────────────────── */
.checkbox-scroll {
    max-height:200px; overflow-y:auto;
    border:1.5px solid var(--st-border); border-radius:8px;
    padding:10px 14px; background:#fafbfc;
}
.checkbox-scroll .form-check { padding:5px 0; border-bottom:1px solid #f0f0f0; }
.checkbox-scroll .form-check:last-child { border-bottom:none; }
.checkbox-scroll .form-check-label { font-size:13px; cursor:pointer; }
.checkbox-scroll .form-check-input:checked {
    background-color:var(--st-accent); border-color:var(--st-accent);
}

/* ── Term / Session inline group ─────────────────────────── */
.inline-check-group {
    display:flex; flex-wrap:wrap; gap:8px;
    padding:10px 14px;
    border:1.5px solid var(--st-border); border-radius:8px;
    background:#fafbfc;
}
.inline-check-group .form-check { margin:0; }
.inline-check-group .form-check-label { font-size:13px; cursor:pointer; }
.inline-check-group .form-check-input:checked {
    background-color:var(--st-accent); border-color:var(--st-accent);
}

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#st-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#st-page-loader.active { opacity:1; visibility:visible; }

.st-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22);
    min-width:220px;
}
.st-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0;
    border-top-color:var(--st-accent);
    border-radius:50%;
    animation:st-spin .75s linear infinite;
}
@keyframes st-spin { to { transform:rotate(360deg); } }

.st-loader-label {
    font-size:14px; font-weight:600;
    color:var(--st-primary); margin-bottom:12px;
}
.st-progress-wrap {
    width:160px; height:5px;
    background:#e2e8f0; border-radius:99px; overflow:hidden;
    margin:0 auto;
}
.st-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--st-accent), #0d9488);
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
.modal-body-loader .inner { display:flex; flex-direction:column; align-items:center; gap:10px; }
.modal-body-loader .mbl-spinner {
    width:36px; height:36px;
    border:3px solid #e2e8f0;
    border-top-color:var(--st-accent);
    border-radius:50%;
    animation:st-spin .7s linear infinite;
}
.modal-body-loader .mbl-text { font-size:13px; font-weight:600; color:var(--st-primary); }

/* ── Toast notifications ─────────────────────────────────── */
#st-toast-stack {
    position:fixed; bottom:24px; right:24px;
    z-index:10000; display:flex;
    flex-direction:column-reverse; gap:10px;
    pointer-events:none;
}
.st-toast {
    pointer-events:all;
    background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--st-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.st-toast.show { transform:translateX(0); }
.st-toast.st-toast-success { border-left-color:var(--st-success); }
.st-toast.st-toast-error   { border-left-color:var(--st-danger);  }
.st-toast.st-toast-warning { border-left-color:var(--st-warning); }
.st-toast .st-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.st-toast-success .st-toast-icon { color:var(--st-success); }
.st-toast-error   .st-toast-icon { color:var(--st-danger);  }
.st-toast-warning .st-toast-icon { color:var(--st-warning); }
.st-toast .st-toast-body { flex:1; }
.st-toast .st-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.st-toast .st-toast-msg   { font-size:12px; color:var(--st-muted); line-height:1.4; }
.st-toast .st-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--st-muted); font-size:16px; line-height:1;
    padding:0; flex-shrink:0;
}

/* ── Button loading state ────────────────────────────────── */
.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:''; position:absolute; inset:0;
    margin:auto; width:16px; height:16px;
    border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%;
    animation:st-spin .65s linear infinite;
}
.btn-loading.btn-outline-secondary::after,
.btn-loading.btn-outline-danger::after { border-top-color:currentColor; }
.btn-loading.btn-light::after { border-top-color:#374151; }

/* ── Subject search inside modal ─────────────────────────── */
.modal-search-input {
    border:1.5px solid var(--st-border); border-radius:8px;
    padding:7px 12px; font-size:12px; width:100%;
    margin-bottom:8px; transition:border .15s;
}
.modal-search-input:focus {
    border-color:var(--st-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="st-page-loader">
    <div class="st-loader-card">
        <div class="st-loader-spinner"></div>
        <div class="st-loader-label" id="st-loader-label">Processing…</div>
        <div class="st-progress-wrap">
            <div class="st-progress-bar" id="st-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast stack ═══ --}}
<div id="st-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="st-hero">
        <h1><i class="ri-user-star-line me-2"></i>Subject Teacher Management</h1>
        <p>Assign teachers to subjects across terms and sessions.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-links-line"></i></div>
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
                <div class="stat-icon"><i class="ri-flask-line"></i></div>
                <div class="stat-value text-success" id="statSubjects">—</div>
                <div class="stat-label">Subjects Covered</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-line"></i></div>
                <div class="stat-value text-warning" id="statSessions">—</div>
                <div class="stat-label">Sessions Active</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--st-primary)">
                    <i class="ri-list-check me-2"></i>Subject Teacher Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create subject-teacher')
                    <button class="btn btn-primary" id="createSubjectTeacherBtn">
                        <i class="ri-add-line me-1"></i>Create Subject Teacher
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
                <table class="table st-table w-100 mb-0" id="subjectTeacherTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Teacher</th>
                            <th>Subject</th>
                            <th>Term(s)</th>
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

{{-- ═══════════════════════ ADD MODAL ═══════════════════════ --}}
<div class="modal fade st-modal" id="addSubjectTeacherModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-circle-line me-2"></i>Add Subject Teacher</h5>
            </div>
            <form id="add-subjectteacher-form" autocomplete="off">
                @csrf
                <div class="modal-body-loader" id="add-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="add-modal-loader-text">Saving…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    {{-- Teacher --}}
                    <div class="mb-3">
                        <label class="form-label">Teacher <span class="text-danger">*</span></label>
                        <select name="staffid" id="add-staffid" class="form-select" required>
                            <option value="">— Select Teacher —</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->userid }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Subjects --}}
                    <div class="mb-3">
                        <label class="form-label">Subject(s) <span class="text-danger">*</span></label>
                        <input type="text" id="add-subject-search" class="modal-search-input"
                               placeholder="🔍  Filter subjects…">
                        <div class="checkbox-scroll" id="add-subject-list">
                            @foreach ($subjects->sortBy('subject') as $subject)
                                <div class="form-check subject-item">
                                    <input class="form-check-input add-subject-checkbox"
                                           type="checkbox"
                                           name="subjectids[]"
                                           id="add-subj-{{ $subject->id }}"
                                           value="{{ $subject->id }}"
                                           data-label="{{ $subject->subject }}">
                                    <label class="form-check-label" for="add-subj-{{ $subject->id }}">
                                        <strong>{{ $subject->subject }}</strong>
                                        <small class="text-muted">({{ $subject->subject_code }})</small>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <span id="add-subject-count">0</span> subject(s) selected
                        </small>
                    </div>

                    {{-- Terms --}}
                    <div class="mb-3">
                        <label class="form-label">Term(s) <span class="text-danger">*</span></label>
                        <div class="inline-check-group">
                            @foreach ($terms as $term)
                                @php
                                    $tClass = match(true) {
                                        str_contains($term->term, 'First')  => 'term-first',
                                        str_contains($term->term, 'Second') => 'term-second',
                                        str_contains($term->term, 'Third')  => 'term-third',
                                        default => 'term-other'
                                    };
                                @endphp
                                <div class="form-check">
                                    <input class="form-check-input add-term-checkbox"
                                           type="checkbox"
                                           name="termid[]"
                                           id="add-term-{{ $term->id }}"
                                           value="{{ $term->id }}">
                                    <label class="form-check-label" for="add-term-{{ $term->id }}">
                                        <span class="st-badge st-badge-term {{ $tClass }}">{{ $term->term }}</span>
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
                                    <input class="form-check-input"
                                           type="radio"
                                           name="sessionid"
                                           id="add-session-{{ $session->id }}"
                                           value="{{ $session->id }}"
                                           required>
                                    <label class="form-check-label" for="add-session-{{ $session->id }}">
                                        {{ $session->session }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="alert alert-danger d-none" id="add-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Assignment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade st-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Subject Teacher</h5>
            </div>
            <form id="edit-subjectteacher-form" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-id">
                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    {{-- Teacher --}}
                    <div class="mb-3">
                        <label class="form-label">Teacher <span class="text-danger">*</span></label>
                        <select name="staffid" id="edit-staffid" class="form-select" required>
                            <option value="">— Select Teacher —</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->userid }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Subjects --}}
                    <div class="mb-3">
                        <label class="form-label">Subject(s) <span class="text-danger">*</span></label>
                        <input type="text" id="edit-subject-search" class="modal-search-input"
                               placeholder="🔍  Filter subjects…">
                        <div class="checkbox-scroll" id="edit-subject-list">
                            @foreach ($subjects->sortBy('subject') as $subject)
                                <div class="form-check subject-item">
                                    <input class="form-check-input edit-subject-checkbox"
                                           type="checkbox"
                                           name="subjectids[]"
                                           id="edit-subj-{{ $subject->id }}"
                                           value="{{ $subject->id }}"
                                           data-label="{{ $subject->subject }}">
                                    <label class="form-check-label" for="edit-subj-{{ $subject->id }}">
                                        <strong>{{ $subject->subject }}</strong>
                                        <small class="text-muted">({{ $subject->subject_code }})</small>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Terms --}}
                    <div class="mb-3">
                        <label class="form-label">Term(s) <span class="text-danger">*</span></label>
                        <div class="inline-check-group">
                            @foreach ($terms as $term)
                                @php
                                    $tClass = match(true) {
                                        str_contains($term->term, 'First')  => 'term-first',
                                        str_contains($term->term, 'Second') => 'term-second',
                                        str_contains($term->term, 'Third')  => 'term-third',
                                        default => 'term-other'
                                    };
                                @endphp
                                <div class="form-check">
                                    <input class="form-check-input edit-term-checkbox"
                                           type="checkbox"
                                           name="termid[]"
                                           id="edit-term-{{ $term->id }}"
                                           value="{{ $term->id }}">
                                    <label class="form-check-label" for="edit-term-{{ $term->id }}">
                                        <span class="st-badge st-badge-term {{ $tClass }}">{{ $term->term }}</span>
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
                                    <input class="form-check-input"
                                           type="radio"
                                           name="sessionid"
                                           id="edit-session-{{ $session->id }}"
                                           value="{{ $session->id }}"
                                           required>
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
                    <button type="submit" class="btn btn-primary" id="update-btn">
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
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Remove <strong id="delete-subject-name"></strong> from
                   <strong id="delete-teacher-name"></strong>'s assignments?</p>
                <p class="text-muted small mb-0">This may affect related class assignments.</p>
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
            $('#st-loader-label').text(label);
            $('#st-progress-bar').css('width', '0%');
            $('#st-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#st-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#st-progress-bar').css('width', '100%');
            setTimeout(() => $('#st-page-loader').removeClass('active'), 350);
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
        const id  = 'st-toast-' + Date.now();
        const $el = $(`
            <div class="st-toast st-toast-${type}" id="${id}">
                <span class="st-toast-icon"><i class="${icons[type] || icons.info}"></i></span>
                <div class="st-toast-body">
                    <div class="st-toast-title">${title}</div>
                    ${msg ? `<div class="st-toast-msg">${msg}</div>` : ''}
                </div>
                <button class="st-toast-close" onclick="$('#${id}').remove()">×</button>
            </div>
        `);
        $('#st-toast-stack').append($el);
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

    var table = $('#subjectTeacherTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("subjectteacher.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load subject teachers. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'teacher_info', orderable: false },
            { data: 'subject_info', orderable: false },
            { data: 'term_info', orderable: false },
            { data: 'session_info', orderable: false },
            { data: 'formatted_date', orderable: false },
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
            emptyTable:      'No subject teacher assignments yet',
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
        $.get('{{ route("subjectteacher.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statTeachers').text(data.stats.unique_teachers);
                $('#statSubjects').text(data.stats.unique_subjects);
                $('#statSessions').text(data.stats.unique_sessions);
            }
        }).fail(function() {
            $('#statTotal, #statTeachers, #statSubjects, #statSessions').text('—');
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
    // SUBJECT SEARCH FILTER (inside modals)
    // =========================================================================

    $('#add-subject-search').on('input', function () {
        const q = $(this).val().toLowerCase();
        $('#add-subject-list .subject-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    $('#edit-subject-search').on('input', function () {
        const q = $(this).val().toLowerCase();
        $('#edit-subject-list .subject-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // =========================================================================
    // ADD MODAL — guard button
    // =========================================================================

    function updateAddBtn() {
        const ok = $('#add-staffid').val() !== '' &&
                   $('.add-subject-checkbox:checked').length > 0 &&
                   $('.add-term-checkbox:checked').length > 0 &&
                   $('input[name="sessionid"]:checked').length > 0;
        $('#add-btn').prop('disabled', !ok);
    }

    $('#add-staffid, #addSubjectTeacherModal input').on('change', updateAddBtn);
    $('#add-subject-list').on('change', '.add-subject-checkbox', function () {
        $('#add-subject-count').text($('.add-subject-checkbox:checked').length);
        updateAddBtn();
    });

    // ── Open ADD ─────────────────────────────────────────────────
    $('#createSubjectTeacherBtn').on('click', function () {
        $('#add-staffid').val('');
        $('.add-subject-checkbox, .add-term-checkbox').prop('checked', false);
        $('input[name="sessionid"]').prop('checked', false);
        $('#add-subject-count').text(0);
        $('#add-btn').prop('disabled', true);
        $('#add-error-msg').addClass('d-none').html('');
        $('#add-subject-search').val('');
        $('#add-subject-list .subject-item').show();
        hideModalLoader('add');
        new bootstrap.Modal(document.getElementById('addSubjectTeacherModal')).show();
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-st-btn', function () {
        const id        = $(this).data('id');
        const staffid   = $(this).data('staffid');
        const subjectid = $(this).data('subjectid');
        const sessionid = $(this).data('sessionid');
        const termids   = String($(this).data('termids') || '').split(',').map(s => s.trim()).filter(s => s);

        $('#edit-id').val(id);
        $('#edit-staffid').val(staffid);

        // Reset all checkboxes/radios then restore saved values
        $('.edit-subject-checkbox').prop('checked', false);
        $('.edit-term-checkbox').prop('checked', false);
        $('input[name="sessionid"]').prop('checked', false);

        if (subjectid) {
            $(`#edit-subj-${subjectid}`).prop('checked', true);
        }
        termids.forEach(function(tid) {
            if (tid) $(`#edit-term-${tid}`).prop('checked', true);
        });
        if (sessionid) {
            $(`#edit-session-${sessionid}`).prop('checked', true);
        }

        $('#edit-error-msg').addClass('d-none').html('');
        $('#edit-subject-search').val('');
        $('#edit-subject-list .subject-item').show();
        hideModalLoader('edit');
        btnReset('#update-btn');

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // =========================================================================
    // SUBMIT: ADD
    // =========================================================================

    $('#add-subjectteacher-form').on('submit', function (e) {
        e.preventDefault();

        const staffid    = $('#add-staffid').val();
        const subjectids = $('.add-subject-checkbox:checked').map((i, el) => el.value).get();
        const termids    = $('.add-term-checkbox:checked').map((i, el) => el.value).get();
        const sessionid  = $('input[name="sessionid"]:checked').val();

        if (!staffid) { showError('#add-error-msg', 'Please select a teacher.'); return; }
        if (!subjectids.length) { showError('#add-error-msg', 'Please select at least one subject.'); return; }
        if (!termids.length) { showError('#add-error-msg', 'Please select at least one term.'); return; }
        if (!sessionid) { showError('#add-error-msg', 'Please select a session.'); return; }

        btnLoad('#add-btn', 'Adding…');
        showModalLoader('add', `Adding ${subjectids.length * termids.length} assignment(s)…`);
        $('#add-error-msg').addClass('d-none').html('');

        $.ajax({
            url:  '{{ route("subjectteacher.store") }}',
            type: 'POST',
            data: {
                staffid: staffid,
                'subjectids[]': subjectids,
                'termid[]': termids,
                sessionid: sessionid,
                _token: CSRF,
            },
            traditional: true,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                if (res.success) {
                    $('#addSubjectTeacherModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('add');
                    btnReset('#add-btn');
                    updateAddBtn();
                    showError('#add-error-msg', res.message || 'Could not add assignment.');
                }
            },

            error: function(xhr) {
                hideModalLoader('add');
                btnReset('#add-btn');
                updateAddBtn();
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

    $('#edit-subjectteacher-form').on('submit', function (e) {
        e.preventDefault();

        const id         = $('#edit-id').val();
        const staffid    = $('#edit-staffid').val();
        const subjectids = $('.edit-subject-checkbox:checked').map((i, el) => el.value).get();
        const termids    = $('.edit-term-checkbox:checked').map((i, el) => el.value).get();
        const sessionid  = $('input[name="sessionid"]:checked').val();

        if (!staffid) { showError('#edit-error-msg', 'Please select a teacher.'); return; }
        if (!subjectids.length) { showError('#edit-error-msg', 'Please select at least one subject.'); return; }
        if (!termids.length) { showError('#edit-error-msg', 'Please select at least one term.'); return; }
        if (!sessionid) { showError('#edit-error-msg', 'Please select a session.'); return; }

        btnLoad('#update-btn', 'Updating…');
        showModalLoader('edit', 'Saving changes…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url:  `{{ url('subjectteacher') }}/${id}`,
            type: 'POST',
            data: {
                staffid: staffid,
                'subjectids[]': subjectids,
                'termid[]': termids,
                sessionid: sessionid,
                _token: CSRF,
                _method: 'PUT',
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

    $(document).on('click', '.delete-st-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-subject-name').text($(this).data('subject') || 'this subject');
        $('#delete-teacher-name').text($(this).data('teacher') || 'this teacher');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: `{{ url('subjectteacher') }}/${deleteId}`,
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
            toast('warning', 'No Selection', 'Please select at least one assignment to delete.');
            return;
        }

        Swal.fire({
            title: 'Delete ' + ids.length + ' assignment(s)?',
            html: 'This will permanently remove the selected subject teacher assignments.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting assignments…');
                    
                    $.ajax({
                        url: '{{ route("subjectteacher.bulk-destroy") }}',
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
                                reject(res.message || 'Failed to delete assignments');
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
                toast('success', 'Deleted!', result.value.message || 'Assignments deleted successfully.');
                table.ajax.reload();
                loadStats();
                $('#selectAll').prop('checked', false);
                updateBulkBar();
            }
        }).catch(function(error) {
            toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete assignments.');
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection