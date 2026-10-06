@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="card"><div class="card-body">
        <h5>Welcome, {{ auth()->user()->name }}</h5>
        <p class="text-muted mb-0">Use the menu to get started.</p>
    </div></div>
</div></div></div>
@endsection
