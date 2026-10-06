{{-- resources/views/payment/payment-details.blade.php --}}
@extends('layouts.master')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css">

<style>
/* ── Design tokens ─────────────────────────────────────── */
:root {
    --p-navy:     #0f2744;
    --p-navy-mid: #1e3a5f;
    --p-blue:     #2563eb;
    --p-blue-lt:  #dbeafe;
    --p-green:    #16a34a;
    --p-green-lt: #dcfce7;
    --p-red:      #dc2626;
    --p-red-lt:   #fee2e2;
    --p-amber:    #d97706;
    --p-amber-lt: #fef3c7;
    --p-purple:   #7c3aed;
    --p-purple-lt:#ede9fe;
    --p-border:   #e2e8f0;
    --p-muted:    #64748b;
    --p-bg:       #f0f4f8;
    --p-surface:  #ffffff;
    --p-radius:   14px;
    --p-radius-sm:8px;
    --p-shadow:   0 1px 3px rgba(15,39,68,.06), 0 4px 16px rgba(15,39,68,.06);
    --p-shadow-lg:0 8px 32px rgba(15,39,68,.12);
    --ff:         'DM Sans', sans-serif;
    --ff-mono:    'DM Mono', monospace;
}
* { font-family: var(--ff); }

/* ── Page loader overlay ──────────────────────────────── */
#pageLoader {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(15,39,68,.55);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    flex-direction: column; gap: 14px;
}
#pageLoader .spinner {
    width: 48px; height: 48px;
    border: 4px solid rgba(255,255,255,.25);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
}
#pageLoader p { color: #fff; font-size: 14px; font-weight: 500; margin: 0; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Hero ──────────────────────────────────────────────── */
.pd-hero {
    background: linear-gradient(135deg, var(--p-navy-mid) 0%, var(--p-blue) 60%, #4f46e5 100%);
    border-radius: var(--p-radius);
    padding: 24px 28px;
    margin-bottom: 22px;
    position: relative; overflow: hidden;
}
.pd-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:200px; height:200px; background:rgba(255,255,255,.06); border-radius:50%;
}
.pd-hero::after {
    content:''; position:absolute; bottom:-70px; left:-20px;
    width:220px; height:220px; background:rgba(255,255,255,.03); border-radius:50%;
}
.pd-hero h1 {
    font-size:20px; font-weight:700; color:#fff;
    margin:0 0 4px; position:relative; z-index:1;
}
.pd-hero p {
    font-size:12px; color:rgba(255,255,255,.72); margin:0; position:relative; z-index:1;
}

/* ── Student profile card ─────────────────────────────── */
.student-profile-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    box-shadow: var(--p-shadow);
    padding: 20px 24px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.student-profile-avatar {
    width: 70px; height: 70px;
    border-radius: 16px;
    object-fit: cover;
    border: 3px solid var(--p-border);
    cursor: pointer;
    transition: transform .2s, box-shadow .2s;
    flex-shrink: 0;
}
.student-profile-avatar:hover {
    transform: scale(1.06);
    box-shadow: 0 6px 20px rgba(37,99,235,.25);
    border-color: var(--p-blue);
}
.student-profile-initials {
    width: 70px; height: 70px;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--p-blue) 0%, var(--p-purple) 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; font-weight: 700; color: white;
    cursor: pointer; flex-shrink: 0;
    transition: transform .2s, box-shadow .2s;
    border: 3px solid transparent;
}
.student-profile-initials:hover {
    transform: scale(1.06);
    box-shadow: 0 6px 20px rgba(124,58,237,.3);
}
.student-profile-info { flex: 1; min-width: 200px; }
.student-profile-name {
    font-size: 18px; font-weight: 700; color: var(--p-navy); line-height: 1.2;
}
.student-profile-meta {
    display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;
}
.profile-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 500; padding: 4px 10px;
    border-radius: 20px; background: var(--p-blue-lt); color: var(--p-blue);
    border: 1px solid #bfdbfe;
}
.profile-chip.green  { background: var(--p-green-lt);  color: var(--p-green);  border-color: #bbf7d0; }
.profile-chip.amber  { background: var(--p-amber-lt);  color: var(--p-amber);  border-color: #fde68a; }
.profile-chip.purple { background: var(--p-purple-lt); color: var(--p-purple); border-color: #ddd6fe; }

.student-profile-totals {
    display: flex; gap: 16px; flex-wrap: wrap; margin-left: auto;
}
.profile-total-item {
    text-align: center; min-width: 90px;
    background: var(--p-bg); border-radius: 10px;
    padding: 10px 14px;
}
.profile-total-label { font-size: 10px; color: var(--p-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.profile-total-value {
    font-size: 15px; font-weight: 700;
    font-family: var(--ff-mono);
    font-feature-settings: "zero" 0, "tnum" 1;
    margin-top: 3px;
}
.profile-total-value.red    { color: var(--p-red); }
.profile-total-value.green  { color: var(--p-green); }
.profile-total-value.amber  { color: var(--p-amber); }
.profile-total-value.navy   { color: var(--p-navy); }

/* ── Section card ─────────────────────────────────────── */
.pd-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    box-shadow: var(--p-shadow);
    overflow: hidden;
    margin-bottom: 22px;
}
.pd-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--p-border);
    background: #f8fafc;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: wrap;
}
.pd-card-header h5 {
    font-size: 14px; font-weight: 700; color: var(--p-navy);
    margin: 0; display: flex; align-items: center; gap: 8px;
}
.pd-card-body { padding: 20px; }

/* ── Bill item ────────────────────────────────────────── */
.bill-item {
    background: #f8fafc;
    border: 1px solid var(--p-border);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 12px;
    transition: border-color .15s, box-shadow .15s;
    position: relative;
}
.bill-item:hover {
    border-color: var(--p-blue);
    box-shadow: 0 2px 12px rgba(37,99,235,.08);
}
.bill-item.is-paid {
    border-color: #bbf7d0;
    background: linear-gradient(135deg, #f0fdf4 0%, #fff 100%);
}
.bill-item.is-partial {
    border-color: #fde68a;
    background: linear-gradient(135deg, #fffbeb 0%, #fff 100%);
}
.bill-item-header {
    display: flex; align-items: flex-start;
    justify-content: space-between; gap: 12px; flex-wrap: wrap;
}
.bill-item-title {
    font-size: 14px; font-weight: 700; color: var(--p-navy);
}
.bill-item-desc {
    font-size: 12px; color: var(--p-muted); margin-top: 3px;
}
.bill-status-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 700; padding: 4px 10px;
    border-radius: 20px; white-space: nowrap; flex-shrink: 0;
}
.bill-status-badge.paid    { background: var(--p-green-lt); color: var(--p-green); border: 1px solid #bbf7d0; }
.bill-status-badge.partial { background: var(--p-amber-lt); color: var(--p-amber); border: 1px solid #fde68a; }
.bill-status-badge.unpaid  { background: var(--p-red-lt);   color: var(--p-red);   border: 1px solid #fecaca; }

/* amounts strip */
.bill-amounts {
    display: flex; flex-wrap: wrap; gap: 16px;
    margin-top: 14px; padding-top: 14px;
    border-top: 1px dashed var(--p-border);
}
.bill-amount-col { min-width: 80px; }
.bill-amount-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--p-muted); font-weight: 600; }
.bill-amount-val {
    font-size: 14px; font-weight: 700;
    font-family: var(--ff-mono);
    font-feature-settings: "zero" 0, "tnum" 1;
    margin-top: 2px;
}
.bill-amount-val.navy   { color: var(--p-navy); }
.bill-amount-val.green  { color: var(--p-green); }
.bill-amount-val.red    { color: var(--p-red); }
.bill-amount-val.amber  { color: var(--p-amber); }
.bill-amount-val.muted  { color: var(--p-muted); text-decoration: line-through; font-weight: 400; }
.bill-amount-val.purple { color: var(--p-purple); }

/* savings row */
.savings-row {
    margin-top: 10px;
    display: flex; flex-wrap: wrap; gap: 8px;
}
.savings-tag {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600; padding: 3px 10px;
    border-radius: 20px;
}
.savings-tag.scholarship { background: var(--p-amber-lt); color: var(--p-amber); }
.savings-tag.discount    { background: var(--p-purple-lt); color: var(--p-purple); }

/* progress bar */
.bill-progress { margin-top: 12px; }
.bill-progress-bar-wrap {
    height: 6px; background: var(--p-border); border-radius: 3px; overflow: hidden;
}
.bill-progress-bar-fill {
    height: 100%; border-radius: 3px;
    background: linear-gradient(90deg, var(--p-blue), var(--p-purple));
    transition: width .5s ease;
}
.bill-progress-bar-fill.full  { background: linear-gradient(90deg, var(--p-green), #22c55e); }
.bill-progress-bar-fill.warn  { background: linear-gradient(90deg, var(--p-amber), #f59e0b); }
.bill-progress-label {
    display: flex; justify-content: space-between;
    font-size: 10px; color: var(--p-muted); margin-top: 4px;
}

/* pay button */
.btn-pay {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 600; padding: 7px 14px;
    border-radius: 8px; cursor: pointer; border: none;
    background: var(--p-blue); color: white;
    transition: background .15s, transform .1s;
}
.btn-pay:hover   { background: #1d4ed8; transform: translateY(-1px); }
.btn-pay:active  { transform: translateY(0); }
.btn-pay.success { background: var(--p-green); }
.btn-pay.success:hover { background: #15803d; }

/* ── Payment Records / History table ─────────────────── */
.pd-table { width: 100%; border-collapse: collapse; }
.pd-table th {
    background: #f8fafc; color: var(--p-muted);
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    padding: 10px 14px; border-bottom: 2px solid var(--p-border); white-space: nowrap;
}
.pd-table td {
    padding: 11px 14px; font-size: 13px; color: var(--p-navy);
    border-bottom: 1px solid var(--p-border); vertical-align: middle;
}
.pd-table tr:hover td { background: #f8fbff; }
.pd-table tr:last-child td { border-bottom: none; }

.status-pill {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px;
}
.status-pill.completed { background: var(--p-green-lt); color: var(--p-green); }
.status-pill.pending   { background: var(--p-amber-lt); color: var(--p-amber); }
.status-pill.partial   { background: var(--p-blue-lt);  color: var(--p-blue);  }

/* FIX: font-feature-settings on all mono values */
.mono-val {
    font-family: var(--ff-mono); font-size: 13px;
    font-feature-settings: "zero" 0, "tnum" 1;
}
.mono-val.red   { color: var(--p-red); font-weight: 600; }
.mono-val.green { color: var(--p-green); }

/* empty state */
.empty-state {
    text-align: center; padding: 48px 20px; color: var(--p-muted);
}
.empty-state i { font-size: 40px; display: block; margin-bottom: 10px; opacity: .35; }
.empty-state p { font-size: 13px; margin: 0; }

/* ── Scholarship / Discount sidebar ──────────────────── */
.benefit-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    box-shadow: var(--p-shadow);
    overflow: hidden;
    margin-bottom: 16px;
}
.benefit-card-header {
    padding: 12px 16px;
    font-size: 13px; font-weight: 700; color: white;
    display: flex; align-items: center; gap: 8px;
}
.benefit-card-header.amber  { background: linear-gradient(135deg, var(--p-amber), #f59e0b); }
.benefit-card-header.purple { background: linear-gradient(135deg, var(--p-purple), #8b5cf6); }
.benefit-card-body { padding: 14px 16px; }
.benefit-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 6px 0; border-bottom: 1px dashed var(--p-border);
    font-size: 12px;
}
.benefit-row:last-child { border-bottom: none; padding-bottom: 0; }
.benefit-row .label { color: var(--p-muted); font-weight: 500; }
.benefit-row .value {
    font-weight: 700; color: var(--p-navy);
    font-family: var(--ff-mono);
    font-feature-settings: "zero" 0, "tnum" 1;
}

/* ── Pay modal ────────────────────────────────────────── */
#payModal .modal-content {
    border: none; border-radius: 20px; overflow: hidden;
    box-shadow: 0 24px 64px rgba(0,0,0,.2);
}
.pay-modal-header {
    background: linear-gradient(135deg, var(--p-navy-mid) 0%, var(--p-blue) 100%);
    padding: 20px 24px; position: relative; overflow: hidden;
}
.pay-modal-header::before {
    content:''; position:absolute; top:-30px; right:-30px;
    width:120px; height:120px; background:rgba(255,255,255,.08); border-radius:50%;
}
.pay-modal-header h5 { color: #fff; font-weight: 700; margin: 0; font-size: 15px; position: relative; }
.pay-modal-header p  { color: rgba(255,255,255,.75); font-size: 12px; margin: 4px 0 0; position: relative; }
.pay-modal-header .btn-close {
    position: absolute; top: 16px; right: 18px;
    filter: invert(1); opacity: .8; z-index: 1;
}
.pay-form-label {
    font-size: 12px; font-weight: 700; color: #374151;
    text-transform: uppercase; letter-spacing: .04em;
    margin-bottom: 6px; display: flex; align-items: center; gap: 5px;
}
.pay-form-label i { color: var(--p-blue); font-size: 13px; }
.pay-form-control {
    width: 100%; border: 1.5px solid var(--p-border);
    border-radius: 10px; padding: 10px 13px;
    font-size: 13px; background: #fff;
    transition: border .15s, box-shadow .15s;
}
.pay-form-control:focus {
    border-color: var(--p-blue); outline: none;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.pay-amount-display {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 16px;
    display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;
}
.pay-amount-item .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--p-muted); font-weight: 600; }
.pay-amount-item .val {
    font-size: 15px; font-weight: 700;
    font-family: var(--ff-mono);
    font-feature-settings: "zero" 0, "tnum" 1;
}
.pay-amount-item .val.green { color: var(--p-green); }
.pay-amount-item .val.red   { color: var(--p-red); }
.pay-amount-item .val.navy  { color: var(--p-navy); }
.btn-submit-pay {
    width: 100%; background: linear-gradient(135deg, var(--p-blue), #4f46e5);
    color: #fff; border: none; border-radius: 10px;
    padding: 12px 24px; font-size: 14px; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: opacity .15s, transform .1s;
}
.btn-submit-pay:hover   { opacity: .9; transform: translateY(-1px); }
.btn-submit-pay:active  { transform: translateY(0); }
.btn-submit-pay:disabled{ opacity: .6; cursor: not-allowed; transform: none; }

/* ── Image Zoom Modal ─────────────────────────────────── */
.image-zoom-modal .modal-content {
    background: transparent; border: none; box-shadow: none;
}
.image-zoom-modal .modal-dialog { max-width: 90vw; }
.image-zoom-modal .modal-body {
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    min-height: 80vh; padding: 20px;
}
.zoomed-image {
    max-width: 90vw; max-height: 70vh;
    border-radius: 16px;
    box-shadow: 0 25px 50px rgba(0,0,0,.35);
    border: 4px solid white;
    cursor: pointer;
    animation: zoomIn .25s ease;
    object-fit: contain;
}
@keyframes zoomIn {
    from { opacity:0; transform:scale(.8); }
    to   { opacity:1; transform:scale(1); }
}
.zoom-btn-close {
    position: absolute; top: 18px; right: 28px;
    background: rgba(0,0,0,.7); border: none; border-radius: 50%;
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px; cursor: pointer; z-index: 1060;
    transition: background .15s, transform .15s;
}
.zoom-btn-close:hover { background: rgba(0,0,0,.9); transform: scale(1.1); }
.zoomed-name {
    color: white; margin-top: 16px; font-size: 16px; font-weight: 600;
    background: rgba(0,0,0,.5); padding: 6px 18px; border-radius: 40px;
}
.zoomed-details { color: rgba(255,255,255,.75); margin-top: 6px; font-size: 13px; }

/* ── Bulk pay strip ───────────────────────────────────── */
.bulk-strip {
    background: linear-gradient(135deg, var(--p-navy-mid), var(--p-blue));
    border-radius: 10px; padding: 14px 18px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap; margin-bottom: 20px;
}
.bulk-strip p   { color: rgba(255,255,255,.85); font-size: 13px; margin: 0; }
.bulk-strip p strong { color: #fff; }
.btn-bulk-pay {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 600; padding: 8px 16px;
    border-radius: 8px; cursor: pointer;
    border: 1px solid rgba(255,255,255,.4);
    background: rgba(255,255,255,.15); color: #fff;
    transition: background .15s;
    text-decoration: none;
}
.btn-bulk-pay:hover { background: rgba(255,255,255,.28); color: #fff; }

/* Responsive */
@media (max-width: 768px) {
    .student-profile-card { flex-direction: column; }
    .student-profile-totals { width: 100%; }
    .profile-total-item { flex: 1; }
}
</style>

{{-- Page loader --}}
<div id="pageLoader">
    <div class="spinner"></div>
    <p>Loading payment details…</p>
</div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="pd-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1><i class="ri-wallet-3-line me-2"></i>Payment Details</h1>
                <p id="heroSubtitle">Loading student information…</p>
            </div>
            <div class="d-flex gap-2 flex-wrap" style="position:relative;z-index:1">
                <a href="{{ route('payment.index') }}" class="btn-bulk-pay">
                    <i class="ri-arrow-left-line"></i> Back
                </a>
                <button class="btn-bulk-pay" id="printInvoiceBtn" style="display:none">
                    <i class="ri-file-text-line"></i> Invoice
                </button>
            </div>
        </div>
    </div>

    {{-- Student profile card --}}
    <div class="student-profile-card" id="studentProfileCard" style="display:none">
        <div id="profileAvatarWrap"></div>
        <div class="student-profile-info">
            <div class="student-profile-name" id="profileName">—</div>
            <div class="student-profile-meta" id="profileMeta"></div>
        </div>
        <div class="student-profile-totals" id="profileTotals"></div>
    </div>

    <div class="row g-3" id="mainContent" style="display:none">

        {{-- LEFT: Bills + payment records --}}
        <div class="col-lg-8">

            {{-- Bulk pay strip --}}
            <div class="bulk-strip" id="bulkPayStrip" style="display:none">
                <p>Pay all outstanding bills in one transaction — <strong id="bulkTotalAmount">₦0.00</strong> total</p>
                <button class="btn-bulk-pay" id="openBulkPayBtn">
                    <i class="ri-stack-line"></i> Bulk Pay
                </button>
            </div>

            {{-- Bills section --}}
            <div class="pd-card">
                <div class="pd-card-header">
                    <h5><i class="ri-receipt-line" style="color:var(--p-blue)"></i>Fee Bills</h5>
                    <span class="text-muted" style="font-size:12px" id="billTermSession"></span>
                </div>
                <div class="pd-card-body" id="billsContainer">
                    <div class="empty-state">
                        <i class="ri-inbox-line"></i>
                        <p>Loading bills…</p>
                    </div>
                </div>
            </div>

            {{-- Pending payment records --}}
            <div class="pd-card" id="pendingSection" style="display:none">
                <div class="pd-card-header">
                    <h5><i class="ri-time-line" style="color:var(--p-amber)"></i>Pending Records
                        <span class="status-pill pending ms-1" id="pendingCount"></span>
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="pd-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bill</th>
                                <th>Method</th>
                                <th class="text-end">Paid (₦)</th>
                                <th class="text-end">Balance (₦)</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="pendingTableBody"></tbody>
                    </table>
                </div>
            </div>

            {{-- Payment history --}}
            <div class="pd-card" id="historySection" style="display:none">
                <div class="pd-card-header">
                    <h5><i class="ri-history-line" style="color:var(--p-green)"></i>Payment History
                        <span class="status-pill completed ms-1" id="historyCount"></span>
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="pd-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bill</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Received By</th>
                                <th class="text-end">Paid (₦)</th>
                                <th class="text-end">Balance (₦)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody"></tbody>
                    </table>
                </div>
            </div>

        </div>{{-- /.col-lg-8 --}}

        {{-- RIGHT: Sidebar --}}
        <div class="col-lg-4">
            <div id="sidebarContent"></div>
        </div>

    </div>{{-- /.row --}}

</div>
</div>
</div>

{{-- ── Pay Modal ─────────────────────────────────────── --}}
<div class="modal fade" id="payModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content">
            <div class="pay-modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-wallet-3-line me-2"></i>Record Payment</h5>
                <p id="payModalBillTitle">—</p>
            </div>
            <div class="p-4">
                <div class="pay-amount-display" id="payAmountDisplay"></div>
                <div class="mb-3">
                    <label class="pay-form-label"><i class="ri-money-dollar-circle-line"></i>Payment Amount (₦) *</label>
                    <input type="number" id="payAmount" class="pay-form-control" placeholder="Enter amount" min="0.01" step="0.01">
                    <small class="text-muted mt-1 d-block" id="payMaxHint"></small>
                </div>
                <div class="mb-4">
                    <label class="pay-form-label"><i class="ri-bank-card-line"></i>Payment Method *</label>
                    <select id="payMethod" class="pay-form-control">
                        <option value="">— Select Method —</option>
                        <option value="Bank Deposit">Bank Deposit</option>
                        <option value="School POS">School POS</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
                <button class="btn-submit-pay" id="submitPayBtn">
                    <i class="ri-save-line"></i> Record Payment
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Bulk Pay Modal ──────────────────────────────────── --}}
<div class="modal fade" id="bulkPayModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px">
        <div class="modal-content">
            <div class="pay-modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-stack-line me-2"></i>Bulk Payment</h5>
                <p>Distribute a single payment across all unpaid bills</p>
            </div>
            <div class="p-4">
                <div id="bulkBillsSummary" class="mb-3"></div>
                <div class="mb-3">
                    <label class="pay-form-label"><i class="ri-money-dollar-circle-line"></i>Total Payment Amount (₦) *</label>
                    <input type="number" id="bulkPayAmount" class="pay-form-control" placeholder="Enter total amount" min="0.01" step="0.01">
                    <small class="text-muted mt-1 d-block" id="bulkMaxHint"></small>
                </div>
                <div class="mb-4">
                    <label class="pay-form-label"><i class="ri-bank-card-line"></i>Payment Method *</label>
                    <select id="bulkPayMethod" class="pay-form-control">
                        <option value="">— Select Method —</option>
                        <option value="Bank Deposit">Bank Deposit</option>
                        <option value="School POS">School POS</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
                <button class="btn-submit-pay" id="submitBulkPayBtn">
                    <i class="ri-save-line"></i> Record Bulk Payment
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Image Zoom Modal ─────────────────────────────────── --}}
<div class="modal fade image-zoom-modal" id="imageZoomModal" tabindex="-1" data-bs-backdrop="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button class="zoom-btn-close" data-bs-dismiss="modal"><i class="ri-close-line"></i></button>
            <div class="modal-body text-center">
                <img id="zoomedImage" src="" alt="Student Photo" class="zoomed-image">
                <div class="zoomed-name" id="zoomedName"></div>
                <div class="zoomed-details" id="zoomedDetails"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ── Route URL from Blade ── */
const PAYMENT_DETAILS_AJAX_URL = '{{ route("payment.getPaymentDetailsAjax") }}';

/* ── Route params from URL segments ── */
const studentId = {{ $studentId ?? 0 }};
const classId   = {{ $classId ?? 0 }};
const termId    = {{ $termId ?? 0 }};
const sessionId = {{ $sessionId ?? 0 }};
const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ── State ── */
let paymentData   = null;
let activeBill    = null;
let selectedBillsMap = {};

/* ── Helpers ── */
function fmt(n) {
    const num = parseFloat(String(n ?? 0).replace(/,/g, '')) || 0;
    return num.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function naira(n) { return '₦' + fmt(n); }

function getInitials(name) {
    if (!name) return 'ST';
    return name.split(' ').slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || 'ST';
}

function getAvatarUrl(picture) {
    if (!picture || picture === 'unnamed.jpg' || picture === '') return null;
    return '/storage/images/student_avatars/' + picture;
}

function formatDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return isNaN(d) ? str : d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
}

function showZoomModal(imageUrl, name, details) {
    document.getElementById('zoomedName').textContent = name || '';
    document.getElementById('zoomedDetails').innerHTML = details || '';
    const imgEl = document.getElementById('zoomedImage');

    if (imageUrl && imageUrl !== '' && imageUrl !== 'null') {
        imgEl.src = imageUrl;
        imgEl.style.display = 'block';
    } else {
        const initials = getInitials(name);
        const canvas = document.createElement('canvas');
        canvas.width = 400;
        canvas.height = 400;
        const ctx = canvas.getContext('2d');
        const g = ctx.createLinearGradient(0, 0, 400, 400);
        g.addColorStop(0, '#2563eb');
        g.addColorStop(1, '#7c3aed');
        ctx.fillStyle = g;
        ctx.beginPath();
        ctx.arc(200, 200, 200, 0, 2 * Math.PI);
        ctx.fill();
        ctx.fillStyle = '#fff';
        ctx.font = 'bold 150px "DM Sans", Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(initials, 200, 200);
        imgEl.src = canvas.toDataURL();
        imgEl.style.display = 'block';
    }
    new bootstrap.Modal(document.getElementById('imageZoomModal')).show();
}

/* ── Load data via AJAX ── */
async function loadPaymentDetails() {
    try {
        const url = `${PAYMENT_DETAILS_AJAX_URL}?studentId=${studentId}&termid=${termId}&sessionid=${sessionId}`;
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) {
            throw new Error(`Server returned ${res.status}: ${res.statusText}`);
        }

        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Load failed');

        paymentData = json.data;
        renderAll();
    } catch (e) {
        console.error(e);
        document.getElementById('pageLoader').style.display = 'none';
        document.getElementById('mainContent').innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="ri-error-warning-line me-2"></i>
                Failed to load payment details: ${e.message}
                <br><small>Please check that the student is enrolled in the selected term and session.</small>
            </div>`;
    }
}

/* ── Render everything ── */
function renderAll() {
    const d = paymentData;
    if (!d) return;

    renderHero(d);
    renderProfile(d);
    renderBills(d);
    renderPendingRecords(d);
    renderHistory(d);
    renderSidebar(d);

    document.getElementById('mainContent').style.display = '';
    document.getElementById('studentProfileCard').style.display = '';
    document.getElementById('pageLoader').style.display = 'none';
}

function renderHero(d) {
    const student = d.student || {};
    const heroText = (student.name || '—') + ' · ' + (d.term || '') + ' / ' + (d.session || '');
    document.getElementById('heroSubtitle').textContent = heroText;

    const invoiceBtn = document.getElementById('printInvoiceBtn');
    if (invoiceBtn) {
        invoiceBtn.style.display = '';
        invoiceBtn.onclick = () => {
            const classIdVal = student.schoolclassId || classId;
            window.open(`/payment/invoice/${studentId}/${classIdVal}/${termId}/${sessionId}`, '_blank');
        };
    }
}

function renderProfile(d) {
    const s = d.student || {};
    const avatarWrap = document.getElementById('profileAvatarWrap');
    const initials = getInitials(s.name);
    const avatarUrl = s.avatar ? getAvatarUrl(s.avatar) : null;
    const detailStr = `${s.admissionNo || 'N/A'} | ${s.schoolclass || ''} ${s.arm || ''}`;

    if (avatarUrl) {
        const img = document.createElement('img');
        img.src = avatarUrl;
        img.alt = s.name || '';
        img.className = 'student-profile-avatar profile-avatar-zoom';
        img.dataset.img = avatarUrl;
        img.dataset.name = s.name || '';
        img.dataset.details = detailStr;
        img.onerror = function() {
            const div = document.createElement('div');
            div.className = 'student-profile-initials profile-avatar-zoom';
            div.textContent = initials;
            div.dataset.img = '';
            div.dataset.name = s.name || '';
            div.dataset.details = detailStr;
            this.parentNode.replaceChild(div, this);
        };
        avatarWrap.innerHTML = '';
        avatarWrap.appendChild(img);
    } else {
        const div = document.createElement('div');
        div.className = 'student-profile-initials profile-avatar-zoom';
        div.textContent = initials;
        div.dataset.img = '';
        div.dataset.name = s.name || '';
        div.dataset.details = detailStr;
        avatarWrap.innerHTML = '';
        avatarWrap.appendChild(div);
    }

    document.getElementById('profileName').textContent = s.name || '—';

    const meta = document.getElementById('profileMeta');
    meta.innerHTML = `
        <span class="profile-chip"><i class="ri-hashtag"></i>${s.admissionNo || 'N/A'}</span>
        <span class="profile-chip green"><i class="ri-building-line"></i>${s.schoolclass || '—'} ${s.arm || ''}</span>
        <span class="profile-chip amber"><i class="ri-calendar-line"></i>${d.term || ''} · ${d.session || ''}</span>
        ${s.student_status ? `<span class="profile-chip purple"><i class="ri-user-star-line"></i>${s.student_status}</span>` : ''}
    `;

    const totals = d.totals || {};
    document.getElementById('profileTotals').innerHTML = `
        <div class="profile-total-item">
            <div class="profile-total-label">Total Bill</div>
            <div class="profile-total-value navy">${naira(totals.adjusted ?? totals.original ?? 0)}</div>
        </div>
        <div class="profile-total-item">
            <div class="profile-total-label">Paid</div>
            <div class="profile-total-value green">${naira(totals.paid ?? 0)}</div>
        </div>
        <div class="profile-total-item">
            <div class="profile-total-label">Outstanding</div>
            <div class="profile-total-value red">${naira(totals.outstanding ?? 0)}</div>
        </div>
        <div class="profile-total-item">
            <div class="profile-total-label">Savings</div>
            <div class="profile-total-value amber">${naira(totals.savings ?? 0)}</div>
        </div>
    `;
}

function renderBills(d) {
    const bills = d.bills || [];
    const container = document.getElementById('billsContainer');
    document.getElementById('billTermSession').textContent = (d.term || '') + ' · ' + (d.session || '');

    if (!bills.length) {
        container.innerHTML = `<div class="empty-state"><i class="ri-inbox-line"></i><p>No bills found for this term and session.</p></div>`;
        return;
    }

    // Update selectedBillsMap to only include bills that still exist and are unpaid
    const newMap = {};
    Object.keys(selectedBillsMap).forEach(id => {
        const bill = bills.find(b => String(b.id) === String(id));
        if (bill && !bill.is_paid && !bill.has_pending_invoice) {
            newMap[id] = bill;
        }
    });
    selectedBillsMap = newMap;

    // Show bulk pay strip if there are unpaid bills
    const unpaidBills = bills.filter(b => !b.is_paid && !b.has_pending_invoice);
    if (unpaidBills.length > 0) {
        const totalOutstanding = unpaidBills.reduce((s, b) => s + (b.balance || 0), 0);
        const strip = document.getElementById('bulkPayStrip');
        strip.style.display = '';
        document.getElementById('bulkTotalAmount').innerHTML = naira(totalOutstanding);
        document.getElementById('openBulkPayBtn').onclick = () => openBulkPayModal(unpaidBills);
    } else {
        document.getElementById('bulkPayStrip').style.display = 'none';
    }

    container.innerHTML = bills.map(bill => {
        const scholDeduct = bill.scholarship_deduction ?? 0;
        const discDeduct = bill.discount_deduction ?? 0;
        const totalSavings = bill.total_savings ?? 0;
        const origAmt = bill.original_amount ?? 0;
        const adjAmt = bill.adjusted_amount ?? origAmt;
        const amtPaid = bill.amount_paid ?? 0;
        const balance = bill.balance ?? 0;
        const progress = bill.progress ?? 0;
        const scholLabel = bill.scholarship_label ?? '';
        const discLabels = bill.discount_labels ?? '';
        const hasPending = bill.has_pending_invoice ?? false;
        const isSelected = !!selectedBillsMap[String(bill.id)];

        const statusBadge = bill.is_paid
            ? `<span class="bill-status-badge paid"><i class="ri-checkbox-circle-line"></i>Fully Paid</span>`
            : bill.is_partial
                ? `<span class="bill-status-badge partial"><i class="ri-time-line"></i>Partial</span>`
                : `<span class="bill-status-badge unpaid"><i class="ri-error-warning-line"></i>Unpaid</span>`;

        const itemClass = bill.is_paid ? 'is-paid' : bill.is_partial ? 'is-partial' : '';
        const progressFill = progress >= 100 ? 'full' : progress >= 40 ? '' : 'warn';

        const savingsHtml = totalSavings > 0 ? `
            <div class="savings-row">
                ${scholDeduct > 0 ? `<span class="savings-tag scholarship"><i class="ri-award-line"></i>${scholLabel || 'Scholarship'}: -${naira(scholDeduct)}</span>` : ''}
                ${discDeduct > 0 ? `<span class="savings-tag discount"><i class="ri-price-tag-3-line"></i>${discLabels || 'Discount'}: -${naira(discDeduct)}</span>` : ''}
            </div>` : '';

        const payBtnHtml = bill.is_paid
            ? `<button class="btn-pay success" disabled><i class="ri-checkbox-circle-line"></i>Paid</button>`
            : hasPending
                ? `<button class="btn-pay" disabled title="Invoice pending"><i class="ri-time-line"></i>Pending Invoice</button>`
                : `<button class="btn-pay btn-open-pay" data-bill-id="${bill.id}"><i class="ri-add-circle-line"></i>Pay</button>`;

        const checkboxHtml = (!bill.is_paid && !hasPending)
            ? `<input type="checkbox" class="bill-select-checkbox" data-bill-id="${bill.id}" ${isSelected ? 'checked' : ''} style="margin-left:8px;transform:scale(1.1);">`
            : '';

        return `
        <div class="bill-item ${itemClass}" data-bill-id="${bill.id}">
            <div class="bill-item-header">
                <div>
                    <div class="bill-item-title">${escapeHtml(bill.title) || 'N/A'}</div>
                    ${bill.description ? `<div class="bill-item-desc">${escapeHtml(bill.description)}</div>` : ''}
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${statusBadge}
                    ${payBtnHtml}
                    ${checkboxHtml}
                </div>
            </div>
            ${savingsHtml}
            <div class="bill-amounts">
                ${totalSavings > 0 ? `
                <div class="bill-amount-col">
                    <div class="bill-amount-label">Original</div>
                    <div class="bill-amount-val muted">${naira(origAmt)}</div>
                </div>
                <div class="bill-amount-col">
                    <div class="bill-amount-label">After Savings</div>
                    <div class="bill-amount-val navy">${naira(adjAmt)}</div>
                </div>` : `
                <div class="bill-amount-col">
                    <div class="bill-amount-label">Bill Amount</div>
                    <div class="bill-amount-val navy">${naira(adjAmt)}</div>
                </div>`}
                <div class="bill-amount-col">
                    <div class="bill-amount-label">Paid</div>
                    <div class="bill-amount-val green">${naira(amtPaid)}</div>
                </div>
                <div class="bill-amount-col">
                    <div class="bill-amount-label">Balance</div>
                    <div class="bill-amount-val ${balance > 0 ? 'red' : 'green'}">${naira(balance)}</div>
                </div>
                ${totalSavings > 0 ? `
                <div class="bill-amount-col">
                    <div class="bill-amount-label">Savings</div>
                    <div class="bill-amount-val amber">${naira(totalSavings)}</div>
                </div>` : ''}
            </div>
            <div class="bill-progress">
                <div class="bill-progress-bar-wrap">
                    <div class="bill-progress-bar-fill ${progressFill}" style="width:${Math.min(100, progress)}%"></div>
                </div>
                <div class="bill-progress-label">
                    <span>${Math.min(100, Math.round(progress))}% paid</span>
                    <span>${balance > 0 ? naira(balance) + ' remaining' : 'Fully settled'}</span>
                </div>
            </div>
        </div>`;
    }).join('');

    // Attach checkbox events
    document.querySelectorAll('.bill-select-checkbox').forEach(cb => {
        cb.addEventListener('change', (e) => {
            e.stopPropagation();
            const billId = cb.dataset.billId;
            const bill = bills.find(b => String(b.id) === billId);
            if (cb.checked && bill) {
                selectedBillsMap[String(billId)] = bill;
            } else {
                delete selectedBillsMap[String(billId)];
            }
            // Update bulk pay strip total
            const remainingUnpaid = Object.values(selectedBillsMap);
            const totalOutstanding = remainingUnpaid.reduce((s, b) => s + (b.balance || 0), 0);
            const strip = document.getElementById('bulkPayStrip');
            if (remainingUnpaid.length > 0) {
                strip.style.display = '';
                document.getElementById('bulkTotalAmount').innerHTML = naira(totalOutstanding);
            } else {
                strip.style.display = 'none';
            }
        });
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function renderPendingRecords(d) {
    const records = d.payment_records || [];
    const container = document.getElementById('pendingSection');
    const body = document.getElementById('pendingTableBody');

    if (!records.length) {
        container.style.display = 'none';
        return;
    }

    container.style.display = '';
    document.getElementById('pendingCount').textContent = records.length;

    body.innerHTML = records.map((r, i) => `
        <tr>
            <td><span style="color:var(--p-muted);font-size:12px">${i + 1}</span></td>
            <td><span style="font-weight:600">${escapeHtml(r.title) || '—'}</span></td>
            <td><span class="profile-chip" style="font-size:11px">${escapeHtml(r.paymentMethod) || '—'}</span></td>
            <td class="text-end"><span class="mono-val green">${naira(r.totalAmountPaid)}</span></td>
            <td class="text-end"><span class="mono-val red">${naira(r.balance)}</span></td>
            <td><span class="status-pill pending">Pending</span></td>
            <td>
                <button class="btn btn-sm btn-outline-danger delete-payment"
                        data-record-id="${r.recordId}"
                        title="Delete record">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </td>
        </tr>
    `).join('');

    // Attach delete handlers
    document.querySelectorAll('.delete-payment').forEach(btn => {
        btn.addEventListener('click', () => deletePaymentRecord(btn.dataset.recordId));
    });
}

function renderHistory(d) {
    const history = d.payment_history || [];
    const container = document.getElementById('historySection');
    const body = document.getElementById('historyTableBody');

    if (!history.length) {
        container.style.display = 'none';
        return;
    }

    container.style.display = '';
    document.getElementById('historyCount').textContent = history.length;

    body.innerHTML = history.map((r, i) => {
        const statusClass = r.paymentStatus === 'Completed' ? 'completed' : 'partial';
        const student = d.student || {};
        const invoiceUrl = `/payment/invoice/${studentId}/${student.schoolclassId || ''}/${r.termId || termId}/${r.sessionId || sessionId}`;
        return `
        <tr>
            <td><span style="color:var(--p-muted);font-size:12px">${i + 1}</span></td>
            <td><span style="font-weight:600">${escapeHtml(r.title) || '—'}</span></td>
            <td><span style="font-size:12px;color:var(--p-muted)">${formatDate(r.receivedDate)}</span></td>
            <td><span class="profile-chip" style="font-size:11px">${escapeHtml(r.paymentMethod) || '—'}</span></td>
            <td><span style="font-size:12px">${escapeHtml(r.receivedBy) || '—'}</span></td>
            <td class="text-end"><span class="mono-val green">${naira(r.totalAmountPaid)}</span></td>
            <td class="text-end"><span class="mono-val ${parseFloat(String(r.balance).replace(/,/g, '')) > 0 ? 'red' : ''}">${naira(r.balance)}</span></td>
            <td><span class="status-pill ${statusClass}">${r.paymentStatus || '—'}</span></td>
        </tr>`;
    }).join('');
}

function renderSidebar(d) {
    const sidebar = document.getElementById('sidebarContent');
    let html = '';

    // Scholarship card
    if (d.scholarship) {
        const s = d.scholarship;
        const valDisplay = s.value_type === 'percentage' ? s.value + '%' : naira(s.value);
        html += `
        <div class="benefit-card">
            <div class="benefit-card-header amber">
                <i class="ri-award-line"></i> Scholarship Applied
            </div>
            <div class="benefit-card-body">
                <div class="benefit-row">
                    <span class="label">Name</span>
                    <span class="value">${escapeHtml(s.title) || '—'}</span>
                </div>
                <div class="benefit-row">
                    <span class="label">Value</span>
                    <span class="value">${valDisplay}</span>
                </div>
                ${s.effective_to ? `
                <div class="benefit-row">
                    <span class="label">Valid Until</span>
                    <span class="value">${formatDate(s.effective_to)}</span>
                </div>` : ''}
            </div>
        </div>`;
    }

    // Discounts card
    if (d.discounts && d.discounts.length) {
        html += `
        <div class="benefit-card">
            <div class="benefit-card-header purple">
                <i class="ri-price-tag-3-line"></i> Discounts Applied
            </div>
            <div class="benefit-card-body">
                ${d.discounts.map(disc => {
                    if (!disc) return '';
                    const valDisplay = disc.value_type === 'percentage' ? disc.value + '%' : naira(disc.value);
                    return `
                    <div class="benefit-row">
                        <span class="label">${escapeHtml(disc.title) || 'Discount'}</span>
                        <span class="value">${valDisplay}</span>
                    </div>`;
                }).join('')}
            </div>
        </div>`;
    }

    // Summary card
    const totals = d.totals || {};
    html += `
    <div class="pd-card">
        <div class="pd-card-header">
            <h5><i class="ri-bar-chart-line" style="color:var(--p-blue)"></i>Summary</h5>
        </div>
        <div class="pd-card-body">
            <div class="benefit-row">
                <span class="label">Original Bill</span>
                <span class="value">${naira(totals.original ?? 0)}</span>
            </div>
            ${(totals.savings ?? 0) > 0 ? `
            <div class="benefit-row">
                <span class="label" style="color:var(--p-amber)">Total Savings</span>
                <span class="value" style="color:var(--p-amber)">-${naira(totals.savings)}</span>
            </div>
            <div class="benefit-row">
                <span class="label">Adjusted Bill</span>
                <span class="value">${naira(totals.adjusted ?? 0)}</span>
            </div>` : ''}
            <div class="benefit-row">
                <span class="label" style="color:var(--p-green)">Total Paid</span>
                <span class="value" style="color:var(--p-green)">${naira(totals.paid ?? 0)}</span>
            </div>
            <div class="benefit-row" style="border-top:2px solid var(--p-border);margin-top:4px;padding-top:10px">
                <span class="label" style="color:var(--p-red);font-weight:700">Outstanding</span>
                <span class="value" style="color:var(--p-red);font-size:16px">${naira(totals.outstanding ?? 0)}</span>
            </div>
        </div>
    </div>`;

    // Quick actions
    const student = d.student || {};
    html += `
    <div class="pd-card">
        <div class="pd-card-header">
            <h5><i class="ri-settings-3-line" style="color:var(--p-muted)"></i>Actions</h5>
        </div>
        <div class="pd-card-body d-grid gap-2">
            <a href="/payment/invoice/${studentId}/${student.schoolclassId || ''}/${termId}/${sessionId}"
               target="_blank"
               class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 justify-content-center">
                <i class="ri-file-text-line"></i> View / Print Invoice
            </a>
            <a href="/payment/statement/${studentId}/${student.schoolclassId || ''}/${termId}/${sessionId}"
               target="_blank"
               class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2 justify-content-center">
                <i class="ri-file-list-3-line"></i> Download Statement
            </a>
        </div>
    </div>`;

    sidebar.innerHTML = html;
}

/* ── Pay button handler ── */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-open-pay');
    if (!btn) return;
    const billId = btn.dataset.billId;
    const bill = paymentData?.bills?.find(b => String(b.id) === String(billId));
    if (bill) openPayModal(bill);
});

/* ── Profile avatar click ── */
document.addEventListener('click', (e) => {
    const el = e.target.closest('.profile-avatar-zoom');
    if (!el) return;
    showZoomModal(el.dataset.img || '', el.dataset.name || '', el.dataset.details || '');
});

/* ── Zoomed image click to close ── */
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('zoomed-image')) {
        bootstrap.Modal.getInstance(document.getElementById('imageZoomModal'))?.hide();
    }
});

/* ── Open Pay Modal ── */
function openPayModal(bill) {
    activeBill = bill;

    const adjAmt = bill.adjusted_amount ?? bill.original_amount ?? 0;
    const amtPaid = bill.amount_paid ?? 0;
    const balance = bill.balance ?? Math.max(0, adjAmt - amtPaid);

    document.getElementById('payModalBillTitle').textContent = bill.title || '—';
    document.getElementById('payAmountDisplay').innerHTML = `
        <div class="pay-amount-item">
            <div class="lbl">Bill Amount</div>
            <div class="val navy">${naira(adjAmt)}</div>
        </div>
        <div class="pay-amount-item">
            <div class="lbl">Already Paid</div>
            <div class="val green">${naira(amtPaid)}</div>
        </div>
        <div class="pay-amount-item">
            <div class="lbl">Balance Due</div>
            <div class="val red">${naira(balance)}</div>
        </div>
    `;

    document.getElementById('payAmount').value = '';
    document.getElementById('payAmount').max = balance;
    document.getElementById('payMaxHint').textContent = `Maximum: ${naira(balance)}`;
    document.getElementById('payMethod').value = '';
    document.getElementById('submitPayBtn').disabled = false;
    document.getElementById('submitPayBtn').innerHTML = '<i class="ri-save-line"></i> Record Payment';

    new bootstrap.Modal(document.getElementById('payModal')).show();
}

/* ── Submit Payment ── */
document.getElementById('submitPayBtn').addEventListener('click', async () => {
    const amount = parseFloat(document.getElementById('payAmount').value);
    const method = document.getElementById('payMethod').value;
    const bill = activeBill;

    if (!amount || amount <= 0) {
        Swal.fire('Error', 'Please enter a valid amount.', 'error');
        return;
    }
    if (!method) {
        Swal.fire('Error', 'Please select a payment method.', 'error');
        return;
    }

    const adjAmt = bill.adjusted_amount ?? bill.original_amount ?? 0;
    const amtPaid = bill.amount_paid ?? 0;
    const balance = bill.balance ?? Math.max(0, adjAmt - amtPaid);

    if (amount > balance + 0.01) {
        Swal.fire('Error', `Amount cannot exceed the balance of ${naira(balance)}.`, 'error');
        return;
    }

    const btn = document.getElementById('submitPayBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing…';

    const formData = new FormData();
    formData.append('_token', CSRF);
    formData.append('student_id', studentId);
    formData.append('class_id', bill.class_id || classId);
    formData.append('term_id', termId);
    formData.append('session_id', sessionId);
    formData.append('school_bill_id', bill.id);
    formData.append('actual_amount', bill.original_amount ?? adjAmt);
    formData.append('adjusted_amount', adjAmt);
    formData.append('balance2', balance);
    formData.append('last_amount_paid', amtPaid);
    formData.append('payment_amount', amount);
    formData.append('payment_amount2', amount);
    formData.append('payment_method2', method);
    formData.append('scholarship_deduction', bill.scholarship_deduction ?? 0);
    formData.append('discount_deduction', bill.discount_deduction ?? 0);

    try {
        const res = await fetch('/payment/store', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('payModal')).hide();
            await Swal.fire({
                icon: 'success',
                title: 'Payment Recorded!',
                text: json.message,
                timer: 2500,
                showConfirmButton: false
            });
            loadPaymentDetails();
        } else {
            Swal.fire('Error', json.message || 'Payment failed.', 'error');
        }
    } catch (e) {
        console.error('Payment error:', e);
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-line"></i> Record Payment';
    }
});

/* ── Open Bulk Pay Modal ── */
function openBulkPayModal(unpaidBills) {
    const bills = unpaidBills || (paymentData?.bills || []).filter(b => !b.is_paid && !b.has_pending_invoice);
    const total = bills.reduce((s, b) => s + (b.balance ?? 0), 0);

    if (bills.length === 0) {
        Swal.fire('Info', 'No unpaid bills available for bulk payment.', 'info');
        return;
    }

    document.getElementById('bulkBillsSummary').innerHTML = `
        <div class="pay-amount-display">
            <div class="pay-amount-item">
                <div class="lbl">Unpaid Bills</div>
                <div class="val navy">${bills.length}</div>
            </div>
            <div class="pay-amount-item">
                <div class="lbl">Total Outstanding</div>
                <div class="val red">${naira(total)}</div>
            </div>
        </div>
        <div style="font-size:12px;color:var(--p-muted);margin-bottom:12px">
            Payment will be distributed across: ${bills.map(b => `<strong>${escapeHtml(b.title)}</strong>`).join(', ')}
        </div>
    `;

    document.getElementById('bulkPayAmount').value = '';
    document.getElementById('bulkPayAmount').max = total;
    document.getElementById('bulkMaxHint').textContent = `Maximum: ${naira(total)}`;
    document.getElementById('bulkPayMethod').value = '';
    document.getElementById('submitBulkPayBtn').disabled = false;
    document.getElementById('submitBulkPayBtn').innerHTML = '<i class="ri-save-line"></i> Record Bulk Payment';

    // Store bills for bulk payment
    window.bulkBills = bills;

    new bootstrap.Modal(document.getElementById('bulkPayModal')).show();
}

/* ── Submit Bulk Payment ── */
document.getElementById('submitBulkPayBtn').addEventListener('click', async () => {
    const amount = parseFloat(document.getElementById('bulkPayAmount').value);
    const method = document.getElementById('bulkPayMethod').value;
    const bills = window.bulkBills || [];

    if (!amount || amount <= 0) {
        Swal.fire('Error', 'Please enter a valid amount.', 'error');
        return;
    }
    if (!method) {
        Swal.fire('Error', 'Please select a payment method.', 'error');
        return;
    }

    const total = bills.reduce((s, b) => s + (b.balance ?? 0), 0);
    if (amount > total + 0.01) {
        Swal.fire('Error', `Amount cannot exceed ${naira(total)}.`, 'error');
        return;
    }

    const btn = document.getElementById('submitBulkPayBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing…';

    const payload = {
        _token: CSRF,
        student_id: studentId,
        class_id: classId,
        term_id: termId,
        session_id: sessionId,
        payment_amount: amount,
        payment_method: method,
        bill_payments: bills.map(b => ({
            school_bill_id: b.id,
            title: b.title,
            adjusted_amount: b.adjusted_amount ?? b.original_amount ?? 0,
            balance: b.balance ?? 0,
            scholarship_deduction: b.scholarship_deduction ?? 0,
            discount_deduction: b.discount_deduction ?? 0,
        })),
    };

    try {
        const res = await fetch('/payment/bulk-store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();

        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('bulkPayModal')).hide();
            await Swal.fire({
                icon: 'success',
                title: 'Bulk Payment Recorded!',
                text: json.message,
                timer: 2500,
                showConfirmButton: false
            });
            selectedBillsMap = {};
            loadPaymentDetails();
        } else {
            Swal.fire('Error', json.message || 'Bulk payment failed.', 'error');
        }
    } catch (e) {
        console.error('Bulk payment error:', e);
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-line"></i> Record Bulk Payment';
    }
});

/* ── Delete Payment Record ── */
async function deletePaymentRecord(recordId) {
    const result = await Swal.fire({
        title: 'Delete Payment Record?',
        text: 'This will reverse the payment amount. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
    });
    if (!result.isConfirmed) return;

    try {
        const res = await fetch(`/payment/delete/${recordId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
        const json = await res.json();
        if (json.success) {
            Swal.fire({ icon: 'success', title: 'Deleted!', text: json.message, timer: 1500, showConfirmButton: false });
            loadPaymentDetails();
        } else {
            Swal.fire('Error', json.message || 'Delete failed.', 'error');
        }
    } catch (e) {
        console.error('Delete error:', e);
        Swal.fire('Error', 'Something went wrong.', 'error');
    }
}

/* ── Boot ── */
document.addEventListener('DOMContentLoaded', loadPaymentDetails);
</script>
@endsection
