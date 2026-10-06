{{-- resources/views/schoolclass/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sc-primary:#1e3a5f; --sc-accent:#2563eb; --sc-success:#16a34a;
    --sc-warning:#d97706; --sc-danger:#dc2626; --sc-muted:#6b7280;
    --sc-border:#e2e8f0; --sc-radius:12px; --sc-shadow:0 2px 8px rgba(0,0,0,.08);
}
.sc-hero { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#4f46e5 100%); border-radius:var(--sc-radius); padding:28px 32px; margin-bottom:24px; position:relative; overflow:hidden; }
.sc-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%; }
.sc-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.sc-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

.stat-card { background:#fff; border:1px solid var(--sc-border); border-radius:var(--sc-radius); padding:18px 20px; transition:transform .15s, box-shadow .15s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--sc-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--sc-primary); }
.stat-card .stat-label { font-size:12px; color:var(--sc-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

.sc-table th { background:var(--sc-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.sc-table td { padding:11px 16px; vertical-align:middle; border-bottom:1px solid var(--sc-border); font-size:13px; }
.sc-table tr:hover td { background:#f0f9ff; }

.sc-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.sc-badge-arm { background:#dbeafe; color:#2563eb; }
.sc-badge-category { background:#ccfbf1; color:#0f766e; }

.dataTables_wrapper .dataTables_filter input { border:1.5px solid var(--sc-border); border-radius:8px; padding:7px 14px; margin-left:8px; font-size:13px; }
.dataTables_wrapper .dataTables_filter input:focus { border-color:var(--sc-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.dataTables_wrapper .dataTables_length select { border:1.5px solid var(--sc-border); border-radius:8px; padding:6px 10px; margin:0 6px; font-size:13px; }
.dataTables_wrapper .paginate_button.current, .dataTables_wrapper .paginate_button.current:hover { background:var(--sc-accent) !important; border-color:var(--sc-accent) !important; color:#fff !important; }

.sc-modal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); padding:22px 28px; position:relative; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); }

.form-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-control, .form-select { border:1.5px solid var(--sc-border); border-radius:8px; font-size:13px; padding:9px 14px; }
.form-control:focus, .form-select:focus { border-color:var(--sc-accent); box-shadow:0 0 0 3px rgba(37,99,235,.1); }

.checkbox-scroll { max-height:220px; overflow-y:auto; border:1.5px solid var(--sc-border); border-radius:8px; padding:10px 14px; background:#fafbfc; }
.checkbox-scroll .form-check { padding:5px 0; border-bottom:1px solid #f0f0f0; }
.checkbox-scroll .form-check:last-child { border-bottom:none; }
.inline-check-group { display:flex; flex-wrap:wrap; gap:12px; padding:10px 14px; border:1.5px solid var(--sc-border); border-radius:8px; background:#fafbfc; }
.inline-check-group .form-check { margin:0; }
.inline-check-group .form-check-label { font-size:13px; cursor:pointer; }

.select-all-bar { background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:8px; padding:7px 12px; margin-bottom:6px; display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--sc-accent); cursor:pointer; }

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
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<div id="sc-page-loader"><div class="sc-loader-card"><div class="sc-loader-spinner"></div><div class="sc-loader-label" id="sc-loader-label">Processing…</div></div></div>
<div id="sc-toast-stack"></div>

<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="sc-hero">
        <h1><i class="ri-building-line me-2"></i>School Class Management</h1>
        <p>Manage school classes with their respective arms and categories.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card"><div class="stat-icon"><i class="ri-group-line"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Classes</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-icon"><i class="ri-shield-line"></i></div><div class="stat-value text-primary" id="statArms">—</div><div class="stat-label">Total Arms</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-icon"><i class="ri-bookmark-line"></i></div><div class="stat-value text-success" id="statCategories">—</div><div class="stat-label">Total Categories</div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--sc-primary)">
                    <i class="ri-list-check me-2"></i>School Classes List
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
                    @can('Create school-class')
                    <button class="btn btn-primary" id="createClassBtn"><i class="ri-add-line me-1"></i>Create Class</button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> class(es) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2"><i class="ri-delete-bin-line me-1"></i>Delete Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table sc-table w-100 mb-0" id="schoolClassesTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>#</th>
                            <th>School Class</th>
                            <th>Arm</th>
                            <th>Category</th>
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

{{-- CREATE MODAL --}}
<div class="modal fade sc-modal" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-line me-2"></i>Create New School Class</h5>
            </div>
            <form id="createForm" autocomplete="off">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">School Class <span class="text-danger">*</span></label>
                        <input type="text" name="schoolclass" id="create-schoolclass" class="form-control" placeholder="e.g., JSS 1, SSS 1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Arm(s) <span class="text-danger">*</span></label>
                        <div class="select-all-bar">
                            <input type="checkbox" class="form-check-input" id="create-select-all-arms-cb">
                            <label for="create-select-all-arms-cb" class="mb-0">Select All Arms</label>
                        </div>
                        <div class="checkbox-scroll" id="create-arm-list">
                            @foreach ($arms as $arm)
                                <div class="form-check">
                                    <input class="form-check-input create-arm-cb" type="checkbox"
                                           value="{{ $arm->id }}" id="create-arm-{{ $arm->id }}">
                                    <label class="form-check-label" for="create-arm-{{ $arm->id }}">
                                        {{ $arm->arm }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted mt-1 d-block"><span id="create-arm-count">0</span> arm(s) selected</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Category <span class="text-danger">*</span></label>
                        <div class="inline-check-group" id="create-category-radios">
                            @foreach ($classcategories as $category)
                                <div class="form-check">
                                    <input class="form-check-input create-category-rb" type="radio"
                                           name="create_classcategoryid"
                                           id="create-category-{{ $category->id }}"
                                           value="{{ $category->id }}">
                                    <label class="form-check-label" for="create-category-{{ $category->id }}">
                                        {{ $category->category }}
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
                        <i class="ri-save-line me-1"></i><span class="btn-text">Create Class</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade sc-modal" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-line me-2"></i>Edit School Class</h5>
            </div>
            <form id="editForm" autocomplete="off">
                @csrf
                <input type="hidden" id="edit-class-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">School Class <span class="text-danger">*</span></label>
                        <input type="text" name="schoolclass" id="edit-schoolclass" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Arm <span class="text-danger">*</span></label>
                        <div class="inline-check-group" id="edit-arm-radios">
                            @foreach ($arms as $arm)
                                <div class="form-check">
                                    <input class="form-check-input edit-arm-rb" type="radio"
                                           name="edit_arm_id" id="edit-arm-{{ $arm->id }}"
                                           value="{{ $arm->id }}">
                                    <label class="form-check-label" for="edit-arm-{{ $arm->id }}">
                                        {{ $arm->arm }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Category <span class="text-danger">*</span></label>
                        <div class="inline-check-group" id="edit-category-radios">
                            @foreach ($classcategories as $category)
                                <div class="form-check">
                                    <input class="form-check-input edit-category-rb" type="radio"
                                           name="edit_classcategoryid"
                                           id="edit-category-{{ $category->id }}"
                                           value="{{ $category->id }}">
                                    <label class="form-check-label" for="edit-category-{{ $category->id }}">
                                        {{ $category->category }}
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
                        <i class="ri-save-line me-1"></i><span class="btn-text">Update Class</span>
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

    const PageLoader = {
        show(lbl) { $('#sc-loader-label').text(lbl || 'Processing…'); $('#sc-page-loader').addClass('active'); },
        hide() { setTimeout(() => $('#sc-page-loader').removeClass('active'), 300); }
    };

    function toast(type, title, msg) {
        var icons = { success: 'ri-checkbox-circle-fill', error: 'ri-close-circle-fill', warning: 'ri-alert-fill', info: 'ri-information-fill' };
        var id = 'sc-toast-' + Date.now();
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

    var table = $('#schoolClassesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("schoolclass.data") }}',
            type: 'GET',
            error: function(xhr) {
                console.error('DataTables error:', xhr.status, xhr.responseText);
                toast('error', 'Load Error', 'Failed to load classes. Please refresh.');
            }
        },
        columns: [
            { data: 'id', orderable: false, searchable: false,
              render: d => '<input type="checkbox" class="form-check-input row-checkbox" value="' + d + '">' },
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_info', orderable: false },
            { data: 'arm_info', orderable: false },
            { data: 'categories_info', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-5'i><'col-sm-7 text-end'p>>",
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading…',
            search: '', searchPlaceholder: 'Search classes…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ classes',
            infoEmpty: 'No classes found', zeroRecords: 'No matching classes',
            emptyTable: 'No school classes created yet'
        },
        order: [[1, 'asc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            bindCB();
            $('#totalBadge').text(this.api().page.info().recordsTotal);
        }
    });

    function loadStats() {
        $.get('{{ route("schoolclass.stats") }}', function(d) {
            if (d.stats) {
                $('#statTotal').text(d.stats.total);
                $('#statArms').text(d.stats.total_arms);
                $('#statCategories').text(d.stats.total_categories);
            }
        }).fail(function() {
            $('#statTotal, #statArms, #statCategories').text('—');
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

    $('#create-select-all-arms-cb').on('change', function() {
        $('.create-arm-cb').prop('checked', this.checked);
        updateCCounts();
    });
    $('.create-arm-cb').on('change', updateCCounts);
    function updateCCounts() {
        $('#create-arm-count').text($('.create-arm-cb:checked').length);
        updateCBtn();
    }
    function updateCBtn() {
        var ok = $('#create-schoolclass').val().trim() !== ''
              && $('.create-arm-cb:checked').length > 0
              && $('.create-category-rb:checked').length > 0;
        $('#create-save-btn').prop('disabled', !ok);
    }
    $('#create-schoolclass').on('input', updateCBtn);
    $('#create-category-radios').on('change', 'input[type=radio]', updateCBtn);

    $('#createClassBtn').on('click', function() {
        $('#create-schoolclass').val('');
        $('.create-arm-cb, #create-select-all-arms-cb').prop('checked', false);
        $('.create-category-rb').prop('checked', false);
        $('#create-arm-count').text(0);
        $('#create-save-btn').prop('disabled', true);
        $('#create-error-msg').addClass('d-none').html('');
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });

    $(document).on('click', '.edit-class-btn', function() {
        var $b = $(this);
        $('#edit-class-id').val($b.data('id'));
        $('#edit-schoolclass').val($b.data('schoolclass'));
        $('.edit-arm-rb').prop('checked', false);
        var armId = $b.data('arm-id');
        if (armId) $('#edit-arm-' + armId).prop('checked', true);
        $('.edit-category-rb').prop('checked', false);
        var catId = $b.data('category-id');
        if (catId) $('#edit-category-' + catId).prop('checked', true);
        $('#edit-error-msg').addClass('d-none').html('');
        btnReset($('#edit-update-btn'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        var arms = $('.create-arm-cb:checked').map(function() { return this.value; }).get();
        var catId = $('.create-category-rb:checked').val();
        if (!arms.length) { showErr('#create-error-msg', 'Select at least one arm.'); return; }
        if (!catId) { showErr('#create-error-msg', 'Select a category.'); return; }

        btnLoad($('#create-save-btn'), 'Saving…');
        $('#create-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("schoolclass.store") }}',
            type: 'POST',
            data: {
                schoolclass: $('#create-schoolclass').val().trim(),
                arm_id: arms,
                classcategoryid: catId,
                _token: CSRF
            },
            traditional: true,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#createModal').modal('hide');
                    toast('success', 'Created!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($('#create-save-btn')); updateCBtn();
                    showErr('#create-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#create-save-btn')); updateCBtn();
                var j = xhr.responseJSON;
                var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr('#create-error-msg', m);
            }
        });
    });

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#edit-class-id').val();
        var armId = $('.edit-arm-rb:checked').val();
        var catId = $('.edit-category-rb:checked').val();
        if (!armId) { showErr('#edit-error-msg', 'Select an arm.'); return; }
        if (!catId) { showErr('#edit-error-msg', 'Select a category.'); return; }

        btnLoad($('#edit-update-btn'), 'Updating…');
        $('#edit-error-msg').addClass('d-none').html('');

        $.ajax({
            url: '{{ url("schoolclass") }}/' + id,
            type: 'POST',
            data: {
                _method: 'PUT',
                schoolclass: $('#edit-schoolclass').val().trim(),
                arm_id: armId,
                classcategoryid: catId,
                _token: CSRF
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#editModal').modal('hide');
                    toast('success', 'Updated!', res.message);
                    table.ajax.reload(); loadStats();
                } else {
                    btnReset($('#edit-update-btn'));
                    showErr('#edit-error-msg', res.message || 'Failed.');
                }
            },
            error: function(xhr) {
                btnReset($('#edit-update-btn'));
                var j = xhr.responseJSON;
                var m = (j && j.message) || (j && j.errors && Object.values(j.errors).flat().join(', ')) || 'An error occurred.';
                showErr('#edit-error-msg', m);
            }
        });
    });

    $(document).on('click', '.delete-class-btn', function() {
        deleteId = $(this).data('id');
        $('#delete-item-title').text($(this).data('name') || 'this class');
        btnReset($('#confirm-delete-btn'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete-btn').on('click', function() {
        if (!deleteId) return;
        var $b = $(this); btnLoad($b, 'Deleting…');
        $.ajax({
            url: '{{ url("schoolclass") }}/' + deleteId,
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                $('#deleteModal').modal('hide');
                if (res.success) { toast('success', 'Deleted!', res.message); table.ajax.reload(); loadStats(); }
                else { toast('error', 'Cannot Delete', res.message); }
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                toast('error', 'Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete.');
            },
            complete: function() { btnReset($b); deleteId = null; }
        });
    });

    function doBulk() {
        var ids = $('.row-checkbox:checked').map(function() { return this.value; }).get();
        if (!ids.length) { toast('warning', 'No Selection', 'Select at least one class.'); return; }
        Swal.fire({
            title: 'Delete ' + ids.length + ' class(es)?',
            html: 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel', reverseButtons: true, showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    PageLoader.show('Deleting classes…');
                    $.ajax({
                        url: '{{ route("schoolclass.bulk-destroy") }}',
                        type: 'POST',
                        data: { ids: ids, _token: CSRF },
                        traditional: true,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(res) { PageLoader.hide(); if (res.success) resolve(res); else reject(res.message); },
                        error: function(xhr) { PageLoader.hide(); reject((xhr.responseJSON && xhr.responseJSON.message) || 'Error.'); }
                    });
                });
            }
        }).then(function(r) {
            if (r.isConfirmed && r.value) {
                toast('success', 'Deleted!', r.value.message);
                table.ajax.reload(); loadStats();
                $('#selectAll').prop('checked', false); updBulk();
            }
        }).catch(function(err) { toast('error', 'Failed', typeof err === 'string' ? err : 'Could not delete.'); });
    }
    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulk);

    bindCB();
});
</script>
@endsection
