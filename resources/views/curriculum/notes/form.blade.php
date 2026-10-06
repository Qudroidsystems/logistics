{{-- resources/views/curriculum/notes/form.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$note->exists ? 'Edit Lesson Note' : 'New Lesson Note'" icon="ri-booklet-fill"
        subtitle="Plan the lesson, link the week's topics, and choose your methods."
        :back="route('curriculum.notes.index')" back-label="My Notes" />

    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    @if($note->exists && $note->status==='returned' && $note->review_comment)
        <div class="cb-banner warning"><i class="ri-chat-1-line"></i><div><strong>Returned by HOD:</strong> {{ $note->review_comment }}</div></div>
    @endif

    @if($assignments->isEmpty())
        <x-cb.card><div class="empty-state"><i class="ri-presentation-line"></i><h6>No classes assigned</h6><p>You have no subject assignments this session.</p></div></x-cb.card>
    @else
    <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

    <form method="POST" action="{{ $note->exists ? route('curriculum.notes.update', $note) : route('curriculum.notes.store') }}">
        @csrf @if($note->exists)@method('PUT')@endif
        <input type="hidden" name="action" id="noteAction" value="save">
        <input type="hidden" name="content" id="noteContent">

        <div class="row g-3">
            <div class="col-lg-8">
                <x-cb.card title="Details" icon="ri-information-line">
                    <div class="row g-2">
                        <div class="col-md-8"><label class="form-label small">Subject &amp; class *</label>
                            <select name="subjectclass_id" id="scSelect" class="form-select" required onchange="renderTopics()">
                                @foreach($assignments as $a)<option value="{{ $a->subjectclass_id }}" @selected($note->subjectclass_id==$a->subjectclass_id)>{{ $a->label }}</option>@endforeach
                            </select></div>
                        <div class="col-md-4"><label class="form-label small">Week</label><input type="number" min="1" name="week_no" value="{{ old('week_no',$note->week_no) }}" class="form-control"></div>
                        <div class="col-12"><label class="form-label small">Title *</label><input name="title" value="{{ old('title',$note->title) }}" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small">Objectives (one per line)</label><textarea name="objectives" rows="3" class="form-control">{{ old('objectives',$note->objectives) }}</textarea></div>
                        <div class="col-12"><label class="form-label small">Lesson content / procedure</label>
                            <div id="contentEditor" class="bg-white"></div></div>
                        <div class="col-12"><label class="form-label small">Materials / teaching aids</label><textarea name="materials" rows="2" class="form-control">{{ old('materials',$note->materials) }}</textarea></div>
                    </div>
                </x-cb.card>
            </div>
            <div class="col-lg-4">
                <x-cb.card title="Topics (this week)" icon="ri-booklet-line">
                    <div id="topicList"><div class="small text-muted">Select a class to see its topics.</div></div>
                </x-cb.card>
                <x-cb.card title="Teaching methods" icon="ri-lightbulb-line">
                    @forelse($methods as $m)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="methods[]" value="{{ $m->id }}" id="m{{ $m->id }}" @checked(in_array($m->id, (array) old('methods', $note->methods ?? [])))><label class="form-check-label small" for="m{{ $m->id }}">{{ $m->name }}</label></div>
                    @empty<div class="small text-muted">No methods configured.</div>@endforelse
                </x-cb.card>
                <div class="d-grid gap-2">
                    <button class="action-btn btn-open justify-content-center" onclick="document.getElementById('noteAction').value='save'"><i class="ri-save-line"></i>Save draft</button>
                    <button class="action-btn btn-primary-cb justify-content-center" onclick="document.getElementById('noteAction').value='submit'"><i class="ri-send-plane-line"></i>Submit for review</button>
                </div>
            </div>
        </div>
    </form>

    <script>
    var TOPICS_BY_CLASS = @json($topicsByClass);
    var SELECTED_TOPICS = @json(array_map('intval', $selectedTopics));
    function renderTopics(){
        var sc = document.getElementById('scSelect').value;
        var list = TOPICS_BY_CLASS[sc] || [];
        var box = document.getElementById('topicList');
        if(!list.length){ box.innerHTML='<div class="small text-muted">No syllabus topics for this class. Ask your HOD to set them.</div>'; return; }
        box.innerHTML = list.map(function(t){
            var checked = SELECTED_TOPICS.indexOf(parseInt(t.id))!==-1 ? ' checked' : '';
            var wk = t.week_no ? ' <span class="text-muted">(Wk '+t.week_no+')</span>' : '';
            return '<div class="form-check"><input class="form-check-input" type="checkbox" name="topics[]" value="'+t.id+'" id="t'+t.id+'"'+checked+'><label class="form-check-label small" for="t'+t.id+'">'+t.title+wk+'</label></div>';
        }).join('');
    }
    var _q = new Quill('#contentEditor', {theme:'snow', modules:{toolbar:[['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],[{header:[2,3,false]}],['clean']]}, placeholder:'Introduction, presentation, evaluation…'});
    _q.root.innerHTML = @json(old('content', $note->content ?? ''));
    _q.on('text-change', function(){ document.getElementById('noteContent').value = _q.root.innerHTML; });
    document.getElementById('noteContent').value = _q.root.innerHTML;
    document.addEventListener('DOMContentLoaded', renderTopics);
    </script>
    @endif
</div></div></div>
@endsection
