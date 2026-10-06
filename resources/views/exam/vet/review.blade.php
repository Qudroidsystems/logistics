{{-- resources/views/exam/vet/review.blade.php — HOD per-question vetting --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Vetting — '.$paper->title" icon="ri-shield-check-fill"
        :subtitle="$label.' · '.$teacher.' · '.$paper->typeLabel()"
        :back="route('exam.vet.queue')" back-label="Vetting queue">
        <x-slot:actions>
            <a href="{{ route('exam.papers.print', $paper) }}" target="_blank" class="action-btn btn-open"><i class="ri-printer-line"></i>Preview print</a>
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    {{-- Coverage snapshot --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><x-cb.stat :value="$coverage['examined_pct'].'%'" label="Syllabus examined" icon="ri-focus-3-line" /></div>
        <div class="col-6 col-md-3"><x-cb.stat :value="$coverage['alignment_pct'].'%'" label="Marks on taught topics" icon="ri-links-line" /></div>
        <div class="col-6 col-md-3"><x-cb.stat :value="$coverage['untagged_count']" label="Untagged questions" icon="ri-price-tag-3-line" /></div>
        <div class="col-6 col-md-3"><x-cb.stat :value="rtrim(rtrim(number_format($coverage['untaught_marks'],2),'0'),'.')" label="Marks on untaught topics" icon="ri-error-warning-line" /></div>
    </div>
    @if($coverage['untaught_marks'] > 0)
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div><strong>{{ rtrim(rtrim(number_format($coverage['untaught_marks'],2),'0'),'.') }} mark(s)</strong> test topics not yet marked taught for this class. Confirm this is intended before approving.</div></div>
    @endif
    @if(count($coverage['taught_not_examined']))
        <div class="cb-banner info"><i class="ri-information-line"></i><div><strong>Taught but not examined:</strong> {{ implode(', ', $coverage['taught_not_examined']) }}</div></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Questions" icon="ri-list-ordered" :count="$paper->questions->count()">
                @if($paper->instructions)<p class="text-muted fst-italic">{{ $paper->instructions }}</p>@endif
                @foreach($paper->questions as $i => $q)
                    <div class="border rounded p-2 mb-3">
                        <div class="d-flex justify-content-between">
                            <div class="fw-semibold">{{ $q->number ?: ($i+1) }}. {{ $q->question }}</div>
                            <div class="small text-muted ms-2" style="white-space:nowrap">{{ rtrim(rtrim(number_format($q->marks,2),'0'),'.') }} mk</div>
                        </div>
                        @if($q->type==='objective' && $q->options)
                            <div class="row g-1 mt-1 ms-1">@foreach($q->options as $L=>$opt)<div class="col-md-6 small">({{ $L }}) {{ $opt }}</div>@endforeach</div>
                        @endif
                        <div class="mt-1 mb-2">
                            @foreach($q->topics as $t)<span class="status-pill st-info me-1">{{ $t->title }}</span>@endforeach
                            @if($q->topics->isEmpty())<span class="status-pill st-pending">Untagged — no topic</span>@endif
                            @if($q->difficulty)<span class="badge bg-light text-dark ms-1">{{ ucfirst($q->difficulty) }}</span>@endif
                        </div>

                        @foreach(($byQuestion[$q->id] ?? collect()) as $c)
                            <div class="small text-muted border-start ps-2 mb-1"><i class="ri-chat-1-line"></i> {{ $c->comment }} <span class="text-muted">— {{ optional($c->user)->name }}, {{ $c->created_at?->diffForHumans() }}</span></div>
                        @endforeach

                        <form method="POST" action="{{ route('exam.vet.comment', $paper) }}" class="d-flex gap-2 mt-1">@csrf
                            <input type="hidden" name="exam_question_id" value="{{ $q->id }}">
                            <input name="comment" class="form-control form-control-sm" placeholder="Comment on this question…" required>
                            <button class="btn btn-sm btn-outline-secondary"><i class="ri-send-plane-line"></i></button>
                        </form>
                    </div>
                @endforeach
            </x-cb.card>
        </div>

        <div class="col-lg-4">
            <x-cb.card title="Decision" icon="ri-gavel-line">
                @if(in_array($paper->status,['submitted','changes_requested']))
                    <form method="POST" action="{{ route('exam.vet.approve', $paper) }}" class="mb-3">@csrf
                        <label class="form-label small">Approval note (optional)</label>
                        <input name="comment" class="form-control mb-2" placeholder="Looks good…">
                        <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-check-double-line"></i>Approve paper</button>
                    </form>
                    <form method="POST" action="{{ route('exam.vet.changes', $paper) }}">@csrf
                        <label class="form-label small">Request changes *</label>
                        <textarea name="comment" rows="3" class="form-control mb-2" placeholder="What must the teacher fix?" required></textarea>
                        <button class="action-btn btn-open w-100 justify-content-center"><i class="ri-arrow-go-back-line"></i>Return for changes</button>
                    </form>
                @elseif($paper->status==='approved')
                    <div class="cb-banner info mb-2"><i class="ri-checkbox-circle-line"></i><div>Approved{{ $paper->vetted_at ? ' '.$paper->vetted_at->format('d M Y') : '' }}.</div></div>
                    <form method="POST" action="{{ route('exam.vet.lock', $paper) }}" class="mb-2">@csrf
                        <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-lock-line"></i>Lock for printing</button>
                    </form>
                @elseif($paper->status==='locked')
                    <div class="cb-banner info mb-2"><i class="ri-lock-line"></i><div>Locked for printing.</div></div>
                    <form method="POST" action="{{ route('exam.vet.unlock', $paper) }}">@csrf
                        <button class="action-btn btn-open w-100 justify-content-center"><i class="ri-lock-unlock-line"></i>Unlock</button>
                    </form>
                @endif
            </x-cb.card>

            <x-cb.card title="Paper-level note" icon="ri-chat-3-line">
                @foreach($paperLevel as $c)
                    <div class="small mb-2 pb-1 border-bottom">
                        <span class="badge bg-{{ $c->action==='approve'?'success':($c->action==='return'?'warning':'secondary') }}">{{ ucfirst($c->action) }}</span>
                        {{ $c->comment }}<div class="text-muted">{{ optional($c->user)->name }} · {{ $c->created_at?->diffForHumans() }}</div>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('exam.vet.comment', $paper) }}" class="mt-1">@csrf
                    <textarea name="comment" rows="2" class="form-control form-control-sm mb-1" placeholder="General note…" required></textarea>
                    <button class="btn btn-sm btn-outline-secondary w-100"><i class="ri-send-plane-line"></i> Add note</button>
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
