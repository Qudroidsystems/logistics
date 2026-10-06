@extends('layouts.master')
@section('content')

<style>
:root {
    --sr-primary: #1e3a5f;
    --sr-accent:  #2563eb;
    --sr-success: #16a34a;
    --sr-warning: #d97706;
    --sr-danger:  #dc2626;
    --sr-muted:   #6b7280;
    --sr-border:  #e2e8f0;
    --sr-radius:  10px;
    --sr-shadow:  0 1px 4px rgba(0,0,0,.08);
}

.sr-hero {
    background: linear-gradient(135deg, var(--sr-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sr-radius);
    padding: 24px 28px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.sr-hero::before {
    content:'';
    position:absolute;
    top:-60px; right:-60px;
    width:220px; height:220px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
}
.sr-hero h4 {
    color:#fff;
    font-weight:700;
    margin:0;
    position:relative;
    font-size:20px;
}
.sr-hero p {
    color:rgba(255,255,255,.75);
    margin:2px 0 0;
    font-size:13px;
    position:relative;
}
.sr-hero-avatar {
    width: 56px; height: 56px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,.35);
    flex-shrink: 0;
}
.sr-hero-avatar-fallback {
    width: 56px; height: 56px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 3px solid rgba(255,255,255,.35);
    flex-shrink: 0;
}

.sr-hours-banner {
    background: #eff6ff;
    color: #1e3a5f;
    border-radius: var(--sr-radius);
    padding: 10px 16px;
    font-size: 13px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.sr-hours-banner i { font-size: 20px; }
.sr-hours-banner .badge { font-weight: 600; }

.sr-stat-card {
    background:#fff;
    border:1px solid var(--sr-border);
    border-radius:var(--sr-radius);
    padding:14px 16px;
    text-align:center;
    transition:transform .15s, box-shadow .15s;
}
.sr-stat-card:hover {
    transform:translateY(-2px);
    box-shadow:var(--sr-shadow);
}
.sr-stat-card .stat-value {
    font-size:22px;
    font-weight:700;
}
.sr-stat-card .stat-label {
    font-size:11px;
    color:var(--sr-muted);
    margin-top:2px;
    text-transform:uppercase;
    letter-spacing:.4px;
}

.sr-card {
    background:#fff;
    border:1px solid var(--sr-border);
    border-radius:var(--sr-radius);
    box-shadow:var(--sr-shadow);
    overflow:hidden;
}
.sr-card .card-header {
    background:#fff;
    border-bottom:1px solid var(--sr-border);
    padding:14px 20px;
    font-weight:700;
    font-size:14px;
    color:var(--sr-primary);
}

.sr-table th {
    background:var(--sr-primary);
    color:#fff;
    padding:12px 16px;
    font-weight:600;
    font-size:13px;
    border:none;
}
.sr-table td {
    padding:11px 16px;
    vertical-align:middle;
    border-bottom:1px solid var(--sr-border);
    font-size:13px;
}
.sr-table tr:hover td {
    background:#eff6ff;
}

.sr-progress {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
}
.sr-progress > span {
    display:block;
    height:100%;
}

@media (max-width: 768px) {
    .sr-hero { padding: 18px; }
    .sr-hero h4 { font-size:16px; }
    .sr-table th, .sr-table td { padding:8px 10px; font-size:12px; }
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            @php
                $initials = strtoupper(substr($student?->fname ?? 'S', 0, 1) . substr($student?->lname ?? '', 0, 1));
                $photoPath = $student?->picture
                    ? asset('storage/student_avatars/' . basename($student->picture))
                    : null;
                $pct = $summary ? (float) $summary->attendance_percentage : 0;
                $pctColor = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
            @endphp
            <div class="sr-hero d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 position-relative">
                    @if($photoPath)
                        <img src="{{ $photoPath }}" class="sr-hero-avatar" alt="{{ $student->fname }}">
                    @else
                        <div class="sr-hero-avatar-fallback">{{ $initials ?: 'S' }}</div>
                    @endif
                    <div>
                        <h4><i class="ri-user-line me-2"></i>{{ $student?->lname }} {{ $student?->fname }} {{ $student?->mname }}</h4>
                        <p>
                            {{ $student?->admissionno ?? '—' }}
                            · {{ $class?->schoolclass }} {{ $class?->arms?->arm }}
                            · {{ $term?->term }} {{ $session?->session }}
                        </p>
                    </div>
                </div>
                <div class="d-flex gap-2 position-relative">
                    <a href="{{ route('attendance.register', [$classId, $termId, $sessionId]) }}"
                       class="btn btn-light btn-sm">
                        <i class="ri-arrow-left-line me-1"></i>Back to Register
                    </a>
                    @can('View attendance-class-summary')
                    <a href="{{ route('attendance.class-summary', [$classId, $termId, $sessionId]) }}"
                       class="btn btn-light btn-sm">
                        <i class="ri-bar-chart-2-line me-1"></i>Class Summary
                    </a>
                    @endcan
                </div>
            </div>

            {{-- School hours banner --}}
            @if($setting)
            <div class="sr-hours-banner">
                <i class="ri-time-line"></i>
                <div>
                    <strong>School Hours:</strong>
                    Resumption
                    <span class="badge bg-primary">
                        {{ \Illuminate\Support\Carbon::parse($setting->resumption_time ?? '08:00:00')->format('g:i A') }}
                    </span>
                    &nbsp;·&nbsp;
                    Closing
                    <span class="badge bg-secondary">
                        {{ \Illuminate\Support\Carbon::parse($setting->closing_time ?? '14:00:00')->format('g:i A') }}
                    </span>
                    @if($setting->track_afternoon)
                        &nbsp;·&nbsp;
                        Afternoon starts
                        <span class="badge bg-info text-dark">
                            {{ \Illuminate\Support\Carbon::parse($setting->morning_end_time ?? '12:00:00')->format('g:i A') }}
                        </span>
                    @endif
                    @if(($setting->late_grace_minutes ?? 0) > 0)
                        &nbsp;·&nbsp;
                        <span class="text-muted">Grace: {{ $setting->late_grace_minutes }} min</span>
                    @endif
                </div>
            </div>
            @endif

            {{-- Term summary stat cards --}}
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3 col-lg">
                    <div class="sr-stat-card">
                        <div class="stat-value text-success">{{ $summary->days_present ?? 0 }}</div>
                        <div class="stat-label">Present</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="sr-stat-card">
                        <div class="stat-value text-danger">{{ $summary->days_absent ?? 0 }}</div>
                        <div class="stat-label">Absent</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="sr-stat-card">
                        <div class="stat-value text-warning">{{ $summary->days_sick_leave ?? 0 }}</div>
                        <div class="stat-label">Sick</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="sr-stat-card">
                        <div class="stat-value text-info">{{ $summary->days_excused ?? 0 }}</div>
                        <div class="stat-label">Excused</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="sr-stat-card">
                        <div class="stat-value text-secondary">{{ $summary->days_late ?? 0 }}</div>
                        <div class="stat-label">Late</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="sr-stat-card">
                        <div class="stat-value text-{{ $pctColor }}">{{ $pct }}%</div>
                        <div class="stat-label">Attendance</div>
                    </div>
                </div>
            </div>

            {{-- Attendance progress bar --}}
            @php
                $totalDays = $summary->total_school_days ?? ($setting?->totalSchoolDays() ?? 0);
            @endphp
            <div class="sr-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ri-bar-chart-line me-2 text-primary"></i>Term Attendance Progress</span>
                    <span class="fw-bold text-{{ $pctColor }}">
                        {{ $pct }}%
                        <small class="text-muted fw-normal ms-1">({{ $totalDays }} school days)</small>
                    </span>
                </div>
                <div class="card-body">
                    <div class="sr-progress">
                        <span class="bg-{{ $pctColor }}" style="width: {{ min($pct, 100) }}%"></span>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-muted" style="font-size:11px;">
                        <span>0%</span>
                        <span>Target: 80%</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>

            {{-- Daily log --}}
            <div class="sr-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ri-calendar-line me-2 text-primary"></i>Daily Log</span>
                    <span class="badge bg-primary">{{ $records->count() }} record{{ $records->count() === 1 ? '' : 's' }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table sr-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:44px;">#</th>
                                    <th>Date</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($records as $i => $r)
                                @php
                                    $statusColors = [
                                        'present'    => 'success',
                                        'absent'     => 'danger',
                                        'sick_leave' => 'warning',
                                        'excused'    => 'info',
                                        'late'       => 'secondary',
                                    ];
                                    $sc = $statusColors[$r->status] ?? 'secondary';
                                    $label = ucfirst(str_replace('_', ' ', $r->status));
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ \Illuminate\Support\Carbon::parse($r->attendance_date)->format('D, d M Y') }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $r->period === 'afternoon' ? 'info' : 'primary' }}-subtle text-{{ $r->period === 'afternoon' ? 'info' : 'primary' }}">
                                            {{ ucfirst($r->period ?? 'morning') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} fw-semibold">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="text-muted">
                                        {{ $r->time_in ? \Illuminate\Support\Carbon::parse($r->time_in)->format('g:i A') : '—' }}
                                    </td>
                                    <td class="text-muted">
                                        {{ $r->time_out ? \Illuminate\Support\Carbon::parse($r->time_out)->format('g:i A') : '—' }}
                                    </td>
                                    <td>
                                        @if($r->source === 'device')
                                            <span class="badge bg-dark-subtle text-dark">
                                                <i class="ri-fingerprint-line me-1"></i>Device
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark border">
                                                <i class="ri-user-line me-1"></i>Manual
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-muted" style="font-size:12px;">
                                        {{ $r->notes ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="ri-inbox-line ri-2x d-block mb-2"></i>
                                        No attendance records yet for this term.
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

@endsection