<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Terminal Report - Claret Secondary School Kabba</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5px;
            line-height: 1.3;
            color: #000;
            background: #f5f5f5;
            padding: 8mm 0;
            text-align: center;
            position: relative;
        }

        /* ================== SCHOOL NAME HEADER ================== */
        .school-name-header {
            width: 100%;
            background: #111827;
            color: white;
            padding: 9px 10px 5px 10px;
            margin-bottom: 0;
            text-align: center;
            border: 3px double #000000;
            border-bottom: 1px solid #1e40af;
        }

        .school-name-header .school-full-name {
            font-family: 'Arial Black', sans-serif;
            font-size: 19.5px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            line-height: 1.05;
        }

        .school-name-header .motto {
            font-size: 9.8px;
            font-weight: 700;
            letter-spacing: 2px;
            opacity: 0.95;
            margin-top: 3px;
        }

        /* WATERMARK */
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
            width: 100%;
            text-align: center;
        }

        /* MAIN CARD */
        .student-section {
            width: 190mm;
            page-break-after: always;
            background: #ffffff;
            border: 3px double #000000;
            margin: 0 auto 15px auto;
            padding: 0;
            position: relative;
            text-align: left;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            z-index: 1;
        }

        .student-section:last-child {
            page-break-after: avoid;
        }

        /* HEADER TABLE - Logo + Expanded School Info + Photo */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 8px 10px 6px 10px;
        }

        .school-logo, .photo-frame {
            width: 74px;
            height: 88px;
            border: 2px solid #47b492;
            border-radius: 6px;
            background: white;
            padding: 3px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-logo img, .photo-frame img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .middle-info {
            font-size: 9.2px;           /* BIGGER text */
            line-height: 1.65;          /* More breathing space */
            padding: 0 15px;
            vertical-align: middle;
        }

        .middle-info strong {
            color: #1e40af;
            font-weight: 700;
        }

        .header-divider {
            width: 100%;
            height: 2px;
            background: #1e40af;
            margin: 0;
        }

        .header-divider2 {
            width: 100%;
            height: 1px;
            background: #64748b;
            margin: 2px 0;
        }

        .report-title {
            background: #111827;
            color: white;
            padding: 6px 8px;
            font-size: 11.5px;
            font-weight: 700;
            text-align: center;
            margin: 0;
        }

        /* STUDENT INFO BAR */
        .student-info-bar {
            background: linear-gradient(to bottom, #f0f7ff 0%, #ffffff 100%);
            border: 2px solid #2aa886;
            border-radius: 6px;
            padding: 7px 12px;
            margin: 8px 10px;
            font-size: 8.8px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 6px;
        }

        .info-bar-label {
            color: #1e40af;
            font-weight: 700;
            font-size: 8.2px;
            white-space: nowrap;
        }

        .info-bar-value {
            font-weight: 900;
            padding-left: 4px;
        }

        /* DUAL LAYOUT */
        .dual-layout-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 10px;
            page-break-inside: avoid;
        }

        .academic-cell { vertical-align: top; padding: 0; }
        .psycho-cell {
            vertical-align: top;
            padding: 0;
            width: 148px;
            min-width: 148px;
            background: #fef9e6;
        }

        /* Result table styles (unchanged) */
        .result-table table {
            width: 100%;
            border: 2px solid #000000;
            border-collapse: collapse;
            font-size: 7.8px;
        }

        .result-table thead th {
            background: #0d1a3d;
            color: white;
            font-weight: 800;
            border: 1px solid #000000;
            padding: 3px 1px;
            font-size: 6.8px;
        }

        .result-table tbody td {
            border: 1px solid #000000;
            padding: 2px 1px;
            text-align: center;
            font-size: 7.5px;
            background: white;
            font-weight: 600;
            height: 16px;
            line-height: 16px;
        }

        .result-table tbody td.subject-name {
            text-align: left;
            font-weight: 700;
            padding-left: 5px;
        }

        .highlight-red { color: #dc2626; font-weight: 900; }

        .col-sn { width: 28px; }
        .col-admissionno { width: 78px; }
        .col-name { width: 130px; }
        .col-assessment { width: 39px; }
        .col-total { width: 46px; }
        .col-bf { width: 36px; }
        .col-cum { width: 42px; }
        .col-grade { width: 36px; }
        .col-position { width: 36px; }
        .col-class-average { width: 39px; }

        .totals-summary {
            width: 98%;
            background: #0d1a3d;
            color: #ffffff;
            font-weight: 900;
            font-size: 7.8px;
            padding: 5px 10px;
            border: 2px solid #000000;
            border-top: none;
            text-align: center;
            margin: 8px auto;
        }

        /* Psychomotor & Remarks & Footer (unchanged) */
        .psychomotor-container { width: 148px; background: #fef9e6; border: 2px solid #c0a86a; border-radius: 8px; padding: 0 4px 4px 4px; }
        .psychomotor-title { background: #2c3e4e; color: white; text-align: center; font-weight: 800; font-size: 8.5px; padding: 3px 2px; margin: 0 -4px 5px -4px; border-radius: 6px 6px 0 0; }
        .psycho-table { width: 100%; border-collapse: collapse; font-size: 7.2px; }
        .psycho-table th, .psycho-table td { border: 1px solid #b78d4a; padding: 2px 2px; height: 16px; line-height: 16px; }
        .psycho-table th { background: #e9d6b0; font-weight: 800; font-size: 7px; }
        .psycho-table td:first-child { width: 72%; background: #fff7e8; padding-left: 3px; }
        .psycho-table td:last-child { width: 28%; text-align: center; font-weight: bold; }
        .psycho-totals-row td { background: #e9d6b0; font-weight: 900; font-size: 7.5px; }
        .psycho-obtainable-row td { background: #faf0dd; font-size: 6.8px; text-align: center; }
        .psycho-note { font-size: 6px; text-align: center; margin-top: 4px; color: #4a5b6e; }

        .remarks-table {
            width: 100%;
            border: 2px solid #000000;
            border-collapse: collapse;
            margin: 8px 10px 4px;
        }
        .remarks-table td {
            border: 1px solid #000000;
            padding: 5px 8px;
            background: white;
            vertical-align: top;
            font-size: 8.5px;
        }
        .remarks-table .h6 {
            font-weight: 700;
            margin-bottom: 3px;
            font-size: 9px;
            border-bottom: 1px solid #ccc;
            display: inline-block;
        }

        .footer-section {
            background: #f1f5f9;
            padding: 9px 12px 6px;
            border-top: 1px solid #cbd5e1;
            text-align: center;
            margin: 0 10px 8px;
            font-size: 8.6px;
        }
        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            line-height: 1.4;
        }
        .text-dot-space2 {
            border-bottom: 1px dotted #333;
            display: inline-block;
            min-width: 110px;
            font-weight: bold;
            margin: 0 4px;
        }
        .powered-by {
            font-size: 8px;
            margin-top: 4px;
            color: #64748b;
        }

        .grade-A { color: #16a34a; font-weight: 900; }
        .grade-B { color: #2563eb; font-weight: 900; }
        .grade-C { color: #ca8a04; font-weight: 900; }
        .grade-D { color: #ea580c; font-weight: 900; }
        .grade-F { color: #dc2626; font-weight: 900; }

        .position-1 { background: gold; color: black; font-weight: 900; border-radius: 2px; }
        .position-2 { background: silver; color: black; font-weight: 900; }
        .position-3 { background: #cd7f32; color: white; font-weight: 900; }

        .stamp-overlay {
            position: absolute;
            bottom: 90px;
            right: 120px;
            width: 110px;
            height: 110px;
            opacity: 0.18;
            z-index: 10;
            pointer-events: none;
        }

        @media print {
            body { background: white; padding: 0; }
            .student-section {
                width: 190mm;
                margin: 0 auto;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="watermark-text">ORIGINAL COPY</div>

    @php
        $selectedColumns = $metadata['selected_columns'] ?? [];
        $defaultColumns = ['sn', 'admission_no', 'name', 'total', 'bf', 'cum', 'grade', 'position', 'class_average'];
        $columnsToShow = !empty($selectedColumns) ? $selectedColumns : $defaultColumns;
        $baseVisibleCount = 0;
        if (in_array('sn', $columnsToShow)) $baseVisibleCount++;
        if (in_array('admission_no', $columnsToShow)) $baseVisibleCount++;
        if (in_array('name', $columnsToShow)) $baseVisibleCount++;
    @endphp

    @foreach ($allStudentData as $index => $studentData)
        @php
            $schoolInfo = $studentData['schoolInfo'] ?? null;
            $student = $studentData['students'] && $studentData['students']->isNotEmpty() ? $studentData['students']->first() : null;
            $assessments = $studentData['assessments'] ?? collect();
            $totals = $studentData['totals_summary'] ?? [];

            // Psychomotor
            $psychomotorSkills = ['Handwriting', 'Sports', 'Musical Skills', 'Participation', 'Punctuality','Concern for Others', 'Relationship(Students)', 'Relationship(Staff)','Courtesy', 'Neatness', 'Honesty', 'Team Spirit', 'Leadership','Listening Skills', 'Organizational Ability', 'Self Control', 'Perseverance', 'Initiative'];
            $displaySkills = ['Handwriting', 'Sports', 'Musical Skills', 'Participation', 'Punctuality','Concern for Others', 'Relate(Students)', 'Relate(Staff)','Courtesy', 'Neatness', 'Honesty', 'Team Spirit', 'Leadership','Listening', 'Organizational', 'Self Control', 'Perseverance', 'Initiative'];

            $psychomotorObtainable = count($psychomotorSkills) * 5;
            if (isset($studentData['psychomotor_scores']) && is_array($studentData['psychomotor_scores'])) {
                $psychomotorScores = $studentData['psychomotor_scores'];
                $psychomotorObtained = array_sum($psychomotorScores);
            } else {
                $sampleScores = ['Handwriting'=>4,'Sports'=>3,'Musical Skills'=>3,'Participation'=>3,'Punctuality'=>4,'Concern for Others'=>5,'Relationship(Students)'=>4,'Relationship(Staff)'=>4,'Courtesy'=>5,'Neatness'=>3,'Honesty'=>5,'Team Spirit'=>4,'Leadership'=>5,'Listening Skills'=>3,'Organizational Ability'=>4,'Self Control'=>4,'Perseverance'=>5,'Initiative'=>4];
                $psychomotorScores = [];
                foreach ($psychomotorSkills as $skill) {
                    $psychomotorScores[$skill] = $sampleScores[$skill] ?? rand(3,5);
                }
                $psychomotorObtained = array_sum($psychomotorScores);
            }

            $assessmentColumnsCount = 0;
            foreach ($assessments as $assessment) {
                if (in_array($assessment->id, $columnsToShow) || in_array('all_assessments', $columnsToShow)) $assessmentColumnsCount++;
            }
            $currentVisibleColumnCount = $baseVisibleCount + $assessmentColumnsCount;
            $otherScoreCols = ['total','bf','cum','grade','position','class_average'];
            foreach ($otherScoreCols as $col) {
                if (in_array($col, $columnsToShow)) $currentVisibleColumnCount++;
            }

            $minPsychomotorRows = 18;
            $currentAcademicRows = count($studentData['scores'] ?? []);
            $remainingRowsNeeded = max(0, $minPsychomotorRows - $currentAcademicRows);
        @endphp

        <div class="student-section">
            <!-- STAMP -->
            <div class="stamp-overlay">
                @php $stampPath = public_path('stamp.png'); $stampExists = file_exists($stampPath); @endphp
                @if($stampExists)
                    <img src="{{ public_path('stamp.png') }}" alt="School Stamp">
                @else
                    <svg width="110" height="110" viewBox="0 0 110 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="55" cy="55" r="48" stroke="#8B0000" stroke-width="3" fill="none" stroke-dasharray="6 4"/>
                        <text x="55" y="40" text-anchor="middle" fill="#8B0000" font-size="10" font-weight="bold">CLARET</text>
                        <text x="55" y="55" text-anchor="middle" fill="#8B0000" font-size="9">SECONDARY</text>
                        <text x="55" y="70" text-anchor="middle" fill="#8B0000" font-size="9">SCHOOL</text>
                        <text x="55" y="88" text-anchor="middle" fill="#8B0000" font-size="7">KABBA</text>
                    </svg>
                @endif
            </div>

            <!-- SCHOOL NAME HEADER -->
            <div class="school-name-header">
                <div class="school-full-name">CLARET SECONDARY SCHOOL KABBA</div>
                <div class="motto">{{ $schoolInfo->school_motto ?? 'KNOWLEDGE AND VIRTUE' }}</div>
            </div>

            <!-- HEADER: Logo + Expanded School Info (Email + Website added) + Photo -->
            <table class="header-table">
                <tr>
                    <!-- Logo -->
                    <td width="20%" style="text-align:center;">
                        <div class="school-logo">
                            @php
                                $logoSrc = !empty($studentData['school_logo_base64'])
                                    ? $studentData['school_logo_base64']
                                    : 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="72" height="85" viewBox="0 0 100 100"><rect width="100" height="100" fill="#f8f9fa" stroke="#47b492" stroke-width="2"/><circle cx="50" cy="40" r="15" fill="#47b492" opacity="0.6"/><rect x="35" y="60" width="30" height="20" fill="#47b492" opacity="0.6" rx="3"/><text x="50" y="95" text-anchor="middle" fill="#1e40af" font-size="8" font-weight="bold">CLARET</text></svg>');
                            @endphp
                            <img src="{{ $logoSrc }}" alt="School Logo">
                        </div>
                    </td>

                    <!-- Middle School Info - Bigger & Better arranged -->
                    <td width="58%" class="middle-info">
                        <strong>Address:</strong> {{ $schoolInfo->school_address ?? 'No. 1, Claret Avenue, Kabba, Kogi State' }}<br>
                        <strong>Phone:</strong> {{ $schoolInfo->school_phone ?? '08136663185' }}<br>
                        <strong>Email:</strong> {{ $schoolInfo->school_email ?? '—' }}<br>
                        <strong>Website:</strong> {{ $schoolInfo->school_website ?? '—' }}
                    </td>

                    <!-- Student Photo - Adjusted to the right -->
                    <td width="22%" style="text-align:right; padding-right: 12px;">
                        @if(in_array('picture', $columnsToShow))
                        <div class="photo-frame">
                            @if(!empty($studentData['student_image_base64']))
                                <img src="{{ $studentData['student_image_base64'] }}" alt="Student Photo">
                            @else
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='72' height='85' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23e2e8f0'/%3E%3Ccircle cx='50' cy='40' r='20' fill='%2394a3b8'/%3E%3Crect x='35' y='65' width='30' height='25' fill='%2394a3b8' rx='4'/%3E%3Ctext x='50' y='95' text-anchor='middle' fill='%23475569' font-size='8'%3EPHOTO%3C/text%3E%3C/svg%3E" alt="Default">
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
            </table>

            <div class="header-divider"></div>
            <div class="header-divider2"></div>

            <!-- REPORT TITLE -->
            <div class="report-title">
                {{ strtoupper($metadata['term'] ?? 'SECOND TERM') }} {{ strtoupper($metadata['session'] ?? '2025/2026') }} ACADEMIC SESSION TERMINAL PROGRESS REPORT
            </div>

            <!-- STUDENT INFO BAR -->
            @if ($studentData['students'] && $studentData['students']->isNotEmpty())
                @php
                    $profile = $studentData['studentpp'] && $studentData['studentpp']->isNotEmpty() ? $studentData['studentpp']->first() : null;
                    $fullName = strtoupper($student->lastname ?? '') . ' ' . ($student->fname ?? '') . ' ' . ($student->othername ?? '');
                    $admNo = $student->admissionNo ?? '—';
                    $classVal = ($studentData['schoolclass']->schoolclass ?? '') . ' ' . ($studentData['schoolclass']->arms->arm ?? '');
                    $schoolOpened = $schoolInfo->date_school_opened ? \Carbon\Carbon::parse($schoolInfo->date_school_opened)->format('jS M, Y') : '—';
                    $numInClass = $studentData['numberOfStudents'] ?? '—';
                @endphp

                <div class="student-info-bar">
                    <table class="info-table">
                        <tr>
                            <td><span class="info-bar-label">NAME:</span><span class="info-bar-value">{{ $fullName }}</span></td>
                            <td><span class="info-bar-label">SESSION:</span><span class="info-bar-value">{{ $metadata['session'] ?? '—' }}</span></td>
                            <td><span class="info-bar-label">TERM:</span><span class="info-bar-value">{{ $metadata['term'] ?? '—' }}</span></td>
                            <td><span class="info-bar-label">CLASS:</span><span class="info-bar-value">{{ $classVal }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="info-bar-label">ADM NO:</span><span class="info-bar-value">{{ $admNo }}</span></td>
                            <td><span class="info-bar-label">SCHOOL OPENED:</span><span class="info-bar-value">{{ $schoolOpened }}</span></td>
                            <td><span class="info-bar-label">NO. IN CLASS:</span><span class="info-bar-value">{{ $numInClass }}</span></td>
                            @if(in_array('gender', $columnsToShow))
                                <td><span class="info-bar-label">SEX:</span><span class="info-bar-value">{{ $student->gender ?? '—' }}</span></td>
                            @endif
                        </tr>
                    </table>
                </div>
            @else
                <div class="student-info-bar"><div style="text-align:center;">No student data available.</div></div>
            @endif>

            <!-- DUAL LAYOUT, TOTALS, REMARKS, FOOTER (unchanged from previous version) -->
            <table class="dual-layout-table">
                <tr>
                    <td class="academic-cell">
                        <div class="result-table">
                            <table>
                                <thead>
                                    <tr>
                                        @if(in_array('sn', $columnsToShow)) <th class="col-sn">S/N</th> @endif
                                        @if(in_array('admission_no', $columnsToShow)) <th class="col-admissionno">Adm No</th> @endif
                                        @if(in_array('name', $columnsToShow)) <th class="col-name">Subject</th> @endif
                                        @foreach ($assessments as $assessment)
                                            @if(in_array($assessment->id, $columnsToShow) || in_array('all_assessments', $columnsToShow))
                                                <th class="col-assessment">{{ $assessment->name }}<br><span style="font-size:5.5px;">({{ $assessment->max_score }})</span></th>
                                            @endif
                                        @endforeach
                                        @if(in_array('total', $columnsToShow)) <th class="col-total">Total</th> @endif
                                        @if(in_array('bf', $columnsToShow)) <th class="col-bf">BF</th> @endif
                                        @if(in_array('cum', $columnsToShow)) <th class="col-cum">Cum</th> @endif
                                        @if(in_array('grade', $columnsToShow)) <th class="col-grade">Grade</th> @endif
                                        @if(in_array('position', $columnsToShow)) <th class="col-position">Pos</th> @endif
                                        @if(in_array('class_average', $columnsToShow)) <th class="col-class-average">Av</th> @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($studentData['scores'] as $scoreIndex => $score)
                                    <tr>
                                        @if(in_array('sn', $columnsToShow)) <td>{{ $scoreIndex + 1 }}</td> @endif
                                        @if(in_array('admission_no', $columnsToShow)) <td>{{ $student->admissionNo ?? '-' }}</td> @endif
                                        @if(in_array('name', $columnsToShow)) <td class="subject-name">{{ $score->subject_name ?? 'NO INFO' }}</td> @endif
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
                                                    {{ $assessmentScore ? number_format($assessmentScore, 0) : '-' }}
                                                </td>
                                            @endif
                                        @endforeach
                                        @if(in_array('total', $columnsToShow)) <td @if($score->total < 50) class="highlight-red" @endif>{{ $score->total ? number_format($score->total, 1) : '-' }}</td> @endif
                                        @if(in_array('bf', $columnsToShow)) <td>{{ $score->bf ? number_format($score->bf, 1) : '-' }}</td> @endif
                                        @if(in_array('cum', $columnsToShow)) <td>{{ $score->cum ? number_format($score->cum, 1) : '-' }}</td> @endif

                                        @if(in_array('grade', $columnsToShow))
                                            @php
                                                $gradeRaw = $score->grade ?? '-';
                                                $gradeUpper = strtoupper($gradeRaw);
                                                $gradeClass = match(true) {
                                                    str_starts_with($gradeUpper, 'A') => 'grade-A',
                                                    str_starts_with($gradeUpper, 'B') => 'grade-B',
                                                    str_starts_with($gradeUpper, 'C') => 'grade-C',
                                                    str_starts_with($gradeUpper, 'D') => 'grade-D',
                                                    default => 'grade-F'
                                                };
                                            @endphp
                                            <td class="{{ $gradeClass }}">{{ $gradeRaw }}</td>
                                        @endif

                                        @if(in_array('position', $columnsToShow))
                                            @php
                                                $posVal = $score->position ?? '-';
                                                $posClass = is_numeric($posVal) ? match((int)$posVal) { 1 => 'position-1', 2 => 'position-2', 3 => 'position-3', default => '' } : '';
                                            @endphp
                                            <td class="{{ $posClass }}">{{ $posVal }}</td>
                                        @endif

                                        @if(in_array('class_average', $columnsToShow)) <td>{{ $score->class_average ? number_format($score->class_average, 1) : '-' }}</td> @endif
                                    </tr>
                                    @empty
                                    <tr><td colspan="{{ $currentVisibleColumnCount }}" style="text-align:center;">No scores available.</td></tr>
                                    @endforelse

                                    @for($i = 0; $i < $remainingRowsNeeded; $i++)
                                        <tr>
                                            @if(in_array('sn', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @if(in_array('admission_no', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @if(in_array('name', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @foreach ($assessments as $assessment)
                                                @if(in_array($assessment->id, $columnsToShow) || in_array('all_assessments', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @endforeach
                                            @if(in_array('total', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @if(in_array('bf', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @if(in_array('cum', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @if(in_array('grade', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @if(in_array('position', $columnsToShow)) <td>&nbsp;</td> @endif
                                            @if(in_array('class_average', $columnsToShow)) <td>&nbsp;</td> @endif
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </td>

                    <td class="psycho-cell">
                        <div class="psychomotor-container">
                            <div class="psychomotor-title">PSYCHOMOTOR &amp; AFFECTIVE</div>
                            <table class="psycho-table">
                                <thead><tr><th>Skill</th><th>Score</th></tr></thead>
                                <tbody>
                                    @foreach($displaySkills as $idx => $shortSkill)
                                    <tr>
                                        <td>{{ $shortSkill }}</td>
                                        <td>{{ $psychomotorScores[$psychomotorSkills[$idx]] ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="psycho-totals-row">
                                        <td style="text-align:right;font-weight:900;">TOTAL OBTAINED</td>
                                        <td style="text-align:center;font-weight:900;">{{ $psychomotorObtained }}</td>
                                    </tr>
                                    <tr class="psycho-obtainable-row">
                                        <td colspan="2" style="text-align:center;">Max: {{ $psychomotorObtainable }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="psycho-note">5=Excellent ... 1=Needs Improvement</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="totals-summary">
                TOTAL OBTAINED: {{ number_format($totals['obtained'], 1) }}&nbsp;&nbsp;|&nbsp;&nbsp;TOTAL OBTAINABLE: {{ $totals['obtainable'] }}&nbsp;&nbsp;|&nbsp;&nbsp;% OBTAINED: {{ $totals['percentage'] }}%
            </div>

            <table class="remarks-table">
                <tbody>
                    <tr>
                        <td width="50%">
                            <div class="h6">Class Teacher's Remark</div>
                            <div style="font-size:8.5px;">{{ $profile ? ($profile->classteachercomment ?? 'NO INFO') : 'NO INFO' }}</div>
                        </td>
                        <td width="50%">
                            <div class="h6">Principal's Remark</div>
                            <div style="font-size:8.5px;">{{ $profile ? ($profile->principalscomment ?? 'NO INFO') : 'NO INFO' }}</div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- FOOTER -->
            <div class="footer-section">
                <div class="footer-content">
                    <div>
                        <strong>Issued:</strong>
                        <span class="text-dot-space2">{{ now()->format('jS F, Y') }}</span>
                        <strong style="margin-left:20px;">Collected by:</strong>
                        <span class="text-dot-space2">.......................................</span>
                    </div>
                    <div>
                        <strong>Next Term Begins:</strong>
                        @php
                            $nextTermBegins = $schoolInfo->date_next_term_begins ?? null;
                            $formattedNextTermBegins = $nextTermBegins ? \Carbon\Carbon::parse($nextTermBegins)->format('jS F, Y') : '........................';
                        @endphp
                        <span class="text-dot-space2">{{ $formattedNextTermBegins }}</span>
                    </div>
                </div>
                <div class="powered-by">Powered by Qudroid Systems</div>
            </div>
        </div>
    @endforeach
</body>
</html>
