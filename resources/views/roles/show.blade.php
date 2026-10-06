@extends('layouts.master')
@section('content')
<?php
use Spatie\Permission\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<style>
:root {
    --rol-primary:  #0f172a;
    --rol-accent:   #6366f1;
    --rol-accent2:  #8b5cf6;
    --rol-success:  #10b981;
    --rol-warning:  #f59e0b;
    --rol-danger:   #ef4444;
    --rol-info:     #0ea5e9;
    --rol-border:   #e2e8f0;
    --rol-surface:  #ffffff;
    --rol-surface2: #f8fafc;
    --rol-muted:    #64748b;
    --rol-radius:   14px;
    --rol-shadow:   0 4px 20px rgba(15,23,42,.07);
    --rol-font:     'Plus Jakarta Sans', sans-serif;
}
*,*::before,*::after{box-sizing:border-box;}
body{font-family:var(--rol-font);}

@keyframes slideDown  { from{opacity:0;transform:translateY(-22px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeUp     { from{opacity:0;transform:translateY(14px)}  to{opacity:1;transform:translateY(0)} }
@keyframes scaleIn    { from{opacity:0;transform:scale(.9)}         to{opacity:1;transform:scale(1)} }
@keyframes pulse      { 0%,100%{transform:scale(1)} 50%{transform:scale(1.06)} }
@keyframes fadeIn     { from{opacity:0} to{opacity:1} }

/* ── Hero ────────────────────────────────────────── */
.rol-hero {
    background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 45%,#312e81 100%);
    border-radius:var(--rol-radius); padding:30px 36px; margin-bottom:24px;
    position:relative; overflow:hidden;
    animation:slideDown .5s cubic-bezier(.22,1,.36,1);
}
.rol-hero::before {
    content:''; position:absolute; top:-80px; right:-80px;
    width:260px; height:260px; background:rgba(99,102,241,.14);
    border-radius:50%; animation:pulse 4s ease-in-out infinite;
}
.rol-hero h1 { font-size:22px; font-weight:800; color:#fff; margin:0 0 5px; position:relative; z-index:1; }
.rol-hero p  { font-size:13px; color:rgba(255,255,255,.65); margin:0; position:relative; z-index:1; }
.rol-hero-actions { position:relative; z-index:1; margin-top:18px; display:flex; gap:8px; flex-wrap:wrap; }

/* ── Buttons ─────────────────────────────────────── */
.rol-btn { display:inline-flex; align-items:center; gap:6px; border:none; border-radius:9px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; font-family:var(--rol-font); transition:transform .15s,box-shadow .15s; text-decoration:none; }
.rol-btn-primary { background:linear-gradient(135deg,var(--rol-accent),var(--rol-accent2)); color:#fff; box-shadow:0 3px 12px rgba(99,102,241,.3); }
.rol-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(99,102,241,.4); color:#fff; }
.rol-btn-success { background:linear-gradient(135deg,#10b981,#059669); color:#fff; box-shadow:0 3px 12px rgba(16,185,129,.3); }
.rol-btn-success:hover { transform:translateY(-1px); color:#fff; }
.rol-btn-danger  { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; box-shadow:0 3px 12px rgba(239,68,68,.3); }
.rol-btn-danger:hover  { transform:translateY(-1px); color:#fff; }
.rol-btn-back    { background:#fff; color:var(--rol-primary); border:1.5px solid var(--rol-border); }
.rol-btn-back:hover { background:var(--rol-surface2); color:var(--rol-primary); }

/* ── Cards ───────────────────────────────────────── */
.rol-card {
    background:var(--rol-surface); border:1px solid var(--rol-border);
    border-radius:var(--rol-radius); overflow:hidden;
    box-shadow:var(--rol-shadow);
    animation:fadeUp .4s ease both;
}
.rol-card:nth-child(1){animation-delay:.05s}
.rol-card:nth-child(2){animation-delay:.1s}
.rol-card-header {
    padding:14px 20px; border-bottom:1px solid var(--rol-border);
    background:var(--rol-surface); display:flex; align-items:center; justify-content:space-between;
}
.rol-card-header h5 { font-size:14px; font-weight:700; color:var(--rol-primary); margin:0; }
.rol-card-body { padding:18px 20px; }

/* ── Permission list (sidebar) ───────────────────── */
.perm-list-item {
    display:flex; align-items:center; gap:8px;
    padding:8px 12px; border-radius:8px; font-size:12.5px; font-weight:500;
    color:var(--rol-primary); transition:background .12s; margin-bottom:2px;
}
.perm-list-item:hover { background:#f0f4ff; }
.perm-list-item i { color:var(--rol-accent); font-size:13px; flex-shrink:0; }

.edit-role-btn {
    width:100%; display:flex; align-items:center; justify-content:center; gap:6px;
    background:#f0f4ff; color:var(--rol-accent); border:1.5px solid #c7d2fe;
    border-radius:9px; padding:9px; font-size:13px; font-weight:600;
    cursor:pointer; transition:all .15s; margin-bottom:16px;
    font-family:var(--rol-font);
}
.edit-role-btn:hover { background:var(--rol-accent); color:#fff; }

/* ── Users table ─────────────────────────────────── */
#usersDataTable { font-size:13px; }
#usersDataTable thead th {
    background:var(--rol-primary); color:#fff; padding:11px 16px;
    font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
    white-space:nowrap; border:none;
}
#usersDataTable tbody td {
    padding:10px 16px; vertical-align:middle;
    border-bottom:1px solid var(--rol-border); transition:background .12s;
}
#usersDataTable tbody tr:hover td { background:#f5f3ff; }
#usersDataTable tbody tr { animation:fadeIn .3s ease both; }

.usr-avatar-sm {
    width:34px; height:34px; border-radius:50%;
    background:linear-gradient(135deg,var(--rol-accent) 0%,var(--rol-accent2) 100%);
    display:inline-flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700; color:#fff;
    border:2px solid var(--rol-border); flex-shrink:0;
}

.rol-checkbox { accent-color:var(--rol-accent); width:15px; height:15px; cursor:pointer; }

/* ── Remove btn in table ─────────────────────────── */
.remove-user-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:30px; height:30px; border-radius:7px;
    background:#fef2f2; color:#dc2626; border:none; cursor:pointer;
    font-size:13px; transition:all .18s;
}
.remove-user-btn:hover { background:#dc2626; color:#fff; transform:scale(1.1); }

/* ── User cards (add modal) ──────────────────────── */
.user-card-sel {
    border:2px solid var(--rol-border); border-radius:10px;
    padding:12px 14px; cursor:pointer; transition:all .18s;
    display:flex; align-items:center; gap:12px; background:#fff;
    margin-bottom:10px;
}
.user-card-sel:hover { border-color:var(--rol-accent); background:#f5f3ff; transform:translateY(-2px); box-shadow:0 4px 12px rgba(99,102,241,.1); }
.user-card-sel.selected { border-color:var(--rol-accent); background:#f0f4ff; }
.user-card-sel .ucs-info { flex:1; min-width:0; }
.user-card-sel .ucs-name { font-size:13px; font-weight:700; color:var(--rol-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-card-sel .ucs-meta { font-size:11px; color:var(--rol-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-card-sel .ucs-check { flex-shrink:0; }
.user-card-sel .ucs-check input { width:16px; height:16px; accent-color:var(--rol-accent); cursor:pointer; }

/* ── Modal - Improved Scrolling ───────────────────────────────────────── */
.rol-modal .modal-dialog-scrollable .modal-content {
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

.rol-modal .modal-dialog-scrollable .modal-header {
    flex-shrink: 0;
}

.rol-modal .modal-dialog-scrollable .modal-body {
    flex: 1;
    overflow-y: auto;
    min-height: 200px;
    max-height: calc(85vh - 130px);
}

.rol-modal .modal-dialog-scrollable .modal-footer {
    flex-shrink: 0;
}

.rol-modal .modal-content {
    border:none; border-radius:18px; overflow:hidden;
    box-shadow:0 24px 64px rgba(15,23,42,.2);
    font-family:var(--rol-font);
    animation:scaleIn .25s cubic-bezier(.22,1,.36,1);
}
.rol-modal .modal-header {
    background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 60%,#312e81 100%);
    padding:20px 24px; border:none;
}
.rol-modal .modal-title { color:#fff; font-weight:700; font-size:16px; }
.rol-modal .modal-header .btn-close { filter:invert(1); opacity:.8; }
.rol-modal .modal-body  { padding:22px 24px; }
.rol-modal .modal-footer { padding:14px 24px; border-top:1px solid var(--rol-border); background:var(--rol-surface2); }

.rol-form-label { font-size:11.5px; font-weight:700; color:var(--rol-muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; display:block; }
.rol-form-control { border:1.5px solid var(--rol-border); border-radius:9px; padding:9px 13px; font-size:13px; font-family:var(--rol-font); width:100%; transition:border-color .2s,box-shadow .2s; }
.rol-form-control:focus { border-color:var(--rol-accent); box-shadow:0 0 0 3px rgba(99,102,241,.12); outline:none; }

/* ── Permission table (edit modal) ──────────────── */
.perm-table { width:100%; border-collapse:separate; border-spacing:0; }
.perm-table thead th {
    background:var(--rol-primary); color:#fff; padding:10px 14px;
    font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
    position: sticky;
    top: 0;
    z-index: 10;
}
.perm-table thead th:first-child { border-radius:8px 0 0 0; }
.perm-table thead th:last-child  { border-radius:0 8px 0 0; }
.perm-table tbody tr:hover td { background:#f5f3ff; }
.perm-table tbody td { padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; vertical-align:middle; }
.perm-table .perm-group { font-weight:700; color:var(--rol-primary); font-size:12.5px; }
.perm-check { accent-color:var(--rol-accent); width:15px; height:15px; cursor:pointer; }

/* Responsive table handling */
@media (max-width: 768px) {
    .perm-table {
        min-width: 600px;
    }
    .perm-table .perm-group {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 5;
    }
    .perm-table tr:hover .perm-group {
        background: #f5f3ff;
    }
}

/* ── Nav tabs ────────────────────────────────────── */
.rol-nav-tabs { border-bottom:2px solid var(--rol-border); margin-bottom:16px; display:flex; gap:4px; }
.rol-nav-tabs .nav-link {
    border:none; background:none; color:var(--rol-muted);
    font-size:13px; font-weight:600; padding:10px 16px; border-radius:8px 8px 0 0;
    cursor:pointer; transition:all .15s; font-family:var(--rol-font);
    position:relative; display:flex; align-items:center; gap:6px;
}
.rol-nav-tabs .nav-link:hover { color:var(--rol-accent); background:#f0f4ff; }
.rol-nav-tabs .nav-link.active { color:var(--rol-accent); background:#f0f4ff; }
.rol-nav-tabs .nav-link.active::after {
    content:''; position:absolute; bottom:-2px; left:0; right:0;
    height:2px; background:var(--rol-accent); border-radius:2px 2px 0 0;
}

/* ── Delete confirm modal ────────────────────────── */
.del-icon-wrap {
    width:70px; height:70px; border-radius:50%;
    background:#fef2f2; border:3px solid #fecaca;
    display:flex; align-items:center; justify-content:center;
    font-size:30px; color:var(--rol-danger); margin:0 auto 16px;
    animation:pulse 1.5s ease infinite;
}

/* ── DataTable overrides ─────────────────────────── */
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--rol-border); border-radius:9px; padding:7px 14px;
    font-size:13px; margin-left:8px; font-family:var(--rol-font);
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--rol-accent); outline:none;
    box-shadow:0 0 0 3px rgba(99,102,241,.1);
}
.dataTables_wrapper .dataTables_info { font-size:12px; color:var(--rol-muted); }
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius:7px !important; font-size:13px !important; padding:4px 10px !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background:var(--rol-accent) !important; border-color:var(--rol-accent) !important; color:#fff !important;
}

/* ── Info banner ─────────────────────────────────── */
.rol-info-banner { background:#eff6ff; border:1px solid #bfdbfe; border-radius:9px; padding:10px 14px; font-size:12.5px; color:#1d4ed8; margin-bottom:14px; }

/* Sticky permission header */
.permission-section-header {
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 10;
    padding: 8px 0 12px 0;
    margin-top: -8px;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="rol-hero">
        <h1><i class="bi bi-shield-fill-check me-2"></i>{{ $role->name }} — Role Details</h1>
        <p>Manage permissions and users assigned to this role.</p>
        <div class="rol-hero-actions">
            <a href="{{ route('roles.index') }}" class="rol-btn rol-btn-back">
                <i class="bi bi-arrow-left"></i> Back to Roles
            </a>
            @can('Update user-role')
            <button type="button" class="rol-btn rol-btn-success" data-bs-toggle="modal" data-bs-target="#addUserModalgrid">
                <i class="bi bi-person-plus-fill"></i> Add Users
            </button>
            @endcan
        </div>
    </div>

    {{-- Alerts --}}
    @if ($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:9px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#b91c1c;">
        <strong>Whoops!</strong>
        <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul>
    </div>
    @endif
    @if (session('success') || session('status'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#16a34a;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') ?? session('status') }}
    </div>
    @endif

    <div class="row g-3">

        {{-- ── Permissions sidebar ── --}}
        <div class="col-xl-3 col-lg-4">
            <div class="rol-card">
                <div class="rol-card-header">
                    <h5><i class="bi bi-key-fill me-2" style="color:var(--rol-accent)"></i>Permissions</h5>
                    <span style="background:#f0f4ff;color:var(--rol-accent);border:1px solid #c7d2fe;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
                        {{ $rolePermissions->count() }}
                    </span>
                </div>
                <div class="rol-card-body">
                    <button type="button" class="edit-role-btn" data-bs-toggle="modal" data-bs-target="#editRoleModalgrid">
                        <i class="bi bi-pencil-square"></i> Edit Role & Permissions
                    </button>
                    @forelse($rolePermissions as $rm)
                    <div class="perm-list-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ $rm->name }}</span>
                    </div>
                    @empty
                    <p style="font-size:13px;color:var(--rol-muted);text-align:center;padding:20px 0;">No permissions assigned</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Users table ── --}}
        <div class="col-xl-9 col-lg-8">
            <div class="rol-card">
                <div class="rol-card-header">
                    <h5>
                        <i class="bi bi-people-fill me-2" style="color:var(--rol-accent)"></i>
                        Assigned Users
                        <span id="totalUsersCount"
                              style="background:var(--rol-accent);color:#fff;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;margin-left:6px;">
                            {{ $userRoleCount }}
                        </span>
                    </h5>
                    @can('Remove user-role')
                    <button type="button" class="rol-btn rol-btn-danger" id="bulkRemoveBtn"
                            style="display:none;padding:7px 14px;font-size:12.5px;">
                        <i class="bi bi-person-x-fill"></i> Remove Selected
                    </button>
                    @endcan
                </div>
                <div class="rol-card-body" style="padding:0;">
                    <div class="table-responsive">
                        <table id="usersDataTable" class="table w-100 mb-0">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" class="rol-checkbox" id="checkAll">
                                    </th>
                                    <th width="50">Avatar</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    @can('Remove user-role')
                                    <th width="80">Action</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                @foreach($usersWithRole as $u)
                                @php
                                    $parts    = explode(' ', $u->username ?? '');
                                    $initials = strtoupper(substr($parts[0],0,1).(count($parts)>1?substr(end($parts),0,1):''));
                                @endphp
                                <tr data-user-id="{{ $u->id }}">
                                    <td>
                                        <input type="checkbox" class="rol-checkbox user-checkbox" value="{{ $u->id }}"
                                               data-user-name="{{ $u->username }}">
                                    </td>
                                    <td><div class="usr-avatar-sm">{{ $initials }}</div></td>
                                    <td>
                                        <span style="font-weight:600;font-size:13.5px;color:var(--rol-primary);">
                                            {{ $u->username }}
                                        </span>
                                    </td>
                                    <td style="font-size:12px;color:var(--rol-muted);">{{ $u->email }}</td>
                                    <td style="font-size:12px;color:var(--rol-muted);">
                                        {{ \Carbon\Carbon::parse($u->created_at)->format('d M Y') }}
                                    </td>
                                    @can('Remove user-role')
                                    <td>
                                        <button type="button" class="remove-user-btn"
                                                data-user-id="{{ $u->id }}"
                                                data-user-name="{{ $u->username }}"
                                                data-url="/roles/{{ $u->id }}/{{ $role->id }}/removeuser"
                                                data-bs-toggle="modal" data-bs-target="#deleteRecordModal"
                                                title="Remove from role">
                                            <i class="bi bi-person-x-fill"></i>
                                        </button>
                                    </td>
                                    @endcan
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /row --}}

    {{-- ════ DELETE SINGLE ════ --}}
    <div id="deleteRecordModal" class="modal fade rol-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content">
                <div class="modal-body" style="padding:36px 28px;text-align:center;">
                    <div class="del-icon-wrap"><i class="bi bi-person-x-fill"></i></div>
                    <h4 style="font-weight:800;color:var(--rol-primary);margin-bottom:8px;">Remove User?</h4>
                    <p id="singleDeleteMessage" style="color:var(--rol-muted);font-size:14px;">
                        Remove this user from the role?
                    </p>
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="rol-btn rol-btn-danger" id="delete-record" style="padding:9px 22px;">
                            <i class="bi bi-person-dash-fill"></i> Yes, Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════ BULK REMOVE ════ --}}
    <div id="bulkRemoveModal" class="modal fade rol-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-people-fill me-2"></i>Remove Multiple Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="del-icon-wrap" style="width:56px;height:56px;font-size:24px;"><i class="bi bi-people-fill"></i></div>
                    <p style="text-align:center;color:var(--rol-muted);font-size:13.5px;margin-bottom:12px;">
                        Remove <strong id="selectedCount">0</strong> user(s) from <strong>{{ $role->name }}</strong>?
                    </p>
                    <div id="selectedUsersList" style="max-height:160px;overflow-y:auto;background:#f8fafc;border-radius:9px;padding:10px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="rol-btn rol-btn-danger" id="confirmBulkRemove">
                        <i class="bi bi-trash3"></i> Remove All
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ════ ADD USER MODAL ════ --}}
    <div class="modal fade rol-modal" id="addUserModalgrid" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Add Users to "{{ $role->name }}"</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addUserRoleForm" action="{{ route('roles.updateuserrole') }}" method="POST">
                    @csrf
                    <input type="hidden" name="roleid" value="{{ $role->id }}">
                    <div class="modal-body">

                        {{-- Tabs --}}
                        <div class="rol-nav-tabs" role="tablist">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#staff-tab" type="button">
                                <i class="fas fa-user-tie"></i> Staff
                                <span style="background:var(--rol-accent);color:#fff;border-radius:12px;padding:1px 8px;font-size:10px;" id="staff-count">0</span>
                            </button>
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#student-tab" type="button">
                                <i class="fas fa-user-graduate"></i> Students
                                <span style="background:var(--rol-success);color:#fff;border-radius:12px;padding:1px 8px;font-size:10px;" id="student-count">0</span>
                            </button>
                        </div>

                        <div class="rol-info-banner">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            Selected: <strong id="selected-count">0</strong> user(s).
                            Click a card or checkbox to select.
                        </div>

                        <div class="tab-content">
                            {{-- STAFF --}}
                            <div class="tab-pane fade show active" id="staff-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span style="font-size:12px;font-weight:700;color:var(--rol-muted);text-transform:uppercase;letter-spacing:.5px;">Staff Members</span>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12.5px;font-weight:600;color:var(--rol-accent);">
                                        <input type="checkbox" class="rol-checkbox" id="select-all-staff">
                                        Select All
                                    </label>
                                </div>
                                <div class="row g-2" id="staff-list-container">
                                    @php
                                        $allUsersNotInRole = \App\Models\User::whereDoesntHave('roles', function ($q) use ($role) {
                                            $q->where('name', $role->name);
                                        })->get();
                                        $staffUsers = $allUsersNotInRole->filter(function ($user) {
                                            if ($user->staffemploymentDetails) return true;
                                            if ($user->staffPicture) return true;
                                            if (!$user->student_id) return true;
                                            return false;
                                        })->reject(function ($user) {
                                            if ($user->student) return true;
                                            return false;
                                        })->sortBy('name');
                                    @endphp
                                    @forelse($staffUsers as $staff)
                                    @php $sInit = strtoupper(substr($staff->name,0,1)); @endphp
                                    <div class="col-xl-4 col-lg-6">
                                        <div class="user-card-sel" data-user-id="{{ $staff->id }}">
                                            <div class="usr-avatar-sm">{{ $sInit }}</div>
                                            <div class="ucs-info">
                                                <div class="ucs-name">{{ $staff->name }}</div>
                                                <div class="ucs-meta">{{ $staff->email }}</div>
                                            </div>
                                            <div class="ucs-check">
                                                <input type="checkbox" value="{{ $staff->id }}" name="users[]"
                                                       class="staff-checkbox user-checkbox" id="staff-{{ $staff->id }}">
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-12"><div class="rol-info-banner">No staff members available.</div></div>
                                    @endforelse
                                </div>
                            </div>

                            {{-- STUDENTS --}}
                            <div class="tab-pane fade" id="student-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span style="font-size:12px;font-weight:700;color:var(--rol-muted);text-transform:uppercase;letter-spacing:.5px;">Students</span>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12.5px;font-weight:600;color:var(--rol-accent);">
                                        <input type="checkbox" class="rol-checkbox" id="select-all-students">
                                        Select All
                                    </label>
                                </div>
                                <div class="row g-2" id="student-list-container">
                                    @php
                                        $studentUsers = $allUsersNotInRole->filter(function ($user) {
                                            return $user->student_id || $user->student;
                                        })->sortBy('name');
                                    @endphp
                                    @forelse($studentUsers as $studentUser)
                                    @php $stInit = strtoupper(substr($studentUser->name,0,1)); @endphp
                                    <div class="col-xl-4 col-lg-6">
                                        <div class="user-card-sel" data-user-id="{{ $studentUser->id }}">
                                            <div class="usr-avatar-sm" style="background:linear-gradient(135deg,#10b981,#059669);">{{ $stInit }}</div>
                                            <div class="ucs-info">
                                                <div class="ucs-name">{{ $studentUser->name }}</div>
                                                <div class="ucs-meta">
                                                    @if($studentUser->student?->admissionNo)
                                                        {{ $studentUser->student->admissionNo }}
                                                    @else
                                                        {{ $studentUser->email }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="ucs-check">
                                                <input type="checkbox" value="{{ $studentUser->id }}" name="users[]"
                                                       class="student-checkbox user-checkbox" id="student-{{ $studentUser->id }}">
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-12"><div class="rol-info-banner">No students available.</div></div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="rol-btn rol-btn-primary" id="submit-add-btn">
                            <i class="bi bi-person-check-fill"></i> Add Selected Users
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ════ EDIT ROLE MODAL (IMPROVED SCROLLABLE) ════ --}}
    <div class="modal fade rol-modal" id="editRoleModalgrid" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Role: {{ $role->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('roles.update', $role->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="rol-form-label">Role Name</label>
                                <input type="text" class="rol-form-control" name="name" value="{{ old('name',$role->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="rol-form-label">Badge Colour</label>
                                <select name="badge" class="rol-form-control">
                                    <option value="">None</option>
                                    <option value="badge bg-light"     {{ $role->badge=='badge bg-light'?'selected':'' }}>Light Grey</option>
                                    <option value="badge bg-dark"      {{ $role->badge=='badge bg-dark'?'selected':'' }}>Dark</option>
                                    <option value="badge bg-primary"   {{ $role->badge=='badge bg-primary'?'selected':'' }}>Blue</option>
                                    <option value="badge bg-secondary" {{ $role->badge=='badge bg-secondary'?'selected':'' }}>Light Blue</option>
                                    <option value="badge bg-success"   {{ $role->badge=='badge bg-success'?'selected':'' }}>Green</option>
                                    <option value="badge bg-info"      {{ $role->badge=='badge bg-info'?'selected':'' }}>Purple</option>
                                    <option value="badge bg-warning"   {{ $role->badge=='badge bg-warning'?'selected':'' }}>Yellow</option>
                                    <option value="badge bg-danger"    {{ $role->badge=='badge bg-danger'?'selected':'' }}>Red</option>
                                </select>
                            </div>
                        </div>

                        <label class="rol-form-label mb-2">Permissions Assignment</label>
                        <div style="background:#f0f4ff;border:1px solid #c7d2fe;border-radius:9px;padding:8px 12px;font-size:12px;color:#3730a3;margin-bottom:12px;">
                            <i class="bi bi-info-circle-fill me-1"></i> Tick the permissions for this role. A group's checkbox selects everything under it at once; hover a permission to read what it does.
                        </div>

                        @include('roles.partials.permission-picker', ['pickerId' => 'editPermPicker', 'checkedIds' => $role->permissions->pluck('id')->all()])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="rol-btn rol-btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── DataTable ─────────────────────────────────────────────────
    $('#usersDataTable').DataTable({
        pageLength: 25,
        order: [[2,'asc']],
        language: {
            search: '', searchPlaceholder:'Search users…',
            info:'Showing _START_–_END_ of _TOTAL_ users',
            zeroRecords:'No users found',
        },
        columnDefs:[{orderable:false, targets:[0,1,5]}],
        drawCallback: bindTableEvents,
    });

    function bindTableEvents() {
        // Individual remove buttons
        document.querySelectorAll('.remove-user-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const url      = this.dataset.url;
                const userName = this.dataset.userName;
                document.getElementById('singleDeleteMessage').textContent =
                    `Remove "${userName}" from the ${document.title.split('—')[0].trim()} role?`;
                document.getElementById('delete-record').onclick = function () {
                    doRemoveSingle(url);
                };
            });
        });

        // Row checkboxes
        document.querySelectorAll('.user-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkBtn);
        });
    }

    function doRemoveSingle(url) {
        const btn = document.getElementById('delete-record');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Removing…';
        fetch(url, {
            method:'DELETE',
            headers:{
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept':'application/json'
            }
        }).then(r=>r.json()).then(data=>{
            if(data.success){
                Swal.fire({icon:'success',title:'Removed!',text:data.message,
                    showConfirmButton:false,timer:1800,confirmButtonColor:'#6366f1'});
                bootstrap.Modal.getInstance('#deleteRecordModal')?.hide();
                setTimeout(()=>location.reload(),1800);
            } else {
                Swal.fire({icon:'error',title:'Error',text:data.message||'Failed',confirmButtonColor:'#6366f1'});
            }
        }).catch(()=>Swal.fire({icon:'error',title:'Network Error',confirmButtonColor:'#6366f1'}))
          .finally(()=>{ btn.disabled=false; btn.innerHTML='<i class="bi bi-person-dash-fill"></i> Yes, Remove'; });
    }

    // ── Bulk remove ───────────────────────────────────────────────
    function updateBulkBtn() {
        const checked = document.querySelectorAll('#usersDataTable .user-checkbox:checked').length;
        const btn = document.getElementById('bulkRemoveBtn');
        if (btn) {
            btn.style.display = checked > 0 ? 'inline-flex' : 'none';
            btn.innerHTML = `<i class="bi bi-person-x-fill"></i> Remove Selected (${checked})`;
        }
        const checkAll = document.getElementById('checkAll');
        const all = document.querySelectorAll('#usersDataTable .user-checkbox').length;
        if (checkAll) { checkAll.checked = all > 0 && all === checked; }
    }

    document.getElementById('checkAll')?.addEventListener('change', function() {
        document.querySelectorAll('#usersDataTable .user-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkBtn();
    });

    document.getElementById('bulkRemoveBtn')?.addEventListener('click', function() {
        const checked = document.querySelectorAll('#usersDataTable .user-checkbox:checked');
        if (!checked.length) return;
        document.getElementById('selectedCount').textContent = checked.length;
        const listEl = document.getElementById('selectedUsersList');
        listEl.innerHTML = '';
        checked.forEach(cb => {
            const d = document.createElement('div');
            d.style = 'padding:5px 8px;background:#fff;border-radius:7px;margin-bottom:4px;font-size:12.5px;display:flex;align-items:center;gap:6px;';
            d.innerHTML = `<i class="bi bi-person-fill" style="color:var(--rol-accent)"></i>${cb.dataset.userName}`;
            listEl.appendChild(d);
        });
        new bootstrap.Modal('#bulkRemoveModal').show();
    });

    document.getElementById('confirmBulkRemove')?.addEventListener('click', function() {
        const ids = Array.from(document.querySelectorAll('#usersDataTable .user-checkbox:checked')).map(c=>c.value);
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Removing…';
        fetch('{{ route("roles.bulkremoveusers") }}', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,
                'Accept':'application/json'
            },
            body:JSON.stringify({ role_id:'{{ $role->id }}', selected_users:ids })
        }).then(r=>r.json()).then(data=>{
            if(data.success){
                Swal.fire({icon:'success',title:'Done!',text:data.message,
                    showConfirmButton:false,timer:1800});
                bootstrap.Modal.getInstance('#bulkRemoveModal')?.hide();
                setTimeout(()=>location.reload(),1800);
            } else {
                Swal.fire({icon:'error',title:'Error',text:data.message||'Failed',confirmButtonColor:'#6366f1'});
            }
        }).catch(()=>Swal.fire({icon:'error',title:'Network error',confirmButtonColor:'#6366f1'}))
          .finally(()=>{ btn.disabled=false; btn.innerHTML='<i class="bi bi-trash3"></i> Remove All'; });
    });

    // ── Add user modal ────────────────────────────────────────────
    const addModal = document.getElementById('addUserModalgrid');
    if (addModal) {
        addModal.addEventListener('shown.bs.modal', initAddUserModal);
        addModal.addEventListener('hidden.bs.modal', function() {
            document.querySelectorAll('#addUserModalgrid .user-checkbox').forEach(c=>{
                c.checked=false;
                c.closest('.user-card-sel')?.classList.remove('selected');
            });
            document.getElementById('selected-count').textContent='0';
        });
    }

    function initAddUserModal() {
        const staffCbs   = document.querySelectorAll('#addUserModalgrid .staff-checkbox');
        const studentCbs = document.querySelectorAll('#addUserModalgrid .student-checkbox');
        const allCbs     = document.querySelectorAll('#addUserModalgrid .user-checkbox');

        document.getElementById('staff-count').textContent   = staffCbs.length;
        document.getElementById('student-count').textContent = studentCbs.length;

        function updateCount() {
            const n = document.querySelectorAll('#addUserModalgrid .user-checkbox:checked').length;
            document.getElementById('selected-count').textContent = n;
        }

        // Card click
        document.querySelectorAll('#addUserModalgrid .user-card-sel').forEach(card => {
            card.onclick = function(e) {
                if (e.target.type === 'checkbox') return;
                const cb = this.querySelector('input[type=checkbox]');
                if (cb) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')); }
            };
        });

        allCbs.forEach(cb => {
            cb.addEventListener('change', function() {
                this.closest('.user-card-sel')?.classList.toggle('selected', this.checked);
                updateCount();
            });
        });

        // Select all staff
        document.getElementById('select-all-staff')?.addEventListener('change', function() {
            staffCbs.forEach(c=>{ c.checked=this.checked; c.closest('.user-card-sel')?.classList.toggle('selected',this.checked); });
            updateCount();
        });

        // Select all students
        document.getElementById('select-all-students')?.addEventListener('change', function() {
            studentCbs.forEach(c=>{ c.checked=this.checked; c.closest('.user-card-sel')?.classList.toggle('selected',this.checked); });
            updateCount();
        });

        // Submit validation
        document.getElementById('addUserRoleForm').addEventListener('submit', function(e) {
            if (!document.querySelectorAll('#addUserModalgrid .user-checkbox:checked').length) {
                e.preventDefault();
                Swal.fire({icon:'warning',title:'No Users Selected',
                    text:'Please select at least one user.',confirmButtonColor:'#6366f1'});
                return;
            }
            const btn = document.getElementById('submit-add-btn');
            btn.disabled=true;
            btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Adding…';
        });
    }

    // ── Edit role permissions select-all (UPDATED ID) ──────────────────────────
    const selectAllEdit = document.getElementById('kt_roles_select_all_edit');
    const permBoxesEdit = document.querySelectorAll('#editRoleModalgrid input[name="permission[]"]');
    if (selectAllEdit && permBoxesEdit.length) {
        selectAllEdit.addEventListener('change', function() {
            permBoxesEdit.forEach(c=>c.checked=this.checked);
        });
        function syncAllEdit() {
            const all  = Array.from(permBoxesEdit).every(c=>c.checked);
            const some = Array.from(permBoxesEdit).some(c=>c.checked);
            selectAllEdit.checked       = all;
            selectAllEdit.indeterminate = some && !all;
        }
        permBoxesEdit.forEach(c=>c.addEventListener('change',syncAllEdit));
        syncAllEdit();
    }

    bindTableEvents();
});
</script>

@endsection
