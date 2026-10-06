<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Merged Timetable — Days as Columns</title>
<style>
    @page { margin: 18px 14px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: {{ 9 * ($bodyScale ?? 1) }}px; color: #1E293B; margin: 0; }

    .school-hdr {
        text-align: center;
        padding: {{ 10 * ($bodyScale ?? 1) }}px 14px;
        background: linear-gradient(135deg, #0f2342 0%, #1e4a7e 55%, #0d9488 100%);
        color: #fff;
        border-radius: {{ 8 * ($bodyScale ?? 1) }}px;
        margin-bottom: {{ 12 * ($bodyScale ?? 1) }}px;
    }
    .school-hdr h1 { font-size: {{ 17 * ($bodyScale ?? 1) }}px; font-weight: 700; margin: 0 0 3px; letter-spacing: -.2px; }
    .school-hdr .school-meta { font-size: {{ 8.5 * ($bodyScale ?? 1) }}px; opacity: .88; line-height: 1.35; }
    .school-hdr .school-motto { font-style: italic; }
    .school-hdr .doc-title { font-size: {{ 12 * ($bodyScale ?? 1) }}px; font-weight: 700; margin-top: 5px; }
    .school-hdr .doc-sub { font-size: {{ 9 * ($bodyScale ?? 1) }}px; opacity: .8; margin-top: 2px; }

    table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: {{ 7.5 * ($bodyScale ?? 1) }}px; }
    table.grid th, table.grid td {
        border: 1px solid #CBD5E1;
        padding: {{ 4 * ($bodyScale ?? 1) }}px {{ 3 * ($bodyScale ?? 1) }}px;
        vertical-align: top;
    }
    table.grid th {
        background: #1E293B; color: #fff;
        font-size: {{ 7 * ($bodyScale ?? 1) }}px;
        text-transform: uppercase; text-align: center;
    }
    th.period-col { width: 80px; text-align: left; padding-left: 8px; }

    .entry {
        border-left: 3px solid #999;
        padding: 2px 4px;
        margin-bottom: 3px;
        border-radius: 3px;
        background: rgba(248, 250, 252, .6);
    }
    .entry .cls { font-weight: 700; display: block; font-size: {{ 7 * ($bodyScale ?? 1) }}px; }
    .entry .subj { display: block; font-size: {{ 7 * ($bodyScale ?? 1) }}px; }
    .entry .meta { display: block; color: #64748B; font-size: {{ 6.3 * ($bodyScale ?? 1) }}px; }
    .conflict-entry { background: #FEF2F2; border-left-color: #EF4444 !important; }

    .cell-break { text-align: center; color: #D97706; font-weight: 600; font-size: {{ 7 * ($bodyScale ?? 1) }}px; }
    .cell-na { background: #F8FAFC; }

    .legend { margin-top: 10px; font-size: {{ 7.5 * ($bodyScale ?? 1) }}px; }
    .legend .item { display: inline-block; margin-right: 10px; }
    .legend .swatch { display: inline-block; width: 8px; height: 8px; margin-right: 3px; border-radius: 2px; }

    .footer-note { margin-top: 8px; font-size: 7px; color: #94A3B8; text-align: right; }
</style>
</head>
<body>

<div class="school-hdr">
    <h1>{{ $schoolInfo->school_name ?? config('app.name') }}</h1>
    @if($schoolInfo?->school_address)
        <div class="school-meta">{{ $schoolInfo->school_address }}</div>
    @endif
    @if($schoolInfo?->school_motto)
        <div class="school-meta school-motto">“{{ $schoolInfo->school_motto }}”</div>
    @endif
    @if(($schoolInfo?->formatted_phones ?? '-') !== '-' || $schoolInfo?->school_email)
        <div class="school-meta">
            @if(($schoolInfo->formatted_phones ?? '-') !== '-')
                Tel: {{ $schoolInfo->formatted_phones }}
            @endif
            @if($schoolInfo?->school_email)
                @if(($schoolInfo->formatted_phones ?? '-') !== '-') &middot; @endif
                {{ $schoolInfo->school_email }}
            @endif
        </div>
    @endif
    <div class="doc-title">Merged Timetable — Days as Columns</div>
    <div class="doc-sub">{{ $sessionName }} &middot; {{ $termName }}</div>
</div>

<table class="grid">
    <thead>
        <tr>
            <th class="period-col">Period</th>
            @foreach($days as $day)
                <th style="background: {{ $dayColors[$day] ?? '#1E293B' }};">{{ $day }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                <td style="background:#F8FAFC;font-weight:700;">
                    {{ $row['label'] }}<br>
                    <span style="font-weight:400;color:#94a3b8;font-size:{{ 6.5 * ($bodyScale ?? 1) }}px;">{{ $row['time'] }}</span>
                </td>
                @foreach($days as $day)
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
                                <div class="entry {{ !empty($e['is_conflict']) ? 'conflict-entry' : '' }}"
                                     style="border-left-color: {{ $e['color'] ?? '#999' }};">
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
        <span class="item">
            <span class="swatch" style="background: {{ $classColors[$cls] ?? '#999' }}"></span>{{ $cls }}
        </span>
    @endforeach
</div>

<div class="footer-note">Generated {{ $generatedAt }}</div>
</body>
</html>