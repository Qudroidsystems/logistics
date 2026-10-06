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

.ttw-grid-card { background:#fff; border:1px solid var(--tt-border); border-radius:var(--tt-radius); box-shadow:var(--tt-shadow); margin-bottom:22px; overflow:hidden; }
.ttw-grid-card .hdr { background:linear-gradient(135deg,#1565C0,#0d9488); color:#fff; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.ttw-grid-card .hdr h5 { margin:0; font-size:15px; font-weight:700; }
.ttw-grid-card .hdr .badges { display:flex; gap:6px; flex-wrap:wrap; }
.ttw-badge { background:rgba(255,255,255,.18); border-radius:14px; padding:3px 10px; font-size:11px; font-weight:600; }

table.ttw-grid { width:100%; border-collapse:collapse; font-size:12px; table-layout:auto; }
table.ttw-grid th { background:#1E293B; color:#fff; padding:8px 6px; text-align:center; font-size:11px; text-transform:uppercase; }
table.ttw-grid td { border:1px solid var(--tt-border); padding:6px; vertical-align:top; transition:box-shadow .15s ease, opacity .15s ease; }
table.ttw-grid td.period-col { background:#F8FAFC; text-align:left; font-weight:700; white-space:nowrap; color:#0f2342; width:90px; }
.entry { border-left:3px solid #999; padding:3px 5px; margin-bottom:4px; border-radius:4px; background:#F8FAFC; text-align:left; }
.entry.conflict { background:#FEF2F2; border-left-color:#EF4444 !important; }
.entry .cls { font-weight:700; display:block; font-size:11px; color:var(--tt-navy); }
.entry .subj { display:block; font-size:11.5px; color:#334155; }
.entry .meta { color:#64748B; font-size:10.5px; }
.ttw-break { background:#FFFBEB; color:#d97706; font-weight:700; font-size:11px; text-align:center; }
.ttw-cell-na { background:#F8FAFC; }

.ttw-legend { display:flex; flex-wrap:wrap; gap:12px; margin-top:16px; font-size:12px; }
.ttw-legend .item { display:flex; align-items:center; gap:6px; }
.ttw-legend .swatch { width:11px; height:11px; border-radius:3px; display:inline-block; }

td.ttw-staff-highlight { box-shadow: inset 0 0 0 2px #dc2626; background:#FEF2F2; }
td.ttw-dimmed { opacity:.25; }

.run-meta-block { background:#F8FAFC; border:1px solid #E2E8F0; border-left:4px solid #1565C0; border-radius:10px; padding:12px 16px; margin-bottom:16px; }
.run-meta-header { display:flex; justify-content:space-between; align-items:baseline; gap:12px; flex-wrap:wrap; }
.run-meta-name { font-size:14px; color:#0f2342; }
.run-meta-code { color:#64748B; margin-left:8px; font-family:monospace; font-size:12px; letter-spacing:.5px; }
.run-meta-right { color:#64748B; font-size:12px; }
.run-meta-desc { margin-top:6px; color:#475569; font-size:13px; line-height:1.4; }
.entry.double-start { border-left-width:4px; }
.entry.double-continue { opacity:.88; border-top:2px dashed #0d9488; margin-top:-1px; }
.double-icon { font-size:10px; color:#0d9488; margin-right:3px; vertical-align:middle; }

@media print {
    .no-print { display:none !important; }
    .ttw-grid-card { box-shadow:none; page-break-inside:avoid; }
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

<div class="ttw-hero">
    <div>
        <h1><i class="ri-layout-grid-line me-2"></i>{{ $schoolInfo->school_name ?? 'School' }} — Merged Timetable</h1>
        <p>Days as Columns &middot; {{ $sessionName }} &middot; {{ $termName }} &middot; Generated {{ $generatedAt }}</p>
        <div class="pills">
            <span class="ttw-pill"><i class="ri-team-line me-1"></i>{{ count($classList) }} classes overlaid</span>
            <span class="ttw-pill"><i class="ri-layout-column-line me-1"></i>{{ count($days) }} days</span>
            <span class="ttw-pill"><i class="ri-time-line me-1"></i>{{ count($rows) }} time slots</span>
        </div>
    </div>
    <button class="ttw-print-btn no-print" onclick="window.print()">
        <i class="ri-printer-line me-1"></i>Print / Save PDF
    </button>
</div>

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

<div class="ttw-stats">
    <div class="ttw-stat"><div class="v">{{ count($classList) }}</div><div class="l">Classes Merged</div></div>
    <div class="ttw-stat"><div class="v">{{ count($days) }}</div><div class="l">Days</div></div>
    <div class="ttw-stat"><div class="v">{{ count($rows) }}</div><div class="l">Time Slots</div></div>
    <div class="ttw-stat">
        <div class="v" style="color:{{ collect($rows)->contains(fn($r) => collect($r['days'])->contains(fn($c) => collect($c['entries'] ?? [])->contains(fn($e) => !empty($e['is_conflict'])))) ? '#dc2626' : '#16a34a' }}">
            {{ collect($rows)->sum(fn($r) => collect($r['days'])->sum(fn($c) => collect($c['entries'] ?? [])->where('is_conflict', true)->count())) }}
        </div>
        <div class="l">Conflicts</div>
    </div>
</div>

@if(!empty($staffAnalytics['staff']))
<div class="ttw-staff-card no-print">
    <h5><i class="ri-bar-chart-grouped-line"></i>Staff Analysis</h5>

    <div class="ttw-staff-filter">
        <label for="ttwStaffFilter">Highlight staff:</label>
        <select id="ttwStaffFilter" onchange="ttwStaffSelect(this.value)">
            <option value="">— Show all (no highlight) —</option>
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
        </div><div class="l">Most Conflicted</div></div>
    </div>
    @endif

    <div id="ttwStaffDetail">
        <div class="ttw-staff-cols">
            <div>
                <h6 style="font-size:12.5px;font-weight:700;color:#334155;">Classes (frequency)</h6>
                <div class="ttw-freq-list" id="ttwStaffClasses"></div>
            </div>
            <div>
                <h6 style="font-size:12.5px;font-weight:700;color:#334155;">Subjects (frequency)</h6>
                <div class="ttw-freq-list" id="ttwStaffSubjects"></div>
            </div>
        </div>
        <h6 style="font-size:12.5px;font-weight:700;color:#334155;margin-top:16px;">Daily Load</h6>
        <div class="ttw-daily-load" id="ttwStaffDailyLoad"></div>
        <div id="ttwStaffConflictBanner"></div>
    </div>

    <h6 style="font-size:12.5px;font-weight:700;color:#334155;margin-top:18px;">Workload Leaderboard</h6>
    <div style="overflow-x:auto;">
        <table class="ttw-board">
            <thead>
                <tr>
                    <th>Staff</th><th>Periods</th><th>Classes</th>
                    <th>Subjects</th><th>Busiest Day</th><th>Conflicts</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($staffAnalytics['staff'] ?? []) as $s)
                    <tr class="ttw-board-row" data-staff-id="{{ $s['id'] }}" onclick="ttwStaffSelect('{{ $s['id'] }}')">
                        <td>{{ $s['name'] }}</td>
                        <td>{{ $s['total_periods'] }}</td>
                        <td>{{ $s['class_count'] }}</td>
                        <td>{{ $s['subject_count'] }}</td>
                        <td>{{ $s['busiest_day'] ?? '—' }}</td>
                        <td class="{{ $s['conflict_count'] > 0 ? 'conflict-cell' : '' }}">{{ $s['conflict_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="ttw-grid-card">
    <div class="hdr">
        <h5><i class="ri-layout-grid-line me-1"></i>Merged Grid — Days as Columns</h5>
        <div class="badges">
            <span class="ttw-badge">{{ count($classList) }} classes</span>
            <span class="ttw-badge">{{ count($rows) }} slots</span>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="ttw-grid">
            <thead>
                <tr>
                    <th class="period-col" style="background:#0f2342;text-align:left;padding-left:10px;">Period</th>
                    @foreach($days as $day)
                        <th style="background:{{ $dayColors[$day] ?? '#1E293B' }}">{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="period-col">
                            {{ $row['label'] }}<br>
                            <span style="font-weight:400;color:#94a3b8;font-size:10.5px;">{{ $row['time'] }}</span>
                        </td>
                        @foreach($days as $day)
                            @php $cell = $row['days'][$day] ?? null; @endphp
                            @if(!$cell || !($cell['applicable'] ?? false))
                                <td class="ttw-cell-na"></td>
                            @elseif(!empty($cell['is_break']))
                                <td class="ttw-break">Break</td>
                            @elseif(empty($cell['entries']))
                                <td></td>
                            @else
                                <td>
                                    
                                     @foreach($cell['entries'] as $e)
                                        <div class="entry
                                                    {{ !empty($e['is_conflict']) ? 'conflict' : '' }}
                                                    {{ ($e['double_role'] ?? null) === 'start'    ? 'double-start'    : '' }}
                                                    {{ ($e['double_role'] ?? null) === 'continue' ? 'double-continue' : '' }}"
                                            style="border-left-color: {{ $e['color'] ?? '#999' }};"
                                            data-teacher-id="{{ $e['teacher_id'] ?? '' }}">
                                            @if(($e['double_role'] ?? null) === 'start')
                                                <span class="double-icon" title="Double period">🔗</span>
                                            @elseif(($e['double_role'] ?? null) === 'continue')
                                                <span class="double-icon" title="Continued double period">↳</span>
                                            @endif
                                            <span class="cls">{{ $e['class'] }}</span>
                                            <span class="subj">{{ $e['subject'] }}</span>
                                            <span class="meta">{{ trim(($e['teacher'] ?? '') . ($e['room'] ? ' · ' . $e['room'] : '')) }}</span>
                                        </div>
                                    @endforeach
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="ttw-legend">
    @foreach($classList as $cls)
        <span class="item">
            <span class="swatch" style="background: {{ $classColors[$cls] ?? '#999' }}"></span>{{ $cls }}
        </span>
    @endforeach
</div>

</div></div></div>

<script>
window.STAFF_ANALYTICS = @json(collect($staffAnalytics['staff'] ?? [])->keyBy('id'));

function ttwStaffSelect(staffId) {
    document.getElementById('ttwStaffFilter').value = staffId || '';

    document.querySelectorAll('.entry[data-teacher-id]').forEach(cell => {
        const matches = !!staffId && cell.dataset.teacherId === String(staffId);
        cell.closest('td')?.classList.toggle('ttw-staff-highlight', matches);
        cell.closest('td')?.classList.toggle('ttw-dimmed', !!staffId && !matches);
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
        ? `<div class="ttw-conflict-banner"><i class="ri-alert-line me-1"></i>${s.name} has ${s.conflict_count} period(s) with a genuine double-booking across classes — check highlighted cells above.</div>`
        : '';
}
</script>
@endsection