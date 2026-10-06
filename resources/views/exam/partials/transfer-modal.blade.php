<!-- Assessment Transfer Modal -->
<div class="modal fade" id="assessmentTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="ph-arrow-circle-right me-2"></i>Transfer Exam Score to Assessment Sheet
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Student Info -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Student Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Name:</strong> <span id="studentName"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Admission No:</strong> <span id="studentAdmission"></span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <strong>Exam Score:</strong>
                                <span class="badge bg-primary" id="examScore"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="assessmentTransferForm">
                    @csrf
                    <input type="hidden" name="exam_id" value="{{ request()->route('examId') ?? '' }}">
                    <input type="hidden" name="student_id" id="studentId">
                    <input type="hidden" name="exam_score" id="examScoreHidden">
                    <input type="hidden" name="subjectclass_id" value="{{ $subjectclassid ?? '' }}">
                    <input type="hidden" name="term_id" value="{{ $termid ?? '' }}">
                    <input type="hidden" name="session_id" value="{{ $sessionid ?? '' }}">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Select Assessment <span class="text-danger">*</span></label>
                            <select class="form-select" id="assessmentSelect" name="assessment_id" required>
                                <option value="">Loading assessments...</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3" id="subAssessmentContainer" style="display: none;">
                            <label class="form-label fw-bold">Select Sub-Assessment</label>
                            <select class="form-select" id="subAssessmentSelect" name="sub_assessment_id">
                                <option value="">-- Select Sub-Assessment (Optional) --</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Maximum Score</label>
                            <input type="number" class="form-control" id="maxScore" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Score to Transfer <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="transferScore" name="score"
                                   step="0.1" min="0" required>
                            <div class="form-text text-danger" id="scoreValidationMsg"></div>
                        </div>

                        <input type="hidden" name="is_sub" id="isSub" value="0">
                    </div>
                </form>

                <!-- Loading Spinner -->
                <div id="assessmentLoader" style="display: none;" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading assessments...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="transferScoreBtn">Transfer Score</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="transferSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="text-success mb-3">
                    <i class="ph-check-circle" style="font-size: 48px;"></i>
                </div>
                <h5 class="mb-3">Score Transferred Successfully!</h5>
                <div id="successMessage" class="text-muted mb-3"></div>
                <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <i class="ph-x-circle" style="font-size: 48px;"></i>
                </div>
                <h5 class="mb-3">Transfer Failed</h5>
                <div id="errorMessage" class="text-muted mb-3"></div>
                <button type="button" class="btn btn-danger w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
