{{-- resources/views/lms/quizzes/analysis.blade.php — per-question difficulty --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Item analysis — '.$quiz->title" icon="ri-bar-chart-2-fill"
        :subtitle="$attempts.' attempt(s) analysed'"
        :back="route('lms.quizzes.edit', [$course, $quiz])" back-label="Quiz" />

    @if(!$attempts)
        <x-cb.card><div class="empty-state"><i class="ri-bar-chart-2-line"></i><h6>No attempts yet</h6><p>Analysis appears once students take this quiz.</p></div></x-cb.card>
    @else
        <x-cb.card title="Questions" icon="ri-list-ordered">
            @foreach($stats as $i => $st)
                @php $qn = $st['question']; $pct = $st['pct']; $band = $pct===null ? 'muted' : ($pct>=70?'paid':($pct>=40?'pending':'danger')); @endphp
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pe-2"><span class="badge bg-secondary">Q{{ $i+1 }}</span> {{ $qn->question }}
                            <div class="small text-muted">{{ \App\Models\LmsQuizQuestion::TYPES[$qn->type] ?? $qn->type }} · {{ $qn->points }} pt · {{ $st['answered'] }} answered</div>
                        </div>
                        <span class="status-pill st-{{ $band }}">{{ $pct===null ? '—' : $pct.'% correct' }}</span>
                    </div>
                    @if($pct!==null)
                        <div class="progress mt-2" style="height:6px"><div class="progress-bar {{ $pct>=70?'bg-success':($pct>=40?'':'bg-danger') }}" style="width: {{ $pct }}%"></div></div>
                    @endif
                    @if($qn->isChoice() && count($st['tally']))
                        <ul class="mb-0 mt-2 small">
                            @foreach(($qn->options ?? []) as $oi => $opt)
                                @php $n = $st['tally'][$oi] ?? 0; $isKey = in_array($oi, $qn->correct ?? []); @endphp
                                <li class="{{ $isKey ? 'text-success fw-semibold' : '' }}">{{ $opt }} — {{ $n }} pick(s) @if($isKey)<i class="ri-check-line"></i>@endif</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </x-cb.card>
    @endif
</div></div></div>
@endsection
