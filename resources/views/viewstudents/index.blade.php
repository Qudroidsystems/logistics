@extends('layouts.master')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<style>
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

/* Hero Section */
.cb-hero {
    background: linear-gradient(135deg, var(--cb-navy) 0%, #1e4a7e 55%, #0d9488 100%);
    border-radius: var(--cb-radius);
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.cb-hero::before {
    content: '';
    position: absolute;
    top: -80px;
    right: -80px;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
    border-radius: 50%;
}
.cb-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 8px;
}
.cb-hero p {
    font-size: 13px;
    color: rgba(255,255,255,.72);
    margin: 0;
}
.cb-hero .meta-pills {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
}
.cb-meta-pill {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Stat Cards */
.cb-stat {
    background: var(--cb-white);
    border: 1px solid var(--cb-border);
    border-radius: var(--cb-radius);
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: transform .15s, box-shadow .15s;
}
.cb-stat:hover {
    transform: translateY(-2px);
    box-shadow: var(--cb-shadow);
}
.cb-stat .stat-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: var(--cb-radius) var(--cb-radius) 0 0;
}
.cb-stat .stat-value {
    font-size: 30px;
    font-weight: 700;
    color: var(--cb-navy);
    line-height: 1;
    margin-top: 8px;
}
.cb-stat .stat-label {
    font-size: 12px;
    color: var(--cb-muted);
    margin-top: 5px;
    font-weight: 500;
}
.cb-stat .stat-ico {
    font-size: 36px;
    opacity: .08;
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
}

/* Filter Panel */
.filter-panel {
    background: var(--cb-white);
    border: 1px solid var(--cb-border);
    border-radius: var(--cb-radius);
    padding: 20px 24px;
    margin-bottom: 28px;
    box-shadow: var(--cb-shadow);
}
.filter-panel h6 {
    font-size: 13px;
    font-weight: 700;
    color: var(--cb-navy);
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 7px;
}
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}
.filter-item label {
    font-size: 11px;
    font-weight: 600;
    color: var(--cb-muted);
    margin-bottom: 6px;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.filter-item input, .filter-item select {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid var(--cb-border);
    border-radius: 10px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.15s;
    background: var(--cb-surface);
}
.filter-item input:focus, .filter-item select:focus {
    border-color: var(--cb-teal);
    outline: none;
    box-shadow: 0 0 0 3px rgba(13,148,136,.1);
}
.btn-filter {
    background: var(--cb-teal);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    justify-content: center;
}
.btn-filter:hover {
    background: #0f766e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13,148,136,0.3);
}
.btn-reset {
    background: #f1f5f9;
    color: var(--cb-muted);
    border: 1.5px solid var(--cb-border);
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    justify-content: center;
}
.btn-reset:hover {
    background: #e2e8f0;
    border-color: var(--cb-teal);
    color: var(--cb-teal);
}

/* Main Card */
.cb-card {
    background: var(--cb-white);
    border: 1px solid var(--cb-border);
    border-radius: var(--cb-radius);
    box-shadow: var(--cb-shadow);
    overflow: hidden;
}
.cb-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--cb-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    background: linear-gradient(to right, #f8fafc, #f0fdf9);
}
.cb-card-header h5 {
    font-size: 15px;
    font-weight: 700;
    color: var(--cb-navy);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.class-info-badge {
    background: var(--cb-teal);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}
.btn-back {
    background: #f1f5f9;
    color: var(--cb-muted);
    border: 1px solid var(--cb-border);
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}
.btn-back:hover {
    background: #e2e8f0;
    color: var(--cb-teal);
    transform: translateY(-1px);
}

/* Table Styles */
.cb-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}
.cb-table thead th {
    background: var(--cb-navy);
    color: #fff;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 11.5px;
    white-space: nowrap;
    text-align: left;
    border-right: 1px solid rgba(255,255,255,.08);
}
.cb-table tbody td {
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--cb-border);
}
.cb-table tbody tr:hover td {
    background: #f0fdf9;
}
.cb-table tbody tr:last-child td {
    border-bottom: none;
}

/* Student Avatar */
.student-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--cb-border);
    cursor: pointer;
    transition: all 0.2s;
}
.student-avatar:hover {
    border-color: var(--cb-teal);
    transform: scale(1.05);
}
.student-name-btn {
    font-weight: 600;
    color: var(--cb-navy);
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    font-size: 12.5px;
    text-align: left;
    transition: color 0.15s;
}
.student-name-btn:hover {
    color: var(--cb-teal);
}

/* Gender Badge */
.gender-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.gender-male   { background: #dbeafe; color: #1e40af; }
.gender-female { background: #fce7f3; color: #be185d; }

/* Action Button */
.btn-view-profile {
    background: linear-gradient(135deg, #e0f2fe, #bae6fd);
    color: #0369a1;
    border: 1px solid #7dd3fc;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    font-family: 'DM Sans', sans-serif;
}
.btn-view-profile:hover {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(14,165,233,0.25);
    border-color: transparent;
}

/* Chart Card */
.chart-card {
    background: var(--cb-white);
    border: 1px solid var(--cb-border);
    border-radius: var(--cb-radius);
    margin-bottom: 28px;
    overflow: hidden;
}
.chart-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--cb-border);
    background: linear-gradient(to right, #f8fafc, #f0fdf9);
}
.chart-header h5 {
    font-size: 14px;
    font-weight: 700;
    color: var(--cb-navy);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.chart-body { padding: 20px; }

/* Pagination */
.pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
}
.page-item {
    display: inline-flex;
    padding: 8px 12px;
    border: 1px solid var(--cb-border);
    border-radius: 8px;
    color: var(--cb-navy);
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.15s;
    background: white;
    cursor: pointer;
}
.page-item:hover:not(:disabled) {
    background: var(--cb-teal);
    color: white;
    border-color: var(--cb-teal);
}
.page-item.active {
    background: var(--cb-teal);
    color: white;
    border-color: var(--cb-teal);
}
.page-item:disabled { opacity: 0.5; cursor: not-allowed; }

/* Empty State */
.empty-state { text-align: center; padding: 60px; }
.empty-state i { font-size: 64px; color: #cbd5e1; margin-bottom: 16px; display: block; }
.empty-state h6 { font-size: 18px; color: var(--cb-navy); margin-bottom: 8px; }
.empty-state p  { color: var(--cb-muted); font-size: 13px; }

/* Image Modal */
#imageViewModal .modal-content { border-radius: var(--cb-radius); overflow: hidden; }
#imageViewModal .modal-header  { background: linear-gradient(135deg, var(--cb-navy), var(--cb-teal)); color: white; border: none; }
#imageViewModal .modal-header .btn-close { filter: invert(1); }
#imageViewModal .modal-body img { max-width: 100%; max-height: 400px; object-fit: contain; border-radius: 12px; }

/* Toast Notification */
.cb-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #1e293b;
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    z-index: 9999;
    animation: slideIn 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 8px;
}
.cb-toast-success { background: #059669; }
.cb-toast-error   { background: #dc2626; }
.cb-toast-info    { background: #3b82f6; }

/* Animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}
.cb-card, .cb-stat, .chart-card { animation: fadeInUp 0.4s ease; }

/* Responsive */
@media (max-width: 768px) {
    .cb-hero { padding: 24px 20px; }
    .cb-hero h1 { font-size: 22px; }
    .filter-grid { grid-template-columns: 1fr; }
    .cb-table thead { display: none; }
    .cb-table tbody td {
        display: block;
        text-align: right;
        padding-left: 50%;
        position: relative;
    }
    .cb-table tbody td:before {
        content: attr(data-label);
        position: absolute;
        left: 16px;
        width: 45%;
        text-align: left;
        font-weight: 600;
        color: var(--cb-navy);
        font-size: 11px;
    }
    .pagination-wrap { justify-content: center; flex-wrap: wrap; }
}

/* ──────────────────────────────────────────
   PROFILE DRAWER STYLES
────────────────────────────────────────── */
.sp-pill {
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 11.5px;
    font-weight: 600;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.sp-tab {
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: rgba(255,255,255,.6);
    font-size: 12.5px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    padding: 10px 16px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .2s;
    border-radius: 10px 10px 0 0;
}
.sp-tab:hover { color: rgba(255,255,255,.9); background: rgba(255,255,255,.07); }
.sp-tab-active { color: #fff !important; border-bottom-color: #0d9488 !important; background: rgba(255,255,255,.1) !important; }
.sp-section-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,35,66,.06);
}
.sp-section-header {
    padding: 13px 18px;
    background: linear-gradient(to right, #f8fafc, #f0fdf9);
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #0f2342;
}
.sp-trait-grid {
    padding: 12px 16px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 10px;
}
.sp-trait-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    transition: border-color .15s, box-shadow .15s;
}
.sp-trait-item:hover { border-color: #0d9488; box-shadow: 0 2px 8px rgba(13,148,136,.08); }
.sp-trait-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.sp-trait-label i { font-size: 13px; color: #94a3b8; }
.sp-select {
    width: 100%;
    padding: 7px 10px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12.5px;
    font-family: 'DM Sans', sans-serif;
    background: #fff;
    color: #0f2342;
    cursor: pointer;
    transition: border-color .15s;
}
.sp-select:focus { border-color: #0d9488; outline: none; box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
.sp-trait-badge {
    display: inline-block;
    margin-top: 5px;
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 20px;
    background: #f1f5f9;
    color: #64748b;
    transition: all .2s;
}
.sp-badge-excellent  { background: #dcfce7; color: #15803d; }
.sp-badge-verygood   { background: #dbeafe; color: #1e40af; }
.sp-badge-good       { background: #ede9fe; color: #6d28d9; }
.sp-badge-fairlygood { background: #fef9c3; color: #854d0e; }
.sp-badge-poor       { background: #fee2e2; color: #991b1b; }
.sp-field-group { display: flex; flex-direction: column; gap: 5px; }
.sp-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .4px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.sp-input, .sp-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    background: #fff;
    color: #0f2342;
    transition: border-color .15s;
    resize: vertical;
}
.sp-input:focus, .sp-textarea:focus { border-color: #0d9488; outline: none; box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
.sp-textarea-readonly { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
.sp-report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,35,66,.06);
}
.sp-report-table thead th {
    background: #0f2342;
    color: #fff;
    padding: 10px 14px;
    font-weight: 600;
    font-size: 11px;
    white-space: nowrap;
    text-align: center;
    border-right: 1px solid rgba(255,255,255,.08);
}
.sp-report-table thead th:nth-child(2) { text-align: left; }
.sp-report-table tbody td {
    padding: 9px 14px;
    border-bottom: 1px solid #f1f5f9;
    text-align: center;
    vertical-align: middle;
    color: #334155;
}
.sp-report-table tbody td:nth-child(2) { text-align: left; font-weight: 600; color: #0f2342; }
.sp-report-table tbody tr:hover td { background: #f0fdf9; }
.sp-report-table tbody tr:last-child td { border-bottom: none; }
.sp-score-low  { color: #dc2626 !important; font-weight: 700; }
.sp-score-high { color: #16a34a !important; font-weight: 700; }
.sp-empty-report { text-align: center; padding: 50px; color: #94a3b8; }
.sp-empty-report i { font-size: 48px; display: block; margin-bottom: 12px; }
.sp-grade-key {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    margin-top: 14px;
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    font-size: 11.5px;
    color: #64748b;
}
.sp-grade-key span { font-weight: 600; }
#spDrawerBody::-webkit-scrollbar { width: 5px; }
#spDrawerBody::-webkit-scrollbar-track { background: transparent; }
#spDrawerBody::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 20px; }
@keyframes sp-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .7; transform: scale(.95); }
}

/* ── Attendance Card (inside drawer) ── */
.sp-att-card {
    background: #fff;
    border: 1px solid #d1fae5;
    border-radius: 14px;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(13,148,136,.07);
}
.sp-att-header {
    background: linear-gradient(to right, #ecfdf5, #d1fae5);
    border-bottom: 1px solid #a7f3d0;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #065f46;
}
.sp-att-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 0;
    border-top: 1px solid #d1fae5;
}
.sp-att-cell {
    padding: 14px 16px;
    text-align: center;
    border-right: 1px solid #d1fae5;
    border-bottom: 1px solid #d1fae5;
    background: #f0fdf9;
    transition: background .15s;
}
.sp-att-cell:hover { background: #dcfce7; }
.sp-att-cell:last-child { border-right: none; }
.sp-att-cell-label {
    font-size: 10.5px;
    font-weight: 600;
    color: #0f766e;
    text-transform: uppercase;
    letter-spacing: .4px;
    display: block;
    margin-bottom: 6px;
}
.sp-att-cell-value {
    font-size: 20px;
    font-weight: 800;
    color: #0f2342;
    display: block;
    line-height: 1;
}
.sp-att-cell-value.att-warn { color: #dc2626; }
.sp-att-cell-value.att-ok   { color: #16a34a; }
.sp-att-pct-wrap {
    padding: 12px 18px;
    border-top: 1px solid #d1fae5;
    background: #fff;
}
.sp-att-pct-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #0f766e;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.sp-att-pct-bar-bg {
    height: 10px;
    background: #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
}
.sp-att-pct-bar-fill {
    height: 100%;
    border-radius: 20px;
    background: linear-gradient(90deg, #0d9488, #22c55e);
    transition: width .6s ease;
}
.sp-att-pct-bar-fill.att-pct-warn {
    background: linear-gradient(90deg, #f59e0b, #dc2626);
}
.sp-att-no-record {
    padding: 24px;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
}
.sp-att-no-record i { display: block; font-size: 32px; margin-bottom: 8px; color: #d1fae5; }

@media (max-width: 600px) {
    #spDrawer { width: 100vw !important; }
    .sp-trait-grid { grid-template-columns: 1fr 1fr; }
    .sp-att-grid   { grid-template-columns: repeat(3, 1fr); }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

{{-- ── Hero ── --}}
<div class="cb-hero">
    <h1><i class="ri-group-line me-2"></i>My Class Students</h1>
    <p>View and manage students in your assigned class.</p>
    <div class="meta-pills">
        <span class="cb-meta-pill"><i class="ri-building-line"></i>{{ $schoolclass[0]->schoolclass ?? 'N/A' }} {{ $schoolclass[0]->arm ?? '' }}</span>
        <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $term[0]->term ?? 'N/A' }}</span>
        <span class="cb-meta-pill"><i class="ri-calendar-event-line"></i>{{ $session[0]->session ?? 'N/A' }}</span>
        <span class="cb-meta-pill"><i class="ri-user-line"></i>{{ Auth::user()->name }}</span>
    </div>
</div>

{{-- ── Stat Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-navy),var(--cb-teal));"></div>
            <div class="stat-ico"><i class="ri-group-line"></i></div>
            <div class="stat-value">{{ $allstudents->count() }}</div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-sky),#38bdf8);"></div>
            <div class="stat-ico"><i class="ri-user-line"></i></div>
            <div class="stat-value text-info">{{ $male ?? 0 }}</div>
            <div class="stat-label">Male Students</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-rose),#f43f5e);"></div>
            <div class="stat-ico"><i class="ri-user-line"></i></div>
            <div class="stat-value text-danger">{{ $female ?? 0 }}</div>
            <div class="stat-label">Female Students</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="cb-stat">
            <div class="stat-accent" style="background:linear-gradient(90deg,var(--cb-green),#4ade80);"></div>
            <div class="stat-ico"><i class="ri-award-line"></i></div>
            <div class="stat-value text-success" id="avgAge">—</div>
            <div class="stat-label">Avg Age</div>
        </div>
    </div>
</div>

{{-- ── Gender Chart ── --}}
<div class="chart-card">
    <div class="chart-header">
        <h5><i class="ri-bar-chart-line" style="color:var(--cb-teal);"></i> Students by Gender Distribution</h5>
    </div>
    <div class="chart-body">
        <canvas id="studentsByGenderChart" height="80"></canvas>
        <div id="chartError" class="text-danger text-center d-none">Failed to load chart.</div>
    </div>
</div>

{{-- ── Filter Panel ── --}}
<div class="filter-panel">
    <h6><i class="ri-filter-line" style="color:var(--cb-teal)"></i> Filter Students</h6>
    <div class="filter-grid">
        <div class="filter-item">
            <label><i class="ri-search-line"></i> Search</label>
            <input type="text" id="searchInput" placeholder="Name or Admission No...">
        </div>
        <div class="filter-item">
            <label><i class="ri-user-line"></i> Gender</label>
            <select id="genderFilter">
                <option value="all">All Genders</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>
        <div class="filter-item">
            <label><i class="ri-id-card-line"></i> Admission No</label>
            <select id="admissionFilter">
                <option value="all">All Admission Numbers</option>
                @foreach ($allstudents as $student)
                    <option value="{{ $student->admissionno }}">{{ $student->admissionno }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-item">
            <button class="btn-filter" onclick="applyFilters()"><i class="ri-search-line"></i> Apply Filters</button>
        </div>
        <div class="filter-item">
            <button class="btn-reset" onclick="resetFilters()"><i class="ri-refresh-line"></i> Reset</button>
        </div>
    </div>
</div>

{{-- ── Student Table ── --}}
<div class="cb-card">
    <div class="cb-card-header">
        <h5>
            <i class="ri-table-alt-line" style="color:var(--cb-teal)"></i>
            Student List
            <span class="class-info-badge" id="studentCount">{{ $allstudents->count() }} Students</span>
        </h5>
        <a href="{{ route('myclass.index') }}" class="btn-back">
            <i class="ri-arrow-left-line"></i> Back to Classes
        </a>
    </div>

    <div style="overflow-x:auto;">
        <table class="cb-table" id="studentListTable">
            <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Gender</th>
                    <th style="width:130px;">Action</th>
                </tr>
            </thead>
            <tbody id="studentTableBody">
                @forelse ($allstudents as $key => $student)
                @php
                    $lastName    = $student->lastname  ?? '';
                    $firstName   = $student->firstname ?? '';
                    $otherNames  = $student->othername ?? '';
                    $formattedName = trim($lastName . ' ' . $firstName . ' ' . $otherNames);
                    $genderClass   = ($student->gender ?? '') == 'Male' ? 'gender-male' : 'gender-female';
                    $genderIcon    = ($student->gender ?? '') == 'Male' ? 'ri-men-line' : 'ri-women-line';
                @endphp
                <tr data-admission="{{ $student->admissionno }}"
                    data-gender="{{ $student->gender }}"
                    data-name="{{ strtolower($formattedName) }}">
                    <td data-label="#" style="font-weight:600;color:var(--cb-navy);">{{ $key + 1 }}</td>
                    <td data-label="Admission No" class="admission-cell">{{ $student->admissionno }}</td>
                    <td data-label="Student Name">
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $student->picture ? asset('storage/student_avatars/'.basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                 alt="{{ $formattedName }}"
                                 class="student-avatar"
                                 data-bs-toggle="modal"
                                 data-bs-target="#imageViewModal"
                                 data-image="{{ $student->picture ? asset('storage/student_avatars/'.basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                 data-name="{{ $formattedName }}"
                                 data-admission="{{ $student->admissionno }}"
                                 onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'">
                            <button type="button" class="student-name-btn"
                                onclick="openProfileDrawer({{ $student->stid }}, {{ $schoolclassid }}, {{ $sessionid }}, {{ $termid }}, '{{ $student->picture ? asset('storage/student_avatars/'.basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}')">
                                {{ $formattedName }}
                            </button>
                        </div>
                    </td>
                    <td data-label="Gender">
                        <span class="gender-badge {{ $genderClass }}">
                            <i class="{{ $genderIcon }}"></i> {{ $student->gender }}
                        </span>
                    </td>
                    <td data-label="Action">
                        <button type="button" class="btn-view-profile"
                            onclick="openProfileDrawer({{ $student->stid }}, {{ $schoolclassid }}, {{ $sessionid }}, {{ $termid }}, '{{ $student->picture ? asset('storage/student_avatars/'.basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}')">
                            <i class="ri-eye-line"></i> View Profile
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="ri-inbox-line"></i>
                            <h6>No Students Found</h6>
                            <p>No students are currently enrolled in this class.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div style="padding:20px 24px;border-top:1px solid var(--cb-border);background:#fafbfc;">
        <div class="row align-items-center">
            <div class="col-sm">
                <div class="text-muted" style="font-size:12px;">
                    <i class="ri-information-line me-1"></i>
                    Showing <span class="fw-semibold text-dark" id="showingCount">{{ $allstudents->count() }}</span> of
                    <span class="fw-semibold text-dark" id="totalCount">{{ $allstudents->count() }}</span> students
                </div>
            </div>
            <div class="col-sm-auto">
                <div class="pagination-wrap" id="paginationContainer">
                    <button class="page-item" id="prevPage" onclick="changePage(-1)" disabled>Prev</button>
                    <span id="pageInfo" class="page-item active" style="background:var(--cb-teal);color:white;">Page 1</span>
                    <button class="page-item" id="nextPage" onclick="changePage(1)">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

</div></div></div>

{{-- ── Avatar View Modal ── --}}
<div id="imageViewModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-image-line me-2"></i>Student Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="enlargedImage" src="" alt="Student Picture" class="img-fluid" />
                <div id="modalStudentName" class="mt-3 fw-semibold" style="color:var(--cb-navy);"></div>
                <div id="modalStudentAdmission" class="text-muted small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     PROFILE DRAWER MODAL
══════════════════════════════════════════════════════════ --}}

{{-- Overlay --}}
<div id="spDrawerOverlay" onclick="closeProfileDrawer()"
     style="display:none;position:fixed;inset:0;background:rgba(10,20,40,.55);
            backdrop-filter:blur(3px);z-index:1040;transition:opacity .3s;"></div>

{{-- Drawer Panel --}}
<div id="spDrawer" style="
    position:fixed;top:0;right:0;height:100vh;width:min(780px,100vw);
    background:#ffffff;z-index:1050;transform:translateX(100%);
    transition:transform .35s cubic-bezier(.4,0,.2,1);
    display:flex;flex-direction:column;overflow:hidden;
    box-shadow:-8px 0 40px rgba(10,20,40,.18);">

    {{-- Drawer Header --}}
    <div style="background:linear-gradient(135deg,#0f2342 0%,#1e4a7e 55%,#0d9488 100%);
                flex-shrink:0;position:relative;overflow:hidden;">
        {{-- decorative orb --}}
        <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;
            background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);
            border-radius:50%;pointer-events:none;"></div>

        {{-- Top bar --}}
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:18px 24px 0;position:relative;">
            <div style="display:flex;align-items:center;gap:12px;">
                {{-- Avatar: shows photo when available, initials as fallback --}}
                <div id="spAvatarCircle" style="width:56px;height:56px;border-radius:50%;
                    background:rgba(255,255,255,.18);border:2.5px solid rgba(255,255,255,.4);
                    display:flex;align-items:center;justify-content:center;font-size:20px;
                    font-weight:700;color:#fff;font-family:'Playfair Display',serif;
                    flex-shrink:0;overflow:hidden;position:relative;">
                    <img id="spAvatarImg"
                         src=""
                         alt="Student Photo"
                         style="display:none;width:100%;height:100%;object-fit:cover;border-radius:50%;"
                         onerror="this.style.display='none';document.getElementById('spAvatarInitials').style.display='flex';">
                    <span id="spAvatarInitials" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">?</span>
                </div>
                <div>
                    <div style="font-size:10.5px;color:rgba(255,255,255,.6);font-weight:600;
                        text-transform:uppercase;letter-spacing:.7px;margin-bottom:2px;">
                        Student Profile
                    </div>
                    <div id="spDrawerTitle" style="font-size:17px;font-weight:700;color:#fff;
                        font-family:'Playfair Display',serif;">Loading…</div>
                </div>
            </div>
            <button onclick="closeProfileDrawer()" style="
                background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);
                border-radius:10px;padding:8px 16px;color:#fff;font-size:12px;
                font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;
                transition:background .2s;font-family:'DM Sans',sans-serif;"
                onmouseover="this.style.background='rgba(255,255,255,.22)'"
                onmouseout="this.style.background='rgba(255,255,255,.12)'">
                <i class="ri-close-line"></i> Close
            </button>
        </div>

        {{-- Info pills --}}
        <div id="spInfoPills" style="display:flex;gap:8px;flex-wrap:wrap;padding:10px 24px 14px;">
            <span class="sp-pill" id="spPillAdmission"><i class="ri-id-card-line"></i> —</span>
            <span class="sp-pill" id="spPillGender"><i class="ri-user-line"></i> —</span>
            <span class="sp-pill" id="spPillClass"><i class="ri-building-line"></i> —</span>
            <span class="sp-pill" id="spPillTerm"><i class="ri-calendar-line"></i> —</span>
            {{-- Attendance quick-pill: populated by JS --}}
            <span class="sp-pill" id="spPillAttendance" style="display:none;">
                <i class="ri-calendar-check-line"></i> <span id="spPillAttText">—%</span>
            </span>
        </div>

        {{-- Tabs --}}
        <div style="display:flex;gap:2px;padding:0 24px;position:relative;">
            <button class="sp-tab sp-tab-active" data-tab="profile" onclick="switchTab('profile',this)">
                <i class="ri-award-line"></i> Personality
            </button>
            <button class="sp-tab" data-tab="attendance" onclick="switchTab('attendance',this)">
                <i class="ri-calendar-check-line"></i> Attendance
            </button>
            <button class="sp-tab" data-tab="terminal" onclick="switchTab('terminal',this)">
                <i class="ri-file-chart-line"></i> Terminal Report
            </button>
            <button class="sp-tab" data-tab="mock" onclick="switchTab('mock',this)">
                <i class="ri-file-list-3-line"></i> Mock Report
            </button>
        </div>
    </div>

    {{-- Drawer Body --}}
    <div id="spDrawerBody" style="flex:1;overflow-y:auto;background:#f1f5f9;">

        {{-- Loading --}}
        <div id="spLoadingState" style="padding:60px;text-align:center;">
            <div style="width:64px;height:64px;border-radius:50%;
                background:linear-gradient(135deg,#0f2342,#0d9488);
                margin:0 auto 18px;display:flex;align-items:center;justify-content:center;
                animation:sp-pulse 1.5s ease-in-out infinite;">
                <i class="ri-user-star-line" style="font-size:28px;color:#fff;"></i>
            </div>
            <div style="font-size:15px;font-weight:700;color:#0f2342;">Loading student profile…</div>
            <div style="font-size:13px;color:#64748b;margin-top:4px;">Fetching data, please wait</div>
        </div>

        {{-- Error --}}
        <div id="spErrorState" style="display:none;padding:60px;text-align:center;">
            <i class="ri-error-warning-line" style="font-size:56px;color:#f43f5e;display:block;margin-bottom:14px;"></i>
            <div style="font-size:15px;font-weight:700;color:#0f2342;">Failed to load profile</div>
            <div id="spErrorMsg" style="font-size:13px;color:#64748b;margin-top:6px;"></div>
            <button onclick="retryLoad()" style="margin-top:20px;background:#0d9488;color:#fff;
                border:none;border-radius:10px;padding:10px 28px;font-weight:700;
                font-size:13px;cursor:pointer;font-family:'DM Sans',sans-serif;">
                <i class="ri-refresh-line"></i> Retry
            </button>
        </div>

        {{-- ── TAB: Personality ── --}}
        <div id="spTab-profile" class="sp-tab-content" style="display:none;padding:24px;">
            <form id="spProfileForm" onsubmit="submitProfileForm(event)">
                <input type="hidden" name="_token"       id="spCsrfToken"    value="{{ csrf_token() }}">
                <input type="hidden" name="studentid"    id="spStudentId">
                <input type="hidden" name="schoolclassid" id="spSchoolClassId">
                <input type="hidden" name="staffid"      id="spStaffId"      value="{{ Auth::user()->id }}">
                <input type="hidden" name="termid"       id="spTermId">
                <input type="hidden" name="sessionid"    id="spSessionId">

                {{-- Behavioral Traits --}}
                <div class="sp-section-card">
                    <div class="sp-section-header">
                        <i class="ri-heart-pulse-line" style="color:#0d9488;font-size:16px;"></i>
                        Behavioral Traits
                    </div>
                    <div class="sp-trait-grid">
                        @php
                        $behavioralTraits = [
                            ['punctuality',   'Punctuality',    'ri-time-line'],
                            ['neatness',      'Neatness',       'ri-brush-line'],
                            ['leadership',    'Leadership',     'ri-trophy-line'],
                            ['attitude',      'Attitude',       'ri-emotion-line'],
                            ['honesty',       'Honesty',        'ri-shield-check-line'],
                            ['cooperation',   'Cooperation',    'ri-team-line'],
                            ['selfcontrol',   'Self-control',   'ri-focus-3-line'],
                            ['politeness',    'Politeness',     'ri-hand-heart-line'],
                            ['physicalhealth','Physical Health','ri-heart-line'],
                            ['stability',     'Stability',      'ri-scales-line'],
                        ];
                        @endphp
                        @foreach ($behavioralTraits as [$name, $label, $icon])
                        <div class="sp-trait-item">
                            <div class="sp-trait-label"><i class="{{ $icon }}"></i> {{ $label }}</div>
                            <select name="{{ $name }}" class="sp-select" id="sp_{{ $name }}" onchange="updateTraitBadge(this)">
                                <option value="">Select…</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Very Good">Very Good</option>
                                <option value="Good">Good</option>
                                <option value="Fairly Good">Fairly Good</option>
                                <option value="Poor">Poor</option>
                            </select>
                            <span class="sp-trait-badge" id="badge_{{ $name }}">—</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Academic & Skills --}}
                <div class="sp-section-card">
                    <div class="sp-section-header">
                        <i class="ri-book-open-line" style="color:#0ea5e9;font-size:16px;"></i>
                        Academic & Skills
                    </div>
                    <div class="sp-trait-grid">
                        @php
                        $academicTraits = [
                            ['reading',                    'Reading',             'ri-book-line'],
                            ['attentiveness_in_class',     'Attentiveness',       'ri-eye-line'],
                            ['class_participation',        'Class Participation', 'ri-discuss-line'],
                            ['relationship_with_others',   'Relationship',        'ri-group-line'],
                            ['doing_assignment',           'Doing Assignment',    'ri-file-list-line'],
                            ['writing_skill',              'Writing Skill',       'ri-pen-nib-line'],
                            ['reading_skill',              'Reading Skill',       'ri-book-read-line'],
                            ['spoken_english_communication','Spoken English',     'ri-speak-line'],
                            ['hand_writing',               'Hand Writing',        'ri-edit-line'],
                        ];
                        @endphp
                        @foreach ($academicTraits as [$name, $label, $icon])
                        <div class="sp-trait-item">
                            <div class="sp-trait-label"><i class="{{ $icon }}"></i> {{ $label }}</div>
                            <select name="{{ $name }}" class="sp-select" id="sp_{{ $name }}" onchange="updateTraitBadge(this)">
                                <option value="">Select…</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Very Good">Very Good</option>
                                <option value="Good">Good</option>
                                <option value="Fairly Good">Fairly Good</option>
                                <option value="Poor">Poor</option>
                            </select>
                            <span class="sp-trait-badge" id="badge_{{ $name }}">—</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Extra-curricular --}}
                <div class="sp-section-card">
                    <div class="sp-section-header">
                        <i class="ri-football-line" style="color:#f59e0b;font-size:16px;"></i>
                        Extra-curricular
                    </div>
                    <div class="sp-trait-grid">
                        @php
                        $extraTraits = [
                            ['gamesandsports','Games & Sports','ri-football-line'],
                            ['club',          'Club',          'ri-group-2-line'],
                            ['music',         'Music',         'ri-music-line'],
                        ];
                        @endphp
                        @foreach ($extraTraits as [$name, $label, $icon])
                        <div class="sp-trait-item">
                            <div class="sp-trait-label"><i class="{{ $icon }}"></i> {{ $label }}</div>
                            <select name="{{ $name }}" class="sp-select" id="sp_{{ $name }}" onchange="updateTraitBadge(this)">
                                <option value="">Select…</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Very Good">Very Good</option>
                                <option value="Good">Good</option>
                                <option value="Fairly Good">Fairly Good</option>
                                <option value="Poor">Poor</option>
                            </select>
                            <span class="sp-trait-badge" id="badge_{{ $name }}">—</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Comments & Attendance (existing field) --}}
                <div class="sp-section-card">
                    <div class="sp-section-header">
                        <i class="ri-chat-3-line" style="color:#8b5cf6;font-size:16px;"></i>
                        Comments & Attendance
                    </div>
                    <div style="padding:16px;display:grid;gap:14px;">
                        <div class="sp-field-group">
                            <label class="sp-label"><i class="ri-calendar-check-line"></i> School Attendance (days present)</label>
                            <input type="number" name="attendance" id="sp_attendance" min="0" max="365"
                                class="sp-input" placeholder="e.g. 90">
                        </div>
                        <div class="sp-field-group">
                            <label class="sp-label"><i class="ri-user-voice-line"></i> Teacher's Comment</label>
                            <textarea name="classteachercomment" id="sp_classteachercomment"
                                class="sp-textarea" rows="3" placeholder="Enter class teacher's remark…"></textarea>
                        </div>
                        <div class="sp-field-group">
                            <label class="sp-label"><i class="ri-government-line"></i> Principal's Comment <span style="font-size:10px;font-weight:400;color:#94a3b8;">(read-only)</span></label>
                            <textarea name="principalscomment" id="sp_principalscomment"
                                class="sp-textarea sp-textarea-readonly" rows="2"
                                placeholder="Principal's remark" readonly></textarea>
                        </div>
                        <div class="sp-field-group">
                            <label class="sp-label"><i class="ri-sticky-note-line"></i> Remark on Other Activities</label>
                            <textarea name="remark_on_other_activities" id="sp_remark_on_other_activities"
                                class="sp-textarea" rows="2" placeholder="Other activities remark…"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Sticky submit bar --}}
                <div style="position:sticky;bottom:0;padding:14px 0 2px;
                    background:linear-gradient(to top,#f1f5f9 75%,transparent);
                    display:flex;gap:12px;align-items:center;justify-content:flex-end;">
                    <div id="spSaveStatus" style="font-size:12px;color:#059669;display:none;
                        align-items:center;gap:6px;font-weight:600;">
                        <i class="ri-checkbox-circle-fill"></i>
                        <span id="spSaveStatusText">Profile saved!</span>
                    </div>
                    <button type="submit" id="spSubmitBtn" style="
                        background:linear-gradient(135deg,#0d9488,#0ea5e9);color:#fff;
                        border:none;padding:12px 30px;border-radius:12px;font-weight:700;
                        font-size:14px;cursor:pointer;display:flex;align-items:center;gap:8px;
                        transition:all .2s;box-shadow:0 4px 14px rgba(13,148,136,.35);
                        font-family:'DM Sans',sans-serif;">
                        <i class="ri-save-line"></i>
                        <span id="spSubmitLabel">Update Profile</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ── TAB: Attendance ── --}}
        <div id="spTab-attendance" class="sp-tab-content" style="display:none;padding:24px;">
            <div id="spAttendancePanel"></div>
        </div>

        {{-- ── TAB: Terminal Report ── --}}
        <div id="spTab-terminal" class="sp-tab-content" style="display:none;padding:24px;">
            <div id="spTerminalReport"></div>
        </div>

        {{-- ── TAB: Mock Report ── --}}
        <div id="spTab-mock" class="sp-tab-content" style="display:none;padding:24px;">
            <div id="spMockReport"></div>
        </div>

    </div>{{-- /spDrawerBody --}}
</div>{{-- /spDrawer --}}

{{-- ══════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ─────────────────────────────────────────────────────
// TABLE FILTERING & PAGINATION
// ─────────────────────────────────────────────────────
let allRows      = [];
let filteredRows = [];
let currentPage  = 1;
const rowsPerPage = 10;

document.addEventListener("DOMContentLoaded", function () {
    const tableBody = document.getElementById('studentTableBody');
    if (tableBody) {
        tableBody.querySelectorAll('tr').forEach(row => {
            if (row.querySelector('.empty-state')) return;
            const admissionCell = row.querySelector('.admission-cell');
            const namebtn       = row.querySelector('.student-name-btn');
            const genderBadge   = row.querySelector('.gender-badge');
            allRows.push({
                element:   row,
                admission: admissionCell ? admissionCell.textContent.trim() : '',
                name:      namebtn       ? namebtn.textContent.trim().toLowerCase() : '',
                gender:    genderBadge   ? genderBadge.textContent.trim().replace(/[^a-zA-Z]/g,'') : ''
            });
        });
    }
    filteredRows = [...allRows];
    updateDisplay();
    calculateAverageAge();
    initChart();

    document.getElementById('searchInput')?.addEventListener('keypress', e => {
        if (e.key === 'Enter') applyFilters();
    });
});

function initChart() {
    const ctx = document.getElementById("studentsByGenderChart")?.getContext("2d");
    const maleCount   = {{ $male ?? 0 }};
    const femaleCount = {{ $female ?? 0 }};
    if (!ctx) return;
    try {
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["Male", "Female"],
                datasets: [{
                    label: "Number of Students",
                    data: [maleCount, femaleCount],
                    backgroundColor: ["#0ea5e9", "#f43f5e"],
                    borderRadius: 8,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: { beginAtZero: true, grid: { color: "#e2e8f0" } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    } catch(e) {
        document.getElementById("chartError")?.classList.remove("d-none");
    }
}

window.applyFilters = function() {
    const searchTerm      = document.getElementById('searchInput')?.value.toLowerCase().trim() || '';
    const genderFilter    = document.getElementById('genderFilter')?.value || 'all';
    const admissionFilter = document.getElementById('admissionFilter')?.value || 'all';

    filteredRows = allRows.filter(row => {
        const matchSearch    = !searchTerm || row.name.includes(searchTerm) || row.admission.toLowerCase().includes(searchTerm);
        const matchGender    = genderFilter === 'all'    || row.gender === genderFilter;
        const matchAdmission = admissionFilter === 'all' || row.admission === admissionFilter;
        return matchSearch && matchGender && matchAdmission;
    });
    currentPage = 1;
    updateDisplay();
    showToast(`Found ${filteredRows.length} student(s)`, 'info');
};

window.resetFilters = function() {
    document.getElementById('searchInput').value     = '';
    document.getElementById('genderFilter').value    = 'all';
    document.getElementById('admissionFilter').value = 'all';
    filteredRows = [...allRows];
    currentPage  = 1;
    updateDisplay();
    showToast('Filters reset', 'info');
};

function updateDisplay() {
    const tableBody = document.getElementById('studentTableBody');
    if (!tableBody) return;
    const startIndex = (currentPage - 1) * rowsPerPage;
    const pageRows   = filteredRows.slice(startIndex, startIndex + rowsPerPage);

    tableBody.innerHTML = '';
    if (!pageRows.length) {
        tableBody.innerHTML = `<tr><td colspan="5">
            <div class="empty-state">
                <i class="ri-inbox-line"></i>
                <h6>No Students Found</h6>
                <p>No students match your filter criteria.</p>
            </div></td></tr>`;
    } else {
        pageRows.forEach((row, idx) => {
            const cloned = row.element.cloneNode(true);
            const snCell = cloned.querySelector('td:first-child');
            if (snCell) snCell.textContent = startIndex + idx + 1;
            tableBody.appendChild(cloned);
        });
    }

    const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
    const el = id => document.getElementById(id);
    el('showingCount') && (el('showingCount').textContent = pageRows.length);
    el('totalCount')   && (el('totalCount').textContent   = filteredRows.length);
    el('pageInfo')     && (el('pageInfo').textContent     = `Page ${currentPage} of ${totalPages}`);
    el('studentCount') && (el('studentCount').textContent = filteredRows.length + ' Students');
    if (el('prevPage')) el('prevPage').disabled = currentPage === 1;
    if (el('nextPage')) el('nextPage').disabled = currentPage >= totalPages;
}

window.changePage = function(dir) {
    const total = Math.ceil(filteredRows.length / rowsPerPage);
    const next  = currentPage + dir;
    if (next >= 1 && next <= total) { currentPage = next; updateDisplay(); }
};

function calculateAverageAge() {
    let total = 0, count = 0;
    @foreach ($allstudents as $student)
        @if($student->dateofbirth ?? false)
        total += new Date().getFullYear() - new Date('{{ $student->dateofbirth }}').getFullYear();
        count++;
        @endif
    @endforeach
    document.getElementById('avgAge').textContent = count ? Math.round(total / count) : '—';
}

// Image modal
const imageViewModal = document.getElementById('imageViewModal');
if (imageViewModal) {
    imageViewModal.addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        document.getElementById('enlargedImage').src           = btn.getAttribute('data-image') || '';
        document.getElementById('modalStudentName').textContent = btn.getAttribute('data-name')      || '';
        document.getElementById('modalStudentAdmission').textContent = btn.getAttribute('data-admission')
            ? 'Admission No: ' + btn.getAttribute('data-admission') : '';
    });
}

function showToast(message, type) {
    const t = document.createElement('div');
    t.className = 'cb-toast cb-toast-' + (type || 'success');
    t.innerHTML = `<i class="ri-information-fill"></i> ${message}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// ─────────────────────────────────────────────────────
// PROFILE DRAWER
// ─────────────────────────────────────────────────────
(function() {
    let _stid, _classid, _sessid, _termid, _activeTab = 'profile', _preloadedPicture = null;

    window.openProfileDrawer = function(stid, classid, sessid, termid, pictureUrl) {
        _stid = stid; _classid = classid; _sessid = sessid; _termid = termid;
        _preloadedPicture = pictureUrl || null;

        // Show photo immediately from the table row — no AJAX wait
        _setDrawerAvatar(null, _preloadedPicture);

        const overlay = document.getElementById('spDrawerOverlay');
        const drawer  = document.getElementById('spDrawer');
        overlay.style.display = 'block';
        overlay.style.opacity = '0';
        drawer.style.transform = 'translateX(100%)';

        requestAnimationFrame(() => {
            overlay.style.opacity  = '1';
            drawer.style.transform = 'translateX(0)';
        });
        document.body.style.overflow = 'hidden';

        showLoading();
        switchTab('profile', document.querySelector('.sp-tab[data-tab="profile"]'));
        fetchProfile(stid, classid, sessid, termid);
    };

    window.closeProfileDrawer = function() {
        document.getElementById('spDrawerOverlay').style.opacity  = '0';
        document.getElementById('spDrawer').style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.getElementById('spDrawerOverlay').style.display = 'none';
            document.body.style.overflow = '';
        }, 350);
    };

    window.retryLoad = function() {
        if (_stid) { showLoading(); fetchProfile(_stid, _classid, _sessid, _termid); }
    };

    window.switchTab = function(tab, btn) {
        _activeTab = tab;
        document.querySelectorAll('.sp-tab').forEach(t => t.classList.remove('sp-tab-active'));
        document.querySelectorAll('.sp-tab-content').forEach(c => c.style.display = 'none');
        if (btn) btn.classList.add('sp-tab-active');
        const panel = document.getElementById('spTab-' + tab);
        if (panel) panel.style.display = 'block';
    };

    window.updateTraitBadge = function(sel) {
        const badge = document.getElementById('badge_' + sel.name);
        if (!badge) return;
        const map = {
            'Excellent':   ['Excellent', 'sp-badge-excellent'],
            'Very Good':   ['Very Good', 'sp-badge-verygood'],
            'Good':        ['Good',      'sp-badge-good'],
            'Fairly Good': ['Fair',      'sp-badge-fairlygood'],
            'Poor':        ['Poor',      'sp-badge-poor'],
        };
        badge.className = 'sp-trait-badge';
        if (map[sel.value]) {
            badge.textContent = map[sel.value][0];
            badge.classList.add(map[sel.value][1]);
        } else {
            badge.textContent = '—';
        }
    };

    // ── Avatar helper ─────────────────────────────────────────────────
    function _setDrawerAvatar(name, pictureUrl) {
        const img      = document.getElementById('spAvatarImg');
        const initials = document.getElementById('spAvatarInitials');
        if (!img || !initials) return;

        if (pictureUrl) {
            img.src = pictureUrl;
            img.style.display      = 'block';
            initials.style.display = 'none';
        } else {
            img.style.display      = 'none';
            initials.style.display = 'flex';
            initials.textContent   = name
                ? name.split(' ').map(w => w[0]).filter(Boolean).slice(0,2).join('').toUpperCase()
                : '?';
        }
    }

    function fetchProfile(stid, classid, sessid, termid) {
        // Use the dedicated drawer endpoint that returns dynamic assessments
        const url = `{{ url('/studentreport/drawer-data') }}/${stid}/${classid}/${sessid}/${termid}`;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => { if (!r.ok) throw new Error('Server error ' + r.status); return r.json(); })
            .then(data => populateDrawer(data))
            .catch(err  => showError(err.message || 'Network error'));
    }

    function populateDrawer(data) {
        hideLoading();

        // Avatar: use URL from API response if present, otherwise keep the photo
        // that was already shown from the table row — never revert to initials if a
        // picture was pre-loaded successfully.
        const picUrl = data.picture_url || data.picture || _preloadedPicture || null;
        _setDrawerAvatar(data.student_name, picUrl);

        document.getElementById('spDrawerTitle').textContent     = data.student_name || '—';
        document.getElementById('spPillAdmission').innerHTML     = `<i class="ri-id-card-line"></i> ${data.admissionno || '—'}`;
        document.getElementById('spPillGender').innerHTML        = `<i class="ri-user-line"></i> ${data.gender || '—'}`;
        document.getElementById('spPillClass').innerHTML         = `<i class="ri-building-line"></i> ${data.schoolclass || '—'}`;
        document.getElementById('spPillTerm').innerHTML          = `<i class="ri-calendar-line"></i> ${data.term || '—'} · ${data.session || '—'}`;

        document.getElementById('spStudentId').value    = data.studentid;
        document.getElementById('spSchoolClassId').value = data.schoolclassid;
        document.getElementById('spTermId').value       = data.termid;
        document.getElementById('spSessionId').value    = data.sessionid;

        const pp = data.profile || {};
        const traitFields = [
            'punctuality','neatness','leadership','attitude','reading','honesty',
            'cooperation','selfcontrol','politeness','physicalhealth','stability',
            'gamesandsports','attentiveness_in_class','class_participation',
            'relationship_with_others','doing_assignment','writing_skill',
            'reading_skill','spoken_english_communication','hand_writing','club','music'
        ];
        traitFields.forEach(f => {
            const el = document.getElementById('sp_' + f);
            if (el) { el.value = pp[f] || ''; updateTraitBadge(el); }
        });
        ['attendance','classteachercomment','principalscomment','remark_on_other_activities'].forEach(f => {
            const el = document.getElementById('sp_' + f);
            if (el) el.value = pp[f] || '';
        });

        // ── Build Attendance Tab ──────────────────────────────────────
        buildAttendancePanel(data.attendance || data.attendance_summary || {});

        buildTerminalReport(data.scores || [], data.assessments || []);
        buildMockReport(data.mock_scores || []);
    }

    // ── ATTENDANCE PANEL ──────────────────────────────────────────────
    function buildAttendancePanel(att) {
        const container = document.getElementById('spAttendancePanel');
        const found     = att.found === true || att.found === 1;
        const pct       = found ? parseFloat(att.attendance_percentage || 0).toFixed(1) : 0;
        const warn      = parseFloat(pct) < 75;

        // Update header pill
        const pill = document.getElementById('spPillAttendance');
        const pillTxt = document.getElementById('spPillAttText');
        if (found) {
            pill.style.display = 'inline-flex';
            pillTxt.textContent = `${pct}% Present`;
            pill.style.background = warn ? 'rgba(220,38,38,.35)' : 'rgba(22,163,74,.35)';
        } else {
            pill.style.display = 'none';
        }

        if (!found) {
            container.innerHTML = `
                <div class="sp-att-card">
                    <div class="sp-att-header">
                        <i class="ri-calendar-check-line" style="color:#0d9488;font-size:18px;"></i>
                        Attendance Summary
                    </div>
                    <div class="sp-att-no-record">
                        <i class="ri-calendar-close-line"></i>
                        No attendance record found for this student in the selected term and session.
                    </div>
                </div>`;
            return;
        }

        const barWidth = Math.min(parseFloat(pct), 100);
        const warnClass = warn ? 'att-pct-warn' : '';
        const valueCls  = (v) => v > 0 ? 'att-warn' : 'att-ok';

        container.innerHTML = `
            <div class="sp-att-card">
                <div class="sp-att-header">
                    <i class="ri-calendar-check-line" style="color:#0d9488;font-size:18px;"></i>
                    Attendance Summary — ${att.term_name || 'This Term'}
                    <span style="margin-left:auto;font-size:11px;font-weight:500;color:#065f46;">
                        ${warn ? '⚠ Below 75% threshold' : '✓ Satisfactory'}
                    </span>
                </div>

                <div class="sp-att-grid">
                    <div class="sp-att-cell">
                        <span class="sp-att-cell-label">School Days</span>
                        <span class="sp-att-cell-value">${att.total_school_days || 0}</span>
                    </div>
                    <div class="sp-att-cell">
                        <span class="sp-att-cell-label">Present</span>
                        <span class="sp-att-cell-value att-ok">${att.days_present || 0}</span>
                    </div>
                    <div class="sp-att-cell">
                        <span class="sp-att-cell-label">Absent</span>
                        <span class="sp-att-cell-value ${valueCls(att.days_absent || 0)}">${att.days_absent || 0}</span>
                    </div>
                    <div class="sp-att-cell">
                        <span class="sp-att-cell-label">Late</span>
                        <span class="sp-att-cell-value ${valueCls(att.days_late || 0)}">${att.days_late || 0}</span>
                    </div>
                    <div class="sp-att-cell">
                        <span class="sp-att-cell-label">Sick Leave</span>
                        <span class="sp-att-cell-value">${att.days_sick_leave || 0}</span>
                    </div>
                    <div class="sp-att-cell">
                        <span class="sp-att-cell-label">Excused</span>
                        <span class="sp-att-cell-value">${att.days_excused || 0}</span>
                    </div>
                </div>

                <div class="sp-att-pct-wrap">
                    <div class="sp-att-pct-label">
                        <span><i class="ri-percent-line"></i> Attendance Rate</span>
                        <span style="font-size:18px;font-weight:800;color:${warn ? '#dc2626' : '#16a34a'};">${pct}%</span>
                    </div>
                    <div class="sp-att-pct-bar-bg">
                        <div class="sp-att-pct-bar-fill ${warnClass}" style="width:${barWidth}%;"></div>
                    </div>
                    <div style="font-size:11px;color:#64748b;margin-top:6px;display:flex;justify-content:space-between;">
                        <span>${att.days_present || 0} days present out of ${att.total_school_days || 0} school days</span>
                        <span>${warn ? 'Below required 75%' : 'Above required 75%'}</span>
                    </div>
                </div>
            </div>

            <div class="sp-section-card" style="border-color:#d1fae5;">
                <div class="sp-section-header" style="background:linear-gradient(to right,#ecfdf5,#d1fae5);">
                    <i class="ri-information-line" style="color:#0d9488;"></i> Attendance Guide
                </div>
                <div style="padding:14px 18px;font-size:12.5px;color:#374151;line-height:1.7;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div><strong style="color:#0d9488;">Present:</strong> Days physically in school</div>
                        <div><strong style="color:#dc2626;">Absent:</strong> Unaccounted absence</div>
                        <div><strong style="color:#f59e0b;">Late:</strong> Arrived after opening time</div>
                        <div><strong style="color:#6d28d9;">Sick Leave:</strong> Medical absence (authorised)</div>
                        <div><strong style="color:#2563eb;">Excused:</strong> Pre-approved absence</div>
                        <div><strong style="color:#0f2342;">Threshold:</strong> Minimum 75% required</div>
                    </div>
                </div>
            </div>`;
    }

    // ── TERMINAL REPORT — dynamic assessments ────────────────────────
    function buildTerminalReport(scores, assessments) {
        const c = document.getElementById('spTerminalReport');
        if (!scores || !scores.length) {
            c.innerHTML = `<div class="sp-empty-report"><i class="ri-file-damage-line"></i>No terminal report scores available.</div>`;
            return;
        }

        // assessments = [{id, name, max_score}, ...]  — ordered by id (same as controller)
        const asses = assessments || [];

        // Build header cells
        const assessmentHeaders = asses.map(a =>
            `<th>${escHtml(a.name)}<br><span style="font-size:9px;opacity:.7;">(${a.max_score})</span></th>`
        ).join('');

        // Build data rows
        const rows = scores.map((s, i) => {
            // Map assessment_scores array to a lookup by assessment_id
            const scoreMap = {};
            (s.assessment_scores || []).forEach(as => {
                scoreMap[as.assessment_id] = as.score;
            });

            // Assessment score cells
            const assessmentCells = asses.map(a => {
                const v = scoreMap[a.assessment_id] ?? scoreMap[a.id] ?? null;
                const low = v !== null && v < (a.max_score * 0.5);
                const display = v !== null ? v : '—';
                return `<td class="${low ? 'sp-score-low' : ''}">${display}</td>`;
            }).join('');

            // Compute total of all assessment scores for "CA Avg" equivalent
            const assessVals = asses.map(a => {
                const v = scoreMap[a.assessment_id] ?? scoreMap[a.id];
                return (v !== null && v !== undefined) ? parseFloat(v) : null;
            }).filter(v => v !== null);

            const assessAvg = assessVals.length
                ? (assessVals.reduce((a, b) => a + b, 0) / assessVals.length).toFixed(1)
                : '—';

            return `<tr>
                <td>${i + 1}</td>
                <td style="text-align:left;font-weight:600;">${escHtml(s.subject_name)}</td>
                ${assessmentCells}
                <td class="${sc(assessAvg)}">${assessAvg}</td>
                <td class="${sc(s.total)}">${s.total ?? '—'}</td>
                <td>${s.bf ?? '—'}</td>
                <td>${s.cum ?? '—'}</td>
                <td>${gradeClass(s.grade)}</td>
                <td>${s.position ?? '—'}</td>
                <td>${s.arm_position ?? '—'}</td>
                <td class="${sc(s.class_average)}">${s.class_average ?? '—'}</td>
            </tr>`;
        }).join('');

        c.innerHTML = `
            <div style="overflow-x:auto;border-radius:12px;box-shadow:0 2px 8px rgba(15,35,66,.06);">
            <table class="sp-report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th style="text-align:left;">Subject</th>
                        ${assessmentHeaders}
                        <th>Avg</th>
                        <th>Total</th>
                        <th>B/F</th>
                        <th>Cum</th>
                        <th>Grade</th>
                        <th>Cls Pos</th>
                        <th>Arm Pos</th>
                        <th>Cls Avg</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table></div>
            <div class="sp-grade-key">
                <span style="color:#15803d;">A1 ≥ 75</span>
                <span style="color:#1e40af;">B2 ≥ 70</span>
                <span style="color:#6d28d9;">B3 ≥ 65</span>
                <span style="color:#854d0e;">C4 ≥ 60</span>
                <span style="color:#ea580c;">D7 ≥ 45</span>
                <span style="color:#dc2626;">F9 &lt; 40</span>
            </div>`;
    }

    function buildMockReport(scores) {
        const c = document.getElementById('spMockReport');
        if (!scores || !scores.length) {
            c.innerHTML = `<div class="sp-empty-report"><i class="ri-file-damage-line"></i>No mock report scores available.</div>`;
            return;
        }

        const rows = scores.map((s, i) => `
            <tr>
                <td>${i + 1}</td>
                <td style="text-align:left;font-weight:600;">${escHtml(s.subject_name)}</td>
                <td class="${sc(s.exam)}">${s.exam ?? '—'}</td>
                <td class="${sc(s.total)}">${s.total ?? '—'}</td>
                <td>${gradeClass(s.grade)}</td>
                <td style="font-size:11px;color:#64748b;">${escHtml(s.remark ?? '—')}</td>
                <td>${escHtml(s.position ?? '—')}</td>
                <td class="${sc(s.class_average)}">${s.class_average ?? '—'}</td>
                <td style="color:#6d28d9;font-weight:700;">${s.cmin ?? '—'}</td>
                <td style="color:#0369a1;font-weight:700;">${s.cmax ?? '—'}</td>
            </tr>`).join('');

        c.innerHTML = `
            <div style="overflow-x:auto;border-radius:12px;box-shadow:0 2px 8px rgba(15,35,66,.06);">
            <table class="sp-report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th style="text-align:left;">Subject</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Remark</th>
                        <th>Position</th>
                        <th>Cls Avg</th>
                        <th>Min</th>
                        <th>Max</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table></div>
            <div class="sp-grade-key">
                <span style="color:#15803d;">A1 ≥ 75</span>
                <span style="color:#1e40af;">B2 ≥ 70</span>
                <span style="color:#6d28d9;">B3 ≥ 65</span>
                <span style="color:#854d0e;">C4 ≥ 60</span>
                <span style="color:#ea580c;">D7 ≥ 45</span>
                <span style="color:#dc2626;">F9 &lt; 40</span>
            </div>`;
    }

    // ── Shared helpers ────────────────────────────────────────────────
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function gradeClass(grade) {
        if (!grade || grade === '-') return '—';
        const g = grade.toUpperCase();
        let cls = '';
        if (g.startsWith('A'))      cls = 'sp-score-high';
        else if (g.startsWith('B')) cls = 'color:#2563eb;font-weight:700;';
        else if (g === 'F9')        cls = 'sp-score-low';
        return cls
            ? `<span class="${cls.includes(':') ? '' : cls}" style="${cls.includes(':') ? cls : ''}">${escHtml(grade)}</span>`
            : escHtml(grade);
    }

    function sc(v) {
        if (v == null || isNaN(v)) return '';
        return +v < 50 ? 'sp-score-low' : (+v >= 70 ? 'sp-score-high' : '');
    }

    window.submitProfileForm = function(e) {
        e.preventDefault();
        const btn   = document.getElementById('spSubmitBtn');
        const label = document.getElementById('spSubmitLabel');
        btn.disabled      = true;
        label.textContent = 'Saving…';

        const fd = new FormData(document.getElementById('spProfileForm'));

        fetch('{{ route("studentpersonalityprofile.save") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':     document.getElementById('spCsrfToken').value,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
            },
            body: fd,
        })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(({ ok, data }) => {
            btn.disabled = false; label.textContent = 'Update Profile';
            if (ok || data.success !== false) {
                const s = document.getElementById('spSaveStatus');
                s.style.display = 'flex';
                setTimeout(() => s.style.display = 'none', 3500);
                drawerToast('Profile updated successfully!', 'success');
            } else {
                drawerToast(data.message || 'Save failed', 'error');
            }
        })
        .catch(() => {
            btn.disabled = false; label.textContent = 'Update Profile';
            drawerToast('Network error — please try again', 'error');
        });
    };

    function showLoading() {
        document.getElementById('spLoadingState').style.display = 'block';
        document.getElementById('spErrorState').style.display   = 'none';
        document.querySelectorAll('.sp-tab-content').forEach(c => c.style.display = 'none');
    }
    function hideLoading() {
        document.getElementById('spLoadingState').style.display = 'none';
        document.getElementById('spErrorState').style.display   = 'none';
        switchTab(_activeTab, document.querySelector(`.sp-tab[data-tab="${_activeTab}"]`));
    }
    function showError(msg) {
        document.getElementById('spLoadingState').style.display = 'none';
        document.getElementById('spErrorState').style.display   = 'block';
        document.getElementById('spErrorMsg').textContent = msg;
    }
    function drawerToast(msg, type) {
        const t = document.createElement('div');
        t.style.cssText = `position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
            background:${type==='success'?'#059669':'#dc2626'};color:#fff;
            padding:12px 22px;border-radius:12px;font-size:13px;font-weight:600;
            z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.2);
            display:flex;align-items:center;gap:8px;font-family:'DM Sans',sans-serif;
            animation:slideIn .3s ease;`;
        t.innerHTML = `<i class="ri-${type==='success'?'checkbox-circle-fill':'error-warning-fill'}"></i> ${msg}`;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeProfileDrawer(); });
})();
</script>
@endsection
