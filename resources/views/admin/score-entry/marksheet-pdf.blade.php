{{-- resources/views/admin/score-entry/marksheet-pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Marks Sheet - Admin Generated</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 18px;
            font-size: 11px;
            background: #fff;
            color: #222;
        }

        .first-page { page-break-after: avoid; }
        .table-container { page-break-before: always; margin-top: 0; }

        .header {
            text-align: center;
            padding-bottom: 14px;
            border-bottom: 2px solid #1a3c6e;
            margin-bottom: 14px;
        }
        .school-logo { width: 70px; height: auto; margin-bottom: 6px; }
        .school-name { font-size: 20px; font-weight: 700; color: #1a3c6e; text-transform: uppercase; letter-spacing: 1px; }
        .school-details { font-size: 10px; color: #555; margin: 2px 0; }

        .doc-title {
            font-size: 15px; font-weight: bold; text-align: center;
            color: #1a3c6e; text-transform: uppercase; letter-spacing: 2px;
            margin: 12px 0 10px; border: 2px solid #1a3c6e;
            display: inline-block; padding: 4px 20px; border-radius: 4px;
        }
        .doc-title-wrap { text-align: center; }

        .admin-banner {
            background: #fef3c7;
            border-left: 4px solid #d97706;
            padding: 6px 12px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 9px;
        }
        .admin-banner strong { color: #d97706; }

        .class-info {
            display: table;
            width: 100%;
            background: #f0f4fa;
            border: 1px solid #c5d3e8;
            padding: 8px 12px;
            margin: 10px 0;
            border-radius: 6px;
            font-size: 10.5px;
        }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; padding: 2px 12px 2px 0; }
        .info-label { font-weight: 700; color: #1a3c6e; }

        .instructions {
            background: #fffbe6;
            border-left: 4px solid #f59e0b;
            padding: 8px 12px;
            margin-bottom: 14px;
            border-radius: 0 4px 4px 0;
            font-size: 10px;
        }
        .instructions strong { color: #b45309; display: block; margin-bottom: 4px; font-size: 11px; }
        .instructions ul { margin-left: 20px; }
        .instructions li { margin-bottom: 2px; }

        table.marks {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 30px;
        }
        .marks th, .marks td {
            border: 1px solid #aab8cc;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
        }
        .marks thead tr { background: #1a3c6e; color: #fff; }
        .marks thead th { font-weight: 600; font-size: 10px; letter-spacing: 0.3px; }
        .marks tbody tr:nth-child(even) { background: #f5f8fd; }
        .marks td.name-col { text-align: left; padding-left: 7px; min-width: 140px; }
        .marks td.score-col { min-width: 45px; }
        .marks tfoot td { background: #e8f0fe; font-weight: 700; font-size: 10px; }

        .footer {
            width: 100%;
            background: #f0f4fa;
            border: 1px solid #c5d3e8;
            padding: 8px 12px;
            margin: 10px 0;
            border-radius: 6px;
            font-size: 10.5px;
        }
        .footer-row { display: table; width: 100%; }
        .footer-cell {
            display: table-cell;
            text-align: center;
            padding: 2px 12px;
            border-right: 1px solid #c5d3e8;
        }
        .footer-cell:last-child { border-right: none; }
        .sig-line { border-top: 1px solid #333; margin-top: 30px; padding-top: 6px; font-size: 10px; font-weight: 600; }
        .sig-title { font-weight: 700; color: #1a3c6e; margin-bottom: 5px; }
        .sig-name { font-size: 9px; color: #555; margin-top: 5px; font-style: italic; }

        @media print {
            body { font-size: 10px; }
            .first-page { page-break-after: avoid; }
            .table-container { page-break-before: always; }
        }
    </style>
</head>
<body>

{{-- FIRST PAGE: Header, School Info, and Instructions --}}
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
            @if($school->school_motto)<div class="school-details"><em>"{{ $school->school_motto }}"</em></div>@endif
        @endif
    </div>

    <div class="admin-banner">
        <strong>📋 Admin Generated Marks Sheet</strong> — This document was generated by an administrator for official use.
    </div>

    <div class="doc-title-wrap">
        <span class="doc-title">STUDENT MARKS SHEET</span>
    </div>

    @php
        $firstBroadSheet = $broadsheets->first();
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

    <div class="instructions">
        <strong>📋 Instructions for Teachers:</strong>
        <ul>
            <li>Fill in all scores clearly. Use only blue or black ink.</li>
            <li>Assessment columns and maximum scores are listed in the table header.</li>
            @if($assessments->isNotEmpty())
                <li>
                    Max scores:
                    @foreach($assessments as $a)
                        <strong>{{ $a->name }}</strong> ({{ number_format($a->max_score, 2) }}){{ !$loop->last ? ', ' : '' }}
                    @endforeach
                    &mdash; <strong>Total</strong> ({{ number_format($assessments->sum('max_score'), 2) }})
                </li>
            @endif
            <li>Sign and submit to the Academic Office after completion.</li>
            <li><strong style="color:#d97706;">Note:</strong> This marks sheet was generated by an administrator. Please verify all entries.</li>
        </ul>
    </div>
    @endif

</div>

{{-- SECOND PAGE AND BEYOND: Students Table and Footer --}}
<div class="table-container">

    <table class="marks">
        <thead>
            <tr>
                <th style="width:30px;">S/N</th>
                <th style="min-width:90px;">Adm. No</th>
                <th style="min-width:150px;">Student Name</th>
                @foreach($assessments as $assessment)
                    <th class="score-col">
                        {{ $assessment->name }}<br>
                        <small style="font-weight:normal;font-size:9px;">({{ number_format($assessment->max_score, 2) }})</small>
                    </th>
                @endforeach
                <th class="score-col" style="background:#163275;">
                    Total<br>
                    <small style="font-weight:normal;font-size:9px;">({{ number_format($assessments->sum('max_score'), 2) }})</small>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($broadsheets as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->admissionno ?? '-' }}</td>
                    <td class="name-col">
                        <strong>{{ $student->lname ?? '' }}</strong>
                        {{ $student->fname ?? '' }}
                        {{ $student->mname ?? '' }}
                    </td>
                    @foreach($assessments as $assessment)
                        <td class="score-col"></td>
                    @endforeach
                    <td class="score-col"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + $assessments->count() + 1 }}" style="text-align:center;padding:12px;">
                        No students found.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($broadsheets->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;font-style:italic;">
                    Total Students: {{ $broadsheets->count() }}
                </td>
                @foreach($assessments as $assessment)
                    <td></td>
                @endforeach
                <td></td>
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
                <div class="sig-name" style="color:#d97706;font-size:8px;">(Admin: {{ $teacherName ?? 'Admin User' }})</div>
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
