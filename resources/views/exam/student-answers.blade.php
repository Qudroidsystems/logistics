@extends('layouts.master')
@section('content')

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
        --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .answers-header {
        background: var(--primary-gradient);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        padding: 1.5rem;
        margin-bottom: 2rem;
        color: white;
    }

    .score-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .score-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--success-gradient);
    }

    .score-badge {
        background: var(--success-gradient);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 1rem;
    }

    .answers-table-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .question-preview {
        max-width: 200px;
        max-height: 150px;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .question-preview:hover {
        transform: scale(1.05);
    }

    .answer-cell {
        background: #f8fafc;
        border-radius: 8px;
        padding: 0.75rem;
        word-break: break-word;
    }

    .correct-badge {
        background: var(--success-gradient);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .incorrect-badge {
        background: var(--danger-gradient);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .not-attempted-badge {
        background: var(--warning-gradient);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .question-html {
        line-height: 1.6;
        color: #374151;
        max-height: 80px;
        overflow: hidden;
        position: relative;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
    }

    .question-html h2 {
        color: #1f2937;
        font-size: 1.125rem;
        margin-bottom: 0.5rem;
    }

    .question-html p {
        margin-bottom: 0.75rem;
    }

    .question-html strong {
        font-weight: 600;
        color: #1f2937;
    }

    .view-more-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        background: linear-gradient(transparent, #f8fafc);
        border: none;
        color: #667eea;
        font-size: 0.875rem;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 4px 0 0 0;
        transition: color 0.2s ease;
    }

    .view-more-btn:hover {
        color: #5a67d8;
        text-decoration: underline;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .answers-table-card {
        animation: fadeIn 0.5s ease;
    }

    /* Horizontal Scroll for Table */
    .table-scroll-container {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .table-scroll-container table {
        min-width: 1200px; /* Ensure table is wide enough to scroll */
    }

    .table-scroll-container th,
    .table-scroll-container td {
        min-width: 150px; /* Minimum width for each column to prevent squishing */
        white-space: normal; /* Allow wrapping in cells */
        word-break: break-word;
    }

    .table-scroll-container th:first-child,
    .table-scroll-container td:first-child {
        min-width: 75px; /* Smaller for SN */
    }

    .table-scroll-container th:nth-child(2),
    .table-scroll-container td:nth-child(2) {
        min-width: 250px; /* Adjusted for better fit with truncation */
    }

    .table-scroll-container th:nth-child(3),
    .table-scroll-container td:nth-child(3) {
        min-width: 150px; /* For Image */
        text-align: center;
    }

    /* Scrollbar Styling */
    .table-scroll-container::-webkit-scrollbar {
        height: 8px;
    }

    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
        background: var(--primary-gradient);
        border-radius: 4px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #5a67d8;
    }

    /* Marks Column Styling */
    .marks-badge {
        background: var(--primary-gradient);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .marks-earned-badge {
        background: var(--success-gradient);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .type-badge {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Exam Answers' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">Exams</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('exams.students', $exam->id) }}">Students</a></li>
                                <li class="breadcrumb-item active">Answers</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Performance Summary -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="answers-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-0 fw-bold" style="font-size: 1.5rem;">{{ $student->firstname }} {{ $student->lastname }}</h4>
                                <p class="mb-0 opacity-75">Admission No: {{ $student->admissionNo }}</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="score-card" style="padding: 1rem; display: inline-block;">
                                    <div class="score-badge" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        <i class="ri-trophy-line me-1"></i>Performance Score
                                    </div>
                                    <h3 class="mb-0 fw-bold" style="font-size: 1.5rem;">
                                        {{ number_format($marksEarned, 1) }} / {{ number_format($totalMarks, 1) }}
                                    </h3>
                                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                        @if($totalMarks > 0)
                                            {{ number_format(($marksEarned/$totalMarks)*100, 1) }}% Achieved
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small mb-2">Total Questions</div>
                            <h2 class="mb-0 fw-bold">{{ $totalQuestions }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small mb-2">Attempted</div>
                            <h2 class="mb-0 fw-bold text-primary">{{ $attempted }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small mb-2">Correct</div>
                            <h2 class="mb-0 fw-bold text-success">{{ $correct }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small mb-2">Incorrect</div>
                            <h2 class="mb-0 fw-bold text-danger">{{ $attempted - $correct }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question & Answers Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card answers-table-card">
                        <div class="card-header d-flex align-items-center bg-light">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    <i class="ri-file-text-line me-2 text-primary"></i>Detailed Answers
                                </h5>
                                <p class="text-muted mb-0 small">Review student responses and correctness</p>
                            </div>
                            <div>
                                <span class="text-muted small me-3">
                                    <i class="ri-checkbox-circle-fill text-success me-1"></i>
                                    Total Marks: {{ number_format($totalMarks, 1) }}
                                </span>
                                <span class="text-muted small">
                                    <i class="ri-star-fill text-warning me-1"></i>
                                    Marks Earned: {{ number_format($marksEarned, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-scroll-container">
                                <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                    <thead class="table-light">
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-75px">SN</th>
                                            <th class="min-w-250px">Question</th>
                                            <th class="min-w-120px">Type</th>
                                            <th class="min-w-100px">Marks</th>
                                            <th class="min-w-150px">Image</th>
                                            <th class="min-w-200px">Student Answer</th>
                                            <th class="min-w-200px">Correct Answer</th>
                                            <th class="min-w-100px">Marks Earned</th>
                                            <th class="min-w-150px">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600">
                                        @php $i = 1 @endphp
                                        @forelse ($questionAnswers as $qa)
                                            <tr class="border-bottom">
                                                <td class="fw-bold text-primary">{{ $i++ }}</td>
                                                <td>
                                                    <div class="question-html" data-full-text="{!! addslashes($qa->question_text) !!}">
                                                        {!! $qa->question_text !!}
                                                        @if(strlen(strip_tags($qa->question_text)) > 200)
                                                            <button class="view-more-btn" onclick="showFullQuestion({{ $qa->id }})">
                                                                <i class="ri-eye-line me-1"></i>View More
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="type-badge">
                                                        {{ ucfirst(str_replace('_', ' ', $qa->type)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="marks-badge">
                                                        {{ number_format($qa->marks ?? 1.0, 1) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($qa->image)
                                                        <img src="{{ asset('storage/' . $qa->image) }}" class="img-fluid rounded question-preview" alt="Question Image" data-bs-toggle="modal" data-bs-target="#imageModal{{ $qa->id }}">
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="answer-cell @if($qa->status == 'Correct') border-success border-1 @elseif($qa->status == 'Incorrect') border-danger border-1 @endif">
                                                        <strong>{{ $qa->student_answer ?? 'Not Attempted' }}</strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="answer-cell bg-success bg-opacity-10 text-success fw-semibold border-success border-1">
                                                        {{ $qa->correct_answer ?? '-' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="marks-earned-badge">
                                                        {{ number_format($qa->marks_earned, 1) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $status = $qa->status;
                                                        $badgeClass = $status == 'Correct' ? 'correct-badge' : ($status == 'Not Attempted' ? 'not-attempted-badge' : 'incorrect-badge');
                                                        $icon = $status == 'Correct' ? 'ri-check-line' : ($status == 'Not Attempted' ? 'ri-time-line' : 'ri-close-line');
                                                    @endphp
                                                    <span class="{{ $badgeClass }}">
                                                        <i class="{{ $icon }} me-1"></i>{{ $status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                                                        No answers available
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page-content -->
    </div>
</div>

<!-- Image Modals -->
@foreach ($questionAnswers as $qa)
    @if($qa->image)
    <div class="modal fade" id="imageModal{{ $qa->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="ri-image-line me-2 text-primary"></i>Question Image
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img src="{{ asset('storage/' . $qa->image) }}" alt="Question Image" class="img-fluid rounded" style="max-height: 80vh;">
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach

<!-- Question Detail Modals -->
@foreach ($questionAnswers as $qa)
<div class="modal fade" id="questionModal{{ $qa->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold">
                    <i class="ri-file-text-line me-2 text-primary"></i>Full Question Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="question-html-full">{!! $qa->question_text !!}</div>
            </div>
        </div>
    </div>
</div>
@endforeach

<script>
function showFullQuestion(qaId) {
    const modal = new bootstrap.Modal(document.getElementById(`questionModal${qaId}`));
    modal.show();
}

// Print PDF function
function printQuestionPaper() {
    window.open("{{ route('exams.student.question-paper', [$exam->id, $student->id]) }}", '_blank');
}
</script>

@endsection
