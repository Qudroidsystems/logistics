<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Merged Timetable — Days as Rows</title>
<style>
    @page { margin: 18px 14px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: {{ 9 * ($bodyScale ?? 1) }}px; color:#1E293B; }
    .hdr { text-align:center; margin-bottom:10px; }
    .hdr h1 { font-size:{{ 15 * ($bodyScale ?? 1) }}px; margin:0 0 2px; }
    .hdr .sub { font-size:{{ 9 * ($bodyScale ?? 1) }}px; color:#64748B; }
    table.grid { width:100%; border-collapse:collapse; table-layout:fixed; }
    table.grid th, table.grid td { border:1px solid #CBD5E1; padding:4px; vertical-align:top; }
    table.grid th { background:#1E293B; color:#fff; font-size:{{ 8 * ($bodyScale ?? 1) }}px; text-transform:uppercase; }
    th.day-col { width:70px; }
    td.day-cell { font-weight:bold; text-align:center; vertical-align:middle; }
    .entry { border-left:3px solid #999; padding:2px 3px; margin-bottom:3px; font-size:{{ 7.5 * ($bodyScale ?? 1) }}px; }
    .entry .cls { font-weight:bold; display:block; }
    .entry .subj { display:block; }
    .entry .meta { display:block; color:#64748B; font-size:{{ 7 * ($bodyScale ?? 1) }}px; }
    .conflict-entry { background:#FEF2F2; }
    .cell-break { text-align:center; color:#D97706; font-size:{{ 7.5 * ($bodyScale ?? 1) }}px; }
    .cell-na { background:#F8FAFC; }
    .legend { margin-top:10px; font-size:{{ 7.5 * ($bodyScale ?? 1) }}px; }
    .legend .item { display:inline-block; margin-right:10px; }
    .legend .swatch { display:inline-block; width:8px; height:8px; margin-right:3px; }
    .footer { margin-top:8px; font-size:7px; color:#94A3B8; text-align:right; }
</style>
</head>
<body>
    <div class="hdr">
        @if(!empty($schoolInfo))<h1>{{ $schoolInfo->name ?? config('app.name') }}</h1>@endif
        <div class="sub">Merged Timetable &middot; Days as Rows &middot; {{ $sessionName }} &middot; {{ $termName }}</div>
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th class="day-col">Day</th>
                @foreach($rows as $row)
                    <th>{{ $row['label'] }}<br>{{ $row['time'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($days as $day)
                <tr>
                    <td class="day-cell" style="background: {{ $dayColors[$day] ?? '#334155' }}; color:#fff;">{{ $day }}</td>
                    @foreach($rows as $row)
                        @php $cell = $row['days'][$day] ?? null; @endphp
                        @if(!$cell || !($cell['applicable'] ?? false))
                            <td class="cell-na"></td>
                        @elseif(!empty($cell['is_break']))
                            <td class="cell-break">Break</td>
                        @elseif(empty($cell['entries']))
                            <td></td>
                        @else
                            <td>
                                @foreach($cell['entries'] as $e)
                                    <div class="entry {{ !empty($e['is_conflict']) ? 'conflict-entry' : '' }}" style="border-left-color: {{ $e['color'] ?? '#999' }}">
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

    <div class="legend">
        @foreach($classList as $cls)
            <span class="item"><span class="swatch" style="background: {{ $classColors[$cls] ?? '#999' }}"></span>{{ $cls }}</span>
        @endforeach
    </div>

    <div class="footer">Generated {{ $generatedAt }}</div>
</body>
</html>