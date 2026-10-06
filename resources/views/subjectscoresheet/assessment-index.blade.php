@extends('layouts.master')

@section('content')
<style>
/* Reuse styles from main scoresheet */
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
    td.assessment-col { padding: 4px !important; }
}
.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}
.score-input:focus.is-invalid {
    background-image: none; /* Override Bootstrap focus */
}
</style>

<!-- Include SweetAlert2 if not already in layout -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Main content container -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Display validation errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Error!</strong> There were some problems with your input.<br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Display success/status messages -->
            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?: session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Subject Information Cards -->
            @if ($broadsheets->isNotEmpty())
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="d-flex flex-wrap flex-stack mb-4">
                                        <div class="d-flex flex-column flex-grow-1 pe-8">
                                            <div class="d-flex flex-wrap">
                                                <!-- Subject Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-book fs-3 text-primary me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $broadsheets->first()->subject }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Subject</div>
                                                </div>
                                                <!-- Subject Code Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-code fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $broadsheets->first()->subject_code }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Subject Code</div>
                                                </div>
                                                <!-- Class Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $broadsheets->first()->schoolclass }} {{ $broadsheets->first()->arm }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Class</div>
                                                </div>
                                                <!-- Term | Session Card -->
                                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-calendar fs-3 text-success me-2"></i>
                                                        <div class="fs-2 fw-bold text-success">{{ $broadsheets->first()->term }} | {{ $broadsheets->first()->session }}</div>
                                                    </div>
                                                    <div class="fw-semibold fs-6 text-gray-400">Term | Session</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assessment Info Card (with back to full subject) -->
                                    <div class="row g-2 mb-4">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="text-muted mb-2">Current Assessment: {{ $assessment->name }}</h6>
                                                    <div class="border border-gray-300 border-dashed rounded py-3 px-4">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-clipboard-check fs-3 text-info me-2"></i>
                                                                <div>
                                                                    <div class="fs-2 fw-bold text-info">{{ $assessment->name }}</div>
                                                                    <small class="text-muted">Max Score: {{ $assessment->max_score }}</small>
                                                                </div>
                                                            </div>
                                                            <a href="{{ route('subjectscoresheet', [
                                                                        'schoolclassid' => session('schoolclass_id'),
                                                                        'subjectclassid' => session('subjectclass_id'),
                                                                        'staffid' => session('staff_id'),
                                                                        'termid' => session('term_id'),
                                                                        'sessionid' => session('session_id')
                                                                    ]) }}" class="btn btn-outline-secondary btn-sm">
                                                                <i class="bi bi-arrow-left"></i> Back to Full Subject
                                                            </a>
                                                        </div>
                                                    </div>
                                                    @if($subAssessments->count() > 1)
                                                        <small class="text-muted d-block mt-2">
                                                            <i class="bi bi-list-ul me-1"></i>
                                                            Showing {{ $subAssessments->count() }} sub-assessments
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Scoresheet Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    {{ $pagetitle }}
                                    @if ($broadsheets->isNotEmpty())
                                        <span class="badge bg-info-subtle text-info ms-2" id="scoreCount">{{ $broadsheets->count() }}</span>
                                    @endif
                                </h5>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by admission no or name..." style="min-width: 200px;" {{ $broadsheets->isEmpty() ? 'disabled' : '' }}>
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- No Data Alert -->
                            <div class="alert alert-info text-center" style="display: {{ $broadsheets->isEmpty() ? 'block' : 'none' }};" id="noDataAlert">
                                <i class="ri-information-line me-2"></i>
                                No scores available for the selected assessment. Please check your filters or import scores.
                            </div>

                            <!-- Scoresheet Table -->
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0" id="scoresheetTable">
                                    <thead class="table-active">
                                        <tr>
                                            <th style="width: 50px;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                    <label class="form-check-label" for="checkAll"></label>
                                                </div>
                                            </th>
                                            <th style="width: 50px;" class="sort cursor-pointer" data-sort="sn">SN</th>
                                            <th class="sort cursor-pointer" data-sort="admissionno">Admission No</th>
                                            <th class="sort cursor-pointer" data-sort="name">Name</th>
                                            @forelse ($subAssessments as $subAssessment)
                                                <th>{{ $subAssessment->name }}<br><small>({{ $subAssessment->max_score }})</small></th>
                                            @empty
                                                <th colspan="1">No Sub-Assessments Defined</th>
                                            @endforelse
                                            <th class="sort cursor-pointer" data-sort="total">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scoresheetTableBody" class="list form-check-all">
                                        @php $i = 0; @endphp
                                        @forelse ($broadsheets as $broadsheet)
                                            @php
                                                $totalScore = 0;
                                                foreach ($subAssessments as $subAssessment) {
                                                    $scoreObj = $subAssessment->is_sub_item 
                                                        ? $broadsheet->subAssessmentScores->where('sub_assessment_id', $subAssessment->id)->first()
                                                        : $broadsheet->assessmentScores->where('assessment_id', $subAssessment->id)->first();
                                                    $totalScore += $scoreObj ? $scoreObj->score : 0;
                                                }
                                            @endphp
                                            <tr data-id="{{ $broadsheet->id }}">
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input score-checkbox" type="checkbox" name="chk_child" data-id="{{ $broadsheet->id }}">
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </td>
                                                <td class="sn">{{ ++$i }}</td>
                                                <td class="admissionno" data-admissionno="{{ $broadsheet->admissionno }}">{{ $broadsheet->admissionno ?? '-' }}</td>
                                                <td class="name" data-name="{{ ($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '') }}">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2">
                                                            <img src="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" alt="{{ ($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '') }}" class="rounded-circle w-100 student-image" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" data-picture="{{ $broadsheet->picture ?? 'none' }}" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'; console.log('Image failed to load for admissionno: {{ $broadsheet->admissionno ?? 'unknown' }}, picture: {{ $broadsheet->picture ?? 'none' }}');">
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold">{{ $broadsheet->lname ?? '' }}</span> {{ $broadsheet->fname ?? '' }} {{ $broadsheet->mname ?? '' }}
                                                        </div>
                                                    </div>
                                                </td>
                                                @forelse ($subAssessments as $subAssessment)
                                                    @php
                                                        $scoreObj = $subAssessment->is_sub_item 
                                                            ? $broadsheet->subAssessmentScores->where('sub_assessment_id', $subAssessment->id)->first()
                                                            : $broadsheet->assessmentScores->where('assessment_id', $subAssessment->id)->first();
                                                        $scoreValue = $scoreObj ? $scoreObj->score : '';
                                                    @endphp
                                                    <td class="assessment-col">
                                                        <input type="number" class="form-control score-input" 
                                                               data-field="{{ $subAssessment->id }}" 
                                                               data-is-sub="{{ $subAssessment->is_sub_item ? 'true' : 'false' }}" 
                                                               data-max="{{ $subAssessment->max_score }}" 
                                                               data-id="{{ $broadsheet->id }}" 
                                                               value="{{ $scoreValue }}" 
                                                               min="0" step="0.1" placeholder="">
                                                    </td>
                                                @empty
                                                    <td>-</td>
                                                @endforelse
                                                <td class="total-display text-center">
                                                    <span class="badge bg-primary" data-total="{{ $totalScore }}">{{ number_format($totalScore, 1) }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="noDataRow">
                                                <td colspan="{{ 5 + $subAssessments->count() }}" class="text-center">No scores available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Enhanced Control Panel -->
                            @if ($broadsheets->isNotEmpty())
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <h6 class="card-title mb-0 me-3">Bulk Actions:</h6>
                                                        <div class="btn-group me-2" role="group">
                                                            <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllScores">
                                                                <i class="ri-check-double-line me-1"></i> Select All
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearAllScores">
                                                                <i class="ri-close-line me-1"></i> Clear All
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger btn-sm" id="deleteSelectedScoresBtn">
                                                                <i class="ri-delete-bin-line me-1"></i> Delete Selected
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <small class="text-muted me-3">
                                                            <i class="ri-information-line"></i> Press Ctrl+S to save quickly
                                                        </small>
                                                        <button class="btn btn-success" id="bulkUpdateScores">
                                                            <i class="ri-save-line me-1"></i> Save All Scores
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress Indicator for Saving Scores -->
                                <div class="row mt-2" id="progressContainer" style="display: none;">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                            <span class="visually-hidden">Saving...</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">Updating Scores...</h6>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="saveProgressBar"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Modal -->
            <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="fw-bold">Bulk Upload Scores for {{ $assessment->name }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body scroll-y mx-5 mx-xl-10 my-7">
                            <form action="{{ route('subjectscoresheet.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                                @csrf
                                <input type="hidden" name="schoolclass_id" value="{{ session('schoolclass_id') }}">
                                <input type="hidden" name="subjectclass_id" value="{{ session('subjectclass_id') }}">
                                <input type="hidden" name="staff_id" value="{{ session('staff_id') }}">
                                <input type="hidden" name="term_id" value="{{ session('term_id') }}">
                                <input type="hidden" name="session_id" value="{{ session('session_id') }}">
                                <input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                                <div class="form-group mb-6">
                                    <label class="required fw-semibold fs-6 mb-2">Excel File (Columns for sub-assessments under {{ $assessment->name }})</label>
                                    <input type="file" name="file" class="form-control form-control-sm mb-3" accept=".xlsx,.xls" required>
                                </div>
                                <div class="form-group mb-6" id="importLoader" style="display: none;">
                                    <div class="d-flex align-items-center">
                                        <div class="spinner-border spinner-border-sm text-primary me-3" role="status">
                                            <span class="visually-hidden">Uploading...</span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Uploading File...</h6>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="uploadProgressBar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center pt-10">
                                    <button type="reset" class="btn btn-outline-secondary me-3" data-bs-dismiss="modal">Discard</button>
                                    <button type="submit" class="btn btn-primary" id="importSubmit">Upload</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scores Modal -->
            <div class="modal fade" id="scoresModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="fw-bold">Scores Overview for {{ $assessment->name }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0">
                                    <thead class="table-active">
                                        <tr>
                                            <th>SN</th>
                                            <th>Admission No</th>
                                            <th>Name</th>
                                            @forelse ($subAssessments as $subAssessment)
                                                <th>{{ $subAssessment->name }}</th>
                                            @empty
                                                <th colspan="1">No Sub-Assessments</th>
                                            @endforelse
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $i = 0; @endphp
                                        @forelse ($broadsheets as $broadsheet)
                                            @php
                                                $totalScore = 0;
                                                foreach ($subAssessments as $subAssessment) {
                                                    $scoreObj = $subAssessment->is_sub_item 
                                                        ? $broadsheet->subAssessmentScores->where('sub_assessment_id', $subAssessment->id)->first()
                                                        : $broadsheet->assessmentScores->where('assessment_id', $subAssessment->id)->first();
                                                    $totalScore += $scoreObj ? $scoreObj->score : 0;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td class="admissionno">{{ $broadsheet->admissionno ?? '-' }}</td>
                                                <td class="name">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2">
                                                            <img src="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" alt="{{ ($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '') }}" class="rounded-circle w-100 student-image" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" data-picture="{{ $broadsheet->picture ?? 'none' }}" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'; console.log('Image failed to load for admissionno: {{ $broadsheet->admissionno ?? 'unknown' }}, picture: {{ $broadsheet->picture ?? 'none' }}');">
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold">{{ $broadsheet->lname ?? '' }}</span> {{ $broadsheet->fname ?? '' }} {{ $broadsheet->mname ?? '' }}
                                                        </div>
                                                    </div>
                                                </td>
                                                @forelse ($subAssessments as $subAssessment)
                                                    @php
                                                        $scoreObj = $subAssessment->is_sub_item 
                                                            ? $broadsheet->subAssessmentScores->where('sub_assessment_id', $subAssessment->id)->first()
                                                            : $broadsheet->assessmentScores->where('assessment_id', $subAssessment->id)->first();
                                                    @endphp
                                                    <td>{{ $scoreObj ? number_format($scoreObj->score, 1) : '0.0' }}</td>
                                                @empty
                                                    <td>-</td>
                                                @endforelse
                                                <td>{{ number_format($totalScore, 1) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ 4 + $subAssessments->count() }}" class="text-center">No scores available.</td>
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

            <!-- Image View Modal -->
            <div id="imageViewModal" class="modal fade" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Student Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="enlargedImage" src="" alt="Student Image" class="img-fluid" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'; console.log('Enlarged image failed to load');">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Global variables first
    console.log('Raw broadsheets before normalization:', @json($broadsheets));
    window.broadsheets = @json($broadsheets);
    window.assessments = @json($subAssessments);
    window.term_id = {{ session('term_id') }};
    window.session_id = {{ session('session_id') }};
    window.subjectclass_id = {{ session('subjectclass_id') }};
    window.schoolclass_id = {{ session('schoolclass_id') }};
    window.staff_id = {{ session('staff_id') }};
    window.assessment_id = {{ $assessment->id }};
    window.assessment_max = {{ $assessment->max_score }};
    window.is_sub = {{ isset($is_sub_view) && $is_sub_view ? 'true' : 'false' }};
    window.is_senior = {{ $is_senior ? 'true' : 'false' }};
    window.routes = {
        results: '{{ route('subjectscoresheet.results') }}',
        bulkUpdate: '{{ route('subjectscoresheet.bulk-update') }}',
        singleUpdate: '{{ route('subjectscoresheet.single-update') }}',
        destroy: '{{ route('subjectscoresheet.destroy', ['id' => '__ID__']) }}',
        import: '{{ route('subjectscoresheet.import') }}',
        export: '{{ route('subjectscoresheet.export') }}',
        downloadMarksSheet: '{{ route('scoresheet.download-marks-sheet') }}',
        gradePreview: '{{ route('subjectscoresheet.grade-preview') }}'
    };

    // Ensure CSRF token is available
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = '{{ csrf_token() }}';
        document.head.appendChild(meta);
    }

    // Initialize everything on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        // Calculate sum of sub max scores
        let subMaxSum = 0;
        window.assessments.forEach(ass => {
            subMaxSum += parseFloat(ass.max_score) || 0;
        });
        window.subMaxSum = subMaxSum;

        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        let tableRows = document.querySelectorAll('#scoresheetTableBody tr[data-id]');
        const noDataAlert = document.getElementById('noDataAlert');
        const scoreCount = document.getElementById('scoreCount');

        function updateSearchAndCount() {
            const searchQuery = searchInput.value.trim().toLowerCase();
            let visibleRows = 0;

            tableRows.forEach(row => {
                const admissionNo = row.querySelector('.admissionno').dataset.admissionno.toLowerCase();
                const name = row.querySelector('.name').dataset.name.toLowerCase();

                if (searchQuery === '' || admissionNo.includes(searchQuery) || name.includes(searchQuery)) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noDataAlert) noDataAlert.style.display = visibleRows === 0 ? 'block' : 'none';
            if (scoreCount) scoreCount.textContent = visibleRows;
        }

        if (searchInput) {
            searchInput.addEventListener('input', updateSearchAndCount);
        }

        if (clearSearch) {
            clearSearch.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                tableRows.forEach(row => row.style.display = '');
                if (noDataAlert) noDataAlert.style.display = tableRows.length === 0 ? 'block' : 'none';
                if (scoreCount) scoreCount.textContent = tableRows.length;
            });
        }

        // Checkbox functionality
        const checkAll = document.getElementById('checkAll');
        const scoreCheckboxes = document.querySelectorAll('.score-checkbox');

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                scoreCheckboxes.forEach(cb => cb.checked = this.checked);
            });
        }

        scoreCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                const checkedCount = document.querySelectorAll('.score-checkbox:checked').length;
                if (checkAll) {
                    checkAll.checked = checkedCount === scoreCheckboxes.length && scoreCheckboxes.length > 0;
                    checkAll.indeterminate = checkedCount > 0 && checkedCount < scoreCheckboxes.length;
                }
            });
        });

        // Select All button
        const selectAllBtn = document.getElementById('selectAllScores');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                if (checkAll) checkAll.checked = true;
                scoreCheckboxes.forEach(cb => cb.checked = true);
            });
        }

        // Clear All button
        const clearAllBtn = document.getElementById('clearAllScores');
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function () {
                if (checkAll) checkAll.checked = false;
                scoreCheckboxes.forEach(cb => cb.checked = false);
            });
        }

        // Enhanced Dynamic total update (no clamping, just sum entered values)
        function updateRowTotal(row) {
            let sum = 0;
            row.querySelectorAll('.score-input').forEach(inp => {
                const val = parseFloat(inp.value) || 0;
                sum += val;
            });
            const totalSpan = row.querySelector('.total-display span');
            if (totalSpan) {
                totalSpan.textContent = sum.toFixed(1);
                totalSpan.dataset.total = sum;
            }
        }

        // Validation check for a single input
        function validateInput(input) {
            const max = parseFloat(input.dataset.max) || 0;
            const val = parseFloat(input.value) || 0;
            if (val > max) {
                input.classList.add('is-invalid');
                input.title = `Maximum score is ${max}. Please correct before saving.`;
                return false;
            } else {
                input.classList.remove('is-invalid');
                input.title = '';
                return true;
            }
        }

        // Validation check for a row (single save)
        function validateRow(row) {
            let isValid = true;
            row.querySelectorAll('.score-input').forEach(input => {
                if (!validateInput(input)) {
                    isValid = false;
                }
            });
            return isValid;
        }

        // Global validation check for all rows (bulk save)
        function validateAllRows() {
            let isValid = true;
            let invalidCount = 0;
            document.querySelectorAll('#scoresheetTableBody tr[data-id]').forEach(row => {
                row.querySelectorAll('.score-input').forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                        invalidCount++;
                    }
                });
            });
            return { isValid, invalidCount };
        }

        // Function to update parent score and broadsheet total for a single row
        function updateParentScoreAndTotal(rowId, rawTotal) {
            if (!window.is_sub || window.subMaxSum === 0 || window.assessment_max === 0) return;

            const normalizedScore = (rawTotal / window.subMaxSum) * window.assessment_max;

            fetch(window.routes.singleUpdate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    broadsheet_id: rowId,
                    assessment_id: window.assessment_id,
                    score: parseFloat(normalizedScore.toFixed(1)),
                    total: rawTotal,
                    is_sub: false,
                    sub_assessment_id: null,
                    term_id: window.term_id,
                    session_id: window.session_id,
                    subjectclass_id: window.subjectclass_id,
                    schoolclass_id: window.schoolclass_id,
                    staff_id: window.staff_id
                })
            }).then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to update parent score and total:', data.message);
                } else {
                    console.log('Parent score and total updated:', { normalizedScore, rawTotal });
                }
            }).catch(error => {
                console.error('Error updating parent score and total:', error);
            });
        }

        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('input', function () {
                validateInput(this);
                updateRowTotal(this.closest('tr'));
            });

            input.addEventListener('blur', function () {
                validateInput(this);
                updateRowTotal(this.closest('tr'));
            });

            // Quick save on Enter (now uses singleUpdate, with validation)
            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const row = this.closest('tr');
                    if (!validateRow(row)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Score!',
                            text: 'Please correct scores exceeding the maximum before saving.',
                        });
                        return;
                    }
                    updateRowTotal(row);
                    saveIndividualScore(this);
                }
            });
        });

        // Fixed Individual score save (updated for sub, with validation removed here as it's checked before calling)
        function saveIndividualScore(input) {
            const rowId = input.dataset.id;
            const fieldId = parseInt(input.dataset.field);
            const isSub = input.dataset.isSub === 'true';
            const score = parseFloat(input.value) || 0;
            const row = input.closest('tr');

            let assessmentId;
            if (isSub) {
                assessmentId = window.assessment_id;
                if (!assessmentId || assessmentId === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Parent assessment assessment ID not set for sub-assessment update.',
                    });
                    return;
                }
            } else {
                assessmentId = fieldId;
            }
            const subAssessmentId = isSub ? fieldId : null;

            fetch(window.routes.singleUpdate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    broadsheet_id: rowId,
                    assessment_id: assessmentId,
                    score: score,
                    is_sub: isSub,
                    sub_assessment_id: subAssessmentId,
                    term_id: window.term_id,
                    session_id: window.session_id,
                    subjectclass_id: window.subjectclass_id,
                    schoolclass_id: window.schoolclass_id,
                    staff_id: window.staff_id
                })
            }).then(response => response.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Unknown error saving score.',
                    });
                } else {
                    // Optional: Show success toast or update row total from response
                    console.log('Score saved:', data.data);

                    // If sub-assessment, update parent score and total
                    if (isSub && window.is_sub) {
                        const total = parseFloat(row.querySelector('.total-display span').dataset.total) || 0;
                        updateParentScoreAndTotal(rowId, total);
                    }
                }
            }).catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error!',
                    text: 'An error occurred while saving the score.',
                });
            });
        }

        // Bulk update scores (all rows, with validation)
        const bulkUpdateBtn = document.getElementById('bulkUpdateScores');
        if (bulkUpdateBtn) {
            bulkUpdateBtn.addEventListener('click', function () {
                const validation = validateAllRows();
                if (!validation.isValid) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Scores!',
                        text: `${validation.invalidCount} score(s) exceed the maximum. Please correct them before saving.`,
                    });
                    return;
                }

                // Update totals (no clamping)
                document.querySelectorAll('#scoresheetTableBody tr[data-id]').forEach(row => {
                    updateRowTotal(row);
                });

                const scores = [];
                document.querySelectorAll('#scoresheetTableBody tr[data-id]').forEach(row => {
                    const broadsheetId = row.dataset.id;
                    const assessments = {};
                    row.querySelectorAll('.score-input').forEach(input => {
                        assessments[input.dataset.field] = parseFloat(input.value) || 0;
                    });
                    scores.push({
                        id: broadsheetId,
                        assessments: assessments
                    });
                });

                if (scores.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Scores!',
                        text: 'No scores to update.',
                    });
                    return;
                }

                // Check for assessment_id if is_sub
                if (window.is_sub && (!window.assessment_id || window.assessment_id === 0)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Parent assessment ID not set for bulk sub-assessment update.',
                    });
                    return;
                }

                const progressContainer = document.getElementById('progressContainer');
                if (progressContainer) progressContainer.style.display = 'block';

                const progressBar = document.getElementById('saveProgressBar');
                let width = 0;
                const interval = setInterval(() => {
                    width += 10;
                    if (progressBar) progressBar.style.width = width + '%';
                    if (width >= 100) {
                        clearInterval(interval);
                    }
                }, 200);

                const commonPayload = {
                    term_id: window.term_id,
                    session_id: window.session_id,
                    subjectclass_id: window.subjectclass_id,
                    staff_id: window.staff_id,
                    schoolclass_id: window.schoolclass_id,
                    assessment_id: window.assessment_id,
                    is_sub: window.is_sub
                };

                fetch(window.routes.bulkUpdate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        ...commonPayload,
                        scores: scores
                    })
                }).then(response => response.json())
                .then(data => {
                    clearInterval(interval);
                    if (progressContainer) progressContainer.style.display = 'none';
                    if (data.success) {
                        // If sub-assessments, update parent scores and totals
                        if (window.is_sub && window.subMaxSum > 0 && window.assessment_max > 0) {
                            const parentScores = [];
                            document.querySelectorAll('#scoresheetTableBody tr[data-id]').forEach(row => {
                                const broadsheetId = parseInt(row.dataset.id);
                                const rawTotal = parseFloat(row.querySelector('.total-display span').dataset.total) || 0;
                                const normalizedScore = (rawTotal / window.subMaxSum) * window.assessment_max;
                                parentScores.push({
                                    id: broadsheetId,
                                    assessments: {
                                        [window.assessment_id]: parseFloat(normalizedScore.toFixed(1))
                                    },
                                    total: rawTotal
                                });
                            });

                            if (progressContainer) progressContainer.style.display = 'block';
                            let pWidth = 0;
                            const pInterval = setInterval(() => {
                                pWidth += 10;
                                if (progressBar) progressBar.style.width = pWidth + '%';
                                if (pWidth >= 100) {
                                    clearInterval(pInterval);
                                }
                            }, 200);

                            fetch(window.routes.bulkUpdate, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    ...commonPayload,
                                    scores: parentScores,
                                    is_sub: false
                                })
                            }).then(response => response.json())
                            .then(parentData => {
                                clearInterval(pInterval);
                                if (progressContainer) progressContainer.style.display = 'none';
                                if (parentData.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: 'Scores, parent assessment, and totals updated successfully!',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Partial Success!',
                                        text: 'Sub-scores updated, but parent assessment and total update failed: ' + (parentData.message || 'Unknown error'),
                                    });
                                }
                            }).catch(error => {
                                clearInterval(pInterval);
                                if (progressContainer) progressContainer.style.display = 'none';
                                console.error('Error updating parent scores and totals:', error);
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Partial Success!',
                                    text: 'Sub-scores updated, but failed to update parent assessment and total due to network error.',
                                });
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Scores updated successfully!',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Unknown error updating scores.',
                        });
                    }
                }).catch(error => {
                    clearInterval(interval);
                    if (progressContainer) progressContainer.style.display = 'none';
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error!',
                        text: 'An error occurred while updating scores.',
                    });
                });
            });
        }

        // Delete selected
        const deleteBtn = document.getElementById('deleteSelectedScoresBtn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                const ids = Array.from(document.querySelectorAll('.score-checkbox:checked')).map(cb => cb.dataset.id);
                if (ids.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection!',
                        text: 'Please select items to delete.',
                    });
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete the selected scores? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const progressContainer = document.getElementById('progressContainer');
                        if (progressContainer) progressContainer.style.display = 'block';

                        Promise.all(ids.map(id => 
                            fetch(window.routes.destroy.replace('__ID__', id), {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            }).then(res => res.json())
                        )).then(results => {
                            if (progressContainer) progressContainer.style.display = 'none';
                            let deletedCount = 0;
                            results.forEach((result, index) => {
                                if (result.success) {
                                    const row = document.querySelector(`tr[data-id="${ids[index]}"]`);
                                    if (row) row.remove();
                                    deletedCount++;
                                }
                            });
                            if (deletedCount > 0) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: `${deletedCount} score(s) deleted successfully.`,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                tableRows = document.querySelectorAll('#scoresheetTableBody tr[data-id]');
                                updateSearchAndCount();
                                if (tableRows.length === 0) {
                                    location.reload();
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'No Deletion!',
                                    text: 'No scores were deleted.',
                                });
                            }
                        }).catch(error => {
                            if (progressContainer) progressContainer.style.display = 'none';
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Network Error!',
                                text: 'An error occurred while deleting scores.',
                            });
                        });
                    }
                });
            });
        }

        // Import form handling with progress
        const importSubmit = document.getElementById('importSubmit');
        if (importSubmit) {
            importSubmit.addEventListener('click', function (e) {
                e.preventDefault();
                const importForm = document.getElementById('importForm');
                const fileInput = importForm.querySelector('input[type="file"]');
                if (!fileInput.files.length) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No File!',
                        text: 'Please select a file.',
                    });
                    return;
                }

                const importLoader = document.getElementById('importLoader');
                if (importLoader) importLoader.style.display = 'block';

                const uploadProgressBar = document.getElementById('uploadProgressBar');
                let width = 0;
                const interval = setInterval(() => {
                    width += 20;
                    if (uploadProgressBar) uploadProgressBar.style.width = width + '%';
                    if (width >= 100) {
                        clearInterval(interval);
                    }
                }, 300);

                setTimeout(() => {
                    importForm.submit();
                }, 1000);
            });
        }

        // Image modal handler
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('student-image')) {
                const imgSrc = e.target.dataset.image;
                const enlargedImage = document.getElementById('enlargedImage');
                if (enlargedImage) enlargedImage.src = imgSrc;
            }
        });

        // Sorting functionality
        const sortHeaders = document.querySelectorAll('th.sort');
        sortHeaders.forEach(header => {
            header.addEventListener('click', function () {
                const sortBy = this.dataset.sort;
                const tbody = document.getElementById('scoresheetTableBody');
                let rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
                const noDataRow = document.getElementById('noDataRow');
                if (noDataRow) noDataRow.style.display = 'none';

                rows.sort((a, b) => {
                    let aVal, bVal;
                    if (sortBy === 'sn') {
                        aVal = parseInt(a.querySelector('.sn').textContent) || 0;
                        bVal = parseInt(b.querySelector('.sn').textContent) || 0;
                        return aVal - bVal;
                    } else if (sortBy === 'admissionno') {
                        aVal = a.querySelector('.admissionno').textContent.trim();
                        bVal = b.querySelector('.admissionno').textContent.trim();
                        return aVal.localeCompare(bVal, undefined, { numeric: true });
                    } else if (sortBy === 'name') {
                        aVal = a.querySelector('.name').dataset.name.toLowerCase();
                        bVal = b.querySelector('.name').dataset.name.toLowerCase();
                        return aVal.localeCompare(bVal);
                    } else if (sortBy === 'total') {
                        aVal = parseFloat(a.querySelector('.total-display').dataset.total) || 0;
                        bVal = parseFloat(b.querySelector('.total-display').dataset.total) || 0;
                        return bVal - aVal; // Descending for total
                    }
                    return 0;
                });

                // Renumber SN
                rows.forEach((row, index) => {
                    row.querySelector('.sn').textContent = index + 1;
                });

                rows.forEach(row => tbody.appendChild(row));
                tableRows = rows; // Update reference
                updateSearchAndCount();
            });
        });
    });

    // Keyboard shortcut for save all
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const bulkUpdateBtn = document.getElementById('bulkUpdateScores');
            if (bulkUpdateBtn) bulkUpdateBtn.click();
        }
    });
</script>
@endsection