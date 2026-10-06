{{-- resources/views/attendance/admin/device-mappings.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
/* ── Complete redesign with subjectscoresheet patterns ── */
:root {
    --dm-primary: #1e3a5f;
    --dm-accent:  #2563eb;
    --dm-success: #16a34a;
    --dm-warning: #d97706;
    --dm-danger:  #dc2626;
    --dm-muted:   #6b7280;
    --dm-border:  #e2e8f0;
    --dm-bg:      #f8fafc;
    --dm-radius:  10px;
    --dm-shadow:  0 1px 4px rgba(0,0,0,.08);
}

/* Hero - same as scoresheet */
.dm-hero {
    background: linear-gradient(135deg, var(--dm-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--dm-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.dm-hero::before {
    content:'';
    position:absolute;
    top:-60px;
    right:-60px;
    width:220px;
    height:220px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
}
.dm-hero::after {
    content:'';
    position:absolute;
    bottom:-80px;
    left:-30px;
    width:260px;
    height:260px;
    background:rgba(255,255,255,.03);
    border-radius:50%;
}
.dm-hero h1 {
    font-size:22px;
    font-weight:700;
    color:#fff;
    margin:0 0 6px;
    position:relative;
}
.dm-hero p {
    font-size:13px;
    color:rgba(255,255,255,.75);
    margin:0;
    position:relative;
}
.dm-hero .btn-light {
    position:relative;
}

/* Stat cards - matching scoresheet exactly */
.dm-stat-card {
    background:#fff;
    border:1px solid var(--dm-border);
    border-radius:var(--dm-radius);
    padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.dm-stat-card:hover {
    transform:translateY(-2px);
    box-shadow:var(--dm-shadow);
}
.dm-stat-card .stat-value {
    font-size:28px;
    font-weight:700;
    color:var(--dm-primary);
}
.dm-stat-card .stat-label {
    font-size:12px;
    color:var(--dm-muted);
    margin-top:4px;
}
.dm-stat-card .stat-icon {
    font-size:32px;
    opacity:.12;
    float:right;
    margin-top:-8px;
}

/* Filter card */
.dm-filter-card {
    background:#fff;
    border:1px solid var(--dm-border);
    border-radius:var(--dm-radius);
    padding:20px 24px;
    margin-bottom:24px;
    box-shadow:var(--dm-shadow);
}

/* Bill table - consistent with scoresheet */
.dm-table th {
    background:var(--dm-primary);
    color:#fff;
    padding:12px 16px;
    font-weight:600;
    font-size:13px;
    white-space:nowrap;
    border:none;
}
.dm-table td {
    padding:12px 16px;
    vertical-align:middle;
    border-bottom:1px solid var(--dm-border);
    font-size:13px;
}
.dm-table tr:hover td {
    background:#eff6ff;
}

.dm-card {
    background:#fff;
    border:1px solid var(--dm-border);
    border-radius:var(--dm-radius);
    box-shadow:var(--dm-shadow);
    overflow:hidden;
}
.dm-card .card-header {
    background:#fff;
    border-bottom:1px solid var(--dm-border);
    padding:16px 20px;
    font-weight:700;
    font-size:14px;
    color:var(--dm-primary);
}
.dm-card .card-body {
    padding:20px;
}

/* Avatar styles */
.dm-avatar {
    width:32px;
    height:32px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid var(--dm-border);
}
.dm-avatar-fallback {
    width:32px;
    height:32px;
    border-radius:50%;
    background:#e2e8f0;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:11px;
    color:#64748b;
    font-weight:600;
}

/* Step bar - matching mass student modal */
.dm-step {
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12px;
    font-weight:600;
    color:#94a3b8;
}
.dm-step.active {
    color:var(--dm-accent);
}
.dm-step.done {
    color:var(--dm-success);
}
.dm-step-circle {
    width:28px;
    height:28px;
    border-radius:50%;
    background:#e2e8f0;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:700;
}
.dm-step.active .dm-step-circle {
    background:var(--dm-accent);
    color:#fff;
    box-shadow:0 0 0 3px rgba(37,99,235,.2);
}
.dm-step.done .dm-step-circle {
    background:var(--dm-success);
    color:#fff;
}
.dm-step-line {
    flex:1;
    height:2px;
    background:#e2e8f0;
    margin:0 12px;
    max-width:80px;
}

/* Form controls - labels above inputs */
.dm-form-label {
    font-size:13px;
    font-weight:600;
    color:#374151;
    margin-bottom:6px;
    display:block;
}
.dm-form-control, .dm-form-select {
    border:1.5px solid var(--dm-border);
    border-radius:8px;
    font-size:13px;
    padding:9px 14px;
    transition:border .15s;
    width:100%;
}
.dm-form-control:focus, .dm-form-select:focus {
    border-color:var(--dm-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
    outline:none;
}
.dm-form-control-sm, .dm-form-select-sm {
    padding:6px 10px;
    border-radius:7px;
}

/* Toast animations - matching scoresheet */
.dm-toast {
    position:fixed;
    bottom:20px;
    right:20px;
    z-index:99999;
    padding:14px 20px;
    border-radius:10px;
    background:#fff;
    box-shadow:0 4px 20px rgba(0,0,0,.12);
    font-weight:600;
    font-size:13px;
    animation: dmToastIn .3s ease;
}
@keyframes dmToastIn {
    from { opacity:0; transform:translateY(20px); }
    to { opacity:1; transform:translateY(0); }
}

/* Import result */
.dm-import-result .text-success {
    color: var(--dm-success) !important;
    font-weight:600;
}
.dm-import-result .text-danger {
    color: var(--dm-danger) !important;
    font-weight:600;
}

/* Select2 overrides */
.select2-container .select2-selection--single,
.select2-container .select2-selection--multiple {
    border:1.5px solid var(--dm-border) !important;
    border-radius:8px !important;
    min-height:36px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height:34px;
    font-size:13px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height:34px;
}
.select2-dropdown {
    border-color: var(--dm-border) !important;
}
.select2-container--open {
    z-index: 1090;
}

/* SweetAlert2 above modals */
.swal2-container {
    z-index: 2000 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .dm-hero {
        padding:20px;
    }
    .dm-hero h1 {
        font-size:18px;
    }
    .dm-stat-card .stat-value {
        font-size:22px;
    }
    .dm-table th, .dm-table td {
        padding:8px 10px;
        font-size:12px;
    }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- ══ HERO ═══════════════════════════════════════════════════════════ --}}
    <div class="dm-hero d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="ri-fingerprint-line me-2"></i>Device PIN Mappings</h1>
            <p>Link biometric device PINs to students and staff, import in bulk, or resolve unmapped punches.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addMappingModal">
                <i class="ri-user-add-line me-1"></i>Add Mapping
            </button>
            <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                <i class="ri-group-line me-1"></i>Bulk Assign
            </button>
            @if($unmappedCount > 0)
            <a href="{{ route('device-mappings.unmapped') }}" class="btn btn-light btn-sm fw-semibold">
                <i class="ri-alert-line me-1"></i>{{ $unmappedCount }} Unmapped PIN(s)
            </a>
            @endif
        </div>
    </div>

    {{-- ══ STAT CARDS ════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="dm-stat-card">
                <div class="stat-icon"><i class="ri-links-line"></i></div>
                <div class="stat-value">{{ $mappings->total() }}</div>
                <div class="stat-label">Total Mappings</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dm-stat-card">
                <div class="stat-icon"><i class="ri-graduation-cap-line"></i></div>
                <div class="stat-value text-primary">{{ $studentCount }}</div>
                <div class="stat-label">Student Mappings</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dm-stat-card">
                <div class="stat-icon"><i class="ri-briefcase-line"></i></div>
                <div class="stat-value text-info">{{ $staffCount }}</div>
                <div class="stat-label">Staff Mappings</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dm-stat-card">
                <div class="stat-icon"><i class="ri-alert-line"></i></div>
                <div class="stat-value text-warning">{{ $unmappedCount }}</div>
                <div class="stat-label">Unmapped PINs</div>
            </div>
        </div>
    </div>

    {{-- ══ BULK IMPORT ══════════════════════════════════════════════════ --}}
    <div class="dm-card mb-4">
        <div class="card-header"><i class="ri-file-upload-line me-2"></i>Bulk Import (CSV)</div>
        <div class="card-body">
            <p class="text-muted" style="font-size:12px;">Columns: <code>device_pin, person_type, identifier</code>. identifier = admission number for students, staff ID for staff.</p>
            <form id="bulkImportForm" enctype="multipart/form-data" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="dm-form-label">Device Serial</label>
                    <input type="text" name="device_serial" class="dm-form-control dm-form-control-sm" placeholder="e.g. PKD7022588362" required>
                </div>
                <div class="col-md-4">
                    <label class="dm-form-label">CSV File</label>
                    <input type="file" name="csv_file" class="dm-form-control dm-form-control-sm" accept=".csv" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100" type="submit"><i class="ri-upload-2-line me-1"></i>Import</button>
                </div>
            </form>
            <div id="importResult" class="dm-import-result mt-2" style="font-size:12px;"></div>
        </div>
    </div>

    {{-- ── Add Single Mapping (modal) ─────────────────────────────────── --}}
    <div class="modal fade" id="addMappingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius:var(--dm-radius);border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--dm-border);">
                    <h5 class="modal-title fw-bold" style="color:var(--dm-primary);"><i class="ri-user-add-line me-2"></i>Add Single Mapping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addMappingForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="dm-form-label">Device Serial</label>
                                <input type="text" name="device_serial" class="dm-form-control dm-form-control-sm" placeholder="Device Serial" required>
                            </div>
                            <div class="col-6">
                                <label class="dm-form-label">Device PIN</label>
                                <input type="number" name="device_pin" class="dm-form-control dm-form-control-sm" placeholder="Device PIN" required>
                            </div>
                            <div class="col-6">
                                <label class="dm-form-label">Person Type</label>
                                <select name="person_type" id="personType" class="dm-form-select dm-form-select-sm" required>
                                    <option value="student">Student</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="dm-form-label">Person</label>
                                <select name="person_id" id="personSelect" style="width:100%;" required></select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--dm-border);">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success btn-sm" type="submit"><i class="ri-add-line me-1"></i>Add Mapping</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Bulk Assign (checkbox table, mirrors Mass Student modal pattern) ── --}}
    <div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#4f46e5 100%);border:none;padding:20px 24px;position:relative;">
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0"><i class="ri-group-line me-2"></i>Bulk Assign — Multiple People</h5>
                        <p class="mb-0" style="color:rgba(255,255,255,.72);font-size:12px;">Select everyone at once, then assign sequential device PINs</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="position:absolute;top:18px;right:20px;"></button>
                </div>

                {{-- Step bar --}}
                <div style="display:flex;align-items:center;justify-content:center;padding:14px 24px;background:#f1f5f9;border-bottom:1px solid var(--dm-border);">
                    <div class="dm-step active" id="dmStepBar1"><div class="dm-step-circle">1</div><span>Select People</span></div>
                    <div class="dm-step-line"></div>
                    <div class="dm-step" id="dmStepBar2"><div class="dm-step-circle">2</div><span>Assign PINs</span></div>
                </div>

                <div class="modal-body" style="padding:20px 24px;background:#f8fafc;max-height:70vh;overflow-y:auto;">

                    {{-- STEP 1: filter + checkbox table --}}
                    <div id="dmStep1">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="dm-form-label">Type</label>
                                <select id="dmType" class="dm-form-select dm-form-select-sm">
                                    <option value="student">Student</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="dm-form-label">Search</label>
                                <input type="text" id="dmSearch" class="dm-form-control dm-form-control-sm" placeholder="Filter by name, ID, department, class…">
                            </div>
                        </div>

                        <div class="dm-card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="ri-list-check-2 me-2"></i>People</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary" id="dmSelectedCount">0 selected</span>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="dmSelectAll">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="dmClearAll">Clear</button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div style="max-height:340px;overflow-y:auto;">
                                    <table class="table dm-table mb-0">
                                        <thead>
                                            <tr>
                                                <th width="36"><input type="checkbox" id="dmCheckAll"></th>
                                                <th></th>
                                                <th>Name</th>
                                                <th>ID / Department</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dmPeopleList">
                                            <tr><td colspan="4" class="text-center py-4 text-muted">
                                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading…
                                            </td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-primary btn-sm" id="dmProceed" disabled>
                                Continue <i class="ri-arrow-right-line ms-1"></i>
                            </button>
                        </div>
                    </div>

                    {{-- STEP 2: device + starting pin + confirm --}}
                    <div id="dmStep2" style="display:none;">
                        <div class="dm-card mb-3">
                            <div class="card-header">
                                <i class="ri-check-double-line me-2"></i>Selected — <span id="dmStep2Count" class="fw-bold">0</span> people
                            </div>
                            <div class="card-body p-0">
                                <div style="max-height:220px;overflow-y:auto;">
                                    <table class="table dm-table mb-0" id="dmSummaryTable">
                                        <thead><tr><th></th><th>Name</th><th>ID / Department</th><th>Will get PIN</th></tr></thead>
                                        <tbody id="dmSummaryBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="dm-form-label">Device Serial</label>
                                <input type="text" id="dmDeviceSerial" class="dm-form-control dm-form-control-sm" placeholder="e.g. PKD7022588362" required>
                            </div>
                            <div class="col-md-6">
                                <label class="dm-form-label">Starting PIN</label>
                                <input type="number" id="dmStartingPin" class="dm-form-control dm-form-control-sm" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="dmBack">
                                <i class="ri-arrow-left-line me-1"></i>Back
                            </button>
                            <button type="button" class="btn btn-success btn-sm" id="dmSubmit">
                                <i class="ri-add-line me-1"></i>Assign All
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ══ FILTER ════════════════════════════════════════════════════════ --}}
    <div class="dm-filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="dm-form-label"><i class="ri-filter-3-line me-1"></i>Type</label>
                <select name="type" class="dm-form-select" onchange="this.form.submit()">
                    <option value="">All types</option>
                    <option value="student" {{ request('type')==='student'?'selected':'' }}>Students</option>
                    <option value="staff" {{ request('type')==='staff'?'selected':'' }}>Staff</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="dm-form-label"><i class="ri-search-line me-1"></i>Search</label>
                <input type="text" name="q" value="{{ request('q') }}" class="dm-form-control" placeholder="Search PIN…">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="ri-search-line me-1"></i>Search</button>
            </div>
        </form>
    </div>

    {{-- ══ TABLE ════════════════════════════════════════════════════════ --}}
    <div class="dm-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="ri-list-check-2 me-2"></i>Mapped Users</span>
            <span class="badge bg-primary">{{ $mappings->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table dm-table w-100 mb-0">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Device</th>
                            <th>PIN</th>
                            <th>Type</th>
                            <th>Person</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($mappings as $m)
                        <tr>
                            <td>
                                @if($m->photo_url)
                                    <img src="{{ $m->photo_url }}" class="dm-avatar" alt="">
                                @else
                                    <div class="dm-avatar-fallback">{{ strtoupper(substr($m->display_name, 0, 2)) }}</div>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:12px;">{{ $m->device_serial }}</td>
                            <td class="fw-semibold">{{ $m->device_pin }}</td>
                            <td><span class="badge bg-{{ $m->person_type==='student'?'primary':'info' }}-subtle text-{{ $m->person_type==='student'?'primary':'info' }}">{{ ucfirst($m->person_type) }}</span></td>
                            <td>{{ $m->display_name }}</td>
                            <td>
                                <span class="badge bg-{{ $m->active?'success':'secondary' }}-subtle text-{{ $m->active?'success':'secondary' }}">
                                    {{ $m->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteMapping({{ $m->id }})"><i class="ri-delete-bin-line"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No mappings yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $mappings->links() }}</div>
        </div>
    </div>

</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function dmToast(msg, type = 'success') {
    const colors = { success:'#16a34a', danger:'#dc2626', warning:'#d97706', info:'#2563eb' };
    const id = 'dm_toast_' + Date.now();
    document.body.insertAdjacentHTML('beforeend',
        `<div id="${id}" class="dm-toast" style="background:${colors[type] || colors.success};color:#fff;min-width:220px;border-radius:10px;padding:14px 20px;box-shadow:0 4px 20px rgba(0,0,0,.12);font-weight:600;font-size:13px;animation:dmToastIn .3s ease;">
            ${msg}
            <button onclick="document.getElementById('${id}').remove()" style="background:none;border:none;color:#fff;float:right;margin-left:12px;font-size:16px;cursor:pointer;">×</button>
        </div>`
    );
    setTimeout(() => document.getElementById(id)?.remove(), 4500);
}

// ── Shared fetch helper ─────────────────────────────────────────────────
function jsonHeaders(extra = {}) {
    return Object.assign({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
    }, extra);
}

async function handleJsonResponse(r) {
    if (r.redirected) {
        await Swal.fire({
            icon: 'warning',
            title: 'Session expired',
            text: 'Your session or security token expired. Please refresh the page and try again.',
            confirmButtonColor: '#2563eb',
        });
        throw new Error('Redirected — session expired');
    }
    if (r.status === 401 || r.status === 419) {
        await Swal.fire({
            icon: 'warning',
            title: 'Session expired',
            text: 'Please refresh the page and log in again.',
            confirmButtonColor: '#2563eb',
        });
        throw new Error('Session expired');
    }
    if (r.status === 422) {
        const err = await r.json();
        const msgs = Object.values(err.errors || {}).flat().join('<br>');
        await Swal.fire({
            icon: 'error',
            title: 'Please fix the following',
            html: msgs || err.message || 'Validation failed.',
            confirmButtonColor: '#2563eb',
        });
        throw new Error('Validation failed');
    }
    if (!r.ok) {
        const text = await r.text();
        console.error('Request failed:', r.status, text);
        await Swal.fire({
            icon: 'error',
            title: 'Something went wrong',
            text: 'Server responded with ' + r.status + '. Check the console for details.',
            confirmButtonColor: '#2563eb',
        });
        throw new Error('Server error (' + r.status + ')');
    }
    return r.json();
}

// ── Rich Select2 template: avatar + name + subtitle/meta line ──────────────
function personTemplate(item) {
    if (!item.id) return item.text;
    const photo = item.photo
        ? `<img src="${item.photo}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:8px;">`
        : `<div style="width:32px;height:32px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;font-size:11px;color:#64748b;">${item.text.slice(0,2).toUpperCase()}</div>`;
    const meta = item.meta ? Object.entries(item.meta).map(([k, v]) => `${k}: ${v}`).join(' · ') : '';
    return $(`
        <div style="display:flex;align-items:center;padding:2px 0;">
            ${photo}
            <div>
                <div style="font-weight:600;font-size:13px;">${item.text}</div>
                <div style="font-size:11px;color:#6b7280;">${item.subtitle || ''}${meta ? ' · ' + meta : ''}</div>
            </div>
        </div>
    `);
}

// ── AJAX-backed live-search person picker ──────────────────────────────
function initPersonSelect(selectEl, typeEl, opts = {}) {
    $(selectEl).select2({
        ajax: {
            url: "{{ route('device-mappings.search') }}",
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term || '', type: $(typeEl).val(), page: params.page || 1 }),
            processResults: data => ({ results: data.results, pagination: data.pagination }),
            cache: false,
        },
        minimumInputLength: 0,
        placeholder: 'Search…',
        templateResult: personTemplate,
        templateSelection: item => item.text || item.id,
        width: '100%',
        dropdownParent: $(document.body),
    });
    $(typeEl).on('change', () => $(selectEl).val(null).trigger('change'));
}

$(document).ready(() => {
    initPersonSelect('#personSelect', '#personType');
});

// ── Bulk Assign: fetch-once, filter client-side, checkbox table ────────────
function idKey(v) {
    return v === null || v === undefined ? '' : String(v);
}

let dmAllPeople = [];
let dmSelected = [];

function dmLoadPeople() {
    const type = document.getElementById('dmType').value;
    const listEl = document.getElementById('dmPeopleList');
    listEl.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading…</td></tr>';

    fetch(`{{ route('device-mappings.search') }}?type=${type}&q=&page=1&picker=1`, {
        headers: jsonHeaders(),
    })
    .then(handleJsonResponse)
    .then(data => {
        dmAllPeople = data.results || [];
        const keptIds = new Set(dmSelected.map(p => idKey(p.id)));
        dmSelected = dmAllPeople.filter(p => keptIds.has(idKey(p.id)));
        dmRenderTable(dmAllPeople);
    })
    .catch(() => {
        listEl.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Failed to load. Try again.</td></tr>';
    });
}

function dmRenderTable(people) {
    const listEl = document.getElementById('dmPeopleList');
    if (!people.length) {
        listEl.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No matches.</td></tr>';
        dmUpdateCount();
        return;
    }

    listEl.innerHTML = people.map(p => {
        const meta = p.meta ? Object.values(p.meta).filter(v => v && v !== '—').join(' · ') : '';
        const photo = p.photo
            ? `<img src="${p.photo}" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">`
            : `<div style="width:28px;height:28px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:10px;color:#64748b;">${p.text.slice(0,2).toUpperCase()}</div>`;
        return `<tr>
            <td><input type="checkbox" class="dm-person-check" data-id="${p.id}"></td>
            <td>${photo}</td>
            <td class="fw-semibold" style="font-size:12.5px;">${p.text}</td>
            <td class="text-muted" style="font-size:12px;">${meta || '—'}</td>
        </tr>`;
    }).join('');

    document.querySelectorAll('.dm-person-check').forEach(cb => {
        const id = cb.dataset.id;
        cb.checked = dmSelected.some(s => idKey(s.id) === idKey(id));
        cb.addEventListener('change', function () {
            const person = dmAllPeople.find(p => idKey(p.id) === idKey(id));
            if (!person) return;
            if (this.checked) {
                if (!dmSelected.some(s => idKey(s.id) === idKey(id))) dmSelected.push(person);
            } else {
                dmSelected = dmSelected.filter(s => idKey(s.id) !== idKey(id));
            }
            dmUpdateCount();
        });
    });

    dmUpdateCount();
}

function dmUpdateCount() {
    document.getElementById('dmSelectedCount').textContent = dmSelected.length + ' selected';
    document.getElementById('dmProceed').disabled = dmSelected.length === 0;
    const checkAll = document.getElementById('dmCheckAll');
    if (checkAll) checkAll.checked = dmAllPeople.length > 0 && dmSelected.length === dmAllPeople.length;
}

function dmApplyClientFilter() {
    const q = (document.getElementById('dmSearch').value || '').toLowerCase();
    if (!q) { dmRenderTable(dmAllPeople); return; }
    const filtered = dmAllPeople.filter(p => {
        const meta = p.meta ? Object.values(p.meta).join(' ').toLowerCase() : '';
        return p.text.toLowerCase().includes(q) || meta.includes(q);
    });
    dmRenderTable(filtered);
}

document.getElementById('dmType').addEventListener('change', () => { dmSelected = []; dmLoadPeople(); });
document.getElementById('dmSearch').addEventListener('input', dmApplyClientFilter);
document.getElementById('dmSelectAll').addEventListener('click', () => { dmSelected = [...dmAllPeople]; dmRenderTable(dmAllPeople); });
document.getElementById('dmClearAll').addEventListener('click', () => { dmSelected = []; dmRenderTable(dmAllPeople); });
document.getElementById('dmCheckAll').addEventListener('change', function () {
    dmSelected = this.checked ? [...dmAllPeople] : [];
    dmRenderTable(dmAllPeople);
});

function dmSetStep(n) {
    [1, 2].forEach(i => {
        const el = document.getElementById('dmStepBar' + i);
        const circle = el.querySelector('.dm-step-circle');
        el.classList.remove('active', 'done');
        if (i < n) { el.classList.add('done'); circle.innerHTML = '<i class="ri-check-line"></i>'; }
        else { circle.textContent = i; if (i === n) el.classList.add('active'); }
    });
}

document.getElementById('dmProceed').addEventListener('click', () => {
    document.getElementById('dmStep2Count').textContent = dmSelected.length;
    document.getElementById('dmSummaryBody').innerHTML = dmSelected.map((p, i) => {
        const startPin = document.getElementById('dmStartingPin').value || '?';
        const pin = startPin !== '?' ? (parseInt(startPin) + i) : '?';
        const meta = p.meta ? Object.values(p.meta).filter(v => v && v !== '—').join(' · ') : '';
        return `<tr><td>${p.photo ? `<img src="${p.photo}" style="width:24px;height:24px;border-radius:50%;">` : ''}</td>
                    <td>${p.text}</td><td class="text-muted" style="font-size:12px;">${meta || '—'}</td>
                    <td class="fw-semibold text-primary dm-pin-preview">${pin}</td></tr>`;
    }).join('');
    document.getElementById('dmStep1').style.display = 'none';
    document.getElementById('dmStep2').style.display = '';
    dmSetStep(2);
});

document.getElementById('dmStartingPin').addEventListener('input', function () {
    const start = parseInt(this.value);
    document.querySelectorAll('.dm-pin-preview').forEach((el, i) => {
        el.textContent = isNaN(start) ? '?' : start + i;
    });
});

document.getElementById('dmBack').addEventListener('click', () => {
    document.getElementById('dmStep2').style.display = 'none';
    document.getElementById('dmStep1').style.display = '';
    dmSetStep(1);
});

document.getElementById('dmSubmit').addEventListener('click', function () {
    const deviceSerial = document.getElementById('dmDeviceSerial').value.trim();
    const startingPin = document.getElementById('dmStartingPin').value;

    if (!deviceSerial || !startingPin) {
        Swal.fire({ icon: 'warning', title: 'Missing info', text: 'Enter device serial and starting PIN.', confirmButtonColor: '#2563eb' });
        return;
    }
    if (!dmSelected.length) {
        Swal.fire({ icon: 'warning', title: 'No one selected', text: 'Go back and select at least one person.', confirmButtonColor: '#2563eb' });
        return;
    }
    const invalid = dmSelected.filter(p => p.id === null || p.id === undefined);
    if (invalid.length) {
        Swal.fire({
            icon: 'error',
            title: 'Some selections are incomplete',
            text: invalid.length + ' selected record(s) are missing a valid ID and were skipped. Please reload and try again.',
            confirmButtonColor: '#2563eb',
        });
        return;
    }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning…';

    fetch('{{ route('device-mappings.bulk-manual') }}', {
        method: 'POST',
        headers: jsonHeaders({ 'Content-Type': 'application/json' }),
        body: JSON.stringify({
            device_serial: deviceSerial,
            starting_pin: startingPin,
            person_type: document.getElementById('dmType').value,
            person_ids: dmSelected.map(p => p.id),
        }),
    })
    .then(handleJsonResponse)
    .then(d => {
        Swal.fire({
            icon: d.success ? 'success' : 'error',
            title: d.success ? 'Assigned!' : 'Failed',
            text: d.message,
            confirmButtonColor: '#2563eb',
        }).then(() => { if (d.success) location.reload(); });
    })
    .catch(e => { console.error(e); })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-add-line me-1"></i>Assign All';
    });
});

function dmResetModal() {
    dmSelected = [];
    document.getElementById('dmStep1').style.display = '';
    document.getElementById('dmStep2').style.display = 'none';
    document.getElementById('dmSearch').value = '';
    document.getElementById('dmDeviceSerial').value = '';
    document.getElementById('dmStartingPin').value = '';
    dmSetStep(1);
    dmLoadPeople();
}

document.getElementById('bulkAssignModal').addEventListener('show.bs.modal', dmResetModal);

// ── Single mapping form ──────────────────────────────────────────────────
document.getElementById('addMappingForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('{{ route('device-mappings.store') }}', {
        method: 'POST',
        headers: jsonHeaders(),
        body: fd,
    })
    .then(handleJsonResponse)
    .then(d => {
        if (d.success) {
            dmToast(d.message || 'Mapping saved.', 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            Swal.fire({ icon: 'error', title: 'Failed', text: d.message, confirmButtonColor: '#2563eb' });
        }
    })
    .catch(e => console.error(e));
});

document.getElementById('addMappingModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('addMappingForm').reset();
    $('#personSelect').val(null).trigger('change');
});

// ── CSV import form ───────────────────────────────────────────────────────
document.getElementById('bulkImportForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    const resultEl = document.getElementById('importResult');
    resultEl.innerHTML = 'Importing…';
    fetch('{{ route('device-mappings.bulk-import') }}', {
        method: 'POST',
        headers: jsonHeaders(),
        body: fd,
    })
    .then(handleJsonResponse)
    .then(d => {
        resultEl.innerHTML = `<span class="text-${d.success?'success':'danger'}">${d.message}</span>`;
        if (d.errors && d.errors.length) resultEl.innerHTML += '<br>' + d.errors.join('<br>');
        if (d.success) {
            dmToast(d.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(e => {
        console.error(e);
        resultEl.innerHTML = '';
    });
});

function deleteMapping(id) {
    Swal.fire({
        icon: 'warning',
        title: 'Remove this mapping?',
        text: 'This cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(`/attendance/device-mappings/${id}`, {
            method: 'DELETE',
            headers: jsonHeaders(),
        })
        .then(handleJsonResponse)
        .then(d => {
            if (d.success) {
                dmToast(d.message || 'Mapping removed.', 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: d.message, confirmButtonColor: '#2563eb' });
            }
        })
        .catch(e => console.error(e));
    });
}
</script>
@endsection