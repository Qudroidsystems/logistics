{{-- resources/views/lms/parent/child.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="trim(($child->firstname ?? '').' '.($child->lastname ?? ''))" icon="ri-graduation-cap-fill"
        :subtitle="'Learning progress · '.($child->admissionNo ?? '')"
        :back="route('lms.parent.index')" back-label="Children" />

    <x-cb.card title="Courses" icon="ri-book-open-line" :count="$courses->count()" :flush="true">
        @if($courses->isEmpty())
            <div class="empty-state"><i class="ri-book-open-line"></i><h6>Not enrolled in any course</h6></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Course</th><th style="width:200px">Progress</th><th class="text-end">Quiz avg</th><th class="text-end">Assignment avg</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($courses as $c)
                    <tr>
                        <td><div class="fw-semibold">{{ $c->title }}</div><div class="small text-muted">{{ $c->code ?: '' }}</div></td>
                        <td>
                            <div class="progress" style="height:8px"><div class="progress-bar {{ $c->status==='completed' ? 'bg-success' : '' }}" style="width: {{ (int)$c->progress_percent }}%"></div></div>
                            <div class="small text-muted">{{ (int)$c->progress_percent }}%</div>
                        </td>
                        <td class="text-end">{{ $c->quiz_avg !== null ? $c->quiz_avg.'%' : '—' }}</td>
                        <td class="text-end">{{ $c->assignment_avg !== null ? $c->assignment_avg.'%' : '—' }}</td>
                        <td><span class="status-pill {{ $c->status==='completed' ? 'st-paid' : 'st-pending' }}">{{ ucfirst($c->status) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
</div></div></div>
@endsection
