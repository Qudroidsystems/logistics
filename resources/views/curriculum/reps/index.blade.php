{{-- resources/views/curriculum/reps/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Class Reps" icon="ri-user-star-fill" subtitle="Assign up to two reps per arm to confirm taught topics."
        :back="route('curriculum.topics.index')" back-label="Curriculum" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <x-cb.card title="Choose arm" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label small">Class / arm</label>
                <select name="class" class="form-select" onchange="this.form.submit()"><option value="">—</option>
                    @foreach($arms as $a)<option value="{{ $a->id }}" @selected(request('class')==$a->id)>{{ $a->name }}</option>@endforeach
                </select></div>
            <div class="col-md-4"><label class="form-label small">Session</label>
                <select name="session" class="form-select" onchange="this.form.submit()">
                    @foreach($sessions as $se)<option value="{{ $se->id }}" @selected((int)$sessionId===(int)$se->id)>{{ $se->session }}@if($se->status==='Current') (Current)@endif</option>@endforeach
                </select></div>
            <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i>View</button></div>
        </form>
    </x-cb.card>

    @if($arm)
        <x-cb.card title="Current reps" icon="ri-user-star-line" :count="$reps->count()">
            @forelse($reps as $r)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div><span class="fw-semibold">{{ $r->name }}</span> <span class="small text-muted">{{ $r->admissionNo }}</span></div>
                    <form method="POST" action="{{ route('curriculum.reps.destroy', $r->id) }}" onsubmit="return confirm('Remove this rep?')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                </div>
            @empty<div class="empty-state"><i class="ri-user-star-line"></i><p>No reps assigned for this arm.</p></div>@endforelse

            @if($reps->count() < 2)
            <form method="POST" action="{{ route('curriculum.reps.store') }}" class="row g-2 align-items-end mt-2">@csrf
                <input type="hidden" name="schoolclass_id" value="{{ $arm }}">
                <input type="hidden" name="session_id" value="{{ $sessionId }}">
                <div class="col-md-8"><label class="form-label small">Add rep @if($students->isEmpty())<span class="text-danger">(no students found for this arm)</span>@endif</label>
                    <select name="student_id" class="form-select" required @disabled($students->isEmpty())><option value="">Select a student…</option>
                        @foreach($students as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->admissionNo }})</option>@endforeach
                    </select></div>
                <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center" @disabled($students->isEmpty())><i class="ri-user-add-line"></i>Add</button></div>
            </form>
            @else<div class="cb-banner info mt-2"><i class="ri-information-line"></i><div class="small">This arm already has two reps (the maximum).</div></div>@endif
        </x-cb.card>
    @endif
</div></div></div>
@endsection
