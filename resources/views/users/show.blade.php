@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">

    @include('partials.flash')

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card"><div class="card-body text-center">
                <img src="{{ $user->avatar_url }}" class="rounded-circle mb-2" width="90" height="90" alt="">
                <h5 class="mb-0">{{ $user->name }}</h5>
                <div class="text-muted">{{ $user->email }}</div>
                <div class="text-muted">{{ $user->phone_number }}</div>
                <div class="mt-2">@foreach($user->roles as $r)<span class="badge bg-primary-subtle text-primary">{{ $r->name }}</span> @endforeach</div>
                <hr>
                <div class="small text-muted">Last sign-in: {{ $user->last_login_at?->diffForHumans() ?? 'never' }}</div>
                @can('Update user')
                <div class="d-grid gap-2 mt-3">
                    <form method="POST" action="{{ route('users.toggle-disabled', $user->id) }}">@csrf
                        <button class="btn btn-outline-{{ $user->is_disabled ? 'success' : 'warning' }} w-100">{{ $user->is_disabled ? 'Enable account' : 'Disable account' }}</button></form>
                    <form method="POST" action="{{ route('users.reset-password', $user->id) }}" onsubmit="return confirm('Reset this user\'s password?')">@csrf
                        <button class="btn btn-outline-secondary w-100">Reset password</button></form>
                </div>
                @endcan
            </div></div>
        </div>
        <div class="col-lg-8">
            @can('Update user')
            <div class="card"><div class="card-header"><h6 class="mb-0">Details</h6></div>
            <form method="POST" action="{{ route('users.update', $user->id) }}" class="card-body">
                @csrf @method('PUT')
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Username</label><input name="username" value="{{ old('username', $user->username) }}" class="form-control"></div>
                </div>
                <button class="btn btn-primary mt-3">Save changes</button>
            </form></div>
            @endcan
            <div class="card"><div class="card-header"><h6 class="mb-0">Roles &amp; permissions</h6></div><div class="card-body">
                <p class="text-muted mb-2">Roles are assigned from <a href="{{ route('roles.index') }}">Roles</a>. Effective permissions: {{ $user->getAllPermissions()->count() }}.</p>
            </div></div>
        </div>
    </div>

</div></div></div>
@endsection
