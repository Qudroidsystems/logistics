@extends('layouts.master')
@section('content')
<?php
use Spatie\Permission\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap">

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

/* ── Animations ─────────────────────────────────── */
@keyframes slideDown  { from{opacity:0;transform:translateY(-22px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeUp     { from{opacity:0;transform:translateY(14px)}  to{opacity:1;transform:translateY(0)} }
@keyframes scaleIn    { from{opacity:0;transform:scale(.9)}         to{opacity:1;transform:scale(1)} }
@keyframes pulse      { 0%,100%{transform:scale(1)} 50%{transform:scale(1.06)} }
@keyframes shimmer    { from{background-position:-400px 0} to{background-position:400px 0} }

/* ── Hero ────────────────────────────────────────── */
.rol-hero {
    background: linear-gradient(135deg,#0f172a 0%,#1e1b4b 45%,#312e81 100%);
    border-radius: var(--rol-radius);
    padding: 30px 36px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: slideDown .5s cubic-bezier(.22,1,.36,1);
}
.rol-hero::before {
    content:''; position:absolute; top:-80px; right:-80px;
    width:260px; height:260px; background:rgba(99,102,241,.14);
    border-radius:50%; animation:pulse 4s ease-in-out infinite;
}
.rol-hero::after {
    content:''; position:absolute; bottom:-50px; left:32%;
    width:160px; height:160px; background:rgba(139,92,246,.1);
    border-radius:50%; animation:pulse 4s ease-in-out infinite 2s;
}
.rol-hero h1 { font-size:24px; font-weight:800; color:#fff; margin:0 0 6px; position:relative; z-index:1; letter-spacing:-.3px; }
.rol-hero p  { font-size:13.5px; color:rgba(255,255,255,.65); margin:0; position:relative; z-index:1; }
.rol-hero-actions { position:relative; z-index:1; margin-top:20px; }

/* ── Buttons ─────────────────────────────────────── */
.rol-btn-primary {
    background:linear-gradient(135deg,var(--rol-accent),var(--rol-accent2));
    color:#fff; border:none; border-radius:9px;
    padding:9px 18px; font-size:13px; font-weight:600;
    cursor:pointer; font-family:var(--rol-font);
    display:inline-flex; align-items:center; gap:6px;
    transition:transform .15s,box-shadow .15s;
    box-shadow:0 3px 12px rgba(99,102,241,.3);
    text-decoration:none;
}
.rol-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(99,102,241,.4); color:#fff; }

/* ── Role cards ──────────────────────────────────── */
.rol-card {
    background:var(--rol-surface);
    border:1px solid var(--rol-border);
    border-radius:var(--rol-radius);
    overflow:hidden;
    box-shadow:var(--rol-shadow);
    transition:transform .2s, box-shadow .2s, border-color .2s;
    animation:fadeUp .4s ease both;
    display:flex; flex-direction:column;
}
.rol-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(15,23,42,.12); border-color:#c7d2fe; }
.rol-card:nth-child(1){animation-delay:.05s}
.rol-card:nth-child(2){animation-delay:.10s}
.rol-card:nth-child(3){animation-delay:.15s}
.rol-card:nth-child(4){animation-delay:.20s}
.rol-card:nth-child(5){animation-delay:.25s}

.rol-card-top {
    background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 80%,#312e81 100%);
    padding:20px 18px 16px;
    position:relative; overflow:hidden;
}
.rol-card-top::before {
    content:''; position:absolute; top:-30px; right:-30px;
    width:100px; height:100px; background:rgba(99,102,241,.18);
    border-radius:50%;
}
.rol-card-icon {
    width:46px; height:46px; border-radius:12px;
    background:rgba(255,255,255,.14);
    display:flex; align-items:center; justify-content:center;
    font-size:20px; color:#fff; margin-bottom:12px;
    backdrop-filter:blur(4px);
    position:relative; z-index:1;
}
.rol-card-name {
    font-size:16px; font-weight:800; color:#fff;
    position:relative; z-index:1; margin-bottom:4px;
}
.rol-card-count {
    font-size:12px; color:rgba(255,255,255,.65);
    position:relative; z-index:1;
    display:flex; align-items:center; gap:5px;
}
.rol-card-count strong { color:rgba(255,255,255,.9); font-size:18px; }

.rol-card-body { padding:14px 18px; flex:1; }
.rol-perm-tag {
    display:inline-flex; align-items:center; gap:4px;
    background:#f0f4ff; color:#4338ca; border:1px solid #c7d2fe;
    border-radius:20px; padding:3px 10px; font-size:11px; font-weight:600;
    margin:2px; transition:background .15s;
}
.rol-perm-tag:hover { background:#e0e7ff; }
.rol-perm-more { color:var(--rol-accent); font-size:11.5px; font-weight:600; text-decoration:none; }
.rol-perm-more:hover { text-decoration:underline; }

.rol-card-footer {
    padding:12px 18px;
    border-top:1px solid var(--rol-border);
    background:var(--rol-surface2);
    display:flex; align-items:center; justify-content:space-between;
}
.rol-users-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;
    border-radius:20px; padding:3px 12px; font-size:11.5px; font-weight:600;
}
.rol-view-link { font-size:12.5px; font-weight:600; color:var(--rol-accent); text-decoration:none; display:flex; align-items:center; gap:4px; }
.rol-view-link:hover { color:var(--rol-accent2); }

/* ── Dropdown ────────────────────────────────────── */
.rol-dropdown .dropdown-toggle {
    background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25);
    color:#fff; border-radius:8px; padding:5px 9px; font-size:14px;
    transition:background .15s;
}
.rol-dropdown .dropdown-toggle:hover { background:rgba(255,255,255,.25); }
.rol-dropdown .dropdown-menu {
    border-radius:10px; border:1px solid var(--rol-border);
    box-shadow:0 8px 24px rgba(15,23,42,.12); padding:6px;
    animation:scaleIn .15s ease;
}
.rol-dropdown .dropdown-item {
    border-radius:7px; font-size:13px; font-weight:500; padding:8px 12px;
    display:flex; align-items:center; gap:8px; transition:background .12s;
}
.rol-dropdown .dropdown-item:hover { background:#f0f4ff; color:var(--rol-accent); }
.rol-dropdown .dropdown-item.text-danger:hover { background:#fef2f2; color:var(--rol-danger); }

/* ── Modal ───────────────────────────────────────── */
.rol-modal .modal-content {
    border:none; border-radius:18px; overflow:hidden;
    box-shadow:0 24px 64px rgba(15,23,42,.2);
    font-family:var(--rol-font);
    animation:scaleIn .25s cubic-bezier(.22,1,.36,1);
    max-height: 90vh;
}
.rol-modal .modal-content form {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}
.rol-modal .modal-header {
    background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 60%,#312e81 100%);
    padding:20px 24px; border:none;
    flex: 0 0 auto;
}
.rol-modal .modal-title { color:#fff; font-weight:700; font-size:16px; }
.rol-modal .modal-header .btn-close { filter:invert(1); opacity:.8; }
.rol-modal .modal-body  {
    padding:22px 24px;
    overflow-y: auto;
    flex: 1 1 auto;
    min-height: 0;
}
.rol-modal .modal-footer {
    padding:14px 24px; border-top:1px solid var(--rol-border); background:var(--rol-surface2);
    flex: 0 0 auto;
}

.rol-form-label { font-size:11.5px; font-weight:700; color:var(--rol-muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; display:block; }
.rol-form-control {
    border:1.5px solid var(--rol-border); border-radius:9px;
    padding:9px 13px; font-size:13px; font-family:var(--rol-font);
    width:100%; transition:border-color .2s, box-shadow .2s;
}
.rol-form-control:focus { border-color:var(--rol-accent); box-shadow:0 0 0 3px rgba(99,102,241,.12); outline:none; }

/* ── Permission table ────────────────────────────── */
.perm-table { width:100%; border-collapse:separate; border-spacing:0; }
.perm-table thead th {
    background:var(--rol-primary); color:#fff;
    padding:10px 14px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.5px;
}
.perm-table thead th:first-child { border-radius:8px 0 0 0; }
.perm-table thead th:last-child  { border-radius:0 8px 0 0; }
.perm-table tbody tr:hover td { background:#f5f3ff; }
.perm-table tbody td { padding:9px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; vertical-align:middle; }
.perm-table .perm-group { font-weight:700; color:var(--rol-primary); font-size:12.5px; }
.perm-check { accent-color:var(--rol-accent); width:15px; height:15px; cursor:pointer; }
.select-all-row td { background:#f0f4ff; }

/* ── Alerts ──────────────────────────────────────── */
.rol-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:9px; padding:10px 14px; font-size:13px; color:#16a34a; }
.rol-alert-danger  { background:#fef2f2; border:1px solid #fecaca; border-radius:9px; padding:10px 14px; font-size:13px; color:#b91c1c; }

/* ── Empty state ─────────────────────────────────── */
.rol-empty {
    text-align:center; padding:60px 24px;
    animation:fadeUp .5s ease;
}
.rol-empty-icon { font-size:56px; opacity:.2; margin-bottom:14px; display:block; }
.rol-empty h4   { font-weight:700; color:var(--rol-muted); font-size:17px; }
.rol-empty p    { font-size:13px; color:var(--rol-muted); }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="rol-hero">
        <h1><i class="bi bi-shield-lock-fill me-2"></i>Role Management</h1>
        <p>Define roles, assign permissions, and control what users can access across the system.</p>
        <div class="rol-hero-actions">
            <button type="button" class="rol-btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModalgrid">
                <i class="bi bi-plus-circle"></i> Create Role
            </button>
        </div>
    </div>

    {{-- Alerts --}}
    @if ($errors->any())
    <div class="rol-alert-danger mb-3">
        <strong>Whoops!</strong>
        <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul>
    </div>
    @endif
    @if (session('success') || session('status'))
    <div class="rol-alert-success mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') ?? session('status') }}
    </div>
    @endif

    {{-- Role cards --}}
    @if($roles->count())
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-4 g-3">
        @foreach ($roles as $role)
        @php
            $roles_num = DB::table('model_has_roles')->where('role_id',$role->id)->count();
            $role_permissions = $role->permissions->pluck('name')->take(4);
            $icons = ['Admin'=>'bi-shield-check','Student'=>'bi-mortarboard','Staff'=>'bi-person-badge','Teacher'=>'bi-book'];
            $icon  = $icons[$role->name] ?? 'bi-key';
        @endphp
        <div class="col">
            <div class="rol-card">
                {{-- Card top --}}
                <div class="rol-card-top">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="rol-card-icon"><i class="bi {{ $icon }}"></i></div>
                        @canany(['Update user-role','Remove user-role'])
                        <div class="dropdown rol-dropdown">
                            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('Update user-role')
                                <li>
                                    <a class="dropdown-item" href="{{ route('roles.adduser', $role->id) }}">
                                        <i class="bi bi-person-plus-fill text-primary"></i> Add User
                                    </a>
                                </li>
                                @endcan
                                @can('View role')
                                <li>
                                    <a class="dropdown-item" href="{{ route('roles.show', $role->id) }}">
                                        <i class="bi bi-eye-fill text-success"></i> View Details
                                    </a>
                                </li>
                                @endcan
                                @can('Remove user-role')
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <form method="POST" action="{{ route('roles.destroy', $role->id) }}"
                                          onsubmit="return confirm('Delete role {{ $role->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-trash3-fill"></i> Delete Role
                                        </button>
                                    </form>
                                </li>
                                @endcan
                            </ul>
                        </div>
                        @endcanany
                    </div>
                    <div class="rol-card-name mt-1">{{ $role->name }}</div>
                    <div class="rol-card-count">
                        <strong>{{ $roles_num }}</strong> assigned user{{ $roles_num !== 1 ? 's' : '' }}
                    </div>
                </div>

                {{-- Permissions --}}
                <div class="rol-card-body">
                    <div style="font-size:10.5px;font-weight:700;color:var(--rol-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                        Permissions
                    </div>
                    @forelse($role_permissions as $perm)
                        <span class="rol-perm-tag"><i class="bi bi-check2"></i>{{ $perm }}</span>
                    @empty
                        <span style="font-size:12px;color:var(--rol-muted);">No permissions assigned</span>
                    @endforelse
                    @if($role->permissions->count() > 4)
                        @can('View role')
                        <br><a href="{{ route('roles.show', $role->id) }}" class="rol-perm-more mt-1 d-inline-block">
                            +{{ $role->permissions->count() - 4 }} more →
                        </a>
                        @endcan
                    @endif
                </div>

                {{-- Footer --}}
                <div class="rol-card-footer">
                    <span class="rol-users-badge">
                        <i class="bi bi-people-fill"></i> {{ $roles_num }} user{{ $roles_num !== 1 ? 's' : '' }}
                    </span>
                    @can('View role')
                    <a href="{{ route('roles.show', $role->id) }}" class="rol-view-link">
                        View <i class="bi bi-arrow-right"></i>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="rol-empty">
        <span class="rol-empty-icon"><i class="bi bi-shield-x"></i></span>
        <h4>No Roles Created Yet</h4>
        <p>Click "Create Role" to define your first role and assign permissions.</p>
    </div>
    @endif

    {{-- ════ ADD ROLE MODAL ════ --}}
    <div class="modal fade rol-modal" id="addRoleModalgrid" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle-fill me-2"></i>Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="kt_modal_add_role_form" action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="rol-form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="rol-form-control" name="name" placeholder="e.g. Administrator" required>
                            </div>
                            <div class="col-md-6">
                                <label class="rol-form-label">Role Badge Colour</label>
                                <select name="badge" class="rol-form-control">
                                    <option value="">Select colour…</option>
                                    <option value="badge bg-light">Light Grey</option>
                                    <option value="badge bg-dark">Dark</option>
                                    <option value="badge bg-primary">Blue</option>
                                    <option value="badge bg-secondary">Light Blue</option>
                                    <option value="badge bg-success">Green</option>
                                    <option value="badge bg-info">Purple</option>
                                    <option value="badge bg-warning">Yellow</option>
                                    <option value="badge bg-danger">Red</option>
                                </select>
                            </div>
                        </div>

                        <div style="background:#f0f4ff;border:1px solid #c7d2fe;border-radius:9px;padding:10px 14px;font-size:12.5px;color:#3730a3;margin-bottom:16px;">
                            <i class="bi bi-info-circle-fill me-1"></i> Tick the permissions this role should have. Use a group's checkbox to select everything under it at once, or the search box to find one quickly.
                        </div>

                        @include('roles.partials.permission-picker', ['pickerId' => 'createPermPicker', 'checkedIds' => []])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="rol-btn-primary">
                            <i class="bi bi-check-circle"></i> Create Role
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll     = document.getElementById('kt_roles_select_all');
    const permBoxes     = document.querySelectorAll('input[name="permission[]"]');
    const moduleToggles = document.querySelectorAll('.module-select-all');

    if (selectAll && permBoxes.length) {

        // Global "select all" → checks/unchecks everything, including module toggles
        selectAll.addEventListener('change', function () {
            permBoxes.forEach(c => c.checked = this.checked);
            moduleToggles.forEach(m => { m.checked = this.checked; m.indeterminate = false; });
        });

        // Per-module "select all" → checks/unchecks only that module's permissions
        moduleToggles.forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                const module = this.dataset.module;
                document.querySelectorAll('.module-perm[data-module="' + module + '"]')
                    .forEach(c => c.checked = this.checked);
                syncGlobal();
            });
        });

        // Keep each module toggle's checked/indeterminate state in sync with its own boxes
        function syncModuleToggles() {
            moduleToggles.forEach(function (toggle) {
                const module = toggle.dataset.module;
                const boxes  = document.querySelectorAll('.module-perm[data-module="' + module + '"]');
                const all    = Array.from(boxes).every(c => c.checked);
                const some   = Array.from(boxes).some(c => c.checked);
                toggle.checked       = all;
                toggle.indeterminate = some && !all;
            });
        }

        function syncGlobal() {
            const all  = Array.from(permBoxes).every(c => c.checked);
            const some = Array.from(permBoxes).some(c => c.checked);
            selectAll.checked       = all;
            selectAll.indeterminate = some && !all;
        }

        function sync() {
            syncModuleToggles();
            syncGlobal();
        }

        permBoxes.forEach(c => c.addEventListener('change', sync));
        sync();

        document.getElementById('kt_modal_add_role_form')
            .addEventListener('submit', function (e) {
                if (!Array.from(permBoxes).some(c => c.checked)) {
                    e.preventDefault();
                    Swal.fire({ icon:'warning', title:'No permissions selected',
                        text:'Please select at least one permission.', confirmButtonColor:'#6366f1' });
                }
            });
    }
});
</script>

@endsection
