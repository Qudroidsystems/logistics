<?php

namespace App\Http\Controllers;

use App\Models\Schoolsession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * "My Subjects" -- a teacher's read-only view of their subject assignments.
 *
 * Assignments themselves are created by admins (Subject Teacher / Subject
 * Class screens), so this controller only lists them -- one row per
 * subject x class x term -- with live score-entry and vetting progress and
 * a direct link into the score sheet for each one.
 */
class MySubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-subject|Create my-subject|Update my-subject|Delete my-subject', ['only' => ['index']]);
    }

    public function index(): View
    {
        $pagetitle = 'My Subjects';
        $user      = Auth::user();

        $currentSession = Schoolsession::where('status', 'Current')->first();

        $current = $currentSession
            ? $this->assignmentsQuery($user->id)
                ->where('st.sessionid', $currentSession->id)
                ->orderBy('st.termid')->orderBy('s.subject')->orderBy('sc.schoolclass')->orderBy('arm.arm')
                ->get()
            : collect();

        $history = $this->assignmentsQuery($user->id)
            ->when($currentSession, fn ($q) => $q->where('st.sessionid', '!=', $currentSession->id))
            ->orderByDesc('st.sessionid')->orderBy('st.termid')->orderBy('s.subject')->orderBy('sc.schoolclass')
            ->get();

        $current = $this->attachProgress($current);

        $withClass = $current->whereNotNull('subjectclass_id');
        $stats = [
            'subjects'    => $current->pluck('subject_id')->unique()->count(),
            'classes'     => $withClass->pluck('schoolclass_id')->unique()->count(),
            'assignments' => $current->count(),
            'students'    => (int) $withClass->sum('students'),
            'progress'    => $withClass->count()
                ? (int) round($withClass->avg('progress'))
                : 0,
            'history'     => $history->count(),
        ];

        // Filter options, built from what the teacher actually has.
        $termOptions    = $current->filter(fn ($r) => $r->termid)->unique('termid')
            ->sortBy('termid')->mapWithKeys(fn ($r) => [$r->termid => $r->term])->all();
        $classOptions   = $withClass->unique('schoolclass_id')
            ->sortBy(fn ($r) => $r->schoolclass . ' ' . $r->arm)
            ->mapWithKeys(fn ($r) => [$r->schoolclass_id => trim($r->schoolclass . ' ' . $r->arm)])->all();
        $historySessions = $history->filter(fn ($r) => $r->sessionid)->unique('sessionid')
            ->mapWithKeys(fn ($r) => [$r->sessionid => $r->session])->all();

        return view('mysubject.index', compact(
            'pagetitle', 'currentSession', 'current', 'history', 'stats',
            'termOptions', 'classOptions', 'historySessions'
        ));
    }

    /**
     * One row per subject-teacher x class. A subject-teacher pairing with no
     * class linked yet still shows (class columns null) so the teacher can
     * see it and ask for it to be completed.
     */
    private function assignmentsQuery(int $staffId)
    {
        return DB::table('subjectteacher as st')
            ->join('subject as s',              's.id',   '=', 'st.subjectid')
            ->leftJoin('subjectclass as sjc',   'sjc.subjectteacherid', '=', 'st.id')
            ->leftJoin('schoolclass as sc',     'sc.id',  '=', 'sjc.schoolclassid')
            ->leftJoin('schoolarm as arm',      'arm.id', '=', 'sc.arm')
            ->leftJoin('schoolterm as t',       't.id',   '=', 'st.termid')
            ->leftJoin('schoolsession as ss',   'ss.id',  '=', 'st.sessionid')
            ->where('st.staffid', $staffId)
            ->select([
                'st.id           as subjectteacher_id',
                'sjc.id          as subjectclass_id',
                's.id            as subject_id',
                's.subject       as subject',
                's.subject_code  as subject_code',
                'sc.id           as schoolclass_id',
                'sc.schoolclass  as schoolclass',
                'arm.arm         as arm',
                'st.termid       as termid',
                't.term          as term',
                'st.sessionid    as sessionid',
                'ss.session      as session',
            ]);
    }

    /**
     * Adds students / scores_entered / vetted / progress to each row, using
     * two grouped queries for the whole list (not one per row).
     *
     *  - students: distinct students registered for that subject-class this
     *    term (subjectRegistrationStatus). If nobody is registered yet, falls
     *    back to the number of broadsheet rows so progress still means
     *    something.
     *  - scores_entered: students with a non-zero total on the broadsheet.
     *  - vetted: broadsheet rows marked vetted.
     */
    private function attachProgress(Collection $rows): Collection
    {
        $sjcIds = $rows->pluck('subjectclass_id')->filter()->unique()->values();
        if ($sjcIds->isEmpty()) {
            return $rows->map(fn ($r) => $this->withProgress($r, 0, 0, 0, 0));
        }

        $registered = DB::table('subjectRegistrationStatus')
            ->whereIn('subjectclassid', $sjcIds)
            ->select([
                'subjectclassid', 'termid', 'sessionid',
                DB::raw('COUNT(DISTINCT studentid) as n'),
            ])
            ->groupBy('subjectclassid', 'termid', 'sessionid')
            ->get()
            ->keyBy(fn ($r) => "{$r->subjectclassid}_{$r->termid}_{$r->sessionid}");

        $sheets = DB::table('broadsheets as b')
            ->join('broadsheet_records as br', 'br.id', '=', 'b.broadsheet_record_id')
            ->whereIn('b.subjectclass_id', $sjcIds)
            ->select([
                'b.subjectclass_id', 'b.term_id', 'br.session_id',
                DB::raw('COUNT(DISTINCT br.student_id) as row_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN b.total > 0 THEN br.student_id END) as entered'),
                DB::raw('SUM(CASE WHEN b.vettedstatus = 1 THEN 1 ELSE 0 END) as vetted'),
            ])
            ->groupBy('b.subjectclass_id', 'b.term_id', 'br.session_id')
            ->get()
            ->keyBy(fn ($r) => "{$r->subjectclass_id}_{$r->term_id}_{$r->session_id}");

        return $rows->map(function ($r) use ($registered, $sheets) {
            if (!$r->subjectclass_id) return $this->withProgress($r, 0, 0, 0, 0);

            $key   = "{$r->subjectclass_id}_{$r->termid}_{$r->sessionid}";
            $reg   = (int) ($registered[$key]->n ?? 0);
            $sheet = $sheets[$key] ?? null;

            return $this->withProgress(
                $r,
                $reg,
                (int) ($sheet->row_count ?? 0),
                (int) ($sheet->entered ?? 0),
                (int) ($sheet->vetted ?? 0)
            );
        });
    }

    private function withProgress(object $r, int $registered, int $sheetRows, int $entered, int $vetted): object
    {
        $students       = max($registered, $sheetRows);
        $r->students    = $students;
        $r->entered     = min($entered, $students ?: $entered);
        $r->vetted      = $vetted;
        $r->progress    = $students > 0 ? (int) round($r->entered / $students * 100) : 0;
        $r->status      = !$r->subjectclass_id ? 'unlinked'
            : ($students === 0 ? 'no_students'
            : ($r->progress >= 100 ? 'complete' : ($r->entered > 0 ? 'in_progress' : 'not_started')));
        return $r;
    }
}
