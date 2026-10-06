{{-- Partial: sortable lesson rows for a section. Expects $items, $course, optional $sectionId. --}}
@php $sid = isset($sectionId) ? $sectionId : ''; @endphp
<div class="table-responsive">
<table class="table align-middle mb-0">
<tbody class="lms-lesson-list" data-section="{{ $sid }}">
@php
$icons = ['text'=>'ri-article-line','file'=>'ri-file-3-line','video_embed'=>'ri-video-line','video_upload'=>'ri-film-line','cbt'=>'ri-questionnaire-line','live'=>'ri-live-line'];
@endphp
@forelse($items as $l)
    <tr data-id="{{ $l->id }}">
        <td style="width:28px" class="text-muted text-center"><i class="ri-draggable lms-lhandle" style="cursor:grab" title="Drag to reorder"></i></td>
        <td style="width:32px" class="text-muted"><i class="{{ $icons[$l->type] ?? 'ri-file-line' }}"></i></td>
        <td>
            <span class="fw-semibold">{{ $l->title }}</span>
            <div class="small text-muted">{{ $l->typeLabel() }}@if($l->duration_minutes) · {{ $l->duration_minutes }} min @endif
                @if($l->is_preview) · <span class="text-info">preview</span>@endif
                @unless($l->is_published) · <span class="text-warning">hidden</span>@endunless
            </div>
        </td>
        <td class="text-end" style="white-space:nowrap">
            <button class="action-btn btn-open" title="Edit"
                data-bs-toggle="modal" data-bs-target="#lessonModal" onclick="lmsPrepLesson(this)"
                data-mode="edit"
                data-id="{{ $l->id }}"
                data-title="{{ e($l->title) }}"
                data-type="{{ $l->type }}"
                data-section="{{ $l->section_id }}"
                data-content="{{ e($l->content) }}"
                data-video="{{ e($l->video_url) }}"
                data-exam="{{ $l->exam_id }}"
                data-duration="{{ $l->duration_minutes }}"
                data-preview="{{ $l->is_preview ? 1 : 0 }}"
                data-published="{{ $l->is_published ? 1 : 0 }}"
                data-attachment="{{ e($l->attachment_name) }}"><i class="ri-edit-line"></i></button>
            <form method="POST" action="{{ route('lms.lessons.destroy', [$course, $l]) }}" class="d-inline" onsubmit="return confirm('Delete this lesson?')">@csrf @method('DELETE')<button class="action-btn btn-open" title="Delete"><i class="ri-delete-bin-line"></i></button></form>
        </td>
    </tr>
@empty
    <tr class="lms-empty-row"><td colspan="4" class="small text-muted px-3 py-2">No lessons here yet — add one, or drag a lesson in.</td></tr>
@endforelse
</tbody>
</table>
</div>
