@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">

    @include('partials.flash')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">User Management</h4>
        @can('Create user')
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="ri-add-line"></i> New user</button>
        @endcan
    </div>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, email, phone or username"></div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">All roles</option>
                    @foreach($roles as $r)<option value="{{ $r->name }}" @selected(request('role') === $r->name)>{{ $r->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Any status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="disabled" @selected(request('status') === 'disabled')>Disabled</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-light w-100">Filter</button></div>
        </div>
    </form>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Contact</th><th>Roles</th><th>Last sign-in</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td><div class="d-flex align-items-center gap-2">
                        <img src="{{ $u->avatar_url }}" class="rounded-circle" width="34" height="34" alt="">
                        <a href="{{ route('users.show', $u->id) }}" class="fw-medium">{{ $u->name }}</a></div></td>
                    <td><div>{{ $u->email }}</div><small class="text-muted">{{ $u->phone_number }}</small></td>
                    <td>@foreach($u->roles as $r)<span class="badge bg-primary-subtle text-primary">{{ $r->name }}</span> @endforeach</td>
                    <td>{{ $u->last_login_at?->diffForHumans() ?? '—' }}</td>
                    <td>@if($u->is_disabled)<span class="badge bg-danger">Disabled</span>@else<span class="badge bg-success">Active</span>@endif</td>
                    <td class="text-end"><a href="{{ route('users.show', $u->id) }}" class="btn btn-sm btn-light">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $users->links() }}</div>

    @can('Create user')
    <div class="modal fade" id="createUserModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('users.store') }}" class="modal-content">
        @csrf
        <div class="modal-header"><h5 class="modal-title">New user</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label class="form-label">Full name</label><input name="name" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">Phone</label><input name="phone_number" class="form-control"></div>
            <div class="mb-2"><label class="form-label">Username</label><input name="username" class="form-control"></div>
            <div class="mb-2"><label class="form-label">Role</label>
                <select name="role" class="form-select"><option value="">— none —</option>
                    @foreach($roles as $r)<option value="{{ $r->name }}">{{ $r->name }}</option>@endforeach</select></div>
            <small class="text-muted">A temporary password is generated and shown once after saving.</small>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Create user</button></div>
    </form></div></div>
    @endcan

</div></div></div>
@endsection
