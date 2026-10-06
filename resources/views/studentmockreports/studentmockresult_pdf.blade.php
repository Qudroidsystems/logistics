<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Mock Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9px;
            line-height: 1.25;
            color: #000;
            background: #f5f5f5;
            padding: 3mm 0;
            text-align: center;
        }

        /* ── Watermark ── */
        .watermark-text {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 60px;
            font-weight: 900;
            color: rgba(0,0,0,0.04);
            font-family: 'Arial Black', sans-serif;
            letter-spacing: 5px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 1000;
            text-transform: uppercase;
            width: 100%;
            text-align: center;
        }

        /* ── Outer wrapper ── */
        .student-section {
            width: 190mm;
            background: #ffffff;
            border: 3px double #000000;
            margin: 0 auto;
            padding: 0;
            position: relative;
            text-align: left;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* ── School name header ── */
        .school-name-header {
            width: 100%;
            background: #111827;
            color: white;
            padding: 7px 10px 4px;
            text-align: center;
            border-bottom: 1px solid #1e40af;
        }
        .school-name-header .school-full-name {
            font-family: 'Arial Black', sans-serif;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            line-height: 1.05;
        }
        .school-name-header .motto {
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 2px;
            opacity: .92;
            margin-top: 2px;
        }

        /* ── Header table (terminal-style: logo | contact rows | photo) ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            padding: 4px 8px 3px;
        }
        .school-logo {
            width: 62px; height: 68px;
            border: 2px solid #47b492;
            border-radius: 5px;
            background: white;
            padding: 3px;
            overflow: hidden;
            display: block;
            text-align: center;
        }
        .school-logo img {
            max-width: 100%; max-height: 100%; object-fit: contain;
        }
        .photo-frame {
            width: 60px; height: 68px;
            border: 2px solid #47b492;
            border-radius: 5px;
            background: white;
            padding: 2px;
            overflow: hidden;
            display: block;
            text-align: center;
            margin-left: auto; margin-right: 0;
        }
        .photo-frame img {
            max-width: 100%; max-height: 100%; object-fit: contain;
        }

        /* Contact info uses a tight 2-col key/value table (matches terminal report) */
        .contact-table {
            border: none;
            border-collapse: collapse;
            width: 100%;
            font-size: 8.8px;
        }
        .contact-table td {
            padding: 1.5px 4px 1.5px 0;
            vertical-align: top;
        }
        .contact-key {
            font-weight: 900;
            color: #1e40af;
            white-space: nowrap;
        }

        /* ── Dividers ── */
        .header-divider  { width:100%; height:2px; background:#1e40af; margin:0; }
        .header-divider2 { width:100%; height:1px; background:#64748b; margin:1px 0; }

        /* ── Report title + mock badge ── */
        .report-title {
            background: #111827;
            color: white;
            padding: 5px 8px;
            font-size: 10.5px;
            font-weight: 700;
            text-align: center;
        }
        .mock-badge {
            background: #b45309;
            color: white;
            font-size: 7.5px;
            font-weight: 700;
            padding: 2px 8px;
            letter-spacing: .8px;
            text-align: center;
        }

        /* ── Student info bar ── */
        .student-info-bar {
            background: linear-gradient(to bottom, #f0f7ff 0%, #ffffff 100%);
            border: 2px solid #2aa886;
            border-radius: 5px;
            padding: 5px 10px;
            margin: 6px 9px;
            font-size: 8.8px;
            text-align: center;
        }
        .info-table { width:100%; border-collapse:collapse; margin:0 auto; }
        .info-table td { padding: 2px 6px; text-align:center; }
        .info-bar-label { color:#1e40af; font-weight:900; font-size:8px; white-space:nowrap; }
        .info-bar-value { font-weight:900; font-size:9px; padding-left:2px; }

        /* ── Result table ── */
        .result-table { padding: 0 9px; margin: 5px 0; }
        .result-table table {
            width: 100%;
            border: 2px solid #000;
            border-collapse: collapse;
            font-size: 7.5px;
            margin: 0;
        }
        .result-table thead th {
            background: #0d1a3d;
            color: white;
            font-weight: 800;
            border: 1px solid #000;
            padding: 2.5px 1px;
            font-size: 6.5px;
            text-align: center;
        }
        .result-table tbody td {
            border: 1px solid #000;
            padding: 1.5px 1px;
            text-align: center;
            font-size: 7.5px;
            background: white;
            font-weight: 800;
            height: 13px;
            line-height: 13px;
        }
        .result-table tbody td.subject-name {
            text-align: left;
            padding-left: 5px;
            font-size: 7.5px;
        }

        /* Grade / position colours */
        .highlight-red { color: #dc2626; font-weight: 900; }
        .grade-A { color: #16a34a; font-weight: 900; }
        .grade-B { color: #2563eb; font-weight: 900; }
        .grade-C { color: #ca8a04; font-weight: 900; }
        .grade-D { color: #ea580c; font-weight: 900; }
        .grade-F { color: #dc2626; font-weight: 900; }
        .position-1 { background: gold;    color: black; font-weight: 900; border-radius: 2px; }
        .position-2 { background: silver;  color: black; font-weight: 900; }
        .position-3 { background: #cd7f32; color: white; font-weight: 900; }

        /* ── Totals bar ── */
        .totals-summary {
            width: calc(100% - 18px);
            background: #0d1a3d;
            color: #ffffff;
            font-weight: 900;
            font-size: 7.5px;
            padding: 4px 9px;
            border: 2px solid #000;
            border-top: none;
            text-align: center;
            margin: 0 9px 6px;
        }

        /* ── Remarks ── */
        .remarks-table {
            width: calc(100% - 18px);
            border: 2px solid #000;
            border-collapse: collapse;
            margin: 0 9px 4px;
        }
        .remarks-table td {
            border: 1px solid #000;
            padding: 4px 7px;
            background: white;
            vertical-align: top;
            font-size: 8px;
        }
        .remarks-table .h6 {
            font-weight: 700;
            margin-bottom: 2px;
            font-size: 8.5px;
            border-bottom: 1px solid #ccc;
            display: inline-block;
        }

        /* ── Bottom strip ── */
        .bottom-strip {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            background: #f1f5f9;
            margin-top: 4px;
        }
        .bottom-strip table { width:100%; border-collapse:collapse; }
        .bottom-strip td { padding: 6px 9px; vertical-align: middle; }
        .cell-qr    { width:80px; text-align:center; vertical-align:middle; }
        .cell-footer{ text-align:center; font-size:8px; vertical-align:middle; }
        .cell-stamp { width:110px; text-align:center; vertical-align:middle; }
        .cell-qr img { width:66px; height:66px; display:block; margin:0 auto 2px; }
        .qr-label   { font-size:6px; color:#333; font-weight:600; text-align:center; }
        .cell-stamp img { width:95px; height:95px; transform:rotate(-8deg); display:block; margin:0 auto; }
        .text-dot-space2 {
            border-bottom: 1px dotted #333;
            display: inline-block;
            min-width: 105px;
            font-weight: bold;
            margin: 0 3px;
        }
        .powered-by { font-size: 7.5px; margin-top: 3px; color: #64748b; }

        @media print {
            body { background: white; padding: 0; }
            .student-section { width:190mm; margin:0 auto; box-shadow:none; }
        }
    </style>
</head>
<body>
    <div class="watermark-text">MOCK EXAMINATION</div>

    @php
        $schoolInfo = $data['schoolInfo'] ?? null;
        $student    = isset($data['students']) && $data['students']->isNotEmpty() ? $data['students']->first() : null;
        $mockScores = $data['mockScores'] ?? collect();
        $totals     = $data['totals_summary'] ?? [];
        $profile    = isset($data['studentpp']) && $data['studentpp']->isNotEmpty() ? $data['studentpp']->first() : null;

        $fullName = strtoupper($student->lastname ?? '') . ' ' . ($student->fname ?? '') . ' ' . ($student->othername ?? '');
        $admNo    = $student->admissionNo ?? '—';
        $classVal = ($data['schoolclass'] ?? null)
            ? ((($data['schoolclass']->schoolclass ?? '')) . ' ' . ($data['schoolclass']->arms?->arm ?? ''))
            : '—';
        $session  = $data['schoolsession']->session ?? '2025/2026';
        $term     = $data['schoolterm']->term ?? 'SECOND TERM';

        $minRows   = 16;
        $extraRows = max(0, $minRows - $mockScores->count());

        $qrData = "Name: {$fullName}\nAdm No: {$admNo}\nClass: {$classVal}\nTerm: {$term}\nSession: {$session}\nSchool: " . ($schoolInfo->school_name ?? 'School');
        $qrCodeBase64 = base64_encode(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(260)
                ->errorCorrection('H')
                ->generate($qrData)
        );

        $stampSrc = !empty($data['school_stamp_base64'])
            ? $data['school_stamp_base64']
            : asset('stamp.jpeg');

        $logoSrc = !empty($data['school_logo_base64'])
            ? $data['school_logo_base64']
            : 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="62" height="68" viewBox="0 0 100 100">
                 <rect width="100" height="100" fill="#f8f9fa" stroke="#47b492" stroke-width="2"/>
                 <text x="50" y="55" text-anchor="middle" fill="#1e40af" font-size="8" font-weight="bold">LOGO</text>
                 </svg>'
            );
    @endphp

    <div class="student-section">

        {{-- ── SCHOOL NAME HEADER ── --}}
        <div class="school-name-header">
            <div class="school-full-name">{{ $schoolInfo->school_name ?? 'CLARET SECONDARY SCHOOL KABBA' }}</div>
            <div class="motto">{{ $schoolInfo->school_motto ?? 'KNOWLEDGE AND VIRTUE' }}</div>
        </div>

        {{-- ── HEADER: Logo | Contact (terminal-style) | Photo ── --}}
        <table class="header-table">
            <tr>
                {{-- Logo --}}
                <td width="15%" style="text-align:center; vertical-align:middle; padding:4px 6px 4px 8px;">
                    <div class="school-logo">
                        <img src="{{ $logoSrc }}" alt="School Logo">
                    </div>
                </td>

                {{-- Contact rows — mirrors terminal report layout --}}
                <td style="vertical-align:top; padding:4px 6px;">
                    <table class="contact-table">
                        <tr>
                            <td class="contact-key">Address:</td>
                            <td>{{ $schoolInfo->school_address ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="contact-key">Phone:</td>
                            <td>{{ $schoolInfo->formatted_phones ?? ($schoolInfo->school_phone ?? '—') }}</td>
                        </tr>
                        <tr>
                            <td class="contact-key">Email:</td>
                            <td>{{ $schoolInfo->school_email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="contact-key">Website:</td>
                            <td>{{ $schoolInfo->school_website ?? '—' }}</td>
                        </tr>
                    </table>
                </td>

                {{-- Student photo --}}
                <td width="17%" style="text-align:right; padding:4px 8px 4px 4px; vertical-align:middle;">
                    <div class="photo-frame">
                        @if(!empty($data['student_image_base64']))
                            <img src="{{ $data['student_image_base64'] }}" alt="Student Photo">
                        @else
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='68' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23e2e8f0'/%3E%3Ccircle cx='50' cy='40' r='20' fill='%2394a3b8'/%3E%3Crect x='35' y='65' width='30' height='25' fill='%2394a3b8' rx='4'/%3E%3C/svg%3E" alt="Default">
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="header-divider"></div>
        <div class="header-divider2"></div>

        <div class="report-title">
            {{ strtoupper($term) }} {{ strtoupper($session) }} MOCK EXAMINATION RESULT
        </div>
        <div class="mock-badge">MOCK EXAMINATION — NOT FOR OFFICIAL PROMOTION USE</div>

        {{-- ── STUDENT INFO BAR ── --}}
        <div class="student-info-bar">
            <table class="info-table">
                <tr>
                    <td><span class="info-bar-label">NAME:</span> <span class="info-bar-value">{{ $fullName }}</span></td>
                    <td><span class="info-bar-label">SESSION:</span> <span class="info-bar-value">{{ $session }}</span></td>
                    <td><span class="info-bar-label">TERM:</span> <span class="info-bar-value">{{ $term }}</span></td>
                    <td><span class="info-bar-label">CLASS:</span> <span class="info-bar-value">{{ $classVal }}</span></td>
                </tr>
                <tr>
                    <td><span class="info-bar-label">ADM NO:</span> <span class="info-bar-value">{{ $admNo }}</span></td>
                    <td><span class="info-bar-label">NO. IN CLASS:</span> <span class="info-bar-value">{{ $data['numberOfStudents'] ?? '—' }}</span></td>
                    <td><span class="info-bar-label">SEX:</span> <span class="info-bar-value">{{ $student->gender ?? '—' }}</span></td>
                    <td><span class="info-bar-label">D.O.B:</span> <span class="info-bar-value">{{ $student->dateofbirth ?? '—' }}</span></td>
                </tr>
            </table>
        </div>

        {{-- ── RESULTS TABLE ── --}}
        <div class="result-table">
            <table>
                <thead>
                    <tr>
                        <th style="width:24px;">S/N</th>
                        <th style="min-width:115px; text-align:left; padding-left:4px;">Subject</th>
                        <th style="width:42px;">Exam Score</th>
                        <th style="width:42px;">Total</th>
                        <th style="width:32px;">Grade</th>
                        <th style="width:32px;">Pos</th>
                        <th style="width:36px;">Avg</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mockScores as $i => $score)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="subject-name">{{ $score->subject_name ?? 'N/A' }}</td>
                        <td @if(($score->exam ?? 0) < 50) class="highlight-red" @endif>
                            {{ $score->exam ? number_format($score->exam, 1) : '-' }}
                        </td>
                        <td @if(($score->total ?? 0) < 50) class="highlight-red" @endif>
                            {{ $score->total ? number_format($score->total, 1) : '-' }}
                        </td>

                        @php
                            $g  = $score->grade ?? '-';
                            $gc = match(true) {
                                str_starts_with(strtoupper($g), 'A') => 'grade-A',
                                str_starts_with(strtoupper($g), 'B') => 'grade-B',
                                str_starts_with(strtoupper($g), 'C') => 'grade-C',
                                str_starts_with(strtoupper($g), 'D') => 'grade-D',
                                default => 'grade-F'
                            };
                        @endphp
                        <td class="{{ $gc }}">{{ $g }}</td>

                        @php
                            $pos    = $score->position ?? '-';
                            $posNum = preg_replace('/\D/', '', $pos);
                            $posC   = match((int)$posNum) { 1=>'position-1', 2=>'position-2', 3=>'position-3', default=>'' };
                        @endphp
                        <td class="{{ $posC }}">{{ $pos }}</td>

                        <td>{{ $score->class_average ? number_format($score->class_average, 1) : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:6px;">No mock scores available.</td></tr>
                    @endforelse

                    {{-- Padding rows ── --}}
                    @for($i = 0; $i < $extraRows; $i++)
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- ── TOTALS ── --}}
        <div class="totals-summary">
            TOTAL OBTAINED: {{ number_format($totals['obtained'] ?? 0, 1) }}&nbsp;&nbsp;|&nbsp;&nbsp;
            TOTAL OBTAINABLE: {{ $totals['obtainable'] ?? 0 }}&nbsp;&nbsp;|&nbsp;&nbsp;
            % OBTAINED: {{ $totals['percentage'] ?? 0 }}%
        </div>

        {{-- ── REMARKS ── --}}
        <table class="remarks-table">
            <tbody>
                <tr>
                    <td width="50%">
                        <div class="h6">Class Teacher's Remark</div>
                        <div>{{ $profile ? ($profile->classteachercomment ?? 'NO INFO') : 'NO INFO' }}</div>
                    </td>
                    <td width="50%">
                        <div class="h6">Principal's Remark</div>
                        <div>{{ $profile ? ($profile->principalscomment ?? 'NO INFO') : 'NO INFO' }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- ── BOTTOM STRIP: QR | Footer | Stamp ── --}}
        <div class="bottom-strip">
            <table>
                <tr>
                    <td class="cell-qr">
                        <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code">
                        <div class="qr-label">Scan for Verification</div>
                    </td>
                    <td class="cell-footer">
                        <div>
                            <strong>Issued:</strong>
                            <span class="text-dot-space2">{{ now()->format('jS F, Y') }}</span>
                        </div>
                        <div style="margin-top:3px;">
                            <strong>Collected by:</strong>
                            <span class="text-dot-space2">.......................................</span>
                        </div>
                        <div style="margin-top:3px;">
                            <strong>Next Term Begins:</strong>
                            <span class="text-dot-space2">
                                @php
                                    $ntb = $schoolInfo->date_next_term_begins ?? null;
                                    echo $ntb ? \Carbon\Carbon::parse($ntb)->format('jS F, Y') : '........................';
                                @endphp
                            </span>
                        </div>
                        <div class="powered-by">Powered by Qudroid Systems</div>
                    </td>
                    <td class="cell-stamp">
                        <img src="{{ $stampSrc }}" alt="School Stamp">
                    </td>
                </tr>
            </table>
        </div>

    </div>
</body>
</html>
