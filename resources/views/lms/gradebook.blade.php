{{-- resources/views/lms/gradebook.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Gradebook'" icon="ri-bar-chart-box-fill" :subtitle="$course->title"
        :back="route('lms.courses.show', $course)" back-label="Course">
        <x-slot name="actions">
            <a href="{{ route('lms.gradebook.export', $course) }}" class="action-btn btn-go"><i class="ri-download-line"></i>Export CSV</a>
            <button type="button" class="action-btn btn-go" data-bs-toggle="collapse" data-bs-target="#caExport"><i class="ri-file-transfer-line"></i>Export for CA</button>
        </x-slot>
    </x-cb.hero>

    <div class="collapse" id="caExport">
        <x-cb.card title="Export scores for score-entry / CA" icon="ri-file-transfer-line">
            <form method="GET" action="{{ route('lms.gradebook.export-ca', $course) }}" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Metric</label>
                    <select name="metric" class="form-select">
                        <option value="overall">Overall (weighted)</option>
                        <option value="quiz_avg">Quiz average</option>
                        <option value="assignment_avg">Assignment average</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label small">Scale to max</label><input type="number" min="1" name="max" value="20" class="form-control"></div>
                <div class="col-md-3"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-download-line"></i>Download CSV</button></div>
            </form>
            <div class="cb-banner info mt-2"><i class="ri-information-line"></i><div class="small">Produces admission-no + score scaled to your CA max, ready to enter in the results module. A one-click write into the broadsheet is planned separately once verified against the scoring engine.</div></div>
        </x-cb.card>
    </div>

    <x-cb.card title="Learners" icon="ri-group-line" :count="count($rows)" :flush="true">
        @if(empty($rows))
            <div class="empty-state"><i class="ri-bar-chart-box-line"></i><h6>No learners</h6><p>Enrol students to see grades.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th>Admission</th><th style="width:200px">Progress</th><th class="text-end">Quiz avg</th><th class="text-end">Assignment avg</th><th class="text-end">Overall</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ $r['name'] }}</td>
                        <td class="small">{{ $r['admissionNo'] ?? '—' }}</td>
                        <td>
                            <div class="progress" style="height:8px"><div class="progress-bar" style="width: {{ $r['progress'] }}%"></div></div>
                            <div class="small text-muted">{{ $r['progress'] }}%</div>
                        </td>
                        <td class="text-end">{{ $r['quiz_avg'] !== null ? $r['quiz_avg'].'%' : '—' }}</td>
                        <td class="text-end">{{ $r['assignment_avg'] !== null ? $r['assignment_avg'].'%' : '—' }}</td>
                        <td class="text-end fw-semibold">{{ $r['overall'] !== null ? $r['overall'].'%' : '—' }}</td>
                        <td><span class="status-pill {{ $r['status']==='completed' ? 'st-paid' : 'st-pending' }}">{{ ucfirst($r['status']) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
</div></div></div>
@endsection
