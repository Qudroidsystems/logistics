<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\SubjectTopic;
use App\Models\TopicProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin / HOD side of the curriculum: build the syllabus (topics per subject ×
 * class level × term), drag them into weeks (scheme of work), verify delivery
 * and see coverage across arms.
 */
class TopicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage topics|Verify topics');
    }

    public function index(Request $request)
    {
        $topics = collect();
        if ($request->filled('subject') && $request->filled('class_level')) {
            $topics = SubjectTopic::where('subject_id', $request->subject)
                ->where('class_level', $request->class_level)
                ->when($request->filled('term'), fn ($q) => $q->where('term_id', $request->term))
                ->orderBy('position')->orderBy('week_no')->orderBy('id')->get();
        }

        return view('curriculum.topics.index', [
            'pagetitle'   => 'Curriculum Topics',
            'topics'      => $topics,
            'subjects'    => $this->subjects(),
            'classLevels' => $this->classLevels(),
            'terms'       => $this->terms(),
            'canManage'   => Auth::user()->can('Manage topics'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize2();
        $data = $this->rules($request);
        $data['position'] = (int) SubjectTopic::where('subject_id', $data['subject_id'])
            ->where('class_level', $data['class_level'])
            ->when(!empty($data['term_id']), fn ($q) => $q->where('term_id', $data['term_id']))
            ->max('position') + 1;
        $data['created_by'] = Auth::id();
        SubjectTopic::create($data);
        return back()->with('success', 'Topic added.');
    }

    /** Add many topics at once — one title per line. */
    public function bulkAdd(Request $request)
    {
        $this->authorize2();
        $request->validate([
            'subject_id'  => 'required|integer',
            'class_level' => 'required|string|max:100',
            'term_id'     => 'nullable|integer',
            'titles'      => 'required|string',
        ]);
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $request->titles))));
        $pos = (int) SubjectTopic::where('subject_id', $request->subject_id)
            ->where('class_level', $request->class_level)->max('position');
        $now = now(); $rows = [];
        foreach ($lines as $i => $title) {
            if ($title === '') continue;
            $rows[] = [
                'subject_id'  => $request->subject_id, 'class_level' => $request->class_level,
                'term_id'     => $request->term_id ?: null, 'title' => mb_substr($title, 0, 250),
                'position'    => ++$pos, 'is_active' => true, 'created_by' => Auth::id(),
                'created_at'  => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) SubjectTopic::insert($chunk);
        return back()->with('success', count($rows) . ' topic(s) added.');
    }

    public function update(Request $request, SubjectTopic $topic)
    {
        $this->authorize2();
        $topic->update($this->rules($request));
        return back()->with('success', 'Topic updated.');
    }

    public function destroy(SubjectTopic $topic)
    {
        $this->authorize2();
        TopicProgress::where('subject_topic_id', $topic->id)->delete();
        $topic->delete();
        return back()->with('success', 'Topic removed.');
    }

    public function reorder(Request $request)
    {
        $this->authorize2();
        foreach (array_values((array) $request->input('order', [])) as $i => $id) {
            SubjectTopic::where('id', (int) $id)->update(['position' => $i + 1]);
        }
        return response()->json(['ok' => true]);
    }

    /** HOD: coverage across every arm teaching this subject at this level/term. */
    public function coverage(Request $request)
    {
        $rows = collect();
        $total = 0;
        if ($request->filled('subject') && $request->filled('class_level')) {
            $topicIds = SubjectTopic::where('subject_id', $request->subject)
                ->where('class_level', $request->class_level)
                ->when($request->filled('term'), fn ($q) => $q->where('term_id', $request->term))
                ->pluck('id');
            $total = $topicIds->count();

            // arms teaching this subject at this class level (+ term)
            $arms = DB::table('subjectclass as sjc')
                ->join('schoolclass as sc', 'sc.id', '=', 'sjc.schoolclassid')
                ->leftJoin('schoolarm as arm', 'arm.id', '=', 'sc.arm')
                ->where('sjc.subjectid', $request->subject)
                ->where('sc.schoolclass', $request->class_level)
                ->when($request->filled('term'), fn ($q) => $q->where('sjc.termid', $request->term))
                ->selectRaw("sjc.id as subjectclass_id, TRIM(CONCAT(sc.schoolclass,' ',COALESCE(arm.arm,''))) as arm_name")
                ->get();

            $rows = $arms->map(function ($a) use ($topicIds, $total) {
                $taught = $total ? TopicProgress::where('subjectclass_id', $a->subjectclass_id)
                    ->whereIn('subject_topic_id', $topicIds)
                    ->whereIn('status', ['taught', 'confirmed'])->count() : 0;
                $confirmed = $total ? TopicProgress::where('subjectclass_id', $a->subjectclass_id)
                    ->whereIn('subject_topic_id', $topicIds)->where('status', 'confirmed')->count() : 0;
                return (object) [
                    'arm_name'  => $a->arm_name,
                    'taught'    => $taught,
                    'confirmed' => $confirmed,
                    'pct'       => $total ? (int) round($taught / $total * 100) : 0,
                ];
            });
        }

        return view('curriculum.topics.coverage', [
            'pagetitle'   => 'Topic Coverage',
            'rows'        => $rows,
            'total'       => $total,
            'subjects'    => $this->subjects(),
            'classLevels' => $this->classLevels(),
            'terms'       => $this->terms(),
        ]);
    }

    // ── helpers ─────────────────────────────────────────────────────────────
    protected function authorize2(): void
    {
        abort_unless(Auth::user()->can('Manage topics'), 403, 'You cannot manage topics.');
    }

    protected function rules(Request $request): array
    {
        $v = $request->validate([
            'subject_id'  => 'required|integer',
            'class_level' => 'required|string|max:100',
            'term_id'     => 'nullable|integer',
            'week_no'     => 'nullable|integer|min:1|max:60',
            'title'       => 'required|string|max:250',
            'description' => 'nullable|string|max:5000',
            'is_active'   => 'nullable|boolean',
        ]);
        $v['term_id']   = $v['term_id'] ?: null;
        $v['is_active'] = $request->boolean('is_active', true);
        return $v;
    }

    protected function subjects()
    {
        return Schema::hasTable('subject') ? DB::table('subject')->orderBy('subject')->get(['id', 'subject']) : collect();
    }

    protected function terms()
    {
        return Schema::hasTable('schoolterm') ? DB::table('schoolterm')->get(['id', 'term']) : collect();
    }

    /** Distinct class levels (schoolclass names, without arm). */
    protected function classLevels()
    {
        if (!Schema::hasTable('schoolclass')) return collect();
        return DB::table('schoolclass')->whereNotNull('schoolclass')
            ->select('schoolclass')->distinct()->orderBy('schoolclass')->pluck('schoolclass');
    }
}
