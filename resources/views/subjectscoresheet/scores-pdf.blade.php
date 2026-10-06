<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Scores Sheet</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 16px;
            font-size: 10.5px;
            background: #fff;
            color: #222;
        }

        /* ── Header ─────────────────────────────────────────────── */
        .header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 3px solid #1a3c6e;
            margin-bottom: 12px;
        }
        .school-logo  { width: 65px; height: auto; margin-bottom: 5px; }
        .school-name  { font-size: 18px; font-weight: 700; color: #1a3c6e; text-transform: uppercase; letter-spacing: 1px; }
        .school-detail{ font-size: 9.5px; color: #555; margin: 2px 0; }

        .doc-title-wrap { text-align: center; margin: 10px 0; }
        .doc-title {
            font-size: 13px; font-weight: bold; color: #1a3c6e;
            text-transform: uppercase; letter-spacing: 2px;
            border: 2px solid #1a3c6e; display: inline-block;
            padding: 4px 20px; border-radius: 4px;
        }

        /* ── Info strip ─────────────────────────────────────────── */
        .info-strip {
            display: flex; flex-wrap: wrap;
            background: #f0f4fa;
            border: 1px solid #c5d3e8;
            border-radius: 6px;
            padding: 8px 12px;
            gap: 6px 24px;
            margin-bottom: 12px;
            font-size: 10px;
        }
        .info-item { white-space: nowrap; }
        .info-label { font-weight: 700; color: #1a3c6e; }

        /* ── Summary row ────────────────────────────────────────── */
        .summary-row {
            display: flex; gap: 8px; margin-bottom: 12px;
        }
        .sum-box {
            flex: 1; text-align: center;
            border-radius: 6px; padding: 7px 4px;
            border: 1px solid #ccc;
        }
        .sum-box .sv { font-size: 16px; font-weight: 700; }
        .sum-box .sl { font-size: 9px; color: #555; margin-top: 2px; }

        /* ── Table ──────────────────────────────────────────────── */
        table.scores {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 20px;
        }

         /* Student photo styling */
        .student-photo {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #c5d3e8;
            background: #f0f4fa;
        }
        .photo-cell {
            width: 40px;
            text-align: center;
        }

        .scores th, .scores td {
            border: 1px solid #aab8cc;
            padding: 4px 4px;
            text-align: center;
            vertical-align: middle;
        }
        .scores thead tr { background: #1a3c6e; color: #fff; }
        .scores thead th { font-weight: 600; font-size: 9.5px; }
        .scores tbody tr:nth-child(even) { background: #f5f8fd; }
        .scores td.name-col { text-align: left; padding-left: 6px; min-width: 130px; }

        /* Grade colouring */
        .g-A, .g-A1 { color: #166534; font-weight: 700; }
        .g-B, .g-B2, .g-B3 { color: #1d4ed8; font-weight: 700; }
        .g-C, .g-C4, .g-C5, .g-C6 { color: #6d28d9; font-weight: 700; }
        .g-D, .g-D7, .g-E8 { color: #b45309; font-weight: 700; }
        .g-F, .g-F9 { color: #dc2626; font-weight: 700; }

        /* ── Grade distribution bar ─────────────────────────────── */
        .grade-section { margin-bottom: 14px; }
        .grade-section h4 { font-size: 10px; font-weight: 700; color: #1a3c6e; margin-bottom: 6px; }
        .grade-row { display: flex; gap: 6px; flex-wrap: wrap; }
        .grade-chip {
            border-radius: 5px; padding: 4px 8px;
            font-weight: 700; font-size: 10px;
            border: 1px solid currentColor;
        }

        /* ── Footer / Signatures ────────────────────────────────── */
        .sig-section {
            display: flex; gap: 12px; margin-top: 16px;
            border-top: 1px solid #c5d3e8; padding-top: 12px;
        }
        .sig-box { flex: 1; text-align: center; }
        .sig-title { font-weight: 700; color: #1a3c6e; font-size: 10px; margin-bottom: 20px; }
        .sig-line { border-top: 1px solid #333; padding-top: 4px; font-size: 9px; color: #555; }

        /* ── Page breaks ────────────────────────────────────────── */
        @media print {
            body { font-size: 9.5px; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>

{{-- ── HEADER ─────────────────────────────────────────────────────── --}}
<div class="header">
    @if($school && $school->school_logo)
        <img src="{{ public_path('storage/'.$school->school_logo) }}" alt="Logo" class="school-logo">
    @endif
    <div class="school-name">{{ $school->school_name ?? 'School Name' }}</div>
    @if($school)
        @if($school->school_address)<div class="school-detail">{{ $school->school_address }}</div>@endif
        @if($school->school_phone)<div class="school-detail">Tel: {{ $school->school_phone }}</div>@endif
        @if($school->school_email)<div class="school-detail">Email: {{ $school->school_email }}</div>@endif
        @if($school->school_motto)<div class="school-detail"><em>"{{ $school->school_motto }}"</em></div>@endif
    @endif
</div>

<div class="doc-title-wrap"><span class="doc-title">SUBJECT SCORES REPORT</span></div>

{{-- ── INFO STRIP ─────────────────────────────────────────────────── --}}
@if($classInfo)
@php
    $total    = $broadsheets->count();
    $passed   = $broadsheets->filter(fn($b) => ($b->cum ?? 0) >= 40)->count();
    $failed   = $total - $passed;
    $avg      = $total > 0 ? round($broadsheets->avg('cum'), 1) : 0;
    $highest  = $total > 0 ? round($broadsheets->max('cum'), 1) : 0;
    $lowest   = $total > 0 ? round($broadsheets->min('cum'), 1) : 0;
    $passRate = $total > 0 ? round($passed / $total * 100) : 0;

    $gradeDist = $broadsheets->groupBy('grade')->map->count()->sortKeysDesc();
    $gradeColorMap = [
        'A'=>'#166534','A1'=>'#166534',
        'B'=>'#1d4ed8','B2'=>'#1d4ed8','B3'=>'#3b82f6',
        'C'=>'#6d28d9','C4'=>'#6d28d9','C5'=>'#8b5cf6','C6'=>'#a78bfa',
        'D'=>'#b45309','D7'=>'#b45309','E8'=>'#d97706',
        'F'=>'#dc2626','F9'=>'#dc2626',
    ];
@endphp
<div class="info-strip">
    <div class="info-item"><span class="info-label">Subject:</span> {{ $classInfo->subject }} ({{ $classInfo->subject_code }})</div>
    <div class="info-item"><span class="info-label">Class:</span> {{ $classInfo->schoolclass }} {{ $classInfo->arm }}</div>
    <div class="info-item"><span class="info-label">Teacher:</span> {{ $teacherName ?? 'N/A' }}</div>
    <div class="info-item"><span class="info-label">Term:</span> {{ $classInfo->term ?? '-' }}</div>
    <div class="info-item"><span class="info-label">Session:</span> {{ $classInfo->session ?? '-' }}</div>
    <div class="info-item"><span class="info-label">Date:</span> {{ date('d M Y') }}</div>
</div>

{{-- ── SUMMARY BOXES ───────────────────────────────────────────────── --}}
<div class="summary-row">
    <div class="sum-box" style="background:#eff6ff;border-color:#bfdbfe;">
        <div class="sv" style="color:#1d4ed8;">{{ $total }}</div>
        <div class="sl">Students</div>
    </div>
    <div class="sum-box" style="background:#f0fdf4;border-color:#bbf7d0;">
        <div class="sv" style="color:#166534;">{{ $passed }}</div>
        <div class="sl">Passed</div>
    </div>
    <div class="sum-box" style="background:#fef2f2;border-color:#fecaca;">
        <div class="sv" style="color:#dc2626;">{{ $failed }}</div>
        <div class="sl">Failed</div>
    </div>
    <div class="sum-box" style="background:#fffbeb;border-color:#fde68a;">
        <div class="sv" style="color:#b45309;">{{ $avg }}</div>
        <div class="sl">Average</div>
    </div>
    <div class="sum-box" style="background:#f5f3ff;border-color:#ddd6fe;">
        <div class="sv" style="color:#6d28d9;">{{ $highest }}</div>
        <div class="sl">Highest</div>
    </div>
    <div class="sum-box" style="background:#fef2f2;border-color:#fecaca;">
        <div class="sv" style="color:#dc2626;">{{ $lowest }}</div>
        <div class="sl">Lowest</div>
    </div>
    <div class="sum-box" style="background:#f0fdf4;border-color:#bbf7d0;">
        <div class="sv" style="color:#166534;">{{ $passRate }}%</div>
        <div class="sl">Pass Rate</div>
    </div>
</div>

{{-- ── GRADE DISTRIBUTION ─────────────────────────────────────────── --}}
@if($gradeDist->isNotEmpty())
<div class="grade-section">
    <h4>Grade Distribution</h4>
    <div class="grade-row">
        @foreach($gradeDist as $grade => $count)
            @php $col = $gradeColorMap[$grade] ?? '#6b7280'; $pct = $total > 0 ? round($count/$total*100) : 0; @endphp
            <div class="grade-chip" style="color:{{ $col }};background:{{ $col }}18;border-color:{{ $col }}60;">
                {{ $grade }}: {{ $count }} ({{ $pct }}%)
            </div>
        @endforeach
    </div>
</div>
@endif
@endif {{-- classInfo --}}

{{-- ── SCORES TABLE ───────────────────────────────────────────────── --}}
<table class="scores">
    <thead>
        <tr>
            <th style="width:26px;">S/N</th>
             <th style="width:40px;">Photo</th>
            <th style="min-width:70px;">Adm. No</th>
            <th style="min-width:130px;">Student Name</th>
            @foreach($assessments as $assessment)
                <th>{{ $assessment->name }}<br><span style="font-weight:400;font-size:8.5px;">({{ number_format($assessment->max_score, 0) }})</span></th>
            @endforeach
            <th style="background:#163275;">Total<br><span style="font-weight:400;font-size:8.5px;">({{ number_format($assessments->sum('max_score'), 0) }})</span></th>
            <th>BF</th>
            <th>Cum</th>
            <th>Grade</th>
            <th>Pos</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        @forelse($broadsheets as $idx => $student)
            @php
                $rowTotal = 0;
                foreach($assessments as $a) {
                    $so = $student->assessmentScores->where('assessment_id', $a->id)->first();
                    $rowTotal += $so ? $so->score : 0;
                }
                $cum      = $student->cum ?? 0;
                $grade    = $student->grade ?? '-';
                $gradeClass = 'g-' . str_replace(' ', '', $grade);
                $pos      = $student->position ?? '-';
                $posText  = is_numeric($pos) ? $pos.(['th','st','nd','rd'][($pos%10 <= 3 && ($pos%100 < 11 || $pos%100 > 13)) ? $pos%10 : 0] ?? 'th') : $pos;

                 // Get student photo path
                $photoPath = $student->picture ?? null;
                $hasPhoto = $photoPath && file_exists(public_path('storage/student_avatars/' . basename($photoPath)));
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td class="photo-cell">
                    @if($hasPhoto)
                        <img src="{{ public_path('storage/student_avatars/' . basename($photoPath)) }}"
                             class="student-photo"
                             alt="Photo">
                    @else
                        <img src="{{ public_path('storage/student_avatars/unnamed.jpg') }}"
                             class="student-photo"
                             alt="No Photo">
                    @endif
                </td>
                <td>{{ $student->admissionno ?? '-' }}</td>
                <td class="name-col"><strong>{{ $student->lname ?? '' }}</strong> {{ $student->fname ?? '' }} {{ $student->mname ?? '' }}</td>
                @foreach($assessments as $assessment)
                    @php $so = $student->assessmentScores->where('assessment_id', $assessment->id)->first(); @endphp
                    <td>{{ $so ? number_format($so->score, 1) : '0.0' }}</td>
                @endforeach
                <td style="font-weight:700;">{{ number_format($rowTotal, 1) }}</td>
                <td>{{ number_format($student->bf ?? 0, 1) }}</td>
                <td style="font-weight:700;
                    @if($cum >= 70) color:#166534; @elseif($cum >= 50) color:#1d4ed8; @elseif($cum >= 40) color:#b45309; @else color:#dc2626; @endif">
                    {{ number_format($cum, 1) }}
                </td>
                <td class="{{ $gradeClass }}">{{ $grade }}</td>
                <td>{{ $posText }}</td>
                <td>{{ $student->remark ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ 5 + $assessments->count() + 5 }}" style="text-align:center;padding:12px;">No students found.</td>
            </tr>
        @endforelse
    </tbody>
    @if($broadsheets->isNotEmpty())
    <tfoot>
        <tr style="background:#e8f0fe;font-weight:700;font-size:9.5px;">
            <td colspan="3" style="text-align:right;font-style:italic;">
                {{ $broadsheets->count() }} student(s) &nbsp;|&nbsp; Class Avg: {{ $avg }}
            </td>
            @foreach($assessments as $a)
                @php $aSum = $broadsheets->sum(fn($b) => optional($b->assessmentScores->where('assessment_id',$a->id)->first())->score ?? 0); @endphp
                <td>{{ number_format($aSum / max($broadsheets->count(),1), 1) }}</td>
            @endforeach
            <td>-</td>
            <td>-</td>
            <td>{{ $avg }}</td>
            <td colspan="3">Pass: {{ $passRate }}%</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── SIGNATURES ─────────────────────────────────────────────────── --}}
<div class="sig-section">
    <div class="sig-box">
        <div class="sig-title">Subject Teacher</div>
        <div class="sig-line">{{ $teacherName ?? '________________________' }}</div>
    </div>
    <div class="sig-box">
        <div class="sig-title">H.O.D</div>
        <div class="sig-line">________________________</div>
    </div>
    <div class="sig-box">
        <div class="sig-title">Principal</div>
        <div class="sig-line">________________________</div>
    </div>
    <div class="sig-box">
        <div class="sig-title">Date</div>
        <div class="sig-line">{{ date('d / m / Y') }}</div>
    </div>
</div>

</body>
</html>
