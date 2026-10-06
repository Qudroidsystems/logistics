<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\LmsAnnouncement;
use App\Models\LmsCourse;
use App\Models\LmsDiscussion;
use App\Models\LmsLiveClass;
use App\Services\Lms\CalendarSync;
use App\Services\Messaging\PortalNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teacher-side engagement: live classes, per-course announcements and
 * discussion moderation (pin / resolve / delete).
 */
class EngagementController extends Controller
{
    use InteractsWithLms;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage courses|Grade coursework');
    }

    // ── Live classes ──────────────────────────────────────────────────────
    public function storeLive(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $data = $this->liveRules($request);
        $data['course_id']  = $course->id;
        $data['created_by'] = $this->me()->id;
        $live = LmsLiveClass::create($data);
        CalendarSync::pushLiveClass($course, $live);

        $this->notifyLearners($course, 'New live class scheduled',
            "\"{$live->title}\" is scheduled" . ($live->scheduled_at ? ' for ' . $live->scheduled_at->format('D, d M Y H:i') : '') . '.');

        return back()->with('success', 'Live class scheduled.');
    }

    public function updateLive(Request $request, LmsCourse $course, LmsLiveClass $live)
    {
        $this->authorizeManage($course);
        abort_unless($live->course_id === $course->id, 404);
        $live->update($this->liveRules($request));
        CalendarSync::pushLiveClass($course, $live->fresh());
        return back()->with('success', 'Live class updated.');
    }

    public function destroyLive(LmsCourse $course, LmsLiveClass $live)
    {
        $this->authorizeManage($course);
        abort_unless($live->course_id === $course->id, 404);
        CalendarSync::remove('lms-live-' . $live->id);
        $live->delete();
        return back()->with('success', 'Live class removed.');
    }

    // ── Announcements ─────────────────────────────────────────────────────
    public function storeAnnouncement(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $data = $request->validate([
            'title'  => 'required|string|max:200',
            'body'   => 'required|string',
            'notify' => 'nullable|boolean',
        ]);
        $data['course_id']  = $course->id;
        $data['created_by'] = $this->me()->id;
        $data['notify']     = $request->boolean('notify', true);
        $a = LmsAnnouncement::create($data);

        if ($a->notify) {
            $this->notifyLearners($course, 'Course announcement: ' . $a->title, mb_substr(strip_tags($a->body), 0, 300));
        }
        return back()->with('success', 'Announcement posted.');
    }

    public function destroyAnnouncement(LmsCourse $course, LmsAnnouncement $announcement)
    {
        $this->authorizeManage($course);
        abort_unless($announcement->course_id === $course->id, 404);
        $announcement->delete();
        return back()->with('success', 'Announcement removed.');
    }

    // ── Discussion moderation ─────────────────────────────────────────────
    public function pinDiscussion(LmsCourse $course, LmsDiscussion $discussion)
    {
        $this->authorizeGrade($course);
        abort_unless($discussion->course_id === $course->id, 404);
        $discussion->update(['is_pinned' => !$discussion->is_pinned]);
        return back();
    }

    public function resolveDiscussion(LmsCourse $course, LmsDiscussion $discussion)
    {
        $this->authorizeGrade($course);
        abort_unless($discussion->course_id === $course->id, 404);
        $discussion->update(['is_resolved' => !$discussion->is_resolved]);
        return back();
    }

    public function destroyDiscussion(LmsCourse $course, LmsDiscussion $discussion)
    {
        $this->authorizeGrade($course);
        abort_unless($discussion->course_id === $course->id, 404);
        LmsDiscussion::where('parent_id', $discussion->id)->delete();
        $discussion->delete();
        return back()->with('success', 'Discussion removed.');
    }

    // ── helpers ───────────────────────────────────────────────────────────
    protected function liveRules(Request $request): array
    {
        return $request->validate([
            'title'            => 'required|string|max:200',
            'description'      => 'nullable|string|max:2000',
            'provider'         => 'nullable|string|max:40',
            'join_url'         => 'nullable|url|max:1000',
            'scheduled_at'     => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:0|max:1440',
            'status'           => 'nullable|in:scheduled,live,ended,cancelled',
        ]);
    }

    /** In-portal bell notification to every enrolled learner's user account. */
    protected function notifyLearners(LmsCourse $course, string $title, string $body): void
    {
        try {
            $studentIds = $course->enrollments()->pluck('student_id')->all();
            if (!$studentIds) return;
            $userIds = DB::table('users')->whereIn('student_id', $studentIds)->pluck('id')->map(fn ($v) => (int) $v)->all();
            if ($userIds) {
                PortalNotifier::toUsers($userIds, $title, $body, route('lms.learn.show', $course), 'notice');
            }
        } catch (\Throwable $e) {}
    }
}
