{{-- resources/views/curriculum/progress/board.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$sc->subject_name" icon="ri-list-check-2"
        :subtitle="$sc->arm_name" :back="route('curriculum.progress.index')" back-label="My Topics" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3 mb-1">
        <div class="col-6 col-lg-3"><x-cb.stat label="Topics" :value="$total" icon="ri-booklet-line" accent="info" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Taught" :value="$taught" icon="ri-check-double-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Progress" :value="$pct.'%'" icon="ri-progress-4-line" accent="violet" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Overdue" :value="$overdue" icon="ri-alarm-warning-line" accent="rose" /></div>
    </div>

    <div class="cb-banner {{ $pace[1]==='st-paid' ? 'info' : 'warning' }}">
        <i class="ri-speed-up-line"></i><div>Pace: <strong>{{ $pace[0] }}</strong>@if($overdue) · {{ $overdue }} planned topic(s) overdue @endif</div>
    </div>

    <x-cb.card title="Topics" icon="ri-booklet-line" :count="$total" :flush="true">
        @if($topics->isEmpty())
            <div class="empty-state"><i class="ri-booklet-line"></i><h6>No syllabus yet</h6><p>Ask your HOD to set the topics for this subject and class level.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Topic</th><th>Status</th><th>Taught on</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @foreach($topics as $t)
                    @php $p = $progress->get($t->id); [$lbl,$cls] = $p ? $p->label() : ['Not taught','st-muted']; @endphp
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $t->title }}</span>
                            @if($t->week_no)<span class="text-muted small ms-1">(Wk {{ $t->week_no }})</span>@endif
                            @if($p && $p->student_confirmed)<span class="status-pill st-paid ms-1" title="Confirmed by class rep">rep ✓</span>@endif
                            @if($p && $p->disputed)<span class="status-pill st-danger ms-1">disputed</span>@endif
                            @if($p && $p->hod_comment)<div class="small text-muted"><i class="ri-chat-1-line"></i> HOD: {{ $p->hod_comment }}</div>@endif
                            @if($p && $p->note)<div class="small text-muted">{{ $p->note }}</div>@endif
                        </td>
                        <td><span class="status-pill {{ $cls }}">{{ $lbl }}</span></td>
                        <td class="small">{{ $p && $p->taught_on ? $p->taught_on->format('d M Y') : '—' }}</td>
                        <td class="text-end" style="white-space:nowrap">
                            @if($isOwner || auth()->user()->can('Manage topics'))
                                <button class="action-btn btn-open" data-bs-toggle="collapse" data-bs-target="#mk{{ $t->id }}"><i class="ri-edit-line"></i></button>
                            @endif
                            @if($canVerify && $p && $p->isTaught())
                                <form method="POST" action="{{ route('curriculum.progress.verify', [$sc->id, $t->id]) }}" class="d-inline">@csrf
                                    <input type="hidden" name="verified" value="{{ $p->status==='confirmed' ? 0 : 1 }}">
                                    <button class="action-btn {{ $p->status==='confirmed' ? 'btn-open' : 'btn-primary-cb' }}" title="{{ $p->status==='confirmed' ? 'Un-verify' : 'Verify' }}"><i class="ri-shield-check-line"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @if($isOwner || auth()->user()->can('Manage topics') || $canVerify)
                    <tr class="collapse" id="mk{{ $t->id }}"><td colspan="4" class="bg-light">
                        <div class="row g-2">
                            @if($isOwner || auth()->user()->can('Manage topics'))
                            <div class="col-lg-8">
                                <form method="POST" action="{{ route('curriculum.progress.mark', [$sc->id, $t->id]) }}" class="row g-2 align-items-end">@csrf
                                    <div class="col-md-3"><label class="form-label small">Status</label>
                                        <select name="status" class="form-select form-select-sm"><option value="taught" @selected($p && $p->isTaught())>Taught</option><option value="pending" @selected(!$p || !$p->isTaught())>Not taught</option></select></div>
                                    <div class="col-md-3"><label class="form-label small">Taught on</label><input type="date" name="taught_on" value="{{ $p && $p->taught_on ? $p->taught_on->format('Y-m-d') : '' }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-3"><label class="form-label small">Planned date</label><input type="date" name="planned_date" value="{{ $p && $p->planned_date ? $p->planned_date->format('Y-m-d') : '' }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-3"><label class="form-label small">Planned week</label><input type="number" min="1" name="planned_week" value="{{ $p->planned_week ?? '' }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-10"><input name="note" value="{{ $p->note ?? '' }}" class="form-control form-control-sm" placeholder="Note (optional)"></div>
                                    <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-save-line"></i></button></div>
                                </form>
                            </div>
                            @endif
                            @if($canVerify)
                            <div class="col-lg-4">
                                <form method="POST" action="{{ route('curriculum.progress.verify', [$sc->id, $t->id]) }}">@csrf
                                    <label class="form-label small">HOD comment</label>
                                    <div class="input-group input-group-sm"><input name="hod_comment" value="{{ $p->hod_comment ?? '' }}" class="form-control" placeholder="Comment"><button class="btn btn-outline-primary"><i class="ri-send-plane-line"></i></button></div>
                                </form>
                            </div>
                            @endif
                        </div>
                    </td></tr>
                    @endif
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
</div></div></div>
@endsection
