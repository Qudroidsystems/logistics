{{-- resources/views/lms/bank/index.blade.php — shared question bank --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Question Bank" icon="ri-database-2-fill" subtitle="Build reusable questions and import them into any quiz." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Bank" icon="ri-list-check" :count="$total" :flush="true">
                <div class="p-3">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4"><select name="subject" class="form-select"><option value="">All subjects</option>@foreach($subjects as $s)<option value="{{ $s->id }}" @selected(request('subject')==$s->id)>{{ $s->subject }}</option>@endforeach</select></div>
                        <div class="col-md-3"><select name="type" class="form-select"><option value="">All types</option>@foreach(\App\Models\LmsQuizQuestion::TYPES as $k=>$v)<option value="{{ $k }}" @selected(request('type')===$k)>{{ $v }}</option>@endforeach</select></div>
                        <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search"></div>
                        <div class="col-md-2"><button class="action-btn btn-open w-100 justify-content-center"><i class="ri-search-line"></i></button></div>
                    </form>
                </div>
                @forelse($rows as $qn)
                    <div class="border-bottom px-3 py-2">
                        <div class="d-flex justify-content-between">
                            <div><strong class="small">{{ $qn->question }}</strong>
                                <div class="small text-muted">{{ \App\Models\LmsQuizQuestion::TYPES[$qn->type] ?? $qn->type }} · {{ $qn->points }} pt @if($qn->tag)· <span class="badge bg-light text-dark">{{ $qn->tag }}</span>@endif</div>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="action-btn btn-open" title="Edit" onclick="lmsEditBank(this)"
                                    data-id="{{ $qn->id }}" data-subject="{{ $qn->subject_id }}" data-tag="{{ e($qn->tag) }}"
                                    data-question="{{ e($qn->question) }}" data-type="{{ $qn->type }}" data-points="{{ $qn->points }}"
                                    data-options="{{ e(json_encode($qn->options ?? [])) }}" data-correct="{{ e(json_encode($qn->correct ?? [])) }}"
                                    data-accepted="{{ e(json_encode($qn->accepted_answers ?? [])) }}" data-explanation="{{ e($qn->explanation) }}"><i class="ri-edit-line"></i></button>
                                <form method="POST" action="{{ route('lms.bank.destroy', $qn) }}" onsubmit="return confirm('Remove from bank?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state"><i class="ri-database-2-line"></i><h6>Bank is empty</h6><p>Add questions on the right, then import them into quizzes.</p></div>
                @endforelse
                @if($rows->hasPages())<div class="p-3">{{ $rows->links() }}</div>@endif
            </x-cb.card>
        </div>

        <div class="col-lg-5">
            <x-cb.card title="Add / edit question" icon="ri-add-line">
                <form method="POST" action="{{ route('lms.bank.store') }}" id="bForm" enctype="multipart/form-data"
                      data-store="{{ route('lms.bank.store') }}" data-update="{{ route('lms.bank.update', 0) }}"
                      onsubmit="return lmsBankPrepare()">@csrf
                    <input type="hidden" name="_method" id="bMethod" value="POST">
                    <div class="row g-2 mb-2">
                        <div class="col-7"><label class="form-label small">Subject</label>
                            <select name="subject_id" id="bSubject" class="form-select"><option value="">—</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->subject }}</option>@endforeach</select></div>
                        <div class="col-5"><label class="form-label small">Tag</label><input name="tag" id="bTag" class="form-control" placeholder="e.g. Algebra"></div>
                    </div>
                    <label class="form-label small">Question *</label><textarea name="question" id="bQuestion" rows="2" class="form-control mb-2" required></textarea>
                    <div class="row g-2 mb-2">
                        <div class="col-8"><label class="form-label small">Type</label>
                            <select name="type" id="bType" class="form-select" onchange="lmsBType()">@foreach(\App\Models\LmsQuizQuestion::TYPES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                        <div class="col-4"><label class="form-label small">Points</label><input type="number" min="1" name="points" id="bPoints" value="1" class="form-control"></div>
                    </div>
                    <div id="bOptWrap" class="bblock"><label class="form-label small">Options (tick correct)</label><div id="bOptRows"></div>
                        <button type="button" class="action-btn btn-open mt-1" onclick="lmsBOptRow('')"><i class="ri-add-line"></i>Add option</button></div>
                    <div id="bAccWrap" class="bblock" style="display:none"><label class="form-label small">Accepted answers</label><div id="bAccRows"></div>
                        <button type="button" class="action-btn btn-open mt-1" onclick="lmsBAccRow('')"><i class="ri-add-line"></i>Add accepted answer</button></div>
                    <div id="bEssayWrap" class="bblock" style="display:none"><div class="cb-banner info"><i class="ri-information-line"></i><div class="small">Essay questions are graded manually in each quiz.</div></div></div>
                    <label class="form-label small mt-2">Explanation (optional)</label><textarea name="explanation" id="bExplanation" rows="2" class="form-control mb-2"></textarea>
                    <label class="form-label small">Image (optional)</label><input type="file" name="image" accept="image/*" class="form-control mb-2">
                    <div class="d-flex gap-2">
                        <button class="action-btn btn-primary-cb" id="bSubmit"><i class="ri-add-circle-line"></i>Add to bank</button>
                        <button type="button" class="action-btn btn-open d-none" id="bCancel" onclick="lmsBankReset()"><i class="ri-close-line"></i>Cancel</button>
                    </div>
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>

<script>
function lmsEsc(v){ return String(v==null?'':v).replace(/"/g,'&quot;'); }
function lmsBOptRow(val, checked){
    var w=document.getElementById('bOptRows'); var r=document.createElement('div'); r.className='input-group input-group-sm mb-1 boptrow';
    r.innerHTML='<span class="input-group-text"><input type="checkbox" class="boptcheck"'+(checked?' checked':'')+'></span><input type="text" class="form-control bopttext" placeholder="Option" value="'+lmsEsc(val)+'"><button type="button" class="btn btn-outline-danger" onclick="this.closest(\'.boptrow\').remove()"><i class="ri-close-line"></i></button>';
    w.appendChild(r);
}
function lmsBAccRow(val){
    var w=document.getElementById('bAccRows'); var r=document.createElement('div'); r.className='input-group input-group-sm mb-1 baccrow';
    r.innerHTML='<input type="text" class="form-control bacctext" placeholder="Accepted answer" value="'+lmsEsc(val)+'"><button type="button" class="btn btn-outline-danger" onclick="this.closest(\'.baccrow\').remove()"><i class="ri-close-line"></i></button>';
    w.appendChild(r);
}
function lmsBType(){
    var t=document.getElementById('bType').value;
    document.getElementById('bOptWrap').style.display   = (t==='single'||t==='multiple'||t==='boolean')?'':'none';
    document.getElementById('bAccWrap').style.display   = (t==='short_answer'||t==='fill_blank')?'':'none';
    document.getElementById('bEssayWrap').style.display = (t==='essay')?'':'none';
    if(t==='boolean'){ var w=document.getElementById('bOptRows'); w.innerHTML=''; lmsBOptRow('True'); lmsBOptRow('False'); }
    else if((t==='single'||t==='multiple') && !document.querySelectorAll('#bOptRows .boptrow').length){ lmsBOptRow(''); lmsBOptRow(''); }
    else if((t==='short_answer'||t==='fill_blank') && !document.querySelectorAll('#bAccRows .baccrow').length){ lmsBAccRow(''); }
}
function lmsBankReset(){
    var f=document.getElementById('bForm'); f.action=f.getAttribute('data-store'); document.getElementById('bMethod').value='POST';
    document.getElementById('bSubmit').innerHTML='<i class="ri-add-circle-line"></i>Add to bank'; document.getElementById('bCancel').classList.add('d-none');
    f.reset(); document.getElementById('bOptRows').innerHTML=''; document.getElementById('bAccRows').innerHTML=''; lmsBType();
}
function lmsEditBank(btn){
    var g=function(a){return btn.getAttribute(a);}; var f=document.getElementById('bForm');
    f.action=f.getAttribute('data-update').replace(/0$/, g('data-id')); document.getElementById('bMethod').value='PUT';
    document.getElementById('bSubmit').innerHTML='<i class="ri-save-line"></i>Save'; document.getElementById('bCancel').classList.remove('d-none');
    document.getElementById('bSubject').value=g('data-subject')||''; document.getElementById('bTag').value=g('data-tag')||'';
    document.getElementById('bQuestion').value=g('data-question')||''; document.getElementById('bType').value=g('data-type')||'single';
    document.getElementById('bPoints').value=g('data-points')||'1'; document.getElementById('bExplanation').value=g('data-explanation')||'';
    var opts=[],corr=[],acc=[]; try{opts=JSON.parse(g('data-options')||'[]');}catch(e){} try{corr=JSON.parse(g('data-correct')||'[]');}catch(e){} try{acc=JSON.parse(g('data-accepted')||'[]');}catch(e){}
    document.getElementById('bOptRows').innerHTML=''; document.getElementById('bAccRows').innerHTML='';
    opts.forEach(function(o,i){ lmsBOptRow(o, corr.indexOf(i)!==-1); }); acc.forEach(function(a){ lmsBAccRow(a); });
    lmsBType(); window.scrollTo({top:0,behavior:'smooth'});
}
function lmsBankPrepare(){
    var t=document.getElementById('bType').value; var f=document.getElementById('bForm'); f.querySelectorAll('.bgen').forEach(function(e){e.remove();});
    if(t==='single'||t==='multiple'||t==='boolean'){
        var rows=document.querySelectorAll('#bOptRows .boptrow'); if(rows.length<2){ alert('Add at least two options.'); return false; }
        var c=0; rows.forEach(function(row,i){ var hi=document.createElement('input'); hi.type='hidden'; hi.name='options['+i+']'; hi.value=row.querySelector('.bopttext').value; hi.className='bgen'; f.appendChild(hi);
            if(row.querySelector('.boptcheck').checked){ var hc=document.createElement('input'); hc.type='hidden'; hc.name='correct[]'; hc.value=i; hc.className='bgen'; f.appendChild(hc); c++; } });
        if(c<1){ alert('Tick at least one correct answer.'); return false; }
    } else if(t==='short_answer'||t==='fill_blank'){
        var ar=document.querySelectorAll('#bAccRows .baccrow'); var n=0; ar.forEach(function(row,i){ var v=row.querySelector('.bacctext').value.trim(); if(v!==''){ var hi=document.createElement('input'); hi.type='hidden'; hi.name='accepted['+i+']'; hi.value=v; hi.className='bgen'; f.appendChild(hi); n++; } });
        if(n<1){ alert('Add at least one accepted answer.'); return false; }
    }
    return true;
}
document.addEventListener('DOMContentLoaded', lmsBType);
</script>
@endsection
