<div class="modal fade" id="moveExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="move-exam-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Move Questions to Another Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="selected-questions" name="question_ids">
                    <div class="mb-3">
                        <label class="form-label">Select Target Exam</label>
                        <select name="data[exam_id]" class="form-control" required id="target-exam-select">
                            <option value="">Select Exam</option>
                            @foreach($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="ri-alert-line me-2"></i>
                        This will move <span id="move-count">0</span> selected question(s) to the target exam.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Move Questions</button>
                </div>
            </form>
        </div>
    </div>
</div>
