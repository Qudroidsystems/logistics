<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\ClassRep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Admin assigns up to two class reps per arm (per session + term). Reps confirm
 * that topics were actually taught (see TopicConfirmController).
 */
class ClassRepController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage topics');
    }

    public function index(Request $request)
    {
        $arm = $request->input('class');

        $sessions = DB::table('schoolsession')->orderByDesc('id')->get(['id', 'session', 'status']);
        $currentSession = optional($sessions->firstWhere('status', 'Current'))->id;
        $sessionId = (int) $request->input('session') ?: ($currentSession ? (int) $currentSession : null);

        $reps = collect();
        $students = collect();

        if ($arm) {
            $reps = DB::table('class_reps as cr')
                ->leftJoin('studentRegistration as s', 's.id', '=', 'cr.student_id')
                ->where('cr.schoolclass_id', $arm)
                ->when($sessionId, fn ($q) => $q->where(function ($w) use ($sessionId) {
                    $w->where('cr.session_id', $sessionId)->orWhereNull('cr.session_id');
                }))
                ->selectRaw("cr.*, TRIM(CONCAT(COALESCE(s.firstname,''),' ',COALESCE(s.lastname,''))) as name, s.admissionNo")
                ->get();

            // Roster builder — resilient to which session the studentclass rows sit under.
            $roster = function (?int $sid) use ($arm) {
                $q = DB::table('studentclass as sc')
                    ->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
                    ->where('sc.schoolclassid', $arm);
                if ($sid) $q->where('sc.sessionid', $sid);
                return $q->orderBy('s.lastname')->orderBy('s.firstname')
                    ->distinct()
                    ->get([
                        's.id',
                        DB::raw("TRIM(CONCAT(COALESCE(s.firstname,''),' ',COALESCE(s.lastname,''))) as name"),
                        's.admissionNo as admissionNo',
                    ]);
            };

            $students = $roster($sessionId);
            if ($students->isEmpty()) {
                // Fall back to the most recent cohort that actually has rows for this arm.
                $latest = DB::table('studentclass')->where('schoolclassid', $arm)->max('sessionid');
                if ($latest && (int) $latest !== (int) $sessionId) {
                    $students = $roster((int) $latest);
                }
            }
            if ($students->isEmpty()) {
                // Last resort: any student ever recorded in this arm.
                $students = $roster(null);
            }
        }

        return view('curriculum.reps.index', [
            'pagetitle' => 'Class Reps',
            'arms'      => $this->arms(),
            'arm'       => $arm,
            'reps'      => $reps,
            'students'  => $students,
            'sessions'  => $sessions,
            'sessionId' => $sessionId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'     => 'required|integer',
            'schoolclass_id' => 'required|integer',
            'session_id'     => 'nullable|integer',
        ]);

        $sessionId = $data['session_id']
            ?? DB::table('schoolsession')->where('status', 'Current')->value('id');
        $termId = DB::table('schoolterm')->where('status', 1)->value('id');

        $count = ClassRep::where('schoolclass_id', $data['schoolclass_id'])
            ->when($sessionId, fn ($q) => $q->where(fn ($w) => $w->where('session_id', $sessionId)->orWhereNull('session_id')))
            ->count();
        if ($count >= 2) return back()->with('error', 'An arm can have at most two class reps.');

        if (ClassRep::where('schoolclass_id', $data['schoolclass_id'])
                ->where('student_id', $data['student_id'])->exists()) {
            return back()->with('error', 'That student is already a rep for this arm.');
        }

        ClassRep::create([
            'student_id'     => $data['student_id'],
            'schoolclass_id' => $data['schoolclass_id'],
            'session_id'     => $sessionId,
            'term_id'        => $termId,
            'assigned_by'    => Auth::id(),
        ]);
        return back()->with('success', 'Class rep assigned.');
    }

    public function destroy(ClassRep $rep)
    {
        $rep->delete();
        return back()->with('success', 'Class rep removed.');
    }

    protected function arms()
    {
        return DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->selectRaw("schoolclass.id, TRIM(CONCAT(schoolclass.schoolclass,' ',COALESCE(schoolarm.arm,''))) as name")
            ->orderBy('schoolclass.schoolclass')->get();
    }
}
