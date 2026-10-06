{{-- resources/views/classcategories/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --cc-primary:  #1e3a5f;
    --cc-accent:   #2563eb;
    --cc-success:  #16a34a;
    --cc-warning:  #d97706;
    --cc-danger:   #dc2626;
    --cc-muted:    #6b7280;
    --cc-border:   #e2e8f0;
    --cc-bg:       #f8fafc;
    --cc-radius:   12px;
    --cc-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.cc-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--cc-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.cc-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.cc-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.cc-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.cc-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Navigation Tabs ────────────────────────────────────── */
.nav-tabs-custom {
    display: flex; gap: 8px; margin-bottom: 24px;
    border-bottom: 1px solid var(--cc-border); padding-bottom: 0;
}
.nav-tabs-custom .nav-link {
    padding: 10px 20px; font-size: 13px; font-weight: 600;
    color: var(--cc-muted); background: transparent;
    border: none; border-radius: 8px 8px 0 0;
    cursor: pointer; transition: all .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
}
.nav-tabs-custom .nav-link i { font-size: 16px; }
.nav-tabs-custom .nav-link:hover {
    color: var(--cc-accent); background: rgba(37,99,235,.05);
}
.nav-tabs-custom .nav-link.active {
    color: var(--cc-accent); border-bottom: 2px solid var(--cc-accent);
    background: transparent;
}

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--cc-border);
    border-radius:var(--cc-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--cc-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--cc-primary); }
.stat-card .stat-label { font-size:12px; color:var(--cc-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.cc-table th {
    background:var(--cc-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.cc-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--cc-border); font-size:13px;
}
.cc-table tr:hover td { background:#f0f9ff; }

/* ── Badges ──────────────────────────────────────────────── */
.cc-badge {
    display:inline-flex; align-items:center;
    padding:4px 12px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.cc-badge-senior {
    background:#f0fdf4; color:#16a34a;
    border:1px solid #bbf7d0;
}
.cc-badge-junior {
    background:#eff6ff; color:#2563eb;
    border:1px solid #bfdbfe;
}

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--cc-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--cc-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--cc-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--cc-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--cc-accent) !important;
    border-color:var(--cc-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.cc-modal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
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
    border:1.5px solid var(--cc-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--cc-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

.sub-assessment-row {
    background:#f8fafc; padding:12px; border-radius:10px;
    margin-bottom:10px; border:1px solid var(--cc-border);
}

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#cc-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#cc-page-loader.active { opacity:1; visibility:visible; }
.cc-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px;
}
.cc-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0; border-top-color:var(--cc-accent);
    border-radius:50%; animation:cc-spin .75s linear infinite;
}
@keyframes cc-spin { to { transform:rotate(360deg); } }
.cc-loader-label { font-size:14px; font-weight:600; color:var(--cc-primary); margin-bottom:12px; }
.cc-progress-wrap {
    width:160px; height:5px; background:#e2e8f0;
    border-radius:99px; overflow:hidden; margin:0 auto;
}
.cc-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--cc-accent), #0d9488);
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
    border-top-color:var(--cc-accent); border-radius:50%;
    animation:cc-spin .7s linear infinite;
}
.modal-body-loader .mbl-text { font-size:13px; font-weight:600; color:var(--cc-primary); }

/* ── Toast notifications ─────────────────────────────────── */
#cc-toast-stack {
    position:fixed; bottom:24px; right:24px; z-index:10000;
    display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none;
}
.cc-toast {
    pointer-events:all; background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--cc-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.cc-toast.show { transform:translateX(0); }
.cc-toast.cc-toast-success { border-left-color:var(--cc-success); }
.cc-toast.cc-toast-error   { border-left-color:var(--cc-danger);  }
.cc-toast.cc-toast-warning { border-left-color:var(--cc-warning); }
.cc-toast .cc-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.cc-toast-success .cc-toast-icon { color:var(--cc-success); }
.cc-toast-error   .cc-toast-icon { color:var(--cc-danger);  }
.cc-toast-warning .cc-toast-icon { color:var(--cc-warning); }
.cc-toast .cc-toast-body { flex:1; }
.cc-toast .cc-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.cc-toast .cc-toast-msg   { font-size:12px; color:var(--cc-muted); line-height:1.4; }
.cc-toast .cc-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--cc-muted); font-size:16px; line-height:1; padding:0; flex-shrink:0;
}

/* ── Button loading state ────────────────────────────────── */
.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:''; position:absolute; inset:0; margin:auto;
    width:16px; height:16px; border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%; animation:cc-spin .65s linear infinite;
}
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="cc-page-loader">
    <div class="cc-loader-card">
        <div class="cc-loader-spinner"></div>
        <div class="cc-loader-label" id="cc-loader-label">Processing…</div>
        <div class="cc-progress-wrap">
            <div class="cc-progress-bar" id="cc-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast stack ═══ --}}
<div id="cc-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="cc-hero">
        <h1><i class="ri-bookmark-line me-2"></i>Class Category Management</h1>
        <p>Manage class categories and their assessment configurations for grading systems.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="nav-tabs-custom">
        <a href="{{ route('classcategories.index') }}" class="nav-link active">
            <i class="ri-bookmark-line"></i> Class Categories
        </a>
        <a href="{{ route('compulsorysubjectclass.index') }}" class="nav-link">
            <i class="ri-star-line"></i> Compulsory Subjects
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-bookmark-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Categories</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-school-line"></i></div>
                <div class="stat-value text-success" id="statSenior">—</div>
                <div class="stat-label">Senior Categories</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-graduation-cap-line"></i></div>
                <div class="stat-value text-warning" id="statJunior">—</div>
                <div class="stat-label">Junior Categories</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-file-list-line"></i></div>
                <div class="stat-value text-primary" id="statWithAssessment">—</div>
                <div class="stat-label">With Assessment</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--cc-primary)">
                    <i class="ri-list-check me-2"></i>Class Categories List
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create class-category')
                    <button class="btn btn-primary" id="createCategoryBtn">
                        <i class="ri-add-line me-1"></i>Create Category
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> category(ies) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table cc-table w-100 mb-0" id="categoriesTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Assessment</th>
                            <th>Grade Type</th>
                            <th>Subs</th>
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
<div class="modal fade cc-modal" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Create New Class Category</h5>
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

                    {{-- Category Name --}}
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category" id="create-category" class="form-control" placeholder="e.g., Science, Arts, Commercial" required>
                    </div>

                    {{-- Grade Type --}}
                    <div class="mb-3">
                        <label class="form-label">Grade Type <span class="text-danger">*</span></label>
                        <div class="inline-check-group">
                            <div class="form-check">
                                <input class="form-check-input create-senior-rb" type="radio"
                                       name="create_is_senior" id="create-junior" value="0" checked>
                                <label class="form-check-label" for="create-junior">
                                    <span class="cc-badge cc-badge-junior">Junior (A, B, C, D, F)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input create-senior-rb" type="radio"
                                       name="create_is_senior" id="create-senior" value="1">
                                <label class="form-check-label" for="create-senior">
                                    <span class="cc-badge cc-badge-senior">Senior (A1-F9)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Assessment Name --}}
                    <div class="mb-3">
                        <label class="form-label">Assessment Name <span class="text-danger">*</span></label>
                        <input type="text" name="assessments[0][name]" id="create-assessment-name" class="form-control" placeholder="e.g., First Term Examination" required>
                    </div>

                    {{-- Sub Assessments --}}
                    <div class="mb-3">
                        <label class="form-label">Sub Assessments <span class="text-danger">*</span></label>
                        <div id="create-sub-container" class="mb-2"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="create-sub-btn">
                            <i class="ri-add-line me-1"></i>Add Sub Assessment
                        </button>
                        <div class="form-text text-muted mt-2">At least one sub-assessment with a valid max score is required.</div>
                    </div>

                    <div class="alert alert-danger d-none" id="create-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="create-save-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Create Category</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade cc-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit Class Category</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-category-id">
                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    {{-- Category Name --}}
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category" id="edit-category" class="form-control" required>
                    </div>

                    {{-- Grade Type --}}
                    <div class="mb-3">
                        <label class="form-label">Grade Type <span class="text-danger">*</span></label>
                        <div class="inline-check-group">
                            <div class="form-check">
                                <input class="form-check-input edit-senior-rb" type="radio"
                                       name="edit_is_senior" id="edit-junior" value="0">
                                <label class="form-check-label" for="edit-junior">
                                    <span class="cc-badge cc-badge-junior">Junior (A, B, C, D, F)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input edit-senior-rb" type="radio"
                                       name="edit_is_senior" id="edit-senior" value="1">
                                <label class="form-check-label" for="edit-senior">
                                    <span class="cc-badge cc-badge-senior">Senior (A1-F9)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Assessment Name --}}
                    <div class="mb-3">
                        <label class="form-label">Assessment Name <span class="text-danger">*</span></label>
                        <input type="text" name="assessments[0][name]" id="edit-assessment-name" class="form-control" required>
                    </div>

                    {{-- Sub Assessments --}}
                    <div class="mb-3">
                        <label class="form-label">Sub Assessments <span class="text-danger">*</span></label>
                        <div id="edit-sub-container" class="mb-2"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="edit-sub-btn">
                            <i class="ri-add-line me-1"></i>Add Sub Assessment
                        </button>
                    </div>

                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Category</span>
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

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    const CSRF = $('meta[name="csrf-token"]').attr('content');
    let deleteId = null;
    let createSubIndex = 0;
    let editSubIndex = 0;

    // =========================================================================
    // LOADING HELPERS
    // =========================================================================

    const PageLoader = {
        _prog: 0, _timer: null,
        show(label = 'Processing…') {
            $('#cc-loader-label').text(label);
            $('#cc-progress-bar').css('width', '0%');
            $('#cc-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#cc-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#cc-progress-bar').css('width', '100%');
            setTimeout(() => $('#cc-page-loader').removeClass('active'), 350);
        },
    };

    function showModalLoader(id, text) {
        $('#' + id + '-modal-loader-text').text(text || 'Processing…');
        $('#' + id + '-modal-loader').addClass('active');
    }
    function hideModalLoader(id) { $('#' + id + '-modal-loader').removeClass('active'); }

    function btnLoad($btn, label) {
        // Store the original HTML before changing
        if (!$btn.data('original-html')) {
            $btn.data('original-html', $btn.html());
        }
        $btn.prop('disabled', true).addClass('btn-loading');
        if (label) {
            $btn.html('<span class="btn-text">' + label + '</span>');
        }
        return $btn;
    }
    
    function btnReset($btn) {
        var orig = $btn.data('original-html');
        // Only reset if we have the original HTML and the button is in loading state
        if (orig && $btn.hasClass('btn-loading')) {
            $btn.html(orig);
            $btn.removeData('original-html');
        } else if (orig) {
            $btn.html(orig);
            $btn.removeData('original-html');
        }
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
        var id  = 'cc-toast-' + Date.now();
        var $el = $([
            '<div class="cc-toast cc-toast-' + type + '" id="' + id + '">',
            '  <span class="cc-toast-icon"><i class="' + (icons[type] || icons.info) + '"></i></span>',
            '  <div class="cc-toast-body">',
            '    <div class="cc-toast-title">' + title + '</div>',
            msg ? '    <div class="cc-toast-msg">' + msg + '</div>' : '',
            '  </div>',
            '  <button class="cc-toast-close" onclick="$(\'#' + id + '\').remove()">×</button>',
            '</div>'
        ].join(''));
        $('#cc-toast-stack').append($el);
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
    // DATATABLE (server-side)
    // =========================================================================

    var table = $('#categoriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("classcategories.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load categories. Please refresh.');
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
            // Category Name
            { data: 'category_info', orderable: false },
            // Assessment
            { data: 'assessment_info', orderable: false },
            // Grade Type
            { data: 'grade_type', orderable: false },
            // Sub Count
            { data: 'sub_count', orderable: false },
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
            searchPlaceholder: 'Search categories…',
            lengthMenu:      'Show _MENU_ entries',
            info:            'Showing _START_–_END_ of _TOTAL_ categories',
            infoEmpty:       'No categories found',
            zeroRecords:     'No matching categories',
            emptyTable:      'No class categories created yet',
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
        $.get('{{ route("classcategories.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statSenior').text(data.stats.senior);
                $('#statJunior').text(data.stats.junior);
                $('#statWithAssessment').text(data.stats.with_assessment);
            }
        }).fail(function() {
            $('#statTotal, #statSenior, #statJunior, #statWithAssessment').text('—');
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
    // SUB ASSESSMENT HELPERS
    // =========================================================================

    function addSubAssessment(containerId, subData = null, isEdit = false) {
        const container = document.getElementById(containerId);
        const currentIndex = isEdit ? editSubIndex++ : createSubIndex++;
        const subHtml = `
            <div class="sub-assessment-row" data-index="${currentIndex}">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="assessments[0][sub_assessments][${currentIndex}][name]"
                               class="form-control" placeholder="Sub Assessment Name"
                               value="${subData && subData.name ? escapeHtml(subData.name) : ''}">
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="assessments[0][sub_assessments][${currentIndex}][max_score]"
                               class="form-control" placeholder="Max Score" min="0" step="0.01"
                               value="${subData && subData.max_score ? subData.max_score : ''}" required>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="$(this).closest('.sub-assessment-row').remove(); updateCreateBtn();">
                            <i class="ri-delete-bin-line"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
        $(container).append(subHtml);
        updateCreateBtn();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m];
        });
    }

    // Add initial sub assessment for create modal
    addSubAssessment('create-sub-container', null, false);

    $('#create-sub-btn').click(function() {
        addSubAssessment('create-sub-container', null, false);
    });

    $('#edit-sub-btn').click(function() {
        addSubAssessment('edit-sub-container', null, true);
    });

    // =========================================================================
    // CREATE MODAL — guard button
    // =========================================================================

    function updateCreateBtn() {
        var category = $('#create-category').val().trim();
        var assessmentName = $('#create-assessment-name').val().trim();
        var hasSubs = $('#create-sub-container .sub-assessment-row').length > 0;
        var hasValidSub = false;
        
        $('#create-sub-container .sub-assessment-row').each(function() {
            var maxScore = parseFloat($(this).find('input[name*="[max_score]"]').val());
            if (!isNaN(maxScore) && maxScore >= 0) {
                hasValidSub = true;
            }
        });
        
        var ok = category !== '' && assessmentName !== '' && hasSubs && hasValidSub;
        $('#create-save-btn').prop('disabled', !ok);
    }

    $('#create-category, #create-assessment-name').on('input', updateCreateBtn);
    $('#create-sub-container').on('change keyup', '.sub-assessment-row input', updateCreateBtn);

    // ── Open CREATE ───────────────────────────────────────────
    $('#createCategoryBtn').on('click', function() {
        // Reset form fields
        $('#create-category').val('');
        $('#create-assessment-name').val('');
        $('#create-junior').prop('checked', true);
        $('#create-sub-container').empty();
        createSubIndex = 0;
        addSubAssessment('create-sub-container', null, false);
        $('#create-save-btn').prop('disabled', true);
        $('#create-error-msg').addClass('d-none').html('');
        hideModalLoader('create');
        
        // CRITICAL FIX: Reset the button state
        var $btn = $('#create-save-btn');
        var origHtml = '<i class="ri-save-line me-1"></i><span class="btn-text">Create Category</span>';
        $btn.html(origHtml);
        $btn.prop('disabled', true).removeClass('btn-loading');
        $btn.removeData('original-html');
        
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-category-btn', function() {
        var id = $(this).data('id');
        var category = $(this).data('category');
        var isSenior = $(this).data('is_senior');
        var assessmentName = $(this).data('assessment-name');
        var subAssessments = $(this).data('sub-assessments');

        $('#edit-category-id').val(id);
        $('#edit-category').val(category);
        $('#edit-assessment-name').val(assessmentName);

        if (isSenior == 1) {
            $('#edit-senior').prop('checked', true);
        } else {
            $('#edit-junior').prop('checked', true);
        }

        $('#edit-sub-container').empty();
        editSubIndex = 0;

        if (subAssessments && subAssessments.length > 0) {
            subAssessments.forEach(function(sub) {
                addSubAssessment('edit-sub-container', sub, true);
            });
        } else {
            addSubAssessment('edit-sub-container', null, true);
        }

        $('#edit-error-msg').addClass('d-none').html('');
        hideModalLoader('edit');
        btnReset($('#edit-update-btn'));

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // =========================================================================
    // SUBMIT: CREATE
    // =========================================================================

    $('#createForm').on('submit', function(e) {
        e.preventDefault();

        var category = $('#create-category').val().trim();
        var isSenior = $('input[name="create_is_senior"]:checked').val();
        var assessmentName = $('#create-assessment-name').val().trim();

        if (!category) {
            showError('#create-error-msg', 'Please enter a category name.');
            return;
        }
        if (!assessmentName) {
            showError('#create-error-msg', 'Please enter an assessment name.');
            return;
        }

        var subAssessments = [];
        var hasValidSub = false;
        
        $('#create-sub-container .sub-assessment-row').each(function() {
            var name = $(this).find('input[name*="[name]"]').val() || null;
            var maxScore = parseFloat($(this).find('input[name*="[max_score]"]').val());
            if (!isNaN(maxScore) && maxScore >= 0) {
                hasValidSub = true;
                subAssessments.push({ name: name, max_score: maxScore });
            }
        });

        if (!hasValidSub) {
            showError('#create-error-msg', 'Please add at least one valid sub-assessment with a max score.');
            return;
        }

        var $btn = $('#create-save-btn');
        btnLoad($btn, 'Saving…');
        showModalLoader('create', 'Creating category…');
        $('#create-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("classcategories.store") }}',
            type: 'POST',
            data: JSON.stringify({
                category: category,
                is_senior: parseInt(isSenior),
                assessments: [{
                    name: assessmentName,
                    sub_assessments: subAssessments
                }]
            }),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },

            success: function(res) {
                hideModalLoader('create');
                
                if (res.success) {
                    // Reset button before hiding modal
                    btnReset($btn);
                    // Reset the button to original state
                    $btn.html('<i class="ri-save-line me-1"></i><span class="btn-text">Create Category</span>');
                    $btn.prop('disabled', true).removeClass('btn-loading');
                    $btn.removeData('original-html');
                    
                    $('#createModal').modal('hide');
                    toast('success', 'Created!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    btnReset($btn);
                    updateCreateBtn();
                    showError('#create-error-msg', res.message || 'Could not create category.');
                }
            },

            error: function(xhr) {
                hideModalLoader('create');
                btnReset($btn);
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

        var id = $('#edit-category-id').val();
        var category = $('#edit-category').val().trim();
        var isSenior = $('input[name="edit_is_senior"]:checked').val();
        var assessmentName = $('#edit-assessment-name').val().trim();

        if (!category) {
            showError('#edit-error-msg', 'Please enter a category name.');
            return;
        }
        if (!assessmentName) {
            showError('#edit-error-msg', 'Please enter an assessment name.');
            return;
        }

        var subAssessments = [];
        var hasValidSub = false;
        
        $('#edit-sub-container .sub-assessment-row').each(function() {
            var name = $(this).find('input[name*="[name]"]').val() || null;
            var maxScore = parseFloat($(this).find('input[name*="[max_score]"]').val());
            if (!isNaN(maxScore) && maxScore >= 0) {
                hasValidSub = true;
                subAssessments.push({ name: name, max_score: maxScore });
            }
        });

        if (!hasValidSub) {
            showError('#edit-error-msg', 'Please add at least one valid sub-assessment with a max score.');
            return;
        }

        btnLoad($('#edit-update-btn'), 'Updating…');
        showModalLoader('edit', 'Updating category…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("classcategories.updateclasscategory") }}',
            type: 'POST',
            data: JSON.stringify({
                id: id,
                category: category,
                is_senior: parseInt(isSenior),
                assessments: [{
                    name: assessmentName,
                    sub_assessments: subAssessments
                }]
            }),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },

            success: function(res) {
                if (res.success) {
                    // Reset button before hiding modal
                    btnReset($('#edit-update-btn'));
                    $('#edit-update-btn').html('<i class="ri-save-line me-1"></i><span class="btn-text">Update Category</span>');
                    $('#edit-update-btn').removeData('original-html');
                    
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('edit');
                    btnReset($('#edit-update-btn'));
                    showError('#edit-error-msg', res.message || 'Could not update category.');
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

    $(document).on('click', '.delete-category-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-item-title').text($(this).data('name') || 'this category');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: '{{ url("classcategories") }}/' + deleteId,
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
// DELETE: BULK - FIXED
// =========================================================================

function doBulkDelete() {
    var ids = [];
    $('.row-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    
    if (ids.length === 0) {
        toast('warning', 'No Selection', 'Please select at least one category to delete.');
        return;
    }

    Swal.fire({
        title: 'Delete ' + ids.length + ' category(ies)?',
        html: 'This will permanently remove the selected categories and all associated data.<br><strong>This action cannot be undone!</strong>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, delete them!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        showLoaderOnConfirm: true,
        preConfirm: function() {
            return new Promise(function(resolve, reject) {
                PageLoader.show('Deleting categories…');
                
                // Send as JSON with proper array format
                $.ajax({
                    url: '{{ route("classcategories.bulk-destroy") }}',
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
                            reject(res.message || 'Failed to delete categories');
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
            toast('success', 'Deleted!', result.value.message || 'Categories deleted successfully.');
            table.ajax.reload();
            loadStats();
            $('#selectAll').prop('checked', false);
            updateBulkBar();
        }
    }).catch(function(error) {
        toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete categories.');
    });
}
    
    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection