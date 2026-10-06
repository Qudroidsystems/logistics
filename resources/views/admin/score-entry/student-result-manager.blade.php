{{-- resources/views/admin/score-entry/student-result-manager.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
/* ═══════════════════════════════════════════════════════════════
   DESIGN SYSTEM — identical to scoresheet index
   ═══════════════════════════════════════════════════════════════ */
:root {
    --ss-primary:#1e3a5f; --ss-accent:#2563eb; --ss-success:#16a34a;
    --ss-warning:#d97706; --ss-danger:#dc2626; --ss-muted:#6b7280;
    --ss-border:#e2e8f0; --ss-bg:#f8fafc; --ss-card:#ffffff;
    --ss-radius:10px; --ss-shadow:0 1px 4px rgba(0,0,0,.08);
}
.spin{animation:spin .8s linear infinite}
@keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}

/* ── score inputs (exact copy from scoresheet) ── */
.score-input{width:72px;min-width:72px;height:36px;padding:4px 6px;border:1.5px solid var(--ss-border);
  border-radius:6px;font-size:13px;text-align:center;background:#fff;transition:border-color .15s,box-shadow .15s}
.score-input:focus{outline:none;border-color:var(--ss-accent);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.score-input.is-invalid{border-color:var(--ss-danger)!important;background:#fef2f2}
.score-input.is-saved{border-color:var(--ss-success)!important;background:#f0fdf4}
.score-input:disabled{background:#f3f4f6;cursor:not-allowed;opacity:.7}

/* ── STUDENTS LIST TABLE ── */
#studentsTable{font-size:12.5px;width:100%;border-collapse:collapse}
#studentsTable thead tr{background:var(--ss-primary);color:#fff}
#studentsTable thead th{padding:10px 8px;font-weight:600;white-space:nowrap;border:none}
#studentsTable tbody td{padding:8px 10px;vertical-align:middle;border-bottom:1px solid var(--ss-border)}
.row-complete{background:#f0fdf4!important}
.row-incomplete{background:#fffbeb!important}
.row-pending{background:#fef2f2!important}

/* ── ROW ENTRANCE & HOVER (identical to scoresheet) ── */
#studentsTableBody tr[data-sid]{opacity:0;transform:translateY(14px);
  transition:opacity .38s cubic-bezier(.25,.46,.45,.94),transform .38s cubic-bezier(.25,.46,.45,.94),background .18s ease;
  will-change:opacity,transform}
#studentsTableBody tr[data-sid].row-visible{opacity:1;transform:translateY(0)}
#studentsTableBody tr[data-sid]:hover{background:#f0f6ff!important;box-shadow:inset 3px 0 0 #2563eb;
  transform:translateY(-1px)!important;
  transition:background .14s ease,box-shadow .18s ease,transform .18s cubic-bezier(.34,1.4,.64,1);
  position:relative;z-index:1}
#studentsTableBody tr.row-complete:hover{background:#e6faf0!important}
#studentsTableBody tr.row-incomplete:hover{background:#fffbeb!important}
#studentsTableBody tr.row-pending:hover{background:#fef0f0!important}
#studentsTableBody tr[data-sid]:hover .student-avatar-img{transform:scale(1.12);
  transition:transform .22s cubic-bezier(.34,1.4,.64,1);box-shadow:0 2px 8px rgba(0,0,0,.15)}
.student-avatar-img{transition:transform .18s ease,box-shadow .18s ease;cursor:pointer}
#studentsTableBody tr[data-sid]:hover .badge{transform:scale(1.06);
  transition:transform .18s cubic-bezier(.34,1.4,.64,1)}

/* ── SCORESHEET-STYLE MODAL TABLE ── */
#modalScoreTable{font-size:12.5px;width:100%;border-collapse:collapse}
#modalScoreTable thead tr{background:var(--ss-primary);color:#fff}
#modalScoreTable thead th{padding:10px 8px;font-weight:600;white-space:nowrap;border:none}
#modalScoreTable tbody td{padding:6px 8px;vertical-align:middle;border-bottom:1px solid var(--ss-border)}

/* modal table row entrance */
#modalScoreTable tbody tr[data-midx]{opacity:0;transform:translateY(10px);
  transition:opacity .32s cubic-bezier(.25,.46,.45,.94),transform .32s cubic-bezier(.25,.46,.45,.94),background .15s ease;
  will-change:opacity,transform}
#modalScoreTable tbody tr[data-midx].row-visible{opacity:1;transform:translateY(0)}
#modalScoreTable tbody tr[data-midx]:hover{background:#f0f6ff!important;box-shadow:inset 3px 0 0 #2563eb;
  transform:translateY(-1px)!important;
  transition:background .14s,box-shadow .18s,transform .18s cubic-bezier(.34,1.4,.64,1);
  position:relative;z-index:1}
#modalScoreTable tbody tr.mrow-saved:hover{background:#e6faf0!important}
#modalScoreTable tbody tr.mrow-unsaved:hover{background:#fef0f0!important}
#modalScoreTable tbody tr[data-midx]:hover .score-input{border-color:#93c5fd;box-shadow:0 1px 6px rgba(37,99,235,.10)}
#modalScoreTable tbody tr[data-midx]:hover .badge{transform:scale(1.06);transition:transform .18s cubic-bezier(.34,1.4,.64,1)}

/* ── grade / position animations (exact copy) ── */
.grade-badge,.cum-grade-badge{display:inline-block;transition:all .25s ease;
  font-weight:700;font-size:13px;min-width:28px;text-align:center}
.grade-badge.updating,.cum-grade-badge.updating{opacity:.5;transform:scale(.9)}
.grade-badge.updated,.cum-grade-badge.updated{animation:gradeFlash .4s ease}
@keyframes gradeFlash{0%{transform:scale(1.15)}50%{transform:scale(1.2)}100%{transform:scale(1)}}
.pos-flash{animation:posFlash .5s cubic-bezier(0.34,1.4,0.64,1)}
@keyframes posFlash{
  0%{transform:scale(1);opacity:1}30%{transform:scale(1.25);opacity:.7}
  60%{transform:scale(.95);opacity:1}100%{transform:scale(1);opacity:1}}

/* ── stat cards ── */
.stat-card{background:var(--ss-card);border:1px solid var(--ss-border);border-radius:var(--ss-radius);
  padding:14px 18px;box-shadow:var(--ss-shadow);transition:transform .15s}
.stat-card:hover{transform:translateY(-2px)}
.stat-card .stat-value{font-size:22px;font-weight:700;color:var(--ss-primary)}
.stat-card .stat-label{font-size:11px;color:var(--ss-muted);margin-top:2px}
.stat-card .stat-icon{font-size:28px;opacity:.15;float:right;margin-top:-6px}
.pass-bar{height:8px;border-radius:4px;background:#e2e8f0;overflow:hidden;margin-top:6px}
.pass-bar-fill{height:100%;border-radius:4px;transition:width .4s}

/* ── Hero ── */
.srm-hero{background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#7c3aed 100%);
  border-radius:12px;padding:28px 32px;margin-bottom:24px;color:#fff}
.btn-hero{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:#fff;
  padding:8px 20px;border-radius:8px;font-size:14px;font-weight:500;transition:all .2s;
  text-decoration:none;display:inline-flex;align-items:center;gap:8px}
.btn-hero:hover{background:rgba(255,255,255,.3);color:#fff;transform:translateY(-2px)}

/* ── Filter ── */
.filter-card{background:#fff;border-radius:12px;border:1px solid #e2e8f0;
  padding:20px 24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)}

/* ── Status badges ── */
.status-badge{display:inline-flex;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600}
.status-complete{background:#dcfce7;color:#15803d}
.status-incomplete{background:#fef3c7;color:#b45309}
.status-pending{background:#fee2e2;color:#dc2626}

/* ── Modal structure ── */
.modal-xl-srm{max-width:95%;width:1360px}
.modal-body-scroll{max-height:66vh;overflow-y:auto;padding:0}
.modal-body-scroll::-webkit-scrollbar{width:6px}
.modal-body-scroll::-webkit-scrollbar-track{background:#f1f5f9}
.modal-body-scroll::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}

/* ── Student info header (modal) ── */
.student-info-header{display:flex;align-items:center;gap:20px;padding:16px 24px;
  background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 70%,#7c3aed 100%);
  color:#fff;flex-wrap:wrap}
.student-avatar-large{width:68px;height:68px;border-radius:50%;
  background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.4);
  display:flex;align-items:center;justify-content:center;
  font-size:26px;font-weight:bold;flex-shrink:0;overflow:hidden;cursor:pointer}
.student-avatar-large img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.summary-stats{display:flex;gap:10px;margin-top:8px;flex-wrap:wrap}
.stat-box{background:rgba(255,255,255,.15);border-radius:10px;padding:7px 14px;
  text-align:center;min-width:78px;backdrop-filter:blur(4px)}
.stat-box .label{font-size:10px;text-transform:uppercase;letter-spacing:.5px;opacity:.8}
.stat-box .value{font-size:18px;font-weight:bold;line-height:1.2}

/* ── VIEW TOGGLE ── */
.view-toggle-btn{padding:6px 14px;border-radius:8px;border:1.5px solid var(--ss-border);
  background:#fff;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;color:var(--ss-muted)}
.view-toggle-btn.active{background:var(--ss-primary);color:#fff;border-color:var(--ss-primary)}

/* ── card view (legacy) ── */
.subject-score-card{background:#f8fafc;border-radius:12px;padding:14px;
  margin:0 14px 14px;border:1px solid var(--ss-border);transition:box-shadow .2s,border-color .2s}
.subject-score-card:hover{box-shadow:0 4px 16px rgba(37,99,235,.1);border-color:#bfdbfe}
.subject-score-card:first-of-type{margin-top:14px}
.subject-header{background:var(--ss-primary);color:#fff;padding:10px 14px;
  border-radius:8px;margin-bottom:12px;display:flex;justify-content:space-between;
  align-items:center;flex-wrap:wrap;gap:8px}
.assessment-row{display:flex;align-items:center;gap:10px;padding:7px 10px;
  background:#fff;border-radius:8px;margin-bottom:7px;border:1px solid transparent;
  transition:border-color .15s,box-shadow .15s}
.assessment-row:hover{border-color:#bfdbfe;box-shadow:0 1px 6px rgba(37,99,235,.08)}
.assessment-label{width:120px;font-weight:600;color:#475569;font-size:12.5px;flex-shrink:0}
.assessment-max{font-size:11px;color:#94a3b8;width:48px;flex-shrink:0}
.subject-metrics{margin-top:10px;padding:10px;border-top:1px solid var(--ss-border);
  background:#fff;border-radius:8px;display:flex;flex-wrap:wrap;gap:12px;
  align-items:center;justify-content:space-between}
.metric-item{text-align:center;min-width:52px}
.metric-item .m-label{font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;font-weight:600}
.metric-item .m-value{font-size:17px;font-weight:800;color:var(--ss-primary);line-height:1.2}
.btn-save-subject{background:#10b981;color:#fff;border:none;padding:6px 16px;
  border-radius:8px;cursor:pointer;font-size:12.5px;font-weight:500;transition:all .2s;
  display:inline-flex;align-items:center;gap:5px}
.btn-save-subject:hover:not(:disabled){background:#059669;transform:translateY(-1px);
  box-shadow:0 4px 12px rgba(16,185,129,.3)}
.btn-save-subject:disabled{background:#9ca3af;cursor:not-allowed;transform:none}

/* ── col-group for visibility modal ── */
.col-group{border:1px solid var(--ss-border);border-radius:8px;padding:10px 14px;margin-bottom:10px}
.col-group h6{color:var(--ss-primary);font-weight:600;margin-bottom:8px}

/* ── No subjects msg ── */
.no-subjects-msg{background:#fef3c7;border:1px solid #fde68a;border-radius:8px;
  padding:20px;color:#92400e;text-align:center;margin:16px}

/* ── score input TOOLTIP (exact copy from scoresheet) ── */
#srmTooltip{display:none;position:fixed;z-index:99990;background:#fff;
  border:.5px solid #cbd5e1;border-radius:10px;padding:10px 13px;width:230px;
  box-shadow:0 4px 20px rgba(0,0,0,.10),0 1px 4px rgba(0,0,0,.06);
  pointer-events:none;opacity:0;transition:opacity .15s ease}
.tip-top{display:flex;align-items:center;gap:8px;margin-bottom:8px;
  padding-bottom:8px;border-bottom:.5px solid #e8ecf0}
.tip-avatar{width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid #e2e8f0}
.tip-name{font-size:12px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tip-adm{font-size:10px;color:#64748b;margin-top:1px}
.tip-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:8px}
.tip-stat{text-align:center}
.tip-stat-label{font-size:9px;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;font-weight:600;margin-bottom:2px}
.tip-stat-val{font-size:15px;font-weight:700;font-variant-numeric:tabular-nums;line-height:1}
.tip-divider{height:.5px;background:#e8ecf0;margin-bottom:8px}
.tip-prog-labels{display:flex;justify-content:space-between;font-size:10px;color:#94a3b8;margin-bottom:3px}
.tip-prog-track{height:3px;background:#f1f5f9;border-radius:2px;overflow:hidden}
.tip-prog-fill{height:100%;border-radius:2px;background:#2563eb;width:0%;transition:width .3s ease,background .3s ease}

/* ── APPLE-STYLE SAVE MODAL ── */
#srmSaveOverlay{display:none;position:fixed;inset:0;z-index:99999;
  background:rgba(0,0,0,.30);align-items:center;justify-content:center;backdrop-filter:blur(2px)}
#srmSaveOverlay.ss-visible{display:flex!important;animation:ssOverlayIn .2s ease forwards}
@keyframes ssOverlayIn{from{opacity:0}to{opacity:1}}
#srmSaveModal{background:#fff;border-radius:20px;border:.5px solid rgba(0,0,0,.10);
  box-shadow:0 24px 64px rgba(0,0,0,.18),0 4px 16px rgba(0,0,0,.08);
  padding:32px 36px 26px;width:310px;text-align:center;
  transform:scale(.85) translateY(16px);opacity:0;
  transition:transform .32s cubic-bezier(.34,1.3,.64,1),opacity .22s ease}
#srmSaveOverlay.ss-visible #srmSaveModal{transform:scale(1) translateY(0);opacity:1}
#srmSaveOverlay.ss-closing #srmSaveModal{transform:scale(.88) translateY(10px);opacity:0}
#srmSaveOverlay.ss-closing{animation:ssOverlayOut .22s ease forwards}
@keyframes ssOverlayOut{from{opacity:1}to{opacity:0}}
.ss-icon-ring{position:relative;width:56px;height:56px;margin:0 auto 16px}
.ss-arc-svg{position:absolute;inset:0;width:100%;height:100%}
.ss-icon-center{position:absolute;inset:6px;border-radius:50%;
  background:rgba(30,58,95,.09);display:flex;align-items:center;justify-content:center;transition:background .3s ease}
.ss-modal-title{font-size:17px;font-weight:700;color:#0f172a;margin-bottom:4px}
.ss-modal-sub{font-size:13px;color:#64748b;margin-bottom:16px;min-height:18px}
.ss-progress-track{height:4px;background:#f1f5f9;border-radius:2px;overflow:hidden;margin-bottom:10px}
.ss-progress-fill{height:100%;border-radius:2px;background:#1e3a5f;width:0%;
  transition:width .38s cubic-bezier(.4,0,.2,1),background .3s ease}
.ss-count-row{display:flex;justify-content:space-between;font-size:12px;color:#94a3b8}
.ss-count-num{font-variant-numeric:tabular-nums;font-weight:600;color:#475569}
.ss-check-path{stroke-dasharray:22;stroke-dashoffset:22;
  transition:stroke-dashoffset .55s cubic-bezier(.4,0,.2,1) .1s}
.ss-check-path.drawn{stroke-dashoffset:0}

/* ── grade distribution pills ── */
.grade-strip{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.grade-pill{flex:1;min-width:60px;text-align:center;border-radius:8px;padding:7px 4px;font-weight:700;font-size:13px}

@media(max-width:768px){
  .modal-xl-srm{max-width:98%}
  .score-input{width:64px;min-width:64px;height:42px;font-size:1rem}
  .stat-card .stat-value{font-size:18px}
  #srmSaveModal{width:280px;padding:26px 24px 22px}
  #srmTooltip{width:calc(100vw - 24px)}
}
</style>

{{-- ══ APPLE-STYLE SAVE OVERLAY ══ --}}
<div id="srmSaveOverlay">
  <div id="srmSaveModal">
    <div class="ss-icon-ring">
      <svg class="ss-arc-svg" viewBox="0 0 56 56" fill="none">
        <circle cx="28" cy="28" r="25" stroke="#e2e8f0" stroke-width="2.5"/>
        <circle id="srmArcFg" cx="28" cy="28" r="25"
          stroke="#1e3a5f" stroke-width="2.5" stroke-linecap="round"
          stroke-dasharray="157.08" stroke-dashoffset="157.08"
          transform="rotate(-90 28 28)"
          style="transition:stroke-dashoffset .38s cubic-bezier(.4,0,.2,1),stroke .3s ease"/>
      </svg>
      <div class="ss-icon-center" id="srmIconCenter">
        <svg id="srmIconSave" width="18" height="18" viewBox="0 0 18 18" fill="none">
          <rect x="2.5" y="2.5" width="13" height="13" rx="2.5" stroke="#1e3a5f" stroke-width="1.5"/>
          <rect x="5.5" y="2.5" width="5" height="4.5" rx="1" fill="#1e3a5f" opacity=".45"/>
          <path d="M5 10.5h8M5 13h5.5" stroke="#1e3a5f" stroke-width="1.3" stroke-linecap="round"/>
        </svg>
        <svg id="srmIconCheck" width="18" height="18" viewBox="0 0 18 18" fill="none" style="display:none">
          <polyline class="ss-check-path" id="srmCheckPath" points="3.5,9.5 7.5,13.5 14.5,5.5"
            stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg id="srmIconX" width="16" height="16" viewBox="0 0 16 16" fill="none" style="display:none">
          <line x1="3.5" y1="3.5" x2="12.5" y2="12.5" stroke="#dc2626" stroke-width="2.2" stroke-linecap="round"/>
          <line x1="12.5" y1="3.5" x2="3.5" y2="12.5" stroke="#dc2626" stroke-width="2.2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
    <div class="ss-modal-title" id="srmSaveTitle">Saving scores</div>
    <div class="ss-modal-sub"  id="srmSaveSub">Please wait…</div>
    <div class="ss-progress-track"><div class="ss-progress-fill" id="srmSaveFill"></div></div>
    <div class="ss-count-row">
      <span id="srmSaveCountLabel">Saved</span>
      <span class="ss-count-num" id="srmSaveCountNum"></span>
    </div>
  </div>
</div>

{{-- ══ SCORE INPUT TOOLTIP ══ --}}
<div id="srmTooltip">
  <div class="tip-top">
    <img id="stAvatar" class="tip-avatar" src="" alt=""
         onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'">
    <div style="min-width:0">
      <div class="tip-name" id="stName">—</div>
      <div class="tip-adm"  id="stMeta">—</div>
    </div>
  </div>
  <div class="tip-grid">
    <div class="tip-stat"><div class="tip-stat-label">Entering</div><div class="tip-stat-val" id="stVal" style="color:#2563eb">—</div></div>
    <div class="tip-stat"><div class="tip-stat-label">Total</div>   <div class="tip-stat-val" id="stTotal" style="color:#1e3a5f">—</div></div>
    <div class="tip-stat"><div class="tip-stat-label">Grade</div>   <div class="tip-stat-val" id="stGrade" style="color:#6b7280">—</div></div>
  </div>
  <div class="tip-divider"></div>
  <div class="tip-prog-labels"><span id="stProgLabel">Score progress</span><span id="stProgPct">0%</span></div>
  <div class="tip-prog-track"><div class="tip-prog-fill" id="stProgFill"></div></div>
</div>

<div class="main-content"><div class="page-content"><div class="container-fluid">

{{-- HERO --}}
<div class="srm-hero">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <h1 class="mb-1 fw-bold"><i class="ri-user-settings-line me-2"></i>Student Result Manager</h1>
      <p class="mb-0 opacity-90">Enter or edit results for individual students across all their registered subjects.</p>
      <p class="mt-2 mb-0 opacity-75 small"><i class="ri-information-line me-1"></i>Select a student to view all subjects — enter scores with positions, grades, BF, CUM and GPA in the full scoresheet view.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
      <a href="{{ route('admin.score-entry.index') }}" class="btn-hero"><i class="ri-arrow-left-line"></i>Back to Teacher View</a>
      <a href="{{ route('admin.score-entry.lock-management') }}" class="btn-hero"><i class="ri-shield-lock-line"></i>Lock Manager</a>
    </div>
  </div>
</div>

{{-- FILTER --}}
<div class="filter-card">
  <div class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
      <select id="classFilter" class="form-select">
        <option value="">— Select Class —</option>
        @foreach($classes as $class)
          <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->armRelation->arm ?? $class->arm ?? '' }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold">Term <span class="text-danger">*</span></label>
      <select id="termFilter" class="form-select">
        <option value="">— Select Term —</option>
        @foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->term }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
      <select id="sessionFilter" class="form-select">
        <option value="">— Select Session —</option>
        @foreach($sessions as $sess)<option value="{{ $sess->id }}">{{ $sess->session }}</option>@endforeach
      </select>
    </div>
    <div class="col-md-2">
      <button type="button" class="btn btn-primary w-100" id="loadStudentsBtn">
        <i class="ri-user-search-line me-1"></i>Load Students
      </button>
    </div>
  </div>
</div>

{{-- STATS --}}
<div id="statsDashboard" style="display:none;" class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="stat-card h-100">
    <div class="stat-icon">👥</div>
    <div class="stat-value text-primary" id="statTotal">0</div>
    <div class="stat-label">Total Students</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="stat-card h-100">
    <div class="stat-icon">✅</div>
    <div class="stat-value text-success" id="statComplete">0</div>
    <div class="stat-label">Complete Records</div>
    <div class="pass-bar mt-2"><div class="pass-bar-fill bg-success" id="completionBar" style="width:0%"></div></div>
    <small class="text-muted" id="completionPct" style="font-size:11px">0% completion</small>
  </div></div>
  <div class="col-6 col-md-3"><div class="stat-card h-100">
    <div class="stat-icon">📊</div>
    <div class="stat-value" style="color:var(--ss-warning)" id="statAvg">0.00</div>
    <div class="stat-label">Class Average</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="stat-card h-100">
    <div class="stat-icon">⭐</div>
    <div class="stat-value" style="color:#7c3aed" id="statGPA">0.00</div>
    <div class="stat-label">Avg GPA (5.0 Scale)</div>
  </div></div>
</div>

{{-- STUDENTS TABLE --}}
<div class="card border-0 shadow-sm">
  <div class="card-header d-flex align-items-center flex-wrap gap-2 py-3" style="background:var(--ss-primary)">
    <div class="flex-grow-1">
      <h5 class="mb-0 text-white fw-semibold">
        <i class="ri-group-line me-2"></i>Students List
        <span class="badge bg-white text-primary ms-2" id="scoreCount" style="display:none">0</span>
      </h5>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="input-group input-group-sm" style="width:240px">
        <span class="input-group-text bg-white border-0"><i class="ri-search-line text-muted"></i></span>
        <input type="text" class="form-control border-0 ps-1" id="studentSearchInput" placeholder="Search name or admission…" disabled>
        <button class="btn btn-light border-0" id="clearSearch"><i class="ri-close-line"></i></button>
      </div>
      <button class="btn btn-sm btn-light" id="columnToggleBtn" data-bs-toggle="modal" data-bs-target="#columnVisibilityModal" style="display:none">
        <i class="ri-eye-line me-1"></i>Columns
      </button>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-nowrap align-middle mb-0" id="studentsTable">
      <thead>
        <tr>
          <th class="col-photo" style="width:52px">Photo</th>
          <th class="col-sn"    style="width:42px">SN</th>
          <th class="col-adm">Adm. No</th>
          <th class="col-name">Student Name</th>
          <th class="col-avg text-center">Average</th>
          <th class="col-grade text-center">Grade</th>
          <th class="col-gpa text-center">GPA</th>
          <th class="col-subjects text-center">Subjects</th>
          <th class="col-status text-center">Status</th>
          <th class="col-action" style="width:140px">Action</th>
        </tr>
      </thead>
      <tbody id="studentsTableBody">
        <tr id="emptyStateRow"><td colspan="10" class="text-center py-5 text-muted">
          <i class="ri-filter-line" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px"></i>
          <h5>Select Class, Term and Session</h5>
          <p class="mb-0">Choose filters above then click "Load Students"</p>
        </td></tr>
      </tbody>
    </table>
    </div>
  </div>
</div>

</div></div></div>{{-- /main-content --}}

{{-- ═══════════════════════════════════════════════════════════════
     STUDENT RESULTS MODAL  (scoresheet-style table + card toggle)
     ═══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="studentResultsModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl-srm">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">

      {{-- Modal nav header --}}
      <div style="background:var(--ss-primary);padding:14px 20px;display:flex;justify-content:space-between;align-items:center">
        <h5 class="mb-0 text-white fw-semibold"><i class="ri-file-list-3-line me-2"></i>Score Entry</h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          {{-- View toggle --}}
          <div class="d-flex gap-1 p-1 rounded-3" style="background:rgba(255,255,255,.12)">
            <button class="view-toggle-btn active" id="btnViewTable" title="Scoresheet table view">
              <i class="ri-table-line me-1"></i>Table View
            </button>
            <button class="view-toggle-btn" id="btnViewCard" title="Card view">
              <i class="ri-layout-cards-line me-1"></i>Card View
            </button>
          </div>
          <button type="button" class="btn btn-sm"
                  style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)"
                  id="modalSaveAllBtn">
            <i class="ri-save-line me-1"></i>Save All
          </button>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
      </div>

      {{-- Student info strip --}}
      <div id="modalStudentInfo"></div>

      {{-- Legend --}}
      <div style="background:#f8fafc;padding:7px 16px;border-bottom:1px solid var(--ss-border);font-size:11px;color:var(--ss-muted)">
        <i class="ri-information-line me-1 text-info"></i>
        <strong>Total</strong>=sum of assessments &nbsp;|&nbsp;
        <strong>Grade</strong>=grade on total &nbsp;|&nbsp;
        <strong>BF</strong>=previous term cum &nbsp;|&nbsp;
        <strong>Cum</strong>=cumulative avg &nbsp;|&nbsp;
        <strong>Pos</strong>=subject position in class &nbsp;|&nbsp;
        <small>Ctrl+S saves all</small>
      </div>

      {{-- Scrollable body --}}
      <div class="modal-body-scroll" id="modalBody">
        <div class="text-center py-5 text-muted">
          <div class="spinner-border text-primary" style="width:2rem;height:2rem"></div>
          <p class="mt-3">Loading subjects…</p>
        </div>
      </div>

      {{-- Footer --}}
      <div class="modal-footer" style="background:#f8fafc;gap:8px">
        <div class="text-muted small me-auto"><i class="ri-keyboard-line me-1"></i>Ctrl+S saves all</div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success px-4" id="saveAllSubjectsBtn">
          <i class="ri-save-line me-1"></i>Save All Changes
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Column visibility --}}
<div class="modal fade" id="columnVisibilityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header" style="background:var(--ss-primary)">
        <h5 class="modal-title text-white"><i class="ri-eye-line me-2"></i>Column Visibility</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="col-group"><h6>Student Info</h6>
          @foreach([['col-photo','Photo'],['col-sn','SN'],['col-adm','Adm. No'],['col-name','Name']] as [$c,$l])
          <div class="form-check">
            <input class="form-check-input col-toggle" type="checkbox" id="chk-{{$c}}" data-col="{{$c}}" checked>
            <label class="form-check-label" for="chk-{{$c}}">{{$l}}</label>
          </div>
          @endforeach
        </div>
        <div class="col-group"><h6>Metrics</h6>
          @foreach([['col-avg','Average'],['col-grade','Grade'],['col-gpa','GPA'],['col-subjects','Subjects'],['col-status','Status']] as [$c,$l])
          <div class="form-check">
            <input class="form-check-input col-toggle" type="checkbox" id="chk-{{$c}}" data-col="{{$c}}" checked>
            <label class="form-check-label" for="chk-{{$c}}">{{$l}}</label>
          </div>
          @endforeach
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

{{-- Photo enlarger modal (same as MyScoreSheetController) --}}
<div class="modal fade" id="imageViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header">
        <h5 class="modal-title">Student Photo</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-4">
        <img id="enlargedImage" src="" alt="Student" class="img-fluid rounded-3" style="max-height:400px;">
      </div>
    </div>
  </div>
</div>

<script>
/* ════════════════════════════════════════════════════════════════
   CSRF
   ════════════════════════════════════════════════════════════════ */
(function(){
  if(!document.querySelector('meta[name="csrf-token"]')){
    const m=document.createElement('meta');m.name='csrf-token';
    m.content='{{ csrf_token() }}';document.head.appendChild(m);
  }
})();
const CSRF=()=>document.querySelector('meta[name="csrf-token"]').content;

/* ════════════════════════════════════════════════════════════════
   ROUTES
   ════════════════════════════════════════════════════════════════ */
const ROUTES={
  getStudents  :'{{ route("admin.score-entry.get-student-results") }}',
  updateSubject:'{{ route("admin.score-entry.update-student-subject-score") }}',
  bulkUpdate   :'{{ route("admin.score-entry.bulk-update-student-scores") }}',
};

/* ════════════════════════════════════════════════════════════════
   STATE
   ════════════════════════════════════════════════════════════════ */
let allStudentsData=[], currentAssessments=[], currentStudentData=null;
let currentFilters={class_id:null,term_id:null,session_id:null};
let isSeniorClass=false;
let currentView='table'; // 'table' | 'card'

/* ════════════════════════════════════════════════════════════════
   GRADE COLOURS
   ════════════════════════════════════════════════════════════════ */
const GRADE_COLORS={
  A:'#16a34a',A1:'#16a34a',
  B:'#2563eb',B2:'#2563eb',B3:'#3b82f6',
  C:'#7c3aed',C4:'#7c3aed',C5:'#8b5cf6',C6:'#a78bfa',
  D:'#d97706',D7:'#d97706',E8:'#f59e0b',
  F:'#dc2626',F9:'#dc2626',
};

/* ════════════════════════════════════════════════════════════════
   UTILITIES
   ════════════════════════════════════════════════════════════════ */
const fmtN=(n,d=1)=>parseFloat(n||0).toFixed(d);
const $=(id)=>document.getElementById(id);

const ordinal=n=>{
  if(!n||isNaN(n))return '-';
  n=+n;const s=n%100;
  return n+([,'st','nd','rd'][n%10]&&![11,12,13].includes(s)?['','st','nd','rd'][n%10]:'th');
};

function esc(t){
  if(t===null||t===undefined)return '';
  const d=document.createElement('div');d.textContent=String(t);return d.innerHTML;
}
function getInitials(n){
  if(!n)return '?';
  const p=n.trim().split(' ');
  return((p[0]?.charAt(0)||'')+(p[1]?.charAt(0)||'')).toUpperCase();
}

function clientGrade(score){
  score=parseFloat(score)||0;
  if(isSeniorClass){
    if(score>=75)return'A1';if(score>=70)return'B2';if(score>=65)return'B3';
    if(score>=60)return'C4';if(score>=55)return'C5';if(score>=50)return'C6';
    if(score>=45)return'D7';if(score>=40)return'E8';return'F9';
  }
  if(score>=70)return'A';if(score>=60)return'B';if(score>=50)return'C';if(score>=40)return'D';return'F';
}
function clientRemark(g){
  return{A:'Excellent',A1:'Excellent',B:'Very Good',B2:'Very Good',B3:'Very Good',
         C:'Good',C4:'Good',C5:'Good',C6:'Good',D:'Pass',D7:'Pass',E8:'Pass'}[g]||'Fail';
}

function applyGrade(badge,grade){
  if(!badge)return;
  badge.textContent=grade||'-';
  badge.style.color=GRADE_COLORS[grade]||'#6b7280';
  badge.classList.remove('updating');
  badge.classList.add('updated');
  setTimeout(()=>badge.classList.remove('updated'),500);
}

function showToast(msg,type='info'){
  const colors={success:'#16a34a',warning:'#d97706',danger:'#dc2626',info:'#2563eb'};
  const id='toast_'+Date.now();
  document.body.insertAdjacentHTML('beforeend',
    `<div id="${id}" class="toast align-items-center border-0 text-white show" role="alert"
      style="position:fixed;bottom:20px;right:20px;z-index:99999;
             background:${colors[type]||colors.info};min-width:280px;border-radius:10px;">
      <div class="d-flex p-3"><div class="me-auto">${msg}</div>
      <button class="btn-close btn-close-white ms-2" onclick="this.closest('.toast').remove()"></button>
      </div></div>`);
  setTimeout(()=>document.getElementById(id)?.remove(),4500);
}

function validateInput(inp){
  const max=parseFloat(inp.dataset.max)||0;
  const val=parseFloat(inp.value)||0;
  inp.classList.toggle('is-invalid',val>max||val<0);
  return val<=max&&val>=0;
}

/* ════════════════════════════════════════════════════════════════
   IMAGE ENLARGER HANDLER (same pattern as MyScoreSheetController)
   ════════════════════════════════════════════════════════════════ */
function openEnlarger(src){
  if(!src)return;
  document.getElementById('enlargedImage').src=src;
  const enlargeModal=new bootstrap.Modal(document.getElementById('imageViewModal'));
  enlargeModal.show();
}

/* ════════════════════════════════════════════════════════════════
   APPLE-STYLE SAVE MODAL
   ════════════════════════════════════════════════════════════════ */
const SS_ARC=157.08;
function srmResetIcons(){
  $('srmIconSave').style.display='';$('srmIconCheck').style.display='none';$('srmIconX').style.display='none';
  $('srmCheckPath').style.strokeDashoffset='22';$('srmCheckPath').classList.remove('drawn');
  $('srmIconCenter').style.background='rgba(30,58,95,0.09)';$('srmArcFg').style.stroke='#1e3a5f';
}
function srmSetArc(p){$('srmArcFg').style.strokeDashoffset=(SS_ARC*(1-p/100)).toFixed(3)}
function srmOpen(total){
  srmResetIcons();srmSetArc(0);
  $('srmSaveFill').style.width='0%';$('srmSaveFill').style.background='#1e3a5f';
  $('srmSaveTitle').textContent='Saving scores';$('srmSaveSub').textContent='Preparing…';
  $('srmSaveCountLabel').textContent='Saved';$('srmSaveCountNum').textContent=`0 / ${total}`;
  const o=$('srmSaveOverlay');o.classList.remove('ss-closing');o.classList.add('ss-visible');
}
function srmUpdate(saved,total,pct){
  $('srmSaveFill').style.width=pct.toFixed(1)+'%';
  $('srmSaveCountNum').textContent=`${saved} / ${total}`;srmSetArc(pct);
  $('srmSaveSub').textContent=pct<30?'Uploading…':pct<60?'Processing…':pct<88?'Recalculating…':'Finalising…';
}
function srmSuccess(total){
  $('srmSaveFill').style.width='100%';$('srmSaveFill').style.background='#16a34a';
  $('srmArcFg').style.strokeDashoffset='0';$('srmArcFg').style.stroke='#16a34a';
  $('srmIconCenter').style.background='#dcfce7';
  $('srmIconSave').style.display='none';$('srmIconCheck').style.display='';
  requestAnimationFrame(()=>$('srmCheckPath').classList.add('drawn'));
  $('srmSaveTitle').textContent='All saved';
  $('srmSaveSub').textContent=`${total} subject${total!==1?'s':''} saved`;
  $('srmSaveCountNum').textContent=`${total} / ${total}`;
  setTimeout(srmClose,1900);
}
function srmError(msg){
  $('srmSaveFill').style.background='#dc2626';$('srmArcFg').style.stroke='#dc2626';
  $('srmIconCenter').style.background='#fee2e2';
  $('srmIconSave').style.display='none';$('srmIconX').style.display='';
  $('srmSaveTitle').textContent='Save failed';$('srmSaveSub').textContent=msg||'Something went wrong.';
  setTimeout(srmClose,2400);
}
function srmClose(){
  const o=$('srmSaveOverlay');o.classList.add('ss-closing');
  setTimeout(()=>o.classList.remove('ss-visible','ss-closing'),260);
}

/* ════════════════════════════════════════════════════════════════
   LOAD STUDENTS
   ════════════════════════════════════════════════════════════════ */
async function loadStudents(){
  const classId=$('classFilter').value,termId=$('termFilter').value,sessionId=$('sessionFilter').value;
  if(!classId||!termId||!sessionId){
    Swal.fire('Warning','Please select Class, Term and Session first.','warning');return;
  }
  currentFilters={class_id:classId,term_id:termId,session_id:sessionId};
  const btn=$('loadStudentsBtn'),orig=btn.innerHTML;
  btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Loading…';
  try{
    const r=await fetch(ROUTES.getStudents,{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF(),'Accept':'application/json'},
      body:JSON.stringify(currentFilters)
    });
    const result=await r.json();
    if(result.success){
      allStudentsData=result.data||[];
      currentAssessments=result.assessments||[];
      isSeniorClass=false;
      renderStudentsTable(allStudentsData);
      updateStats(allStudentsData);
      $('statsDashboard').style.display='flex';
      $('studentSearchInput').disabled=false;
      $('scoreCount').style.display='';$('scoreCount').textContent=allStudentsData.length;
      $('columnToggleBtn').style.display='';
      Swal.fire({icon:'success',title:'Loaded',text:`${allStudentsData.length} student(s) loaded.`,timer:1500,showConfirmButton:false});
    }else{
      Swal.fire('Error',result.message||'Failed to load students','error');
      renderEmptyTable(result.message||'No students found.');
      $('statsDashboard').style.display='none';
    }
  }catch(e){
    Swal.fire('Error','Network error. Please try again.','error');
    renderEmptyTable('Network error.');
  }finally{btn.disabled=false;btn.innerHTML=orig;}
}

/* ════════════════════════════════════════════════════════════════
   STATS
   ════════════════════════════════════════════════════════════════ */
function updateStats(students){
  let complete=0,totalAvg=0,totalGpa=0;
  students.forEach(s=>{
    const done=(s.subjects||[]).filter(sub=>sub.total>0).length;
    if(done===s.total_subjects&&s.total_subjects>0)complete++;
    totalAvg+=s.average||0;totalGpa+=s.gpa||0;
  });
  const n=students.length||1,rate=students.length?Math.round((complete/students.length)*100):0;
  $('statTotal').textContent=students.length;$('statComplete').textContent=complete;
  $('statAvg').textContent=fmtN(totalAvg/n,2);$('statGPA').textContent=fmtN(totalGpa/n,2);
  $('completionBar').style.width=rate+'%';$('completionPct').textContent=rate+'% completion';
}

/* ════════════════════════════════════════════════════════════════
   RENDER STUDENTS TABLE
   ════════════════════════════════════════════════════════════════ */
const DEFAULT_AVT='{{ asset("storage/student_avatars/unnamed.jpg") }}';

function getStatusBadge(s){
  const total=s.total_subjects||0,done=(s.subjects||[]).filter(sub=>sub.total>0).length;
  if(done===0)    return'<span class="status-badge status-pending">Pending</span>';
  if(done===total)return'<span class="status-badge status-complete">Complete</span>';
  return`<span class="status-badge status-incomplete">${done}/${total}</span>`;
}
function getRowClass(s){
  const total=s.total_subjects||0,done=(s.subjects||[]).filter(sub=>sub.total>0).length;
  if(done===0)return'row-pending';if(done===total)return'row-complete';return'row-incomplete';
}

function renderStudentsTable(students){
  const tbody=$('studentsTableBody');
  if(!students||students.length===0){renderEmptyTable('No students found.');return;}
  let i=0;
  tbody.innerHTML=students.map(s=>{
    i++;
    const avatarUrl=s.photo||DEFAULT_AVT;
    const avgVal=parseFloat(s.average||0);
    const avgColor=avgVal>=70?'success':avgVal>=50?'info':avgVal>=40?'warning':'danger';
    const gradeColor=GRADE_COLORS[s.average_grade]||'#6b7280';
    return`<tr class="${getRowClass(s)}" data-sid="${s.student_id}"
               data-name="${esc((s.lastname||'')+' '+(s.firstname||''))}"
               data-adm="${esc(s.admission_no||'')}">
      <td class="col-photo">
        <img src="${esc(avatarUrl)}"
             class="rounded-circle student-avatar-img"
             style="width:40px;height:40px;object-fit:cover;border:2px solid var(--ss-border);cursor:pointer"
             data-image="${esc(avatarUrl)}"
             onclick="openEnlarger('${esc(avatarUrl)}')"
             onerror="this.onerror=null;this.src='${DEFAULT_AVT}'"
             alt="Photo">
      </td>
      <td class="col-sn fw-medium">${i}</td>
      <td class="col-adm"><span class="text-muted small">${esc(s.admission_no||'—')}</span></td>
      <td class="col-name">
        <span class="fw-semibold d-block" style="font-size:12.5px">${esc(s.lastname||'')}, ${esc(s.firstname||'')}</span>
        ${s.othername?`<span class="text-muted small">${esc(s.othername)}</span>`:''}
      </td>
      <td class="col-avg text-center">
        <span class="badge bg-${avgColor}-subtle text-${avgColor} fw-bold" style="font-size:12px">${fmtN(s.average,2)}</span>
      </td>
      <td class="col-grade text-center">
        <span class="fw-bold" style="color:${gradeColor};font-size:13px">${s.average_grade||'F'}</span>
      </td>
      <td class="col-gpa text-center">
        <span class="badge bg-warning-subtle text-warning fw-semibold">${fmtN(s.gpa,2)}</span>
      </td>
      <td class="col-subjects text-center">
        <span class="badge bg-primary-subtle text-primary">${s.total_subjects||0}</span>
      </td>
      <td class="col-status text-center">${getStatusBadge(s)}</td>
      <td class="col-action">
        <button class="btn btn-sm btn-primary px-3" onclick="openStudentModal(${s.student_id})">
          <i class="ri-edit-line me-1"></i>Enter Scores
        </button>
      </td>
     </tr>`;
  }).join('');
  initRowEntrance('#studentsTableBody tr[data-sid]');
}

function renderEmptyTable(msg){
  $('studentsTableBody').innerHTML=`<tr><td colspan="10" class="text-center py-5 text-muted">
    <i class="ri-inbox-line" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px"></i>
    <h5>No students found</h5><p class="mb-0 small">${esc(msg||'')}</p>
   </td></tr>`;
}

/* ════════════════════════════════════════════════════════════════
   ROW ENTRANCE ANIMATION
   ════════════════════════════════════════════════════════════════ */
function initRowEntrance(selector){
  const rows=Array.from(document.querySelectorAll(selector));
  if(!rows.length)return;
  if(window.matchMedia('(prefers-reduced-motion:reduce)').matches){
    rows.forEach(r=>r.classList.add('row-visible'));return;
  }
  const obs=new IntersectionObserver(entries=>{
    entries.forEach(entry=>{
      if(!entry.isIntersecting)return;
      const row=entry.target,index=rows.indexOf(row);
      setTimeout(()=>row.classList.add('row-visible'),Math.min(index*36,15*36)+40);
      obs.unobserve(row);
    });
  },{threshold:0.05,rootMargin:'0px 0px -20px 0px'});
  rows.forEach(r=>obs.observe(r));
}

/* ════════════════════════════════════════════════════════════════
   SEARCH
   ════════════════════════════════════════════════════════════════ */
function applySearch(){
  const q=($('studentSearchInput')?.value??'').trim().toLowerCase();
  let vis=0;
  document.querySelectorAll('#studentsTableBody tr[data-sid]').forEach(row=>{
    const show=!q||(row.dataset.name||'').toLowerCase().includes(q)||(row.dataset.adm||'').toLowerCase().includes(q);
    row.style.display=show?'':'none';if(show)vis++;
  });
  const sc=$('scoreCount');if(sc)sc.textContent=vis;
}

/* ════════════════════════════════════════════════════════════════
   OPEN STUDENT MODAL
   ════════════════════════════════════════════════════════════════ */
window.openStudentModal=function(studentId){
  const student=allStudentsData.find(s=>s.student_id===studentId);
  if(!student){Swal.fire('Error','Student data not found','error');return;}
  currentStudentData=student;
  renderStudentModal();
  new bootstrap.Modal($('studentResultsModal')).show();
};

/* ════════════════════════════════════════════════════════════════
   RENDER MODAL — info header + view dispatcher
   ════════════════════════════════════════════════════════════════ */
function renderStudentModal(){
  if(!currentStudentData)return;
  const s=currentStudentData;
  const avatarUrl=s.photo||DEFAULT_AVT;
  const avgColor=parseFloat(s.average||0)>=70?'#16a34a':parseFloat(s.average||0)>=50?'#2563eb':parseFloat(s.average||0)>=40?'#d97706':'#dc2626';

  $('modalStudentInfo').innerHTML=`
    <div class="student-info-header">
      <div class="student-avatar-large" onclick="openEnlarger('${esc(avatarUrl)}')">
        <img id="modalAvtImg" src="${esc(avatarUrl)}"
             data-image="${esc(avatarUrl)}"
             onerror="this.onerror=null;this.src='${DEFAULT_AVT}'"
             alt="Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
      </div>
      <div class="flex-grow-1">
        <h4 class="mb-1 fw-bold">${esc(s.full_name)}</h4>
        <p class="mb-0 opacity-80" style="font-size:13px">
          <i class="ri-id-card-line me-1"></i>${esc(s.admission_no||'—')}
          &nbsp;·&nbsp;${s.total_subjects||0} subjects
        </p>
        <div class="summary-stats">
          <div class="stat-box"><div class="label">Average</div><div class="value" style="color:${avgColor}">${fmtN(s.average,2)}</div></div>
          <div class="stat-box"><div class="label">GPA</div><div class="value">${fmtN(s.gpa,2)}</div></div>
          <div class="stat-box"><div class="label">Subjects</div><div class="value">${s.total_subjects||0}</div></div>
          <div class="stat-box"><div class="label">Grade</div><div class="value" style="color:${GRADE_COLORS[s.average_grade]||'#fff'}">${s.average_grade||'F'}</div></div>
        </div>
      </div>
    </div>`;

  renderModalContent();
}

function renderModalContent(){
  if(currentView==='table') renderTableView();
  else renderCardView();
}

/* ════════════════════════════════════════════════════════════════
   TABLE VIEW  (scoresheet-style)
   ════════════════════════════════════════════════════════════════ */
function renderTableView(){
  const s=currentStudentData;
  const container=$('modalBody');

  if(!s.subjects||s.subjects.length===0){
    container.innerHTML=`<div class="no-subjects-msg m-3">
      <i class="ri-information-line me-2" style="font-size:20px"></i>
      <strong>No subjects registered</strong><br>
      <small>Check subject registrations exist with Status='active' for this class/term/session.</small>
    </div>`;return;
  }

  const assessmentHeaders=currentAssessments.map(a=>
    `<th class="text-center" style="white-space:nowrap">
      ${esc(a.name)}<br><small class="fw-normal opacity-75">(${a.max_score})</small>
    </th>`
  ).join('');

  let html=`
  <div class="px-3 pt-3 pb-2 d-flex align-items-center gap-2 flex-wrap" style="background:#f8fafc;border-bottom:1px solid var(--ss-border)">
    <div class="input-group input-group-sm" style="width:220px">
      <span class="input-group-text border-0 bg-white"><i class="ri-search-line text-muted"></i></span>
      <input type="text" class="form-control border-0 ps-1" id="modalSubjectSearch" placeholder="Search subject…">
    </div>
    <small class="text-muted ms-auto"><kbd>Tab</kbd> moves between inputs · <kbd>Enter</kbd> saves subject · <kbd>Ctrl+S</kbd> saves all</small>
  </div>
  <div class="table-responsive">
  <table class="table table-nowrap align-middle mb-0" id="modalScoreTable">
    <thead>
      <tr>
        <th style="width:42px">SN</th>
        <th>Subject</th>
        ${assessmentHeaders}
        <th class="text-center">Total</th>
        <th class="text-center">Total<br><small class="fw-normal opacity-75">Grade</small></th>
        <th class="text-center">BF</th>
        <th class="text-center">Cum</th>
        <th class="text-center">Cum<br><small class="fw-normal opacity-75">Grade</small></th>
        <th class="text-center" title="Position in class by cumulative">Pos<br><small class="fw-normal opacity-75">(Cum)</small></th>
        <th class="text-center" title="Position in class by total">Pos<br><small class="fw-normal opacity-75">(Total)</small></th>
        <th class="text-center">Remark</th>
        <th class="text-center" style="width:90px">Save</th>
      </tr>
    </thead>
    <tbody id="modalScoreTableBody">`;

  s.subjects.forEach((subj,idx)=>{
    const totalRaw=parseFloat(subj.total||0);
    const bf=parseFloat(subj.bf||0);
    const cum=parseFloat(subj.cum||0);
    const grade=subj.grade||clientGrade(totalRaw);
    const remark=subj.remark||clientRemark(grade);
    const gradeColor=GRADE_COLORS[grade]||'#6b7280';
    const cumGrade=clientGrade(cum);
    const totalColor=totalRaw>=70?'success':totalRaw>=50?'info':totalRaw>=40?'warning':'danger';
    const cumColor=cum>=70?'success':cum>=50?'info':cum>=40?'warning':'danger';
    const rowCls=totalRaw>0?'mrow-saved':'mrow-unsaved';

    const scoreMap={};(subj.assessment_scores||[]).forEach(a=>{scoreMap[a.assessment_id]=a.score;});

    const assessmentInputs=currentAssessments.map(a=>`
      <td class="text-center">
        <input type="number" class="score-input"
          data-idx="${idx}" data-assessment-id="${a.id}" data-max="${a.max_score}"
          data-assessment-name="${esc(a.name)}"
          data-student-name="${esc(s.full_name)}"
          data-student-adm="${esc(s.admission_no||'')}"
          data-student-avatar="${esc(s.photo||DEFAULT_AVT)}"
          value="${parseFloat(scoreMap[a.id]||0)}"
          min="0" max="${a.max_score}" step="0.5">
       </td>`).join('');

    html+=`
    <tr class="${rowCls}" data-midx="${idx}" data-subject-name="${esc(subj.subject_name||'').toLowerCase()}">
      <td class="fw-medium text-muted">${idx+1}</td>
      <td>
        <span class="fw-semibold d-block" style="font-size:12.5px">${esc(subj.subject_name)}</span>
        ${subj.subject_code?`<span class="badge bg-light text-dark" style="font-size:10px">${esc(subj.subject_code)}</span>`:''}
      </td>
      ${assessmentInputs}
      <td class="text-center">
        <span class="badge fw-bold mtotal-badge bg-${totalColor}-subtle text-${totalColor}" style="font-size:12px" id="mtotal_${idx}">${fmtN(totalRaw)}</span>
      </td>
      <td class="text-center">
        <span class="grade-badge" id="mgrade_${idx}" style="color:${gradeColor}">${grade}</span>
      </td>
      <td class="text-center">
        <span class="badge bg-secondary-subtle text-secondary mbf-badge" id="mbf_${idx}">${fmtN(bf)}</span>
      </td>
      <td class="text-center">
        <span class="badge fw-bold mcum-badge bg-${cumColor}-subtle text-${cumColor}" style="font-size:12px" id="mcum_${idx}">${fmtN(cum)}</span>
      </td>
      <td class="text-center">
        <span class="cum-grade-badge grade-badge" id="mcumgrade_${idx}" style="color:${GRADE_COLORS[cumGrade]||'#6b7280'}">${cumGrade}</span>
      </td>
      <td class="text-center">
        <span class="badge position-badge" style="background:var(--ss-primary)" id="mpos_${idx}">${ordinal(subj.subject_position_class)}</span>
      </td>
      <td class="text-center">
        <span class="badge" style="background:#0f766e" id="mpostotal_${idx}">${ordinal(subj.subject_position_class_total)}</span>
      </td>
      <td class="text-center">
        <span style="font-size:11px;font-weight:600;color:${gradeColor}" id="mremark_${idx}">${esc(remark)}</span>
      </td>
      <td class="text-center">
        <button class="btn-save-subject" data-idx="${idx}" style="padding:5px 10px;font-size:11px">
          <i class="ri-save-line"></i>
        </button>
      </td>
    </tr>`;
  });

  html+=`</tbody></table></div>`;
  container.innerHTML=html;

  wireTableInputs();
  initRowEntrance('#modalScoreTableBody tr[data-midx]');

  $('modalSubjectSearch')?.addEventListener('input',function(){
    const q=this.value.toLowerCase();
    document.querySelectorAll('#modalScoreTableBody tr[data-midx]').forEach(row=>{
      row.style.display=!q||(row.dataset.subjectName||'').includes(q)?'':'none';
    });
  });
}

/* ════════════════════════════════════════════════════════════════
   WIRE TABLE INPUTS
   ════════════════════════════════════════════════════════════════ */
function wireTableInputs(){
  document.querySelectorAll('#modalScoreTableBody .score-input').forEach(inp=>{
    inp.addEventListener('focus',function(){this.select();tipShow(this);});
    inp.addEventListener('input',function(){
      validateInput(this);
      updateTableRowMetrics(parseInt(this.dataset.idx));
      if(tipInput===this)tipRefresh(this);
    });
    inp.addEventListener('blur',function(){
      setTimeout(()=>{if(tipInput===this)tipHide();},80);
      validateInput(this);
    });
    inp.addEventListener('keydown',function(e){
      if(e.key==='Escape'){e.preventDefault();tipHide();this.blur();return;}
      if(e.key!=='Enter')return;
      e.preventDefault();
      if(validateInput(this))saveSubjectByIndex(parseInt(this.dataset.idx));
      const all=Array.from(document.querySelectorAll('#modalScoreTableBody .score-input'));
      const i=all.indexOf(this);if(i<all.length-1)all[i+1].focus();
    });
  });

  document.querySelectorAll('#modalScoreTableBody .btn-save-subject').forEach(btn=>{
    btn.addEventListener('click',function(){saveSubjectByIndex(parseInt(this.dataset.idx));});
  });
}

function updateTableRowMetrics(idx){
  const inputs=document.querySelectorAll(`.score-input[data-idx="${idx}"]`);
  let total=0;
  inputs.forEach(inp=>{
    let v=parseFloat(inp.value)||0;
    const max=parseFloat(inp.dataset.max)||100;
    if(v>max){v=max;inp.value=max;}if(v<0){v=0;inp.value=0;}total+=v;
  });
  total=Math.round(total*100)/100;
  const subj=currentStudentData?.subjects?.[idx];
  const bf=parseFloat(subj?.bf||0);
  const termId=parseInt(currentFilters.term_id||1);
  const cum=(termId===1||bf===0)?total:Math.round(((total+bf)/2)*100)/100;
  const grade=clientGrade(total);
  const cumGrade=clientGrade(cum);
  const remark=clientRemark(grade);
  const totalColor=total>=70?'success':total>=50?'info':total>=40?'warning':'danger';
  const cumColor=cum>=70?'success':cum>=50?'info':cum>=40?'warning':'danger';
  const gradeColor=GRADE_COLORS[grade]||'#6b7280';

  const tb=document.getElementById(`mtotal_${idx}`);
  const gb=document.getElementById(`mgrade_${idx}`);
  const cb=document.getElementById(`mcum_${idx}`);
  const cgb=document.getElementById(`mcumgrade_${idx}`);
  const rb=document.getElementById(`mremark_${idx}`);

  if(tb){tb.textContent=fmtN(total);tb.className=`badge fw-bold mtotal-badge bg-${totalColor}-subtle text-${totalColor}`;tb.style.fontSize='12px';}
  if(gb)applyGrade(gb,grade);
  if(cb){cb.textContent=fmtN(cum);cb.className=`badge fw-bold mcum-badge bg-${cumColor}-subtle text-${cumColor}`;cb.style.fontSize='12px';}
  if(cgb)applyGrade(cgb,cumGrade);
  if(rb){rb.textContent=remark;rb.style.color=gradeColor;}
}

/* ════════════════════════════════════════════════════════════════
   CARD VIEW
   ════════════════════════════════════════════════════════════════ */
function renderCardView(){
  const s=currentStudentData;
  const container=$('modalBody');

  if(!s.subjects||s.subjects.length===0){
    container.innerHTML=`<div class="no-subjects-msg m-3">
      <strong>No subjects registered</strong><br>
      <small>Check subject registrations.</small>
    </div>`;return;
  }

  container.innerHTML=s.subjects.map((subj,idx)=>renderSubjectCard(subj,idx)).join('');

  container.querySelectorAll('.btn-save-subject[data-idx]').forEach(btn=>{
    btn.addEventListener('click',function(){saveSubjectByIndex(parseInt(this.dataset.idx));});
  });

  container.querySelectorAll('.score-input[data-idx]').forEach(inp=>{
    inp.addEventListener('focus',function(){this.select();tipShow(this);});
    inp.addEventListener('input',function(){
      validateInput(this);
      updateCardRowMetrics(parseInt(this.dataset.idx));
      if(tipInput===this)tipRefresh(this);
    });
    inp.addEventListener('blur',function(){
      setTimeout(()=>{if(tipInput===this)tipHide();},80);
      validateInput(this);
    });
    inp.addEventListener('keydown',function(e){
      if(e.key==='Escape'){e.preventDefault();tipHide();this.blur();return;}
      if(e.key!=='Enter')return;
      e.preventDefault();
      if(validateInput(this))saveSubjectByIndex(parseInt(this.dataset.idx));
      const all=Array.from(document.querySelectorAll('.score-input[data-idx]'));
      const i=all.indexOf(this);if(i<all.length-1)all[i+1].focus();
    });
  });
}

function renderSubjectCard(subj,idx){
  const totalRaw=parseFloat(subj.total||0);
  const bf=parseFloat(subj.bf||0);
  const cum=parseFloat(subj.cum||0);
  const grade=subj.grade||clientGrade(totalRaw);
  const remark=subj.remark||clientRemark(grade);
  const gradeColor=GRADE_COLORS[grade]||'#6b7280';
  const cumGrade=clientGrade(cum);
  const totalColor=totalRaw>=70?'success':totalRaw>=50?'info':totalRaw>=40?'warning':'danger';
  const cumColor=cum>=70?'success':cum>=50?'info':cum>=40?'warning':'danger';

  const scoreMap={};(subj.assessment_scores||[]).forEach(a=>{scoreMap[a.assessment_id]=a.score;});
  const asmtRows=currentAssessments.map(a=>`
    <div class="assessment-row">
      <div class="assessment-label">${esc(a.name)}</div>
      <input type="number" class="score-input" data-idx="${idx}"
        data-assessment-id="${a.id}" data-max="${a.max_score}"
        data-assessment-name="${esc(a.name)}"
        data-student-name="${esc(currentStudentData?.full_name||'')}"
        data-student-adm="${esc(currentStudentData?.admission_no||'')}"
        data-student-avatar="${esc(currentStudentData?.photo||DEFAULT_AVT)}"
        value="${parseFloat(scoreMap[a.id]||0)}" min="0" max="${a.max_score}" step="0.5">
      <div class="assessment-max">/ ${a.max_score}</div>
    </div>`).join('');

  return`<div class="subject-score-card" data-idx="${idx}">
    <div class="subject-header">
      <div>
        <strong><i class="ri-book-open-line me-1"></i>${esc(subj.subject_name)}</strong>
        ${subj.subject_code?`<span class="badge bg-light text-dark ms-2" style="font-size:11px">${esc(subj.subject_code)}</span>`:''}
      </div>
      <button class="btn-save-subject" data-idx="${idx}"><i class="ri-save-line"></i> Save</button>
    </div>
    ${asmtRows||'<div class="text-muted text-center py-2">No assessments configured</div>'}
    <div class="subject-metrics">
      <div class="metric-group" style="display:flex;gap:12px;flex-wrap:wrap">
        <div class="metric-item">
          <div class="m-label">Total</div>
          <div class="m-value bg-${totalColor}-subtle rounded px-2" id="ctotal_${idx}" style="color:var(--ss-primary)">${fmtN(totalRaw)}</div>
        </div>
        <div class="metric-item">
          <div class="m-label">Grade</div>
          <div class="m-value grade-badge" id="cgrade_${idx}" style="color:${gradeColor}">${grade}</div>
        </div>
        <div class="metric-item">
          <div class="m-label">BF</div>
          <div class="m-value text-secondary" id="cbf_${idx}">${fmtN(bf)}</div>
        </div>
        <div class="metric-item">
          <div class="m-label">Cum</div>
          <div class="m-value bg-${cumColor}-subtle rounded px-2" id="ccum_${idx}">${fmtN(cum)}</div>
        </div>
        <div class="metric-item">
          <div class="m-label">Cum Grade</div>
          <div class="cum-grade-badge grade-badge" id="ccumgrade_${idx}" style="color:${GRADE_COLORS[cumGrade]||'#6b7280'}">${cumGrade}</div>
        </div>
        <div class="metric-item">
          <div class="m-label">Remark</div>
          <div style="font-size:12px;font-weight:600;color:${gradeColor}" id="cremark_${idx}">${esc(remark)}</div>
        </div>
        <div class="metric-item">
          <div class="m-label">Pos (Cum)</div>
          <span class="badge position-badge" style="background:var(--ss-primary)" id="cpos_${idx}">${ordinal(subj.subject_position_class)}</span>
        </div>
      </div>
    </div>
  </div>`;
}

function updateCardRowMetrics(idx){
  const inputs=document.querySelectorAll(`.score-input[data-idx="${idx}"]`);
  let total=0;
  inputs.forEach(inp=>{
    let v=parseFloat(inp.value)||0;const max=parseFloat(inp.dataset.max)||100;
    if(v>max){v=max;inp.value=max;}if(v<0){v=0;inp.value=0;}total+=v;
  });
  total=Math.round(total*100)/100;
  const subj=currentStudentData?.subjects?.[idx];
  const bf=parseFloat(subj?.bf||0);
  const termId=parseInt(currentFilters.term_id||1);
  const cum=(termId===1||bf===0)?total:Math.round(((total+bf)/2)*100)/100;
  const grade=clientGrade(total);
  const cumGrade=clientGrade(cum);
  const remark=clientRemark(grade);
  const gradeColor=GRADE_COLORS[grade]||'#6b7280';

  const pfx=currentView==='card'?'c':'m';
  const totalEl=document.getElementById(`${pfx}total_${idx}`);
  const gradeEl=document.getElementById(`${pfx}grade_${idx}`);
  const cumEl=document.getElementById(`${pfx}cum_${idx}`);
  const cgEl=document.getElementById(`${pfx}cumgrade_${idx}`);
  const rEl=document.getElementById(`${pfx}remark_${idx}`);

  if(totalEl)totalEl.textContent=fmtN(total);
  if(gradeEl)applyGrade(gradeEl,grade);
  if(cumEl)cumEl.textContent=fmtN(cum);
  if(cgEl)applyGrade(cgEl,cumGrade);
  if(rEl){rEl.textContent=remark;rEl.style.color=gradeColor;}
}

/* ════════════════════════════════════════════════════════════════
   SAVE SUBJECT SCORES
   ════════════════════════════════════════════════════════════════ */
async function saveSubjectByIndex(idx){
  const subj=currentStudentData?.subjects?.[idx];
  if(!subj)return;

  const scores=[];
  let totalRaw=0;
  document.querySelectorAll(`.score-input[data-idx="${idx}"]`).forEach(inp=>{
    const aid=parseInt(inp.dataset.assessmentId);
    let val=parseFloat(inp.value)||0;
    const max=parseFloat(inp.dataset.max)||100;
    if(val>max)val=max;
    if(val<0)val=0;
    scores.push({assessment_id:aid,score:val});
    totalRaw+=val;
  });

  const btn=document.querySelector(`.btn-save-subject[data-idx="${idx}"]`);
  const origHtml=btn?.innerHTML;
  if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Saving…';}

  try{
    const res=await fetch(ROUTES.updateSubject,{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF()},
      body:JSON.stringify({
        student_id:currentStudentData.student_id,
        subject_id:subj.subject_id,
        subjectclass_id:subj.subjectclass_id,
        term_id:currentFilters.term_id,
        session_id:currentFilters.session_id,
        class_id:currentFilters.class_id,
        scores:scores
      })
    });
    const data=await res.json();
    if(data.success){
      subj.total=data.data.total;
      subj.bf=data.data.bf;
      subj.cum=data.data.cum;
      subj.grade=data.data.grade;
      subj.remark=data.data.remark;
      if(currentView==='table'){
        updateTableRowMetrics(idx);
      }else{
        updateCardRowMetrics(idx);
      }
      showToast('Subject scores saved!','success');
    }else{
      showToast(data.message||'Save failed','danger');
    }
  }catch(err){
    showToast('Network error','danger');
  }finally{
    if(btn){btn.disabled=false;btn.innerHTML=origHtml||'<i class="ri-save-line"></i>';}
  }
}

async function saveAllSubjects(){
  if(!currentStudentData||!currentStudentData.subjects)return;

  const updates=[];
  for(let idx=0;idx<currentStudentData.subjects.length;idx++){
    const subj=currentStudentData.subjects[idx];
    const scores=[];
    document.querySelectorAll(`.score-input[data-idx="${idx}"]`).forEach(inp=>{
      const aid=parseInt(inp.dataset.assessmentId);
      let val=parseFloat(inp.value)||0;
      const max=parseFloat(inp.dataset.max)||100;
      if(val>max)val=max;
      if(val<0)val=0;
      scores.push({assessment_id:aid,score:val});
    });
    updates.push({
      subject_id:subj.subject_id,
      subjectclass_id:subj.subjectclass_id,
      scores:scores
    });
  }

  srmOpen(updates.length);
  let saved=0;
  const total=updates.length;
  let pct=0;

  for(let i=0;i<updates.length;i++){
    try{
      const res=await fetch(ROUTES.updateSubject,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF()},
        body:JSON.stringify({
          student_id:currentStudentData.student_id,
          subject_id:updates[i].subject_id,
          subjectclass_id:updates[i].subjectclass_id,
          term_id:currentFilters.term_id,
          session_id:currentFilters.session_id,
          class_id:currentFilters.class_id,
          scores:updates[i].scores
        })
      });
      const data=await res.json();
      if(data.success){
        const subj=currentStudentData.subjects[i];
        subj.total=data.data.total;
        subj.bf=data.data.bf;
        subj.cum=data.data.cum;
        subj.grade=data.data.grade;
        subj.remark=data.data.remark;
        saved++;
        pct=(saved/total)*100;
        srmUpdate(saved,total,pct);
        if(currentView==='table'){
          updateTableRowMetrics(i);
        }else{
          updateCardRowMetrics(i);
        }
      }
    }catch(err){
      srmError('Save failed for subject '+(i+1));
      return;
    }
  }
  srmSuccess(saved);
  showToast(`${saved} subject(s) saved!`,'success');
}

/* ════════════════════════════════════════════════════════════════
   SCORE INPUT TOOLTIP
   ════════════════════════════════════════════════════════════════ */
const tip=document.getElementById('srmTooltip');
let tipInput=null,tipHideTimer=null;

function tipPosition(inp){
  const r=inp.getBoundingClientRect(),tw=230,margin=8;
  let left=r.left+r.width/2-tw/2;
  left=Math.max(margin,Math.min(left,window.innerWidth-tw-margin));
  tip.style.left=left+'px';
  tip.classList.remove('tip-above','tip-below');
  if(r.top>155){
    tip.style.top=(r.top-8)+'px';tip.classList.add('tip-above');
  }else{
    tip.style.top=(r.bottom+8)+'px';tip.classList.add('tip-below');
  }
}

function tipRefresh(inp){
  if(!inp)return;
  const idx=parseInt(inp.dataset.idx);
  const val=parseFloat(inp.value)||0;
  const max=parseFloat(inp.dataset.max)||100;
  const name=inp.dataset.assessmentName||'Score';
  let total=0,totalMax=0;
  document.querySelectorAll(`.score-input[data-idx="${idx}"]`).forEach(i=>{
    total+=parseFloat(i.value)||0;totalMax+=parseFloat(i.dataset.max)||0;
  });
  const grade=clientGrade(total);
  const pct=totalMax>0?Math.min(total/totalMax*100,100):0;
  const col=GRADE_COLORS[grade]||'#6b7280';
  document.getElementById('stAvatar').src=inp.dataset.studentAvatar||DEFAULT_AVT;
  document.getElementById('stName').textContent=inp.dataset.studentName||'—';
  document.getElementById('stMeta').textContent=(inp.dataset.studentAdm||'—')+' · '+name+' (max '+max+')';
  document.getElementById('stVal').textContent=val%1===0?String(val):val.toFixed(1);
  document.getElementById('stTotal').textContent=fmtN(total);
  const gEl=document.getElementById('stGrade');gEl.textContent=grade;gEl.style.color=col;
  document.getElementById('stProgLabel').textContent=fmtN(total)+' / '+totalMax+' marks';
  document.getElementById('stProgPct').textContent=Math.round(pct)+'%';
  const fill=document.getElementById('stProgFill');
  fill.style.width=pct.toFixed(1)+'%';
  fill.style.background=pct>=70?'#16a34a':pct>=50?'#2563eb':pct>=40?'#d97706':'#dc2626';
  tipPosition(inp);
}

function tipShow(inp){
  clearTimeout(tipHideTimer);tipInput=inp;
  tip.style.display='block';
  tipRefresh(inp);requestAnimationFrame(()=>{tip.style.opacity='1';});
}
function tipHide(){
  tip.style.opacity='0';
  tipHideTimer=setTimeout(()=>{if(tip.style.opacity==='0')tip.style.display='none';},160);
  tipInput=null;
}

/* ════════════════════════════════════════════════════════════════
   DOM READY
   ════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded',function(){

  if(typeof Swal==='undefined'){
    const s=document.createElement('script');
    s.src='https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(s);
  }

  // Image enlarger handler for dynamic images via event delegation
  document.addEventListener('click',function(e){
    const img=e.target.closest('.student-avatar-img, .srm-enlargeable, [onclick*="openEnlarger"]');
    if(img&&img.tagName==='IMG'&&img.dataset.image){
      e.preventDefault();
      openEnlarger(img.dataset.image);
    }
  });

  // Load students
  document.getElementById('loadStudentsBtn').addEventListener('click',loadStudents);

  // Save all
  document.getElementById('saveAllSubjectsBtn').addEventListener('click',saveAllSubjects);
  document.getElementById('modalSaveAllBtn')?.addEventListener('click',saveAllSubjects);

  // Search
  document.getElementById('studentSearchInput').addEventListener('input',applySearch);
  document.getElementById('clearSearch').addEventListener('click',()=>{
    document.getElementById('studentSearchInput').value='';applySearch();
  });

  // Column toggles
  document.querySelectorAll('.col-toggle').forEach(cb=>{
    cb.addEventListener('change',function(){
      document.querySelectorAll(`th.${this.dataset.col},td.${this.dataset.col}`)
        .forEach(el=>el.style.display=this.checked?'':'none');
    });
  });

  // VIEW TOGGLE
  document.getElementById('btnViewTable')?.addEventListener('click',function(){
    if(currentView==='table')return;
    currentView='table';
    this.classList.add('active');
    document.getElementById('btnViewCard').classList.remove('active');
    if(currentStudentData)renderModalContent();
  });
  document.getElementById('btnViewCard')?.addEventListener('click',function(){
    if(currentView==='card')return;
    currentView='card';
    this.classList.add('active');
    document.getElementById('btnViewTable').classList.remove('active');
    if(currentStudentData)renderModalContent();
  });

  // Keyboard shortcuts
  document.addEventListener('keydown',e=>{
    if((e.ctrlKey||e.metaKey)&&e.key==='s'){
      if(document.getElementById('studentResultsModal').classList.contains('show')){
        e.preventDefault();saveAllSubjects();
      }
    }
    if(e.key==='Escape'&&tipInput){tipHide();document.activeElement?.blur();}
  });
});
</script>
@endsection
