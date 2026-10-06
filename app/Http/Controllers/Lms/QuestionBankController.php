<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\LmsQuestionBank;
use App\Models\LmsQuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A shared, reusable pool of questions teachers can build once and import into
 * any quiz (selected, or N random from a filter).
 */
class QuestionBankController extends Controller
{
    use InteractsWithLms;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage courses|Grade coursework');
    }

    public function index(Request $request)
    {
        $rows = LmsQuestionBank::query()
            ->when($request->filled('subject'), fn ($q) => $q->where('subject_id', $request->subject))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('tag'), fn ($q) => $q->where('tag', 'like', "%{$request->tag}%"))
            ->when($request->filled('q'), fn ($q) => $q->where('question', 'like', "%{$request->q}%"))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('lms.bank.index', [
            'pagetitle' => 'Question Bank',
            'rows'      => $rows,
            'subjects'  => Schema::hasTable('subject') ? DB::table('subject')->orderBy('subject')->get(['id', 'subject']) : collect(),
            'total'     => LmsQuestionBank::count(),
        ]);
    }

    /** AJAX list for the quiz "import from bank" picker. */
    public function candidates(Request $request)
    {
        $rows = LmsQuestionBank::query()
            ->when($request->filled('subject'), fn ($q) => $q->where('subject_id', $request->subject))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('tag'), fn ($q) => $q->where('tag', 'like', "%{$request->tag}%"))
            ->when($request->filled('q'), fn ($q) => $q->where('question', 'like', "%{$request->q}%"))
            ->orderByDesc('id')->limit(300)->get(['id', 'question', 'type', 'points', 'tag']);
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        LmsQuestionBank::create($this->rules($request) + ['created_by' => Auth::id()]);
        return back()->with('success', 'Question added to the bank.');
    }

    public function update(Request $request, LmsQuestionBank $question)
    {
        $question->update($this->rules($request));
        return back()->with('success', 'Question updated.');
    }

    public function destroy(LmsQuestionBank $question)
    {
        $question->delete();
        return back()->with('success', 'Question removed from the bank.');
    }

    protected function rules(Request $request): array
    {
        $data = $request->validate([
            'subject_id'  => 'nullable|integer',
            'tag'         => 'nullable|string|max:80',
            'question'    => 'required|string',
            'type'        => 'required|in:single,multiple,boolean,short_answer,fill_blank,essay',
            'points'      => 'required|integer|min:1|max:100',
            'options'     => 'nullable|array',
            'options.*'   => 'nullable|string|max:500',
            'correct'     => 'nullable|array',
            'correct.*'   => 'integer|min:0',
            'accepted'    => 'nullable|array',
            'accepted.*'  => 'nullable|string|max:500',
            'explanation' => 'nullable|string|max:5000',
            'image'       => 'nullable|image|max:4096',
        ]);

        $type = $data['type'];
        $out = [
            'subject_id'  => $data['subject_id'] ?? null,
            'tag'         => $data['tag'] ?? null,
            'question'    => $data['question'],
            'type'        => $type,
            'points'      => $data['points'],
            'explanation' => $data['explanation'] ?? null,
            'options'     => null,
            'correct'     => null,
            'accepted_answers' => null,
        ];

        if (in_array($type, ['single', 'multiple', 'boolean'], true)) {
            $out['options'] = $type === 'boolean' ? ['True', 'False'] : array_values(array_filter(
                array_map(fn ($o) => trim((string) $o), $data['options'] ?? []), fn ($o) => $o !== ''));
            $max = count($out['options']);
            if ($max < 2) abort(422, 'Add at least two options.');
            $correct = array_values(array_filter(array_unique(array_map('intval', $data['correct'] ?? [])),
                fn ($i) => $i >= 0 && $i < $max));
            if ($type !== 'multiple') $correct = array_slice($correct, 0, 1);
            if (empty($correct)) abort(422, 'Select at least one correct answer.');
            $out['correct'] = $correct;
        } elseif (in_array($type, ['short_answer', 'fill_blank'], true)) {
            $accepted = array_values(array_filter(array_map(fn ($a) => trim((string) $a), $data['accepted'] ?? []),
                fn ($a) => $a !== ''));
            if (empty($accepted)) abort(422, 'Add at least one accepted answer.');
            $out['accepted_answers'] = $accepted;
        }

        if ($request->hasFile('image')) {
            $out['image_path'] = $request->file('image')->store('lms/questions', 'public');
        }
        return $out;
    }
}
