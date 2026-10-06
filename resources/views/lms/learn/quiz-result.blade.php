{{-- resources/views/lms/learn/quiz-result.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Quiz result'" icon="ri-award-fill" :subtitle="$quiz->title"
        :back="route('lms.learn.show', $course)" back-label="Course" />

    <x-cb.card>
        @if($attempt->needs_review)
            <div class="text-center py-3">
                <i class="ri-time-line display-5 text-warning"></i>
                <div class="fw-semibold mt-2">Awaiting grading</div>
                <div class="small text-muted">Some answers need your teacher's review. Your final score will show here once graded.</div>
                <div class="small text-muted mt-1">Auto-graded so far: {{ rtrim(rtrim(number_format($attempt->score,2),'0'),'.') }} / {{ rtrim(rtrim(number_format($attempt->max_score,2),'0'),'.') }} points</div>
            </div>
        @else
            <div class="text-center py-3">
                <div class="display-4 fw-bold {{ $attempt->passed ? 'text-success' : 'text-danger' }}">{{ $attempt->percent }}%</div>
                <div class="mb-2">{{ rtrim(rtrim(number_format($attempt->score,2),'0'),'.') }} / {{ rtrim(rtrim(number_format($attempt->max_score,2),'0'),'.') }} points</div>
                <span class="status-pill {{ $attempt->passed ? 'st-paid' : 'st-danger' }}">{{ $attempt->passed ? 'Passed' : 'Not passed' }}</span>
            </div>
        @endif
    </x-cb.card>

    <x-cb.card title="Review" icon="ri-file-list-3-line">
        @php $answers = $attempt->answers ?? []; $marks = $attempt->marks ?? []; @endphp
        @foreach($questions as $i => $qn)
            @php $ans = $answers[$qn->id] ?? null; @endphp
            <div class="border rounded p-2 mb-2">
                @if(in_array($qn->type, ['single','multiple','boolean']))
                    @php $sel = array_map('intval', (array)$ans); $right = $qn->isCorrect($sel); @endphp
                    <div class="fw-semibold mb-1"><i class="ri-{{ $right ? 'checkbox-circle-fill text-success' : 'close-circle-fill text-danger' }}"></i> Q{{ $i+1 }}. {{ $qn->question }}</div>
                    <ul class="mb-0 small">
                        @foreach(($qn->options ?? []) as $oi => $opt)
                            @php $isKey = in_array($oi, $qn->correct ?? []); $isSel = in_array($oi, $sel); @endphp
                            <li class="{{ $isKey ? 'text-success fw-semibold' : ($isSel ? 'text-danger' : '') }}">{{ $opt }}
                                @if($isKey)<i class="ri-check-line"></i>@endif
                                @if($isSel && !$isKey)<i class="ri-close-line"></i> (your answer)@endif
                                @if($isSel && $isKey)(your answer)@endif
                            </li>
                        @endforeach
                    </ul>
                @elseif(in_array($qn->type, ['short_answer','fill_blank']))
                    @php $awarded = (float)($marks[$qn->id] ?? 0); $right = $awarded >= (float)$qn->points && $qn->points > 0; @endphp
                    <div class="fw-semibold mb-1"><i class="ri-{{ $right ? 'checkbox-circle-fill text-success' : 'close-circle-fill text-danger' }}"></i> Q{{ $i+1 }}. {{ $qn->question }}</div>
                    <div class="small">Your answer: <span class="{{ $right ? 'text-success' : 'text-danger' }}">{{ $ans !== null && $ans !== '' ? $ans : '—' }}</span></div>
                    @unless($right)<div class="small text-success">Accepted: {{ implode(' · ', $qn->accepted_answers ?? []) }}</div>@endunless
                @else
                    @php $awarded = $marks[$qn->id] ?? null; @endphp
                    <div class="fw-semibold mb-1"><i class="ri-quill-pen-line text-muted"></i> Q{{ $i+1 }}. {{ $qn->question }}</div>
                    <div class="border rounded bg-light p-2 small" style="white-space:pre-wrap">{{ $ans !== null && $ans !== '' ? $ans : '—' }}</div>
                    <div class="small mt-1">{{ $attempt->needs_review ? 'Awaiting grading' : 'Awarded: '.rtrim(rtrim(number_format((float)$awarded,2),'0'),'.').' / '.$qn->points }}</div>
                @endif
                @if($qn->explanation)<div class="small text-muted mt-1"><i class="ri-information-line"></i> {{ $qn->explanation }}</div>@endif
            </div>
        @endforeach
    </x-cb.card>

    <a href="{{ route('lms.learn.show', $course) }}" class="action-btn btn-primary-cb"><i class="ri-arrow-left-line"></i>Back to course</a>
</div></div></div>
@endsection
