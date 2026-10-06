{{-- resources/views/schoolpayment/studentpayment.blade.php --}}
@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
:root {
    --pay-primary: #1e3a5f;
    --pay-accent:  #2563eb;
    --pay-success: #16a34a;
    --pay-warning: #d97706;
    --pay-danger:  #dc2626;
    --pay-purple:  #7c3aed;
    --pay-muted:   #6b7280;
    --pay-border:  #e2e8f0;
    --pay-bg:      #f8fafc;
    --pay-radius:  12px;
    --pay-shadow:  0 2px 8px rgba(0,0,0,.08);
}

/* Loading overlay */
.loading-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); z-index: 9999;
    display: none; align-items: center; justify-content: center;
}
.loading-overlay.active { display: flex; }
.loading-spinner {
    background: white; padding: 24px 32px; border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.18); text-align: center;
}
.loading-spinner .spinner-border { width: 2.5rem; height: 2.5rem; }
.loading-spinner p { margin: 10px 0 0; font-size: 14px; font-weight: 600; color: var(--pay-primary); }

/* Hero */
.pay-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--pay-radius); padding: 24px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.pay-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px; background: rgba(255,255,255,.06); border-radius: 50%;
}
.pay-hero h1 { font-size: 20px; font-weight: 700; color: #fff; margin: 0 0 4px; position: relative; }
.pay-hero p  { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; position: relative; }

/* Student card */
.student-card {
    background: #fff; border: 1px solid var(--pay-border);
    border-radius: var(--pay-radius); padding: 20px 24px;
    margin-bottom: 20px; box-shadow: var(--pay-shadow);
}
.student-avatar-lg {
    width: 72px; height: 72px; border-radius: 50%;
    object-fit: cover; border: 3px solid var(--pay-border); background: #f0f0f0;
}
.avatar-placeholder-lg {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(135deg, #dbeafe, #93c5fd);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 700; color: var(--pay-accent);
    border: 3px solid var(--pay-border); flex-shrink: 0;
}
.info-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--pay-bg); border: 1px solid var(--pay-border);
    border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600;
}
.info-chip i { opacity: .7; }

/* Benefit banners */
.benefit-banner {
    border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;
    display: flex; align-items: flex-start; gap: 12px; font-size: 13px;
}
.benefit-banner.schol { background: #fef9c3; border: 1px solid #fde68a; color: #92400e; }
.benefit-banner.disc  { background: #ede9fe; border: 1px solid #ddd6fe; color: #6d28d9; }
.benefit-banner .icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }

/* Fallback warning banner */
.fallback-banner {
    background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412;
    border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;
    display: flex; align-items: flex-start; gap: 10px; font-size: 13px;
}
.fallback-banner i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

/* Arrears banner */
.arrears-banner {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    text-decoration: none;
}
.arrears-banner:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(220, 38, 38, .12);
    text-decoration: none;
}
.arrears-banner .arrears-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: #fecaca; color: #dc2626;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.arrears-banner .arrears-body { flex: 1; min-width: 200px; }
.arrears-banner .arrears-title {
    font-size: 14px; font-weight: 700; color: #991b1b; margin-bottom: 2px;
}
.arrears-banner .arrears-meta {
    font-size: 12px; color: #b91c1c;
}
.arrears-banner .arrears-amount {
    font-size: 22px; font-weight: 800; color: #dc2626; white-space: nowrap;
}
.arrears-banner .arrears-cta {
    font-size: 12px; font-weight: 600; color: #dc2626;
    display: inline-flex; align-items: center; gap: 4px;
}

/* Bill cards */
.bill-card {
    background: #fff; border: 1px solid var(--pay-border);
    border-radius: 12px; padding: 18px 20px; height: 100%;
    position: relative; overflow: hidden;
    transition: transform .15s, box-shadow .15s;
}
.bill-card:hover { transform: translateY(-2px); box-shadow: var(--pay-shadow); }
.bill-card .stripe { position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.bill-card.paid    .stripe { background: linear-gradient(90deg, #16a34a, #15803d); }
.bill-card.partial .stripe { background: linear-gradient(90deg, #2563eb, #1d4ed8); }
.bill-card.unpaid  .stripe { background: linear-gradient(90deg, #d97706, #b45309); }
.bill-card.savings .stripe { background: linear-gradient(90deg, #7c3aed, #6d28d9); }
.bill-card.selected {
    border: 2px solid #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.15);
}

.bill-amount-main { font-size: 22px; font-weight: 700; color: var(--pay-primary); }
.bill-mini-label  { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
.bill-mini-value  { font-size: 13px; font-weight: 700; }

.schol-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fef9c3; border: 1px solid #fde68a;
    color: #92400e; border-radius: 20px; padding: 2px 9px;
    font-size: 11px; font-weight: 600;
}
.disc-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: #ede9fe; border: 1px solid #ddd6fe;
    color: #6d28d9; border-radius: 20px; padding: 2px 9px;
    font-size: 11px; font-weight: 600;
}
.select-hint {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; color: var(--pay-muted);
    background: #f8fafc; border: 1px dashed var(--pay-border);
    border-radius: 6px; padding: 3px 8px; margin-top: 4px;
}

/* Progress bar */
.progress { height: 6px; border-radius: 10px; background: #e2e8f0; overflow: hidden; }
.progress-bar-paid    { background: linear-gradient(90deg, #16a34a, #15803d); border-radius: 10px; height: 6px; }
.progress-bar-partial { background: linear-gradient(90deg, #2563eb, #1d4ed8); border-radius: 10px; height: 6px; }

/* Tabs */
.nav-tabs .nav-link {
    color: var(--pay-muted); font-size: 13px; font-weight: 600;
    border: none; border-bottom: 2px solid transparent; padding: 10px 16px;
}
.nav-tabs .nav-link.active {
    color: var(--pay-accent); border-bottom-color: var(--pay-accent); background: transparent;
}

/* Record tables */
.rec-table th {
    background: var(--pay-bg); color: var(--pay-primary);
    padding: 10px 14px; font-size: 12px; font-weight: 700;
    white-space: nowrap; border-bottom: 2px solid var(--pay-border);
}
.rec-table td {
    padding: 10px 14px; vertical-align: middle;
    font-size: 13px; border-bottom: 1px solid var(--pay-border);
}
.rec-table tr:hover td { background: #f0f9ff; }

/* Empty state */
.empty-state { text-align: center; padding: 52px 24px; color: var(--pay-muted); }
.empty-state i { font-size: 3rem; opacity: .25; display: block; margin-bottom: 14px; }
.empty-state p { margin: 0; font-size: 14px; }

/* Payment modals */
#paymentModal .modal-content,
#bulkPaymentModal .modal-content {
    border: none; border-radius: 16px; overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.15);
}
.modal-hero-bar {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    padding: 20px 28px; position: relative; overflow: hidden;
}
.modal-hero-bar::before {
    content: ''; position: absolute; top: -25px; right: -25px;
    width: 100px; height: 100px; background: rgba(255,255,255,.07); border-radius: 50%;
}
.modal-hero-bar h5 { color: #fff; font-weight: 700; margin: 0; font-size: 15px; position: relative; }
.modal-hero-bar .btn-close { position: absolute; top: 16px; right: 20px; filter: invert(1); }

.savings-breakdown {
    background: linear-gradient(135deg, #f3e8ff, #ede9fe);
    border: 1px solid #ddd6fe; border-radius: 10px;
    padding: 12px 16px; margin-bottom: 14px;
}
.savings-breakdown .title { font-size: 12px; font-weight: 700; color: #7c3aed; margin-bottom: 8px; }
.savings-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px; }
.savings-row:last-child { margin-bottom: 0; border-top: 1px solid #ddd6fe; padding-top: 6px; font-weight: 700; }

.form-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.form-control, .form-select {
    border: 1.5px solid var(--pay-border); border-radius: 8px;
    font-size: 13px; padding: 9px 14px; transition: border .15s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--pay-accent); box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.form-control[readonly] { background: var(--pay-bg); cursor: default; }

/* Amount display in modal */
.amount-display-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid var(--pay-border);
    font-size: 13px;
}
.amount-display-row:last-child { border-bottom: none; }
.amount-display-row .label { color: var(--pay-muted); }
.amount-display-row .value { font-weight: 700; color: var(--pay-primary); }
.amount-display-row .value.text-success { color: var(--pay-success); }
.amount-display-row .value.text-danger { color: var(--pay-danger); }

/* OPay-style currency input */
.currency-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: #fff;
    border: 2px solid var(--pay-border);
    border-radius: 12px;
    transition: border-color .15s, box-shadow .15s;
    overflow: hidden;
}
.currency-input-wrapper:focus-within {
    border-color: var(--pay-accent);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
}
.currency-input-wrapper.has-error {
    border-color: var(--pay-danger);
    box-shadow: 0 0 0 4px rgba(220, 38, 38, .1);
}
.currency-input-wrapper .currency-symbol {
    position: absolute;
    left: 14px;
    font-size: 20px;
    font-weight: 700;
    color: var(--pay-primary);
    pointer-events: none;
    z-index: 1;
    line-height: 1;
}
.currency-input-wrapper .currency-amount {
    width: 100%;
    border: none;
    padding: 14px 16px 14px 40px;
    font-size: 26px;
    font-weight: 700;
    color: var(--pay-primary);
    background: transparent;
    outline: none;
    letter-spacing: 0.3px;
    font-variant-numeric: tabular-nums;
}
.currency-input-wrapper .currency-amount::placeholder {
    color: #d1d5db;
    font-weight: 500;
    font-size: 20px;
}
.currency-input-wrapper .currency-amount:focus {
    box-shadow: none;
}

/* Word equivalent */
.word-equivalent {
    font-size: 12px;
    color: var(--pay-muted);
    padding: 8px 12px;
    background: var(--pay-bg);
    border-radius: 8px;
    border: 1px dashed var(--pay-border);
    min-height: 36px;
    display: flex;
    align-items: flex-start;
    gap: 6px;
    margin-top: 8px;
}
.word-equivalent .label {
    font-weight: 600;
    color: var(--pay-primary);
    white-space: nowrap;
    padding-top: 1px;
}
.word-equivalent .value {
    color: var(--pay-accent);
    font-weight: 500;
    word-break: break-word;
    line-height: 1.4;
}

/* Quick amount buttons */
.quick-amount-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}
.quick-amount-btn {
    padding: 6px 14px;
    border-radius: 20px;
    border: 1.5px solid var(--pay-border);
    background: #fff;
    font-size: 12px;
    font-weight: 600;
    color: var(--pay-muted);
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}
.quick-amount-btn:hover {
    border-color: var(--pay-accent);
    color: var(--pay-accent);
    background: #eff6ff;
}
.quick-amount-btn:active { transform: scale(0.96); }
.quick-amount-btn.full-amount {
    border-color: var(--pay-success);
    color: var(--pay-success);
}
.quick-amount-btn.full-amount:hover { background: #f0fdf4; }
.quick-amount-btn.is-active {
    background: #eff6ff;
    border-color: var(--pay-accent);
    color: var(--pay-accent);
}

/* Bulk payment */
.bulk-summary {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 12px; padding: 16px; margin-bottom: 20px;
}
.bulk-summary-item {
    display: flex; justify-content: space-between;
    padding: 8px 0; border-bottom: 1px solid #dcfce7;
}
.bulk-summary-item:last-child {
    border-bottom: none; font-weight: 700; color: var(--pay-primary);
}

/* Bulk distribution preview */
.distribution-preview {
    background: #f8fafc;
    border: 1px solid var(--pay-border);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 8px;
}
.distribution-preview .dist-header {
    display: flex;
    justify-content: space-between;
    padding: 10px 14px;
    background: #f1f5f9;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--pay-muted);
    border-bottom: 1px solid var(--pay-border);
}
.distribution-preview .dist-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid var(--pay-border);
    font-size: 13px;
}
.distribution-preview .dist-row:last-child { border-bottom: none; }
.distribution-preview .dist-row .bill-name {
    font-weight: 600;
    color: var(--pay-primary);
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding-right: 12px;
}
.distribution-preview .dist-row .bill-pay {
    font-weight: 700;
    color: var(--pay-success);
    white-space: nowrap;
}
.distribution-preview .dist-row .bill-new-bal {
    font-size: 11px;
    color: var(--pay-muted);
    white-space: nowrap;
}
.distribution-preview .dist-footer {
    display: flex;
    justify-content: space-between;
    padding: 10px 14px;
    background: #ecfdf5;
    border-top: 1px solid #bbf7d0;
    font-size: 13px;
    font-weight: 700;
    color: var(--pay-primary);
}
.distribution-preview .dist-remainder {
    background: #fff7ed;
    border-top: 1px solid #fed7aa;
    padding: 8px 14px;
    font-size: 12px;
    color: #9a3412;
    font-weight: 600;
}

@media (max-width: 576px) {
    .currency-input-wrapper .currency-amount {
        font-size: 22px;
        padding: 12px 14px 12px 36px;
    }
    .currency-input-wrapper .currency-symbol {
        font-size: 18px;
        left: 12px;
    }
    .quick-amount-btn {
        font-size: 11px;
        padding: 3px 10px;
    }
    .word-equivalent {
        font-size: 11px;
        padding: 4px 10px;
    }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading…</span>
            </div>
            <p>Processing…</p>
        </div>
    </div>

    <div class="pay-hero">
        <h1><i class="ri-wallet-line me-2"></i>Payment Details</h1>
        <p>Manage school fee payments for the selected student, term, and session.</p>
    </div>

    <div id="paymentContent">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading…</span>
            </div>
            <p class="mt-3 text-muted">Loading payment data…</p>
        </div>
    </div>

</div>
</div>
</div>

{{-- BULK PAYMENT MODAL --}}
<div class="modal fade" id="bulkPaymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:620px">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-wallet-3-line me-2"></i>Bulk Payment</h5>
            </div>
            <div class="modal-body p-4">
                <div id="selectedBillsSummary"></div>
                <div class="bulk-summary">
                    <div class="bulk-summary-item">
                        <span>Total Payable (selected bills):</span>
                        <span class="fw-bold" id="bulkTotalPayable">₦0</span>
                    </div>
                    <div class="bulk-summary-item">
                        <span>Total Savings Applied:</span>
                        <span class="fw-bold text-success" id="bulkTotalSavings">₦0</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Payment Amount <span class="text-danger">*</span></label>
                    <div class="currency-input-wrapper">
                        <span class="currency-symbol">₦</span>
                        <input type="text" id="bulk_payment_amount" class="currency-amount"
                               placeholder="0.00" autocomplete="off" inputmode="decimal">
                    </div>
                    <div class="word-equivalent">
                        <span class="label">In Words:</span>
                        <span class="value" id="bulkWordEquivalent">—</span>
                    </div>
                    <div class="form-text text-muted">Amount is distributed across selected bills in order.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select id="bulk_payment_method" class="form-select">
                        <option value="">— Select Method —</option>
                        <option value="Bank Deposit">Bank Deposit / Teller</option>
                        <option value="School POS">School POS / Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
                <div id="paymentDistribution" style="display:none">
                    <label class="form-label">Distribution Preview</label>
                    <div id="distributionList"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitBulkPayment">
                    <i class="ri-wallet-line me-1"></i>Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

{{-- INDIVIDUAL PAYMENT MODAL --}}
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-wallet-line me-2"></i>Make Payment</h5>
            </div>
            <form id="paymentForm">
                @csrf
                <input type="hidden" id="student_id"            name="student_id">
                <input type="hidden" id="class_id"              name="class_id">
                <input type="hidden" id="term_id"               name="term_id">
                <input type="hidden" id="session_id"            name="session_id">
                <input type="hidden" id="school_bill_id"        name="school_bill_id">
                <input type="hidden" id="actual_amount"         name="actual_amount">
                <input type="hidden" id="adjusted_amount"       name="adjusted_amount">
                <input type="hidden" id="balance2"              name="balance2">
                <input type="hidden" id="last_amount_paid"      name="last_amount_paid">
                <input type="hidden" id="payment_amount2"       name="payment_amount2">
                <input type="hidden" id="scholarship_deduction" name="scholarship_deduction">
                <input type="hidden" id="discount_deduction"    name="discount_deduction">

                <div class="modal-body p-4">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-semibold" id="modal-bill-title"
                             style="color:var(--pay-primary);font-size:15px">—</div>
                    </div>

                    <div class="savings-breakdown d-none" id="savingsBreakdown">
                        <div class="title"><i class="ri-gift-line me-1"></i>Savings Applied</div>
                        <div class="savings-row d-none" id="scholRow">
                            <span id="scholLabel">Scholarship</span>
                            <span class="fw-semibold" style="color:#92400e" id="scholAmt">-₦0</span>
                        </div>
                        <div class="savings-row d-none" id="discRow">
                            <span id="discLabel">Discount</span>
                            <span class="fw-semibold" style="color:#6d28d9" id="discAmt">-₦0</span>
                        </div>
                        <div class="savings-row">
                            <span>Total Savings</span>
                            <span style="color:#7c3aed;font-weight:700" id="totalSavingsAmt">-₦0</span>
                        </div>
                    </div>

                    <div class="bg-light rounded-3 p-3 mb-3">
                        <div class="amount-display-row">
                            <span class="label">Bill Amount</span>
                            <span class="value" id="amount_d">₦0</span>
                        </div>
                        <div class="amount-display-row">
                            <span class="label">Amount Paid</span>
                            <span class="value text-success" id="amount_paid_d">₦0</span>
                        </div>
                        <div class="amount-display-row">
                            <span class="label">Outstanding Balance</span>
                            <span class="value text-danger" id="balance_d">₦0</span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Enter Payment Amount <span class="text-danger">*</span></label>
                        <div class="currency-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="text" id="payment_amount" name="payment_amount"
                                   class="currency-amount" placeholder="0.00"
                                   autocomplete="off" inputmode="decimal">
                        </div>
                        <div class="invalid-feedback d-block" id="amountError"></div>
                    </div>

                    <div class="word-equivalent" id="wordEquivalentContainer">
                        <span class="label">In Words:</span>
                        <span class="value" id="wordEquivalent">—</span>
                    </div>

                    <div class="quick-amount-buttons" id="quickAmountButtons">
                        <button type="button" class="quick-amount-btn" data-percent="25">25%</button>
                        <button type="button" class="quick-amount-btn" data-percent="50">50%</button>
                        <button type="button" class="quick-amount-btn" data-percent="75">75%</button>
                        <button type="button" class="quick-amount-btn full-amount" data-percent="100">100%</button>
                        <button type="button" class="quick-amount-btn" data-custom="clear">Clear</button>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select id="payment_method2" name="payment_method2" class="form-select" required>
                            <option value="">— Select Method —</option>
                            <option value="Bank Deposit">Bank Deposit / Teller</option>
                            <option value="School POS">School POS / Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="paySubmitBtn">
                        <i class="ri-wallet-line me-1"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- DELETE CONFIRM MODAL --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to delete this payment record?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="ri-delete-bin-line me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get('studentId') || '{{ $studentId ?? "" }}';
    const termid    = urlParams.get('termid')    || '{{ $termid ?? "" }}';
    const sessionid = urlParams.get('sessionid') || '{{ $sessionid ?? "" }}';

    let selectedBillsMap = {};
    let billsDataGlobal  = [];
    let currentDeleteUrl = '';
    let deleteInProgress = false;

    // ═══════════════════════════════════════════════════════════════════
    //  CURRENCY INPUT MODULE (OPay-style)
    // ═══════════════════════════════════════════════════════════════════

    const CurrencyInput = (function () {
        const MAX_DECIMALS = 2;
        const MAX_INTEGER_DIGITS = 12;

        function sanitizeRaw(str) {
            let s = String(str || '').replace(/[^\d.]/g, '');
            const firstDot = s.indexOf('.');
            if (firstDot !== -1) {
                s = s.slice(0, firstDot + 1) + s.slice(firstDot + 1).replace(/\./g, '');
            }
            return s;
        }

        function splitParts(raw) {
            const clean = sanitizeRaw(raw);
            const hasDot = clean.includes('.');
            let [intPart, decPart = ''] = clean.split('.');
            intPart = intPart.replace(/^0+(?=\d)/, '') || (hasDot || clean === '0' ? '0' : '');
            if (intPart.length > MAX_INTEGER_DIGITS) {
                intPart = intPart.slice(0, MAX_INTEGER_DIGITS);
            }
            if (decPart.length > MAX_DECIMALS) {
                decPart = decPart.slice(0, MAX_DECIMALS);
            }
            return { intPart, decPart, hasDot };
        }

        function formatDisplay(raw) {
            const { intPart, decPart, hasDot } = splitParts(raw);
            if (intPart === '' && !hasDot) return '';
            const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            if (hasDot) return withCommas + '.' + decPart;
            return withCommas;
        }

        function parse(value) {
            if (!value && value !== 0) return 0;
            const n = parseFloat(String(value).replace(/,/g, '').replace(/[^\d.]/g, ''));
            return isNaN(n) ? 0 : n;
        }

        function toFixed2(n) {
            return (Math.round((n + Number.EPSILON) * 100) / 100).toFixed(2);
        }

        function numberToWords(num) {
            if (num === null || num === undefined || isNaN(num)) return 'Zero';
            num = Math.round(num * 100) / 100;
            if (num === 0) return 'Zero Naira';

            const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                'Seventeen', 'Eighteen', 'Nineteen'];
            const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            const scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

            function convertHundreds(n) {
                let word = '';
                const hundred = Math.floor(n / 100);
                const remainder = n % 100;
                if (hundred > 0) {
                    word += ones[hundred] + ' Hundred';
                    if (remainder > 0) word += ' and ';
                }
                if (remainder > 0) {
                    if (remainder < 20) {
                        word += ones[remainder];
                    } else {
                        word += tens[Math.floor(remainder / 10)];
                        if (remainder % 10) word += ' ' + ones[remainder % 10];
                    }
                }
                return word;
            }

            function convertNumber(n) {
                if (n === 0) return 'Zero';
                let word = '';
                let scaleIndex = 0;
                while (n > 0) {
                    const chunk = n % 1000;
                    if (chunk > 0) {
                        const chunkWords = convertHundreds(chunk);
                        word = chunkWords + (scaleIndex > 0 ? ' ' + scales[scaleIndex] : '') + (word ? ' ' + word : '');
                    }
                    n = Math.floor(n / 1000);
                    scaleIndex++;
                }
                return word.trim();
            }

            const whole = Math.floor(num);
            const kobo = Math.round((num - whole) * 100);
            let words = convertNumber(whole) + ' Naira';
            if (kobo > 0) words += ', ' + convertNumber(kobo) + ' Kobo';
            return words;
        }

        function bind(input, options) {
            options = options || {};
            if (!input || input._currencyBound) return;
            input._currencyBound = true;

            const wordEl = options.wordEl
                ? (typeof options.wordEl === 'string' ? document.getElementById(options.wordEl) : options.wordEl)
                : null;
            const onChange = typeof options.onChange === 'function' ? options.onChange : null;
            const maxAmount = function () {
                return typeof options.maxAmount === 'function'
                    ? options.maxAmount()
                    : (options.maxAmount || Infinity);
            };

            function updateWords() {
                if (!wordEl) return;
                const v = parse(input.value);
                wordEl.textContent = v > 0 ? numberToWords(v) : '—';
            }

            function applyFormat(preserveCursor) {
                const oldVal = input.value;
                const oldPos = input.selectionStart;
                const formatted = formatDisplay(oldVal);
                if (formatted === oldVal) {
                    updateWords();
                    return;
                }

                let digitsBefore = 0;
                for (let i = 0; i < oldPos && i < oldVal.length; i++) {
                    if (/\d/.test(oldVal[i])) digitsBefore++;
                }
                input.value = formatted;
                if (preserveCursor && document.activeElement === input) {
                    let newPos = 0;
                    let seen = 0;
                    for (let i = 0; i < formatted.length; i++) {
                        if (/\d/.test(formatted[i])) {
                            seen++;
                            if (seen >= digitsBefore) {
                                newPos = i + 1;
                                break;
                            }
                        }
                        newPos = i + 1;
                    }
                    if (formatted.endsWith('.') && oldVal.includes('.')) {
                        newPos = formatted.length;
                    }
                    input.setSelectionRange(newPos, newPos);
                }
                updateWords();
                if (onChange) onChange(parse(input.value));
            }

            input.addEventListener('keydown', function (e) {
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                const nav = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
                if (nav.indexOf(e.key) !== -1) return;
                if (e.key === '.') {
                    if (input.value.includes('.')) e.preventDefault();
                    return;
                }
                if (!/^\d$/.test(e.key)) {
                    e.preventDefault();
                }
            });

            input.addEventListener('input', function () {
                applyFormat(true);
            });

            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const start = input.selectionStart;
                const end = input.selectionEnd;
                const next = input.value.slice(0, start) + text + input.value.slice(end);
                input.value = formatDisplay(next);
                applyFormat(false);
                const len = formatDisplay(text).length;
                input.setSelectionRange(start + len, start + len);
            });

            input.addEventListener('focus', function () {
                this.select();
            });

            input.addEventListener('blur', function () {
                const v = parse(this.value);
                if (v > 0) {
                    this.value = formatDisplay(toFixed2(v));
                } else if (this.value === '' || this.value === '.') {
                    this.value = '';
                }
                updateWords();
                const max = maxAmount();
                if (v > max + 0.001 && max < Infinity) {
                    input.closest('.currency-input-wrapper')?.classList.add('has-error');
                } else {
                    input.closest('.currency-input-wrapper')?.classList.remove('has-error');
                }
                if (onChange) onChange(v);
            });

            input._currency = {
                getValue: function () { return parse(input.value); },
                setValue: function (n) {
                    if (n === '' || n === null || n === undefined) {
                        input.value = '';
                    } else {
                        input.value = formatDisplay(toFixed2(Number(n)));
                    }
                    updateWords();
                    if (onChange) onChange(parse(input.value));
                },
                clear: function () {
                    input.value = '';
                    updateWords();
                    input.closest('.currency-input-wrapper')?.classList.remove('has-error');
                    if (onChange) onChange(0);
                },
                refreshWords: updateWords,
            };

            updateWords();
        }

        return {
            bind: bind,
            parse: parse,
            formatDisplay: formatDisplay,
            numberToWords: numberToWords,
            toFixed2: toFixed2,
        };
    })();

    function getCurrencyValue(input) {
        return input && input._currency ? input._currency.getValue() : CurrencyInput.parse(input ? input.value : '');
    }

    function setCurrencyValue(input, value) {
        if (input && input._currency) {
            input._currency.setValue(value);
        } else if (input) {
            input.value = value === '' || value === null || value === undefined
                ? ''
                : CurrencyInput.formatDisplay(CurrencyInput.toFixed2(value || 0));
        }
    }

    function fmt(n) {
        return Number(n || 0).toLocaleString('en-NG', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    function showLoading(show) {
        document.getElementById('loadingOverlay').classList.toggle('active', !!show);
    }

    function getAvatarUrl(picture) {
        if (!picture || picture === 'unnamed.jpg' || picture === '') return null;
        return '/storage/images/student_avatars/' + picture.replace(/^\/+/, '');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  QUICK AMOUNT BUTTONS
    // ═══════════════════════════════════════════════════════════════════

    function setupQuickAmountButtons() {
        const input = document.getElementById('payment_amount');
        const balanceEl = document.getElementById('balance_d');
        if (!input) return;

        document.querySelectorAll('.quick-amount-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.quick-amount-btn').forEach(function (b) {
                    b.classList.remove('is-active');
                });
                this.classList.add('is-active');

                if (this.dataset.custom === 'clear') {
                    setCurrencyValue(input, '');
                    return;
                }

                const percent = parseInt(this.dataset.percent, 10);
                const balanceText = (balanceEl ? balanceEl.textContent : '').replace(/[^\d.]/g, '');
                const balance = parseFloat(balanceText) || 0;

                if (balance <= 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'No Balance',
                        text: 'This bill has no outstanding balance.',
                        confirmButtonColor: '#2563eb',
                    });
                    return;
                }

                const amount = Math.round((balance * percent / 100) * 100) / 100;
                setCurrencyValue(input, amount);
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    //  BULK DISTRIBUTION PREVIEW
    // ═══════════════════════════════════════════════════════════════════

    function renderBulkDistribution(selectedBills, paymentAmount) {
        const container = document.getElementById('paymentDistribution');
        const list = document.getElementById('distributionList');
        if (!container || !list) return;

        if (!paymentAmount || paymentAmount <= 0 || !selectedBills.length) {
            container.style.display = 'none';
            list.innerHTML = '';
            return;
        }

        let remaining = paymentAmount;
        const rows = [];
        let totalApplied = 0;

        for (let i = 0; i < selectedBills.length; i++) {
            const bill = selectedBills[i];
            if (remaining <= 0) break;
            const bal = parseFloat(bill.balance || 0);
            if (bal <= 0) continue;
            const pay = Math.min(remaining, bal);
            const newBal = Math.round((bal - pay) * 100) / 100;
            rows.push({
                title: bill.title,
                amount: pay,
                newBalance: newBal,
                fullyCovered: newBal <= 0,
            });
            remaining = Math.round((remaining - pay) * 100) / 100;
            totalApplied += pay;
        }

        if (rows.length === 0) {
            container.style.display = 'none';
            return;
        }

        let html = '<div class="distribution-preview">' +
            '<div class="dist-header"><span>Bill</span><span>Applied → New Balance</span></div>';

        rows.forEach(function (r) {
            html += '<div class="dist-row">' +
                '<div class="bill-name" title="' + escapeHtml(r.title) + '">' + escapeHtml(r.title) + '</div>' +
                '<div class="text-end">' +
                '<div class="bill-pay">₦' + fmt(r.amount) + '</div>' +
                '<div class="bill-new-bal">' + (r.fullyCovered ? 'Fully paid' : 'Balance ₦' + fmt(r.newBalance)) + '</div>' +
                '</div></div>';
        });

        html += '<div class="dist-footer"><span>Total applied</span><span>₦' + fmt(totalApplied) + '</span></div>';

        if (remaining > 0.009) {
            html += '<div class="dist-remainder">' +
                '<i class="ri-information-line me-1"></i>' +
                '₦' + fmt(remaining) + ' remaining — exceeds selected balances and will not be applied.' +
                '</div>';
        }

        html += '</div>';
        list.innerHTML = html;
        container.style.display = 'block';
    }

    function setupCurrencyInputHandlers() {
        const paymentInput = document.getElementById('payment_amount');
        if (paymentInput && !paymentInput._currencyBound) {
            CurrencyInput.bind(paymentInput, {
                wordEl: 'wordEquivalent',
                maxAmount: function () {
                    return parseFloat(document.getElementById('balance2')?.value) || Infinity;
                },
            });
        }

        const bulkInput = document.getElementById('bulk_payment_amount');
        if (bulkInput && !bulkInput._currencyBound) {
            CurrencyInput.bind(bulkInput, {
                wordEl: 'bulkWordEquivalent',
                onChange: function (amount) {
                    renderBulkDistribution(Object.values(selectedBillsMap), amount);
                },
                maxAmount: function () {
                    return Object.values(selectedBillsMap)
                        .reduce(function (s, b) { return s + parseFloat(b.balance || 0); }, 0);
                },
            });
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  LOAD / RENDER
    // ═══════════════════════════════════════════════════════════════════

    function loadPaymentData() {
        if (!studentId || !termid || !sessionid) {
            document.getElementById('paymentContent').innerHTML =
                '<div class="alert alert-warning">' +
                '<i class="ri-alert-line me-2"></i>Invalid parameters.' +
                '<a href="{{ route('schoolpayment.index') }}" class="alert-link ms-2">← Back</a>' +
                '</div>';
            return;
        }

        document.getElementById('paymentContent').innerHTML =
            '<div class="text-center py-5">' +
            '<div class="spinner-border text-primary" role="status">' +
            '<span class="visually-hidden">Loading…</span></div>' +
            '<p class="mt-3 text-muted">Loading payment data…</p></div>';

        const url = '{{ route("schoolpayment.getPaymentDetailsAjax") }}' +
            '?studentId=' + studentId +
            '&termid=' + termid +
            '&sessionid=' + sessionid;

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (result) {
            if (result.success) {
                renderPaymentContent(result.data, result.used_fallback === true);
            } else {
                document.getElementById('paymentContent').innerHTML =
                    '<div class="alert alert-danger">' +
                    '<i class="ri-error-warning-line me-2"></i>' +
                    escapeHtml(result.message || 'Failed to load data') +
                    '</div>';
            }
        })
        .catch(function (err) {
            console.error('Load error:', err);
            document.getElementById('paymentContent').innerHTML =
                '<div class="alert alert-danger">' +
                '<i class="ri-error-warning-line me-2"></i>An error occurred. Please refresh and try again.' +
                '</div>';
        });
    }

    function renderPaymentContent(data, usedFallback) {
        const student = data.student;
        const bills = data.bills;
        const paymentRecords = data.payment_records;
        const paymentHistory = data.payment_history;
        const scholarship = data.scholarship;
        const discounts = data.discounts;
        const totals = data.totals;
        const arrears = data.arrears || {};

        billsDataGlobal = bills;

        const newMap = {};
        Object.keys(selectedBillsMap).forEach(function (id) {
            const refreshed = bills.find(function (b) { return String(b.id) === id; });
            if (refreshed && !refreshed.is_paid && !refreshed.has_pending_invoice) {
                newMap[id] = refreshed;
            }
        });
        selectedBillsMap = newMap;

        const initials = (student.name || '??')
            .split(' ').map(function (n) { return n[0]; }).join('').toUpperCase().substring(0, 2);
        let avatarHtml;
        const avatarUrl = getAvatarUrl(student.avatar);
        if (avatarUrl) {
            avatarHtml =
                '<img src="' + escapeHtml(avatarUrl) + '" alt="' + escapeHtml(student.name) + '" ' +
                'class="student-avatar-lg" ' +
                'onerror="this.onerror=null;this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\';">' +
                '<div class="avatar-placeholder-lg" style="display:none">' + escapeHtml(initials) + '</div>';
        } else {
            avatarHtml = '<div class="avatar-placeholder-lg">' + escapeHtml(initials) + '</div>';
        }

        const hasArrears = arrears.has_arrears && arrears.total_arrears > 0;
        const arrearsBanner = hasArrears
            ? '<a href="{{ url('schoolpayment/arrears') }}/' + student.id +
              '?exclude_term=' + termid + '&exclude_session=' + sessionid +
              '" class="arrears-banner">' +
              '<div class="d-flex align-items-center gap-3 flex-grow-1">' +
              '<div class="arrears-icon"><i class="ri-alarm-warning-line"></i></div>' +
              '<div class="arrears-body">' +
              '<div class="arrears-title">Past Arrears / Outstanding Balance</div>' +
              '<div class="arrears-meta">' +
              (arrears.groups ? arrears.groups.length : 0) + ' previous term/session group(s)' +
              ' · Click to view full breakdown</div></div></div>' +
              '<div class="text-end">' +
              '<div class="arrears-amount">₦' + fmt(arrears.total_arrears) + '</div>' +
              '<div class="arrears-cta">View details <i class="ri-arrow-right-s-line"></i></div>' +
              '</div></a>'
            : '';

        const billsHtml = bills.map(function (bill) {
            const billKey = String(bill.id);
            const isSelected = !!selectedBillsMap[billKey];
            const cardClass = bill.is_paid ? 'paid'
                : bill.is_partial ? 'partial'
                : bill.total_savings > 0 ? 'savings'
                : 'unpaid';

            return '<div class="col-xl-4 col-lg-6">' +
                '<div class="bill-card ' + cardClass + (isSelected ? ' selected' : '') + '" data-bill-id="' + bill.id + '">' +
                '<div class="stripe"></div>' +
                '<div class="d-flex align-items-start justify-content-between mb-2 mt-1">' +
                '<div class="flex-grow-1">' +
                '<div class="fw-semibold mb-1" style="font-size:14px;color:var(--pay-primary)">' + escapeHtml(bill.title) + '</div>' +
                (bill.description ? '<div class="text-muted" style="font-size:11px">' + escapeHtml(bill.description) + '</div>' : '') +
                '</div>' +
                '<div class="ms-2 flex-shrink-0">' +
                '<input type="checkbox" class="bill-select-checkbox" data-bill-id="' + bill.id + '" ' +
                (bill.is_paid || bill.has_pending_invoice ? 'disabled' : '') + ' ' +
                (isSelected ? 'checked' : '') +
                ' style="transform:scale(1.3);cursor:pointer;">' +
                '</div></div>' +
                (bill.total_savings > 0
                    ? '<div class="d-flex flex-wrap gap-1 mb-2">' +
                      (bill.scholarship_deduction > 0
                          ? '<span class="schol-pill"><i class="ri-award-line"></i> -₦' + fmt(bill.scholarship_deduction) + ' Scholarship</span>'
                          : '') +
                      (bill.discount_deduction > 0
                          ? '<span class="disc-pill"><i class="ri-price-tag-3-line"></i> -₦' + fmt(bill.discount_deduction) + ' Discount</span>'
                          : '') +
                      '</div>'
                    : '') +
                '<div class="text-center mb-2">' +
                (bill.total_savings > 0
                    ? '<div class="text-muted text-decoration-line-through" style="font-size:12px">₦' + fmt(bill.original_amount) + '</div>'
                    : '') +
                '<div class="bill-amount-main">₦' + fmt(bill.adjusted_amount) + '</div>' +
                '<div style="font-size:11px;color:var(--pay-muted)">' +
                (bill.total_savings > 0 ? 'After savings' : 'Payable amount') +
                '</div></div>' +
                '<div class="row g-2 mb-2">' +
                '<div class="col-6 text-center"><div class="bill-mini-label">Paid</div>' +
                '<div class="bill-mini-value text-success">₦' + fmt(bill.amount_paid) + '</div></div>' +
                '<div class="col-6 text-center"><div class="bill-mini-label">Balance</div>' +
                '<div class="bill-mini-value ' + (bill.balance > 0 ? 'text-danger' : 'text-success') + '">₦' + fmt(bill.balance) + '</div></div>' +
                '</div>' +
                '<div class="mb-3">' +
                '<div class="d-flex justify-content-between mb-1" style="font-size:10px;color:var(--pay-muted)">' +
                '<span>Progress</span>' +
                '<span class="fw-semibold ' + (bill.is_paid ? 'text-success' : 'text-primary') + '">' + Math.round(bill.progress) + '%</span>' +
                '</div>' +
                '<div class="progress">' +
                '<div class="' + (bill.is_paid ? 'progress-bar-paid' : 'progress-bar-partial') + '" style="width:' + bill.progress + '%"></div>' +
                '</div></div>' +
                (!bill.is_paid
                    ? (bill.has_pending_invoice
                        ? '<button class="btn btn-secondary btn-sm w-100" disabled>' +
                          '<i class="ri-lock-line me-1"></i>Invoice Pending</button>'
                        : '<button class="btn btn-primary btn-sm w-100 make-payment-btn" data-bill-id="' + bill.id + '">' +
                          '<i class="ri-wallet-line me-1"></i>Make Payment</button>' +
                          '<div class="select-hint mt-2"><i class="ri-checkbox-line"></i>Tick to include in bulk payment</div>')
                    : '<button class="btn btn-success btn-sm w-100" disabled>' +
                      '<i class="ri-checkbox-circle-line me-1"></i>Fully Paid</button>') +
                '</div></div>';
        }).join('');

        const paymentRecordsHtml = paymentRecords.length > 0
            ? '<div class="table-responsive"><table class="table rec-table w-100 mb-0"><thead><tr>' +
              '<th>#</th><th>Bill</th><th>Bill Amt</th><th>Paid</th><th>Balance</th>' +
              '<th>Method</th><th>Received By</th><th>Date</th><th>Status</th><th>Action</th>' +
              '</tr></thead><tbody>' +
              paymentRecords.map(function (sp, i) {
                  return '<tr>' +
                      '<td>' + (i + 1) + '</td>' +
                      '<td><div class="fw-semibold">' + escapeHtml(sp.title) + '</div>' +
                      (sp.description ? '<div class="text-muted small">' + escapeHtml(sp.description) + '</div>' : '') + '</td>' +
                      '<td>₦' + fmt(sp.billAmount) + '</td>' +
                      '<td class="text-success fw-semibold">₦' + fmt(sp.totalAmountPaid) + '</td>' +
                      '<td class="' + (sp.balance > 0 ? 'text-danger' : 'text-success') + ' fw-semibold">₦' + fmt(sp.balance) + '</td>' +
                      '<td><span class="badge bg-secondary-subtle text-secondary">' + escapeHtml(sp.paymentMethod || '—') + '</span></td>' +
                      '<td class="text-muted small">' + escapeHtml(sp.receivedBy || '—') + '</td>' +
                      '<td class="text-muted small">' + (sp.receivedDate ? new Date(sp.receivedDate).toLocaleDateString('en-GB') : 'N/A') + '</td>' +
                      '<td><span class="badge ' + (sp.paymentStatus === 'Completed' ? 'bg-success' : 'bg-warning text-dark') + '">' +
                      escapeHtml(sp.paymentStatus || 'Pending') + '</span></td>' +
                      '<td>' + (sp.recordId
                          ? '<button class="btn btn-sm btn-danger delete-payment" data-record-id="' + sp.recordId +
                            '" data-payment-id="' + sp.paymentId + '"><i class="ri-delete-bin-line"></i></button>'
                          : '<span class="text-muted small">—</span>') +
                      '</td></tr>';
              }).join('') +
              '</tbody></table></div>'
            : '<div class="empty-state"><i class="ri-receipt-line"></i><p>No pending payment records.</p></div>';

        const historyHtml = paymentHistory.length > 0
            ? '<div class="table-responsive"><table class="table rec-table w-100 mb-0"><thead><tr>' +
              '<th>#</th><th>Bill</th><th>Bill Amt</th><th>Paid</th><th>Balance</th>' +
              '<th>Method</th><th>Received By</th><th>Date</th><th>Status</th><th>Invoice</th>' +
              '</tr></thead><tbody>' +
              paymentHistory.map(function (ph, i) {
                  return '<tr>' +
                      '<td>' + (i + 1) + '</td>' +
                      '<td><div class="fw-semibold">' + escapeHtml(ph.title) + '</div>' +
                      (ph.description ? '<div class="text-muted small">' + escapeHtml(ph.description) + '</div>' : '') + '</td>' +
                      '<td>₦' + fmt(ph.billAmount) + '</td>' +
                      '<td class="text-success fw-semibold">₦' + fmt(ph.totalAmountPaid) + '</td>' +
                      '<td class="' + (ph.balance > 0 ? 'text-danger' : 'text-success') + ' fw-semibold">₦' + fmt(ph.balance) + '</td>' +
                      '<td><span class="badge bg-secondary-subtle text-secondary">' + escapeHtml(ph.paymentMethod || '—') + '</span></td>' +
                      '<td class="text-muted small">' + escapeHtml(ph.receivedBy || '—') + '</td>' +
                      '<td class="text-muted small">' + (ph.receivedDate ? new Date(ph.receivedDate).toLocaleDateString('en-GB') : 'N/A') + '</td>' +
                      '<td><span class="badge ' + ((ph.paymentStatus === 'Completed' || ph.completePayment) ? 'bg-success' : 'bg-warning text-dark') + '">' +
                      ((ph.paymentStatus === 'Completed' || ph.completePayment) ? 'Completed' : 'Partial') + '</span></td>' +
                      '<td><a href="{{ url('schoolpayment/invoice') }}/' + studentId + '/' +
                      (ph.classId || student.schoolclassId || '') + '/' + (ph.termId || termid) + '/' + (ph.sessionId || sessionid) +
                      '" class="btn btn-sm btn-outline-primary" title="View Invoice">' +
                      '<i class="ri-file-download-line"></i></a></td></tr>';
              }).join('') +
              '</tbody></table></div>'
            : '<div class="empty-state"><i class="ri-history-line"></i><p>No payment history found.</p></div>';

        const totalOutstanding = totals.outstanding;
        const totalPaid = totals.paid;
        const selectedCount = Object.keys(selectedBillsMap).length;
        const hasBulkableBills = bills.some(function (b) {
            return !b.is_paid && !b.has_pending_invoice;
        });

        const fallbackBanner = usedFallback
            ? '<div class="fallback-banner"><i class="ri-information-line"></i><div>' +
              '<strong>Note:</strong> This student was not enrolled in the selected term/session. ' +
              'Data shown is from their current active enrollment.</div></div>'
            : '';

        const contentHtml =
            fallbackBanner +
            arrearsBanner +
            '<div class="student-card">' +
            '<div class="d-flex align-items-start gap-4 flex-wrap">' +
            '<div class="flex-shrink-0">' + avatarHtml + '</div>' +
            '<div class="flex-grow-1">' +
            '<div class="d-flex align-items-center gap-2 flex-wrap mb-1">' +
            '<h5 class="mb-0 fw-bold" style="color:var(--pay-primary)">' + escapeHtml(student.name) + '</h5>' +
            '<span class="badge ' + (student.student_status === 'Active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger') +
            ' px-2 py-1" style="font-size:11px">' + escapeHtml(student.student_status || 'Unknown') + '</span>' +
            '<span class="badge ' + (student.statusId == 1 ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning') +
            ' px-2 py-1" style="font-size:11px">' + (student.statusId == 1 ? 'Returning Student' : 'New Student') + '</span>' +
            '</div>' +
            '<div class="text-muted small font-monospace mb-3">' + escapeHtml(student.admissionNo) + '</div>' +
            '<div class="d-flex flex-wrap gap-2">' +
            '<div class="info-chip"><i class="ri-building-line text-success"></i>' +
            escapeHtml(student.schoolclass) + ' ' + escapeHtml(student.arm) + '</div>' +
            '<div class="info-chip"><i class="ri-calendar-line text-primary"></i>' + escapeHtml(data.term || '') + '</div>' +
            '<div class="info-chip"><i class="ri-time-line text-warning"></i>' + escapeHtml(data.session || '') + '</div>' +
            '<div class="info-chip"><i class="ri-money-dollar-circle-line text-danger"></i>Total: ₦' + fmt(totals.adjusted) + '</div>' +
            '<div class="info-chip"><i class="ri-check-line text-success"></i>Paid: ₦' + fmt(totals.paid) + '</div>' +
            (bills.length === 0
                ? '<div class="info-chip" style="background:#fef2f2;border-color:#fecaca;color:#dc2626">' +
                  '<i class="ri-alert-line"></i>No Bills Assigned</div>'
                : totalOutstanding > 0
                    ? '<div class="info-chip" style="background:#fef2f2;border-color:#fecaca;color:#dc2626">' +
                      '<i class="ri-alert-line"></i>Outstanding: ₦' + fmt(totalOutstanding) + '</div>'
                    : totalPaid > 0
                        ? '<div class="info-chip" style="background:#f0fdf4;border-color:#bbf7d0;color:#16a34a">' +
                          '<i class="ri-checkbox-circle-line"></i>Fully Paid</div>'
                        : '<div class="info-chip" style="background:#fef2f2;border-color:#fecaca;color:#dc2626">' +
                          '<i class="ri-alert-line"></i>No Payments Made</div>') +
            '</div></div>' +
            '<div class="d-flex gap-2 flex-wrap align-items-start">' +
            '<a href="{{ route('schoolpayment.index') }}" class="btn btn-outline-secondary btn-sm">' +
            '<i class="ri-arrow-left-line me-1"></i>Back</a>' +
            (paymentRecords.length > 0
                ? '<a href="{{ url('schoolpayment/invoice') }}/' + studentId + '/' +
                  (student.schoolclassId || '') + '/' + termid + '/' + sessionid +
                  '" class="btn btn-primary btn-sm">' +
                  '<i class="ri-file-download-line me-1"></i>Generate Invoice</a>'
                : '<button class="btn btn-primary btn-sm" disabled title="Make a payment first">' +
                  '<i class="ri-file-download-line me-1"></i>Generate Invoice</button>') +
            '<a href="{{ url('schoolpayment/statement') }}/' + studentId + '/' +
            (student.schoolclassId || '') + '/' + termid + '/' + sessionid +
            '" class="btn btn-outline-primary btn-sm">' +
            '<i class="ri-file-list-line me-1"></i>Statement</a>' +
            '<button class="btn btn-success btn-sm" id="bulkPaymentBtn" ' +
            (!hasBulkableBills ? 'disabled' : '') + '>' +
            '<i class="ri-wallet-3-line me-1"></i>Bulk Payment' +
            '<span class="badge bg-white text-success ms-1" id="selectedCount">' + selectedCount + '</span>' +
            '</button></div></div></div>' +
            (scholarship
                ? '<div class="benefit-banner schol"><i class="ri-award-line icon"></i><div>' +
                  '<div class="fw-semibold mb-1">Scholarship Active: ' + escapeHtml(scholarship.title) + '</div>' +
                  '<div class="small">' +
                  (scholarship.value_type === 'percentage'
                      ? scholarship.value + '% deduction.'
                      : '₦' + fmt(scholarship.value) + ' fixed deduction per bill.') +
                  (scholarship.effective_to
                      ? ' Valid until ' + new Date(scholarship.effective_to).toLocaleDateString('en-GB') + '.'
                      : '') +
                  '<strong class="ms-2">Total Savings: ₦' + fmt(totals.savings) + '</strong></div></div></div>'
                : '') +
            (discounts.length > 0
                ? '<div class="benefit-banner disc"><i class="ri-price-tag-3-line icon"></i><div>' +
                  '<div class="fw-semibold mb-1">Discount(s) Active</div><div class="small">' +
                  discounts.map(function (d) {
                      return '<span class="me-3"><strong>' + escapeHtml(d.title) + ':</strong> ' +
                          (d.value_type === 'percentage' ? d.value + '% off' : '₦' + fmt(d.value) + ' off') +
                          '</span>';
                  }).join('') +
                  '</div></div></div>'
                : '') +
            '<div class="card border-0 shadow-sm">' +
            '<div class="card-header bg-white border-bottom pt-3 pb-0">' +
            '<ul class="nav nav-tabs border-0" id="payTabs">' +
            '<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-bills">' +
            '<i class="ri-bill-line me-1"></i>School Bills' +
            '<span class="badge ' + (bills.length === 0 ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary') +
            ' ms-1">' + bills.length + '</span></a></li>' +
            '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-records">' +
            '<i class="ri-receipt-line me-1"></i>Payment Records' +
            (paymentRecords.length
                ? '<span class="badge bg-success-subtle text-success ms-1">' + paymentRecords.length + '</span>'
                : '') +
            '</a></li>' +
            '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-history">' +
            '<i class="ri-history-line me-1"></i>History' +
            (paymentHistory.length
                ? '<span class="badge bg-info-subtle text-info ms-1">' + paymentHistory.length + '</span>'
                : '') +
            '</a></li></ul></div>' +
            '<div class="card-body p-3"><div class="tab-content">' +
            '<div class="tab-pane fade show active" id="tab-bills">' +
            (bills.length > 0
                ? '<div class="row g-3 mt-1">' + billsHtml + '</div>' +
                  (totals.savings > 0
                      ? '<div class="mt-4 p-3 rounded-3" style="background:linear-gradient(135deg,#f3e8ff,#ede9fe);border:1px solid #ddd6fe">' +
                        '<div class="d-flex align-items-center gap-2">' +
                        '<i class="ri-gift-line" style="font-size:18px;color:#7c3aed"></i><div>' +
                        '<span class="fw-semibold" style="color:#7c3aed">Total Savings Applied: </span>' +
                        '<span class="fw-bold" style="color:#7c3aed">₦' + fmt(totals.savings) + '</span>' +
                        '<span class="text-muted small ms-2">(Original: ₦' + fmt(totals.original) +
                        ' → Payable: ₦' + fmt(totals.adjusted) + ')</span></div></div></div>'
                      : '')
                : '<div class="empty-state"><i class="ri-inbox-line"></i>' +
                  '<p>No bills assigned to this student for the selected term/session.</p>' +
                  '<div class="alert alert-info mt-3 text-start" style="font-size:12px">' +
                  '<i class="ri-information-line me-2"></i><strong>Possible reasons:</strong>' +
                  '<ul class="mb-0 mt-2">' +
                  '<li>No fee structure has been configured for this class (' +
                  escapeHtml(student.schoolclass) + ' ' + escapeHtml(student.arm) + ')</li>' +
                  '<li>The selected term (' + escapeHtml(data.term || '') +
                  ') or session (' + escapeHtml(data.session || '') + ') has no associated bills</li>' +
                  '<li>The student\'s enrollment in this class may not have bills assigned</li></ul>' +
                  '<hr class="my-2"><p class="mb-0 small">Please contact the school administrator to configure fee structures for this class, term, and session.</p>' +
                  '</div></div>') +
            '</div>' +
            '<div class="tab-pane fade" id="tab-records">' + paymentRecordsHtml + '</div>' +
            '<div class="tab-pane fade" id="tab-history">' + historyHtml + '</div>' +
            '</div></div></div>';

        document.getElementById('paymentContent').innerHTML = contentHtml;

        attachBillSelectionEvents(bills);
        attachPaymentButtons(bills);
        attachDeleteHandlers();
        setupCurrencyInputHandlers();
        setupQuickAmountButtons();

        const bulkBtn = document.getElementById('bulkPaymentBtn');
        if (bulkBtn) {
            bulkBtn.addEventListener('click', function () {
                openBulkPaymentModal();
            });
        }
    }

    function attachBillSelectionEvents(bills) {
        document.querySelectorAll('.bill-select-checkbox').forEach(function (cb) {
            cb.addEventListener('change', function () {
                const billKey = String(this.dataset.billId);
                const bill = bills.find(function (b) { return String(b.id) === billKey; });

                if (this.checked && bill) {
                    selectedBillsMap[billKey] = bill;
                } else {
                    delete selectedBillsMap[billKey];
                }

                const card = document.querySelector('.bill-card[data-bill-id="' + billKey + '"]');
                if (card) card.classList.toggle('selected', !!selectedBillsMap[billKey]);

                const badge = document.getElementById('selectedCount');
                if (badge) badge.textContent = Object.keys(selectedBillsMap).length;
            });
        });
    }

    function attachPaymentButtons(bills) {
        document.querySelectorAll('.make-payment-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const billKey = String(this.dataset.billId);
                const bill = bills.find(function (b) { return String(b.id) === billKey; });
                if (bill) {
                    openPaymentModal(bill);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Could not find bill data. Please refresh.' });
                }
            });
        });
    }

    function openPaymentModal(bill) {
        document.getElementById('modal-bill-title').textContent = bill.title;
        document.getElementById('student_id').value = studentId;
        document.getElementById('class_id').value = bill.class_id || '';
        document.getElementById('term_id').value = termid;
        document.getElementById('session_id').value = sessionid;
        document.getElementById('school_bill_id').value = bill.id;
        document.getElementById('actual_amount').value = bill.original_amount;
        document.getElementById('adjusted_amount').value = bill.adjusted_amount;
        document.getElementById('balance2').value = bill.balance;
        document.getElementById('last_amount_paid').value = bill.amount_paid;
        document.getElementById('scholarship_deduction').value = bill.scholarship_deduction || 0;
        document.getElementById('discount_deduction').value = bill.discount_deduction || 0;

        document.getElementById('amount_d').textContent = '₦' + fmt(bill.adjusted_amount);
        document.getElementById('amount_paid_d').textContent = '₦' + fmt(bill.amount_paid);
        document.getElementById('balance_d').textContent = '₦' + fmt(bill.balance);

        const paymentInput = document.getElementById('payment_amount');
        setupCurrencyInputHandlers();
        setCurrencyValue(paymentInput, '');
        document.getElementById('payment_method2').value = '';
        document.getElementById('amountError').textContent = '';
        document.querySelectorAll('.quick-amount-btn').forEach(function (b) {
            b.classList.remove('is-active');
        });

        const savBox = document.getElementById('savingsBreakdown');
        if (bill.total_savings > 0) {
            savBox.classList.remove('d-none');
            const scholRow = document.getElementById('scholRow');
            const discRow = document.getElementById('discRow');
            if (bill.scholarship_deduction > 0) {
                scholRow.classList.remove('d-none');
                document.getElementById('scholLabel').textContent = bill.scholarship_label || 'Scholarship';
                document.getElementById('scholAmt').textContent = '-₦' + fmt(bill.scholarship_deduction);
            } else {
                scholRow.classList.add('d-none');
            }
            if (bill.discount_deduction > 0) {
                discRow.classList.remove('d-none');
                document.getElementById('discLabel').textContent = bill.discount_labels || 'Discount';
                document.getElementById('discAmt').textContent = '-₦' + fmt(bill.discount_deduction);
            } else {
                discRow.classList.add('d-none');
            }
            document.getElementById('totalSavingsAmt').textContent = '-₦' + fmt(bill.total_savings);
        } else {
            savBox.classList.add('d-none');
        }

        new bootstrap.Modal(document.getElementById('paymentModal')).show();
        setTimeout(function () { paymentInput.focus(); }, 300);
    }

    function openBulkPaymentModal() {
        const selectedBills = Object.values(selectedBillsMap);

        if (selectedBills.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Bills Selected',
                html: '<p>Please tick the checkbox on one or more bills before clicking <strong>Bulk Payment</strong>.</p>',
                confirmButtonColor: '#2563eb',
            });
            return;
        }

        const totalPayable = selectedBills.reduce(function (s, b) {
            return s + parseFloat(b.balance || 0);
        }, 0);
        const totalSavings = selectedBills.reduce(function (s, b) {
            return s + parseFloat(b.total_savings || 0);
        }, 0);

        document.getElementById('selectedBillsSummary').innerHTML =
            '<div class="mb-3"><label class="form-label">Selected Bills (' + selectedBills.length + ')</label>' +
            '<div class="list-group">' +
            selectedBills.map(function (b) {
                return '<div class="list-group-item d-flex justify-content-between align-items-center py-2">' +
                    '<div class="fw-semibold">' + escapeHtml(b.title) + '</div>' +
                    '<div class="text-end">' +
                    '<small class="text-muted d-block">Balance: ₦' + fmt(b.balance) + '</small>' +
                    (b.total_savings > 0
                        ? '<small class="text-success">Saved: ₦' + fmt(b.total_savings) + '</small>'
                        : '') +
                    '</div></div>';
            }).join('') +
            '</div></div>';

        document.getElementById('bulkTotalPayable').textContent = '₦' + fmt(totalPayable);
        document.getElementById('bulkTotalSavings').textContent = '₦' + fmt(totalSavings);

        const bulkInput = document.getElementById('bulk_payment_amount');
        setupCurrencyInputHandlers();
        setCurrencyValue(bulkInput, '');
        document.getElementById('bulk_payment_method').value = '';
        document.getElementById('paymentDistribution').style.display = 'none';
        document.getElementById('distributionList').innerHTML = '';

        new bootstrap.Modal(document.getElementById('bulkPaymentModal')).show();
        setTimeout(function () { bulkInput.focus(); }, 300);

        const submitBtn = document.getElementById('submitBulkPayment');
        submitBtn.onclick = null;
        submitBtn.onclick = function () {
            submitBulkPayment(selectedBills);
        };
    }

    function submitBulkPayment(selectedBills) {
        const bulkInput = document.getElementById('bulk_payment_amount');
        const paymentAmount = getCurrencyValue(bulkInput);
        const paymentMethod = document.getElementById('bulk_payment_method').value;
        const totalPayable = selectedBills.reduce(function (s, b) {
            return s + parseFloat(b.balance || 0);
        }, 0);

        if (paymentAmount <= 0) {
            return Swal.fire({
                icon: 'warning',
                title: 'Invalid Amount',
                text: 'Please enter a valid payment amount.',
                confirmButtonColor: '#2563eb',
            });
        }
        if (paymentAmount > totalPayable + 0.01) {
            return Swal.fire({
                icon: 'warning',
                title: 'Exceeds Balance',
                text: 'Total outstanding is ₦' + fmt(totalPayable) + '.',
                confirmButtonColor: '#2563eb',
            });
        }
        if (!paymentMethod) {
            return Swal.fire({
                icon: 'warning',
                title: 'No Method',
                text: 'Please select a payment method.',
                confirmButtonColor: '#2563eb',
            });
        }

        showLoading(true);

        const classId = selectedBills[0] ? selectedBills[0].class_id : 0;

        const payload = {
            student_id: studentId,
            class_id: classId,
            term_id: termid,
            session_id: sessionid,
            payment_amount: paymentAmount,
            payment_method: paymentMethod,
            bill_payments: selectedBills.map(function (b) {
                return {
                    school_bill_id: b.id,
                    title: b.title,
                    adjusted_amount: b.adjusted_amount,
                    balance: b.balance,
                    scholarship_deduction: b.scholarship_deduction || 0,
                    discount_deduction: b.discount_deduction || 0,
                };
            }),
        };

        fetch('{{ route("schoolpayment.bulk-store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(function (response) {
            if (!response.ok) {
                return response.json().then(function (err) { throw err; });
            }
            return response.json();
        })
        .then(function (result) {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message,
                    timer: 2000,
                    showConfirmButton: false,
                }).then(function () {
                    const inst = bootstrap.Modal.getInstance(document.getElementById('bulkPaymentModal'));
                    if (inst) inst.hide();
                    selectedBillsMap = {};
                    loadPaymentData();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Payment failed.' });
            }
        })
        .catch(function (err) {
            console.error('Bulk payment error:', err);
            let errorMsg = 'An error occurred.';
            if (err.errors) {
                errorMsg = Object.values(err.errors).flat().join('. ');
            } else if (err.message) {
                errorMsg = err.message;
            }
            Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
        })
        .finally(function () {
            showLoading(false);
        });
    }

    function attachDeleteHandlers() {
        document.querySelectorAll('.delete-payment').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const recordId = this.dataset.recordId;
                const paymentId = this.dataset.paymentId;
                if (recordId) {
                    currentDeleteUrl = '/schoolpayment/delete/' + recordId;
                    document.getElementById('confirmDeleteBtn').dataset.paymentId = paymentId;
                    new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
                }
            });
        });
    }

    document.getElementById('paymentForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const amountInput = document.getElementById('payment_amount');
        const amount = getCurrencyValue(amountInput);
        const balance = parseFloat(document.getElementById('balance2').value) || 0;
        const method = document.getElementById('payment_method2').value;

        if (amount <= 0) {
            return Swal.fire({
                icon: 'warning',
                title: 'Invalid Amount',
                text: 'Enter a valid amount.',
                confirmButtonColor: '#2563eb',
            });
        }
        if (amount > balance + 0.01) {
            return Swal.fire({
                icon: 'warning',
                title: 'Exceeds Balance',
                text: 'Balance is ₦' + fmt(balance),
                confirmButtonColor: '#2563eb',
            });
        }
        if (!method) {
            return Swal.fire({
                icon: 'warning',
                title: 'No Method',
                text: 'Select a payment method.',
                confirmButtonColor: '#2563eb',
            });
        }

        document.getElementById('payment_amount2').value = amount.toFixed(2);

        const btn = document.getElementById('paySubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing…';

        const formData = new FormData(this);
        formData.set('payment_amount', amount.toFixed(2));

        fetch('{{ route("schoolpayment.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(function (response) {
            if (!response.ok) {
                return response.json().then(function (err) { throw err; });
            }
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                const inst = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                if (inst) inst.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Recorded!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false,
                }).then(function () {
                    loadPaymentData();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Payment failed.' });
            }
        })
        .catch(function (err) {
            console.error('Payment error:', err);
            let errorMsg = 'An error occurred.';
            if (err.errors) {
                errorMsg = Object.values(err.errors).flat().join('. ');
            } else if (err.message) {
                errorMsg = err.message;
            }
            Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-wallet-line me-1"></i>Record Payment';
        });
    });

    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (deleteInProgress) return;
        deleteInProgress = true;

        const self = this;
        const originalText = self.innerHTML;
        self.disabled = true;
        self.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting…';

        const inst = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
        if (inst) inst.hide();

        fetch(currentDeleteUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        })
        .then(function (response) {
            if (!response.ok) {
                return response.json().then(function (err) { throw err; });
            }
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: data.message || 'Payment record deleted successfully.',
                    timer: 2000,
                    showConfirmButton: false,
                }).then(function () {
                    loadPaymentData();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to delete payment record.',
                });
                loadPaymentData();
            }
        })
        .catch(function (err) {
            console.error('Delete error:', err);
            let errorMsg = 'Failed to delete. Please try again.';
            if (err.message) errorMsg = err.message;
            Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
            loadPaymentData();
        })
        .finally(function () {
            self.disabled = false;
            self.innerHTML = originalText;
            deleteInProgress = false;
            currentDeleteUrl = '';
        });
    });

    // Boot
    setupCurrencyInputHandlers();
    loadPaymentData();
});
</script>
@endsection