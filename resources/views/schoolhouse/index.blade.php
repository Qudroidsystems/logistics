@extends('layouts.master')

@section('content')
<style>
:root {
    --sh-primary:  #1e3a5f;
    --sh-accent:   #2563eb;
    --sh-success:  #16a34a;
    --sh-warning:  #d97706;
    --sh-danger:   #dc2626;
    --sh-muted:    #6b7280;
    --sh-border:   #e2e8f0;
    --sh-bg:       #f8fafc;
    --sh-radius:   12px;
    --sh-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.sh-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sh-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.sh-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.sh-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.sh-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sh-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--sh-border);
    border-radius:var(--sh-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sh-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sh-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sh-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.sh-table th {
    background:var(--sh-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.sh-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--sh-border); font-size:13px;
}
.sh-table tr:hover td { background:#f0f9ff; }

/* ── Badges ──────────────────────────────────────────────── */
.sh-badge {
    display:inline-flex; align-items:center;
    padding:3px 9px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.sh-badge-colour { padding:4px 12px; color:#fff; border-radius:20px; }
.sh-badge-term    { background:#dbeafe; color:#2563eb; }
.sh-badge-session { background:#ccfbf1; color:#0f766e; }

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--sh-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--sh-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--sh-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--sh-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--sh-accent) !important;
    border-color:var(--sh-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.sh-modal .modal-content {
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
    border:1.5px solid var(--sh-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--sh-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#sh-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#sh-page-loader.active { opacity:1; visibility:visible; }
.sh-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px;
}
.sh-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0; border-top-color:var(--sh-accent);
    border-radius:50%; animation:sh-spin .75s linear infinite;
}
@keyframes sh-spin { to { transform:rotate(360deg); } }
.sh-loader-label { font-size:14px; font-weight:600; color:var(--sh-primary); margin-bottom:12px; }
.sh-progress-wrap {
    width:160px; height:5px; background:#e2e8f0;
    border-radius:99px; overflow:hidden; margin:0 auto;
}
.sh-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--sh-accent), #0d9488);
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
    border-top-color:var(--sh-accent); border-radius:50%;
    animation:sh-spin .7s linear infinite;
}
.modal-body-loader .mbl-text { font-size:13px; font-weight:600; color:var(--sh-primary); }

/* ── Toast notifications ─────────────────────────────────── */
#sh-toast-stack {
    position:fixed; bottom:24px; right:24px; z-index:10000;
    display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none;
}
.sh-toast {
    pointer-events:all; background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--sh-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.sh-toast.show { transform:translateX(0); }
.sh-toast.sh-toast-success { border-left-color:var(--sh-success); }
.sh-toast.sh-toast-error   { border-left-color:var(--sh-danger);  }
.sh-toast.sh-toast-warning { border-left-color:var(--sh-warning); }
.sh-toast .sh-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.sh-toast-success .sh-toast-icon { color:var(--sh-success); }
.sh-toast-error   .sh-toast-icon { color:var(--sh-danger);  }
.sh-toast-warning .sh-toast-icon { color:var(--sh-warning); }
.sh-toast .sh-toast-body { flex:1; }
.sh-toast .sh-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.sh-toast .sh-toast-msg   { font-size:12px; color:var(--sh-muted); line-height:1.4; }
.sh-toast .sh-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--sh-muted); font-size:16px; line-height:1; padding:0; flex-shrink:0;
}

/* ── Button loading state ────────────────────────────────── */
.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:''; position:absolute; inset:0; margin:auto;
    width:16px; height:16px; border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%; animation:sh-spin .65s linear infinite;
}
.btn-loading.btn-outline-secondary::after,
.btn-loading.btn-outline-danger::after { border-top-color:currentColor; }
.btn-loading.btn-light::after { border-top-color:#374151; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="sh-page-loader">
    <div class="sh-loader-card">
        <div class="sh-loader-spinner"></div>
        <div class="sh-loader-label" id="sh-loader-label">Processing…</div>
        <div class="sh-progress-wrap">
            <div class="sh-progress-bar" id="sh-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast stack ═══ --}}
<div id="sh-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="sh-hero">
        <h1><i class="ri-home-2-line me-2"></i>School House Management</h1>
        <p>Manage school houses, their colours, masters, terms, and sessions.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-home-2-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Houses</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-line"></i></div>
                <div class="stat-value text-primary" id="statMasters">—</div>
                <div class="stat-label">Unique Masters</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-bookmark-line"></i></div>
                <div class="stat-value text-success" id="statTerms">—</div>
                <div class="stat-label">Active Terms</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-event-line"></i></div>
                <div class="stat-value text-warning" id="statSessions">—</div>
                <div class="stat-label">Active Sessions</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sh-primary)">
                    <i class="ri-list-check me-2"></i>School Houses List
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create schoolhouse')
                    <button class="btn btn-primary" id="createHouseBtn">
                        <i class="ri-add-line me-1"></i>Create House
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> house(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table sh-table w-100 mb-0" id="housesTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>House</th>
                            <th>Colour</th>
                            <th>House Master</th>
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
<div class="modal fade sh-modal" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Create New School House</h5>
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

                    {{-- House Name --}}
                    <div class="mb-3">
                        <label class="form-label">House Name <span class="text-danger">*</span></label>
                        <input type="text" name="house" id="create-house" class="form-control" placeholder="Enter house name" required>
                    </div>

                    {{-- House Colour --}}
                    <div class="mb-3">
                        <label class="form-label">House Colour <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" name="housecolour" id="create-housecolour" class="form-control" placeholder="Enter colour (e.g., red, #FF0000)" required>
                            <input type="color" id="create-colour-picker" class="form-control" style="width: 60px; padding: 2px; height: 38px;" value="#2563eb">
                        </div>
                        <small class="text-muted">Enter a valid CSS color (name, hex, or RGB)</small>
                    </div>

                    {{-- House Master --}}
                    <div class="mb-3">
                        <label class="form-label">House Master <span class="text-danger">*</span></label>
                        <select name="housemasterid" id="create-housemasterid" class="form-select" required>
                            <option value="">— Select House Master —</option>
                            @foreach ($staff as $s)
                                <option value="{{ $s->userid }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Term --}}
                    <div class="mb-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="termid" id="create-termid" class="form-select" required>
                            <option value="">— Select Term —</option>
                            @foreach ($schoolterm as $term)
                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Session --}}
                    <div class="mb-3">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <select name="sessionid" id="create-sessionid" class="form-select" required>
                            <option value="">— Select Session —</option>
                            @foreach ($schoolsession as $session)
                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-danger d-none" id="create-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="create-save-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Create House</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade sh-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit School House</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-house-id">
                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    {{-- House Name --}}
                    <div class="mb-3">
                        <label class="form-label">House Name <span class="text-danger">*</span></label>
                        <input type="text" name="house" id="edit-house" class="form-control" required>
                    </div>

                    {{-- House Colour --}}
                    <div class="mb-3">
                        <label class="form-label">House Colour <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" name="housecolour" id="edit-housecolour" class="form-control" required>
                            <input type="color" id="edit-colour-picker" class="form-control" style="width: 60px; padding: 2px; height: 38px;" value="#2563eb">
                        </div>
                    </div>

                    {{-- House Master --}}
                    <div class="mb-3">
                        <label class="form-label">House Master <span class="text-danger">*</span></label>
                        <select name="housemasterid" id="edit-housemasterid" class="form-select" required>
                            <option value="">— Select House Master —</option>
                            @foreach ($staff as $s)
                                <option value="{{ $s->userid }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Term --}}
                    <div class="mb-3">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="termid" id="edit-termid" class="form-select" required>
                            <option value="">— Select Term —</option>
                            @foreach ($schoolterm as $term)
                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Session --}}
                    <div class="mb-3">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <select name="sessionid" id="edit-sessionid" class="form-select" required>
                            <option value="">— Select Session —</option>
                            @foreach ($schoolsession as $session)
                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update House</span>
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

    // =========================================================================
    // LOADING HELPERS
    // =========================================================================

    const PageLoader = {
        _prog: 0, _timer: null,
        show(label = 'Processing…') {
            $('#sh-loader-label').text(label);
            $('#sh-progress-bar').css('width', '0%');
            $('#sh-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#sh-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#sh-progress-bar').css('width', '100%');
            setTimeout(() => $('#sh-page-loader').removeClass('active'), 350);
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
        var id  = 'sh-toast-' + Date.now();
        var $el = $([
            '<div class="sh-toast sh-toast-' + type + '" id="' + id + '">',
            '  <span class="sh-toast-icon"><i class="' + (icons[type] || icons.info) + '"></i></span>',
            '  <div class="sh-toast-body">',
            '    <div class="sh-toast-title">' + title + '</div>',
            msg ? '    <div class="sh-toast-msg">' + msg + '</div>' : '',
            '  </div>',
            '  <button class="sh-toast-close" onclick="$(\'#' + id + '\').remove()">×</button>',
            '</div>'
        ].join(''));
        $('#sh-toast-stack').append($el);
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
    // COLOUR PICKER SYNC
    // =========================================================================

    $('#create-colour-picker').on('input', function() {
        $('#create-housecolour').val($(this).val());
        updateCreateBtn();
    });

    $('#create-housecolour').on('input', function() {
        $('#create-colour-picker').val($(this).val());
        updateCreateBtn();
    });

    $('#edit-colour-picker').on('input', function() {
        $('#edit-housecolour').val($(this).val());
    });

    $('#edit-housecolour').on('input', function() {
        $('#edit-colour-picker').val($(this).val());
    });

    // =========================================================================
    // DATATABLE (server-side)
    // =========================================================================

    var table = $('#housesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("schoolhouse.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load houses. Please refresh.');
            }
        },
        columns: [
            // Checkbox
            {
                data: 'checkbox', orderable: false, searchable: false,
                render: function(data) {
                    return data;
                }
            },
            // Row index
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            // House Name
            { data: 'house_info', orderable: false },
            // Colour
            { data: 'colour_info', orderable: false },
            // House Master
            { data: 'master_info', orderable: false },
            // Term
            { data: 'term_info', orderable: false },
            // Session
            { data: 'session_info', orderable: false },
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
            searchPlaceholder: 'Search houses…',
            lengthMenu:      'Show _MENU_ entries',
            info:            'Showing _START_–_END_ of _TOTAL_ houses',
            infoEmpty:       'No houses found',
            zeroRecords:     'No matching houses',
            emptyTable:      'No school houses created yet',
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
        $.get('{{ route("schoolhouse.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statMasters').text(data.stats.unique_masters);
                $('#statTerms').text(data.stats.unique_terms);
                $('#statSessions').text(data.stats.unique_sessions);
            }
        }).fail(function() {
            $('#statTotal, #statMasters, #statTerms, #statSessions').text('—');
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
    // CREATE MODAL — guard button
    // =========================================================================

    function updateCreateBtn() {
        var ok = $('#create-house').val().trim() !== '' &&
                 $('#create-housecolour').val().trim() !== '' &&
                 $('#create-housemasterid').val() !== '' &&
                 $('#create-termid').val() !== '' &&
                 $('#create-sessionid').val() !== '';
        $('#create-save-btn').prop('disabled', !ok);
    }

    $('#create-house, #create-housecolour, #create-housemasterid, #create-termid, #create-sessionid').on('change input', updateCreateBtn);

    // ── Open CREATE ───────────────────────────────────────────
    $('#createHouseBtn').on('click', function() {
        $('#create-house').val('');
        $('#create-housecolour').val('#2563eb');
        $('#create-colour-picker').val('#2563eb');
        $('#create-housemasterid').val('');
        $('#create-termid').val('');
        $('#create-sessionid').val('');
        $('#create-save-btn').prop('disabled', true);
        $('#create-error-msg').addClass('d-none').html('');
        hideModalLoader('create');
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-house-btn', function() {
        var id = $(this).data('id');
        var house = $(this).data('house');
        var housecolour = $(this).data('housecolour');
        var housemasterid = $(this).data('housemasterid');
        var termid = $(this).data('termid');
        var sessionid = $(this).data('sessionid');

        $('#edit-house-id').val(id);
        $('#edit-house').val(house);
        $('#edit-housecolour').val(housecolour || '#2563eb');
        $('#edit-colour-picker').val(housecolour || '#2563eb');
        $('#edit-housemasterid').val(housemasterid);
        $('#edit-termid').val(termid);
        $('#edit-sessionid').val(sessionid);

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

        var house = $('#create-house').val().trim();
        var housecolour = $('#create-housecolour').val().trim();
        var housemasterid = $('#create-housemasterid').val();
        var termid = $('#create-termid').val();
        var sessionid = $('#create-sessionid').val();

        if (!house) {
            showError('#create-error-msg', 'Please enter a house name.');
            return;
        }
        if (!housecolour) {
            showError('#create-error-msg', 'Please enter a house colour.');
            return;
        }
        if (!housemasterid) {
            showError('#create-error-msg', 'Please select a house master.');
            return;
        }
        if (!termid) {
            showError('#create-error-msg', 'Please select a term.');
            return;
        }
        if (!sessionid) {
            showError('#create-error-msg', 'Please select a session.');
            return;
        }

        btnLoad($('#create-save-btn'), 'Saving…');
        showModalLoader('create', 'Creating house…');
        $('#create-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("schoolhouse.store") }}',
            type: 'POST',
            data: {
                house: house,
                housecolour: housecolour,
                housemasterid: housemasterid,
                termid: termid,
                sessionid: sessionid,
                _token: CSRF,
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            success: function(res) {
                if (res.success) {
                    $('#createModal').modal('hide');
                    toast('success', 'Created!', res.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    hideModalLoader('create');
                    btnReset($('#create-save-btn'));
                    updateCreateBtn();
                    showError('#create-error-msg', res.message || 'Could not create house.');
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

        var id = $('#edit-house-id').val();
        var house = $('#edit-house').val().trim();
        var housecolour = $('#edit-housecolour').val().trim();
        var housemasterid = $('#edit-housemasterid').val();
        var termid = $('#edit-termid').val();
        var sessionid = $('#edit-sessionid').val();

        if (!house) {
            showError('#edit-error-msg', 'Please enter a house name.');
            return;
        }
        if (!housecolour) {
            showError('#edit-error-msg', 'Please enter a house colour.');
            return;
        }
        if (!housemasterid) {
            showError('#edit-error-msg', 'Please select a house master.');
            return;
        }
        if (!termid) {
            showError('#edit-error-msg', 'Please select a term.');
            return;
        }
        if (!sessionid) {
            showError('#edit-error-msg', 'Please select a session.');
            return;
        }

        btnLoad($('#edit-update-btn'), 'Updating…');
        showModalLoader('edit', 'Updating house…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("schoolhouse.updatehouse") }}',
            type: 'POST',
            data: {
                id: id,
                house: house,
                housecolour: housecolour,
                housemasterid: housemasterid,
                termid: termid,
                sessionid: sessionid,
                _token: CSRF,
            },
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
                    showError('#edit-error-msg', res.message || 'Could not update house.');
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

    $(document).on('click', '.delete-house-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-item-title').text($(this).data('name') || 'this house');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: '{{ route("schoolhouse.deletehouse") }}',
            type: 'POST',
            data: { houseid: deleteId, _token: CSRF },
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
            toast('warning', 'No Selection', 'Please select at least one house to delete.');
            return;
        }

        Swal.fire({
            title: 'Delete ' + ids.length + ' house(s)?',
            html: 'This will permanently remove the selected houses.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting houses…');
                    
                    $.ajax({
                        url: '{{ route("schoolhouse.bulk-destroy") }}',
                        type: 'POST',
                        data: {
                            ids: ids,
                            _token: CSRF
                        },
                        traditional: true,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function(res) {
                            PageLoader.hide();
                            if (res.success) {
                                resolve(res);
                            } else {
                                reject(res.message || 'Failed to delete houses');
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
                toast('success', 'Deleted!', result.value.message || 'Houses deleted successfully.');
                table.ajax.reload();
                loadStats();
                $('#selectAll').prop('checked', false);
                updateBulkBar();
            }
        }).catch(function(error) {
            toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete houses.');
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection