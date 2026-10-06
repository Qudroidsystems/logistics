<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Transcript</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }

@page {
    size: A4 portrait;
    margin: 10mm 12mm;
}

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 7.5pt;
    color: #1a1a2e;
    background: #fff;
    line-height: 1.3;
    width: 100%;
    position: relative;
}

/* ── Watermark ───────────────────────────────────────────────────────── */
.watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font-size: 52pt;
    font-weight: 900;
    color: rgba(30,58,95,0.055);
    white-space: nowrap;
    letter-spacing: 4px;
    text-transform: uppercase;
    pointer-events: none;
    z-index: 0;
}
.watermark-logo {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    width: 320px;
    height: 320px;
    object-fit: contain;
    opacity: 0.035;
    z-index: 0;
}

/* ── School header ───────────────────────────────────────────────────── */
.school-header {
    border-bottom: 3px solid #1e3a5f;
    padding-bottom: 8px;
    margin-bottom: 8px;
}
.header-inner { display: table; width: 100%; }
.header-logo-cell {
    display: table-cell;
    width: 80px;
    vertical-align: middle;
    text-align: center;
}
.header-logo-cell img {
    width: 68px; height: 68px;
    object-fit: contain;
    border-radius: 50%;
    border: 2.5px solid #1e3a5f;
}
.header-text-cell {
    display: table-cell;
    vertical-align: middle;
    text-align: center;
    padding: 0 8px;
}
.school-name {
    font-size: 14pt;
    font-weight: bold;
    color: #1e3a5f;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.school-address { font-size: 7.5pt; color: #555; margin-top: 2px; }
.school-contact { font-size: 7pt;   color: #666; margin-top: 1px; }
.school-motto   { font-size: 7.5pt; color: #2563eb; font-style: italic; margin-top: 3px; font-weight: 600; }

/* ── Title strip ─────────────────────────────────────────────────────── */
.doc-title-strip {
    background: #1e3a5f;
    color: white;
    text-align: center;
    padding: 6px 8px;
    font-size: 11pt;
    font-weight: bold;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.doc-subtitle {
    text-align: center;
    color: #6b7280;
    font-size: 7pt;
    margin-bottom: 8px;
    margin-top: -5px;
}

/* ── Copy stamp ──────────────────────────────────────────────────────── */
.copy-stamp {
    display: inline-block;
    border: 2px solid;
    padding: 2px 8px;
    font-size: 8pt;
    font-weight: bold;
    letter-spacing: 1px;
    border-radius: 3px;
    text-transform: uppercase;
}
.copy-stamp.original  { color: #1e3a5f; border-color: #1e3a5f; }
.copy-stamp.duplicate { color: #dc2626; border-color: #dc2626; }

/* ── Student info box ────────────────────────────────────────────────── */
.student-box {
    border: 2px solid #1e3a5f;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 10px;
    display: table;
    width: 100%;
}
.student-photo-cell {
    display: table-cell;
    width: 85px;
    background: #1e3a5f;
    vertical-align: middle;
    text-align: center;
    padding: 10px 8px;
}
.student-photo-cell img {
    width: 68px; height: 68px;
    border-radius: 50%;
    object-fit: cover;
    border: 2.5px solid white;
}
.student-info-cell {
    display: table-cell;
    vertical-align: middle;
    padding: 8px 12px;
    background: #f8fafc;
}
.student-info-grid { display: table; width: 100%; }
.info-row { display: table-row; }
.info-label {
    display: table-cell;
    font-size: 6pt;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 2px 12px 2px 0;
    white-space: nowrap;
    width: 90px;
}
.info-value {
    display: table-cell;
    font-size: 8.5pt;
    font-weight: bold;
    color: #1e3a5f;
    padding: 2px 0;
}
.info-value.blue { color: #2563eb; }
.info-value.green { color: #16a34a; }

/* ── Grade key ───────────────────────────────────────────────────────── */
.grade-key {
    display: table;
    width: 100%;
    margin-bottom: 8px;
    border: 1px solid #e2e8f0;
    padding: 3px 6px;
    background: #fafafa;
}
.grade-key-title {
    display: table-cell;
    font-size: 6pt;
    font-weight: bold;
    color: #1e3a5f;
    width: 70px;
    vertical-align: middle;
}
.grade-key-items { display: table-cell; vertical-align: middle; }
.grade-badge {
    display: inline-block;
    padding: 1px 4px;
    border-radius: 2px;
    font-weight: bold;
    color: white;
    font-size: 6pt;
    margin-right: 5px;
}

/* ── Session block ───────────────────────────────────────────────────── */
.session-block { margin-bottom: 10px; }
.session-title {
    display: table;
    width: 100%;
    margin-bottom: 6px;
}
.session-label {
    display: table-cell;
    background: #1e3a5f;
    color: white;
    padding: 3px 12px;
    font-size: 8pt;
    font-weight: bold;
    border-radius: 10px 0 0 10px;
    white-space: nowrap;
    width: 1%;
}
.session-line {
    display: table-cell;
    vertical-align: middle;
    padding-left: 6px;
}
.session-line-inner {
    height: 2px;
    background: #1e3a5f;
    opacity: 0.2;
}

/* ── Term block ──────────────────────────────────────────────────────── */
.term-block {
    border-left: 3px solid #bfdbfe;
    padding-left: 8px;
    margin-bottom: 8px;
    page-break-inside: avoid;
}
.term-header {
    display: table;
    width: 100%;
    margin-bottom: 4px;
}
.term-title-cell { display: table-cell; vertical-align: middle; }
.term-name {
    font-size: 8.5pt;
    font-weight: bold;
    color: #1e3a5f;
}
.term-class {
    font-size: 7pt;
    color: #6b7280;
    margin-left: 6px;
}
.term-badges-cell { display: table-cell; text-align: right; vertical-align: middle; }
.badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 3px;
    font-size: 6.5pt;
    font-weight: bold;
    margin-left: 4px;
}
.badge-position { background: #fef9c3; color: #92400e; }
.badge-promoted { background: #dcfce7; color: #166534; }
.badge-repeated { background: #fee2e2; color: #991b1b; }

/* ── Subject table ───────────────────────────────────────────────────── */
.subject-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7pt;
    margin-bottom: 4px;
    page-break-inside: avoid;
}
.subject-table thead tr th {
    background: #1e3a5f;
    color: white;
    padding: 3px 4px;
    text-align: center;
    border: 0.5px solid #2563eb44;
    font-size: 6.5pt;
    white-space: nowrap;
}
.subject-table thead tr th.subj-col {
    text-align: left;
    padding-left: 5px;
    min-width: 100px;
    background: #0f2040;
}
.subject-table thead tr.asmt-header th {
    background: #1a3d6a;
    color: #a8d4ef;
    padding: 2px 3px;
    font-size: 6pt;
    border: 0.5px solid #2563eb22;
}
.subject-table thead tr.asmt-header th.asmt-first {
    border-left: 1.5px solid #2563eb;
}

.subject-table tbody tr:nth-child(odd)  { background: #ffffff; }
.subject-table tbody tr:nth-child(even) { background: #f0f4fa; }
.subject-table tbody td {
    padding: 2.5px 3px;
    border: 0.5px solid #c5d3e8;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}
.subject-table tbody td.subj-name {
    text-align: left;
    padding-left: 5px;
    font-weight: 600;
}
.subject-table tfoot td {
    background: #f0f4fa;
    font-weight: bold;
    padding: 3px 4px;
    border: 0.5px solid #c5d3e8;
    font-size: 6.5pt;
}

/* Grade colouring */
.g-a1 { background: #dcfce7 !important; color: #166534; font-weight: bold; }
.g-b2 { background: #dbeafe !important; color: #1e40af; }
.g-b3 { background: #e0eeff !important; color: #1e40af; }
.g-c4 { background: #fef9c3 !important; color: #854d0e; }
.g-c5 { background: #fef3c7 !important; color: #92400e; }
.g-c6 { background: #fde68a !important; color: #78350f; }
.g-d7 { background: #ffedd5 !important; color: #9a3412; }
.g-e8 { background: #fed7aa !important; color: #9a3412; }
.g-f9 { background: #fee2e2 !important; color: #991b1b; font-weight: bold; }

/* ── Principal's comment ─────────────────────────────────────────────── */
.principal-comment {
    background: #fef9c3;
    border-left: 3px solid #d97706;
    padding: 3px 7px;
    font-size: 6.5pt;
    margin-bottom: 4px;
    border-radius: 0 3px 3px 0;
}
.comment-label {
    font-size: 6pt;
    font-weight: bold;
    text-transform: uppercase;
    color: #92400e;
    display: block;
    margin-bottom: 1px;
}

/* ── Grade distribution ──────────────────────────────────────────────── */
.grade-dist-section {
    margin-top: 8px;
    border-top: 1.5px solid #e2e8f0;
    padding-top: 6px;
}
.grade-dist-title {
    font-size: 7pt;
    font-weight: bold;
    color: #1e3a5f;
    margin-bottom: 5px;
}
.grade-dist-grid { display: table; }
.grade-dist-item {
    display: table-cell;
    text-align: center;
    padding: 4px 7px;
    border-radius: 4px;
    margin-right: 5px;
    border: 1px solid #e2e8f0;
    min-width: 40px;
}
.grade-dist-grade { font-size: 10pt; font-weight: bold; display: block; }
.grade-dist-count { font-size: 6pt; color: #6b7280; display: block; }

/* ── Signatures ──────────────────────────────────────────────────────── */
.signature-section {
    margin-top: 14px;
    border-top: 1px solid #e2e8f0;
    padding-top: 5px;
    display: table;
    width: 100%;
}
.sig-cell { display: table-cell; text-align: center; padding: 0 6px; width: 33.33%; }
.sig-line {
    border-top: 1px solid #374151;
    padding-top: 4px;
    font-size: 7pt;
    color: #374151;
    margin-top: 24px;
}

/* ── Footer ──────────────────────────────────────────────────────────── */
.doc-footer {
    margin-top: 8px;
    border-top: 1px dashed #e2e8f0;
    padding-top: 4px;
    display: table;
    width: 100%;
    font-size: 6pt;
    color: #9ca3af;
}
.footer-left  { display: table-cell; text-align: left; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>

{{-- Watermark --}}
@if(!empty($school_logo_base64))
<img src="{{ $school_logo_base64 }}" class="watermark-logo" alt="">
@endif
<div class="watermark">{{ strtoupper($copy_type ?? 'original') }} COPY</div>

{{-- School Header --}}
<div class="school-header">
    <div class="header-inner">
        <div class="header-logo-cell">
            @if(!empty($school_logo_base64))
                <img src="{{ $school_logo_base64 }}" alt="Logo">
            @endif
        </div>
        <div class="header-text-cell">
            <div class="school-name">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</div>
            @if(!empty($schoolInfo->school_address))
                <div class="school-address">{{ $schoolInfo->school_address }}</div>
            @endif
            @if(!empty($schoolInfo->school_phone) || !empty($schoolInfo->school_email))
                <div class="school-contact">
                    @if(!empty($schoolInfo->school_phone)) Tel: {{ $schoolInfo->school_phone }} @endif
                    @if(!empty($schoolInfo->school_email)) &nbsp;| {{ $schoolInfo->school_email }} @endif
                </div>
            @endif
            @if(!empty($schoolInfo->school_motto))
                <div class="school-motto">"{{ $schoolInfo->school_motto }}"</div>
            @endif
        </div>
        <div class="header-logo-cell">
            <span class="copy-stamp {{ $copy_type === 'duplicate' ? 'duplicate' : 'original' }}">
                {{ strtoupper($copy_type ?? 'original') }}
            </span>
        </div>
    </div>
</div>

{{-- Title --}}
<div class="doc-title-strip">ACADEMIC TRANSCRIPT</div>
<div class="doc-subtitle">
    {{ ucfirst($type) }} Record &nbsp;·&nbsp; Generated: {{ $generated_at ?? now()->format('d M Y, H:i') }}
    &nbsp;·&nbsp; By: {{ $generated_by ?? 'System' }}
</div>

{{-- Student Info --}}
<div class="student-box">
    <div class="student-photo-cell">
        <img src="{{ $student->picture ? asset('storage/student_avatars/'.basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
             alt="Photo"
             onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'">
    </div>
    <div class="student-info-cell">
        <div class="student-info-grid">
            <div class="info-row">
                <div class="info-label">Full Name</div>
                <div class="info-value" style="font-size:10pt;">
                    {{ strtoupper($student->lastname) }}, {{ $student->firstname }} {{ $student->othername ?? '' }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Admission No.</div>
                <div class="info-value blue">{{ $student->admissionno }}</div>
                <div class="info-label" style="padding-left:20px;">Gender</div>
                <div class="info-value">{{ $student->gender ?? '—' }}</div>
            </div>
            @if($student->dateofbirth)
            <div class="info-row">
                <div class="info-label">Date of Birth</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($student->dateofbirth)->format('d M Y') }}</div>
                <div class="info-label" style="padding-left:20px;">Sessions on Record</div>
                <div class="info-value green">{{ $totalSessions }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Overall GPA</div>
                <div class="info-value">
                    {{ number_format($overallGpa, 2) }}
                    <span class="grade-badge" style="background:#1e3a5f;margin-left:4px;">{{ $overallGpaGrade }}</span>
                </div>
                <div class="info-label" style="padding-left:20px;">Total Subjects</div>
                <div class="info-value">{{ $totalSubjects }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Grade Key --}}
<div class="grade-key">
    <div class="grade-key-title">GRADING SCALE:</div>
    <div class="grade-key-items">
        @php
        $gradeKey = [
            'A1'=>['75-100','#16a34a'],'B2'=>['70-74','#1d4ed8'],'B3'=>['65-69','#2563eb'],
            'C4'=>['60-64','#d97706'], 'C5'=>['55-59','#b45309'],'C6'=>['50-54','#92400e'],
            'D7'=>['45-49','#ea580c'], 'E8'=>['40-44','#c2410c'],'F9'=>['0-39', '#dc2626'],
        ];
        @endphp
        @foreach($gradeKey as $grade => $info)
            <span class="grade-badge" style="background:{{ $info[1] }};">{{ $grade }}</span>
            <span style="font-size:6pt;margin-right:6px;">{{ $info[0] }}</span>
        @endforeach
        &nbsp;
        <span style="font-size:6pt;color:#555;">
            <strong>BF</strong>=Brought Forward &nbsp;
            <strong>CUM</strong>=Cumulative &nbsp;
            <strong>POS</strong>=Position
        </span>
    </div>
</div>

{{-- ══ Transcript Data ══ --}}
@php
$gradeClassMap = [
    'A1'=>'g-a1','B2'=>'g-b2','B3'=>'g-b3',
    'C4'=>'g-c4','C5'=>'g-c5','C6'=>'g-c6',
    'D7'=>'g-d7','E8'=>'g-e8','F9'=>'g-f9',
];
@endphp

@forelse($transcriptData as $sessionName => $sessionData)
    <div class="session-block">

        {{-- Session heading --}}
        <div class="session-title">
            <div class="session-label">{{ $sessionName }}</div>
            <div class="session-line"><div class="session-line-inner"></div></div>
        </div>

        @foreach($sessionData['terms'] as $termName => $termData)
            @php $assessments = $termData['assessments'] ?? $assessments; @endphp
            <div class="term-block">

                {{-- Term header --}}
                <div class="term-header">
                    <div class="term-title-cell">
                        <span class="term-name">{{ $termName }}</span>
                        <span class="term-class">— {{ $termData['class'] }}</span>
                    </div>
                    {{-- <div class="term-badges-cell">
                        @if($termData['class_position'])
                            <span class="badge badge-position">Position: {{ $termData['class_position'] }}</span>
                        @endif
                        @if($termData['promotion'])
                            @php $isPromoted = strtolower($termData['promotion']) === 'promoted'; @endphp
                            <span class="badge {{ $isPromoted ? 'badge-promoted' : 'badge-repeated' }}">
                                {{ $termData['promotion'] }}
                            </span>
                        @endif
                    </div> --}}
                </div>

                {{-- Subject table --}}
                @if(!empty($termData['subjects']))
                <table class="subject-table">
                    <thead>
                        {{-- Row 1: column headings --}}
                        <tr>
                            <th class="subj-col" rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}">Subject</th>
                            @if($assessments->count() > 0)
                                <th colspan="{{ $assessments->count() }}" style="border-left:1.5px solid #2563eb;">
                                    Assessments
                                </th>
                            @endif
                            <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}">Total</th>
                            <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}">BF</th>
                            <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}">Cum</th>
                            <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}">Grade</th>
                            <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}">Position</th>
                            <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}">Class Avg</th>
                        </tr>
                        {{-- Row 2: individual assessment names (only if assessments exist) --}}
                        @if($assessments->count() > 0)
                        <tr class="asmt-header">
                            @foreach($assessments as $aIdx => $a)
                                <th class="{{ $aIdx === 0 ? 'asmt-first' : '' }}" style="min-width:22px;">
                                    {{ $a->name }}<br>
                                    <span style="font-size:5pt;opacity:.75;">/{{ $a->max_score }}</span>
                                </th>
                            @endforeach
                        </tr>
                        @endif
                    </thead>
                    <tbody>
                        @foreach($termData['subjects'] as $sub)
                        @php $gc = $gradeClassMap[$sub['grade']] ?? ''; @endphp
                        <tr>
                            <td class="subj-name">
                                {{ $sub['subject'] }}
                                @if(!empty($sub['subject_code']))
                                    <span style="color:#9ca3af;font-size:5.5pt;">({{ $sub['subject_code'] }})</span>
                                @endif
                            </td>
                            {{-- Dynamic assessment scores --}}
                            @foreach($assessments as $aIdx => $a)
                                @php $score = $sub['assessments'][$a->id] ?? null; @endphp
                                <td style="{{ $aIdx === 0 ? 'border-left:1.5px solid #2563eb;' : '' }}">
                                    {{ $score !== null && $score > 0 ? number_format($score, 1) : '—' }}
                                </td>
                            @endforeach
                            <td class="{{ $gc }}">{{ $sub['total'] > 0 ? number_format($sub['total'],1) : '—' }}</td>
                            <td style="color:#9ca3af;">{{ $sub['bf'] > 0 ? number_format($sub['bf'],1) : '—' }}</td>
                            <td class="{{ $gc }}" style="font-weight:bold;">{{ $sub['cum'] > 0 ? number_format($sub['cum'],1) : '—' }}</td>
                            <td class="{{ $gc }}" style="font-weight:bold;">{{ $sub['grade'] }}</td>
                            <td style="font-size:6pt;">{{ $sub['position'] !== '-' ? $sub['position'] : '—' }}</td>
                            <td style="color:#6b7280;">{{ $sub['class_average'] > 0 ? number_format($sub['class_average'],1) : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="text-align:left;padding-left:5px;" colspan="{{ 1 + $assessments->count() }}">
                                SUMMARY — {{ $termData['subject_count'] }} subjects
                            </td>
                            <td colspan="2" style="color:#2563eb;">Avg: {{ $termData['average'] }}</td>
                            <td colspan="2" style="color:#16a34a;">Cum Avg: {{ $termData['cum_average'] }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
                @endif

                {{-- Principal's comment --}}
                @if($termData['comment'])
                <div class="principal-comment">
                    <span class="comment-label">Principal's Comment:</span>
                    {{ $termData['comment'] }}
                </div>
                @endif

            </div>{{-- end term-block --}}
        @endforeach

    </div>{{-- end session-block --}}
@empty
    <div style="text-align:center;padding:20px;color:#9ca3af;">
        No academic records found for this student.
    </div>
@endforelse

{{-- Overall Grade Distribution --}}
@if(!empty($gradeDistribution) && $gradeDistribution->count() > 0)
<div class="grade-dist-section">
    <div class="grade-dist-title">Overall Grade Distribution</div>
    <div class="grade-dist-grid">
        @php
        $gdColors = [
            'A1'=>'#16a34a','B2'=>'#1d4ed8','B3'=>'#2563eb',
            'C4'=>'#d97706','C5'=>'#b45309','C6'=>'#92400e',
            'D7'=>'#ea580c','E8'=>'#c2410c','F9'=>'#dc2626',
        ];
        @endphp
        @foreach($gradeDistribution as $grade => $count)
        <div class="grade-dist-item" style="border-color:{{ ($gdColors[$grade] ?? '#6b7280') }}33;">
            <span class="grade-dist-grade" style="color:{{ $gdColors[$grade] ?? '#6b7280' }};">{{ $grade }}</span>
            <span class="grade-dist-count">{{ $count }}×</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Signatures --}}
<div class="signature-section">
    <div class="sig-cell"><div class="sig-line">Class Teacher</div></div>
    <div class="sig-cell"><div class="sig-line">Date</div></div>
    <div class="sig-cell"><div class="sig-line">Principal</div></div>
</div>

{{-- Footer --}}
<div class="doc-footer">
    <div class="footer-left">
        {{ $schoolInfo->school_name ?? '' }} — Confidential Academic Record
        &nbsp;·&nbsp; {{ strtoupper($copy_type ?? 'original') }} COPY
    </div>
    <div class="footer-right">
        Generated: {{ $generated_at ?? now()->format('d M Y, H:i') }}
        &nbsp;·&nbsp; By: {{ $generated_by ?? 'System' }}
    </div>
</div>

</body>
</html>
