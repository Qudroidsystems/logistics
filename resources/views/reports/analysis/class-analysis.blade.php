@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
/* ── Design System ──────────────────────────────────────────────── */
:root {
    --ss-primary: #1e3a5f;
    --ss-accent:  #2563eb;
    --ss-success: #16a34a;
    --ss-warning: #d97706;
    --ss-danger:  #dc2626;
    --ss-muted:   #6b7280;
    --ss-border:  #e2e8f0;
    --ss-bg:      #f8fafc;
    --ss-card:    #ffffff;
    --ss-radius:  10px;
}

/* Loading Overlay */
#loadingOverlay {
    position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,.5);
    backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
    z-index: 99999; display:none; justify-content:center; align-items:center;
}
#loadingOverlay.active { display:flex; }
.loading-content {
    background:rgba(255,255,255,.96); border-radius:20px;
    padding:30px 40px; text-align:center;
    box-shadow:0 20px 40px rgba(0,0,0,.2);
}
.loading-spinner {
    width:40px; height:40px;
    border:3px solid #e2e8f0; border-top-color:#2563eb;
    border-radius:50%; animation:spin .8s linear infinite; margin:0 auto 15px;
}
@keyframes spin { to { transform:rotate(360deg); } }
.loading-text { font-size:14px; color:#1e3a5f; font-weight:500; }

/* Hero */
.report-hero {
    background:linear-gradient(135deg,var(--ss-primary) 0%,var(--ss-accent) 60%,#4f46e5 100%);
    border-radius:var(--ss-radius); padding:28px 32px; margin-bottom:24px;
    position:relative; overflow:hidden;
}
.report-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.report-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.report-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* Stat cards */
.stat-card {
    background:var(--ss-card); border:1px solid var(--ss-border);
    border-radius:var(--ss-radius); padding:18px 20px;
    transition:transform .15s,box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,.1); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--ss-primary); }
.stat-card .stat-label { font-size:12px; color:var(--ss-muted); margin-top:4px; }

/* Filter bar */
.filter-bar { background:#f8fafc; padding:20px; border-radius:12px; margin-bottom:24px; }
.filter-label { font-weight:600; font-size:13px; margin-bottom:8px; color:var(--ss-primary); }
.filter-label .required { color:#dc2626; }

/* Table */
.report-table th {
    background:var(--ss-primary); color:#fff;
    padding:12px 16px; font-size:13px; white-space:nowrap; font-weight:600;
}
.report-table td { padding:12px 16px; border-bottom:1px solid var(--ss-border); vertical-align:middle; }

/* Row entrance */
#analysisTable tbody tr {
    opacity:0; transform:translateY(14px);
    transition:opacity .38s cubic-bezier(.25,.46,.45,.94),
               transform .38s cubic-bezier(.25,.46,.45,.94),
               background .18s ease;
}
#analysisTable tbody tr.row-visible { opacity:1; transform:translateY(0); }

/* Row hover */
#analysisTable tbody tr:hover {
    background:#f0f6ff !important;
    box-shadow:inset 3px 0 0 #2563eb;
    transform:translateY(-1px) !important;
    transition:background .14s,box-shadow .18s,transform .18s cubic-bezier(.34,1.4,.64,1);
    position:relative; z-index:1;
}
#analysisTable tbody tr:hover .student-avatar-table {
    transform:scale(1.12); box-shadow:0 2px 8px rgba(0,0,0,.15);
}

/* Badges */
.status-paid    { background:#dcfce7!important;color:#16a34a!important;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block; }
.status-partial { background:#fef3c7!important;color:#d97706!important;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block; }
.status-unpaid  { background:#fee2e2!important;color:#dc2626!important;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block; }
.benefit-badge       { display:inline-block;padding:3px 8px;border-radius:12px;font-size:10px;font-weight:600; }
.benefit-scholarship { background:#fef3c7;color:#d97706; }
.benefit-discount    { background:#ede9fe;color:#6d28d9; }

/* Progress bar */
.progress-container { width:80px;background:#e2e8f0;border-radius:10px;overflow:hidden;margin:0 auto; }
.progress-fill      { height:5px;border-radius:10px;transition:width .3s; }
.progress-high   { background:#16a34a; }
.progress-medium { background:#d97706; }
.progress-low    { background:#dc2626; }
.progress-text   { font-size:10px;color:#6b7280;margin-top:2px;display:block; }

/* Avatars */
.student-avatar-table {
    width:35px;height:35px;border-radius:50%;object-fit:cover;
    border:2px solid #e2e8f0;cursor:pointer;
    transition:transform .18s,box-shadow .18s;
}
.student-avatar-placeholder {
    width:35px;height:35px;border-radius:50%;
    background:linear-gradient(135deg,#2563eb,#4f46e5);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-size:12px;font-weight:600;cursor:pointer;
    transition:transform .18s,box-shadow .18s;
}

/* ─────────────────────────────────────────────────────────────────
   TOOLTIP
   - The element is placed directly inside <body> (outside every
     overflow/scroll container) so it is never clipped.
   - CSS: position:fixed  so getBoundingClientRect values plug in
     directly without any scroll offset arithmetic.
   - pointer-events:none  so the tooltip never intercepts mouse
     events and mouseleave on rows fires reliably.
   ──────────────────────────────────────────────────────────────── */
#studentPopover {
    position: fixed;
    z-index: 999999;
    pointer-events: none;
    opacity: 0;
    transform: scale(.92) translateY(6px);
    transition: opacity .22s cubic-bezier(.4,0,.2,1),
                transform .22s cubic-bezier(.4,0,.2,1);
    width: 280px;
    top: -9999px; left: -9999px;
}
#studentPopover.visible { opacity:1; transform:scale(1) translateY(0); }

.popover-card {
    background:#fff;
    border-radius:20px;
    box-shadow:0 0 0 .5px rgba(0,0,0,.1),0 8px 32px rgba(0,0,0,.2);
    overflow:hidden;
}
.popover-header { background:linear-gradient(135deg,#1e3a5f,#2563eb); padding:16px; }
.popover-avatar-wrapper { display:flex;align-items:center;gap:12px; }
.popover-avatar { width:56px;height:56px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.9); }
.popover-name   { font-size:14px;font-weight:700;color:#fff; }
.popover-adm    { font-size:10px;color:rgba(255,255,255,.75); }
.popover-body   { padding:12px 16px; }
.popover-stats-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px; }
.popover-stat     { background:#f8fafc;border-radius:10px;padding:8px;text-align:center; }
.popover-stat-val { font-size:14px;font-weight:700;color:#1e3a5f;display:block; }
.popover-stat-lbl { font-size:9px;color:#9ca3af;display:block; }
.popover-subject-list { max-height:150px;overflow-y:auto; }
.popover-subject-row  { display:flex;justify-content:space-between;padding:6px 8px;border-bottom:1px solid #e2e8f0;font-size:11px; }

/* DataTables chrome */
.dataTables_wrapper .dataTables_filter { margin-bottom:15px; }
.dataTables_wrapper .dataTables_filter input {
    border:1.5px solid var(--ss-border);border-radius:8px;
    padding:8px 14px;margin-left:8px;font-size:13px;width:250px;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color:var(--ss-accent);outline:none;box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--ss-border);border-radius:8px;
    padding:6px 10px;margin:0 6px;font-size:13px;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--ss-accent)!important;border-color:var(--ss-accent)!important;color:#fff!important;
}
.dataTables_wrapper .dataTables_info { font-size:13px;color:var(--ss-muted); }

/* Image zoom modal */
.image-zoom-modal .modal-content { background:transparent;border:none; }
.zoomed-image { max-width:90vw;max-height:75vh;border-radius:16px;border:4px solid #fff;cursor:pointer; }
.btn-close-zoom {
    position:absolute;top:20px;right:30px;
    background:rgba(0,0,0,.7);border:none;border-radius:50%;
    width:38px;height:38px;color:#fff;font-size:18px;cursor:pointer;
}

@media (prefers-reduced-motion:reduce) {
    #analysisTable tbody tr, #analysisTable tbody tr:hover {
        transition:background .15s!important; transform:none!important; opacity:1!important;
    }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading payment data...</div>
        </div>
    </div>

    <div class="report-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1><i class="ri-bar-chart-line me-2"></i>Class Analysis Report</h1>
                <p>Analyse fee collection by class, term, and session</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-light btn-sm" onclick="exportReport('excel')"><i class="ri-file-excel-line"></i> Excel</button>
                <button class="btn btn-light btn-sm" onclick="exportReport('pdf')"><i class="ri-file-pdf-line"></i> PDF</button>
                <button class="btn btn-light btn-sm" onclick="window.print()"><i class="ri-printer-line"></i> Print</button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-value" id="totalStudents">0</div><div class="stat-label">Total Students</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-success" id="totalBilled">₦0</div><div class="stat-label">Total Billed</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-info" id="totalPaid">₦0</div><div class="stat-label">Total Paid</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value text-warning" id="collectionRate">0%</div><div class="stat-label">Collection Rate</div></div></div>
    </div>

    <div class="filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="filter-label">Class</label>
                <select class="form-select" id="class_id">
                    <option value="">-- All Classes --</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="filter-label">Term <span class="required">*</span></label>
                <select class="form-select" id="term_id">
                    <option value="">-- Select Term --</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="filter-label">Session <span class="required">*</span></label>
                <select class="form-select" id="session_id">
                    <option value="">-- Select Session --</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <button class="btn btn-primary" id="loadReportBtn"><i class="ri-search-line me-1"></i> Load Report</button>
                <button class="btn btn-secondary ms-2" id="resetBtn"><i class="ri-refresh-line me-1"></i> Reset</button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="ri-table-line me-2"></i>Student Payment Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table report-table w-100" id="analysisTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="60">Photo</th>
                            <th>Student Name</th>
                            <th>Admission No</th>
                            <th>Gender</th>
                            <th>Benefits</th>
                            <th class="text-end">Total Billed (₦)</th>
                            <th class="text-end">Total Paid (₦)</th>
                            <th class="text-end">Outstanding (₦)</th>
                            <th width="100">Progress</th>
                            <th width="100">Status</th>
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

{{--
    Tooltip element — placed outside every container so it is never
    clipped by overflow:hidden on parent cards/tables.
    position:fixed is set in CSS; JS reads getBoundingClientRect()
    and writes top/left directly — no scroll offsets needed.
--}}
<div id="studentPopover">
    <div class="popover-card">
        <div class="popover-header">
            <div class="popover-avatar-wrapper">
                <img id="popAvatar" src="" alt="" class="popover-avatar">
                <div>
                    <div class="popover-name" id="popName">—</div>
                    <div class="popover-adm"  id="popAdm">—</div>
                </div>
            </div>
        </div>
        <div class="popover-body">
            <div class="popover-stats-grid">
                <div class="popover-stat"><span class="popover-stat-val" id="popBilled">—</span><span class="popover-stat-lbl">Billed</span></div>
                <div class="popover-stat"><span class="popover-stat-val" id="popPaid">—</span><span class="popover-stat-lbl">Paid</span></div>
                <div class="popover-stat"><span class="popover-stat-val" id="popOutstanding">—</span><span class="popover-stat-lbl">Owing</span></div>
            </div>
            <div class="popover-subject-list" id="popBillList"></div>
        </div>
    </div>
</div>

<!-- Image Zoom Modal -->
<div class="modal fade image-zoom-modal" id="imageZoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <button class="btn-close-zoom" data-bs-dismiss="modal">×</button>
            <div class="modal-body text-center">
                <img id="zoomedImage" src="" class="zoomed-image">
                <div id="zoomedImageName" style="color:#fff;margin-top:18px;font-size:14px;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ====================================================================
   STATE
   ==================================================================== */
let analysisTable;
let currentFilters = {};
let studentData    = {};   // keyed by student_id  – for tooltip lookups
let allRowData     = [];   // every row returned    – for stat totals

/* ====================================================================
   HELPERS
   ==================================================================== */
function fmt(n) {
    return '₦' + parseFloat(n||0).toLocaleString('en-NG',{minimumFractionDigits:2});
}
function initials(name) {
    if (!name) return 'ST';
    return name.split(' ').slice(0,2).map(w=>w[0]||'').join('').toUpperCase();
}
function avatarUrl(pic) {
    if (!pic || pic==='unnamed.jpg') return null;
    return '/storage/images/student_avatars/'+pic;
}
function progressClass(p) {
    return p>=70?'progress-high':p>=40?'progress-medium':'progress-low';
}
function esc(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
}
function showLoading(on) {
    document.getElementById('loadingOverlay').classList.toggle('active',on);
}

/* ====================================================================
   RENDERS
   ==================================================================== */
function renderAvatar(pic,name,adm) {
    const url=avatarUrl(pic), ini=initials(name);
    const n=esc(name), a=esc(adm);
    if (url) return `<img src="${url}" class="student-avatar-table" data-name="${n}" data-admission="${a}" data-avatar="${url}" alt="${n}">`;
    return `<div class="student-avatar-placeholder" data-name="${n}" data-admission="${a}" data-avatar="">${ini}</div>`;
}
function renderBenefits(sc,disc) {
    let h='';
    if(sc)   h+='<span class="benefit-badge benefit-scholarship me-1">🎓 Scholarship</span>';
    if(disc) h+='<span class="benefit-badge benefit-discount">🏷️ Discount</span>';
    return h||'<span class="text-muted">—</span>';
}
function renderStatus(s) {
    if(s==='Fully Paid') return '<span class="status-paid"><i class="ri-checkbox-circle-line me-1"></i>Fully Paid</span>';
    if(s==='Partial')    return '<span class="status-partial"><i class="ri-time-line me-1"></i>Partial</span>';
    return '<span class="status-unpaid"><i class="ri-error-warning-line me-1"></i>Unpaid</span>';
}
function renderProgress(p) {
    return `<div class="progress-container"><div class="progress-fill ${progressClass(p)}" style="width:${p}%"></div></div><span class="progress-text">${p}%</span>`;
}

/* ====================================================================
   STATS  – totals across the FULL dataset, not just the visible page
   ==================================================================== */
function updateStats() {
    let billed=0,paid=0;
    allRowData.forEach(r=>{
        billed+=parseFloat(r.total_billed)||0;
        paid  +=parseFloat(r.total_paid)  ||0;
    });
    const rate=billed>0?((paid/billed)*100).toFixed(1):0;
    document.getElementById('totalStudents').textContent = allRowData.length;
    document.getElementById('totalBilled').textContent   = fmt(billed);
    document.getElementById('totalPaid').textContent     = fmt(paid);
    document.getElementById('collectionRate').textContent= rate+'%';
}

/* ====================================================================
   ROW ENTRANCE ANIMATION
   ==================================================================== */
function initRowEntrance() {
    const rows=document.querySelectorAll('#analysisTable tbody tr');
    if (!rows.length) return;
    rows.forEach(r=>r.classList.remove('row-visible'));
    if (window.matchMedia('(prefers-reduced-motion:reduce)').matches) {
        rows.forEach(r=>r.classList.add('row-visible')); return;
    }
    const obs=new IntersectionObserver(entries=>{
        entries.forEach(e=>{
            if (!e.isIntersecting) return;
            const row=e.target;
            const idx=Array.from(rows).indexOf(row);
            setTimeout(()=>row.classList.add('row-visible'), Math.min(idx*38,15*38)+60);
            obs.unobserve(row);
        });
    },{threshold:.05,rootMargin:'0px 0px -20px 0px'});
    rows.forEach(r=>obs.observe(r));
}

/* ====================================================================
   TOOLTIP
   ──────────────────────────────────────────────────────────────────────
   Why position:fixed works here:
   • The #studentPopover <div> is at the very end of <body>, outside
     every card/table/overflow container.
   • position:fixed means its coordinates are relative to the VIEWPORT,
     so we just use getBoundingClientRect() values directly — no
     offsetParent arithmetic, no scroll offset, no iframe weirdness.
   • pointer-events:none ensures the tooltip never receives mouse events,
     so mouseleave on table rows always fires cleanly.
   ==================================================================== */
const popEl   = document.getElementById('studentPopover');
let popTimer  = null;
let hideTimer = null;

function fillPopover(id) {
    const s=studentData[id];
    if (!s) return;
    document.getElementById('popName').textContent        = s.name;
    document.getElementById('popAdm').textContent         = 'Adm: '+s.admission;
    document.getElementById('popBilled').textContent      = fmt(s.billed);
    document.getElementById('popPaid').textContent        = fmt(s.paid);
    document.getElementById('popOutstanding').textContent = fmt(s.outstanding);
    document.getElementById('popAvatar').src              = avatarUrl(s.avatar)||'';

    const list=document.getElementById('popBillList');
    list.innerHTML='';
    if (s.bills && s.bills.length) {
        s.bills.forEach(b=>{
            list.innerHTML+=`<div class="popover-subject-row"><span>${esc(b.title)}</span><span class="fw-bold">${fmt(b.balance)}</span></div>`;
        });
    } else {
        list.innerHTML='<div class="popover-subject-row text-muted">No bills available</div>';
    }
}

function positionPopover(rect) {
    /*
     * rect  = getBoundingClientRect() of the hovered <tr>
     * We show the card just below the row's bottom edge.
     * Clamp so it never overflows the right or bottom of the viewport.
     */
    const pw=280, ph=340;
    const vw=window.innerWidth, vh=window.innerHeight;

    let left = rect.left;
    let top  = rect.bottom + 8;

    if (left+pw > vw-8) left = vw-pw-8;
    if (left < 8)       left = 8;

    // Flip above the row if not enough room below
    if (top+ph > vh-8)  top = rect.top-ph-8;
    if (top < 8)        top = 8;

    popEl.style.left = left+'px';
    popEl.style.top  = top +'px';
}

function showPopover(row) {
    clearTimeout(hideTimer);
    const id=row.getAttribute('data-student-id');
    if (!id || !studentData[id]) return;
    fillPopover(id);
    positionPopover(row.getBoundingClientRect());
    popEl.classList.add('visible');
}
function hidePopover() {
    hideTimer=setTimeout(()=>popEl.classList.remove('visible'), 180);
}

/* ====================================================================
   ROW EVENT BINDING  (re-run after every DataTable redraw)
   ==================================================================== */
function attachRowEvents() {
    $('#analysisTable tbody tr').off('mouseenter mouseleave');
    $('#analysisTable tbody tr').on('mouseenter',function(){
        const row=this;
        clearTimeout(popTimer);
        popTimer=setTimeout(()=>showPopover(row), 260);
    }).on('mouseleave',function(){
        clearTimeout(popTimer);
        hidePopover();
    });
}

/* ====================================================================
   DATATABLE
   ──────────────────────────────────────────────────────────────────────
   PAGINATION FIX — root cause explained:
   ─────────────────────────────────────
   The controller (getClassAnalysisData) always returns every student
   for the selected filters. It sets:
       recordsTotal    = count($data)
       recordsFiltered = count($data)
   and does NOT slice with $request->input('start') / 'length'.

   When serverSide:true is set, DataTables sends draw/start/length to
   the server and expects the server to return only that slice.  Because
   the server always returns everything and always reports
   recordsFiltered = total, the paginator thinks there is only one page
   no matter how many rows exist.

   Fix: use serverSide:false (client-side mode).
   • The AJAX call still fires on demand when "Load Report" is clicked.
   • Pagination, search, and sorting all run in the browser on the full
     dataset that the server returned.
   • No controller changes needed.
   ==================================================================== */
$(document).ready(function(){

    analysisTable = $('#analysisTable').DataTable({
        processing: false,   // we show our own branded overlay
        serverSide: false,   // ← PAGINATION FIX (see above)
        deferRender: true,   // only render visible rows for speed
        ajax: {
            url:  '{{ route("reports.analysis.class-data") }}',
            type: 'GET',
            data: function(d){
                d.class_id   = currentFilters.class_id   || '';
                d.term_id    = currentFilters.term_id    || '';
                d.session_id = currentFilters.session_id || '';
            },
            beforeSend: function(){ showLoading(true); },
            complete:   function(){ showLoading(false); },
            dataSrc: function(resp){
                allRowData  = resp.data || [];
                studentData = {};
                allRowData.forEach(item=>{
                    studentData[item.student_id] = {
                        name:        item.student_name,
                        admission:   item.admission_no,
                        avatar:      item.avatar,
                        billed:      item.total_billed,
                        paid:        item.total_paid,
                        outstanding: item.outstanding,
                        bills:       item.bills || []
                    };
                });
                updateStats();
                return allRowData;
            }
        },
        columns: [
            { data:null, orderable:false,
              render:(d,t,r,meta)=>meta.row+1 },
            { data:null, orderable:false,
              render:(d,t,row)=>renderAvatar(row.avatar,row.student_name,row.admission_no) },
            { data:'student_name' },
            { data:'admission_no' },
            { data:'gender' },
            { data:null, orderable:false,
              render:(d,t,row)=>renderBenefits(row.has_scholarship,row.has_discount) },
            { data:'total_billed',  className:'text-end', render:d=>fmt(d) },
            { data:'total_paid',    className:'text-end', render:d=>fmt(d) },
            { data:'outstanding',   className:'text-end', render:d=>fmt(d) },
            { data:'completion',    render:d=>renderProgress(d) },
            { data:'status',        render:d=>renderStatus(d) },
            { data:null, orderable:false,
              render:(d,t,row)=>{
                const base='/reports/analysis/student/'+row.student_id
                    +'/'+(currentFilters.class_id||'')
                    +'/'+(currentFilters.term_id||'')
                    +'/'+(currentFilters.session_id||'');
                return `<a href="${base}" class="btn btn-sm btn-outline-primary" target="_blank"><i class="ri-eye-line"></i></a>`;
              }
            }
        ],
        createdRow: function(row,data){
            // Stamp student_id on the <tr> so the tooltip can read it
            row.setAttribute('data-student-id', data.student_id);
        },
        drawCallback: function(){
            setTimeout(()=>{
                initRowEntrance();
                attachRowEvents();
            }, 60);
        },
        language: {
            emptyTable:   '<div class="text-center py-5 text-muted"><i class="ri-inbox-line" style="font-size:2rem;display:block;margin-bottom:8px"></i>No data yet. Select filters and click Load Report.</div>',
            processing:   '',
            search:       'Search:',
            searchPlaceholder:'Filter by name, admission…',
            lengthMenu:   'Show _MENU_ entries',
            info:         'Showing _START_ to _END_ of _TOTAL_ students',
            infoEmpty:    'No students found',
            infoFiltered: '(filtered from _MAX_ total)'
        },
        pageLength: 25,
        searchDelay: 400,   // ms idle after typing before client-side filter runs
        order: [[8,'desc']]
    });

    /* ── Load Report ─────────────────────────────────────────────── */
    $('#loadReportBtn').on('click',function(){
        const termId    = $('#term_id').val();
        const sessionId = $('#session_id').val();
        if (!termId || !sessionId) {
            Swal.fire('Warning','Please select term and session','warning');
            return;
        }
        currentFilters = {
            class_id:   $('#class_id').val()||null,
            term_id:    termId,
            session_id: sessionId
        };
        allRowData  = [];
        studentData = {};
        analysisTable.ajax.reload();
    });

    /* ── Reset ───────────────────────────────────────────────────── */
    $('#resetBtn').on('click',function(){
        $('#class_id,#term_id,#session_id').val('');
        currentFilters={};
        allRowData=[];
        studentData={};
        analysisTable.clear().draw();
        updateStats();
    });
});

/* ====================================================================
   EXPORT
   ==================================================================== */
function exportReport(format) {
    if (!currentFilters.term_id) {
        Swal.fire('Warning','Please load report data first','warning');
        return;
    }
    const url='{{ route("reports.analysis.export") }}'
        +'?class_id='  +(currentFilters.class_id  ||'')
        +'&term_id='   + currentFilters.term_id
        +'&session_id='+ currentFilters.session_id
        +'&format='    + format;
    window.open(url,'_blank');
}

/* ====================================================================
   AVATAR CLICK → IMAGE ZOOM MODAL
   ==================================================================== */
$(document).on('click','.student-avatar-table,.student-avatar-placeholder',function(){
    const isImg  = $(this).is('img');
    const imgUrl = isImg ? $(this).attr('src') : null;
    const name   = $(this).data('name');
    const adm    = $(this).data('admission');

    $('#zoomedImageName').text(name+' ('+adm+')');

    if (imgUrl) {
        $('#zoomedImage').attr('src', imgUrl);
    } else {
        const ini=initials(name);
        const cv=document.createElement('canvas');
        cv.width=cv.height=400;
        const ctx=cv.getContext('2d');
        const g=ctx.createLinearGradient(0,0,400,400);
        g.addColorStop(0,'#2563eb'); g.addColorStop(1,'#7c3aed');
        ctx.fillStyle=g; ctx.fillRect(0,0,400,400);
        ctx.fillStyle='#fff'; ctx.font='bold 160px Arial,sans-serif';
        ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText(ini.substring(0,2),200,200);
        $('#zoomedImage').attr('src',cv.toDataURL());
    }
    $('#imageZoomModal').modal('show');
});
$(document).on('click','.zoomed-image',()=>$('#imageZoomModal').modal('hide'));
</script>
@endsection
