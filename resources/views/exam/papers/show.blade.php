{{-- resources/views/exam/papers/show.blade.php — preview --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$paper->title" icon="ri-file-list-3-fill"
        :subtitle="$label.' · '.$paper->typeLabel()"
        :back="route('exam.papers.index')" back-label="Exam Papers">
        <x-slot:actions>
            @php [$lbl,$cls] = $paper->label(); @endphp
            <span class="status-pill {{ $cls }}">{{ $lbl }}</span>
            @if(in_array($paper->status,['approved','locked']))
                <a href="{{ route('exam.papers.print', $paper) }}" target="_blank" class="action-btn btn-open"><i class="ri-printer-line"></i>Print</a>
                <a href="{{ route('exam.papers.word', $paper) }}" class="action-btn btn-open"><i class="ri-file-word-2-line"></i>Word</a>
            @endif
            @if($canVet && in_array($paper->status,['submitted','changes_requested']))
                <a href="{{ route('exam.vet.review', $paper) }}" class="action-btn btn-primary-cb"><i class="ri-shield-check-line"></i>Vet this paper</a>
            @endif
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Questions" icon="ri-list-ordered" :count="$paper->questions->count()">
                @if($paper->instructions)<p class="text-muted fst-italic mb-3">{{ $paper->instructions }}</p>@endif
                @forelse($paper->questions as $i => $q)
                    <div class="mb-3 pb-2 border-bottom">
                        <div class="d-flex justify-content-between">
                            <div class="fw-semibold">{{ $q->number ?: ($i+1) }}. {{ $q->question }}</div>
                            <div class="text-muted small ms-2" style="white-space:nowrap">{{ rtrim(rtrim(number_format($q->marks,2),'0'),'.') }} mk</div>
                        </div>
                        @if($q->type==='objective' && $q->options)
                            <div class="row g-1 mt-1 ms-1">
                                @foreach($q->options as $L => $opt)<div class="col-md-6 small">({{ $L }}) {{ $opt }}</div>@endforeach
                            </div>
                        @endif
                        <div class="mt-1">
                            @foreach($q->topics as $t)<span class="status-pill st-info me-1">{{ $t->title }}</span>@endforeach
                            @if($q->topics->isEmpty())<span class="status-pill st-pending">Untagged</span>@endif
                            @if($q->difficulty)<span class="badge bg-light text-dark ms-1">{{ ucfirst($q->difficulty) }}</span>@endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state"><i class="ri-list-ordered"></i><p>No questions.</p></div>
                @endforelse
            </x-cb.card>
        </div>
        <div class="col-lg-4">
            <x-cb.card title="Summary" icon="ri-information-line">
                <table class="table table-sm mb-0"><tbody>
                    <tr><td class="text-muted">Questions</td><td class="text-end">{{ $paper->questions->count() }}</td></tr>
                    <tr><td class="text-muted">Total marks</td><td class="text-end">{{ rtrim(rtrim(number_format($paper->total_marks,2),'0'),'.') }}</td></tr>
                    <tr><td class="text-muted">Duration</td><td class="text-end">{{ $paper->duration_minutes ? $paper->duration_minutes.' min' : '—' }}</td></tr>
                    <tr><td class="text-muted">Tagged</td><td class="text-end">{{ $paper->questions->filter(fn($q)=>$q->topics->count())->count() }}/{{ $paper->questions->count() }}</td></tr>
                </tbody></table>
                <a href="{{ route('exam.coverage.report', $paper) }}" class="action-btn btn-open w-100 justify-content-center mt-2"><i class="ri-bar-chart-2-line"></i>Coverage &amp; alignment</a>
                @if(auth()->user()->can('Write exam papers') && (int)$paper->teacher_id===auth()->id() && in_array($paper->status,['approved','locked']))
                    <a href="{{ route('exam.scores.grid', $paper) }}" class="action-btn btn-primary-cb w-100 justify-content-center mt-2"><i class="ri-table-line"></i>Enter scores</a>
                @endif
            </x-cb.card>

            @if($paper->comments->count())
            <x-cb.card title="Vetting notes" icon="ri-chat-3-line">
                @foreach($paper->comments as $c)
                    <div class="small mb-2 pb-2 border-bottom">
                        <span class="badge bg-{{ $c->action==='approve'?'success':($c->action==='return'?'warning':'secondary') }}">{{ ucfirst($c->action) }}</span>
                        {{ $c->comment }}
                        <div class="text-muted">{{ $c->created_at?->diffForHumans() }}</div>
                    </div>
                @endforeach
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
