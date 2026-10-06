{{-- resources/views/schoolbilltermsession/index.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style>
:root {
    --ts-primary: #1e3a5f;
    --ts-accent:  #2563eb;
    --ts-success: #16a34a;
    --ts-warning: #d97706;
    --ts-danger:  #dc2626;
    --ts-muted:   #6b7280;
    --ts-border:  #e2e8f0;
    --ts-bg:      #f8fafc;
    --ts-radius:  12px;
    --ts-shadow:  0 2px 8px rgba(0,0,0,.08);
}
.ts-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #0f766e 60%, #0891b2 100%);
    border-radius: var(--ts-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.ts-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%; }
.ts-hero::after  { content:''; position:absolute; bottom:-80px; left:-30px; width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%; }
.ts-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.ts-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.stat-card { background:#fff; border:1px solid var(--ts-border); border-radius:var(--ts-radius); padding:18px 20px; transition:transform .15s, box-shadow .15s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--ts-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--ts-primary); }
.stat-card .stat-label { font-size:12px; color:var(--ts-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.ts-table th { background:var(--ts-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.ts-table td { padding:12px 16px; vertical-align:middle; border-bottom:1px solid var(--ts-border); font-size:13px; }
.ts-table tr:hover td { background:#f0fdfa; }

.ts-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.ts-badge-term    { background:#dbeafe; color:#2563eb; }
.ts-badge-session { background:#ccfbf1; color:#0f766e; }

.dataTables_wrapper .dataTables_filter input { border:1.5px solid var(--ts-border); border-radius:8px; padding:7px 14px; margin-left:8px; font-size:13px; transition:border .15s; }
.dataTables_wrapper .dataTables_filter input:focus { border-color:var(--ts-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.dataTables_wrapper .dataTables_length select { border:1.5px solid var(--ts-border); border-radius:8px; padding:6px 10px; margin:0 6px; font-size:13px; }
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--ts-muted); }
.dataTables_wrapper .paginate_button { border-radius:6px !important; font-size:13px !important; padding:4px 10px !important; }
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover { background:var(--ts-accent) !important; border-color:var(--ts-accent) !important; color:#fff !important; }

#tsModal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar { background:linear-gradient(135deg, #1e3a5f 0%, #0f766e 100%); padding:22px 28px; position:relative; overflow:hidden; }
.modal-hero-bar::before { content:''; position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,.07); border-radius:50%; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border:1.5px solid var(--ts-border); border-radius:8px; font-size:13px; padding:9px 14px; transition:border .15s; }
.form-control:focus, .form-select:focus { border-color:var(--ts-accent); box-shadow:0 0 0 3px rgba(37,99,235,.1); }

.check-group { border:1.5px solid var(--ts-border); border-radius:10px; padding:14px 16px; background:var(--ts-bg); max-height:160px; overflow-y:auto; }
.check-group .form-check { margin-bottom:6px; }
.check-group .form-check:last-child { margin-bottom:0; }
.check-group .form-check-input:checked { background-color:var(--ts-accent); border-color:var(--ts-accent); }
.select-all-bar { background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:8px; padding:7px 12px; margin-bottom:8px; display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--ts-accent); }

.bulk-bar { background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:10px 16px; display:none; align-items:center; gap:12px; margin-bottom:12px; }
.bulk-bar.show { display:flex; }

.edit-info-note { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; font-size:12px; color:#2563eb; margin-bottom:16px; }

#ts-page-loader { position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; opacity:0; visibility:hidden; transition:opacity .22s, visibility .22s; }
#ts-page-loader.active { opacity:1; visibility:visible; }
.ts-loader-card { background:#fff; border-radius:16px; padding:32px 40px; text-align:center; box-shadow:0 24px 64px rgba(0,0,0,.22); min-width:220px; }
.ts-loader-spinner { width:52px; height:52px; margin:0 auto 16px; border:4px solid #e2e8f0; border-top-color:var(--ts-accent); border-radius:50%; animation:ts-spin .75s linear infinite; }
@keyframes ts-spin { to { transform:rotate(360deg); } }
.ts-loader-label { font-size:14px; font-weight:600; color:var(--ts-primary); margin-bottom:12px; }

#ts-toast-stack { position:fixed; bottom:24px; right:24px; z-index:10000; display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none; }
.ts-toast { pointer-events:all; background:#fff; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.14); padding:14px 18px; min-width:280px; max-width:360px; display:flex; align-items:flex-start; gap:12px; border-left:4px solid var(--ts-accent); transform:translateX(120%); transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.ts-toast.show { transform:translateX(0); }
.ts-toast-success { border-left-color:var(--ts-success); }
.ts-toast-error   { border-left-color:var(--ts-danger);  }
.ts-toast-warning { border-left-color:var(--ts-warning); }
.ts-toast .ts-toast-icon { font-size:20px; flex-shrink:0; }
.ts-toast .ts-toast-body { flex:1; }
.ts-toast .ts-toast-title { font-size:13px; font-weight:700; color:#111827; }
.ts-toast .ts-toast-msg   { font-size:12px; color:var(--ts-muted); }
.ts-toast .ts-toast-close { background:none; border:none; cursor:pointer; color:var(--ts-muted); font-size:16px; }

.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after { content:''; position:absolute; inset:0; margin:auto; width:16px; height:16px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:ts-spin .65s linear infinite; }
</style>

<div id="ts-page-loader"><div class="ts-loader-card"><div class="ts-loader-spinner"></div><div class="ts-loader-label" id="ts-loader-label">Processing…</div></div></div>
<div id="ts-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="ts-hero">
        <h1><i class="ri-calendar-check-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Assign school bills to classes, terms, and sessions in one step.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-links-line"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Assignments</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-file-list-3-line"></i></div><div class="stat-value text-primary" id="statBills">—</div><div class="stat-label">Unique Bills Assigned</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-calendar-2-line"></i></div><div class="stat-value text-success" id="statSessions">—</div><div class="stat-label">Active Sessions</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="ri-money-dollar-circle-line"></i></div><div class="stat-value text-warning" id="statAmount">—</div><div class="stat-label">Total Assigned Value</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--ts-primary)">
                    <i class="ri-list-check me-2"></i>All Assignments
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create school-bill-for-term-session')
                    <button class="btn btn-primary" id="createAssignmentBtn">
                        <i class="ri-add-line me-1"></i>Create Assignment
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> assignment(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table ts-table w-100 mb-0" id="assignmentsTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>#</th>
                            <th>School Bill</th>
                            <th>Class</th>
                            <th>Term | Session</th>
                            <th>Created By</th>
                            <th>Last Updated</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div></div></div>

{{-- CREATE / EDIT MODAL --}}
<div class="modal fade" id="tsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5 id="modalTitle"><i class="ri-links-line me-2"></i>Create Assignment</h5>
            </div>
            <form id="tsForm">
                @csrf
                <input type="hidden" id="assignmentId">
                <div class="modal-body p-4">

                    <div class="edit-info-note d-none" id="editNote">
                        <i class="ri-information-line me-1"></i>
                        You are editing a <strong>single</strong> assignment record. To reassign multiple classes or terms, delete and recreate.
                    </div>

                    <div class="mb-4">
                        <label class="form-label">School Bill <span class="text-danger">*</span></label>
                        <select id="bill_id" class="form-select" required>
                            <option value="">— Select School Bill —</option>
                            @foreach($schoolbills as $bill)
                                <option value="{{ $bill->id }}">{{ $bill->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4" id="classCheckboxGroup">
                        <label class="form-label">
                            Classes <span class="text-danger">*</span>
                            <span class="text-muted fw-normal small ms-1">(select one or more)</span>
                        </label>
                        <div class="select-all-bar">
                            <input type="checkbox" class="form-check-input" id="selectAllClasses">
                            <label for="selectAllClasses" class="mb-0" style="cursor:pointer">Select All Classes</label>
                        </div>
                        <div class="check-group" id="classCheckboxes">
                            @foreach($schoolclasses as $class)
                                <div class="form-check">
                                    <input class="form-check-input class-cb" type="checkbox"
                                           value="{{ $class->id }}" id="cls_{{ $class->id }}">
                                    <label class="form-check-label" for="cls_{{ $class->id }}">
                                        {{ $class->label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-4 d-none" id="classSingleGroup">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select id="class_id_single" class="form-select">
                            <option value="">— Select Class —</option>
                            @foreach($schoolclasses as $class)
                                <option value="{{ $class->id }}">{{ $class->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4" id="termCheckboxGroup">
                        <label class="form-label">
                            Terms <span class="text-danger">*</span>
                            <span class="text-muted fw-normal small ms-1">(select one or more)</span>
                        </label>
                        <div class="check-group" id="termCheckboxes">
                            @foreach($terms as $term)
                                <div class="form-check">
                                    <input class="form-check-input term-cb" type="checkbox"
                                           value="{{ $term->id }}" id="trm_{{ $term->id }}">
                                    <label class="form-check-label" for="trm_{{ $term->id }}">
                                        {{ $term->term }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-4 d-none" id="termSingleGroup">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select id="termid_id_single" class="form-select">
                            <option value="">— Select Term —</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3" id="sessionRadios">
                            @foreach($schoolsessions as $session)
                                <div class="form-check form-check-outline form-check-primary">
                                    <input class="form-check-input session-rb" type="radio"
                                           name="session_id" value="{{ $session->id }}"
                                           id="ses_{{ $session->id }}">
                                    <label class="form-check-label" for="ses_{{ $session->id }}">
                                        {{ $session->session }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="alert alert-danger d-none" id="formErrors"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="ri-save-line me-1"></i>Save Assignment
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
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemTitle"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="ri-delete-bin-line me-1"></i>Delete
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

    const ROUTES = {
        index:       '{{ route("schoolbilltermsession.index") }}',
        data:        '{{ route("schoolbilltermsession.data") }}',
        stats:       '{{ route("schoolbilltermsession.stats") }}',
        store:       '{{ route("schoolbilltermsession.store") }}',
        update:      function(id) { return '{{ url("schoolbilltermsession") }}/' + id; },
        destroy:     function(id) { return '{{ url("schoolbilltermsession") }}/' + id; },
        bulkDestroy: '{{ route("schoolbilltermsession.bulk-destroy") }}',
    };

    const CSRF = $('meta[name="csrf-token"]').attr('content');
    let table, deleteId = null;

    // =========================================================================
    // LOADING + TOAST HELPERS
    // =========================================================================
    const PageLoader = {
        show(lbl) { $('#ts-loader-label').text(lbl || 'Processing…'); $('#ts-page-loader').addClass('active'); },
        hide() { setTimeout(() => $('#ts-page-loader').removeClass('active'), 300); }
    };

    function toast(type, title, msg) {
        var icons = { success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
        var id = 'ts-toast-' + Date.now();
        var $el = $('<div class="ts-toast ts-toast-' + type + '" id="' + id + '">'
            + '<span class="ts-toast-icon"><i class="' + icons[type] + '"></i></span>'
            + '<div class="ts-toast-body"><div class="ts-toast-title">' + title + '</div>'
            + (msg ? '<div class="ts-toast-msg">' + msg + '</div>' : '') + '</div>'
            + '<button class="ts-toast-close" onclick="$(\'#' + id + '\').remove()">×</button></div>');
        $('#ts-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        setTimeout(() => { $el.removeClass('show'); setTimeout(() => $el.remove(), 300); }, 4000);
    }

    function btnLoad($b, lbl) { $b.data('orig', $b.html()).prop('disabled', true).addClass('btn-loading'); if (lbl) $b.html('<span class="btn-text">' + lbl + '</span>'); }
    function btnReset($b) { var o = $b.data('orig'); if (o) $b.html(o); $b.prop('disabled', false).removeClass('btn-loading'); }

    // =========================================================================
    // DATATABLE
    // =========================================================================
    table = $('#assignmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  ROUTES.data,
            type: 'GET',
            cache: false,
            error: function (xhr) {
                console.error('DataTables AJAX error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load assignments. Please refresh.');
            }
        },
        columns: [
            {
                data: 'id', orderable: false, searchable: false,
                render: function(data) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' + data + '">';
                }
            },
            { data: 'DT_RowIndex',           orderable: false, searchable: false },
            { data: 'formatted_bill',         orderable: false },
            { data: 'formatted_class',        orderable: false },
            { data: 'formatted_term_session', orderable: false },
            { data: 'createdBy',              orderable: false },
            { data: 'formatted_date',         orderable: false },
            { data: 'action',                 orderable: false, searchable: false },
        ],
        language: {
            processing:        '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading...',
            search:            '',
            searchPlaceholder: 'Search assignments...',
            lengthMenu:        'Show _MENU_ entries',
            info:              'Showing _START_&ndash;_END_ of _TOTAL_ assignments',
            infoEmpty:         'No assignments found',
            zeroRecords:       'No matching assignments',
            emptyTable:        'No assignments created yet',
        },
        order:      [[1, 'desc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function () {
            bindCheckboxes();
            const info = this.api().page.info();
            $('#totalBadge').text(info.recordsTotal);
        },
    });

    // =========================================================================
    // STATS
    // =========================================================================
    function loadStats() {
        $.get(ROUTES.stats, function (data) {
            if (data.stats) {
                $('#statTotal'   ).text(data.stats.total);
                $('#statBills'   ).text(data.stats.unique_bills);
                $('#statSessions').text(data.stats.unique_sessions);
                $('#statAmount'  ).text(
                    '\u20A6' + Number(data.stats.total_amount)
                        .toLocaleString('en-NG', { minimumFractionDigits: 0 })
                );
            }
        });
    }
    loadStats();

    // =========================================================================
    // CHECKBOXES / BULK BAR
    // =========================================================================
    function bindCheckboxes() {
        $('.row-checkbox').off('change').on('change', updateBulkBar);
    }

    $('#selectAll').on('change', function () {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkBar();
    });

    function updateBulkBar() {
        const count = $('.row-checkbox:checked').length;
        $('#bulkBar').toggleClass('show', count > 0);
        $('#bulkCount').text(count);
        $('#bulkDeleteBtn').toggleClass('d-none', count === 0);
        if (count === 0) $('#selectAll').prop('checked', false);
    }

    $('#selectAllClasses').on('change', function () {
        $('.class-cb').prop('checked', this.checked);
    });

    // =========================================================================
    // MODE SWITCHING
    // =========================================================================
    function setCreateMode() {
        $('#editNote'          ).addClass('d-none');
        $('#classCheckboxGroup').removeClass('d-none');
        $('#classSingleGroup'  ).addClass('d-none');
        $('#termCheckboxGroup' ).removeClass('d-none');
        $('#termSingleGroup'   ).addClass('d-none');
    }

    function setEditMode() {
        $('#editNote'          ).removeClass('d-none');
        $('#classCheckboxGroup').addClass('d-none');
        $('#classSingleGroup'  ).removeClass('d-none');
        $('#termCheckboxGroup' ).addClass('d-none');
        $('#termSingleGroup'   ).removeClass('d-none');
    }

    function resetForm() {
        $('#assignmentId').val('');
        $('#bill_id').val('');
        $('.class-cb').prop('checked', false);
        $('#selectAllClasses').prop('checked', false);
        $('.term-cb').prop('checked', false);
        $('.session-rb').prop('checked', false);
        $('#class_id_single').val('');
        $('#termid_id_single').val('');
        $('#formErrors').addClass('d-none').html('');
    }

    $('#createAssignmentBtn').on('click', function () {
        resetForm();
        setCreateMode();
        $('#modalTitle').html('<i class="ri-links-line me-2"></i>Create Assignment');
        $('#saveBtn').html('<i class="ri-save-line me-1"></i>Save Assignment');
        $('#tsModal').modal('show');
    });

    $(document).on('click', '.edit-assignment', function () {
        resetForm();
        setEditMode();

        const id         = $(this).data('id');
        const bill_id    = $(this).data('bill_id');
        const class_id   = $(this).data('class_id');
        const termid_id  = $(this).data('termid_id');
        const session_id = $(this).data('session_id');

        $('#assignmentId').val(id);
        $('#bill_id').val(bill_id);
        $('#class_id_single').val(class_id);
        $('#termid_id_single').val(termid_id);
        $('#ses_' + session_id).prop('checked', true);

        $('#modalTitle').html('<i class="ri-edit-line me-2"></i>Edit Assignment');
        $('#saveBtn').html('<i class="ri-save-line me-1"></i>Update Assignment');
        $('#tsModal').modal('show');
    });

    // =========================================================================
    // SAVE (CREATE + UPDATE)
    // =========================================================================
    $('#tsForm').on('submit', function (e) {
        e.preventDefault();

        const id     = $('#assignmentId').val();
        const isEdit = !!id;
        const url    = isEdit ? ROUTES.update(id) : ROUTES.store;

        const payload = {
            bill_id:    $('#bill_id').val(),
            session_id: $('input[name="session_id"]:checked').val() || '',
            _token:     CSRF,
        };

        if (isEdit) {
            payload._method   = 'PUT';
            payload.class_id  = $('#class_id_single').val();
            payload.termid_id = $('#termid_id_single').val();
        } else {
            payload['class_id[]']  = $('.class-cb:checked').map(function(i, el) { return el.value; }).get();
            payload['termid_id[]'] = $('.term-cb:checked').map(function(i, el) { return el.value; }).get();
        }

        btnLoad($('#saveBtn'), 'Saving…');
        $('#formErrors').addClass('d-none').html('');

        $.ajax({
            url:         url,
            type:        'POST',
            data:        payload,
            traditional: true,
            success: function(res) {
                btnReset($('#saveBtn'));
                if (res.success) {
                    $('#tsModal').modal('hide');
                    table.ajax.reload();
                    loadStats();
                    toast('success', 'Saved!', res.message);
                } else {
                    showErrors(res.message, res.errors);
                }
            },
            error: function(xhr) {
                btnReset($('#saveBtn'));
                const json = xhr.responseJSON;
                const msg = (json && json.message)
                         || (json && json.errors && Object.values(json.errors).flat()[0])
                         || 'Server error (' + xhr.status + '): ' + (xhr.statusText || 'Unknown');

                // Duplicate / validation errors — close modal, refresh, warn
                if (xhr.status === 422) {
                    $('#tsModal').modal('hide');
                    table.ajax.reload(null, false);
                    loadStats();
                    toast('warning', 'Duplicate', msg);
                } else {
                    // Other server errors — keep modal open, show details
                    showErrors(msg, json ? json.errors : null);
                    toast('error', 'Failed', msg);
                }
            },
        });
    });

    function showErrors(message, errors) {
        let html = '<ul class="mb-0 ps-3">';
        if (errors) {
            $.each(errors, function (k, v) {
                html += '<li>' + (Array.isArray(v) ? v[0] : v) + '</li>';
            });
        } else {
            html += '<li>' + (message || 'Something went wrong.') + '</li>';
        }
        html += '</ul>';
        $('#formErrors').removeClass('d-none').html(html);
    }

    // =========================================================================
    // SINGLE DELETE
    // =========================================================================
    $(document).on('click', '.delete-assignment', function () {
        deleteId = $(this).data('id');
        $('#deleteItemTitle').text('"' + $(this).data('title') + '"');
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function () {
        if (!deleteId) return;
        const $b = $(this);
        btnLoad($b, 'Deleting…');

        $.ajax({
            url:  ROUTES.destroy(deleteId),
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            success: function(res) {
                $('#deleteModal').modal('hide');
                if (res.success) {
                    toast('success', 'Deleted!', res.message);
                    table.ajax.reload(null, false);
                    loadStats();
                } else {
                    toast('error', 'Cannot Delete', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                const j = xhr.responseJSON;
                toast('error', 'Error', (j && j.message) || 'Failed to delete.');
            },
            complete: function() {
                btnReset($b);
                deleteId = null;
            },
        });
    });

    // =========================================================================
    // BULK DELETE — SweetAlert2 confirm + toast result + page loader
    // =========================================================================
    function doBulkDelete() {
        const ids = $('.row-checkbox:checked').map(function () { return this.value; }).get();

        if (!ids.length) {
            toast('warning', 'No Selection', 'Please select at least one assignment.');
            return;
        }

        Swal.fire({
            title: 'Delete ' + ids.length + ' assignment(s)?',
            html: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return new Promise(function (resolve, reject) {
                    PageLoader.show('Deleting ' + ids.length + ' assignment(s)…');

                    $.ajax({
                        url: '{{ route("schoolbilltermsession.bulk-destroy") }}',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ ids: ids, _token: CSRF }),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            PageLoader.hide();
                            if (res.success) {
                                resolve(res);
                            } else {
                                reject(res.message || 'Failed.');
                            }
                        },
                        error: function (xhr) {
                            PageLoader.hide();
                            const j = xhr.responseJSON;
                            reject((j && j.message) || 'Failed to delete assignments.');
                        },
                    });
                });
            }
        }).then(function (result) {
            if (result.isConfirmed && result.value) {
                toast('success', 'Deleted!', result.value.message || 'Assignments deleted.');
                table.ajax.reload(null, false);
                loadStats();
                $('#selectAll').prop('checked', false);
                updateBulkBar();
            }
        }).catch(function (err) {
            toast('error', 'Failed', typeof err === 'string' ? err : 'Could not delete.');
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);
});
</script>
@endsection