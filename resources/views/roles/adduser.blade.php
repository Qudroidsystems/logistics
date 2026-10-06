@extends('layouts.master')
@section('content')
<?php
use Spatie\Permission\Models\Role;
use App\Models\User;
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap">

<style>
:root {
    --rol-primary:  #0f172a;
    --rol-accent:   #6366f1;
    --rol-accent2:  #8b5cf6;
    --rol-success:  #10b981;
    --rol-border:   #e2e8f0;
    --rol-surface:  #ffffff;
    --rol-surface2: #f8fafc;
    --rol-muted:    #64748b;
    --rol-radius:   14px;
    --rol-shadow:   0 4px 20px rgba(15,23,42,.07);
    --rol-font:     'Plus Jakarta Sans', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: var(--rol-font); }

@keyframes slideDown { from{opacity:0;transform:translateY(-20px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeUp    { from{opacity:0;transform:translateY(14px)}  to{opacity:1;transform:translateY(0)} }
@keyframes pulse     { 0%,100%{transform:scale(1)} 50%{transform:scale(1.06)} }

.rol-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 45%, #312e81 100%);
    border-radius: var(--rol-radius); padding: 30px 36px; margin-bottom: 24px;
    position: relative; overflow: hidden;
    animation: slideDown .5s cubic-bezier(.22,1,.36,1);
}
.rol-hero::before {
    content: ''; position: absolute; top: -80px; right: -80px;
    width: 260px; height: 260px; background: rgba(99,102,241,.14);
    border-radius: 50%; animation: pulse 4s ease-in-out infinite;
}
.rol-hero h1 { font-size: 22px; font-weight: 800; color: #fff; margin: 0 0 5px; position: relative; z-index: 1; }
.rol-hero p  { font-size: 13px; color: rgba(255,255,255,.65); margin: 0; position: relative; z-index: 1; }
.rol-hero-actions { position: relative; z-index: 1; margin-top: 18px; display: flex; gap: 8px; }

.rol-btn { display:inline-flex; align-items:center; gap:6px; border:none; border-radius:9px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; font-family:var(--rol-font); transition:transform .15s,box-shadow .15s; text-decoration:none; }
.rol-btn-primary { background:linear-gradient(135deg,var(--rol-accent),var(--rol-accent2)); color:#fff; box-shadow:0 3px 12px rgba(99,102,241,.3); }
.rol-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(99,102,241,.4); color:#fff; }
.rol-btn-back    { background:#fff; color:var(--rol-primary); border:1.5px solid var(--rol-border); }
.rol-btn-back:hover { background:var(--rol-surface2); color:var(--rol-primary); }

.rol-card {
    background: var(--rol-surface); border: 1px solid var(--rol-border);
    border-radius: var(--rol-radius); overflow: hidden; box-shadow: var(--rol-shadow);
    animation: fadeUp .4s ease .1s both;
}
.rol-card-header { padding: 14px 20px; border-bottom: 1px solid var(--rol-border); background: var(--rol-surface); }
.rol-card-header h5 { font-size: 14px; font-weight: 700; color: var(--rol-primary); margin: 0; }
.rol-card-body { padding: 24px; }

.rol-form-label { font-size: 11.5px; font-weight: 700; color: var(--rol-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; display: block; }
.rol-form-control { border: 1.5px solid var(--rol-border); border-radius: 9px; padding: 10px 14px; font-size: 13px; font-family: var(--rol-font); width: 100%; transition: border-color .2s, box-shadow .2s; background: #fff; }
.rol-form-control:focus { border-color: var(--rol-accent); box-shadow: 0 0 0 3px rgba(99,102,241,.12); outline: none; }
.rol-form-control[readonly] { background: var(--rol-surface2); cursor: default; }

.rol-role-chip {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, var(--rol-accent), var(--rol-accent2));
    color: #fff; border-radius: 10px; padding: 10px 16px;
    font-size: 14px; font-weight: 700;
    box-shadow: 0 3px 12px rgba(99,102,241,.3);
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="rol-hero">
        <h1><i class="bi bi-person-plus-fill me-2"></i>Add User to Role</h1>
        <p>Assign a user to the <strong style="color:rgba(255,255,255,.9);">{{ $role->name }}</strong> role.</p>
        <div class="rol-hero-actions">
            <a href="{{ route('roles.index') }}" class="rol-btn rol-btn-back">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="rol-card">
        <div class="rol-card-header">
            <h5><i class="bi bi-person-badge-fill me-2" style="color:var(--rol-accent)"></i>Role Assignment</h5>
        </div>
        <div class="rol-card-body">

            <div class="mb-4">
                <div class="rol-role-chip">
                    <i class="bi bi-shield-check-fill"></i>
                    {{ $role->name }}
                </div>
                <p style="font-size:12.5px;color:var(--rol-muted);margin-top:8px;">
                    The selected user will be granted all permissions attached to this role.
                </p>
            </div>

            <form action="{{ route('roles.updateuserrole') }}" method="GET">
                @csrf
                <input type="hidden" name="roleid" value="{{ $role->id }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="rol-form-label">Role</label>
                        <input type="text" class="rol-form-control" value="{{ $role->name }}" readonly>
                    </div>
                    <div class="col-md-5">
                        <label class="rol-form-label">Select User <span class="text-danger">*</span></label>
                        <select name="name" class="rol-form-control" required>
                            <option value="">Choose a user…</option>
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="rol-btn rol-btn-primary" style="width:100%;justify-content:center;">
                            <i class="bi bi-person-check-fill"></i> Add User
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

</div>
</div>
</div>
@endsection
