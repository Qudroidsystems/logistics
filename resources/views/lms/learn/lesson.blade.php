{{-- resources/views/lms/learn/lesson.blade.php — lesson player --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$lesson->title" icon="ri-play-circle-fill"
        :subtitle="$course->title.' · '.$lesson->typeLabel()"
        :back="route('lms.learn.show', $course)" back-label="Course home" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card>
                {{-- ── Body by type ── --}}
                @if($lesson->type === 'video_embed' && $lesson->embedUrl())
                    <div class="ratio ratio-16x9 mb-3"><iframe src="{{ $lesson->embedUrl() }}" allowfullscreen frameborder="0"></iframe></div>
                @elseif($lesson->type === 'video_upload' && $lesson->attachmentUrl())
                    <video controls class="w-100 rounded mb-3" src="{{ $lesson->attachmentUrl() }}"></video>
                @elseif($lesson->type === 'file' && $lesson->attachmentUrl())
                    <div class="cb-banner info mb-3"><i class="ri-file-download-line"></i><div>Lesson material:
                        <a href="{{ $lesson->attachmentUrl() }}" target="_blank" class="fw-semibold">{{ $lesson->attachment_name ?: 'Download file' }}</a></div></div>
                    @php $ext = strtolower(pathinfo($lesson->attachment_name ?? '', PATHINFO_EXTENSION)); @endphp
                    @if($ext === 'pdf')<div class="ratio ratio-4x3 mb-3"><iframe src="{{ $lesson->attachmentUrl() }}"></iframe></div>@endif
                @elseif($lesson->type === 'cbt')
                    <div class="text-center py-3">
                        <i class="ri-questionnaire-line display-5 text-primary"></i>
                        <h5 class="mt-2">Graded test</h5>
                        @if($exam)
                            <p class="text-muted small">{{ $exam->title }}</p>
                            <a href="{{ route('cbt.take', $exam->id) }}" class="action-btn btn-primary-cb"><i class="ri-play-line"></i>Start test</a>
                        @else
                            <p class="text-muted small">No test has been linked to this lesson yet.</p>
                        @endif
                    </div>
                @elseif($lesson->type === 'live')
                    <div class="cb-banner info mb-3"><i class="ri-live-line"></i><div>This is a live session — check the <a href="{{ route('lms.learn.show', $course) }}">course home</a> for the join link and schedule.</div></div>
                @endif

                @if($lesson->content)
                    <div class="lesson-content">{!! $lesson->content !!}</div>
                @endif

                {{-- lesson quizzes --}}
                @if($quizzes->count())
                    <hr>
                    <h6><i class="ri-questionnaire-line"></i> Quizzes for this lesson</h6>
                    @foreach($quizzes as $q)
                        @php $left = $studentId ? $q->attemptsLeft($studentId) : null; $best = $studentId ? $q->bestAttempt($studentId) : null; @endphp
                        <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-1">
                            <span>{{ $q->title }} <span class="small text-muted">· pass {{ $q->pass_mark }}%@if($best) · best {{ $best->percent }}%@endif</span></span>
                            @if($studentId && ($left===null || $left>0))<a href="{{ route('lms.learn.quiz', [$course, $q]) }}" class="action-btn btn-primary-cb"><i class="ri-play-line"></i>Take</a>@endif
                        </div>
                    @endforeach
                @endif

                {{-- complete + nav --}}
                <hr>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        @if($prev)<a href="{{ route('lms.learn.lesson', [$course, $prev]) }}" class="action-btn btn-open"><i class="ri-arrow-left-line"></i>Previous</a>@endif
                    </div>
                    <div class="d-flex gap-2">
                        @if($studentId)
                        <form method="POST" action="{{ route('lms.learn.lesson.complete', [$course, $lesson]) }}">@csrf
                            <input type="hidden" name="completed" value="{{ $completed ? 0 : 1 }}">
                            <button class="action-btn {{ $completed ? 'btn-open' : 'btn-primary-cb' }}">
                                <i class="ri-{{ $completed ? 'close' : 'check' }}-line"></i>{{ $completed ? 'Mark incomplete' : 'Mark complete' }}
                            </button>
                        </form>
                        @endif
                        @if($next)<a href="{{ route('lms.learn.lesson', [$course, $next]) }}" class="action-btn btn-primary-cb">Next<i class="ri-arrow-right-line"></i></a>@endif
                    </div>
                </div>
            </x-cb.card>

            {{-- Discussions / Q&A --}}
            <x-cb.card title="Discussion" icon="ri-chat-3-line" :count="$discussions->count()">
                <form method="POST" action="{{ route('lms.learn.discussion.store', $course) }}" class="mb-3">@csrf
                    <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">
                    <textarea name="body" rows="2" class="form-control mb-2" placeholder="Ask a question or share a thought" required></textarea>
                    <button class="action-btn btn-primary-cb"><i class="ri-send-plane-line"></i>Post</button>
                </form>
                @forelse($discussions as $d)
                    <div class="border rounded p-2 mb-2 {{ $d->is_pinned ? 'border-primary' : '' }}">
                        <div class="d-flex justify-content-between">
                            <div class="small">
                                <strong>{{ optional($d->author)->name ?? 'User' }}</strong>
                                <span class="text-muted">· {{ $d->created_at->diffForHumans() }}</span>
                                @if($d->is_pinned)<span class="badge bg-primary-subtle text-primary">pinned</span>@endif
                                @if($d->is_resolved)<span class="badge bg-success-subtle text-success">resolved</span>@endif
                            </div>
                            <div class="d-flex gap-1">
                                @if($canModerate)
                                    <form method="POST" action="{{ route('lms.discussions.pin', [$course, $d]) }}">@csrf<button class="action-btn btn-open" title="Pin"><i class="ri-pushpin-line"></i></button></form>
                                    <form method="POST" action="{{ route('lms.discussions.resolve', [$course, $d]) }}">@csrf<button class="action-btn btn-open" title="Resolve"><i class="ri-check-double-line"></i></button></form>
                                @endif
                                @if($canModerate || $d->user_id === auth()->id())
                                    <form method="POST" action="{{ route('lms.learn.discussion.destroy', [$course, $d]) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                                @endif
                            </div>
                        </div>
                        <div class="mt-1">{{ $d->body }}</div>

                        @foreach($d->replies as $r)
                            <div class="border-start ps-2 ms-2 mt-2">
                                <div class="small"><strong>{{ optional($r->author)->name ?? 'User' }}</strong> <span class="text-muted">· {{ $r->created_at->diffForHumans() }}</span></div>
                                <div class="small">{{ $r->body }}</div>
                            </div>
                        @endforeach

                        <form method="POST" action="{{ route('lms.learn.discussion.store', $course) }}" class="mt-2 d-flex gap-2">@csrf
                            <input type="hidden" name="parent_id" value="{{ $d->id }}">
                            <input name="body" class="form-control form-control-sm" placeholder="Reply…" required>
                            <button class="btn btn-sm btn-outline-primary"><i class="ri-reply-line"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state"><i class="ri-chat-3-line"></i><p>No discussion yet. Start one!</p></div>
                @endforelse
            </x-cb.card>
        </div>

        {{-- side: lesson list --}}
        <div class="col-lg-4">
            <x-cb.card title="Lessons" icon="ri-list-check-2" :count="$lessons->count()">
                @foreach($lessons as $li)
                    @php $d = in_array((int)$li->id, $doneIds, true); @endphp
                    <a href="{{ route('lms.learn.lesson', [$course, $li]) }}" class="d-flex align-items-center gap-2 py-1 text-decoration-none {{ $li->id===$lesson->id ? 'fw-semibold text-primary' : 'text-body' }}">
                        <i class="{{ $d ? 'ri-checkbox-circle-fill text-success' : 'ri-circle-line text-muted' }}"></i>
                        <span class="small flex-grow-1">{{ $li->title }}</span>
                    </a>
                @endforeach
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
