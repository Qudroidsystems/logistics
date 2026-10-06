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
.student-row-transferred {
    background-color: #d4edda;
}
.badge-transferred {
    background-color: #28a745;
    color: white;
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Transfer Exam Scores - {{ $subjectclass->subject->subject ?? 'Subject' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">Exams</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('exams.transfer.subjects') }}?termid={{ $termid }}&sessionid={{ $sessionid }}">Select Subject</a></li>
                                <li class="breadcrumb-item active">Transfer Scores</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Subject Info Cards -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h6 class="text-muted mb-2">Subject</h6>
                                        <h4>{{ $subjectclass->subject->subject ?? 'N/A' }}</h4>
                                        <span class="badge bg-primary">{{ $subjectclass->subject->subject_code ?? 'N/A' }}</span>
                                    </div>
                                </div>
                               <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h6 class="text-muted mb-2">Class</h6>
                                        <h4>{{ $schoolclass->schoolclass ?? 'N/A' }} {{ $armDisplay ?? '' }}</h4>
                                        <span class="badge bg-info">{{ $subjectclass->classcategories ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                 <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h6 class="text-muted mb-2">Term</h6>
                                        <h4>{{ $term->term ?? 'Term ' . $termid }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h6 class="text-muted mb-2">Session</h6>
                                        <h4>{{ $session->session ?? 'Session ' . $sessionid }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    <i class="ph-users me-2"></i>Students Who Attempted Exam
                                    <span class="badge bg-primary ms-2" id="studentsCount">{{ $students->count() }}</span>
                                </h5>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="input-group" style="width: 300px;">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search students...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="ph-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($students->isEmpty())
                                <div class="alert alert-info text-center py-5">
                                    <i class="ph-users fs-1 mb-3 d-block"></i>
                                    <h5>No Students Found</h5>
                                    <p class="mb-0">No students have attempted exams for this subject.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">#</th>
                                                <th width="60">Photo</th>
                                                <th>Student Name</th>
                                                <th>Admission No</th>
                                                <th>Exam Score</th>
                                                <th width="200">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentsTableBody">
                                            @foreach($students as $index => $student)
                                                <tr class="student-row" data-student-id="{{ $student->id }}" data-student-name="{{ $student->lastname }} {{ $student->firstname }}" data-admission="{{ $student->admissionNo }}" data-score="{{ $student->score ?? 0 }}">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <img src="{{ $student->picture ? asset('storage/student_avatars/' . basename($student->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                                                             alt="{{ $student->lastname }} {{ $student->firstname }}"
                                                             class="rounded-circle" width="40" height="40"
                                                             onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                                                    </td>
                                                    <td>
                                                        <strong>{{ $student->lastname }}</strong> {{ $student->firstname }}
                                                    </td>
                                                    <td>{{ $student->admissionNo }}</td>
                                                    <td>
                                                        <span class="badge bg-primary fs-6">{{ number_format($student->score ?? 0, 1) }}</span>
                                                    </td>
                                                    <td>
                                                        <button type="button"
                                                                class="btn btn-success btn-sm transfer-score-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#assessmentTransferModal"
                                                                data-student-id="{{ $student->id }}"
                                                                data-student-name="{{ $student->lastname }} {{ $student->firstname }}"
                                                                data-student-admission="{{ $student->admissionNo }}"
                                                                data-exam-score="{{ $student->score ?? 0 }}">
                                                            <i class="ph-arrow-right me-1"></i> Transfer to Assessment
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                    <input type="hidden" name="student_id" id="studentId">
                    <input type="hidden" name="exam_score" id="examScoreHidden">
                    <input type="hidden" name="subjectclass_id" value="{{ $subjectclassid }}">
                    <input type="hidden" name="term_id" value="{{ $termid }}">
                    <input type="hidden" name="session_id" value="{{ $sessionid }}">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Select Assessment <span class="text-danger">*</span></label>
                            <select class="form-select" id="assessmentSelect" name="assessment_id" required>
                                <option value="">Select an assessment</option>
                                @foreach($assessments as $assessment)
                                    <option value="{{ $assessment->id }}"
                                            data-max="{{ $assessment->max_score }}"
                                            data-has-sub="{{ $assessment->subAssessments->isNotEmpty() ? 'true' : 'false' }}">
                                        {{ $assessment->name }} (Max: {{ $assessment->max_score }})
                                    </option>
                                @endforeach
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

                <div id="assessmentInfo" class="mt-3 p-3 bg-light rounded" style="display: none;">
                    <h6 class="fw-bold">Assessment Details:</h6>
                    <div id="assessmentDetails" class="small"></div>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Transfer Scoresheet JavaScript loaded');

    // Initialize modals
    let assessmentModal, successModal, errorModal;
    try {
        assessmentModal = new bootstrap.Modal(document.getElementById('assessmentTransferModal'));
        successModal = new bootstrap.Modal(document.getElementById('transferSuccessModal'));
        errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
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
    const transferBtn = document.getElementById('transferScoreBtn');

    // Store assessments data
    const assessments = @json($assessments);

    console.log('📊 Assessments loaded:', assessments.length);

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const tableRows = document.querySelectorAll('#studentsTableBody tr');
    const studentsCount = document.getElementById('studentsCount');

    function filterStudents() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        tableRows.forEach(row => {
            const name = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const admission = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

            if (name.includes(searchTerm) || admission.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        studentsCount.textContent = visibleCount;
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterStudents);
    }

    if (clearSearch) {
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            filterStudents();
        });
    }

    // Transfer button click handler
    document.querySelectorAll('.transfer-score-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            const studentName = this.dataset.studentName;
            const studentAdmission = this.dataset.studentAdmission;
            const examScore = parseFloat(this.dataset.examScore) || 0;

            console.log('Transfer button clicked for:', studentName);

            document.getElementById('studentId').value = studentId;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('studentAdmission').textContent = studentAdmission;
            document.getElementById('examScore').textContent = examScore.toFixed(1) + ' marks';
            document.getElementById('examScoreHidden').value = examScore;
            transferScoreInput.value = examScore.toFixed(1);

            // Reset assessment selection
            assessmentSelect.value = '';
            maxScoreInput.value = '';
            assessmentInfo.style.display = 'none';
            subAssessmentContainer.style.display = 'none';
            subAssessmentSelect.innerHTML = '<option value="">-- Select Sub-Assessment (Optional) --</option>';
            isSubInput.value = '0';
            transferScoreInput.classList.remove('is-invalid');
            scoreValidationMsg.innerHTML = '';
        });
    });

    // Assessment selection handler
    if (assessmentSelect) {
        assessmentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const assessmentId = this.value;

            console.log('Assessment selected:', assessmentId);

            if (!assessmentId) {
                maxScoreInput.value = '';
                assessmentInfo.style.display = 'none';
                subAssessmentContainer.style.display = 'none';
                isSubInput.value = '0';
                return;
            }

            const maxScore = selectedOption.dataset.max;
            const hasSub = selectedOption.dataset.hasSub === 'true';

            maxScoreInput.value = maxScore;
            assessmentInfo.style.display = 'block';

            const assessment = assessments.find(a => a.id == assessmentId);
            if (assessment) {
                let details = `
                    <strong>Name:</strong> ${assessment.name}<br>
                    <strong>Max Score:</strong> ${assessment.max_score}<br>
                `;

                if (assessment.sub_assessments && assessment.sub_assessments.length > 0) {
                    details += `<strong>Sub-assessments:</strong> ${assessment.sub_assessments.length}<br>`;
                    details += `<small class="text-muted">${assessment.sub_assessments.map(s => s.name).join(', ')}</small>`;

                    // Load sub-assessments
                    let subOptions = '<option value="">-- Select Sub-Assessment (Optional) --</option>';
                    assessment.sub_assessments.forEach(sub => {
                        subOptions += `<option value="${sub.id}" data-max="${sub.max_score}">${sub.name} (Max: ${sub.max_score})</option>`;
                    });
                    subAssessmentSelect.innerHTML = subOptions;
                    subAssessmentContainer.style.display = 'block';
                    isSubInput.value = '1';
                } else {
                    subAssessmentContainer.style.display = 'none';
                    isSubInput.value = '0';
                }

                assessmentDetails.innerHTML = details;
            }

            validateScore();
        });
    }

    // Sub-assessment selection handler
    if (subAssessmentSelect) {
        subAssessmentSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedOption = this.options[this.selectedIndex];
                maxScoreInput.value = selectedOption.dataset.max;
            } else {
                const mainOption = assessmentSelect.options[assessmentSelect.selectedIndex];
                maxScoreInput.value = mainOption.dataset.max;
            }
            validateScore();
        });
    }

    // Validate score
    function validateScore() {
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
    }

    if (transferScoreInput) {
        transferScoreInput.addEventListener('input', validateScore);
        transferScoreInput.addEventListener('blur', validateScore);
    }

    // Transfer button click handler
    if (transferBtn) {
        transferBtn.addEventListener('click', function() {
            console.log('Transfer button clicked');

            if (!assessmentSelect.value) {
                showError('Please select an assessment');
                return;
            }

            if (!validateScore()) {
                return;
            }

            const formData = new FormData(document.getElementById('assessmentTransferForm'));
            formData.append('max_score', maxScoreInput.value);
            formData.append('is_sub', isSubInput.value);

            if (subAssessmentSelect.value) {
                formData.append('sub_assessment_id', subAssessmentSelect.value);
            }

            // Log form data
            console.log('Sending form data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            // Show loading
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Transferring...';
            btn.disabled = true;

            fetch('{{ route("exams.update-assessment-score") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                btn.innerHTML = originalText;
                btn.disabled = false;

                if (data.success) {
                    if (assessmentModal) assessmentModal.hide();

                    const successMsg = `Score transferred successfully!<br>
                        <small>
                            Student: ${data.data.student_name}<br>
                            Score: ${data.data.total} | Cum: ${data.data.cum} | Grade: ${data.data.grade}
                        </small>`;
                    document.getElementById('successMessage').innerHTML = successMsg;
                    if (successModal) successModal.show();

                    // Mark row as transferred
                    const studentId = formData.get('student_id');
                    const studentRow = document.querySelector(`tr[data-student-id="${studentId}"]`);
                    if (studentRow) {
                        studentRow.classList.add('student-row-transferred');
                        const actionCell = studentRow.querySelector('td:last-child');
                        if (actionCell) {
                            actionCell.innerHTML = '<span class="badge bg-success"><i class="ph-check me-1"></i>Transferred</span>';
                        }
                    }
                } else {
                    showError(data.message || 'Failed to transfer score');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalText;
                btn.disabled = false;
                showError('Network error occurred');
            });
        });
    }

    function showError(message) {
        document.getElementById('errorMessage').textContent = message;
        if (errorModal) errorModal.show();
    }

    // Reset modal on hide
    if (assessmentModal) {
        document.getElementById('assessmentTransferModal').addEventListener('hidden.bs.modal', function() {
            assessmentSelect.value = '';
            maxScoreInput.value = '';
            transferScoreInput.value = '';
            assessmentInfo.style.display = 'none';
            subAssessmentContainer.style.display = 'none';
            transferScoreInput.classList.remove('is-invalid');
            scoreValidationMsg.innerHTML = '';
        });
    }
});
</script>
@endsection
