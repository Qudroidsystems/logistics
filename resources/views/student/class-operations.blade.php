{{-- resources/views/student/class-operations.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --ss-primary:   #1e3a5f;
    --ss-accent:    #2563eb;
    --ss-success:   #16a34a;
    --ss-warning:   #d97706;
    --ss-danger:    #dc2626;
    --ss-info:      #4338ca;
    --ss-muted:     #6b7280;
    --ss-border:    #e2e8f0;
    --ss-card:      #ffffff;
    --ss-radius:    10px;
    --ss-shadow:    0 1px 4px rgba(0,0,0,.08);
}

.stat-card { background: var(--ss-card); border: 1px solid var(--ss-border); border-radius: var(--ss-radius); padding: 14px 18px; box-shadow: var(--ss-shadow); transition: transform .15s; }
.stat-card:hover { transform: translateY(-2px); }
.stat-card .stat-value { font-size: 22px; font-weight: 700; color: var(--ss-primary); }
.stat-card .stat-label { font-size: 11px; color: var(--ss-muted); margin-top: 2px; }
.stat-card .stat-icon  { font-size: 26px; opacity: .15; float: right; margin-top: -4px; }

.ss-card-header { background: var(--ss-primary); }
.ss-card-header h5, .ss-card-header .card-title { color: #fff; }
.ss-modal-header { background: var(--ss-primary); border: none; }
.ss-modal-header .modal-title { color: #fff; }

.section-nav { display:flex; gap:8px; margin-bottom:16px; }
.section-nav .btn { border-radius: 20px; font-weight:600; font-size:13px; }

.filter-card { background: var(--ss-card); border: 1px solid var(--ss-border); border-radius: var(--ss-radius); box-shadow: var(--ss-shadow); }

.status-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.status-pill.active    { background: #dcfce7; color: var(--ss-success); }
.status-pill.inactive  { background: #f3f4f6; color: var(--ss-muted); }
.status-pill.new       { background: #fef3c7; color: var(--ss-warning); }
.status-pill.old       { background: #e0e7ff; color: var(--ss-info); }
.status-pill.current   { background: #dcfce7; color: var(--ss-success); }

#rosterTable, #registrationCards { font-size: 12.5px; }
#rosterTable thead tr { background: var(--ss-primary); color: #fff; }
#rosterTable thead th { padding: 10px 8px; font-weight: 600; white-space: nowrap; border: none; }
#rosterTable tbody td { padding: 8px; vertical-align: middle; border-bottom: 1px solid var(--ss-border); }
#rosterTable tbody tr:hover { background: #f0f6ff; }

.avatar-title { display:flex; align-items:center; justify-content:center; width:100%; height:100%; font-weight:700; }
.bg-soft-primary { background-color: rgba(30,58,95,.1); color: var(--ss-primary); }

.reg-card { transition: transform .15s, box-shadow .15s; border:1px solid var(--ss-border); border-radius: var(--ss-radius); }
.reg-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.08); }

.empty-note { padding: 40px 20px; text-align:center; color: var(--ss-muted); }
.empty-note i { font-size: 40px; opacity:.3; display:block; margin-bottom:10px; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('student.index') }}">Student Management</a></li>
                        <li class="breadcrumb-item active">Class &amp; Term Operations</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
        <i class="ri-information-line fs-5 mt-1"></i>
        <div>
            <strong>What lives here:</strong> anything that acts on a <em>set</em> of students defined by class/term/session —
            bulk status changes, assigning a current term, and removing term registrations.
            For finding and editing a single student, use
            <a href="{{ route('student.index') }}">All Students</a> instead.
        </div>
    </div>

    {{-- ══ SECTION SWITCHER ═══════════════════════════════════════════ --}}
    <div class="section-nav">
        <button type="button" class="btn btn-primary active" id="navRoster" onclick="switchSection('roster')" style="background:var(--ss-primary);border-color:var(--ss-primary);">
            <i class="ri-group-line me-1"></i>Roster Actions
        </button>
        <button type="button" class="btn btn-outline-secondary" id="navTermReg" onclick="switchSection('termreg')">
            <i class="ri-calendar-check-line me-1"></i>Term Registration Management
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         SECTION 1 — ROSTER ACTIONS
         (status update / student type / assign current term)
         ══════════════════════════════════════════════════════════════ --}}
    <div id="sectionRoster">

        <div class="row mb-3">
            <div class="col-lg-12">
                <div class="filter-card p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1">Class</label>
                            <select class="form-control" id="rosterClass">
                                <option value="">Select Class</option>
                                @foreach ($schoolclasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1">Session</label>
                            <select class="form-control" id="rosterSession">
                                <option value="">Select Session</option>
                                @foreach ($schoolsessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary w-100" id="loadRosterBtn" style="background:var(--ss-primary);border-color:var(--ss-primary);">
                                <i class="ri-search-line me-1"></i>Load Roster
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="rosterStatsRow" class="row g-3 mb-3 d-none">
            <div class="col-6 col-md-3">
                <div class="stat-card text-center h-100">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value text-primary" id="statTotal">0</div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center h-100">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value" style="color:var(--ss-success);" id="statActive">0</div>
                    <div class="stat-label">Active</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center h-100">
                    <div class="stat-icon">⏸️</div>
                    <div class="stat-value" style="color:var(--ss-muted);" id="statInactive">0</div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center h-100">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-value" style="color:var(--ss-warning);" id="statNew">0</div>
                    <div class="stat-label">New Students</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none" id="rosterCard">
            <div class="card-header ss-card-header d-flex align-items-center flex-wrap gap-2 py-3">
                <div class="flex-grow-1">
                    <h5 class="mb-0"><i class="ri-list-check-2 me-2"></i>Roster <span class="badge bg-white text-primary ms-1" id="rosterCount">0</span></h5>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="btn-group">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-toggle-line me-1"></i>Activity Status
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="RosterManager.bulkUpdateStatus('activity_status','Active')"><i class="ri-checkbox-circle-line text-success me-2"></i>Set Active</a></li>
                            <li><a class="dropdown-item" href="#" onclick="RosterManager.bulkUpdateStatus('activity_status','Inactive')"><i class="ri-pause-circle-line text-secondary me-2"></i>Set Inactive</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-user-star-line me-1"></i>Student Type
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="RosterManager.bulkUpdateStatus('student_type','old')"><i class="ri-history-line text-secondary me-2"></i>Old Student</a></li>
                            <li><a class="dropdown-item" href="#" onclick="RosterManager.bulkUpdateStatus('student_type','new')"><i class="ri-star-line text-warning me-2"></i>New Student</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-warning" id="assignTermOpenBtn" style="color:#111827;" data-bs-toggle="modal" data-bs-target="#assignTermModal" disabled title="Select at least one student to enable this">
                        <i class="ri-calendar-2-line me-1"></i>Assign / Update Term
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0" id="rosterTable">
                        <thead>
                            <tr>
                                <th style="width:44px;"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="rosterCheckAll"></div></th>
                                <th>Student</th>
                                <th>Admission No</th>
                                <th>Class</th>
                                <th>Activity Status</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody id="rosterTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="empty-note d-none" id="rosterEmptyNote">
            <i class="ri-group-line"></i>
            Select a class and session, then click "Load Roster" to begin.
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         SECTION 2 — TERM REGISTRATION MANAGEMENT
         ══════════════════════════════════════════════════════════════ --}}
    <div id="sectionTermReg" class="d-none">

        <div class="row mb-3">
            <div class="col-lg-12">
                <div class="filter-card p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1">Term</label>
                            <select class="form-control" id="termRegTerm">
                                <option value="">Select Term</option>
                                @foreach ($schoolterms as $term)
                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1">Session</label>
                            <select class="form-control" id="termRegSession">
                                <option value="">Select Session</option>
                                @foreach ($schoolsessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1">Class <small class="text-muted">(optional)</small></label>
                            <select class="form-control" id="termRegClass">
                                <option value="">All Classes</option>
                                @foreach ($schoolclasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100" id="loadTermRegBtn" style="background:var(--ss-primary);border-color:var(--ss-primary);">
                                <i class="ri-search-line me-1"></i>Load Registrations
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none" id="termRegCard">
            <div class="card-header ss-card-header d-flex align-items-center flex-wrap gap-2 py-3">
                <div class="flex-grow-1">
                    <h5 class="mb-0"><i class="ri-calendar-check-line me-2"></i>Registered Students <span class="badge bg-white text-primary ms-1" id="termRegCount">0</span></h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="form-check text-white mb-0">
                        <input class="form-check-input" type="checkbox" id="termRegCheckAll">
                        <label class="form-check-label" for="termRegCheckAll">Select all</label>
                    </div>
                    <button class="btn btn-danger btn-sm" onclick="TermRegistrationManager.bulkRemoveFromTerm()">
                        <i class="ri-user-unfollow-line me-1"></i>Remove Selected
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="registrationCards"></div>
            </div>
        </div>

        <div class="empty-note d-none" id="termRegEmptyNote">
            <i class="ri-calendar-check-line"></i>
            Select a term and session, then click "Load Registrations" to begin.
        </div>
    </div>

    {{-- ══ ASSIGN / UPDATE TERM MODAL ══════════════════════════════════ --}}
    <div class="modal fade" id="assignTermModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header ss-modal-header">
                    <h5 class="modal-title"><i class="ri-calendar-2-line me-2"></i>Assign / Update Current Term</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        Applies to <strong id="assignTermSelectedCount">0</strong> selected student(s) from the roster above.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class</label>
                        <select class="form-control" id="assignTermClass" required>
                            <option value="">Select Class</option>
                            @foreach ($schoolclasses as $class)
                                <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Term</label>
                        <select class="form-control" id="assignTermTerm" required>
                            <option value="">Select Term</option>
                            @foreach ($schoolterms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Session</label>
                        <select class="form-control" id="assignTermSession" required>
                            <option value="">Select Session</option>
                            @foreach ($schoolsessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="assignTermIsCurrent" checked>
                        <label class="form-check-label" for="assignTermIsCurrent">Mark as current term for selected student(s)</label>
                    </div>
                    <div class="alert alert-warning small mb-0">
                        If a term already exists for a student in the chosen session, it's updated in place; otherwise a new registration is created.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" style="background:var(--ss-primary);border-color:var(--ss-primary);" onclick="RosterManager.assignTerm()">
                        <i class="ri-save-line me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>

</div></div></div>

{{-- ══════════════════════════════════════════════════════════════════
     REQUIRED LIBRARIES
     These are loaded explicitly because layouts.master does NOT provide
     them globally (index.blade.php loads them the same way). Without
     axios, the script below throws a ReferenceError on line 1 and every
     feature on this page silently fails.
     ══════════════════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ══════════════════════════════════════════════════════════════════
   CSRF / SHARED HELPERS
   ══════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    // ── Pre-flight: fail loudly instead of silently killing the page ──
    if (typeof axios === 'undefined') {
        console.error('[class-operations] axios failed to load. Aborting.');
        document.body.insertAdjacentHTML('afterbegin',
            '<div class="alert alert-danger m-3"><strong>Page script failed to load axios.</strong> Please refresh or contact support.</div>');
        return;
    }
    if (typeof Swal === 'undefined') {
        console.warn('[class-operations] SweetAlert2 not loaded — confirm dialogs will fall back to native confirm().');
    }

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!CSRF) {
        console.error('[class-operations] CSRF meta tag missing — check layouts.master.');
    }
    axios.defaults.headers.common['X-CSRF-TOKEN'] = CSRF || '';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // ── Confirmation helper: uses Swal if present, falls back to confirm() ──
    function confirmAction(title, text, confirmText) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: confirmText || 'Yes',
                cancelButtonText: 'Cancel',
            }).then(r => r.isConfirmed);
        }
        return Promise.resolve(window.confirm(title + '\n\n' + (text || '')));
    }

    // ── Toast helper ──
    window.showToast = function (msg, type = 'info') {
        const colors = { success: '#16a34a', warning: '#d97706', danger: '#dc2626', info: '#2563eb' };
        const id = 'toast_' + Date.now();
        document.body.insertAdjacentHTML('beforeend',
            `<div id="${id}" class="toast align-items-center border-0 show" role="alert"
              style="position:fixed;bottom:20px;right:20px;z-index:99999;background:${colors[type] || colors.info};min-width:280px;border-radius:10px;color:#fff;">
              <div class="d-flex p-3"><div class="me-auto">${msg}</div>
              <button class="btn-close btn-close-white ms-2" onclick="this.closest('.toast').remove()"></button></div></div>`);
        setTimeout(() => document.getElementById(id)?.remove(), 4000);
    };

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    /* ══════════════════════════════════════════════════════════════
       SECTION SWITCHER
       ══════════════════════════════════════════════════════════════ */
    window.switchSection = function (section) {
        const roster = document.getElementById('sectionRoster');
        const termreg = document.getElementById('sectionTermReg');
        const navRoster = document.getElementById('navRoster');
        const navTermReg = document.getElementById('navTermReg');

        if (section === 'roster') {
            roster.classList.remove('d-none');
            termreg.classList.add('d-none');
            navRoster.classList.add('active', 'btn-primary');
            navRoster.classList.remove('btn-outline-secondary');
            navRoster.style.background = 'var(--ss-primary)';
            navRoster.style.borderColor = 'var(--ss-primary)';
            navTermReg.classList.remove('active', 'btn-primary');
            navTermReg.classList.add('btn-outline-secondary');
            navTermReg.style.background = '';
            navTermReg.style.borderColor = '';
        } else {
            termreg.classList.remove('d-none');
            roster.classList.add('d-none');
            navTermReg.classList.add('active', 'btn-primary');
            navTermReg.classList.remove('btn-outline-secondary');
            navTermReg.style.background = 'var(--ss-primary)';
            navTermReg.style.borderColor = 'var(--ss-primary)';
            navRoster.classList.remove('active', 'btn-primary');
            navRoster.classList.add('btn-outline-secondary');
            navRoster.style.background = '';
            navRoster.style.borderColor = '';
        }
    };

    /* ══════════════════════════════════════════════════════════════
       SECTION 1 — ROSTER MANAGER
       ══════════════════════════════════════════════════════════════ */
    const RosterManager = {
        currentFilters: null,
        currentStudents: [],

        async loadRoster() {
            const classId   = document.getElementById('rosterClass').value;
            const sessionId = document.getElementById('rosterSession').value;

            if (!classId || !sessionId) {
                showToast('Please select both a class and a session.', 'warning');
                return;
            }

            this.currentFilters = { class_id: classId, session_id: sessionId };

            const btn = document.getElementById('loadRosterBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line"></i> Loading…';

            try {
                const { data } = await axios.get('/students/by-class-session', { params: this.currentFilters });

                btn.disabled = false;
                btn.innerHTML = '<i class="ri-search-line me-1"></i>Load Roster';

                if (!data.success) {
                    showToast(data.message || 'Failed to load roster.', 'danger');
                    return;
                }

                this.currentStudents = data.students || [];

                // Stats: prefer server-provided block; fall back to client-side derivation
                // so the page keeps working even if the controller doesn't return `stats`.
                const stats = data.stats || this.deriveStats(this.currentStudents);

                this.renderStats(stats);
                this.renderTable(this.currentStudents);

                document.getElementById('rosterStatsRow').classList.remove('d-none');
                document.getElementById('rosterCard').classList.remove('d-none');
                document.getElementById('rosterEmptyNote').classList.add('d-none');
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-search-line me-1"></i>Load Roster';
                console.error('[class-operations] loadRoster failed', err);
                showToast(err.response?.data?.message || 'Error loading roster.', 'danger');
            }
        },

        deriveStats(students) {
            return {
                total: students.length,
                active: students.filter(s => s.student_status === 'Active').length,
                inactive: students.filter(s => s.student_status !== 'Active').length,
                new_students: students.filter(s => s.statusId == 2).length,
            };
        },

        renderStats(stats) {
            document.getElementById('statTotal').textContent    = stats.total ?? 0;
            document.getElementById('statActive').textContent   = stats.active ?? 0;
            document.getElementById('statInactive').textContent = stats.inactive ?? 0;
            document.getElementById('statNew').textContent      = stats.new_students ?? 0;
        },

        renderTable(students) {
            const tbody = document.getElementById('rosterTableBody');
            document.getElementById('rosterCount').textContent = students.length;

            // Fresh roster = no selection yet, so the term-assign action starts disabled.
            this.updateAssignTermButtonState();

            // Reset header "select all"
            const checkAll = document.getElementById('rosterCheckAll');
            if (checkAll) checkAll.checked = false;

            if (!students.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No students found for this class/session.</td></tr>';
                return;
            }

            tbody.innerHTML = students.map(s => {
                const initials = (s.firstname?.charAt(0) || '') + (s.lastname?.charAt(0) || '');
                const activityPill = s.student_status === 'Active'
                    ? '<span class="status-pill active"><i class="ri-checkbox-circle-line"></i>Active</span>'
                    : '<span class="status-pill inactive"><i class="ri-pause-circle-line"></i>Inactive</span>';
                const typePill = s.statusId == 2
                    ? '<span class="status-pill new"><i class="ri-star-line"></i>New</span>'
                    : '<span class="status-pill old"><i class="ri-history-line"></i>Old</span>';

                return `
                    <tr data-student-id="${s.id}">
                        <td><div class="form-check mb-0"><input class="form-check-input roster-checkbox" type="checkbox" value="${s.id}"></div></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-title bg-soft-primary rounded-circle" style="width:34px;height:34px;">${initials}</div>
                                <div>
                                    <div class="fw-semibold">${escapeHtml(s.lastname)} ${escapeHtml(s.firstname)}</div>
                                    <small class="text-muted">${escapeHtml(s.othername || '')}</small>
                                </div>
                            </div>
                        </td>
                        <td>${escapeHtml(s.admissionNo || 'N/A')}</td>
                        <td>${escapeHtml(s.schoolclass || '')} ${escapeHtml(s.arm || '')}</td>
                        <td>${activityPill}</td>
                        <td>${typePill}</td>
                    </tr>
                `;
            }).join('');
        },

        getSelectedIds() {
            return Array.from(document.querySelectorAll('.roster-checkbox:checked')).map(cb => cb.value);
        },

        updateAssignTermButtonState() {
            const btn = document.getElementById('assignTermOpenBtn');
            if (!btn) return;
            const hasSelection = this.getSelectedIds().length > 0;
            btn.disabled = !hasSelection;
            btn.title = hasSelection ? '' : 'Select at least one student to enable this';
        },

        async bulkUpdateStatus(updateType, value) {
            const ids = this.getSelectedIds();
            if (!ids.length) { showToast('Select at least one student first.', 'warning'); return; }

            const label = updateType === 'student_type'
                ? (value === 'old' ? 'Old Student' : 'New Student')
                : value;

            const confirmed = await confirmAction(
                'Confirm Update',
                `Update ${ids.length} student(s) to "${label}"?`,
                'Yes, update'
            );
            if (!confirmed) return;

            try {
                const { data } = await axios.post('/students/bulk-update-status', {
                    student_ids: ids,
                    update_type: updateType,
                    value: value,
                });
                if (data.success) {
                    showToast(data.message, 'success');
                    this.loadRoster();
                } else {
                    showToast(data.message || 'Update failed.', 'danger');
                }
            } catch (err) {
                console.error('[class-operations] bulkUpdateStatus failed', err);
                showToast(err.response?.data?.message || 'Failed to update status.', 'danger');
            }
        },

        async assignTerm() {
            const ids       = this.getSelectedIds();
            const classId   = document.getElementById('assignTermClass').value;
            const termId    = document.getElementById('assignTermTerm').value;
            const sessionId = document.getElementById('assignTermSession').value;
            const isCurrent = document.getElementById('assignTermIsCurrent').checked;

            if (!ids.length) { showToast('Select at least one student in the roster first.', 'warning'); return; }
            if (!classId || !termId || !sessionId) { showToast('Please select class, term, and session.', 'warning'); return; }

            try {
                const { data } = await axios.post('/students/bulk-update-current-term', {
                    student_ids: ids,
                    schoolclassId: classId,
                    termId: termId,
                    sessionId: sessionId,
                    is_current: isCurrent,
                });

                bootstrap.Modal.getInstance(document.getElementById('assignTermModal'))?.hide();

                if (data.success) {
                    showToast(data.message, 'success');
                    this.loadRoster();
                } else {
                    showToast(data.message || 'Failed to assign term.', 'danger');
                }
            } catch (err) {
                console.error('[class-operations] assignTerm failed', err);
                showToast(err.response?.data?.message || 'Failed to assign term.', 'danger');
            }
        },
    };

    window.RosterManager = RosterManager;

    /* ══════════════════════════════════════════════════════════════
       SECTION 2 — TERM REGISTRATION MANAGER
       ══════════════════════════════════════════════════════════════ */
    const TermRegistrationManager = {
        currentFilters: null,

        async loadRegistrations() {
            const termId    = document.getElementById('termRegTerm').value;
            const sessionId = document.getElementById('termRegSession').value;
            const classId   = document.getElementById('termRegClass').value;

            if (!termId || !sessionId) {
                showToast('Please select both a term and a session.', 'warning');
                return;
            }

            this.currentFilters = {
                term_id: termId,
                session_id: sessionId,
                class_id: classId || null,
            };

            const btn = document.getElementById('loadTermRegBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line"></i> Loading…';

            try {
                const { data } = await axios.get('/students-in-term', { params: this.currentFilters });

                btn.disabled = false;
                btn.innerHTML = '<i class="ri-search-line me-1"></i>Load Registrations';

                if (!data.success) {
                    showToast(data.message || 'Failed to load registrations.', 'danger');
                    return;
                }

                this.render(data.students || []);

                document.getElementById('termRegCard').classList.remove('d-none');
                document.getElementById('termRegEmptyNote').classList.add('d-none');
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-search-line me-1"></i>Load Registrations';
                console.error('[class-operations] loadRegistrations failed', err);
                showToast(err.response?.data?.message || 'Error loading registrations.', 'danger');
            }
        },

        render(students) {
            const container = document.getElementById('registrationCards');
            document.getElementById('termRegCount').textContent = students.length;

            // Reset the "select all" checkbox for a fresh render
            const checkAll = document.getElementById('termRegCheckAll');
            if (checkAll) checkAll.checked = false;

            if (!students.length) {
                container.innerHTML = '<div class="col-12"><div class="alert alert-warning text-center mb-0">No students registered for this term.</div></div>';
                return;
            }

            container.innerHTML = students.map(s => {
                const initials = (s.firstname?.charAt(0) || '') + (s.lastname?.charAt(0) || '');
                const currentBadge = s.is_current
                    ? '<span class="status-pill current position-absolute top-0 end-0 m-2"><i class="ri-check-line"></i>Current</span>'
                    : '';

                return `
                    <div class="col-md-4 col-lg-3 mb-3">
                        <div class="reg-card card h-100" data-registration-id="${s.registration_id}">
                            <div class="card-body position-relative">
                                ${currentBadge}
                                <div class="form-check position-absolute top-0 start-0 m-2">
                                    <input class="form-check-input term-reg-checkbox" type="checkbox" value="${s.registration_id}">
                                </div>
                                <div class="text-center mb-3 mt-2">
                                    <div class="avatar-title bg-soft-primary rounded-circle mx-auto mb-2" style="width:70px;height:70px;font-size:26px;">${initials || 'ST'}</div>
                                    <h6 class="mb-1 fw-semibold">${escapeHtml(s.fullname)}</h6>
                                    <p class="text-muted small mb-2">${escapeHtml(s.admissionNo)}</p>
                                </div>
                                <div class="d-flex flex-column gap-1 mb-3 small">
                                    <div><i class="ri-school-line text-muted me-2"></i>${escapeHtml(s.class)} ${escapeHtml(s.arm)}</div>
                                    <div><i class="ri-calendar-line text-muted me-2"></i>Reg: ${s.registered_at}</div>
                                </div>
                                <button class="btn btn-outline-danger btn-sm w-100" onclick="TermRegistrationManager.removeSingle(${s.registration_id}, '${escapeHtml(s.fullname)}')">
                                    <i class="ri-user-unfollow-line me-1"></i>Remove from Term
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        },

        getSelectedIds() {
            return Array.from(document.querySelectorAll('.term-reg-checkbox:checked')).map(cb => cb.value);
        },

        async removeSingle(registrationId, name) {
            const confirmed = await confirmAction(
                'Confirm Removal',
                `Remove ${name} from this term registration?`,
                'Yes, remove'
            );
            if (!confirmed) return;

            try {
                const { data } = await axios.post('/students/remove-from-term', {
                    registration_id: registrationId,
                });
                if (data.success) {
                    showToast(data.message, 'success');
                    this.loadRegistrations();
                } else {
                    showToast(data.message || 'Removal failed.', 'danger');
                }
            } catch (err) {
                console.error('[class-operations] removeSingle failed', err);
                showToast(err.response?.data?.message || 'Failed to remove student.', 'danger');
            }
        },

        async bulkRemoveFromTerm() {
            const ids = this.getSelectedIds();
            if (!ids.length) { showToast('Select at least one student first.', 'warning'); return; }

            const confirmed = await confirmAction(
                'Confirm Bulk Removal',
                `Remove ${ids.length} student(s) from this term registration?`,
                'Yes, remove all'
            );
            if (!confirmed) return;

            try {
                const { data } = await axios.post('/students/bulk-remove-from-term', {
                    registration_ids: ids,
                });
                if (data.success) {
                    showToast(data.message, 'success');
                    this.loadRegistrations();
                } else {
                    showToast(data.message || 'Bulk removal failed.', 'danger');
                }
            } catch (err) {
                console.error('[class-operations] bulkRemoveFromTerm failed', err);
                showToast(err.response?.data?.message || 'Failed to remove students.', 'danger');
            }
        },
    };

    window.TermRegistrationManager = TermRegistrationManager;

    /* ══════════════════════════════════════════════════════════════
       EVENT BINDINGS
       ══════════════════════════════════════════════════════════════ */
    document.getElementById('loadRosterBtn')?.addEventListener('click', () => RosterManager.loadRoster());
    document.getElementById('loadTermRegBtn')?.addEventListener('click', () => TermRegistrationManager.loadRegistrations());

    document.getElementById('rosterCheckAll')?.addEventListener('change', function () {
        document.querySelectorAll('.roster-checkbox').forEach(cb => cb.checked = this.checked);
        RosterManager.updateAssignTermButtonState();
    });

    document.getElementById('termRegCheckAll')?.addEventListener('change', function () {
        document.querySelectorAll('.term-reg-checkbox').forEach(cb => cb.checked = this.checked);
    });

    // Row checkboxes are rendered dynamically, so listen at the document level
    // rather than rebinding one-by-one after every render.
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('roster-checkbox')) {
            RosterManager.updateAssignTermButtonState();
        }
    });

    document.getElementById('assignTermModal')?.addEventListener('show.bs.modal', function () {
        document.getElementById('assignTermSelectedCount').textContent = RosterManager.getSelectedIds().length;
    });

    console.log('[class-operations] Initialized successfully.');
})();
</script>
@endsection