@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Student Transcript</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('transcript.index') }}">Transcript</a></li>
                                <li class="breadcrumb-item active">Preview</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Toolbar --}}
            <div class="card shadow-sm mb-3 d-print-none">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <a href="{{ route('transcript.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i>Back
                            </a>
                            <span class="badge bg-primary-subtle text-primary px-3 py-2" style="font-size:12px;">
                                {{ ucfirst($type) }} Transcript
                            </span>
                            <span class="badge bg-success-subtle text-success px-3 py-2" style="font-size:12px;">
                                {{ $totalSessions }} Session(s) · {{ $totalSubjects }} Subjects
                            </span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                                <i class="ri-printer-line me-1"></i>Print
                            </button>
                            <form method="POST" action="{{ route('transcript.pdf') }}" target="_blank" class="d-inline">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                <input type="hidden" name="type"       value="{{ $type }}">
                                <input type="hidden" name="session_id" value="{{ $sessionId ?? '' }}">
                                <input type="hidden" name="term_id"    value="{{ $termId ?? '' }}">
                                <input type="hidden" name="copy_type"  value="original">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="ri-file-pdf-line me-1"></i>Download PDF (Original)
                                </button>
                            </form>
                            <form method="POST" action="{{ route('transcript.pdf') }}" target="_blank" class="d-inline">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                <input type="hidden" name="type"       value="{{ $type }}">
                                <input type="hidden" name="session_id" value="{{ $sessionId ?? '' }}">
                                <input type="hidden" name="term_id"    value="{{ $termId ?? '' }}">
                                <input type="hidden" name="copy_type"  value="duplicate">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="ri-file-copy-line me-1"></i>Download (Duplicate)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transcript Document --}}
            <div class="card shadow-sm" id="transcriptDoc"
                 style="max-width:900px;margin:0 auto;position:relative;overflow:hidden;">

                {{-- Watermark --}}
                <div style="position:absolute;inset:0;pointer-events:none;z-index:0;overflow:hidden;">
                    @if(!empty($school_logo_base64))
                        <img src="{{ $school_logo_base64 }}"
                             style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);
                                    width:380px;height:380px;object-fit:contain;opacity:0.04;">
                    @endif
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-35deg);
                                font-size:58px;font-weight:900;color:rgba(30,58,95,0.06);white-space:nowrap;
                                letter-spacing:4px;text-transform:uppercase;font-family:Georgia,serif;">
                        ORIGINAL COPY
                    </div>
                </div>

                <div class="card-body p-4" style="position:relative;z-index:1;">

                    {{-- School Header --}}
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3"
                         style="border-bottom:3px solid #1e3a5f;">
                        @if(!empty($school_logo_base64))
                            <img src="{{ $school_logo_base64 }}" alt="Logo"
                                 style="width:75px;height:75px;object-fit:contain;border-radius:50%;
                                        border:3px solid #1e3a5f;flex-shrink:0;">
                        @endif
                        <div class="flex-grow-1 text-center">
                            <h3 class="fw-bold text-uppercase mb-1" style="color:#1e3a5f;letter-spacing:1px;font-size:1.4rem;">
                                {{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}
                            </h3>
                            @if(!empty($schoolInfo->school_address))
                                <p class="text-muted mb-1" style="font-size:13px;">{{ $schoolInfo->school_address }}</p>
                            @endif
                            @if(!empty($schoolInfo->school_phone) || !empty($schoolInfo->school_email))
                                <p class="text-muted mb-1" style="font-size:12px;">
                                    @if(!empty($schoolInfo->school_phone)) Tel: {{ $schoolInfo->school_phone }} @endif
                                    @if(!empty($schoolInfo->school_email)) &nbsp;|&nbsp; {{ $schoolInfo->school_email }} @endif
                                </p>
                            @endif
                            @if(!empty($schoolInfo->school_motto))
                                <p class="fst-italic mb-0" style="font-size:12px;color:#2563eb;">
                                    "{{ $schoolInfo->school_motto }}"
                                </p>
                            @endif
                        </div>
                        <div style="width:75px;flex-shrink:0;text-align:center;">
                            <span style="display:inline-block;border:2px solid #1e3a5f;color:#1e3a5f;
                                         padding:3px 8px;font-size:10px;font-weight:700;
                                         border-radius:4px;letter-spacing:1px;">ORIGINAL</span>
                        </div>
                    </div>

                    {{-- Document Title --}}
                    <div class="text-center mb-4">
                        <div style="background:#1e3a5f;color:white;padding:10px 20px;border-radius:6px;
                                    display:inline-block;font-size:15px;font-weight:700;letter-spacing:2px;
                                    text-transform:uppercase;">
                            ACADEMIC TRANSCRIPT
                        </div>
                        <div class="text-muted mt-2" style="font-size:12px;">
                            {{ ucfirst($type) }} Record &nbsp;·&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
                            &nbsp;·&nbsp; By: {{ auth()->user()->name ?? 'System' }}
                        </div>
                    </div>

                    {{-- Student Info Box --}}
                    <div class="mb-4" style="border:2px solid #1e3a5f;border-radius:10px;overflow:hidden;">
                        <div class="d-flex">
                            <div style="background:#1e3a5f;padding:16px;display:flex;align-items:center;flex-shrink:0;">
                                <img src="{{ $student->picture ? asset('storage/student_avatars/'.basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                     style="width:90px;height:90px;object-fit:cover;border-radius:50%;border:3px solid white;"
                                     onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'">
                            </div>
                            <div style="padding:14px 20px;background:#f8fafc;flex:1;">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Full Name</div>
                                        <div class="fw-bold" style="font-size:16px;color:#1e3a5f;">
                                            {{ strtoupper($student->lastname) }}, {{ $student->firstname }} {{ $student->othername ?? '' }}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Admission No.</div>
                                        <div class="fw-bold" style="color:#2563eb;">{{ $student->admissionno }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Gender</div>
                                        <div class="fw-bold">{{ $student->gender ?? '—' }}</div>
                                    </div>
                                    @if($student->dateofbirth)
                                    <div class="col-md-3">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Date of Birth</div>
                                        <div>{{ \Carbon\Carbon::parse($student->dateofbirth)->format('d M Y') }}</div>
                                    </div>
                                    @endif
                                    <div class="col-md-3">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Sessions on Record</div>
                                        <div class="fw-bold text-success">{{ $totalSessions }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Total Subjects</div>
                                        <div class="fw-bold">{{ $totalSubjects }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Overall GPA</div>
                                        <div class="fw-bold" style="color:#1e3a5f;font-size:15px;">
                                            {{ number_format($overallGpa, 2) }}
                                            <span class="badge bg-primary ms-1" style="font-size:10px;">{{ $overallGpaGrade }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Grade Key --}}
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-4 p-2 rounded-3"
                         style="background:#f8fafc;border:1px solid #e2e8f0;font-size:11px;">
                        <strong style="color:#1e3a5f;font-size:11px;">GRADING:</strong>
                        @php
                        $gradeKey = [
                            'A1'=>['75-100','#16a34a'],'B2'=>['70-74','#1d4ed8'],'B3'=>['65-69','#2563eb'],
                            'C4'=>['60-64','#d97706'], 'C5'=>['55-59','#b45309'],'C6'=>['50-54','#92400e'],
                            'D7'=>['45-49','#ea580c'], 'E8'=>['40-44','#c2410c'],'F9'=>['0-39', '#dc2626'],
                        ];
                        @endphp
                        @foreach($gradeKey as $grade => $info)
                            <span class="badge" style="background:{{ $info[1] }};font-size:10px;">
                                {{ $grade }} ({{ $info[0] }})
                            </span>
                        @endforeach
                        <span class="text-muted ms-2" style="font-size:11px;">
                            <strong>BF</strong>=Brought Forward &nbsp;
                            <strong>CUM</strong>=Cumulative &nbsp;
                            <strong>POS</strong>=Position
                        </span>
                    </div>

                    {{-- Transcript Data --}}
                    @php
                    $gradeColors = [
                        'A1'=>'#dcfce7','B2'=>'#dbeafe','B3'=>'#e0eeff',
                        'C4'=>'#fef9c3','C5'=>'#fef3c7','C6'=>'#fde68a',
                        'D7'=>'#ffedd5','E8'=>'#fed7aa','F9'=>'#fee2e2',
                    ];
                    $gradeTextColors = [
                        'A1'=>'#166534','B2'=>'#1e40af','B3'=>'#1e40af',
                        'C4'=>'#854d0e','C5'=>'#92400e','C6'=>'#78350f',
                        'D7'=>'#9a3412','E8'=>'#9a3412','F9'=>'#991b1b',
                    ];
                    @endphp

                    @forelse($transcriptData as $sessionName => $sessionData)
                        <div class="mb-4">

                            {{-- Session Header --}}
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div style="background:#1e3a5f;color:white;padding:5px 16px;
                                            border-radius:20px;font-size:13px;font-weight:700;
                                            white-space:nowrap;">
                                    {{ $sessionName }}
                                </div>
                                <div style="flex:1;height:2px;background:linear-gradient(90deg,#1e3a5f22,transparent);"></div>
                            </div>

                            @foreach($sessionData['terms'] as $termName => $termData)
                                @php $assessments = $termData['assessments'] ?? $assessments; @endphp
                                <div class="mb-3 ms-2" style="border-left:3px solid #bfdbfe;padding-left:16px;">

                                    {{-- Term Header --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                        <div>
                                            <span class="fw-bold" style="font-size:14px;color:#1e3a5f;">{{ $termName }}</span>
                                            <span class="text-muted ms-2" style="font-size:12px;">— {{ $termData['class'] }}</span>
                                        </div>
                                        {{-- <div class="d-flex gap-2 flex-wrap">
                                            @if($termData['class_position'])
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-trophy-line me-1"></i>Position: {{ $termData['class_position'] }}
                                                </span>
                                            @endif
                                            @if($termData['promotion'])
                                                @php $isPromoted = strtolower($termData['promotion']) === 'promoted'; @endphp
                                                <span class="badge {{ $isPromoted ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                    <i class="ri-{{ $isPromoted ? 'arrow-up' : 'arrow-down' }}-line me-1"></i>
                                                    {{ $termData['promotion'] }}
                                                </span>
                                            @endif
                                        </div> --}}
                                    </div>

                                    {{-- Subject Table --}}
                                    @if(!empty($termData['subjects']))
                                    <div class="table-responsive mb-2">
                                        <table class="table table-sm table-bordered mb-0"
                                               style="font-size:12px;border-color:#dee2e6;">
                                            <thead>
                                                {{-- Row 1: Group headers --}}
                                                <tr>
                                                    <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}"
                                                        style="background:#1e3a5f;color:white;min-width:160px;vertical-align:middle;">
                                                        Subject
                                                    </th>
                                                    @if($assessments->count() > 0)
                                                        <th colspan="{{ $assessments->count() }}"
                                                            style="background:#163562;color:#a8d4ef;text-align:center;
                                                                   font-size:11px;border-left:2px solid #2563eb;">
                                                            Assessments
                                                        </th>
                                                    @endif
                                                    <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}"
                                                        style="background:#1e3a5f;color:white;text-align:center;vertical-align:middle;">
                                                        Total
                                                    </th>
                                                    <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}"
                                                        style="background:#1e3a5f;color:white;text-align:center;vertical-align:middle;">
                                                        BF
                                                    </th>
                                                    <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}"
                                                        style="background:#1e3a5f;color:white;text-align:center;vertical-align:middle;">
                                                        Cum
                                                    </th>
                                                    <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}"
                                                        style="background:#1e3a5f;color:white;text-align:center;vertical-align:middle;">
                                                        Grade
                                                    </th>
                                                    <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}"
                                                        style="background:#1e3a5f;color:white;text-align:center;vertical-align:middle;">
                                                        Position
                                                    </th>
                                                    <th rowspan="{{ $assessments->count() > 0 ? 2 : 1 }}"
                                                        style="background:#1e3a5f;color:white;text-align:center;vertical-align:middle;">
                                                        Class Avg
                                                    </th>
                                                </tr>
                                                {{-- Row 2: Individual assessment names --}}
                                                @if($assessments->count() > 0)
                                                <tr>
                                                    @foreach($assessments as $aIdx => $a)
                                                        <th style="background:#1a3d6a;color:#a8d4ef;text-align:center;
                                                                   font-size:11px;white-space:nowrap;
                                                                   {{ $aIdx === 0 ? 'border-left:2px solid #2563eb;' : '' }}
                                                                   min-width:45px;">
                                                            {{ $a->name }}<br>
                                                            <span style="font-size:10px;opacity:.75;">/{{ $a->max_score }}</span>
                                                        </th>
                                                    @endforeach
                                                </tr>
                                                @endif
                                            </thead>
                                            <tbody>
                                                @foreach($termData['subjects'] as $sub)
                                                @php
                                                    $g   = $sub['grade'] ?? '-';
                                                    $bg  = $gradeColors[$g]     ?? '';
                                                    $fg  = $gradeTextColors[$g] ?? '';
                                                @endphp
                                                <tr>
                                                    <td class="fw-medium">
                                                        {{ $sub['subject'] }}
                                                        @if(!empty($sub['subject_code']))
                                                            <small class="text-muted">({{ $sub['subject_code'] }})</small>
                                                        @endif
                                                    </td>
                                                    {{-- Assessment scores --}}
                                                    @foreach($assessments as $aIdx => $a)
                                                        @php $score = $sub['assessments'][$a->id] ?? null; @endphp
                                                        <td style="text-align:center;
                                                                   {{ $aIdx === 0 ? 'border-left:2px solid #bfdbfe;' : '' }}">
                                                            {{ $score !== null && $score > 0 ? number_format($score, 1) : '—' }}
                                                        </td>
                                                    @endforeach
                                                    <td style="text-align:center;">
                                                        {{ $sub['total'] > 0 ? number_format($sub['total'],1) : '—' }}
                                                    </td>
                                                    <td style="text-align:center;color:#9ca3af;">
                                                        {{ $sub['bf'] > 0 ? number_format($sub['bf'],1) : '—' }}
                                                    </td>
                                                    <td style="text-align:center;font-weight:bold;
                                                               background:{{ $bg }};color:{{ $fg }};">
                                                        {{ $sub['cum'] > 0 ? number_format($sub['cum'],1) : '—' }}
                                                    </td>
                                                    <td style="text-align:center;font-weight:bold;
                                                               background:{{ $bg }};color:{{ $fg }};">
                                                        {{ $g }}
                                                    </td>
                                                    <td style="text-align:center;font-size:11px;">
                                                        {{ $sub['position'] !== '-' ? $sub['position'] : '—' }}
                                                    </td>
                                                    <td style="text-align:center;color:#6b7280;">
                                                        {{ $sub['class_average'] > 0 ? number_format($sub['class_average'],1) : '—' }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr style="background:#f0f4fa;">
                                                    <td class="fw-bold"
                                                        colspan="{{ 1 + $assessments->count() }}">
                                                        SUMMARY — {{ $termData['subject_count'] }} subjects
                                                    </td>
                                                    <td colspan="2" style="text-align:center;font-weight:bold;color:#2563eb;">
                                                        Avg: {{ $termData['average'] }}
                                                    </td>
                                                    <td colspan="2" style="text-align:center;font-weight:bold;color:#16a34a;">
                                                        Cum Avg: {{ $termData['cum_average'] }}
                                                    </td>
                                                    <td colspan="2"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    @endif

                                    {{-- Principal's Comment --}}
                                    @if($termData['comment'])
                                    <div class="p-2 rounded-3 mb-1"
                                         style="background:#fef9c3;border-left:3px solid #d97706;font-size:12px;">
                                        <strong style="font-size:10px;text-transform:uppercase;color:#92400e;">
                                            Principal's Comment:
                                        </strong>
                                        <p class="mb-0 mt-1" style="color:#1a1a2e;">{{ $termData['comment'] }}</p>
                                    </div>
                                    @endif

                                </div>{{-- end term block --}}
                            @endforeach
                        </div>{{-- end session block --}}
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="ri-inbox-line ri-3x d-block mb-3"></i>
                            <p>No academic records found for this student.</p>
                        </div>
                    @endforelse

                    {{-- Overall Grade Distribution --}}
                    @if(!empty($gradeDistribution) && $gradeDistribution->count() > 0)
                    <div class="mt-4 pt-3" style="border-top:2px solid #e2e8f0;">
                        <h6 class="fw-bold mb-3" style="color:#1e3a5f;font-size:13px;">
                            <i class="ri-bar-chart-2-line me-2"></i>Overall Grade Distribution
                        </h6>
                        <div class="d-flex gap-2 flex-wrap">
                            @php
                            $gdColors = [
                                'A1'=>'#16a34a','B2'=>'#1d4ed8','B3'=>'#2563eb',
                                'C4'=>'#d97706','C5'=>'#b45309','C6'=>'#92400e',
                                'D7'=>'#ea580c','E8'=>'#c2410c','F9'=>'#dc2626',
                            ];
                            @endphp
                            @foreach($gradeDistribution as $grade => $count)
                            <div class="text-center px-3 py-2 rounded-3"
                                 style="background:{{ ($gdColors[$grade] ?? '#6b7280') }}18;
                                        border:1px solid {{ ($gdColors[$grade] ?? '#6b7280') }}40;
                                        min-width:56px;">
                                <div class="fw-bold" style="font-size:18px;color:{{ $gdColors[$grade] ?? '#6b7280' }};">
                                    {{ $grade }}
                                </div>
                                <div style="font-size:11px;color:#6b7280;">{{ $count }}×</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Signatures --}}
                    <div class="row mt-5 pt-3" style="border-top:1px solid #e2e8f0;">
                        <div class="col-4 text-center">
                            <div style="border-top:1px solid #374151;padding-top:6px;margin-top:40px;
                                        font-size:12px;color:#374151;">
                                Class Teacher
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div style="border-top:1px solid #374151;padding-top:6px;margin-top:40px;
                                        font-size:12px;color:#374151;">
                                Date
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div style="border-top:1px solid #374151;padding-top:6px;margin-top:40px;
                                        font-size:12px;color:#374151;">
                                Principal
                            </div>
                        </div>
                    </div>

                    {{-- Document Footer --}}
                    <div class="text-center mt-3 text-muted"
                         style="font-size:10px;border-top:1px dashed #e2e8f0;padding-top:8px;">
                        This transcript was generated on {{ now()->format('d M Y, H:i') }}
                        by {{ auth()->user()->name ?? 'System' }}
                        &nbsp;·&nbsp; {{ $schoolInfo->school_name ?? '' }}
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
