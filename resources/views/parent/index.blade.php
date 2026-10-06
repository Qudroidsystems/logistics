@extends('layouts.master')

@section('content')
<?php use Spatie\Permission\Models\Role; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap');

:root {
    --u-primary:    #1e3a5f;
    --u-accent:     #2563eb;
    --u-indigo:     #4f46e5;
    --u-success:    #16a34a;
    --u-warning:    #d97706;
    --u-danger:     #dc2626;
    --u-muted:      #6b7280;
    --u-border:     #e2e8f0;
    --u-bg:         #f8fafc;
    --u-surface:    #ffffff;
    --u-radius:     12px;
    --u-shadow:     0 2px 8px rgba(0,0,0,.07);
    --u-shadow-lg:  0 8px 32px rgba(0,0,0,.12);
}

*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Plus Jakarta Sans', sans-serif; }

@keyframes fadeInUp   { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInDown { from { opacity:0; transform:translateY(-14px);} to { opacity:1; transform:translateY(0); } }
@keyframes slideRight { from { opacity:0; transform:translateX(-20px);} to { opacity:1; transform:translateX(0); } }
@keyframes scaleIn    { from { opacity:0; transform:scale(.92);       } to { opacity:1; transform:scale(1);    } }
@keyframes pulse      { 0%,100%{transform:scale(1);}50%{transform:scale(1.05);} }
@keyframes shimmer    { from{background-position:-200% 0;} to{background-position:200% 0;} }
@keyframes rowIn      { from{opacity:0;transform:translateX(-8px);}to{opacity:1;transform:translateX(0);} }
@keyframes badgePop   { 0%{transform:scale(0.5);}70%{transform:scale(1.15);}100%{transform:scale(1);} }

/* ── Hero ── */
.u-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 55%, #4f46e5 100%);
    border-radius: var(--u-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: fadeInDown .5s ease both;
}
.u-hero::before {
    content:''; position:absolute; top:-70px; right:-70px;
    width:240px; height:240px;
    background:rgba(255,255,255,.06); border-radius:50%;
    animation: pulse 6s ease-in-out infinite;
}
.u-hero::after {
    content:''; position:absolute; bottom:-50px; right:140px;
    width:150px; height:150px;
    background:rgba(255,255,255,.04); border-radius:50%;
}
.u-hero h1 { font-size:22px; font-weight:800; color:#fff; margin:0 0 6px; position:relative; letter-spacing:-.3px; }
.u-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ── */
.stat-card {
    background: var(--u-surface);
    border: 1px solid var(--u-border);
    border-radius: var(--u-radius);
    padding: 18px 20px;
    transition: transform .2s, box-shadow .2s;
    animation: fadeInUp .5s ease both;
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content:''; position:absolute; bottom:0; left:0; right:0; height:3px;
    border-radius:0 0 var(--u-radius) var(--u-radius);
    background: linear-gradient(90deg, var(--u-accent), var(--u-indigo));
    transform: scaleX(0); transform-origin: left;
    transition: transform .3s ease;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:var(--u-shadow-lg); }
.stat-card:hover::before { transform: scaleX(1); }
.stat-card .stat-value { font-size:28px; font-weight:800; color:var(--u-primary); line-height:1; }
.stat-card .stat-label { font-size:12px; color:var(--u-muted); margin-top:5px; font-weight:500; }
.stat-card .stat-icon  { font-size:34px; opacity:.1; float:right; margin-top:-6px; }

/* ── Filter area ── */
.u-filter-card {
    background: var(--u-surface);
    border: 1px solid var(--u-border);
    border-top: 3px solid var(--u-accent);
    border-radius: var(--u-radius);
    padding: 16px 20px;
    animation: fadeInUp .5s .1s ease both;
}
.u-input {
    border: 1.5px solid var(--u-border);
    border-radius: 9px;
    padding: 9px 14px;
    font-size: 13px;
    font-family: inherit;
    transition: border-color .2s, box-shadow .2s;
    width: 100%;
    background: #fff;
}
.u-input:focus { border-color:var(--u-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.u-input-icon-wrap { position:relative; }
.u-input-icon { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
.u-input-icon-wrap .u-input { padding-left: 34px; }

/* ── Table card ── */
.u-table-card {
    background: var(--u-surface);
    border: 1px solid var(--u-border);
    border-radius: var(--u-radius);
    overflow: hidden;
    box-shadow: var(--u-shadow);
    animation: fadeInUp .5s .15s ease both;
}
.u-table-card .card-header {
    background: var(--u-surface);
    border-bottom: 1px solid var(--u-border);
    padding: 14px 20px;
}
.u-table thead th {
    background: var(--u-primary);
    color: #fff;
    padding: 12px 16px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
    border: none;
}
.u-table tbody td {
    padding: 11px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--u-border);
    font-size: 13px;
    transition: background .12s;
}
.u-table tbody tr {
    animation: rowIn .3s ease both;
}
.u-table tbody tr:hover td { background: #f0f9ff; }
.u-table tbody tr:last-child td { border-bottom: none; }

/* ── Avatar ── */
.u-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: #fff;
    border: 2px solid var(--u-border);
    flex-shrink: 0;
    transition: transform .2s;
}
.u-avatar:hover { transform: scale(1.1); }

/* ── Parent badges ── */
.u-role-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
    animation: badgePop .3s ease;
}
.u-role-pill.father  { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.u-role-pill.mother  { background:#fce7f3; color:#9d174d; border:1px solid #fbcfe8; }
.u-role-pill.both    { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.u-role-pill.default { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

/* ── Action buttons ── */
.u-action-btn {
    width: 30px; height: 30px;
    border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    border: none; cursor: pointer;
    font-size: 13px;
    transition: transform .15s, box-shadow .15s;
    text-decoration: none;
}
.u-action-btn:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0,0,0,.15); }
.u-action-btn.view   { background:#eff6ff; color:#2563eb; }
.u-action-btn.edit   { background:#f0fdf4; color:#16a34a; }
.u-action-btn.del    { background:#fef2f2; color:#dc2626; }
.u-action-btn.phone  { background:#fffbeb; color:#d97706; }
.u-action-btn.email  { background:#f0f9ff; color:#0ea5e9; }

/* ── Top buttons ── */
.u-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 9px;
    font-size: 13px; font-weight: 600;
    border: none; cursor: pointer;
    transition: transform .15s, box-shadow .15s, opacity .15s;
    text-decoration: none;
}
.u-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,.15); opacity: .92; }
.u-btn.primary  { background:linear-gradient(135deg,var(--u-accent),var(--u-indigo)); color:#fff; }
.u-btn.success  { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; }
.u-btn.warning  { background:linear-gradient(135deg,#d97706,#b45309); color:#fff; }
.u-btn.danger   { background:var(--u-danger); color:#fff; }
.u-btn.ghost    { background:#fff; color:var(--u-primary); border:1.5px solid var(--u-border); }
.u-btn.ghost:hover { background:#f8fafc; }

/* ── Modal redesign ── */
.u-modal .modal-content {
    border: none;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: var(--u-shadow-lg);
    animation: scaleIn .25s ease;
}
.u-modal-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    padding: 22px 28px;
    position: relative; overflow: hidden;
}
.u-modal-hero::before {
    content:''; position:absolute; top:-40px; right:-40px;
    width:140px; height:140px;
    background:rgba(255,255,255,.07); border-radius:50%;
}
.u-modal-hero h5 { color:#fff; font-weight:700; font-size:16px; margin:0; position:relative; }
.u-modal-hero p  { color:rgba(255,255,255,.72); font-size:12px; margin:4px 0 0; position:relative; }
.u-modal-hero .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); opacity:.8; }
.u-modal-hero .btn-close:hover { opacity:1; }
.u-modal-body { padding: 22px 24px; background: var(--u-bg); max-height: calc(100vh - 200px); overflow-y: auto; }
.u-modal-footer { padding: 14px 24px; background: var(--u-surface); border-top:1px solid var(--u-border); border-radius:0 0 18px 18px; }

.u-form-label {
    font-size: 11.5px; font-weight: 700; color: var(--u-muted);
    text-transform: uppercase; letter-spacing: .4px;
    display: block; margin-bottom: 5px;
}
.u-form-input {
    width: 100%;
    border: 1.5px solid var(--u-border);
    border-radius: 9px;
    padding: 10px 14px;
    font-size: 13px;
    font-family: inherit;
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.u-form-input:focus { border-color: var(--u-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.u-form-input:disabled { background:#f1f5f9; cursor:not-allowed; }

/* ── Empty state ── */
.u-empty {
    text-align:center; padding:48px 24px; color: var(--u-muted);
}
.u-empty i { font-size:3rem; display:block; margin-bottom:12px; opacity:.3; }

/* ── Pagination ── */
.u-pagination {
    display: flex; align-items: center; gap: 4px; flex-wrap: wrap;
}
.u-page-btn {
    min-width: 32px; height: 32px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 7px; border: 1.5px solid var(--u-border);
    background: #fff; color: var(--u-primary);
    font-size: 12px; font-weight: 600; cursor: pointer;
    transition: all .15s;
    text-decoration: none;
}
.u-page-btn:hover, .u-page-btn.active { background: var(--u-accent); color:#fff; border-color:var(--u-accent); }
.u-page-btn.disabled { opacity:0.4; cursor:not-allowed; }

/* ── Phone badge ── */
.phone-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 12px;
    font-size: 11px; font-family: 'JetBrains Mono', monospace;
    background: #f0fdf4; color: #065f46; border: 1px solid #bbf7d0;
}
.phone-badge.missing { background:#fef2f2; color:#991b1b; border-color:#fecaca; }

/* ── Contact info card ── */
.contact-group {
    display: flex; flex-direction: column; gap: 2px;
}
.contact-group .label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; font-weight: 600; }
.contact-group .value { font-size: 13px; font-weight: 500; color: #1e293b; }
.contact-group .value .phone-link { color: var(--u-accent); text-decoration: none; }
.contact-group .value .phone-link:hover { text-decoration: underline; }

/* ── View modal specific ── */
.detail-row { display:flex; padding:6px 0; border-bottom:1px solid #f1f5f9; }
.detail-label { width:40%; font-weight:600; color:#475569; font-size:13px; }
.detail-value { width:60%; color:#0f172a; font-size:13px; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .u-hero { padding: 20px; }
    .stat-card .stat-value { font-size: 22px; }
    .u-table thead th { font-size: 10px; padding: 8px 10px; }
    .u-table tbody td { padding: 8px 10px; font-size: 12px; }
    .u-modal-body { max-height: calc(100vh - 160px); }
    .detail-row { flex-direction:column; gap:2px; }
    .detail-label { width:100%; }
    .detail-value { width:100%; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Breadcrumb --}}
    <div class="row mb-1" style="animation:fadeInDown .4s ease both;">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 small">
                    <li class="breadcrumb-item"><a href="#" style="color:var(--u-accent)">Parent Management</a></li>
                    <li class="breadcrumb-item active text-muted">Parents / Guardians</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Hero --}}
    <div class="u-hero">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="ri-user-heart-line me-2"></i>Parent / Guardian Management</h1>
                <p>Manage parent and guardian records linked to students.</p>
            </div>
            <div class="col-auto d-none d-md-flex gap-2">
                @can('Create parent')
                <button type="button" class="u-btn primary" id="openAddParentModalBtn">
                    <i class="bi bi-plus-circle"></i> Add Parent
                </button>
                @endcan
                <button type="button" class="u-btn ghost" id="refreshBtn">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-3" style="animation:fadeInDown .4s ease both;">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    </div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger border-0 rounded-3 mb-3" style="animation:fadeInDown .4s ease both;">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3" style="animation-delay:.05s">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value" id="totalParents">{{ $totalParents }}</div>
                <div class="stat-label">Total Parent Records</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="animation-delay:.08s">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-3-line"></i></div>
                <div class="stat-value" style="color:var(--u-accent)" id="fathersCount">{{ $parentsWithFather }}</div>
                <div class="stat-label">Fathers Registered</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="animation-delay:.11s">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-4-line"></i></div>
                <div class="stat-value" style="color:#d97706" id="mothersCount">{{ $parentsWithMother }}</div>
                <div class="stat-label">Mothers Registered</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="animation-delay:.14s">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-heart-line"></i></div>
                <div class="stat-value" style="color:#16a34a" id="bothCount">{{ $parentsWithBoth }}</div>
                <div class="stat-label">Both Parents</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="u-filter-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="u-form-label">Search</label>
                <div class="u-input-icon-wrap">
                    <i class="bi bi-search u-input-icon"></i>
                    <input type="text" id="liveSearch" class="u-input" placeholder="Name, phone, admission…">
                </div>
            </div>
            <div class="col-md-2">
                <label class="u-form-label">Class</label>
                <select id="classFilter" class="u-input">
                    <option value="all">All Classes</option>
                    @foreach ($schoolclasses as $class)
                    <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="u-form-label">Term</label>
                <select id="termFilter" class="u-input">
                    <option value="all">All Terms</option>
                    @foreach ($schoolterms as $term)
                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="u-form-label">Session</label>
                <select id="sessionFilter" class="u-input">
                    <option value="all">All Sessions</option>
                    @foreach ($schoolsessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="u-form-label">Phone Status</label>
                <select id="phoneFilter" class="u-input">
                    <option value="all">All</option>
                    <option value="has_father_phone">Has Father's Phone</option>
                    <option value="has_mother_phone">Has Mother's Phone</option>
                    <option value="has_any_phone">Has Any Phone</option>
                    <option value="no_phone">No Phone</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="u-btn ghost w-100" id="clearFilters">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="u-table-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="fw-bold" style="color:var(--u-primary);font-size:14px;">
                <i class="ri-list-check me-2" style="color:var(--u-accent)"></i>
                Parent / Guardian Records
                <span id="parentCountBadge" class="badge ms-2" style="background:var(--u-accent);font-size:11px;font-weight:600;">{{ $totalParents }}</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button class="u-btn danger d-none" id="deleteMultipleBtn" onclick="deleteMultipleParents()">
                    <i class="bi bi-trash"></i> Delete Selected
                </button>
                <div class="d-md-none d-flex gap-2">
                    @can('Create parent')
                    <button type="button" class="u-btn primary" id="openAddParentModalBtnMobile">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table u-table mb-0" id="parentsTable">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="checkAll" style="accent-color:#fff;cursor:pointer;">
                        </th>
                        <th width="50"></th>
                        <th class="sortable" data-col="0">Student <i class="bi bi-chevron-expand ms-1 opacity-50"></i></th>
                        <th class="sortable" data-col="1">Father <i class="bi bi-chevron-expand ms-1 opacity-50"></i></th>
                        <th class="sortable" data-col="2">Mother <i class="bi bi-chevron-expand ms-1 opacity-50"></i></th>
                        <th>Contact</th>
                        <th width="140">Actions</th>
                    </tr>
                </thead>
                <tbody id="parentsTableBody">
                    <tr id="loadingRow">
                        <td colspan="7">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2">Loading parent records...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top"
             style="background:var(--u-bg);">
            <div class="text-muted small">
                Showing <span id="showingCount" class="fw-semibold text-dark">0</span>
                of <span id="totalCount" class="fw-semibold text-dark">0</span> records
            </div>
            <div id="paginationEl" class="u-pagination"></div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         ADD PARENT MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="addParentModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="u-modal-hero">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h5><i class="bi bi-person-plus me-2"></i>Add Parent / Guardian</h5>
                    <p>Create a parent/guardian record linked to a student</p>
                </div>
                <form id="addParentForm" autocomplete="off">
                    @csrf
                    <div class="u-modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="u-form-label">Select Student <span class="text-danger">*</span></label>
                                <div class="u-input-icon-wrap mb-2">
                                    <i class="bi bi-search u-input-icon"></i>
                                    <input type="text" id="studentSearch" class="u-input" placeholder="Search by name or admission number...">
                                </div>
                                <select id="studentSelect" name="studentId" class="u-form-input" required>
                                    <option value="">— Select a student —</option>
                                </select>
                                <small class="text-muted">Only students without existing parent records are shown.</small>
                            </div>

                            <div class="col-12 mt-3">
                                <h6 class="fw-bold text-primary"><i class="bi bi-person me-2"></i>Father's Information</h6>
                                <hr class="my-2">
                            </div>

                            <div class="col-md-4">
                                <label class="u-form-label">Title</label>
                                <select name="father_title" class="u-form-input">
                                    <option value="">Select</option>
                                    <option value="Mr">Mr</option>
                                    <option value="Dr">Dr</option>
                                    <option value="Prof">Prof</option>
                                    <option value="Chief">Chief</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="u-form-label">Father's Name</label>
                                <input type="text" name="father" class="u-form-input" placeholder="Full name">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Phone Number</label>
                                <input type="text" name="father_phone" class="u-form-input" placeholder="+234 xxx xxx xxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Occupation</label>
                                <input type="text" name="father_occupation" class="u-form-input" placeholder="Occupation">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">City</label>
                                <input type="text" name="father_city" class="u-form-input" placeholder="City">
                            </div>

                            <div class="col-12 mt-3">
                                <h6 class="fw-bold text-danger"><i class="bi bi-person me-2"></i>Mother's Information</h6>
                                <hr class="my-2">
                            </div>

                            <div class="col-md-4">
                                <label class="u-form-label">Title</label>
                                <select name="mother_title" class="u-form-input">
                                    <option value="">Select</option>
                                    <option value="Mrs">Mrs</option>
                                    <option value="Dr">Dr</option>
                                    <option value="Prof">Prof</option>
                                    <option value="Chief">Chief</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="u-form-label">Mother's Name</label>
                                <input type="text" name="mother" class="u-form-input" placeholder="Full name">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Phone Number</label>
                                <input type="text" name="mother_phone" class="u-form-input" placeholder="+234 xxx xxx xxxx">
                            </div>

                            <div class="col-12 mt-3">
                                <h6 class="fw-bold text-secondary"><i class="bi bi-house me-2"></i>Contact Information</h6>
                                <hr class="my-2">
                            </div>

                            <div class="col-12">
                                <label class="u-form-label">Parent Email</label>
                                <input type="email" name="parent_email" class="u-form-input" placeholder="parent@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Office Address</label>
                                <input type="text" name="office_address" class="u-form-input" placeholder="Office address">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Home Address</label>
                                <input type="text" name="parent_address" class="u-form-input" placeholder="Home address">
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3 rounded-3" id="addAlert"></div>
                    </div>
                    <div class="u-modal-footer d-flex justify-content-end gap-2">
                        <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="u-btn primary" id="addParentBtn">
                            <i class="bi bi-plus-circle"></i> Create Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         EDIT PARENT MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="editParentModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="u-modal-hero" style="background:linear-gradient(135deg,#065f46,#16a34a,#4ade80);">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h5><i class="bi bi-pencil-square me-2"></i>Edit Parent Record</h5>
                    <p>Update parent/guardian information</p>
                </div>
                <form id="editParentForm" autocomplete="off">
                    @csrf
                    @method('PATCH')
                    <div class="u-modal-body">
                        <input type="hidden" id="editParentId" name="id">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-info border-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Student: <strong id="editStudentName">-</strong> 
                                    (Adm: <span id="editAdmissionNo">-</span>)
                                </div>
                            </div>

                            <div class="col-12">
                                <h6 class="fw-bold text-primary"><i class="bi bi-person me-2"></i>Father's Information</h6>
                                <hr class="my-2">
                            </div>

                            <div class="col-md-4">
                                <label class="u-form-label">Title</label>
                                <select name="father_title" id="editFatherTitle" class="u-form-input">
                                    <option value="">Select</option>
                                    <option value="Mr">Mr</option>
                                    <option value="Dr">Dr</option>
                                    <option value="Prof">Prof</option>
                                    <option value="Chief">Chief</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="u-form-label">Father's Name</label>
                                <input type="text" name="father" id="editFather" class="u-form-input" placeholder="Full name">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Phone Number</label>
                                <input type="text" name="father_phone" id="editFatherPhone" class="u-form-input" placeholder="+234 xxx xxx xxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Occupation</label>
                                <input type="text" name="father_occupation" id="editFatherOccupation" class="u-form-input" placeholder="Occupation">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">City</label>
                                <input type="text" name="father_city" id="editFatherCity" class="u-form-input" placeholder="City">
                            </div>

                            <div class="col-12 mt-2">
                                <h6 class="fw-bold text-danger"><i class="bi bi-person me-2"></i>Mother's Information</h6>
                                <hr class="my-2">
                            </div>

                            <div class="col-md-4">
                                <label class="u-form-label">Title</label>
                                <select name="mother_title" id="editMotherTitle" class="u-form-input">
                                    <option value="">Select</option>
                                    <option value="Mrs">Mrs</option>
                                    <option value="Dr">Dr</option>
                                    <option value="Prof">Prof</option>
                                    <option value="Chief">Chief</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="u-form-label">Mother's Name</label>
                                <input type="text" name="mother" id="editMother" class="u-form-input" placeholder="Full name">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Phone Number</label>
                                <input type="text" name="mother_phone" id="editMotherPhone" class="u-form-input" placeholder="+234 xxx xxx xxxx">
                            </div>

                            <div class="col-12 mt-2">
                                <h6 class="fw-bold text-secondary"><i class="bi bi-house me-2"></i>Contact Information</h6>
                                <hr class="my-2">
                            </div>

                            <div class="col-12">
                                <label class="u-form-label">Parent Email</label>
                                <input type="email" name="parent_email" id="editParentEmail" class="u-form-input" placeholder="parent@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Office Address</label>
                                <input type="text" name="office_address" id="editOfficeAddress" class="u-form-input" placeholder="Office address">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Home Address</label>
                                <input type="text" name="parent_address" id="editParentAddress" class="u-form-input" placeholder="Home address">
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3 rounded-3" id="editAlert"></div>
                    </div>
                    <div class="u-modal-footer d-flex justify-content-end gap-2">
                        <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="u-btn success" id="updateParentBtn">
                            <i class="bi bi-check-circle"></i> Update Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         VIEW PARENT MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="viewParentModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="u-modal-hero" style="background:linear-gradient(135deg,#1e3a5f,#4f46e5,#7c3aed);">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h5><i class="bi bi-person-lines-fill me-2"></i>Parent / Guardian Details</h5>
                    <p>Complete parent/guardian information</p>
                </div>
                <div class="u-modal-body" id="viewParentContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading details...</p>
                    </div>
                </div>
                <div class="u-modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="u-btn primary" id="viewEditBtn" style="display:none;">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         DELETE CONFIRM MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="deleteParentModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <div style="width:70px;height:70px;border-radius:50%;background:#fef2f2;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="bi bi-trash" style="font-size:28px;color:var(--u-danger)"></i>
                    </div>
                    <h4 class="fw-700" style="color:var(--u-primary)">Confirm Delete</h4>
                    <p class="text-muted mb-4" id="deleteParentMessage">This will permanently remove this parent record.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="u-btn danger" id="confirmDeleteParent">
                            <i class="bi bi-trash"></i> Yes, Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════════ --}}

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // CONFIGURATION
    // ============================================================
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let currentPage = 1;
    let totalRecords = 0;
    let deleteTargetId = null;
    let viewTargetId = null;

    // ============================================================
    // MODAL HELPERS
    // ============================================================
    function showModal(modalId) {
        const modalEl = document.getElementById(modalId);
        if (!modalEl) return null;
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
        modal.show();
        return modal;
    }

    function hideModal(modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    }

    // ============================================================
    // DATA FETCHING
    // ============================================================
    function fetchParents(page = 1) {
        const search = document.getElementById('liveSearch')?.value || '';
        const classId = document.getElementById('classFilter')?.value || 'all';
        const termId = document.getElementById('termFilter')?.value || 'all';
        const sessionId = document.getElementById('sessionFilter')?.value || 'all';
        const phoneFilter = document.getElementById('phoneFilter')?.value || 'all';

        const params = new URLSearchParams({
            page: page,
            per_page: 15,
            search: search,
            class_id: classId,
            term_id: termId,
            session_id: sessionId,
            has_phone: phoneFilter
        });

        const tbody = document.getElementById('parentsTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2">Loading records...</p>
                        </div>
                    </td>
                </tr>
            `;
        }

        axios.get(`/parents/optimized?${params.toString()}`)
            .then(response => {
                if (response.data.success) {
                    renderParents(response.data.data.data);
                    updatePagination(response.data.data);
                    updateStats(response.data.stats || {});
                }
            })
            .catch(error => {
                console.error('Error fetching parents:', error);
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7">
                                <div class="u-empty">
                                    <i class="bi bi-exclamation-triangle text-danger"></i>
                                    Failed to load records. Please try again.
                                </div>
                            </td>
                        </tr>
                    `;
                }
            });
    }

    // ============================================================
    // RENDER FUNCTIONS
    // ============================================================
    function renderParents(parents) {
        const tbody = document.getElementById('parentsTableBody');
        if (!tbody) return;

        if (!parents || parents.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="u-empty">
                            <i class="bi bi-person-x"></i>
                            No parent records found
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        parents.forEach((p, index) => {
            const studentInitials = getInitials(p.student_name);
            const hasFather = p.father && p.father.trim() !== '';
            const hasMother = p.mother && p.mother.trim() !== '';
            const hasFatherPhone = p.father_phone && p.father_phone.trim() !== '';
            const hasMotherPhone = p.mother_phone && p.mother_phone.trim() !== '';

            const parentBadge = hasFather && hasMother
                ? '<span class="u-role-pill both"><i class="bi bi-person-heart"></i>Both</span>'
                : hasFather
                ? '<span class="u-role-pill father"><i class="bi bi-person"></i>Father</span>'
                : hasMother
                ? '<span class="u-role-pill mother"><i class="bi bi-person"></i>Mother</span>'
                : '<span class="u-role-pill default">—</span>';

            const phoneHtml = hasFatherPhone || hasMotherPhone
                ? `<div class="contact-group">
                    ${hasFatherPhone ? `<div><span class="label">Father</span> <div class="value"><a href="tel:${p.father_phone}" class="phone-link">${p.father_phone}</a></div></div>` : ''}
                    ${hasMotherPhone ? `<div><span class="label">Mother</span> <div class="value"><a href="tel:${p.mother_phone}" class="phone-link">${p.mother_phone}</a></div></div>` : ''}
                </div>`
                : '<span class="phone-badge missing"><i class="bi bi-telephone-x"></i> No phone</span>';

            const delay = (index % 10) * 0.03;

            html += `
                <tr data-id="${p.id}" style="animation-delay:${delay}s">
                    <td>
                        <input type="checkbox" class="row-check" value="${p.id}" style="accent-color:var(--u-accent);cursor:pointer;">
                    </td>
                    <td>
                        <div class="u-avatar" style="background:linear-gradient(135deg,#667eea,#764ba2);">
                            ${studentInitials || 'S'}
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold" style="color:var(--u-primary)">
                            ${escapeHtml(p.student_name || '—')}
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-id-card me-1"></i>${escapeHtml(p.admissionNo || 'N/A')}
                            ${p.schoolclass ? `<span class="ms-2"><i class="bi bi-building me-1"></i>${escapeHtml(p.schoolclass)} ${escapeHtml(p.arm || '')}</span>` : ''}
                        </div>
                    </td>
                    <td>
                        ${hasFather ? `<div class="fw-semibold">${escapeHtml(p.father)}</div>
                            ${p.father_title ? `<small class="text-muted">${escapeHtml(p.father_title)}</small>` : ''}
                            ${p.father_occupation ? `<div><small class="text-muted">${escapeHtml(p.father_occupation)}</small></div>` : ''}`
                            : '<span class="text-muted">—</span>'}
                    </td>
                    <td>
                        ${hasMother ? `<div class="fw-semibold">${escapeHtml(p.mother)}</div>
                            ${p.mother_title ? `<small class="text-muted">${escapeHtml(p.mother_title)}</small>` : ''}`
                            : '<span class="text-muted">—</span>'}
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            ${phoneHtml}
                            ${parentBadge}
                            ${p.parent_email ? `<small><i class="bi bi-envelope me-1 text-muted"></i>${escapeHtml(p.parent_email)}</small>` : ''}
                        </div>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button type="button" class="u-action-btn view view-parent-btn" data-id="${p.id}" title="View">
                                <i class="bi bi-eye"></i>
                            </button>
                            @can('Update parent')
                            <button type="button" class="u-action-btn edit edit-parent-btn" data-id="${p.id}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                            @can('Delete parent')
                            <button type="button" class="u-action-btn del delete-parent-btn" data-id="${p.id}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                            ${hasFatherPhone ? `<a href="tel:${p.father_phone}" class="u-action-btn phone" title="Call Father"><i class="bi bi-telephone"></i></a>` : ''}
                            ${hasMotherPhone ? `<a href="tel:${p.mother_phone}" class="u-action-btn phone" title="Call Mother"><i class="bi bi-telephone"></i></a>` : ''}
                            ${p.parent_email ? `<a href="mailto:${p.parent_email}" class="u-action-btn email" title="Email"><i class="bi bi-envelope"></i></a>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        updateCheckAllState();
    }

    function updatePagination(pagination) {
        currentPage = pagination.current_page || 1;
        totalRecords = pagination.total || 0;

        const showingSpan = document.getElementById('showingCount');
        const totalSpan = document.getElementById('totalCount');
        const badge = document.getElementById('parentCountBadge');

        if (showingSpan) showingSpan.textContent = pagination.data?.length || 0;
        if (totalSpan) totalSpan.textContent = totalRecords;
        if (badge) badge.textContent = totalRecords;

        const paginationEl = document.getElementById('paginationEl');
        if (!paginationEl) return;

        const lastPage = pagination.last_page || 1;
        let html = '';

        html += `<button class="u-page-btn ${currentPage <= 1 ? 'disabled' : ''}" 
                        onclick="${currentPage > 1 ? `fetchParents(${currentPage - 1})` : ''}"
                        ${currentPage <= 1 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-left"></i>
                </button>`;

        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(lastPage, currentPage + 2);

        if (startPage > 1) {
            html += `<button class="u-page-btn" onclick="fetchParents(1)">1</button>`;
            if (startPage > 2) html += `<span class="px-1 text-muted">…</span>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="u-page-btn ${i === currentPage ? 'active' : ''}" 
                            onclick="fetchParents(${i})">${i}</button>`;
        }

        if (endPage < lastPage) {
            if (endPage < lastPage - 1) html += `<span class="px-1 text-muted">…</span>`;
            html += `<button class="u-page-btn" onclick="fetchParents(${lastPage})">${lastPage}</button>`;
        }

        html += `<button class="u-page-btn ${currentPage >= lastPage ? 'disabled' : ''}" 
                        onclick="${currentPage < lastPage ? `fetchParents(${currentPage + 1})` : ''}"
                        ${currentPage >= lastPage ? 'disabled' : ''}>
                    <i class="bi bi-chevron-right"></i>
                </button>`;

        paginationEl.innerHTML = html;
    }

    function updateStats(stats) {
        const totalEl = document.getElementById('totalParents');
        const fathersEl = document.getElementById('fathersCount');
        const mothersEl = document.getElementById('mothersCount');
        const bothEl = document.getElementById('bothCount');

        if (totalEl) totalEl.textContent = stats.total || 0;
        if (fathersEl) fathersEl.textContent = stats.has_father || 0;
        if (mothersEl) mothersEl.textContent = stats.has_mother || 0;
        if (bothEl) bothEl.textContent = stats.has_both || 0;
    }

    // ============================================================
    // UTILITY FUNCTIONS
    // ============================================================
    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m]));
    }

    function getInitials(name) {
        if (!name) return '';
        const parts = name.split(' ');
        const first = parts[0]?.[0] || '';
        const last = parts[parts.length - 1]?.[0] || '';
        return (first + last).toUpperCase();
    }

    function showAlert(el, msg) {
        if (el) {
            el.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>${msg}`;
            el.classList.remove('d-none');
            setTimeout(() => el.classList.add('d-none'), 5000);
        }
    }

    function updateCheckAllState() {
        const total = document.querySelectorAll('.row-check').length;
        const checked = document.querySelectorAll('.row-check:checked').length;
        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.checked = total > 0 && total === checked;
            checkAll.indeterminate = checked > 0 && checked < total;
        }

        const deleteBtn = document.getElementById('deleteMultipleBtn');
        if (deleteBtn) {
            deleteBtn.classList.toggle('d-none', checked === 0);
        }
    }

    // ============================================================
    // CRUD OPERATIONS
    // ============================================================

    // ── View Parent ──
    function viewParent(id) {
        viewTargetId = id;
        const content = document.getElementById('viewParentContent');
        if (content) {
            content.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Loading details...</p>
                </div>
            `;
        }
        document.getElementById('viewEditBtn').style.display = 'none';

        showModal('viewParentModal');

        axios.get(`/parents/${id}`)
            .then(response => {
                if (response.data.success) {
                    renderViewContent(response.data.parent);
                }
            })
            .catch(() => {
                if (content) {
                    content.innerHTML = `
                        <div class="u-empty">
                            <i class="bi bi-exclamation-triangle text-danger"></i>
                            Failed to load parent details.
                        </div>
                    `;
                }
            });
    }

    function renderViewContent(p) {
        const content = document.getElementById('viewParentContent');
        if (!content) return;

        const hasFather = p.father && p.father.trim() !== '';
        const hasMother = p.mother && p.mother.trim() !== '';
        const hasFatherPhone = p.father_phone && p.father_phone.trim() !== '';
        const hasMotherPhone = p.mother_phone && p.mother_phone.trim() !== '';

        content.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Student Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="detail-row"><span class="detail-label">Student Name</span><span class="detail-value"><strong>${escapeHtml(p.firstname || '')} ${escapeHtml(p.lastname || '')}</strong></span></div>
                            <div class="detail-row"><span class="detail-label">Admission No</span><span class="detail-value"><span class="badge bg-primary">${escapeHtml(p.admissionNo || 'N/A')}</span></span></div>
                            <div class="detail-row"><span class="detail-label">Gender</span><span class="detail-value">${escapeHtml(p.gender || '—')}</span></div>
                            <div class="detail-row"><span class="detail-label">Class</span><span class="detail-value">${escapeHtml(p.schoolclass || '—')} ${escapeHtml(p.arm || '')}</span></div>
                            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="badge ${p.student_status == 'Active' ? 'bg-success' : 'bg-secondary'}">${escapeHtml(p.student_status || '—')}</span></span></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-envelope me-2"></i>Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="detail-row"><span class="detail-label">Parent Email</span><span class="detail-value">${escapeHtml(p.parent_email || '—')}</span></div>
                            <div class="detail-row"><span class="detail-label">Home Address</span><span class="detail-value">${escapeHtml(p.parent_address || '—')}</span></div>
                            <div class="detail-row"><span class="detail-label">Office Address</span><span class="detail-value">${escapeHtml(p.office_address || '—')}</span></div>
                            <div class="detail-row"><span class="detail-label">Term</span><span class="detail-value">${escapeHtml(p.term_name || '—')}</span></div>
                            <div class="detail-row"><span class="detail-label">Session</span><span class="detail-value">${escapeHtml(p.session_name || '—')}</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Father's Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value"><strong>${escapeHtml(p.father || '—')}</strong></span></div>
                            <div class="detail-row"><span class="detail-label">Title</span><span class="detail-value">${escapeHtml(p.father_title || '—')}</span></div>
                            <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value">${hasFatherPhone ? `<a href="tel:${p.father_phone}" class="phone-link">${p.father_phone}</a>` : '—'}</span></div>
                            <div class="detail-row"><span class="detail-label">Occupation</span><span class="detail-value">${escapeHtml(p.father_occupation || '—')}</span></div>
                            <div class="detail-row"><span class="detail-label">City</span><span class="detail-value">${escapeHtml(p.father_city || '—')}</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Mother's Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value"><strong>${escapeHtml(p.mother || '—')}</strong></span></div>
                            <div class="detail-row"><span class="detail-label">Title</span><span class="detail-value">${escapeHtml(p.mother_title || '—')}</span></div>
                            <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value">${hasMotherPhone ? `<a href="tel:${p.mother_phone}" class="phone-link">${p.mother_phone}</a>` : '—'}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Show edit button
        const editBtn = document.getElementById('viewEditBtn');
        if (editBtn) {
            editBtn.style.display = 'inline-flex';
            editBtn.onclick = function() {
                hideModal('viewParentModal');
                setTimeout(() => editParent(viewTargetId), 300);
            };
        }
    }

    // ── Edit Parent ──
    function editParent(id) {
        Swal.fire({
            title: 'Loading...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        axios.get(`/parents/${id}/edit`)
            .then(response => {
                Swal.close();
                if (response.data.success) {
                    const p = response.data.parent;
                    document.getElementById('editParentId').value = p.id;
                    document.getElementById('editStudentName').textContent = (p.firstname || '') + ' ' + (p.lastname || '');
                    document.getElementById('editAdmissionNo').textContent = p.admissionNo || 'N/A';
                    document.getElementById('editFather').value = p.father || '';
                    document.getElementById('editMother').value = p.mother || '';
                    document.getElementById('editFatherPhone').value = p.father_phone || '';
                    document.getElementById('editMotherPhone').value = p.mother_phone || '';
                    document.getElementById('editFatherOccupation').value = p.father_occupation || '';
                    document.getElementById('editFatherCity').value = p.father_city || '';
                    document.getElementById('editFatherTitle').value = p.father_title || '';
                    document.getElementById('editMotherTitle').value = p.mother_title || '';
                    document.getElementById('editOfficeAddress').value = p.office_address || '';
                    document.getElementById('editParentEmail').value = p.parent_email || '';
                    document.getElementById('editParentAddress').value = p.parent_address || '';
                    document.getElementById('editAlert')?.classList.add('d-none');
                    showModal('editParentModal');
                }
            })
            .catch(() => {
                Swal.close();
                Swal.fire('Error', 'Failed to load parent record', 'error');
            });
    }

    // ── Delete Parent ──
    function confirmDeleteParent(id) {
        deleteTargetId = id;
        const message = document.getElementById('deleteParentMessage');
        if (message) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                const name = row.querySelector('.fw-semibold')?.textContent || 'this record';
                message.textContent = `This will permanently remove the parent record for ${name}.`;
            }
        }
        showModal('deleteParentModal');
    }

    document.getElementById('confirmDeleteParent')?.addEventListener('click', function() {
        if (!deleteTargetId) return;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

        axios.delete(`/parents/${deleteTargetId}`, {
            headers: { 'X-CSRF-TOKEN': CSRF }
        })
        .then(response => {
            if (response.data.success) {
                hideModal('deleteParentModal');
                Swal.fire('Deleted!', response.data.message, 'success');
                fetchParents(currentPage);
            }
        })
        .catch(error => {
            Swal.fire('Error', error.response?.data?.message || 'Delete failed', 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash"></i> Yes, Delete';
            deleteTargetId = null;
        });
    });

    // ── Delete Multiple ──
    window.deleteMultipleParents = function() {
        const ids = Array.from(document.querySelectorAll('.row-check:checked'))
            .map(cb => cb.value).filter(Boolean);

        if (ids.length === 0) {
            Swal.fire('No Selection', 'Please select at least one record.', 'warning');
            return;
        }

        Swal.fire({
            title: `Delete ${ids.length} record(s)?`,
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, Delete All'
        }).then(result => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Deleting...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            axios.post('/parents/destroy-multiple', { ids: ids }, {
                headers: { 'X-CSRF-TOKEN': CSRF }
            })
            .then(response => {
                Swal.close();
                if (response.data.success) {
                    Swal.fire('Deleted!', response.data.message, 'success');
                    fetchParents(currentPage);
                }
            })
            .catch(() => {
                Swal.close();
                Swal.fire('Error', 'Failed to delete records', 'error');
            });
        });
    };

    // ============================================================
    // ADD PARENT FORM
    // ============================================================
    const addParentForm = document.getElementById('addParentForm');
    if (addParentForm) {
        const studentSearch = document.getElementById('studentSearch');
        const studentSelect = document.getElementById('studentSelect');

        function loadStudents(search = '') {
            const params = new URLSearchParams();
            if (search) params.append('search', search);

            axios.get(`/parents/students-without-parent?${params.toString()}`)
                .then(response => {
                    if (response.data.success) {
                        const students = response.data.students || [];
                        studentSelect.innerHTML = '<option value="">— Select a student —</option>';
                        students.forEach(s => {
                            const opt = document.createElement('option');
                            opt.value = s.id;
                            opt.textContent = `${s.lastname || ''} ${s.firstname || ''} (${s.admissionNo || 'N/A'})`;
                            studentSelect.appendChild(opt);
                        });
                    }
                })
                .catch(error => console.error('Error loading students:', error));
        }

        if (studentSearch) {
            let searchTimeout;
            studentSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadStudents(this.value), 300);
            });
        }

        loadStudents();

        // Form submit
        addParentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertEl = document.getElementById('addAlert');
            alertEl?.classList.add('d-none');

            const formData = new FormData(this);
            const btn = document.getElementById('addParentBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            axios.post('/parents', formData, {
                headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': CSRF }
            })
            .then(response => {
                if (response.data.success) {
                    hideModal('addParentModal');
                    Swal.fire('Created!', response.data.message, 'success');
                    fetchParents(currentPage);
                    this.reset();
                    loadStudents();
                }
            })
            .catch(error => {
                const msg = error.response?.data?.message || 'Error creating parent record';
                showAlert(alertEl, msg);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-plus-circle"></i> Create Record';
            });
        });
    }

    // ============================================================
    // EDIT PARENT FORM
    // ============================================================
    const editParentForm = document.getElementById('editParentForm');
    if (editParentForm) {
        editParentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertEl = document.getElementById('editAlert');
            alertEl?.classList.add('d-none');

            const id = document.getElementById('editParentId').value;
            const formData = new FormData(this);
            formData.append('_method', 'PATCH');

            const btn = document.getElementById('updateParentBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

            axios.post(`/parents/${id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': CSRF }
            })
            .then(response => {
                if (response.data.success) {
                    hideModal('editParentModal');
                    Swal.fire('Updated!', response.data.message, 'success');
                    fetchParents(currentPage);
                }
            })
            .catch(error => {
                const msg = error.response?.data?.message || 'Error updating parent record';
                showAlert(alertEl, msg);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Update Record';
            });
        });
    }

    // ============================================================
    // EVENT LISTENERS
    // ============================================================

    // ── Check All ──
    document.getElementById('checkAll')?.addEventListener('change', function() {
        document.querySelectorAll('.row-check').forEach(cb => {
            cb.checked = this.checked;
        });
        updateCheckAllState();
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-check')) {
            updateCheckAllState();
        }
    });

    // ── Filters ──
    ['liveSearch', 'classFilter', 'termFilter', 'sessionFilter', 'phoneFilter'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            const event = id === 'liveSearch' ? 'input' : 'change';
            el.addEventListener(event, () => fetchParents(1));
        }
    });

    // ── Clear Filters ──
    document.getElementById('clearFilters')?.addEventListener('click', function() {
        ['liveSearch', 'classFilter', 'termFilter', 'sessionFilter', 'phoneFilter'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (el.tagName === 'SELECT') el.value = 'all';
                else el.value = '';
            }
        });
        fetchParents(1);
    });

    // ── Refresh ──
    document.getElementById('refreshBtn')?.addEventListener('click', () => fetchParents(currentPage));

    // ── Modal Triggers ──
    ['openAddParentModalBtn', 'openAddParentModalBtnMobile'].forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.addEventListener('click', () => {
                document.getElementById('addParentForm')?.reset();
                document.getElementById('addAlert')?.classList.add('d-none');
                showModal('addParentModal');
                // Reload students
                document.getElementById('studentSearch').value = '';
                loadStudents();
            });
        }
    });

    // ── Modal reset on close ──
    document.getElementById('addParentModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addParentForm')?.reset();
        document.getElementById('addAlert')?.classList.add('d-none');
    });

    document.getElementById('editParentModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('editAlert')?.classList.add('d-none');
    });

    // ── View Edit Button ──
    document.getElementById('viewEditBtn')?.addEventListener('click', function() {
        if (viewTargetId) {
            hideModal('viewParentModal');
            setTimeout(() => editParent(viewTargetId), 300);
        }
    });

    // ── Event delegation for action buttons ──
    document.addEventListener('click', function(e) {
        const viewBtn = e.target.closest('.view-parent-btn');
        if (viewBtn) {
            e.preventDefault();
            viewParent(viewBtn.dataset.id);
            return;
        }

        const editBtn = e.target.closest('.edit-parent-btn');
        if (editBtn) {
            e.preventDefault();
            editParent(editBtn.dataset.id);
            return;
        }

        const deleteBtn = e.target.closest('.delete-parent-btn');
        if (deleteBtn) {
            e.preventDefault();
            confirmDeleteParent(deleteBtn.dataset.id);
            return;
        }
    });

    // ============================================================
    // INITIAL LOAD
    // ============================================================
    fetchParents(1);

    // Expose functions globally
    window.fetchParents = fetchParents;
    window.viewParent = viewParent;
    window.editParent = editParent;
    window.confirmDeleteParent = confirmDeleteParent;
    window.deleteMultipleParents = deleteMultipleParents;

    console.log('Parent management page initialized');
});
</script>

@endsection