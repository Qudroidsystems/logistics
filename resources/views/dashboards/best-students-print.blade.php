{{-- resources/views/dashboards/best-students-print.blade.php
     Standalone, print-ready Best Students report (A4). Opened from the
     dashboard's Best Students Explorer with the same criteria. --}}
@php
    $opts  = $report['options'];
    $isSum = $opts['measure'] === 'sum';
    $fmt   = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $schoolName = $school->school_name ?? config('app.name');
    $gradeNote  = 'Grades: A1–F9 (WAEC scale), as on the broadsheet.';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Best Students Report — {{ $term?->term }} {{ $session?->session }}</title>
<style>
    @page { size: A4 portrait; margin: 14mm 12mm 16mm; }
    * { box-sizing: border-box; }
    body { font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #111827; font-size: 11px; margin: 0; background: #e5e7eb; }
    .sheet { background: #fff; max-width: 210mm; margin: 16px auto; padding: 14mm 12mm; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
    .toolbar { position: sticky; top: 0; z-index: 5; background: #1f2937; color: #fff; padding: 10px 16px; display: flex; gap: 10px; align-items: center; justify-content: space-between; }
    .toolbar button { background: #fff; color: #111827; border: 0; border-radius: 6px; padding: 7px 14px; font-weight: 600; cursor: pointer; font-size: 12px; }
    .toolbar button.primary { background: #4f5fff; color: #fff; }
    .toolbar button:focus-visible { outline: 2px solid #93c5fd; outline-offset: 2px; }

    .head { display: flex; align-items: center; gap: 14px; border-bottom: 3px double #111827; padding-bottom: 10px; }
    .head img { width: 64px; height: 64px; object-fit: contain; }
    .head .school { flex: 1; text-align: center; }
    .head h1 { font-size: 18px; margin: 0; letter-spacing: .6px; text-transform: uppercase; }
    .head .addr { font-size: 10.5px; color: #374151; margin-top: 2px; }
    .head .motto { font-size: 10px; font-style: italic; color: #4b5563; margin-top: 2px; }
    .title { text-align: center; margin: 12px 0 4px; font-size: 15px; font-weight: 800; letter-spacing: 1.5px; }
    .subtitle { text-align: center; font-size: 11.5px; color: #374151; margin-bottom: 12px; }

    .criteria { border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 4px 18px; font-size: 10.5px; margin-bottom: 10px; }
    .criteria b { color: #111827; }
    .criteria .full { grid-column: 1 / -1; }

    .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
    .kpi { border: 1px solid #e5e7eb; border-radius: 6px; padding: 6px 8px; text-align: center; }
    .kpi .l { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; }
    .kpi .v { font-size: 16px; font-weight: 800; }

    h2 { font-size: 13px; margin: 16px 0 6px; padding: 5px 8px; background: #111827; color: #fff; border-radius: 4px; }
    h3 { font-size: 11.5px; margin: 12px 0 5px; color: #111827; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
    .meta { font-size: 10px; color: #4b5563; margin: -2px 0 6px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 6px; page-break-inside: auto; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #f3f4f6; font-size: 9.5px; text-transform: uppercase; letter-spacing: .4px; }
    td.num, th.num { text-align: right; white-space: nowrap; }
    td.pos { text-align: center; font-weight: 700; width: 34px; }
    tr.p1 td.pos { background: #fef3c7; }
    tr.p2 td.pos { background: #f3f4f6; }
    tr.p3 td.pos { background: #ffedd5; }
    td.subj { font-weight: 700; background: #fafafa; }
    .muted { color: #6b7280; font-weight: 400; font-size: 9.5px; }
    .empty { text-align: center; color: #6b7280; font-style: italic; }

    .class-block { page-break-before: always; }
    .arm-block { page-break-inside: avoid; }
    .two-col { display: grid; grid-template-columns: 1fr; gap: 6px; }

    .sign { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 34px; }
    .sign div { border-top: 1px solid #111827; padding-top: 4px; text-align: center; font-size: 10.5px; font-weight: 600; }
    .foot { margin-top: 14px; font-size: 9.5px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 6px; }

    @media print {
        body { background: #fff; }
        .toolbar { display: none; }
        .sheet { box-shadow: none; margin: 0; padding: 0; max-width: none; }
        h2, th, tr.p1 td.pos, tr.p2 td.pos, tr.p3 td.pos, td.subj { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <span>Best Students Report · {{ $term?->term }} · {{ $session?->session }}</span>
    <span style="display:flex;gap:8px;">
        <button type="button" class="primary" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
    </span>
</div>

<div class="sheet">
    {{-- ── Header ── --}}
    <div class="head">
        @if($logo)<img src="{{ $logo }}" alt="School logo">@endif
        <div class="school">
            <h1>{{ $schoolName }}</h1>
            @if(!empty($school->school_address))<div class="addr">{{ $school->school_address }}</div>@endif
            @if(!empty($school->school_motto))<div class="motto">"{{ $school->school_motto }}"</div>@endif
        </div>
        @if($logo)<img src="{{ $logo }}" alt="" aria-hidden="true" style="visibility:hidden;">@endif
    </div>

    <div class="title">BEST STUDENTS REPORT</div>
    <div class="subtitle">{{ $term?->term }} · {{ $session?->session }} Academic Session</div>

    {{-- ── Criteria ── --}}
    <div class="criteria">
        <div><b>Score basis:</b> {{ $report['basis_label'] }}</div>
        <div><b>Students ranked by:</b> {{ $report['measure_label'] }}</div>
        <div><b>Shown:</b> top {{ $opts['top_n'] }} overall · top {{ $opts['subject_top_n'] }} per subject</div>
        <div><b>Eligibility:</b>
            {{ $opts['min_subjects'] > 0 ? 'at least ' . $opts['min_subjects'] . ' subjects scored' : 'at least 1 subject scored' }}{{ $opts['exclude_failed'] ? '; no subject below 40' : '' }}
        </div>
        <div class="full"><b>Classes / arms:</b> {{ implode(', ', $report['selection']) }}</div>
        <div class="full"><b>Ties:</b> students with equal scores share a position (1, 2, 2, 4). {{ $gradeNote }}</div>
    </div>

    <div class="kpis">
        <div class="kpi"><div class="l">Students</div><div class="v">{{ number_format($report['students']) }}</div></div>
        <div class="kpi"><div class="l">Ranked</div><div class="v">{{ number_format($report['ranked']) }}</div></div>
        <div class="kpi"><div class="l">Subjects</div><div class="v">{{ $report['subject_count'] }}</div></div>
        <div class="kpi"><div class="l">{{ $isSum ? 'Mean total' : 'Mean average' }}</div><div class="v">{{ $fmt($report['mean'] ?? 0) }}</div></div>
    </div>

    {{-- ── Whole selection ── --}}
    <h2>1. Best students across the whole selection</h2>
    @include('dashboards.partials.bx-print-students', ['entries' => $report['overall'], 'showClass' => true, 'isSum' => $isSum, 'fmt' => $fmt])

    {{-- ── Per class, then per arm ── --}}
    @php $n = 1; @endphp
    @foreach($report['classes'] as $className => $c)
        @php $n++; $multiArm = count($c['arms']) > 1; @endphp
        <div class="class-block">
            <h2>{{ $n }}. {{ $className }}</h2>
            <div class="meta">
                Arms: {{ implode(', ', array_keys($c['arms'])) }} · {{ $c['students'] }} students · {{ $c['ranked'] }} ranked ·
                {{ $isSum ? 'mean total' : 'mean average' }} {{ $fmt($c['mean']) }}
            </div>

            <h3>{{ $n }}.1 {{ $className }} — best students{{ $multiArm ? ' (all selected arms together)' : '' }}</h3>
            @include('dashboards.partials.bx-print-students', ['entries' => $c['top'], 'showClass' => $multiArm, 'isSum' => $isSum, 'fmt' => $fmt])

            <h3>{{ $n }}.2 {{ $className }} — best in each subject{{ $multiArm ? ' (class-wide)' : '' }}</h3>
            @include('dashboards.partials.bx-print-subjects', ['subjects' => $c['subjects'], 'showArm' => $multiArm, 'fmt' => $fmt])

            @if($multiArm)
                @php $a_i = 2; @endphp
                @foreach($c['arms'] as $armLabel => $a)
                    @php $a_i++; @endphp
                    <div class="arm-block">
                        <h3>{{ $n }}.{{ $a_i }} {{ $armLabel }} — best students ({{ $a['students'] }} students, {{ $a['ranked'] }} ranked)</h3>
                        @include('dashboards.partials.bx-print-students', ['entries' => $a['top'], 'showClass' => false, 'isSum' => $isSum, 'fmt' => $fmt])
                        <h3>{{ $n }}.{{ $a_i }} {{ $armLabel }} — best in each subject</h3>
                        @include('dashboards.partials.bx-print-subjects', ['subjects' => $a['subjects'], 'showArm' => false, 'fmt' => $fmt])
                    </div>
                @endforeach
            @endif
        </div>
    @endforeach

    <div class="sign">
        <div>Examinations Officer</div>
        <div>Vice Principal (Academics)</div>
        <div>Principal</div>
    </div>

    <div class="foot">
        Generated {{ $generatedAt }}@if($generatedBy) by {{ $generatedBy }}@endif.
        Scores use the same formulas as the class broadsheet for this term. Unofficial ranking — report-card positions are not changed.
    </div>
</div>

</body>
</html>
