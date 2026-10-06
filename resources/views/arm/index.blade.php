{{-- resources/views/arm/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sa-primary:  #1e3a5f;
    --sa-accent:   #2563eb;
    --sa-success:  #16a34a;
    --sa-warning:  #d97706;
    --sa-danger:   #dc2626;
    --sa-muted:    #6b7280;
    --sa-border:   #e2e8f0;
    --sa-bg:       #f8fafc;
    --sa-radius:   12px;
    --sa-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ────────────────────────────────────────────────── */
.sa-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sa-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.sa-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.sa-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.sa-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sa-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--sa-border);
    border-radius:var(--sa-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sa-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sa-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sa-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ───────────────────────────────────────────────── */
.sa-table th {
    background:var(--sa-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.sa-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--sa-border); font-size:13px;
}
.sa-table tr:hover td { background:#f0f9ff; }

/* ── Badges ──────────────────────────────────────────────── */
.sa-badge {
    display:inline-flex; align-items:center;
    padding:3px 9px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.sa-badge-arm { background:#dbeafe; color:#2563eb; }

/* ── DataTables overrides ────────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--sa-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--sa-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--sa-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--sa-muted); }
.dataTables_wrapper .paginate_button  {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--sa-accent) !important;
    border-color:var(--sa-accent) !important; color:#fff !important;
}

/* ── Modals ──────────────────────────────────────────────── */
.sa-modal .modal-content {
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
    border:1.5px solid var(--sa-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--sa-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
textarea.form-control { resize:vertical; min-height:80px; }

/* ── Bulk bar ────────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Full-page loader overlay ────────────────────────────── */
#sa-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#sa-page-loader.active { opacity:1; visibility:visible; }
.sa-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px;
}
.sa-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0; border-top-color:var(--sa-accent);
    border-radius:50%; animation:sa-spin .75s linear infinite;
}
@keyframes sa-spin { to { transform:rotate(360deg); } }
.sa-loader-label { font-size:14px; font-weight:600; color:var(--sa-primary); margin-bottom:12px; }
.sa-progress-wrap {
    width:160px; height:5px; background:#e2e8f0;
    border-radius:99px; overflow:hidden; margin:0 auto;
}
.sa-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--sa-accent), #0d9488);
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
    border-top-color:var(--sa-accent); border-radius:50%;
    animation:sa-spin .7s linear infinite;
}
.modal-body-loader .mbl-text { font-size:13px; font-weight:600; color:var(--sa-primary); }

/* ── Toast notifications ─────────────────────────────────── */
#sa-toast-stack {
    position:fixed; bottom:24px; right:24px; z-index:10000;
    display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none;
}
.sa-toast {
    pointer-events:all; background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--sa-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.sa-toast.show { transform:translateX(0); }
.sa-toast.sa-toast-success { border-left-color:var(--sa-success); }
.sa-toast.sa-toast-error   { border-left-color:var(--sa-danger);  }
.sa-toast.sa-toast-warning { border-left-color:var(--sa-warning); }
.sa-toast .sa-toast-icon { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.sa-toast-success .sa-toast-icon { color:var(--sa-success); }
.sa-toast-error   .sa-toast-icon { color:var(--sa-danger);  }
.sa-toast-warning .sa-toast-icon { color:var(--sa-warning); }
.sa-toast .sa-toast-body { flex:1; }
.sa-toast .sa-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.sa-toast .sa-toast-msg   { font-size:12px; color:var(--sa-muted); line-height:1.4; }
.sa-toast .sa-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--sa-muted); font-size:16px; line-height:1; padding:0; flex-shrink:0;
}

/* ── Button loading state ────────────────────────────────── */
.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:''; position:absolute; inset:0; margin:auto;
    width:16px; height:16px; border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%; animation:sa-spin .65s linear infinite;
}
.btn-loading.btn-outline-secondary::after,
.btn-loading.btn-outline-danger::after { border-top-color:currentColor; }
.btn-loading.btn-light::after { border-top-color:#374151; }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

{{-- ═══ Full-page loader overlay ═══ --}}
<div id="sa-page-loader">
    <div class="sa-loader-card">
        <div class="sa-loader-spinner"></div>
        <div class="sa-loader-label" id="sa-loader-label">Processing…</div>
        <div class="sa-progress-wrap">
            <div class="sa-progress-bar" id="sa-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast stack ═══ --}}
<div id="sa-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="sa-hero">
        <h1><i class="ri-building-line me-2"></i>School Arm Management</h1>
        <p>Manage school arms/classes divisions for organizing student classes.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Arms</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-bar-chart-line"></i></div>
                <div class="stat-value text-primary" id="statShowing">—</div>
                <div class="stat-label">Showing Now</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-school-line"></i></div>
                <div class="stat-value text-success" id="statWithClasses">—</div>
                <div class="stat-label">With Classes</div>
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
                <h5 class="mb-0 fw-semibold" style="color:var(--sa-primary)">
                    <i class="ri-list-check me-2"></i>School Arms List
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create school-arm')
                    <button class="btn btn-primary" id="createArmBtn">
                        <i class="ri-add-line me-1"></i>Create Arm
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> arm(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table sa-table w-100 mb-0" id="armsTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Arm Name</th>
                            <th>Description</th>
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

{{-- ═══════════════════════ CREATE MODAL ════════════════════ --}}
<div class="modal fade sa-modal" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Create New School Arm</h5>
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

                    {{-- Arm Name --}}
                    <div class="mb-3">
                        <label class="form-label">Arm Name <span class="text-danger">*</span></label>
                        <input type="text" name="arm" id="create-arm" class="form-control" placeholder="e.g., A, B, C, or Science, Arts" required>
                        <small class="text-muted">Enter the arm/class division name</small>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label">Description / Remark</label>
                        <textarea name="description" id="create-description" class="form-control" placeholder="Enter a brief description of this arm" rows="3"></textarea>
                    </div>

                    <div class="alert alert-danger d-none" id="create-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="create-save-btn" disabled>
                        <i class="ri-save-line me-1"></i><span class="btn-text">Create Arm</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ EDIT MODAL ══════════════════════ --}}
<div class="modal fade sa-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit School Arm</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-arm-id">
                <div class="modal-body-loader" id="edit-modal-loader">
                    <div class="inner">
                        <div class="mbl-spinner"></div>
                        <div class="mbl-text" id="edit-modal-loader-text">Updating…</div>
                    </div>
                </div>
                <div class="modal-body p-4" style="position:relative">

                    {{-- Arm Name --}}
                    <div class="mb-3">
                        <label class="form-label">Arm Name <span class="text-danger">*</span></label>
                        <input type="text" name="arm" id="edit-arm" class="form-control" required>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label">Description / Remark</label>
                        <textarea name="description" id="edit-description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="edit-update-btn">
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Arm</span>
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
            $('#sa-loader-label').text(label);
            $('#sa-progress-bar').css('width', '0%');
            $('#sa-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#sa-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#sa-progress-bar').css('width', '100%');
            setTimeout(() => $('#sa-page-loader').removeClass('active'), 350);
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
        var id  = 'sa-toast-' + Date.now();
        var $el = $([
            '<div class="sa-toast sa-toast-' + type + '" id="' + id + '">',
            '  <span class="sa-toast-icon"><i class="' + (icons[type] || icons.info) + '"></i></span>',
            '  <div class="sa-toast-body">',
            '    <div class="sa-toast-title">' + title + '</div>',
            msg ? '    <div class="sa-toast-msg">' + msg + '</div>' : '',
            '  </div>',
            '  <button class="sa-toast-close" onclick="$(\'#' + id + '\').remove()">×</button>',
            '</div>'
        ].join(''));
        $('#sa-toast-stack').append($el);
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

    var table = $('#armsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("schoolarm.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load arms. Please refresh.');
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
            // Arm Name
            { data: 'arm_info', orderable: false },
            // Description
            { data: 'description_info', orderable: false },
            // Usage
            { data: 'usage_count', orderable: false },
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
            searchPlaceholder: 'Search arms…',
            lengthMenu:      'Show _MENU_ entries',
            info:            'Showing _START_–_END_ of _TOTAL_ arms',
            infoEmpty:       'No arms found',
            zeroRecords:     'No matching arms',
            emptyTable:      'No school arms created yet',
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
        $.get('{{ route("schoolarm.stats") }}', function(data) {
            if (data.stats) {
                $('#statTotal').text(data.stats.total);
                $('#statShowing').text(data.stats.showing);
                $('#statWithClasses').text(data.stats.with_classes);
                $('#statRecent').text(data.stats.recently_updated);
            }
        }).fail(function() {
            $('#statTotal, #statShowing, #statWithClasses, #statRecent').text('—');
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
        var ok = $('#create-arm').val().trim() !== '';
        $('#create-save-btn').prop('disabled', !ok);
    }

    $('#create-arm').on('input', updateCreateBtn);

    // ── Open CREATE ───────────────────────────────────────────
    $('#createArmBtn').on('click', function() {
        $('#create-arm').val('');
        $('#create-description').val('');
        $('#create-save-btn').prop('disabled', true);
        $('#create-error-msg').addClass('d-none').html('');
        hideModalLoader('create');
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });

    // =========================================================================
    // EDIT MODAL
    // =========================================================================

    $(document).on('click', '.edit-arm-btn', function() {
        var id = $(this).data('id');
        var arm = $(this).data('arm');
        var description = $(this).data('description');

        $('#edit-arm-id').val(id);
        $('#edit-arm').val(arm);
        $('#edit-description').val(description || '');

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

        var arm = $('#create-arm').val().trim();
        var description = $('#create-description').val().trim();

        if (!arm) {
            showError('#create-error-msg', 'Please enter an arm name.');
            return;
        }

        btnLoad($('#create-save-btn'), 'Saving…');
        showModalLoader('create', 'Creating arm…');
        $('#create-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("schoolarm.store") }}',
            type: 'POST',
            data: {
                arm: arm,
                description: description,
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
                    showError('#create-error-msg', res.message || 'Could not create arm.');
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

        var id = $('#edit-arm-id').val();
        var arm = $('#edit-arm').val().trim();
        var description = $('#edit-description').val().trim();

        if (!arm) {
            showError('#edit-error-msg', 'Please enter an arm name.');
            return;
        }

        btnLoad($('#edit-update-btn'), 'Updating…');
        showModalLoader('edit', 'Updating arm…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("schoolarm.updatearm") }}',
            type: 'POST',
            data: {
                id: id,
                arm: arm,
                description: description,
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
                    showError('#edit-error-msg', res.message || 'Could not update arm.');
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

    $(document).on('click', '.delete-arm-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-item-title').text($(this).data('name') || 'this arm');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        btnLoad($btn, 'Deleting…');

        $.ajax({
            url: '{{ route("schoolarm.deletearm") }}',
            type: 'POST',
            data: { armid: deleteId, _token: CSRF },
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
            toast('warning', 'No Selection', 'Please select at least one arm to delete.');
            return;
        }

        Swal.fire({
            title: 'Delete ' + ids.length + ' arm(s)?',
            html: 'This will permanently remove the selected arms.<br><strong>This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting arms…');
                    
                    $.ajax({
                        url: '{{ route("schoolarm.bulk-destroy") }}',
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
                                reject(res.message || 'Failed to delete arms');
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
                toast('success', 'Deleted!', result.value.message || 'Arms deleted successfully.');
                table.ajax.reload();
                loadStats();
                $('#selectAll').prop('checked', false);
                updateBulkBar();
            }
        }).catch(function(error) {
            toast('error', 'Failed', typeof error === 'string' ? error : 'Could not delete arms.');
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);

    bindCheckboxes();
});
</script>
@endsection