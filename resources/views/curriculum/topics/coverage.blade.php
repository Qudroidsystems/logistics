{{-- resources/views/curriculum/topics/coverage.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Topic Coverage" icon="ri-bar-chart-2-fill" subtitle="How much of the syllabus each arm has covered."
        :back="route('curriculum.topics.index')" back-label="Topics" />

    <x-cb.card title="Choose" icon="ri-filter-3-line">
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
            <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i></button></div>
        </form>
    </x-cb.card>

    @if(request('subject') && request('class_level'))
    <x-cb.card title="Coverage by arm" icon="ri-bar-chart-line" :count="$rows->count()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-bar-chart-line"></i><h6>No arms found</h6><p>No class is teaching this subject at this level for the selected term.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Arm</th><th style="width:220px">Coverage ({{ $total }} topics)</th><th class="text-end">Taught</th><th class="text-end">Verified</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r->arm_name }}</td>
                        <td>
                            <div class="progress" style="height:8px"><div class="progress-bar {{ $r->pct>=70?'bg-success':($r->pct>=40?'':'bg-danger') }}" style="width: {{ $r->pct }}%"></div></div>
                            <div class="small text-muted">{{ $r->pct }}%</div>
                        </td>
                        <td class="text-end">{{ $r->taught }}/{{ $total }}</td>
                        <td class="text-end">{{ $r->confirmed }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @endif
</div></div></div>
@endsection
