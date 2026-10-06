{{-- resources/views/lms/learn/catalog.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Course Catalog" icon="ri-compass-3-fill" subtitle="Courses open for self-enrolment."
        :back="route('lms.learn.index')" back-label="My Learning" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    @if($rows->isEmpty())
        <x-cb.card title="Catalog" icon="ri-compass-3-line"><div class="empty-state"><i class="ri-compass-3-line"></i><h6>Nothing to enrol in right now</h6></div></x-cb.card>
    @else
        <div class="row g-3">
            @foreach($rows as $c)
                <div class="col-md-6 col-lg-4">
                    <div class="cb-card h-100"><div class="cb-card-body d-flex flex-column">
                        <div class="fw-semibold mb-1"><i class="ri-book-open-line me-1"></i>{{ $c->title }}</div>
                        <div class="small text-muted flex-grow-1">{{ Str::limit($c->description, 110) ?: 'No description.' }}</div>
                        <form method="POST" action="{{ route('lms.learn.self-enroll', $c->id) }}" class="mt-2">@csrf
                            <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-user-add-line"></i>Enrol</button>
                        </form>
                    </div></div>
                </div>
            @endforeach
        </div>
        @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
    @endif
</div></div></div>
@endsection
