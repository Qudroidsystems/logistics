{{-- resources/views/student/idcard/index.blade.php --}}

@extends('layouts.master')

@section('content')
<style>
:root {
    --id-primary: #1e3a5f;
    --id-accent:  #2563eb;
    --id-muted:   #6b7280;
    --id-border:  #e2e8f0;
    --id-radius:  12px;
    --id-shadow:  0 2px 8px rgba(0,0,0,.08);
}

/* ── Hero ── */
.id-hero {
    background: linear-gradient(135deg, var(--id-primary) 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--id-radius);
    padding: 28px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.id-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:220px; height:220px; background:rgba(255,255,255,.06); border-radius:50%;
}
.id-hero::after {
    content:''; position:absolute; bottom:-80px; left:-30px;
    width:260px; height:260px; background:rgba(255,255,255,.03); border-radius:50%;
}
.id-hero h1 { font-size:22px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.id-hero p  { font-size:13px; color:rgba(255,255,255,.75); margin:0; position:relative; }

/* ── Stat Cards ── */
.stat-card {
    background:#fff; border:1px solid var(--id-border);
    border-radius:var(--id-radius); padding:18px 20px;
    transition:transform .15s, box-shadow .15s;
}
.stat-card:hover { transform:translateY(-2px); box-shadow:var(--id-shadow); }
.stat-card .stat-value { font-size:28px; font-weight:700; color:var(--id-primary); }
.stat-card .stat-label { font-size:12px; color:var(--id-muted); margin-top:4px; }
.stat-card .stat-icon  { font-size:32px; opacity:.12; float:right; margin-top:-8px; }

/* ── Panel ── */
.id-panel {
    background:#fff; border:1px solid var(--id-border);
    border-radius:var(--id-radius); overflow:hidden;
    box-shadow:var(--id-shadow); margin-bottom:24px;
    position: relative;
}
.id-panel-header {
    padding:16px 20px; border-bottom:1px solid var(--id-border);
    display:flex; align-items:center; justify-content:space-between;
    background:#fff;
}
.id-panel-title {
    font-size:15px; font-weight:600; color:var(--id-primary);
    display:flex; align-items:center; gap:8px;
}

/* ── Filters ── */
.id-filter {
    padding:16px 20px; border-bottom:1px solid var(--id-border);
    background:#fafbfc;
}
.id-filter .form-control,
.id-filter .form-select {
    border:1.5px solid var(--id-border); border-radius:8px;
    font-size:13px; padding:8px 14px; transition:border .15s;
}
.id-filter .form-control:focus,
.id-filter .form-select:focus {
    border-color:var(--id-accent);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}

/* ── Student Cards ── */
.stu-card {
    background:#fff; border:2px solid var(--id-border);
    border-radius:10px; position:relative; cursor:pointer;
    transition:border-color .15s, box-shadow .15s, transform .15s;
    overflow:hidden; height:100%;
}
.stu-card:hover { border-color:var(--id-accent); box-shadow:0 4px 16px rgba(37,99,235,.12); transform:translateY(-2px); }
.stu-card.selected { border-color:var(--id-accent); background:#eff6ff; box-shadow:0 4px 16px rgba(37,99,235,.15); }
.stu-card .check-wrap { position:absolute; top:10px; left:10px; z-index:5; }
.stu-card .check-wrap input[type=checkbox] { width:17px; height:17px; cursor:pointer; accent-color:var(--id-accent); }
.stu-card .stu-photo {
    width:80px; height:80px; border-radius:50%; object-fit:cover;
    border:3px solid var(--id-accent); display:block; margin:0 auto;
}
.stu-card .stu-initials {
    width:80px; height:80px; border-radius:50%;
    background:linear-gradient(135deg,#2563eb,#4f46e5);
    color:#fff; font-size:22px; font-weight:700;
    display:flex; align-items:center; justify-content:center; margin:0 auto;
    border:3px solid var(--id-accent);
}
.stu-card .stu-name  { font-size:13px; font-weight:600; color:#1e2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.stu-card .stu-adm   { font-size:11px; color:var(--id-accent); font-weight:600; }
.stu-card .stu-class { font-size:11px; color:var(--id-muted); }

/* ── Empty / Loading ── */
.id-empty { text-align:center; padding:48px 20px; color:var(--id-muted); }
.id-empty i { font-size:48px; opacity:.25; display:block; margin-bottom:12px; }
.id-empty p { font-size:14px; margin:0; }

/* ── Bulk bar ── */
.bulk-bar {
    background:#fff3cd; border:1px solid #ffc107;
    border-radius:8px; padding:10px 16px;
    display:none; align-items:center; gap:12px; margin-bottom:12px;
}
.bulk-bar.show { display:flex; }

/* ── Pagination ── */
.pag-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:34px; height:34px; border-radius:8px;
    border:1.5px solid var(--id-border); background:#fff;
    color:var(--id-primary); font-size:13px; cursor:pointer;
    transition:all .15s; text-decoration:none;
}
.pag-btn:hover, .pag-btn.active { background:var(--id-accent); border-color:var(--id-accent); color:#fff; }
.pag-btn:disabled { opacity:.4; cursor:not-allowed; }

/* ── Orientation toggle ── */
.orient-toggle .btn-outline-primary { font-size:12px; padding:6px 14px; border-radius:8px; border:1.5px solid var(--id-border); color:var(--id-primary); }
.orient-toggle .btn-check:checked + .btn-outline-primary { background:var(--id-accent); border-color:var(--id-accent); color:#fff; }

/* ── Modal ── */
#previewModal .modal-content { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.15); }
.modal-hero-bar {
    background:linear-gradient(135deg, var(--id-primary) 0%, #2563eb 100%);
    padding:22px 28px; position:relative; overflow:hidden;
}
.modal-hero-bar::before { content:''; position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,.07); border-radius:50%; }
.modal-hero-bar h5 { color:#fff; font-weight:700; margin:0; font-size:16px; position:relative; }
.modal-hero-bar .btn-close { position:absolute; top:18px; right:20px; filter:invert(1); opacity:1; }

/* ── Spinner overlay ── */
#loadingOverlay {
    display:none; position:absolute; inset:0; background:rgba(255,255,255,.95);
    z-index:10; align-items:center; justify-content:center; border-radius:var(--id-radius);
}
#loadingOverlay.show { display:flex; }

/* ── ID Card Preview Styles ── */
.id-card-preview {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}
.id-card-item {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.id-card-label {
    background: #e9ecef;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    border-bottom: 1px solid #dee2e6;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="id-hero">
        <h1><i class="ri-id-card-line me-2"></i>Student ID Card Generator</h1>
        <p>Generate, preview and download professional student ID cards.</p>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-group-line"></i></div>
                <div class="stat-value" id="statLoaded">0</div>
                <div class="stat-label">Students Loaded</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-checkbox-circle-line"></i></div>
                <div class="stat-value text-primary" id="statSelected">0</div>
                <div class="stat-label">Selected</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-line"></i></div>
                <div class="stat-value text-success" id="statMale">0</div>
                <div class="stat-label">Male Students</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="ri-user-2-line"></i></div>
                <div class="stat-value text-warning" id="statFemale">0</div>
                <div class="stat-label">Female Students</div>
            </div>
        </div>
    </div>

    {{-- Main Panel --}}
    <div class="id-panel" style="position:relative;">

        <div id="loadingOverlay">
            <div class="text-center">
                <div class="spinner-border text-primary mb-2"></div>
                <div class="text-muted" style="font-size:13px;">Loading students...</div>
            </div>
        </div>

        {{-- Header --}}
        <div class="id-panel-header">
            <div class="id-panel-title">
                <i class="ri-id-card-line"></i>
                Select Students
                <span class="badge bg-primary ms-1" id="totalBadge">0</span>
            </div>
            <div class="d-flex gap-2">
                @can('Generate id card')
                <button class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn" style="display:none!important;">
                    <i class="ri-close-line me-1"></i>Clear
                </button>
                <button class="btn btn-primary btn-sm" id="previewSelectedBtn" disabled>
                    <i class="fas fa-eye me-1"></i> Preview (<span id="previewCount">0</span>)
                </button>
                @endcan
            </div>
        </div>

        {{-- Filters --}}
        <div class="id-filter">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:12px;color:#374151;">Search Student</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border:1.5px solid var(--id-border);border-right:none;background:#f8fafc;border-radius:8px 0 0 8px;">
                            <i class="ri-search-line text-muted" style="font-size:14px;"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control"
                               style="border-left:none;border-radius:0 8px 8px 0;border:1.5px solid var(--id-border);"
                               placeholder="Name or admission number...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:12px;color:#374151;">Class</label>
                    <select id="classFilter" class="form-select">
                        <option value="">All Classes</option>
                        @foreach($schoolclasses as $class)
                            <option value="{{ $class->id }}">{{ $class->class_display }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" style="font-size:12px;color:#374151;">Per Page</label>
                    <select id="perPage" class="form-select">
                        <option value="20">20</option>
                        <option value="40">40</option>
                        <option value="60">60</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:12px;color:#374151;">Orientation</label>
                    <div class="btn-group w-100 orient-toggle">
                        <input type="radio" class="btn-check" name="orientation" id="portrait" value="portrait" checked>
                        <label class="btn btn-outline-primary w-50" for="portrait"><i class="ri-layout-top-line me-1"></i>Portrait</label>
                        <input type="radio" class="btn-check" name="orientation" id="landscape" value="landscape">
                        <label class="btn btn-outline-primary w-50" for="landscape"><i class="ri-layout-right-line me-1"></i>Landscape</label>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" id="loadStudentsBtn">
                        <i class="ri-search-line me-1"></i>Load Students
                    </button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn">
                        <i class="ri-refresh-line me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Bulk bar --}}
        <div class="bulk-bar mx-4 mt-3" id="bulkBar">
            <i class="ri-checkbox-circle-line text-warning"></i>
            <span id="bulkCount">0</span> student(s) selected
            @can('Print id card')
            <button class="btn btn-sm btn-success ms-2" id="downloadBulkBtn">
                <i class="ri-download-line me-1"></i>Download PDF
            </button>
            @endcan
            <button class="btn btn-sm btn-outline-secondary ms-auto" id="clearBulkBtn">
                <i class="ri-close-line me-1"></i>Clear Selection
            </button>
        </div>

        {{-- Grid --}}
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" id="selectAll">
                        <label class="form-check-label fw-semibold" for="selectAll" style="font-size:13px;">Select All on Page</label>
                    </div>
                    <span class="text-muted" style="font-size:12px;" id="paginationInfo"></span>
                </div>
                <div class="d-flex gap-1 align-items-center" id="paginationControls"></div>
            </div>

            <div class="row g-3" id="studentsGrid">
                <div class="col-12 id-empty">
                    <i class="ri-id-card-line"></i>
                    <p>Use the filters above and click <strong>Load Students</strong> to begin.</p>
                </div>
            </div>
        </div>

    </div>{{-- /.id-panel --}}

</div>
</div>
</div>

{{-- ── PREVIEW MODAL ────────────────────────────────────────── --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-hero-bar">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                <h5><i class="ri-eye-line me-2"></i>ID Card Preview</h5>
            </div>
            <div class="modal-body p-0 bg-light" id="previewBody">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--id-border);">
                <span class="text-muted me-auto" style="font-size:13px;" id="previewInfo"></span>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                @can('Print id card')
                <button type="button" class="btn btn-primary" id="downloadFromPreview">
                    <i class="ri-download-line me-1"></i>Download PDF
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    /* ── State ── */
    let selectedIds   = new Set();
    let currentPage   = 1;
    let studentsCache = [];

    const CSRF = $('meta[name="csrf-token"]').attr('content');

    /* ════════════════════════════════════
       LOAD STUDENTS
    ════════════════════════════════════ */
    function loadStudents(page) {
        page = page || 1;
        currentPage = page;

        const search  = $('#searchInput').val().trim();
        const classId = $('#classFilter').val();
        const perPage = $('#perPage').val();

        $('#loadingOverlay').addClass('show');
        $('#studentsGrid').html('');
        $('#paginationControls').html('');
        $('#paginationInfo').text('');

        $.ajax({
            url: '{{ route("student-id-cards.load-students") }}',
            type: 'GET',
            data: { search, class_id: classId, per_page: perPage, page },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res.success) {
                    studentsCache = res.data.data;
                    renderStudents(studentsCache);
                    renderPagination(res.data);
                    updateStats(studentsCache, res.data.total);
                } else {
                    showGridError('Failed to load students. ' + (res.message || ''));
                }
            },
            error: function (xhr) {
                let msg = 'An error occurred while loading students.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showGridError(msg);
                console.error(xhr.responseText);
            },
            complete: function () {
                $('#loadingOverlay').removeClass('show');
            }
        });
    }

    function showGridError(msg) {
        $('#studentsGrid').html(`
            <div class="col-12 id-empty">
                <i class="ri-error-warning-line" style="color:#dc2626;opacity:.4;"></i>
                <p class="text-danger">${msg}</p>
            </div>`);
    }

    /* ════════════════════════════════════
       RENDER STUDENTS
    ════════════════════════════════════ */
    function renderStudents(students) {
        if (!students || students.length === 0) {
            $('#studentsGrid').html(`
                <div class="col-12 id-empty">
                    <i class="ri-user-search-line"></i>
                    <p>No students found. Try adjusting your filters.</p>
                </div>`);
            $('#selectAll').prop('checked', false);
            return;
        }

        let html = '';
        students.forEach(function (s) {
            const id       = s.id.toString();
            const isChk    = selectedIds.has(id);
            const fullname = ((s.firstname || '') + ' ' + (s.lastname || '')).trim();
            const initials = ((s.firstname || ' ')[0] + (s.lastname || ' ')[0]).toUpperCase();
            const photoUrl = s.picture ? '/storage/images/student_avatars/' + s.picture : null;
            const cls      = [s.schoolclass, s.arm].filter(Boolean).join(' ');
            const isMale   = (s.gender === 'Male' || s.gender === 'M');

            html += `
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="stu-card${isChk ? ' selected' : ''}" data-id="${id}">
                    <div class="check-wrap">
                        <input type="checkbox" class="student-check" value="${id}"${isChk ? ' checked' : ''}>
                    </div>
                    <div class="text-center pt-4 pb-2 px-2">
                        ${photoUrl
                            ? `<img src="${photoUrl}" class="stu-photo" alt="${fullname}"
                                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                               <div class="stu-initials" style="display:none;">${initials}</div>`
                            : `<div class="stu-initials">${initials}</div>`
                        }
                    </div>
                    <div class="pb-3 px-2 text-center">
                        <div class="stu-name" title="${fullname}">${fullname}</div>
                        <div class="stu-adm">${s.admissionNo || ''}</div>
                        <div class="stu-class">${cls}</div>
                        <div class="mt-1">
                            <span class="badge ${isMale ? 'bg-primary' : 'bg-danger'} bg-opacity-10
                                  text-${isMale ? 'primary' : 'danger'}" style="font-size:10px;">
                                ${s.gender || 'N/A'}
                            </span>
                        </div>
                    </div>
                </div>
            </div>`;
        });

        $('#studentsGrid').html(html);

        const allOnPage = students.every(s => selectedIds.has(s.id.toString()));
        $('#selectAll').prop('checked', allOnPage && students.length > 0);
    }

    /* ════════════════════════════════════
       STATS
    ════════════════════════════════════ */
    function updateStats(students, total) {
        $('#statLoaded').text(total || 0);
        $('#statSelected').text(selectedIds.size);
        $('#totalBadge').text(total || 0);

        const maleCount = students.filter(s => s.gender === 'Male' || s.gender === 'M').length;
        const femaleCount = students.filter(s => s.gender === 'Female' || s.gender === 'F').length;

        $('#statMale').text(maleCount);
        $('#statFemale').text(femaleCount);
    }

    function updateSelectionUI() {
        const count = selectedIds.size;
        $('#statSelected').text(count);
        $('#previewCount').text(count);
        $('#previewSelectedBtn').prop('disabled', count === 0);
        $('#bulkBar').toggleClass('show', count > 0);
        $('#bulkCount').text(count);
        $('#clearSelectionBtn').toggle(count > 0);
    }

    /* ════════════════════════════════════
       PAGINATION
    ════════════════════════════════════ */
    function renderPagination(meta) {
        const from = meta.from || 0, to = meta.to || 0, tot = meta.total || 0;
        $('#paginationInfo').text(`Showing ${from}–${to} of ${tot} students`);

        if (meta.last_page <= 1) {
            $('#paginationControls').html('');
            return;
        }

        const cur = meta.current_page, last = meta.last_page;
        let html = `<button class="pag-btn me-1" id="prevPage" ${cur <= 1 ? 'disabled' : ''}>
                        <i class="ri-arrow-left-s-line"></i></button>`;

        let start = Math.max(1, cur - 2), end = Math.min(last, cur + 2);
        if (start > 1) html += `<button class="pag-btn me-1" data-page="1">1</button>`;
        if (start > 2) html += `<span class="text-muted mx-1" style="font-size:13px;">…</span>`;
        for (let p = start; p <= end; p++) {
            html += `<button class="pag-btn me-1${p === cur ? ' active' : ''}" data-page="${p}">${p}</button>`;
        }
        if (end < last - 1) html += `<span class="text-muted mx-1" style="font-size:13px;">…</span>`;
        if (end < last) html += `<button class="pag-btn me-1" data-page="${last}">${last}</button>`;
        html += `<button class="pag-btn" id="nextPage" ${cur >= last ? 'disabled' : ''}>
                     <i class="ri-arrow-right-s-line"></i></button>`;

        $('#paginationControls').html(html);
    }

    $(document).on('click', '#prevPage:not([disabled])',  function () { loadStudents(currentPage - 1); });
    $(document).on('click', '#nextPage:not([disabled])',  function () { loadStudents(currentPage + 1); });
    $(document).on('click', '#paginationControls [data-page]', function () {
        loadStudents(parseInt($(this).data('page')));
    });

    /* ════════════════════════════════════
       SELECTION EVENTS
    ════════════════════════════════════ */
    $(document).on('click', '.stu-card', function (e) {
        if ($(e.target).is('input[type=checkbox]')) return;
        const chk = $(this).find('.student-check');
        chk.prop('checked', !chk.prop('checked')).trigger('change');
    });

    $(document).on('change', '.student-check', function () {
        const id   = this.value;
        const card = $(this).closest('.stu-card');
        if (this.checked) {
            selectedIds.add(id);
            card.addClass('selected');
        } else {
            selectedIds.delete(id);
            card.removeClass('selected');
        }

        const allOnPage = studentsCache.length > 0 && studentsCache.every(s => selectedIds.has(s.id.toString()));
        $('#selectAll').prop('checked', allOnPage);
        updateSelectionUI();
    });

    $('#selectAll').on('change', function () {
        const checked = this.checked;
        $('.student-check').prop('checked', checked).each(function () {
            const id   = this.value;
            const card = $(this).closest('.stu-card');
            if (checked) {
                selectedIds.add(id);
                card.addClass('selected');
            } else {
                selectedIds.delete(id);
                card.removeClass('selected');
            }
        });
        updateSelectionUI();
    });

    $('#clearBulkBtn, #clearSelectionBtn').on('click', function () {
        selectedIds.clear();
        $('.student-check').prop('checked', false);
        $('.stu-card').removeClass('selected');
        $('#selectAll').prop('checked', false);
        updateSelectionUI();
    });

    /* ════════════════════════════════════
       LOAD / RESET BUTTONS
    ════════════════════════════════════ */
    $('#loadStudentsBtn').on('click', function () {
        loadStudents(1);
    });

    $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) loadStudents(1);
    });

    $('#resetFiltersBtn').on('click', function () {
        $('#searchInput').val('');
        $('#classFilter').val('');
        $('#perPage').val('20');
        loadStudents(1);
    });

    /* ════════════════════════════════════
       PREVIEW
    ════════════════════════════════════ */
    $('#previewSelectedBtn').on('click', function () {
        if (selectedIds.size === 0) {
            alert('Please select at least one student.');
            return;
        }
        doPreview();
    });

    function doPreview() {
        $('#previewBody').html('<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>');
        $('#previewModal').modal('show');

        $.ajax({
            url:  '{{ route("student-id-cards.preview") }}',
            type: 'POST',
            data: {
                student_ids: Array.from(selectedIds),
                orientation: $('input[name="orientation"]:checked').val(),
                _token: CSRF,
            },
            success: function (res) {
                if (res.success) {
                    $('#previewBody').html(res.html);
                    $('#previewInfo').text(res.count + ' ID card(s) ready');
                } else {
                    $('#previewBody').html('<div class="p-4 text-danger">Failed to generate preview: ' + (res.message || 'Unknown error') + '</div>');
                }
            },
            error: function (xhr) {
                let msg = 'An error occurred while generating preview.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#previewBody').html('<div class="p-4 text-danger">' + msg + '</div>');
                console.error(xhr.responseText);
            }
        });
    }

    /* ════════════════════════════════════
       DOWNLOAD PDF
    ════════════════════════════════════ */
    function doDownload() {
        if (selectedIds.size === 0) {
            alert('Please select at least one student.');
            return;
        }

        const form = $('<form>', {
            method: 'POST',
            action: '{{ route("student-id-cards.download") }}',
            target: '_blank'
        });

        form.append($('<input>', { type: 'hidden', name: '_token', value: CSRF }));
        form.append($('<input>', { type: 'hidden', name: 'orientation', value: $('input[name="orientation"]:checked').val() }));

        Array.from(selectedIds).forEach(id => {
            form.append($('<input>', { type: 'hidden', name: 'student_ids[]', value: id }));
        });

        $('body').append(form);
        form.submit();
        setTimeout(function() { form.remove(); }, 100);
    }

    $('#downloadFromPreview').on('click', doDownload);
    $('#downloadBulkBtn').on('click', doDownload);

    /* ── auto-load on page ready ── */
    loadStudents(1);
});
</script>
@endsection
