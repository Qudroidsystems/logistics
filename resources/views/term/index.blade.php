@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
:root {
    --term-primary: #1e3a5f;
    --term-accent:  #2563eb;
    --term-success: #16a34a;
    --term-warning: #d97706;
    --term-danger:  #dc2626;
    --term-purple:  #7c3aed;
    --term-muted:   #6b7280;
    --term-border:  #e2e8f0;
    --term-bg:      #f8fafc;
    --term-radius:  12px;
    --term-shadow:  0 2px 8px rgba(0,0,0,.08);
}

/* Loading overlay */
.loading-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); z-index: 9999;
    display: none; align-items: center; justify-content: center;
}
.loading-overlay.active { display: flex; }
.loading-spinner {
    background: white; padding: 24px 32px; border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.18); text-align: center;
}
.loading-spinner .spinner-border { width: 2.5rem; height: 2.5rem; }
.loading-spinner p { margin: 10px 0 0; font-size: 14px; font-weight: 600; color: var(--term-primary); }

/* Hero */
.term-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--term-radius); padding: 24px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.term-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px; background: rgba(255,255,255,.06); border-radius: 50%;
}
.term-hero h1 { font-size: 20px; font-weight: 700; color: #fff; margin: 0 0 4px; position: relative; }
.term-hero p  { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; position: relative; }

/* Info banner */
.promo-banner {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0; border-radius: 12px;
    padding: 16px 20px; margin-bottom: 20px;
}
.promo-banner-warning {
    background: linear-gradient(135deg, #fffbeb, #fef9c3);
    border: 1px solid #fde68a;
}

/* Switch styling */
.form-check-input.status-toggle,
.form-check-input.promotional-toggle {
    width: 3em; height: 1.5em; cursor: pointer;
    transition: all .3s ease;
}
.form-check-input.promotional-toggle:checked {
    background-color: #198754; border-color: #198754;
}
.form-check-input.status-toggle:checked {
    background-color: #198754; border-color: #198754;
}
.status-label.active   { color: #198754; font-weight: 500; }
.status-label.inactive { color: #dc3545; font-weight: 500; }

/* Modal styling */
#addTermModal .modal-content,
#editModal .modal-content {
    border: none; border-radius: 16px; overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    padding: 20px 28px; position: relative; overflow: hidden;
}
.modal-hero-bar::before {
    content: ''; position: absolute; top: -25px; right: -25px;
    width: 100px; height: 100px; background: rgba(255,255,255,.07); border-radius: 50%;
}
.modal-hero-bar h5 { color: #fff; font-weight: 700; margin: 0; font-size: 15px; position: relative; }
.modal-hero-bar .btn-close { position: absolute; top: 16px; right: 20px; filter: invert(1); }

/* Table styling - EVEN COLUMN WIDTHS */
.term-table {
    width: 100%;
    table-layout: fixed; /* This ensures even distribution */
}
.term-table th,
.term-table td {
    padding: 12px 16px;
    vertical-align: middle;
    font-size: 13px;
    border-bottom: 1px solid var(--term-border);
}
.term-table th {
    background: var(--term-bg);
    color: var(--term-primary);
    font-size: 12px;
    font-weight: 700;
    border-bottom: 2px solid var(--term-border);
}
.term-table tr:hover td {
    background: #f0f9ff;
}

/* Specific column widths - evenly distributed */
.term-table th:nth-child(1) { width: 5%; }   /* # */
.term-table th:nth-child(2) { width: 25%; }  /* Term Name */
.term-table th:nth-child(3) { width: 15%; }  /* Status */
.term-table th:nth-child(4) { width: 25%; }  /* Promotional Term */
.term-table th:nth-child(5) { width: 15%; }  /* Date Updated */
.term-table th:nth-child(6) { width: 15%; }  /* Actions */

/* Responsive adjustments */
@media (max-width: 768px) {
    .term-table {
        table-layout: auto;
    }
    .term-table th:nth-child(1),
    .term-table th:nth-child(5) {
        display: none;
    }
    .term-table td:nth-child(1),
    .term-table td:nth-child(5) {
        display: none;
    }
}

/* Search box */
.search-box {
    position: relative;
}
.search-box .form-control {
    padding-left: 38px;
    border-radius: 10px;
    border: 1.5px solid var(--term-border);
}
.search-box .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--term-muted);
    font-size: 16px;
}

/* Button styles */
.btn-outline-primary {
    border: 1px solid var(--term-border);
    transition: all 0.2s;
}
.btn-outline-primary:hover {
    background: var(--term-accent);
    border-color: var(--term-accent);
}
.btn-outline-danger:hover {
    background: var(--term-danger);
    border-color: var(--term-danger);
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Global loading overlay --}}
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading…</span>
            </div>
            <p>Processing…</p>
        </div>
    </div>

    <div class="term-hero">
        <h1><i class="ri-calendar-line me-2"></i>Term Management</h1>
        <p>Manage academic terms, set active/inactive status, and define promotional terms for student progression.</p>
    </div>

    {{-- Promotional term info banner --}}
    @php $promotionalTerm = $terms->firstWhere('is_promotional', true); @endphp
    @if ($promotionalTerm)
        <div class="promo-banner d-flex align-items-start gap-3">
            <i class="ri-award-line fs-4 text-success" style="flex-shrink:0"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1" style="color: var(--term-success);">
                    <i class="ri-star-fill me-1"></i> Promotional Term Active
                </div>
                <div>
                    <strong>{{ $promotionalTerm->term }}</strong> is currently set as the promotional term.
                    <span class="text-muted ms-2">Student promotion is evaluated at the end of this term.</span>
                </div>
            </div>
        </div>
    @else
        <div class="promo-banner promo-banner-warning d-flex align-items-start gap-3">
            <i class="ri-alert-line fs-4 text-warning" style="flex-shrink:0"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1" style="color: var(--term-warning);">
                    <i class="ri-information-line me-1"></i> No Promotional Term Set
                </div>
                <div>
                    No promotional term is currently selected.
                    <span class="text-muted">Students will show <strong>Awaiting Final Term</strong> on their reports until a promotional term is designated.</span>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex align-items-center flex-wrap gap-2">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-0">
                            <i class="ri-list-check-2 me-2"></i>Academic Terms
                            <span class="badge bg-primary-subtle text-primary ms-2">{{ count($terms) }}</span>
                        </h5>
                    </div>
                    <div class="flex-shrink-0 d-flex gap-2">
                        <div class="search-box">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search terms..." style="width: 250px;">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                        @can('Create term')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTermModal">
                                <i class="ri-add-line me-1"></i> Create Term
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table term-table mb-0" id="termTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Term Name</th>
                                    <th>Status</th>
                                    <th>Promotional Term</th>
                                    <th>Date Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($terms as $index => $term)
                                    <tr data-term-id="{{ $term->id }}" data-term-name="{{ strtolower($term->term) }}">
                                        <td>{{ $index + $terms->firstItem() ?? $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold" style="color: var(--term-primary);">
                                                {{ $term->term }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox"
                                                       class="form-check-input status-toggle"
                                                       data-id="{{ $term->id }}"
                                                       id="status-switch-{{ $term->id }}"
                                                       {{ $term->status ? 'checked' : '' }}>
                                                <label class="form-check-label ms-2 status-label {{ $term->status ? 'active' : 'inactive' }}"
                                                       for="status-switch-{{ $term->id }}">
                                                    {{ $term->status ? 'Active' : 'Inactive' }}
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch d-inline-flex align-items-center gap-2">
                                                <input type="checkbox"
                                                       class="form-check-input promotional-toggle"
                                                       role="switch"
                                                       data-id="{{ $term->id }}"
                                                       id="promo-switch-{{ $term->id }}"
                                                       {{ $term->is_promotional ? 'checked' : '' }}>
                                                <label class="form-check-label" for="promo-switch-{{ $term->id }}">
                                                    @if ($term->is_promotional)
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1" id="promo-label-{{ $term->id }}">
                                                            <i class="ri-award-line me-1"></i>Promotional
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-muted px-3 py-1" id="promo-label-{{ $term->id }}">
                                                            Not Promotional
                                                        </span>
                                                    @endif
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-muted">{{ $term->updated_at->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @can('Update term')
                                                    <button class="btn btn-sm btn-outline-primary edit-item-btn"
                                                            data-id="{{ $term->id }}"
                                                            data-term="{{ $term->term }}"
                                                            data-status="{{ $term->status ? 1 : 0 }}"
                                                            data-is-promotional="{{ $term->is_promotional ? 1 : 0 }}"
                                                            title="Edit Term">
                                                        <i class="ri-pencil-line"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete term')
                                                    <button class="btn btn-sm btn-outline-danger remove-item-btn"
                                                            data-id="{{ $term->id }}"
                                                            data-term="{{ $term->term }}"
                                                            title="Delete Term">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="ri-inbox-line" style="font-size: 48px; opacity: 0.3;"></i>
                                                <p class="mt-3 text-muted">No terms found.</p>
                                                <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addTermModal">
                                                    <i class="ri-add-line me-1"></i>Create your first term
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(method_exists($terms, 'links'))
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex justify-content-end">
                            {{ $terms->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
</div>

{{-- ADD TERM MODAL --}}
<div id="addTermModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-add-circle-line me-2"></i>Create New Term</h5>
            </div>
            <form id="addTermForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Term Name <span class="text-danger">*</span></label>
                        <input type="text" name="term" id="term_name" class="form-control"
                               placeholder="e.g., First Term 2024" required>
                        <div class="invalid-feedback" id="termNameError"></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="add-status-switch" name="status" checked>
                            <label class="form-check-label" for="add-status-switch">
                                <span class="fw-semibold">Active</span>
                                <small class="text-muted d-block">Active terms will be available for use.</small>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="add-promo-switch" name="is_promotional">
                            <label class="form-check-label" for="add-promo-switch">
                                <span class="fw-semibold">Promotional Term</span>
                                <small class="text-muted d-block">If enabled, all other terms will lose their promotional status.</small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addBtn">
                        <i class="ri-save-line me-1"></i>Create Term
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT TERM MODAL --}}
<div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-edit-circle-line me-2"></i>Edit Term</h5>
            </div>
            <form id="editTermForm">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <input type="hidden" id="edit-id-field" name="id">
                    <div class="mb-3">
                        <label class="form-label">Term Name <span class="text-danger">*</span></label>
                        <input type="text" name="term" id="edit-term" class="form-control" required>
                        <div class="invalid-feedback" id="editTermNameError"></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="edit-status-switch" name="status">
                            <label class="form-check-label" for="edit-status-switch">
                                <span class="fw-semibold">Active</span>
                                <small class="text-muted d-block">Active terms will be available for use.</small>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="edit-promo-switch" name="is_promotional">
                            <label class="form-check-label" for="edit-promo-switch">
                                <span class="fw-semibold">Promotional Term</span>
                                <small class="text-muted d-block">If enabled, all other terms will lose their promotional status.</small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updateBtn">
                        <i class="ri-save-line me-1"></i>Update Term
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DELETE CONFIRM MODAL --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">Are you sure you want to delete the term: <strong id="deleteTermName"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone. Associated data may be affected.</p>
            </div>
            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="ri-delete-bin-line me-1"></i>Delete Term
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    const CSRF = '{{ csrf_token() }}';
    let deleteId = null;

    // Helper: Show loading
    function showLoading(show) {
        if (show) {
            $('#loadingOverlay').addClass('active');
        } else {
            $('#loadingOverlay').removeClass('active');
        }
    }

    // Helper: Show SweetAlert toast
    function showToast(icon, title) {
        Swal.fire({
            icon: icon,
            title: title,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    // Search functionality
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#termTable tbody tr').filter(function() {
            const termName = $(this).find('td:eq(1)').text().toLowerCase();
            $(this).toggle(termName.indexOf(searchTerm) > -1);
        });
    });

    // Promotional toggle
    $(document).on('change', '.promotional-toggle', function() {
        const id = $(this).data('id');
        const isPromo = $(this).is(':checked');
        const $toggle = $(this);
        const $label = $(`#promo-label-${id}`);

        showLoading(true);
        $toggle.prop('disabled', true);

        $.ajax({
            url: `/term/${id}/promotional`,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: JSON.stringify({ is_promotional: isPromo ? 1 : 0 }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    if (isPromo) {
                        // Reset all other toggles
                        $('.promotional-toggle').not($toggle).each(function() {
                            const otherId = $(this).data('id');
                            $(this).prop('checked', false);
                            $(`#promo-label-${otherId}`)
                                .removeClass('bg-success-subtle text-success border border-success-subtle')
                                .addClass('bg-light text-muted')
                                .html('Not Promotional');
                        });
                        // Update current label
                        $label
                            .removeClass('bg-light text-muted')
                            .addClass('bg-success-subtle text-success border border-success-subtle')
                            .html('<i class="ri-award-line me-1"></i>Promotional');
                        showToast('success', response.message);
                    } else {
                        $label
                            .removeClass('bg-success-subtle text-success border border-success-subtle')
                            .addClass('bg-light text-muted')
                            .html('Not Promotional');
                        showToast('success', response.message);
                    }
                    // Reload to update banner
                    setTimeout(() => location.reload(), 1500);
                } else {
                    $toggle.prop('checked', !isPromo);
                    showToast('error', response.message || 'Failed to update promotional term');
                }
            },
            error: function(xhr) {
                $toggle.prop('checked', !isPromo);
                const error = xhr.responseJSON?.message || 'An error occurred';
                showToast('error', error);
            },
            complete: function() {
                showLoading(false);
                $toggle.prop('disabled', false);
            }
        });
    });

    // Status toggle
    $(document).on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const isActive = $(this).is(':checked');

        showLoading(true);

        $.ajax({
            url: `/term/${id}/status`,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: JSON.stringify({ status: isActive ? 1 : 0 }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    const statusText = isActive ? 'Active' : 'Inactive';
                    showToast('success', `Term ${statusText.toLowerCase()} successfully`);
                    // Update label class
                    const $statusLabel = $(`.status-toggle[data-id="${id}"]`).next('.status-label');
                    if (isActive) {
                        $statusLabel.removeClass('inactive').addClass('active').text('Active');
                    } else {
                        $statusLabel.removeClass('active').addClass('inactive').text('Inactive');
                    }
                } else {
                    $(this).prop('checked', !isActive);
                    showToast('error', response.message || 'Failed to update status');
                }
            },
            error: function() {
                $(this).prop('checked', !isActive);
                showToast('error', 'An error occurred while updating status');
            },
            complete: function() {
                showLoading(false);
            }
        });
    });

    // Edit button click
    $(document).on('click', '.edit-item-btn', function() {
        const id = $(this).data('id');
        const term = $(this).data('term');
        const status = $(this).data('status');
        const isPromo = $(this).data('is-promotional');

        $('#edit-id-field').val(id);
        $('#edit-term').val(term);
        $('#edit-status-switch').prop('checked', status == 1);
        $('#edit-promo-switch').prop('checked', isPromo == 1);
        $('#editModal').modal('show');
    });

    // Delete button click
    $(document).on('click', '.remove-item-btn', function() {
        deleteId = $(this).data('id');
        const termName = $(this).data('term');
        $('#deleteTermName').text(termName);
        $('#deleteModal').modal('show');
    });

    // Confirm delete
    $('#confirmDeleteBtn').on('click', function() {
        if (!deleteId) return;

        showLoading(true);
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting...');

        $.ajax({
            url: `/term/${deleteId}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function(response) {
                if (response.success) {
                    showToast('success', 'Term deleted successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', response.message || 'Failed to delete term');
                    $('#deleteModal').modal('hide');
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                showToast('error', error);
                $('#deleteModal').modal('hide');
            },
            complete: function() {
                showLoading(false);
                btn.prop('disabled', false).html('<i class="ri-delete-bin-line me-1"></i>Delete Term');
                deleteId = null;
            }
        });
    });

    // Add term form submit
    $('#addTermForm').on('submit', function(e) {
        e.preventDefault();

        const termName = $('#term_name').val().trim();
        if (!termName) {
            $('#term_name').addClass('is-invalid');
            $('#termNameError').text('Term name is required');
            return;
        }

        $('#term_name').removeClass('is-invalid');
        showLoading(true);

        const formData = {
            term: termName,
            status: $('#add-status-switch').is(':checked') ? 1 : 0,
            is_promotional: $('#add-promo-switch').is(':checked') ? 1 : 0
        };

        $.ajax({
            url: '{{ route("term.store") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    showToast('success', 'Term created successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', response.message || 'Failed to create term');
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred';
                if (xhr.responseJSON?.errors?.term) {
                    errorMsg = xhr.responseJSON.errors.term[0];
                } else if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                $('#term_name').addClass('is-invalid');
                $('#termNameError').text(errorMsg);
                showToast('error', errorMsg);
            },
            complete: function() {
                showLoading(false);
            }
        });
    });

    // Edit term form submit
    $('#editTermForm').on('submit', function(e) {
        e.preventDefault();

        const id = $('#edit-id-field').val();
        const termName = $('#edit-term').val().trim();

        if (!termName) {
            $('#edit-term').addClass('is-invalid');
            $('#editTermNameError').text('Term name is required');
            return;
        }

        $('#edit-term').removeClass('is-invalid');
        showLoading(true);

        const formData = {
            term: termName,
            status: $('#edit-status-switch').is(':checked') ? 1 : 0,
            is_promotional: $('#edit-promo-switch').is(':checked') ? 1 : 0
        };

        $.ajax({
            url: `/term/${id}`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-HTTP-Method-Override': 'PUT'
            },
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    showToast('success', 'Term updated successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', response.message || 'Failed to update term');
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred';
                if (xhr.responseJSON?.errors?.term) {
                    errorMsg = xhr.responseJSON.errors.term[0];
                } else if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                $('#edit-term').addClass('is-invalid');
                $('#editTermNameError').text(errorMsg);
                showToast('error', errorMsg);
            },
            complete: function() {
                showLoading(false);
            }
        });
    });
});
</script>
@endsection
