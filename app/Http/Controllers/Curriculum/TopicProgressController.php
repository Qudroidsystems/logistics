<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\SubjectTopic;
use App\Models\TopicProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Teacher's topic board (the "task manager for topics"): per assigned class/arm,
 * mark topics planned / taught, add notes, and see pace. HOD verification lives
 * here too.
 */
class TopicProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Track topics|Manage topics|Verify topics');
    }

    /** The teacher's own subject+arm assignments for the current session. */
    public function index()
    {
        $uid = Auth::id();
        $currentSession = DB::table('schoolsession')->where('status', 'Current')->value('id');

        $rows = DB::table('subjectteacher as st')
            ->join('subject as s', 's.id', '=', 'st.subjectid')
            ->join('subjectclass as sjc', 'sjc.subjectteacherid', '=', 'st.id')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
            ->leftJoin('schoolterm as t', 't.id', '=', 'sjc.termid')
            ->where('st.staffid', $uid)
            ->when($currentSession, fn ($q) => $q->where('st.sessionid', $currentSession))
            ->selectRaw("sjc.id as subjectclass_id, s.subject as subject_name, c.schoolclass as class_level,
                TRIM(CONCAT(c.schoolclass,' ',COALESCE(arm.arm,''))) as arm_name, t.term as term_name,
                sjc.subjectid as subject_id, sjc.termid as term_id")
            ->orderBy('s.subject')->orderBy('c.schoolclass')->get();

        // attach coverage per assignment
        foreach ($rows as $r) {
            $topicIds = $this->topicIds($r->subject_id, $r->class_level, $r->term_id);
            $r->total = $topicIds->count();
            $r->taught = $r->total ? TopicProgress::where('subjectclass_id', $r->subjectclass_id)
                ->whereIn('subject_topic_id', $topicIds)->whereIn('status', ['taught', 'confirmed'])->count() : 0;
            $r->pct = $r->total ? (int) round($r->taught / $r->total * 100) : 0;
        }

        return view('curriculum.progress.index', [
            'pagetitle' => 'My Topics',
            'rows'      => $rows,
        ]);
    }

    /** The board for one subject+arm assignment. */
    public function board(int $subjectclass)
    {
        $sc = $this->resolve($subjectclass);
        $this->authorizeView($sc);

        $topics = SubjectTopic::where('subject_id', $sc->subject_id)
            ->where('class_level', $sc->class_level)
            ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $sc->term_id))
            ->orderBy('position')->orderBy('week_no')->orderBy('id')->get();

        $progress = TopicProgress::where('subjectclass_id', $subjectclass)
            ->get()->keyBy('subject_topic_id');

        $total = $topics->count();
        $taught = 0; $overdue = 0;
        foreach ($topics as $t) {
            $p = $progress->get($t->id);
            if ($p && $p->isTaught()) $taught++;
            if ($p && $p->isOverdue()) $overdue++;
        }
        $pace = $overdue === 0 ? ['On track', 'st-paid'] : ($overdue <= 2 ? ['Slightly behind', 'st-pending'] : ['Behind', 'st-danger']);

        return view('curriculum.progress.board', [
            'pagetitle' => $sc->subject_name . ' — ' . $sc->arm_name,
            'sc'        => $sc,
            'topics'    => $topics,
            'progress'  => $progress,
            'total'     => $total,
            'taught'    => $taught,
            'overdue'   => $overdue,
            'pct'       => $total ? (int) round($taught / $total * 100) : 0,
            'pace'      => $pace,
            'canVerify' => Auth::user()->can('Verify topics'),
            'isOwner'   => (int) $sc->staff_id === (int) Auth::id(),
        ]);
    }

    /** Teacher marks a topic planned/taught and adds notes. */
    public function mark(Request $request, int $subjectclass, SubjectTopic $topic)
    {
        $sc = $this->resolve($subjectclass);
        $this->authorizeEdit($sc);
        abort_unless($topic->subject_id == $sc->subject_id && $topic->class_level === $sc->class_level, 404);

        $data = $request->validate([
            'status'       => 'required|in:pending,taught',
            'planned_week' => 'nullable|integer|min:1|max:60',
            'planned_date' => 'nullable|date',
            'taught_on'    => 'nullable|date',
            'note'         => 'nullable|string|max:3000',
        ]);

        $p = TopicProgress::firstOrNew([
            'subject_topic_id' => $topic->id, 'subjectclass_id' => $subjectclass,
        ]);
        // if HOD had verified, a change back to pending drops it to pending
        $p->status = $data['status'] === 'taught'
            ? ($p->status === 'confirmed' ? 'confirmed' : 'taught')
            : 'pending';
        $p->planned_week = $data['planned_week'] ?? $p->planned_week;
        $p->planned_date = $data['planned_date'] ?? $p->planned_date;
        $p->note = $data['note'] ?? $p->note;
        if ($data['status'] === 'taught') {
            $p->taught_on = $data['taught_on'] ?? ($p->taught_on ?: now()->toDateString());
            $p->taught_by = Auth::id();
        } else {
            $p->taught_on = null;
            $p->student_confirmed = false;
        }
        $p->save();

        return back()->with('success', 'Topic updated.');
    }

    /** HOD verifies (or comments on) a taught topic. */
    public function verify(Request $request, int $subjectclass, SubjectTopic $topic)
    {
        abort_unless(Auth::user()->can('Verify topics'), 403);
        $sc = $this->resolve($subjectclass);
        $data = $request->validate([
            'verified'    => 'nullable|boolean',
            'hod_comment' => 'nullable|string|max:3000',
        ]);

        $p = TopicProgress::firstOrNew([
            'subject_topic_id' => $topic->id, 'subjectclass_id' => $subjectclass,
        ]);
        $p->hod_comment = $data['hod_comment'] ?? $p->hod_comment;
        if ($request->boolean('verified')) {
            $p->status = 'confirmed';
            $p->verified_by = Auth::id();
            $p->verified_at = now();
        } elseif ($p->status === 'confirmed') {
            $p->status = $p->taught_on ? 'taught' : 'pending';
            $p->verified_by = null; $p->verified_at = null;
        }
        $p->save();

        return back()->with('success', 'Saved.');
    }

    // ── helpers ─────────────────────────────────────────────────────────────
    protected function topicIds($subjectId, $classLevel, $termId)
    {
        return SubjectTopic::where('subject_id', $subjectId)->where('class_level', $classLevel)
            ->where(fn ($q) => $q->whereNull('term_id')->orWhere('term_id', $termId))
            ->pluck('id');
    }

    protected function resolve(int $id)
    {
        $sc = DB::table('subjectclass as sjc')
            ->join('subjectteacher as st', 'st.id', '=', 'sjc.subjectteacherid')
            ->join('subject as s', 's.id', '=', 'sjc.subjectid')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
            ->where('sjc.id', $id)
            ->selectRaw("sjc.id, sjc.subjectid as subject_id, sjc.termid as term_id, sjc.sessionid as session_id,
                st.staffid as staff_id, s.subject as subject_name, c.schoolclass as class_level, c.id as schoolclass_id,
                TRIM(CONCAT(c.schoolclass,' ',COALESCE(arm.arm,''))) as arm_name")
            ->first();
        abort_unless($sc, 404);
        return $sc;
    }

    protected function authorizeView($sc): void
    {
        $u = Auth::user();
        if ((int) $sc->staff_id === (int) $u->id) return;
        abort_unless($u->can('Verify topics') || $u->can('Manage topics'), 403, 'Not your class.');
    }

    protected function authorizeEdit($sc): void
    {
        $u = Auth::user();
        if ((int) $sc->staff_id === (int) $u->id) return;
        abort_unless($u->can('Manage topics'), 403, 'Only the assigned teacher can update delivery.');
    }
}
