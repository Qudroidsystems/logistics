{{-- resources/views/curriculum/notes/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="My Lesson Notes" icon="ri-booklet-fill" subtitle="Write, submit and deliver your lesson notes." :back="route('dashboard')" back-label="Dashboard">
        <x-slot name="actions">
            @can('Write lesson notes')<a href="{{ route('curriculum.notes.create') }}" class="action-btn btn-primary-cb"><i class="ri-add-line"></i>New note</a>@endcan
            @can('Review lesson notes')<a href="{{ route('curriculum.notes.review') }}" class="action-btn btn-go"><i class="ri-inbox-line"></i>Review queue</a>@endcan
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <x-cb.card title="Notes" icon="ri-booklet-line" :count="$notes->total()" :flush="true">
        <div class="p-3">
            <form method="GET" class="row g-2"><div class="col-md-3"><select name="status" class="form-select" onchange="this.form.submit()"><option value="">All statuses</option>@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v[0] }}</option>@endforeach</select></div></form>
        </div>
        @if($notes->isEmpty())
            <div class="empty-state"><i class="ri-booklet-line"></i><h6>No lesson notes yet</h6><p>Create your first note.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Title</th><th>Class</th><th>Week</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @foreach($notes as $n)
                    @php [$lbl,$cls] = $n->label(); @endphp
                    <tr>
                        <td class="fw-semibold">{{ $n->title }}
                            @if($n->status==='returned' && $n->review_comment)<div class="small text-danger"><i class="ri-chat-1-line"></i> {{ $n->review_comment }}</div>@endif
                        </td>
                        <td class="small">{{ $labels[$n->subjectclass_id] ?? '—' }}</td>
                        <td class="small">{{ $n->week_no ? 'Wk '.$n->week_no : '—' }}</td>
                        <td><span class="status-pill {{ $cls }}">{{ $lbl }}</span></td>
                        <td class="text-end" style="white-space:nowrap">
                            <a href="{{ route('curriculum.notes.show', $n) }}" class="action-btn btn-open" title="View / print" target="_blank"><i class="ri-printer-line"></i></a>
                            @if($n->isEditable())<a href="{{ route('curriculum.notes.edit', $n) }}" class="action-btn btn-open" title="Edit"><i class="ri-edit-line"></i></a>@endif
                            @if($n->isEditable())<form method="POST" action="{{ route('curriculum.notes.submit', $n) }}" class="d-inline">@csrf<button class="action-btn btn-open" title="Submit"><i class="ri-send-plane-line"></i></button></form>@endif
                            @if($n->status==='approved')<form method="POST" action="{{ route('curriculum.notes.deliver', $n) }}" class="d-inline">@csrf<button class="action-btn btn-primary-cb" title="Mark delivered"><i class="ri-check-double-line"></i></button></form>@endif
                            <form method="POST" action="{{ route('curriculum.notes.copy', $n) }}" class="d-inline">@csrf<button class="action-btn btn-open" title="Copy to new draft"><i class="ri-file-copy-line"></i></button></form>
                            @if($n->isEditable())<form method="POST" action="{{ route('curriculum.notes.destroy', $n) }}" class="d-inline" onsubmit="return confirm('Delete this note?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($notes->hasPages())<div class="mt-3">{{ $notes->links() }}</div>@endif
</div></div></div>
@endsection
