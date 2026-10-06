<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\LmsCourse;
use App\Services\Lms\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Read-only parent view of a child's e-learning progress and grades.
 */
class ParentLmsController extends Controller
{
    public function __construct(protected ProgressService $progress)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $children = $this->children();
        if ($children->count() === 1) {
            return redirect()->route('lms.parent.child', $children->first()->id);
        }
        return view('lms.parent.index', [
            'pagetitle' => 'Children — Learning',
            'children'  => $children,
        ]);
    }

    public function child(int $student)
    {
        $children = $this->children();
        $child = $children->firstWhere('id', $student);
        abort_unless($child, 403, 'This is not your child.');

        $courses = DB::table('lms_enrollments as e')
            ->join('lms_courses as c', 'c.id', '=', 'e.course_id')
            ->where('e.student_id', $student)->where('c.is_published', true)
            ->orderByDesc('e.updated_at')
            ->select('c.id', 'c.title', 'c.code', 'e.progress_percent', 'e.status')
            ->get();

        // quiz & assignment averages per course for this child
        foreach ($courses as $c) {
            $c->quiz_avg = DB::table('lms_quiz_attempts as a')
                ->join('lms_quizzes as q', 'q.id', '=', 'a.quiz_id')
                ->where('q.course_id', $c->id)->where('a.student_id', $student)
                ->avg('a.percent');
            $c->quiz_avg = $c->quiz_avg !== null ? round($c->quiz_avg, 1) : null;

            $subs = DB::table('lms_assignment_submissions as s')
                ->join('lms_assignments as ag', 'ag.id', '=', 's.assignment_id')
                ->where('ag.course_id', $c->id)->where('s.student_id', $student)
                ->whereNotNull('s.score')
                ->select('s.score', 'ag.max_score')->get();
            $c->assignment_avg = $subs->isNotEmpty()
                ? round($subs->map(fn ($r) => $r->max_score > 0 ? $r->score / $r->max_score * 100 : 0)->avg(), 1)
                : null;
        }

        return view('lms.parent.child', [
            'pagetitle' => 'Learning — ' . trim(($child->firstname ?? '') . ' ' . ($child->lastname ?? '')),
            'child'     => $child,
            'courses'   => $courses,
        ]);
    }

    protected function children()
    {
        $u = Auth::user();
        try {
            return $u->parentChildren()->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
