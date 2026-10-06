{{-- resources/views/parent/results.blade.php --}}
@extends('layouts.master')

@section('content')
@php $naira = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Results — ' . $child->firstname . ' ' . $child->lastname" icon="ri-file-chart-line"
               subtitle="Term results and report cards." :back="route('parent.dashboard')" back-label="My children" />

    @include('parent.partials.nav', ['section' => 'results'])

    @if($terms->isEmpty())
        <div class="cb-card"><div class="empty-state"><i class="ri-file-chart-line"></i><h6>No results yet</h6><p>Results appear here once the school publishes them.</p></div></div>
    @else
        <div class="row g-3">
            <div class="col-lg-4">
                <x-cb.card title="Terms" icon="ri-calendar-line" :count="$terms->count()" :flush="true">
                    <div class="list-group list-group-flush">
                        @foreach($terms as $t)
                            @php $active = $selected && $t->term_id == $selected->term_id && $t->session_id == $selected->session_id; @endphp
                            <a href="{{ route('parent.results', ['student' => $child->id, 'term_id' => $t->term_id, 'session_id' => $t->session_id]) }}"
                               class="list-group-item list-group-item-action {{ $active ? 'active' : '' }}">
                                <div class="d-flex justify-content-between"><strong>{{ $t->term }} · {{ $t->session }}</strong>
                                    @if(!$t->allowed && !empty($t->not_released))<span class="status-pill st-muted">Not released</span>
                                    @elseif(!$t->allowed)<span class="status-pill st-danger">On hold</span>
                                    @elseif(!$t->ready)<span class="status-pill st-pending">In progress</span>
                                    @else<span class="status-pill st-paid">Ready</span>@endif
                                </div>
                                <div class="small {{ $active ? '' : 'text-muted' }}">{{ $t->class_name }} · {{ $t->subjects }} subjects · avg {{ $t->average }}</div>
                            </a>
                        @endforeach
                    </div>
                </x-cb.card>
            </div>
            <div class="col-lg-8">
                @if($selected && !$selected->allowed)
                    <div class="cb-card"><div class="empty-state"><i class="ri-lock-line"></i><h6>{{ !empty($selected->not_released) ? 'Not released yet' : 'This result is on hold' }}</h6>
                        <p>{{ $selected->hold_message ?: 'Please clear the outstanding fees to view this result.' }}</p>
                        @if($selected->owed > 0)
                            <p class="fw-bold text-danger">Outstanding: {{ $naira($selected->owed) }}</p>
                            <a href="{{ route('parent.pay', $child->id) }}" class="action-btn btn-primary-cb"><i class="ri-secure-payment-line"></i>Pay online</a>
                        @endif
                    </div></div>
                @elseif($selected)
                    <x-cb.card :title="$selected->term . ' · ' . $selected->session" icon="ri-file-list-3-line" :count="$scores->count()" :flush="true">
                        <x-slot:tools>
                            @if($selected->ready)
                                <a target="_blank" class="action-btn btn-go"
                                   href="{{ route('parent.report-card', ['student' => $child->id, 'session' => $selected->session_id, 'term' => $selected->term_id, 'class' => $selected->class_id]) }}">
                                    <i class="ri-file-pdf-2-line"></i>Report card (PDF)</a>
                            @endif
                        </x-slot:tools>
                        @unless($selected->ready)
                            <div class="cb-banner info m-3"><i class="ri-information-line"></i><div>Some subjects are still being checked by teachers. The report card will be available once all are approved.</div></div>
                        @endunless
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Subject</th><th class="text-end">Score</th><th>Grade</th><th class="text-end">Position</th><th class="text-end">Class avg</th><th>Remark</th></tr></thead>
                                <tbody>
                                @foreach($scores as $r)
                                    <tr>
                                        <td>{{ $r->subject }}</td>
                                        <td class="text-end fw-bold">{{ $r->total }}</td>
                                        <td><span class="term-badge">{{ $r->grade ?: '—' }}</span></td>
                                        <td class="text-end">{{ $r->position ?: '—' }}</td>
                                        <td class="text-end">{{ $r->class_average !== null ? round($r->class_average, 1) : '—' }}</td>
                                        <td class="small text-muted">{{ $r->remark }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-cb.card>
                @endif
            </div>
        </div>
    @endif
</div>
</div>
</div>
@endsection
