{{-- resources/views/admin/scholarship/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sch-primary: #1e3a5f;
    --sch-accent: #2563eb;
    --sch-success: #16a34a;
    --sch-warning: #d97706;
    --sch-danger: #dc2626;
    --sch-muted: #6b7280;
    --sch-border: #e2e8f0;
    --sch-bg: #f8fafc;
    --sch-radius: 12px;
    --sch-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.sch-hero {
    background: linear-gradient(135deg, var(--sch-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sch-radius); padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.sch-hero::before {
    content: ''; position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px; background: rgba(255,255,255,.06); border-radius: 50%;
}
.sch-hero h1  { font-size: 22px; font-weight: 700; color: white; margin: 0 0 6px; }
.sch-hero p   { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; }

.stat-card { background: white; border: 1px solid var(--sch-border); border-radius: var(--sch-radius); padding: 18px 20px; transition: transform .15s, box-shadow .15s; }
.stat-card:hover         { transform: translateY(-2px); box-shadow: var(--sch-shadow); }
.stat-card .stat-value   { font-size: 28px; font-weight: 700; color: var(--sch-primary); }
.stat-card .stat-label   { font-size: 12px; color: var(--sch-muted); margin-top: 4px; }
.stat-card .stat-icon    { font-size: 32px; opacity: .12; float: right; margin-top: -8px; }

.sch-table th { background: var(--sch-primary); color: white; padding: 12px 16px; font-weight: 600; }
.sch-table td { padding: 12px 16px; vertical-align: middle; border-bottom: 1px solid var(--sch-border); }
.sch-table tr:hover { background: #eff6ff; }

.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
.status-active    { background: #dcfce7; color: #16a34a; }
.status-draft     { background: #fef3c7; color: #d97706; }
.status-expired   { background: #fee2e2; color: #dc2626; }
.status-suspended { background: #f3f4f6; color: #6b7280; }

.filter-bar { background: white; border: 1px solid var(--sch-border); border-radius: var(--sch-radius); padding: 16px 20px; margin-bottom: 20px; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="sch-hero">
        <h1><i class="ri-graduation-cap-line me-2"></i>{{ $pagetitle }}</h1>
        <p>Manage scholarships, assign to students, track budgets and monitor scholarship impact on school revenue.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-graduation-cap-line"></i></div>
                <div class="stat-value">{{ $totalScholarships ?? 0 }}</div>
                <div class="stat-label">Total Scholarships</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-checkbox-circle-line"></i></div>
                <div class="stat-value text-success">{{ $activeScholarships ?? 0 }}</div>
                <div class="stat-label">Active Scholarships</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-money-dollar-circle-line"></i></div>
                <div class="stat-value text-warning">₦{{ number_format($totalAwarded ?? 0, 2) }}</div>
                <div class="stat-label">Total Awarded</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-star-line"></i></div>
                <div class="stat-value text-primary">{{ $totalStudents ?? 0 }}</div>
                <div class="stat-label">Beneficiaries</div>
            </div>
        </div>
    </div>

    <div class="filter-bar">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="search-box">
                    <input type="text" class="form-control search" id="searchInput"
                           placeholder="Search by title or code..."
                           value="{{ request('search') }}">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active"    {{ request('status') == 'active'    ? 'selected' : '' }}>Active</option>
                    <option value="draft"     {{ request('status') == 'draft'     ? 'selected' : '' }}>Draft</option>
                    <option value="expired"   {{ request('status') == 'expired'   ? 'selected' : '' }}>Expired</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="typeFilter">
                    <option value="">All Types</option>
                    @foreach($scholarshipTypes ?? [] as $type)
                        <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 text-end">
                <a href="{{ route('admin.scholarship.create') }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i>Create Scholarship
                </a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold" style="color: var(--sch-primary);">
                <i class="ri-list-check me-2"></i>All Scholarships
                <span class="badge bg-primary ms-2">{{ $scholarships->total() ?? 0 }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table sch-table mb-0">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="checkAll"></th>
                            <th>#</th>
                            <th>Scholarship No.</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th>Effective Period</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @php $i = ($scholarships->currentPage() - 1) * $scholarships->perPage(); @endphp
                        @forelse($scholarships as $scholarship)
                            @php
                                $budgetUsage = $scholarship->budget_amount > 0
                                    ? round(($scholarship->utilized_amount / $scholarship->budget_amount) * 100, 1)
                                    : 0;
                            @endphp
                            <tr data-id="{{ $scholarship->id }}">
                                <td><input type="checkbox" class="row-checkbox" value="{{ $scholarship->id }}"></td>
                                <td>{{ ++$i }}</td>
                                <td><code>{{ $scholarship->scholarship_no }}</code></td>
                                <td class="fw-semibold">{{ $scholarship->title }}</td>
                                <td>{{ $scholarship->type->name ?? 'N/A' }}</td>
                                <td>
                                    @if($scholarship->value_type == 'percentage')
                                        {{ $scholarship->value }}%
                                    @else
                                        ₦{{ number_format($scholarship->value, 2) }}
                                    @endif
                                </td>
                                <td>
                                    @if($scholarship->budget_amount)
                                        ₦{{ number_format($scholarship->budget_amount, 2) }}
                                        <div class="progress mt-1" style="height:3px;">
                                            <div class="progress-bar bg-warning" style="width:{{ $budgetUsage }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-muted">Unlimited</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $scholarship->status }}">
                                        <i class="ri-{{ $scholarship->status == 'active' ? 'check-circle-line' : ($scholarship->status == 'draft' ? 'edit-line' : 'close-circle-line') }}"></i>
                                        {{ ucfirst($scholarship->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        {{ \Carbon\Carbon::parse($scholarship->effective_from)->format('d M Y') }}
                                        @if($scholarship->effective_to)
                                            → {{ \Carbon\Carbon::parse($scholarship->effective_to)->format('d M Y') }}
                                        @else
                                            → Ongoing
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.scholarship.show', $scholarship->id) }}" class="btn btn-info" title="View">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('admin.scholarship.edit', $scholarship->id) }}" class="btn btn-primary" title="Edit">
                                            <i class="ri-pencil-line"></i>
                                        </a>
                                        <button class="btn btn-danger delete-btn" data-id="{{ $scholarship->id }}" title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="ri-inbox-line ri-2x d-block mb-2"></i>
                                    No scholarships found. Click "Create Scholarship" to add one.
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
                <div>{{ $scholarships->links() }}</div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this scholarship?</p>
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

document.addEventListener('DOMContentLoaded', function () {

    // ── Search & Filters ────────────────────────────────────────────────
    const searchInput  = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter   = document.getElementById('typeFilter');
    let searchTimeout;

    function applyFilters() {
        const url = new URL(window.location.href);
        url.searchParams.set('search',  searchInput?.value  || '');
        url.searchParams.set('status',  statusFilter?.value || '');
        url.searchParams.set('type_id', typeFilter?.value   || '');
        window.location.href = url.toString();
    }

    searchInput?.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });
    statusFilter?.addEventListener('change', applyFilters);
    typeFilter?.addEventListener('change',   applyFilters);

    // ── Check All / Bulk ────────────────────────────────────────────────
    const checkAll       = document.getElementById('checkAll');
    const rowCheckboxes  = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn  = document.getElementById('bulkDeleteBtn');

    checkAll?.addEventListener('change', function () {
        rowCheckboxes.forEach(cb => cb.checked = this.checked);
        bulkDeleteBtn.style.display = this.checked ? 'inline-flex' : 'none';
    });

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const anyChecked = Array.from(rowCheckboxes).some(c => c.checked);
            bulkDeleteBtn.style.display = anyChecked ? 'inline-flex' : 'none';
            if (checkAll) checkAll.checked = Array.from(rowCheckboxes).every(c => c.checked);
        });
    });

    // ── Bulk Delete ─────────────────────────────────────────────────────
    bulkDeleteBtn?.addEventListener('click', function () {
        const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
        if (!selectedIds.length) return;

        Swal.fire({
            title: 'Delete Scholarships?',
            text:  `Are you sure you want to delete ${selectedIds.length} scholarship(s)?`,
            icon:  'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText:  'Yes, delete',
        }).then(async result => {
            if (!result.isConfirmed) return;
            try {
                const response = await fetch('{{ route("admin.scholarship.bulk-destroy") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ ids: selectedIds }),
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error!', 'Something went wrong', 'error');
            }
        });
    });

    // ── Single Delete ───────────────────────────────────────────────────
    const deleteModal   = new bootstrap.Modal(document.getElementById('deleteModal'));
    let deleteId        = null;

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            deleteId = this.dataset.id;
            deleteModal.show();
        });
    });

    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function () {
        if (!deleteId) return;
        try {
            const response = await fetch(`/admin/scholarship/${deleteId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN':     CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error!', 'Something went wrong', 'error');
        }
        deleteModal.hide();
    });
});
</script>
@endsection
