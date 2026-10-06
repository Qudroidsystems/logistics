@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Exams Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">Exams</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-2 fs-2">
                                        <i class="ph-exam"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Exams</p>
                                    <h4 class="fs-4 mb-0">{{ $exams->total() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
                                        <i class="ph-question"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Questions</p>
                                    <h4 class="fs-4 mb-0">{{ $exams->sum('questions_count') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info-subtle text-info rounded-2 fs-2">
                                        <i class="ph-graduation-cap"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Active Classes</p>
                                    <h4 class="fs-4 mb-0">{{ $myclass->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-2 fs-2">
                                        <i class="ph-book-open"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Subjects</p>
                                    <h4 class="fs-4 mb-0">{{ $mysubjects->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="alert-container"></div>

            <div id="examsList">
                <!-- Search and Action Bar -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search exams by title, description..." value="{{ request('search', '') }}">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-end gap-2">
                                            @can('Delete exam')
                                                <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                                    <i class="ri-delete-bin-2-line me-1"></i> Delete Selected
                                                </button>
                                            @endcan
                                            @can('Create exam')
                                                <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addExamModal">
                                                    <i class="bi bi-plus-circle align-baseline me-1"></i> Create New Exam
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exams Table -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">Exams Management</h5>
                                    <p class="text-muted mb-0 mt-1">Manage all your exams and assessments</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <button class="btn btn-subtle-secondary btn-sm" onclick="window.print()">
                                        <i class="ph-printer ph-sm me-1"></i> Print
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                @if($exams->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" id="examsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" style="width: 50px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="checkAll" />
                                                        </div>
                                                    </th>
                                                    <th class="text-center" style="width: 60px;">SN</th>
                                                    <th>Exam Details</th>
                                                    <th class="text-center" style="width: 120px;">Duration</th>
                                                    <th class="text-center" style="width: 150px;">Schedule</th>
                                                    <th class="text-center" style="width: 120px;">Term & Session</th>
                                                    <th class="text-center" style="width: 120px;">Questions</th>
                                                    <th class="text-center" style="width: 150px;">Class</th>
                                                    <th class="text-center" style="width: 120px;">Status</th>
                                                    <th class="text-center" style="width: 100px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $i = ($exams->currentPage() - 1) * $exams->perPage() @endphp
                                                @foreach($exams as $exam)
                                                    @if($exam->id)
                                                    <tr data-exam-id="{{ $exam->id }}">
                                                        <td class="text-center">
                                                            <div class="form-check">
                                                                <input class="form-check-input exam-checkbox" type="checkbox" name="chk_child" data-id="{{ $exam->id }}" />
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary rounded-pill p-2">
                                                                <span class="fw-bold">{{ ++$i }}</span>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-start">
                                                                <div class="flex-shrink-0 me-3">
                                                                    <div class="avatar-sm">
                                                                        <span class="avatar-title bg-primary-subtle text-primary rounded">
                                                                            <i class="ph-exam ph-sm"></i>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <h6 class="mb-1 exam-title">{{ $exam->title }}</h6>
                                                                    <p class="text-muted mb-0 exam-description">
                                                                        {{ Str::limit($exam->description ?? 'No description', 80) }}
                                                                    </p>
                                                                    <div class="mt-1">
                                                                        <small class="text-muted">
                                                                            <i class="ph-book-open-text ph-xs me-1"></i>
                                                                            {{ $exam->subject->subject ?? 'No Subject' }}
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-flex flex-column align-items-center">
                                                                <span class="badge bg-info-subtle text-info fs-6 px-3 py-2">
                                                                    <i class="ph-clock ph-sm me-1"></i>{{ $exam->duration }} min
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="text-center">
                                                                <div class="mb-1">
                                                                    <small class="text-muted d-block">Start</small>
                                                                    <span class="fw-medium">{{ $exam->start_time->format('M d, h:i A') }}</span>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">End</small>
                                                                    <span class="fw-medium">{{ $exam->end_time->format('M d, h:i A') }}</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            @php
                                                                $term = $terms->firstWhere('id', $exam->termid);
                                                                $session = $sessions->firstWhere('id', $exam->session);
                                                            @endphp
                                                            <div class="d-flex flex-column align-items-center">
                                                                <span class="badge bg-primary-subtle text-primary mb-1">
                                                                    {{ $term->term ?? 'N/A' }}
                                                                </span>
                                                                <span class="badge bg-success-subtle text-success">
                                                                    {{ $session->session ?? 'N/A' }}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <a href="{{ route('questions.show', $exam->id) }}" class="btn btn-subtle-primary btn-sm w-100">
                                                                <i class="ph-list-checks ph-sm me-1"></i>
                                                                {{ $exam->questions_count ?? 0 }}
                                                                <span class="ms-1">Q</span>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            @if($exam->schoolclass)
                                                                <div class="text-center">
                                                                    <span class="badge bg-primary mb-1 d-block">
                                                                        {{ $exam->schoolclass->schoolclass }}
                                                                        @if($exam->schoolclass->arm)
                                                                            ({{ $exam->schoolclass->arm }})
                                                                        @endif
                                                                    </span>
                                                                    <small class="text-muted">
                                                                        <i class="ph-users ph-xs me-1"></i>
                                                                        View Students
                                                                    </small>
                                                                </div>
                                                            @else
                                                                <span class="text-muted">No class</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($exam->is_published)
                                                                <span class="badge bg-success">
                                                                    <i class="ph-check-circle ph-xs me-1"></i>Published
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary">
                                                                    <i class="ph-clock ph-xs me-1"></i>Draft
                                                                </span>
                                                            @endif
                                                            <div class="mt-1">
                                                                <small class="text-muted">
                                                                    {{ $exam->created_at->diffForHumans() }}
                                                                </small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex justify-content-center gap-1">
                                                                <a href="{{ route('exams.students', $exam->id) }}"
                                                                   class="btn btn-subtle-info btn-icon btn-sm"
                                                                   data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="View Students">
                                                                    <i class="ph-users"></i>
                                                                </a>
                                                                @can('Update exam')
                                                                    <button class="btn btn-subtle-secondary btn-icon btn-sm edit-exam-btn"
                                                                            data-id="{{ $exam->id }}"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="Edit Exam">
                                                                        <i class="ph-pencil"></i>
                                                                    </button>
                                                                @endcan
                                                                @can('Delete exam')
                                                                    <button class="btn btn-subtle-danger btn-icon btn-sm delete-exam-btn"
                                                                            data-id="{{ $exam->id }}"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="Delete Exam">
                                                                        <i class="ph-trash"></i>
                                                                    </button>
                                                                @endcan
                                                            </div>
                                                            @if($exam->schoolclass)
                                                                <div class="text-center mt-1">
                                                                    <a href="{{ route('exams.students', $exam->id) }}" class="text-primary small">
                                                                        <i class="ph-eye ph-xs me-1"></i>View Students
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <!-- Empty State -->
                                    <div class="text-center py-5">
                                        <div class="avatar-lg mx-auto mb-4">
                                            <div class="avatar-title bg-primary-subtle text-primary rounded-circle display-5">
                                                <i class="ph-exam ph-2x"></i>
                                            </div>
                                        </div>
                                        <h4 class="mb-3">No Exams Found</h4>
                                        <p class="text-muted mb-4">You haven't created any exams yet. Start by creating your first exam.</p>
                                        @can('Create exam')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addExamModal">
                                                <i class="bi bi-plus-circle align-baseline me-1"></i> Create Your First Exam
                                            </button>
                                        @endcan
                                    </div>
                                @endif

                                <!-- Pagination -->
                                @if($exams->hasPages())
                                <div class="row mt-4 align-items-center">
                                    <div class="col-sm">
                                        <div class="text-muted">
                                            Showing <span class="fw-semibold">{{ $exams->firstItem() ?? 0 }}</span> to
                                            <span class="fw-semibold">{{ $exams->lastItem() ?? 0 }}</span> of
                                            <span class="fw-semibold">{{ $exams->total() }}</span> exams
                                        </div>
                                    </div>
                                    <div class="col-sm-auto">
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination pagination-separated pagination-sm mb-0">
                                                @if($exams->onFirstPage())
                                                    <li class="page-item disabled">
                                                        <span class="page-link"><i class="mdi mdi-chevron-left"></i></span>
                                                    </li>
                                                @else
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $exams->previousPageUrl() }}">
                                                            <i class="mdi mdi-chevron-left"></i>
                                                        </a>
                                                    </li>
                                                @endif

                                                @foreach ($exams->getUrlRange(1, $exams->lastPage()) as $page => $url)
                                                    @if($page == $exams->currentPage())
                                                        <li class="page-item active">
                                                            <span class="page-link">{{ $page }}</span>
                                                        </li>
                                                    @else
                                                        <li class="page-item">
                                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                        </li>
                                                    @endif
                                                @endforeach

                                                @if($exams->hasMorePages())
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $exams->nextPageUrl() }}">
                                                            <i class="mdi mdi-chevron-right"></i>
                                                        </a>
                                                    </li>
                                                @else
                                                    <li class="page-item disabled">
                                                        <span class="page-link"><i class="mdi mdi-chevron-right"></i></span>
                                                    </li>
                                                @endif
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Exam Modal -->
            @can('Create exam')
            <div id="addExamModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="ph-plus-circle ph-sm me-2"></i>Create New Exam
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="add-exam-form" autocomplete="off">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="staffId" value="{{ Auth::user()->id }}" required>

                                <!-- Basic Information -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-info ph-sm me-2"></i>Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label required">Exam Title</label>
                                                <input type="text" name="title" class="form-control" placeholder="e.g., Mid-Term Mathematics Exam" required>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="2" placeholder="Enter exam description..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Duration & Timing -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-clock ph-sm me-2"></i>Duration & Timing</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label required">Duration (minutes)</label>
                                                <div class="input-group">
                                                    <input type="number" name="duration" class="form-control" placeholder="60" required min="1">
                                                    <span class="input-group-text">min</span>
                                                </div>
                                                <small class="text-muted">Total time allowed for the exam</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label required">Start Time</label>
                                                <input type="datetime-local" name="start_time" class="form-control" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label required">End Time</label>
                                                <input type="datetime-local" name="end_time" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning d-none" id="duration-alert">
                                            <i class="ph-warning-circle ph-sm me-2"></i>
                                            <span id="duration-alert-text"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Academic Information -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-graduation-cap ph-sm me-2"></i>Academic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label required">Select Term</label>
                                                <select name="termid" id="addTerm" class="form-control" required>
                                                    <option value="" selected>Select Term</option>
                                                    @foreach ($terms as $term)
                                                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label required">Select Session</label>
                                                <select name="session" id="addSession" class="form-control" required>
                                                    <option value="" selected>Select Session</option>
                                                    @foreach ($sessions as $schoolsession)
                                                        <option value="{{ $schoolsession->id }}">{{ $schoolsession->session }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label required">Select Subject</label>
                                                <select name="subject_id" id="addSubject" class="form-control" required>
                                                    <option value="" selected>Select Subject</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Class Assignment -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-users ph-sm me-2"></i>Class Assignment</h6>
                                    </div>
                                    <div class="card-body">
                                        <label class="form-label required">Select Classes</label>
                                        <div id="addClassContainer" class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                            <p class="text-muted text-center mb-0">
                                                <i class="ph-info ph-sm me-1"></i>Select a subject first to see available classes
                                            </p>
                                        </div>
                                        <small class="text-muted mt-2 d-block">
                                            <i class="ph-info ph-sm me-1"></i>Select one or more classes for this exam
                                        </small>
                                    </div>
                                </div>

                                <!-- Publication Settings -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-globe ph-sm me-2"></i>Publication Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="publishStatus">
                                            <label class="form-check-label" for="publishStatus">
                                                <span class="fw-medium">Publish exam immediately</span>
                                            </label>
                                        </div>
                                        <div class="text-muted mt-2">
                                            <i class="ph-info ph-sm me-1"></i>
                                            If not checked, the exam will be saved as a draft and won't be visible to students.
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-danger d-none" id="add-alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    <i class="ph-x ph-sm me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-primary" id="add-btn">
                                    <i class="ph-check-circle ph-sm me-1"></i>Create Exam
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endcan

            <!-- Edit Exam Modal -->
            @can('Update exam')
            <div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="ph-pencil ph-sm me-2"></i>Edit Exam
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="edit-exam-form" autocomplete="off">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="edit-id-field" name="id">
                            <div class="modal-body">
                                <input type="hidden" name="staffId" value="{{ Auth::user()->id }}">

                                <!-- Basic Information -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-info ph-sm me-2"></i>Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label required">Exam Title</label>
                                                <input type="text" name="title" id="edit-title" class="form-control" required>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" id="edit-description" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Duration & Timing -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-clock ph-sm me-2"></i>Duration & Timing</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label required">Duration (minutes)</label>
                                                <div class="input-group">
                                                    <input type="number" name="duration" id="edit-duration" class="form-control" required min="1">
                                                    <span class="input-group-text">min</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label required">Start Time</label>
                                                <input type="datetime-local" name="start_time" id="edit-start_time" class="form-control" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label required">End Time</label>
                                                <input type="datetime-local" name="end_time" id="edit-end_time" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning d-none" id="edit-duration-alert">
                                            <i class="ph-warning-circle ph-sm me-2"></i>
                                            <span id="edit-duration-alert-text"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Academic Information -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-graduation-cap ph-sm me-2"></i>Academic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label required">Select Term</label>
                                                <select name="termid" id="edit-termid" class="form-control" required>
                                                    <option value="" selected>Select Term</option>
                                                    @foreach ($terms as $term)
                                                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label required">Select Session</label>
                                                <select name="session" id="edit-session" class="form-control" required>
                                                    <option value="" selected>Select Session</option>
                                                    @foreach ($sessions as $schoolsession)
                                                        <option value="{{ $schoolsession->id }}">{{ $schoolsession->session }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label required">Select Subject</label>
                                                <select name="subject_id" id="edit-subject_id" class="form-control" required>
                                                    <option value="" selected>Select Subject</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Class Assignment -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-users ph-sm me-2"></i>Class Assignment</h6>
                                    </div>
                                    <div class="card-body">
                                        <label class="form-label required">Select Classes</label>
                                        <div id="editClassContainer" class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                            <p class="text-muted text-center mb-0">
                                                <i class="ph-circle-notch ph-sm spin me-1"></i>Loading classes...
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Publication Settings -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ph-globe ph-sm me-2"></i>Publication Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_published" id="edit-publishStatus" value="1">
                                            <label class="form-check-label" for="edit-publishStatus">
                                                <span class="fw-medium">Publish exam immediately</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-danger d-none" id="edit-alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    <i class="ph-x ph-sm me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-primary" id="update-btn">
                                    <i class="ph-check-circle ph-sm me-1"></i>Update Exam
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endcan

            <!-- Copy Questions Modal -->
            <div id="copyQuestionsModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="ph-copy ph-sm me-2"></i>Copy Questions to New Classes
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info mb-4">
                                <i class="ph-info ph-sm me-2"></i>
                                You have added <span id="new-classes-count" class="fw-bold">0</span> new class(es). Would you like to copy questions to these new exams?
                            </div>

                            <div class="card mb-3 border">
                                <div class="card-header bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="copy-all-questions" checked>
                                        <label class="form-check-label fw-medium" for="copy-all-questions">
                                            Copy all questions from current exam
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="questions-loading" class="text-center py-3 d-none">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading questions...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading questions...</p>
                                    </div>

                                    <div id="questions-list-container" class="d-none">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted">
                                                    <i class="ph-check-square ph-sm me-1"></i>
                                                    <span id="selected-questions-count">0</span> of <span id="total-questions-count">0</span> questions selected
                                                </span>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-link" id="select-all-questions">Select All</button>
                                                    <button type="button" class="btn btn-sm btn-link" id="deselect-all-questions">Deselect All</button>
                                                </div>
                                            </div>
                                            <div class="list-group" id="questions-list" style="max-height: 300px; overflow-y: auto;"></div>
                                        </div>
                                    </div>

                                    <div id="no-questions-message" class="alert alert-warning mb-0 d-none">
                                        <i class="ph-warning-circle ph-sm me-2"></i>
                                        No questions found in the current exam. You can add questions later.
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mb-0">
                                <i class="ph-info ph-sm me-2"></i>
                                <strong>Note:</strong> Questions will be duplicated for each new class. They will be independent copies that you can edit separately.
                            </div>

                            <input type="hidden" id="source-exam-id" value="">
                            <input type="hidden" id="new-class-ids" value="">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                <i class="ph-x ph-sm me-1"></i>Skip & Continue
                            </button>
                            <button type="button" class="btn btn-primary" id="confirm-copy-questions">
                                <i class="ph-check-circle ph-sm me-1"></i>Copy Selected Questions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page-content -->
    </div>
</div>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.card-animate {
    transition: transform 0.3s ease;
}

.card-animate:hover {
    transform: translateY(-5px);
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
}

.table-hover tbody tr:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.search-box {
    position: relative;
}

.search-box .search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #74788d;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spinner-border {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    vertical-align: text-bottom;
    border: 0.2em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border .75s linear infinite;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}

/* Modal styles */
.modal-header.bg-primary {
    background-color: #0d6efd !important;
}

.modal-header.bg-primary .btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

#questions-list {
    max-height: 300px;
    overflow-y: auto;
}

#questions-list .list-group-item {
    padding: 0.75rem 1rem;
}

#questions-list .form-check {
    margin-bottom: 0;
}

#questions-list .form-check-label {
    cursor: pointer;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 14px;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Print styles */
@media print {
    .card-header,
    .btn,
    #remove-actions,
    .search-box,
    .modal,
    .pagination {
        display: none !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }

    th, td {
        border: 1px solid #ddd !important;
        padding: 8px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Initialize tooltips
    try {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    } catch (e) {
        console.warn('Tooltips could not be initialized:', e);
    }

    // Initialize Copy Questions Modal
    let copyQuestionsModal;
    const copyModalElement = document.getElementById('copyQuestionsModal');
    if (copyModalElement) {
        try {
            copyQuestionsModal = new bootstrap.Modal(copyModalElement, {
                backdrop: 'static',
                keyboard: false
            });
        } catch (e) {
            console.error('Error initializing copy questions modal:', e);
        }
    }

    // Initialize modal filtering
    initModalFiltering();

    // Global variables
    window.newClassesToCreate = [];
    window.sourceExamId = null;
    window.originalClassIds = [];

    // Edit button click handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-exam-btn')) {
            e.preventDefault();
            const examId = e.target.closest('.edit-exam-btn').dataset.id;
            if (examId) {
                loadExamForEdit(examId);
            }
        }

        if (e.target.closest('.delete-exam-btn')) {
            e.preventDefault();
            const examId = e.target.closest('.delete-exam-btn').dataset.id;
            if (examId) {
                deleteExam(examId);
            }
        }

        // Check all functionality
        if (e.target.id === 'checkAll') {
            const checkboxes = document.querySelectorAll('.exam-checkbox');
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            toggleRemoveActions();
        }

        // Individual checkbox change
        if (e.target.classList.contains('exam-checkbox')) {
            toggleRemoveActions();
        }
    });

    // Form submissions
    const addForm = document.getElementById('add-exam-form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAddForm();
        });

        // Duration validation for add form
        addForm.querySelectorAll('input[name="duration"], input[name="start_time"], input[name="end_time"]').forEach(input => {
            input.addEventListener('change', validateDuration);
        });
    }

    const editForm = document.getElementById('edit-exam-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitEditForm();
        });

        // Duration validation for edit form
        editForm.querySelectorAll('input[name="duration"], input[name="start_time"], input[name="end_time"]').forEach(input => {
            input.addEventListener('change', validateEditDuration);
        });
    }

    // Search functionality
    const searchInput = document.querySelector('.search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                window.location.href = `{{ route('exams.index') }}?search=${encodeURIComponent(this.value)}`;
            }, 500);
        });
    }

    // Copy questions modal event listeners
    const copyAllCheckbox = document.getElementById('copy-all-questions');
    if (copyAllCheckbox) {
        copyAllCheckbox.addEventListener('change', function() {
            const questionsListContainer = document.getElementById('questions-list-container');
            if (questionsListContainer) {
                if (this.checked) {
                    questionsListContainer.classList.add('d-none');
                } else {
                    questionsListContainer.classList.remove('d-none');
                }
            }
        });
    }

    const selectAllBtn = document.getElementById('select-all-questions');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#questions-list .question-checkbox').forEach(cb => {
                cb.checked = true;
            });
            updateSelectedQuestionsCount();
        });
    }

    const deselectAllBtn = document.getElementById('deselect-all-questions');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#questions-list .question-checkbox').forEach(cb => {
                cb.checked = false;
            });
            updateSelectedQuestionsCount();
        });
    }

    const confirmCopyBtn = document.getElementById('confirm-copy-questions');
    if (confirmCopyBtn) {
        confirmCopyBtn.addEventListener('click', function() {
            const copyAll = document.getElementById('copy-all-questions').checked;
            let selectedQuestions = [];

            if (!copyAll) {
                selectedQuestions = Array.from(document.querySelectorAll('#questions-list .question-checkbox:checked'))
                    .map(cb => cb.value);
            }

            // Close copy modal
            if (copyQuestionsModal) {
                copyQuestionsModal.hide();
            } else {
                // Fallback
                const modalElement = document.getElementById('copyQuestionsModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
            }

            // Execute the form submission with copy parameters
            if (window.pendingEditFormData) {
                executeEditFormSubmit(
                    window.pendingEditFormData.examId,
                    window.pendingEditFormData.formData,
                    true,  // copyQuestions
                    copyAll,  // copyAllQuestions
                    selectedQuestions  // selectedQuestions
                );
                window.pendingEditFormData = null;
            } else {
                // If no pending form data, try to get it from the edit form
                const editForm = document.getElementById('edit-exam-form');
                const examId = document.getElementById('edit-id-field')?.value;

                if (examId && editForm) {
                    executeEditFormSubmit(
                        examId,
                        new FormData(editForm),
                        true,
                        copyAll,
                        selectedQuestions
                    );
                }
            }
        });
    }

    // Skip & Continue button - just proceed without copying
    const skipCopyBtn = document.querySelector('#copyQuestionsModal .btn-light');
    if (skipCopyBtn) {
        skipCopyBtn.addEventListener('click', function(e) {
            if (window.pendingEditFormData) {
                // Close modal and submit without copying
                if (copyQuestionsModal) {
                    copyQuestionsModal.hide();
                }

                executeEditFormSubmit(
                    window.pendingEditFormData.examId,
                    window.pendingEditFormData.formData,
                    false, // don't copy questions
                    true,  // not used when copyQuestions is false
                    []     // not used when copyQuestions is false
                );
                window.pendingEditFormData = null;
            }
        });
    }
});

// Global variables
let newClassesToCreate = [];
let sourceExamId = null;

function initModalFiltering() {
    // Add modal filtering
    const addTerm = document.getElementById('addTerm');
    const addSession = document.getElementById('addSession');
    const addSubject = document.getElementById('addSubject');

    if (addTerm && addSession && addSubject) {
        addTerm.addEventListener('change', function() {
            fetchFilteredSubjects(this.value, addSession.value, 'add');
        });

        addSession.addEventListener('change', function() {
            fetchFilteredSubjects(addTerm.value, this.value, 'add');
        });

        // Load all subjects initially
        fetchFilteredSubjects('', '', 'add');
    }

    // Subject change listener for add modal
    if (addSubject) {
        addSubject.addEventListener('change', function() {
            if (this.value) {
                loadClassesForSubject(this.value, 'add');
            } else {
                const container = document.getElementById('addClassContainer');
                if (container) {
                    container.innerHTML = '<p class="text-muted text-center mb-0"><i class="ph-info ph-sm me-1"></i>Select a subject first to see available classes</p>';
                }
            }
        });
    }
}

// Function to validate duration against start and end times
function validateDuration() {
    const form = document.getElementById('add-exam-form');
    if (!form) return true;

    const duration = parseInt(form.querySelector('input[name="duration"]')?.value) || 0;
    const startTime = form.querySelector('input[name="start_time"]')?.value;
    const endTime = form.querySelector('input[name="end_time"]')?.value;

    if (duration && startTime && endTime) {
        const start = new Date(startTime);
        const end = new Date(endTime);
        const totalMinutes = Math.round((end - start) / (1000 * 60));

        const alertDiv = document.getElementById('duration-alert');
        const alertText = document.getElementById('duration-alert-text');

        if (duration > totalMinutes) {
            if (alertText) alertText.textContent = `Duration (${duration} minutes) exceeds the time between start and end (${totalMinutes} minutes). Please adjust the duration or extend the end time.`;
            if (alertDiv) alertDiv.classList.remove('d-none');
            return false;
        } else {
            if (alertDiv) alertDiv.classList.add('d-none');
            return true;
        }
    }
    return true;
}

function validateEditDuration() {
    const form = document.getElementById('edit-exam-form');
    if (!form) return true;

    const duration = parseInt(form.querySelector('input[name="duration"]')?.value) || 0;
    const startTime = form.querySelector('input[name="start_time"]')?.value;
    const endTime = form.querySelector('input[name="end_time"]')?.value;

    if (duration && startTime && endTime) {
        const start = new Date(startTime);
        const end = new Date(endTime);
        const totalMinutes = Math.round((end - start) / (1000 * 60));

        const alertDiv = document.getElementById('edit-duration-alert');
        const alertText = document.getElementById('edit-duration-alert-text');

        if (duration > totalMinutes) {
            if (alertText) alertText.textContent = `Duration (${duration} minutes) exceeds the time between start and end (${totalMinutes} minutes). Please adjust the duration or extend the end time.`;
            if (alertDiv) alertDiv.classList.remove('d-none');
            return false;
        } else {
            if (alertDiv) alertDiv.classList.add('d-none');
            return true;
        }
    }
    return true;
}

// Function to fetch subjects based on term and session
function fetchFilteredSubjects(termId, sessionId, mode = 'add') {
    const subjectSelect = document.getElementById(mode === 'add' ? 'addSubject' : 'edit-subject_id');
    const subjectContainer = mode === 'add' ? 'addClassContainer' : 'editClassContainer';

    const containerElement = document.getElementById(subjectContainer);
    if (!subjectSelect || !containerElement) return;

    // Show loading
    subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
    containerElement.innerHTML = '<p class="text-muted text-center mb-0"><i class="ph-circle-notch ph-sm spin me-1"></i>Loading subjects...</p>';

    // Build query parameters
    const params = new URLSearchParams();
    if (termId) params.append('term_id', termId);
    if (sessionId) params.append('session_id', sessionId);

    fetch(`/exams/filtered-subjects?${params.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.subjects && data.subjects.length > 0) {
            let options = '<option value="" selected>Select Subject</option>';

            data.subjects.forEach(subject => {
                options += `<option value="${subject.id}"
                    data-termid="${subject.termid}"
                    data-sessionid="${subject.sessionid}">
                    ${subject.display_text}
                </option>`;
            });

            subjectSelect.innerHTML = options;
        } else {
            subjectSelect.innerHTML = '<option value="">No subjects found for selected term/session</option>';
            containerElement.innerHTML = '<p class="text-muted text-center mb-0">No subjects available for the selected term/session</p>';
        }
    })
    .catch(error => {
        console.error('Error fetching subjects:', error);
        subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
        containerElement.innerHTML = '<p class="text-danger text-center mb-0">Error loading subjects. Please try again.</p>';
    });
}

function loadClassesForSubject(subjectId, mode = 'add') {
    const containerId = mode === 'add' ? 'addClassContainer' : 'editClassContainer';
    const container = document.getElementById(containerId);

    if (!container) return;

    container.innerHTML = '<p class="text-muted text-center mb-0"><i class="ph-circle-notch ph-sm spin me-1"></i> Loading classes...</p>';

    fetch(`/exams/subject-classes/${subjectId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success && data.classes && data.classes.length > 0) {
            let html = '<div class="row">';

            data.classes.forEach(cls => {
                html += `
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input class="form-check-input class-checkbox" type="checkbox"
                                   name="schoolclass_ids[]"
                                   value="${cls.id}"
                                   id="class_${mode}_${cls.id}">
                            <label class="form-check-label" for="class_${mode}_${cls.id}">
                                <span class="fw-medium">${cls.schoolclass}</span>
                                ${cls.arm ? `<span class="text-muted">(${cls.arm})</span>` : ''}
                            </label>
                        </div>
                    </div>`;
            });

            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-muted text-center mb-0">No classes assigned to this subject.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading classes:', error);
        container.innerHTML = '<p class="text-danger text-center mb-0">Error loading classes. Please try again.</p>';
    });
}

function loadExamForEdit(examId) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/exams/${examId}/edit`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.exam) {
            // Store original class IDs for comparison
            window.originalClassIds = data.schoolclass_ids || [];

            // Store source exam ID for copying questions
            sourceExamId = data.exam.id;
            const sourceExamIdField = document.getElementById('source-exam-id');
            if (sourceExamIdField) {
                sourceExamIdField.value = data.exam.id;
            }

            populateEditForm(data);

            const editModalElement = document.getElementById('editModal');
            if (editModalElement) {
                const editModal = new bootstrap.Modal(editModalElement);
                editModal.show();
            }

            Swal.close();
        } else {
            throw new Error(data.message || 'Invalid response format');
        }
    })
    .catch(error => {
        console.error('Error loading exam:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load exam data. Please try again.',
            timer: 3000
        });
    });
}

function populateEditForm(data) {
    const exam = data.exam;

    // Basic fields
    const idField = document.getElementById('edit-id-field');
    const titleField = document.getElementById('edit-title');
    const descriptionField = document.getElementById('edit-description');
    const durationField = document.getElementById('edit-duration');
    const startTimeField = document.getElementById('edit-start_time');
    const endTimeField = document.getElementById('edit-end_time');
    const termidField = document.getElementById('edit-termid');
    const sessionField = document.getElementById('edit-session');
    const publishStatusField = document.getElementById('edit-publishStatus');

    if (idField) idField.value = exam.id || '';
    if (titleField) titleField.value = exam.title || '';
    if (descriptionField) descriptionField.value = exam.description || '';
    if (durationField) durationField.value = exam.duration || '';

    // Date fields - format for datetime-local input
    if (exam.start_time && startTimeField) {
        let startDate = new Date(exam.start_time);
        startTimeField.value = formatDateForInput(startDate);
    }

    if (exam.end_time && endTimeField) {
        let endDate = new Date(exam.end_time);
        endTimeField.value = formatDateForInput(endDate);
    }

    // Select fields
    if (termidField) termidField.value = data.termid || '';
    if (sessionField) sessionField.value = data.sessionid || '';
    if (publishStatusField) publishStatusField.checked = exam.is_published == 1;

    // Load subjects for the selected term and session
    setTimeout(() => {
        const termId = data.termid || '';
        const sessionId = data.sessionid || '';
        fetchFilteredSubjects(termId, sessionId, 'edit');

        // Wait for subjects to load, then select the correct one
        setTimeout(() => {
            const subjectSelect = document.getElementById('edit-subject_id');
            if (subjectSelect && data.subject_id) {
                subjectSelect.value = data.subject_id;

                // Now load classes for this subject with selection
                if (data.subject_id) {
                    loadClassesForEditWithSelection(data.subject_id, data.schoolclass_ids || []);
                }
            }
        }, 500);
    }, 100);
}

function loadClassesForEditWithSelection(subjectId, selectedClassIds = []) {
    const container = document.getElementById('editClassContainer');
    if (!container) return;

    container.innerHTML = '<p class="text-muted text-center mb-0"><i class="ph-circle-notch ph-sm spin me-1"></i> Loading classes...</p>';

    fetch(`/exams/subject-classes/${subjectId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success && data.classes && data.classes.length > 0) {
            // Get original class IDs from the exam group
            const originalClassIds = window.originalClassIds || [];

            let html = '<div class="row">';

            data.classes.forEach(cls => {
                const classId = parseInt(cls.id);
                const isChecked = selectedClassIds.some(id => parseInt(id) === classId);

                html += `
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input class="form-check-input class-checkbox"
                                   type="checkbox"
                                   name="schoolclass_ids[]"
                                   value="${cls.id}"
                                   id="class_edit_${cls.id}"
                                   ${isChecked ? 'checked' : ''}>
                            <label class="form-check-label" for="class_edit_${cls.id}">
                                <span class="fw-medium">${cls.schoolclass}</span>
                                ${cls.arm ? `<span class="text-muted">(${cls.arm})</span>` : ''}
                                ${!originalClassIds.includes(classId) && isChecked ?
                                    '<span class="badge bg-success ms-2">New</span>' : ''}
                            </label>
                        </div>
                    </div>`;
            });

            html += '</div>';
            container.innerHTML = html;

            // Track new classes that are checked
            setTimeout(() => {
                trackNewClasses(originalClassIds);
            }, 100);
        } else {
            container.innerHTML = '<p class="text-muted text-center mb-0">No classes assigned to this subject.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading classes:', error);
        container.innerHTML = '<p class="text-danger text-center mb-0">Error loading classes. Please try again.</p>';
    });
}

// Function to track which classes are new
function trackNewClasses(originalClassIds) {
    const checkboxes = document.querySelectorAll('input[name="schoolclass_ids[]"]:checked');
    newClassesToCreate = [];

    checkboxes.forEach(cb => {
        const classId = parseInt(cb.value);
        if (!originalClassIds.includes(classId)) {
            newClassesToCreate.push(classId);
        }
    });

    console.log('New classes to create:', newClassesToCreate);
}

function formatDateForInput(date) {
    if (!date || isNaN(date)) return '';

    try {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    } catch (error) {
        console.error('Date formatting error:', error);
        return '';
    }
}

function submitAddForm() {
    const form = document.getElementById('add-exam-form');
    const submitBtn = document.getElementById('add-btn');

    if (!form || !submitBtn) return;

    const originalText = submitBtn.textContent;

    // Validate duration first
    if (!validateDuration()) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Duration',
            text: 'Duration exceeds the time between start and end. Please adjust.',
            timer: 3000
        });
        return;
    }

    // Validate class selection
    const classCheckboxes = form.querySelectorAll('input[name="schoolclass_ids[]"]:checked');
    if (classCheckboxes.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select at least one class.',
            timer: 3000
        });
        return;
    }

    const formData = new FormData(form);

    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
    submitBtn.disabled = true;

    fetch('{{ route('exams.store') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Exam created successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addExamModal'));
                if (modal) modal.hide();
                form.reset();

                // Reset class container
                const classContainer = document.getElementById('addClassContainer');
                if (classContainer) {
                    classContainer.innerHTML = '<p class="text-muted text-center mb-0"><i class="ph-info ph-sm me-1"></i>Select a subject first to see available classes</p>';
                }

                // Reload page
                window.location.reload();
            });
        } else {
            let errorMsg = 'An error occurred.';
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join('<br>');
            } else if (data.message) {
                errorMsg = data.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMsg,
                timer: 5000
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.',
            timer: 3000
        });
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function submitEditForm() {
    const form = document.getElementById('edit-exam-form');
    const examId = document.getElementById('edit-id-field')?.value;
    const submitBtn = document.getElementById('update-btn');

    if (!examId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Invalid exam ID.',
            timer: 3000
        });
        return;
    }

    // Validate duration first
    if (!validateEditDuration()) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Duration',
            text: 'Duration exceeds the time between start and end. Please adjust.',
            timer: 3000
        });
        return;
    }

    // Validate class selection
    const classCheckboxes = form.querySelectorAll('input[name="schoolclass_ids[]"]:checked');
    if (classCheckboxes.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select at least one class.',
            timer: 3000
        });
        return;
    }

    // Check if there are new classes to create
    const originalClassIds = window.originalClassIds || [];
    const selectedClassIds = Array.from(classCheckboxes).map(cb => parseInt(cb.value));
    const newClassIds = selectedClassIds.filter(id => !originalClassIds.includes(id));

    if (newClassIds.length > 0 && sourceExamId) {
        // Show copy questions modal first
        newClassesToCreate = newClassIds;

        // Store form data to submit later
        window.pendingEditFormData = {
            examId: examId,
            formData: new FormData(form)
        };

        showCopyQuestionsModal();
        return;
    }

    // If no new classes, submit directly
    executeEditFormSubmit(examId, form);
}

// Execute the actual form submission
function executeEditFormSubmit(examId, form, copyQuestions = false, copyAll = true, selectedQuestions = []) {
    const submitBtn = document.getElementById('update-btn');
    if (!submitBtn) return;

    const originalText = submitBtn.textContent;

    let formData;
    if (form instanceof FormData) {
        formData = form;
    } else {
        formData = new FormData(form);
    }

    formData.append('_method', 'PUT');

    // Add copy questions parameters
    if (copyQuestions) {
        formData.append('copy_questions', '1');
        formData.append('copy_all_questions', copyAll ? '1' : '0');
        if (!copyAll && selectedQuestions.length > 0) {
            selectedQuestions.forEach(id => {
                formData.append('selected_questions[]', id);
            });
        }
    }

    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    submitBtn.disabled = true;

    fetch(`/exams/${examId}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Exam updated successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                if (modal) modal.hide();
                window.location.reload();
            });
        } else {
            let errorMsg = 'An error occurred.';
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join('<br>');
            } else if (data.message) {
                errorMsg = data.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMsg,
                timer: 5000
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.',
            timer: 3000
        });
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Function to show copy questions modal
function showCopyQuestionsModal() {
    const modalElement = document.getElementById('copyQuestionsModal');
    if (!modalElement) {
        console.error('Copy questions modal element not found');
        return;
    }

    // Update new classes count
    const newClassesCountEl = document.getElementById('new-classes-count');
    if (newClassesCountEl) {
        newClassesCountEl.textContent = newClassesToCreate.length;
    }

    const newClassIdsEl = document.getElementById('new-class-ids');
    if (newClassIdsEl) {
        newClassIdsEl.value = JSON.stringify(newClassesToCreate);
    }

    // Reset modal state
    const copyAllCheckbox = document.getElementById('copy-all-questions');
    if (copyAllCheckbox) copyAllCheckbox.checked = true;

    const questionsListContainer = document.getElementById('questions-list-container');
    if (questionsListContainer) questionsListContainer.classList.add('d-none');

    const noQuestionsMessage = document.getElementById('no-questions-message');
    if (noQuestionsMessage) noQuestionsMessage.classList.add('d-none');

    // Load questions
    if (sourceExamId) {
        loadQuestionsForCopy(sourceExamId);
    }

    // Show modal
    try {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } catch (e) {
        console.error('Error showing copy questions modal:', e);
    }
}

// Function to load questions for copying
function loadQuestionsForCopy(examId) {
    const questionsList = document.getElementById('questions-list');
    const questionsLoading = document.getElementById('questions-loading');
    const questionsListContainer = document.getElementById('questions-list-container');
    const noQuestionsMessage = document.getElementById('no-questions-message');

    if (!questionsList || !questionsLoading || !questionsListContainer || !noQuestionsMessage) return;

    questionsLoading.classList.remove('d-none');
    questionsListContainer.classList.add('d-none');
    noQuestionsMessage.classList.add('d-none');

    fetch(`/exams/${examId}/questions`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        questionsLoading.classList.add('d-none');

        if (data.success && data.questions && data.questions.length > 0) {
            let html = '';
            let typeBadges = {
                'mcq': 'bg-info',
                'true_false': 'bg-warning',
                'short_answer': 'bg-success'
            };

            data.questions.forEach((question, index) => {
                const typeClass = typeBadges[question.type] || 'bg-secondary';
                const typeLabel = question.type.toUpperCase().replace('_', ' ');

                html += `
                    <div class="list-group-item list-group-item-action">
                        <div class="form-check">
                            <input class="form-check-input question-checkbox"
                                   type="checkbox"
                                   value="${question.id}"
                                   id="copy_question_${question.id}"
                                   checked>
                            <label class="form-check-label w-100" for="copy_question_${question.id}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 me-3">
                                        <span class="fw-medium">Q${index + 1}:</span>
                                        ${question.text}
                                        <div class="mt-2">
                                            <span class="badge ${typeClass} me-1">${typeLabel}</span>
                                            <span class="badge bg-primary me-1">${question.marks} marks</span>
                                            <span class="badge bg-secondary">${question.options_count} options</span>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                `;
            });

            questionsList.innerHTML = html;

            const totalCountEl = document.getElementById('total-questions-count');
            if (totalCountEl) totalCountEl.textContent = data.questions.length;

            const selectedCountEl = document.getElementById('selected-questions-count');
            if (selectedCountEl) selectedCountEl.textContent = data.questions.length;

            questionsListContainer.classList.remove('d-none');

            // Add event listeners to checkboxes
            document.querySelectorAll('#questions-list .question-checkbox').forEach(cb => {
                cb.addEventListener('change', updateSelectedQuestionsCount);
            });
        } else {
            noQuestionsMessage.classList.remove('d-none');
        }
    })
    .catch(error => {
        console.error('Error loading questions:', error);
        questionsLoading.classList.add('d-none');
        noQuestionsMessage.classList.remove('d-none');
        if (noQuestionsMessage) {
            noQuestionsMessage.innerHTML = `
                <i class="ph-warning-circle ph-sm me-2"></i>
                Error loading questions. Please try again.
            `;
        }
    });
}

// Update selected questions count
function updateSelectedQuestionsCount() {
    const checkboxes = document.querySelectorAll('#questions-list .question-checkbox:checked');
    const count = checkboxes.length;
    const selectedCountEl = document.getElementById('selected-questions-count');
    if (selectedCountEl) selectedCountEl.textContent = count;
}

function deleteExam(examId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the exam and all associated questions!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/exams/${examId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message || 'Exam deleted successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to delete exam.',
                        timer: 3000
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete exam. Please try again.',
                    timer: 3000
                });
            });
        }
    });
}

function deleteMultiple() {
    const checkedBoxes = document.querySelectorAll('.exam-checkbox:checked');
    if (checkedBoxes.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select at least one exam to delete.',
            timer: 3000
        });
        return;
    }

    const ids = Array.from(checkedBoxes)
        .map(cb => cb.dataset.id)
        .filter(id => id);

    Swal.fire({
        title: `Delete ${ids.length} exam(s)?`,
        text: "This will delete all selected exams and their associated questions!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete them!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/exams/bulk-destroy`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message || 'Exams deleted successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to delete exams.',
                        timer: 3000
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete exams. Please try again.',
                    timer: 3000
                });
            });
        }
    });
}

function toggleRemoveActions() {
    const removeActions = document.getElementById('remove-actions');
    if (removeActions) {
        const checkedBoxes = document.querySelectorAll('.exam-checkbox:checked');
        removeActions.classList.toggle('d-none', checkedBoxes.length === 0);
    }
}
</script>

@endsection
