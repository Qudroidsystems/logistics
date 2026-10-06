<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Merged Timetable — Days as Rows (Grouped by Class)</title>
<style>
    @page { margin: 18px 14px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: {{ 9 * ($bodyScale ?? 1) }}px; color:#1E293B; }

    /* ── School header ───────────────────────────────── */
    .school-hdr { text-align:center; margin-bottom:10px; }
    .school-hdr h1 { font-size:{{ 16 * ($bodyScale ?? 1) }}px; margin:0 0 2px; color:#0F172A; letter-spacing:-0.2px; }
    .school-hdr .school-meta { font-size:{{ 8 * ($bodyScale ?? 1) }}px; color:#64748B; line-height:1.35; }
    .school-hdr .school-motto { font-style:italic; }
    .school-hdr .doc-title {
        margin-top:6px; font-size:{{ 11 * ($bodyScale ?? 1) }}px;
        font-weight:700; color:#1E293B;
    }
    .school-hdr .doc-sub {
        font-size:{{ 8.5 * ($bodyScale ?? 1) }}px; color:#64748B;
        margin-top:1px;
    }
    .school-hdr hr {
        border:none; border-top:1px solid #CBD5E1;
        margin:6px 0 0;
    }

    table.grid { width:100%; border-collapse:collapse; table-layout:fixed; margin-top:4px; }
    table.grid th, table.grid td {
        border:1px solid #CBD5E1; padding:3px 4px; vertical-align:top;
        font-size:{{ 7 * ($bodyScale ?? 1) }}px; line-height:1.25;
    }
    table.grid th {
        background:#1E293B; color:#fff;
        font-size:{{ 7.5 * ($bodyScale ?? 1) }}px;
        text-transform:uppercase; text-align:center;
    }
    th.day-col   { width:56px; }
    th.class-col { width:64px; }

    td.day-cell {
        font-weight:700; text-align:center; vertical-align:middle;
        color:#fff; font-size:{{ 8.5 * ($bodyScale ?? 1) }}px;
        border-right:2px solid #0F172A;
    }
    td.class-cell {
        font-weight:700; text-align:center; vertical-align:middle;
        color:#fff; font-size:{{ 7.5 * ($bodyScale ?? 1) }}px;
        border-right:2px solid #0F172A;
        white-space:nowrap;
    }

    .cell-lesson { padding-left:4px; }
    .cell-lesson .subj { font-weight:700; display:block; color:#0F172A; }
    .cell-lesson .meta { display:block; color:#64748B; font-size:{{ 6.3 * ($bodyScale ?? 1) }}px; margin-top:1px; }

    .cell-break { text-align:center; color:#D97706; font-weight:600; }
    .cell-na    { background:#F8FAFC; }
    .cell-free  { text-align:center; color:#CBD5E1; font-style:italic; }

    .legend {
        margin-top:10px; font-size:{{ 7.5 * ($bodyScale ?? 1) }}px;
        display:flex; flex-wrap:wrap; gap:8px;
    }
    .legend .item { display:inline-flex; align-items:center; gap:3px; }
    .legend .swatch { display:inline-block; width:8px; height:8px; border-radius:2px; }

    .footer { margin-top:8px; font-size:7px; color:#94A3B8; text-align:right; }
</style>
</head>
<body>

    {{-- ═══════════ SCHOOL HEADER ═══════════ --}}
    <div class="school-hdr">
        @if(!empty($schoolInfo))
            <h1>{{ $schoolInfo->school_name ?? config('app.name') }}</h1>
            @if($schoolInfo->school_address)
                <div class="school-meta">{{ $schoolInfo->school_address }}</div>
            @endif
            @if($schoolInfo->school_motto)
                <div class="school-meta school-motto">“{{ $schoolInfo->school_motto }}”</div>
            @endif
            @if(($schoolInfo->formatted_phones ?? '-') !== '-' || $schoolInfo->school_email)
                <div class="school-meta">
                    @if(($schoolInfo->formatted_phones ?? '-') !== '-')
                        Tel: {{ $schoolInfo->formatted_phones }}
                    @endif
                    @if($schoolInfo->school_email)
                        @if(($schoolInfo->formatted_phones ?? '-') !== '-') &middot; @endif
                        {{ $schoolInfo->school_email }}
                    @endif
                </div>
            @endif
        @else
            <h1>{{ config('app.name') }}</h1>
        @endif

        <div class="doc-title">Merged Timetable — Days as Rows (Grouped by Class)</div>
        <div class="doc-sub">{{ $sessionName }} &middot; {{ $termName }}</div>
        <hr>
    </div>

    {{-- ═══════════ GRID ═══════════ --}}
    @if(empty($daySections))
        <p style="text-align:center;color:#94A3B8;padding:30px 0;">
            No timetables found for this session / term.
        </p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th class="day-col">Day</th>
                    <th class="class-col">Class</th>
                    @foreach($timeSlots as $slot)
                        <th>
                            {{ $slot['label'] }}<br>
                            <span style="font-weight:400;opacity:.75">{{ $slot['start'] }}–{{ $slot['end'] }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($daySections as $section)
                    @foreach($section['class_rows'] as $rowIdx => $classRow)
                        <tr>
                            @if($rowIdx === 0)
                                {{-- Day cell spans all class rows for this day --}}
                                <td class="day-cell"
                                    rowspan="{{ $section['rowspan'] }}"
                                    style="background: {{ $section['day_color'] }};">
                                    {{ $section['day'] }}
                                </td>
                            @endif

                            {{-- Class cell — one per class row --}}
                            <td class="class-cell" style="background: {{ $classRow['class_color'] }};">
                                {{ $classRow['class'] }}
                            </td>

                            {{-- Period cells --}}
                            @foreach($classRow['cells'] as $cell)
                                @if($cell['state'] === 'na')
                                    <td class="cell-na"></td>
                                @elseif($cell['state'] === 'break')
                                    <td class="cell-break">Break</td>
                                @elseif($cell['state'] === 'free')
                                    <td class="cell-free">Free</td>
                                @else
                                    <td>
                                        <div class="cell-lesson"
                                             style="border-left:2px solid {{ $classRow['class_color'] }};">
                                            <span class="subj">{{ $cell['subject'] ?? '—' }}</span>
                                            <span class="meta">
                                                {{ trim(($cell['teacher'] ?? '') . ($cell['room'] ? ' · ' . $cell['room'] : '')) }}
                                            </span>
                                        </div>
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ═══════════ LEGEND ═══════════ --}}
    @if(!empty($classList))
        <div class="legend">
            @foreach($classList as $cls)
                <span class="item">
                    <span class="swatch" style="background: {{ $classColors[$cls] ?? '#999' }}"></span>{{ $cls }}
                </span>
            @endforeach
        </div>
    @endif

    <div class="footer">Generated {{ $generatedAt }}</div>
</body>
</html>