{{-- resources/views/timetable/index.blade.php --}}
@extends('layouts.master')

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

<style>
/* ── Design tokens ────────────────────────────────── */
:root {
    --tt-blue:     #1565C0;
    --tt-purple:   #6A1B9A;
    --tt-green:    #1B5E20;
    --tt-orange:   #E65100;
    --tt-pink:     #880E4F;
    --tt-surface:  #F8FAFC;
    --tt-border:   #E2E8F0;
    --tt-radius:   12px;
    --tt-shadow:   0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}

.timetable-container * { box-sizing: border-box; }

@keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes popIn {
    0%   { opacity: 0; transform: scale(0.92); }
    60%  { opacity: 1; transform: scale(1.02); }
    100% { opacity: 1; transform: scale(1); }
}

.timetable-container .btn { transition: transform 0.12s ease, box-shadow 0.12s ease; }
.timetable-container .btn:active { transform: scale(0.96); }

/* ── Page header ──────────────────────────────────── */
.tt-page-header {
    background: linear-gradient(135deg, #1565C0 0%, #6A1B9A 100%);
    border-radius: 16px; padding: 24px 28px; color: #fff; margin-bottom: 24px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    animation: fadeSlideUp 0.45s ease both;
}
.tt-page-header h4 { color: #fff; margin: 0; font-size: 20px; font-weight: 700; }
.tt-page-header p  { color: rgba(255,255,255,.75); margin: 4px 0 0; font-size: 13px; }
.tt-page-header .btn { border-color: rgba(255,255,255,.3); color: #fff; }
.tt-page-header .btn:hover { background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.5); }

/* ── Cards ────────────────────────────────────────── */
.tt-card {
    background: #fff; border: 1px solid var(--tt-border); border-radius: var(--tt-radius);
    box-shadow: var(--tt-shadow); overflow: hidden;
    animation: fadeSlideUp 0.45s ease both; animation-delay: .05s;
}
.tt-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--tt-border);
    background: var(--tt-surface); flex-wrap: wrap; gap: 8px;
}
.tt-card-header h6 { margin: 0; font-size: 14px; font-weight: 600; color: #1E293B; }
.tt-card-body { padding: 20px; }

/* ── Setting cards ───────────────────────────────── */
.setting-card {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border: 1px solid var(--tt-border); border-radius: 10px;
    background: #fff; transition: all 0.18s ease; margin-bottom: 10px; cursor: pointer;
    animation: fadeSlideUp 0.35s ease both;
}
.setting-card:nth-child(1) { animation-delay: .02s; }
.setting-card:nth-child(2) { animation-delay: .06s; }
.setting-card:nth-child(3) { animation-delay: .10s; }
.setting-card:nth-child(4) { animation-delay: .14s; }
.setting-card:nth-child(5) { animation-delay: .18s; }
.setting-card:nth-child(n+6) { animation-delay: .20s; }
.setting-card:hover { border-color: var(--tt-blue); box-shadow: 0 0 0 3px rgba(21,101,192,.08); transform: translateY(-1px); }
.setting-card:active { transform: scale(0.99); }
.setting-card:last-child { margin-bottom: 0; }
.setting-card .sc-select { display: flex; align-items: center; margin-right: 12px; flex-shrink: 0; }
.setting-card .sc-select input { width: 18px; height: 18px; cursor: pointer; }
.setting-card .sc-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: linear-gradient(135deg, #E3F2FD, #EDE7F6);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.setting-card .sc-icon i { font-size: 20px; color: var(--tt-blue); }
.setting-card .sc-body { flex: 1; margin: 0 14px; min-width: 0; }
.setting-card .sc-body .sc-title { font-size: 14px; font-weight: 600; color: #1E293B; margin-bottom: 2px; }
.setting-card .sc-body .sc-meta  { font-size: 12px; color: #64748B; word-break: break-word; }
.setting-card .sc-actions { display: flex; gap: 6px; flex-shrink: 0; }
.setting-card.is-selected { border-color: var(--tt-blue); background: rgba(21,101,192,.04); box-shadow: 0 0 0 3px rgba(21,101,192,.1); }

/* ── Tabs ─────────────────────────────────────────── */
.tt-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--tt-border); margin-bottom: 24px; overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
.tt-tab {
    padding: 10px 18px; font-size: 13px; font-weight: 500; color: #64748B; cursor: pointer;
    border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.15s;
    white-space: nowrap; display: flex; align-items: center; gap: 6px;
    text-decoration: none; background: none; border-top: none; border-left: none; border-right: none; flex-shrink: 0;
}
.tt-tab:hover { color: var(--tt-blue); background: rgba(21,101,192,.04); }
.tt-tab.active { color: var(--tt-blue); border-bottom-color: var(--tt-blue); font-weight: 600; }
.tt-tab .tab-badge { font-size: 10px; padding: 1px 6px; background: #EF4444; color: #fff; border-radius: 10px; font-weight: 600; }
.tab-content-pane { animation: fadeIn 0.25s ease; }

/* ── Timetable grid ───────────────────────────────── */
.tt-grid-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.tt-grid { width: 100%; border-collapse: collapse; min-width: 700px; animation: fadeIn 0.3s ease; }
.tt-grid th { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 12px 10px; text-align: center; white-space: nowrap; }
.tt-grid th.period-th { background: #1E293B; color: #fff; width: 100px; text-align: left; padding-left: 14px; }
.tt-grid th.monday-th    { background: var(--tt-blue);   color: #fff; }
.tt-grid th.tuesday-th   { background: var(--tt-purple); color: #fff; }
.tt-grid th.wednesday-th { background: var(--tt-green);  color: #fff; }
.tt-grid th.thursday-th  { background: var(--tt-orange); color: #fff; }
.tt-grid th.friday-th    { background: var(--tt-pink);   color: #fff; }
.tt-grid th.holiday-th   { background: #b91c1c !important; }
.tt-grid th .holiday-badge { display: block; font-size: 9px; font-weight: 700; text-transform: none; letter-spacing: 0; margin-top: 2px; opacity: .95; }
.tt-grid td.holiday-col { background: rgba(239, 68, 68, 0.07); }
.tt-grid td { border: 1px solid var(--tt-border); vertical-align: middle; padding: 0; transition: all 0.15s; }
.tt-grid td.period-td { background: var(--tt-surface); padding: 10px 14px; min-width: 100px; }
.tt-grid .period-td .pname { font-size: 12px; font-weight: 700; color: #1E293B; }
.tt-grid .period-td .ptime { font-size: 11px; color: #94A3B8; margin-top: 2px; }

/* ── Grid cells ───────────────────────────────────── */
.tt-cell {
    cursor: pointer; padding: 8px; min-height: 68px;
    display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
    transition: opacity 0.28s ease, transform 0.15s cubic-bezier(.34,1.56,.64,1), background 0.15s ease;
    min-width: 80px;
}
.tt-cell:hover { background: rgba(21,101,192,.06) !important; }
.tt-cell:active { transform: scale(0.94); }
.tt-cell.is-free { background: #FAFAFA; }
.tt-cell.is-double { background: rgba(21,101,192,.05); }
.tt-cell.is-break { background: #FFFBEB; cursor: default; }
.tt-cell.is-break:hover { background: #FFFBEB !important; }
.tt-cell.is-break:active { transform: none; }
.tt-cell .cell-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,.8); box-shadow: 0 2px 6px rgba(0,0,0,.15); margin-bottom: 5px; }
.tt-cell .cell-avatar-placeholder { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #E3F2FD, #EDE7F6); display: flex; align-items: center; justify-content: center; margin-bottom: 5px; }
.tt-cell .cell-avatar-placeholder i { font-size: 16px; color: var(--tt-blue); }
.tt-cell .cell-subject { font-size: 11px; font-weight: 700; color: #1E293B; line-height: 1.3; }
.tt-cell .cell-teacher { font-size: 10px; color: #64748B; margin-top: 1px; }
.tt-cell .cell-room    { font-size: 10px; color: #94A3B8; margin-top: 1px; }
.tt-cell .cell-room i  { font-size: 9px; margin-right: 2px; }
.tt-cell .cell-free    { font-size: 11px; color: #CBD5E1; }
.tt-cell .cell-break   { font-size: 11px; color: #D97706; font-weight: 600; }
.tt-cell .cell-double-badge { font-size: 9px; padding: 1px 5px; background: rgba(21,101,192,.12); color: var(--tt-blue); border-radius: 4px; font-weight: 700; margin-top: 3px; }
.tt-cell.has-subject { border-left: 3px solid; }
.tt-cell.cell-building { opacity: 0; transform: scale(0.75); }
.tt-cell.is-na { background: repeating-linear-gradient(45deg, #F8FAFC, #F8FAFC 6px, #EEF2F6 6px, #EEF2F6 12px); cursor: default; }
.tt-cell.is-na:hover { background: repeating-linear-gradient(45deg, #F8FAFC, #F8FAFC 6px, #EEF2F6 6px, #EEF2F6 12px) !important; }
.tt-cell.is-na:active { transform: none; }
.tt-cell .cell-na { font-size: 10px; color: #94A3B8; font-weight: 600; }
.tt-generating-banner {
    display: flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg,#EFF6FF,#F5F3FF);
    border: 1px solid #BFDBFE; border-radius: 10px; padding: 10px 16px; margin: 0 0 12px;
    font-size: 13px; color: #1565C0; font-weight: 600; animation: fadeSlideUp 0.25s ease both;
}
.tt-generating-banner .spinner-border { width: 16px; height: 16px; border-width: 2px; }
.tt-generating-skip { margin-left: auto; font-size: 12px; font-weight: 600; color: #64748B; cursor: pointer; text-decoration: underline; }

.hide-avatars .cell-avatar,
.hide-avatars .cell-avatar-placeholder { display: none !important; }

/* ── Constraints table ────────────────────────────── */
#constraintsTable { font-size: 13px; }
#constraintsTable td { vertical-align: middle; padding: 8px 6px; }
#constraintsTable input[type="number"] { width: 70px; }
#constraintsTable select[multiple] { min-height: 50px; font-size: 12px; }

/* ── Conflict items ───────────────────────────────── */
.conflict-item { border: 1px solid #FEE2E2; background: #FFF5F5; border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; display: flex; align-items: flex-start; gap: 14px; animation: fadeSlideUp 0.3s ease both; }
.conflict-item.room-conflict { border-color: #FED7AA; background: #FFF7ED; }
.conflict-item:last-child { margin-bottom: 0; }
.conflict-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.conflict-avatar-ph { width: 44px; height: 44px; border-radius: 50%; background: #FEE2E2; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.conflict-avatar-ph.room { background: #FED7AA; }
.conflict-avatar-ph i { color: #EF4444; }
.conflict-avatar-ph.room i { color: #EA580C; }

/* ── Real-time conflict panel ─────── */
.rtc-panel { border-radius: 10px; padding: 12px 14px; margin-bottom: 8px; display: flex; align-items: flex-start; gap: 10px; font-size: 12px; animation: rtcSlideIn 0.2s ease; }
.rtc-panel:last-child { margin-bottom: 0; }
@keyframes rtcSlideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
.rtc-error   { background: #FFF1F2; border: 1px solid #FECDD3; }
.rtc-warning { background: #FFFBEB; border: 1px solid #FDE68A; }
.rtc-clear   { background: #F0FDF4; border: 1px solid #BBF7D0; }
.rtc-icon    { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.rtc-body    { flex: 1; min-width: 0; }
.rtc-msg     { font-weight: 600; color: #1E293B; margin-bottom: 4px; line-height: 1.4; }
.rtc-msg.green { color: #15803d; }
.rtc-detail  { color: #64748B; font-size: 11px; margin-bottom: 6px; }
.rtc-alts    { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
.rtc-alt-badge { font-size: 10px; padding: 3px 8px; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 6px; cursor: pointer; transition: all .15s; white-space: nowrap; }
.rtc-alt-badge:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
.rtc-room-alt { font-size: 10px; padding: 3px 8px; background: #EFF6FF; color: #1565C0; border: 1px solid #BFDBFE; border-radius: 6px; cursor: pointer; transition: all .15s; white-space: nowrap; }
.rtc-room-alt:hover { background: #1565C0; color: #fff; }
.rtc-spinner { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #64748B; padding: 10px 0; }
.rtc-spinner .spinner-border { width: 14px; height: 14px; border-width: 2px; }

/* ── Conflict suggestion box ─────── */
.conflict-suggestion { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 8px 12px; font-size: 12px; margin-top: 8px; }
.conflict-suggestion .alt-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
.alt-badge { font-size: 11px; padding: 4px 8px; background: #dcfce7; color: #15803d; border-radius: 6px; cursor: pointer; border: 1px solid #bbf7d0; transition: all .15s; }
.alt-badge:hover { background: #16a34a; color: #fff; }

.export-group { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

/* ── Tom Select overrides ─────── */
.ts-wrapper .ts-control { border-color: #D1D5DB; border-radius: 6px; min-height: 38px; font-size: 14px; }
.ts-wrapper.focus .ts-control { border-color: #1565C0; box-shadow: 0 0 0 3px rgba(21,101,192,.12); }
.ts-dropdown { font-size: 13px; }
.ts-dropdown .option { padding: 8px 12px; }
.ts-dropdown .option:hover,
.ts-dropdown .option.active { background: #EFF6FF; color: #1565C0; }

#editingBanner { border: 1px solid #FDE68A; background: #FFFBEB; color: #92400E; border-radius: 8px; padding: 10px 16px; }

#periodsTable .form-control-sm,
#periodsTable .form-select-sm { font-size: 13px; padding: 4px 8px; }
#periodsTable td { padding: 6px 4px; vertical-align: middle; }
#periodsTable .period-order { font-size: 13px; font-weight: 600; color: #94A3B8; }
#periodsTable tr { animation: fadeIn 0.2s ease; }

.modal-content { border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.18); animation: popIn 0.22s ease; }
.modal-header.bg-gradient-primary { background: linear-gradient(135deg, #1565C0, #6A1B9A); }
.modal-header .modal-title { color: #fff; }
.modal-header .btn-close-white { filter: brightness(0) invert(1); }

.wiz-half-day-row { background: var(--tt-surface); padding: 8px 12px; border-radius: 8px; border: 1px solid var(--tt-border); animation: fadeSlideUp 0.2s ease both; }

#teacherAssignmentContainer table tr { animation: fadeIn 0.2s ease; }
.assignment-teacher-select { min-width: 220px; }
.ta-row-status { font-size: 15px; }

.ws-mode-toggle { display: flex; gap: 8px; margin-bottom: 14px; }
.ws-mode-btn { flex: 1; border: 1.5px solid var(--tt-border); background: #fff; border-radius: 10px; padding: 10px 12px; font-size: 12.5px; font-weight: 600; color: #64748B; cursor: pointer; text-align: left; transition: all .15s; }
.ws-mode-btn i { font-size: 16px; display:block; margin-bottom: 4px; color: var(--tt-blue); }
.ws-mode-btn.active { border-color: var(--tt-blue); background: rgba(21,101,192,.06); color: var(--tt-blue); box-shadow: 0 0 0 3px rgba(21,101,192,.1); }
.ws-mode-btn small { display:block; font-weight:400; color:#94A3B8; margin-top:2px; }

.cursor-pointer { cursor: pointer; }
.flex-1 { flex: 1; }
.opacity-30 { opacity: 0.3; }
.opacity-50 { opacity: 0.5; }
.bg-success-subtle { background: #DCFCE7; }
.text-success { color: #15803d; }
.bg-warning-subtle { background: #FEF3C7; }
.text-warning { color: #D97706; }
.bg-primary-subtle { background: #EFF6FF; }
.text-primary { color: #1565C0; }
.bg-info-subtle { background: #E0F2FE; }
.text-info { color: #0369a1; }
.bg-danger-subtle { background: #FEE2E2; }
.text-danger { color: #DC2626; }

/* ── Wizard: Subjects & Priority ──────────────────── */
.wiz-class-card { border: 1px solid var(--tt-border); border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
.wiz-class-card .wiz-class-hdr {
    background: linear-gradient(135deg, #1565C0, #0d9488);
    color: #fff; padding: 10px 14px;
    display: flex; justify-content: space-between; align-items: center;
    cursor: pointer;
}
.wiz-class-card .wiz-class-hdr h6 { margin: 0; font-size: 13px; font-weight: 700; }
.wiz-class-card .wiz-class-body { padding: 10px 14px; }

.wiz-subj-row {
    display: grid;
    grid-template-columns: 22px minmax(0, 1.4fr) 80px 60px 60px minmax(0, 1fr) minmax(0, 1.2fr);
    gap: 8px; align-items: center;
    padding: 8px 4px;
    border-bottom: 1px solid #F1F5F9;
    font-size: 12.5px;
}
.wiz-subj-row:last-child { border-bottom: none; }
.wiz-subj-row.is-compulsory { border-left: 3px solid #F59E0B; padding-left: 10px; background: #FFFBEB; }
.wiz-subj-name { font-weight: 600; color: #1E293B; }
.wiz-subj-teacher { color: #64748B; font-size: 11.5px; }
.wiz-subj-num { width: 100%; }

/* ── Period Allocation modal ──────────────────────── */
.pa-subj-row {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) 90px 90px 70px;
    gap: 8px; align-items: center;
    padding: 8px 4px;
    border-bottom: 1px solid #F1F5F9;
    font-size: 12.5px;
}
.pa-subj-row:last-child { border-bottom: none; }
.pa-subj-name { font-weight: 600; color: #1E293B; }
.pa-subj-teacher { color: #64748B; font-size: 11.5px; }
.pa-subj-num { width: 100%; }

.pa-class-summary {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    padding: 8px 14px; background: #F8FAFC; border-bottom: 1px solid #F1F5F9;
}
.pa-class-summary-input { display: flex; align-items: center; gap: 6px; }
.pa-class-summary-input label { font-size: 11px; color: #64748B; font-weight: 600; white-space: nowrap; }
.pa-class-summary-input input { width: 72px; }
.pa-class-summary-badge {
    font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 20px;
    display: inline-flex; align-items: center; gap: 4px;
}
.pa-class-summary-badge.neutral { background: #F1F5F9; color: #64748B; font-weight: 500; }
.pa-class-summary-badge.ok      { background: #DCFCE7; color: #15803D; }
.pa-class-summary-badge.exact   { background: #DBEAFE; color: #1D4ED8; }
.pa-class-summary-badge.over    { background: #FEE2E2; color: #B91C1C; }

.wiz-class-card.pa-class-over { border-color: #DC2626; }
.wiz-class-card.pa-class-over .wiz-class-hdr { background: linear-gradient(135deg, #DC2626, #B91C1C); }
.pa-class-over-pill { font-size: 10px; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; }

.pa-subj-row-pending, .wiz-subj-row.wiz-subj-row-pending {
    border-left: 3px solid #F59E0B; padding-left: 10px; background: #FFFBEB;
}
.pa-pending-badge {
    display: inline-flex; align-items: center;
    background: #FEF3C7; color: #92400E;
    font-size: 9.5px; font-weight: 700; padding: 1px 6px; border-radius: 8px;
    margin-left: 6px; vertical-align: middle;
}
.pa-pending-divider {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 600; color: #92400E;
    margin: 10px 0 4px; padding-top: 8px; border-top: 1px dashed #FDE68A;
}
.pa-pending-include, .wiz-pending-include { margin-right: 6px; }

.pa-set-row {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1px solid var(--tt-border); border-radius: 10px;
    padding: 8px 10px; cursor: pointer; transition: all .15s;
}
.pa-set-row:hover { background: #F8FAFC; border-color: #CBD5E1; }
.pa-set-row.active {
    background: linear-gradient(135deg, rgba(13,148,136,.08), rgba(21,101,192,.08));
    border-color: #0d9488;
}
.pa-set-row-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: #F1F5F9; color: #0d9488; font-size: 15px;
}
.pa-set-row.active .pa-set-row-icon {
    background: linear-gradient(135deg, #0d9488, #1565C0); color: #fff;
}
.pa-set-row-body { flex: 1; min-width: 0; }
.pa-set-row-name { font-weight: 700; color: #1E293B; font-size: 13.5px; }
.pa-set-row-desc {
    color: #64748B; font-size: 11.5px; margin-top: 1px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.pa-set-row-meta { color: #94A3B8; font-size: 11px; margin-top: 2px; }
.pa-set-row-actions { display: flex; gap: 4px; flex-shrink: 0; }
.pa-set-row-actions .btn { padding: 4px 8px; font-size: 13px; line-height: 1; }

.wiz-saved-allocation-box {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    background: linear-gradient(135deg, rgba(13,148,136,.06), rgba(21,101,192,.06));
    border: 1px solid rgba(13,148,136,.25); border-radius: 10px;
    padding: 10px 12px; margin-bottom: 12px;
}
.wiz-saved-allocation-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #0d9488, #1565C0); color: #fff; font-size: 15px;
}
.wiz-saved-allocation-text { font-size: 12.5px; color: #334155; font-weight: 600; margin-right: auto; }
.wiz-saved-allocation-text small { display: block; font-weight: 400; color: #64748B; font-size: 11px; margin-top: 1px; }

.wiz-priority-select { font-size: 12px; padding: 3px 6px; }
.wiz-priority-flags { display: flex; gap: 10px; flex-wrap: wrap; font-size: 11px; }
.wiz-priority-flags label { display: flex; align-items: center; gap: 4px; cursor: pointer; }
.wiz-priority-flags input { margin: 0; }

.wiz-compulsory-badge {
    background: #F59E0B; color: #fff;
    font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
    letter-spacing: 0.5px;
}
.wiz-mapped-rooms { font-size: 10.5px; color: #64748B; margin-top: 2px; }
.wiz-mapped-rooms.none { color: #DC2626; }
.wiz-mapped-rooms a { color: #0d9488; font-weight: 600; text-decoration: none; }
.wiz-mapped-rooms a:hover { text-decoration: underline; }
.wiz-mapped-rooms.none a { color: #DC2626; }

/* ── Wizard: Period Limits ────────────────────────── */
.wiz-limit-row {
    display: grid;
    grid-template-columns: 160px minmax(0, 1fr) 140px 80px 32px;
    gap: 8px; align-items: center;
    padding: 6px 0;
    border-bottom: 1px dashed #F1F5F9;
}
.wiz-limit-row:last-child { border-bottom: none; }
.wiz-limit-row select,
.wiz-limit-row input { font-size: 12px; }

/* ── Wizard: Room Mappings panel ──────────────────── */
.wiz-bulk-room-select { font-size: 11.5px; padding: 2px 6px; }
.wiz-bulk-room-select option { padding: 2px 6px; }
#wizRoomMappingsPanel .table td { vertical-align: middle; }

/* ── Wizard: Advanced rules panel ─────────────────── */
.wizard-advanced summary { padding: 6px 0; list-style: none; }
.wizard-advanced summary::marker { display: none; }
.wizard-advanced summary::after {
    content: '▸'; float: right; transition: transform .15s; color: #94A3B8;
}
.wizard-advanced[open] summary::after { transform: rotate(90deg); }

/* ── Preview summary mini-strips ──────────────────── */
.mini-strip {
    border: 1px solid var(--tt-border); border-radius: 8px;
    padding: 10px 12px; text-align: center; background: #fff;
}
.mini-strip .v { font-size: 22px; font-weight: 700; color: #0f2342; }
.mini-strip .l { font-size: 10.5px; color: #94a3b8; text-transform: uppercase; margin-top: 2px; }
.mini-strip.ok   { border-color: #BBF7D0; background: #F0FDF4; }
.mini-strip.ok .v { color: #16a34a; }
.mini-strip.warn { border-color: #FDE68A; background: #FFFBEB; }
.mini-strip.warn .v { color: #D97706; }
.mini-strip.bad  { border-color: #FECACA; background: #FEF2F2; }
.mini-strip.bad .v { color: #DC2626; }

/* ── Popover tweak ───────────────────────────────── */
.popover { max-width: 340px; font-size: 12.5px; }

@media (max-width: 768px) {
    .tt-page-header { flex-direction: column; align-items: stretch; text-align: center; }
    .tt-page-header .d-flex { justify-content: center; }
    .tt-tabs { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .tt-tab { font-size: 12px; padding: 8px 12px; }
    .export-group { flex-wrap: wrap; justify-content: center; }
    .tt-card-header { flex-direction: column; align-items: stretch; text-align: center; }
    .tt-card-header .d-flex { justify-content: center; }
    .setting-card { flex-wrap: wrap; gap: 8px; }
    .setting-card .sc-actions { width: 100%; justify-content: flex-end; }
    .tt-grid-wrapper { margin: 0 -12px; padding: 0 12px; }
    #constraintsTable { font-size: 12px; }
    #constraintsTable input[type="number"] { width: 50px; }
    #constraintsTable select[multiple] { min-height: 40px; font-size: 11px; }
    .conflict-item { flex-direction: column; align-items: stretch; }
    .conflict-avatar, .conflict-avatar-ph { align-self: center; }
    .ws-mode-toggle { flex-direction: column; }
    .wiz-subj-row { grid-template-columns: 22px minmax(0,1fr) 60px 60px 1fr; }
    .wiz-limit-row { grid-template-columns: 1fr; }
    .pa-subj-row { grid-template-columns: minmax(0,1fr) 70px 70px 55px; font-size: 11.5px; }
}
@media (max-width: 576px) {
    .tt-page-header { padding: 16px 18px; border-radius: 12px; }
    .tt-page-header h4 { font-size: 17px; }
    .tt-card-body { padding: 14px; }
    .setting-card { padding: 10px 12px; }
    .tt-grid td.period-td { padding: 6px 8px; min-width: 70px; }
    .tt-cell { min-height: 50px; padding: 4px; min-width: 60px; }
    .tt-cell .cell-subject { font-size: 10px; }
    .tt-cell .cell-avatar { width: 28px; height: 28px; }
    .tt-cell .cell-avatar-placeholder { width: 28px; height: 28px; }
    .tt-cell .cell-avatar-placeholder i { font-size: 13px; }
    .assignment-teacher-select { min-width: 160px; }
}

/* ── Save Run modal ──────────────────────────────────── */
.save-run-preview {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    margin-top: 18px;
}
.save-run-preview-hdr {
    background: #F1F5F9;
    padding: 8px 14px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748B;
    border-bottom: 1px solid #E2E8F0;
}
.save-run-preview-body {
    padding: 12px 14px;
}
.save-run-preview-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 5px 0;
    font-size: 12.5px;
    color: #334155;
    border-bottom: 1px solid #F1F5F9;
}
.save-run-preview-row:last-child { border-bottom: none; }
.save-run-preview-row .k { color: #64748B; }
.save-run-preview-row .v { font-weight: 600; color: #0F172A; text-align: right; }
.save-run-preview-row .v.warn { color: #D97706; }

/* Success view */
.save-run-success-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #E8F9EE;
    color: #34C759;
    font-size: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    animation: saveRunPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes saveRunPop {
    0%   { transform: scale(0.6); opacity: 0; }
    60%  { transform: scale(1.08); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
.save-run-success-title {
    font-size: 17px;
    font-weight: 600;
    color: #0F172A;
    margin: 0 0 4px;
    letter-spacing: -0.01em;
}
.save-run-success-sub {
    font-size: 13px;
    color: #64748B;
    margin: 0 0 20px;
}
.save-run-code-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 10px 14px 10px 20px;
    margin-bottom: 6px;
}
.save-run-code {
    font-family: "SF Mono", ui-monospace, Menlo, monospace;
    font-size: 20px;
    font-weight: 600;
    letter-spacing: 3px;
    color: #0F172A;
    user-select: all;
}
.save-run-copy-btn {
    background: transparent;
    border: none;
    color: #64748B;
    padding: 4px 6px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 18px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.save-run-copy-btn:hover { background: #E2E8F0; color: #0F172A; }
.save-run-copy-btn.copied { color: #34C759; }

.save-run-meta {
    font-size: 12px;
    color: #94A3B8;
    margin-top: 12px;
    line-height: 1.5;
}

/* ── Run Detail modal ────────────────────────────────── */
.run-detail-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    padding: 20px 24px 4px;
}
.run-detail-stat {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.run-detail-stat .lbl {
    font-size: 11px;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-weight: 600;
}
.run-detail-stat .val {
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.1;
}
.run-detail-stat .val.small { font-size: 15px; font-weight: 600; }
.run-detail-stat.warn { background: #FFFBEB; border-color: #FDE68A; }
.run-detail-stat.warn .val { color: #D97706; }
.run-detail-stat.ok   { background: #F0FDF4; border-color: #BBF7D0; }
.run-detail-stat.ok .val { color: #15803D; }
.run-detail-stat.bad  { background: #FEF2F2; border-color: #FECACA; }
.run-detail-stat.bad .val { color: #DC2626; }

.run-detail-tabs {
    display: flex;
    gap: 4px;
    padding: 16px 24px 0;
    border-bottom: 1px solid #E2E8F0;
    margin-bottom: 0;
}
.run-detail-tab {
    background: none;
    border: none;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    display: inline-flex;
    align-items: center;
    transition: all 0.15s;
    border-radius: 8px 8px 0 0;
}
.run-detail-tab:hover { color: #1565C0; background: #F8FAFC; }
.run-detail-tab.active { color: #1565C0; border-bottom-color: #1565C0; }
.run-detail-tab-badge {
    display: inline-block;
    margin-left: 6px;
    font-size: 10px;
    background: #EFF6FF;
    color: #1565C0;
    padding: 1px 7px;
    border-radius: 10px;
    font-weight: 700;
}

/* Overview pane */
.run-overview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
@media (max-width: 768px) { .run-overview-grid { grid-template-columns: 1fr; } }
.run-overview-section h6 {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748B;
    font-weight: 700;
    margin: 0 0 12px;
}
.run-kv {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #F1F5F9;
    font-size: 13px;
}
.run-kv:last-child { border-bottom: none; }
.run-kv .k { color: #64748B; }
.run-kv .v { color: #0F172A; font-weight: 600; text-align: right; }
.run-kv .v.mono { font-family: "SF Mono", ui-monospace, Menlo, monospace; letter-spacing: 1px; }

/* Classes pane */
.run-classes-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.run-classes-table thead th {
    text-align: left;
    padding: 10px 12px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748B;
    font-weight: 700;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    position: sticky;
    top: 0;
    z-index: 1;
}
.run-classes-table thead th.num { text-align: right; }
.run-classes-table tbody td {
    padding: 11px 12px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
}
.run-classes-table tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
.run-classes-table tbody tr:hover td { background: #F8FAFC; }
.run-classes-table .class-name { font-weight: 600; color: #0F172A; }
.run-classes-table .stat-zero { color: #CBD5E1; }

.run-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.run-status-pill.full    { background: #DCFCE7; color: #15803D; }
.run-status-pill.partial { background: #FEF3C7; color: #B45309; }
.run-status-pill.empty   { background: #FEE2E2; color: #B91C1C; }

.run-classes-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94A3B8;
    font-size: 13px;
}

/* Metadata pane */
.run-meta-pre {
    background: #0F172A;
    color: #E2E8F0;
    border-radius: 12px;
    padding: 16px 20px;
    font-family: "SF Mono", ui-monospace, Menlo, monospace;
    font-size: 12.5px;
    line-height: 1.6;
    overflow-x: auto;
    max-height: 340px;
}
.run-meta-pre .key { color: #7DD3FC; }
.run-meta-pre .str { color: #86EFAC; }
.run-meta-pre .num { color: #FCD34D; }
.run-meta-pre .bool-t { color: #A5B4FC; }
.run-meta-pre .null { color: #94A3B8; font-style: italic; }
</style>


@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid timetable-container">

    {{-- Page Header --}}
    <div class="tt-page-header">
        <div>
            <h4><i class="ri-calendar-todo-line me-2"></i>Timetable Management</h4>
            <p>Create, manage, and export class timetables for your school.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('timetable.teacher') }}" class="btn btn-outline-light btn-sm">
                <i class="ri-user-line me-1"></i>My Timetable
            </a>
            <button class="btn btn-outline-light btn-sm" onclick="openTeacherAssignModal()">
                <i class="ri-user-search-line me-1"></i>View Assignments
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="openGenerationWizardModal()">
                <i class="ri-magic-line me-1"></i>Generation Wizard
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="openPeriodAllocationModal()">
                <i class="ri-grid-line me-1"></i>Period Allocation
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="openConflictScopeModal()">
                <i class="ri-shield-cross-line me-1"></i>Check Conflicts &amp; Anomalies
            </button>
            <button class="btn btn-outline-light btn-sm" onclick="openWholeSchoolExportModal()">
                <i class="ri-school-line me-1"></i>Whole School
            </button>
            <a href="{{ route('timetable.reports.index') }}" class="btn btn-outline-light btn-sm">
                <i class="ri-bar-chart-2-line me-1"></i>Reports
            </a>
        </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3">
        <i class="ri-error-warning-line me-2"></i><strong>Validation Error:</strong>
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">
        <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Selection + Existing timetables --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="tt-card h-100">
                <div class="tt-card-header">
                    <h6><i class="ri-add-circle-line me-2 text-primary"></i>Load / Create Timetable</h6>
                </div>
                <div class="tt-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Class</label>
                            <select class="form-select" id="classSelect">
                                <option value="">— Select Class —</option>
                                @foreach ($schoolclasses as $class)
                                    <option value="{{ $class->id }}">
                                        {{ $class->schoolclass }}{{ $class->arm_name ? ' ' . $class->arm_name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Session</label>
                            <select class="form-select" id="sessionSelect">
                                <option value="">— Select Session —</option>
                                @foreach ($schoolsessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
                            <select class="form-select" id="termSelect">
                                <option value="">All Terms</option>
                                @foreach ($schoolterms as $term)
                                    <option value="{{ $term->id }}">{{ $term->term }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100" onclick="loadOrCreateSetting()">
                                <i class="ri-settings-4-line me-2"></i>Load / Create Timetable
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="tt-card h-100">
                <div class="tt-card-header" style="padding-bottom:0">
                    <ul class="nav nav-tabs border-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tabExistingBtn" data-bs-toggle="tab"
                                    data-bs-target="#tabExisting" type="button" role="tab"
                                    style="font-size:13px;font-weight:600;padding:8px 16px">
                                <i class="ri-history-line me-1"></i>Existing
                                <span class="badge bg-success-subtle text-success ms-1">{{ $settings->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tabSavedRunsBtn" data-bs-toggle="tab"
                                    data-bs-target="#tabSavedRuns" type="button" role="tab"
                                    onclick="loadSavedRuns()"
                                    style="font-size:13px;font-weight:600;padding:8px 16px">
                                <i class="ri-bookmark-3-line me-1"></i>Saved Runs
                                <span class="badge bg-info-subtle text-info ms-1" id="savedRunsCountBadge"></span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content" style="max-height:380px;overflow-y:auto">
                    {{-- Tab 1: Existing timetables --}}
                    <div class="tab-pane fade show active" id="tabExisting" role="tabpanel">
                        <div class="tt-card-body pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="d-flex align-items-center gap-1 cursor-pointer" style="font-size:12px">
                                    <input type="checkbox" id="selectAllSettings" class="form-check-input mt-0"
                                           onchange="toggleSelectAllSettings(this.checked)">
                                    Select All
                                </label>
                                <button class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn"
                                        onclick="bulkDeleteSelectedSettings()" disabled>
                                    <i class="ri-delete-bin-line me-1"></i>Delete Selected
                                </button>
                            </div>

                            @forelse ($settings as $setting)
                            <div class="setting-card" data-id="{{ $setting->id }}" onclick="loadSetting({{ $setting->id }})" data-updated-at="{{ $setting->updated_at->toISOString() }}">
                                <div class="sc-select" onclick="event.stopPropagation()">
                                    <input type="checkbox" class="form-check-input setting-select-checkbox"
                                           value="{{ $setting->id }}"
                                           onchange="toggleSettingSelection({{ $setting->id }}, this.checked)">
                                </div>
                                <div class="sc-icon"><i class="ri-school-line"></i></div>
                                <div class="sc-body">
                                    <div class="sc-title">
                                        {{ $setting->resolved_class_name ?: 'Unknown Class' }}
                                        @if($setting->generation_name)
                                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:10px;font-weight:600;vertical-align:middle">
                                                <i class="ri-magic-line" style="font-size:10px"></i> {{ $setting->generation_name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="sc-meta">
                                        <span>{{ $setting->session->session ?? '—' }}</span>
                                        @if($setting->term)
                                            <span class="mx-1">·</span><span>{{ $setting->term->term }}</span>
                                        @endif
                                        <span class="mx-1">·</span>
                                        <span class="text-muted">Updated {{ $setting->updated_at->diffForHumans() }}</span>
                                        @if($setting->creator)
                                            <span class="mx-1">·</span>
                                            <span class="text-muted">by {{ $setting->creator->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="sc-actions" onclick="event.stopPropagation()">
                                    <button class="btn btn-sm btn-outline-primary" onclick="loadSetting({{ $setting->id }})" title="Edit">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="cloneSetting({{ $setting->id }})" title="Clone">
                                        <i class="ri-file-copy-line"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteSetting({{ $setting->id }}, '{{ $setting->updated_at->toISOString() }}')" title="Delete">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-5 text-muted">
                                <i class="ri-calendar-line ri-3x d-block mb-3 opacity-30"></i>
                                <p>No timetables yet. Create your first one.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Tab 2: Saved runs --}}
                    <div class="tab-pane fade" id="tabSavedRuns" role="tabpanel">
                        <div class="tt-card-body pt-3">
                            <div class="row g-2 mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold" style="font-size:12px">
                                        Find by ID
                                        <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:12px"
                                           data-bs-toggle="popover"
                                           data-bs-title="Find by ID"
                                           data-bs-content="Paste a 10-character run code to jump straight to that saved run."></i>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="runCodeLookup"
                                               placeholder="e.g. A7k9mP2xQw" maxlength="10"
                                               onkeydown="if(event.key==='Enter')lookupRunByCode()">
                                        <button class="btn btn-primary" onclick="lookupRunByCode()">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label fw-semibold" style="font-size:12px">Search</label>
                                    <input type="text" class="form-control form-control-sm" id="runSearchInput"
                                           placeholder="Name, description, or code…"
                                           oninput="debouncedLoadSavedRuns()">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" id="runFilterSession" onchange="loadSavedRuns()">
                                        <option value="">All Sessions</option>
                                        @foreach($schoolsessions as $session)
                                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" id="runFilterTerm" onchange="loadSavedRuns()">
                                        <option value="">All Terms</option>
                                        @foreach($schoolterms as $term)
                                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" id="runFilterStatus" onchange="loadSavedRuns()">
                                        <option value="">All Statuses</option>
                                        <option value="success">Success</option>
                                        <option value="shortfalls">Had Shortfalls</option>
                                        <option value="reverted">Reverted</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" id="runFilterClass" onchange="loadSavedRuns()">
                                        <option value="">All Classes</option>
                                        @foreach($schoolclasses as $class)
                                            <option value="{{ $class->id }}">
                                                {{ $class->schoolclass }}{{ $class->arm_name ? ' '.$class->arm_name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control form-control-sm" id="runFilterDateFrom"
                                           placeholder="From" onchange="loadSavedRuns()">
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control form-control-sm" id="runFilterDateTo"
                                           placeholder="To" onchange="loadSavedRuns()">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="clearRunFilters()">
                                        <i class="ri-close-line me-1"></i>Clear Filters
                                    </button>
                                </div>
                            </div>

                            <div id="savedRunsList">
                                <div class="text-center py-5 text-muted">
                                    <i class="ri-bookmark-3-line ri-3x d-block mb-3 opacity-30"></i>
                                    <p class="mb-0">Load a saved run by its ID above, or browse the list below.</p>
                                </div>
                            </div>

                            <div id="savedRunsPagination" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== TIMETABLE EDITOR ===== --}}
    <div id="timetableEditor" style="display:none">
        <div class="tt-card">
            <div class="tt-card-header" style="background:linear-gradient(135deg,#EFF6FF,#F5F3FF)">
                <div>
                    <h6 id="editorContext" class="mb-0"><i class="ri-school-line me-2 text-primary"></i>Loading…</h6>
                    <small class="text-muted" id="editorSubContext"></small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-secondary" onclick="closeEditor()">
                        <i class="ri-arrow-go-back-line me-1"></i>Close Editor
                    </button>
                </div>
            </div>

            <div class="tt-card-body">
                <div id="editingBanner" class="alert d-flex align-items-center gap-2 mb-3" style="display:none">
                    <i class="ri-user-shared-line ri-lg"></i>
                    <span id="editingBannerText"></span>
                </div>
                <div class="tt-tabs" role="tablist">
                    <button class="tt-tab active" onclick="showTab('periodsTab', this)">
                        <i class="ri-time-line"></i> Periods &amp; Settings
                    </button>
                    <button class="tt-tab" onclick="showTab('constraintsTab', this)">
                        <i class="ri-bar-chart-2-line"></i> Constraints
                    </button>
                    <button class="tt-tab" onclick="showTab('gridTab', this); loadTimetableGrid()">
                        <i class="ri-table-line"></i> Grid
                    </button>
                    <button class="tt-tab" onclick="showTab('conflictsTab', this)">
                        <i class="ri-alert-line"></i> Conflicts
                        <span class="tab-badge" id="conflictBadgeTab" style="display:none">!</span>
                    </button>
                </div>

                {{-- TAB: Periods & Settings --}}
                <div id="periodsTab" class="tab-content-pane">
                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="tt-card border">
                                <div class="tt-card-header"><h6><i class="ri-settings-3-line me-2"></i>Day Settings</h6></div>
                                <div class="tt-card-body">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold">Day Start</label>
                                            <input type="time" class="form-control" id="schoolDayStart">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold">Day End</label>
                                            <input type="time" class="form-control" id="schoolDayEnd">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold">Period (min)</label>
                                            <input type="number" class="form-control" id="periodDuration" min="20" max="90">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold">Short Break (min)</label>
                                            <input type="number" class="form-control" id="shortBreakDuration" min="5" max="60">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Long Break (min)</label>
                                            <input type="number" class="form-control" id="longBreakDuration" min="10" max="90">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Active Days</label>
                                            <div class="d-flex flex-wrap gap-2 mt-1">
                                                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
                                                <label class="d-flex align-items-center gap-1 cursor-pointer" style="font-size:13px">
                                                    <input class="form-check-input active-day-checkbox mt-0" type="checkbox" value="{{ $day }}" id="day_{{ $day }}">
                                                    {{ $day }}
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="tt-card border">
                                <div class="tt-card-header">
                                    <h6><i class="ri-list-check-2-line me-2"></i>Period Schedule</h6>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-info" onclick="openAnchorRebuildPanel()">
                                            <i class="ri-flashlight-line"></i> Rebuild
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="addPeriodRow()">
                                            <i class="ri-add-line"></i> Add
                                        </button>
                                    </div>
                                </div>
                                <div class="tt-card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0" id="periodsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:36px">#</th>
                                                    <th>Name</th>
                                                    <th style="width:140px">Type</th>
                                                    <th style="width:44px"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="periodsBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tt-card-header border-top-0 border-bottom-0" style="border-top:1px solid var(--tt-border)">
                                    <div></div>
                                    <button class="btn btn-success" onclick="saveSettings()">
                                        <i class="ri-save-line me-2"></i>Save Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: Constraints --}}
                <div id="constraintsTab" class="tab-content-pane" style="display:none">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h6 class="mb-1">Subject Constraints</h6>
                            <p class="text-muted mb-0" style="font-size:13px">Define how many times per week each subject is taught and preferred scheduling rules.</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-success" onclick="saveConstraints()">
                                <i class="ri-save-line me-2"></i>Save
                            </button>
                            <button class="btn btn-primary" onclick="generateTimetable()">
                                <i class="ri-magic-line me-2"></i>Auto-Generate
                            </button>
                        </div>
                    </div>
                    <div class="tt-card border">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="constraintsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th><th>Teacher</th><th>Periods / Week</th>
                                        <th>Allow Double</th><th>Max Doubles</th>
                                        <th>Preferred Days</th><th>Avoid Days</th><th>Compulsory</th>
                                    </tr>
                                </thead>
                                <tbody id="constraintsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- TAB: Grid --}}
                <div id="gridTab" class="tab-content-pane" style="display:none">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h6 class="mb-1">Weekly Timetable Grid</h6>
                            <p class="text-muted mb-0" style="font-size:13px">Click any cell to assign a subject and teacher.</p>
                        </div>
                        <div class="export-group">
                            <label class="d-flex align-items-center gap-1 cursor-pointer" style="font-size:12px" title="Toggle staff photos in the grid">
                                <input type="checkbox" id="toggleStaffPictures" checked onchange="toggleStaffPictureVisibility()">
                                Show staff pictures
                            </label>
                            <select id="exportOrientation" class="form-select form-select-sm" style="width:auto">
                                <option value="horizontal">Horizontal Layout</option>
                                <option value="vertical">Vertical Layout</option>
                            </select>
                            <select id="exportPaper" class="form-select form-select-sm" style="width:auto">
                                @foreach(\App\Http\Controllers\TimetableController::PAPER_SIZES as $size)
                                    @php $meta = \App\Http\Controllers\TimetableController::PAPER_LABELS[$size] ?? [strtoupper($size)]; @endphp
                                    <option value="{{ $size }}" {{ $size === 'a3' ? 'selected' : '' }}>{{ $meta[0] }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-outline-secondary" onclick="loadTimetableGrid()">
                                <i class="ri-refresh-line me-1"></i>Refresh
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="exportTimetable('csv')">
                                <i class="ri-file-excel-line me-1"></i>CSV
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="exportTimetable('pdf')">
                                <i class="ri-file-pdf-line me-1"></i>PDF
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="sendNotifications()">
                                <i class="ri-mail-send-line me-1"></i>Notify
                            </button>
                        </div>
                    </div>
                    <div id="ttHolidayBanner" class="alert d-none d-flex align-items-center gap-2 mb-3 border-0 shadow-sm" role="alert"></div>
                    <div class="tt-card border">
                        <div class="tt-grid-wrapper" id="timetableGridContainer">
                            <div class="text-center py-5 text-muted">
                                <i class="ri-table-line ri-3x d-block mb-3 opacity-30"></i>
                                <p>Select the <strong>Timetable Grid</strong> tab to load the schedule.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: Conflicts --}}
                <div id="conflictsTab" class="tab-content-pane" style="display:none">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h6 class="mb-1">Conflict Checker</h6>
                            <p class="text-muted mb-0" style="font-size:13px">
                                Detects teacher double-booking and room conflicts across <strong>all classes</strong>.
                            </p>
                        </div>
                        <button class="btn btn-primary" onclick="checkConflicts()">
                            <i class="ri-search-line me-2"></i>Run Check
                        </button>
                    </div>
                    <div id="conflictCheckedAt" class="text-muted mb-2" style="font-size:12px;display:none">
                        <i class="ri-time-line me-1"></i><span id="conflictCheckedAtText"></span>
                    </div>
                    <div id="conflictsList">
                        <div class="text-center py-5 text-muted">
                            <i class="ri-check-double-line ri-3x d-block mb-3 text-success opacity-50"></i>
                            <p>Click <strong>Run Conflict Check</strong> to validate teacher and room assignments across all classes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

{{-- ============================================================ --}}
{{-- TEACHER ASSIGNMENT MODAL (READ-ONLY)                         --}}
{{-- ============================================================ --}}
<div class="modal fade" id="teacherAssignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1565C0,#6A1B9A)">
        <h5 class="modal-title text-white"><i class="ri-user-search-line me-2"></i>View Teacher Assignments</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="max-height:75vh;overflow-y:auto">
        <div class="alert alert-info d-flex align-items-start gap-2 mb-3" style="font-size:13px">
          <i class="ri-information-line ri-lg mt-1"></i>
          <div>
            <strong>Read-only view.</strong> This screen only shows the current teacher–subject–class mapping used by the timetable generator.
            To assign or change teachers, use the main <strong>Subject / Class management</strong> screens.
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
            <select class="form-select" id="taSessionId" onchange="loadTeacherAssignments()">
              <option value="">— Select —</option>
              @foreach($schoolsessions as $session)
                <option value="{{ $session->id }}">{{ $session->session }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
            <select class="form-select" id="taTermId" onchange="loadTeacherAssignments()">
              <option value="">All Terms</option>
              @foreach($schoolterms as $term)
                <option value="{{ $term->id }}">{{ $term->term }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Search</label>
            <input type="text" class="form-control" id="taSearchInput" placeholder="Filter by class, subject or teacher…" oninput="renderTeacherAssignmentTable()">
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap" id="taSummaryBar" style="display:none">
            <span class="badge bg-success-subtle text-success" id="taAssignedCount">0 assigned</span>
            <span class="badge bg-warning-subtle text-warning" id="taUnassignedCount">0 unassigned</span>
        </div>
        <div id="teacherAssignmentContainer">
            <div class="text-center py-5 text-muted">
                <i class="ri-user-search-line ri-3x d-block mb-3 opacity-30"></i>
                <p>Select a session to view subject/teacher assignments.</p>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ============================================================ --}}
{{-- EDIT SLOT MODAL                                              --}}
{{-- ============================================================ --}}
<div class="modal fade" id="editSlotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary pb-0" style="padding:20px 24px 0">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div id="editTeacherAvatar" style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="ri-user-line text-white ri-xl"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-white mb-0" style="font-size:15px">Edit Timetable Slot</h5>
                        <small class="text-white opacity-75" id="editSlotContext">—</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto mt-0" data-bs-dismiss="modal"></button>
                </div>
                <div class="w-100 mt-3 d-flex gap-2 pb-0">
                    <div class="flex-1 px-2 py-2 rounded-top" style="background:rgba(255,255,255,.1)">
                        <div class="text-white opacity-60" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px">Period</div>
                        <div class="text-white fw-semibold" id="editSlotPeriodName" style="font-size:13px">—</div>
                    </div>
                    <div class="flex-1 px-2 py-2 rounded-top" style="background:rgba(255,255,255,.1)">
                        <div class="text-white opacity-60" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px">Day</div>
                        <div class="text-white fw-semibold" id="editSlotDayName" style="font-size:13px">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <input type="hidden" id="editSlotSettingId">
                <input type="hidden" id="editSlotPeriodId">
                <input type="hidden" id="editSlotDay">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subject</label>
                        <select class="form-select" id="editSlotSubject" onchange="onSubjectChange()">
                            <option value="">— Free Period —</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Teacher</label>
                        <select class="form-select" id="editSlotTeacher" onchange="onTeacherChange()">
                            <option value="">— No Teacher —</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Room / Venue</label>
                        <select id="editSlotRoom" placeholder="Search or type a room…"></select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="editSlotIsDouble">
                            <label class="form-check-label" for="editSlotIsDouble">Double Period</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea class="form-control" id="editSlotNotes" rows="2" placeholder="Optional notes…"></textarea>
                    </div>
                </div>
                <div id="slotConflictPanel" style="display:none;margin-top:16px">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748B;margin-bottom:8px">
                        <i class="ri-shield-check-line me-1"></i>Conflict Check
                    </div>
                    <div id="slotConflictInner"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0" style="padding:0 24px 20px">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="saveSlotBtn" onclick="saveSlot()">
                    <i class="ri-save-line me-2"></i>Save Slot
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- WHOLE SCHOOL EXPORT MODAL                                     --}}
{{-- ============================================================ --}}
<div class="modal fade" id="wholeSchoolExportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-school-line me-2"></i>Export Whole School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ws-mode-toggle">
                    <button type="button" class="ws-mode-btn active" data-mode="per_class" onclick="selectWsMode(this)">
                        <i class="ri-file-copy-2-line"></i>Per-Class
                        <small>One page per class</small>
                    </button>
                    <button type="button" class="ws-mode-btn" data-mode="merged" onclick="selectWsMode(this)">
                        <i class="ri-layout-grid-line"></i>Merged Grid
                        <small>All classes overlaid in one table</small>
                    </button>
                </div>
                <input type="hidden" id="wholeSchoolMode" value="per_class">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
                    <select class="form-select" id="wholeSchoolSessionId">
                        <option value="">— Select Session —</option>
                        @foreach($schoolsessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
                    <select class="form-select" id="wholeSchoolTermId">
                        <option value="">All Terms</option>
                        @foreach($schoolterms as $term)
                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Merged-layout selector — only relevant when "Merged Grid" mode is active --}}
                <div class="mb-3" id="wsLayoutWrap" style="display:none">
                    <label class="form-label fw-semibold">
                        Merged Layout
                        <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                           data-bs-toggle="popover"
                           data-bs-title="Merged Layout"
                           data-bs-content="Days as Columns / Days as Rows overlay every class into one grid, colour-coded per class. Columns per Class / Rows per Class instead lay each class's own grid out side by side or stacked, one after another."></i>
                    </label>
                    <select class="form-select" id="wholeSchoolLayout">
                    <option value="overlay_horizontal">Days as Columns</option>
                    <option value="overlay_vertical">Days as Rows</option>
                    <option value="days_as_rows_by_class">Days as Rows (Grouped by Class)</option>   {{-- NEW --}}
                    <option value="class_columns">Columns per Class</option>
                    <option value="class_rows">Rows per Class</option>
                </select>
                </div>

                <div class="mb-3" id="wsOrientationWrap">
                    <label class="form-label fw-semibold">Orientation</label>
                    <select class="form-select" id="wholeSchoolOrientation">
                        <option value="horizontal">Horizontal Layout (Days as columns)</option>
                        <option value="vertical">Vertical Layout (Days as rows)</option>
                    </select>
                </div>
                <div class="mb-3" id="wsPaperWrap">
                    @include('timetable.partials.paper-select', [
                        'selectId' => 'wholeSchoolPaper',
                        'selected' => 'a3',
                    ])
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-outline-primary" onclick="exportWholeSchoolTimetable('web')">
                    <i class="ri-global-line me-2"></i>Web View
                </button>
                <button class="btn btn-primary" onclick="exportWholeSchoolTimetable('pdf')">
                    <i class="ri-file-pdf-line me-2"></i>PDF
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- CONFLICT SCOPE MODAL                                          --}}
{{-- ============================================================ --}}
<div class="modal fade" id="conflictScopeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#DC2626,#EA580C)">
        <h5 class="modal-title text-white"><i class="ri-shield-cross-line me-2"></i>Check Conflicts &amp; Anomalies — Session / Term</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="max-height:70vh;overflow-y:auto">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
            <select class="form-select" id="ccSessionId">
              <option value="">— Select —</option>
              @foreach($schoolsessions as $session)
                <option value="{{ $session->id }}">{{ $session->session }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
            <select class="form-select" id="ccTermId">
              <option value="">All Terms</option>
              @foreach($schoolterms as $term)
                <option value="{{ $term->id }}">{{ $term->term }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <button class="btn btn-danger w-100 mb-3" onclick="runScopeConflictCheck()">
          <i class="ri-search-line me-2"></i>Run Check
        </button>
        <p class="text-muted mb-3" style="font-size:12px">
          <i class="ri-information-line me-1"></i>
          Checks teacher and room double-bookings, plus subjects that repeat for the same class on the
          same day in a way a real school day never should — more than a double period, or two periods
          that aren't genuinely back-to-back (a break sits between them).
        </p>
        <div id="conflictScopeResults">
          <div class="text-center py-4 text-muted">
            <i class="ri-shield-check-line ri-2x d-block mb-2 opacity-30"></i>
            <p>Select a session and run the check.</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ============================================================ --}}
{{-- CLONE MODAL                                                   --}}
{{-- ============================================================ --}}
<div class="modal fade" id="cloneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-file-copy-line me-2"></i>Clone Timetable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:13px">Copies all periods, constraints and slots. Optionally change session or term.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Session <span class="text-muted fw-normal">(optional)</span></label>
                    <select class="form-select" id="cloneSessionId">
                        <option value="">Same Session</option>
                        @foreach($schoolsessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label fw-semibold">New Term <span class="text-muted fw-normal">(optional)</span></label>
                    <select class="form-select" id="cloneTermId">
                        <option value="">Same Term</option>
                        @foreach($schoolterms as $term)
                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="confirmClone()"><i class="ri-file-copy-line me-2"></i>Clone</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- GENERATION WIZARD MODAL                                       --}}
{{-- ============================================================ --}}
<div class="modal fade" id="generationWizardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1565C0,#6A1B9A)">
        <h5 class="modal-title text-white"><i class="ri-magic-line me-2"></i>Generation Wizard</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="max-height:70vh;overflow-y:auto">

        <div id="wizFormContent">
        <p class="text-muted" style="font-size:13px">Set up the day structure for many classes at once, then optionally auto-generate timetables for all of them.</p>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
            <select class="form-select" id="wizSessionId" onchange="refreshWizardAllocationPicker()">
              <option value="">— Select —</option>
              @foreach($schoolsessions as $session)
                <option value="{{ $session->id }}">{{ $session->session }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
            <select class="form-select" id="wizTermId" onchange="refreshWizardAllocationPicker()">
              <option value="">All Terms</option>
              @foreach($schoolterms as $term)
                <option value="{{ $term->id }}">{{ $term->term }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Scope</label>
            <select class="form-select" id="wizScope" onchange="toggleWizardClassPicker()">
              <option value="all">All Classes (with subjects assigned)</option>
              <option value="selected">Selected Classes Only</option>
            </select>
          </div>
          <div class="col-12" id="wizClassPickerWrap" style="display:none">
            <label class="form-label fw-semibold">Classes</label>
            <select class="form-select" id="wizClassIds" multiple size="6">
              @foreach($schoolclasses as $class)
                <option value="{{ $class->id }}">{{ $class->schoolclass }}{{ $class->arm_name ? ' '.$class->arm_name : '' }}</option>
              @endforeach
            </select>
            <small class="text-muted">Ctrl/Cmd-click to select multiple.</small>
          </div>
        </div>

        <hr>
        <h6 class="mb-3"><i class="ri-time-line me-2"></i>Day Structure</h6>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Day Start</label>
            <input type="time" class="form-control" id="wizDayStart" value="08:00">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Day End</label>
            <input type="time" class="form-control" id="wizDayEnd" value="14:30">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Lessons / Day</label>
            <input type="number" class="form-control" id="wizLessonsPerDay" min="1" max="12" value="8">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Period Length (min)</label>
            <input type="number" class="form-control" id="wizPeriodDuration" min="20" max="90" value="40">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Short Break After Period</label>
            <input type="number" class="form-control" id="wizShortBreakAfter" min="1" placeholder="e.g. 2">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Short Break (min)</label>
            <input type="number" class="form-control" id="wizShortBreakDuration" min="5" max="60" value="20">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Long Break After Period</label>
            <input type="number" class="form-control" id="wizLongBreakAfter" min="1" placeholder="e.g. 4">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Long Break (min)</label>
            <input type="number" class="form-control" id="wizLongBreakDuration" min="10" max="90" value="40">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="wizAssemblyFirstPeriod" onchange="toggleWizardAssemblyDay()">
              <label class="form-check-label" for="wizAssemblyFirstPeriod">Assembly as First Period</label>
            </div>
          </div>
          <div class="col-md-3" id="wizAssemblyDayWrap" style="display:none">
            <label class="form-label fw-semibold">Assembly Day</label>
            <select class="form-select" id="wizAssemblyDay">
              @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
                <option value="{{ $day }}">{{ $day }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Free Periods / Week</label>
            <input type="number" class="form-control" id="wizFreePeriods" min="0" value="0">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Max Lessons / Day <span class="text-muted fw-normal">(optional)</span></label>
            <input type="number" class="form-control" id="wizMaxLessonsPerDay" min="1" placeholder="No cap">
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Active Days</label>
            <div class="d-flex flex-wrap gap-3 mt-1">
              @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
                <label class="d-flex align-items-center gap-1" style="font-size:13px">
                  <input class="form-check-input wiz-active-day mt-0" type="checkbox" value="{{ $day }}" checked>
                  {{ $day }}
                </label>
              @endforeach
            </div>
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="wizDeprioritizeBreakAdjacent" checked>
              <label class="form-check-label" for="wizDeprioritizeBreakAdjacent">
                Deprioritize periods next to a break when auto-generating
                <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                   data-bs-toggle="popover"
                   data-bs-title="Deprioritize break-adjacent periods"
                   data-bs-content="When on, lesson slots immediately before or after a break or assembly are scored down, so academic periods tend to cluster away from the interruption. Turn off if you want the generator to treat all lesson periods equally."></i>
              </label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="wizIncludeRooms" checked>
              <label class="form-check-label" for="wizIncludeRooms">
                Automatically assign available rooms (no double-bookings)
              </label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="wizStrictRoomMapping">
              <label class="form-check-label" for="wizStrictRoomMapping">
                Strict room mapping
                <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                   data-bs-toggle="popover"
                   data-bs-title="Strict room mapping"
                   data-bs-content="On: a lesson can only use a room that is mapped to its class (and subject) in Room Management. Off: the generator falls back to any free room, as before."></i>
                <small class="text-muted d-block ms-4">
                  Requires rooms to be mapped to classes in Room Management first.
                </small>
              </label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="wizPrioritiesActive" checked>
              <label class="form-check-label" for="wizPrioritiesActive">
                Apply subject priorities
                <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                   data-bs-toggle="popover"
                   data-bs-title="Apply subject priorities"
                   data-bs-content="Off: ignore every priority row, even if set. Useful for comparing two generations — one with priorities, one without."></i>
              </label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="wizJoinDoublePeriods" checked>
              <label class="form-check-label" for="wizJoinDoublePeriods">
                Join double periods together
                <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                   data-bs-toggle="popover"
                   data-bs-title="Join double periods together"
                   data-bs-content="On: when a subject needs more than one period on the same day, they're placed back-to-back (e.g. Period 1 and 2) as one genuine double period. Off: a subject never gets two periods on the same day at all -- its periods are always spread across different days instead."></i>
              </label>
            </div>
          </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><i class="ri-calendar-event-line me-2"></i>Half-Days
                <span class="text-muted fw-normal" style="font-size:12px">(optional — cap lessons on specific days)</span>
            </h6>
            <button class="btn btn-sm btn-outline-primary" onclick="addWizardHalfDayRow()"><i class="ri-add-line"></i> Add</button>
        </div>
        <div id="wizHalfDaysBody"></div>

        {{-- Subjects & Priority panel --}}
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="ri-bookmark-3-line me-2"></i>Subjects &amp; Priority
                <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                   data-bs-toggle="popover"
                   data-bs-title="Subjects &amp; Priority"
                   data-bs-content="For each class in scope, tune the weekly period count, allow double periods, and — optionally — set a priority that influences how the generator places this subject."></i>
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadWizardSubjects()">
                <i class="ri-refresh-line me-1"></i>Load Subjects
            </button>
        </div>

        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="wizIncludeUnassigned" onchange="loadWizardSubjects()">
          <label class="form-check-label" for="wizIncludeUnassigned" style="font-size:12.5px">
            Also show every other subject not yet allocated to this class
            <span class="text-muted">— includes subjects already taught in other classes; tick "Include" on a row (or apply a saved set that has one) to generate it for this class</span>
          </label>
        </div>

        <div id="wizAllocationSetWrap" class="wiz-saved-allocation-box" style="display:none">
            <div class="wiz-saved-allocation-icon"><i class="ri-flashlight-line"></i></div>
            <div class="wiz-saved-allocation-text">
                Skip the manual setup
                <small>Apply a saved period allocation for this session/term instead</small>
            </div>
            <select class="form-select form-select-sm" id="wizAllocationSetId" style="max-width:260px">
                <option value="">Choose a saved set…</option>
            </select>
            <button type="button" class="btn btn-sm btn-success" onclick="applyWizardPeriodAllocationSet()">
                <i class="ri-download-2-line me-1"></i>Apply
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openPeriodAllocationModal()">
                <i class="ri-grid-line me-1"></i>Manage Sets
            </button>
        </div>

        <div id="wizSubjectsPanel">
            <div class="text-center py-4 text-muted">
                <i class="ri-bookmark-3-line ri-2x d-block mb-2 opacity-30"></i>
                <p class="mb-0">Pick a session above, then click <strong>Load Subjects</strong> to see per-class subject settings.</p>
            </div>
        </div>

        {{-- Room Mappings panel --}}
        <hr>
        <h6 class="mb-3">
            <i class="ri-links-line me-2"></i>Room Mappings
            <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
               data-bs-toggle="popover"
               data-bs-title="Room Mappings"
               data-bs-content="Assign rooms to each (class, subject) pair in scope. Only rooms listed here will be used when Strict Room Mapping is on."></i>
        </h6>

        <div id="wizRoomMappingsPanel">
            <div class="text-center py-4 text-muted">
                <i class="ri-links-line ri-2x d-block mb-2 opacity-30"></i>
                <p class="mb-0">Click <strong>Load Subjects</strong> above to see room mappings.</p>
            </div>
        </div>

        {{-- Period Limits panel --}}
        <hr>
        <h6 class="mb-3">
            <i class="ri-speed-up-line me-2"></i>Period Limits
            <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
               data-bs-toggle="popover"
               data-bs-title="Period Limits"
               data-bs-content="Hard ceilings on how many periods the generator will place. Applied on top of the per-subject periods/week values in the panel above."></i>
        </h6>

        <div id="wizPeriodLimitsBody"></div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="wizCapModeSoft">
                <label class="form-check-label" for="wizCapModeSoft">
                    Soft penalty mode
                    <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                       data-bs-toggle="popover"
                       data-bs-title="Soft penalty mode"
                       data-bs-content="Off (default): a candidate slot is skipped if placing it would exceed any cap — caps are honoured literally. On: the slot is scored down heavily but still usable — the generator prefers to stay under caps but will exceed them rather than leave a lesson unplaced."></i>
                </label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addWizardPeriodLimit()">
                <i class="ri-add-line me-1"></i>Add Limit
            </button>
        </div>

        {{-- Advanced rules panel --}}
        <hr>
        <details id="wizAdvancedPanel" class="wizard-advanced">
            <summary class="h6 mb-0" style="cursor:pointer;">
                <i class="ri-settings-5-line me-2"></i>Advanced Generation Rules
                <span class="text-muted fw-normal" style="font-size:12px">(optional)</span>
            </summary>

            <div class="mt-3">

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Morning cutoff
                        <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                           data-bs-toggle="popover"
                           data-bs-title="Morning cutoff"
                           data-bs-content="Only used when a subject's priority says to affect slot quality. High-priority subjects get a scoring bonus for morning slots; low-priority subjects get a small bonus for afternoon."></i>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="wizMorningCutoffMode" id="wizMorningCutoffHalf" value="half" checked>
                        <label class="form-check-label" for="wizMorningCutoffHalf">
                            Half of the day's lesson periods
                            <small class="text-muted d-block ms-4">
                                With 8 lessons/day, the first 4 count as "morning".
                            </small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="wizMorningCutoffMode" id="wizMorningCutoffFixed" value="fixed">
                        <label class="form-check-label" for="wizMorningCutoffFixed">
                            Fixed number of lesson periods
                        </label>
                    </div>
                    <div class="ms-4 mt-1" style="max-width:160px">
                        <input type="number" class="form-control form-control-sm" id="wizMorningCutoffFixedCount"
                               min="1" max="12" value="3" disabled>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Protected subject handling
                        <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                           data-bs-toggle="popover"
                           data-bs-title="Protected subject handling"
                           data-bs-content="Applies only to subjects with Is Protected switched on in the panel above."></i>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="wizProtectedMode" id="wizProtectedDrop" value="drop_unprotected" checked>
                        <label class="form-check-label" for="wizProtectedDrop">
                            Prefer to drop unprotected subjects
                            <small class="text-muted d-block ms-4">
                                If a protected subject can't fit, the generator keeps it
                                and lets whichever unprotected subject was left over go
                                unplaced instead.
                            </small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="wizProtectedMode" id="wizProtectedEvict" value="evict">
                        <label class="form-check-label" for="wizProtectedEvict">
                            Evict lower-priority slots to make room
                            <small class="text-muted d-block ms-4">
                                Actively removes a lower-priority lesson from a slot to
                                place the protected subject. The evicted subject may
                                itself become partially unplaced.
                            </small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="wizProtectedMode" id="wizProtectedBoth" value="both">
                        <label class="form-check-label" for="wizProtectedBoth">
                            Try eviction first, then fall back to dropping
                            <small class="text-muted d-block ms-4">
                                Best of both: attempt eviction; if that still leaves the
                                protected subject unplaced, keep it and drop an
                                unprotected one instead.
                            </small>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Strict room mapping behaviour
                        <i class="ri-question-line text-muted ms-1" style="cursor:pointer;font-size:14px"
                           data-bs-toggle="popover"
                           data-bs-title="Strict room mapping behaviour"
                           data-bs-content="Applies only when Strict room mapping is switched on (Day Structure panel above)."></i>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="wizStrictRoomMode" id="wizStrictTeacherOnly" value="teacher_only" checked>
                        <label class="form-check-label" for="wizStrictTeacherOnly">
                            Place teacher-only and flag
                            <small class="text-muted d-block ms-4">
                                The lesson is still scheduled — it just has no room.
                                Flagged in the generation summary as a warning.
                            </small>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="wizStrictRoomMode" id="wizStrictRefuse" value="refuse">
                        <label class="form-check-label" for="wizStrictRefuse">
                            Refuse to place the subject
                            <small class="text-muted d-block ms-4">
                                The subject doesn't get scheduled at all; it appears
                                under "unplaced subjects" in the summary.
                            </small>
                        </label>
                    </div>
                </div>

            </div>
        </details>

        <div class="mt-3 pt-3" style="border-top:1px solid #E2E8F0">
            <label class="form-label fw-semibold">
                Timetable name <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="wizGenerationName" maxlength="150"
                   placeholder="e.g. First Term 2026 — Full Generation">
            <small class="text-muted">
                Required to generate. Shown on each timetable's card in the Existing tab, so runs are easy to tell apart later.
            </small>
        </div>

        </div><!-- /wizFormContent -->

        <div id="wizGenerationProgress" style="display:none">
          <h6 class="mb-3"><i class="ri-magic-line me-2"></i>Generating Timetables…</h6>
          <div id="wizProgressList" style="max-height:320px;overflow-y:auto"></div>
        </div>

        <div id="wizPreviewPane" style="display:none">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="mb-0">
                    <i class="ri-eye-line me-2"></i>Preview — not saved
                    <span class="badge bg-info-subtle text-info ms-2" id="wizPreviewClass"></span>
                </h6>
                <div class="small text-muted">
                    This is a scratch run. Nothing has been committed yet.
                </div>
            </div>

            <div id="wizPreviewSummary" class="mb-3"></div>

            <div class="tt-card border">
                <div class="tt-grid-wrapper" id="wizPreviewGridContainer">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-3">Running preview…</p>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <button class="btn btn-outline-secondary" onclick="exitPreviewMode()">
                    <i class="ri-arrow-go-back-line me-1"></i>Back to Wizard
                </button>
                <button class="btn btn-primary" onclick="acceptPreviewAndApply()">
                    <i class="ri-save-line me-1"></i>Accept &amp; Save for Real
                </button>
            </div>
        </div>

      </div>
      <div class="modal-footer flex-wrap gap-2">
        <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-outline-info" onclick="previewGeneration()">
            <i class="ri-eye-line me-1"></i>Preview
        </button>
        <button class="btn btn-outline-primary" onclick="submitGenerationWizard(false)">
            <i class="ri-save-line me-1"></i>Apply Structure Only
        </button>
        <button class="btn btn-primary" onclick="submitGenerationWizard(true)">
            <i class="ri-magic-line me-1"></i>Apply &amp; Generate
        </button>
        <button class="btn btn-success ms-auto" onclick="openSaveRunModal()">
            <i class="ri-bookmark-line me-1"></i>Save Run
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ============================================================ --}}
{{-- ============================================================ --}}
{{-- PERIOD ALLOCATION MODAL                                      --}}
{{-- ============================================================ --}}
<div class="modal fade" id="periodAllocationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#1565C0)">
        <h5 class="modal-title text-white"><i class="ri-grid-line me-2"></i>Period Allocation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="max-height:75vh;overflow-y:auto">

        <p class="text-muted" style="font-size:13px">
            Set how many periods a week each subject gets per class, save it as a named preset for a session
            and term, then pick it in the Generation Wizard instead of typing it in every time.
        </p>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
            <select class="form-select" id="paSessionId" onchange="onPeriodAllocationScopeChange()">
              <option value="">— Select —</option>
              @foreach($schoolsessions as $session)
                <option value="{{ $session->id }}">{{ $session->session }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
            <select class="form-select" id="paTermId" onchange="onPeriodAllocationScopeChange()">
              <option value="">All Terms</option>
              @foreach($schoolterms as $term)
                <option value="{{ $term->id }}">{{ $term->term }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Classes <span class="text-muted fw-normal">(optional filter)</span></label>
            <select class="form-select" id="paClassIds" multiple size="1">
              @foreach($schoolclasses as $class)
                <option value="{{ $class->id }}">{{ $class->schoolclass }}{{ $class->arm_name ? ' '.$class->arm_name : '' }}</option>
              @endforeach
            </select>
            <small class="text-muted">Leave empty for every class that has subjects assigned. Ctrl/Cmd-click to select several.</small>
          </div>
        </div>

        <div id="paSetsWrap" class="mt-3" style="display:none">
          <label class="form-label fw-semibold mb-1">Saved sets for this session/term</label>
          <div id="paSetsPills" class="d-flex flex-column gap-2" style="max-height:280px; overflow-y:auto;"></div>
        </div>

        <hr>

        <div class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Set name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="paSetName" placeholder="e.g. Term 1 Standard Allocation" maxlength="150">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" class="form-control" id="paSetDescription" placeholder="Notes for other admins" maxlength="1000">
          </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0"><i class="ri-bookmark-3-line me-2"></i>Subjects &amp; Periods/Week</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadPeriodAllocationGrid()">
            <i class="ri-refresh-line me-1"></i>Load Classes
          </button>
        </div>

        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="paIncludeUnassigned" onchange="loadPeriodAllocationGrid()">
          <label class="form-check-label" for="paIncludeUnassigned" style="font-size:12.5px">
            Also show every other subject not yet allocated to this class
            <span class="text-muted">— includes subjects already taught in other classes; tick "Include" on a row to plan periods for it here; it won't create a Subject-Class assignment</span>
          </label>
        </div>

        <div id="paGridPanel">
          <div class="text-center py-4 text-muted">
            <i class="ri-grid-line ri-2x d-block mb-2 opacity-30"></i>
            <p class="mb-0">Select a session and click <strong>Load Classes</strong> to see per-class subjects.</p>
          </div>
        </div>

      </div>
      <div class="modal-footer flex-wrap gap-2">
        <span id="paEditingHint" class="text-muted me-auto" style="font-size:12px"></span>
        <button type="button" class="btn btn-outline-danger" id="paDeleteBtn" style="display:none" onclick="deleteCurrentPeriodAllocationSet()">
          <i class="ri-delete-bin-line me-1"></i>Delete Set
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="resetPeriodAllocationForm(true)">
          <i class="ri-add-line me-1"></i>New Set
        </button>
        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="paSaveBtn" onclick="savePeriodAllocationSet()">
          <i class="ri-save-line me-1"></i>Save Set
        </button>
      </div>
    </div>
  </div>
</div>


{{-- ANCHOR REBUILD MODAL                                         --}}
{{-- ============================================================ --}}
<div class="modal fade" id="anchorRebuildModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-flashlight-line me-2"></i>Quick Rebuild Periods</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3" style="font-size:13px">Rebuilds this class's period list from lesson count + break/assembly anchors, replacing manually-edited rows.</p>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Day Start</label>
            <input type="time" class="form-control" id="arDayStart" value="08:00">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Lessons / Day</label>
            <input type="number" class="form-control" id="arLessonsPerDay" min="1" max="12" value="8">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Period Length (min)</label>
            <input type="number" class="form-control" id="arPeriodDuration" min="20" max="90" value="40">
          </div>
          <div class="col-6"></div>
          <div class="col-6">
            <label class="form-label fw-semibold">Short Break After Period</label>
            <input type="number" class="form-control" id="arShortBreakAfter" min="1" placeholder="e.g. 2">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Short Break (min)</label>
            <input type="number" class="form-control" id="arShortBreakDuration" min="5" max="60" value="20">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Long Break After Period</label>
            <input type="number" class="form-control" id="arLongBreakAfter" min="1" placeholder="e.g. 4">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Long Break (min)</label>
            <input type="number" class="form-control" id="arLongBreakDuration" min="10" max="90" value="40">
          </div>
          <div class="col-6 d-flex align-items-end">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="arAssemblyEnabled" onchange="document.getElementById('arAssemblyDayWrap').style.display=this.checked?'':'none'">
              <label class="form-check-label" for="arAssemblyEnabled">Assembly First Period</label>
            </div>
          </div>
          <div class="col-6" id="arAssemblyDayWrap" style="display:none">
            <label class="form-label fw-semibold">Assembly Day</label>
            <select class="form-select" id="arAssemblyDay">
              @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
                <option value="{{ $day }}">{{ $day }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="submitAnchorRebuild()"><i class="ri-flashlight-line me-1"></i>Rebuild</button>
      </div>
    </div>
  </div>
</div>

{{-- ============================================================ --}}
{{-- SAVE GENERATION RUN MODAL — single modal, inline code reveal --}}
{{-- ============================================================ --}}
<div class="modal fade" id="saveRunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#1B5E20,#2E7D32)">
                <div>
                    <h5 class="modal-title text-white mb-0">
                        <i class="ri-bookmark-3-line me-2"></i>Save This Generation Run
                    </h5>
                    <small class="text-white opacity-75">A frozen, retrievable copy of what you just generated.</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- ─── Input view (shown first) ──────────────────────── --}}
            <div id="saveRunInputView">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Run name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="saveRunName" maxlength="150"
                               placeholder="e.g. First term draft — SSS1">
                        <small class="text-muted">Give it something you'll recognise a month from now.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Description <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <textarea class="form-control" id="saveRunDescription" rows="3" maxlength="2000"
                                  placeholder="What did you tweak? Why this run?"></textarea>
                    </div>

                    {{-- Preview of what's about to be saved --}}
                    <div class="save-run-preview">
                        <div class="save-run-preview-hdr">
                            <i class="ri-information-line me-1"></i>Will save
                        </div>
                        <div class="save-run-preview-body" id="saveRunPreview">
                            <span class="text-muted" style="font-size:12.5px">Computing…</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" id="saveRunBtn" onclick="saveGenerationRun()">
                        <i class="ri-save-line me-1"></i>Save run
                    </button>
                </div>
            </div>

            {{-- ─── Success view (revealed after save) ────────────── --}}
            <div id="saveRunSuccessView" style="display:none">
                <div class="modal-body text-center" style="padding:32px 24px 20px">
                    <div class="save-run-success-icon">
                        <i class="ri-checkbox-circle-fill"></i>
                    </div>
                    <h5 class="save-run-success-title">Run saved</h5>
                    <p class="save-run-success-sub">Use this code to find it again</p>

                    <div class="save-run-code-wrap">
                        <div class="save-run-code" id="savedRunCode">—</div>
                        <button class="save-run-copy-btn" id="savedRunCopyBtn"
                                onclick="copySavedRunCode()" title="Copy code">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </div>

                    <div class="save-run-meta" id="savedRunMeta"></div>
                </div>
                <div class="modal-footer" style="border-top:none;padding-top:0">
                    <button class="btn btn-light" onclick="closeSaveRunAndBrowse()">Close</button>
                    <button class="btn btn-primary" onclick="closeSaveRunAndBrowse()">
                        <i class="ri-bookmark-3-line me-1"></i>View in saved runs
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- RUN DETAIL MODAL — tabbed                                    --}}
{{-- ============================================================ --}}
<div class="modal fade" id="runDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#1565C0,#0d9488)">
                <div>
                    <h5 class="modal-title text-white mb-0" id="runDetailTitle">Run</h5>
                    <small class="text-white opacity-75" id="runDetailCode"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Stat strip --}}
            <div class="run-detail-stats" id="runDetailStats"></div>

            {{-- Tab nav --}}
            <div class="run-detail-tabs">
                <button class="run-detail-tab active" data-tab="overview" onclick="runDetailTab('overview', this)">
                    <i class="ri-dashboard-line me-1"></i>Overview
                </button>
                <button class="run-detail-tab" data-tab="classes" onclick="runDetailTab('classes', this)">
                    <i class="ri-organization-chart me-1"></i>Classes
                    <span class="run-detail-tab-badge" id="runDetailClassesBadge"></span>
                </button>
                <button class="run-detail-tab" data-tab="metadata" onclick="runDetailTab('metadata', this)">
                    <i class="ri-information-line me-1"></i>Metadata
                </button>
            </div>

            <div class="modal-body" style="max-height:60vh;overflow-y:auto;padding-top:16px">
                <div class="run-detail-pane" id="runDetailPane_overview"></div>
                <div class="run-detail-pane" id="runDetailPane_classes" style="display:none"></div>
                <div class="run-detail-pane" id="runDetailPane_metadata" style="display:none"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-outline-info" onclick="compareRunWithAnother()">
                    <i class="ri-git-compare-line me-1"></i>Compare
                </button>
                <button class="btn btn-outline-primary" onclick="exportRunToPdf()">
                    <i class="ri-file-pdf-line me-1"></i>Export PDF
                </button>
                <button class="btn btn-warning" id="restoreRunBtn" onclick="openRestoreModal()">
                    <i class="ri-restart-line me-1"></i>Restore to live
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- RESTORE RUN MODAL                                            --}}
{{-- ============================================================ --}}
<div class="modal fade" id="restoreRunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#E65100,#EA580C)">
                <h5 class="modal-title text-white">
                    <i class="ri-restart-line me-2"></i>Restore Run to Live Settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="ri-alert-line me-1"></i>
                    <strong>This replaces the live timetables.</strong> Any edits made to them since
                    this run was saved will be reported before overwriting — you can cancel here.
                </div>

                <div id="restoreRunSummary" class="mb-3">
                    <div class="spinner-border spinner-border-sm me-2"></div>Preparing…
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="restoreForce">
                    <label class="form-check-label" for="restoreForce">
                        Force overwrite
                        <small class="text-muted d-block ms-4">
                            Overwrite even settings that have been edited since this run was saved.
                        </small>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="restoreUnpublish">
                    <label class="form-check-label" for="restoreUnpublish">
                        Unpublish locked settings
                        <small class="text-muted d-block ms-4">
                            Restoring into a published timetable requires unpublishing it first.
                            Teachers will need to be re-notified afterward.
                        </small>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning" id="confirmRestoreBtn" onclick="confirmRestoreRun()">
                    <i class="ri-restart-line me-1"></i>Restore
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- COMPARE RUNS MODAL                                           --}}
{{-- ============================================================ --}}
<div class="modal fade" id="compareRunsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#1565C0,#0d9488)">
                <h5 class="modal-title text-white">
                    <i class="ri-git-compare-line me-2"></i>Compare Runs
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:75vh;overflow-y:auto">
                <div id="compareRunsBody">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-3">Loading…</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- EXPORT RUN MODAL                                             --}}
{{-- ============================================================ --}}
<div class="modal fade" id="exportRunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-file-pdf-line me-2"></i>Export Run</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Format</label>
                    <select class="form-select" id="exportRunFormat">
                        <option value="pdf" selected>PDF</option>
                        <option value="web">Web View</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Layout</label>
                    <select class="form-select" id="exportRunMode">
                        <option value="per_class" selected>Per-Class (one page per class)</option>
                        <option value="merged">Merged Grid (all classes in one table)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Orientation</label>
                    <select class="form-select" id="exportRunOrientation">
                        <option value="horizontal" selected>Horizontal (days as columns)</option>
                        <option value="vertical">Vertical (days as rows)</option>
                    </select>
                </div>
                <div class="mb-3">
                    @include('timetable.partials.paper-select', [
                        'selectId' => 'exportRunPaper',
                        'selected' => 'a3',
                    ])
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="exportRunIncludeMeta" checked>
                    <label class="form-check-label" for="exportRunIncludeMeta">
                        Include run metadata block
                        <small class="text-muted d-block ms-4">
                            Name, code, description, creator, and date in the header.
                        </small>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="exportRunIncludeRules">
                    <label class="form-check-label" for="exportRunIncludeRules">
                        Append generation rules
                        <small class="text-muted d-block ms-4">
                            The advanced rules snapshot that produced this run.
                        </small>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="submitExportRun()">
                    <i class="ri-download-line me-1"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- QUICK MAP ROOM MODAL                                         --}}
{{-- ============================================================ --}}
<div class="modal fade" id="quickMapRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#0ea5e9)">
                <h5 class="modal-title text-white">
                    <i class="ri-links-line me-2"></i>Map a Room
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="quickMapClassId">
                <input type="hidden" id="quickMapSubjectId">

                <div class="alert alert-info mb-3" style="font-size:12.5px">
                    Mapping <strong id="quickMapSubjectName">—</strong> for <strong id="quickMapClassName">—</strong>.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Room <span class="text-danger">*</span></label>
                    <select class="form-select" id="quickMapRoomSelect"></select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
                    <select class="form-select" id="quickMapSessionId"></select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Term <span class="text-muted fw-normal">(optional)</span></label>
                    <select class="form-select" id="quickMapTermId">
                        <option value="">All terms</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-info text-white" onclick="submitQuickMapRoom()">
                    <i class="ri-add-line me-1"></i>Add Mapping
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@include('partials.apple-alert')

<script>
// ============================================================================
// GLOBALS
// ============================================================================
let currentSettingId  = null;
let currentSetting    = null;
let currentSettingVersion = null;
let editingHeartbeatTimer = null;
let currentPeriods    = [];
let currentGrid       = {};
let currentDays       = [];
let currentDayMeta    = {};
let availableSubjects = [];
let allTeachers       = [];
let availableRooms    = [];
let currentTodayDayName = null;
let currentTodayHoliday = null;
let pendingCloneId    = null;
let roomTomSelect     = null;
let conflictCheckTimer = null;
let selectedSettingIds = new Set();
let taData = { teachers: [], unassigned: [] };
let taRows = [];
let previewState = null;
let currentRun = null;
let currentRunData = null;

const SUBJECT_COLORS = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899','#14B8A6','#84CC16'];
const subjectColorMap = {};
let colorSeq = 0;

function getSubjectColor(subjectId) {
    if (!subjectId) return null;
    if (!subjectColorMap[subjectId]) subjectColorMap[subjectId] = SUBJECT_COLORS[colorSeq++ % SUBJECT_COLORS.length];
    return subjectColorMap[subjectId];
}

// ============================================================================
// ROUTES
// ============================================================================
const ROUTES = {
    setup:                      '{{ route("timetable.setup") }}',
    saveSettings:               '{{ route("timetable.save-settings") }}',
    saveConstraints:            '{{ route("timetable.save-constraints") }}',
    autoGenerate:               '{{ route("timetable.auto-generate") }}',
    saveSlot:                   '{{ route("timetable.save-slot") }}',
    sendNotifications:          '{{ route("timetable.send-notifications") }}',
    cloneSetting:               '{{ route("timetable.clone-setting") }}',
    exportWholeSchool:          '{{ route("timetable.export-whole-school") }}',
    exportWholeSchoolWeb:       '{{ route("timetable.export-whole-school-web") }}',
    exportMergedGrid:           '{{ route("timetable.export-merged-grid") }}',
    mergedGridWeb:              '{{ route("timetable.merged-grid-web") }}',
    applyGenerationTemplate:    '{{ route("timetable.apply-generation-template") }}',
    autoGenerateWholeSchool:    '{{ route("timetable.auto-generate-whole-school") }}',
    rebuildPeriodsFromAnchors:  '{{ route("timetable.rebuild-periods-from-anchors") }}',
    saveHalfDays:               '{{ route("timetable.save-half-days") }}',
    checkSlotConflict:          '{{ route("timetable.check-slot-conflict") }}',
    getTeacherAssignments:      '{{ route("timetable.teacher-assignments") }}',
    checkConflictsScope:        '{{ route("timetable.check-conflicts-scope") }}',
    resolveSubjectSpread:        '{{ route("timetable.resolve-subject-spread") }}',
    wizardData:                 '{{ route("timetable.wizard-data") }}',
    previewGeneration:          '{{ route("timetable.preview-generation") }}',
    getSetting:                 '{{ route("timetable.get-setting", ["settingId" => ":id"]) }}',
    getGrid:                    '{{ route("timetable.get-grid", ["settingId" => ":id"]) }}',
    checkConflicts:             '{{ route("timetable.check-conflicts", ["settingId" => ":id"]) }}',
    export:                     '{{ route("timetable.export", ["settingId" => ":id"]) }}',
    deleteSetting:              '{{ route("timetable.delete-setting", ["settingId" => ":id"]) }}',
    heartbeat:                  '{{ route("timetable.heartbeat", ["id" => ":id"]) }}',
    releaseEditing:             '{{ route("timetable.release-editing", ["id" => ":id"]) }}',

    // Saved generation runs
    runsSave:                   '{{ route("timetable.runs.save") }}',
    runsList:                   '{{ route("timetable.runs.list") }}',
    runsCompare:                '{{ route("timetable.runs.compare") }}',
    runsShow:                   '{{ route("timetable.runs.show", ["identifier" => "__ID__"]) }}'.replace('/__ID__', ''),
    runsDelete:                 '{{ route("timetable.runs.delete", ["runId" => "__ID__"]) }}'.replace('/__ID__', ''),
    runsRestore:                '{{ route("timetable.runs.restore", ["runId" => "__ID__"]) }}'.replace('/__ID__', ''),
    runsExport:                 '{{ route("timetable.runs.export", ["runId" => "__ID__"]) }}'.replace('/__ID__', ''),

    // Rooms
    roomsListJson:              '{{ route("rooms.list-json") }}',
    roomMappings:               '{{ route("rooms.mappings", ["roomId" => "__ID__"]) }}',
    roomMappingsStore:          '{{ route("rooms.mappings.store", ["roomId" => "__ID__"]) }}',
    roomMappingsDestroy:        '{{ route("rooms.mappings.destroy", ["mappingId" => "__ID__"]) }}',
    sessionsList:               '{{ route("api.sessions-list") }}',
    termsList:                  '{{ route("api.terms-list") }}',

    // Period Allocation
    periodAllocationGrid:       '{{ route("timetable.periodAllocation.grid") }}',
    periodAllocationSetsList:   '{{ route("timetable.periodAllocation.sets") }}',
    periodAllocationSetShow:    '{{ route("timetable.periodAllocation.sets.show", ["setId" => ":id"]) }}',
    periodAllocationSetSave:    '{{ route("timetable.periodAllocation.sets.save") }}',
    periodAllocationSetDelete:  '{{ route("timetable.periodAllocation.sets.delete", ["setId" => ":id"]) }}',
};

const CSRF = '{{ csrf_token() }}';

function url(base, id) {
    return base.replace(/:id\b/, id);
}

// ============================================================================
// UTILITIES
// ============================================================================
function escapeHtml(str) {
    if (str == null) return '';
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

// function apiFetch(endpoint, method = 'GET', body = null) {
//     const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } };
//     if (body && method !== 'GET') { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
//     return fetch(endpoint, opts);
// }

async function apiFetch(endpoint, method = 'GET', body = null) {
    const opts = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF,
        },
    };
    if (body && method !== 'GET') {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }

    const res = await fetch(endpoint, opts);
    const ct  = res.headers.get('content-type') || '';

    if (!ct.includes('application/json')) {
        const text = await res.text();
        console.error('[apiFetch] Non-JSON response', {
            url:    endpoint,
            method: method,
            status: res.status,
            body:   text.slice(0, 1500),
        });
        throw new Error(
            `Server returned ${res.status} ${res.statusText} (not JSON). ` +
            `Check the browser console for the raw body, and storage/logs/laravel.log.`
        );
    }
    return res;
}

function showLoader() { AppleAlert.loading('Processing…'); }
function hideLoader() { AppleAlert.close(); }

function showTab(tabId, btn) {
    document.querySelectorAll('.tab-content-pane').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tt-tab').forEach(t => t.classList.remove('active'));
    document.getElementById(tabId).style.display = '';
    if (btn) btn.classList.add('active');
}

function closeEditor() {
    stopEditingHeartbeat();
    document.getElementById('timetableEditor').style.display = 'none';
    currentSettingId = null;
    currentSettingVersion = null;
}

// ============================================================================
// TEACHER ASSIGNMENT MODAL — READ-ONLY
// ============================================================================
function openTeacherAssignModal() {
    document.getElementById('teacherAssignmentContainer').innerHTML = `
        <div class="text-center py-5 text-muted">
            <i class="ri-user-search-line ri-3x d-block mb-3 opacity-30"></i>
            <p>Select a session to view subject/teacher assignments.</p>
        </div>`;
    document.getElementById('taSummaryBar').style.display = 'none';
    document.getElementById('taSearchInput').value = '';
    new bootstrap.Modal(document.getElementById('teacherAssignModal')).show();
}

async function loadTeacherAssignments() {
    const sessionId = document.getElementById('taSessionId').value;
    const termId    = document.getElementById('taTermId').value;
    const container = document.getElementById('teacherAssignmentContainer');

    if (!sessionId) {
        container.innerHTML = `<div class="text-center py-5 text-muted"><p>Select a session to view subject/teacher assignments.</p></div>`;
        document.getElementById('taSummaryBar').style.display = 'none';
        taRows = [];
        return;
    }

    container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3 text-muted">Loading assignments…</p></div>';

    try {
        const params = new URLSearchParams({ session_id: sessionId });
        if (termId) params.set('term_id', termId);
        const res  = await apiFetch(`${ROUTES.getTeacherAssignments}?${params.toString()}`, 'GET');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load.');

        taData = data;
        taRows = buildAssignmentRows(data);
        renderTeacherAssignmentTable();
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger m-3">Failed to load: ${escapeHtml(e.message)}</div>`;
    }
}

function buildAssignmentRows(data) {
    const rows = [];
    (data.unassigned || []).forEach(u => rows.push({ ...u, teacher_id: null, teacher_name: null }));
    (data.teachers || []).forEach(t => {
        (t.assignments || []).forEach(a => rows.push({ ...a, teacher_id: t.teacher_id, teacher_name: t.teacher_name }));
    });
    rows.sort((a, b) =>
        (a.class_name || '').localeCompare(b.class_name || '') ||
        (a.subject_name || '').localeCompare(b.subject_name || '')
    );
    return rows;
}

function renderTeacherAssignmentTable() {
    const container = document.getElementById('teacherAssignmentContainer');
    const search    = (document.getElementById('taSearchInput').value || '').toLowerCase().trim();

    if (!taRows.length) {
        document.getElementById('taSummaryBar').style.display = 'none';
        container.innerHTML = `<div class="text-center py-5 text-muted">
            <i class="ri-information-line ri-2x d-block mb-2"></i>
            <p>No subjects assigned to any class for this session/term yet.
            Assign subjects and teachers in the main <strong>Subject / Class management</strong> screens first.
            The timetable module only reads those assignments for generation.</p>
        </div>`;
        return;
    }

    const filtered = !search ? taRows : taRows.filter(r =>
        (r.class_name || '').toLowerCase().includes(search) ||
        (r.subject_name || '').toLowerCase().includes(search) ||
        (r.teacher_name || '').toLowerCase().includes(search)
    );

    const assignedCount   = taRows.filter(r => r.teacher_id).length;
    const unassignedCount = taRows.length - assignedCount;
    document.getElementById('taSummaryBar').style.display = '';
    document.getElementById('taAssignedCount').textContent   = `${assignedCount} assigned`;
    document.getElementById('taUnassignedCount').textContent = `${unassignedCount} unassigned`;

    if (!filtered.length) {
        container.innerHTML = `<div class="text-center py-4 text-muted"><p>No matches for "${escapeHtml(search)}".</p></div>`;
        return;
    }

    let html = `<div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>
            <th>Class</th><th>Subject</th><th>Teacher</th><th style="width:90px">Status</th>
        </tr></thead><tbody>`;

    filtered.forEach(row => {
        const hasTeacher = !!row.teacher_id;
        const teacherCell = hasTeacher
            ? `<span class="fw-semibold">${escapeHtml(row.teacher_name || '—')}</span>`
            : `<span class="text-warning"><i class="ri-alert-line me-1"></i>Unassigned</span>`;
        const statusBadge = hasTeacher
            ? `<span class="badge bg-success-subtle text-success">Ready</span>`
            : `<span class="badge bg-warning-subtle text-warning">Missing</span>`;

        html += `<tr>
            <td>${escapeHtml(row.class_name || '—')}</td>
            <td>${escapeHtml(row.subject_name || '—')}</td>
            <td>${teacherCell}</td>
            <td>${statusBadge}</td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// ============================================================================
// MULTI-SELECT DELETE
// ============================================================================
function toggleSettingSelection(id, checked) {
    if (checked) selectedSettingIds.add(id);
    else selectedSettingIds.delete(id);

    const card = document.querySelector(`.setting-card[data-id="${id}"]`);
    if (card) card.classList.toggle('is-selected', checked);

    updateBulkDeleteUI();
}

function toggleSelectAllSettings(checked) {
    document.querySelectorAll('.setting-select-checkbox').forEach(cb => {
        cb.checked = checked;
        const id = parseInt(cb.value);
        if (checked) selectedSettingIds.add(id);
        else selectedSettingIds.delete(id);
        const card = document.querySelector(`.setting-card[data-id="${id}"]`);
        if (card) card.classList.toggle('is-selected', checked);
    });
    updateBulkDeleteUI();
}

function updateBulkDeleteUI() {
    const btn   = document.getElementById('bulkDeleteBtn');
    const count = selectedSettingIds.size;
    if (btn) {
        btn.disabled  = count === 0;
        btn.innerHTML = `<i class="ri-delete-bin-line me-1"></i>Delete Selected${count ? ' (' + count + ')' : ''}`;
    }

    const allCbs      = document.querySelectorAll('.setting-select-checkbox');
    const selectAllCb = document.getElementById('selectAllSettings');
    if (selectAllCb) {
        selectAllCb.checked       = allCbs.length > 0 && count === allCbs.length;
        selectAllCb.indeterminate = count > 0 && count < allCbs.length;
    }
}

async function bulkDeleteSelectedSettings() {
    const ids = [...selectedSettingIds];
    if (!ids.length) return;

    const ok = await AppleAlert.destructive(
        `Delete ${ids.length} timetable${ids.length > 1 ? 's' : ''}?`,
        'Every slot in the selected timetables will be permanently removed. This can\'t be undone.',
        { confirmText: 'Delete all' }
    );
    if (!ok.isConfirmed) return;

    showLoader();
    const outcomes = await Promise.all(ids.map(async (id) => {
        const card      = document.querySelector(`.setting-card[data-id="${id}"]`);
        const updatedAt = card?.dataset.updatedAt || null;
        try {
            const res  = await apiFetch(url(ROUTES.deleteSetting, id), 'DELETE', { expected_updated_at: updatedAt });
            const data = await res.json();
            return { id, success: !!data.success };
        } catch (e) {
            return { id, success: false };
        }
    }));
    hideLoader();

    selectedSettingIds.clear();
    const failedCount  = outcomes.filter(o => !o.success).length;
    const successCount = outcomes.length - failedCount;

    if (!failedCount) {
        AppleAlert.deleted(`${successCount} timetable(s) removed`);
    } else {
        AppleAlert.warning(
            'Partially completed',
            `${successCount} deleted, ${failedCount} failed (possibly changed or already removed by someone else). Reloading list…`
        );
    }
    setTimeout(() => location.reload(), 1800);
}

// ============================================================================
// LOAD / CREATE
// ============================================================================
async function loadOrCreateSetting() {
    const classId   = document.getElementById('classSelect').value;
    const sessionId = document.getElementById('sessionSelect').value;
    const termId    = document.getElementById('termSelect').value || null;
    if (!classId || !sessionId) return AppleAlert.warning('Required', 'Please select a Class and a Session.');

    showLoader();
    try {
        const res  = await apiFetch(ROUTES.setup, 'POST', { schoolclass_id: classId, session_id: sessionId, term_id: termId });
        const data = await res.json();
        if (data.success) {
            await loadSetting(data.setting_id);
        } else {
            hideLoader();
            AppleAlert.error('Could not load timetable', data.message || 'Please try again.');
        }
    } catch (e) {
        hideLoader();
        AppleAlert.error('Could not load timetable', e.message);
    }
}

async function loadSetting(settingId) {
    showLoader();
    try {
        const res  = await apiFetch(url(ROUTES.getSetting, settingId), 'GET');
        const data = await res.json();
        if (!data.success) {
            hideLoader();
            AppleAlert.error('Could not load timetable', data.message || 'Please try again.');
            return;
        }

        currentSettingId      = settingId;
        currentSetting        = data.setting;
        currentSettingVersion = data.setting.updated_at;
        availableSubjects     = data.available_subjects || [];

        if (data.editing_info) {
            document.getElementById('editingBanner').style.display = '';
            document.getElementById('editingBannerText').textContent =
                `${data.editing_info.user_name} is also editing this timetable (since ${data.editing_info.since}).`;
        } else {
            document.getElementById('editingBanner').style.display = 'none';
        }
        startEditingHeartbeat(settingId);

        const className   = (data.setting.schoolclass?.schoolclass || '')
            + (data.setting.schoolclass?.arm_name ? ' ' + data.setting.schoolclass.arm_name : '');
        const sessionName = data.setting.session?.session || '—';
        const termName    = data.setting.term?.term || 'All Terms';

        document.getElementById('editorContext').innerHTML    = `<i class="ri-school-line me-2 text-primary"></i>${escapeHtml(className || '—')}`;
        document.getElementById('editorSubContext').textContent = `${sessionName} · ${termName}`;

        document.getElementById('schoolDayStart').value     = (data.setting.school_day_start || '08:00').slice(0, 5);
        document.getElementById('schoolDayEnd').value       = (data.setting.school_day_end   || '14:30').slice(0, 5);
        document.getElementById('periodDuration').value     = data.setting.period_duration_minutes      || 40;
        document.getElementById('shortBreakDuration').value = data.setting.short_break_duration_minutes || 20;
        document.getElementById('longBreakDuration').value  = data.setting.long_break_duration_minutes  || 40;

        const activeDays = data.setting.active_days || ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        document.querySelectorAll('.active-day-checkbox').forEach(cb => cb.checked = activeDays.includes(cb.value));

        loadPeriodsIntoTable(data.setting.periods?.length ? data.setting.periods : [
            {name:'Period 1',type:'lesson'},{name:'Period 2',type:'lesson'},
            {name:'Short Break',type:'short_break'},{name:'Period 3',type:'lesson'},
            {name:'Period 4',type:'lesson'},{name:'Long Break',type:'long_break'},
            {name:'Period 5',type:'lesson'},{name:'Period 6',type:'lesson'},
        ]);

        loadConstraintsIntoTable(data.setting.constraints || []);

        hideLoader();
        document.getElementById('timetableEditor').style.display = '';
        document.getElementById('timetableEditor').scrollIntoView({ behavior: 'smooth', block: 'start' });
        showTab('periodsTab', document.querySelector('.tt-tab'));

    } catch (e) {
        hideLoader();
        AppleAlert.error('Could not load timetable', e.message);
    }
}

// ============================================================================
// EDITING HEARTBEAT
// ============================================================================
function startEditingHeartbeat(settingId) {
    stopEditingHeartbeat();
    editingHeartbeatTimer = setInterval(() => {
        apiFetch(url(ROUTES.heartbeat, settingId), 'POST').catch(() => {});
    }, 60000);
}

function stopEditingHeartbeat() {
    if (editingHeartbeatTimer) {
        clearInterval(editingHeartbeatTimer);
        editingHeartbeatTimer = null;
    }
    if (currentSettingId) {
        apiFetch(url(ROUTES.releaseEditing, currentSettingId), 'POST').catch(() => {});
    }
}

window.addEventListener('beforeunload', stopEditingHeartbeat);

// ============================================================================
// PERIODS
// ============================================================================
function loadPeriodsIntoTable(periods) {
    document.getElementById('periodsBody').innerHTML = '';
    periods.forEach((p, i) => addPeriodRow(p.name, p.type, i + 1));
}

function addPeriodRow(name = '', type = 'lesson', order = null) {
    const tbody  = document.getElementById('periodsBody');
    const rowNum = order ?? (tbody.querySelectorAll('tr').length + 1);
    const tr     = document.createElement('tr');
    tr.innerHTML = `
        <td class="text-center fw-bold text-muted period-order">${rowNum}</td>
        <td><input type="text" class="form-control form-control-sm period-name" value="${escapeHtml(name)}" placeholder="e.g. Period 1"></td>
        <td>
            <select class="form-select form-select-sm period-type">
                ${['lesson','short_break','long_break','assembly','free'].map(v =>
                    `<option value="${v}" ${type===v?'selected':''}>${v.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`
                ).join('')}
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove();reorderPeriods()">
                <i class="ri-delete-bin-line ri-lg"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    reorderPeriods();
}

function reorderPeriods() {
    document.querySelectorAll('#periodsBody tr').forEach((tr, i) => {
        const cell = tr.querySelector('.period-order');
        if (cell) cell.textContent = i + 1;
    });
}

function getPeriodsFromTable() {
    return [...document.querySelectorAll('#periodsBody tr')].map(tr => ({
        name: tr.querySelector('.period-name')?.value?.trim(),
        type: tr.querySelector('.period-type')?.value,
    })).filter(p => p.name);
}

async function saveSettings() {
    const periods    = getPeriodsFromTable();
    const activeDays = [...document.querySelectorAll('.active-day-checkbox:checked')].map(cb => cb.value);
    if (!periods.length)    return AppleAlert.warning('Missing periods', 'Add at least one period.');
    if (!activeDays.length) return AppleAlert.warning('Missing days', 'Select at least one active day.');

    showLoader();
    try {
        const res  = await apiFetch(ROUTES.saveSettings, 'POST', {
            setting_id:                   currentSettingId,
            expected_updated_at:          currentSettingVersion,
            school_day_start:             document.getElementById('schoolDayStart').value,
            school_day_end:               document.getElementById('schoolDayEnd').value,
            period_duration_minutes:      parseInt(document.getElementById('periodDuration').value),
            short_break_duration_minutes: parseInt(document.getElementById('shortBreakDuration').value),
            long_break_duration_minutes:  parseInt(document.getElementById('longBreakDuration').value),
            active_days: activeDays, periods,
        });
        const data = await res.json();
        if (data.success) {
            currentSettingVersion = data.setting.updated_at;
            hideLoader();
            AppleAlert.saved('Settings saved');
            await loadSetting(currentSettingId);
        } else if (data.has_version_conflict) {
            hideLoader();
            handleVersionConflict(data);
        } else {
            hideLoader();
            AppleAlert.error('Save failed', data.message || 'Please try again.');
        }
    } catch (e) {
        hideLoader();
        AppleAlert.error('Save failed', e.message);
    }
}

// ============================================================================
// CONSTRAINTS
// ============================================================================
function loadConstraintsIntoTable(constraints) {
    const tbody = document.getElementById('constraintsBody');
    tbody.innerHTML = '';
    if (!availableSubjects.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">
            <i class="ri-information-line ri-2x d-block mb-2"></i>No subjects assigned to this class.</td></tr>`;
        return;
    }
    const cMap = new Map(constraints.map(c => [c.subject_id, c]));
    availableSubjects.forEach(subj => {
        const c  = cMap.get(subj.subject_id) || {};
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="fw-semibold" style="font-size:13px">
                ${escapeHtml(subj.subject_name)}
                <input type="hidden" class="constraint-subject-id" value="${subj.subject_id}">
            </td>
            <td class="text-muted" style="font-size:12px">${escapeHtml(subj.teacher_name)}</td>
            <td><input type="number" class="form-control form-control-sm periods-per-week" value="${c.periods_per_week||2}" min="1" max="10" style="width:70px"></td>
            <td class="text-center"><input type="checkbox" class="form-check-input allow-double" ${c.allow_double_period?'checked':''}></td>
            <td><input type="number" class="form-control form-control-sm max-double" value="${c.max_double_periods_per_week??1}" min="0" max="5" style="width:60px" ${!c.allow_double_period?'disabled':''}></td>
            <td><select class="form-select form-select-sm preferred-days" multiple size="3">${genDayOptions(c.preferred_days||[])}</select></td>
            <td><select class="form-select form-select-sm avoid-days" multiple size="3">${genDayOptions(c.avoid_days||[])}</select></td>
            <td class="text-center"><input type="checkbox" class="form-check-input is-compulsory" ${c.is_compulsory!==false?'checked':''}></td>
        `;
        tr.querySelector('.allow-double').addEventListener('change', function() {
            tr.querySelector('.max-double').disabled = !this.checked;
        });
        tbody.appendChild(tr);
    });
}

function genDayOptions(selected) {
    return ['Monday','Tuesday','Wednesday','Thursday','Friday']
        .map(d => `<option value="${d}" ${selected.includes(d)?'selected':''}>${d}</option>`).join('');
}

function getConstraintsFromTable() {
    return [...document.querySelectorAll('#constraintsBody tr')].map(tr => {
        const sid = tr.querySelector('.constraint-subject-id')?.value;
        if (!sid) return null;
        return {
            subject_id:       parseInt(sid),
            periods_per_week: parseInt(tr.querySelector('.periods-per-week').value),
            allow_double:     tr.querySelector('.allow-double').checked,
            max_double:       parseInt(tr.querySelector('.max-double').value),
            preferred_days:   [...tr.querySelector('.preferred-days').selectedOptions].map(o => o.value),
            avoid_days:       [...tr.querySelector('.avoid-days').selectedOptions].map(o => o.value),
            is_compulsory:    tr.querySelector('.is-compulsory').checked,
        };
    }).filter(Boolean);
}

async function saveConstraints() {
    const constraints = getConstraintsFromTable();
    if (!constraints.length) return AppleAlert.warning('Nothing to save', 'No constraint rows to save.');
    showLoader();
    try {
        const res  = await apiFetch(ROUTES.saveConstraints, 'POST', { setting_id: currentSettingId, expected_updated_at: currentSettingVersion, constraints });
        const data = await res.json();
        if (data.success) {
            currentSettingVersion = data.updated_at;
            hideLoader();
            AppleAlert.saved('Constraints saved');
        } else if (data.has_version_conflict) {
            hideLoader();
            handleVersionConflict(data);
        } else {
            hideLoader();
            AppleAlert.error('Save failed', data.message || 'Please try again.');
        }
    } catch (e) {
        hideLoader();
        AppleAlert.error('Save failed', e.message);
    }
}

// ============================================================================
// AUTO-GENERATE (single class)
// ============================================================================
async function generateTimetable() {
    const result = await AppleAlert.rich({
        title: 'Auto-generate this timetable?',
        html: `
            <p>The existing timetable will be cleared and rebuilt from your constraints. Teacher assignments are respected across all classes.</p>
            <label style="display:flex;align-items:center;gap:8px;margin-top:14px;cursor:pointer;justify-content:flex-start">
                <input type="checkbox" id="swalIncludeRooms" checked style="width:16px;height:16px">
                <span>Automatically assign available rooms</span>
            </label>`,
        icon: 'warning',
        showCancelButton: true,
        confirmText: 'Generate',
        theme: 'primary',
        width: 480,
        preConfirm: () => ({ includeRooms: document.getElementById('swalIncludeRooms')?.checked ?? true }),
    });
    if (!result.isConfirmed) return;
    const includeRooms = result.value?.includeRooms ?? true;

    showLoader();
    try {
        const res  = await apiFetch(ROUTES.autoGenerate, 'POST', {
            setting_id: currentSettingId,
            expected_updated_at: currentSettingVersion,
            include_rooms: includeRooms,
        });
        const data = await res.json();
        if (data.success) {
            currentSettingVersion = data.setting_updated_at || currentSettingVersion;
            hideLoader();
            showTab('gridTab', document.querySelectorAll('.tt-tab')[2]);
            await loadTimetableGridAnimated();
            silentConflictCheck();

            const shortfall = data.stats?.room_shortfall_count
                ? `<p class="text-warning mt-2" style="font-size:12px"><i class="ri-alert-line"></i> ${data.stats.room_shortfall_count} lesson(s) couldn't get a room.</p>`
                : '';
            const unplaced = data.stats?.unplaced_subjects?.length
                ? `<p class="text-warning mt-2" style="font-size:12px"><i class="ri-alert-line"></i> Some subjects could not be fully placed.</p>`
                : '';
            const noRoom = data.stats?.no_room_placement_count
                ? `<p class="text-warning mt-2" style="font-size:12px"><i class="ri-alert-line"></i> ${data.stats.no_room_placement_count} lesson(s) placed without a room.</p>`
                : '';
            const refused = data.stats?.room_refused_count
                ? `<p class="text-danger mt-2" style="font-size:12px"><i class="ri-close-circle-line"></i> ${data.stats.room_refused_count} candidate placement(s) skipped — no mapped room and strict mode is on refuse.</p>`
                : '';
            const needsAttention = !!(shortfall || unplaced || noRoom || refused);

            if (needsAttention) {
                AppleAlert.rich({
                    title: 'Timetable generated',
                    html: `<p>Built successfully, but with some caveats:</p>${shortfall}${unplaced}${noRoom}${refused}`,
                    icon: 'warning',
                    confirmText: 'Got it',
                    theme: 'warning',
                    width: 480,
                });
            } else {
                AppleAlert.toast('Timetable generated', 'success');
            }
        } else if (data.has_version_conflict) {
            hideLoader();
            handleVersionConflict(data);
        } else {
            hideLoader();
            AppleAlert.error('Generation failed', data.message || 'Please try again.');
        }
    } catch (e) {
        hideLoader();
        AppleAlert.error('Generation failed', e.message);
    }
}

// ============================================================================
// TIMETABLE GRID
// ============================================================================
async function loadTimetableGrid() {
    if (!currentSettingId) return;
    const container = document.getElementById('timetableGridContainer');
    container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3 text-muted">Loading timetable…</p></div>';
    try {
        const res  = await apiFetch(url(ROUTES.getGrid, currentSettingId), 'GET');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed');
        currentPeriods = data.periods || [];
        currentGrid    = data.grid    || {};
        currentDays    = data.days    || ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        currentDayMeta = data.day_period_meta || {};
        allTeachers    = data.teachers|| [];
        availableRooms = data.rooms   || [];
        currentTodayDayName = data.today_day_name || null;
        currentTodayHoliday = data.today_holiday   || null;
        updateRoomDropdown(availableRooms);
        updateHolidayBanner();
        renderGrid({ animate: false });
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger m-3">Failed to load grid: ${escapeHtml(e.message)}</div>`;
    }
}

async function loadTimetableGridAnimated() {
    if (!currentSettingId) return;
    const container = document.getElementById('timetableGridContainer');
    container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3 text-muted">Loading timetable…</p></div>';
    try {
        const res  = await apiFetch(url(ROUTES.getGrid, currentSettingId), 'GET');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed');
        currentPeriods = data.periods || [];
        currentGrid    = data.grid    || {};
        currentDays    = data.days    || ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        currentDayMeta = data.day_period_meta || {};
        allTeachers    = data.teachers|| [];
        availableRooms = data.rooms   || [];
        currentTodayDayName = data.today_day_name || null;
        currentTodayHoliday = data.today_holiday   || null;
        updateRoomDropdown(availableRooms);
        updateHolidayBanner();
        renderGrid({ animate: true });
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger m-3">Failed to load grid: ${escapeHtml(e.message)}</div>`;
    }
}

function updateHolidayBanner() {
    const banner = document.getElementById('ttHolidayBanner');
    if (!banner) return;

    if (!currentTodayHoliday) {
        banner.classList.add('d-none');
        banner.innerHTML = '';
        return;
    }

    const h = currentTodayHoliday;
    banner.classList.remove('d-none', 'alert-danger', 'alert-warning');
    banner.classList.add(h.is_full_day ? 'alert-danger' : 'alert-warning');
    const detail = h.is_full_day
        ? 'No classes are expected today.'
        : `Classes are expected to end by ${(h.cutoff_time || '').slice(0, 5)} today.`;
    banner.innerHTML = `<i class="ri-calendar-event-fill fs-18"></i>
        <div><strong>Today (${escapeHtml(currentTodayDayName || '')}) is a holiday — ${escapeHtml(h.title)}.</strong> ${detail}</div>`;
}

function renderGrid(options = {}) {
    const animate   = !!options.animate;
    const container = document.getElementById(options.containerId || 'timetableGridContainer');
    const periods   = options.periods ?? currentPeriods;
    const grid      = options.grid    ?? currentGrid;
    const days      = options.days    ?? currentDays;
    const dayMeta   = options.dayMeta ?? currentDayMeta;

    if (!container) return;
    if (!periods.length) {
        container.innerHTML = '<div class="alert alert-warning m-3">No periods configured. Save settings first.</div>';
        return;
    }
    const dayThClasses = {Monday:'monday-th',Tuesday:'tuesday-th',Wednesday:'wednesday-th',Thursday:'thursday-th',Friday:'friday-th'};

    const { skipSet, spanSet } = computeDoubleSpanMap(periods, grid, days);

    let html = `<table class="tt-grid"><thead><tr>
        <th class="period-th">Period</th>
        ${days.map(d => {
            const isHolidayDay = currentTodayHoliday && d === currentTodayDayName;
            const cls = `${dayThClasses[d]||''}${isHolidayDay ? ' holiday-th' : ''}`;
            const badge = isHolidayDay ? '<span class="holiday-badge"><i class="ri-flag-fill"></i> Holiday</span>' : '';
            return `<th class="${cls}">${escapeHtml(d)}${badge}</th>`;
        }).join('')}
    </tr></thead><tbody>`;

    let cellSeq = 0;
    const buildingCells = [];

    periods.forEach(period => {
        const isBreak   = period.is_break || ['short_break','long_break'].includes(period.type);
        const startTime = (period.start_time || '').slice(0, 5);
        const endTime   = (period.end_time   || '').slice(0, 5);

        html += `<tr><td class="period-td">
            <div class="pname">${escapeHtml(period.name)}</div>
            <div class="ptime">${startTime} – ${endTime}</div>
        </td>`;

        days.forEach(day => {
            const key = `${period.id}|${day}`;
            if (skipSet.has(key)) return; // second half of a merged double — no <td> in this row

            const rowspanAttr = spanSet.has(key) ? ' rowspan="2"' : '';
            const slot   = grid[period.id]?.[day] || null;
            const isFree = !slot || slot.is_free || (!slot.subject_id && !slot.teacher_id);
            const isHolidayDay = currentTodayHoliday && day === currentTodayDayName;
            const holidayTdClass = isHolidayDay ? ' holiday-col' : '';
            cellSeq++;
            const cellId = `c${cellSeq}`;

            // A lesson period past that day's Half-Days cutoff -- render it
            // as a distinct "not in use" cell instead of an ordinary free
            // one, so the cutoff is actually visible here (the merged grid
            // export already does this with its own ttw-cell-na treatment).
            const meta = dayMeta[day]?.[period.id];
            const isNotApplicable = !isBreak && meta && meta.applicable === false;

            if (isNotApplicable) {
                html += `<td class="${holidayTdClass.trim()}"><div class="tt-cell is-na" data-cell-id="${cellId}"><span class="cell-na">Not in use</span></div></td>`;
            } else if (isBreak) {
                html += `<td class="${holidayTdClass.trim()}"><div class="tt-cell is-break"><span class="cell-break">☕ Break</span></div></td>`;
            } else if (isFree) {
                html += `<td class="${holidayTdClass.trim()}" onclick="openSlotModal(${period.id},'${day}')"${rowspanAttr}>
                    <div class="tt-cell is-free" data-cell-id="${cellId}">
                        <i class="ri-add-line ri-lg text-muted opacity-30"></i>
                        <span class="cell-free">Free</span>
                    </div></td>`;
            } else {
                const sc          = getSubjectColor(slot.subject_id);
                const borderStyle = sc ? `style="border-left:3px solid ${sc}"` : '';
                const avatarHtml  = slot.teacher_picture
                    ? `<img src="${slot.teacher_picture}" class="cell-avatar" onerror="this.style.display='none'">`
                    : `<div class="cell-avatar-placeholder"><i class="ri-user-line"></i></div>`;
                const doubleBadge = spanSet.has(key) ? '<span class="cell-double-badge">Double</span>' : '';
                const roomHtml    = slot.room_name
                    ? `<span class="cell-room"><i class="ri-door-line"></i> ${escapeHtml(slot.room_name)}</span>`
                    : '';
                const teacherHtml = slot.teacher
                    ? `<span class="cell-teacher">${escapeHtml(slot.teacher.split(' ')[0])}</span>`
                    : '';

                const animClass = animate ? ' cell-building' : '';
                if (animate) buildingCells.push(cellId);

                html += `<td class="${holidayTdClass.trim()}" onclick="openSlotModal(${period.id},'${day}')" ${borderStyle}${rowspanAttr}>
                    <div class="tt-cell has-subject${slot.is_double?' is-double':''}${animClass}" data-cell-id="${cellId}">
                        ${avatarHtml}
                        <span class="cell-subject">${escapeHtml(slot.subject_code || slot.subject || '—')}</span>
                        ${teacherHtml}${roomHtml}${doubleBadge}
                    </div></td>`;
            }
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    container.innerHTML = html;

    if (options.containerId === undefined) {
        applyStaffPictureVisibility();
    }

    if (animate && buildingCells.length) {
        playGridBuildAnimation(container, buildingCells);
    }
}

function computeDoubleSpanMap(periods, grid, days) {
    const skipSet = new Set();  // "periodId|day" — second half, don't render a <td>
    const spanSet = new Set();  // "periodId|day" — first half, render with rowspan="2"

    for (let i = 0; i < periods.length - 1; i++) {
        const cur  = periods[i];
        const next = periods[i + 1];
        if (next.type !== 'lesson') continue; // never merge across a break/assembly row

        days.forEach(day => {
            const curKey  = `${cur.id}|${day}`;
            const nextKey = `${next.id}|${day}`;
            if (skipSet.has(curKey)) return; // already consumed as someone else's second half

            const s1 = grid[cur.id]?.[day];
            const s2 = grid[next.id]?.[day];
            if (!s1 || !s2) return;
            if (!s1.is_double || !s2.is_double) return;
            if (s1.is_free || s2.is_free) return;
            if (s1.subject_id !== s2.subject_id) return;
            if (s1.teacher_id !== s2.teacher_id) return;
            if ((s1.room_id || null) !== (s2.room_id || null)) return;

            spanSet.add(curKey);
            skipSet.add(nextKey);
        });
    }
    return { skipSet, spanSet };
}

function playGridBuildAnimation(container, cellIds) {
    const banner = document.createElement('div');
    banner.className = 'tt-generating-banner';
    banner.id = 'ttGeneratingBanner';
    banner.innerHTML = `
        <div class="spinner-border text-primary"></div>
        <span id="ttGeneratingText">Placing lessons… 0 / ${cellIds.length}</span>
        <span class="tt-generating-skip" onclick="skipGridBuildAnimation()">Skip animation</span>`;
    container.prepend(banner);

    let i = 0;
    const total     = cellIds.length;
    const stepDelay = total > 60 ? 12 : total > 30 ? 20 : 35;

    window._ttBuildTimer = setInterval(() => {
        if (i >= total) {
            clearInterval(window._ttBuildTimer);
            finishGridBuildAnimation();
            return;
        }
        const el = container.querySelector(`[data-cell-id="${cellIds[i]}"]`);
        if (el) el.classList.remove('cell-building');
        i++;
        const textEl = document.getElementById('ttGeneratingText');
        if (textEl) textEl.textContent = `Placing lessons… ${i} / ${total}`;
    }, stepDelay);
}

function skipGridBuildAnimation() {
    if (window._ttBuildTimer) clearInterval(window._ttBuildTimer);
    document.querySelectorAll('#timetableGridContainer .cell-building').forEach(el => el.classList.remove('cell-building'));
    finishGridBuildAnimation();
}

function finishGridBuildAnimation() {
    const banner = document.getElementById('ttGeneratingBanner');
    if (banner) banner.remove();
}

function applyStaffPictureVisibility() {
    const show = localStorage.getItem('tt_show_staff_pictures') !== '0';
    const cb = document.getElementById('toggleStaffPictures');
    if (cb) cb.checked = show;
    document.getElementById('timetableGridContainer')?.classList.toggle('hide-avatars', !show);
}

function toggleStaffPictureVisibility() {
    const show = document.getElementById('toggleStaffPictures').checked;
    localStorage.setItem('tt_show_staff_pictures', show ? '1' : '0');
    document.getElementById('timetableGridContainer')?.classList.toggle('hide-avatars', !show);
}

// ============================================================================
// ROOM DROPDOWN (Tom Select)
// ============================================================================
function updateRoomDropdown(rooms) {
    if (roomTomSelect) {
        roomTomSelect.destroy();
        roomTomSelect = null;
    }
    const el = document.getElementById('editSlotRoom');
    if (!el) return;

    roomTomSelect = new TomSelect(el, {
        valueField: 'id',
        labelField: 'label',
        searchField: ['label', 'name', 'code'],
        options: rooms,
        create: false,
        placeholder: 'Search or select a room…',
        onChange: function() { debounceConflictCheck(); }
    });
}

// ============================================================================
// EDIT SLOT MODAL
// ============================================================================
function openSlotModal(periodId, day) {
    const period = currentPeriods.find(p => p.id == periodId);
    if (!period) return;
    const slot = currentGrid[periodId]?.[day] || {};

    document.getElementById('editSlotSettingId').value = currentSettingId;
    document.getElementById('editSlotPeriodId').value  = periodId;
    document.getElementById('editSlotDay').value       = day;

    const startFmt = (period.start_time || '').slice(0, 5);
    const endFmt   = (period.end_time   || '').slice(0, 5);
    document.getElementById('editSlotPeriodName').textContent = period.name + ' · ' + startFmt + ' – ' + endFmt;
    document.getElementById('editSlotDayName').textContent    = day;
    document.getElementById('editSlotContext').textContent    = period.name + ' · ' + day;
    document.getElementById('editSlotNotes').value            = slot.notes || '';
    document.getElementById('editSlotIsDouble').checked       = slot.is_double || false;

    resetConflictPanel();

    if (roomTomSelect) {
        roomTomSelect.setValue(slot.room_id ? slot.room_id.toString() : '', true);
    }

    const avatarDiv = document.getElementById('editTeacherAvatar');
    if (slot.teacher_picture) {
        avatarDiv.innerHTML = `<img src="${slot.teacher_picture}" style="width:44px;height:44px;border-radius:50%;object-fit:cover">`;
    } else {
        avatarDiv.innerHTML = `<i class="ri-user-line text-white ri-xl"></i>`;
    }

    const subjectSel = document.getElementById('editSlotSubject');
    subjectSel.innerHTML = '<option value="">— Free Period —</option>';
    availableSubjects.forEach(s => {
        const opt      = new Option(`${s.subject_name} (${s.teacher_name})`, s.subject_id);
        opt.dataset.teacherId   = s.teacher_id;
        opt.dataset.teacherName = s.teacher_name;
        opt.selected = (slot.subject_id == s.subject_id);
        subjectSel.appendChild(opt);
    });

    const teacherSel = document.getElementById('editSlotTeacher');
    teacherSel.innerHTML = '<option value="">— No Teacher —</option>';
    const uniqueTeachers = new Map();
    availableSubjects.forEach(s => {
        if (s.teacher_id && !uniqueTeachers.has(s.teacher_id)) uniqueTeachers.set(s.teacher_id, s.teacher_name);
    });
    uniqueTeachers.forEach((name, id) => {
        const opt = new Option(name, id);
        opt.selected = (slot.teacher_id == id);
        teacherSel.appendChild(opt);
    });

    new bootstrap.Modal(document.getElementById('editSlotModal')).show();

    if (slot.teacher_id || slot.room_id) {
        setTimeout(runRealtimeConflictCheck, 300);
    }
}

function onSubjectChange() {
    const sel = document.getElementById('editSlotSubject');
    const opt = sel.options[sel.selectedIndex];
    const tid = opt?.dataset?.teacherId;
    if (tid) document.getElementById('editSlotTeacher').value = tid;
    onTeacherChange();
    debounceConflictCheck();
}

function onTeacherChange() {
    const tid = document.getElementById('editSlotTeacher').value;
    if (!tid) { debounceConflictCheck(); return; }
    const t = allTeachers.find(t => t.id == tid);
    const avatarDiv = document.getElementById('editTeacherAvatar');
    if (t?.picture) {
        avatarDiv.innerHTML = `<img src="${t.picture}" style="width:44px;height:44px;border-radius:50%;object-fit:cover" onerror="this.parentElement.innerHTML='<i class=\\'ri-user-line text-white ri-xl\\'></i>'">`;
    }
    debounceConflictCheck();
}

// ============================================================================
// REAL-TIME CONFLICT CHECK
// ============================================================================
function debounceConflictCheck() {
    clearTimeout(conflictCheckTimer);
    const panel = document.getElementById('slotConflictPanel');
    const inner = document.getElementById('slotConflictInner');
    const teacherId = document.getElementById('editSlotTeacher').value;
    const roomId    = roomTomSelect ? roomTomSelect.getValue() : '';
    if (!teacherId && !roomId) {
        resetConflictPanel();
        return;
    }
    panel.style.display = '';
    inner.innerHTML = `<div class="rtc-spinner"><div class="spinner-border text-primary"></div><span>Checking for conflicts…</span></div>`;
    conflictCheckTimer = setTimeout(runRealtimeConflictCheck, 400);
}

async function runRealtimeConflictCheck() {
    const teacherId = document.getElementById('editSlotTeacher').value;
    const roomId    = roomTomSelect ? roomTomSelect.getValue() : '';
    const periodId  = document.getElementById('editSlotPeriodId').value;
    const day       = document.getElementById('editSlotDay').value;
    const settingId = document.getElementById('editSlotSettingId').value;

    const panel = document.getElementById('slotConflictPanel');
    const inner = document.getElementById('slotConflictInner');

    if (!teacherId && !roomId) { resetConflictPanel(); return; }

    try {
        const res  = await apiFetch(ROUTES.checkSlotConflict, 'POST', {
            setting_id: parseInt(settingId),
            period_id:  parseInt(periodId),
            day:        day,
            teacher_id: teacherId ? parseInt(teacherId) : null,
            room_id:    roomId    ? parseInt(roomId)    : null,
            subject_id: document.getElementById('editSlotSubject').value
                ? parseInt(document.getElementById('editSlotSubject').value) : null,
        });
        const data = await res.json();
        if (!data.success) return;

        inner.innerHTML = '';
        panel.style.display = '';

        data.conflicts.forEach(c => {
            const div = document.createElement('div');
            div.className = 'rtc-panel ' + (c.severity === 'error' ? 'rtc-error' : 'rtc-warning');

            let altsHtml = '';
            if (c.alternatives?.length) {
                altsHtml += '<div class="rtc-alts">'
                    + c.alternatives.slice(0, 4).map(a =>
                        `<span class="rtc-alt-badge" onclick="closeModalAndOpenSlot(${a.period_id}, '${escapeHtml(a.day)}')">
                            📅 ${escapeHtml(a.day)} · ${escapeHtml(a.period_name)}
                        </span>`
                    ).join('') + '</div>';
            }
            if (c.alternative_rooms?.length) {
                altsHtml += '<div class="rtc-alts" style="margin-top:4px">'
                    + c.alternative_rooms.slice(0, 4).map(r =>
                        `<span class="rtc-room-alt" onclick="switchToRoom(${r.id}, '${escapeHtml(r.label)}')">
                            🏫 ${escapeHtml(r.label)}
                        </span>`
                    ).join('') + '</div>';
            }

            div.innerHTML = `
                <div class="rtc-icon">${c.icon}</div>
                <div class="rtc-body">
                    <div class="rtc-msg">${escapeHtml(c.message)}</div>
                    ${c.detail ? `<div class="rtc-detail">${escapeHtml(c.detail)}</div>` : ''}
                    ${altsHtml}
                </div>`;
            inner.appendChild(div);
        });

        data.warnings.forEach(w => {
            const div = document.createElement('div');
            const isCombined = w.type === 'combined_session';
            div.className = 'rtc-panel ' + (isCombined ? 'rtc-clear' : 'rtc-warning');
            div.innerHTML = `<div class="rtc-icon">${w.icon}</div>
                <div class="rtc-body"><div class="rtc-msg${isCombined ? ' green' : ''}">${escapeHtml(w.message)}</div></div>`;
            inner.appendChild(div);
        });

        if (!data.conflicts.length && !data.warnings.length) {
            inner.innerHTML = `<div class="rtc-panel rtc-clear">
                <div class="rtc-icon">✅</div>
                <div class="rtc-body"><div class="rtc-msg green">No conflicts detected for this slot.</div></div>
            </div>`;
        }

        const saveBtn = document.getElementById('saveSlotBtn');
        if (data.has_error) {
            saveBtn.innerHTML = '<i class="ri-alert-line me-2"></i>Save Anyway (Override)';
            saveBtn.className = 'btn btn-danger px-4';
        } else {
            saveBtn.innerHTML = '<i class="ri-save-line me-2"></i>Save Slot';
            saveBtn.className = 'btn btn-primary px-4';
        }

    } catch (e) {
        inner.innerHTML = '';
    }
}

function resetConflictPanel() {
    document.getElementById('slotConflictPanel').style.display = 'none';
    document.getElementById('slotConflictInner').innerHTML = '';
    const saveBtn = document.getElementById('saveSlotBtn');
    if (saveBtn) {
        saveBtn.innerHTML = '<i class="ri-save-line me-2"></i>Save Slot';
        saveBtn.className = 'btn btn-primary px-4';
    }
}

function closeModalAndOpenSlot(periodId, day) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('editSlotModal'));
    if (modal) modal.hide();
    loadTimetableGrid().then(() => openSlotModal(periodId, day));
}

function switchToRoom(roomId, label) {
    if (!roomTomSelect) return;
    const idStr = roomId.toString();
    if (!roomTomSelect.getOption(idStr)) {
        roomTomSelect.addOption({ value: idStr, label: label });
    }
    roomTomSelect.setValue(idStr);
}

async function silentConflictCheck() {
    if (!currentSettingId) return;
    try {
        const res  = await apiFetch(url(ROUTES.checkConflicts, currentSettingId), 'GET');
        const data = await res.json();
        if (!data.success) return;
        const badge = document.getElementById('conflictBadgeTab');
        if (data.conflict_count > 0) {
            badge.style.display = '';
            badge.textContent   = data.conflict_count;
        } else {
            badge.style.display = 'none';
        }
    } catch (e) { /* silent */ }
}

// ============================================================================
// SAVE SLOT
// ============================================================================
async function saveSlot() {
    const roomId = roomTomSelect ? (roomTomSelect.getValue() || null) : null;
    const payload = {
        setting_id: parseInt(document.getElementById('editSlotSettingId').value),
        expected_updated_at: currentSettingVersion,
        period_id:  parseInt(document.getElementById('editSlotPeriodId').value),
        day:        document.getElementById('editSlotDay').value,
        subject_id: document.getElementById('editSlotSubject').value || null,
        teacher_id: document.getElementById('editSlotTeacher').value || null,
        room_id:    roomId ? parseInt(roomId) : null,
        notes:      document.getElementById('editSlotNotes').value || null,
        is_double:  document.getElementById('editSlotIsDouble').checked,
    };

    showLoader();
    try {
        const res    = await apiFetch(ROUTES.saveSlot, 'POST', payload);
        const result = await res.json();

        if (result.success) {
            currentSettingVersion = result.setting_updated_at;
            hideLoader();
            bootstrap.Modal.getInstance(document.getElementById('editSlotModal'))?.hide();
            await loadTimetableGrid();
            silentConflictCheck();
            AppleAlert.saved('Slot saved');
            return;
        }

        if (result.has_version_conflict) {
            hideLoader();
            bootstrap.Modal.getInstance(document.getElementById('editSlotModal'))?.hide();
            return handleVersionConflict(result);
        }

        if (result.has_conflict) {
            hideLoader();

            const isRoomConflict = (result.conflict_type || '').startsWith('room');
            const icon           = isRoomConflict ? '🏫' : '⚠️';
            const title          = isRoomConflict ? 'Room already in use' : 'Teacher conflict detected';

            let altsHtml = '';
            if (result.alternatives?.length) {
                altsHtml += `<p style="margin:12px 0 6px;font-size:12px;font-weight:600;color:#0F172A;text-align:left">Available alternative slots</p>
                    <div class="apple-alert-badge-row" style="justify-content:flex-start">`
                    + result.alternatives.slice(0, 5).map(a =>
                        `<span class="apple-alert-badge">📅 ${escapeHtml(a.day)} · ${escapeHtml(a.period_name)}</span>`
                    ).join('') + '</div>';
            }
            if (result.alternative_rooms?.length) {
                altsHtml += `<p style="margin:12px 0 6px;font-size:12px;font-weight:600;color:#0F172A;text-align:left">Available alternative rooms</p>
                    <div class="apple-alert-badge-row" style="justify-content:flex-start">`
                    + result.alternative_rooms.slice(0, 4).map(r =>
                        `<span class="apple-alert-badge interactive" onclick="switchToRoom(${r.id}, '${escapeHtml(r.label)}')">🏫 ${escapeHtml(r.label)}</span>`
                    ).join('') + '</div>';
            }

            const confirmed = await AppleAlert.rich({
                title: `${icon} ${title}`,
                html: `<p style="text-align:left">${escapeHtml(result.message)}</p>${altsHtml}
                       <p style="margin-top:14px;font-size:12px;color:#94A3B8;text-align:left">Override to save anyway, or cancel to pick something else.</p>`,
                showCancelButton: true,
                confirmText: 'Override & Save',
                cancelText: 'Cancel',
                theme: 'destructive',
                width: 520,
            });

            if (!confirmed.isConfirmed) return;

            showLoader();
            const res2    = await apiFetch(ROUTES.saveSlot, 'POST', { ...payload, force_save: true });
            const result2 = await res2.json();
            if (result2.success) {
                currentSettingVersion = result2.setting_updated_at;
                hideLoader();
                bootstrap.Modal.getInstance(document.getElementById('editSlotModal'))?.hide();
                await loadTimetableGrid();
                silentConflictCheck();
                AppleAlert.saved('Slot saved (override)');
            } else if (result2.has_version_conflict) {
                hideLoader();
                bootstrap.Modal.getInstance(document.getElementById('editSlotModal'))?.hide();
                return handleVersionConflict(result2);
            } else {
                hideLoader();
                AppleAlert.error('Save failed', result2.message || 'Please try again.');
            }
            return;
        }

        hideLoader();
        AppleAlert.error('Save failed', result.message || 'Please try again.');
    } catch (e) {
        hideLoader();
        AppleAlert.error('Save failed', e.message);
    }
}

// ============================================================================
// CONFLICT CHECKER TAB
// ============================================================================
async function checkConflicts() {
    if (!currentSettingId) return;
    showLoader();
    try {
        const res  = await apiFetch(url(ROUTES.checkConflicts, currentSettingId), 'GET');
        const data = await res.json();
        if (!data.success) {
            hideLoader();
            AppleAlert.error('Check failed', data.message || 'Please try again.');
            return;
        }

        const container = document.getElementById('conflictsList');
        const badge     = document.getElementById('conflictBadgeTab');

        if (data.checked_at) {
            document.getElementById('conflictCheckedAt').style.display = '';
            document.getElementById('conflictCheckedAtText').textContent = 'Last checked: ' + data.checked_at;
        }

        if (!data.conflict_count) {
            badge.style.display = 'none';
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="ri-check-double-line ri-3x d-block mb-3 text-success"></i>
                    <h6 class="text-success">No Conflicts Found</h6>
                    <p class="text-muted mb-0">All teachers and rooms are properly scheduled with no overlaps, and no subject repeats abnormally, across any class.</p>
                </div>`;
            hideLoader();
            return;
        }

        badge.style.display = '';
        badge.textContent   = data.conflict_count;

        document.getElementById('conflictsList').innerHTML = renderConflictsHtml(data);
        hideLoader();
    } catch (e) {
        hideLoader();
        AppleAlert.error('Check failed', e.message);
    }
}

function openConflictScopeModal() {
    document.getElementById('conflictScopeResults').innerHTML = `
        <div class="text-center py-4 text-muted">
            <i class="ri-shield-check-line ri-2x d-block mb-2 opacity-30"></i>
            <p>Select a session and run the check.</p>
        </div>`;
    new bootstrap.Modal(document.getElementById('conflictScopeModal')).show();
}

async function runScopeConflictCheck() {
    const sessionId = document.getElementById('ccSessionId').value;
    const termId    = document.getElementById('ccTermId').value;
    if (!sessionId) return AppleAlert.warning('Required', 'Please select a session.');

    const container = document.getElementById('conflictScopeResults');
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger"></div><p class="mt-3 text-muted">Scanning all classes…</p></div>';

    try {
        const params = new URLSearchParams({ session_id: sessionId });
        if (termId) params.set('term_id', termId);
        const res  = await apiFetch(`${ROUTES.checkConflictsScope}?${params.toString()}`, 'GET');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed');
        container.innerHTML = renderConflictsHtml(data);
    } catch (e) {
        container.innerHTML = `<div class="alert alert-danger m-0">Failed: ${escapeHtml(e.message)}</div>`;
    }
}

// Resolves a 'subject_spread' anomaly by freeing the extra/non-adjacent
// period(s) server-side (see TimetableController::resolveSubjectSpread —
// it only ever frees slots, never moves or reassigns anything, so it
// cannot introduce a new teacher/room conflict or a new anomaly), then
// re-runs whichever check this button's card came from so the list
// reflects the real, current state instead of being patched in place.
async function resolveSubjectSpreadAnomaly(btn) {
    const settingId   = btn.dataset.settingId;
    const day         = btn.dataset.day;
    const subjectId   = btn.dataset.subjectId;
    const subjectName = btn.dataset.subjectName || 'this subject';
    const className    = btn.dataset.className || 'this class';

    if (!settingId || !subjectId) {
        return AppleAlert.error('Cannot resolve', 'Missing data for this anomaly — please re-run the check and try again.');
    }

    const ok = await AppleAlert.confirm(
        `Resolve ${subjectName} on ${day}?`,
        `This keeps a genuine double period if one exists (otherwise just the earliest occurrence), and frees every other period ${subjectName} was placed in on ${day} for ${className}. Nothing else on the timetable is touched, so this can't create a new conflict — the freed period(s) simply become open again.`
    );
    if (!ok.isConfirmed) return;

    const inScopeModal = !!btn.closest('#conflictScopeResults');
    const originalHtml = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
        const res  = await apiFetch(ROUTES.resolveSubjectSpread, 'POST', {
            setting_id: settingId,
            day:        day,
            subject_id: subjectId,
        });
        const data = await res.json();

        if (!data.success) {
            btn.disabled  = false;
            btn.innerHTML = originalHtml;
            return AppleAlert.error('Could not resolve', data.message || 'Please try again.');
        }

        AppleAlert.toast(data.message || 'Resolved.', 'success');

        if (inScopeModal) {
            await runScopeConflictCheck();
        } else {
            await checkConflicts();
        }
    } catch (e) {
        btn.disabled  = false;
        btn.innerHTML = originalHtml;
        AppleAlert.error('Could not resolve', e.message);
    }
}

function renderConflictsHtml(data) {
    if (!data.conflict_count) {
        return `<div class="text-center py-4">
            <i class="ri-check-double-line ri-3x d-block mb-3 text-success"></i>
            <h6 class="text-success">No Conflicts Found</h6>
            <p class="text-muted mb-0">All teachers and rooms are properly scheduled with no overlaps, and no subject repeats abnormally.</p>
        </div>`;
    }

    const teacherConflicts = data.conflicts.filter(c => c.conflict_category === 'teacher');
    const roomConflicts    = data.conflicts.filter(c => c.conflict_category === 'room');
    const subjectSpread    = data.conflicts.filter(c => c.conflict_category === 'subject_spread');

    let html = `<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <i class="ri-alert-line ri-xl"></i>
        Found <strong class="mx-1">${data.conflict_count}</strong> issue(s)
        ${teacherConflicts.length ? `<span class="badge bg-danger ms-1">${teacherConflicts.length} teacher</span>` : ''}
        ${roomConflicts.length    ? `<span class="badge bg-warning text-dark ms-1">${roomConflicts.length} room</span>` : ''}
        ${subjectSpread.length    ? `<span class="badge bg-info text-dark ms-1">${subjectSpread.length} subject spread</span>` : ''}
    </div>`;

    data.conflicts.forEach(c => {
        // Subject-spread anomalies (a subject repeating for the same class
        // on the same day, either more than twice or not genuinely
        // back-to-back) have a different shape from a teacher/room
        // double-booking -- one class/subject/day plus a LIST of periods,
        // not an A-vs-B pairing -- so they get their own render branch.
        if (c.conflict_category === 'subject_spread') {
            const isExcess = c.type === 'subject_spread_excess';
            const periodsHtml = (c.periods || []).map(p =>
                `<span class="alt-badge">📅 ${escapeHtml(p.period_name)} (${escapeHtml(p.period_time)})</span>`
            ).join('');

            html += `<div class="conflict-item subject-spread-conflict">
                <div class="conflict-avatar-ph" style="background:#FEF3C7">
                    <i class="ri-repeat-line ri-xl" style="color:#B45309"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">
                        ${escapeHtml(c.subject_a || '—')}
                        <span class="badge bg-warning-subtle text-warning ms-1" style="font-size:10px">
                            ${isExcess ? 'Repeated too many times' : 'Not back-to-back'}
                        </span>
                    </div>
                    <div class="text-danger fw-semibold" style="font-size:12px">
                        <i class="ri-time-line me-1"></i>${escapeHtml(c.day)}
                        <span class="badge bg-primary-subtle text-primary ms-1">${escapeHtml(c.class_a || '')}</span>
                        ${c.teacher && c.teacher !== '—' ? ' · ' + escapeHtml(c.teacher) : ''}
                    </div>
                    <div class="alt-badges mt-1">${periodsHtml}</div>
                    <div class="mt-2 text-muted" style="font-size:12px">
                        <i class="ri-information-line me-1"></i>${escapeHtml(c.resolution_suggestion)}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success mt-2"
                        onclick="resolveSubjectSpreadAnomaly(this)"
                        data-setting-id="${c.setting_a_id ?? ''}"
                        data-day="${escapeHtml(c.day || '')}"
                        data-subject-id="${c.subject_id ?? ''}"
                        data-subject-name="${escapeHtml(c.subject_a || 'this subject')}"
                        data-class-name="${escapeHtml(c.class_a || 'this class')}"
                        ${(c.setting_a_id && c.subject_id) ? '' : 'disabled'}>
                        <i class="ri-magic-line me-1"></i>Resolve
                    </button>
                </div>
            </div>`;
            return;
        }

        const isRoomConflict = c.conflict_category === 'room';
        const avatarHtml     = isRoomConflict
            ? `<div class="conflict-avatar-ph room"><i class="ri-home-3-line ri-xl" style="color:#EA580C"></i></div>`
            : (c.teacher_picture
                ? `<img src="${c.teacher_picture}" class="conflict-avatar">`
                : `<div class="conflict-avatar-ph"><i class="ri-user-line ri-xl"></i></div>`);

        const crossArmBadge = c.is_cross_arm
            ? `<span class="badge bg-warning-subtle text-warning ms-1" style="font-size:10px"><i class="ri-git-branch-line"></i> Cross-Arm</span>` : '';

        const classesHtml = (c.all_classes && c.all_classes.length > 2)
            ? c.all_classes.map(cls => `<span class="badge bg-primary-subtle text-primary me-1">${escapeHtml(cls)}</span>`).join('')
            : `<span class="badge bg-primary-subtle text-primary">${escapeHtml(c.class_a || '')}</span>
               <span class="mx-1 text-muted">&amp;</span>
               <span class="badge bg-primary-subtle text-primary">${escapeHtml(c.class_b || '')}</span>`;

        const altHtml = c.alternatives?.length
            ? `<div class="conflict-suggestion">
                   <div><i class="ri-lightbulb-line text-success me-1"></i><strong>Suggestion:</strong> ${escapeHtml(c.resolution_suggestion)}</div>
                   <div class="alt-badges">
                       ${c.alternatives.slice(0, 4).map(a =>
                           `<span class="alt-badge">📅 ${escapeHtml(a.day)} · ${escapeHtml(a.period_name)} (${escapeHtml(a.period_time)})</span>`
                       ).join('')}
                   </div>
               </div>`
            : `<div class="mt-2 text-muted" style="font-size:12px"><i class="ri-information-line me-1"></i>${escapeHtml(c.resolution_suggestion)}</div>`;

        html += `<div class="conflict-item ${isRoomConflict ? 'room-conflict' : ''}">
            ${avatarHtml}
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1">
                    ${escapeHtml(c.teacher || '—')} ${crossArmBadge}
                    ${isRoomConflict ? '<span class="badge bg-warning-subtle text-warning ms-1" style="font-size:10px">Room Conflict</span>' : ''}
                </div>
                <div class="text-danger fw-semibold" style="font-size:12px">
                    <i class="ri-time-line me-1"></i>${escapeHtml(c.day)} · ${escapeHtml(c.period)}
                    ${c.period_time ? ' (' + escapeHtml(c.period_time) + ')' : ''}
                </div>
                <div class="mt-1" style="font-size:12px">
                    ${classesHtml}
                    <span class="text-muted ms-2">${escapeHtml(c.subject_a || '—')} vs ${escapeHtml(c.subject_b || '—')}</span>
                </div>
                ${altHtml}
            </div>
        </div>`;
    });

    return html;
}

function switchToGridAndOpen(periodId, day) {
    showTab('gridTab', document.querySelectorAll('.tt-tab')[2]);
    loadTimetableGrid().then(() => openSlotModal(periodId, day));
}

// ============================================================================
// NOTIFICATIONS / EXPORT / DELETE / CLONE
// ============================================================================
async function sendNotifications() {
    const ok = await AppleAlert.confirm(
        'Send notifications?',
        'Every teacher assigned to this timetable will receive an email.'
    );
    if (!ok.isConfirmed) return;

    showLoader();
    try {
        const res  = await apiFetch(ROUTES.sendNotifications, 'POST', { setting_id: currentSettingId, type: 'weekly_preview' });
        const data = await res.json();
        hideLoader();
        if (data.success) {
            AppleAlert.saved(data.message || 'Notifications sent');
        } else {
            AppleAlert.error('Could not send', data.message || 'Please try again.');
        }
    } catch (e) {
        hideLoader();
        AppleAlert.error('Could not send', e.message);
    }
}

function exportTimetable(format) {
    if (!currentSettingId) return AppleAlert.error('No timetable loaded');
    const orientation = document.getElementById('exportOrientation')?.value || 'horizontal';
    const paper       = document.getElementById('exportPaper')?.value       || 'a3';
    const exportUrl   = url(ROUTES.export, currentSettingId)
                      + '?format=' + format
                      + '&orientation=' + encodeURIComponent(orientation)
                      + '&paper=' + encodeURIComponent(paper);
    if (format === 'pdf') window.open(exportUrl, '_blank');
    else window.location.href = exportUrl;
}

function openWholeSchoolExportModal() {
    selectWsMode(document.querySelector('.ws-mode-btn[data-mode="per_class"]'));
    new bootstrap.Modal(document.getElementById('wholeSchoolExportModal')).show();
}

function selectWsMode(btn) {
    document.querySelectorAll('.ws-mode-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const mode = btn.dataset.mode;
    document.getElementById('wholeSchoolMode').value = mode;
    document.getElementById('wsOrientationWrap').style.display = '';
    document.getElementById('wsPaperWrap').style.display = '';
    // Layout choice (Days as Columns / Days as Rows / Columns per Class / Rows per Class)
    // only makes sense once "Merged Grid" mode is selected.
    document.getElementById('wsLayoutWrap').style.display = (mode === 'merged') ? '' : 'none';
}

function exportWholeSchoolTimetable(type = 'pdf') {
    const sessionId   = document.getElementById('wholeSchoolSessionId').value;
    const termId      = document.getElementById('wholeSchoolTermId').value;
    const orientation = document.getElementById('wholeSchoolOrientation').value;
    const paper       = document.getElementById('wholeSchoolPaper').value;
    const mode        = document.getElementById('wholeSchoolMode').value;
    const layout      = document.getElementById('wholeSchoolLayout').value;

    if (!sessionId) return AppleAlert.warning('Required', 'Please select a session.');

    const base = mode === 'merged'
        ? (type === 'web' ? ROUTES.mergedGridWeb : ROUTES.exportMergedGrid)
        : (type === 'web' ? ROUTES.exportWholeSchoolWeb : ROUTES.exportWholeSchool);

    const qs = `?session_id=${encodeURIComponent(sessionId)}`
             + `&term_id=${encodeURIComponent(termId || '')}`
             + `&orientation=${encodeURIComponent(orientation)}`
             + `&paper=${encodeURIComponent(paper)}`
             + (mode === 'merged' ? `&layout=${encodeURIComponent(layout)}` : '');

    window.open(base + qs, '_blank');
}

async function deleteSetting(settingId, updatedAt) {
    const ok = await AppleAlert.confirmDelete(
        'Delete this timetable?',
        'Every slot in this timetable will be permanently removed.'
    );
    if (!ok) return;

    showLoader();
    try {
        const res  = await apiFetch(url(ROUTES.deleteSetting, settingId), 'DELETE', { expected_updated_at: updatedAt });
        const data = await res.json();
        hideLoader();
        if (data.success) {
            AppleAlert.deleted('Timetable deleted');
            setTimeout(() => location.reload(), 900);
        } else if (data.has_version_conflict) {
            const r = await AppleAlert.confirm(
                'Changed since you last saw it',
                data.message + '  Reload the list?',
                { confirmText: 'Reload' }
            );
            if (r.isConfirmed) location.reload();
        } else {
            AppleAlert.error('Could not delete', data.message || 'Please try again.');
        }
    } catch (e) {
        hideLoader();
        AppleAlert.error('Could not delete', e.message);
    }
}

function cloneSetting(settingId) {
    pendingCloneId = settingId;
    new bootstrap.Modal(document.getElementById('cloneModal')).show();
}

async function confirmClone(force = false) {
    if (!pendingCloneId) return;
    if (!force) bootstrap.Modal.getInstance(document.getElementById('cloneModal'))?.hide();

    const settingId = pendingCloneId;
    showLoader();
    try {
        const res  = await apiFetch(ROUTES.cloneSetting, 'POST', {
            setting_id:     settingId,
            new_session_id: document.getElementById('cloneSessionId').value || null,
            new_term_id:    document.getElementById('cloneTermId').value    || null,
            force,
        });
        const data = await res.json();
        hideLoader();

        if (data.success) {
            pendingCloneId = null;
            AppleAlert.saved('Timetable cloned');
            setTimeout(() => location.reload(), 900);
            return;
        }

        if (data.is_being_edited) {
            const confirmResult = await AppleAlert.confirm(
                'Being edited',
                data.message,
                { confirmText: 'Clone anyway' }
            );
            if (confirmResult.isConfirmed) {
                pendingCloneId = settingId;
                return confirmClone(true);
            }
            pendingCloneId = null;
            return;
        }

        pendingCloneId = null;
        AppleAlert.error('Clone failed', data.message || 'Please try again.');
    } catch (e) {
        hideLoader();
        pendingCloneId = null;
        AppleAlert.error('Clone failed', e.message);
    }
}

// ============================================================================
// GENERATION WIZARD
// ============================================================================
function openGenerationWizardModal() {
    document.getElementById('wizHalfDaysBody').innerHTML = '';
    const formEl     = document.getElementById('wizFormContent');
    const progressEl = document.getElementById('wizGenerationProgress');
    const previewEl  = document.getElementById('wizPreviewPane');
    if (formEl)     formEl.style.display = '';
    if (progressEl) progressEl.style.display = 'none';
    if (previewEl)  previewEl.style.display = 'none';
    new bootstrap.Modal(document.getElementById('generationWizardModal')).show();
    // In case a session was already picked before the modal was reopened
    // (e.g. the select kept its value), refresh the saved-set picker right
    // away instead of waiting for "Load Subjects" to be clicked.
    refreshWizardAllocationPicker();
}

function toggleWizardClassPicker() {
    document.getElementById('wizClassPickerWrap').style.display =
        document.getElementById('wizScope').value === 'selected' ? '' : 'none';
}

function toggleWizardAssemblyDay() {
    document.getElementById('wizAssemblyDayWrap').style.display =
        document.getElementById('wizAssemblyFirstPeriod').checked ? '' : 'none';
}

function addWizardHalfDayRow() {
    const wrap = document.getElementById('wizHalfDaysBody');
    const row  = document.createElement('div');
    row.className = 'row g-2 align-items-center mb-2 wiz-half-day-row';
    row.innerHTML = `
        <div class="col-md-5">
            <select class="form-select form-select-sm half-day-select">
                ${['Monday','Tuesday','Wednesday','Thursday','Friday'].map(d => `<option value="${d}">${d}</option>`).join('')}
            </select>
        </div>
        <div class="col-md-5">
            <input type="number" class="form-control form-control-sm half-day-lessons" min="1" placeholder="Lessons that day">
        </div>
        <div class="col-md-2 text-end">
            <button class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.wiz-half-day-row').remove()">
                <i class="ri-delete-bin-line"></i>
            </button>
        </div>`;
    wrap.appendChild(row);
}

function getWizardHalfDays() {
    return [...document.querySelectorAll('.wiz-half-day-row')].map(row => {
        const day     = row.querySelector('.half-day-select').value;
        const lessons = parseInt(row.querySelector('.half-day-lessons').value);
        return (day && lessons) ? { day, lessons } : null;
    }).filter(Boolean);
}

function buildWizardResultsSummary(results) {
    if (!Array.isArray(results) || !results.length) return '';
    const skipped = results.filter(r => r.skipped);
    const applied = results.filter(r => !r.skipped);

    const classNameById = {};
    document.querySelectorAll('#wizClassIds option').forEach(opt => {
        classNameById[opt.value] = opt.textContent.trim();
    });
    const nameFor = (id) => classNameById[id] || `Class #${id}`;

    let html = `<div class="text-start mt-2" style="font-size:12px">
        <div class="text-success mb-1"><i class="ri-checkbox-circle-line"></i> Applied to ${applied.length} class(es)</div>`;

    if (skipped.length) {
        html += `<div class="text-warning mb-1"><i class="ri-alert-line"></i> Skipped ${skipped.length} class(es) — published/locked:</div>
            <ul class="mb-0 ps-4">
                ${skipped.map(s => `<li>${escapeHtml(nameFor(s.schoolclass_id))}</li>`).join('')}
            </ul>`;
    }
    html += '</div>';
    return html;
}

function buildGenerationIssuesHtml(classes) {
    const flagged = (classes || []).filter(c => c.issue || !c.placed || (c.no_teacher || []).length);
    if (!flagged.length) return '';
    return `<div class="text-start mt-2" style="font-size:12px">
        <div class="text-warning mb-1"><i class="ri-alert-line"></i> ${flagged.length} class(es) need attention:</div>
        <ul class="mb-0 ps-4" style="max-height:180px;overflow:auto">
            ${flagged.map(c => {
                const bits = [];
                if (c.issue) bits.push(escapeHtml(c.issue));
                else if (!c.placed) bits.push('Nothing was placed.');
                if ((c.no_teacher || []).length) bits.push('No teacher for: ' + c.no_teacher.map(escapeHtml).join(', '));
                return `<li><strong>${escapeHtml(c.class_name)}</strong> — ${bits.join(' ')}</li>`;
            }).join('')}
        </ul>
    </div>`;
}

function animateWizardResults(results) {
    return new Promise((resolve) => {
        const formEl     = document.getElementById('wizFormContent');
        const progressEl = document.getElementById('wizGenerationProgress');
        const listEl     = document.getElementById('wizProgressList');
        if (!formEl || !progressEl || !listEl || !results?.length) return resolve();

        formEl.style.display = 'none';
        progressEl.style.display = '';
        listEl.innerHTML = results.map((r, i) => `
            <div class="d-flex align-items-center gap-2 py-2 px-1" style="border-bottom:1px solid #F1F5F9;font-size:13px">
                <span class="spinner-border spinner-border-sm text-primary" id="wizProgSpinner${i}"></span>
                <i class="ri-checkbox-circle-fill text-success" id="wizProgCheck${i}" style="display:none"></i>
                <span class="flex-grow-1">${escapeHtml(r.class_name)}</span>
                <span class="text-muted" id="wizProgDetail${i}"></span>
            </div>`).join('');

        let i = 0;
        const stepDelay = results.length > 20 ? 90 : 180;
        const timer = setInterval(() => {
            if (i >= results.length) {
                clearInterval(timer);
                setTimeout(resolve, 400);
                return;
            }
            const spinner = document.getElementById(`wizProgSpinner${i}`);
            const check   = document.getElementById(`wizProgCheck${i}`);
            const detail  = document.getElementById(`wizProgDetail${i}`);
            const r = results[i];
            if (spinner) spinner.style.display = 'none';
            if (check) {
                check.style.display = '';
                if (r.issue || !r.placed) {
                    check.className = 'ri-error-warning-fill text-warning';
                    check.title = r.issue || 'Nothing was placed for this class.';
                }
            }
            if (detail)  detail.textContent = r.lesson_slots
                ? `${r.placed}/${r.lesson_slots} filled${r.unplaced?.length ? `, ${r.unplaced.length} short` : ''}`
                : `${r.placed} placed${r.unplaced?.length ? `, ${r.unplaced.length} short` : ''}`;
            i++;
        }, stepDelay);
    });
}

// ============================================================================
// WIZARD: SUBJECTS & PRIORITY PANEL
// ============================================================================
let wizardSubjectsState = {};

async function loadWizardSubjects() {
    const sessionId = document.getElementById('wizSessionId').value;
    const termId    = document.getElementById('wizTermId').value;
    if (!sessionId) return AppleAlert.warning('Required', 'Please select a session first.');

    const scope = document.getElementById('wizScope').value;
    const classIds = scope === 'selected'
        ? [...document.getElementById('wizClassIds').selectedOptions].map(o => parseInt(o.value))
        : null;

    if (scope === 'selected' && (!classIds || !classIds.length)) {
        return AppleAlert.warning('Required', 'Select at least one class, or switch scope to "All Classes".');
    }

    const effectiveClassIds = classIds ?? [...document.getElementById('wizClassIds').options]
        .map(o => parseInt(o.value));

    if (!effectiveClassIds.length) {
        return AppleAlert.warning('No classes', 'No classes available in this scope.');
    }

    const panel = document.getElementById('wizSubjectsPanel');
    panel.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading subjects…</div>';

    try {
        const params = new URLSearchParams();
        params.set('session_id', sessionId);
        if (termId) params.set('term_id', termId);
        effectiveClassIds.forEach(id => params.append('schoolclass_ids[]', id));
        if (document.getElementById('wizIncludeUnassigned')?.checked) params.set('include_unassigned', '1');

        const res = await fetch(`${ROUTES.wizardData}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load subjects.');

        wizardSubjectsState = {};
        data.classes.forEach(c => {
            wizardSubjectsState[c.schoolclass_id] = {
                setting_id: c.setting_id,
                setting_exists: c.setting_exists,
                subjects: c.subjects,
            };
        });

        renderWizardSubjectsPanel(data.classes, data.priority_levels);
        refreshWizardAllocationPicker();
    } catch (e) {
        panel.innerHTML = `<div class="alert alert-danger m-0">Failed: ${escapeHtml(e.message)}</div>`;
    }
}

function renderWizardSubjectsPanel(classes, levels) {
    const panel = document.getElementById('wizSubjectsPanel');
    if (!classes.length) {
        panel.innerHTML = '<div class="text-center py-4 text-muted"><i class="ri-information-line ri-2x d-block mb-2 opacity-30"></i>No subjects assigned to any class in this scope.</div>';
        return;
    }

    let html = '';
    classes.forEach(cls => {
        const classId = cls.schoolclass_id;
        const compulsoryCount = cls.subjects.filter(s => s.is_compulsory).length;
        html += `<div class="wiz-class-card">
            <div class="wiz-class-hdr" onclick="toggleWizClassCard(${classId})">
                <h6><i class="ri-arrow-down-s-line me-1 wiz-caret" id="wizCaret_${classId}"></i>${escapeHtml(cls.class_name)}</h6>
                <div>
                    <span class="badge bg-light text-dark">${cls.subjects.length} subjects</span>
                    ${compulsoryCount ? `<span class="badge bg-warning text-dark ms-1">${compulsoryCount} compulsory</span>` : ''}
                </div>
            </div>
            <div class="wiz-class-body" id="wizClassBody_${classId}" style="display:none">
                <div class="text-muted mb-2" style="font-size:11.5px;">
                    <i class="ri-information-line me-1"></i>
                    Compulsory subjects are highlighted. Priority rows only apply if <em>Use Priority</em> is on.
                </div>`;

        cls.subjects.forEach(s => {
            const sid = s.subject_id;
            const subjectNameEsc = escapeHtml(s.subject_name).replace(/'/g, "\\'");
            const classNameEsc = escapeHtml(cls.class_name).replace(/'/g, "\\'");

            const roomHint = s.mapped_rooms_subject.length
                ? `<div class="wiz-mapped-rooms"><i class="ri-door-line me-1"></i>${s.mapped_rooms_subject.map(r => escapeHtml(r.name)).join(', ')} <a href="#" onclick="event.preventDefault();openQuickMapRoom(${classId}, ${sid}, '${subjectNameEsc}', '${classNameEsc}')" style="font-size:10px;margin-left:4px">＋ map</a></div>`
                : (s.mapped_rooms_generic.length
                    ? `<div class="wiz-mapped-rooms"><i class="ri-door-line me-1"></i>${s.mapped_rooms_generic.map(r => escapeHtml(r.name)).join(', ')} <em>(any subject)</em> <a href="#" onclick="event.preventDefault();openQuickMapRoom(${classId}, ${sid}, '${subjectNameEsc}', '${classNameEsc}')" style="font-size:10px;margin-left:4px">＋ map</a></div>`
                    : `<div class="wiz-mapped-rooms none"><i class="ri-alert-line me-1"></i>No mapped rooms <a href="#" onclick="event.preventDefault();openQuickMapRoom(${classId}, ${sid}, '${subjectNameEsc}', '${classNameEsc}')" style="font-size:10px;margin-left:4px">＋ map</a></div>`);

            const priorityOptions = Object.entries(levels).map(([level, label]) => {
                const selected = s.use_priority && s.priority_level == level;
                return `<option value="${level}" ${selected ? 'selected' : ''}>Level ${level} — ${label}</option>`;
            }).join('');

            const isPending  = !!s.is_pending;
            const rowClasses = `wiz-subj-row ${s.is_compulsory ? 'is-compulsory' : ''} ${isPending ? 'wiz-subj-row-pending' : ''}`.trim();
            const leadCell   = isPending
                ? `<input type="checkbox" class="form-check-input wiz-pending-include" id="wizPendingInclude_${classId}_${sid}"
                          onchange="toggleWizPendingInclude(${classId}, ${sid}, this.checked)">`
                : (s.is_compulsory ? '<span class="wiz-compulsory-badge">COMP</span>' : '');

            html += `<div class="${rowClasses}" data-class-id="${classId}" data-subject-id="${sid}">
                <div>${leadCell}</div>
                <div>
                    <div class="wiz-subj-name">${escapeHtml(s.subject_name)}${isPending ? '<span class="pa-pending-badge">Not on this class yet</span>' : ''}</div>
                    <div class="wiz-subj-teacher">${escapeHtml(s.teacher_name)}</div>
                    ${roomHint}
                </div>
                <div>
                    <input type="number" class="form-control form-control-sm wiz-subj-num"
                           min="1" max="20"
                           id="wizPpw_${classId}_${sid}"
                           value="${s.periods_per_week}" ${isPending ? 'disabled' : ''}>
                    <small class="text-muted">periods/week</small>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           id="wizDouble_${classId}_${sid}"
                           ${s.allow_double_period ? 'checked' : ''}
                           onchange="toggleWizDouble(${classId}, ${sid}, this.checked)" ${isPending ? 'disabled' : ''}>
                    <label class="form-check-label" for="wizDouble_${classId}_${sid}" style="font-size:11px;">Doubles</label>
                </div>
                <div>
                    <input type="number" class="form-control form-control-sm wiz-subj-num"
                           min="0" max="5"
                           id="wizMaxDouble_${classId}_${sid}"
                           value="${s.max_double_periods_per_week}"
                           ${s.allow_double_period && !isPending ? '' : 'disabled'}>
                    <small class="text-muted">max</small>
                </div>
                <div>
                    <select class="form-select form-select-sm wiz-priority-select"
                            id="wizPrio_${classId}_${sid}"
                            onchange="onWizPriorityChange(${classId}, ${sid}, this.value)" ${isPending ? 'disabled' : ''}>
                        <option value="">Use Priority: Off</option>
                        ${priorityOptions}
                    </select>
                </div>
                <div class="wiz-priority-flags" id="wizFlags_${classId}_${sid}" style="display:${s.use_priority ? 'flex' : 'none'}">
                    <label title="Reorders placement — higher-priority subjects get placed before lower ones.">
                        <input type="checkbox" class="form-check-input"
                               id="wizOrder_${classId}_${sid}" ${s.affects_ordering ? 'checked' : ''}> Order
                    </label>
                    <label title="Prefers morning slots for high-priority subjects.">
                        <input type="checkbox" class="form-check-input"
                               id="wizQuality_${classId}_${sid}" ${s.affects_slot_quality ? 'checked' : ''}> Quality
                    </label>
                    <label title="Won't be dropped if the timetable is tight.">
                        <input type="checkbox" class="form-check-input"
                               id="wizProtect_${classId}_${sid}" ${s.is_protected ? 'checked' : ''}> Protect
                    </label>
                </div>
            </div>`;
        });

        html += `</div></div>`;
    });

    panel.innerHTML = html;

    renderRoomMappingsPanel();
}

function toggleWizClassCard(key) {
    const body  = document.getElementById('wizClassBody_' + key);
    const caret = document.getElementById('wizCaret_' + key);
    if (!body) return;
    const open = body.style.display === 'none';
    body.style.display = open ? '' : 'none';
    if (caret) {
        caret.className = `ri-${open ? 'arrow-down-s' : 'arrow-right-s'}-line me-1 wiz-caret`;
    }
}

function toggleWizDouble(classId, subjectId, checked) {
    const el = document.getElementById(`wizMaxDouble_${classId}_${subjectId}`);
    if (el) el.disabled = !checked;
}

// A "pending" row (a subject-teacher pairing with no `subjectclass` link
// to this class yet) stays disabled until the admin opts it in here --
// this never creates or touches a `subjectclass` row, it only controls
// whether this row is generated for.
function toggleWizPendingInclude(classId, subjectId, checked) {
    const ppwEl  = document.getElementById(`wizPpw_${classId}_${subjectId}`);
    const dblEl  = document.getElementById(`wizDouble_${classId}_${subjectId}`);
    const maxEl  = document.getElementById(`wizMaxDouble_${classId}_${subjectId}`);
    const prioEl = document.getElementById(`wizPrio_${classId}_${subjectId}`);
    if (ppwEl)  ppwEl.disabled  = !checked;
    if (dblEl)  dblEl.disabled  = !checked;
    if (maxEl)  maxEl.disabled  = !checked || !dblEl?.checked;
    if (prioEl) prioEl.disabled = !checked;
}

function onWizPriorityChange(classId, subjectId, value) {
    const flags = document.getElementById(`wizFlags_${classId}_${subjectId}`);
    if (flags) flags.style.display = value ? 'flex' : 'none';
}

// ============================================================================
// PERIOD ALLOCATION MODAL
// ============================================================================
let paState = {
    classes: [],       // last-loaded class/subject/teacher grid
    sets: [],           // saved sets for the current session/term
    currentSetId: null, // set being edited, or null when creating a new one
};

function openPeriodAllocationModal() {
    resetPeriodAllocationForm(false);
    new bootstrap.Modal(document.getElementById('periodAllocationModal')).show();
}

function onPeriodAllocationScopeChange() {
    paState.currentSetId = null;
    document.getElementById('paSetName').value = '';
    document.getElementById('paSetDescription').value = '';
    document.getElementById('paDeleteBtn').style.display = 'none';
    document.getElementById('paEditingHint').textContent = '';
    loadPeriodAllocationSetsList();
}

async function loadPeriodAllocationSetsList() {
    const sessionId = document.getElementById('paSessionId').value;
    const wrap  = document.getElementById('paSetsWrap');
    const pills = document.getElementById('paSetsPills');

    if (!sessionId) { wrap.style.display = 'none'; paState.sets = []; return; }

    const termId = document.getElementById('paTermId').value;

    try {
        const params = new URLSearchParams({ session_id: sessionId });
        if (termId) params.set('term_id', termId);
        const res  = await fetch(`${ROUTES.periodAllocationSetsList}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load saved sets.');

        paState.sets = data.sets;
        wrap.style.display = '';

        if (!data.sets.length) {
            pills.innerHTML = '<span class="text-muted" style="font-size:12px">No saved sets yet for this session/term.</span>';
            return;
        }

        pills.innerHTML = data.sets.map(s => `
            <div class="pa-set-row ${paState.currentSetId === s.id ? 'active' : ''}"
                 onclick="loadPeriodAllocationSetIntoForm(${s.id})">
                <div class="pa-set-row-icon"><i class="ri-bookmark-3-line"></i></div>
                <div class="pa-set-row-body">
                    <div class="pa-set-row-name">${escapeHtml(s.name)}</div>
                    ${s.description ? `<div class="pa-set-row-desc">${escapeHtml(s.description)}</div>` : ''}
                    <div class="pa-set-row-meta">
                        ${s.class_count} class${s.class_count === 1 ? '' : 'es'}
                        &middot; ${s.allocation_count} subject${s.allocation_count === 1 ? '' : 's'}
                        ${s.is_all_terms ? '&middot; All Terms' : ''}
                        &middot; Updated ${escapeHtml(s.updated_at)}${s.updated_by ? ' by ' + escapeHtml(s.updated_by) : ''}
                    </div>
                </div>
                <div class="pa-set-row-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary" title="Edit this set"
                            onclick="event.stopPropagation(); loadPeriodAllocationSetIntoForm(${s.id})">
                        <i class="ri-pencil-line"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete this set"
                            onclick="event.stopPropagation(); deletePeriodAllocationSetById(${s.id})">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>`).join('');
    } catch (e) {
        pills.innerHTML = `<span class="text-danger" style="font-size:12px">Failed: ${escapeHtml(e.message)}</span>`;
    }
}

async function loadPeriodAllocationGrid() {
    const sessionId = document.getElementById('paSessionId').value;
    if (!sessionId) return AppleAlert.warning('Required', 'Please select a session first.');

    const termId    = document.getElementById('paTermId').value;
    const classIds  = [...document.getElementById('paClassIds').selectedOptions].map(o => o.value).filter(Boolean);

    const panel = document.getElementById('paGridPanel');
    panel.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading classes…</div>';

    try {
        const params = new URLSearchParams({ session_id: sessionId });
        if (termId) params.set('term_id', termId);
        classIds.forEach(id => params.append('schoolclass_ids[]', id));
        if (document.getElementById('paIncludeUnassigned')?.checked) params.set('include_unassigned', '1');

        const res  = await fetch(`${ROUTES.periodAllocationGrid}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load classes.');

        paState.classes = data.classes;
        renderPeriodAllocationGrid(data.classes);
        loadPeriodAllocationSetsList();
    } catch (e) {
        panel.innerHTML = `<div class="alert alert-danger m-0">Failed: ${escapeHtml(e.message)}</div>`;
    }
}

function renderPeriodAllocationGrid(classes, overlay = null) {
    const panel = document.getElementById('paGridPanel');
    if (!classes.length) {
        panel.innerHTML = '<div class="text-center py-4 text-muted"><i class="ri-information-line ri-2x d-block mb-2 opacity-30"></i>No subjects assigned to any class in this scope.</div>';
        return;
    }

    // One subject row. Pending rows (not yet linked to this class via
    // Subject-Class assignment) start disabled — the admin opts each one
    // in per class with the "Include" checkbox — unless a saved set being
    // loaded for edit already included it (overlay match), in which case
    // it opens pre-checked with its saved values.
    const renderPaSubjRow = (classId, s, isPending) => {
        const sid = s.subject_id;
        const ov  = overlay ? overlay[`${classId}:${sid}`] : null;
        const ppw = ov ? ov.periods_per_week : 2;
        const dbl = ov ? !!ov.allow_double_period : false;
        const max = ov ? ov.max_double_periods_per_week : 1;
        const included = !isPending || !!ov;

        const noTeacher = !s.teacher_id;
        const rowClass  = isPending ? 'pa-subj-row pa-subj-row-pending' : 'pa-subj-row';
        const leadCell  = isPending
            ? `<input type="checkbox" class="form-check-input pa-pending-include" id="paPendingInclude_${classId}_${sid}"
                      ${included ? 'checked' : ''}
                      onchange="togglePaPendingInclude(${classId}, ${sid}, this.checked)">`
            : '';

        return `<div class="${rowClass}" data-class-id="${classId}" data-subject-id="${sid}">
                <div>
                    ${leadCell}<span class="pa-subj-name">${escapeHtml(s.subject_name)}</span>${isPending ? '<span class="pa-pending-badge">Not on this class yet</span>' : ''}
                    <div class="pa-subj-teacher${noTeacher ? ' text-danger' : ''}">${noTeacher ? 'No teacher assigned yet' : escapeHtml(s.teacher_name)}</div>
                </div>
                <div>
                    <input type="number" class="form-control form-control-sm pa-subj-num"
                           min="1" max="20" id="paPpw_${classId}_${sid}" value="${ppw}"
                           oninput="updatePaClassSummary(${classId})" ${included ? '' : 'disabled'}>
                    <small class="text-muted">periods/wk</small>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="paDouble_${classId}_${sid}"
                           ${dbl ? 'checked' : ''} onchange="togglePaDouble(${classId}, ${sid}, this.checked)" ${included ? '' : 'disabled'}>
                    <label class="form-check-label" for="paDouble_${classId}_${sid}" style="font-size:11px;">Doubles</label>
                </div>
                <div>
                    <input type="number" class="form-control form-control-sm pa-subj-num"
                           min="0" max="5" id="paMaxDouble_${classId}_${sid}" value="${max}"
                           ${dbl && included ? '' : 'disabled'}>
                    <small class="text-muted">max</small>
                </div>
            </div>`;
    };

    let html = '';
    classes.forEach(cls => {
        const classId = cls.schoolclass_id;
        html += `<div class="wiz-class-card" id="paClassCard_${classId}">
            <div class="wiz-class-hdr" onclick="togglePaClassCard(${classId})">
                <h6><i class="ri-arrow-down-s-line me-1 pa-caret" id="paCaret_${classId}"></i>${escapeHtml(cls.class_name)}</h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger pa-class-over-pill" id="paClassOverPill_${classId}" style="display:none">
                        <i class="ri-error-warning-line"></i><span id="paClassOverPillText_${classId}"></span>
                    </span>
                    <span class="badge bg-light text-dark">${cls.subjects.length} subjects</span>
                </div>
            </div>
            <div class="pa-class-summary">
                <div class="pa-class-summary-input">
                    <label for="paTotalPeriods_${classId}">Total periods/wk</label>
                    <input type="number" class="form-control form-control-sm" id="paTotalPeriods_${classId}"
                           min="1" max="60" placeholder="e.g. 30" oninput="updatePaClassSummary(${classId})">
                </div>
                <div class="pa-class-summary-badge neutral" id="paClassSummaryBadge_${classId}">
                    Set a total to track remaining periods
                </div>
            </div>
            <div class="wiz-class-body" id="paClassBody_${classId}" style="display:none">`;

        cls.subjects.forEach(s => { html += renderPaSubjRow(classId, s, false); });

        const pending = cls.pending_subjects || [];
        if (pending.length) {
            html += `<div class="pa-pending-divider"><i class="ri-time-line"></i>Not yet allocated to this class — tick to include</div>`;
            pending.forEach(s => { html += renderPaSubjRow(classId, s, true); });
        }

        html += `</div></div>`;
    });

    panel.innerHTML = html;

    // Prime the live "used / remaining" summary for every class now that
    // its inputs exist in the DOM (covers both a fresh load and an
    // overlay from a saved set).
    classes.forEach(cls => updatePaClassSummary(cls.schoolclass_id));
}

function togglePaClassCard(classId) {
    const body  = document.getElementById('paClassBody_' + classId);
    const caret = document.getElementById('paCaret_' + classId);
    if (!body) return;
    const open = body.style.display === 'none';
    body.style.display = open ? '' : 'none';
    if (caret) caret.className = `ri-${open ? 'arrow-down-s' : 'arrow-right-s'}-line me-1 pa-caret`;
}

function togglePaDouble(classId, subjectId, checked) {
    const el = document.getElementById(`paMaxDouble_${classId}_${subjectId}`);
    if (el) el.disabled = !checked;
}

// A "pending" row (a subject-teacher pairing not yet linked to this class
// via Subject-Class assignment) stays disabled until the admin explicitly
// opts it in for this class. Toggling it on/off just enables/disables its
// inputs -- it never creates or touches a `subjectclass` row.
function togglePaPendingInclude(classId, subjectId, checked) {
    const ppwEl = document.getElementById(`paPpw_${classId}_${subjectId}`);
    const dblEl = document.getElementById(`paDouble_${classId}_${subjectId}`);
    const maxEl = document.getElementById(`paMaxDouble_${classId}_${subjectId}`);
    if (ppwEl) ppwEl.disabled = !checked;
    if (dblEl) dblEl.disabled = !checked;
    if (maxEl) maxEl.disabled = !checked || !dblEl?.checked;
    updatePaClassSummary(classId);
}

// Recomputes, for one class card, how many periods/week have been entered
// across its subjects vs. the "Total periods/wk" the user typed in for
// that class, and updates the live badge (and the header's over-limit
// pill) to match. Called on every keystroke in either field, so the
// remaining-periods count is always current -- no need to click anything.
function updatePaClassSummary(classId) {
    const totalEl = document.getElementById(`paTotalPeriods_${classId}`);
    const badge   = document.getElementById(`paClassSummaryBadge_${classId}`);
    const card    = document.getElementById(`paClassCard_${classId}`);
    const pill    = document.getElementById(`paClassOverPill_${classId}`);
    const pillTxt = document.getElementById(`paClassOverPillText_${classId}`);
    if (!totalEl || !badge || !card) return;

    let used = 0;
    document.querySelectorAll(`input[id^="paPpw_${classId}_"]`).forEach(el => {
        if (el.disabled) return; // a not-yet-included pending row doesn't count
        used += parseInt(el.value) || 0;
    });

    const totalRaw = totalEl.value.trim();
    card.classList.remove('pa-class-over', 'pa-class-ok', 'pa-class-exact');

    if (totalRaw === '') {
        badge.className = 'pa-class-summary-badge neutral';
        badge.innerHTML = `Used: <strong>${used}</strong> &middot; set a total to track remaining`;
        if (pill) pill.style.display = 'none';
        return;
    }

    const total     = parseInt(totalRaw) || 0;
    const remaining = total - used;

    if (remaining < 0) {
        card.classList.add('pa-class-over');
        badge.className = 'pa-class-summary-badge over';
        badge.innerHTML = `<i class="ri-error-warning-line"></i> Over by <strong>${-remaining}</strong> period${-remaining === 1 ? '' : 's'} (${used} of ${total} used)`;
        if (pill && pillTxt) { pillTxt.textContent = `Over by ${-remaining}`; pill.style.display = ''; }
    } else if (remaining === 0) {
        card.classList.add('pa-class-exact');
        badge.className = 'pa-class-summary-badge exact';
        badge.innerHTML = `<i class="ri-checkbox-circle-line"></i> All ${total} periods allocated`;
        if (pill) pill.style.display = 'none';
    } else {
        card.classList.add('pa-class-ok');
        badge.className = 'pa-class-summary-badge ok';
        badge.innerHTML = `<strong>${remaining}</strong> period${remaining === 1 ? '' : 's'} remaining (${used} of ${total} used)`;
        if (pill) pill.style.display = 'none';
    }
}

// Fresh, save-time re-check (independent of the live badges above) of
// every class that has a total set: which ones currently have more
// periods entered than their total allows. Used to block the actual save.
function computePaClassOverages() {
    const overages = [];
    paState.classes.forEach(cls => {
        const classId = cls.schoolclass_id;
        const totalEl = document.getElementById(`paTotalPeriods_${classId}`);
        if (!totalEl || totalEl.value.trim() === '') return;
        const total = parseInt(totalEl.value) || 0;

        let used = 0;
        [...cls.subjects, ...(cls.pending_subjects || [])].forEach(s => {
            const ppwEl = document.getElementById(`paPpw_${classId}_${s.subject_id}`);
            if (ppwEl && !ppwEl.disabled) used += parseInt(ppwEl.value) || 0;
        });

        if (used > total) {
            overages.push({ classId, className: cls.class_name, used, total, over: used - total });
        }
    });
    return overages;
}

function expandPaClassCard(classId) {
    const body  = document.getElementById('paClassBody_' + classId);
    const caret = document.getElementById('paCaret_' + classId);
    if (body)  body.style.display = '';
    if (caret) caret.className = 'ri-arrow-down-s-line me-1 pa-caret';
}

function scrollToPaClassCard(classId) {
    const card = document.getElementById('paClassCard_' + classId);
    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function collectPeriodAllocationRows() {
    const rows = [];
    paState.classes.forEach(cls => {
        [...cls.subjects, ...(cls.pending_subjects || [])].forEach(s => {
            const ppwEl = document.getElementById(`paPpw_${cls.schoolclass_id}_${s.subject_id}`);
            if (!ppwEl || ppwEl.disabled) return; // not-included pending rows are left out
            rows.push({
                schoolclass_id:               cls.schoolclass_id,
                subject_id:                   s.subject_id,
                periods_per_week:             parseInt(ppwEl.value) || 1,
                allow_double_period:          document.getElementById(`paDouble_${cls.schoolclass_id}_${s.subject_id}`)?.checked || false,
                max_double_periods_per_week:  parseInt(document.getElementById(`paMaxDouble_${cls.schoolclass_id}_${s.subject_id}`)?.value) || 1,
            });
        });
    });
    return rows;
}

async function loadPeriodAllocationSetIntoForm(setId) {
    try {
        const res  = await fetch(`${url(ROUTES.periodAllocationSetShow, setId)}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load that set.');

        paState.currentSetId = data.set.id;
        document.getElementById('paSessionId').value = data.set.session_id;
        document.getElementById('paTermId').value    = data.set.term_id || '';
        document.getElementById('paSetName').value        = data.set.name;
        document.getElementById('paSetDescription').value = data.set.description || '';
        document.getElementById('paDeleteBtn').style.display = '';
        document.getElementById('paEditingHint').textContent = `Editing "${data.set.name}"`;

        const overlay = {};
        data.allocations.forEach(a => { overlay[`${a.schoolclass_id}:${a.subject_id}`] = a; });

        // Load (or re-render, if already loaded) the grid for this set's
        // scope, then overlay its saved periods-per-week values onto it.
        // Always request pending (not-yet-assigned) subjects too, in case
        // this set includes any -- otherwise those rows, and their saved
        // periods, would silently disappear while editing.
        const sessionId = data.set.session_id;
        const termId    = data.set.term_id;
        const includeUnassignedEl = document.getElementById('paIncludeUnassigned');
        if (includeUnassignedEl) includeUnassignedEl.checked = true;
        const params = new URLSearchParams({ session_id: sessionId, include_unassigned: '1' });
        if (termId) params.set('term_id', termId);
        const gridRes  = await fetch(`${ROUTES.periodAllocationGrid}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
        const gridData = await gridRes.json();
        if (!gridData.success) throw new Error(gridData.message || 'Failed to load classes for that set.');

        paState.classes = gridData.classes;
        renderPeriodAllocationGrid(gridData.classes, overlay);
        loadPeriodAllocationSetsList();
    } catch (e) {
        AppleAlert.error('Could not load set', e.message);
    }
}

function resetPeriodAllocationForm(clearGrid = true) {
    paState.currentSetId = null;
    document.getElementById('paSetName').value = '';
    document.getElementById('paSetDescription').value = '';
    document.getElementById('paDeleteBtn').style.display = 'none';
    document.getElementById('paEditingHint').textContent = '';
    if (clearGrid) {
        paState.classes = [];
        document.getElementById('paGridPanel').innerHTML =
            '<div class="text-center py-4 text-muted"><i class="ri-grid-line ri-2x d-block mb-2 opacity-30"></i><p class="mb-0">Select a session and click <strong>Load Classes</strong> to see per-class subjects.</p></div>';
    }
    loadPeriodAllocationSetsList();
}

async function savePeriodAllocationSet() {
    const sessionId = document.getElementById('paSessionId').value;
    const name      = document.getElementById('paSetName').value.trim();
    if (!sessionId) return AppleAlert.warning('Required', 'Please select a session.');
    if (!name)      return AppleAlert.warning('Required', 'Give this set a name.');
    if (!paState.classes.length) return AppleAlert.warning('Nothing to save', 'Click "Load Classes" first, then set periods/week.');

    const allocations = collectPeriodAllocationRows();
    if (!allocations.length) return AppleAlert.warning('Nothing to save', 'No subject rows to save.');

    const overages = computePaClassOverages();
    if (overages.length) {
        overages.forEach(o => expandPaClassCard(o.classId));
        scrollToPaClassCard(overages[0].classId);
        const list = overages
            .map(o => `<strong>${escapeHtml(o.className)}</strong>: ${o.used} used vs ${o.total} total (over by ${o.over})`)
            .join('<br>');
        AppleAlert.error('Fix periods before saving', `These classes exceed their total periods/week:<br>${list}`);
        return;
    }

    showLoader();
    try {
        const res  = await apiFetch(ROUTES.periodAllocationSetSave, 'POST', {
            set_id:           paState.currentSetId,
            session_id:       sessionId,
            term_id:          document.getElementById('paTermId').value || null,
            name,
            description:      document.getElementById('paSetDescription').value.trim() || null,
            // Sent as one JSON-encoded string instead of a native array of
            // objects: a large set repeats the same field names
            // (schoolclass_id, subject_id, ...) once per row, and some
            // hosting WAFs (Comodo's ruleset on cPanel, in particular) flag
            // that shape as too many / duplicate arguments and block the
            // request with a 406 before it ever reaches Laravel. One string
            // field sidesteps that without changing what gets saved.
            allocations_json: JSON.stringify(allocations),
        });
        const data = await res.json();
        hideLoader();
        if (data.success) {
            paState.currentSetId = data.set_id;
            document.getElementById('paDeleteBtn').style.display = '';
            document.getElementById('paEditingHint').textContent = `Editing "${name}"`;
            AppleAlert.saved('Period allocation set saved');
            loadPeriodAllocationSetsList();
        } else {
            AppleAlert.error('Save failed', data.message || 'Please try again.');
        }
    } catch (e) {
        hideLoader();
        AppleAlert.error('Save failed', e.message);
    }
}

async function deletePeriodAllocationSetById(setId) {
    const set  = paState.sets.find(s => s.id === setId);
    const name = set ? set.name : (document.getElementById('paSetName').value || 'this set');
    const ok = await AppleAlert.confirmDelete(
        'Delete this period allocation set?',
        `Permanently removes <strong>${escapeHtml(name)}</strong>. Timetables already generated from it are not affected.`
    );
    if (!ok) return;

    try {
        const res  = await apiFetch(url(ROUTES.periodAllocationSetDelete, setId), 'DELETE');
        const data = await res.json();
        if (data.success) {
            AppleAlert.deleted('Period allocation set deleted');
            if (paState.currentSetId === setId) {
                resetPeriodAllocationForm(false);
            } else {
                loadPeriodAllocationSetsList();
            }
        } else {
            AppleAlert.error('Could not delete', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.error('Could not delete', e.message);
    }
}

async function deleteCurrentPeriodAllocationSet() {
    if (!paState.currentSetId) return;
    await deletePeriodAllocationSetById(paState.currentSetId);
}

// ── Generation Wizard integration ──────────────────────────────────────────
async function refreshWizardAllocationPicker() {
    const wrap   = document.getElementById('wizAllocationSetWrap');
    const select = document.getElementById('wizAllocationSetId');
    const sessionId = document.getElementById('wizSessionId').value;
    if (!sessionId) { wrap.style.display = 'none'; return; }

    const termId = document.getElementById('wizTermId').value;

    try {
        const params = new URLSearchParams({ session_id: sessionId });
        if (termId) params.set('term_id', termId);
        const res  = await fetch(`${ROUTES.periodAllocationSetsList}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!data.success || !data.sets.length) { wrap.style.display = 'none'; return; }

        select.innerHTML = '<option value="">Choose a saved set…</option>' +
            data.sets.map(s => `<option value="${s.id}">${escapeHtml(s.name)} (${s.class_count} class${s.class_count === 1 ? '' : 'es'})</option>`).join('');
        wrap.style.display = '';
    } catch (e) {
        wrap.style.display = 'none';
    }
}

async function applyWizardPeriodAllocationSet() {
    const setId = document.getElementById('wizAllocationSetId').value;
    if (!setId) return AppleAlert.warning('Pick a set', 'Choose a saved period allocation first.');

    try {
        const res  = await fetch(url(ROUTES.periodAllocationSetShow, setId), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load that set.');

        // The Subjects & Priority panel below has to actually be loaded
        // before this set's rows have anywhere to land -- otherwise every
        // row is silently skipped (0 applied) even though a confirmation
        // still pops up, and pending subjects never get their "Include"
        // checkbox turned on, so they're missing from generation too. Load
        // the panel automatically here instead of relying on the admin
        // having already clicked "Load Subjects" in just the right order,
        // switching on "show unassigned" first if this set has any
        // pending (not-yet-assigned) rows so those aren't skipped either.
        const hasPendingRows = data.allocations.some(a => a.is_pending);
        const includeToggle  = document.getElementById('wizIncludeUnassigned');
        const needsReload = !Object.keys(wizardSubjectsState).length
            || (hasPendingRows && includeToggle && !includeToggle.checked);

        if (needsReload) {
            if (hasPendingRows && includeToggle) includeToggle.checked = true;
            await loadWizardSubjects();
            if (!Object.keys(wizardSubjectsState).length) {
                // loadWizardSubjects() already explained why (no session
                // picked, no classes in scope, etc.) -- nothing to apply.
                return;
            }
        }

        let applied = 0, skipped = 0, skippedPending = 0;
        data.allocations.forEach(a => {
            const ppwEl = document.getElementById(`wizPpw_${a.schoolclass_id}_${a.subject_id}`);
            if (!ppwEl) {
                skipped++;
                if (a.is_pending) skippedPending++;
                return;
            }

            // A pending row is disabled until opted in -- applying a saved
            // set that planned periods for it counts as that opt-in.
            const includeEl = document.getElementById(`wizPendingInclude_${a.schoolclass_id}_${a.subject_id}`);
            if (includeEl) {
                includeEl.checked = true;
                toggleWizPendingInclude(a.schoolclass_id, a.subject_id, true);
            }

            ppwEl.value = a.periods_per_week;

            const dblEl = document.getElementById(`wizDouble_${a.schoolclass_id}_${a.subject_id}`);
            if (dblEl) {
                dblEl.checked = !!a.allow_double_period;
                toggleWizDouble(a.schoolclass_id, a.subject_id, dblEl.checked);
            }
            const maxEl = document.getElementById(`wizMaxDouble_${a.schoolclass_id}_${a.subject_id}`);
            if (maxEl) maxEl.value = a.max_double_periods_per_week;

            applied++;
        });

        const pendingNote = skippedPending
            ? ` ${skippedPending} of those aren't linked to a class yet -- turn on "Also show subjects not yet assigned to a class" above, click Load Subjects, then re-apply this set.`
            : '';
        const skipNote = (skipped - skippedPending) > 0
            ? ` ${skipped - skippedPending} row(s) in that set aren't in the classes currently loaded here, so they were left as-is.`
            : '';
        AppleAlert.saved(`Applied periods/week for ${applied} subject(s).${pendingNote}${skipNote}`);
    } catch (e) {
        AppleAlert.error('Could not apply set', e.message);
    }
}

// ============================================================================
// WIZARD: ROOM MAPPINGS PANEL
// ============================================================================
async function renderRoomMappingsPanel() {
    const panel = document.getElementById('wizRoomMappingsPanel');
    if (!panel) return;

    if (!Object.keys(wizardSubjectsState).length) {
        panel.innerHTML = '<div class="text-center py-4 text-muted"><i class="ri-links-line ri-2x d-block mb-2 opacity-30"></i><p class="mb-0">Click <strong>Load Subjects</strong> above to see room mappings.</p></div>';
        return;
    }

    let rooms = [];
    try {
        const r = await fetch(ROUTES.roomsListJson, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        rooms = d.data ?? [];
    } catch (e) {
        panel.innerHTML = '<div class="alert alert-danger m-0">Failed to load rooms: ' + escapeHtml(e.message) + '</div>';
        return;
    }

    let html = `<div class="text-muted mb-2" style="font-size:11.5px">
        <i class="ri-information-line me-1"></i>
        Rooms selected here are the only ones the generator will use when
        <strong>Strict Room Mapping</strong> is enabled.
    </div>`;

    Object.entries(wizardSubjectsState).forEach(([classId, info]) => {
        const classOpt = document.querySelector(`#wizClassIds option[value="${classId}"]`);
        const className = classOpt?.textContent.trim() ?? `Class #${classId}`;

        html += `<div class="wiz-class-card mb-2">
            <div class="wiz-class-hdr" onclick="toggleWizClassCard('rm_${classId}')">
                <h6><i class="ri-arrow-down-s-line me-1 wiz-caret" id="wizCaret_rm_${classId}"></i>${escapeHtml(className)}</h6>
                <span class="badge bg-light text-dark">${info.subjects.length} subjects</span>
            </div>
            <div class="wiz-class-body" id="wizClassBody_rm_${classId}" style="display:none">
                <table class="table table-sm mb-0" style="font-size:12px">
                    <thead class="table-light">
                        <tr><th>Subject</th><th style="width:45%">Mapped rooms</th><th style="width:80px"></th></tr>
                    </thead>
                    <tbody>`;

        info.subjects.forEach(s => {
            const currentlyMapped = (s.mapped_rooms_subject ?? []).map(r => r.id);
            const optionsHtml = rooms.map(r =>
                `<option value="${r.id}" ${currentlyMapped.includes(r.id) ? 'selected' : ''}>${escapeHtml(r.label)}</option>`
            ).join('');

            html += `<tr>
                <td>
                    <div class="fw-semibold">${escapeHtml(s.subject_name)}</div>
                    <div class="text-muted" style="font-size:10.5px">${escapeHtml(s.teacher_name)}</div>
                </td>
                <td>
                    <select class="form-select form-select-sm wiz-bulk-room-select"
                            data-class-id="${classId}"
                            data-subject-id="${s.subject_id}"
                            multiple size="3">
                        ${optionsHtml || '<option disabled>No rooms available</option>'}
                    </select>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary"
                            onclick="saveBulkRoomMapping(${classId}, ${s.subject_id})"
                            title="Save mapping">
                        <i class="ri-save-line"></i>
                    </button>
                </td>
            </tr>`;
        });

        html += `</tbody></table></div></div>`;
    });

    panel.innerHTML = html;
}

async function saveBulkRoomMapping(classId, subjectId) {
    const select = document.querySelector(
        `.wiz-bulk-room-select[data-class-id="${classId}"][data-subject-id="${subjectId}"]`
    );
    if (!select) return;
    const selectedRoomIds = [...select.selectedOptions].map(o => parseInt(o.value));

    const sessionId = document.getElementById('wizSessionId').value;
    const termId    = document.getElementById('wizTermId').value || null;

    if (!sessionId) return AppleAlert.warning('Required', 'Select a session first.');

    AppleAlert.loading('Saving mappings…');

    let added = 0, skipped = 0, failed = 0;
    for (const roomId of selectedRoomIds) {
        try {
            const res = await fetch(ROUTES.roomMappingsStore.replace('__ID__', roomId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({
                    schoolclass_id: parseInt(classId),
                    subject_id:     parseInt(subjectId),
                    session_id:     parseInt(sessionId),
                    term_id:        termId ? parseInt(termId) : null,
                    note:           'Bulk-mapped from wizard',
                }),
            });
            const data = await res.json();
            if (data.success) added++;
            else if (res.status === 422) skipped++;
            else failed++;
        } catch (e) {
            failed++;
        }
    }

    AppleAlert.close();

    let msg = `${added} new mapping(s) added.`;
    if (skipped) msg += ` ${skipped} already existed.`;
    if (failed)  msg += ` ${failed} failed.`;

    if (failed) {
        AppleAlert.warning('Mappings saved with errors', msg);
    } else {
        AppleAlert.toast(msg, 'success', 2400);
    }

    if (added > 0) loadWizardSubjects();
}

// ============================================================================
// WIZARD: ROOM QUICK-MAP MODAL
// ============================================================================
async function openQuickMapRoom(classId, subjectId, subjectName, className) {
    document.getElementById('quickMapClassId').value = classId;
    document.getElementById('quickMapSubjectId').value = subjectId;
    document.getElementById('quickMapSubjectName').textContent = subjectName;
    document.getElementById('quickMapClassName').textContent = className;

    try {
        const [roomsRes, sessionsRes, termsRes] = await Promise.all([
            fetch(ROUTES.roomsListJson, { headers: { 'Accept': 'application/json' } }),
            fetch(ROUTES.sessionsList,  { headers: { 'Accept': 'application/json' } }),
            fetch(ROUTES.termsList,     { headers: { 'Accept': 'application/json' } }),
        ]);
        const rooms    = await roomsRes.json();
        const sessions = await sessionsRes.json();
        const terms    = await termsRes.json();

        const roomSel = document.getElementById('quickMapRoomSelect');
        roomSel.innerHTML = '<option value="">— Pick a room —</option>'
            + (rooms.data ?? []).map(r => `<option value="${r.id}">${escapeHtml(r.label)}</option>`).join('');

        const sessSel = document.getElementById('quickMapSessionId');
        sessSel.innerHTML = '<option value="">— Select session —</option>'
            + (sessions.data ?? []).map(s => `<option value="${s.id}">${escapeHtml(s.session)}</option>`).join('');

        const termSel = document.getElementById('quickMapTermId');
        termSel.innerHTML = '<option value="">All terms</option>'
            + (terms.data ?? []).map(t => `<option value="${t.id}">${escapeHtml(t.term)}</option>`).join('');

        const wizSession = document.getElementById('wizSessionId').value;
        if (wizSession) sessSel.value = wizSession;
        const wizTerm = document.getElementById('wizTermId').value;
        if (wizTerm) termSel.value = wizTerm;

        new bootstrap.Modal(document.getElementById('quickMapRoomModal')).show();
    } catch (e) {
        AppleAlert.error('Could not load rooms', e.message);
    }
}

async function submitQuickMapRoom() {
    const classId   = document.getElementById('quickMapClassId').value;
    const subjectId = document.getElementById('quickMapSubjectId').value;
    const roomId    = document.getElementById('quickMapRoomSelect').value;
    const sessionId = document.getElementById('quickMapSessionId').value;
    const termId    = document.getElementById('quickMapTermId').value;

    if (!roomId || !sessionId) {
        return AppleAlert.warning('Missing fields', 'Pick a room and a session.');
    }

    try {
        const res = await fetch(ROUTES.roomMappingsStore.replace('__ID__', roomId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify({
                schoolclass_id: parseInt(classId),
                subject_id:     parseInt(subjectId),
                session_id:     parseInt(sessionId),
                term_id:        termId ? parseInt(termId) : null,
                note:           'Mapped from wizard',
            }),
        });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('quickMapRoomModal'))?.hide();
            AppleAlert.saved('Mapping added');
            loadWizardSubjects();
        } else {
            AppleAlert.error('Could not add mapping', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.error('Could not add mapping', e.message);
    }
}

// ============================================================================
// WIZARD: PERIOD LIMITS
// ============================================================================
let wizLimitRowSeq = 0;

function addWizardPeriodLimit() {
    wizLimitRowSeq++;
    const rowId = `wizLimit_${wizLimitRowSeq}`;
    const wrap  = document.getElementById('wizPeriodLimitsBody');
    const row   = document.createElement('div');
    row.className = 'wiz-limit-row';
    row.id = rowId;
    row.innerHTML = `
        <select class="form-select form-select-sm" onchange="updateWizLimitRow('${rowId}')" data-field="scope">
            <option value="teacher_total">Teacher — weekly total</option>
            <option value="teacher_class">Teacher — weekly for one class</option>
            <option value="teacher_day">Teacher — one day</option>
            <option value="class_total">Class — weekly total</option>
        </select>
        <div class="d-flex gap-2" data-field="targetWrap"></div>
        <div data-field="dayWrap" style="display:none">
            <select class="form-select form-select-sm" data-field="day">
                <option value="">— Day —</option>
                <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                <option>Thursday</option><option>Friday</option>
            </select>
        </div>
        <input type="number" class="form-control form-control-sm" min="1" max="60"
               placeholder="Max" data-field="max">
        <button type="button" class="btn btn-sm btn-link text-danger p-0"
                onclick="document.getElementById('${rowId}').remove()">
            <i class="ri-delete-bin-line"></i>
        </button>`;
    wrap.appendChild(row);
    updateWizLimitRow(rowId);
}

function updateWizLimitRow(rowId) {
    const row   = document.getElementById(rowId);
    if (!row) return;
    const scope = row.querySelector('[data-field="scope"]').value;
    const dayWrap = row.querySelector('[data-field="dayWrap"]');
    const targetWrap = row.querySelector('[data-field="targetWrap"]');
    targetWrap.innerHTML = '';
    dayWrap.style.display = (scope === 'teacher_day') ? '' : 'none';

    if (scope === 'teacher_total' || scope === 'teacher_day') {
        const sel = document.createElement('select');
        sel.className = 'form-select form-select-sm';
        sel.setAttribute('data-field', 'teacher');
        sel.innerHTML = '<option value="">— Teacher —</option>' + wizardTeacherOptions();
        targetWrap.appendChild(sel);
    } else if (scope === 'teacher_class') {
        const t = document.createElement('select');
        t.className = 'form-select form-select-sm';
        t.setAttribute('data-field', 'teacher');
        t.innerHTML = '<option value="">— Teacher —</option>' + wizardTeacherOptions();
        targetWrap.appendChild(t);

        const c = document.createElement('select');
        c.className = 'form-select form-select-sm';
        c.setAttribute('data-field', 'schoolclass');
        c.innerHTML = '<option value="">— Class —</option>' + wizardClassOptions();
        targetWrap.appendChild(c);
    } else if (scope === 'class_total') {
        const c = document.createElement('select');
        c.className = 'form-select form-select-sm';
        c.setAttribute('data-field', 'schoolclass');
        c.innerHTML = '<option value="">— Class —</option>' + wizardClassOptions();
        targetWrap.appendChild(c);
    }
}

function wizardTeacherOptions() {
    const seen = new Map();
    Object.values(wizardSubjectsState).forEach(c => {
        (c.subjects || []).forEach(s => {
            if (s.teacher_id && !seen.has(s.teacher_id)) {
                seen.set(s.teacher_id, s.teacher_name || `Teacher #${s.teacher_id}`);
            }
        });
    });
    return [...seen.entries()]
        .sort((a, b) => a[1].localeCompare(b[1]))
        .map(([id, name]) => `<option value="${id}">${escapeHtml(name)}</option>`)
        .join('');
}

function wizardClassOptions() {
    return [...document.getElementById('wizClassIds').options]
        .map(o => `<option value="${o.value}">${escapeHtml(o.textContent.trim())}</option>`)
        .join('');
}

function collectWizardPeriodLimits() {
    const rows = document.querySelectorAll('#wizPeriodLimitsBody .wiz-limit-row');
    const out = [];
    rows.forEach(row => {
        const scope   = row.querySelector('[data-field="scope"]').value;
        const teacher = row.querySelector('[data-field="teacher"]')?.value ?? null;
        const cls     = row.querySelector('[data-field="schoolclass"]')?.value ?? null;
        const day     = row.querySelector('[data-field="day"]')?.value ?? null;
        const max     = parseInt(row.querySelector('[data-field="max"]').value);

        if (!max || max < 1) return;
        if (scope === 'teacher_total' && !teacher) return;
        if (scope === 'teacher_class' && (!teacher || !cls)) return;
        if (scope === 'teacher_day' && (!teacher || !day)) return;
        if (scope === 'class_total' && !cls) return;

        out.push({
            scope,
            teacher_id:     teacher ? parseInt(teacher) : null,
            schoolclass_id: cls     ? parseInt(cls)     : null,
            day:            day || null,
            max_periods:    max,
        });
    });
    return out;
}

// ============================================================================
// WIZARD: ADVANCED RULES COLLECTOR
// ============================================================================
function collectWizardAdvancedRules() {
    const morningMode = document.querySelector('input[name="wizMorningCutoffMode"]:checked')?.value || 'half';
    const morningFixed = parseInt(document.getElementById('wizMorningCutoffFixedCount').value) || 3;
    return {
        cap_mode:            document.getElementById('wizCapModeSoft').checked ? 'soft' : 'hard',
        morning_cutoff:      morningMode,
        morning_cutoff_n:    morningMode === 'fixed' ? morningFixed : null,
        protected_mode:      document.querySelector('input[name="wizProtectedMode"]:checked')?.value || 'drop_unprotected',
        strict_room_mode:    document.querySelector('input[name="wizStrictRoomMode"]:checked')?.value || 'teacher_only',
        strict_room_mapping: document.getElementById('wizStrictRoomMapping').checked,
        priorities_active:   document.getElementById('wizPrioritiesActive').checked,
        join_double_periods: document.getElementById('wizJoinDoublePeriods')?.checked ?? true,
    };
}

// ============================================================================
// WIZARD: PRIORITY PAYLOAD COLLECTOR
// ============================================================================
function collectWizardPriorityPayload() {
    return Object.entries(wizardSubjectsState).flatMap(([classId, info]) =>
        (info.subjects || [])
            .filter(s => {
                if (!s.is_pending) return true;
                return !!document.getElementById(`wizPendingInclude_${classId}_${s.subject_id}`)?.checked;
            })
            .map(s => {
            const prioEl  = document.getElementById(`wizPrio_${classId}_${s.subject_id}`);
            const usePrio = prioEl && prioEl.value !== '';
            return {
                schoolclass_id:              parseInt(classId),
                subject_id:                  s.subject_id,
                periods_per_week:            parseInt(document.getElementById(`wizPpw_${classId}_${s.subject_id}`)?.value) || 2,
                allow_double_period:         document.getElementById(`wizDouble_${classId}_${s.subject_id}`)?.checked || false,
                max_double_periods_per_week: parseInt(document.getElementById(`wizMaxDouble_${classId}_${s.subject_id}`)?.value) || 0,
                use_priority:                usePrio,
                priority_level:              usePrio ? parseInt(prioEl.value) : 3,
                affects_ordering:            usePrio && document.getElementById(`wizOrder_${classId}_${s.subject_id}`)?.checked,
                affects_slot_quality:        usePrio && document.getElementById(`wizQuality_${classId}_${s.subject_id}`)?.checked,
                is_protected:                usePrio && document.getElementById(`wizProtect_${classId}_${s.subject_id}`)?.checked,
            };
        })
    );
}

// ============================================================================
// WIZARD: PREVIEW
// ============================================================================
async function previewGeneration() {
    const sessionId = document.getElementById('wizSessionId').value;
    if (!sessionId) return AppleAlert.warning('Required', 'Please select a session first.');

    const scope    = document.getElementById('wizScope').value;
    const classIds = scope === 'selected'
        ? [...document.getElementById('wizClassIds').selectedOptions].map(o => parseInt(o.value))
        : null;

    if (scope === 'selected' && (!classIds || !classIds.length)) {
        return AppleAlert.warning('Required', 'Select at least one class, or switch scope to "All Classes".');
    }

    const effectiveClassIds = classIds ?? [...document.getElementById('wizClassIds').options]
        .map(o => parseInt(o.value));
    if (!effectiveClassIds.length) {
        return AppleAlert.warning('No classes', 'No classes available in this scope.');
    }
    const previewClassId = effectiveClassIds[0];

    const settingRes = await fetch(ROUTES.setup, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            schoolclass_id: previewClassId,
            session_id:     parseInt(sessionId),
            term_id:        document.getElementById('wizTermId').value || null,
        }),
    });
    const settingData = await settingRes.json();
    if (!settingData.success) {
        return AppleAlert.error('Could not prepare preview', settingData.message || 'Please try again.');
    }

    document.getElementById('wizFormContent').style.display = 'none';
    document.getElementById('wizGenerationProgress').style.display = 'none';
    document.getElementById('wizPreviewPane').style.display = '';

    const classOpt = document.querySelector(`#wizClassIds option[value="${previewClassId}"]`);
    document.getElementById('wizPreviewClass').textContent = classOpt?.textContent.trim() ?? `Class #${previewClassId}`;

    document.getElementById('wizPreviewGridContainer').innerHTML =
        '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary"></div><p class="mt-3">Running preview…</p></div>';
    document.getElementById('wizPreviewSummary').innerHTML = '';

    const subjectPriorityPayload = collectWizardPriorityPayload();
    const periodLimitsPayload    = collectWizardPeriodLimits();
    const advancedRules          = collectWizardAdvancedRules();

    try {
        const res = await fetch(ROUTES.previewGeneration, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                setting_id:                     settingData.setting_id,
                include_rooms:                  document.getElementById('wizIncludeRooms').checked,
                // Same WAF workaround as applyGenerationTemplate's payload
                // in submitGenerationWizard() -- see the comment there.
                subject_priority_payload_json:  JSON.stringify(subjectPriorityPayload),
                period_limits_payload_json:     JSON.stringify(periodLimitsPayload),
                advanced_rules:                 advancedRules,
            }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Preview failed.');

        previewState = {
            settingId:   settingData.setting_id,
            classId:     previewClassId,
            gridPayload: data.grid,
            stats:       data.stats,
        };

        renderGrid({
            containerId: 'wizPreviewGridContainer',
            periods:     data.grid.periods,
            grid:        data.grid.grid,
            days:        data.grid.days,
            animate:     false,
        });

        renderPreviewSummary(data.stats, data.grid);
    } catch (e) {
        document.getElementById('wizPreviewGridContainer').innerHTML =
            `<div class="alert alert-danger m-3">Preview failed: ${escapeHtml(e.message)}</div>`;
    }
}

function renderPreviewSummary(stats, gridPayload) {
    const wrap = document.getElementById('wizPreviewSummary');
    const placed = stats.placed ?? 0;
    const unplaced = stats.unplaced_subjects?.length ?? 0;
    const shortfall = stats.room_shortfall_count ?? 0;
    const noRoom = stats.no_room_placement_count ?? 0;
    const refused = stats.room_refused_count ?? 0;

    let html = `<div class="row g-2">
        <div class="col-md-3"><div class="mini-strip"><div class="v">${placed}</div><div class="l">Lessons placed</div></div></div>
        <div class="col-md-3"><div class="mini-strip ${unplaced ? 'warn' : 'ok'}"><div class="v">${unplaced}</div><div class="l">Subjects short</div></div></div>
        <div class="col-md-3"><div class="mini-strip ${shortfall ? 'warn' : 'ok'}"><div class="v">${shortfall}</div><div class="l">Room shortfalls</div></div></div>
        <div class="col-md-3"><div class="mini-strip ${refused ? 'bad' : 'ok'}"><div class="v">${refused}</div><div class="l">Refused (strict)</div></div></div>
    </div>`;

    if (unplaced) {
        html += `<div class="alert alert-warning mt-3 mb-0" style="font-size:12.5px">
            <strong>Subjects that couldn't be fully placed:</strong>
            <ul class="mb-0 mt-1">
                ${stats.unplaced_subjects.map(u => `<li>${escapeHtml(u.subject)} — ${u.placed}/${u.needed}</li>`).join('')}
            </ul>
        </div>`;
    }
    if (noRoom) {
        html += `<div class="alert alert-info mt-2 mb-0" style="font-size:12.5px">
            <i class="ri-door-line me-1"></i>${noRoom} lesson(s) placed without a room.
        </div>`;
    }

    wrap.innerHTML = html;
}

function exitPreviewMode() {
    previewState = null;
    document.getElementById('wizPreviewPane').style.display = 'none';
    document.getElementById('wizFormContent').style.display = '';
}

async function acceptPreviewAndApply() {
    if (!previewState) return;
    exitPreviewMode();
    await submitGenerationWizard(true);
}

// ============================================================================
// WIZARD: SUBMIT
// ============================================================================
async function submitGenerationWizard(alsoGenerate) {
    const sessionId = document.getElementById('wizSessionId').value;
    if (!sessionId) return AppleAlert.warning('Required', 'Please select a session.');
    const activeDays = [...document.querySelectorAll('.wiz-active-day:checked')].map(cb => cb.value);
    if (!activeDays.length) return AppleAlert.warning('Required', 'Select at least one active day.');
    const scope    = document.getElementById('wizScope').value;
    const classIds = scope === 'selected'
        ? [...document.getElementById('wizClassIds').selectedOptions].map(o => parseInt(o.value))
        : null;
    if (scope === 'selected' && !classIds.length) {
        return AppleAlert.warning('Required', 'Select at least one class, or switch scope to "All Classes".');
    }
    const includeRooms = document.getElementById('wizIncludeRooms')?.checked ?? true;

    const generationName = document.getElementById('wizGenerationName').value.trim();
    if (alsoGenerate && !generationName) {
        document.getElementById('wizGenerationName').focus();
        return AppleAlert.warning('Required', 'Please name this generation so these timetables are easy to identify later.');
    }

    const payload = {
        session_id:                  parseInt(sessionId),
        term_id:                     document.getElementById('wizTermId').value || null,
        schoolclass_ids:             classIds,
        school_day_start:            document.getElementById('wizDayStart').value,
        school_day_end:              document.getElementById('wizDayEnd').value,
        period_duration_minutes:     parseInt(document.getElementById('wizPeriodDuration').value),
        short_break_duration:        parseInt(document.getElementById('wizShortBreakDuration').value),
        long_break_duration:         parseInt(document.getElementById('wizLongBreakDuration').value),
        lessons_per_day:             parseInt(document.getElementById('wizLessonsPerDay').value),
        short_break_after:           document.getElementById('wizShortBreakAfter').value ? parseInt(document.getElementById('wizShortBreakAfter').value) : null,
        long_break_after:            document.getElementById('wizLongBreakAfter').value ? parseInt(document.getElementById('wizLongBreakAfter').value) : null,
        assembly_first_period:       document.getElementById('wizAssemblyFirstPeriod').checked,
        assembly_day:                document.getElementById('wizAssemblyFirstPeriod').checked
                                          ? document.getElementById('wizAssemblyDay').value : null,
        active_days:                 activeDays,
        free_periods_per_week:       parseInt(document.getElementById('wizFreePeriods').value) || 0,
        max_lessons_per_day:         document.getElementById('wizMaxLessonsPerDay').value ? parseInt(document.getElementById('wizMaxLessonsPerDay').value) : null,
        half_days:                   getWizardHalfDays(),
        deprioritize_break_adjacent: document.getElementById('wizDeprioritizeBreakAdjacent').checked,
        include_rooms:               includeRooms,

        // Sent as JSON-encoded strings, not native arrays: a run covering
        // many classes repeats the same field names (schoolclass_id,
        // subject_id, periods_per_week, ...) once per row, and some
        // hosting WAFs (Comodo's ruleset on cPanel, in particular) flag
        // that shape as too many / duplicate arguments and block the
        // request with a 406 before it ever reaches Laravel -- the same
        // fix as allocations_json in savePeriodAllocationSet().
        subject_priority_payload_json: JSON.stringify(collectWizardPriorityPayload()),
        period_limits_payload_json:    JSON.stringify(collectWizardPeriodLimits()),
        advanced_rules:                collectWizardAdvancedRules(),
    };

    showLoader();
    try {
        const res  = await apiFetch(ROUTES.applyGenerationTemplate, 'POST', payload);
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to apply structure.');
        const summaryHtml = buildWizardResultsSummary(data.results);
        if (!alsoGenerate) {
            hideLoader();
            bootstrap.Modal.getInstance(document.getElementById('generationWizardModal'))?.hide();
            AppleAlert.rich({
                title: 'Structure applied',
                html: `Applied to <strong>${data.applied_to}</strong> class(es).${summaryHtml}`,
                icon: 'success',
                confirmText: 'OK',
                theme: 'success',
            }).then(() => location.reload());
            return;
        }
        const genRes  = await apiFetch(ROUTES.autoGenerateWholeSchool, 'POST', {
            session_id: payload.session_id, term_id: payload.term_id, schoolclass_ids: payload.schoolclass_ids,
            include_rooms: includeRooms, generation_name: generationName,
        });
        const genData = await genRes.json();
        hideLoader();
        if (genData.success) {
            await animateWizardResults(genData.classes);
            bootstrap.Modal.getInstance(document.getElementById('generationWizardModal'))?.hide();
            const conflictNote = genData.conflict_summary?.total
                ? `<p class="text-danger mt-2" style="font-size:12px"><i class="ri-alert-line"></i>
                    ${genData.conflict_summary.total} conflict(s) detected.</p>`
                : `<p class="text-success mt-2" style="font-size:12px"><i class="ri-check-line"></i> No conflicts across the generated classes.</p>`;
            const shortfallNote = genData.had_shortfalls
                ? '<p class="text-warning mt-2" style="font-size:12px"><i class="ri-alert-line"></i> Some subjects could not be fully placed.</p>'
                : '';
            AppleAlert.rich({
                title: 'Generated!',
                html: `Generated timetables for <strong>${genData.classes.length}</strong> class(es).${summaryHtml}${buildGenerationIssuesHtml(genData.classes)}${conflictNote}${shortfallNote}`,
                icon: 'success',
                confirmText: 'OK',
                theme: 'success',
                width: 480,
            }).then(() => location.reload());
        } else if (genData.has_locked) {
            const confirmResult = await AppleAlert.rich({
                title: 'Some timetables are locked',
                html: `${escapeHtml(genData.message)}<p style="margin-top:10px">Unpublish and regenerate anyway?</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmText: 'Unpublish & generate',
                cancelText: 'Cancel',
                theme: 'destructive',
                width: 480,
            });
            if (confirmResult.isConfirmed) {
                showLoader();
                const forceRes  = await apiFetch(ROUTES.autoGenerateWholeSchool, 'POST', {
                    session_id: payload.session_id, term_id: payload.term_id,
                    schoolclass_ids: payload.schoolclass_ids, force_unpublish: true,
                    include_rooms: includeRooms, generation_name: generationName,
                });
                const forceData = await forceRes.json();
                hideLoader();
                if (forceData.success) {
                    await animateWizardResults(forceData.classes);
                    bootstrap.Modal.getInstance(document.getElementById('generationWizardModal'))?.hide();
                    AppleAlert.rich({
                        title: 'Generated!',
                        html: `Generated timetables for <strong>${forceData.classes.length}</strong> class(es).${summaryHtml}${buildGenerationIssuesHtml(forceData.classes)}`,
                        icon: 'success',
                        confirmText: 'OK',
                        theme: 'success',
                    }).then(() => location.reload());
                } else AppleAlert.error('Generation failed', forceData.message || 'Please try again.');
            }
        } else throw new Error(genData.message || 'Generation failed.');
    } catch (e) {
        hideLoader();
        AppleAlert.error('Generation failed', e.message);
    }
}

// ============================================================================
// SAVED GENERATION RUNS
// ============================================================================
let savedRunsSearchTimer = null;

function debouncedLoadSavedRuns() {
    clearTimeout(savedRunsSearchTimer);
    savedRunsSearchTimer = setTimeout(loadSavedRuns, 350);
}

async function loadSavedRuns() {
    const listEl = document.getElementById('savedRunsList');
    listEl.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>';

    const params = new URLSearchParams();
    const q = document.getElementById('runSearchInput').value.trim();
    if (q) params.set('q', q);

    const filters = {
        session_id: document.getElementById('runFilterSession').value,
        term_id:    document.getElementById('runFilterTerm').value,
        class_id:   document.getElementById('runFilterClass').value,
        status:     document.getElementById('runFilterStatus').value,
        date_from:  document.getElementById('runFilterDateFrom').value,
        date_to:    document.getElementById('runFilterDateTo').value,
    };
    Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });

    try {
        const res  = await fetch(`${ROUTES.runsList}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed.');
        renderSavedRunsList(data);
    } catch (e) {
        listEl.innerHTML = `<div class="alert alert-danger m-0">Failed: ${escapeHtml(e.message)}</div>`;
    }
}

function jumpToRunPage(page) {
    const params = new URLSearchParams();
    const q = document.getElementById('runSearchInput').value.trim();
    if (q) params.set('q', q);
    const filters = {
        session_id: document.getElementById('runFilterSession').value,
        term_id:    document.getElementById('runFilterTerm').value,
        class_id:   document.getElementById('runFilterClass').value,
        status:     document.getElementById('runFilterStatus').value,
        date_from:  document.getElementById('runFilterDateFrom').value,
        date_to:    document.getElementById('runFilterDateTo').value,
        page:       page,
    };
    Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });

    const listEl = document.getElementById('savedRunsList');
    listEl.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>';
    fetch(`${ROUTES.runsList}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => renderSavedRunsList(data))
        .catch(e => {
            listEl.innerHTML = `<div class="alert alert-danger m-0">Failed: ${escapeHtml(e.message)}</div>`;
        });
}

function renderSavedRunsList(data) {
    const listEl = document.getElementById('savedRunsList');
    if (!data.success) {
        listEl.innerHTML = `<div class="alert alert-danger m-0">Failed: ${escapeHtml(data.message || 'Error')}</div>`;
        return;
    }
    document.getElementById('savedRunsCountBadge').textContent = data.pagination.total;

    if (!data.runs.length) {
        listEl.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="ri-bookmark-line ri-3x d-block mb-3 opacity-30"></i>
                <p class="mb-0">No saved runs match your filters.</p>
            </div>`;
        document.getElementById('savedRunsPagination').innerHTML = '';
        return;
    }

    listEl.innerHTML = data.runs.map(run => `
        <div class="setting-card" style="cursor:pointer" onclick="showRunDetail('${escapeHtml(run.run_code)}')">
            <div class="sc-icon" style="background:linear-gradient(135deg,#E8F5E9,#C8E6C9)">
                <i class="ri-bookmark-3-line" style="color:#1B5E20"></i>
            </div>
            <div class="sc-body">
                <div class="sc-title">${escapeHtml(run.name)}</div>
                <div class="sc-meta">
                    <span class="badge" style="background:#F1F5F9;color:#334155;font-family:monospace;font-size:11px">${escapeHtml(run.run_code)}</span>
                    <span class="mx-1">·</span>
                    <span>${escapeHtml(run.session || '—')}</span>
                    ${run.term ? `<span class="mx-1">·</span><span>${escapeHtml(run.term)}</span>` : ''}
                    <span class="mx-1">·</span>
                    <span>${run.class_count} classes</span>
                    <span class="mx-1">·</span>
                    <span class="text-muted">by ${escapeHtml(run.creator || '—')}</span>
                    <span class="mx-1">·</span>
                    <span class="text-muted">${escapeHtml(run.created_at_h)}</span>
                    ${run.status === 'shortfalls'
                        ? '<span class="badge bg-warning-subtle text-warning ms-1">Shortfalls</span>'
                        : run.status === 'reverted'
                            ? '<span class="badge bg-danger-subtle text-danger ms-1">Reverted</span>'
                            : '<span class="badge bg-success-subtle text-success ms-1">Success</span>'}
                </div>
            </div>
            <div class="sc-actions" onclick="event.stopPropagation()">
                <button class="btn btn-sm btn-outline-primary" onclick="showRunDetail('${escapeHtml(run.run_code)}')" title="View">
                    <i class="ri-eye-line"></i>
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="copyRunCode('${escapeHtml(run.run_code)}')" title="Copy code">
                    <i class="ri-file-copy-line"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteSavedRun(${run.id}, '${escapeHtml(run.name)}')" title="Delete">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `).join('');

    if (data.pagination.last_page > 1) {
        let pHTML = '<nav><ul class="pagination pagination-sm mb-0 justify-content-center">';
        for (let p = 1; p <= data.pagination.last_page; p++) {
            pHTML += `<li class="page-item ${p === data.pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault();jumpToRunPage(${p})">${p}</a>
            </li>`;
        }
        pHTML += '</ul></nav>';
        document.getElementById('savedRunsPagination').innerHTML = pHTML;
    } else {
        document.getElementById('savedRunsPagination').innerHTML = '';
    }
}

function clearRunFilters() {
    document.getElementById('runSearchInput').value = '';
    document.getElementById('runFilterSession').value = '';
    document.getElementById('runFilterTerm').value = '';
    document.getElementById('runFilterClass').value = '';
    document.getElementById('runFilterStatus').value = '';
    document.getElementById('runFilterDateFrom').value = '';
    document.getElementById('runFilterDateTo').value = '';
    loadSavedRuns();
}

function lookupRunByCode() {
    const code = document.getElementById('runCodeLookup').value.trim();
    if (!code) return;
    if (code.length !== 10) {
        return AppleAlert.warning('Invalid code', 'Run codes are exactly 10 characters.');
    }
    showRunDetail(code);
}

// ============================================================================
// RUN DETAIL — tabbed
// ============================================================================
async function showRunDetail(identifier) {
    const modal = new bootstrap.Modal(document.getElementById('runDetailModal'));
    document.getElementById('runDetailTitle').textContent = 'Loading…';
    document.getElementById('runDetailCode').textContent = '';
    document.getElementById('runDetailStats').innerHTML = '';
    document.getElementById('runDetailPane_overview').innerHTML =
        '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary"></div><p class="mt-3">Loading…</p></div>';
    document.getElementById('runDetailPane_classes').innerHTML = '';
    document.getElementById('runDetailPane_metadata').innerHTML = '';

    // Reset to Overview tab.
    runDetailTab('overview', document.querySelector('.run-detail-tab[data-tab="overview"]'));

    modal.show();

    try {
        const res  = await fetch(`${ROUTES.runsShow}/${encodeURIComponent(identifier)}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed.');

        currentRunData = data;
        currentRun = data.run;

        renderRunDetail(data);
    } catch (e) {
        document.getElementById('runDetailPane_overview').innerHTML =
            `<div class="alert alert-danger m-0">Failed: ${escapeHtml(e.message)}</div>`;
    }
}

function renderRunDetail(data) {
    const r = data.run;

    // Header
    document.getElementById('runDetailTitle').textContent = r.name;
    document.getElementById('runDetailCode').textContent =
        `Run ${r.run_code} · ${r.session || '—'}${r.term ? ' · ' + r.term : ''} · ${r.created_at}`;

    // Stat strip
    const statusClass = r.status === 'success' ? 'ok' : r.status === 'reverted' ? 'bad' : 'warn';
    const statusText  = r.status === 'success' ? 'Success' : r.status === 'reverted' ? 'Reverted' : 'Shortfalls';

    document.getElementById('runDetailStats').innerHTML = `
        <div class="run-detail-stat">
            <div class="lbl">Classes</div>
            <div class="val">${r.class_count}</div>
        </div>
        <div class="run-detail-stat ok">
            <div class="lbl">Lessons placed</div>
            <div class="val">${r.total_placed}</div>
        </div>
        <div class="run-detail-stat">
            <div class="lbl">Created by</div>
            <div class="val small">${escapeHtml(r.creator || '—')}</div>
        </div>
        <div class="run-detail-stat ${statusClass}">
            <div class="lbl">Status</div>
            <div class="val small">${statusText}</div>
        </div>`;

    // Overview pane
    document.getElementById('runDetailPane_overview').innerHTML = `
        <div class="run-overview-grid">
            <div class="run-overview-section">
                <h6>Run</h6>
                <div class="run-kv"><span class="k">Name</span><span class="v">${escapeHtml(r.name)}</span></div>
                <div class="run-kv"><span class="k">Code</span><span class="v mono">${escapeHtml(r.run_code)}</span></div>
                <div class="run-kv"><span class="k">Session</span><span class="v">${escapeHtml(r.session || '—')}</span></div>
                <div class="run-kv"><span class="k">Term</span><span class="v">${escapeHtml(r.term || 'All Terms')}</span></div>
                <div class="run-kv"><span class="k">Seed</span><span class="v mono">${r.seed ?? '—'}</span></div>
            </div>
            <div class="run-overview-section">
                <h6>Provenance</h6>
                <div class="run-kv"><span class="k">Created by</span><span class="v">${escapeHtml(r.creator || '—')}</span></div>
                <div class="run-kv"><span class="k">Created</span><span class="v">${escapeHtml(r.created_at)}</span></div>
                <div class="run-kv"><span class="k">Status</span><span class="v">${statusText}</span></div>
            </div>
        </div>
        ${r.description ? `
        <div class="run-overview-section" style="margin-top:24px">
            <h6>Description</h6>
            <div style="font-size:13.5px;color:#334155;line-height:1.55">${escapeHtml(r.description)}</div>
        </div>` : ''}`;

    // Classes pane
    renderRunClassesPane(data.classes);

    // Metadata pane
    renderRunMetadataPane(r);
}

function renderRunClassesPane(classes) {
    const pane = document.getElementById('runDetailPane_classes');
    const badge = document.getElementById('runDetailClassesBadge');
    if (badge) badge.textContent = classes.length;

    if (!classes.length) {
        pane.innerHTML = '<div class="run-classes-empty"><i class="ri-inbox-line ri-3x d-block mb-2"></i>No classes in this run.</div>';
        return;
    }

    // Sort by placed desc, so problem classes (0 placed) sink to the bottom
    // and fully-populated classes rise to the top.
    const sorted = [...classes].sort((a, b) => (b.placed || 0) - (a.placed || 0));

    let html = `<table class="run-classes-table">
        <thead>
            <tr>
                <th>Class</th>
                <th class="num">Placed</th>
                <th class="num">Empty</th>
                <th class="num">Room issues</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>`;

    sorted.forEach(c => {
        const placed = c.placed || 0;
        const shortfall = c.room_shortfall || 0;

        let statusLabel, statusClass;
        if (placed === 0) {
            statusLabel = 'Empty';
            statusClass = 'empty';
        } else if (shortfall > 0) {
            statusLabel = 'Partial';
            statusClass = 'partial';
        } else {
            statusLabel = 'Full';
            statusClass = 'full';
        }

        const placedClass = placed === 0 ? 'stat-zero' : '';
        const shortfallClass = shortfall === 0 ? 'stat-zero' : '';

        html += `<tr>
            <td class="class-name">${escapeHtml(c.class_name)}</td>
            <td class="num ${placedClass}">${placed}</td>
            <td class="num stat-zero">—</td>
            <td class="num ${shortfallClass}">${shortfall || '—'}</td>
            <td><span class="run-status-pill ${statusClass}">${statusLabel}</span></td>
        </tr>`;
    });

    html += `</tbody></table>`;
    pane.innerHTML = html;
}

function renderRunMetadataPane(r) {
    const pane = document.getElementById('runDetailPane_metadata');

    const metadata = {
        wizard_input: r.wizard_input || null,
        advanced_rules: r.advanced_rules || null,
    };

    pane.innerHTML = `
        <div style="margin-bottom:12px;font-size:12.5px;color:#64748B">
            <i class="ri-information-line me-1"></i>
            The exact wizard inputs that produced this run. Useful when reproducing a specific layout.
        </div>
        <pre class="run-meta-pre">${syntaxHighlightJson(metadata)}</pre>`;
}

function syntaxHighlightJson(obj) {
    const json = JSON.stringify(obj, null, 2);
    return json
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"([^"\\]*(?:\\.[^"\\]*)*)"(\s*:)?/g, (match, content, colon) => {
            if (colon) return `<span class="key">"${content}"</span>${colon}`;
            return `<span class="str">"${content}"</span>`;
        })
        .replace(/\b(true|false)\b/g, '<span class="bool-t">$1</span>')
        .replace(/\bnull\b/g, '<span class="null">null</span>')
        .replace(/\b(-?\d+(?:\.\d+)?)\b/g, '<span class="num">$1</span>');
}

function runDetailTab(tabName, btn) {
    document.querySelectorAll('.run-detail-tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');

    document.querySelectorAll('.run-detail-pane').forEach(p => p.style.display = 'none');
    const pane = document.getElementById('runDetailPane_' + tabName);
    if (pane) pane.style.display = '';
}

function copyRunCode(code) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(() => AppleAlert.copied(`Run code ${code} copied`));
    } else {
        const ta = document.createElement('textarea');
        ta.value = code;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        AppleAlert.copied(`Run code ${code} copied`);
    }
}

async function deleteSavedRun(runId, name) {
    const ok = await AppleAlert.confirmDelete(
        'Delete this saved run?',
        `Permanently removes <strong>${escapeHtml(name)}</strong> and its frozen snapshots. Live timetables are not affected.`
    );
    if (!ok) return;
    try {
        const res  = await fetch(`${ROUTES.runsDelete}/${runId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            loadSavedRuns();
            AppleAlert.deleted('Saved run deleted');
        } else {
            AppleAlert.error('Could not delete', data.message || 'Please try again.');
        }
    } catch (e) {
        AppleAlert.error('Could not delete', e.message);
    }
}

// ============================================================================
// SAVE RUN MODAL — inline success reveal
// ============================================================================
function openSaveRunModal() {
    // Reset the modal to its input state every time it opens.
    document.getElementById('saveRunInputView').style.display = '';
    document.getElementById('saveRunSuccessView').style.display = 'none';

    const sessionOpt = document.querySelector('#wizSessionId option:checked');
    const defaultName = sessionOpt
        ? `Run — ${sessionOpt.textContent.trim()} (${new Date().toLocaleDateString()})`
        : `Run — ${new Date().toLocaleString()}`;
    document.getElementById('saveRunName').value = defaultName;
    document.getElementById('saveRunDescription').value = '';

    // Populate the "will save" preview from the wizard's current state.
    renderSaveRunPreview();

    new bootstrap.Modal(document.getElementById('saveRunModal')).show();
}

function renderSaveRunPreview() {
    const preview = document.getElementById('saveRunPreview');
    if (!preview) return;

    const scope    = document.getElementById('wizScope').value;
    const classIds = scope === 'selected'
        ? [...document.getElementById('wizClassIds').selectedOptions].map(o => parseInt(o.value))
        : null;

    const effectiveIds = classIds ?? [...document.getElementById('wizClassIds').options].map(o => parseInt(o.value));

    let classCount = 0;
    let lessonCount = 0;
    let shortfallCount = 0;

    if (Object.keys(wizardSubjectsState).length) {
        Object.keys(wizardSubjectsState).forEach((classId) => {
            if (scope === 'selected' && !classIds.includes(parseInt(classId))) return;
            classCount++;
        });
    }

    // Try to pull the actual generated counts from the last preview run.
    const stats = previewState?.stats;
    if (stats) {
        lessonCount    = stats.placed ?? 0;
        shortfallCount = stats.room_shortfall_count ?? 0;
    }

    // Class count falls back to the current scope count.
    if (!classCount) classCount = effectiveIds.length;

    const sessionText = document.querySelector('#wizSessionId option:checked')?.textContent.trim() || '—';
    const termText    = document.querySelector('#wizTermId option:checked')?.textContent.trim()  || 'All Terms';

    let html = '';
    html += `<div class="save-run-preview-row"><span class="k">Session</span><span class="v">${escapeHtml(sessionText)}</span></div>`;
    html += `<div class="save-run-preview-row"><span class="k">Term</span><span class="v">${escapeHtml(termText)}</span></div>`;
    html += `<div class="save-run-preview-row"><span class="k">Classes</span><span class="v">${classCount}</span></div>`;
    if (lessonCount) {
        html += `<div class="save-run-preview-row"><span class="k">Lessons placed</span><span class="v">${lessonCount}</span></div>`;
    }
    if (shortfallCount) {
        html += `<div class="save-run-preview-row"><span class="k">Room shortfalls</span><span class="v warn">${shortfallCount}</span></div>`;
    }

    preview.innerHTML = html;
}

async function saveGenerationRun() {
    const name = document.getElementById('saveRunName').value.trim();
    if (!name) return AppleAlert.warning('Required', 'Please give this run a name.');

    const sessionId = document.getElementById('wizSessionId').value;
    if (!sessionId) return AppleAlert.warning('Required', 'Select a session in the wizard first.');

    const scope    = document.getElementById('wizScope').value;
    const classIds = scope === 'selected'
        ? [...document.getElementById('wizClassIds').selectedOptions].map(o => parseInt(o.value))
        : null;

    const payload = {
        name:            name,
        description:     document.getElementById('saveRunDescription').value.trim() || null,
        session_id:      parseInt(sessionId),
        term_id:         document.getElementById('wizTermId').value || null,
        schoolclass_ids: classIds,
        wizard_input: {
            scope:           scope,
            class_ids:       classIds,
            day_start:       document.getElementById('wizDayStart').value,
            day_end:         document.getElementById('wizDayEnd').value,
            lessons_per_day: parseInt(document.getElementById('wizLessonsPerDay').value),
            period_minutes:  parseInt(document.getElementById('wizPeriodDuration').value),
            active_days:     [...document.querySelectorAll('.wiz-active-day:checked')].map(cb => cb.value),
            include_rooms:   document.getElementById('wizIncludeRooms').checked,
        },
        advanced_rules:  collectWizardAdvancedRules(),
    };

    // Disable the button so double-tap doesn't fire two saves.
    const btn = document.getElementById('saveRunBtn');
    const originalBtnHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';

    try {
        const res  = await apiFetch(ROUTES.runsSave, 'POST', payload);
        const data = await res.json();

        if (data.success) {
            showSaveRunSuccess(data);
        } else {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
            AppleAlert.error('Save failed', data.message || 'Please try again.');
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = originalBtnHtml;
        AppleAlert.error('Save failed', e.message);
    }
}

function showSaveRunSuccess(data) {
    // Swap views inside the same modal.
    document.getElementById('saveRunInputView').style.display = 'none';
    document.getElementById('saveRunSuccessView').style.display = '';

    document.getElementById('savedRunCode').textContent = data.run_code;

    const sessionText = document.querySelector('#wizSessionId option:checked')?.textContent.trim() || '—';
    const termText    = document.querySelector('#wizTermId option:checked')?.textContent.trim()  || 'All Terms';
    const classNameCount = (function () {
        const scope = document.getElementById('wizScope').value;
        const classIds = scope === 'selected'
            ? [...document.getElementById('wizClassIds').selectedOptions].map(o => parseInt(o.value))
            : null;
        return classIds ? classIds.length : document.getElementById('wizClassIds').options.length;
    })();

    document.getElementById('savedRunMeta').innerHTML =
        `${classNameCount} class${classNameCount === 1 ? '' : 'es'} · ${escapeHtml(sessionText)}` +
        `${termText !== 'All Terms' ? ' · ' + escapeHtml(termText) : ''} · saved just now`;

    // Reset the copy button state.
    const copyBtn = document.getElementById('savedRunCopyBtn');
    copyBtn.classList.remove('copied');
    copyBtn.innerHTML = '<i class="ri-file-copy-line"></i>';

    // Re-enable the save button for the next time this modal opens.
    const btn = document.getElementById('saveRunBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="ri-save-line me-1"></i>Save run';
}

function copySavedRunCode() {
    const code = document.getElementById('savedRunCode').textContent.trim();
    if (!code || code === '—') return;

    const doDone = () => {
        const copyBtn = document.getElementById('savedRunCopyBtn');
        copyBtn.classList.add('copied');
        copyBtn.innerHTML = '<i class="ri-check-line"></i>';
        AppleAlert.copied('Run code copied');
        setTimeout(() => {
            copyBtn.classList.remove('copied');
            copyBtn.innerHTML = '<i class="ri-file-copy-line"></i>';
        }, 1600);
    };

    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(doDone).catch(() => fallbackCopy(code, doDone));
    } else {
        fallbackCopy(code, doDone);
    }
}

function fallbackCopy(text, onDone) {
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); if (onDone) onDone(); } catch (e) {}
    document.body.removeChild(ta);
}

function closeSaveRunAndBrowse() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('saveRunModal'));
    if (modal) modal.hide();

    // Jump to the Saved Runs tab and refresh the list so the new run shows.
    const tabBtn = document.getElementById('tabSavedRunsBtn');
    if (tabBtn) {
        new bootstrap.Tab(tabBtn).show();
        loadSavedRuns();
    }
}

// ============================================================================
// RESTORE SAVED RUN TO LIVE
// ============================================================================
function openRestoreModal() {
    if (!currentRun) return AppleAlert.error('No run loaded');
    document.getElementById('restoreRunSummary').innerHTML =
        '<div class="spinner-border spinner-border-sm me-2"></div>Preparing…';
    document.getElementById('restoreForce').checked = false;
    document.getElementById('restoreUnpublish').checked = false;
    new bootstrap.Modal(document.getElementById('restoreRunModal')).show();
    previewRestoreRun();
}

async function previewRestoreRun() {
    try {
        const res  = await fetch(`${ROUTES.runsShow}/${currentRun.run_code}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        const classes = data.classes;
        let html = `
            <div class="mb-3">
                <strong>Run:</strong> ${escapeHtml(data.run.name)} · <code>${escapeHtml(data.run.run_code)}</code><br>
                <strong>Saved:</strong> ${escapeHtml(data.run.created_at)} by ${escapeHtml(data.run.creator || '—')}
            </div>
            <div class="mb-2"><strong>Will restore these ${classes.length} classes:</strong></div>
            <div style="max-height:200px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:8px;padding:8px">
                ${classes.map(c => `
                    <div class="d-flex justify-content-between align-items-center py-1" style="font-size:13px;border-bottom:1px solid #F1F5F9">
                        <label class="mb-0">
                            <input type="checkbox" class="form-check-input me-2 restore-class-checkbox" value="${c.schoolclass_id}" checked>
                            ${escapeHtml(c.class_name)}
                        </label>
                        <span class="text-muted">${c.placed} lessons · ${c.room_shortfall} room-short</span>
                    </div>
                `).join('')}
            </div>`;
        document.getElementById('restoreRunSummary').innerHTML = html;
    } catch (e) {
        document.getElementById('restoreRunSummary').innerHTML =
            `<div class="alert alert-danger m-0">Failed: ${escapeHtml(e.message)}</div>`;
    }
}

async function confirmRestoreRun() {
    if (!currentRun) return;
    const classIds = [...document.querySelectorAll('.restore-class-checkbox:checked')].map(cb => parseInt(cb.value));
    if (!classIds.length) return AppleAlert.warning('Required', 'Select at least one class to restore.');

    const force      = document.getElementById('restoreForce').checked;
    const unpublish  = document.getElementById('restoreUnpublish').checked;

    showLoader();
    try {
        const res = await fetch(`${ROUTES.runsRestore}/${currentRun.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                schoolclass_ids:   classIds,
                force_overwrite:   force,
                unpublish_locked:  unpublish,
            }),
        });
        const data = await res.json();
        hideLoader();

        bootstrap.Modal.getInstance(document.getElementById('restoreRunModal'))?.hide();

        if (data.success) {
            let html = `<p>${escapeHtml(data.message)}</p>`;
            if (data.skipped?.length) {
                html += '<p style="margin-top:12px;font-size:13px;font-weight:600;color:#0F172A;text-align:left">Skipped:</p>';
                html += '<ul class="apple-alert-list">';
                html += data.skipped.map(s =>
                    `<li><strong>${escapeHtml(s.class_name)}</strong> — <em>${escapeHtml(s.reason.replace(/_/g, ' '))}</em></li>`
                ).join('');
                html += '</ul>';
            }
            AppleAlert.rich({
                title: 'Restored',
                html: html,
                icon: 'success',
                confirmText: 'OK',
                theme: 'success',
                width: 520,
            }).then(() => location.reload());
        } else {
            AppleAlert.error('Restore failed', data.message || 'Please try again.');
        }
    } catch (e) {
        hideLoader();
        AppleAlert.error('Restore failed', e.message);
    }
}

// ============================================================================
// COMPARE RUNS
// ============================================================================
function compareRunWithAnother() {
    if (!currentRun) return AppleAlert.error('No run loaded');

    fetch(`${ROUTES.runsList}?per_page=100`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const options = data.runs
                .filter(r => r.id !== currentRun.id)
                .map(r => `<option value="${r.id}">${escapeHtml(r.name)} — ${escapeHtml(r.run_code)}</option>`)
                .join('');

            AppleAlert.rich({
                title: 'Compare with…',
                html: `<select id="compareTarget" class="swal2-select" style="width:100%">
                    <option value="">— Pick another run —</option>
                    ${options}
                </select>`,
                showCancelButton: true,
                confirmText: 'Compare',
                cancelText: 'Cancel',
                theme: 'primary',
                preConfirm: () => document.getElementById('compareTarget').value,
            }).then(result => {
                if (!result.isConfirmed || !result.value) return;
                runComparison(currentRun.id, parseInt(result.value));
            });
        });
}

async function runComparison(idA, idB) {
    const modal = new bootstrap.Modal(document.getElementById('compareRunsModal'));
    document.getElementById('compareRunsBody').innerHTML =
        '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary"></div><p class="mt-3">Comparing…</p></div>';
    modal.show();

    try {
        const res  = await fetch(ROUTES.runsCompare, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ run_a_id: idA, run_b_id: idB }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        renderComparison(data);
    } catch (e) {
        document.getElementById('compareRunsBody').innerHTML =
            `<div class="alert alert-danger m-0">Failed: ${escapeHtml(e.message)}</div>`;
    }
}

function renderComparison(data) {
    const wrap = document.getElementById('compareRunsBody');
    const s = data.summary;

    let html = `
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#cmpSummary">Summary</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cmpInputs">Input Diff (${data.input_diff.length})</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cmpClasses">Per-Class Diff</button></li>
        </ul>
        <div class="tab-content">

            <div class="tab-pane fade show active" id="cmpSummary">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="mini-strip">
                            <div class="v" style="font-size:14px">${escapeHtml(data.run_a.name)}</div>
                            <div class="l">${escapeHtml(data.run_a.run_code)} · ${data.run_a.classes} classes · ${data.run_a.placed} placed</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mini-strip">
                            <div class="v" style="font-size:14px">${escapeHtml(data.run_b.name)}</div>
                            <div class="l">${escapeHtml(data.run_b.run_code)} · ${data.run_b.classes} classes · ${data.run_b.placed} placed</div>
                        </div>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-3"><div class="mini-strip ok"><div class="v">${s.identical_cells}</div><div class="l">Identical cells</div></div></div>
                    <div class="col-md-3"><div class="mini-strip warn"><div class="v">${s.differing_cells}</div><div class="l">Differing cells</div></div></div>
                    <div class="col-md-3"><div class="mini-strip"><div class="v">${s.only_in_a_cells}</div><div class="l">Only in A</div></div></div>
                    <div class="col-md-3"><div class="mini-strip"><div class="v">${s.only_in_b_cells}</div><div class="l">Only in B</div></div></div>
                </div>

                ${data.only_in_a.length || data.only_in_b.length ? `
                    <hr class="my-4">
                    <h6 class="mb-2">Class scope difference</h6>
                    ${data.only_in_a.length ? `<div class="mb-2"><strong>Only in A:</strong> ${data.only_in_a.map(c => escapeHtml(c.name)).join(', ')}</div>` : ''}
                    ${data.only_in_b.length ? `<div class="mb-2"><strong>Only in B:</strong> ${data.only_in_b.map(c => escapeHtml(c.name)).join(', ')}</div>` : ''}
                ` : ''}
            </div>

            <div class="tab-pane fade" id="cmpInputs">
                ${data.input_diff.length ? `
                    <table class="table table-sm">
                        <thead class="table-light"><tr><th>Parameter</th><th>Run A</th><th>Run B</th></tr></thead>
                        <tbody>
                            ${data.input_diff.map(d => `
                                <tr>
                                    <td><code>${escapeHtml(d.key)}</code></td>
                                    <td>${escapeHtml(String(d.a ?? '—'))}</td>
                                    <td>${escapeHtml(String(d.b ?? '—'))}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                ` : '<p class="text-muted">All wizard inputs and advanced rules are identical.</p>'}
            </div>

            <div class="tab-pane fade" id="cmpClasses">
                ${data.shared_classes.map(cd => renderClassDiff(cd)).join('')}
            </div>
        </div>`;

    wrap.innerHTML = html;
}

function renderClassDiff(cd) {
    const diffCells = cd.cells.filter(c => c.state !== 'identical');
    if (!diffCells.length) {
        return `<div class="alert alert-success mb-2" style="font-size:13px">
            <strong>${escapeHtml(cd.class_name)}</strong> — identical in both runs.
        </div>`;
    }

    const cellMap = {};
    cd.cells.forEach(c => cellMap[`${c.period_id}|${c.day}`] = c);

    let html = `<h6 class="mb-2 mt-3">${escapeHtml(cd.class_name)} <span class="text-muted" style="font-size:12px">(${diffCells.length} differing cells)</span></h6>
        <table class="table table-sm table-bordered" style="font-size:12px">
            <thead><tr><th style="width:140px">Period</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th></tr></thead>
            <tbody>`;

    const periods = cd.periods_a;
    periods.forEach(p => {
        html += `<tr><td>${escapeHtml(p.name)}</td>`;
        ['Monday','Tuesday','Wednesday','Thursday','Friday'].forEach(day => {
            const cell = cellMap[`${p.id}|${day}`];
            if (!cell) { html += '<td></td>'; return; }

            const label = (sig) => {
                if (!sig) return '—';
                if (sig.is_free) return 'Free';
                const parts = [];
                parts.push(sig.subject_id ? `subj ${sig.subject_id}` : '');
                parts.push(sig.teacher_id ? `t ${sig.teacher_id}` : '');
                return parts.filter(Boolean).join(' / ') || '—';
            };

            const bg = {
                'identical': '#F0FDF4',
                'differing': '#FFFBEB',
                'only_in_a': '#EFF6FF',
                'only_in_b': '#FEF2F2',
            }[cell.state] || '';

            html += `<td style="background:${bg};font-size:11px">
                ${cell.state === 'identical'
                    ? ''
                    : `<div><strong>A:</strong> ${escapeHtml(label(cell.a))}</div>
                       <div><strong>B:</strong> ${escapeHtml(label(cell.b))}</div>`}
            </td>`;
        });
        html += '</tr>';
    });

    html += '</tbody></table>';
    return html;
}

// ============================================================================
// EXPORT RUN TO PDF
// ============================================================================
function exportRunToPdf() {
    if (!currentRun) return AppleAlert.error('No run loaded');
    new bootstrap.Modal(document.getElementById('exportRunModal')).show();
}

function submitExportRun() {
    if (!currentRun) return;
    const params = new URLSearchParams({
        format:        document.getElementById('exportRunFormat').value,
        mode:          document.getElementById('exportRunMode').value,
        orientation:   document.getElementById('exportRunOrientation').value,
        paper:         document.getElementById('exportRunPaper').value,
        include_meta:  document.getElementById('exportRunIncludeMeta').checked ? 1 : 0,
        include_rules: document.getElementById('exportRunIncludeRules').checked ? 1 : 0,
    });

    const url = `${ROUTES.runsExport}/${currentRun.id}/export?${params.toString()}`;
    window.open(url, '_blank');
    bootstrap.Modal.getInstance(document.getElementById('exportRunModal'))?.hide();
}

// ============================================================================
// QUICK REBUILD
// ============================================================================
function openAnchorRebuildPanel() {
    if (!currentSettingId) return AppleAlert.warning('No class loaded', 'Load or create a class timetable first.');
    new bootstrap.Modal(document.getElementById('anchorRebuildModal')).show();
}

async function submitAnchorRebuild() {
    const assemblyChecked = document.getElementById('arAssemblyEnabled').checked;
    const payload = {
        setting_id:                    currentSettingId,
        lessons_per_day:                parseInt(document.getElementById('arLessonsPerDay').value),
        short_break_after_period:       document.getElementById('arShortBreakAfter').value ? parseInt(document.getElementById('arShortBreakAfter').value) : null,
        long_break_after_period:        document.getElementById('arLongBreakAfter').value ? parseInt(document.getElementById('arLongBreakAfter').value) : null,
        assembly_day:                   assemblyChecked ? document.getElementById('arAssemblyDay').value : null,
        short_break_duration_minutes:   parseInt(document.getElementById('arShortBreakDuration').value),
        long_break_duration_minutes:    parseInt(document.getElementById('arLongBreakDuration').value),
        period_duration_minutes:        parseInt(document.getElementById('arPeriodDuration').value),
        school_day_start:               document.getElementById('arDayStart').value,
    };

    showLoader();
    try {
        const res  = await apiFetch(ROUTES.rebuildPeriodsFromAnchors, 'POST', payload);
        const data = await res.json();
        if (!data.success) {
            hideLoader();
            AppleAlert.error('Could not rebuild', data.message || 'Please try again.');
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('anchorRebuildModal'))?.hide();
        hideLoader();
        await loadSetting(currentSettingId);
        AppleAlert.saved('Periods rebuilt');
    } catch (e) {
        hideLoader();
        AppleAlert.error('Could not rebuild', e.message);
    }
}

// ============================================================================
// VERSION CONFLICT HANDLER
// ============================================================================
async function handleVersionConflict(data) {
    const result = await AppleAlert.confirm(
        'Timetable changed',
        data.message || 'This timetable was modified by someone else. Reload to get the latest version?',
        { confirmText: 'Reload', cancelText: 'Stay' }
    );
    if (result.isConfirmed && currentSettingId) {
        await loadSetting(currentSettingId);
    }
}

// ============================================================================
// DOM INIT
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap popovers for help icons.
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => new bootstrap.Popover(el));

    // Advanced panel remembers open/closed state across wizard opens.
    const details = document.getElementById('wizAdvancedPanel');
    if (details) {
        const wasOpen = localStorage.getItem('wiz_advanced_open') === '1';
        details.open = wasOpen;
        details.addEventListener('toggle', () => {
            localStorage.setItem('wiz_advanced_open', details.open ? '1' : '0');
        });
    }

    // Enable/disable the fixed morning-cutoff input based on radio state.
    document.querySelectorAll('input[name="wizMorningCutoffMode"]').forEach(r => {
        r.addEventListener('change', () => {
            const fixedEl = document.getElementById('wizMorningCutoffFixedCount');
            if (fixedEl) {
                fixedEl.disabled = document.querySelector('input[name="wizMorningCutoffMode"]:checked')?.value !== 'fixed';
            }
        });
    });
});
</script>

@endsection 
