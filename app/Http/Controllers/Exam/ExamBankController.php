<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ExamBankQuestion;
use App\Models\SubjectTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Exam question bank, keyed by subject × class level and tagged to syllabus
 * topics (shared taxonomy with the topics tracker). Teachers pull from it into
 * a paper; HOD/admin curate it.
 */
class ExamBankController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // read (index, fetch) for anyone who can write papers; curation needs Manage exam bank
        $this->middleware('permission:Manage exam bank|Write exam papers')->only(['index', 'fetch']);
        $this->middleware('permission:Manage exam bank')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $subjects = DB::table('subject')->orderBy('subject')->get(['id', 'subject']);
        $levels   = DB::table('schoolclass')->select('schoolclass')->distinct()->orderBy('schoolclass')->pluck('schoolclass');

        $subjectId = (int) $request->get('subject_id');
        $level     = $request->get('class_level');

        $q = ExamBankQuestion::query()->with('topics:id,title');
        if ($subjectId) $q->where('subject_id', $subjectId);
        if ($level)     $q->where('class_level', $level);
        if ($d = $request->get('difficulty')) $q->where('difficulty', $d);
        if ($s = $request->get('q')) $q->where('question', 'like', '%'.$s.'%');

        $items = $q->orderByDesc('id')->paginate(25)->withQueryString();

        // topics for the add form (when a subject + level are chosen)
        $topics = ($subjectId && $level)
            ? SubjectTopic::where('subject_id', $subjectId)->where('class_level', $level)
                ->orderBy('week_no')->orderBy('position')->get(['id', 'title', 'week_no'])
            : collect();

        return view('exam.bank.index', [
            'pagetitle' => 'Exam Question Bank',
            'items'     => $items,
            'subjects'  => $subjects,
            'levels'    => $levels,
            'topics'    => $topics,
            'subjectId' => $subjectId,
            'level'     => $level,
            'difficulty'=> $request->get('difficulty'),
            'search'    => $request->get('q'),
            'canManage' => Auth::user()->can('Manage exam bank'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id'  => 'required|integer',
            'class_level' => 'required|string|max:100',
            'type'        => 'required|in:objective,theory,practical',
            'question'    => 'required|string|max:8000',
            'answer'      => 'nullable|string|max:8000',
            'marks'       => 'required|numeric|min:0|max:100',
            'difficulty'  => 'nullable|in:easy,medium,hard',
            'options'     => 'nullable|array',
            'topics'      => 'nullable|array',
        ]);

        $options = null;
        if ($data['type'] === 'objective' && !empty($data['options'])) {
            $options = array_filter(array_map('trim', $data['options']), fn ($v) => $v !== '');
            if (empty($options)) $options = null;
        }

        $item = ExamBankQuestion::create([
            'subject_id'  => $data['subject_id'],
            'class_level' => $data['class_level'],
            'type'        => $data['type'],
            'question'    => $data['question'],
            'answer'      => $data['answer'] ?? null,
            'marks'       => $data['marks'],
            'difficulty'  => $data['difficulty'] ?? null,
            'options'     => $options,
            'created_by'  => Auth::id(),
            'is_active'   => true,
        ]);

        $validTopics = SubjectTopic::where('subject_id', $data['subject_id'])
            ->where('class_level', $data['class_level'])->pluck('id')->all();
        $item->topics()->sync(array_values(array_intersect(array_map('intval', (array) ($data['topics'] ?? [])), $validTopics)));

        return back()->with('success', 'Question added to the bank.');
    }

    public function update(Request $request, ExamBankQuestion $question)
    {
        $data = $request->validate([
            'question'   => 'nullable|string|max:8000',
            'marks'      => 'nullable|numeric|min:0|max:100',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'is_active'  => 'nullable|boolean',
        ]);
        $question->fill(array_filter($data, fn ($v) => !is_null($v)));
        if ($request->has('is_active')) $question->is_active = $request->boolean('is_active');
        $question->save();

        return back()->with('success', 'Updated.');
    }

    public function destroy(ExamBankQuestion $question)
    {
        $question->topics()->detach();
        $question->delete();
        return back()->with('success', 'Removed from the bank.');
    }

    /** JSON feed for the paper builder: bank questions for a paper's subject + level. */
    public function fetch(Request $request)
    {
        $scId = (int) $request->get('subjectclass_id');
        $sc = DB::table('subjectclass as sjc')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->where('sjc.id', $scId)
            ->selectRaw('sjc.subjectid as subject_id, c.schoolclass as class_level')->first();

        if (!$sc) return response()->json(['items' => []]);

        $q = ExamBankQuestion::with('topics:id')
            ->where('is_active', true)
            ->where('subject_id', $sc->subject_id)
            ->where('class_level', $sc->class_level);
        if ($d = $request->get('difficulty')) $q->where('difficulty', $d);
        if ($s = $request->get('q'))          $q->where('question', 'like', '%'.$s.'%');

        $items = $q->orderByDesc('id')->limit(100)->get()->map(fn ($b) => [
            'id'         => $b->id,
            'type'       => $b->type,
            'question'   => $b->question,
            'options'    => $b->options ?: (object) [],
            'answer'     => $b->answer,
            'marks'      => (float) $b->marks,
            'difficulty' => $b->difficulty,
            'topics'     => $b->topics->pluck('id')->all(),
        ]);

        return response()->json(['items' => $items]);
    }
}
