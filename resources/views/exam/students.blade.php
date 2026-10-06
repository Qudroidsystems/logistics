@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
.transfer-score-btn {
    transition: all 0.3s ease;
}
.transfer-score-btn:hover {
    transform: scale(1.1);
}
.transfer-score-btn.btn-success {
    background-color: #28a745;
    color: white;
}
#assessmentLoader {
    min-height: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.score-input.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
#successMessage small {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 0.85rem;
}
.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}
</style>

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
                                <li class="breadcrumb-item active">Students</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    Students <span class="badge bg-dark-subtle text-dark ms-1" id="students-count">{{ $students->total() }}</span>
                                    <span class="ms-3 text-muted">
                                        <i class="ph-info me-1"></i>
                                        Click <i class="ph-arrow-right text-success"></i> to transfer scores to assessment sheet
                                    </span>
                                </h5>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="bulkTransferBtn" data-bs-toggle="modal" data-bs-target="#termSessionModal">
                                    <i class="ph-arrow-circle-right me-1"></i> Bulk Transfer to Assessment
                                </button>
                                <div>
                                    <span class="text-muted me-2">Total Questions: <strong>{{ $examTotals['total_questions'] ?? 0 }}</strong></span>
                                    <span class="text-muted">Total Marks: <strong>{{ number_format($examTotals['total_marks'] ?? 0, 1) }}</strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($assignedClasses->count() > 1)
                            <div class="mb-3">
                                <label class="form-label">Filter by Class:</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('exams.students', $exam->id) }}"
                                       class="btn btn-sm {{ !$classId ? 'btn-primary' : 'btn-outline-primary' }}">
                                        All Classes ({{ $assignedClasses->count() }})
                                    </a>
                                    @foreach($assignedClasses as $class)
                                        @php
                                            $armName = '';
                                            if ($class->arm) {
                                                if (is_numeric($class->arm)) {
                                                    $armRecord = DB::table('schoolarm')->where('id', $class->arm)->first();
                                                    $armName = $armRecord ? ' - ' . $armRecord->arm : '';
                                                } else {
                                                    $armName = ' - ' . $class->arm;
                                                }
                                            }
                                        @endphp
                                        <a href="{{ route('exams.students', ['exam' => $exam->id, 'class_id' => $class->schoolclassID]) }}"
                                           class="btn btn-sm {{ $classId == $class->schoolclassID ? 'btn-primary' : 'btn-outline-primary' }}">
                                            {{ $class->schoolclass }}{{ $armName }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-50px">SN</th>
                                            <th class="min-w-80px">Photo</th>
                                            <th class="min-w-150px">Student Name</th>
                                            <th class="min-w-100px">Admission No</th>
                                            <th class="min-w-100px">Total Ques</th>
                                            <th class="min-w-80px">Attempted</th>
                                            <th class="min-w-70px">Correct</th>
                                            <th class="min-w-80px">Incorrect</th>
                                            <th class="min-w-100px">Not Attempted</th>
                                            <th class="min-w-120px">Score (Marks)</th>
                                            <th class="min-w-250px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-tbody">
                                        @php
                                            $i = ($students->currentPage() - 1) * $students->perPage();
                                            $hasStudents = false;
                                        @endphp
                                        @forelse ($students as $student)
                                            @if($student)
                                                @php
                                                    $totalQuestions = $examTotals['total_questions'] ?? 0;
                                                    $totalMarks = $examTotals['total_marks'] ?? 0;
                                                    $attempted = $student->attempted_questions ?? 0;
                                                    $correct = $student->correct_count ?? 0;
                                                    $incorrect = $student->incorrect ?? 0;
                                                    $notAttempted = $totalQuestions - $attempted;
                                                    $score = $student->marks_earned ?? $student->score ?? 0;
                                                    $studentTotalMarks = $student->total_marks ?? $totalMarks;
                                                    $hasStudents = true;
                                                @endphp
                                                <tr data-student-id="{{ $student->id }}">
                                                    <td class="sn-number">{{ ++$i }}</td>
                                                    <td>
                                                        <img src="{{ $student->picture ? asset('storage/student_avatars/' . basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                                             alt="{{ $student->lastname }} {{ $student->firstname }}"
                                                             class="rounded-circle avatar-xs"
                                                             onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                    </td>
                                                    <td>{{ $student->lastname }} {{ $student->firstname }}</td>
                                                    <td>{{ $student->admissionNo }}</td>
                                                    <td>
                                                        @if($student->attempt_status === 'in_progress')
                                                            <span class="badge bg-warning text-dark">In Progress</span>
                                                        @else
                                                            {{ $totalQuestions }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($student->attempt_status === 'in_progress')
                                                            <span class="badge bg-warning text-dark">In Progress</span>
                                                        @else
                                                            {{ $attempted }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($student->attempt_status === 'in_progress')
                                                            <span class="badge bg-warning text-dark">In Progress</span>
                                                        @else
                                                            <span class="badge bg-success">{{ $correct }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($student->attempt_status === 'in_progress')
                                                            <span class="badge bg-warning text-dark">In Progress</span>
                                                        @else
                                                            <span class="badge bg-danger">{{ $incorrect }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($student->attempt_status === 'in_progress')
                                                            <span class="badge bg-warning text-dark">In Progress</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ $notAttempted }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($student->attempt_status === 'in_progress')
                                                            <span class="badge bg-info">Ongoing</span>
                                                        @else
                                                            <span class="badge bg-primary">
                                                                {{ number_format($score, 1) }} / {{ number_format($studentTotalMarks, 1) }}
                                                                @if($studentTotalMarks > 0)
                                                                    ({{ number_format(($score/$studentTotalMarks)*100, 1) }}%)
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </td>
                                                   <td>
    <div class="btn-group" role="group">
        @if($student->attempt_status === 'completed')
            <a href="{{ route('exams.student.answers', [$exam->id, $student->id]) }}"
               class="btn btn-subtle-info btn-icon btn-sm"
               data-bs-toggle="tooltip"
               data-bs-placement="top"
               title="View Answers">
                <i class="ph-eye"></i>
            </a>
            <!-- PDF Download Button -->
            <a href="{{ route('exams.generate-pdf', ['exam' => $exam->id, 'student' => $student->id]) }}"
               class="btn btn-subtle-danger btn-icon btn-sm"
               data-bs-toggle="tooltip"
               data-bs-placement="top"
               title="Download Question Paper with Answers"
               target="_blank">
                <i class="ph-file-pdf"></i>
            </a>
        @endif
        <button type="button"
                class="btn btn-subtle-danger btn-icon btn-sm delete-attempt"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="Delete Attempt (allows retake)"
                data-exam-id="{{ $exam->id }}"
                data-student-id="{{ $student->id }}"
                data-student-name="{{ $student->lastname }} {{ $student->firstname }}"
                data-delete-url="{{ route('exams.student.attempt.delete', ['exam' => $exam->id, 'student' => $student->id]) }}">
            <i class="ph-trash-simple"></i>
        </button>
    </div>
</td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr class="empty-row">
                                                <td colspan="11" class="text-center">No students found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($hasStudents)
                            <div class="row mt-3 align-items-center">
                                <div class="col-sm">
                                    <div class="text-muted text-center text-sm-start" id="pagination-text">
                                        Showing <span class="fw-semibold">{{ $students->firstItem() ?? 0 }}</span> to <span class="fw-semibold">{{ $students->lastItem() ?? 0 }}</span> of <span class="fw-semibold">{{ $students->total() }}</span> Results
                                    </div>
                                </div>
                                <div class="col-sm-auto mt-3 mt-sm-0">
                                    <div class="pagination-wrap hstack gap-2 justify-content-center">
                                        {{ $students->appends(request()->query())->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Term/Session Selection Modal -->
<div class="modal fade" id="termSessionModal" tabindex="-1" aria-labelledby="termSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="termSessionModalLabel">
                    <i class="ph-calendar me-2"></i>Select Term and Session
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="ph-info me-2"></i>
                    Select the term and session to view subjects for bulk transfer.
                </div>

                <form id="termSessionForm">
                    @csrf
                    <div class="mb-3">
                        <label for="modalSession" class="form-label fw-bold">Session <span class="text-danger">*</span></label>
                        <select class="form-select" id="modalSession" name="sessionid" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="modalTerm" class="form-label fw-bold">Term <span class="text-danger">*</span></label>
                        <select class="form-select" id="modalTerm" name="termid" required>
                            <option value="">Select Term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ $term->term }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div id="termSessionLoader" style="display: none;" class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Fetching your subjects...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ph-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="proceedToSubjectsBtn">
                    <i class="ph-arrow-right me-1"></i>Proceed to Subjects
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assessment Transfer Modal -->
<div class="modal fade" id="assessmentTransferModal" tabindex="-1" aria-labelledby="assessmentTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="assessmentTransferModalLabel">
                    <i class="ph-arrow-circle-right me-2"></i>Transfer Exam Score to Assessment Sheet
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Exam Info -->
                <div class="alert alert-info d-flex align-items-center mb-4">
                    <i class="ph-info fs-4 me-2"></i>
                    <div>
                        <strong>Exam:</strong> {{ $exam->title }} ({{ $exam->subject->subject ?? 'N/A' }})<br>
                        <strong>Class:</strong> {{ $exam->schoolclass->schoolclass ?? 'N/A' }} {{ $exam->schoolclass->arm ?? '' }} |
                        <strong>Term:</strong> {{ $term->term ?? 'N/A' }} |
                        <strong>Session:</strong> {{ $session->session ?? 'N/A' }}
                    </div>
                </div>

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
                    <input type="hidden" name="exam_id" id="examId" value="{{ $exam->id }}">
                    <input type="hidden" name="student_id" id="studentId">
                    <input type="hidden" name="exam_score" id="examScoreHidden">
                    <input type="hidden" name="assessment_id" id="assessmentId" value="">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="assessmentSelect" class="form-label fw-bold">Select Assessment <span class="text-danger">*</span></label>
                            <select class="form-select" id="assessmentSelect" name="assessment_id" required>
                                <option value="">Loading assessments...</option>
                            </select>
                            <div class="form-text">Choose the assessment to transfer this score to</div>
                        </div>

                        <div class="col-md-12 mb-3" id="subAssessmentContainer" style="display: none;">
                            <label for="subAssessmentSelect" class="form-label fw-bold">Select Sub-Assessment</label>
                            <select class="form-select" id="subAssessmentSelect" name="sub_assessment_id">
                                <option value="">-- Select Sub-Assessment (Optional) --</option>
                            </select>
                            <div class="form-text">If this assessment has sub-components, you can select a specific one</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="maxScore" class="form-label fw-bold">Maximum Score</label>
                            <input type="number" class="form-control" id="maxScore" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="transferScore" class="form-label fw-bold">Score to Transfer <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="transferScore" name="score"
                                   step="0.1" min="0" required>
                            <div class="form-text text-danger" id="scoreValidationMsg"></div>
                        </div>

                        <input type="hidden" name="is_sub" id="isSub" value="0">
                    </div>
                </form>

                <div id="assessmentInfo" class="mt-3 p-3 bg-light rounded" style="display: none;">
                    <h6 class="fw-bold">Assessment Details:</h6>
                    <div id="assessmentDetails" class="small"></div>
                </div>

                <!-- Loading Spinner -->
                <div id="assessmentLoader" style="display: none;" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading assessments...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ph-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="transferScoreBtn">
                    <i class="ph-check me-1"></i>Transfer Score
                </button>
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

<script>
// =============================================
// GLOBAL ERROR HANDLER
// =============================================
window.onerror = function(message, source, lineno, colno, error) {
    console.error('❌ GLOBAL ERROR:', {
        message: message,
        source: source,
        line: lineno,
        column: colno,
        error: error ? error.stack : 'No stack trace'
    });
    return true;
};

console.log('✅ JavaScript starting...');
console.log('✅ Error handler installed');
console.log('✅ Timestamp:', new Date().toISOString());

document.addEventListener('DOMContentLoaded', function() {
    console.log('=========================================');
    console.log('✅ DOM FULLY LOADED AND PARSED');
    console.log('=========================================');

    try {
        // Test if we can find the transfer buttons
        const transferButtons = document.querySelectorAll('.transfer-score-btn');
        console.log('🔍 Found transfer buttons:', transferButtons.length);

        // Test if we can find the modals
        const termSessionModal = document.getElementById('termSessionModal');
        const assessmentModal = document.getElementById('assessmentTransferModal');
        console.log('🔍 Term/Session modal found:', termSessionModal ? '✅ Yes' : '❌ No');
        console.log('🔍 Assessment modal found:', assessmentModal ? '✅ Yes' : '❌ No');

        // Test if we can find the form
        const form = document.getElementById('assessmentTransferForm');
        console.log('🔍 Assessment form found:', form ? '✅ Yes' : '❌ No');

        // Test if Bootstrap is loaded
        console.log('🔍 Bootstrap available:', typeof bootstrap !== 'undefined' ? '✅ Yes' : '❌ No');

        // Test if SweetAlert is loaded
        console.log('🔍 SweetAlert available:', typeof Swal !== 'undefined' ? '✅ Yes' : '❌ No');

        // Initialize tooltips
        try {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            console.log('✅ Tooltips initialized');
        } catch (e) {
            console.error('❌ Error initializing tooltips:', e);
        }

        // Initialize modals
        let bsTermSessionModal, bsAssessmentModal, bsSuccessModal, bsErrorModal;
        try {
            bsTermSessionModal = new bootstrap.Modal(document.getElementById('termSessionModal'));
            bsAssessmentModal = new bootstrap.Modal(document.getElementById('assessmentTransferModal'));
            bsSuccessModal = new bootstrap.Modal(document.getElementById('transferSuccessModal'));
            bsErrorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            console.log('✅ Modals initialized');
        } catch (e) {
            console.error('❌ Error initializing modals:', e);
        }

        // DOM elements
        const assessmentSelect = document.getElementById('assessmentSelect');
        const subAssessmentSelect = document.getElementById('subAssessmentSelect');
        const subAssessmentContainer = document.getElementById('subAssessmentContainer');
        const maxScoreInput = document.getElementById('maxScore');
        const transferScoreInput = document.getElementById('transferScore');
        const isSubInput = document.getElementById('isSub');
        const assessmentInfo = document.getElementById('assessmentInfo');
        const assessmentDetails = document.getElementById('assessmentDetails');
        const scoreValidationMsg = document.getElementById('scoreValidationMsg');
        const assessmentLoader = document.getElementById('assessmentLoader');
        const transferBtn = document.getElementById('transferScoreBtn');

        // Term/Session modal elements
        const modalSession = document.getElementById('modalSession');
        const modalTerm = document.getElementById('modalTerm');
        const termSessionLoader = document.getElementById('termSessionLoader');
        const proceedBtn = document.getElementById('proceedToSubjectsBtn');

        // Log DOM elements status
        console.log('🔍 DOM Elements check:');
        console.log('  - assessmentSelect:', assessmentSelect ? '✅' : '❌');
        console.log('  - subAssessmentSelect:', subAssessmentSelect ? '✅' : '❌');
        console.log('  - transferBtn:', transferBtn ? '✅' : '❌');
        console.log('  - maxScoreInput:', maxScoreInput ? '✅' : '❌');
        console.log('  - transferScoreInput:', transferScoreInput ? '✅' : '❌');
        console.log('  - modalSession:', modalSession ? '✅' : '❌');
        console.log('  - modalTerm:', modalTerm ? '✅' : '❌');
        console.log('  - proceedBtn:', proceedBtn ? '✅' : '❌');

        // Global variables
        let assessments = [];
        let currentExamId = '{{ $exam->id }}';
        let subjectclass_id = null;

        console.log('📝 Current Exam ID:', currentExamId);

        // =============================================
        // TERM/SESSION MODAL HANDLER
        // =============================================
        if (proceedBtn) {
            proceedBtn.addEventListener('click', function() {
                const sessionId = modalSession.value;
                const termId = modalTerm.value;

                console.log('Term/Session selected:', { termId, sessionId });

                if (!sessionId || !termId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please select both term and session.'
                    });
                    return;
                }

                // Show loader
                termSessionLoader.style.display = 'block';
                proceedBtn.disabled = true;

                // Redirect to subject selection page
                setTimeout(function() {
                    window.location.href = '{{ route("exams.transfer.subjects") }}?termid=' + termId + '&sessionid=' + sessionId;
                }, 500);
            });
        } else {
            console.error('❌ Proceed button not found!');
        }

        // =============================================
        // TRANSFER BUTTON CLICK HANDLER (Opens Modal)
        // =============================================
        if (transferButtons.length > 0) {
            transferButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    console.log('=========================================');
                    console.log('✅ TRANSFER BUTTON CLICKED (Opens Modal)');
                    console.log('=========================================');

                    try {
                        const studentId = this.dataset.studentId;
                        const studentName = this.dataset.studentName;
                        const studentAdmission = this.dataset.studentAdmission;
                        const examScore = parseFloat(this.dataset.examScore) || 0;

                        console.log('Student ID:', studentId);
                        console.log('Student Name:', studentName);
                        console.log('Admission:', studentAdmission);
                        console.log('Exam Score:', examScore);

                        // Set student data in modal
                        document.getElementById('studentId').value = studentId;
                        document.getElementById('studentName').textContent = studentName;
                        document.getElementById('studentAdmission').textContent = studentAdmission;
                        document.getElementById('examScore').textContent = examScore.toFixed(1) + ' marks';
                        document.getElementById('examScoreHidden').value = examScore;
                        transferScoreInput.value = examScore.toFixed(1);

                        // Load assessments
                        loadAssessments(currentExamId);

                        // Show modal
                        if (bsAssessmentModal) {
                            bsAssessmentModal.show();
                            console.log('✅ Modal shown');
                        } else {
                            console.error('❌ bsAssessmentModal is not initialized');
                        }
                    } catch (e) {
                        console.error('❌ Error in transfer button click handler:', e);
                    }
                });
            });
        } else {
            console.log('ℹ️ No transfer buttons to attach events to (this is normal if no completed attempts)');
        }

        // =============================================
        // LOAD ASSESSMENTS FUNCTION
        // =============================================
        function loadAssessments(examId) {
            console.log('=========================================');
            console.log('📡 LOADING ASSESSMENTS FOR EXAM:', examId);
            console.log('=========================================');

            try {
                // Show loader
                if (assessmentLoader) assessmentLoader.style.display = 'block';
                if (assessmentSelect) assessmentSelect.style.display = 'none';

                fetch(`/exams/assessments/${examId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => {
                    console.log('API Response Status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API Response Data:', data);

                    // Hide loader
                    if (assessmentLoader) assessmentLoader.style.display = 'none';
                    if (assessmentSelect) assessmentSelect.style.display = 'block';

                    if (data.success) {
                        assessments = data.assessments;
                        subjectclass_id = data.subjectclass_id;

                        console.log('✅ ASSESSMENTS LOADED SUCCESSFULLY');
                        console.log('📊 Assessments Count:', assessments.length);
                        console.log('🆔 Subjectclass ID:', subjectclass_id);

                        if (!subjectclass_id) {
                            console.error('❌ ERROR: subjectclass_id is null or undefined!');
                            showError('No subject class found for this exam');
                            return;
                        }

                        // Build assessment options
                        let options = '<option value="">Select an assessment</option>';

                        data.assessments.forEach(assessment => {
                            const hasSub = assessment.sub_assessments && assessment.sub_assessments.length > 0;
                            options += `<option value="${assessment.id}"
                                             data-max="${assessment.max_score}"
                                             data-has-sub="${hasSub}">
                                            ${assessment.name} (Max: ${assessment.max_score})
                                        </option>`;
                        });

                        if (assessmentSelect) {
                            assessmentSelect.innerHTML = options;
                            console.log('✅ Assessment dropdown populated');
                        }
                    } else {
                        console.error('❌ API returned success: false', data.message);
                        if (assessmentSelect) {
                            assessmentSelect.innerHTML = '<option value="">Error loading assessments</option>';
                        }
                        showError('Failed to load assessments: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('❌ NETWORK ERROR loading assessments:', error);
                    if (assessmentLoader) assessmentLoader.style.display = 'none';
                    if (assessmentSelect) {
                        assessmentSelect.style.display = 'block';
                        assessmentSelect.innerHTML = '<option value="">Error loading assessments</option>';
                    }
                    showError('Network error while loading assessments');
                });
            } catch (e) {
                console.error('❌ Error in loadAssessments function:', e);
            }
        }

        // =============================================
        // ASSESSMENT SELECTION HANDLER
        // =============================================
        if (assessmentSelect) {
            assessmentSelect.addEventListener('change', function() {
                try {
                    const selectedOption = this.options[this.selectedIndex];
                    const assessmentId = this.value;

                    console.log('=========================================');
                    console.log('📝 ASSESSMENT SELECTED:', assessmentId);
                    console.log('=========================================');

                    document.getElementById('assessmentId').value = assessmentId;

                    if (!assessmentId) {
                        if (maxScoreInput) maxScoreInput.value = '';
                        if (assessmentInfo) assessmentInfo.style.display = 'none';
                        if (subAssessmentContainer) subAssessmentContainer.style.display = 'none';
                        if (isSubInput) isSubInput.value = '0';
                        return;
                    }

                    const maxScore = selectedOption.dataset.max;
                    const hasSub = selectedOption.dataset.hasSub === 'true';

                    console.log('Max Score:', maxScore);
                    console.log('Has Sub-assessments:', hasSub);

                    if (maxScoreInput) maxScoreInput.value = maxScore;
                    if (assessmentInfo) assessmentInfo.style.display = 'block';

                    const assessment = assessments.find(a => a.id == assessmentId);
                    if (assessment && assessmentDetails) {
                        let details = `
                            <strong>Name:</strong> ${assessment.name}<br>
                            <strong>Max Score:</strong> ${assessment.max_score}<br>
                        `;

                        if (assessment.sub_assessments && assessment.sub_assessments.length > 0) {
                            details += `<strong>Sub-assessments:</strong> ${assessment.sub_assessments.length}<br>`;
                            details += `<small class="text-muted">${assessment.sub_assessments.map(s => s.name).join(', ')}</small>`;
                        }

                        assessmentDetails.innerHTML = details;
                    }

                    // Handle sub-assessments
                    if (hasSub) {
                        loadSubAssessments(assessmentId);
                        if (subAssessmentContainer) subAssessmentContainer.style.display = 'block';
                        if (isSubInput) isSubInput.value = '1';
                    } else {
                        if (subAssessmentContainer) subAssessmentContainer.style.display = 'none';
                        if (subAssessmentSelect) {
                            subAssessmentSelect.innerHTML = '<option value="">-- Select Sub-Assessment (Optional) --</option>';
                        }
                        if (isSubInput) isSubInput.value = '0';
                    }

                    validateScore();
                } catch (e) {
                    console.error('❌ Error in assessment selection handler:', e);
                }
            });
        }

        // =============================================
        // LOAD SUB-ASSESSMENTS FUNCTION
        // =============================================
        function loadSubAssessments(assessmentId) {
            try {
                const assessment = assessments.find(a => a.id == assessmentId);
                if (assessment && assessment.sub_assessments && assessment.sub_assessments.length > 0 && subAssessmentSelect) {
                    let options = '<option value="">-- Select Sub-Assessment (Optional) --</option>';
                    assessment.sub_assessments.forEach(sub => {
                        options += `<option value="${sub.id}" data-max="${sub.max_score}">
                                        ${sub.name} (Max: ${sub.max_score})
                                   </option>`;
                    });
                    subAssessmentSelect.innerHTML = options;
                    console.log('✅ Sub-assessments loaded:', assessment.sub_assessments.length);
                } else if (subAssessmentSelect) {
                    subAssessmentSelect.innerHTML = '<option value="">-- No sub-assessments available --</option>';
                }
            } catch (e) {
                console.error('❌ Error loading sub-assessments:', e);
            }
        }

        // =============================================
        // SUB-ASSESSMENT SELECTION HANDLER
        // =============================================
        if (subAssessmentSelect) {
            subAssessmentSelect.addEventListener('change', function() {
                try {
                    if (this.value) {
                        const selectedOption = this.options[this.selectedIndex];
                        if (maxScoreInput) maxScoreInput.value = selectedOption.dataset.max;
                        console.log('Sub-assessment selected, max score:', selectedOption.dataset.max);
                    } else {
                        const mainOption = assessmentSelect.options[assessmentSelect.selectedIndex];
                        if (maxScoreInput) maxScoreInput.value = mainOption.dataset.max;
                        console.log('Reverted to main assessment max:', mainOption.dataset.max);
                    }
                    validateScore();
                } catch (e) {
                    console.error('❌ Error in sub-assessment selection:', e);
                }
            });
        }

        // =============================================
        // VALIDATE SCORE FUNCTION
        // =============================================
        function validateScore() {
            try {
                const score = parseFloat(transferScoreInput.value) || 0;
                const maxScore = parseFloat(maxScoreInput.value) || 0;

                if (score > maxScore) {
                    transferScoreInput.classList.add('is-invalid');
                    scoreValidationMsg.innerHTML = `Score cannot exceed ${maxScore}`;
                    return false;
                } else if (score < 0) {
                    transferScoreInput.classList.add('is-invalid');
                    scoreValidationMsg.innerHTML = 'Score cannot be negative';
                    return false;
                } else {
                    transferScoreInput.classList.remove('is-invalid');
                    scoreValidationMsg.innerHTML = '';
                    return true;
                }
            } catch (e) {
                console.error('❌ Error in validateScore:', e);
                return false;
            }
        }

        if (transferScoreInput) {
            transferScoreInput.addEventListener('input', validateScore);
            transferScoreInput.addEventListener('blur', validateScore);
        }

        // =============================================
        // TRANSFER SCORE BUTTON CLICK HANDLER (INSIDE MODAL)
        // =============================================
        if (transferBtn) {
            transferBtn.addEventListener('click', function() {
                console.log('=========================================');
                console.log('✅ TRANSFER SCORE BUTTON CLICKED (INSIDE MODAL)');
                console.log('=========================================');

                try {
                    // Validate assessment selected
                    if (!assessmentSelect.value) {
                        console.error('❌ No assessment selected');
                        showError('Please select an assessment');
                        return;
                    }

                    // Validate score
                    if (!validateScore()) {
                        console.error('❌ Score validation failed');
                        return;
                    }

                    // CRITICAL: Check if subjectclass_id exists
                    console.log('Current subjectclass_id value:', subjectclass_id);

                    if (!subjectclass_id) {
                        console.error('❌ ERROR: subjectclass_id is null or undefined!');
                        showError('Subject class ID not found. Please refresh and try again.');
                        return;
                    }

                    const score = parseFloat(transferScoreInput.value);
                    const maxScore = parseFloat(maxScoreInput.value);

                    console.log('Score to transfer:', score);
                    console.log('Max score:', maxScore);
                    console.log('Subjectclass ID being sent:', subjectclass_id);

                    // Get the form
                    const form = document.getElementById('assessmentTransferForm');

                    // Create FormData
                    const formData = new FormData(form);

                    // Add required fields
                    formData.append('max_score', maxScore);
                    formData.append('subjectclass_id', subjectclass_id);
                    formData.append('term_id', '{{ $exam->termid }}');
                    formData.append('session_id', '{{ $exam->session }}');

                    // Add sub-assessment if selected
                    if (subAssessmentSelect.value) {
                        formData.append('sub_assessment_id', subAssessmentSelect.value);
                        formData.append('is_sub', '1');
                        console.log('Sub-assessment selected:', subAssessmentSelect.value);
                    } else {
                        formData.append('is_sub', '0');
                    }

                    // DEBUG: Log ALL form data to verify
                    console.log('========== FORM DATA BEING SENT ==========');
                    for (let pair of formData.entries()) {
                        console.log(`  🔹 ${pair[0]}: ${pair[1]}`);
                    }
                    console.log('==========================================');

                    // Show loading state
                    const btn = this;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Transferring...';
                    btn.disabled = true;

                    // Send request
                    fetch('{{ route("exams.update-assessment-score") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);

                        // Reset button
                        btn.innerHTML = originalText;
                        btn.disabled = false;

                        if (data.success) {
                            console.log('✅ TRANSFER SUCCESSFUL!');
                            console.log('📊 Transfer data:', data.data);

                            // Hide transfer modal
                            if (bsAssessmentModal) bsAssessmentModal.hide();

                            // Show success message with actual data
                            const successMsg = `Score transferred successfully!<br>
                                <small>
                                    Student: ${data.data.student_name} (${data.data.admission_no})<br>
                                    Score: ${data.data.total} | Cum: ${data.data.cum} | Grade: ${data.data.grade}<br>
                                    Broadsheet ID: ${data.data.broadsheet_id}
                                </small>`;
                            document.getElementById('successMessage').innerHTML = successMsg;
                            if (bsSuccessModal) bsSuccessModal.show();

                            // Mark the row as transferred
                            const studentId = formData.get('student_id');
                            const studentRow = document.querySelector(`tr[data-student-id="${studentId}"]`);
                            if (studentRow) {
                                const transferBtn = studentRow.querySelector('.transfer-score-btn');
                                if (transferBtn) {
                                    transferBtn.classList.remove('btn-subtle-success');
                                    transferBtn.classList.add('btn-success');
                                    transferBtn.innerHTML = '<i class="ph-check"></i>';
                                    transferBtn.setAttribute('title', 'Already transferred to assessment sheet');
                                    transferBtn.disabled = true;
                                }
                            }
                        } else {
                            console.error('❌ TRANSFER FAILED:', data.message);
                            showError(data.message || 'Failed to transfer score');
                        }
                    })
                    .catch(error => {
                        console.error('❌ NETWORK ERROR:', error);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        showError('Network error occurred. Please try again. Error: ' + error.message);
                    });

                } catch (e) {
                    console.error('❌ Error in transfer button handler:', e);
                    showError('An error occurred: ' + e.message);
                }
            });
        } else {
            console.log('ℹ️ transferBtn element not found (this is normal if no students with completed attempts)');
        }

        // =============================================
        // SHOW ERROR FUNCTION
        // =============================================
        function showError(message) {
            console.error('❌ ERROR:', message);
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) {
                errorMessage.textContent = message;
            }
            if (bsErrorModal) {
                bsErrorModal.show();
            }
        }

        // =============================================
        // RESET MODAL WHEN HIDDEN
        // =============================================
        if (bsAssessmentModal) {
            document.getElementById('assessmentTransferModal').addEventListener('hidden.bs.modal', function() {
                console.log('=========================================');
                console.log('MODAL HIDDEN - RESETTING FORM');
                console.log('=========================================');

                if (assessmentSelect) assessmentSelect.value = '';
                if (subAssessmentSelect) {
                    subAssessmentSelect.innerHTML = '<option value="">-- Select Sub-Assessment (Optional) --</option>';
                }
                if (subAssessmentContainer) subAssessmentContainer.style.display = 'none';
                if (maxScoreInput) maxScoreInput.value = '';
                if (transferScoreInput) transferScoreInput.value = '';
                if (isSubInput) isSubInput.value = '0';
                if (assessmentInfo) assessmentInfo.style.display = 'none';
                if (transferScoreInput) transferScoreInput.classList.remove('is-invalid');
                if (scoreValidationMsg) scoreValidationMsg.innerHTML = '';

                console.log('✅ Form reset complete');
            });
        }

        // =============================================
        // DELETE ATTEMPT FUNCTIONALITY
        // =============================================
        const deleteButtons = document.querySelectorAll('.delete-attempt');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const examId = this.dataset.examId;
                const studentId = this.dataset.studentId;
                const studentName = this.dataset.studentName;
                const deleteUrl = this.dataset.deleteUrl;
                const row = this.closest('tr');
                const isInProgress = row.querySelector('.badge.bg-warning') !== null;

                const confirmMsg = isInProgress
                    ? `Are you sure you want to delete ${studentName}'s ongoing exam attempt? This will stop the exam and allow a retake.`
                    : `Are you sure you want to delete ${studentName}'s exam attempt? This will allow them to retake the exam.`;

                Swal.fire({
                    title: 'Are you sure?',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                row.remove();
                                updateCountBadge();
                                updatePaginationText();
                                updateSerialNumbers();
                                checkEmptyTable();

                                Swal.fire({
                                    title: 'Deleted!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message || 'Error deleting attempt. Please try again.',
                                    icon: 'error',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Delete Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while deleting the attempt.',
                                icon: 'error',
                                confirmButtonColor: '#3085d6'
                            });
                        });
                    }
                });
            });
        });

        // =============================================
        // HELPER FUNCTIONS
        // =============================================
        function updateCountBadge() {
            const badge = document.getElementById('students-count');
            if (badge) {
                let currentTotal = parseInt(badge.textContent.trim());
                if (!isNaN(currentTotal)) {
                    badge.textContent = currentTotal - 1;
                }
            }
        }

        function updatePaginationText() {
            const paginationText = document.getElementById('pagination-text');
            if (paginationText) {
                const match = paginationText.textContent.match(/of (\d+) Results/);
                if (match) {
                    const newTotal = parseInt(match[1]) - 1;
                    paginationText.innerHTML = paginationText.innerHTML.replace(/of \d+ Results/, `of ${newTotal} Results`);
                }
            }
        }

        function updateSerialNumbers() {
            const rows = document.querySelectorAll('#students-tbody tr:not(.empty-row)');
            let i = (1 + (Math.max(0, rows.length - 15) / 15) * 15);
            rows.forEach(row => {
                const snCell = row.querySelector('.sn-number');
                if (snCell) {
                    snCell.textContent = ++i;
                }
            });
        }

        function checkEmptyTable() {
            const tbody = document.getElementById('students-tbody');
            const rows = tbody.querySelectorAll('tr:not(.empty-row)');
            if (rows.length === 0) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="11" class="text-center">No students found</td></tr>';
                const paginationContainer = document.querySelector('.pagination-wrap');
                const paginationText = document.getElementById('pagination-text');
                if (paginationContainer) paginationContainer.style.display = 'none';
                if (paginationText) paginationText.style.display = 'none';
            }
        }

        console.log('✅ JavaScript initialization complete');
        console.log('=========================================');

    } catch (e) {
        console.error('❌ FATAL ERROR in main execution:', e);
    }
});
</script>
@endsection
