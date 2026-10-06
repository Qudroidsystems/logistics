<div class="modal fade" id="questionFormModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-title-text">Add</span> Question to
                    <span id="selected-exam-title"></span>
                    <span id="multiple-exams-badge" class="badge bg-info ms-2" style="display: none;">Multiple Exams</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Exam Selection Info -->
                <div id="exam-selection-info" class="alert alert-info mb-4">
                    <div class="d-flex align-items-start">
                        <i class="ri-information-line fs-4 me-2"></i>
                        <div>
                            <div id="single-exam-info" style="display: none;">
                                <strong>Exam:</strong> <span id="exam-title-text"></span><br>
                                <strong>Class:</strong> <span id="exam-class-text"></span><br>
                                <strong>Subject:</strong> <span id="exam-subject-text"></span>
                            </div>
                            <div id="multiple-exams-info" style="display: none;">
                                <strong>Selected Exams:</strong> <span id="selected-exams-count"></span> exams<br>
                                <small class="text-muted">This question will be added to all selected exams.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Question Form -->
                <form id="question-form" enctype="multipart/form-data">
                    @csrf
                    <div id="method-field"></div>
                    <input type="hidden" name="question_id" id="question_id_field">
                    <div id="selected-exams-field"></div>

                    <div class="mb-3">
                        <label for="question_text" class="form-label required">Question Text</label>
                        <div id="question-text-editor" style="min-height: 150px;"></div>
                        <textarea name="question_text" id="question_text" style="display: none;" required></textarea>
                        <div class="form-text">Enter the main question text here.</div>
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
                <button type="button" class="btn btn-secondary" id="change-exam-selection">
                    <i class="ri-arrow-left-line me-1"></i> Change Exams
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
            <div id="short-answer-editor" style="min-height: 100px;"></div>
            <textarea name="options[answer][option_text]" id="short_answer_text" style="display: none;" required></textarea>

            <!-- Hidden radio button for short answer (always checked) -->
            <div style="display: none;">
                <input type="radio" name="correct_option" value="answer" checked />
            </div>
        </div>
    </div>
</template>
