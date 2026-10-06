{{-- resources/views/curriculum/notes/review.blade.php — HOD review queue --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Lesson Note Review" icon="ri-inbox-fill" subtitle="Approve or return teachers' lesson notes."
        :back="route('curriculum.notes.index')" back-label="My Notes" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <x-cb.card title="Notes" icon="ri-inbox-line" :count="$notes->total()" :flush="true">
        <div class="p-3">
            <form method="GET" class="row g-2"><div class="col-md-3"><select name="status" class="form-select" onchange="this.form.submit()">
                @foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected($status===$k)>{{ $v[0] }}</option>@endforeach
            </select></div></form>
        </div>
        @if($notes->isEmpty())
            <div class="empty-state"><i class="ri-inbox-line"></i><h6>Nothing here</h6></div>
        @else
            @foreach($notes as $n)
                @php [$lbl,$cls] = $n->label(); @endphp
                <div class="border-bottom px-3 py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-semibold">{{ $n->title }}</span> <span class="status-pill {{ $cls }} ms-1">{{ $lbl }}</span>
                            <div class="small text-muted">{{ $teachers[$n->teacher_id] ?? 'Teacher' }} · {{ $labels[$n->subjectclass_id] ?? '' }}@if($n->week_no) · Wk {{ $n->week_no }}@endif</div>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('curriculum.notes.show', $n) }}" target="_blank" class="action-btn btn-open" title="Open"><i class="ri-eye-line"></i></a>
                            @if($n->status==='submitted')
                                <form method="POST" action="{{ route('curriculum.notes.approve', $n) }}" class="d-inline">@csrf<button class="action-btn btn-primary-cb"><i class="ri-check-line"></i>Approve</button></form>
                                <button class="action-btn btn-open" data-bs-toggle="collapse" data-bs-target="#ret{{ $n->id }}"><i class="ri-arrow-go-back-line"></i>Return</button>
                            @endif
                        </div>
                    </div>
                    @if($n->status==='submitted')
                    <div class="collapse mt-2" id="ret{{ $n->id }}">
                        <form method="POST" action="{{ route('curriculum.notes.return', $n) }}" class="d-flex gap-2">@csrf
                            <input name="review_comment" class="form-control form-control-sm" placeholder="What needs changing?" required>
                            <button class="btn btn-sm btn-warning"><i class="ri-send-plane-line"></i> Return</button>
                        </form>
                    </div>
                    @endif
                </div>
            @endforeach
        @endif
    </x-cb.card>
    @if($notes->hasPages())<div class="mt-3">{{ $notes->links() }}</div>@endif
</div></div></div>
@endsection
