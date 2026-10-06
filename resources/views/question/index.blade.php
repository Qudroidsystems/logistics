@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Questions for Exam: {{ $exam->title }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">Exams</a></li>
                                <li class="breadcrumb-item active">Questions</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="questionsList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search questions">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">Manage Questions <span class="badge bg-dark-subtle text-dark ms-1" id="total-badge">{{ $exam->questions->count() }}</span></h5>
                                    <p class="text-muted mb-0 fs-6">for {{ $exam->title }}</p>
                                    @if($exam->schoolclass)
                                        <p class="text-primary mb-0 fs-6">
                                            <i class="ri-building-line align-middle"></i>
                                            Class: {{ $exam->schoolclass->schoolclass }}
                                            @if($exam->schoolclass->armRelation && $exam->schoolclass->armRelation->arm)
                                                ({{ $exam->schoolclass->armRelation->arm }})
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addQuestionModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Add New Question</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_questions_table">
                                        <thead>
                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                                <th class="w-10px pe-2">
                                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                        <input class="form-check-input" type="checkbox" id="checkAll" />
                                                    </div>
                                                </th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="sn">SN</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="question">Question Text</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="type">Type</th>
                                                <th class="min-w-125px">Class</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="image">Image</th>
                                                <th class="min-w-125px sort cursor-pointer" data-sort="options">Options</th>
                                                <th class="min-w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600 list form-check-all">
                                            @php $i = 1 @endphp
                                            @forelse ($exam->questions as $question)
                                                <tr data-url="{{ route('questions.destroy', $question->id) }}">
                                                    <td class="id" data-id="{{ $question->id }}">
                                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" />
                                                        </div>
                                                    </td>
                                                    <td class="sn">{{ $i++ }}</td>
                                                    <td class="question">{{ Str::limit(strip_tags($question->question_text), 50) }}</td>
                                                    <td class="type">{{ ucfirst(str_replace('_', ' ', $question->type)) }}</td>
                                                    <td class="class-info">
                                                        @if($exam->schoolclass)
                                                            <span class="badge bg-info-subtle text-info">
                                                                {{ $exam->schoolclass->schoolclass }}
                                                                @if($exam->schoolclass->armRelation && $exam->schoolclass->armRelation->arm)
                                                                    ({{ $exam->schoolclass->armRelation->arm }})
                                                                @endif
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning">No Class</span>
                                                        @endif
                                                    </td>
                                                    <td class="image">
                                                        @if ($question->image)
                                                            <img src="{{ asset('storage/' . $question->image) }}" alt="Question Image" style="max-width: 50px; max-height: 50px;">
                                                        @else
                                                            <span class="text-muted">No Image</span>
                                                        @endif
                                                    </td>
                                                    <td class="options">{{ $question->options->count() }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            <li>
                                                                <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm view-item-btn" data-id="{{ $question->id }}"><i class="ph-eye"></i></a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{ $question->id }}"><i class="ph-pencil"></i></a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $question->id }}" data-url="{{ route('questions.destroy', $question->id) }}"><i class="ph-trash"></i></a>
                                                            </li>
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="noresult" style="display: block;">No questions found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span id="showing-count" class="fw-semibold">{{ $exam->questions->count() }}</span> of <span id="total-count" class="fw-semibold">{{ $exam->questions->count() }}</span> Results
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Question Modal -->
            <div id="addQuestionModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Question</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="add-question-form" class="tablelist-form" autocomplete="off" enctype="multipart/form-data" novalidate>
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" name="exam_id" value="{{ $exam->id }}">
                                <div class="alert alert-info mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ri-information-line fs-4 me-2"></i>
                                        <div>
                                            <strong>Exam:</strong> {{ $exam->title }}<br>
                                            @if($exam->schoolclass)
                                                <strong>Class:</strong> {{ $exam->schoolclass->schoolclass }}
                                                @if($exam->schoolclass->armRelation && $exam->schoolclass->armRelation->arm)
                                                    ({{ $exam->schoolclass->armRelation->arm }})
                                                @endif
                                            @else
                                                <strong>Class:</strong> <span class="text-warning">Not assigned</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div id="questions-container">
                                    <div class="question-field mb-7 border p-5 rounded">
                                        <div class="mb-3">
                                            <label for="question_text" class="form-label">Question Text</label>
                                            <div id="add-question-editor"></div>
                                            <textarea name="question_text" id="question_text" style="display: none;"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="type" class="form-label required">Question Type</label>
                                            <select name="type" id="type" class="form-control question-type" required>
                                                <option value="" disabled selected>Select a type</option>
                                                <option value="mcq">Multiple Choice (MCQ)</option>
                                                <option value="true_false">True/False</option>
                                                <option value="short_answer">Short Answer</option>
                                            </select>
                                        </div>

                                        <!-- Image Upload -->
                                        <div class="mb-3">
                                            <label for="image" class="form-label">Upload Image (Optional)</label>
                                            <input type="file" name="image" id="image" class="form-control" accept="image/*" />
                                            <div id="image-preview" class="mt-3" style="display: none;">
                                                <img id="preview-img" src="#" alt="Image Preview" style="max-width: 200px; max-height: 200px;">
                                            </div>
                                        </div>

                                        <!-- MCQ Options -->
                                        <div class="mcq-options options-container" style="display: none;">
                                            <h6 class="fw-bold mb-3">Options (A-E, at least 2 required)</h6>
                                            <div class="options-fields">
                                                <div class="option-field mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <label class="fw-semibold me-3">A:</label>
                                                        <input type="text" name="options[a][option_text]" class="form-control me-3" placeholder="Enter option A..." />
                                                        <div class="form-check">
                                                            <input class="form-check-input is-correct" type="radio" name="correct_option" value="a" />
                                                            <label class="form-check-label">Correct</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="option-field mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <label class="fw-semibold me-3">B:</label>
                                                        <input type="text" name="options[b][option_text]" class="form-control me-3" placeholder="Enter option B..." />
                                                        <div class="form-check">
                                                            <input class="form-check-input is-correct" type="radio" name="correct_option" value="b" />
                                                            <label class="form-check-label">Correct</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="option-field mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <label class="fw-semibold me-3">C:</label>
                                                        <input type="text" name="options[c][option_text]" class="form-control me-3" placeholder="Enter option C..." />
                                                        <div class="form-check">
                                                            <input class="form-check-input is-correct" type="radio" name="correct_option" value="c" />
                                                            <label class="form-check-label">Correct</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="option-field mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <label class="fw-semibold me-3">D:</label>
                                                        <input type="text" name="options[d][option_text]" class="form-control me-3" placeholder="Enter option D..." />
                                                        <div class="form-check">
                                                            <input class="form-check-input is-correct" type="radio" name="correct_option" value="d" />
                                                            <label class="form-check-label">Correct</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="option-field mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <label class="fw-semibold me-3">E:</label>
                                                        <input type="text" name="options[e][option_text]" class="form-control me-3" placeholder="Enter option E..." />
                                                        <div class="form-check">
                                                            <input class="form-check-input is-correct" type="radio" name="correct_option" value="e" />
                                                            <label class="form-check-label">Correct</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- True/False Options -->
                                        <div class="tf-options options-container" style="display: none;">
                                            <h6 class="fw-bold mb-3">Options</h6>
                                            <div class="options-fields">
                                                <div class="option-field mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <input type="hidden" name="options[true][option_text]" value="True">
                                                        <label class="fw-semibold me-3">True</label>
                                                        <div class="form-check">
                                                            <input class="form-check-input is-correct" type="radio" name="correct_option" value="true" />
                                                            <label class="form-check-label">Correct</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="option-field mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <input type="hidden" name="options[false][option_text]" value="False">
                                                        <label class="fw-semibold me-3">False</label>
                                                        <div class="form-check">
                                                            <input class="form-check-input is-correct" type="radio" name="correct_option" value="false" />
                                                            <label class="form-check-label">Correct</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Short Answer Correct Answer -->
                                        <div class="sa-options options-container" style="display: none;">
                                            <h6 class="fw-bold mb-3">Correct Answer</h6>
                                            <div class="mb-3">
                                                <div id="add-sa-editor"></div>
                                                <textarea name="options[answer][option_text]" id="add_sa_answer" style="display: none;"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="correct_option" id="correct_option_hidden" value="">
                                <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Question Modal -->
            <div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Question</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="edit-question-form" class="tablelist-form" autocomplete="off" enctype="multipart/form-data" novalidate>
                            @method('PUT')
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <input type="hidden" name="exam_id" id="edit_exam_id">
                                <div class="alert alert-info mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ri-information-line fs-4 me-2"></i>
                                        <div>
                                            <strong>Exam:</strong> {{ $exam->title }}<br>
                                            @if($exam->schoolclass)
                                                <strong>Class:</strong> {{ $exam->schoolclass->schoolclass }}
                                                @if($exam->schoolclass->armRelation && $exam->schoolclass->armRelation->arm)
                                                    ({{ $exam->schoolclass->armRelation->arm }})
                                                @endif
                                            @else
                                                <strong>Class:</strong> <span class="text-warning">Not assigned</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="question-field mb-7 border p-5 rounded">
                                    <div class="mb-3">
                                        <label for="edit_question_text" class="form-label">Question Text</label>
                                        <div id="edit-question-editor"></div>
                                        <textarea name="question_text" id="edit_question_text" style="display: none;"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_type" class="form-label required">Question Type</label>
                                        <select id="edit_type" class="form-control" disabled>
                                            <option value="mcq">Multiple Choice (MCQ)</option>
                                            <option value="true_false">True/False</option>
                                            <option value="short_answer">Short Answer</option>
                                        </select>
                                        <div class="form-text text-muted">Question type cannot be changed after creation.</div>
                                    </div>

                                    <!-- Image Upload -->
                                    <div class="mb-3">
                                        <label for="edit_image" class="form-label">Upload Image (Optional)</label>
                                        <div id="edit_current_image" class="mb-3">
                                            <!-- Current image will be displayed here -->
                                        </div>
                                        <input type="file" name="image" id="edit_image" class="form-control" accept="image/*" />
                                        <div id="edit_image_preview" class="mt-3" style="display: none;">
                                            <img id="edit_preview_img" src="#" alt="Image Preview" style="max-width: 200px; max-height: 200px;">
                                        </div>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image_checkbox" />
                                            <label class="form-check-label" for="remove_image_checkbox">Remove current image</label>
                                        </div>
                                    </div>

                                    <!-- MCQ Options -->
                                    <div id="edit_mcq_options" class="options-container" style="display: none;">
                                        <h6 class="fw-bold mb-3">Options (A-E, at least 2 required)</h6>
                                        <div class="options-fields">
                                            <!-- Option fields will be populated via JavaScript -->
                                        </div>
                                    </div>

                                    <!-- True/False Options -->
                                    <div id="edit_tf_options" class="options-container" style="display: none;">
                                        <h6 class="fw-bold mb-3">Options</h6>
                                        <div class="options-fields">
                                            <!-- Options will be populated via JavaScript -->
                                        </div>
                                    </div>

                                    <!-- Short Answer Correct Answer -->
                                    <div id="edit_sa_options" class="options-container" style="display: none;">
                                        <h6 class="fw-bold mb-3">Correct Answer</h6>
                                        <div class="mb-3">
                                            <div id="edit-sa-editor"></div>
                                            <textarea name="options[answer][option_text]" id="edit_sa_answer" style="display: none;"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="correct_option" id="edit_correct_option" value="">
                                <div class="alert alert-danger d-none" id="edit-alert-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="update-btn">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- View Question Modal -->
            <div id="viewModal" class="modal fade" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Question Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="viewModalBody">
                            <!-- Content loaded dynamically -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteRecordModal" class="modal fade" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <h4>Are you sure?</h4>
                            <p>You won't be able to revert this!</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger" id="delete-record">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page-content -->
    </div>
</div>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editors
    let addQuill = new Quill('#add-question-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, false] }],
                ['bold', 'italic'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    let editQuill = new Quill('#edit-question-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, false] }],
                ['bold', 'italic'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    let addSaQuill = new Quill('#add-sa-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, false] }],
                ['bold', 'italic'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['clean']
            ]
        }
    });

    let editSaQuill = new Quill('#edit-sa-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, false] }],
                ['bold', 'italic'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['clean']
            ]
        }
    });

    const tableBody = document.querySelector('#kt_questions_table tbody');
    const searchInput = document.querySelector('.search');
    const checkAll = document.getElementById('checkAll');
    const removeActions = document.getElementById('remove-actions');
    let searchTerm = '';
    let totalQuestions = {{ $exam->questions->count() }};

    // Get class information for the exam
    const examClass = @json($exam->schoolclass);
    const armName = examClass && examClass.arm_relation ? examClass.arm_relation.arm : '';
    const classDisplay = examClass ?
        `${examClass.schoolclass}${armName ? ' (' + armName + ')' : ''}` :
        'No Class';

    // Helper functions
    function stripTags(html) {
        return html.replace(/<[^>]*>/g, '');
    }

    function limitText(text, len = 50) {
        const stripped = stripTags(text);
        return stripped.length > len ? stripped.slice(0, len) + '...' : stripped;
    }

    function formatType(type) {
        return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ');
    }

    function createElementFromHTML(htmlString) {
        const div = document.createElement('div');
        div.innerHTML = htmlString.trim();
        return div.firstChild;
    }

    function updateQuestionCount(visibleCount = null) {
        if (visibleCount !== null) {
            document.getElementById('showing-count').textContent = visibleCount;
        }
        document.getElementById('total-count').textContent = totalQuestions;
        document.getElementById('total-badge').textContent = totalQuestions;
    }

    function addNewQuestionRow(question) {
        const currentSN = tableBody.querySelectorAll('tr:not(.noresult)').length + 1;
        const rowHtml = `
            <tr data-url="/questions/${question.id}">
                <td class="id" data-id="${question.id}">
                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" name="chk_child" />
                    </div>
                </td>
                <td class="sn">${currentSN}</td>
                <td class="question">${limitText(question.question_text)}</td>
                <td class="type">${formatType(question.type)}</td>
                <td class="class-info">
                    <span class="badge bg-info-subtle text-info">${classDisplay}</span>
                </td>
                <td class="image">
                    ${question.image ? `<img src="/storage/${question.image}" alt="Question Image" style="max-width: 50px; max-height: 50px;">` : '<span class="text-muted">No Image</span>'}
                </td>
                <td class="options">${question.options_count}</td>
                <td>
                    <ul class="d-flex gap-2 list-unstyled mb-0">
                        <li>
                            <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm view-item-btn" data-id="${question.id}"><i class="ph-eye"></i></a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="${question.id}"><i class="ph-pencil"></i></a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="${question.id}" data-url="/questions/${question.id}"><i class="ph-trash"></i></a>
                        </li>
                    </ul>
                </td>
            </tr>
        `;
        const noresultRow = tableBody.querySelector('.noresult');
        if (noresultRow) {
            noresultRow.parentNode.insertBefore(createElementFromHTML(rowHtml), noresultRow);
            noresultRow.remove();
        } else {
            tableBody.appendChild(createElementFromHTML(rowHtml));
        }
        totalQuestions++;
        updateQuestionCount();
        attachEventListeners(); // Re-attach for new buttons
        filterTable(); // Re-apply filter if active
    }

    function updateQuestionRow(question) {
        const row = document.querySelector(`tr[data-id="${question.id}"]`);
        if (!row) return;
        row.querySelector('.question').textContent = limitText(question.question_text);
        row.querySelector('.type').textContent = formatType(question.type);
        const imgCell = row.querySelector('.image');
        imgCell.innerHTML = question.image ? `<img src="/storage/${question.image}" alt="Question Image" style="max-width: 50px; max-height: 50px;">` : '<span class="text-muted">No Image</span>';
        row.querySelector('.options').textContent = question.options_count;
        row.dataset.url = `/questions/${question.id}`;
        row.querySelector('.remove-item-btn').dataset.url = `/questions/${question.id}`;
        filterTable(); // Re-apply filter if active
    }

    function removeRow(id) {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            row.remove();
        }
        if (tableBody.querySelectorAll('tr:not(.noresult)').length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" class="noresult" style="display: block;">No questions found</td></tr>';
        }
        totalQuestions--;
        updateQuestionCount();
        attachEventListeners(); // Re-attach if needed
        filterTable(); // Re-apply filter
    }

    function removeRows(ids) {
        ids.forEach(removeRow);
    }

    function addNoResultRow() {
        if (tableBody.querySelectorAll('tr:not(.noresult)').length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" class="noresult" style="display: block;">No questions found</td></tr>';
        }
    }

    // Handle checkboxes
    checkAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="chk_child"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
        toggleRemoveActions();
    });

    tableBody.addEventListener('change', function(e) {
        if (e.target.name === 'chk_child') {
            toggleRemoveActions();
        }
    });

    function toggleRemoveActions() {
        const checkedBoxes = document.querySelectorAll('input[name="chk_child"]:checked');
        removeActions.classList.toggle('d-none', checkedBoxes.length === 0);
    }

    // Search functionality
    searchInput.addEventListener('input', debounce(function(e) {
        searchTerm = e.target.value;
        filterTable();
    }, 300));

    function filterTable() {
        const rows = tableBody.querySelectorAll('tr:not(.noresult)');
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        updateQuestionCount(visibleCount);
    }

    // Function to update correct option hidden for edit modal
    function updateCorrectOptionEdit() {
        const hidden = document.getElementById('edit_correct_option');
        if (!hidden) return;
        const type = document.getElementById('edit_type').value;
        if (type === 'short_answer') {
            hidden.value = 'answer';
        } else {
            const selectedRadio = document.querySelector('#editModal .is-correct:checked');
            hidden.value = selectedRadio ? selectedRadio.value : '';
        }
    }

    // Attach event listeners for edit modal radios
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('is-correct') && e.target.closest('#editModal')) {
            updateCorrectOptionEdit();
        }
    });

    // Attach event listeners
    function attachEventListeners() {
        // View buttons
        document.querySelectorAll('.view-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const modal = new bootstrap.Modal(document.getElementById('viewModal'));
                modal.show();

                fetch(`/questions/${id}/details`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    let html = `
                        <div class="card">
                            <div class="card-header bg-light">
                                <h4 class="card-title fw-bold">Question</h4>
                                <div class="d-flex align-items-center mt-2">
                                    <span class="badge bg-primary me-2">Exam: ${data.exam_title}</span>
                                    <span class="badge bg-info">Class: ${classDisplay}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="fs-5 mb-4">${data.question_text}</div>`;

                    if (data.image) {
                        html += `<div class="text-center mb-4"><img src="/storage/${data.image}" alt="Question image" class="img-fluid rounded border" style="max-height: 300px;"></div>`;
                    }

                    let typeName = {
                        'mcq': 'Multiple Choice',
                        'true_false': 'True/False',
                        'short_answer': 'Short Answer'
                    }[data.type];

                    html += `<div class="badge bg-primary mb-4">Type: ${typeName}</div>`;

                    html += `<h5 class="fw-bold mb-3">Answer Options</h5>`;

                    if (data.type === 'true_false') {
                        html += `<div class="row g-3">`;
                        data.options.forEach(option => {
                            html += `
                                <div class="col-6">
                                    <div class="card ${option.is_correct ? 'bg-success text-white' : 'bg-light'} h-100">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-30px me-3">
                                                    <span class="symbol-label ${option.is_correct ? 'bg-light' : 'bg-primary'}">
                                                        <i class="ki-duotone ${option.is_correct ? 'ki-check' : 'ki-minus'} fs-2 ${option.is_correct ? 'text-success' : 'text-primary'}"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold d-block">${option.option_text}</span>
                                                    ${option.is_correct ? '<span class="text-light fs-7">Correct Answer</span>' : ''}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        html += `</div>`;
                    } else if (data.type === 'mcq') {
                        html += `<div class="row g-3">`;
                        const optionLabels = ['A', 'B', 'C', 'D', 'E'];
                        data.options.forEach((option, index) => {
                            const label = (option.label ? option.label.toUpperCase() : optionLabels[index]) || (index + 1);
                            html += `
                                <div class="col-md-6">
                                    <div class="card ${option.is_correct ? 'bg-success text-white' : 'bg-light'} h-100">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-30px me-3">
                                                    <span class="symbol-label ${option.is_correct ? 'bg-light' : 'bg-primary'}">
                                                        ${label}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold d-block">${option.option_text}</span>
                                                    ${option.is_correct ? '<span class="text-light fs-7">Correct Answer</span>' : ''}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        html += `</div>`;
                    } else if (data.type === 'short_answer') {
                        html += `
                            <div class="card bg-success text-white">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="symbol symbol-30px me-3">
                                            <span class="symbol-label bg-light">
                                                <i class="ki-duotone ki-check fs-2 text-success"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="fw-bold">Correct Answer</span>
                                        </div>
                                    </div>
                                    <div class="fs-5 ps-9">${data.options[0].option_text}</div>
                                </div>
                            </div>`;
                    }

                    html += `</div></div>`;
                    document.getElementById('viewModalBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('viewModalBody').innerHTML = '<div class="alert alert-danger">Error loading question details.</div>';
                });
            });
        });

        // Edit buttons
        document.querySelectorAll('.edit-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                fetch(`/questions/${id}/edit`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit-id-field').value = data.question.id;
                        document.getElementById('edit_exam_id').value = data.exam_id;
                        editQuill.root.innerHTML = data.question.question_text;
                        document.getElementById('edit_type').value = data.question.type; // For display only

                        // Handle image
                        const currentImageDiv = document.getElementById('edit_current_image');
                        currentImageDiv.innerHTML = data.question.image ?
                            `<img src="/storage/${data.question.image}" class="img-fluid mb-3" style="max-height: 150px;">` :
                            '<div class="text-muted">No image</div>';

                        // Reset hidden
                        document.getElementById('edit_correct_option').value = '';

                        // Handle options - first hide all and uncheck all radios
                        document.querySelectorAll('#editModal .options-container').forEach(c => c.style.display = 'none');
                        document.querySelectorAll('#editModal .is-correct').forEach(r => r.checked = false);

                        if (data.question.type === 'mcq') {
                            const container = document.getElementById('edit_mcq_options');
                            container.style.display = 'block';
                            const optionsFields = container.querySelector('.options-fields');
                            optionsFields.innerHTML = '';

                            const optionLetters = ['a', 'b', 'c', 'd', 'e'];
                            data.options.forEach((option, index) => {
                                if (index >= optionLetters.length) return;
                                const letter = option.label || optionLetters[index];
                                const upper = letter.toUpperCase();
                                const radioHtml = (option.is_correct ? 'checked' : '');
                                optionsFields.innerHTML += `
                                    <div class="option-field mb-3">
                                        <div class="d-flex align-items-center">
                                            <label class="fw-semibold me-3">${upper}:</label>
                                            <input type="text" name="options[${letter}][option_text]"
                                                class="form-control me-3" value="${option.option_text || ''}" />
                                            <div class="form-check">
                                                <input class="form-check-input is-correct" type="radio"
                                                    name="correct_option" value="${letter}" ${radioHtml} />
                                                <label class="form-check-label">Correct</label>
                                            </div>
                                        </div>
                                    </div>`;
                            });
                        } else if (data.question.type === 'true_false') {
                            const container = document.getElementById('edit_tf_options');
                            container.style.display = 'block';
                            const optionsFields = container.querySelector('.options-fields');
                            const trueOption = data.options.find(o => o.option_text === 'True');
                            const falseOption = data.options.find(o => o.option_text === 'False');
                            const trueCorrect = trueOption?.is_correct ? 'checked' : '';
                            const falseCorrect = falseOption?.is_correct ? 'checked' : '';
                            optionsFields.innerHTML = `
                                <div class="option-field mb-3">
                                    <div class="d-flex align-items-center">
                                        <input type="radio" class="is-correct" name="correct_option" value="true" ${trueCorrect}>
                                        <label class="ms-2">True</label>
                                    </div>
                                </div>
                                <div class="option-field mb-3">
                                    <div class="d-flex align-items-center">
                                        <input type="radio" class="is-correct" name="correct_option" value="false" ${falseCorrect}>
                                        <label class="ms-2">False</label>
                                    </div>
                                </div>`;
                        } else if (data.question.type === 'short_answer') {
                            const container = document.getElementById('edit_sa_options');
                            container.style.display = 'block';
                            editSaQuill.root.innerHTML = data.options[0]?.option_text || '';
                        }

                        // Update hidden after population
                        updateCorrectOptionEdit();

                        new bootstrap.Modal(document.getElementById('editModal')).show();
                    } else {
                        console.error('Failed to load question data:', data);
                        alert('Error loading question for edit. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching question data. Please check console for details.');
                });
            });
        });

        // Delete buttons
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const url = this.dataset.url;
                document.getElementById('delete-record').onclick = function() {
                    deleteQuestion(id, url);
                    bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal')).hide();
                };
                new bootstrap.Modal(document.getElementById('deleteRecordModal')).show();
            });
        });
    }

    // Initial attach
    attachEventListeners();
    updateQuestionCount();

    // Add form submission
    document.getElementById('add-question-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate Quill question text
        const questionText = addQuill.getText().trim();
        if (!questionText) {
            alert('Question text is required.');
            return;
        }

        // Update hidden textarea with Quill content
        document.getElementById('question_text').value = addQuill.root.innerHTML;

        // Validate short answer if applicable
        const type = document.getElementById('type').value;
        if (type === 'short_answer') {
            const saText = addSaQuill.getText().trim();
            if (!saText) {
                alert('Correct answer is required for Short Answer.');
                return;
            }
            document.getElementById('add_sa_answer').value = addSaQuill.root.innerHTML;
        }

        const formData = new FormData(this);
        const submitBtn = document.getElementById('add-btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Adding...';
        submitBtn.disabled = true;

        // Validate
        if (type === 'mcq') {
            const mcqInputs = document.querySelectorAll('.mcq-options input[type="text"]');
            const filledOptions = Array.from(mcqInputs).filter(input => input.value.trim() !== '').length;
            const correctSelected = document.querySelector('.mcq-options .is-correct:checked');
            if (filledOptions < 2) {
                alert('At least 2 MCQ options must be filled');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                return;
            }
            if (!correctSelected) {
                alert('Please select a correct option for MCQ');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                return;
            }
        } else if (type === 'true_false') {
            const correctSelected = document.querySelector('.tf-options .is-correct:checked');
            if (!correctSelected) {
                alert('Please select a correct option for True/False');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                return;
            }
        }

        fetch('{{ route("questions.store", $exam->id) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addQuestionModal'));
                modal.hide();
                this.reset();
                addQuill.root.innerHTML = '';
                if (type === 'short_answer') addSaQuill.root.innerHTML = '';
                showAlert('success', data.message);
                addNewQuestionRow(data.question);
            } else {
                showFormErrors(this, data.errors || {});
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while adding the question.');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });

    // Edit form submission
    document.getElementById('edit-question-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate Quill question text
        const questionText = editQuill.getText().trim();
        if (!questionText) {
            alert('Question text is required.');
            return;
        }

        // Update hidden textarea with Quill content
        document.getElementById('edit_question_text').value = editQuill.root.innerHTML;

        // Validate short answer if applicable
        const type = document.getElementById('edit_type').value;
        if (type === 'short_answer') {
            const saText = editSaQuill.getText().trim();
            if (!saText) {
                alert('Correct answer is required for Short Answer.');
                return;
            }
            document.getElementById('edit_sa_answer').value = editSaQuill.root.innerHTML;
        }

        const id = document.getElementById('edit-id-field').value;
        const formData = new FormData(this);
        formData.append('_method', 'PUT');
        const submitBtn = document.getElementById('update-btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Updating...';
        submitBtn.disabled = true;

        // Client-side validation for edit
        if (type === 'mcq') {
            const mcqInputs = document.querySelectorAll('#edit_mcq_options input[type="text"]');
            const filledOptions = Array.from(mcqInputs).filter(input => input.value.trim() !== '').length;
            const correctSelected = document.querySelector('#edit_mcq_options .is-correct:checked');
            if (filledOptions < 2) {
                alert('At least 2 MCQ options must be filled');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                return;
            }
            if (!correctSelected) {
                alert('Please select a correct option for MCQ');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                return;
            }
        } else if (type === 'true_false') {
            const correctSelected = document.querySelector('#edit_tf_options .is-correct:checked');
            if (!correctSelected) {
                alert('Please select a correct option for True/False');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                return;
            }
        }

        fetch(`/questions/${id}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                modal.hide();
                showAlert('success', data.message);
                updateQuestionRow(data.question);
            } else {
                showFormErrors(this, data.errors || {});
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while updating the question.');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });

    // Delete function
    function deleteQuestion(id, url) {
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                removeRow(id);
            } else {
                showAlert('danger', data.message || 'An error occurred while deleting.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while deleting.');
        });
    }

    // Bulk delete
    window.deleteMultiple = function() {
        const checked = document.querySelectorAll('input[name="chk_child"]:checked');
        if (checked.length === 0) return;

        document.getElementById('delete-record').onclick = function() {
            const ids = Array.from(checked).map(cb => cb.closest('td').dataset.id);
            bulkDeleteQuestions(ids);
            bootstrap.Modal.getInstance(document.getElementById('deleteRecordModal')).hide();
        };
        new bootstrap.Modal(document.getElementById('deleteRecordModal')).show();
    };

    function bulkDeleteQuestions(ids) {
        fetch('/questions/bulk-destroy', {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                removeRows(ids);
                addNoResultRow();
            } else {
                showAlert('danger', data.message || 'An error occurred while deleting.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while deleting.');
        });
    }

    // Helper functions
    function showFormErrors(form, errors) {
        const alert = form.querySelector('.alert');
        alert.classList.remove('d-none');
        alert.innerHTML = Object.values(errors).flat().join('<br>');
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.container-fluid').insertBefore(alertDiv, document.getElementById('questionsList'));
        setTimeout(() => alertDiv.remove(), 5000);
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Image preview for add modal
    const imageInput = document.getElementById('image');
    const previewContainer = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    if (imageInput && previewContainer && previewImg) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        });
    }

    // Question type toggle for add modal
    const typeSelect = document.getElementById('type');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            // Uncheck all radios when changing type
            document.querySelectorAll('.is-correct').forEach(r => r.checked = false);
            document.querySelectorAll('.options-container').forEach(container => {
                container.style.display = 'none';
            });

            if (this.value === 'mcq') {
                document.querySelector('.mcq-options').style.display = 'block';
            } else if (this.value === 'true_false') {
                document.querySelector('.tf-options').style.display = 'block';
            } else if (this.value === 'short_answer') {
                document.querySelector('.sa-options').style.display = 'block';
                addSaQuill.root.innerHTML = '';
            }

            updateCorrectOptionAdd();
        });

        function updateCorrectOptionAdd() {
            const correctOptionHidden = document.getElementById('correct_option_hidden');
            if (!correctOptionHidden) return;

            const type = typeSelect.value;
            if (type === 'short_answer') {
                correctOptionHidden.value = 'answer';
            } else {
                const selectedRadio = document.querySelector('.is-correct:checked');
                correctOptionHidden.value = selectedRadio ? selectedRadio.value : '';
            }
        }

        document.querySelectorAll('.is-correct').forEach(radio => {
            radio.addEventListener('change', updateCorrectOptionAdd);
        });
    }

    // Image preview for edit modal
    const editImageInput = document.getElementById('edit_image');
    const editPreviewContainer = document.getElementById('edit_image_preview');
    const editPreviewImg = document.getElementById('edit_preview_img');
    if (editImageInput && editPreviewContainer && editPreviewImg) {
        editImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    editPreviewImg.src = e.target.result;
                    editPreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                editPreviewContainer.style.display = 'none';
            }
        });
    }

    // CSRF token
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = '{{ csrf_token() }}';
        document.head.appendChild(meta);
    }
});
</script>

<style>
/* Additional styling for class column */
.badge.bg-info-subtle {
    border: 1px solid rgba(var(--bs-info-rgb), 0.2);
}

.badge.bg-warning-subtle {
    border: 1px solid rgba(var(--bs-warning-rgb), 0.2);
}

td.class-info {
    min-width: 120px;
}
</style>

@endsection
