{{-- resources/views/lms/learn/show.blade.php — learner course home --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$course->title" icon="ri-book-open-fill"
        :subtitle="$course->code ?: (optional($course->subject)->subject ?? '')"
        :back="route('lms.learn.index')" back-label="My Learning" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-8">
            @if($course->description)
                <x-cb.card title="About this course" icon="ri-information-line"><div class="small">{!! nl2br(e($course->description)) !!}</div></x-cb.card>
            @endif

            @php $bySection = $lessons->groupBy('section_id'); @endphp
            <x-cb.card title="Curriculum" icon="ri-list-check-2" :count="$lessons->count()">
                @php $icons = ['text'=>'ri-article-line','file'=>'ri-file-3-line','video_embed'=>'ri-video-line','video_upload'=>'ri-film-line','cbt'=>'ri-questionnaire-line','live'=>'ri-live-line']; @endphp
                @foreach($course->sections as $section)
                    @php $items = $bySection->get($section->id, collect()); @endphp
                    @if($items->count())
                        <div class="fw-semibold text-muted small text-uppercase mt-2 mb-1">{{ $section->title }}</div>
                        @foreach($items as $l)
                            @include('lms.learn._lesson-row', ['l' => $l])
                        @endforeach
                    @endif
                @endforeach
                @php $loose = $bySection->get(null, collect()); @endphp
                @foreach($loose as $l)
                    @include('lms.learn._lesson-row', ['l' => $l])
                @endforeach
                @if($lessons->isEmpty())<div class="empty-state"><i class="ri-list-check-2"></i><p>No lessons published yet.</p></div>@endif
            </x-cb.card>

            @if($assignments->count())
                <x-cb.card title="Assignments" icon="ri-file-edit-line" :count="$assignments->count()">
                    @foreach($assignments as $a)
                        @php $sub = $studentId ? $a->submissionFor($studentId) : null; @endphp
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div><div class="fw-semibold">{{ $a->title }}</div>
                                <div class="small text-muted">{{ $a->max_score }} pts @if($a->due_at)· due {{ $a->due_at->format('d M Y') }} @if($a->isOverdue())<span class="text-danger">(overdue)</span>@endif @endif</div></div>
                            <div class="text-end">
                                @if($sub && $sub->isGraded())<span class="status-pill st-paid">{{ rtrim(rtrim(number_format($sub->score,2),'0'),'.') }}/{{ $a->max_score }}</span>
                                @elseif($sub)<span class="status-pill st-info">Submitted</span>@endif
                                <button class="action-btn btn-open ms-1" data-bs-toggle="collapse" data-bs-target="#asg{{ $a->id }}"><i class="ri-upload-2-line"></i></button>
                            </div>
                        </div>
                        <div class="collapse" id="asg{{ $a->id }}">
                            <div class="border rounded p-2 my-2 bg-light">
                                @if($a->instructions)<div class="small mb-2">{!! nl2br(e($a->instructions)) !!}</div>@endif
                                @if($sub && $sub->feedback)<div class="cb-banner info"><i class="ri-chat-1-line"></i><div class="small"><strong>Feedback:</strong> {{ $sub->feedback }}</div></div>@endif
                                @if($studentId)
                                <form method="POST" action="{{ route('lms.learn.assignment.submit', [$course, $a]) }}" enctype="multipart/form-data">@csrf
                                    @if($a->allow_text)<textarea name="body" rows="3" class="form-control mb-2" placeholder="Your answer">{{ $sub->body ?? '' }}</textarea>@endif
                                    @if($a->allow_file)<input type="file" name="file" class="form-control mb-2">@if($sub && $sub->file_path)<div class="small mb-2">Current: <a href="{{ $sub->fileUrl() }}" target="_blank">{{ $sub->file_name }}</a></div>@endif @endif
                                    <button class="action-btn btn-primary-cb"><i class="ri-send-plane-line"></i>{{ $sub ? 'Resubmit' : 'Submit' }}</button>
                                </form>
                                @else<div class="small text-muted">Preview mode — enrol to submit.</div>@endif
                            </div>
                        </div>
                    @endforeach
                </x-cb.card>
            @endif

            @if($quizzes->count())
                <x-cb.card title="Quizzes" icon="ri-questionnaire-line" :count="$quizzes->count()">
                    @foreach($quizzes as $q)
                        @php $best = $studentId ? $q->bestAttempt($studentId) : null; $left = $studentId ? $q->attemptsLeft($studentId) : null; @endphp
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div><div class="fw-semibold">{{ $q->title }}</div>
                                <div class="small text-muted">{{ $q->questions()->count() }} questions · pass {{ $q->pass_mark }}%
                                    @if($best)· best {{ $best->percent }}%@endif
                                    @if($left !== null)· {{ $left }} attempt(s) left @endif</div></div>
                            @if($studentId && ($left === null || $left > 0))
                                <a href="{{ route('lms.learn.quiz', [$course, $q]) }}" class="action-btn btn-primary-cb"><i class="ri-play-line"></i>{{ $best ? 'Retake' : 'Start' }}</a>
                            @elseif($best)
                                <span class="status-pill {{ $best->passed ? 'st-paid' : 'st-danger' }}">{{ $best->percent }}%</span>
                            @endif
                        </div>
                    @endforeach
                </x-cb.card>
            @endif
        </div>

        <div class="col-lg-4">
            <x-cb.card title="Your progress" icon="ri-progress-4-line">
                <div class="text-center py-2">
                    <div class="display-6 fw-bold {{ $progress>=100 ? 'text-success' : 'text-primary' }}">{{ $progress }}%</div>
                    <div class="progress mt-2" style="height:10px"><div class="progress-bar {{ $progress>=100 ? 'bg-success' : '' }}" style="width: {{ $progress }}%"></div></div>
                    @if($progress>=100)<div class="small text-success mt-2"><i class="ri-medal-line"></i> Course completed!</div>@endif
                </div>
            </x-cb.card>

            @if($liveClasses->count())
                <x-cb.card title="Live classes" icon="ri-live-line">
                    @foreach($liveClasses as $lc)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold small">{{ $lc->title }}</div>
                            <div class="small text-muted">{{ $lc->scheduled_at ? $lc->scheduled_at->format('D, d M · H:i') : 'TBA' }}</div>
                            @if($lc->isJoinable())<a href="{{ $lc->join_url }}" target="_blank" class="action-btn btn-primary-cb mt-1"><i class="ri-vidicon-line"></i>Join now</a>
                            @elseif($lc->join_url && $lc->isUpcoming())<span class="status-pill st-info mt-1">Upcoming</span>
                            @elseif($lc->status==='ended')<span class="status-pill st-muted mt-1">Ended</span>@endif
                        </div>
                    @endforeach
                </x-cb.card>
            @endif

            @if($announcements->count())
                <x-cb.card title="Announcements" icon="ri-megaphone-line">
                    @foreach($announcements as $an)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold small">{{ $an->title }}</div>
                            <div class="small text-muted">{{ $an->created_at->diffForHumans() }}</div>
                            <div class="small">{{ Str::limit($an->body, 140) }}</div>
                        </div>
                    @endforeach
                </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
