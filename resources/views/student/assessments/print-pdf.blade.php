<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Progress Report - {{ $metadata['session'] ?? '2025/2026' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            line-height: 1.3;
            color: #000;
            background: #f5f5f5;
            padding: 2mm 0;
            text-align: center;
        }

        .watermark-text {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 65px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.04);
            font-family: 'Arial Black', sans-serif;
            letter-spacing: 5px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 1000;
            text-transform: uppercase;
        }

        .student-section {
            width: 190mm;
            page-break-after: always;
            page-break-inside: avoid;
            break-after: page;
            break-inside: avoid;
            background: #ffffff;
            border: 3px double #000000;
            margin: 0 auto;
            position: relative;
            text-align: left;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .student-section:last-child {
            page-break-after: avoid;
        }

        .school-name-header {
            width: 96.5%;
            background: #111827;
            color: white;
            padding: 7px 10px 5px 10px;
            border: 3px double #000000;
            border-bottom: 1px solid #1e40af;
            text-align: center;
        }

        .school-name-header .school-full-name {
            font-family: 'Arial Black', sans-serif;
            font-size: 23px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            line-height: 1.05;
        }

        .school-name-header .motto {
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: 2px;
            opacity: 0.95;
            margin-top: 2px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            padding: 3px 7px 3px 7px;
        }

        .school-logo {
            width: 68px;
            height: 76px;
            border: 2px solid #47b492;
            border-radius: 6px;
            background: white;
            padding: 3px;
            overflow: hidden;
            display: block;
            text-align: center;
        }

        .school-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .photo-frame {
            width: 68px;
            height: 76px;
            border: 2px solid #47b492;
            border-radius: 6px;
            background: #e2e8f0;
            padding: 0;
            overflow: hidden;
            display: block;
            margin-left: auto;
            margin-right: 4px;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .header-divider { height: 2px; background: #1e40af; width: 100%; }
        .header-divider2 { height: 1px; background: #64748b; width: 100%; margin: 1px 0; }

        .report-title {
            background: #111827;
            color: white;
            padding: 5px 8px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
        }

        .student-info-bar {
            background: linear-gradient(to bottom, #f0f7ff 0%, #ffffff 100%);
            border: 2px solid #2aa886;
            border-radius: 6px;
            padding: 5px 10px;
            margin: 5px 8px;
            font-size: 12px;
            text-align: center;
        }

        .info-table { width: 100%; margin: 0 auto; }
        .info-table td { padding: 2px 6px; text-align: center; }
        .info-bar-label { color: #1e40af; font-weight: 900; font-size: 11.5px; white-space: nowrap; }
        .info-bar-value { font-weight: 900; font-size: 12.5px; padding-left: 3px; }

        .result-table { padding: 0 8px; margin: 5px 0; }

        .result-table table {
            width: 100%;
            border: 2px solid #000000;
            border-collapse: collapse;
            font-size: 11px;
            margin: 0;
        }

        .result-table thead th {
            background: #0d1a3d;
            color: white;
            font-weight: 800;
            border: 1px solid #000000;
            padding: 3px 2px;
            font-size: 10px;
            text-align: center;
            line-height: 1.2;
        }

        .result-table tbody td {
            border: 1px solid #000000;
            padding: 2px 2px;
            text-align: center;
            font-size: 11px;
            background: white;
            font-weight: 800;
            height: 15px;
            line-height: 15px;
        }

        .result-table tbody td.subject-name {
            text-align: left;
            font-weight: 800;
            font-size: 11px;
            padding-left: 6px;
        }

        .highlight-red { color: #dc2626; font-weight: 900; }

        .col-sn { width: 20px; }
        .col-admissionno { width: 70px; }
        .col-name { width: 148px; }
        .col-assessment { width: 36px; }
        .col-total { width: 36px; }
        .col-bf { width: 30px; }
        .col-cum { width: 34px; }
        .col-grade { width: 32px; }
        .col-compulsory { width: 34px; }
        .col-position { width: 32px; }
        .col-class-average { width: 34px; }

        /* Always-on marker next to a compulsory subject's name */
        .compulsory-mark {
            font-weight: 900;
            font-size: 12px;
            margin-left: 2px;
            vertical-align: super;
        }
        .compulsory-mark-pass { color: #16a34a; }
        .compulsory-mark-fail { color: #dc2626; }

        .compulsory-badge-pass { color: #16a34a; font-weight: 900; }
        .compulsory-badge-fail { color: #dc2626; font-weight: 900; }
        .compulsory-badge-no   { color: #94a3b8; font-weight: 700; }

        .compulsory-note {
            font-size: 9.5px;
            color: #64748b;
            font-style: italic;
            margin: 3px 8px 0 8px;
            text-align: left;
        }

        /* Grade colours - A=Green, B=Blue, C=Pink, D/E=Purple, F=Red */
        .grade-A1 { color: #15803d; font-weight: 900; padding: 1px 6px; border-radius: 4px; }
        .grade-B2 { color: #1d4ed8; font-weight: 900; padding: 1px 6px; border-radius: 4px; }
        .grade-B3 { color: #1d4ed8; font-weight: 900; padding: 1px 6px; border-radius: 4px; }
        .grade-C4 { color: #db2777; font-weight: 900; padding: 1px 6px; border-radius: 4px; }
        .grade-C5 { color: #db2777; font-weight: 900; padding: 1px 6px; border-radius: 4px; }
        .grade-C6 { color: #db2777; font-weight: 900; padding: 1px 6px; border-radius: 4px; }
        .grade-D7 { color: #7e22ce; font-weight: 900; padding: 1px 6px; border-radius: 4px; }
        .grade-E8 { color: #7e22ce; font-weight: 900; padding: 1px 6px; border-radius: 4px; }
        .grade-F9 { color: #dc2626; font-weight: 900; padding: 1px 6px; border-radius: 4px; }

        .totals-summary {
            width: calc(97% - 16px);
            background: #0d1a3d;
            color: #ffffff;
            font-weight: 900;
            font-size: 11px;
            padding: 4px 8px;
            border: 2px solid #000000;
            border-top: none;
            text-align: center;
            margin: 0 8px 5px 8px;
        }

        .position-cell { font-weight: 900; text-align: center; padding: 2px 4px; }
        .position-1 { background-color: #FFD700; color: #000000; font-weight: 900; }
        .position-2 { background-color: #C0C0C0; color: #000000; font-weight: 900; }
        .position-3 { background-color: #CD7F32; color: #000000; font-weight: 900; }
        td.position-1, td.position-2, td.position-3 { color: #000000 !important; }

        .promo-card {
            width: calc(96% - 16px);
            margin: 6px 8px 8px 8px;
            padding: 8px 12px;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
            clear: both;
        }

        .promo-title {
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .promo-message {
            font-size: 9.5px;
            font-weight: 500;
            margin-top: 2px;
            line-height: 1.3;
        }

        .promo-promoted {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-left: 3px solid #16a34a;
            border-right: 3px solid #16a34a;
            color: #14532d;
        }

        .promo-trial {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            border-left: 3px solid #ca8a04;
            border-right: 3px solid #ca8a04;
            color: #854d0e;
        }

        .promo-principal {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 3px solid #3b82f6;
            border-right: 3px solid #3b82f6;
            color: #1e3a8a;
        }

        .promo-repeated {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 3px solid #dc2626;
            border-right: 3px solid #dc2626;
            color: #7f1d1d;
        }

        .promo-awaiting {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-left: 3px solid #94a3b8;
            border-right: 3px solid #94a3b8;
            color: #475569;
        }

        .bottom-strip {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            background: #f1f5f9;
            margin-top: 4px;
        }

        .bottom-strip table {
            width: 100%;
            border-collapse: collapse;
        }

        .bottom-strip td {
            padding: 5px 8px;
            vertical-align: middle;
        }

        .bottom-strip .cell-qr {
            width: 80px;
            text-align: center;
            vertical-align: middle;
        }

        .bottom-strip .cell-footer {
            text-align: center;
            font-size: 11.5px;
            vertical-align: middle;
        }

        .bottom-strip .cell-stamp {
            width: 110px;
            text-align: center;
            vertical-align: middle;
        }

        .bottom-strip .cell-qr img {
            width: 65px;
            height: 65px;
            display: block;
            margin: 0 auto 2px;
        }

        .qr-label {
            font-size: 9px;
            color: #333;
            font-weight: 600;
            text-align: center;
        }

        .bottom-strip .cell-stamp img {
            width: 95px;
            height: 95px;
            transform: rotate(-8deg);
            display: block;
            margin: 0 auto;
        }

        .text-dot-space2 {
            border-bottom: 1px dotted #333;
            display: inline-block;
            min-width: 110px;
            font-weight: bold;
            margin: 0 4px;
        }

        .powered-by { font-size: 11px; margin-top: 3px; color: #64748b; }

        .mock-section {
            margin: 8px 8px 4px 8px;
            border: 2px solid #000000;
            border-radius: 4px;
            overflow: hidden;
        }

        .mock-header {
            background: #111827;
            color: white;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            border-bottom: 1px solid #000;
        }

        .mock-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        .mock-table th {
            background: #1a2f55;
            color: white;
            border: 1px solid #000000;
            padding: 3px 2px;
            font-size: 9px;
            text-align: center;
            font-weight: 800;
        }

        .mock-table td {
            border: 1px solid #000000;
            padding: 2px 2px;
            text-align: center;
            font-size: 10.5px;
            background: white;
            font-weight: 700;
        }

        .mock-table td.subject-name {
            text-align: left;
            padding-left: 6px;
        }

        .mock-summary {
            background: #0d1a3d;
            color: white;
            font-weight: 900;
            font-size: 9.5px;
            padding: 3px 8px;
            text-align: center;
        }

        @media print {
            body { background: white; padding: 0; }
            .student-section {
                box-shadow: none;
                page-break-inside: avoid;
                page-break-after: always;
                break-after: page;
            }
        }
    </style>
</head>
<body>
    <div class="watermark-text">STUDENT COPY</div>

    @php
        function formatOrdinal($number) {
            if (!is_numeric($number) || $number <= 0) { return '-'; }
            $lastDigit     = $number % 10;
            $lastTwoDigits = $number % 100;
            if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) { return $number . 'th'; }
            switch ($lastDigit) {
                case 1: return $number . 'st';
                case 2: return $number . 'nd';
                case 3: return $number . 'rd';
                default: return $number . 'th';
            }
        }

        $selectedColumns = $metadata['selected_columns'] ?? [];
        $gradeBasis = $metadata['grade_basis'] ?? 'cum_ave';
        
        $defaultColumns = [
            'sn', 'name',
            'total', 'bf', 'cum', 'cum_ave', 'grade',
            'arm_position', 'arm_position_cum', 'position_total', 'position',
            'class_average'
        ];
        $columnsToShow = !empty($selectedColumns) ? $selectedColumns : $defaultColumns;
    @endphp

    @foreach ($allStudentData as $index => $studentData)
        @php
            $schoolInfo = $studentData['schoolInfo'] ?? null;
            $student = $studentData['students'] && $studentData['students']->isNotEmpty() 
                ? $studentData['students']->first() 
                : null;
            $scores = $studentData['scores'] ?? collect();
            $assessments = $studentData['assessments'] ?? collect();
            $totals = $studentData['totals_summary'] ?? [];
            $attendance = $studentData['attendance_summary'] ?? [];
            $promotionResult = $studentData['promotion_result'] ?? [];
            $studentpp = $studentData['studentpp'] ?? collect();
            $numberOfStudents = $studentData['numberOfStudents'] ?? 0;
            $schoolclass = $studentData['schoolclass'] ?? null;
            $mockResults = $studentData['mock_results'] ?? collect();
            $mockSummary = $studentData['mock_summary'] ?? [];

            $admNo = $student->admissionNo ?? 'N/A';
            $fullName = trim(strtoupper($student->lastname ?? '') . ' ' . ($student->firstname ?? '') . ' ' . ($student->othername ?? ''));
            $className = $schoolclass ? trim(($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arms->arm ?? '')) : 'N/A';
            $termName = $metadata['term'] ?? 'Third Term';
            $sessionName = $metadata['session'] ?? '2025/2026';
            
            $profile = $studentpp && $studentpp->isNotEmpty() ? $studentpp->first() : null;
            
            $promoStatus = $promotionResult['status'] ?? 'awaiting';
            $isPromoTerm = $promotionResult['is_promotional_term'] ?? false;
            $statusLabel = $promotionResult['status_label'] ?? 'Awaiting Decision';
            $promoFailed = $promotionResult['failed_compulsory'] ?? [];
            $reqAvg = $promotionResult['required_average'] ?? null;
            $actAvg = $promotionResult['actual_average'] ?? null;
            $promoTotal = $promotionResult['compulsory_count'] ?? 0;
            $promoPassed = $promotionResult['passed_compulsory'] ?? 0;
            $appliedRule = $promotionResult['applied_rule']['name'] ?? null;
            $ruleDisplay = '';
            if ($appliedRule) {
                $ruleDisplay = preg_replace('/^Rule\s+\d+\s*[-:.]?\s*/i', '', $appliedRule);
                $ruleDisplay = trim($ruleDisplay);
                if (empty($ruleDisplay) || $ruleDisplay === 'null') {
                    $ruleDisplay = '';
                }
            }
            
            $attPct = isset($attendance['attendance_percentage']) ? round($attendance['attendance_percentage'], 1) : 0;
            $attWarn = $attPct < 75;
            $attFound = $attendance['found'] ?? false;

            $hasAnyCompulsory = collect($scores)->contains(fn($s) => $s->is_compulsory ?? false);
            
            $qrData = "Name: {$fullName}\nAdm No: {$admNo}\nClass: {$className}\nTerm: {$termName}\nSession: {$sessionName}\nSchool: " . ($schoolInfo->school_name ?? 'School');
            $qrCodeBase64 = base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(280)
                    ->errorCorrection('H')
                    ->generate($qrData)
            );
            
            $stampSrc = $studentData['school_stamp_base64'] ?? null;
            if (!$stampSrc) {
                $stampSrc = 'data:image/svg+xml;base64,' . base64_encode(
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="#f1f5f9" stroke="#3b82f6" stroke-width="2"/>
                        <text x="50" y="55" text-anchor="middle" fill="#1e293b" font-size="12" font-family="Arial">STAMP</text>
                    </svg>'
                );
            }
            
            $logoSrc = $studentData['school_logo_base64'] ?? null;
            if (!$logoSrc) {
                $logoSrc = 'data:image/svg+xml;base64,' . base64_encode(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="72" height="85" viewBox="0 0 100 100">
                        <rect width="100" height="100" fill="#f8f9fa" stroke="#47b492" stroke-width="2"/>
                        <circle cx="50" cy="40" r="15" fill="#47b492" opacity="0.6"/>
                        <rect x="35" y="60" width="30" height="20" fill="#47b492" opacity="0.6" rx="3"/>
                    </svg>'
                );
            }
            
            $studentImage = $studentData['student_image_base64'] ?? null;
            
            $showMock = in_array('include_mock', $columnsToShow) && $mockResults->isNotEmpty();
        @endphp

        <div class="student-section">
            {{-- SCHOOL NAME HEADER --}}
            <div class="school-name-header">
                <div class="school-full-name">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</div>
                <div class="motto">{{ $schoolInfo->school_motto ?? 'KNOWLEDGE AND VIRTUE' }}</div>
            </div>

            {{-- HEADER: Logo + Contact + Photo --}}
            <table class="header-table">
                <tr>
                    <td width="18%" style="text-align:center; padding: 4px 6px; vertical-align:middle;">
                        <div class="school-logo">
                            <img src="{{ $logoSrc }}" alt="School Logo">
                        </div>
                    </td>
                    <td style="vertical-align:top; padding: 4px 7px;">
                        <table style="border:none; border-collapse:collapse; width:100%; font-size:12px;">
                            <tr>
                                <td style="font-weight:900; color:#1e40af; white-space:nowrap; vertical-align:top; padding:0 4px 0 0;">Address:</td>
                                <td style="vertical-align:top; padding:0;">{{ $schoolInfo->school_address ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight:900; color:#1e40af; white-space:nowrap; padding:0 4px 0 0;">Phone:</td>
                                <td style="vertical-align:top; padding:0;">{{ $schoolInfo->formatted_phones ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight:900; color:#1e40af; white-space:nowrap; padding:0 4px 0 0;">Email:</td>
                                <td style="vertical-align:top; padding:0;">{{ $schoolInfo->school_email ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight:900; color:#1e40af; white-space:nowrap; padding:0 4px 0 0;">Website:</td>
                                <td style="vertical-align:top; padding:0;">{{ $schoolInfo->school_website ?? '—' }}</td>
                            </tr>
                        </table>
                    </td>
                    <td width="20%" style="text-align:right; padding: 4px 6px 4px 0; vertical-align:middle;">
                        <div class="photo-frame">
                            @if(!empty($studentImage))
                                <img src="{{ $studentImage }}" alt="Student Photo">
                            @else
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='72' height='85' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23e2e8f0'/%3E%3Ccircle cx='50' cy='40' r='20' fill='%2394a3b8'/%3E%3Crect x='35' y='65' width='30' height='25' fill='%2394a3b8' rx='4'/%3E%3C/svg%3E" alt="Default Photo">
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <div class="header-divider"></div>
            <div class="header-divider2"></div>

            {{-- REPORT TITLE --}}
            <div class="report-title">
                {{ strtoupper($termName) }} {{ strtoupper($sessionName) }} ACADEMIC SESSION TERMINAL PROGRESS REPORT
            </div>

            {{-- STUDENT INFO BAR --}}
            <div class="student-info-bar">
                <table class="info-table">
                    <tr>
                        <td><span class="info-bar-label">NAME:</span> <span class="info-bar-value">{{ $fullName }}</span></td>
                        <td><span class="info-bar-label">SESSION:</span> <span class="info-bar-value">{{ $sessionName }}</span></td>
                        <td><span class="info-bar-label">TERM:</span> <span class="info-bar-value">{{ $termName }}</span></td>
                        <td><span class="info-bar-label">CLASS:</span> <span class="info-bar-value">{{ $className }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="info-bar-label">ADM NO:</span> <span class="info-bar-value">{{ $admNo }}</span></td>
                        <td><span class="info-bar-label">SCHOOL OPENED:</span> <span class="info-bar-value">{{ $schoolInfo->date_school_opened ? \Carbon\Carbon::parse($schoolInfo->date_school_opened)->format('jS M, Y') : '—' }}</span></td>
                        <td><span class="info-bar-label">NO. IN CLASS:</span> <span class="info-bar-value">{{ $numberOfStudents }}</span></td>
                        <td><span class="info-bar-label">SEX:</span> <span class="info-bar-value">{{ $student->gender ?? '—' }}</span></td>
                    </tr>
                </table>
            </div>

            {{-- RESULT TABLE --}}
            <div class="result-table">
                <table>
                    <thead>
                        <tr>
                            @if(in_array('sn', $columnsToShow))
                                <th class="col-sn">S/N</th>
                            @endif
                            @if(in_array('name', $columnsToShow))
                                <th class="col-name">Subject</th>
                            @endif

                            @foreach ($assessments as $assessment)
                                @if(in_array($assessment->id, $columnsToShow) || in_array('all_assessments', $columnsToShow))
                                    <th class="col-assessment">
                                        {{ $assessment->name }}<br>
                                        <span style="font-size:8px;">({{ $assessment->max_score }})</span>
                                    </th>
                                @endif
                            @endforeach

                            @if(in_array('total', $columnsToShow))
                                <th class="col-total">Total<br><span style="font-size:8px;">(100)</span></th>
                            @endif
                            @if(in_array('bf', $columnsToShow))
                                <th class="col-bf">BF</th>
                            @endif
                            @if(in_array('cum', $columnsToShow))
                                <th class="col-cum">Cum</th>
                            @endif
                            @if(in_array('cum_ave', $columnsToShow))
                                <th class="col-cum">Cum<br><span style="font-size:8px;">Ave</span></th>
                            @endif
                            @if(in_array('grade', $columnsToShow))
                                <th class="col-grade">Grade</th>
                            @endif
                            @if(in_array('compulsory_flag', $columnsToShow))
                                <th class="col-compulsory">Comp.</th>
                            @endif
                            @if(in_array('arm_position', $columnsToShow))
                                <th class="col-position">Arm Pos<br>(Total)</th>
                            @endif
                            @if(in_array('arm_position_cum', $columnsToShow))
                                <th class="col-position">Arm Pos<br>(Cum)</th>
                            @endif
                            @if(in_array('position_total', $columnsToShow))
                                <th class="col-position">Class Pos<br>(Total)</th>
                            @endif
                            @if(in_array('position', $columnsToShow))
                                <th class="col-position">Class Pos<br>(Cum)</th>
                            @endif
                            @if(in_array('class_average', $columnsToShow))
                                <th class="col-class-average">Subject<br><span style="font-size:8px;">Ave</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scores as $scoreIndex => $score)
                            @php
                                $total = (float)($score->total ?? 0);
                                $isFailing = $total < 50 && $total > 0;
                                
                                $posCum = $score->position ?? null;
                                $posTotal = $score->position_total ?? null;
                                $armPos = $score->arm_position ?? null;
                                $armCum = $score->arm_position_cum ?? null;
                                
                                $posCumClass = ($posCum == 1) ? 'position-1' : (($posCum == 2) ? 'position-2' : (($posCum == 3) ? 'position-3' : ''));
                                $posTotalClass = ($posTotal == 1) ? 'position-1' : (($posTotal == 2) ? 'position-2' : (($posTotal == 3) ? 'position-3' : ''));
                                $armPosClass = ($armPos == 1) ? 'position-1' : (($armPos == 2) ? 'position-2' : (($armPos == 3) ? 'position-3' : ''));
                                $armCumClass = ($armCum == 1) ? 'position-1' : (($armCum == 2) ? 'position-2' : (($armCum == 3) ? 'position-3' : ''));
                                
                                $grade = $score->grade ?? '-';
                                $gradeClass = match(true) {
                                    str_starts_with($grade, 'A') => 'grade-A1',
                                    str_starts_with($grade, 'B2') => 'grade-B2',
                                    str_starts_with($grade, 'B3') => 'grade-B3',
                                    str_starts_with($grade, 'B') => 'grade-B2',
                                    str_starts_with($grade, 'C4') => 'grade-C4',
                                    str_starts_with($grade, 'C5') => 'grade-C5',
                                    str_starts_with($grade, 'C6') => 'grade-C6',
                                    str_starts_with($grade, 'C') => 'grade-C4',
                                    str_starts_with($grade, 'D') => 'grade-D7',
                                    str_starts_with($grade, 'E') => 'grade-E8',
                                    default => 'grade-F9',
                                };

                                $isCompulsory = $score->is_compulsory ?? false;
                                $isFailingGrade = $gradeClass === 'grade-F9';
                            @endphp
                            <tr>
                                @if(in_array('sn', $columnsToShow))
                                    <td>{{ $scoreIndex + 1 }}</td>
                                @endif
                                @if(in_array('name', $columnsToShow))
                                    <td class="subject-name">
                                        {{ $score->subject_name ?? 'NO INFO' }}
                                        @if($isCompulsory)
                                            <span class="compulsory-mark {{ $isFailingGrade ? 'compulsory-mark-fail' : 'compulsory-mark-pass' }}"
                                                  title="Compulsory Subject — {{ $isFailingGrade ? 'Failed' : 'Passed' }}">*</span>
                                        @endif
                                    </td>
                                @endif

                                @foreach ($assessments as $assessment)
                                    @if(in_array($assessment->id, $columnsToShow) || in_array('all_assessments', $columnsToShow))
                                        @php
                                            $assessmentScore = 0;
                                            if (isset($score->assessment_scores)) {
                                                $found = $score->assessment_scores->firstWhere('assessment_id', $assessment->id);
                                                $assessmentScore = $found ? $found->score : 0;
                                            }
                                            $isLow = $assessmentScore < ($assessment->max_score * 0.5);
                                        @endphp
                                        <td @if($isLow && is_numeric($assessmentScore)) class="highlight-red" @endif>
                                            {{ $assessmentScore !== null && $assessmentScore !== '' ? number_format($assessmentScore, 1) : '-' }}
                                        </td>
                                    @endif
                                @endforeach

                                @if(in_array('total', $columnsToShow))
                                    <td @if($isFailing) class="highlight-red" @endif>
                                        {{ number_format(round($total), 0) }}
                                    </td>
                                @endif
                                @if(in_array('bf', $columnsToShow))
                                    <td>{{ number_format($score->bf ?? 0, 1) }}</td>
                                @endif
                                @if(in_array('cum', $columnsToShow))
                                    <td>{{ number_format(round($score->cum ?? 0), 0) }}</td>
                                @endif
                                @if(in_array('cum_ave', $columnsToShow))
                                    <td>{{ number_format(round($score->cum_ave ?? 0), 0) }}</td>
                                @endif
                                @if(in_array('grade', $columnsToShow))
                                    <td class="{{ $gradeClass }}">{{ $grade }}</td>
                                @endif
                                @if(in_array('compulsory_flag', $columnsToShow))
                                    <td>
                                        @if(!$isCompulsory)
                                            <span class="compulsory-badge-no">&mdash;</span>
                                        @elseif($isFailingGrade)
                                            <span class="compulsory-badge-fail">NO</span>
                                        @else
                                            <span class="compulsory-badge-pass">YES</span>
                                        @endif
                                    </td>
                                @endif
                                @if(in_array('arm_position', $columnsToShow))
                                    <td class="{{ $armPosClass }}">{{ formatOrdinal($armPos) }}</td>
                                @endif
                                @if(in_array('arm_position_cum', $columnsToShow))
                                    <td class="{{ $armCumClass }}">{{ formatOrdinal($armCum) }}</td>
                                @endif
                                @if(in_array('position_total', $columnsToShow))
                                    <td class="{{ $posTotalClass }}">{{ formatOrdinal($posTotal) }}</td>
                                @endif
                                @if(in_array('position', $columnsToShow))
                                    <td class="{{ $posCumClass }}">{{ formatOrdinal($posCum) }}</td>
                                @endif
                                @if(in_array('class_average', $columnsToShow))
                                    <td>{{ number_format($score->class_average ?? 0, 1) }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="30" style="text-align:center; padding:8px;">No scores available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if(in_array('name', $columnsToShow) && $hasAnyCompulsory)
                    <div class="compulsory-note">
                        <span class="compulsory-mark compulsory-mark-pass" style="margin-left:0;">*</span> Compulsory subject &mdash;
                        green = passed, red = failed. Must be passed to qualify for promotion.
                    </div>
                @endif
            </div>

            {{-- TOTALS SUMMARY --}}
            <div class="totals-summary">
                TOTAL OBTAINED: {{ number_format($totals['obtained'] ?? 0, 1) }}
                &nbsp;&nbsp;|&nbsp;&nbsp;
                TOTAL OBTAINABLE: {{ $totals['obtainable'] ?? 0 }}
                &nbsp;&nbsp;|&nbsp;&nbsp;
                % OBTAINED: {{ $totals['percentage'] ?? 0 }}%
                &nbsp;&nbsp;|&nbsp;&nbsp;
                GRADED ON: {{ ($studentData['grade_basis'] ?? $gradeBasis) === 'cum_ave' ? 'CUMULATIVE AVERAGE' : 'TERM TOTAL' }}
            </div>

            {{-- PROMOTION BADGE --}}
            @if($isPromoTerm)
                @if($promoStatus === 'promoted')
                    <div class="promo-card promo-promoted">
                        <div class="promo-title">{{ $statusLabel }}</div>
                        <div class="promo-message">
                            @if($promoTotal > 0)
                                Passed {{ $promoPassed }}/{{ $promoTotal }} compulsory subject(s)
                            @else
                                Met all promotion requirements
                            @endif
                            @if($reqAvg !== null && $actAvg !== null)
                                <br>Average: {{ number_format($actAvg, 1) }}%
                                (Required: {{ number_format($reqAvg, 1) }}%) ✓
                            @endif
                            @if(!empty($ruleDisplay))
                                <br><span style="font-size:8.5px; opacity:0.8;">{{ $ruleDisplay }}</span>
                            @endif
                        </div>
                    </div>
                @elseif($promoStatus === 'trial')
                    <div class="promo-card promo-trial">
                        <div class="promo-title">{{ $statusLabel }}</div>
                        <div class="promo-message">Promoted conditionally - needs improvement</div>
                        @if($reqAvg !== null && $actAvg !== null)
                            <div>Average: {{ number_format($actAvg, 1) }}%
                            (Required: {{ number_format($reqAvg, 1) }}%)</div>
                        @endif
                        @if(!empty($ruleDisplay))
                            <div style="font-size:8.5px; opacity:0.8; margin-top:2px;">{{ $ruleDisplay }}</div>
                        @endif
                    </div>
                @elseif($promoStatus === 'see_principal')
                    <div class="promo-card promo-principal">
                        <div class="promo-title">{{ $statusLabel }}</div>
                        <div class="promo-message">Parents must see the Principal for discussion</div>
                        @if($reqAvg !== null && $actAvg !== null)
                            <div>Average: {{ number_format($actAvg, 1) }}%
                            (Required: {{ number_format($reqAvg, 1) }}%)</div>
                        @endif
                        @if(!empty($ruleDisplay))
                            <div style="font-size:8.5px; opacity:0.8; margin-top:2px;">{{ $ruleDisplay }}</div>
                        @endif
                    </div>
                @elseif($promoStatus === 'repeated' || $promoStatus === 'repeat')
                    <div class="promo-card promo-repeated">
                        <div class="promo-title">{{ $statusLabel }}</div>
                        @if(!empty($promoFailed))
                            <div class="promo-message">
                                Failed: {{ collect($promoFailed)->pluck('subject')->filter()->implode(', ') }}
                            </div>
                        @endif
                        @if($reqAvg !== null && $actAvg !== null)
                            <div>Average: {{ number_format($actAvg, 1) }}%
                            (Required: {{ number_format($reqAvg, 1) }}%)</div>
                        @endif
                        @if(!empty($ruleDisplay))
                            <div style="font-size:8.5px; opacity:0.8; margin-top:2px;">{{ $ruleDisplay }}</div>
                        @endif
                    </div>
                @else
                    <div class="promo-card promo-awaiting">
                        <div class="promo-title">AWAITING DECISION</div>
                        <div class="promo-message">Promotion decision pending further review</div>
                    </div>
                @endif
            @else
                <div class="promo-card promo-awaiting">
                    <div class="promo-title">NON-PROMOTIONAL TERM</div>
                    <div class="promo-message">This term is not a promotional term. Promotion is only assessed at the end of the academic year (Third Term).</div>
                </div>
            @endif

            {{-- MOCK RESULTS SECTION --}}
            @if($showMock)
                <div class="mock-section">
                    <div class="mock-header">📝 MOCK EXAMINATION RESULTS</div>
                    <table class="mock-table">
                        <thead>
                            <tr>
                                <th style="width:25px;">S/N</th>
                                <th style="text-align:left; padding-left:6px;">Subject</th>
                                <th style="width:45px;">Exam</th>
                                <th style="width:45px;">Total</th>
                                <th style="width:40px;">Grade</th>
                                <th style="width:40px;">Position</th>
                                <th style="width:45px;">Class Avg</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mockResults as $mockIndex => $mock)
                                @php
                                    $mockGrade = $mock->grade ?? '-';
                                    $mockGradeClass = match(true) {
                                        str_starts_with($mockGrade, 'A') => 'grade-A1',
                                        str_starts_with($mockGrade, 'B2') => 'grade-B2',
                                        str_starts_with($mockGrade, 'B3') => 'grade-B3',
                                        str_starts_with($mockGrade, 'B') => 'grade-B2',
                                        str_starts_with($mockGrade, 'C4') => 'grade-C4',
                                        str_starts_with($mockGrade, 'C5') => 'grade-C5',
                                        str_starts_with($mockGrade, 'C6') => 'grade-C6',
                                        str_starts_with($mockGrade, 'C') => 'grade-C4',
                                        str_starts_with($mockGrade, 'D') => 'grade-D7',
                                        str_starts_with($mockGrade, 'E') => 'grade-E8',
                                        default => 'grade-F9',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $mockIndex + 1 }}</td>
                                    <td class="subject-name">{{ $mock->subject_name ?? 'Unknown' }}</td>
                                    <td>{{ number_format($mock->exam ?? 0, 1) }}</td>
                                    <td>{{ number_format($mock->total ?? 0, 1) }}</td>
                                    <td class="{{ $mockGradeClass }}">{{ $mockGrade }}</td>
                                    <td>{{ formatOrdinal($mock->position ?? null) }}</td>
                                    <td>{{ number_format($mock->class_average ?? 0, 1) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mock-summary">
                        TOTAL OBTAINED: {{ number_format($mockSummary['obtained'] ?? 0, 1) }}
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        TOTAL OBTAINABLE: {{ $mockSummary['obtainable'] ?? 0 }}
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        PERCENTAGE: {{ $mockSummary['percentage'] ?? 0 }}%
                    </div>
                </div>
            @endif

            {{-- REMARKS --}}
            <table style="width:calc(100% - 16px); border:2px solid #000000; border-collapse:collapse; margin:5px 8px 3px;">
                <tbody>
                    <tr>
                        <td style="border:1px solid #000000; padding:4px 6px; background:white; vertical-align:top; font-size:11.5px; width:50%;">
                            <div style="font-weight:700; margin-bottom:3px; font-size:12px; border-bottom:1px solid #ccc; display:inline-block;">Class Teacher's Remark</div>
                            <div>{{ $profile ? ($profile->classteachercomment ?? 'NO COMMENT') : 'NO COMMENT' }}</div>
                        </td>
                        <td style="border:1px solid #000000; padding:4px 6px; background:white; vertical-align:top; font-size:11.5px; width:50%;">
                            <div style="font-weight:700; margin-bottom:3px; font-size:12px; border-bottom:1px solid #ccc; display:inline-block;">Principal's Remark</div>
                            <div>{{ $profile ? ($profile->principalscomment ?? 'NO COMMENT') : 'NO COMMENT' }}</div>
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- BOTTOM STRIP --}}
            <div class="bottom-strip">
                <table>
                    <tr>
                        <td class="cell-qr">
                            <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code">
                            <div class="qr-label">Scan for Verification</div>
                        </td>
                        <td class="cell-footer">
                            <div><strong>Issued:</strong> <span class="text-dot-space2">{{ now()->format('jS F, Y') }}</span></div>
                            <div style="margin-top:3px;"><strong>Collected by:</strong> <span class="text-dot-space2">.......................................</span></div>
                            <div style="margin-top:3px;"><strong>Next Term Begins:</strong> <span class="text-dot-space2">
                                @php
                                    $nextTerm = $schoolInfo->date_next_term_begins ?? null;
                                    echo $nextTerm ? \Carbon\Carbon::parse($nextTerm)->format('jS F, Y') : '........................';
                                @endphp
                            </span></div>
                            <div class="powered-by">Powered by Qudroid Systems</div>
                        </td>
                        <td class="cell-stamp">
                            <img src="{{ $stampSrc }}" alt="School Stamp">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach
</body>
</html>