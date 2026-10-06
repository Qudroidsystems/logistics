{{-- resources/views/promotions/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --pay-primary: #1e3a5f;
    --pay-accent:  #2563eb;
    --pay-success: #16a34a;
    --pay-warning: #d97706;
    --pay-danger:  #dc2626;
    --pay-muted:   #6b7280;
    --pay-border:  #e2e8f0;
    --pay-bg:      #f8fafc;
    --pay-radius:  12px;
    --pay-shadow:  0 2px 8px rgba(0,0,0,.08);

    --color-background-success:   #dcfce7;
    --color-background-danger:    #fee2e2;
    --color-background-warning:   #fef9c3;
    --color-background-info:      #dbeafe;
    --color-background-secondary: #f1f5f9;

    --color-text-success:   #15803d;
    --color-text-danger:    #b91c1c;
    --color-text-warning:   #92400e;
    --color-text-info:      #1e40af;
    --color-text-secondary: #475569;
    --color-text-primary:   #1e293b;

    --color-border-success:   #86efac;
    --color-border-danger:    #fecaca;
    --color-border-warning:   #fed7aa;
    --color-border-info:      #bfdbfe;
    --color-border-tertiary:  #cbd5e1;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes modalZoomIn {
    from { opacity: 0; transform: scale(0.95) translateY(-10px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes toastSlideIn {
    from { opacity: 0; transform: translateX(100px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes toastSlideOut {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(100px); }
}
@keyframes skeletonLoading {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
@keyframes btnPulse {
    0%   { box-shadow: 0 0 0 0   rgba(37,99,235,0.4); }
    70%  { box-shadow: 0 0 0 8px rgba(37,99,235,0);   }
    100% { box-shadow: 0 0 0 0   rgba(37,99,235,0);   }
}
@keyframes statFlash {
    0%   { transform: scale(1);    color: inherit; }
    40%  { transform: scale(1.18); color: #2563eb; }
    100% { transform: scale(1);    color: inherit; }
}
@keyframes badgePop {
    0%   { transform: scale(1);    }
    40%  { transform: scale(1.22); }
    70%  { transform: scale(0.94); }
    100% { transform: scale(1);    }
}
@keyframes bounce {
    0%, 100% { transform: translateY(0);     }
    50%       { transform: translateY(-10px); }
}

/* ── Hero ───────────────────────────────────────────────────────────────────── */
.pay-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--pay-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: slideIn 0.5s ease-out;
}
.pay-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.pay-hero h1 { font-size: 22px; font-weight: 700; color: #fff; margin: 0 0 6px; position: relative; }
.pay-hero p  { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; position: relative; }

/* ── Stat cards ─────────────────────────────────────────────────────────────── */
.stat-card {
    background: #fff;
    border: 1px solid var(--pay-border);
    border-radius: var(--pay-radius);
    padding: 18px 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
.stat-card .stat-value { font-size: 28px; font-weight: 700; color: var(--pay-primary); }
.stat-card .stat-label { font-size: 12px; color: var(--pay-muted); margin-top: 4px; }
.stat-card .stat-icon  { font-size: 32px; opacity: .12; float: right; margin-top: -8px; }
.stat-flash { animation: statFlash .45s cubic-bezier(.34,1.4,.64,1); }

/* ── Info banner ────────────────────────────────────────────────────────────── */
.info-banner {
    background: #eff6ff; border: 1px solid #bfdbfe;
    border-radius: 10px; padding: 12px 16px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    animation: fadeInUp 0.4s ease-out;
}
.info-banner i { font-size: 20px; color: #2563eb; }
.info-banner .text { font-size: 13px; color: #1e40af; }
.info-banner .text strong { display: block; margin-bottom: 4px; }
.info-banner .text a { color: #1e40af; font-weight: 600; text-decoration: underline; }

/* ── Promotion badges ───────────────────────────────────────────────────────── */
.promotion-badge-promoted,
.promotion-badge-trial,
.promotion-badge-see_principal,
.promotion-badge-repeated,
.promotion-badge-pending {
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 4px;
}
.promotion-badge-promoted    { background: #10b981; color: white; }
.promotion-badge-trial       { background: #f59e0b; color: white; }
.promotion-badge-see_principal { background: #3b82f6; color: white; }
.promotion-badge-repeated    { background: #ef4444; color: white; }
.promotion-badge-pending     { background: #6b7280; color: white; }
.badge-pop { animation: badgePop .4s cubic-bezier(.34,1.4,.64,1); }

/* ── Bulk action bar ────────────────────────────────────────────────────────── */
.bulk-action-bar {
    display: none; align-items: center; gap: 12px;
    background: #fff7ed; border: 1px solid #fed7aa;
    border-radius: 10px; padding: 10px 16px; margin-bottom: 16px;
}
.bulk-action-bar.visible { display: flex; }
.bulk-action-bar .bulk-count { font-size: 13px; font-weight: 600; color: #92400e; }
.select-all-checkbox { width: 16px; height: 16px; cursor: pointer; }

/* ── Modal chrome ───────────────────────────────────────────────────────────── */
.modal-content {
    border-radius: 16px; overflow: hidden;
    animation: modalZoomIn 0.3s cubic-bezier(0.34, 1.3, 0.64, 1);
}
.modal-header {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    padding: 20px 28px; border-bottom: none;
}
.modal-header .modal-title { color: #fff; font-weight: 700; }
.modal-header .btn-close { filter: invert(1); background: transparent; opacity: .8; }

.form-section { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 20px; }
.form-section-title {
    font-size: 14px; font-weight: 700; color: var(--pay-primary);
    margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--pay-border);
}

/* ── Decision cards ─────────────────────────────────────────────────────────── */
.form-check-card .form-check-input { display: none; }
.promotion-card, .trial-card, .principal-card, .repeat-card {
    transition: all 0.3s ease; background-color: #fff;
}
.promotion-card:hover { border-color: #198754 !important; box-shadow: 0 0 0 0.2rem rgba(25,135,84,.1); }
.trial-card:hover     { border-color: #ffc107 !important; box-shadow: 0 0 0 0.2rem rgba(255,193,7,.1); }
.principal-card:hover { border-color: #0dcaf0 !important; box-shadow: 0 0 0 0.2rem rgba(13,202,240,.1); }
.repeat-card:hover    { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220,53,69,.1); }

#promotionCheckbox:checked ~ label .promotion-card    { border-color: #198754 !important; background-color: #d1e7dd !important; }
#trialCheckbox:checked ~ label .trial-card            { border-color: #ffc107 !important; background-color: #fff3cd !important; }
#seePrincipalCheckbox:checked ~ label .principal-card { border-color: #0dcaf0 !important; background-color: #cff4fc !important; }
#repeatCheckbox:checked ~ label .repeat-card          { border-color: #dc3545 !important; background-color: #f8d7da !important; }

/* ── Row entrance ───────────────────────────────────────────────────────────── */
#studentTableBody tr[data-student-id] {
    opacity: 0; transform: translateY(14px);
    transition: opacity .38s cubic-bezier(.25,.46,.45,.94),
                transform .38s cubic-bezier(.25,.46,.45,.94),
                background .18s ease;
    will-change: opacity, transform;
}
#studentTableBody tr[data-student-id].row-visible { opacity: 1; transform: translateY(0); }
#studentTableBody tr[data-student-id]:hover {
    background: #f0f6ff !important;
    box-shadow: inset 3px 0 0 #2563eb;
    transform: translateY(-1px) !important;
    transition: background .14s ease, box-shadow .18s ease, transform .18s cubic-bezier(.34,1.4,.64,1);
    position: relative; z-index: 1;
}
#studentTableBody tr[data-student-id].selected             { background: #e0f2fe !important; }
#studentTableBody tr[data-student-id].selected:hover       { background: #d9ebf7 !important; }
#studentTableBody tr[data-student-id] .student-row-avatar  { transition: transform .18s ease, box-shadow .18s ease; }
#studentTableBody tr[data-student-id]:hover .student-row-avatar { transform: scale(1.12); box-shadow: 0 2px 8px rgba(0,0,0,.15); }
#studentTableBody tr[data-student-id]:hover .badge,
#studentTableBody tr[data-student-id]:hover [class*="promotion-badge-"] { transition: transform .18s cubic-bezier(.34,1.4,.64,1); transform: scale(1.06); }
#studentTableBody tr[data-student-id] .row-checkbox { opacity: .35; transform: scale(.85); transition: opacity .18s ease, transform .18s cubic-bezier(.34,1.4,.64,1); }
#studentTableBody tr[data-student-id]:hover .row-checkbox,
#studentTableBody tr[data-student-id] .row-checkbox:checked { opacity: 1; transform: scale(1); }

/* ── Score bar ──────────────────────────────────────────────────────────────── */
.score-bar-wrap { background: #e2e8f0; border-radius: 4px; height: 6px; width: 60px; display: inline-block; vertical-align: middle; margin-left: 6px; }
.score-bar-fill { height: 100%; border-radius: 4px; }

/* ── Table chrome ───────────────────────────────────────────────────────────── */
.compulsory-table { width: 100%; border-collapse: collapse; }
.compulsory-table th { background: var(--pay-primary); color: #fff; padding: 12px 16px; font-weight: 600; font-size: 13px; white-space: nowrap; text-align: left; }
.compulsory-table td { padding: 11px 16px; vertical-align: middle; border-bottom: 1px solid var(--pay-border); font-size: 13px; }

/* ── Subjects table (modal) ─────────────────────────────────────────────────── */
.subj-table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
.subj-table thead th {
    background: var(--pay-primary); color: #fff;
    padding: 10px 12px; font-weight: 600; font-size: 11.5px; white-space: nowrap;
    position: sticky; top: 0; z-index: 2;
    border-right: 1px solid rgba(255,255,255,.08);
}
.subj-table thead th:last-child { border-right: none; }
.subj-table tbody td { padding: 9px 12px; vertical-align: middle; border-bottom: 1px solid var(--pay-border); }
.subj-table tbody tr:hover td { background: #f8fafc; }
.subj-table .section-row td {
    background: #f1f5f9; padding: 6px 12px;
    font-size: 10.5px; font-weight: 700; color: #475569;
    letter-spacing: .05em; text-transform: uppercase;
    border-bottom: 1px solid var(--pay-border);
    border-top: 2px solid #e2e8f0;
}

/* grade colors */
.gc-a  { color: #15803d; }
.gc-b  { color: #1d4ed8; }
.gc-c  { color: #0369a1; }
.gc-d  { color: #d97706; }
.gc-f  { color: #b91c1c; }
.gc-na { color: #9ca3af; }

/* row left-border status */
.subj-table .row-pass    { border-left: 3px solid #10b981; }
.subj-table .row-fail    { border-left: 3px solid #ef4444; }
.subj-table .row-notsat  { border-left: 3px solid #f59e0b; }
.subj-table .row-credit  { border-left: 3px solid #3b82f6; }
.subj-table .row-passonly{ border-left: 3px solid #d97706; }
.subj-table .row-optfail { border-left: 3px solid #ef4444; }
.subj-table .row-optns   { border-left: 3px solid #9ca3af; }

/* inline mini-bar */
.mini-bar { display:inline-flex; align-items:center; gap:7px; }
.mini-bar-track { height:6px; background:#e2e8f0; border-radius:3px; display:inline-block; vertical-align:middle; overflow:hidden; flex-shrink:0; }
.mini-bar-fill  { height:100%; border-radius:3px; display:block; }

/* eval tag */
.eval-tag {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 500; padding: 2px 8px;
    border-radius: 5px; white-space: nowrap;
    border: 0.5px solid transparent;
}
.eval-green  { background: #dcfce7; color: #15803d; border-color: #86efac; }
.eval-red    { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
.eval-amber  { background: #fef9c3; color: #92400e; border-color: #fde68a; }
.eval-blue   { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.eval-gray   { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
.eval-sub { display: block; font-size: 10.5px; color: #64748b; margin-top: 2px; line-height: 1.35; }

/* stat summary row */
.subj-stat-row {
    display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
    padding: 10px 14px; background: #f8fafc;
    border: 1px solid var(--pay-border); border-radius: 8px; margin-bottom: 12px;
}
.subj-stat-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11.5px; font-weight: 500; padding: 3px 10px;
    border-radius: 20px;
}
.chip-total   { background: #ede9fe; color: #6d28d9; }
.chip-pass    { background: #dcfce7; color: #15803d; }
.chip-fail    { background: #fee2e2; color: #b91c1c; }
.chip-notsat  { background: #fef9c3; color: #92400e; }
.chip-credit  { background: #dbeafe; color: #1e40af; }

/* credit tally cards */
.credit-tally {
    display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;
}
.credit-tally-card {
    flex: 1; min-width: 120px;
    background: #fff; border: 1px solid var(--pay-border);
    border-radius: 8px; padding: 10px 14px;
    display: flex; align-items: center; gap: 10px;
}
.credit-tally-card .num { font-size: 22px; font-weight: 700; line-height: 1; }
.credit-tally-card .lbl { font-size: 11px; color: #64748b; line-height: 1.4; }
.credit-tally-card .lbl small { display: block; opacity: .75; }

/* rule match banner */
.rule-match-banner {
    padding: 10px 14px; border-radius: 8px;
    font-size: 12.5px; margin-bottom: 12px;
    display: flex; flex-direction: column; gap: 5px;
    border: 0.5px solid transparent;
}
.rule-match-banner.matched   { background: #dcfce7; color: #15803d; border-color: #86efac; }
.rule-match-banner.unmatched { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
.rule-match-banner.info      { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.rule-match-banner .top      { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; font-weight: 600; }
.rule-match-banner .sub      { font-size: 11.5px; opacity: .85; }

/* ── Empty state ────────────────────────────────────────────────────────────── */
.empty-state { text-align: center; padding: 52px 24px; color: var(--pay-muted); }
.empty-state i { font-size: 3rem; opacity: .25; display: block; margin-bottom: 14px; }

/* ── Search box ─────────────────────────────────────────────────────────────── */
.search-box { position: relative; }
.search-box .form-control { border: 1.5px solid var(--pay-border); border-radius: 8px; padding: 9px 14px; padding-right: 36px; font-size: 13px; width: 100%; }
.search-box .form-control:focus { border-color: var(--pay-accent); outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.search-box .search-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--pay-muted); pointer-events: none; }

/* ── Modal student profile ──────────────────────────────────────────────────── */
.student-avatar-lg { width: 120px; height: 120px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,.15); background: #f8f9fa; }
.status-badge-lg   { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 30px; font-size: 14px; font-weight: 600; }

/* ── Rule badge ─────────────────────────────────────────────────────────────── */
.rule-badge { background: #1e3a5f; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }

/* ── Button icons ───────────────────────────────────────────────────────────── */
.btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: all .15s; border: none; cursor: pointer; }
.btn-subtle-primary { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.btn-subtle-primary:hover { background: #dbeafe; color: #1d4ed8; transform: translateY(-1px); }
.btn-subtle-danger  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.btn-subtle-danger:hover  { background: #fee2e2; color: #b91c1c; transform: translateY(-1px); }

/* ── Toast ──────────────────────────────────────────────────────────────────── */
.toast-notification { position: fixed; bottom: 20px; right: 20px; z-index: 10000; animation: toastSlideIn 0.3s ease-out; }
.toast-notification.closing { animation: toastSlideOut 0.3s ease-out forwards; }

/* ── Loading overlay ────────────────────────────────────────────────────────── */
.loading-overlay  { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(3px); }
.loading-spinner  { background: white; padding: 20px 30px; border-radius: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }

/* ── Skeleton ───────────────────────────────────────────────────────────────── */
.skeleton-row td { position: relative; overflow: hidden; }
.skeleton-row td::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); animation: skeletonLoading 1.5s infinite; }

/* ── Misc ───────────────────────────────────────────────────────────────────── */
.btn-pulse { animation: btnPulse 2s infinite; }
.animate-bounce { animation: bounce 2s infinite; }
.avatar-sm { height: 3rem; width: 3rem; }
.avatar-title { align-items: center; display: flex; height: 100%; justify-content: center; width: 100%; }
.bg-success-subtle { background-color: rgba(25,135,84,.1)  !important; }
.bg-warning-subtle { background-color: rgba(255,193,7,.1)  !important; }
.bg-info-subtle    { background-color: rgba(13,202,240,.1) !important; }
.bg-danger-subtle  { background-color: rgba(220,53,69,.1)  !important; }
.table-hover tbody tr:hover { background-color: rgba(0,0,0,.02); }

/* ── Recommendation card ────────────────────────────────────────────────────── */
.recommendation-card { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
.recommendation-card.promoted      { border-left: 4px solid #10b981; }
.recommendation-card.trial         { border-left: 4px solid #f59e0b; }
.recommendation-card.see_principal { border-left: 4px solid #3b82f6; }
.recommendation-card.repeated      { border-left: 4px solid #ef4444; }
.recommendation-card .label { font-size: 12px; color: var(--pay-muted); margin-bottom: 4px; }
.recommendation-card .value { font-size: 16px; font-weight: 700; }

@media (prefers-reduced-motion: reduce) {
    #studentTableBody tr[data-student-id],
    #studentTableBody tr[data-student-id]:hover { transition: background .15s ease !important; transform: none !important; opacity: 1 !important; }
    .stat-flash, .badge-pop { animation: none !important; }
    .toast-notification, .modal-content { animation: none !important; }
}
.promotion-badge-advanced { background:#6366f1; color:#fff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
.quick-select-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-bottom:12px; }
.quick-select-bar .qs-label { font-size:13px; font-weight:600; color:#1e3a5f; }
.btn-subtle-warning { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.btn-subtle-warning:hover { background:#fef3c7; color:#92400e; }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Hero --}}
            <div class="pay-hero">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="ri-user-star-line me-2"></i>Student Promotion Management</h1>
                        <p>Manage student promotion, repetition, and class assignments based on academic performance.</p>
                    </div>
                    <div>
                        <a href="{{ route('promotion-settings.index') }}" class="btn btn-light">
                            <i class="ri-settings-4-line me-1"></i>Promotion Settings
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card" data-tooltip="Total number of students in this class">
                        <div class="stat-icon"><i class="ri-user-line"></i></div>
                        <div class="stat-value" id="totalStudents">0</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-tooltip="Students recommended for promotion">
                        <div class="stat-icon"><i class="ri-arrow-up-circle-line"></i></div>
                        <div class="stat-value text-success" id="promotedCount">0</div>
                        <div class="stat-label">Recommended Promoted</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-tooltip="Students recommended for conditional promotion">
                        <div class="stat-icon"><i class="ri-time-line"></i></div>
                        <div class="stat-value text-warning" id="trialCount">0</div>
                        <div class="stat-label">On Trial</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-tooltip="Students recommended to repeat current class">
                        <div class="stat-icon"><i class="ri-repeat-line"></i></div>
                        <div class="stat-value text-danger" id="repeatCount">0</div>
                        <div class="stat-label">To Repeat</div>
                    </div>
                </div>
            </div>

            {{-- Info Banner --}}
            <div class="info-banner">
                <i class="ri-information-line"></i>
                <div class="text">
                    <strong>Promotion Rules</strong>
                    Promotion decisions are based on compulsory subject performance and overall averages.
                    Only <strong>active</strong> rules are applied automatically.
                    Configure rules in <a href="{{ route('promotion-settings.index') }}">Promotion Settings</a>.
                </div>
            </div>

            {{-- Warning Banner --}}
            @php
                $selectedClassId = request()->input('schoolclassid');
                $hasPromotionSettings = false;
                if ($selectedClassId && $selectedClassId !== 'ALL') {
                    $hasPromotionSettings = \App\Models\PromotionSetting::where('schoolclass_id', $selectedClassId)
                        ->where('is_active', true)->exists();
                }
            @endphp

            @if(request()->filled('schoolclassid') && request()->input('schoolclassid') !== 'ALL' && !$hasPromotionSettings)
            <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert" style="border-left: 4px solid #d97706;">
                <div class="d-flex align-items-center">
                    <i class="ri-alert-line fs-4 me-3"></i>
                    <div>
                        <strong class="d-block mb-1">⚠️ No Promotion Rules Configured!</strong>
                        <span>No active promotion settings found for this class. Please
                        <a href="{{ route('promotion-settings.index') }}" class="alert-link fw-bold">configure promotion rules</a>
                        to enable automatic recommendations.</span>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            </div>
            @endif

            {{-- Filters --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Select Class</label>
                            <select class="form-select" id="idclass" name="schoolclassid">
                                <option value="ALL">-- Select Class --</option>
                                @foreach ($schoolclasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Select Session</label>
                            <select class="form-select" id="idsession" name="sessionid">
                                <option value="ALL">-- Select Session --</option>
                                @foreach ($schoolsessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Select Term</label>
                            <select class="form-select" id="idterm" name="termid">
                                <option value="3">Third Term (Promotional)</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}">{{ $term->term }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Average Basis</label>
                            <select class="form-select" id="average_basis" name="average_basis">
                                <option value="total">Term Total</option>
                                <option value="cum">Cumulative (Cum Avg)</option>
                            </select>
                            <small class="text-muted mt-1 d-block">Used for Overall Avg &amp; Position</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search Student</label>
                            <div class="search-box">
                                <input type="text" class="form-control" id="searchInput"
                                       placeholder="Search by name or admission number...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="ri-keyboard-line me-1"></i>Tip: Press <kbd>Ctrl+F</kbd> to focus search
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Students Table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="padding:16px 20px">
                    <h5 class="mb-0 fw-semibold" style="color:var(--pay-primary)">
                        <i class="ri-group-line me-2"></i>Students
                        <span class="badge bg-primary ms-2" id="studentcount">{{ $allstudents->total() }}</span>
                    </h5>
                    <div class="d-flex gap-2 align-items-center">
                        <small class="text-muted"><i class="ri-keyboard-line me-1"></i><kbd>Ctrl+A</kbd> Select all</small>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openHistoryModal()">
                            <i class="ri-history-line me-1"></i>History &amp; Undo
                        </button>
                        <button type="button" class="btn btn-dark btn-sm" onclick="openPrintListDialog()">
                            <i class="ri-printer-line me-1"></i>Print Student List
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Quick select by System Recommendation --}}
                    <div class="quick-select-bar" id="quickSelectBar">
                        <span class="qs-label"><i class="ri-checkbox-multiple-line me-1"></i>Quick select:</span>
                        <select class="form-select form-select-sm" id="quickSelectRecommendation" style="width:auto;min-width:230px;">
                            <option value="">— by System Recommendation —</option>
                            <option value="promoted">Promoted</option>
                            <option value="trial">Promoted on Trial</option>
                            <option value="see_principal">See Principal</option>
                            <option value="repeated">Advice to Repeat</option>
                            <option value="awaiting">Awaiting / Not Configured</option>
                        </select>
                        <label class="form-check-label small d-inline-flex align-items-center gap-1" title="Leave out students who already have a saved decision for this term">
                            <input type="checkbox" class="form-check-input m-0" id="quickSelectUndecidedOnly">
                            Only undecided
                        </label>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectByRecommendation(false)">
                            <i class="ri-check-double-line me-1"></i>Select matching
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectByRecommendation(true)" title="Add matching students to the current selection">
                            <i class="ri-add-line me-1"></i>Add to selection
                        </button>
                    </div>

                    <div class="bulk-action-bar" id="bulkActionBar">
                        <span class="bulk-count" id="bulkCount">0 selected</span>
                        <button type="button" class="btn btn-info btn-sm text-white" id="advanceTermActionBtn" onclick="submitAdvanceTerm()"
                                title="Move selected students to the next term, same class and session">
                            <i class="ri-skip-forward-line me-1"></i>Advance to Next Term
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="bulkPromoteActionBtn">
                            <i class="ri-group-line me-1"></i>Bulk Promote Selected
                        </button>
                        <button type="button" class="btn btn-light btn-sm" id="clearSelectionBtn">
                            <i class="ri-close-line me-1"></i>Clear
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="compulsory-table">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" class="select-all-checkbox" id="selectAll"></th>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Arm</th>
                                    <th>Session</th>
                                    <th>Overall Avg</th>
                                    <th>Position</th>
                                    <th>Recommendation</th>
                                    <th>Promotion Status</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody">
                                @include('promotions.partials.student_rows')
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3" id="pagination-container">
                        {{ $allstudents->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ================================================================
     Promotion Modal
     ================================================================ --}}
<div class="modal fade" id="promotionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white">
                    <i class="ri-user-star-line me-2"></i>Student Promotion Management
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="promotionForm">
                @csrf
                <div class="modal-body p-4" style="max-height:82vh;overflow-y:auto;">

                    {{-- Student Profile --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <img id="modalStudentImage"
                                         src="{{ asset('storage/student_avatars/unnamed.jpg') }}"
                                         alt="Student Picture"
                                         class="student-avatar-lg rounded-circle">
                                    <div class="mt-2">
                                        <span class="badge bg-primary" id="modalStudentGender"></span>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <h4 class="mb-2 text-primary" id="modalStudentName"></h4>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                                <i class="ri-book-2-line text-primary fs-4 me-3"></i>
                                                <div>
                                                    <small class="text-muted d-block">Current Class</small>
                                                    <strong id="modalCurrentClass" class="fs-5"></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                                <i class="ri-team-line text-primary fs-4 me-3"></i>
                                                <div>
                                                    <small class="text-muted d-block">Arm</small>
                                                    <strong id="modalCurrentArm" class="fs-5"></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                                <i class="ri-calendar-line text-primary fs-4 me-3"></i>
                                                <div>
                                                    <small class="text-muted d-block">Session</small>
                                                    <strong id="modalCurrentSession" class="fs-5"></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                                <i class="ri-percent-line text-primary fs-4 me-3"></i>
                                                <div>
                                                    <small class="text-muted d-block">Overall Average</small>
                                                    <strong id="modalOverallAverage" class="fs-5"></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Student Info (bio + parent contact) --}}
                    <div class="card border-0 shadow-sm mb-4" id="studentInfoCard" style="display:none;">
                        <div class="card-header bg-white fw-semibold"><i class="ri-user-line me-1"></i>Student Info</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-3"><small class="text-muted d-block">Date of Birth</small><strong id="modalDob">—</strong></div>
                                <div class="col-sm-3"><small class="text-muted d-block">Admission Date</small><strong id="modalAdmissionDate">—</strong></div>
                                <div class="col-sm-3"><small class="text-muted d-block">Phone</small><strong id="modalPhone">—</strong></div>
                                <div class="col-sm-3"><small class="text-muted d-block">Class Position</small><strong id="modalPosition">—</strong></div>
                                <div class="col-sm-12"><small class="text-muted d-block">Home Address</small><strong id="modalAddress">—</strong></div>
                            </div>
                            <hr class="my-3">
                            <div class="row g-3">
                                <div class="col-sm-4"><small class="text-muted d-block">Father</small><strong id="modalFather">—</strong></div>
                                <div class="col-sm-4"><small class="text-muted d-block">Mother</small><strong id="modalMother">—</strong></div>
                                <div class="col-sm-4"><small class="text-muted d-block">Parent Email</small><strong id="modalParentEmail">—</strong></div>
                            </div>
                        </div>
                    </div>

                    {{-- Class History --}}
                    <div class="card border-0 shadow-sm mb-4" id="classHistoryCard" style="display:none;">
                        <div class="card-header bg-white fw-semibold"><i class="ri-history-line me-1"></i>Class History</div>
                        <div class="card-body" id="classHistoryContent"></div>
                    </div>

                    {{-- System Recommendation --}}
                    <div class="card border-0 shadow-sm mb-4" id="recommendationCard" style="display:none;">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="ri-robot-line fs-4 text-primary"></i>
                                <h6 class="mb-0 fw-bold text-primary">System Recommendation</h6>
                            </div>
                            <div id="recommendationContent"></div>
                        </div>
                    </div>

                    {{-- Compulsory Subjects Summary --}}
                    <div class="card border-0 shadow-sm mb-4" id="compulsoryCard" style="display:none;">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="ri-star-fill fs-4 text-warning"></i>
                                <h6 class="mb-0 fw-bold">Compulsory Subjects Performance</h6>
                            </div>
                            <div id="compulsoryContent"></div>
                        </div>
                    </div>

                    {{-- All Subjects Table --}}
                    <div class="card border-0 shadow-sm mb-4" id="allSubjectsCard" style="display:none;">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="ri-book-open-line fs-4 text-primary"></i>
                                <h6 class="mb-0 fw-bold">All Subjects Performance</h6>
                            </div>
                            <div id="allSubjectsContent"></div>
                        </div>
                    </div>

                    <div class="text-center my-4">
                        <i class="ri-arrow-down-line text-primary fs-1 animate-bounce"></i>
                    </div>

                    {{-- New Assignment --}}
                    <div class="card border-2 border-primary shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="ri-refresh-line me-2"></i>New Assignment
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">New Class <span class="text-danger">*</span></label>
                                    <select class="form-select" name="new_schoolclassid" id="newClassSelect" required>
                                        <option value="">-- Select Class --</option>
                                        @foreach ($schoolclasses as $class)
                                            <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">New Session <span class="text-danger">*</span></label>
                                    <select class="form-select" name="new_sessionid" id="newSessionSelect" required>
                                        <option value="">-- Select Session --</option>
                                        @foreach ($schoolsessions as $session)
                                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">New Term <span class="text-danger">*</span></label>
                                    <select class="form-select" name="new_termid" id="newTermSelect" required>
                                        <option value="">-- Select Term --</option>
                                        <option value="1">First Term</option>
                                        <option value="2">Second Term</option>
                                        <option value="3">Third Term</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Promotion Decision --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <i class="ri-checkbox-circle-line me-2 text-primary"></i>Promotion Decision
                            <span class="text-danger">*</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-check form-check-card">
                                        <input class="form-check-input" type="checkbox" name="promotion" id="promotionCheckbox" value="promoted">
                                        <label class="form-check-label w-100" for="promotionCheckbox">
                                            <div class="d-flex align-items-center p-3 border rounded cursor-pointer promotion-card">
                                                <div class="flex-shrink-0">
                                                    <div class="avatar-sm"><div class="avatar-title bg-success-subtle text-success rounded-circle fs-2"><i class="ri-arrow-up-circle-line"></i></div></div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">Promote</h6>
                                                    <p class="text-muted mb-0 small">Move to next class level</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-card">
                                        <input class="form-check-input" type="checkbox" name="trial" id="trialCheckbox" value="trial">
                                        <label class="form-check-label w-100" for="trialCheckbox">
                                            <div class="d-flex align-items-center p-3 border rounded cursor-pointer trial-card">
                                                <div class="flex-shrink-0">
                                                    <div class="avatar-sm"><div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-2"><i class="ri-time-line"></i></div></div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">Promote on Trial</h6>
                                                    <p class="text-muted mb-0 small">Conditional promotion</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-card">
                                        <input class="form-check-input" type="checkbox" name="see_principal" id="seePrincipalCheckbox" value="see_principal">
                                        <label class="form-check-label w-100" for="seePrincipalCheckbox">
                                            <div class="d-flex align-items-center p-3 border rounded cursor-pointer principal-card">
                                                <div class="flex-shrink-0">
                                                    <div class="avatar-sm"><div class="avatar-title bg-info-subtle text-info rounded-circle fs-2"><i class="ri-eye-line"></i></div></div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">See Principal</h6>
                                                    <p class="text-muted mb-0 small">Principal review needed</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-card">
                                        <input class="form-check-input" type="checkbox" name="repeat" id="repeatCheckbox" value="repeat">
                                        <label class="form-check-label w-100" for="repeatCheckbox">
                                            <div class="d-flex align-items-center p-3 border rounded cursor-pointer repeat-card">
                                                <div class="flex-shrink-0">
                                                    <div class="avatar-sm"><div class="avatar-title bg-danger-subtle text-danger rounded-circle fs-2"><i class="ri-repeat-line"></i></div></div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">Repeat Class</h6>
                                                    <p class="text-muted mb-0 small">Student repeats current level</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitPromotion()">
                        <i class="ri-save-line me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================================
     Bulk Promotion Modal
     ================================================================ --}}
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white"><i class="ri-history-line me-2"></i>Promotion History &amp; Undo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <small class="text-muted flex-grow-1">
                        Every promotion, bulk promotion, term advance, cleared decision and removal is recorded here.
                        <strong>Revert</strong> puts the students' class placement and saved decision back exactly as they were.
                        Undo the newest action first.
                    </small>
                    <label class="form-check-label small d-inline-flex align-items-center gap-1">
                        <input type="checkbox" class="form-check-input m-0" id="historyThisSessionOnly" checked onchange="loadHistory()">
                        Selected session only
                    </label>
                </div>
                <div id="historyList"><div class="text-center text-muted py-4">Loading…</div></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkPromotionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white"><i class="ri-group-line me-2"></i>Bulk Promotion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>You have selected <strong id="bulkSelectedCount">0</strong> students.</p>
                <div class="mb-3">
                    <label class="form-label">Promotion Type</label>
                    <select class="form-select" id="bulkPromotionType">
                        <option value="promoted">Promote Students</option>
                        <option value="trial">Promote on Trial</option>
                        <option value="see_principal">Advised to See Principal</option>
                        <option value="repeat">Advice to Repeat</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Class</label>
                    <select class="form-select" id="bulkNewClass">
                        <option value="">-- Select Class --</option>
                        @foreach ($schoolclasses as $class)
                            <option value="{{ $class->id }}">{{ $class->schoolclass }} {{ $class->arm ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Session</label>
                    <select class="form-select" id="bulkNewSession">
                        <option value="">-- Select Session --</option>
                        @foreach ($schoolsessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Term</label>
                    <select class="form-select" id="bulkNewTerm">
                        <option value="1">First Term</option>
                        <option value="2">Second Term</option>
                        <option value="3">Third Term</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBulkPromoteBtn">Process Bulk Promotion</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentStudentId     = null;
let currentSchoolclassId = null;
let currentSessionId     = null;
let currentTermId        = null;
let currentStudentData   = null;

/* ── Grade helpers ─────────────────────────────────────────────────────────── */
const SENIOR_GO = { F9:0, E8:1, D7:2, C6:3, C5:4, C4:5, B3:6, B2:7, A1:8 };
const JUNIOR_GO = { F:0, D:1, C:2, B:3, A:4 };

function detectScale(grades) {
    return grades.some(g => /^[A-E][1-9]$|^F9$/.test((g||'').toUpperCase()));
}
function gradeRank(g, senior) {
    const u = (g||'').toUpperCase();
    return senior ? (SENIOR_GO[u] ?? -1) : (JUNIOR_GO[u] ?? -1);
}
function isCreditGrade(g, senior) {
    const u = (g||'').toUpperCase();
    return senior ? ['A1','B2','B3','C4','C5','C6'].includes(u) : ['A','B','C'].includes(u);
}
function isPassGrade(g, senior) {
    const u = (g||'').toUpperCase();
    return senior ? !['F9','E8'].includes(u) && u !== '' : u !== 'F' && u !== '';
}
function gradeColorClass(g) {
    const u = (g||'').toUpperCase();
    if (['A1','A'].includes(u))           return 'gc-a';
    if (['B2','B3','B'].includes(u))      return 'gc-b';
    if (['C4','C5','C6','C'].includes(u)) return 'gc-c';
    if (['D7','D'].includes(u))           return 'gc-d';
    if (['F9','E8','F'].includes(u))      return 'gc-f';
    return 'gc-na';
}

/* ── escapeHtml ────────────────────────────────────────────────────────────── */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Image helpers ─────────────────────────────────────────────────────────── */
function normalizeImagePath(picture, gender) {
    const defaultImg = gender === 'Male'
        ? '/storage/student_avatars/male-default.png'
        : '/storage/student_avatars/female-default.png';

    if (!picture) return defaultImg;
    const raw = String(picture).trim();
    if (!raw || ['null','undefined','false'].includes(raw)) return defaultImg;

    // Already absolute URL
    if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;

    // Strip all known leading segments to get the bare filename/path
    let clean = raw
        .replace(/^\/+/, '')          // leading slashes
        .replace(/^storage\//, '')    // storage/
        .replace(/^public\//, '')     // public/
        .replace(/^app\/public\//, '') // app/public/
        .replace(/^\/+/, '');         // any remaining leading slashes

    if (!clean) return defaultImg;
    return '/storage/' + clean;
}

function setStudentImage(imgEl, primarySrc, gender) {
    const fallback1 = gender === 'Male'
        ? '/storage/student_avatars/male-default.png'
        : '/storage/student_avatars/female-default.png';
    const fallback2 = '/storage/student_avatars/unnamed.jpg';

    const tries = [primarySrc, fallback1, fallback2]
        .filter(s => s && s !== '/storage/' && s !== '/storage/null' && s !== '/storage/undefined');

    console.log('[StudentImage] trying paths:', tries);

    let attempt = 0;
    imgEl.onerror = null;
    imgEl.onerror = function () {
        console.warn('[StudentImage] failed:', this.src, '— trying next fallback');
        attempt++;
        if (attempt < tries.length) { this.src = tries[attempt]; }
        else { this.onerror = null; }
    };
    imgEl.src = tries[0] || fallback2;
}

/* ── Toast ─────────────────────────────────────────────────────────────────── */
function showToast(message, type = 'info') {
    const colors = { success:'#16a34a', warning:'#d97706', danger:'#dc2626', info:'#2563eb' };
    const icons  = { success:'ri-checkbox-circle-line', warning:'ri-alert-line', danger:'ri-error-warning-line', info:'ri-information-line' };
    const id = 'toast_' + Date.now();
    document.body.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast-notification" style="background:${colors[type]};color:white;padding:12px 20px;border-radius:10px;display:flex;align-items:center;gap:10px;box-shadow:0 4px 15px rgba(0,0,0,0.2);">
            <i class="${icons[type]} fs-5"></i>
            <span>${message}</span>
            <button onclick="document.getElementById('${id}').classList.add('closing');setTimeout(()=>document.getElementById('${id}')?.remove(),300)" style="background:none;border:none;color:white;margin-left:10px;cursor:pointer;font-size:18px;">&times;</button>
        </div>`);
    setTimeout(() => {
        const t = document.getElementById(id);
        if (t) { t.classList.add('closing'); setTimeout(() => t.remove(), 300); }
    }, 4000);
}

/* ── Loading ───────────────────────────────────────────────────────────────── */
function showLoading(msg = 'Loading...') {
    let ov = document.getElementById('globalLoadingOverlay');
    if (!ov) {
        ov = document.createElement('div');
        ov.id = 'globalLoadingOverlay';
        ov.className = 'loading-overlay';
        ov.innerHTML = `<div class="loading-spinner"><div class="spinner-border text-primary" role="status"></div><span id="loadingMessage">${msg}</span></div>`;
        document.body.appendChild(ov);
    } else {
        ov.style.display = 'flex';
        const s = ov.querySelector('#loadingMessage');
        if (s) s.textContent = msg;
    }
}
function hideLoading() {
    const ov = document.getElementById('globalLoadingOverlay');
    if (ov) ov.style.display = 'none';
}

/* ── Counter animation ─────────────────────────────────────────────────────── */
function easeOutCubic(x) { return 1 - Math.pow(1 - x, 3); }
function animateCounter(el, start, end, dur = 500) {
    if (!el) return;
    const t0 = performance.now();
    const tick = (t) => {
        const p = Math.min((t - t0) / dur, 1);
        el.textContent = Math.floor(start + (end - start) * easeOutCubic(p));
        if (p < 1) requestAnimationFrame(tick);
        else el.textContent = end;
    };
    requestAnimationFrame(tick);
}

/* ── Stats ─────────────────────────────────────────────────────────────────── */
function updateStats() {
    if (typeof refreshQuickSelectCounts === 'function') refreshQuickSelectCounts();
    const rows = document.querySelectorAll('#studentTableBody tr[data-student-id]');
    let total = 0, promoted = 0, trial = 0, repeat = 0;
    rows.forEach(row => {
        total++;
        const s = (row.querySelector('[data-rec-status]')?.getAttribute('data-rec-status') || '').toLowerCase();
        if (s === 'promoted') promoted++;
        else if (s === 'trial') trial++;
        else if (s === 'repeated' || s === 'repeat') repeat++;
    });
    animateCounter(document.getElementById('totalStudents'),  parseInt(document.getElementById('totalStudents')?.innerText)  || 0, total);
    animateCounter(document.getElementById('promotedCount'),  parseInt(document.getElementById('promotedCount')?.innerText)  || 0, promoted);
    animateCounter(document.getElementById('trialCount'),     parseInt(document.getElementById('trialCount')?.innerText)     || 0, trial);
    animateCounter(document.getElementById('repeatCount'),    parseInt(document.getElementById('repeatCount')?.innerText)    || 0, repeat);
    document.querySelectorAll('.stat-card').forEach(c => {
        c.classList.add('stat-flash');
        setTimeout(() => c.classList.remove('stat-flash'), 450);
    });
}

/* ── Row animations ────────────────────────────────────────────────────────── */
function triggerRowEntrance() {
    document.querySelectorAll('#studentTableBody tr[data-student-id]').forEach((row, i) => {
        row.classList.remove('row-visible');
        setTimeout(() => row.classList.add('row-visible'), i * 20);
    });
}
function popPromotionBadges() {
    document.querySelectorAll('#studentTableBody [class*="promotion-badge-"]').forEach(b => {
        b.classList.remove('badge-pop');
        void b.offsetWidth;
        b.classList.add('badge-pop');
        b.addEventListener('animationend', () => b.classList.remove('badge-pop'), { once: true });
    });
}

/* ── Filter & load ─────────────────────────────────────────────────────────── */
function filterData() {
    const cls  = document.getElementById('idclass').value;
    const sess = document.getElementById('idsession').value;
    const term = document.getElementById('idterm').value;
    const srch = document.getElementById('searchInput').value.trim();

    if (cls === 'ALL' || sess === 'ALL') {
        document.getElementById('studentTableBody').innerHTML =
            '<tr><td colspan="11" class="text-center py-4 text-muted">Select class and session to view students.</td></tr>';
        document.getElementById('pagination-container').innerHTML = '';
        document.getElementById('studentcount').innerText = '0';
        updateStats();
        return;
    }

    const tb = document.getElementById('studentTableBody');
    tb.innerHTML = '<tr class="skeleton-row"><td colspan="11"><div style="height:300px;"></div></td></tr>';
    showLoading('Loading students...');

    axios.get('{{ route("promotions.index") }}', {
        params: { search: srch, schoolclassid: cls, sessionid: sess, termid: term, average_basis: getAverageBasis() },
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' }
    }).then(res => {
        hideLoading();
        document.getElementById('studentTableBody').innerHTML       = res.data.tableBody;
        document.getElementById('pagination-container').innerHTML   = res.data.pagination;
        document.getElementById('studentcount').innerText           = res.data.studentCount || '0';
        updateStats(); setupPaginationLinks(); setupCheckboxHandlers();
        triggerRowEntrance(); popPromotionBadges(); setupRowSelection();
        showToast(`${res.data.studentCount || 0} students loaded`, 'success');
    }).catch(err => {
        hideLoading();
        tb.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">Error loading data. Please try again.</td></tr>';
        showToast('Failed to fetch student data', 'danger');
    });
}

function setupPaginationLinks() {
    document.querySelectorAll('#pagination-container a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = new URL(this.href);
            url.searchParams.set('schoolclassid', document.getElementById('idclass').value);
            url.searchParams.set('sessionid',     document.getElementById('idsession').value);
            url.searchParams.set('termid',        document.getElementById('idterm').value);
            url.searchParams.set('average_basis', getAverageBasis());
            loadPage(url.toString());
        });
    });
}

function loadPage(url) {
    const tb = document.getElementById('studentTableBody');
    tb.innerHTML = '<tr class="skeleton-row"><td colspan="11"><div style="height:300px;"></div></td></tr>';
    showLoading('Loading page...');
    axios.get(url, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' }
    }).then(res => {
        hideLoading();
        document.getElementById('studentTableBody').innerHTML     = res.data.tableBody;
        document.getElementById('pagination-container').innerHTML = res.data.pagination;
        document.getElementById('studentcount').innerText         = res.data.studentCount || '0';
        updateStats(); setupPaginationLinks(); setupCheckboxHandlers();
        triggerRowEntrance(); popPromotionBadges(); setupRowSelection();
    }).catch(() => {
        hideLoading();
        tb.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">Error loading data.</td></tr>';
    });
}

/* ── Row selection ─────────────────────────────────────────────────────────── */
function setupRowSelection() {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.removeEventListener('change', handleRowSelectionChange);
        cb.addEventListener('change', handleRowSelectionChange);
    });
}
function handleRowSelectionChange() {
    const row = this.closest('tr');
    row?.classList.toggle('selected', this.checked);
    updateBulkBar();
}
function setupCheckboxHandlers() {
    const sa = document.getElementById('selectAll');
    if (sa) {
        const fresh = sa.cloneNode(true);
        sa.parentNode.replaceChild(fresh, sa);
        fresh.addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = this.checked;
                cb.closest('tr')?.classList.toggle('selected', this.checked);
            });
            updateBulkBar();
        });
    }
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.removeEventListener('change', handleRowSelectionChange);
        cb.addEventListener('change', handleRowSelectionChange);
    });
}
function updateBulkBar() {
    const count = document.querySelectorAll('.row-checkbox:checked').length;
    const bar   = document.getElementById('bulkActionBar');
    bar.classList.toggle('visible', count > 0);
    document.getElementById('bulkCount').innerText = count + ' selected';
}
function clearSelection() {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = false;
        cb.closest('tr')?.classList.remove('selected');
    });
    const sa = document.getElementById('selectAll');
    if (sa) sa.checked = false;
    updateBulkBar();
    showToast('Selection cleared', 'info');
}
document.getElementById('clearSelectionBtn')?.addEventListener('click', clearSelection);

document.getElementById('bulkPromoteActionBtn')?.addEventListener('click', () => {
    const selected = document.querySelectorAll('.row-checkbox:checked');
    if (!selected.length) { showToast('No students selected', 'warning'); return; }
    document.getElementById('bulkSelectedCount').innerText = selected.length;
    presetBulkDecisionFromSelection();
    new bootstrap.Modal(document.getElementById('bulkPromotionModal')).show();
});

document.getElementById('confirmBulkPromoteBtn')?.addEventListener('click', async () => {
    const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) { showToast('No students selected', 'warning'); return; }
    const newClass   = document.getElementById('bulkNewClass').value;
    const newSession = document.getElementById('bulkNewSession').value;
    if (!newClass || !newSession) { showToast('Please select new class and session', 'warning'); return; }

    showLoading(`Processing ${ids.length} students...`);
    bootstrap.Modal.getInstance(document.getElementById('bulkPromotionModal'))?.hide();

    try {
        const res = await axios.post('{{ route("promotions.bulk.promote") }}', {
            student_ids: ids,
            new_schoolclassid: newClass,
            new_sessionid: newSession,
            new_termid: document.getElementById('bulkNewTerm').value,
            promotion_type: document.getElementById('bulkPromotionType').value,
            _token: document.querySelector('meta[name="csrf-token"]').content
        });
        hideLoading();
        showToast(res.data.message, res.data.success ? 'success' : 'danger');
        if (res.data.success) { clearSelection(); filterData(); offerUndo(res.data.batch_id, res.data.message); }
    } catch (err) {
        hideLoading();
        showToast(err.response?.data?.message || 'Bulk promotion failed', 'danger');
    }
});

/* ── formatRuleDescription ─────────────────────────────────────────────────── */
function formatRuleDescription(d) {
    if (!d) return '';
    let f = d.replace(/subj\b/g,'subjects').replace(/subj\.\b/g,'subjects').replace(/;\s*>=/g,'; ≥');
    return f.charAt(0).toUpperCase() + f.slice(1);
}

/* ── Build subjects table ───────────────────────────────────────────────────── */
function buildSubjectsTable(allSubjects, result) {
    if (!allSubjects || !allSubjects.length) return '';

    const senior      = detectScale(allSubjects.map(s => s.grade));
    const creditLabel = senior ? 'C4–A1' : 'C–A';
    const FAIL_SET    = new Set(senior ? ['F9','E8'] : ['F']);

    function isCredit(g)  { return g && g !== '—' && isCreditGrade(g, senior); }
    function isPass(g)    { return g && g !== '—' && isPassGrade(g, senior); }
    function isFail(g)    { return g && g !== '—' && FAIL_SET.has(g.toUpperCase()); }

    const compList  = allSubjects.filter(s =>  s.is_compulsory);
    const optList   = allSubjects.filter(s => !s.is_compulsory);

    // Tallies
    const compCred  = compList.filter(s => isCredit(s.grade)).length;
    const optCred   = optList.filter(s  => isCredit(s.grade)).length;
    const allCred   = allSubjects.filter(s => isCredit(s.grade)).length;
    const compPass  = compList.filter(s => s.pass_status === 'pass').length;
    const compFail  = compList.filter(s => s.pass_status === 'fail').length;
    const compNS    = compList.filter(s => s.pass_status === 'not_sat').length;
    const optPass   = optList.filter(s  => isPass(s.grade) || s.pass_status === 'optional_pass').length;
    const optFail   = optList.filter(s  => isFail(s.grade) || s.pass_status === 'optional_fail').length;

    const appliedRule   = result?.applied_rule || null;
    const ruleStatus    = result?.status || null;
    // rule_logic tells us WHICH evaluation mode actually decided this
    // student's outcome — this drives which banner variant we show below.
    const ruleLogicMode = result?.rule_logic || result?.settings?.rule_logic || 'grade_count';

    // ── Rule match banner
    let html = '';
    if (appliedRule) {
        // A rule genuinely matched (grade_count or both mode) — show which one.
        const statusBg = { promoted:'matched', trial:'matched', see_principal:'matched', repeated:'unmatched' }[ruleStatus] || 'matched';
        html += `<div class="rule-match-banner ${statusBg}">
            <div class="top">
                <i class="ri-price-tag-3-line"></i>
                Matched rule: <strong>${escapeHtml(appliedRule.name)}</strong>
                ${appliedRule.description ? `<span style="font-weight:400;opacity:.8;">— ${escapeHtml(formatRuleDescription(appliedRule.description))}</span>` : ''}
            </div>
            <div class="sub"><i class="ri-information-line me-1"></i>Credit grades (${creditLabel}) contribute to grade-count conditions. Each subject's contribution is shown below.</div>
        </div>`;
    } else if (ruleLogicMode === 'average_only') {
        // No rule is EVER matched in average_only mode by design — the
        // per-rule conditions are not evaluated at all in this mode. Showing
        // "No rule matched" here would be misleading, since the outcome is
        // driven entirely by the Global Minimum Average comparison instead.
        const requiredAvg = result?.required_average;
        const actualAvg   = result?.actual_average;
        const hasBoth      = requiredAvg !== null && requiredAvg !== undefined
                           && actualAvg   !== null && actualAvg   !== undefined;
        const metAvg       = hasBoth && actualAvg >= requiredAvg;
        const outcomeLabelMap = {
            promoted: 'Promoted', trial: 'Promoted on Trial',
            see_principal: 'Advised to See Principal',
            repeated: 'Advice to Repeat', awaiting: 'Awaiting Decision',
        };
        const bannerCls = !hasBoth ? 'info' : (metAvg ? 'matched' : 'unmatched');

        html += `<div class="rule-match-banner ${bannerCls}">
            <div class="top">
                <i class="ri-percent-line"></i>
                Average-only mode: <strong>${hasBoth ? actualAvg + '%' : '—'}</strong>
                vs required <strong>${hasBoth ? requiredAvg + '%' : '— (not configured)'}</strong>
                → <strong>${outcomeLabelMap[ruleStatus] || ruleStatus || '—'}</strong>
            </div>
            <div class="sub"><i class="ri-information-line me-1"></i>This rule set's Evaluation Mode is "Minimum Average Only" — grade-count rules and per-subject minimums below are informational only and are not evaluated. Only the Global Minimum Average decides the outcome in this mode.</div>
        </div>`;
    } else {
        // grade_count or both mode, and genuinely no rule matched.
        html += `<div class="rule-match-banner unmatched">
            <div class="top"><i class="ri-close-circle-line"></i>No rule matched — result is Advice to Repeat.</div>
            <div class="sub"><i class="ri-information-line me-1"></i>Check compulsory subject min grades and whether credit counts reach rule thresholds.</div>
        </div>`;
    }

    // ── Credit tally cards
    html += `<div class="credit-tally">
        <div class="credit-tally-card">
            <div class="num" style="color:#15803d;">${compCred}</div>
            <div class="lbl">Credits — compulsory<small>of ${compList.length} subjects</small></div>
        </div>
        <div class="credit-tally-card">
            <div class="num" style="color:#1e40af;">${optCred}</div>
            <div class="lbl">Credits — optional<small>of ${optList.length} subjects</small></div>
        </div>
        <div class="credit-tally-card">
            <div class="num" style="color:#1e293b;">${allCred}</div>
            <div class="lbl">Total credits<small>of ${allSubjects.length} subjects</small></div>
        </div>
    </div>`;

    // ── Summary strip
    html += `<div class="subj-stat-row">
        <span class="subj-stat-chip chip-total"><i class="ri-book-open-line me-1"></i>${allSubjects.length} subjects</span>
        <span style="color:#cbd5e1;font-size:11px;">|</span>
        <span style="font-size:11px;color:#64748b;font-weight:600;">Compulsory:</span>
        <span class="subj-stat-chip chip-pass"><i class="ri-checkbox-circle-line me-1"></i>${compPass} passed</span>
        <span class="subj-stat-chip chip-fail"><i class="ri-close-circle-line me-1"></i>${compFail} failed</span>
        ${compNS ? `<span class="subj-stat-chip chip-notsat"><i class="ri-minus-circle-line me-1"></i>${compNS} not sat</span>` : ''}
        <span style="color:#cbd5e1;font-size:11px;">|</span>
        <span style="font-size:11px;color:#64748b;font-weight:600;">Optional:</span>
        <span class="subj-stat-chip chip-pass"><i class="ri-checkbox-circle-line me-1"></i>${optPass} passed</span>
        <span class="subj-stat-chip chip-fail"><i class="ri-close-circle-line me-1"></i>${optFail} failed</span>
        <span class="subj-stat-chip chip-credit ms-auto"><i class="ri-add-circle-line me-1"></i>${allCred} total credits</span>
    </div>`;

    // ── Table
    // col widths: # 36 | Subject ~auto | Code 60 | Score 110 | Grade 56 | Min 70 | Eval ~auto
    html += `<div style="overflow-x:auto;border-radius:10px;border:1px solid var(--pay-border);">
    <table class="subj-table">
    <colgroup>
        <col style="width:36px">
        <col>
        <col style="width:62px">
        <col style="width:120px">
        <col style="width:58px">
        <col style="width:76px">
        <col>
    </colgroup>
    <thead><tr>
        <th style="text-align:center;">#</th>
        <th>Subject</th>
        <th style="text-align:center;">Code</th>
        <th style="text-align:center;">Score / 100</th>
        <th style="text-align:center;">Grade</th>
        <th style="text-align:center;">Min grade</th>
        <th>Rule evaluation</th>
    </tr></thead>
    <tbody>`;

    /* ── Eval tag builder ── */
    function evalTag(cls, icon, label) {
        return `<span class="eval-tag eval-${cls}"><i class="${icon}"></i> ${label}</span>`;
    }
    function evalNote(text) {
        return `<span class="eval-sub">${text}</span>`;
    }
    function buildEval(s) {
        const grade = (s.grade || '').toUpperCase();
        const ps    = s.pass_status;
        const min   = s.required_min_grade;

        if (s.is_compulsory) {
            if (ps === 'not_sat')
                return evalTag('amber','ri-alert-line','Not sat') +
                       evalNote('Absent — compulsory subjects must be sat to qualify');
            if (min && min !== '—') {
                return ps === 'pass'
                    ? evalTag('green','ri-checkbox-circle-line',`Meets min grade (≥ ${min})`) +
                      evalNote(`${grade} ≥ required ${min} ✓`)
                    : evalTag('red','ri-close-circle-line',`Below min grade (needs ≥ ${min})`) +
                      evalNote(`${grade} < required ${min} — blocks promotion`);
            }
            return ps === 'pass'
                ? evalTag('green','ri-checkbox-circle-line','Compulsory — passed') + evalNote('Meets pass threshold')
                : evalTag('red','ri-close-circle-line','Compulsory — failed') + evalNote('Does not meet pass threshold');
        }

        // Optional
        if (!grade || grade === '—')
            return evalTag('gray','ri-subtract-line','Not attempted') + evalNote('No score — not counted in any condition');
        if (isCredit(s.grade))
            return evalTag('blue','ri-add-circle-line','Credit') + evalNote(`${grade} = credit (${creditLabel}) — counts toward credit conditions`);
        if (isPass(s.grade))
            return evalTag('amber','ri-record-circle-line','Pass — not a credit') + evalNote(`${grade} is a pass but below credit threshold`);
        return evalTag('red','ri-close-circle-line','Fail') + evalNote(`${grade} is a failing grade — not counted`);
    }

    /* ── Grade → bar/text color (always grade-based, not status-based) ── */
    function gradeHexColor(g) {
        const u = (g || '').toUpperCase();
        if (['A1','A'].includes(u))           return { bar: '#10b981', text: '#15803d' }; // emerald
        if (['B2','B3','B'].includes(u))      return { bar: '#3b82f6', text: '#1d4ed8' }; // blue
        if (['C4','C5','C6','C'].includes(u)) return { bar: '#0891b2', text: '#0369a1' }; // cyan
        if (['D7','D'].includes(u))           return { bar: '#f59e0b', text: '#d97706' }; // amber
        if (['E8'].includes(u))               return { bar: '#f97316', text: '#c2410c' }; // orange
        if (['F9','F'].includes(u))           return { bar: '#ef4444', text: '#b91c1c' }; // red
        return { bar: '#94a3b8', text: '#64748b' };                                        // gray (not sat)
    }

    /* ── Score mini-bar — color driven by grade ── */
    function scoreBar(score, grade) {
        if (score === null || score === undefined || score === '')
            return `<span style="color:#94a3b8;font-size:12px;">—</span>`;
        const num = parseFloat(score);
        const pct = Math.min(100, Math.round((num / 100) * 100));
        const clr = gradeHexColor(grade);
        return `<span class="mini-bar">
            <strong style="min-width:28px;text-align:right;display:inline-block;color:${clr.text};font-size:13px;">${score}</strong>
            <span class="mini-bar-track" style="width:52px;height:5px;">
                <span class="mini-bar-fill" style="width:${pct}%;background:${clr.bar};"></span>
            </span>
        </span>`;
    }

    /* ── Row class ── */
    function rowCls(s) {
        if (s.is_compulsory) {
            if (s.pass_status === 'pass')    return 'row-pass';
            if (s.pass_status === 'not_sat') return 'row-notsat';
            return 'row-fail';
        }
        const g = (s.grade || '').toUpperCase();
        if (!g || g === '—') return 'row-optns';
        if (isCredit(s.grade)) return 'row-credit';
        if (isPass(s.grade))   return 'row-passonly';
        return 'row-optfail';
    }

    /* ── Compulsory section ── */
    if (compList.length) {
        html += `<tr class="section-row"><td colspan="7">
            <i class="ri-star-fill" style="color:#d97706;margin-right:5px;"></i>
            Compulsory subjects — always rule-bound &nbsp;·&nbsp; ${compList.length} subject${compList.length !== 1 ? 's' : ''}
        </td></tr>`;

        compList.forEach((s, i) => {
            const grade = s.grade || '—';
            const min   = s.required_min_grade;
            html += `<tr class="${rowCls(s)}">
                <td style="text-align:center;color:#94a3b8;font-size:11px;">${i + 1}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                        <strong style="font-size:13px;">${escapeHtml(s.subject_name)}</strong>
                        <span style="background:#fef3c7;color:#92400e;border:0.5px solid #fde68a;font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px;letter-spacing:.03em;">COMPULSORY</span>
                    </div>
                </td>
                <td style="text-align:center;font-family:monospace;font-size:11px;color:#64748b;">${escapeHtml(s.subject_code || '—')}</td>
                <td style="text-align:center;">${scoreBar(s.total, grade)}</td>
                <td style="text-align:center;"><strong class="${gradeColorClass(grade)}" style="font-size:17px;letter-spacing:.02em;">${grade}</strong></td>
                <td style="text-align:center;">
                    ${min && min !== '—'
                        ? `<span style="background:#dbeafe;color:#1e40af;border:0.5px solid #bfdbfe;font-size:11px;padding:2px 9px;border-radius:10px;font-weight:600;">≥ ${min}</span>`
                        : `<span style="color:#94a3b8;font-size:12px;">—</span>`}
                </td>
                <td>${buildEval(s)}</td>
            </tr>`;
        });
    }

    /* ── Optional section ── */
    if (optList.length) {
        html += `<tr class="section-row"><td colspan="7">
            <i class="ri-book-line" style="color:#0891b2;margin-right:5px;"></i>
            Optional subjects — credits count toward grade conditions &nbsp;·&nbsp; ${optCred} credit${optCred !== 1 ? 's' : ''} from ${optList.length} subject${optList.length !== 1 ? 's' : ''}
        </td></tr>`;

        optList.forEach((s, i) => {
            const grade = s.grade || '—';
            html += `<tr class="${rowCls(s)}">
                <td style="text-align:center;color:#94a3b8;font-size:11px;">${compList.length + i + 1}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                        <strong style="font-size:13px;">${escapeHtml(s.subject_name)}</strong>
                        <span style="background:#e0f2fe;color:#0369a1;border:0.5px solid #bae6fd;font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px;letter-spacing:.03em;">OPTIONAL</span>
                    </div>
                </td>
                <td style="text-align:center;font-family:monospace;font-size:11px;color:#64748b;">${escapeHtml(s.subject_code || '—')}</td>
                <td style="text-align:center;">${scoreBar(s.total, grade)}</td>
                <td style="text-align:center;"><strong class="${gradeColorClass(grade)}" style="font-size:17px;letter-spacing:.02em;">${grade}</strong></td>
                <td style="text-align:center;"><span style="color:#94a3b8;font-size:11px;font-style:italic;">No min grade</span></td>
                <td>${buildEval(s)}</td>
            </tr>`;
        });
    }

    html += `</tbody></table></div>`;
    return html;
}

/* ── Open promotion modal ───────────────────────────────────────────────────── */
async function openPromotionModal(studentId, admissionNo, firstName, lastName, otherName, picture, gender, schoolclass, schoolarm, session, termid) {
    currentStudentId     = studentId;
    currentSchoolclassId = document.getElementById('idclass').value;
    currentSessionId     = document.getElementById('idsession').value;
    // Always the selected filter term, so the modal evaluates exactly what the table shows.
    currentTermId        = document.getElementById('idterm').value || termid;

    document.getElementById('modalStudentName').innerHTML =
        `<i class="ri-id-card-line me-2"></i>${admissionNo} — ${firstName} ${lastName}${otherName ? ' ' + otherName : ''}`;
    document.getElementById('modalStudentGender').innerHTML =
        `<i class="ri-gender-${gender === 'Male' ? 'male' : 'female'}-line me-1"></i>${gender || 'N/A'}`;
    document.getElementById('modalCurrentClass').innerText   = schoolclass;
    document.getElementById('modalCurrentArm').innerText     = schoolarm || 'N/A';
    document.getElementById('modalCurrentSession').innerText = session;

    // Show picture immediately from what the table already has (no blank flash)
    const imgEl = document.getElementById('modalStudentImage');
    setStudentImage(imgEl, normalizeImagePath(picture, gender), gender);

    // Reset form state
    document.getElementById('promotionForm').reset();
    ['newClassSelect','newSessionSelect','newTermSelect'].forEach(id => {
        document.getElementById(id).value = '';
    });
    ['promotionCheckbox','trialCheckbox','seePrincipalCheckbox','repeatCheckbox'].forEach(id => {
        document.getElementById(id).checked = false;
    });
    document.getElementById('recommendationCard').style.display  = 'none';
    document.getElementById('compulsoryCard').style.display      = 'none';
    document.getElementById('allSubjectsCard').style.display     = 'none';
    document.getElementById('allSubjectsContent').innerHTML      = '';
    document.getElementById('compulsoryContent').innerHTML       = '';
    document.getElementById('recommendationContent').innerHTML   = '';
    document.getElementById('modalOverallAverage').innerHTML     = '<span class="text-muted">Loading…</span>';
    document.getElementById('studentInfoCard').style.display     = 'none';
    document.getElementById('classHistoryCard').style.display    = 'none';
    document.getElementById('classHistoryContent').innerHTML     = '';
    ['modalDob','modalAdmissionDate','modalPhone','modalAddress','modalFather','modalMother','modalParentEmail','modalPosition']
        .forEach(id => { document.getElementById(id).innerText = '—'; });

    showLoading('Loading student data...');

    try {
        const response = await axios.get(
            `/promotions/student-details/${studentId}/${currentSchoolclassId}/${currentSessionId}/${currentTermId}`,
            { params: { average_basis: getAverageBasis() } }
        );
        hideLoading();

        if (!response.data.success) {
            showToast(response.data.message || 'Failed to load student details', 'danger');
            return;
        }

        currentStudentData = response.data;

        // Refresh picture from server response (more authoritative)
        const serverPic = response.data.student?.picture || picture;
        setStudentImage(imgEl, normalizeImagePath(serverPic, gender), gender);

        const result      = response.data.promotion_result;
        const avg         = response.data.overall_average;
        const allSubjects = response.data.all_subjects        || [];
        const compData    = response.data.compulsory_subjects || [];

        // Overall average
        const avgEl    = document.getElementById('modalOverallAverage');
        const avgValue = avg !== null && avg !== undefined ? `${avg}%` : 'N/A';
        const avgCls   = avg !== null
            ? (avg >= 50 ? 'text-success' : avg >= 40 ? 'text-warning' : 'text-danger')
            : 'text-muted';
        const basisLbl = (response.data.average_basis === 'cum') ? 'Cum Avg' : 'Term Total';
        avgEl.innerHTML = `<span class="${avgCls} fs-5 fw-bold">${avgValue}</span> <small class="text-muted">(${basisLbl})</small>`;

        // ── Student info + class history
        const bio = response.data.student_bio || {};
        const par = response.data.parent_info || {};
        document.getElementById('modalDob').innerText           = bio.dateofbirth || '—';
        document.getElementById('modalAdmissionDate').innerText = bio.admission_date || '—';
        document.getElementById('modalPhone').innerText         = bio.phone_number || '—';
        document.getElementById('modalAddress').innerText       = bio.home_address || '—';
        document.getElementById('modalPosition').innerText      = response.data.position || '—';
        document.getElementById('modalFather').innerText        = [par.father, par.father_phone].filter(Boolean).join(' — ') || '—';
        document.getElementById('modalMother').innerText        = [par.mother, par.mother_phone].filter(Boolean).join(' — ') || '—';
        document.getElementById('modalParentEmail').innerText   = par.parent_email || '—';
        document.getElementById('studentInfoCard').style.display = 'block';

        const history = response.data.class_history || [];
        if (history.length) {
            document.getElementById('classHistoryContent').innerHTML = buildClassHistoryTable(history);
            document.getElementById('classHistoryCard').style.display = 'block';
        }

        // ── Recommendation card
        if (result && result.status !== 'awaiting') {
            const recCard = document.getElementById('recommendationCard');
            recCard.style.display = 'block';
            const statusColors = {
                promoted:      { bg: '#10b981', icon: 'ri-checkbox-circle-line' },
                trial:         { bg: '#f59e0b', icon: 'ri-time-line'            },
                see_principal: { bg: '#3b82f6', icon: 'ri-eye-line'             },
                repeated:      { bg: '#ef4444', icon: 'ri-repeat-line'          },
            };
            const sc = statusColors[result.status] || { bg: '#6b7280', icon: 'ri-question-line' };
            let html = `<div class="recommendation-card ${result.status}">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div class="label text-muted mb-2">System Recommendation</div>
                        <span class="status-badge-lg text-white" style="background:${sc.bg};">
                            <i class="${sc.icon} me-1"></i>${result.status_label || result.status}
                        </span>
                    </div>`;
            if (result.required_average !== null) {
                const metAvg = result.actual_average >= result.required_average;
                html += `<div class="text-end">
                    <div class="rule-badge"><i class="ri-percent-line me-1"></i>Required: ${result.required_average}%</div>
                    <div class="small mt-1 ${metAvg ? 'text-success' : 'text-danger'}">
                        ${metAvg ? '✓' : '✗'} Actual: ${result.actual_average ?? avg ?? 'N/A'}%
                    </div>
                </div>`;
            }
            html += `</div>`;
            if (result.applied_rule) {
                html += `<div class="mt-3 pt-3 border-top d-flex align-items-center flex-wrap gap-2">
                    <i class="ri-price-tag-3-line text-primary"></i>
                    <strong>Matched rule:</strong>
                    <span class="badge bg-primary">${escapeHtml(result.applied_rule.name)}</span>
                    ${result.applied_rule.description ? `<span class="small text-muted">— ${escapeHtml(formatRuleDescription(result.applied_rule.description))}</span>` : ''}
                </div>`;
            } else if (result.rule_logic === 'average_only') {
                html += `<div class="mt-3 pt-3 border-top d-flex align-items-center flex-wrap gap-2">
                    <i class="ri-percent-line text-info"></i>
                    <span class="small text-muted">Determined by Global Minimum Average only — grade-count rules were not evaluated for this class's current Evaluation Mode.</span>
                </div>`;
            }
            if (result.compulsory_count > 0) {
                const allPassed = result.passed_compulsory === result.compulsory_count;
                html += `<div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between">
                        <span><i class="ri-book-open-line me-1"></i>Compulsory subjects:</span>
                        <span class="${allPassed ? 'text-success' : 'text-danger'} fw-bold">
                            ${result.passed_compulsory}/${result.compulsory_count} passed
                        </span>
                    </div>`;
                if (result.failed_compulsory?.length) {
                    html += `<div class="mt-2 small text-danger"><i class="ri-close-circle-line me-1"></i>Failed: `;
                    result.failed_compulsory.forEach(f => {
                        html += `<span class="badge bg-danger me-1">${f.subject || `Subject #${f.subject_id}`}</span>`;
                    });
                    html += `</div>`;
                }
                html += `</div>`;
            }
            html += `</div>`;
            document.getElementById('recommendationContent').innerHTML = html;
        }

        // ── All Subjects table
        if (allSubjects.length) {
            document.getElementById('allSubjectsCard').style.display = 'block';
            document.getElementById('allSubjectsContent').innerHTML = buildSubjectsTable(allSubjects, result);
        }

        // ── Compulsory subjects summary card
        if (compData.length) {
            const passCount  = compData.filter(s => s.pass_status === 'pass').length;
            const failCount  = compData.filter(s => s.pass_status === 'fail').length;
            const nsCount    = compData.filter(s => s.pass_status === 'not_sat').length;
            function gc2(g) {
                if (!g) return '#6b7280'; const u = g.toUpperCase();
                if (['A1','A'].includes(u)) return '#15803d';
                if (['B2','B3','B'].includes(u)) return '#1d4ed8';
                if (['C4','C5','C6','C'].includes(u)) return '#0369a1';
                if (['D7','D'].includes(u)) return '#d97706';
                return '#b91c1c';
            }
            let chtml = `<div class="d-flex gap-2 mb-3 flex-wrap">
                <span class="badge bg-success" style="font-size:13px;padding:6px 12px;"><i class="ri-checkbox-circle-line me-1"></i>${passCount} Passed</span>
                <span class="badge bg-danger"  style="font-size:13px;padding:6px 12px;"><i class="ri-close-circle-line me-1"></i>${failCount} Failed</span>
                ${nsCount ? `<span class="badge bg-secondary" style="font-size:13px;padding:6px 12px;"><i class="ri-minus-line me-1"></i>${nsCount} Not Sat</span>` : ''}
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Subject</th><th>Grade</th><th>Required</th><th>Rule Requirement</th><th>Status</th></tr></thead>
                    <tbody>`;
            compData.forEach(cs => {
                const sc2 = cs.pass_status === 'pass' ? 'success' : (cs.pass_status === 'fail' ? 'danger' : 'secondary');
                const ico = cs.pass_status === 'pass' ? '✓' : (cs.pass_status === 'fail' ? '✗' : '○');
                chtml += `<tr>
                    <td><strong>${escapeHtml(cs.subject)}</strong><br><small class="text-muted">${escapeHtml(cs.subject_code || '')}</small></td>
                    <td><strong style="color:${gc2(cs.student_grade || '')}">${cs.student_grade || 'Not Sat'}</strong></td>
                    <td>${cs.required_min_grade || '—'}</td>
                    <td><small class="text-muted">${cs.rule_requirement || '—'}</small></td>
                    <td><span class="badge bg-${sc2}">${ico} ${cs.pass_status_label || cs.pass_status}</span></td>
                </tr>`;
            });
            chtml += `</tbody></table></div>`;
            document.getElementById('compulsoryContent').innerHTML = chtml;
            document.getElementById('compulsoryCard').style.display = 'block';
        }

    } catch (error) {
        hideLoading();
        console.error('Error fetching student details:', error);
        showToast('Failed to load student details: ' + (error.response?.data?.message || error.message), 'danger');
    }

    new bootstrap.Modal(document.getElementById('promotionModal')).show();
}

/* ── Quick select by System Recommendation ──────────────────────────────────── */
const REC_LABELS = {
    promoted: 'Promoted', trial: 'Promoted on Trial', see_principal: 'See Principal',
    repeated: 'Advice to Repeat', awaiting: 'Awaiting / Not Configured',
};
function normalizeRec(st) {
    st = (st || '').toLowerCase();
    return st === 'repeat' ? 'repeated' : (st || 'awaiting');
}
function rowRec(tr) {
    return normalizeRec(tr.querySelector('[data-rec-status]')?.getAttribute('data-rec-status'));
}
function rowIsDecided(tr) {
    const saved = (tr.querySelector('[data-saved-status]')?.getAttribute('data-saved-status') || '').toLowerCase();
    return saved !== '' && saved !== 'pending';
}

/* Show how many students on this page fall under each recommendation. */
function refreshQuickSelectCounts() {
    const sel = document.getElementById('quickSelectRecommendation');
    if (!sel) return;
    const counts = {};
    document.querySelectorAll('#studentTableBody tr[data-student-id]').forEach(tr => {
        const r = rowRec(tr);
        counts[r] = (counts[r] || 0) + 1;
    });
    [...sel.options].forEach(opt => {
        if (!opt.value) return;
        const n = counts[opt.value] || 0;
        opt.textContent = `${REC_LABELS[opt.value]} (${n})`;
        opt.disabled = n === 0;
    });
}

function selectByRecommendation(additive) {
    const status = document.getElementById('quickSelectRecommendation').value;
    if (!status) { showToast('Choose a recommendation first', 'warning'); return; }
    const undecidedOnly = document.getElementById('quickSelectUndecidedOnly').checked;

    let matched = 0, skippedDecided = 0;
    document.querySelectorAll('#studentTableBody tr[data-student-id]').forEach(tr => {
        const cb = tr.querySelector('.row-checkbox');
        if (!cb) return;
        let isMatch = rowRec(tr) === status;
        if (isMatch && undecidedOnly && rowIsDecided(tr)) { isMatch = false; skippedDecided++; }

        if (isMatch) {
            cb.checked = true;
            matched++;
        } else if (!additive) {
            cb.checked = false;
        }
        tr.classList.toggle('selected', cb.checked);
    });

    const sa = document.getElementById('selectAll');
    if (sa) sa.checked = false;
    updateBulkBar();

    const extra = skippedDecided ? ` (${skippedDecided} already decided, skipped)` : '';
    showToast(matched
        ? `${matched} ${REC_LABELS[status]} student(s) ${additive ? 'added' : 'selected'}${extra}`
        : `No undecided students match that recommendation${extra}`,
        matched ? 'success' : 'info');
}

/* If every selected student shares one recommendation, pre-pick the matching
   decision in the Bulk Promote modal (admin can still change it). */
function presetBulkDecisionFromSelection() {
    const recs = new Set();
    document.querySelectorAll('.row-checkbox:checked').forEach(cb => recs.add(rowRec(cb.closest('tr'))));
    if (recs.size !== 1) return;
    const map = { promoted: 'promoted', trial: 'trial', see_principal: 'see_principal', repeated: 'repeat' };
    const val = map[[...recs][0]];
    const sel = document.getElementById('bulkPromotionType');
    if (val && sel) sel.value = val;
}

/* ── Average basis / class history / advance term / print list ─────────────── */
function getAverageBasis() {
    return document.getElementById('average_basis')?.value || 'total';
}

function buildClassHistoryTable(history) {
    const colors = {
        PROMOTED: '#10b981', TRIAL: '#f59e0b', SEE_PRINCIPAL: '#3b82f6',
        REPEAT: '#ef4444', ADVANCED: '#6366f1', PARENTS_TO_SEE_PRINCIPAL: '#6b7280',
    };
    const badge = (st) => {
        if (!st) return '<span class="badge bg-secondary">—</span>';
        const label = st.charAt(0) + st.slice(1).toLowerCase().replace(/_/g, ' ');
        return `<span class="badge" style="background:${colors[st] || '#6b7280'};color:#fff;">${escapeHtml(label)}</span>`;
    };
    let html = `<div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Session</th><th>Term</th><th>Class</th><th>Arm</th><th>Decision</th></tr></thead><tbody>`;
    history.forEach(h => {
        html += `<tr>
            <td>${escapeHtml(h.session || '—')}</td>
            <td>${escapeHtml(h.term || '—')}</td>
            <td>${escapeHtml(h.class || '—')}</td>
            <td>${escapeHtml(h.arm || '—')}</td>
            <td>${badge(h.promotion_status)}</td>
        </tr>`;
    });
    return html + '</tbody></table></div>';
}

function isLastTermSelected() {
    const sel = document.getElementById('idterm');
    if (!sel || !sel.value) return false;
    const ids = [...sel.options].map(o => parseInt(o.value, 10)).filter(n => !isNaN(n));
    return parseInt(sel.value, 10) >= Math.max(...ids);
}
function updateAdvanceTermButtonVisibility() {
    const btn = document.getElementById('advanceTermActionBtn');
    if (btn) btn.style.display = isLastTermSelected() ? 'none' : '';
}

function submitAdvanceTerm() {
    const ids  = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    const cls  = document.getElementById('idclass').value;
    const sess = document.getElementById('idsession').value;
    const term = document.getElementById('idterm').value;
    if (!ids.length) { showToast('No students selected', 'warning'); return; }
    if (cls === 'ALL' || sess === 'ALL' || !term) { showToast('Select class, session and term first', 'warning'); return; }

    Swal.fire({
        title: 'Advance to Next Term',
        text: `Move ${ids.length} student(s) to the next term in the same class and session?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Advance',
    }).then(async result => {
        if (!result.isConfirmed) return;
        showLoading('Advancing term…');
        try {
            const res = await axios.post('{{ route("promotions.advance-term") }}', {
                student_ids: ids, schoolclassid: cls, sessionid: sess, current_termid: term,
                _token: document.querySelector('meta[name="csrf-token"]').content,
            });
            hideLoading();
            showToast(res.data.message, res.data.success ? 'success' : 'danger');
            if (res.data.success) { clearSelection(); filterData(); offerUndo(res.data.batch_id, res.data.message); }
        } catch (err) {
            hideLoading();
            showToast(err.response?.data?.message || 'Advance term failed', 'danger');
        }
    });
}

function openPrintListDialog() {
    const cls  = document.getElementById('idclass').value;
    const sess = document.getElementById('idsession').value;
    const term = document.getElementById('idterm').value;
    if (sess === 'ALL' || !term) { showToast('Select a session and term first', 'warning'); return; }
    const hasClass = cls && cls !== 'ALL';

    Swal.fire({
        title: 'Print Student List',
        html: `<div style="text-align:left">
            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px">Scope</label>
            <select id="printScopeSelect" class="form-select form-select-sm">
                <option value="class"${hasClass ? '' : ' disabled'}>This class / arm only</option>
                <option value="class_wide"${hasClass ? '' : ' disabled'}>All arms of this class</option>
                <option value="school"${hasClass ? '' : ' selected'}>Whole school</option>
            </select>
            <label style="font-size:13px;font-weight:600;display:block;margin:12px 0 6px">Format</label>
            <select id="printFormatSelect" class="form-select form-select-sm">
                <option value="compact">Compact — plain print sheet</option>
                <option value="detailed">Detailed — school header, photos, summary</option>
            </select>
            <small class="text-muted d-block mt-2">Grouped by System Recommendation. Paper size and orientation are set on the print page.</small>
        </div>`,
        showCancelButton: true,
        confirmButtonText: 'Open Print View',
        didOpen: () => {
            try {
                const f = localStorage.getItem('promoListFormat');
                if (f) document.getElementById('printFormatSelect').value = f;
            } catch (e) { /* ignore */ }
        },
        preConfirm: () => ({
            scope:  document.getElementById('printScopeSelect').value,
            format: document.getElementById('printFormatSelect').value,
        }),
    }).then(result => {
        if (!result.isConfirmed) return;
        const { scope, format } = result.value;
        try { localStorage.setItem('promoListFormat', format); } catch (e) { /* ignore */ }
        const params = new URLSearchParams({
            scope, format, sessionid: sess, termid: term, average_basis: getAverageBasis(),
        });
        if (scope !== 'school') params.set('schoolclassid', cls);
        window.open(`{{ route("promotions.student-list") }}?${params.toString()}`, '_blank');
    });
}

/* ── Undo / history / clear decision ───────────────────────────────────────── */
const CSRF_TOKEN = () => document.querySelector('meta[name="csrf-token"]').content;

/* Short "Undo" prompt right after an action, so a wrong click is one tap away. */
function offerUndo(batchId, message) {
    if (!batchId || typeof Swal === 'undefined') return;
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: 'success',
        title: message || 'Done',
        showConfirmButton: true,
        confirmButtonText: 'Undo',
        showCloseButton: true,
        timer: 10000,
        timerProgressBar: true,
    }).then(r => { if (r.isConfirmed) revertAction(batchId, null, false); });
}

async function revertAction(batchId, studentId, force) {
    showLoading('Reverting…');
    try {
        const res = await axios.post(`/promotions/history/${encodeURIComponent(batchId)}/revert`, {
            student_id: studentId || null, force: !!force, _token: CSRF_TOKEN(),
        });
        hideLoading();
        const d = res.data || {};
        if (d.blocked?.length) {
            const list = d.blocked.map(b => `<li>${escapeHtml(b)}</li>`).join('');
            const r = await Swal.fire({
                icon: d.reverted ? 'warning' : 'error',
                title: d.message,
                html: `<ul style="text-align:left;font-size:13px;margin:0;padding-left:18px">${list}</ul>`
                    + (d.can_force ? '<p class="small text-muted mt-2 mb-0">Revert anyway overwrites those later changes with the state from before this action.</p>' : ''),
                showCancelButton: !!d.can_force,
                confirmButtonText: d.can_force ? 'Revert anyway' : 'OK',
                confirmButtonColor: d.can_force ? '#dc2626' : undefined,
            });
            if (d.can_force && r.isConfirmed) return revertAction(batchId, studentId, true);
        } else {
            showToast(d.message || 'Reverted', d.success ? 'success' : 'danger');
        }
        filterData();
        if (document.getElementById('historyModal')?.classList.contains('show')) loadHistory();
    } catch (err) {
        hideLoading();
        showToast(err.response?.data?.message || 'Revert failed', 'danger');
    }
}

function confirmRevert(batchId, studentId, label) {
    Swal.fire({
        title: studentId ? 'Revert for this student?' : 'Revert this whole action?',
        html: `<div style="font-size:13px">${escapeHtml(label)}</div>
               <div class="small text-muted mt-2">Class placement, saved decision and current term go back to how they were before.</div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, revert',
        confirmButtonColor: '#dc2626',
    }).then(r => { if (r.isConfirmed) revertAction(batchId, studentId, false); });
}

function openHistoryModal() {
    new bootstrap.Modal(document.getElementById('historyModal')).show();
    loadHistory();
}

async function loadHistory() {
    const box  = document.getElementById('historyList');
    const sess = document.getElementById('idsession').value;
    const onlySess = document.getElementById('historyThisSessionOnly').checked && sess && sess !== 'ALL';
    box.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>';
    try {
        const res = await axios.get('{{ route("promotions.history") }}', { params: onlySess ? { sessionid: sess } : {} });
        const batches = res.data.batches || [];
        if (!batches.length) {
            box.innerHTML = '<div class="text-center text-muted py-4"><i class="ri-inbox-line d-block fs-3 mb-2"></i>No promotion actions recorded yet.</div>';
            return;
        }
        box.innerHTML = batches.map((b, i) => {
            const state = b.fully_reverted
                ? '<span class="badge bg-secondary">Reverted</span>'
                : (b.active < b.total ? `<span class="badge bg-warning text-dark">${b.total - b.active} of ${b.total} reverted</span>` : '');
            const students = b.students.map(st => `
                <div class="d-flex align-items-center justify-content-between py-1 border-bottom" style="font-size:12.5px">
                    <span class="${st.reverted ? 'text-muted text-decoration-line-through' : ''}">
                        ${escapeHtml(st.admissionno)} — ${escapeHtml(st.name)}
                        ${st.reverted ? `<small class="ms-1">(reverted ${escapeHtml(st.reverted_at || '')})</small>` : ''}
                    </span>
                    ${st.reverted ? '' : `<button type="button" class="btn btn-link btn-sm text-danger p-0"
                        onclick="confirmRevert('${b.batch_id}', ${st.student_id}, ${JSON.stringify(st.name).replace(/"/g, '&quot;')})">Revert</button>`}
                </div>`).join('');
            return `
            <div class="border rounded-3 p-3 mb-2 ${b.fully_reverted ? 'bg-light' : ''}">
                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                    <div>
                        <div class="fw-semibold">${escapeHtml(b.action_label)} ${state}</div>
                        <div class="small">${escapeHtml(b.description || '')}</div>
                        <div class="small text-muted">${escapeHtml(b.performed_at || '')} · by ${escapeHtml(b.performed_by)} · ${b.total} student(s)</div>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#histStudents${i}">
                            <i class="ri-group-line me-1"></i>Students
                        </button>
                        ${b.fully_reverted ? '' : `<button type="button" class="btn btn-outline-danger btn-sm"
                            onclick="confirmRevert('${b.batch_id}', null, ${JSON.stringify(b.description || b.action_label).replace(/"/g, '&quot;')})">
                            <i class="ri-arrow-go-back-line me-1"></i>Revert${b.active < b.total ? ' rest' : ''}</button>`}
                    </div>
                </div>
                <div class="collapse mt-2" id="histStudents${i}">${students}</div>
            </div>`;
        }).join('');
    } catch (err) {
        box.innerHTML = `<div class="alert alert-danger mb-0">Failed to load history: ${escapeHtml(err.response?.data?.message || err.message)}</div>`;
    }
}

function clearDecision(studentId, schoolclassId, sessionId, termId, name) {
    Swal.fire({
        title: 'Clear saved decision?',
        text: `Remove the saved promotion decision for ${name} for this term? Their class placement is not changed. You can undo this.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear it',
    }).then(async r => {
        if (!r.isConfirmed) return;
        showLoading('Clearing decision…');
        try {
            const res = await axios.post(`/promotions/${studentId}/clear-decision`, {
                schoolclassid: schoolclassId, sessionid: sessionId, termid: termId, _token: CSRF_TOKEN(),
            });
            hideLoading();
            showToast(res.data.message, res.data.success ? 'success' : 'danger');
            if (res.data.success) { filterData(); offerUndo(res.data.batch_id, res.data.message); }
        } catch (err) {
            hideLoading();
            showToast(err.response?.data?.message || 'Failed to clear decision', 'danger');
        }
    });
}

/* ── Remove student ─────────────────────────────────────────────────────────── */
function removeStudent(studentId, schoolclassId, sessionId, termId, admissionNo, firstName, lastName) {
    Swal.fire({
        title: 'Confirm Removal',
        text: `Remove ${admissionNo} - ${firstName} ${lastName} from this class?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Remove',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (!result.isConfirmed) return;
        showLoading('Removing student...');
        const fd = new FormData();
        fd.append('_method', 'DELETE');
        fd.append('schoolclassid', schoolclassId);
        fd.append('sessionid', sessionId);
        fd.append('termid', termId);
        axios.post(`/promotions/${studentId}`, fd, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'multipart/form-data' }
        }).then(response => {
            hideLoading();
            showToast(response.data.success ? response.data.message : (response.data.message || 'Failed to remove'), response.data.success ? 'success' : 'danger');
            if (response.data.success) { filterData(); offerUndo(response.data.batch_id, response.data.message); }
        }).catch(error => {
            hideLoading();
            showToast(error.response?.data?.message || 'Failed to remove student', 'danger');
        });
    });
}

/* ── Submit promotion ───────────────────────────────────────────────────────── */
function submitPromotion() {
    if (!currentStudentId) { showToast('Student ID not found', 'danger'); return; }

    const nc = document.getElementById('newClassSelect');
    const ns = document.getElementById('newSessionSelect');
    const nt = document.getElementById('newTermSelect');
    if (!nc.value)  { showToast('Please select a new class',   'warning'); return; }
    if (!ns.value)  { showToast('Please select a new session', 'warning'); return; }
    if (!nt.value)  { showToast('Please select a new term',    'warning'); return; }

    const cbs = ['promotionCheckbox','trialCheckbox','seePrincipalCheckbox','repeatCheckbox']
        .map(id => document.getElementById(id));
    if (cbs.filter(cb => cb.checked).length !== 1) {
        showToast('Please select exactly one promotion decision', 'warning'); return;
    }

    const fd = new FormData();
    fd.append('_method',           'PUT');
    fd.append('new_schoolclassid', nc.value);
    fd.append('new_sessionid',     ns.value);
    fd.append('new_termid',        nt.value);
    fd.append('promotion',         document.getElementById('promotionCheckbox').checked    ? '1' : '0');
    fd.append('trial',             document.getElementById('trialCheckbox').checked        ? '1' : '0');
    fd.append('see_principal',     document.getElementById('seePrincipalCheckbox').checked ? '1' : '0');
    fd.append('repeat',            document.getElementById('repeatCheckbox').checked       ? '1' : '0');

    Swal.fire({
        title: 'Confirm Update',
        text: "Update this student's promotion?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Update',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (!result.isConfirmed) return;
        showLoading('Updating promotion...');
        bootstrap.Modal.getInstance(document.getElementById('promotionModal'))?.hide();
        axios.post(`/promotions/${currentStudentId}`, fd, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        }).then(response => {
            hideLoading();
            showToast(response.data.success ? response.data.message : (response.data.message || 'Failed to update'), response.data.success ? 'success' : 'danger');
            if (response.data.success) { filterData(); offerUndo(response.data.batch_id, response.data.message); }
        }).catch(error => {
            hideLoading();
            showToast(error.response?.data?.message || 'Failed to update promotion', 'danger');
        });
    });
}

/* ── Keyboard shortcuts ─────────────────────────────────────────────────────── */
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput')?.focus();
        showToast('Search focused — type to filter students', 'info');
    }
    if (e.key === 'Escape') {
        const si = document.getElementById('searchInput');
        if (si && document.activeElement === si) {
            si.value = ''; filterData();
            showToast('Search cleared', 'info');
        }
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
        const ae = document.activeElement;
        if (ae && !['INPUT','TEXTAREA'].includes(ae.tagName)) {
            e.preventDefault();
            const sa = document.getElementById('selectAll');
            if (sa) {
                sa.checked = true;
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = true;
                    cb.closest('tr')?.classList.add('selected');
                });
                updateBulkBar();
                showToast('All students selected', 'info');
            }
        }
    }
});

/* ── DOMContentLoaded ───────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('idclass').addEventListener('change', filterData);
    document.getElementById('idsession').addEventListener('change', filterData);
    document.getElementById('idterm').addEventListener('change', function () {
        filterData();
        updateAdvanceTermButtonVisibility();
    });
    document.getElementById('average_basis').addEventListener('change', filterData);
    updateAdvanceTermButtonVisibility();

    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterData, 500);
    });

    setupCheckboxHandlers();
    updateStats();
    triggerRowEntrance();
    popPromotionBadges();
    setupRowSelection();

    // Pulse bulk button when bar is visible
    const bulkBtn = document.getElementById('bulkPromoteActionBtn');
    const bulkBar = document.getElementById('bulkActionBar');
    if (bulkBtn && bulkBar) {
        new MutationObserver(() => {
            bulkBtn.classList.toggle('btn-pulse', bulkBar.classList.contains('visible'));
        }).observe(bulkBar, { attributes: true });
    }
});
</script>
@endsection