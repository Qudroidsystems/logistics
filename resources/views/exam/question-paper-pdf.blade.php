<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $exam->title }} - {{ $student->firstname }} {{ $student->lastname }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0d6efd;
        }
        .school-logo {
            max-width: 100px;
            max-height: 100px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .school-details {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        .exam-title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }
        .examiner-info {
            margin-top: 5px;
            font-size: 11px;
            color: #555;
            text-align: right;
            font-style: italic;
        }
        .student-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .student-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #0d6efd;
        }
        .student-details {
            flex: 1;
        }
        .student-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .student-details td {
            padding: 5px;
        }
        .student-details .label {
            font-weight: bold;
            width: 120px;
        }
        .stats {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #0d6efd;
        }
        .stats table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats td {
            padding: 5px;
            text-align: center;
        }
        .stats .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #0d6efd;
        }
        .question {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .question-header {
            background: #f8f9fa;
            padding: 8px;
            margin: -10px -10px 10px -10px;
            border-radius: 5px 5px 0 0;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .question-text {
            margin-bottom: 15px;
            font-weight: 500;
        }
        .question-image-container {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background: #fafafa;
        }
        .question-image {
            max-width: 100%;
            max-height: 300px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: inline-block;
        }
        .options {
            margin-left: 20px;
        }
        .option {
            margin-bottom: 8px;
            padding: 8px 10px;
            border-radius: 3px;
            border: 1px solid #e9ecef;
        }
        .option.correct {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            border-top: 1px solid #c3e6cb;
            border-right: 1px solid #c3e6cb;
            border-bottom: 1px solid #c3e6cb;
        }
        .option.selected {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            border-top: 1px solid #ffeeba;
            border-right: 1px solid #ffeeba;
            border-bottom: 1px solid #ffeeba;
        }
        .option.selected.correct {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            border-top: 1px solid #c3e6cb;
            border-right: 1px solid #c3e6cb;
            border-bottom: 1px solid #c3e6cb;
        }
        .option.selected.incorrect {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            border-top: 1px solid #f5c6cb;
            border-right: 1px solid #f5c6cb;
            border-bottom: 1px solid #f5c6cb;
        }
        .mark {
            font-size: 11px;
            color: #6c757d;
            margin-left: auto;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 3px;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .correct-answer-box {
            margin-top: 12px;
            padding: 12px 15px;
            background-color: #e8f4fd;
            border-left: 4px solid #17a2b8;
            border-radius: 5px;
        }
        .correct-answer-text {
            margin-top: 8px;
            padding: 8px 12px;
            background-color: #ffffff;
            border: 1px solid #b8e2f2;
            border-radius: 4px;
            font-family: 'DejaVu Sans', monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($school && $school->school_logo)
            <img src="{{ $school->logo_url }}" alt="School Logo" class="school-logo">
        @endif
        <div class="school-name">{{ $school->school_name ?? 'School Name' }}</div>
        @if($school && $school->school_motto)
            <div class="school-details">"{{ $school->school_motto }}"</div>
        @endif
        @if($school && ($school->school_address || $school->school_phone || $school->school_email))
            <div class="school-details">
                {{ $school->school_address ?? '' }}
                @if($school->school_phone || $school->school_email)
                    <br>Phone: {{ $school->school_phone ?? '' }} | Email: {{ $school->school_email ?? '' }}
                @endif
            </div>
        @endif
        <div class="exam-title">{{ strtoupper($exam->title) }}</div>
        <div>{{ $exam->subject->subject ?? 'Subject' }} ({{ $exam->subject->subject_code ?? 'Code' }})</div>
        <div class="examiner-info">Examiner: {{ auth()->user()->name ?? 'N/A' }}</div>
    </div>

    <div class="student-info">
        <img src="{{ $student->picture_path }}" alt="Student Photo" class="student-photo">
        <div class="student-details">
            <table>
                <tr>
                    <td class="label">Student Name:</td>
                    <td><strong>{{ $student->lastname }} {{ $student->firstname }}</strong></td>
                    <td class="label">Admission No:</td>
                    <td><strong>{{ $student->admissionNo }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Class:</td>
                    <td>{{ $exam->schoolclass->schoolclass ?? 'N/A' }} {{ $exam->schoolclass->arm ?? '' }}</td>
                    <td class="label">Term/Session:</td>
                    <td>{{ $exam->termRelation->term ?? 'N/A' }} | {{ $exam->sessionRelation->session ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Exam Date:</td>
                    <td>{{ $attempt->created_at->format('F j, Y') ?? now()->format('F j, Y') }}</td>
                    <td class="label">Duration:</td>
                    <td>{{ $exam->duration }} minutes</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="stats">
        <table>
            <tr>
                <td>
                    <div class="stat-value">{{ $totalQuestions ?? 0 }}</div>
                    <div>Total Questions</div>
                </td>
                <td>
                    <div class="stat-value">{{ $attemptedQuestions ?? 0 }}</div>
                    <div>Attempted</div>
                </td>
                <td>
                    <div class="stat-value">{{ $correctAnswers ?? 0 }}</div>
                    <div>Correct</div>
                </td>
                <td>
                    <div class="stat-value">{{ $score ?? 0 }}</div>
                    <div>Score</div>
                </td>
                <td>
                    <div class="stat-value">{{ $percentage ?? 0 }}%</div>
                    <div>Percentage</div>
                </td>
            </tr>
        </table>
    </div>

    <h3>Questions and Answers</h3>

    @foreach($questions as $index => $question)
        <div class="question">
            <div class="question-header">
                <span>Question {{ $index + 1 }}</span>
                <span class="mark">{{ number_format($question->marks, 1) }} marks</span>
                @if(isset($question->is_correct))
                    @if($question->is_correct)
                        <span class="badge badge-success">Correct</span>
                    @elseif($question->student_answer !== 'Not Attempted' && $question->student_answer !== 'Not answered')
                        <span class="badge badge-danger">Incorrect</span>
                    @else
                        <span class="badge badge-warning">Not Attempted</span>
                    @endif
                @endif
            </div>

            <div class="question-text">
                <strong>{!! $question->question_text !!}</strong>
            </div>

            @if($question->image)
                <div class="question-image-container">
                    @php
                        // Get the raw image path from the database
                        $imagePath = $question->image;

                        // Base URL for storage (update this to match your domain)
                        $baseStorageUrl = 'https://csskabba.qudroid.co/storage/';

                        // Clean the path - remove any storage/app/public/ or public/ prefixes
                        $cleanPath = preg_replace('/^(.*?)(storage\/app\/public\/|public\/)/', '', $imagePath);

                        // If the path already starts with http, use it as is
                        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                            $imageUrl = $imagePath;
                        }
                        // If it's a relative path, construct the full URL
                        else {
                            // Remove any leading slashes
                            $cleanPath = ltrim($cleanPath, '/');
                            $imageUrl = $baseStorageUrl . $cleanPath;
                        }
                    @endphp
                    <img src="{{ $imageUrl }}" alt="Question image" class="question-image">
                </div>
            @endif

            @if($question->type === 'short_answer')
                <div class="options">
                    <div class="option {{ isset($question->is_correct) ? ($question->is_correct ? 'correct' : 'selected incorrect') : '' }}">
                        <strong>Student's Answer:</strong>
                        {{ $question->student_answer ?? 'Not answered' }}
                        @if(isset($question->is_correct))
                            @if($question->is_correct)
                                <span class="badge badge-success">Correct</span>
                            @else
                                <span class="badge badge-danger">Incorrect</span>
                            @endif
                        @endif
                    </div>
                    @if(isset($question->correct_answer_text) && $question->student_answer !== $question->correct_answer_text)
                        <div class="correct-answer-box">
                            <strong>Correct Answer:</strong>
                            <div class="correct-answer-text">{{ $question->correct_answer_text }}</div>
                        </div>
                    @endif
                </div>
            @else
                <div class="options">
                    @foreach($question->options as $option)
                        @php
                            $optionClass = '';
                            if ($option->is_correct) {
                                $optionClass = 'correct';
                            }
                            if (isset($question->selected_option_id) && $question->selected_option_id == $option->id) {
                                $optionClass .= ' selected';
                                if (!$option->is_correct) {
                                    $optionClass .= ' incorrect';
                                }
                            }
                        @endphp
                        <div class="option {{ $optionClass }}">
                            @if($question->type === 'true_false')
                                <strong>{{ ucfirst($option->label) }}:</strong>
                            @else
                                <strong>{{ $option->label }}.</strong>
                            @endif
                            {{ $option->option_text }}
                            @if($option->is_correct)
                                <span style="color: #28a745; margin-left: 10px;">✓ Correct Answer</span>
                            @endif
                            @if(isset($question->selected_option_id) && $question->selected_option_id == $option->id)
                                <span style="color: #ffc107; margin-left: 10px;">← Student's Choice</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <p>Generated on {{ now()->format('F j, Y \a\t h:i A') }} | {{ $school->school_name ?? 'School Name' }}</p>
        <p>Examiner: {{ auth()->user()->name ?? 'N/A' }} | This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
