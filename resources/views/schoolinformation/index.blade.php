@extends('layouts.master')

@section('content')

<style>
    :root {
        --school-primary: #1e3a5f;
        --school-accent: #2563eb;
        --school-success: #16a34a;
        --school-warning: #d97706;
        --school-danger: #dc2626;
        --school-muted: #6b7280;
        --school-border: #e2e8f0;
        --school-bg: #f8fafc;
        --school-radius: 12px;
        --school-shadow: 0 2px 8px rgba(0,0,0,.08);
    }

    .school-hero {
        background: linear-gradient(135deg, var(--school-primary) 0%, #2563eb 60%, #4f46e5 100%);
        border-radius: var(--school-radius);
        padding: 28px 32px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .school-hero h1 {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        margin: 0 0 6px;
        position: relative;
    }
    .school-hero p {
        font-size: 13px;
        color: rgba(255,255,255,.75);
        margin: 0;
        position: relative;
    }

    .phone-input-group {
        background: #f8fafc;
        border: 1.5px solid var(--school-border);
        border-radius: 8px;
        padding: 12px;
    }
    .phone-input-item {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
        align-items: center;
    }
    .phone-input-item:last-child {
        margin-bottom: 0;
    }
    .phone-input-item .form-control {
        flex: 1;
    }
    .remove-phone-btn {
        background: none;
        border: none;
        color: var(--school-danger);
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
    }
    .remove-phone-btn:hover {
        background: #fee2e2;
    }
    .add-phone-btn {
        margin-top: 10px;
        width: 100%;
    }

    .stat-card {
        background: #fff;
        border: 1px solid var(--school-border);
        border-radius: var(--school-radius);
        padding: 18px 20px;
        transition: transform .15s, box-shadow .15s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--school-shadow);
    }
    .stat-card .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--school-primary);
    }
    .stat-card .stat-label {
        font-size: 12px;
        color: var(--school-muted);
        margin-top: 4px;
    }
    .stat-card .stat-icon {
        font-size: 32px;
        opacity: .12;
        float: right;
        margin-top: -8px;
    }

    .school-table th {
        background: var(--school-primary);
        color: #fff;
        padding: 12px 16px;
        font-weight: 600;
        font-size: 13px;
        white-space: nowrap;
    }
    .school-table td {
        padding: 12px 16px;
        vertical-align: middle;
        border-bottom: 1px solid var(--school-border);
        font-size: 13px;
    }
    .school-table tr:hover td {
        background: #eff6ff;
    }

    .phone-badge {
        display: inline-block;
        background: #f3f4f6;
        padding: 4px 10px;
        border-radius: 20px;
        margin: 2px;
        font-size: 12px;
    }

    .btn-subtle-primary {
        color: #2563eb;
        background-color: rgba(37,99,235,.1);
        border: none;
    }
    .btn-subtle-primary:hover {
        background-color: rgba(37,99,235,.2);
        color: #1d4ed8;
    }
    .btn-subtle-secondary {
        color: #6b7280;
        background-color: rgba(107,114,128,.1);
        border: none;
    }
    .btn-subtle-secondary:hover {
        background-color: rgba(107,114,128,.2);
        color: #4b5563;
    }
    .btn-subtle-danger {
        color: #dc2626;
        background-color: rgba(220,38,38,.1);
        border: none;
    }
    .btn-subtle-danger:hover {
        background-color: rgba(220,38,38,.2);
        color: #b91c1c;
    }
    .btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }

    .bulk-bar {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 10px 16px;
        display: none;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .bulk-bar.show {
        display: flex;
    }

    /* Image upload preview */
    .asset-preview-wrap {
        position: relative;
        display: inline-block;
        margin-top: 8px;
    }
    .asset-preview-wrap img {
        max-width: 100px;
        max-height: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--school-border);
    }
    .asset-remove-btn {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #dc2626;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 11px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="school-hero">
        <h1><i class="ri-school-line me-2"></i>{{ $pagetitle ?? 'School Information Management' }}</h1>
        <p>Manage school information, logos, stamps, and operational dates</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-building-line"></i></div>
                <div class="stat-value">{{ $data->total() }}</div>
                <div class="stat-label">Total Schools</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-checkbox-circle-line"></i></div>
                <div class="stat-value text-success">{{ $status_counts['Active'] ?? 0 }}</div>
                <div class="stat-label">Active Schools</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-close-circle-line"></i></div>
                <div class="stat-value text-secondary">{{ $status_counts['Inactive'] ?? 0 }}</div>
                <div class="stat-label">Inactive Schools</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-calendar-line"></i></div>
                <div class="stat-value" id="openedCount">—</div>
                <div class="stat-label">Total Times Opened</div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold" style="color:var(--school-primary)">
                        <i class="ri-bar-chart-2-line me-2"></i>Schools by Status
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="schoolsByStatusChart" data-status='@json($status_counts)' height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
            <div class="flex-grow-1">
                <h5 class="mb-0 fw-semibold" style="color:var(--school-primary)">
                    <i class="ri-list-check me-2"></i>Schools
                    <span class="badge bg-primary ms-2">{{ $data->total() }}</span>
                </h5>
            </div>
            <div class="flex-shrink-0">
                <div class="d-flex flex-wrap align-items-start gap-2">
                    <button class="btn btn-subtle-danger d-none" id="remove-actions">
                        <i class="ri-delete-bin-2-line"></i>
                    </button>
                    @can('Create schoolinformation')
                        <button type="button" class="btn btn-primary add-btn" onclick="openAddModal()">
                            <i class="ri-add-line me-1"></i> Add School
                        </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-xxl-3">
                    <div class="search-box">
                        <input type="text" class="form-control search" id="searchInput" placeholder="Search schools...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <select class="form-control" id="idStatus">
                        <option value="all">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-xxl-3 col-sm-6">
                    <select class="form-control" id="idEmail">
                        <option value="all">All Emails</option>
                        @foreach ($data as $school)
                            <option value="{{ $school->school_email }}">{{ $school->school_email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xxl-1 col-sm-6">
                    <button type="button" class="btn btn-secondary w-100" onclick="filterData()">
                        <i class="ri-filter-line me-1"></i> Filter
                    </button>
                </div>
            </div>

            <div class="bulk-bar" id="bulkBar">
                <i class="ri-checkbox-circle-line text-warning"></i>
                <span id="bulkCount">0</span> school(s) selected
                <button class="btn btn-sm btn-danger ms-auto" id="bulkDeleteBtn">
                    <i class="ri-delete-bin-line me-1"></i> Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <table class="table school-table align-middle table-nowrap mb-0">
                    <thead class="table-active">
                        <tr>
                            <th width="40"><div class="form-check"><input class="form-check-input" type="checkbox" id="checkAll"></div></th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone(s)</th>
                            <th>Status</th>
                            <th>Times Opened</th>
                            <th>Date Opened</th>
                            <th>Date Closed</th>
                            <th>Next Term</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $school)
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input row-checkbox" type="checkbox" value="{{ $school->id }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($school->getLogoUrlAttribute())
                                            <img src="{{ $school->getLogoUrlAttribute() }}" alt="logo" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="ri-building-line text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('admin.school-info.show', $school->id) }}" class="text-reset fw-medium">{{ $school->school_name }}</a>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $school->school_email }}</td>
                                <td>
                                    @php
                                        $phones = is_array($school->school_phones)
                                            ? $school->school_phones
                                            : json_decode($school->school_phones ?? '[]', true);
                                    @endphp
                                    @if(!empty($phones))
                                        @foreach($phones as $phone)
                                            <span class="phone-badge">{{ $phone }}</span>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $school->is_active ? 'success' : 'secondary' }}">
                                        {{ $school->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $school->no_of_times_school_opened }}</td>
                                <td>{{ $school->date_school_opened ? $school->date_school_opened->format('Y-m-d') : '-' }}</td>
                                <td>{{ $school->date_school_closed ? $school->date_school_closed->format('Y-m-d') : '-' }}</td>
                                <td>{{ $school->date_next_term_begins ? $school->date_next_term_begins->format('Y-m-d') : '-' }}</td>
                                <td>{{ $school->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <ul class="d-flex gap-2 list-unstyled mb-0">
                                        @can('View schoolinformation')
                                            <li>
                                                <a href="{{ route('admin.school-info.show', $school->id) }}" class="btn btn-subtle-primary btn-icon btn-sm" title="View">
                                                    <i class="ph-eye"></i>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('Update schoolinformation')
                                            <li>
                                                <button type="button"
                                                    class="btn btn-subtle-secondary btn-icon btn-sm"
                                                    onclick="openEditModal({{ $school->id }})"
                                                    title="Edit">
                                                    <i class="ph-pencil"></i>
                                                </button>
                                            </li>
                                        @endcan
                                        @can('Delete schoolinformation')
                                            <li>
                                                <button type="button"
                                                    class="btn btn-subtle-danger btn-icon btn-sm"
                                                    onclick="openDeleteModal({{ $school->id }}, '{{ addslashes($school->school_name) }}')"
                                                    title="Delete">
                                                    <i class="ph-trash"></i>
                                                </button>
                                            </li>
                                        @endcan
                                    </ul>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5">No schools found. Click "Add School" to create one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row mt-4 align-items-center">
                <div class="col-sm">
                    <div class="text-muted text-center text-sm-start">
                        Showing <span class="fw-semibold">{{ $data->count() }}</span> of
                        <span class="fw-semibold">{{ $data->total() }}</span> Results
                    </div>
                </div>
                <div class="col-sm-auto mt-3 mt-sm-0">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>

</div><!-- /container-fluid -->
</div><!-- /page-content -->
</div><!-- /main-content -->


{{-- ===================== ADD / EDIT MODAL ===================== --}}
<div class="modal fade" id="schoolModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalTitle" class="modal-title">Add School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{--
                IMPORTANT: Do NOT use method="POST" here.
                We submit via fetch() with FormData so the browser
                sets the correct multipart boundary automatically.
                A plain <form> tag with no action/method is fine.
            --}}
            <form id="schoolForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="schoolId" name="id">

                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="formErrors"></div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">School Name <span class="text-danger">*</span></label>
                                <input type="text" id="school_name" name="school_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" id="school_email" name="school_email" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea id="school_address" name="school_address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Numbers <span class="text-danger">*</span></label>
                        <div class="phone-input-group">
                            <div id="phoneInputsList">
                                <div class="phone-input-item">
                                    <input type="text" class="form-control phone-input" name="school_phones[]"
                                        placeholder="e.g., +234 123 456 7890" required>
                                    <button type="button" class="remove-phone-btn" style="display:none;" onclick="removePhoneInput(this)">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary add-phone-btn" onclick="addPhoneInput()">
                                <i class="ri-add-line me-1"></i>Add Another Phone Number
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" id="school_website" name="school_website" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Motto</label>
                                <input type="text" id="school_motto" name="school_motto" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Times Opened <span class="text-danger">*</span></label>
                                <input type="number" id="no_of_times_school_opened" name="no_of_times_school_opened"
                                    class="form-control" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Date School Opened</label>
                                <input type="date" id="date_school_opened" name="date_school_opened" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Date School Closed</label>
                                <input type="date" id="date_school_closed" name="date_school_closed" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Next Term Begins</label>
                                <input type="date" id="date_next_term_begins" name="date_next_term_begins" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 pt-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="is_active">Set as Active School</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-semibold mb-3">School Assets</h6>
                    <p class="text-muted small mb-3">
                        <i class="ri-information-line me-1"></i>
                        Accepted formats: JPEG, PNG, JPG, WEBP. Maximum size: 5MB each.
                    </p>

                    <div class="row">
                        {{-- School Logo --}}
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">School Logo</h6>
                                </div>
                                <div class="card-body">
                                    <input type="file" id="school_logo" name="school_logo"
                                        class="form-control mb-1" accept="image/jpeg,image/png,image/jpg,image/webp">
                                    <small class="text-muted d-block mb-2">Recommended: 300×300px</small>
                                    <div id="school-logo-preview"></div>
                                </div>
                            </div>
                        </div>

                        {{-- App Logo --}}
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">App Logo</h6>
                                </div>
                                <div class="card-body">
                                    <input type="file" id="app_logo" name="app_logo"
                                        class="form-control mb-1" accept="image/jpeg,image/png,image/jpg,image/webp">
                                    <small class="text-muted d-block mb-2">Recommended: 200×200px</small>
                                    <div id="app-logo-preview"></div>
                                </div>
                            </div>
                        </div>

                        {{-- School Stamp --}}
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">School Stamp</h6>
                                </div>
                                <div class="card-body">
                                    <input type="file" id="school_stamp" name="school_stamp"
                                        class="form-control mb-1" accept="image/jpeg,image/png,image/jpg,image/webp">
                                    <small class="text-muted d-block mb-2">For official documents</small>
                                    <div id="school-stamp-preview"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /modal-body -->

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save School</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ===================== DELETE MODAL ===================== --}}
<div id="deleteRecordModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-md-5">
                <div class="text-center">
                    <div class="text-danger">
                        <i class="bi bi-trash display-4"></i>
                    </div>
                    <div class="mt-4">
                        <h3 class="mb-2">Are you sure?</h3>
                        <p class="text-muted fs-lg mx-3 mb-0">
                            Are you sure you want to remove <strong id="deleteItemName"></strong>?
                        </p>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                    <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn w-sm btn-danger" id="confirmDeleteBtn">Yes, Delete It!</button>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ─── Globals ─────────────────────────────────────────────────────────────────
let currentDeleteId = null;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// ─── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initStatusChart();
    initEventListeners();
    updateOpenedCount();
    initImagePreviews();

    // Open edit modal if redirected from show page
    const editId = sessionStorage.getItem('editSchoolId');
    if (editId) {
        sessionStorage.removeItem('editSchoolId');
        setTimeout(() => openEditModal(editId), 500);
    }
});

// ─── Chart ────────────────────────────────────────────────────────────────────
function initStatusChart() {
    const ctx = document.getElementById('schoolsByStatusChart');
    if (!ctx) return;
    const statusData = JSON.parse(ctx.getAttribute('data-status') || '{"Active":0,"Inactive":0}');
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                label: 'Number of Schools',
                data: Object.values(statusData),
                backgroundColor: ['#16a34a', '#6b7280'],
                borderRadius: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
    });
}

// ─── Stat helpers ─────────────────────────────────────────────────────────────
function updateOpenedCount() {
    let total = 0;
    document.querySelectorAll('table tbody tr td:nth-child(6)').forEach(cell => {
        const val = parseInt(cell.innerText, 10);
        if (!isNaN(val)) total += val;
    });
    const el = document.getElementById('openedCount');
    if (el) el.textContent = total;
}

// ─── Phone inputs ─────────────────────────────────────────────────────────────
function refreshRemoveButtons() {
    const btns = document.querySelectorAll('#phoneInputsList .remove-phone-btn');
    btns.forEach(btn => {
        btn.style.display = btns.length > 1 ? 'inline-flex' : 'none';
    });
}

function addPhoneInput(value = '') {
    const container = document.getElementById('phoneInputsList');
    const div = document.createElement('div');
    div.className = 'phone-input-item';
    div.innerHTML = `
        <input type="text" class="form-control phone-input" name="school_phones[]"
               placeholder="e.g., +234 123 456 7890" value="${escapeHtml(value)}" required>
        <button type="button" class="remove-phone-btn" onclick="removePhoneInput(this)">
            <i class="ri-delete-bin-line"></i>
        </button>`;
    container.appendChild(div);
    refreshRemoveButtons();
}

function removePhoneInput(btn) {
    if (document.querySelectorAll('#phoneInputsList .phone-input-item').length <= 1) return;
    btn.closest('.phone-input-item').remove();
    refreshRemoveButtons();
}

function getPhonesArray() {
    return Array.from(document.querySelectorAll('#phoneInputsList .phone-input'))
        .map(i => i.value.trim())
        .filter(Boolean);
}

function setPhonesArray(phones) {
    document.getElementById('phoneInputsList').innerHTML = '';
    if (!phones || phones.length === 0) {
        addPhoneInput('');
    } else {
        phones.forEach(p => addPhoneInput(p));
    }
}

// ─── Image preview helpers ────────────────────────────────────────────────────
function initImagePreviews() {
    const fields = [
        { inputId: 'school_logo',  previewId: 'school-logo-preview' },
        { inputId: 'app_logo',     previewId: 'app-logo-preview'    },
        { inputId: 'school_stamp', previewId: 'school-stamp-preview' },
    ];
    fields.forEach(({ inputId, previewId }) => {
        document.getElementById(inputId)?.addEventListener('change', function () {
            showLocalPreview(this, previewId);
        });
    });
}

/**
 * Show a local file preview inside the given container div.
 * Renders a thumbnail with an ×-button that clears the file input.
 */
function showLocalPreview(input, previewId) {
    const container = document.getElementById(previewId);
    if (!container) return;
    if (!input.files || !input.files[0]) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        container.innerHTML = `
            <div class="asset-preview-wrap">
                <img src="${e.target.result}" alt="preview">
                <button type="button" class="asset-remove-btn"
                        onclick="clearFileInput('${input.id}','${previewId}')"
                        title="Remove">&times;</button>
            </div>`;
    };
    reader.readAsDataURL(input.files[0]);
}

/**
 * Show a URL-based preview (existing server image) inside the container.
 */
function showUrlPreview(url, previewId, inputId) {
    const container = document.getElementById(previewId);
    if (!container || !url) return;
    container.innerHTML = `
        <div class="asset-preview-wrap">
            <img src="${url}" alt="current image">
            <button type="button" class="asset-remove-btn"
                    onclick="clearFileInput('${inputId}','${previewId}')"
                    title="Remove">&times;</button>
        </div>`;
}

function clearFileInput(inputId, previewId) {
    const input = document.getElementById(inputId);
    if (input) input.value = '';
    const container = document.getElementById(previewId);
    if (container) container.innerHTML = '';
}

function clearAllPreviews() {
    ['school-logo-preview', 'app-logo-preview', 'school-stamp-preview'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '';
    });
    ['school_logo', 'app_logo', 'school_stamp'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
}

// ─── Utility ──────────────────────────────────────────────────────────────────
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ─── Modal: Add ──────────────────────────────────────────────────────────────
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add School';
    document.getElementById('schoolForm').reset();
    document.getElementById('schoolId').value = '';
    document.getElementById('formErrors').classList.add('d-none');
    setPhonesArray(['']);
    clearAllPreviews();
    new bootstrap.Modal(document.getElementById('schoolModal')).show();
}

// ─── Modal: Edit ──────────────────────────────────────────────────────────────
function openEditModal(id) {
    document.getElementById('modalTitle').textContent = 'Edit School';
    document.getElementById('schoolId').value = id;
    document.getElementById('formErrors').classList.add('d-none');
    clearAllPreviews();

    const saveBtn = document.getElementById('saveBtn');
    const origHtml = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';

    fetch(`/school-info/${id}/edit-json`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (!data.success) throw new Error(data.message || 'Failed to load school data');

        const s = data.school;
        document.getElementById('school_name').value               = s.school_name               || '';
        document.getElementById('school_email').value              = s.school_email              || '';
        document.getElementById('school_address').value            = s.school_address            || '';
        document.getElementById('school_website').value            = s.school_website            || '';
        document.getElementById('school_motto').value              = s.school_motto              || '';
        document.getElementById('no_of_times_school_opened').value = s.no_of_times_school_opened || 0;
        document.getElementById('date_school_opened').value        = s.date_school_opened        || '';
        document.getElementById('date_school_closed').value        = s.date_school_closed        || '';
        document.getElementById('date_next_term_begins').value     = s.date_next_term_begins     || '';
        document.getElementById('is_active').checked               = !!s.is_active;

        setPhonesArray(s.school_phones && s.school_phones.length ? s.school_phones : ['']);

        // Show existing images so user knows what is already uploaded
        if (s.logo_url)     showUrlPreview(s.logo_url,     'school-logo-preview',  'school_logo');
        if (s.app_logo_url) showUrlPreview(s.app_logo_url, 'app-logo-preview',     'app_logo');
        if (s.stamp_url)    showUrlPreview(s.stamp_url,    'school-stamp-preview', 'school_stamp');

        new bootstrap.Modal(document.getElementById('schoolModal')).show();
    })
    .catch(err => {
        console.error('Edit load error:', err);
        Swal.fire('Error', err.message || 'Network error. Please try again.', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = origHtml;
    });
}

// ─── Modal: Delete ────────────────────────────────────────────────────────────
function openDeleteModal(id, name) {
    currentDeleteId = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteRecordModal')).show();
}

// ─── Form submit (Create / Update) ───────────────────────────────────────────
document.getElementById('schoolForm')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const schoolId = document.getElementById('schoolId').value.trim();

    // Build FormData manually so we control exactly what goes in
    const formData = new FormData();
    formData.append('_token', CSRF_TOKEN);
    formData.append('school_name',               document.getElementById('school_name').value);
    formData.append('school_address',            document.getElementById('school_address').value);
    formData.append('school_email',              document.getElementById('school_email').value);
    formData.append('school_website',            document.getElementById('school_website').value || '');
    formData.append('school_motto',              document.getElementById('school_motto').value   || '');
    formData.append('no_of_times_school_opened', document.getElementById('no_of_times_school_opened').value);
    formData.append('date_school_opened',        document.getElementById('date_school_opened').value  || '');
    formData.append('date_school_closed',        document.getElementById('date_school_closed').value  || '');
    formData.append('date_next_term_begins',     document.getElementById('date_next_term_begins').value || '');
    formData.append('is_active',                 document.getElementById('is_active').checked ? '1' : '0');

    // Phone numbers
    getPhonesArray().forEach(p => formData.append('school_phones[]', p));

    // ── FILE UPLOADS ──────────────────────────────────────────────────────────
    // We check .files[0] directly; do NOT set Content-Type header — the browser
    // handles the multipart boundary automatically when body is FormData.
    const logoFile  = document.getElementById('school_logo').files[0];
    const appFile   = document.getElementById('app_logo').files[0];
    const stampFile = document.getElementById('school_stamp').files[0];

    if (logoFile)  formData.append('school_logo',  logoFile,  logoFile.name);
    if (appFile)   formData.append('app_logo',     appFile,   appFile.name);
    if (stampFile) formData.append('school_stamp', stampFile, stampFile.name);

    // For Laravel PUT/PATCH method-spoofing
    const url = schoolId ? `/school-info/${schoolId}` : '/school-info';
    if (schoolId) formData.append('_method', 'PUT');

    // ── Debug (remove in production) ─────────────────────────────────────────
    console.group('Form submit → ' + url);
    for (const [k, v] of formData.entries()) {
        console.log(k, v instanceof File ? `File(${v.name}, ${v.size}B, ${v.type})` : v);
    }
    console.groupEnd();
    // ─────────────────────────────────────────────────────────────────────────

    const btn = document.getElementById('saveBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    // DO NOT set Content-Type header when sending FormData with files!
    fetch(url, {
        method: 'POST',          // Always POST; Laravel reads _method for override
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            // ← NO 'Content-Type' here. Browser sets it with the correct boundary.
        },
    })
    .then(res => {
        // Catch non-JSON responses (e.g. 419 CSRF, 500 server error HTML page)
        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            return res.text().then(text => {
                throw new Error(`Server returned non-JSON (${res.status}): ${text.substring(0, 200)}`);
            });
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false,
            });
            setTimeout(() => window.location.reload(), 1500);
        } else {
            // Show validation / server errors inside the modal
            let html = '<ul class="mb-0">';
            if (data.errors) {
                Object.values(data.errors).flat().forEach(err => {
                    html += `<li>${err}</li>`;
                });
            } else {
                html += `<li>${data.message || 'Something went wrong'}</li>`;
            }
            html += '</ul>';
            const errEl = document.getElementById('formErrors');
            errEl.innerHTML = html;
            errEl.classList.remove('d-none');
            errEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    })
    .catch(err => {
        console.error('Submit error:', err);
        Swal.fire('Error', err.message || 'Network error. Please check the console.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    });
});

// ─── Confirm delete ───────────────────────────────────────────────────────────
document.getElementById('confirmDeleteBtn')?.addEventListener('click', function () {
    if (!currentDeleteId) return;

    const btn = this;
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

    fetch(`/school-info/${currentDeleteId}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/json',
        },
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message, timer: 2000, showConfirmButton: false });
            setTimeout(() => window.location.reload(), 1500);
        } else {
            Swal.fire('Error', data.message || 'Delete failed', 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Network error', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal'))?.hide();
        currentDeleteId = null;
    });
});

// ─── Bulk delete ──────────────────────────────────────────────────────────────
function bulkDelete() {
    const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) return;

    Swal.fire({
        title: `Delete ${ids.length} school(s)?`,
        text: 'This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, delete',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('/school-info/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ ids }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Deleted!', data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                Swal.fire('Error', data.message || 'Delete failed', 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Network error', 'error'));
    });
}

// ─── Filter ───────────────────────────────────────────────────────────────────
function filterData() {
    const search = (document.getElementById('searchInput')?.value || '').toLowerCase();
    const status = document.getElementById('idStatus')?.value  || 'all';
    const email  = document.getElementById('idEmail')?.value   || 'all';

    document.querySelectorAll('table tbody tr').forEach(row => {
        const name      = (row.cells[1]?.innerText || '').toLowerCase();
        const emailText = (row.cells[2]?.innerText || '').toLowerCase();
        const statusText = row.cells[4]?.innerText.trim() || '';
        const rowEmail  = row.cells[2]?.innerText.trim()  || '';

        const match = (name.includes(search) || emailText.includes(search)) &&
                      (status === 'all' || statusText === status) &&
                      (email  === 'all' || rowEmail  === email);

        row.style.display = match ? '' : 'none';
    });
}

// ─── Event listeners ──────────────────────────────────────────────────────────
function initEventListeners() {
    // Edit/Delete buttons use onclick attributes directly on the elements,
    // so no addEventListener needed for those.

    document.getElementById('checkAll')?.addEventListener('change', function () {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkBar();
    });

    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });

    document.getElementById('bulkDeleteBtn')?.addEventListener('click', bulkDelete);
}

function updateBulkBar() {
    const count   = document.querySelectorAll('.row-checkbox:checked').length;
    const bar     = document.getElementById('bulkBar');
    const actions = document.getElementById('remove-actions');

    if (count > 0) {
        bar.classList.add('show');
        actions?.classList.remove('d-none');
        document.getElementById('bulkCount').textContent = count;
    } else {
        bar.classList.remove('show');
        actions?.classList.add('d-none');
    }
}

// ─── Global exports ───────────────────────────────────────────────────────────
window.filterData       = filterData;
window.addPhoneInput    = addPhoneInput;
window.removePhoneInput = removePhoneInput;
window.openAddModal     = openAddModal;
window.openEditModal    = openEditModal;
window.openDeleteModal  = openDeleteModal;
window.clearFileInput   = clearFileInput;
</script>

@endsection
