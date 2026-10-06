<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentLeaveRequest;
use App\Services\Leave\StudentLeaveService;
use App\Services\Parents\ParentAccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentLeaveController extends Controller
{
    public function __construct(protected StudentLeaveService $svc)
    {
        $this->middleware('auth');
        $this->middleware('permission:Recommend student leave|Approve student leave|View student leave records')
            ->only(['approvals', 'act', 'records', 'exportRecords']);
    }

    // ── Student / parent side ────────────────────────────────────────────

    /** Students I may request leave for (myself, or my children). */
    protected function myStudents(Request $request): array
    {
        $u = $request->user();
        $out = [];
        if ($u->student_id) {
            $s = Student::find($u->student_id);
            if ($s) $out[$s->id] = $s;
        }
        foreach (ParentAccountService::children($u) as $c) {
            $s = Student::find($c->id);
            if ($s) $out[$s->id] = $s;
        }
        return $out;
    }

    public function mine(Request $request)
    {
        $students = $this->myStudents($request);
        abort_if(!$students, 403, 'This page is for students and parents.');
        $ids = array_keys($students);
        $requests = StudentLeaveRequest::with('student')->whereIn('student_id', $ids)->latest()->get();

        return view('student-leave.mine', [
            'pagetitle' => 'Leave of Absence',
            'students' => $students,
            'requests' => $requests,
            'reasons' => StudentLeaveRequest::REASONS,
        ]);
    }

    public function store(Request $request)
    {
        $students = $this->myStudents($request);
        $d = $request->validate([
            'student_id' => 'required|integer',
            'reason_type' => 'required|in:' . implode(',', array_keys(StudentLeaveRequest::REASONS)),
            'reason' => 'required|string|min:5|max:1000',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'contact_phone' => 'nullable|string|max:30',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        abort_unless(isset($students[(int) $d['student_id']]), 403, 'You cannot request leave for that student.');
        $student = $students[(int) $d['student_id']];

        if (Carbon::parse($d['end_date'])->diffInDays($d['start_date']) > 120) {
            return back()->withInput()->with('error', 'A single leave request cannot be longer than about 4 months.');
        }
        if ($this->svc->overlaps((int) $student->id, $d['start_date'], $d['end_date'])) {
            return back()->withInput()->with('error', 'This student already has leave requested or approved that overlaps those dates.');
        }
        if ($request->hasFile('attachment')) $d['attachment'] = $request->file('attachment')->store('student-leave', 'local');

        $type = $request->user()->student_id == $student->id ? 'student' : 'parent';
        $this->svc->submit($student, $d, (int) $request->user()->id, $type);

        return redirect()->route('student-leave.mine')->with('success', 'Leave request sent. You will be notified when the school decides.');
    }

    public function cancel(Request $request, StudentLeaveRequest $leave)
    {
        $students = $this->myStudents($request);
        abort_unless(isset($students[(int) $leave->student_id]) && $leave->isOpen(), 403);
        $leave->update(['status' => 'cancelled']);
        return back()->with('success', 'Leave request withdrawn.');
    }

    // ── Staff side: approvals & records ──────────────────────────────────

    public function approvals(Request $request)
    {
        $u = $request->user();
        $canApprove = $u->can('Approve student leave');
        $canRecommend = $u->can('Recommend student leave');

        // Class teacher: pending_teacher for classes they teach.
        $myClassIds = DB::table('classteacher')->where('staffid', $u->id)->pluck('schoolclassid')->all();

        $pending = StudentLeaveRequest::with('student')
            ->where(function ($q) use ($canApprove, $canRecommend, $myClassIds) {
                if ($canApprove) $q->orWhere('status', 'pending_principal');
                if ($canRecommend && $myClassIds) $q->orWhere(fn ($w) => $w->where('status', 'pending_teacher')->whereIn('class_id', $myClassIds));
            })
            ->orderBy('start_date')->get();

        $recent = StudentLeaveRequest::with('student')->whereIn('status', ['approved', 'rejected'])->latest('approved_at')->limit(20)->get();

        return view('student-leave.approvals', [
            'pagetitle' => 'Student Leave Approvals',
            'pending' => $pending, 'recent' => $recent,
            'classNames' => $this->classNames($pending->pluck('class_id')->merge($recent->pluck('class_id'))->all()),
            'canApprove' => $canApprove, 'canRecommend' => $canRecommend, 'myClassIds' => $myClassIds,
        ]);
    }

    public function act(Request $request, StudentLeaveRequest $leave)
    {
        $d = $request->validate(['decision' => 'required|in:yes,no', 'note' => 'nullable|string|max:500']);
        $u = $request->user();
        $ok = $d['decision'] === 'yes';

        if ($leave->status === 'pending_teacher') {
            $mine = DB::table('classteacher')->where('staffid', $u->id)->where('schoolclassid', $leave->class_id)->exists();
            abort_unless($u->can('Recommend student leave') && $mine, 403, 'Only this class\'s teacher can recommend.');
            $this->svc->recommend($leave, (int) $u->id, $ok, $d['note'] ?? null);
            return back()->with('success', $ok ? 'Recommended — sent to the principal.' : 'Marked as not recommended.');
        }

        if ($leave->status === 'pending_principal') {
            abort_unless($u->can('Approve student leave'), 403);
            $this->svc->decide($leave, (int) $u->id, $ok, $d['note'] ?? null);
            return back()->with('success', $ok ? 'Leave approved.' : 'Leave declined.');
        }

        return back()->with('error', 'This request has already been decided.');
    }

    public function records(Request $request)
    {
        $q = StudentLeaveRequest::with('student')
            ->when($request->filled('status'), fn ($x) => $x->where('status', $request->status))
            ->when($request->filled('reason'), fn ($x) => $x->where('reason_type', $request->reason))
            ->when($request->filled('from'), fn ($x) => $x->where('end_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($x) => $x->where('start_date', '<=', $request->to))
            ->when($request->filled('q'), fn ($x) => $x->whereIn('student_id',
                DB::table('studentRegistration')->where('firstname', 'like', "%{$request->q}%")->orWhere('lastname', 'like', "%{$request->q}%")->orWhere('admissionNo', 'like', "%{$request->q}%")->pluck('id')));

        return view('student-leave.records', [
            'pagetitle' => 'Student Leave Records',
            'rows' => $q->latest('start_date')->paginate(30)->withQueryString(),
            'classNames' => $this->classNames(StudentLeaveRequest::distinct()->pluck('class_id')->all()),
            'stats' => [
                'on_leave' => $this->svc->onLeave(now())->count(),
                'pending' => StudentLeaveRequest::whereIn('status', ['pending_teacher', 'pending_principal'])->count(),
                'approved_month' => StudentLeaveRequest::where('status', 'approved')->where('start_date', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    public function exportRecords(Request $request)
    {
        $rows = StudentLeaveRequest::with('student')
            ->when($request->filled('status'), fn ($x) => $x->where('status', $request->status))
            ->orderBy('start_date')->get();
        $names = $this->classNames($rows->pluck('class_id')->all());
        return response()->streamDownload(function () use ($rows, $names) {
            $o = fopen('php://output', 'w');
            fputcsv($o, ['Student', 'Admission No', 'Class', 'Reason', 'From', 'To', 'School days', 'Status', 'Decided by note']);
            foreach ($rows as $r) {
                $s = $r->student;
                fputcsv($o, [trim(($s->firstname ?? '') . ' ' . ($s->lastname ?? '')), $s->admissionNo ?? '', $names[$r->class_id] ?? '',
                    $r->reasonLabel(), $r->start_date->toDateString(), $r->end_date->toDateString(), $r->days, $r->label()[0], $r->approver_note]);
            }
            fclose($o);
        }, 'student-leave-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function attachment(Request $request, StudentLeaveRequest $leave)
    {
        $u = $request->user();
        $students = $this->myStudents($request);
        $allowed = isset($students[(int) $leave->student_id]) || $u->can('Approve student leave') || $u->can('View student leave records')
            || DB::table('classteacher')->where('staffid', $u->id)->where('schoolclassid', $leave->class_id)->exists();
        abort_unless($allowed && $leave->attachment && Storage::disk('local')->exists($leave->attachment), 403);
        return Storage::disk('local')->response($leave->attachment);
    }

    protected function classNames(array $ids): array
    {
        $ids = array_values(array_filter(array_unique($ids)));
        if (!$ids) return [];
        return DB::table('schoolclass as c')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->whereIn('c.id', $ids)
            ->selectRaw("c.id, TRIM(CONCAT(COALESCE(c.schoolclass,''),' ',COALESCE(a.arm,''))) as name")
            ->pluck('name', 'id')->all();
    }
}
