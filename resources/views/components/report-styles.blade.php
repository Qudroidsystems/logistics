<style>
:root {
    --report-primary: #1e3a5f;
    --report-accent: #2563eb;
    --report-success: #16a34a;
    --report-warning: #d97706;
    --report-danger: #dc2626;
    --report-purple: #7c3aed;
    --report-muted: #6b7280;
    --report-border: #e2e8f0;
    --report-bg: #f8fafc;
    --report-radius: 12px;
    --report-shadow: 0 2px 8px rgba(0,0,0,.08);
    --report-shadow-lg: 0 8px 32px rgba(0,0,0,.12);
}

/* Hero Banner */
.report-hero {
    background: linear-gradient(135deg, var(--report-primary) 0%, var(--report-accent) 60%, #4f46e5 100%);
    border-radius: var(--report-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.report-hero::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 220px;
    height: 220px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.report-hero::after {
    content: '';
    position: absolute;
    bottom: -80px;
    left: -30px;
    width: 260px;
    height: 260px;
    background: rgba(255,255,255,.03);
    border-radius: 50%;
}
.report-hero h1 {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 6px;
    position: relative;
}
.report-hero p {
    font-size: 13px;
    color: rgba(255,255,255,.75);
    margin: 0;
    position: relative;
}

/* Stat Cards */
.stat-card {
    background: #fff;
    border: 1px solid var(--report-border);
    border-radius: var(--report-radius);
    padding: 18px 20px;
    transition: transform .15s, box-shadow .15s;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--report-shadow);
}
.stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--report-primary);
}
.stat-card .stat-label {
    font-size: 12px;
    color: var(--report-muted);
    margin-top: 4px;
}
.stat-card .stat-icon {
    font-size: 32px;
    opacity: .12;
    float: right;
    margin-top: -8px;
}

/* Filter Bar */
.filter-bar {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}
.filter-label {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 8px;
    color: var(--report-primary);
}
.filter-label .required {
    color: #dc2626;
}

/* Table Styles */
.report-table th {
    background: var(--report-primary);
    color: #fff;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 13px;
    white-space: nowrap;
}
.report-table td {
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--report-border);
    font-size: 13px;
}
.report-table tr:hover td {
    background: #eff6ff;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.status-success { background: #dcfce7; color: #16a34a; }
.status-warning { background: #fef3c7; color: #d97706; }
.status-danger { background: #fee2e2; color: #dc2626; }
.status-info { background: #dbeafe; color: #2563eb; }

/* Completion Progress */
.completion-progress {
    display: flex;
    align-items: center;
    gap: 8px;
}
.completion-progress .progress {
    flex: 1;
    height: 6px;
    border-radius: 10px;
    background: #e2e8f0;
    overflow: hidden;
}
.completion-progress .progress-bar {
    border-radius: 10px;
    transition: width 0.3s ease;
}
.completion-progress .progress-bar.high { background: linear-gradient(90deg, #16a34a, #22c55e); }
.completion-progress .progress-bar.medium { background: linear-gradient(90deg, #d97706, #f59e0b); }
.completion-progress .progress-bar.low { background: linear-gradient(90deg, #dc2626, #ef4444); }
.completion-progress span {
    font-size: 11px;
    font-weight: 600;
    min-width: 45px;
}

/* Student Avatar */
.student-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--report-border);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.student-avatar:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(37,99,235,.3);
    border-color: var(--report-accent);
}
.student-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--report-accent), #4f46e5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    color: white;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.student-avatar-placeholder:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(124,58,237,.3);
}

/* Bill Card (for class analysis) */
.bill-card {
    background: #fff;
    border: 1px solid var(--report-border);
    border-radius: 12px;
    padding: 18px 20px;
    height: 100%;
    transition: transform .15s, box-shadow .15s;
}
.bill-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--report-shadow);
}
.bill-card .stripe {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}
.bill-card.paid .stripe { background: linear-gradient(90deg, #16a34a, #15803d); }
.bill-card.partial .stripe { background: linear-gradient(90deg, #2563eb, #1d4ed8); }
.bill-card.unpaid .stripe { background: linear-gradient(90deg, #d97706, #b45309); }

/* Image Zoom Modal */
.image-zoom-modal .modal-content {
    background: transparent;
    border: none;
    box-shadow: none;
}
.image-zoom-modal .modal-dialog {
    max-width: 90vw;
    margin: 1.75rem auto;
}
.image-zoom-modal .modal-body {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    padding: 20px;
}
.zoomed-image {
    max-width: 90vw;
    max-height: 75vh;
    border-radius: 16px;
    box-shadow: 0 25px 50px rgba(0,0,0,.35);
    border: 4px solid white;
    cursor: pointer;
    animation: zoomIn .25s ease;
    object-fit: contain;
}
@keyframes zoomIn {
    from { opacity: 0; transform: scale(.8); }
    to { opacity: 1; transform: scale(1); }
}
.image-zoom-modal .btn-close-zoom {
    position: absolute;
    top: 20px;
    right: 30px;
    background: rgba(0,0,0,.7);
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    cursor: pointer;
    z-index: 1060;
    transition: background .15s, transform .15s;
}
.image-zoom-modal .btn-close-zoom:hover {
    background: rgba(0,0,0,.9);
    transform: scale(1.1);
}
.zoomed-image-name {
    color: white;
    margin-top: 18px;
    font-size: 17px;
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(0,0,0,.3);
    background: rgba(0,0,0,.5);
    padding: 7px 20px;
    border-radius: 40px;
    display: inline-block;
}
.zoomed-image-details {
    color: rgba(255,255,255,.8);
    margin-top: 8px;
    font-size: 13px;
    text-align: center;
}

/* DataTables Overrides */
.dataTables_wrapper .dataTables_filter input {
    border: 1.5px solid var(--report-border);
    border-radius: 8px;
    padding: 7px 14px;
    margin-left: 8px;
    font-size: 13px;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--report-accent);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border: 1.5px solid var(--report-border);
    border-radius: 8px;
    padding: 6px 10px;
    margin: 0 6px;
    font-size: 13px;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background: var(--report-accent) !important;
    border-color: var(--report-accent) !important;
    color: #fff !important;
}

/* Print Styles */
@media print {
    .no-print, .filter-bar, .btn, .dataTables_filter, .dataTables_length, .dataTables_paginate {
        display: none !important;
    }
    .report-hero {
        background: var(--report-primary) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .stat-card, .report-table th {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
