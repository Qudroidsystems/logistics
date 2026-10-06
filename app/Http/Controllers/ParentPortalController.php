<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ViewStudentReportController;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Services\Billing\StudentFeeStatementService;
use App\Services\Parents\ParentAccountService;
use App\Services\ResultAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

/**
 * Parent portal: one login, all children — results, fees (pay online),
 * attendance, timetable and notices.
 */
class ParentPortalController extends Controller
{
    public function __construct(protected StudentFeeStatementService $statements) {}

    // ── Dashboard ───────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        $user     = $request->user();
        $children = ParentAccountService::children($user);
        $cards    = $children->map(fn ($c) => $this->summary($c));

        return view('parent.dashboard', [
            'pagetitle' => 'Parent Portal',
            'user'      => $user,
            'cards'     => $cards,
            'notices'   => Schema::hasTable('notifications') ? $user->notifications()->limit(6)->get() : collect(),
        ]);
    }

    protected function summary(object $c): object
    {
        $student = Student::find($c->id);
        $termId = (int) $c->term_id; $sessionId = (int) $c->session_id;

        $balance = null;
        try {
            $st = $this->statements->buildStatement($student, $termId ?: null, $sessionId ?: null);
            $balance = (float) ($st['totals']['outstanding'] ?? 0) + (float) ($st['arrears']['total_arrears'] ?? 0);
        } catch (\Throwable $e) {}

        $att = DB::table('student_attendance')->where('student_id', $c->id)->where('term_id', $termId)->where('session_id', $sessionId)
            ->selectRaw("SUM(status IN ('present','late')) as present, COUNT(*) as total, SUM(status = 'absent') as absent")->first();

        $latest = DB::table('broadsheet_records as br')->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->where('br.student_id', $c->id)->orderByDesc('br.session_id')->orderByDesc('b.term_id')
            ->first(['br.session_id', 'b.term_id']);

        $c->balance    = $balance;
        $c->attendance = $att && $att->total ? round($att->present / $att->total * 100) : null;
        $c->absent     = (int) ($att->absent ?? 0);
        $c->latest     = $latest ? (Schoolterm::where('id', $latest->term_id)->value('term') . ' · ' . Schoolsession::where('id', $latest->session_id)->value('session')) : null;
        $c->photo      = $c->picture ? asset('storage/student_avatars/' . basename($c->picture)) : null;
        return $c;
    }

    // ── Results ─────────────────────────────────────────────────────────

    public function results(Request $request, int $student)
    {
        $child = $this->child($request, $student);

        $terms = DB::table('broadsheet_records as br')->join('broadsheets as b', 'b.broadsheet_record_id', '=', 'br.id')
            ->join('schoolterm as t', 't.id', '=', 'b.term_id')->join('schoolsession as ss', 'ss.id', '=', 'br.session_id')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'br.schoolclass_id')->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->where('br.student_id', $student)
            ->groupBy('br.session_id', 'b.term_id', 'br.schoolclass_id', 't.term', 'ss.session', 'c.schoolclass', 'a.arm')
            ->orderByDesc('br.session_id')->orderByDesc('b.term_id')
            ->get(['br.session_id', 'b.term_id', 'br.schoolclass_id as class_id', 't.term', 'ss.session',
                   DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_name"),
                   DB::raw('COUNT(*) as subjects'), DB::raw('SUM(b.vettedstatus = 1) as vetted'),
                   DB::raw('ROUND(AVG(b.total), 1) as average')]);

        $access = app(ResultAccessService::class);
        $terms = $terms->map(function ($t) use ($access, $student) {
            $chk = $access->check($student, (int) $t->term_id, (int) $t->session_id, false);
            $t->allowed = !empty($chk['allowed']);
            $t->hold_message = $chk['message'] ?? null;
            $t->not_released = ($chk['reason'] ?? '') === 'not_released';
            $t->owed = (float) ($chk['owed'] ?? 0);
            $t->ready = (int) $t->vetted >= (int) $t->subjects;
            return $t;
        });

        $selected = $terms->first(fn ($t) => $t->term_id == $request->get('term_id') && $t->session_id == $request->get('session_id')) ?? $terms->first();
        $scores = collect();
        if ($selected && $selected->allowed) {
            $scores = DB::table('broadsheets as b')->join('broadsheet_records as br', 'br.id', '=', 'b.broadsheet_record_id')
                ->join('subject as sj', 'sj.id', '=', 'br.subject_id')
                ->where('br.student_id', $student)->where('br.session_id', $selected->session_id)
                ->where('br.schoolclass_id', $selected->class_id)->where('b.term_id', $selected->term_id)
                ->orderBy('sj.subject')
                ->get(['sj.subject', 'b.total', 'b.grade', 'b.remark', 'b.subject_position_class as position', 'b.avg as class_average']);
        }

        return view('parent.results', [
            'pagetitle' => 'Results — ' . $child->firstname,
            'child' => $child, 'children' => ParentAccountService::children($request->user()),
            'terms' => $terms, 'selected' => $selected, 'scores' => $scores,
        ]);
    }

    public function reportCard(Request $request, int $student, int $session, int $term, int $class)
    {
        $this->child($request, $student);
        $chk = app(ResultAccessService::class)->check($student, $term, $session, false);
        abort_unless(!empty($chk['allowed']), 403, $chk['message'] ?? 'This result is on hold.');

        $pdf = app(ViewStudentReportController::class)->renderReportCardPdf($student, $class, $session, $term);
        abort_unless($pdf, 404, 'The report card could not be generated.');

        $s = Student::find($student);
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', ($s->firstname ?? '') . '_' . ($s->lastname ?? '')) ?: 'student';
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Report_Card_' . $name . '.pdf"',
        ]);
    }

    // ── Fees ────────────────────────────────────────────────────────────

    public function fees(Request $request, int $student)
    {
        $child = $this->child($request, $student);
        [$sessions, $terms] = $this->periods($student);
        $sessionId = (int) ($request->get('session_id') ?: ($child->session_id ?: ($sessions->first()->id ?? 0)));
        $termId    = (int) ($request->get('term_id') ?: ($child->term_id ?: ($terms->first()->id ?? 0)));

        $statement = $this->statements->buildStatement(Student::find($student), $termId ?: null, $sessionId ?: null);

        return view('parent.fees', [
            'pagetitle' => 'Fees — ' . $child->firstname,
            'child' => $child, 'children' => ParentAccountService::children($request->user()),
            'sessions' => $sessions, 'terms' => $terms, 'sessionId' => $sessionId, 'termId' => $termId,
            's' => $statement,
        ]);
    }

    // ── Attendance ──────────────────────────────────────────────────────

    public function attendance(Request $request, int $student)
    {
        $child = $this->child($request, $student);
        [$sessions, $terms] = $this->periods($student);
        $sessionId = (int) ($request->get('session_id') ?: ($child->session_id ?: ($sessions->first()->id ?? 0)));
        $termId    = (int) ($request->get('term_id') ?: ($child->term_id ?: ($terms->first()->id ?? 0)));

        $rows = DB::table('student_attendance')->where('student_id', $student)
            ->where('term_id', $termId)->where('session_id', $sessionId)
            ->orderByDesc('attendance_date')->orderBy('period')
            ->get(['attendance_date', 'period', 'status', 'notes']);

        $days = $rows->groupBy(fn ($r) => (string) $r->attendance_date);
        $counts = $rows->countBy('status');

        return view('parent.attendance', [
            'pagetitle' => 'Attendance — ' . $child->firstname,
            'child' => $child, 'children' => ParentAccountService::children($request->user()),
            'sessions' => $sessions, 'terms' => $terms, 'sessionId' => $sessionId, 'termId' => $termId,
            'days' => $days, 'counts' => $counts, 'total' => $rows->count(),
        ]);
    }

    // ── Timetable ───────────────────────────────────────────────────────

    public function timetable(Request $request, int $student)
    {
        $child = $this->child($request, $student);
        $setting = null; $periods = collect(); $grid = []; $days = [];

        if ($child->class_id && Schema::hasTable('timetable_settings')) {
            $q = DB::table('timetable_settings')->where('schoolclass_id', $child->class_id)->where('is_active', 1);
            if ($child->session_id) $q->where('session_id', $child->session_id);
            if (Schema::hasColumn('timetable_settings', 'is_published')) $q->orderByDesc('is_published');
            $setting = $q->orderByDesc('id')->first();

            if ($setting && (!Schema::hasColumn('timetable_settings', 'is_published') || $setting->is_published)) {
                $periods = DB::table('timetable_periods')->where('setting_id', $setting->id)->orderBy('order')->get();
                $days = json_decode($setting->active_days ?? '[]', true) ?: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                $slots = DB::table('timetable_slots as ts')
                    ->leftJoin('subject as sj', 'sj.id', '=', 'ts.subject_id')
                    ->leftJoin('users as u', 'u.id', '=', 'ts.teacher_id')
                    ->where('ts.setting_id', $setting->id)
                    ->get(['ts.day', 'ts.period_id', 'ts.is_free', 'sj.subject', 'u.name as teacher']);
                foreach ($slots as $s) $grid[$s->day][$s->period_id] = $s;
            } else {
                $setting = null;
            }
        }

        return view('parent.timetable', [
            'pagetitle' => 'Timetable — ' . $child->firstname,
            'child' => $child, 'children' => ParentAccountService::children($request->user()),
            'setting' => $setting, 'periods' => $periods, 'grid' => $grid, 'days' => $days,
        ]);
    }

    // ── Password ────────────────────────────────────────────────────────

    public function passwordForm(Request $request)
    {
        return view('parent.password', ['pagetitle' => 'Change password', 'forced' => (bool) $request->user()->must_change_password]);
    }

    public function passwordUpdate(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'current_password' => $user->must_change_password ? 'nullable' : 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $user->forceFill(['password' => Hash::make($request->password), 'must_change_password' => false])->save();

        $home = $user->hasRole(ParentAccountService::ROLE) && !$user->can('dashboard') ? 'parent.dashboard' : 'dashboard';
        return redirect()->route($home)->with('success', 'Password changed.');
    }

    // ── helpers ─────────────────────────────────────────────────────────

    protected function child(Request $request, int $studentId): object
    {
        $child = ParentAccountService::children($request->user())->firstWhere('id', $studentId);
        abort_unless($child, 403, 'This student is not linked to your account.');
        return $child;
    }

    protected function periods(int $studentId): array
    {
        $ids = collect()
            ->merge(DB::table('studentclass')->where('studentId', $studentId)->pluck('sessionid'))
            ->merge(DB::table('student_bill_payment_book')->where('student_id', $studentId)->pluck('session_id'))
            ->merge(DB::table('broadsheet_records')->where('student_id', $studentId)->pluck('session_id'))
            ->merge(Schoolsession::where('status', 'Current')->pluck('id'))
            ->map(fn ($v) => (int) $v)->filter()->unique();

        return [
            Schoolsession::whereIn('id', $ids)->orderByDesc('id')->get(['id', 'session', 'status']),
            Schoolterm::orderBy('id')->get(['id', 'term']),
        ];
    }
}
