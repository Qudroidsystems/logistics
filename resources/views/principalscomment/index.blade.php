@extends('layouts.master')

@section('content')
<style>
    .staff-image {
        width: 40px;
        height: 40px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .staff-image:hover {
        transform: scale(1.1);
    }
    .table-actions {
        white-space: nowrap;
    }
    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
    }
    .pagination-info {
        color: #6c757d;
        font-size: 0.875rem;
    }
    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    .page-link {
        color: #0d6efd;
    }
    .page-link:hover {
        color: #0a58ca;
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Principals Comment Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Principals Comment</a></li>
                                <li class="breadcrumb-item active">Assignments</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div id="principalsCommentList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" id="searchInput" placeholder="Search assignments...">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 ms-auto">
                                        <select class="form-select" id="entriesPerPage">
                                            <option value="10">10 per page</option>
                                            <option value="25">25 per page</option>
                                            <option value="50">50 per page</option>
                                            <option value="100">100 per page</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Principals Comment Assignments 
                                        <span class="badge bg-dark-subtle text-dark ms-1" id="total-records">0</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    @can('Create principals-comment')
                                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addPrincipalsCommentModal">
                                            <i class="bi bi-plus-circle align-baseline me-1"></i> Create Assignment
                                        </button>
                                    @endcan
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                        <thead>
                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th>SN</th>
                                                <th>Staff</th>
                                                <th>Class</th>
                                                <th>Arm</th>
                                                <th>Session</th>
                                                <th>Term</th>
                                                <th>Date Updated</th>
                                                <th class="table-actions">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="assignmentsTableBody" class="fw-semibold text-gray-600">
                                            <!-- Data will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <div class="pagination-container mt-3">
                                    <div class="pagination-info" id="paginationInfo"></div>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination pagination-sm mb-0" id="pagination">
                                            <!-- Pagination links will be generated by JavaScript -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Modal -->
                <div class="modal fade" id="addPrincipalsCommentModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Assignment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="add-principalscomment-form">
                                @csrf
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label>Staff Member *</label>
                                            <select name="staffId" class="form-control" required>
                                                <option value="">Select Staff</option>
                                                @foreach ($staff as $s)
                                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Session *</label>
                                            <select name="sessionid" class="form-control" required>
                                                <option value="">Select Session</option>
                                                @foreach ($sessions as $session)
                                                    <option value="{{ $session->id }}" {{ $session->status == 'Current' ? 'selected' : '' }}>
                                                        {{ $session->session }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Term *</label>
                                            <select name="termid" class="form-control" required>
                                                <option value="">Select Term</option>
                                                @foreach ($terms as $term)
                                                    <option value="{{ $term->id }}">{{ $term->term }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Classes *</label>
                                            <div class="border p-3 rounded" style="max-height: 200px; overflow-y: auto;">
                                                @foreach ($schoolclasses as $class)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="schoolclassid[]" value="{{ $class->id }}">
                                                        <label class="form-check-label">
                                                            {{ $class->schoolclass }} ({{ $class->arm ?? 'No Arm' }})
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-danger mt-3 d-none" id="alert-error-msg"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary" id="add-btn">Add Assignment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal -->
                <div class="modal fade" id="editPrincipalsCommentModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Assignment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="edit-principalscomment-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id" id="edit-id">
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label>Staff Member *</label>
                                            <select name="staffId" id="edit-staffId" class="form-control" required>
                                                <option value="">Select Staff</option>
                                                @foreach ($staff as $s)
                                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <label>Class *</label>
                                            <select name="schoolclassid" id="edit-schoolclassid" class="form-control" required>
                                                <option value="">Select Class</option>
                                                @foreach ($schoolclasses as $class)
                                                    <option value="{{ $class->id }}">
                                                        {{ $class->schoolclass }} ({{ $class->arm ?? 'No Arm' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="alert alert-danger mt-3 d-none" id="edit-alert-error-msg"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary" id="edit-btn">Update Assignment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Delete</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this assignment? This action cannot be undone.</p>
                                <input type="hidden" id="delete-id">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image View Modal -->
                <div class="modal fade" id="imageViewModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalTitle">Staff Photo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="modalStaffImage" src="" alt="Staff Photo" class="img-fluid rounded">
                                <h6 class="mt-3" id="modalStaffName"></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    let assignmentsData = @json($principalscomments);
    let filteredData = [...assignmentsData];
    let currentPage = 1;
    let entriesPerPage = 10;
    let searchTerm = '';

    // DOM Elements
    const tableBody = document.getElementById('assignmentsTableBody');
    const searchInput = document.getElementById('searchInput');
    const entriesSelect = document.getElementById('entriesPerPage');
    const totalRecordsSpan = document.getElementById('total-records');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationContainer = document.getElementById('pagination');

    // Initialize
    updateTable();

    // Search functionality
    searchInput.addEventListener('input', function() {
        searchTerm = this.value.toLowerCase();
        currentPage = 1;
        filterData();
        updateTable();
    });

    // Entries per page change
    entriesSelect.addEventListener('change', function() {
        entriesPerPage = parseInt(this.value);
        currentPage = 1;
        updateTable();
    });

    // Filter data based on search term
    function filterData() {
        if (!searchTerm) {
            filteredData = [...assignmentsData];
            return;
        }
        
        filteredData = assignmentsData.filter(assignment => {
            return (
                assignment.staffname.toLowerCase().includes(searchTerm) ||
                assignment.sclass.toLowerCase().includes(searchTerm) ||
                (assignment.schoolarm && assignment.schoolarm.toLowerCase().includes(searchTerm)) ||
                assignment.session_name.toLowerCase().includes(searchTerm) ||
                assignment.term_name.toLowerCase().includes(searchTerm)
            );
        });
    }

    // Update table with pagination
    function updateTable() {
        filterData();
        
        // Update total records
        totalRecordsSpan.textContent = filteredData.length;
        
        // Calculate pagination
        const totalPages = Math.ceil(filteredData.length / entriesPerPage);
        const startIndex = (currentPage - 1) * entriesPerPage;
        const endIndex = Math.min(startIndex + entriesPerPage, filteredData.length);
        const pageData = filteredData.slice(startIndex, endIndex);
        
        // Clear table body
        tableBody.innerHTML = '';
        
        if (pageData.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">No assignments found</td>
                </tr>
            `;
        } else {
            // Populate table rows
            pageData.forEach((assignment, index) => {
                const picture = assignment.picture || 'unnamed.jpg';
                const imagePath = `{{ asset('storage/staff_avatars/') }}/${picture}`;
                const fallbackImage = `{{ asset('storage/staff_avatars/unnamed.jpg') }}`;
                const sn = startIndex + index + 1;
                const formattedDate = new Date(assignment.updated_at).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${sn}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                <img src="${imagePath}" alt="${assignment.staffname}" 
                                     class="rounded-circle avatar-md staff-image"
                                     data-bs-toggle="modal" data-bs-target="#imageViewModal"
                                     data-image="${imagePath}" 
                                     data-teachername="${assignment.staffname}"
                                     onerror="this.src='${fallbackImage}';" />
                            </div>
                            <div>${assignment.staffname}</div>
                        </div>
                    </td>
                    <td>${assignment.sclass}</td>
                    <td>${assignment.schoolarm || 'N/A'}</td>
                    <td>${assignment.session_name}</td>
                    <td>${assignment.term_name}</td>
                    <td>${formattedDate}</td>
                    <td class="table-actions">
                        <ul class="d-flex gap-2 list-unstyled mb-0">
                            @can('Update principals-comment')
                                <li>
                                    <button class="btn btn-subtle-secondary btn-icon btn-sm edit-btn" 
                                            data-id="${assignment.pcid}"
                                            data-staffid="${assignment.staffid}"
                                            data-schoolclassid="${assignment.schoolclassid}">
                                        <i class="ph-pencil"></i>
                                    </button>
                                </li>
                            @endcan
                            @can('Delete principals-comment')
                                <li>
                                    <button class="btn btn-subtle-danger btn-icon btn-sm delete-btn" 
                                            data-id="${assignment.pcid}">
                                        <i class="ph-trash"></i>
                                    </button>
                                </li>
                            @endcan
                        </ul>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }
        
        // Update pagination info
        paginationInfo.innerHTML = `Showing ${startIndex + 1} to ${endIndex} of ${filteredData.length} entries`;
        
        // Generate pagination links
        generatePagination(totalPages);
        
        // Add event listeners to edit and delete buttons
        attachEventListeners();
    }

    // Generate pagination links
    function generatePagination(totalPages) {
        paginationContainer.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>`;
        paginationContainer.appendChild(prevLi);
        
        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === currentPage ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            paginationContainer.appendChild(li);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>`;
        paginationContainer.appendChild(nextLi);
        
        // Add click event to pagination links
        paginationContainer.addEventListener('click', function(e) {
            e.preventDefault();
            if (e.target.tagName === 'A' && e.target.dataset.page) {
                const page = parseInt(e.target.dataset.page);
                if (page >= 1 && page <= totalPages && page !== currentPage) {
                    currentPage = page;
                    updateTable();
                }
            }
        });
    }

    // Attach event listeners to edit and delete buttons
    function attachEventListeners() {
        // Edit buttons
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const staffId = this.dataset.staffid;
                const schoolclassid = this.dataset.schoolclassid;
                
                document.getElementById('edit-id').value = id;
                document.getElementById('edit-staffId').value = staffId;
                document.getElementById('edit-schoolclassid').value = schoolclassid;
                
                const editModal = new bootstrap.Modal(document.getElementById('editPrincipalsCommentModal'));
                editModal.show();
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                document.getElementById('delete-id').value = id;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
                deleteModal.show();
            });
        });
        
        // Image view
        document.querySelectorAll('.staff-image').forEach(img => {
            img.addEventListener('click', function() {
                document.getElementById('modalStaffImage').src = this.dataset.image;
                document.getElementById('modalStaffName').textContent = this.dataset.teachername;
            });
        });
    }

    // Add form submission
    const addForm = document.getElementById('add-principalscomment-form');
    const addAlertError = document.getElementById('alert-error-msg');
    
    addForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset error message
        addAlertError.classList.add('d-none');
        addAlertError.innerHTML = '';
        
        const formData = new FormData(this);
        const addBtn = document.getElementById('add-btn');
        const originalBtnText = addBtn.innerHTML;
        
        addBtn.disabled = true;
        addBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
        
        fetch('{{ route("principalscomment.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addPrincipalsCommentModal'));
                modal.hide();
                
                // Reset form
                addForm.reset();
                
                // Show success message
                showToast(data.message, 'success');
                
                // Reload page to get updated data
                setTimeout(() => location.reload(), 1500);
            } else {
                // Show validation errors
                if (data.errors) {
                    let errors = '';
                    for (const key in data.errors) {
                        errors += `<div>${data.errors[key][0]}</div>`;
                    }
                    addAlertError.innerHTML = errors;
                    addAlertError.classList.remove('d-none');
                } else if (data.message) {
                    addAlertError.innerHTML = data.message;
                    addAlertError.classList.remove('d-none');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addAlertError.innerHTML = 'An error occurred. Please try again.';
            addAlertError.classList.remove('d-none');
        })
        .finally(() => {
            addBtn.disabled = false;
            addBtn.innerHTML = originalBtnText;
        });
    });

    // Edit form submission
    const editForm = document.getElementById('edit-principalscomment-form');
    const editAlertError = document.getElementById('edit-alert-error-msg');
    
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset error message
        editAlertError.classList.add('d-none');
        editAlertError.innerHTML = '';
        
        const id = document.getElementById('edit-id').value;
        const formData = new FormData(this);
        const editBtn = document.getElementById('edit-btn');
        const originalBtnText = editBtn.innerHTML;
        
        editBtn.disabled = true;
        editBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        
        fetch(`/principalscomment/${id}`, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-HTTP-Method-Override': 'PUT'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editPrincipalsCommentModal'));
                modal.hide();
                
                // Reset form
                editForm.reset();
                
                // Show success message
                showToast(data.message, 'success');
                
                // Reload page to get updated data
                setTimeout(() => location.reload(), 1500);
            } else {
                // Show validation errors
                if (data.errors) {
                    let errors = '';
                    for (const key in data.errors) {
                        errors += `<div>${data.errors[key][0]}</div>`;
                    }
                    editAlertError.innerHTML = errors;
                    editAlertError.classList.remove('d-none');
                } else if (data.message) {
                    editAlertError.innerHTML = data.message;
                    editAlertError.classList.remove('d-none');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            editAlertError.innerHTML = 'An error occurred. Please try again.';
            editAlertError.classList.remove('d-none');
        })
        .finally(() => {
            editBtn.disabled = false;
            editBtn.innerHTML = originalBtnText;
        });
    });

    // Delete confirmation
    document.getElementById('confirm-delete-btn').addEventListener('click', function() {
        const id = document.getElementById('delete-id').value;
        const deleteBtn = this;
        const originalBtnText = deleteBtn.innerHTML;
        
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
        
        fetch(`/principalscomment/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal'));
                modal.hide();
                
                // Show success message
                showToast(data.message, 'success');
                
                // Reload page to get updated data
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to delete assignment', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalBtnText;
        });
    });

    // Toast notification function
    function showToast(message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast-container');
        existingToasts.forEach(toast => toast.remove());
        
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Initialize and show toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 3000
        });
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }

    // Reset form when modal is closed
    const addModal = document.getElementById('addPrincipalsCommentModal');
    addModal.addEventListener('hidden.bs.modal', function() {
        addForm.reset();
        addAlertError.classList.add('d-none');
        addAlertError.innerHTML = '';
    });
    
    const editModal = document.getElementById('editPrincipalsCommentModal');
    editModal.addEventListener('hidden.bs.modal', function() {
        editForm.reset();
        editAlertError.classList.add('d-none');
        editAlertError.innerHTML = '';
    });
});
</script>
@endsection