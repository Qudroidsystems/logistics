@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style>
:root {
    --principal-primary: #1e3a5f;
    --principal-accent:  #2563eb;
    --principal-success: #16a34a;
    --principal-warning: #d97706;
    --principal-danger:  #dc2626;
    --principal-muted:   #6b7280;
    --principal-border:  #e2e8f0;
    --principal-bg:      #f8fafc;
    --principal-radius:  12px;
    --principal-shadow:  0 2px 8px rgba(0,0,0,.08);
    --mock-color:        #7c3aed;
    --mock-light:        #ede9fe;
    --cumave-color:      #7c3aed;
}

/* Hero */
.principal-hero {
    background: linear-gradient(135deg, var(--principal-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--principal-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.principal-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.principal-hero::after {
    content: '';
    position: absolute;
    bottom: -80px; left: -30px;
    width: 260px; height: 260px;
    background: rgba(255,255,255,.03);
    border-radius: 50%;
}
.principal-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.principal-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* Scoring Mode Bar */
.scoring-mode-bar {
    background: #fff;
    border: 1px solid var(--principal-border);
    border-radius: var(--principal-radius);
    padding: 14px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: var(--principal-shadow);
}
.scoring-mode-bar .mode-label { font-size:13px; font-weight:600; color:var(--principal-primary); white-space:nowrap; }
.scoring-mode-toggle {
    display: flex;
    background: var(--principal-bg);
    border: 1.5px solid var(--principal-border);
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.scoring-mode-toggle .mode-btn {
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    background: transparent;
    color: var(--principal-muted);
    cursor: pointer;
    transition: all .2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}
.scoring-mode-toggle .mode-btn:hover { background:#e9ecef; color:var(--principal-primary); }
.scoring-mode-toggle .mode-btn.active         { background: var(--principal-primary); color:#fff; }
.scoring-mode-toggle .mode-btn.active-term    { background: #0891b2; color:#fff; }
.scoring-mode-toggle .mode-btn.active-mock    { background: var(--mock-color); color:#fff; }
.scoring-mode-toggle .mode-btn.active-cumave  { background: linear-gradient(135deg,#1e3a5f,#2563eb); color:#fff; }
.scoring-mode-toggle .mode-btn.active-total   { background: linear-gradient(135deg,#0891b2,#06b6d4); color:#fff; }

.mode-hint {
    font-size:12px; color:var(--principal-muted);
    background:var(--principal-bg);
    border:1px dashed var(--principal-border);
    border-radius:6px; padding:5px 10px;
}
.mode-hint strong { color:var(--principal-primary); }

/* Mode badges */
.principal-badge-mode-cum   { background:linear-gradient(135deg,#1e3a5f,#2563eb); color:#fff; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700; }
.principal-badge-mode-term  { background:linear-gradient(135deg,#0891b2,#06b6d4); color:#fff; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700; }
.principal-badge-mode-mock  { background:linear-gradient(135deg,#7c3aed,#a855f7); color:#fff; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700; }
.principal-badge-mode-cumave { background:linear-gradient(135deg,#1e3a5f,#2563eb); color:#fff; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700; }
.principal-badge-mode-total  { background:linear-gradient(135deg,#0891b2,#06b6d4); color:#fff; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700; }

/* Stat cards */
.stat-card {
    background:#fff;
    border:1px solid var(--principal-border);
    border-radius:var(--principal-radius);
    padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--principal-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--principal-primary); }
.stat-card .stat-label { font-size:12px; color:var(--principal-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* Badges */
.principal-badge          { display:inline-flex; align-items:center; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.principal-badge-senior   { background:#fef3c7; color:#d97706; }
.principal-badge-junior   { background:#dbeafe; color:#2563eb; }
.principal-badge-cumulative { background:linear-gradient(135deg,#17a2b8,#0d6efd); color:#fff; }
.principal-badge-cumave   { background:#ede9fe; color:#5b21b6; }
.principal-badge-total    { background:#dbeafe; color:#0891b2; }

/* Cum Ave badge */
.cumave-badge {
    display:inline-block;
    background:#ede9fe;
    color:#5b21b6;
    padding:2px 10px;
    border-radius:12px;
    font-size:10px;
    font-weight:700;
    border:1px solid #c4b5fd;
}

/* Avatar */
.avatar-clickable { cursor:pointer; transition:transform .2s ease, opacity .2s ease; }
.avatar-clickable:hover { transform:scale(1.1); opacity:.9; }
.student-avatar { width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid var(--principal-border); background:#f0f0f0; }
.avatar-placeholder {
    width:45px; height:45px; border-radius:50%;
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    display:inline-flex; align-items:center; justify-content:center;
    font-size:16px; font-weight:700; color:white;
    border:2px solid var(--principal-border);
    cursor:pointer; transition:transform .2s ease;
}
.avatar-placeholder:hover { transform:scale(1.1); }

/* Image zoom */
.image-zoom-modal .modal-content { background:transparent; border:none; box-shadow:none; }
.image-zoom-modal .modal-dialog { max-width:90vw; margin:1.75rem auto; }
.image-zoom-modal .modal-body { display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:80vh; padding:20px; }
.zoomed-image { max-width:90vw; max-height:75vh; border-radius:16px; box-shadow:0 25px 50px rgba(0,0,0,.3); border:4px solid white; cursor:pointer; animation:zoomIn .3s ease; object-fit:contain; }
@keyframes zoomIn { from { opacity:0; transform:scale(.8); } to { opacity:1; transform:scale(1); } }
.image-zoom-modal .btn-close { position:absolute; top:20px; right:30px; background-color:rgba(0,0,0,.7); border-radius:50%; padding:12px; opacity:1; z-index:1060; filter:brightness(0) invert(1); }
.image-zoom-modal .btn-close:hover { background-color:rgba(0,0,0,.9); transform:scale(1.1); }
.zoomed-image-name    { color:white; margin-top:20px; font-size:18px; font-weight:600; text-shadow:0 2px 4px rgba(0,0,0,.3); background:rgba(0,0,0,.5); padding:8px 20px; border-radius:40px; display:inline-block; }
.zoomed-image-details { color:rgba(255,255,255,.8); margin-top:8px; font-size:14px; text-align:center; }

/* ── Triple-Score Subject Card ── */
.subject-score-card {
    background:var(--principal-bg);
    border-radius:10px; padding:8px 6px; text-align:center;
    transition:all .2s ease; min-width:110px;
    border:1px solid var(--principal-border);
}
.subject-score-card:hover { background:#e9ecef; transform:translateY(-2px); box-shadow:var(--principal-shadow); }
.subject-score-card .score-subject-name { font-size:.65rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; line-height:1.2; }

/* Term row */
.score-row-term { background:rgba(8,145,178,.07); border-radius:6px; padding:4px 5px; margin-bottom:4px; border-left:3px solid #0891b2; }
.score-row-term .score-type-label { font-size:.55rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#0891b2; margin-bottom:1px; }

/* Cumulative row */
.score-row-cum { background:rgba(30,58,95,.07); border-radius:6px; padding:4px 5px; margin-bottom:4px; border-left:3px solid var(--principal-primary); }
.score-row-cum .score-type-label { font-size:.55rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--principal-primary); margin-bottom:1px; }

/* Cum Ave row */
.score-row-cumave { background:rgba(124,58,237,.07); border-radius:6px; padding:4px 5px; margin-bottom:4px; border-left:3px solid #7c3aed; }
.score-row-cumave .score-type-label { font-size:.55rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#7c3aed; margin-bottom:1px; }

/* Mock row */
.score-row-mock { background:rgba(124,58,237,.07); border-radius:6px; padding:4px 5px; border-left:3px solid var(--mock-color); }
.score-row-mock .score-type-label { font-size:.55rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--mock-color); margin-bottom:1px; }

.score-value { font-size:1rem; font-weight:700; line-height:1; }
.score-grade-badge { display:inline-block; padding:1px 6px; border-radius:10px; font-size:.6rem; font-weight:700; margin-left:3px; vertical-align:middle; }

/* Active mode highlight */
.subject-score-card.mode-cum .score-row-cum   { background:rgba(30,58,95,.13); box-shadow:0 1px 4px rgba(30,58,95,.1); }
.subject-score-card.mode-term .score-row-term { background:rgba(8,145,178,.14); box-shadow:0 1px 4px rgba(8,145,178,.12); }
.subject-score-card.mode-mock .score-row-mock { background:rgba(124,58,237,.14); box-shadow:0 1px 4px rgba(124,58,237,.12); }
.subject-score-card.mode-cumave .score-row-cumave { background:rgba(124,58,237,.14); box-shadow:0 1px 4px rgba(124,58,237,.12); }

/* Grade colours */
.grade-a,.grade-a1  { background:#16a34a; color:#fff; }
.grade-b,.grade-b2,.grade-b3 { background:#0891b2; color:#fff; }
.grade-c,.grade-c4,.grade-c5,.grade-c6 { background:#6b7280; color:#fff; }
.grade-d,.grade-d7  { background:#d97706; color:#fff; }
.grade-e,.grade-e8  { background:#ea580c; color:#fff; }
.grade-f,.grade-f9  { background:#dc2626; color:#fff; }

.highlight-red    { color:#dc2626!important; font-weight:600; }
.highlight-orange { color:#ea580c!important; font-weight:600; }
.highlight-green  { color:#16a34a!important; font-weight:600; }
.highlight-purple { color:var(--mock-color)!important; font-weight:600; }

/* Table */
.principal-table th { background:var(--principal-primary); color:#fff; padding:12px 16px; font-weight:600; font-size:13px; white-space:nowrap; }
.principal-table td { padding:12px 16px; vertical-align:middle; border-bottom:1px solid var(--principal-border); font-size:13px; }
.principal-table tr:hover td { background:#eff6ff; }

/* Comment dropdown */
.form-select.teacher-comment-dropdown {
    width:100%; min-width:200px; cursor:pointer;
    background-color:var(--principal-bg);
    border:1.5px solid var(--principal-border);
    border-radius:8px; transition:all .2s ease; font-size:.85rem;
}
.form-select.teacher-comment-dropdown:focus { background-color:#fff; border-color:var(--principal-accent); box-shadow:0 0 0 3px rgba(37,99,235,.1); }

.comment-cell { position:relative; }
.comment-info-icon {
    position:absolute; right:5px; top:50%; transform:translateY(-50%);
    font-size:1rem; color:var(--principal-accent); z-index:2;
    background:#fff; border-radius:50%; width:28px; height:28px;
    display:flex; align-items:center; justify-content:center;
    border:none; cursor:pointer; transition:all .2s ease;
}
.comment-info-icon:hover { color:#0056b3; background:#e9ecef; }

/* Intelligent comment */
.intelligent-comment-section { border-left:3px solid #28a745; background-color:#f0fdf4!important; margin-bottom:10px; border-radius:8px; padding:8px; }
.intelligent-comment-preview { font-size:.8rem; line-height:1.4; white-space:pre-wrap; background:#fff; border:1px solid var(--principal-border); border-radius:6px; padding:8px; margin-top:5px; }
.intelligent-comment-text   { color:#155724; font-weight:500; }
.saved-comment-preview { background-color:#f0fdf4!important; border-left:3px solid #28a745; border-radius:8px; padding:8px; font-size:.8rem; line-height:1.4; white-space:pre-wrap; max-height:100px; overflow-y:auto; }

/* Mock notice banner */
.mock-notice-banner {
    background:linear-gradient(135deg,#7c3aed,#a855f7);
    color:#fff; border-radius:var(--principal-radius);
    padding:12px 20px; margin-bottom:16px;
    display:flex; align-items:center; gap:10px;
    font-size:13px; font-weight:600;
}

/* Grades tooltip */
.grades-tooltip {
    position:fixed;
    background:#fff;
    border:2px solid var(--principal-accent);
    border-radius:16px;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
    width:680px;
    max-height:620px;
    overflow:hidden;
    z-index:10050;
    opacity:0;
    visibility:hidden;
    transition:all .3s cubic-bezier(.4,0,.2,1);
    pointer-events:none;
}
.grades-tooltip.show { opacity:1; visibility:visible; pointer-events:auto; animation:tooltipFadeIn .3s ease-out; }
@keyframes tooltipFadeIn {
    from { opacity:0; transform:translate(-50%,-48%) scale(.95); }
    to   { opacity:1; transform:translate(-50%,-50%) scale(1); }
}
.grades-tooltip.position-bottom { bottom:15%; left:50%; transform:translateX(-50%); }
.grades-tooltip .tooltip-header { background:linear-gradient(135deg,var(--principal-primary) 0%,var(--principal-accent) 60%,#4f46e5 100%); color:#fff; padding:16px 50px 16px 20px; font-weight:700; font-size:1.1rem; border-radius:14px 14px 0 0; margin:-2px -2px 15px -2px; position:relative; display:flex; align-items:center; }
.grades-tooltip .tooltip-close  { position:absolute; right:15px; top:50%; transform:translateY(-50%); width:30px; height:30px; border-radius:50%; background:rgba(255,255,255,.2); color:#fff; font-size:1.2rem; display:flex; align-items:center; justify-content:center; cursor:pointer; border:none; }
.grades-tooltip .tooltip-body   { padding:0 15px 15px 15px; max-height:500px; overflow-y:auto; }

.tooltip-grade-header { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.tooltip-col-term   { color:#0891b2; }
.tooltip-col-cum    { color:var(--principal-primary); }
.tooltip-col-cumave { color:#7c3aed; }
.tooltip-col-mock   { color:var(--mock-color); }
.tooltip-col-pos    { color:#6b7280; }

/* BF highlight */
.bf-value { font-size:.75rem; color:var(--principal-muted); font-weight:500; }

/* Auto-save toast */
.auto-save-toast { position:fixed; bottom:20px; right:20px; z-index:99999; min-width:280px; box-shadow:0 4px 12px rgba(0,0,0,.15); }

/* Mobile cards */
.mobile-cards { display:none; }
.student-card { background:#fff; border:1px solid var(--principal-border); border-radius:12px; margin-bottom:1.5rem; box-shadow:var(--principal-shadow); overflow:hidden; }
.student-header { background:var(--principal-bg); padding:12px 15px; border-bottom:1px solid var(--principal-border); }
.student-info { display:flex; align-items:center; gap:12px; }
.avatar-sm { width:45px; height:45px; object-fit:cover; border-radius:50%; cursor:pointer; transition:transform .2s ease; border:2px solid var(--principal-border); }
.avatar-sm:hover { transform:scale(1.1); }
.student-details h6 { margin:0; font-size:1rem; font-weight:600; }
.student-meta { font-size:.8rem; color:var(--principal-muted); }
.student-body { padding:15px; }
.subjects-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px; margin-bottom:20px; }
.subject-item { text-align:center; padding:10px 6px; background:var(--principal-bg); border-radius:10px; border:1px solid var(--principal-border); }
.subject-name { font-size:.7rem; font-weight:700; color:#495057; margin-bottom:6px; line-height:1.2; }
.performance-summary { background:linear-gradient(135deg,var(--principal-primary) 0%,var(--principal-accent) 100%); border-radius:12px; padding:15px; margin-bottom:15px; color:#fff; }
.performance-summary.mock-mode { background:linear-gradient(135deg,#7c3aed 0%,#a855f7 100%); }
.performance-summary.cumave-mode { background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); }
.performance-summary.total-mode { background:linear-gradient(135deg,#0891b2 0%,#06b6d4 100%); }
.summary-title { font-weight:600; font-size:.85rem; margin-bottom:12px; display:flex; align-items:center; gap:6px; }
.summary-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; text-align:center; }
.summary-item { padding:8px; background:rgba(255,255,255,.15); border-radius:8px; backdrop-filter:blur(5px); }
.summary-label { font-size:.65rem; opacity:.9; margin-bottom:4px; text-transform:uppercase; letter-spacing:.5px; }
.summary-value { font-size:1.2rem; font-weight:700; }

/* Responsive */
@media (min-width:1200px) { .desktop-table { display:block!important; } .mobile-cards { display:none!important; } }
@media (max-width:1199.98px) { .desktop-table { display:none!important; } .mobile-cards { display:block!important; } }

.spin-icon { animation:spin 1s linear infinite; }
@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }

.class-arm-badge  { font-size:10px; padding:2px 7px; background:#e0f2fe; color:#0369a1; border-radius:12px; font-weight:600; margin-left:6px; }
.comment-saved-badge { font-size:11px; color:#16a34a; }
.auto-save-comment { transition:all .3s ease; }

/* Cum Ave badge */
.cumave-badge {
    display:inline-block;
    background:#ede9fe;
    color:#5b21b6;
    padding:2px 10px;
    border-radius:12px;
    font-size:10px;
    font-weight:700;
    border:1px solid #c4b5fd;
}
</style>

<div class="main-content class-broadsheet">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Hero --}}
            <div class="principal-hero">
                <h1>
                    <i class="ri-chat-quote-line me-2"></i>
                    Principal's Comment & Class Broadsheet
                </h1>
                <p>
                    <i class="ri-school-line me-1"></i>
                    {{ $schoolclass->schoolclass }} {{ $schoolclass->arm_name ?? '' }} |
                    {{ $schooltermName }} {{ $schoolsession }}
                </p>
            </div>

            {{-- ── Scoring Mode Toggle Bar ── --}}
            <div class="scoring-mode-bar">
                <span class="mode-label">
                    <i class="ri-bar-chart-grouped-line me-1"></i>
                    Grading & Comment Mode:
                </span>
                <div class="scoring-mode-toggle">
                    <a href="{{ request()->fullUrlWithQuery(['scoring_mode' => 'cumulative']) }}"
                       class="mode-btn {{ $scoringMode === 'cumulative' ? 'active' : '' }}">
                        <i class="ri-bar-chart-line"></i> Cumulative
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['scoring_mode' => 'term']) }}"
                       class="mode-btn {{ $scoringMode === 'term' ? 'active-term' : '' }}">
                        <i class="ri-calendar-check-line"></i> Term
                    </a>
                    @if($hasMockData)
                    <a href="{{ request()->fullUrlWithQuery(['scoring_mode' => 'mock']) }}"
                       class="mode-btn {{ $scoringMode === 'mock' ? 'active-mock' : '' }}">
                        <i class="ri-file-list-3-line"></i> Mock
                    </a>
                    @else
                    <span class="mode-btn text-muted" style="cursor:not-allowed;opacity:.5;" title="No mock data available">
                        <i class="ri-file-list-3-line"></i> Mock
                    </span>
                    @endif
                </div>

                <span class="mode-hint">
                    @if($scoringMode === 'cumulative')
                        <i class="ri-information-line text-primary"></i>
                        Grades &amp; comments based on <strong>Cumulative (BF + Term avg)</strong>
                    @elseif($scoringMode === 'term')
                        <i class="ri-information-line text-info"></i>
                        Grades &amp; comments based on <strong>Term scores only</strong>
                    @else
                        <i class="ri-information-line" style="color:var(--mock-color)"></i>
                        Grades &amp; comments based on <strong>Mock exam scores</strong>
                    @endif
                </span>

                <span class="ms-auto
                    @if($scoringMode==='cumulative') principal-badge-mode-cum
                    @elseif($scoringMode==='term')   principal-badge-mode-term
                    @else                            principal-badge-mode-mock
                    @endif">
                    @if($scoringMode === 'cumulative')
                        <i class="ri-bar-chart-line me-1"></i> Cumulative Mode
                    @elseif($scoringMode === 'term')
                        <i class="ri-calendar-check-line me-1"></i> Term Mode
                    @else
                        <i class="ri-file-list-3-line me-1"></i> Mock Mode
                    @endif
                </span>
            </div>

            {{-- ── Grade Basis Toggle ── --}}
            <div class="scoring-mode-bar" style="border-left:4px solid #7c3aed;">
                <span class="mode-label">
                    <i class="ri-scales-3-line me-1"></i>
                    Grade Basis:
                </span>
                <div class="scoring-mode-toggle">
                    <a href="{{ request()->fullUrlWithQuery(['grade_basis' => 'cum_ave', 'scoring_mode' => $scoringMode]) }}"
                       class="mode-btn {{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'active-cumave' : '' }}">
                        <i class="ri-bar-chart-line"></i> Cumulative Average
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['grade_basis' => 'total', 'scoring_mode' => $scoringMode]) }}"
                       class="mode-btn {{ ($gradeBasis ?? 'cum_ave') === 'total' ? 'active-total' : '' }}">
                        <i class="ri-calendar-check-line"></i> Term Total
                    </a>
                </div>
                <span class="mode-hint">
                    <i class="ri-information-line text-primary"></i>
                    @if(($gradeBasis ?? 'cum_ave') === 'cum_ave')
                        Grades based on <strong>Cumulative Average</strong> (BF + Term ÷ term number)
                    @else
                        Grades based on <strong>Term Total</strong> scores only
                    @endif
                </span>
                <span class="ms-auto badge" style="background:{{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? '#7c3aed' : '#0891b2' }};color:#fff;font-size:11px;padding:6px 14px;">
                    @if(($gradeBasis ?? 'cum_ave') === 'cum_ave')
                        <i class="ri-bar-chart-line me-1"></i> Cum Ave
                    @else
                        <i class="ri-calendar-check-line me-1"></i> Term Total
                    @endif
                </span>
            </div>

            @if($scoringMode === 'mock')
            <div class="mock-notice-banner">
                <i class="ri-file-list-3-line fs-5"></i>
                <div>
                    <strong>Mock Mode Active:</strong>
                    Comments, grades, and analytics are all derived from mock examination scores.
                    Saving comments here will store them against the selected term.
                </div>
            </div>
            @endif

            {{-- Stat Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="ri-group-line"></i></div>
                        <div class="stat-value">{{ $students->count() }}</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="ri-book-open-line"></i></div>
                        <div class="stat-value text-primary">{{ count($subjects) }}</div>
                        <div class="stat-label">Subjects</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="ri-bar-chart-line"></i></div>
                        <div class="stat-value
                            @if($scoringMode==='term') text-info
                            @elseif($scoringMode==='mock') text-purple
                            @else text-success
                            @endif"
                            style="{{ $scoringMode==='mock' ? 'color:var(--mock-color)' : '' }}">
                            {{ $classAnalytics['average'] }}
                        </div>
                        <div class="stat-label">
                            Class Average
                            <span class="badge ms-1"
                                  style="font-size:.65rem; background:{{ $scoringMode==='term' ? '#0891b2' : ($scoringMode==='mock' ? '#7c3aed' : '#2563eb') }}; color:#fff;">
                                {{ ucfirst($scoringMode) }}
                            </span>
                            <span class="badge ms-1"
                                  style="font-size:.65rem; background:{{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? '#7c3aed' : '#0891b2' }}; color:#fff;">
                                {{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'Cum Ave' : 'Term Total' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="ri-award-line"></i></div>
                        <div class="stat-value text-warning" style="font-size:16px;">
                            @php
                                $topStudent = $students->sortByDesc(function($s) use ($scoringMode, $gradeBasis, $studentAnalytics) {
                                    return match($scoringMode) {
                                        'term' => $studentAnalytics[$s->id]['term_average'] ?? 0,
                                        'mock' => $studentAnalytics[$s->id]['mock_average'] ?? 0,
                                        default => ($gradeBasis ?? 'cum_ave') === 'total' 
                                            ? ($studentAnalytics[$s->id]['term_average'] ?? 0)
                                            : ($studentAnalytics[$s->id]['cum_ave_average'] ?? 0),
                                    };
                                })->first();
                            @endphp
                            @if($topStudent)
                                {{ $topStudent->fname }} {{ substr($topStudent->lastname, 0, 1) }}.
                            @else —
                            @endif
                        </div>
                        <div class="stat-label">Top Performer</div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @if ($students->isNotEmpty())
                <form id="commentsForm"
                      action="{{ route('myprincipalscomment.updateComments', [$schoolclassid, $sessionid, $termid]) }}"
                      method="POST">
                    @csrf

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h5 class="mb-0 fw-semibold" style="color:var(--principal-primary)">
                                    <i class="ri-bar-chart-2-line me-2"></i>
                                    Student Performance Dashboard
                                    @if($isSenior)
                                        <span class="principal-badge principal-badge-senior ms-2">Senior (A1–F9)</span>
                                    @else
                                        <span class="principal-badge principal-badge-junior ms-2">Junior (A–F)</span>
                                    @endif
                                    <span class="principal-badge principal-badge-cumave ms-1">
                                        <i class="ri-bar-chart-line me-1"></i> Cum Ave
                                    </span>
                                    <span class="principal-badge principal-badge-total ms-1">
                                        <i class="ri-calendar-check-line me-1"></i> Term Total
                                    </span>
                                </h5>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($scoringMode !== 'mock')
                                    <span class="principal-badge" style="background:#e0f2fe;color:#0891b2;">
                                        <i class="ri-calendar-check-line me-1"></i> Term Score
                                    </span>
                                    <span class="principal-badge" style="background:#dbeafe;color:#1e3a5f;">
                                        <i class="ri-bar-chart-line me-1"></i> BF
                                    </span>
                                    <span class="principal-badge" style="background:#ede9fe;color:#5b21b6;">
                                        <i class="ri-bar-chart-line me-1"></i> Cum Ave
                                    </span>
                                    <span class="principal-badge principal-badge-cumulative">
                                        <i class="ri-bar-chart-line me-1"></i> Cumulative
                                    </span>
                                    @else
                                    <span class="principal-badge" style="background:var(--mock-light);color:var(--mock-color);">
                                        <i class="ri-file-list-3-line me-1"></i> Mock Score
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            {{-- Search --}}
                            <div class="search-box mb-4">
                                <input type="text" class="form-control" id="searchInput"
                                       placeholder="🔍 Search students by name or admission number…"
                                       style="border:1.5px solid var(--principal-border); border-radius:8px;">
                            </div>

                            {{-- ============================================================
                                 DESKTOP TABLE
                            ============================================================ --}}
                            <div class="desktop-table">
                                <div class="table-responsive">
                                    <table class="table principal-table w-100 mb-0">
                                        <thead>
                                            <tr>
                                                <th width="40">#</th>
                                                <th width="60">Photo</th>
                                                <th width="100">Adm. No</th>
                                                <th width="180">Student Name</th>
                                                <th width="70">Gender</th>
                                                <th class="text-center">
                                                    <i class="ri-book-open-line me-1"></i>
                                                    Subject Performance
                                                    <small class="d-block fw-normal opacity-75" style="font-size:.6rem;margin-top:2px;">
                                                        @if($scoringMode !== 'mock')
                                                            <span style="color:#90cdf4;">■ Term</span> &nbsp;
                                                            <span style="color:#c4b5fd;">■ Cum Ave</span> &nbsp;
                                                            <span style="color:#bfdbfe;">■ Cum</span>
                                                        @else
                                                            <span style="color:#c4b5fd;">■ Mock Score</span>
                                                        @endif
                                                    </small>
                                                </th>
                                                <th width="310">Principal's Comment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($students as $index => $student)
                                                @php
                                                    $sid = $student->id;
                                                    $avatarUrl = null;
                                                    if(isset($student->picture) && $student->picture && $student->picture != 'unnamed.jpg') {
                                                        $avatarUrl = asset('storage/student_avatars/' . $student->picture);
                                                    }
                                                    $fullName = trim($student->lastname . ' ' . $student->fname);
                                                    $otherName = $student->othername ?? '';
                                                    $fullNameWithOther = trim($fullName . ($otherName ? ' (' . $otherName . ')' : ''));
                                                    $initials = strtoupper(substr($student->fname, 0, 1) . substr($student->lastname, 0, 1));
                                                    if(empty($initials)) $initials = 'ST';

                                                    $currentComment      = $profiles[$sid] ?? '';
                                                    $currentCommentPlain = strip_tags($currentComment);
                                                    $intelligentComment  = $intelligentComments[$sid] ?? '';
                                                    $hasWeakAdvice       = !empty($studentGradeAnalysis[$sid]['weak_subjects'] ?? []);
                                                    $analytics           = $studentAnalytics[$sid] ?? [];
                                                    $basisLabel = ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'Cum Ave' : 'Term Total';
                                                    $cumAveAvg = $analytics['cum_ave_average'] ?? 0;
                                                    $cumAvePct = $analytics['cum_ave_percentage'] ?? 0;
                                                @endphp
                                                <tr data-student-id="{{ $sid }}" class="student-row">
                                                    <td class="fw-bold">{{ $index + 1 }}</td>

                                                    {{-- Photo --}}
                                                    <td class="text-center">
                                                        @if($avatarUrl)
                                                            <img src="{{ $avatarUrl }}" alt="{{ $fullNameWithOther }}"
                                                                 class="student-avatar avatar-clickable"
                                                                 data-bs-toggle="modal" data-bs-target="#imageZoomModal"
                                                                 data-image="{{ $avatarUrl }}"
                                                                 data-name="{{ $fullNameWithOther }}"
                                                                 data-admission="{{ $student->admissionNo ?? 'N/A' }}"
                                                                 data-class="{{ $schoolclass->schoolclass }} {{ $schoolclass->arm_name ?? '' }}"
                                                                 data-gender="{{ $student->gender ?? 'N/A' }}">
                                                        @else
                                                            <div class="avatar-placeholder avatar-clickable"
                                                                 data-bs-toggle="modal" data-bs-target="#imageZoomModal"
                                                                 data-name="{{ $fullNameWithOther }}"
                                                                 data-admission="{{ $student->admissionNo ?? 'N/A' }}"
                                                                 data-class="{{ $schoolclass->schoolclass }} {{ $schoolclass->arm_name ?? '' }}"
                                                                 data-gender="{{ $student->gender ?? 'N/A' }}"
                                                                 data-initials="{{ $initials }}">
                                                                {{ $initials }}
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td>{{ $student->admissionNo }}</td>
                                                    <td>
                                                        <strong>{{ $fullNameWithOther }}</strong>
                                                        <span class="class-arm-badge">{{ $schoolclass->schoolclass }} {{ $schoolclass->arm_name ?? '' }}</span>
                                                        @if($currentComment)
                                                            <small class="comment-saved-badge d-block mt-1">
                                                                <i class="ri-check-double-line"></i> Comment Saved
                                                            </small>
                                                        @endif
                                                        <small class="d-block mt-1">
                                                            <span class="cumave-badge">
                                                                <i class="ri-bar-chart-line me-1"></i> {{ $basisLabel }}: {{ number_format($cumAveAvg, 1) }}%
                                                            </span>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark">
                                                            <i class="ri-{{ $student->gender === 'Male' ? 'male' : 'female' }}-line"></i>
                                                            {{ $student->gender ?? 'N/A' }}
                                                        </span>
                                                    </td>

                                                    {{-- ── Subject Scores ── --}}
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @foreach ($subjects as $subject)
                                                                @php
                                                                    $termTotal = $termScoreMap[$sid][$subject] ?? 0;
                                                                    $cumTotal  = $cumScoreMap[$sid][$subject]  ?? 0;
                                                                    $cumAve    = $cumAveMap[$sid][$subject]    ?? 0;
                                                                    $bf        = $bfMap[$sid][$subject]        ?? 0;
                                                                    $mockTotal = $mockScoreMap[$sid][$subject] ?? 0;

                                                                    $inlineGrade = function($val) use ($isSenior) {
                                                                        if ($val <= 0) return [null, null];
                                                                        if ($isSenior) {
                                                                            if ($val >= 75) return ['A1','a1'];
                                                                            if ($val >= 70) return ['B2','b2'];
                                                                            if ($val >= 65) return ['B3','b3'];
                                                                            if ($val >= 60) return ['C4','c4'];
                                                                            if ($val >= 55) return ['C5','c5'];
                                                                            if ($val >= 50) return ['C6','c6'];
                                                                            if ($val >= 45) return ['D7','d7'];
                                                                            if ($val >= 40) return ['E8','e8'];
                                                                            return ['F9','f9'];
                                                                        }
                                                                        if ($val >= 70) return ['A','a'];
                                                                        if ($val >= 60) return ['B','b'];
                                                                        if ($val >= 50) return ['C','c'];
                                                                        if ($val >= 40) return ['D','d'];
                                                                        return ['F','f'];
                                                                    };

                                                                    [$tGrade,$tGL]   = $inlineGrade($termTotal);
                                                                    [$cGrade,$cGL]   = $inlineGrade($cumTotal);
                                                                    [$caGrade,$caGL] = $inlineGrade($cumAve);
                                                                    [$mGrade,$mGL]   = $inlineGrade($mockTotal);

                                                                    $tColor = $termTotal < 40 ? 'highlight-red' : ($termTotal < 50 ? 'highlight-orange' : ($termTotal >= 70 ? 'highlight-green' : ''));
                                                                    $cColor = $cumTotal  < 40 ? 'highlight-red' : ($cumTotal  < 50 ? 'highlight-orange' : ($cumTotal  >= 70 ? 'highlight-green' : ''));
                                                                    $caColor = $cumAve < 40 ? 'highlight-red' : ($cumAve < 50 ? 'highlight-orange' : ($cumAve >= 70 ? 'highlight-purple' : ''));
                                                                    $mColor = $mockTotal < 40 ? 'highlight-red' : ($mockTotal < 50 ? 'highlight-orange' : ($mockTotal >= 70 ? 'highlight-purple' : ''));
                                                                @endphp
                                                                <div class="subject-score-card mode-cumave">
                                                                    <div class="score-subject-name">{{ $subject }}</div>

                                                                    @if($scoringMode !== 'mock')
                                                                    {{-- Term row --}}
                                                                    <div class="score-row-term">
                                                                        <div class="score-type-label">
                                                                            <i class="ri-calendar-check-line"></i> Term
                                                                        </div>
                                                                        <div>
                                                                            <span class="score-value {{ $tColor }}">{{ $termTotal ?: '—' }}</span>
                                                                            @if($tGrade)<span class="score-grade-badge grade-{{ $tGL }}">{{ $tGrade }}</span>@endif
                                                                        </div>
                                                                    </div>
                                                                    {{-- BF row --}}
                                                                    @if($bf > 0)
                                                                    <div style="text-align:left;padding:2px 5px;">
                                                                        <span class="bf-value"><i class="ri-arrow-left-right-line"></i> BF: {{ $bf }}</span>
                                                                    </div>
                                                                    @endif
                                                                    {{-- Cum Ave row --}}
                                                                    <div class="score-row-cumave">
                                                                        <div class="score-type-label">
                                                                            <i class="ri-bar-chart-line"></i> Cum Ave
                                                                            @if(($gradeBasis ?? 'cum_ave') === 'cum_ave') <span style="color:#ea580c;font-size:.55rem;">★</span> @endif
                                                                        </div>
                                                                        <div>
                                                                            <span class="score-value {{ $caColor }}" style="color:{{ $cumAve >= 70 ? '#7c3aed' : ($cumAve >= 50 ? '#7c3aed' : '#dc2626') }}">{{ $cumAve ?: '—' }}</span>
                                                                            @if($caGrade)<span class="score-grade-badge grade-{{ $caGL }}" style="background:#7c3aed;color:#fff;">{{ $caGrade }}</span>@endif
                                                                        </div>
                                                                    </div>
                                                                    {{-- Cum row --}}
                                                                    <div class="score-row-cum">
                                                                        <div class="score-type-label">
                                                                            <i class="ri-bar-chart-line"></i> Cum
                                                                        </div>
                                                                        <div>
                                                                            <span class="score-value {{ $cColor }}">{{ $cumTotal ?: '—' }}</span>
                                                                            @if($cGrade)<span class="score-grade-badge grade-{{ $cGL }}">{{ $cGrade }}</span>@endif
                                                                        </div>
                                                                    </div>
                                                                    @else
                                                                    {{-- Mock row --}}
                                                                    <div class="score-row-mock">
                                                                        <div class="score-type-label">
                                                                            <i class="ri-file-list-3-line"></i> Mock
                                                                        </div>
                                                                        <div>
                                                                            <span class="score-value {{ $mColor }}">{{ $mockTotal ?: '—' }}</span>
                                                                            @if($mGrade)<span class="score-grade-badge grade-{{ $mGL }}">{{ $mGrade }}</span>@endif
                                                                        </div>
                                                                    </div>
                                                                    @if($mockPositionMap[$sid][$subject] ?? null)
                                                                    <div style="text-align:center;font-size:.6rem;color:var(--mock-color);margin-top:2px;">
                                                                        Pos: {{ $mockPositionMap[$sid][$subject] }}
                                                                    </div>
                                                                    @endif
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>

                                                    {{-- Comment cell --}}
                                                    <td class="comment-cell">
                                                        @if($intelligentComment)
                                                            <div class="intelligent-comment-section mb-2">
                                                                <small class="text-muted d-block mb-1">
                                                                    <i class="ri-lightbulb-line text-success"></i>
                                                                    <strong>AI Suggested Comment</strong>
                                                                    @if($hasWeakAdvice)
                                                                        <span class="badge bg-warning ms-1">Includes Advice</span>
                                                                    @endif
                                                                    <span class="badge ms-1"
                                                                          style="font-size:.6rem;background:{{ $scoringMode==='mock' ? '#7c3aed' : ($scoringMode==='term' ? '#0891b2' : '#2563eb') }};color:#fff;">
                                                                        Based on {{ ucfirst($scoringMode) }}
                                                                    </span>
                                                                    <span class="badge ms-1"
                                                                          style="font-size:.6rem;background:{{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? '#7c3aed' : '#0891b2' }};color:#fff;">
                                                                        {{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'Cum Ave' : 'Term Total' }}
                                                                    </span>
                                                                </small>
                                                                <div class="intelligent-comment-preview">
                                                                    <div class="intelligent-comment-text small">
                                                                        {!! nl2br(e(\Illuminate\Support\Str::limit($intelligentComment, 120))) !!}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if($currentComment)
                                                            <div class="mb-2">
                                                                <small class="text-success d-block mb-1">
                                                                    <i class="ri-chat-check-line"></i> <strong>Saved Comment</strong>
                                                                </small>
                                                                <div class="saved-comment-preview">
                                                                    <small class="text-secondary">
                                                                        {!! nl2br(e(\Illuminate\Support\Str::limit($currentCommentPlain, 100))) !!}
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <select class="form-select teacher-comment-dropdown auto-save-comment"
                                                                name="teacher_comments[{{ $sid }}]"
                                                                data-student-id="{{ $sid }}"
                                                                data-original-value="{{ $currentCommentPlain }}">
                                                            <option value="">-- Select a Comment --</option>
                                                            @foreach ($standardPersonalizedComments[$sid] ?? [] as $opt)
                                                                @php $plain = strip_tags($opt); @endphp
                                                                <option value="{{ $plain }}"
                                                                    {{ $currentCommentPlain === $plain ? 'selected' : '' }}>
                                                                    {{ \Illuminate\Support\Str::limit($plain, 70) }}
                                                                </option>
                                                            @endforeach
                                                            @php
                                                                $intPlain  = strip_tags($intelligentComments[$sid] ?? '');
                                                                $stdPlains = array_map('strip_tags', $standardPersonalizedComments[$sid] ?? []);
                                                                $showAI    = $intPlain && !in_array($intPlain, $stdPlains);
                                                            @endphp
                                                            @if($showAI)
                                                                <option value="{{ $intPlain }}"
                                                                        style="background-color:#e8f5e8!important;font-weight:600!important;color:#155724!important;"
                                                                    {{ $currentCommentPlain === $intPlain ? 'selected' : '' }}>
                                                                    💡 Use AI Generated Comment
                                                                </option>
                                                            @endif
                                                        </select>

                                                        <button type="button"
                                                                class="comment-info-icon grades-trigger"
                                                                data-student-id="{{ $sid }}"
                                                                data-student-name="{{ $fullNameWithOther }}">
                                                            <i class="ri-eye-line"></i>
                                                        </button>

                                                        {{-- Grades tooltip --}}
                                                        <div class="grades-tooltip position-bottom" id="tooltip-{{ $sid }}">
                                                            <div class="tooltip-header">
                                                                <i class="ri-bar-chart-line me-2"></i>
                                                                <span id="tooltip-title-{{ $sid }}">Performance Details</span>
                                                                <button type="button" class="tooltip-close"><i class="ri-close-line"></i></button>
                                                            </div>
                                                            <div class="tooltip-body">
                                                                <div class="row mb-2 g-2">
                                                                    @if($scoringMode !== 'mock')
                                                                    <div class="col-3">
                                                                        <div class="stat-card" style="border:2px solid #0891b2;padding:10px;">
                                                                            <small class="text-info fw-bold"><i class="ri-calendar-check-line"></i> Term</small>
                                                                            <h4 class="mb-0">{{ $analytics['term_total'] ?? 0 }}</h4>
                                                                            <small class="text-muted">Avg: {{ $analytics['term_average'] ?? 0 }}</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <div class="stat-card" style="border:2px solid #7c3aed;padding:10px;">
                                                                            <small style="color:#7c3aed;" class="fw-bold"><i class="ri-bar-chart-line"></i> Cum Ave</small>
                                                                            <h4 class="mb-0" style="color:#7c3aed;">{{ $analytics['cum_ave_average'] ?? 0 }}</h4>
                                                                            <small class="text-muted">Avg: {{ $analytics['cum_ave_average'] ?? 0 }}</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <div class="stat-card" style="border:2px solid var(--principal-primary);padding:10px;">
                                                                            <small style="color:var(--principal-primary);" class="fw-bold"><i class="ri-bar-chart-line"></i> Cum</small>
                                                                            <h4 class="mb-0">{{ $analytics['total_score'] ?? 0 }}</h4>
                                                                            <small class="text-muted">Avg: {{ $analytics['average'] ?? 0 }}</small>
                                                                        </div>
                                                                    </div>
                                                                    @else
                                                                    <div class="col-4">
                                                                        <div class="stat-card" style="border:2px solid var(--mock-color);padding:10px;">
                                                                            <small style="color:var(--mock-color);" class="fw-bold"><i class="ri-file-list-3-line"></i> Mock</small>
                                                                            <h4 class="mb-0">{{ $analytics['mock_total'] ?? 0 }}</h4>
                                                                            <small class="text-muted">Avg: {{ $analytics['mock_average'] ?? 0 }}</small>
                                                                        </div>
                                                                    </div>
                                                                    @endif
                                                                    <div class="{{ $scoringMode !== 'mock' ? 'col-3' : 'col-4' }}">
                                                                        <div class="stat-card" style="padding:10px;">
                                                                            <small>Position</small>
                                                                            <strong class="d-block text-primary fs-5">{{ $analytics['position_text'] ?? '—' }}</strong>
                                                                            <small class="text-muted">of {{ $classAnalytics['total_students'] }}</small>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="text-center mb-3 p-2 bg-light rounded">
                                                                    <small class="text-muted">
                                                                        Class Avg ({{ ucfirst($scoringMode) }}):
                                                                        <strong>{{ $classAnalytics['average'] }}</strong>
                                                                    </small>
                                                                    <span class="ms-2 badge" style="background:#7c3aed;color:#fff;">
                                                                        {{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'Cum Ave' : 'Term Total' }}
                                                                    </span>
                                                                    @php
                                                                        $myAvg = match($scoringMode) {
                                                                            'term' => $analytics['term_average'] ?? 0,
                                                                            'mock' => $analytics['mock_average'] ?? 0,
                                                                            default => ($gradeBasis ?? 'cum_ave') === 'total' 
                                                                                ? ($analytics['term_average'] ?? 0)
                                                                                : ($analytics['cum_ave_average'] ?? 0),
                                                                        };
                                                                        $diff = $myAvg - $classAnalytics['average'];
                                                                    @endphp
                                                                    @if($diff > 0.5)
                                                                        <span class="text-success ms-2"><i class="ri-arrow-up-line"></i> +{{ round($diff,1) }}</span>
                                                                    @elseif($diff < -0.5)
                                                                        <span class="text-danger ms-2"><i class="ri-arrow-down-line"></i> {{ round($diff,1) }}</span>
                                                                    @endif
                                                                </div>

                                                                {{-- Detailed grade table --}}
                                                                <table class="table table-sm table-hover">
                                                                    <thead>
                                                                        <tr>
                                                                            <th class="tooltip-grade-header">Subject</th>
                                                                            @if($scoringMode !== 'mock')
                                                                            <th class="tooltip-grade-header tooltip-col-term text-center">Term</th>
                                                                            <th class="tooltip-grade-header tooltip-col-term text-center">T.Grade</th>
                                                                            <th class="tooltip-grade-header tooltip-col-cumave text-center" style="color:#7c3aed;">Cum Ave</th>
                                                                            <th class="tooltip-grade-header tooltip-col-cumave text-center" style="color:#7c3aed;">CA.Grade</th>
                                                                            <th class="tooltip-grade-header tooltip-col-pos text-center" title="BF (Brought Forward)">BF</th>
                                                                            <th class="tooltip-grade-header tooltip-col-cum text-center">Cum</th>
                                                                            <th class="tooltip-grade-header tooltip-col-cum text-center">C.Grade</th>
                                                                            <th class="tooltip-grade-header tooltip-col-pos text-center" title="Class Pos by Cum">Cl.Pos(C)</th>
                                                                            <th class="tooltip-grade-header tooltip-col-pos text-center" title="Class Pos by Total">Cl.Pos(T)</th>
                                                                            <th class="tooltip-grade-header tooltip-col-pos text-center" title="Arm Pos by Cum">Arm(C)</th>
                                                                            <th class="tooltip-grade-header tooltip-col-pos text-center" title="Arm Pos by Total">Arm(T)</th>
                                                                            @else
                                                                            <th class="tooltip-grade-header tooltip-col-mock text-center">Mock</th>
                                                                            <th class="tooltip-grade-header tooltip-col-mock text-center">Grade</th>
                                                                            <th class="tooltip-grade-header tooltip-col-pos text-center">Class Pos</th>
                                                                            @endif
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody id="grades-body-{{ $sid }}"></tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- ============================================================
                                 MOBILE CARDS
                            ============================================================ --}}
                            <div class="mobile-cards">
                                @foreach ($students as $student)
                                    @php
                                        $sid = $student->id;
                                        $avatarUrl = null;
                                        if(isset($student->picture) && $student->picture && $student->picture != 'unnamed.jpg') {
                                            $avatarUrl = asset('storage/student_avatars/' . $student->picture);
                                        }
                                        $fullName = trim($student->lastname . ' ' . $student->fname);
                                        $otherName = $student->othername ?? '';
                                        $fullNameWithOther = trim($fullName . ($otherName ? ' (' . $otherName . ')' : ''));
                                        $initials = strtoupper(substr($student->fname, 0, 1) . substr($student->lastname, 0, 1));
                                        if(empty($initials)) $initials = 'ST';

                                        $currentComment      = $profiles[$sid] ?? '';
                                        $currentCommentPlain = strip_tags($currentComment);
                                        $intelligentComment  = $intelligentComments[$sid] ?? '';
                                        $analytics           = $studentAnalytics[$sid] ?? [];
                                        $basisLabel = ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'Cum Ave' : 'Term Total';
                                        $cumAveAvg = $analytics['cum_ave_average'] ?? 0;
                                    @endphp
                                    <div class="student-card" data-student-id="{{ $sid }}">
                                        <div class="student-header">
                                            <div class="student-info">
                                                @if($avatarUrl)
                                                    <img src="{{ $avatarUrl }}" alt="{{ $fullNameWithOther }}"
                                                         class="avatar-sm avatar-clickable"
                                                         data-bs-toggle="modal" data-bs-target="#imageZoomModal"
                                                         data-image="{{ $avatarUrl }}"
                                                         data-name="{{ $fullNameWithOther }}"
                                                         data-admission="{{ $student->admissionNo ?? 'N/A' }}"
                                                         data-class="{{ $schoolclass->schoolclass }} {{ $schoolclass->arm_name ?? '' }}"
                                                         data-gender="{{ $student->gender ?? 'N/A' }}">
                                                @else
                                                    <div class="avatar-placeholder avatar-clickable"
                                                         style="width:45px;height:45px;font-size:16px;"
                                                         data-bs-toggle="modal" data-bs-target="#imageZoomModal"
                                                         data-name="{{ $fullNameWithOther }}"
                                                         data-admission="{{ $student->admissionNo ?? 'N/A' }}"
                                                         data-class="{{ $schoolclass->schoolclass }} {{ $schoolclass->arm_name ?? '' }}"
                                                         data-gender="{{ $student->gender ?? 'N/A' }}"
                                                         data-initials="{{ $initials }}">
                                                        {{ $initials }}
                                                    </div>
                                                @endif
                                                <div class="student-details">
                                                    <h6>
                                                        {{ $fullNameWithOther }}
                                                        @if($currentComment)
                                                            <span class="badge bg-success ms-2">✓</span>
                                                        @endif
                                                    </h6>
                                                    <div class="student-meta">
                                                        <i class="ri-id-card-line"></i> {{ $student->admissionNo }} |
                                                        <i class="ri-user-line"></i> {{ $student->gender ?? 'N/A' }}
                                                        <span class="badge ms-1" style="background:#7c3aed;color:#fff;font-size:9px;">
                                                            {{ $basisLabel }}: {{ number_format($cumAveAvg, 1) }}%
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="student-body">
                                            <div class="performance-summary {{ $scoringMode === 'mock' ? 'mock-mode' : (($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'cumave-mode' : 'total-mode') }}">
                                                <div class="summary-title">
                                                    <i class="ri-bar-chart-line"></i>
                                                    Performance Summary
                                                    <span class="badge bg-light text-dark ms-auto" style="font-size:.65rem;">
                                                        {{ ucfirst($scoringMode) }} / {{ $basisLabel }}
                                                    </span>
                                                </div>
                                                <div class="summary-grid">
                                                    @if($scoringMode === 'mock')
                                                    <div class="summary-item">
                                                        <div class="summary-label">Mock Total</div>
                                                        <div class="summary-value">{{ $analytics['mock_total'] ?? 0 }}</div>
                                                    </div>
                                                    <div class="summary-item">
                                                        <div class="summary-label">Mock Avg</div>
                                                        <div class="summary-value">{{ $analytics['mock_average'] ?? 0 }}</div>
                                                    </div>
                                                    @else
                                                    <div class="summary-item">
                                                        <div class="summary-label">Term Avg</div>
                                                        <div class="summary-value">{{ $analytics['term_average'] ?? 0 }}</div>
                                                    </div>
                                                    <div class="summary-item" style="background:rgba(124,58,237,.3);">
                                                        <div class="summary-label" style="color:#c4b5fd;">Cum Ave ★</div>
                                                        <div class="summary-value" style="color:#c4b5fd;">{{ $analytics['cum_ave_average'] ?? 0 }}</div>
                                                    </div>
                                                    @endif
                                                    <div class="summary-item">
                                                        <div class="summary-label">Position</div>
                                                        <div class="summary-value">{{ $analytics['position_text'] ?? '—' }}</div>
                                                    </div>
                                                    <div class="summary-item">
                                                        <div class="summary-label">Subjects</div>
                                                        <div class="summary-value">{{ $analytics['subjects'] ?? 0 }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Mobile subject grid --}}
                                            <div class="subjects-grid">
                                                @foreach ($subjects as $subject)
                                                    @php
                                                        $termTotal = $termScoreMap[$sid][$subject] ?? 0;
                                                        $cumAve    = $cumAveMap[$sid][$subject]    ?? 0;
                                                        $cumTotal  = $cumScoreMap[$sid][$subject]  ?? 0;
                                                        $bf        = $bfMap[$sid][$subject]        ?? 0;
                                                        $mockTotal = $mockScoreMap[$sid][$subject] ?? 0;
                                                        $displayScore = ($gradeBasis ?? 'cum_ave') === 'total' ? $termTotal : $cumAve;
                                                        $displayColor = $displayScore < 40 ? 'text-danger' : ($displayScore < 50 ? 'text-warning' : (($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'text-purple' : 'text-success'));
                                                    @endphp
                                                    <div class="subject-item">
                                                        <div class="subject-name">{{ $subject }}</div>
                                                        @if($scoringMode !== 'mock')
                                                        <div class="score-row-term mb-1" style="border-radius:5px;padding:3px 4px;">
                                                            <div style="font-size:.55rem;font-weight:700;color:#0891b2;text-transform:uppercase;">Term</div>
                                                            <span class="fw-bold {{ $termTotal < 50 ? 'text-danger' : 'text-success' }}" style="font-size:.85rem;">{{ $termTotal ?: '—' }}</span>
                                                        </div>
                                                        @if($bf > 0)
                                                        <div style="font-size:.55rem;color:var(--principal-muted);text-align:left;padding:0 4px;">BF: {{ $bf }}</div>
                                                        @endif
                                                        <div class="score-row-cumave" style="border-radius:5px;padding:3px 4px;border-left:3px solid #7c3aed;background:rgba(124,58,237,.07);">
                                                            <div style="font-size:.55rem;font-weight:700;color:#7c3aed;text-transform:uppercase;">Cum Ave</div>
                                                            <span class="fw-bold {{ $cumAve < 50 ? 'text-danger' : 'text-success' }}" style="font-size:.85rem;color:{{ $cumAve >= 50 ? '#7c3aed' : '' }}">{{ $cumAve ?: '—' }}</span>
                                                            @if(($gradeBasis ?? 'cum_ave') === 'cum_ave')<span style="font-size:.6rem;color:#ea580c;">★</span>@endif
                                                        </div>
                                                        @else
                                                        <div class="score-row-mock" style="border-radius:5px;padding:3px 4px;">
                                                            <div style="font-size:.55rem;font-weight:700;color:var(--mock-color);text-transform:uppercase;">Mock</div>
                                                            <span class="fw-bold {{ $mockTotal < 50 ? 'text-danger' : '' }}" style="font-size:.85rem;color:{{ $mockTotal >= 50 ? 'var(--mock-color)' : '' }}">{{ $mockTotal ?: '—' }}</span>
                                                        </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if($intelligentComment)
                                                <div class="intelligent-comment-section mb-2">
                                                    <small>
                                                        <i class="ri-lightbulb-line text-success"></i>
                                                        <strong>AI Suggestion</strong>
                                                        <span class="badge ms-1" style="font-size:.6rem;background:{{ $scoringMode==='mock' ? '#7c3aed' : ($scoringMode==='term' ? '#0891b2' : '#2563eb') }};color:#fff;">
                                                            {{ ucfirst($scoringMode) }}
                                                        </span>
                                                        <span class="badge ms-1" style="font-size:.6rem;background:{{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? '#7c3aed' : '#0891b2' }};color:#fff;">
                                                            {{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'Cum Ave' : 'Term Total' }}
                                                        </span>
                                                    </small>
                                                    <div class="small mt-1 text-muted">{{ \Illuminate\Support\Str::limit($intelligentComment, 100) }}</div>
                                                </div>
                                            @endif

                                            <div class="comment-section-mobile">
                                                <label class="form-label mb-2"><i class="ri-chat-3-line"></i> Principal's Comment</label>
                                                <select class="form-select auto-save-comment"
                                                        name="teacher_comments[{{ $sid }}]"
                                                        data-student-id="{{ $sid }}"
                                                        data-original-value="{{ $currentCommentPlain }}">
                                                    <option value="">-- Select Comment --</option>
                                                    @foreach ($standardPersonalizedComments[$sid] ?? [] as $opt)
                                                        @php $plain = strip_tags($opt); @endphp
                                                        <option value="{{ $plain }}" {{ $currentCommentPlain === $plain ? 'selected' : '' }}>
                                                            {{ \Illuminate\Support\Str::limit($plain, 50) }}
                                                        </option>
                                                    @endforeach
                                                    @php
                                                        $intPlain  = strip_tags($intelligentComments[$sid] ?? '');
                                                        $stdPlains = array_map('strip_tags', $standardPersonalizedComments[$sid] ?? []);
                                                    @endphp
                                                    @if($intPlain && !in_array($intPlain, $stdPlains))
                                                        <option value="{{ $intPlain }}"
                                                                style="background-color:#e8f5e8!important;font-weight:600!important;"
                                                            {{ $currentCommentPlain === $intPlain ? 'selected' : '' }}>
                                                            💡 Use AI Generated Comment
                                                        </option>
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Save all --}}
                            <div class="row mt-4">
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary" id="saveAllBtn"
                                            style="padding:10px 24px; font-weight:600; border-radius:8px;">
                                        <i class="ri-save-line me-1"></i> Save All Comments
                                    </button>
                                    <span id="savingIndicator" class="ms-2 text-muted" style="display:none;">
                                        <i class="ri-loader-4-line spin-icon me-1"></i> Saving…
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="ri-user-unfollow-line" style="font-size:80px;color:#ccc;"></i>
                        <h5 class="mt-4 text-muted">No Students Enrolled</h5>
                        <p class="text-muted mb-0">No students are enrolled in this class for the selected session and term.</p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

{{-- IMAGE ZOOM MODAL --}}
<div class="modal fade image-zoom-modal" id="imageZoomModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body text-center">
                <img id="zoomedImage" src="" alt="Student Photo" class="zoomed-image">
                <div class="zoomed-image-name" id="zoomedImageName"></div>
                <div class="zoomed-image-details" id="zoomedImageDetails"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Pass PHP data to JS ──────────────────────────────────────────────────────
window.studentGradesData = @json($studentGrades);
window.termScoreMap      = @json($termScoreMap);
window.cumScoreMap       = @json($cumScoreMap);
window.cumAveMap         = @json($cumAveMap);
window.bfMap             = @json($bfMap);
window.mockScoreMap      = @json($mockScoreMap);
window.mockPositionMap   = @json($mockPositionMap);
window.posClassCumMap    = @json($posClassCumMap);
window.posClassTotalMap  = @json($posClassTotalMap);
window.posArmTotalMap    = @json($posArmTotalMap);
window.posArmCumMap      = @json($posArmCumMap);
window.currentScoringMode = '{{ $scoringMode }}';
window.currentGradeBasis  = '{{ $gradeBasis ?? 'cum_ave' }}';

let activeTooltip = null;

// ── Toast ────────────────────────────────────────────────────────────────────
function showToast(message, type = 'info') {
    document.querySelectorAll('.auto-save-toast').forEach(t => t.remove());
    const toast = document.createElement('div');
    toast.className = `auto-save-toast alert alert-${type} alert-dismissible fade show shadow`;
    toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:99999;min-width:360px;';
    toast.innerHTML = `
        <div class="d-flex align-items-start">
            <i class="ri-${type === 'success' ? 'checkbox-circle' : type === 'danger' ? 'error-warning' : 'information'}-fill me-2 fs-4 mt-1"></i>
            <div>${message}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4500);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// ── Tooltip helpers ──────────────────────────────────────────────────────────
function closeAllTooltips() {
    document.querySelectorAll('.grades-tooltip.show').forEach(t => t.classList.remove('show'));
    activeTooltip = null;
}

function getGradeClass(grade) {
    const g = (grade || '').toLowerCase().replace(/\s+/g, '');
    const map = { a1:'grade-a1', b2:'grade-b2', b3:'grade-b3', c4:'grade-c4', c5:'grade-c5', c6:'grade-c6', d7:'grade-d7', e8:'grade-e8', f9:'grade-f9', a:'grade-a', b:'grade-b', c:'grade-c', d:'grade-d', f:'grade-f' };
    return map[g] || 'bg-secondary text-white';
}

function showTooltip(tooltipId, studentId, studentName) {
    const tooltip = document.getElementById(tooltipId);
    if (!tooltip) return;
    closeAllTooltips();

    const titleEl = document.getElementById(`tooltip-title-${studentId}`);
    if (titleEl) titleEl.textContent = `${studentName}'s Performance`;

    const grades = window.studentGradesData[studentId] || [];
    const tbody  = document.getElementById(`grades-body-${studentId}`);
    const mode   = window.currentScoringMode;
    const basis  = window.currentGradeBasis;

    if (tbody) {
        tbody.innerHTML = '';
        if (!grades.length) {
            tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted">No grades available</td></tr>';
        } else {
            grades.forEach(g => {
                const row = document.createElement('tr');
                const sid = studentId;

                if (mode === 'mock') {
                    const mockScore = (window.mockScoreMap[sid] || {})[g.subject] || 0;
                    const mockPos   = (window.mockPositionMap[sid] || {})[g.subject] || '—';
                    const mGradeClass = getGradeClass(g.mock_grade);
                    const mColor = mockScore < 40 ? 'text-danger' : (mockScore < 50 ? 'text-warning' : 'text-success');
                    row.innerHTML = `
                        <td><strong>${escapeHtml(g.subject)}</strong></td>
                        <td class="text-center fw-bold ${mColor}" style="color:${mockScore>0&&mockScore>=50?'var(--mock-color)':''}">
                            ${mockScore || '—'}
                        </td>
                        <td class="text-center">
                            ${g.mock_grade ? `<span class="score-grade-badge ${mGradeClass}">${escapeHtml(g.mock_grade)}</span>` : '<span class="text-muted">—</span>'}
                        </td>
                        <td class="text-center text-muted">${mockPos}</td>
                    `;
                } else {
                    const termScore = g.term_score || 0;
                    const cumScore  = g.cum_score  || 0;
                    const cumAve    = g.cum_ave_score || 0;
                    const bf        = (window.bfMap[sid] || {})[g.subject] || 0;
                    const posCC     = (window.posClassCumMap[sid]   || {})[g.subject] || '—';
                    const posCT     = (window.posClassTotalMap[sid] || {})[g.subject] || '—';
                    const posAT     = (window.posArmTotalMap[sid]   || {})[g.subject] || '—';
                    const posAC     = (window.posArmCumMap[sid]     || {})[g.subject] || '—';

                    const tColor = termScore < 40 ? 'text-danger' : (termScore < 50 ? 'text-warning' : 'text-success');
                    const caColor = cumAve < 40 ? 'text-danger' : (cumAve < 50 ? 'text-warning' : '');
                    const cColor = cumScore < 40 ? 'text-danger' : (cumScore < 50 ? 'text-warning' : '');
                    const tGC    = getGradeClass(g.term_grade);
                    const caGC   = getGradeClass(g.cum_ave_grade);
                    const cGC    = getGradeClass(g.cum_grade);

                    const isCumAveActive = basis === 'cum_ave';
                    const isTotalActive = basis === 'total';

                    row.innerHTML = `
                        <td><strong>${escapeHtml(g.subject)}</strong></td>
                        <td class="text-center fw-bold ${tColor}" style="color:#0891b2!important;${isTotalActive ? 'background:rgba(8,145,178,.1);border-radius:4px;' : ''}">
                            ${termScore || '—'}
                            ${isTotalActive ? '★' : ''}
                        </td>
                        <td class="text-center">
                            ${g.term_grade ? `<span class="score-grade-badge ${tGC}">${escapeHtml(g.term_grade)}</span>` : '—'}
                        </td>
                        <td class="text-center fw-bold ${caColor}" style="color:${cumAve>=50?'#7c3aed':''};${isCumAveActive ? 'background:rgba(124,58,237,.1);border-radius:4px;' : ''}">
                            ${cumAve || '—'}
                            ${isCumAveActive ? '★' : ''}
                        </td>
                        <td class="text-center">
                            ${g.cum_ave_grade ? `<span class="score-grade-badge ${caGC}" style="background:#7c3aed;color:#fff;">${escapeHtml(g.cum_ave_grade)}</span>` : '—'}
                        </td>
                        <td class="text-center" style="color:var(--principal-muted);font-size:.75rem">${bf > 0 ? bf : '—'}</td>
                        <td class="text-center fw-bold ${cColor}">${cumScore || '—'}</td>
                        <td class="text-center">
                            ${g.cum_grade ? `<span class="score-grade-badge ${cGC}">${escapeHtml(g.cum_grade)}</span>` : '—'}
                        </td>
                        <td class="text-center text-muted small">${posCC}</td>
                        <td class="text-center text-muted small">${posCT}</td>
                        <td class="text-center text-muted small">${posAC}</td>
                        <td class="text-center text-muted small">${posAT}</td>
                    `;
                }
                tbody.appendChild(row);
            });
        }
    }

    tooltip.classList.add('show');
    activeTooltip = tooltipId;
}

// ── Image zoom ───────────────────────────────────────────────────────────────
function setupImageZoom() {
    $('.avatar-clickable').off('click').on('click', function(e) {
        e.stopPropagation();
        const imageUrl    = $(this).data('image');
        const studentName = $(this).data('name');
        const admissionNo = $(this).data('admission') || 'N/A';
        const studentClass = $(this).data('class') || 'N/A';
        const gender      = $(this).data('gender') || 'N/A';
        const initials    = $(this).data('initials');

        $('#zoomedImageName').text(studentName || 'Student Photo');
        $('#zoomedImageDetails').html(`
            <i class="ri-id-card-line me-1"></i> ${admissionNo} &nbsp;|&nbsp;
            <i class="ri-school-line me-1"></i> ${studentClass} &nbsp;|&nbsp;
            <i class="ri-${gender === 'Male' ? 'male' : 'female'}-line me-1"></i> ${gender}
        `);

        if (imageUrl && imageUrl !== '' && imageUrl !== 'null') {
            $('#zoomedImage').attr('src', imageUrl).show();
        } else {
            const canvas = document.createElement('canvas');
            canvas.width = 400; canvas.height = 400;
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 400, 400);
            gradient.addColorStop(0, '#667eea'); gradient.addColorStop(1, '#764ba2');
            ctx.fillStyle = gradient; ctx.fillRect(0, 0, 400, 400);
            ctx.fillStyle = '#ffffff'; ctx.font = 'bold 160px "Segoe UI",Arial,sans-serif';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText((initials || 'ST').substring(0, 2), 200, 200);
            $('#zoomedImage').attr('src', canvas.toDataURL()).show();
        }
    });
}

// ── Auto-save (single comment on change) ────────────────────────────────────
document.querySelectorAll('.auto-save-comment').forEach(select => {
    select.addEventListener('change', function() {
        const studentId = this.dataset.studentId;
        const comment   = this.value.trim();
        const original  = this.dataset.originalValue || '';
        if (comment === original) return;

        const row = this.closest('tr') || this.closest('.student-card');
        let studentName = 'Student';
        if (row) {
            const nameEl = row.querySelector('td:nth-child(4) strong') || row.querySelector('.student-details h6');
            if (nameEl) studentName = nameEl.textContent.trim();
        }

        this.style.borderColor = '#f59e0b'; this.style.backgroundColor = '#fffbeb';
        this.disabled = true;

        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append(`teacher_comments[${studentId}]`, comment);

        fetch('{{ route("myprincipalscomment.updateComments", [$schoolclassid, $sessionid, $termid]) }}', {
            method: 'POST',
            body: fd,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.dataset.originalValue = comment;
                this.style.borderColor = '#16a34a'; this.style.backgroundColor = '#d1fae5';
                showToast(`✅ Comment saved for <strong>${studentName}</strong>`, 'success');
            } else { throw new Error(data.message || 'Save failed'); }
        })
        .catch(() => {
            this.value = original;
            this.style.borderColor = '#dc2626'; this.style.backgroundColor = '#fee2e2';
            showToast(`❌ Failed to save for ${studentName}`, 'danger');
        })
        .finally(() => {
            this.disabled = false;
            setTimeout(() => { this.style.borderColor = ''; this.style.backgroundColor = ''; }, 1800);
        });
    });
});

// ── Bulk Save ────────────────────────────────────────────────────────────────
const commentsForm = document.getElementById('commentsForm');
if (commentsForm) {
    commentsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn  = document.getElementById('saveAllBtn');
        const ind  = document.getElementById('savingIndicator');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line spin-icon me-1"></i> Saving All...';
        ind.style.display = 'inline-block';

        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        document.querySelectorAll('.auto-save-comment').forEach(sel => {
            const val = sel.value.trim();
            if (val) fd.append(`teacher_comments[${sel.dataset.studentId}]`, val);
        });

        fetch(this.action, {
            method: 'POST', body: fd,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Save failed');
            showToast('✅ All comments saved successfully!', 'success');
        })
        .catch(err => { showToast('❌ Error: ' + err.message, 'danger'); })
        .finally(() => { btn.disabled = false; btn.innerHTML = orig; ind.style.display = 'none'; });
    });
}

// ── Tooltip triggers ─────────────────────────────────────────────────────────
if (window.innerWidth > 1199) {
    document.querySelectorAll('.grades-trigger').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            const sid  = this.dataset.studentId;
            const name = this.dataset.studentName;
            const tid  = `tooltip-${sid}`;
            activeTooltip === tid ? closeAllTooltips() : showTooltip(tid, sid, name);
        });
    });
    document.querySelectorAll('.tooltip-close').forEach(btn => btn.addEventListener('click', closeAllTooltips));
    document.addEventListener('click', e => {
        if (activeTooltip) {
            const el = document.getElementById(activeTooltip);
            if (el && !el.contains(e.target)) closeAllTooltips();
        }
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllTooltips(); });
}

// ── Search ───────────────────────────────────────────────────────────────────
document.getElementById('searchInput')?.addEventListener('input', function() {
    const term = this.value.toLowerCase().trim();
    document.querySelectorAll('.desktop-table tbody tr').forEach(row => {
        row.style.display = (!term || row.textContent.toLowerCase().includes(term)) ? '' : 'none';
    });
    document.querySelectorAll('.mobile-cards .student-card').forEach(card => {
        card.style.display = (!term || card.textContent.toLowerCase().includes(term)) ? '' : 'none';
    });
});

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.auto-save-comment').forEach(s => { s.dataset.originalValue = s.value; });
    setupImageZoom();
    $(document).on('click', '.zoomed-image', () => $('#imageZoomModal').modal('hide'));
    $(document).on('keydown', e => { if (e.key === 'Escape') $('#imageZoomModal').modal('hide'); });
});
</script>
@endsection