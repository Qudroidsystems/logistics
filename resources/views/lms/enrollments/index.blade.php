{{-- resources/views/lms/enrollments/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Learners'" icon="ri-group-fill" :subtitle="$course->title"
        :back="route('lms.courses.show', $course)" back-label="Course">
        <x-slot name="actions">
            <form method="POST" action="{{ route('lms.enrollments.sync', $course) }}" class="d-inline" onsubmit="return confirm('Enrol all {{ $eligible }} students of this class?')">@csrf
                <button class="action-btn btn-go"><i class="ri-refresh-line"></i>Auto-enrol class ({{ $eligible }})</button>
            </form>
            @if($course->subject_id)
            <form method="POST" action="{{ route('lms.enrollments.sync-subject', $course) }}" class="d-inline" onsubmit="return confirm('Enrol everyone registered for this subject?')">@csrf
                <button class="action-btn btn-go"><i class="ri-book-mark-line"></i>Enrol by subject</button>
            </form>
            @endif
            <button class="action-btn btn-primary-cb" data-bs-toggle="modal" data-bs-target="#addModal"><i class="ri-user-add-line"></i>Add students</button>
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    @unless($course->schoolclass_id)
        <div class="cb-banner warning"><i class="ri-information-line"></i><div>This course has no class assigned, so “Auto-enrol class” won’t find anyone. Use <strong>Add students</strong> (choose <em>All classes</em> to search everyone), or set a class in the course settings.</div></div>
    @endunless

    <x-cb.card title="Enrolled" icon="ri-group-line" :count="$rows->total()" :flush="true">
        <div class="p-3">
            <form method="GET" class="row g-2"><div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name / admission no"></div><div class="col-md-2"><button class="action-btn btn-open w-100 justify-content-center"><i class="ri-search-line"></i></button></div></form>
        </div>
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-group-line"></i><h6>No learners</h6><p>Auto-enrol the class or add students manually.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th>Admission</th><th>Source</th><th style="width:180px">Progress</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ trim(($r->firstname ?? '').' '.($r->lastname ?? '')) }}</td>
                        <td class="small">{{ $r->admissionNo ?? '—' }}</td>
                        <td><span class="status-pill {{ $r->source==='auto' ? 'st-info' : 'st-muted' }}">{{ ucfirst($r->source) }}</span></td>
                        <td>
                            <div class="progress" style="height:8px"><div class="progress-bar" style="width: {{ (int)$r->progress_percent }}%"></div></div>
                            <div class="small text-muted">{{ (int)$r->progress_percent }}%</div>
                        </td>
                        <td><span class="status-pill {{ $r->status==='completed' ? 'st-paid' : ($r->status==='dropped' ? 'st-danger' : 'st-pending') }}">{{ ucfirst($r->status) }}</span></td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('lms.enrollments.destroy', [$course, $r->student_id]) }}" onsubmit="return confirm('Remove this learner and their progress?')">@csrf @method('DELETE')<button class="action-btn btn-open" title="Remove"><i class="ri-user-unfollow-line"></i></button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div></div></div>

{{-- Add students modal --}}
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="{{ route('lms.enrollments.store', $course) }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Add students</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row g-2 mb-2">
                <div class="col-md-5">
                    <select id="candClass" class="form-select" onchange="lmsLoadCandidates()">
                        @if($course->schoolclass_id)<option value="">Course class</option>@endif
                        <option value="all" @if(!$course->schoolclass_id) selected @endif>All classes</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-5"><input id="candSearch" class="form-control" placeholder="Search name / admission no"></div>
                <div class="col-md-2"><button type="button" class="action-btn btn-open w-100 justify-content-center" onclick="lmsLoadCandidates()"><i class="ri-search-line"></i></button></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small text-muted" id="candCount"></span>
                <button type="button" class="btn btn-sm btn-link p-0" onclick="lmsToggleAll(this)">Select all</button>
            </div>
            <div id="candList" class="border rounded p-2" style="max-height:340px;overflow:auto">
                <div class="text-muted small">Loading…</div>
            </div>
        </div>
        <div class="modal-footer"><button class="action-btn btn-primary-cb"><i class="ri-user-add-line"></i>Enrol selected</button></div>
    </form>
</div></div></div>

<script>
function lmsLoadCandidates(){
    var q = document.getElementById('candSearch').value;
    var cls = document.getElementById('candClass').value;
    var box = document.getElementById('candList');
    box.innerHTML = '<div class="text-muted small">Loading…</div>';
    var url = "{{ route('lms.enrollments.candidates', $course) }}" + "?q=" + encodeURIComponent(q) + "&class_id=" + encodeURIComponent(cls);
    fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ if(!r.ok) throw new Error(r.status); return r.json(); })
      .then(function(res){
        var data = res.data || [];
        document.getElementById('candCount').textContent = data.length ? (data.length + ' student(s) found' + (data.length>=500 ? ' (showing first 500 — narrow your search)' : '')) : '';
        if(!data.length){ box.innerHTML='<div class="text-muted small">No matching students. Try “All classes” or a different search.</div>'; return; }
        box.innerHTML = data.map(function(s){
            return '<div class="form-check"><input class="form-check-input candbox" type="checkbox" name="student_ids[]" value="'+s.id+'" id="cand'+s.id+'"><label class="form-check-label" for="cand'+s.id+'">'+s.name+' <span class="text-muted small">'+(s.admissionNo||'')+'</span></label></div>';
        }).join('');
    })
      .catch(function(err){ box.innerHTML='<div class="text-danger small">Could not load students ('+err.message+'). Please refresh and try again.</div>'; });
}
function lmsToggleAll(btn){
    var boxes = document.querySelectorAll('#candList .candbox');
    var check = btn.textContent.indexOf('Select all') !== -1;
    boxes.forEach(function(b){ b.checked = check; });
    btn.textContent = check ? 'Clear all' : 'Select all';
}
document.getElementById('candSearch').addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); lmsLoadCandidates(); }});
var _addModalEl = document.getElementById('addModal');
if(_addModalEl){ _addModalEl.addEventListener('shown.bs.modal', function(){ lmsLoadCandidates(); }); }
</script>
@endsection
