<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\LessonNote;
use App\Services\Messaging\PortalNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * HOD review of submitted lesson notes: approve or return with a comment.
 */
class LessonNoteReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Review lesson notes');
    }

    public function queue(Request $request)
    {
        $status = $request->input('status', 'submitted');
        $notes = LessonNote::when(in_array($status, array_keys(LessonNote::STATUS), true),
                fn ($q) => $q->where('status', $status))
            ->orderByDesc('updated_at')->paginate(25)->withQueryString();

        $scIds = $notes->pluck('subjectclass_id')->all();
        $labels = $scIds ? DB::table('subjectclass as sjc')
            ->join('subject as s', 's.id', '=', 'sjc.subjectid')
            ->join('schoolclass as c', 'c.id', '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'c.arm')
            ->whereIn('sjc.id', $scIds)
            ->selectRaw("sjc.id, TRIM(CONCAT(s.subject,' — ',c.schoolclass,' ',COALESCE(arm.arm,''))) as label")
            ->pluck('label', 'sjc.id')->all() : [];

        $teachers = DB::table('users')->whereIn('id', $notes->pluck('teacher_id')->all())->pluck('name', 'id')->all();

        return view('curriculum.notes.review', [
            'pagetitle' => 'Lesson Note Review',
            'notes'     => $notes,
            'labels'    => $labels,
            'teachers'  => $teachers,
            'statuses'  => LessonNote::STATUS,
            'status'    => $status,
        ]);
    }

    public function approve(LessonNote $note)
    {
        $note->update(['status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'review_comment' => null]);
        $this->notifyTeacher($note, 'Lesson note approved', "Your note \"{$note->title}\" was approved.");
        return back()->with('success', 'Approved.');
    }

    public function return(Request $request, LessonNote $note)
    {
        $data = $request->validate(['review_comment' => 'required|string|max:3000']);
        $note->update(['status' => 'returned', 'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'review_comment' => $data['review_comment']]);
        $this->notifyTeacher($note, 'Lesson note returned', "Your note \"{$note->title}\" needs changes: " . $data['review_comment']);
        return back()->with('success', 'Returned to the teacher.');
    }

    protected function notifyTeacher(LessonNote $note, string $title, string $body): void
    {
        try {
            if (class_exists(PortalNotifier::class)) {
                PortalNotifier::toUsers([(int) $note->teacher_id], $title, $body,
                    route('curriculum.notes.index'), 'notice');
            }
        } catch (\Throwable $e) {}
    }
}
