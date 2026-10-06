{{-- resources/views/lms/quizzes/edit.blade.php — quiz settings + question builder --}}
@extends('layouts.master')

@section('content')
@php $pending = $quiz->attempts()->where('needs_review', true)->count(); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$quiz->title" icon="ri-questionnaire-fill"
        :subtitle="'Quiz builder · '.$quiz->questions->count().' questions · '.$quiz->totalPoints().' pts'"
        :back="route('lms.courses.show', $course)" back-label="Course">
        <x-slot name="actions">
            @if($pending)
                <a href="{{ route('lms.quizzes.review', [$course, $quiz]) }}" class="action-btn btn-primary-cb"><i class="ri-quill-pen-line"></i>Grade attempts ({{ $pending }})</a>
            @endif
            <button type="button" class="action-btn btn-go" data-bs-toggle="modal" data-bs-target="#bankModal"><i class="ri-database-2-line"></i>Import from bank</button>
            <a href="{{ route('lms.quizzes.analysis', [$course, $quiz]) }}" class="action-btn btn-go"><i class="ri-bar-chart-2-line"></i>Item analysis</a>
            <a href="{{ route('lms.quizzes.results', [$course, $quiz]) }}" class="action-btn btn-go"><i class="ri-bar-chart-line"></i>Results</a>
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-4">
            <x-cb.card title="Settings" icon="ri-settings-3-line">
                <form method="POST" action="{{ route('lms.quizzes.update', [$course, $quiz]) }}">@csrf @method('PUT')
                    <label class="form-label small">Title *</label><input name="title" value="{{ $quiz->title }}" class="form-control mb-2" required>
                    <label class="form-label small">Description</label><textarea name="description" rows="2" class="form-control mb-2">{{ $quiz->description }}</textarea>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small">Pass %</label><input type="number" min="0" max="100" name="pass_mark" value="{{ $quiz->pass_mark }}" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">Max attempts</label><input type="number" min="0" name="max_attempts" value="{{ $quiz->max_attempts }}" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">Time limit (min)</label><input type="number" min="0" name="time_limit_minutes" value="{{ $quiz->time_limit_minutes }}" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">Lesson</label>
                            <select name="lesson_id" class="form-select"><option value="">—</option>@foreach($course->lessons as $l)<option value="{{ $l->id }}" @selected($quiz->lesson_id==$l->id)>{{ $l->title }}</option>@endforeach</select></div>
                        <div class="col-6"><label class="form-label small">Opens</label><input type="datetime-local" name="available_from" value="{{ optional($quiz->available_from)->format('Y-m-d\TH:i') }}" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">Closes</label><input type="datetime-local" name="available_until" value="{{ optional($quiz->available_until)->format('Y-m-d\TH:i') }}" class="form-control"></div>
                    </div>
                    <div class="d-flex gap-3 mt-2 flex-wrap">
                        <div class="form-check form-switch"><input type="hidden" name="shuffle" value="0"><input class="form-check-input" type="checkbox" name="shuffle" value="1" id="qs" @checked($quiz->shuffle)><label class="form-check-label small" for="qs">Shuffle</label></div>
                        <div class="form-check form-switch"><input type="hidden" name="allow_partial" value="0"><input class="form-check-input" type="checkbox" name="allow_partial" value="1" id="qpartial" @checked($quiz->allow_partial)><label class="form-check-label small" for="qpartial">Partial credit</label></div>
                        <div class="form-check form-switch"><input type="hidden" name="is_published" value="0"><input class="form-check-input" type="checkbox" name="is_published" value="1" id="qp" @checked($quiz->is_published)><label class="form-check-label small" for="qp">Published</label></div>
                    </div>
                    <button class="action-btn btn-primary-cb w-100 justify-content-center mt-3"><i class="ri-save-line"></i>Save settings</button>
                </form>
            </x-cb.card>
        </div>

        <div class="col-lg-8">
            <x-cb.card title="Questions" icon="ri-list-ordered" :count="$quiz->questions->count()">
                @forelse($quiz->questions as $i => $qn)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <div><span class="badge bg-secondary">Q{{ $i+1 }}</span> <strong>{{ $qn->question }}</strong>
                                <span class="text-muted small">· {{ \App\Models\LmsQuizQuestion::TYPES[$qn->type] ?? ucfirst($qn->type) }} · {{ $qn->points }} pt</span></div>
                            <div class="d-flex gap-1">
                                <button class="action-btn btn-open" title="Edit" onclick="lmsEditQuestion(this)"
                                    data-id="{{ $qn->id }}"
                                    data-question="{{ e($qn->question) }}"
                                    data-type="{{ $qn->type }}"
                                    data-points="{{ $qn->points }}"
                                    data-options="{{ e(json_encode($qn->options ?? [])) }}"
                                    data-correct="{{ e(json_encode($qn->correct ?? [])) }}"
                                    data-accepted="{{ e(json_encode($qn->accepted_answers ?? [])) }}"
                                    data-explanation="{{ e($qn->explanation) }}"
                                    data-image="{{ $qn->imageUrl() }}"><i class="ri-edit-line"></i></button>
                                <form method="POST" action="{{ route('lms.questions.to-bank', [$course, $quiz, $qn]) }}" title="Save to bank">@csrf<button class="action-btn btn-open"><i class="ri-save-3-line"></i></button></form>
                                <form method="POST" action="{{ route('lms.questions.destroy', [$course, $quiz, $qn]) }}" onsubmit="return confirm('Remove question?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                            </div>
                        </div>
                        @if($qn->imageUrl())<img src="{{ $qn->imageUrl() }}" class="img-fluid rounded my-1" style="max-height:120px" alt="">@endif
                        @if(in_array($qn->type, ['single','multiple','boolean']))
                            <ul class="mb-0 mt-1 small">
                                @foreach(($qn->options ?? []) as $oi => $opt)
                                    <li class="{{ in_array($oi, $qn->correct ?? []) ? 'text-success fw-semibold' : '' }}">{{ $opt }} @if(in_array($oi, $qn->correct ?? []))<i class="ri-check-line"></i>@endif</li>
                                @endforeach
                            </ul>
                        @elseif(in_array($qn->type, ['short_answer','fill_blank']))
                            <div class="small mt-1 text-muted">Accepted: <span class="text-success">{{ implode(' · ', $qn->accepted_answers ?? []) }}</span></div>
                        @else
                            <div class="small mt-1 text-muted"><i class="ri-quill-pen-line"></i> Essay — graded manually</div>
                        @endif
                        @if($qn->explanation)<div class="small mt-1 text-muted"><i class="ri-information-line"></i> {{ $qn->explanation }}</div>@endif
                    </div>
                @empty
                    <div class="empty-state"><i class="ri-list-ordered"></i><p>No questions yet.</p></div>
                @endforelse

                <hr>
                <h6 class="mb-2"><i class="ri-add-line"></i> <span id="qFormTitle">Add question</span></h6>
                <form method="POST" action="{{ route('lms.questions.store', [$course, $quiz]) }}" id="qForm"
                      enctype="multipart/form-data"
                      data-store="{{ route('lms.questions.store', [$course, $quiz]) }}"
                      data-update="{{ route('lms.questions.update', [$course, $quiz, 0]) }}"
                      onsubmit="return lmsQPrepare()">@csrf
                    <input type="hidden" name="_method" id="qMethod" value="POST">
                    <input type="hidden" name="remove_image" id="qRemoveImage" value="0">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-md-8"><label class="form-label small">Question *</label><textarea name="question" rows="2" class="form-control" required></textarea></div>
                        <div class="col-md-2"><label class="form-label small">Type</label>
                            <select name="type" id="qType" class="form-select" onchange="lmsQType()">
                                @foreach(\App\Models\LmsQuizQuestion::TYPES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                            </select></div>
                        <div class="col-md-2"><label class="form-label small">Points</label><input type="number" min="1" name="points" value="1" class="form-control"></div>
                    </div>

                    {{-- choice options --}}
                    <div id="optWrap" class="qblock">
                        <label class="form-label small">Options (tick the correct one/s)</label>
                        <div id="optRows"></div>
                        <button type="button" class="action-btn btn-open mt-1" onclick="lmsAddOpt()"><i class="ri-add-line"></i>Add option</button>
                    </div>

                    {{-- accepted answers (short answer / fill blank) --}}
                    <div id="accWrap" class="qblock" style="display:none">
                        <label class="form-label small">Accepted answers (any match is correct, case-insensitive)</label>
                        <div id="accRows"></div>
                        <button type="button" class="action-btn btn-open mt-1" onclick="lmsAccRow('')"><i class="ri-add-line"></i>Add accepted answer</button>
                    </div>

                    {{-- essay note --}}
                    <div id="essayWrap" class="qblock" style="display:none">
                        <div class="cb-banner info"><i class="ri-information-line"></i><div class="small">Essay answers are graded manually under <strong>Grade attempts</strong> after students submit.</div></div>
                    </div>

                    <div class="mt-2">
                        <label class="form-label small">Explanation (shown to students after they submit) — optional</label>
                        <textarea name="explanation" id="qExplanation" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small">Image (optional)</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <div id="qImgCurrent" class="small mt-1"></div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="action-btn btn-primary-cb" id="qSubmit"><i class="ri-add-circle-line"></i>Add question</button>
                        <button type="button" class="action-btn btn-open d-none" id="qCancel" onclick="lmsResetQuestionForm()"><i class="ri-close-line"></i>Cancel edit</button>
                    </div>
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>

<script>
function lmsEsc(v){ return String(v==null?'':v).replace(/"/g,'&quot;'); }
function lmsOptRow(val, checked){
    var wrap = document.getElementById('optRows');
    var row = document.createElement('div');
    row.className = 'input-group input-group-sm mb-1 optrow';
    row.innerHTML = '<span class="input-group-text"><input type="checkbox" class="optcheck"'+(checked?' checked':'')+'></span>'
        + '<input type="text" class="form-control opttext" placeholder="Option text" value="'+lmsEsc(val)+'">'
        + '<button type="button" class="btn btn-outline-danger" onclick="this.closest(\'.optrow\').remove()"><i class="ri-close-line"></i></button>';
    wrap.appendChild(row);
}
function lmsAccRow(val){
    var wrap = document.getElementById('accRows');
    var row = document.createElement('div');
    row.className = 'input-group input-group-sm mb-1 accrow';
    row.innerHTML = '<input type="text" class="form-control acctext" placeholder="Accepted answer" value="'+lmsEsc(val)+'">'
        + '<button type="button" class="btn btn-outline-danger" onclick="this.closest(\'.accrow\').remove()"><i class="ri-close-line"></i></button>';
    wrap.appendChild(row);
}
function lmsQType(){
    var t = document.getElementById('qType').value;
    document.getElementById('optWrap').style.display   = (t==='single'||t==='multiple'||t==='boolean') ? '' : 'none';
    document.getElementById('accWrap').style.display   = (t==='short_answer'||t==='fill_blank') ? '' : 'none';
    document.getElementById('essayWrap').style.display = (t==='essay') ? '' : 'none';
    if(t==='boolean'){
        var wrap=document.getElementById('optRows'); wrap.innerHTML=''; lmsOptRow('True'); lmsOptRow('False');
    } else if((t==='single'||t==='multiple') && !document.querySelectorAll('#optRows .optrow').length){
        lmsOptRow(''); lmsOptRow('');
    } else if((t==='short_answer'||t==='fill_blank') && !document.querySelectorAll('#accRows .accrow').length){
        lmsAccRow('');
    }
}
function lmsAddOpt(){ if(document.getElementById('qType').value!=='boolean') lmsOptRow(''); }
function lmsResetQuestionForm(){
    var f=document.getElementById('qForm');
    f.action=f.getAttribute('data-store');
    document.getElementById('qMethod').value='POST';
    document.getElementById('qRemoveImage').value='0';
    document.getElementById('qFormTitle').textContent='Add question';
    document.getElementById('qSubmit').innerHTML='<i class="ri-add-circle-line"></i>Add question';
    document.getElementById('qCancel').classList.add('d-none');
    f.querySelector('[name=question]').value='';
    document.getElementById('qType').value='single';
    f.querySelector('[name=points]').value='1';
    document.getElementById('qExplanation').value='';
    document.getElementById('qImgCurrent').innerHTML='';
    document.getElementById('optRows').innerHTML='';
    document.getElementById('accRows').innerHTML='';
    lmsQType();
}
function lmsEditQuestion(btn){
    var g=function(a){return btn.getAttribute(a);};
    var f=document.getElementById('qForm');
    f.action=f.getAttribute('data-update').replace(/0$/, g('data-id'));
    document.getElementById('qMethod').value='PUT';
    document.getElementById('qRemoveImage').value='0';
    document.getElementById('qFormTitle').textContent='Edit question';
    document.getElementById('qSubmit').innerHTML='<i class="ri-save-line"></i>Save question';
    document.getElementById('qCancel').classList.remove('d-none');
    f.querySelector('[name=question]').value=g('data-question')||'';
    document.getElementById('qType').value=g('data-type')||'single';
    f.querySelector('[name=points]').value=g('data-points')||'1';
    document.getElementById('qExplanation').value=g('data-explanation')||'';
    var opts=[], corr=[], acc=[];
    try{opts=JSON.parse(g('data-options')||'[]');}catch(e){}
    try{corr=JSON.parse(g('data-correct')||'[]');}catch(e){}
    try{acc=JSON.parse(g('data-accepted')||'[]');}catch(e){}
    document.getElementById('optRows').innerHTML='';
    document.getElementById('accRows').innerHTML='';
    opts.forEach(function(o,i){ lmsOptRow(o, corr.indexOf(i)!==-1); });
    acc.forEach(function(a){ lmsAccRow(a); });
    lmsQType();
    var img=g('data-image');
    document.getElementById('qImgCurrent').innerHTML = img ? ('<img src="'+img+'" style="max-height:70px" class="rounded me-2"><label class="small"><input type="checkbox" onchange="document.getElementById(\'qRemoveImage\').value=this.checked?1:0"> remove image</label>') : '';
    f.scrollIntoView({behavior:'smooth', block:'center'});
}
// Build hidden inputs on submit depending on type.
function lmsQPrepare(){
    var t=document.getElementById('qType').value;
    var form=document.getElementById('qForm');
    form.querySelectorAll('.gen').forEach(function(e){ e.remove(); });

    if(t==='single'||t==='multiple'||t==='boolean'){
        var rows=document.querySelectorAll('#optRows .optrow');
        if(rows.length<2){ alert('Add at least two options.'); return false; }
        var correct=0;
        rows.forEach(function(row,i){
            var hi=document.createElement('input'); hi.type='hidden'; hi.name='options['+i+']'; hi.value=row.querySelector('.opttext').value; hi.className='gen'; form.appendChild(hi);
            if(row.querySelector('.optcheck').checked){ var hc=document.createElement('input'); hc.type='hidden'; hc.name='correct[]'; hc.value=i; hc.className='gen'; form.appendChild(hc); correct++; }
        });
        if(correct<1){ alert('Tick at least one correct answer.'); return false; }
    } else if(t==='short_answer'||t==='fill_blank'){
        var arows=document.querySelectorAll('#accRows .accrow');
        var n=0;
        arows.forEach(function(row,i){
            var v=row.querySelector('.acctext').value.trim();
            if(v!==''){ var hi=document.createElement('input'); hi.type='hidden'; hi.name='accepted['+i+']'; hi.value=v; hi.className='gen'; form.appendChild(hi); n++; }
        });
        if(n<1){ alert('Add at least one accepted answer.'); return false; }
    }
    return true;
}
document.addEventListener('DOMContentLoaded', function(){ lmsQType(); });
</script>

{{-- Import from bank --}}
<div class="modal fade" id="bankModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="{{ route('lms.quizzes.import-bank', [$course, $quiz]) }}">@csrf
        <input type="hidden" name="mode" id="bankMode" value="selected">
        <div class="modal-header"><h5 class="modal-title">Import from question bank</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row g-2 mb-2">
                <div class="col-md-4"><select name="subject" id="bkSubject" class="form-select"><option value="">All subjects</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->subject }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="type" id="bkType" class="form-select"><option value="">All types</option>@foreach(\App\Models\LmsQuizQuestion::TYPES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-3"><input name="tag" id="bkTag" class="form-control" placeholder="Tag"></div>
                <div class="col-md-2"><button type="button" class="action-btn btn-open w-100 justify-content-center" onclick="lmsBankSearch()"><i class="ri-search-line"></i></button></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small text-muted" id="bkCount"></span>
                <div class="d-flex align-items-center gap-2">
                    <label class="small mb-0">or add random</label>
                    <input type="number" min="1" max="100" name="count" id="bkRandom" class="form-control form-control-sm" style="width:80px" placeholder="N">
                    <button type="submit" class="action-btn btn-open" onclick="document.getElementById('bankMode').value='random'"><i class="ri-shuffle-line"></i>Add random</button>
                </div>
            </div>
            <div id="bkList" class="border rounded p-2" style="max-height:340px;overflow:auto"><div class="text-muted small">Search to list bank questions.</div></div>
        </div>
        <div class="modal-footer"><button type="submit" class="action-btn btn-primary-cb" onclick="document.getElementById('bankMode').value='selected'"><i class="ri-add-line"></i>Add selected</button></div>
    </form>
</div></div></div>

<script>
function lmsBankSearch(){
    var qs = new URLSearchParams({subject:document.getElementById('bkSubject').value, type:document.getElementById('bkType').value, tag:document.getElementById('bkTag').value});
    var box=document.getElementById('bkList'); box.innerHTML='<div class="text-muted small">Loading…</div>';
    fetch("{{ route('lms.bank.candidates') }}?"+qs.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(res){
        var d=res.data||[]; document.getElementById('bkCount').textContent = d.length ? d.length+' found' : '';
        if(!d.length){ box.innerHTML='<div class="text-muted small">No matching bank questions.</div>'; return; }
        box.innerHTML = d.map(function(q){
            return '<div class="form-check"><input class="form-check-input" type="checkbox" name="ids[]" value="'+q.id+'" id="bk'+q.id+'"><label class="form-check-label small" for="bk'+q.id+'">'+q.question+' <span class="text-muted">('+q.type+' · '+q.points+' pt'+(q.tag?' · '+q.tag:'')+')</span></label></div>';
        }).join('');
    }).catch(function(e){ box.innerHTML='<div class="text-danger small">Could not load ('+e.message+').</div>'; });
}
var _bankModal=document.getElementById('bankModal');
if(_bankModal){ _bankModal.addEventListener('shown.bs.modal', lmsBankSearch); }
</script>
@endsection
