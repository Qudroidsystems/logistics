@extends('layouts.master')

@section('content')
<style>
:root {
    --lm-primary : #1e3a5f;
    --lm-accent  : #2563eb;
    --lm-success : #16a34a;
    --lm-warning : #d97706;
    --lm-danger  : #dc2626;
    --lm-muted   : #6b7280;
    --lm-border  : #e2e8f0;
    --lm-card    : #ffffff;
    --lm-radius  : 12px;
    --lm-shadow  : 0 1px 6px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.04);
}

.lm-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 0;
    width: 100%;
}

.lm-spin { animation: lm-spin .8s linear infinite; }
@keyframes lm-spin { to { transform: rotate(360deg); } }
@keyframes lm-fadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
@keyframes lm-slideIn { from { transform:translateX(110%); opacity:0; } to { transform:none; opacity:1; } }
@keyframes lm-pulse-bg { 0%,100%{background:#f1f5f9} 50%{background:#e2e8f0} }

.lm-hero {
    background: linear-gradient(135deg,#1e3a5f 0%,#2563eb 58%,#4f46e5 100%);
    border-radius: var(--lm-radius);
    padding: 26px 30px;
    position: relative; overflow: hidden;
    animation: lm-fadeUp .45s ease both;
}
.lm-hero::before {
    content:''; position:absolute; width:260px; height:260px;
    top:-80px; right:-50px; border-radius:50%;
    background:rgba(255,255,255,.06);
}
.lm-hero::after {
    content:''; position:absolute; width:120px; height:120px;
    bottom:-40px; right:140px; border-radius:50%;
    background:rgba(255,255,255,.05);
}
.lm-hero h1 { font-size:21px; font-weight:700; color:#fff; margin:0 0 5px; position:relative; }
.lm-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }
.lm-hero-badge {
    display:inline-flex; align-items:center; gap:6px; position:relative;
    background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.2);
    color:#fff; border-radius:20px; padding:3px 14px;
    font-size:11.5px; font-weight:600; margin-top:10px;
}

.lm-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
}
@media(max-width:992px){ .lm-stats{ grid-template-columns: repeat(3,1fr); } }
@media(max-width:576px){ .lm-stats{ grid-template-columns: repeat(2,1fr); } }

.stat-card {
    background: var(--lm-card);
    border: 1px solid var(--lm-border);
    border-radius: var(--lm-radius);
    padding: 16px 18px;
    position: relative; overflow: hidden;
    transition: transform .18s cubic-bezier(.34,1.4,.64,1), box-shadow .18s ease;
    animation: lm-fadeUp .4s ease both;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
.stat-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    border-radius: var(--lm-radius) var(--lm-radius) 0 0;
}
.sc-total::before   { background:var(--lm-accent); }
.sc-open::before    { background:var(--lm-success); }
.sc-indiv::before   { background:var(--lm-warning); }
.sc-global::before  { background:var(--lm-danger); }
.sc-disabled::before{ background:var(--lm-muted); }
.stat-value { font-size:26px; font-weight:800; color:var(--lm-primary); line-height:1; }
.stat-label { font-size:10.5px; font-weight:700; color:var(--lm-muted); margin-top:5px; text-transform:uppercase; letter-spacing:.4px; }
.stat-ico   { position:absolute; top:12px; right:14px; font-size:32px; opacity:.08; transition:opacity .2s,transform .2s; }
.stat-card:hover .stat-ico { opacity:.16; transform:scale(1.1) rotate(-5deg); }

.lm-filter {
    background: var(--lm-card);
    border: 1px solid var(--lm-border);
    border-radius: var(--lm-radius);
    padding: 18px 22px;
    box-shadow: var(--lm-shadow);
    animation: lm-fadeUp .4s .08s ease both;
}
.fg label {
    font-size:10.5px; font-weight:700; margin-bottom:5px; color:#475569;
    text-transform:uppercase; letter-spacing:.4px; display:block;
}
.fg input, .fg select {
    width:100%; padding:8px 11px;
    border:1.5px solid #cbd5e1; border-radius:8px;
    font-size:13px; color:#1e293b; background:#fff;
    transition:border-color .18s, box-shadow .18s;
    height: 38px;
}
.fg input:focus, .fg select:focus {
    outline:none; border-color:var(--lm-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.12);
}

.lm-action-bar {
    background: var(--lm-card);
    border: 1px solid var(--lm-border);
    border-radius: var(--lm-radius);
    padding: 12px 18px;
    display: flex; gap:10px; flex-wrap:wrap; align-items:center;
    box-shadow: var(--lm-shadow);
    animation: lm-fadeUp .4s .12s ease both;
}
.sel-info {
    font-size:12.5px; color:var(--lm-muted); margin-right:auto;
    background:#f1f5f9; padding:5px 14px; border-radius:20px;
    transition:background .2s, color .2s;
}
.sel-info.active { background:#dbeafe; color:#1d4ed8; }
.sel-info strong { font-weight:700; }

.btn {
    padding:7px 16px; border:none; border-radius:8px;
    font-size:12.5px; font-weight:600; cursor:pointer;
    transition:all .18s cubic-bezier(.34,1.4,.64,1);
    display:inline-flex; align-items:center; gap:6px;
    text-decoration:none; white-space:nowrap; line-height:1.4;
}
.btn-primary   { background:var(--lm-accent); color:#fff; }
.btn-primary:hover   { background:#1d4ed8; box-shadow:0 4px 12px rgba(37,99,235,.35); color:#fff; }
.btn-success   { background:#10b981; color:#fff; }
.btn-success:hover   { background:#059669; box-shadow:0 4px 12px rgba(16,185,129,.3); color:#fff; }
.btn-warning   { background:#f59e0b; color:#fff; }
.btn-warning:hover   { background:#d97706; box-shadow:0 4px 12px rgba(245,158,11,.3); color:#fff; }
.btn-danger    { background:#ef4444; color:#fff; }
.btn-danger:hover    { background:#dc2626; box-shadow:0 4px 12px rgba(239,68,68,.3); color:#fff; }
.btn-secondary { background:#64748b; color:#fff; }
.btn-secondary:hover { background:#475569; box-shadow:0 4px 12px rgba(100,116,139,.3); color:#fff; }
.btn-outline   { background:transparent; border:1.5px solid #cbd5e1; color:#475569; }
.btn-outline:hover   { background:#f8fafc; border-color:#94a3b8; color:#1e293b; }
.btn:disabled  { opacity:.4; cursor:not-allowed; transform:none !important; box-shadow:none !important; }

.lm-table-card {
    background: var(--lm-card);
    border: 1px solid var(--lm-border);
    border-radius: var(--lm-radius);
    overflow: hidden;
    box-shadow: var(--lm-shadow);
    animation: lm-fadeUp .4s .16s ease both;
    width: 100%;
    min-width: 0;
}
.lm-table-card .tc-header {
    background: var(--lm-primary);
    padding: 14px 20px;
    display: flex; align-items:center; justify-content:space-between;
}
.lm-table-card .tc-header h5 {
    color:#fff; margin:0; font-size:14.5px; font-weight:600;
    display:flex; align-items:center; gap:8px;
}
.tc-badge {
    background:rgba(255,255,255,.2); color:#fff;
    border-radius:20px; padding:2px 12px;
    font-size:12px; font-weight:700;
    transition: transform .3s cubic-bezier(.34,1.4,.64,1);
}
.tc-badge.pop { animation: lm-pop .35s cubic-bezier(.34,1.4,.64,1) both; }
@keyframes lm-pop { 0%{transform:scale(1)} 50%{transform:scale(1.3)} 100%{transform:scale(1)} }

#scoresheetsTable {
    width:100%; border-collapse:collapse; font-size:12.5px;
    table-layout: auto;
}
#scoresheetsTable thead th {
    background:var(--lm-primary); color:#fff;
    padding:11px 14px; font-weight:600; font-size:12px;
    white-space:nowrap; border:none;
    position: sticky; top:0; z-index:2;
}

#scoresheetsTableBody tr.lm-row {
    opacity:0; transform:translateY(10px);
    transition: opacity .34s cubic-bezier(.25,.46,.45,.94),
                transform .34s cubic-bezier(.25,.46,.45,.94),
                background .15s ease;
}
#scoresheetsTableBody tr.lm-row.visible { opacity:1; transform:none; }

#scoresheetsTableBody tr.lm-row td {
    padding:11px 14px; vertical-align:middle;
    border-bottom:1px solid var(--lm-border);
}

#scoresheetsTableBody tr.lm-row:hover td { background:#f0f6ff !important; }
#scoresheetsTableBody tr.lm-row:hover td:nth-child(2) {
    box-shadow: inset 3px 0 0 var(--lm-accent);
}
#scoresheetsTableBody tr.lm-row:hover { transform:translateY(-1px) !important; z-index:1; position:relative; }

#scoresheetsTableBody tr.rs-indiv    td { background:#fffdf5; }
#scoresheetsTableBody tr.rs-global   td { background:#fff8f8; }
#scoresheetsTableBody tr.rs-disabled td { background:#fafafa; }
#scoresheetsTableBody tr.rs-indiv:hover  td { background:#fef9ec !important; }
#scoresheetsTableBody tr.rs-global:hover td { background:#fff0f0 !important; }
#scoresheetsTableBody tr.rs-disabled:hover td { background:#f1f5f9 !important; }

#scoresheetsTableBody tr.lm-row .row-cb {
    opacity:.3; transform:scale(.82);
    transition:opacity .16s,transform .16s cubic-bezier(.34,1.4,.64,1);
}
#scoresheetsTableBody tr.lm-row:hover .row-cb,
#scoresheetsTableBody tr.lm-row .row-cb:checked { opacity:1; transform:scale(1); }

.t-avatar {
    width:36px; height:36px; border-radius:50%; flex-shrink:0;
    background:linear-gradient(135deg,var(--lm-accent),#4f46e5);
    color:#fff; font-size:12px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    border:2px solid rgba(255,255,255,.85);
    box-shadow:0 2px 6px rgba(37,99,235,.22);
    transition:transform .2s cubic-bezier(.34,1.4,.64,1);
}
#scoresheetsTableBody tr.lm-row:hover .t-avatar { transform:scale(1.1); }

.lock-bar { height:4px; border-radius:2px; background:#e2e8f0; margin-top:5px; overflow:hidden; }
.lock-bar-fill { height:100%; border-radius:2px; background:#f59e0b; transition:width .4s ease; }

.status-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 11px; border-radius:20px; font-size:11.5px; font-weight:600;
}
.sb-open     { background:#d1fae5; color:#065f46; }
.sb-indiv    { background:#fef3c7; color:#92400e; }
.sb-global   { background:#fee2e2; color:#991b1b; }
.sb-disabled { background:#e5e7eb; color:#374151; }

.row-actions { display:flex; gap:5px; flex-wrap:wrap; }
.ib {
    width:30px; height:30px; border-radius:7px;
    background:transparent; border:1.5px solid var(--lm-border);
    cursor:pointer; font-size:13px; color:#64748b;
    display:inline-flex; align-items:center; justify-content:center;
    transition:all .16s cubic-bezier(.34,1.4,.64,1);
}
.ib:hover { transform:scale(1.12); }
.ib-lock:hover    { background:#fef9ec; border-color:#fbbf24; color:#d97706; }
.ib-unlock:hover  { background:#ecfdf5; border-color:#6ee7b7; color:#059669; }
.ib-disable:hover { background:#fef2f2; border-color:#fca5a5; color:#dc2626; }
.ib-enable:hover  { background:#eff6ff; border-color:#93c5fd; color:#2563eb; }
.ib-info:hover    { background:#f5f3ff; border-color:#c4b5fd; color:#7c3aed; }

.skeleton {
    background: #f1f5f9;
    border-radius: 4px;
    animation: lm-pulse-bg 1.4s ease infinite;
    display: inline-block;
    height: 14px;
}

.lm-state-row td {
    padding:52px 16px !important;
    text-align:center; color:var(--lm-muted);
}
.lm-state-icon { font-size:2.6rem; display:block; margin-bottom:10px; opacity:.4; }

.tc-footer {
    padding:11px 20px; background:#f8fafc;
    border-top:1px solid var(--lm-border);
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;
    font-size:12px; color:var(--lm-muted);
}

.lm-toasts {
    position:fixed; bottom:22px; right:22px; z-index:1200;
    display:flex; flex-direction:column; gap:10px;
}
.lm-toast {
    background:#fff; border-radius:12px; padding:11px 16px;
    box-shadow:0 10px 28px rgba(0,0,0,.13);
    display:flex; align-items:center; gap:11px;
    animation:lm-slideIn .28s ease;
    border-left:4px solid; min-width:250px; max-width:400px;
    font-size:13px;
}
.lm-toast.t-success { border-left-color:#10b981; }
.lm-toast.t-error   { border-left-color:#ef4444; }
.lm-toast.t-info    { border-left-color:#3b82f6; }
.lm-toast.t-warning { border-left-color:#f59e0b; }
.lm-toast .tm       { flex:1; color:#1e293b; }
.lm-toast .tc       { background:none; border:none; cursor:pointer; color:#94a3b8; font-size:15px; padding:0; line-height:1; }
.lm-toast .tc:hover { color:#475569; }

.class-chip {
    background:#f0f4ff; color:#3b4fa0;
    border-radius:6px; padding:4px 10px;
    font-size:11.5px; font-weight:600; white-space:nowrap;
}
.subj-code {
    background:#f1f5f9; border-radius:4px;
    padding:1px 6px; margin-left:4px;
    font-weight:600; font-size:11px; color:#475569;
}

@media (prefers-reduced-motion:reduce) {
    #scoresheetsTableBody tr.lm-row {
        transition:background .15s ease !important;
        transform:none !important; opacity:1 !important;
    }
}

@media(max-width:768px){
    .lm-hero { padding:18px; }
    .lm-hero h1 { font-size:17px; }
    .lm-action-bar { flex-direction:column; align-items:stretch; }
    .sel-info { margin-right:0; text-align:center; }
    .lm-filter { padding:14px; }
    .lm-table-card { overflow-x:auto; }
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <div class="lm-page">
        <div class="lm-hero">
            <h1><i class="ri-shield-lock-line me-2"></i>Scoresheet Lock Management</h1>
            <p>Manage locks, disable teacher editing, and control access to scoresheets across all subjects.</p>
            <div class="lm-hero-badge">
                <i class="ri-database-2-line"></i>
                <span id="heroCount">Loading…</span>
            </div>
        </div>

        <div class="lm-stats">
            <div class="stat-card sc-total"><div class="stat-ico"><i class="ri-file-list-line"></i></div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Scoresheets</div></div>
            <div class="stat-card sc-open"><div class="stat-ico"><i class="ri-lock-unlock-line"></i></div><div class="stat-value" id="statOpen" style="color:var(--lm-success)">—</div><div class="stat-label">Open</div></div>
            <div class="stat-card sc-indiv"><div class="stat-ico"><i class="ri-lock-line"></i></div><div class="stat-value" id="statIndividual" style="color:var(--lm-warning)">—</div><div class="stat-label">Individually Locked</div></div>
            <div class="stat-card sc-global"><div class="stat-ico"><i class="ri-global-line"></i></div><div class="stat-value" id="statGlobal" style="color:var(--lm-danger)">—</div><div class="stat-label">Globally Locked</div></div>
            <div class="stat-card sc-disabled"><div class="stat-ico"><i class="ri-ban-line"></i></div><div class="stat-value" id="statDisabled" style="color:var(--lm-muted)">—</div><div class="stat-label">Editing Disabled</div></div>
        </div>

        <div class="lm-filter">
            <div class="row g-2 align-items-end">
                <div class="col-md-3"><div class="fg"><label><i class="ri-search-line me-1"></i>Search</label><input type="text" id="searchInput" placeholder="Teacher, subject or code…"></div></div>
                <div class="col-md-2"><div class="fg"><label><i class="ri-calendar-line me-1"></i>Term</label><select id="termFilter"><option value="">All Terms</option></select></div></div>
                <div class="col-md-2"><div class="fg"><label><i class="ri-calendar-event-line me-1"></i>Session</label><select id="sessionFilter"><option value="">All Sessions</option></select></div></div>
                <div class="col-md-2"><div class="fg"><label><i class="ri-group-line me-1"></i>Class</label><select id="classFilter"><option value="">All Classes</option></select></div></div>
                <div class="col-md-2"><div class="fg"><label><i class="ri-shield-line me-1"></i>Status</label><select id="statusFilter"><option value="">All Statuses</option><option value="open">Open</option><option value="individual">Individually Locked</option><option value="global">Globally Locked</option><option value="disabled">Editing Disabled</option></select></div></div>
                <div class="col-md-1"><button class="btn btn-primary w-100" id="applyFiltersBtn" style="height:38px;"><i class="ri-filter-3-line"></i></button></div>
            </div>
        </div>

        <div class="lm-action-bar">
            <div class="sel-info" id="selInfo"><i class="ri-checkbox-line me-1"></i><strong id="selectedCount">0</strong> scoresheet(s) selected</div>
            <button class="btn btn-warning btn-sm" id="bulkLockIndivBtn" disabled><i class="ri-lock-line"></i>Lock Individual</button>
            <button class="btn btn-success btn-sm" id="bulkUnlockIndivBtn" disabled><i class="ri-lock-unlock-line"></i>Unlock Individual</button>
            <button class="btn btn-danger btn-sm" id="bulkLockGlobalBtn" disabled><i class="ri-global-line"></i>Lock Global</button>
            <button class="btn btn-secondary btn-sm" id="bulkUnlockGlobalBtn" disabled><i class="ri-global-line"></i>Unlock Global</button>
            <button class="btn btn-danger btn-sm" id="bulkDisableBtn" disabled><i class="ri-ban-line"></i>Disable Editing</button>
            <button class="btn btn-success btn-sm" id="bulkEnableBtn" disabled><i class="ri-check-line"></i>Enable Editing</button>
            <button class="btn btn-outline btn-sm" id="refreshBtn"><i class="ri-refresh-line"></i>Refresh</button>
            <a href="{{ route('admin.score-entry.index') }}" class="btn btn-outline btn-sm"><i class="ri-arrow-left-line"></i>Back</a>
        </div>

        <div class="lm-table-card">
            <div class="tc-header"><h5><i class="ri-list-check"></i>Scoresheets</h5><span class="tc-badge" id="recordCount">—</span></div>
            <div style="overflow-x:auto;"><table id="scoresheetsTable"><thead><tr><th style="width:42px;padding-left:16px;"><input type="checkbox" id="selectAll" class="form-check-input"></th><th>Teacher &amp; Subject</th><th>Class</th><th>Term</th><th>Session</th><th>Lock Status</th><th style="width:210px;">Actions</th></tr></thead><tbody id="scoresheetsTableBody"><tr class="lm-state-row"><td colspan="7"><i class="ri-loader-4-line lm-spin lm-state-icon"></i>Loading scoresheets…</td></tr></tbody></table></div>
            <div class="tc-footer"><span><i class="ri-information-line me-1 text-info"></i>Use row icons for single-row actions · action bar for bulk operations.</span><span id="footerCount" class="fw-semibold">— records</span></div>
        </div>
    </div>
</div>
</div>
</div>

<div id="lmToasts" class="lm-toasts"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const ROUTES = { list: '{{ route("admin.score-entry.scoresheets-list") }}', bulk: '{{ route("admin.score-entry.bulk-lock-management") }}' };
const CSRF = '{{ csrf_token() }}';

let allData = [], selectedIds = new Set(), filtersReady = false;

const ACTION = {
    lock_individual: { title: 'Lock Scoresheets (Individual)', msg: 'Lock individual student scores in selected subjects.', reason: true, color: '#f59e0b', needsTermSession: false, buttonId: 'bulkLockIndivBtn' },
    unlock_individual: { title: 'Unlock Scoresheets (Individual)', msg: 'Unlock individual student scores in selected subjects.', reason: false, color: '#10b981', needsTermSession: false, buttonId: 'bulkUnlockIndivBtn' },
    lock_global: { title: 'Lock Scoresheets (Global)', msg: 'Global lock prevents ANY edits to these subjects.', reason: true, color: '#dc2626', needsTermSession: true, buttonId: 'bulkLockGlobalBtn' },
    unlock_global: { title: 'Unlock Scoresheets (Global)', msg: 'Remove global lock from these subjects.', reason: false, color: '#10b981', needsTermSession: true, buttonId: 'bulkUnlockGlobalBtn' },
    disable_editing: { title: 'Disable Teacher Editing', msg: 'Teachers cannot edit scores in these subjects.', reason: true, color: '#ef4444', needsTermSession: false, buttonId: 'bulkDisableBtn' },
    enable_editing: { title: 'Enable Teacher Editing', msg: 'Teachers regain editing access.', reason: false, color: '#10b981', needsTermSession: false, buttonId: 'bulkEnableBtn' },
};

document.addEventListener('DOMContentLoaded', () => {
    fetchAndRender(true);
    document.getElementById('applyFiltersBtn').addEventListener('click', () => fetchAndRender(false));
    ['termFilter','sessionFilter','classFilter','statusFilter'].forEach(id => document.getElementById(id).addEventListener('change', debounce(() => fetchAndRender(false), 280)));
    document.getElementById('searchInput').addEventListener('input', debounce(applySearchFilter, 220));
    document.getElementById('refreshBtn').addEventListener('click', () => { spinRefresh(); fetchAndRender(false); });
    document.getElementById('selectAll').addEventListener('change', onSelectAll);
    document.getElementById('bulkLockIndivBtn').addEventListener('click', () => bulkAction('lock_individual'));
    document.getElementById('bulkUnlockIndivBtn').addEventListener('click', () => bulkAction('unlock_individual'));
    document.getElementById('bulkLockGlobalBtn').addEventListener('click', () => bulkAction('lock_global'));
    document.getElementById('bulkUnlockGlobalBtn').addEventListener('click', () => bulkAction('unlock_global'));
    document.getElementById('bulkDisableBtn').addEventListener('click', () => bulkAction('disable_editing'));
    document.getElementById('bulkEnableBtn').addEventListener('click', () => bulkAction('enable_editing'));
});

function debounce(fn, ms) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); }; }

async function fetchAndRender(buildFilters) {
    showSkeleton();
    const params = new URLSearchParams();
    const search = document.getElementById('searchInput').value.trim();
    const term = document.getElementById('termFilter').value;
    const sess = document.getElementById('sessionFilter').value;
    const cls = document.getElementById('classFilter').value;
    const status = document.getElementById('statusFilter').value;
    if (search) params.set('search', search);
    if (term) params.set('term_id', term);
    if (sess) params.set('session_id', sess);
    if (cls) params.set('class_id', cls);
    if (status) params.set('status', status);
    try {
        const res = await fetch(`${ROUTES.list}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const result = await res.json();
        if (!result.success) { showError(result.message || 'Failed to load.'); toast(result.message || 'Failed to load.', 'error'); return; }
        allData = result.data || [];
        if (buildFilters && result.filters && !filtersReady) { buildFilterDropdowns(result.filters); filtersReady = true; }
        renderTable(allData);
        updateStats(allData);
    } catch (err) { console.error(err); showError('Network error — please check your connection.'); toast('Network error loading scoresheets.', 'error'); }
}

function applySearchFilter() {
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    let vis = 0;
    document.querySelectorAll('#scoresheetsTableBody tr.lm-row').forEach(tr => {
        const match = !q || (tr.dataset.search || '').includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) vis++;
    });
    setRecordCount(vis);
}

function buildFilterDropdowns(filters) {
    const term = document.getElementById('termFilter'), sess = document.getElementById('sessionFilter'), cls = document.getElementById('classFilter');
    [term, sess, cls].forEach(el => { while (el.options.length > 1) el.remove(1); });
    (filters.terms || []).forEach(t => term.insertAdjacentHTML('beforeend', `<option value="${t.id}">${esc(t.term)}</option>`));
    (filters.sessions || []).forEach(s => sess.insertAdjacentHTML('beforeend', `<option value="${s.id}">${esc(s.session)}</option>`));
    (filters.classes || []).forEach(c => { const arm = c.arm?.arm || ''; cls.insertAdjacentHTML('beforeend', `<option value="${c.id}">${esc(c.schoolclass)} ${esc(arm)}</option>`); });
}

function showSkeleton() {
    const rows = Array.from({length:6}).map(() => `<tr class="lm-state-row"><td colspan="7"><div style="display:flex;align-items:center;gap:14px;padding:13px 16px;"><div class="skeleton" style="width:36px;height:36px;border-radius:50%;"></div><div style="flex:1;"><div class="skeleton" style="width:55%;height:14px;"></div><div class="skeleton" style="width:35%;height:11px;margin-top:5px;"></div></div><div class="skeleton" style="width:70px;height:14px;"></div><div class="skeleton" style="width:80px;height:14px;"></div><div class="skeleton" style="width:80px;height:14px;"></div><div class="skeleton" style="width:80px;height:14px;border-radius:20px;"></div><div class="skeleton" style="width:180px;height:14px;"></div></div></td></tr>`).join('');
    document.getElementById('scoresheetsTableBody').innerHTML = rows;
}

function showError(msg) { document.getElementById('scoresheetsTableBody').innerHTML = `<tr class="lm-state-row"><td colspan="7"><i class="ri-error-warning-line lm-state-icon" style="color:#ef4444;opacity:1;"></i>${esc(msg)}</td></tr>`; }

function renderTable(data) {
    const tbody = document.getElementById('scoresheetsTableBody');
    setRecordCount(data.length);
    if (!data.length) { tbody.innerHTML = `<tr class="lm-state-row"><td colspan="7"><i class="ri-inbox-line lm-state-icon"></i>No scoresheets match your filters.</td></tr>`; return; }
    const frag = document.createDocumentFragment();
    data.forEach(sheet => {
        const st = getStatus(sheet);
        const ini = initials(sheet.teacher_name);
        const pct = sheet.total_students > 0 ? Math.round(sheet.individually_locked_count / sheet.total_students * 100) : 0;
        const searchIndex = [sheet.teacher_name, sheet.subject_name, sheet.subject_code, sheet.class_name, sheet.term_name, sheet.session_name].join(' ').toLowerCase();
        const tr = document.createElement('tr');
        tr.className = `lm-row rs-${st.key}`;
        tr.dataset.id = sheet.subjectclass_id;
        tr.dataset.search = searchIndex;
        tr.innerHTML = `<td style="padding-left:16px;"><input type="checkbox" class="form-check-input row-cb" data-id="${sheet.subjectclass_id}" ${selectedIds.has(sheet.subjectclass_id) ? 'checked' : ''}></td>
            <td><div style="display:flex;align-items:center;gap:10px;"><div class="t-avatar">${esc(ini)}</div><div><div style="font-weight:600;font-size:13px;color:#1e293b;">${esc(sheet.teacher_name)}</div><div style="font-size:11.5px;color:#64748b;">${esc(sheet.subject_name)}<span class="subj-code">${esc(sheet.subject_code)}</span></div>${sheet.individually_locked_count > 0 ? `<div class="lock-bar"><div class="lock-bar-fill" style="width:${pct}%"></div></div>` : ''}</div></div></td>
            <td><span class="class-chip">${esc(sheet.class_name)}</span></td>
            <td>${esc(sheet.term_name || '—')}</td>
            <td>${esc(sheet.session_name || '—')}</td>
            <td><span class="status-badge ${st.cls}"><i class="${st.icon}"></i>${st.text}</span></td>
            <td><div class="row-actions"><button class="ib ib-lock" title="Lock individual" onclick="quickAction(${sheet.subjectclass_id},'lock_individual')"><i class="ri-lock-line"></i></button><button class="ib ib-unlock" title="Unlock individual" onclick="quickAction(${sheet.subjectclass_id},'unlock_individual')"><i class="ri-lock-unlock-line"></i></button><button class="ib ib-disable" title="Disable editing" onclick="quickAction(${sheet.subjectclass_id},'disable_editing')"><i class="ri-ban-line"></i></button><button class="ib ib-enable" title="Enable editing" onclick="quickAction(${sheet.subjectclass_id},'enable_editing')"><i class="ri-check-line"></i></button>${sheet.global_lock_active ? `<button class="ib ib-info" title="Remove global lock" onclick="quickAction(${sheet.subjectclass_id},'unlock_global')"><i class="ri-global-line"></i></button>` : `<button class="ib ib-info" title="Apply global lock" onclick="quickAction(${sheet.subjectclass_id},'lock_global')"><i class="ri-global-line"></i></button>`}</div></td>`;
        frag.appendChild(tr);
    });
    tbody.innerHTML = ''; tbody.appendChild(frag);
    tbody.querySelectorAll('.row-cb').forEach(cb => cb.addEventListener('change', onRowCheckbox));
    staggerRows();
}

function staggerRows() {
    if (window.matchMedia('(prefers-reduced-motion:reduce)').matches) { document.querySelectorAll('#scoresheetsTableBody tr.lm-row').forEach(r => r.classList.add('visible')); return; }
    const rows = Array.from(document.querySelectorAll('#scoresheetsTableBody tr.lm-row'));
    const io = new IntersectionObserver(entries => { entries.forEach(e => { if (!e.isIntersecting) return; const idx = rows.indexOf(e.target); setTimeout(() => e.target.classList.add('visible'), Math.min(idx * 35, 490) + 40); io.unobserve(e.target); }); }, { threshold:.05 });
    rows.forEach(r => io.observe(r));
}

function updateStats(data) {
    let open=0, indiv=0, global=0, disabled=0;
    data.forEach(s => { if (!s.teacher_editing_enabled) disabled++; else if (s.global_lock_active) global++; else if (s.individually_locked_count > 0) indiv++; else open++; });
    countUp('statTotal', data.length); countUp('statOpen', open); countUp('statIndividual', indiv); countUp('statGlobal', global); countUp('statDisabled', disabled);
    document.getElementById('heroCount').textContent = `${data.length} scoresheet${data.length!==1?'s':''} loaded`;
}

function countUp(id, target) {
    const el = document.getElementById(id); if (!el) return;
    const from = parseInt(el.textContent) || 0; if (from === target) return;
    const steps = 20, dur = 500; let i = 0;
    const iv = setInterval(() => { i++; el.textContent = i >= steps ? target : Math.round(from + (target - from) * (i/steps)); if (i >= steps) clearInterval(iv); }, dur / steps);
}

function setRecordCount(n) { const badge = document.getElementById('recordCount'), footer = document.getElementById('footerCount'); if (badge) { badge.textContent = n; badge.classList.add('pop'); setTimeout(()=>badge.classList.remove('pop'),380); } if (footer) footer.textContent = `${n} record${n!==1?'s':''}`; }

function onRowCheckbox() { const id = parseInt(this.dataset.id); const row = this.closest('tr'); if (this.checked) { selectedIds.add(id); row.style.outline='2px solid #bfdbfe'; row.style.outlineOffset='-2px'; } else { selectedIds.delete(id); row.style.outline=''; } syncSelectionUI(); }
function onSelectAll() { const checked = this.checked; document.querySelectorAll('#scoresheetsTableBody .row-cb').forEach(cb => { cb.checked = checked; cb.dispatchEvent(new Event('change')); }); }
function syncSelectionUI() { const n = selectedIds.size; document.getElementById('selectedCount').textContent = n; document.getElementById('selInfo').classList.toggle('active', n > 0); Object.values(ACTION).forEach(action => { const btn = document.getElementById(action.buttonId); if (btn) btn.disabled = n === 0; }); const all = document.querySelectorAll('#scoresheetsTableBody .row-cb'), chk = document.querySelectorAll('#scoresheetsTableBody .row-cb:checked'), sa = document.getElementById('selectAll'); sa.checked = all.length > 0 && chk.length === all.length; sa.indeterminate = chk.length > 0 && chk.length < all.length; }

async function bulkAction(action) {
    const ids = [...selectedIds]; if (!ids.length) { toast('No scoresheets selected.', 'warning'); return; }
    const m = ACTION[action]; if (!m) { toast('Invalid action.', 'error'); return; }
    let extraData = {};
    if (m.needsTermSession) {
        const termId = document.getElementById('termFilter').value, sessionId = document.getElementById('sessionFilter').value;
        if (!termId || !sessionId) { Swal.fire({ title: 'Term & Session Required', text: 'Please select a Term and Session first.', icon: 'warning', confirmButtonColor: '#2563eb' }); return; }
        extraData = { term_id: termId, session_id: sessionId };
    }
    let reason = null;
    if (m.reason) { const result = await Swal.fire({ title: m.title, text: m.msg, icon: 'question', input: 'textarea', inputPlaceholder: 'Enter reason (optional)...', showCancelButton: true, confirmButtonColor: m.color, confirmButtonText: 'Confirm' }); if (result.isDismissed) return; reason = result.value || null; }
    else { const result = await Swal.fire({ title: m.title, text: m.msg, icon: 'question', showCancelButton: true, confirmButtonColor: m.color, confirmButtonText: 'Confirm' }); if (result.isDismissed) return; }
    Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    try {
        const response = await fetch(ROUTES.bulk, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify({ action, subjectclass_ids: ids, reason, ...extraData }) });
        const result = await response.json(); Swal.close();
        if (result.success) { toast(result.message, 'success'); selectedIds.clear(); syncSelectionUI(); await fetchAndRender(false); }
        else { toast(result.message || 'Action failed.', 'error'); Swal.fire({ title: 'Action Failed', text: result.message || 'An error occurred.', icon: 'error', confirmButtonColor: '#dc2626' }); }
    } catch (error) { Swal.close(); console.error(error); toast('Network error. Please try again.', 'error'); }
}

window.quickAction = async function(id, action) {
    const m = ACTION[action]; if (!m) { toast('Invalid action.', 'error'); return; }
    let extraData = {};
    if (m.needsTermSession) {
        const termId = document.getElementById('termFilter').value, sessionId = document.getElementById('sessionFilter').value;
        if (!termId || !sessionId) { Swal.fire({ title: 'Term & Session Required', text: 'Please select a Term and Session first.', icon: 'warning', confirmButtonColor: '#2563eb' }); return; }
        extraData = { term_id: termId, session_id: sessionId };
    }
    let reason = null;
    if (m.reason) { const result = await Swal.fire({ title: m.title, text: m.msg, icon: 'question', input: 'textarea', inputPlaceholder: 'Enter reason (optional)...', showCancelButton: true, confirmButtonColor: m.color, confirmButtonText: 'Confirm' }); if (result.isDismissed) return; reason = result.value || null; }
    else { const result = await Swal.fire({ title: m.title, text: m.msg, icon: 'question', showCancelButton: true, confirmButtonColor: m.color, confirmButtonText: 'Confirm' }); if (result.isDismissed) return; }
    Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    try {
        const response = await fetch(ROUTES.bulk, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify({ action, subjectclass_ids: [id], reason, ...extraData }) });
        const result = await response.json(); Swal.close();
        if (result.success) { toast(result.message, 'success'); await fetchAndRender(false); }
        else { toast(result.message || 'Action failed.', 'error'); }
    } catch (error) { Swal.close(); console.error(error); toast('Network error. Please try again.', 'error'); }
};

function getStatus(s) {
    if (!s.teacher_editing_enabled) return { key:'disabled', cls:'sb-disabled', text:'Editing Disabled', icon:'ri-ban-line' };
    if (s.global_lock_active) return { key:'global', cls:'sb-global', text:'Globally Locked', icon:'ri-global-line' };
    const lc = parseInt(s.individually_locked_count||0), tc = parseInt(s.total_students||0);
    if (lc > 0) return { key:'indiv', cls:'sb-indiv', text: lc===tc ? 'Fully Locked' : `Partial (${lc}/${tc})`, icon:'ri-lock-line' };
    return { key:'open', cls:'sb-open', text:'Open', icon:'ri-lock-unlock-line' };
}

function initials(name) { if (!name) return '?'; const p = name.trim().split(/\s+/); return (p[0][0] + (p[1]?p[1][0]:'')).toUpperCase(); }
function esc(text) { if (text==null) return ''; const d = document.createElement('div'); d.textContent = String(text); return d.innerHTML; }
function toast(msg, type='info') { const icons = { success:'<i class="ri-checkbox-circle-fill" style="color:#10b981;"></i>', error:'<i class="ri-error-warning-fill" style="color:#ef4444;"></i>', info:'<i class="ri-information-fill" style="color:#3b82f6;"></i>', warning:'<i class="ri-alert-fill" style="color:#f59e0b;"></i>' }; const el = document.createElement('div'); el.className = `lm-toast t-${type}`; el.innerHTML = `${icons[type]||icons.info}<span class="tm">${esc(msg)}</span><button class="tc" onclick="this.closest('.lm-toast').remove()"><i class="ri-close-line"></i></button>`; document.getElementById('lmToasts').appendChild(el); setTimeout(()=>{ el.style.transition='opacity .3s'; el.style.opacity='0'; setTimeout(()=>el.remove(),320); }, 5000); }
function spinRefresh() { const ico = document.querySelector('#refreshBtn i'); if (ico) { ico.classList.add('lm-spin'); setTimeout(()=>ico.classList.remove('lm-spin'), 900); } }
</script>
@endsection
