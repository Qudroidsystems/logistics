{{-- resources/views/lms/quizzes/review.blade.php — grade essay / manual answers --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Grade attempts — '.$quiz->title" icon="ri-quill-pen-line"
        :subtitle="$attempts->count().' attempt(s) awaiting grading'"
        :back="route('lms.quizzes.edit', [$course, $quiz])" back-label="Quiz">
        <x-slot name="actions"><a href="{{ route('lms.quizzes.results', [$course, $quiz]) }}" class="action-btn btn-go"><i class="ri-bar-chart-line"></i>All results</a></x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    @php $manual = $quiz->questions->whereIn('type', ['essay']); @endphp

    @forelse($attempts as $a)
        @php $st = $students[$a->student_id] ?? null; $ans = $a->answers ?? []; $mk = $a->marks ?? []; @endphp
        <x-cb.card :title="$st ? trim(($st->firstname ?? '').' '.($st->lastname ?? '')) : ('Student #'.$a->student_id)"
                   icon="ri-user-line">
            <x-slot name="tools"><span class="small text-muted">Submitted {{ $a->submitted_at ? $a->submitted_at->format('d M Y H:i') : '—' }} · auto-subtotal {{ rtrim(rtrim(number_format((float)$a->score,2),'0'),'.') }}/{{ rtrim(rtrim(number_format((float)$a->max_score,2),'0'),'.') }}</span></x-slot>

            <form method="POST" action="{{ route('lms.attempts.grade', [$course, $quiz, $a]) }}">@csrf
                @foreach($quiz->questions as $qn)
                    @if($qn->type === 'essay')
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold small mb-1">{{ $qn->question }} <span class="text-muted">({{ $qn->points }} pts)</span></div>
                            <div class="border rounded bg-light p-2 mb-2 small" style="white-space:pre-wrap">{{ $ans[$qn->id] ?? '—' }}</div>
                            <div class="input-group input-group-sm" style="max-width:220px">
                                <span class="input-group-text">Marks</span>
                                <input type="number" step="0.01" min="0" max="{{ $qn->points }}" name="marks[{{ $qn->id }}]" value="{{ $mk[$qn->id] ?? '' }}" class="form-control" placeholder="0">
                                <span class="input-group-text">/ {{ $qn->points }}</span>
                            </div>
                        </div>
                    @endif
                @endforeach
                <button class="action-btn btn-primary-cb"><i class="ri-check-double-line"></i>Save &amp; finalise</button>
            </form>
        </x-cb.card>
    @empty
        <x-cb.card><div class="empty-state"><i class="ri-check-double-line"></i><h6>Nothing to grade</h6><p>All attempts have been graded.</p></div></x-cb.card>
    @endforelse
</div></div></div>
@endsection
