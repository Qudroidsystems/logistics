<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\LessonNote;
use App\Models\SubjectTopic;
use App\Models\TeachingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Teacher-side lesson notes: draft, submit for HOD review, deliver, copy from a
 * past note, and print. Notes link to the week's syllabus topics and record the
 * teaching methods used.
 */
class LessonNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Write lesson notes|Review lesson notes');
    }

    public function index(Request $request)
    {
        $notes = LessonNote::where('teacher_id', Auth::id())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        // subject + arm labels
        $scIds = $notes->pluck('subjectclass_id')->all();
        $labels = $this->labelsFor($scIds);

        return view('curriculum.notes.index', [
            'pagetitle' => 'My Lesson Notes',
            'notes'     => $notes,
            'labels'    => $labels,
            'statuses'  => LessonNote::STATUS,
        ]);
    }

    public function create()
    {
        return view('curriculum.notes.form', $this->formData(new LessonNote(['status' => 'draft'])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $sc = $this->ownedSubjectclass($data['subjectclass_id']);

        $note = LessonNote::create([
            'subjectclass_id' => $sc->id,
            'subject_id'      => $sc->subject_id,
            'class_level'     => $sc->class_level,
            'term_id'         => $sc->term_id,
            'session_id'      => $sc->session_id,
            'week_no'         => $data['week_no'] ?? null,
            'title'           => $data['title'],
            'objectives'      => $data['objectives'] ?? null,
            'content'         => $data['content'] ?? null,
            'materials'       => $data['materials'] ?? null,
            'methods'         => $data['methods'] ?? [],
            'status'          => $request->input('action') === 'submit' ? 'submitted' : 'draft',
            'teacher_id'      => Auth::id(),
        ]);
        $this->syncTopics($note, $sc, (array) $request->input('topics', []));

        return redirect()->route('curriculum.notes.index')->with('success', $note->status === 'submitted' ? 'Lesson note submitted for review.' : 'Draft saved.');
    }

    public function edit(LessonNote $note)
    {
        $this->authorizeOwner($note);
        abort_unless($note->isEditable(), 403, 'This note can no longer be edited.');
        return view('curriculum.notes.form', $this->formData($note));
    }

    public function update(Request $request, LessonNote $note)
    {
        $this->authorizeOwner($note);
        abort_unless($note->isEditable(), 403);
        $data = $this->validated($request);
        $sc = $this->ownedSubjectclass($data['subjectclass_id']);

        $note->update([
            'subjectclass_id' => $sc->id, 'subject_id' => $sc->subject_id, 'class_level' => $sc->class_level,
            'term_id' => $sc->term_id, 'session_id' => $sc->session_id, 'week_no' => $data['week_no'] ?? null,
            'title' => $data['title'], 'objectives' => $data['objectives'] ?? null, 'content' => $data['content'] ?? null,
            'materials' => $data['materials'] ?? null, 'methods' => $data['methods'] ?? [],
            'status' => $request->input('action') === 'submit' ? 'submitted' : $note->status,
        ]);
        $this->syncTopics($note, $sc, (array) $request->input('topics', []));

        return redirect()->route('curriculum.notes.index')->with('success', 'Lesson note saved.');
    }

    public function submit(LessonNote $note)
    {
        $this->authorizeOwner($note);
        abort_unless($note->isEditable(), 403);
        $note->update(['status' => 'submitted']);
        return back()->with('success', 'Submitted for review.');
    }

    public function deliver(LessonNote $note)
    {
        $this->authorizeOwner($note);
        abort_unless($note->status === 'approved', 403, 'Only an approved note can be marked delivered.');
        $note->update(['status' => 'delivered', 'delivered_on' => now()->toDateString()]);
        return back()->with('success', 'Marked as delivered.');
    }

    public function copy(LessonNote $note)
    {
        $this->authorizeOwner($note);
        $new = $note->replicate(['status', 'reviewed_by', 'reviewed_at', 'review_comment', 'delivered_on']);
        $new->title = $note->title . ' (copy)';
        $new->status = 'draft';
        $new->save();
        $new->topics()->sync($note->topics()->pluck('subject_topics.id')->all());
        return redirect()->route('curriculum.notes.edit', $new)->with('success', 'Copied to a new draft.');
    }

    public function destroy(LessonNote $note)
    {
        $this->authorizeOwner($note);
        abort_unless($note->isEditable(), 403);
        $note->topics()->detach();
        $note->delete();
        return back()->with('success', 'Lesson note deleted.');
    }

    public function show(LessonNote $note)
    {
        $this->authorizeViewer($note);
        return view('curriculum.notes.print', [
            'note'    => $note->load('topics', 'subject'),
            'label'   => $this->labelsFor([$note->subjectclass_id])[$note->subjectclass_id] ?? '',
            'methods' => TeachingMethod::whereIn('id', (array) $note->methods)->pluck('name')->all(),
        ]);
    }

    // ── helpers ─────────────────────────────────────────────────────────────
    protected function validated(Request $request): array
    {
        return $request->validate([
            'subjectclass_id' => 'required|integer',
            'week_no'         => 'nullable|integer|min:1|max:60',
            'title'           => 'required|string|max:200',
            'objectives'      => 'nullable|string',
            'content'         => 'nullable|string',
            'materials'       => 'nullable|string',
            'methods'         => 'nullable|array',
            'methods.*'       => 'integer',
            'topics'          => 'nullable|array',
            'topics.*'        => 'integer',
        ]);
    }

    protected function syncTopics(LessonNote $note, $sc, array $topicIds): void
    {
        // keep only topics that belong to this subject + class level
        $valid = SubjectTopic::where('subject_id', $sc->subject_id)->where('class_level', $sc->class_level)
            ->whereIn('id', array_map('intval', $topicIds))->pluck('id')->all();
        $note->topics()->sync($valid);
    }

    protected function formData(LessonNote $note): array
    {
        $assignments = $this->myAssignments();
        $topicsByClass = [];
        foreach ($assignments as $a) {
            $topicsByClass[$a->subjectclass_id] = SubjectTopic::where('subject_id', $a->subject_id)
                ->where('class_level', $a->class_level)
                ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $a->term_id))
                ->orderBy('position')->orderBy('week_no')->get(['id', 'title', 'week_no']);
        }

        return [
            'pagetitle'     => $note->exists ? 'Edit Lesson Note' : 'New Lesson Note',
            'note'          => $note,
            'assignments'   => $assignments,
            'topicsByClass' => $topicsByClass,
            'methods'       => TeachingMethod::where('is_active', true)->orderBy('name')->get(),
            'selectedTopics'=> $note->exists ? $note->topics()->pluck('subject_topics.id')->all() : [],
        ];
    }

    /** The teacher's subject+arm assignments for the current session. */
    protected function myAssignments()
    {
        $currentSession = DB::table('schoolsession')->where('status', 'Current')->value('id');
        return DB::table('subjectteacher as st')
            ->join('subject as s', 's.id', '=', 'st.subjectid')
            ->join('subjectclass as sjc', 'sjc.subjectteacherid', '=', 'st.id')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
            ->where('st.staffid', Auth::id())
            ->when($currentSession, fn ($q) => $q->where('st.sessionid', $currentSession))
            ->selectRaw("sjc.id as subjectclass_id, sjc.subjectid as subject_id, sjc.termid as term_id, sjc.sessionid as session_id,
                s.subject as subject_name, c.schoolclass as class_level,
                TRIM(CONCAT(s.subject,' — ',c.schoolclass,' ',COALESCE(arm.arm,''))) as label")
            ->orderBy('s.subject')->get();
    }

    protected function ownedSubjectclass(int $id)
    {
        $sc = DB::table('subjectclass as sjc')
            ->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')
            ->join('subject as s', 's.id', '=', 'sjc.subjectid')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->where('sjc.id', $id)
            ->selectRaw("sjc.id, sjc.subjectid as subject_id, sjc.termid as term_id, sjc.sessionid as session_id,
                st.staffid as staff_id, s.subject as subject_name, c.schoolclass as class_level")
            ->first();
        abort_unless($sc, 404);
        abort_unless((int) $sc->staff_id === (int) Auth::id() || Auth::user()->can('Manage topics'), 403, 'Not your class.');
        return $sc;
    }

    protected function labelsFor(array $scIds): array
    {
        if (!$scIds) return [];
        return DB::table('subjectclass as sjc')
            ->join('subject as s', 's.id', '=', 'sjc.subjectid')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
            ->whereIn('sjc.id', $scIds)
            ->selectRaw("sjc.id, TRIM(CONCAT(s.subject,' — ',c.schoolclass,' ',COALESCE(arm.arm,''))) as label")
            ->pluck('label', 'sjc.id')->all();
    }

    protected function authorizeOwner(LessonNote $note): void
    {
        abort_unless((int) $note->teacher_id === (int) Auth::id(), 403, 'Not your lesson note.');
    }

    protected function authorizeViewer(LessonNote $note): void
    {
        $u = Auth::user();
        if ((int) $note->teacher_id === (int) $u->id) return;
        abort_unless($u->can('Review lesson notes') || $u->can('Manage topics'), 403);
    }
}
