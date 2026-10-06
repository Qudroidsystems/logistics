@extends('layouts.master')

@section('content')
<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
        --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .exam-header {
        background: var(--primary-gradient);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .timer-display {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .exam-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .exam-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .question-card {
        background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        padding: 2rem;
        min-height: 500px;
    }

    .question-number-badge {
        background: var(--primary-gradient);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        animation: fadeIn 0.5s ease;
    }

    .question-text-container {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        border-left: 4px solid #667eea;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin: 1.5rem 0;
        animation: slideIn 0.5s ease;
    }

    .marks-badge {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 2px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        display: flex;
        align-items: center;
        white-space: nowrap;
        animation: fadeIn 0.5s ease;
        transition: all 0.3s ease;
    }

    .marks-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }

    .marks-badge.bg-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }

    .marks-badge.bg-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    }

    .marks-badge.bg-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .marks-badge.bg-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    /* MCQ Options */
    .option-card {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .option-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 0;
        background: var(--primary-gradient);
        opacity: 0.1;
        transition: width 0.3s ease;
    }

    .option-card:hover {
        border-color: #667eea;
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
    }

    .option-card:hover::before {
        width: 100%;
    }

    .option-card.selected {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        color: white;
        transform: scale(1.02);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .option-card.selected .form-check-label {
        color: white;
        font-weight: 600;
    }

    .option-letter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f3f4f6;
        color: #667eea;
        font-weight: bold;
        margin-right: 1rem;
        transition: all 0.3s ease;
    }

    .option-card.selected .option-letter {
        background: white;
        color: #667eea;
    }

    /* True/False Options */
    .true-false-container {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .true-false-btn {
        flex: 1;
        padding: 1.5rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        background: white;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .true-false-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .true-false-btn.selected {
        transform: scale(1.02);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .true-false-btn.true.selected {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-color: #10b981;
        color: white;
    }

    .true-false-btn.false.selected {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border-color: #ef4444;
        color: white;
    }

    /* Short Answer Input */
    .short-answer-container {
        margin-top: 1.5rem;
    }

    .short-answer-input {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        font-size: 1.1rem;
        width: 100%;
        transition: all 0.3s ease;
        background: white;
    }

    .short-answer-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .short-answer-input.answered {
        background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
        border-color: #667eea;
    }

    .nav-button {
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .nav-button:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .nav-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .question-nav-btn {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        background: white;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .question-nav-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: currentColor;
        opacity: 0.2;
        transform: translate(-50%, -50%);
        transition: width 0.3s ease, height 0.3s ease;
    }

    .question-nav-btn:hover::before {
        width: 100%;
        height: 100%;
    }

    .question-nav-btn.answered {
        background: var(--success-gradient);
        color: white;
        border-color: #10b981;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .question-nav-btn.unanswered {
        background: white;
        color: #6b7280;
        border-color: #e5e7eb;
    }

    .question-nav-btn.active {
        border-width: 3px;
        border-color: #667eea;
        transform: scale(1.1);
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
    }

    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        border: 2px solid #f3f4f6;
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .image-container {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        background: #f9fafb;
        padding: 1rem;
    }

    .image-zoom-controls {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 0.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .zoom-btn {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: none;
        background: #f3f4f6;
        color: #667eea;
        transition: all 0.2s ease;
    }

    .zoom-btn:hover {
        background: #667eea;
        color: white;
        transform: scale(1.1);
    }

    .notes-container {
        background: #fffbeb;
        border-radius: 12px;
        padding: 1.5rem;
        border: 2px solid #fef3c7;
    }

    .notes-textarea {
        border: 2px solid #fde68a;
        border-radius: 8px;
        resize: vertical;
        transition: all 0.3s ease;
    }

    .notes-textarea:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        outline: none;
    }

    .submit-btn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        transition: all 0.3s ease;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(239, 68, 68, 0.4);
    }

    .question-type-badge {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-left: 0.5rem;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .pulse-animation {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }

    /* Calculator Styles */
    .calculator-container {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        width: 320px;
    }

    .calculator-display {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 12px;
        text-align: right;
        font-size: 2rem;
        font-weight: bold;
        min-height: 70px;
        word-wrap: break-word;
        margin-bottom: 1rem;
        box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .calculator-buttons {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }

    .calc-btn {
        padding: 1.25rem;
        border: none;
        border-radius: 10px;
        font-size: 1.25rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f3f4f6;
        color: #374151;
    }

    .calc-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .calc-btn:active {
        transform: translateY(0);
    }

    .calc-btn.operator {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .calc-btn.equals {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        grid-column: span 2;
    }

    .calc-btn.clear {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .calc-btn.zero {
        grid-column: span 2;
    }

    /* Marks display in question navigation */
    .question-nav-btn.with-marks {
        position: relative;
    }

    .marks-indicator {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 0.6rem;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Modern Exam Header -->
            <div class="exam-header">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="ri-file-list-3-line" style="font-size: 2.5rem; color: white;"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 text-white fw-bold">Computer Based Test</h4>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('cbt.index') }}" class="text-white opacity-75 text-decoration-none">
                                        <i class="ri-home-line me-1"></i>Exams
                                    </a>
                                    <span class="text-white opacity-50">/</span>
                                    <span class="text-white">Take Exam</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex align-items-center justify-content-end gap-3 flex-wrap">
                            <div class="timer-display">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-star-line text-white" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <div class="text-white opacity-75 small">Total Marks</div>
                                        <div id="totalMarksDisplay" class="text-white fw-bold" style="font-size: 1.75rem;">
                                            {{ $totalExamMarks ?? 0 }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="timer-display">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-time-line text-white" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <div class="text-white opacity-75 small">Time Remaining</div>
                                        <div id="examTimer" class="text-white fw-bold" style="font-size: 1.75rem; letter-spacing: 1px;">00:00:00</div>
                                    </div>
                                </div>
                            </div>

                            @can('Submit cbt-exam')
                                <button class="submit-btn" id="submitExam">
                                    <i class="ri-send-plane-fill me-2"></i>Submit Exam
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-2"></i>
                    <strong>Whoops!</strong> There were some problems with your input.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-close-circle-line me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @can('Take cbt-exam')
            <div class="row g-4">
                <!-- Main Question Area -->
                <div class="col-lg-8">
                    <div class="exam-card">
                        <div class="card-header bg-white border-0 p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold">{{ $exam->title }}</h5>
                                    <p class="text-muted mb-0 small">{{ $exam->description }}</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button id="calculatorBtn" class="zoom-btn" title="Calculator">
                                        <i class="ri-calculator-line"></i>
                                    </button>
                                    <button id="fontSizeIncrease" class="zoom-btn" title="Increase font size">
                                        <i class="ri-font-size"></i>
                                    </button>
                                    <button id="fontSizeDecrease" class="zoom-btn" title="Decrease font size">
                                        <i class="ri-font-size-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <div class="question-card">
                                <!-- Question Number Badge -->
                                <div class="mb-4">
                                    <span class="question-number-badge">
                                        <i class="ri-question-line"></i>
                                        Question <span id="currentQuestionNum">1</span>
                                        <span id="questionTypeBadge" class="question-type-badge" style="display: none;">
                                            <i class="ri-checkbox-blank-circle-fill" style="font-size: 0.5rem;"></i>
                                            <span id="questionTypeText"></span>
                                        </span>
                                    </span>
                                </div>

                                <!-- Question Text with Marks Badge -->
                                <div class="question-text-container">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div style="flex-grow: 1;">
                                            <div id="questionText" class="question-text" style="font-size: 1.1rem; line-height: 1.8;"></div>
                                        </div>
                                        <div class="ms-3">
                                            <span class="badge bg-primary marks-badge" id="questionMarksBadge" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                                <i class="ri-star-line me-1"></i>
                                                <span id="questionMarks">0</span> Marks
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question Image -->
                                <div id="questionImageContainer" class="my-4" style="display: none;">
                                    <div class="image-container">
                                        <img id="questionImage" src="" alt="Question Image" class="img-fluid rounded question-image" style="max-height: 400px; cursor: pointer;">
                                        <div class="image-zoom-controls position-absolute bottom-0 end-0 m-3">
                                            <button id="zoomIn" class="zoom-btn me-1" title="Zoom in">
                                                <i class="ri-zoom-in-line"></i>
                                            </button>
                                            <button id="zoomOut" class="zoom-btn me-1" title="Zoom out">
                                                <i class="ri-zoom-out-line"></i>
                                            </button>
                                            <button id="zoomReset" class="zoom-btn" title="Reset zoom">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Options Container - Will be populated based on question type -->
                                <div id="optionsContainer" class="mt-4">
                                    <!-- Options will be loaded here based on question type -->
                                </div>
                            </div>

                            <!-- Navigation -->
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <button class="nav-button btn btn-outline-primary" id="prevQuestion">
                                    <i class="ri-arrow-left-line me-2"></i>Previous
                                </button>
                                <div class="d-flex gap-2">
                                    <span class="text-muted small">
                                        <span id="currentQuestionNum2">1</span> of <span id="totalQuestions">0</span>
                                    </span>
                                </div>
                                <button class="nav-button btn btn-primary" id="nextQuestion">
                                    Next<i class="ri-arrow-right-line ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="exam-card sticky-top" style="top: 20px;">
                        <div class="card-body p-4">
                            <!-- Statistics -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">
                                    <i class="ri-bar-chart-box-line me-2"></i>Progress Overview
                                </h6>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="stats-card">
                                            <div class="stat-icon mb-2" style="background: #d1fae5; color: #10b981;">
                                                <i class="ri-checkbox-circle-fill"></i>
                                            </div>
                                            <div class="small text-muted">Answered</div>
                                            <div class="h4 mb-0 fw-bold" id="answeredCount">0</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stats-card">
                                            <div class="stat-icon mb-2" style="background: #fef3c7; color: #f59e0b;">
                                                <i class="ri-question-line"></i>
                                            </div>
                                            <div class="small text-muted">Remaining</div>
                                            <div class="h4 mb-0 fw-bold" id="unansweredCount">0</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="stats-card">
                                            <div class="stat-icon mb-2" style="background: #e0e7ff; color: #667eea;">
                                                <i class="ri-star-line"></i>
                                            </div>
                                            <div class="small text-muted">Total Marks Attempted</div>
                                            <div class="h4 mb-0 fw-bold" id="marksAttempted">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Question Navigator -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">
                                    <i class="ri-layout-grid-line me-2"></i>Question Navigator
                                </h6>
                                <div id="questionNavigator" class="d-flex flex-wrap gap-2">
                                    <!-- Question buttons will be generated here -->
                                </div>
                            </div>

                            <!-- Notes Section -->
                            <div class="notes-container">
                                <h6 class="fw-bold mb-3">
                                    <i class="ri-sticky-note-line me-2"></i>Question Notes
                                </h6>
                                <textarea id="questionNotes" class="form-control notes-textarea" rows="4" placeholder="Add your notes for this question..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="ri-image-line me-2"></i>Question Image
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="modalImage" src="" alt="Question Image" class="img-fluid" style="border-radius: 12px;">
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const duration = {{ $exam->duration ?? 0 }} * 60;
    let timeLeft = duration;
    const timerElement = document.getElementById('examTimer');
    let timer;

    if (!timerElement) {
        console.error('Timer element not found');
    } else if (isNaN(duration) || duration <= 0) {
        console.error('Invalid duration');
        timerElement.textContent = 'Timer Error';
    } else {
        const startTimeStr = localStorage.getItem('examStartTime');
        if (startTimeStr !== null) {
            const startTime = parseInt(startTimeStr, 10);
            const now = Date.now();
            const elapsedMs = now - startTime;
            const elapsedSeconds = Math.floor(elapsedMs / 1000);

            if (elapsedSeconds >= duration) {
                localStorage.removeItem('examStartTime');
                localStorage.removeItem('examTimeLeft');
                localStorage.removeItem('examAnswers');
                localStorage.removeItem('examNotes');
                Swal.fire({
                    title: 'Time Expired',
                    text: 'Your exam time has run out. Submitting now.',
                    icon: 'warning',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => submitExam(true));
                return;
            } else {
                timeLeft = duration - elapsedSeconds;
                localStorage.removeItem('examTimeLeft');
            }
        } else {
            const now = Date.now();
            localStorage.setItem('examStartTime', now.toString());
        }

        timer = setInterval(() => {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;

            timerElement.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            if (timeLeft <= 300 && timeLeft > 0) {
                timerElement.classList.add('pulse-animation');
            }

            localStorage.setItem('examTimeLeft', timeLeft);

            if (timeLeft <= 0) {
                clearInterval(timer);
                localStorage.removeItem('examStartTime');
                localStorage.removeItem('examTimeLeft');
                localStorage.removeItem('examAnswers');
                localStorage.removeItem('examNotes');
                submitExam(true);
            }
            timeLeft--;
        }, 1000);
    }

    const questions = @json($questions);

    if (!questions || questions.length === 0) {
        console.error('No questions loaded');
        document.getElementById('questionText').innerHTML = 'No questions available';
        return;
    }

    document.getElementById('totalQuestions').textContent = questions.length;

    let currentQuestion = 0;
    let answers = new Array(questions.length).fill('');
    let notes = new Array(questions.length).fill('');
    let currentFontSize = 16;
    let currentZoom = 1;

    const savedAnswers = localStorage.getItem('examAnswers');
    const savedNotes = localStorage.getItem('examNotes');
    if (savedAnswers) answers = JSON.parse(savedAnswers);
    if (savedNotes) notes = JSON.parse(savedNotes);

    // Calculator functionality
    document.getElementById('calculatorBtn').addEventListener('click', () => {
        showCalculator();
    });

    function showCalculator() {
        Swal.fire({
            title: '<i class="ri-calculator-line me-2"></i>Calculator',
            html: `
                <div class="calculator-container">
                    <div class="calculator-display" id="calcDisplay">0</div>
                    <div class="calculator-buttons">
                        <button class="calc-btn clear" onclick="calcClear()">C</button>
                        <button class="calc-btn operator" onclick="calcInput('/')">/</button>
                        <button class="calc-btn operator" onclick="calcInput('*')">×</button>
                        <button class="calc-btn operator" onclick="calcBackspace()">⌫</button>

                        <button class="calc-btn" onclick="calcInput('7')">7</button>
                        <button class="calc-btn" onclick="calcInput('8')">8</button>
                        <button class="calc-btn" onclick="calcInput('9')">9</button>
                        <button class="calc-btn operator" onclick="calcInput('-')">−</button>

                        <button class="calc-btn" onclick="calcInput('4')">4</button>
                        <button class="calc-btn" onclick="calcInput('5')">5</button>
                        <button class="calc-btn" onclick="calcInput('6')">6</button>
                        <button class="calc-btn operator" onclick="calcInput('+')">+</button>

                        <button class="calc-btn" onclick="calcInput('1')">1</button>
                        <button class="calc-btn" onclick="calcInput('2')">2</button>
                        <button class="calc-btn" onclick="calcInput('3')">3</button>
                        <button class="calc-btn operator" onclick="calcInput('%')">%</button>

                        <button class="calc-btn zero" onclick="calcInput('0')">0</button>
                        <button class="calc-btn" onclick="calcInput('.')">.</button>
                        <button class="calc-btn equals" onclick="calcEquals()">=</button>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            width: 'auto',
            padding: '1rem',
            background: 'transparent',
            backdrop: 'rgba(0,0,0,0.4)',
            customClass: {
                popup: 'calculator-popup'
            }
        });
    }

    // Font size controls
    document.getElementById('fontSizeIncrease').addEventListener('click', () => {
        currentFontSize += 2;
        updateFontSize();
    });

    document.getElementById('fontSizeDecrease').addEventListener('click', () => {
        if (currentFontSize > 12) {
            currentFontSize -= 2;
            updateFontSize();
        }
    });

    function updateFontSize() {
        document.querySelector('.question-text').style.fontSize = `${currentFontSize}px`;
        document.querySelectorAll('.form-check-label').forEach(label => {
            label.style.fontSize = `${currentFontSize}px`;
        });
    }

    // Zoom controls
    document.getElementById('zoomIn').addEventListener('click', () => {
        currentZoom += 0.1;
        updateZoom();
    });

    document.getElementById('zoomOut').addEventListener('click', () => {
        if (currentZoom > 0.1) {
            currentZoom -= 0.1;
            updateZoom();
        }
    });

    document.getElementById('zoomReset').addEventListener('click', () => {
        currentZoom = 1;
        updateZoom();
    });

    function updateZoom() {
        const img = document.getElementById('questionImage');
        if (img) {
            img.style.transform = `scale(${currentZoom})`;
            img.style.transformOrigin = 'center center';
            img.style.transition = 'transform 0.3s ease';
        }
    }

    // Image modal
    document.getElementById('questionImage').addEventListener('click', () => {
        const img = document.getElementById('questionImage');
        if (img.src && img.src !== window.location.href) {
            document.getElementById('modalImage').src = img.src;
            const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }
    });

    function loadQuestion(index) {
        const question = questions[index];
        document.getElementById('currentQuestionNum').textContent = index + 1;
        document.getElementById('currentQuestionNum2').textContent = index + 1;
        document.getElementById('questionText').innerHTML = question.text;

        // Display question type badge
        const typeBadge = document.getElementById('questionTypeBadge');
        const typeText = document.getElementById('questionTypeText');
        if (question.type) {
            typeBadge.style.display = 'inline-flex';
            let typeLabel = '';
            let iconClass = '';

            switch(question.type) {
                case 'mcq':
                    typeLabel = 'MCQ';
                    iconClass = 'ri-checkbox-multiple-line';
                    break;
                case 'true_false':
                    typeLabel = 'True/False';
                    iconClass = 'ri-toggle-line';
                    break;
                case 'short_answer':
                    typeLabel = 'Short Answer';
                    iconClass = 'ri-edit-line';
                    break;
                default:
                    typeLabel = question.type;
                    iconClass = 'ri-question-line';
            }

            typeText.textContent = typeLabel;
            // Update icon
            const icon = typeBadge.querySelector('i');
            icon.className = iconClass + ' me-1';
        } else {
            typeBadge.style.display = 'none';
        }

        // Display marks for the question - ALWAYS show marks
        const marksBadge = document.getElementById('questionMarksBadge');
        const marksText = document.getElementById('questionMarks');

        // Get marks, default to 1 if not set
        const marks = parseFloat(question.marks) || 1;
        marksText.textContent = marks.toFixed(1); // Show with 1 decimal place

        // Always show the marks badge
        marksBadge.style.display = 'flex';

        // Color code based on marks value
        if (marks >= 5) {
            marksBadge.className = 'badge bg-danger marks-badge';
        } else if (marks >= 3) {
            marksBadge.className = 'badge bg-warning marks-badge';
        } else if (marks > 1) {
            marksBadge.className = 'badge bg-primary marks-badge';
        } else {
            marksBadge.className = 'badge bg-success marks-badge'; // 1 mark = success color
        }

        // Save notes from previous question
        if (currentQuestion !== index) {
            notes[currentQuestion] = document.getElementById('questionNotes').value;
            localStorage.setItem('examNotes', JSON.stringify(notes));
        }

        // Load image
        const imageContainer = document.getElementById('questionImageContainer');
        const questionImage = document.getElementById('questionImage');

        if (question.image_url) {
            imageContainer.style.display = 'block';
            questionImage.src = question.image_url;
            questionImage.alt = `Image for question ${index + 1}`;
            questionImage.onerror = () => {
                imageContainer.style.display = 'none';
            };
            questionImage.onload = () => {
                currentZoom = 1;
                updateZoom();
            };
        } else {
            imageContainer.style.display = 'none';
        }

        // Load options based on question type
        const optionsContainer = document.getElementById('optionsContainer');
        optionsContainer.innerHTML = '';

        switch(question.type) {
            case 'mcq':
                loadMCQOptions(question, index);
                break;
            case 'true_false':
                loadTrueFalseOptions(question, index);
                break;
            case 'short_answer':
                loadShortAnswerInput(question, index);
                break;
            default:
                optionsContainer.innerHTML = '<div class="alert alert-warning">Unknown question type</div>';
        }

        document.getElementById('questionNotes').value = notes[index] || '';
        currentQuestion = index;
        updateFontSize();
        updateNavigation();
    }

    function loadMCQOptions(question, index) {
        const optionsContainer = document.getElementById('optionsContainer');
        const optionLetters = ['A', 'B', 'C', 'D', 'E', 'F'];

        question.options.forEach((option, i) => {
            if (option.text) {
                const div = document.createElement('div');
                div.className = `option-card ${answers[index] === option.text ? 'selected' : ''}`;
                div.innerHTML = `
                    <label class="d-flex align-items-center w-100 cursor-pointer m-0">
                        <span class="option-letter">${optionLetters[i]}</span>
                        <input class="form-check-input question-option d-none"
                               type="radio"
                               name="answer"
                               value="${option.text}"
                               data-question-id="${question.id}"
                               ${answers[index] === option.text ? 'checked' : ''}>
                        <span class="form-check-label flex-grow-1">${option.text}</span>
                    </label>
                `;

                div.addEventListener('click', function() {
                    document.querySelectorAll('.option-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    const input = this.querySelector('input');
                    input.checked = true;
                    answers[currentQuestion] = input.value;
                    localStorage.setItem('examAnswers', JSON.stringify(answers));
                    updateNavigation();
                });

                optionsContainer.appendChild(div);
            }
        });
    }

    function loadTrueFalseOptions(question, index) {
        const optionsContainer = document.getElementById('optionsContainer');
        optionsContainer.innerHTML = `
            <div class="true-false-container">
                <button class="true-false-btn true ${answers[index] === 'True' ? 'selected' : ''}" data-value="True">
                    <i class="ri-check-line"></i> True
                </button>
                <button class="true-false-btn false ${answers[index] === 'False' ? 'selected' : ''}" data-value="False">
                    <i class="ri-close-line"></i> False
                </button>
            </div>
        `;

        // Add event listeners for True/False buttons
        document.querySelectorAll('.true-false-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.true-false-btn').forEach(b => {
                    b.classList.remove('selected');
                });
                this.classList.add('selected');
                answers[currentQuestion] = this.dataset.value;
                localStorage.setItem('examAnswers', JSON.stringify(answers));
                updateNavigation();
            });
        });
    }

    function loadShortAnswerInput(question, index) {
        const optionsContainer = document.getElementById('optionsContainer');
        optionsContainer.innerHTML = `
            <div class="short-answer-container">
                <input type="text"
                       class="form-control short-answer-input ${answers[index] ? 'answered' : ''}"
                       placeholder="Type your answer here..."
                       value="${answers[index] || ''}">
            </div>
        `;

        const input = optionsContainer.querySelector('.short-answer-input');
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.add('answered');
                answers[currentQuestion] = this.value;
            } else {
                this.classList.remove('answered');
                answers[currentQuestion] = '';
            }
            localStorage.setItem('examAnswers', JSON.stringify(answers));
            updateNavigation();
        });

        // Also save on blur
        input.addEventListener('blur', function() {
            localStorage.setItem('examAnswers', JSON.stringify(answers));
        });
    }

    function calculateMarksAttempted() {
        let marks = 0;
        questions.forEach((question, index) => {
            if (answers[index] && answers[index].trim() !== '') {
                marks += parseFloat(question.marks) || 1;
            }
        });
        return marks.toFixed(1);
    }

    function updateNavigation() {
        const navigator = document.getElementById('questionNavigator');
        navigator.innerHTML = '';

        let answeredCount = 0;
        let marksAttempted = 0;

        questions.forEach((question, index) => {
            const btn = document.createElement('button');
            btn.className = `question-nav-btn ${answers[index] && answers[index].trim() !== '' ? 'answered' : 'unanswered'} ${index === currentQuestion ? 'active' : ''}`;

            // Add marks indicator for questions with high marks
            const questionMarks = parseFloat(question.marks) || 1;
            if (questionMarks >= 3) {
                btn.classList.add('with-marks');
                const marksIndicator = document.createElement('span');
                marksIndicator.className = 'marks-indicator';
                marksIndicator.textContent = questionMarks;
                btn.appendChild(marksIndicator);
            }

            btn.textContent = index + 1;
            btn.onclick = () => loadQuestion(index);
            navigator.appendChild(btn);

            if (answers[index] && answers[index].trim() !== '') {
                answeredCount++;
                marksAttempted += questionMarks;
            }
        });

        document.getElementById('answeredCount').textContent = answeredCount;
        document.getElementById('unansweredCount').textContent = questions.length - answeredCount;
        document.getElementById('marksAttempted').textContent = marksAttempted.toFixed(1);

        // Update navigation buttons
        document.getElementById('prevQuestion').disabled = currentQuestion === 0;
        document.getElementById('nextQuestion').disabled = currentQuestion === questions.length - 1;
    }

    // Navigation
    document.getElementById('prevQuestion').onclick = () => {
        if (currentQuestion > 0) loadQuestion(currentQuestion - 1);
    };

    document.getElementById('nextQuestion').onclick = () => {
        if (currentQuestion < questions.length - 1) loadQuestion(currentQuestion + 1);
    };

    // Submit exam
    @if(auth()->user()->can('Submit cbt-exam'))
    document.getElementById('submitExam').onclick = () => {
        Swal.fire({
            title: 'Submit Exam?',
            text: 'Are you sure you want to submit your exam? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, submit it!',
            cancelButtonText: 'No, keep working'
        }).then((result) => {
            if (result.isConfirmed) {
                submitExam(false);
            }
        });
    };
    @endif

    // Notes with debounce
    let notesTimeout;
    document.getElementById('questionNotes').addEventListener('input', (e) => {
        clearTimeout(notesTimeout);
        notesTimeout = setTimeout(() => {
            notes[currentQuestion] = e.target.value;
            localStorage.setItem('examNotes', JSON.stringify(notes));
        }, 500);
    });

    // Submit function
    function submitExam(isAutoSubmit = false) {
        notes[currentQuestion] = document.getElementById('questionNotes').value;
        localStorage.setItem('examNotes', JSON.stringify(notes));

        if (!isAutoSubmit) {
            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while we submit your exam',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        const submissionData = {
            attempt_id: {{ $attempt->id }},
            exam_id: {{ $exam->id }},
            answers: questions.map((q, index) => ({
                question_id: q.id,
                answer: answers[index] || '',
                notes: notes[index] || null
            }))
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('CSRF token missing');
            return Swal.fire('Error', 'Security token missing. Please refresh and try again.', 'error');
        }

        fetch('/cbt/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(submissionData)
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                if (response.status === 419) {
                    throw new Error('Security token expired. Refreshing page...');
                }
                throw new Error(data.message || `Server error: ${response.status}`);
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                localStorage.removeItem('examStartTime');
                localStorage.removeItem('examTimeLeft');
                localStorage.removeItem('examAnswers');
                localStorage.removeItem('examNotes');

                Swal.fire({
                    title: 'Success!',
                    text: 'Your exam has been submitted successfully',
                    icon: 'success',
                    confirmButtonColor: '#667eea',
                    confirmButtonText: 'View Results'
                }).then(() => {
                    window.location.href = '{{ route("cbt.index") }}';
                });
            } else {
                throw new Error(data.message || 'Unknown submission error');
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            if (isAutoSubmit || error.message.includes('419')) {
                if (error.message.includes('419')) {
                    Swal.fire('Token Expired', 'Refreshing page to retry...', 'warning').then(() => {
                        location.reload();
                    });
                } else {
                    console.log('Auto-submit failed; retrying in 5s...');
                    setTimeout(() => submitExam(true), 5000);
                }
            } else {
                Swal.fire({
                    title: 'Submission Failed',
                    text: error.message + '. Your answers are saved locally—retry or contact support.',
                    icon: 'error',
                    confirmButtonColor: '#667eea',
                    confirmButtonText: 'Retry'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitExam(false);
                    }
                });
            }
        });
    }

    // Network status handling
    window.addEventListener('offline', () => {
        clearInterval(timer);
        Swal.fire({
            title: 'Network Disconnected',
            text: 'Your progress is saved. The exam will resume when the network is restored.',
            icon: 'warning',
            confirmButtonColor: '#667eea',
            confirmButtonText: 'OK'
        });
    });

    window.addEventListener('online', () => {
        if (timeLeft > 0) {
            timer = setInterval(() => {
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                const seconds = timeLeft % 60;

                timerElement.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

                if (timeLeft <= 300 && timeLeft > 0) {
                    timerElement.classList.add('pulse-animation');
                }

                localStorage.setItem('examTimeLeft', timeLeft);

                if (timeLeft <= 0) {
                    clearInterval(timer);
                    localStorage.removeItem('examStartTime');
                    localStorage.removeItem('examTimeLeft');
                    localStorage.removeItem('examAnswers');
                    localStorage.removeItem('examNotes');
                    submitExam(true);
                }
                timeLeft--;
            }, 1000);

            Swal.fire({
                title: 'Network Restored',
                text: 'Exam resumed successfully',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            submitExam(true);
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            if (currentQuestion > 0) loadQuestion(currentQuestion - 1);
        }
        else if (e.key === 'ArrowRight') {
            if (currentQuestion < questions.length - 1) loadQuestion(currentQuestion + 1);
        }
        else if (/^[1-9]$/.test(e.key)) {
            const question = questions[currentQuestion];
            if (question.type === 'mcq') {
                const optionIndex = parseInt(e.key) - 1;
                const options = document.querySelectorAll('.question-option');
                if (optionIndex < options.length) {
                    const optionCard = options[optionIndex].closest('.option-card');
                    if (optionCard) {
                        optionCard.click();
                    }
                }
            }
        }
    });

    // Prevent accidental page leave
    window.addEventListener('beforeunload', (e) => {
        if (timeLeft > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Initialize
    loadQuestion(0);
    updateNavigation();
});

// Calculator functions (global scope for SweetAlert HTML)
let calcCurrentValue = '0';
let calcPreviousValue = '';
let calcOperation = null;

function calcInput(value) {
    const display = document.getElementById('calcDisplay');
    if (calcCurrentValue === '0' || calcCurrentValue === 'Error') {
        calcCurrentValue = value;
    } else {
        calcCurrentValue += value;
    }
    display.textContent = calcCurrentValue;
}

function calcClear() {
    const display = document.getElementById('calcDisplay');
    calcCurrentValue = '0';
    calcPreviousValue = '';
    calcOperation = null;
    display.textContent = '0';
}

function calcBackspace() {
    const display = document.getElementById('calcDisplay');
    if (calcCurrentValue.length > 1) {
        calcCurrentValue = calcCurrentValue.slice(0, -1);
    } else {
        calcCurrentValue = '0';
    }
    display.textContent = calcCurrentValue;
}

function calcEquals() {
    const display = document.getElementById('calcDisplay');
    try {
        // Replace × with * for evaluation
        const expression = calcCurrentValue.replace(/×/g, '*').replace(/−/g, '-');
        const result = eval(expression);
        calcCurrentValue = result.toString();
        display.textContent = calcCurrentValue;
    } catch (error) {
        display.textContent = 'Error';
        calcCurrentValue = 'Error';
    }
}
</script>

@endsection
