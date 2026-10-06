<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $pagetitle ?? 'Merged Timetable' }} — Rows per Class</title>
<style>
    :root { --border:#E2E8F0; }
    * { box-sizing:border-box; }
    body { font-family: -apple-system, "Segoe UI", Roboto, sans-serif; margin:0; padding:24px; background:#F8FAFC; color:#1E293B; }
    .toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px; }
    .toolbar h1 { font-size:18px; margin:0; }
    .toolbar .sub { font-size:13px; color:#64748B; }
    .btn { border:1px solid var(--border); background:#fff; border-radius:8px; padding:8px 14px; font-size:13px; cursor:pointer; }
    .btn:hover { background:#F1F5F9; }
    .grid-wrap { overflow-x:auto; background:#fff; border:1px solid var(--border); border-radius:12px; }
    table { border-collapse:collapse; width:100%; min-width:900px; }
    th, td { border:1px solid var(--border); padding:8px; vertical-align:top; font-size:12px; }
    thead th { background:#1E293B; color:#fff; position:sticky; top:0; text-align:left; }
    th.class-col, td.class-col { width:90px; text-align:center; font-weight:700; color:#fff; }
    th.time-col, td.time-col { width:110px; }
    .cell-lesson { border-left:3px solid #999; padding:5px 7px; border-radius:4px; background:#F8FAFC; }
    .cell-lesson .subj { font-weight:700; display:block; }
    .cell-lesson .meta { color:#64748B; font-size:11px; }
    .cell-break { text-align:center; color:#D97706; font-weight:600; }
    .cell-free { text-align:center; color:#CBD5E1; }
    .cell-na { background:#F8FAFC; }
    .footer-note { margin-top:10px; font-size:11px; color:#94A3B8; }
    @media print {
        .toolbar .btn { display:none; }
        body { background:#fff; padding:0; }
    }
</style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>{{ $pagetitle ?? 'Merged Timetable' }} — Rows per Class</h1>
            <div class="sub">{{ $sessionName }} &middot; {{ $termName }}</div>
        </div>
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <div class="grid-wrap">
    <table>
        <thead>
            <tr>
                <th class="class-col">Class</th>
                <th class="time-col">Period</th>
                @foreach($days as $day)
                    <th style="background: {{ $dayColors[$day] ?? '#1E293B' }}">{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @if($row['is_first_row'])
                        <td class="class-col" rowspan="{{ $row['rowspan'] }}" style="background: {{ $row['class_color'] ?? '#334155' }};">{{ $row['class'] }}</td>
                    @endif
                    <td class="time-col">
                        <strong>{{ $row['label'] }}</strong><br>
                        <span style="color:#94A3B8">{{ $row['time'] }}</span>
                    </td>
                    @foreach($days as $day)
                        @php $cell = $row['cells'][$day] ?? ['state' => 'na']; @endphp
                        @if($cell['state'] === 'na')
                            <td class="cell-na"></td>
                        @elseif($cell['state'] === 'break')
                            <td class="cell-break">☕ Break</td>
                        @elseif($cell['state'] === 'free')
                            <td class="cell-free">Free</td>
                        @else
                            <td>
                                <div class="cell-lesson" style="border-left-color: {{ $row['class_color'] ?? '#999' }}">
                                    <span class="subj">{{ $cell['subject'] ?? '—' }}</span>
                                    <span class="meta">{{ trim(($cell['teacher'] ?? '') . ($cell['room'] ? ' · ' . $cell['room'] : '')) }}{{ !empty($cell['is_double']) ? ' · Double' : '' }}</span>
                                </div>
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    <div class="footer-note">Generated {{ $generatedAt }}</div>
</body>
</html>