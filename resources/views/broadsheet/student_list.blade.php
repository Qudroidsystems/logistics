{{--
    resources/views/broadsheet/student_list.blade.php
    Standalone printable student list grouped by promotion recommendation.
    Opened in a new tab via POST from the broadsheet web view.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Promotion List — {{ ($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arm_name ?? '') }}</title>
<style>
/* ═══════════════════════════════════════════════════════════
   BASE & ANIMATIONS
═══════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-30px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(30px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}

@keyframes glowPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(13,148,136,.4); }
    50% { box-shadow: 0 0 0 8px rgba(13,148,136,0); }
}

@keyframes rowSlide {
    from { opacity: 0; transform: translateX(-15px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes countUp {
    from { opacity: 0; transform: scale(0.6); }
    to { opacity: 1; transform: scale(1); }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

body {
    font-family: 'Segoe UI', 'Arial', sans-serif;
    font-size: 13px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #0f2342;
    line-height: 1.5;
    min-height: 100vh;
}

/* ── Print resets ── */
@media print {
    body { background: #fff !important; font-size: 11px; }
    .no-print { display: none !important; }
    .page-wrap { max-width: none !important; padding: 0 !important; background: #fff !important; }
    .school-header { page-break-after: avoid; }
    .group-section {
        page-break-after: always;
        page-break-inside: avoid;
    }
    .group-section:last-child {
        page-break-after: auto;
    }
    @page { margin: 1.4cm 1.2cm; }
    .btn-print, .btn-pdf, .btn-close-tab, .btn-settings, .toolbar, .settings-panel {
        display: none !important;
    }
    .student-table tbody tr {
        animation: none !important;
    }
    .group-header {
        animation: none !important;
    }
}

/* Paper size overrides for print */
@media print and (size: A4) { @page { size: A4; } }
@media print and (size: A3) { @page { size: A3; } }
@media print and (size: A2) { @page { size: A2; } }
@media print and (size: A1) { @page { size: A1; } }
@media print and (size: Legal) { @page { size: Legal; } }
@media print and (size: Letter) { @page { size: Letter; } }

@media print and (orientation: portrait) { @page { orientation: portrait; } }
@media print and (orientation: landscape) { @page { orientation: landscape; } }

/* ── Layout ── */
.page-wrap {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 20px;
    animation: fadeInUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
}

/* ═══════════════════════════════════════════════════════════
   SCHOOL HEADER
═══════════════════════════════════════════════════════════ */
.school-header {
    background: linear-gradient(135deg, #0f2342 0%, #1e3a5f 55%, #0d9488 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 24px;
    color: white;
    position: relative;
    overflow: hidden;
    animation: fadeInDown 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.school-header::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%);
    border-radius: 50%;
    animation: floatUp 6s ease-in-out infinite;
}

.school-header::after {
    content: '';
    position: absolute;
    bottom: -40px;
    left: -40px;
    width: 150px;
    height: 150px;
    background: radial-gradient(circle, rgba(255,255,255,.05) 0%, transparent 70%);
    border-radius: 50%;
    animation: floatUp 8s ease-in-out infinite reverse;
}

@keyframes floatUp {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.school-logo {
    width: 85px;
    height: 85px;
    border-radius: 50%;
    object-fit: contain;
    border: 3px solid rgba(255,255,255,.4);
    background: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
    animation: pulse 3s ease-in-out infinite;
}

.school-logo:hover {
    transform: scale(1.05);
    border-color: #0d9488;
}

.school-logo-placeholder {
    width: 85px;
    height: 85px;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 3px solid rgba(255,255,255,.35);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 800;
    color: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.school-info { flex: 1; text-align: center; }
.school-name {
    font-size: 22px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    line-height: 1.2;
    animation: fadeInUp 0.5s ease both;
    animation-delay: 0.1s;
}
.school-address {
    font-size: 12px;
    opacity: 0.85;
    margin-top: 6px;
    animation: fadeInUp 0.5s ease both;
    animation-delay: 0.2s;
}
.school-motto {
    font-size: 11.5px;
    font-style: italic;
    opacity: 0.7;
    margin-top: 4px;
    animation: fadeInUp 0.5s ease both;
    animation-delay: 0.3s;
}

.list-title-bar {
    background: linear-gradient(135deg, #0f2342, #1e4a7e);
    color: white;
    text-align: center;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 2px;
    border-radius: 12px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.5s ease both;
    animation-delay: 0.15s;
}

.list-title-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.1), transparent);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* ── Meta strip ── */
.meta-strip {
    display: flex;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    margin-bottom: 24px;
    overflow: hidden;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    animation: fadeInUp 0.5s ease both;
    animation-delay: 0.2s;
}

.meta-cell {
    flex: 1;
    padding: 12px 16px;
    border-right: 1px solid #e2e8f0;
    text-align: center;
    min-width: 100px;
    transition: all 0.3s ease;
}
.meta-cell:last-child { border-right: none; }
.meta-cell:hover {
    background: #f0fdf9;
    transform: translateY(-2px);
}

.meta-label {
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
}
.meta-value {
    font-size: 14px;
    font-weight: 700;
    color: #0f2342;
    display: block;
    margin-top: 4px;
}

/* ═══════════════════════════════════════════════════════════
   PROMOTION GROUP HEADER
═══════════════════════════════════════════════════════════ */
.group-section {
    margin-bottom: 32px;
    page-break-after: always;
    page-break-inside: avoid;
    animation: fadeInUp 0.5s ease both;
}
.group-section:last-child {
    page-break-after: auto;
}

.group-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-radius: 12px 12px 0 0;
    font-weight: 700;
    font-size: 15px;
    border-bottom: 2px solid rgba(0,0,0,.08);
    transition: all 0.3s ease;
    animation: slideIn 0.4s ease both;
}

.group-header:hover {
    transform: translateX(5px);
}

.group-header .count-badge {
    margin-left: auto;
    padding: 4px 14px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(255,255,255,.4);
    transition: all 0.3s ease;
}

.group-header:hover .count-badge {
    transform: scale(1.05);
}

/* Status colours with animations */
.status-promoted {
    background: linear-gradient(90deg, #d1fae5, #ecfdf5);
    color: #065f46;
    border-left: 5px solid #10b981;
}
.status-trial {
    background: linear-gradient(90deg, #fef3c7, #fffbeb);
    color: #92400e;
    border-left: 5px solid #f59e0b;
}
.status-see_principal {
    background: linear-gradient(90deg, #dbeafe, #eff6ff);
    color: #1e40af;
    border-left: 5px solid #3b82f6;
}
.status-repeated {
    background: linear-gradient(90deg, #fee2e2, #fff1f2);
    color: #991b1b;
    border-left: 5px solid #ef4444;
}
.status-awaiting {
    background: linear-gradient(90deg, #f1f5f9, #f8fafc);
    color: #475569;
    border-left: 5px solid #94a3b8;
}
.status-other {
    background: linear-gradient(90deg, #f5f3ff, #ede9fe);
    color: #5b21b6;
    border-left: 5px solid #7c3aed;
}

/* ═══════════════════════════════════════════════════════════
   STUDENT TABLE
═══════════════════════════════════════════════════════════ */
.table-responsive-wrapper {
    overflow-x: auto;
    border-radius: 0 0 12px 12px;
}

.student-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border: 1px solid #e2e8f0;
    border-top: none;
    font-size: 12px;
    min-width: 600px;
}

.student-table thead th {
    background: linear-gradient(135deg, #1e3a5f, #0f2342);
    color: #a8d4ef;
    padding: 12px 14px;
    text-align: left;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-right: 1px solid rgba(255,255,255,.1);
    white-space: nowrap;
    position: sticky;
    top: 0;
}
.student-table thead th:last-child { border-right: none; }

.student-table tbody tr {
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    animation: rowSlide 0.4s ease both;
}
.student-table tbody tr:nth-child(odd)  { background: #ffffff; }
.student-table tbody tr:nth-child(even) { background: #f8fafc; }
.student-table tbody tr:hover {
    background: linear-gradient(90deg, #f0fdf9, #e8f0fe) !important;
    transform: translateX(4px);
    box-shadow: -4px 0 0 #0d9488;
}

.student-table tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    border-right: 1px solid #f1f5f9;
    vertical-align: middle;
    white-space: nowrap;
    transition: all 0.2s ease;
}
.student-table tbody td:last-child { border-right: none; }
.student-table tbody tr:last-child td { border-bottom: none; }

td.sn-cell {
    width: 40px;
    text-align: center;
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    background: linear-gradient(135deg, #f8fafc, #fff);
}

/* Avatar */
.student-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    flex-shrink: 0;
    transition: all 0.3s ease;
}
.student-avatar:hover {
    transform: scale(1.1);
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13,148,136,.2);
}

.avatar-initials {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d9488, #0ea5e9);
    color: white;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.3s ease;
}
.avatar-initials:hover {
    transform: scale(1.1);
    animation: glowPulse 0.8s ease infinite;
}

.name-cell {
    font-weight: 600;
    color: #0f2342;
    transition: color 0.3s ease;
}
.name-cell:hover {
    color: #0d9488;
}

.adm-cell {
    font-family: 'Courier New', monospace;
    font-size: 11px;
    color: #475569;
    letter-spacing: 0.5px;
}
.gender-cell { text-align: center; font-size: 11px; }
.arm-cell {
    font-size: 11px;
    color: #0f2342;
    font-weight: 500;
}

/* ── Summary footer ── */
.summary-footer {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 24px;
    margin-top: 28px;
    box-shadow: 0 4px 12px rgba(0,0,0,.05);
    animation: fadeInUp 0.5s ease both;
    animation-delay: 0.3s;
}
.summary-footer h4 {
    font-size: 15px;
    font-weight: 700;
    color: #0f2342;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.summary-grid { display: flex; gap: 12px; flex-wrap: wrap; }
.summary-item {
    flex: 1;
    min-width: 110px;
    text-align: center;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    animation: scaleIn 0.4s ease both;
}
.summary-item:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 8px 20px rgba(0,0,0,.1);
}
.summary-count {
    font-size: 28px;
    font-weight: 800;
    display: block;
    animation: countUp 0.6s ease both;
}
.summary-lbl {
    font-size: 11px;
    font-weight: 600;
    display: block;
    margin-top: 6px;
}

/* ── No-print toolbar ── */
.toolbar {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: 0 4px 12px rgba(0,0,0,.07);
    animation: fadeInUp 0.5s ease both;
}

.toolbar-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f2342;
    display: flex;
    align-items: center;
    gap: 10px;
}
.toolbar-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.btn-print, .btn-pdf {
    background: linear-gradient(135deg, #0f2342, #1e3a5f);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 22px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    position: relative;
    overflow: hidden;
}
.btn-print::before, .btn-pdf::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255,255,255,.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}
.btn-print:hover::before, .btn-pdf:hover::before {
    width: 200px;
    height: 200px;
}

.btn-pdf {
    background: linear-gradient(135deg, #dc2626, #ef4444);
}
.btn-print:hover, .btn-pdf:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,.2);
}

.btn-close-tab {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}
.btn-close-tab:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
}

/* Settings panel */
.settings-panel {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
    display: none;
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
    animation: scaleIn 0.3s ease;
}
.settings-panel.open { display: block; }
.settings-panel h4 {
    font-size: 14px;
    font-weight: 700;
    color: #0f2342;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.settings-group { display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 18px; align-items: center; }
.settings-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12.5px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.settings-group label:hover {
    color: #0d9488;
}
.settings-group select, .settings-group input {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
}
.settings-group select:hover, .settings-group input:hover {
    border-color: #0d9488;
}
.settings-group select:focus, .settings-group input:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13,148,136,.1);
}

.btn-settings {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}
.btn-settings:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
}
.btn-settings.active {
    background: #7c3aed;
    color: white;
    border-color: #7c3aed;
}

/* Column checkboxes grid */
.columns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
    margin-top: 12px;
}
.columns-grid label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    padding: 8px 12px;
    background: white;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
}
.columns-grid label:hover {
    background: #f0fdf9;
    border-color: #0d9488;
    transform: translateX(4px);
}
.columns-grid input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #7c3aed;
    cursor: pointer;
}

/* ── Generated at line ── */
.generated-line {
    text-align: center;
    font-size: 10.5px;
    color: #94a3b8;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px dashed #e2e8f0;
    animation: fadeInUp 0.5s ease both;
}

/* Loading overlay for PDF generation */
.pdf-loading {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,.85);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: white;
    backdrop-filter: blur(4px);
}
.pdf-loading.active { display: flex; }
.pdf-loading .spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255,255,255,.2);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}
.pdf-loading p {
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* Animations delay for rows */
.student-table tbody tr:nth-child(1) { animation-delay: 0.02s; }
.student-table tbody tr:nth-child(2) { animation-delay: 0.04s; }
.student-table tbody tr:nth-child(3) { animation-delay: 0.06s; }
.student-table tbody tr:nth-child(4) { animation-delay: 0.08s; }
.student-table tbody tr:nth-child(5) { animation-delay: 0.10s; }
.student-table tbody tr:nth-child(6) { animation-delay: 0.12s; }
.student-table tbody tr:nth-child(7) { animation-delay: 0.14s; }
.student-table tbody tr:nth-child(8) { animation-delay: 0.16s; }
.student-table tbody tr:nth-child(9) { animation-delay: 0.18s; }
.student-table tbody tr:nth-child(10) { animation-delay: 0.20s; }
</style>
</head>
<body>
<div class="page-wrap">

    {{-- PDF Loading Overlay --}}
    <div id="pdfLoading" class="pdf-loading">
        <div class="spinner"></div>
        <p>Generating PDF, please wait...</p>
    </div>

    {{-- ── TOOLBAR (no-print) ── --}}
    <div class="toolbar no-print">
        <div class="toolbar-title">
            <span style="font-size:22px;">📋</span>
            Promotion Student List
            <span style="font-size:12px;font-weight:400;color:#64748b;">
                — {{ ($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arm_name ?? '') }}
                &nbsp;·&nbsp; {{ $schoolsession->session ?? '' }}
                &nbsp;·&nbsp; {{ $schoolterm->term ?? '' }}
            </span>
        </div>
        <div class="toolbar-actions">
            <button class="btn-settings" id="toggleSettingsBtn" onclick="toggleSettings()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                </svg>
                Settings
            </button>
            <button class="btn-print" onclick="printStudentList()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 9V2h12v7"/>
                    <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Print
            </button>
            <button class="btn-pdf" onclick="exportToPDF()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <path d="M14 2v6h6"/>
                    <path d="M12 18v-4"/>
                    <path d="M9 14h6"/>
                </svg>
                Export PDF
            </button>
            <a href="javascript:window.close()" class="btn-close-tab">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
                Close
            </a>
        </div>
    </div>

    {{-- ── SETTINGS PANEL ── --}}
    <div id="settingsPanel" class="settings-panel no-print">
        <h4><span>⚙️</span> Print & Display Settings</h4>

        <div class="settings-group">
            <label>
                <span>📄 Page Orientation:</span>
                <select id="printOrientation">
                    <option value="portrait">Portrait</option>
                    <option value="landscape" selected>Landscape</option>
                </select>
            </label>
            <label>
                <span>📏 Paper Size:</span>
                <select id="paperSize">
                    <option value="A4">A4</option>
                    <option value="A3">A3</option>
                    <option value="A2">A2</option>
                    <option value="A1">A1</option>
                    <option value="Legal">Legal</option>
                    <option value="Letter">Letter</option>
                </select>
            </label>
            <label>
                <span>📄 New Page per Group:</span>
                <select id="newPagePerGroup">
                    <option value="yes" selected>Yes (Start each group on new page)</option>
                    <option value="no">No (Continue on same page)</option>
                </select>
            </label>
        </div>

        <div class="settings-group">
            <label>
                <input type="checkbox" id="showPhotosCheckbox" {{ $show_photos ? 'checked' : '' }}>
                📷 Show Student Photos
            </label>
            <label>
                <input type="checkbox" id="showSnCheckbox" {{ $show_sn ? 'checked' : '' }}>
                🔢 Show Serial Numbers
            </label>
        </div>

        <div>
            <h4 style="font-size:12px; margin-bottom:10px;">📋 Columns to Display:</h4>
            <div class="columns-grid" id="columnsGrid">
                @php
                $columnOptions = [
                    'admissionno' => 'Admission Number',
                    'firstname' => 'First Name',
                    'lastname' => 'Last Name',
                    'gender' => 'Gender',
                    'dateofbirth' => 'Date of Birth',
                    'arm' => 'Arm/Class',
                    'total_cum' => 'Cumulative Total',
                    'total_term' => 'Term Total',
                    'cum_ave' => 'Cumulative Average',
                    'position_cum' => 'Overall Position (Cum)',
                    'position_term' => 'Overall Position (Term)',
                    'gpa' => 'GPA',
                    'gpa_grade' => 'GPA Grade (Cum)',
                ];
                @endphp
                @foreach($columnOptions as $key => $label)
                    <label>
                        <input type="checkbox" class="column-checkbox" value="{{ $key }}"
                            {{ in_array($key, $list_fields) ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="settings-group" style="margin-top: 20px;">
            <button class="btn-print" onclick="applySettingsAndRefresh()" style="padding: 8px 20px; background: #7c3aed;">
                Apply Settings & Refresh
            </button>
        </div>
    </div>

    {{-- ── SCHOOL HEADER ── --}}
    <div class="school-header" id="schoolHeader">
        @if(!empty($school_logo_base64))
            <img src="{{ $school_logo_base64 }}" class="school-logo" alt="Logo">
        @else
            <div class="school-logo-placeholder">
                {{ strtoupper(substr($schoolInfo->school_name ?? 'S', 0, 2)) }}
            </div>
        @endif
        <div class="school-info">
            <div class="school-name">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</div>
            @if(!empty($schoolInfo->school_address))
                <div class="school-address">{{ $schoolInfo->school_address }}</div>
            @endif
            @if(!empty($schoolInfo->school_motto))
                <div class="school-motto">"{{ $schoolInfo->school_motto }}"</div>
            @endif
        </div>
        <div style="width:80px;flex-shrink:0;"></div>
    </div>

    {{-- ── TITLE BAR ── --}}
    <div class="list-title-bar">STUDENT PROMOTION RECOMMENDATION LIST</div>

    {{-- ── META STRIP ── --}}
    <div class="meta-strip">
        <div class="meta-cell">
            <span class="meta-label">Class</span>
            <span class="meta-value">{{ ($schoolclass->schoolclass ?? '-') . ' ' . ($schoolclass->arm_name ?? '') }}</span>
        </div>
        <div class="meta-cell">
            <span class="meta-label">Session</span>
            <span class="meta-value">{{ $schoolsession->session ?? '-' }}</span>
        </div>
        <div class="meta-cell">
            <span class="meta-label">Term</span>
            <span class="meta-value">{{ $schoolterm->term ?? '-' }}</span>
        </div>
        <div class="meta-cell">
            <span class="meta-label">Grade Basis</span>
            <span class="meta-value" style="font-size:12px;">{{ ($grade_basis ?? 'cum_ave') === 'total' ? 'Term Total' : 'Cumulative Avg' }}</span>
        </div>
        <div class="meta-cell">
            <span class="meta-label">Total Students</span>
            <span class="meta-value">{{ $totalStudents }}</span>
        </div>
        <div class="meta-cell">
            <span class="meta-label">Generated</span>
            <span class="meta-value" style="font-size:11px;">{{ $generatedAt }}</span>
        </div>
    </div>

    @php
    $allFields = [
        'admissionno'   => 'Admission No',
        'firstname'     => 'First Name',
        'lastname'      => 'Last Name',
        'gender'        => 'Gender',
        'dateofbirth'   => 'Date of Birth',
        'arm'           => 'Arm',
        'total_cum'     => 'Cum Total',
        'total_term'    => 'Term Total',
        'cum_ave'       => 'Cum Ave',
        'position_cum'  => 'Overall Pos (Cum)',
        'position_term' => 'Overall Pos (Term)',
        'gpa'           => 'GPA',
        'gpa_grade'     => 'GPA Grade',
    ];

    $statusMeta = [
        'promoted'      => ['label' => 'Promoted', 'icon' => '✅', 'class' => 'status-promoted'],
        'trial'         => ['label' => 'Promoted on Trial', 'icon' => '⚠️', 'class' => 'status-trial'],
        'see_principal' => ['label' => 'Advised to See Principal', 'icon' => '👤', 'class' => 'status-see_principal'],
        'repeated'      => ['label' => 'Advice to Repeat', 'icon' => '🔁', 'class' => 'status-repeated'],
        'awaiting'      => ['label' => 'Awaiting Decision', 'icon' => '⏳', 'class' => 'status-awaiting'],
        '__other'       => ['label' => 'Other', 'icon' => '📌', 'class' => 'status-other'],
    ];

    function listOrdinal($n) {
        if (!$n) return '—';
        $n = (int)$n;
        $s = ['th','st','nd','rd'];
        $v = $n % 100;
        return $n . ($s[($v-20)%10] ?? $s[$v] ?? $s[0]);
    }

    $globalSn = 0;
    @endphp

    {{-- Grouped student sections --}}
    <div id="studentListContent">
        @foreach($grouped_students as $statusKey => $students)
            @php
                $meta = $statusMeta[$statusKey] ?? $statusMeta['__other'];
                $groupLabel = $students[0]['promotion_label'] ?? $meta['label'];
                $groupCount = count($students);
                $hasArm = !empty($students[0]['arm']) && $students[0]['arm'] !== '—';
            @endphp
            <div class="group-section" data-status="{{ $statusKey }}">
                <div class="group-header {{ $meta['class'] }}">
                    <span style="font-size:20px;">{{ $meta['icon'] }}</span>
                    <span>{{ $groupLabel }}</span>
                    @if($hasArm)
                        <span style="font-size:12px; font-weight:normal; margin-left:8px; background:rgba(0,0,0,.05); padding:2px 10px; border-radius:20px;">
                            📍 Arm: {{ $students[0]['arm'] }}
                        </span>
                    @endif
                    <span class="count-badge">{{ $groupCount }} Student{{ $groupCount === 1 ? '' : 's' }}</span>
                </div>

                <div class="table-responsive-wrapper">
                    <table class="student-table">
                        <thead>
                            <tr>
                                @if($show_sn)
                                    <th style="width:45px;text-align:center;">#</th>
                                @endif
                                @if($show_photos)
                                    <th style="width:50px;"></th>
                                @endif

                                @if(in_array('firstname', $list_fields) && in_array('lastname', $list_fields))
                                    <th>Student Name</th>
                                @elseif(in_array('firstname', $list_fields))
                                    <th>First Name</th>
                                @elseif(in_array('lastname', $list_fields))
                                    <th>Last Name</th>
                                @endif

                                @foreach($list_fields as $field)
                                    @if(in_array($field, ['firstname','lastname','name'])) @continue @endif
                                    @if(isset($allFields[$field]))
                                        <th>{{ $allFields[$field] }}</th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $idx => $stu)
                                @php
                                    $globalSn++;
                                    $hasPic = !empty($stu['picture']) && $stu['picture'] !== 'unnamed.jpg';
                                    $imgSrc = $hasPic ? asset('storage/student_avatars/' . basename($stu['picture'])) : null;
                                    $initials = strtoupper(substr($stu['lastname']??'',0,1) . substr($stu['firstname']??'',0,1)) ?: 'ST';
                                @endphp
                                <tr>
                                    @if($show_sn)
                                        <td class="sn-cell">{{ $globalSn }}</td>
                                    @endif

                                    @if($show_photos)
                                        <td style="padding:6px 10px;width:50px;">
                                            @if($imgSrc)
                                                <img src="{{ $imgSrc }}" class="student-avatar" alt="{{ $stu['firstname'] }}"
                                                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex'">
                                                <span class="avatar-initials" style="display:none;">{{ $initials }}</span>
                                            @else
                                                <span class="avatar-initials">{{ $initials }}</span>
                                            @endif
                                        </td>
                                    @endif

                                    @if(in_array('firstname', $list_fields) && in_array('lastname', $list_fields))
                                        <td class="name-cell">
                                            {{ strtoupper($stu['lastname'] ?? '') }}, {{ $stu['firstname'] ?? '' }}
                                            @if(!empty($stu['arm']) && $stu['arm'] !== '—' && !in_array('arm', $list_fields))
                                                <span style="font-size:10px; color:#64748b; margin-left:8px; background:#f1f5f9; padding:2px 8px; border-radius:12px;">{{ $stu['arm'] }}</span>
                                            @endif
                                         </td>
                                    @elseif(in_array('firstname', $list_fields))
                                        <td class="name-cell">
                                            {{ $stu['firstname'] ?? '' }}
                                            @if(!empty($stu['arm']) && $stu['arm'] !== '—' && !in_array('arm', $list_fields))
                                                <span style="font-size:10px; color:#64748b; margin-left:8px; background:#f1f5f9; padding:2px 8px; border-radius:12px;">{{ $stu['arm'] }}</span>
                                            @endif
                                         </td>
                                    @elseif(in_array('lastname', $list_fields))
                                        <td class="name-cell">
                                            {{ strtoupper($stu['lastname'] ?? '') }}
                                            @if(!empty($stu['arm']) && $stu['arm'] !== '—' && !in_array('arm', $list_fields))
                                                <span style="font-size:10px; color:#64748b; margin-left:8px; background:#f1f5f9; padding:2px 8px; border-radius:12px;">{{ $stu['arm'] }}</span>
                                            @endif
                                         </td>
                                    @endif

                                    @foreach($list_fields as $field)
                                        @if(in_array($field, ['firstname','lastname','name'])) @continue @endif

                                        @if($field === 'admissionno')
                                            <td class="adm-cell">{{ $stu['admissionno'] ?? '—' }}</td>
                                        @elseif($field === 'gender')
                                            <td class="gender-cell">{{ $stu['gender'] ?? '—' }}</td>
                                        @elseif($field === 'dateofbirth')
                                            <td>{{ !empty($stu['dateofbirth']) ? \Carbon\Carbon::parse($stu['dateofbirth'])->format('d M Y') : '—' }}</td>
                                        @elseif($field === 'arm')
                                            <td class="arm-cell">{{ $stu['arm'] ?: '—' }}</td>
                                        @elseif($field === 'total_cum')
                                            <td style="text-align:center;font-weight:700;">{{ $stu['total_cum'] ?? '—' }}</td>
                                        @elseif($field === 'total_term')
                                            <td style="text-align:center;font-weight:700;">{{ $stu['total_term'] ?? '—' }}</td>
                                        @elseif($field === 'cum_ave')
                                            <td style="text-align:center;font-weight:700;color:#7c3aed;">{{ $stu['cum_ave'] ?? '—' }}</td>
                                        @elseif($field === 'position_cum')
                                            <td style="text-align:center;font-weight:700;color:#1e40af;">{{ listOrdinal($stu['position_cum'] ?? null) }}</td>
                                        @elseif($field === 'position_term')
                                            <td style="text-align:center;font-weight:700;color:#92400e;">{{ listOrdinal($stu['position_term'] ?? null) }}</td>
                                        @elseif($field === 'gpa')
                                            <td style="text-align:center;">{{ number_format($stu['gpa'] ?? 0, 2) }}</td>
                                        @elseif($field === 'gpa_grade')
                                            <td style="text-align:center;font-weight:700;">{{ $stu['gpa_grade'] ?? '—' }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Summary footer --}}
    @php
        $summaryGroups = [];
        foreach($grouped_students as $statusKey => $students) {
            $meta = $statusMeta[$statusKey] ?? $statusMeta['__other'];
            $label = !empty($students[0]['promotion_label']) ? $students[0]['promotion_label'] : $meta['label'];
            $summaryGroups[] = [
                'label' => $label,
                'count' => count($students),
                'bgColor' => match($statusKey) {
                    'promoted' => '#d1fae5',
                    'trial' => '#fef3c7',
                    'see_principal' => '#dbeafe',
                    'repeated' => '#fee2e2',
                    default => '#f1f5f9',
                },
                'textColor' => match($statusKey) {
                    'promoted' => '#065f46',
                    'trial' => '#92400e',
                    'see_principal' => '#1e40af',
                    'repeated' => '#991b1b',
                    default => '#475569',
                },
            ];
        }
    @endphp
    <div class="summary-footer">
        <h4><span>📊</span> Summary by Recommendation</h4>
        <div class="summary-grid">
            @foreach($summaryGroups as $sg)
                <div class="summary-item" style="background:{{ $sg['bgColor'] }};border-color:{{ $sg['bgColor'] }};">
                    <span class="summary-count" style="color:{{ $sg['textColor'] }};">{{ $sg['count'] }}</span>
                    <span class="summary-lbl" style="color:{{ $sg['textColor'] }};">{{ $sg['label'] }}</span>
                </div>
            @endforeach
            <div class="summary-item" style="background:linear-gradient(135deg,#0f2342,#1e3a5f);border-color:#0f2342;">
                <span class="summary-count" style="color:white;">{{ $totalStudents }}</span>
                <span class="summary-lbl" style="color:rgba(255,255,255,.8);">Total Students</span>
            </div>
        </div>
    </div>

    <div class="generated-line">
        Generated: {{ $generatedAt }} &nbsp;·&nbsp;
        {{ ($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arm_name ?? '') }}
        &nbsp;·&nbsp; {{ $schoolsession->session ?? '' }}
        &nbsp;·&nbsp; {{ $schoolterm->term ?? '' }}
    </div>

</div>

<script>
// Settings panel toggle
function toggleSettings() {
    var panel = document.getElementById('settingsPanel');
    var btn = document.getElementById('toggleSettingsBtn');
    panel.classList.toggle('open');
    btn.classList.toggle('active');
}

// Apply page break based on setting
function applyPageBreaks() {
    var newPagePerGroup = localStorage.getItem('new_page_per_group') || document.getElementById('newPagePerGroup')?.value || 'yes';
    var groups = document.querySelectorAll('.group-section');

    groups.forEach(function(group, index) {
        if (newPagePerGroup === 'yes') {
            if (index < groups.length - 1) {
                group.style.pageBreakAfter = 'always';
            } else {
                group.style.pageBreakAfter = 'auto';
            }
        } else {
            group.style.pageBreakAfter = 'auto';
        }
    });
}

// Print with orientation and page breaks
function printStudentList() {
    var orientation = document.getElementById('printOrientation').value;
    var paperSize = document.getElementById('paperSize').value;
    var newPagePerGroup = document.getElementById('newPagePerGroup').value;

    // Apply page breaks
    var groups = document.querySelectorAll('.group-section');
    groups.forEach(function(group, index) {
        if (newPagePerGroup === 'yes') {
            if (index < groups.length - 1) {
                group.style.pageBreakAfter = 'always';
            } else {
                group.style.pageBreakAfter = 'auto';
            }
        } else {
            group.style.pageBreakAfter = 'auto';
        }
    });

    var style = document.createElement('style');
    style.textContent = '@page { size: ' + paperSize + ' ' + orientation + '; margin: 1.2cm; }';
    document.head.appendChild(style);

    window.print();

    setTimeout(function() {
        document.head.removeChild(style);
        groups.forEach(function(group) {
            group.style.pageBreakAfter = '';
        });
    }, 100);
}

// Export to PDF
function exportToPDF() {
    var orientation = document.getElementById('printOrientation').value;
    var paperSize = document.getElementById('paperSize').value;
    var newPagePerGroup = document.getElementById('newPagePerGroup').value;

    var groups = document.querySelectorAll('.group-section');
    groups.forEach(function(group, index) {
        if (newPagePerGroup === 'yes') {
            if (index < groups.length - 1) {
                group.style.pageBreakAfter = 'always';
            } else {
                group.style.pageBreakAfter = 'auto';
            }
        } else {
            group.style.pageBreakAfter = 'auto';
        }
    });

    var loading = document.getElementById('pdfLoading');
    loading.classList.add('active');

    var style = document.createElement('style');
    style.textContent = '@page { size: ' + paperSize + ' ' + orientation + '; margin: 1.2cm; }';
    document.head.appendChild(style);

    setTimeout(function() {
        window.print();
        loading.classList.remove('active');
        setTimeout(function() {
            if (style.parentNode) document.head.removeChild(style);
            groups.forEach(function(group) {
                group.style.pageBreakAfter = '';
            });
        }, 500);
    }, 500);
}

// Apply settings and refresh - FIXED to use POST
function applySettingsAndRefresh() {
    var selectedColumns = [];
    document.querySelectorAll('.column-checkbox:checked').forEach(function(cb) {
        selectedColumns.push(cb.value);
    });

    var showPhotos = document.getElementById('showPhotosCheckbox').checked;
    var showSn = document.getElementById('showSnCheckbox').checked;
    var orientation = document.getElementById('printOrientation').value;
    var paperSize = document.getElementById('paperSize').value;
    var newPagePerGroup = document.getElementById('newPagePerGroup').value;

    localStorage.setItem('student_list_columns', JSON.stringify(selectedColumns));
    localStorage.setItem('student_list_show_photos', showPhotos);
    localStorage.setItem('student_list_show_sn', showSn);
    localStorage.setItem('student_list_orientation', orientation);
    localStorage.setItem('student_list_paper_size', paperSize);
    localStorage.setItem('new_page_per_group', newPagePerGroup);

    // Build a POST form and submit it
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.pathname;
    
    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('input[name="_token"]').value;
    form.appendChild(csrf);
    
    // Add required fields from the hidden data passed by the controller
    var requiredFields = ['schoolclassid', 'sessionid', 'termid', 'grade_basis'];
    requiredFields.forEach(function(name) {
        var hidden = document.querySelector('input[name="' + name + '"]');
        if (hidden) {
            var clone = hidden.cloneNode(true);
            form.appendChild(clone);
        }
    });
    
    // Add list fields
    selectedColumns.forEach(function(val, idx) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'list_fields[' + idx + ']';
        input.value = val;
        form.appendChild(input);
    });
    
    // Add show_photos, show_sn
    var photoInput = document.createElement('input');
    photoInput.type = 'hidden';
    photoInput.name = 'show_photos';
    photoInput.value = showPhotos ? '1' : '0';
    form.appendChild(photoInput);
    
    var snInput = document.createElement('input');
    snInput.type = 'hidden';
    snInput.name = 'show_sn';
    snInput.value = showSn ? '1' : '0';
    form.appendChild(snInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Load saved preferences
document.addEventListener('DOMContentLoaded', function() {
    var savedColumns = localStorage.getItem('student_list_columns');
    if (savedColumns) {
        var columns = JSON.parse(savedColumns);
        document.querySelectorAll('.column-checkbox').forEach(function(cb) {
            cb.checked = columns.includes(cb.value);
        });
    }

    var savedShowPhotos = localStorage.getItem('student_list_show_photos');
    if (savedShowPhotos !== null) {
        document.getElementById('showPhotosCheckbox').checked = savedShowPhotos === 'true';
    }

    var savedShowSn = localStorage.getItem('student_list_show_sn');
    if (savedShowSn !== null) {
        document.getElementById('showSnCheckbox').checked = savedShowSn === 'true';
    }

    var savedOrientation = localStorage.getItem('student_list_orientation');
    if (savedOrientation) {
        document.getElementById('printOrientation').value = savedOrientation;
    }

    var savedPaperSize = localStorage.getItem('student_list_paper_size');
    if (savedPaperSize) {
        document.getElementById('paperSize').value = savedPaperSize;
    }

    var savedNewPage = localStorage.getItem('new_page_per_group');
    if (savedNewPage) {
        document.getElementById('newPagePerGroup').value = savedNewPage;
    }

    applyPageBreaks();
});

window.onbeforeprint = function() {
    var panel = document.getElementById('settingsPanel');
    if (panel && panel.classList.contains('open')) {
        panel.style.display = 'none';
    }
};

window.onafterprint = function() {
    var panel = document.getElementById('settingsPanel');
    if (panel && panel.classList.contains('open')) {
        panel.style.display = 'block';
    }
};
</script>
</body>
</html>