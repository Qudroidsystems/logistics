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

.ttw-grid-card { background:#fff; border:1px solid var(--tt-border); border-radius:var(--tt-radius); box-shadow:var(--tt-shadow); margin-bottom:22px; overflow:hidden; }
.ttw-grid-card .hdr { background:linear-gradient(135deg,#1565C0,#0d9488); color:#fff; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.ttw-grid-card .hdr h5 { margin:0; font-size:15px; font-weight:700; }
.ttw-grid-card .hdr .badges { display:flex; gap:6px; flex-wrap:wrap; }
.ttw-badge { background:rgba(255,255,255,.18); border-radius:14px; padding:3px 10px; font-size:11px; font-weight:600; }

table.ttw-grid { width:100%; border-collapse:collapse; font-size:12px; }
table.ttw-grid th { background:#1E293B; color:#fff; padding:8px 6px; text-align:center; font-size:11px; text-transform:uppercase; }
table.ttw-grid td { border:1px solid var(--tt-border); padding:6px; vertical-align:top; }
table.ttw-grid td.day-cell {
    font-weight:700; text-align:center; vertical-align:middle;
    color:#fff; width:80px; font-size:13px;
    border-right:2px solid #0f172a;
}
table.ttw-grid td.class-cell {
    font-weight:700; text-align:center; vertical-align:middle;
    color:#fff; width:90px; font-size:12px;
    border-right:2px solid #0f172a;
    white-space:nowrap;
}
.cell-lesson { padding-left:5px; border-left:3px solid #999; }
.cell-lesson .subj { font-weight:700; display:block; font-size:11.5px; color:var(--tt-navy); }
.cell-lesson .meta { color:#64748B; font-size:10.5px; display:block; margin-top:1px; }
.ttw-break { background:#FFFBEB; color:#d97706; font-weight:700; font-size:11px; text-align:center; }
.ttw-free  { color:#cbd5e1; font-size:11px; text-align:center; font-style:italic; }
.ttw-cell-na { background:#F8FAFC; }

.ttw-legend { display:flex; flex-wrap:wrap; gap:12px; margin-top:16px; font-size:12px; }
.ttw-legend .item { display:flex; align-items:center; gap:6px; }
.ttw-legend .swatch { width:11px; height:11px; border-radius:3px; display:inline-block; }

@media print {
    .no-print { display:none !important; }
    .ttw-grid-card { box-shadow:none; }
}
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

<div class="ttw-hero">
    <div>
        <h1><i class="ri-layout-row-line me-2"></i>{{ $schoolInfo->school_name ?? 'School' }} — Merged Timetable</h1>
        <p>Days as Rows (Grouped by Class) &middot; {{ $sessionName }} &middot; {{ $termName }} &middot; Generated {{ $generatedAt }}</p>
        <div class="pills">
            <span class="ttw-pill"><i class="ri-team-line me-1"></i>{{ count($classList) }} classes</span>
            <span class="ttw-pill"><i class="ri-calendar-line me-1"></i>{{ count($days) }} days</span>
            <span class="ttw-pill"><i class="ri-time-line me-1"></i>{{ count($timeSlots) }} time slots</span>
        </div>
    </div>
    <button class="ttw-print-btn no-print" onclick="window.print()">
        <i class="ri-printer-line me-1"></i>Print / Save PDF
    </button>
</div>

<div class="ttw-stats">
    <div class="ttw-stat"><div class="v">{{ count($classList) }}</div><div class="l">Classes</div></div>
    <div class="ttw-stat"><div class="v">{{ count($days) }}</div><div class="l">Days</div></div>
    <div class="ttw-stat"><div class="v">{{ count($timeSlots) }}</div><div class="l">Time Slots</div></div>
    <div class="ttw-stat"><div class="v">{{ count($classList) * count($days) }}</div><div class="l">Class-Day Rows</div></div>
</div>

@if(empty($daySections))
    <div class="ttw-grid-card" style="padding:40px;text-align:center;color:#94a3b8;">
        No timetables found for this session / term.
    </div>
@else
    <div class="ttw-grid-card">
        <div class="hdr">
            <h5><i class="ri-layout-row-line me-1"></i>Merged Grid — Days as Rows, Grouped by Class</h5>
            <div class="badges">
                <span class="ttw-badge">{{ count($classList) }} classes</span>
                <span class="ttw-badge">{{ count($timeSlots) }} time slots</span>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="ttw-grid">
                <thead>
                    <tr>
                        <th style="background:#0f2342;width:80px;">Day</th>
                        <th style="background:#0f2342;width:90px;">Class</th>
                        @foreach($timeSlots as $slot)
                            <th>
                                {{ $slot['label'] }}<br>
                                <span style="font-weight:400;opacity:.75;font-size:10px;">{{ $slot['start'] }}–{{ $slot['end'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($daySections as $section)
                        @foreach($section['class_rows'] as $rowIdx => $classRow)
                            <tr>
                                @if($rowIdx === 0)
                                    <td class="day-cell"
                                        rowspan="{{ $section['rowspan'] }}"
                                        style="background: {{ $section['day_color'] }};">
                                        {{ $section['day'] }}
                                    </td>
                                @endif

                                <td class="class-cell" style="background: {{ $classRow['class_color'] }};">
                                    {{ $classRow['class'] }}
                                </td>

                                @foreach($classRow['cells'] as $cell)
                                    @if($cell['state'] === 'na')
                                        <td class="ttw-cell-na"></td>
                                    @elseif($cell['state'] === 'break')
                                        <td class="ttw-break">☕ Break</td>
                                    @elseif($cell['state'] === 'free')
                                        <td class="ttw-free">Free</td>
                                    @else
                                        <td>
                                            <div class="cell-lesson" style="border-left-color: {{ $classRow['class_color'] }};">
                                                <span class="subj">{{ $cell['subject'] ?? '—' }}</span>
                                                <span class="meta">{{ trim(($cell['teacher'] ?? '') . ($cell['room'] ? ' · ' . $cell['room'] : '')) }}</span>
                                            </div>
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
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
@endif

</div></div></div>
@endsection