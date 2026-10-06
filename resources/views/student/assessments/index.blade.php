{{-- resources/views/student/assessments/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <style>
                /* Mapped onto the shared CB UI tokens (public/css/cb-ui.css) so this
                   page matches the rest of the portal, incl. dark mode. */
                .assessment-portal {
                    --navy:     var(--cb-navy);
                    --navy-mid: var(--cb-navy-2);
                    --gold:     var(--cb-teal);
                    --cream:    var(--cb-page);
                    --paper:    var(--cb-surface);
                    --border:   var(--cb-border);
                    --radius:   var(--cb-radius);
                    --radius-sm:var(--cb-radius-sm);
                }

                .assessment-portal { font-family:var(--cb-font); background:transparent; }
                .ap-body { padding:0 !important; }
                .ap-attendance-card, .ap-trend-card, .mock-section-card { box-shadow: var(--cb-shadow); }

                /* Hero */
                .ap-hero { background:var(--navy); padding:36px 32px 28px; position:relative; }
                .ap-hero-title { font-size:28px; font-weight:700; color:#fff; margin:0; }
                .ap-hero-sub { color:rgba(255,255,255,.55); font-size:13.5px; margin-top:5px; }

                /* Filter bar */
                .ap-filter-bar { background:var(--paper); padding:16px 32px; display:flex; gap:16px; flex-wrap:wrap; border-bottom:1px solid var(--border); align-items:center; }
                .ap-filter-select { padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); min-width:160px; }
                .ap-filter-btn,.ap-print-btn { padding:8px 22px; border-radius:var(--radius-sm); cursor:pointer; border:none; }
                .ap-filter-btn { background:var(--gold); color:var(--navy); font-weight:600; }
                .ap-print-btn  { background:var(--navy-mid); color:#fff; text-decoration:none; display:inline-flex; align-items:center; gap:7px; font-size:13px; }
                .ap-print-btn-mock { background:var(--gold) !important; color:var(--navy) !important; font-weight:700 !important; }
                .ap-print-btn-group { margin-left:auto; display:flex; gap:10px; flex-wrap:wrap; }

                /* Body */
                .ap-body { padding:32px 24px; }

                /* Identity card */
                .ap-identity-card { background:var(--paper); border-radius:var(--radius); padding:24px 28px; display:flex; align-items:center; gap:24px; margin-bottom:24px; border:1px solid var(--border); }
                .ap-avatar { width:64px; height:64px; border-radius:50%; background:var(--navy); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:24px; font-weight:bold; overflow:hidden; flex-shrink:0; }
                .ap-avatar img { width:100%; height:100%; object-fit:cover; }
                .ap-identity-name { font-size:19px; font-weight:700; color:var(--navy); margin:0 0 6px; }

                /* Stats strip */
                .ap-stats-strip { display:grid; grid-template-columns:repeat(6,1fr); gap:14px; margin-bottom:24px; }
                @media(max-width:900px){ .ap-stats-strip{ grid-template-columns:repeat(3,1fr); } }
                .ap-stat-card { background:var(--paper); border:1px solid var(--border); border-radius:var(--radius); padding:18px 16px; text-align:center; }
                .ap-stat-value { font-size:26px; font-weight:700; color:var(--navy); }
                .ap-stat-label { font-size:10.5px; text-transform:uppercase; color:#7b85a3; margin-top:4px; }

                /* Attendance */
                .ap-attendance-card { background:var(--paper); border-radius:var(--radius); padding:22px 24px; margin-bottom:24px; border:1px solid var(--border); }
                .att-section-title { font-size:13px; font-weight:700; color:var(--navy); text-transform:uppercase; letter-spacing:.05em; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
                .att-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:16px; }
                @media(max-width:900px){ .att-grid{ grid-template-columns:repeat(3,1fr); } }
                .att-stat { background:var(--cream); border:1px solid var(--border); border-radius:10px; padding:12px 10px; text-align:center; }
                .att-stat-value { font-size:20px; font-weight:700; color:var(--navy); }
                .att-stat-label { font-size:9px; text-transform:uppercase; color:#7b85a3; margin-top:2px; letter-spacing:.04em; }
                .att-bar-wrap { margin-bottom:8px; }
                .att-bar-track { height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden; }
                .att-bar-fill  { height:100%; border-radius:5px; transition:width .6s ease; }
                .att-excellent { background:linear-gradient(90deg,#16a34a,#15803d); }
                .att-good      { background:linear-gradient(90deg,#2563eb,#1d4ed8); }
                .att-average   { background:linear-gradient(90deg,#d97706,#b45309); }
                .att-poor      { background:linear-gradient(90deg,#dc2626,#b91c1c); }
                .att-no-data   { text-align:center; padding:24px; color:#7b85a3; font-size:13px; }

                /* GPA trend card */
                .ap-trend-card { background:var(--paper); border-radius:var(--radius); padding:22px 24px; margin-bottom:24px; border:1px solid var(--border); }

                /* ── SHARED ACCORDION ── */
                .ap-accordion { display:flex; flex-direction:column; gap:12px; }
                .ap-accordion-item { background:var(--paper); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
                .ap-accordion-item.is-open { box-shadow:0 4px 12px rgba(0,0,0,.1); }
                .ap-accordion-trigger { width:100%; display:flex; justify-content:space-between; align-items:center; padding:18px 22px; background:none; border:none; cursor:pointer; text-align:left; }
                .ap-subject-name { font-size:15px; font-weight:700; color:var(--navy); }
                .ap-subject-code { font-size:11px; color:#7b85a3; margin-top:2px; }
                .ap-grade-pill { padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
                .ap-panel { display:none; border-top:1px solid var(--border); padding:22px; background:#fdf6e3; }
                .ap-accordion-item.is-open .ap-panel { display:block; }

                /* Grade colours - distinct and easily distinguishable */
                .grade-A1 { color: #0b8a2e; font-weight: 900; background: #e6f7ec; }
                .grade-B2 { color: #1a6bc4; font-weight: 900; background: #e3edfc; }
                .grade-B3 { color: #4a7bc4; font-weight: 900; background: #ecf1fa; }
                .grade-C4 { color: #c97d0e; font-weight: 900; background: #fdf3e0; }
                .grade-C5 { color: #d49a1a; font-weight: 900; background: #fef8e6; }
                .grade-C6 { color: #e0b02a; font-weight: 900; background: #fefce8; }
                .grade-D7 { color: #c05a1a; font-weight: 900; background: #fdf0e5; }
                .grade-E8 { color: #b84a2a; font-weight: 900; background: #fce8e0; }
                .grade-F9 { color: #c0392b; font-weight: 900; background: #fce4e2; }

                /* Compulsory subject indicators */
                .compulsory-pass {
                    color: #16a34a;
                    font-weight: 900;
                    font-size: 16px;
                    margin-left: 2px;
                    vertical-align: super;
                    display: inline-block;
                }
                .compulsory-fail {
                    color: #dc2626;
                    font-weight: 900;
                    font-size: 16px;
                    margin-left: 2px;
                    vertical-align: super;
                    display: inline-block;
                }

                /* Compulsory legend */
                .compulsory-legend {
                    font-size: 11px;
                    color: #6b7280;
                    margin-top: 8px;
                    padding: 8px 14px;
                    background: #f8fafc;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    flex-wrap: wrap;
                }
                .compulsory-legend .pass { color: #16a34a; font-weight: 700; }
                .compulsory-legend .fail { color: #dc2626; font-weight: 700; }

                /* Panel metrics strip */
                .ap-metrics-strip { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
                .ap-metric-box { background:white; padding:10px 16px; border-radius:8px; border:1px solid var(--border); }
                .ap-metric-box strong { display:block; font-size:10px; color:#7b85a3; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
                .ap-metric-box span { font-size:16px; font-weight:700; color:var(--navy); }
                .ap-metric-box.pos-class-cum   { border-left:3px solid #2563eb; }
                .ap-metric-box.pos-class-total { border-left:3px solid #0891b2; }
                .ap-metric-box.pos-arm-total   { border-left:3px solid #7c3aed; }
                .ap-metric-box.pos-arm-cum     { border-left:3px solid #a21caf; }
                .ap-metric-box.mock-pos        { border-left:3px solid #c9a84c; }
                .ap-metric-box.mock-avg        { border-left:3px solid #059669; }
                .ap-metric-box.mock-minmax     { border-left:3px solid #dc2626; }
                .ap-metric-box.compulsory-pass-box { border-left:3px solid #16a34a; }
                .ap-metric-box.compulsory-fail-box { border-left:3px solid #dc2626; }

                /* Assessment row */
                .ap-assessment-row { background:white; border-radius:8px; padding:12px 16px; margin-bottom:8px; border:1px solid #e9ecef; }
                .ap-assessment-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; font-size:13px; }
                .ap-bar-track { height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
                .ap-bar-fill  { height:100%; border-radius:4px; transition:width .5s ease; }
                .bar-excellent { background:#1a7f5a; }
                .bar-good      { background:#2563eb; }
                .bar-average   { background:#d4870a; }
                .bar-low       { background:#c0392b; }

                /* ════ MOCK SECTION ════ */
                .mock-section-card {
                    background: var(--paper);
                    border: 1px solid var(--border);
                    border-top: 4px solid var(--gold);
                    border-radius: var(--radius);
                    margin-top: 28px;
                    overflow: hidden;
                }
                .mock-section-header {
                    background: linear-gradient(135deg, #0f1c35 0%, #1a2f55 100%);
                    padding: 16px 22px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                .mock-section-header h4 {
                    color: #fff;
                    font-size: 15px;
                    font-weight: 700;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .mock-badge {
                    background: var(--gold);
                    color: #0f1c35;
                    font-size: 11px;
                    font-weight: 800;
                    padding: 3px 11px;
                    border-radius: 20px;
                    text-transform: uppercase;
                    letter-spacing: .5px;
                }
                .mock-ap-panel { background: #fffbea !important; border-top-color: #f0e4bc !important; }
                .mock-score-bar-lg { height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
                .mock-score-fill-lg { height:100%; border-radius:4px; transition:width .5s; }
                .mock-summary-strip {
                    background: #0f1c35;
                    color: rgba(255,255,255,.75);
                    font-weight: 600;
                    font-size: 13px;
                    text-align: center;
                    padding: 12px;
                    display: flex;
                    gap: 28px;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                .mock-summary-strip strong { color: var(--gold); }
                .mock-empty {
                    text-align: center;
                    padding: 36px 24px;
                    color: #7b85a3;
                    font-size: 13px;
                }
            </style>

            <div class="assessment-portal">

                @php
                    $displayTerm    = $selectedTermName ?? ($term->term ?? '');
                    $displaySession = $selectedSessionName ?? ($session->session ?? '');
                    $hasResults     = isset($subjectsWithAssessments) && $subjectsWithAssessments->isNotEmpty();
                @endphp

                {{-- HERO --}}
                <x-cb.hero title="My Assessment Report" icon="ri-file-chart-line"
                           subtitle="Your subject scores, assessment breakdowns, positions and attendance.">
                    <x-slot:actions>
                        @if($hasResults)
                            <button type="button" class="cb-hero-btn" id="showPrintModalBtn">
                                <i class="ri-printer-line"></i>Print terminal report
                            </button>
                            @if(isset($mockResults) && $mockResults->isNotEmpty())
                                <button type="button" class="cb-hero-btn" id="printMockBtn">
                                    <i class="ri-file-list-3-line"></i>Print mock report
                                </button>
                            @endif
                        @endif
                    </x-slot:actions>
                    <x-slot:pills>
                        <span class="cb-meta-pill">
                            <i class="ri-user-line"></i>{{ trim(($student->lastname ?? '') . ', ' . ($student->firstname ?? '') . ' ' . ($student->othername ?? ''), ', ') }}
                        </span>
                        <span class="cb-meta-pill"><i class="ri-hashtag"></i>{{ $student->admissionNo ?? '—' }}</span>
                        @isset($class)<span class="cb-meta-pill"><i class="ri-building-line"></i>{{ trim($class->schoolclass . ' ' . ($class->arm_name ?? '')) }}</span>@endisset
                        @if($displayTerm && $displaySession)<span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $displayTerm }} · {{ $displaySession }}</span>@endif
                    </x-slot:pills>
                </x-cb.hero>

                {{-- PERIOD PICKER --}}
                <div class="cb-card">
                    <form method="GET" action="{{ route('assessments') }}" class="cb-toolbar" style="border-bottom:none">
                        <select name="session_id" class="cb-select" id="sessionSelect" aria-label="Session" onchange="this.form.submit()">
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}" @selected(($selectedSessionId ?? null) == $s->id)>{{ $s->session }}{{ ($s->status ?? '') === 'Current' ? ' (current)' : '' }}</option>
                            @endforeach
                        </select>
                        <div class="term-chips" role="group" aria-label="Term">
                            <a class="term-chip {{ empty($userSelectedTermId) ? 'active' : '' }}"
                               href="{{ route('assessments', ['session_id' => $selectedSessionId]) }}" title="Your most recent term with results">Latest</a>
                            @foreach($terms->sortBy('id') as $t)
                                <a class="term-chip {{ ($userSelectedTermId ?? null) == $t->id ? 'active' : '' }}"
                                   href="{{ route('assessments', ['session_id' => $selectedSessionId, 'term_id' => $t->id]) }}">{{ $t->term }}</a>
                            @endforeach
                        </div>
                        <select name="term_id" id="termSelect" class="d-none" aria-hidden="true" tabindex="-1">
                            <option value=""></option>
                            @foreach($terms as $t)<option value="{{ $t->id }}" @selected(($userSelectedTermId ?? null) == $t->id)>{{ $t->term }}</option>@endforeach
                        </select>
                    </form>
                </div>

                <div class="ap-body">

                    @if(session('error') || !empty($error))
                        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') ?? $error }}</div></div>
                    @endif

                    @if(!isset($subjectsWithAssessments) || $subjectsWithAssessments->isEmpty())
                        <div class="cb-card">
                            <div class="empty-state">
                                <i class="ri-file-search-line"></i>
                                <h6>No results for this term yet</h6>
                                <p>Your scores will appear here once your teachers have entered them for {{ $displayTerm ?: 'the selected term' }}{{ $displaySession ? ', ' . $displaySession : '' }}.</p>
                            </div>
                        </div>
                    @else

                    {{-- STATS --}}
                    @php
                        $gpaGrade = $overallProgress['gpa_grade'] ?? '-';
                        $avg      = (float) ($overallProgress['average_cum'] ?? 0);
                    @endphp
                    <div class="row g-3 mb-4">
                        <div class="col-lg col-md-4 col-6"><x-cb.stat label="Subjects" :value="$overallProgress['total_subjects'] ?? 0" icon="ri-book-2-line" accent="teal"
                            :hint="($overallProgress['completed_subjects'] ?? 0) . ' with scores'" /></div>
                        <div class="col-lg col-md-4 col-6"><x-cb.stat label="Average (cum. avg)" :value="number_format($avg, 1)" icon="ri-percent-line"
                            :accent="$avg >= 70 ? 'green' : ($avg >= 50 ? 'sky' : ($avg >= 40 ? 'amber' : 'rose'))" /></div>
                        <div class="col-lg col-md-4 col-6"><x-cb.stat label="GPA" :value="number_format((float) ($overallProgress['gpa'] ?? 0), 2)" icon="ri-line-chart-line" accent="violet" /></div>
                        <div class="col-lg col-md-6 col-6"><x-cb.stat label="Overall grade" :value="$gpaGrade" icon="ri-award-line" accent="amber" /></div>
                        <div class="col-lg col-md-6 col-12"><x-cb.stat label="Total grade points" :value="number_format((float) ($overallProgress['total_grade_points'] ?? 0), 1)" icon="ri-medal-line" accent="sky" /></div>
                    </div>

                    {{-- ATTENDANCE CARD --}}
                    @php
                        $att         = $attendanceSummary ?? null;
                        $attPct      = $att ? (float)($att->attendance_percentage ?? 0) : null;
                        $attBarClass = $attPct === null ? '' : ($attPct >= 90 ? 'att-excellent' : ($attPct >= 75 ? 'att-good' : ($attPct >= 60 ? 'att-average' : 'att-poor')));
                        $attLabel    = $attPct === null ? '' : ($attPct >= 90 ? 'Excellent' : ($attPct >= 75 ? 'Good' : ($attPct >= 60 ? 'Average' : 'Poor')));
                        $attColor    = $attPct === null ? '' : ($attPct >= 90 ? '#16a34a' : ($attPct >= 75 ? '#2563eb' : ($attPct >= 60 ? '#d97706' : '#dc2626')));
                        $attBg       = $attPct === null ? '' : ($attPct >= 90 ? '#d1fae5' : ($attPct >= 75 ? '#dbeafe' : ($attPct >= 60 ? '#fef3c7' : '#fee2e2')));
                    @endphp
                    <div class="ap-attendance-card">
                        <div class="att-section-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                                <path d="m9 16 2 2 4-4"/>
                            </svg>
                            Attendance Summary
                        </div>
                        @if($att)
                        <div class="att-grid">
                            <div class="att-stat"><div class="att-stat-value">{{ $att->total_school_days ?? 0 }}</div><div class="att-stat-label">School Days</div></div>
                            <div class="att-stat" style="background:#d1fae5;border-color:#6ee7b7;"><div class="att-stat-value" style="color:#065f46;">{{ $att->days_present ?? 0 }}</div><div class="att-stat-label">Present</div></div>
                            <div class="att-stat" style="background:#fee2e2;border-color:#fca5a5;"><div class="att-stat-value" style="color:#991b1b;">{{ $att->days_absent ?? 0 }}</div><div class="att-stat-label">Absent</div></div>
                            <div class="att-stat" style="background:#fef9c3;border-color:#fde68a;"><div class="att-stat-value" style="color:#92400e;">{{ $att->days_late ?? 0 }}</div><div class="att-stat-label">Late</div></div>
                            <div class="att-stat" style="background:#e0f2fe;border-color:#7dd3fc;"><div class="att-stat-value" style="color:#075985;">{{ $att->days_sick_leave ?? 0 }}</div><div class="att-stat-label">Sick Leave</div></div>
                            <div class="att-stat" style="background:#f3e8ff;border-color:#d8b4fe;"><div class="att-stat-value" style="color:#6b21a8;">{{ $att->days_excused ?? 0 }}</div><div class="att-stat-label">Excused</div></div>
                        </div>
                        <div class="att-bar-wrap">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <span style="font-size:12px;color:#6b7280;font-weight:600;">Attendance Rate</span>
                                <span style="font-size:14px;font-weight:700;color:{{ $attColor }};">
                                    {{ number_format($attPct, 1) }}% <span style="font-size:11px;">({{ $attLabel }})</span>
                                </span>
                            </div>
                            <div class="att-bar-track">
                                <div class="att-bar-fill {{ $attBarClass }}" style="width:{{ min($attPct, 100) }}%;"></div>
                            </div>
                        </div>
                        @php $attRemark = $attPct >= 90 ? '🌟 Outstanding attendance! Keep it up.' : ($attPct >= 75 ? '👍 Good attendance. Aim for 90% and above.' : ($attPct >= 60 ? '⚠️ Your attendance needs improvement.' : '🚨 Poor attendance. Please attend regularly.')); @endphp
                        <div style="margin-top:10px;padding:10px 14px;border-radius:8px;background:{{ $attBg }};font-size:12px;font-weight:500;color:{{ $attColor }};">{{ $attRemark }}</div>
                        @else
                        <div class="att-no-data">Attendance data not available for this term/session.</div>
                        @endif
                    </div>

                    {{-- GPA TREND CHART --}}
                    @if(isset($gpaTrend) && count($gpaTrend) > 0)
                    <div class="ap-trend-card">
                        <h4 style="font-size:13px;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;">GPA Trend</h4>
                        <div style="height:200px;"><canvas id="gpaTrendChart"></canvas></div>
                    </div>
                    @endif

                    {{-- ════════════════════════════════════════════════
                         TERMINAL SUBJECTS ACCORDION WITH COMPULSORY INDICATORS
                    ════════════════════════════════════════════════ --}}
                    <div class="ap-accordion" id="apAccordion">
                        @foreach($subjectsWithAssessments as $idx => $subject)
                        @php
                            $grade = $subject['grade'] ?? '-';
                            $gradeClass = match(true) {
                                str_starts_with($grade, 'A1') => 'grade-A1',
                                str_starts_with($grade, 'B2') => 'grade-B2',
                                str_starts_with($grade, 'B3') => 'grade-B3',
                                str_starts_with($grade, 'C4') => 'grade-C4',
                                str_starts_with($grade, 'C5') => 'grade-C5',
                                str_starts_with($grade, 'C6') => 'grade-C6',
                                str_starts_with($grade, 'D7') => 'grade-D7',
                                str_starts_with($grade, 'E8') => 'grade-E8',
                                default => 'grade-F9',
                            };
                            $icons = ['📐','📚','🔬','🌍','💻','🎨','⚗️','📊','🏛️','🌿'];
                            $icon  = $icons[$idx % count($icons)];
                            
                            // Compulsory subject indicator
                            $isCompulsory = $subject['is_compulsory'] ?? false;
                            $cumAve = $subject['cum_ave'] ?? 0;
                            $isPassing = $cumAve >= 50;
                            $compulsorySymbol = '';
                            $compulsoryClass = '';
                            if ($isCompulsory) {
                                $compulsoryClass = $isPassing ? 'compulsory-pass' : 'compulsory-fail';
                                $compulsorySymbol = '<span class="' . $compulsoryClass . '" title="' . ($isPassing ? 'Passed' : 'Failed') . ' compulsory subject">*</span>';
                            }
                        @endphp
                        <div class="ap-accordion-item {{ $idx === 0 ? 'is-open' : '' }}" id="item-{{ $idx }}">
                            <button class="ap-accordion-trigger" onclick="toggleItem({{ $idx }})">
                                <div style="display:flex;align-items:center;gap:14px;">
                                    <div style="width:40px;height:40px;background:var(--navy);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">{{ $icon }}</div>
                                    <div>
                                        <p class="ap-subject-name">
                                            {{ $subject['subject_name'] ?? 'Unknown Subject' }}
                                            {!! $compulsorySymbol !!}
                                            @if($isCompulsory)
                                                <span style="font-size:8px;color:#6b7280;font-weight:400;margin-left:4px;">
                                                    ({{ $isPassing ? '✓ Passed' : '✗ Failed' }})
                                                </span>
                                            @endif
                                        </p>
                                        <p class="ap-subject-code">{{ $subject['subject_code'] ?? '' }}</p>
                                    </div>
                                </div>
                                <div style="display:flex;gap:12px;align-items:center;">
                                    <span class="ap-grade-pill {{ $gradeClass }}">{{ $grade }}</span>
                                    <span style="font-weight:600;font-size:14px;">{{ number_format(round($subject['cum'] ?? 0)) }}</span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                            </button>
                            <div class="ap-panel">
                                <div class="ap-metrics-strip">
                                    <div class="ap-metric-box"><strong>Total</strong><span>{{ number_format(round($subject['total'] ?? 0)) }}</span></div>
                                    <div class="ap-metric-box"><strong>Cumulative</strong><span>{{ number_format(round($subject['cum'] ?? 0)) }}</span></div>
                                    <div class="ap-metric-box"><strong>Cum. Average</strong><span>{{ number_format(round($subject['cum_ave'] ?? 0)) }}</span></div>
                                    <div class="ap-metric-box"><strong>Subject GPA</strong><span>{{ number_format($subject['subject_gpa'] ?? 0, 1) }}</span></div>
                                    @if($isCompulsory)
                                        <div class="ap-metric-box {{ $isPassing ? 'compulsory-pass-box' : 'compulsory-fail-box' }}">
                                            <strong>Compulsory</strong>
                                            <span style="color:{{ $isPassing ? '#16a34a' : '#dc2626' }};">
                                                {{ $isPassing ? '✓ PASSED' : '✗ FAILED' }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="ap-metric-box pos-arm-total"><strong>Arm Pos (Total)</strong><span>{{ $subject['arm_position'] ?? '—' }}</span></div>
                                    <div class="ap-metric-box pos-arm-cum"><strong>Arm Pos (Cum)</strong><span>{{ $subject['arm_position_cum'] ?? '—' }}</span></div>
                                    <div class="ap-metric-box pos-class-total"><strong>Class Pos (Total)</strong><span>{{ $subject['position_total'] ?? '—' }}</span></div>
                                    <div class="ap-metric-box pos-class-cum"><strong>Class Pos (Cum)</strong><span>{{ $subject['position'] ?? '—' }}</span></div>
                                </div>
                                @if(isset($subject['assessments']) && $subject['assessments']->isNotEmpty())
                                <h4 style="font-size:12px;margin-bottom:12px;text-transform:uppercase;letter-spacing:.04em;color:#374151;">Assessment Breakdown</h4>
                                @foreach($subject['assessments'] as $assessment)
                                @php $pct = $assessment['percentage'] ?? 0; $barClass = $pct >= 70 ? 'bar-excellent' : ($pct >= 50 ? 'bar-good' : ($pct >= 40 ? 'bar-average' : 'bar-low')); @endphp
                                <div class="ap-assessment-row">
                                    <div class="ap-assessment-header">
                                        <span><strong>{{ $assessment['name'] }}</strong></span>
                                        <span style="font-size:13px;color:#374151;">
                                            {{ number_format($assessment['score'] ?? 0, 1) }} / {{ $assessment['max_score'] ?? 0 }}
                                            <span style="color:#7b85a3;">({{ $pct }}%)</span>
                                        </span>
                                    </div>
                                    <div class="ap-bar-track"><div class="ap-bar-fill {{ $barClass }}" style="width:{{ min($pct,100) }}%;"></div></div>
                                </div>
                                @endforeach

                                <div class="ap-assessment-row" style="background:#f0f9f0;border-color:#4ade80;margin-top:12px;">
                                    <div class="ap-assessment-header">
                                        <span><strong style="color:#166534;">TOTAL SCORE</strong></span>
                                        <span style="font-size:15px;font-weight:700;color:#166534;">{{ number_format(round($subject['total'] ?? 0)) }} / 100</span>
                                    </div>
                                    <div class="ap-bar-track"><div class="ap-bar-fill bar-excellent" style="width:{{ min($subject['total'] ?? 0, 100) }}%;"></div></div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- COMPULSORY SUBJECT LEGEND --}}
                    @php
                        $hasCompulsorySubjects = collect($subjectsWithAssessments)->contains(fn($s) => $s['is_compulsory'] ?? false);
                    @endphp
                    @if($hasCompulsorySubjects)
                    <div class="compulsory-legend">
                        <span>📌 <strong>Compulsory Subjects:</strong></span>
                        <span class="pass">✓ <span style="color:#16a34a;">*</span> = Passed</span>
                        <span class="fail">✗ <span style="color:#dc2626;">*</span> = Failed</span>
                        <span style="color:#6b7280;font-size:10px;">
                            (Must pass all compulsory subjects for promotion)
                        </span>
                    </div>
                    @endif

                    {{-- ════════════════════════════════════════════════
                         MOCK EXAM RESULTS — ACCORDION UI
                    ════════════════════════════════════════════════ --}}
                    @php $hasMock = isset($mockResults) && $mockResults->isNotEmpty(); @endphp

                    <div class="mock-section-card">
                        <div class="mock-section-header">
                            <h4>
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M9 11l3 3L22 4"/>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                                Mock Exam Results
                            </h4>
                            @if($hasMock)
                                <span class="mock-badge">{{ $mockResults->count() }} Subjects</span>
                            @endif
                        </div>

                        @if($hasMock)

                        {{-- Mock summary strip (always visible above accordion) --}}
                        <div class="mock-summary-strip">
                            <div>Total Obtained: <strong>{{ $mockSummary['obtained'] ?? 0 }}</strong></div>
                            <div>Total Obtainable: <strong>{{ $mockSummary['obtainable'] ?? 0 }}</strong></div>
                            <div>Overall %: <strong>{{ $mockSummary['percentage'] ?? 0 }}%</strong></div>
                        </div>

                        {{-- Mock subjects accordion --}}
                        <div style="padding:20px 24px;">
                            <div class="ap-accordion" id="mockAccordion">
                                @foreach($mockResults as $mi => $mock)
                                @php
                                    $mg = $mock->grade ?? '-';
                                    $mgClass = match(true) {
                                        str_starts_with($mg, 'A1') => 'grade-A1',
                                        str_starts_with($mg, 'B2') => 'grade-B2',
                                        str_starts_with($mg, 'B3') => 'grade-B3',
                                        str_starts_with($mg, 'C4') => 'grade-C4',
                                        str_starts_with($mg, 'C5') => 'grade-C5',
                                        str_starts_with($mg, 'C6') => 'grade-C6',
                                        str_starts_with($mg, 'D7') => 'grade-D7',
                                        str_starts_with($mg, 'E8') => 'grade-E8',
                                        default => 'grade-F9',
                                    };
                                    $mTotal    = (float)($mock->total ?? 0);
                                    $mBarColor = $mTotal >= 70 ? '#1a7f5a' : ($mTotal >= 50 ? '#2563eb' : ($mTotal >= 40 ? '#d4870a' : '#c0392b'));
                                    $mBarClass = $mTotal >= 70 ? 'bar-excellent' : ($mTotal >= 50 ? 'bar-good' : ($mTotal >= 40 ? 'bar-average' : 'bar-low'));
                                    $mIcons    = ['🏆','📝','✏️','📖','🔖','📋','🗒️','📌','🗂️','📑'];
                                    $mIcon     = $mIcons[$mi % count($mIcons)];
                                @endphp
                                <div class="ap-accordion-item {{ $mi === 0 ? 'is-open' : '' }}" id="mock-item-{{ $mi }}">
                                    <button class="ap-accordion-trigger" onclick="toggleMockItem({{ $mi }})">
                                        <div style="display:flex;align-items:center;gap:14px;">
                                            <div style="width:40px;height:40px;background:linear-gradient(135deg,#0f1c35,#1a2f55);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">{{ $mIcon }}</div>
                                            <div>
                                                <p class="ap-subject-name">{{ $mock->subject_name ?? 'Unknown Subject' }}</p>
                                                @if(!empty($mock->subject_code))
                                                    <p class="ap-subject-code">{{ $mock->subject_code }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div style="display:flex;gap:12px;align-items:center;">
                                            <span class="ap-grade-pill {{ $mgClass }}">{{ $mg }}</span>
                                            <span style="font-weight:600;font-size:14px;">{{ number_format($mTotal, 1) }}</span>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                        </div>
                                    </button>
                                    <div class="ap-panel mock-ap-panel">
                                        {{-- Metrics strip --}}
                                        <div class="ap-metrics-strip">
                                            <div class="ap-metric-box">
                                                <strong>Exam Score</strong>
                                                <span>{{ number_format($mock->exam ?? 0, 1) }}</span>
                                            </div>
                                            <div class="ap-metric-box">
                                                <strong>Total / 100</strong>
                                                <span>{{ number_format($mTotal, 1) }}</span>
                                            </div>
                                            <div class="ap-metric-box mock-pos">
                                                <strong>Position</strong>
                                                <span>{{ $mock->position ?? '—' }}</span>
                                            </div>
                                            <div class="ap-metric-box mock-avg">
                                                <strong>Class Avg</strong>
                                                <span>{{ number_format($mock->class_average ?? 0, 1) }}</span>
                                            </div>
                                            <div class="ap-metric-box mock-minmax">
                                                <strong>Min</strong>
                                                <span style="color:#dc2626;">{{ number_format($mock->cmin ?? 0, 1) }}</span>
                                            </div>
                                            <div class="ap-metric-box mock-minmax">
                                                <strong>Max</strong>
                                                <span style="color:#16a34a;">{{ number_format($mock->cmax ?? 0, 1) }}</span>
                                            </div>
                                        </div>

                                        {{-- Score breakdown --}}
                                        <h4 style="font-size:12px;margin-bottom:12px;text-transform:uppercase;letter-spacing:.04em;color:#374151;">Score Breakdown</h4>

                                        <div class="ap-assessment-row">
                                            <div class="ap-assessment-header">
                                                <span><strong>Exam Score</strong></span>
                                                <span style="font-size:13px;color:#374151;">
                                                    {{ number_format($mock->exam ?? 0, 1) }} / 100
                                                    <span style="color:#7b85a3;">({{ number_format($mTotal, 1) }}%)</span>
                                                </span>
                                            </div>
                                            <div class="ap-bar-track">
                                                <div class="ap-bar-fill {{ $mBarClass }}" style="width:{{ min($mTotal,100) }}%;"></div>
                                            </div>
                                        </div>

                                        <div class="ap-assessment-row" style="background:#f0f9f0;border-color:#4ade80;margin-top:12px;">
                                            <div class="ap-assessment-header">
                                                <span><strong style="color:#166534;">TOTAL SCORE</strong></span>
                                                <span style="font-size:15px;font-weight:700;color:#166534;">{{ number_format($mTotal, 1) }} / 100</span>
                                            </div>
                                            <div class="ap-bar-track">
                                                <div class="ap-bar-fill bar-excellent" style="width:{{ min($mTotal,100) }}%;"></div>
                                            </div>
                                        </div>

                                        @if(!empty($mock->remark) && $mock->remark !== '—')
                                        <div style="margin-top:12px;padding:10px 14px;border-radius:8px;background:#fef3c7;border:1px solid #fde68a;font-size:12px;color:#92400e;font-weight:500;">
                                            💬 Remark: <strong>{{ $mock->remark }}</strong>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        @else
                        <div class="mock-empty">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 10px;display:block;">
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                                <rect x="9" y="3" width="6" height="4" rx="2"/>
                                <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
                            </svg>
                            No mock exam results are available for this term.
                        </div>
                        @endif
                    </div>

                    @endif {{-- end subjectsWithAssessments --}}
                </div>
            </div>

            {{-- PRINT COLUMN SELECTION MODAL --}}
            <div class="modal fade" id="printModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Select Columns for PDF Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">Select the columns you want to include in your PDF report.</div>

                            <div class="card mb-3">
                                <div class="card-header">Student Information</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4"><label><input type="checkbox" class="col-checkbox" value="picture" checked> Student Picture</label></div>
                                        <div class="col-md-4"><label><input type="checkbox" class="col-checkbox" value="gender"> Gender</label></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Assessments</span>
                                    <label class="mb-0"><input type="checkbox" id="selectAllAssessments"> Select All</label>
                                </div>
                                <div class="card-body">
                                    <div class="row" id="assessmentsCheckboxes">
                                        @foreach($allAssessments ?? [] as $assessment)
                                        <div class="col-md-4">
                                            <label>
                                                <input type="checkbox" class="col-checkbox assessment-cb" value="{{ $assessment->id }}" checked>
                                                {{ $assessment->name }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">Scores &amp; Metrics</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="sn" checked> S/N</label></div>
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="name" checked> Subject Name</label></div>
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="total" checked> Total</label></div>
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="bf" checked> BF</label></div>
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="cum" checked> Cumulative</label></div>
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="cum_ave" checked> Cum. Average</label></div>
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="grade" checked> Grade</label></div>
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="class_average" checked> Class Average</label></div>
                                        <div class="col-md-3"><label><input type="checkbox" class="col-checkbox" value="compulsory_flag"> Compulsory</label></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Position Columns</span>
                                    <label class="mb-0 text-muted" style="font-size:11px;">4 separate position types</label>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6"><label><input type="checkbox" class="col-checkbox" value="position" checked><strong>Class Pos (Cum)</strong></label></div>
                                        <div class="col-md-6"><label><input type="checkbox" class="col-checkbox" value="position_total" checked><strong>Class Pos (Total)</strong></label></div>
                                        <div class="col-md-6 mt-2"><label><input type="checkbox" class="col-checkbox" value="arm_position" checked><strong>Arm Pos (Total)</strong></label></div>
                                        <div class="col-md-6 mt-2"><label><input type="checkbox" class="col-checkbox" value="arm_position_cum" checked><strong>Arm Pos (Cum)</strong></label></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">Attendance</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4"><label><input type="checkbox" class="col-checkbox" value="attendance" checked> Include Attendance Summary</label></div>
                                    </div>
                                </div>
                            </div>

                            @if(isset($mockResults) && $mockResults->isNotEmpty())
                            <div class="card mb-3" style="border-top:3px solid #c9a84c;">
                                <div class="card-header" style="background:#0f1c35;color:#fff;font-weight:700;">
                                    🏆 Mock Exam Results
                                </div>
                                <div class="card-body">
                                    <label>
                                        <input type="checkbox" class="col-checkbox" value="include_mock" checked>
                                        <strong>Include Mock Results section in PDF</strong>
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="generatePdfBtn">Generate PDF</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Terminal accordion
    function toggleItem(idx) {
        document.getElementById('item-' + idx).classList.toggle('is-open');
    }

    // Mock accordion
    function toggleMockItem(idx) {
        document.getElementById('mock-item-' + idx).classList.toggle('is-open');
    }

    // Terminal print modal
    document.getElementById('showPrintModalBtn')?.addEventListener('click', function () {
        new bootstrap.Modal(document.getElementById('printModal')).show();
    });

    document.getElementById('selectAllAssessments')?.addEventListener('change', function () {
        document.querySelectorAll('.assessment-cb').forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('generatePdfBtn')?.addEventListener('click', function () {
        const selectedColumns = ['sn', 'name'];
        document.querySelectorAll('.col-checkbox:checked').forEach(cb => {
            if (!selectedColumns.includes(cb.value)) selectedColumns.push(cb.value);
        });

        if (selectedColumns.length <= 2) {
            Swal.fire({ icon:'warning', title:'No Columns Selected', text:'Please select at least one column.' });
            return;
        }

        const termId    = document.getElementById('termSelect').value;
        const sessionId = document.getElementById('sessionSelect').value;

        if (!termId || !sessionId) {
            Swal.fire({ icon:'warning', title:'Missing Selection', text:'Please select both Term and Session.' });
            return;
        }

        bootstrap.Modal.getInstance(document.getElementById('printModal')).hide();

        Swal.fire({
            title: 'Generating PDF',
            html: 'Please wait while your report is being prepared...',
            icon: 'info', showConfirmButton: false, allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const params = new URLSearchParams();
        params.append('session_id', sessionId);
        params.append('term_id', termId);
        selectedColumns.forEach(col => params.append('selected_columns[]', col));

        window.open("{{ route('assessments.print') }}?" + params.toString(), '_blank');
        setTimeout(() => Swal.close(), 1500);
    });

    // Mock PDF print button
    document.getElementById('printMockBtn')?.addEventListener('click', function () {
        const termId    = document.getElementById('termSelect').value;
        const sessionId = document.getElementById('sessionSelect').value;

        if (!termId || !sessionId) {
            Swal.fire({ icon:'warning', title:'Missing Selection', text:'Please select both Term and Session before printing the mock report.' });
            return;
        }

        Swal.fire({
            title: 'Generating Mock PDF',
            html: 'Please wait while your mock report is being prepared...',
            icon: 'info', showConfirmButton: false, allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const params = new URLSearchParams({ session_id: sessionId, term_id: termId });
        window.open("{{ route('assessments.print.mock') }}?" + params.toString(), '_blank');
        setTimeout(() => Swal.close(), 1500);
    });

    @if(isset($gpaTrend) && count($gpaTrend) > 0)
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('gpaTrendChart')?.getContext('2d');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json(array_keys($gpaTrend)),
                    datasets: [{
                        label: 'GPA',
                        data: @json(array_values($gpaTrend)),
                        borderColor: '#c9a84c',
                        backgroundColor: 'rgba(201,168,76,0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#0f1c35',
                        pointBorderColor: '#c9a84c',
                        pointRadius: 5,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, max: 5 } }
                }
            });
        }
    });
    @endif
</script>
@endsection