<?php

namespace App\Http\Controllers;

use App\Services\Leave\LeaveBoardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveBoardController extends Controller
{
    public function __construct(protected LeaveBoardService $svc)
    {
        $this->middleware('auth');
        $this->middleware('permission:View leave records|View student leave records|Approve leave|Approve student leave');
    }

    public function index(Request $request)
    {
        $anchor = $this->anchor($request);
        [$staff, $students] = $this->scope($request);

        $today = Carbon::today();
        $todayEvents = $this->svc->onDay($today, $staff, $students);
        $upcoming = $this->svc->events($today->copy()->addDay(), $today->copy()->addDays(7), $staff, $students);

        return view('leave.board', [
            'pagetitle'    => "Who's Away",
            'anchor'       => $anchor,
            'weeks'        => $this->svc->month($anchor, $staff, $students),
            'todayEvents'  => $todayEvents,
            'upcoming'     => $upcoming,
            'staff'        => $staff,
            'students'     => $students,
            'stats'        => [
                'staff_today'   => $todayEvents->where('kind', 'staff')->count(),
                'student_today' => $todayEvents->where('kind', 'student')->count(),
                'upcoming'      => $upcoming->count(),
            ],
        ]);
    }

    protected function anchor(Request $request): Carbon
    {
        $m = (string) $request->query('month', '');
        try {
            return $m ? Carbon::createFromFormat('Y-m', $m)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfMonth();
        }
    }

    /** Which sides to show, limited by what the viewer may see. */
    protected function scope(Request $request): array
    {
        $u = $request->user();
        $canStaff = $u->can('View leave records') || $u->can('Approve leave');
        $canStudents = $u->can('View student leave records') || $u->can('Approve student leave');

        $show = (string) $request->query('show', 'all');
        $staff = $canStaff && in_array($show, ['all', 'staff'], true);
        $students = $canStudents && in_array($show, ['all', 'students'], true);

        // If the chosen filter left nothing (e.g. no permission), fall back to what they can see.
        if (!$staff && !$students) { $staff = $canStaff; $students = $canStudents; }
        return [$staff, $students];
    }
}
