<div class="modal fade" id="examSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Exams for Questions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-start">
                        <i class="ri-information-line fs-4 me-2"></i>
                        <div>
                            <strong>Instructions:</strong>
                            <ul class="mb-0">
                                <li>Select one or more exams to add questions to</li>
                                <li>Questions will be added to all selected exams</li>
                                <li>Use search to filter exams by title or class</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" class="form-control" id="search-exams-input" placeholder="Search exams by title, class, or subject...">
                        <button class="btn btn-outline-secondary" type="button" id="clear-search-exams">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>

                <!-- Exams Grouped by Class -->
                <div id="exams-by-class-container" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading exams...</span>
                        </div>
                    </div>
                </div>

                <!-- Selected Exams Summary -->
                <div class="selected-exams-summary mt-4 p-3 bg-light rounded" id="selected-exams-summary" style="display: none;">
                    <h6 class="fw-bold mb-2">Selected Exams: <span id="selected-count">0</span></h6>
                    <div id="selected-exams-list" class="small"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cancel
                </button>
                <div class="form-check me-auto">
                    <input class="form-check-input" type="checkbox" id="select-all-exams-checkbox">
                    <label class="form-check-label" for="select-all-exams-checkbox">
                        Select all
                    </label>
                </div>
                <button type="button" class="btn btn-primary" id="proceed-to-question-form-btn" disabled>
                    <i class="ri-arrow-right-line me-1"></i> Proceed with <span id="selected-exam-count">0</span> Exam(s)
                </button>
            </div>
        </div>
    </div>
</div>
