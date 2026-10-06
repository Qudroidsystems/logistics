@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Page Title --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Class Attendance Summary</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('attendance.my-classes') }}">Attendance</a></li>
                                <li class="breadcrumb-item active">Class Summary</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Class Info --}}
            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm mb-0" style="border-left: 4px solid #2563eb !important;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <h5 class="fw-bold mb-1">{{ $class?->schoolclass }} {{ $class?->arms?->arm }}</h5>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-primary-subtle text-primary">{{ $term?->term }}</span>
                                        <span class="badge bg-info-subtle text-info">{{ $session?->session }}</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    @can('Create attendance-register')
                                    <a href="{{ route('attendance.register', [$classId, $termId, $sessionId]) }}"
                                       class="btn btn-primary btn-sm">
                                        <i class="ri-check-line me-1"></i>Mark Attendance
                                    </a>
                                    @endcan
                                    <a href="{{ route('attendance.my-classes') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="ri-arrow-left-line me-1"></i>Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                @php
                    $totP   = $summaries->sum('days_present');
                    $totA   = $summaries->sum('days_absent');
                    $totS   = $summaries->sum('days_sick_leave');
                    $avgPct = $summaries->count() > 0 ? round($summaries->avg('attendance_percentage'), 1) : 0;
                    $totalDays = $setting?->totalSchoolDays() ?? 0;
                    $avgColor = $avgPct >= 80 ? 'success' : ($avgPct >= 60 ? 'warning' : 'danger');
                @endphp
                <div class="col-lg-6">
                    <div class="row g-2">
                        <div class="col-3">
                            <div class="card border text-center mb-0 py-2">
                                <div class="fw-bold fs-4 text-primary">{{ $summaries->count() }}</div>
                                <div class="text-muted" style="font-size:11px;">Students</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card border text-center mb-0 py-2">
                                <div class="fw-bold fs-4 text-secondary">{{ $totalDays }}</div>
                                <div class="text-muted" style="font-size:11px;">School Days</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card border text-center mb-0 py-2">
                                <div class="fw-bold fs-4 text-success">{{ $totP }}</div>
                                <div class="text-muted" style="font-size:11px;">Present</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="card border text-center mb-0 py-2">
                                <div class="fw-bold fs-4 text-{{ $avgColor }}">{{ $avgPct }}%</div>
                                <div class="text-muted" style="font-size:11px;">Class Avg</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center py-3">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <i class="ri-group-line me-2 text-primary"></i>Student Attendance Summary
                    </h5>
                    <div class="input-group input-group-sm" style="width:240px;">
                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" id="srch" placeholder="Search students…">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead style="background:#1e3a5f;">
                                <tr>
                                    <th class="text-white">#</th>
                                    <th class="text-white">Student</th>
                                    <th class="text-white text-center">Present</th>
                                    <th class="text-white text-center">Absent</th>
                                    <th class="text-white text-center">Sick</th>
                                    <th class="text-white text-center">Excused</th>
                                    <th class="text-white text-center">Late</th>
                                    <th class="text-white">Attendance</th>
                                    <th class="text-white"></th>
                                </tr>
                            </thead>
                            <tbody id="summaryRows">
                            @forelse($summaries as $i => $s)
                                @php
                                    $pct = (float) $s->attendance_percentage;
                                    $col = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                                    $img = $s->picture
                                        ? asset('storage/student_avatars/'.basename($s->picture))
                                        : asset('storage/student_avatars/unnamed.jpg');
                                @endphp
                                <tr data-name="{{ strtolower($s->lname.' '.$s->fname.' '.$s->admissionno) }}">
                                    <td class="text-muted fw-medium">{{ $i+1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $img }}" class="rounded-circle"
                                                 style="width:34px;height:34px;object-fit:cover;border:2px solid #e2e8f0;"
                                                 onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'">
                                            <div>
                                                <div class="fw-semibold" style="font-size:13px;">{{ $s->lname }} {{ $s->fname }}</div>
                                                <div class="text-muted" style="font-size:11px;">{{ $s->admissionno }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-success-subtle text-success fw-semibold">{{ $s->days_present }}</span></td>
                                    <td class="text-center"><span class="badge bg-danger-subtle text-danger fw-semibold">{{ $s->days_absent }}</span></td>
                                    <td class="text-center"><span class="badge bg-warning-subtle text-warning fw-semibold">{{ $s->days_sick_leave }}</span></td>
                                    <td class="text-center"><span class="badge bg-info-subtle text-info fw-semibold">{{ $s->days_excused }}</span></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary fw-semibold">{{ $s->days_late }}</span></td>
                                    <td style="min-width:160px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:6px;">
                                                <div class="progress-bar bg-{{ $col }}" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <span class="fw-bold text-{{ $col }}" style="font-size:12px;min-width:38px;">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        @can('View attendance-student-report')
                                        <a href="{{ route('attendance.student-report', [$s->student_id, $classId, $termId, $sessionId]) }}"
                                           class="btn btn-outline-primary btn-sm" style="font-size:11px;">
                                            Details →
                                        </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="ri-inbox-line ri-2x d-block mb-2 text-muted"></i>
                                        <p class="text-muted mb-0">No attendance data recorded yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('srch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#summaryRows tr[data-name]').forEach(r => {
        r.style.display = (!q || r.dataset.name.includes(q)) ? '' : 'none';
    });
});
</script>
@endsection
