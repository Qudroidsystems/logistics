@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
/* ─────────────────────────────────────────────
   SCREEN STYLES
───────────────────────────────────────────── */
:root {
    --cb-navy:      #0f2342;
    --cb-teal:      #0d9488;
    --cb-sky:       #0ea5e9;
    --cb-amber:     #f59e0b;
    --cb-rose:      #f43f5e;
    --cb-green:     #22c55e;
    --cb-muted:     #64748b;
    --cb-border:    #e2e8f0;
    --cb-surface:   #f8fafc;
    --cb-white:     #ffffff;
    --cb-radius:    14px;
    --cb-shadow:    0 4px 16px rgba(15,35,66,.10);
    --cb-shadow-lg: 0 8px 32px rgba(15,35,66,.14);
    --cumave-color: #7c3aed;
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; background: #f1f5f9; }

/* ── Keyframes ── */
@keyframes fadeInUp    { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInDown  { from { opacity:0; transform:translateY(-22px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInLeft  { from { opacity:0; transform:translateX(-22px); } to { opacity:1; transform:translateX(0); } }
@keyframes fadeInRight { from { opacity:0; transform:translateX(22px); }  to { opacity:1; transform:translateX(0); } }
@keyframes scaleIn     { from { opacity:0; transform:scale(.88); } to { opacity:1; transform:scale(1); } }
@keyframes pulse       { 0%,100% { transform:scale(1); } 50% { transform:scale(1.06); } }
@keyframes shimmer     {
    0%   { background-position:-800px 0; }
    100% { background-position:800px 0; }
}
@keyframes slideInRight{ from { transform:translateX(110%); opacity:0; } to { transform:translateX(0); opacity:1; } }
@keyframes spin        { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
@keyframes popIn       { 0% { opacity:0; transform:scale(.7) translateY(12px); } 60% { transform:scale(1.04) translateY(-3px); } 100% { opacity:1; transform:scale(1) translateY(0); } }
@keyframes floatUp     { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-6px); } }
@keyframes glowPulse   { 0%,100% { box-shadow:0 0 0 0 rgba(13,148,136,.4); } 50% { box-shadow:0 0 0 8px rgba(13,148,136,0); } }
@keyframes progressFill{ from { width:0; } }
@keyframes barGrow     { from { transform:scaleX(0); transform-origin:left; } to { transform:scaleX(1); transform-origin:left; } }
@keyframes countUp     { from { opacity:0; transform:scale(.6); } to { opacity:1; transform:scale(1); } }
@keyframes rowSlide    { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:translateX(0); } }
@keyframes backdropIn  { from { opacity:0; } to { opacity:1; } }

/* ── Grade Basis Toggle Bar ── */
.grade-basis-bar {
    background: #fff;
    border: 1px solid var(--cb-border);
    border-radius: var(--cb-radius);
    padding: 14px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: var(--cb-shadow);
    animation: fadeInUp .5s ease .1s both;
}
.grade-basis-bar .mode-label { font-size:13px; font-weight:600; color:var(--cb-navy); white-space:nowrap; }
.grade-basis-toggle {
    display: flex;
    background: var(--cb-surface);
    border: 1.5px solid var(--cb-border);
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.grade-basis-toggle .basis-btn {
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    background: transparent;
    color: var(--cb-muted);
    cursor: pointer;
    transition: all .2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}
.grade-basis-toggle .basis-btn:hover { background:#e9ecef; color:var(--cb-navy); }
.grade-basis-toggle .basis-btn.active-cumave { background:linear-gradient(135deg,var(--cumave-color),#a855f7); color:#fff; }
.grade-basis-toggle .basis-btn.active-total  { background:linear-gradient(135deg,#0891b2,#06b6d4); color:#fff; }
.basis-hint {
    font-size:12px; color:var(--cb-muted);
    background:var(--cb-surface);
    border:1px dashed var(--cb-border);
    border-radius:6px; padding:5px 10px;
}
.basis-hint strong { color:var(--cb-navy); }

/* ── Cum Ave Badge ── */
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

/* ── Hero ── */
.cb-hero {
    background: linear-gradient(135deg, var(--cb-navy) 0%, #1e4a7e 55%, #0d9488 100%);
    border-radius: var(--cb-radius);
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    animation: fadeInDown .6s cubic-bezier(.22,1,.36,1) both;
}
.cb-hero::before {
    content:'';
    position:absolute; top:-80px; right:-80px;
    width:280px; height:280px;
    background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);
    border-radius:50%;
    animation:floatUp 6s ease-in-out infinite;
}
.cb-hero::after {
    content:'';
    position:absolute; bottom:-60px; left:-60px;
    width:150px; height:150px;
    background:radial-gradient(circle,rgba(255,255,255,.05) 0%,transparent 70%);
    border-radius:50%;
    animation:floatUp 8s ease-in-out infinite reverse;
}
.cb-hero h1 { font-family:'Playfair Display',serif; font-size:26px; font-weight:700; color:#fff; margin:0 0 8px; }
.cb-hero p  { font-size:13px; color:rgba(255,255,255,.72); margin:0; }
.cb-hero .meta-pills { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
.cb-meta-pill {
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
    border-radius:20px; padding:4px 14px; font-size:12px; font-weight:600; color:#fff;
    display:inline-flex; align-items:center; gap:5px;
    transition:all .3s ease; animation:fadeInUp .5s ease both;
}
.cb-meta-pill:nth-child(1){animation-delay:.15s}
.cb-meta-pill:nth-child(2){animation-delay:.25s}
.cb-meta-pill:nth-child(3){animation-delay:.35s}
.cb-meta-pill:hover { background:rgba(255,255,255,.22); transform:translateY(-2px); }
.btn-back {
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
    border-radius:10px; padding:8px 18px; color:#fff; font-size:12px; font-weight:600;
    text-decoration:none; display:inline-flex; align-items:center; gap:8px;
    transition:all .3s ease; animation:fadeInRight .5s ease .2s both;
}
.btn-back:hover { background:rgba(255,255,255,.22); color:#fff; transform:translateX(-4px); }

/* ── Stats ── */
.cb-stat {
    background:var(--cb-white); border:1px solid var(--cb-border);
    border-radius:var(--cb-radius); padding:20px 22px;
    position:relative; overflow:hidden;
    transition:all .35s cubic-bezier(.22,1,.36,1);
    animation:scaleIn .5s cubic-bezier(.22,1,.36,1) both;
}
.cb-stat:nth-child(1){animation-delay:.08s}
.cb-stat:nth-child(2){animation-delay:.16s}
.cb-stat:nth-child(3){animation-delay:.24s}
.cb-stat:nth-child(4){animation-delay:.32s}
.cb-stat:hover { transform:translateY(-6px) scale(1.01); box-shadow:var(--cb-shadow-lg); }
.cb-stat .stat-accent { position:absolute; top:0; left:0; right:0; height:3px; border-radius:var(--cb-radius) var(--cb-radius) 0 0; }
.cb-stat .stat-value  { font-size:30px; font-weight:700; color:var(--cb-navy); line-height:1; margin-top:8px; animation:countUp .6s ease both; animation-delay:.4s; }
.cb-stat .stat-label  { font-size:12px; color:var(--cb-muted); margin-top:5px; font-weight:500; }
.cb-stat .stat-ico    { font-size:36px; opacity:.08; position:absolute; right:16px; top:50%; transform:translateY(-50%); }
.stat-pct-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:6px; }
.stat-pct-pill {
    display:inline-flex; align-items:center; gap:4px;
    font-size:11px; font-weight:700;
    padding:2px 10px; border-radius:20px;
}
.stat-pct-term { background:rgba(14,165,233,.12); color:#0369a1; }
.stat-pct-cum  { background:rgba(15,35,66,.08);  color:var(--cb-navy); }
.stat-pct-cumave { background:rgba(124,58,237,.12); color:#5b21b6; }

/* ── Column Toggle Panel ── */
.col-toggle-panel {
    background:var(--cb-white); border:1px solid var(--cb-border);
    border-radius:var(--cb-radius); padding:18px 22px; margin-bottom:22px;
    box-shadow:var(--cb-shadow); animation:fadeInLeft .5s ease .1s both;
}
.col-toggle-panel h6 { font-size:13px; font-weight:700; color:var(--cb-navy); margin:0 0 14px; display:flex; align-items:center; gap:7px; }
.toggle-chips { display:flex; flex-wrap:wrap; gap:8px; }
.toggle-chip {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600;
    cursor:pointer; border:1.5px solid var(--cb-border); background:var(--cb-surface); color:var(--cb-muted);
    transition:all .25s cubic-bezier(.22,1,.36,1); user-select:none;
}
.toggle-chip:hover { border-color:var(--cb-teal); color:var(--cb-teal); transform:translateY(-2px) scale(1.03); }
.toggle-chip.active { background:var(--cb-teal); border-color:var(--cb-teal); color:#fff; box-shadow:0 2px 10px rgba(13,148,136,.35); }

/* ── Card & Table ── */
.cb-card {
    background:var(--cb-white); border:1px solid var(--cb-border);
    border-radius:var(--cb-radius); box-shadow:var(--cb-shadow);
    overflow:visible;
    animation:fadeInUp .5s ease .2s both;
}
.cb-card-header {
    padding:18px 24px; border-bottom:1px solid var(--cb-border);
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    background:linear-gradient(to right,#f8fafc,#f0fdf9);
    border-radius:var(--cb-radius) var(--cb-radius) 0 0;
}

.cb-table-scroll { overflow-x:auto; overflow-y:visible; }
.cb-table { width:100%; border-collapse:collapse; font-size:12.5px; }
.cb-table thead th {
    background:var(--cb-navy); color:#fff; padding:11px 14px;
    font-weight:600; font-size:11.5px; white-space:nowrap;
    text-align:center; border-right:1px solid rgba(255,255,255,.08);
    position:sticky; top:0; z-index:2;
}
.cb-table thead th.col-name-hdr { text-align:left; }
.cb-table tbody td { padding:10px 14px; vertical-align:middle; border-bottom:1px solid var(--cb-border); }
.cb-table tbody td.td-name { text-align:left; }
.cb-table tbody tr {
    transition:all .25s ease;
    animation:rowSlide .4s ease both;
}
.cb-table tbody tr:nth-child(1){animation-delay:.05s}
.cb-table tbody tr:nth-child(2){animation-delay:.08s}
.cb-table tbody tr:nth-child(3){animation-delay:.11s}
.cb-table tbody tr:nth-child(4){animation-delay:.14s}
.cb-table tbody tr:nth-child(5){animation-delay:.17s}
.cb-table tbody tr:nth-child(n+6){animation-delay:.20s}
.cb-table tbody tr:hover td { background:#f0fdf9; }

/* ── Position Badge ── */
.pos-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    font-size: 14px;
    font-weight: 800;
    border: 2px solid;
    transition: all .3s cubic-bezier(.22,1,.36,1);
}
.pos-badge:hover { transform: scale(1.18) rotate(-5deg); }
.pos-1 { background: linear-gradient(135deg,#fef9c3,#fde68a); border-color: #f59e0b; color: #92400e; box-shadow: 0 2px 8px rgba(245,158,11,.35); }
.pos-2 { background: linear-gradient(135deg,#f1f5f9,#e2e8f0); border-color: #94a3b8; color: #475569; box-shadow: 0 2px 8px rgba(148,163,184,.25); }
.pos-3 { background: linear-gradient(135deg,#ffedd5,#fed7aa); border-color: #f97316; color: #9a3412; box-shadow: 0 2px 8px rgba(249,115,22,.25); }
.pos-other { background: var(--cb-surface); border-color: var(--cb-border); color: var(--cb-muted); font-size: 12px; width: 38px; height: 38px; }

/* ── Score / Grade Cells ── */
.score-dual { display:flex; flex-direction:column; gap:2px; min-width:80px; }
.score-row {
    display:flex; align-items:center; justify-content:center; gap:4px;
    padding:2px 5px; border-radius:5px; font-size:11px; font-weight:700;
    transition:all .2s ease;
}
.score-row-term { background:rgba(14,165,233,.08); border-left:2.5px solid #0ea5e9; }
.score-row-cum  { background:rgba(15,35,66,.06);  border-left:2.5px solid var(--cb-navy); }
.score-row-cumave { background:rgba(124,58,237,.08); border-left:2.5px solid #7c3aed; }
.score-row:hover { transform:translateX(3px); }
.score-lbl { font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; opacity:.7; }

.grade-badge {
    display:inline-block; padding:1px 5px; border-radius:8px;
    font-size:9px; font-weight:700; transition:all .2s ease;
}
.grade-badge:hover { transform:scale(1.15); }
.g-a,.g-a1             { background:#dcfce7; color:#15803d; }
.g-b,.g-b2,.g-b3       { background:#dbeafe; color:#1d4ed8; }
.g-c,.g-c4,.g-c5,.g-c6 { background:#fef9c3; color:#a16207; }
.g-d,.g-d7             { background:#ffedd5; color:#c2410c; }
.g-e,.g-e8             { background:#ffe4e6; color:#be123c; }
.g-f,.g-f9             { background:#fee2e2; color:#b91c1c; }
.score-red   { color:#dc2626 !important; }
.score-amber { color:#d97706 !important; }
.score-green { color:#16a34a !important; }
.score-purple { color:#7c3aed !important; }

/* ── Analytics Cell ── */
.analytics-cell { min-width:200px; font-size:11px; line-height:1.4; }
.analytics-row  { display:flex; justify-content:space-between; align-items:center; padding:3px 0; gap:6px; }
.analytics-lbl  { color:var(--cb-muted); font-size:10px; font-weight:500; }
.analytics-val  { font-weight:700; color:var(--cb-navy); font-size:11.5px; }

.analytics-percentage {
    font-weight:800; font-size:12px;
    display:inline-block;
    animation:countUp .6s ease both;
}

.pct-bar-wrap { background:#e2e8f0; border-radius:4px; height:6px; margin-top:3px; overflow:hidden; }
.pct-bar {
    height:100%; border-radius:4px;
    background:#f43f5e;
    transition: background-color 1s ease;
    animation:progressFill .8s ease both;
}

/* ── Grade Popup Trigger ── */
.grade-trigger-btn {
    background:none; border:none; cursor:pointer;
    color:var(--cb-sky); font-size:17px;
    padding:5px 8px; border-radius:8px;
    transition:all .25s ease;
    position:relative; z-index:1;
}
.grade-trigger-btn:hover {
    color:#fff; background:var(--cb-teal);
    transform:scale(1.15);
    box-shadow:0 3px 10px rgba(13,148,136,.4);
    animation:glowPulse .8s ease infinite;
}

/* ── Inputs ── */
.cb-input {
    border:1.5px solid var(--cb-border); border-radius:8px; padding:6px 10px;
    font-size:12px; width:100%; transition:all .25s ease;
    background:var(--cb-surface); font-family:'DM Sans',sans-serif;
}
.cb-input:focus { border-color:var(--cb-teal); outline:none; box-shadow:0 0 0 3px rgba(13,148,136,.12); background:#fff; transform:translateX(2px); }
.cb-input.has-value { border-left-color:var(--cb-teal); background:#f0fdf9; }
.absence-input { width:70px !important; text-align:center; margin:0 auto; display:block; }

/* ── Student Name Cell ── */
.student-name-cell { display:flex; align-items:center; gap:9px; }
.cb-avatar {
    width:38px; height:38px; border-radius:50%; overflow:hidden;
    flex-shrink:0; border:2px solid var(--cb-border); cursor:pointer;
    transition:all .3s cubic-bezier(.22,1,.36,1);
    display:flex; align-items:center; justify-content:center;
}
.cb-avatar:hover { border-color:var(--cb-teal); transform:scale(1.12) rotate(-3deg); box-shadow:0 4px 14px rgba(13,148,136,.25); }
.cb-avatar img { width:100%; height:100%; object-fit:cover; }
.cb-avatar-initials { background:linear-gradient(135deg,var(--cb-teal),var(--cb-sky)); color:#fff; font-size:13px; font-weight:700; }
.student-name-text { font-weight:700; font-size:13px; color:var(--cb-navy); }
.student-adm { font-size:10.5px; color:var(--cb-muted); margin-top:1px; }

/* ── Autosave Chip ── */
.autosave-chip {
    display:inline-flex; align-items:center; gap:4px;
    font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;
    margin-top:3px; transition:all .3s ease;
}
.ac-idle    { background:#f1f5f9; color:#94a3b8; }
.ac-saving  { background:#fef3c7; color:#92400e; animation:pulse .9s ease-in-out infinite; }
.ac-saved   { background:#dcfce7; color:#15803d; }
.ac-err     { background:#ffe4e6; color:#be123c; }

/* ── Comment Status Dot ── */
.comment-status-dot {
    display:inline-flex; align-items:center; gap:3px;
    font-size:10px; font-weight:700; padding:1px 7px; border-radius:20px;
    margin-top:2px; transition:all .3s ease;
}
.dot-saved   { background:#dcfce7; color:#15803d; }
.dot-unsaved { background:#f1f5f9; color:#94a3b8; }

/* ── Save Bar ── */
.save-bar {
    position:sticky; bottom:0; z-index:100;
    background:rgba(15,35,66,.97); backdrop-filter:blur(10px);
    border-top:2px solid var(--cb-teal); padding:14px 24px;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
    border-radius:0 0 var(--cb-radius) var(--cb-radius);
    animation:fadeInUp .5s ease .3s both;
}
.save-bar label { color:rgba(255,255,255,.7); font-size:12px; font-weight:500; margin-bottom:0; }
.file-input-styled {
    padding:6px 12px; border-radius:8px; border:1.5px solid rgba(255,255,255,.2);
    background:rgba(255,255,255,.08); color:#fff; font-size:12px; cursor:pointer; transition:all .3s ease;
}
.file-input-styled:hover { border-color:var(--cb-teal); transform:translateY(-2px); }
.btn-save-all {
    background:var(--cb-teal); color:#fff; border:none; border-radius:10px;
    padding:10px 28px; font-size:14px; font-weight:700; cursor:pointer;
    transition:all .3s cubic-bezier(.22,1,.36,1);
    display:flex; align-items:center; gap:8px; font-family:'DM Sans',sans-serif;
}
.btn-save-all:hover { background:#0b7c72; transform:translateY(-3px); box-shadow:0 8px 22px rgba(13,148,136,.45); }
.btn-save-all:active{ transform:translateY(0); }

/* ── Print Button ── */
.btn-print {
    background:rgba(255,255,255,.12); border:1.5px solid rgba(255,255,255,.25);
    border-radius:10px; padding:9px 20px; color:#fff; font-size:13px; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; gap:8px;
    transition:all .3s ease; font-family:'DM Sans',sans-serif;
}
.btn-print:hover { background:rgba(255,255,255,.22); transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.2); }

/* ── Toast ── */
.cb-toast {
    position:fixed; bottom:80px; right:24px; min-width:300px; z-index:99999;
    padding:14px 18px; border-radius:12px; display:flex; align-items:center; gap:10px;
    font-size:13px; font-weight:600; box-shadow:var(--cb-shadow-lg);
    animation:slideInRight .3s cubic-bezier(.22,1,.36,1);
}
.cb-toast-success { background:#ecfdf5; border:1.5px solid #86efac; color:#15803d; }
.cb-toast-error   { background:#fff1f2; border:1.5px solid #fca5a5; color:#be123c; }
.cb-toast-info    { background:#eff6ff; border:1.5px solid #93c5fd; color:#1d4ed8; }

/* ── Search ── */
.cb-search { position:relative; }
.cb-search input {
    width:100%; padding:9px 14px 9px 38px; border:1.5px solid var(--cb-border); border-radius:10px;
    font-size:13px; background:var(--cb-surface); font-family:'DM Sans',sans-serif; transition:all .25s ease;
}
.cb-search input:focus { border-color:var(--cb-teal); outline:none; box-shadow:0 0 0 3px rgba(13,148,136,.1); transform:translateX(2px); }
.cb-search i { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--cb-muted); pointer-events:none; }

/* ── Image Zoom Modal ── */
#cbImgZoomModal .modal-content { background:transparent; border:none; box-shadow:none; }
#cbImgZoomModal .modal-dialog  { max-width:92vw; }
.cb-zoomed-img { max-width:88vw; max-height:70vh; border-radius:16px; border:4px solid #fff; box-shadow:0 24px 60px rgba(0,0,0,.4); object-fit:contain; animation:scaleIn .3s ease; }
.cb-zoom-close { position:absolute; top:16px; right:16px; background:rgba(0,0,0,.5); border:none; color:#fff; width:36px; height:36px; border-radius:50%; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:10; transition:all .2s ease; }
.cb-zoom-close:hover { background:rgba(0,0,0,.8); transform:rotate(90deg) scale(1.1); }
.cb-zoom-name { margin-top:16px; font-size:18px; font-weight:700; color:#fff; text-align:center; text-shadow:0 2px 8px rgba(0,0,0,.5); }
.cb-zoom-meta { margin-top:6px; font-size:13px; color:rgba(255,255,255,.75); text-align:center; }

/* ── Grade Popup ── */
#cbGradePopup {
    display:none; position:fixed; z-index:99999;
    background:var(--cb-white); border:2px solid var(--cb-teal);
    border-radius:16px; box-shadow:0 20px 60px rgba(15,35,66,.22);
    width:480px; max-height:600px; overflow:hidden; flex-direction:column;
}
#cbGradePopup.is-open { display:flex; animation:popIn .28s cubic-bezier(.22,1,.36,1); }
.gpop-hdr {
    background:linear-gradient(135deg, var(--cb-navy), var(--cb-teal));
    color:#fff; padding:13px 18px; border-radius:14px 14px 0 0;
    font-weight:700; font-size:14px;
    display:flex; justify-content:space-between; align-items:center; flex-shrink:0;
}
.gpop-close-btn { background:rgba(255,255,255,.18); border:none; color:#fff; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all .25s ease; }
.gpop-close-btn:hover { background:rgba(255,255,255,.4); transform:rotate(90deg) scale(1.1); }
.gpop-body { padding:16px; overflow-y:auto; flex:1; }
.gpop-legend { display:flex; align-items:center; gap:12px; margin-bottom:10px; padding:6px 10px; background:var(--cb-surface); border-radius:8px; border:1px solid var(--cb-border); flex-wrap:wrap; }
.gpop-legend-item { display:flex; align-items:center; gap:4px; font-size:10px; font-weight:700; color:var(--cb-muted); }
.gpop-legend-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.gpop-legend-dot.t { background:#0ea5e9; }
.gpop-legend-dot.c { background:var(--cb-navy); }
.gpop-legend-dot.ca { background:#7c3aed; }
.gpop-scroll { max-height:280px; overflow-y:auto; border:1px solid var(--cb-border); border-radius:10px; }
.gpop-table { width:100%; border-collapse:collapse; font-size:12px; table-layout:fixed; }
.gpop-table thead th { background:var(--cb-navy); color:#fff; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; padding:9px 8px; border-right:1px solid rgba(255,255,255,.08); text-align:center; position:sticky; top:0; z-index:2; }
.gpop-table thead th:first-child { text-align:left; padding-left:12px; width:32%; }
.gpop-table thead th.col-score { width:28%; }
.gpop-table thead th.col-grade { width:40%; }
.gpop-table tbody td { padding:8px 6px; border-bottom:1px solid #f1f5f9; font-weight:500; text-align:center; vertical-align:middle; }
.gpop-table tbody td:first-child { text-align:left; font-weight:600; color:var(--cb-navy); padding-left:12px; }
.gpop-table tbody tr:hover td { background:#f0fdf9; }
.score-pair { display:flex; flex-direction:column; gap:2px; }
.score-cell { display:flex; align-items:center; justify-content:center; gap:3px; padding:2px 4px; border-radius:4px; font-size:11px; font-weight:700; }
.score-cell.term { background:rgba(14,165,233,.08); border-left:2px solid #0ea5e9; }
.score-cell.cum  { background:rgba(15,35,66,.06);   border-left:2px solid var(--cb-navy); }
.score-cell.cumave { background:rgba(124,58,237,.08); border-left:2px solid #7c3aed; }
.gpop-summary { background:linear-gradient(135deg,#f8fafc,#f0fdf9); border-radius:12px; padding:12px; margin-top:14px; display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.gpop-sum-item { text-align:center; padding:10px 6px; border-radius:10px; background:white; transition:all .2s ease; border:1px solid #e2e8f0; }
.gpop-sum-item:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.09); border-color:var(--cb-teal); }
.gpop-sum-lbl { font-size:9px; color:var(--cb-muted); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; font-weight:600; line-height:1.4; }
.gpop-sum-val { font-size:17px; font-weight:800; color:var(--cb-navy); }
.gpop-sum-val.score-red   { color:#dc2626; }
.gpop-sum-val.score-amber { color:#d97706; }
.gpop-sum-val.score-green { color:#16a34a; }
.gpop-sum-val.score-purple { color:#7c3aed; }
#cbPopupBackdrop { display:none; position:fixed; inset:0; z-index:99998; background:rgba(0,0,0,.3); animation:backdropIn .2s ease; }

/* ── Print ── */
@media print {
    .cb-hero::before, .cb-hero::after,
    .col-toggle-panel, .cb-card-header .cb-search,
    .save-bar, .cb-toast, .grade-trigger-btn,
    .autosave-chip, .comment-status-dot, .btn-back,
    nav, header, footer, .sidebar, .navbar,
    #cbGradePopup, #cbPopupBackdrop, #cbImgZoomModal,
    .mobile-only, .ri-eye-line, .pct-bar-wrap,
    .grade-basis-bar .basis-btn, .grade-basis-toggle { display:none !important; }

    .grade-basis-bar .mode-label { display:none !important; }
    .basis-hint { display:block !important; border:none !important; background:transparent !important; }

    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { background:#fff !important; font-family:'DM Sans',sans-serif; font-size:10px; }
    .main-content, .page-content, .container-fluid { padding:0 !important; margin:0 !important; }

    .cb-hero { background: linear-gradient(135deg, var(--cb-navy) 0%, #1e4a7e 55%, #0d9488 100%) !important; border-radius:8px !important; padding:14px 18px !important; margin-bottom:14px !important; animation:none !important; page-break-inside: avoid; }
    .cb-hero h1 { font-size:16px !important; }
    .cb-hero p  { font-size:10px !important; }
    .cb-meta-pill { font-size:9px !important; padding:2px 8px !important; animation:none !important; }

    .cb-stat { animation:none !important; box-shadow:none !important; border:1px solid #e2e8f0 !important; break-inside:avoid; }
    .cb-stat .stat-value { font-size:20px !important; }

    .cb-card { box-shadow:none !important; border:1px solid #cbd5e1 !important; animation:none !important; break-inside:auto; }
    .cb-card-header { background:#f8fafc !important; padding:10px 14px !important; border-radius:0 !important; }
    .cb-card-header h5 { font-size:12px !important; }

    .cb-table-scroll { overflow:visible !important; }
    .cb-table { font-size:9px !important; }
    .cb-table thead th { background:var(--cb-navy) !important; color:#fff !important; font-size:8px !important; padding:6px 8px !important; position:static !important; }
    .cb-table tbody td { padding:5px 8px !important; }
    .cb-table tbody tr { animation:none !important; }
    .cb-table tbody tr:hover td { background:transparent !important; }

    .cb-avatar { width:28px !important; height:28px !important; border:1.5px solid #e2e8f0 !important; }
    .cb-avatar-initials { font-size:10px !important; }
    .student-name-text { font-size:9px !important; }
    .student-adm { font-size:8px !important; }

    .score-dual { min-width:60px !important; gap:1px !important; }
    .score-row { font-size:9px !important; padding:1px 3px !important; }
    .grade-badge { font-size:7px !important; padding:0 3px !important; }

    .analytics-cell { min-width:120px !important; font-size:9px !important; }
    .analytics-row { padding:1px 0 !important; }
    .pct-bar-wrap { display:none !important; }
    .analytics-percentage { font-size:10px !important; }

    .cb-input { border:none !important; background:transparent !important; padding:0 !important; font-size:9px !important; box-shadow:none !important; }
    .absence-input { width:40px !important; }

    .pos-badge { width:28px !important; height:28px !important; font-size:10px !important; border-width:1.5px !important; }

    .desktop-only { display:block !important; }
    .mobile-only  { display:none !important; }

    @page {
        margin: 1.5cm 1.2cm;
        @bottom-center { content: "Class Broadsheet — Confidential | Page " counter(page) " of " counter(pages); font-size:8pt; color:#64748b; }
    }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

{{-- Hero --}}
<div class="cb-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1><i class="ri-clipboard-line me-2"></i>Class Broadsheet</h1>
            <p>Review student performance, assign comments, and track attendance for your class.</p>
            <div class="meta-pills">
                <span class="cb-meta-pill"><i class="ri-building-line"></i>{{ $schoolclass ? $schoolclass->schoolclass . ' ' . $schoolclass->arm : 'N/A' }}</span>
                <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $schoolterm }}</span>
                <span class="cb-meta-pill"><i class="ri-calendar-event-line"></i>{{ $schoolsession }}</span>
                <span class="cb-meta-pill" style="background:rgba(124,58,237,.2);border-color:rgba(124,58,237,.4);">
                    <i class="ri-bar-chart-line"></i> Cum Ave: {{ $avgCumAvePercentage ?? 0 }}%
                </span>
            </div>
        </div>
        <a href="{{ route('myclass.index') }}" class="btn-back"><i class="ri-arrow-left-line"></i> Back to My Classes</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-navy),var(--cb-teal));"></div>
            <div class="stat-ico"><i class="ri-group-line"></i></div>
            <div class="stat-value">{{ $students->count() }}</div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-sky),#38bdf8);"></div>
            <div class="stat-ico"><i class="ri-book-open-line"></i></div>
            <div class="stat-value text-info">{{ $subjects->count() }}</div>
            <div class="stat-label">Subjects</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-green),#4ade80);"></div>
            <div class="stat-ico"><i class="ri-percent-line"></i></div>
            <div class="stat-value text-success" id="statAvgTermPct">{{ $avgTermPercentage ?? 0 }}%</div>
            <div class="stat-label">Average % Obtained</div>
            <div class="stat-pct-row mt-2">
                <span class="stat-pct-pill stat-pct-term"><i class="ri-time-line me-1"></i>Term: {{ $avgTermPercentage ?? 0 }}%</span>
                <span class="stat-pct-pill stat-pct-cum"><i class="ri-history-line me-1"></i>Cum: {{ $avgCumPercentage ?? 0 }}%</span>
                <span class="stat-pct-pill stat-pct-cumave" style="background:rgba(124,58,237,.12);color:#5b21b6;"><i class="ri-bar-chart-line me-1"></i>Cum Ave: {{ $avgCumAvePercentage ?? 0 }}%</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cb-stat" id="topPerformerStat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-amber),#fcd34d);"></div>
            <div class="stat-ico"><i class="ri-award-line"></i></div>
            <div class="stat-value text-warning" id="statTop">{{ $topPerformerByCum ?? '—' }}</div>
            <div class="stat-label">Top Performer (Cumulative)</div>
            <div class="small text-muted mt-1">Term Top: <span id="statTermTop">{{ $topPerformerByTerm ?? '—' }}</span></div>
            @if($topPerformerPicture)
            <div class="mt-2">
                <img src="{{ $topPerformerPicture }}" alt="Top Performer"
                     style="width:40px;height:40px;border-radius:50%;border:2px solid var(--cb-amber);object-fit:cover;animation:floatUp 3s ease-in-out infinite;">
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Grade Basis Toggle ── --}}
<div class="grade-basis-bar">
    <span class="mode-label">
        <i class="ri-scales-3-line me-1"></i>
        Grade Basis:
    </span>
    <div class="grade-basis-toggle">
        <a href="{{ request()->fullUrlWithQuery(['grade_basis' => 'cum_ave']) }}"
           class="basis-btn {{ ($gradeBasis ?? 'cum_ave') === 'cum_ave' ? 'active-cumave' : '' }}">
            <i class="ri-bar-chart-line"></i> Cumulative Average
        </a>
        <a href="{{ request()->fullUrlWithQuery(['grade_basis' => 'total']) }}"
           class="basis-btn {{ ($gradeBasis ?? 'cum_ave') === 'total' ? 'active-total' : '' }}">
            <i class="ri-calendar-check-line"></i> Term Total
        </a>
    </div>
    <span class="basis-hint">
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

{{-- Column Toggle --}}
<div class="col-toggle-panel">
    <h6><i class="ri-layout-column-line" style="color:var(--cb-teal)"></i> Show / Hide Columns</h6>
    <div class="toggle-chips">
        <span class="toggle-chip active" data-colkey="position"><i class="ri-trophy-line"></i> Position</span>
        <span class="toggle-chip active" data-colkey="scores"><i class="ri-bar-chart-line"></i> Subject Scores</span>
        <span class="toggle-chip active" data-colkey="summary"><i class="ri-pie-chart-line"></i> Summary</span>
        <span class="toggle-chip active" data-colkey="teacher"><i class="ri-chat-3-line"></i> Teacher's Comment</span>
        <span class="toggle-chip active" data-colkey="guidance"><i class="ri-mental-health-line"></i> Counselor's Comment</span>
        <span class="toggle-chip active" data-colkey="activities"><i class="ri-football-line"></i> Remark on Activities</span>
        <span class="toggle-chip active" data-colkey="absence"><i class="ri-calendar-close-line"></i> Absences</span>
    </div>
    <p class="mt-2 mb-0" style="font-size:11px;color:var(--cb-muted);">
        <i class="ri-information-line me-1"></i>
        Click any comment field to open the rich editor with templates &amp; past history.
        <span class="ms-3"><i class="ri-magic-line me-1" style="color:var(--cb-teal);"></i>Use the <strong>✦ Templates</strong> button inside comments for quick-fill suggestions.</span>
    </p>
</div>

@if ($students->isNotEmpty())

@php
    $rankedBySelected = collect($studentAnalytics)->sortByDesc(function($a) use ($gradeBasis) {
        return $gradeBasis === 'total' ? ($a['term_percentage'] ?? 0) : ($a['cum_ave_percentage'] ?? 0);
    })->values();
    $positionMap = [];
    $prevPct = null;
    $prevPos = 0;
    $counter = 0;
    foreach ($rankedBySelected as $an) {
        $counter++;
        $sid = null;
        foreach ($studentAnalytics as $s => $a) {
            if ($a === $an) { $sid = $s; break; }
        }
        $key = $gradeBasis === 'total' ? 'term_percentage' : 'cum_ave_percentage';
        if ($prevPct !== null && $an[$key] == $prevPct) {
            $positionMap[$sid] = $prevPos;
        } else {
            $positionMap[$sid] = $counter;
            $prevPos = $counter;
        }
        $prevPct = $an[$key];
    }

    $cbAnalyticsJson = json_encode($studentAnalytics, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $positionMapJson = json_encode($positionMap,       JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
@endphp

<div id="cbPopupBackdrop"></div>
<div id="cbGradePopup">
    <div class="gpop-hdr">
        <span id="gpopTitle"><i class="ri-bar-chart-line me-1"></i>Grade Breakdown</span>
        <button type="button" class="gpop-close-btn" id="gpopCloseBtn">&times;</button>
    </div>
    <div class="gpop-body" id="gpopBody"></div>
</div>

<div class="cb-card">
    <div class="cb-card-header">
        <h5 style="margin:0;font-size:15px;font-weight:700;color:var(--cb-navy);">
            <i class="ri-table-alt-line me-1" style="color:var(--cb-teal)"></i>
            Student Performance &amp; Comments
            <span class="badge ms-2" style="background:var(--cb-teal);color:#fff;font-size:11px;border-radius:20px;padding:3px 10px;">{{ $students->count() }} Students</span>
            <span class="badge ms-2" style="background:#7c3aed;color:#fff;font-size:11px;border-radius:20px;padding:3px 10px;">
                <i class="ri-bar-chart-line me-1"></i> Cum Ave
            </span>
        </h5>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="cb-search" style="max-width:240px;">
                <i class="ri-search-line"></i>
                <input type="text" id="searchInput" placeholder="Search students…">
            </div>
        </div>
    </div>

    <form id="commentsForm">
        @csrf
        <input type="hidden" name="_method" value="PATCH">

        @foreach ($students as $student)
            @php $profile = $personalityProfiles->where('studentid', $student->id)->first(); @endphp
            <input type="hidden" class="canonical-teacher"    id="c_teacher_{{ $student->id }}"    name="teacher_comments[{{ $student->id }}]"           value="{{ $profile ? $profile->classteachercomment : '' }}">
            <input type="hidden" class="canonical-guidance"   id="c_guidance_{{ $student->id }}"   name="guidance_comments[{{ $student->id }}]"          value="{{ $profile ? $profile->guidancescomment : '' }}">
            <input type="hidden" class="canonical-activities" id="c_activities_{{ $student->id }}" name="remarks_on_other_activities[{{ $student->id }}]" value="{{ $profile ? $profile->remark_on_other_activities : '' }}">
            <input type="hidden" class="canonical-absence"    id="c_absence_{{ $student->id }}"    name="no_of_times_school_absent[{{ $student->id }}]"  value="{{ $profile ? $profile->no_of_times_school_absent : '' }}">
        @endforeach

        {{-- DESKTOP TABLE --}}
        <div class="desktop-only cb-table-scroll">
            <table class="cb-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">#</th>
                        <th class="col-name-hdr" style="min-width:220px;">Student</th>
                        <th class="cbcol-position" style="width:80px; text-align:center;">Position</th>
                        @foreach ($subjects as $subject)
                            <th class="cbcol-scores" style="min-width:120px; text-align:center;">{{ $subject->subject }}</th>
                        @endforeach
                        <th class="cbcol-summary" style="min-width:260px;">Summary</th>
                        <th class="cbcol-teacher" style="min-width:240px;">Teacher's Comment</th>
                        <th class="cbcol-guidance" style="min-width:220px;">Counselor's Comment</th>
                        <th class="cbcol-activities" style="min-width:240px;">Remark on Activities</th>
                        <th class="cbcol-absence" style="width:85px; text-align:center;">Absent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $index => $student)
                        @php
                            $sid        = $student->id;
                            $initials   = strtoupper(substr($student->fname ?? '', 0, 1) . substr($student->lastname ?? '', 0, 1)) ?: 'ST';
                            $hasPic     = !empty($student->picture) && $student->picture !== 'unnamed.jpg';
                            $imgUrl     = $hasPic ? asset('storage/student_avatars/' . basename($student->picture)) : null;
                            $fullName   = trim(($student->lastname ?? '') . ' ' . ($student->fname ?? '') . ' ' . ($student->othername ?? ''));
                            $profile    = $personalityProfiles->where('studentid', $sid)->first();
                            $an         = $studentAnalytics[$sid] ?? [];
                            $hasComment = $profile && !empty(trim($profile->classteachercomment ?? ''));
                            $pos        = $positionMap[$sid] ?? 0;
                            $posClass   = $pos === 1 ? 'pos-1' : ($pos === 2 ? 'pos-2' : ($pos === 3 ? 'pos-3' : 'pos-other'));
                            $posIcon    = $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : ($pos === 3 ? '🥉' : $pos));
                            $rowDelay   = 0.3 + ($index * 0.04);
                            $termPct    = $an['term_percentage'] ?? 0;
                            $cumPct     = $an['cum_percentage'] ?? 0;
                            $cumAvePct  = $an['cum_ave_percentage'] ?? 0;
                            $cumAveAvg  = $an['cum_ave_average'] ?? 0;
                            $termColor  = $termPct < 40 ? '#f43f5e' : ($termPct < 70 ? '#f59e0b' : '#22c55e');
                            $cumColor   = $cumPct  < 40 ? '#f43f5e' : ($cumPct  < 70 ? '#f59e0b' : '#22c55e');
                            $cumAveColor = $cumAvePct < 40 ? '#f43f5e' : ($cumAvePct < 70 ? '#f59e0b' : '#7c3aed');

                            $teacherComment = $profile ? $profile->classteachercomment : '';
                            $guidanceComment = $profile ? $profile->guidancescomment : '';
                            $activitiesComment = $profile ? $profile->remark_on_other_activities : '';
                        @endphp
                        <tr class="cb-student-row {{ $hasComment ? 'row-has-comment' : 'row-no-comment' }}"
                            data-student-id="{{ $sid }}"
                            data-student-name="{{ $fullName }}"
                            data-student-adm="{{ $student->admissionNo }}"
                            data-student-img="{{ $imgUrl }}"
                            data-student-analytics='@json($an)'
                            data-student-pos="{{ $pos }}"
                            data-searchkey="{{ strtolower($fullName . ' ' . $student->admissionNo) }}">

                            {{-- Serial Number --}}
                            <td class="sn-cell" style="text-align:center; font-weight:600; font-size:13px;">{{ $index + 1 }}</td>

                            {{-- Student Name Column --}}
                            <td class="td-name">
                                <div class="student-name-cell">
                                    @if($imgUrl)
                                        <div class="cb-avatar cb-avatar-trigger"
                                             data-img="{{ $imgUrl }}" data-name="{{ $fullName }}"
                                             data-adm="{{ $student->admissionNo }}"
                                             data-class="{{ $schoolclass ? $schoolclass->schoolclass . ' ' . $schoolclass->arm : '' }}"
                                             data-gender="{{ $student->gender ?? '' }}">
                                            <img src="{{ $imgUrl }}" alt="{{ $fullName }}"
                                                 onerror="var p=this.closest('.cb-avatar');p.classList.add('cb-avatar-initials');p.textContent='{{ $initials }}'">
                                        </div>
                                    @else
                                        <div class="cb-avatar cb-avatar-initials cb-avatar-trigger"
                                             data-img="" data-name="{{ $fullName }}"
                                             data-adm="{{ $student->admissionNo }}"
                                             data-class="{{ $schoolclass ? $schoolclass->schoolclass . ' ' . $schoolclass->arm : '' }}"
                                             data-gender="{{ $student->gender ?? '' }}"
                                             data-initials="{{ $initials }}">{{ $initials }}</div>
                                    @endif
                                    <div>
                                        <div class="student-name-text">{{ $fullName }}</div>
                                        <div class="student-adm">{{ $student->admissionNo }} · {{ $student->gender ?? '' }}</div>
                                        <span class="cumave-badge">
                                            <i class="ri-bar-chart-line me-1"></i> Cum Ave: {{ number_format($cumAveAvg, 1) }}%
                                        </span>
                                        <span class="comment-status-dot {{ $hasComment ? 'dot-saved' : 'dot-unsaved' }}" id="status-{{ $sid }}">{{ $hasComment ? '✓ Commented' : '○ No comment' }}</span>
                                        <span class="autosave-chip ac-idle" id="autosave-{{ $sid }}"></span>
                                    </div>
                                </div>
                            </td>

                            {{-- Position Badge --}}
                            <td class="cbcol-position" style="text-align:center; vertical-align:middle;">
                                <div style="display:flex; justify-content:center; align-items:center;">
                                    <div class="pos-badge {{ $posClass }}"
                                         data-tooltip="{{ $pos }}{{ $pos === 1 ? 'st' : ($pos === 2 ? 'nd' : ($pos === 3 ? 'rd' : 'th')) }} position ({{ $gradeBasis === 'total' ? 'Term' : 'Cum Ave' }})"
                                         style="cursor:pointer;">
                                        {{ $posIcon }}
                                    </div>
                                </div>
                            </td>

                            {{-- Subject Scores --}}
                            @foreach ($subjects as $subject)
                                @php
                                    $tScore = $termScoreMap[$sid][$subject->subject] ?? 0;
                                    $cScore = $cumScoreMap[$sid][$subject->subject]  ?? 0;
                                    $caScore = $cumAveMap[$sid][$subject->subject]   ?? 0;
                                    $bf = $bfMap[$sid][$subject->subject] ?? 0;
                                    $displayScore = $gradeBasis === 'total' ? $tScore : $caScore;

                                    $tGrade = $cGrade = $caGrade = '-';
                                    if ($isSenior) {
                                        if ($tScore >= 75) $tGrade='A1'; elseif ($tScore >= 70) $tGrade='B2'; elseif ($tScore >= 65) $tGrade='B3'; elseif ($tScore >= 60) $tGrade='C4'; elseif ($tScore >= 55) $tGrade='C5'; elseif ($tScore >= 50) $tGrade='C6'; elseif ($tScore >= 45) $tGrade='D7'; elseif ($tScore >= 40) $tGrade='E8'; elseif ($tScore > 0) $tGrade='F9';
                                        if ($cScore >= 75) $cGrade='A1'; elseif ($cScore >= 70) $cGrade='B2'; elseif ($cScore >= 65) $cGrade='B3'; elseif ($cScore >= 60) $cGrade='C4'; elseif ($cScore >= 55) $cGrade='C5'; elseif ($cScore >= 50) $cGrade='C6'; elseif ($cScore >= 45) $cGrade='D7'; elseif ($cScore >= 40) $cGrade='E8'; elseif ($cScore > 0) $cGrade='F9';
                                        if ($caScore >= 75) $caGrade='A1'; elseif ($caScore >= 70) $caGrade='B2'; elseif ($caScore >= 65) $caGrade='B3'; elseif ($caScore >= 60) $caGrade='C4'; elseif ($caScore >= 55) $caGrade='C5'; elseif ($caScore >= 50) $caGrade='C6'; elseif ($caScore >= 45) $caGrade='D7'; elseif ($caScore >= 40) $caGrade='E8'; elseif ($caScore > 0) $caGrade='F9';
                                    } else {
                                        if ($tScore >= 70) $tGrade='A'; elseif ($tScore >= 60) $tGrade='B'; elseif ($tScore >= 50) $tGrade='C'; elseif ($tScore >= 40) $tGrade='D'; elseif ($tScore > 0) $tGrade='F';
                                        if ($cScore >= 70) $cGrade='A'; elseif ($cScore >= 60) $cGrade='B'; elseif ($cScore >= 50) $cGrade='C'; elseif ($cScore >= 40) $cGrade='D'; elseif ($cScore > 0) $cGrade='F';
                                        if ($caScore >= 70) $caGrade='A'; elseif ($caScore >= 60) $caGrade='B'; elseif ($caScore >= 50) $caGrade='C'; elseif ($caScore >= 40) $caGrade='D'; elseif ($caScore > 0) $caGrade='F';
                                    }
                                    $tC = $tScore < 40 ? 'score-red' : ($tScore < 50 ? 'score-amber' : 'score-green');
                                    $cC = $cScore < 40 ? 'score-red' : ($cScore < 50 ? 'score-amber' : 'score-green');
                                    $caC = $caScore < 40 ? 'score-red' : ($caScore < 50 ? 'score-amber' : 'score-purple');
                                    $displayColor = $displayScore < 40 ? 'score-red' : ($displayScore < 50 ? 'score-amber' : ($gradeBasis === 'total' ? 'score-green' : 'score-purple'));
                                @endphp
                                <td class="cbcol-scores">
                                    <div class="score-dual">
                                        <div class="score-row score-row-term">
                                            <span class="score-lbl" style="color:#0891b2;">T</span>
                                            <span class="{{ $tC }}">{{ $tScore ? number_format($tScore, 1) : '—' }}</span>
                                            @if($tGrade !== '-')<span class="grade-badge g-{{ strtolower($tGrade) }}">{{ $tGrade }}</span>@endif
                                        </div>
                                        <div class="score-row score-row-cum">
                                            <span class="score-lbl" style="color:var(--cb-navy);">C</span>
                                            <span class="{{ $cC }}">{{ $cScore ? number_format($cScore, 1) : '—' }}</span>
                                            @if($cGrade !== '-')<span class="grade-badge g-{{ strtolower($cGrade) }}">{{ $cGrade }}</span>@endif
                                        </div>
                                        <div class="score-row score-row-cumave">
                                            <span class="score-lbl" style="color:#7c3aed;">CA</span>
                                            <span class="{{ $caC }}">{{ $caScore ? number_format($caScore, 1) : '—' }}</span>
                                            @if($caGrade !== '-')<span class="grade-badge g-{{ strtolower($caGrade) }}" style="background:#7c3aed;color:#fff;">{{ $caGrade }}</span>@endif
                                            @if($gradeBasis === 'cum_ave')<span style="font-size:8px;color:#7c3aed;">★</span>@endif
                                        </div>
                                        @if($bf > 0)
                                        <div style="font-size:8px;color:var(--cb-muted);text-align:left;padding:0 4px;">BF: {{ $bf }}</div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach

                            {{-- Summary / Analytics cell --}}
                            <td class="cbcol-summary analytics-cell">
                                <div class="analytics-row">
                                    <span class="analytics-lbl">Obtained (Term)</span>
                                    <span class="analytics-val">{{ number_format($an['term_total'] ?? 0, 1) }}</span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-lbl">Obtained (Cum)</span>
                                    <span class="analytics-val">{{ number_format($an['cum_total'] ?? 0, 1) }}</span>
                                </div>
                                <div class="analytics-row" style="background:#ede9fe;border-radius:4px;padding:2px 6px;">
                                    <span class="analytics-lbl" style="color:#5b21b6;"><i class="ri-bar-chart-line me-1"></i> Cum Ave ★</span>
                                    <span class="analytics-val" style="color:#7c3aed;">
                                        {{ number_format($an['cum_ave_average'] ?? 0, 1) }}
                                    </span>
                                </div>
                                <div class="analytics-row">
                                    <span class="analytics-lbl">% ({{ $gradeBasis === 'total' ? 'Term' : 'Cum Ave' }})</span>
                                    <span class="analytics-val analytics-percentage {{ $displayScore < 50 ? 'score-red' : ($displayScore < 70 ? 'score-amber' : ($gradeBasis === 'total' ? 'score-green' : 'score-purple')) }}"
                                          data-target="{{ number_format($gradeBasis === 'total' ? $termPct : $cumAvePct, 1) }}"
                                          data-type="pct">0%</span>
                                </div>

                                <div style="margin-top:5px;">
                                    <div style="font-size:9px;color:var(--cb-muted);margin-bottom:2px;">Term</div>
                                    <div class="pct-bar-wrap">
                                        <div class="pct-bar"
                                             data-final-color="{{ $termColor }}"
                                             style="width:{{ $termPct }}%;animation-delay:{{ $rowDelay }}s;"></div>
                                    </div>
                                    <div style="font-size:9px;color:var(--cb-muted);margin:3px 0 2px;">Cum</div>
                                    <div class="pct-bar-wrap">
                                        <div class="pct-bar"
                                             data-final-color="{{ $cumColor }}"
                                             style="width:{{ $cumPct }}%;animation-delay:{{ $rowDelay + 0.1 }}s;"></div>
                                    </div>
                                    <div style="font-size:9px;color:var(--cb-muted);margin:3px 0 2px;background:#ede9fe;padding:0 4px;border-radius:4px;">
                                        Cum Ave ★
                                    </div>
                                    <div class="pct-bar-wrap">
                                        <div class="pct-bar"
                                             data-final-color="{{ $cumAveColor }}"
                                             style="width:{{ $cumAvePct }}%;animation-delay:{{ $rowDelay + 0.2 }}s;"></div>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <button type="button"
                                            class="grade-trigger-btn"
                                            data-sid="{{ $sid }}"
                                            data-sname="{{ $fullName }}"
                                            data-tooltip="View Grade Breakdown"
                                            title="View Grade Breakdown">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </div>
                            </td>

                            {{-- Teacher's Comment Display --}}
                            <td class="cbcol-teacher">
                                <div class="comment-display"
                                     data-sid="{{ $sid }}"
                                     data-field="teacher"
                                     data-value="{{ $teacherComment }}"
                                     onclick="openCommentModal('{{ $sid }}', 'teacher', '{{ addslashes($fullName) }}', '{{ $student->admissionNo }}', '{{ $imgUrl }}', {{ json_encode($an) }})"
                                     style="cursor:pointer; padding:10px 12px; background:#f8fafc; border-radius:8px; border:1px solid var(--cb-border); min-height:60px; white-space:normal; word-wrap:break-word; font-size:12px; line-height:1.5;">
                                    {!! $teacherComment ? nl2br(e($teacherComment)) : '<span style="color:#94a3b8;">— Click to add comment —</span>' !!}
                                </div>
                            </td>

                            {{-- Counselor's Comment Display --}}
                            <td class="cbcol-guidance">
                                <div class="comment-display"
                                     data-sid="{{ $sid }}"
                                     data-field="guidance"
                                     data-value="{{ $guidanceComment }}"
                                     onclick="openCommentModal('{{ $sid }}', 'guidance', '{{ addslashes($fullName) }}', '{{ $student->admissionNo }}', '{{ $imgUrl }}', {{ json_encode($an) }})"
                                     style="cursor:pointer; padding:10px 12px; background:#f8fafc; border-radius:8px; border:1px solid var(--cb-border); min-height:60px; white-space:normal; word-wrap:break-word; font-size:12px; line-height:1.5;">
                                    {!! $guidanceComment ? nl2br(e($guidanceComment)) : '<span style="color:#94a3b8;">— Click to add comment —</span>' !!}
                                </div>
                            </td>

                            {{-- Remark on Activities Display --}}
                            <td class="cbcol-activities">
                                <div class="comment-display"
                                     data-sid="{{ $sid }}"
                                     data-field="activities"
                                     data-value="{{ $activitiesComment }}"
                                     onclick="openCommentModal('{{ $sid }}', 'activities', '{{ addslashes($fullName) }}', '{{ $student->admissionNo }}', '{{ $imgUrl }}', {{ json_encode($an) }})"
                                     style="cursor:pointer; padding:10px 12px; background:#f8fafc; border-radius:8px; border:1px solid var(--cb-border); min-height:60px; white-space:normal; word-wrap:break-word; font-size:12px; line-height:1.5;">
                                    {!! $activitiesComment ? nl2br(e($activitiesComment)) : '<span style="color:#94a3b8;">— Click to add comment —</span>' !!}
                                </div>
                            </td>

                            {{-- Absences --}}
                            <td class="cbcol-absence" style="text-align:center; vertical-align:middle;">
                                <input type="number" class="cb-input absence-input desk-absence"
                                       data-sid="{{ $sid }}" data-field="absence"
                                       value="{{ $profile ? $profile->no_of_times_school_absent : '' }}"
                                       min="0" placeholder="0" autocomplete="off"
                                       style="width:70px; text-align:center; margin:0 auto;">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- MOBILE CARDS --}}
        <div class="mobile-only" style="padding:16px;">
            @foreach ($students as $index => $student)
                @php
                    $sid = $student->id;
                    $initials = strtoupper(substr($student->fname ?? '', 0, 1) . substr($student->lastname ?? '', 0, 1)) ?: 'ST';
                    $hasPic = !empty($student->picture) && $student->picture !== 'unnamed.jpg';
                    $imgUrl = $hasPic ? asset('storage/student_avatars/' . basename($student->picture)) : null;
                    $fullName = trim(($student->lastname ?? '') . ' ' . ($student->fname ?? '') . ' ' . ($student->othername ?? ''));
                    $profile = $personalityProfiles->where('studentid', $sid)->first();
                    $an = $studentAnalytics[$sid] ?? [];
                    $hasComment = $profile && !empty(trim($profile->classteachercomment ?? ''));
                    $pos = $positionMap[$sid] ?? 0;
                    $posClass = $pos === 1 ? 'pos-1' : ($pos === 2 ? 'pos-2' : ($pos === 3 ? 'pos-3' : 'pos-other'));
                    $termPct = $an['term_percentage'] ?? 0;
                    $cumPct = $an['cum_percentage'] ?? 0;
                    $cumAvePct = $an['cum_ave_percentage'] ?? 0;
                    $cumAveAvg = $an['cum_ave_average'] ?? 0;

                    $teacherComment = $profile ? $profile->classteachercomment : '';
                    $guidanceComment = $profile ? $profile->guidancescomment : '';
                    $activitiesComment = $profile ? $profile->remark_on_other_activities : '';
                @endphp
                <div class="cb-student-card" data-student-id="{{ $sid }}" data-searchkey="{{ strtolower($fullName . ' ' . $student->admissionNo) }}">
                    <div class="card-top">
                        @if($imgUrl)
                            <div class="cb-avatar" style="width:48px;height:48px;">
                                <img src="{{ $imgUrl }}" alt="{{ $fullName }}" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        @else
                            <div class="cb-avatar cb-avatar-initials" style="width:48px;height:48px;font-size:16px;">{{ $initials }}</div>
                        @endif
                        <div style="flex:1;">
                            <div style="font-weight:700;font-size:14px;color:var(--cb-navy);">{{ $fullName }}</div>
                            <div style="font-size:11px;color:var(--cb-muted);">{{ $student->admissionNo }}</div>
                            <div style="font-size:10px;color:#7c3aed;background:#ede9fe;padding:2px 8px;border-radius:12px;display:inline-block;margin-top:2px;">
                                <i class="ri-bar-chart-line me-1"></i> Cum Ave: {{ number_format($cumAveAvg, 1) }}%
                            </div>
                        </div>
                        <div class="pos-badge {{ $posClass }}">{{ $pos }}</div>
                    </div>
                    <div class="card-body-pad">
                        <!-- Subject scores compact -->
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;margin-bottom:14px;">
                            @foreach ($subjects as $subject)
                                @php
                                    $tScore = $termScoreMap[$sid][$subject->subject] ?? 0;
                                    $caScore = $cumAveMap[$sid][$subject->subject] ?? 0;
                                    $displayScore = $gradeBasis === 'total' ? $tScore : $caScore;
                                    $displayGrade = $displayScore ? ($isSenior ?
                                        ($displayScore >= 75 ? 'A1' : ($displayScore >= 70 ? 'B2' : ($displayScore >= 65 ? 'B3' : ($displayScore >= 60 ? 'C4' : ($displayScore >= 55 ? 'C5' : ($displayScore >= 50 ? 'C6' : ($displayScore >= 45 ? 'D7' : ($displayScore >= 40 ? 'E8' : 'F9')))))))) :
                                        ($displayScore >= 70 ? 'A' : ($displayScore >= 60 ? 'B' : ($displayScore >= 50 ? 'C' : ($displayScore >= 40 ? 'D' : 'F'))))
                                    ) : '-';
                                    $displayColor = $displayScore < 40 ? 'score-red' : ($displayScore < 50 ? 'score-amber' : ($gradeBasis === 'total' ? 'score-green' : 'score-purple'));
                                @endphp
                                <div style="background:var(--cb-surface);border-radius:8px;padding:6px;text-align:center;border:1px solid var(--cb-border);">
                                    <div style="font-size:9px;font-weight:700;color:var(--cb-muted);">{{ $subject->subject }}</div>
                                    <div><span class="{{ $displayColor }}" style="font-weight:700;font-size:13px;">{{ $displayScore ? number_format($displayScore,1) : '—' }}</span></div>
                                    <div><span class="grade-badge g-{{ strtolower($displayGrade) }}">{{ $displayGrade }}</span></div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Mobile comment sections -->
                        <div class="comment-field-group">
                            <label>Teacher's Comment</label>
                            <div class="comment-display-mobile"
                                 data-sid="{{ $sid }}" data-field="teacher"
                                 onclick="openCommentModal('{{ $sid }}', 'teacher', '{{ addslashes($fullName) }}', '{{ $student->admissionNo }}', '{{ $imgUrl }}', {{ json_encode($an) }})"
                                 style="cursor:pointer; padding:10px; background:#f8fafc; border-radius:8px; border:1px solid var(--cb-border);">
                                {!! $teacherComment ? nl2br(e($teacherComment)) : '<span style="color:#94a3b8;">— Click to add comment —</span>' !!}
                            </div>
                        </div>
                        <div class="comment-field-group">
                            <label>Counselor's Comment</label>
                            <div class="comment-display-mobile"
                                 data-sid="{{ $sid }}" data-field="guidance"
                                 onclick="openCommentModal('{{ $sid }}', 'guidance', '{{ addslashes($fullName) }}', '{{ $student->admissionNo }}', '{{ $imgUrl }}', {{ json_encode($an) }})"
                                 style="cursor:pointer; padding:10px; background:#f8fafc; border-radius:8px; border:1px solid var(--cb-border);">
                                {!! $guidanceComment ? nl2br(e($guidanceComment)) : '<span style="color:#94a3b8;">— Click to add comment —</span>' !!}
                            </div>
                        </div>
                        <div class="comment-field-group">
                            <label>Remark on Activities</label>
                            <div class="comment-display-mobile"
                                 data-sid="{{ $sid }}" data-field="activities"
                                 onclick="openCommentModal('{{ $sid }}', 'activities', '{{ addslashes($fullName) }}', '{{ $student->admissionNo }}', '{{ $imgUrl }}', {{ json_encode($an) }})"
                                 style="cursor:pointer; padding:10px; background:#f8fafc; border-radius:8px; border:1px solid var(--cb-border);">
                                {!! $activitiesComment ? nl2br(e($activitiesComment)) : '<span style="color:#94a3b8;">— Click to add comment —</span>' !!}
                            </div>
                        </div>
                        <div class="comment-field-group">
                            <label>Times Absent</label>
                            <input type="number" class="cb-input mob-absence"
                                   data-sid="{{ $sid }}" data-field="absence"
                                   value="{{ $profile ? $profile->no_of_times_school_absent : '' }}"
                                   style="width:100px;">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="save-bar">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <label style="margin:0;"><i class="ri-pen-nib-line me-1"></i>Signature (optional)</label>
                <input type="file" id="signatureFile" accept=".jpg,.jpeg,.png,.pdf" class="file-input-styled">
            </div>
            <div class="save-counter">
                <span class="sc-pill-done"><i class="ri-checkbox-circle-line"></i><span id="counterDoneNum">0</span> commented</span>
                <span class="sc-pill-pending"><i class="ri-time-line"></i><span id="counterPendingNum">0</span> pending</span>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <button type="button" class="btn-print" onclick="triggerPrint()" title="Print Broadsheet">
                    <i class="ri-printer-line"></i> Print
                </button>
                <span id="savingText" style="display:none;color:rgba(255,255,255,.75);font-size:13px;align-items:center;gap:6px;"><i class="spin ri-loader-4-line"></i> Saving…</span>
                <button type="button" id="saveBtn" class="btn-save-all"><i class="ri-save-3-line"></i> Save All Changes</button>
            </div>
        </div>
    </form>
</div>

@else
<div class="cb-card" style="padding:60px;text-align:center;">
    <i class="ri-information-line" style="font-size:56px;color:var(--cb-border);display:block;margin-bottom:16px;"></i>
    <h5 style="color:var(--cb-navy);">No Students Found</h5>
    <p style="color:var(--cb-muted);">No students are enrolled in this class for the selected session and term.</p>
</div>
@endif

</div></div></div>

{{-- Image Zoom Modal --}}
<div class="modal fade" id="cbImgZoomModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:transparent;border:none;box-shadow:none;">
            <button type="button" class="cb-zoom-close" data-bs-dismiss="modal">&times;</button>
            <div class="modal-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:75vh;padding:20px;">
                <img id="cbZoomedImg" src="" alt="Student Photo" class="cb-zoomed-img" style="cursor:pointer;">
                <div class="cb-zoom-name" id="cbZoomedName"></div>
                <div class="cb-zoom-meta" id="cbZoomedMeta"></div>
            </div>
        </div>
    </div>
</div>

{{-- Comment Modal --}}
<div class="modal fade" id="cbCommentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:var(--cb-radius);overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,var(--cb-navy),var(--cb-teal));color:#fff;border:none;padding:20px 24px;">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div id="modalStudentAvatar" style="width:65px;height:65px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,0.3);background:linear-gradient(135deg,var(--cb-teal),var(--cb-sky));flex-shrink:0;"></div>
                    <div>
                        <h5 class="mb-1 text-white" id="modalStudentName" style="font-weight:700;"></h5>
                        <div class="text-white-50 small" id="modalStudentMeta"></div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <div class="performance-strip" style="background:linear-gradient(135deg,var(--cb-navy),#1e5f74);border-radius:12px;padding:16px 20px;color:#fff;margin-bottom:24px;">
                    <div style="font-size:12px;font-weight:600;opacity:.9;margin-bottom:12px;"><i class="ri-bar-chart-line me-1"></i>Performance Summary</div>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;">
                        <div style="text-align:center;background:rgba(255,255,255,.12);border-radius:10px;padding:10px;">
                            <div style="font-size:10px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;">Term</div>
                            <div id="modalTermTotal" style="font-size:20px;font-weight:700;margin-top:5px;">0.0</div>
                        </div>
                        <div style="text-align:center;background:rgba(255,255,255,.12);border-radius:10px;padding:10px;">
                            <div style="font-size:10px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;">Cum</div>
                            <div id="modalCumTotal" style="font-size:20px;font-weight:700;margin-top:5px;">0.0</div>
                        </div>
                        <div style="text-align:center;background:rgba(124,58,237,.2);border-radius:10px;padding:10px;">
                            <div style="font-size:10px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;color:#c4b5fd;">Cum Ave ★</div>
                            <div id="modalCumAveTotal" style="font-size:20px;font-weight:700;margin-top:5px;color:#c4b5fd;">0.0</div>
                        </div>
                        <div style="text-align:center;background:rgba(255,255,255,.12);border-radius:10px;padding:10px;">
                            <div style="font-size:10px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;">Position</div>
                            <div id="modalPosition" style="font-size:20px;font-weight:700;margin-top:5px;">—</div>
                        </div>
                    </div>
                    <div class="mt-2 pt-1" style="border-top:1px solid rgba(255,255,255,.15);">
                        <div class="small text-center opacity-75">Basis: <span id="modalGradeBasis">{{ $gradeBasis === 'total' ? 'Term Total' : 'Cumulative Average' }}</span> | Subjects: <span id="modalSubjects">0</span></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0" id="modalCommentType" style="font-weight:700;color:var(--cb-navy);"><i class="ri-chat-3-line me-1" style="color:var(--cb-teal);"></i> Teacher's Comment</h6>
                        <small class="text-muted" id="modalCommentHint">Click on any past comment to load it below</small>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm" id="btnOpenTemplates"
                                style="border-radius:20px;padding:6px 16px;background:var(--cb-teal);color:#fff;border:none;">
                            <i class="ri-magic-line me-1"></i> ✦ Templates
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnLoadPastComments" style="border-radius:20px;padding:6px 16px;">
                            <i class="ri-history-line me-1"></i> Past Comments <span id="pastCommentCount" class="badge bg-secondary ms-1" style="border-radius:20px;">0</span>
                        </button>
                    </div>
                </div>

                <div class="mb-2" style="background:#f8fafc;border-radius:10px;padding:8px;border:1px solid var(--cb-border);">
                    <button type="button" class="btn btn-sm btn-light me-1" onclick="formatCommentText('bold')"   style="border-radius:6px;" title="Bold"><i class="ri-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-light me-1" onclick="formatCommentText('italic')" style="border-radius:6px;" title="Italic"><i class="ri-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-light me-1" onclick="formatCommentText('bullet')" style="border-radius:6px;" title="Bullet List"><i class="ri-list-unordered"></i></button>
                    <button type="button" class="btn btn-sm btn-light me-1" onclick="formatCommentText('number')" style="border-radius:6px;" title="Number List"><i class="ri-list-ordered"></i></button>
                    <button type="button" class="btn btn-sm btn-light"      onclick="formatCommentText('clear')"  style="border-radius:6px;" title="Clear"><i class="ri-delete-back-line"></i></button>
                </div>
                <textarea id="modalTextarea" class="form-control" rows="6"
                    style="resize:vertical;font-family:inherit;font-size:14px;line-height:1.6;border-radius:10px;border:1.5px solid var(--cb-border);padding:12px;"></textarea>

                <div id="pastCommentsPanel" style="display:none;margin-top:20px;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="small fw-bold mb-0" style="color:var(--cb-navy);"><i class="ri-history-line me-1" style="color:var(--cb-teal);"></i> Past Comments from Previous Terms</h6>
                        <button type="button" class="btn-close btn-sm" onclick="document.getElementById('pastCommentsPanel').style.display='none'"></button>
                    </div>
                    <div id="pastCommentsList" style="max-height:350px;overflow-y:auto;border-radius:10px;"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--cb-border);padding:16px 24px;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:8px;padding:8px 20px;">Cancel</button>
                <button type="button" class="btn btn-success" id="modalSaveBtn" style="border-radius:8px;padding:8px 24px;background:var(--cb-teal);border:none;"><i class="ri-save-line me-1"></i> Save Comment</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // The two variables below are only defined when the class has students.
    // Fall back to empty objects so the page still renders the
    // "No Students Found" state instead of throwing "Undefined variable".
    var SA          = {!! $cbAnalyticsJson ?? '{}' !!};
    var PM          = {!! $positionMapJson ?? '{}' !!};
    var SAVE_URL    = '{{ route("classbroadsheet.updateComments", [$schoolclassid, $sessionid, $termid]) }}';
    var CSRF        = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    var CLASS_NAME  = '{{ $schoolclass ? $schoolclass->schoolclass . " " . $schoolclass->arm : "" }}';
    var TERM_NAME   = '{{ $schoolterm }}';
    var SESSION_NAME= '{{ $schoolsession }}';
    var GRADE_BASIS = '{{ $gradeBasis ?? "cum_ave" }}';

    var FIELD_MAP = { teacher:'c_teacher_', guidance:'c_guidance_', activities:'c_activities_', absence:'c_absence_' };
    var debounceTimers = {};
    var AUTOSAVE_DELAY = 1200;
    var currentModalSid   = null;
    var currentModalField = null;
    var commentModal      = null;
    var pastCommentRegistry = [];

    window.triggerPrint = function() { window.print(); };

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function esc(str) {
        var d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function nl2br(str) {
        if (!str) return '';
        return str.replace(/\n/g, '<br>');
    }

    function toast(msg, type) {
        document.querySelectorAll('.cb-toast').forEach(function(t){ t.remove(); });
        var icons = { success:'checkbox-circle-fill', error:'error-warning-fill', info:'information-fill' };
        var el = document.createElement('div');
        el.className = 'cb-toast cb-toast-' + (type || 'info');
        el.innerHTML = '<i class="ri-' + (icons[type] || icons.info) + '" style="font-size:18px;flex-shrink:0;"></i> ' + esc(msg);
        document.body.appendChild(el);
        setTimeout(function(){ el.remove(); }, 5000);
    }

    function getCanonical(sid, field) {
        var el = document.getElementById(FIELD_MAP[field] + sid);
        return el ? el.value : '';
    }

    function setCanonical(sid, field, value) {
        var el = document.getElementById(FIELD_MAP[field] + sid);
        if (el) el.value = value;
    }

    function setChipState(sid, state, text) {
        ['autosave-' + sid, 'autosave-m-' + sid].forEach(function(id){
            var chip = document.getElementById(id);
            if (!chip) return;
            chip.className = 'autosave-chip ' + state;
            chip.textContent = text || '';
        });
    }

    function refreshCommentDisplay(sid, field, value) {
        var displayValue = value && value.trim() !== '' ? value : '';

        document.querySelectorAll('.comment-display[data-sid="' + sid + '"][data-field="' + field + '"]').forEach(function(el) {
            if (displayValue) {
                el.innerHTML = nl2br(escapeHtml(displayValue));
                el.setAttribute('data-value', displayValue);
            } else {
                el.innerHTML = '<span style="color:#94a3b8;">— Click to add comment —</span>';
                el.setAttribute('data-value', '');
            }
        });

        document.querySelectorAll('.comment-display-mobile[data-sid="' + sid + '"][data-field="' + field + '"]').forEach(function(el) {
            if (displayValue) {
                el.innerHTML = nl2br(escapeHtml(displayValue));
                el.setAttribute('data-value', displayValue);
            } else {
                el.innerHTML = '<span style="color:#94a3b8;">— Click to add comment —</span>';
                el.setAttribute('data-value', '');
            }
        });
    }

    function autoSaveStudent(sid) {
        var fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('_method', 'PATCH');
        fd.append('teacher_comments[' + sid + ']',              getCanonical(sid, 'teacher'));
        fd.append('guidance_comments[' + sid + ']',             getCanonical(sid, 'guidance'));
        fd.append('remarks_on_other_activities[' + sid + ']',   getCanonical(sid, 'activities'));
        fd.append('no_of_times_school_absent[' + sid + ']',     getCanonical(sid, 'absence'));
        var sigFile = document.getElementById('signatureFile');
        if (sigFile && sigFile.files && sigFile.files[0]) fd.append('signature', sigFile.files[0]);
        setChipState(sid, 'ac-saving', '⏳ Saving…');
        fetch(SAVE_URL, {
            method:'POST',
            headers:{
                'Accept':'application/json',
                'X-Requested-With':'XMLHttpRequest',
                'X-CSRF-TOKEN':CSRF
            },
            body:fd
        })
            .then(function(res){ return res.json().then(function(data){ if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status)); return data; }); })
            .then(function(data){
                if (data.success){
                    setChipState(sid,'ac-saved','✓ Saved');
                    refreshCommentStatusForStudent(sid);
                    setTimeout(function(){ setChipState(sid,'ac-idle',''); }, 3000);
                } else {
                    setChipState(sid,'ac-err','✗ Failed');
                }
            })
            .catch(function(err){ console.error('Autosave error sid=' + sid, err); setChipState(sid,'ac-err','✗ Error'); });
    }

    function scheduleAutosave(sid) {
        if (debounceTimers[sid]) clearTimeout(debounceTimers[sid]);
        debounceTimers[sid] = setTimeout(function(){ autoSaveStudent(sid); }, AUTOSAVE_DELAY);
    }

    function refreshCommentStatusForStudent(sid) {
        var hasVal = getCanonical(sid, 'teacher').trim() !== '';
        ['status-' + sid, 'status-m-' + sid].forEach(function(id){
            var badge = document.getElementById(id);
            if (!badge) return;
            badge.textContent = hasVal ? '✓ Commented' : '○ No comment';
            badge.className = 'comment-status-dot ' + (hasVal ? 'dot-saved' : 'dot-unsaved');
        });
    }

    function refreshCommentStatus() {
        var done = 0, pending = 0, seen = {};
        document.querySelectorAll('.cb-student-row').forEach(function(row){
            var sid = row.getAttribute('data-student-id');
            if (!sid || seen[sid]) return;
            seen[sid] = true;
            if (getCanonical(sid, 'teacher').trim() !== '') done++; else pending++;
            refreshCommentStatusForStudent(sid);
        });
        var dNum = document.getElementById('counterDoneNum'), pNum = document.getElementById('counterPendingNum');
        if (dNum) dNum.textContent = done;
        if (pNum) pNum.textContent = pending;
    }

    window.formatCommentText = function(type) {
        var ta = document.getElementById('modalTextarea');
        if (!ta) return;
        var start = ta.selectionStart, end = ta.selectionEnd, text = ta.value, sel = text.substring(start, end);
        var formatted;
        if (type === 'bold')   { formatted = '**' + (sel || 'bold text') + '**'; }
        else if (type === 'italic')  { formatted = '*' + (sel || 'italic text') + '*'; }
        else if (type === 'bullet')  { formatted = sel ? sel.split('\n').map(function(l){ return '• ' + l; }).join('\n') : '• '; }
        else if (type === 'number')  { formatted = sel ? sel.split('\n').map(function(l,i){ return (i+1) + '. ' + l; }).join('\n') : '1. '; }
        else if (type === 'clear')   { ta.value = ''; ta.focus(); return; }
        ta.value = text.substring(0, start) + formatted + text.substring(end);
        ta.focus();
    };

    // Make openCommentModal available globally
    window.openCommentModal = function(sid, field, studentName, studentAdm, studentImg, analytics) {
        console.log('openCommentModal called:', sid, field, studentName);
        currentModalSid = sid;
        currentModalField = field;

        if (!commentModal) {
            var modalElement = document.getElementById('cbCommentModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                commentModal = new bootstrap.Modal(modalElement);
            } else {
                console.error('Bootstrap modal not available');
                return;
            }
        }

        document.getElementById('modalStudentName').textContent = studentName;
        document.getElementById('modalStudentMeta').innerHTML = '<i class="ri-id-card-line me-1"></i>' + esc(studentAdm || '');

        var avatarDiv = document.getElementById('modalStudentAvatar');
        if (studentImg && studentImg !== 'null' && studentImg !== '') {
            avatarDiv.innerHTML = '<img src="' + studentImg + '" style="width:100%;height:100%;object-fit:cover;">';
        } else {
            var initials = studentName.split(' ').map(function(n){ return n[0]; }).join('').substring(0,2).toUpperCase();
            avatarDiv.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;">' + esc(initials) + '</div>';
        }

        var analyticsData = typeof analytics === 'string' ? JSON.parse(analytics) : analytics;

        document.getElementById('modalTermTotal').textContent = parseFloat(analyticsData.term_total || 0).toFixed(1);
        document.getElementById('modalCumTotal').textContent  = parseFloat(analyticsData.cum_total || 0).toFixed(1);
        document.getElementById('modalCumAveTotal').textContent = parseFloat(analyticsData.cum_ave_average || 0).toFixed(1);
        document.getElementById('modalSubjects').textContent   = analyticsData.subject_count || 0;
        document.getElementById('modalGradeBasis').textContent = GRADE_BASIS === 'total' ? 'Term Total' : 'Cumulative Average';

        var posEl = document.getElementById('modalPosition');
        if (posEl) {
            var p = PM[sid] || 0;
            var suffix = p === 1 ? 'st' : p === 2 ? 'nd' : p === 3 ? 'rd' : 'th';
            posEl.textContent = p ? (p + suffix + ' / ' + Object.keys(PM).length) : '—';
        }

        var labels = { teacher:"Teacher's Comment", guidance:"Counselor's Comment", activities:"Remark on Activities" };
        var icons  = { teacher:'ri-chat-quote-line', guidance:'ri-mental-health-line', activities:'ri-football-line' };
        document.getElementById('modalCommentType').innerHTML = '<i class="' + (icons[field]||'ri-chat-3-line') + ' me-1" style="color:var(--cb-teal);"></i> ' + (labels[field] || field);
        document.getElementById('modalTextarea').value = getCanonical(sid, field);
        document.getElementById('pastCommentsPanel').style.display = 'none';
        pastCommentRegistry = [];
        document.getElementById('pastCommentCount').textContent = '0';

        commentModal.show();
    };

    async function loadPastComments() {
        if (!currentModalSid) return;
        var listEl = document.getElementById('pastCommentsList');
        listEl.innerHTML = '<div class="text-center py-4"><i class="ri-loader-4-line ri-spin" style="font-size:24px;color:var(--cb-teal);"></i><br><span class="text-muted mt-2 d-block">Loading past comments…</span></div>';
        document.getElementById('pastCommentsPanel').style.display = 'block';
        try {
            var res = await fetch('/classbroadsheet/past-comments/' + currentModalSid, {
                headers:{ 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            var data = await res.json();
            if (data.success && data.data && data.data.length > 0) {
                pastCommentRegistry = data.data.slice();
                document.getElementById('pastCommentCount').textContent = pastCommentRegistry.length;
                var summaryHtml = '<div class="mb-3" style="background:#f1f5f9;border-radius:10px;padding:12px;"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small fw-bold text-muted"><i class="ri-bar-chart-line"></i> Comment History</span><div class="d-flex gap-2 flex-wrap"><span class="badge" style="background:#0ea5e9;color:white;">Teacher: ' + (data.counts.classteacher||0) + '</span><span class="badge" style="background:#8b5cf6;color:white;">Guidance: ' + (data.counts.guidance||0) + '</span><span class="badge" style="background:#f59e0b;color:white;">Activities: ' + (data.counts.activities||0) + '</span><span class="badge" style="background:#1e293b;">Total: ' + data.counts.total + '</span></div></div></div>';
                var commentsHtml = '<div>';
                pastCommentRegistry.forEach(function(comment, idx) {
                    var bc='', bi='';
                    if (comment.comment_type==='Teacher')         { bc='#0ea5e9'; bi='ri-chat-quote-line'; }
                    else if (comment.comment_type==='Guidance')   { bc='#8b5cf6'; bi='ri-mental-health-line'; }
                    else if (comment.comment_type==='Activities') { bc='#f59e0b'; bi='ri-football-line'; }
                    else                                          { bc='#64748b'; bi='ri-chat-3-line'; }
                    var staffAvatarHtml = '';
                    if (comment.staff_picture && comment.staff_picture !== 'null' && comment.staff_picture !== '') {
                        staffAvatarHtml = '<img src="' + comment.staff_picture + '" alt="' + esc(comment.staff_name) + '" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">';
                    } else {
                        var staffInitials = (comment.staff_name || 'S').split(' ').map(function(n){ return n[0]; }).join('').substring(0,2).toUpperCase();
                        staffAvatarHtml = '<div style="width:32px;height:32px;border-radius:50%;background:' + bc + ';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;">' + staffInitials + '</div>';
                    }
                    var snippet = comment.comment_text.length > 200 ? comment.comment_text.substring(0,200) + '…' : comment.comment_text;
                    commentsHtml += '<div class="past-comment-item" style="border-left:4px solid ' + bc + ';background:#fff;padding:16px;margin-bottom:14px;border-radius:12px;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.05);" onclick="window.usePastComment(' + idx + ')">';
                    commentsHtml += '<div class="d-flex justify-content-between align-items-start mb-2"><span class="badge" style="background:' + bc + ';color:white;"><i class="' + bi + ' me-1"></i>' + comment.comment_type + '</span><small class="text-muted"><i class="ri-calendar-line me-1"></i>' + (comment.date||'—') + '</small></div>';
                    commentsHtml += '<div class="d-flex align-items-center gap-2 mb-2"><div style="width:32px;height:32px;border-radius:50%;overflow:hidden;">' + staffAvatarHtml + '</div><div><div class="small fw-semibold">' + esc(comment.staff_name) + '</div><div class="small text-muted">' + esc(comment.session) + ' · ' + esc(comment.term) + '</div></div></div>';
                    commentsHtml += '<div class="small" style="color:#334155;line-height:1.6;background:#fefce8;padding:10px 12px;border-radius:8px;border-left:3px solid ' + bc + ';">' + esc(snippet) + '</div>';
                    commentsHtml += '<div class="mt-2 text-end"><small class="text-primary" style="cursor:pointer;"><i class="ri-double-quotes-r"></i> Click to load</small></div></div>';
                });
                commentsHtml += '</div>';
                listEl.innerHTML = summaryHtml + commentsHtml;
            } else {
                pastCommentRegistry = [];
                document.getElementById('pastCommentCount').textContent = '0';
                listEl.innerHTML = '<div class="text-center py-5"><i class="ri-inbox-line" style="font-size:48px;color:#cbd5e1;"></i><p class="text-muted mt-2 mb-0">No past comments found for this student.</p></div>';
            }
        } catch(err) {
            console.error('Past comments error:', err);
            listEl.innerHTML = '<div class="text-center py-5 text-danger"><i class="ri-error-warning-line" style="font-size:48px;"></i><p class="mt-2">Failed to load past comments</p></div>';
        }
    }

    window.usePastComment = function(idx) {
        var comment = pastCommentRegistry[idx];
        if (!comment) { toast('Comment not found.', 'error'); return; }
        var ta = document.getElementById('modalTextarea');
        if (!ta) return;
        ta.value = comment.comment_text;
        ta.focus();
        ta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        toast('Past comment loaded — edit before saving.', 'success');
    };

    function saveCommentFromModal() {
        var newValue = document.getElementById('modalTextarea').value;
        setCanonical(currentModalSid, currentModalField, newValue);
        document.querySelectorAll('[data-sid="' + currentModalSid + '"][data-field="' + currentModalField + '"]').forEach(function(inp) {
            inp.value = newValue;
        });
        refreshCommentDisplay(currentModalSid, currentModalField, newValue);
        refreshCommentStatusForStudent(currentModalSid);
        scheduleAutosave(currentModalSid);
        if (commentModal) commentModal.hide();
        var fieldNames = { teacher: 'Teacher\'s', guidance: 'Counselor\'s', activities: 'Remark on Activities' };
        toast(fieldNames[currentModalField] + ' comment saved!', 'success');
    }

    function closeGradePop() {
        var gpop = document.getElementById('cbGradePopup'), backdrop = document.getElementById('cbPopupBackdrop');
        if (gpop)     { gpop.classList.remove('is-open'); gpop.removeAttribute('data-active-sid'); }
        if (backdrop) backdrop.style.display = 'none';
    }

    function gradeClass(g) { return (g || '-').toLowerCase().replace(/[\s-]/g, ''); }
    function getPctClass(p) { return p < 40 ? 'score-red' : p < 70 ? 'score-amber' : 'score-green'; }

    function openGradePop(sid, name, triggerEl) {
        var an = SA[sid];
        if (!an) { toast('No data found for this student.', 'error'); return; }
        var gpop = document.getElementById('cbGradePopup'), gpopBody = document.getElementById('gpopBody'), gpopTitle = document.getElementById('gpopTitle');
        if (!gpop) return;
        gpopTitle.innerHTML = '<i class="ri-bar-chart-line me-1"></i>' + esc(name) + "'s Grades";
        var grades = an.grades || [];
        var rows = '';
        if (grades.length) {
            for (var i = 0; i < grades.length; i++) {
                var g = grades[i];
                var tgl = gradeClass(g.term_grade), cgl = gradeClass(g.cum_grade), cal = gradeClass(g.cum_ave_grade || g.cum_grade);
                var tC = (g.term_score > 0 && g.term_score < 50) ? 'score-red' : (g.term_score >= 70 ? 'score-green' : 'score-amber');
                var cC = (g.cum_score  > 0 && g.cum_score  < 50) ? 'score-red' : (g.cum_score  >= 70 ? 'score-green' : 'score-amber');
                var caC = (g.cum_ave_score > 0 && g.cum_ave_score < 50) ? 'score-red' : (g.cum_ave_score >= 70 ? 'score-purple' : 'score-amber');
                if (!g.term_score || g.term_score <= 0) tC = '';
                if (!g.cum_score  || g.cum_score  <= 0) cC = '';
                if (!g.cum_ave_score || g.cum_ave_score <= 0) caC = '';
                var tsDisplay = (g.term_score && g.term_score > 0) ? parseFloat(g.term_score).toFixed(1) : '—';
                var csDisplay = (g.cum_score  && g.cum_score  > 0) ? parseFloat(g.cum_score).toFixed(1)  : '—';
                var casDisplay = (g.cum_ave_score && g.cum_ave_score > 0) ? parseFloat(g.cum_ave_score).toFixed(1) : '—';
                var termGradeBadge = (g.term_grade && g.term_grade !== '-') ? '<span class="grade-badge g-' + tgl + '">' + esc(g.term_grade) + '</span>' : '<span style="color:#94a3b8;font-size:11px;">—</span>';
                var cumGradeBadge  = (g.cum_grade  && g.cum_grade  !== '-') ? '<span class="grade-badge g-' + cgl + '">' + esc(g.cum_grade)  + '</span>' : '<span style="color:#94a3b8;font-size:11px;">—</span>';
                var cumAveGradeBadge = (g.cum_ave_grade && g.cum_ave_grade !== '-') ? '<span class="grade-badge g-' + cal + '" style="background:#7c3aed;color:#fff;">' + esc(g.cum_ave_grade)  + '</span>' : '<span style="color:#94a3b8;font-size:11px;">—</span>';
                rows += '<tr><td style="text-align:left;font-weight:600;">' + esc(g.subject) + '</td>' +
                        '<td><div class="score-pair">' +
                        '<div class="score-cell term"><span class="score-lbl" style="color:#0891b2;">T</span><span class="' + tC + '">' + tsDisplay + '</span></div>' +
                        '<div class="score-cell cum"><span class="score-lbl" style="color:var(--cb-navy);">C</span><span class="' + cC + '">' + csDisplay + '</span></div>' +
                        '<div class="score-cell cumave"><span class="score-lbl" style="color:#7c3aed;">CA</span><span class="' + caC + '">' + casDisplay + '</span></div>' +
                        '</div></td>' +
                        '<td><div style="display:flex;flex-direction:column;align-items:center;gap:3px;">' + termGradeBadge + cumGradeBadge + cumAveGradeBadge + '</div></td></tr>';
            }
        } else {
            rows = '<tr><td colspan="3" class="text-center text-muted py-3">No subject records available</td><td></td><td></td></tr>';
        }
        var tPct = parseFloat(an.term_percentage || 0), cPct = parseFloat(an.cum_percentage || 0), caPct = parseFloat(an.cum_ave_percentage || 0);
        var pos = PM[sid] || 0;
        var suffix = pos === 1 ? 'st' : pos === 2 ? 'nd' : pos === 3 ? 'rd' : 'th';
        var basisLabel = GRADE_BASIS === 'total' ? 'Term Total' : 'Cumulative Average';
        gpopBody.innerHTML =
            '<div class="gpop-legend">' +
            '<span style="font-size:10px;font-weight:700;color:var(--cb-muted);">Legend:</span>' +
            '<span class="gpop-legend-item"><span class="gpop-legend-dot t"></span>Term</span>' +
            '<span class="gpop-legend-item"><span class="gpop-legend-dot c"></span>Cum (raw)</span>' +
            '<span class="gpop-legend-item"><span class="gpop-legend-dot ca"></span>Cum Ave ★</span>' +
            '<span style="margin-left:auto;font-size:10px;color:var(--cb-muted);">Basis: <strong>' + basisLabel + '</strong></span>' +
            '</div>' +
            '<div class="gpop-scroll"><table class="gpop-table"><thead><tr><th style="text-align:left;padding-left:12px;">Subject</th><th class="col-score">Score<br><small style="opacity:.65;font-weight:400;font-size:9px;text-transform:none;letter-spacing:0;">T / C / CA</small></th><th class="col-grade">Grade<br><small style="opacity:.65;font-weight:400;font-size:9px;text-transform:none;letter-spacing:0;">T / C / CA</small></th></tr></thead><tbody>' + rows + '</tbody></table></div>' +
            '<div class="gpop-summary">' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">Obtained (Term)</div><div class="gpop-sum-val">' + parseFloat(an.term_total || 0).toFixed(1) + '</div></div>' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">Obtained (Cum)</div><div class="gpop-sum-val">' + parseFloat(an.cum_total || 0).toFixed(1) + '</div></div>' +
            '<div class="gpop-sum-item" style="border-color:#7c3aed;background:#ede9fe;"><div class="gpop-sum-lbl" style="color:#5b21b6;">Cum Ave ★</div><div class="gpop-sum-val score-purple">' + parseFloat(an.cum_ave_average || 0).toFixed(1) + '</div></div>' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">% (Term)</div><div class="gpop-sum-val ' + getPctClass(tPct) + '">' + tPct.toFixed(1) + '%</div></div>' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">% (Cum)</div><div class="gpop-sum-val ' + getPctClass(cPct) + '">' + cPct.toFixed(1) + '%</div></div>' +
            '<div class="gpop-sum-item" style="border-color:#7c3aed;background:#ede9fe;"><div class="gpop-sum-lbl" style="color:#5b21b6;">% (Cum Ave ★)</div><div class="gpop-sum-val score-purple">' + caPct.toFixed(1) + '%</div></div>' +
            '</div>';

        var rect = triggerEl.getBoundingClientRect();
        var pw = 480, ph = Math.min(600, window.innerHeight - 40);
        var vw = window.innerWidth, vh = window.innerHeight;
        var top = rect.bottom + 8, left = rect.left + (rect.width / 2) - (pw / 2);
        if (top + ph > vh - 8) top  = Math.max(8, rect.top - ph - 8);
        if (left < 8)          left = 8;
        if (left + pw > vw - 8) left = vw - pw - 8;
        gpop.style.width = pw + 'px'; gpop.style.top = top + 'px'; gpop.style.left = left + 'px'; gpop.style.maxHeight = ph + 'px';
        gpop.dataset.activeSid = sid;
        gpop.classList.add('is-open');
        var backdrop = document.getElementById('cbPopupBackdrop');
        if (backdrop) backdrop.style.display = 'block';
    }

    function doSaveAll() {
        var fd = new FormData(document.getElementById('commentsForm'));
        fd.append('_token', CSRF);
        fd.append('_method', 'PATCH');
        var sigFile = document.getElementById('signatureFile');
        if (sigFile && sigFile.files && sigFile.files[0]) fd.append('signature', sigFile.files[0]);
        var saveBtn = document.getElementById('saveBtn'), savingText = document.getElementById('savingText'), origHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="ri-loader-4-line spin"></i> Saving…';
        if (savingText) savingText.style.display = 'inline-flex';
        fetch(SAVE_URL, {
            method:'POST',
            headers:{
                'Accept':'application/json',
                'X-Requested-With':'XMLHttpRequest',
                'X-CSRF-TOKEN':CSRF
            },
            body:fd
        })
            .then(function(res){ return res.json(); })
            .then(function(data){
                if (data.success){
                    toast(data.message || 'Saved successfully!', 'success');
                    refreshCommentStatus();
                } else {
                    toast(data.message || 'Save failed.', 'error');
                }
            })
            .catch(function(err){ console.error(err); toast('Error: ' + err.message, 'error'); })
            .finally(function(){
                saveBtn.disabled = false;
                saveBtn.innerHTML = origHtml;
                if (savingText) savingText.style.display = 'none';
            });
    }

    function applyBarFinalColors() {
        document.querySelectorAll('.pct-bar[data-final-color]').forEach(function(bar) {
            var color = bar.getAttribute('data-final-color');
            var delay = parseFloat(bar.style.animationDelay || '0') * 1000 + 820;
            setTimeout(function() {
                bar.style.backgroundColor = color;
            }, delay);
        });
    }

    function runCounters() {
        var duration = 700;
        var fps = 60;
        var steps = Math.round(duration / (1000 / fps));

        document.querySelectorAll('[data-target][data-type="pct"]').forEach(function(el) {
            var target = parseFloat(el.getAttribute('data-target') || '0');
            var current = 0;
            var increment = target / steps;
            var step = 0;
            var timer = setInterval(function() {
                step++;
                current += increment;
                if (step >= steps) { current = target; clearInterval(timer); }
                el.textContent = current.toFixed(1) + '%';
            }, 1000 / fps);
        });
    }

    var TEMPLATES = [
        { cat:'excellent', label:'Outstanding Performer', text:'%%NAME%% has demonstrated exceptional academic performance this term, consistently achieving outstanding results across all subjects. A truly gifted student whose dedication and hard work serve as an inspiration to peers.' },
        { cat:'excellent', label:'Top of Class', text:'%%NAME%% has continued to excel academically, maintaining a top position in the class. With excellent study habits and a keen intellect, this student is well on track for great achievements ahead.' },
        { cat:'good', label:'Good Performance', text:'%%NAME%% has performed well this term, showing a solid grasp of the subjects studied. With continued focus and dedication, even higher results are achievable next term.' },
        { cat:'average', label:'Satisfactory Performance', text:'%%NAME%% has shown satisfactory performance this term. There is room for improvement, and we encourage more consistent effort and study habits going forward.' },
        { cat:'improvement', label:'Needs Improvement', text:'%%NAME%% needs to put in significantly more effort to achieve their potential. We encourage this student to revise regularly, pay close attention in class, and seek assistance when faced with difficulties.' },
        { cat:'conduct', label:'Excellent Conduct', text:'%%NAME%% has exhibited exemplary conduct and character throughout this term. A respectful, disciplined, and well-mannered student who is a positive influence in the classroom.' },
        { cat:'counselor', label:'Positive Wellbeing', text:'%%NAME%% demonstrates a healthy sense of self-esteem and interacts positively with peers and teachers. Continue to nurture this positive outlook and engage in open communication whenever challenges arise.' },
        { cat:'activities', label:'Active Participation', text:'%%NAME%% actively participates in school extracurricular activities and has demonstrated excellent sportsmanship and teamwork. A well-rounded student who contributes positively to the school community.' },
    ];

    var tplActiveCategory = 'all';

    function renderTemplates(filter, catFilter) {
        var list = document.getElementById('tplList'); if (!list) return;
        var filt = (filter || '').toLowerCase().trim();
        var cat  = catFilter || tplActiveCategory;
        var shown = TEMPLATES.filter(function(t){
            var matchCat  = cat === 'all' || t.cat === cat;
            var matchText = !filt || t.label.toLowerCase().includes(filt) || t.text.toLowerCase().includes(filt);
            return matchCat && matchText;
        });
        if (!shown.length) { list.innerHTML = '<div class="tpl-no-results"><i class="ri-inbox-line" style="font-size:36px;color:#cbd5e1;display:block;margin-bottom:8px;"></i>No matching templates</div>'; return; }
        list.innerHTML = shown.map(function(t) {
            var idx = TEMPLATES.indexOf(t);
            return '<div class="tpl-item" onclick="window.applyTemplate(' + idx + ')"><span class="tpl-item-label">' + esc(t.label) + '</span><span class="tpl-item-text">' + esc(t.text.substring(0,90) + (t.text.length > 90 ? '…' : '')) + '</span></div>';
        }).join('');
    }

    window.applyTemplate = function(idx) {
        var tpl = TEMPLATES[idx]; if (!tpl) return;
        var ta = document.getElementById('modalTextarea'); if (!ta) return;
        var sName = '';
        if (currentModalSid) {
            var row = document.querySelector('[data-student-id="' + currentModalSid + '"]');
            if (row) sName = (row.getAttribute('data-student-name') || '').split(' ')[0] || 'Student';
        }
        ta.value = tpl.text.replace(/%%NAME%%/g, sName || 'Student');
        ta.focus();
        closeTplPicker();
        toast('Template loaded — personalise before saving.', 'success');
    };

    function openTplPicker() {
        renderTemplates('', 'all');
        tplActiveCategory = 'all';
        document.querySelectorAll('.tpl-cat-btn').forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-cat') === 'all'); });
        document.getElementById('tplSearchInput').value = '';
        var picker   = document.getElementById('tplPicker');
        var backdrop = document.getElementById('tplBackdrop');
        if (!picker) return;
        picker.classList.add('is-open');
        backdrop.style.display = 'block';
        var btn = document.getElementById('btnOpenTemplates');
        if (btn) {
            var rect = btn.getBoundingClientRect();
            var pw = 380, ph = Math.min(420, window.innerHeight - 40);
            var top = rect.bottom + 8, left = rect.left;
            if (top + ph > window.innerHeight - 8) top = Math.max(8, rect.top - ph - 8);
            if (left + pw > window.innerWidth - 8)  left = window.innerWidth - pw - 8;
            if (left < 8) left = 8;
            picker.style.position = 'fixed';
            picker.style.top = top + 'px';
            picker.style.left = left + 'px';
            picker.style.maxHeight = ph + 'px';
            picker.style.zIndex = '999999';
        }
    }

    function closeTplPicker() {
        var picker   = document.getElementById('tplPicker');
        var backdrop = document.getElementById('tplBackdrop');
        if (picker)   picker.classList.remove('is-open');
        if (backdrop) backdrop.style.display = 'none';
    }

    function onInputChange(e) {
        var inp = e.target, sid = inp.getAttribute('data-sid'), field = inp.getAttribute('data-field');
        if (!sid || !field) return;
        var val = inp.value;
        setCanonical(sid, field, val);
        scheduleAutosave(sid);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Move popups to body
        var gpop = document.getElementById('cbGradePopup'), backdrop = document.getElementById('cbPopupBackdrop');
        if (gpop && gpop.parentNode !== document.body) document.body.appendChild(gpop);
        if (backdrop && backdrop.parentNode !== document.body) document.body.appendChild(backdrop);
        var tplPicker = document.getElementById('tplPicker'), tplBd = document.getElementById('tplBackdrop');
        if (tplPicker && tplPicker.parentNode !== document.body) document.body.appendChild(tplPicker);
        if (tplBd && tplBd.parentNode !== document.body) document.body.appendChild(tplBd);

        runCounters();
        applyBarFinalColors();

        // Column toggles
        document.querySelectorAll('.toggle-chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                var key = this.getAttribute('data-colkey');
                var show = this.classList.toggle('active') ? '' : 'none';
                document.querySelectorAll('.cbcol-' + key).forEach(function(el) {
                    if (el) el.style.display = show;
                });
                var mobileClass = { guidance:'.mobile-col-guidance', activities:'.mobile-col-activities', absence:'.mobile-col-absence' }[key];
                if (mobileClass) document.querySelectorAll(mobileClass).forEach(function(el) {
                    if (el) el.style.display = show;
                });
            });
        });

        // Search
        var searchEl = document.getElementById('searchInput');
        if (searchEl) {
            searchEl.addEventListener('input', function() {
                var q = this.value.toLowerCase().trim();
                document.querySelectorAll('.cb-student-row').forEach(function(row) {
                    if (!row) return;
                    row.style.display = (!q || (row.getAttribute('data-searchkey') || '').toLowerCase().includes(q)) ? '' : 'none';
                });
            });
        }

        // Image zoom modal
        var imgModal = null, imgModalEl = document.getElementById('cbImgZoomModal');
        if (imgModalEl && typeof bootstrap !== 'undefined') imgModal = new bootstrap.Modal(imgModalEl);

        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('.cb-avatar-trigger');
            if (!trigger) return;
            var img = trigger.querySelector('img');
            var imgUrl = img ? img.src : null;
            var name = trigger.getAttribute('data-name') || 'Student';
            var adm = trigger.getAttribute('data-adm') || '';
            var cls = trigger.getAttribute('data-class') || '';
            var gender = trigger.getAttribute('data-gender') || '';
            var initials = trigger.getAttribute('data-initials') || name.substring(0,2).toUpperCase();
            document.getElementById('cbZoomedName').textContent = name;
            document.getElementById('cbZoomedMeta').innerHTML = (adm ? '<i class="ri-id-card-line me-1"></i>' + esc(adm) : '') + (cls ? ' &nbsp;|&nbsp; <i class="ri-building-line me-1"></i>' + esc(cls) : '') + (gender ? ' &nbsp;|&nbsp; ' + esc(gender) : '');
            var zoomedImg = document.getElementById('cbZoomedImg');
            if (imgUrl && imgUrl !== 'null' && imgUrl !== '') {
                zoomedImg.src = imgUrl;
            } else {
                var canvas = document.createElement('canvas');
                canvas.width = canvas.height = 400;
                var ctx = canvas.getContext('2d');
                var grad = ctx.createLinearGradient(0,0,400,400);
                grad.addColorStop(0,'#0d9488');
                grad.addColorStop(1,'#0ea5e9');
                ctx.fillStyle = grad;
                ctx.fillRect(0,0,400,400);
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 150px "DM Sans",Arial,sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(initials.substring(0,2).toUpperCase(), 200, 200);
                zoomedImg.src = canvas.toDataURL();
            }
            if (imgModal) imgModal.show();
        });

        // Grade popup close
        var gpopCloseBtn = document.getElementById('gpopCloseBtn');
        if (gpopCloseBtn) gpopCloseBtn.addEventListener('click', closeGradePop);
        document.addEventListener('click', function(e) {
            var bd = document.getElementById('cbPopupBackdrop');
            if (bd && e.target === bd) closeGradePop();
        });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') {
                closeGradePop();
                closeTplPicker();
            }
        });

        // Grade trigger button
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.grade-trigger-btn');
            if (!btn) return;
            e.stopPropagation();
            e.preventDefault();
            var sid = btn.getAttribute('data-sid'), name = btn.getAttribute('data-sname');
            if (!sid) return;
            var gpop = document.getElementById('cbGradePopup');
            if (gpop && gpop.classList.contains('is-open') && gpop.dataset.activeSid === sid) {
                closeGradePop();
                return;
            }
            closeGradePop();
            setTimeout(function(){ openGradePop(sid, name, btn); }, 16);
        });

        // Absence inputs
        document.querySelectorAll('.desk-absence, .mob-absence').forEach(function(inp){
            if (inp) inp.addEventListener('input', onInputChange);
        });

        // Template picker
        var btnOpenTemplates = document.getElementById('btnOpenTemplates');
        if (btnOpenTemplates) {
            btnOpenTemplates.addEventListener('click', function(e){
                e.stopPropagation();
                openTplPicker();
            });
        }
        var tplCloseBtn = document.getElementById('tplCloseBtn');
        if (tplCloseBtn) tplCloseBtn.addEventListener('click', closeTplPicker);
        var tplBackdrop = document.getElementById('tplBackdrop');
        if (tplBackdrop) tplBackdrop.addEventListener('click', closeTplPicker);
        var tplSearchInput = document.getElementById('tplSearchInput');
        if (tplSearchInput) {
            tplSearchInput.addEventListener('input', function(){
                renderTemplates(this.value, tplActiveCategory);
            });
        }
        document.querySelectorAll('.tpl-cat-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                tplActiveCategory = this.getAttribute('data-cat');
                document.querySelectorAll('.tpl-cat-btn').forEach(function(b){ b.classList.remove('active'); });
                this.classList.add('active');
                renderTemplates(document.getElementById('tplSearchInput').value, tplActiveCategory);
            });
        });

        // Modal buttons
        var loadBtn = document.getElementById('btnLoadPastComments');
        var modalSaveBtn = document.getElementById('modalSaveBtn');
        var saveBtn = document.getElementById('saveBtn');
        if (loadBtn) loadBtn.addEventListener('click', loadPastComments);
        if (modalSaveBtn) modalSaveBtn.addEventListener('click', saveCommentFromModal);
        if (saveBtn) saveBtn.addEventListener('click', doSaveAll);

        refreshCommentStatus();

        console.log('DOM fully loaded and modals initialized');
    });
})();
</script>
@endsection
