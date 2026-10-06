<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $pagetitle ?? 'Merged Timetable' }} — Columns per Class</title>
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
    thead th { color:#fff; position:sticky; top:0; text-align:left; }
    th.day-col, td.day-col { width:70px; text-align:center; font-weight:700; color:#fff; }
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
            <h1>{{ $pagetitle ?? 'Merged Timetable' }} — Columns per Class</h1>
            <div class="sub">{{ $sessionName }} &middot; {{ $termName }}</div>
        </div>
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    @php
        $dayRowSpans = [];
        $lastDay = null; $groupStart = -1;
        foreach ($rows as $i => $r) {
            if ($r['day'] !== $lastDay) {
                $groupStart = $i;
                $dayRowSpans[$groupStart] = 1;
                $lastDay = $r['day'];
            } else {
                $dayRowSpans[$groupStart]++;
            }
        }
    @endphp

    <div class="grid-wrap">
    <table>
        <thead>
            <tr>
                <th class="day-col" style="background:#1E293B">Day</th>
                <th class="time-col" style="background:#1E293B">Period</th>
                @foreach($classList as $cls)
                    <th style="background: {{ $classColors[$cls] ?? '#1E293B' }}">{{ $cls }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    @if(isset($dayRowSpans[$i]))
                        <td class="day-col" rowspan="{{ $dayRowSpans[$i] }}" style="background: {{ $dayColors[$row['day']] ?? '#334155' }};">{{ $row['day'] }}</td>
                    @endif
                    <td class="time-col">
                        <strong>{{ $row['label'] }}</strong><br>
                        <span style="color:#94A3B8">{{ $row['time'] }}</span>
                    </td>
                    @foreach($classList as $cls)
                        @php $cell = $row['cells'][$cls] ?? ['state' => 'na']; @endphp
                        @if($cell['state'] === 'na')
                            <td class="cell-na"></td>
                        @elseif($cell['state'] === 'break')
                            <td class="cell-break">☕ Break</td>
                        @elseif($cell['state'] === 'free')
                            <td class="cell-free">Free</td>
                        @else
                            <td>
                                <div class="cell-lesson" style="border-left-color: {{ $classColors[$cls] ?? '#999' }}">
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