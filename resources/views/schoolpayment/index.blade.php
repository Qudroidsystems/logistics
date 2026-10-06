@extends('layouts.master')

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════ --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap');

:root {
    --p-primary:    #1e3a5f;
    --p-accent:     #2563eb;
    --p-indigo:     #4f46e5;
    --p-success:    #16a34a;
    --p-warning:    #d97706;
    --p-danger:     #dc2626;
    --p-muted:      #6b7280;
    --p-border:     #e2e8f0;
    --p-bg:         #f8fafc;
    --p-surface:    #ffffff;
    --p-radius:     12px;
    --p-shadow:     0 2px 8px rgba(0,0,0,.07);
    --p-shadow-lg:  0 8px 32px rgba(0,0,0,.12);
}

*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Plus Jakarta Sans', sans-serif; }

@keyframes fadeInUp   { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInDown { from { opacity:0; transform:translateY(-14px);} to { opacity:1; transform:translateY(0); } }
@keyframes pulse      { 0%,100%{transform:scale(1);}50%{transform:scale(1.05);} }
@keyframes badgePop   { 0%{transform:scale(0.5);}70%{transform:scale(1.15);}100%{transform:scale(1);} }

/* ── Hero ── */
.p-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 55%, #4f46e5 100%);
    border-radius: var(--p-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: fadeInDown .5s ease both;
}
.p-hero::before {
    content:''; position:absolute; top:-70px; right:-70px;
    width:240px; height:240px;
    background:rgba(255,255,255,.06); border-radius:50%;
    animation: pulse 6s ease-in-out infinite;
}
.p-hero::after {
    content:''; position:absolute; bottom:-50px; right:140px;
    width:150px; height:150px;
    background:rgba(255,255,255,.04); border-radius:50%;
}
.p-hero h1 { font-size:22px; font-weight:800; color:#fff; margin:0 0 6px; position:relative; letter-spacing:-.3px; }
.p-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat cards ── */
.p-stat-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    padding: 18px 20px;
    transition: transform .2s, box-shadow .2s;
    animation: fadeInUp .5s ease both;
    position: relative;
    overflow: hidden;
}
.p-stat-card::before {
    content:''; position:absolute; bottom:0; left:0; right:0; height:3px;
    border-radius:0 0 var(--p-radius) var(--p-radius);
    background: linear-gradient(90deg, var(--p-accent), var(--p-indigo));
    transform: scaleX(0); transform-origin: left;
    transition: transform .3s ease;
}
.p-stat-card:hover { transform:translateY(-3px); box-shadow:var(--p-shadow-lg); }
.p-stat-card:hover::before { transform: scaleX(1); }
.p-stat-card .stat-value { font-size:28px; font-weight:800; color:var(--p-primary); line-height:1; }
.p-stat-card .stat-label { font-size:12px; color:var(--p-muted); margin-top:5px; font-weight:500; }
.p-stat-card .stat-icon  { font-size:34px; opacity:.1; float:right; margin-top:-6px; }

/* ── Filter area ── */
.p-filter-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-top: 3px solid var(--p-accent);
    border-radius: var(--p-radius);
    padding: 16px 20px;
    animation: fadeInUp .5s .1s ease both;
}
.p-input {
    border: 1.5px solid var(--p-border);
    border-radius: 9px;
    padding: 9px 14px;
    font-size: 13px;
    font-family: inherit;
    transition: border-color .2s, box-shadow .2s;
    width: 100%;
    background: #fff;
}
.p-input:focus { border-color:var(--p-accent); outline:none; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.p-input-icon-wrap { position:relative; }
.p-input-icon { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
.p-input-icon-wrap .p-input { padding-left: 34px; }
.p-form-label {
    font-size: 11.5px; font-weight: 700; color: var(--p-muted);
    text-transform: uppercase; letter-spacing: .4px;
    display: block; margin-bottom: 5px;
}

/* ── Table card ── */
.p-table-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    overflow: hidden;
    box-shadow: var(--p-shadow);
    animation: fadeInUp .5s .15s ease both;
}
.p-table-card .card-header {
    background: var(--p-surface);
    border-bottom: 1px solid var(--p-border);
    padding: 14px 20px;
}
.p-table thead th {
    background: var(--p-primary);
    color: #fff;
    padding: 12px 16px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
    border: none;
}
.p-table tbody td {
    padding: 11px 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--p-border);
    font-size: 13px;
    transition: background .12s;
}
.p-table tbody tr:hover td { background: #f0f9ff; }
.p-table tbody tr:last-child td { border-bottom: none; }

/* ── Avatar ── */
.p-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: #fff;
    border: 2px solid var(--p-border);
    flex-shrink: 0;
    transition: transform .2s;
    overflow: hidden;
}
.p-avatar img { width:100%; height:100%; object-fit:cover; }
.p-avatar:hover { transform: scale(1.1); }

/* ── Badges / pills ── */
.p-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
    animation: badgePop .3s ease;
    white-space: nowrap;
}
.p-pill.status-active   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.p-pill.status-inactive { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
.p-pill.status-default  { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.p-pill.scholarship     { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.p-pill.discount        { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.p-pill.none            { background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; }

/* ── Buttons ── */
.p-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 9px;
    font-size: 13px; font-weight: 600;
    border: none; cursor: pointer;
    transition: transform .15s, box-shadow .15s, opacity .15s;
    text-decoration: none;
}
.p-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,.15); opacity: .92; color:#fff; }
.p-btn.primary  { background:linear-gradient(135deg,var(--p-accent),var(--p-indigo)); color:#fff; }
.p-btn.ghost    { background:#fff; color:var(--p-primary); border:1.5px solid var(--p-border); }
.p-btn.ghost:hover { color:var(--p-primary); box-shadow:none; opacity:1; background:#f8fafc; }
.p-action-btn {
    width: 30px; height: 30px;
    border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    border: none; cursor: pointer;
    font-size: 13px;
    transition: transform .15s, box-shadow .15s;
    text-decoration: none;
}
.p-action-btn:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0,0,0,.15); }
.p-action-btn.pay  { background:#eff6ff; color:#2563eb; }

/* ── Empty state ── */
.p-empty { text-align:center; padding:48px 24px; color: var(--p-muted); }
.p-empty i { font-size:3rem; display:block; margin-bottom:12px; opacity:.3; }

/* ── DataTables overrides to match theme ── */
.dataTables_wrapper .dataTables_filter { display:none; } /* using our own search box */
.dataTables_wrapper .dataTables_length select {
    border:1.5px solid var(--p-border); border-radius:8px;
    padding:6px 10px; margin:0 6px; font-size:13px;
}
.dataTables_wrapper .dataTables_info  { font-size:13px; color:var(--p-muted); padding: 14px 20px; }
.dataTables_wrapper .dataTables_paginate { padding: 10px 16px 16px; }
.dataTables_wrapper .paginate_button {
    border-radius:6px !important; font-size:13px !important;
    padding:4px 10px !important;
}
.dataTables_wrapper .paginate_button.current,
.dataTables_wrapper .paginate_button.current:hover {
    background:var(--p-accent) !important;
    border-color:var(--p-accent) !important; color:#fff !important;
}
.dataTables_processing {
    background: transparent !important; border: none !important; box-shadow:none !important;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Breadcrumb --}}
    <div class="row mb-1" style="animation:fadeInDown .4s ease both;">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb m-0 small">
                    <li class="breadcrumb-item"><a href="#" style="color:var(--p-accent)">Payments</a></li>
                    <li class="breadcrumb-item active text-muted">Student Payments</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Hero --}}
    <div class="p-hero">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="ri-wallet-3-line me-2"></i>Student Payments</h1>
                <p>Select a student to view bills, record payments, and manage scholarships or discounts.</p>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('error'))
    <div class="alert alert-danger border-0 rounded-3 mb-3" style="animation:fadeInDown .4s ease both;">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    </div>
    @endif
    @if (session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-3" style="animation:fadeInDown .4s ease both;">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3" style="animation-delay:.05s">
            <div class="p-stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value">—</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="animation-delay:.08s">
            <div class="p-stat-card">
                <div class="stat-icon"><i class="ri-user-follow-line"></i></div>
                <div class="stat-value" style="color:var(--p-success)">—</div>
                <div class="stat-label">Active Students</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="animation-delay:.11s">
            <div class="p-stat-card">
                <div class="stat-icon"><i class="ri-medal-line"></i></div>
                <div class="stat-value" style="color:var(--p-warning)">—</div>
                <div class="stat-label">On Scholarship</div>
            </div>
        </div>
        <div class="col-6 col-md-3" style="animation-delay:.14s">
            <div class="p-stat-card">
                <div class="stat-icon"><i class="ri-price-tag-3-line"></i></div>
                <div class="stat-value" style="color:var(--p-accent)">—</div>
                <div class="stat-label">On Discount</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="p-filter-card mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="p-form-label">Search</label>
                <div class="p-input-icon-wrap">
                    <i class="bi bi-search p-input-icon"></i>
                    <input type="text" id="liveSearch" class="p-input" placeholder="Name, admission no…">
                </div>
            </div>
            <div class="col-md-2">
                <label class="p-form-label">Session</label>
                <select id="sessionFilter" class="p-input">
                    <option value="all">All Sessions</option>
                    @foreach ($schoolsessions as $s)
                    <option value="{{ $s->id }}" @selected($defaultSessionId == $s->id)>{{ $s->session }}{{ $s->status === 'Current' ? ' (Current)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="p-form-label">Class</label>
                <select id="classFilter" class="p-input">
                    <option value="">All Classes</option>
                    @foreach ($classOptions as $c)
                    <option value="{{ $c->id }}">{{ $c->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="p-form-label">Status</label>
                <select id="statusFilter" class="p-input">
                    <option value="">All Statuses</option>
                    @foreach ($statusOptions as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="p-btn ghost w-100" id="clearFilters">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="p-table-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="fw-bold" style="color:var(--p-primary);font-size:14px;">
                <i class="ri-list-check me-2" style="color:var(--p-accent)"></i>
                Students
                <span id="studentCountBadge" class="badge ms-2" style="background:var(--p-accent);font-size:11px;font-weight:600;">0</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table p-table mb-0" id="studentsTable">
                <thead>
                    <tr>
                        <th width="50"></th>
                        <th>Name</th>
                        <th>Admission No</th>
                        <th>Class</th>
                        <th>Term / Session</th>
                        <th>Status</th>
                        <th>Fee Adjustments</th>
                        <th width="90">Action</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody"></tbody>
            </table>
        </div>

    </div>

</div>
</div>
</div>

{{-- TERM / SESSION MODAL (fix #1: opens instead of navigating to termSession page) --}}
<div class="modal fade" id="termSessionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15);">
      <div class="modal-header" style="background:linear-gradient(135deg,#1e3a5f,#2563eb);border:none;position:relative;">
        <h5 class="modal-title text-white mb-0" id="tsModalStudentName" style="font-size:15px;">
            <i class="ri-calendar-2-line me-2"></i>Select Term & Session
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="tsModalStudentId">
        <div class="mb-3">
          <label class="p-form-label">Term</label>
          <select id="tsModalTerm" class="p-input">
            <option value="">-- Select Term --</option>
            @foreach($schoolterms as $term)
                <option value="{{ $term->id }}">{{ $term->term }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-1">
          <label class="p-form-label">Session</label>
          <select id="tsModalSession" class="p-input">
            <option value="">-- Select Session --</option>
            @foreach($schoolsessions as $session)
                <option value="{{ $session->id }}">{{ $session->session }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="p-btn ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="p-btn primary" id="tsModalGoBtn"><i class="bi bi-arrow-right-circle me-1"></i>View Payment Details</button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════════ --}}
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    const table = $('#studentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("schoolpayment.data") }}',
            data: function (d) {
                d.session_filter = $('#sessionFilter').val();
                d.class_filter  = $('#classFilter').val();
                d.status_filter = $('#statusFilter').val();
            },
            error: function (xhr) {
                console.error('DataTables error:', xhr.status, xhr.responseText);
            }
        },
        columns: [
            { data: 'avatar',                orderable: false, searchable: false },
            { data: 'full_name',             orderable: false },
            { data: 'admissionNo',           orderable: false },
            { data: 'class_display',         orderable: false, searchable: false },
            { data: 'term_session_display',  orderable: false, searchable: false },
            { data: 'status_badge',          orderable: false, searchable: false },
            { data: 'adjustments',           orderable: false, searchable: false },
            { data: 'action',                orderable: false, searchable: false },
        ],
        order: [],
        pageLength: 15,
        dom: 'rtip',
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary"></span>',
            emptyTable: '<div class="p-empty"><i class="ri-user-line"></i>No students found for the selected session</div>',
            zeroRecords: '<div class="p-empty"><i class="ri-search-line"></i>No students match your filters</div>',
        },
        drawCallback: function () {
            const info = this.api().page.info();
            $('#studentCountBadge').text(info.recordsDisplay);
        },
    });

    // Debounced live search
    let searchTimer;
    $('#liveSearch').on('input', function () {
        clearTimeout(searchTimer);
        const val = this.value;
        searchTimer = setTimeout(() => table.search(val).draw(), 350);
    });

    $('#classFilter, #statusFilter').on('change', function () {
        table.draw();
        loadStats();
    });
    $('#sessionFilter').on('change', function () {
        table.draw();
        loadStats();
    });

    $('#clearFilters').on('click', function () {
        $('#liveSearch').val('');
        $('#classFilter').val('');
        $('#statusFilter').val('');
        $('#sessionFilter').val(@json((string) ($defaultSessionId ?? 'all')));
        table.search('').draw();
        loadStats();
    });

    // Stat cards (AJAX, independent of table draws)
    function loadStats() {
        $.get('{{ route("schoolpayment.stats") }}', {
            session_filter: $('#sessionFilter').val(),
            class_filter:   $('#classFilter').val(),
        }, function (res) {
            const vals = $('.p-stat-card .stat-value');
            vals.eq(0).text(res.stats.total);
            vals.eq(1).text(res.stats.active);
            vals.eq(2).text(res.stats.scholarship);
            vals.eq(3).text(res.stats.discount);
        });
    }
    loadStats();

    // ── Term/Session modal (fix #1) ─────────────────────────────────────
    $(document).on('click', '.select-term-session-btn', function () {
        $('#tsModalStudentId').val($(this).data('student-id'));
        $('#tsModalStudentName').html('<i class="ri-calendar-2-line me-2"></i>' + $(this).data('student-name') + ' — Select Term & Session');
        $('#tsModalTerm').val('');
        const listSession = $('#sessionFilter').val();
        $('#tsModalSession').val(listSession && listSession !== 'all' ? listSession : '');
        new bootstrap.Modal(document.getElementById('termSessionModal')).show();
    });

    $('#tsModalGoBtn').on('click', function () {
        const studentId = $('#tsModalStudentId').val();
        const termid    = $('#tsModalTerm').val();
        const sessionid = $('#tsModalSession').val();

        if (!termid || !sessionid) {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Selection',
                text: 'Please select both term and session to continue.',
                confirmButtonColor: '#2563eb',
            });
            return;
        }

        window.location.href = '{{ route("schoolpayment.termsessionpayments") }}'
            + '?studentId=' + studentId
            + '&termid='    + termid
            + '&sessionid=' + sessionid;
    });
});
</script>
@endsection