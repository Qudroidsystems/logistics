@extends('layouts.master')
@section('content')
<style>
/* ============================================================
   SCHOOL COMMAND CENTRE — Dashboard v3
   Aesthetic: Refined editorial / data-dense minimal
   Fonts: Syne (headings) + Outfit (body)
   ============================================================ */
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Outfit:wght@300;400;500;600&display=swap');

:root {
    --c-bg:       #f6f7fb;
    --c-surface:  #ffffff;
    --c-border:   #eaecf4;
    --c-muted:    #94a3b8;
    --c-text:     #1e2a3a;
    --c-sub:      #4a5568;

    --c-indigo:   #4f5fff;
    --c-violet:   #7c3aed;
    --c-sky:      #0ea5e9;
    --c-teal:     #0d9488;
    --c-emerald:  #059669;
    --c-rose:     #f43f5e;
    --c-amber:    #d97706;
    --c-orange:   #ea580c;
    --c-slate:    #475569;

    --r:  14px;
    --r-sm: 8px;
    --sh: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    --sh-hover: 0 4px 20px rgba(0,0,0,.10);
    --tr: .22s cubic-bezier(.4,0,.2,1);
}

/* ── Base ── */
.cmd { font-family:'Outfit',sans-serif; background:var(--c-bg); min-height:100vh; }
.cmd-heading { font-family:'Syne',sans-serif; }

/* ── Keyframes ── */
@keyframes fadeUp   { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes pulse2   { 0%,100%{opacity:1} 50%{opacity:.5} }
@keyframes barIn    { from{width:0} to{width:var(--bw)} }
@keyframes countUp  { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }
@keyframes spin     { to{transform:rotate(360deg)} }
@keyframes overlayIn{ from{opacity:0} to{opacity:1} }
@keyframes modalIn  { from{opacity:0;transform:translateY(20px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }

/* ── Top filter bar ── */
.filter-bar-top {
    background:var(--c-surface); border:1px solid var(--c-border);
    border-radius:var(--r); padding:14px 20px;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    box-shadow:var(--sh); margin-bottom:24px;
    animation:fadeUp .35s ease both;
}
.filter-label { font-size:11px; font-weight:600; text-transform:uppercase;
    letter-spacing:.6px; color:var(--c-muted); flex-shrink:0; }
.filter-select {
    padding:7px 30px 7px 12px; border:1px solid var(--c-border);
    border-radius:var(--r-sm); font-size:13px; font-family:'Outfit',sans-serif;
    color:var(--c-text); background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 10px center;
    -webkit-appearance:none; cursor:pointer; transition:border-color var(--tr);
}
.filter-select:focus { outline:none; border-color:var(--c-indigo); }
.filter-apply {
    padding:7px 18px; background:var(--c-indigo); color:#fff;
    border:none; border-radius:var(--r-sm); font-size:13px; font-weight:600;
    font-family:'Outfit',sans-serif; cursor:pointer; transition:all var(--tr);
}
.filter-apply:hover { background:#3b4de8; transform:translateY(-1px); }
.active-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600;
}
.ab-term    { background:#eef2ff; color:#4f5fff; }
.ab-session { background:#f0fdf4; color:#059669; }

/* ── Stat Cards ── */
.sc {
    background:var(--c-surface); border:1px solid var(--c-border);
    border-radius:var(--r); padding:18px 20px;
    box-shadow:var(--sh); transition:all var(--tr);
    animation:fadeUp .4s ease both; position:relative; overflow:hidden;
    cursor:pointer;
}
.sc::after {
    content:''; position:absolute; top:0; right:0;
    width:80px; height:80px; border-radius:50%;
    background:var(--sc-color,#4f5fff); opacity:.04;
    transform:translate(25px,-25px);
}
.sc:hover { transform:translateY(-3px); box-shadow:var(--sh-hover); }
.sc:nth-child(1){animation-delay:.00s}
.sc:nth-child(2){animation-delay:.05s}
.sc:nth-child(3){animation-delay:.10s}
.sc:nth-child(4){animation-delay:.15s}
.sc:nth-child(5){animation-delay:.20s}
.sc:nth-child(6){animation-delay:.25s}
.sc:nth-child(7){animation-delay:.30s}
.sc:nth-child(8){animation-delay:.35s}

.sc-icon {
    width:46px; height:46px; border-radius:12px; display:flex;
    align-items:center; justify-content:center; font-size:20px; flex-shrink:0;
}
.sc-label { font-size:10.5px; font-weight:600; text-transform:uppercase;
    letter-spacing:.6px; color:var(--c-muted); }
.sc-value { font-family:'Syne',sans-serif; font-size:26px; font-weight:700;
    color:var(--c-text); line-height:1.1; }
.sc-sub { font-size:11.5px; color:var(--c-sub); display:flex; align-items:center; gap:5px; }
.badge-up   { display:inline-flex;align-items:center;gap:2px;padding:2px 6px;
    border-radius:20px;font-size:10.5px;font-weight:600;background:#dcfce7;color:#16a34a; }
.badge-down { background:#fee2e2; color:#dc2626; }
.badge-neu  { background:#f1f5f9; color:#64748b; }

.sc-bar { height:3px; border-radius:3px; background:#f1f5f9; margin-top:14px; overflow:hidden; }
.sc-bar-fill { height:100%; border-radius:3px; animation:barIn .9s ease both; animation-delay:.5s; }

/* ── Section Card ── */
.section {
    background:var(--c-surface); border:1px solid var(--c-border);
    border-radius:var(--r); box-shadow:var(--sh); overflow:hidden;
    animation:fadeUp .45s ease both; transition:box-shadow var(--tr);
}
.section:hover { box-shadow:var(--sh-hover); }
.section-hd {
    padding:16px 20px 12px; border-bottom:1px solid #f8fafc;
    display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
}
.section-title { font-family:'Syne',sans-serif; font-size:14.5px; font-weight:700; color:var(--c-text); }
.section-sub   { font-size:11.5px; color:var(--c-muted); margin-top:1px; }
.section-bd    { padding:16px 20px 20px; }

/* ── Pill Buttons ── */
.pill-btn {
    padding:4px 12px; border:1px solid var(--c-border); border-radius:20px;
    background:#fff; font-size:11.5px; font-weight:500; color:var(--c-sub);
    cursor:pointer; transition:all var(--tr); white-space:nowrap;
}
.pill-btn:hover, .pill-btn.on { background:var(--c-indigo); border-color:var(--c-indigo); color:#fff; }

/* ── Tables ── */
.dt { width:100%; border-collapse:collapse; font-size:12.5px; }
.dt thead th {
    padding:8px 10px; font-size:10px; font-weight:600; text-transform:uppercase;
    letter-spacing:.5px; color:var(--c-muted); border-bottom:1px solid var(--c-border);
    white-space:nowrap; background:#fafbfe;
}
.dt tbody tr { transition:background var(--tr); }
.dt tbody tr:hover { background:#f8fafc; }
.dt td { padding:9px 10px; border-bottom:1px solid #f8fafc; color:var(--c-sub); vertical-align:middle; }
.dt tbody tr:last-child td { border-bottom:none; }

/* Score pill */
.sp { display:inline-flex; align-items:center; justify-content:center;
    padding:2px 8px; border-radius:20px; font-size:11.5px; font-weight:600; min-width:42px; }
.sp-A { background:#dcfce7; color:#16a34a; }
.sp-B { background:#dbeafe; color:#2563eb; }
.sp-C { background:#fef9c3; color:#ca8a04; }
.sp-D { background:#ffedd5; color:#ea580c; }
.sp-F { background:#fee2e2; color:#dc2626; }

/* Mini bar */
.mb { height:5px; border-radius:5px; background:#f1f5f9; overflow:hidden; min-width:56px; }
.mb-fill { height:100%; border-radius:5px; }

/* Avatar */
.av {
    width:32px; height:32px; border-radius:50%; object-fit:cover;
    border:2px solid var(--c-border); background:#e2e8f0; display:inline-flex;
    align-items:center; justify-content:center; font-size:11px;
    font-weight:700; color:#64748b; flex-shrink:0;
}
.av-sm { width:24px; height:24px; font-size:9px; }
.av-strip { display:flex; }
.av-strip .av { margin-left:-6px; }
.av-strip .av:first-child { margin-left:0; }

/* Rank */
.rank { width:22px; height:22px; border-radius:50%; display:inline-flex;
    align-items:center; justify-content:center; font-size:11px; font-weight:700; }
.r1 { background:#fef3c7; color:#d97706; }
.r2 { background:#e2e8f0; color:#64748b; }
.r3 { background:#fce7f3; color:#c026d3; }
.rn { background:#f8fafc; color:#94a3b8; }

/* Teacher chip */
.t-chip {
    display:inline-flex; align-items:center; gap:4px;
    background:#f8fafc; border:1px solid var(--c-border);
    border-radius:20px; padding:2px 7px 2px 4px; font-size:11px; color:var(--c-sub);
}

/* ── Progress ring (attendance) ── */
.prog-ring { position:relative; display:inline-flex; align-items:center; justify-content:center; }
.prog-ring svg { transform:rotate(-90deg); }
.prog-ring-label {
    position:absolute; font-family:'Syne',sans-serif; font-size:16px; font-weight:700;
    color:var(--c-text);
}

/* ── Timeline ── */
.tl-item { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid #f8fafc; }
.tl-item:last-child { border-bottom:none; }
.tl-dot { width:34px; height:34px; border-radius:10px; display:flex;
    align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }

/* ── Quick Actions ── */
.qa {
    display:flex; flex-direction:column; align-items:center; gap:7px;
    padding:14px 10px; border:1px solid var(--c-border); border-radius:var(--r-sm);
    text-decoration:none; background:#fff; text-align:center;
    transition:all var(--tr);
}
.qa:hover { background:#f8fafc; transform:translateY(-2px); border-color:#d1d5db; }

/* ── Live dot ── */
.live-dot {
    display:inline-flex; align-items:center; gap:6px;
    font-size:11.5px; color:var(--c-muted); font-weight:500;
}
.live-dot::before {
    content:''; width:7px; height:7px; border-radius:50%; background:#10b981;
    animation:pulse2 2s infinite; flex-shrink:0;
}

/* ── Class/Arm grid card ── */
.cls-tile {
    border-radius:10px; padding:12px 14px; position:relative; overflow:hidden;
    cursor:pointer; transition:transform var(--tr);
}
.cls-tile:hover { transform:translateY(-2px); }

/* ── Heatmap bar row ── */
.hm-row { display:flex; align-items:center; gap:10px; padding:6px 0; border-bottom:1px solid #f8fafc; }
.hm-row:last-child { border-bottom:none; }
.hm-label { font-size:12px; font-weight:500; color:var(--c-sub); width:120px; flex-shrink:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.hm-bar-wrap { flex:1; height:8px; background:#f1f5f9; border-radius:8px; overflow:hidden; }
.hm-bar { height:100%; border-radius:8px; animation:barIn .8s ease both; }
.hm-val { font-size:12px; font-weight:600; width:42px; text-align:right; flex-shrink:0; }

/* ── Teacher perf card ── */
.teacher-card {
    display:flex; align-items:center; gap:12px; padding:12px;
    border:1px solid var(--c-border); border-radius:10px;
    transition:all var(--tr); cursor:pointer;
}
.teacher-card:hover { background:#f8fafc; border-color:#d1d5db; }

/* ── Modal system ── */
.m-overlay {
    position:fixed; inset:0; background:rgba(15,23,42,.5);
    z-index:9900; display:flex; align-items:center; justify-content:center;
    padding:16px; opacity:0; pointer-events:none; transition:opacity .2s;
    backdrop-filter:blur(3px);
}
.m-overlay.open { opacity:1; pointer-events:all; animation:overlayIn .2s ease; }
.m-box {
    background:#fff; border-radius:18px; width:100%; max-width:900px;
    max-height:90vh; display:flex; flex-direction:column; overflow:hidden;
    transform:translateY(18px) scale(.97); opacity:0;
    transition:all .22s cubic-bezier(.4,0,.2,1);
    box-shadow:0 20px 60px rgba(0,0,0,.2);
}
.m-overlay.open .m-box { transform:none; opacity:1; }
.m-hd { padding:18px 22px 14px; border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
.m-title { font-family:'Syne',sans-serif; font-size:17px; font-weight:700; color:var(--c-text); }
.m-close { width:30px; height:30px; border-radius:8px; background:#f8fafc; border:none;
    cursor:pointer; font-size:14px; color:#64748b; display:flex; align-items:center;
    justify-content:center; transition:all var(--tr); }
.m-close:hover { background:#fee2e2; color:#dc2626; }
.m-body { padding:18px 22px; overflow-y:auto; flex:1; }
.m-ft { padding:12px 22px; border-top:1px solid #f1f5f9; display:flex;
    align-items:center; justify-content:flex-end; gap:8px; flex-shrink:0; }

/* KPI grid in modal */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; }
.kpi { background:#f8fafc; border-radius:10px; padding:12px 14px; }
.kpi-l { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--c-muted); }
.kpi-v { font-family:'Syne',sans-serif; font-size:20px; font-weight:700; color:var(--c-text); }

/* Modal tabs */
.m-tabs { display:flex; gap:3px; background:#f8fafc; border-radius:9px; padding:3px; margin-bottom:18px; }
.m-tab { flex:1; padding:6px 10px; border-radius:7px; border:none; background:transparent;
    font-size:12.5px; font-weight:500; color:var(--c-muted); cursor:pointer; transition:all var(--tr); }
.m-tab.on { background:#fff; color:var(--c-text); font-weight:600; box-shadow:0 1px 4px rgba(0,0,0,.08); }
.m-panel { display:none; }
.m-panel.on { display:block; animation:fadeUp .18s ease; }

/* ── Attendance gauge row ── */
.att-row {
    display:flex; align-items:center; gap:12px; padding:8px 0;
    border-bottom:1px solid #f8fafc;
}
.att-row:last-child { border-bottom:none; }

/* Responsive */
@media(max-width:768px){
    .sc-value { font-size:20px; }
    .m-box { max-height:96vh; }
    .kpi-grid { grid-template-columns:1fr 1fr; }
    .filter-bar-top { flex-direction:column; align-items:flex-start; }
}

/* Color utilities */
.bg-indigo  { background:#eef2ff; } .fg-indigo  { color:#4f5fff; }
.bg-violet  { background:#f3e8ff; } .fg-violet  { color:#7c3aed; }
.bg-sky     { background:#e0f2fe; } .fg-sky     { color:#0ea5e9; }
.bg-teal    { background:#ccfbf1; } .fg-teal    { color:#0d9488; }
.bg-emerald { background:#d1fae5; } .fg-emerald { color:#059669; }
.bg-rose    { background:#ffe4e6; } .fg-rose    { color:#f43f5e; }
.bg-amber   { background:#fef3c7; } .fg-amber   { color:#d97706; }
.bg-orange  { background:#ffedd5; } .fg-orange  { color:#ea580c; }
.bg-slate   { background:#f1f5f9; } .fg-slate   { color:#475569; }
</style>

<div class="main-content cmd">
<div class="page-content">
<div class="container-fluid">

@include('layouts.partials.dashboard-notices')

{{-- ═══════════════════════════════════════════════════════
     HEADER
═══════════════════════════════════════════════════════════ --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="mb-0 fw-bold cmd-heading" style="color:var(--c-text);font-size:21px;">School Command Centre</h4>
                <span class="live-dot mt-1 d-inline-block">Live · {{ now()->format('D, d M Y · g:i A') }}</span>
            </div>
            <ol class="breadcrumb m-0 bg-transparent" style="font-size:12px;">
                <li class="breadcrumb-item"><a href="#" style="color:var(--c-muted);">Dashboard</a></li>
                <li class="breadcrumb-item active">Analytics</li>
            </ol>
        </div>
    </div>
</div>

@hasrole('Super Admin')

{{-- ═══════════════════════════════════════════════════════
     TERM / SESSION FILTER BAR
═══════════════════════════════════════════════════════════ --}}
<form method="GET" action="{{ request()->url() }}" id="filterForm">
<div class="filter-bar-top">
    <span class="filter-label"><i class="ph-funnel me-1"></i>Viewing</span>

    <div class="d-flex align-items-center gap-8" style="gap:8px;">
        <span class="filter-label" style="font-size:10px;">Session</span>
        <select name="session_id" class="filter-select" id="sessionSelect">
            @foreach($allSessions as $sess)
            <option value="{{ $sess->id }}" {{ $sess->id == $selectedSession?->id ? 'selected' : '' }}>
                {{ $sess->session }}{{ $sess->status === 'Current' ? ' (Current)' : '' }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="d-flex align-items-center gap-8" style="gap:8px;">
        <span class="filter-label" style="font-size:10px;">Term</span>
        <select name="term_id" class="filter-select" id="termSelect">
            @foreach($allTerms as $t)
            <option value="{{ $t->id }}" {{ $t->id == $selectedTerm?->id ? 'selected' : '' }}>
                {{ $t->term }}{{ $t->status ? ' (Active)' : '' }}
            </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="filter-apply">Apply Filter</button>

    <div class="ms-auto d-flex gap-2 flex-wrap">
        @if($selectedSession)
        <span class="active-badge ab-session"><i class="bi bi-calendar3 me-1"></i>{{ $selectedSession->session }}</span>
        @endif
        @if($selectedTerm)
        <span class="active-badge ab-term"><i class="bi bi-bookmark me-1"></i>{{ $selectedTerm->term }}</span>
        @endif
    </div>
</div>
</form>

{{-- ═══════════════════════════════════════════════════════
     ROW 1 — PRIMARY STATS (8 cards)
═══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-3">

    {{-- Total Population --}}
    <div class="col-xxl-3 col-md-6">
        <div class="sc" style="--sc-color:#4f5fff;" onclick="openM('mPopulation')">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <p class="sc-label mb-0">Total Students</p>
                    <div class="sc-value counter" data-t="{{ $total_population }}">0</div>
                    <div class="sc-sub mt-1">
                        @php $pp = is_numeric($population_percentage) ? $population_percentage : 0; @endphp
                        <span class="badge-up {{ $pp < 0 ? 'badge-down' : '' }}">
                            <i class="bi bi-arrow-{{ $pp >= 0 ? 'up' : 'down' }}" style="font-size:9px;"></i>{{ abs($pp) }}%
                        </span>
                        <span>vs last month</span>
                    </div>
                </div>
                <div class="sc-icon bg-indigo fg-indigo"><i class="ph-users-three"></i></div>
            </div>
            <div class="sc-bar mt-3">
                @php $bw = min(100, abs($pp)*4+20); @endphp
                <div class="sc-bar-fill" style="width:{{ $bw }}%;--bw:{{ $bw }}%;background:linear-gradient(90deg,#4f5fff,#7c3aed);"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="av-strip">
                    @foreach(\App\Models\Student::latest()->take(4)->get() as $rs)
                    @php $pic=$rs->picture?->picture; @endphp
                    @if($pic && $pic!=='unnamed.jpg')
                    <img src="{{ asset('storage/student_avatars/'.$pic) }}" class="av av-sm" title="{{ $rs->firstname }}">
                    @else
                    <div class="av av-sm" title="{{ $rs->firstname }}">{{ strtoupper(substr($rs->firstname,0,1).substr($rs->lastname,0,1)) }}</div>
                    @endif
                    @endforeach
                    @if($total_population > 4)<div class="av av-sm" style="background:#eef2ff;color:#4f5fff;font-size:8px;">+{{ number_format($total_population-4) }}</div>@endif
                </div>
                <a class="pill-btn" onclick="event.stopPropagation();openM('mPopulation')" style="font-size:10.5px;padding:3px 9px;">Details →</a>
            </div>
        </div>
    </div>

    {{-- Term Enrollment --}}
    <div class="col-xxl-3 col-md-6">
        <div class="sc" style="--sc-color:#059669;" onclick="openM('mClasses')">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <p class="sc-label mb-0">Enrolled This Term</p>
                    <div class="sc-value counter" data-t="{{ $students_in_selected_term }}">0</div>
                    <div class="sc-sub mt-1">
                        <i class="bi bi-bookmark-check text-success me-1"></i>
                        {{ $selectedTerm?->term ?? 'N/A' }} · {{ $selectedSession?->session ?? '' }}
                    </div>
                </div>
                <div class="sc-icon bg-emerald fg-emerald"><i class="ph-rocket-launch"></i></div>
            </div>
            <div class="sc-bar mt-3">
                @php $ew = $total_population > 0 ? min(100,round(($students_in_selected_term/$total_population)*100)) : 0; @endphp
                <div class="sc-bar-fill" style="width:{{ $ew }}%;--bw:{{ $ew }}%;background:#059669;"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small style="font-size:11px;color:var(--c-muted);">{{ $ew }}% of total students</small>
                <a class="pill-btn" onclick="event.stopPropagation();openM('mClasses')" style="font-size:10.5px;padding:3px 9px;">By Class →</a>
            </div>
        </div>
    </div>

    {{-- Staff --}}
    <div class="col-xxl-3 col-md-6">
        <div class="sc" style="--sc-color:#d97706;" onclick="openM('mStaff')">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <p class="sc-label mb-0">Staff Members</p>
                    <div class="sc-value counter" data-t="{{ $staff_count }}">0</div>
                    <div class="sc-sub mt-1">
                        @foreach($staff_by_role as $role => $cnt)
                        <span style="margin-right:4px;">{{ ucfirst($role) }}: {{ $cnt }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="sc-icon bg-amber fg-amber"><i class="ph-chalkboard-teacher"></i></div>
            </div>
            <div class="sc-bar mt-3">
                <div class="sc-bar-fill" style="width:75%;--bw:75%;background:linear-gradient(90deg,#d97706,#ef4444);"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small style="font-size:11px;color:var(--c-muted);">Admin · Teachers · Staff</small>
                <a class="pill-btn" onclick="event.stopPropagation();openM('mStaff')" style="font-size:10.5px;padding:3px 9px;">View →</a>
            </div>
        </div>
    </div>

    {{-- Attendance Rate --}}
    <div class="col-xxl-3 col-md-6">
        <div class="sc" style="--sc-color:{{ $overall_attendance_rate >= 75 ? '#059669' : ($overall_attendance_rate >= 50 ? '#d97706' : '#dc2626') }};" onclick="openM('mAttendance')">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <p class="sc-label mb-0">Attendance Rate</p>
                    <div class="sc-value mt-1" style="color:{{ $overall_attendance_rate >= 75 ? '#059669' : ($overall_attendance_rate >= 50 ? '#d97706' : '#dc2626') }};">
                        {{ $overall_attendance_rate }}%
                    </div>
                    <div class="sc-sub mt-1">
                        <i class="bi bi-calendar-check me-1"></i>{{ $selectedTerm?->term ?? 'This term' }}
                    </div>
                </div>
                <div class="sc-icon bg-sky fg-sky"><i class="ph-calendar-check"></i></div>
            </div>
            <div class="sc-bar mt-3">
                <div class="sc-bar-fill" style="width:{{ $overall_attendance_rate }}%;--bw:{{ $overall_attendance_rate }}%;background:{{ $overall_attendance_rate >= 75 ? '#059669' : ($overall_attendance_rate >= 50 ? '#f59e0b' : '#ef4444') }};"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small style="font-size:11px;color:var(--c-muted);">{{ $overall_attendance_rate >= 75 ? 'Good standing' : ($overall_attendance_rate >= 50 ? 'Needs attention' : 'Critical') }}</small>
                <a class="pill-btn" onclick="event.stopPropagation();openM('mAttendance')" style="font-size:10.5px;padding:3px 9px;">By Class →</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Male --}}
    <div class="col-xxl-3 col-md-6">
        <div class="sc" style="--sc-color:#0ea5e9;" onclick="openM('mGender')">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <p class="sc-label mb-0">Male Students</p>
                    <div class="sc-value counter" data-t="{{ $gender_counts['Male'] }}">0</div>
                    @php $mp = $total_population > 0 ? round(($gender_counts['Male']/$total_population)*100,1) : 0; @endphp
                    <div class="sc-sub mt-1">{{ $mp }}% of total population</div>
                </div>
                <div class="sc-icon bg-sky fg-sky"><i class="ph-gender-male"></i></div>
            </div>
            <div class="sc-bar mt-3">
                <div class="sc-bar-fill" style="width:{{ $mp }}%;--bw:{{ $mp }}%;background:#0ea5e9;"></div>
            </div>
        </div>
    </div>

    {{-- Female --}}
    <div class="col-xxl-3 col-md-6">
        <div class="sc" style="--sc-color:#f43f5e;" onclick="openM('mGender')">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <p class="sc-label mb-0">Female Students</p>
                    <div class="sc-value counter" data-t="{{ $gender_counts['Female'] }}">0</div>
                    @php $fp = $total_population > 0 ? round(($gender_counts['Female']/$total_population)*100,1) : 0; @endphp
                    <div class="sc-sub mt-1">{{ $fp }}% of total population</div>
                </div>
                <div class="sc-icon bg-rose fg-rose"><i class="ph-gender-female"></i></div>
            </div>
            <div class="sc-bar mt-3">
                <div class="sc-bar-fill" style="width:{{ $fp }}%;--bw:{{ $fp }}%;background:#f43f5e;"></div>
            </div>
        </div>
    </div>

    {{-- Classes --}}
    <div class="col-xxl-3 col-md-6">
        <div class="sc" style="--sc-color:#7c3aed;" onclick="openM('mClasses')">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <p class="sc-label mb-0">Active Classes</p>
                    <div class="sc-value counter" data-t="{{ $total_classes }}">0</div>
                    <div class="sc-sub mt-1">Avg {{ $total_classes > 0 ? round($total_population/$total_classes) : 0 }} students / class</div>
                </div>
                <div class="sc-icon bg-violet fg-violet"><i class="ph-graduation-cap"></i></div>
            </div>
            <div class="sc-bar mt-3">
                <div class="sc-bar-fill" style="width:80%;--bw:80%;background:linear-gradient(90deg,#7c3aed,#c026d3);"></div>
            </div>
        </div>
    </div>

    {{-- Subjects --}}
    <div class="col-xxl-3 col-md-6">
        <div class="sc" style="--sc-color:#0d9488;" onclick="openM('mSubjects')">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <p class="sc-label mb-0">Total Subjects</p>
                    <div class="sc-value counter" data-t="{{ $total_subjects }}">0</div>
                    <div class="sc-sub mt-1">Across {{ $total_classes }} classes</div>
                </div>
                <div class="sc-icon bg-teal fg-teal"><i class="ph-book-open"></i></div>
            </div>
            <div class="sc-bar mt-3">
                <div class="sc-bar-fill" style="width:70%;--bw:70%;background:linear-gradient(90deg,#0d9488,#0ea5e9);"></div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ROW 2 — ACADEMIC PERFORMANCE + GRADE DISTRIBUTION
═══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Academic Performance Bar --}}
    <div class="col-xxl-8">
        <div class="section" style="animation-delay:.1s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">Academic Performance by Class & Arm</div>
                    <div class="section-sub">Average total scores — {{ $selectedTerm?->term }} · {{ $selectedSession?->session }}</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="pill-btn on" id="perfBarBtn" onclick="switchPerf('bar',this)">Bar</button>
                    <button class="pill-btn" id="perfLineBtn" onclick="switchPerf('line',this)">Line</button>
                    <button class="pill-btn" onclick="openM('mAcademic')">Full →</button>
                </div>
            </div>
            <div class="section-bd">
                <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:10px;font-size:11px;color:var(--c-muted);">
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:2px;background:#4f5fff;"></span>Avg Score</span>
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:2px;background:#fee2e2;"></span>Below 40 (Fail zone)</span>
                </div>
                <div style="height:290px;position:relative;">
                    <canvas id="chartPerf"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Grade Distribution --}}
    <div class="col-xxl-4">
        <div class="section" style="animation-delay:.15s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">Grade Distribution</div>
                    <div class="section-sub">{{ $selectedTerm?->term }} breakdown</div>
                </div>
                <button class="pill-btn" onclick="openM('mGrades')">Expand →</button>
            </div>
            <div class="section-bd">
                @php
                    $totalGrades = array_sum($grade_distribution ?? []);
                    $gradeColors = ['A1'=>'#16a34a','B2'=>'#2563eb','B3'=>'#0284c7','C4'=>'#d97706','C5'=>'#ea580c','C6'=>'#dc2626','D7'=>'#9333ea','E8'=>'#be185d','F9'=>'#94a3b8'];
                @endphp
                <div style="height:230px;position:relative;margin-bottom:8px;">
                    <canvas id="chartGrades"></canvas>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;">
                    @foreach($grade_distribution ?? [] as $g => $cnt)
                    @if($cnt > 0)
                    <span style="display:flex;align-items:center;gap:3px;font-size:10.5px;color:var(--c-sub);">
                        <span style="width:7px;height:7px;border-radius:2px;background:{{ $gradeColors[$g]??'#94a3b8' }};display:inline-block;"></span>
                        {{ $g }}: {{ $cnt }}
                    </span>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ROW 3 — TOP STUDENTS + SUBJECT PERFORMANCE
═══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Top Students --}}
    <div class="col-xxl-6">
        <div class="section" style="animation-delay:.2s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">🏆 Top Performing Students</div>
                    <div class="section-sub">Whole school · ranked by {{ strtolower($best_students['measure_label'] ?? 'cumulative average') }} (same as broadsheet) — {{ $selectedTerm?->term }} · {{ $selectedSession?->session }}</div>
                </div>
                <button class="pill-btn" onclick="openM('mTopStudents')">Full List →</button>
            </div>
            <div class="section-bd">
                <table class="dt">
                    <thead><tr>
                        <th style="width:30px;">#</th>
                        <th>Student</th>
                        <th>Class / Arm</th>
                        <th>Avg</th>
                        <th>Grade</th>
                    </tr></thead>
                    <tbody>
                    @forelse(array_slice($top_students,0,8) as $idx => $stu)
                    @php
                        $avg = $stu['average'];
                        $spClass = $avg >= 75 ? 'sp-A' : ($avg >= 60 ? 'sp-B' : ($avg >= 50 ? 'sp-C' : ($avg >= 40 ? 'sp-D' : 'sp-F')));
                        $pic = !empty($stu['picture']) ? basename($stu['picture']) : null;
                        $rk  = (int) ($stu['rank'] ?? $idx + 1);
                    @endphp
                    <tr>
                        <td>
                            <span class="rank {{ $rk===1?'r1':($rk===2?'r2':($rk===3?'r3':'rn')) }}">
                                {{ $rk <= 3 ? ['🥇','🥈','🥉'][$rk-1] : $rk }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                @if($pic && $pic!=='unnamed.jpg')
                                <img src="{{ asset('storage/student_avatars/'.$pic) }}" class="av" style="width:30px;height:30px;">
                                @else
                                <div class="av" style="width:30px;height:30px;font-size:10px;">{{ strtoupper(substr($stu['name'],0,2)) }}</div>
                                @endif
                                <div>
                                    <div style="font-weight:600;font-size:12.5px;color:var(--c-text);">{{ $stu['name'] }}</div>
                                    <div style="font-size:10.5px;color:var(--c-muted);">{{ $stu['admission_no'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:11.5px;font-weight:500;color:var(--c-sub);">{{ $stu['class'] }}</span>
                            @if($stu['arm'])
                            <span style="font-size:10.5px;background:#f1f5f9;color:#64748b;padding:1px 6px;border-radius:10px;margin-left:3px;">{{ $stu['arm'] }}</span>
                            @endif
                        </td>
                        <td><span class="sp {{ $spClass }}">{{ $avg }}%</span></td>
                        <td style="font-size:12px;font-weight:700;color:{{ $avg>=60?'#16a34a':($avg>=40?'#d97706':'#dc2626') }};">{{ $stu['grade'] }}</td>
                    </tr>
                    @empty
                    @php
                        $bsWhy = $best_students['notice'] ?? null;
                        $bsEx  = $best_students['excluded_summary'] ?? [];
                    @endphp
                    <tr><td colspan="5" class="text-center text-muted py-3" style="font-size:12.5px;">
                        @if($bsWhy)
                            {{ $bsWhy }}
                        @elseif(!empty($bsEx))
                            No student met the ranking rules:
                            @foreach($bsEx as $reason => $n)<div style="font-size:11.5px;">{{ $n }} × {{ $reason }}</div>@endforeach
                            @if(Route::has('broadsheet.ranking.index'))<a href="{{ route('broadsheet.ranking.index') }}" style="font-size:11.5px;">Open Ranking settings</a>@endif
                        @else
                            No academic data for this term
                        @endif
                    </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Subject Performance --}}
    <div class="col-xxl-6">
        <div class="section" style="animation-delay:.25s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">Subject Performance</div>
                    <div class="section-sub">Avg score, pass rate & assigned teachers</div>
                </div>
                <button class="pill-btn" onclick="openM('mSubjectPerf')">Full →</button>
            </div>
            <div class="section-bd">
                <table class="dt">
                    <thead><tr>
                        <th>Subject</th>
                        <th>Teacher(s)</th>
                        <th>Avg</th>
                        <th>Pass %</th>
                    </tr></thead>
                    <tbody>
                    @forelse(array_slice($subject_performance,0,8) as $subj)
                    <tr>
                        <td style="font-weight:500;font-size:12.5px;color:var(--c-text);">{{ $subj['subject_name'] }}</td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                @foreach(array_slice($subj['teachers']??[],0,2) as $t)
                                <div class="t-chip">
                                    @if(!empty($t['avatar']))<img src="{{ asset('storage/'.$t['avatar']) }}" class="av" style="width:16px;height:16px;">
                                    @else<div class="av" style="width:16px;height:16px;font-size:7px;">{{ strtoupper(substr($t['name'],0,2)) }}</div>@endif
                                    <span>{{ Str::limit(explode(' ',$t['name'])[0]??'',10) }}</span>
                                </div>
                                @endforeach
                                @if(count($subj['teachers']??[]) > 2)
                                <span style="font-size:10.5px;color:var(--c-muted);">+{{ count($subj['teachers'])-2 }}</span>
                                @endif
                                @if(empty($subj['teachers']))<span style="font-size:11px;color:var(--c-muted);">—</span>@endif
                            </div>
                        </td>
                        <td>
                            <span class="sp {{ $subj['avg_score']>=60?'sp-B':($subj['avg_score']>=40?'sp-C':'sp-F') }}">
                                {{ $subj['avg_score'] }}%
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <div class="mb" style="width:50px;">
                                    <div class="mb-fill" style="width:{{ $subj['pass_rate'] }}%;background:#10b981;"></div>
                                </div>
                                <span style="font-size:11px;color:var(--c-sub);">{{ $subj['pass_rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3" style="font-size:12.5px;">No data for this term</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ROW 3B — BEST STUDENTS BY CLASS (ALL ARMS) & BY ARM
     Same figures as the broadsheet: Cum Ave with BF, same ranking rules.
═══════════════════════════════════════════════════════════ --}}
@php
    $bs        = $best_students ?? ['by_class' => [], 'by_arm' => [], 'measure_label' => 'Cumulative average', 'ranked' => 0, 'students' => 0];
    $bsMedal   = fn ($r) => $r === 1 ? 'r1' : ($r === 2 ? 'r2' : ($r === 3 ? 'r3' : 'rn'));
    $bsSp      = fn ($v) => $v >= 75 ? 'sp-A' : ($v >= 60 ? 'sp-B' : ($v >= 50 ? 'sp-C' : ($v >= 40 ? 'sp-D' : 'sp-F')));
    $bsFmt     = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="section" style="animation-delay:.27s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">🎓 Best Students by Class &amp; Arm</div>
                    <div class="section-sub">
                        Ranked by {{ strtolower($bs['measure_label']) }} — the same figures and rules as the broadsheet ·
                        {{ $selectedTerm?->term }} · {{ $selectedSession?->session }}
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap" role="tablist" aria-label="Best students view">
                    <button type="button" class="pill-btn on bsx-tab" data-pane="bsxClass" role="tab" aria-selected="true">By Class (all arms)</button>
                    <button type="button" class="pill-btn bsx-tab" data-pane="bsxArm" role="tab" aria-selected="false">By Arm</button>
                    @if(Route::has('broadsheet.best-students'))
                        <a href="{{ route('broadsheet.best-students') }}" class="pill-btn" style="text-decoration:none;">Compare classes →</a>
                    @endif
                </div>
            </div>
            <div class="section-bd">
                @if(empty($bs['by_class']))
                    <div class="text-center py-3" style="color:var(--c-muted);font-size:12.5px;">{{ $bs['notice'] ?? 'No results recorded for this term yet' }}</div>
                @else
                    {{-- By class (all arms together) --}}
                    <div class="bsx-pane" id="bsxClass" role="tabpanel">
                        <div class="row g-3">
                            @foreach($bs['by_class'] as $className => $c)
                            <div class="col-xxl-3 col-lg-4 col-md-6">
                                <div style="border:1px solid var(--c-border);border-radius:12px;height:100%;">
                                    <div style="padding:10px 14px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:baseline;gap:8px;">
                                        <span class="cmd-heading" style="font-weight:700;font-size:13.5px;color:var(--c-text);">{{ $className }}</span>
                                        <span style="font-size:10.5px;color:var(--c-muted);white-space:nowrap;">{{ $c['arms'] }} arm{{ $c['arms'] > 1 ? 's' : '' }} · {{ $c['students'] }} students</span>
                                    </div>
                                    <div style="padding:6px 14px 10px;">
                                        @forelse($c['top'] as $e)
                                        <div style="display:flex;align-items:center;gap:9px;padding:6px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;' : '' }}">
                                            <span class="rank {{ $bsMedal($e['rank']) }}">{{ $e['rank'] }}</span>
                                            @if(!empty($e['picture']) && $e['picture'] !== 'unnamed.jpg')
                                                <img src="{{ asset('storage/student_avatars/'.basename($e['picture'])) }}" class="av" style="width:28px;height:28px;" alt="">
                                            @else
                                                <div class="av" style="width:28px;height:28px;font-size:9px;">{{ strtoupper(substr($e['name'],0,2)) }}</div>
                                            @endif
                                            <div style="min-width:0;flex:1;">
                                                <div style="font-weight:600;font-size:12px;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $e['name'] }}</div>
                                                <div style="font-size:10.5px;color:var(--c-muted);">{{ $e['arm'] ? 'Arm '.$e['arm'] : $e['admission_no'] }}</div>
                                            </div>
                                            <span class="sp {{ $bsSp($e['average']) }}" title="{{ $bs['measure_label'] }}: {{ $bsFmt($e['value']) }}">{{ $bsFmt($e['value']) }}</span>
                                        </div>
                                        @empty
                                        <div style="font-size:11.5px;color:var(--c-muted);padding:8px 0;">No student met the ranking rules</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- By arm --}}
                    <div class="bsx-pane" id="bsxArm" role="tabpanel" hidden>
                        <div class="row g-3">
                            @foreach($bs['by_arm'] as $armLabel => $a)
                            <div class="col-xxl-3 col-lg-4 col-md-6">
                                <div style="border:1px solid var(--c-border);border-radius:12px;height:100%;">
                                    <div style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                        <span class="cmd-heading" style="font-weight:700;font-size:13.5px;color:var(--c-text);">{{ $armLabel }}</span>
                                    </div>
                                    <div style="padding:6px 14px 10px;">
                                        @foreach($a['top'] as $e)
                                        <div style="display:flex;align-items:center;gap:9px;padding:6px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;' : '' }}">
                                            <span class="rank {{ $bsMedal($e['rank']) }}">{{ $e['rank'] }}</span>
                                            @if(!empty($e['picture']) && $e['picture'] !== 'unnamed.jpg')
                                                <img src="{{ asset('storage/student_avatars/'.basename($e['picture'])) }}" class="av" style="width:28px;height:28px;" alt="">
                                            @else
                                                <div class="av" style="width:28px;height:28px;font-size:9px;">{{ strtoupper(substr($e['name'],0,2)) }}</div>
                                            @endif
                                            <div style="min-width:0;flex:1;">
                                                <div style="font-weight:600;font-size:12px;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $e['name'] }}</div>
                                                <div style="font-size:10.5px;color:var(--c-muted);">{{ $e['admission_no'] }} · {{ $e['subject_count'] }} subjects</div>
                                            </div>
                                            <span class="sp {{ $bsSp($e['average']) }}" title="{{ $bs['measure_label'] }}: {{ $bsFmt($e['value']) }}">{{ $bsFmt($e['value']) }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div style="font-size:11px;color:var(--c-muted);margin-top:12px;">
                        {{ $bs['ranked'] }} of {{ $bs['students'] }} students met the ranking rules (Broadsheet Ranking settings). Ties share a position.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Best Students Explorer (admin-selected classes/arms + criteria, printable) --}}
@include('dashboards.partials.best-students-explorer')

{{-- ═══════════════════════════════════════════════════════
     ROW 4 — TOP TEACHERS + ATTENDANCE BY CLASS
═══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Top Teachers --}}
    <div class="col-xxl-5">
        <div class="section" style="animation-delay:.3s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">🎖️ Best Performing Teachers</div>
                    <div class="section-sub">Ranked by student average & pass rate</div>
                </div>
                <button class="pill-btn" onclick="openM('mTeachers')">All →</button>
            </div>
            <div class="section-bd">
                @forelse(array_slice($top_teachers,0,6) as $idx => $teacher)
                @php
                    $avg = $teacher['avg_score'];
                    $barColor = $avg >= 70 ? '#059669' : ($avg >= 55 ? '#4f5fff' : ($avg >= 40 ? '#d97706' : '#ef4444'));
                @endphp
                <div class="teacher-card mb-2">
                    <div style="position:relative;flex-shrink:0;">
                        @if(!empty($teacher['avatar']))
                        <img src="{{ asset('storage/'.$teacher['avatar']) }}" class="av" style="width:38px;height:38px;">
                        @else
                        <div class="av" style="width:38px;height:38px;font-size:13px;">{{ strtoupper(substr($teacher['name'],0,2)) }}</div>
                        @endif
                        <span style="position:absolute;bottom:-2px;right:-2px;width:16px;height:16px;border-radius:50%;background:{{ $barColor }};border:2px solid #fff;display:flex;align-items:center;justify-content:center;">
                            <span style="font-size:7px;color:#fff;font-weight:800;">{{ $idx+1 }}</span>
                        </span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:12.5px;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $teacher['name'] }}</div>
                        <div style="font-size:10.5px;color:var(--c-muted);margin-top:1px;">
                            {{ implode(', ', array_slice($teacher['subjects'],0,3)) }}
                            @if(count($teacher['subjects'])>3)<span>+{{ count($teacher['subjects'])-3 }} more</span>@endif
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                            <div class="mb" style="width:100%;max-width:100px;">
                                <div class="mb-fill" style="width:{{ $avg }}%;background:{{ $barColor }};"></div>
                            </div>
                            <span style="font-size:11px;font-weight:600;color:{{ $barColor }};">{{ $avg }}%</span>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:11.5px;font-weight:600;color:var(--c-sub);">{{ $teacher['student_count'] }} students</div>
                        <div style="font-size:10.5px;color:var(--c-muted);">{{ $teacher['pass_rate'] }}% pass</div>
                        <div style="font-size:10px;color:var(--c-muted);">{{ $teacher['subject_count'] }} subjects</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-3" style="color:var(--c-muted);font-size:12.5px;">No academic data available for this term</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Attendance by Class/Arm --}}
    <div class="col-xxl-7">
        <div class="section" style="animation-delay:.35s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">Attendance by Class & Arm</div>
                    <div class="section-sub">{{ $selectedTerm?->term }} · {{ $selectedSession?->session }}</div>
                </div>
                <div class="d-flex gap-2">
                    <span style="font-size:11px;color:var(--c-muted);display:flex;align-items:center;gap:4px;">
                        <span style="width:8px;height:8px;border-radius:2px;background:#059669;display:inline-block;"></span>≥75%
                        <span style="width:8px;height:8px;border-radius:2px;background:#f59e0b;display:inline-block;margin-left:6px;"></span>50–74%
                        <span style="width:8px;height:8px;border-radius:2px;background:#ef4444;display:inline-block;margin-left:6px;"></span>&lt;50%
                    </span>
                    <button class="pill-btn" onclick="openM('mAttendance')">Full →</button>
                </div>
            </div>
            <div class="section-bd">
                @forelse($attendance_by_class as $att)
                @php
                    $rc = $att['rate'] >= 75 ? '#059669' : ($att['rate'] >= 50 ? '#f59e0b' : '#ef4444');
                @endphp
                <div class="hm-row">
                    <div class="hm-label" title="{{ $att['label'] }}">
                        <span style="font-weight:500;color:var(--c-text);">{{ $att['class_name'] }}</span>
                        @if($att['arm'])<span style="background:#f1f5f9;color:#64748b;padding:1px 5px;border-radius:8px;font-size:10px;margin-left:3px;">{{ $att['arm'] }}</span>@endif
                    </div>
                    <div class="hm-bar-wrap">
                        <div class="hm-bar" style="width:{{ $att['rate'] }}%;background:{{ $rc }};--bw:{{ $att['rate'] }}%;"></div>
                    </div>
                    <div class="hm-val" style="color:{{ $rc }};">{{ $att['rate'] }}%</div>
                    <div style="font-size:10.5px;color:var(--c-muted);width:70px;text-align:right;flex-shrink:0;">
                        {{ $att['student_count'] }} students
                    </div>
                </div>
                @empty
                <div class="text-center py-3" style="color:var(--c-muted);font-size:12.5px;">No attendance data for this term</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ROW 5 — CHARTS: Population + Trends + Attendance Trend
═══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Population Donut --}}
    <div class="col-xxl-4">
        <div class="section" style="animation-delay:.4s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">Population Breakdown</div>
                    <div class="section-sub">Gender & staff distribution</div>
                </div>
            </div>
            <div class="section-bd">
                <div style="height:240px;position:relative;">
                    <canvas id="chartPop"></canvas>
                </div>
                <div style="display:flex;justify-content:center;gap:18px;margin-top:10px;font-size:12px;flex-wrap:wrap;">
                    <span style="display:flex;align-items:center;gap:4px;color:var(--c-sub);"><span style="width:10px;height:10px;border-radius:2px;background:#0ea5e9;"></span>Male {{ $gender_counts['Male'] }}</span>
                    <span style="display:flex;align-items:center;gap:4px;color:var(--c-sub);"><span style="width:10px;height:10px;border-radius:2px;background:#f43f5e;"></span>Female {{ $gender_counts['Female'] }}</span>
                    <span style="display:flex;align-items:center;gap:4px;color:var(--c-sub);"><span style="width:10px;height:10px;border-radius:2px;background:#d97706;"></span>Staff {{ $staff_count }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Enrollment Trend --}}
    <div class="col-xxl-5">
        <div class="section" style="animation-delay:.45s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">Enrollment Trend</div>
                    <div class="section-sub">Monthly new students — last 12 months</div>
                </div>
            </div>
            <div class="section-bd">
                <div style="height:240px;position:relative;">
                    <canvas id="chartTrend"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- All-Term Performance (selected session) --}}
    <div class="col-xxl-3">
        <div class="section" style="animation-delay:.5s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">Term Comparison</div>
                    <div class="section-sub">Avg score across terms · {{ $selectedSession?->session }}</div>
                </div>
            </div>
            <div class="section-bd">
                <div style="height:240px;position:relative;">
                    <canvas id="chartTerms"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ROW 6 — CLASS CAPACITY + ACTIVITY + QUICK ACTIONS
═══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Class Capacity Grid --}}
    <div class="col-xxl-7">
        <div class="section" style="animation-delay:.55s;">
            <div class="section-hd">
                <div>
                    <div class="section-title">Class Capacity & Utilization</div>
                    <div class="section-sub">Enrollment vs capacity per class/arm</div>
                </div>
                <button class="pill-btn" onclick="openM('mClasses')">All →</button>
            </div>
            <div class="section-bd">
                <div class="row g-2">
                    @foreach(array_slice($class_capacity_data,0,12) as $cls)
                    @php
                        $u = $cls['utilization'];
                        $cc = $u >= 90 ? '#ef4444' : ($u >= 70 ? '#f59e0b' : '#059669');
                        $cbg = $u >= 90 ? '#fee2e2' : ($u >= 70 ? '#fef3c7' : '#dcfce7');
                    @endphp
                    <div class="col-xxl-3 col-md-4 col-6">
                        <div class="cls-tile" style="background:{{ $cbg }};" onclick="openM('mClasses')">
                            <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;">
                                {{ $cls['class_name'] }}@if($cls['arm']) <span style="font-size:9px;">· {{ $cls['arm'] }}</span>@endif
                            </div>
                            <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:{{ $cc }};margin:3px 0;">{{ $cls['students'] }}</div>
                            <div style="font-size:9.5px;color:#64748b;">of {{ $cls['capacity'] }} capacity</div>
                            <div style="height:3px;background:rgba(0,0,0,.08);border-radius:3px;margin-top:6px;overflow:hidden;">
                                <div style="width:{{ min(100,$u) }}%;height:100%;background:{{ $cc }};border-radius:3px;"></div>
                            </div>
                            <div style="font-size:9.5px;font-weight:700;color:{{ $cc }};margin-top:3px;">{{ $u }}%</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Activity + Quick Actions --}}
    <div class="col-xxl-5">
        <div class="row g-3 h-100">
            <div class="col-12">
                <div class="section">
                    <div class="section-hd">
                        <div class="section-title">Recent Activity</div>
                    </div>
                    <div class="section-bd" style="padding-top:12px;">
                        @forelse($recent_activities as $act)
                        <div class="tl-item">
                            <div class="tl-dot bg-{{ $act['color'] }} fg-{{ $act['color'] }}">
                                <i class="{{ $act['icon'] }}"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:600;font-size:12.5px;color:var(--c-text);">{{ $act['title'] }}</div>
                                <div style="font-size:11.5px;color:var(--c-sub);margin-top:1px;">{{ $act['description'] }}</div>
                                <div style="font-size:10.5px;color:var(--c-muted);margin-top:2px;"><i class="bi bi-clock me-1"></i>{{ $act['time'] }}</div>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted text-center py-2" style="font-size:12px;">No recent activities</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="section">
                    <div class="section-hd">
                        <div class="section-title">Quick Actions</div>
                    </div>
                    <div class="section-bd">
                        <div class="row g-2">
                            @php $qas = [
                                ['url'=>route('student.index'),      'icon'=>'ph-users-four',       'cls'=>'fg-indigo',  'label'=>'Students'],
                                ['url'=>route('exams.index'),         'icon'=>'ph-clipboard-text',   'cls'=>'fg-amber',   'label'=>'Exams'],
                                ['url'=>route('attendance.my-classes'),'icon'=>'ph-calendar-check', 'cls'=>'fg-sky',     'label'=>'Attendance'],
                                ['url'=>route('staff.payments.dashboard'),'icon'=>'ph-wallet',       'cls'=>'fg-emerald', 'label'=>'Payroll'],
                            ]; @endphp
                            @foreach($qas as $qa)
                            <div class="col-6">
                                <a href="{{ $qa['url'] }}" class="qa">
                                    <i class="{{ $qa['icon'] }} {{ $qa['cls'] }}" style="font-size:24px;"></i>
                                    <span style="font-size:12px;font-weight:600;color:var(--c-text);">{{ $qa['label'] }}</span>
                                </a>
                            </div>
                            @endforeach
                        </div>
                        <div style="margin-top:14px;padding:12px;background:#f8fafc;border-radius:10px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                <span style="font-size:12px;font-weight:600;color:var(--c-sub);">Fee Collection</span>
                                <span style="font-size:12px;font-weight:700;color:#4f5fff;">{{ $collection_rate }}%</span>
                            </div>
                            <div style="height:6px;background:#e2e8f0;border-radius:6px;overflow:hidden;">
                                <div style="width:{{ $collection_rate }}%;height:100%;background:linear-gradient(90deg,#4f5fff,#7c3aed);border-radius:6px;"></div>
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-top:5px;">
                                <span style="font-size:10px;color:var(--c-muted);">Collected ₦{{ number_format($total_payments) }}</span>
                                <span style="font-size:10px;color:var(--c-muted);">Target ₦{{ number_format($total_bills) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════════════════ --}}

{{-- Population Modal --}}
<div class="m-overlay" id="mPopulation" onclick="if(event.target===this)closeM('mPopulation')">
<div class="m-box" style="max-width:720px;">
    <div class="m-hd">
        <div class="m-title">Population Overview</div>
        <button class="m-close" onclick="closeM('mPopulation')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div class="kpi-grid mb-4">
            <div class="kpi"><div class="kpi-l">Total</div><div class="kpi-v">{{ number_format($total_population) }}</div></div>
            <div class="kpi"><div class="kpi-l">Male</div><div class="kpi-v" style="color:#0ea5e9;">{{ number_format($gender_counts['Male']) }}</div></div>
            <div class="kpi"><div class="kpi-l">Female</div><div class="kpi-v" style="color:#f43f5e;">{{ number_format($gender_counts['Female']) }}</div></div>
            <div class="kpi"><div class="kpi-l">Staff</div><div class="kpi-v" style="color:#d97706;">{{ number_format($staff_count) }}</div></div>
            <div class="kpi"><div class="kpi-l">New Students</div><div class="kpi-v" style="color:#059669;">{{ number_format($status_counts['New Student']??0) }}</div></div>
            <div class="kpi"><div class="kpi-l">Returning</div><div class="kpi-v" style="color:#4f5fff;">{{ number_format($status_counts['Old Student']??0) }}</div></div>
        </div>
        <div style="height:280px;"><canvas id="mPopChart"></canvas></div>
    </div>
</div>
</div>

{{-- Gender Modal --}}
<div class="m-overlay" id="mGender" onclick="if(event.target===this)closeM('mGender')">
<div class="m-box" style="max-width:560px;">
    <div class="m-hd">
        <div class="m-title">Gender Distribution</div>
        <button class="m-close" onclick="closeM('mGender')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div class="kpi-grid mb-4">
            <div class="kpi"><div class="kpi-l">Male</div><div class="kpi-v" style="color:#0ea5e9;">{{ $gender_counts['Male'] }}</div></div>
            <div class="kpi"><div class="kpi-l">Female</div><div class="kpi-v" style="color:#f43f5e;">{{ $gender_counts['Female'] }}</div></div>
            <div class="kpi"><div class="kpi-l">Ratio M:F</div><div class="kpi-v" style="font-size:16px;">{{ $gender_counts['Female']>0?round($gender_counts['Male']/$gender_counts['Female'],2):'—' }}:1</div></div>
        </div>
        <div style="height:260px;"><canvas id="mGenderChart"></canvas></div>
    </div>
</div>
</div>

{{-- Academic Modal --}}
<div class="m-overlay" id="mAcademic" onclick="if(event.target===this)closeM('mAcademic')">
<div class="m-box" style="max-width:960px;">
    <div class="m-hd">
        <div class="m-title">Academic Performance — Full Report</div>
        <button class="m-close" onclick="closeM('mAcademic')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div class="m-tabs" id="acadTabs">
            <button class="m-tab on" onclick="switchTab('acadTabs','aTab1')">By Class/Arm</button>
            <button class="m-tab" onclick="switchTab('acadTabs','aTab2')">Grade Distribution</button>
            <button class="m-tab" onclick="switchTab('acadTabs','aTab3')">Subject Analysis</button>
            <button class="m-tab" onclick="switchTab('acadTabs','aTab4')">Term Comparison</button>
        </div>
        <div class="m-panel on" id="aTab1">
            <div class="kpi-grid mb-3">
                @foreach(array_slice($academic_performance,0,6) as $p)
                <div class="kpi">
                    <div class="kpi-l">{{ $p['label'] }}</div>
                    <div class="kpi-v" style="font-size:17px;color:{{ $p['avg_score']>=60?'#059669':($p['avg_score']>=40?'#d97706':'#dc2626') }};">{{ $p['avg_score'] }}%</div>
                    <div class="mb mt-1"><div class="mb-fill" style="width:{{ $p['avg_score'] }}%;background:#4f5fff;"></div></div>
                </div>
                @endforeach
            </div>
            <div style="height:280px;"><canvas id="mAcadChart"></canvas></div>
        </div>
        <div class="m-panel" id="aTab2">
            <div style="height:320px;"><canvas id="mGradeChart"></canvas></div>
        </div>
        <div class="m-panel" id="aTab3">
            <div style="height:320px;"><canvas id="mSubjChart"></canvas></div>
        </div>
        <div class="m-panel" id="aTab4">
            <div style="height:320px;"><canvas id="mTermChart"></canvas></div>
        </div>
    </div>
</div>
</div>

{{-- Top Students Modal --}}
<div class="m-overlay" id="mTopStudents" onclick="if(event.target===this)closeM('mTopStudents')">
<div class="m-box">
    <div class="m-hd">
        <div class="m-title">Top Performing Students — {{ $selectedTerm?->term }} · {{ $selectedSession?->session }}</div>
        <button class="m-close" onclick="closeM('mTopStudents')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div class="kpi-grid mb-4">
            <div class="kpi"><div class="kpi-l">Ranked</div><div class="kpi-v">{{ $best_students['ranked'] ?? count($top_students) }}</div></div>
            @if(count($top_students)>0)
            <div class="kpi"><div class="kpi-l">Top Student</div><div class="kpi-v" style="font-size:14px;">{{ $top_students[0]['name'] }}</div></div>
            <div class="kpi"><div class="kpi-l">Top Avg</div><div class="kpi-v" style="color:#059669;">{{ $top_students[0]['average'] }}%</div></div>
            <div class="kpi"><div class="kpi-l">Top Class</div><div class="kpi-v" style="font-size:14px;">{{ $top_students[0]['class'] }} {{ $top_students[0]['arm'] }}</div></div>
            @endif
        </div>
        <table class="dt">
            <thead><tr><th>#</th><th>Student</th><th>Adm No</th><th>Class</th><th>Arm</th><th>Avg</th><th>Grade</th><th>Subjects</th></tr></thead>
            <tbody>
            @forelse($top_students as $i => $stu)
            @php
                $avg=$stu['average'];
                $spC=$avg>=75?'sp-A':($avg>=60?'sp-B':($avg>=50?'sp-C':($avg>=40?'sp-D':'sp-F')));
                $pic=!empty($stu['picture']) ? basename($stu['picture']) : null;
                $rk=(int)($stu['rank'] ?? $i+1);
            @endphp
            <tr>
                <td><span class="rank {{ $rk===1?'r1':($rk===2?'r2':($rk===3?'r3':'rn')) }}">{{ $rk }}</span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:7px;">
                        @if($pic&&$pic!=='unnamed.jpg')<img src="{{ asset('storage/student_avatars/'.$pic) }}" class="av" style="width:28px;height:28px;">
                        @else<div class="av" style="width:28px;height:28px;font-size:9px;">{{ strtoupper(substr($stu['name'],0,2)) }}</div>@endif
                        <span style="font-weight:500;font-size:12px;">{{ $stu['name'] }}</span>
                    </div>
                </td>
                <td style="font-size:11px;color:var(--c-muted);">{{ $stu['admission_no'] }}</td>
                <td style="font-weight:500;font-size:12px;">{{ $stu['class'] }}</td>
                <td><span style="background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:8px;font-size:10.5px;">{{ $stu['arm'] ?: '—' }}</span></td>
                <td><span class="sp {{ $spC }}">{{ $avg }}%</span></td>
                <td style="font-weight:700;font-size:12px;color:{{ $avg>=60?'#059669':($avg>=40?'#d97706':'#dc2626') }};">{{ $stu['grade'] }}</td>
                <td style="font-size:11px;color:var(--c-muted);">{{ $stu['subject_count'] }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-3">No data for this term</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="m-ft">
        <a href="{{ route('student.index') }}" class="btn btn-sm" style="background:#4f5fff;color:#fff;border-radius:8px;font-size:12px;padding:7px 16px;">View All Students</a>
    </div>
</div>
</div>

{{-- Subject Performance Modal --}}
<div class="m-overlay" id="mSubjectPerf" onclick="if(event.target===this)closeM('mSubjectPerf')">
<div class="m-box" style="max-width:960px;">
    <div class="m-hd">
        <div class="m-title">Subject Performance — Full Report</div>
        <button class="m-close" onclick="closeM('mSubjectPerf')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div style="height:260px;margin-bottom:14px;"><canvas id="mSubjPerfChart"></canvas></div>
        <table class="dt">
            <thead><tr><th>Subject</th><th>Teacher(s)</th><th>Avg</th><th>Highest</th><th>Lowest</th><th>Pass Rate</th><th>Entries</th></tr></thead>
            <tbody>
            @forelse($subject_performance as $sp)
            <tr>
                <td style="font-weight:500;font-size:12.5px;">{{ $sp['subject_name'] }}</td>
                <td>
                    <div style="display:flex;gap:3px;flex-wrap:wrap;">
                        @foreach(array_slice($sp['teachers']??[],0,3) as $t)
                        <div class="t-chip">
                            @if(!empty($t['avatar']))<img src="{{ asset('storage/'.$t['avatar']) }}" class="av" style="width:14px;height:14px;">
                            @else<div class="av" style="width:14px;height:14px;font-size:6px;">{{ strtoupper(substr($t['name'],0,2)) }}</div>@endif
                            <span>{{ Str::limit($t['name'],16) }}</span>
                        </div>
                        @endforeach
                        @if(empty($sp['teachers']))<span style="color:var(--c-muted);font-size:11px;">—</span>@endif
                    </div>
                </td>
                <td><span class="sp {{ $sp['avg_score']>=60?'sp-B':($sp['avg_score']>=40?'sp-C':'sp-F') }}">{{ $sp['avg_score'] }}%</span></td>
                <td style="color:#059669;font-weight:600;font-size:12px;">{{ $sp['max_score'] }}%</td>
                <td style="color:#dc2626;font-size:12px;">{{ $sp['min_score'] }}%</td>
                <td>
                    <div style="display:flex;align-items:center;gap:5px;">
                        <div class="mb" style="width:60px;"><div class="mb-fill" style="width:{{ $sp['pass_rate'] }}%;background:#10b981;"></div></div>
                        <span style="font-size:11px;font-weight:600;">{{ $sp['pass_rate'] }}%</span>
                    </div>
                </td>
                <td style="font-size:11px;color:var(--c-muted);">{{ $sp['total_entries'] }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-3 text-muted">No data</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

{{-- Teachers Modal --}}
<div class="m-overlay" id="mTeachers" onclick="if(event.target===this)closeM('mTeachers')">
<div class="m-box">
    <div class="m-hd">
        <div class="m-title">Teacher Performance — {{ $selectedTerm?->term }}</div>
        <button class="m-close" onclick="closeM('mTeachers')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div style="height:260px;margin-bottom:14px;"><canvas id="mTeacherChart"></canvas></div>
        <table class="dt">
            <thead><tr><th>#</th><th>Teacher</th><th>Subjects</th><th>Avg Score</th><th>Pass Rate</th><th>Students</th><th>Best Score</th></tr></thead>
            <tbody>
            @forelse($top_teachers as $idx => $t)
            <tr>
                <td><span class="rank {{ $idx===0?'r1':($idx===1?'r2':($idx===2?'r3':'rn')) }}">{{ $idx+1 }}</span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        @if(!empty($t['avatar']))<img src="{{ asset('storage/'.$t['avatar']) }}" class="av" style="width:30px;height:30px;">
                        @else<div class="av" style="width:30px;height:30px;font-size:10px;">{{ strtoupper(substr($t['name'],0,2)) }}</div>@endif
                        <span style="font-weight:500;font-size:12.5px;">{{ $t['name'] }}</span>
                    </div>
                </td>
                <td style="font-size:11px;color:var(--c-muted);">{{ implode(', ',array_slice($t['subjects'],0,3)) }}{{ count($t['subjects'])>3?'...':'' }}</td>
                <td>
                    @php $ac=$t['avg_score']>=70?'sp-A':($t['avg_score']>=55?'sp-B':($t['avg_score']>=40?'sp-C':'sp-F')); @endphp
                    <span class="sp {{ $ac }}">{{ $t['avg_score'] }}%</span>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:5px;">
                        <div class="mb" style="width:55px;"><div class="mb-fill" style="width:{{ $t['pass_rate'] }}%;background:#10b981;"></div></div>
                        <span style="font-size:11px;font-weight:600;">{{ $t['pass_rate'] }}%</span>
                    </div>
                </td>
                <td style="font-size:12px;font-weight:600;color:var(--c-sub);">{{ $t['student_count'] }}</td>
                <td style="font-size:12px;color:#059669;font-weight:600;">{{ $t['max_score'] }}%</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-3 text-muted">No teacher data for this term</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

{{-- Attendance Modal --}}
<div class="m-overlay" id="mAttendance" onclick="if(event.target===this)closeM('mAttendance')">
<div class="m-box" style="max-width:900px;">
    <div class="m-hd">
        <div class="m-title">Attendance — {{ $selectedTerm?->term }} · {{ $selectedSession?->session }}</div>
        <button class="m-close" onclick="closeM('mAttendance')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div class="m-tabs" id="attTabs">
            <button class="m-tab on" onclick="switchTab('attTabs','attTab1')">By Class/Arm</button>
            <button class="m-tab" onclick="switchTab('attTabs','attTab2')">Daily Trend</button>
        </div>
        <div class="m-panel on" id="attTab1">
            <div class="kpi-grid mb-3">
                <div class="kpi"><div class="kpi-l">Overall Rate</div><div class="kpi-v" style="color:{{ $overall_attendance_rate>=75?'#059669':($overall_attendance_rate>=50?'#d97706':'#dc2626') }};">{{ $overall_attendance_rate }}%</div></div>
                <div class="kpi"><div class="kpi-l">Classes Tracked</div><div class="kpi-v">{{ count($attendance_by_class) }}</div></div>
                @if(count($attendance_by_class)>0)
                <div class="kpi"><div class="kpi-l">Best Class</div><div class="kpi-v" style="font-size:14px;color:#059669;">{{ $attendance_by_class[0]['label'] }}</div></div>
                <div class="kpi"><div class="kpi-l">Best Rate</div><div class="kpi-v" style="color:#059669;">{{ $attendance_by_class[0]['rate'] }}%</div></div>
                @endif
            </div>
            <table class="dt">
                <thead><tr><th>Class</th><th>Arm</th><th>Students</th><th>Rate</th><th>Present</th><th>Absent</th><th>Late</th><th>School Days</th></tr></thead>
                <tbody>
                @forelse($attendance_by_class as $att)
                @php $rc=$att['rate']>=75?'#059669':($att['rate']>=50?'#f59e0b':'#ef4444'); @endphp
                <tr>
                    <td style="font-weight:600;font-size:12.5px;">{{ $att['class_name'] }}</td>
                    <td><span style="background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:8px;font-size:10.5px;">{{ $att['arm'] ?: '—' }}</span></td>
                    <td style="font-size:12px;">{{ $att['student_count'] }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div class="mb" style="width:60px;"><div class="mb-fill" style="width:{{ $att['rate'] }}%;background:{{ $rc }};"></div></div>
                            <span style="font-size:11.5px;font-weight:700;color:{{ $rc }};">{{ $att['rate'] }}%</span>
                        </div>
                    </td>
                    <td style="color:#059669;font-size:12px;font-weight:600;">{{ number_format($att['total_present']) }}</td>
                    <td style="color:#dc2626;font-size:12px;">{{ number_format($att['total_absent']) }}</td>
                    <td style="color:#d97706;font-size:12px;">{{ number_format($att['total_late']) }}</td>
                    <td style="font-size:12px;color:var(--c-muted);">{{ number_format($att['total_days']) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-3 text-muted">No attendance data for this term</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="m-panel" id="attTab2">
            <div style="height:320px;"><canvas id="mAttTrendChart"></canvas></div>
        </div>
    </div>
    <div class="m-ft">
        <a href="{{ route('attendance.my-classes') }}" class="btn btn-sm" style="background:#4f5fff;color:#fff;border-radius:8px;font-size:12px;padding:7px 16px;">Manage Attendance</a>
    </div>
</div>
</div>

{{-- Classes Modal --}}
<div class="m-overlay" id="mClasses" onclick="if(event.target===this)closeM('mClasses')">
<div class="m-box" style="max-width:900px;">
    <div class="m-hd">
        <div class="m-title">Classes & Enrollment</div>
        <button class="m-close" onclick="closeM('mClasses')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div class="kpi-grid mb-4">
            <div class="kpi"><div class="kpi-l">Total Classes</div><div class="kpi-v">{{ $total_classes }}</div></div>
            <div class="kpi"><div class="kpi-l">Term Enrolled</div><div class="kpi-v" style="color:#4f5fff;">{{ number_format($students_in_selected_term) }}</div></div>
            <div class="kpi"><div class="kpi-l">Avg / Class</div><div class="kpi-v">{{ $total_classes > 0 ? round($total_population/$total_classes) : 0 }}</div></div>
        </div>
        <table class="dt">
            <thead><tr><th>Class</th><th>Arm</th><th>Students (Session)</th><th>Capacity</th><th>Utilization</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($class_capacity_data as $cls)
            @php $u=$cls['utilization']; @endphp
            <tr>
                <td style="font-weight:600;font-size:12.5px;">{{ $cls['class_name'] }}</td>
                <td><span style="background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:8px;font-size:10.5px;">{{ $cls['arm'] ?: '—' }}</span></td>
                <td style="font-weight:600;">{{ $cls['students'] }}</td>
                <td style="color:var(--c-muted);">{{ $cls['capacity'] }}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div class="mb" style="width:70px;"><div class="mb-fill" style="width:{{ min(100,$u) }}%;background:{{ $u>=90?'#ef4444':($u>=70?'#f59e0b':'#059669') }};"></div></div>
                        <span style="font-size:11.5px;font-weight:600;">{{ $u }}%</span>
                    </div>
                </td>
                <td>
                    <span style="padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;background:{{ $u>=90?'#fee2e2':($u>=70?'#fef3c7':'#dcfce7') }};color:{{ $u>=90?'#dc2626':($u>=70?'#d97706':'#059669') }};">
                        {{ $u>=90?'Full':($u>=70?'High':'Optimal') }}
                    </span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>

        @if(!empty($students_by_class))
        <div style="margin-top:18px;">
            <div style="font-size:13px;font-weight:600;color:var(--c-text);margin-bottom:10px;">Students by Class ({{ $selectedTerm?->term }} · {{ $selectedSession?->session }})</div>
            <table class="dt">
                <thead><tr><th>Class</th><th>Arm</th><th>Enrolled</th></tr></thead>
                <tbody>
                @foreach($students_by_class as $sc)
                <tr>
                    <td style="font-weight:500;">{{ $sc['class_name'] }}</td>
                    <td style="color:var(--c-muted);font-size:11.5px;">{{ $sc['arm'] ?: '—' }}</td>
                    <td style="font-weight:700;color:#4f5fff;">{{ $sc['total'] }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
</div>

{{-- Staff Modal --}}
<div class="m-overlay" id="mStaff" onclick="if(event.target===this)closeM('mStaff')">
<div class="m-box">
    <div class="m-hd">
        <div class="m-title">Staff Directory</div>
        <button class="m-close" onclick="closeM('mStaff')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div class="kpi-grid mb-4">
            <div class="kpi"><div class="kpi-l">Total Staff</div><div class="kpi-v">{{ $staff_count }}</div></div>
            @foreach($staff_by_role as $role => $cnt)
            <div class="kpi"><div class="kpi-l">{{ ucfirst($role) }}</div><div class="kpi-v" style="font-size:18px;">{{ $cnt }}</div></div>
            @endforeach
        </div>
        <table class="dt">
            <thead><tr><th>Staff</th><th>Role</th><th>Joined</th></tr></thead>
            <tbody>
            @foreach(\App\Models\User::whereHas('roles',fn($q)=>$q->whereIn('name',['staff','teacher','admin']))->with('roles')->latest()->take(25)->get() as $st)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        @if($st->avatar)<img src="{{ asset('storage/'.$st->avatar) }}" class="av" style="width:28px;height:28px;">
                        @else<div class="av" style="width:28px;height:28px;font-size:9px;">{{ strtoupper(substr($st->name,0,2)) }}</div>@endif
                        <div>
                            <div style="font-weight:500;font-size:12.5px;color:var(--c-text);">{{ $st->name }}</div>
                            <div style="font-size:10.5px;color:var(--c-muted);">{{ $st->email }}</div>
                        </div>
                    </div>
                </td>
                <td><span style="background:#eef2ff;color:#4f5fff;font-size:10.5px;padding:2px 7px;border-radius:20px;">{{ $st->roles->first()?->name ?? 'Staff' }}</span></td>
                <td style="font-size:11px;color:var(--c-muted);">{{ $st->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>

{{-- Grades Modal --}}
<div class="m-overlay" id="mGrades" onclick="if(event.target===this)closeM('mGrades')">
<div class="m-box" style="max-width:620px;">
    <div class="m-hd">
        <div class="m-title">Grade Distribution — Detail</div>
        <button class="m-close" onclick="closeM('mGrades')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        @php
            $pass = array_sum(array_filter($grade_distribution??[], fn($v,$k)=>in_array($k,['A1','B2','B3','C4','C5','C6']),ARRAY_FILTER_USE_BOTH));
            $fail = array_sum(array_filter($grade_distribution??[], fn($v,$k)=>in_array($k,['D7','E8','F9']),ARRAY_FILTER_USE_BOTH));
        @endphp
        <div class="kpi-grid mb-4">
            <div class="kpi"><div class="kpi-l">Total Scores</div><div class="kpi-v">{{ number_format($totalGrades??0) }}</div></div>
            <div class="kpi"><div class="kpi-l">Pass (A1–C6)</div><div class="kpi-v" style="color:#059669;">{{ number_format($pass) }}</div></div>
            <div class="kpi"><div class="kpi-l">Fail (D7–F9)</div><div class="kpi-v" style="color:#dc2626;">{{ number_format($fail) }}</div></div>
            <div class="kpi"><div class="kpi-l">Pass Rate</div><div class="kpi-v" style="color:#4f5fff;">{{ ($pass+$fail)>0?round($pass/($pass+$fail)*100,1):0 }}%</div></div>
        </div>
        <div style="height:280px;"><canvas id="mGradeExpandChart"></canvas></div>
    </div>
</div>
</div>

{{-- Subjects Modal --}}
<div class="m-overlay" id="mSubjects" onclick="if(event.target===this)closeM('mSubjects')">
<div class="m-box">
    <div class="m-hd">
        <div class="m-title">Subjects & Teachers</div>
        <button class="m-close" onclick="closeM('mSubjects')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="m-body">
        <div class="kpi-grid mb-4">
            <div class="kpi"><div class="kpi-l">Total Subjects</div><div class="kpi-v">{{ $total_subjects }}</div></div>
        </div>
        <table class="dt">
            <thead><tr><th>Subject</th><th>Teachers Assigned</th></tr></thead>
            <tbody>
            @foreach(\App\Models\Subject::take(40)->get() as $sub)
            <tr>
                <td style="font-weight:500;font-size:12.5px;">{{ $sub->subject }}</td>
                <td>
                    @php
                        $subTeachers = \App\Models\User::whereHas('subjectTeachings',fn($q)=>$q->where('subjectid',$sub->id))->take(4)->get();
                    @endphp
                    <div style="display:flex;gap:3px;flex-wrap:wrap;">
                        @foreach($subTeachers as $t)
                        <div class="t-chip">
                            @if($t->avatar)<img src="{{ asset('storage/'.$t->avatar) }}" class="av" style="width:14px;height:14px;">
                            @else<div class="av" style="width:14px;height:14px;font-size:6px;">{{ strtoupper(substr($t->name,0,2)) }}</div>@endif
                            <span>{{ Str::limit($t->name,14) }}</span>
                        </div>
                        @endforeach
                        @if($subTeachers->isEmpty())<span style="font-size:11px;color:var(--c-muted);">Not assigned</span>@endif
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>

@endhasrole

</div>{{-- container --}}
</div>{{-- page-content --}}
</div>{{-- main-content --}}

{{-- ═══════════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════════════ --}}
<script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/* ── Data bridge ── */
const D = {
  genderM:    {{ $gender_counts['Male']   ?? 0 }},
  genderF:    {{ $gender_counts['Female'] ?? 0 }},
  staff:      {{ $staff_count ?? 0 }},
  perfLabels: @json(array_column($academic_performance??[], 'label')),
  perfAvg:    @json(array_column($academic_performance??[], 'avg_score')),
  perfMax:    @json(array_column($academic_performance??[], 'max_score')),
  perfMin:    @json(array_column($academic_performance??[], 'min_score')),
  perfStuds:  @json(array_column($academic_performance??[], 'student_count')),
  grades:     @json($grade_distribution ?? []),
  gradeKeys:  @json(array_keys($grade_distribution ?? [])),
  gradeVals:  @json(array_values($grade_distribution ?? [])),
  trends:     { m: @json(array_column($yearly_trends??[], 'month')), s: @json(array_column($yearly_trends??[], 'students')) },
  terms:      { labels: @json(array_column($all_terms_performance??[], 'term')), avgs: @json(array_column($all_terms_performance??[], 'avg_score')) },
  subjNames:  @json(array_column($subject_performance??[], 'subject_name')),
  subjAvgs:   @json(array_column($subject_performance??[], 'avg_score')),
  subjPass:   @json(array_column($subject_performance??[], 'pass_rate')),
  teachNames: @json(array_column($top_teachers??[], 'name')),
  teachAvgs:  @json(array_column($top_teachers??[], 'avg_score')),
  teachPass:  @json(array_column($top_teachers??[], 'pass_rate')),
  attLabels:  @json(array_column($attendance_by_class??[], 'label')),
  attRates:   @json(array_column($attendance_by_class??[], 'rate')),
  attTrendDates: @json(array_column($attendance_trend??[], 'date')),
  attTrendRates: @json(array_column($attendance_trend??[], 'rate')),
  attTrendAbs:   @json(array_column($attendance_trend??[], 'absent_count')),
};

const GRADE_COLORS = {
  A1:'#16a34a',B2:'#2563eb',B3:'#0284c7',
  C4:'#d97706',C5:'#ea580c',C6:'#dc2626',
  D7:'#9333ea',E8:'#be185d',F9:'#94a3b8'
};

/* ── Chart defaults ── */
Chart.defaults.font.family = "'Outfit', sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#94a3b8';

const charts = {};
function mk(id, cfg) {
  const el = document.getElementById(id);
  if (!el) return null;
  if (charts[id]) charts[id].destroy();
  charts[id] = new Chart(el.getContext('2d'), cfg);
  return charts[id];
}

/* ── Counter ── */
document.querySelectorAll('.counter').forEach(el => {
  const target = parseInt(el.dataset.t) || 0;
  if (!target) { el.textContent='0'; return; }
  let cur = 0, step = Math.max(1, Math.ceil(target/60));
  const t = setInterval(() => {
    cur = Math.min(cur+step, target);
    el.textContent = cur.toLocaleString();
    if (cur >= target) clearInterval(t);
  }, 16);
});

/* ── Performance chart ── */
let perfType = 'bar';
function buildPerf(type='bar') {
  perfType = type;
  mk('chartPerf', {
    type:'bar',
    data:{
      labels:D.perfLabels,
      datasets:[{
        label:'Avg Score (%)',
        data:D.perfAvg,
        backgroundColor:D.perfAvg.map(v=>v>=75?'rgba(5,150,105,.8)':v>=50?'rgba(79,95,255,.8)':'rgba(239,68,68,.7)'),
        borderRadius:6, borderSkipped:false,
      },{
        label:'Pass Threshold',
        data:D.perfLabels.map(()=>40),
        type:'line', borderColor:'#ef4444', borderDash:[5,4],
        borderWidth:1.5, pointRadius:0, fill:false,
      }]
    },
    options:{
      indexAxis: type==='line'?'x':'x',
      responsive:true, maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${c.raw}%`}}},
      scales:{
        y:{beginAtZero:true,max:100,grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:v=>v+'%'}},
        x:{grid:{display:false},ticks:{maxRotation:40,autoSkip:true}}
      },
      animation:{duration:800}
    }
  });
}
buildPerf();

function switchPerf(type, btn) {
  document.getElementById('perfBarBtn').classList.toggle('on', type==='bar');
  document.getElementById('perfLineBtn').classList.toggle('on', type==='line');
  buildPerf(type);
}

/* ── Grade distribution ── */
mk('chartGrades',{
  type:'bar',
  data:{
    labels:D.gradeKeys,
    datasets:[{
      label:'Students',
      data:D.gradeVals,
      backgroundColor:D.gradeKeys.map(g=>GRADE_COLORS[g]||'#94a3b8'),
      borderRadius:5,borderSkipped:false,
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.raw} students`}}},
    scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.04)'}},x:{grid:{display:false}}},
    animation:{duration:900}
  }
});

/* ── Population donut ── */
mk('chartPop',{
  type:'doughnut',
  data:{
    labels:['Male','Female','Staff'],
    datasets:[{
      data:[D.genderM,D.genderF,D.staff],
      backgroundColor:['#0ea5e9','#f43f5e','#d97706'],
      borderColor:'#fff',borderWidth:3,hoverOffset:10
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:false,cutout:'68%',
    plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>{const t=c.dataset.data.reduce((a,b)=>a+b,0);return ` ${c.label}: ${c.raw.toLocaleString()} (${t>0?Math.round(c.raw/t*100):0}%)`;}}}}
  }
});

/* ── Trend line ── */
mk('chartTrend',{
  type:'line',
  data:{
    labels:D.trends.m,
    datasets:[{
      label:'New Students',data:D.trends.s,
      borderColor:'#4f5fff',backgroundColor:'rgba(79,95,255,.07)',
      fill:true,tension:.42,pointRadius:3,pointBackgroundColor:'#4f5fff',
      pointBorderColor:'#fff',pointHoverRadius:6
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false}},
    scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.04)'}},x:{grid:{display:false},ticks:{maxRotation:40,autoSkip:true}}}
  }
});

/* ── Term comparison ── */
mk('chartTerms',{
  type:'bar',
  data:{
    labels:D.terms.labels,
    datasets:[{
      label:'Avg Score',data:D.terms.avgs,
      backgroundColor:D.terms.avgs.map(v=>v>=60?'rgba(5,150,105,.7)':v>=40?'rgba(79,95,255,.7)':'rgba(239,68,68,.7)'),
      borderRadius:6,borderSkipped:false
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.raw}%`}}},
    scales:{y:{beginAtZero:true,max:100,grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}
  }
});

/* ── MODAL SYSTEM ── */
let activeStack = [];
function openM(id) {
  const el = document.getElementById(id);
  if(!el) return;
  el.classList.add('open');
  document.body.style.overflow='hidden';
  activeStack.push(id);
  setTimeout(()=>initModalChart(id), 80);
}
function closeM(id) {
  document.getElementById(id)?.classList.remove('open');
  activeStack = activeStack.filter(x=>x!==id);
  if(!activeStack.length) document.body.style.overflow='';
}
document.addEventListener('keydown',e=>{
  if(e.key==='Escape'&&activeStack.length) closeM(activeStack[activeStack.length-1]);
});

/* ── Tab switcher ── */
function switchTab(groupId, panelId) {
  const tabs   = document.querySelectorAll(`#${groupId} .m-tab`);
  const panels = document.querySelectorAll('.m-panel');
  tabs.forEach((t,i)=>t.classList.toggle('on', t.getAttribute('onclick').includes(panelId)));
  panels.forEach(p=>p.classList.toggle('on', p.id===panelId));
  // init charts when tab becomes visible
  setTimeout(()=>initPanelCharts(panelId), 60);
}

/* ── Modal chart initializers ── */
const mInited = {};
function initModalChart(mId) {
  if(mInited[mId]) return;
  mInited[mId] = true;

  if(mId==='mPopulation') {
    mk('mPopChart',{type:'bar',data:{labels:['Male','Female','Staff'],datasets:[{data:[D.genderM,D.genderF,D.staff],backgroundColor:['#0ea5e9','#f43f5e','#d97706'],borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}}},animation:{duration:700}}});
  }
  if(mId==='mGender') {
    mk('mGenderChart',{type:'pie',data:{labels:['Male','Female'],datasets:[{data:[D.genderM,D.genderF],backgroundColor:['#0ea5e9','#f43f5e'],borderColor:'#fff',borderWidth:3,hoverOffset:10}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},animation:{animateScale:true,duration:800}}});
  }
  if(mId==='mAcademic') {
    mk('mAcadChart',{type:'bar',data:{labels:D.perfLabels,datasets:[{label:'Avg',data:D.perfAvg,backgroundColor:D.perfAvg.map(v=>v>=60?'#059669':v>=40?'#4f5fff':'#ef4444'),borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100}},animation:{duration:700}}});
  }
  if(mId==='mGrades') {
    mk('mGradeExpandChart',{type:'bar',data:{labels:D.gradeKeys,datasets:[{label:'Students',data:D.gradeVals,backgroundColor:D.gradeKeys.map(g=>GRADE_COLORS[g]||'#94a3b8'),borderRadius:6,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},animation:{duration:700}}});
  }
  if(mId==='mSubjectPerf') {
    mk('mSubjPerfChart',{type:'bar',data:{labels:D.subjNames,datasets:[{label:'Avg Score',data:D.subjAvgs,backgroundColor:D.subjAvgs.map(v=>v>=60?'#4f5fff':v>=40?'#f59e0b':'#ef4444'),borderRadius:6},{label:'Pass Rate',data:D.subjPass,type:'line',borderColor:'#10b981',borderWidth:2,fill:false,tension:.3,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100}},animation:{duration:700}}});
  }
  if(mId==='mTeachers') {
    mk('mTeacherChart',{type:'bar',data:{labels:D.teachNames,datasets:[{label:'Avg Score',data:D.teachAvgs,backgroundColor:D.teachAvgs.map(v=>v>=70?'#059669':v>=55?'#4f5fff':v>=40?'#f59e0b':'#ef4444'),borderRadius:6},{label:'Pass Rate',data:D.teachPass,type:'line',borderColor:'#10b981',borderWidth:2,fill:false,tension:.3,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100}},animation:{duration:700}}});
  }
  if(mId==='mAttendance') {
    mk('mAttTrendChart',{type:'line',data:{labels:D.attTrendDates,datasets:[{label:'Attendance Rate',data:D.attTrendRates,borderColor:'#059669',backgroundColor:'rgba(5,150,105,.08)',fill:true,tension:.4,pointRadius:3},{label:'Absent',data:D.attTrendAbs,borderColor:'#ef4444',borderDash:[4,3],pointRadius:2,fill:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true},x:{grid:{display:false}}},animation:{duration:700}}});
  }
}

function initPanelCharts(panelId) {
  if(panelId==='aTab2' && !mInited['aTab2']) {
    mInited['aTab2']=true;
    mk('mGradeChart',{type:'bar',data:{labels:D.gradeKeys,datasets:[{data:D.gradeVals,backgroundColor:D.gradeKeys.map(g=>GRADE_COLORS[g]||'#94a3b8'),borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},animation:{duration:600}}});
  }
  if(panelId==='aTab3' && !mInited['aTab3']) {
    mInited['aTab3']=true;
    mk('mSubjChart',{type:'bar',data:{labels:D.subjNames,datasets:[{label:'Avg',data:D.subjAvgs,backgroundColor:D.subjAvgs.map(v=>v>=60?'#4f5fff':v>=40?'#f59e0b':'#ef4444'),borderRadius:6},{label:'Pass%',data:D.subjPass,type:'line',borderColor:'#10b981',fill:false,tension:.3,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100}},animation:{duration:600}}});
  }
  if(panelId==='aTab4' && !mInited['aTab4']) {
    mInited['aTab4']=true;
    mk('mTermChart',{type:'bar',data:{labels:D.terms.labels,datasets:[{label:'Avg Score',data:D.terms.avgs,backgroundColor:D.terms.avgs.map(v=>v>=60?'#059669':v>=40?'#4f5fff':'#ef4444'),borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%'}}},animation:{duration:600}}});
  }
  if(panelId==='attTab2' && !mInited['attTab2']) {
    mInited['attTab2']=true;
    mk('mAttTrendChart',{type:'line',data:{labels:D.attTrendDates,datasets:[{label:'Attendance Rate',data:D.attTrendRates,borderColor:'#059669',backgroundColor:'rgba(5,150,105,.08)',fill:true,tension:.4,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true},x:{grid:{display:false}}},animation:{duration:600}}});
  }
}

/* ── Best students: By Class / By Arm tabs ── */
document.querySelectorAll('.bsx-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.bsx-tab').forEach(b => {
      const on = b === btn;
      b.classList.toggle('on', on);
      b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    document.querySelectorAll('.bsx-pane').forEach(p => { p.hidden = p.id !== btn.dataset.pane; });
  });
});

/* ── Reinit dropdown fix ── */
setTimeout(()=>{
  const btn = document.getElementById('page-header-user-dropdown');
  if(btn && typeof bootstrap !== 'undefined') {
    try { bootstrap.Dropdown.getInstance(btn)?.dispose(); new bootstrap.Dropdown(btn); } catch(e){}
  }
}, 300);
</script>
@endsection
