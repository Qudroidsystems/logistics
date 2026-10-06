<?php

namespace App\Http\Controllers;

use App\Models\AttendanceHoliday;
use App\Models\AttendanceSummary;
use App\Models\AttendanceTermSetting;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\StudentAttendance;
use App\Models\Studentclass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View attendance-settings',   ['only' => ['index']]);
        $this->middleware('permission:Create attendance-settings', ['only' => ['store', 'update']]);
        $this->middleware('permission:Delete attendance-settings', ['only' => ['destroy']]);

        $this->middleware('permission:View attendance-holidays',   ['only' => ['holidays']]);
        $this->middleware('permission:Create attendance-holidays', ['only' => ['storeHoliday']]);
        $this->middleware('permission:Delete attendance-holidays', ['only' => ['destroyHoliday']]);

        $this->middleware('permission:View attendance-school-report', ['only' => ['schoolReport']]);
    }

    // =========================================================================
    // ADMIN – TERM SETTINGS
    // =========================================================================

    public function index()
    {
        $settings  = AttendanceTermSetting::with(['term', 'session', 'creator'])->latest()->get();
        $terms     = Schoolterm::all();
        $sessions  = Schoolsession::all();
        $holidays  = AttendanceHoliday::with(['term', 'session'])->latest()->get();
        $pagetitle = 'Attendance Settings';

        return view('attendance.admin.settings', compact('settings', 'terms', 'sessions', 'holidays', 'pagetitle'));
    }

    private function settingRules(): array
    {
        return [
            'term_id'            => 'required|exists:schoolterm,id',
            'session_id'         => 'required|exists:schoolsession,id',
            'resumption_date'    => 'required|date',
            'vacation_date'      => 'required|date|after:resumption_date',
            'resumption_time'    => 'required|date_format:H:i',
            'closing_time'       => 'required|date_format:H:i|after:resumption_time',
            'morning_end_time'   => 'required|date_format:H:i|after:resumption_time|before:closing_time',
            'late_grace_minutes' => 'nullable|integer|min:0|max:120',
            'track_morning'      => 'boolean',
            'track_afternoon'    => 'boolean',
            'allow_backdating'   => 'boolean',
            'backdate_days'      => 'nullable|integer|min:0|max:180',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->settingRules());

        $validated['created_by']         = Auth::id();
        $validated['track_morning']      = $request->boolean('track_morning', true);
        $validated['track_afternoon']    = $request->boolean('track_afternoon', false);
        $validated['allow_backdating']   = $request->boolean('allow_backdating', true);
        $validated['backdate_days']      = (int) ($request->input('backdate_days', 7));
        $validated['late_grace_minutes'] = (int) ($validated['late_grace_minutes'] ?? 0);

        try {
            $setting = AttendanceTermSetting::updateOrCreate(
                ['term_id' => $validated['term_id'], 'session_id' => $validated['session_id']],
                $validated
            );

            AttendanceTermSetting::forget();

            return response()->json([
                'success' => true,
                'message' => 'Term attendance setting saved.',
                'data'    => $setting,
            ]);
        } catch (\Exception $e) {
            Log::error('AttendanceSetting store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate($this->settingRules());

        $validated['track_morning']      = $request->boolean('track_morning', true);
        $validated['track_afternoon']    = $request->boolean('track_afternoon', false);
        $validated['allow_backdating']   = $request->boolean('allow_backdating', true);
        $validated['backdate_days']      = (int) ($request->input('backdate_days', 7));
        $validated['late_grace_minutes'] = (int) ($validated['late_grace_minutes'] ?? 0);

        try {
            $setting = AttendanceTermSetting::findOrFail($id);
            $setting->update($validated);

            AttendanceTermSetting::forget();

            return response()->json([
                'success' => true,
                'message' => 'Term setting updated successfully.',
                'data'    => $setting,
            ]);
        } catch (\Exception $e) {
            Log::error('AttendanceSetting update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        AttendanceTermSetting::findOrFail($id)->delete();
        AttendanceTermSetting::forget();
        return response()->json(['success' => true, 'message' => 'Setting deleted.']);
    }

    // =========================================================================
    // ADMIN – HOLIDAYS
    // =========================================================================

    public function holidays()
    {
        $holidays  = AttendanceHoliday::with(['term', 'session'])->latest()->get();
        $terms     = Schoolterm::all();
        $sessions  = Schoolsession::all();
        $pagetitle = 'Holidays & Breaks';

        return view('attendance.admin.holidays', compact('holidays', 'terms', 'sessions', 'pagetitle'));
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'term_id'          => 'required|exists:schoolterm,id',
            'session_id'       => 'required|exists:schoolsession,id',
            'holiday_date'     => 'required|date',
            'holiday_end_date' => 'nullable|date|after_or_equal:holiday_date',
            'holiday_name'     => 'required|string|max:200',
            'holiday_type'     => 'required|in:public,midterm,school_event,other',
            'notes'            => 'nullable|string|max:500',
        ]);

        $validated['created_by'] = Auth::id();

        $holiday = AttendanceHoliday::create($validated);
        return response()->json(['success' => true, 'message' => 'Holiday saved.', 'data' => $holiday]);
    }

    public function destroyHoliday($id)
    {
        AttendanceHoliday::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Holiday deleted.']);
    }

    // =========================================================================
    // ADMIN – SCHOOL-WIDE REPORT
    // =========================================================================

    public function schoolReport(Request $request)
    {
        $termId    = $request->input('term_id');
        $sessionId = $request->input('session_id');

        $summaries = collect();
        $classes   = collect();

        if ($termId && $sessionId) {
            $summaries = AttendanceSummary::where('term_id', $termId)
                ->where('session_id', $sessionId)
                ->with(['student', 'schoolclass'])
                ->get();

            $classes = $summaries->groupBy('schoolclass_id');
        }

        $terms     = Schoolterm::all();
        $sessions  = Schoolsession::all();
        $pagetitle = 'School Attendance Report';

        return view('attendance.admin.school-report', compact(
            'summaries', 'classes', 'terms', 'sessions',
            'pagetitle', 'termId', 'sessionId'
        ));
    }

    // =========================================================================
    // HELPER – rebuild summary for one student
    // =========================================================================

    public static function rebuildSummary(int $studentId, int $classId, int $termId, int $sessionId): void
    {
        $setting   = AttendanceTermSetting::where('term_id', $termId)->where('session_id', $sessionId)->first();
        $totalDays = $setting ? $setting->totalSchoolDays() : 0;

        $rows = StudentAttendance::where('student_id', $studentId)
            ->where('schoolclass_id', $classId)
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->selectRaw('status, COUNT(DISTINCT attendance_date) as day_count')
            ->groupBy('status')
            ->pluck('day_count', 'status');

        $present  = (int) ($rows['present']    ?? 0);
        $absent   = (int) ($rows['absent']     ?? 0);
        $sick     = (int) ($rows['sick_leave'] ?? 0);
        $excused  = (int) ($rows['excused']    ?? 0);
        $late     = (int) ($rows['late']       ?? 0);
        $attended = $present + $late;

        AttendanceSummary::updateOrCreate(
            [
                'student_id'     => $studentId,
                'schoolclass_id' => $classId,
                'term_id'        => $termId,
                'session_id'     => $sessionId,
            ],
            [
                'total_school_days'     => $totalDays,
                'days_present'          => $present,
                'days_absent'           => $absent,
                'days_sick_leave'       => $sick,
                'days_excused'          => $excused,
                'days_late'             => $late,
                'attendance_percentage' => $totalDays > 0 ? round(($attended / $totalDays) * 100, 2) : 0,
            ]
        );
    }
}