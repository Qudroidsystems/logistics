<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Merged Timetable — Columns per Class</title>
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
    th.day-col, td.day-col { width:44px; text-align:center; vertical-align:middle; font-weight:bold; }
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
        <div class="sub">Merged Timetable &middot; Columns per Class &middot; {{ $sessionName }} &middot; {{ $termName }}</div>
    </div>

    {{-- Controller doesn't precompute day-rowspan groups for this builder (unlike class_rows),
         so we group consecutive same-day rows here. Rows arrive already ordered day-then-time. --}}
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

    <table class="grid">
        <thead>
            <tr>
                <th class="day-col">Day</th>
                <th class="time-col">Period</th>
                @foreach($classList as $cls)
                    <th style="background: {{ $classColors[$cls] ?? '#1E293B' }}">{{ $cls }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    @if(isset($dayRowSpans[$i]))
                        <td class="day-col" rowspan="{{ $dayRowSpans[$i] }}" style="background: {{ $dayColors[$row['day']] ?? '#334155' }}; color:#fff;">{{ $row['day'] }}</td>
                    @endif
                    <td class="time-col">{{ $row['label'] }}<br>{{ $row['time'] }}</td>
                    @foreach($classList as $cls)
                        @php $cell = $row['cells'][$cls] ?? ['state' => 'na']; @endphp
                        @if($cell['state'] === 'na')
                            <td class="cell-na"></td>
                        @elseif($cell['state'] === 'break')
                            <td class="cell-break">Break</td>
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

    <div class="footer">Generated {{ $generatedAt }}</div>
</body>
</html>