{{-- resources/views/attendance/admin/staff-school-report.blade.php --}}
@extends('layouts.master')
@section('content')
<style>
:root {
    --sar-primary: #1e3a5f;
    --sar-accent:  #2563eb;
    --sar-success: #16a34a;
    --sar-warning: #d97706;
    --sar-danger:  #dc2626;
    --sar-muted:   #6b7280;
    --sar-border:  #e2e8f0;
    --sar-radius:  10px;
    --sar-shadow:  0 1px 4px rgba(0,0,0,.08);
}

.sar-hero {
    background: linear-gradient(135deg, var(--sar-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--sar-radius);
    padding: 24px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.sar-hero::before {
    content:'';
    position:absolute;
    top:-60px;
    right:-60px;
    width:220px;
    height:220px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
}
.sar-hero h4 {
    color:#fff;
    font-weight:700;
    margin:0;
    position:relative;
}
.sar-hero p {
    color:rgba(255,255,255,.75);
    margin:0;
    font-size:13px;
    position:relative;
}

.sar-stat-card {
    background:#fff;
    border:1px solid var(--sar-border);
    border-radius:var(--sar-radius);
    padding:16px 18px;
    text-align:center;
    transition:transform .15s, box-shadow .15s;
}
.sar-stat-card:hover {
    transform:translateY(-2px);
    box-shadow:var(--sar-shadow);
}
.sar-stat-card .stat-value {
    font-size:24px;
    font-weight:700;
}
.sar-stat-card .stat-label {
    font-size:11px;
    color:var(--sar-muted);
    margin-top:2px;
}

.sar-card {
    background:#fff;
    border:1px solid var(--sar-border);
    border-radius:var(--sar-radius);
    box-shadow:var(--sar-shadow);
    overflow:hidden;
}
.sar-card .card-header {
    background:#fff;
    border-bottom:1px solid var(--sar-border);
    padding:16px 20px;
    font-weight:700;
    font-size:14px;
    color:var(--sar-primary);
}

.sar-table th {
    background:var(--sar-primary);
    color:#fff;
    padding:12px 16px;
    font-weight:600;
    font-size:13px;
    border:none;
}
.sar-table td {
    padding:12px 16px;
    vertical-align:middle;
    border-bottom:1px solid var(--sar-border);
    font-size:13px;
}
.sar-table tr:hover td {
    background:#eff6ff;
}

.sar-form-label {
    font-size:13px;
    font-weight:600;
    color:#374151;
    margin-bottom:6px;
}
.sar-form-control {
    border:1.5px solid var(--sar-border);
    border-radius:8px;
    font-size:13px;
    padding:9px 14px;
    transition:border .15s;
}
.sar-form-control:focus {
    border-color:var(--sar-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
    outline:none;
}
.sar-form-control-sm {
    padding:6px 10px;
    border-radius:7px;
}

.sar-badge-outage {
    background: #fef2f2;
    color: #dc2626;
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    display:inline-flex;
    align-items:center;
    gap:6px;
}

.sar-toast {
    position:fixed;
    bottom:20px;
    right:20px;
    z-index:99999;
    padding:14px 20px;
    border-radius:10px;
    background:#fff;
    box-shadow:0 4px 20px rgba(0,0,0,.12);
    font-weight:600;
    font-size:13px;
    animation: sarToastIn .3s ease;
}
@keyframes sarToastIn {
    from { opacity:0; transform:translateY(20px); }
    to { opacity:1; transform:translateY(0); }
}

.sar-exclude-panel {
    display:none;
    background:#f8fafc;
    border:1px dashed var(--sar-border);
    border-radius:8px;
    padding:12px 14px;
    margin-top:10px;
    max-height:160px;
    overflow-y:auto;
}
.sar-exclude-panel.open { display:block; }
.sar-exclude-day {
    display:inline-flex;
    align-items:center;
    gap:5px;
    background:#fff;
    border:1px solid var(--sar-border);
    border-radius:20px;
    padding:4px 10px;
    margin:3px 4px 3px 0;
    font-size:11.5px;
    cursor:pointer;
    user-select:none;
}
.sar-exclude-day input { accent-color:#dc2626; }
.sar-exclude-day.checked { background:#fef2f2; border-color:#fca5a5; color:#dc2626; }

.sar-chart-panel { padding:16px 20px 20px; }

.sar-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 2px solid var(--sar-border);
    flex-shrink: 0;
    object-fit: cover;
}

.sar-weekday-row { display:flex; gap:6px; flex-wrap:wrap; }
.sar-weekday-chip {
    display:inline-flex; align-items:center; gap:5px;
    background:#fff; border:1px solid var(--sar-border);
    border-radius:20px; padding:5px 12px;
    font-size:11.5px; cursor:pointer; user-select:none;
}
.sar-weekday-chip input { accent-color:#dc2626; }
.sar-weekday-chip.checked { background:#fef2f2; border-color:#fca5a5; color:#dc2626; }

.sar-hours-banner {
    background: #eff6ff;
    color: #1e3a5f;
    border-radius: var(--sar-radius);
    padding: 12px 18px;
    font-size: 13px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    border: 1px solid #dbeafe;
}
.sar-hours-banner i { font-size: 20px; }
.sar-hours-banner .badge { font-weight: 600; }
.sar-hours-banner .sar-grace-pill {
    background: #fef3c7;
    color: #92400e;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

    {{-- ══ HERO ═══════════════════════════════════════════════════════════ --}}
    <div class="sar-hero d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4><i class="ri-briefcase-line me-2"></i>Staff Attendance Report</h4>
            <p>Device-driven attendance tracking — no manual entries</p>
        </div>
        <div class="d-flex gap-2">
            @can('View attendance-time-settings')
            <a href="{{ route('staff-attendance.time-settings.edit') }}" class="btn btn-light btn-sm">
                <i class="ri-settings-3-line me-1"></i>Lateness Settings
            </a>
            @endcan
            @php
                $exportParams = array_filter([
                    'date_from'                 => $dateFrom,
                    'date_to'                   => $dateTo,
                    'excluded_dates'             => request('excluded_dates'),
                    'excluded_weekdays'          => request('excluded_weekdays'),
                    'weekday_filter_submitted'   => request('weekday_filter_submitted'),
                ], fn($v) => $v !== null && $v !== '' && $v !== []);
            @endphp
            <a href="{{ route('staff-attendance.export') }}?{{ http_build_query($exportParams) }}"
               class="btn btn-light btn-sm">
                <i class="ri-file-excel-2-line me-1"></i>Export Excel
            </a>
            <span class="badge bg-light text-dark align-self-center">Device-driven</span>
        </div>
    </div>

    {{-- ══ SCHOOL HOURS / LATE EXPECTATION BANNER ═══════════════════════ --}}
    @if(!empty($timeContext))
    <div class="sar-hours-banner">
        <i class="ri-time-line"></i>
        <div class="flex-grow-1">
            <strong>School Hours:</strong>
            Resumption <span class="badge bg-primary">{{ $timeContext['resumption_label'] }}</span>
            &nbsp;·&nbsp;
            Closing <span class="badge bg-secondary">{{ $timeContext['closing_label'] }}</span>
            &nbsp;·&nbsp;
            Staff must clock in by
            <span class="badge bg-dark">{{ $timeContext['expected_by'] }}</span>
            @if($timeContext['grace_minutes'] > 0)
                <span class="sar-grace-pill ms-2">
                    <i class="ri-timer-line me-1"></i>{{ $timeContext['grace_minutes'] }} min grace
                </span>
            @else
                <span class="sar-grace-pill ms-2" style="background:#e2e8f0;color:#475569;">
                    <i class="ri-timer-line me-1"></i>No grace
                </span>
            @endif
            <span class="text-muted ms-2" style="font-size:11.5px;">
                — anyone clocking in after <strong>{{ $timeContext['expected_by'] }}</strong> is marked <em>late</em>.
            </span>
        </div>
    </div>
    @endif

    {{-- ══ FILTER ════════════════════════════════════════════════════════ --}}
    <div class="sar-card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="d-flex align-items-end gap-3 flex-wrap" id="reportFilterForm">
                <input type="hidden" name="weekday_filter_submitted" value="1">
                <div>
                    <label class="sar-form-label mb-1" style="font-size:11px;text-transform:uppercase;">From</label>
                    <input type="date" name="date_from" id="dateFromInput" value="{{ $dateFrom }}" class="sar-form-control sar-form-control-sm" style="width:160px;">
                </div>
                <div>
                    <label class="sar-form-label mb-1" style="font-size:11px;text-transform:uppercase;">To</label>
                    <input type="date" name="date_to" id="dateToInput" value="{{ $dateTo }}" class="sar-form-control sar-form-control-sm" style="width:160px;">
                </div>
                <div>
                    <label class="sar-form-label mb-1" style="font-size:11px;text-transform:uppercase;">Exclude Weekdays</label>
                    @php
                        $weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        $excludedWeekdaysSet = collect($excludedWeekdays ?? [])->map(fn($d) => (int) $d);
                    @endphp
                    <div class="sar-weekday-row">
                        @foreach($weekdayLabels as $dayNum => $dayLabel)
                        <label class="sar-weekday-chip {{ $excludedWeekdaysSet->contains($dayNum) ? 'checked' : '' }}">
                            <input type="checkbox" name="excluded_weekdays[]" value="{{ $dayNum }}"
                                   {{ $excludedWeekdaysSet->contains($dayNum) ? 'checked' : '' }}
                                   onchange="this.closest('label').classList.toggle('checked', this.checked)">
                            {{ $dayLabel }}
                        </label>
                        @endforeach
                    </div>
                </div>
                <button class="btn btn-primary btn-sm"><i class="ri-search-line me-1"></i>Filter</button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="toggleExcludePanel()">
                    <i class="ri-calendar-close-line me-1"></i>Exclude Specific Days
                </button>
                <div class="ms-auto d-flex gap-4">
                    <div class="text-center">
                        <div class="fw-bold fs-5 text-secondary">{{ $workingDays }}</div>
                        <div class="text-muted" style="font-size:11px;">Working Days</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold fs-5 text-{{ $avgPct >= 80 ? 'success' : ($avgPct >= 60 ? 'warning' : 'danger') }}">{{ $avgPct }}%</div>
                        <div class="text-muted" style="font-size:11px;">Staff Avg</div>
                    </div>
                </div>

                <div class="sar-exclude-panel w-100" id="excludePanel">
                    <div class="mb-2" style="font-size:11.5px;color:var(--sar-muted);">
                        Tick any specific dates in this range to leave out of the calculation (e.g. a one-off holiday). For recurring days like every Saturday, use "Exclude Weekdays" above instead.
                    </div>
                    <div id="excludeDayList"></div>
                </div>
            </form>
        </div>
    </div>

    {{-- ══ SUMMARY STATS ═══════════════════════════════════════════════════ --}}
    @php
        $totalStaffCount = $rows->count();
        $sumPresent = $rows->sum('days_present');
        $sumLate    = $rows->sum('days_late');
        $sumExcused = $rows->sum('days_excused');
        $sumAbsent  = $rows->sum('days_absent');
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-lg-2 col-6">
            <div class="sar-stat-card">
                <div class="stat-value text-dark">{{ $totalStaffCount }}</div>
                <div class="stat-label">Staff Tracked</div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="sar-stat-card">
                <div class="stat-value text-success">{{ $sumPresent }}</div>
                <div class="stat-label">Present (days)</div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="sar-stat-card">
                <div class="stat-value text-warning">{{ $sumLate }}</div>
                <div class="stat-label">Late (days)</div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="sar-stat-card">
                <div class="stat-value" style="color:#0ea5e9;">{{ $sumExcused }}</div>
                <div class="stat-label">Excused (days)</div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="sar-stat-card">
                <div class="stat-value text-danger">{{ $sumAbsent }}</div>
                <div class="stat-label">Absent (days)</div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="sar-stat-card">
                <div class="stat-value text-{{ $avgPct >= 80 ? 'success' : ($avgPct >= 60 ? 'warning' : 'danger') }}">{{ $avgPct }}%</div>
                <div class="stat-label">Overall Avg</div>
            </div>
        </div>
    </div>

    {{-- ══ TREND CHART ═══════════════════════════════════════════════════ --}}
    @if(isset($dailyTrend) && count($dailyTrend))
    <div class="sar-card mb-3">
        <div class="card-header">Attendance Trend — {{ $dateFrom }} to {{ $dateTo }}</div>
        <div class="sar-chart-panel">
            <div style="height:240px;"><canvas id="sarTrendChart"></canvas></div>
        </div>
    </div>
    @endif

    {{-- ══ DEPARTMENT BREAKDOWN ═════════════════════════════════════════ --}}
    @if(isset($deptBreakdown) && count($deptBreakdown))
    <div class="sar-card mb-3">
        <div class="card-header">Attendance by Department</div>
        <div class="sar-chart-panel">
            <div style="height:220px;"><canvas id="sarDeptChart"></canvas></div>
        </div>
    </div>
    @endif

    {{-- ══ DEVICE OUTAGE MANAGER ════════════════════════════════════════ --}}
    <div class="sar-card mb-3">
        <div class="card-header">
            <i class="ri-signal-wifi-off-line me-2 text-danger"></i>Device Outage Dates
        </div>
        <div class="card-body">
            <p class="text-muted" style="font-size:12px;">Mark a date as a device outage to exclude it from every staff member's working-day count for this range, so nobody is wrongly marked absent.</p>
            <form id="outageForm" class="d-flex gap-2 align-items-end mb-3">
                <div>
                    <label class="sar-form-label mb-1" style="font-size:11px;">Date</label>
                    <input type="date" name="outage_date" class="sar-form-control sar-form-control-sm" required style="width:160px;">
                </div>
                <div class="flex-grow-1">
                    <label class="sar-form-label mb-1" style="font-size:11px;">Reason</label>
                    <input type="text" name="reason" class="sar-form-control sar-form-control-sm" placeholder="e.g. Power outage, network down…">
                </div>
                @can('Create device-outages')
                <button class="btn btn-outline-danger btn-sm"><i class="ri-add-line me-1"></i>Mark Outage</button>
                @endcan
            </form>

            @if($outages->isNotEmpty())
            <div class="d-flex flex-wrap gap-2">
                @foreach($outages as $o)
                <span class="sar-badge-outage">
                    {{ $o->outage_date->format('d M Y') }}{{ $o->reason ? ' — '.$o->reason : '' }}
                    @can('Delete device-outages')
                    <button onclick="removeOutage({{ $o->id }})" class="btn-close btn-close-sm" style="font-size:8px;"></button>
                    @endcan
                </span>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ══ TABLE ════════════════════════════════════════════════════════ --}}
    <div class="sar-card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1"><i class="ri-briefcase-line me-2 text-primary"></i>Staff</h5>
            <div class="input-group input-group-sm" style="width:220px;">
                <span class="input-group-text"><i class="ri-search-line"></i></span>
                <input type="text" class="form-control" id="srch" placeholder="Search staff…">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table sar-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Staff</th>
                            <th>Department</th>
                            <th class="text-center">Present</th>
                            <th class="text-center">Late</th>
                            <th class="text-center">Excused</th>
                            <th class="text-center">Absent*</th>
                            <th>Attendance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="rows">
                    @php $avatarColors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#30cfd0']; @endphp
                    @forelse($rows as $i => $r)
                        @php
                            $col = $r->attendance_percentage >= 80 ? 'success' : ($r->attendance_percentage >= 60 ? 'warning' : 'danger');
                            $initials = strtoupper(substr($r->full_name,0,1) . (strpos($r->full_name,' ')!==false ? substr($r->full_name, strpos($r->full_name,' ')+1, 1) : ''));
                            $avatarFrom = $avatarColors[$i % count($avatarColors)];
                            $avatarTo   = $avatarColors[($i + 2) % count($avatarColors)];
                        @endphp
                        <tr data-name="{{ strtolower($r->full_name . ' ' . $r->employmentid) }}">
                            <td class="text-muted">{{ $i+1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($r->avatar_url)
                                    <img src="{{ $r->avatar_url }}" class="sar-avatar" alt="{{ $r->full_name }}">
                                    @else
                                    <div class="sar-avatar" style="background:linear-gradient(135deg,{{ $avatarFrom }} 0%,{{ $avatarTo }} 100%)">{{ $initials ?: 'S' }}</div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ $r->full_name }}</div>
                                        <div class="text-muted" style="font-size:11px;">{{ $r->employmentid }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size:12px;">{{ $r->department ?? '—' }}</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $r->days_present }}</span></td>
                            <td class="text-center">
                                @if($r->days_late > 0)
                                    <span class="badge bg-warning-subtle text-warning fw-semibold"
                                          title="Clocked in after {{ $timeContext['expected_by'] ?? '8:00 AM' }} on {{ $r->days_late }} day(s)">
                                        <i class="ri-time-line me-1"></i>{{ $r->days_late }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">0</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $r->days_excused }}</span></td>
                            <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $r->days_absent }}</span></td>
                            <td style="min-width:150px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;">
                                        <div class="progress-bar bg-{{ $col }}" style="width:{{ $r->attendance_percentage }}%"></div>
                                    </div>
                                    <span class="fw-bold text-{{ $col }}" style="font-size:12px;min-width:38px;">{{ $r->attendance_percentage }}%</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $detailParams = array_filter([
                                        'date_from'                 => $dateFrom,
                                        'date_to'                   => $dateTo,
                                        'excluded_dates'             => request('excluded_dates'),
                                        'excluded_weekdays'          => request('excluded_weekdays'),
                                        'weekday_filter_submitted'   => request('weekday_filter_submitted'),
                                    ], fn($v) => $v !== null && $v !== '' && $v !== []);
                                @endphp
                                <a href="{{ route('staff-attendance.report', $r->staff_id) }}?{{ http_build_query($detailParams) }}"
                                   class="btn btn-outline-primary btn-sm" style="font-size:11px;">
                                    Details <i class="ri-arrow-right-line ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-5 text-muted">
                            <i class="ri-inbox-line ri-2x d-block mb-2"></i>No staff records found.
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 text-muted" style="font-size:11px;">
                *Absent is inferred — no device punch recorded for that working day.
                Late means the staff member clocked in after <strong>{{ $timeContext['expected_by'] ?? '8:00 AM' }}</strong>
                @if(($timeContext['grace_minutes'] ?? 0) > 0)
                    (resumption {{ $timeContext['resumption_label'] }} + {{ $timeContext['grace_minutes'] }}-min grace)
                @endif.
            </div>
        </div>
    </div>

</div></div></div>

@include('attendance.partials.live-attendance-toast')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function sarToast(msg, type = 'success') {
    const colors = { success:'#16a34a', danger:'#dc2626', warning:'#d97706', info:'#2563eb' };
    const id = 'sar_toast_' + Date.now();
    document.body.insertAdjacentHTML('beforeend',
        `<div id="${id}" class="sar-toast" style="background:${colors[type] || colors.success};color:#fff;min-width:220px;border-radius:10px;padding:14px 20px;box-shadow:0 4px 20px rgba(0,0,0,.12);font-weight:600;font-size:13px;animation:sarToastIn .3s ease;">
            ${msg}
            <button onclick="document.getElementById('${id}').remove()" style="background:none;border:none;color:#fff;float:right;margin-left:12px;font-size:16px;cursor:pointer;">×</button>
        </div>`
    );
    setTimeout(() => document.getElementById(id)?.remove(), 4500);
}

document.getElementById('srch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#rows tr[data-name]').forEach(r => {
        r.style.display = (!q || r.dataset.name.includes(q)) ? '' : 'none';
    });
});

document.getElementById('outageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('{{ route('staff-attendance.outage.store') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd,
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            sarToast(d.message || 'Outage marked.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(d.message || 'Failed to mark outage.');
        }
    })
    .catch(e => console.error(e));
});

function removeOutage(id) {
    if (!confirm('Remove this outage flag? Absences for that date will be recalculated normally.')) return;
    fetch(`{{ url('attendance/staff/outage') }}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            sarToast(d.message || 'Outage removed.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(d.message || 'Failed to remove outage.');
        }
    })
    .catch(e => console.error(e));
}

/* ── Exclude-days panel ── */
const alreadyExcluded = new Set(@json(request('excluded_dates', [])));

function buildExcludeList() {
    const from = document.getElementById('dateFromInput').value;
    const to   = document.getElementById('dateToInput').value;
    const list = document.getElementById('excludeDayList');
    list.innerHTML = '';

    if (!from || !to) return;

    let cur = new Date(from + 'T00:00:00');
    const end = new Date(to + 'T00:00:00');

    let guard = 0;
    while (cur <= end && guard < 62) {
        const iso = cur.toISOString().slice(0, 10);
        const label = cur.toLocaleDateString(undefined, { weekday:'short', day:'numeric', month:'short' });
        const checked = alreadyExcluded.has(iso);

        const wrap = document.createElement('label');
        wrap.className = 'sar-exclude-day' + (checked ? ' checked' : '');
        wrap.innerHTML = `<input type="checkbox" value="${iso}" ${checked ? 'checked' : ''}> ${label}`;
        wrap.querySelector('input').addEventListener('change', function() {
            wrap.classList.toggle('checked', this.checked);
        });

        list.appendChild(wrap);
        cur.setDate(cur.getDate() + 1);
        guard++;
    }
}

function toggleExcludePanel() {
    const panel = document.getElementById('excludePanel');
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) buildExcludeList();
}

document.getElementById('dateFromInput').addEventListener('change', buildExcludeList);
document.getElementById('dateToInput').addEventListener('change', buildExcludeList);

document.getElementById('reportFilterForm').addEventListener('submit', function() {
    this.querySelectorAll('input[name="excluded_dates[]"]').forEach(el => el.remove());
    document.querySelectorAll('#excludeDayList input:checked').forEach(cb => {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'excluded_dates[]';
        hidden.value = cb.value;
        this.appendChild(hidden);
    });
});

if (alreadyExcluded.size) {
    document.getElementById('excludePanel').classList.add('open');
    buildExcludeList();
}

/* ── Optional charts ── */
@if(isset($dailyTrend) && count($dailyTrend))
new Chart(document.getElementById('sarTrendChart'), {
    type: 'line',
    data: {
        labels: @json(array_column($dailyTrend, 'date')),
        datasets: [{
            label: 'Attendance Rate',
            data: @json(array_column($dailyTrend, 'rate')),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,.08)',
            fill: true, tension: .4, pointRadius: 3,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
    }
});
@endif

@if(isset($deptBreakdown) && count($deptBreakdown))
new Chart(document.getElementById('sarDeptChart'), {
    type: 'bar',
    data: {
        labels: @json(array_column($deptBreakdown, 'department')),
        datasets: [{
            label: 'Avg Attendance',
            data: @json(array_column($deptBreakdown, 'avg')),
            backgroundColor: '#4f5fff',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
    }
});
@endif
</script>
@endsection