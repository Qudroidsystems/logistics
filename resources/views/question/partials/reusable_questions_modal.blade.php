<div class="modal fade" id="reusableQuestionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reusable Questions Bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="search-reusable" placeholder="Search reusable questions...">
                    </div>
                    <div class="col-md-6">
                        <select class="form-control" id="reusable-exam-filter">
                            <option value="">Filter by Exam</option>
                            @foreach($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="reusable-questions-list" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="add-reusable-questions" disabled>
                    <i class="ri-add-line me-1"></i> Add Selected to Exam
                </button>
            </div>
        </div>
    </div>
</div>
