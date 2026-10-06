<?php

namespace App\Http\Controllers;

use App\Exports\StaffAttendanceExport;
use App\Models\AttendanceTermSetting;
use App\Models\DeviceOutageDate;
use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Staff attendance is purely device-driven — there is no manual register
 * UI. Read-only reporting on top of rows written by
 * DeviceAttendanceProcessor::processStaff(), plus admin management of
 * device-outage dates so outages don't get counted as mass absence.
 *
 * Lateness context (expected clock-in time, grace window, closing time)
 * comes from AttendanceTermSetting::current() and is passed into both
 * views so blades don't have to query it themselves.
 */
class StaffAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View staff-attendance-school-report', ['only' => ['index', 'exportExcel']]);
        $this->middleware('permission:View staff-attendance-report',        ['only' => ['report']]);
        $this->middleware('permission:Create device-outages',               ['only' => ['storeOutage']]);
        $this->middleware('permission:Delete device-outages',               ['only' => ['destroyOutage']]);
    }

    // =========================================================================
    // SCHOOL-WIDE STAFF ATTENDANCE SUMMARY
    // =========================================================================

    public function index(Request $request)
    {
        $data = $this->buildSchoolReportData($request);

        return view('attendance.admin.staff-school-report', $data + [
            'pagetitle'   => 'Staff Attendance Report',
            'timeContext' => $this->buildTimeContext(),
        ]);
    }

    // =========================================================================
    // EXCEL EXPORT
    // =========================================================================

    public function exportExcel(Request $request)
    {
        $data = $this->buildSchoolReportData($request);

        $filename = "staff-attendance-{$data['dateFrom']}-to-{$data['dateTo']}.xlsx";

        return Excel::download(
            new StaffAttendanceExport($data['rows'], $data['dateFrom'], $data['dateTo']),
            $filename
        );
    }

    /**
     * Shared by index() and exportExcel().
     */
    private function buildSchoolReportData(Request $request): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $excl        = $this->resolveExclusions($request, $dateFrom, $dateTo);
        $workingDays = $this->getWorkingDays($dateFrom, $dateTo, $excl['all']);

        $records = StaffAttendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->whereNotIn('attendance_date', $excl['all'])
            ->select('staff_id')
            ->selectRaw("COUNT(CASE WHEN status = 'present' THEN 1 END) as days_present")
            ->selectRaw("COUNT(CASE WHEN status = 'late' THEN 1 END) as days_late")
            ->selectRaw("COUNT(CASE WHEN status = 'excused' THEN 1 END) as days_excused")
            ->groupBy('staff_id')
            ->get()
            ->keyBy('staff_id');

        $staffList = Staff::with('user')->active()->orderBy('id')->get();

        $rows = $staffList->map(function ($staff) use ($records, $workingDays) {
            $r        = $records->get($staff->id);
            $present  = (int) ($r->days_present ?? 0);
            $late     = (int) ($r->days_late ?? 0);
            $excused  = (int) ($r->days_excused ?? 0);
            $attended = $present + $late + $excused;
            $absent   = max($workingDays - $attended, 0);

            return (object) [
                'staff_id'              => $staff->id,
                'full_name'             => $staff->full_name,
                'employmentid'          => $staff->employmentid,
                'department'            => $staff->department,
                'avatar_url'            => $staff->user?->avatar_url,
                'days_present'          => $present,
                'days_late'             => $late,
                'days_excused'          => $excused,
                'days_absent'           => $absent,
                'attendance_percentage' => $workingDays > 0 ? round(($attended / $workingDays) * 100, 2) : 0,
            ];
        });

        $avgPct = $rows->count() > 0 ? round($rows->avg('attendance_percentage'), 1) : 0;

        $outages = DeviceOutageDate::whereBetween('outage_date', [$dateFrom, $dateTo])
            ->orderByDesc('outage_date')
            ->get();

        return compact('rows', 'workingDays', 'avgPct', 'dateFrom', 'dateTo', 'outages')
            + ['excludedWeekdays' => $excl['weekdays']];
    }

    // =========================================================================
    // INDIVIDUAL STAFF DAILY LOG
    // =========================================================================

    public function report(Request $request, int $staffId)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $staff = Staff::with('user')->findOrFail($staffId);
        $excl  = $this->resolveExclusions($request, $dateFrom, $dateTo);

        $records = StaffAttendance::where('staff_id', $staffId)
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->whereNotIn('attendance_date', $excl['all'])
            ->orderBy('attendance_date')
            ->get();

        $workingDays = $this->getWorkingDays($dateFrom, $dateTo, $excl['all']);
        $present     = $records->where('status', 'present')->count();
        $late        = $records->where('status', 'late')->count();
        $excused     = $records->where('status', 'excused')->count();
        $attended    = $present + $late + $excused;
        $absent      = max($workingDays - $attended, 0);
        $pct         = $workingDays > 0 ? round(($attended / $workingDays) * 100, 2) : 0;

        $calendar = $this->buildCalendarWithRecords($dateFrom, $dateTo, $records, $excl);

        $pagetitle = "Attendance – {$staff->full_name}";

        return view('attendance.admin.staff-report', compact(
            'staff', 'records', 'calendar', 'workingDays',
            'present', 'late', 'excused', 'absent', 'pct',
            'dateFrom', 'dateTo', 'pagetitle'
        ) + [
            'excludedWeekdays' => $excl['weekdays'],
            'timeContext'      => $this->buildTimeContext(),
        ]);
    }

    // =========================================================================
    // DEVICE OUTAGE MANAGEMENT
    // =========================================================================

    public function storeOutage(Request $request)
    {
        $validated = $request->validate([
            'outage_date' => 'required|date',
            'reason'      => 'nullable|string|max:255',
        ]);
        $validated['marked_by'] = auth()->id();

        $outage = DeviceOutageDate::updateOrCreate(
            ['outage_date' => $validated['outage_date']],
            $validated
        );

        return response()->json(['success' => true, 'message' => 'Date marked as device outage.', 'data' => $outage]);
    }

    public function destroyOutage($id)
    {
        DeviceOutageDate::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Outage flag removed.']);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        try {
            $dateFrom = Carbon::parse($dateFrom)->toDateString();
            $dateTo   = Carbon::parse($dateTo)->toDateString();
        } catch (\Exception $e) {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo   = now()->toDateString();
        }

        return [$dateFrom, $dateTo];
    }

    private function getOutageDates(string $dateFrom, string $dateTo): Collection
    {
        return DeviceOutageDate::whereBetween('outage_date', [$dateFrom, $dateTo])
            ->pluck('outage_date')
            ->map(fn($d) => $d->toDateString());
    }

    private function resolveExcludedWeekdays(Request $request): Collection
    {
        if (!$request->has('weekday_filter_submitted')) {
            return collect([0, 6]);
        }

        return collect($request->input('excluded_weekdays', []))
            ->map(fn($d) => (int) $d)
            ->filter(fn($d) => $d >= 0 && $d <= 6)
            ->unique()
            ->values();
    }

    private function resolveExclusions(Request $request, string $dateFrom, string $dateTo): array
    {
        $deviceOutages    = $this->getOutageDates($dateFrom, $dateTo);
        $excludedWeekdays = $this->resolveExcludedWeekdays($request);

        $weekdayDates = collect();
        if ($excludedWeekdays->isNotEmpty()) {
            $cursor = Carbon::parse($dateFrom);
            $end    = Carbon::parse($dateTo);
            while ($cursor->lte($end)) {
                if ($excludedWeekdays->contains($cursor->dayOfWeek)) {
                    $weekdayDates->push($cursor->toDateString());
                }
                $cursor->addDay();
            }
        }

        $adHoc = collect($request->input('excluded_dates', []))
            ->filter()
            ->map(function ($d) {
                try {
                    return Carbon::parse($d)->toDateString();
                } catch (\Exception $e) {
                    return null;
                }
            })
            ->filter()
            ->filter(fn($d) => $d >= $dateFrom && $d <= $dateTo);

        $visible = $deviceOutages->merge($adHoc)->unique()->values();
        $all     = $visible->merge($weekdayDates)->unique()->values();
        $hidden  = $weekdayDates->diff($visible)->values();

        return [
            'all'      => $all,
            'visible'  => $visible,
            'hidden'   => $hidden,
            'outages'  => $deviceOutages,
            'weekdays' => $excludedWeekdays,
        ];
    }

    private function getWorkingDays(string $dateFrom, string $dateTo, ?Collection $excludedDates = null): int
    {
        $excludedDates ??= collect();

        $count   = 0;
        $current = Carbon::parse($dateFrom);
        $end     = Carbon::parse($dateTo);

        while ($current->lte($end)) {
            if ($current->lte(now()) && !$excludedDates->contains($current->toDateString())) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Builds the per-day calendar for the individual staff report.
     * Each day carries pre-computed display values so the blade does
     * zero date math:
     *
     *   'date'         — raw Y-m-d
     *   'label'        — "Mon, 08 Sep"
     *   'status'       — present | late | absent | excused | outage | excluded
     *   'time_in'      — "g:i A" (or null)
     *   'time_out'     — "g:i A" (or null)
     *   'expected_by'  — "g:i A" cutoff (null for outage/excluded)
     *   'minutes_late' — int (only when status === 'late')
     */
    private function buildCalendarWithRecords(string $dateFrom, string $dateTo, $records, array $excl): array
    {
        $byDate = $records->keyBy(fn($r) => Carbon::parse($r->attendance_date)->toDateString());

        $setting      = AttendanceTermSetting::current();
        $graceMinutes = (int) ($setting->late_grace_minutes ?? 0);

        $days    = [];
        $current = Carbon::parse($dateFrom);
        $end     = Carbon::parse($dateTo);

        while ($current->lte($end) && $current->lte(now())) {
            $key = $current->toDateString();

            if ($excl['hidden']->contains($key)) {
                $current->addDay();
                continue;
            }

            if ($excl['visible']->contains($key)) {
                $days[] = [
                    'date'         => $key,
                    'label'        => $current->format('D, d M'),
                    'status'       => $excl['outages']->contains($key) ? 'outage' : 'excluded',
                    'time_in'      => null,
                    'time_out'     => null,
                    'expected_by'  => null,
                    'minutes_late' => null,
                ];
            } else {
                $rec    = $byDate->get($key);
                $status = $rec->status ?? 'absent';

                $timeIn  = $rec?->time_in  ? Carbon::parse($rec->time_in)->format('g:i A')  : null;
                $timeOut = $rec?->time_out ? Carbon::parse($rec->time_out)->format('g:i A') : null;

                $expectedBy  = null;
                $minutesLate = null;

                if ($setting && $setting->resumption_time) {
                    $expected = $current->copy()
                        ->setTimeFromTimeString($setting->resumption_time)
                        ->addMinutes($graceMinutes);

                    $expectedBy = $expected->format('g:i A');

                    if ($status === 'late' && $rec?->time_in) {
                        $actual      = $current->copy()->setTimeFromTimeString(
                            Carbon::parse($rec->time_in)->format('H:i:s')
                        );
                        $minutesLate = max(0, $expected->diffInMinutes($actual, false));
                    }
                }

                $days[] = [
                    'date'         => $key,
                    'label'        => $current->format('D, d M'),
                    'status'       => $status,
                    'time_in'      => $timeIn,
                    'time_out'     => $timeOut,
                    'expected_by'  => $expectedBy,
                    'minutes_late' => $minutesLate,
                ];
            }

            $current->addDay();
        }

        return $days;
    }

    /**
     * One snapshot of the lateness context for the current term, used by
     * both staff blades to render the school-hours banner and "expected by"
     * text without each blade re-querying.
     */
    private function buildTimeContext(): ?array
    {
        $setting = AttendanceTermSetting::current();
        if (!$setting) {
            return null;
        }

        $graceMinutes = (int) ($setting->late_grace_minutes ?? 0);

        $resumption = $setting->resumption_time
            ? Carbon::parse($setting->resumption_time)
            : null;
        $closing = $setting->closing_time
            ? Carbon::parse($setting->closing_time)
            : null;
        $morningEnd = $setting->morning_end_time
            ? Carbon::parse($setting->morning_end_time)
            : null;

        return [
            'setting'           => $setting,
            'resumption_label'  => $resumption ? $resumption->format('g:i A')  : '8:00 AM',
            'closing_label'     => $closing    ? $closing->format('g:i A')     : '2:00 PM',
            'morning_end_label' => $morningEnd ? $morningEnd->format('g:i A')  : '12:00 PM',
            'grace_minutes'     => $graceMinutes,
            'expected_by'       => $resumption
                ? $resumption->copy()->addMinutes($graceMinutes)->format('g:i A')
                : '8:00 AM',
        ];
    }
}