@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style>
:root {
    --bill-primary: #1e3a5f;
    --bill-accent:  #2563eb;
    --bill-success: #16a34a;
    --bill-warning: #d97706;
    --bill-danger:  #dc2626;
    --bill-muted:   #6b7280;
    --bill-border:  #e2e8f0;
    --bill-bg:      #f8fafc;
    --bill-radius:  12px;
    --bill-shadow:  0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ──────────────────────────────────────────────── */
.bill-hero {
    background: linear-gradient(135deg, var(--bill-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--bill-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.bill-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.bill-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.bill-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.bill-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ────────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--bill-border);
    border-radius:var(--bill-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--bill-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--bill-primary); }
.stat-card .stat-label { font-size:12px; color:var(--bill-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Table ─────────────────────────────────────────────── */
.bill-table th {
    background:var(--bill-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.bill-table td {
    padding:12px 16px; vertical-align:middle;
    border-bottom:1px solid var(--bill-border); font-size:13px;
}
.bill-table tr:hover td { background:#eff6ff; }

/* ── Badges ────────────────────────────────────────────── */
.bill-badge {
    display:inline-flex; align-items:center;
    padding:4px 10px; border-radius:20px;
    font-size:11px; font-weight:600;
}
.bill-badge-old     { background:#dbeafe; color:#2563eb; }
.bill-badge-new     { background:#dcfce7; color:#16a34a; }
.bill-badge-unknown { background:#f3f4f6; color:#6b7280; }

/* ── DataTables overrides ──────────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--bill-border); border-radius:8px;
    padding:7px 14px; margin-left:8px; font-size:13px;
    transition:border .15s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--bill-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--bill-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--bill-muted); }
.dataTables_wrapper .paginate_button {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--bill-accent) !important;
    border-color:var(--bill-accent) !important; color:#fff !important;
}

/* ── Modal ─────────────────────────────────────────────── */
#billModal .modal-content {
    border:none; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background:linear-gradient(135deg, var(--bill-primary) 0%, #2563eb 100%);
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
    border:1.5px solid var(--bill-border); border-radius:8px;
    font-size:13px; padding:9px 14px; transition:border .15s;
}
.form-control:focus, .form-select:focus {
    border-color:var(--bill-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Bulk bar ──────────────────────────────────────────── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="bill-hero">
        <h1><i class="ri-file-list-3-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Create, manage and assign school bills across student categories.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-file-list-3-line"></i></div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Total Bills</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-line"></i></div>
                <div class="stat-value text-primary" id="statOld">—</div>
                <div class="stat-label">Old Student Bills</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-add-line"></i></div>
                <div class="stat-value text-success" id="statNew">—</div>
                <div class="stat-label">New Student Bills</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-money-naira-circle-line"></i></div>
                <div class="stat-value text-warning" id="statAmount">—</div>
                <div class="stat-label">Total Bill Value</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold" style="color:var(--bill-primary)">
                    <i class="ri-list-check me-2"></i>All School Bills
                    <span class="badge bg-primary ms-2" id="totalBadge">0</span>
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                    @can('Create school-bills')
                    <button class="btn btn-primary" id="createBillBtn">
                        <i class="ri-add-line me-1"></i>Create School Bill
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">

            {{-- Bulk bar --}}
            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> bill(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn2">
                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table bill-table w-100 mb-0" id="billsTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>Title</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Student Type</th>
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

{{-- CREATE / EDIT MODAL --}}
<div class="modal fade" id="billModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5 id="modalTitle"><i class="ri-file-add-line me-2"></i>Create School Bill</h5>
            </div>
            <form id="billForm">
                @csrf
                <input type="hidden" id="billId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Bill Title <span class="text-danger">*</span></label>
                        <input type="text" id="title" class="form-control"
                               placeholder="e.g., First Term Tuition 2025" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bill Amount (₦) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"
                                  style="border-radius:8px 0 0 8px;border:1.5px solid var(--bill-border);
                                         border-right:none;background:#f8fafc;font-weight:600;color:var(--bill-muted)">₦</span>
                            <input type="number" id="billAmount" class="form-control"
                                   style="border-radius:0 8px 8px 0"
                                   step="0.01" min="1" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea id="description" class="form-control" rows="3"
                                  placeholder="Optional remark..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Student Type <span class="text-danger">*</span></label>
                        <select id="statusId" class="form-select" required>
                            <option value="">— Select Type —</option>
                            <option value="1">Old Student Bill</option>
                            <option value="2">New Student Bill</option>
                        </select>
                    </div>
                    <div class="alert alert-danger d-none" id="formErrors"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="ri-save-line me-1"></i>Save Bill
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

    // ── Use route helpers for all URLs ─────────────────────────────────
    const ROUTES = {
        index:       '{{ route("schoolbill.index") }}',
        store:       '{{ route("schoolbill.store") }}',
        update:      function(id) { return '{{ url("schoolbill") }}/' + id; },
        destroy:     function(id) { return '{{ url("schoolbill") }}/' + id; },
        bulkDestroy: '{{ route("schoolbill.bulk-destroy") }}',
        stats:       '{{ route("schoolbill.index") }}?stats=1',
    };

    const CSRF = $('meta[name="csrf-token"]').attr('content');
    let table, deleteId = null;

    // ── DataTable ──────────────────────────────────────────────────────
    table = $('#billsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ROUTES.index,
            type: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            error: function (xhr, status, error) {
                console.error('DataTables error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });

                let errorMsg = 'Failed to load bills. ';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += xhr.responseJSON.message;
                } else if (xhr.status === 500) {
                    errorMsg += 'Server error. Please check the console for details.';
                } else {
                    errorMsg += 'Please refresh and try again.';
                }

                Swal.fire('Error', errorMsg, 'error');
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'index', orderable: false, searchable: false },
            { data: 'title' },
            { data: 'formatted_amount', orderable: false },
            { data: 'description', orderable: false },
            { data: 'status_name', orderable: false },
            { data: 'formatted_date', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading...',
            search: '',
            searchPlaceholder: 'Search bills...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_–_END_ of _TOTAL_ bills',
            infoEmpty: 'No bills found',
            zeroRecords: 'No matching bills',
            emptyTable: 'No school bills created yet',
        },
        order: [[1, 'desc']],
        pageLength: 15,
        responsive: true,
        drawCallback: function() {
            bindCheckboxes();
            const info = this.api().page.info();
            $('#totalBadge').text(info.recordsTotal);
        },
    });

    // ── Load stats ─────────────────────────────────────────────────────
    function loadStats() {
        $.ajax({
            url: ROUTES.stats,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.stats) {
                    $('#statTotal').text(data.stats.total);
                    $('#statOld').text(data.stats.old);
                    $('#statNew').text(data.stats.new);
                    $('#statAmount').text(
                        '₦' + Number(data.stats.total_amount).toLocaleString('en-NG', { minimumFractionDigits: 0 })
                    );
                }
            },
            error: function(xhr) {
                console.error('Stats load error:', xhr);
            }
        });
    }
    loadStats();

    // ── Checkboxes ─────────────────────────────────────────────────────
    function bindCheckboxes() {
        $('.row-checkbox').off('change').on('change', updateBulkBar);
    }

    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkBar();
    });

    function updateBulkBar() {
        const count = $('.row-checkbox:checked').length;
        if (count > 0) {
            $('#bulkBar').addClass('show');
            $('#bulkDeleteBtn').removeClass('d-none');
        } else {
            $('#bulkBar').removeClass('show');
            $('#bulkDeleteBtn').addClass('d-none');
        }
        $('#bulkCount').text(count);
        if (count === 0) $('#selectAll').prop('checked', false);
    }

    // ── Create ─────────────────────────────────────────────────────────
    $('#createBillBtn').on('click', function() {
        $('#billId').val('');
        $('#title').val('');
        $('#billAmount').val('');
        $('#description').val('');
        $('#statusId').val('');
        $('#modalTitle').html('<i class="ri-file-add-line me-2"></i>Create School Bill');
        $('#saveBtn').html('<i class="ri-save-line me-1"></i>Save Bill');
        $('#formErrors').addClass('d-none').html('');
        $('#billModal').modal('show');
    });

    // ── Edit ───────────────────────────────────────────────────────────
    $(document).on('click', '.edit-bill', function() {
        $('#billId').val($(this).data('id'));
        $('#title').val($(this).data('title'));
        $('#billAmount').val($(this).data('amount'));
        $('#description').val($(this).data('description') || '');
        $('#statusId').val($(this).data('status'));
        $('#modalTitle').html('<i class="ri-edit-line me-2"></i>Edit School Bill');
        $('#saveBtn').html('<i class="ri-save-line me-1"></i>Update Bill');
        $('#formErrors').addClass('d-none').html('');
        $('#billModal').modal('show');
    });

    // ── Save (create + update) ─────────────────────────────────────────
    $('#billForm').on('submit', function(e) {
        e.preventDefault();

        const id = $('#billId').val();
        const isEdit = !!id;

        const url = isEdit ? ROUTES.update(id) : ROUTES.store;
        const method = 'POST';

        const payload = {
            title: $('#title').val(),
            bill_amount: $('#billAmount').val(),
            description: $('#description').val(),
            statusId: $('#statusId').val(),
            _token: CSRF,
        };
        if (isEdit) payload._method = 'PUT';

        $('#saveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        $('#formErrors').addClass('d-none').html('');

        $.ajax({
            url: url,
            type: method,
            data: payload,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#billModal').modal('hide');
                    table.ajax.reload(null, false);
                    loadStats();
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: res.message,
                        timer: 2500,
                        showConfirmButton: false,
                    });
                } else {
                    showErrors(res.message, res.errors);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    showErrors(null, xhr.responseJSON?.errors);
                } else {
                    Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
                }
            },
            complete: function() {
                $('#saveBtn').prop('disabled', false).html('<i class="ri-save-line me-1"></i>Save Bill');
            },
        });
    });

    function showErrors(message, errors) {
        let html = '<ul class="mb-0 ps-3">';
        if (errors) {
            $.each(errors, function(k, v) {
                html += `<li>${Array.isArray(v) ? v[0] : v}</li>`;
            });
        } else {
            html += `<li>${message || 'Something went wrong.'}</li>`;
        }
        html += '</ul>';
        $('#formErrors').removeClass('d-none').html(html);
    }

    // ── Single delete ──────────────────────────────────────────────────
    $(document).on('click', '.delete-bill', function() {
        deleteId = $(this).data('id');
        $('#deleteItemTitle').text('"' + $(this).data('title') + '"');
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!deleteId) return;
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: ROUTES.destroy(deleteId),
            type: 'POST',
            data: { _method: 'DELETE', _token: CSRF },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#deleteModal').modal('hide');
                    table.ajax.reload(null, false);
                    loadStats();
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                } else {
                    Swal.fire('Error!', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to delete bill.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ri-delete-bin-line me-1"></i>Delete');
                deleteId = null;
            },
        });
    });

    // ── Bulk delete ────────────────────────────────────────────────────
    function doBulkDelete() {
        const ids = $('.row-checkbox:checked').map(function(i, el) {
            return $(el).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: `Delete ${ids.length} bill(s)?`,
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, delete',
        }).then(function(result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: ROUTES.bulkDestroy,
                type: 'POST',
                data: { ids: ids, _token: CSRF },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                traditional: true,
                success: function(res) {
                    if (res.success) {
                        table.ajax.reload(null, false);
                        loadStats();
                        $('#selectAll').prop('checked', false);
                        updateBulkBar();
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete bills.', 'error');
                },
            });
        });
    }

    $('#bulkDeleteBtn, #bulkDeleteBtn2').on('click', doBulkDelete);
});
</script>
@endsection
