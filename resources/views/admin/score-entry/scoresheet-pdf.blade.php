{{-- resources/views/admin/score-entry/scoresheet-pdf.blade.php --}}
{{-- Rendered by DomPDF via AdminScoreEntryController::bulkExportPdf --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Scoresheet – {{ $classInfo->subject ?? 'Subject' }}</title>
<style>
/* ── Base ─────────────────────────────────────────────────────── */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9px;
    color: #1a202c;
    background: #fff;
}
.page { padding: 14px 18px 10px; }

/* ── School Header ────────────────────────────────────────────── */
.school-header {
    display: table;
    width: 100%;
    margin-bottom: 8px;
    border-bottom: 2.5px solid #1e3a5f;
    padding-bottom: 8px;
}
.school-logo-cell {
    display: table-cell;
    width: 64px;
    vertical-align: middle;
}
.school-logo-cell img {
    width: 56px;
    height: 56px;
    object-fit: contain;
}
.school-info-cell {
    display: table-cell;
    vertical-align: middle;
    padding-left: 10px;
}
.school-name {
    font-size: 14px;
    font-weight: bold;
    color: #1e3a5f;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.school-address {
    font-size: 8px;
    color: #4a5568;
    margin-top: 2px;
}
.school-contact {
    font-size: 8px;
    color: #4a5568;
    margin-top: 1px;
}
.school-motto {
    font-size: 8px;
    font-style: italic;
    color: #2563eb;
    margin-top: 2px;
}
.report-title-cell {
    display: table-cell;
    vertical-align: middle;
    text-align: right;
    width: 160px;
}
.report-type-badge {
    background: #1e3a5f;
    color: #fff;
    font-size: 9px;
    font-weight: bold;
    padding: 4px 10px;
    border-radius: 4px;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.report-subtitle {
    font-size: 8px;
    color: #64748b;
    margin-top: 4px;
    text-align: right;
}

/* ── Subject / Class Info Bar ─────────────────────────────────── */
.info-bar {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 4px;
    padding: 6px 10px;
    margin-bottom: 8px;
    display: table;
    width: 100%;
}
.info-bar-cell {
    display: table-cell;
    vertical-align: middle;
    width: 50%;
}
.info-bar-cell:last-child { text-align: right; }
.info-row { margin-bottom: 2px; }
.info-label {
    font-weight: bold;
    color: #1e3a5f;
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.info-value {
    color: #374151;
    font-size: 9px;
    font-weight: bold;
}

/* ── Stats Row ────────────────────────────────────────────────── */
.stats-row {
    display: table;
    width: 100%;
    margin-bottom: 8px;
    border-collapse: separate;
    border-spacing: 4px 0;
}
.stat-box {
    display: table-cell;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    text-align: center;
    padding: 5px 4px;
    vertical-align: middle;
}
.stat-box.blue   { border-top: 2px solid #2563eb; }
.stat-box.green  { border-top: 2px solid #16a34a; }
.stat-box.amber  { border-top: 2px solid #d97706; }
.stat-box.red    { border-top: 2px solid #dc2626; }
.stat-box.purple { border-top: 2px solid #7c3aed; }
.stat-value {
    font-size: 13px;
    font-weight: bold;
    color: #1e3a5f;
    display: block;
}
.stat-label {
    font-size: 7px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: block;
    margin-top: 1px;
}

/* ── Grade Distribution ───────────────────────────────────────── */
.grade-dist {
    display: table;
    width: 100%;
    margin-bottom: 8px;
    border-collapse: collapse;
}
.grade-pill-cell {
    display: table-cell;
    text-align: center;
    padding: 3px 2px;
}
.grade-pill {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 8px;
    font-weight: bold;
}

/* ── Section Title ────────────────────────────────────────────── */
.section-title {
    font-size: 9px;
    font-weight: bold;
    color: #1e3a5f;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 3px;
    margin-bottom: 6px;
}

/* ── Score Table ──────────────────────────────────────────────── */
table.score-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8px;
}
table.score-table thead tr {
    background: #1e3a5f;
    color: #fff;
}
table.score-table thead th {
    padding: 5px 4px;
    text-align: center;
    font-weight: 600;
    border: 1px solid #2a4a6f;
    font-size: 7.5px;
    white-space: nowrap;
}
table.score-table thead th.left { text-align: left; }
table.score-table tbody tr { border-bottom: 1px solid #e2e8f0; }
table.score-table tbody tr:nth-child(even) { background: #f8fafc; }
table.score-table tbody tr:nth-child(odd)  { background: #ffffff; }
table.score-table tbody td {
    padding: 4px 4px;
    text-align: center;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
    font-size: 8px;
}
table.score-table tbody td.left { text-align: left; }
table.score-table tbody td.name-cell {
    text-align: left;
    font-weight: 600;
    color: #1e3a5f;
    white-space: nowrap;
    max-width: 100px;
    overflow: hidden;
}
table.score-table tbody td.adm-cell {
    font-size: 7.5px;
    color: #64748b;
    white-space: nowrap;
}

/* ── Grade / Position badges in table ────────────────────────── */
.grade-chip {
    display: inline-block;
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 8px;
    font-weight: bold;
}
.g-a  { background: #dcfce7; color: #15803d; }
.g-b  { background: #dbeafe; color: #1d4ed8; }
.g-c  { background: #ede9fe; color: #6d28d9; }
.g-d  { background: #fef3c7; color: #b45309; }
.g-f  { background: #fee2e2; color: #dc2626; }
.pos-chip {
    display: inline-block;
    background: #1e3a5f;
    color: #fff;
    padding: 1px 4px;
    border-radius: 3px;
    font-size: 7.5px;
    font-weight: bold;
}

/* ── Footer ───────────────────────────────────────────────────── */
.pdf-footer {
    margin-top: 10px;
    border-top: 1px solid #e2e8f0;
    padding-top: 5px;
    display: table;
    width: 100%;
}
.footer-left  { display: table-cell; font-size: 7px; color: #94a3b8; }
.footer-right { display: table-cell; text-align: right; font-size: 7px; color: #94a3b8; }
.footer-sig {
    display: table;
    width: 100%;
    margin-top: 14px;
}
.sig-cell {
    display: table-cell;
    width: 33%;
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #94a3b8;
    font-size: 7.5px;
    color: #4a5568;
}
</style>
</head>
<body>
<div class="page">

    {{-- ══ SCHOOL HEADER ══════════════════════════════════════════════ --}}
    <div class="school-header">
        <div class="school-logo-cell">
            @if($school && $school->school_logo)
                @php
                    $logoPath = public_path('storage/' . $school->school_logo);
                    $logoUrl  = file_exists($logoPath) ? $logoPath : null;
                @endphp
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="School Logo">
                @endif
            @endif
        </div>
        <div class="school-info-cell">
            <div class="school-name">{{ $school->school_name ?? 'School Name' }}</div>
            @if($school && $school->school_address)
                <div class="school-address">{{ $school->school_address }}</div>
            @endif
            @if($school && !empty($school->school_phones))
                <div class="school-contact">
                    Tel: {{ is_array($school->school_phones) ? implode(' / ', $school->school_phones) : $school->school_phones }}
                    @if($school->school_email)
                        &nbsp;|&nbsp; Email: {{ $school->school_email }}
                    @endif
                </div>
            @endif
            @if($school && $school->school_motto)
                <div class="school-motto">"{{ $school->school_motto }}"</div>
            @endif
        </div>
        <div class="report-title-cell">
            <span class="report-type-badge">Score Report</span>
            <div class="report-subtitle">Admin Export</div>
            <div class="report-subtitle">{{ now()->format('d M Y, H:i') }}</div>
        </div>
    </div>

    {{-- ══ SUBJECT / CLASS INFO BAR ══════════════════════════════════ --}}
    @php
        $first = $broadsheets->first();
    @endphp
    <div class="info-bar">
        <div class="info-bar-cell">
            <div class="info-row">
                <span class="info-label">Subject: </span>
                <span class="info-value">{{ $first->subject ?? '-' }}
                    @if($first->subject_code ?? null) ({{ $first->subject_code }}) @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Class: </span>
                <span class="info-value">{{ ($first->schoolclass ?? '') . ' ' . ($first->arm ?? '') }}</span>
            </div>
        </div>
        <div class="info-bar-cell">
            <div class="info-row">
                <span class="info-label">Term: </span>
                <span class="info-value">{{ $first->term ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Session: </span>
                <span class="info-value">{{ $first->session ?? '-' }}</span>
            </div>
            @if(!empty($teacherName))
            <div class="info-row">
                <span class="info-label">Teacher: </span>
                <span class="info-value">{{ $teacherName }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- ══ STATS ROW ══════════════════════════════════════════════════ --}}
    @php
        $total    = $broadsheets->count();
        $passed   = $broadsheets->filter(fn($b) => ($b->total ?? 0) >= 40)->count();
        $failed   = $total - $passed;
        $avg      = $total > 0 ? round($broadsheets->avg('total'), 1) : 0;
        $highest  = $total > 0 ? round($broadsheets->max('total'), 1) : 0;
        $lowest   = $total > 0 ? round($broadsheets->min('total'), 1) : 0;
        $passRate = $total > 0 ? round($passed / $total * 100) : 0;
        $gradeDist = $broadsheets->groupBy('grade')->map->count();
        $gradeStyleMap = [
            'A' => '#16a34a','A1' => '#16a34a',
            'B' => '#2563eb','B2' => '#2563eb','B3' => '#3b82f6',
            'C' => '#7c3aed','C4' => '#7c3aed','C5' => '#8b5cf6','C6' => '#a78bfa',
            'D' => '#d97706','D7' => '#d97706','E8' => '#f59e0b',
            'F' => '#dc2626','F9' => '#dc2626',
        ];
    @endphp
    <div class="stats-row">
        <div class="stat-box blue">
            <span class="stat-value">{{ $total }}</span>
            <span class="stat-label">Students</span>
        </div>
        <div class="stat-box green">
            <span class="stat-value">{{ $passed }}</span>
            <span class="stat-label">Passed</span>
        </div>
        <div class="stat-box red">
            <span class="stat-value">{{ $failed }}</span>
            <span class="stat-label">Failed</span>
        </div>
        <div class="stat-box amber">
            <span class="stat-value">{{ $avg }}</span>
            <span class="stat-label">Avg Score</span>
        </div>
        <div class="stat-box blue">
            <span class="stat-value">{{ $highest }}</span>
            <span class="stat-label">Highest</span>
        </div>
        <div class="stat-box purple">
            <span class="stat-value">{{ $lowest }}</span>
            <span class="stat-label">Lowest</span>
        </div>
        <div class="stat-box green">
            <span class="stat-value">{{ $passRate }}%</span>
            <span class="stat-label">Pass Rate</span>
        </div>
    </div>

    {{-- ══ GRADE DISTRIBUTION ═════════════════════════════════════════ --}}
    @if($gradeDist->isNotEmpty())
    <div class="grade-dist">
        @foreach($gradeDist->sortKeys() as $grade => $cnt)
        @php
            $col = $gradeStyleMap[$grade] ?? '#6b7280';
            $pct = $total > 0 ? round($cnt / $total * 100) : 0;
        @endphp
        <div class="grade-pill-cell">
            <div class="grade-pill" style="background: {{ $col }}22; color: {{ $col }}; border: 1px solid {{ $col }}55;">
                {{ $grade }}: {{ $cnt }} ({{ $pct }}%)
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ══ SCORE TABLE ════════════════════════════════════════════════ --}}
    <div class="section-title">Student Scores</div>
    <table class="score-table">
        <thead>
            <tr>
                <th style="width: 20px;">SN</th>
                <th class="left" style="width: 70px;">Adm. No</th>
                <th class="left" style="min-width: 90px;">Student Name</th>
                @foreach($assessments as $assessment)
                    <th>{{ $assessment->name }}<br><span style="font-size:6.5px;opacity:.8;">({{ $assessment->max_score }})</span></th>
                @endforeach
                <th>Total</th>
                <th>Grade</th>
                <th>BF</th>
                <th>Cum</th>
                <th>Cum<br><span style="font-size:6.5px;opacity:.8;">Grade</span></th>
                <th>Class Pos<br><span style="font-size:6.5px;opacity:.8;">(Cum)</span></th>
                <th>Class Pos<br><span style="font-size:6.5px;opacity:.8;">(Total)</span></th>
                <th>Arm Pos<br><span style="font-size:6.5px;opacity:.8;">(Total)</span></th>
                <th>Arm Pos<br><span style="font-size:6.5px;opacity:.8;">(Cum)</span></th>
                <th>Avg</th>
            </tr>
        </thead>
        <tbody>
            @php
                // ── Ordinal helper ───────────────────────────────────────────
                // Declared with function_exists guard: safe when DomPDF renders
                // multiple PDFs in a single PHP request (bulk export).
                if (!function_exists('ordinal_pdf')) {
                    function ordinal_pdf($n) {
                        if (!$n) return '-';
                        $n = (int) $n;
                        $mod100 = $n % 100;
                        $mod10  = $n % 10;
                        if ($mod100 >= 11 && $mod100 <= 13) return $n . 'th';
                        return $n . match($mod10) {
                            1       => 'st',
                            2       => 'nd',
                            3       => 'rd',
                            default => 'th',
                        };
                    }
                }

                // ── Position fallback maps ───────────────────────────────────
                // All four position columns may be null in the DB when
                // subjectRegistrationStatus records are absent. We compute every
                // rank directly from the collection so the PDF always has values.

                // Helper: rank a collection by a score key, return [id => rank]
                $rankByKey = function($items, $keyFn) {
                    $sorted = collect($items)->sortByDesc($keyFn)->values();
                    $map    = [];
                    $rank   = 0;
                    $last   = null;
                    foreach ($sorted as $idx => $b) {
                        $score = (float) $keyFn($b);
                        if ($last === null || $score !== $last) {
                            $rank = $idx + 1;
                            $last = $score;
                        }
                        $map[$b->id] = $rank;
                    }
                    return $map;
                };

                // Class-wide rankings (all rows in this $broadsheets collection)
                $classByTotalMap = $rankByKey($broadsheets, fn($b) => $b->total ?? 0);
                $classByCumMap   = $rankByKey($broadsheets, fn($b) => $b->cum   ?? 0);

                // Arm rankings — group by arm_id, fall back to schoolclass_id
                $armGroups      = $broadsheets->groupBy(fn($b) => $b->arm_id ?? $b->schoolclass_id ?? 0);
                $armByTotalMap  = [];
                $armByCumMap    = [];
                foreach ($armGroups as $armStudents) {
                    $armByTotalMap += $rankByKey($armStudents, fn($b) => $b->total ?? 0);
                    $armByCumMap   += $rankByKey($armStudents, fn($b) => $b->cum   ?? 0);
                }

                $i = 0;
            @endphp
            @foreach($broadsheets as $broadsheet)
            @php
                $rowTotal = 0;
                foreach($assessments as $a) {
                    $so = $broadsheet->assessmentScores->where('assessment_id', $a->id)->first();
                    $rowTotal += $so ? $so->score : 0;
                }
                $grade = $broadsheet->grade ?? '-';
                $gradeClass = match(true) {
                    in_array($grade, ['A','A1'])           => 'g-a',
                    in_array($grade, ['B','B2','B3'])      => 'g-b',
                    in_array($grade, ['C','C4','C5','C6']) => 'g-c',
                    in_array($grade, ['D','D7','E8'])      => 'g-d',
                    default                                => 'g-f',
                };

                // DB value preferred; collection-computed rank as fallback
                $posClassCum   = ($broadsheet->position         ?? null) ?: ($classByCumMap[$broadsheet->id]  ?? null);
                $posClassTotal = ($broadsheet->position_total   ?? null) ?: ($classByTotalMap[$broadsheet->id] ?? null);
                $posArmTotal   = ($broadsheet->arm_position     ?? null) ?: ($armByTotalMap[$broadsheet->id]   ?? null);
                $posArmCum     = ($broadsheet->arm_position_cum ?? null) ?: ($armByCumMap[$broadsheet->id]     ?? null);

                // Cumulative grade
                $cum     = (float)($broadsheet->cum ?? 0);
                $cumGrade = match(true) {
                    $cum >= 70 => 'A',
                    $cum >= 60 => 'B',
                    $cum >= 50 => 'C',
                    $cum >= 40 => 'D',
                    default    => 'F',
                };
                $cumGradeClass = match($cumGrade) {
                    'A'     => 'g-a',
                    'B'     => 'g-b',
                    'C'     => 'g-c',
                    'D'     => 'g-d',
                    default => 'g-f',
                };
            @endphp
            <tr>
                <td>{{ ++$i }}</td>
                <td class="adm-cell">{{ $broadsheet->admissionno ?? '-' }}</td>
                <td class="name-cell">{{ ($broadsheet->lname ?? '') . ', ' . ($broadsheet->fname ?? '') }}</td>
                @foreach($assessments as $assessment)
                @php
                    $so = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                    $sv = $so ? number_format($so->score, 1) : '0.0';
                @endphp
                <td>{{ $sv }}</td>
                @endforeach
                <td style="font-weight: bold; color: #1e3a5f;">{{ number_format($broadsheet->total ?? 0, 1) }}</td>
                <td><span class="grade-chip {{ $gradeClass }}">{{ $grade }}</span></td>
                <td style="color: #64748b;">{{ number_format($broadsheet->bf ?? 0, 1) }}</td>
                <td style="font-weight: bold;">{{ number_format($cum, 1) }}</td>
                <td><span class="grade-chip {{ $cumGradeClass }}">{{ $cumGrade }}</span></td>
                {{-- Class Pos (Cum) --}}
                <td>
                    @if($posClassCum)
                        <span class="pos-chip">{{ ordinal_pdf($posClassCum) }}</span>
                    @else
                        <span style="color:#94a3b8;">-</span>
                    @endif
                </td>
                {{-- Class Pos (Total) --}}
                <td>
                    @if($posClassTotal)
                        <span class="pos-chip" style="background:#0f766e;">{{ ordinal_pdf($posClassTotal) }}</span>
                    @else
                        <span style="color:#94a3b8;">-</span>
                    @endif
                </td>
                {{-- Arm Pos (Total) --}}
                <td>
                    @if($posArmTotal)
                        <span class="pos-chip" style="background:#0891b2;">{{ ordinal_pdf($posArmTotal) }}</span>
                    @else
                        <span style="color:#94a3b8;">-</span>
                    @endif
                </td>
                {{-- Arm Pos (Cum) --}}
                <td>
                    @if($posArmCum)
                        <span class="pos-chip" style="background:#7c3aed;">{{ ordinal_pdf($posArmCum) }}</span>
                    @else
                        <span style="color:#94a3b8;">-</span>
                    @endif
                </td>
                <td style="color: #7c3aed;">{{ number_format($broadsheet->avg ?? 0, 1) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ══ SIGNATURE BLOCK ════════════════════════════════════════════ --}}
    <div class="footer-sig">
        <div class="sig-cell">Class Teacher's Signature</div>
        <div class="sig-cell">Head of Department</div>
        <div class="sig-cell">Principal's Signature</div>
    </div>

    {{-- ══ FOOTER ═════════════════════════════════════════════════════ --}}
    <div class="pdf-footer">
        <div class="footer-left">
            Generated by {{ auth()->user()->name ?? 'Admin' }} &mdash; {{ now()->format('d M Y H:i:s') }}
        </div>
        <div class="footer-right">
            {{ $school->school_name ?? '' }} &mdash; Admin Score Export
        </div>
    </div>

</div>
</body>
</html>
