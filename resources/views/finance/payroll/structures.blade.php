{{-- resources/views/finance/payroll/structures.blade.php --}}
@extends('layouts.master')

@section('content')
<style>.modal-lg { max-width: 800px; }</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$pagetitle" icon="ri-bank-card-line" subtitle="Individual pay packages — basic salary plus allowances — for staff who are not paid from a salary grade.">
        <x-slot:actions>
            <button type="button" class="cb-hero-btn" data-bs-toggle="modal" data-bs-target="#createStructureModal"><i class="ri-add-line"></i>Add structure</button>
            <a href="{{ route('payroll.scales') }}" class="cb-hero-btn"><i class="ri-stack-line"></i>Salary scales</a>
            <a href="{{ route('payroll.items') }}" class="cb-hero-btn"><i class="ri-list-settings-line"></i>Allowances &amp; deductions</a>
        </x-slot:actions>
    </x-cb.hero>

    <div class="cb-banner info"><i class="ri-information-line"></i><div>Staff on a grade and step are paid from <a href="{{ route('payroll.scales') }}">Salary Scales</a>; use a structure here for anyone with an individual package (contract, part-time, management). Each person's pay profile says which one applies.</div></div>

    <div class="row g-3 mb-3">
        @foreach([['totalStructures', 'Structures', 'ri-bank-card-line', 'teal', '0'], ['activeStructures', 'Active', 'ri-checkbox-circle-line', 'green', '0'], ['avgBasicSalary', 'Average basic salary', 'ri-money-dollar-circle-line', 'amber', '₦0'], ['staffCovered', 'Staff covered', 'ri-user-line', 'rose', '0']] as [$id, $label, $icon, $acc, $v])
            <div class="col-md-3 col-6"><div class="cb-stat accent-{{ $acc }}"><div class="stat-accent"></div><div class="stat-ico"><i class="{{ $icon }}"></i></div><div class="stat-value" id="{{ $id }}">{{ $v }}</div><div class="stat-label">{{ $label }}</div></div></div>
        @endforeach
    </div>

    <x-cb.card title="Salary structures" icon="ri-table-line" :flush="true">
            <div class="table-responsive">
                <table class="table align-middle mb-0 w-100" id="structuresTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Staff</th>
                            <th>Staff ID</th>
                            <th class="text-end">Basic salary</th>
                            <th class="text-end">Total earnings</th>
                            <th>Effective</th>
                            <th>Status</th>
                            <th width="120"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading salary structures...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
    </x-cb.card>

</div>
</div>
</div>

{{-- Create Structure Modal --}}
<div class="modal fade" id="createStructureModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="ri-add-circle-line me-2"></i>Create Salary Structure</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createStructureForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Staff Member <span class="text-danger">*</span></label>
                            <select name="staff_id" class="form-select" id="staffSelect" required>
                                <option value="">-- Select Staff --</option>
                                @foreach($staff as $s)
                                    <option value="{{ $s->id }}">{{ $s->user->name ?? 'N/A' }} ({{ $s->employmentid ?? 'No ID' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                            <input type="date" name="effective_from" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective To</label>
                            <input type="date" name="effective_to" class="form-control">
                            <small class="text-muted">Leave empty for ongoing</small>
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3 fw-semibold border-bottom pb-2">Salary Components</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Basic Salary <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="basic_salary" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Housing Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="housing_allowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Transport Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="transport_allowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meal Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="meal_allowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Medical Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="medical_allowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Utility Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="utility_allowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Other Allowances</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="other_allowances" class="form-control" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-danger d-none mt-3" id="structureErrors"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveStructureBtn">
                        <i class="ri-save-line me-1"></i>Save Structure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Structure Modal --}}
<div class="modal fade" id="editStructureModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="ri-edit-line me-2"></i>Edit Salary Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editStructureForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Staff Member</label>
                            <input type="text" id="editStaffName" class="form-control" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective From</label>
                            <input type="date" name="effective_from" id="editEffectiveFrom" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Effective To</label>
                            <input type="date" name="effective_to" id="editEffectiveTo" class="form-control">
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3 fw-semibold border-bottom pb-2">Salary Components</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Basic Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="basic_salary" id="editBasicSalary" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Housing Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="housing_allowance" id="editHousingAllowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Transport Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                                               <input type="number" name="transport_allowance" id="editTransportAllowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meal Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="meal_allowance" id="editMealAllowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Medical Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="medical_allowance" id="editMedicalAllowance" class="form-control" step="0.01>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Utility Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="utility_allowance" id="editUtilityAllowance" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Other Allowances</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="other_allowances" id="editOtherAllowances" class="form-control" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-danger d-none mt-3" id="editStructureErrors"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="updateStructureBtn">
                        <i class="ri-save-line me-1"></i>Update Structure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Structure Modal --}}
<div class="modal fade" id="viewStructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="ri-eye-line me-2"></i>View Salary Structure</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewStructureContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading structure details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Delete Salary Structure</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this salary structure?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
let deleteId = null;
let dataTable;

$(document).ready(function() {
    // Initialize DataTable
    dataTable = $('#structuresTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("payroll.salary-structures") }}',
            type: 'GET',
            dataSrc: 'data',
            beforeSend: function() {
                $('#structuresTable tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary"></div><p>Loading...</p> </td></tr>');
            },
            error: function(xhr) {
                console.error('DataTable Error:', xhr);
                $('#structuresTable tbody').html('<tr><td colspan="8" class="text-center text-danger">Failed to load data. Please refresh the page. </td></tr>');
            }
        },
        columns: [
            { data: 'DT_RowIndex' },
            { data: 'staff_name' },
            { data: 'staff_id_no', defaultContent: 'N/A' },
            { data: 'basic_salary' },
            { data: 'total_earnings' },
            { data: 'effective_period' },
            { data: 'is_active' },
            { data: 'action', orderable: false }
        ],
        drawCallback: function(settings) {
            updateStatistics();
        },
        language: {
            emptyTable: "No salary structures found",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            zeroRecords: "No matching records found"
        }
    });

    // Create Structure Form Submit
    $('#createStructureForm').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $('#saveStructureBtn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        const formData = $(this).serialize();

        $.ajax({
            url: '{{ route("payroll.salary-structures.store") }}',
            type: 'POST',
            data: formData,
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        $('#createStructureModal').modal('hide');
                        $('#createStructureForm')[0].reset();
                        dataTable.ajax.reload();
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var html = '<ul class="mb-0">';
                    $.each(errors, function(k, v) {
                        html += '<li>' + v + '</li>';
                    });
                    html += '</ul>';
                    $('#structureErrors').removeClass('d-none').html(html);
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                } else {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Something went wrong', 'error');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // View Structure
    $(document).on('click', '.view-structure', function() {
        const id = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('viewStructureModal'));

        $.ajax({
            url: `/payroll/salary-structures/${id}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#viewStructureContent').html(`
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Staff Name</div>
                                    <div class="fw-bold">${data.staff?.user?.name || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Staff ID</div>
                                    <div class="fw-bold">${data.staff?.employmentid || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Effective From</div>
                                    <div class="fw-bold">${new Date(data.effective_from).toLocaleDateString()}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Effective To</div>
                                    <div class="fw-bold">${data.effective_to ? new Date(data.effective_to).toLocaleDateString() : 'Ongoing'}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Basic Salary</div>
                                    <div class="fw-bold text-success">₦${parseFloat(data.basic_salary).toLocaleString()}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Total Earnings</div>
                                    <div class="fw-bold text-primary">₦${parseFloat(data.total_earnings).toLocaleString()}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted mb-2">Allowances Breakdown</div>
                                    <table class="table table-sm mb-0">
                                        <tr><td width="50%">Housing Allowance:</td><td>₦${parseFloat(data.housing_allowance || 0).toLocaleString()}</td></tr>
                                        <tr><td>Transport Allowance:</td><td>₦${parseFloat(data.transport_allowance || 0).toLocaleString()}</td></tr>
                                        <tr><td>Meal Allowance:</td><td>₦${parseFloat(data.meal_allowance || 0).toLocaleString()}</td></tr>
                                        <tr><td>Medical Allowance:</td><td>₦${parseFloat(data.medical_allowance || 0).toLocaleString()}</td></tr>
                                        <tr><td>Utility Allowance:</td><td>₦${parseFloat(data.utility_allowance || 0).toLocaleString()}</td></tr>
                                        <tr><td>Other Allowances:</td><td>₦${parseFloat(data.other_allowances || 0).toLocaleString()}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `);
                    modal.show();
                }
            },
            error: function() {
                $('#viewStructureContent').html('<div class="alert alert-danger">Failed to load structure details.</div>');
            }
        });
    });

    // Edit Structure
    $(document).on('click', '.edit-structure', function() {
        const id = $(this).data('id');

        $.ajax({
            url: `/payroll/salary-structures/${id}/edit`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#editId').val(data.id);
                    $('#editStaffName').val(data.staff?.user?.name || 'N/A');
                    $('#editEffectiveFrom').val(data.effective_from);
                    $('#editEffectiveTo').val(data.effective_to || '');
                    $('#editBasicSalary').val(data.basic_salary);
                    $('#editHousingAllowance').val(data.housing_allowance);
                    $('#editTransportAllowance').val(data.transport_allowance);
                    $('#editMealAllowance').val(data.meal_allowance);
                    $('#editMedicalAllowance').val(data.medical_allowance);
                    $('#editUtilityAllowance').val(data.utility_allowance);
                    $('#editOtherAllowances').val(data.other_allowances);

                    new bootstrap.Modal(document.getElementById('editStructureModal')).show();
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', 'Failed to load structure details', 'error');
            }
        });
    });

    // Update Structure Form Submit
    $('#editStructureForm').on('submit', function(e) {
        e.preventDefault();

        const id = $('#editId').val();
        const submitBtn = $('#updateStructureBtn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...');

        const formData = $(this).serialize();

        $.ajax({
            url: `/payroll/salary-structures/${id}`,
            type: 'PUT',
            data: formData,
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Updated!', response.message, 'success').then(() => {
                        $('#editStructureModal').modal('hide');
                        dataTable.ajax.reload();
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var html = '<ul class="mb-0">';
                    $.each(errors, function(k, v) {
                        html += '<li>' + v + '</li>';
                    });
                    html += '</ul>';
                    $('#editStructureErrors').removeClass('d-none').html(html);
                } else {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Something went wrong', 'error');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Delete Structure
    $(document).on('click', '.delete-structure', function() {
        deleteId = $(this).data('id');
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (!deleteId) return;

        $.ajax({
            url: `/payroll/salary-structures/${deleteId}`,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Deleted!', response.message, 'success').then(() => {
                        $('#deleteModal').modal('hide');
                        dataTable.ajax.reload();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to delete', 'error');
            }
        });
    });

    function updateStatistics() {
        const rows = dataTable.rows().data();
        const total = rows.count();
        const active = rows.filter(function(row) {
            return row.is_active.indexOf('Active') !== -1;
        }).count();

        let totalBasic = 0;
        rows.each(function(row) {
            const basic = parseFloat(row.basic_salary.replace(/[₦,]/g, '')) || 0;
            totalBasic += basic;
        });
        const avgBasic = total > 0 ? totalBasic / total : 0;

        const uniqueStaff = new Set();
        rows.each(function(row) {
            uniqueStaff.add(row.id);
        });

        $('#totalStructures').text(total);
        $('#activeStructures').text(active);
        $('#avgBasicSalary').text('₦' + avgBasic.toLocaleString());
        $('#staffCovered').text(uniqueStaff.size);
    }
});
</script>
@endsection
