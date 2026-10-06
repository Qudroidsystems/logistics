{{-- resources/views/exam/coverage/report.blade.php — per-paper coverage & alignment --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Coverage — '.$paper->title" icon="ri-bar-chart-2-fill"
        :subtitle="$label.' · '.$paper->typeLabel()"
        :back="route('exam.papers.show', $paper)" back-label="Paper" />

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><x-cb.stat :value="$snap['examined_pct'].'%'" label="Syllabus examined" icon="ri-focus-3-line" accent="teal" /></div>
        <div class="col-6 col-md-3"><x-cb.stat :value="$snap['alignment_pct'].'%'" label="Marks on taught topics" icon="ri-links-line" accent="violet" /></div>
        <div class="col-6 col-md-3"><x-cb.stat :value="$snap['taught_count'].'/'.$snap['syllabus_total']" label="Topics taught" icon="ri-book-open-line" accent="amber" /></div>
        <div class="col-6 col-md-3"><x-cb.stat :value="rtrim(rtrim(number_format($snap['untaught_marks'],2),'0'),'.')" label="Marks on untaught topics" icon="ri-error-warning-line" accent="rose" /></div>
    </div>

    @if($snap['untaught_marks'] > 0)
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div><strong>{{ rtrim(rtrim(number_format($snap['untaught_marks'],2),'0'),'.') }} mark(s)</strong> test topics not yet marked taught for this class.</div></div>
    @endif
    @if($snap['untagged_count'] > 0)
        <div class="cb-banner info"><i class="ri-price-tag-3-line"></i><div><strong>{{ $snap['untagged_count'] }} question(s)</strong> have no topic tag, so they can't be scored against the syllabus. Tag them for full coverage.</div></div>
    @endif

    <x-cb.card title="Topic-by-topic" icon="ri-table-line" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Topic</th><th class="text-center">Wk</th><th class="text-center">Taught</th><th class="text-center">Examined</th><th class="text-center">Marks</th><th class="text-center">Qs</th><th class="text-center">Avg score</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->title }}</td>
                    <td class="text-center text-muted">{{ $r->week_no ?: '—' }}</td>
                    <td class="text-center">@if($r->taught)<i class="ri-check-line text-success"></i>@else<i class="ri-close-line text-muted"></i>@endif</td>
                    <td class="text-center">
                        @if($r->examined)<span class="status-pill st-paid">Yes</span>
                        @elseif($r->taught)<span class="status-pill st-pending">Taught, not tested</span>
                        @else<span class="status-pill st-muted">—</span>@endif
                    </td>
                    <td class="text-center">{{ $r->examined ? rtrim(rtrim(number_format($r->marks,2),'0'),'.') : '—' }}</td>
                    <td class="text-center">{{ $r->questions ?: '—' }}</td>
                    <td class="text-center">
                        @if($r->avg_pct !== null)
                            <span class="status-pill {{ $r->avg_pct >= 50 ? 'st-paid' : 'st-danger' }}">{{ $r->avg_pct }}%</span>
                        @else<span class="text-muted">—</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-3">No syllabus topics set for this subject/level. Add them under Curriculum → Topics.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    @if($untagged->count())
    <x-cb.card title="Untagged questions" icon="ri-price-tag-3-line" :count="$untagged->count()">
        <p class="small text-muted">These aren't linked to any topic, so they're invisible to coverage and the parent report. Edit the paper to tag them.</p>
        <ol class="mb-0">@foreach($untagged as $q)<li class="small">{{ \Illuminate\Support\Str::limit($q->question, 120) }} <span class="text-muted">({{ rtrim(rtrim(number_format($q->marks,2),'0'),'.') }} mk)</span></li>@endforeach</ol>
    </x-cb.card>
    @endif
</div></div></div>
@endsection
