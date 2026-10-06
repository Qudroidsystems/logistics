{{-- resources/views/curriculum/progress/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="My Topics" icon="ri-task-fill" subtitle="Track the topics you've taught in each class." :back="route('dashboard')" back-label="Dashboard" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    @if($rows->isEmpty())
        <x-cb.card title="Classes" icon="ri-presentation-line"><div class="empty-state"><i class="ri-presentation-line"></i><h6>No classes assigned</h6><p>You have no subject assignments for the current session.</p></div></x-cb.card>
    @else
        <div class="row g-3">
            @foreach($rows as $r)
                <div class="col-md-6 col-lg-4">
                    <div class="cb-card h-100"><div class="cb-card-body d-flex flex-column">
                        <div class="fw-semibold">{{ $r->subject_name }}</div>
                        <div class="small text-muted mb-2">{{ $r->arm_name }}@if($r->term_name) · {{ $r->term_name }}@endif</div>
                        <div class="progress mb-1" style="height:8px"><div class="progress-bar {{ $r->pct>=70?'bg-success':'' }}" style="width: {{ $r->pct }}%"></div></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">{{ $r->taught }}/{{ $r->total }} taught ({{ $r->pct }}%)</span>
                            <a href="{{ route('curriculum.progress.board', $r->subjectclass_id) }}" class="action-btn btn-primary-cb"><i class="ri-list-check-2"></i>Open</a>
                        </div>
                    </div></div>
                </div>
            @endforeach
        </div>
    @endif
</div></div></div>
@endsection
