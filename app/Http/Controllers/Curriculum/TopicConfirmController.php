<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\ClassRep;
use App\Models\TopicConfirmation;
use App\Models\TopicProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class-rep side: confirm or dispute that a topic the teacher marked "taught" was
 * actually taught. One topic at a time, within a 7-day window, no "confirm all".
 */
class TopicConfirmController extends Controller
{
    protected const WINDOW_DAYS = 7;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $studentId = (int) (Auth::user()->student_id ?? 0);
        $armIds = $studentId
            ? ClassRep::where('student_id', $studentId)->pluck('schoolclass_id')->all()
            : [];
        if (!$armIds) abort(403, 'This area is for class reps.');

        // subjectclasses for the rep's arm(s)
        $subjectClassIds = DB::table('subjectclass')->whereIn('schoolclassid', $armIds)->pluck('id')->all();

        $items = collect();
        if ($subjectClassIds) {
            $items = DB::table('topic_progress as tp')
                ->join('subject_topics as t', 't.id', '=', 'tp.subject_topic_id')
                ->join('subject as s', 's.id', '=', 't.subject_id')
                ->join('subjectclass as sjc', 'sjc.id', '=', 'tp.subjectclass_id')
                ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
                ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
                ->whereIn('tp.subjectclass_id', $subjectClassIds)
                ->where('tp.status', 'taught')
                ->where('tp.student_confirmed', false)
                ->where('tp.disputed', false)
                ->whereNotNull('tp.taught_on')
                ->whereDate('tp.taught_on', '>=', now()->subDays(self::WINDOW_DAYS)->toDateString())
                ->orderByDesc('tp.taught_on')
                ->selectRaw("tp.id, tp.taught_on, t.title, s.subject as subject_name,
                    TRIM(CONCAT(c.schoolclass,' ',COALESCE(arm.arm,''))) as arm_name")
                ->get();
        }

        return view('curriculum.reps.confirm', [
            'pagetitle' => 'Confirm Topics',
            'items'     => $items,
        ]);
    }

    public function act(Request $request, TopicProgress $progress)
    {
        $studentId = (int) (Auth::user()->student_id ?? 0);
        abort_unless($studentId, 403);

        // the progress must belong to one of the rep's arms
        $armIds = ClassRep::where('student_id', $studentId)->pluck('schoolclass_id')->all();
        $ok = DB::table('subjectclass')->where('id', $progress->subjectclass_id)->whereIn('schoolclassid', $armIds)->exists();
        abort_unless($ok, 403, 'Not your class.');

        $data = $request->validate([
            'action' => 'required|in:confirm,dispute',
            'note'   => 'nullable|string|max:1000',
        ]);

        // window + state guard
        if ($progress->status !== 'taught' || !$progress->taught_on
            || $progress->taught_on->lt(now()->subDays(self::WINDOW_DAYS))) {
            return back()->with('error', 'This topic can no longer be confirmed.');
        }

        TopicConfirmation::create([
            'topic_progress_id' => $progress->id,
            'student_id'        => $studentId,
            'action'            => $data['action'],
            'note'              => $data['note'] ?? null,
        ]);

        if ($data['action'] === 'confirm') {
            $progress->student_confirmed = true;
        } else {
            $progress->disputed = true;
        }
        $progress->save();

        return back()->with('success', $data['action'] === 'confirm' ? 'Confirmed. Thank you.' : 'Disputed — your HOD will review.');
    }
}
