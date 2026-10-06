{{-- resources/views/exam/papers/form.blade.php — exam paper + question builder --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$paper->exists ? 'Edit Exam Paper' : 'New Exam Paper'" icon="ri-file-edit-fill"
        subtitle="Add questions, tag each to the syllabus topics it tests, then submit for vetting."
        :back="route('exam.papers.index')" back-label="Exam Papers" />

    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    @if($paper->exists && $paper->status==='changes_requested')
        <div class="cb-banner warning"><i class="ri-chat-1-line"></i><div><strong>Changes requested by the vetter.</strong> See the comments on each flagged question, then resubmit.</div></div>
    @endif

    @if($assignments->isEmpty())
        <x-cb.card><div class="empty-state"><i class="ri-presentation-line"></i><h6>No classes assigned</h6><p>You have no subject assignments this session.</p></div></x-cb.card>
    @else
    <form method="POST" id="paperForm" action="{{ $paper->exists ? route('exam.papers.update', $paper) : route('exam.papers.store') }}">
        @csrf @if($paper->exists)@method('PUT')@endif
        <input type="hidden" name="action" id="paperAction" value="save">
        <input type="hidden" name="questions" id="questionsField">

        <div class="row g-3">
            <div class="col-lg-4">
                <x-cb.card title="Paper details" icon="ri-information-line">
                    <label class="form-label small">Subject &amp; class *</label>
                    <select name="subjectclass_id" id="scSelect" class="form-select mb-2" required onchange="onClassChange()">
                        @foreach($assignments as $a)<option value="{{ $a->subjectclass_id }}" @selected($paper->subjectclass_id==$a->subjectclass_id)>{{ $a->label }}</option>@endforeach
                    </select>
                    <label class="form-label small">Title *</label>
                    <input name="title" value="{{ old('title',$paper->title) }}" class="form-control mb-2" placeholder="e.g. First Term Examination" required>
                    <div class="row g-2">
                        <div class="col-7"><label class="form-label small">Type *</label>
                            <select name="exam_type" class="form-select">
                                @foreach(\App\Models\ExamPaper::TYPES as $k=>$v)<option value="{{ $k }}" @selected(old('exam_type',$paper->exam_type)==$k)>{{ $v }}</option>@endforeach
                            </select></div>
                        <div class="col-5"><label class="form-label small">Duration (min)</label>
                            <input type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes',$paper->duration_minutes) }}" class="form-control"></div>
                    </div>
                    <label class="form-label small mt-2">Instructions to candidates</label>
                    <textarea name="instructions" rows="3" class="form-control">{{ old('instructions',$paper->instructions) }}</textarea>
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Total marks</span>
                        <span class="fw-bold" id="totalMarks">0</span>
                    </div>
                </x-cb.card>

                <div class="d-grid gap-2 mt-3">
                    <button type="button" class="action-btn btn-primary-cb justify-content-center" onclick="addQuestion()"><i class="ri-add-line"></i>Add question</button>
                    <button type="button" class="action-btn btn-open justify-content-center" onclick="openBank()"><i class="ri-archive-line"></i>Add from question bank</button>
                    <button type="submit" class="action-btn btn-open justify-content-center" onclick="document.getElementById('paperAction').value='save'"><i class="ri-save-line"></i>Save draft</button>
                    <button type="submit" class="action-btn btn-primary-cb justify-content-center" onclick="document.getElementById('paperAction').value='submit'"><i class="ri-send-plane-line"></i>Submit for vetting</button>
                </div>
            </div>

            <div class="col-lg-8">
                <x-cb.card title="Questions" icon="ri-list-ordered">
                    <div id="qList"></div>
                    <div id="qEmpty" class="empty-state" style="display:none"><i class="ri-list-ordered"></i><p>No questions yet. Use <strong>Add question</strong> or pull from the bank.</p></div>
                </x-cb.card>
            </div>
        </div>
    </form>

    {{-- Bank picker modal --}}
    <div class="modal fade" id="bankModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Question bank</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="d-flex gap-2 mb-2">
                <select id="bankDifficulty" class="form-select form-select-sm" style="width:auto" onchange="loadBank()">
                    <option value="">Any difficulty</option><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option>
                </select>
                <input id="bankSearch" class="form-control form-control-sm" placeholder="Search questions…" oninput="loadBank()">
            </div>
            <div id="bankList"><div class="small text-muted">Loading…</div></div>
        </div>
    </div></div></div>

    <script>
    var TOPICS_BY_CLASS = @json($topicsByClass);
    var EXISTING = @json($questions);
    var BANK_URL = "{{ route('exam.bank.fetch') }}";
    var CSRF = "{{ csrf_token() }}";
    var questions = EXISTING.map(normalise);

    function normalise(q){
        return {
            id: q.id||null, section:q.section||'', number:q.number||'', type:q.type||'theory',
            question:q.question||'', options:q.options&&!Array.isArray(q.options)?q.options:{},
            answer:q.answer||'', marks:parseFloat(q.marks)||1, difficulty:q.difficulty||'',
            topics:(q.topics||[]).map(function(x){return parseInt(x);})
        };
    }
    function currentTopics(){ return TOPICS_BY_CLASS[document.getElementById('scSelect').value] || []; }
    function onClassChange(){ render(); }
    function addQuestion(){ questions.push(normalise({})); render(); window.scrollTo(0,document.body.scrollHeight); }
    function removeQuestion(i){ questions.splice(i,1); render(); }
    function moveUp(i){ if(i>0){ var t=questions[i]; questions[i]=questions[i-1]; questions[i-1]=t; render(); } }

    function render(){
        var box=document.getElementById('qList'); var topics=currentTopics();
        document.getElementById('qEmpty').style.display = questions.length? 'none':'block';
        var total=0;
        box.innerHTML = questions.map(function(q,i){
            total += (parseFloat(q.marks)||0);
            var topicChecks = topics.length ? topics.map(function(t){
                var on = q.topics.indexOf(t.id)!==-1 ? 'checked':'';
                var wk = t.week_no? ' (Wk '+t.week_no+')':'';
                return '<label class="form-check form-check-inline small"><input type="checkbox" class="form-check-input" '+on+' onchange="toggleTopic('+i+','+t.id+',this.checked)"> '+esc(t.title)+wk+'</label>';
            }).join('') : '<span class="small text-muted">No syllabus topics for this class. Ask your HOD to set them — questions can still be saved, but coverage will be blank.</span>';
            var opts = q.type==='objective' ? objectiveBlock(q,i) : '';
            return '<div class="border rounded p-2 mb-2">'
              + '<div class="d-flex gap-2 align-items-center mb-2">'
              + '<span class="badge bg-secondary">Q'+(i+1)+'</span>'
              + '<input class="form-control form-control-sm" style="width:70px" placeholder="No." value="'+esc(q.number)+'" oninput="setF('+i+',\'number\',this.value)">'
              + '<input class="form-control form-control-sm" style="width:90px" placeholder="Section" value="'+esc(q.section)+'" oninput="setF('+i+',\'section\',this.value)">'
              + '<select class="form-select form-select-sm" style="width:auto" onchange="setType('+i+',this.value)">'
              + opt('theory','Theory',q.type)+opt('objective','Objective',q.type)+opt('practical','Practical',q.type)+'</select>'
              + '<select class="form-select form-select-sm" style="width:auto" onchange="setF('+i+',\'difficulty\',this.value)">'
              + opt('','Difficulty',q.difficulty)+opt('easy','Easy',q.difficulty)+opt('medium','Medium',q.difficulty)+opt('hard','Hard',q.difficulty)+'</select>'
              + '<div class="input-group input-group-sm" style="width:110px"><span class="input-group-text">Marks</span><input type="number" min="0" step="0.5" class="form-control" value="'+q.marks+'" oninput="setF('+i+',\'marks\',this.value)"></div>'
              + '<button type="button" class="action-btn btn-open ms-auto" onclick="moveUp('+i+')" title="Move up"><i class="ri-arrow-up-line"></i></button>'
              + '<button type="button" class="action-btn btn-open" onclick="removeQuestion('+i+')" title="Remove"><i class="ri-delete-bin-line"></i></button>'
              + '</div>'
              + '<textarea class="form-control form-control-sm mb-2" rows="2" placeholder="Question text" oninput="setF('+i+',\'question\',this.value)">'+esc(q.question)+'</textarea>'
              + opts
              + '<input class="form-control form-control-sm mb-2" placeholder="Correct answer / marking guide (optional)" value="'+esc(q.answer)+'" oninput="setF('+i+',\'answer\',this.value)">'
              + '<div class="small text-muted mb-1"><i class="ri-price-tag-3-line"></i> Topics tested:</div>'
              + '<div>'+topicChecks+'</div>'
              + '</div>';
        }).join('');
        document.getElementById('totalMarks').textContent = (Math.round(total*100)/100);
    }
    function objectiveBlock(q,i){
        var letters=['A','B','C','D','E'];
        return '<div class="row g-1 mb-2">'+letters.map(function(L){
            var v=(q.options&&q.options[L])?q.options[L]:'';
            return '<div class="col-md-6"><div class="input-group input-group-sm"><span class="input-group-text">'+L+'</span><input class="form-control" value="'+esc(v)+'" oninput="setOpt('+i+',\''+L+'\',this.value)"></div></div>';
        }).join('')+'</div>';
    }
    function opt(v,l,cur){ return '<option value="'+v+'"'+(cur==v?' selected':'')+'>'+l+'</option>'; }
    function setF(i,k,v){ questions[i][k]= (k==='marks')?(parseFloat(v)||0):v; if(k==='marks') render(); }
    function setType(i,v){ questions[i].type=v; render(); }
    function setOpt(i,L,v){ if(!questions[i].options) questions[i].options={}; questions[i].options[L]=v; }
    function toggleTopic(i,id,on){ var a=questions[i].topics; var p=a.indexOf(id); if(on&&p===-1)a.push(id); if(!on&&p!==-1)a.splice(p,1); }
    function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    // ── bank ──
    var _bankModal;
    function openBank(){ _bankModal = _bankModal || new bootstrap.Modal(document.getElementById('bankModal')); _bankModal.show(); loadBank(); }
    function loadBank(){
        var sc=document.getElementById('scSelect'); var opt=sc.options[sc.selectedIndex];
        var url=BANK_URL+'?subjectclass_id='+sc.value
          +'&difficulty='+encodeURIComponent(document.getElementById('bankDifficulty').value)
          +'&q='+encodeURIComponent(document.getElementById('bankSearch').value);
        fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){return r.json();}).then(function(d){
            var list=document.getElementById('bankList');
            if(!d.items||!d.items.length){ list.innerHTML='<div class="small text-muted">No matching bank questions for this subject/class.</div>'; return; }
            list.innerHTML=d.items.map(function(b){
                return '<div class="border rounded p-2 mb-1 d-flex justify-content-between align-items-start">'
                 +'<div><div class="small">'+esc(b.question)+'</div>'
                 +'<div class="small text-muted">'+(b.type||'')+' · '+(b.marks)+' mk'+(b.difficulty?' · '+b.difficulty:'')+'</div></div>'
                 +'<button type="button" class="action-btn btn-primary-cb" onclick=\'addFromBank('+JSON.stringify(b).replace(/'/g,"&#39;")+')\'><i class="ri-add-line"></i></button>'
                 +'</div>';
            }).join('');
        }).catch(function(){ document.getElementById('bankList').innerHTML='<div class="small text-danger">Could not load the bank.</div>'; });
    }
    function addFromBank(b){
        questions.push(normalise({ type:b.type, question:b.question, options:b.options, answer:b.answer, marks:b.marks, difficulty:b.difficulty, topics:b.topics }));
        render();
    }

    document.getElementById('paperForm').addEventListener('submit',function(){
        document.getElementById('questionsField').value = JSON.stringify(questions);
    });
    document.addEventListener('DOMContentLoaded', render);
    </script>
    @endif
</div></div></div>
@endsection
