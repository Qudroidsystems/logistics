{{-- Partial: a single lesson row in the learner curriculum. Expects $l, $course, $doneIds, $icons. --}}
@php $done = in_array((int)$l->id, $doneIds ?? [], true); @endphp
<a href="{{ route('lms.learn.lesson', [$course, $l]) }}" class="d-flex align-items-center gap-2 border-bottom py-2 text-decoration-none text-body">
    <i class="{{ $done ? 'ri-checkbox-circle-fill text-success' : ($icons[$l->type] ?? 'ri-file-line').' text-muted' }}"></i>
    <span class="flex-grow-1">{{ $l->title }}
        @if($l->is_preview)<span class="badge bg-info-subtle text-info ms-1">preview</span>@endif
    </span>
    <span class="small text-muted">{{ $l->typeLabel() }}@if($l->duration_minutes) · {{ $l->duration_minutes }}m @endif</span>
    <i class="ri-arrow-right-s-line text-muted"></i>
</a>
