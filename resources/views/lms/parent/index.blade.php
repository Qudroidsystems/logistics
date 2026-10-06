{{-- resources/views/lms/parent/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Children — Learning" icon="ri-parent-fill" subtitle="Your children's e-learning progress." />

    @if($children->isEmpty())
        <x-cb.card title="Children" icon="ri-user-line"><div class="empty-state"><i class="ri-user-line"></i><h6>No children linked</h6></div></x-cb.card>
    @else
        <div class="row g-3">
            @foreach($children as $c)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('lms.parent.child', $c->id) }}" class="text-decoration-none">
                        <div class="cb-card h-100"><div class="cb-card-body d-flex align-items-center gap-3">
                            <i class="ri-graduation-cap-fill fs-3 text-primary"></i>
                            <div><div class="fw-semibold text-body">{{ trim(($c->firstname ?? '').' '.($c->lastname ?? '')) }}</div>
                                <div class="small text-muted">{{ $c->admissionNo ?? '' }}</div></div>
                        </div></div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div></div></div>
@endsection
