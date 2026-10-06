{{-- resources/views/admin/discount/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --disc-primary: #1e3a5f;
    --disc-accent: #2563eb;
    --disc-success: #16a34a;
    --disc-warning: #d97706;
    --disc-danger: #dc2626;
    --disc-border: #e2e8f0;
    --disc-radius: 12px;
}

.disc-hero {
    background: linear-gradient(135deg, var(--disc-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--disc-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
}
.disc-hero h1 { font-size: 22px; font-weight: 700; color: white; margin: 0 0 6px; }
.disc-hero p { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; }

.stat-card {
    background: white;
    border: 1px solid var(--disc-border);
    border-radius: var(--disc-radius);
    padding: 18px 20px;
    transition: all 0.2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.stat-card .stat-value { font-size: 28px; font-weight: 700; color: var(--disc-primary); }
.stat-card .stat-label { font-size: 12px; color: #6b7280; margin-top: 4px; }

.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500;
}
.status-active { background: #dcfce7; color: #16a34a; }
.status-draft { background: #fef3c7; color: #d97706; }
.status-expired { background: #fee2e2; color: #dc2626; }
.status-suspended { background: #f3f4f6; color: #6b7280; }

.filter-bar {
    background: white; border: 1px solid var(--disc-border);
    border-radius: var(--disc-radius); padding: 16px 20px; margin-bottom: 20px;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="disc-hero">
        <h1><i class="ri-discount-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Manage discounts, configure discount types, assign to students, and track savings.</p>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-discount-line"></i></div>
                <div class="stat-value">{{ $totalDiscounts ?? 0 }}</div>
                <div class="stat-label">Total Discounts</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-checkbox-circle-line"></i></div>
                <div class="stat-value text-success">{{ $activeDiscounts ?? 0 }}</div>
                <div class="stat-label">Active Discounts</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-money-saved-line"></i></div>
                <div class="stat-value text-warning">₦{{ number_format($totalSavings ?? 0, 2) }}</div>
                <div class="stat-label">Total Savings</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-settings-line"></i></div>
                <div class="stat-value text-primary">{{ $totalBeneficiaries ?? 0 }}</div>
                <div class="stat-label">Beneficiaries</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="search-box">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by title or code...">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="expired">Expired</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="typeFilter">
                    <option value="">All Types</option>
                    @foreach($discountTypes ?? [] as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 text-end">
                <a href="{{ route('admin.discount.create') }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i>Create Discount
                </a>
            </div>
        </div>
    </div>

    {{-- Discounts Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold" style="color: var(--disc-primary);">
                <i class="ri-list-check me-2"></i>All Discounts
                <span class="badge bg-primary ms-2">{{ $discounts->total() ?? 0 }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" id="checkAll"></th>
                            <th>#</th>
                            <th>Discount No.</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Applicable To</th>
                            <th>Status</th>
                            <th>Effective Period</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = ($discounts->currentPage() - 1) * $discounts->perPage(); @endphp
                        @forelse($discounts as $discount)
                            <tr data-id="{{ $discount->id }}">
                                <td><input type="checkbox" class="row-checkbox" value="{{ $discount->id }}"></td>
                                <td>{{ ++$i }}</td>
                                <td><code>{{ $discount->discount_no }}</code></td>
                                <td class="fw-semibold">{{ $discount->title }}</td>
                                <td>{{ $discount->type->name ?? 'N/A' }}</td>
                                <td>
                                    @if($discount->value_type == 'percentage')
                                        {{ $discount->value }}%
                                    @else
                                        ₦{{ number_format($discount->value, 2) }}
                                    @endif
                                </td>
                                <td>
                                    @if($discount->applicable_to == 'all_bills')
                                        All Bills
                                    @elseif($discount->applicable_to == 'specific_bills')
                                        Selected Bills
                                    @else
                                        Selected Categories
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $discount->status }}">
                                        <i class="ri-{{ $discount->status == 'active' ? 'check-circle-line' : ($discount->status == 'draft' ? 'edit-line' : 'close-circle-line') }}"></i>
                                        {{ ucfirst($discount->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        {{ \Carbon\Carbon::parse($discount->effective_from)->format('d M Y') }}
                                        @if($discount->effective_to)
                                            → {{ \Carbon\Carbon::parse($discount->effective_to)->format('d M Y') }}
                                        @else
                                            → Ongoing
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.discount.show', $discount->id) }}" class="btn btn-info" title="View">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('admin.discount.edit', $discount->id) }}" class="btn btn-primary" title="Edit">
                                            <i class="ri-pencil-line"></i>
                                        </a>
                                        <button class="btn btn-danger delete-btn" data-id="{{ $discount->id }}" title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                 </td>
                             </tr>
                        @empty
                             <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="ri-inbox-line ri-2x d-block mb-2"></i>
                                    No discounts found. Click "Create Discount" to add one.
                                </td>
                             </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button class="btn btn-sm btn-danger" id="bulkDeleteBtn" style="display:none;">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
                </div>
                <div>
                    {{ $discounts->links() }}
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this discount?</p>
                <p class="text-muted small mb-0">This action cannot be undone. All assignments will also be removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;

document.addEventListener('DOMContentLoaded', function() {
    // Search and Filter
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    let searchTimeout;

    function applyFilters() {
        const search = searchInput?.value || '';
        const status = statusFilter?.value || '';
        const type = typeFilter?.value || '';
        let url = new URL(window.location.href);
        url.searchParams.set('search', search);
        url.searchParams.set('status', status);
        url.searchParams.set('type_id', type);
        window.location.href = url.toString();
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        });
    }
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);
    if (typeFilter) typeFilter.addEventListener('change', applyFilters);

    // Check All
    const checkAll = document.getElementById('checkAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => cb.checked = this.checked);
            bulkDeleteBtn.style.display = this.checked || Array.from(rowCheckboxes).some(cb => cb.checked) ? 'inline-flex' : 'none';
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const anyChecked = Array.from(rowCheckboxes).some(c => c.checked);
            bulkDeleteBtn.style.display = anyChecked ? 'inline-flex' : 'none';
            if (checkAll) checkAll.checked = Array.from(rowCheckboxes).every(c => c.checked);
        });
    });

    // Bulk Delete
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', async function() {
            const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            if (!selectedIds.length) return;

            Swal.fire({
                title: 'Delete Discounts?',
                text: `Are you sure you want to delete ${selectedIds.length} discount(s)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Yes, delete'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('{{ route("admin.discount.bulk-destroy") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                            body: JSON.stringify({ ids: selectedIds })
                        });
                        const data = await response.json();
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('Error!', 'Something went wrong', 'error');
                    }
                }
            });
        });
    }

    // Single Delete
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    let deleteId = null;

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteId = this.dataset.id;
            deleteModal.show();
        });
    });

    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function() {
        if (!deleteId) return;
        try {
            const response = await fetch(`/admin/discount/${deleteId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'Something went wrong', 'error');
        }
        deleteModal.hide();
    });
});
</script>
@endsection
