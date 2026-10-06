<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('questions.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Questions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Exam</label>
                        <select name="exam_id" class="form-control" required>
                            <option value="">Select Exam</option>
                            @foreach($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload File</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
                        <div class="form-text">
                            Supported formats: CSV, Excel (.xlsx, .xls)<br>
                            <a href="{{ asset('templates/questions_import_template.xlsx') }}" download class="btn btn-sm btn-outline-primary">
                                <i class="ri-download-line me-1"></i> Download Template
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="ri-information-line me-2"></i>File Format Instructions:</h6>
                        <ul class="mb-0">
                            <li><strong>Column A:</strong> Question Text (Required)</li>
                            <li><strong>Column B:</strong> Type (mcq/true_false/short_answer)</li>
                            <li><strong>Column C:</strong> Correct Answer</li>
                            <li><strong>Column D-H:</strong> Options A-E (for MCQ)</li>
                            <li><strong>Column I:</strong> Marks (default: 1)</li>
                            <li><strong>Column J:</strong> Reusable (true/false)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import Questions</button>
                </div>
            </form>
        </div>
    </div>
</div>
