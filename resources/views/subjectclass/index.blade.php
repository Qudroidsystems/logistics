{{-- resources/views/subjectclass/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sc-primary:  #1e3a5f;
    --sc-accent:   #2563eb;
    --sc-success:  #16a34a;
    --sc-warning:  #d97706;
    --sc-danger:   #dc2626;
    --sc-muted:    #6b7280;
    --sc-border:   #e2e8f0;
    --sc-bg:       #f8fafc;
    --sc-radius:   12px;
    --sc-shadow:   0 2px 8px rgba(0,0,0,.08);
}
.sc-hero {
    background: linear-gradient(135deg, var(--sc-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sc-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.sc-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%; }
.sc-hero::after { content:''; position:absolute; bottom:-80px; left:-30px; width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%; }
.sc-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sc-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.stat-card { background:#fff; border:1px solid var(--sc-border); border-radius:var(--sc-radius); padding:18px 20px; transition:transform .15s, box-shadow .15s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sc-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sc-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sc-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.sc-table th { background:var(--sc-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.sc-table td { padding:11px 16px; vertical-align:middle; border-bottom:1px solid var(--sc-border); font-size:13px; }
.sc-table tr:hover td { background:#eff6ff; }

.sc-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.sc-badge-class { background:#dbeafe; color:#2563eb; }
.sc-badge-session { background:#ccfbf1; color:#0f766e; }
.sc-badge-term-first  { background:#dcfce7; color:#16a34a; }
.sc-badge-term-second { background:#dbeafe; color:#2563eb; }
.sc-badge-term-third  { background:#fee2e2; color:#dc2626; }
.sc-badge-term-other  { background:#f3f4f6; color:#6b7280; }

.avatar-initials {
    width:36px; height:36px; border-radius:50%;
    background:linear-gradient(135deg, #1e3a5f 0%, #0891b2 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:700; font-size:13px; letter-spacing:.5px;
    border:2px solid var(--sc-border); flex-shrink:0; user-select:none;
}
.teacher-avatar {
    width:36px; height:36px; border-radius:50%;
    object-fit:cover; border:2px solid var(--sc-border); flex-shrink:0;
}

.dataTables_wrapper .dataTables_filter input { border:1.5px solid var(--sc-border); border-radius:8px; padding:7px 14px; margin-left:8px; font-size:13px; }
.dataTables_wrapper .dataTables_filter input:focus { border-color:var(--sc-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.dataTables_wrapper .dataTables_length select { border:1.5px solid var(--sc-border); border-radius:8px; padding:6px 10px; margin:0 6px; font-size:13px; }
.dataTables_wrapper .paginate_button.current, .dataTables_wrapper .paginate_button.current:hover { background:var(--sc-accent) !important; border-color:var(--sc-accent) !important; color:#fff !important; }

.sc-modal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar { background:linear-gradient(135deg, var(--sc-primary) 0%, #2563eb 100%); padding:22px 28px; position:relative; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border:1.5px solid var(--sc-border); border-radius:8px; font-size:13px; padding:9px 14px; }
.form-control:focus, .form-select:focus { border-color:var(--sc-accent); box-shadow:0 0 0 3px rgba(37,99,235,.1); }

.checkbox-scroll { max-height:220px; overflow-y:auto; border:1.5px solid var(--sc-border); border-radius:8px; padding:10px 14px; background:#fafbfc; }
.checkbox-scroll .form-check { padding:5px 0; border-bottom:1px solid #f0f0f0; }
.checkbox-scroll .form-check:last-child { border-bottom:none; }
.checkbox-scroll .form-check-label { font-size:13px; cursor:pointer; }
.checkbox-scroll .form-check-input:checked { background-color:var(--sc-accent); border-color:var(--sc-accent); }

/* Select-all bar + class list filter */
.select-all-bar { background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:8px; padding:7px 12px; margin-bottom:6px; display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--sc-accent); cursor:pointer; }
.class-item.hidden-by-filter { display:none !important; }

/* Filter chip group */
.filter-chip-group {
    display:flex; flex-wrap:wrap; gap:6px;
    padding:10px 12px;
    border:1.5px solid var(--sc-border); border-radius:8px;
    background:#fafbfc;
    min-height:44px;
}
.filter-chip-group .form-check { margin:0; }
.filter-chip-group .form-check-input { display:none; }
.filter-chip-group .form-check-label {
    display:inline-flex; align-items:center;
    padding:4px 12px; border-radius:20px;
    font-size:12px; font-weight:600;
    border:1.5px solid var(--sc-border);
    background:#fff; color:var(--sc-muted);
    cursor:pointer; user-select:none;
    transition:all .15s;
}
.filter-chip-group .form-check-label:hover { border-color:var(--sc-accent); color:var(--sc-accent); }
.filter-chip-group .form-check-input:checked + .form-check-label {
    background:var(--sc-accent); color:#fff; border-color:var(--sc-accent);
    box-shadow:0 2px 6px rgba(37,99,235,.25);
}

.filter-toolbar {
    display:flex; flex-wrap:wrap; gap:10px;
    align-items:flex-end; margin-bottom:10px;
}
.filter-toolbar .filter-col { flex:1 1 200px; min-width:180px; }
.filter-toolbar .filter-col.full { flex:1 1 100%; }

.filter-summary {
    font-size:11px; color:var(--sc-muted);
    background:#eff6ff; border:1px solid #bfdbfe;
    border-radius:6px; padding:6px 10px;
    display:flex; align-items:center; gap:6px;
    margin-bottom:8px;
}
.filter-summary i { color:var(--sc-accent); }
.filter-summary strong { color:var(--sc-primary); }

.filter-clear-btn {
    background:none; border:none; color:var(--sc-danger);
    font-size:11px; font-weight:600; cursor:pointer;
    padding:2px 6px; border-radius:4px;
}
.filter-clear-btn:hover { background:#fee2e2; }

.sc-context-box { background: var(--sc-bg); border: 1.5px solid var(--sc-border); border-radius: 10px; padding: 14px 18px; }
.sc-context-box .context-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--sc-muted); margin-bottom: 4px; }
.sc-context-box .context-value { font-size: 13px; font-weight: 600; color: var(--sc-primary); }
.sc-context-box .context-sub { font-size: 11px; color: var(--sc-muted); }
.sc-lock-note { font-size: 11px; color: var(--sc-muted); text-align: center; margin-top: 8px; }

.bulk-bar { background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:10px 16px; display:none; align-items:center; gap:12px; margin-bottom:12px; }
.bulk-bar.show { display:flex; }

#sc-page-loader { position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; opacity:0; visibility:hidden; transition:opacity .22s, visibility .22s; }
#sc-page-loader.active { opacity:1; visibility:visible; }
.sc-loader-card { background:#fff; border-radius:16px; padding:32px 40px; text-align:center; box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px; }
.sc-loader-spinner { width:52px; height:52px; margin:0 auto 16px; border:4px solid #e2e8f0; border-top-color:var(--sc-accent); border-radius:50%; animation:sc-spin .75s linear infinite; }
@keyframes sc-spin { to { transform:rotate(360deg); } }
.sc-loader-label { font-size:14px; font-weight:600; color:var(--sc-primary); margin-bottom:12px; }

#sc-toast-stack { position:fixed; bottom:24px; right:24px; z-index:10000; display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none; }
.sc-toast { pointer-events:all; background:#fff; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.14); padding:14px 18px; min-width:280px; max-width:360px; display:flex; align-items:flex-start; gap:12px; border-left:4px solid var(--sc-accent); transform:translateX(120%); transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.sc-toast.show { transform:translateX(0); }
.sc-toast-success { border-left-color:var(--sc-success); }
.sc-toast-error   { border-left-color:var(--sc-danger);  }
.sc-toast-warning { border-left-color:var(--sc-warning); }
.sc-toast .sc-toast-icon { font-size:20px; flex-shrink:0; }
.sc-toast .sc-toast-body { flex:1; }
.sc-toast .sc-toast-title { font-size:13px; font-weight:700; color:#111827; }
.sc-toast .sc-toast-msg   { font-size:12px; color:var(--sc-muted); }
.sc-toast .sc-toast-close { background:none; border:none; cursor:pointer; color:var(--sc-muted); font-size:16px; }

.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after { content:''; position:absolute; inset:0; margin:auto; width:16px; height:16px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:sc-spin .65s linear infinite; }

/* Modal loader overlay */
.modal-body-loader { display:none; position:absolute; inset:0; background:rgba(255,255,255,.85); z-index:5; align-items:center; justify-content:center; }
.modal-body-loader.active { display:flex; }
.modal-body-loader .inner { text-align:center; }
.modal-body-loader .mbl-spinner { width:42px; height:42px; margin:0 auto 12px; border:3px solid #e2e8f0; border-top-color:var(--sc-accent); border-radius:50%; animation:sc-spin .75s linear infinite; }
.modal-body-loader .mbl-text { font-size:13px; font-weight:600; color:var(--sc-primary); }

.teacher-item.hidden-by-filter { display:none !important; }
.no-teachers-msg {
    text-align:center; padding:22px 10px; color:var(--sc-muted); font-size:13px;
}
.no-teachers-msg i { font-size:28px; display:block; margin-bottom:6px; opacity:.4; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<div id="sc-page-loader"><div class="sc-loader-card"><div class="sc-loader-spinner"></div><div class="sc-loader-label" id="sc-loader-label">Processing…</div></div></div>
<div id="sc-toast-stack"></div>

<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="sc-hero">
        <h1><i class="ri-book-open-line me-2"></i>Subject Class Management</h1>
        <p>Assign subject teachers to classes across terms and sessions.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-book-2-line"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Assignments</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-user-star-line"></i></div><div class="stat-value text-primary" id="statTeachers">—</div><div class="stat-label">Unique Teachers</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-building-line"></i></div><div class="stat-value text-success" id="statClasses">—</div><div class="stat-label">Classes Covered</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-flask-line"></i></div><div class="stat-value text-warning" id="statSubjects">—</div><div class="stat-label">Unique Subjects</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sc-primary)">
                    <i class="ri-list-check me-2"></i>Subject Class Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
                    @can('Create subject-class')
                    <button class="btn btn-primary" id="createSubjectClassBtn"><i class="ri-add-line me-1"></i>Create Subject Class</button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> assignment(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table sc-table w-100 mb-0" id="subjectClassTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>#</th>
                            <th>Teacher</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Session</th>
                            <th>Registrations</th>
                            <th>Updated</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div></div></div>

{{-- ADD MODAL --}}
<div class="modal fade sc-modal" id="addSubjectClassModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-circle-line me-2"></i>Add Subject Class</h5>
            </div>
            <form id="add-subjectclass-form" autocomplete="off">
                @csrf
                <div class="modal-body p-4" style="position:relative">
                    <div class="modal-body-loader" id="add-modal-loader">
                        <div class="inner">
                            <div class="mbl-spinner"></div>
                            <div class="mbl-text" id="add-modal-loader-text">Saving…</div>
                        </div>
                    </div>

                    {{-- CLASSES (multi-select checkboxes) --}}
                    <div class="mb-3">
                        <label class="form-label">Classes <span class="text-danger">*</span></label>
                        <input type="text" id="add-class-search" class="form-control form-control-sm mb-2"
                               placeholder="🔍  Search classes…">
                        <div class="select-all-bar">
                            <input type="checkbox" class="form-check-input" id="add-select-all-classes">
                            <label for="add-select-all-classes" class="mb-0">Select all (visible) classes</label>
                        </div>
                        <div class="checkbox-scroll" id="add-class-list">
                            @foreach ($schoolclasses as $class)
                                <div class="form-check class-item"
                                     data-search-text="{{ strtolower($class->schoolclass . ' ' . $class->arm) }}">
                                    <input class="form-check-input add-class-checkbox" type="checkbox"
                                           id="add-c-{{ $class->id }}" value="{{ $class->id }}">
                                    <label class="form-check-label" for="add-c-{{ $class->id }}">
                                        {{ $class->schoolclass }} {{ $class->arm }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <span id="add-class-count">0</span> class(es) selected
                        </small>
                    </div>

                    {{-- FILTER TOOLBAR --}}
                    <div class="mb-3">
                        <label class="form-label">Filter Subject Teachers</label>

                        <div class="filter-toolbar">
                            <div class="filter-col full">
                                <input type="text" id="add-teacher-search"
                                       class="form-control form-control-sm"
                                       placeholder="🔍  Search teacher or subject…">
                            </div>

                            <div class="filter-col">
                                <small class="d-block mb-1" style="font-size:11px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.4px;">
                                    Session
                                </small>
                                <div class="filter-chip-group" id="add-session-filter">
                                    @foreach ($filterSessions as $sess)
                                        <div class="form-check">
                                            <input class="form-check-input add-session-filter-cb"
                                                   type="checkbox"
                                                   id="add-sessf-{{ $sess->id }}"
                                                   value="{{ $sess->id }}"
                                                   data-session="{{ strtolower($sess->session) }}">
                                            <label class="form-check-label" for="add-sessf-{{ $sess->id }}">
                                                {{ $sess->session }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="filter-col">
                                <small class="d-block mb-1" style="font-size:11px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.4px;">
                                    Term
                                </small>
                                <div class="filter-chip-group" id="add-term-filter">
                                    @foreach ($filterTerms as $t)
                                        <div class="form-check">
                                            <input class="form-check-input add-term-filter-cb"
                                                   type="checkbox"
                                                   id="add-termf-{{ $t->id }}"
                                                   value="{{ $t->id }}"
                                                   data-term="{{ strtolower($t->term) }}">
                                            <label class="form-check-label" for="add-termf-{{ $t->id }}">
                                                {{ $t->term }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="filter-summary" id="add-filter-summary" style="display:none;">
                            <i class="ri-filter-3-line"></i>
                            <span id="add-filter-summary-text">Showing all teachers</span>
                            <button type="button" class="filter-clear-btn ms-auto" id="add-clear-filters">
                                <i class="ri-close-line"></i> Clear filters
                            </button>
                        </div>
                    </div>

                    {{-- TEACHER LIST --}}
                    <div class="mb-3">
                        <label class="form-label">
                            Subject Teachers <span class="text-danger">*</span>
                            <span class="text-muted fw-normal" style="font-size:11px;">
                                (<span id="add-visible-count">0</span> shown)
                            </span>
                        </label>
                        <div class="checkbox-scroll" id="add-teacher-list">
                            @foreach ($subjectteacher->sortBy(['teachername', 'subject']) as $teacher)
                                @php
                                    $tColor = match(true) {
                                        str_contains($teacher->termname ?? '', 'First')  => '#16a34a',
                                        str_contains($teacher->termname ?? '', 'Second') => '#2563eb',
                                        str_contains($teacher->termname ?? '', 'Third')  => '#dc2626',
                                        default => '#6b7280'
                                    };
                                @endphp
                                <div class="form-check teacher-item"
                                     data-session-id="{{ $teacher->sessionid }}"
                                     data-term-id="{{ $teacher->termid }}"
                                     data-session-text="{{ strtolower($teacher->sessionname ?? '') }}"
                                     data-term-text="{{ strtolower($teacher->termname ?? '') }}"
                                     data-search-text="{{ strtolower(($teacher->teachername ?? '') . ' ' . ($teacher->subject ?? '') . ' ' . ($teacher->subjectcode ?? '') . ' ' . ($teacher->sessionname ?? '') . ' ' . ($teacher->termname ?? '')) }}">
                                    <input class="form-check-input add-teacher-checkbox"
                                           type="checkbox"
                                           name="subjectteacherid[]"
                                           id="add-t-{{ $teacher->id }}"
                                           value="{{ $teacher->id }}"
                                           data-label="{{ $teacher->teachername }} {{ $teacher->subject }} {{ $teacher->sessionname }}">
                                    <label class="form-check-label" for="add-t-{{ $teacher->id }}">
                                        <strong>{{ $teacher->teachername }}</strong>
                                        — {{ $teacher->subject }}
                                        <small class="text-muted">({{ $teacher->subjectcode }})</small>
                                        <span class="ms-1" style="color:{{ $tColor }};font-size:11px;font-weight:600;">
                                            {{ $teacher->termname }}
                                        </span>
                                        <span class="ms-1 sc-badge sc-badge-session" style="font-size:10px;">
                                            {{ $teacher->sessionname }}
                                        </span>
                                    </label>
                                </div>
                            @endforeach

                            <div class="no-teachers-msg" id="add-no-teachers" style="display:none;">
                                <i class="ri-search-eye-line"></i>
                                No subject teachers match the current filters.
                            </div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <span id="add-selected-count">0</span> teacher(s) selected
                        </small>
                    </div>

                    <div class="alert alert-danger d-none" id="add-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="add-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Subject Class</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade sc-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-user-follow-line me-2"></i>Change Subject Teacher</h5>
            </div>
            <form id="edit-subjectclass-form" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-id">
                <input type="hidden" id="edit-old-staffid">

                <div class="modal-body p-4" style="position:relative">
                    <div class="modal-body-loader" id="edit-modal-loader">
                        <div class="inner">
                            <div class="mbl-spinner"></div>
                            <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                        </div>
                    </div>

                    <div class="sc-context-box mb-4">
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="context-label">Subject</div>
                                <div class="context-value" id="ctx-subject">—</div>
                                <div class="context-sub" id="ctx-subject-code"></div>
                            </div>
                            <div class="col-6">
                                <div class="context-label">Class</div>
                                <div class="context-value" id="ctx-class">—</div>
                            </div>
                            <div class="col-6">
                                <div class="context-label">Term</div>
                                <div class="context-value" id="ctx-term">—</div>
                            </div>
                            <div class="col-6">
                                <div class="context-label">Session</div>
                                <div class="context-value" id="ctx-session">—</div>
                            </div>
                        </div>
                        <p class="sc-lock-note mt-3 mb-0">
                            <i class="ri-lock-line me-1"></i>
                            Subject, class, term and session are fixed — only the teacher will change.
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Current Teacher</label>
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:#f0fdf4;border:1.5px solid #bbf7d0;">
                            <i class="ri-user-3-line text-success fs-5"></i>
                            <span class="fw-semibold" id="ctx-current-teacher" style="color:#15803d;">—</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Teacher <span class="text-danger">*</span></label>
                        <select name="new_staffid" id="edit-new-staffid" class="form-select" required>
                            <option value="">— Select new teacher —</option>
                            @foreach ($allStaff as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">
                            Select the person who will now teach this subject for this class, term and session.
                        </small>
                    </div>

                    <div class="alert alert-warning d-none mb-2" id="edit-staff-change-warning">
                        <i class="ri-alert-line me-1"></i>
                        <strong>Heads up:</strong> Saving will update the staff assignment across all related broadsheet and registration records for this subject class.
                    </div>

                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Teacher</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete the subject class assignment for <strong id="delete-subject-name"></strong> taught by <strong id="delete-teacher-name"></strong>?</p>
                <p class="text-muted small mb-0">This will be blocked if students are already registered or scores exist.</p>
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

    const PageLoader = {
        show(label) { $('#sc-loader-label').text(label || 'Processing…'); $('#sc-page-loader').addClass('active'); },
        hide() { setTimeout(() => $('#sc-page-loader').removeClass('active'), 300); }
    };

    function toast(type, title, msg) {
        var icons = { success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
        var id = 'sc-toast-' + Date.now() + Math.floor(Math.random() * 1000);
        var $el = $('<div class="sc-toast sc-toast-' + type + '" id="' + id + '">'
            + '<span class="sc-toast-icon"><i class="' + icons[type] + '"></i></span>'
            + '<div class="sc-toast-body"><div class="sc-toast-title">' + title + '</div>'
            + (msg ? '<div class="sc-toast-msg">' + msg + '</div>' : '') + '</div>'
            + '<button class="sc-toast-close" onclick="$(\'#' + id + '\').remove()">×</button></div>');
        $('#sc-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        setTimeout(() => { $el.removeClass('show'); setTimeout(() => $el.remove(), 300); }, 4000);
    }

    function btnLoad($b, lbl) { $b.data('orig', $b.html()).prop('disabled', true).addClass('btn-loading'); if (lbl) $b.html('<span class="btn-text">' + lbl + '</span>'); }
    function btnReset($b) { var o = $b.data('orig'); if (o) $b.html(o); $b.prop('disabled', false).removeClass('btn-loading'); }
    function showErr(sel, m) { $(sel).removeClass('d-none').html('<i class="ri-error-warning-line me-1"></i>' + m); }

    // ─────────────────────────────────────────────────────────────
    // SAFETY NET — reset loading state whenever either modal closes,
    // regardless of how it was closed (X, Esc, Cancel, backdrop, etc.)
    // ─────────────────────────────────────────────────────────────
    $('#addSubjectClassModal').on('hidden.bs.modal', function () {
        btnReset($('#add-btn'));
        $('#add-modal-loader').removeClass('active');
        updateAddBtn();
    });
    $('#editModal').on('hidden.bs.modal', function () {
        btnReset($('#update-btn'));
        $('#edit-modal-loader').removeClass('active');
    });

    // ─────────────────────────────────────────────────────────────
    // ADD MODAL — LIVE FILTER (Text + Session + Term)
    // ─────────────────────────────────────────────────────────────
    function getCheckedValues(selector) {
        return $(selector).map(function () { return String(this.value); }).get();
    }

    function applyAddFilters() {
        const q          = ($('#add-teacher-search').val() || '').toLowerCase().trim();
        const sessions   = getCheckedValues('.add-session-filter-cb:checked');
        const terms      = getCheckedValues('.add-term-filter-cb:checked');
        const sessionsTx = $('.add-session-filter-cb:checked').map(function(){ return $(this).data('session'); }).get();
        const termsTx    = $('.add-term-filter-cb:checked').map(function(){ return $(this).data('term'); }).get();

        let visibleCount = 0;

        $('#add-teacher-list .teacher-item').each(function () {
            const $item       = $(this);
            const itemSession = String($item.data('session-id'));
            const itemTerm    = String($item.data('term-id'));
            const searchText  = String($item.data('search-text') || '');

            let show = true;

            if (q && searchText.indexOf(q) === -1) {
                show = false;
            }

            if (show && sessions.length > 0) {
                if (sessions.indexOf(itemSession) === -1) {
                    const itemSessTx = String($item.data('session-text') || '');
                    const txMatch = sessionsTx.some(function (tx) {
                        return tx && itemSessTx.indexOf(String(tx).toLowerCase()) !== -1;
                    });
                    if (!txMatch) show = false;
                }
            }

            if (show && terms.length > 0) {
                if (terms.indexOf(itemTerm) === -1) {
                    const itemTermTx = String($item.data('term-text') || '');
                    const txMatch = termsTx.some(function (tx) {
                        return tx && itemTermTx.indexOf(String(tx).toLowerCase()) !== -1;
                    });
                    if (!txMatch) show = false;
                }
            }

            $item.toggleClass('hidden-by-filter', !show);
            if (show) visibleCount++;
        });

        $('#add-visible-count').text(visibleCount);
        $('#add-no-teachers').toggle(visibleCount === 0);

        updateFilterSummary(sessionsTx, termsTx, q, visibleCount);
    }

    function updateFilterSummary(sessionsTx, termsTx, q, visibleCount) {
        const parts = [];
        if (q) parts.push('search "<strong>' + $('<div>').text(q).html() + '</strong>"');
        if (sessionsTx.length) parts.push('session <strong>' + sessionsTx.join(' / ') + '</strong>');
        if (termsTx.length)    parts.push('term <strong>' + termsTx.join(' / ') + '</strong>');

        if (parts.length === 0) {
            $('#add-filter-summary').hide();
            return;
        }

        $('#add-filter-summary').show();
        $('#add-filter-summary-text').html(
            'Filtering by ' + parts.join(', ') + ' — ' +
            '<strong>' + visibleCount + '</strong> teacher(s) match'
        );
    }

    $('#add-teacher-search').on('input', applyAddFilters);
    $('#add-session-filter').on('change', '.add-session-filter-cb', applyAddFilters);
    $('#add-term-filter').on('change', '.add-term-filter-cb', applyAddFilters);

    $('#add-clear-filters').on('click', function () {
        $('#add-teacher-search').val('');
        $('#add-session-filter .add-session-filter-cb').prop('checked', false);
        $('#add-term-filter .add-term-filter-cb').prop('checked', false);
        applyAddFilters();
    });

    // ─────────────────────────────────────────────────────────────
    // DATATABLE
    // ─────────────────────────────────────────────────────────────
    var table = $('#subjectClassTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("subjectclass.data") }}',
            type: 'GET',
            dataSrc: function(json) {
                if (json && json.error) {
                    toast('error', 'Load Error', json.error);
                    return [];
                }
                return json.data || [];
            },
            error: function(xhr) {
                console.error('DataTables error:', xhr.status);
                console.error('Response:', xhr.responseText);
                let msg = 'Failed to load assignments.';
                try {
                    const j = JSON.parse(xhr.responseText);
                    if (j.error)   msg = j.error;
                    if (j.message) msg = j.message;
                } catch (e) {}
                toast('error', 'Load Error (HTTP ' + xhr.status + ')', msg);
            }
        },
        columns: [
            { data: 'checkbox',           orderable: false, searchable: false },
            { data: 'DT_RowIndex',        orderable: false, searchable: false },
            { data: 'teacher_info',       orderable: false, searchable: true  },
            { data: 'subject_info',       orderable: false, searchable: true  },
            { data: 'class_info',         orderable: false, searchable: true  },
            { data: 'term_info',          orderable: false, searchable: true  },
            { data: 'session_info',       orderable: false, searchable: true  },
            { data: 'registration_count', orderable: false, searchable: false },
            { data: 'formatted_date',     orderable: false, searchable: false },
            { data: 'action',             orderable: false, searchable: false },
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search: '', searchPlaceholder: 'Search assignments…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ assignments',
            infoEmpty: 'No assignments found', zeroRecords: 'No matching assignments',
            emptyTable: 'No subject class assignments yet',
            paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Prev' }
        },
        order: [[1, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            bindCB();
            // Clear selection after every redraw so the bulk bar never shows a stale count
            $('#selectAll').prop('checked', false);
            updBulk();
            $('#totalBadge').text(this.api().page.info().recordsTotal);
        }
    });

    function loadStats() {
        $.get('{{ route("subjectclass.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statTeachers').text(data.stats.unique_teachers);
                $('#statClasses').text(data.stats.unique_classes);
                $('#statSubjects').text(data.stats.unique_subjects);
            }
        }).fail(function() {
            $('#statTotal, #statTeachers, #statClasses, #statSubjects').text('—');
        });
    }
    loadStats();

    function bindCB() { $('.row-checkbox').off('change').on('change', updBulk); }
    $('#selectAll').on('change', function() { $('.row-checkbox').prop('checked', this.checked); updBulk(); });
    function updBulk() {
        var c = $('.row-checkbox:checked').length;
        $('#bulkBar').toggleClass('show', c > 0);
        $('#bulkCount').text(c);
        $('#bulkDeleteBtn').toggleClass('d-none', c === 0);
        if (c === 0) $('#selectAll').prop('checked', false);
    }

    // ─────────────────────────────────────────────────────────────
    // ADD MODAL — CLASS CHECKBOXES
    // ─────────────────────────────────────────────────────────────
    function updateClassCount() {
        $('#add-class-count').text($('.add-class-checkbox:checked').length);
        var $vis = $('#add-class-list .class-item:not(.hidden-by-filter) .add-class-checkbox');
        $('#add-select-all-classes').prop('checked',
            $vis.length > 0 && $vis.filter(':checked').length === $vis.length);
        updateAddBtn();
    }

    $('#add-class-list').on('change', '.add-class-checkbox', updateClassCount);

    $('#add-select-all-classes').on('change', function () {
        $('#add-class-list .class-item:not(.hidden-by-filter) .add-class-checkbox')
            .prop('checked', this.checked);
        updateClassCount();
    });

    $('#add-class-search').on('input', function () {
        var q = this.value.toLowerCase().trim();
        $('#add-class-list .class-item').each(function () {
            var match = !q || String($(this).data('search-text')).indexOf(q) !== -1;
            $(this).toggleClass('hidden-by-filter', !match);
        });
        updateClassCount();
    });

    $('#add-teacher-list').on('change', '.add-teacher-checkbox', function () {
        $('#add-selected-count').text($('.add-teacher-checkbox:checked').length);
        updateAddBtn();
    });

    function updateAddBtn() {
        const ok = $('.add-class-checkbox:checked').length > 0 &&
                   $('.add-teacher-checkbox:checked').length > 0;
        $('#add-btn').prop('disabled', !ok);
    }

    $('#createSubjectClassBtn').on('click', function() {
        $('.add-class-checkbox, #add-select-all-classes').prop('checked', false);
        $('#add-class-search').val('').trigger('input');
        $('#add-class-count').text(0);

        $('.add-teacher-checkbox').prop('checked', false);
        $('#add-selected-count').text(0);
        $('#add-btn').prop('disabled', true);
        $('#add-error-msg').addClass('d-none').html('');

        $('#add-teacher-search').val('');
        $('#add-session-filter .add-session-filter-cb').prop('checked', false);
        $('#add-term-filter .add-term-filter-cb').prop('checked', false);
        applyAddFilters();

        btnReset($('#add-btn'));
        $('#add-modal-loader').removeClass('active');
        new bootstrap.Modal(document.getElementById('addSubjectClassModal')).show();
    });

    $(document).on('click', '.edit-sc-btn', function () {
        var $b = $(this);
        $('#edit-id').val($b.data('id'));
        $('#edit-old-staffid').val($b.data('staffid'));
        $('#ctx-subject').text($b.data('subjectname') || '—');
        $('#ctx-subject-code').text($b.data('subjectcode') || '');
        $('#ctx-class').text($b.data('classname') || '—');
        $('#ctx-term').text($b.data('termname') || '—');
        $('#ctx-session').text($b.data('sessionname') || '—');
        $('#ctx-current-teacher').text($b.data('teachername') || '—');
        $('#edit-new-staffid').val('');
        $('#edit-staff-change-warning').addClass('d-none');
        $('#edit-error-msg').addClass('d-none').html('');
        btnReset($('#update-btn'));
        $('#edit-modal-loader').removeClass('active');
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $('#edit-new-staffid').on('change', function () {
        const oldId = $('#edit-old-staffid').val();
        const newId = $(this).val();
        const diff  = newId && newId !== String(oldId);
        $('#edit-staff-change-warning').toggleClass('d-none', !diff);
    });

    $(document).on('click', '.delete-sc-btn', function () {
        deleteId = $(this).data('id');
        $('#delete-subject-name').text($(this).data('subject'));
        $('#delete-teacher-name').text($(this).data('teacher'));
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    // ── ADD submit ─────────────────────────────────────────────
    $('#add-subjectclass-form').on('submit', function(e) {
        e.preventDefault();

        const schoolclassids    = $('.add-class-checkbox:checked').map((i, el) => el.value).get();
        const subjectteacherids = $('.add-teacher-checkbox:checked').map((i, el) => el.value).get();

        if (schoolclassids.length === 0) { showErr('#add-error-msg', 'Please select at least one class.'); return; }
        if (subjectteacherids.length === 0) { showErr('#add-error-msg', 'Please select at least one subject teacher.'); return; }

        btnLoad($('#add-btn'), 'Adding…');
        $('#add-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("subjectclass.store") }}',
            type: 'POST',
            // NOTE: no "traditional: true" — jQuery must send schoolclassid[]=1&schoolclassid[]=2
            // so PHP receives real arrays.
            data: { schoolclassid: schoolclassids, subjectteacherid: subjectteacherids, _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(res) {
                if (res.success) {
                    // reset BEFORE hiding so next open is clean
                    btnReset($('#add-btn'));
                    $('#add-modal-loader').removeClass('active');
                    updateAddBtn();

                    $('#addSubjectClassModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload(null, false);
                    loadStats();
                } else {
                    btnReset($('#add-btn'));
                    $('#add-modal-loader').removeClass('active');
                    updateAddBtn();
                    showErr('#add-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#add-btn'));
                $('#add-modal-loader').removeClass('active');
                updateAddBtn();
                var j = xhr.responseJSON;
                var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr('#add-error-msg', m);
            }
        });
    });

    // ── EDIT submit ────────────────────────────────────────────
    $('#edit-subjectclass-form').on('submit', function(e) {
        e.preventDefault();

        const id         = $('#edit-id').val();
        const newStaffId = $('#edit-new-staffid').val();
        const oldStaffId = $('#edit-old-staffid').val();

        if (!newStaffId) { showErr('#edit-error-msg', 'Please select a new teacher.'); return; }

        const doUpdate = function() {
            btnLoad($('#update-btn'), 'Updating…');
            $('#edit-error-msg').addClass('d-none').html('');

            $.ajax({
                url: '{{ url("subjectclass") }}/' + id,
                type: 'POST',
                data: { new_staffid: newStaffId, _token: CSRF, _method: 'PUT' },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (res.success) {
                        // reset BEFORE hiding
                        btnReset($('#update-btn'));
                        $('#edit-modal-loader').removeClass('active');

                        $('#editModal').modal('hide');
                        toast('success', 'Updated!', res.message);
                        table.ajax.reload(null, false);
                        loadStats();
                    } else {
                        btnReset($('#update-btn'));
                        $('#edit-modal-loader').removeClass('active');
                        showErr('#edit-error-msg', res.message || 'Failed.');
                    }
                },
                error: function(xhr) {
                    btnReset($('#update-btn'));
                    $('#edit-modal-loader').removeClass('active');
                    var j = xhr.responseJSON;
                    var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                    showErr('#edit-error-msg', m);
                }
            });
        };

        if (String(newStaffId) !== String(oldStaffId)) {
            Swal.fire({
                title: 'Change Teacher?',
                html: 'This will update the staff assignment on all related <strong>broadsheet</strong> and <strong>registration records</strong> for this subject class. Continue?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Yes, update',
                cancelButtonText: 'Cancel',
            }).then(function(r) { if (r.isConfirmed) doUpdate(); });
        } else {
            toast('info', 'No Change', 'The selected teacher is already assigned to this subject class.');
            $('#editModal').modal('hide');
        }
    });

    // ── DELETE single ──────────────────────────────────────────
    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $b = $(this); btnLoad($b, 'Deleting…');
        $.ajax({
            url: '{{ url("subjectclass") }}/' + deleteId,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(res) {
                $('#deleteModal').modal('hide');
                if (res.success) { toast('success', 'Deleted!', res.message); table.ajax.reload(null, false); loadStats(); }
                else { toast('error', 'Cannot Delete', res.message); }
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                toast('error', 'Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed.');
            },
            complete: function() { btnReset($b); deleteId = null; }
        });
    });

    // ── BULK delete ────────────────────────────────────────────
    function doBulk() {
        var ids = $('.row-checkbox:checked').map(function() { return this.value; }).get();
        if (!ids.length) { toast('warning', 'No Selection', 'Select at least one assignment.'); return; }

        Swal.fire({
            title: 'Delete ' + ids.length + ' assignment(s)?',
            html: 'Assignments with student records or scores will be skipped.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel', reverseButtons: true,
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: function() {
                return new Promise(function(resolve) {
                    $.ajax({
                        url: '{{ route("subjectclass.bulk-destroy") }}',
                        type: 'POST',
                        // no "traditional" — send ids[]=1&ids[]=2 so PHP gets the full array
                        data: { ids: ids, _token: CSRF },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function(res) { resolve(res); },
                        error: function(xhr) {
                            resolve({ success: false,
                                      message: (xhr.responseJSON && xhr.responseJSON.message) || 'Request failed.' });
                        }
                    });
                }).then(function(res) {
                    if (!res.success) { Swal.showValidationMessage(res.message); return false; }
                    return res;
                });
            }
        }).then(function(r) {
            if (!r.isConfirmed || !r.value) return;
            var res = r.value;
            toast(res.skipped_count > 0 ? 'warning' : 'success',
                  res.skipped_count > 0 ? 'Partly deleted' : 'Deleted!', res.message);
            table.ajax.reload(null, false);
            loadStats();
        });
    }
    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulk);

    bindCB();

    // Initialise filters so the visible count is correct on page load
    applyAddFilters();
});
</script>
@endsection
