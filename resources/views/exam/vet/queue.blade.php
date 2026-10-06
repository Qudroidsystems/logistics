{{-- resources/views/exam/vet/queue.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Exam Vetting" icon="ri-shield-check-fill" subtitle="Review, comment on and approve exam papers before printing." :back="route('dashboard')" back-label="Dashboard" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <x-cb.card title="Papers" icon="ri-inbox-line" :count="$papers->total()" :flush="true">
        <div class="p-3">
            <form method="GET"><select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Awaiting vetting</option>
                @foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected($status===$k)>{{ $v[0] }}</option>@endforeach
            </select></form>
        </div>
        @if($papers->isEmpty())
            <div class="empty-state"><i class="ri-inbox-line"></i><h6>Nothing to vet</h6></div>
        @else
            @foreach($papers as $p)
                @php [$lbl,$cls] = $p->label(); @endphp
                <div class="border-bottom px-3 py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold">{{ $p->title }}</span> <span class="status-pill {{ $cls }} ms-1">{{ $lbl }}</span>
                        <div class="small text-muted">{{ $teachers[$p->teacher_id] ?? 'Teacher' }} · {{ $labels[$p->subjectclass_id] ?? '' }} · {{ $p->questions()->count() }} Qs · {{ rtrim(rtrim(number_format($p->total_marks,2),'0'),'.') }} mk</div>
                    </div>
                    <a href="{{ route('exam.vet.review', $p) }}" class="action-btn btn-primary-cb"><i class="ri-shield-check-line"></i>Review</a>
                </div>
            @endforeach
        @endif
    </x-cb.card>
    @if($papers->hasPages())<div class="mt-3">{{ $papers->links() }}</div>@endif
</div></div></div>
@endsection
