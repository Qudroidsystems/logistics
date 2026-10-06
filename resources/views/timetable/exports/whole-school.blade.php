<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 16px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: {{ 10 * ($bodyScale ?? 1.0) }}px; color:#1E293B; }

    /* ── School header ── */
    .school-header { border: 2px solid #0f2342; border-radius: 6px; overflow: hidden; margin-bottom: 10px; }
    .school-header table { width:100%; border-collapse:collapse; }
    .school-header .logo-cell { width:64px; text-align:center; vertical-align:middle; padding:6px; background:#0f2342; }
    .school-header .logo-cell img { width:52px; height:52px; border-radius:50%; object-fit:contain; border:2px solid rgba(255,255,255,.35); }
    .school-header-top { background:#0f2342; color:#fff; }
    .school-header-top .name { font-size: {{ 16 * ($bodyScale ?? 1.0) }}px; font-weight:700; text-transform:uppercase; letter-spacing:1px; text-align:center; }
    .school-header-top .addr { font-size: {{ 8.5 * ($bodyScale ?? 1.0) }}px; opacity:.85; text-align:center; margin-top:2px; }
    .school-header-top .motto { font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; font-style:italic; opacity:.7; text-align:center; margin-top:2px; }
    .school-header-top .contact { font-size: {{ 8 * ($bodyScale ?? 1.0) }}px; opacity:.85; text-align:center; margin-top:3px; }
    .school-header-top .contact span { display:inline-block; margin:0 6px; }
    .school-header-bottom { background:#1565C0; color:#fff; text-align:center; padding:6px; font-size: {{ 12 * ($bodyScale ?? 1.0) }}px; font-weight:700; letter-spacing:1.5px; }

    /* ── Run metadata block ── */
    .run-meta-block {
        border: 1px solid #CBD5E1;
        border-left: 3px solid #1565C0;
        border-radius: 4px;
        padding: 6px 10px;
        margin-bottom: 10px;
        background: #F8FAFC;
        font-size: {{ 10 * ($bodyScale ?? 1.0) }}px;
    }
    .run-meta-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        flex-wrap: wrap;
    }
    .run-meta-name {
        font-size: {{ 12 * ($bodyScale ?? 1.0) }}px;
        color: #0f2342;
    }
    .run-meta-code {
        color: #64748B;
        margin-left: 8px;
        font-family: monospace;
        letter-spacing: 0.5px;
    }
    .run-meta-right {
        color: #64748B;
        font-size: {{ 9 * ($bodyScale ?? 1.0) }}px;
    }
    .run-meta-desc {
        margin-top: 4px;
        color: #475569;
        line-height: 1.35;
    }

    /* ── Generation rules appendix ── */
    .run-rules-block {
        border: 1px solid #FDE68A;
        border-radius: 4px;
        padding: 6px 10px;
        margin-bottom: 10px;
        background: #FFFBEB;
        font-size: {{ 9 * ($bodyScale ?? 1.0) }}px;
        color: #92400E;
    }
    .run-rule-item {
        display: inline-block;
        margin-left: 8px;
        margin-right: 2px;
        white-space: nowrap;
    }

    /* ── Summary strip ── */
    .summary-strip { display:table; width:100%; border:1px solid #CBD5E1; border-radius:6px; background:#F8FAFC; margin-bottom:10px; }
    .summary-strip .s-cell { display:table-cell; text-align:center; padding:6px 10px; border-right:1px solid #CBD5E1; }
    .summary-strip .s-cell:last-child { border-right:none; }
    .summary-strip .s-lbl { font-size:8px; color:#64748B; text-transform:uppercase; }
    .summary-strip .s-val { font-size:13px; font-weight:700; color:#0f2342; }

    /* ── Class page ── */
    .class-page { page-break-after: always; }
    .class-page:last-child { page-break-after: auto; }
    .class-title { background:#1565C0; color:#fff; padding:6px 10px; font-size:13px; font-weight:bold; border-radius:4px; margin-bottom:8px; display:table; width:100%; }
    .class-title .ct-name { display:table-cell; }
    .class-title .ct-stats { display:table-cell; text-align:right; font-size:9px; font-weight:400; opacity:.9; }

    table.grid { width:100%; border-collapse: collapse; }
    table.grid th, table.grid td { border:1px solid #CBD5E1; padding:4px; text-align:center; vertical-align:middle; }
    table.grid th { color:#fff; font-size:9px; text-transform:uppercase; }
    table.grid td.period-col { background:#F8FAFC; text-align:left; font-weight:bold; white-space:nowrap; }
    .subject { font-weight:bold; font-size:9.5px; }
    .teacher { font-size:8.5px; color:#475569; }
    .room { font-size:8px; color:#94A3B8; }
    .free { color:#CBD5E1; font-size:9px; }
    .break-cell { background:#FFFBEB; color:#D97706; font-weight:bold; font-size:9px; }

    /* ── Per-class stats footer ── */
    .class-stats { display:table; width:100%; margin-top:8px; border:1px solid #E2E8F0; border-radius:6px; background:#F8FAFC; }
    .class-stats .cs-cell { display:table-cell; text-align:center; padding:5px 4px; border-right:1px solid #E2E8F0; }
    .class-stats .cs-cell:last-child { border-right:none; }
    .class-stats .cs-lbl { font-size:7.5px; color:#64748B; text-transform:uppercase; }
    .class-stats .cs-val { font-size:11px; font-weight:700; color:#1565C0; }
</style>
</head>
<body>

@php
    $isVertical = ($orientation ?? 'horizontal') === 'vertical';

    $schoolInfo = $schoolInfo ?? (object) [];
    $logo       = $schoolInfo->logo_base64 ?? null;
    $schoolName = $schoolInfo->school_name  ?? 'School';
    $address    = $schoolInfo->school_address ?? null;
    $motto      = $schoolInfo->school_motto   ?? null;
    $phones     = $schoolInfo->formatted_phones ?? null;
    $email      = $schoolInfo->school_email   ?? null;
    $website    = $schoolInfo->school_website ?? null;
@endphp

<div class="school-header">
    <table>
        <tr class="school-header-top">
            <td class="logo-cell">
                @if($logo)
                    <img src="{{ $logo }}" alt="Logo">
                @endif
            </td>
            <td>
                <div class="name">{{ $schoolName }}</div>
                @if($address)
                    <div class="addr">{{ $address }}</div>
                @endif
                @if($motto)
                    <div class="motto">"{{ $motto }}"</div>
                @endif
                @if($phones || $email || $website)
                    <div class="contact">
                        @if($phones && $phones !== '-')<span>📞 {{ $phones }}</span>@endif
                        @if($email)<span>✉ {{ $email }}</span>@endif
                        @if($website)<span>🌐 {{ $website }}</span>@endif
                    </div>
                @endif
            </td>
            <td style="width:64px;"></td>
        </tr>
    </table>
    <div class="school-header-bottom">
        Whole School Timetable — {{ $sessionName }} · {{ $termName }}
        @if(!empty($paperSize)) · {{ strtoupper($paperSize) }} @endif
    </div>
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

@if(!empty($overallStats))
<div class="summary-strip">
    <div class="s-cell"><div class="s-lbl">Classes</div><div class="s-val">{{ $overallStats['total_classes'] ?? '—' }}</div></div>
    <div class="s-cell"><div class="s-lbl">Teachers Involved</div><div class="s-val">{{ $overallStats['total_teachers'] ?? '—' }}</div></div>
    <div class="s-cell"><div class="s-lbl">Avg Fill Rate</div><div class="s-val">{{ $overallStats['avg_fill_rate'] ?? 0 }}%</div></div>
    <div class="s-cell">
        <div class="s-lbl">Conflicts</div>
        <div class="s-val" style="color:{{ ($overallStats['total_conflicts'] ?? 0) > 0 ? '#DC2626' : '#16A34A' }}">
            {{ $overallStats['total_conflicts'] ?? 0 }}
        </div>
    </div>
    <div class="s-cell"><div class="s-lbl">Generated</div><div class="s-val" style="font-size:10px;">{{ $generatedAt }}</div></div>
</div>
@endif

@foreach ($allTimetables as $tt)
<div class="class-page">
    <div class="class-title">
        <span class="ct-name">{{ $tt['class_name'] }}</span>
        <span class="ct-stats">
            {{ $tt['stats']['filled_slots'] ?? 0 }}/{{ $tt['stats']['total_slots'] ?? 0 }} slots filled
            · {{ $tt['stats']['fill_rate'] ?? 0 }}% fill rate
        </span>
    </div>

    @if ($isVertical)
        <table class="grid">
            <thead>
                <tr>
                    <th style="background:#1E293B">Day</th>
                    @foreach ($tt['periods'] as $period)
                        <th style="background:#1565C0">
                            {{ $period->name }}<br>
                            <span style="font-weight:normal">{{ substr($period->start_time,0,5) }}–{{ substr($period->end_time,0,5) }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($tt['days'] as $day)
                <tr>
                    <td class="period-col" style="background:{{ $dayColors[$day] ?? '#1E293B' }};color:#fff">{{ $day }}</td>
                    @foreach ($tt['periods'] as $period)
                        @php
                            $meta    = $tt['day_meta'][$day][$period->id] ?? null;
                            $isBreak = in_array($period->type, ['short_break','long_break','assembly'])
                                       && ($meta['effective_type'] ?? $period->type) !== 'lesson';
                            $slot    = $tt['grid'][$period->id][$day] ?? null;
                        @endphp
                        @if ($isBreak)
                            <td class="break-cell">{{ ucfirst(str_replace('_',' ',$period->type)) }}</td>
                        @elseif (!$meta || !($meta['applicable'] ?? true))
                            <td>—</td>
                        @elseif (!$slot || $slot['is_free'])
                            <td class="free">Free</td>
                        @else
                            <td>
                                <div class="subject">{{ $slot['subject'] }}</div>
                                @if($slot['teacher'])<div class="teacher">{{ $slot['teacher'] }}</div>@endif
                                @if($slot['room'])<div class="room">{{ $slot['room'] }}</div>@endif
                            </td>
                        @endif
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th style="background:#1E293B">Period</th>
                    @foreach ($tt['days'] as $day)
                        <th style="background:{{ $dayColors[$day] ?? '#1565C0' }}">{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($tt['periods'] as $period)
                <tr>
                    <td class="period-col">
                        {{ $period->name }}<br>
                        <span style="font-weight:normal;color:#94A3B8">{{ substr($period->start_time,0,5) }}–{{ substr($period->end_time,0,5) }}</span>
                    </td>
                    @foreach ($tt['days'] as $day)
                        @php
                            $meta    = $tt['day_meta'][$day][$period->id] ?? null;
                            $isBreak = in_array($period->type, ['short_break','long_break','assembly'])
                                       && ($meta['effective_type'] ?? $period->type) !== 'lesson';
                            $slot    = $tt['grid'][$period->id][$day] ?? null;
                        @endphp
                        @if ($isBreak)
                            <td class="break-cell">{{ ucfirst(str_replace('_',' ',$period->type)) }}</td>
                        @elseif (!$meta || !($meta['applicable'] ?? true))
                            <td>—</td>
                        @elseif (!$slot || $slot['is_free'])
                            <td class="free">Free</td>
                        @else
                            <td>
                                <div class="subject">{{ $slot['subject'] }}</div>
                                @if($slot['teacher'])<div class="teacher">{{ $slot['teacher'] }}</div>@endif
                                @if($slot['room'])<div class="room">{{ $slot['room'] }}</div>@endif
                            </td>
                        @endif
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="class-stats">
        <div class="cs-cell"><div class="cs-lbl">Total Slots</div><div class="cs-val">{{ $tt['stats']['total_slots'] ?? 0 }}</div></div>
        <div class="cs-cell"><div class="cs-lbl">Filled</div><div class="cs-val" style="color:#16A34A">{{ $tt['stats']['filled_slots'] ?? 0 }}</div></div>
        <div class="cs-cell"><div class="cs-lbl">Free</div><div class="cs-val" style="color:#94A3B8">{{ $tt['stats']['free_slots'] ?? 0 }}</div></div>
        <div class="cs-cell"><div class="cs-lbl">Fill Rate</div><div class="cs-val">{{ $tt['stats']['fill_rate'] ?? 0 }}%</div></div>
        <div class="cs-cell"><div class="cs-lbl">Subjects</div><div class="cs-val">{{ $tt['stats']['subject_count'] ?? 0 }}</div></div>
        <div class="cs-cell"><div class="cs-lbl">Teachers</div><div class="cs-val">{{ $tt['stats']['teacher_count'] ?? 0 }}</div></div>
        <div class="cs-cell"><div class="cs-lbl">Rooms Used</div><div class="cs-val">{{ $tt['stats']['room_count'] ?? 0 }}</div></div>
    </div>
</div>
@endforeach

</body>
</html>