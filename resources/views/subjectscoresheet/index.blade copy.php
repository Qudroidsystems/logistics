@extends('layouts.master')

@section('content')
<style>
/* Existing styles */
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
    .table-responsive {
        overflow-x: auto;
    }
    .avatar-sm {
        width: 40px !important;
        height: 40px !important;
    }
    td.assessment-col, td.vetted-status {
        padding: 4px !important;
    }
    td.vetted-status {
        font-size: 0.9rem;
    }
}

/* Vetted status background colors */
.bg-success-subtle { background-color: #d4edda !important; }
.bg-danger-subtle { background-color: #f8d7da !important; }
.bg-warning-subtle { background-color: #fff3cd !important; }

/* Assessment dropdown styles */
.assessment-dropdown .dropdown-menu {
    max-height: 200px;
    overflow-y: auto;
}
.assessment-dropdown .dropdown-item {
    white-space: nowrap;
}

/* Invalid input styling */
.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}
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

                               
                                    <!-- Assessment Buttons Section -->
                                    @if($assessments->isNotEmpty())
                                        <div class="row g-2 mb-4">
                                            <div class="col-12">
                                                <h6 class="text-muted mb-2">Assessments</h6>
                                            </div>
                                            @foreach($assessments as $assessment)
                                                <div class="col-md-3 col-sm-6">
                                                    <a href="{{ route('assessment.scoresheet', [
                                                        'schoolclassid' => session('schoolclass_id'),
                                                        'subjectclassid' => session('subjectclass_id'),
                                                        'staffid' => session('staff_id'),
                                                        'termid' => session('term_id'),
                                                        'sessionid' => session('session_id'),
                                                        'assessmentid' => $assessment->id
                                                    ]) }}" class="btn btn-outline-info w-100 text-start">
                                                        <i class="bi bi-clipboard-check me-1"></i>
                                                        {{ $assessment->name }}<br>
                                                        <small class="text-muted">({{ $assessment->max_score }})</small>
                                                        @if($assessment->subAssessments->isNotEmpty())
                                                            <small class="d-block text-muted mt-1">
                                                                <i class="bi bi-chevron-right"></i> {{ $assessment->subAssessments->count() }} sub-assessments
                                                            </small>
                                                        @else
                                                            <small class="d-block text-muted mt-1">
                                                                <i class="bi bi-info-circle"></i> No sub-assessments
                                                            </small>
                                                        @endif
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    No assessments defined for this class.
                                                </div>
                                            </div>
                                        </div>
                                    @endif
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
                                {{-- <div>
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
                                </div> --}}
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
                                No scores available for the selected subject. Please check your filters or import scores.
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
                                            @forelse ($assessments as $assessment)
                                                <th>{{ $assessment->name }}<br><small>({{ $assessment->max_score }})</small></th>
                                            @empty
                                                <th colspan="4">No Assessments Defined</th>
                                            @endforelse
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
                                            @php
                                                $initialTotal = 0;
                                                foreach ($assessments as $assessment) {
                                                    $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                                                    $initialTotal += $scoreObj ? $scoreObj->score : 0;
                                                }
                                            @endphp
                                            <tr class="{{ $broadsheet->vettedstatus === '1' ? 'bg-success-subtle' : ($broadsheet->vettedstatus === '0' ? 'bg-danger-subtle' : 'bg-warning-subtle') }}"
                                                data-id="{{ $broadsheet->id }}"
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
                                                            <img src="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" alt="{{ ($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '') }}" class="rounded-circle w-100 student-image" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" data-picture="{{ $broadsheet->picture ?? 'none' }}" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold">{{ $broadsheet->lname ?? '' }}</span> {{ $broadsheet->fname ?? '' }} {{ $broadsheet->mname ?? '' }}
                                                        </div>
                                                    </div>
                                                </td>
                                                @forelse ($assessments as $assessment)
                                                    @php
                                                        $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                                                        $scoreValue = $scoreObj ? $scoreObj->score : '';
                                                    @endphp
                                                    <td class="assessment-col">
                                                        <input type="number" class="form-control score-input" data-field="{{ $assessment->id }}" data-max="{{ $assessment->max_score }}" data-id="{{ $broadsheet->id }}" data-original="{{ $scoreValue }}" value="{{ $scoreValue }}" min="0" max="{{ $assessment->max_score }}" step="0.1" placeholder="">
                                                    </td>
                                                @empty
                                                    <td colspan="4">-</td>
                                                @endforelse
                                                <td class="total-display text-center">
                                                    <span class="badge bg-primary" data-total="{{ $initialTotal }}">{{ number_format($initialTotal, 1) }}</span>
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
                                                <td colspan="{{ 4 + ($assessments->count() > 0 ? $assessments->count() : 4) + 7 }}" class="text-center">No scores available.</td>
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
                            <h2 class="fw-bold">Bulk Upload Scores</h2>
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
                                <div class="form-group mb-6">
                                    <label class="required fw-semibold fs-6 mb-2">Excel File</label>
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
                            <h2 class="fw-bold">Scores Overview</h2>
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
                                            @forelse ($assessments as $assessment)
                                                <th>{{ $assessment->name }}</th>
                                            @empty
                                                <th colspan="4">No Assessments</th>
                                            @endforelse
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
                                            @php
                                                $modalTotal = 0;
                                                foreach ($assessments as $assessment) {
                                                    $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                                                    $modalTotal += $scoreObj ? $scoreObj->score : 0;
                                                }
                                            @endphp
                                            <tr class="{{ $broadsheet->vettedstatus === '1' ? 'bg-success-subtle' : ($broadsheet->vettedstatus === '0' ? 'bg-danger-subtle' : 'bg-warning-subtle') }}"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top"
                                                title="{{ $broadsheet->vettedstatus === '1' ? 'Scores vetted' : ($broadsheet->vettedstatus === '0' ? 'Scores not vetted' : 'Scores not vetted yet') }}">
                                                <td>{{ ++$i }}</td>
                                                <td class="admissionno">{{ $broadsheet->admissionno ?? '-' }}</td>
                                                <td class="name">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2">
                                                            <img src="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" alt="{{ ($broadsheet->lname ?? '') . ' ' . ($broadsheet->fname ?? '') . ' ' . ($broadsheet->mname ?? '') }}" class="rounded-circle w-100 student-image" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="{{ $broadsheet->picture ? asset('storage/student_avatars/' . basename($broadsheet->picture)) : asset('storage/student_avatars/unnamed.jpg') }}" data-picture="{{ $broadsheet->picture ?? 'none' }}" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold">{{ $broadsheet->lname ?? '' }}</span> {{ $broadsheet->fname ?? '' }} {{ $broadsheet->mname ?? '' }}
                                                        </div>
                                                    </div>
                                                </td>
                                                @forelse ($assessments as $assessment)
                                                    @php
                                                        $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                                                    @endphp
                                                    <td>{{ $scoreObj ? number_format($scoreObj->score, 1) : '0.0' }}</td>
                                                @empty
                                                    <td colspan="4">-</td>
                                                @endforelse
                                                <td>{{ number_format($modalTotal, 1) }}</td>
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
                                                <td colspan="{{ 3 + ($assessments->count() > 0 ? $assessments->count() : 4) + 6 }}" class="text-center">No scores available.</td>
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
                            <img id="enlargedImage" src="" alt="Student Image" class="img-fluid" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>


<script>
    // Ensure CSRF token is available
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = '{{ csrf_token() }}';
        document.head.appendChild(meta);
    }

    // Global variables
    console.log('Raw broadsheets before normalization:', @json($broadsheets));
    window.broadsheets = @json($broadsheets);
    window.assessments = @json($assessments);
    window.term_id = {{ session('term_id') }};
    window.session_id = {{ session('session_id') }};
    window.subjectclass_id = {{ session('subjectclass_id') }};
    window.schoolclass_id = {{ session('schoolclass_id') }};
    window.staff_id = {{ session('staff_id') }};
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

    // Initialize everything on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
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

        // Dynamic total update: sum of all assessment scores (raw)
        function updateRowTotal(row) {
            let sum = 0;
            row.querySelectorAll('.score-input').forEach(inp => {
                const val = parseFloat(inp.value) || 0;
                sum += val;
            });
            const totalSpan = row.querySelector('.total-display span');
            if (totalSpan) {
                totalSpan.textContent = number_format(sum, 1);
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

        // Validation check for a row
        function validateRow(row) {
            let isValid = true;
            row.querySelectorAll('.score-input').forEach(input => {
                if (!validateInput(input)) {
                    isValid = false;
                }
            });
            return isValid;
        }

        // Global validation check for all rows
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

        // Event listeners for score inputs
        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('input', function () {
                validateInput(this);
                updateRowTotal(this.closest('tr'));
            });

            input.addEventListener('blur', function () {
                validateInput(this);
                updateRowTotal(this.closest('tr'));
                // Check if value changed and save if so
                const original = parseFloat(this.dataset.original) || 0;
                const current = parseFloat(this.value) || 0;
                if (Math.abs(current - original) > 0.01) { // Tolerance for floating point
                    saveIndividualScore(this);
                    this.dataset.original = this.value;
                }
            });

            // Quick save on Enter
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

        // Fixed Individual score save (sends current row total)
        function saveIndividualScore(input) {
            const rowId = input.dataset.id;
            const fieldId = parseInt(input.dataset.field);
            const score = parseFloat(input.value) || 0;
            const row = input.closest('tr');
            
            // Get current total from row
            const totalSpan = row.querySelector('.total-display span');
            const currentTotal = totalSpan ? parseFloat(totalSpan.dataset.total) || 0 : 0;

            fetch(window.routes.singleUpdate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    broadsheet_id: rowId,
                    assessment_id: fieldId,
                    score: score,
                    is_sub: false,
                    sub_assessment_id: null,
                    raw_total: currentTotal, // Send the calculated total
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
                    console.log('Score saved:', data.data);
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

        // Bulk update scores
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

                // Update all totals before saving
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
                    
                    // Get row total
                    const totalSpan = row.querySelector('.total-display span');
                    const rowTotal = totalSpan ? parseFloat(totalSpan.dataset.total) || 0 : 0;
                    
                    scores.push({
                        id: broadsheetId,
                        assessments: assessments,
                        raw_total: rowTotal // Send the calculated total
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

                fetch(window.routes.bulkUpdate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        scores: scores,
                        term_id: window.term_id,
                        session_id: window.session_id,
                        subjectclass_id: window.subjectclass_id,
                        staff_id: window.staff_id,
                        schoolclass_id: window.schoolclass_id,
                        is_sub: false
                    })
                }).then(response => response.json())
                .then(data => {
                    clearInterval(interval);
                    if (progressContainer) progressContainer.style.display = 'none';
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Scores updated successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
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

        // Import form handling
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

    // Helper function for number formatting (since PHP number_format isn't in JS)
    function number_format(number, decimals) {
        return parseFloat(number).toFixed(decimals || 1);
    }

    // Keyboard shortcut for save all
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const bulkUpdateBtn = document.getElementById('bulkUpdateScores');
            if (bulkUpdateBtn) bulkUpdateBtn.click();
        }
    });

    // Include SweetAlert2 if not already in layout
    if (typeof Swal === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        document.head.appendChild(script);
    }
</script>
@endsection


<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Models\SubAssessment;
use App\Models\BroadsheetsMock;
use App\Models\PromotionStatus;
use App\Models\BroadsheetRecord;
use App\Exports\MarksSheetExport;
use App\Imports\ScoresheetImport;
use App\Exports\RecordsheetExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BroadsheetRecordMock;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MockMarksSheetExport;
use App\Exports\MockRecordsheetExport;
use Illuminate\Support\Facades\Storage;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetSubAssessmentScore;

class MyScoreSheetController extends Controller
{
    
      public function index(Request $request)
    {
        $pagetitle = 'My Scoresheets';
        $broadsheets = collect();

        Log::info('Index session:', $request->session()->all());

        if (!$request->ajax()) {
            $termId = $request->query('termid', 'ALL');
            $sessionId = $request->query('sessionid', 'ALL');

            if ($termId !== 'ALL' && $sessionId !== 'ALL') {
                $broadsheets = $this->getBroadsheets($request->user()->id, $termId, $sessionId);
                Log::info('Index broadsheets count:', ['count' => $broadsheets->count()]);
            }
        }

        if ($request->ajax()) {
            $termId = $request->input('termid', 'ALL');
            $sessionId = $request->input('sessionid', 'ALL');

            if ($termId === 'ALL' || $sessionId === 'ALL') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select both term and session.',
                ], 422);
            }

            $broadsheets = $this->getBroadsheets($request->user()->id, $termId, $sessionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'broadsheets' => $broadsheets,
                ],
            ]);
        }

        return view('subjectscoresheet.index', compact('pagetitle', 'broadsheets'));
    }

    public function subjectscoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        Log::info('Subjectscoresheet parameters:', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid'));

        session([
            'schoolclass_id' => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
        ]);

        // Initial broadsheets fetch to check data
        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
        $assessments = collect(); // Default empty

        if ($broadsheets->isNotEmpty() && $schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->orderBy('id') // or add 'order' field if exists
                ->get();

            // Update metrics and positions
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);

            // Compute and update totals, cums, grades, remarks dynamically
            $this->computeDynamicTotals($broadsheets, $assessments, $schoolclass, $termid, $sessionid);

            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

            // Refresh broadsheets to ensure updated positions
            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

            Log::info('Broadsheets after position update:', $broadsheets->map(function ($b) {
                return [
                    'id' => $b->id,
                    'student_id' => $b->student_id,
                    'admissionno' => $b->admissionno,
                    'cum' => $b->cum,
                    'subject_position_class' => $b->position,
                ];
            })->toArray());

            $pagetitle = sprintf(
                'Scoresheet for %s (%s) - %s %s - %s %s',
                $broadsheets->first()->subject,
                $broadsheets->first()->subject_code,
                $broadsheets->first()->schoolclass,
                $broadsheets->first()->arm,
                $broadsheets->first()->term,
                $broadsheets->first()->session
            );
        } else {
            $pagetitle = 'Subject Scoresheet';
            Log::warning('No broadsheets found for the given parameters', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid'));
        }

        $is_senior = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first()->is_senior ?? false : false;

        return view('subjectscoresheet.index', compact('broadsheets', 'pagetitle', 'is_senior', 'assessments'));
    }

    /**
     * Handle subassessment scoresheet view.
     */
    public function subassessmentScoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid, $subassessmentid)
    {
        Log::info('SubassessmentScoresheet parameters:', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid', 'subassessmentid'));

        session([
            'schoolclass_id' => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subassessment_id' => $subassessmentid,  // For use in views/other methods
        ]);

        // Validate and fetch subassessment
        $subassessment = SubAssessment::findOrFail($subassessmentid);  // Adjust model name if different
        $assessment = $subassessment->assessment;  // Assuming belongsTo relation

        // Fetch broadsheets (load all subAssessmentScores, without scoping for compute)
        $broadsheets = $this->getSubassessmentBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid, $subassessmentid);

        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
        $assessments = collect([$subassessment]);  // Focus on this subassessment; expand if needed for context

        $allAssessments = collect(); // For compute

        if ($broadsheets->isNotEmpty() && $schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            // Fetch full assessments for compute
            $allAssessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')  // Assuming hasMany relation
                ->orderBy('id')
                ->get();

            // Optionally fetch parent assessment or related subassessments for full context
            $fullAssessments = $allAssessments;
            $assessments = $fullAssessments->flatMap(function ($a) {
                return $a->subAssessments;
            })->where('id', $subassessmentid);  // Or adjust as needed

            // Update metrics, totals, positions (similar to subjectscoresheet)
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->computeDynamicTotals($broadsheets, $allAssessments, $schoolclass, $termid, $sessionid);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

            // Refresh broadsheets
            $broadsheets = $this->getSubassessmentBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid, $subassessmentid);
        } else {
            $pagetitle = 'Subassessment Scoresheet';
            Log::warning('No broadsheets found for subassessment', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid', 'subassessmentid'));
        }

        $pagetitle = sprintf(
            'Scoresheet for %s (%s) - %s %s - %s %s',
            $subassessment->name,
            $subassessment->max_score ?? 'N/A',
            $broadsheets->first()?->schoolclass ?? 'Class',
            $broadsheets->first()?->arm ?? '',
            $broadsheets->first()?->term ?? '',
            $broadsheets->first()?->session ?? ''
        );

        $is_senior = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first()->is_senior ?? false : false;

        return view('subjectscoresheet.subassessment-index', compact('broadsheets', 'pagetitle', 'is_senior', 'assessments', 'subassessment'));
    }

   
    /**
     * Handle assessment-specific scoresheet view (shows all sub-assessments under the assessment).
     */
    public function assessmentScoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid, $assessmentid)
    {
        Log::info('AssessmentScoresheet parameters:', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid', 'assessmentid'));

        session([
            'schoolclass_id' => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'assessment_id' => $assessmentid,  // For use in views/other methods
        ]);

        // Validate and fetch assessment
        $assessment = Assessment::with('subAssessments')->findOrFail($assessmentid);

        // Fetch broadsheets (eager loads all assessmentScores and subAssessmentScores)
        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);

        // Determine subAssessments with is_sub_item flag
        $realSubAssessments = $assessment->subAssessments;
        $is_sub_view = $realSubAssessments->isNotEmpty();
        if (!$is_sub_view) {
            // Treat as direct assessment
            $subAssessments = collect([$assessment]);
            $subAssessments->each(function ($sa) {
                $sa->is_sub_item = false;
            });
        } else {
            // Use real sub-assessments
            $subAssessments = $realSubAssessments;
            $subAssessments->each(function ($sa) {
                $sa->is_sub_item = true;
            });
        }

        $allAssessments = collect(); // For compute

        if ($broadsheets->isNotEmpty() && $schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            // Fetch all assessments for full context (totals computed over all)
            $allAssessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->orderBy('id')
                ->get();

            // Update metrics and positions (over all assessments)
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);

            // Compute and update totals, cums, grades, remarks dynamically (using all assessments)
            $this->computeDynamicTotals($broadsheets, $allAssessments, $schoolclass, $termid, $sessionid);

            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

            // Refresh broadsheets
            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
        } else {
            Log::warning('No broadsheets found for assessment', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid', 'assessmentid'));
        }

        $pagetitle = sprintf(
            'Scoresheet for %s (%s) - %s %s - %s %s',
            $assessment->name,
            $assessment->max_score,
            $broadsheets->first()?->schoolclass ?? 'Class',
            $broadsheets->first()?->arm ?? '',
            $broadsheets->first()?->term ?? '',
            $broadsheets->first()?->session ?? ''
        );

        $is_senior = $schoolclass && $schoolclass->classcategories->isNotEmpty() ? $schoolclass->classcategories->first()->is_senior ?? false : false;

        return view('subjectscoresheet.assessment-index', compact('broadsheets', 'pagetitle', 'is_senior', 'subAssessments', 'assessment', 'is_sub_view'));
    }

    /**
     * Compute dynamic totals, cums, grades, and remarks based on assessment scores.
     * Always uses the parent assessment score as authoritative.
     * Cum is calculated as (total + bf) / 2 for terms after term 1, or just total for term 1.
     */
    private function computeDynamicTotals($broadsheets, $assessments, $schoolclass, $termId, $sessionId)
    {
        $totalMax = $assessments->sum('max_score');

        foreach ($broadsheets as $broadsheet) {
            $assessmentScores = $broadsheet->assessmentScores ?? collect();
            $subAssessmentScores = $broadsheet->subAssessmentScores ?? collect(); // Added for sub scores

            $totalRaw = 0;

            foreach ($assessments as $assessment) {
                // Always use parent assessment score
                $scoreObj = $assessmentScores->where('assessment_id', $assessment->id)->first();
                $assessmentScore = $scoreObj ? $scoreObj->score : 0;

                $totalRaw += $assessmentScore;
            }

            // Get BF (brought forward from previous term)
            $newBf = $this->getPreviousTermCum(
                $broadsheet->student_id,
                $broadsheet->subject_id,
                $termId,
                $sessionId
            );

            // Calculate cum as (total + bf) / 2, or just total for term 1
            $newCum = $termId == 1 ? round($totalRaw, 2) : round(($totalRaw + $newBf) / 2, 2);

            $newGrade = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? $schoolclass->classcategories->first()->calculateGrade($newCum)
                : $this->getDefaultGrade($newCum);

            $newRemark = $this->getRemark($newGrade);

            $significantChange = abs($broadsheet->total - $totalRaw) > 0.01 ||
                                abs($broadsheet->bf - $newBf) > 0.01 ||
                                abs($broadsheet->cum - $newCum) > 0.01 ||
                                $broadsheet->grade !== $newGrade ||
                                $broadsheet->remark !== $newRemark;

            if ($significantChange) {
                Log::info("computeDynamicTotals: Updating broadsheet {$broadsheet->id} due to significant changes", [
                    'old_values' => [
                        'total' => $broadsheet->total,
                        'bf' => $broadsheet->bf,
                        'cum' => $broadsheet->cum,
                        'grade' => $broadsheet->grade,
                        'remark' => $broadsheet->remark,
                    ],
                    'new_values' => [
                        'total' => $totalRaw,
                        'bf' => $newBf,
                        'cum' => $newCum,
                        'grade' => $newGrade,
                        'remark' => $newRemark,
                    ],
                ]);

                $broadsheet->total = $totalRaw;
                $broadsheet->bf = $newBf;
                $broadsheet->cum = $newCum;
                $broadsheet->grade = $newGrade;
                $broadsheet->remark = $newRemark;
                $broadsheet->save();
            }
        }
    }

    protected function getBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = Broadsheets::query()
            ->where('broadsheets.staff_id', $staffId)
            ->where('broadsheets.term_id', $termId)
            ->with(['assessmentScores', 'subAssessmentScores']) // Updated to load both
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->join('subjectclass', function ($join) use ($subjectClassId) {
                $join->on('subjectclass.id', '=', 'broadsheets.subjectclass_id')
                    ->on('broadsheet_records.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records.schoolclass_id', '=', 'subjectclass.schoolclassid');
                if ($subjectClassId) {
                    $join->where('subjectclass.id', $subjectClassId);
                }
            })
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheet_records.session_id', $sessionId);

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        // Log the raw SQL query for debugging
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        Log::debug('getBroadsheets: Raw SQL query', [
            'sql' => $sql,
            'bindings' => $bindings,
        ]);

        $results = $query->get([
            'broadsheets.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records.subject_id',
            'schoolclass.schoolclass',
            'schoolclass.id as schoolclass_id',
            'schoolarm.arm',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheets.staff_id',
            'broadsheets.term_id',
            'broadsheet_records.session_id as sessionid',
            // Remove fixed CA and exam fields
            'studentpicture.picture',
            'broadsheets.total',
            'broadsheets.bf',
            'broadsheets.cum',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position',
            'broadsheets.remark',
            'broadsheets.vettedstatus', // Added vettedstatus
        ])->sortBy('lastname');

        // No longer compute fixed CA totals here; done dynamically in subjectscoresheet

        Log::debug('getBroadsheets: Retrieved broadsheets', [
            'staff_id' => $staffId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'schoolclass_id' => $schoolClassId,
            'subjectclass_id' => $subjectClassId,
            'result_count' => $results->count(),
            'students' => $results->map(function ($item) {
                return [
                    'admissionno' => $item->admissionno,
                    'student_id' => $item->student_id,
                    'subject' => $item->subject,
                    'subject_id' => $item->subject_id,
                    'subjectclass_id' => $item->subjectclid,
                    'position' => $item->position,
                    'vettedstatus' => $item->vettedstatus, // Added to log
                ];
            })->toArray(),
            'subjects' => $results->pluck('subject')->unique()->values()->toArray(),
        ]);

        return $results;
    }

    /**
     * Fetch broadsheets scoped to a specific subassessment.
     */
    protected function getSubassessmentBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null, $subassessmentId)
    {
        $query = Broadsheets::query()
            ->where('broadsheets.staff_id', $staffId)
            ->where('broadsheets.term_id', $termId)
            ->with(['subAssessmentScores']) // Load all for compute, filter in view if needed
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->join('subjectclass', function ($join) use ($subjectClassId) {
                $join->on('subjectclass.id', '=', 'broadsheets.subjectclass_id')
                    ->on('broadsheet_records.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records.schoolclass_id', '=', 'subjectclass.schoolclassid');
                if ($subjectClassId) {
                    $join->where('subjectclass.id', $subjectClassId);
                }
            })
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheet_records.session_id', $sessionId);

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        // Select fields (reuse from your getBroadsheets method)
        $results = $query->get([
            'broadsheets.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records.subject_id',
            'schoolclass.schoolclass',
            'schoolclass.id as schoolclass_id',
            'schoolarm.arm',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheets.staff_id',
            'broadsheets.term_id',
            'broadsheet_records.session_id as sessionid',
            'studentpicture.picture',
            'broadsheets.total',
            'broadsheets.bf',
            'broadsheets.cum',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position',
            'broadsheets.remark',
            'broadsheets.vettedstatus',
        ])->sortBy('lastname');

        Log::debug('getSubassessmentBroadsheets: Retrieved', [
            'count' => $results->count(),
            'subassessment_id' => $subassessmentId,
        ]);

        return $results;
    }

    
   
    /**
     * Handle single assessment score update (updated for sub-assessments with normalization).
     */
    public function singleUpdateScore(Request $request)
    {
        $validated = $request->validate([
            'broadsheet_id' => 'required|exists:broadsheets,id',
            'assessment_id' => 'required|exists:assessments,id', // Parent for sub
            'score' => 'required|numeric|min:0',
            'is_sub' => 'boolean',
            'sub_assessment_id' => 'nullable|exists:sub_assessments,id', // For single sub update
            'total' => 'nullable|numeric', // Optional raw total from JS
            'raw_total' => 'nullable|numeric', // Explicit raw total for direct saving
        ]);

        $broadsheetId = $validated['broadsheet_id'];
        $assessmentId = $validated['assessment_id'];
        $score = $validated['score'];
        $isSub = $validated['is_sub'] ?? false;
        $subAssessmentId = $validated['sub_assessment_id'] ?? null;
        $rawTotal = $validated['raw_total'] ?? $validated['total'] ?? null; // From JS if provided

        if ($isSub && !$subAssessmentId) {
            return response()->json([
                'success' => false,
                'message' => 'Sub-assessment ID required for sub-assessment updates.',
            ], 422);
        }

        // Fetch broadsheet and related data
        $broadsheet = Broadsheets::findOrFail($broadsheetId);
        $model = $isSub ? SubAssessment::findOrFail($subAssessmentId) : Assessment::findOrFail($assessmentId);

        if ($score > $model->max_score) {
            return response()->json([
                'success' => false,
                'message' => "Score cannot exceed maximum of {$model->max_score}.",
            ], 422);
        }

        $broadsheetRecord = BroadsheetRecord::find($broadsheet->broadSheet_record_id);
        $schoolclassId = $broadsheetRecord->schoolclass_id ?? 0;
        $termId = $broadsheet->term_id;
        $sessionId = $broadsheetRecord->session_id;

        DB::transaction(function () use ($broadsheetId, $assessmentId, $score, $broadsheet, $isSub, $subAssessmentId, $broadsheetRecord) {
            // Update or create the assessment score
            if ($isSub) {
                BroadsheetSubAssessmentScore::updateOrCreate(
                    [
                        'broadsheet_id' => $broadsheetId,
                        'sub_assessment_id' => $subAssessmentId,
                        'assessment_id' => $assessmentId, // Link to parent
                    ],
                    ['score' => $score]
                );

                // Compute normalized parent score
                $assessment = Assessment::with('subAssessments')->find($assessmentId);
                if ($assessment) {
                    $subMaxSum = $assessment->subAssessments->sum('max_score');
                    $subTotal = BroadsheetSubAssessmentScore::where('broadsheet_id', $broadsheetId)
                        ->where('assessment_id', $assessmentId)
                        ->sum('score');
                    $normalized = $subMaxSum > 0 ? ($subTotal / $subMaxSum) * $assessment->max_score : 0;
                    $clampedNormalized = max(0, min($normalized, $assessment->max_score));

                    BroadsheetAssessmentScore::updateOrCreate(
                        [
                            'broadsheet_id' => $broadsheetId,
                            'assessment_id' => $assessmentId,
                        ],
                        ['score' => $clampedNormalized]
                    );

                    Log::info('Normalized parent score updated for single sub save', [
                        'broadsheet_id' => $broadsheetId,
                        'assessment_id' => $assessmentId,
                        'sub_assessment_id' => $subAssessmentId,
                        'sub_total' => $subTotal,
                        'sub_max_sum' => $subMaxSum,
                        'normalized_score' => $clampedNormalized,
                        'raw_total_from_js' => $rawTotal ?? 'not provided',
                    ]);
                }
            } else {
                BroadsheetAssessmentScore::updateOrCreate(
                    [
                        'broadsheet_id' => $broadsheetId,
                        'assessment_id' => $assessmentId,
                    ],
                    ['score' => $score]
                );
            }

            // Recompute totals, cum, grade, remark for this broadsheet
            $schoolclass = Schoolclass::with('classcategories')->find($broadsheetRecord->schoolclass_id);
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                    ->with('subAssessments')
                    ->get();
            }
            $broadsheet->load(['assessmentScores', 'subAssessmentScores']);
            $this->computeDynamicTotals(collect([$broadsheet]), $assessments, $schoolclass, $broadsheet->term_id, $broadsheetRecord->session_id);
        });

        // Update positions and metrics (full class refresh)
        $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $sessionId);
        $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $sessionId);
        $this->updateClassPositions($schoolclassId, $termId, $sessionId);

        Log::info('Single score updated', [
            'broadsheet_id' => $broadsheetId,
            'assessment_id' => $assessmentId,
            'is_sub' => $isSub,
            'sub_assessment_id' => $subAssessmentId,
            'score' => $score,
            'new_total' => $broadsheet->fresh()->total,
            'new_cum' => $broadsheet->fresh()->cum,
            'new_bf' => $broadsheet->fresh()->bf,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Score updated successfully!',
            'data' => [
                'total' => $broadsheet->total,
                'cum' => $broadsheet->cum,
                'bf' => $broadsheet->bf,
                'grade' => $broadsheet->grade,
                'remark' => $broadsheet->remark,
            ],
        ]);
    }

    public function bulkUpdateScores(Request $request)
    {
        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.id' => 'required|exists:broadsheets,id',
            'scores.*.assessments' => 'sometimes|array',
            'scores.*.total' => 'nullable|numeric', // Raw total from JS
            'scores.*.raw_total' => 'nullable|numeric', // Explicit raw total
            'term_id' => 'required|exists:schoolterm,id',
            'session_id' => 'required|exists:schoolsession,id',
            'subjectclass_id' => 'required|exists:subjectclass,id',
            'staff_id' => 'required|exists:users,id',
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'assessment_id' => 'required_if:is_sub,true|exists:assessments,id',
            'is_sub' => 'boolean',
        ]);

        $scores = $validated['scores'];
        $term_id = $validated['term_id'];
        $session_id = $validated['session_id'];
        $subjectclass_id = $validated['subjectclass_id'];
        $staff_id = $validated['staff_id'];
        $schoolclass_id = $validated['schoolclass_id'];
        $assessment_id = $validated['assessment_id'] ?? null;
        $is_sub = $validated['is_sub'] ?? false;

        // Fetch the school class and its class category once
        $schoolclass = Schoolclass::with('classcategories')->find($schoolclass_id);
        if (!$schoolclass) {
            Log::error('Schoolclass not found', ['schoolclass_id' => $schoolclass_id]);
            return response()->json([
                'success' => false,
                'message' => 'School class not found',
            ], 404);
        }

        $assessments = collect();
        if ($schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->get();
        }

        $updatedCount = 0;
        $errors = [];

        DB::transaction(function () use ($scores, $term_id, $session_id, $subjectclass_id, $staff_id, $schoolclass_id, $schoolclass, $assessments, $is_sub, $assessment_id, &$updatedCount, &$errors) {
            foreach ($scores as $scoreData) {
                $broadsheetId = $scoreData['id'];
                $broadsheet = Broadsheets::find($broadsheetId);
                if (!$broadsheet) {
                    $errors[] = "Broadsheet ID {$broadsheetId} not found.";
                    continue;
                }

                $assessmentsData = $scoreData['assessments'] ?? [];
                $rawTotal = $scoreData['raw_total'] ?? $scoreData['total'] ?? null;
                if (empty($assessmentsData)) {
                    // Skip if no assessments data
                    continue;
                }
                $localErrors = [];

                // Validate and update each assessment score
                foreach ($assessmentsData as $componentId => $inputScore) {
                    $componentId = (int) $componentId; // Ensure integer
                    if ($is_sub) {
                        $model = SubAssessment::find($componentId);
                        if (!$model) {
                            $localErrors[] = "SubAssessment ID {$componentId} invalid.";
                            continue;
                        }
                        if ($model->assessment_id != $assessment_id) {
                            $localErrors[] = "SubAssessment ID {$componentId} does not belong to assessment {$assessment_id}.";
                            continue;
                        }
                        $clampedScore = max(0, min($inputScore, $model->max_score));
                        if ($inputScore != $clampedScore) {
                            $localErrors[] = "Score for {$model->name} clamped to {$clampedScore} (max: {$model->max_score}).";
                        }
                        BroadsheetSubAssessmentScore::updateOrCreate(
                            [
                                'broadsheet_id' => $broadsheetId,
                                'sub_assessment_id' => $componentId,
                                'assessment_id' => $assessment_id,
                            ],
                            ['score' => $clampedScore]
                        );
                    } else {
                        $model = $assessments->where('id', $componentId)->first();
                        if (!$model) {
                            $localErrors[] = "Assessment ID {$componentId} invalid.";
                            continue;
                        }
                        $clampedScore = max(0, min($inputScore, $model->max_score));
                        if ($inputScore != $clampedScore) {
                            $localErrors[] = "Score for {$model->name} clamped to {$clampedScore} (max: {$model->max_score}).";
                        }
                        BroadsheetAssessmentScore::updateOrCreate(
                            [
                                'broadsheet_id' => $broadsheetId,
                                'assessment_id' => $componentId,
                            ],
                            ['score' => $clampedScore]
                        );
                    }
                }

                if (!empty($localErrors)) {
                    $errors[] = "Broadsheet {$broadsheetId}: " . implode(', ', $localErrors);
                    continue;
                }

                // If sub-assessments, compute normalized parent score
                if ($is_sub && $assessment_id) {
                    $assessment = $assessments->where('id', $assessment_id)->first();
                    if ($assessment) {
                        $subMaxSum = $assessment->subAssessments->sum('max_score');
                        $subTotal = BroadsheetSubAssessmentScore::where('broadsheet_id', $broadsheetId)
                            ->where('assessment_id', $assessment_id)
                            ->sum('score');
                        $normalized = $subMaxSum > 0 ? ($subTotal / $subMaxSum) * $assessment->max_score : 0;
                        $clampedNormalized = max(0, min($normalized, $assessment->max_score));

                        BroadsheetAssessmentScore::updateOrCreate(
                            [
                                'broadsheet_id' => $broadsheetId,
                                'assessment_id' => $assessment_id,
                            ],
                            ['score' => $clampedNormalized]
                        );

                        Log::info('Normalized parent score updated for bulk sub save', [
                            'broadsheet_id' => $broadsheetId,
                            'assessment_id' => $assessment_id,
                            'sub_total' => $subTotal,
                            'sub_max_sum' => $subMaxSum,
                            'normalized_score' => $clampedNormalized,
                            'raw_total_from_js' => $rawTotal ?? 'not provided',
                        ]);
                    }
                }

                // Recompute totals
                $broadsheetRecord = BroadsheetRecord::find($broadsheet->broadSheet_record_id);
                $broadsheet->load(['assessmentScores', 'subAssessmentScores']);
                $this->computeDynamicTotals(collect([$broadsheet]), $assessments, $schoolclass, $term_id, $session_id);

                $updatedCount++;
                Log::info('Updated broadsheet in bulk', [
                    'id' => $broadsheet->id,
                    'admissionno' => optional(optional($broadsheet->broadsheetRecord)->student)->admissionNO ?? 'N/A',
                    'total' => $broadsheet->total,
                    'bf' => $broadsheet->bf,
                    'cum' => $broadsheet->cum,
                    'grade' => $broadsheet->grade,
                    'remark' => $broadsheet->remark,
                ]);
            }

            // Update metrics and positions
            $this->updateClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
            $this->updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
            $this->updateClassPositions($schoolclass_id, $term_id, $session_id);
        });

        // Fetch updated broadsheets
        $updatedBroadsheets = $this->getBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

        $responseData = [
            'success' => true,
            'message' => "{$updatedCount} score(s) updated successfully!",
            'data' => [
                'broadsheets' => $updatedBroadsheets->toArray(),
                'assessments' => $assessments,
            ],
        ];

        if (!empty($errors)) {
            $responseData['warnings'] = $errors;
        }

        Log::info('bulkUpdateScores completed', ['updated_count' => $updatedCount, 'errors' => $errors]);

        return response()->json($responseData, 200);
    }
    
    public function results()
    {
        try {
            $subjectclass_id = session('subjectclass_id');
            $schoolclass_id = session('schoolclass_id');
            $term_id = session('term_id');
            $session_id = session('session_id');

            if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required session data',
                    'scores' => [],
                ], 400);
            }

            $schoolclass = Schoolclass::with('classcategories')->find($schoolclass_id);
            $assessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                    ->with('subAssessments')
                    ->orderBy('id')->get();
            }

            $broadsheets = Broadsheets::where([
                'subjectclass_id' => $subjectclass_id,
                'term_id' => $term_id,
            ])
                ->with(['assessmentScores', 'subAssessmentScores'])
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->where('broadsheet_records.session_id', $session_id)
                ->get([
                    'broadsheets.id',
                    'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.grade',
                    'broadsheets.subject_position_class as position',
                    'broadsheets.term_id',
                ]);

            // Compute dynamic data for response
            $scoresData = $broadsheets->map(function ($broadsheet) use ($assessments) {
                $assessmentData = [];
                foreach ($assessments as $assessment) {
                    $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                    $assessmentData[$assessment->id] = [
                        'name' => $assessment->name,
                        'max_score' => $assessment->max_score,
                        'score' => $scoreObj ? $scoreObj->score : 0,
                    ];
                }

                return [
                    'id' => $broadsheet->id,
                    'admissionno' => $broadsheet->admissionno,
                    'fname' => $broadsheet->fname,
                    'lname' => $broadsheet->lname,
                    'assessments' => $assessmentData,
                    'total' => $broadsheet->total,
                    'bf' => $broadsheet->bf,
                    'cum' => $broadsheet->cum,
                    'grade' => $broadsheet->grade,
                    'position' => $broadsheet->position,
                ];
            });

            return response()->json([
                'success' => true,
                'assessments' => $assessments,
                'scores' => $scoresData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in results endpoint: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        // Fetch the subjectclass to get the subject_id
        $subjectClass = DB::table('subjectclass')
            ->where('id', $subjectclassid)
            ->first(['subjectteacherid']);

        if (!$subjectClass) {
            Log::warning('Subjectclass not found', ['subjectclass_id' => $subjectclassid]);
            return;
        }

        $subjectTeacher = DB::table('subjectteacher')
            ->where('id', $subjectClass->subjectteacherid)
            ->first(['subjectid']);

        if (!$subjectTeacher) {
            Log::warning('Subjectteacher not found', ['subjectteacherid' => $subjectClass->subjectteacherid]);
            return;
        }

        $subjectId = $subjectTeacher->subjectid;

        // Calculate class metrics (min, max, avg) for the subject across all students
        $metrics = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassid)
            ->where('broadsheets.staff_id', $staffid)
            ->where('broadsheets.term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->select([
                DB::raw('MIN(broadsheets.cum) as class_min'),
                DB::raw('MAX(broadsheets.cum) as class_max'),
                DB::raw('SUM(broadsheets.cum) as cum_sum'),
                DB::raw('COUNT(broadsheets.id) as student_count')
            ])
            ->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round($metrics->cum_sum / $metrics->student_count, 1) : 0;

        Log::info('Calculated class metrics', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subject_id' => $subjectId,
            'class_min' => $classMin,
            'class_max' => $classMax,
            'class_avg' => $classAvg,
            'student_count' => $metrics->student_count,
            'cum_sum' => $metrics->cum_sum,
        ]);

        // Update all relevant broadsheet records with the calculated metrics
        Broadsheets::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->update([
                'cmin' => $classMin,
                'cmax' => $classMax,
                'avg' => $classAvg,
            ]);

        Log::info('Updated class metrics for broadsheets', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subject_id' => $subjectId,
        ]);
    }

    protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        Log::info('updateSubjectPositions called', compact('subjectclass_id', 'staff_id', 'term_id', 'session_id'));
        $broadsheets = Broadsheets::where('subjectclass_id', $subjectclass_id)
            ->where('staff_id', $staff_id)
            ->where('term_id', $term_id)
            ->where('broadsheet_records.session_id', $session_id)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->orderByDesc('broadsheets.cum')
            ->orderBy('broadsheets.id')
            ->get();

        if ($broadsheets->isEmpty()) {
            Log::warning('No broadsheets found for position update', compact('subjectclass_id', 'staff_id', 'term_id', 'session_id'));
            return;
        }

        $rank = 0;
        $lastCum = null;
        $lastPosition = 0;

        foreach ($broadsheets as $broadsheet) {
            $rank++;
            if ($lastCum !== null && $broadsheet->cum == $lastCum) {
                // Tied rank
            } else {
                $lastPosition = $rank;
                $lastCum = $broadsheet->cum;
            }
            if ($broadsheet->subject_position_class != $lastPosition) {
                $broadsheet->subject_position_class = $lastPosition;
                $broadsheet->save();
                Log::info('Updated position', [
                    'broadsheet_id' => $broadsheet->id,
                    'student_id' => $broadsheet->student_id,
                    'admissionno' => $broadsheet->admissionno,
                    'cum' => $broadsheet->cum,
                    'subject_position_class' => $lastPosition,
                ]);
            }
        }

        Log::info('Subject positions updated', ['total_records' => $broadsheets->count()]);
    }

    protected function updateClassPositions($schoolclassid, $termid, $sessionid)
    {
        $rank = 0;
        $lastScore = null;
        $rows = 0;

        $pos = PromotionStatus::where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)
            ->orderBy('subjectstotalscores', 'DESC')
            ->get();

        foreach ($pos as $row) {
            $rows++;
            if ($lastScore !== $row->subjectstotalscores) {
                $lastScore = $row->subjectstotalscores;
                $rank = $rows;
            }
            $position = match ($rank) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
            $rankPos = $rank . $position;

            PromotionStatus::where('id', $row->id)
                ->update(['position' => $rankPos]);
        }
    }

    public function edit($id)
    {
        $broadsheet = Broadsheets::where('broadsheets.id', $id)
            ->with('assessmentScores')
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheets.subjectclass_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->first([
                'broadsheets.id as bid',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.title',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentpicture.picture',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'schoolterm.term',
                'schoolsession.session',
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.id',
                'broadsheets.subject_position_class as position',
                'broadsheets.remark',
                'broadsheet_records.student_id',
                'broadsheets.staff_id',
                'broadsheets.term_id',
                'broadsheet_records.session_id as sessionid',
                'schoolclass.classcategoryid',
            ]);

        if (!$broadsheet) {
            return view('error', [
                'id' => $id,
                'title' => 'Not Found',
                'message' => 'Score not found.',
            ]);
        }

        $schoolclass = Schoolclass::with('classcategories')->find($broadsheet->schoolclass_id ?? 0); // From join
        $assessments = collect();
        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->orderBy('id')->get();
        }

        $pagetitle = sprintf(
            'Edit Score for %s %s - %s (%s)',
            $broadsheet->fname,
            $broadsheet->lname,
            $broadsheet->subject,
            $id
        );

        return view('scoresheet.edit', compact('broadsheet', 'pagetitle', 'assessments'));
    }

    public function update(Request $request, $id)
    {
        $broadsheet = Broadsheets::findOrFail($id);
        $termId = $broadsheet->term_id;
        $broadsheetRecordId = $broadsheet->broadSheet_record_id;
        $broadsheetRecord = BroadsheetRecord::find($broadsheetRecordId); // Fetch the record
        $schoolclassId = $broadsheetRecord->schoolclass_id ?? 0; // Assume from record

        // Validate dynamic assessments
        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassId);
        $assessments = collect();
        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->get();
        }
        $validationRules = [];
        foreach ($assessments as $assessment) {
            $field = 'assessment_' . $assessment->id;
            $validationRules[$field] = 'nullable|numeric|min:0|max:' . $assessment->max_score;
        }

        $request->validate($validationRules);

        // Update assessment scores
        foreach ($assessments as $assessment) {
            $field = 'assessment_' . $assessment->id;
            $score = $request->input($field, 0);
            BroadsheetAssessmentScore::updateOrCreate(
                [
                    'broadsheet_id' => $id,
                    'assessment_id' => $assessment->id,
                ],
                ['score' => $score]
            );
        }

        // Recompute total, cum, grade, remark
        $broadsheet->load('assessmentScores');
        $this->computeDynamicTotals(collect([$broadsheet]), $assessments, $schoolclass, $termId, $broadsheetRecord->session_id);

        $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);
        $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);
        $this->updateClassPositions($schoolclassId, $broadsheet->term_id, $broadsheetRecord->session_id);

        return redirect()->action(
            [self::class, 'subjectscoresheet'],
            [
                'schoolclassid' => $schoolclassId,
                'subjectclassid' => $broadsheet->subjectclass_id,
                'staffid' => $broadsheet->staff_id,
                'termid' => $termId,
                'sessionid' => $broadsheetRecord->session_id,
            ]
        )->with('success', 'Score updated successfully!');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $broadsheet = Broadsheets::findOrFail($id);
        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid = $broadsheet->staff_id;
        $termid = $broadsheet->term_id;

        $broadsheetRecord = DB::table('broadsheet_records')
            ->where('id', $broadsheet->broadSheet_record_id)
            ->first();

        // Delete assessment scores
        BroadsheetAssessmentScore::where('broadsheet_id', $id)->delete();
        BroadsheetSubAssessmentScore::where('broadsheet_id', $id)->delete();

        $broadsheet->delete();

        if ($broadsheetRecord) {
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateClassPositions($broadsheetRecord->schoolclass_id, $termid, $broadsheetRecord->session_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Score deleted successfully!',
        ]);
    }

    protected function calculateJuniorGrade($score)
    {
        if ($score >= 70 && $score <= 100) {
            return 'A';
        } elseif ($score >= 60) {
            return 'B';
        } elseif ($score >= 50) {
            return 'C';
        } elseif ($score >= 40) {
            return 'D';
        }
        return 'F';
    }

    /**
     * Fallback grading logic when class category is not available
     */
    protected function getDefaultGrade($score)
    {
        if ($score >= 70 && $score <= 100) {
            return 'A';
        } elseif ($score >= 60) {
            return 'B';
        } elseif ($score >= 50) {
            return 'C';
        } elseif ($score >= 40) {
            return 'D';
        }
        return 'F';
    }

    protected function getRemark($grade)
    {
        $remarks = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Pass',
            'F' => 'Fail',
            'A1' => 'Excellent',
            'B2' => 'Very Good',
            'B3' => 'Good',
            'C4' => 'Credit',
            'C5' => 'Credit',
            'C6' => 'Credit',
            'D7' => 'Pass',
            'E8' => 'Pass',
            'F9' => 'Fail',
        ];
        return $remarks[$grade] ?? 'Unknown';
    }

    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) {
            Log::debug('getBroadsheets: Term 1, bf set to 0', [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
            ]);
            return 0;
        }

        $previousTerm = Broadsheets::where('broadsheet_records.student_id', $studentId)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->where('broadsheets.term_id', $termId - 1)
            ->where('broadsheet_records.session_id', $sessionId)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->value('broadsheets.cum');

        if (is_null($previousTerm)) {
            Log::warning('getBroadsheets: No previous term cum found', [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'term_id' => $termId - 1,
                'session_id' => $sessionId,
            ]);
            return 0;
        }

        $cum = round($previousTerm, 2);
        Log::debug('getBroadsheets: Fetched previous cum', [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'term_id' => $termId - 1,
            'cum' => $cum,
        ]);

        return $cum;
    }

  

    public function import(Request $request)
    {
        // Note: ScoresheetImport needs modification to handle dynamic columns based on assessment names/ids
        // For now, assume it's updated externally
        // ... existing code
    }

    public function calculateGradePreview(Request $request)
    {
        $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'cum' => 'required|numeric|min:0|max:100',
        ]);

        $schoolclass = Schoolclass::with('classcategories')->findOrFail($request->schoolclass_id);
        $grade = $schoolclass->classcategories->isNotEmpty()
            ? $schoolclass->classcategories->first()->calculateGrade($request->cum)
            : $this->getDefaultGrade($request->cum);

        return response()->json(['grade' => $grade]);
    }
}