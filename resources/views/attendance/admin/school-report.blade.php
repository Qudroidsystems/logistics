{{-- resources/views/attendance/admin/staff-report.blade.php
     This is the view StaffAttendanceController@report() actually renders
     ('attendance.admin.staff-report') — NOT attendance/staff/staff-attendance-detail.blade.php. --}}
@extends('layouts.master')
@section('content')
<style>
:root {
    --sad-primary: #1e3a5f;
    --sad-accent:  #2563eb;
    --sad-success: #16a34a;
    --sad-warning: #d97706;
    --sad-danger:  #dc2626;
    --sad-muted:   #6b7280;
    --sad-border:  #e2e8f0;
    --sad-radius:  10px;
    --sad-shadow:  0 1px 4px rgba(0,0,0,.08);
}

.sad-hero {
    background: linear-gradient(135deg, var(--sad-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sad-radius);
    padding: 24px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.sad-hero::before {
    content:'';
    position:absolute;
    top:-60px;
    right:-60px;
    width:220px;
    height:220px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
}
.sad-hero h4 {
    color:#fff;
    font-weight:700;
    margin:0;
    position:relative;
}
.sad-hero p {
    color:rgba(255,255,255,.75);
    margin:0;
    font-size:13px;
    position:relative;
}
.sad-hero-avatar {
    width: 52px; height: 52px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 700; color: #fff;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 2px solid rgba(255,255,255,.4);
    flex-shrink: 0;
    object-fit: cover;
}

.sad-stat-card {
    background:#fff;
    border:1px solid var(--sad-border);
    border-radius:var(--sad-radius);
    padding:14px 16px;
    text-align:center;
    transition:transform .15s, box-shadow .15s;
}
.sad-stat-card:hover {
    transform:translateY(-2px);
    box-shadow:var(--sad-shadow);
}
.sad-stat-card .stat-value {
    font-size:20px;
    font-weight:700;
}
.sad-stat-card .stat-label {
    font-size:11px;
    color:var(--sad-muted);
    margin-top:2px;
}

.sad-card {
    background:#fff;
    border:1px solid var(--sad-border);
    border-radius:var(--sad-radius);
    box-shadow:var(--sad-shadow);
    overflow:hidden;
}
.sad-card .card-header {
    background:#fff;
    border-bottom:1px solid var(--sad-border);
    padding:16px 20px;
    font-weight:700;
    font-size:14px;
    color:var(--sad-primary);
}

.sad-table th {
    background:var(--sad-primary);
    color:#fff;
    padding:12px 16px;
    font-weight:600;
    font-size:13px;
    border:none;
}
.sad-table td {
    padding:12px 16px;
    vertical-align:middle;
    border-bottom:1px solid var(--sad-border);
    font-size:13px;
}
.sad-table tr:hover td {
    background:#eff6ff;
}

.sad-form-control {
    border:1.5px solid var(--sad-border);
    border-radius:8px;
    font-size:13px;
    padding:9px 14px;
    transition:border .15s;
}
.sad-form-control:focus {
    border-color:var(--sad-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
    outline:none;
}
.sad-form-control-sm {
    padding:6px 10px;
    border-radius:7px;
}
.sad-weekday-row { display:flex; gap:6px; flex-wrap:wrap; }
.sad-weekday-chip {
    display:inline-flex; align-items:center; gap:4px;
    background:#fff; border:1px solid var(--sad-border);
    border-radius:20px; padding:4px 10px;
    font-size:11px; cursor:pointer; user-select:none;
}
.sad-weekday-chip input { accent-color:#dc2626; }
.sad-weekday-chip.checked { background:#fef2f2; border-color:#fca5a5; color:#dc2626; }

/* ── School-hours banner ── */
.sad-hours-banner {
    background: #eff6ff;
    color: #1e3a5f;
    border-radius: var(--sad-radius);
    padding: 12px 18px;
    font-size: 13px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    border: 1px solid #dbeafe;
}
.sad-hours-banner i { font-size: 20px; }
.sad-hours-banner .badge { font-weight: 600; }
.sad-hours-banner .sad-grace-pill {
    background: #fef3c7;
    color: #92400e;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
}

/* ── Late cell ── */
.sad-late-by {
    font-weight: 700;
    color: #b45309;
    font-size: 12.5px;
    white-space: nowrap;
}
.sad-late-by .mins { color: #dc2626; }
.sad-ontime {
    color: #16a34a;
    font-weight: 600;
    font-size: 12.5px;
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

    {{-- ══ HERO ═══════════════════════════════════════════════════════════ --}}
    <div class="sad-hero d-flex align-items-center justify-content-between flex-wrap gap-2">
        @php
            $initials = strtoupper(substr($staff->full_name,0,1) . (strpos($staff->full_name,' ')!==false ? substr($staff->full_name, strpos($staff->full_name,' ')+1, 1) : ''));
        @endphp
        <div class="d-flex align-items-center gap-3">
            @if($staff->user?->avatar_url)
            <img src="{{ $staff->user->avatar_url }}" class="sad-hero-avatar" alt="{{ $staff->full_name }}">
            @else
            <div class="sad-hero-avatar">{{ $initials ?: 'S' }}</div>
            @endif
            <div>
                <h4><i class="ri-user-line me-2"></i>Staff Attendance – {{ $staff->full_name }}</h4>
                <p>{{ $staff->employmentid }} · {{ $staff->department ?? '—' }}</p>
            </div>
        </div>
        <a href="{{ route('staff-attendance.index') }}" class="btn btn-light btn-sm">
            <i class="ri-arrow-left-line me-1"></i>Back to Report
        </a>
    </div>

    {{-- ══ SCHOOL HOURS / LATE EXPECTATION BANNER ═══════════════════════ --}}
    @php
        $currentTermSetting = \App\Models\AttendanceTermSetting::current();
        $resumptionLabel = $currentTermSetting?->resumption_time
            ? \Illuminate\Support\Carbon::parse($currentTermSetting->resumption_time)->format('g:i A')
            : '8:00 AM';
        $closingLabel = $currentTermSetting?->closing_time
            ? \Illuminate\Support\Carbon::parse($currentTermSetting->closing_time)->format('g:i A')
            : '2:00 PM';
        $graceMinutes = (int) ($currentTermSetting->late_grace_minutes ?? 0);
        $expectedBy = $currentTermSetting?->resumption_time
            ? \Illuminate\Support\Carbon::parse($currentTermSetting->resumption_time)->addMinutes($graceMinutes)->format('g:i A')
            : '8:00 AM';
    @endphp
    @if($currentTermSetting)
    <div class="sad-hours-banner">
        <i class="ri-time-line"></i>
        <div class="flex-grow-1">
            <strong>School Hours:</strong>
            Resumption <span class="badge bg-primary">{{ $resumptionLabel }}</span>
            &nbsp;·&nbsp;
            Closing <span class="badge bg-secondary">{{ $closingLabel }}</span>
            &nbsp;·&nbsp;
            Expected clock-in by
            <span class="badge bg-dark">{{ $expectedBy }}</span>
            @if($graceMinutes > 0)
                <span class="sad-grace-pill ms-2">
                    <i class="ri-timer-line me-1"></i>{{ $graceMinutes }} min grace
                </span>
            @else
                <span class="sad-grace-pill ms-2" style="background:#e2e8f0;color:#475569;">
                    <i class="ri-timer-line me-1"></i>No grace
                </span>
            @endif
        </div>
    </div>
    @endif

    {{-- ══ STATS + FILTER ═══════════════════════════════════════════════ --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="sad-card">
                <div class="card-body py-3">
                    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                        <input type="hidden" name="weekday_filter_submitted" value="1">
                        <div>
                            <label class="text-muted" style="font-size:11px;display:block;margin-bottom:4px;">From</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="sad-form-control sad-form-control-sm" style="width:150px;">
                        </div>
                        <div>
                            <label class="text-muted" style="font-size:11px;display:block;margin-bottom:4px;">To</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="sad-form-control sad-form-control-sm" style="width:150px;">
                        </div>
                        <div>
                            <label class="text-muted" style="font-size:11px;display:block;margin-bottom:4px;">Exclude Weekdays</label>
                            @php
                                $weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                $excludedWeekdaysSet = collect($excludedWeekdays ?? [])->map(fn($d) => (int) $d);
                            @endphp
                            <div class="sad-weekday-row">
                                @foreach($weekdayLabels as $dayNum => $dayLabel)
                                <label class="sad-weekday-chip {{ $excludedWeekdaysSet->contains($dayNum) ? 'checked' : '' }}">
                                    <input type="checkbox" name="excluded_weekdays[]" value="{{ $dayNum }}"
                                           {{ $excludedWeekdaysSet->contains($dayNum) ? 'checked' : '' }}
                                           onchange="this.closest('label').classList.toggle('checked', this.checked)">
                                    {{ $dayLabel }}
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm"><i class="ri-search-line me-1"></i>Filter</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="row g-2">
                @foreach([
                    ['Present', $present, 'success'],
                    ['Late', $late, 'secondary'],
                    ['Excused', $excused, 'info'],
                    ['Absent', $absent, 'danger'],
                ] as [$label, $val, $color])
                <div class="col-3">
                    <div class="sad-stat-card">
                        <div class="stat-value text-{{ $color }}">{{ $val }}</div>
                        <div class="stat-label">{{ $label }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══ DAILY LOG TABLE ══════════════════════════════════════════════ --}}
    <div class="sad-card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1"><i class="ri-calendar-line me-2 text-primary"></i>Daily Log</h5>
            <span class="fw-bold text-{{ $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger') }}">{{ $pct }}% attendance</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table sad-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Expected</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Late By</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($calendar as $day)
                        @php
                            // 'excluded' = admin ticked this date in the Exclude Days panel
                            // for this report only (see StaffAttendanceController::resolveExcludedDates).
                            // 'outage'   = a persisted DeviceOutageDate.
                            $sc = ['present'=>'success','late'=>'secondary','excused'=>'info','absent'=>'danger','outage'=>'dark','excluded'=>'warning'];
                            $c  = $sc[$day['status']] ?? 'secondary';
                            $statusLabel = $day['status'] === 'excluded' ? 'Excluded' : ucfirst($day['status']);

                            // Compute minutes late for this row, if the staff member was late.
                            $minutesLate = null;
                            if ($day['status'] === 'late' && !empty($day['time_in']) && $currentTermSetting?->resumption_time) {
                                $punchDate = \Illuminate\Support\Carbon::parse($day['date'] ?? $day['label']);
                                $expected  = \Illuminate\Support\Carbon::parse(
                                    $punchDate->toDateString().' '.$currentTermSetting->resumption_time
                                )->addMinutes($graceMinutes);
                                $actual = \Illuminate\Support\Carbon::parse($punchDate->toDateString().' '.$day['time_in']);
                                $minutesLate = max(0, $expected->diffInMinutes($actual, false));
                            }
                        @endphp
                        <tr>
                            <td><strong>{{ $day['label'] }}</strong></td>
                            <td><span class="badge bg-{{ $c }}-subtle text-{{ $c }} fw-semibold">{{ $statusLabel }}</span></td>
                            <td class="text-muted" style="font-size:12px;">
                                @if($day['status'] === 'absent' || $day['status'] === 'outage' || $day['status'] === 'excluded')
                                    —
                                @else
                                    {{ $expectedBy }}
                                    @if($graceMinutes > 0)
                                        <span class="text-muted" style="font-size:10.5px;">(+{{ $graceMinutes }}m)</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-muted">{{ $day['time_in'] ?? '—' }}</td>
                            <td class="text-muted">{{ $day['time_out'] ?? '—' }}</td>
                            <td>
                                @if($day['status'] === 'late' && $minutesLate !== null)
                                    @php
                                        $h = intdiv($minutesLate, 60);
                                        $m = $minutesLate % 60;
                                        $human = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                                    @endphp
                                    <span class="sad-late-by">
                                        <i class="ri-timer-flash-line me-1"></i>
                                        <span class="mins">{{ $human }}</span> late
                                    </span>
                                @elseif($day['status'] === 'present')
                                    <span class="sad-ontime"><i class="ri-check-line me-1"></i>On time</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">
                            <i class="ri-inbox-line ri-2x d-block mb-2"></i>No working days in this range.
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 text-muted" style="font-size:11px;">
                Late = clocked in after <strong>{{ $expectedBy }}</strong>
                @if($graceMinutes > 0) (resumption {{ $resumptionLabel }} + {{ $graceMinutes }}-min grace) @endif.
            </div>
        </div>
    </div>

</div></div></div>
@endsection