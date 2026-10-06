<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Merged Timetable — Rows per Class</title>
<style>
    @page { margin: 16px 12px; }
    * { box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:{{ 8.5 * ($bodyScale ?? 1) }}px; color:#1E293B; }
    .hdr { text-align:center; margin-bottom:8px; }
    .hdr h1 { font-size:{{ 14 * ($bodyScale ?? 1) }}px; margin:0; }
    .hdr .sub { font-size:{{ 8.5 * ($bodyScale ?? 1) }}px; color:#64748B; }
    table.grid { width:100%; border-collapse:collapse; table-layout:fixed; }
    table.grid th, table.grid td { border:1px solid #CBD5E1; padding:3px; vertical-align:top; }
    table.grid th { background:#1E293B; color:#fff; font-size:{{ 7.5 * ($bodyScale ?? 1) }}px; text-transform:uppercase; }
    th.class-col, td.class-col { width:70px; text-align:center; vertical-align:middle; font-weight:bold; color:#fff; }
    th.time-col, td.time-col { width:74px; }
    .cell-lesson { font-size:{{ 7 * ($bodyScale ?? 1) }}px; border-left:3px solid #999; padding:2px 3px; }
    .cell-lesson .subj { font-weight:bold; display:block; }
    .cell-lesson .meta { color:#64748B; display:block; }
    .cell-break { text-align:center; color:#D97706; }
    .cell-free { text-align:center; color:#CBD5E1; }
    .cell-na { background:#F8FAFC; }
    .footer { margin-top:8px; font-size:7px; color:#94A3B8; text-align:right; }
</style>
</head>
<body>
    <div class="hdr">
        @if(!empty($schoolInfo))<h1>{{ $schoolInfo->name ?? config('app.name') }}</h1>@endif
        <div class="sub">Merged Timetable &middot; Rows per Class &middot; {{ $sessionName }} &middot; {{ $termName }}</div>
    </div>

    <table class="grid">
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
                    <td class="time-col">{{ $row['label'] }}<br>{{ $row['time'] }}</td>
                    @foreach($days as $day)
                        @php $cell = $row['cells'][$day] ?? ['state' => 'na']; @endphp
                        @if($cell['state'] === 'na')
                            <td class="cell-na"></td>
                        @elseif($cell['state'] === 'break')
                            <td class="cell-break">Break</td>
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

    <div class="footer">Generated {{ $generatedAt }}</div>
</body>
</html>