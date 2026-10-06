@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
/* ─────────────────────────────────────────────
   CSS VARIABLES
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
@keyframes shimmer     { 0% { background-position:-800px 0; } 100% { background-position:800px 0; } }
@keyframes slideInRight{ from { transform:translateX(110%); opacity:0; } to { transform:translateX(0); opacity:1; } }
@keyframes spin        { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
@keyframes popIn       { 0% { opacity:0; transform:scale(.7) translateY(12px); } 60% { transform:scale(1.04) translateY(-3px); } 100% { opacity:1; transform:scale(1) translateY(0); } }
@keyframes floatUp     { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-6px); } }
@keyframes glowPulse   { 0%,100% { box-shadow:0 0 0 0 rgba(13,148,136,.4); } 50% { box-shadow:0 0 0 8px rgba(13,148,136,0); } }
@keyframes progressFill{ from { width:0; } }
@keyframes rowSlide    { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:translateX(0); } }
@keyframes countUp     { from { opacity:0; transform:scale(.6); } to { opacity:1; transform:scale(1); } }
@keyframes backdropIn  { from { opacity:0; } to { opacity:1; } }
@keyframes barGrow     { from { transform:scaleX(0); transform-origin:left; } to { transform:scaleX(1); transform-origin:left; } }

.spin { animation:spin .8s linear infinite; }

/* ── Grade Basis Toggle ── */
.gb-toggle {
    display:inline-flex;
    background:#e6fffa;
    border:1px solid #99f6e4;
    border-radius:20px;
    padding:2px;
}
.gb-toggle-btn {
    border:none;
    background:transparent;
    padding:6px 16px;
    font-size:12px;
    font-weight:700;
    border-radius:18px;
    cursor:pointer;
    color:var(--cb-muted);
    transition:all .25s ease;
}
.gb-toggle-btn.active {
    background:var(--cb-teal);
    color:#fff;
    box-shadow:0 2px 6px rgba(13,148,136,.35);
}
.gb-toggle-btn:hover:not(.active) {
    color:var(--cb-teal);
}
#printGradeBasisNote { display:none; }

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
    content:''; position:absolute; top:-80px; right:-80px;
    width:280px; height:280px;
    background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);
    border-radius:50%; animation:floatUp 6s ease-in-out infinite;
}
.cb-hero::after {
    content:''; position:absolute; bottom:-60px; left:-60px;
    width:200px; height:200px;
    background:radial-gradient(circle,rgba(255,255,255,.05) 0%,transparent 70%);
    border-radius:50%; animation:floatUp 8s ease-in-out infinite reverse;
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

/* ── Stats Cards ── */
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

/* ── Meta grid ── */
.meta-grid {
    display:flex; border:1px solid var(--cb-border); background:var(--cb-surface);
    border-radius:8px; overflow:hidden; margin-bottom:14px; animation:fadeInUp .5s ease;
}
.meta-cell { flex:1; padding:10px 14px; border-right:1px solid var(--cb-border); transition:all .2s ease; }
.meta-cell:last-child { border-right:none; }
.meta-cell:hover { background:#e8f0fe; transform:translateY(-2px); }
.meta-label { font-size:10px; color:var(--cb-muted); text-transform:uppercase; letter-spacing:.4px; display:block; }
.meta-value { font-size:13px; font-weight:700; color:var(--cb-navy); }

/* ── Grade basis strip ── */
.grade-basis-strip {
    text-align:center; font-size:12px; color:var(--cb-muted);
    margin-bottom:14px; padding:6px 14px;
    background:#f0fdf9; border:1px solid #99f6e4; border-radius:8px;
    animation:fadeInUp .5s ease;
}
.grade-basis-strip strong { color:var(--cb-navy); }

/* ── Grade key ── */
.grade-key {
    display:flex; align-items:center; border:1px solid var(--cb-border);
    padding:6px 14px; background:#fafafa; border-radius:8px; margin-bottom:14px;
    flex-wrap:wrap; gap:6px; animation:fadeInLeft .5s ease;
}

/* ── Card & Table ── */
.cb-card {
    background:var(--cb-white); border:1px solid var(--cb-border);
    border-radius:var(--cb-radius); box-shadow:var(--cb-shadow);
    overflow:visible; animation:fadeInUp .5s ease .2s both;
}
.cb-card-header {
    padding:18px 24px; border-bottom:1px solid var(--cb-border);
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    background:linear-gradient(to right,#f8fafc,#f0fdf9);
    border-radius:var(--cb-radius) var(--cb-radius) 0 0;
}

/* ── Broadsheet table ── */
.broadsheet-table { width:100%; border-collapse:collapse; font-size:11px; background:white; border:1.5px solid var(--cb-navy); }
.broadsheet-table thead tr.subject-header th {
    background:var(--cb-navy); color:#fff; text-align:center;
    padding:7px 4px; border:0.5px solid rgba(37,99,235,.35);
    font-weight:600; font-size:11.5px; white-space:nowrap;
    position:sticky; top:0; z-index:10;
}
.broadsheet-table thead tr.subject-header th.student-col { background:#0f2040; text-align:left; padding-left:6px; }
.broadsheet-table thead tr.subject-header th.subj-name-hdr {
    background:#163562; border-left:1.5px solid #2563eb;
    font-size:10px; white-space:normal; word-break:break-word; min-width:60px;
}
.broadsheet-table thead tr.assessment-header th {
    background:#1a3d6a; color:#a8d4ef; text-align:center;
    padding:5px 3px; border:0.5px solid rgba(37,99,235,.2);
    font-size:10px; white-space:nowrap;
}
.broadsheet-table thead tr.assessment-header th.sub-boundary { border-left:1.5px solid #2563eb; }

/* Sub-position header styles */
.broadsheet-table thead tr.assessment-header th.pos-class-hdr {
    background:#1a2f50; color:#fef3c7; font-size:9px; white-space:nowrap;
}
.broadsheet-table thead tr.assessment-header th.pos-arm-hdr {
    background:#1a2040; color:#bfdbfe; font-size:9px; white-space:nowrap;
}

.broadsheet-table tbody tr {
    animation:rowSlide .4s ease both;
    transition:all .25s ease;
}
.broadsheet-table tbody tr:nth-child(odd)  { background:#ffffff; }
.broadsheet-table tbody tr:nth-child(even) { background:#f0f4fa; }
.broadsheet-table tbody tr:hover { background-color:#e8f0fe !important; transform:scale(1.005); box-shadow:0 2px 8px rgba(0,0,0,.08); }
.broadsheet-table tbody tr:nth-child(1){animation-delay:.05s}
.broadsheet-table tbody tr:nth-child(2){animation-delay:.08s}
.broadsheet-table tbody tr:nth-child(3){animation-delay:.11s}
.broadsheet-table tbody tr:nth-child(4){animation-delay:.14s}
.broadsheet-table tbody tr:nth-child(5){animation-delay:.17s}
.broadsheet-table tbody tr:nth-child(n+6){animation-delay:.20s}

.broadsheet-table tbody td {
    padding:5px 4px; border:0.5px solid #c5d3e8;
    text-align:center; vertical-align:middle;
    white-space:nowrap; font-size:11px; transition:all .2s ease;
}
.broadsheet-table tbody td.student-info-cell {
    text-align:left; padding-left:8px; font-weight:600;
    position:sticky; left:0; background:inherit; z-index:5; min-width:200px;
}
.score-cell { transition:all .2s ease; }
.score-cell:hover { transform:scale(1.05); filter:brightness(.95); }

/* ── Promotion status cells ── */
.promo-cell { text-align: center; border-left: 2px solid #7c3aed !important; }

.promo-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700; white-space: nowrap;
    transition: all .2s ease; cursor: default;
}
.promo-badge:hover { transform: scale(1.06); }

.promo-promoted     { background: #d1fae5; color: #065f46; }
.promo-trial        { background: #fef3c7; color: #92400e; }
.promo-see_principal{ background: #dbeafe; color: #1e40af; }
.promo-repeated     { background: #fee2e2; color: #991b1b; }
.promo-awaiting     { background: #f1f5f9; color: #475569; }

.promo-header-th {
    background: #3b0764 !important;
    border-left: 2px solid #7c3aed !important;
    min-width: 110px;
}

/* ── Position cells: term vs cum styling ── */
.pos-term-cell {
    background:#fef3c7 !important; color:#92400e; font-weight:700;
    border-left:1.5px solid #f59e0b !important; font-size:11px;
}
.pos-cum-cell {
    background:#dbeafe !important; color:#1e40af; font-weight:700;
    border-left:1.5px solid #3b82f6 !important; font-size:11px;
}

/* ── Per-subject position cell styles ── */
.sub-pos-class-cum-cell {
    background:#f0fdf4 !important; color:#166534; font-weight:700;
    border-left:1px solid #86efac !important; font-size:10px;
}
.sub-pos-class-total-cell {
    background:#fefce8 !important; color:#854d0e; font-weight:700;
    font-size:10px;
}
.sub-pos-arm-total-cell {
    background:#eff6ff !important; color:#1e40af; font-weight:700;
    border-left:1px solid #93c5fd !important; font-size:10px;
}
.sub-pos-arm-cum-cell {
    background:#f5f3ff !important; color:#5b21b6; font-weight:700;
    font-size:10px;
}

/* ── Grade colors ── */
.grade-a1 { background:#dcfce7 !important; color:#166534; font-weight:700; }
.grade-b2 { background:#dbeafe !important; color:#1e40af; }
.grade-b3 { background:#e0eeff !important; color:#1e40af; }
.grade-c4 { background:#fef9c3 !important; color:#854d0e; }
.grade-c5 { background:#fef3c7 !important; color:#92400e; }
.grade-c6 { background:#fde68a !important; color:#78350f; }
.grade-d7 { background:#ffedd5 !important; color:#9a3412; }
.grade-e8 { background:#fed7aa !important; color:#9a3412; }
.grade-f9 { background:#fee2e2 !important; color:#991b1b; font-weight:700; }

/* ── Score colors ── */
.score-red   { color:#dc2626 !important; font-weight:700; }
.score-amber { color:#d97706 !important; font-weight:700; }
.score-green { color:#16a34a !important; font-weight:700; }

/* ── GPA cells ── */
.gpa-cell { background:#eff6ff !important; color:#1e3a8a; font-weight:700; border-left:1.5px solid #3b82f6 !important; transition:all .2s ease; }
.gpa-cell:hover { background:#dbeafe !important; transform:scale(1.02); }

/* ── Position Badge ── */
.pos-badge {
    display:inline-flex; align-items:center; justify-content:center;
    width:32px; height:32px; border-radius:50%;
    font-size:12px; font-weight:800; border:2px solid;
    transition:all .3s cubic-bezier(.22,1,.36,1); flex-shrink:0; cursor:pointer;
}
.pos-badge:hover { transform:scale(1.18) rotate(-5deg); animation:glowPulse .8s ease infinite; }
.pos-1 { background:linear-gradient(135deg,#fef9c3,#fde68a); border-color:#f59e0b; color:#92400e; box-shadow:0 2px 8px rgba(245,158,11,.35); }
.pos-2 { background:linear-gradient(135deg,#f1f5f9,#e2e8f0); border-color:#94a3b8; color:#475569; }
.pos-3 { background:linear-gradient(135deg,#ffedd5,#fed7aa); border-color:#f97316; color:#9a3412; }
.pos-other { background:var(--cb-surface); border-color:var(--cb-border); color:var(--cb-muted); font-size:11px; }

/* ── Dual position badge (term + cum) ── */
.pos-dual {
    display:inline-flex; flex-direction:column; align-items:center;
    gap:2px; cursor:pointer;
}
.pos-dual .pos-term-lbl { font-size:9px; font-weight:700; color:#92400e; background:#fef3c7; border-radius:4px; padding:1px 5px; line-height:1.4; }
.pos-dual .pos-cum-lbl  { font-size:9px; font-weight:700; color:#1e40af; background:#dbeafe; border-radius:4px; padding:1px 5px; line-height:1.4; }

/* ── Student avatar ── */
.cb-avatar {
    width:30px; height:30px; border-radius:50%; overflow:hidden;
    border:2px solid var(--cb-border); flex-shrink:0;
    transition:all .3s cubic-bezier(.22,1,.36,1);
    display:inline-flex; align-items:center; justify-content:center;
}
.cb-avatar:hover { border-color:var(--cb-teal); transform:scale(1.12); box-shadow:0 3px 10px rgba(13,148,136,.25); }
.cb-avatar img { width:100%; height:100%; object-fit:cover; }
.cb-avatar-initials { background:linear-gradient(135deg,var(--cb-teal),var(--cb-sky)); color:#fff; font-size:11px; font-weight:700; }

/* ── Eye / Grade trigger button ── */
.grade-trigger-btn {
    background:none; border:none; cursor:pointer;
    color:var(--cb-sky); font-size:17px; padding:5px 8px; border-radius:8px;
    transition:all .25s ease; position:relative; z-index:1;
}
.grade-trigger-btn:hover {
    color:#fff; background:var(--cb-teal); transform:scale(1.15);
    box-shadow:0 3px 10px rgba(13,148,136,.4); animation:glowPulse .8s ease infinite;
}

/* ── Stats rows ── */
.stats-row td { background:var(--cb-navy) !important; color:white; font-weight:700; padding:5px 4px; text-align:center; border:0.5px solid #163785; font-size:11px; }
.stats-row td.stats-label { text-align:left; padding-left:8px; font-size:10px; }
.stats-hi td { background:#0a2240 !important; }
.stats-lo td { background:#111c2a !important; }

/* ── Toolbar / search ── */
.cb-search { position:relative; }
.cb-search input {
    width:100%; padding:9px 14px 9px 38px; border:1.5px solid var(--cb-border);
    border-radius:10px; font-size:13px; background:var(--cb-surface);
    font-family:'DM Sans',sans-serif; transition:all .25s ease;
}
.cb-search input:focus { border-color:var(--cb-teal); outline:none; box-shadow:0 0 0 3px rgba(13,148,136,.1); }
.cb-search i { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--cb-muted); pointer-events:none; }

/* ── Toast ── */
.cb-toast {
    position:fixed; bottom:80px; right:24px; min-width:260px; z-index:99999;
    padding:12px 16px; border-radius:12px; display:flex; align-items:center; gap:10px;
    font-size:13px; font-weight:600; box-shadow:var(--cb-shadow-lg);
    animation:slideInRight .3s cubic-bezier(.22,1,.36,1);
}
.cb-toast-success { background:#ecfdf5; border:1.5px solid #86efac; color:#15803d; }
.cb-toast-error   { background:#fff1f2; border:1.5px solid #fca5a5; color:#be123c; }
.cb-toast-info    { background:#eff6ff; border:1.5px solid #93c5fd; color:#1d4ed8; }
.cb-toast-warning { background:#fffbeb; border:1.5px solid #fcd34d; color:#92400e; }

/* ── Student List Modal ── */
.slist-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 99990;
    align-items: center; justify-content: center;
    animation: backdropIn .2s ease;
}
.slist-modal-overlay.open { display: flex; }

.slist-modal {
    background: white; border-radius: 16px;
    width: 620px; max-width: calc(100vw - 32px);
    max-height: calc(100vh - 40px); overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 24px 64px rgba(0,0,0,.25);
    animation: popIn .28s cubic-bezier(.22,1,.36,1);
}

.slist-modal-header {
    background: linear-gradient(135deg, #3b0764, #7c3aed);
    color: white; padding: 18px 22px;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.slist-modal-header h5 { font-size: 16px; font-weight: 700; margin: 0; }
.slist-modal-close {
    background: rgba(255,255,255,.18); border: none; color: white;
    width: 30px; height: 30px; border-radius: 50%; cursor: pointer;
    font-size: 17px; display: flex; align-items: center; justify-content: center;
    transition: all .2s ease;
}
.slist-modal-close:hover { background: rgba(255,255,255,.35); transform: rotate(90deg); }

.slist-modal-body { padding: 20px 22px; overflow-y: auto; flex: 1; }
.slist-modal-footer {
    padding: 14px 22px; border-top: 1px solid #e2e8f0;
    display: flex; justify-content: flex-end; gap: 10px;
    flex-shrink: 0; background: #f8fafc;
}

/* Drag-and-drop list */
.promo-order-list { list-style: none; padding: 0; margin: 0; }
.promo-order-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; background: white;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    margin-bottom: 6px; cursor: grab;
    transition: all .2s ease; user-select: none;
}
.promo-order-item:hover { border-color: #7c3aed; box-shadow: 0 2px 8px rgba(124,58,237,.15); }
.promo-order-item.dragging { opacity: .45; transform: scale(.98); cursor: grabbing; }
.promo-order-item.drag-over { border-color: #7c3aed; background: #f5f3ff; transform: translateY(-2px); box-shadow: 0 4px 14px rgba(124,58,237,.2); }
.drag-handle { color: #94a3b8; font-size: 18px; cursor: grab; line-height: 1; }
.drag-handle:active { cursor: grabbing; }

/* Field checkboxes grid */
.field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.field-checkbox-item {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 12px; border: 1.5px solid #e2e8f0;
    border-radius: 8px; cursor: pointer;
    transition: all .15s ease; font-size: 12.5px;
}
.field-checkbox-item:hover { border-color: #7c3aed; background: #f5f3ff; }
.field-checkbox-item input[type=checkbox] { accent-color: #7c3aed; width: 15px; height: 15px; }
.field-checkbox-item.checked { border-color: #7c3aed; background: #f5f3ff; }

.slist-section-title {
    font-size: 12px; font-weight: 700; color: #3b0764;
    text-transform: uppercase; letter-spacing: .5px;
    margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
}

/* ── Performance (grade) popup ── */
#cbGradePopup {
    display:none; position:fixed; z-index:99999;
    background:var(--cb-white); border:2px solid var(--cb-teal);
    border-radius:16px; box-shadow:0 20px 60px rgba(15,35,66,.22);
    width:560px; max-height:640px; overflow:hidden; flex-direction:column;
}
#cbGradePopup.is-open { display:flex; animation:popIn .28s cubic-bezier(.22,1,.36,1); }
.gpop-hdr {
    background:linear-gradient(135deg,var(--cb-navy),var(--cb-teal));
    color:#fff; padding:14px 18px; border-radius:14px 14px 0 0;
    font-weight:700; font-size:14px;
    display:flex; justify-content:space-between; align-items:center; flex-shrink:0;
}
.gpop-close-btn { background:rgba(255,255,255,.18); border:none; color:#fff; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all .25s ease; }
.gpop-close-btn:hover { background:rgba(255,255,255,.4); transform:rotate(90deg) scale(1.1); }
.gpop-body { padding:16px; overflow-y:auto; flex:1; }

/* ── Performance summary card inside popup ── */
.gpop-perf-strip {
    background:linear-gradient(135deg,var(--cb-navy),#1e5f74);
    border-radius:10px; padding:12px 16px; color:#fff; margin-bottom:14px;
}
.gpop-perf-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:8px; }
.gpop-perf-item { text-align:center; background:rgba(255,255,255,.1); border-radius:8px; padding:8px; transition:all .2s ease; }
.gpop-perf-item:hover { background:rgba(255,255,255,.2); transform:scale(1.03); }
.gpop-perf-lbl { font-size:9px; opacity:.8; text-transform:uppercase; letter-spacing:.4px; }
.gpop-perf-val { font-size:15px; font-weight:700; margin-top:2px; }

.gpop-legend { display:flex; align-items:center; gap:12px; margin-bottom:10px; padding:6px 10px; background:var(--cb-surface); border-radius:8px; border:1px solid var(--cb-border); flex-wrap:wrap; }
.gpop-legend-item { display:flex; align-items:center; gap:4px; font-size:10px; font-weight:700; color:var(--cb-muted); }
.gpop-legend-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.gpop-legend-dot.t { background:#0ea5e9; }
.gpop-legend-dot.c { background:var(--cb-navy); }
.gpop-legend-dot.ca { background:#7c3aed; }

.gpop-scroll { max-height:260px; overflow-y:auto; border:1px solid var(--cb-border); border-radius:10px; }
.gpop-table { width:100%; border-collapse:collapse; font-size:12px; table-layout:fixed; }
.gpop-table thead th { background:var(--cb-navy); color:#fff; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; padding:9px 8px; border-right:1px solid rgba(255,255,255,.08); text-align:center; position:sticky; top:0; z-index:2; }
.gpop-table thead th:first-child { text-align:left; padding-left:12px; width:22%; }
.gpop-table tbody td { padding:8px 6px; border-bottom:1px solid #f1f5f9; font-weight:500; text-align:center; vertical-align:middle; }
.gpop-table tbody td:first-child { text-align:left; font-weight:600; color:var(--cb-navy); padding-left:12px; }
.gpop-table tbody tr:hover td { background:#f0fdf9; }

.score-pair { display:flex; flex-direction:column; gap:2px; }
.score-cell-inner { display:flex; align-items:center; justify-content:center; gap:3px; padding:2px 4px; border-radius:4px; font-size:11px; font-weight:700; }
.score-cell-inner.term { background:rgba(14,165,233,.08); border-left:2px solid #0ea5e9; }
.score-cell-inner.cum  { background:rgba(15,35,66,.06);   border-left:2px solid var(--cb-navy); }
.score-cell-inner.cumave { background:rgba(124,58,237,.08); border-left:2px solid #7c3aed; }

.gpop-summary { background:linear-gradient(135deg,#f8fafc,#f0fdf9); border-radius:12px; padding:12px; margin-top:14px; display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.gpop-sum-item { text-align:center; padding:10px 6px; border-radius:10px; background:white; transition:all .2s ease; border:1px solid #e2e8f0; }
.gpop-sum-item:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.09); border-color:var(--cb-teal); }
.gpop-sum-lbl { font-size:9px; color:var(--cb-muted); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; font-weight:600; line-height:1.4; }
.gpop-sum-val { font-size:16px; font-weight:800; color:var(--cb-navy); }
.gpop-sum-val.score-red   { color:#dc2626; }
.gpop-sum-val.score-amber { color:#d97706; }
.gpop-sum-val.score-green { color:#16a34a; }

/* ── Progress bars inside popup ── */
.pct-bar-wrap { background:rgba(255,255,255,.15); border-radius:4px; height:6px; overflow:hidden; }
.pct-bar { height:100%; border-radius:4px; background:#22c55e; transition:background .8s ease; animation:progressFill .8s ease both; }

#cbPopupBackdrop { display:none; position:fixed; inset:0; z-index:99998; background:rgba(0,0,0,.3); animation:backdropIn .2s ease; }

/* ── Tooltips ── */
[data-tooltip] { position:relative; cursor:pointer; }
[data-tooltip]:before {
    content:attr(data-tooltip); position:absolute; bottom:100%; left:50%;
    transform:translateX(-50%); background:#1e293b; color:white;
    padding:4px 8px; border-radius:6px; font-size:10px; white-space:nowrap;
    opacity:0; visibility:hidden; transition:all .2s ease;
    pointer-events:none; z-index:1000;
}
[data-tooltip]:hover:before { opacity:1; visibility:visible; transform:translateX(-50%) translateY(-5px); }

/* ── Subject performance summary card ── */
.subj-summary-card {
    background:var(--cb-white); border:1px solid var(--cb-border);
    border-radius:var(--cb-radius); box-shadow:var(--cb-shadow);
    animation:fadeInUp .5s ease .4s both;
}
.subj-summary-card .card-header-custom {
    background:var(--cb-navy); color:#fff; padding:14px 20px;
    border-radius:var(--cb-radius) var(--cb-radius) 0 0;
    font-weight:700; font-size:14px;
}

/* ── School header ── */
.school-header-bar {
    background:linear-gradient(135deg,var(--cb-navy) 0%,#2563eb 100%);
    border-radius:10px; padding:18px 24px; margin-bottom:16px; color:white;
    animation:fadeInUp .6s ease;
}

/* ── BF note badge ── */
.bf-note {
    font-size:9px; opacity:.65; display:block;
    font-weight:400; margin-top:2px; line-height:1.3;
}

/* ── Print ── */
@media print {
    .no-print { display:none !important; }
    body { background:#fff !important; font-size:10px; }
    .cb-hero::before, .cb-hero::after { display:none; }
    .cb-hero { animation:none !important; }
    .cb-stat, .cb-card { box-shadow:none !important; animation:none !important; }
    .broadsheet-table tbody tr { animation:none !important; }
    #cbGradePopup, #cbPopupBackdrop { display:none !important; }
    #printGradeBasisNote { display:block !important; }
    .gb-toggle { display:none !important; }
    @page { margin:1.5cm 1.2cm; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

{{-- ── Hero ── --}}
<div class="cb-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1><i class="ri-table-alt-line me-2"></i>Class Broadsheet</h1>
            <p>Academic performance overview — scores, grades, positions and analytics for every student.</p>
            <div class="meta-pills">
                <span class="cb-meta-pill"><i class="ri-building-line"></i>{{ ($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arm_name ?? '') }}</span>
                <span class="cb-meta-pill"><i class="ri-calendar-event-line"></i>{{ $schoolsession->session ?? '-' }}</span>
                <span class="cb-meta-pill"><i class="ri-bookmark-line"></i>{{ $schoolterm->term ?? '-' }}</span>
                @if(!empty($is_combined))
                    <span class="cb-meta-pill" style="background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.4);"><i class="ri-links-line"></i>Combined Arms</span>
                @endif
            </div>
        </div>
        <a href="javascript:history.back()" class="btn-back"><i class="ri-arrow-left-line"></i> Back</a>
    </div>
</div>

{{-- ── Stats Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-navy),var(--cb-teal));"></div>
            <div class="stat-ico"><i class="ri-group-line"></i></div>
            <div class="stat-value" id="statTotalStudents">{{ $totalStudents }}</div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-sky),#38bdf8);"></div>
            <div class="stat-ico"><i class="ri-book-open-line"></i></div>
            <div class="stat-value text-info" id="statTotalSubjects">{{ count($subjects) }}</div>
            <div class="stat-label">Subjects</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-green),#4ade80);"></div>
            <div class="stat-ico"><i class="ri-percent-line"></i></div>
            <div class="stat-value text-success" id="statAvgPct">0%</div>
            <div class="stat-label">Avg % (Cumulative)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-amber),#fcd34d);"></div>
            <div class="stat-ico"><i class="ri-award-line"></i></div>
            <div class="stat-value text-warning" id="statTopPerformer" style="font-size:16px;">—</div>
            <div class="stat-label">Top Performer (Cum)</div>
        </div>
    </div>
</div>

@isset($ranking)
@include('broadsheet.partials.ranking')
@endisset

{{-- Best per arm (combined view) + best per subject --}}
@isset($toppers)
@include('broadsheet.partials.toppers')
@endisset

{{-- ── School Header ── --}}
<div class="school-header-bar">
    <div class="d-flex align-items-center">
        @if(!empty($school_logo_base64))
            <img src="{{ $school_logo_base64 }}" alt="Logo"
                 style="width:65px;height:65px;object-fit:contain;border-radius:50%;border:2px solid rgba(255,255,255,.4);margin-right:18px;animation:floatUp 3s ease-in-out infinite;">
        @endif
        <div class="flex-grow-1 text-center">
            <h4 class="mb-1 fw-bold text-uppercase text-white">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</h4>
            @if(!empty($schoolInfo->school_address))
                <p class="mb-1 opacity-75 text-white" style="font-size:13px;">{{ $schoolInfo->school_address }}</p>
            @endif
            @if(!empty($schoolInfo->school_motto))
                <p class="mb-0 fst-italic opacity-75 text-white" style="font-size:12px;">"{{ $schoolInfo->school_motto }}"</p>
            @endif
        </div>
    </div>
</div>

{{-- ── Title Strip ── --}}
<div style="background:var(--cb-navy);color:white;text-align:center;padding:10px;font-size:15px;font-weight:700;letter-spacing:1.5px;border-radius:8px;margin-bottom:14px;animation:fadeInUp .5s ease;">
    CLASS ACADEMIC BROADSHEET
    @if(!empty($is_combined))<span style="font-size:11px;opacity:.7;font-weight:400;margin-left:10px;">— Combined Arms</span>@endif
</div>

{{-- ── Grade Basis Toggle ── --}}
<div class="grade-basis-strip no-print" style="display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;padding:10px 16px;">
    <span style="font-weight:500;"><i class="ri-scales-3-line me-1"></i>Grades, GPA &amp; rankings based on:</span>
    <div class="gb-toggle" role="group" aria-label="Grade basis toggle">
        <button type="button"
                class="gb-toggle-btn {{ ($grade_basis ?? 'cum_ave') === 'cum_ave' ? 'active' : '' }}"
                onclick="switchGradeBasis('cum_ave')">Cumulative Average</button>
        <button type="button"
                class="gb-toggle-btn {{ ($grade_basis ?? 'cum_ave') === 'total' ? 'active' : '' }}"
                onclick="switchGradeBasis('total')">Term Total</button>
    </div>
</div>

{{-- Print-only static version --}}
<div class="grade-basis-strip" id="printGradeBasisNote">
    <i class="ri-scales-3-line me-1"></i>Grades, GPA &amp; rankings on this sheet are based on:
    <strong>{{ ($grade_basis ?? 'cum_ave') === 'total' ? 'Term Total' : 'Cumulative Average' }}</strong>
</div>

{{-- Hidden form for resubmission --}}
<form id="gradeBasisForm" method="POST" action="{{ url()->current() }}" style="display:none;">
    @csrf
    @if(!empty($is_combined))
        <input type="hidden" name="classgroup" value="{{ request('classgroup') }}">
    @else
        <input type="hidden" name="schoolclassid" value="{{ request('schoolclassid') }}">
    @endif
    <input type="hidden" name="sessionid" value="{{ request('sessionid') }}">
    <input type="hidden" name="termid" value="{{ request('termid') }}">
    <input type="hidden" name="grade_basis" id="gb_input" value="{{ $grade_basis ?? 'cum_ave' }}">
    @foreach(request('selectedColumns', []) as $i => $col)
        <input type="hidden" name="selectedColumns[{{ $i }}]" value="{{ $col }}">
    @endforeach
</form>

{{-- ── Meta Grid ── --}}
<div class="meta-grid">
    <div class="meta-cell" style="animation:fadeInLeft .5s ease .05s both;">
        <span class="meta-label">Class</span>
        <span class="meta-value">{{ ($schoolclass->schoolclass ?? '-') . ' ' . ($schoolclass->arm_name ?? '') }}</span>
    </div>
    <div class="meta-cell" style="animation:fadeInLeft .5s ease .10s both;">
        <span class="meta-label">Academic Session</span>
        <span class="meta-value">{{ $schoolsession->session ?? '-' }}</span>
    </div>
    <div class="meta-cell" style="animation:fadeInLeft .5s ease .15s both;">
        <span class="meta-label">Term</span>
        <span class="meta-value">{{ $schoolterm->term ?? '-' }}</span>
    </div>
    <div class="meta-cell" style="animation:fadeInLeft .5s ease .20s both;">
        <span class="meta-label">Generated</span>
        <span class="meta-value" style="font-size:11px;">{{ $generatedAt }}</span>
    </div>
</div>

{{-- ── Grade Key ── --}}
<div class="grade-key">
    <strong style="color:var(--cb-navy);margin-right:8px;font-size:12px;">GRADING SCALE:</strong>
    @php
    $gradeKey = [
        'A1'=>['75-100','#16a34a'],'B2'=>['70-74','#1d4ed8'],'B3'=>['65-69','#2563eb'],
        'C4'=>['60-64','#d97706'],'C5'=>['55-59','#b45309'],'C6'=>['50-54','#92400e'],
        'D7'=>['45-49','#ea580c'],'E8'=>['40-44','#c2410c'],'F9'=>['0-39','#dc2626'],
    ];
    @endphp
    @foreach($gradeKey as $grade => $info)
        <span style="animation:scaleIn .4s ease {{ $loop->index * 0.03 }}s both;display:inline-block;">
            <span class="badge" style="background:{{ $info[1] }};font-size:11px;border-radius:12px;padding:3px 9px;">{{ $grade }} ({{ $info[0] }})</span>
        </span>
    @endforeach
    <span class="text-muted ms-2" style="font-size:11px;">
        <strong>BF</strong>=Brought Forward &nbsp; <strong>CUM</strong>=Raw Sum (BF+Total) &nbsp; <strong>CUM AVE</strong>=Cum ÷ Term No. &nbsp;
        <span style="background:#fef3c7;color:#92400e;border-radius:4px;padding:1px 5px;font-weight:700;">T-POS</span>=Overall Term Pos &nbsp;
        <span style="background:#dbeafe;color:#1e40af;border-radius:4px;padding:1px 5px;font-weight:700;">C-POS</span>=Overall Cum Pos &nbsp;
        <span style="background:#f0fdf4;color:#166534;border-radius:4px;padding:1px 5px;font-weight:700;">CC</span>=Class Pos (Cum) &nbsp;
        <span style="background:#fefce8;color:#854d0e;border-radius:4px;padding:1px 5px;font-weight:700;">CT</span>=Class Pos (Total) &nbsp;
        <span style="background:#eff6ff;color:#1e40af;border-radius:4px;padding:1px 5px;font-weight:700;">AC</span>=Arm Pos (Total) &nbsp;
        <span style="background:#f5f3ff;color:#5b21b6;border-radius:4px;padding:1px 5px;font-weight:700;">AK</span>=Arm Pos (Cum)
    </span>
</div>

{{-- ── Toolbar ── --}}
<div class="cb-card mb-3 no-print" style="animation:fadeInUp .4s ease .1s both;">
    <div class="cb-card-header">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="cb-search" style="max-width:260px;">
                <i class="ri-search-line"></i>
                <input type="text" id="searchStudent" placeholder="Search name or admission no…">
            </div>
            <select class="form-select form-select-sm" id="locateStudent" style="max-width:260px;border-radius:8px;border:1.5px solid var(--cb-border);font-size:12px;">
                <option value="">🔍 Quick Locate…</option>
                <option value="top5">🏆 Top 5 (by Cum)</option>
                <option value="top10">⭐ Top 10</option>
                <option value="failures">⚠️ Students with F9</option>
                <option value="below_avg">📉 Below Class Average</option>
                <option disabled>──────────</option>
                <option value="promoted">✅ Promoted Students</option>
                <option value="trial">⚠️ On Trial</option>
                <option value="see_principal">👤 See Principal</option>
                <option value="repeated">🔁 Repeat Students</option>
                <option value="awaiting">⏳ Awaiting Decision</option>
                <option disabled>──────────</option>
                @foreach($studentRows as $student)
                    <option value="student_{{ $student['id'] }}">👤 {{ $student['lastname'] }}, {{ $student['firstname'] }} ({{ $student['admissionno'] }})</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" style="border-radius:8px;">
                <i class="ri-printer-line me-1"></i>Print
            </button>
            <button class="btn btn-sm" onclick="openStudentListModal()"
                style="background:linear-gradient(135deg,#3b0764,#7c3aed);color:#fff;border:none;border-radius:8px;">
                <i class="ri-list-check-2 me-1"></i>Print Student List
            </button>
            <button class="btn btn-sm" onclick="scrollToTop()" style="background:var(--cb-teal);color:#fff;border-radius:8px;border:none;">
                <i class="ri-arrow-up-line me-1"></i>Top
            </button>
        </div>
    </div>
</div>

{{-- ── Grade Popup ── --}}
<div id="cbPopupBackdrop"></div>
<div id="cbGradePopup">
    <div class="gpop-hdr">
        <span id="gpopTitle"><i class="ri-bar-chart-line me-1"></i>Performance Summary</span>
        <button type="button" class="gpop-close-btn" id="gpopCloseBtn">&times;</button>
    </div>
    <div class="gpop-body" id="gpopBody"></div>
</div>

@php
    $selected = $selectedColumns ?? [];
    $showAll  = empty($selected);

    // Student info columns
    $showAdmNo   = $showAll || in_array('admission_no',   $selected);
    $showGender  = in_array('gender', $selected);

    // Score columns
    $showTotal   = $showAll || in_array('total',          $selected);
    $showBF      = $showAll || in_array('bf',             $selected);
    $showCum     = $showAll || in_array('cum',            $selected);
    $showCumAve  = $showAll || in_array('cum_ave',         $selected);
    $showGrade   = $showAll || in_array('grade',          $selected);
    $showAvg     = $showAll || in_array('class_average',  $selected);
    $showRemark  = in_array('remark', $selected);

    // Overall student positions
    $showPosTerm = $showAll || in_array('position_term',  $selected);
    $showPosCum  = $showAll || in_array('position_cum',   $selected);

    // Per-subject position flags
    $showSubPosClassCum   = $showAll || in_array('pos_class_cum',   $selected);
    $showSubPosClassTotal = $showAll || in_array('pos_class_total', $selected);
    $showSubPosArmTotal   = $showAll || in_array('pos_arm_total',   $selected);
    $showSubPosArmCum     = $showAll || in_array('pos_arm_cum',     $selected);

    // GPA columns
    $showGPA     = $showAll || in_array('gpa',            $selected);
    $showCGPA    = in_array('cgpa', $selected);
    $showGPAGrade = in_array('gpa_grade', $selected);
    $showNumSub  = in_array('num_subjects', $selected);
    $showTotalGP = in_array('total_grade_points', $selected);

    // Promotion columns - include rule by default
    $showPromoStatus = $showAll || in_array('promotion_status', $selected);
    $showPromoLabel  = in_array('promotion_label', $selected);
    $showPromoRule   = $showAll || in_array('promotion_rule_applied', $selected);

    $promoColspan = ($showPromoStatus ? 1 : 0)
                  + ($showPromoLabel  ? 1 : 0)
                  + ($showPromoRule   ? 1 : 0);

    $activeAssessments = $assessments->filter(fn($a) =>
        empty($selected) || in_array('assessment_' . $a->id, $selected)
    );

    $gradeColors = [
        'A1'=>'grade-a1','B2'=>'grade-b2','B3'=>'grade-b3',
        'C4'=>'grade-c4','C5'=>'grade-c5','C6'=>'grade-c6',
        'D7'=>'grade-d7','E8'=>'grade-e8','F9'=>'grade-f9','-'=>'',
    ];

    $frozenCols = 2 + ($showAdmNo ? 1 : 0) + 1 + ($showGender ? 1 : 0);
    $gpaColspan = ($showGPA?1:0)+($showCGPA?1:0)+($showGPAGrade?1:0)+($showNumSub?1:0)+($showTotalGP?1:0);

    // Calculate per-subject colspan
    $subColspan = $activeAssessments->count();
    if($showTotal) $subColspan++;
    if($showBF) $subColspan++;
    if($showCum) $subColspan++;
    if($showCumAve) $subColspan++;
    if($showGrade) $subColspan++;
    if($showSubPosClassCum) $subColspan++;
    if($showSubPosClassTotal) $subColspan++;
    if($showSubPosArmTotal) $subColspan++;
    if($showSubPosArmCum) $subColspan++;
    if($showAvg) $subColspan++;
    if($showRemark) $subColspan++;
    $subColspan = max(1, $subColspan);
@endphp

{{-- ── Main Broadsheet Table ── --}}
<div class="cb-card mb-4">
    <div class="cb-card-header">
        <h5 style="margin:0;font-size:15px;font-weight:700;color:var(--cb-navy);">
            <i class="ri-table-alt-line me-1" style="color:var(--cb-teal)"></i>
            Student Performance &amp; Scores
            <span class="badge ms-2" style="background:var(--cb-teal);color:#fff;font-size:11px;border-radius:20px;padding:3px 10px;">{{ $totalStudents }} Students</span>
        </h5>
    </div>
    <div style="overflow-x:auto;">
        <table class="broadsheet-table" id="broadsheetTable">
            <thead>
                {{-- Row 1: Subject name header + frozen student cols --}}
                <tr class="subject-header">
                    <th class="student-col" rowspan="2" style="width:36px;">#</th>
                    @if($showPosTerm || $showPosCum)
                        <th class="student-col" rowspan="2" style="width:70px;">Position</th>
                    @endif
                    @if($showAdmNo)
                        <th class="student-col" rowspan="2" style="min-width:72px;">Adm. No</th>
                    @endif
                    <th class="student-col" rowspan="2" style="min-width:180px;text-align:left;padding-left:8px;">Student Name</th>
                    @if($showGender)
                        <th class="student-col" rowspan="2" style="width:38px;">Sex</th>
                    @endif

                    {{-- One header cell per subject spanning all its sub-columns --}}
                    @foreach($subjects as $subId => $subInfo)
                        <th class="subj-name-hdr" colspan="{{ $subColspan }}">
                            {{ $subInfo['subject_name'] }}
                            @if(!empty($subInfo['subject_code']))
                                <br><small style="opacity:.75;font-size:9px;">({{ $subInfo['subject_code'] }})</small>
                            @endif
                        </th>
                    @endforeach

                    {{-- Analytics (eye) column header --}}
                    <th class="subj-name-hdr" colspan="1" style="background:#0a2240;border-left:2px solid var(--cb-teal);min-width:46px;">
                        <i class="ri-eye-line" style="font-size:13px;"></i>
                    </th>

                    @if($gpaColspan > 0)
                        <th colspan="{{ $gpaColspan }}" style="background:#0a1e38;border-left:2px solid #3b82f6;font-size:10px;">GPA METRICS</th>
                    @endif

                    @if($promoColspan > 0)
                        <th colspan="{{ $promoColspan }}" class="promo-header-th"
                            style="font-size:10px;letter-spacing:.4px;">
                            <span style="font-size:13px; margin-right:4px;">🎓</span> PROMOTION
                        </th>
                    @endif
                </tr>

                {{-- Row 2: Assessment / score sub-headers --}}
                <tr class="assessment-header">
                    @foreach($subjects as $subId => $subInfo)
                        {{-- Assessment score columns --}}
                        @foreach($activeAssessments as $aIdx => $a)
                            <th class="{{ $aIdx === 0 ? 'sub-boundary' : '' }}" style="min-width:38px;">
                                {{ $a->name }}<br><span style="font-size:9px;opacity:.75;">/{{ $a->max_score }}</span>
                            </th>
                        @endforeach
                        {{-- Score summary columns --}}
                        @if($showTotal)
                            <th style="min-width:36px;">Total</th>
                        @endif
                        @if($showBF)
                            <th style="min-width:30px;">BF</th>
                        @endif
                        @if($showCum)
                            <th style="min-width:36px;">Cum<br><small style="font-size:8px;opacity:.75;">Raw Sum</small></th>
                        @endif
                        @if($showCumAve)
                            <th style="min-width:36px;">Cum<br><small style="font-size:8px;opacity:.75;">Ave</small></th>
                        @endif
                        @if($showGrade)
                            <th style="min-width:30px;">Grd</th>
                        @endif
                        {{-- Per-subject position columns --}}
                        @if($showSubPosClassCum)
                            <th class="pos-class-hdr" style="min-width:32px;" title="Class-wide position ranked by cumulative average">
                                CC<br><small style="font-size:8px;opacity:.8;">Cls✦Cum</small>
                            </th>
                        @endif
                        @if($showSubPosClassTotal)
                            <th class="pos-class-hdr" style="min-width:32px;" title="Class-wide position ranked by term total">
                                CT<br><small style="font-size:8px;opacity:.8;">Cls✦Tot</small>
                            </th>
                        @endif
                        @if($showSubPosArmTotal)
                            <th class="pos-arm-hdr" style="min-width:32px;" title="Arm-only position ranked by term total">
                                AC<br><small style="font-size:8px;opacity:.8;">Arm✦Tot</small>
                            </th>
                        @endif
                        @if($showSubPosArmCum)
                            <th class="pos-arm-hdr" style="min-width:32px;" title="Arm-only position ranked by cumulative average">
                                AK<br><small style="font-size:8px;opacity:.8;">Arm✦Cum</small>
                            </th>
                        @endif
                        @if($showAvg)
                            <th style="min-width:32px;">Avg</th>
                        @endif
                        @if($showRemark)
                            <th style="min-width:44px;">Rmk</th>
                        @endif
                    @endforeach

                    {{-- Analytics col sub-header --}}
                    <th style="min-width:44px;background:#0a2240;border-left:2px solid var(--cb-teal);">View</th>

                    @if($showGPA)      <th style="background:#0a1e38;color:#93c5fd;min-width:36px;border-left:2px solid #3b82f6;">GPA</th>   @endif
                    @if($showCGPA)     <th style="background:#0a1e38;color:#86efac;min-width:36px;">CGPA</th>  @endif
                    @if($showGPAGrade) <th style="background:#0a1e38;color:#fcd34d;min-width:30px;">GGrd</th>  @endif
                    @if($showNumSub)   <th style="background:#0a1e38;color:#a8d4ef;min-width:30px;">NS</th>    @endif
                    @if($showTotalGP)  <th style="background:#0a1e38;color:#a8d4ef;min-width:36px;">TGP</th>   @endif

                    @if($showPromoStatus)
                        <th style="background:#3b0764;color:#d8b4fe;min-width:110px;border-left:2px solid #7c3aed;white-space:nowrap;">Status</th>
                    @endif
                    @if($showPromoLabel)
                        <th style="background:#3b0764;color:#d8b4fe;min-width:130px;">Label</th>
                    @endif
                    @if($showPromoRule)
                        <th style="background:#3b0764;color:#d8b4fe;min-width:100px;">Rule Applied</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($studentRows as $idx => $stu)
                    @php
                        $sid      = $stu['id'];
                        $posCum   = $stu['position_cum']  ?? 0;
                        $posTerm  = $stu['position_term'] ?? 0;

                        $hasFailure = false;
                        foreach($stu['subjects'] as $sd) {
                            if(($sd['grade']??'') === 'F9') { $hasFailure = true; break; }
                        }
                        $hasPic   = !empty($stu['picture']) && $stu['picture'] !== 'unnamed.jpg';
                        $imgSrc   = $hasPic ? asset('storage/student_avatars/' . basename($stu['picture'])) : null;
                        $initials = strtoupper(substr($stu['lastname']??'',0,1) . substr($stu['firstname']??'',0,1)) ?: 'ST';
                        $fullName = trim(($stu['lastname']??'') . ' ' . ($stu['firstname']??''));

                        $subjectCount    = count($subjects);
                        $totalObtainable = $subjectCount * 100;
                        $totalObtained   = $stu['total_cum']  ?? 0;
                        $termObtained    = $stu['total_term'] ?? 0;

                        $hasBF = false;
                        foreach($stu['subjects'] as $sd) {
                            if(($sd['bf'] ?? 0) > 0) { $hasBF = true; break; }
                        }

                        $termPct = $totalObtainable > 0 ? round(($termObtained / $totalObtainable) * 100, 1) : 0;
                        $cumPct  = $totalObtainable > 0 ? round(($totalObtained / $totalObtainable) * 100, 1) : 0;
                        $posTotal = count($studentRows);

                        // Build grades array for popup - now includes both term and cum grades
                        $gradesForPopup = [];
                        foreach ($subjects as $subId => $subInfo) {
                            $sd = $stu['subjects'][$subId] ?? [];
                            $gradesForPopup[] = [
                                'subject'         => $subInfo['subject_name'],
                                'term_score'      => $sd['total']   ?? 0,
                                'cum_score'       => $sd['cum']     ?? 0,
                                'cum_ave_score'   => $sd['cum_ave'] ?? 0,
                                'bf_score'        => $sd['bf']      ?? 0,
                                'grade'           => $sd['grade']   ?? '-',
                                'term_grade'      => $sd['total_grade'] ?? '-',
                                'cum_grade'       => $sd['cum_grade']   ?? '-',
                                'pos_class_cum'   => $sd['pos_class_cum']   ?? null,
                                'pos_class_total' => $sd['pos_class_total'] ?? null,
                                'pos_arm_total'   => $sd['pos_arm_total']   ?? null,
                                'pos_arm_cum'     => $sd['pos_arm_cum']     ?? null,
                            ];
                        }
                    @endphp
                    <tr data-student-id="{{ $sid }}"
                        data-student-name="{{ strtolower($fullName) }}"
                        data-admission="{{ strtolower($stu['admissionno']) }}"
                        data-gpa="{{ $stu['gpa'] }}"
                        data-total-cum="{{ $totalObtained }}"
                        data-total-term="{{ $termObtained }}"
                        data-has-failure="{{ $hasFailure ? 'true' : 'false' }}"
                        data-term-pct="{{ $termPct }}"
                        data-cum-pct="{{ $cumPct }}"
                        data-has-bf="{{ $hasBF ? 'true' : 'false' }}"
                        style="animation-delay:{{ $idx * 0.05 }}s;">

                        <td>{{ $idx + 1 }}</td>

                        @if($showPosTerm || $showPosCum)
                            <td style="text-align:center;white-space:nowrap;">
                                <div class="pos-dual" data-tooltip="Overall Term: {{ $posTerm }} · Overall Cum: {{ $posCum }}">
                                    <span class="pos-term-lbl">T:{{ $posTerm }}</span>
                                    <span class="pos-cum-lbl">C:{{ $posCum }}</span>
                                </div>
                            </td>
                        @endif

                        @if($showAdmNo)
                            <td class="adm-cell">{{ $stu['admissionno'] }}</td>
                        @endif

                        <td class="student-info-cell">
                            <div style="display:flex;align-items:center;gap:8px;">
                                @if($imgSrc)
                                    <div class="cb-avatar">
                                        <img src="{{ $imgSrc }}" alt="{{ $fullName }}"
                                             onerror="var p=this.closest('.cb-avatar');p.classList.add('cb-avatar-initials');p.textContent='{{ $initials }}'">
                                    </div>
                                @else
                                    <div class="cb-avatar cb-avatar-initials">{{ $initials }}</div>
                                @endif
                                <div>
                                    <div style="font-weight:700;font-size:12px;color:var(--cb-navy);">{{ strtoupper($stu['lastname']) }}, {{ $stu['firstname'] }}</div>
                                    @if(!empty($stu['arm']))
                                        <div style="font-size:10px;color:var(--cb-muted);">Arm {{ $stu['arm'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        @if($showGender)
                            <td style="font-size:10px;">{{ substr($stu['gender']??'',0,1) }}</td>
                        @endif

                        {{-- Per-subject score cells --}}
                        @foreach($subjects as $subId => $subInfo)
                            @php
                                $sd = $stu['subjects'][$subId] ?? [];
                                $g = $sd['grade'] ?? '-';
                                $gc = $gradeColors[$g] ?? '';
                                $bfVal = (float)($sd['bf'] ?? 0);
                                $cumAveVal = (float)($sd['cum_ave'] ?? 0);

                                // Per-subject positions
                                $spCC = $sd['pos_class_cum'] ?? null;
                                $spCT = $sd['pos_class_total'] ?? null;
                                $spAT = $sd['pos_arm_total'] ?? null;
                                $spAK = $sd['pos_arm_cum'] ?? null;

                                $ord = function($n) {
                                    if (!$n) return '—';
                                    $n = (int)$n;
                                    $s = ['th','st','nd','rd'];
                                    $v = $n % 100;
                                    return $n . ($s[($v-20)%10] ?? $s[$v] ?? $s[0]);
                                };
                            @endphp

                            {{-- Assessment raw scores --}}
                            @foreach($activeAssessments as $aIdx => $a)
                                @php $as = $sd['assessments'][$a->id] ?? 0; @endphp
                                <td class="score-cell {{ $aIdx === 0 ? 'sub-boundary' : '' }}"
                                    style="{{ $aIdx === 0 ? 'border-left:1.5px solid #2563eb;' : '' }}">
                                    {{ $as > 0 ? number_format($as,1) : '—' }}
                                </td>
                            @endforeach

                            {{-- Term total --}}
                            @if($showTotal)
                                <td class="score-cell {{ $gc }}">
                                    {{ ($sd['total']??0) > 0 ? number_format($sd['total'],1) : '—' }}
                                </td>
                            @endif

                            {{-- Brought Forward --}}
                            @if($showBF)
                                <td class="score-cell" style="{{ $bfVal > 0 ? 'color:#0369a1;font-weight:700;' : 'color:#94a3b8;' }}">
                                    {{ $bfVal > 0 ? number_format($bfVal,1) : '—' }}
                                </td>
                            @endif

                            {{-- Cumulative — raw running sum (BF + Total) --}}
                            @if($showCum)
                                <td class="score-cell" style="font-weight:700;">
                                    {{ ($sd['cum']??0) > 0 ? number_format($sd['cum'],1) : '—' }}
                                </td>
                            @endif

                            {{-- Cumulative Average --}}
                            @if($showCumAve)
                                <td class="score-cell {{ $gc }}" style="font-weight:700;">
                                    {{ $cumAveVal > 0 ? number_format($cumAveVal,1) : '—' }}
                                </td>
                            @endif

                            {{-- Grade --}}
                            @if($showGrade)
                                <td class="score-cell {{ $gc }}" style="font-weight:700;">{{ $g }}</td>
                            @endif

                            {{-- Per-subject class-wide position (cum) --}}
                            @if($showSubPosClassCum)
                                <td class="score-cell sub-pos-class-cum-cell" data-tooltip="Class position (all arms) by cumulative average">
                                    {{ $ord($spCC) }}
                                </td>
                            @endif

                            {{-- Per-subject class-wide position (total) --}}
                            @if($showSubPosClassTotal)
                                <td class="score-cell sub-pos-class-total-cell" data-tooltip="Class position (all arms) by term total">
                                    {{ $ord($spCT) }}
                                </td>
                            @endif

                            {{-- Per-subject arm-only position (total) --}}
                            @if($showSubPosArmTotal)
                                <td class="score-cell sub-pos-arm-total-cell" data-tooltip="Arm position (this arm only) by term total">
                                    {{ $ord($spAT) }}
                                </td>
                            @endif

                            {{-- Per-subject arm-only position (cum) --}}
                            @if($showSubPosArmCum)
                                <td class="score-cell sub-pos-arm-cum-cell" data-tooltip="Arm position (this arm only) by cumulative average">
                                    {{ $ord($spAK) }}
                                </td>
                            @endif

                            {{-- Class average --}}
                            @if($showAvg)
                                <td class="score-cell" style="font-size:10px;color:var(--cb-muted);">{{ $subjectStats[$subId]['avg'] ?? '—' }}</td>
                            @endif

                            {{-- Remark --}}
                            @if($showRemark)
                                <td class="score-cell" style="font-size:10px;white-space:nowrap;">{{ $sd['remark'] ?? '—' }}</td>
                            @endif
                        @endforeach

                        {{-- Eye button for popup --}}
                        <td style="text-align:center;border-left:2px solid var(--cb-teal);background:#f0fdf9;">
                            <button type="button"
                                    class="grade-trigger-btn"
                                    data-sid="{{ $sid }}"
                                    data-sname="{{ $fullName }}"
                                    data-sadm="{{ $stu['admissionno'] }}"
                                    data-term-obtained="{{ $termObtained }}"
                                    data-cum-obtained="{{ $totalObtained }}"
                                    data-obtainable="{{ $totalObtainable }}"
                                    data-term-pct="{{ $termPct }}"
                                    data-cum-pct="{{ $cumPct }}"
                                    data-gpa="{{ $stu['gpa'] }}"
                                    data-gpa-grade="{{ $stu['gpa_grade'] ?? '-' }}"
                                    data-pos-cum="{{ $posCum }}"
                                    data-pos-term="{{ $posTerm }}"
                                    data-pos-total="{{ $posTotal }}"
                                    data-has-bf="{{ $hasBF ? 'true' : 'false' }}"
                                    data-grade-basis="{{ $grade_basis ?? 'cum_ave' }}"
                                    data-grades='@json($gradesForPopup)'
                                    data-tooltip="View Performance Summary"
                                    title="View Performance Summary">
                                <i class="ri-eye-line"></i>
                            </button>
                        </td>

                        {{-- GPA Metrics --}}
                        @if($showGPA)      <td class="gpa-cell">{{ number_format($stu['gpa'],2) }}</td>            @endif
                        @if($showCGPA)     <td class="gpa-cell" style="background:#f0fdf4!important;color:#166534;">{{ number_format($stu['cgpa'],2) }}</td> @endif
                        @if($showGPAGrade) @php $ggc = $gradeColors[$stu['gpa_grade']??'-'] ?? ''; @endphp
                                           <td class="gpa-cell {{ $ggc }}" style="font-weight:700;">{{ $stu['gpa_grade'] ?? '—' }}</td> @endif
                        @if($showNumSub)   <td>{{ $stu['num_subjects'] ?? '—' }}</td> @endif
                        @if($showTotalGP)  <td>{{ number_format($stu['total_grade_points'],1) }}</td> @endif

                        {{-- Promotion Status Cells --}}
                        @if($showPromoStatus)
                            @php
                                $pStatus    = $stu['promotion_status'] ?? 'awaiting';
                                $pLabel     = $stu['promotion_label']  ?? 'Awaiting';
                                $pBadgeClass = match($pStatus) {
                                    'promoted'      => 'promo-promoted',
                                    'trial'         => 'promo-trial',
                                    'see_principal' => 'promo-see_principal',
                                    'repeated'      => 'promo-repeated',
                                    default         => 'promo-awaiting',
                                };
                                $pIcon = match($pStatus) {
                                    'promoted'      => '✅',
                                    'trial'         => '⚠️',
                                    'see_principal' => '👤',
                                    'repeated'      => '🔁',
                                    default         => '⏳',
                                };
                            @endphp
                            <td class="promo-cell">
                                <span class="promo-badge {{ $pBadgeClass }}" title="{{ $pLabel }}">
                                    {{ $pIcon }} {{ ucfirst(str_replace('_', ' ', $pStatus)) }}
                                </span>
                            </td>
                        @endif

                        @if($showPromoLabel)
                            <td class="promo-cell" style="font-size:10px;font-weight:600;color:#5b21b6;">
                                {{ $stu['promotion_label'] ?? '—' }}
                            </td>
                        @endif

                        @if($showPromoRule)
                            <td class="promo-cell" style="font-size:10px;color:#64748b;max-width:120px;overflow:hidden;text-overflow:ellipsis;"
                                title="{{ $stu['promotion_rule_applied'] ?? '' }}">
                                {{ $stu['promotion_rule_applied'] ? Str::limit($stu['promotion_rule_applied'], 20) : '—' }}
                            </td>
                        @endif
                    </tr>
                @endforeach

                {{-- Stats rows (Avg / Highest / Lowest) --}}
                @php
                    $statRows = [['CLASS AVG','avg'],['HIGHEST','highest'],['LOWEST','lowest']];
                    $statStyles = ['avg'=>'','highest'=>'stats-hi','lowest'=>'stats-lo'];
                @endphp
                @foreach($statRows as [$label, $key])
                    <tr class="stats-row {{ $statStyles[$key] }}">
                        <td class="stats-label" colspan="{{ $frozenCols + ($showPosTerm || $showPosCum ? 0 : -1) }}">{{ $label }}</td>
                        @foreach($subjects as $subId => $subInfo)
                            @php $st = $subjectStats[$subId] ?? []; @endphp
                            @foreach($activeAssessments as $a)  <td>—</td> @endforeach
                            @if($showTotal)             <td>{{ $st[$key] ?? '—' }}</td> @endif
                            @if($showBF)                <td>—</td> @endif
                            @if($showCum)               <td>—</td> @endif
                            @if($showCumAve)            <td>—</td> @endif
                            @if($showGrade)             <td>—</td> @endif
                            @if($showSubPosClassCum)    <td>—</td> @endif
                            @if($showSubPosClassTotal)  <td>—</td> @endif
                            @if($showSubPosArmTotal)    <td>—</td> @endif
                            @if($showSubPosArmCum)      <td>—</td> @endif
                            @if($showAvg)               <td>{{ $key==='avg' ? ($st['avg']??'—') : '—' }}</td> @endif
                            @if($showRemark)            <td>—</td> @endif
                        @endforeach
                        <td>—</td> {{-- Analytics column --}}
                        @if($showGPA)        <td>—</td> @endif
                        @if($showCGPA)       <td>—</td> @endif
                        @if($showGPAGrade)   <td>—</td> @endif
                        @if($showNumSub)     <td>—</td> @endif
                        @if($showTotalGP)    <td>—</td> @endif
                        @if($showPromoStatus) <td>—</td> @endif
                        @if($showPromoLabel)  <td>—</td> @endif
                        @if($showPromoRule)   <td>—</td> @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Subject Performance Summary --}}
<div class="subj-summary-card mb-4">
    <div class="card-header-custom">
        <i class="ri-bar-chart-2-line me-2"></i>Subject Performance Summary
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:12px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="min-width:160px;color:var(--cb-navy);">Subject</th>
                    <th style="text-align:center;color:var(--cb-navy);">Avg</th>
                    <th style="text-align:center;color:var(--cb-navy);">Highest</th>
                    <th style="text-align:center;color:var(--cb-navy);">Lowest</th>
                    <th style="text-align:center;color:var(--cb-navy);">Passed</th>
                    <th style="text-align:center;color:var(--cb-navy);">Failed</th>
                    <th style="text-align:center;color:var(--cb-navy);">Pass Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjects as $subId => $subInfo)
                    @php
                        $st = $subjectStats[$subId] ?? [];
                        $p = $st['passed'] ?? 0;
                        $f = $st['failed'] ?? 0;
                        $t = $p + $f;
                        $pr = $t > 0 ? round($p / $t * 100) : 0;
                    @endphp
                    <tr style="animation:rowSlide .3s ease both;animation-delay:{{ $loop->index * 0.03 }}s;">
                        <td style="font-weight:600;color:var(--cb-navy);">
                            {{ $subInfo['subject_name'] }}
                            @if(!empty($subInfo['subject_code']))
                                <span class="text-muted" style="font-size:10px;">({{ $subInfo['subject_code'] }})</span>
                            @endif
                        </td>
                        <td style="text-align:center;font-weight:700;">{{ $st['avg'] ?? '—' }}</td>
                        <td style="text-align:center;color:#16a34a;font-weight:700;">{{ $st['highest'] ?? '—' }}</td>
                        <td style="text-align:center;color:#dc2626;font-weight:700;">{{ $st['lowest'] ?? '—' }}</td>
                        <td style="text-align:center;color:#16a34a;">{{ $p }}</td>
                        <td style="text-align:center;color:#dc2626;">{{ $f }}</td>
                        <td style="text-align:center;">
                            <span class="{{ $pr >= 50 ? 'score-green' : 'score-red' }}">{{ $pr }}%</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Signature Block --}}
<div class="cb-card mb-4 no-print" style="animation:fadeInUp .5s ease .5s both;">
    <div class="cb-card-header">
        <h6 style="margin:0;font-size:13px;font-weight:700;color:var(--cb-navy);;"><i class="ri-pen-nib-line me-1" style="color:var(--cb-teal)"></i>Authorisation Signatures</h6>
    </div>
    <div class="card-body p-4">
        <div class="row">
            @foreach(['Class Teacher','Head of Department','Vice Principal','Principal'] as $sig)
                <div class="col-3 text-center">
                    <div style="border-top:2px solid var(--cb-border);padding-top:8px;margin-top:40px;font-size:12px;color:var(--cb-muted);font-weight:600;">{{ $sig }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Student List Modal ── --}}
<div class="slist-modal-overlay" id="slistModalOverlay">
    <div class="slist-modal">
        <div class="slist-modal-header">
            <h5><span style="font-size:18px;margin-right:6px;">📋</span>Print Student List Preferences</h5>
            <button class="slist-modal-close" onclick="closeSlistModal()">&times;</button>
        </div>
        <div class="slist-modal-body">
            <div class="d-flex gap-2 flex-wrap mb-4">
                <span class="badge" style="background:#ede9fe;color:#5b21b6;padding:5px 12px;border-radius:20px;font-size:12px;">
                    <i class="ri-school-line me-1"></i>
                    {{ ($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arm_name ?? '') }}
                </span>
                <span class="badge" style="background:#dbeafe;color:#1e40af;padding:5px 12px;border-radius:20px;font-size:12px;">
                    {{ $schoolsession->session ?? '' }}
                </span>
                <span class="badge" style="background:#fef3c7;color:#92400e;padding:5px 12px;border-radius:20px;font-size:12px;">
                    {{ $schoolterm->term ?? '' }}
                </span>
                <span class="badge" style="background:#f1f5f9;color:#475569;padding:5px 12px;border-radius:20px;font-size:12px;">
                    {{ $totalStudents }} Students
                </span>
            </div>

            <div class="mb-4">
                <div class="slist-section-title">
                    <i class="ri-table-line"></i> Student Fields to Include
                </div>
                <div class="field-grid" id="slistFieldGrid">
                    @php
                    $fieldOptions = [
                        ['key' => 'admissionno',   'label' => 'Admission Number', 'default' => true],
                        ['key' => 'lastname',      'label' => 'Last Name',        'default' => true],
                        ['key' => 'firstname',     'label' => 'First Name',       'default' => true],
                        ['key' => 'gender',        'label' => 'Gender',           'default' => false],
                        ['key' => 'dateofbirth',   'label' => 'Date of Birth',    'default' => false],
                        ['key' => 'arm',           'label' => 'Arm / Class',      'default' => true],
                        ['key' => 'total_cum',     'label' => 'Cum Total Score',  'default' => true],
                        ['key' => 'total_term',    'label' => 'Term Total Score', 'default' => false],
                        ['key' => 'cum_ave',       'label' => 'Cumulative Average','default' => true],
                        ['key' => 'position_cum',  'label' => 'Overall Pos (Cum)','default' => true],
                        ['key' => 'position_term', 'label' => 'Overall Pos (Term)','default'=> false],
                        ['key' => 'gpa',           'label' => 'GPA',              'default' => false],
                        ['key' => 'gpa_grade',     'label' => 'GPA Grade (Cum)',  'default' => true],
                    ];
                    @endphp
                    @foreach($fieldOptions as $fo)
                        <label class="field-checkbox-item {{ $fo['default'] ? 'checked' : '' }}">
                            <input type="checkbox" name="list_fields[]" value="{{ $fo['key'] }}" {{ $fo['default'] ? 'checked' : '' }}
                                   onchange="this.closest('.field-checkbox-item').classList.toggle('checked', this.checked)">
                            {{ $fo['label'] }}
                        </label>
                    @endforeach
                </div>
                <div class="d-flex gap-3 mt-3">
                    <label class="field-checkbox-item" style="flex:1;">
                        <input type="checkbox" id="slistShowPhotos">
                        <i class="ri-image-line"></i> Show Student Photos
                    </label>
                    <label class="field-checkbox-item" style="flex:1;">
                        <input type="checkbox" id="slistShowSn" checked>
                        <i class="ri-list-ordered-2"></i> Show Serial Number
                    </label>
                </div>
            </div>

            <div>
                <div class="slist-section-title">
                    <i class="ri-drag-move-line"></i> Recommendation Order
                    <span style="font-size:10px;font-weight:400;color:#64748b;">— drag to reorder, uncheck to exclude</span>
                </div>
                <ul class="promo-order-list" id="promoOrderList">
                    @php
                    $existingStatuses = collect($studentRows)->groupBy('promotion_status')->map->count();
                    $defaultStatusOrder = [
                        ['key' => 'promoted',      'icon' => '✅', 'color' => '#d1fae5', 'text' => '#065f46'],
                        ['key' => 'trial',         'icon' => '⚠️', 'color' => '#fef3c7', 'text' => '#92400e'],
                        ['key' => 'see_principal', 'icon' => '👤', 'color' => '#dbeafe', 'text' => '#1e40af'],
                        ['key' => 'repeated',      'icon' => '🔁', 'color' => '#fee2e2', 'text' => '#991b1b'],
                        ['key' => 'awaiting',      'icon' => '⏳', 'color' => '#f1f5f9', 'text' => '#475569'],
                    ];
                    @endphp
                    @foreach($defaultStatusOrder as $so)
                        @php
                            $count = $existingStatuses[$so['key']] ?? 0;
                            $label = ucfirst(str_replace('_', ' ', $so['key']));
                        @endphp
                        <li class="promo-order-item" data-status="{{ $so['key'] }}" draggable="true">
                            <input type="checkbox" class="promo-group-checkbox" data-status="{{ $so['key'] }}" checked style="margin-right: 8px;">
                            <span class="drag-handle" title="Drag to reorder">⠿</span>
                            <span style="font-size:16px;">{{ $so['icon'] }}</span>
                            <span style="font-weight:600;color:{{ $so['text'] }};flex:1;">
                                {{ $label }}
                                <small style="font-weight:400;color:#94a3b8;margin-left:4px;">({{ $so['key'] }})</small>
                            </span>
                            <span class="badge" style="background:{{ $so['color'] }};color:{{ $so['text'] }};font-size:11px;border-radius:12px;padding:3px 10px;">
                                {{ $count }} student{{ $count !== 1 ? 's' : '' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
                <p style="font-size:11px;color:#94a3b8;margin-top:8px;">
                    <i class="ri-information-line me-1"></i>
                    Groups with zero students or unchecked groups will be omitted from the printed list.
                </p>
            </div>
        </div>
        <div class="slist-modal-footer">
            <button class="btn btn-secondary btn-sm" onclick="closeSlistModal()">Cancel</button>
            <button class="btn btn-primary btn-sm" id="generateListBtn"
                    style="background:linear-gradient(135deg,#3b0764,#7c3aed);border:none;"
                    onclick="generateStudentList()">
                <i class="ri-file-list-line me-1"></i>Generate List
            </button>
        </div>
    </div>
</div>

{{-- Hidden form for student list POST --}}
<form id="slistForm" method="POST" action="{{ route('broadsheet.student-list') }}" target="_blank" style="display:none;">
    @csrf
    <input type="hidden" name="schoolclassid" value="{{ request('schoolclassid') }}">
    <input type="hidden" name="sessionid"     value="{{ request('sessionid') }}">
    <input type="hidden" name="termid"        value="{{ request('termid') }}">
    <input type="hidden" name="grade_basis"   value="{{ $grade_basis ?? 'cum_ave' }}">
    <input type="hidden" name="show_photos"   id="sf_show_photos" value="0">
    <input type="hidden" name="show_sn"       id="sf_show_sn"     value="1">
    <div id="sf_fields"></div>
    <div id="sf_order"></div>
</form>

</div>{{-- /container --}}
</div>{{-- /page-content --}}
</div>{{-- /main-content --}}

<script>
(function () {
    'use strict';

    var GRADE_COLORS = {
        'A1': 'grade-a1', 'B2': 'grade-b2', 'B3': 'grade-b3',
        'C4': 'grade-c4', 'C5': 'grade-c5', 'C6': 'grade-c6',
        'D7': 'grade-d7', 'E8': 'grade-e8', 'F9': 'grade-f9', '-': ''
    };

    function esc(str) {
        var d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function ordinal(n) {
        n = parseInt(n, 10);
        if (!n) return '—';
        var s = ['th','st','nd','rd'];
        var v = n % 100;
        return n + (s[(v - 20) % 10] || s[v] || s[0]);
    }

    function getPctClass(p) {
        return p < 40 ? 'score-red' : (p < 70 ? 'score-amber' : 'score-green');
    }

    function toast(msg, type) {
        document.querySelectorAll('.cb-toast').forEach(function (t) { t.remove(); });
        var icons = { success: 'checkbox-circle-fill', error: 'error-warning-fill', info: 'information-fill', warning: 'alert-fill' };
        var el = document.createElement('div');
        el.className = 'cb-toast cb-toast-' + (type || 'info');
        el.innerHTML = '<i class="ri-' + (icons[type] || icons.info) + '" style="font-size:18px;flex-shrink:0;"></i> ' + esc(msg);
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 4000);
    }

    function animateNumber(elId, target, suffix, decimals) {
        var el = document.getElementById(elId);
        if (!el) return;
        var steps = 60, step = 0, current = 0, inc = target / steps;
        var timer = setInterval(function () {
            step++; current += inc;
            if (step >= steps) { current = target; clearInterval(timer); }
            el.textContent = current.toFixed(decimals || 0) + (suffix || '');
        }, 800 / steps);
    }

    function closeGradePop() {
        var gpop = document.getElementById('cbGradePopup');
        var backdrop = document.getElementById('cbPopupBackdrop');
        if (gpop) { gpop.classList.remove('is-open'); delete gpop.dataset.activeSid; }
        if (backdrop) backdrop.style.display = 'none';
    }

    function gradeBadge(val) {
        return (val && val !== '-')
            ? '<span class="badge ' + (GRADE_COLORS[val] || '') + '" style="font-size:9px;border-radius:6px;">' + esc(val) + '</span>'
            : '<span style="color:#94a3b8;">—</span>';
    }

    function openGradePop(btn) {
        var gpop = document.getElementById('cbGradePopup');
        if (!gpop) return;

        var sid = btn.getAttribute('data-sid') || '';
        var name = btn.getAttribute('data-sname') || '';
        var adm = btn.getAttribute('data-sadm') || '';
        var termObtained = parseFloat(btn.getAttribute('data-term-obtained') || 0);
        var cumObtained = parseFloat(btn.getAttribute('data-cum-obtained') || 0);
        var obtainable = parseFloat(btn.getAttribute('data-obtainable') || 0);
        var termPct = parseFloat(btn.getAttribute('data-term-pct') || 0);
        var cumPct = parseFloat(btn.getAttribute('data-cum-pct') || 0);
        var gpa = parseFloat(btn.getAttribute('data-gpa') || 0);
        var gpaGrade = btn.getAttribute('data-gpa-grade') || '—';
        var posCum = parseInt(btn.getAttribute('data-pos-cum') || 0, 10);
        var posTerm = parseInt(btn.getAttribute('data-pos-term') || 0, 10);
        var posTotal = parseInt(btn.getAttribute('data-pos-total') || 0, 10);
        var hasBF = btn.getAttribute('data-has-bf') === 'true';
        var gradeBasis = btn.getAttribute('data-grade-basis') || 'cum_ave';
        var grades = [];
        try { grades = JSON.parse(btn.getAttribute('data-grades') || '[]'); } catch (e) {}

        document.getElementById('gpopTitle').innerHTML = '<i class="ri-bar-chart-line me-1"></i>' + esc(name) + '\'s Performance';

        var posCumStr = posCum ? (ordinal(posCum) + ' / ' + posTotal) : '—';
        var posTermStr = posTerm ? (ordinal(posTerm) + ' / ' + posTotal) : '—';
        var termColor = termPct < 40 ? '#f43f5e' : (termPct < 70 ? '#f59e0b' : '#22c55e');
        var cumColor = cumPct < 40 ? '#f43f5e' : (cumPct < 70 ? '#f59e0b' : '#22c55e');

        var noBFNote = !hasBF ? '<span style="font-size:9px;opacity:.65;display:block;font-weight:400;margin-top:2px;">no BF yet</span>' : '';
        var noBFBanner = !hasBF ? '<span style="font-size:10px;color:#92400e;font-weight:600;margin-left:auto;background:#fef3c7;padding:2px 8px;border-radius:6px;">First term — no BF on record</span>' : '';
        var basisNote = '<span style="font-size:10px;color:#5b21b6;font-weight:600;margin-left:auto;background:#f5f3ff;padding:2px 8px;border-radius:6px;">Grading basis: ' + (gradeBasis === 'total' ? 'Term Total' : 'Cumulative Average') + '</span>';

        var rows = '';
        if (grades.length) {
            grades.forEach(function (g) {
                var tC = g.term_score > 0 ? (g.term_score < 50 ? 'score-red' : (g.term_score >= 70 ? 'score-green' : 'score-amber')) : '';
                var caC = g.cum_ave_score > 0 ? (g.cum_ave_score < 50 ? 'score-red' : (g.cum_ave_score >= 70 ? 'score-green' : 'score-amber')) : '';

                var termGrBadge = gradeBadge(g.term_grade || g.grade);
                var cumGrBadge = gradeBadge(g.cum_grade || g.grade);

                var tS  = g.term_score    > 0 ? parseFloat(g.term_score).toFixed(1)    : '—';
                var cS  = g.cum_score     > 0 ? parseFloat(g.cum_score).toFixed(1)     : '—';
                var caS = g.cum_ave_score > 0 ? parseFloat(g.cum_ave_score).toFixed(1) : '—';
                var bS  = g.bf_score      > 0 ? parseFloat(g.bf_score).toFixed(1)      : '—';

                function posPill(val, bg, col, label) {
                    if (!val) return '';
                    return '<span style="background:' + bg + ';color:' + col + ';border-radius:3px;padding:1px 4px;font-size:8px;font-weight:700;margin:1px;display:inline-block;">' + label + ':' + val + '</span>';
                }
                var posHTML = posPill(g.pos_class_cum, '#f0fdf4', '#166534', 'CC') +
                            posPill(g.pos_class_total, '#fefce8', '#854d0e', 'CT') +
                            '<br>' +
                            posPill(g.pos_arm_total, '#eff6ff', '#1e40af', 'AC') +
                            posPill(g.pos_arm_cum, '#f5f3ff', '#5b21b6', 'AK');
                var subPos = (g.pos_class_cum || g.pos_class_total || g.pos_arm_total || g.pos_arm_cum)
                    ? '<div style="display:flex;flex-direction:column;align-items:center;gap:1px;line-height:1.2;">' + posHTML + '</div>'
                    : '<span style="color:#94a3b8;">—</span>';

                rows += '<tr>' +
                        '<td style="text-align:left;font-weight:600;padding-left:12px;">' + esc(g.subject) + '</td>' +
                        '<td><div class="score-pair">' +
                        '<div class="score-cell-inner term"><span style="font-size:8px;opacity:.7;">T</span><span class="' + tC + '">' + tS + '</span></div>' +
                        '<div class="score-cell-inner cum"><span style="font-size:8px;opacity:.7;">BF</span><span>' + bS + '</span></div>' +
                        '</div></td>' +
                        '<td><div class="score-cell-inner cum" style="justify-content:center;"><span style="font-size:8px;opacity:.7;">C</span><span>' + cS + '</span></div></td>' +
                        '<td><div class="score-cell-inner cumave" style="justify-content:center;"><span style="font-size:8px;opacity:.7;">CA</span><span class="' + caC + '">' + caS + '</span></div></td>' +
                        '<td>' + termGrBadge + '</td>' +
                        '<td>' + cumGrBadge + '</td>' +
                        '<td>' + subPos + '</td>' +
                        '</tr>';
            });
        } else {
            rows = '<tr><td colspan="7" style="text-align:center;padding:16px;color:#94a3b8;">No subject records</td></tr>';
        }

        var body = document.getElementById('gpopBody');
        body.innerHTML =
            '<div class="gpop-perf-strip">' +
            '<div style="font-size:11px;font-weight:700;opacity:.8;margin-bottom:6px;"><i class="ri-dashboard-line me-1"></i>Performance Snapshot</div>' +
            '<div class="gpop-perf-grid">' +
            '<div class="gpop-perf-item"><div class="gpop-perf-lbl">Adm. No</div><div class="gpop-perf-val" style="font-size:12px;">' + esc(adm) + '</div></div>' +
            '<div class="gpop-perf-item"><div class="gpop-perf-lbl">Term Total</div><div class="gpop-perf-val">' + termObtained.toFixed(1) + '</div></div>' +
            '<div class="gpop-perf-item"><div class="gpop-perf-lbl">Cum Total</div><div class="gpop-perf-val ' + (hasBF ? 'score-green' : '') + '">' + cumObtained.toFixed(1) + noBFNote + '</div></div>' +
            '<div class="gpop-perf-item"><div class="gpop-perf-lbl">Obtainable</div><div class="gpop-perf-val">' + obtainable.toFixed(0) + '</div></div>' +
            '<div class="gpop-perf-item"><div class="gpop-perf-lbl">% (Term)</div><div class="gpop-perf-val ' + getPctClass(termPct) + '" id="ppct-term">0%</div></div>' +
            '<div class="gpop-perf-item"><div class="gpop-perf-lbl">% (Cum)</div><div class="gpop-perf-val ' + getPctClass(cumPct) + '" id="ppct-cum">0%</div></div>' +
            '</div>' +
            '<div style="margin-top:10px;">' +
            '<div style="font-size:9px;opacity:.7;margin-bottom:3px;">Term % — ' + termPct.toFixed(1) + '%</div>' +
            '<div class="pct-bar-wrap"><div id="pbar-term" class="pct-bar" style="width:0%;background:#22c55e;"></div></div>' +
            '<div style="font-size:9px;opacity:.7;margin:5px 0 3px;">Cum % — ' + cumPct.toFixed(1) + '%' + (!hasBF ? ' <span style="opacity:.6;">(no BF — same as term)</span>' : '') + '</div>' +
            '<div class="pct-bar-wrap"><div id="pbar-cum" class="pct-bar" style="width:0%;background:#22c55e;"></div></div>' +
            '</div>' +
            '<div style="margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">' +
            '<span class="badge" style="background:#fef3c7;color:#92400e;font-size:10px;border-radius:6px;padding:3px 10px;">Overall T-Pos: ' + posTermStr + '</span>' +
            '<span class="badge" style="background:#dbeafe;color:#1e40af;font-size:10px;border-radius:6px;padding:3px 10px;">Overall C-Pos: ' + posCumStr + '</span>' +
            '</div>' +
            '</div>' +
            '<div class="gpop-legend">' +
            '<span style="font-size:10px;font-weight:700;color:var(--cb-muted);">Legend:</span>' +
            '<span class="gpop-legend-item"><span class="gpop-legend-dot t"></span>Term score</span>' +
            '<span class="gpop-legend-item"><span class="gpop-legend-dot c"></span>BF / Cum (raw sum)</span>' +
            '<span class="gpop-legend-item"><span class="gpop-legend-dot ca"></span>Cum Ave</span>' +
            '<span style="font-size:9px;color:#64748b;margin-left:4px;"><b>CC</b>=Cls Cum &nbsp;<b>CT</b>=Cls Tot &nbsp;<b>AC</b>=Arm Tot &nbsp;<b>AK</b>=Arm Cum</span>' +
            basisNote +
            noBFBanner +
            '</div>' +
            '<div class="gpop-scroll">' +
            '<table class="gpop-table"><thead><tr>' +
            '<th style="text-align:left;padding-left:12px;width:22%;">Subject</th>' +
            '<th style="width:14%;">Term / BF</th>' +
            '<th style="width:12%;">Cum</th>' +
            '<th style="width:12%;">Cum Ave</th>' +
            '<th style="width:10%;">T.Grd</th>' +
            '<th style="width:10%;">C.Grd</th>' +
            '<th style="width:20%;">Positions<br><small style="opacity:.65;font-weight:400;font-size:8px;">CC · CT · AC · AK</small></th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table>' +
            '</div>' +
            '<div class="gpop-summary">' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">Term Total</div><div class="gpop-sum-val">' + termObtained.toFixed(1) + '</div></div>' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">Cum Total</div><div class="gpop-sum-val ' + (hasBF ? 'score-green' : '') + '">' + cumObtained.toFixed(1) + (!hasBF ? '<span class="bf-note">= Term (no BF)</span>' : '') + '</div></div>' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">Obtainable</div><div class="gpop-sum-val">' + obtainable.toFixed(0) + '</div></div>' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">% (Term)</div><div class="gpop-sum-val ' + getPctClass(termPct) + '">' + termPct.toFixed(1) + '%</div></div>' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">% (Cum)</div><div class="gpop-sum-val ' + getPctClass(cumPct) + '">' + cumPct.toFixed(1) + '%</div></div>' +
            '<div class="gpop-sum-item"><div class="gpop-sum-lbl">GPA</div><div class="gpop-sum-val ' + getPctClass(gpa * 20) + '">' + gpa.toFixed(2) + ' <span style="font-size:11px;">' + esc(gpaGrade) + '</span></div></div>' +
            '</div>';

        var pw = 600;
        var ph = Math.min(660, window.innerHeight - 40);
        var rect = btn.getBoundingClientRect();
        var vw = window.innerWidth;
        var vh = window.innerHeight;
        var top = rect.bottom + 8;
        var left = rect.left + rect.width / 2 - pw / 2;
        if (top + ph > vh - 8) top = Math.max(8, rect.top - ph - 8);
        if (left < 8) left = 8;
        if (left + pw > vw - 8) left = vw - pw - 8;

        gpop.style.cssText = 'width:' + pw + 'px;top:' + top + 'px;left:' + left + 'px;max-height:' + ph + 'px;';
        gpop.dataset.activeSid = sid;
        gpop.classList.add('is-open');
        document.getElementById('cbPopupBackdrop').style.display = 'block';

        setTimeout(function () {
            var termEl = document.getElementById('ppct-term');
            var cumEl = document.getElementById('ppct-cum');
            var termBar = document.getElementById('pbar-term');
            var cumBar = document.getElementById('pbar-cum');

            function animPct(el, target) {
                if (!el) return;
                var steps = 50, step = 0, current = 0, inc = target / steps;
                var t = setInterval(function () {
                    step++; current += inc;
                    if (step >= steps) { current = target; clearInterval(t); }
                    el.textContent = current.toFixed(1) + '%';
                }, 800 / steps);
            }
            animPct(termEl, termPct);
            animPct(cumEl, cumPct);

            if (termBar) { termBar.style.transition = 'width .8s ease, background-color .8s ease'; termBar.style.width = termPct + '%'; termBar.style.backgroundColor = termColor; }
            if (cumBar) { cumBar.style.transition = 'width .8s ease, background-color .8s ease'; cumBar.style.width = cumPct + '%'; cumBar.style.backgroundColor = cumColor; }
        }, 60);
    }

    var tableRows = [];

    function initSearch() {
        tableRows = Array.from(document.querySelectorAll('#broadsheetTable tbody tr[data-student-id]'));
        var searchEl = document.getElementById('searchStudent');
        if (searchEl) {
            searchEl.addEventListener('input', function () {
                var q = this.value.toLowerCase().trim();
                var count = 0;
                tableRows.forEach(function (row) {
                    var name = (row.getAttribute('data-student-name') || '').toLowerCase();
                    var adm = (row.getAttribute('data-admission') || '').toLowerCase();
                    var show = !q || name.indexOf(q) !== -1 || adm.indexOf(q) !== -1;
                    row.style.display = show ? '' : 'none';
                    if (show) count++;
                });
                if (q) toast('Found ' + count + ' student(s)', 'info');
            });
        }
    }

    function highlightTop(n) {
        var visible = tableRows.filter(function (r) { return r.style.display !== 'none'; });
        visible.sort(function (a, b) { return parseFloat(b.getAttribute('data-total-cum') || 0) - parseFloat(a.getAttribute('data-total-cum') || 0); });
        visible.slice(0, n).forEach(function (r) { r.style.backgroundColor = '#fef9c3'; r.style.outline = '2px solid #d97706'; });
        toast('Top ' + n + ' students highlighted', 'success');
    }

    function highlightFailures() {
        var c = 0;
        tableRows.forEach(function (r) {
            if (r.getAttribute('data-has-failure') === 'true') { r.style.backgroundColor = '#fee2e2'; r.style.outline = '2px solid #dc2626'; c++; }
        });
        toast(c + ' student(s) with F9 highlighted', 'warning');
    }

    function highlightBelowAvg() {
        var totals = tableRows.map(function (r) { return parseFloat(r.getAttribute('data-total-cum') || 0); }).filter(function (v) { return v > 0; });
        var avg = totals.length ? totals.reduce(function (a, b) { return a + b; }, 0) / totals.length : 0;
        var c = 0;
        tableRows.forEach(function (r) {
            var v = parseFloat(r.getAttribute('data-total-cum') || 0);
            if (v > 0 && v < avg) { r.style.backgroundColor = '#fff7ed'; r.style.outline = '2px solid #f97316'; c++; }
        });
        toast(c + ' student(s) below class average', 'info');
    }

    function highlightPromoted() {
        var c = 0;
        tableRows.forEach(function (r) {
            var status = r.querySelector('.promo-badge')?.textContent || '';
            if (status.includes('Promoted')) {
                r.style.backgroundColor = '#d1fae5';
                r.style.outline = '2px solid #10b981';
                c++;
            }
        });
        toast(c + ' promoted student(s) highlighted', 'success');
    }

    function highlightTrial() {
        var c = 0;
        tableRows.forEach(function (r) {
            var status = r.querySelector('.promo-badge')?.textContent || '';
            if (status.includes('Trial')) {
                r.style.backgroundColor = '#fef3c7';
                r.style.outline = '2px solid #f59e0b';
                c++;
            }
        });
        toast(c + ' student(s) on trial highlighted', 'warning');
    }

    function highlightSeePrincipal() {
        var c = 0;
        tableRows.forEach(function (r) {
            var status = r.querySelector('.promo-badge')?.textContent || '';
            if (status.includes('See Principal') || status.includes('see_principal')) {
                r.style.backgroundColor = '#dbeafe';
                r.style.outline = '2px solid #3b82f6';
                c++;
            }
        });
        toast(c + ' student(s) to see principal highlighted', 'info');
    }

    function highlightRepeated() {
        var c = 0;
        tableRows.forEach(function (r) {
            var status = r.querySelector('.promo-badge')?.textContent || '';
            if (status.includes('Repeat')) {
                r.style.backgroundColor = '#fee2e2';
                r.style.outline = '2px solid #ef4444';
                c++;
            }
        });
        toast(c + ' repeat student(s) highlighted', 'error');
    }

    function highlightAwaiting() {
        var c = 0;
        tableRows.forEach(function (r) {
            var status = r.querySelector('.promo-badge')?.textContent || '';
            if (status.includes('Awaiting')) {
                r.style.backgroundColor = '#f1f5f9';
                r.style.outline = '2px solid #94a3b8';
                c++;
            }
        });
        toast(c + ' student(s) awaiting decision highlighted', 'info');
    }

    function initLocate() {
        var el = document.getElementById('locateStudent');
        if (!el) return;
        el.addEventListener('change', function () {
            var val = this.value;
            if (!val) return;
            tableRows.forEach(function (r) { r.style.outline = ''; r.style.backgroundColor = ''; });

            if (val === 'top5') { highlightTop(5); }
            else if (val === 'top10') { highlightTop(10); }
            else if (val === 'failures') { highlightFailures(); }
            else if (val === 'below_avg') { highlightBelowAvg(); }
            else if (val === 'promoted') { highlightPromoted(); }
            else if (val === 'trial') { highlightTrial(); }
            else if (val === 'see_principal') { highlightSeePrincipal(); }
            else if (val === 'repeated') { highlightRepeated(); }
            else if (val === 'awaiting') { highlightAwaiting(); }
            else if (val.indexOf('student_') === 0) {
                var id = val.replace('student_', '');
                var row = document.querySelector('tr[data-student-id="' + id + '"]');
                if (row) {
                    row.style.outline = '3px solid var(--cb-teal)';
                    row.style.backgroundColor = '#f0fdf9';
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    toast('Located: ' + (row.getAttribute('data-student-name') || ''), 'success');
                }
            }
            var self = this;
            setTimeout(function () { self.value = ''; }, 200);
        });
    }

    window.scrollToTop = function () { window.scrollTo({ top: 0, behavior: 'smooth' }); };
    window.closeSlistModal = function() { document.getElementById('slistModalOverlay').classList.remove('open'); };
    window.openStudentListModal = function() { document.getElementById('slistModalOverlay').classList.add('open'); };

    window.switchGradeBasis = function (basis) {
        document.getElementById('gb_input').value = basis;
        document.getElementById('gradeBasisForm').submit();
    };

    window.generateStudentList = function() {
        var btn = document.getElementById('generateListBtn');
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Opening…';

        var fieldDivEl = document.getElementById('sf_fields');
        if (fieldDivEl) {
            fieldDivEl.innerHTML = '';
            document.querySelectorAll('#slistFieldGrid input[name="list_fields[]"]:checked').forEach(function(cb, i) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'list_fields[' + i + ']';
                inp.value = cb.value;
                fieldDivEl.appendChild(inp);
            });
        }

        var orderDivEl = document.getElementById('sf_order');
        if (orderDivEl) {
            orderDivEl.innerHTML = '';
            document.querySelectorAll('#promoOrderList .promo-order-item').forEach(function(item, i) {
                var checkbox = item.querySelector('.promo-group-checkbox');
                if (checkbox && checkbox.checked) {
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'recommendation_order[' + i + ']';
                    inp.value = item.getAttribute('data-status');
                    orderDivEl.appendChild(inp);
                }
            });
        }

        var showPhotos = document.getElementById('slistShowPhotos');
        var showSn = document.getElementById('slistShowSn');
        var sfShowPhotos = document.getElementById('sf_show_photos');
        var sfShowSn = document.getElementById('sf_show_sn');
        if (sfShowPhotos) sfShowPhotos.value = (showPhotos && showPhotos.checked) ? '1' : '0';
        if (sfShowSn) sfShowSn.value = (showSn && showSn.checked) ? '1' : '0';

        var slistForm = document.getElementById('slistForm');
        if (slistForm) slistForm.submit();

        setTimeout(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-file-list-line me-1"></i>Generate List';
            var modal = document.getElementById('slistModalOverlay');
            if (modal) modal.classList.remove('open');
        }, 1500);
    };

    // Drag-and-drop for promo order list
    (function initDnD() {
        var list = document.getElementById('promoOrderList');
        if (!list) return;
        var draggingEl = null;
        list.addEventListener('dragstart', function(e) {
            draggingEl = e.target.closest('.promo-order-item');
            if (!draggingEl) return;
            draggingEl.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        list.addEventListener('dragend', function() {
            if (draggingEl) draggingEl.classList.remove('dragging');
            if (list) list.querySelectorAll('.promo-order-item').forEach(function(i) { i.classList.remove('drag-over'); });
            draggingEl = null;
        });
        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            var target = e.target.closest('.promo-order-item');
            if (!target || target === draggingEl || !list) return;
            list.querySelectorAll('.promo-order-item').forEach(function(i) { i.classList.remove('drag-over'); });
            target.classList.add('drag-over');
            var rect = target.getBoundingClientRect();
            var midY = rect.top + rect.height / 2;
            if (e.clientY < midY) list.insertBefore(draggingEl, target);
            else list.insertBefore(draggingEl, target.nextSibling);
        });
        list.addEventListener('drop', function(e) { e.preventDefault(); });
    })();

    var modalOverlay = document.getElementById('slistModalOverlay');
    if (modalOverlay) modalOverlay.addEventListener('click', function(e) { if (e.target === this) window.closeSlistModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { window.closeSlistModal(); closeGradePop(); } });

    function animateStats() {
        var rows = Array.from(document.querySelectorAll('#broadsheetTable tbody tr[data-student-id]'));
        if (!rows.length) return;
        var totalPct = 0, topCum = -1, topName = '—';
        rows.forEach(function (r) {
            totalPct += parseFloat(r.getAttribute('data-cum-pct') || 0);
            var cum = parseFloat(r.getAttribute('data-total-cum') || 0);
            if (cum > topCum) { topCum = cum; topName = r.getAttribute('data-student-name') || '—'; }
        });
        var avg = rows.length ? totalPct / rows.length : 0;
        animateNumber('statAvgPct', avg, '%', 1);
        var topEl = document.getElementById('statTopPerformer');
        if (topEl) topEl.textContent = topName.split(' ').map(function (w) { return w.charAt(0).toUpperCase() + w.slice(1); }).join(' ');
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['cbGradePopup', 'cbPopupBackdrop'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.parentNode !== document.body) document.body.appendChild(el);
        });
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.grade-trigger-btn');
            if (!btn) return;
            e.stopPropagation(); e.preventDefault();
            var gpop = document.getElementById('cbGradePopup');
            if (gpop && gpop.classList.contains('is-open') && gpop.dataset.activeSid === btn.getAttribute('data-sid')) { closeGradePop(); return; }
            closeGradePop();
            setTimeout(function () { openGradePop(btn); }, 16);
        });
        var closeBtn = document.getElementById('gpopCloseBtn');
        if (closeBtn) closeBtn.addEventListener('click', closeGradePop);
        document.addEventListener('click', function (e) { if (e.target && e.target.id === 'cbPopupBackdrop') closeGradePop(); });
        initSearch();
        initLocate();
        animateStats();
    });
})();
</script>
@endsection
