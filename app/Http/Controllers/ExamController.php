<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Schoolterm;
use App\Models\ExamAttempt;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\SubjectTeacher;
use App\Models\Assessment;
use App\Models\SubAssessment;
use App\Models\Broadsheets;
use App\Models\BroadsheetRecord;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetSubAssessmentScore;
use App\Models\Subjectclass;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SchoolInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ExamController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View exam', ['only' => ['index', 'show', 'showStudents', 'showStudentAnswers', 'analytics', 'showTransferSubjects', 'showTransferScoresheet']]);
        $this->middleware('permission:Create exam', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update exam', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete exam', ['only' => ['destroy', 'bulkDestroy', 'deleteStudentAttempt']]);
    }

    /**
     * Display a listing of exams.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $terms = Schoolterm::all();
        $sessions = Schoolsession::all();

        $query = Exam::query()
            ->with([
                'schoolclass' => function($query) {
                    $query->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
                          ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm');
                },
                'subject:id,subject,subject_code',
                'termRelation:id,term',
                'sessionRelation:id,session'
            ])
            ->withCount('questions')
            ->where('staffId', $user->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('subject', function($q) use ($search) {
                      $q->where('subject', 'like', "%{$search}%");
                  });
            });
        }

        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        $exams = $query->paginate(15);

        $mysubjects = SubjectTeacher::where('staffid', $user->id)
            ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->join('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->join('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->select([
                'subject.id as subject_id',
                'subject.subject as subject_name',
                'subject.subject_code',
                'schoolterm.term as term_name',
                'schoolterm.id as term_id',
                'schoolsession.session as session_name',
                'schoolsession.id as session_id',
                'subjectteacher.termid',
                'subjectteacher.sessionid'
            ])
            ->distinct()
            ->get()
            ->unique('subject_id')
            ->sortBy('subject_name')
            ->values();

        $myclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(
                'schoolclass.id as schoolclassID',
                'schoolclass.schoolclass',
                'schoolarm.arm as arm_name'
            )->get();

        $totalExams = $exams->total();
        $totalQuestions = $exams->sum('questions_count');
        $totalClasses = $myclass->count();
        $totalSubjects = $mysubjects->count();

        $pagetitle = 'Exams Management';

        return view('exam.index', compact(
            'pagetitle',
            'exams',
            'terms',
            'sessions',
            'mysubjects',
            'myclass',
            'totalExams',
            'totalQuestions',
            'totalClasses',
            'totalSubjects'
        ));
    }

    /**
     * Show the form for creating a new exam.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created exam in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'staffId'         => 'required|integer',
                'title'           => 'required|string|max:255',
                'description'     => 'nullable|string',
                'duration'        => 'required|integer|min:1',
                'start_time'      => 'required|date',
                'end_time'        => 'required|date|after:start_time',
                'termid'          => 'required|integer|exists:schoolterm,id',
                'session'         => 'required|integer|exists:schoolsession,id',
                'subject_id'      => 'required|integer|exists:subject,id',
                'schoolclass_ids' => 'required|array|min:1',
                'schoolclass_ids.*' => 'integer|exists:schoolclass,id',
                'is_published'    => 'boolean|nullable',
            ]);

            $startTime = strtotime($validated['start_time']);
            $endTime = strtotime($validated['end_time']);
            $durationMinutes = $validated['duration'];
            $totalMinutes = round(($endTime - $startTime) / 60);

            if ($durationMinutes > $totalMinutes) {
                return response()->json([
                    'success' => false,
                    'message' => "Duration ({$durationMinutes} minutes) exceeds the time between start and end ({$totalMinutes} minutes). Please adjust.",
                    'errors' => ['duration' => 'Duration exceeds available time']
                ], 422);
            }

            $validated['is_published'] = $request->has('is_published');

            $createdCount = 0;
            $createdExams = [];

            foreach ($validated['schoolclass_ids'] as $classId) {
                $examData = $validated;
                $examData['schoolclass_id'] = $classId;
                unset($examData['schoolclass_ids']);
                $exam = Exam::create($examData);
                $createdExams[] = $exam;
                $createdCount++;
            }

            $message = "Exam created successfully for {$createdCount} class" . ($createdCount > 1 ? 'es' : '.') . ".";

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'exams' => $createdExams
                ]);
            }

            return redirect()->route('exams.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error creating exam: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while creating the exam. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while creating the exam.');
        }
    }

    /**
     * Display the specified exam.
     */
    public function show($id)
    {
        $user = Auth::user();

        $exam = Exam::with([
                'schoolclass' => function($query) {
                    $query->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
                          ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm');
                },
                'subject:id,subject',
                'termRelation:id,term',
                'sessionRelation:id,session'
            ])
            ->where('id', $id)
            ->where('staffId', $user->id)
            ->firstOrFail();

        $questions = Question::with('options')
            ->where('exam_id', $id)
            ->orderBy('order')
            ->get();

        $term = Schoolterm::find($exam->termid);
        $session = Schoolsession::find($exam->session);

        $pagetitle = 'Questions for: ' . $exam->title;

        return view('questions.show', compact(
            'pagetitle',
            'exam',
            'questions',
            'term',
            'session'
        ));
    }

    /**
     * Show the form for editing the specified exam.
     */
    public function edit(string $id)
    {
        $exam = Exam::where('id', $id)->where('staffId', auth()->user()->id)->firstOrFail();

        $groupExams = Exam::where('staffId', $exam->staffId)
            ->where('title', $exam->title)
            ->where('subject_id', $exam->subject_id)
            ->where('termid', $exam->termid)
            ->where('session', $exam->session)
            ->get();

        $groupClassIds = $groupExams->pluck('schoolclass_id')->toArray();

        if (request()->ajax()) {
            return response()->json([
                'success'         => true,
                'exam'            => $exam,
                'schoolclass_ids' => $groupClassIds,
                'termid'          => $exam->termid,
                'sessionid'       => $exam->session,
                'subject_id'      => $exam->subject_id
            ]);
        }

        abort(404);
    }

    /**
     * Update the specified exam in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            Log::info('Update request received:', [
                'exam_id' => $id,
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            $exam = Exam::where('id', $id)->where('staffId', auth()->user()->id)->firstOrFail();

            $validated = $request->validate([
                'title'           => 'required|string|max:255',
                'description'     => 'nullable|string',
                'duration'        => 'required|integer|min:1',
                'start_time'      => 'required|date',
                'end_time'        => 'required|date|after:start_time',
                'termid'          => 'required|integer|exists:schoolterm,id',
                'session'         => 'required|integer|exists:schoolsession,id',
                'subject_id'      => 'required|integer|exists:subject,id',
                'schoolclass_ids' => 'required|array|min:1',
                'schoolclass_ids.*' => 'integer|exists:schoolclass,id',
                'is_published'    => 'boolean|nullable',
                'copy_questions'  => 'boolean|nullable',
                'copy_all_questions' => 'boolean|nullable',
                'selected_questions' => 'nullable|array',
                'selected_questions.*' => 'integer|exists:questions,id',
            ]);

            $startTime = strtotime($validated['start_time']);
            $endTime = strtotime($validated['end_time']);
            $durationMinutes = $validated['duration'];
            $totalMinutes = round(($endTime - $startTime) / 60);

            if ($durationMinutes > $totalMinutes) {
                return response()->json([
                    'success' => false,
                    'message' => "Duration ({$durationMinutes} minutes) exceeds the time between start and end ({$totalMinutes} minutes). Please adjust.",
                    'errors' => ['duration' => 'Duration exceeds available time']
                ], 422);
            }

            $validated['is_published'] = $request->has('is_published');
            $validated['staffId'] = $exam->staffId;

            $originalGroupExams = Exam::where('staffId', $exam->staffId)
                ->where('title', $exam->title)
                ->where('subject_id', $exam->subject_id)
                ->where('termid', $exam->termid)
                ->where('session', $exam->session)
                ->get();

            $originalClassIds = $originalGroupExams->pluck('schoolclass_id')->toArray();
            $newClassIds = $validated['schoolclass_ids'];

            $updatedCount = 0;
            $createdCount = 0;
            $copiedQuestionsCount = 0;

            $classesToUpdate = array_intersect($originalClassIds, $newClassIds);

            foreach ($classesToUpdate as $classId) {
                $existingExam = $originalGroupExams->where('schoolclass_id', $classId)->first();
                if ($existingExam) {
                    $existingExam->update([
                        'title' => $validated['title'],
                        'description' => $validated['description'],
                        'duration' => $validated['duration'],
                        'start_time' => $validated['start_time'],
                        'end_time' => $validated['end_time'],
                        'termid' => $validated['termid'],
                        'session' => $validated['session'],
                        'subject_id' => $validated['subject_id'],
                        'is_published' => $validated['is_published']
                    ]);
                    $updatedCount++;
                }
            }

            $classesToCreate = array_diff($newClassIds, $originalClassIds);

            foreach ($classesToCreate as $classId) {
                $newExam = Exam::create([
                    'staffId' => $validated['staffId'],
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'duration' => $validated['duration'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'termid' => $validated['termid'],
                    'session' => $validated['session'],
                    'subject_id' => $validated['subject_id'],
                    'schoolclass_id' => $classId,
                    'is_published' => $validated['is_published']
                ]);
                $createdCount++;

                if ($request->has('copy_questions') && $request->copy_questions) {
                    $sourceExam = $exam;

                    if ($request->has('copy_all_questions') && $request->copy_all_questions) {
                        $copiedCount = $this->copyQuestionsToExam($sourceExam->id, $newExam->id);
                        $copiedQuestionsCount += $copiedCount;
                    } elseif ($request->has('selected_questions') && !empty($request->selected_questions)) {
                        $copiedCount = $this->copySelectedQuestionsToExam($sourceExam->id, $newExam->id, $request->selected_questions);
                        $copiedQuestionsCount += $copiedCount;
                    }
                }
            }

            $message = "Exam updated successfully. ";
            if ($updatedCount > 0) {
                $message .= "Updated {$updatedCount} existing class" . ($updatedCount > 1 ? 'es' : '') . ". ";
            }
            if ($createdCount > 0) {
                $message .= "Added {$createdCount} new class" . ($createdCount > 1 ? 'es' : '') . ". ";
                if ($copiedQuestionsCount > 0) {
                    $message .= "Copied {$copiedQuestionsCount} questions to new class" . ($createdCount > 1 ? 'es' : '') . ". ";
                }
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('exams.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error updating exam: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the exam. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while updating the exam.');
        }
    }

    /**
     * Copy all questions from source exam to target exam
     */
    private function copyQuestionsToExam($sourceExamId, $targetExamId)
    {
        $copiedCount = 0;

        $questions = Question::with('options')
            ->where('exam_id', $sourceExamId)
            ->orderBy('order')
            ->get();

        foreach ($questions as $question) {
            $order = Question::where('exam_id', $targetExamId)->max('order') + 1;

            $newQuestion = Question::create([
                'exam_id' => $targetExamId,
                'question_text' => $question->question_text,
                'type' => $question->type,
                'image' => $question->image,
                'marks' => $question->marks,
                'order' => $order,
                'is_reusable' => $question->is_reusable,
            ]);

            foreach ($question->options as $option) {
                $newQuestion->options()->create([
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                    'label' => $option->label,
                ]);
            }

            $copiedCount++;
        }

        return $copiedCount;
    }

    /**
     * Copy selected questions from source exam to target exam
     */
    private function copySelectedQuestionsToExam($sourceExamId, $targetExamId, $selectedQuestionIds)
    {
        $copiedCount = 0;

        $questions = Question::with('options')
            ->where('exam_id', $sourceExamId)
            ->whereIn('id', $selectedQuestionIds)
            ->orderBy('order')
            ->get();

        foreach ($questions as $question) {
            $order = Question::where('exam_id', $targetExamId)->max('order') + 1;

            $newQuestion = Question::create([
                'exam_id' => $targetExamId,
                'question_text' => $question->question_text,
                'type' => $question->type,
                'image' => $question->image,
                'marks' => $question->marks,
                'order' => $order,
                'is_reusable' => $question->is_reusable,
            ]);

            foreach ($question->options as $option) {
                $newQuestion->options()->create([
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                    'label' => $option->label,
                ]);
            }

            $copiedCount++;
        }

        return $copiedCount;
    }

    /**
     * Get questions for a specific exam to display in copy modal
     */
    public function getExamQuestions($examId)
    {
        try {
            $exam = Exam::where('id', $examId)
                ->where('staffId', auth()->user()->id)
                ->firstOrFail();

            $questions = Question::where('exam_id', $examId)
                ->orderBy('order')
                ->get()
                ->map(function($question) {
                    return [
                        'id' => $question->id,
                        'text' => strip_tags($question->question_text),
                        'type' => $question->type,
                        'marks' => $question->marks,
                        'options_count' => $question->options()->count(),
                    ];
                });

            return response()->json([
                'success' => true,
                'questions' => $questions,
                'exam_title' => $exam->title
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting exam questions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load questions'
            ], 500);
        }
    }

    /**
     * Remove the specified exam from storage.
     */
    public function destroy(string $id)
    {
        try {
            $exam = Exam::where('id', $id)->where('staffId', auth()->user()->id)->firstOrFail();

            $hasAttempts = ExamAttempt::where('exam_id', $exam->id)->exists();

            if ($hasAttempts) {
                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete exam because students have already attempted it.'
                    ], 400);
                }
                return redirect()->route('exams.index')->with('error', 'Cannot delete exam because students have already attempted it.');
            }

            $exam->delete();

            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Exam deleted successfully.']);
            }

            return redirect()->route('exams.index')->with('success', 'Exam deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting exam: ' . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while deleting the exam. Please try again.'
                ], 500);
            }

            return redirect()->route('exams.index')->with('error', 'An error occurred while deleting the exam.');
        }
    }

    /**
     * Remove multiple exams from storage.
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No exams selected'], 400);
            }

            $examsWithAttempts = Exam::whereIn('id', $ids)
                ->where('staffId', auth()->user()->id)
                ->whereHas('attempts')
                ->count();

            if ($examsWithAttempts > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete {$examsWithAttempts} exam(s) because students have already attempted them."
                ], 400);
            }

            $count = Exam::whereIn('id', $ids)
                ->where('staffId', auth()->user()->id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} exam(s) deleted successfully."
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting exams: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting exams. Please try again.'
            ], 500);
        }
    }

    /**
     * Get filtered subjects based on term and session.
     */
    public function getFilteredSubjects(Request $request)
    {
        try {
            $user = auth()->user();

            $query = SubjectTeacher::where('subjectteacher.staffid', $user->id)
                ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->join('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->join('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid');

            if ($request->filled('term_id')) {
                $query->where('subjectteacher.termid', $request->term_id);
            }

            if ($request->filled('session_id')) {
                $query->where('subjectteacher.sessionid', $request->session_id);
            }

            $subjects = $query->select([
                    'subject.id as subject_id',
                    'subject.subject as subject_name',
                    'subject.subject_code',
                    'schoolterm.term as term_name',
                    'schoolterm.id as term_id',
                    'schoolsession.session as session_name',
                    'schoolsession.id as session_id',
                    'subjectteacher.termid',
                    'subjectteacher.sessionid'
                ])
                ->distinct()
                ->get()
                ->unique('subject_id')
                ->sortBy('subject_name')
                ->map(function($item) {
                    $displayText = sprintf('%s (%s) - %s %s',
                        $item->subject_name,
                        $item->subject_code,
                        $item->term_name,
                        $item->session_name
                    );

                    return [
                        'id' => $item->subject_id,
                        'display_text' => $displayText,
                        'subject' => $item->subject_name,
                        'subjectcode' => $item->subject_code,
                        'term' => $item->term_name,
                        'session' => $item->session_name,
                        'termid' => $item->termid,
                        'sessionid' => $item->sessionid
                    ];
                })
                ->values();

            return response()->json(['subjects' => $subjects]);
        } catch (\Exception $e) {
            Log::error('Error getting filtered subjects: ' . $e->getMessage());
            return response()->json(['subjects' => []], 500);
        }
    }

    /**
     * Get classes for a specific subject.
     */
    public function getClassesForSubject($subjectId)
    {
        try {
            $user = auth()->user();

            $classes = DB::table('subjectteacher')
                ->join('subjectclass', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->join('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
                ->where('subjectteacher.staffid', $user->id)
                ->where('subjectteacher.subjectid', $subjectId)
                ->select(
                    'schoolclass.id',
                    'schoolclass.schoolclass',
                    'schoolarm.arm'
                )
                ->distinct()
                ->get();

            return response()->json([
                'success' => true,
                'classes' => $classes
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting classes for subject: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading classes. Please try again.',
                'classes' => []
            ], 500);
        }
    }

    /**
     * Display students who attempted the exam.
     */
    public function showStudents(Request $request, $examId)
    {
        $exam = Exam::where('id', $examId)
                ->where('staffId', auth()->user()->id)
                ->with(['schoolclass', 'termRelation:id,term', 'sessionRelation:id,session', 'subject:id,subject'])
                ->firstOrFail();

        $examTotal = DB::table('questions')
            ->where('exam_id', $examId)
            ->select(
                DB::raw('COUNT(*) as total_questions'),
                DB::raw('SUM(COALESCE(marks, 1.0)) as total_marks')
            )
            ->first();

        $classId = $request->query('class_id');

        $query = DB::table('exam_attempts')
            ->join('studentRegistration', 'exam_attempts.student_id', '=', 'studentRegistration.id')
            ->leftJoin('studentpicture', 'studentRegistration.id', '=', 'studentpicture.studentid')
            ->leftJoin('studentclass', function ($join) {
                $join->on('studentRegistration.id', '=', 'studentclass.studentId')
                     ->where('studentclass.sessionid', function ($q) {
                         $q->select('id')
                           ->from('schoolsession')
                           ->where('status', 'Current')
                           ->orWhereRaw('id = (SELECT MAX(id) FROM schoolsession)')
                           ->limit(1);
                     })
                     ->where('studentclass.termid', function ($q) {
                         $q->select('id')
                           ->from('schoolterm')
                           ->where('status', 'Current')
                           ->orWhereRaw('id = (SELECT MAX(id) FROM schoolterm)')
                           ->limit(1);
                     });
            })
            ->leftJoin('results', function ($join) use ($examId) {
                $join->on('exam_attempts.student_id', '=', 'results.user_id')
                     ->where('results.exam_id', '=', $examId);
            })
            ->where('exam_attempts.exam_id', $examId)
            ->whereIn('exam_attempts.status', ['completed', 'in_progress']);

        if ($classId) {
            $query->where('studentclass.schoolclassid', $classId);
        }

        $query->select(
            'studentRegistration.id',
            'studentRegistration.firstname',
            'studentRegistration.lastname',
            'studentRegistration.admissionNo',
            'studentpicture.picture as picture',
            'results.score',
            'results.total_marks',
            'exam_attempts.status as attempt_status',
            'studentclass.schoolclassid'
        );

        $students = $query->orderBy('studentRegistration.lastname')->paginate(15);

        $correctOptions = DB::table('options')
            ->join('questions', 'options.question_id', '=', 'questions.id')
            ->where('questions.exam_id', $examId)
            ->where('options.is_correct', true)
            ->select('options.id', 'options.question_id', 'options.option_text')
            ->get()
            ->keyBy('question_id');

        $questions = DB::table('questions')
            ->where('exam_id', $examId)
            ->select('id', 'marks', 'type')
            ->get()
            ->keyBy('id');

        foreach ($students as $student) {
            if ($student->attempt_status === 'in_progress') {
                $student->attempted_questions = 0;
                $student->correct_count = 0;
                $student->marks_earned = 0;
                $student->incorrect = 0;
                $student->score = 0;
                continue;
            }

            $answers = Answer::where('exam_id', $examId)
                            ->where('user_id', $student->id)
                            ->with(['option'])
                            ->get();

            $attempted = 0;
            $correctCount = 0;
            $marksEarned = 0;

            foreach ($answers as $answer) {
                $attempted++;

                $question = $questions->get($answer->question_id);
                $questionMarks = (float) ($question->marks ?? 1.0);
                $correctOption = $correctOptions->get($answer->question_id);

                if ($question && $question->type === 'short_answer') {
                    $studentAnswer = trim(strip_tags($answer->short_answer ?? ''));
                    $correctAnswer = trim(strip_tags($correctOption->option_text ?? ''));

                    if (!empty($studentAnswer) && !empty($correctAnswer)) {
                        $normalizedStudent = $this->normalizeTextForComparison($studentAnswer);
                        $normalizedCorrect = $this->normalizeTextForComparison($correctAnswer);

                        if ($normalizedStudent === $normalizedCorrect) {
                            $correctCount++;
                            $marksEarned += $questionMarks;
                        }
                    }
                } else {
                    if ($answer->option && $answer->option->is_correct) {
                        $correctCount++;
                        $marksEarned += $questionMarks;
                    }
                }
            }

            $student->attempted_questions = $attempted;
            $student->correct_count = $correctCount;
            $student->marks_earned = $marksEarned;
            $student->incorrect = $attempted - $correctCount;
            $student->score = $marksEarned;
            $student->total_marks = $examTotal->total_marks ?? 0;

            if ($student->attempt_status === 'completed') {
                DB::table('results')
                    ->updateOrInsert(
                        [
                            'user_id' => $student->id,
                            'exam_id' => $examId
                        ],
                        [
                            'score' => $marksEarned,
                            'total_marks' => $examTotal->total_marks ?? 0,
                            'updated_at' => now()
                        ]
                    );
            }
        }

        $assignedClasses = Schoolclass::whereIn('id',
            Exam::where('title', $exam->title)
                ->where('staffId', $exam->staffId)
                ->where('subject_id', $exam->subject_id)
                ->where('termid', $exam->termid)
                ->where('session', $exam->session)
                ->pluck('schoolclass_id')
        )->get(['id as schoolclassID', 'schoolclass', 'arm']);

        $examTotals = [
            'total_questions' => $examTotal->total_questions ?? 0,
            'total_marks' => $examTotal->total_marks ?? 0
        ];

        $term = Schoolterm::find($exam->termid);
        $session = Schoolsession::find($exam->session);

        // Get all terms and sessions for the modal
        $terms = Schoolterm::all();
        $sessions = Schoolsession::all();

        if ($request->ajax()) {
            return response()->json($students);
        }

        $pagetitle = 'Students who Attempted: ' . $exam->title;

        return view('exam.students', compact(
            'pagetitle',
            'exam',
            'students',
            'assignedClasses',
            'classId',
            'examTotals',
            'term',
            'session',
            'terms',
            'sessions'
        ));
    }

    /**
     * Normalize text for comparison.
     */
    private function normalizeTextForComparison($text)
    {
        $text = trim($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        return $text;
    }

    /**
     * Delete a student's exam attempt.
     */
    public function deleteStudentAttempt($examId, $studentId)
    {
        $exam = Exam::where('id', $examId)->where('staffId', auth()->user()->id)->firstOrFail();

        try {
            Answer::where('exam_id', $examId)
                  ->where('user_id', $studentId)
                  ->delete();

            DB::table('results')
              ->where('exam_id', $examId)
              ->where('user_id', $studentId)
              ->delete();

            $deletedAttempt = DB::table('exam_attempts')
              ->where('exam_id', $examId)
              ->where('student_id', $studentId)
              ->delete();

            $message = $deletedAttempt > 0
                ? 'Student\'s exam attempt deleted successfully. They can now retake the exam.'
                : 'No active attempt found for this student.';

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error("Error deleting student attempt: " . $e->getMessage());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error deleting attempt'], 500);
            }

            return redirect()->back()->with('error', 'An error occurred.');
        }
    }

    /**
     * Show student answers for an exam.
     */
    public function showStudentAnswers($examId, $studentId)
    {
        $exam = Exam::where('id', $examId)
                    ->where('staffId', auth()->user()->id)
                    ->with(['questions.options' => function($query) {
                        $query->select('id', 'question_id', 'option_text', 'is_correct', 'label');
                    }])
                    ->firstOrFail();

        $student = DB::table('studentRegistration')
            ->where('id', $studentId)
            ->select('id', 'firstname', 'lastname', 'admissionNo')
            ->firstOrFail();

        $result = DB::table('results')
            ->where('user_id', $studentId)
            ->where('exam_id', $examId)
            ->first();

        $answers = Answer::where('exam_id', $examId)
                         ->where('user_id', $studentId)
                         ->with('option')
                         ->get()
                         ->keyBy('question_id');

        $questionAnswers = [];
        $totalMarks = 0;
        $marksEarned = 0;
        $attempted = 0;
        $correct = 0;

        foreach ($exam->questions as $question) {
            $answer = $answers->get($question->id);
            $correctOption = $question->options->where('is_correct', true)->first();

            $isCorrect = false;
            $studentAnswerText = '';
            $correctAnswerText = '';

            if ($correctOption) {
                if ($question->type === 'true_false') {
                    $correctAnswerText = ucfirst($correctOption->label);
                } else {
                    $correctAnswerText = $correctOption->option_text;
                }
            }

            if ($answer) {
                $attempted++;

                if ($question->type === 'short_answer') {
                    $studentAnswerText = $answer->short_answer ?? '';

                    if (!empty($studentAnswerText) && $correctOption) {
                        $studentAnswerText = strip_tags($studentAnswerText);
                        $cleanCorrectAnswer = strip_tags($correctOption->option_text);
                        $normalizedStudent = $this->normalizeTextForComparison($studentAnswerText);
                        $normalizedCorrect = $this->normalizeTextForComparison($cleanCorrectAnswer);
                        $isCorrect = $normalizedStudent === $normalizedCorrect;
                    }
                } elseif ($question->type === 'true_false') {
                    if ($answer->option) {
                        $studentAnswerText = ucfirst($answer->option->label);
                        $isCorrect = $answer->option->is_correct;
                    } else {
                        $studentAnswerText = $answer->short_answer ?? '';
                        if (!empty($studentAnswerText) && $correctOption) {
                            $studentAnswerText = ucfirst(strtolower(trim($studentAnswerText)));
                            $isCorrect = $studentAnswerText === ucfirst($correctOption->label);
                        }
                    }
                } else {
                    if ($answer->option) {
                        $studentAnswerText = $answer->option->option_text;
                        $isCorrect = $answer->option->is_correct;
                    } else {
                        $studentAnswerText = $answer->short_answer ?? '';
                        if (!empty($studentAnswerText) && $correctOption) {
                            $cleanStudentAnswer = trim(strip_tags($studentAnswerText));
                            $cleanCorrectAnswer = trim(strip_tags($correctOption->option_text));
                            $isCorrect = strtolower($cleanStudentAnswer) === strtolower($cleanCorrectAnswer);
                        }
                    }
                }
            } else {
                $studentAnswerText = 'Not Attempted';
            }

            if ($studentAnswerText !== 'Not Attempted') {
                $studentAnswerText = strip_tags($studentAnswerText);
                if (empty(trim($studentAnswerText))) {
                    $studentAnswerText = 'Not Attempted';
                }
            }

            if (!empty($correctAnswerText)) {
                $correctAnswerText = strip_tags($correctAnswerText);
            }

            $questionMarks = (float)($question->marks ?? 1.0);
            $totalMarks += $questionMarks;

            if ($isCorrect) {
                $correct++;
                $marksEarned += $questionMarks;
            }

            $questionAnswers[] = (object)[
                'id' => $question->id,
                'question_text' => $question->question_text,
                'image' => $question->image,
                'type' => $question->type,
                'marks' => $questionMarks,
                'student_answer' => $studentAnswerText,
                'correct_answer' => $correctAnswerText,
                'answer_id' => $answer ? $answer->id : null,
                'status' => $answer ? ($isCorrect ? 'Correct' : 'Incorrect') : 'Not Attempted',
                'marks_earned' => $isCorrect ? $questionMarks : 0,
            ];
        }

        $totalQuestions = count($questionAnswers);

        $pagetitle = 'Exam Answers: ' . $student->firstname . ' ' . $student->lastname . ' - ' . $exam->title;

        return view('exam.student-answers', compact(
            'pagetitle',
            'exam',
            'student',
            'questionAnswers',
            'result',
            'totalQuestions',
            'attempted',
            'correct',
            'totalMarks',
            'marksEarned'
        ));
    }



    /**
     * Display exam analytics.
     */
    public function analytics($examId)
    {
        $exam = Exam::where('id', $examId)
                    ->where('staffId', auth()->user()->id)
                    ->with(['schoolclass', 'questions.options'])
                    ->firstOrFail();

        $attempts = ExamAttempt::where('exam_id', $examId)
            ->whereIn('status', ['completed'])
            ->get();

        $totalStudents = $attempts->count();
        $completedCount = $attempts->where('status', 'completed')->count();
        $completionRate = $totalStudents > 0 ? round(($completedCount / $totalStudents) * 100, 1) : 0;

        $results = DB::table('results')
            ->where('exam_id', $examId)
            ->get();

        $avgScore = $results->avg('score') ?? 0;
        $highestScore = $results->max('score') ?? 0;
        $lowestScore = $results->min('score') ?? 0;

        $topPerformers = DB::table('results')
            ->join('studentRegistration', 'results.user_id', '=', 'studentRegistration.id')
            ->where('results.exam_id', $examId)
            ->select('studentRegistration.firstname', 'studentRegistration.lastname', 'results.score', 'results.total_marks')
            ->orderByDesc('results.score')
            ->limit(5)
            ->get();

        $questionStats = [];
        foreach ($exam->questions as $question) {
            $correctCount = DB::table('answers')
                ->join('options', 'answers.option_id', '=', 'options.id')
                ->where('answers.question_id', $question->id)
                ->where('answers.exam_id', $examId)
                ->where('options.is_correct', 1)
                ->count();

            $attemptedCount = DB::table('answers')
                ->where('question_id', $question->id)
                ->where('exam_id', $examId)
                ->count();

            $correctRate = $attemptedCount > 0 ? round(($correctCount / $attemptedCount) * 100, 1) : 0;

            $questionStats[] = [
                'text' => Str::limit($question->question_text, 60),
                'correct_rate' => $correctRate,
                'attempted' => $attemptedCount,
                'marks' => $question->marks ?? 1.0
            ];
        }

        $scoreBins = array_fill(0, 10, 0);
        foreach ($results as $result) {
            if ($result->total_marks > 0) {
                $percentage = ($result->score / $result->total_marks) * 100;
                $bin = min(9, floor($percentage / 10));
                $scoreBins[$bin]++;
            }
        }

        $questionAvgCorrect = collect($questionStats)->pluck('correct_rate')->toArray();

        $pagetitle = 'Analytics: ' . $exam->title;

        return view('exam.analytics', compact(
            'pagetitle', 'exam', 'totalStudents', 'completedCount', 'completionRate',
            'avgScore', 'highestScore', 'lowestScore', 'topPerformers', 'questionStats',
            'scoreBins', 'questionAvgCorrect'
        ));
    }

    // =============================================
    // NEW METHODS FOR EXAM TRANSFER
    // =============================================

    /**
     * Show the term/session selection for exam transfer.
     */
    public function showTransferSubjects(Request $request)
    {
        $pagetitle = "Transfer Exam Scores - Select Term and Session";
        $terms = Schoolterm::orderBy('id')->get();
        $sessions = Schoolsession::orderBy('id', 'desc')->get();

        return view('exam.transfer-subjects', compact('pagetitle', 'terms', 'sessions'));
    }

    /**
     * Get subjects for the selected term and session (AJAX).
     */
    public function getTransferSubjects(Request $request)
    {
        try {
            Log::info('========== GET TRANSFER SUBJECTS ==========');
            Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'termid' => 'required|exists:schoolterm,id',
                'sessionid' => 'required|exists:schoolsession,id',
            ]);

            $user = auth()->user();

            $subjectsQuery = SubjectTeacher::where('subjectteacher.staffid', $user->id)
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->leftJoin('schoolclass_classcategory', 'schoolclass_classcategory.schoolclass_id', '=', 'schoolclass.id')
                ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
                ->where('subjectteacher.sessionid', $validated['sessionid'])
                ->where('subjectteacher.termid', $validated['termid'])
                ->whereNotNull('subjectclass.id')
                ->groupBy(
                    'subjectteacher.id',
                    'users.id',
                    'users.name',
                    'subject.subject',
                    'subject.subject_code',
                    'schoolterm.id',
                    'subjectclass.id',
                    'schoolclass.id',
                    'subjectteacher.sessionid',
                    DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))"),
                    'schoolterm.term',
                    'schoolsession.session'
                )
                ->orderBy('schoolclass.schoolclass')
                ->orderBy('schoolarm.arm');

            $subjectTeachersData = $subjectsQuery->get([
                'subjectteacher.id as id',
                'users.id as userid',
                'users.name as staffname',
                'subject.subject as subject',
                'subject.subject_code as subjectcode',
                'schoolterm.id as termid',
                'subjectclass.id as subjectclassid',
                'schoolclass.id as schoolclassid',
                'subjectteacher.sessionid as sessionid',
                DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as schoolclass"),
                DB::raw("GROUP_CONCAT(DISTINCT classcategories.category ORDER BY classcategories.category SEPARATOR ', ') as classcategories"),
                'schoolterm.term as term',
                'schoolsession.session as session',
            ]);

            Log::info('Found subjects:', ['count' => $subjectTeachersData->count()]);

            $mysubjects = $subjectTeachersData->map(function ($subject) use ($user) {
                $broadsheetExists = Broadsheets::where('staff_id', $user->id)
                    ->where('subjectclass_id', $subject->subjectclassid)
                    ->where('term_id', $subject->termid)
                    ->whereHas('broadsheetRecord', function ($query) use ($subject) {
                        $query->where('session_id', $subject->sessionid);
                    })
                    ->exists();

                return (object) [
                    'id' => $subject->id,
                    'schoolclass' => $subject->schoolclass,
                    'classcategories' => $subject->classcategories ?? 'N/A',
                    'subject' => $subject->subject,
                    'subjectcode' => $subject->subjectcode,
                    'term' => $subject->term,
                    'session' => $subject->session,
                    'userid' => $subject->userid,
                    'subjectclassid' => $subject->subjectclassid,
                    'schoolclassid' => $subject->schoolclassid,
                    'session_id' => $subject->sessionid,
                    'termid' => $subject->termid,
                    'broadsheet_exists' => $broadsheetExists,
                    'staffname' => $subject->staffname ?? 'Unknown'
                ];
            })->filter()->values();

            Log::info('Processed subjects:', ['count' => $mysubjects->count()]);

            return response()->json([
                'success' => true,
                'data' => [
                    'mysubjects' => $mysubjects,
                    'term' => Schoolterm::find($validated['termid'])->term,
                    'session' => Schoolsession::find($validated['sessionid'])->session
                ]
            ]);

        } catch (ValidationException $e) {
            Log::warning('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid input: ' . implode(', ', array_merge(...array_values($e->errors()))),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error getting transfer subjects:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
 * Show the scoresheet for transferring exam scores.
 */
public function showTransferScoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
{
    try {
        Log::info('========== SHOW TRANSFER SCORESHEET ==========');
        Log::info('Step 1: Parameters received', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid'));

        // Validate that the logged-in user matches the staffid
        if (auth()->user()->id != $staffid) {
            Log::error('Step 1.1: User ID mismatch', [
                'logged_in_user' => auth()->user()->id,
                'staffid' => $staffid
            ]);
            abort(403, 'Unauthorized access');
        }

        session([
            'schoolclass_id' => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
        ]);
        Log::info('Step 2: Session data set');

        // Find subjectclass with relationships
        $subjectclass = Subjectclass::with(['subject', 'schoolClass'])->find($subjectclassid);

        if (!$subjectclass) {
            Log::error('Step 3: Subject class not found', ['subjectclassid' => $subjectclassid]);
            abort(404, 'Subject class not found');
        }
        Log::info('Step 3: Subject class found', [
            'subjectclass_id' => $subjectclass->id,
            'subject_id' => $subjectclass->subjectid,
            'schoolclass_id' => $subjectclass->schoolclassid
        ]);

        // Find schoolclass with arm relationship - FIXED: properly join with schoolarm
        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('schoolclass.id', $schoolclassid)
            ->select(
                'schoolclass.*',
                'schoolarm.arm as arm_name'
            )
            ->first();

        if (!$schoolclass) {
            Log::error('Step 4: School class not found', ['schoolclassid' => $schoolclassid]);
            abort(404, 'School class not found');
        }

        // Also load classcategories
        $schoolclass->load('classcategories');

        Log::info('Step 4: School class found', [
            'schoolclass' => $schoolclass->schoolclass,
            'arm' => $schoolclass->arm_name ?? 'No arm'
        ]);

        // Get term and session
        $term = Schoolterm::find($termid);
        $session = Schoolsession::find($sessionid);

        Log::info('Step 5: Term and session found', [
            'term' => $term ? $term->term : 'Not found',
            'session' => $session ? $session->session : 'Not found'
        ]);

        // Get assessments
        $assessments = collect();
        if ($schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            Log::info('Step 6: Class categories', ['category_ids' => $categoryIds]);

            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->orderBy('name')
                ->get();

            Log::info('Step 7: Assessments found', ['count' => $assessments->count()]);
        } else {
            Log::warning('Step 6: No class categories found');
        }

        // Get students who attempted exams
        Log::info('Step 8: Fetching students who attempted exams');

        $students = DB::table('exam_attempts')
            ->join('studentRegistration', 'exam_attempts.student_id', '=', 'studentRegistration.id')
            ->leftJoin('studentpicture', 'studentRegistration.id', '=', 'studentpicture.studentid')
            ->leftJoin('results', function ($join) {
                $join->on('exam_attempts.student_id', '=', 'results.user_id')
                     ->on('exam_attempts.exam_id', '=', 'results.exam_id');
            })
            ->whereIn('exam_attempts.status', ['completed'])
            ->whereExists(function ($query) use ($subjectclass, $schoolclassid, $termid, $sessionid) {
                $query->select(DB::raw(1))
                      ->from('exams')
                      ->whereColumn('exams.id', 'exam_attempts.exam_id')
                      ->where('exams.subject_id', $subjectclass->subjectid)
                      ->where('exams.schoolclass_id', $schoolclassid)
                      ->where('exams.termid', $termid)
                      ->where('exams.session', $sessionid);
            })
            ->select(
                'studentRegistration.id',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.admissionNo',
                'studentpicture.picture',
                'results.score',
                'results.total_marks',
                'exam_attempts.status'
            )
            ->orderBy('studentRegistration.lastname')
            ->get();

        Log::info('Step 9: Students found', ['count' => $students->count()]);

        // Format the arm display
        $armDisplay = '';
        if (!empty($schoolclass->arm_name)) {
            $armDisplay = $schoolclass->arm_name;
        } elseif (!empty($schoolclass->arm)) {
            // Try to get arm name if arm is an ID
            $armRecord = DB::table('schoolarm')->where('id', $schoolclass->arm)->first();
            $armDisplay = $armRecord ? $armRecord->arm : '';
        }

        $pagetitle = "Transfer Exam Scores - " . $subjectclass->subject->subject . " (" . $schoolclass->schoolclass . " " . $armDisplay . ")";

        Log::info('Step 10: Rendering view', ['pagetitle' => $pagetitle]);

        return view('exam.transfer-scoresheet', compact(
            'pagetitle',
            'schoolclassid',
            'subjectclassid',
            'staffid',
            'termid',
            'sessionid',
            'subjectclass',
            'schoolclass',
            'armDisplay',  // Pass the formatted arm display
            'term',
            'session',
            'assessments',
            'students'
        ));

    } catch (\Exception $e) {
        Log::error('ERROR in showTransferScoresheet: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Return a more detailed error page for debugging
        if (config('app.debug')) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ], 500);
        }

        abort(500, 'Error loading scoresheet. Please check the logs.');
    }
}


    /**
     * Get available assessments for the exam's class.
     */
    public function getAssessments($examId)
    {
        try {
            Log::info('========== GET ASSESSMENTS CALLED ==========');
            Log::info('Fetching assessments for exam', ['exam_id' => $examId, 'user_id' => auth()->id()]);

            $exam = Exam::where('id', $examId)
                ->where('staffId', auth()->user()->id)
                ->with(['schoolclass', 'subject'])
                ->firstOrFail();

            $subjectclass = Subjectclass::where('subjectid', $exam->subject_id)
                ->where('schoolclassid', $exam->schoolclass_id)
                ->first();

            if (!$subjectclass) {
                Log::error('No subjectclass found', [
                    'subject_id' => $exam->subject_id,
                    'schoolclass_id' => $exam->schoolclass_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Subject class configuration not found'
                ], 404);
            }

            $schoolclass = Schoolclass::with('classcategories')->find($exam->schoolclass_id);

            if (!$schoolclass || $schoolclass->classcategories->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No class category found for this class'
                ], 404);
            }

            $categoryIds = $schoolclass->classcategories->pluck('id');

            $allAssessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with(['subAssessments' => function($query) {
                    $query->orderBy('name');
                }])
                ->orderBy('name')
                ->get();

            $existingBroadsheets = Broadsheets::where('subjectclass_id', $subjectclass->id)
                ->where('term_id', $exam->termid)
                ->where('staff_id', auth()->user()->id)
                ->with(['assessmentScores.assessment'])
                ->get();

            Log::info('Existing broadsheets found:', [
                'count' => $existingBroadsheets->count(),
                'broadsheet_ids' => $existingBroadsheets->pluck('id')->toArray()
            ]);

            $assessmentIdsWithScores = collect();
            foreach ($existingBroadsheets as $broadsheet) {
                foreach ($broadsheet->assessmentScores as $score) {
                    $assessmentIdsWithScores->push($score->assessment_id);
                }
            }
            $assessmentIdsWithScores = $assessmentIdsWithScores->unique()->values();

            $term = Schoolterm::find($exam->termid);
            $session = Schoolsession::find($exam->session);

            Log::info('========== ASSESSMENT DATA SUMMARY ==========');
            Log::info('Final data:', [
                'subjectclass_id' => $subjectclass->id,
                'all_assessments_count' => $allAssessments->count(),
                'assessments_with_scores_count' => $assessmentIdsWithScores->count(),
                'assessment_ids_with_scores' => $assessmentIdsWithScores->toArray(),
                'existing_broadsheet_ids' => $existingBroadsheets->pluck('id')->toArray(),
                'term' => $term->term ?? 'N/A',
                'session' => $session->session ?? 'N/A'
            ]);

            return response()->json([
                'success' => true,
                'assessments' => $allAssessments,
                'assessment_ids_with_scores' => $assessmentIdsWithScores,
                'subjectclass_id' => $subjectclass->id,
                'existing_broadsheet_ids' => $existingBroadsheets->pluck('id')->toArray(),
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'subject' => $exam->subject->subject ?? 'N/A',
                    'subject_id' => $exam->subject_id,
                    'subject_code' => $exam->subject->subject_code ?? 'N/A',
                    'class' => $schoolclass->schoolclass . ' ' . ($schoolclass->arm ?? ''),
                    'schoolclass_id' => $exam->schoolclass_id,
                    'term' => $term->term ?? 'N/A',
                    'term_id' => $exam->termid,
                    'session' => $session->session ?? 'N/A',
                    'session_id' => $exam->session
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getAssessments: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'exam_id' => $examId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load assessments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assessments for a specific subjectclass.
     */
    public function getAssessmentsForSubject($subjectclassId, $termId, $sessionId)
    {
        try {
            $subjectclass = Subjectclass::with(['schoolClass.classcategories'])->find($subjectclassId);

            if (!$subjectclass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject class not found'
                ], 404);
            }

            $schoolclass = $subjectclass->schoolClass;
            $categoryIds = $schoolclass->classcategories->pluck('id');

            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with(['subAssessments'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'assessments' => $assessments,
                'subjectclass_id' => $subjectclassId
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting assessments for subject:', [
                'message' => $e->getMessage(),
                'subjectclassId' => $subjectclassId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load assessments'
            ], 500);
        }
    }

    /**
     * Update student's exam score to assessment scoresheet.
     */
    public function updateAssessmentScore(Request $request)
    {
        try {
            Log::info('========== ASSESSMENT SCORE TRANSFER ==========');
            Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'student_id' => 'required|exists:studentRegistration,id',
                'assessment_id' => 'required|exists:assessments,id',
                'sub_assessment_id' => 'nullable|exists:sub_assessments,id',
                'score' => 'required|numeric|min:0',
                'max_score' => 'required|numeric|min:0',
                'is_sub' => 'boolean',
                'subjectclass_id' => 'required|exists:subjectclass,id',
                'term_id' => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id'
            ]);

            $isSub = $validated['is_sub'] ?? false;

            if ($validated['score'] > $validated['max_score']) {
                return response()->json([
                    'success' => false,
                    'message' => "Score cannot exceed maximum of {$validated['max_score']}"
                ], 422);
            }

            DB::beginTransaction();

            $student = DB::table('studentRegistration')
                ->where('id', $validated['student_id'])
                ->first();

            if (!$student) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            $assessment = Assessment::with('subAssessments')->find($validated['assessment_id']);

            if (!$assessment) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Assessment not found'
                ], 404);
            }

            $subjectclass = Subjectclass::find($validated['subjectclass_id']);

            if (!$subjectclass) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Subject class not found'
                ], 404);
            }

            Log::info('Using subjectclass:', [
                'id' => $subjectclass->id,
                'subjectid' => $subjectclass->subjectid,
                'schoolclassid' => $subjectclass->schoolclassid
            ]);

            $broadsheetRecord = BroadsheetRecord::firstOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'session_id' => $validated['session_id'],
                    'subject_id' => $subjectclass->subjectid,
                    'schoolclass_id' => $subjectclass->schoolclassid,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            Log::info('Broadsheet record:', ['id' => $broadsheetRecord->id]);

            $broadsheet = Broadsheets::where('subjectclass_id', $subjectclass->id)
                ->where('term_id', $validated['term_id'])
                ->where('staff_id', auth()->user()->id)
                ->where('broadSheet_record_id', $broadsheetRecord->id)
                ->first();

            if (!$broadsheet) {
                Log::info('No broadsheet found, creating new one');

                $broadsheet = Broadsheets::create([
                    'broadSheet_record_id' => $broadsheetRecord->id,
                    'subjectclass_id' => $subjectclass->id,
                    'staff_id' => auth()->user()->id,
                    'term_id' => $validated['term_id'],
                    'total' => 0,
                    'bf' => 0,
                    'cum' => 0,
                    'vettedstatus' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                Log::info('Found existing broadsheet:', ['id' => $broadsheet->id]);
            }

            if ($isSub && !empty($validated['sub_assessment_id'])) {
                BroadsheetSubAssessmentScore::updateOrCreate(
                    [
                        'broadsheet_id' => $broadsheet->id,
                        'sub_assessment_id' => $validated['sub_assessment_id'],
                        'assessment_id' => $validated['assessment_id'],
                    ],
                    ['score' => $validated['score']]
                );

                if ($assessment && $assessment->subAssessments->isNotEmpty()) {
                    $subTotal = BroadsheetSubAssessmentScore::where('broadsheet_id', $broadsheet->id)
                        ->where('assessment_id', $validated['assessment_id'])
                        ->sum('score');

                    BroadsheetAssessmentScore::updateOrCreate(
                        [
                            'broadsheet_id' => $broadsheet->id,
                            'assessment_id' => $validated['assessment_id'],
                        ],
                        ['score' => $subTotal]
                    );
                }
            } else {
                BroadsheetAssessmentScore::updateOrCreate(
                    [
                        'broadsheet_id' => $broadsheet->id,
                        'assessment_id' => $validated['assessment_id'],
                    ],
                    ['score' => $validated['score']]
                );
            }

            $schoolclass = Schoolclass::with('classcategories')->find($subjectclass->schoolclassid);

            $allAssessments = collect();
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $allAssessments = Assessment::whereIn('classcategory_id', $categoryIds)
                    ->with('subAssessments')
                    ->get();
            }

            $broadsheet->load(['assessmentScores', 'subAssessmentScores']);

            $totalRaw = 0;
            foreach ($allAssessments as $assess) {
                $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assess->id)->first();
                $totalRaw += $scoreObj ? $scoreObj->score : 0;
            }

            $previousTerm = Broadsheets::where('broadsheet_records.student_id', $broadsheet->student_id)
                ->where('broadsheet_records.subject_id', $subjectclass->subjectid)
                ->where('broadsheets.term_id', $validated['term_id'] - 1)
                ->where('broadsheet_records.session_id', $validated['session_id'])
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->value('broadsheets.cum');

            $newBf = $previousTerm ? round($previousTerm, 2) : 0;
            $newCum = $validated['term_id'] == 1 ? round($totalRaw, 2) : round(($totalRaw + $newBf) / 2, 2);

            $isSenior = $schoolclass->classcategories->first()->is_senior ?? false;
            $newGrade = $this->calculateGrade($newCum, $isSenior);
            $newRemark = $this->getRemark($newGrade);

            $broadsheet->total = $totalRaw;
            $broadsheet->bf = $newBf;
            $broadsheet->cum = $newCum;
            $broadsheet->grade = $newGrade;
            $broadsheet->remark = $newRemark;
            $broadsheet->save();

            $this->updateClassMetrics($subjectclass->id, auth()->user()->id, $validated['term_id'], $validated['session_id']);
            $this->updateSubjectPositions($subjectclass->id, auth()->user()->id, $validated['term_id'], $validated['session_id']);

            DB::commit();

            Log::info('TRANSFER COMPLETED:', [
                'student' => $student->firstname . ' ' . $student->lastname,
                'admission' => $student->admissionNo,
                'broadsheet_id' => $broadsheet->id,
                'subjectclass_id' => $subjectclass->id,
                'assessment' => $assessment->name,
                'score_saved' => $validated['score']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Score successfully transferred to assessment sheet',
                'data' => [
                    'broadsheet_id' => $broadsheet->id,
                    'subjectclass_id' => $subjectclass->id,
                    'total' => $totalRaw,
                    'cum' => $newCum,
                    'grade' => $newGrade,
                    'student_name' => $student->firstname . ' ' . $student->lastname,
                    'admission_no' => $student->admissionNo
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERROR IN TRANSFER:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update score: ' . $e->getMessage()
            ], 500);
        }
    }

    // =============================================
    // HELPER METHODS
    // =============================================

    /**
     * Calculate grade based on score and level.
     */
    private function calculateGrade($score, $isSenior = false)
    {
        if (!$isSenior) {
            if ($score >= 70) return 'A';
            if ($score >= 60) return 'B';
            if ($score >= 50) return 'C';
            if ($score >= 45) return 'D';
            if ($score >= 40) return 'E';
            return 'F';
        } else {
            if ($score >= 75) return 'A1';
            if ($score >= 70) return 'B2';
            if ($score >= 65) return 'B3';
            if ($score >= 60) return 'C4';
            if ($score >= 55) return 'C5';
            if ($score >= 50) return 'C6';
            if ($score >= 45) return 'D7';
            if ($score >= 40) return 'E8';
            return 'F9';
        }
    }

    /**
     * Get remark based on grade.
     */
    private function getRemark($grade)
    {
        $remarks = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Credit',
            'E' => 'Pass',
            'F' => 'Fail',
            'A1' => 'Excellent',
            'B2' => 'Very Good',
            'B3' => 'Good',
            'C4' => 'Credit',
            'C5' => 'Credit',
            'C6' => 'Credit',
            'D7' => 'Pass',
            'E8' => 'Pass',
            'F9' => 'Fail',
        ];

        return $remarks[$grade] ?? 'Unknown';
    }

    /**
     * Update class metrics.
     */
    private function updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        try {
            $subjectClass = DB::table('subjectclass')
                ->where('id', $subjectclassid)
                ->first(['subjectteacherid']);

            if (!$subjectClass) {
                Log::warning('Subjectclass not found', ['subjectclass_id' => $subjectclassid]);
                return;
            }

            $subjectTeacher = DB::table('subjectteacher')
                ->where('id', $subjectClass->subjectteacherid)
                ->first(['subjectid']);

            if (!$subjectTeacher) {
                Log::warning('Subjectteacher not found', ['subjectteacherid' => $subjectClass->subjectteacherid]);
                return;
            }

            $subjectId = $subjectTeacher->subjectid;

            $metrics = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassid)
                ->where('broadsheets.staff_id', $staffid)
                ->where('broadsheets.term_id', $termid)
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->where('broadsheet_records.session_id', $sessionid)
                ->where('broadsheet_records.subject_id', $subjectId)
                ->select([
                    DB::raw('MIN(broadsheets.cum) as class_min'),
                    DB::raw('MAX(broadsheets.cum) as class_max'),
                    DB::raw('SUM(broadsheets.cum) as cum_sum'),
                    DB::raw('COUNT(broadsheets.id) as student_count')
                ])
                ->first();

            $classMin = $metrics->class_min ?? 0;
            $classMax = $metrics->class_max ?? 0;
            $classAvg = $metrics->student_count > 0 ? round($metrics->cum_sum / $metrics->student_count, 1) : 0;

            Broadsheets::where('subjectclass_id', $subjectclassid)
                ->where('staff_id', $staffid)
                ->where('term_id', $termid)
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->where('broadsheet_records.session_id', $sessionid)
                ->where('broadsheet_records.subject_id', $subjectId)
                ->update([
                    'cmin' => $classMin,
                    'cmax' => $classMax,
                    'avg' => $classAvg,
                ]);

            Log::info('Class metrics updated:', [
                'subjectclass_id' => $subjectclassid,
                'cmin' => $classMin,
                'cmax' => $classMax,
                'avg' => $classAvg
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating class metrics:', [
                'message' => $e->getMessage(),
                'subjectclass_id' => $subjectclassid
            ]);
        }
    }

    /**
     * Update subject positions.
     */
    private function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        try {
            $broadsheets = Broadsheets::where('subjectclass_id', $subjectclass_id)
                ->where('staff_id', $staff_id)
                ->where('term_id', $term_id)
                ->where('broadsheet_records.session_id', $session_id)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->orderByDesc('broadsheets.cum')
                ->orderBy('broadsheets.id')
                ->get();

            if ($broadsheets->isEmpty()) {
                return;
            }

            $rank = 0;
            $lastCum = null;
            $lastPosition = 0;

            foreach ($broadsheets as $broadsheet) {
                $rank++;
                if ($lastCum !== null && $broadsheet->cum == $lastCum) {
                    // Tied rank - keep same position
                } else {
                    $lastPosition = $rank;
                    $lastCum = $broadsheet->cum;
                }

                if ($broadsheet->subject_position_class != $lastPosition) {
                    $broadsheet->subject_position_class = $lastPosition;
                    $broadsheet->save();
                }
            }

            Log::info('Subject positions updated for subjectclass_id: ' . $subjectclass_id);
        } catch (\Exception $e) {
            Log::error('Error updating subject positions:', [
                'message' => $e->getMessage(),
                'subjectclass_id' => $subjectclass_id
            ]);
        }
    }





    /**
 * Generate question paper PDF with student's answers.
 */
public function generateQuestionPaperPdf(Exam $exam, $studentId)
{
    try {
        Log::info('========== GENERATING QUESTION PAPER PDF ==========');
        Log::info('Step 1: Parameters received', [
            'exam_id' => $exam->id,
            'exam_title' => $exam->title,
            'student_id' => $studentId,
            'user_id' => auth()->user()->id
        ]);

        // Verify the exam belongs to the logged-in teacher
        if ($exam->staffId != auth()->user()->id) {
            Log::error('Step 2: Unauthorized access', [
                'exam_staff_id' => $exam->staffId,
                'user_id' => auth()->user()->id
            ]);
            abort(403, 'Unauthorized access');
        }
        Log::info('Step 2: Authorization passed');

        // Get student details
        $student = DB::table('studentRegistration')
            ->leftJoin('studentpicture', 'studentRegistration.id', '=', 'studentpicture.studentid')
            ->where('studentRegistration.id', $studentId)
            ->select(
                'studentRegistration.id',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.admissionNo',
                'studentpicture.picture as picture'
            )
            ->first();

        if (!$student) {
            Log::error('Step 3: Student not found', ['student_id' => $studentId]);
            abort(404, 'Student not found');
        }
        Log::info('Step 3: Student found', [
            'student_name' => $student->firstname . ' ' . $student->lastname,
            'admission' => $student->admissionNo
        ]);

        // Set student picture path
        if ($student->picture && Storage::disk('public')->exists($student->picture)) {
            $student->picture_path = asset('storage/' . $student->picture);
            Log::info('Step 4: Student picture found', ['path' => $student->picture_path]);
        } else {
            $student->picture_path = asset('storage/student_avatars/unnamed.jpg');
            Log::info('Step 4: Using default student picture');
        }

        // Get exam result
        $result = DB::table('results')
            ->where('user_id', $studentId)
            ->where('exam_id', $exam->id)
            ->first();
        Log::info('Step 5: Exam result', ['result' => $result ? 'found' : 'not found']);

        // Get school information
        $school = SchoolInformation::where('is_active', true)->first();
        Log::info('Step 6: School information', ['school' => $school ? $school->school_name : 'not found']);

        // Get exam attempt details
        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->whereIn('status', ['completed', 'in_progress'])
            ->orderBy('created_at', 'desc')
            ->first();
        Log::info('Step 7: Exam attempt', ['attempt' => $attempt ? 'found' : 'not found']);

        // Get all questions with options
        $questions = Question::where('exam_id', $exam->id)
            ->with(['options' => function($query) {
                $query->orderBy('label');
            }])
            ->orderBy('order')
            ->get();
        Log::info('Step 8: Questions loaded', ['count' => $questions->count()]);

        // Get all answers for this student
        $answers = Answer::where('exam_id', $exam->id)
            ->where('user_id', $studentId)
            ->get()
            ->keyBy('question_id');
        Log::info('Step 9: Answers loaded', ['count' => $answers->count()]);

        // Process each question to add student's answer and correctness
        $totalQuestions = $questions->count();
        $attemptedQuestions = 0;
        $correctAnswers = 0;

        foreach ($questions as $question) {
            $studentAnswer = $answers->get($question->id);

            // Get the correct option for this question
            $correctOption = $question->options->where('is_correct', true)->first();

            // Set student's answer text and strip HTML tags
            if ($studentAnswer) {
                $attemptedQuestions++;

                if ($question->type === 'short_answer') {
                    // Strip HTML tags from short answer
                    $question->student_answer = strip_tags($studentAnswer->short_answer ?? 'Not answered');

                    // Get correct answer text with HTML stripped
                    $correctText = $correctOption ? strip_tags($correctOption->option_text) : '';
                    $studentText = strip_tags($studentAnswer->short_answer ?? '');

                    // Check if correct
                    $question->is_correct = $this->checkShortAnswerCorrectness($studentText, $correctText);
                    if ($question->is_correct) {
                        $correctAnswers++;
                    }

                    // Store stripped correct answer for display
                    $question->correct_answer_text = $correctText;
                } else {
                    // For MCQ and True/False
                    $selectedOption = $question->options->where('id', $studentAnswer->option_id)->first();
                    $question->student_answer = $selectedOption ? strip_tags($selectedOption->option_text) : 'Not answered';
                    $question->selected_option_id = $studentAnswer->option_id;
                    $question->is_correct = $selectedOption ? $selectedOption->is_correct : false;

                    if ($question->is_correct) {
                        $correctAnswers++;
                    }
                }

                $question->student_option_id = $studentAnswer->option_id;
            } else {
                $question->student_answer = 'Not Attempted';
                $question->is_correct = false;
                $question->student_option_id = null;
            }

            // Store correct option text for reference (strip HTML tags)
            if ($correctOption) {
                $question->correct_option_id = $correctOption->id;
                $question->correct_answer_text = strip_tags($correctOption->option_text);
                if ($question->type === 'true_false') {
                    $question->correct_answer_text = ucfirst($correctOption->label);
                }
            }
        }

        // Calculate statistics
        $score = $result->score ?? 0;
        $percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : 0;

        Log::info('Step 10: Statistics calculated', [
            'totalQuestions' => $totalQuestions,
            'attemptedQuestions' => $attemptedQuestions,
            'correctAnswers' => $correctAnswers,
            'score' => $score,
            'percentage' => $percentage
        ]);

        // Prepare data for the view
        $data = compact(
            'exam',
            'student',
            'result',
            'school',
            'attempt',
            'questions',
            'totalQuestions',
            'attemptedQuestions',
            'correctAnswers',
            'score',
            'percentage'
        );

        Log::info('Step 11: Rendering PDF view');

        // Create safe filename
        $safeFirstName = str_replace(['/', '\\', ' '], '-', $student->firstname);
        $safeLastName = str_replace(['/', '\\', ' '], '-', $student->lastname);
        $safeAdmission = str_replace('/', '-', $student->admissionNo);
        $safeExamTitle = str_replace(['/', '\\', ' '], '-', $exam->title);
        $filename = "{$safeLastName}_{$safeFirstName}_{$safeAdmission}_{$safeExamTitle}.pdf";

        // Load PDF view
        $pdf = Pdf::loadView('exam.question-paper-pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'defaultFont' => 'sans-serif'
        ]);

        Log::info('Step 12: PDF generated successfully', ['filename' => $filename]);

        return $pdf->download($filename);

    } catch (\Exception $e) {
        Log::error('ERROR in generateQuestionPaperPdf: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if (request()->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }

        return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
    }
}

/**
 * Helper method to check if short answer is correct
 */
private function checkShortAnswerCorrectness($studentAnswer, $correctAnswer)
{
    if (empty($studentAnswer) || empty($correctAnswer)) {
        return false;
    }

    // Clean and normalize both answers
    $studentAnswer = trim($studentAnswer);
    $correctAnswer = trim($correctAnswer);

    // Remove extra spaces and convert to lowercase
    $studentAnswer = preg_replace('/\s+/', ' ', strtolower($studentAnswer));
    $correctAnswer = preg_replace('/\s+/', ' ', strtolower($correctAnswer));

    // Remove punctuation for comparison
    $studentAnswer = preg_replace('/[^\p{L}\p{N}\s]/u', '', $studentAnswer);
    $correctAnswer = preg_replace('/[^\p{L}\p{N}\s]/u', '', $correctAnswer);

    return $studentAnswer === $correctAnswer;
}
}
