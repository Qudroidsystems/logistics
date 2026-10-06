@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Page Title --}}
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Attendance Register</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('attendance.my-classes') }}">Attendance</a></li>
                                <li class="breadcrumb-item active">Register</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Backdating / date-lock banner --}}
            @if(!empty($backdateNote))
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" id="backdateLock">
                <i class="ri-lock-line fs-5"></i>
                <div>
                    <strong>This date is locked for you.</strong>
                    <span class="ms-1" style="font-size:13px;">{{ $backdateNote }}</span>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Prevent marking a locked date: disable inputs/buttons in the register area.
                    document.querySelectorAll('.attendance-register-area button, .attendance-register-area input, .attendance-register-area select, .att-status-btn, #saveAttendanceBtn, #markAllPresentBtn')
                        .forEach(function (el) { el.disabled = true; el.classList.add('disabled'); });
                });
            </script>
            @endif

            {{-- Holiday Banner --}}
            @if($isHoliday)
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                <i class="ri-calendar-close-line fs-5"></i>
                <div>
                    <strong>This date is a holiday or school break.</strong>
                    <span class="ms-1 text-muted" style="font-size:13px;">You can still record attendance if needed, but this day may not count toward school totals.</span>
                </div>
            </div>
            @endif

            {{-- School Hours Banner --}}
            @php
                $resumptionLabel = $setting->resumption_time
                    ? \Illuminate\Support\Carbon::parse($setting->resumption_time)->format('g:i A')
                    : '8:00 AM';
                $closingLabel = $setting->closing_time
                    ? \Illuminate\Support\Carbon::parse($setting->closing_time)->format('g:i A')
                    : '2:00 PM';
                $morningEndLabel = $setting->morning_end_time
                    ? \Illuminate\Support\Carbon::parse($setting->morning_end_time)->format('g:i A')
                    : '12:00 PM';
                $graceMinutes = (int) ($setting->late_grace_minutes ?? 0);
                $expectedBy = $setting->resumption_time
                    ? \Illuminate\Support\Carbon::parse($setting->resumption_time)->addMinutes($graceMinutes)->format('g:i A')
                    : '8:00 AM';
            @endphp
            <div class="school-hours-banner mb-3">
                <i class="ri-time-line"></i>
                <div style="font-size:13px;">
                    <strong>School Hours:</strong>
                    Resumption <span class="badge bg-primary">{{ $resumptionLabel }}</span>
                    &nbsp;·&nbsp;
                    Closing <span class="badge bg-secondary">{{ $closingLabel }}</span>
                    @if($setting->track_afternoon)
                        &nbsp;·&nbsp;
                        Afternoon starts <span class="badge bg-info text-dark">{{ $morningEndLabel }}</span>
                    @endif
                    @if($graceMinutes > 0)
                        &nbsp;·&nbsp;
                        <span class="text-muted">Grace: {{ $graceMinutes }} min</span>
                    @endif
                    @if($setting->track_morning)
                        &nbsp;·&nbsp;
                        <span class="text-muted" style="font-size:11.5px;">
                            Morning punches after <strong>{{ $expectedBy }}</strong> are marked <em>late</em>.
                        </span>
                    @endif
                </div>
            </div>

            {{-- Header Info --}}
            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm mb-0" style="border-left: 4px solid #2563eb !important;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-3 fs-4">
                                        <i class="ri-home-3-line"></i>
                                    </span>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1">{{ $schoolclass->schoolclass }} {{ $schoolclass->arms?->arm }}</h5>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge bg-primary-subtle text-primary">{{ $term->term }}</span>
                                        <span class="badge bg-info-subtle text-info">{{ $session->session }}</span>
                                        @if($isHoliday)
                                            <span class="badge bg-danger-subtle text-danger">⛔ Holiday</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="card border text-center mb-0 py-2">
                                <div class="fw-bold fs-4 text-primary" id="stat-total">{{ $students->count() }}</div>
                                <div class="text-muted" style="font-size:11px;text-transform:uppercase;">Total</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border text-center mb-0 py-2">
                                <div class="fw-bold fs-4 text-success" id="stat-present">0</div>
                                <div class="text-muted" style="font-size:11px;text-transform:uppercase;">Present</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border text-center mb-0 py-2">
                                <div class="fw-bold fs-4 text-danger" id="stat-absent">0</div>
                                <div class="text-muted" style="font-size:11px;text-transform:uppercase;">Absent</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- More stats row --}}
            <div class="row g-2 mb-3">
                <div class="col-3 col-md-2">
                    <div class="card border text-center mb-0 py-2">
                        <div class="fw-bold fs-5 text-warning" id="stat-sick">0</div>
                        <div class="text-muted" style="font-size:11px;">Sick</div>
                    </div>
                </div>
                <div class="col-3 col-md-2">
                    <div class="card border text-center mb-0 py-2">
                        <div class="fw-bold fs-5" style="color:#7c3aed;" id="stat-excused">0</div>
                        <div class="text-muted" style="font-size:11px;">Excused</div>
                    </div>
                </div>
                <div class="col-3 col-md-2">
                    <div class="card border text-center mb-0 py-2">
                        <div class="fw-bold fs-5" style="color:#ea580c;" id="stat-late">0</div>
                        <div class="text-muted" style="font-size:11px;">Late</div>
                    </div>
                </div>
                <div class="col-3 col-md-2">
                    <div class="card border text-center mb-0 py-2">
                        <div class="fw-bold fs-5 text-secondary" id="stat-unmarked">{{ $students->count() }}</div>
                        <div class="text-muted" style="font-size:11px;">Unmarked</div>
                    </div>
                </div>
            </div>

            {{-- Controls --}}
            <div class="card shadow-sm mb-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        {{-- Date --}}
                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Date</label>
                            <input type="date" id="dateInput" class="form-control form-control-sm" value="{{ $date }}"
                                   min="{{ $setting->resumption_date->toDateString() }}"
                                   max="{{ $setting->vacation_date->toDateString() }}"
                                   style="width:160px;">
                        </div>

                        {{-- Period --}}
                        @if($setting->track_morning && $setting->track_afternoon)
                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Period</label>
                            <select id="periodSelect" class="form-select form-select-sm" style="width:140px;">
                                <option value="morning"   {{ $period === 'morning'   ? 'selected' : '' }}>🌅 Morning</option>
                                <option value="afternoon" {{ $period === 'afternoon' ? 'selected' : '' }}>🌇 Afternoon</option>
                            </select>
                            <div class="text-muted mt-1" style="font-size:10.5px;line-height:1.3;">
                                Morning: {{ $resumptionLabel }} – {{ $morningEndLabel }}
                                <br>Afternoon: {{ $morningEndLabel }} – {{ $closingLabel }}
                            </div>
                        </div>
                        @else
                        <input type="hidden" id="periodSelect" value="{{ $period }}">
                        <div class="align-self-end">
                            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                                {{ ucfirst($period) }} Session
                                <span class="ms-1" style="opacity:.75;">
                                    ({{ $period === 'afternoon'
                                        ? $morningEndLabel . ' – ' . $closingLabel
                                        : $resumptionLabel . ' – ' . $morningEndLabel }})
                                </span>
                            </span>
                        </div>
                        @endif

                        {{-- Search --}}
                        <div class="flex-grow-1" style="max-width:280px;">
                            <label class="form-label fw-semibold mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Search</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ri-search-line"></i></span>
                                <input type="text" id="searchStudents" class="form-control" placeholder="Search students…">
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex gap-2 align-items-end ms-auto flex-wrap">
                            <button class="btn btn-success btn-sm" id="markAllPresentBtn">
                                <i class="ri-check-double-line me-1"></i>All Present
                            </button>
                            <button class="btn btn-outline-danger btn-sm" id="markAllAbsentBtn">
                                <i class="ri-close-line me-1"></i>All Absent
                            </button>
                            @can('View attendance-class-summary')
                            <a href="{{ route('attendance.class-summary', [$classId, $termId, $sessionId]) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ri-bar-chart-2-line me-1"></i>Summary
                            </a>
                            @endcan
                            <a href="{{ route('attendance.my-classes') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Table --}}
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center py-3">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <i class="ri-file-list-3-line me-2 text-primary"></i>
                        Attendance Register
                        <span class="badge bg-dark-subtle text-dark ms-1">{{ $students->count() }}</span>
                    </h5>
                    <small class="text-muted"><i class="ri-keyboard-line me-1"></i>Ctrl+S to save all</small>
                </div>
                <div class="card-body p-0">

                    <div id="progressContainer" style="display:none;" class="px-3 pt-3">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-1" style="font-size:13px;">Saving attendance…</div>
                                <div class="progress" style="height:5px;">
                                    <div class="progress-bar progress-bar-animated bg-primary" id="saveProgressBar" style="width:0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0" id="attendanceTable">
                            <thead style="background:#1e3a5f;">
                                <tr>
                                    <th class="text-white" style="width:44px;">#</th>
                                    <th class="text-white">Student</th>
                                    <th class="text-white">Status</th>
                                    <th class="text-white">Notes</th>
                                    <th class="text-white">Term Summary</th>
                                </tr>
                            </thead>
                            <tbody id="studentRows">
                            @php $idx = 0; @endphp
                            @foreach($students as $student)
                                @php
                                    $idx++;
                                    $currentStatus = $existing[$student->id] ?? null;
                                    $sum = $summaries[$student->id] ?? null;
                                    $pct = $sum ? (float) $sum->attendance_percentage : 0;
                                    $pctColor = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                                    $img = $student->picture
                                        ? asset('storage/student_avatars/' . basename($student->picture))
                                        : asset('storage/student_avatars/unnamed.jpg');
                                @endphp
                                <tr data-student-id="{{ $student->id }}"
                                    data-name="{{ strtolower($student->lname . ' ' . $student->fname . ' ' . ($student->mname ?? '') . ' ' . $student->admissionno) }}">
                                    <td class="text-muted fw-medium">{{ $idx }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $img }}" class="rounded-circle"
                                                 style="width:36px;height:36px;object-fit:cover;border:2px solid #e2e8f0;"
                                                 onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'">
                                            <div>
                                                <div class="fw-semibold" style="font-size:13px;">{{ $student->lname }} {{ $student->fname }}</div>
                                                <div class="text-muted" style="font-size:11px;">{{ $student->admissionno }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap status-group" data-student="{{ $student->id }}">
                                            @foreach(['present' => ['ri-check-line','Present','btn-outline-success','btn-success'], 'absent' => ['ri-close-line','Absent','btn-outline-danger','btn-danger'], 'sick_leave' => ['ri-heart-pulse-line','Sick','btn-outline-warning','btn-warning'], 'excused' => ['ri-file-text-line','Excused','btn-outline-info','btn-info'], 'late' => ['ri-time-line','Late','btn-outline-secondary','btn-secondary']] as $val => [$icon, $lbl, $outlineClass, $solidClass])
                                            <button type="button"
                                                    class="btn btn-sm att-status-btn {{ $currentStatus === $val ? $solidClass . ' text-white' : $outlineClass }}"
                                                    data-status="{{ $val }}"
                                                    data-student="{{ $student->id }}"
                                                    data-outline="{{ $outlineClass }}"
                                                    data-solid="{{ $solidClass }}"
                                                    onclick="toggleStatus(this, {{ $student->id }})">
                                                <i class="{{ $icon }} me-1"></i>{{ $lbl }}
                                            </button>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm notes-inp"
                                               data-student="{{ $student->id }}"
                                               placeholder="Optional note…" maxlength="200"
                                               style="width:150px;">
                                    </td>
                                    <td>
                                        @if($sum)
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge bg-success-subtle text-success" title="Present">P:{{ $sum->days_present }}</span>
                                            <span class="badge bg-danger-subtle text-danger" title="Absent">A:{{ $sum->days_absent }}</span>
                                            <span class="badge bg-warning-subtle text-warning" title="Sick">S:{{ $sum->days_sick_leave }}</span>
                                            <span class="badge bg-info-subtle text-info" title="Excused">E:{{ $sum->days_excused }}</span>
                                            <span class="badge bg-secondary-subtle text-secondary" title="Late">L:{{ $sum->days_late }}</span>
                                            <span class="badge bg-{{ $pctColor }}-subtle text-{{ $pctColor }} fw-bold">{{ $pct }}%</span>
                                        </div>
                                        @can('View attendance-student-report')
                                        <div class="mt-1">
                                            <a href="{{ route('attendance.student-report', [$student->id, $classId, $termId, $sessionId]) }}"
                                               class="text-primary" style="font-size:11px;">View report →</a>
                                        </div>
                                        @endcan
                                        @else
                                        <span class="text-muted" style="font-size:12px;">No data yet</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 border-top bg-light">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="text-muted" style="font-size:13px;">
                                <strong id="markedCount">0</strong> / {{ $students->count() }} marked
                                &nbsp;·&nbsp; <span id="currentDateLabel">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</span>
                                &nbsp;·&nbsp; <span class="text-capitalize">{{ $period }}</span>
                            </div>
                            @can('Create attendance-register')
                            <button class="btn btn-success px-4" id="saveAllBtn">
                                <i class="ri-save-line me-1"></i>Save Attendance
                            </button>
                            @endcan
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<div id="att-toast" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;"></div>

<script>
const STATE = {
    classId  : {{ $classId }},
    termId   : {{ $termId }},
    sessionId: {{ $sessionId }},
    records  : {},
    total    : {{ $students->count() }},
};

@foreach($existing as $sid => $st)
STATE.records[{{ $sid }}] = { status: '{{ $st }}', notes: '' };
@endforeach

document.addEventListener('DOMContentLoaded', () => {
    refreshStats();

    document.getElementById('dateInput').addEventListener('change', reloadPage);
    const ps = document.getElementById('periodSelect');
    if (ps && ps.tagName === 'SELECT') ps.addEventListener('change', reloadPage);

    document.getElementById('searchStudents').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#studentRows tr').forEach(r => {
            r.style.display = (!q || r.dataset.name.includes(q)) ? '' : 'none';
        });
    });

    document.getElementById('markAllPresentBtn').addEventListener('click', () => {
        document.querySelectorAll('#studentRows tr').forEach(r => applyStatus(parseInt(r.dataset.studentId), 'present'));
        refreshStats();
    });

    document.getElementById('markAllAbsentBtn').addEventListener('click', () => {
        document.querySelectorAll('#studentRows tr').forEach(r => applyStatus(parseInt(r.dataset.studentId), 'absent'));
        refreshStats();
    });

    document.getElementById('saveAllBtn')?.addEventListener('click', saveAll);
    document.addEventListener('keydown', e => { if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); saveAll(); } });
});

function reloadPage() {
    const d = document.getElementById('dateInput').value;
    const p = document.getElementById('periodSelect').value || '{{ $period }}';
    window.location.href = `{{ route('attendance.register', [$classId, $termId, $sessionId]) }}?date=${d}&period=${p}`;
}

function toggleStatus(btn, studentId) {
    const status = btn.dataset.status;
    applyStatus(studentId, status);
    refreshStats();
    const notes = document.querySelector(`.notes-inp[data-student="${studentId}"]`)?.value || '';
    saveSingle(studentId, status, notes);
}

function applyStatus(studentId, status) {
    const group = document.querySelector(`.status-group[data-student="${studentId}"]`);
    if (!group) return;
    group.querySelectorAll('.att-status-btn').forEach(b => {
        const isActive = b.dataset.status === status;
        b.className = 'btn btn-sm att-status-btn ' + (isActive ? b.dataset.solid + ' text-white' : b.dataset.outline);
    });
    const notes = document.querySelector(`.notes-inp[data-student="${studentId}"]`)?.value || '';
    STATE.records[studentId] = { status, notes };
}

function refreshStats() {
    const counts = { present:0, absent:0, sick_leave:0, excused:0, late:0 };
    let marked = 0;
    Object.values(STATE.records).forEach(r => {
        if (r.status) { counts[r.status] = (counts[r.status]||0)+1; marked++; }
    });
    document.getElementById('stat-present').textContent = counts.present;
    document.getElementById('stat-absent').textContent  = counts.absent;
    document.getElementById('stat-sick').textContent    = counts.sick_leave;
    document.getElementById('stat-excused').textContent = counts.excused;
    document.getElementById('stat-late').textContent    = counts.late;
    document.getElementById('markedCount').textContent  = marked;
    document.getElementById('stat-unmarked').textContent= STATE.total - marked;
}

function saveSingle(studentId, status, notes) {
    const date   = document.getElementById('dateInput').value;
    const period = document.getElementById('periodSelect').value || '{{ $period }}';
    fetch('{{ route('attendance.save-single') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify({ student_id: studentId, schoolclass_id: STATE.classId, term_id: STATE.termId, session_id: STATE.sessionId, attendance_date: date, period, status, notes }),
    }).then(r => r.json()).then(d => {
        if (d.success && d.summary) updateSummaryCell(studentId, d.summary);
    }).catch(() => {});
}

function updateSummaryCell(studentId, sum) {
    const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
    if (!row) return;
    const pct = parseFloat(sum.attendance_percentage || 0);
    const col = pct >= 80 ? 'success' : (pct >= 60 ? 'warning' : 'danger');
    const cell = row.cells[4];
    const bar = cell.querySelector('.d-flex');
    if (bar) bar.innerHTML =
        `<span class="badge bg-success-subtle text-success">P:${sum.days_present}</span>
         <span class="badge bg-danger-subtle text-danger">A:${sum.days_absent}</span>
         <span class="badge bg-warning-subtle text-warning">S:${sum.days_sick_leave}</span>
         <span class="badge bg-info-subtle text-info">E:${sum.days_excused}</span>
         <span class="badge bg-secondary-subtle text-secondary">L:${sum.days_late}</span>
         <span class="badge bg-${col}-subtle text-${col} fw-bold">${pct}%</span>`;
}

function saveAll() {
    const date   = document.getElementById('dateInput').value;
    const period = document.getElementById('periodSelect').value || '{{ $period }}';
    const records = [];
    document.querySelectorAll('#studentRows tr').forEach(row => {
        const sid    = parseInt(row.dataset.studentId);
        const notes  = row.querySelector('.notes-inp')?.value || '';
        const active = row.querySelector('.att-status-btn.text-white');
        if (active) { records.push({ student_id: sid, status: active.dataset.status, notes }); STATE.records[sid] = { status: active.dataset.status, notes }; }
    });
    if (!records.length) { showToast('No attendance marked yet.', 'warning'); return; }

    const btn  = document.getElementById('saveAllBtn');
    const prog = document.getElementById('progressContainer');
    const bar  = document.getElementById('saveProgressBar');
    if (btn) btn.disabled = true;
    if (prog) prog.style.display = 'block';
    let w = 0; const iv = setInterval(() => { w = Math.min(w+12,90); if(bar) bar.style.width=w+'%'; }, 120);

    fetch('{{ route('attendance.save') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify({ schoolclass_id: STATE.classId, term_id: STATE.termId, session_id: STATE.sessionId, attendance_date: date, period, records }),
    }).then(r => r.json()).then(d => {
        clearInterval(iv); if(prog) prog.style.display='none'; if(bar) bar.style.width='100%';
        if(btn) btn.disabled = false;
        showToast(d.message, d.success ? 'success' : 'danger');
    }).catch(() => {
        clearInterval(iv); if(prog) prog.style.display='none'; if(btn) btn.disabled=false;
        showToast('Network error.', 'danger');
    });
}

function csrfToken() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; }

function showToast(msg, type = 'success') {
    const colors = { success:'#16a34a', danger:'#dc2626', warning:'#d97706', info:'#2563eb' };
    const id = 'toast_' + Date.now();
    document.body.insertAdjacentHTML('beforeend',
        `<div id="${id}" style="position:fixed;bottom:20px;right:20px;z-index:9999;background:${colors[type]||colors.info};color:#fff;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;animation:fadeIn .3s ease;min-width:220px;">
            ${msg}
         </div>`);
    setTimeout(() => document.getElementById(id)?.remove(), 3500);
}

refreshStats();
</script>
<style>
@keyframes fadeIn { from{transform:translateY(10px);opacity:0} to{transform:translateY(0);opacity:1} }
.att-status-btn { font-size: 12px; padding: 4px 8px; white-space: nowrap; }

.school-hours-banner {
    background: #eff6ff;
    color: #1e3a5f;
    border-radius: 10px;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.school-hours-banner i { font-size: 20px; }
.school-hours-banner .badge { font-weight: 600; }
</style>
@endsection