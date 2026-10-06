{{-- resources/views/subjectvetting/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sv-primary:#1e3a5f; --sv-accent:#2563eb; --sv-success:#16a34a;
    --sv-warning:#d97706; --sv-danger:#dc2626; --sv-muted:#6b7280;
    --sv-border:#e2e8f0; --sv-radius:12px; --sv-shadow:0 2px 8px rgba(0,0,0,.08);
}
.sv-hero {
    background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#4f46e5 100%);
    border-radius:var(--sv-radius); padding:28px 32px; margin-bottom:24px;
    position:relative; overflow:hidden;
}
.sv-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%; }
.sv-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sv-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.stat-card { background:#fff; border:1px solid var(--sv-border); border-radius:var(--sv-radius); padding:18px 20px; cursor:pointer; transition:transform .15s, box-shadow .15s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sv-shadow); }
.stat-card.active-stat { border-color:var(--sv-accent); box-shadow:0 0 0 3px rgba(37,99,235,.15); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sv-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sv-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.sv-table th { background:var(--sv-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.sv-table td { padding:11px 16px; vertical-align:middle; border-bottom:1px solid var(--sv-border); font-size:13px; }
.sv-table tr:hover td { background:#f0f9ff; }

.sv-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.sv-badge-class { background:#dbeafe; color:#2563eb; }
.sv-badge-session { background:#ccfbf1; color:#0f766e; }
.sv-badge-term-first  { background:#dcfce7; color:#16a34a; }
.sv-badge-term-second { background:#dbeafe; color:#2563eb; }
.sv-badge-term-third  { background:#fee2e2; color:#dc2626; }
.sv-badge-term-other  { background:#f3f4f6; color:#6b7280; }
.sv-badge-status-pending   { background:#fee2e2; color:#dc2626; }
.sv-badge-status-completed { background:#dcfce7; color:#16a34a; }
.sv-badge-status-rejected  { background:#fef3c7; color:#92400e; }

.sv-avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid var(--sv-border); flex-shrink:0; }
.sv-avatar-initials {
    width:36px; height:36px; border-radius:50%;
    background:linear-gradient(135deg,#1e3a5f 0%,#0891b2 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:700; font-size:13px; letter-spacing:.5px;
    border:2px solid var(--sv-border); flex-shrink:0; user-select:none;
}

.dataTables_wrapper .dataTables_filter input { border:1.5px solid var(--sv-border); border-radius:8px; padding:7px 14px; margin-left:8px; font-size:13px; }
.dataTables_wrapper .dataTables_filter input:focus { border-color:var(--sv-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.dataTables_wrapper .dataTables_length select { border:1.5px solid var(--sv-border); border-radius:8px; padding:6px 10px; margin:0 6px; font-size:13px; }
.dataTables_wrapper .paginate_button.current, .dataTables_wrapper .paginate_button.current:hover { background:var(--sv-accent) !important; border-color:var(--sv-accent) !important; color:#fff !important; }

.sv-modal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); padding:22px 28px; position:relative; overflow:hidden; }
.modal-hero-bar::before { content:''; position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,.07); border-radius:50%; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border:1.5px solid var(--sv-border); border-radius:8px; font-size:13px; padding:9px 14px; }
.form-control:focus, .form-select:focus { border-color:var(--sv-accent); box-shadow:0 0 0 3px rgba(37,99,235,.1); }

.term-pills { display:flex; gap:8px; padding:10px 14px; border:1.5px solid var(--sv-border); border-radius:8px; background:#fafbfc; flex-wrap:wrap; }
.term-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; cursor:pointer; user-select:none; transition:all .15s; }
.term-pill input { margin:0; }
.term-pill-term-first  { background:#dcfce7; color:#16a34a; }
.term-pill-term-second { background:#dbeafe; color:#2563eb; }
.term-pill-term-third  { background:#fee2e2; color:#dc2626; }
.term-pill-term-other  { background:#f3f4f6; color:#6b7280; }

.subject-search-list { max-height:300px; overflow-y:auto; border:1.5px solid var(--sv-border); border-radius:8px; }
.subject-search-item { padding:10px 14px; border-bottom:1px solid var(--sv-border); cursor:pointer; transition:background .12s; }
.subject-search-item:last-child { border-bottom:none; }
.subject-search-item:hover { background:#f0f9ff; }
.subject-search-item.term-first  { border-left:3px solid #16a34a; }
.subject-search-item.term-second { border-left:3px solid #2563eb; }
.subject-search-item.term-third  { border-left:3px solid #dc2626; }

.selected-subject-item { background:#f8fafc; border:1px solid var(--sv-border); border-radius:8px; padding:10px 12px; margin-bottom:8px; transition:background .12s; }
.selected-subject-item:hover { background:#f0f9ff; }

.bulk-bar { background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:10px 16px; display:none; align-items:center; gap:12px; margin-bottom:12px; }
.bulk-bar.show { display:flex; }

#sv-page-loader { position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; opacity:0; visibility:hidden; transition:opacity .22s, visibility .22s; }
#sv-page-loader.active { opacity:1; visibility:visible; }
.sv-loader-card { background:#fff; border-radius:16px; padding:32px 40px; text-align:center; box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px; }
.sv-loader-spinner { width:52px; height:52px; margin:0 auto 16px; border:4px solid #e2e8f0; border-top-color:var(--sv-accent); border-radius:50%; animation:sv-spin .75s linear infinite; }
@keyframes sv-spin { to { transform:rotate(360deg); } }
.sv-loader-label { font-size:14px; font-weight:600; color:var(--sv-primary); margin-bottom:12px; }

#sv-toast-stack { position:fixed; bottom:24px; right:24px; z-index:10000; display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none; }
.sv-toast { pointer-events:all; background:#fff; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.14); padding:14px 18px; min-width:280px; max-width:360px; display:flex; align-items:flex-start; gap:12px; border-left:4px solid var(--sv-accent); transform:translateX(120%); transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.sv-toast.show { transform:translateX(0); }
.sv-toast-success { border-left-color:var(--sv-success); }
.sv-toast-error   { border-left-color:var(--sv-danger);  }
.sv-toast-warning { border-left-color:var(--sv-warning); }
.sv-toast .sv-toast-icon { font-size:20px; flex-shrink:0; }
.sv-toast .sv-toast-body { flex:1; }
.sv-toast .sv-toast-title { font-size:13px; font-weight:700; color:#111827; }
.sv-toast .sv-toast-msg   { font-size:12px; color:var(--sv-muted); }
.sv-toast .sv-toast-close { background:none; border:none; cursor:pointer; color:var(--sv-muted); font-size:16px; }

.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after { content:''; position:absolute; inset:0; margin:auto; width:16px; height:16px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:sv-spin .65s linear infinite; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<div id="sv-page-loader"><div class="sv-loader-card"><div class="sv-loader-spinner"></div><div class="sv-loader-label" id="sv-loader-label">Processing…</div></div></div>
<div id="sv-toast-stack"></div>

<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="sv-hero">
        <h1><i class="ri-shield-check-line me-2"></i>Subject Vetting Management</h1>
        <p>Assign staff to vet subject-class scoresheets across terms and sessions.</p>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label"><i class="ri-calendar-line me-1"></i>Term</label>
                    <select class="form-select" id="filter-term">
                        <option value="">All Terms</option>
                        @foreach ($terms as $t)
                            <option value="{{ $t->id }}">{{ $t->term }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label"><i class="ri-calendar-event-line me-1"></i>Session</label>
                    <select class="form-select" id="filter-session">
                        <option value="">All Sessions</option>
                        @foreach ($sessions as $s)
                            <option value="{{ $s->id }}" {{ ($currentSession && $currentSession->id == $s->id) ? 'selected' : '' }}>
                                {{ $s->session }}{{ $s->status == 'Current' ? ' (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary w-100" id="reset-filters"><i class="ri-refresh-line me-1"></i>Reset</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card" data-status="all"><div class="stat-icon"><i class="ri-file-list-line"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Assignments</div></div></div>
        <div class="col-md-3"><div class="stat-card" data-status="pending"><div class="stat-icon"><i class="ri-timer-line"></i></div><div class="stat-value text-danger" id="statPending">—</div><div class="stat-label">Pending</div></div></div>
        <div class="col-md-3"><div class="stat-card" data-status="completed"><div class="stat-icon"><i class="ri-checkbox-circle-line"></i></div><div class="stat-value text-success" id="statCompleted">—</div><div class="stat-label">Completed</div></div></div>
        <div class="col-md-3"><div class="stat-card" data-status="rejected"><div class="stat-icon"><i class="ri-close-circle-line"></i></div><div class="stat-value text-warning" id="statRejected">—</div><div class="stat-label">Rejected</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sv-primary)">
                    <i class="ri-list-check me-2"></i>Vetting Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
                    @can('Create subject-vettings')
                    <button class="btn btn-primary" id="createBtn"><i class="ri-add-line me-1"></i>Create Assignment</button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> record(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table sv-table w-100 mb-0" id="svTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>#</th>
                            <th>Vetting Staff</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Arm</th>
                            <th>Teacher</th>
                            <th>Term</th>
                            <th>Session</th>
                            <th>Status</th>
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
<div class="modal fade sv-modal" id="addSubjectVettingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-circle-line me-2"></i>Add Subject Vetting Assignment</h5>
            </div>
            <form id="add-subjectvetting-form" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vetting Staff <span class="text-danger">*</span></label>
                            <select name="userid" id="subject-userid" class="form-select" required>
                                <option value="">— Select Staff —</option>
                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session <span class="text-danger">*</span></label>
                            <select name="sessionid" id="subject-sessionid" class="form-select" required>
                                <option value="">— Select Session —</option>
                                @foreach ($sessions as $session)
                                    <option value="{{ $session->id }}" {{ ($currentSession && $currentSession->id == $session->id) ? 'selected' : '' }}>
                                        {{ $session->session }}{{ $session->status == 'Current' ? ' (Current)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label">Terms <span class="text-danger">*</span></label>
                        <div class="term-pills" id="subject-term-pills">
                            @foreach ($terms as $term)
                                @php
                                    $tClass = match(true) {
                                        str_contains($term->term, 'First')  => 'term-pill-term-first',
                                        str_contains($term->term, 'Second') => 'term-pill-term-second',
                                        str_contains($term->term, 'Third')  => 'term-pill-term-third',
                                        default => 'term-pill-term-other'
                                    };
                                @endphp
                                <label class="term-pill {{ $tClass }}">
                                    <input class="form-check-input term-checkbox" type="checkbox"
                                           name="termid[]" value="{{ $term->id }}"
                                           id="subject-term-{{ $term->id }}">
                                    {{ $term->term }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject-Class Assignments <span class="text-danger">*</span></label>
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" id="subjectSearchInput" class="form-control"
                                   placeholder="Search by subject, class, teacher, term, or session… (min 2 characters)"
                                   autocomplete="off">
                            <button type="button" id="subjectClearSearchBtn" class="btn btn-outline-secondary" style="display:none;">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div id="subjectSearchResults" class="subject-search-list mb-3" style="display:none;"></div>
                        <div id="subjectSearchLoading" class="text-center p-3" style="display:none;">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                            <span class="ms-2">Searching…</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Selected (<span id="subjectSelectedCount">0</span>)</h6>
                            <button type="button" id="subjectClearAllSelectedBtn" class="btn btn-sm btn-outline-danger" style="display:none;">
                                <i class="ri-delete-bin-line me-1"></i>Clear All
                            </button>
                        </div>
                        <div id="subjectSelectedSubjectsContainer" class="border rounded p-2" style="min-height:80px;max-height:300px;overflow-y:auto;">
                            <div class="text-center text-muted py-3">No subjects selected</div>
                        </div>
                    </div>

                    <div class="alert alert-danger d-none" id="subject-alert-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="subject-add-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Add Assignment(s)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade sv-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Subject Vetting Assignment</h5>
            </div>
            <form id="edit-subjectvetting-form" autocomplete="off">
                @csrf
                <input type="hidden" name="id" id="edit-id-field">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vetting Staff <span class="text-danger">*</span></label>
                            <select name="userid" id="edit-userid" class="form-select" required>
                                <option value="">— Select Staff —</option>
                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session <span class="text-danger">*</span></label>
                            <select name="sessionid" id="edit-sessionid" class="form-select" required>
                                <option value="">— Select Session —</option>
                                @foreach ($sessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <div class="term-pills" id="edit-term-pills">
                            @foreach ($terms as $term)
                                @php
                                    $tClass = match(true) {
                                        str_contains($term->term, 'First')  => 'term-pill-term-first',
                                        str_contains($term->term, 'Second') => 'term-pill-term-second',
                                        str_contains($term->term, 'Third')  => 'term-pill-term-third',
                                        default => 'term-pill-term-other'
                                    };
                                @endphp
                                <label class="term-pill {{ $tClass }}">
                                    <input class="form-check-input edit-term-checkbox" type="radio"
                                           name="termid" value="{{ $term->id }}"
                                           id="edit-term-{{ $term->id }}">
                                    {{ $term->term }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject-Class Assignment <span class="text-danger">*</span></label>
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" id="editSubjectSearchInput" class="form-control"
                                   placeholder="Search by subject, class, teacher, term, or session… (min 2 characters)"
                                   autocomplete="off">
                            <button type="button" id="editClearSearchBtn" class="btn btn-outline-secondary" style="display:none;">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div id="editSearchResults" class="subject-search-list mb-3" style="display:none;"></div>
                        <div id="editSearchLoading" class="text-center p-3" style="display:none;">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                            <span class="ms-2">Searching…</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Selected Subject</h6>
                            <button type="button" id="editClearSelectedBtn" class="btn btn-sm btn-outline-danger" style="display:none;">
                                <i class="ri-delete-bin-line me-1"></i>Clear
                            </button>
                        </div>
                        <div id="editSelectedSubjectContainer" class="border rounded p-2" style="min-height:80px;">
                            <div class="text-center text-muted py-3">No subject selected</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="edit-status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="alert alert-danger d-none" id="edit-alert-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Assignment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal fade" id="deleteRecordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete this vetting assignment?</p>
                <p class="text-muted small mb-0">This will also clear the vetting status on any related broadsheets.</p>
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
// =========================================================================
// SHARED HELPERS
// =========================================================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
function getTermColorClass(termId) {
    if (termId == 1) return 'term-pill-term-first';
    if (termId == 2) return 'term-pill-term-second';
    if (termId == 3) return 'term-pill-term-third';
    return 'term-pill-term-other';
}
function getTermBorderClass(termId) {
    if (termId == 1) return 'term-first';
    if (termId == 2) return 'term-second';
    if (termId == 3) return 'term-third';
    return '';
}

$(document).ready(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    let deleteId = null;

    const PageLoader = {
        show(lbl) { $('#sv-loader-label').text(lbl || 'Processing…'); $('#sv-page-loader').addClass('active'); },
        hide() { setTimeout(() => $('#sv-page-loader').removeClass('active'), 300); }
    };

    function toast(type, title, msg) {
        var icons = { success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
        var id = 'sv-toast-' + Date.now();
        var $el = $('<div class="sv-toast sv-toast-' + type + '" id="' + id + '">'
            + '<span class="sv-toast-icon"><i class="' + icons[type] + '"></i></span>'
            + '<div class="sv-toast-body"><div class="sv-toast-title">' + title + '</div>'
            + (msg ? '<div class="sv-toast-msg">' + msg + '</div>' : '') + '</div>'
            + '<button class="sv-toast-close" onclick="$(\'#' + id + '\').remove()">×</button></div>');
        $('#sv-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        setTimeout(() => { $el.removeClass('show'); setTimeout(() => $el.remove(), 300); }, 4000);
    }

    function btnLoad($b, lbl) { $b.data('orig', $b.html()).prop('disabled', true).addClass('btn-loading'); if (lbl) $b.html('<span class="btn-text">' + lbl + '</span>'); }
    function btnReset($b) { var o = $b.data('orig'); if (o) $b.html(o); $b.prop('disabled', false).removeClass('btn-loading'); }
    function showErr(sel, m) { $(sel).removeClass('d-none').html('<i class="ri-error-warning-line me-1"></i>' + m); }

    // =========================================================================
    // DATATABLE
    // =========================================================================
    var table = $('#svTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("subjectvetting.data") }}',
            type: 'GET',
            data: function (d) {
                d.filter_term    = $('#filter-term').val();
                d.filter_session = $('#filter-session').val();
            },
            error: function (xhr) {
                console.error('DataTables error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load assignments. Please refresh.');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'vetting_info', orderable: false, searchable: false },
            { data: 'subject_info', orderable: false, searchable: false },
            { data: 'class_info', orderable: false, searchable: false },
            { data: 'arm_info', orderable: false, searchable: false },
            { data: 'teacher_info', orderable: false, searchable: false },
            { data: 'term_info', orderable: false, searchable: false },
            { data: 'session_info', orderable: false, searchable: false },
            { data: 'status_info', orderable: false, searchable: false },
            { data: 'formatted_date', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
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
            emptyTable: 'No subject vetting assignments yet'
        },
        order: [[1, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function () {
            bindCB();
            $('#totalBadge').text(this.api().page.info().recordsTotal);
        }
    });

    function loadStats() {
        $.get('{{ route("subjectvetting.stats") }}', {
            filter_term: $('#filter-term').val(),
            filter_session: $('#filter-session').val()
        }, function (d) {
            if (d.stats) {
                $('#statTotal').text(d.stats.total);
                $('#statPending').text(d.stats.pending);
                $('#statCompleted').text(d.stats.completed);
                $('#statRejected').text(d.stats.rejected);
            }
        }).fail(function () {
            $('#statTotal, #statPending, #statCompleted, #statRejected').text('—');
        });
    }
    loadStats();

    // Filters
    $('#filter-term, #filter-session').on('change', function () { table.ajax.reload(); loadStats(); });
    $('#reset-filters').on('click', function () {
        $('#filter-term').val('');
        $('#filter-session').val('');
        table.ajax.reload(); loadStats();
    });

    // Stat card click → apply status filter via search hint
    $('.stat-card').on('click', function () {
        const status = $(this).data('status');
        $('.stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        if (status === 'all') {
            table.column(9).search('').draw();
        } else {
            table.column(9).search(status, true, false).draw();
        }
    });

    // Checkboxes
    function bindCB() { $('.row-checkbox').off('change').on('change', updBulk); }
    $('#selectAll').on('change', function () { $('.row-checkbox').prop('checked', this.checked); updBulk(); });
    function updBulk() {
        var c = $('.row-checkbox:checked').length;
        $('#bulkBar').toggleClass('show', c > 0);
        $('#bulkCount').text(c);
        $('#bulkDeleteBtn').toggleClass('d-none', c === 0);
        if (c === 0) $('#selectAll').prop('checked', false);
    }

    // =========================================================================
    // ADD MODAL — AJAX SUBJECT SEARCH
    // =========================================================================
    let subjectSelectedSubjects = new Map();
    const searchInput      = document.getElementById('subjectSearchInput');
    const resultsDiv       = document.getElementById('subjectSearchResults');
    const loadingDiv       = document.getElementById('subjectSearchLoading');
    const clearSearchBtn   = document.getElementById('subjectClearSearchBtn');
    let searchTimeout;

    function getCheckedTermIds() {
        return Array.from(document.querySelectorAll('input[name="termid[]"]:checked')).map(cb => cb.value).join(',');
    }

    function runSubjectAddSearch(query) {
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            clearSearchBtn.style.display = 'none';
            return;
        }
        clearSearchBtn.style.display = 'block';
        loadingDiv.style.display = 'block';
        resultsDiv.style.display = 'none';

        const excludeIds = Array.from(subjectSelectedSubjects.keys()).join(',');
        const termIds = getCheckedTermIds();

        fetch(`{{ url('api/subject-classes/search') }}?q=${encodeURIComponent(query)}&exclude_ids=${excludeIds}&term_ids=${termIds}`, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(response => {
            loadingDiv.style.display = 'none';
            if (!response.success) {
                resultsDiv.innerHTML = `<div class="text-danger p-3">${response.message || 'Search failed'}</div>`;
                resultsDiv.style.display = 'block';
                return;
            }
            const data = response.data;
            if (data.length === 0) {
                resultsDiv.innerHTML = '<div class="text-muted p-3">No results found</div>';
                resultsDiv.style.display = 'block';
                return;
            }
            resultsDiv.innerHTML = data.map(item => {
                const termBorder = getTermBorderClass(item.termid);
                return `
                    <div class="subject-search-item ${termBorder}" data-id="${item.id}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="fw-bold">${escapeHtml(item.subjectname)} ${item.subjectcode ? `<span class="text-muted">(${escapeHtml(item.subjectcode)})</span>` : ''}</div>
                                <div class="small text-muted mt-1">
                                    <i class="ri-group-line me-1"></i>Class: ${escapeHtml(item.sclass)} ${item.schoolarm ? `(${escapeHtml(item.schoolarm)})` : ''}<br>
                                    <i class="ri-user-line me-1"></i>Teacher: ${escapeHtml(item.teachername)}<br>
                                    <i class="ri-calendar-line me-1"></i>Session: ${escapeHtml(item.sessionname)}<br>
                                    <i class="ri-calendar-event-line me-1"></i>Term: <strong>${escapeHtml(item.termname)}</strong>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary add-subject-btn"><i class="ri-add-line"></i></button>
                        </div>
                    </div>`;
            }).join('');
            resultsDiv.style.display = 'block';
        })
        .catch(() => {
            loadingDiv.style.display = 'none';
            resultsDiv.innerHTML = '<div class="text-danger p-3">Network error</div>';
            resultsDiv.style.display = 'block';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => runSubjectAddSearch(query), 400);
        });
    }
    document.querySelectorAll('input[name="termid[]"]').forEach(cb => {
        cb.addEventListener('change', () => {
            const q = searchInput.value.trim();
            if (q.length >= 2) { clearTimeout(searchTimeout); runSubjectAddSearch(q); }
        });
    });
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = ''; resultsDiv.style.display = 'none'; clearSearchBtn.style.display = 'none';
        });
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.add-subject-btn');
        if (!btn) return;
        const item = btn.closest('.subject-search-item');
        if (!item) return;
        const id = item.dataset.id;
        const name = item.querySelector('.fw-bold')?.innerText || '';
        if (subjectSelectedSubjects.has(id)) {
            toast('warning', 'Already selected', '');
            return;
        }
        const detailsHtml = item.querySelector('.small')?.innerHTML || '';
        subjectSelectedSubjects.set(id, { id, name, detailsHtml });
        updateSubjectSelectedDisplay();
        item.remove();
        if (resultsDiv.children.length === 0) {
            resultsDiv.style.display = 'none'; searchInput.value = ''; clearSearchBtn.style.display = 'none';
        }
    });

    function updateSubjectSelectedDisplay() {
        const container = document.getElementById('subjectSelectedSubjectsContainer');
        const countSpan = document.getElementById('subjectSelectedCount');
        const clearAllBtn = document.getElementById('subjectClearAllSelectedBtn');
        const count = subjectSelectedSubjects.size;
        countSpan.textContent = count;
        clearAllBtn.style.display = count > 0 ? 'block' : 'none';

        if (count === 0) {
            container.innerHTML = '<div class="text-center text-muted py-3">No subjects selected</div>';
            return;
        }
        let html = '';
        for (let [id, s] of subjectSelectedSubjects) {
            html += `
                <div class="selected-subject-item d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong>${escapeHtml(s.name)}</strong>
                        <div class="small text-muted">${s.detailsHtml || ''}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger remove-subject-btn" data-id="${id}">
                        <i class="ri-close-line"></i>
                    </button>
                </div>`;
        }
        container.innerHTML = html;
        container.querySelectorAll('.remove-subject-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                subjectSelectedSubjects.delete(btn.dataset.id);
                updateSubjectSelectedDisplay();
            });
        });
    }

    document.getElementById('subjectClearAllSelectedBtn')?.addEventListener('click', () => {
        if (confirm('Clear all selected subjects?')) {
            subjectSelectedSubjects.clear();
            updateSubjectSelectedDisplay();
        }
    });

    $('#createBtn').on('click', function () {
        document.getElementById('add-subjectvetting-form').reset();
        subjectSelectedSubjects.clear();
        updateSubjectSelectedDisplay();
        $('#subjectSearchInput').val('');
        $('#subjectSearchResults').hide();
        $('#subject-alert-error-msg').addClass('d-none').html('');
        new bootstrap.Modal(document.getElementById('addSubjectVettingModal')).show();
    });

    document.getElementById('addSubjectVettingModal')?.addEventListener('hidden.bs.modal', () => {
        document.getElementById('add-subjectvetting-form').reset();
        subjectSelectedSubjects.clear();
        updateSubjectSelectedDisplay();
        $('#subjectSearchInput').val('');
        $('#subjectSearchResults').hide();
        $('#subject-alert-error-msg').addClass('d-none').html('');
    });

    $('#add-subjectvetting-form').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const errorEl = document.getElementById('subject-alert-error-msg');
        const $btn = $('#subject-add-btn');

        if (!formData.get('userid'))         { showErr(errorEl, 'Please select a vetting staff member.'); return; }
        if (!formData.get('sessionid'))      { showErr(errorEl, 'Please select a session.'); return; }
        if (formData.getAll('termid[]').length === 0) { showErr(errorEl, 'Please select at least one term.'); return; }
        if (subjectSelectedSubjects.size === 0)       { showErr(errorEl, 'Please select at least one subject-class.'); return; }

        formData.delete('subjectclassid[]');
        Array.from(subjectSelectedSubjects.keys()).forEach(id => formData.append('subjectclassid[]', id));

        $(errorEl).addClass('d-none').html('');
        btnLoad($btn, 'Adding…');

        $.ajax({
            url: '{{ route("subjectvetting.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            success: function (res) {
                if (res.success) {
                    $('#addSubjectVettingModal').modal('hide');
                    toast('success', 'Added!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($btn);
                    showErr(errorEl, res.message || (res.errors && Object.values(res.errors).flat()[0]) || 'Failed.');
                }
            },
            error: function (xhr) {
                btnReset($btn);
                const j = xhr.responseJSON;
                const m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr(errorEl, m);
            }
        });
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================
    let editSelectedSubject = null;
    const editSearchInput = document.getElementById('editSubjectSearchInput');
    const editResultsDiv  = document.getElementById('editSearchResults');
    const editLoadingDiv  = document.getElementById('editSearchLoading');
    const editClearSearchBtn = document.getElementById('editClearSearchBtn');
    let editSearchTimeout;

    function getSelectedEditTermId() {
        const checked = document.querySelector('input[name="termid"]:checked');
        return checked ? checked.value : '';
    }

    function runSubjectEditSearch(query) {
        if (query.length < 2) {
            editResultsDiv.style.display = 'none';
            editClearSearchBtn.style.display = 'none';
            return;
        }
        editClearSearchBtn.style.display = 'block';
        editLoadingDiv.style.display = 'block';
        editResultsDiv.style.display = 'none';

        const excludeId = editSelectedSubject ? editSelectedSubject.id : '';
        const termId = getSelectedEditTermId();

        fetch(`{{ url('api/subject-classes/search') }}?q=${encodeURIComponent(query)}&exclude_ids=${excludeId}&term_ids=${termId}`, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(response => {
            editLoadingDiv.style.display = 'none';
            if (!response.success) {
                editResultsDiv.innerHTML = `<div class="text-danger p-3">${response.message || 'Search failed'}</div>`;
                editResultsDiv.style.display = 'block';
                return;
            }
            const data = response.data;
            if (data.length === 0) {
                editResultsDiv.innerHTML = '<div class="text-muted p-3">No results found</div>';
                editResultsDiv.style.display = 'block';
                return;
            }
            editResultsDiv.innerHTML = data.map(item => {
                const termBorder = getTermBorderClass(item.termid);
                return `
                    <div class="subject-search-item ${termBorder}" data-id="${item.id}" data-details='${JSON.stringify(item).replace(/'/g, "&apos;")}'>
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="fw-bold">${escapeHtml(item.subjectname)} ${item.subjectcode ? `<span class="text-muted">(${escapeHtml(item.subjectcode)})</span>` : ''}</div>
                                <div class="small text-muted mt-1">
                                    <i class="ri-group-line me-1"></i>Class: ${escapeHtml(item.sclass)} ${item.schoolarm ? `(${escapeHtml(item.schoolarm)})` : ''}<br>
                                    <i class="ri-user-line me-1"></i>Teacher: ${escapeHtml(item.teachername)}<br>
                                    <i class="ri-calendar-line me-1"></i>Session: ${escapeHtml(item.sessionname)}<br>
                                    <i class="ri-calendar-event-line me-1"></i>Term: <strong>${escapeHtml(item.termname)}</strong>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary edit-select-subject-btn"><i class="ri-check-line"></i></button>
                        </div>
                    </div>`;
            }).join('');
            editResultsDiv.style.display = 'block';
        })
        .catch(() => {
            editLoadingDiv.style.display = 'none';
            editResultsDiv.innerHTML = '<div class="text-danger p-3">Network error</div>';
            editResultsDiv.style.display = 'block';
        });
    }

    if (editSearchInput) {
        editSearchInput.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(editSearchTimeout);
            editSearchTimeout = setTimeout(() => runSubjectEditSearch(query), 400);
        });
    }
    document.querySelectorAll('.edit-term-checkbox').forEach(radio => {
        radio.addEventListener('change', () => {
            const q = editSearchInput.value.trim();
            if (q.length >= 2) { clearTimeout(editSearchTimeout); runSubjectEditSearch(q); }
        });
    });
    if (editClearSearchBtn) {
        editClearSearchBtn.addEventListener('click', () => {
            editSearchInput.value = ''; editResultsDiv.style.display = 'none'; editClearSearchBtn.style.display = 'none';
        });
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.edit-select-subject-btn');
        if (!btn) return;
        const item = btn.closest('.subject-search-item');
        if (!item) return;
        const details = JSON.parse(item.dataset.details.replace(/&apos;/g, "'"));
        editSelectedSubject = { id: details.id, name: details.subjectname, details: details };
        updateEditSelectedDisplay();
        editResultsDiv.style.display = 'none';
        editSearchInput.value = '';
        editClearSearchBtn.style.display = 'none';
    });

    function updateEditSelectedDisplay() {
        const container = document.getElementById('editSelectedSubjectContainer');
        const clearBtn = document.getElementById('editClearSelectedBtn');
        if (!editSelectedSubject) {
            container.innerHTML = '<div class="text-center text-muted py-3">No subject selected</div>';
            clearBtn.style.display = 'none';
            return;
        }
        clearBtn.style.display = 'block';
        container.innerHTML = `
            <div class="selected-subject-item">
                <strong>${escapeHtml(editSelectedSubject.name)}</strong>
                <div class="small text-muted mt-1">
                    <i class="ri-group-line me-1"></i>Class: ${escapeHtml(editSelectedSubject.details.sclass)} ${editSelectedSubject.details.schoolarm ? `(${escapeHtml(editSelectedSubject.details.schoolarm)})` : ''}<br>
                    <i class="ri-user-line me-1"></i>Teacher: ${escapeHtml(editSelectedSubject.details.teachername)}<br>
                    <i class="ri-calendar-line me-1"></i>Session: ${escapeHtml(editSelectedSubject.details.sessionname)}<br>
                    <i class="ri-calendar-event-line me-1"></i>Term: <strong>${escapeHtml(editSelectedSubject.details.termname)}</strong>
                </div>
            </div>`;
    }

    document.getElementById('editClearSelectedBtn')?.addEventListener('click', () => {
        editSelectedSubject = null;
        updateEditSelectedDisplay();
    });

    $(document).on('click', '.edit-sv-btn', function () {
        const $b = $(this);
        $('#edit-id-field').val($b.data('id'));
        $('#edit-userid').val($b.data('vetting-userid'));
        $('#edit-sessionid').val($b.data('sessionid'));
        $('#edit-status').val($b.data('status'));

        document.querySelectorAll('.edit-term-checkbox').forEach(r => r.checked = false);
        const termRadio = document.querySelector(`.edit-term-checkbox[value="${$b.data('termid')}"]`);
        if (termRadio) termRadio.checked = true;

        editSelectedSubject = {
            id: $b.data('subjectclassid'),
            name: $b.data('subjectname'),
            details: {
                id: $b.data('subjectclassid'),
                subjectname: $b.data('subjectname'),
                sclass: $b.data('sclass'),
                schoolarm: $b.data('arm'),
                teachername: $b.data('teachername'),
                termname: $b.data('termname'),
                termid: $b.data('termid'),
                sessionname: $b.data('sessionname'),
            }
        };
        updateEditSelectedDisplay();

        $('#edit-alert-error-msg').addClass('d-none').html('');
        btnReset($('#edit-btn'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    document.getElementById('editModal')?.addEventListener('hidden.bs.modal', () => {
        editSelectedSubject = null;
        updateEditSelectedDisplay();
        editSearchInput.value = '';
        editResultsDiv.style.display = 'none';
        $('#edit-alert-error-msg').addClass('d-none').html('');
    });

    $('#edit-subjectvetting-form').on('submit', function (e) {
        e.preventDefault();
        const id = $('#edit-id-field').val();
        const formData = new FormData(this);
        const errorEl = document.getElementById('edit-alert-error-msg');
        const $btn = $('#edit-btn');

        if (!formData.get('userid'))    { showErr(errorEl, 'Please select a vetting staff member.'); return; }
        if (!formData.get('sessionid')) { showErr(errorEl, 'Please select a session.'); return; }
        if (!formData.get('termid'))    { showErr(errorEl, 'Please select a term.'); return; }
        if (!editSelectedSubject)       { showErr(errorEl, 'Please select a subject-class.'); return; }

        formData.set('subjectclassid', editSelectedSubject.id);
        $(errorEl).addClass('d-none').html('');
        btnLoad($btn, 'Updating…');

        $.ajax({
            url: '{{ url("subjectvetting") }}/' + id,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-HTTP-Method-Override': 'PUT' },
            success: function (res) {
                if (res.success) {
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($btn);
                    showErr(errorEl, res.message || (res.errors && Object.values(res.errors).flat()[0]) || 'Failed.');
                }
            },
            error: function (xhr) {
                btnReset($btn);
                const j = xhr.responseJSON;
                const m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr(errorEl, m);
            }
        });
    });

    // =========================================================================
    // DELETE (single + bulk)
    // =========================================================================
    $(document).on('click', '.delete-sv-btn', function () {
        deleteId = $(this).data('id');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteRecordModal')).show();
    });

    $('#confirm-delete-btn').on('click', function () {
        if (!deleteId) return;
        const $b = $(this); btnLoad($b, 'Deleting…');
        $.ajax({
            url: '{{ url("subjectvetting") }}/' + deleteId,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                $('#deleteRecordModal').modal('hide');
                if (res.success) { toast('success', 'Deleted!', res.message); table.ajax.reload(); loadStats(); }
                else { toast('error', 'Cannot Delete', res.message); }
            },
            error: function (xhr) {
                $('#deleteRecordModal').modal('hide');
                toast('error', 'Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed.');
            },
            complete: function () { btnReset($b); deleteId = null; }
        });
    });

    function doBulk() {
        const ids = $('.row-checkbox:checked').map(function () { return this.value; }).get();
        if (!ids.length) { toast('warning', 'No Selection', 'Select at least one record.'); return; }
        Swal.fire({
            title: 'Delete ' + ids.length + ' record(s)?',
            html: 'Related broadsheets will have their vetting fields cleared.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel', reverseButtons: true, showLoaderOnConfirm: true,
            preConfirm: function () {
                return new Promise(function (resolve, reject) {
                    PageLoader.show('Deleting…');
                    $.ajax({
                        url: '{{ route("subjectvetting.bulkDelete") }}',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ ids: ids, _token: CSRF }),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) { PageLoader.hide(); if (res.success) resolve(res); else reject(res.message); },
                        error: function (xhr) { PageLoader.hide(); reject((xhr.responseJSON && xhr.responseJSON.message) || 'Error.'); }
                    });
                });
            }
        }).then(function (r) {
            if (r.isConfirmed && r.value) {
                toast('success', 'Deleted!', r.value.message);
                table.ajax.reload(); loadStats();
                $('#selectAll').prop('checked', false); updBulk();
            }
        }).catch(function (err) { toast('error', 'Failed', typeof err === 'string' ? err : 'Could not delete.'); });
    }
    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulk);

    bindCB();
});
</script>
@endsection