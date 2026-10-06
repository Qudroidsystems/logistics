{{-- resources/views/lms/assignments/submissions.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$assignment->title" icon="ri-inbox-fill"
        :subtitle="'Submissions · '.$subs->total().' of '.$enrolled.' learners · max '.$assignment->max_score.' pts'"
        :back="route('lms.courses.show', $course)" back-label="Course" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <x-cb.card title="Submissions" icon="ri-inbox-line" :count="$subs->total()" :flush="true">
        @if($subs->isEmpty())
            <div class="empty-state"><i class="ri-inbox-line"></i><h6>No submissions yet</h6></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th>Submitted</th><th>Response</th><th>Status</th><th style="width:260px" class="text-end">Grade</th></tr></thead>
                <tbody>
                @foreach($subs as $s)
                    <tr>
                        <td>{{ trim(($s->firstname ?? '').' '.($s->lastname ?? '')) }}<div class="small text-muted">{{ $s->admissionNo ?? '' }}</div></td>
                        <td class="small">{{ $s->submitted_at ? \Illuminate\Support\Carbon::parse($s->submitted_at)->format('d M Y H:i') : '—' }}</td>
                        <td class="small">
                            @if($s->body)<button class="action-btn btn-open" data-bs-toggle="collapse" data-bs-target="#body{{ $s->id }}"><i class="ri-file-text-line"></i>Text</button>@endif
                            @if($s->file_path)<a href="{{ asset('storage/'.$s->file_path) }}" target="_blank" class="action-btn btn-open"><i class="ri-download-line"></i>{{ Str::limit($s->file_name ?: 'file', 18) }}</a>@endif
                            @if($s->body)<div class="collapse mt-2" id="body{{ $s->id }}"><div class="border rounded p-2 bg-light">{!! nl2br(e($s->body)) !!}</div></div>@endif
                        </td>
                        <td><span class="status-pill {{ $s->status==='graded' ? 'st-paid' : 'st-pending' }}">{{ ucfirst($s->status) }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('lms.assignments.grade', [$course, $assignment, $s->id]) }}" class="d-flex flex-column gap-1">@csrf
                                @if($assignment->hasRubric())
                                    @php $rs = json_decode($s->rubric_scores ?? '[]', true) ?: []; @endphp
                                    @foreach($assignment->rubric as $ci => $crit)
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text" style="max-width:150px" title="{{ $crit['name'] }}">{{ Str::limit($crit['name'], 16) }}</span>
                                            <input type="number" step="0.01" min="0" max="{{ $crit['max'] }}" name="rubric_scores[{{ $ci }}]" value="{{ $rs[$ci] ?? '' }}" class="form-control" placeholder="0">
                                            <span class="input-group-text">/ {{ $crit['max'] }}</span>
                                        </div>
                                    @endforeach
                                    <input name="feedback" value="{{ $s->feedback }}" class="form-control form-control-sm" placeholder="Feedback (optional)">
                                    <button class="btn btn-sm btn-primary"><i class="ri-check-line"></i> Save ({{ rtrim(rtrim(number_format((float)($s->score ?? 0),2),'0'),'.') }}/{{ $assignment->max_score }})</button>
                                @else
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0" max="{{ $assignment->max_score }}" name="score" value="{{ $s->score }}" class="form-control" placeholder="Score">
                                        <span class="input-group-text">/ {{ $assignment->max_score }}</span>
                                        <button class="btn btn-sm btn-primary"><i class="ri-check-line"></i></button>
                                    </div>
                                    <input name="feedback" value="{{ $s->feedback }}" class="form-control form-control-sm" placeholder="Feedback (optional)">
                                @endif
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($subs->hasPages())<div class="mt-3">{{ $subs->links() }}</div>@endif
</div></div></div>
@endsection
