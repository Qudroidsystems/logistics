{{-- resources/views/lms/quizzes/results.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Results — '.$quiz->title" icon="ri-bar-chart-fill"
        :subtitle="'Pass mark '.$quiz->pass_mark.'% · '.$rows->total().' attempts'"
        :back="route('lms.quizzes.edit', [$course, $quiz])" back-label="Quiz" />

    <x-cb.card title="Attempts" icon="ri-bar-chart-line" :count="$rows->total()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-bar-chart-line"></i><h6>No attempts yet</h6></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th>Attempt</th><th class="text-end">Score</th><th class="text-end">%</th><th>Result</th><th>Submitted</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ trim(($r->firstname ?? '').' '.($r->lastname ?? '')) }}<div class="small text-muted">{{ $r->admissionNo ?? '' }}</div></td>
                        <td class="small">#{{ $r->attempt_no }}</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format($r->score,2),'0'),'.') }}/{{ rtrim(rtrim(number_format($r->max_score,2),'0'),'.') }}</td>
                        <td class="text-end">{{ $r->percent }}%</td>
                        <td><span class="status-pill {{ $r->passed ? 'st-paid' : 'st-danger' }}">{{ $r->passed ? 'Passed' : 'Failed' }}</span></td>
                        <td class="small">{{ $r->submitted_at ? \Illuminate\Support\Carbon::parse($r->submitted_at)->format('d M Y H:i') : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div></div></div>
@endsection
