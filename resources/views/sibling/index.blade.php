@extends('layouts.master')

@section('content')
<style>
:root {
    --sib-primary: #1e3a5f;
    --sib-accent: #2563eb;
    --sib-success: #16a34a;
    --sib-warning: #d97706;
    --sib-danger: #dc2626;
    --sib-border: #e2e8f0;
    --sib-radius: 12px;
}

.sib-hero {
    background: linear-gradient(135deg, var(--sib-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sib-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.sib-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.sib-hero h1 { font-size: 22px; font-weight: 700; color: white; margin: 0 0 6px; position: relative; }
.sib-hero p { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; position: relative; }

.stat-card {
    background: white;
    border: 1px solid var(--sib-border);
    border-radius: var(--sib-radius);
    padding: 18px 20px;
    transition: transform .15s, box-shadow .15s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.stat-card .stat-value { font-size: 28px; font-weight: 700; color: var(--sib-primary); }
.stat-card .stat-label { font-size: 12px; color: #6b7280; margin-top: 4px; }
.stat-card .stat-icon { font-size: 32px; opacity: .12; float: right; margin-top: -8px; }

.group-card {
    background: white;
    border: 1px solid var(--sib-border);
    border-radius: var(--sib-radius);
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.2s;
    cursor: pointer;
}
.group-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,.1);
    border-color: var(--sib-accent);
}
.student-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eff6ff;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    margin: 3px;
}
.discount-badge {
    background: #dcfce7;
    color: #16a34a;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}
.filter-bar {
    background: white; border: 1px solid var(--sib-border);
    border-radius: var(--sib-radius); padding: 16px 20px; margin-bottom: 20px;
}
.search-box {
    position: relative;
}
.search-box input {
    padding-left: 38px;
}
.search-box .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="sib-hero">
        <h1><i class="ri-group-line me-2"></i>{{ $pagetitle ?? 'Sibling Groups Management' }}</h1>
        <p>Manage family groups, apply sibling discounts, and track family savings across multiple children.</p>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4" id="statsRow">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value" id="totalGroups">0</div>
                <div class="stat-label">Total Families</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-line"></i></div>
                <div class="stat-value" id="totalStudents">0</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-money-saved-line"></i></div>
                <div class="stat-value text-success" id="totalSavings">₦0</div>
                <div class="stat-label">Total Savings</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-discount-line"></i></div>
                <div class="stat-value text-warning" id="activeDiscounts">0</div>
                <div class="stat-label">Active Discounts</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="search-box">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by family name...">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            <div class="col-md-6">
                <select class="form-select" id="discountFilter">
                    <option value="">All Groups</option>
                    <option value="has_discount">Has Discount Applied</option>
                    <option value="no_discount">No Discount</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <a href="{{ route('sibling.create') }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i>Create Family Group
                </a>
            </div>
        </div>
    </div>

    {{-- Groups Container --}}
    <div id="groupsContainer">
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading family groups...</p>
        </div>
    </div>

</div>
</div>
</div>

{{-- View Group Modal --}}
<div class="modal fade" id="viewGroupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="ri-group-line me-2"></i>Family Group Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewGroupContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Apply Discount Modal --}}
<div class="modal fade" id="applyDiscountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="ri-discount-line me-2"></i>Apply Family Discount</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="applyDiscountForm">
                @csrf
                <input type="hidden" name="group_id" id="discountGroupId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                        <select name="discount_type" id="applyDiscountType" class="form-select" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed_per_child">Fixed Amount per Child (₦)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="applyDiscountValueLabel">Discount Value (%)</label>
                        <input type="number" name="discount_value" id="applyDiscountValue" class="form-control" step="0.01" required>
                        <small class="text-muted">Additional 5% discount for each subsequent child after the first (max 50%).</small>
                    </div>
                    <div class="alert alert-danger d-none" id="discountFormErrors"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Apply Discount</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Delete Family Group</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this family group?</p>
                <p class="text-muted small">This will also remove any sibling discounts applied to students in this group.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
let currentDeleteId = null;

$(document).ready(function() {
    loadGroups();

    $('#searchInput').on('keyup', filterGroups);
    $('#discountFilter').on('change', filterGroups);

    function filterGroups() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        const discountFilter = $('#discountFilter').val();

        $('.group-card').each(function() {
            const familyName = $(this).data('family-name')?.toLowerCase() || '';
            const hasDiscount = $(this).data('has-discount') === 'true';

            let matchesSearch = familyName.includes(searchTerm) || searchTerm === '';
            let matchesFilter = true;

            if (discountFilter === 'has_discount') matchesFilter = hasDiscount;
            else if (discountFilter === 'no_discount') matchesFilter = !hasDiscount;

            $(this).closest('.col-md-6, .col-lg-4').toggle(matchesSearch && matchesFilter);
        });
    }

    function loadGroups() {
        $.ajax({
            url: '{{ route("sibling.index") }}',
            type: 'GET',
            data: { ajax: true },
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function(response) {
                if (response.success) {
                    renderGroups(response.data);
                    updateStats(response.stats);
                } else {
                    $('#groupsContainer').html('<div class="alert alert-danger text-center">' + (response.message || 'Failed to load family groups.') + '</div>');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr);
                let errorMessage = 'Failed to load family groups.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $('#groupsContainer').html('<div class="alert alert-danger text-center">' + errorMessage + '</div>');
            }
        });
    }

    function renderGroups(groups) {
        const container = $('#groupsContainer');
        if (!groups || groups.length === 0) {
            container.html(`
                <div class="alert alert-info text-center py-5">
                    <i class="ri-inbox-line ri-2x d-block mb-2"></i>
                    No family groups found. Click "Create Family Group" to add one.
                </div>
            `);
            return;
        }

        let html = '<div class="row">';
        groups.forEach(group => {
            const studentsHtml = group.students.map(s => `
                <span class="student-badge">
                    <i class="ri-user-line"></i> ${escapeHtml(s.name)} (${s.admission_no})
                </span>
            `).join('');

            const discountHtml = group.discount_value ? `
                <div class="discount-badge mt-2">
                    <i class="ri-discount-line me-1"></i>
                    ${group.discount_type === 'percentage' ? group.discount_value + '% off' : '₦' + parseFloat(group.discount_value).toLocaleString() + ' per child'}
                </div>
            ` : '<span class="text-muted small">No discount applied</span>';

            html += `
                <div class="col-md-6 col-lg-4">
                    <div class="group-card"
                         data-family-name="${escapeHtml(group.family_name)}"
                         data-has-discount="${group.discount_value ? 'true' : 'false'}"
                         data-group-id="${group.id}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1 fw-bold">${escapeHtml(group.family_name)}</h5>
                                <small class="text-muted">Group #: ${group.group_no}</small>
                            </div>
                            ${discountHtml}
                        </div>

                        <div class="mb-3">
                            <div class="small text-muted mb-2">Children (${group.total_children})</div>
                            <div class="d-flex flex-wrap gap-1">
                                ${studentsHtml || '<span class="text-muted">No students assigned</span>'}
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-sm btn-info view-group" data-id="${group.id}">
                                <i class="ri-eye-line"></i> View
                            </button>
                            <a href="/sibling/${group.id}/edit" class="btn btn-sm btn-primary">
                                <i class="ri-pencil-line"></i> Edit
                            </a>
                            <button class="btn btn-sm btn-success apply-discount" data-id="${group.id}">
                                <i class="ri-discount-line"></i> Discount
                            </button>
                            <button class="btn btn-sm btn-danger delete-group" data-id="${group.id}" data-name="${escapeHtml(group.family_name)}">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>

                        <div class="mt-2 pt-2 border-top">
                            <small class="text-muted">
                                <i class="ri-phone-line me-1"></i> ${group.parent_phone || 'N/A'} |
                                <i class="ri-mail-line me-1"></i> ${group.parent_email || 'N/A'}
                            </small>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.html(html);
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function updateStats(stats) {
        if (stats) {
            $('#totalGroups').text(stats.total_groups || 0);
            $('#totalStudents').text(stats.total_students || 0);
            $('#totalSavings').text('₦' + (stats.total_savings?.toLocaleString() || '0'));
            $('#activeDiscounts').text(stats.active_discounts || 0);
        }
    }

    // View Group
    $(document).on('click', '.view-group', function() {
        const id = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('viewGroupModal'));

        $.ajax({
            url: `/sibling/${id}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const group = response.data.group;
                    const students = response.data.students;

                    const studentsList = students.map(s => `
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${escapeHtml(s.name)}</strong><br>
                                    <small class="text-muted">Admission: ${s.admission_no}</small>
                                </div>
                                <span class="badge bg-info">Class: ${s.class}</span>
                            </div>
                        </li>
                    `).join('');

                    $('#viewGroupContent').html(`
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Family Name</div>
                                    <div class="fw-bold fs-5">${escapeHtml(group.family_name)}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Group Number</div>
                                    <div class="fw-bold"><code>${group.group_no}</code></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Parent Contact</div>
                                    <div><i class="ri-phone-line me-1"></i> ${group.parent_phone || 'N/A'}</div>
                                    <div><i class="ri-mail-line me-1"></i> ${group.parent_email || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted">Discount Applied</div>
                                    <div class="fw-bold text-success">
                                        ${group.discount_value ? (group.discount_type === 'percentage' ? group.discount_value + '%' : '₦' + group.discount_value + ' per child') : 'None'}
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted mb-2">Address</div>
                                    <div>${group.address || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-3">
                                    <div class="small text-muted mb-2">Children (${students.length})</div>
                                    <ul class="list-group list-group-flush">${studentsList || '<li class="list-group-item">No students assigned</li>'}</ul>
                                </div>
                            </div>
                        </div>
                    `);
                    modal.show();
                }
            },
            error: function() {
                $('#viewGroupContent').html('<div class="alert alert-danger">Failed to load group details.</div>');
            }
        });
    });

    // Apply Discount
    $(document).on('click', '.apply-discount', function() {
        const id = $(this).data('id');
        $('#discountGroupId').val(id);
        $('#applyDiscountValue').val('');
        $('#discountFormErrors').addClass('d-none');
        new bootstrap.Modal(document.getElementById('applyDiscountModal')).show();
    });

    $('#applyDiscountType').on('change', function() {
        const valueLabel = $('#applyDiscountValueLabel');
        if (this.value === 'percentage') {
            valueLabel.text('Discount Value (%)');
            $('#applyDiscountValue').attr('step', '0.01');
        } else {
            valueLabel.text('Discount Value (₦ per child)');
            $('#applyDiscountValue').attr('step', '100');
        }
    });

    $('#applyDiscountForm').on('submit', async function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        try {
            const response = await fetch('{{ route("sibling.apply-discount") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('Success!', data.message, 'success');
                $('#applyDiscountModal').modal('hide');
                loadGroups();
            } else {
                let errorMsg = data.message || 'Something went wrong.';
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join('<br>');
                }
                $('#discountFormErrors').removeClass('d-none').html(errorMsg);
            }
        } catch (error) {
            Swal.fire('Error!', 'Something went wrong', 'error');
        }
    });

    // Delete Group
    $(document).on('click', '.delete-group', function() {
        currentDeleteId = $(this).data('id');
        const familyName = $(this).data('name');
        $('#deleteModal .modal-body p:first').text(`Are you sure you want to delete "${familyName}" family group?`);
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirmDeleteBtn').on('click', async function() {
        if (!currentDeleteId) return;

        try {
            const response = await fetch(`/sibling/${currentDeleteId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('Deleted!', data.message, 'success');
                $('#deleteModal').modal('hide');
                loadGroups();
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'Something went wrong', 'error');
        }
        currentDeleteId = null;
    });
});
</script>
@endsection
