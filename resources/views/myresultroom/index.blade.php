{{-- resources/views/myresultroom/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --s-primary:  #1e3a5f;
    --s-accent:   #2563eb;
    --s-success:  #16a34a;
    --s-warning:  #d97706;
    --s-danger:   #dc2626;
    --s-info:     #0891b2;
    --s-muted:    #6b7280;
    --s-border:   #e2e8f0;
    --s-bg:       #f8fafc;
    --s-radius:   12px;
    --s-shadow:   0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ─────────────────────────────────────────────── */
.s-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #7c3aed 100%);
    border-radius: var(--s-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.s-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.s-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.s-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.s-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ──────────────────────────────────────── */
.stat-card {
    background:#fff; border:1px solid var(--s-border);
    border-radius:var(--s-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--s-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--s-primary); }
.stat-card .stat-label { font-size:12px; color:var(--s-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Filter card ─────────────────────────────────────── */
.filter-card {
    background:#fff; border-radius:var(--s-radius);
    border:1px solid var(--s-border); padding:20px 24px;
    margin-bottom:24px;
}
.filter-label {
    font-size:13px; font-weight:600; color:#374151;
    margin-bottom:6px; display:block;
}
.filter-select {
    border:1.5px solid var(--s-border); border-radius:8px;
    padding:9px 14px; font-size:13px; width:100%;
    transition:border .15s; background:#fff;
}
.filter-select:focus {
    border-color:var(--s-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── View toggle ─────────────────────────────────────── */
.view-toggle { display:flex; gap:8px; }
.view-toggle-btn {
    padding:6px 14px; border-radius:8px; font-size:12px;
    font-weight:600; cursor:pointer;
    border:1.5px solid var(--s-border); background:#fff;
    transition:all .15s;
}
.view-toggle-btn.active {
    background:var(--s-accent); border-color:var(--s-accent); color:#fff;
}
.search-input {
    border:1.5px solid var(--s-border); border-radius:8px;
    padding:8px 14px; font-size:13px; min-width:220px;
    transition:border .15s;
}
.search-input:focus {
    border-color:var(--s-accent); outline:none;
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Subject grid cards ──────────────────────────────── */
.subjects-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(360px, 1fr));
    gap:20px;
}
.subject-card {
    background:#fff; border:1px solid var(--s-border);
    border-radius:var(--s-radius); overflow:hidden;
    transition:transform .18s, box-shadow .18s;
}
.subject-card:hover {
    transform:translateY(-3px);
    box-shadow:0 12px 28px rgba(0,0,0,.1);
}
.subject-card-header {
    background:linear-gradient(135deg, #f1f5f9 0%, #e9eef3 100%);
    padding:14px 20px;
    border-bottom:1px solid var(--s-border);
    display:flex; justify-content:space-between; align-items:center;
}
.subject-card-header .subject-name {
    font-weight:700; color:var(--s-primary);
    font-size:15px; margin:0;
}
.subject-card-header .subject-code {
    background:#fff; padding:3px 10px;
    border-radius:20px; font-size:11px;
    font-weight:600; color:var(--s-accent);
    border:1px solid #cbd5e1; white-space:nowrap; flex-shrink:0;
}
.subject-card-body { padding:16px 20px; }
.subject-info-row {
    display:flex; align-items:flex-start;
    margin-bottom:10px; font-size:13px; gap:8px;
}
.subject-info-row i { color:var(--s-muted); font-size:15px; margin-top:1px; flex-shrink:0; }

.subject-badge {
    display:inline-flex; align-items:center;
    padding:3px 10px; border-radius:20px;
    font-size:11px; font-weight:600;
    margin-right:6px; margin-bottom:6px;
}
.badge-terminal { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.badge-mock     { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.badge-pending  { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

.subject-card-footer {
    background:#fafcff;
    padding:14px 20px;
    border-top:1px solid var(--s-border);
    display:flex; gap:10px;
}
.btn-action {
    flex:1; padding:8px 6px; border-radius:8px;
    font-size:12px; font-weight:600;
    text-align:center; transition:all .15s;
    text-decoration:none; display:inline-block;
    cursor:pointer; border:none;
}
.btn-terminal { background:var(--s-success); color:#fff; }
.btn-terminal:hover { background:#15803d; color:#fff; text-decoration:none; }
.btn-mock { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.btn-mock:hover { background:#fde68a; color:#b45309; text-decoration:none; }

/* ── Table view ──────────────────────────────────────── */
.s-table th {
    background:var(--s-primary); color:#fff;
    padding:12px 16px; font-weight:600; font-size:13px;
    white-space:nowrap;
}
.s-table td {
    padding:11px 16px; vertical-align:middle;
    border-bottom:1px solid var(--s-border); font-size:13px;
}
.s-table tr:hover td { background:#eff6ff; }

/* ── Empty state ─────────────────────────────────────── */
.empty-state {
    text-align:center; padding:60px 20px;
    background:#fff; border-radius:var(--s-radius);
    border:1px solid var(--s-border);
}
.empty-state i { font-size:64px; color:#cbd5e1; margin-bottom:16px; display:block; }
.empty-state h5 { font-size:18px; font-weight:600; color:#64748b; margin-bottom:8px; }
.empty-state p  { font-size:13px; color:var(--s-muted); }

/* ── Full-page loader ────────────────────────────────── */
#s-page-loader {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(3px);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    opacity:0; visibility:hidden;
    transition:opacity .22s, visibility .22s;
}
#s-page-loader.active { opacity:1; visibility:visible; }
.s-loader-card {
    background:#fff; border-radius:16px;
    padding:32px 40px; text-align:center;
    box-shadow:0 24px 64px rgba(0,0,0,.22);
    min-width:220px;
}
.s-loader-spinner {
    width:52px; height:52px; margin:0 auto 16px;
    border:4px solid #e2e8f0;
    border-top-color:var(--s-accent);
    border-radius:50%;
    animation:s-spin .75s linear infinite;
}
@keyframes s-spin { to { transform:rotate(360deg); } }
.s-loader-label {
    font-size:14px; font-weight:600;
    color:var(--s-primary); margin-bottom:12px;
}
.s-progress-wrap {
    width:160px; height:5px;
    background:#e2e8f0; border-radius:99px; overflow:hidden;
    margin:0 auto;
}
.s-progress-bar {
    height:100%; width:0%;
    background:linear-gradient(90deg, var(--s-accent), #7c3aed);
    border-radius:99px; transition:width .35s ease;
}

/* ── Inline loader (filter button) ──────────────────── */
.btn-loading { position:relative; pointer-events:none; opacity:.85; }
.btn-loading .btn-text { visibility:hidden; }
.btn-loading::after {
    content:''; position:absolute; inset:0;
    margin:auto; width:16px; height:16px;
    border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff; border-radius:50%;
    animation:s-spin .65s linear infinite;
}

/* ── Toast stack ─────────────────────────────────────── */
#s-toast-stack {
    position:fixed; bottom:24px; right:24px;
    z-index:10000; display:flex;
    flex-direction:column-reverse; gap:10px;
    pointer-events:none;
}
.s-toast {
    pointer-events:all; background:#fff; border-radius:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    padding:14px 18px; min-width:280px; max-width:360px;
    display:flex; align-items:flex-start; gap:12px;
    border-left:4px solid var(--s-accent);
    transform:translateX(120%);
    transition:transform .3s cubic-bezier(.34,1.56,.64,1);
}
.s-toast.show { transform:translateX(0); }
.s-toast.s-toast-success { border-left-color:var(--s-success); }
.s-toast.s-toast-error   { border-left-color:var(--s-danger);  }
.s-toast.s-toast-warning { border-left-color:var(--s-warning); }
.s-toast.s-toast-info    { border-left-color:var(--s-info);    }
.s-toast .s-toast-icon   { font-size:20px; line-height:1; flex-shrink:0; margin-top:1px; }
.s-toast-success .s-toast-icon { color:var(--s-success); }
.s-toast-error   .s-toast-icon { color:var(--s-danger);  }
.s-toast-warning .s-toast-icon { color:var(--s-warning); }
.s-toast-info    .s-toast-icon { color:var(--s-info);    }
.s-toast .s-toast-body  { flex:1; }
.s-toast .s-toast-title { font-size:13px; font-weight:700; color:#111827; margin-bottom:2px; }
.s-toast .s-toast-msg   { font-size:12px; color:var(--s-muted); line-height:1.4; }
.s-toast .s-toast-close {
    background:none; border:none; cursor:pointer;
    color:var(--s-muted); font-size:16px; line-height:1;
    padding:0; flex-shrink:0;
}
</style>

{{-- ═══ Full-page loader ═══ --}}
<div id="s-page-loader">
    <div class="s-loader-card">
        <div class="s-loader-spinner"></div>
        <div class="s-loader-label" id="s-loader-label">Loading…</div>
        <div class="s-progress-wrap">
            <div class="s-progress-bar" id="s-progress-bar"></div>
        </div>
    </div>
</div>

{{-- ═══ Toast stack ═══ --}}
<div id="s-toast-stack"></div>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="s-hero">
        <h1><i class="ri-dashboard-line me-2"></i>My Result Room</h1>
        <p>View and manage your assigned subjects — enter terminal results and mock examination scores.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-book-open-line"></i></div>
                <div class="stat-value" id="statTotal">{{ isset($mysubjects) ? $mysubjects->count() : 0 }}</div>
                <div class="stat-label">My Subjects</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-checkbox-circle-line"></i></div>
                <div class="stat-value text-success" id="statTerminal">
                    {{ isset($mysubjects) ? $mysubjects->where('broadsheet_exists', true)->count() : 0 }}
                </div>
                <div class="stat-label">Terminal Records</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-flask-line"></i></div>
                <div class="stat-value text-warning" id="statMock">
                    {{ isset($mysubjects) ? $mysubjects->where('broadsheet_mock_exists', true)->count() : 0 }}
                </div>
                <div class="stat-label">Mock Records</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-time-line"></i></div>
                <div class="stat-value text-danger" id="statPending">
                    {{ isset($mysubjects) ? $mysubjects->where('broadsheet_exists', false)->count() : 0 }}
                </div>
                <div class="stat-label">Pending Entry</div>
            </div>
        </div>
    </div>

    {{-- Filter section --}}
    <div class="filter-card">
        <form id="filterForm" autocomplete="off">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="filter-label"><i class="ri-calendar-line me-1"></i>Academic Session</label>
                    <select name="sessionid" id="sessionid" class="filter-select" required>
                        <option value="">— Select Session —</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="filter-label"><i class="ri-survey-line me-1"></i>Term</label>
                    <select name="termid" id="termid" class="filter-select" required>
                        <option value="">— Select Term —</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" id="filterBtn">
                        <span class="btn-text"><i class="ri-filter-3-line me-1"></i>Load Subjects</span>
                    </button>
                </div>
            </div>
            <div class="alert alert-danger d-none mt-3 mb-0" id="filter-error-msg"></div>
        </form>
    </div>

    {{-- Session alerts (flash) --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <strong>Whoops!</strong> There were some problems.
            <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- View controls --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="view-toggle">
            <button type="button" class="view-toggle-btn active" id="gridViewBtn">
                <i class="ri-grid-line me-1"></i>Grid View
            </button>
            <button type="button" class="view-toggle-btn" id="tableViewBtn">
                <i class="ri-table-line me-1"></i>Table View
            </button>
        </div>
        <div>
            <input type="text" id="searchInput" class="search-input" placeholder="Search subjects…">
        </div>
    </div>

    {{-- ── Grid View ── --}}
    <div id="gridView">
        <div id="subjectsGrid">
            @if(isset($mysubjects) && $mysubjects->count() > 0)
                <div class="subjects-grid">
                    @foreach($mysubjects as $subject)
                        @php
                            $terminalUrl = route('subjectscoresheet.index', [
                                $subject->schoolclassid,
                                $subject->subjectclassid,
                                $subject->userid,
                                $subject->termid,
                                $subject->session_id
                            ]);
                            $mockUrl = route('subjectscoresheet-mock.show', [
                                $subject->schoolclassid,
                                $subject->subjectclassid,
                                $subject->userid,
                                $subject->termid,
                                $subject->session_id
                            ]);
                        @endphp
                        <div class="subject-card" data-search="{{ strtolower($subject->subject . ' ' . $subject->subjectcode . ' ' . $subject->schoolclass . ' ' . $subject->term) }}">
                            <div class="subject-card-header">
                                <h4 class="subject-name">{{ $subject->subject }}</h4>
                                <span class="subject-code">{{ $subject->subjectcode }}</span>
                            </div>
                            <div class="subject-card-body">
                                <div class="subject-info-row">
                                    <i class="ri-group-line"></i>
                                    <span><strong>Class:</strong> {{ $subject->schoolclass }}</span>
                                </div>
                                @if(!empty($subject->classcategories) && $subject->classcategories !== 'N/A')
                                <div class="subject-info-row">
                                    <i class="ri-folder-line"></i>
                                    <span><strong>Category:</strong> {{ $subject->classcategories }}</span>
                                </div>
                                @endif
                                <div class="subject-info-row">
                                    <i class="ri-calendar-event-line"></i>
                                    <span><strong>Term:</strong> {{ $subject->term }} &nbsp;|&nbsp; <strong>Session:</strong> {{ $subject->session }}</span>
                                </div>
                                <div class="mt-2">
                                    @if($subject->broadsheet_exists)
                                        <span class="subject-badge badge-terminal"><i class="ri-check-line me-1"></i>Terminal ✓</span>
                                    @else
                                        <span class="subject-badge badge-pending"><i class="ri-time-line me-1"></i>Pending Entry</span>
                                    @endif
                                    @if($subject->broadsheet_mock_exists)
                                        <span class="subject-badge badge-mock"><i class="ri-flask-line me-1"></i>Mock ✓</span>
                                    @endif
                                </div>
                            </div>
                            <div class="subject-card-footer">
                                <a href="{{ $terminalUrl }}" class="btn-action btn-terminal">
                                    <i class="ri-file-list-line me-1"></i>{{ $subject->broadsheet_exists ? 'View Terminal' : 'Enter Terminal' }}
                                </a>
                                <a href="{{ $mockUrl }}" class="btn-action btn-mock">
                                    <i class="ri-flask-line me-1"></i>{{ $subject->broadsheet_mock_exists ? 'View Mock' : 'Enter Mock' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state" id="emptyGridState">
                    <i class="ri-book-open-line"></i>
                    <h5>No subjects assigned yet</h5>
                    <p>Select a session and term above, then click <strong>Load Subjects</strong>.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Table View ── --}}
    <div id="tableView" style="display:none;">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table s-table w-100 mb-0" id="subjectTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Code</th>
                                <th>Term</th>
                                <th>Session</th>
                                <th>Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="subjectTableBody">
                            @if(isset($mysubjects) && $mysubjects->count() > 0)
                                @foreach($mysubjects as $index => $subject)
                                    @php
                                        $terminalUrl = route('subjectscoresheet.index', [
                                            $subject->schoolclassid,
                                            $subject->subjectclassid,
                                            $subject->userid,
                                            $subject->termid,
                                            $subject->session_id
                                        ]);
                                        $mockUrl = route('subjectscoresheet-mock.show', [
                                            $subject->schoolclassid,
                                            $subject->subjectclassid,
                                            $subject->userid,
                                            $subject->termid,
                                            $subject->session_id
                                        ]);
                                    @endphp
                                    <tr data-search="{{ strtolower($subject->subject . ' ' . $subject->subjectcode . ' ' . $subject->schoolclass . ' ' . $subject->term) }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $subject->schoolclass }}</td>
                                        <td><span class="fw-semibold text-dark">{{ $subject->subject }}</span></td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-3 py-2" style="border-radius:6px;font-size:12px;">
                                                {{ $subject->subjectcode }}
                                            </span>
                                        </td>
                                        <td>{{ $subject->term }}</td>
                                        <td>{{ $subject->session }}</td>
                                        <td>
                                            @if($subject->broadsheet_exists)
                                                <span class="badge bg-success">Terminal ✓</span>
                                            @else
                                                <span class="badge bg-danger">Pending</span>
                                            @endif
                                            @if($subject->broadsheet_mock_exists)
                                                <span class="badge bg-warning text-dark ms-1">Mock ✓</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ $terminalUrl }}" class="btn btn-sm {{ $subject->broadsheet_exists ? 'btn-success' : 'btn-primary' }}" title="{{ $subject->broadsheet_exists ? 'View Terminal' : 'Enter Terminal' }}">
                                                    <i class="ri-file-list-line"></i>
                                                </a>
                                                <a href="{{ $mockUrl }}" class="btn btn-sm {{ $subject->broadsheet_mock_exists ? 'btn-warning' : 'btn-outline-warning' }}" title="{{ $subject->broadsheet_mock_exists ? 'View Mock' : 'Enter Mock' }}">
                                                    <i class="ri-flask-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="emptyTableRow">
                                    <td colspan="8" class="text-center text-muted py-4">No subjects found. Use the filter above to load your subjects.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
$(document).ready(function () {

    const CSRF  = $('meta[name="csrf-token"]').attr('content');
    let currentView     = 'grid';
    let allSubjects     = @json(isset($mysubjects) ? $mysubjects->values() : []);
    let filteredSubjects = [...allSubjects];

    // ── Page loader ─────────────────────────────────────────────
    const PageLoader = {
        _prog: 0, _timer: null,
        show(label = 'Processing…') {
            $('#s-loader-label').text(label);
            $('#s-progress-bar').css('width', '0%');
            $('#s-page-loader').addClass('active');
            this._prog = 0; this._tick();
        },
        _tick() {
            PageLoader._timer = setInterval(() => {
                if (PageLoader._prog < 85) {
                    PageLoader._prog += Math.random() * 8;
                    $('#s-progress-bar').css('width', Math.min(PageLoader._prog, 85) + '%');
                }
            }, 220);
        },
        hide() {
            clearInterval(this._timer);
            $('#s-progress-bar').css('width', '100%');
            setTimeout(() => $('#s-page-loader').removeClass('active'), 350);
        },
    };

    // ── Toast ────────────────────────────────────────────────────
    function toast(type, title, msg, duration = 4500) {
        const icons = {
            success: 'ri-checkbox-circle-fill',
            error:   'ri-close-circle-fill',
            warning: 'ri-alert-fill',
            info:    'ri-information-fill',
        };
        const id  = 'toast-' + Date.now();
        const $el = $(`
            <div class="s-toast s-toast-${type}" id="${id}">
                <span class="s-toast-icon"><i class="${icons[type] || icons.info}"></i></span>
                <div class="s-toast-body">
                    <div class="s-toast-title">${title}</div>
                    ${msg ? `<div class="s-toast-msg">${msg}</div>` : ''}
                </div>
                <button class="s-toast-close" onclick="$('#${id}').remove()">×</button>
            </div>`);
        $('#s-toast-stack').append($el);
        setTimeout(() => $el.addClass('show'), 20);
        if (duration > 0) {
            setTimeout(() => {
                $el.removeClass('show');
                setTimeout(() => $el.remove(), 350);
            }, duration);
        }
    }

    // ── Button helpers ───────────────────────────────────────────
    function btnLoad(selector, text = '') {
        const $b = $(selector);
        $b.data('original-html', $b.html()).prop('disabled', true).addClass('btn-loading');
        if (text) $b.html(`<span class="btn-text">${text}</span>`);
        return $b;
    }
    function btnReset(selector) {
        const $b = $(selector);
        const orig = $b.data('original-html');
        if (orig) $b.html(orig);
        $b.prop('disabled', false).removeClass('btn-loading');
    }

    // ── Build Terminal / Mock URL ────────────────────────────────
    // We replicate the Laravel route pattern on the JS side so we
    // can render cards entirely in JS without a page reload.
    const baseUrl = '{{ url("/") }}';
    function terminalUrl(s) {
        return `${baseUrl}/subjectscoresheet/${s.schoolclassid}/${s.subjectclassid}/${s.userid}/${s.termid}/${s.session_id}`;
    }
    function mockUrl(s) {
        return `${baseUrl}/subjectscoresheet-mock/${s.schoolclassid}/${s.subjectclassid}/${s.userid}/${s.termid}/${s.session_id}`;
    }

    // ── Build one grid card HTML ─────────────────────────────────
    function buildCard(s) {
        const tUrl = terminalUrl(s);
        const mUrl = mockUrl(s);
        const searchKey = (s.subject + ' ' + s.subjectcode + ' ' + s.schoolclass + ' ' + s.term).toLowerCase();

        const terminalBadge = s.broadsheet_exists
            ? `<span class="subject-badge badge-terminal"><i class="ri-check-line me-1"></i>Terminal ✓</span>`
            : `<span class="subject-badge badge-pending"><i class="ri-time-line me-1"></i>Pending Entry</span>`;

        const mockBadge = s.broadsheet_mock_exists
            ? `<span class="subject-badge badge-mock"><i class="ri-flask-line me-1"></i>Mock ✓</span>`
            : '';

        const categoryRow = (s.classcategories && s.classcategories !== 'N/A')
            ? `<div class="subject-info-row">
                    <i class="ri-folder-line"></i>
                    <span><strong>Category:</strong> ${escHtml(s.classcategories)}</span>
               </div>`
            : '';

        return `
        <div class="subject-card" data-search="${escAttr(searchKey)}">
            <div class="subject-card-header">
                <h4 class="subject-name">${escHtml(s.subject)}</h4>
                <span class="subject-code">${escHtml(s.subjectcode)}</span>
            </div>
            <div class="subject-card-body">
                <div class="subject-info-row">
                    <i class="ri-group-line"></i>
                    <span><strong>Class:</strong> ${escHtml(s.schoolclass)}</span>
                </div>
                ${categoryRow}
                <div class="subject-info-row">
                    <i class="ri-calendar-event-line"></i>
                    <span><strong>Term:</strong> ${escHtml(s.term)} &nbsp;|&nbsp; <strong>Session:</strong> ${escHtml(s.session)}</span>
                </div>
                <div class="mt-2">
                    ${terminalBadge}
                    ${mockBadge}
                </div>
            </div>
            <div class="subject-card-footer">
                <a href="${tUrl}" class="btn-action btn-terminal">
                    <i class="ri-file-list-line me-1"></i>${s.broadsheet_exists ? 'View Terminal' : 'Enter Terminal'}
                </a>
                <a href="${mUrl}" class="btn-action btn-mock">
                    <i class="ri-flask-line me-1"></i>${s.broadsheet_mock_exists ? 'View Mock' : 'Enter Mock'}
                </a>
            </div>
        </div>`;
    }

    // ── Build one table row HTML ─────────────────────────────────
    function buildRow(s, index) {
        const tUrl = terminalUrl(s);
        const mUrl = mockUrl(s);
        const searchKey = (s.subject + ' ' + s.subjectcode + ' ' + s.schoolclass + ' ' + s.term).toLowerCase();

        const terminalBadge = s.broadsheet_exists
            ? `<span class="badge bg-success">Terminal ✓</span>`
            : `<span class="badge bg-danger">Pending</span>`;

        const mockBadge = s.broadsheet_mock_exists
            ? `<span class="badge bg-warning text-dark ms-1">Mock ✓</span>`
            : '';

        const tBtnClass = s.broadsheet_exists ? 'btn-success' : 'btn-primary';
        const mBtnClass = s.broadsheet_mock_exists ? 'btn-warning' : 'btn-outline-warning';

        return `
        <tr data-search="${escAttr(searchKey)}">
            <td>${index + 1}</td>
            <td>${escHtml(s.schoolclass)}</td>
            <td><span class="fw-semibold text-dark">${escHtml(s.subject)}</span></td>
            <td><span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-3 py-2" style="border-radius:6px;font-size:12px;">${escHtml(s.subjectcode)}</span></td>
            <td>${escHtml(s.term)}</td>
            <td>${escHtml(s.session)}</td>
            <td>${terminalBadge}${mockBadge}</td>
            <td>
                <div class="d-flex gap-1">
                    <a href="${tUrl}" class="btn btn-sm ${tBtnClass}" title="${s.broadsheet_exists ? 'View Terminal' : 'Enter Terminal'}"><i class="ri-file-list-line"></i></a>
                    <a href="${mUrl}" class="btn btn-sm ${mBtnClass}" title="${s.broadsheet_mock_exists ? 'View Mock' : 'Enter Mock'}"><i class="ri-flask-line"></i></a>
                </div>
            </td>
        </tr>`;
    }

    // ── Escape helpers ───────────────────────────────────────────
    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escAttr(str) { return escHtml(str); }

    // ── Render subjects into DOM ─────────────────────────────────
    function renderSubjects(subjects) {
        // Grid
        if (subjects.length === 0) {
            $('#subjectsGrid').html(`
                <div class="empty-state">
                    <i class="ri-inbox-line"></i>
                    <h5>No subjects found</h5>
                    <p>No subjects were assigned to you for the selected session and term.</p>
                </div>`);
        } else {
            const cards = subjects.map(s => buildCard(s)).join('');
            $('#subjectsGrid').html(`<div class="subjects-grid">${cards}</div>`);
        }

        // Table
        if (subjects.length === 0) {
            $('#subjectTableBody').html(`<tr><td colspan="8" class="text-center text-muted py-4">No subjects found for the selected session and term.</td></tr>`);
        } else {
            const rows = subjects.map((s, i) => buildRow(s, i)).join('');
            $('#subjectTableBody').html(rows);
        }
    }

    // ── Update stat counters ─────────────────────────────────────
    function updateStats(subjects) {
        const total    = subjects.length;
        const terminal = subjects.filter(s => s.broadsheet_exists).length;
        const mock     = subjects.filter(s => s.broadsheet_mock_exists).length;
        const pending  = subjects.filter(s => !s.broadsheet_exists).length;

        // Animate counter change
        function countTo(el, val) {
            const $el = $(el);
            const from = parseInt($el.text()) || 0;
            $({ n: from }).animate({ n: val }, {
                duration: 500,
                step: function () { $el.text(Math.round(this.n)); },
                complete: function () { $el.text(val); },
            });
        }
        countTo('#statTotal',    total);
        countTo('#statTerminal', terminal);
        countTo('#statMock',     mock);
        countTo('#statPending',  pending);
    }

    // ── Filter/search subjects ───────────────────────────────────
    function applySearch(term) {
        const q = (term || '').toLowerCase().trim();
        if (!q) {
            filteredSubjects = [...allSubjects];
        } else {
            filteredSubjects = allSubjects.filter(s => {
                const haystack = (s.subject + ' ' + s.subjectcode + ' ' + s.schoolclass + ' ' + s.term + ' ' + s.session).toLowerCase();
                return haystack.includes(q);
            });
        }
        renderSubjects(filteredSubjects);
    }

    // ── AJAX filter submit ───────────────────────────────────────
    $('#filterForm').on('submit', function (e) {
        e.preventDefault();

        const sessionid = $('#sessionid').val();
        const termid    = $('#termid').val();

        if (!sessionid || !termid) {
            $('#filter-error-msg')
                .removeClass('d-none')
                .html('<i class="ri-error-warning-line me-1"></i>Please select both a session and a term.');
            return;
        }

        $('#filter-error-msg').addClass('d-none').html('');
        btnLoad('#filterBtn', 'Loading…');
        PageLoader.show('Loading subjects…');

        $.ajax({
            url:     '{{ route("myresultroom.index") }}',
            type:    'POST',
            data:    $(this).serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                PageLoader.hide();
                btnReset('#filterBtn');

                if (res.success && res.data) {
                    allSubjects      = res.data.mysubjects || [];
                    filteredSubjects = [...allSubjects];

                    // Clear search box
                    $('#searchInput').val('');

                    renderSubjects(allSubjects);
                    updateStats(allSubjects);

                    if (allSubjects.length === 0) {
                        toast('warning', 'No Subjects', 'No subjects are assigned to you for the selected session and term.');
                    } else {
                        toast('success', 'Subjects Loaded', `${allSubjects.length} subject(s) loaded successfully.`);
                    }
                } else {
                    toast('error', 'Failed', res.message || 'Could not load subjects.');
                    $('#filter-error-msg')
                        .removeClass('d-none')
                        .html('<i class="ri-error-warning-line me-1"></i>' + (res.message || 'Could not load subjects.'));
                }
            },
            error: function (xhr) {
                PageLoader.hide();
                btnReset('#filterBtn');

                const errData = xhr.responseJSON || {};
                let msg = errData.message || '';
                if (errData.errors) {
                    msg = Object.values(errData.errors).flat().join(', ');
                }
                if (!msg) msg = 'An error occurred while loading subjects.';

                toast('error', 'Error', msg);
                $('#filter-error-msg')
                    .removeClass('d-none')
                    .html('<i class="ri-error-warning-line me-1"></i>' + msg);
            },
        });
    });

    // ── Search input ─────────────────────────────────────────────
    $('#searchInput').on('input', function () {
        applySearch($(this).val());
    });

    // ── View toggle ──────────────────────────────────────────────
    $('#gridViewBtn').on('click', function () {
        currentView = 'grid';
        $(this).addClass('active');
        $('#tableViewBtn').removeClass('active');
        $('#gridView').show();
        $('#tableView').hide();
    });

    $('#tableViewBtn').on('click', function () {
        currentView = 'table';
        $(this).addClass('active');
        $('#gridViewBtn').removeClass('active');
        $('#gridView').hide();
        $('#tableView').show();
    });

    // ── Initial stats (server-rendered data) ────────────────────
    updateStats(allSubjects);
});
</script>
@endsection
