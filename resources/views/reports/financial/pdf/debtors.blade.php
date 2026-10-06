{{-- resources/views/reports/financial/debtors-list.blade.php --}}
@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
:root {
    --ss-primary: #1e3a5f;
    --ss-accent: #2563eb;
    --ss-success: #16a34a;
    --ss-warning: #d97706;
    --ss-danger: #dc2626;
    --ss-muted: #6b7280;
    --ss-border: #e2e8f0;
    --ss-bg: #f8fafc;
    --ss-card: #ffffff;
    --ss-radius: 12px;
}

.report-hero {
    background: linear-gradient(135deg, var(--ss-primary) 0%, var(--ss-accent) 60%, #4f46e5 100%);
    border-radius: var(--ss-radius);
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

.filter-bar {
    background: var(--ss-bg);
    padding: 20px;
    border-radius: var(--ss-radius);
    margin-bottom: 24px;
}
.filter-label {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 8px;
    color: var(--ss-primary);
}

.stat-card {
    background: var(--ss-card);
    border: 1px solid var(--ss-border);
    border-radius: var(--ss-radius);
    padding: 18px 20px;
    transition: transform .15s, box-shadow .15s;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,.08);
}
.stat-card .stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--ss-primary);
}
.stat-card .stat-label {
    font-size: 12px;
    color: var(--ss-muted);
    margin-top: 4px;
}

.debtors-table th {
    background: var(--ss-primary);
    color: #fff;
    padding: 12px 16px;
    font-size: 12px;
    white-space: nowrap;
    font-weight: 600;
}
.debtors-table td {
    padding: 10px 16px;
    border-bottom: 1px solid var(--ss-border);
    vertical-align: middle;
    font-size: 13px;
}

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.status-active { background: #dcfce7; color: #16a34a; }
.status-inactive { background: #fee2e2; color: #dc2626; }
.status-suspended { background: #fef3c7; color: #d97706; }

.benefit-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    margin: 1px 2px;
}
.benefit-scholarship { background: #fef3c7; color: #d97706; }
.benefit-discount { background: #ede9fe; color: #6d28d9; }
.benefit-both {
    background: linear-gradient(135deg, #fef3c7 50%, #ede9fe 50%);
    color: #4a3f5f;
}
.benefit-unreachable {
    background: #fee2e2;
    color: #dc2626;
}

.student-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    cursor: pointer;
    transition: transform 0.18s;
}
.student-avatar:hover { transform: scale(1.1); }
.student-avatar-placeholder {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--ss-accent), #4f46e5);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.amount-high { color: #dc2626; font-weight: 700; }
.amount-medium { color: #d97706; font-weight: 600; }
.amount-low { color: #16a34a; font-weight: 500; }

.expandable-content {
    background: #f8fafc;
    padding: 16px 20px;
    border-radius: 8px;
    margin-top: 8px;
}
.expandable-content .bill-item {
    padding: 6px 0;
    border-bottom: 1px solid #e2e8f0;
}
.expandable-content .bill-item:last-child { border-bottom: none; }

#loadingOverlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,.5);
    backdrop-filter: blur(8px);
    z-index: 99999;
    display: none;
    justify-content: center;
    align-items: center;
}
#loadingOverlay.active { display: flex; }
.loading-content {
    background: rgba(255,255,255,.96);
    border-radius: 20px;
    padding: 30px 40px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,.2);
}
.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: var(--ss-accent);
    border-radius: 50%;
    animation: spin .8s linear infinite;
    margin: 0 auto 15px;
}
@keyframes spin { to { transform: rotate(360deg); } }
.loading-text {
    font-size: 14px;
    color: var(--ss-primary);
    font-weight: 500;
}

#studentPopover {
    position: fixed;
    z-index: 999999;
    pointer-events: none;
    opacity: 0;
    transform: scale(.92) translateY(6px);
    transition: opacity .22s cubic-bezier(.4,0,.2,1), transform .22s cubic-bezier(.4,0,.2,1);
    width: 300px;
    top: -9999px;
    left: -9999px;
}
#studentPopover.visible {
    opacity: 1;
    transform: scale(1) translateY(0);
}
.popover-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 0 0 .5px rgba(0,0,0,.1), 0 8px 32px rgba(0,0,0,.2);
    overflow: hidden;
}
.popover-header {
    background: linear-gradient(135deg, var(--ss-primary), var(--ss-accent));
    padding: 14px 16px;
}
.popover-avatar-wrapper { display: flex; align-items: center; gap: 12px; }
.popover-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    object-fit: cover; border: 3px solid rgba(255,255,255,.9);
}
.popover-name { font-size: 14px; font-weight: 700; color: #fff; }
.popover-adm { font-size: 10px; color: rgba(255,255,255,.75); }
.popover-body { padding: 12px 16px; }
.popover-stats-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 6px;
    margin-bottom: 12px;
}
.popover-stat {
    background: var(--ss-bg);
    border-radius: 8px;
    padding: 6px;
    text-align: center;
}
.popover-stat-val {
    font-size: 14px; font-weight: 700; color: var(--ss-primary); display: block;
}
.popover-stat-lbl { font-size: 9px; color: #9ca3af; display: block; }
.popover-bill-list { max-height: 120px; overflow-y: auto; }
.popover-bill-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 6px;
    border-bottom: 1px solid var(--ss-border);
    font-size: 11px;
}
.popover-benefits { display: flex; gap: 4px; margin-top: 8px; flex-wrap: wrap; }

.dataTables_wrapper .dataTables_filter { margin-bottom: 15px; }
.dataTables_wrapper .dataTables_filter input {
    border: 1.5px solid var(--ss-border);
    border-radius: 8px;
    padding: 8px 14px;
    margin-left: 8px;
    font-size: 13px;
    width: 250px;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--ss-accent);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}

.rem-channel-label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    font-size: 14px;
    text-align: left;
}
.rem-channel-label:hover { background: #f8fafc; }
.rem-channel-label input { width: 16px; height: 16px; }

@media (max-width: 768px) {
    .report-hero { padding: 20px; }
    .report-hero h1 { font-size: 18px; }
    .stat-card .stat-value { font-size: 20px; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading debtors data...</div>
        </div>
    </div>

    <div class="report-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1><i class="ri-user-unfollow-line me-2"></i>Student Debtors List</h1>
                <p>View and manage students with outstanding fee balances</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="ri-download-line me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportDebtors('pdf')">
                            <i class="ri-file-pdf-line me-2"></i> PDF
                        </a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportDebtors('excel')">
                            <i class="ri-file-excel-line me-2"></i> Excel
                        </a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportDebtors('csv')">
                            <i class="ri-file-text-line me-2"></i> CSV
                        </a></li>
                    </ul>
                </div>
                <button class="btn btn-light btn-sm" onclick="window.print()">
                    <i class="ri-printer-line"></i> Print
                </button>
                <button class="btn btn-light btn-sm" id="sendRemindersBtn">
                    <i class="ri-mail-send-line"></i> Send Reminders
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value text-danger" id="totalDebtors">0</div>
                <div class="stat-label">Total Debtors</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value text-warning" id="totalOutstanding">₦0</div>
                <div class="stat-label">Total Outstanding</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <div class="stat-value text-info" id="avgDebt">₦0</div>
                <div class="stat-label">Average Debt</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card">
                <div class="stat-value text-success" id="collectionRate">0%</div>
                <div class="stat-label">Collection Rate</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="border-color:#fecaca;">
                <div class="stat-value text-danger" id="unreachableCount">0</div>
                <div class="stat-label">No Contact Info</div>
            </div>
        </div>
    </div>

    <div class="filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="filter-label">Class</label>
                <select class="form-select" id="class_id">
                    <option value="">-- All Classes --</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="filter-label">Term</label>
                <select class="form-select" id="term_id">
                    <option value="">-- All Terms --</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="filter-label">Session</label>
                <select class="form-select" id="session_id">
                    <option value="">-- All Sessions --</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="filter-label">Min Outstanding (₦)</label>
                <input type="number" class="form-control" id="min_outstanding" placeholder="0" min="0">
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <button class="btn btn-primary" id="loadReportBtn">
                    <i class="ri-search-line me-1"></i> Load Report
                </button>
                <button class="btn btn-secondary ms-2" id="resetBtn">
                    <i class="ri-refresh-line me-1"></i> Reset
                </button>
                <span class="ms-3 text-muted small" id="recordCount"></span>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold"><i class="ri-table-line me-2"></i>Debtors List</h5>
            <div>
                <span class="badge bg-primary" id="totalRecords">0</span> records
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table debtors-table w-100" id="debtorsTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll"></th>
                            <th width="50">#</th>
                            <th width="50">Photo</th>
                            <th>Student</th>
                            <th>Admission</th>
                            <th>Class</th>
                            <th>Term/Session</th>
                            <th>Benefits</th>
                            <th class="text-end">Billed (₦)</th>
                            <th class="text-end">Paid (₦)</th>
                            <th class="text-end">Outstanding (₦)</th>
                            <th width="100">Rate</th>
                            <th width="80">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<div id="studentPopover">
    <div class="popover-card">
        <div class="popover-header">
            <div class="popover-avatar-wrapper">
                <img id="popAvatar" src="" alt="" class="popover-avatar">
                <div>
                    <div class="popover-name" id="popName">—</div>
                    <div class="popover-adm" id="popAdm">—</div>
                </div>
            </div>
        </div>
        <div class="popover-body">
            <div class="popover-stats-grid">
                <div class="popover-stat">
                    <span class="popover-stat-val" id="popBilled">—</span>
                    <span class="popover-stat-lbl">Billed</span>
                </div>
                <div class="popover-stat">
                    <span class="popover-stat-val" id="popPaid">—</span>
                    <span class="popover-stat-lbl">Paid</span>
                </div>
                <div class="popover-stat">
                    <span class="popover-stat-val" id="popOwing">—</span>
                    <span class="popover-stat-lbl">Owing</span>
                </div>
            </div>
            <div class="popover-benefits" id="popBenefits"></div>
            <div class="popover-bill-list" id="popBillList"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let debtorsTable;
let currentFilters = {};
let studentData = {};
let allRowData = [];
let popTimer = null;
let hideTimer = null;

const exportUrls = {
    pdf:   @json(route('reports.financial.export', ['report' => 'debtors', 'format' => 'pdf'])),
    excel: @json(route('reports.financial.export', ['report' => 'debtors', 'format' => 'excel'])),
    csv:   @json(route('reports.financial.export', ['report' => 'debtors', 'format' => 'csv'])),
};

const reminderChannelsEnabled = {
    email:    @json((bool) config('reminders.channels.email', true)),
    sms:      @json((bool) config('reminders.channels.sms', false)),
    whatsapp: @json((bool) config('reminders.channels.whatsapp', false)),
};

function fmt(n) {
    return '₦' + parseFloat(n || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 });
}

function initials(name) {
    if (!name) return 'ST';
    return name.split(' ').slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase();
}

function avatarUrl(pic) {
    if (!pic || pic === 'unnamed.jpg' || pic === '') return null;
    return '/storage/images/student_avatars/' + pic;
}

function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, function (m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
}

function showLoading(on) {
    var el = document.getElementById('loadingOverlay');
    if (el) el.classList.toggle('active', !!on);
}

function getOutstandingClass(amount) {
    if (amount >= 100000) return 'amount-high';
    if (amount >= 50000) return 'amount-medium';
    return 'amount-low';
}

function renderAvatar(pic, name) {
    var url = avatarUrl(pic);
    var ini = initials(name);
    var n = escapeHtml(name);
    if (url) {
        return '<img src="' + url + '" class="student-avatar" data-name="' + n + '" data-avatar="' + url + '" alt="' + n + '" onerror="this.style.display=\'none\'">';
    }
    return '<div class="student-avatar-placeholder" data-name="' + n + '">' + ini + '</div>';
}

function renderBenefits(hasScholarship, hasDiscount) {
    if (hasScholarship && hasDiscount) {
        return '<span class="benefit-badge benefit-both"><i class="ri-gift-line me-1"></i>Both</span>';
    }
    if (hasScholarship) {
        return '<span class="benefit-badge benefit-scholarship"><i class="ri-award-line me-1"></i>Scholarship</span>';
    }
    if (hasDiscount) {
        return '<span class="benefit-badge benefit-discount"><i class="ri-price-tag-3-line me-1"></i>Discount</span>';
    }
    return '<span class="text-muted">—</span>';
}

function updateStats() {
    var totalOutstanding = 0, totalBilled = 0, totalPaid = 0, unreachable = 0;
    allRowData.forEach(function (r) {
        totalBilled += parseFloat(r.original_amount) || 0;
        totalPaid += parseFloat(r.amount_paid) || 0;
        totalOutstanding += parseFloat(r.outstanding) || 0;
        if (!r.has_contact) unreachable++;
    });
    var rate = totalBilled > 0 ? ((totalPaid / totalBilled) * 100).toFixed(1) : 0;
    var avg = allRowData.length > 0 ? totalOutstanding / allRowData.length : 0;
    document.getElementById('totalDebtors').textContent = allRowData.length;
    document.getElementById('totalOutstanding').textContent = fmt(totalOutstanding);
    document.getElementById('avgDebt').textContent = fmt(avg);
    document.getElementById('collectionRate').textContent = rate + '%';
    document.getElementById('unreachableCount').textContent = unreachable;
    document.getElementById('totalRecords').textContent = allRowData.length;
}

var popEl = document.getElementById('studentPopover');

function fillPopover(id) {
    var s = studentData[id];
    if (!s) return;
    document.getElementById('popName').textContent = s.student_name;
    document.getElementById('popAdm').textContent = 'Adm: ' + s.admission_no;
    document.getElementById('popBilled').textContent = fmt(s.original_amount);
    document.getElementById('popPaid').textContent = fmt(s.amount_paid);
    document.getElementById('popOwing').textContent = fmt(s.outstanding);
    var avatar = avatarUrl(s.avatar);
    document.getElementById('popAvatar').src = avatar || '';
    document.getElementById('popAvatar').style.display = avatar ? 'block' : 'none';
    document.getElementById('popBenefits').innerHTML = renderBenefits(s.has_scholarship, s.has_discount);
    var list = document.getElementById('popBillList');
    list.innerHTML = '';
    if (s.bills && s.bills.length) {
        s.bills.forEach(function (b) {
            list.innerHTML += '<div class="popover-bill-row"><span>' + escapeHtml(b.title) + '</span><span class="fw-bold">' + fmt(b.outstanding) + '</span></div>';
        });
    } else {
        list.innerHTML = '<div class="popover-bill-row text-muted">No bills</div>';
    }
}

function positionPopover(rect) {
    var pw = 300, ph = 380, vw = window.innerWidth, vh = window.innerHeight;
    var left = rect.left, top = rect.bottom + 8;
    if (left + pw > vw - 8) left = vw - pw - 8;
    if (left < 8) left = 8;
    if (top + ph > vh - 8) top = rect.top - ph - 8;
    if (top < 8) top = 8;
    popEl.style.left = left + 'px';
    popEl.style.top = top + 'px';
}

function showPopover(row) {
    clearTimeout(hideTimer);
    var id = row.getAttribute('data-student-id');
    if (!id || !studentData[id]) return;
    fillPopover(id);
    positionPopover(row.getBoundingClientRect());
    popEl.classList.add('visible');
}

function hidePopover() {
    hideTimer = setTimeout(function () { popEl.classList.remove('visible'); }, 180);
}

function attachRowEvents() {
    $('#debtorsTable tbody tr').off('mouseenter mouseleave');
    $('#debtorsTable tbody tr').on('mouseenter', function () {
        var row = this;
        clearTimeout(popTimer);
        popTimer = setTimeout(function () { showPopover(row); }, 300);
    }).on('mouseleave', function () {
        clearTimeout(popTimer);
        hidePopover();
    });

    $('#debtorsTable tbody').off('click', '.expand-btn').on('click', '.expand-btn', function (e) {
        e.stopPropagation();
        var btn = $(this);
        var row = btn.closest('tr');
        var id = row.data('student-id');
        var detailRow = row.next('.detail-row');

        if (detailRow.length) {
            detailRow.remove();
            btn.html('<i class="ri-arrow-down-s-line"></i>');
            return;
        }

        var data = studentData[id];
        if (!data || !data.bills) return;

        var billsHtml = '';
        data.bills.forEach(function (b) {
            billsHtml += '<div class="bill-item d-flex justify-content-between align-items-center">' +
                '<span>' + escapeHtml(b.title) + '</span>' +
                '<div>' +
                '<span class="text-muted me-2">' + fmt(b.original_amount) + '</span>' +
                '<span class="text-success me-2">' + fmt(b.amount_paid) + '</span>' +
                '<span class="text-danger">' + fmt(b.outstanding) + '</span>' +
                '</div></div>';
        });

        var html = '<tr class="detail-row"><td colspan="13"><div class="expandable-content">' +
            '<h6 class="mb-2"><i class="ri-receipt-line me-2"></i>Bill Details</h6>' +
            '<div class="row"><div class="col-md-6">' +
            '<div class="bill-item d-flex justify-content-between"><span class="fw-semibold">Total Billed:</span><span>' + fmt(data.original_amount) + '</span></div>' +
            '<div class="bill-item d-flex justify-content-between"><span class="fw-semibold">Total Paid:</span><span class="text-success">' + fmt(data.amount_paid) + '</span></div>' +
            '<div class="bill-item d-flex justify-content-between"><span class="fw-semibold">Outstanding:</span><span class="text-danger">' + fmt(data.outstanding) + '</span></div>' +
            '</div><div class="col-md-6">' +
            '<div class="bill-item d-flex justify-content-between"><span class="fw-semibold">Collection Rate:</span><span>' + data.collection_rate + '%</span></div>' +
            '<div class="bill-item d-flex justify-content-between"><span class="fw-semibold">Scholarship:</span><span>' + (data.has_scholarship ? fmt(data.savings) : 'None') + '</span></div>' +
            '<div class="bill-item d-flex justify-content-between"><span class="fw-semibold">Discount:</span><span>' + (data.has_discount ? fmt(data.savings) : 'None') + '</span></div>' +
            '</div></div><hr><h6 class="mb-2">Bill Items</h6>' + billsHtml +
            '</div></td></tr>';

        row.after(html);
        btn.html('<i class="ri-arrow-up-s-line"></i>');
    });
}

function exportDebtors(format) {
    var filters = {
        class_id: currentFilters.class_id || '',
        term_id: currentFilters.term_id || '',
        session_id: currentFilters.session_id || '',
        min_outstanding: currentFilters.min_outstanding || '',
        search: currentFilters.search || ''
    };
    var params = new URLSearchParams(filters);
    var base = exportUrls[format];
    if (!base) {
        Swal.fire('Error', 'Unknown export format: ' + format, 'error');
        return;
    }
    window.open(base + '?' + params.toString(), '_blank');
}

function buildChannelPickerHtml(count) {
    var html = '<div class="text-start">';
    html += '<p class="mb-3">Send fee reminder for <strong>' + count + '</strong> student(s).</p>';
    html += '<p class="mb-2 fw-semibold">Select channel(s):</p>';

    var any = false;
    if (reminderChannelsEnabled.email) {
        any = true;
        html += '<label class="rem-channel-label"><input type="checkbox" class="rem-channel" value="email" checked> <i class="ri-mail-line"></i> Email</label>';
    }
    if (reminderChannelsEnabled.sms) {
        any = true;
        html += '<label class="rem-channel-label"><input type="checkbox" class="rem-channel" value="sms"> <i class="ri-message-2-line"></i> SMS</label>';
    }
    if (reminderChannelsEnabled.whatsapp) {
        any = true;
        html += '<label class="rem-channel-label"><input type="checkbox" class="rem-channel" value="whatsapp"> <i class="ri-whatsapp-line"></i> WhatsApp</label>';
    }
    if (!any) {
        html += '<p class="text-danger small mb-0">No channels enabled. Set REMINDER_* in .env / config/reminders.php</p>';
    }
    html += '</div>';
    return html;
}

function sendReminders(studentIds) {
    if (!studentIds || studentIds.length === 0) {
        Swal.fire('Warning', 'Please select students first', 'warning');
        return;
    }

    Swal.fire({
        title: 'Send Payment Reminders',
        html: buildChannelPickerHtml(studentIds.length),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Send now',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
        preConfirm: function () {
            var channels = [];
            document.querySelectorAll('.rem-channel:checked').forEach(function (el) {
                channels.push(el.value);
            });
            if (channels.length === 0) {
                Swal.showValidationMessage('Select at least one channel');
                return false;
            }
            return channels;
        }
    }).then(function (result) {
        if (!result.isConfirmed) return;

        var channels = result.value;

        Swal.fire({
            title: 'Sending…',
            text: 'Please wait while reminders are processed.',
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); }
        });

        $.ajax({
            url: '{{ route("reports.analysis.send-reminders") }}',
            type: 'POST',
            data: {
                student_ids: studentIds,
                term_id: $('#term_id').val() || '',
                session_id: $('#session_id').val() || '',
                channels: channels,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                Swal.fire({
                    icon: response.success ? 'success' : 'error',
                    title: response.success ? 'Done' : 'Error',
                    html: '<pre style="text-align:left;white-space:pre-wrap;font-size:13px;margin:0">' +
                        escapeHtml(response.message || '') + '</pre>' +
                        (response.queued ? '<p class="small text-muted mt-2">Sends are processing in the background.</p>' : '')
                });
            },
            error: function (xhr) {
                var msg = 'Failed to send reminders';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    msg = 'Reminder endpoint not found. Check route reports.analysis.send-reminders';
                } else if (xhr.status === 403) {
                    msg = 'You do not have permission to send reminders.';
                } else if (xhr.status === 419) {
                    msg = 'Session expired. Refresh the page and try again.';
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });
}

$(document).ready(function () {
    showLoading(false);

    debtorsTable = $('#debtorsTable').DataTable({
        processing: false,
        serverSide: false,
        deferRender: true,
        deferLoading: 0,
        ajax: {
            url: '{{ route("reports.financial.debtors") }}',
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            data: function (d) {
                d.class_id = currentFilters.class_id || '';
                d.term_id = currentFilters.term_id || '';
                d.session_id = currentFilters.session_id || '';
                d.min_outstanding = currentFilters.min_outstanding || '';
                d.ajax = 1;
                if (d.search && typeof d.search === 'object') {
                    d.search = d.search.value || '';
                }
            },
            beforeSend: function () {
                showLoading(true);
            },
            dataSrc: function (resp) {
                var rows = Array.isArray(resp)
                    ? resp
                    : (resp && Array.isArray(resp.data) ? resp.data : []);

                if (resp && resp.success === false) {
                    Swal.fire('Error', resp.message || 'Failed to load debtors', 'error');
                }

                allRowData = rows;
                studentData = {};
                allRowData.forEach(function (item) {
                    studentData[item.student_id] = item;
                });
                updateStats();
                return allRowData;
            },
            error: function (xhr) {
                showLoading(false);
                console.error('Debtors AJAX error', xhr && xhr.status, xhr && xhr.responseText);
                var msg = 'Failed to load debtors data.';
                if (xhr && xhr.status === 403) msg = 'You do not have permission to view this report.';
                else if (xhr && xhr.status === 401) msg = 'Session expired. Please log in again.';
                else if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Error', msg, 'error');
            },
            complete: function () {
                showLoading(false);
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                render: function (d, t, row) {
                    if (!row.has_contact) {
                        return '<input type="checkbox" class="row-selector" data-student-id="' + row.student_id + '" disabled title="No contact info on file">';
                    }
                    return '<input type="checkbox" class="row-selector" data-student-id="' + row.student_id + '">';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (d, t, r, meta) {
                    return meta.row + 1;
                }
            },
            {
                data: null,
                orderable: false,
                render: function (d, t, row) {
                    return renderAvatar(row.avatar, row.student_name);
                }
            },
            { data: 'student_name' },
            { data: 'admission_no' },
            { data: 'class_name' },
            {
                data: null,
                render: function (d, t, row) {
                    return escapeHtml(row.term_name || '') + ' · ' + escapeHtml(row.session_name || '');
                }
            },
            {
                data: null,
                orderable: false,
                render: function (d, t, row) {
                    var html = renderBenefits(row.has_scholarship, row.has_discount);
                    if (!row.has_contact) {
                        html += ' <span class="benefit-badge benefit-unreachable" title="No parent email or phone on file">' +
                            '<i class="ri-error-warning-line me-1"></i>No Contact</span>';
                    }
                    return html;
                }
            },
            {
                data: 'original_amount',
                className: 'text-end',
                render: function (d) { return fmt(d); }
            },
            {
                data: 'amount_paid',
                className: 'text-end',
                render: function (d) { return fmt(d); }
            },
            {
                data: 'outstanding',
                className: 'text-end',
                render: function (d) {
                    return '<span class="' + getOutstandingClass(d) + '">' + fmt(d) + '</span>';
                }
            },
            {
                data: 'collection_rate',
                render: function (d) {
                    var cls = d >= 70 ? 'text-success' : (d >= 40 ? 'text-warning' : 'text-danger');
                    return '<span class="' + cls + '">' + d + '%</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (d, t, row) {
                    var reminderBtn = row.has_contact
                        ? '<a href="javascript:void(0)" class="btn btn-sm btn-outline-success ms-1" ' +
                          'onclick="sendReminders([' + row.student_id + ']); return false;" title="Send Reminder">' +
                          '<i class="ri-mail-send-line"></i></a>'
                        : '<span class="btn btn-sm btn-outline-secondary ms-1 disabled" title="No contact info on file">' +
                          '<i class="ri-mail-send-line"></i></span>';

                    return '<button type="button" class="btn btn-sm btn-outline-primary expand-btn" title="View Details">' +
                        '<i class="ri-arrow-down-s-line"></i></button>' + reminderBtn;
                }
            }
        ],
        createdRow: function (row, data) {
            row.setAttribute('data-student-id', data.student_id);
        },
        drawCallback: function () {
            setTimeout(function () { attachRowEvents(); }, 50);
        },
        language: {
            emptyTable: '<div class="text-center py-5 text-muted">' +
                '<i class="ri-inbox-line" style="font-size:2rem;display:block;margin-bottom:8px"></i>' +
                'No debtors found. Select filters and click Load Report.' +
                '</div>',
            processing: '',
            search: 'Search:',
            searchPlaceholder: 'Search by name or admission...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ debtors',
            infoEmpty: 'No debtors found',
            infoFiltered: '(filtered from _MAX_ total)'
        },
        pageLength: 25,
        searchDelay: 400,
        order: [[10, 'desc']]
    });

    $('#selectAll').on('change', function () {
        $('.row-selector:not(:disabled)').prop('checked', $(this).is(':checked'));
    });

    $('#loadReportBtn').on('click', function () {
        currentFilters = {
            class_id: $('#class_id').val() || null,
            term_id: $('#term_id').val() || null,
            session_id: $('#session_id').val() || null,
            min_outstanding: $('#min_outstanding').val() || null
        };
        allRowData = [];
        studentData = {};
        showLoading(true);
        debtorsTable.ajax.reload(function () {
            showLoading(false);
        }, true);
    });

    $('#resetBtn').on('click', function () {
        $('#class_id, #term_id, #session_id, #min_outstanding').val('');
        currentFilters = {};
        allRowData = [];
        studentData = {};
        debtorsTable.clear().draw();
        updateStats();
        showLoading(false);
    });

    $('#sendRemindersBtn').on('click', function () {
        var ids = [];
        $('.row-selector:checked').each(function () {
            ids.push($(this).data('student-id'));
        });

        var skippedForNoContact = $('.row-selector:disabled').length;

        if (ids.length === 0) {
            Swal.fire('Warning', 'Please select students first', 'warning');
            return;
        }

        if (skippedForNoContact > 0) {
            Swal.fire({
                title: 'Heads up',
                text: skippedForNoContact + ' debtor(s) on this page have no contact info and were excluded automatically. Continue with ' + ids.length + ' student(s)?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Continue',
            }).then(function (result) {
                if (result.isConfirmed) sendReminders(ids);
            });
        } else {
            sendReminders(ids);
        }
    });

    $('#debtorsTable_filter input').on('keyup', function (e) {
        if (e.key === 'Enter') {
            currentFilters.search = $(this).val();
        }
    });
});
</script>
@endsection