@extends('layouts.master')

@section('content')
<style>
/* Existing styles from the main scoresheet, adapted for subassessment */
@media (max-width: 768px) {
    .score-input {
        height: 48px; /* Increased height for better touch interaction */
        font-size: 1.1rem; /* Larger font for better readability */
        padding: 8px; /* Increased padding for touch */
        width: 80px; /* Fixed width to accommodate 3 digits (e.g., 100.0) */
        min-width: 80px; /* Ensure minimum width */
        box-sizing: border-box; /* Ensure padding is included in width */
        touch-action: manipulation; /* Improve touch interaction */
        text-align: right; /* Align numbers for better readability */
    }
    .table-responsive {
        overflow-x: auto; /* Ensure table scrolls on mobile */
    }
    .avatar-sm {
        width: 40px !important;
        height: 40px !important;
    }
    /* Adjust table cell padding for better spacing */
    td.assessment-col, td.vetted-status {
        padding: 4px !important; /* Reduced padding to fit wider inputs */
    }
    /* Ensure vetted status text is readable on mobile */
    td.vetted-status {
        font-size: 0.9rem;
    }
}

/* Vetted status background colors */
.bg-success-subtle { background-color: #d4edda !important; }
.bg-danger-subtle { background-color: #f8d7da !important; }
.bg-warning-subtle { background-color: #fff3cd !important; }

/* No dropdown needed for subassessment-specific view */
</style>

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

                                    <!-- Subassessment Info Card (since no dropdown) -->
                                    <div class="row g-2 mb-4">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="text-muted mb-2">Current Subassessment</h6>
                                                    <div class="border border-gray-300 border-dashed rounded py-3 px-4">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-clipboard-check fs-3 text-info me-2"></i>
                                                            <div>
                                                                <div class="fs-2 fw-bold text-info">{{ $subassessment->name }}</div>
                                                                <small class="text-muted">Max Score: {{ $subassessment->max_score }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted d-block mt-2">
                                                        <i class="bi bi-arrow-left-right me-1"></i>
                                                        <a href="{{ route('myresultroom.index') }}" class="text-decoration-none">Back to full assessments</a>
                                                    </small>
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
                            <div class="d-flex justify-content-between mb-3">
                                <a href="{{ route('myresultroom.index') }}" class="btn btn-primary">
                                    <i class="ri-arrow-left-line"></i> Back
                                </a>
                                <div>
                                    @if(session('subjectclass_id'))
                                        <a href="{{ route('scoresheet.download-marks-sheet') }}" class="btn btn-warning" id="downloadMarksSheet">
                                            <i class="fas fa-file-pdf"></i> Download Marks Sheet
                                        </a>
                                    @endif
                                    <a href="{{ route('subjectscoresheet.export') }}" class="btn btn-info me-2" id="downloadExcel">
                                        <i class="ri-download-line me-1"></i> Download Excel
                                    </a>
                                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importModal" {{ !session('schoolclass_id') || !session('subjectclass_id') || !session('staff_id') || !session('term_id') || !session('session_id') ? 'disabled title="Please select a class, subject, term, and session first"' : '' }}>
                                        <i class="ri-upload-line me-1"></i> Bulk Excel Upload
                                    </button>
                                    @if ($broadsheets->isNotEmpty())
                                        <button class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#scoresModal">
                                            <i class="bi bi-table me-1"></i> View Scores
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Download Progress Indicator -->
                            <div class="row mt-2" id="downloadProgressContainer" style="display: none;">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                        <span class="visually-hidden">Downloading...</span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">Downloading Excel...</h6>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="downloadProgressBar"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No Data Alert -->
                            <div class="alert alert-info text-center" id="noDataAlert" style="display: {{ $broadsheets->isEmpty() ? 'block' : 'none' }};">
                                <i class="ri-information-line me-2"></i>
                                No scores available for the selected subassessment. Please check your filters or import scores.
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
                                            <!-- Single Subassessment Column -->
                                            <th>{{ $subassessment->name }}<br><small>({{ $subassessment->max_score }})</small></th>
                                            <th>Total</th>
                                            <th>BF</th>
                                            <th>Cum</th>
                                            <th>Grade</th>
                                            <th>Position</th>
                                            <th>Vetted Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scoresheetTableBody" class="list form-check-all">
                                        @php $i = 0; @endphp
                                        @forelse ($broadsheets as $broadsheet)
                                            <tr class="{{ $broadsheet->vettedstatus === '1' ? 'bg-success-subtle' : ($broadsheet->vettedstatus === '0' ? 'bg-danger-subtle' : 'bg-warning-subtle') }}"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top"
                                                title="{{ $broadsheet->vettedstatus === '1' ? 'Scores vetted' : ($broadsheet->vettedstatus === '0' ? 'Scores not vetted' : 'Scores not vetted yet') }}">
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
                                                @php
                                                    $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $subassessment->id)->first();
                                                    $scoreValue = $scoreObj ? $scoreObj->score : '';
                                                @endphp
                                                <td class="assessment-col">
                                                    <input type="number" class="form-control score-input" data-field="{{ $subassessment->id }}" data-max="{{ $subassessment->max_score }}" data-id="{{ $broadsheet->id }}" value="{{ $scoreValue }}" min="0" max="{{ $subassessment->max_score }}" step="0.1" placeholder="">
                                                </td>
                                                <td class="total-display text-center">
                                                    <span class="badge bg-primary">{{ $broadsheet->total ? number_format($broadsheet->total, 1) : '0.0' }}</span>
                                                </td>
                                                <td class="bf-display text-center">
                                                    <span class="badge bg-secondary">{{ $broadsheet->bf ? number_format($broadsheet->bf, 2) : '0.00' }}</span>
                                                </td>
                                                <td class="cum-display text-center">
                                                    <span class="badge bg-info">{{ $broadsheet->cum ? number_format($broadsheet->cum, 2) : '0.00' }}</span>
                                                </td>
                                                <td class="grade-display text-center">
                                                    <span class="badge bg-secondary">{{ $broadsheet->grade ?? '-' }}</span>
                                                </td>
                                                <td class="position-display text-center">
                                                    <span class="badge bg-info">{{ $broadsheet->position ? $broadsheet->position . \App\Helpers\OrdinalHelper::getOrdinalSuffix($broadsheet->position) : '-' }}</span>
                                                </td>
                                                <td class="vetted-status text-center">
                                                    <span class="badge {{ $broadsheet->vettedstatus === '1' ? 'bg-success' : ($broadsheet->vettedstatus === '0' ? 'bg-danger' : 'bg-warning') }}">
                                                        {{ $broadsheet->vettedstatus === '1' ? 'Scores vetted' : ($broadsheet->vettedstatus === '0' ? 'Scores not vetted' : 'Scores not vetted yet') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="noDataRow">
                                                <td colspan="12" class="text-center">No scores available.</td>
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
                                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteSelectedScores()">
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
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
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

            <!-- Import Modal (adjusted for subassessment if needed) -->
            <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="fw-bold">Bulk Upload Scores for {{ $subassessment->name }}</h2>
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
                                <input type="hidden" name="subassessment_id" value="{{ $subassessment->id }}">
                                <div class="form-group mb-6">
                                    <label class="required fw-semibold fs-6 mb-2">Excel File (Column for {{ $subassessment->name }})</label>
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

            <!-- Scores Modal (adjusted for single subassessment) -->
            <div class="modal fade" id="scoresModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="fw-bold">Scores Overview for {{ $subassessment->name }}</h2>
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
                                            <!-- Single Subassessment Column -->
                                            <th>{{ $subassessment->name }}</th>
                                            <th>Total</th>
                                            <th>BF</th>
                                            <th>Cum</th>
                                            <th>Grade</th>
                                            <th>Position</th>
                                            <th>Vetted Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $i = 0; @endphp
                                        @forelse ($broadsheets as $broadsheet)
                                            <tr class="{{ $broadsheet->vettedstatus === '1' ? 'bg-success-subtle' : ($broadsheet->vettedstatus === '0' ? 'bg-danger-subtle' : 'bg-warning-subtle') }}"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top"
                                                title="{{ $broadsheet->vettedstatus === '1' ? 'Scores vetted' : ($broadsheet->vettedstatus === '0' ? 'Scores not vetted' : 'Scores not vetted yet') }}">
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
                                                @php
                                                    $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $subassessment->id)->first();
                                                @endphp
                                                <td>{{ $scoreObj ? number_format($scoreObj->score, 1) : '0.0' }}</td>
                                                <td>{{ $broadsheet->total ? number_format($broadsheet->total, 1) : '0.0' }}</td>
                                                <td>{{ $broadsheet->bf ? number_format($broadsheet->bf, 2) : '0.00' }}</td>
                                                <td>{{ $broadsheet->cum ? number_format($broadsheet->cum, 2) : '0.00' }}</td>
                                                <td>{{ $broadsheet->grade ?? '-' }}</td>
                                                <td>
                                                    {{ $broadsheet->position ? $broadsheet->position . \App\Helpers\OrdinalHelper::getOrdinalSuffix($broadsheet->position) : '-' }}
                                                </td>
                                                <td class="vetted-status text-center">
                                                    <span class="badge {{ $broadsheet->vettedstatus === '1' ? 'bg-success' : ($broadsheet->vettedstatus === '0' ? 'bg-danger' : 'bg-warning') }}">
                                                        {{ $broadsheet->vettedstatus === '1' ? 'Scores vetted' : ($broadsheet->vettedstatus === '0' ? 'Scores not vetted' : 'Scores not vetted yet') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center">No scores available.</td>
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
    // Initialize Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Existing search functionality
        const searchInput = document.querySelector('#searchInput');
        const clearSearch = document.querySelector('#clearSearch');
        const tableRows = document.querySelectorAll('#scoresheetTableBody tr:not(#noDataRow)');
        const noDataAlert = document.querySelector('#noDataAlert');
        const scoreCount = document.querySelector('#scoreCount');

        searchInput.addEventListener('input', function () {
            const searchQuery = this.value.trim().toLowerCase();
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

            noDataAlert.style.display = visibleRows === 0 ? 'block' : 'none';
            if (scoreCount) {
                scoreCount.textContent = visibleRows;
            }
        });

        clearSearch.addEventListener('click', function () {
            searchInput.value = '';
            tableRows.forEach(row => row.style.display = '');
            noDataAlert.style.display = tableRows.length === 0 ? 'block' : 'none';
            if (scoreCount) {
                scoreCount.textContent = tableRows.length;
            }
        });
    });

    console.log('Raw broadsheets before normalization:', @json($broadsheets));
    window.broadsheets = @json($broadsheets);
    window.assessments = @json([$subassessment]);  // Single subassessment
    window.term_id = {{ session('term_id') }};
    window.session_id = {{ session('session_id') }};
    window.subjectclass_id = {{ session('subjectclass_id') }};
    window.schoolclass_id = {{ session('schoolclass_id') }};
    window.staff_id = {{ session('staff_id') }};
    window.subassessment_id = {{ $subassessment->id }};
    window.is_senior = {{ $is_senior ? 'true' : 'false' }};
    window.routes = {
        results: '{{ route('subjectscoresheet.results') }}',
        bulkUpdate: '{{ route('subjectscoresheet.bulk-update') }}',
        destroy: '{{ route('subjectscoresheet.destroy', ['id' => '__ID__']) }}',
        import: '{{ route('subjectscoresheet.import') }}',
        export: '{{ route('subjectscoresheet.export') }}',
        downloadMarksSheet: '{{ route('scoresheet.download-marks-sheet') }}',
        gradePreview: '{{ route('subjectscoresheet.grade-preview') }}'
    };

    // Update JS for single subassessment
    // In bulkUpdateScores JS (not shown, but update to collect data-field as assessment_id: value)
    // Example:
    // const scores = [];
    // tableRows.forEach(row => {
    //     if (row.querySelector('input[type="checkbox"]').checked) {
    //         const rowData = { id: row.querySelector('[data-id]').dataset.id, assessments: {} };
    //         row.querySelectorAll('.score-input').forEach(input => {
    //             rowData.assessments[input.dataset.field] = parseFloat(input.value) || 0;
    //         });
    //         scores.push(rowData);
    //     }
    // });
</script>
{{-- 
<script src="{{ asset('js/subjectscoresheet.init.js') }}"></script> 
--}}
@endsection