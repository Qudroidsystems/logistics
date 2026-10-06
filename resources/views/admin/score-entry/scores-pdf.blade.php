{{-- resources/views/admin/score-entry/scores-pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Scores Report - Admin Generated</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 18px;
            font-size: 10px;
            background: #fff;
            color: #222;
        }

        .first-page { page-break-after: avoid; }
        .table-container { page-break-before: always; margin-top: 0; }

        .header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px solid #1a3c6e;
            margin-bottom: 12px;
        }
        .school-logo { width: 65px; height: auto; margin-bottom: 5px; }
        .school-name { font-size: 18px; font-weight: 700; color: #1a3c6e; text-transform: uppercase; letter-spacing: 1px; }
        .school-details { font-size: 9px; color: #555; margin: 2px 0; }

        .doc-title {
            font-size: 14px; font-weight: bold; text-align: center;
            color: #1a3c6e; text-transform: uppercase; letter-spacing: 2px;
            margin: 10px 0 8px; border: 2px solid #1a3c6e;
            display: inline-block; padding: 3px 18px; border-radius: 4px;
        }
        .doc-title-wrap { text-align: center; }

        .admin-banner {
            background: #fef3c7;
            border-left: 4px solid #d97706;
            padding: 5px 10px;
            margin: 8px 0;
            border-radius: 4px;
            font-size: 8.5px;
        }
        .admin-banner strong { color: #d97706; }

        .class-info {
            display: table;
            width: 100%;
            background: #f0f4fa;
            border: 1px solid #c5d3e8;
            padding: 6px 10px;
            margin: 8px 0;
            border-radius: 6px;
            font-size: 9.5px;
        }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; padding: 2px 10px 2px 0; }
        .info-label { font-weight: 700; color: #1a3c6e; }

        .stats-box {
            background: #e8f0fe;
            border: 1px solid #c5d3e8;
            border-radius: 6px;
            padding: 8px 12px;
            margin: 10px 0;
            font-size: 9.5px;
        }
        .stats-grid {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .stat-item { text-align: center; flex: 1; }
        .stat-value { font-size: 16px; font-weight: 700; color: #1a3c6e; }
        .stat-label { font-size: 8px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }

        table.scores {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 20px;
        }
        .scores th, .scores td {
            border: 1px solid #aab8cc;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
        }
        .scores thead tr { background: #1a3c6e; color: #fff; }
        .scores thead th { font-weight: 600; font-size: 8.5px; letter-spacing: 0.3px; }
        .scores tbody tr:nth-child(even) { background: #f5f8fd; }
        .scores td.name-col { text-align: left; padding-left: 6px; min-width: 120px; }
        .scores td.score-col { min-width: 35px; }

        .grade-A { background: #dcfce7 !important; }
        .grade-B { background: #dbeafe !important; }
        .grade-C { background: #fef3c7 !important; }
        .grade-D { background: #fed7aa !important; }
        .grade-F { background: #fee2e2 !important; }

        .footer {
            width: 100%;
            background: #f0f4fa;
            border: 1px solid #c5d3e8;
            padding: 6px 10px;
            margin: 8px 0;
            border-radius: 6px;
            font-size: 9px;
        }
        .footer-row { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .footer-cell {
            flex: 1;
            text-align: center;
            padding: 2px 8px;
            border-right: 1px solid #c5d3e8;
        }
        .footer-cell:last-child { border-right: none; }
        .sig-line { border-top: 1px solid #333; margin-top: 25px; padding-top: 4px; font-size: 9px; font-weight: 600; }
        .sig-title { font-weight: 700; color: #1a3c6e; margin-bottom: 4px; font-size: 9px; }
        .sig-name { font-size: 8px; color: #555; margin-top: 4px; font-style: italic; }

        @media print {
            body { font-size: 9px; }
            .first-page { page-break-after: avoid; }
            .table-container { page-break-before: always; }
        }
    </style>
</head>
<body>

{{-- FIRST PAGE: Header, School Info, Stats --}}
<div class="first-page">

    <div class="header">
        @php
            if (!isset($school)) {
                $school = App\Models\SchoolInformation::getActiveSchool();
            }
        @endphp
        @if(isset($school) && $school && $school->school_logo)
            <img src="{{ public_path('storage/' . $school->school_logo) }}" alt="Logo" class="school-logo">
        @endif
        <div class="school-name">{{ isset($school) && $school ? $school->school_name : 'School Name' }}</div>
        @if(isset($school) && $school)
            @if($school->school_address)<div class="school-details">{{ $school->school_address }}</div>@endif
            @if($school->school_phone)<div class="school-details">Tel: {{ $school->school_phone }}</div>@endif
            @if($school->school_email)<div class="school-details">Email: {{ $school->school_email }}</div>@endif
        @endif
    </div>

    <div class="admin-banner">
        <strong>📋 Admin Generated Scores Report</strong> — This document was generated by an administrator for official use.
    </div>

    <div class="doc-title-wrap">
        <span class="doc-title">STUDENT SCORES REPORT</span>
    </div>

    @php
        $firstBroadSheet = $broadsheets->first();
        $total = $broadsheets->count();
        $passed = $broadsheets->filter(fn($b) => ($b->total ?? 0) >= 40)->count();
        $avg = $total > 0 ? round($broadsheets->avg('total'), 1) : 0;
        $highest = $total > 0 ? round($broadsheets->max('total'), 1) : 0;
        $lowest = $total > 0 ? round($broadsheets->min('total'), 1) : 0;
    @endphp

    @if($firstBroadSheet)
    <div class="class-info">
        <div class="info-row">
            <div class="info-cell"><span class="info-label">Subject:</span> {{ $firstBroadSheet->subject ?? '' }} ({{ $firstBroadSheet->subject_code ?? '' }})</div>
            <div class="info-cell"><span class="info-label">Class:</span> {{ $firstBroadSheet->schoolclass ?? '' }} {{ $firstBroadSheet->arm ?? '' }}</div>
            <div class="info-cell"><span class="info-label">Teacher:</span> {{ $teacherName ?? 'N/A' }} <span style="color:#d97706;">(Admin Entry)</span></div>
            <div class="info-cell"><span class="info-label">Term:</span> {{ $firstBroadSheet->term ?? 'First Term' }}</div>
            <div class="info-cell"><span class="info-label">Session:</span> {{ $firstBroadSheet->session ?? '2025/2026' }}</div>
            <div class="info-cell"><span class="info-label">Date:</span> {{ date('d M Y') }}</div>
        </div>
    </div>

    <div class="stats-box">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value">{{ $total }}</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #16a34a;">{{ $passed }}</div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #dc2626;">{{ $total - $passed }}</div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #d97706;">{{ $avg }}</div>
                <div class="stat-label">Class Average</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #2563eb;">{{ $highest }}</div>
                <div class="stat-label">Highest Score</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #7c3aed;">{{ $lowest }}</div>
                <div class="stat-label">Lowest Score</div>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- SECOND PAGE AND BEYOND: Students Table --}}
<div class="table-container">

    <table class="scores">
        <thead>
            <tr>
                <th style="width:25px;">S/N</th>
                <th style="min-width:80px;">Adm. No</th>
                <th style="min-width:130px;">Student Name</th>
                @foreach($assessments as $assessment)
                    <th class="score-col">
                        {{ $assessment->name }}<br>
                        <small style="font-weight:normal;">({{ number_format($assessment->max_score, 1) }})</small>
                    </th>
                @endforeach
                <th class="score-col" style="background:#163275;">Total</th>
                <th class="score-col">Grade</th>
                <th class="score-col">Cum</th>
                <th class="score-col">Position</th>
                <th class="score-col">Remark</th>
            </tr>
        </thead>
        <tbody>
            @forelse($broadsheets as $index => $student)
                @php
                    $gradeClass = match($student->grade) {
                        'A', 'A1' => 'grade-A',
                        'B', 'B2', 'B3' => 'grade-B',
                        'C', 'C4', 'C5', 'C6' => 'grade-C',
                        'D', 'D7', 'E8' => 'grade-D',
                        default => 'grade-F'
                    };
                @endphp
                <tr class="{{ $gradeClass }}">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->admissionno ?? '-' }}</td>
                    <td class="name-col">
                        <strong>{{ $student->lname ?? '' }}</strong>
                        {{ $student->fname ?? '' }}
                        {{ $student->mname ?? '' }}
                    </td>
                    @foreach($assessments as $assessment)
                        @php
                            $score = $student->assessmentScores->where('assessment_id', $assessment->id)->first();
                            $scoreValue = $score ? $score->score : 0;
                        @endphp
                        <td class="score-col">{{ number_format($scoreValue, 1) }}</td>
                    @endforeach
                    <td class="score-col"><strong>{{ number_format($student->total ?? 0, 1) }}</strong></td>
                    <td class="score-col"><strong>{{ $student->grade ?? '-' }}</strong></td>
                    <td class="score-col">{{ number_format($student->cum ?? 0, 1) }}</td>
                    <td class="score-col">{{ $student->position ?? '-' }}</td>
                    <td class="score-col">{{ $student->remark ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 4 + $assessments->count() + 4 }}" style="text-align:center;padding:12px;">
                        No students found.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($broadsheets->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;font-style:italic;">
                    Total: {{ $broadsheets->count() }} students
                </td>
                @foreach($assessments as $assessment)
                    <td class="score-col"></td>
                @endforeach
                <td class="score-col"><strong>Average: {{ $avg }}</strong></td>
                <td class="score-col"></td>
                <td class="score-col"></td>
                <td class="score-col"></td>
                <td class="score-col"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <div class="footer-row">
            <div class="footer-cell">
                <div class="sig-title">Subject Teacher</div>
                <div class="sig-line"></div>
                <div class="sig-name">Name: _________________</div>
                <div class="sig-name" style="color:#d97706;font-size:7px;">(Admin: {{ $teacherName ?? 'Admin User' }})</div>
            </div>
            <div class="footer-cell">
                <div class="sig-title">H.O.D</div>
                <div class="sig-line"></div>
                <div class="sig-name">Name: _________________</div>
            </div>
            <div class="footer-cell">
                <div class="sig-title">Principal</div>
                <div class="sig-line"></div>
                <div class="sig-name">Name: _________________</div>
            </div>
            <div class="footer-cell">
                <div class="sig-title">Date</div>
                <div class="sig-line"></div>
                <div class="sig-name">____/____/________</div>
            </div>
        </div>
    </div>

</div>

</body>
</html>
