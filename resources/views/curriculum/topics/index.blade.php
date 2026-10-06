{{-- resources/views/curriculum/topics/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Curriculum Topics" icon="ri-booklet-fill" subtitle="Build the syllabus per subject, class level and term." :back="route('dashboard')" back-label="Dashboard">
        <x-slot name="actions">
            <a href="{{ route('curriculum.topics.coverage', request()->only('subject','class_level','term')) }}" class="action-btn btn-go"><i class="ri-bar-chart-2-line"></i>Coverage</a>
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <x-cb.card title="Choose syllabus" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">Subject</label>
                <select name="subject" class="form-select" required><option value="">—</option>
                    @foreach($subjects as $s)<option value="{{ $s->id }}" @selected(request('subject')==$s->id)>{{ $s->subject }}</option>@endforeach
                </select></div>
            <div class="col-md-4"><label class="form-label small">Class level</label>
                <select name="class_level" class="form-select" required><option value="">—</option>
                    @foreach($classLevels as $cl)<option value="{{ $cl }}" @selected(request('class_level')===$cl)>{{ $cl }}</option>@endforeach
                </select></div>
            <div class="col-md-2"><label class="form-label small">Term</label>
                <select name="term" class="form-select"><option value="">All</option>
                    @foreach($terms as $t)<option value="{{ $t->id }}" @selected(request('term')==$t->id)>{{ $t->term }}</option>@endforeach
                </select></div>
            <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i>Load</button></div>
        </form>
    </x-cb.card>

    @if(request('subject') && request('class_level'))
        @if($canManage)
        <x-cb.card title="Add topics" icon="ri-add-line">
            <form method="POST" action="{{ route('curriculum.topics.store') }}" class="row g-2 align-items-end">@csrf
                <input type="hidden" name="subject_id" value="{{ request('subject') }}">
                <input type="hidden" name="class_level" value="{{ request('class_level') }}">
                <input type="hidden" name="term_id" value="{{ request('term') }}">
                <div class="col-md-6"><label class="form-label small">Topic title *</label><input name="title" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label small">Week</label><input type="number" min="1" name="week_no" class="form-control"></div>
                <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-add-line"></i>Add</button></div>
                <div class="col-md-2"><button type="button" class="action-btn btn-open w-100 justify-content-center" data-bs-toggle="collapse" data-bs-target="#bulkAdd"><i class="ri-list-check"></i>Bulk</button></div>
            </form>
            <div class="collapse mt-2" id="bulkAdd">
                <form method="POST" action="{{ route('curriculum.topics.bulk') }}">@csrf
                    <input type="hidden" name="subject_id" value="{{ request('subject') }}">
                    <input type="hidden" name="class_level" value="{{ request('class_level') }}">
                    <input type="hidden" name="term_id" value="{{ request('term') }}">
                    <label class="form-label small">One topic per line</label>
                    <textarea name="titles" rows="5" class="form-control mb-2" placeholder="Introduction to…&#10;Chapter 2…&#10;…"></textarea>
                    <button class="action-btn btn-primary-cb"><i class="ri-add-line"></i>Add all</button>
                </form>
            </div>
        </x-cb.card>
        @endif

        <x-cb.card title="Topics" icon="ri-booklet-line" :count="$topics->count()" :flush="true">
            @if($topics->isEmpty())
                <div class="empty-state"><i class="ri-booklet-line"></i><h6>No topics yet</h6><p>Add topics above.</p></div>
            @else
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <tbody id="topicRows">
                    @foreach($topics as $t)
                        <tr data-id="{{ $t->id }}">
                            @if($canManage)<td style="width:28px" class="text-muted text-center"><i class="ri-draggable topic-handle" style="cursor:grab"></i></td>@endif
                            <td style="width:60px" class="text-muted small">{{ $t->week_no ? 'Wk '.$t->week_no : '—' }}</td>
                            <td><span class="fw-semibold">{{ $t->title }}</span>@unless($t->is_active)<span class="status-pill st-muted ms-2">inactive</span>@endunless
                                @if($t->description)<div class="small text-muted">{{ Str::limit($t->description, 120) }}</div>@endif</td>
                            @if($canManage)
                            <td class="text-end" style="white-space:nowrap">
                                <button class="action-btn btn-open" onclick="editTopic(this)"
                                    data-id="{{ $t->id }}" data-title="{{ e($t->title) }}" data-week="{{ $t->week_no }}"
                                    data-desc="{{ e($t->description) }}" data-active="{{ $t->is_active?1:0 }}"><i class="ri-edit-line"></i></button>
                                <form method="POST" action="{{ route('curriculum.topics.destroy', $t) }}" class="d-inline" onsubmit="return confirm('Remove topic?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @endif
        </x-cb.card>
    @endif
</div></div></div>

@if(request('subject') && request('class_level') && $canManage)
{{-- Edit modal --}}
<div class="modal fade" id="topicModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" id="topicForm" data-update="{{ route('curriculum.topics.update', 0) }}">@csrf @method('PUT')
        <input type="hidden" name="subject_id" value="{{ request('subject') }}">
        <input type="hidden" name="class_level" value="{{ request('class_level') }}">
        <input type="hidden" name="term_id" value="{{ request('term') }}">
        <div class="modal-header"><h5 class="modal-title">Edit topic</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <label class="form-label small">Title *</label><input name="title" id="etTitle" class="form-control mb-2" required>
            <label class="form-label small">Week</label><input type="number" min="1" name="week_no" id="etWeek" class="form-control mb-2">
            <label class="form-label small">Description</label><textarea name="description" id="etDesc" rows="3" class="form-control mb-2"></textarea>
            <div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="etActive"><label class="form-check-label small" for="etActive">Active</label></div>
        </div>
        <div class="modal-footer"><button class="action-btn btn-primary-cb">Save</button></div>
    </form>
</div></div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.6/Sortable.min.js"></script>
<script>
var CRS_CSRF='{{ csrf_token() }}';
function editTopic(btn){
    var g=function(a){return btn.getAttribute(a)||'';};
    var f=document.getElementById('topicForm');
    f.action=f.getAttribute('data-update').replace(/0$/, g('data-id'));
    document.getElementById('etTitle').value=g('data-title');
    document.getElementById('etWeek').value=g('data-week');
    document.getElementById('etDesc').value=g('data-desc');
    document.getElementById('etActive').checked=g('data-active')==='1';
    new bootstrap.Modal(document.getElementById('topicModal')).show();
}
(function(){
    var el=document.getElementById('topicRows');
    if(el && window.Sortable){
        new Sortable(el,{handle:'.topic-handle',animation:150,draggable:'tr[data-id]',onEnd:function(){
            var order=Array.from(el.querySelectorAll('tr[data-id]')).map(function(r){return r.getAttribute('data-id');});
            fetch('{{ route('curriculum.topics.reorder') }}',{method:'POST',headers:{'X-CSRF-TOKEN':CRS_CSRF,'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',body:JSON.stringify({order:order})});
        }});
    }
})();
</script>
@endif
@endsection
