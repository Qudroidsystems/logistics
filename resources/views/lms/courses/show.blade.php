{{-- resources/views/lms/courses/show.blade.php — course management dashboard --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$course->title" icon="ri-book-open-fill"
        :subtitle="($course->code ? $course->code.' · ' : '').(optional($course->schoolclass)->schoolclass ?? 'No class').' · '.($course->is_published ? 'Published' : 'Draft')"
        :back="route('lms.courses.index')" back-label="Courses">
        <x-slot name="actions">
            <a href="{{ route('lms.learn.show', $course) }}" class="action-btn btn-go" target="_blank"><i class="ri-eye-line"></i>Preview</a>
            <a href="{{ route('lms.enrollments.index', $course) }}" class="action-btn btn-go"><i class="ri-group-line"></i>Learners</a>
            <a href="{{ route('lms.gradebook.show', $course) }}" class="action-btn btn-go"><i class="ri-bar-chart-box-line"></i>Gradebook</a>
            <a href="{{ route('lms.courses.edit', $course) }}" class="action-btn btn-go"><i class="ri-edit-line"></i>Edit</a>
            <form method="POST" action="{{ route('lms.courses.duplicate', $course) }}" class="d-inline" onsubmit="return confirm('Make a draft copy of this course (content only, no learners)?')">@csrf
                <button class="action-btn btn-go"><i class="ri-file-copy-line"></i>Duplicate</button>
            </form>
            @if(Route::has('calendar.index'))
            <form method="POST" action="{{ route('lms.courses.sync-calendar', $course) }}" class="d-inline">@csrf
                <button class="action-btn btn-go" title="Add due dates & live classes to the school calendar"><i class="ri-calendar-2-line"></i>Sync calendar</button>
            </form>
            @endif
            <form method="POST" action="{{ route('lms.courses.publish', $course) }}" class="d-inline">@csrf
                <button class="action-btn {{ $course->is_published ? 'btn-open' : 'btn-primary-cb' }}"><i class="ri-global-line"></i>{{ $course->is_published ? 'Unpublish' : 'Publish' }}</button>
            </form>
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3 mb-1">
        <div class="col-6 col-lg-3"><x-cb.stat label="Lessons" :value="$course->lessons->count()" icon="ri-file-list-3-line" accent="info" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Learners" :value="$enrollCount" icon="ri-group-line" accent="violet" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Completed" :value="$completedCount" icon="ri-medal-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Coursework" :value="$assignments->count() + $quizzes->count()" icon="ri-task-line" accent="amber" /></div>
    </div>

    <ul class="nav nav-pills mb-3 gap-1" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabCurriculum" type="button"><i class="ri-list-check-2"></i> Curriculum</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabCoursework" type="button"><i class="ri-task-line"></i> Coursework</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabEngagement" type="button"><i class="ri-live-line"></i> Live &amp; Announcements</button></li>
    </ul>

    <div class="tab-content">
        {{-- ── CURRICULUM ─────────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tabCurriculum">
            <x-cb.card title="Curriculum" icon="ri-list-check-2">
                <x-slot name="tools">
                    <button class="action-btn btn-open" data-bs-toggle="modal" data-bs-target="#sectionModal"><i class="ri-folder-add-line"></i>Add section</button>
                    <button class="action-btn btn-primary-cb" data-bs-toggle="modal" data-bs-target="#lessonModal" onclick="lmsPrepLesson(this)" data-mode="create"><i class="ri-add-line"></i>Add lesson</button>
                </x-slot>

                @php $bySection = $course->lessons->groupBy('section_id'); @endphp
                @if($course->sections->count() || $course->lessons->count())
                    <p class="small text-muted mb-2"><i class="ri-drag-move-2-line"></i> Drag the handle to reorder sections and lessons; drag a lesson between sections to move it.</p>
                @endif

                <div id="sectionSortable">
                @forelse($course->sections as $section)
                    <div class="border rounded mb-2 lms-section" data-id="{{ $section->id }}">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light">
                            <div><i class="ri-draggable sec-handle me-1" style="cursor:grab" title="Drag to reorder"></i><i class="ri-folder-3-line me-1"></i><strong>{{ $section->title }}</strong>
                                @unless($section->is_published)<span class="status-pill st-muted ms-2">Hidden</span>@endunless
                                @if($section->description)<div class="small text-muted">{{ $section->description }}</div>@endif
                            </div>
                            <div class="d-flex gap-1">
                                <form method="POST" action="{{ route('lms.sections.destroy', [$course, $section]) }}" onsubmit="return confirm('Remove this section? Its lessons are kept.')">@csrf @method('DELETE')<button class="action-btn btn-open" title="Remove section"><i class="ri-delete-bin-line"></i></button></form>
                            </div>
                        </div>
                        @include('lms.courses._lesson-list', ['items' => $bySection->get($section->id, collect()), 'sectionId' => $section->id])
                    </div>
                @empty
                @endforelse
                </div>

                {{-- lessons with no section (always shown as a drop target) --}}
                @php $loose = $bySection->get(null, collect()); @endphp
                <div class="border rounded mb-2">
                    <div class="px-3 py-2 bg-light"><i class="ri-file-list-line me-1"></i><strong>Ungrouped lessons</strong></div>
                    @include('lms.courses._lesson-list', ['items' => $loose, 'sectionId' => ''])
                </div>

                @if($course->lessons->isEmpty() && $course->sections->isEmpty())
                    <div class="empty-state"><i class="ri-list-check-2"></i><h6>No content yet</h6><p>Add a section, then add lessons.</p></div>
                @endif
            </x-cb.card>
        </div>

        {{-- ── COURSEWORK ─────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tabCoursework">
            <div class="row g-3">
                <div class="col-lg-6">
                    <x-cb.card title="Assignments" icon="ri-file-edit-line" :count="$assignments->count()">
                        <x-slot name="tools"><button class="action-btn btn-primary-cb" data-bs-toggle="modal" data-bs-target="#assignmentModal"><i class="ri-add-line"></i>New</button></x-slot>
                        @forelse($assignments as $a)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">{{ $a->title }}</div>
                                    <div class="small text-muted">{{ $a->submissions_count }} submitted · {{ $a->max_score }} pts @if($a->due_at)· due {{ $a->due_at->format('d M Y') }}@endif</div>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('lms.assignments.submissions', [$course, $a]) }}" class="action-btn btn-open" title="Submissions"><i class="ri-inbox-line"></i></a>
                                    <form method="POST" action="{{ route('lms.assignments.destroy', [$course, $a]) }}" onsubmit="return confirm('Delete assignment and all submissions?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                                </div>
                            </div>
                        @empty<div class="empty-state"><i class="ri-file-edit-line"></i><p>No assignments.</p></div>@endforelse
                    </x-cb.card>
                </div>
                <div class="col-lg-6">
                    <x-cb.card title="Quizzes" icon="ri-questionnaire-line" :count="$quizzes->count()">
                        <x-slot name="tools"><button class="action-btn btn-primary-cb" data-bs-toggle="modal" data-bs-target="#quizModal"><i class="ri-add-line"></i>New</button></x-slot>
                        @forelse($quizzes as $q)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">{{ $q->title }}</div>
                                    <div class="small text-muted">{{ $q->questions_count }} questions · pass {{ $q->pass_mark }}% @unless($q->is_published)· <span class="text-warning">draft</span>@endunless</div>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('lms.quizzes.edit', [$course, $q]) }}" class="action-btn btn-open" title="Questions"><i class="ri-edit-line"></i></a>
                                    <a href="{{ route('lms.quizzes.results', [$course, $q]) }}" class="action-btn btn-open" title="Results"><i class="ri-bar-chart-line"></i></a>
                                    <form method="POST" action="{{ route('lms.quizzes.destroy', [$course, $q]) }}" onsubmit="return confirm('Delete quiz and attempts?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                                </div>
                            </div>
                        @empty<div class="empty-state"><i class="ri-questionnaire-line"></i><p>No quizzes.</p></div>@endforelse
                        <div class="cb-banner info mt-2"><i class="ri-information-line"></i><div class="small">For formal graded tests, add a lesson of type <strong>Graded test (CBT)</strong> linked to an exam from the CBT module.</div></div>
                    </x-cb.card>
                </div>
            </div>
        </div>

        {{-- ── ENGAGEMENT ─────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tabEngagement">
            <div class="row g-3">
                <div class="col-lg-6">
                    <x-cb.card title="Live classes" icon="ri-live-line" :count="$liveClasses->count()">
                        <x-slot name="tools"><button class="action-btn btn-primary-cb" data-bs-toggle="modal" data-bs-target="#liveModal"><i class="ri-add-line"></i>Schedule</button></x-slot>
                        @forelse($liveClasses as $lc)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">{{ $lc->title }}</div>
                                    <div class="small text-muted">{{ $lc->scheduled_at ? $lc->scheduled_at->format('D, d M Y H:i') : 'No time' }} · <span class="text-capitalize">{{ $lc->status }}</span></div>
                                </div>
                                <div class="d-flex gap-1">
                                    @if($lc->join_url)<a href="{{ $lc->join_url }}" target="_blank" class="action-btn btn-open" title="Join link"><i class="ri-external-link-line"></i></a>@endif
                                    <form method="POST" action="{{ route('lms.live.destroy', [$course, $lc]) }}" onsubmit="return confirm('Remove live class?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                                </div>
                            </div>
                        @empty<div class="empty-state"><i class="ri-live-line"></i><p>No live classes scheduled.</p></div>@endforelse
                    </x-cb.card>
                </div>
                <div class="col-lg-6">
                    <x-cb.card title="Announcements" icon="ri-megaphone-line" :count="$announcements->count()">
                        <form method="POST" action="{{ route('lms.announcements.store', $course) }}" class="mb-3">@csrf
                            <input name="title" class="form-control mb-2" placeholder="Announcement title" required>
                            <textarea name="body" rows="2" class="form-control mb-2" placeholder="Message to learners" required></textarea>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify" value="1" id="annNotify" checked><label class="form-check-label small" for="annNotify">Notify learners</label></div>
                                <button class="action-btn btn-primary-cb"><i class="ri-send-plane-line"></i>Post</button>
                            </div>
                        </form>
                        @forelse($announcements as $an)
                            <div class="border-bottom py-2">
                                <div class="d-flex justify-content-between"><div class="fw-semibold">{{ $an->title }}</div>
                                    <form method="POST" action="{{ route('lms.announcements.destroy', [$course, $an]) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                                </div>
                                <div class="small text-muted">{{ $an->created_at->diffForHumans() }}</div>
                                <div class="small">{{ Str::limit($an->body, 160) }}</div>
                            </div>
                        @empty<div class="empty-state"><i class="ri-megaphone-line"></i><p>No announcements.</p></div>@endforelse
                    </x-cb.card>
                </div>
            </div>
        </div>
    </div>
</div></div></div>

@include('lms.courses._modals')

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.6/Sortable.min.js"></script>
<script>
(function(){
    if(!window.Sortable) return;
    var CSRF = '{{ csrf_token() }}';
    function post(url, body){
        return fetch(url, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin', body:JSON.stringify(body)});
    }
    // Reorder sections
    var secWrap = document.getElementById('sectionSortable');
    if(secWrap){
        new Sortable(secWrap, {handle:'.sec-handle', animation:150, draggable:'.lms-section', onEnd:function(){
            var order = Array.from(secWrap.querySelectorAll('.lms-section')).map(function(el){return el.getAttribute('data-id');});
            post('{{ route('lms.sections.reorder', $course) }}', {order:order});
        }});
    }
    // Reorder / move lessons (shared group so they move between sections)
    document.querySelectorAll('tbody.lms-lesson-list').forEach(function(tb){
        new Sortable(tb, {group:'lms-lessons', handle:'.lms-lhandle', draggable:'tr[data-id]', animation:150, onEnd:function(evt){
            var target = evt.to;
            var order = Array.from(target.querySelectorAll('tr[data-id]')).map(function(tr){return tr.getAttribute('data-id');});
            post('{{ route('lms.lessons.reorder', $course) }}', {order:order, section_id: target.getAttribute('data-section')});
        }});
    });
})();
</script>
@endsection
