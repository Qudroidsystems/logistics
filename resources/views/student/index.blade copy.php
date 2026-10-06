@extends('layouts.master')
@section('content')
<?php use Spatie\Permission\Models\Role; ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Student Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">Students</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* ====================================================
                   CSS VARIABLES & BASE
                   ==================================================== */
                :root {
                    --sm-primary:   #1e3a5f;
                    --sm-accent:    #2563eb;
                    --sm-success:   #16a34a;
                    --sm-warning:   #d97706;
                    --sm-danger:    #dc2626;
                    --sm-purple:    #7c3aed;
                    --sm-pink:      #db2777;
                    --sm-teal:      #0d9488;
                    --sm-muted:     #6b7280;
                    --sm-border:    #e2e8f0;
                    --sm-bg:        #f8fafc;
                    --sm-radius:    14px;
                    --sm-shadow:    0 2px 12px rgba(0,0,0,.08);
                    --sm-shadow-lg: 0 8px 32px rgba(0,0,0,.12);
                }

                /* ====================================================
                   HERO BANNER
                   ==================================================== */
                .sm-hero {
                    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 55%, #4f46e5 100%);
                    border-radius: var(--sm-radius);
                    padding: 28px 32px;
                    margin-bottom: 24px;
                    position: relative;
                    overflow: hidden;
                }
                .sm-hero::before {
                    content: '';
                    position: absolute; top: -70px; right: -70px;
                    width: 240px; height: 240px;
                    background: rgba(255,255,255,.06);
                    border-radius: 50%;
                }
                .sm-hero::after {
                    content: '';
                    position: absolute; bottom: -40px; left: 180px;
                    width: 150px; height: 150px;
                    background: rgba(255,255,255,.04);
                    border-radius: 50%;
                }
                .sm-hero h1 {
                    font-size: 22px; font-weight: 700; color: #fff;
                    margin: 0 0 6px; position: relative; z-index: 1;
                }
                .sm-hero p {
                    font-size: 13px; color: rgba(255,255,255,.75);
                    margin: 0; position: relative; z-index: 1;
                }

                /* ====================================================
                   STAT CARDS
                   ==================================================== */
                .sm-stat {
                    background: #fff;
                    border: 1px solid var(--sm-border);
                    border-radius: var(--sm-radius);
                    padding: 20px 22px;
                    position: relative;
                    overflow: hidden;
                    transition: transform .2s, box-shadow .2s;
                    margin-bottom: 20px;
                }
                .sm-stat:hover { transform: translateY(-3px); box-shadow: var(--sm-shadow-lg); }
                .sm-stat::before {
                    content: '';
                    position: absolute; top: 0; left: 0; right: 0; height: 3px;
                    background: var(--sm-stat-color, var(--sm-accent));
                }
                .sm-stat-icon {
                    width: 52px; height: 52px; border-radius: 12px;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 22px; color: #fff; margin-bottom: 14px;
                    background: var(--sm-stat-color, var(--sm-accent));
                    box-shadow: 0 4px 12px rgba(0,0,0,.12);
                }
                .sm-stat-value { font-size: 30px; font-weight: 800; color: var(--sm-primary); line-height: 1; margin-bottom: 4px; }
                .sm-stat-label { font-size: 12px; font-weight: 600; color: var(--sm-muted); text-transform: uppercase; letter-spacing: .04em; }
                .sm-stat-sub   { font-size: 11px; color: var(--sm-muted); margin-top: 4px; }
                .sm-stat-sub .up   { color: var(--sm-success); }
                .sm-stat-sub .down { color: var(--sm-danger);  }
                /* color variants */
                .sm-stat.c-blue   { --sm-stat-color: #2563eb; }
                .sm-stat.c-green  { --sm-stat-color: #16a34a; }
                .sm-stat.c-amber  { --sm-stat-color: #d97706; }
                .sm-stat.c-purple { --sm-stat-color: #7c3aed; }
                .sm-stat.c-sky    { --sm-stat-color: #0284c7; }
                .sm-stat.c-pink   { --sm-stat-color: #db2777; }
                .sm-stat.c-teal   { --sm-stat-color: #0d9488; }
                .sm-stat.c-orange { --sm-stat-color: #ea580c; }

                /* ====================================================
                   MAIN PANEL
                   ==================================================== */
                .sm-panel {
                    background: #fff;
                    border: 1px solid var(--sm-border);
                    border-radius: var(--sm-radius);
                    overflow: hidden;
                    box-shadow: var(--sm-shadow);
                }
                .sm-panel-header {
                    padding: 16px 20px;
                    border-bottom: 1px solid var(--sm-border);
                    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
                }
                .sm-panel-title {
                    font-size: 15px; font-weight: 700; color: var(--sm-primary);
                    display: flex; align-items: center; gap: 8px;
                }

                /* ====================================================
                   FILTER BAR
                   ==================================================== */
                .sm-filter {
                    padding: 16px 20px;
                    border-bottom: 1px solid var(--sm-border);
                    background: #fafbfc;
                }
                .sm-search { position: relative; }
                .sm-search i.icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 15px; }
                .sm-search input {
                    padding-left: 38px; padding-right: 36px;
                    border-radius: 10px; border: 1.5px solid var(--sm-border);
                    height: 42px; font-size: 13px;
                    transition: border .2s, box-shadow .2s; width: 100%;
                }
                .sm-search input:focus {
                    border-color: var(--sm-accent);
                    box-shadow: 0 0 0 3px rgba(37,99,235,.1); outline: none;
                }
                .sm-search .clear-btn {
                    position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
                    background: transparent; border: none; color: #9ca3af;
                    font-size: 15px; padding: 4px 7px; cursor: pointer; display: none;
                }
                .sm-search .clear-btn:hover { color: var(--sm-danger); }
                .sm-filter select {
                    border-radius: 10px; border: 1.5px solid var(--sm-border);
                    height: 42px; font-size: 13px; padding: 0 12px; width: 100%;
                    transition: border .2s; background: #fff;
                }
                .sm-filter select:focus { border-color: var(--sm-accent); outline: none; }

                /* ====================================================
                   TABLE
                   ==================================================== */
                .sm-table thead th {
                    background: var(--sm-primary);
                    color: #fff; padding: 13px 14px;
                    font-weight: 600; font-size: 12px;
                    text-transform: uppercase; letter-spacing: .04em;
                    border: none; white-space: nowrap;
                }
                .sm-table tbody tr {
                    border-bottom: 1px solid var(--sm-border);
                    transition: background .15s;
                }
                .sm-table tbody tr:hover { background: #f0f7ff; }
                .sm-table tbody td { padding: 13px 14px; vertical-align: middle; font-size: 13px; }

                /* ====================================================
                   AVATAR
                   ==================================================== */
                .sm-avatar {
                    width: 44px; height: 44px; border-radius: 12px;
                    object-fit: cover;
                    border: 2px solid var(--sm-border);
                    cursor: pointer;
                    transition: transform .2s, box-shadow .2s;
                }
                .sm-avatar:hover { transform: scale(1.08); box-shadow: 0 4px 14px rgba(0,0,0,.15); }
                .sm-avatar-init {
                    width: 44px; height: 44px; border-radius: 12px;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    display: inline-flex; align-items: center; justify-content: center;
                    font-size: 16px; font-weight: 700; color: #fff;
                    border: 2px solid var(--sm-border); cursor: pointer;
                    transition: transform .2s, box-shadow .2s;
                }
                .sm-avatar-init:hover { transform: scale(1.08); box-shadow: 0 4px 14px rgba(0,0,0,.15); }

                /* ====================================================
                   STUDENT INFO IN TABLE ROW
                   ==================================================== */
                .stu-name    { font-weight: 600; font-size: 13px; color: #1e293b; margin-bottom: 2px; }
                .stu-meta    { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
                .stu-chip    {
                    background: #f1f5f9; color: #475569;
                    padding: 2px 7px; border-radius: 20px; font-size: 11px;
                    display: inline-flex; align-items: center; gap: 3px;
                }

                /* ====================================================
                   BADGES
                   ==================================================== */
                .badge-active   { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
                .badge-inactive { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
                .badge-new      { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
                .badge-old      { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
                .sm-badge {
                    display: inline-flex; align-items: center; gap: 4px;
                    padding: 4px 10px; border-radius: 20px;
                    font-size: 11px; font-weight: 600;
                }

                /* ====================================================
                   ACTION BUTTONS
                   ==================================================== */
                .act-btn {
                    width: 32px; height: 32px; border-radius: 8px; border: none;
                    display: inline-flex; align-items: center; justify-content: center;
                    font-size: 14px; cursor: pointer; transition: all .15s;
                }
                .act-view   { background: #e0f2fe; color: #0284c7; }
                .act-view:hover  { background: #0284c7; color: #fff; transform: translateY(-1px); }
                .act-edit   { background: #fef9c3; color: #a16207; }
                .act-edit:hover  { background: #d97706; color: #fff; transform: translateY(-1px); }
                .act-delete { background: #fee2e2; color: #dc2626; }
                .act-delete:hover { background: #dc2626; color: #fff; transform: translateY(-1px); }

                /* ====================================================
                   STUDENT PROFILE CARD (card view)
                   ==================================================== */
                .stu-card {
                    background: #fff;
                    border: 1px solid var(--sm-border);
                    border-radius: var(--sm-radius);
                    overflow: hidden;
                    transition: transform .2s, box-shadow .2s, border-color .2s;
                    height: 100%;
                    position: relative;
                }
                .stu-card:hover {
                    transform: translateY(-4px);
                    box-shadow: var(--sm-shadow-lg);
                    border-color: var(--sm-accent);
                }
                .stu-card-header {
                    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
                    padding: 18px 16px 14px;
                    position: relative;
                    min-height: 110px;
                }
                .stu-card-avatar {
                    position: absolute; top: 14px; right: 14px;
                    width: 68px; height: 68px; border-radius: 12px;
                    border: 3px solid rgba(255,255,255,.9);
                    overflow: hidden; background: #fff;
                    box-shadow: 0 4px 12px rgba(0,0,0,.2);
                    cursor: pointer;
                    transition: transform .2s;
                }
                .stu-card-avatar:hover { transform: scale(1.06); }
                .stu-card-avatar img  { width: 100%; height: 100%; object-fit: cover; }
                .stu-card-avatar-init {
                    width: 100%; height: 100%;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 24px; font-weight: 700; color: #667eea;
                    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
                }
                .stu-card-name {
                    font-size: 15px; font-weight: 700; color: #fff;
                    padding-right: 82px; margin-bottom: 5px; line-height: 1.3;
                }
                .stu-card-adm {
                    display: inline-block; background: rgba(255,255,255,.15);
                    backdrop-filter: blur(6px); color: rgba(255,255,255,.95);
                    padding: 3px 10px; border-radius: 20px; font-size: 11px;
                }
                .stu-card-checkbox {
                    position: absolute; top: 12px; left: 12px; z-index: 2;
                }
                .stu-card-body { padding: 14px 16px; }
                .stu-card-grid {
                    display: grid; grid-template-columns: 1fr 1fr;
                    gap: 10px; margin-bottom: 12px;
                }
                .stu-card-info-label { font-size: 10px; font-weight: 700; color: var(--sm-muted); text-transform: uppercase; letter-spacing: .04em; }
                .stu-card-info-val   { font-size: 13px; font-weight: 600; color: #374151; margin-top: 2px; }
                .stu-card-actions {
                    display: flex; gap: 6px;
                    padding-top: 12px; border-top: 1px solid var(--sm-border);
                }
                .stu-card-actions button {
                    flex: 1; padding: 8px 4px; border-radius: 8px; border: none;
                    font-size: 12px; font-weight: 600; cursor: pointer;
                    display: flex; align-items: center; justify-content: center; gap: 4px;
                    transition: all .15s;
                }
                .stu-card-view   { background: var(--sm-accent); color: #fff; }
                .stu-card-view:hover   { background: #1d4ed8; }
                .stu-card-edit   { background: #f3f4f6; color: #374151; border: 1px solid var(--sm-border); }
                .stu-card-edit:hover   { background: #e5e7eb; }
                .stu-card-delete { background: #fef2f2; color: var(--sm-danger); border: 1px solid #fecaca; }
                .stu-card-delete:hover { background: #fee2e2; }

                /* ====================================================
                   IMAGE ZOOM MODAL
                   ==================================================== */
                .img-zoom-modal .modal-content { background: transparent; border: none; }
                .img-zoom-modal .modal-dialog  { max-width: 90vw; }
                .img-zoom-modal .modal-body    {
                    display: flex; flex-direction: column;
                    align-items: center; justify-content: center;
                    min-height: 80vh; padding: 20px;
                }
                .img-zoomed {
                    max-width: 90vw; max-height: 72vh;
                    border-radius: 16px; border: 4px solid #fff;
                    box-shadow: 0 24px 64px rgba(0,0,0,.4);
                    object-fit: contain; cursor: pointer;
                    animation: zoomIn .25s ease;
                }
                @keyframes zoomIn {
                    from { opacity: 0; transform: scale(.85); }
                    to   { opacity: 1; transform: scale(1); }
                }
                .img-zoom-modal .btn-close {
                    position: fixed; top: 20px; right: 28px;
                    background: rgba(0,0,0,.7); border-radius: 50%;
                    padding: 12px; filter: brightness(0) invert(1);
                    opacity: 1; z-index: 9999;
                }
                .img-zoom-modal .btn-close:hover { background: rgba(0,0,0,.9); transform: scale(1.1); }
                .zoom-name {
                    color: #fff; margin-top: 18px; font-size: 17px; font-weight: 700;
                    background: rgba(0,0,0,.5); padding: 7px 22px;
                    border-radius: 40px; text-shadow: 0 1px 4px rgba(0,0,0,.3);
                }
                .zoom-detail {
                    color: rgba(255,255,255,.78); margin-top: 6px;
                    font-size: 13px; text-align: center;
                }

                /* ====================================================
                   VIEW TOGGLE BUTTONS
                   ==================================================== */
                .view-toggle .btn {
                    border-radius: 10px; padding: 8px 16px;
                    font-weight: 600; font-size: 13px;
                    transition: all .2s;
                }
                .view-toggle .btn.active {
                    background: linear-gradient(135deg, #1e3a5f, #2563eb);
                    border-color: #2563eb; color: #fff;
                    box-shadow: 0 4px 12px rgba(37,99,235,.3);
                }

                /* ====================================================
                   PAGINATION
                   ==================================================== */
                .sm-pagination { padding: 16px 20px; border-top: 1px solid var(--sm-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
                .sm-pagination .page-link { border: none; border-radius: 9px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; margin: 0 2px; transition: all .15s; color: #374151; }
                .sm-pagination .page-link:hover { background: #f3f4f6; color: var(--sm-accent); }
                .sm-pagination .page-item.active .page-link { background: linear-gradient(135deg, #1e3a5f, #2563eb); color: #fff; }

                /* ====================================================
                   EMPTY / LOADING STATES
                   ==================================================== */
                .sm-empty { padding: 60px 20px; text-align: center; }
                .sm-empty-icon { font-size: 60px; color: #d1d5db; margin-bottom: 16px; }
                .sm-empty-title { font-size: 18px; font-weight: 700; color: #374151; margin-bottom: 6px; }
                .sm-empty-desc  { color: var(--sm-muted); font-size: 13px; max-width: 360px; margin: 0 auto 20px; }
                .sm-loading { padding: 60px 20px; text-align: center; }
                .spin-ring {
                    width: 60px; height: 60px; margin: 0 auto;
                    border: 4px solid #f3f4f6; border-top-color: var(--sm-accent);
                    border-radius: 50%; animation: spin 1s linear infinite;
                }
                @keyframes spin { to { transform: rotate(360deg); } }

                /* ====================================================
                   MODAL HEADERS
                   ==================================================== */
                .modal-hdr {
                    background: linear-gradient(135deg, #1e3a5f, #2563eb 55%, #4f46e5 100%);
                    padding: 22px 28px; color: #fff; border: none;
                    position: relative; overflow: hidden;
                }
                .modal-hdr::before { content: ''; position: absolute; top: -50px; right: -50px; width: 160px; height: 160px; background: rgba(255,255,255,.06); border-radius: 50%; }
                .modal-hdr .modal-title { font-weight: 700; font-size: 16px; position: relative; }
                .modal-hdr .btn-close   { filter: brightness(0) invert(1); opacity: .8; position: relative; }
                .modal-hdr .btn-close:hover { opacity: 1; }

                /* ====================================================
                   PROGRESS STEPS
                   ==================================================== */
                .progress-steps { display: flex; justify-content: space-between; position: relative; margin-bottom: 28px; counter-reset: step; }
                .progress-steps::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: #e5e7eb; transform: translateY(-50%); z-index: 0; }
                .progress-steps .step { width: 38px; height: 38px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: #9ca3af; position: relative; z-index: 1; border: 2px solid #e5e7eb; }
                .progress-steps .step.active { background: var(--sm-accent); color: #fff; border-color: var(--sm-accent); box-shadow: 0 0 0 4px rgba(37,99,235,.15); }

                /* ====================================================
                   DRAG AND DROP COLUMNS (SORTABLE FIX)
                   ==================================================== */
                #columnsContainer {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0;
                }
                #columnsContainer .col-md-4 {
                    transition: none;
                }
                .draggable-item {
                    background: #fff;
                    border: 1.5px solid var(--sm-border) !important;
                    border-radius: 10px !important;
                    padding: 10px 12px !important;
                    cursor: default;
                    user-select: none;
                    transition: box-shadow .15s;
                    position: relative;
                }
                .draggable-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
                .drag-handle {
                    cursor: grab !important;
                    color: #9ca3af; padding: 4px 6px; margin-right: 6px;
                    border-radius: 6px; display: inline-flex; align-items: center;
                    transition: color .15s, background .15s;
                    font-size: 16px;
                }
                .drag-handle:hover  { color: var(--sm-accent); background: rgba(37,99,235,.08); }
                .drag-handle:active { cursor: grabbing !important; }
                .sortable-ghost    { opacity: .4; background: #e0f2fe !important; border: 2px dashed var(--sm-accent) !important; }
                .sortable-chosen   { box-shadow: 0 8px 24px rgba(0,0,0,.18) !important; transform: scale(1.02); border-color: var(--sm-accent) !important; z-index: 1000; }
                .sortable-drag     { opacity: .85; }
                .order-badge       { font-size: 10px; padding: 1px 5px; border-radius: 8px; float: right; background: var(--sm-accent); color: #fff; }

                /* ====================================================
                   BULK STATUS CARDS
                   ==================================================== */
                .bsc-card {
                    border: 1.5px solid var(--sm-border);
                    border-radius: 12px; overflow: hidden;
                    transition: transform .15s, box-shadow .15s, border-color .15s;
                    background: #fff;
                }
                .bsc-card:hover { transform: translateY(-3px); box-shadow: var(--sm-shadow); border-color: var(--sm-accent); }
                .bsc-avatar {
                    width: 56px; height: 56px; border-radius: 50%;
                    margin: 0 auto 10px;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    display: flex; align-items: center; justify-content: center;
                    font-size: 22px; font-weight: 700; color: #fff;
                    border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.12);
                }

                /* ====================================================
                   INFO CARD (view modal)
                   ==================================================== */
                .info-card { border: 1px solid var(--sm-border); border-radius: 12px; overflow: hidden; margin-bottom: 16px; }
                .info-card-header { background: #f8fafc; padding: 12px 16px; border-bottom: 1px solid var(--sm-border); }
                .info-card-header h6 { margin: 0; font-size: 13px; font-weight: 700; color: var(--sm-primary); }
                .info-card-body { padding: 14px 16px; }
                .info-card-body .table th { font-size: 12px; color: var(--sm-muted); font-weight: 600; width: 42%; padding: 6px 0; }
                .info-card-body .table td { font-size: 13px; padding: 6px 0; }

                /* ====================================================
                   MISC UTILITIES
                   ==================================================== */
                .btn-pg { background: linear-gradient(135deg, #1e3a5f, #2563eb); border: none; color: #fff; padding: 10px 22px; border-radius: 10px; font-weight: 600; font-size: 13px; transition: opacity .15s, transform .1s; }
                .btn-pg:hover { opacity: .9; transform: translateY(-1px); color: #fff; }
                @media(max-width:768px) { .sm-stat-value { font-size: 22px; } }
            </style>

            <!-- ========================================================
                 HERO
                 ======================================================== -->
            <div class="sm-hero">
                <h1><i class="ri-group-line me-2"></i>Student Management</h1>
                <p>Manage student records, registrations, status, and term enrolments from one place.</p>
            </div>

            <!-- ========================================================
                 STAT CARDS — ROW 1
                 ======================================================== -->
            <div class="row g-3 mb-2">
                <div class="col-6 col-md-3">
                    <div class="sm-stat c-blue">
                        <div class="sm-stat-icon"><i class="fas fa-users"></i></div>
                        <div class="sm-stat-value">{{ $total_population }}</div>
                        <div class="sm-stat-label">Total Students</div>
                        <div class="sm-stat-sub"><span class="up"><i class="fas fa-arrow-up"></i> 12%</span> from last term</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="sm-stat c-green">
                        <div class="sm-stat-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="sm-stat-value">{{ $student_status_counts['Active'] ?? 0 }}</div>
                        <div class="sm-stat-label">Active Students</div>
                        <div class="sm-stat-sub"><span class="up"><i class="fas fa-arrow-up"></i> 8%</span> from last term</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="sm-stat c-amber">
                        <div class="sm-stat-icon"><i class="fas fa-user-plus"></i></div>
                        <div class="sm-stat-value">{{ $status_counts['New Student'] ?? 0 }}</div>
                        <div class="sm-stat-label">New Admissions</div>
                        <div class="sm-stat-sub"><span class="up"><i class="fas fa-arrow-up"></i> 15%</span> from last term</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="sm-stat c-purple">
                        <div class="sm-stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="sm-stat-value">{{ $staff_count }}</div>
                        <div class="sm-stat-label">Staff Count</div>
                        <div class="sm-stat-sub"><span class="up"><i class="fas fa-arrow-up"></i> 5%</span> from last term</div>
                    </div>
                </div>
            </div>

            <!-- ========================================================
                 STAT CARDS — ROW 2
                 ======================================================== -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="sm-stat c-sky">
                        <div class="sm-stat-icon"><i class="fas fa-mars"></i></div>
                        <div class="sm-stat-value">{{ $gender_counts['Male'] ?? 0 }}</div>
                        <div class="sm-stat-label">Male Students</div>
                        <div class="sm-stat-sub">{{ $total_population > 0 ? number_format(($gender_counts['Male'] / $total_population) * 100, 1) : 0 }}% of total</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="sm-stat c-pink">
                        <div class="sm-stat-icon"><i class="fas fa-venus"></i></div>
                        <div class="sm-stat-value">{{ $gender_counts['Female'] ?? 0 }}</div>
                        <div class="sm-stat-label">Female Students</div>
                        <div class="sm-stat-sub">{{ $total_population > 0 ? number_format(($gender_counts['Female'] / $total_population) * 100, 1) : 0 }}% of total</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="sm-stat c-teal">
                        <div class="sm-stat-icon"><i class="fas fa-cross"></i></div>
                        <div class="sm-stat-value">{{ $religion_counts['Christianity'] ?? 0 }}</div>
                        <div class="sm-stat-label">Christians</div>
                        <div class="sm-stat-sub">{{ $total_population > 0 ? number_format(($religion_counts['Christianity'] / $total_population) * 100, 1) : 0 }}% of total</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="sm-stat c-orange">
                        <div class="sm-stat-icon"><i class="fas fa-moon"></i></div>
                        <div class="sm-stat-value">{{ $religion_counts['Islam'] ?? 0 }}</div>
                        <div class="sm-stat-label">Muslims</div>
                        <div class="sm-stat-sub">{{ $total_population > 0 ? number_format(($religion_counts['Islam'] / $total_population) * 100, 1) : 0 }}% of total</div>
                    </div>
                </div>
            </div>

            <!-- ========================================================
                 ALERTS
                 ======================================================== -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><strong>Validation Error!</strong>
                    <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- ========================================================
                 MAIN PANEL
                 ======================================================== -->
            <div class="sm-panel">

                <!-- Panel Header -->
                <div class="sm-panel-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                        </div>
                        <div class="sm-panel-title">
                            <i class="fas fa-list"></i> Student Records
                            <span class="badge bg-primary rounded-pill" id="totalStudents">0</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- View Toggle -->
                        <div class="btn-group view-toggle" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="tableViewBtn">
                                <i class="fas fa-table me-1"></i>Table
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="cardViewBtn">
                                <i class="fas fa-th-large me-1"></i>Cards
                            </button>
                        </div>
                        <!-- Bulk Actions -->
                        @can('Delete student')
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="bulkActionsDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false" disabled>
                                <i class="fas fa-cog me-1"></i>Actions
                            </button>
                            <ul class="dropdown-menu shadow-sm border-0 rounded-3 p-2">
                                <li><a class="dropdown-item rounded-2" href="javascript:void(0);" id="deleteMultipleBtn">
                                    <i class="fas fa-trash text-danger me-2"></i>Delete Selected
                                </a></li>
                                <li><a class="dropdown-item rounded-2" href="javascript:void(0);" id="updateCurrentTermBtn">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>Update Current Term
                                </a></li>
                            </ul>
                        </div>
                        @endcan
                        @can('Create student')
                        <button type="button" class="btn-pg" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-user-plus me-1"></i>Add Student
                        </button>
                        @endcan
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#printStudentReportModal">
                            <i class="fas fa-file-export me-1"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="sm-filter">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <div class="sm-search">
                                <i class="fas fa-search icon"></i>
                                <input type="text" id="search-input" placeholder="Search name or admission no…">
                                <button class="clear-btn" id="clear-search" title="Clear"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <select id="schoolclass-filter">
                                <option value="all">All Classes</option>
                                @foreach($schoolclasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->schoolclass }} - {{ $class->arm }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <select id="term-filter">
                                <option value="all">All Terms</option>
                                @foreach($schoolterms as $term)
                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <select id="session-filter">
                                <option value="all">All Sessions</option>
                                @foreach($schoolsessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 col-6">
                            <button class="btn btn-primary w-100" id="filterBtn" style="height:42px;border-radius:10px;">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                        <div class="col-md-1 col-6">
                            <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn" style="height:42px;border-radius:10px;">
                                <i class="fas fa-redo-alt"></i>
                            </button>
                        </div>
                        <div class="col-md-1 col-6">
                            <button class="btn btn-warning w-100" id="bulkStatusBtn" title="Bulk Update Status" style="height:42px;border-radius:10px;font-size:12px;">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="col-md-1 col-6">
                            <button class="btn btn-info w-100 text-white" id="manageTermBtn" title="Manage Term" style="height:42px;border-radius:10px;font-size:12px;">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TABLE VIEW -->
                <div id="tableView" class="view-container">
                    <div class="table-responsive">
                        <table class="table sm-table mb-0">
                            <thead>
                                <tr>
                                    <th width="46"><div class="form-check"><input class="form-check-input" type="checkbox" id="checkAllTable"></div></th>
                                    <th width="56">Photo</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Gender</th>
                                    <th>Registered</th>
                                    <th width="120" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- CARD VIEW -->
                <div id="cardView" class="view-container d-none p-4">
                    <div class="row g-3" id="studentsCardsContainer"></div>
                </div>

                <!-- EMPTY STATE -->
                <div id="emptyState" class="sm-empty d-none">
                    <div class="sm-empty-icon"><i class="fas fa-users-slash"></i></div>
                    <div class="sm-empty-title">No Students Found</div>
                    <div class="sm-empty-desc">Try adjusting your search or filters to find what you're looking for.</div>
                    <button class="btn-pg" id="resetFromEmptyBtn"><i class="fas fa-redo me-1"></i>Reset Filters</button>
                </div>

                <!-- LOADING STATE -->
                <div id="loadingState" class="sm-loading">
                    <div class="spin-ring"></div>
                    <p class="mt-3 text-muted">Loading students…</p>
                </div>

                <!-- PAGINATION -->
                <div class="sm-pagination">
                    <div class="text-muted" style="font-size:13px;">
                        Showing <strong id="showingCount">0</strong>–<strong id="toCount">0</strong> of <strong id="totalCount">0</strong> students
                    </div>
                    <nav><ul class="pagination mb-0" id="pagination">
                        <li class="page-item" id="prevPageLi"><a class="page-link" href="javascript:void(0);" id="prevPage"><i class="fas fa-chevron-left"></i></a></li>
                        <li class="page-item" id="nextPageLi"><a class="page-link" href="javascript:void(0);" id="nextPage"><i class="fas fa-chevron-right"></i></a></li>
                    </ul></nav>
                </div>
            </div><!-- /sm-panel -->
        </div><!-- /container-fluid -->

        <!-- ============================================================
             IMAGE ZOOM MODAL
             ============================================================ -->
        <div class="modal fade img-zoom-modal" id="imageZoomModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body text-center">
                        <img id="zoomedStudentImg" src="" alt="Student Photo" class="img-zoomed" onclick="bootstrap.Modal.getInstance(document.getElementById('imageZoomModal')).hide()">
                        <div class="zoom-name" id="zoomedStudentName"></div>
                        <div class="zoom-detail" id="zoomedStudentDetail"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
             UPDATE CURRENT TERM MODAL
             ============================================================ -->
        <div id="updateCurrentTermModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header modal-hdr">
                        <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Register / Update Term</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form id="updateCurrentTermForm">
                            @csrf
                            <div class="alert alert-info border-0 rounded-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Registering term for <strong><span id="selectedStudentsCount">0</span></strong> selected student(s).
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Class</label>
                                <select class="form-control rounded-3" name="schoolclassId" required>
                                    <option value="">Select Class</option>
                                    @foreach($schoolclasses as $class)
                                        <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Term</label>
                                <select class="form-control rounded-3" name="termId" required>
                                    <option value="">Select Term</option>
                                    @foreach($schoolterms as $term)
                                        <option value="{{ $term->id }}">{{ $term->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Session</label>
                                <select class="form-control rounded-3" name="sessionId" required>
                                    <option value="">Select Session</option>
                                    @foreach($schoolsessions as $session)
                                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_current" id="is_current" value="1" checked>
                                <label class="form-check-label" for="is_current">Mark as current term</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-pg" id="confirmUpdateCurrentTerm">
                            <i class="fas fa-save me-1"></i>Register / Update
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
             EXPORT / REPORT MODAL
             ============================================================ -->
        <div id="printStudentReportModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-hdr">
                        <h5 class="modal-title"><i class="fas fa-file-export me-2"></i>Generate Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form id="printReportForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Class</label>
                                    <select class="form-select rounded-3" name="class_id">
                                        <option value="">— All Classes —</option>
                                        @foreach($schoolclasses as $class)
                                            <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select class="form-select rounded-3" name="status">
                                        <option value="">— All —</option>
                                        <option value="1">Old Students</option>
                                        <option value="2">New Students</option>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Term</label>
                                    <select class="form-select rounded-3" name="term_id">
                                        <option value="">— All Terms —</option>
                                        @foreach($schoolterms as $term)
                                            <option value="{{ $term->id }}">{{ $term->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Session</label>
                                    <select class="form-select rounded-3" name="session_id">
                                        <option value="">— All Sessions —</option>
                                        @foreach($schoolsessions as $session)
                                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- ─── DRAG & DROP COLUMNS ─── -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold d-flex align-items-center gap-2">
                                    <i class="fas fa-grip-vertical text-primary"></i>
                                    Select &amp; Arrange Columns
                                    <small class="text-muted fw-normal">— drag handle to reorder</small>
                                </label>
                                <div class="alert alert-info py-2 px-3 mb-2" style="font-size:12px;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Drag the <i class="fas fa-grip-vertical"></i> handle on any column chip to reorder. Check to include in report.
                                </div>
                                <!-- Sortable container: items sit directly inside, no nested grid wrappers -->
                                <div id="columnsContainer" style="display:flex;flex-wrap:wrap;gap:8px;min-height:48px;padding:8px;background:#f8fafc;border:1.5px solid var(--sm-border);border-radius:12px;">
                                    <input type="hidden" name="columns_order" id="columnsOrderInput" value="">
                                    @php
                                        $availableColumns = [
                                            'photo'          => 'Photo',
                                            'admissionNo'    => 'Admission No',
                                            'lastname'       => 'Last Name',
                                            'firstname'      => 'First Name',
                                            'othername'      => 'Other Name',
                                            'gender'         => 'Gender',
                                            'dateofbirth'    => 'Date of Birth',
                                            'age'            => 'Age',
                                            'class'          => 'Class / Arm',
                                            'status'         => 'Student Status',
                                            'admission_date' => 'Admission Date',
                                            'phone_number'   => 'Phone Number',
                                            'state'          => 'State of Origin',
                                            'local'          => 'LGA',
                                            'religion'       => 'Religion',
                                            'blood_group'    => 'Blood Group',
                                            'father_name'    => "Father's Name",
                                            'mother_name'    => "Mother's Name",
                                            'guardian_phone' => 'Guardian Phone',
                                            'term'           => 'Term',
                                            'session'        => 'Session',
                                        ];
                                    @endphp
                                    @foreach($availableColumns as $key => $label)
                                        <div class="draggable-item d-inline-flex align-items-center gap-2 px-2 py-2"
                                             data-column="{{ $key }}"
                                             style="background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;cursor:default;user-select:none;white-space:nowrap;">
                                            <span class="drag-handle" title="Drag to reorder">
                                                <i class="fas fa-grip-vertical"></i>
                                            </span>
                                            <input class="form-check-input column-checkbox m-0" type="checkbox"
                                                   name="columns[]" value="{{ $key }}" id="col_{{ $key }}"
                                                   {{ in_array($key, ['admissionNo','lastname','firstname','class','gender']) ? 'checked' : '' }}>
                                            <label class="form-check-label mb-0" for="col_{{ $key }}" style="font-size:12px;font-weight:600;cursor:pointer;">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-2 d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3" id="selectAllColumnsBtn">Check All</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" id="deselectAllColumnsBtn">Uncheck All</button>
                                </div>
                            </div>

                            <!-- Report Header -->
                            <div class="card border-0 bg-light rounded-3 mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="ri-file-info-line me-1 text-primary"></i>Report Options</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="include_header" id="includeHeader" checked>
                                                <label class="form-check-label" for="includeHeader">Include School Header</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="include_logo" id="includeLogo" checked>
                                                <label class="form-check-label" for="includeLogo">Include School Logo</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Page Orientation</label>
                                            <select class="form-select form-select-sm rounded-3" name="orientation" id="orientation">
                                                <option value="portrait">Portrait</option>
                                                <option value="landscape">Landscape</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Format -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Export Format</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="format_pdf" value="pdf" checked>
                                        <label class="form-check-label" for="format_pdf"><i class="ri-file-pdf-2-line text-danger me-1"></i>PDF</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="format_excel" value="excel">
                                        <label class="form-check-label" for="format_excel"><i class="ri-file-excel-2-line text-success me-1"></i>Excel</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="alert alert-info border-0 rounded-3 small mb-0">
                                <i class="ri-information-fill me-1"></i>
                                <strong>Selected columns:</strong>
                                <span id="columnOrderPreview">admissionNo, lastname, firstname, class, gender</span>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success rounded-3" id="generateReportBtn">
                            <i class="ri-printer-line me-1"></i>Generate &amp; Download
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
             ADD STUDENT MODAL
             ============================================================ -->
        <div id="addStudentModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-hdr">
                        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Student Registration</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="addStudentForm" enctype="multipart/form-data" autocomplete="off" method="POST" action="{{ route('student.store') }}">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="progress-steps mb-4">
                                <div class="step active">1</div>
                                <div class="step">2</div>
                                <div class="step">3</div>
                                <div class="step">4</div>
                            </div>
                            <div class="row g-4">
                                <!-- Academic Details -->
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-primary text-white rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Admission Number Mode <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="admissionMode" id="admissionAuto" value="auto" required onchange="toggleAdmissionInput()">
                                                        <label class="form-check-label" for="admissionAuto"><i class="fas fa-magic me-1"></i>Auto Generate</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="admissionMode" id="admissionManual" value="manual" required onchange="toggleAdmissionInput()">
                                                        <label class="form-check-label" for="admissionManual"><i class="fas fa-edit me-1"></i>Manual Entry</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Admission Number <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select class="form-control" id="admissionYear" name="admissionYear" required onchange="updateAdmissionNumber()">
                                                        @for($year = date('Y'); $year >= date('Y') - 5; $year--)<option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>@endfor
                                                    </select>
                                                    <input type="text" id="admissionNo" name="admissionNo" class="form-control" placeholder="TCC/YYYY/0001" required>
                                                </div>
                                                <small class="text-muted">Format: TCC/YYYY/0001</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Admission Date <span class="text-danger">*</span></label>
                                                <input type="date" id="admissionDate" name="admissionDate" class="form-control" required max="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                                                <select id="schoolclassid" name="schoolclassid" class="form-control" required>
                                                    <option value="">Select Class</option>
                                                    @foreach($schoolclasses as $class)<option value="{{ $class->id }}">{{ $class->schoolclass }} - {{ $class->arm }}</option>@endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Term <span class="text-danger">*</span></label>
                                                        <select id="termid" name="termid" class="form-control" required>
                                                            <option value="">Select Term</option>
                                                            @foreach($schoolterms as $term)<option value="{{ $term->id }}">{{ $term->name }}</option>@endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
                                                        <select id="sessionid" name="sessionid" class="form-control" required>
                                                            <option value="">Select Session</option>
                                                            @foreach($schoolsessions as $session)<option value="{{ $session->id }}">{{ $session->name }}</option>@endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Student Status <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="statusId" id="statusOld" value="1" required><label class="form-check-label" for="statusOld">Old Student</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="statusId" id="statusNew" value="2" required><label class="form-check-label" for="statusNew">New Student</label></div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Activity Status <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="student_status" id="statusActive" value="Active" required><label class="form-check-label" for="statusActive">Active</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="student_status" id="statusInactive" value="Inactive" required><label class="form-check-label" for="statusInactive">Inactive</label></div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Student Category <span class="text-danger">*</span></label>
                                                <select id="student_category" name="student_category" class="form-control" required>
                                                    <option value="">Select Category</option>
                                                    <option value="Day">Day Student</option>
                                                    <option value="Boarding">Boarding Student</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Personal Details -->
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-info text-white rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 text-center">
                                                <div class="upload-area border border-2 border-dashed border-primary rounded p-3">
                                                    <img id="addStudentAvatar" src="https://via.placeholder.com/120x120/2563eb/ffffff?text=Photo" alt="Avatar Preview" class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;border:4px solid #2563eb;"/>
                                                    <div>
                                                        <label for="avatar" class="btn btn-outline-primary btn-sm"><i class="fas fa-camera me-1"></i>Choose Photo</label>
                                                        <input type="file" id="avatar" name="avatar" class="d-none" accept=".png,.jpg,.jpeg" onchange="previewImage(this)">
                                                        <div class="form-text">Max 2MB (PNG, JPG)</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-3">
                                                    <label class="form-label fw-semibold">Title</label>
                                                    <select id="title" name="title" class="form-control"><option value="">—</option><option value="Master">Master</option><option value="Miss">Miss</option></select>
                                                </div>
                                                <div class="col-9">
                                                    <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                                    <input type="text" id="lastname" name="lastname" class="form-control" placeholder="Last name" required>
                                                </div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                                    <input type="text" id="firstname" name="firstname" class="form-control" placeholder="First name" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold">Other Names</label>
                                                    <input type="text" id="othername" name="othername" class="form-control" placeholder="Middle name">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" required><label class="form-check-label" for="genderMale">Male</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female" required><label class="form-check-label" for="genderFemale">Female</label></div>
                                                </div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-7">
                                                    <label class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                                                    <input type="date" id="addDOB" name="dateofbirth" class="form-control" required onchange="calculateAge(this.value,'addAgeInput')">
                                                </div>
                                                <div class="col-5">
                                                    <label class="form-label fw-semibold">Age</label>
                                                    <input type="number" id="addAgeInput" name="age" class="form-control" readonly>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Place of Birth <span class="text-danger">*</span></label>
                                                <input type="text" id="placeofbirth" name="placeofbirth" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Phone Number</label>
                                                <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="+234 …">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Email</label>
                                                <input type="email" id="email" name="email" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Future Ambition <span class="text-danger">*</span></label>
                                                <textarea id="future_ambition" name="future_ambition" class="form-control" rows="2" required></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Permanent Address <span class="text-danger">*</span></label>
                                                <textarea id="permanent_address" name="permanent_address" class="form-control" rows="2" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Additional Info -->
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-success text-white rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Additional Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3"><label class="form-label fw-semibold">Nationality <span class="text-danger">*</span></label><input type="text" id="nationality" name="nationality" class="form-control" required></div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">State <span class="text-danger">*</span></label><select id="addState" name="state" class="form-control" required><option value="">Select State</option></select></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">LGA <span class="text-danger">*</span></label><select id="addLocal" name="local" class="form-control" required disabled><option value="">Select LGA</option></select></div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">City</label><input type="text" id="city" name="city" class="form-control"></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Religion <span class="text-danger">*</span></label><select id="religion" name="religion" class="form-control" required><option value="">Select</option><option value="Christianity">Christianity</option><option value="Islam">Islam</option><option value="Others">Others</option></select></div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Blood Group</label><select id="blood_group" name="blood_group" class="form-control"><option value="">Select</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Mother Tongue</label><input type="text" id="mother_tongue" name="mother_tongue" class="form-control"></div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">NIN Number</label><input type="text" id="nin_number" name="nin_number" class="form-control" maxlength="11"></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">School House <span class="text-danger">*</span></label><select id="school_house" name="schoolhouseid" class="form-control" required><option value="">Select House</option>@foreach($schoolhouses as $h)<option value="{{ $h->id }}">{{ $h->house }}</option>@endforeach</select></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Parent & Previous School -->
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-header bg-warning text-dark rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Parent / Guardian Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3"><label class="form-label fw-semibold">Father's Name</label><input type="text" id="father_name" name="father_name" class="form-control"></div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Father's Phone</label><input type="text" id="father_phone" name="father_phone" class="form-control"></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Father's Occupation</label><input type="text" id="father_occupation" name="father_occupation" class="form-control"></div>
                                            </div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Father's City</label><input type="text" id="father_city" name="father_city" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Mother's Name</label><input type="text" id="mother_name" name="mother_name" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Mother's Phone</label><input type="text" id="mother_phone" name="mother_phone" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Parent Email</label><input type="email" id="parent_email" name="parent_email" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Parent Address</label><textarea id="parent_address" name="parent_address" class="form-control" rows="2"></textarea></div>
                                        </div>
                                    </div>
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-secondary text-white rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-school me-2"></i>Previous School</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3"><label class="form-label fw-semibold">Last School Attended</label><input type="text" id="last_school" name="last_school" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Last Class Attended</label><input type="text" id="last_class" name="last_class" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Reason for Leaving</label><textarea id="reason_for_leaving" name="reason_for_leaving" class="form-control" rows="2"></textarea></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                            <button type="submit" class="btn-pg" id="add-btn"><i class="fas fa-save me-1"></i>Register Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================
             EDIT STUDENT MODAL
             ============================================================ -->
        <div id="editStudentModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-hdr">
                        <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editStudentForm" enctype="multipart/form-data" autocomplete="off" method="POST"
                          action="{{ route('student.update', ':id') }}"
                          data-base-action="{{ route('student.update', ':id') }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body p-4">
                            <input type="hidden" id="editStudentId" name="id">
                            <div class="progress-steps mb-4">
                                <div class="step">1</div><div class="step">2</div><div class="step">3</div><div class="step">4</div>
                            </div>
                            <div class="row g-4">
                                <!-- Academic Details -->
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-primary text-white rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Admission Number Mode <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="admissionMode" id="editAdmissionAuto" value="auto" required onchange="toggleAdmissionInput('edit')"><label class="form-check-label" for="editAdmissionAuto">Auto</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="admissionMode" id="editAdmissionManual" value="manual" required onchange="toggleAdmissionInput('edit')"><label class="form-check-label" for="editAdmissionManual">Manual</label></div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Admission Number <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select class="form-control" id="editAdmissionYear" name="admissionYear" required onchange="updateAdmissionNumber('edit')">
                                                        @for($year = date('Y'); $year >= date('Y') - 5; $year--)<option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>@endfor
                                                    </select>
                                                    <input type="text" id="editAdmissionNo" name="admissionNo" class="form-control" placeholder="TCC/YYYY/0001" required>
                                                </div>
                                            </div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Admission Date <span class="text-danger">*</span></label><input type="date" id="editAdmissionDate" name="admissionDate" class="form-control" required max="{{ date('Y-m-d') }}"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Class <span class="text-danger">*</span></label><select id="editSchoolclassid" name="schoolclassid" class="form-control" required><option value="">Select Class</option>@foreach($schoolclasses as $class)<option value="{{ $class->id }}">{{ $class->schoolclass }} - {{ $class->arm }}</option>@endforeach</select></div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Term <span class="text-danger">*</span></label><select id="editTermid" name="termid" class="form-control" required><option value="">Select Term</option>@foreach($schoolterms as $term)<option value="{{ $term->id }}">{{ $term->name }}</option>@endforeach</select></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Session <span class="text-danger">*</span></label><select id="editSessionid" name="sessionid" class="form-control" required><option value="">Select Session</option>@foreach($schoolsessions as $session)<option value="{{ $session->id }}">{{ $session->name }}</option>@endforeach</select></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Student Status <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="statusId" id="editStatusOld" value="1" required><label class="form-check-label" for="editStatusOld">Old</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="statusId" id="editStatusNew" value="2" required><label class="form-check-label" for="editStatusNew">New</label></div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Activity Status <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="student_status" id="editStatusActive" value="Active" required><label class="form-check-label" for="editStatusActive">Active</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="student_status" id="editStatusInactive" value="Inactive" required><label class="form-check-label" for="editStatusInactive">Inactive</label></div>
                                                </div>
                                            </div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Category <span class="text-danger">*</span></label><select id="editStudentCategory" name="student_category" class="form-control" required><option value="">Select</option><option value="Day">Day</option><option value="Boarding">Boarding</option></select></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Personal Details -->
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-info text-white rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Details</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 text-center">
                                                <img id="editStudentAvatar" src="{{ asset('theme/layouts/assets/media/avatars/blank.png') }}" alt="Avatar Preview" class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;border:4px solid #0dcaf0;cursor:pointer;" onclick="document.getElementById('editAvatar').click()"/>
                                                <div><label for="editAvatar" class="btn btn-outline-info btn-sm"><i class="fas fa-camera me-1"></i>Choose Photo</label><input type="file" id="editAvatar" name="avatar" class="d-none" accept=".png,.jpg,.jpeg" onchange="previewImage(this,'editStudentAvatar')"></div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-3"><label class="form-label fw-semibold">Title</label><select id="editTitle" name="title" class="form-control"><option value="">—</option><option value="Master">Master</option><option value="Miss">Miss</option></select></div>
                                                <div class="col-9"><label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label><input type="text" id="editLastname" name="lastname" class="form-control" required></div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6"><label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label><input type="text" id="editFirstname" name="firstname" class="form-control" required></div>
                                                <div class="col-6"><label class="form-label fw-semibold">Other Names</label><input type="text" id="editOthername" name="othername" class="form-control"></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="gender" id="editGenderMale" value="Male" required><label class="form-check-label" for="editGenderMale">Male</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="radio" name="gender" id="editGenderFemale" value="Female" required><label class="form-check-label" for="editGenderFemale">Female</label></div>
                                                </div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-7"><label class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label><input type="date" id="editDOB" name="dateofbirth" class="form-control" required onchange="calculateAge(this.value,'editAgeInput')"></div>
                                                <div class="col-5"><label class="form-label fw-semibold">Age</label><input type="number" id="editAgeInput" name="age" class="form-control" readonly></div>
                                            </div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Place of Birth <span class="text-danger">*</span></label><input type="text" id="editPlaceofbirth" name="placeofbirth" class="form-control" required></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Phone Number</label><input type="text" id="editPhoneNumber" name="phone_number" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Email</label><input type="email" id="editEmail" name="email" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Future Ambition <span class="text-danger">*</span></label><textarea id="editFutureAmbition" name="future_ambition" class="form-control" rows="2" required></textarea></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Permanent Address <span class="text-danger">*</span></label><textarea id="editPermanentAddress" name="permanent_address" class="form-control" rows="2" required></textarea></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Additional Info -->
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-success text-white rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Additional Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3"><label class="form-label fw-semibold">Nationality <span class="text-danger">*</span></label><input type="text" id="editNationality" name="nationality" class="form-control" required></div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">State <span class="text-danger">*</span></label><select id="editState" name="state" class="form-control" required><option value="">Select State</option></select></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">LGA <span class="text-danger">*</span></label><select id="editLocal" name="local" class="form-control" required disabled><option value="">Select LGA</option></select></div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">City</label><input type="text" id="editCity" name="city" class="form-control"></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Religion <span class="text-danger">*</span></label><select id="editReligion" name="religion" class="form-control" required><option value="">Select</option><option value="Christianity">Christianity</option><option value="Islam">Islam</option><option value="Others">Others</option></select></div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Blood Group</label><select id="editBloodGroup" name="blood_group" class="form-control"><option value="">Select</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Mother Tongue</label><input type="text" id="editMotherTongue" name="mother_tongue" class="form-control"></div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">NIN Number</label><input type="text" id="editNinNumber" name="nin_number" class="form-control" maxlength="11"></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">School House <span class="text-danger">*</span></label><select id="editSchoolHouse" name="schoolhouseid" class="form-control" required><option value="">Select House</option>@foreach($schoolhouses as $h)<option value="{{ $h->id }}">{{ $h->house }}</option>@endforeach</select></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Parent & Previous School -->
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-header bg-warning text-dark rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Parent / Guardian</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3"><label class="form-label fw-semibold">Father's Name</label><input type="text" id="editFatherName" name="father_name" class="form-control"></div>
                                            <div class="row g-2">
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Father's Phone</label><input type="text" id="editFatherPhone" name="father_phone" class="form-control"></div>
                                                <div class="col-6 mb-3"><label class="form-label fw-semibold">Father's Occupation</label><input type="text" id="editFatherOccupation" name="father_occupation" class="form-control"></div>
                                            </div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Father's City</label><input type="text" id="editFatherCity" name="father_city" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Mother's Name</label><input type="text" id="editMotherName" name="mother_name" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Mother's Phone</label><input type="text" id="editMotherPhone" name="mother_phone" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Parent Email</label><input type="email" id="editParentEmail" name="parent_email" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Parent Address</label><textarea id="editParentAddress" name="parent_address" class="form-control" rows="2"></textarea></div>
                                        </div>
                                    </div>
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-secondary text-white rounded-top-3">
                                            <h6 class="mb-0"><i class="fas fa-school me-2"></i>Previous School</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3"><label class="form-label fw-semibold">Last School</label><input type="text" id="editLastSchool" name="last_school" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Last Class</label><input type="text" id="editLastClass" name="last_class" class="form-control"></div>
                                            <div class="mb-3"><label class="form-label fw-semibold">Reason for Leaving</label><textarea id="editReasonForLeaving" name="reason_for_leaving" class="form-control" rows="2"></textarea></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                            <button type="submit" class="btn-pg" id="edit-btn"><i class="fas fa-save me-1"></i>Update Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================
             VIEW STUDENT MODAL
             ============================================================ -->
        <div id="viewStudentModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-hdr">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-graduation-cap fa-xl"></i>
                            <div>
                                <h5 class="modal-title mb-0">Student Profile</h5>
                                <small style="color:rgba(255,255,255,.7)">Complete student information</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <!-- Profile Header -->
                        <div class="bg-light p-4 border-bottom">
                            <div class="d-flex align-items-center gap-4">
                                <div class="position-relative" style="flex-shrink:0;">
                                    <img id="viewStudentPhoto"
                                         src="{{ asset('theme/layouts/assets/media/avatars/blank.png') }}"
                                         alt="Student Photo"
                                         class="rounded-circle border border-3 shadow"
                                         style="width:110px;height:110px;object-fit:cover;border-color:#fff!important;cursor:pointer;"
                                         onclick="openZoomFromView(this)">
                                    <span id="studentStatusIndicator" class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-2 border-white" style="width:18px;height:18px;"></span>
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="fw-bold mb-2" id="viewFullName" style="color:var(--sm-primary)">—</h3>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span class="badge bg-primary px-3 py-2"><i class="fas fa-id-card me-1"></i><span id="viewAdmissionNumber">—</span></span>
                                        <span class="badge bg-info px-3 py-2"><i class="fas fa-school me-1"></i><span id="viewClassDisplay">—</span></span>
                                        <span class="badge px-3 py-2" id="viewStudentTypeBadge" style="background:#fef3c7;color:#92400e;"><i class="fas fa-user-tag me-1"></i><span id="viewStudentType">—</span></span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-4 text-muted" style="font-size:13px;">
                                        <div><i class="fas fa-calendar-alt me-1"></i>Admitted: <span id="viewAdmittedDate">—</span></div>
                                        <div><i class="fas fa-venus-mars me-1"></i><span id="viewGenderText">—</span></div>
                                        <div><i class="fas fa-birthday-cake me-1"></i>Age: <span id="viewAge">—</span> yrs</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Tabs -->
                        <div class="px-4 pt-3">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#vPersonal" role="tab"><i class="fas fa-user-circle me-1"></i>Personal</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vAcademic" role="tab"><i class="fas fa-graduation-cap me-1"></i>Academic</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vFamily" role="tab"><i class="fas fa-users me-1"></i>Family</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vAdditional" role="tab"><i class="fas fa-info-circle me-1"></i>Additional</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vTerms" role="tab"><i class="fas fa-history me-1"></i>Term History</a></li>
                            </ul>
                        </div>
                        <div class="tab-content p-4">
                            <!-- Personal -->
                            <div class="tab-pane fade show active" id="vPersonal">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-id-badge me-2 text-primary"></i>Basic Information</h6></div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm">
                                                    <tr><th>Full Name:</th><td id="viewFullNameDetail">—</td></tr>
                                                    <tr><th>Title:</th><td id="viewTitle">—</td></tr>
                                                    <tr><th>Date of Birth:</th><td><span id="viewDOB">—</span> (<span id="viewAgeDetail">—</span> yrs)</td></tr>
                                                    <tr><th>Place of Birth:</th><td id="viewPlaceOfBirth">—</td></tr>
                                                    <tr><th>Gender:</th><td id="viewGenderDetail">—</td></tr>
                                                    <tr><th>Blood Group:</th><td id="viewBloodGroupDetail">—</td></tr>
                                                    <tr><th>Religion:</th><td id="viewReligionDetail">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-address-card me-2 text-primary"></i>Contact</h6></div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm">
                                                    <tr><th>Phone:</th><td id="viewPhoneNumber">—</td></tr>
                                                    <tr><th>Email:</th><td id="viewEmailAddress">—</td></tr>
                                                    <tr><th>Address:</th><td id="viewPermanentAddress">—</td></tr>
                                                    <tr><th>City:</th><td id="viewCity">—</td></tr>
                                                    <tr><th>State:</th><td id="viewStateOrigin">—</td></tr>
                                                    <tr><th>LGA:</th><td id="viewLGA">—</td></tr>
                                                    <tr><th>Nationality:</th><td id="viewNationality">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-rocket me-2 text-primary"></i>Future Ambition</h6></div>
                                            <div class="info-card-body"><p class="mb-0 fst-italic" id="viewFutureAmbition">—</p></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Academic -->
                            <div class="tab-pane fade" id="vAcademic">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-graduation-cap me-2 text-success"></i>Current Academic Status</h6></div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm">
                                                    <tr><th>Admission No:</th><td class="fw-bold text-primary" id="viewAdmissionNo">—</td></tr>
                                                    <tr><th>Admission Date:</th><td id="viewAdmissionDate">—</td></tr>
                                                    <tr><th>Class:</th><td><span class="badge bg-info" id="viewCurrentClass">—</span></td></tr>
                                                    <tr><th>Arm:</th><td id="viewArm">—</td></tr>
                                                    <tr><th>Category:</th><td><span class="badge bg-secondary" id="viewStudentCategory">—</span></td></tr>
                                                    <tr><th>Status:</th><td id="viewStudentStatus">—</td></tr>
                                                    <tr><th>School House:</th><td id="viewSchoolHouse">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-calendar-alt me-2 text-success"></i>Current Term</h6></div>
                                            <div class="info-card-body">
                                                <div id="currentTermAlert" class="mb-3"></div>
                                                <table class="table table-borderless table-sm">
                                                    <tr><th>Term:</th><td id="viewCurrentTerm">—</td></tr>
                                                    <tr><th>Session:</th><td id="viewCurrentSession">—</td></tr>
                                                    <tr><th>Term Status:</th><td id="viewCurrentTermStatus">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-school me-2 text-success"></i>Previous School</h6></div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm">
                                                    <tr><th>Last School:</th><td id="viewLastSchool">—</td></tr>
                                                    <tr><th>Last Class:</th><td id="viewLastClass">—</td></tr>
                                                    <tr><th>Reason:</th><td id="viewReasonForLeaving">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Family -->
                            <div class="tab-pane fade" id="vFamily">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-user-tie me-2 text-primary"></i>Father's Information <span class="badge bg-primary ms-1" id="fatherStatusBadge"></span></h6></div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm">
                                                    <tr><th>Name:</th><td class="fw-semibold" id="viewFatherFullName">—</td></tr>
                                                    <tr><th>Phone:</th><td>
                                                        <span id="viewFatherPhone">—</span>
                                                        <a href="javascript:void(0)" onclick="callNumber('viewFatherPhone')" class="ms-2 text-success" title="Call"><i class="fas fa-phone-alt"></i></a>
                                                    </td></tr>
                                                    <tr><th>Occupation:</th><td id="viewFatherOccupation">—</td></tr>
                                                    <tr><th>City:</th><td id="viewFatherCityState">—</td></tr>
                                                    <tr><th>Email:</th><td id="viewFatherEmail">—</td></tr>
                                                    <tr><th>Address:</th><td id="viewFatherAddress">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-user-tie me-2 text-danger"></i>Mother's Information <span class="badge bg-danger ms-1" id="motherStatusBadge"></span></h6></div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm">
                                                    <tr><th>Name:</th><td class="fw-semibold" id="viewMotherFullName">—</td></tr>
                                                    <tr><th>Phone:</th><td>
                                                        <span id="viewMotherPhone">—</span>
                                                        <a href="javascript:void(0)" onclick="callNumber('viewMotherPhone')" class="ms-2 text-success" title="Call"><i class="fas fa-phone-alt"></i></a>
                                                    </td></tr>
                                                    <tr><th>Occupation:</th><td id="viewMotherOccupation">—</td></tr>
                                                    <tr><th>Email:</th><td id="viewMotherEmail">—</td></tr>
                                                    <tr><th>Address:</th><td id="viewMotherAddress">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-user-shield me-2 text-warning"></i>Emergency Contact</h6></div>
                                            <div class="info-card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <table class="table table-borderless table-sm">
                                                            <tr><th>Parent Email:</th><td id="viewParentEmail">—</td></tr>
                                                            <tr><th>Parent Address:</th><td id="viewParentAddress">—</td></tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Additional -->
                            <div class="tab-pane fade" id="vAdditional">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <div class="info-card-header"><h6><i class="fas fa-notes-medical me-2 text-info"></i>Medical & Personal</h6></div>
                                            <div class="info-card-body">
                                                <table class="table table-borderless table-sm">
                                                    <tr><th>Blood Group:</th><td id="viewBloodGroupAdditional">—</td></tr>
                                                    <tr><th>NIN Number:</th><td id="viewNIN">—</td></tr>
                                                    <tr><th>Mother Tongue:</th><td id="viewMotherTongue">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Term History -->
                            <div class="tab-pane fade" id="vTerms">
                                <div class="info-card">
                                    <div class="info-card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Term Registration History</h6>
                                        <button class="btn btn-sm btn-outline-primary rounded-3" onclick="refreshTermHistory()"><i class="fas fa-sync-alt me-1"></i>Refresh</button>
                                    </div>
                                    <div class="info-card-body">
                                        <div id="termHistoryLoading" class="text-center py-4">
                                            <div class="spin-ring"></div>
                                            <p class="mt-2 text-muted">Loading term history…</p>
                                        </div>
                                        <div id="termHistoryContent" style="display:none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Close</button>
                        <button type="button" class="btn btn-primary rounded-3" onclick="editStudentFromView()"><i class="fas fa-edit me-1"></i>Edit Student</button>
                        <button type="button" class="btn btn-success rounded-3" onclick="printStudentProfile()"><i class="fas fa-print me-1"></i>Print</button>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /page-content -->
</div><!-- /main-content -->

<!-- ================================================================
     SCRIPTS
     ================================================================ -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
(function () {
    'use strict';

    // ================================================================
    // CONFIGURATION & STATE
    // ================================================================
    const CONFIG = {
        DEFAULT_PER_PAGE: 25,
        PER_PAGE_OPTIONS: [25, 50, 100, 250],
        SEARCH_DEBOUNCE: 500,
        ENABLE_LOG: true
    };

    const AppState = {
        pagination:  { currentPage: 1, perPage: CONFIG.DEFAULT_PER_PAGE, total: 0, lastPage: 1, from: 0, to: 0, data: [] },
        filters:     { search: '', class: 'all', status: 'all', gender: 'all', session: 'all', term: 'all' },
        ui:          { currentView: 'table', isLoading: false, selectedStudents: new Set() },
        cache:       { students: new Map() },
        bulkStatusFilters: null,
        termFilters:       null
    };

    // ================================================================
    // NIGERIAN STATES / LGAS
    // ================================================================
    const NIGERIAN_STATES = [
        { name: "Abia", lgas: ["Aba North","Aba South","Arochukwu","Bende","Ikwuano","Isiala Ngwa North","Isiala Ngwa South","Isuikwuato","Obi Ngwa","Ohafia","Osisioma","Ugwunagbo","Ukwa East","Ukwa West","Umuahia North","Umuahia South","Umu Nneochi"] },
        { name: "Adamawa", lgas: ["Demsa","Fufure","Ganye","Gayuk","Gombi","Grie","Hong","Jada","Lamurde","Madagali","Maiha","Mayo Belwa","Michika","Mubi North","Mubi South","Numan","Shelleng","Song","Toungo","Yola North","Yola South"] },
        { name: "Akwa Ibom", lgas: ["Abak","Eastern Obolo","Eket","Esit Eket","Essien Udim","Etim Ekpo","Etinan","Ibeno","Ibesikpo Asutan","Ibiono-Ibom","Ika","Ikono","Ikot Abasi","Ikot Ekpene","Ini","Itu","Mbo","Mkpat-Enin","Nsit-Atai","Nsit-Ibom","Nsit-Ubium","Obot Akara","Okobo","Onna","Oron","Oruk Anam","Udung-Uko","Ukanafun","Uruan","Urue-Offong/Oruko","Uyo"] },
        { name: "Anambra", lgas: ["Aguata","Anambra East","Anambra West","Anaocha","Awka North","Awka South","Ayamelum","Dunukofia","Ekwusigo","Idemili North","Idemili South","Ihiala","Njikoka","Nnewi North","Nnewi South","Ogbaru","Onitsha North","Onitsha South","Orumba North","Orumba South","Oyi"] },
        { name: "Bauchi", lgas: ["Alkaleri","Bauchi","Bogoro","Damban","Darazo","Dass","Gamawa","Ganjuwa","Giade","Itas/Gadau","Jama'are","Katagum","Kirfi","Misau","Ningi","Shira","Tafawa Balewa","Toro","Warji","Zaki"] },
        { name: "Bayelsa", lgas: ["Brass","Ekeremor","Kolokuma/Opokuma","Nembe","Ogbia","Sagbama","Southern Ijaw","Yenagoa"] },
        { name: "Benue", lgas: ["Ado","Agatu","Apa","Buruku","Gboko","Guma","Gwer East","Gwer West","Katsina-Ala","Konshisha","Kwande","Logo","Makurdi","Obi","Ogbadibo","Ohimini","Oju","Okpokwu","Oturkpo","Tarka","Ukum","Ushongo","Vandeikya"] },
        { name: "Cross River", lgas: ["Abi","Akamkpa","Akpabuyo","Bakassi","Bekwarra","Biase","Boki","Calabar Municipal","Calabar South","Etung","Ikom","Obanliku","Obubra","Obudu","Odukpani","Ogoja","Yakuur","Yala"] },
        { name: "Delta", lgas: ["Aniocha North","Aniocha South","Bomadi","Burutu","Ethiope East","Ethiope West","Ika North East","Ika South","Isoko North","Isoko South","Ndokwa East","Ndokwa West","Okpe","Oshimili North","Oshimili South","Patani","Sapele","Udu","Ughelli North","Ughelli South","Ukwuani","Uvwie","Warri North","Warri South","Warri South West"] },
        { name: "Ebonyi", lgas: ["Abakaliki","Afikpo North","Afikpo South","Ebonyi","Ezza North","Ezza South","Ikwo","Ishielu","Ivo","Izzi","Ohaozara","Ohaukwu","Onicha"] },
        { name: "Edo", lgas: ["Akoko-Edo","Egor","Esan Central","Esan North-East","Esan South-East","Esan West","Etsako Central","Etsako East","Etsako West","Igueben","Ikpoba Okha","Orhionmwon","Oredo","Ovia North-East","Ovia South-West","Owan East","Owan West","Uhunmwonde"] },
        { name: "Ekiti", lgas: ["Ado Ekiti","Efon","Ekiti East","Ekiti South-West","Ekiti West","Emure","Gbonyin","Ido Osi","Ijero","Ikere","Ilejemeje","Irepodun/Ifelodun","Ise/Orun","Moba","Oye"] },
        { name: "Enugu", lgas: ["Aninri","Awgu","Enugu East","Enugu North","Enugu South","Ezeagu","Igbo Etiti","Igbo Eze North","Igbo Eze South","Isi Uzo","Nkanu East","Nkanu West","Nsukka","Oji River","Udenu","Udi","Uzo Uwani"] },
        { name: "FCT", lgas: ["Abaji","Bwari","Gwagwalada","Kuje","Kwali","Municipal Area Council"] },
        { name: "Gombe", lgas: ["Akko","Balanga","Billiri","Dukku","Funakaye","Gombe","Kaltungo","Kwami","Nafada","Shongom","Yamaltu/Deba"] },
        { name: "Imo", lgas: ["Aboh Mbaise","Ahiazu Mbaise","Ehime Mbano","Ezinihitte","Ideato North","Ideato South","Ihitte/Uboma","Ikeduru","Isiala Mbano","Isu","Mbaitoli","Ngor Okpala","Njaba","Nkwerre","Nwangele","Obowo","Oguta","Ohaji/Egbema","Okigwe","Orlu","Orsu","Oru East","Oru West","Owerri Municipal","Owerri North","Owerri West","Unuimo"] },
        { name: "Jigawa", lgas: ["Auyo","Babura","Biriniwa","Birnin Kudu","Buji","Dutse","Gagarawa","Garki","Gumel","Guri","Gwaram","Gwiwa","Hadejia","Jahun","Kafin Hausa","Kazaure","Kiri Kasama","Kiyawa","Kaugama","Maigatari","Malam Madori","Miga","Ringim","Roni","Sule Tankarkar","Taura","Yankwashi"] },
        { name: "Kaduna", lgas: ["Birnin Gwari","Chikun","Giwa","Igabi","Ikara","Jaba","Jema'a","Kachia","Kaduna North","Kaduna South","Kagarko","Kajuru","Kaura","Kauru","Kubau","Kudan","Lere","Makarfi","Sabon Gari","Sanga","Soba","Zangon Kataf","Zaria"] },
        { name: "Kano", lgas: ["Ajingi","Albasu","Bagwai","Bebeji","Bichi","Bunkure","Dala","Dambatta","Dawakin Kudu","Dawakin Tofa","Doguwa","Fagge","Gabasawa","Garko","Garun Mallam","Gaya","Gezawa","Gwale","Gwarzo","Kabo","Kano Municipal","Karaye","Kibiya","Kiru","Kumbotso","Kunchi","Kura","Madobi","Makoda","Minjibir","Nasarawa","Rano","Rimin Gado","Rogo","Shanono","Sumaila","Takai","Tarauni","Tofa","Tsanyawa","Tudun Wada","Ungogo","Warawa","Wudil"] },
        { name: "Katsina", lgas: ["Bakori","Batagarawa","Batsari","Baure","Bindawa","Charanchi","Dan Musa","Dandume","Danja","Daura","Dutsi","Dutsin Ma","Faskari","Funtua","Ingawa","Jibia","Kafur","Kaita","Kankara","Kankia","Katsina","Kurfi","Kusada","Mai'Adua","Malumfashi","Mani","Mashi","Matazu","Musawa","Rimi","Sabuwa","Safana","Sandamu","Zango"] },
        { name: "Kebbi", lgas: ["Aleiro","Arewa Dandi","Argungu","Augie","Bagudo","Birnin Kebbi","Bunza","Dandi","Fakai","Gwandu","Jega","Kalgo","Koko/Besse","Maiyama","Ngaski","Sakaba","Shanga","Suru","Danko/Wasagu","Yauri","Zuru"] },
        { name: "Kogi", lgas: ["Adavi","Ajaokuta","Ankpa","Bassa","Dekina","Ibaji","Idah","Igalamela Odolu","Ijumu","Kabba/Bunu","Kogi","Lokoja","Mopa Muro","Ofu","Ogori/Magongo","Okehi","Okene","Olamaboro","Omala","Yagba East","Yagba West"] },
        { name: "Kwara", lgas: ["Asa","Baruten","Edu","Ekiti","Ifelodun","Ilorin East","Ilorin South","Ilorin West","Irepodun","Isin","Kaiama","Moro","Offa","Oke Ero","Oyun","Pategi"] },
        { name: "Lagos", lgas: ["Agege","Ajeromi-Ifelodun","Alimosho","Amuwo-Odofin","Apapa","Badagry","Epe","Eti Osa","Ibeju-Lekki","Ifako-Ijaiye","Ikeja","Ikorodu","Kosofe","Lagos Island","Lagos Mainland","Mushin","Ojo","Oshodi-Isolo","Shomolu","Surulere"] },
        { name: "Nasarawa", lgas: ["Akwanga","Awe","Doma","Karu","Keana","Keffi","Kokona","Lafia","Nasarawa","Nasarawa Egon","Obi","Toto","Wamba"] },
        { name: "Niger", lgas: ["Agaie","Agwara","Bida","Borgu","Bosso","Chanchaga","Edati","Gbako","Gurara","Katcha","Kontagora","Lapai","Lavun","Magama","Mariga","Mashegu","Mokwa","Moya","Paikoro","Rafi","Rijau","Shiroro","Suleja","Tafa","Wushishi"] },
        { name: "Ogun", lgas: ["Abeokuta North","Abeokuta South","Ado-Odo/Ota","Egbado North","Egbado South","Ewekoro","Ifo","Ijebu East","Ijebu North","Ijebu North East","Ijebu Ode","Ikenne","Imeko Afon","Ipokia","Obafemi Owode","Odeda","Odogbolu","Ogun Waterside","Remo North","Shagamu"] },
        { name: "Ondo", lgas: ["Akoko North-East","Akoko North-West","Akoko South-East","Akoko South-West","Akure North","Akure South","Ese Odo","Idanre","Ifedore","Ilaje","Ile Oluji/Okeigbo","Irele","Odigbo","Okitipupa","Ondo East","Ondo West","Ose","Owo"] },
        { name: "Osun", lgas: ["Aiyedade","Aiyedire","Atakunmosa East","Atakunmosa West","Boluwaduro","Boripe","Ede North","Ede South","Egbedore","Ejigbo","Ife Central","Ife East","Ife North","Ife South","Ifedayo","Ifelodun","Ila","Ilesa East","Ilesa West","Irepodun","Irewole","Isokan","Iwo","Obokun","Odo Otin","Ola Oluwa","Olorunda","Oriade","Orolu","Osogbo"] },
        { name: "Oyo", lgas: ["Afijio","Akinyele","Atiba","Atisbo","Egbeda","Ibadan North","Ibadan North-East","Ibadan North-West","Ibadan South-East","Ibadan South-West","Ibarapa Central","Ibarapa East","Ibarapa North","Ido","Irepo","Iseyin","Itesiwaju","Iwajowa","Kajola","Lagelu","Ogbomosho North","Ogbomosho South","Ogo Oluwa","Olorunsogo","Oluyole","Ona Ara","Orelope","Ori Ire","Oyo East","Oyo West","Saki East","Saki West","Surulere"] },
        { name: "Plateau", lgas: ["Bokkos","Barkin Ladi","Bassa","Jos East","Jos North","Jos South","Kanam","Kanke","Langtang North","Langtang South","Mangu","Mikang","Pankshin","Qua'an Pan","Riyom","Shendam","Wase"] },
        { name: "Rivers", lgas: ["Abua/Odual","Ahoada East","Ahoada West","Akuku-Toru","Andoni","Asari-Toru","Bonny","Degema","Eleme","Emohua","Etche","Gokana","Ikwerre","Khana","Obio/Akpor","Ogba/Egbema/Ndoni","Ogu/Bolo","Okrika","Omuma","Opobo/Nkoro","Oyigbo","Port Harcourt","Tai"] },
        { name: "Sokoto", lgas: ["Binji","Bodinga","Dange Shuni","Gada","Goronyo","Gudu","Gwadabawa","Illela","Isa","Kebbe","Kware","Rabah","Sabon Birni","Shagari","Silame","Sokoto North","Sokoto South","Tambuwal","Tangaza","Tureta","Wamako","Wurno","Yabo"] },
        { name: "Taraba", lgas: ["Ardo Kola","Bali","Donga","Gashaka","Gassol","Ibi","Jalingo","Karim Lamido","Kumi","Lau","Sardauna","Takum","Ussa","Wukari","Yorro","Zing"] },
        { name: "Yobe", lgas: ["Bade","Bursari","Damaturu","Fika","Fune","Geidam","Gujba","Gulani","Jakusko","Karasuwa","Machina","Nangere","Nguru","Potiskum","Tarmuwa","Yunusari","Yusufari"] },
        { name: "Zamfara", lgas: ["Anka","Bakura","Birnin Magaji/Kiyaw","Bukkuyum","Bungudu","Gummi","Gusau","Kaura Namoda","Maradun","Maru","Shinkafi","Talata Mafara","Chafe","Zurmi"] }
    ];

    // ================================================================
    // UTILS
    // ================================================================
    const U = {
        log: (m, d) => CONFIG.ENABLE_LOG && (d ? console.log(m, d) : console.log(m)),
        esc: t => !t ? '' : t.toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])),
        date: (v, fmt='short') => {
            if (!v) return 'N/A';
            try {
                const d = new Date(v); if (isNaN(d.getTime())) return 'N/A';
                return d.toLocaleDateString('en-US', fmt === 'long' ? {year:'numeric',month:'long',day:'numeric'} : {year:'numeric',month:'short',day:'numeric'});
            } catch { return 'N/A'; }
        },
        age: dob => {
            if (!dob) return 'N/A';
            try {
                const d = new Date(dob); if (isNaN(d.getTime())) return 'N/A';
                const t = new Date(); let a = t.getFullYear() - d.getFullYear();
                if (t.getMonth() - d.getMonth() < 0 || (t.getMonth() === d.getMonth() && t.getDate() < d.getDate())) a--;
                return a;
            } catch { return 'N/A'; }
        },
        initials: (f, l) => ((f||'').charAt(0) + (l||'').charAt(0)).toUpperCase() || 'ST',
        debounce: (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; },
        showLoading: () => {
            ['tableView','cardView','emptyState'].forEach(id => document.getElementById(id)?.classList.add('d-none'));
            document.getElementById('loadingState')?.classList.remove('d-none');
            AppState.ui.isLoading = true;
        },
        hideLoading: () => {
            document.getElementById('loadingState')?.classList.add('d-none');
            const hasData = AppState.pagination.data?.length > 0;
            if (hasData) {
                const tv = document.getElementById('tableView'), cv = document.getElementById('cardView');
                if (AppState.ui.currentView === 'table') tv?.classList.remove('d-none');
                else cv?.classList.remove('d-none');
                document.getElementById('emptyState')?.classList.add('d-none');
            } else {
                ['tableView','cardView'].forEach(id => document.getElementById(id)?.classList.add('d-none'));
                document.getElementById('emptyState')?.classList.remove('d-none');
            }
            AppState.ui.isLoading = false;
        },
        err:  (msg, title='Error')   => Swal.fire({title,text:msg,icon:'error',confirmButtonText:'OK',customClass:{confirmButton:'btn btn-primary'}}),
        ok:   (msg, title='Success') => Swal.fire({title,text:msg,icon:'success',timer:2000,timerProgressBar:true,showConfirmButton:false}),
        confirm: async (title, text, confirmText='Yes') => {
            const r = await Swal.fire({title,text,icon:'warning',showCancelButton:true,confirmButtonText:confirmText,cancelButtonText:'Cancel',customClass:{confirmButton:'btn btn-danger',cancelButton:'btn btn-light'}});
            return r.isConfirmed;
        },
        csrf: () => {
            const t = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (t) { axios.defaults.headers.common['X-CSRF-TOKEN'] = t; axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'; }
            return !!t;
        },
        setText: (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; },
        /* zoom helper */
        openZoom: (imgSrc, name, initials, detail) => {
            const zImg  = document.getElementById('zoomedStudentImg');
            const zName = document.getElementById('zoomedStudentName');
            const zDet  = document.getElementById('zoomedStudentDetail');
            if (imgSrc) {
                zImg.src = imgSrc; zImg.style.display = 'block';
            } else {
                // Draw initials on canvas
                const cv = document.createElement('canvas'); cv.width = 400; cv.height = 400;
                const ctx = cv.getContext('2d');
                const g = ctx.createLinearGradient(0,0,400,400); g.addColorStop(0,'#667eea'); g.addColorStop(1,'#764ba2');
                ctx.fillStyle = g; ctx.beginPath(); ctx.arc(200,200,200,0,2*Math.PI); ctx.fill();
                ctx.fillStyle = '#fff'; ctx.font = 'bold 160px Arial'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                ctx.fillText((initials||'ST').substring(0,2), 200, 200);
                zImg.src = cv.toDataURL(); zImg.style.display = 'block';
            }
            if (zName) zName.textContent = name || '';
            if (zDet)  zDet.innerHTML = detail || '';
            const m = new bootstrap.Modal(document.getElementById('imageZoomModal'));
            m.show();
        }
    };

    // ================================================================
    // API SERVICE
    // ================================================================
    const API = {
        async get(url, params) {
            U.csrf();
            const q = new URLSearchParams(params).toString();
            return (await axios.get(`${url}?${q}`)).data;
        },
        async post(url, data) { U.csrf(); return (await axios.post(url, data)).data; },
        async del(url)        { U.csrf(); return (await axios.delete(url)).data; },

        students: (page, perPage, filters) => {
            const p = { page, per_page: perPage };
            if (filters.search && filters.search.trim()) p.search = filters.search.trim();
            if (filters.class   !== 'all') p.class_id   = filters.class;
            if (filters.status  !== 'all') p.status      = filters.status;
            if (filters.gender  !== 'all') p.gender      = filters.gender;
            if (filters.session !== 'all') p.session_id  = filters.session;
            return API.get('/students/optimized', p);
        },
        student:  id => API.get(`/student/${id}/edit`, {}),
        delete:   id => API.del(`/student/${id}/destroy`),
        delMany:  ids => API.post('/students/destroy-multiple', {ids}),
        activeTerm: sid => API.get(`/student-current-term/student/${sid}/active`, {}),
        bulkTerm:   d  => API.post('/student-current-term/bulk-update', d),
        byClassSession: (cid, sid) => API.get('/students/by-class-session', {class_id:cid,session_id:sid}),
        bulkStatus: d  => API.post('/students/bulk-update-status', d),
        inTerm:     p  => API.get('/students-in-term', p),
        rmTerm:     id => API.post('/students/remove-from-term', {registration_id:id}),
        rmTermMany: ids=> API.post('/students/bulk-remove-from-term', {registration_ids:ids})
    };

    // ================================================================
    // STATE / LGA MANAGER
    // ================================================================
    const SLM = {
        populate: (selectId, lgaId) => {
            const sel = document.getElementById(selectId);
            if (!sel) return;
            sel.innerHTML = '<option value="">Select State</option>';
            NIGERIAN_STATES.forEach(s => { const o = document.createElement('option'); o.value = s.name; o.textContent = s.name; sel.appendChild(o); });
            const lgaSel = document.getElementById(lgaId);
            if (lgaSel) { lgaSel.innerHTML = '<option value="">Select LGA</option>'; lgaSel.disabled = true; }
            sel.onchange = () => SLM.updateLGA(sel.value, lgaId);
        },
        updateLGA: (stateName, lgaId) => {
            const lgaSel = document.getElementById(lgaId);
            if (!lgaSel) return;
            lgaSel.innerHTML = '<option value="">Select LGA</option>';
            const state = NIGERIAN_STATES.find(s => s.name === stateName);
            if (state) { lgaSel.disabled = false; state.lgas.forEach(l => { const o = document.createElement('option'); o.value = l; o.textContent = l; lgaSel.appendChild(o); }); }
            else lgaSel.disabled = true;
        },
        set: (stateId, lgaId, stateVal, lgaVal) => {
            SLM.populate(stateId, lgaId);
            if (stateVal) {
                const sel = document.getElementById(stateId);
                if (sel) { sel.value = stateVal; SLM.updateLGA(stateVal, lgaId); }
                setTimeout(() => { const ls = document.getElementById(lgaId); if (ls && lgaVal) ls.value = lgaVal; }, 350);
            }
        }
    };

    // ================================================================
    // RENDER MANAGER — TABLE & CARDS (othername included)
    // ================================================================
    const Render = {
        table: students => {
            const tbody = document.getElementById('studentTableBody');
            if (!tbody) return;
            if (!students?.length) { tbody.innerHTML = ''; return; }
            const frag = document.createDocumentFragment();
            students.forEach(s => {
                const tr = document.createElement('tr');
                tr.dataset.id = s.id;
                const init    = U.initials(s.firstname, s.lastname);
                const fullName= [s.lastname, s.firstname, s.othername].filter(Boolean).join(' ');
                const imgHtml = s.picture && s.picture !== 'unnamed.jpg'
                    ? `<img src="/storage/images/student_avatars/${s.picture}" class="sm-avatar" alt="${U.esc(fullName)}"
                           data-zoom-src="/storage/images/student_avatars/${s.picture}"
                           data-zoom-name="${U.esc(fullName)}" data-zoom-init="${U.esc(init)}"
                           data-zoom-detail="${U.esc(s.admissionNo||'')} &bull; ${U.esc(s.schoolclass||'')} ${U.esc(s.arm||'')} &bull; ${U.esc(s.gender||'')}
                           " onclick="handleAvatarZoom(this)">`
                    : `<div class="sm-avatar-init" data-zoom-src="" data-zoom-name="${U.esc(fullName)}" data-zoom-init="${U.esc(init)}"
                            data-zoom-detail="${U.esc(s.admissionNo||'')} &bull; ${U.esc(s.schoolclass||'')} ${U.esc(s.arm||'')} &bull; ${U.esc(s.gender||'')}"
                            onclick="handleAvatarZoom(this)">${U.esc(init)}</div>`;
                const actBadge = s.student_status === 'Active'
                    ? `<span class="sm-badge badge-active"><i class="fas fa-circle" style="font-size:7px"></i>${U.esc(s.student_status)}</span>`
                    : `<span class="sm-badge badge-inactive"><i class="fas fa-circle" style="font-size:7px"></i>${U.esc(s.student_status||'—')}</span>`;
                const typeBadge = s.statusId == 2
                    ? `<span class="sm-badge badge-new"><i class="fas fa-star" style="font-size:9px"></i>New</span>`
                    : `<span class="sm-badge badge-old"><i class="fas fa-history" style="font-size:9px"></i>Old</span>`;

                tr.innerHTML = `
                    <td><div class="form-check"><input class="form-check-input student-checkbox" type="checkbox" value="${s.id}"></div></td>
                    <td>${imgHtml}</td>
                    <td>
                        <div class="stu-name">${U.esc(fullName)}</div>
                        <div class="stu-meta">
                            <span class="stu-chip"><i class="fas fa-id-card" style="font-size:9px"></i>${U.esc(s.admissionNo||'N/A')}</span>
                            ${typeBadge}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:13px;">${U.esc(s.schoolclass||'')} ${U.esc(s.arm||'')}</div>
                        <small class="text-muted">${U.esc(s.student_category||'')}</small>
                    </td>
                    <td>${actBadge}</td>
                    <td>
                        <span style="display:flex;align-items:center;gap:5px;font-size:13px;">
                            <i class="fas fa-${s.gender==='Male'?'mars text-primary':'venus text-danger'}"></i>
                            ${U.esc(s.gender||'N/A')}
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--sm-muted);">${U.date(s.created_at,'short')}</td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="act-btn act-view view-student-btn" data-student-id="${s.id}" title="View"><i class="fas fa-eye"></i></button>
                            <button class="act-btn act-edit edit-student-btn"  data-student-id="${s.id}" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="act-btn act-delete delete-student-btn" data-student-id="${s.id}" title="Delete"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </td>`;
                frag.appendChild(tr);
            });
            tbody.innerHTML = ''; tbody.appendChild(frag);
            Render.updateCheckAll();
        },

        cards: students => {
            const container = document.getElementById('studentsCardsContainer');
            if (!container) return;
            if (!students?.length) { container.innerHTML = ''; return; }
            const frag = document.createDocumentFragment();
            students.forEach(s => {
                const col = document.createElement('div');
                col.className = 'col-xl-3 col-lg-4 col-md-6';
                const init = U.initials(s.firstname, s.lastname);
                const fullName = [s.lastname, s.firstname, s.othername].filter(Boolean).join(' ');
                const avatarHtml = s.picture && s.picture !== 'unnamed.jpg'
                    ? `<div class="stu-card-avatar" onclick="handleAvatarZoom(this)"
                             data-zoom-src="/storage/images/student_avatars/${s.picture}"
                             data-zoom-name="${U.esc(fullName)}" data-zoom-init="${U.esc(init)}"
                             data-zoom-detail="${U.esc(s.admissionNo||'')} &bull; ${U.esc(s.schoolclass||'')} ${U.esc(s.arm||'')}">
                           <img src="/storage/images/student_avatars/${s.picture}" alt="${U.esc(fullName)}">
                       </div>`
                    : `<div class="stu-card-avatar" onclick="handleAvatarZoom(this)"
                             data-zoom-src="" data-zoom-name="${U.esc(fullName)}" data-zoom-init="${U.esc(init)}"
                             data-zoom-detail="${U.esc(s.admissionNo||'')} &bull; ${U.esc(s.schoolclass||'')} ${U.esc(s.arm||'')}">
                           <div class="stu-card-avatar-init">${U.esc(init)}</div>
                       </div>`;
                const actBadge = s.student_status === 'Active'
                    ? `<span class="sm-badge badge-active" style="font-size:11px;"><i class="fas fa-circle" style="font-size:7px"></i>Active</span>`
                    : `<span class="sm-badge badge-inactive" style="font-size:11px;"><i class="fas fa-circle" style="font-size:7px"></i>Inactive</span>`;
                const typeBadge = s.statusId == 2
                    ? `<span class="sm-badge badge-new ms-1" style="font-size:11px;">New</span>`
                    : `<span class="sm-badge badge-old ms-1" style="font-size:11px;">Old</span>`;

                col.innerHTML = `
                    <div class="stu-card mb-3" data-id="${s.id}">
                        <div class="stu-card-checkbox">
                            <input class="form-check-input student-checkbox" type="checkbox" value="${s.id}" style="width:18px;height:18px;border:2px solid rgba(255,255,255,.8);">
                        </div>
                        ${avatarHtml}
                        <div class="stu-card-header" style="min-height:100px;">
                            <div class="stu-card-name">${U.esc(fullName)}</div>
                            <span class="stu-card-adm">${U.esc(s.admissionNo||'N/A')}</span>
                        </div>
                        <div class="stu-card-body">
                            <div class="mb-2">${actBadge}${typeBadge}</div>
                            <div class="stu-card-grid">
                                <div>
                                    <div class="stu-card-info-label">Class</div>
                                    <div class="stu-card-info-val">${U.esc(s.schoolclass||'—')} ${U.esc(s.arm||'')}</div>
                                </div>
                                <div>
                                    <div class="stu-card-info-label">Gender</div>
                                    <div class="stu-card-info-val">${U.esc(s.gender||'—')}</div>
                                </div>
                                <div>
                                    <div class="stu-card-info-label">Age</div>
                                    <div class="stu-card-info-val">${s.age||U.age(s.dateofbirth)||'—'}</div>
                                </div>
                                <div>
                                    <div class="stu-card-info-label">Registered</div>
                                    <div class="stu-card-info-val">${U.date(s.created_at,'short')}</div>
                                </div>
                            </div>
                            <div class="stu-card-actions">
                                <button class="stu-card-view view-student-btn"   data-student-id="${s.id}"><i class="fas fa-eye"></i> View</button>
                                <button class="stu-card-edit edit-student-btn"   data-student-id="${s.id}"><i class="fas fa-edit"></i> Edit</button>
                                <button class="stu-card-delete delete-student-btn" data-student-id="${s.id}"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>
                    </div>`;
                frag.appendChild(col);
            });
            container.innerHTML = ''; container.appendChild(frag);
            Render.updateCheckAll();
        },

        updateCheckAll: () => {
            const all = document.querySelectorAll('.student-checkbox').length;
            const ch  = document.querySelectorAll('.student-checkbox:checked').length;
            ['checkAll','checkAllTable'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.checked = all > 0 && all === ch; el.indeterminate = ch > 0 && ch < all; }
            });
            const bulkBtn = document.getElementById('bulkActionsDropdown');
            if (bulkBtn) {
                bulkBtn.disabled = ch === 0;
                bulkBtn.innerHTML = ch > 0 ? `<i class="fas fa-cog me-1"></i>Actions (${ch})` : '<i class="fas fa-cog me-1"></i>Actions';
            }
        },

        toggleView: type => {
            AppState.ui.currentView = type;
            const tv = document.getElementById('tableView'), cv = document.getElementById('cardView');
            const tvb = document.getElementById('tableViewBtn'), cvb = document.getElementById('cardViewBtn');
            if (type === 'table') {
                tv?.classList.remove('d-none'); cv?.classList.add('d-none');
                tvb?.classList.add('active');   cvb?.classList.remove('active');
                if (AppState.pagination.data.length) Render.table(AppState.pagination.data);
            } else {
                tv?.classList.add('d-none'); cv?.classList.remove('d-none');
                tvb?.classList.remove('active'); cvb?.classList.add('active');
                if (AppState.pagination.data.length) Render.cards(AppState.pagination.data);
            }
        }
    };

    // ================================================================
    // STUDENT MANAGER
    // ================================================================
    const SM = {
        async fetch() {
            U.showLoading();
            try {
                const resp = await API.students(AppState.pagination.currentPage, AppState.pagination.perPage, AppState.filters);
                if (!resp.success) throw new Error(resp.message || 'Failed');
                const pd = resp.data;
                AppState.pagination = { currentPage: pd.current_page, lastPage: pd.last_page, total: pd.total, from: pd.from, to: pd.to, data: pd.data };
                AppState.ui.currentView === 'table' ? Render.table(pd.data) : Render.cards(pd.data);
                Pagination.update(pd);
                Selection.clearAll();
                pd.data.forEach(s => AppState.cache.students.set(String(s.id), s));
            } catch (e) {
                U.log('Fetch error', e, 'error');
                U.err('Failed to load students. Please try again.');
            } finally { U.hideLoading(); }
        },

        async view(id) {
            try {
                Swal.fire({title:'Loading…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
                let s = AppState.cache.students.get(String(id));
                if (!s) { const r = await API.student(id); if (r.success) { s = r.student; AppState.cache.students.set(String(id), s); } }
                Swal.close();
                if (s) {
                    ViewModal.populate(s);
                    new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
                } else U.err('Student not found.');
            } catch (e) { Swal.close(); U.err('Failed to load student.'); }
        },

        async edit(id) {
            try {
                Swal.fire({title:'Loading…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
                SLM.populate('editState','editLocal');
                const r = await API.student(id);
                Swal.close();
                if (!r.success || !r.student) throw new Error(r.message||'Not found');
                EditForm.populate(r.student);
                new bootstrap.Modal(document.getElementById('editStudentModal')).show();
            } catch(e) { Swal.close(); U.err('Failed to load student: ' + (e.message||'')); }
        },

        async delete(id) {
            if (!await U.confirm('Delete Student?', "You won't be able to revert this!", 'Yes, delete it!')) return;
            try { await API.delete(id); AppState.cache.students.delete(String(id)); await SM.fetch(); U.ok('Student deleted.'); }
            catch(e) { U.err('Failed to delete student.'); }
        },

        async deleteMany() {
            const ids = Selection.getIds();
            if (!ids.length) { U.err('Please select at least one student.', 'No Selection'); return; }
            if (!await U.confirm(`Delete ${ids.length} Students?`, 'This cannot be undone!', 'Yes, delete them!')) return;
            try { await API.delMany(ids); ids.forEach(id => AppState.cache.students.delete(String(id))); await SM.fetch(); U.ok(`${ids.length} student(s) deleted.`); Selection.clearAll(); }
            catch(e) { U.err('Failed to delete selected students.'); }
        }
    };

    // ================================================================
    // SELECTION MANAGER
    // ================================================================
    const Selection = {
        getIds: () => Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value),
        clearAll: () => {
            document.querySelectorAll('.student-checkbox').forEach(cb => { cb.checked = false; cb.closest('tr,div[data-id]')?.classList.remove('selected'); });
            AppState.ui.selectedStudents.clear();
            ['checkAll','checkAllTable'].forEach(id => { const el = document.getElementById(id); if (el) { el.checked = false; el.indeterminate = false; } });
            Render.updateCheckAll();
        },
        init: () => {
            ['checkAll','checkAllTable'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.onchange = e => {
                    document.querySelectorAll('.student-checkbox').forEach(cb => { cb.checked = e.target.checked; });
                    Render.updateCheckAll();
                };
            });
            document.addEventListener('change', e => {
                if (e.target.classList.contains('student-checkbox')) Render.updateCheckAll();
            });
        }
    };

    // ================================================================
    // PAGINATION
    // ================================================================
    const Pagination = {
        update: pd => {
            U.setText('showingCount', pd.from || 0);
            U.setText('toCount',      pd.to   || 0);
            U.setText('totalCount',   pd.total|| 0);
            U.setText('totalStudents',pd.total|| 0);

            const ul = document.getElementById('pagination');
            if (!ul) return;
            ul.querySelectorAll('.page-item:not(#prevPageLi):not(#nextPageLi)').forEach(el => el.remove());

            if (pd.last_page > 1) {
                const start = Math.max(1, pd.current_page - 2);
                const end   = Math.min(pd.last_page, pd.current_page + 2);
                const addItem = (n, label=n) => {
                    const li = document.createElement('li');
                    li.className = `page-item ${n === pd.current_page ? 'active' : ''}`;
                    const a = document.createElement('a'); a.className = 'page-link'; a.href = 'javascript:void(0);';
                    a.textContent = label;
                    a.onclick = () => { AppState.pagination.currentPage = n; SM.fetch(); };
                    li.appendChild(a);
                    ul.querySelector('#nextPageLi').before(li);
                };
                if (start > 1) { addItem(1); if (start > 2) { const li = document.createElement('li'); li.className='page-item disabled'; li.innerHTML='<span class="page-link">…</span>'; ul.querySelector('#nextPageLi').before(li); } }
                for (let i = start; i <= end; i++) addItem(i);
                if (end < pd.last_page) { if (end < pd.last_page-1) { const li = document.createElement('li'); li.className='page-item disabled'; li.innerHTML='<span class="page-link">…</span>'; ul.querySelector('#nextPageLi').before(li); } addItem(pd.last_page); }
            }
            const prev = document.getElementById('prevPage');
            const next = document.getElementById('nextPage');
            if (prev) { prev.classList.toggle('disabled', pd.current_page <= 1); prev.onclick = e => { e.preventDefault(); if (pd.current_page > 1) { AppState.pagination.currentPage--; SM.fetch(); } }; }
            if (next) { next.classList.toggle('disabled', pd.current_page >= pd.last_page); next.onclick = e => { e.preventDefault(); if (pd.current_page < pd.last_page) { AppState.pagination.currentPage++; SM.fetch(); } }; }
        }
    };

    // ================================================================
    // VIEW MODAL
    // ================================================================
    const ViewModal = {
        currentId: null,
        populate: s => {
            ViewModal.currentId = s.id;
            const full = [s.lastname, s.firstname, s.othername].filter(Boolean).join(' ');
            U.setText('viewFullName',       full||'—');
            U.setText('viewFullNameDetail', full||'—');
            U.setText('viewAdmissionNumber',s.admissionNo||'—');
            U.setText('viewAdmissionNo',    s.admissionNo||'—');
            U.setText('viewTitle',          s.title||'—');
            U.setText('viewDOB',            U.date(s.dateofbirth,'long'));
            U.setText('viewAge',            s.age||U.age(s.dateofbirth));
            U.setText('viewAgeDetail',      s.age||U.age(s.dateofbirth));
            U.setText('viewPlaceOfBirth',   s.placeofbirth||'—');
            U.setText('viewGenderDetail',   s.gender||'—');
            U.setText('viewGenderText',     s.gender||'—');
            U.setText('viewBloodGroupDetail',   s.blood_group||'—');
            U.setText('viewBloodGroupAdditional',s.blood_group||'—');
            U.setText('viewReligionDetail', s.religion||'—');
            U.setText('viewPhoneNumber',    s.phone_number||'—');
            U.setText('viewEmailAddress',   s.email||'—');
            U.setText('viewPermanentAddress',s.permanent_address||'—');
            U.setText('viewCity',           s.city||'—');
            U.setText('viewStateOrigin',    s.state||'—');
            U.setText('viewLGA',            s.local||'—');
            U.setText('viewNationality',    s.nationality||'—');
            U.setText('viewFutureAmbition', s.future_ambition||'—');
            U.setText('viewAdmissionDate',  U.date(s.admission_date||s.admissionDate,'long'));
            U.setText('viewAdmittedDate',   U.date(s.admission_date||s.admissionDate,'short'));
            const cls = `${s.schoolclass||''} ${s.arm||''}`.trim() || '—';
            U.setText('viewCurrentClass',   cls);
            U.setText('viewClassDisplay',   cls);
            U.setText('viewArm',            s.arm||'—');
            U.setText('viewStudentCategory',s.student_category||'—');
            const type = s.statusId==2 ? 'New Student' : s.statusId==1 ? 'Old Student' : '—';
            U.setText('viewStudentType',    type);
            U.setText('viewStudentStatus',  s.student_status||'—');
            U.setText('viewSchoolHouse',    s.school_house||'—');
            U.setText('viewLastSchool',     s.last_school||'—');
            U.setText('viewLastClass',      s.last_class||'—');
            U.setText('viewReasonForLeaving',s.reason_for_leaving||'—');
            U.setText('viewFatherFullName', s.father_name||'—');
            U.setText('viewFatherPhone',    s.father_phone||'—');
            U.setText('viewFatherOccupation',s.father_occupation||'—');
            U.setText('viewFatherCityState',s.father_city||'—');
            U.setText('viewFatherEmail',    s.parent_email||'—');
            U.setText('viewFatherAddress',  s.parent_address||'—');
            U.setText('viewMotherFullName', s.mother_name||'—');
            U.setText('viewMotherPhone',    s.mother_phone||'—');
            U.setText('viewMotherOccupation',s.mother_occupation||'—');
            U.setText('viewMotherEmail',    s.parent_email||'—');
            U.setText('viewMotherAddress',  s.parent_address||'—');
            U.setText('viewParentEmail',    s.parent_email||'—');
            U.setText('viewParentAddress',  s.parent_address||'—');
            U.setText('viewNIN',            s.nin_number||'—');
            U.setText('viewMotherTongue',   s.mother_tongue||'—');

            // Father/Mother status badges
            const fb = document.getElementById('fatherStatusBadge'); if (fb) { fb.textContent = s.father_name ? 'Available' : 'Not Provided'; fb.className = `badge ms-1 ${s.father_name ? 'bg-success' : 'bg-secondary'}`; }
            const mb = document.getElementById('motherStatusBadge'); if (mb) { mb.textContent = s.mother_name ? 'Available' : 'Not Provided'; mb.className = `badge ms-1 ${s.mother_name ? 'bg-success' : 'bg-secondary'}`; }

            // Type badge styling
            const tb = document.getElementById('viewStudentTypeBadge');
            if (tb) { if (s.statusId==2) { tb.style.background='#fef3c7'; tb.style.color='#92400e'; } else { tb.style.background='#ede9fe'; tb.style.color='#5b21b6'; } }

            // Status indicator
            const si = document.getElementById('studentStatusIndicator');
            if (si) si.className = `position-absolute bottom-0 end-0 rounded-circle border border-2 border-white ${s.student_status==='Active'?'bg-success':'bg-secondary'}`;
            si.style.cssText = 'width:18px;height:18px;';

            // Photo
            const photo = document.getElementById('viewStudentPhoto');
            if (photo) {
                const src = s.picture && s.picture !== 'unnamed.jpg' ? `/storage/images/student_avatars/${s.picture}` : null;
                photo.src = src || '{{ asset("theme/layouts/assets/media/avatars/blank.png") }}';
                photo.dataset.zoomSrc  = src || '';
                photo.dataset.zoomName = full;
                photo.dataset.zoomInit = U.initials(s.firstname, s.lastname);
                photo.dataset.zoomDetail = `${s.admissionNo||''} &bull; ${cls} &bull; ${s.gender||''}`;
            }

            ViewModal.fetchTerm(s.id);
            // Reset term history
            const thc = document.getElementById('termHistoryContent'); if (thc) { thc.style.display = 'none'; thc.innerHTML = ''; }
            const thl = document.getElementById('termHistoryLoading'); if (thl) thl.style.display = 'block';
        },

        fetchTerm: async id => {
            try {
                const r = await API.activeTerm(id);
                if (r.success && r.data) {
                    const d = r.data;
                    U.setText('viewCurrentTerm',   d.term?.term||'—');
                    U.setText('viewCurrentSession', d.session?.session||'—');
                    const status = document.getElementById('viewCurrentTermStatus');
                    if (status) status.innerHTML = d.is_current ? '<span class="badge bg-success">Active Term</span>' : '<span class="badge bg-warning text-dark">Registered</span>';
                    const alert = document.getElementById('currentTermAlert');
                    if (alert) alert.innerHTML = `<div class="alert alert-success border-0 rounded-3 py-2 small"><i class="fas fa-check-circle me-1"></i><strong>Enrolled:</strong> ${d.schoolClass?.schoolclass||''} ${d.schoolClass?.armRelation?.arm||''} &bull; ${d.term?.term||''} Term &bull; ${d.session?.session||''}</div>`;
                } else {
                    ['viewCurrentTerm','viewCurrentSession'].forEach(id => U.setText(id, '—'));
                    const status = document.getElementById('viewCurrentTermStatus'); if (status) status.innerHTML = '<span class="badge bg-secondary">Not Registered</span>';
                    const alert = document.getElementById('currentTermAlert'); if (alert) alert.innerHTML = '<div class="alert alert-warning border-0 rounded-3 py-2 small"><i class="fas fa-exclamation-triangle me-1"></i>No active term registration found.</div>';
                }
            } catch {}
        }
    };

    // ================================================================
    // EDIT FORM MANAGER
    // ================================================================
    const EditForm = {
        populate: s => {
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v||''; };
            const chk = (name, v) => { const el = document.querySelector(`input[name="${name}"][value="${v}"]`); if (el) el.checked = true; };

            document.getElementById('editStudentId').value = s.id||'';

            // Form action
            const form = document.getElementById('editStudentForm');
            if (form) { const base = form.dataset.baseAction; if (base) form.action = base.replace(':id', s.id); }

            set('editAdmissionNo',   s.admissionNo);
            set('editAdmissionYear', s.admissionYear||new Date().getFullYear());
            set('editAdmissionDate', (s.admissionDate||s.admission_date||'').split(' ')[0].split('T')[0]);
            set('editSchoolclassid', s.schoolclassid);
            set('editTermid',        s.termid);
            set('editSessionid',     s.sessionid);
            set('editStudentCategory',s.student_category);
            set('editTitle',         s.title);
            set('editLastname',      s.lastname);
            set('editFirstname',     s.firstname);
            set('editOthername',     s.othername);
            set('editPlaceofbirth',  s.placeofbirth);
            set('editPhoneNumber',   s.phone_number);
            set('editEmail',         s.email);
            set('editFutureAmbition',s.future_ambition);
            set('editPermanentAddress',s.permanent_address);
            set('editNationality',   s.nationality);
            set('editCity',          s.city);
            set('editReligion',      s.religion);
            set('editBloodGroup',    s.blood_group);
            set('editMotherTongue',  s.mother_tongue);
            set('editNinNumber',     s.nin_number);
            set('editFatherName',    s.father_name);
            set('editFatherPhone',   s.father_phone);
            set('editFatherOccupation',s.father_occupation);
            set('editFatherCity',    s.father_city);
            set('editMotherName',    s.mother_name);
            set('editMotherPhone',   s.mother_phone);
            set('editParentEmail',   s.parent_email);
            set('editParentAddress', s.parent_address);
            set('editLastSchool',    s.last_school);
            set('editLastClass',     s.last_class);
            set('editReasonForLeaving',s.reason_for_leaving);

            // DOB & age
            const dobInput = document.getElementById('editDOB');
            if (dobInput && s.dateofbirth) {
                const dv = s.dateofbirth.split(' ')[0].split('T')[0];
                dobInput.value = dv;
                const ageEl = document.getElementById('editAgeInput');
                if (ageEl) ageEl.value = U.age(dv)||s.age||'';
            }

            // Radio buttons
            if (s.statusId==1) chk('statusId','1'); else if (s.statusId==2) chk('statusId','2');
            if (s.student_status==='Active') chk('student_status','Active'); else chk('student_status','Inactive');
            if (s.gender==='Male') chk('gender','Male'); else if (s.gender==='Female') chk('gender','Female');

            // Admission mode
            const manualRadio = document.getElementById('editAdmissionManual');
            if (manualRadio) { manualRadio.checked = true; const noEl = document.getElementById('editAdmissionNo'); if (noEl) noEl.readOnly = false; }

            // School House
            const houseEl = document.getElementById('editSchoolHouse');
            if (houseEl && s.schoolhouseid) houseEl.value = s.schoolhouseid;

            // State & LGA
            if (s.state) SLM.set('editState','editLocal', s.state, s.local);

            // Avatar
            const avatar = document.getElementById('editStudentAvatar');
            if (avatar) avatar.src = s.picture && s.picture !== 'unnamed.jpg' ? `/storage/images/student_avatars/${s.picture}` : '{{ asset("theme/layouts/assets/media/avatars/blank.png") }}';
        }
    };

    // ================================================================
    // FILTERS
    // ================================================================
    const Filters = {
        searchDebounce: null,
        init: () => {
            const si = document.getElementById('search-input');
            const cl = document.getElementById('clear-search');
            if (si) {
                si.addEventListener('input', e => {
                    if (cl) cl.style.display = e.target.value ? 'block' : 'none';
                    clearTimeout(Filters.searchDebounce);
                    Filters.searchDebounce = setTimeout(() => Filters.apply(), CONFIG.SEARCH_DEBOUNCE);
                });
                si.addEventListener('keypress', e => { if (e.key==='Enter') { clearTimeout(Filters.searchDebounce); Filters.apply(); } });
            }
            if (cl) cl.addEventListener('click', () => { if (si) { si.value = ''; cl.style.display='none'; } Filters.apply(); });
            ['schoolclass-filter','term-filter','session-filter'].forEach(id => { const el=document.getElementById(id); if (el) el.addEventListener('change', () => Filters.apply()); });
            document.getElementById('filterBtn')?.addEventListener('click', () => Filters.apply());
            document.getElementById('resetFiltersBtn')?.addEventListener('click', () => Filters.reset());
            document.getElementById('resetFromEmptyBtn')?.addEventListener('click', () => Filters.reset());
        },
        apply: () => {
            AppState.filters = {
                search:  document.getElementById('search-input')?.value.trim()||'',
                class:   document.getElementById('schoolclass-filter')?.value||'all',
                term:    document.getElementById('term-filter')?.value||'all',
                session: document.getElementById('session-filter')?.value||'all',
                status:  'all', gender: 'all'
            };
            AppState.pagination.currentPage = 1;
            SM.fetch();
        },
        reset: () => {
            ['search-input'].forEach(id => { const el=document.getElementById(id); if (el) el.value=''; });
            ['schoolclass-filter','term-filter','session-filter'].forEach(id => { const el=document.getElementById(id); if (el) el.value='all'; });
            const cl = document.getElementById('clear-search'); if (cl) cl.style.display='none';
            AppState.filters = { search:'',class:'all',status:'all',gender:'all',session:'all',term:'all' };
            AppState.pagination.currentPage = 1;
            SM.fetch();
        }
    };

    // ================================================================
    // CURRENT TERM MANAGER
    // ================================================================
    const CTM = {
        show: () => {
            const ids = Selection.getIds();
            if (!ids.length) { U.err('Please select at least one student.','No Selection'); return; }
            U.setText('selectedStudentsCount', ids.length);
            new bootstrap.Modal(document.getElementById('updateCurrentTermModal')).show();
        },
        update: async () => {
            const form = document.getElementById('updateCurrentTermForm');
            const classId   = form?.querySelector('[name="schoolclassId"]')?.value;
            const termId    = form?.querySelector('[name="termId"]')?.value;
            const sessionId = form?.querySelector('[name="sessionId"]')?.value;
            if (!classId||!termId||!sessionId) { U.err('Please select class, term, and session.','Missing Fields'); return; }
            const ids = Selection.getIds();
            Swal.fire({title:'Updating…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            try {
                const r = await API.bulkTerm({student_ids:ids,schoolclassId:classId,termId,sessionId,is_current:true});
                bootstrap.Modal.getInstance(document.getElementById('updateCurrentTermModal'))?.hide();
                Swal.close(); U.ok(r.message||'Term updated.'); await SM.fetch();
            } catch(e) { Swal.close(); U.err(e.response?.data?.message||'Failed to update term.'); }
        }
    };

    // ================================================================
    // BULK STATUS MANAGER
    // ================================================================
    const BSM = {
        show: () => {
            const cid = document.getElementById('schoolclass-filter')?.value;
            const sid = document.getElementById('session-filter')?.value;
            if (!cid||cid==='all'||!sid||sid==='all') { U.err('Please select both a class and a session first.','Selection Required'); return; }
            AppState.bulkStatusFilters = {class_id:cid,session_id:sid};
            Swal.fire({title:'Loading Students…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            API.byClassSession(cid,sid).then(r => {
                Swal.close();
                if (r.success) BSM.render(r.students, r.stats);
                else U.err(r.message||'Failed to load.');
            }).catch(e => { Swal.close(); U.err(e.response?.data?.message||e.message); });
        },
        render: (students, stats) => {
            document.getElementById('bulkStatusUpdateModal')?.remove();
            const html = `
            <div class="modal fade" id="bulkStatusUpdateModal" tabindex="-1" data-bs-backdrop="static">
              <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header modal-hdr">
                    <h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Bulk Update Student Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                      <div class="col-6 col-md-3"><div class="sm-stat c-blue"><div class="sm-stat-value">${stats.total}</div><div class="sm-stat-label">Total</div></div></div>
                      <div class="col-6 col-md-3"><div class="sm-stat c-green"><div class="sm-stat-value">${stats.active}</div><div class="sm-stat-label">Active</div></div></div>
                      <div class="col-6 col-md-3"><div class="sm-stat c-orange" style="--sm-stat-color:#6b7280"><div class="sm-stat-value">${stats.inactive}</div><div class="sm-stat-label">Inactive</div></div></div>
                      <div class="col-6 col-md-3"><div class="sm-stat c-amber"><div class="sm-stat-value">${stats.new_students}</div><div class="sm-stat-label">New</div></div></div>
                    </div>
                    <div class="sm-panel mb-3">
                      <div class="sm-panel-header">
                        <div class="d-flex align-items-center gap-3">
                          <div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="bsSelectAll"><label class="form-check-label fw-semibold" for="bsSelectAll">Select All</label></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <div class="dropdown"><button class="btn btn-outline-success btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-user-check me-1"></i>Activity Status</button>
                            <ul class="dropdown-menu shadow border-0 rounded-3 p-2">
                              <li><a class="dropdown-item rounded-2" href="#" onclick="BulkStatusManager.bulkUpdateStatus('activity_status','Active')"><i class="fas fa-check-circle text-success me-2"></i>Active</a></li>
                              <li><a class="dropdown-item rounded-2" href="#" onclick="BulkStatusManager.bulkUpdateStatus('activity_status','Inactive')"><i class="fas fa-pause-circle text-secondary me-2"></i>Inactive</a></li>
                            </ul>
                          </div>
                          <div class="dropdown"><button class="btn btn-outline-warning btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-user-tag me-1"></i>Student Type</button>
                            <ul class="dropdown-menu shadow border-0 rounded-3 p-2">
                              <li><a class="dropdown-item rounded-2" href="#" onclick="BulkStatusManager.bulkUpdateStatus('student_type','old')"><i class="fas fa-history text-secondary me-2"></i>Old Student</a></li>
                              <li><a class="dropdown-item rounded-2" href="#" onclick="BulkStatusManager.bulkUpdateStatus('student_type','new')"><i class="fas fa-star text-warning me-2"></i>New Student</a></li>
                            </ul>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table class="table sm-table mb-0">
                        <thead><tr><th width="46"><input class="form-check-input" type="checkbox" id="bsSelectAllTable"></th><th>Student</th><th>Admission No</th><th>Class</th><th>Activity Status</th><th>Type</th><th>Actions</th></tr></thead>
                        <tbody>${BSM.rows(students)}</tbody>
                      </table>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn-pg" onclick="BulkStatusManager.refreshData()"><i class="fas fa-sync-alt me-1"></i>Refresh</button>
                  </div>
                </div>
              </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', html);
            // Select all
            document.getElementById('bsSelectAll')?.addEventListener('change', e => { document.querySelectorAll('.bs-checkbox').forEach(cb => cb.checked = e.target.checked); });
            document.getElementById('bsSelectAllTable')?.addEventListener('change', e => { document.querySelectorAll('.bs-checkbox').forEach(cb => cb.checked = e.target.checked); });
            new bootstrap.Modal(document.getElementById('bulkStatusUpdateModal')).show();
        },
        rows: students => {
            if (!students?.length) return '<tr><td colspan="7" class="text-center py-4 text-muted">No students found</td></tr>';
            return students.map(s => {
                const init = U.initials(s.firstname, s.lastname);
                const act = s.student_status==='Active' ? '<span class="sm-badge badge-active" style="font-size:11px;">Active</span>' : '<span class="sm-badge badge-inactive" style="font-size:11px;">Inactive</span>';
                const type= s.statusId==2 ? '<span class="sm-badge badge-new" style="font-size:11px;">New</span>' : '<span class="sm-badge badge-old" style="font-size:11px;">Old</span>';
                return `<tr data-student-id="${s.id}">
                  <td><input class="form-check-input bs-checkbox" type="checkbox" value="${s.id}"></td>
                  <td><div class="d-flex align-items-center gap-2">
                    <div class="sm-avatar-init" style="width:36px;height:36px;border-radius:8px;font-size:14px;">${U.esc(init)}</div>
                    <div><div class="stu-name">${U.esc(s.lastname||'')} ${U.esc(s.firstname||'')}</div><small class="text-muted">${U.esc(s.othername||'')}</small></div>
                  </div></td>
                  <td><span style="font-family:monospace;font-size:12px;">${U.esc(s.admissionNo||'N/A')}</span></td>
                  <td>${U.esc(s.schoolclass||'')} ${U.esc(s.arm||'')}</td>
                  <td><div class="d-flex align-items-center gap-1">${act}<button class="act-btn act-edit" style="width:26px;height:26px;" onclick="BulkStatusManager.toggleOne(${s.id},'activity')"><i class="fas fa-exchange-alt" style="font-size:11px;"></i></button></div></td>
                  <td><div class="d-flex align-items-center gap-1">${type}<button class="act-btn act-edit" style="width:26px;height:26px;" onclick="BulkStatusManager.toggleOne(${s.id},'type')"><i class="fas fa-exchange-alt" style="font-size:11px;"></i></button></div></td>
                  <td><button class="act-btn act-view" onclick="StudentManager.viewStudent(${s.id})"><i class="fas fa-eye"></i></button></td>
                </tr>`;
            }).join('');
        },
        getSelectedIds: () => Array.from(document.querySelectorAll('.bs-checkbox:checked')).map(cb=>parseInt(cb.value)).filter(v=>!isNaN(v)),
        bulkUpdateStatus: async (type, value) => {
            const ids = BSM.getSelectedIds();
            if (!ids.length) { U.err('Please select at least one student.','No Selection'); return; }
            const displayValue = type==='student_type' ? (value==='old'?'Old Student':'New Student') : value;
            if (!await U.confirm(`Update ${ids.length} student(s)?`, `Set to "${displayValue}"`, 'Yes, update')) return;
            Swal.fire({title:'Updating…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            try {
                const r = await API.bulkStatus({student_ids:ids,update_type:type,value});
                Swal.close(); if (r.success) { U.ok(r.message); BSM.refreshData(); }
            } catch(e) { Swal.close(); U.err(e.response?.data?.message||e.message); }
        },
        toggleOne: async (id, type) => {
            const row = document.querySelector(`tr[data-student-id="${id}"]`);
            const s = AppState.cache.students.get(String(id));
            if (!s) { U.err('Cannot find student data.'); return; }
            const updateType = type==='activity' ? 'activity_status' : 'student_type';
            const newVal = type==='activity' ? (s.student_status==='Active'?'Inactive':'Active') : (s.statusId==1?'new':'old');
            if (!await U.confirm('Confirm Update', `Change to ${newVal}?`, 'Yes, update')) return;
            Swal.fire({title:'Updating…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            try {
                const r = await API.bulkStatus({student_ids:[id],update_type:updateType,value:newVal});
                Swal.close(); if (r.success) { U.ok('Updated'); BSM.refreshData(); }
            } catch(e) { Swal.close(); U.err('Failed.'); }
        },
        refreshData: async () => {
            if (!AppState.bulkStatusFilters) return;
            Swal.fire({title:'Refreshing…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            try {
                const r = await API.byClassSession(AppState.bulkStatusFilters.class_id, AppState.bulkStatusFilters.session_id);
                if (r.success) {
                    const tbody = document.querySelector('#bulkStatusUpdateModal tbody');
                    if (tbody) tbody.innerHTML = BSM.rows(r.students);
                    // Update stats
                    const vals = document.querySelectorAll('#bulkStatusUpdateModal .sm-stat-value');
                    if (vals.length >= 4) { vals[0].textContent=r.stats.total; vals[1].textContent=r.stats.active; vals[2].textContent=r.stats.inactive; vals[3].textContent=r.stats.new_students; }
                }
                Swal.close();
            } catch(e) { Swal.close(); U.err('Failed to refresh.'); }
        }
    };

    // ================================================================
    // TERM REGISTRATION MANAGER
    // ================================================================
    const TRM = {
        show: () => {
            const tid = document.getElementById('term-filter')?.value;
            const sid = document.getElementById('session-filter')?.value;
            if (!tid||tid==='all'||!sid||sid==='all') { U.err('Please select both a term and a session first.','Selection Required'); return; }
            AppState.termFilters = {term_id:tid, session_id:sid, class_id: document.getElementById('schoolclass-filter')?.value !== 'all' ? document.getElementById('schoolclass-filter')?.value : null};
            Swal.fire({title:'Loading…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            API.inTerm(AppState.termFilters).then(r => {
                Swal.close();
                if (r.success) TRM.render(r.students, r.total);
                else U.err(r.message||'Failed.');
            }).catch(e => { Swal.close(); U.err(e.response?.data?.message||e.message); });
        },
        render: (students, total) => {
            document.getElementById('termStudentsModal')?.remove();
            const html = `
            <div class="modal fade" id="termStudentsModal" tabindex="-1" data-bs-backdrop="static">
              <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header modal-hdr">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Term Registration Management</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-3 mb-4">
                      <i class="fas fa-info-circle fa-2x"></i>
                      <div><strong>Total Registered: ${total}</strong><br><small>Manage term registrations below</small></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                      <div class="form-check"><input class="form-check-input" type="checkbox" id="trmSelectAll"><label class="form-check-label fw-semibold" for="trmSelectAll">Select All</label></div>
                      <button class="btn btn-danger btn-sm rounded-3" onclick="TermRegistrationManager.bulkRemove()"><i class="fas fa-user-minus me-1"></i>Remove Selected from Term</button>
                    </div>
                    <div class="row g-3" id="termStudentsContainer">${TRM.cards(students)}</div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn-pg" onclick="TermRegistrationManager.refreshData()"><i class="fas fa-sync-alt me-1"></i>Refresh</button>
                  </div>
                </div>
              </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', html);
            document.getElementById('trmSelectAll')?.addEventListener('change', e => { document.querySelectorAll('.trm-checkbox').forEach(cb => cb.checked = e.target.checked); });
            new bootstrap.Modal(document.getElementById('termStudentsModal')).show();
        },
        cards: students => {
            if (!students?.length) return '<div class="col-12"><div class="alert alert-warning rounded-3 text-center">No students registered for this term.</div></div>';
            return students.map(s => {
                const init = ((s.firstname||'').charAt(0)+(s.lastname||'').charAt(0)).toUpperCase()||'ST';
                const cur  = s.is_current ? '<span class="badge bg-success position-absolute top-0 end-0 m-2" style="font-size:10px;">Current</span>' : '';
                return `<div class="col-md-4 col-lg-3">
                  <div class="stu-card" data-registration-id="${s.registration_id}" style="margin-bottom:12px;">
                    <div class="stu-card-header" style="min-height:80px;">
                      ${cur}
                      <div class="stu-card-checkbox"><input class="form-check-input trm-checkbox" type="checkbox" value="${s.registration_id}" style="border:2px solid rgba(255,255,255,.8);width:18px;height:18px;"></div>
                      <div class="stu-card-name">${U.esc(s.fullname||'')}</div>
                      <span class="stu-card-adm">${U.esc(s.admissionNo||'N/A')}</span>
                    </div>
                    <div class="stu-card-body">
                      <div class="stu-card-grid">
                        <div><div class="stu-card-info-label">Class</div><div class="stu-card-info-val">${U.esc(s.class||'')} ${U.esc(s.arm||'')}</div></div>
                        <div><div class="stu-card-info-label">Gender</div><div class="stu-card-info-val">${U.esc(s.gender||'—')}</div></div>
                      </div>
                      <button class="btn btn-outline-danger btn-sm w-100 rounded-3" style="font-size:12px;"
                              onclick="TermRegistrationManager.removeOne(${s.registration_id}, '${U.esc(s.fullname)}')">
                        <i class="fas fa-user-minus me-1"></i>Remove from Term
                      </button>
                    </div>
                  </div>
                </div>`;
            }).join('');
        },
        removeOne: async (regId, name) => {
            if (!await U.confirm('Remove Student?', `Remove ${name} from this term?`, 'Yes, remove')) return;
            Swal.fire({title:'Removing…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            try {
                const r = await API.rmTerm(regId); Swal.close();
                if (r.success) { U.ok(r.message); const card = document.querySelector(`.stu-card[data-registration-id="${regId}"]`); card?.closest('.col-md-4,.col-lg-3')?.remove(); }
            } catch(e) { Swal.close(); U.err('Failed to remove.'); }
        },
        bulkRemove: async () => {
            const ids = Array.from(document.querySelectorAll('.trm-checkbox:checked')).map(cb=>cb.value);
            if (!ids.length) { U.err('Please select at least one student.','No Selection'); return; }
            if (!await U.confirm(`Remove ${ids.length} student(s)?`, 'This will remove their term registration.', 'Yes, remove all')) return;
            Swal.fire({title:'Removing…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            try {
                const r = await API.rmTermMany(ids); Swal.close();
                if (r.success) { U.ok(r.message); TRM.refreshData(); }
            } catch(e) { Swal.close(); U.err('Failed to remove.'); }
        },
        refreshData: async () => {
            if (!AppState.termFilters) return;
            Swal.fire({title:'Refreshing…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            try {
                const r = await API.inTerm(AppState.termFilters);
                if (r.success) { const c = document.getElementById('termStudentsContainer'); if (c) c.innerHTML = TRM.cards(r.students); }
                Swal.close();
            } catch(e) { Swal.close(); U.err('Failed.'); }
        }
    };

    // ================================================================
    // REPORT MANAGER — FIXED DRAG & DROP
    // ================================================================
    const Report = {
        sortable: null,
        init: () => {
            const container = document.getElementById('columnsContainer');
            if (!container) return;
            if (typeof Sortable === 'undefined') { console.error('Sortable not loaded'); return; }
            if (Report.sortable) { try { Report.sortable.destroy(); } catch {} }
            Report.sortable = new Sortable(container, {
                animation: 200,
                handle: '.drag-handle',
                draggable: '.draggable-item',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: () => { Report.updateOrder(); Report.updatePreview(); }
            });
            container.querySelectorAll('.column-checkbox').forEach(cb => { cb.onchange = () => { Report.updateOrder(); Report.updatePreview(); }; });
            // Select/deselect all buttons
            document.getElementById('selectAllColumnsBtn')?.addEventListener('click', () => {
                container.querySelectorAll('.column-checkbox').forEach(cb => cb.checked = true);
                Report.updateOrder(); Report.updatePreview();
            });
            document.getElementById('deselectAllColumnsBtn')?.addEventListener('click', () => {
                container.querySelectorAll('.column-checkbox').forEach(cb => cb.checked = false);
                Report.updateOrder(); Report.updatePreview();
            });
            Report.updateOrder();
            Report.updatePreview();
        },
        updateOrder: () => {
            const container = document.getElementById('columnsContainer');
            const orderInput = document.getElementById('columnsOrderInput');
            if (!container || !orderInput) return;
            const items = container.querySelectorAll('.draggable-item');
            const checked = Array.from(items).filter(el => el.querySelector('.column-checkbox')?.checked).map(el => el.dataset.column);
            orderInput.value = checked.join(',');
            // Badge numbers
            let n = 0;
            items.forEach(el => {
                let badge = el.querySelector('.order-badge');
                if (el.querySelector('.column-checkbox')?.checked) {
                    n++;
                    if (!badge) { badge = document.createElement('span'); badge.className = 'order-badge'; el.style.position='relative'; el.appendChild(badge); }
                    badge.textContent = n;
                } else { badge?.remove(); }
            });
        },
        updatePreview: () => {
            const preview = document.getElementById('columnOrderPreview');
            if (!preview) return;
            const labels = Array.from(document.querySelectorAll('.column-checkbox:checked')).map(cb => {
                const label = document.querySelector(`label[for="${cb.id}"]`);
                return label ? label.textContent.trim() : cb.value;
            });
            preview.textContent = labels.length ? labels.join(', ') : 'No columns selected';
        },
        generate: async () => {
            const form = document.getElementById('printReportForm');
            if (!form) return;
            const selectedColumns = Array.from(form.querySelectorAll('.column-checkbox:checked')).map(cb => cb.value);
            if (!selectedColumns.length) { U.err('Please select at least one column.','No Columns Selected'); return; }
            const fd = new FormData(form);
            const params = {};
            for (const [k, v] of fd.entries()) {
                if (k==='columns[]') { params.columns = params.columns ? params.columns+','+v : v; }
                else if (k==='columns_order') { params.columns_order = document.getElementById('columnsOrderInput')?.value||v; }
                else if (v) params[k] = v;
            }
            if (!params.format) { const r = form.querySelector('input[name="format"]:checked'); params.format = r?.value||'pdf'; }
            if (!params.orientation) { const o = form.querySelector('#orientation'); params.orientation = o?.value||'portrait'; }
            params.include_header = form.querySelector('input[name="include_header"]')?.checked ? '1' : '0';
            params.include_logo   = form.querySelector('input[name="include_logo"]')?.checked   ? '1' : '0';
            bootstrap.Modal.getInstance(document.getElementById('printStudentReportModal'))?.hide();
            Swal.fire({title:'Generating Report…',html:'Please wait…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
            try {
                const r = await axios({method:'GET',url:'/students/report',params,responseType:'blob',timeout:120000});
                Swal.close();
                const url = window.URL.createObjectURL(new Blob([r.data]));
                const a = document.createElement('a'); a.href = url;
                let fn = `student-report-${new Date().toISOString().split('T')[0]}.${params.format==='excel'?'xlsx':'pdf'}`;
                const cd = r.headers['content-disposition'];
                if (cd) { const m = cd.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/); if (m) fn = m[1].replace(/['"]/g,''); }
                a.download = fn; document.body.appendChild(a); a.click(); document.body.removeChild(a); window.URL.revokeObjectURL(url);
                U.ok(`Report generated: ${fn}`);
            } catch(e) {
                Swal.close();
                let msg = 'Failed to generate report.';
                if (e.response?.data instanceof Blob) { try { const t = await e.response.data.text(); msg = JSON.parse(t).message||msg; } catch {} }
                else if (e.response?.data?.message) msg = e.response.data.message;
                U.err(msg, 'Report Generation Failed');
            }
        }
    };

    // ================================================================
    // FORM SUBMISSIONS
    // ================================================================
    document.getElementById('addStudentForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);
        Swal.fire({title:'Saving…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
        try {
            const r = await axios.post(form.action, fd, {headers:{'Content-Type':'multipart/form-data'}});
            if (r.data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addStudentModal'))?.hide();
                await SM.fetch(); U.ok(r.data.message||'Student registered.');
                form.reset();
                const av = document.getElementById('addStudentAvatar'); if (av) av.src = 'https://via.placeholder.com/120x120/2563eb/ffffff?text=Photo';
            }
        } catch(err) { Swal.close(); U.err(err.response?.data?.message||'Failed to save student.'); }
    });

    document.getElementById('editStudentForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);
        fd.append('_method','PATCH');
        Swal.fire({title:'Updating…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
        try {
            const r = await axios.post(form.action, fd, {headers:{'Content-Type':'multipart/form-data'}});
            if (r.data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editStudentModal'))?.hide();
                await SM.fetch(); U.ok(r.data.message||'Student updated.');
            } else { Swal.close(); U.err(r.data.message||'Failed.'); }
        } catch(err) { Swal.close(); U.err(err.response?.data?.message||'Failed to update student.'); }
    });

    // ================================================================
    // EVENT DELEGATION & INIT
    // ================================================================
    document.addEventListener('click', e => {
        const vb = e.target.closest('.view-student-btn');   if (vb) { e.preventDefault(); SM.view(vb.dataset.studentId); return; }
        const eb = e.target.closest('.edit-student-btn');   if (eb) { e.preventDefault(); SM.edit(eb.dataset.studentId); return; }
        const db = e.target.closest('.delete-student-btn'); if (db) { e.preventDefault(); SM.delete(db.dataset.studentId); return; }
    });

    document.getElementById('deleteMultipleBtn')?.addEventListener('click', e => { e.preventDefault(); SM.deleteMany(); });
    document.getElementById('updateCurrentTermBtn')?.addEventListener('click', e => { e.preventDefault(); CTM.show(); });
    document.getElementById('confirmUpdateCurrentTerm')?.addEventListener('click', () => CTM.update());
    document.getElementById('generateReportBtn')?.addEventListener('click', e => { e.preventDefault(); Report.generate(); });
    document.getElementById('tableViewBtn')?.addEventListener('click', () => Render.toggleView('table'));
    document.getElementById('cardViewBtn')?.addEventListener('click',  () => Render.toggleView('card'));
    document.getElementById('bulkStatusBtn')?.addEventListener('click', e => { e.preventDefault(); BSM.show(); });
    document.getElementById('manageTermBtn')?.addEventListener('click',  e => { e.preventDefault(); TRM.show(); });

    // Report modal — init sortable when it opens
    document.getElementById('printStudentReportModal')?.addEventListener('show.bs.modal', () => {
        setTimeout(() => Report.init(), 200);
    });

    // ================================================================
    // GLOBAL FUNCTIONS (used in templates / onclick attrs)
    // ================================================================
    window.handleAvatarZoom = el => {
        const src    = el.dataset.zoomSrc;
        const name   = el.dataset.zoomName;
        const init   = el.dataset.zoomInit;
        const detail = el.dataset.zoomDetail;
        U.openZoom(src || null, name, init, detail);
    };
    window.openZoomFromView = el => {
        const src    = el.dataset.zoomSrc;
        const name   = el.dataset.zoomName;
        const init   = el.dataset.zoomInit;
        const detail = el.dataset.zoomDetail;
        U.openZoom(src||null, name, init, detail);
    };
    window.updateAdmissionNumber = prefix => {
        const yr = document.getElementById(`${prefix?prefix+'A':'a'}dmissionYear`)?.value || new Date().getFullYear();
        const input = document.getElementById(`${prefix?prefix+'A':'a'}dmissionNo`);
        if (input) { const base = `TCC/${yr}/`; if (!input.value.startsWith(base)) input.value = `${base}0001`; }
    };
    window.toggleAdmissionInput = prefix => {
        const p = prefix ? prefix.charAt(0).toUpperCase() + prefix.slice(1) : '';
        const mode = document.querySelector(`input[name="admissionMode"]#${prefix?prefix:''}${p?'':prefix}AdmissionAuto, input[name="admissionMode"]#${prefix}AdmissionAuto, input[name="admissionMode"]#admissionAuto`);
        const selMode = prefix ? document.querySelector(`input[name="admissionMode"][id^="${prefix}"]:checked`) : document.querySelector('input[name="admissionMode"]:checked');
        const input = document.getElementById(`${prefix?prefix+'A':'a'}dmissionNo`);
        if (input && selMode) input.readOnly = selMode.value === 'auto';
    };
    window.previewImage = (input, targetId='addStudentAvatar') => {
        const f = input.files[0]; if (!f) return;
        const reader = new FileReader();
        reader.onload = e => { const img = document.getElementById(targetId); if (img) img.src = e.target.result; };
        reader.readAsDataURL(f);
    };
    window.calculateAge = (dob, ageInputId) => { const el = document.getElementById(ageInputId); if (el) el.value = U.age(dob)||''; };
    window.callNumber = id => { const p = document.getElementById(id)?.textContent; if (p && p !== '—') window.location.href = `tel:${p}`; };
    window.sendSMS    = id => { const p = document.getElementById(id)?.textContent; if (p && p !== '—') window.location.href = `sms:${p}`; };
    window.sendEmail  = id => { const em = document.getElementById(id)?.textContent; if (em && em !== '—') window.location.href = `mailto:${em}`; };
    window.editStudentFromView = () => {
        const id = ViewModal.currentId;
        if (id) { bootstrap.Modal.getInstance(document.getElementById('viewStudentModal'))?.hide(); SM.edit(id); }
    };
    window.printStudentProfile = () => window.print();
    window.refreshTermHistory  = () => { if (ViewModal.currentId) ViewModal.fetchTerm(ViewModal.currentId); };

    // Expose managers (needed by inline onclick in dynamically created modals)
    window.BulkStatusManager       = BSM;
    window.TermRegistrationManager = TRM;
    window.StudentManager          = { viewStudent: id => SM.view(id) };

    // ================================================================
    // BOOT
    // ================================================================
    function boot() {
        U.csrf();
        Filters.init();
        Selection.init();
        SLM.populate('addState','addLocal');
        SLM.populate('editState','editLocal');
        SM.fetch();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();

})();
</script>
@endsection
