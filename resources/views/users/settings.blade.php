@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid" style="max-width:820px">

    @include('partials.flash')

    <div class="card"><div class="card-header"><h6 class="mb-0">Profile</h6></div>
    <form method="POST" action="{{ route('profile.update', $user->id) }}" enctype="multipart/form-data" class="card-body">
        @csrf @method('PUT')
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="form-control"></div>
            <div class="col-md-12"><label class="form-label">Photo</label><input type="file" name="avatar" accept="image/*" class="form-control"></div>
        </div>
        <button class="btn btn-primary mt-3">Save profile</button>
    </form></div>

    @if($user->id === auth()->id())
    <div class="card"><div class="card-header"><h6 class="mb-0">Change password</h6></div>
    <form method="POST" action="{{ route('profile.password', $user->id) }}" class="card-body">
        @csrf @method('PUT')
        <div class="row g-2">
            <div class="col-md-4"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">New password</label><input type="password" name="password" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Confirm</label><input type="password" name="password_confirmation" class="form-control" required></div>
        </div>
        <button class="btn btn-primary mt-3">Change password</button>
    </form></div>
    @endif

</div></div></div>
@endsection
