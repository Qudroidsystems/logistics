@extends('layouts.master')

@section('content')
<style>
@media (max-width: 768px) {
    .score-input {
        height: 48px;
        font-size: 1.1rem;
        padding: 8px;
        width: 80px;
        min-width: 80px;
        box-sizing: border-box;
        touch-action: manipulation;
        text-align: right;
    }
    .table-responsive { overflow-x: auto; }
    .avatar-sm { width: 40px !important; height: 40px !important; }
    td.vetted-status { font-size: 0.9rem; }
}
.bg-success-subtle { background-color: #d4edda !important; }
.bg-danger-subtle  { background-color: #f8d7da !important; }
.bg-warning-subtle { background-color: #fff3cd !important; }
.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25) !important;
}
th.sort { cursor: pointer; user-select: none; }
th.sort:hover { background-color: rgba(0,0,0,.05); }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Error!</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Flash messages --}}
            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?: session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- ── Subject info cards ── --}}
            @if ($broadsheets->isNotEmpty())
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body py-3">
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="border border-dashed rounded px-4 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-book fs-4 text-primary"></i>
                                            <span class="fs-5 fw-bold text-success">{{ $broadsheets->first()->subject }}</span>
                                        </div>
                                        <small class="text-muted">Subject</small>
                                    </div>
                                    <div class="border border-dashed rounded px-4 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-code fs-4 text-success"></i>
                                            <span class="fs-5 fw-bold text-success">{{ $broadsheets->first()->subject_code }}</span>
                                        </div>
                                        <small class="text-muted">Subject Code</small>
                                    </div>
                                    <div class="border border-dashed rounded px-4 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-building fs-4 text-success"></i>
                                            <span class="fs-5 fw-bold text-success">
                                                {{ $broadsheets->first()->schoolclass }} {{ $broadsheets->first()->arm }}
                                            </span>
                                        </div>
                                        <small class="text-muted">Class</small>
                                    </div>
                                    <div class="border border-dashed rounded px-4 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-calendar fs-4 text-success"></i>
                                            <span class="fs-5 fw-bold text-success">
                                                {{ $broadsheets->first()->term }} | {{ $broadsheets->first()->session }}
                                            </span>
                                        </div>
                                        <small class="text-muted">Term | Session</small>
                                    </div>
                                    {{-- Stats --}}
                                    @php
                                        $firstWithData = $broadsheets->firstWhere('cmin', '!=', null);
                                    @endphp
                                    @if($broadsheets->first()->cmin !== null)
                                    <div class="border border-dashed rounded px-4 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-bar-chart fs-4 text-info"></i>
                                            <span class="fs-5 fw-bold text-info">
                                                Min: {{ $broadsheets->first()->cmin }}
                                                &nbsp;|&nbsp; Max: {{ $broadsheets->first()->cmax }}
                                                &nbsp;|&nbsp; Avg: {{ $broadsheets->first()->avg }}
                                            </span>
                                        </div>
                                        <small class="text-muted">Class Statistics</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ── Main scoresheet card ── --}}
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="card-title mb-0 flex-grow-1">
                                {{ $pagetitle }}
                                @if ($broadsheets->isNotEmpty())
                                    <span class="badge bg-info-subtle text-info ms-2" id="scoreCount">
                                        {{ $broadsheets->count() }}
                                    </span>
                                @endif
                            </h5>
                            <div class="input-group" style="max-width:320px;">
                                <input type="text" class="form-control" id="searchInput"
                                       placeholder="Search by admission no or name…"
                                       {{ $broadsheets->isEmpty() ? 'disabled' : '' }}>
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="ri-close-line"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            {{-- Top action bar --}}
                            <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                                <a href="{{ route('myresultroom.index') }}" class="btn btn-primary">
                                    <i class="ri-arrow-left-line"></i> Back
                                </a>
                                <div class="d-flex gap-2">
                                    @if(session('subjectclass_id'))
                                        <a href="{{ route('subjectscoresheet-mock.download-marksheet') }}"
                                           class="btn btn-warning">
                                            <i class="fas fa-file-pdf me-1"></i> Download Marks Sheet
                                        </a>
                                    @endif
                                    @if ($broadsheets->isNotEmpty())
                                        <button class="btn btn-secondary"
                                                data-bs-toggle="modal" data-bs-target="#scoresModal">
                                            <i class="bi bi-table me-1"></i> View Scores
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- No data alert --}}
                            <div class="alert alert-info text-center" id="noDataAlert"
                                 style="display:{{ $broadsheets->isEmpty() ? 'block' : 'none' }};">
                                <i class="ri-information-line me-2"></i>
                                No mock scores available for the selected subject.
                            </div>

                            {{-- ── TABLE ── --}}
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0"
                                       id="scoresheetTable">
                                    <thead class="table-active">
                                        <tr>
                                            <th style="width:50px;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                </div>
                                            </th>
                                            <th class="sort" data-sort="sn" style="width:50px;">SN</th>
                                            <th class="sort" data-sort="admissionno">Admission No</th>
                                            <th class="sort" data-sort="name">Name</th>
                                            <th style="width:110px;">
                                                Exam<br><small class="text-muted">(100)</small>
                                            </th>
                                            <th>Total</th>
                                            <th>Grade</th>
                                            <th>Remark</th>
                                            <th>Position</th>
                                            <th>Vetted</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scoresheetTableBody" class="list form-check-all">
                                        @php $i = 0; @endphp
                                        @forelse ($broadsheets as $broadsheet)
                                            @php
                                                $vStatus = $broadsheet->vettedstatus;
                                                $rowClass = $vStatus === '1'
                                                    ? 'bg-success-subtle'
                                                    : ($vStatus === '0' ? 'bg-danger-subtle' : 'bg-warning-subtle');
                                                $vLabel = $vStatus === '1'
                                                    ? 'Vetted'
                                                    : ($vStatus === '0' ? 'Not Vetted' : 'Pending');
                                                $vBadge = $vStatus === '1' ? 'bg-success' : ($vStatus === '0' ? 'bg-danger' : 'bg-warning');
                                            @endphp
                                            <tr class="{{ $rowClass }}"
                                                data-id="{{ $broadsheet->id }}"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="{{ $vLabel }}">
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input score-checkbox"
                                                               type="checkbox" name="chk_child"
                                                               data-id="{{ $broadsheet->id }}">
                                                    </div>
                                                </td>
                                                <td class="sn">{{ ++$i }}</td>
                                                <td class="admissionno"
                                                    data-admissionno="{{ $broadsheet->admissionno }}">
                                                    {{ $broadsheet->admissionno ?? '-' }}
                                                </td>
                                                <td class="name"
                                                    data-name="{{ ($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '') }}">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2">
                                                            <img src="{{ $broadsheet->picture
                                                                    ? asset('storage/student_avatars/' . basename($broadsheet->picture))
                                                                    : asset('storage/student_avatars/unnamed.jpg') }}"
                                                                 alt="{{ ($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') }}"
                                                                 class="rounded-circle w-100 student-image"
                                                                 data-bs-toggle="modal"
                                                                 data-bs-target="#imageViewModal"
                                                                 data-image="{{ $broadsheet->picture
                                                                    ? asset('storage/student_avatars/' . basename($broadsheet->picture))
                                                                    : asset('storage/student_avatars/unnamed.jpg') }}"
                                                                 onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold">{{ $broadsheet->lname ?? '' }}</span>
                                                            {{ $broadsheet->fname ?? '' }} {{ $broadsheet->mname ?? '' }}
                                                        </div>
                                                    </div>
                                                </td>

                                                {{-- ── EXAM INPUT ── --}}
                                                <td>
                                                    <input type="number"
                                                           class="form-control score-input"
                                                           data-id="{{ $broadsheet->id }}"
                                                           data-original="{{ $broadsheet->exam ?? '' }}"
                                                           value="{{ $broadsheet->exam ?? '' }}"
                                                           min="0" max="100" step="0.1"
                                                           placeholder="0–100"
                                                           style="width:90px;">
                                                </td>

                                                <td class="total-display text-center">
                                                    <span class="badge bg-primary">
                                                        {{ $broadsheet->total ? number_format($broadsheet->total, 1) : '0.0' }}
                                                    </span>
                                                </td>
                                                <td class="grade-display text-center">
                                                    <span class="badge bg-secondary">
                                                        {{ $broadsheet->grade ?? '-' }}
                                                    </span>
                                                </td>
                                                <td class="remark-display text-center">
                                                    <span class="badge bg-info text-dark">
                                                        {{ $broadsheet->remark ?? '-' }}
                                                    </span>
                                                </td>
                                                <td class="position-display text-center">
                                                    <span class="badge bg-dark">
                                                        @if($broadsheet->position)
                                                            {{ $broadsheet->position }}{{ \App\Helpers\OrdinalHelper::getOrdinalSuffix($broadsheet->position) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="vetted-status text-center">
                                                    <span class="badge {{ $vBadge }}">{{ $vLabel }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="noDataRow">
                                                <td colspan="10" class="text-center text-muted py-4">
                                                    No scores available.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>{{-- /table-responsive --}}

                            {{-- ── Control panel ── --}}
                            @if ($broadsheets->isNotEmpty())
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-semibold">Bulk Actions:</span>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary"
                                                            id="selectAllBtn">
                                                        <i class="ri-check-double-line me-1"></i>Select All
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                            id="clearAllBtn">
                                                        <i class="ri-close-line me-1"></i>Clear All
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger"
                                                            id="deleteSelectedBtn">
                                                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-muted">
                                                    <i class="ri-information-line"></i> Ctrl+S to save quickly
                                                </small>
                                                <button class="btn btn-success" id="bulkSaveBtn">
                                                    <i class="ri-save-line me-1"></i> Save All Scores
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Save progress --}}
                                <div id="progressContainer" style="display:none;" class="mt-2">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Saving…</span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">Updating Mock Scores…</h6>
                                                    <div class="progress" style="height:6px;">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                             role="progressbar" id="saveProgressBar"
                                                             style="width:0%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>{{-- /card-body --}}
                    </div>{{-- /card --}}
                </div>
            </div>

            {{-- ── Scores overview modal ── --}}
            <div class="modal fade" id="scoresModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="fw-bold">Mock Scores Overview</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Admission No</th>
                                            <th>Name</th>
                                            <th>Exam</th>
                                            <th>Total</th>
                                            <th>Grade</th>
                                            <th>Remark</th>
                                            <th>Position</th>
                                            <th>Min</th>
                                            <th>Max</th>
                                            <th>Avg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $j = 0; @endphp
                                        @forelse ($broadsheets as $broadsheet)
                                            <tr>
                                                <td>{{ ++$j }}</td>
                                                <td>{{ $broadsheet->admissionno ?? '-' }}</td>
                                                <td>
                                                    <span class="fw-bold">{{ $broadsheet->lname }}</span>
                                                    {{ $broadsheet->fname }} {{ $broadsheet->mname }}
                                                </td>
                                                <td>{{ $broadsheet->exam ? number_format($broadsheet->exam, 1) : '0.0' }}</td>
                                                <td>{{ $broadsheet->total ? number_format($broadsheet->total, 1) : '0.0' }}</td>
                                                <td>{{ $broadsheet->grade ?? '-' }}</td>
                                                <td>{{ $broadsheet->remark ?? '-' }}</td>
                                                <td>
                                                    @if($broadsheet->position)
                                                        {{ $broadsheet->position }}{{ \App\Helpers\OrdinalHelper::getOrdinalSuffix($broadsheet->position) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ $broadsheet->cmin ?? '-' }}</td>
                                                <td>{{ $broadsheet->cmax ?? '-' }}</td>
                                                <td>{{ $broadsheet->avg ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center text-muted">No data.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Image viewer modal ── --}}
            <div class="modal fade" id="imageViewModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Student Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="enlargedImage" src="" alt="Student Image" class="img-fluid"
                                 onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /container-fluid --}}
    </div>
</div>

{{-- ── JavaScript ── --}}
<script>
    // Inject PHP data
    window.mockBroadsheets = @json($broadsheets);
    window.term_id         = {{ session('term_id') ?? 0 }};
    window.session_id      = {{ session('session_id') ?? 0 }};
    window.subjectclass_id = {{ session('subjectclass_id') ?? 0 }};
    window.schoolclass_id  = {{ session('schoolclass_id') ?? 0 }};
    window.staff_id        = {{ session('staff_id') ?? 0 }};

    window.mockRoutes = {
        bulkUpdate   : '{{ route("scoresheet-mock.bulk-update") }}',
        singleUpdate : '{{ route("scoresheet-mock.single-update") }}',
        destroy      : '{{ route("scoresheet-mock.destroy") }}',
    };

    document.addEventListener('DOMContentLoaded', function () {

        // ── Tooltips ──────────────────────────────────────────────
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });

        // ── Helpers ───────────────────────────────────────────────
        function fmt(n, d = 1) { return parseFloat(n || 0).toFixed(d); }

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        function validateInput(input) {
            const val = parseFloat(input.value) || 0;
            if (val > 100 || val < 0) {
                input.classList.add('is-invalid');
                input.title = 'Score must be between 0 and 100.';
                return false;
            }
            input.classList.remove('is-invalid');
            input.title = '';
            return true;
        }

        function validateAll() {
            let ok = true, count = 0;
            document.querySelectorAll('.score-input').forEach(inp => {
                if (!validateInput(inp)) { ok = false; count++; }
            });
            return { isValid: ok, invalidCount: count };
        }

        // ── Search ────────────────────────────────────────────────
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const noDataAlert = document.getElementById('noDataAlert');
        const scoreCount  = document.getElementById('scoreCount');

        function applySearch() {
            const q = (searchInput?.value || '').toLowerCase().trim();
            let visible = 0;
            document.querySelectorAll('#scoresheetTableBody tr[data-id]').forEach(row => {
                const admno = (row.querySelector('.admissionno')?.dataset.admissionno || '').toLowerCase();
                const name  = (row.querySelector('.name')?.dataset.name || '').toLowerCase();
                const show  = !q || admno.includes(q) || name.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (noDataAlert) noDataAlert.style.display = visible === 0 ? 'block' : 'none';
            if (scoreCount)  scoreCount.textContent = visible;
        }

        searchInput?.addEventListener('input', applySearch);
        clearSearch?.addEventListener('click', () => { if (searchInput) searchInput.value = ''; applySearch(); });

        // ── Checkboxes ────────────────────────────────────────────
        const checkAll = document.getElementById('checkAll');
        function syncCheckAll() {
            const all = document.querySelectorAll('.score-checkbox');
            const checked = document.querySelectorAll('.score-checkbox:checked');
            if (checkAll) {
                checkAll.checked = checked.length === all.length && all.length > 0;
                checkAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }
        }
        checkAll?.addEventListener('change', function () {
            document.querySelectorAll('.score-checkbox').forEach(cb => cb.checked = this.checked);
        });
        document.querySelectorAll('.score-checkbox').forEach(cb => cb.addEventListener('change', syncCheckAll));

        document.getElementById('selectAllBtn')?.addEventListener('click', () => {
            if (checkAll) checkAll.checked = true;
            document.querySelectorAll('.score-checkbox').forEach(cb => cb.checked = true);
        });
        document.getElementById('clearAllBtn')?.addEventListener('click', () => {
            if (checkAll) checkAll.checked = false;
            document.querySelectorAll('.score-checkbox').forEach(cb => cb.checked = false);
        });

        // ── Input events ──────────────────────────────────────────
        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('input', () => validateInput(input));

            // Auto-save on blur if changed
            input.addEventListener('blur', function () {
                validateInput(this);
                const original = parseFloat(this.dataset.original) || 0;
                const current  = parseFloat(this.value) || 0;
                if (Math.abs(current - original) > 0.005) {
                    saveSingleScore(this);
                    this.dataset.original = this.value;
                }
            });

            // Enter key → save
            input.addEventListener('keypress', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                if (!validateInput(this)) {
                    Swal.fire({ icon: 'warning', title: 'Invalid Score', text: 'Score must be between 0 and 100.' });
                    return;
                }
                saveSingleScore(this);
            });
        });

        // ── Single save ───────────────────────────────────────────
        function saveSingleScore(input) {
            const id    = input.dataset.id;
            const score = parseFloat(input.value) || 0;

            fetch(window.mockRoutes.singleUpdate, {
                method : 'POST',
                headers: {
                    'Content-Type' : 'application/json',
                    'X-CSRF-TOKEN' : csrfToken(),
                },
                body: JSON.stringify({ broadsheet_id: id, exam: score }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Save failed.' });
                    return;
                }
                const row = document.querySelector(`tr[data-id="${id}"]`);
                if (row) {
                    const d = data.data;
                    const q = (sel) => row.querySelector(sel);
                    if (q('.total-display span'))   q('.total-display span').textContent   = fmt(d.total);
                    if (q('.grade-display span'))    q('.grade-display span').textContent   = d.grade || '-';
                    if (q('.remark-display span'))   q('.remark-display span').textContent  = d.remark || '-';
                    if (q('.position-display span')) q('.position-display span').textContent = d.position || '-';
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not save score.' });
            });
        }

        // ── Bulk save ─────────────────────────────────────────────
        document.getElementById('bulkSaveBtn')?.addEventListener('click', function () {
            const { isValid, invalidCount } = validateAll();
            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Scores',
                    text: `${invalidCount} score(s) are out of range. Please correct them first.`,
                });
                return;
            }

            const scores = [];
            document.querySelectorAll('#scoresheetTableBody tr[data-id]').forEach(row => {
                const input = row.querySelector('.score-input');
                scores.push({
                    id  : row.dataset.id,
                    exam: parseFloat(input?.value) || 0,
                });
            });

            if (!scores.length) {
                Swal.fire({ icon: 'warning', title: 'Nothing to save', text: 'No rows found.' });
                return;
            }

            const progress = document.getElementById('progressContainer');
            const bar      = document.getElementById('saveProgressBar');
            if (progress) progress.style.display = 'block';
            let w = 0;
            const ticker = setInterval(() => { w = Math.min(w + 8, 95); if (bar) bar.style.width = w + '%'; }, 150);

            fetch(window.mockRoutes.bulkUpdate, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body   : JSON.stringify({
                    scores,
                    term_id         : window.term_id,
                    session_id      : window.session_id,
                    subjectclass_id : window.subjectclass_id,
                    staff_id        : window.staff_id,
                    schoolclass_id  : window.schoolclass_id,
                }),
            })
            .then(r => r.json())
            .then(data => {
                clearInterval(ticker);
                if (bar) bar.style.width = '100%';
                setTimeout(() => { if (progress) progress.style.display = 'none'; }, 400);

                if (data.success) {
                    Swal.fire({
                        icon: 'success', title: 'Saved!',
                        text: data.message, timer: 2000, showConfirmButton: false,
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Update failed.' });
                }
            })
            .catch(err => {
                clearInterval(ticker);
                if (progress) progress.style.display = 'none';
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not save scores.' });
            });
        });

        // ── Delete selected ───────────────────────────────────────
        document.getElementById('deleteSelectedBtn')?.addEventListener('click', function () {
            const ids = Array.from(document.querySelectorAll('.score-checkbox:checked')).map(cb => cb.dataset.id);
            if (!ids.length) {
                Swal.fire({ icon: 'warning', title: 'No Selection', text: 'Please select rows to delete.' });
                return;
            }
            Swal.fire({
                title: 'Are you sure?',
                text : `Delete ${ids.length} selected score(s)? This cannot be undone.`,
                icon : 'warning',
                showCancelButton    : true,
                confirmButtonColor  : '#d33',
                cancelButtonColor   : '#3085d6',
                confirmButtonText   : 'Yes, delete!',
            }).then(result => {
                if (!result.isConfirmed) return;
                Promise.all(ids.map(id =>
                    fetch(window.mockRoutes.destroy, {
                        method : 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                        body   : JSON.stringify({ id }),
                    }).then(r => r.json()).then(d => ({ id, success: d.success }))
                )).then(results => {
                    let deleted = 0;
                    results.forEach(({ id, success }) => {
                        if (success) {
                            document.querySelector(`tr[data-id="${id}"]`)?.remove();
                            deleted++;
                        }
                    });
                    if (deleted) {
                        Swal.fire({
                            icon: 'success', title: 'Deleted!',
                            text: `${deleted} score(s) removed.`, timer: 2000, showConfirmButton: false,
                        });
                        applySearch();
                        if (!document.querySelectorAll('#scoresheetTableBody tr[data-id]').length) location.reload();
                    }
                });
            });
        });

        // ── Sorting ───────────────────────────────────────────────
        document.querySelectorAll('th.sort').forEach(th => {
            th.addEventListener('click', function () {
                const by   = this.dataset.sort;
                const tbody = document.getElementById('scoresheetTableBody');
                const rows  = Array.from(tbody.querySelectorAll('tr[data-id]'));
                rows.sort((a, b) => {
                    if (by === 'sn') {
                        return parseInt(a.querySelector('.sn')?.textContent) - parseInt(b.querySelector('.sn')?.textContent);
                    } else if (by === 'admissionno') {
                        return (a.querySelector('.admissionno')?.textContent.trim() || '')
                            .localeCompare(b.querySelector('.admissionno')?.textContent.trim() || '', undefined, { numeric: true });
                    } else if (by === 'name') {
                        return (a.querySelector('.name')?.dataset.name || '').localeCompare(b.querySelector('.name')?.dataset.name || '');
                    }
                    return 0;
                });
                rows.forEach((row, i) => { if (row.querySelector('.sn')) row.querySelector('.sn').textContent = i + 1; });
                rows.forEach(row => tbody.appendChild(row));
            });
        });

        // ── Image modal ───────────────────────────────────────────
        document.addEventListener('click', e => {
            if (e.target.classList.contains('student-image')) {
                const img = document.getElementById('enlargedImage');
                if (img) img.src = e.target.dataset.image || '';
            }
        });

        // ── Ctrl+S shortcut ───────────────────────────────────────
        document.addEventListener('keydown', e => {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('bulkSaveBtn')?.click();
            }
        });

    }); // DOMContentLoaded

    // Load SweetAlert2 if missing
    if (typeof Swal === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        document.head.appendChild(s);
    }
</script>
@endsection
