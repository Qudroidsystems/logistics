<form id="kt_modal_edit_question_form" class="form" action="{{ route('questions.update', $question->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="exam_id" value="{{ $question->exam_id }}">

    <div class="fv-row mb-7">
        <label class="required fw-semibold fs-6 mb-2">Question Text</label>
        <textarea name="question_text" class="form-control form-control-solid" required>{{ $question->question_text }}</textarea>
    </div>

    <div class="fv-row mb-7">
        <label class="required fw-semibold fs-6 mb-2">Question Type</label>
        <select name="type" class="form-control form-control-solid question-type" required>
            <option value="mcq" {{ $question->type === 'mcq' ? 'selected' : '' }}>Multiple Choice (MCQ)</option>
            <option value="true_false" {{ $question->type === 'true_false' ? 'selected' : '' }}>True/False</option>
            <option value="short_answer" {{ $question->type === 'short_answer' ? 'selected' : '' }}>Short Answer</option>
        </select>
    </div>

    <!-- Add the same image upload and options fields as in your add form, pre-filled with existing data -->
    <!-- You'll need to adapt the options section based on question type, similar to the add form -->

    <div class="text-center pt-15">
        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Question</button>
    </div>
</form>