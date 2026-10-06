@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">Exams</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">All Exams</a></li>
                                <li class="breadcrumb-item active">Questions</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Exam Summary Card -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card overflow-hidden">
                        <div class="bg-primary-subtle">
                            <div class="row">
                                <div class="col-7">
                                    <div class="text-primary p-3">
                                        <h5 class="text-primary mb-0">{{ $exam->title }}</h5>
                                        <p class="mb-0">{{ $exam->description ?? 'No description available' }}</p>
                                    </div>
                                </div>
                                <div class="col-5 align-self-end">
                                    <img src="{{ asset('assets/images/profile-img.png') }}" alt="" class="img-fluid" style="max-height: 120px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="avatar-md profile-user-wid mb-4">
                                        <span class="avatar-title rounded-circle bg-primary bg-soft text-primary font-size-24">
                                            <i class="ph-book-open"></i>
                                        </span>
                                    </div>
                                    <h5 class="font-size-15 text-truncate">{{ $exam->subject->subject ?? 'No Subject' }}</h5>
                                    <p class="text-muted mb-0 text-truncate">Subject</p>
                                </div>

                                <div class="col-sm-4">
                                    <div class="mt-4">
                                        <h5 class="font-size-14 mb-1">Class</h5>
                                        <div class="d-flex align-items-center">
                                            <i class="ph-users-three text-primary me-2"></i>
                                            <p class="mb-0">
                                                {{ $exam->schoolclass->schoolclass ?? 'N/A' }}
                                                @if($exam->schoolclass && $exam->schoolclass->armRelation)
                                                    <span class="badge bg-primary ms-1">{{ $exam->schoolclass->armRelation->arm }}</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-4">
                                    <div class="mt-4">
                                        <h5 class="font-size-14 mb-1">Time Details</h5>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="ph-clock text-primary me-2"></i>
                                            <span class="fw-medium">{{ $exam->duration }} mins</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="ph-calendar-blank text-primary me-2"></i>
                                            <small>{{ $exam->start_time->format('M d, Y') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-2 fs-2">
                                        <i class="ph-question"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Questions</p>
                                    <h4 class="fs-4 mb-0">{{ $questions->count() }}</h4>
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
                                        <i class="ph-check-circle"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Marks</p>
                                    <h4 class="fs-4 mb-0">{{ number_format($questions->sum('marks'), 1) }}</h4>
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
                                        <i class="ph-list-numbers"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Avg. Marks</p>
                                    <h4 class="fs-4 mb-0">
                                        {{ $questions->count() > 0 ? number_format($questions->avg('marks'), 1) : '0.0' }}
                                    </h4>
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
                                        <i class="ph-graduation-cap"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Status</p>
                                    <h4 class="fs-4 mb-0">
                                        @if($exam->is_published)
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions Section -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">Exam Questions</h5>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" id="add-question-btn" data-exam-id="{{ $exam->id }}">
                                        <i class="ph-plus-circle me-1"></i> Add Question
                                    </button>
                                    <button class="btn btn-subtle-info btn-sm" id="toggleViewBtn">
                                        <i class="ph-list ph-sm me-1"></i> Toggle View
                                    </button>
                                    <a href="{{ route('exams.index') }}" class="btn btn-subtle-secondary btn-sm">
                                        <i class="ph-arrow-left ph-sm me-1"></i> Back to Exams
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($questions->count() > 0)
                                <!-- Grid View (Default) -->
                                <div id="gridView" class="row g-4">
                                    @foreach($questions as $index => $question)
                                        <div class="col-xl-4 col-lg-6" data-question-id="{{ $question->id }}">
                                            <div class="card question-card h-100 border">
                                                <div class="card-body">
                                                    <!-- Question Header -->
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-primary rounded-pill p-2 me-2">
                                                                <span class="fw-bold fs-5">{{ $index + 1 }}</span>
                                                            </span>
                                                            <span class="badge
                                                                @if($question->type === 'mcq') bg-primary
                                                                @elseif($question->type === 'true_false') bg-info
                                                                @else bg-success @endif">
                                                                {{ ucfirst(str_replace('_', ' ', $question->type)) }}
                                                            </span>
                                                            <span class="badge bg-warning-subtle text-warning ms-2">
                                                                <i class="ph-star ph-xs me-1"></i>{{ $question->marks }} pts
                                                            </span>
                                                        </div>
                                                        <div class="d-flex gap-1">
                                                            @if($question->image)
                                                                <button class="btn btn-sm btn-subtle-primary view-image-btn"
                                                                        data-image="{{ asset('storage/' . $question->image) }}">
                                                                    <i class="ph-image ph-sm"></i>
                                                                </button>
                                                            @endif
                                                            <button class="btn btn-sm btn-subtle-secondary edit-question-btn"
                                                                    data-question-id="{{ $question->id }}">
                                                                <i class="ph-pencil ph-sm"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-subtle-danger delete-question-btn"
                                                                    data-question-id="{{ $question->id }}">
                                                                <i class="ph-trash ph-sm"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Question Text -->
                                                    <div class="mb-3">
                                                        <div class="fw-medium text-dark mb-1">Question:</div>
                                                        <div class="question-text">{!! $question->question_text !!}</div>
                                                    </div>

                                                    <!-- Options/Answer -->
                                                    <div class="mb-3">
                                                        @if($question->type === 'mcq')
                                                            <div class="fw-medium text-dark mb-2">Options:</div>
                                                            <div class="options-container">
                                                                @php
                                                                    $labels = ['A', 'B', 'C', 'D', 'E'];
                                                                    $optionIndex = 0;
                                                                @endphp
                                                                @foreach($question->options as $option)
                                                                    @if($option->option_text)
                                                                        <div class="option-item d-flex align-items-center mb-2 p-2 rounded
                                                                            {{ $option->is_correct ? 'bg-success-subtle border border-success' : 'bg-light' }}">
                                                                            <span class="badge
                                                                                {{ $option->is_correct ? 'bg-success' : 'bg-secondary' }}
                                                                                me-2">{{ $labels[$optionIndex] }}</span>
                                                                            <span class="{{ $option->is_correct ? 'text-success fw-medium' : '' }}">
                                                                                {{ $option->option_text }}
                                                                            </span>
                                                                            @if($option->is_correct)
                                                                                <i class="ph-check-circle-fill text-success ms-auto"></i>
                                                                            @endif
                                                                        </div>
                                                                        @php $optionIndex++; @endphp
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @elseif($question->type === 'true_false')
                                                            <div class="fw-medium text-dark mb-2">Correct Answer:</div>
                                                            <div class="d-flex align-items-center">
                                                                @foreach($question->options as $option)
                                                                    @if($option->is_correct)
                                                                        <span class="badge bg-success fs-6 px-3 py-2">
                                                                            <i class="ph-check-circle ph-sm me-2"></i>
                                                                            {{ ucfirst($option->label) }}
                                                                        </span>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @elseif($question->type === 'short_answer')
                                                            <div class="fw-medium text-dark mb-2">Expected Answer:</div>
                                                            <div class="bg-light p-3 rounded">
                                                                @foreach($question->options as $option)
                                                                    @if($option->is_correct)
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="ph-check-circle text-success me-2"></i>
                                                                            <span class="text-success fw-medium">{{ $option->option_text }}</span>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <!-- Footer -->
                                                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                                        <div>
                                                            @if($question->is_reusable)
                                                                <span class="badge bg-info-subtle text-info">
                                                                    <i class="ph-repeat ph-xs me-1"></i>Reusable
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="ph-clock ph-xs me-1"></i>
                                                            {{ $question->created_at->diffForHumans() }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Table View (Hidden by Default) -->
                                <div id="tableView" class="d-none">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" style="width: 50px;">#</th>
                                                    <th style="min-width: 300px;">Question</th>
                                                    <th class="text-center" style="width: 100px;">Type</th>
                                                    <th class="text-center" style="width: 80px;">Marks</th>
                                                    <th style="min-width: 200px;">Correct Answer</th>
                                                    <th class="text-center" style="width: 100px;">Image</th>
                                                    <th class="text-center" style="width: 120px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($questions as $index => $question)
                                                    <tr data-question-id="{{ $question->id }}">
                                                        <td class="text-center">
                                                            <span class="badge bg-primary rounded-pill p-2">
                                                                <span class="fw-bold">{{ $index + 1 }}</span>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="fw-medium text-dark mb-1">{!! Str::limit($question->question_text, 150) !!}</div>
                                                            @if($question->is_reusable)
                                                                <span class="badge bg-info-subtle text-info fs-10">
                                                                    <i class="ph-repeat ph-xs me-1"></i>Reusable
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge
                                                                @if($question->type === 'mcq') bg-primary
                                                                @elseif($question->type === 'true_false') bg-info
                                                                @else bg-success @endif">
                                                                {{ strtoupper(substr($question->type, 0, 2)) }}
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="fw-bold text-warning">{{ $question->marks }}</span>
                                                        </td>
                                                        <td>
                                                            @if($question->type === 'mcq')
                                                                @foreach($question->options as $option)
                                                                    @if($option->is_correct)
                                                                        @php
                                                                            $labels = ['A', 'B', 'C', 'D', 'E'];
                                                                            $optionIndex = 0;
                                                                        @endphp
                                                                        @foreach($question->options as $opt)
                                                                            @if($opt->id === $option->id)
                                                                                <span class="badge bg-success">Option {{ $labels[$optionIndex] }}</span>
                                                                            @endif
                                                                            @if($opt->option_text) @php $optionIndex++; @endphp @endif
                                                                        @endforeach
                                                                        <div class="text-muted fs-12 mt-1">{{ Str::limit($option->option_text, 50) }}</div>
                                                                    @endif
                                                                @endforeach
                                                            @elseif($question->type === 'true_false')
                                                                @foreach($question->options as $option)
                                                                    @if($option->is_correct)
                                                                        <span class="badge bg-success">{{ ucfirst($option->label) }}</span>
                                                                    @endif
                                                                @endforeach
                                                            @elseif($question->type === 'short_answer')
                                                                @foreach($question->options as $option)
                                                                    @if($option->is_correct)
                                                                        <div class="text-success fw-medium">{{ Str::limit($option->option_text, 50) }}</div>
                                                                    @endif
                                                                @endforeach
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($question->image)
                                                                <button class="btn btn-sm btn-subtle-primary view-image-btn"
                                                                        data-image="{{ asset('storage/' . $question->image) }}">
                                                                    <i class="ph-image ph-sm"></i> View
                                                                </button>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-flex justify-content-center gap-1">
                                                                <button class="btn btn-sm btn-subtle-secondary edit-question-btn"
                                                                        data-question-id="{{ $question->id }}">
                                                                    <i class="ph-pencil"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-subtle-danger delete-question-btn"
                                                                        data-question-id="{{ $question->id }}">
                                                                    <i class="ph-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Question Type Distribution -->
                                <div class="row mt-4">
                                    <div class="col-lg-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Question Type Distribution</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    @php
                                                        $typeCounts = $questions->groupBy('type')->map->count();
                                                    @endphp
                                                    @foreach($typeCounts as $type => $count)
                                                        <div class="col-md-4 mb-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-shrink-0">
                                                                    @if($type === 'mcq')
                                                                        <span class="avatar avatar-sm bg-primary-subtle text-primary rounded">
                                                                            <i class="ph-list-dashes ph-sm"></i>
                                                                        </span>
                                                                    @elseif($type === 'true_false')
                                                                        <span class="avatar avatar-sm bg-info-subtle text-info rounded">
                                                                            <i class="ph-check ph-sm"></i>
                                                                        </span>
                                                                    @else
                                                                        <span class="avatar avatar-sm bg-success-subtle text-success rounded">
                                                                            <i class="ph-pencil-line ph-sm"></i>
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                <div class="flex-grow-1 ms-3">
                                                                    <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $type)) }}</h6>
                                                                    <p class="text-muted mb-0">{{ $count }} questions
                                                                        ({{ number_format(($count/$questions->count())*100, 1) }}%)</p>
                                                                </div>
                                                                <div class="flex-shrink-0">
                                                                    <span class="fw-bold fs-5">{{ $count }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @else
                                <!-- Empty State -->
                                <div class="text-center py-5">
                                    <div class="avatar-lg mx-auto mb-4">
                                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle display-5">
                                            <i class="ph-question ph-2x"></i>
                                        </div>
                                    </div>
                                    <h4 class="mb-3">No Questions Found</h4>
                                    <p class="text-muted mb-4">This exam doesn't have any questions yet. Start by adding some questions.</p>
                                    <button type="button" class="btn btn-primary" id="add-question-btn" data-exam-id="{{ $exam->id }}">
                                        <i class="ph-plus-circle me-2"></i> Add Your First Question
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question Form Modal -->
<div class="modal fade" id="questionFormModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span id="modal-title-text">Add</span> Question to Exam
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Exam Info -->
                <div id="exam-info" class="alert alert-info mb-4">
                    <div class="d-flex align-items-start">
                        <i class="ri-information-line fs-4 me-2"></i>
                        <div>
                            <strong>Exam:</strong> <span id="exam-title-text">{{ $exam->title }}</span><br>
                            <strong>Class:</strong> <span id="exam-class-text">{{ $exam->schoolclass->schoolclass ?? 'N/A' }} @if($exam->schoolclass && $exam->schoolclass->armRelation) ({{ $exam->schoolclass->armRelation->arm }}) @endif</span><br>
                            <strong>Subject:</strong> <span id="exam-subject-text">{{ $exam->subject->subject ?? 'No Subject' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Question Form -->
                <form id="question-form" enctype="multipart/form-data">
                    @csrf
                    <div id="method-field"></div>
                    <input type="hidden" name="question_id" id="question_id_field">
                    <input type="hidden" name="exam_id" id="exam_id_field" value="{{ $exam->id }}">

                    <!-- Quill Editor for Question Text -->
                    <div class="mb-3">
                        <label for="question_text" class="form-label required">Question Text</label>
                        <div id="question-text-editor" style="height: 200px;"></div>
                        <textarea name="question_text" id="question_text" style="display: none;" required></textarea>
                        <div class="form-text">Enter the main question text here. You can use formatting tools.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label required">Question Type</label>
                            <select name="type" id="type" class="form-control question-type" required>
                                <option value="" disabled selected>Select a type</option>
                                <option value="mcq">Multiple Choice (MCQ)</option>
                                <option value="true_false">True/False</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="marks" class="form-label required">Marks</label>
                            <input type="number" name="marks" id="marks" class="form-control" value="1" min="0.1" step="0.1" required>
                        </div>
                    </div>

                    <!-- Question Type Options -->
                    <div id="question-options-container">
                        <!-- Options will be dynamically loaded based on type -->
                    </div>

                    <!-- Image Upload -->
                    <div class="mb-3">
                        <label for="image" class="form-label">Upload Image (Optional)</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/*" />
                        <div id="image-preview" class="mt-3" style="display: none;">
                            <img id="preview-img" src="#" alt="Image Preview" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">
                            <button type="button" class="btn btn-sm btn-danger ms-2" id="remove-image">
                                <i class="ri-close-line"></i> Remove
                            </button>
                        </div>
                    </div>

                    <!-- Reusable Option -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_reusable" id="is_reusable" value="1">
                            <label class="form-check-label" for="is_reusable">
                                <strong>Mark as reusable question</strong>
                                <div class="form-text">This question can be reused in other exams</div>
                            </label>
                        </div>
                    </div>

                    <!-- Error Display -->
                    <div class="alert alert-danger d-none" id="form-errors">
                        <ul id="error-list" class="mb-0"></ul>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="save-question-btn" form="question-form">
                    <i class="ri-save-line me-1"></i> Save Question
                </button>
                <button type="button" class="btn btn-success" id="save-and-add-another-btn">
                    <i class="ri-add-line me-1"></i> Save & Add Another
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MCQ Options Template -->
<template id="mcq-options-template">
    <div class="mcq-options">
        <h6 class="fw-bold mb-3">Multiple Choice Options (Select at least 2)</h6>
        <div class="alert alert-warning">
            <i class="ri-alert-line me-2"></i> You must select one correct option
        </div>
        <div class="options-fields">
            <div class="option-field mb-3">
                <div class="d-flex align-items-center">
                    <label class="fw-semibold me-3">A:</label>
                    <input type="text" name="options[a][option_text]" class="form-control me-3" placeholder="Enter option A..." required />
                    <div class="form-check">
                        <input class="form-check-input is-correct" type="radio" name="correct_option" value="a" required />
                        <label class="form-check-label">Correct Answer</label>
                    </div>
                </div>
            </div>
            <div class="option-field mb-3">
                <div class="d-flex align-items-center">
                    <label class="fw-semibold me-3">B:</label>
                    <input type="text" name="options[b][option_text]" class="form-control me-3" placeholder="Enter option B..." required />
                    <div class="form-check">
                        <input class="form-check-input is-correct" type="radio" name="correct_option" value="b" />
                        <label class="form-check-label">Correct Answer</label>
                    </div>
                </div>
            </div>
            <div class="option-field mb-3">
                <div class="d-flex align-items-center">
                    <label class="fw-semibold me-3">C:</label>
                    <input type="text" name="options[c][option_text]" class="form-control me-3" placeholder="Enter option C..." />
                    <div class="form-check">
                        <input class="form-check-input is-correct" type="radio" name="correct_option" value="c" />
                        <label class="form-check-label">Correct Answer</label>
                    </div>
                </div>
            </div>
            <div class="option-field mb-3">
                <div class="d-flex align-items-center">
                    <label class="fw-semibold me-3">D:</label>
                    <input type="text" name="options[d][option_text]" class="form-control me-3" placeholder="Enter option D..." />
                    <div class="form-check">
                        <input class="form-check-input is-correct" type="radio" name="correct_option" value="d" />
                        <label class="form-check-label">Correct Answer</label>
                    </div>
                </div>
            </div>
            <div class="option-field mb-3">
                <div class="d-flex align-items-center">
                    <label class="fw-semibold me-3">E:</label>
                    <input type="text" name="options[e][option_text]" class="form-control me-3" placeholder="Enter option E..." />
                    <div class="form-check">
                        <input class="form-check-input is-correct" type="radio" name="correct_option" value="e" />
                        <label class="form-check-label">Correct Answer</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- True/False Options Template -->
<template id="tf-options-template">
    <div class="tf-options">
        <h6 class="fw-bold mb-3">True/False Options</h6>
        <div class="alert alert-warning">
            <i class="ri-alert-line me-2"></i> Select the correct answer
        </div>
        <div class="options-fields">
            <div class="option-field mb-3">
                <div class="d-flex align-items-center">
                    <input type="hidden" name="options[true][option_text]" value="True">
                    <label class="fw-semibold me-3">True</label>
                    <div class="form-check">
                        <input class="form-check-input is-correct" type="radio" name="correct_option" value="true" required />
                        <label class="form-check-label">Correct Answer</label>
                    </div>
                </div>
            </div>
            <div class="option-field mb-3">
                <div class="d-flex align-items-center">
                    <input type="hidden" name="options[false][option_text]" value="False">
                    <label class="fw-semibold me-3">False</label>
                    <div class="form-check">
                        <input class="form-check-input is-correct" type="radio" name="correct_option" value="false" />
                        <label class="form-check-label">Correct Answer</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Short Answer Options Template -->
<template id="sa-options-template">
    <div class="sa-options">
        <h6 class="fw-bold mb-3">Correct Answer</h6>
        <div class="alert alert-warning">
            <i class="ri-alert-line me-2"></i> Enter the correct answer for this short answer question
        </div>
        <div class="mb-3">
            <div id="short-answer-editor" style="height: 100px;"></div>
            <textarea name="options[answer][option_text]" id="short_answer_text" style="display: none;" required></textarea>

            <!-- Hidden radio button for short answer (always checked) -->
            <div style="display: none;">
                <input type="radio" name="correct_option" value="answer" checked />
            </div>
        </div>
    </div>
</template>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Question Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Question Image" class="img-fluid rounded">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="downloadImage" class="btn btn-primary" download>
                    <i class="ph-download-simple me-2"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Include Quill CSS and JS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<style>
.question-card {
    transition: all 0.3s ease;
    border-radius: 10px;
    overflow: hidden;
}

.question-card:hover {
    border-color: var(--bs-primary);
}

.question-text {
    line-height: 1.6;
    color: #333;
}

.option-item {
    transition: all 0.2s ease;
}

.option-item:hover {
    transform: translateX(5px);
    background-color: var(--bs-primary-subtle) !important;
}

.avatar-sm {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-animate {
    transition: transform 0.3s ease;
}

.card-animate:hover {
    transform: translateY(-5px);
}

.table-hover tbody tr:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.bg-primary-subtle {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}

.bg-success-subtle {
    background-color: rgba(var(--bs-success-rgb), 0.1) !important;
}

.bg-info-subtle {
    background-color: rgba(var(--bs-info-rgb), 0.1) !important;
}

.bg-warning-subtle {
    background-color: rgba(var(--bs-warning-rgb), 0.1) !important;
}

.fs-10 {
    font-size: 10px;
}

.fs-12 {
    font-size: 12px;
}

/* Print Styles */
@media print {
    .card-header,
    .btn,
    #toggleViewBtn,
    .view-image-btn,
    .edit-question-btn,
    .delete-question-btn {
        display: none !important;
    }

    .question-card {
        break-inside: avoid;
        border: 1px solid #ddd;
        box-shadow: none !important;
        transform: none !important;
    }
}

/* Quill Editor Styles */
.ql-editor {
    min-height: 150px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const examId = {{ $exam->id }};

    // Initialize Quill editor for question text
    window.questionQuill = new Quill('#question-text-editor', {
        theme: 'snow',
        placeholder: 'Enter your question here...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });

    // Initialize Quill for short answer (will be initialized when needed)
    window.shortAnswerQuill = null;

    // Initialize Bootstrap modal
    const questionModal = new bootstrap.Modal(document.getElementById('questionFormModal'));

    // Toggle between grid and table view
    const toggleViewBtn = document.getElementById('toggleViewBtn');
    const gridView = document.getElementById('gridView');
    const tableView = document.getElementById('tableView');

    if (toggleViewBtn) {
        toggleViewBtn.addEventListener('click', function() {
            if (gridView && tableView) {
                if (gridView.classList.contains('d-none')) {
                    // Show Grid View
                    gridView.classList.remove('d-none');
                    tableView.classList.add('d-none');
                    toggleViewBtn.innerHTML = '<i class="ph-list ph-sm me-1"></i> Switch to Table View';
                } else {
                    // Show Grid View
                    gridView.classList.add('d-none');
                    tableView.classList.remove('d-none');
                    toggleViewBtn.innerHTML = '<i class="ph-grid-four ph-sm me-1"></i> Switch to Card View';
                }
            }
        });
    }

    // Add Question button
    document.querySelectorAll('#add-question-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            resetQuestionForm();
            document.getElementById('modal-title-text').textContent = 'Add';
            document.getElementById('method-field').innerHTML = '';
            document.getElementById('question_id_field').value = '';
            document.getElementById('exam_id_field').value = examId;

            // Clear Quill editor
            if (window.questionQuill) {
                window.questionQuill.setText('');
            }

            questionModal.show();
        });
    });

    // Edit Question button
    document.querySelectorAll('.edit-question-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const questionId = this.dataset.questionId;
            loadQuestionForEdit(questionId);
        });
    });

    // Delete Question button
    document.querySelectorAll('.delete-question-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const questionId = this.dataset.questionId;
            deleteQuestion(questionId);
        });
    });

    // Question type change
    document.getElementById('type').addEventListener('change', function() {
        const type = this.value;
        const container = document.getElementById('question-options-container');

        // Clear container
        container.innerHTML = '';

        // Load appropriate template
        if (type === 'mcq') {
            const template = document.getElementById('mcq-options-template');
            container.appendChild(template.content.cloneNode(true));

            // Destroy short answer quill if it exists
            if (window.shortAnswerQuill) {
                window.shortAnswerQuill = null;
            }
        } else if (type === 'true_false') {
            const template = document.getElementById('tf-options-template');
            container.appendChild(template.content.cloneNode(true));

            // Destroy short answer quill if it exists
            if (window.shortAnswerQuill) {
                window.shortAnswerQuill = null;
            }
        } else if (type === 'short_answer') {
            const template = document.getElementById('sa-options-template');
            container.appendChild(template.content.cloneNode(true));

            // Initialize Quill for short answer
            setTimeout(() => {
                if (document.getElementById('short-answer-editor')) {
                    window.shortAnswerQuill = new Quill('#short-answer-editor', {
                        theme: 'snow',
                        placeholder: 'Enter the correct answer...',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                ['clean']
                            ]
                        }
                    });
                }
            }, 100);
        }
    });

    // Form submission
    document.getElementById('question-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Update hidden textarea with Quill content
        if (window.questionQuill) {
            const questionText = window.questionQuill.root.innerHTML;
            document.getElementById('question_text').value = questionText;
        }

        // Update short answer if type is short_answer
        if (document.getElementById('type').value === 'short_answer' && window.shortAnswerQuill) {
            const shortAnswerText = window.shortAnswerQuill.root.innerHTML;
            document.getElementById('short_answer_text').value = shortAnswerText;
        }

        saveQuestion(false);
    });

    // Save & Add Another
    document.getElementById('save-and-add-another-btn').addEventListener('click', function() {
        // Update hidden textarea with Quill content
        if (window.questionQuill) {
            const questionText = window.questionQuill.root.innerHTML;
            document.getElementById('question_text').value = questionText;
        }

        // Update short answer if type is short_answer
        if (document.getElementById('type').value === 'short_answer' && window.shortAnswerQuill) {
            const shortAnswerText = window.shortAnswerQuill.root.innerHTML;
            document.getElementById('short_answer_text').value = shortAnswerText;
        }

        saveQuestion(true);
    });

    // Image preview
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-img').src = e.target.result;
                document.getElementById('image-preview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove image
    document.getElementById('remove-image').addEventListener('click', function() {
        document.getElementById('image').value = '';
        document.getElementById('image-preview').style.display = 'none';
    });

    // Image Modal
    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
    const modalImage = document.getElementById('modalImage');
    const downloadLink = document.getElementById('downloadImage');

    document.querySelectorAll('.view-image-btn').forEach(button => {
        button.addEventListener('click', function() {
            const imageUrl = this.getAttribute('data-image');
            modalImage.src = imageUrl;
            downloadLink.href = imageUrl;
            imageModal.show();
        });
    });

    // Add animation to cards on hover
    document.querySelectorAll('.question-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
            this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Print functionality
    const printBtn = document.createElement('button');
    printBtn.className = 'btn btn-subtle-info btn-sm ms-2';
    printBtn.innerHTML = '<i class="ph-printer ph-sm me-1"></i> Print Questions';
    printBtn.addEventListener('click', function() {
        window.print();
    });

    const actionBar = document.querySelector('.card-header .flex-shrink-0 .d-flex');
    if (actionBar) {
        actionBar.appendChild(printBtn);
    }
});

function resetQuestionForm() {
    document.getElementById('question-form').reset();
    document.getElementById('question-options-container').innerHTML = '';
    document.getElementById('image-preview').style.display = 'none';
    document.getElementById('form-errors').classList.add('d-none');
    document.getElementById('error-list').innerHTML = '';
    document.getElementById('method-field').innerHTML = '';
    document.getElementById('question_id_field').value = '';
    document.getElementById('is_reusable').checked = false;

    // Clear Quill editors
    if (window.questionQuill) {
        window.questionQuill.setText('');
    }
    if (window.shortAnswerQuill) {
        window.shortAnswerQuill = null;
    }
}

function loadQuestionForEdit(questionId) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/questions/${questionId}/edit`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Reset form and populate with data
            resetQuestionForm();

            // Set form to edit mode
            document.getElementById('modal-title-text').textContent = 'Edit';
            document.getElementById('method-field').innerHTML = '@method("PUT")';
            document.getElementById('question_id_field').value = questionId;

            // Populate basic fields
            if (window.questionQuill) {
                window.questionQuill.root.innerHTML = data.question.question_text;
            }
            document.getElementById('type').value = data.question.type;
            document.getElementById('marks').value = data.question.marks;
            document.getElementById('is_reusable').checked = data.question.is_reusable;

            // Trigger type change to load options
            const typeEvent = new Event('change');
            document.getElementById('type').dispatchEvent(typeEvent);

            // Wait for options container to render then populate
            setTimeout(() => {
                if (data.question.type === 'mcq') {
                    // Populate MCQ options
                    data.options.forEach(option => {
                        const optionInput = document.querySelector(`input[name="options[${option.label}][option_text]"]`);
                        if (optionInput) {
                            optionInput.value = option.option_text;
                        }
                        if (option.is_correct) {
                            const radio = document.querySelector(`input[name="correct_option"][value="${option.label}"]`);
                            if (radio) radio.checked = true;
                        }
                    });
                } else if (data.question.type === 'true_false') {
                    // Populate True/False
                    data.options.forEach(option => {
                        if (option.is_correct) {
                            const radio = document.querySelector(`input[name="correct_option"][value="${option.label}"]`);
                            if (radio) radio.checked = true;
                        }
                    });
                } else if (data.question.type === 'short_answer') {
                    // Populate Short Answer with Quill
                    setTimeout(() => {
                        if (window.shortAnswerQuill) {
                            data.options.forEach(option => {
                                if (option.is_correct) {
                                    window.shortAnswerQuill.root.innerHTML = option.option_text;
                                }
                            });
                        }
                    }, 200);
                }

                // Show image preview if exists
                if (data.question.image) {
                    document.getElementById('preview-img').src = '/storage/' + data.question.image;
                    document.getElementById('image-preview').style.display = 'block';
                }
            }, 200);

            // Show modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('questionFormModal'));
            if (modal) modal.show();
            Swal.close();
        } else {
            throw new Error(data.message || 'Failed to load question');
        }
    })
    .catch(error => {
        console.error('Error loading question:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load question data. Please try again.',
            timer: 3000
        });
    });
}

function saveQuestion(andAddAnother = false) {
    const form = document.getElementById('question-form');
    const formData = new FormData(form);
    const questionId = document.getElementById('question_id_field').value;
    const isEdit = questionId !== '';

    let url = '{{ route("questions.store") }}';
    let method = 'POST';

    if (isEdit) {
        url = `/questions/${questionId}`;
        formData.append('_method', 'PUT');
    }

    // Disable submit buttons
    const saveBtn = document.getElementById('save-question-btn');
    const saveAnotherBtn = document.getElementById('save-and-add-another-btn');
    const originalText = saveBtn.innerHTML;
    const originalAnotherText = saveAnotherBtn.innerHTML;

    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    saveBtn.disabled = true;
    saveAnotherBtn.disabled = true;

    fetch(url, {
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
                text: data.message || 'Question saved successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                if (andAddAnother) {
                    // Reset form but keep exam ID
                    resetQuestionForm();
                    document.getElementById('exam_id_field').value = examId;
                    document.getElementById('modal-title-text').textContent = 'Add';
                    document.getElementById('method-field').innerHTML = '';

                    // Clear Quill
                    if (window.questionQuill) {
                        window.questionQuill.setText('');
                    }
                } else {
                    // Close modal and reload
                    const modal = bootstrap.Modal.getInstance(document.getElementById('questionFormModal'));
                    if (modal) modal.hide();
                    window.location.reload();
                }
            });
        } else {
            let errorMsg = 'An error occurred.';
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join('<br>');
                // Display errors in form
                const errorList = document.getElementById('error-list');
                errorList.innerHTML = '';
                Object.values(data.errors).flat().forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });
                document.getElementById('form-errors').classList.remove('d-none');
            } else if (data.message) {
                errorMsg = data.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg,
                    timer: 3000
                });
            }
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
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        saveAnotherBtn.innerHTML = originalAnotherText;
        saveAnotherBtn.disabled = false;
    });
}

function deleteQuestion(questionId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete this question!",
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

            fetch(`/questions/${questionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Question deleted successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to delete question.',
                        timer: 3000
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete question. Please try again.',
                    timer: 3000
                });
            });
        }
    });
}
</script>

@endsection
