@extends('layouts.master')

@section('content')

@php
    $schoolInfo = $schoolInfo ?? null;
    $student    = isset($students) && $students->isNotEmpty() ? $students->first() : null;
    $mockScoresList = $mockScores ?? collect();
    $totals     = $totals_summary ?? [];
    $profile    = isset($studentpp) && $studentpp->isNotEmpty() ? $studentpp->first() : null;

    $fullName = strtoupper($student->lastname ?? '') . ' ' . ($student->fname ?? '') . ' ' . ($student->othername ?? '');
    $admNo    = $student->admissionNo ?? '—';
    $classVal = $schoolclass ? (($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arms?->arm ?? '')) : '—';
    $sessionLabel = $schoolsession->session ?? '—';
    $termLabel    = $schoolterm->term ?? '—';

    // Student photo — on-screen view uses direct asset URL, no base64 needed
    $studentPhotoUrl = ($student && !empty($student->picture))
        ? asset('storage/student_avatars/' . $student->picture)
        : asset('storage/student_avatars/unnamed.jpg');

    $schoolLogoUrl = !empty($schoolInfo->school_logo)
        ? asset('storage/' . $schoolInfo->school_logo)
        : null;
@endphp

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.smr-wrapper { font-family: 'Plus Jakarta Sans', sans-serif; padding: 16px 0 40px; }

.smr-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 18px; flex-wrap: wrap; gap: 10px;
}
.smr-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 9px;
    font-size: 13px; font-weight: 600; font-family: inherit;
    border: none; cursor: pointer; text-decoration: none;
    transition: transform .15s, box-shadow .15s, opacity .15s;
}
.smr-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,.15); opacity: .92; }
.smr-btn.secondary { background:#fff; color:#1e3a5f; border:1.5px solid #e2e8f0; }
.smr-btn.success   { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; }

/* ── Report card (mirrors PDF look) ── */
.smr-report {
    width: 100%;
    max-width: 780px;
    margin: 0 auto;
    background: #ffffff;
    border: 3px double #000000;
    box-shadow: 0 4px 24px rgba(0,0,0,.12);
    overflow: hidden;
    font-family: 'Times New Roman', Times, serif;
    color: #000;
}

.smr-school-header {
    background: #111827; color: white;
    padding: 14px 16px 8px; text-align: center;
    border-bottom: 1px solid #1e40af;
}
.smr-school-header .smr-school-name {
    font-family: Arial, sans-serif; font-size: 24px; font-weight: 900;
    letter-spacing: 1.5px; text-transform: uppercase; line-height: 1.1;
}
.smr-school-header .smr-motto {
    font-size: 12px; font-weight: 700; letter-spacing: 2px; opacity: .92; margin-top: 4px;
}

.smr-header-table { width: 100%; border-collapse: collapse; padding: 8px 12px; }
.smr-logo, .smr-photo {
    width: 80px; height: 90px; border: 2px solid #47b492; border-radius: 6px;
    background: #fff; padding: 4px; overflow: hidden;
}
.smr-logo img, .smr-photo img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; margin: 0 auto; }

.smr-contact-table { border-collapse: collapse; width: 100%; font-size: 13px; }
.smr-contact-table td { padding: 2px 6px 2px 0; vertical-align: top; }
.smr-contact-key { font-weight: 900; color: #1e40af; white-space: nowrap; }

.smr-divider  { width:100%; height:2px; background:#1e40af; margin:0; }
.smr-divider2 { width:100%; height:1px; background:#64748b; margin:1px 0; }

.smr-report-title { background:#111827; color:#fff; padding:10px 12px; font-size:16px; font-weight:700; text-align:center; }
.smr-mock-badge { background:#b45309; color:#fff; font-size:11px; font-weight:700; padding:4px 10px; letter-spacing:.8px; text-align:center; }

.smr-info-bar {
    background: linear-gradient(to bottom, #f0f7ff 0%, #ffffff 100%);
    border: 2px solid #2aa886; border-radius: 6px;
    padding: 10px 14px; margin: 12px; font-size: 13px; text-align: center;
}
.smr-info-table { width:100%; border-collapse:collapse; margin:0 auto; }
.smr-info-table td { padding: 4px 8px; text-align:center; }
.smr-info-label { color:#1e40af; font-weight:900; font-size:11px; white-space:nowrap; }
.smr-info-value { font-weight:900; font-size:13px; padding-left:3px; }

.smr-result-wrap { padding: 0 16px; margin: 10px 0; }
.smr-result-table { width: 100%; border: 2px solid #000; border-collapse: collapse; font-size: 12px; }
.smr-result-table thead th {
    background:#0d1a3d; color:#fff; font-weight:800; border:1px solid #000;
    padding: 6px 4px; font-size: 11px; text-align: center;
}
.smr-result-table tbody td {
    border:1px solid #000; padding:5px 4px; text-align:center; font-size:12px;
    background:#fff; font-weight:700;
}
.smr-result-table tbody td.smr-subject-name { text-align: left; padding-left: 8px; }

.smr-highlight-red { color:#dc2626; font-weight:900; }
.smr-grade-A { color:#16a34a; font-weight:900; }
.smr-grade-B { color:#2563eb; font-weight:900; }
.smr-grade-C { color:#ca8a04; font-weight:900; }
.smr-grade-D { color:#ea580c; font-weight:900; }
.smr-grade-F { color:#dc2626; font-weight:900; }
.smr-position-1 { background:gold;   color:#000; font-weight:900; }
.smr-position-2 { background:silver; color:#000; font-weight:900; }
.smr-position-3 { background:#cd7f32;color:#fff; font-weight:900; }

.smr-totals {
    background:#0d1a3d; color:#fff; font-weight:900; font-size:12px;
    padding:8px 16px; border:2px solid #000; border-top:none; text-align:center; margin:0 16px 10px;
}

.smr-remarks-table { width: calc(100% - 32px); border:2px solid #000; border-collapse:collapse; margin:0 16px 14px; }
.smr-remarks-table td { border:1px solid #000; padding:8px 12px; background:#fff; vertical-align:top; font-size:13px; }
.smr-remarks-table .smr-h6 { font-weight:700; margin-bottom:4px; font-size:13px; border-bottom:1px solid #ccc; display:inline-block; }

@media print {
    .smr-toolbar { display: none; }
    .smr-report { box-shadow: none; border: 3px double #000; }
    body { background: #fff; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">
<div class="smr-wrapper">

    <div class="smr-toolbar">
        <a href="{{ route('studentmockreports.index') }}" class="smr-btn secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="smr-btn secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="{{ route('studentmockreports.exportStudentMockResultPdf', [
                        $studentid, $schoolclassid, $sessionid, $termid
                    ]) }}" class="smr-btn success" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </a>
        </div>
    </div>

    <div class="smr-report">

        <div class="smr-school-header">
            <div class="smr-school-name">{{ $schoolInfo->school_name ?? 'CLARET SECONDARY SCHOOL KABBA' }}</div>
            <div class="smr-motto">{{ $schoolInfo->school_motto ?? 'KNOWLEDGE AND VIRTUE' }}</div>
        </div>

        <table class="smr-header-table">
            <tr>
                <td width="18%" style="text-align:center; vertical-align:middle; padding:6px 8px;">
                    <div class="smr-logo">
                        @if($schoolLogoUrl)
                            <img src="{{ $schoolLogoUrl }}" alt="School Logo">
                        @else
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:11px;font-weight:700;">LOGO</div>
                        @endif
                    </div>
                </td>
                <td style="vertical-align:top; padding:6px 8px;">
                    <table class="smr-contact-table">
                        <tr><td class="smr-contact-key">Address:</td><td>{{ $schoolInfo->school_address ?? '—' }}</td></tr>
                        <tr><td class="smr-contact-key">Phone:</td><td>{{ $schoolInfo->school_phone ?? '—' }}</td></tr>
                        <tr><td class="smr-contact-key">Email:</td><td>{{ $schoolInfo->school_email ?? '—' }}</td></tr>
                        <tr><td class="smr-contact-key">Website:</td><td>{{ $schoolInfo->school_website ?? '—' }}</td></tr>
                    </table>
                </td>
                <td width="18%" style="text-align:right; padding:6px 8px; vertical-align:middle;">
                    <div class="smr-photo" style="margin-left:auto;">
                        <img src="{{ $studentPhotoUrl }}" alt="Student Photo"
                             onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                    </div>
                </td>
            </tr>
        </table>

        <div class="smr-divider"></div>
        <div class="smr-divider2"></div>

        <div class="smr-report-title">
            {{ strtoupper($termLabel) }} {{ strtoupper($sessionLabel) }} MOCK EXAMINATION RESULT
        </div>
        <div class="smr-mock-badge">MOCK EXAMINATION — NOT FOR OFFICIAL PROMOTION USE</div>

        <div class="smr-info-bar">
            <table class="smr-info-table">
                <tr>
                    <td><span class="smr-info-label">NAME:</span> <span class="smr-info-value">{{ $fullName }}</span></td>
                    <td><span class="smr-info-label">SESSION:</span> <span class="smr-info-value">{{ $sessionLabel }}</span></td>
                    <td><span class="smr-info-label">TERM:</span> <span class="smr-info-value">{{ $termLabel }}</span></td>
                    <td><span class="smr-info-label">CLASS:</span> <span class="smr-info-value">{{ $classVal }}</span></td>
                </tr>
                <tr>
                    <td><span class="smr-info-label">ADM NO:</span> <span class="smr-info-value">{{ $admNo }}</span></td>
                    <td><span class="smr-info-label">NO. IN CLASS:</span> <span class="smr-info-value">{{ $numberOfStudents ?? '—' }}</span></td>
                    <td><span class="smr-info-label">SEX:</span> <span class="smr-info-value">{{ $student->gender ?? '—' }}</span></td>
                    <td><span class="smr-info-label">D.O.B:</span> <span class="smr-info-value">{{ $student->dateofbirth ?? '—' }}</span></td>
                </tr>
            </table>
        </div>

        <div class="smr-result-wrap">
            <table class="smr-result-table">
                <thead>
                    <tr>
                        <th style="width:30px;">S/N</th>
                        <th style="min-width:140px; text-align:left; padding-left:8px;">Subject</th>
                        <th style="width:60px;">Exam Score</th>
                        <th style="width:60px;">Total</th>
                        <th style="width:50px;">Grade</th>
                        <th style="width:50px;">Pos</th>
                        <th style="width:55px;">Class Avg</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mockScoresList as $i => $score)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="smr-subject-name">{{ $score->subject_name ?? 'N/A' }}</td>
                        <td @if(($score->exam ?? 0) < 50) class="smr-highlight-red" @endif>
                            {{ $score->exam ? number_format($score->exam, 1) : '-' }}
                        </td>
                        <td @if(($score->total ?? 0) < 50) class="smr-highlight-red" @endif>
                            {{ $score->total ? number_format($score->total, 1) : '-' }}
                        </td>
                        @php
                            $g  = $score->grade ?? '-';
                            $gc = match(true) {
                                str_starts_with(strtoupper($g), 'A') => 'smr-grade-A',
                                str_starts_with(strtoupper($g), 'B') => 'smr-grade-B',
                                str_starts_with(strtoupper($g), 'C') => 'smr-grade-C',
                                str_starts_with(strtoupper($g), 'D') => 'smr-grade-D',
                                default => 'smr-grade-F'
                            };
                            $pos    = $score->position ?? '-';
                            $posNum = preg_replace('/\D/', '', $pos);
                            $posC   = match((int)$posNum) { 1=>'smr-position-1', 2=>'smr-position-2', 3=>'smr-position-3', default=>'' };
                        @endphp
                        <td class="{{ $gc }}">{{ $g }}</td>
                        <td class="{{ $posC }}">{{ $pos }}</td>
                        <td>{{ $score->class_average ? number_format($score->class_average, 1) : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:14px;">No mock scores available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="smr-totals">
            TOTAL OBTAINED: {{ number_format($totals['obtained'] ?? 0, 1) }}&nbsp;&nbsp;|&nbsp;&nbsp;
            TOTAL OBTAINABLE: {{ $totals['obtainable'] ?? 0 }}&nbsp;&nbsp;|&nbsp;&nbsp;
            % OBTAINED: {{ $totals['percentage'] ?? 0 }}%
        </div>

        <table class="smr-remarks-table">
            <tr>
                <td width="50%">
                    <div class="smr-h6">Class Teacher's Remark</div>
                    <div>{{ $profile ? ($profile->classteachercomment ?? 'NO INFO') : 'NO INFO' }}</div>
                </td>
                <td width="50%">
                    <div class="smr-h6">Principal's Remark</div>
                    <div>{{ $profile ? ($profile->principalscomment ?? 'NO INFO') : 'NO INFO' }}</div>
                </td>
            </tr>
        </table>

    </div>

</div>
</div>
</div>
</div>

@endsection
