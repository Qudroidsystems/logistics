@extends('layouts.master')

@section('content')
<style>
:root {
    --tt-navy: #0f2342; --tt-teal: #0d9488; --tt-sky: #0ea5e9;
    --tt-muted: #64748b; --tt-border: #e2e8f0; --tt-radius: 14px;
    --tt-shadow: 0 4px 16px rgba(15,35,66,.10);
}
.ttw-hero {
    background: linear-gradient(135deg, var(--tt-navy) 0%, #1e4a7e 55%, #0d9488 100%);
    border-radius: var(--tt-radius); padding: 28px 32px; margin-bottom: 24px; color:#fff;
    display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;
}
.ttw-hero h1 { font-size:22px; font-weight:700; margin:0 0 6px; }
.ttw-hero p  { font-size:13px; opacity:.75; margin:0; }
.ttw-hero .pills { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
.ttw-pill { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); border-radius:20px; padding:4px 12px; font-size:12px; font-weight:600; }
.ttw-print-btn { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3); color:#fff; border-radius:10px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; }
.ttw-print-btn:hover { background:rgba(255,255,255,.28); }

/* ── Run metadata block (web) ── */
.run-meta-block {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-left: 4px solid #1565C0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
}
.run-meta-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 12px;
    flex-wrap: wrap;
}
.run-meta-name {
    font-size: 14px;
    color: #0f2342;
}
.run-meta-code {
    color: #64748B;
    margin-left: 8px;
    font-family: monospace;
    font-size: 12px;
    letter-spacing: 0.5px;
}
.run-meta-right {
    color: #64748B;
    font-size: 12px;
}
.run-meta-desc {
    margin-top: 6px;
    color: #475569;
    font-size: 13px;
    line-height: 1.4;
}
.run-rules-block {
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: 10px;
    padding: 10px 16px;
    margin-bottom: 16px;
    font-size: 12.5px;
    color: #92400E;
}
.run-rule-item {
    display: inline-block;
    margin-left: 10px;
    margin-right: 2px;
    white-space: nowrap;
}

.ttw-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
.ttw-stat { background:#fff; border:1px solid var(--tt-border); border-radius:var(--tt-radius); box-shadow:var(--tt-shadow); padding:16px 18px; }
.ttw-stat .v { font-size:26px; font-weight:700; color:var(--tt-navy); }
.ttw-stat .l { font-size:11px; color:var(--tt-muted); text-transform:uppercase; margin-top:4px; }

.ttw-toolbar { display:flex; gap:12px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
.ttw-search { position:relative; flex:1; min-width:220px; max-width:340px; }
.ttw-search input { width:100%; padding:9px 14px 9px 36px; border:1.5px solid var(--tt-border); border-radius:10px; font-size:13px; }
.ttw-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--tt-muted); }

.ttw-staff-card { background:#fff; border:1px solid var(--tt-border); border-radius:var(--tt-radius); box-shadow:var(--tt-shadow); padding:18px 20px; margin-bottom:22px; }
.ttw-staff-card h5 { font-size:14px; font-weight:700; color:var(--tt-navy); margin:0 0 12px; display:flex; align-items:center; gap:8px; }
.ttw-staff-filter { display:flex; gap:10px; align-items:center; margin-bottom:14px; flex-wrap:wrap; }
.ttw-staff-filter label { font-size:12px; font-weight:700; color:var(--tt-navy); }
.ttw-staff-filter select { border:1.5px solid var(--tt-border); border-radius:10px; padding:8px 12px; font-size:13px; min-width:260px; }
.ttw-staff-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:14px; }
.ttw-staff-summary .mini { background:#F8FAFC; border:1px solid var(--tt-border); border-radius:10px; padding:10px 12px; }
.ttw-staff-summary .mini .v { font-size:20px; font-weight:700; color:var(--tt-navy); }
.ttw-staff-summary .mini .l { font-size:10.5px; color:#94a3b8; text-transform:uppercase; margin-top:2px; }

#ttwStaffDetail { display:none; }
#ttwStaffDetail.active { display:block; }
.ttw-freq-list { display:flex; flex-direction:column; gap:6px; }
.ttw-freq-row { display:flex; align-items:center; gap:10px; font-size:12.5px; }
.ttw-freq-row .name { flex:1; color:#334155; }
.ttw-freq-bar-wrap { flex:2; background:#eef2f7; border-radius:6px; height:8px; overflow:hidden; }
.ttw-freq-bar { height:100%; background:linear-gradient(90deg,#0d9488,#0ea5e9); border-radius:6px; }
.ttw-freq-count { font-weight:700; color:var(--tt-navy); min-width:22px; text-align:right; }
.ttw-staff-cols { display:grid; grid-template-columns:1fr 1fr; gap:22px; }
@media (max-width: 768px) { .ttw-staff-cols { grid-template-columns:1fr; } }
.ttw-daily-load { display:flex; gap:6px; margin-top:4px; }
.ttw-daily-chip { flex:1; text-align:center; border-radius:8px; padding:6px 4px; background:#F8FAFC; border:1px solid var(--tt-border); }
.ttw-daily-chip .d { font-size:9.5px; color:#94a3b8; text-transform:uppercase; }
.ttw-daily-chip .n { font-size:15px; font-weight:700; color:var(--tt-navy); }
.ttw-daily-chip.busiest { background:#FFFBEB; border-color:#fbbf24; }
.ttw-conflict-banner { background:#FEF2F2; border:1px solid #fecaca; color:#b91c1c; border-radius:10px; padding:8px 14px; font-size:12.5px; font-weight:600; margin-top:10px; }

.ttw-board { width:100%; border-collapse:collapse; font-size:12.5px; }
.ttw-board th { text-align:left; padding:8px 10px; font-size:10.5px; text-transform:uppercase; color:#94a3b8; border-bottom:2px solid var(--tt-border); }
.ttw-board td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.ttw-board tr.ttw-board-row { cursor:pointer; }
.ttw-board tr.ttw-board-row:hover { background:#F8FAFC; }
.ttw-board tr.ttw-board-selected { background:#EFF6FF; }
.ttw-board .conflict-cell { color:#dc2626; font-weight:700; }

.ttw-class-card { background:#fff; border:1px solid var(--tt-border); border-radius:var(--tt-radius); box-shadow:var(--tt-shadow); margin-bottom:22px; overflow:hidden; }
.ttw-class-card .hdr { background:linear-gradient(135deg,#1565C0,#0d9488); color:#fff; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.ttw-class-card .hdr h5 { margin:0; font-size:15px; font-weight:700; }
.ttw-class-card .hdr .badges { display:flex; gap:6px; flex-wrap:wrap; }
.ttw-badge { background:rgba(255,255,255,.18); border-radius:14px; padding:3px 10px; font-size:11px; font-weight:600; }

table.ttw-grid { width:100%; border-collapse:collapse; font-size:12px; }
table.ttw-grid th { background:#1E293B; color:#fff; padding:8px 6px; text-align:center; font-size:11px; text-transform:uppercase; }
table.ttw-grid td { border:1px solid var(--tt-border); padding:6px; text-align:center; vertical-align:middle; transition:box-shadow .15s ease, opacity .15s ease; }
table.ttw-grid td.period-col { background:#F8FAFC; text-align:left; font-weight:700; white-space:nowrap; }
table.ttw-grid.is-vertical td.period-col { white-space: nowrap; min-width: 90px; color: #fff; }
.ttw-subject { font-weight:700; font-size:12px; color:var(--tt-navy); }
.ttw-teacher { font-size:10.5px; color:#475569; }
.ttw-room { font-size:10px; color:#94a3b8; }
.ttw-free { color:#cbd5e1; font-size:11px; }
.ttw-break { background:#FFFBEB; color:#d97706; font-weight:700; font-size:11px; }
td.ttw-staff-highlight { box-shadow: inset 0 0 0 2px #dc2626; background:#FEF2F2; }
td.ttw-dimmed { opacity:.25; }

@media print {
    .no-print { display:none !important; }
    .ttw-class-card { box-shadow:none; page-break-inside: avoid; margin-bottom: 14px; }
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

<div class="ttw-hero">
    <div>
        <h1><i class="ri-school-line me-2"></i>{{ $schoolInfo->school_name ?? 'School' }} — Whole School Timetable</h1>
        <p>{{ $sessionName }} · {{ $termName }} · Generated {{ $generatedAt }}</p>
        <div class="pills">
            <span class="ttw-pill"><i class="ri-building-line me-1"></i>{{ $overallStats['total_classes'] ?? 0 }} classes</span>
            <span class="ttw-pill"><i class="ri-user-line me-1"></i>{{ $overallStats['total_teachers'] ?? 0 }} teachers involved</span>
            <span class="ttw-pill"><i class="ri-layout-column-line me-1"></i>{{ ucfirst($orientation ?? 'horizontal') }} layout</span>
        </div>
    </div>
    <button class="ttw-print-btn no-print" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print / Save PDF</button>
</div>

{{-- Run metadata block — only present when exporting a saved generation run. --}}
@if(!empty($runMeta))
<div class="run-meta-block">
    <div class="run-meta-header">
        <div>
            <strong class="run-meta-name">{{ $runMeta['name'] }}</strong>
            <span class="run-meta-code">Run {{ $runMeta['run_code'] }}</span>
        </div>
        <div class="run-meta-right">
            {{ $runMeta['creator'] }} · {{ $runMeta['created_at'] }}
            @if($runMeta['seed']) · seed {{ $runMeta['seed'] }} @endif
        </div>
    </div>
    @if(!empty($runMeta['description']))
        <div class="run-meta-desc">{{ $runMeta['description'] }}</div>
    @endif
</div>
@endif

{{-- Advanced-rules appendix — only when the caller requested it. --}}
@if(!empty($runRules))
<div class="run-rules-block">
    <strong>Generation rules used:</strong>
    @foreach($runRules as $key => $value)
        <span class="run-rule-item">
            {{ $key }} =
            {{ is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'true' : 'false') : $value) }}
        </span>
    @endforeach
</div>
@endif

<div class="ttw-stats">
    <div class="ttw-stat"><div class="v">{{ $overallStats['total_classes'] ?? 0 }}</div><div class="l">Classes</div></div>
    <div class="ttw-stat"><div class="v">{{ $overallStats['total_teachers'] ?? 0 }}</div><div class="l">Teachers Involved</div></div>
    <div class="ttw-stat"><div class="v">{{ $overallStats['avg_fill_rate'] ?? 0 }}%</div><div class="l">Avg Fill Rate</div></div>
    <div class="ttw-stat">
        <div class="v" style="color:{{ ($overallStats['total_conflicts'] ?? 0) > 0 ? '#dc2626' : '#16a34a' }}">
            {{ $overallStats['total_conflicts'] ?? 0 }}
        </div>
        <div class="l">Conflicts Detected</div>
    </div>
</div>

<div class="ttw-staff-card no-print">
    <h5><i class="ri-bar-chart-grouped-line"></i>Staff Analysis</h5>

    <div class="ttw-staff-filter">
        <label for="ttwStaffFilter">Select staff:</label>
        <select id="ttwStaffFilter" onchange="ttwStaffSelect(this.value)">
            <option value="">— Show all classes (no staff selected) —</option>
            @foreach(($staffAnalytics['staff'] ?? []) as $s)
                <option value="{{ $s['id'] }}">{{ $s['name'] }} ({{ $s['total_periods'] }} periods{{ $s['conflict_count'] > 0 ? ', ' . $s['conflict_count'] . ' conflict(s)' : '' }})</option>
            @endforeach
        </select>
    </div>

    @if(!empty($staffAnalytics['summary']))
    <div class="ttw-staff-summary">
        <div class="mini"><div class="v">{{ $staffAnalytics['summary']['total_staff'] ?? 0 }}</div><div class="l">Staff Scheduled</div></div>
        <div class="mini"><div class="v">{{ $staffAnalytics['summary']['avg_periods_per_staff'] ?? 0 }}</div><div class="l">Avg Periods / Staff</div></div>
        <div class="mini"><div class="v">{{ $staffAnalytics['summary']['busiest_staff']['name'] ?? '—' }}</div><div class="l">Busiest ({{ $staffAnalytics['summary']['busiest_staff']['periods'] ?? 0 }} periods)</div></div>
        <div class="mini"><div class="v" style="color:{{ ($staffAnalytics['summary']['most_conflicted_staff']['conflicts'] ?? 0) > 0 ? '#dc2626' : '#16a34a' }}">
            {{ $staffAnalytics['summary']['most_conflicted_staff']['name'] ?? 'None' }}
        </div><div class="l">Most Conflicted{{ isset($staffAnalytics['summary']['most_conflicted_staff']) ? ' (' . $staffAnalytics['summary']['most_conflicted_staff']['conflicts'] . ')' : '' }}</div></div>
    </div>
    @endif

    <div id="ttwStaffDetail">
        <div class="ttw-staff-cols">
            <div>
                <h6 style="font-size:12.5px;font-weight:700;color:#334155;">Classes &amp; Arms (frequency)</h6>
                <div class="ttw-freq-list" id="ttwStaffClasses"></div>
            </div>
            <div>
                <h6 style="font-size:12.5px;font-weight:700;color:#334155;">Subjects Taught (frequency)</h6>
                <div class="ttw-freq-list" id="ttwStaffSubjects"></div>
            </div>
        </div>
        <h6 style="font-size:12.5px;font-weight:700;color:#334155;margin-top:16px;">Daily Load</h6>
        <div class="ttw-daily-load" id="ttwStaffDailyLoad"></div>
        <div id="ttwStaffConflictBanner"></div>
    </div>

    <h6 style="font-size:12.5px;font-weight:700;color:#334155;margin-top:18px;">All Staff — Workload Leaderboard</h6>
    <div style="overflow-x:auto;">
        <table class="ttw-board">
            <thead>
                <tr>
                    <th>Staff</th><th>Total Periods</th><th>Classes</th>
                    <th>Subjects</th><th>Busiest Day</th><th>Conflicts</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($staffAnalytics['staff'] ?? []) as $s)
                    <tr class="ttw-board-row" data-staff-id="{{ $s['id'] }}" onclick="ttwStaffSelect('{{ $s['id'] }}')">
                        <td>{{ $s['name'] }}</td>
                        <td>{{ $s['total_periods'] }}</td>
                        <td>{{ $s['class_count'] }}</td>
                        <td>{{ $s['subject_count'] }}</td>
                        <td>{{ $s['busiest_day'] ?? '—' }}</td>
                        <td class="{{ $s['conflict_count'] > 0 ? 'conflict-cell' : '' }}">{{ $s['conflict_count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center py-3">No staff scheduled in this scope yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ttw-toolbar no-print">
    <div class="ttw-search">
        <i class="ri-search-line"></i>
        <input type="text" id="ttwSearch" placeholder="Filter by class name…">
    </div>
</div>

@php $isVertical = ($orientation ?? 'horizontal') === 'vertical'; @endphp

<div id="ttwClassList">
@foreach ($allTimetables as $tt)
    <div class="ttw-class-card" data-class-name="{{ strtolower($tt['class_name']) }}">
        <div class="hdr">
            <h5><i class="ri-team-line me-1"></i>{{ $tt['class_name'] }}</h5>
            <div class="badges">
                <span class="ttw-badge">{{ $tt['stats']['filled_slots'] }}/{{ $tt['stats']['total_slots'] }} filled ({{ $tt['stats']['fill_rate'] }}%)</span>
                <span class="ttw-badge">{{ $tt['stats']['subject_count'] }} subjects</span>
                <span class="ttw-badge">{{ $tt['stats']['teacher_count'] }} teachers</span>
                @if($tt['stats']['room_count'] > 0)
                    <span class="ttw-badge">{{ $tt['stats']['room_count'] }} rooms</span>
                @endif
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="ttw-grid {{ $isVertical ? 'is-vertical' : '' }}">
                <thead>
                    <tr>
                        @if ($isVertical)
                            <th style="background:#0f2342;">Day</th>
                            @foreach ($tt['periods'] as $period)
                                <th style="background:#1565C0;">
                                    {{ $period->name }}<br>
                                    <span style="font-weight:normal;font-size:10px;">
                                        {{ substr($period->start_time,0,5) }}–{{ substr($period->end_time,0,5) }}
                                    </span>
                                </th>
                            @endforeach
                        @else
                            <th style="background:#0f2342;">Period</th>
                            @foreach ($tt['days'] as $day)
                                <th style="background:{{ $dayColors[$day] ?? '#1565C0' }}">{{ $day }}</th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if ($isVertical)
                        @foreach ($tt['days'] as $day)
                            <tr>
                                <td class="period-col" style="background:{{ $dayColors[$day] ?? '#0f2342' }};color:#fff;">
                                    {{ $day }}
                                </td>
                                @foreach ($tt['periods'] as $period)
                                    @php
                                        $meta    = $tt['day_meta'][$day][$period->id] ?? null;
                                        $isBreak = in_array($period->type, ['short_break','long_break','assembly'])
                                                   && ($meta['effective_type'] ?? $period->type) !== 'lesson';
                                        $slot    = $tt['grid'][$period->id][$day] ?? null;
                                    @endphp
                                    @if ($isBreak)
                                        <td class="ttw-break">{{ ucfirst(str_replace('_',' ',$period->type)) }}</td>
                                    @elseif (!$meta || !($meta['applicable'] ?? true))
                                        <td>—</td>
                                    @elseif (!$slot || $slot['is_free'])
                                        <td class="ttw-free">Free</td>
                                    @else
                                        <td data-teacher-id="{{ $slot['teacher_id'] ?? '' }}">
                                            <div class="ttw-subject">{{ $slot['subject'] }}</div>
                                            @if($slot['teacher'])<div class="ttw-teacher">{{ $slot['teacher'] }}</div>@endif
                                            @if($slot['room'])<div class="ttw-room">{{ $slot['room'] }}</div>@endif
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    @else
                        @foreach ($tt['periods'] as $period)
                        <tr>
                            <td class="period-col">
                                {{ $period->name }}<br>
                                <span style="font-weight:400;color:#94a3b8;font-size:10px;">{{ substr($period->start_time,0,5) }}–{{ substr($period->end_time,0,5) }}</span>
                            </td>
                            @foreach ($tt['days'] as $day)
                                @php
                                    $meta    = $tt['day_meta'][$day][$period->id] ?? null;
                                    $isBreak = in_array($period->type, ['short_break','long_break','assembly'])
                                               && ($meta['effective_type'] ?? $period->type) !== 'lesson';
                                    $slot    = $tt['grid'][$period->id][$day] ?? null;
                                @endphp
                                @if ($isBreak)
                                    <td class="ttw-break">{{ ucfirst(str_replace('_',' ',$period->type)) }}</td>
                                @elseif (!$meta || !($meta['applicable'] ?? true))
                                    <td>—</td>
                                @elseif (!$slot || $slot['is_free'])
                                    <td class="ttw-free">Free</td>
                                @else
                                    <td data-teacher-id="{{ $slot['teacher_id'] ?? '' }}">
                                        <div class="ttw-subject">{{ $slot['subject'] }}</div>
                                        @if($slot['teacher'])<div class="ttw-teacher">{{ $slot['teacher'] }}</div>@endif
                                        @if($slot['room'])<div class="ttw-room">{{ $slot['room'] }}</div>@endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endforeach
</div>

</div></div></div>

<script>
window.STAFF_ANALYTICS = @json(collect($staffAnalytics['staff'] ?? [])->keyBy('id'));

document.getElementById('ttwSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('#ttwClassList .ttw-class-card').forEach(card => {
        card.style.display = !q || card.dataset.className.includes(q) ? '' : 'none';
    });
});

function ttwStaffSelect(staffId) {
    document.getElementById('ttwStaffFilter').value = staffId || '';

    document.querySelectorAll('td[data-teacher-id]').forEach(cell => {
        const matches = !!staffId && cell.dataset.teacherId === String(staffId);
        cell.classList.toggle('ttw-staff-highlight', matches);
        cell.classList.toggle('ttw-dimmed', !!staffId && !matches);
    });

    document.querySelectorAll('.ttw-board-row').forEach(row => {
        row.classList.toggle('ttw-board-selected', String(row.dataset.staffId) === String(staffId));
    });

    const detail = document.getElementById('ttwStaffDetail');
    if (!staffId || !window.STAFF_ANALYTICS[staffId]) {
        detail.classList.remove('active');
        return;
    }

    const s = window.STAFF_ANALYTICS[staffId];
    detail.classList.add('active');

    const renderFreq = (obj, max) => Object.entries(obj || {}).map(([name, count]) => `
        <div class="ttw-freq-row">
            <span class="name">${name}</span>
            <div class="ttw-freq-bar-wrap"><div class="ttw-freq-bar" style="width:${max ? Math.round((count / max) * 100) : 0}%"></div></div>
            <span class="ttw-freq-count">${count}</span>
        </div>
    `).join('') || '<div class="text-muted small">None</div>';

    const classMax = Math.max(0, ...Object.values(s.classes || {}));
    const subjectMax = Math.max(0, ...Object.values(s.subjects || {}));
    document.getElementById('ttwStaffClasses').innerHTML = renderFreq(s.classes, classMax);
    document.getElementById('ttwStaffSubjects').innerHTML = renderFreq(s.subjects, subjectMax);

    const days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    document.getElementById('ttwStaffDailyLoad').innerHTML = days.map(d => `
        <div class="ttw-daily-chip ${d === s.busiest_day ? 'busiest' : ''}">
            <div class="d">${d.slice(0,3)}</div>
            <div class="n">${(s.daily_load && s.daily_load[d]) || 0}</div>
        </div>
    `).join('');

    document.getElementById('ttwStaffConflictBanner').innerHTML = s.conflict_count > 0
        ? `<div class="ttw-conflict-banner"><i class="ri-alert-line me-1"></i>${s.name} has ${s.conflict_count} period(s) with a genuine double-booking across classes — check the highlighted cells above.</div>`
        : '';
}
</script>
@endsection