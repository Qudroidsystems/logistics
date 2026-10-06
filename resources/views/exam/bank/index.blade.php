{{-- resources/views/exam/bank/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Exam Question Bank" icon="ri-archive-fill" subtitle="Reusable questions, tagged to syllabus topics, that teachers pull into papers." :back="route('dashboard')" back-label="Dashboard" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <x-cb.card title="Filter" icon="ri-filter-3-line">
        <form method="GET" class="row g-2">
            <div class="col-md-3"><select name="subject_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All subjects</option>
                @foreach($subjects as $s)<option value="{{ $s->id }}" @selected($subjectId==$s->id)>{{ $s->subject }}</option>@endforeach
            </select></div>
            <div class="col-md-3"><select name="class_level" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All levels</option>
                @foreach($levels as $l)<option value="{{ $l }}" @selected($level===$l)>{{ $l }}</option>@endforeach
            </select></div>
            <div class="col-md-2"><select name="difficulty" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any difficulty</option>
                @foreach(['easy','medium','hard'] as $d)<option value="{{ $d }}" @selected($difficulty===$d)>{{ ucfirst($d) }}</option>@endforeach
            </select></div>
            <div class="col-md-4"><input name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search question text…"></div>
        </form>
    </x-cb.card>

    <div class="row g-3 mt-0">
        @if($canManage)
        <div class="col-lg-5">
            <x-cb.card title="Add a question" icon="ri-add-line">
                @if(!$subjectId || !$level)
                    <div class="small text-muted">Pick a <strong>subject</strong> and <strong>level</strong> above to add a question (so it can be tagged to that syllabus).</div>
                @else
                <form method="POST" action="{{ route('exam.bank.store') }}">@csrf
                    <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                    <input type="hidden" name="class_level" value="{{ $level }}">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small">Type</label>
                            <select name="type" class="form-select form-select-sm" id="bType" onchange="toggleOpts()">
                                <option value="theory">Theory</option><option value="objective">Objective</option><option value="practical">Practical</option>
                            </select></div>
                        <div class="col-3"><label class="form-label small">Marks</label><input type="number" step="0.5" min="0" name="marks" value="1" class="form-control form-control-sm"></div>
                        <div class="col-3"><label class="form-label small">Level</label>
                            <select name="difficulty" class="form-select form-select-sm"><option value="">—</option><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option></select></div>
                    </div>
                    <label class="form-label small mt-2">Question *</label>
                    <textarea name="question" rows="2" class="form-control form-control-sm" required></textarea>
                    <div id="optsBox" style="display:none">
                        <label class="form-label small mt-2">Options</label>
                        @foreach(['A','B','C','D'] as $L)<div class="input-group input-group-sm mb-1"><span class="input-group-text">{{ $L }}</span><input name="options[{{ $L }}]" class="form-control"></div>@endforeach
                    </div>
                    <label class="form-label small mt-2">Answer / marking guide</label>
                    <input name="answer" class="form-control form-control-sm">
                    <label class="form-label small mt-2">Topics</label>
                    @forelse($topics as $t)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="topics[]" value="{{ $t->id }}" id="bt{{ $t->id }}"><label class="form-check-label small" for="bt{{ $t->id }}">{{ $t->title }}@if($t->week_no) (Wk {{ $t->week_no }})@endif</label></div>
                    @empty<div class="small text-muted">No syllabus topics for this subject/level yet.</div>@endforelse
                    <button class="action-btn btn-primary-cb w-100 justify-content-center mt-3"><i class="ri-add-line"></i>Add to bank</button>
                </form>
                @endif
            </x-cb.card>
        </div>
        @endif

        <div class="col-lg-{{ $canManage ? 7 : 12 }}">
            <x-cb.card title="Bank" icon="ri-archive-line" :count="$items->total()" :flush="true">
                @if($items->isEmpty())
                    <div class="empty-state"><i class="ri-archive-line"></i><p>No questions match.</p></div>
                @else
                    @foreach($items as $b)
                        <div class="border-bottom px-3 py-2">
                            <div class="d-flex justify-content-between">
                                <div class="small">{{ $b->question }}</div>
                                <div class="small text-muted ms-2" style="white-space:nowrap">{{ rtrim(rtrim(number_format($b->marks,2),'0'),'.') }} mk</div>
                            </div>
                            <div class="small text-muted mt-1">
                                {{ ucfirst($b->type) }}@if($b->difficulty) · {{ ucfirst($b->difficulty) }}@endif
                                @foreach($b->topics as $t)<span class="status-pill st-info ms-1">{{ $t->title }}</span>@endforeach
                                @unless($b->is_active)<span class="status-pill st-muted ms-1">inactive</span>@endunless
                            </div>
                            @if($canManage)
                            <div class="mt-1 d-flex gap-1">
                                <form method="POST" action="{{ route('exam.bank.update', $b) }}" class="d-inline">@csrf @method('PUT')
                                    <input type="hidden" name="is_active" value="{{ $b->is_active ? 0 : 1 }}">
                                    <button class="action-btn btn-open" title="{{ $b->is_active ? 'Deactivate' : 'Activate' }}"><i class="ri-{{ $b->is_active ? 'eye-off' : 'eye' }}-line"></i></button>
                                </form>
                                <form method="POST" action="{{ route('exam.bank.destroy', $b) }}" class="d-inline" onsubmit="return confirm('Remove from bank?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                            </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </x-cb.card>
            @if($items->hasPages())<div class="mt-3">{{ $items->links() }}</div>@endif
        </div>
    </div>

    <script>
    function toggleOpts(){ document.getElementById('optsBox').style.display = document.getElementById('bType').value==='objective'?'block':'none'; }
    document.addEventListener('DOMContentLoaded', toggleOpts);
    </script>
</div></div></div>
@endsection
