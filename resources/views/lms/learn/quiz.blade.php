{{-- resources/views/lms/learn/quiz.blade.php — take a quiz --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$quiz->title" icon="ri-questionnaire-fill"
        :subtitle="$course->title.' · pass '.$quiz->pass_mark.'%'.($attemptsLeft!==null ? ' · '.$attemptsLeft.' attempt(s) left' : '')"
        :back="route('lms.learn.show', $course)" back-label="Course" />

    @if($quiz->description)<div class="cb-banner info"><i class="ri-information-line"></i><div>{{ $quiz->description }}</div></div>@endif

    <form method="POST" action="{{ route('lms.learn.quiz.submit', [$course, $quiz]) }}" id="quizForm">@csrf
        <input type="hidden" name="started_at" value="{{ now()->toDateTimeString() }}">

        @if($quiz->time_limit_minutes)
            <div class="cb-banner warning" id="timerBanner"><i class="ri-time-line"></i><div>Time remaining: <span id="timer" class="fw-bold"></span></div></div>
        @endif

        @foreach($questions as $i => $qn)
            <x-cb.card>
                <div class="fw-semibold mb-2"><span class="badge bg-secondary">Q{{ $i+1 }}</span> {{ $qn->question }}
                    <span class="text-muted small">({{ $qn->points }} pt @if($qn->type==='multiple') · select all that apply @endif)</span></div>
                @if($qn->imageUrl())<img src="{{ $qn->imageUrl() }}" class="img-fluid rounded mb-2" style="max-height:260px" alt="">@endif

                @if(in_array($qn->type, ['single','multiple','boolean']))
                    @foreach(($qn->options ?? []) as $oi => $opt)
                        <div class="form-check">
                            <input class="form-check-input" type="{{ $qn->type==='multiple' ? 'checkbox' : 'radio' }}"
                                   name="answers[{{ $qn->id }}]{{ $qn->type==='multiple' ? '[]' : '' }}"
                                   value="{{ $oi }}" id="q{{ $qn->id }}o{{ $oi }}">
                            <label class="form-check-label" for="q{{ $qn->id }}o{{ $oi }}">{{ $opt }}</label>
                        </div>
                    @endforeach
                @elseif(in_array($qn->type, ['short_answer','fill_blank']))
                    <input type="text" class="form-control" name="answers[{{ $qn->id }}]" placeholder="Your answer" autocomplete="off">
                @else
                    <textarea class="form-control" rows="5" name="answers[{{ $qn->id }}]" placeholder="Write your answer…"></textarea>
                    <div class="small text-muted mt-1"><i class="ri-quill-pen-line"></i> This answer will be graded by your teacher.</div>
                @endif
            </x-cb.card>
        @endforeach

        @if($questions->isEmpty())
            <x-cb.card><div class="empty-state"><i class="ri-questionnaire-line"></i><p>This quiz has no questions yet.</p></div></x-cb.card>
        @else
            <button class="action-btn btn-primary-cb"><i class="ri-send-plane-line"></i>Submit quiz</button>
        @endif
    </form>
</div></div></div>

@if($quiz->time_limit_minutes)
<script>
(function(){
    var secs = {{ (int)$quiz->time_limit_minutes * 60 }};
    var el = document.getElementById('timer');
    var t = setInterval(function(){
        var m = Math.floor(secs/60), s = secs%60;
        el.textContent = m + ':' + (s<10?'0':'') + s;
        if(secs<=0){ clearInterval(t); document.getElementById('quizForm').submit(); }
        secs--;
    }, 1000);
})();
</script>
@endif
@endsection
