<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View question', ['only' => ['index', 'show', 'showDetails', 'edit']]);
        $this->middleware('permission:Create question', ['only' => ['create', 'store', 'import', 'duplicate']]);
        $this->middleware('permission:Update question', ['only' => ['edit', 'update', 'bulkUpdate', 'reorder']]);
        $this->middleware('permission:Delete question', ['only' => ['destroy', 'bulkDestroy']]);
    }

    /**
     * Display a listing of ALL questions across all exams
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get all questions for this teacher across all exams
        $query = Question::with(['exam.schoolclass.armRelation', 'exam.subject', 'options'])
            ->whereHas('exam', function($q) use ($user) {
                $q->where('staffId', $user->id);
            })
            ->orderBy('exam_id')
            ->orderBy('order');

        // Apply filters
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('exam', function($q) use ($request) {
                $q->where('schoolclass_id', $request->class_id);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('question_text', 'like', "%{$search}%")
                  ->orWhereHas('options', function($q2) use ($search) {
                      $q2->where('option_text', 'like', "%{$search}%");
                  });
            });
        }

        // Date filters
        if ($request->filled('date_from')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = Carbon::parse($request->date_to)->endOfDay();
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Time filters
        if ($request->filled('time_from')) {
            $timeFrom = Carbon::parse($request->time_from)->format('H:i:s');
            $query->whereTime('created_at', '>=', $timeFrom);
        }

        if ($request->filled('time_to')) {
            $timeTo = Carbon::parse($request->time_to)->format('H:i:s');
            $query->whereTime('created_at', '<=', $timeTo);
        }

        $questions = $query->paginate(20);

        // Get exams and classes for filters
        $exams = Exam::where('staffId', $user->id)->get(['id', 'title']);
        $classes = Schoolclass::all();

        $pagetitle = 'All Questions Management';

        // Check if it's an AJAX request for pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('question.partials.questions_table', compact('questions'))->render(),
                'pagination' => view('question.partials.pagination', compact('questions'))->render(),
                'count' => $questions->total(),
                'mcq_count' => $questions->where('type', 'mcq')->count(),
                'tf_count' => $questions->where('type', 'true_false')->count(),
                'short_count' => $questions->where('type', 'short_answer')->count()
            ]);
        }

        return view('question.questionindex', compact('pagetitle', 'questions', 'exams', 'classes'));
    }

    /**
     * Display questions for a specific exam
     */
    public function show($examId)
    {
        $user = Auth::user();

        // Check if user owns this exam
        $exam = Exam::with(['schoolclass.armRelation', 'subject'])
                    ->where('id', $examId)
                    ->where('staffId', $user->id)
                    ->firstOrFail();

        // Get questions for this exam with options
        $questions = Question::with('options')
                             ->where('exam_id', $examId)
                             ->orderBy('order')
                             ->get();

        $pagetitle = 'Questions for: ' . $exam->title;

        return view('question.show', compact('pagetitle', 'exam', 'questions'));
    }

    /**
     * Store a newly created question
     */
    public function store(Request $request)
    {
        \Log::info('=== STORING NEW QUESTION ===');
        \Log::info('Request type: ' . $request->type);
        \Log::info('Correct option: ' . $request->correct_option);
        \Log::info('Options array: ' . json_encode($request->options));
        \Log::info('All request data: ' . json_encode($request->all()));

        // Handle multiple exam_ids or single exam_id
        if ($request->has('exam_ids') && is_array($request->exam_ids)) {
            // Multiple exams selected
            $examIds = $request->exam_ids;
        } else {
            // Single exam selected
            $examIds = [$request->exam_id];
        }

        // Validate exam IDs
        foreach ($examIds as $examId) {
            if (!Exam::where('id', $examId)->where('staffId', Auth::id())->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Invalid exam selected']
                ], 422);
            }
        }

        $rules = [
            'question_text' => 'required|string',
            'type' => 'required|in:mcq,true_false,short_answer',
            'image' => 'nullable|image|max:2048',
            'correct_option' => 'required',
            'marks' => 'nullable|numeric|min:0.1',
            'is_reusable' => 'nullable|boolean',
        ];

        // Add type-specific validation
        if ($request->type === 'mcq') {
            $rules['options'] = 'required|array';
        } elseif ($request->type === 'short_answer') {
            $rules['options.answer.option_text'] = 'required|string';
        }

        \Log::info('Validation rules:', $rules);

        $validated = $request->validate($rules);

        \Log::info('Validated data:', $validated);

        $createdQuestions = [];
        $imagePath = "";

        // Handle image upload (only once for all exams)
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('question_images', 'public');
        }

        // Create question for each selected exam
        foreach ($examIds as $examId) {
            // Get next order number for this exam
            $order = Question::where('exam_id', $examId)->max('order') + 1;

            // Create question
            $question = Question::create([
                'exam_id' => $examId,
                'question_text' => $validated['question_text'],
                'type' => $validated['type'],
                'image' => $imagePath,
                'marks' => $validated['marks'] ?? 1,
                'order' => $order,
                'is_reusable' => $validated['is_reusable'] ?? false,
            ]);

            $createdQuestions[] = $question;

            // Create options based on type
            if ($validated['type'] === 'mcq') {
                \Log::info('Creating MCQ options');
                $filledOptions = 0;

                // Process MCQ options
                foreach ($request->options as $key => $option) {
                    if (isset($option['option_text']) && !empty(trim($option['option_text']))) {
                        $question->options()->create([
                            'option_text' => $option['option_text'],
                            'is_correct' => $validated['correct_option'] === $key,
                            'label' => $key, // 'a', 'b', 'c', 'd', 'e'
                        ]);
                        $filledOptions++;
                    }
                }

                \Log::info('Question created with options: ' . json_encode([
                    'question_id' => $question->id,
                    'type' => $question->type,
                    'options_count' => $filledOptions
                ]));

                if ($filledOptions < 2) {
                    // Clean up on failure
                    foreach ($createdQuestions as $q) {
                        $q->options()->delete();
                        $q->delete();
                    }
                    if ($imagePath) Storage::disk('public')->delete($imagePath);

                    return response()->json([
                        'success' => false,
                        'errors' => ['At least 2 options must be filled for MCQ']
                    ]);
                }
            } elseif ($validated['type'] === 'true_false') {
                \Log::info('Creating True/False options');
                // Use full labels now that database is fixed
                $question->options()->create([
                    'option_text' => 'True',
                    'is_correct' => $validated['correct_option'] === 'true',
                    'label' => 'true',
                ]);
                $question->options()->create([
                    'option_text' => 'False',
                    'is_correct' => $validated['correct_option'] === 'false',
                    'label' => 'false',
                ]);
            } elseif ($validated['type'] === 'short_answer') {
                \Log::info('Creating Short Answer option');
                // Extract the answer text from the nested array
                $answerText = $request->input('options.answer.option_text', '');

                \Log::info('Short answer data: ' . json_encode([
                    'option_text' => $answerText
                ]));

                $question->options()->create([
                    'option_text' => $answerText,
                    'is_correct' => true,
                    'label' => 'answer',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($examIds) > 1 ?
                'Question added to ' . count($examIds) . ' exams successfully' :
                'Question added successfully',
            'question_count' => count($createdQuestions),
            'exam_ids' => $examIds
        ]);
    }

    public function showDetails(Question $question)
    {
        $question->load('exam.schoolclass.armRelation', 'exam.subject', 'options');

        return response()->json([
            'exam_id' => $question->exam_id,
            'exam_title' => $question->exam->title,
            'question_text' => $question->question_text,
            'type' => $question->type,
            'image' => $question->image,
            'marks' => $question->marks,
            'is_reusable' => $question->is_reusable,
            'created_at' => $question->created_at,
            'options' => $question->options->map(function($option) {
                return [
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                    'label' => $option->label ?? '',
                ];
            })
        ]);
    }

    public function edit(Question $question)
    {
        $question->load('options');

        return response()->json([
            'success' => true,
            'exam_id' => $question->exam_id,
            'question' => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'type' => $question->type,
                'image' => $question->image,
                'marks' => $question->marks,
                'is_reusable' => $question->is_reusable,
            ],
            'options' => $question->options->map(function($option) {
                return [
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                    'label' => $option->label ?? '',
                ];
            })
        ]);
    }

    public function update(Request $request, Question $question)
    {
        \Log::info('=== UPDATING QUESTION ===');
        \Log::info('Question ID: ' . $question->id);
        \Log::info('Request data: ' . json_encode($request->all()));

        // Get the original question type - don't allow type change on update
        $type = $question->type;

        $rules = [
            'question_text' => 'required|string',
            'exam_id' => 'required|exists:exams,id',
            'marks' => 'nullable|numeric|min:0.1',
            'is_reusable' => 'nullable|boolean',
        ];

        // Add correct_option validation based on type
        if ($type === 'mcq') {
            $rules['correct_option'] = 'required|in:a,b,c,d,e';
            $rules['options'] = 'required|array';
            $rules['options.*.option_text'] = 'nullable|string';
        } elseif ($type === 'true_false') {
            $rules['correct_option'] = 'required|in:true,false';
        } elseif ($type === 'short_answer') {
            $rules['correct_option'] = 'required|in:answer';
            $rules['options.answer.option_text'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);

        // Extra check for MCQ: at least 2 non-empty options
        if ($type === 'mcq') {
            $nonEmptyOptions = 0;
            if (isset($request->options)) {
                foreach ($request->options as $option) {
                    if (!empty(trim($option['option_text'] ?? ''))) {
                        $nonEmptyOptions++;
                    }
                }
            }
            if ($nonEmptyOptions < 2) {
                $validator->errors()->add('options', 'At least 2 options must be filled for MCQ');
            }
        }

        if ($validator->fails()) {
            \Log::error('Validation failed: ' . json_encode($validator->errors()->all()));
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $validated = $validator->validated();

        // Update question (don't change type)
        $question->update([
            'question_text' => $validated['question_text'],
            'exam_id' => $validated['exam_id'],
            'marks' => $validated['marks'] ?? $question->marks,
            'is_reusable' => $validated['is_reusable'] ?? $question->is_reusable,
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($question->image) {
                Storage::disk('public')->delete($question->image);
            }
            // Upload new image
            $imagePath = $request->file('image')->store('question_images', 'public');
            $question->update(['image' => $imagePath]);
        }

        // Handle image removal
        if ($request->has('remove_image') && $request->remove_image == '1') {
            if ($question->image) {
                Storage::disk('public')->delete($question->image);
            }
            $question->update(['image' => null]);
        }

        // Delete all existing options and recreate them
        $question->options()->delete();

        // Create new options based on type
        if ($type === 'mcq') {
            \Log::info('Updating MCQ options');
            \Log::info('Correct option from request: ' . $request->correct_option);

            foreach ($request->options as $key => $option) {
                if (!empty(trim($option['option_text'] ?? ''))) {
                    $isCorrect = ($request->correct_option === $key);
                    \Log::info("Option {$key}: is_correct=" . ($isCorrect ? 'true' : 'false'));

                    $question->options()->create([
                        'option_text' => $option['option_text'],
                        'is_correct' => $isCorrect,
                        'label' => $key, // 'a', 'b', 'c', 'd', 'e'
                    ]);
                }
            }
        } elseif ($type === 'true_false') {
            \Log::info('Updating True/False options. Correct option: ' . $request->correct_option);
            $question->options()->create([
                'option_text' => 'True',
                'is_correct' => $request->correct_option === 'true',
                'label' => 'true',
            ]);
            $question->options()->create([
                'option_text' => 'False',
                'is_correct' => $request->correct_option === 'false',
                'label' => 'false',
            ]);
        } elseif ($type === 'short_answer') {
            \Log::info('Updating Short Answer option');
            $question->options()->create([
                'option_text' => $request->input('options.answer.option_text'),
                'is_correct' => true,
                'label' => 'answer',
            ]);
        }

        \Log::info('Question updated successfully');

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully'
        ]);
    }

    public function destroy(Question $question)
    {
        if ($question->image) {
            Storage::disk('public')->delete($question->image);
        }
        $question->options()->delete();
        $question->delete();
        return response()->json(['success' => true]);
    }

    public function getExamsForSelection(Request $request)
    {
        $user = Auth::user();

        \Log::info('====== DEBUG: Getting exams for user: ' . $user->id . ' ======');

        // Get exams with ALL relationships to debug
        $exams = Exam::with([
            'schoolclass.armRelation',
            'subject:id,subject',
            'questions'
        ])
        ->where('staffId', $user->id)
        ->orderBy('title')
        ->get();

        \Log::info('Total exams found: ' . $exams->count());

        $formattedExams = $exams->map(function($exam) {
            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject ? $exam->subject->subject : 'No Subject',
                'subject_id' => $exam->subject_id,
                'class_name' => $exam->schoolclass ?
                    $exam->schoolclass->schoolclass .
                    ($exam->schoolclass->armRelation ? ' (' . $exam->schoolclass->armRelation->arm . ')' : '') :
                    'No Class',
                'question_count' => $exam->questions->count(),
                'marks' => $exam->questions->sum('marks')
            ];
        });

        \Log::info('====== DEBUG: Returning exams ======');

        return response()->json([
            'success' => true,
            'exams' => $formattedExams,
            'debug_info' => [
                'user_id' => $user->id,
                'exam_count' => $exams->count()
            ]
        ]);
    }

    /**
     * Bulk operations (delete, change type, etc.)
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id',
            'action' => 'required|in:delete,change_exam,mark_reusable',
            'data' => 'nullable|array'
        ]);

        $ids = $request->question_ids;

        switch ($request->action) {
            case 'delete':
                Question::whereIn('id', $ids)->delete();
                $message = 'Questions deleted successfully';
                break;

            case 'mark_reusable':
                Question::whereIn('id', $ids)->update(['is_reusable' => true]);
                $message = 'Questions marked as reusable';
                break;

            case 'change_exam':
                $request->validate(['data.exam_id' => 'required|exists:exams,id']);

                // Get the target exam
                $targetExamId = $request->data['exam_id'];

                foreach ($ids as $questionId) {
                    $question = Question::find($questionId);

                    // Get next order number for target exam
                    $newOrder = Question::where('exam_id', $targetExamId)->max('order') + 1;

                    // Update question
                    $question->update([
                        'exam_id' => $targetExamId,
                        'order' => $newOrder
                    ]);
                }

                $message = 'Questions moved to new exam';
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Get reusable questions for selection
     */
    public function getReusableQuestions(Request $request)
    {
        $user = Auth::user();

        $questions = Question::with(['exam.schoolclass', 'options'])
            ->whereHas('exam', function($q) use ($user) {
                $q->where('staffId', $user->id);
            })
            ->where('is_reusable', true)
            ->get()
            ->map(function($question) {
                return [
                    'id' => $question->id,
                    'text' => strip_tags($question->question_text),
                    'exam_title' => $question->exam->title,
                    'class' => $question->exam->schoolclass ?
                        $question->exam->schoolclass->schoolclass .
                        ($question->exam->schoolclass->armRelation ? ' (' . $question->exam->schoolclass->armRelation->arm . ')' : '') :
                        'No Class',
                    'type' => $question->type,
                    'marks' => $question->marks,
                    'options_count' => $question->options->count()
                ];
            });

        return response()->json(['questions' => $questions]);
    }
}
