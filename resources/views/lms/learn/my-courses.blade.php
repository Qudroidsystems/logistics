{{-- resources/views/lms/learn/my-courses.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="My Learning" icon="ri-graduation-cap-fill" subtitle="Your enrolled courses and progress.">
        @if($catalog->count())
        <x-slot name="actions"><a href="{{ route('lms.learn.catalog') }}" class="action-btn btn-go"><i class="ri-compass-3-line"></i>Browse catalog</a></x-slot>
        @endif
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    @php $resume = $rows->first(fn($c) => (int)$c->progress_percent > 0 && (int)$c->progress_percent < 100); @endphp
    @if($resume)
        <div class="cb-card mb-3" style="border-left:4px solid var(--bs-primary,#4f46e5)">
            <div class="cb-card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="small text-muted"><i class="ri-history-line"></i> Continue where you left off</div>
                    <div class="fw-semibold">{{ $resume->title }}</div>
                    <div class="progress mt-1" style="height:6px;width:220px"><div class="progress-bar" style="width: {{ (int)$resume->progress_percent }}%"></div></div>
                </div>
                <a href="{{ route('lms.learn.show', $resume->id) }}" class="action-btn btn-primary-cb"><i class="ri-play-line"></i>Resume ({{ (int)$resume->progress_percent }}%)</a>
            </div>
        </div>
    @endif

    @if($rows->isEmpty())
        <x-cb.card title="Courses" icon="ri-book-open-line">
            <div class="empty-state"><i class="ri-book-open-line"></i><h6>You're not enrolled in any course yet</h6>
                @if($catalog->count())<p><a href="{{ route('lms.learn.catalog') }}">Browse the catalog</a> to enrol.</p>@else<p>Your teachers will enrol you soon.</p>@endif
            </div>
        </x-cb.card>
    @else
        <div class="row g-3">
            @foreach($rows as $c)
                <div class="col-md-6 col-lg-4">
                    <div class="cb-card h-100">
                        <div class="cb-card-body d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="ri-book-open-fill fs-4 text-primary"></i>
                                <div><div class="fw-semibold">{{ $c->title }}</div><div class="small text-muted">{{ $c->code ?: '' }}</div></div>
                            </div>
                            @if($c->description)<p class="small text-muted flex-grow-1">{{ Str::limit($c->description, 90) }}</p>@else<div class="flex-grow-1"></div>@endif
                            <div class="progress mb-1" style="height:8px"><div class="progress-bar {{ $c->enrol_status==='completed' ? 'bg-success' : '' }}" style="width: {{ (int)$c->progress_percent }}%"></div></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">{{ (int)$c->progress_percent }}% @if($c->enrol_status==='completed')· <span class="text-success">Completed</span>@endif</span>
                                <a href="{{ route('lms.learn.show', $c->id) }}" class="action-btn btn-primary-cb"><i class="ri-play-line"></i>{{ (int)$c->progress_percent>0 ? 'Continue' : 'Start' }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div></div></div>
@endsection
