@extends('layouts.master')

@section('content')
<?php use Spatie\Permission\Models\Role; ?>

{{-- ═══════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════ --}}
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

/* ── Keyframes ── */
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

/* ── Role badges ── */
.u-role-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
    animation: badgePop .3s ease;
}
.u-role-pill.student  { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.u-role-pill.admin    { background:#fce7f3; color:#9d174d; border:1px solid #fbcfe8; }
.u-role-pill.teacher  { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.u-role-pill.staff    { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.u-role-pill.default  { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

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
.u-action-btn.key    { background:#fffbeb; color:#d97706; }
.u-action-btn.del    { background:#fef2f2; color:#dc2626; }

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
.u-modal-body { padding: 22px 24px; background: var(--u-bg); }
.u-modal-footer { padding: 14px 24px; background: var(--u-surface); border-top:1px solid var(--u-border); }

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

/* ── Empty state ── */
.u-empty {
    text-align:center; padding:48px 24px; color: var(--u-muted);
}
.u-empty i { font-size:3rem; display:block; margin-bottom:12px; opacity:.3; }

/* ── Loading shimmer ── */
.shimmer {
    background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 6px;
    height: 16px;
}

/* ── Chart card ── */
.u-chart-card {
    background: var(--u-surface);
    border: 1px solid var(--u-border);
    border-radius: var(--u-radius);
    overflow: hidden;
    box-shadow: var(--u-shadow);
    animation: fadeInUp .5s .05s ease both;
    margin-bottom: 24px;
}
.u-chart-card .chart-header {
    padding: 14px 20px;
    background: var(--u-surface);
    border-bottom: 1px solid var(--u-border);
    font-weight: 700; font-size: 14px; color: var(--u-primary);
    display: flex; align-items: center; gap: 8px;
}

/* ── Stagger animation for rows ── */
.u-table tbody tr:nth-child(1)  { animation-delay: .03s; }
.u-table tbody tr:nth-child(2)  { animation-delay: .06s; }
.u-table tbody tr:nth-child(3)  { animation-delay: .09s; }
.u-table tbody tr:nth-child(4)  { animation-delay: .12s; }
.u-table tbody tr:nth-child(5)  { animation-delay: .15s; }
.u-table tbody tr:nth-child(6)  { animation-delay: .18s; }
.u-table tbody tr:nth-child(7)  { animation-delay: .21s; }
.u-table tbody tr:nth-child(8)  { animation-delay: .24s; }
.u-table tbody tr:nth-child(9)  { animation-delay: .27s; }
.u-table tbody tr:nth-child(10) { animation-delay: .30s; }

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

</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Breadcrumb --}}
    <div class="row mb-1" style="animation:fadeInDown .4s ease both;">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 small">
                    <li class="breadcrumb-item"><a href="#" style="color:var(--u-accent)">User Management</a></li>
                    <li class="breadcrumb-item active text-muted">Users</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Hero --}}
    <div class="u-hero">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="ri-team-line me-2"></i>User Management</h1>
                <p>Manage system users, roles, and student portal access from one place.</p>
            </div>
            <div class="col-auto d-none d-md-flex gap-2">
                @can('Create user')
                <button type="button" class="u-btn primary" id="openAddUserModalBtn">
                    <i class="bi bi-plus-circle"></i> Add User
                </button>
                @can('Create user')
                    <button type="button" class="u-btn ghost" data-bs-toggle="modal" data-bs-target="#generateStaffTemplateModal">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Staff Template
                    </button>
                    <button type="button" class="u-btn success" data-bs-toggle="modal" data-bs-target="#importStaffModal">
                        <i class="bi bi-upload"></i> Import Staff
                    </button>
                @endcan
                <button type="button" class="u-btn success" id="openAddStudentModalBtn">
                    <i class="bi bi-person-plus"></i> Add Student
                </button>
                <button type="button" class="u-btn warning" id="openMassStudentModalBtn">
                    <i class="bi bi-people-fill"></i> Mass Manage
                </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if ($errors->any())
    <div class="alert alert-danger border-0 rounded-3 mb-3" style="animation:fadeInDown .4s ease both;">
        <strong>Input errors:</strong> {{ $errors->all()[0] }}
    </div>
    @endif
    @if (session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-3" style="animation:fadeInDown .4s ease both;">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3" style="animation-delay:.05s">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value">{{ $data->count() }}</div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        @foreach(array_slice($role_counts, 0, 3, true) as $role => $count)
        <div class="col-6 col-md-3" style="animation-delay:{{ .08 + $loop->index * .03 }}s">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-shield-user-line"></i></div>
                <div class="stat-value" style="color:var(--u-accent)">{{ $count }}</div>
                <div class="stat-label">{{ $role }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Chart --}}
    <div class="u-chart-card">
        <div class="chart-header">
            <i class="ri-bar-chart-2-line" style="color:var(--u-accent)"></i>
            Users by Role
        </div>
        <div class="p-4">
            <canvas id="usersByRoleChart" height="80"></canvas>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="u-filter-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="u-form-label">Search</label>
                <div class="u-input-icon-wrap">
                    <i class="bi bi-search u-input-icon"></i>
                    <input type="text" id="liveSearch" class="u-input" placeholder="Name, email…">
                </div>
            </div>
            <div class="col-md-3">
                <label class="u-form-label">Role</label>
                <select id="roleFilter" class="u-input">
                    <option value="">All Roles</option>
                    @foreach ($roles as $role => $name)
                    <option value="{{ strtolower($name) }}">{{ $name }}</option>
                    @endforeach
                    <option value="no role">No Role</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="u-form-label">Email</label>
                <select id="emailFilter" class="u-input">
                    <option value="">All Emails</option>
                    @foreach ($data as $user)
                    <option value="{{ strtolower($user->email) }}">{{ $user->email }}</option>
                    @endforeach
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
                Users
                <span id="userCountBadge" class="badge ms-2" style="background:var(--u-accent);font-size:11px;font-weight:600;">{{ $data->count() }}</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button class="u-btn danger d-none" id="remove-actions" onclick="deleteMultiple()">
                    <i class="ri-delete-bin-2-line"></i> Delete Selected
                </button>
                @can('Create user')
                <div class="d-md-none d-flex gap-2">
                    <button type="button" class="u-btn primary" id="openAddUserModalBtnMobile">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                    <button type="button" class="u-btn success" id="openAddStudentModalBtnMobile">
                        <i class="bi bi-person-plus"></i>
                    </button>
                    <button type="button" class="u-btn warning" id="openMassStudentModalBtnMobile">
                        <i class="bi bi-people-fill"></i>
                    </button>
                </div>
                @endcan
            </div>
        </div>

        <div class="table-responsive">
            <table class="table u-table mb-0" id="usersTable">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="checkAll" style="accent-color:#fff;cursor:pointer;">
                        </th>
                        <th width="50"></th>
                        <th class="sortable" data-col="0">Name <i class="bi bi-chevron-expand ms-1 opacity-50"></i></th>
                        <th class="sortable" data-col="1">Email <i class="bi bi-chevron-expand ms-1 opacity-50"></i></th>
                        <th>Role</th>
                        <th class="sortable" data-col="4">Registered <i class="bi bi-chevron-expand ms-1 opacity-50"></i></th>
                        <th width="130">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    @forelse ($data as $key => $user)
                    @php
                        $initials = strtoupper(substr($user->name,0,1) . (strpos($user->name,' ')!==false ? substr($user->name, strpos($user->name,' ')+1, 1) : ''));
                        $roleNames = $user->getRoleNames();
                        $colors = ['student'=>'student','admin'=>'admin','teacher'=>'teacher','staff'=>'staff'];
                        $avatarColors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#30cfd0'];
                        $avatarColor = $avatarColors[$key % count($avatarColors)];
                    @endphp
                    <tr data-id="{{ $user->id }}"
                        data-name="{{ strtolower($user->name) }}"
                        data-email="{{ strtolower($user->email) }}"
                        data-roles="{{ strtolower($roleNames->implode(',')) }}"
                        data-date="{{ $user->created_at->format('Y-m-d') }}">
                        <td>
                            <input type="checkbox" class="row-check" name="chk_child" style="accent-color:var(--u-accent);cursor:pointer;">
                        </td>
                        <td>
                            <div class="u-avatar" style="background:linear-gradient(135deg,{{ $avatarColor }} 0%,{{ $avatarColors[($key+2)%count($avatarColors)] }} 100%)">
                                {{ $initials ?: 'U' }}
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold" style="color:var(--u-primary)">
                                <a href="{{ route('users.show', $user->id) }}" style="color:inherit;text-decoration:none;">
                                    {{ $user->name }}
                                </a>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted small">{{ $user->email }}</span>
                        </td>
                        <td>
                            @if($roleNames->isNotEmpty())
                                @foreach($roleNames as $role)
                                    @php $rkey = strtolower($role); $cls = $colors[$rkey] ?? 'default'; @endphp
                                    <span class="u-role-pill {{ $cls }}">
                                        <i class="bi bi-shield-check"></i>{{ $role }}
                                    </span>
                                @endforeach
                            @else
                                <span class="u-role-pill default"><i class="bi bi-dash"></i>No Role</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $user->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                @can('View user')
                                <a href="{{ route('users.show', $user->id) }}" class="u-action-btn view" title="View">
                                    <i class="ph-eye"></i>
                                </a>
                                @endcan
                                @can('Update user')
                                <button type="button" class="u-action-btn edit edit-item-btn" title="Edit"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-roles="{{ $user->getRoleNames()->implode(',') }}">
                                    <i class="ph-pencil"></i>
                                </button>
                                @endcan
                                @can('Update user')
                                @if($user->hasRole('Student'))
                                <button type="button" class="u-action-btn key reset-student-pwd-btn" title="Reset Password"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}">
                                    <i class="bi bi-key-fill"></i>
                                </button>
                                @endif
                                @endcan
                                @can('Delete user')
                                <button type="button" class="u-action-btn del remove-item-btn" title="Delete"
                                    data-id="{{ $user->id }}">
                                    <i class="ph-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="7">
                            <div class="u-empty">
                                <i class="ri-user-line"></i>
                                No users found
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination / count --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top"
             style="background:var(--u-bg);">
            <div class="text-muted small">
                Showing <span id="showingCount" class="fw-semibold text-dark">{{ $data->count() }}</span>
                of <span id="totalCount" class="fw-semibold text-dark">{{ $data->count() }}</span> users
            </div>
            <div id="paginationEl" class="u-pagination"></div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         ADD USER MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="showModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="u-modal-hero">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h5><i class="bi bi-person-plus me-2"></i>Add New User</h5>
                    <p>Create a system user with role-based access</p>
                </div>
                <form id="add-user-form" autocomplete="off">
                    <div class="u-modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="u-form-label">Full Name</label>
                                <input type="text" id="name" name="name" class="u-form-input" placeholder="e.g. John Adeyemi" required>
                            </div>
                            <div class="col-12">
                                <label class="u-form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="u-form-input" placeholder="user@school.ng" required>
                            </div>
                            <div class="col-12">
                                <label class="u-form-label">Role(s)</label>
                                <select id="role" name="roles[]" class="u-form-input" multiple required>
                                    @foreach (Role::all() as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <div class="small text-muted mt-1">Hold Ctrl/Cmd to select multiple</div>
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Password</label>
                                <input type="password" id="password" name="password" class="u-form-input" placeholder="Min. 8 chars" required>
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="u-form-input" placeholder="Repeat password" required>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3 rounded-3" id="add-alert"></div>
                    </div>
                    <div class="u-modal-footer d-flex justify-content-end gap-2">
                        <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="u-btn primary" id="add-btn">
                            <i class="bi bi-plus-circle"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade u-modal" id="generateStaffTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="u-modal-hero">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="bi bi-file-earmark-spreadsheet me-2"></i>Generate Staff Template</h5>
                <p>Download a blank Excel file for bulk staff creation</p>
            </div>
            <div class="u-modal-body">
                <div class="mb-3">
                    <label class="u-form-label">Number of blank rows</label>
                    <input type="number" id="staff_tpl_rows" class="u-form-input" value="30" min="1" max="200">
                </div>
                <div class="alert alert-info small mb-0">
                    Role is locked to <strong>Staff</strong>. Only fill Name, Email and Password.
                </div>
            </div>
            <div class="u-modal-footer">
                <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Close</button>
                <button type="button" class="u-btn primary" id="downloadStaffTemplateBtn">
                    <i class="bi bi-download me-1"></i> Download Template
                </button>
            </div>
        </div>
    </div>
</div>



<div class="modal fade u-modal" id="importStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="u-modal-hero">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="bi bi-upload me-2"></i>Import Staff Users</h5>
                <p>Upload the filled template to create Staff accounts</p>
            </div>
            <form id="import-staff-form" enctype="multipart/form-data">
                @csrf
                <div class="u-modal-body">
                    <div class="mb-3">
                        <label class="u-form-label">Excel File (.xlsx / .xls / .csv)</label>
                        <input type="file" name="filesheet" id="staff_filesheet" class="u-form-input" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="alert alert-warning small">
                        Only users with the <strong>Staff</strong> role will be created. Existing emails are skipped.
                    </div>
                    <div id="staff-import-result" class="d-none"></div>
                </div>
                <div class="u-modal-footer">
                    <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="u-btn success" id="importStaffBtn">
                        <i class="bi bi-check-circle me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



    {{-- ══════════════════════════════════════════════════════
         EDIT USER MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="editModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="u-modal-hero" style="background:linear-gradient(135deg,#065f46,#16a34a,#4ade80);">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h5><i class="ph-pencil me-2"></i>Edit User</h5>
                    <p>Update user information and permissions</p>
                </div>
                <form id="edit-user-form" autocomplete="off">
                    <div class="u-modal-body">
                        <input type="hidden" id="edit-id-field">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="u-form-label">Full Name</label>
                                <input type="text" id="edit-name" name="name" class="u-form-input" required>
                            </div>
                            <div class="col-12">
                                <label class="u-form-label">Email Address</label>
                                <input type="email" id="edit-email" name="email" class="u-form-input" required>
                            </div>
                            <div class="col-12">
                                <label class="u-form-label">Role(s)</label>
                                <select id="edit-role" name="roles[]" class="u-form-input" multiple required>
                                    @foreach (Role::all() as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">New Password <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="password" id="edit-password" name="password" class="u-form-input" placeholder="Leave blank to keep">
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Confirm Password</label>
                                <input type="password" id="edit-password_confirmation" name="password_confirmation" class="u-form-input" placeholder="Repeat if changing">
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3 rounded-3" id="edit-alert"></div>
                    </div>
                    <div class="u-modal-footer d-flex justify-content-end gap-2">
                        <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="u-btn success" id="update-btn">
                            <i class="bi bi-check-circle"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         DELETE CONFIRM MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="deleteRecordModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <div style="width:70px;height:70px;border-radius:50%;background:#fef2f2;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="bi bi-trash" style="font-size:28px;color:var(--u-danger)"></i>
                    </div>
                    <h4 class="fw-700" style="color:var(--u-primary)">Confirm Delete</h4>
                    <p class="text-muted mb-4">This action cannot be undone. The user will be permanently removed.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="u-btn danger" id="delete-record">
                            <i class="bi bi-trash"></i> Yes, Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         ADD STUDENT (single) MODAL
    ══════════════════════════════════════════════════════ --}}
    <div id="addStudentModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="u-modal-hero" style="background:linear-gradient(135deg,#064e3b,#059669,#34d399);">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h5><i class="bi bi-mortarboard me-2"></i>Add Student as User</h5>
                    <p>Create portal access for a registered student</p>
                </div>
                <div class="u-modal-body">
                    <div class="mb-3">
                        <label class="u-form-label">Search Student</label>
                        <div class="u-input-icon-wrap">
                            <i class="bi bi-search u-input-icon"></i>
                            <input type="text" id="student-search" class="u-input" placeholder="Admission no., name…">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="u-form-label">Select Student</label>
                        <select id="student-select" class="u-input" required>
                            <option value="">— Choose a student —</option>
                        </select>
                    </div>
                    <div class="rounded-3 p-3 mb-2" style="background:#f0fdf4;border:1px solid #bbf7d0;font-size:12.5px;color:#166534;">
                        <i class="bi bi-envelope me-2"></i>
                        Email auto-generated: <code class="ms-1">firstname.lastname@csskabba.ng</code>
                    </div>
                    <div class="alert alert-danger d-none rounded-3" id="student-select-error"></div>
                </div>
                <div class="u-modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="u-btn success" id="proceed-to-credentials" disabled>
                        Continue <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SET STUDENT CREDENTIALS MODAL --}}
    <div id="setStudentCredentialsModal" class="modal fade u-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="u-modal-hero" style="background:linear-gradient(135deg,#1e3a5f,#2563eb,#4f46e5);">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h5><i class="bi bi-key me-2"></i>Set Credentials</h5>
                    <p>Configure login details for the selected student</p>
                </div>
                <form id="add-student-credentials-form" autocomplete="off">
                    <div class="u-modal-body">
                        <input type="hidden" id="student-id-field" name="student_id">
                        <input type="hidden" id="student-name-field" name="name">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="u-form-label">Email Address</label>
                                <input type="email" id="student-user-email" name="email" class="u-form-input" placeholder="Will be auto-generated" required>
                                <div class="small text-muted mt-1">Format: firstname.lastname@csskabba.ng</div>
                            </div>
                            <div class="col-12">
                                <label class="u-form-label">Username (Admission No)</label>
                                <input type="text" id="student-username" name="username" class="u-form-input" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Temporary Password</label>
                                <div class="input-group">
                                    <input type="text" id="student-password" name="password" class="u-form-input" style="border-radius:9px 0 0 9px;" placeholder="Auto-generate or type" required>
                                    <button type="button" id="generate-temp-password" style="border:1.5px solid var(--u-border);border-left:none;border-radius:0 9px 9px 0;background:#f8fafc;color:var(--u-accent);padding:0 12px;cursor:pointer;font-size:12px;font-weight:600;">Gen</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="u-form-label">Confirm Password</label>
                                <input type="password" id="student-password_confirmation" name="password_confirmation" class="u-form-input" placeholder="Repeat password" required>
                            </div>
                            <div class="col-12">
                                <label class="u-form-label">Role</label>
                                <select id="student-role" name="roles[]" class="u-form-input">
                                    <option value="Student" selected>Student</option>
                                </select>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3 rounded-3" id="student-credentials-error"></div>
                    </div>
                    <div class="u-modal-footer d-flex justify-content-end gap-2">
                        <button type="button" class="u-btn ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="u-btn primary" id="create-student-user">
                            <i class="bi bi-person-check"></i> Create Student User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MASS STUDENT MODAL — single canonical copy, included
    ══════════════════════════════════════════════════════ --}}
    @include('users.partials.mass-student-modal')

</div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════════ --}}

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // MAIN USER MANAGEMENT FUNCTIONS
    // ============================================================

    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap not loaded!');
        return;
    }

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showModal(modalId) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return null;
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) modal = new bootstrap.Modal(modalElement, { backdrop: 'static', keyboard: true });
        modal.show();
        return modal;
    }

    function hideModal(modalId) {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
        }
    }

    // Modal triggers
    ['openAddUserModalBtn', 'openAddUserModalBtnMobile'].forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) btn.addEventListener('click', e => { e.preventDefault(); showModal('showModal'); });
    });
    ['openAddStudentModalBtn', 'openAddStudentModalBtnMobile'].forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) btn.addEventListener('click', e => { e.preventDefault(); showModal('addStudentModal'); });
    });
    ['openMassStudentModalBtn', 'openMassStudentModalBtnMobile'].forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) btn.addEventListener('click', e => { e.preventDefault(); showModal('massStudentModal'); });
    });

    // ── Table filtering ─────────────────────────────────────
    const allRows = () => Array.from(document.querySelectorAll('#usersTableBody tr[data-id]'));

    function applyFilters() {
        const search = document.getElementById('liveSearch')?.value.toLowerCase().trim() || '';
        const role   = document.getElementById('roleFilter')?.value.toLowerCase().trim() || '';
        const email  = document.getElementById('emailFilter')?.value.toLowerCase().trim() || '';
        let shown = 0;
        allRows().forEach(row => {
            const name = row.dataset.name || '';
            const rEmail = row.dataset.email || '';
            const roles = row.dataset.roles || '';
            const matchSearch = !search || name.includes(search) || rEmail.includes(search);
            const matchRole = !role || roles.split(',').some(r => r.trim() === role) || (role === 'no role' && !roles.trim());
            const matchEmail = !email || rEmail === email;
            const visible = matchSearch && matchRole && matchEmail;
            row.style.display = visible ? '' : 'none';
            if (visible) shown++;
        });
        const showingSpan = document.getElementById('showingCount');
        if (showingSpan) showingSpan.textContent = shown;
        const userBadge = document.getElementById('userCountBadge');
        if (userBadge) userBadge.textContent = shown;

        let empty = document.getElementById('noResults');
        if (shown === 0 && allRows().length > 0) {
            if (!empty) {
                empty = document.createElement('tr');
                empty.id = 'noResults';
                empty.innerHTML = `<td colspan="7"><div class="u-empty"><i class="ri-search-line"></i>No users match your filters</div></td>`;
                document.getElementById('usersTableBody')?.appendChild(empty);
            }
        } else if (empty) empty.remove();
    }

    document.getElementById('liveSearch')?.addEventListener('input', applyFilters);
    document.getElementById('roleFilter')?.addEventListener('change', applyFilters);
    document.getElementById('emailFilter')?.addEventListener('change', applyFilters);
    document.getElementById('clearFilters')?.addEventListener('click', () => {
        if (document.getElementById('liveSearch')) document.getElementById('liveSearch').value = '';
        if (document.getElementById('roleFilter')) document.getElementById('roleFilter').value = '';
        if (document.getElementById('emailFilter')) document.getElementById('emailFilter').value = '';
        applyFilters();
    });

    // Sortable columns
    let sortDir = {};
    document.querySelectorAll('.sortable').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const col = parseInt(th.dataset.col);
            sortDir[col] = !sortDir[col];
            const tbody = document.getElementById('usersTableBody');
            if (!tbody) return;
            const rows = [...document.querySelectorAll('#usersTableBody tr[data-id]')];
            rows.sort((a, b) => {
                const key = ['name','email','','','','date'][col] || 'name';
                const av = a.dataset[key] || a.cells[col]?.textContent || '';
                const bv = b.dataset[key] || b.cells[col]?.textContent || '';
                return sortDir[col] ? av.localeCompare(bv) : bv.localeCompare(av);
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });

    // Check all functionality
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') cb.checked = this.checked;
            });
            updateRemoveBtn();
        });
    }
    document.addEventListener('change', e => { if (e.target.classList.contains('row-check')) updateRemoveBtn(); });
    function updateRemoveBtn() {
        const count = document.querySelectorAll('.row-check:checked').length;
        const removeBtn = document.getElementById('remove-actions');
        if (removeBtn) removeBtn.classList.toggle('d-none', count === 0);
    }

    // Delete single
    document.addEventListener('click', e => {
        const btn = e.target.closest('.remove-item-btn');
        if (!btn) return;
        const id = btn.dataset.id;
        const deleteBtn = document.getElementById('delete-record');
        if (deleteBtn) {
            const fresh = deleteBtn.cloneNode(true);
            deleteBtn.replaceWith(fresh);
            fresh.addEventListener('click', () => {
                axios.delete(`/users/${id}`, { headers: { 'X-CSRF-TOKEN': CSRF } })
                    .then(() => {
                        const row = document.querySelector(`tr[data-id="${id}"]`);
                        if (row) row.remove();
                        const totalEl = document.getElementById('totalCount');
                        if (totalEl) totalEl.textContent = parseInt(totalEl.textContent) - 1;
                        hideModal('deleteRecordModal');
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: 'User removed.', showConfirmButton: false, timer: 2000 });
                        applyFilters();
                    })
                    .catch(err => Swal.fire('Error', err.response?.data?.message || 'Delete failed', 'error'));
            });
        }
        showModal('deleteRecordModal');
    });

    // Delete multiple
    window.deleteMultiple = function() {
        const ids = [...document.querySelectorAll('.row-check:checked')].map(cb => cb.closest('tr').dataset.id).filter(Boolean);
        if (!ids.length) { Swal.fire('Select at least one user'); return; }
        Swal.fire({
            title: 'Delete ' + ids.length + ' user(s)?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, Delete All'
        }).then(r => {
            if (!r.isConfirmed) return;
            Promise.all(ids.map(id => axios.delete(`/users/${id}`, { headers: { 'X-CSRF-TOKEN': CSRF } })))
                .then(() => {
                    ids.forEach(id => { const row = document.querySelector(`tr[data-id="${id}"]`); if (row) row.remove(); });
                    Swal.fire('Deleted!', ids.length + ' users removed.', 'success');
                    updateRemoveBtn();
                    applyFilters();
                });
        });
    };

    // Edit user
    document.addEventListener('click', e => {
        const btn = e.target.closest('.edit-item-btn');
        if (!btn) return;
        document.getElementById('edit-id-field').value = btn.dataset.id;
        document.getElementById('edit-name').value = btn.dataset.name;
        document.getElementById('edit-email').value = btn.dataset.email;
        const roles = (btn.dataset.roles || '').split(',');
        const roleSelect = document.getElementById('edit-role');
        if (roleSelect) {
            Array.from(roleSelect.options).forEach(opt => { opt.selected = roles.includes(opt.value); });
        }
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-password_confirmation').value = '';
        document.getElementById('edit-alert')?.classList.add('d-none');
        showModal('editModal');
    });

    // Add user form
    const addUserForm = document.getElementById('add-user-form');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const alert = document.getElementById('add-alert');
            alert?.classList.add('d-none');
            const name = document.getElementById('name')?.value.trim();
            const email = document.getElementById('email')?.value.trim();
            const pass = document.getElementById('password')?.value;
            const conf = document.getElementById('password_confirmation')?.value;
            const roles = Array.from(document.getElementById('role')?.selectedOptions || []).map(o => o.value);
            if (!name) return showAlert(alert, 'Enter a name');
            if (!email) return showAlert(alert, 'Enter an email');
            if (!roles.length) return showAlert(alert, 'Select at least one role');
            if (!pass) return showAlert(alert, 'Enter a password');
            if (pass !== conf) return showAlert(alert, 'Passwords do not match');
            const btn = document.getElementById('add-btn');
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating…'; }
            axios.post('/users', { name, email, roles, password: pass, password_confirmation: conf, _token: CSRF })
                .then(res => {
                    addRowToTable(res.data.user);
                    hideModal('showModal');
                    Swal.fire({ icon: 'success', title: 'User Created!', text: res.data.user.name + ' added.', showConfirmButton: false, timer: 2500 });
                })
                .catch(err => showAlert(alert, err.response?.data?.message || 'Error creating user'))
                .finally(() => { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-plus-circle"></i> Create User'; } });
        });
    }

    // Edit user form
    const editUserForm = document.getElementById('edit-user-form');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const alert = document.getElementById('edit-alert');
            alert?.classList.add('d-none');
            const id = document.getElementById('edit-id-field')?.value;
            const name = document.getElementById('edit-name')?.value.trim();
            const email = document.getElementById('edit-email')?.value.trim();
            const pass = document.getElementById('edit-password')?.value;
            const conf = document.getElementById('edit-password_confirmation')?.value;
            const roles = Array.from(document.getElementById('edit-role')?.selectedOptions || []).map(o => o.value);
            if (!name) return showAlert(alert, 'Enter a name');
            if (!email) return showAlert(alert, 'Enter an email');
            if (!roles.length) return showAlert(alert, 'Select at least one role');
            if (pass && pass !== conf) return showAlert(alert, 'Passwords do not match');
            const btn = document.getElementById('update-btn');
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…'; }
            const payload = { name, email, roles, _token: CSRF };
            if (pass) { payload.password = pass; payload.password_confirmation = conf; }
            axios.put(`/users/${id}`, payload, { headers: { 'X-CSRF-TOKEN': CSRF } })
                .then(res => {
                    updateRowInTable(res.data.user);
                    hideModal('editModal');
                    Swal.fire({ icon: 'success', title: 'Updated!', text: res.data.user.name + ' updated.', showConfirmButton: false, timer: 2500 });
                })
                .catch(err => showAlert(alert, err.response?.data?.message || 'Error updating user'))
                .finally(() => { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Update User'; } });
        });
    }

    function showAlert(el, msg) { if (el) { el.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + msg; el.classList.remove('d-none'); setTimeout(() => el.classList.add('d-none'), 5000); } }
    function rolePill(roleName) {
        const map = { Student: 'student', Admin: 'admin', Teacher: 'teacher', Staff: 'staff' };
        const cls = map[roleName] || 'default';
        return `<span class="u-role-pill ${cls}"><i class="bi bi-shield-check"></i>${roleName}</span>`;
    }
    function escHtml(s) { return String(s || '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }
    function getInitials(name) { const parts = (name || '').split(' '); return (parts[0]?.[0] || '') + (parts[1]?.[0] || ''); }
    function addRowToTable(user) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();
        const row = document.createElement('tr');
        row.dataset.id = user.id;
        row.dataset.name = user.name.toLowerCase();
        row.dataset.email = user.email.toLowerCase();
        row.dataset.roles = (user.roles || []).join(',').toLowerCase();
        row.dataset.date = new Date().toISOString().slice(0, 10);
        row.innerHTML = `
            <td><input type="checkbox" class="row-check" style="accent-color:var(--u-accent);cursor:pointer;"></td>
            <td><div class="u-avatar">${getInitials(user.name)}</div></td>
            <td><div class="fw-semibold" style="color:var(--u-primary)">${escHtml(user.name)}</div></td>
            <td><span class="text-muted small">${escHtml(user.email)}</span></td>
            <td>${(user.roles || []).map(rolePill).join('')}</td>
            <td class="text-muted small">${new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
            <td><div class="d-flex gap-1">
                <a href="/users/${user.id}" class="u-action-btn view"><i class="ph-eye"></i></a>
                <button class="u-action-btn edit edit-item-btn" data-id="${user.id}" data-name="${escHtml(user.name)}" data-email="${escHtml(user.email)}" data-roles="${(user.roles || []).join(',')}"><i class="ph-pencil"></i></button>
                <button class="u-action-btn del remove-item-btn" data-id="${user.id}"><i class="ph-trash"></i></button>
            </div></td>
        `;
        tbody.prepend(row);
        const total = document.getElementById('totalCount');
        if (total) total.textContent = parseInt(total.textContent) + 1;
        applyFilters();
    }
    function updateRowInTable(user) {
        const row = document.querySelector(`tr[data-id="${user.id}"]`);
        if (!row) return;
        row.dataset.name = user.name.toLowerCase();
        row.dataset.email = user.email.toLowerCase();
        row.dataset.roles = (user.roles || []).join(',').toLowerCase();
        if (row.cells[2]) row.cells[2].innerHTML = `<div class="fw-semibold" style="color:var(--u-primary)">${escHtml(user.name)}</div>`;
        if (row.cells[3]) row.cells[3].innerHTML = `<span class="text-muted small">${escHtml(user.email)}</span>`;
        if (row.cells[4]) row.cells[4].innerHTML = (user.roles || []).map(rolePill).join('');
    }

    // Password reset for student
    $(document).on('click', '.reset-student-pwd-btn', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        Swal.fire({
            title: 'Reset Password?',
            html: `Reset password for <strong>${userName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            confirmButtonText: 'Reset'
        }).then(r => {
            if (!r.isConfirmed) return;
            Swal.fire({ title: 'Resetting…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            fetch(`/users/reset-single-password/${userId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Password Reset!',
                        html: `<div style="text-align:center"><p class="text-muted mb-3">New password for <strong>${data.user.name}</strong></p>
                            <div style="background:#f0f9ff;border:2px solid #bfdbfe;border-radius:10px;padding:16px 24px;display:inline-block;">
                                <code style="font-size:26px;font-weight:700;letter-spacing:4px;color:#1e40af;">${data.password}</code>
                            </div></div>`,
                        icon: 'success',
                        confirmButtonColor: '#2563eb'
                    });
                } else Swal.fire('Error', data.message || 'Reset failed', 'error');
            }).catch(() => Swal.fire('Error', 'Network error', 'error'));
        });
    });

    // Chart
    const ctx = document.getElementById('usersByRoleChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: { labels: @json(array_keys($role_counts)), datasets: [{ label: 'Users', data: @json(array_values($role_counts)), backgroundColor: ['rgba(37,99,235,.75)', 'rgba(16,185,129,.75)', 'rgba(245,158,11,.75)', 'rgba(239,68,68,.75)'], borderRadius: 8 }] },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }



    // Download Staff Template
document.getElementById('downloadStaffTemplateBtn')?.addEventListener('click', function () {
    const rows = document.getElementById('staff_tpl_rows')?.value || 30;
    window.location.href = `{{ route('users.staff.template') }}?rows=${rows}`;
});

// Import Staff Users
document.getElementById('import-staff-form')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('importStaffBtn');
    const resultDiv = document.getElementById('staff-import-result');
    const formData = new FormData(this);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing…';

    fetch('{{ route("users.staff.import") }}', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        resultDiv.classList.remove('d-none');
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success border-0">
                    <strong>${data.message}</strong>
                    ${data.skipped?.length ? `<ul class="mb-0 mt-2 small">${data.skipped.map(s => `<li>${s}</li>`).join('')}</ul>` : ''}
                </div>`;
            setTimeout(() => location.reload(), 1800);
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger border-0">${data.message || 'Import failed'}</div>`;
        }
    })
    .catch(() => {
        resultDiv.classList.remove('d-none');
        resultDiv.innerHTML = `<div class="alert alert-danger border-0">Network error</div>`;
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Import';
    });
});



    // ============================================================
    // MASS STUDENT MANAGEMENT
    // ============================================================

    let selectedStudents = [];
    let allStudents = [];
    let currentResults = null;
    let isProcessing = false;

    // Normalizes any id (number/string/null) to a consistent string key
    // so comparisons never fail on type mismatches (e.g. "12" !== 12).
    function idKey(v) {
        return v === null || v === undefined ? '' : String(v);
    }

    function classLabel(s) {
        const c = (s.class_name || '').trim();
        const a = (s.arm_name || '').trim();
        return c && a ? `${c} ${a}` : c || a || '—';
    }

    function genEmail(first, last) {
        const clean = s => (s || '').toLowerCase().replace(/[^a-z0-9]/g, '') || 'user';
        return clean(first) + '.' + clean(last) + '@csskabba.ng';
    }

    function statusBadge(has) {
        return has
            ? '<span class="msm-badge-has"><i class="bi bi-check-circle-fill me-1"></i>Has Account</span>'
            : '<span class="msm-badge-none"><i class="bi bi-circle me-1"></i>No Account</span>';
    }

    function setStep(n) {
        [1, 2, 3].forEach(i => {
            const el = document.getElementById('stepBar' + i);
            const circle = el?.querySelector('.msm-step-circle');
            if (!el || !circle) return;
            el.classList.remove('active', 'done');
            if (i < n) {
                el.classList.add('done');
                circle.innerHTML = '<i class="bi bi-check-lg"></i>';
            } else {
                circle.textContent = i;
                if (i === n) el.classList.add('active');
            }
        });
    }

    function loadStudents() {
        const search = document.getElementById('massStudentSearch')?.value || '';
        const classId = document.getElementById('massClassFilter')?.value || '';
        const status = document.getElementById('massAccountStatus')?.value || 'all';

        const tbody = document.getElementById('massStudentList');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="msm-loading-cell"><div class="spinner-border spinner-border-sm me-2 text-primary"></div>Loading students…</td></tr>';
        }

        let url = '{{ route("get.students") }}?limit=2000';
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (classId) url += `&class_id=${classId}`;
        if (status !== 'all') url += `&has_account=${status}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="6" class="msm-loading-cell text-danger">Error loading students.</td></tr>';
                    }
                    return;
                }

                allStudents = data.students.map(s => ({
                    ...s,
                    generatedEmail: genEmail(s.firstname, s.lastname)
                }));

                // IMPORTANT: rebuild selectedStudents from the freshly loaded list so
                // stale references never linger, but preserve prior selection by id.
                // Compare via idKey() so string/number id mismatches don't drop selections.
                if (selectedStudents.length) {
                    const keptIds = new Set(selectedStudents.map(s => idKey(s && s.id)));
                    selectedStudents = allStudents.filter(s => keptIds.has(idKey(s.id)));
                }

                renderStudentTable(allStudents);

                // Populate class filter
                const classFilter = document.getElementById('massClassFilter');
                if (classFilter && classFilter.options.length <= 1) {
                    let html = '<option value="">All Classes</option>';
                    if (data.classes?.length) {
                        data.classes.forEach(c => {
                            html += `<option value="${escHtml(String(c.id))}">${escHtml(c.name || c.class_name || '')}</option>`;
                        });
                    } else {
                        const seen = new Map();
                        allStudents.forEach(s => {
                            if (s.class_id && !seen.has(s.class_id)) {
                                seen.set(s.class_id, classLabel(s));
                            }
                        });
                        [...seen.entries()].sort((a, b) => a[1].localeCompare(b[1])).forEach(([id, lbl]) => {
                            html += `<option value="${escHtml(String(id))}">${escHtml(lbl)}</option>`;
                        });
                    }
                    classFilter.innerHTML = html;
                }
            })
            .catch(() => {
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="6" class="msm-loading-cell text-danger">Network error.</td></tr>';
                }
            });
    }

    function renderStudentTable(students) {
        const tbody = document.getElementById('massStudentList');
        if (!tbody) return;

        if (!students || !students.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="msm-loading-cell">No students found.</td></tr>';
            updateSelectedCount();
            return;
        }

        let html = '';
        students.forEach(s => {
            // idKey() comparison — was strict `x.id === s.id`, which silently
            // failed whenever one side was a string and the other a number.
            const checked = selectedStudents.some(x => x && idKey(x.id) === idKey(s.id)) ? 'checked' : '';
            html += `<tr>
                <td><input type="checkbox" class="student-checkbox" data-id="${escHtml(String(s.id))}" ${checked}></td>
                <td><strong>${escHtml(s.admissionNo || 'N/A')}</strong></td>
                <td>${escHtml(s.name)}</td>
                <td>${escHtml(classLabel(s))}</td>
                <td>${statusBadge(s.has_account)}</td>
                <td><small class="text-muted font-monospace">${escHtml(s.generatedEmail)}</small></td>
            </tr>`;
        });
        tbody.innerHTML = html;
        updateSelectedCount();

        // Attach change events to new checkboxes — this table is fully
        // re-rendered on every filter/select-all action, so there's never
        // more than one listener per checkbox at a time.
        document.querySelectorAll('#massStudentList .student-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                // FIX: previously used `parseInt(this.dataset.id, 10)` and then
                // compared with strict `===` against `x.id`. If the API returns
                // ids as strings (or any non-Number type), that strict comparison
                // never matched, `stu` came back undefined, and the handler
                // returned early — so nothing was ever pushed to selectedStudents
                // and updateSelectedCount() never ran. That's why a single
                // checkbox click ticked the box but left the "0 selected" counter
                // frozen and the "Continue to Action" button disabled.
                const id = this.dataset.id;
                const stu = allStudents.find(x => x && idKey(x.id) === idKey(id));
                if (!stu) return;

                if (this.checked) {
                    if (!selectedStudents.some(x => x && idKey(x.id) === idKey(id))) {
                        selectedStudents.push(stu);
                    }
                } else {
                    selectedStudents = selectedStudents.filter(x => x && idKey(x.id) !== idKey(id));
                }
                updateSelectedCount();
            });
        });
    }

    function updateSelectedCount() {
        const countSpan = document.getElementById('massSelectedCount');
        if (countSpan) {
            countSpan.textContent = `${selectedStudents.length} selected`;
        }
        const selectAllCheck = document.getElementById('selectAllCheckbox');
        if (selectAllCheck) {
            selectAllCheck.checked = allStudents.length > 0 && selectedStudents.length === allStudents.length;
        }
        updateProceedButton();
    }

    // Enables "Continue to Action" as soon as ONE student is checked — no
    // requirement to select all, and no accidental "select everything".
    function updateProceedButton() {
        const proceedBtn = document.getElementById('proceedToAction');
        if (proceedBtn) {
            proceedBtn.disabled = selectedStudents.length === 0;
            proceedBtn.style.opacity = selectedStudents.length === 0 ? '0.5' : '1';
        }
    }

    function applyClientFilters() {
        const search = document.getElementById('massStudentSearch')?.value?.toLowerCase() || '';
        const status = document.getElementById('massAccountStatus')?.value || 'all';

        const filtered = allStudents.filter(s => {
            if (!s) return false;
            if (search && !s.name?.toLowerCase().includes(search) && !(s.admissionNo || '').toLowerCase().includes(search)) return false;
            if (status === 'yes' && !s.has_account) return false;
            if (status === 'no' && s.has_account) return false;
            return true;
        });
        renderStudentTable(filtered);
    }

    // ── Mass Student Event Listeners ──────────────────────

    const massSearchInput = document.getElementById('massStudentSearch');
    if (massSearchInput) massSearchInput.addEventListener('input', applyClientFilters);

    const massStatusFilter = document.getElementById('massAccountStatus');
    if (massStatusFilter) massStatusFilter.addEventListener('change', applyClientFilters);

    const massClassFilter = document.getElementById('massClassFilter');
    if (massClassFilter) {
        massClassFilter.addEventListener('change', () => {
            selectedStudents = [];
            loadStudents();
        });
    }

    const selectAllStudentsBtn = document.getElementById('selectAllStudents');
    if (selectAllStudentsBtn) {
        selectAllStudentsBtn.addEventListener('click', () => {
            selectedStudents = [...allStudents];
            renderStudentTable(allStudents);
        });
    }

    const deselectAllBtn = document.getElementById('deselectAll');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', () => {
            selectedStudents = [];
            renderStudentTable(allStudents);
        });
    }

    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            selectedStudents = this.checked ? [...allStudents] : [];
            renderStudentTable(allStudents);
        });
    }

    // ── Step 1 → 2 ──────────────────────────────────────────
    const proceedToActionBtn = document.getElementById('proceedToAction');
    if (proceedToActionBtn) {
        proceedToActionBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // Works identically whether 1 student or all students are checked.
            if (!selectedStudents || selectedStudents.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Students Selected',
                    text: 'Please select at least one student to proceed.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            // Build the summary table
            let html = '';
            selectedStudents.forEach(s => {
                if (!s) return;
                html += `<tr>
                    <td>${escHtml(s.name || '')}</td>
                    <td>${escHtml(s.admissionNo || 'N/A')}</td>
                    <td>${escHtml(classLabel(s))}</td>
                    <td>${statusBadge(s.has_account)}</td>
                    <td><small class="font-monospace">${escHtml(s.generatedEmail || '')}</small></td>
                </tr>`;
            });

            const listBody = document.getElementById('selectedStudentsList');
            if (listBody) {
                listBody.innerHTML = html;
            }

            const countSpan = document.getElementById('step2SelectedCount');
            if (countSpan) {
                countSpan.textContent = selectedStudents.length;
            }

            // Show step 2, hide step 1
            const step1 = document.getElementById('massStep1');
            const step2 = document.getElementById('massStep2');
            if (step1) step1.style.display = 'none';
            if (step2) step2.style.display = '';

            setStep(2);
        });
    }

    // ── Action Cards ───────────────────────────────────────
    document.querySelectorAll('.msm-action-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.msm-action-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const action = this.dataset.action;
            document.getElementById('selectedAction').value = action;

            const showPwd = action === 'create' || action === 'reset';
            const pwdSettings = document.getElementById('passwordSettings');
            const roleSettings = document.getElementById('roleSettings');
            if (pwdSettings) pwdSettings.style.display = showPwd ? '' : 'none';
            if (roleSettings) roleSettings.style.display = showPwd ? '' : 'none';

            // Show warnings
            const hasAcc = selectedStudents.filter(s => s && s.has_account).length;
            const noAcc = selectedStudents.filter(s => s && !s.has_account).length;
            let warn = '';
            if (action === 'create' && hasAcc) {
                warn = `<i class="bi bi-exclamation-triangle-fill me-2"></i>${hasAcc} student(s) already have accounts and will be skipped.`;
            } else if (action === 'reset' && noAcc) {
                warn = `<i class="bi bi-exclamation-triangle-fill me-2"></i>${noAcc} student(s) have no accounts and will be skipped.`;
            } else if (action === 'revoke' && noAcc) {
                warn = `<i class="bi bi-exclamation-triangle-fill me-2"></i>${noAcc} student(s) have no accounts and will be skipped.`;
            }

            const warningEl = document.getElementById('actionWarning');
            if (warningEl) {
                if (warn) {
                    warningEl.innerHTML = warn;
                    warningEl.style.display = '';
                } else {
                    warningEl.style.display = 'none';
                }
            }
        });
    });

    // ── Password Type Radio ───────────────────────────────
    document.querySelectorAll('input[name="passwordTypeRadio"]').forEach(r => {
        r.addEventListener('change', function() {
            const container = document.getElementById('sharedPasswordContainer');
            if (container) {
                container.style.display = this.value === 'same' ? '' : 'none';
            }
        });
    });

    // ── Back to Step 1 ─────────────────────────────────────
    const backToStep1Btn = document.getElementById('backToStep1');
    if (backToStep1Btn) {
        backToStep1Btn.addEventListener('click', () => {
            const step2 = document.getElementById('massStep2');
            const step1 = document.getElementById('massStep1');
            if (step2) step2.style.display = 'none';
            if (step1) step1.style.display = '';
            setStep(1);
        });
    }

    // ── Execute Action ─────────────────────────────────────
    const executeActionBtn = document.getElementById('executeAction');
    if (executeActionBtn) {
        executeActionBtn.addEventListener('click', function() {
            if (isProcessing) return;

            const actionType = document.getElementById('selectedAction')?.value;
            if (!actionType) {
                Swal.fire({
                    icon: 'error',
                    title: 'No Action Selected',
                    text: 'Please choose an action first.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            if (!selectedStudents || selectedStudents.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'No Students',
                    text: 'No students selected to process.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            const payload = {
                _token: '{{ csrf_token() }}',
                students: selectedStudents.map(s => ({ student_id: s.id })),
                action_type: actionType,
            };

            if (actionType === 'create' || actionType === 'reset') {
                const pwdType = document.querySelector('input[name="passwordTypeRadio"]:checked')?.value || 'individual';
                payload.password_type = pwdType;
                if (pwdType === 'same') {
                    const sharedPwd = document.getElementById('sharedPassword')?.value;
                    if (!sharedPwd || sharedPwd.length < 6) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Password Too Short',
                            text: 'Shared password must be at least 6 characters.',
                            confirmButtonColor: '#2563eb'
                        });
                        return;
                    }
                    payload.shared_password = sharedPwd;
                }
                payload.roles = ['Student'];
            }

            isProcessing = true;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            Swal.fire({
                title: 'Processing...',
                text: `Performing "${actionType}" action on ${selectedStudents.length} student(s)`,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route("users.mass-create-students") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                isProcessing = false;
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-check-circle me-1"></i>Execute Action';

                if (data.success) {
                    currentResults = data;
                    displayResults(data);

                    const step2 = document.getElementById('massStep2');
                    const step3 = document.getElementById('massStep3');
                    if (step2) step2.style.display = 'none';
                    if (step3) step3.style.display = '';
                    setStep(3);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Operation Failed',
                        text: data.message || 'An error occurred while processing.',
                        confirmButtonColor: '#2563eb'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                isProcessing = false;
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-check-circle me-1"></i>Execute Action';

                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to connect to the server. Please try again.',
                    confirmButtonColor: '#2563eb'
                });
            });
        });
    }

    // ── Results Display ────────────────────────────────────
    function displayResults(data) {
        let html = `<div class="alert alert-success border-0 rounded-3" style="background:#f0fdf4;border-left:4px solid #16a34a !important;">
            <h5 class="mb-1"><i class="bi bi-check-circle-fill me-2 text-success"></i>Operation Complete</h5>
            <p class="mb-0 text-muted">${escHtml(data.message)}</p>
        </div>`;

        if (data.created?.length) {
            html += mkTable('Created Accounts', data.created, 'success', 'person-plus-fill',
                ['Name', 'Username', 'Email', 'Password', 'Admission No', 'Class'],
                c => `<tr>
                    <td>${escHtml(c.name)}</td>
                    <td><code>${escHtml(c.username)}</code></td>
                    <td><small>${escHtml(c.email)}</small></td>
                    <td><code class="text-success fw-bold">${escHtml(c.password)}</code></td>
                    <td>${escHtml(c.admissionNo || 'N/A')}</td>
                    <td>${escHtml(c.class_name || '')}</td>
                </tr>`
            );
        }

        if (data.reset?.length) {
            html += mkTable('Password Resets', data.reset, 'warning', 'key-fill',
                ['Name', 'Username', 'Email', 'New Password', 'Admission No', 'Class'],
                r => `<tr>
                    <td>${escHtml(r.name)}</td>
                    <td><code>${escHtml(r.username)}</code></td>
                    <td><small>${escHtml(r.email)}</small></td>
                    <td><code class="text-warning fw-bold">${escHtml(r.password)}</code></td>
                    <td>${escHtml(r.admissionNo || 'N/A')}</td>
                    <td>${escHtml(r.class_name || '')}</td>
                </tr>`
            );
        }

        if (data.revoked?.length) {
            html += `<div class="mt-3 p-3 border rounded-3">
                <strong><i class="bi bi-person-x-fill text-danger me-2"></i>Revoked (${data.revoked.length})</strong>
                <ul class="mt-2 mb-0">
                    ${data.revoked.map(r => `<li>${escHtml(r.name)} (${escHtml(r.admissionNo || 'N/A')}) — account removed</li>`).join('')}
                </ul>
            </div>`;
        }

        if (data.reprinted?.length) {
            html += mkTable('Reprinted Credentials', data.reprinted, 'info', 'printer-fill',
                ['Name', 'Username', 'Email', 'Admission No', 'Note'],
                r => `<tr>
                    <td>${escHtml(r.name)}</td>
                    <td><code>${escHtml(r.username)}</code></td>
                    <td><small>${escHtml(r.email)}</small></td>
                    <td>${escHtml(r.admissionNo || 'N/A')}</td>
                    <td><small class="text-muted">Password hidden</small></td>
                </tr>`
            );
        }

        if (data.skipped?.length) {
            html += `<div class="mt-3 p-3 border rounded-3 bg-light">
                <strong><i class="bi bi-skip-forward-fill text-muted me-2"></i>Skipped (${data.skipped.length})</strong>
                <ul class="mt-2 mb-0">
                    ${data.skipped.map(s => `<li class="text-muted">${escHtml(s)}</li>`).join('')}
                </ul>
            </div>`;
        }

        const container = document.getElementById('resultsContainer');
        if (container) container.innerHTML = html;
    }

    function mkTable(title, rows, color, icon, headers, rowFn) {
        if (!rows || !rows.length) return '';
        return `<div class="mt-3">
            <strong><i class="bi bi-${icon} text-${color} me-2"></i>${title} (${rows.length})</strong>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered msm-table">
                    <thead class="table-${color}"><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead>
                    <tbody>${rows.map(rowFn).join('')}</tbody>
                </table>
            </div>
        </div>`;
    }

    // ── Print Results ──────────────────────────────────────
    const printResultsBtn = document.getElementById('printResults');
    if (printResultsBtn) {
        printResultsBtn.addEventListener('click', function() {
            if (!currentResults) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Results',
                    text: 'Please execute an action first.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            const school = document.querySelector('meta[name="school-name"]')?.content || 'CSS Kabba';
            const today = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

            const allCreds = [
                ...(currentResults.created || []).map(c => ({ ...c, type: 'created' })),
                ...(currentResults.reset || []).map(r => ({ ...r, type: 'reset' })),
            ];

            if (!allCreds.length) {
                Swal.fire({
                    icon: 'info',
                    title: 'Nothing to Print',
                    text: 'No created or reset credentials available.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            // Build slips with photos if available
            const slipHtml = allCreds.map(s => {
                const isReset = s.type === 'reset';
                const tag = isReset ? 'RESET' : 'NEW';
                const tagColor = isReset ? '#d97706' : '#16a34a';

                // idKey() comparison here too — matching against student_id.
                const matchingStudent = selectedStudents.find(st => st && idKey(st.id) === idKey(s.student_id));
                let photoHtml = '';
                if (matchingStudent && matchingStudent.photo_url) {
                    photoHtml = `<div class="slip-photo"><img src="${matchingStudent.photo_url}" alt="Photo" onerror="this.style.display='none'"></div>`;
                } else if (matchingStudent && matchingStudent.picture) {
                    photoHtml = `<div class="slip-photo"><img src="/storage/images/student_avatars/${matchingStudent.picture}" alt="Photo" onerror="this.style.display='none'"></div>`;
                } else {
                    const initials = (s.name || 'ST').split(' ').map(w => w[0]).join('').toUpperCase().substring(0, 2);
                    photoHtml = `<div class="slip-photo" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-size:24px;font-weight:700;display:flex;align-items:center;justify-content:center;">${initials}</div>`;
                }

                return `
                <div class="slip">
                    <div class="slip-tag" style="background:${tagColor}">${tag}</div>
                    <div class="slip-school">${escHtml(school)}</div>
                    ${photoHtml}
                    <div class="slip-name">${escHtml(s.name)}</div>
                    <div class="slip-row"><span class="sl">Adm No</span><span class="sv">${escHtml(s.admissionNo || 'N/A')}</span></div>
                    <div class="slip-row"><span class="sl">Class</span><span class="sv">${escHtml(s.class_name || '—')}</span></div>
                    <div class="slip-row"><span class="sl">Email</span><span class="sv mono">${escHtml(s.email)}</span></div>
                    <div class="slip-row"><span class="sl">Username</span><span class="sv mono">${escHtml(s.username || '')}</span></div>
                    <div class="slip-pwd"><span class="pwd-label">${isReset ? 'New Password' : 'Password'}</span><span class="pwd-val">${escHtml(s.password)}</span></div>
                    <div class="slip-note">Change password after first login &bull; ${window.location.hostname}</div>
                </div>`;
            }).join('');

            const printWin = window.open('', '_blank');
            printWin.document.write(`<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Student Credentials — ${today}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; background:#f0f4f8; font-size:10px; }

/* Summary Page */
.summary-page { page-break-after: always; padding:20mm; }
.summary-page h2 { font-size:18px; color:#1e3a5f; margin-bottom:8px; }
.summary-page .meta { color:#666; font-size:12px; margin-bottom:16px; }
.sum-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
.sum-stat { background:#fff; border-radius:8px; padding:14px; text-align:center; border:1px solid #e2e8f0; }
.sum-stat .n { font-size:28px; font-weight:800; color:#1e3a5f; }
.sum-stat .l { font-size:11px; color:#64748b; margin-top:2px; }
.sum-table { width:100%; border-collapse:collapse; font-size:12px; }
.sum-table th { background:#1e3a5f; color:#fff; padding:8px 12px; text-align:left; }
.sum-table td { border:1px solid #e2e8f0; padding:7px 12px; }
.sum-table tr:nth-child(even) td { background:#f8fafc; }

/* Slip Grid - 3 per row */
.slips-page { padding:8mm; }
.slip-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:5mm; }

/* Individual Slip */
.slip {
    border:1px solid #d1d5db;
    border-radius:8px;
    padding:9px 11px;
    background:#fff;
    page-break-inside: avoid;
    break-inside: avoid;
    position: relative;
    overflow: hidden;
}
.slip::before {
    content:'';
    position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,#1e3a5f,#2563eb);
}
.slip-tag {
    display:inline-block;
    color:#fff; font-size:8px; font-weight:700;
    padding:1px 6px; border-radius:10px;
    margin-bottom:4px; letter-spacing:.5px;
}
.slip-school { font-size:9px; font-weight:700; color:#1e3a5f; margin-bottom:3px; text-transform:uppercase; letter-spacing:.5px; }
.slip-photo {
    width:50px; height:50px;
    border-radius:50%;
    overflow:hidden;
    margin:0 auto 6px;
    border:2px solid #e2e8f0;
    background:#f1f5f9;
}
.slip-photo img { width:100%; height:100%; object-fit:cover; }
.slip-name { font-size:13px; font-weight:800; color:#0f172a; margin-bottom:7px; text-align:center; line-height:1.2; }
.slip-row { display:flex; justify-content:space-between; align-items:center; padding:2px 0; border-bottom:1px dashed #f1f5f9; }
.sl { color:#64748b; font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
.sv { color:#1e293b; font-size:9px; font-weight:500; text-align:right; word-break:break-all; }
.mono { font-family:'Courier New',monospace; font-size:8.5px; }
.slip-pwd {
    margin-top:7px;
    background:linear-gradient(135deg,#f0f9ff,#eff6ff);
    border:1.5px solid #bfdbfe;
    border-radius:6px;
    padding:7px 9px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}
.pwd-label { font-size:8px; font-weight:700; color:#1e40af; text-transform:uppercase; letter-spacing:.3px; }
.pwd-val { font-family:'Courier New',monospace; font-size:14px; font-weight:900; color:#1e40af; letter-spacing:2px; }
.slip-note { margin-top:5px; font-size:7.5px; color:#94a3b8; text-align:center; border-top:1px dashed #f1f5f9; padding-top:4px; }

.cut-row { text-align:center; font-size:8px; color:#cbd5e1; letter-spacing:2px; margin:2mm 0; font-family:monospace; }

@media print {
    body { background:#fff; }
    .summary-page { padding:15mm; }
    .slips-page { padding:6mm; }
    .slip-grid { gap:4mm; }
    .pwd-val, .slip-pwd, .slip-tag, .slip-photo { print-color-adjust:exact; -webkit-print-color-adjust:exact; }
    .summary-page { page-break-after:always; break-after:page; }
}
</style>
</head>
<body>

<!-- Summary Page -->
<div class="summary-page">
    <h2>🎓 Student Portal Credentials — ${escHtml(school)}</h2>
    <div class="meta">Printed: ${today} &nbsp;|&nbsp; Total slips: ${allCreds.length}</div>
    <div class="sum-grid">
        <div class="sum-stat"><div class="n" style="color:#2563eb">${allCreds.length}</div><div class="l">Total Slips</div></div>
        <div class="sum-stat"><div class="n" style="color:#16a34a">${currentResults.created?.length || 0}</div><div class="l">New Accounts</div></div>
        <div class="sum-stat"><div class="n" style="color:#d97706">${currentResults.reset?.length || 0}</div><div class="l">Password Resets</div></div>
        <div class="sum-stat"><div class="n" style="color:#64748b">${currentResults.skipped?.length || 0}</div><div class="l">Skipped</div></div>
    </div>
    <table class="sum-table">
        <thead><tr><th>#</th><th>Student Name</th><th>Admission No</th><th>Class</th><th>Email</th><th>Type</th></tr></thead>
        <tbody>
        ${allCreds.map((s, i) => `<tr>
            <td>${i + 1}</td>
            <td><strong>${escHtml(s.name)}</strong></td>
            <td style="font-family:monospace">${escHtml(s.admissionNo || 'N/A')}</td>
            <td>${escHtml(s.class_name || '—')}</td>
            <td style="font-family:monospace;font-size:10px">${escHtml(s.email)}</td>
            <td style="color:${s.type === 'reset' ? '#d97706' : '#16a34a'};font-weight:700">${s.type === 'reset' ? 'RESET' : 'NEW'}</td>
        </tr>`).join('')}
        </tbody>
    </table>
    <div class="print-note" style="margin-top:12px;font-size:10.5px;color:#94a3b8;text-align:center;">
        ✂ Cut individual slips along the borders &nbsp;|&nbsp; Keep credentials secure &nbsp;|&nbsp; ${escHtml(school)} School Management System
    </div>
</div>

<!-- Credential Slips -->
<div class="slips-page">
    <div class="slip-grid">${slipHtml}</div>
</div>

<script>
window.onload = function() {
    setTimeout(function() {
        window.print();
        setTimeout(function() {
            window.close();
        }, 1500);
    }, 600);
};
<\/script>
</body></html>`);
            printWin.document.close();
        });
    }

    // ── Modal Reset ────────────────────────────────────────
    function resetMassModal() {
        selectedStudents = [];
        currentResults = null;
        isProcessing = false;

        const actionField = document.getElementById('selectedAction');
        if (actionField) actionField.value = '';

        const step1 = document.getElementById('massStep1');
        const step2 = document.getElementById('massStep2');
        const step3 = document.getElementById('massStep3');

        if (step1) step1.style.display = '';
        if (step2) step2.style.display = 'none';
        if (step3) step3.style.display = 'none';

        document.querySelectorAll('.msm-action-card').forEach(c => c.classList.remove('selected'));

        const warning = document.getElementById('actionWarning');
        if (warning) warning.style.display = 'none';

        const pwdSettings = document.getElementById('passwordSettings');
        const roleSettings = document.getElementById('roleSettings');
        if (pwdSettings) pwdSettings.style.display = 'none';
        if (roleSettings) roleSettings.style.display = 'none';

        const defaultRadio = document.querySelector('input[name="passwordTypeRadio"][value="individual"]');
        if (defaultRadio) defaultRadio.checked = true;
        const sharedContainer = document.getElementById('sharedPasswordContainer');
        if (sharedContainer) sharedContainer.style.display = 'none';
        const sharedPwd = document.getElementById('sharedPassword');
        if (sharedPwd) sharedPwd.value = '';

        setStep(1);
        loadStudents();
    }

    const newActionBtn = document.getElementById('newAction');
    if (newActionBtn) newActionBtn.addEventListener('click', resetMassModal);

    const massModal = document.getElementById('massStudentModal');
    if (massModal) {
        massModal.addEventListener('hidden.bs.modal', resetMassModal);
        massModal.addEventListener('show.bs.modal', () => {
            selectedStudents = [];
            loadStudents();
        });
    }

    // ── Single Student Modal ──────────────────────────────
    const addStudentModal = document.getElementById('addStudentModal');
    const credentialsModal = document.getElementById('setStudentCredentialsModal');

    if (addStudentModal && credentialsModal) {
        let selectedSingleStudent = null;

        addStudentModal.addEventListener('show.bs.modal', () => loadStudentsForSingle(''));

        function loadStudentsForSingle(search) {
            const proceed = document.getElementById('proceed-to-credentials');
            if (proceed) proceed.disabled = true;

            let url = '{{ route("get.students") }}?limit=500&has_account=no';
            if (search.trim()) url += `&search=${encodeURIComponent(search.trim())}`;

            fetch(url, { headers: { 'X-CSRF-TOKEN': CSRF } })
                .then(r => r.json())
                .then(data => {
                    const sel = document.getElementById('student-select');
                    if (sel) {
                        sel.innerHTML = '<option value="">— Choose a student —</option>';
                        (data.students || []).forEach(s => {
                            const o = document.createElement('option');
                            o.value = s.id;
                            o.textContent = `${s.name} (${s.admissionNo})`;
                            Object.assign(o.dataset, { name: s.name, email: s.email || '', admission: s.admissionNo || '' });
                            sel.appendChild(o);
                        });
                    }
                })
                .catch(err => console.error(err));
        }

        document.getElementById('student-search')?.addEventListener('input', debounce(e => loadStudentsForSingle(e.target.value), 350));

        document.getElementById('student-select')?.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) {
                selectedSingleStudent = null;
                const proceed = document.getElementById('proceed-to-credentials');
                if (proceed) proceed.disabled = true;
                return;
            }
            selectedSingleStudent = {
                id: opt.value,
                name: opt.dataset.name,
                email: opt.dataset.email,
                admissionNo: opt.dataset.admission
            };
            const proceed = document.getElementById('proceed-to-credentials');
            if (proceed) proceed.disabled = false;
        });

        document.getElementById('proceed-to-credentials')?.addEventListener('click', () => {
            if (!selectedSingleStudent) return;

            const idField = document.getElementById('student-id-field');
            const nameField = document.getElementById('student-name-field');
            const emailField = document.getElementById('student-user-email');
            const usernameField = document.getElementById('student-username');

            if (idField) idField.value = selectedSingleStudent.id;
            if (nameField) nameField.value = selectedSingleStudent.name;
            if (emailField) emailField.value = selectedSingleStudent.email;
            if (usernameField) usernameField.value = (selectedSingleStudent.admissionNo || '').replace(/[\/\\]/g, '_');

            hideModal('addStudentModal');
            setTimeout(() => showModal('setStudentCredentialsModal'), 300);
        });

        document.getElementById('generate-temp-password')?.addEventListener('click', () => {
            const p = Math.random().toString(36).slice(-8) + Math.random().toString(36).slice(-4).toUpperCase();
            const pwdField = document.getElementById('student-password');
            const confField = document.getElementById('student-password_confirmation');
            if (pwdField) pwdField.value = p;
            if (confField) confField.value = p;
        });

        document.getElementById('add-student-credentials-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('_token', CSRF);

            const btn = document.getElementById('create-student-user');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating…';
            }

            fetch('{{ route("users.store-student") }}', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Student User Created!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 2000
                    });
                    hideModal('setStudentCredentialsModal');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    const errDiv = document.getElementById('student-credentials-error');
                    if (errDiv) {
                        errDiv.innerHTML = data.errors
                            ? Object.values(data.errors).flat().join('<br>')
                            : (data.message || 'Error');
                        errDiv.classList.remove('d-none');
                    }
                }
            })
            .catch(() => Swal.fire('Error', 'Network error', 'error'))
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-person-check"></i> Create Student User';
                }
            });
        });

        window.resetStudentCredentialsModal = function() {
            ['student-id-field', 'student-name-field', 'student-user-email', 'student-username', 'student-password', 'student-password_confirmation'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            const errDiv = document.getElementById('student-credentials-error');
            if (errDiv) errDiv.classList.add('d-none');
        };

        credentialsModal.addEventListener('hidden.bs.modal', window.resetStudentCredentialsModal);
    }

    function debounce(fn, ms) {
        let t;
        return (...a) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...a), ms);
        };
    }

    // ── Initial Load ───────────────────────────────────────
    applyFilters();
    console.log('User management page initialized');

});
</script>
@endsection
