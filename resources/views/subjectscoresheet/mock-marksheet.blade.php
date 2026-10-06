<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Mock Marks Sheet</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            background: #fff;
            color: #333;
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }

        .school-logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        .school-name {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            text-transform: uppercase;
        }

        .school-details {
            font-size: 11px;
            color: #555;
            margin: 2px 0;
        }

        .title {
            font-size: 18px;
            margin: 25px 0 10px;
            font-weight: bold;
            text-align: center;
            color: #222;
            text-transform: uppercase;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .class-info {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            background: #f1f5f9;
            border: 1px solid #ccc;
            padding: 10px;
            margin: 20px 0;
            border-radius: 8px;
            font-size: 11px;
        }

        .info-section {
            flex: 1 1 30%;
            margin: 5px 0;
        }

        .info-label {
            font-weight: 600;
            color: #000;
        }

        .instructions {
            background: #fff8e1;
            border: 1px solid #ffe082;
            padding: 10px;
            margin-bottom: 25px;
            border-radius: 6px;
        }

        .instructions h4 {
            font-size: 13px;
            margin-bottom: 6px;
            color: #7c6000;
        }

        .instructions ul {
            padding-left: 16px;
            margin: 0;
            font-size: 11px;
        }

        .instructions li {
            margin-bottom: 4px;
        }

        table.marks-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px;
            font-size: 11px;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
        }

        .marks-table th,
        .marks-table td {
            border: 1px solid #ccc;
            padding: 6px 4px;
            text-align: center;
        }

        .marks-table th {
            background: #e6f0fa;
            color: #333;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 10px;
        }

        .marks-table th:first-child {
            border-top-left-radius: 8px;
        }

        .marks-table th:last-child {
            border-top-right-radius: 8px;
        }

        .marks-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .student-name {
            text-align: left;
            padding-left: 6px;
        }

        .footer {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            font-size: 11px;
        }

        .signature-section {
            text-align: center;
            width: 160px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }

        .page-break {
            page-break-before: always;
        }

        @media print {
            .page-break {
                page-break-before: always;
            }

            body {
                font-size: 10px;
            }

            .marks-table th,
            .marks-table td {
                padding: 4px 2px;
            }
        }
    </style>
</head>
<body>

<div class="header">
    @if($school && $school->school_logo)
        <img src="{{ $school->logo_url }}" alt="School Logo" class="school-logo">
    @endif
    <div class="school-name">{{ $school->school_name ?? 'TOPCLASS COLLEGE' }}</div>
    @if($school)
        @if($school->school_address)<div class="school-details">{{ $school->school_address }}</div>@endif
        @if($school->school_phone)<div class="school-details">Tel: {{ $school->school_phone }}</div>@endif
        @if($school->school_email)<div class="school-details">Email: {{ $school->school_email }}</div>@endif
        @if($school->school_motto)<div class="school-details"><em>"{{ $school->school_motto }}"</em></div>@endif
    @endif
</div>

<div class="title">Student Mock Marks Sheet</div>

@if($classInfo)
    <div class="class-info">
        <div class="info-section"><span class="info-label">Subject:</span> {{ $classInfo->subject }} ({{ $classInfo->subject_code }})</div>
        <div class="info-section"><span class="info-label">Class:</span> {{ $classInfo->schoolclass }} {{ $classInfo->arm }}</div>
        <div class="info-section"><span class="info-label">Teacher:</span> {{ $classInfo->teacher_name }}</div>
        <div class="info-section"><span class="info-label">Term:</span> {{ $classInfo->term }}</div>
        <div class="info-section"><span class="info-label">Session:</span> {{ $classInfo->session }}</div>
        <div class="info-section"><span class="info-label">Date:</span> {{ date('d/m/Y') }}</div>
    </div>

    <div class="instructions">
        <h4>Instructions for Teachers:</h4>
        <ul>
            <li>Fill in all scores clearly and legibly.</li>
            <li>CA1 Max: {{ $classInfo->max_ca1 ?? 'N/A' }}, CA2 Max: {{ $classInfo->max_ca2 ?? 'N/A' }}, CA3 Max: {{ $classInfo->max_ca3 ?? 'N/A' }}, Exam Max: {{ $classInfo->max_exam ?? 'N/A' }}.</li>
            <li>Use only blue or black ink.</li>
            <li>Sign and submit to the Academic Office after completion.</li>
        </ul>
    </div>
@endif

<div class="page-break"></div>

<table class="marks-table">
    <thead>
        <tr>
            <th>S/N</th>
            <th>Admission No.</th>
            <th>Student Name</th>
            <th>Exam<br><small>({{ $classInfo->max_exam ?? 'N/A' }})</small></th>
        </tr>
    </thead>
    <tbody>
        @forelse($broadsheets as $index => $broadsheet)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $broadsheet->admissionno }}</td>
                <td class="student-name">
                    @if($broadsheet->lname || $broadsheet->fname || $broadsheet->mname)
                        <span class="fw-bold">{{ $broadsheet->lname ?? '' }}</span> {{ $broadsheet->fname ?? '' }} {{ $broadsheet->mname ?? '' }}
                    @else
                        -
                    @endif
                </td>
                <td></td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No students found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <div class="signature-section"><div class="signature-line">Subject Teacher</div></div>
    <div class="signature-section"><div class="signature-line">HOD</div></div>
    <div class="signature-section"><div class="signature-line">Date</div></div>
</div>

</body>
</html>