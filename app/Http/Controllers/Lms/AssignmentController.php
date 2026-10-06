<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithLms;
use App\Models\LmsAssignment;
use App\Models\LmsAssignmentSubmission;
use App\Models\LmsCourse;
use App\Services\Lms\CalendarSync;
use App\Services\Messaging\PortalNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teacher-side assignment management and grading.
 */
class AssignmentController extends Controller
{
    use InteractsWithLms;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage courses|Grade coursework');
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->authorizeManage($course);
        $data = $this->rules($request);
        $data['course_id']  = $course->id;
        $data['created_by'] = $this->me()->id;
        $a = LmsAssignment::create($data);
        CalendarSync::pushAssignment($course, $a);
        return back()->with('success', 'Assignment created.');
    }

    public function update(Request $request, LmsCourse $course, LmsAssignment $assignment)
    {
        $this->authorizeManage($course);
        abort_unless($assignment->course_id === $course->id, 404);
        $assignment->update($this->rules($request));
        CalendarSync::pushAssignment($course, $assignment->fresh());
        return back()->with('success', 'Assignment updated.');
    }

    public function destroy(LmsCourse $course, LmsAssignment $assignment)
    {
        $this->authorizeManage($course);
        abort_unless($assignment->course_id === $course->id, 404);
        DB::table('lms_assignment_submissions')->where('assignment_id', $assignment->id)->delete();
        CalendarSync::remove('lms-assignment-' . $assignment->id);
        $assignment->delete();
        return back()->with('success', 'Assignment deleted.');
    }

    /** List submissions for grading. */
    public function submissions(LmsCourse $course, LmsAssignment $assignment)
    {
        $this->authorizeGrade($course);
        abort_unless($assignment->course_id === $course->id, 404);

        $subs = DB::table('lms_assignment_submissions as sub')
            ->leftJoin('studentRegistration as s', 's.id', '=', 'sub.student_id')
            ->where('sub.assignment_id', $assignment->id)
            ->orderByDesc('sub.submitted_at')
            ->select('sub.*', 's.firstname', 's.lastname', 's.admissionNo')
            ->paginate(30);

        return view('lms.assignments.submissions', [
            'pagetitle'  => 'Submissions — ' . $assignment->title,
            'course'     => $course,
            'assignment' => $assignment,
            'subs'       => $subs,
            'enrolled'   => $course->enrollments()->count(),
        ]);
    }

    public function grade(Request $request, LmsCourse $course, LmsAssignment $assignment, LmsAssignmentSubmission $submission)
    {
        $this->authorizeGrade($course);
        abort_unless($assignment->course_id === $course->id && $submission->assignment_id === $assignment->id, 404);

        $rubricScores = null;
        if ($assignment->hasRubric()) {
            // score is the sum of per-criterion marks, each capped at its max
            $request->validate(['rubric_scores' => 'nullable|array', 'feedback' => 'nullable|string|max:5000']);
            $rubricScores = [];
            $score = 0.0;
            foreach ($assignment->rubric as $i => $crit) {
                $max = (float) ($crit['max'] ?? 0);
                $val = max(0, min($max, (float) ($request->input("rubric_scores.$i", 0))));
                $rubricScores[$i] = $val;
                $score += $val;
            }
            $feedback = $request->input('feedback');
        } else {
            $data = $request->validate([
                'score'    => 'required|numeric|min:0|max:' . $assignment->max_score,
                'feedback' => 'nullable|string|max:5000',
            ]);
            $score = (float) $data['score'];
            $feedback = $data['feedback'] ?? null;
        }

        $submission->update([
            'score'         => $score,
            'rubric_scores' => $rubricScores,
            'feedback'      => $feedback,
            'status'        => 'graded',
            'graded_by'     => $this->me()->id,
            'graded_at'     => now(),
        ]);

        // notify the student
        try {
            $userId = DB::table('users')->where('student_id', $submission->student_id)->value('id');
            if ($userId) {
                $shown = rtrim(rtrim(number_format($score, 2), '0'), '.');
                PortalNotifier::toUsers([(int) $userId],
                    'Assignment graded',
                    "Your submission for \"{$assignment->title}\" was graded: {$shown}/{$assignment->max_score}.",
                    route('lms.learn.show', $course), 'result');
            }
        } catch (\Throwable $e) {}

        return back()->with('success', 'Grade saved.');
    }

    protected function rules(Request $request): array
    {
        $v = $request->validate([
            'title'            => 'required|string|max:200',
            'instructions'     => 'nullable|string',
            'lesson_id'        => 'nullable|integer',
            'max_score'        => 'required|numeric|min:1|max:100000',
            'due_at'           => 'nullable|date',
            'allow_file'       => 'nullable|boolean',
            'allow_text'       => 'nullable|boolean',
            'is_published'     => 'nullable|boolean',
            'rubric_name'      => 'nullable|array',
            'rubric_name.*'    => 'nullable|string|max:200',
            'rubric_max'       => 'nullable|array',
            'rubric_max.*'     => 'nullable|numeric|min:0|max:100000',
        ]);
        $v['allow_file']   = $request->boolean('allow_file');
        $v['allow_text']   = $request->boolean('allow_text');
        $v['is_published'] = $request->boolean('is_published', true);
        if (!$v['allow_file'] && !$v['allow_text']) $v['allow_text'] = true;

        // rubric: pair name[] with max[]; when present, max_score = sum of maxes
        $names = $request->input('rubric_name', []);
        $maxes = $request->input('rubric_max', []);
        $rubric = [];
        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            $rubric[] = ['name' => $name, 'max' => (float) ($maxes[$i] ?? 0)];
        }
        if ($rubric) {
            $v['rubric'] = $rubric;
            $v['max_score'] = array_sum(array_column($rubric, 'max')) ?: $v['max_score'];
        } else {
            $v['rubric'] = null;
        }
        unset($v['rubric_name'], $v['rubric_max']);
        return $v;
    }
}
