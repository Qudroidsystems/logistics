@extends('layouts.master')
@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <div class="row"><div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Attendance History</h4>
            <div class="page-title-right"><ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="{{ route('attendance.my-classes') }}">Attendance</a></li>
                <li class="breadcrumb-item active">History</li>
            </ol></div>
        </div>
    </div></div>

    <div class="row"><div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-2 text-primary"></i>
                        {{ $schoolclass->schoolclass ?? '' }} {{ $schoolclass->arms->arm ?? '' }}
                    </h5>
                    <div class="mt-1">
                        <span class="badge bg-primary-subtle text-primary">{{ $term->term ?? '' }}</span>
                        <span class="badge bg-info-subtle text-info">{{ $session->session ?? '' }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @can('Create attendance-register')
                    <a href="{{ route('attendance.register', [$classId, $termId, $sessionId]) }}" class="btn btn-primary btn-sm"><i class="ri-check-line me-1"></i> Mark today</a>
                    @endcan
                    @can('View attendance-class-summary')
                    <a href="{{ route('attendance.class-summary', [$classId, $termId, $sessionId]) }}" class="btn btn-outline-secondary btn-sm"><i class="ri-bar-chart-2-line me-1"></i> Summary</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Every day this class was marked, for the selected term and session.</p>
                @if($days->isEmpty())
                    <div class="text-center py-5">
                        <i class="ri-calendar-line" style="font-size:48px;color:#cbd5e1"></i>
                        <h5 class="text-muted mt-3">No attendance recorded yet</h5>
                        <p class="text-muted mb-0">Marked days will appear here.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th><th>Period</th>
                                    <th class="text-end">Present</th><th class="text-end">Late</th>
                                    <th class="text-end">Absent</th><th class="text-end">Excused</th>
                                    <th class="text-end">Total</th><th>Last marked</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($days as $d)
                                @php $dt = \Carbon\Carbon::parse($d->attendance_date); @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $dt->format('D, d M Y') }}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary text-capitalize">{{ $d->period }}</span></td>
                                    <td class="text-end text-success fw-semibold">{{ $d->present }}</td>
                                    <td class="text-end text-warning">{{ $d->late }}</td>
                                    <td class="text-end text-danger">{{ $d->absent }}</td>
                                    <td class="text-end text-info">{{ $d->excused }}</td>
                                    <td class="text-end">{{ $d->total }}</td>
                                    <td class="small text-muted">{{ $d->last_marked ? \Carbon\Carbon::parse($d->last_marked)->format('d M, g:i a') : '—' }}</td>
                                    <td class="text-end">
                                        @can('Create attendance-register')
                                        <a href="{{ route('attendance.register', [$classId, $termId, $sessionId]) }}?date={{ $dt->toDateString() }}&period={{ $d->period }}" class="btn btn-outline-primary btn-sm" title="Open this day"><i class="ri-external-link-line"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $days->links() }}</div>
                @endif
            </div>
        </div>
    </div></div>
</div></div></div>
@endsection
