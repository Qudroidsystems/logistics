<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Schoolarm;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\TimetableReport;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TimetableReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View timetable reports|View own timetable reports', ['only' => ['index', 'show', 'download']]);
        $this->middleware('permission:Generate timetable reports|Generate own timetable reports', ['only' => ['generate']]);
        $this->middleware('permission:Delete timetable reports', ['only' => ['destroy']]);
    }

    public function index()
    {
        $pagetitle = 'Timetable Reports';
        $isWholeSchoolAccess = Auth::user()->can('View timetable reports');

        $reportsQuery = TimetableReport::with(['generator', 'session', 'term'])->orderByDesc('created_at');
        if (!$isWholeSchoolAccess) {
            $reportsQuery->where('generated_by', Auth::id());
        }
        $reports  = $reportsQuery->paginate(15);
        $sessions = Schoolsession::orderByDesc('id')->get();
        $terms    = Schoolterm::all();

        return view('timetable.reports', compact('pagetitle', 'reports', 'sessions', 'terms', 'isWholeSchoolAccess'));
    }

    // =========================================================================
    // GENERATE — always returns JSON. File downloads go through download()
    // as a separate GET request so the browser handles the binary response.
    // =========================================================================
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => 'required|in:teacher_workload,room_utilization,class_schedule,conflict_analysis,subject_distribution',
            'session_id'  => 'nullable|exists:schoolsession,id',
            'term_id'     => 'nullable|exists:schoolterm,id',
            'format'      => 'in:json,csv,pdf',
        ]);

        $isWholeSchoolAccess = Auth::user()->can('Generate timetable reports');

        // "Own" tier can only generate report types that make sense scoped to
        // a single teacher — everything else is inherently whole-school.
        if (!$isWholeSchoolAccess && !in_array($validated['report_type'], ['teacher_workload', 'class_schedule'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to generate this report type. Contact an administrator for school-wide reports.',
            ], 403);
        }

        $sessionId = $validated['session_id']
            ?? Schoolsession::where('status', 'Current')->value('id')
            ?? Schoolsession::latest('id')->value('id');

        if (!$sessionId) {
            return response()->json(['success' => false, 'message' => 'No session found. Please create a session first.'], 422);
        }

        $termId        = $validated['term_id'] ?? null;
        $onlyTeacherId = $isWholeSchoolAccess ? null : Auth::id();

        $data    = $this->generateReportData($validated['report_type'], (int) $sessionId, $termId, $onlyTeacherId);
        $summary = $this->computeReportSummary($validated['report_type'], $data);

        $reportName = ucfirst(str_replace('_', ' ', $validated['report_type'])) . ' Report — ' . now()->format('Y-m-d H:i');

        $report = TimetableReport::create([
            'report_name'  => $reportName,
            'report_type'  => $validated['report_type'],
            'session_id'   => $sessionId,
            'term_id'      => $termId,
            'filters'      => $validated,
            'data'         => $data,
            'generated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'report'  => $report,
            'data'    => $data,
            'summary' => $summary,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $report = TimetableReport::with(['generator', 'session', 'term'])->findOrFail($id);

        if (!Auth::user()->can('View timetable reports') && $report->generated_by !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view this report.'], 403);
        }

        $summary = $this->computeReportSummary($report->report_type, $report->data ?? []);

        return response()->json(['success' => true, 'report' => $report, 'summary' => $summary]);
    }

    // =========================================================================
    // DOWNLOAD — the only action that returns a raw file.
    // =========================================================================
    public function download(Request $request, int $id)
    {
        $report = TimetableReport::findOrFail($id);

        if (!Auth::user()->can('View timetable reports') && $report->generated_by !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to download this report.'], 403);
        }

        $format = $request->query('format', 'csv');
        $data   = $report->data ?? [];

        if ($format === 'pdf') {
            return $this->exportToPdf($report, $data);
        }

        if ($report->file_path && Storage::exists($report->file_path)) {
            return Storage::download($report->file_path, $this->safeFilename($report->report_name) . '.csv');
        }

        return $this->exportToCsv($report, $data);
    }

    public function destroy(int $id): JsonResponse
    {
        $report = TimetableReport::findOrFail($id);

        if ($report->file_path && Storage::exists($report->file_path)) {
            Storage::delete($report->file_path);
        }

        $report->delete();

        return response()->json(['success' => true, 'message' => 'Report deleted successfully']);
    }

    // =========================================================================
    // REPORT DATA GENERATORS
    // =========================================================================

    private function generateReportData(string $reportType, int $sessionId, ?int $termId, ?int $onlyTeacherId = null): array
    {
        return match ($reportType) {
            'teacher_workload'     => $this->getTeacherWorkloadReport($sessionId, $termId, $onlyTeacherId),
            'room_utilization'     => $this->getRoomUtilizationReport($sessionId, $termId),
            'class_schedule'       => $this->getClassScheduleReport($sessionId, $termId, $onlyTeacherId),
            'conflict_analysis'    => $this->getConflictAnalysisReport($sessionId, $termId),
            'subject_distribution' => $this->getSubjectDistributionReport($sessionId, $termId),
            default                => [],
        };
    }

    private function scopeSettingQuery($query, int $sessionId, ?int $termId)
    {
        return $query->where('session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('term_id', $termId));
    }

    /**
     * Distinct teacher IDs actually scheduled in this session/term.
     * Fallback only — used when no user holds the 'Teacher' role yet.
     */
    private function scheduledTeacherIds(int $sessionId, ?int $termId)
    {
        return TimetableSlot::whereNotNull('teacher_id')
            ->whereNotNull('subject_id')
            ->whereHas('setting', fn($q) => $this->scopeSettingQuery($q, $sessionId, $termId))
            ->distinct()
            ->pluck('teacher_id');
    }

    // =========================================================================
    // CLASS NAME (schoolclass + arm) — mirrors TimetableController's helper
    // so every report shows the arm (e.g. "JSS 1A"), not just the class.
    // =========================================================================
    private function resolveArmName($schoolclass): string
    {
        if (!$schoolclass) return '';
        if (!empty($schoolclass->arm_name)) return ' ' . $schoolclass->arm_name;

        $arm = $schoolclass->armRelation ?? null;
        if (is_object($arm) && isset($arm->arm)) return ' ' . $arm->arm;

        if (is_string($schoolclass->arm ?? null) && !is_numeric($schoolclass->arm)) return ' ' . $schoolclass->arm;

        if (is_numeric($schoolclass->arm ?? null)) {
            $armModel = Schoolarm::find($schoolclass->arm);
            if ($armModel?->arm) return ' ' . $armModel->arm;
        }

        return '';
    }

    private function getClassName($schoolclass): string
    {
        if (!$schoolclass) return 'Unknown Class';
        return ($schoolclass->schoolclass ?? '') . $this->resolveArmName($schoolclass);
    }

    private function getTeacherWorkloadReport(int $sessionId, ?int $termId, ?int $onlyTeacherId = null): array
    {
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'Teacher'))
            ->when($onlyTeacherId, fn($q) => $q->where('id', $onlyTeacherId))
            ->get();

        if ($teachers->isEmpty()) {
            $ids = $this->scheduledTeacherIds($sessionId, $termId);
            if ($onlyTeacherId) $ids = $ids->filter(fn($id) => $id == $onlyTeacherId);
            $teachers = User::whereIn('id', $ids)->get();
        }

        $report = [];

        foreach ($teachers as $teacher) {
            $slots = TimetableSlot::where('teacher_id', $teacher->id)
                ->whereNotNull('subject_id')
                ->whereHas('setting', fn($q) => $this->scopeSettingQuery($q, $sessionId, $termId))
                ->with(['period', 'subject', 'setting.schoolclass.armRelation'])
                ->get();

            $dailyDistribution = [];
            foreach (TimetableController::DAYS as $day) {
                $dailyDistribution[$day] = $slots->where('day', $day)->count();
            }

            $report[] = [
                'teacher_name'    => $teacher->name,
                'teacher_email'   => $teacher->email,
                'total_periods'   => $slots->count(),
                'monday'          => $dailyDistribution['Monday'],
                'tuesday'         => $dailyDistribution['Tuesday'],
                'wednesday'       => $dailyDistribution['Wednesday'],
                'thursday'        => $dailyDistribution['Thursday'],
                'friday'          => $dailyDistribution['Friday'],
                'subjects_taught' => $slots->pluck('subject.subject')->filter()->unique()->implode(', '),
                'classes_taught'  => $slots->pluck('setting.schoolclass')->filter()->unique('id')->map(fn($c) => $this->getClassName($c))->implode(', '),
                'total_classes'   => $slots->pluck('setting.schoolclass_id')->unique()->count(),
            ];
        }

        usort($report, fn($a, $b) => $b['total_periods'] <=> $a['total_periods']);

        return $report;
    }

    private function getRoomUtilizationReport(int $sessionId, ?int $termId): array
    {
        $rooms = Room::where('is_active', true)->get();

        if ($rooms->isEmpty()) {
            $roomIds = TimetableSlot::whereNotNull('room_id')
                ->whereNotNull('subject_id')
                ->whereHas('setting', fn($q) => $this->scopeSettingQuery($q, $sessionId, $termId))
                ->distinct()
                ->pluck('room_id');
            $rooms = Room::whereIn('id', $roomIds)->get();
        }

        $report = [];

        $maxLessonSlotsPerDay = TimetableSetting::query()
            ->where('session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('term_id', $termId))
            ->withCount(['periods' => fn($q) => $q->where('type', 'lesson')])
            ->get()
            ->max('periods_count') ?: 8;

        foreach ($rooms as $room) {
            $bookings = TimetableSlot::where('room_id', $room->id)
                ->whereNotNull('subject_id')
                ->whereHas('setting', fn($q) => $this->scopeSettingQuery($q, $sessionId, $termId))
                ->get();

            $utilizationByDay = [];
            foreach (TimetableController::DAYS as $day) {
                $count = $bookings->where('day', $day)->count();
                $utilizationByDay[$day] = [
                    'count'      => $count,
                    'percentage' => $maxLessonSlotsPerDay > 0 ? min(100, round(($count / $maxLessonSlotsPerDay) * 100)) : 0,
                ];
            }

            $avgUtil = $bookings->count() > 0
                ? round(collect($utilizationByDay)->avg('percentage'), 1)
                : 0;

            $report[] = [
                'room_name'           => $room->room_name,
                'room_code'           => $room->room_code,
                'type'                => $room->type,
                'capacity'            => $room->capacity,
                'total_bookings'      => $bookings->count(),
                'monday_count'        => $utilizationByDay['Monday']['count'],
                'tuesday_count'       => $utilizationByDay['Tuesday']['count'],
                'wednesday_count'     => $utilizationByDay['Wednesday']['count'],
                'thursday_count'      => $utilizationByDay['Thursday']['count'],
                'friday_count'        => $utilizationByDay['Friday']['count'],
                'average_utilization' => $avgUtil . '%',
            ];
        }

        usort($report, fn($a, $b) => $b['total_bookings'] <=> $a['total_bookings']);

        return $report;
    }

    private function getClassScheduleReport(int $sessionId, ?int $termId, ?int $onlyTeacherId = null): array
    {
        $settingsQuery = TimetableSetting::where('session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('term_id', $termId))
            ->where('is_active', true);

        if ($onlyTeacherId) {
            $settingsQuery->whereIn('id', TimetableSlot::where('teacher_id', $onlyTeacherId)
                ->whereNotNull('subject_id')
                ->pluck('setting_id')
                ->unique());
        }

        $settings = $settingsQuery->with(['schoolclass.armRelation', 'session', 'term', 'periods', 'slots.subject', 'slots.teacher'])->get();

        $report = [];
        foreach ($settings as $setting) {
            $totalLessonSlots = $setting->periods->where('type', 'lesson')->count() * count($setting->active_days ?? TimetableController::DAYS);
            $filledSlots      = $setting->slots->whereNotNull('subject_id')->count();

            $report[] = [
                'class'              => $this->getClassName($setting->schoolclass),
                'session'            => $setting->session->session ?? '',
                'term'               => $setting->term->term ?? 'All Terms',
                'total_lesson_slots' => $totalLessonSlots,
                'filled_slots'       => $filledSlots,
                'free_slots'         => max(0, $totalLessonSlots - $filledSlots),
                'completion_percent' => $totalLessonSlots > 0
                    ? round(($filledSlots / $totalLessonSlots) * 100, 1) . '%'
                    : '0%',
                'subjects_count'     => $setting->slots->pluck('subject_id')->filter()->unique()->count(),
                'teachers_count'     => $setting->slots->pluck('teacher_id')->filter()->unique()->count(),
            ];
        }

        return $report;
    }

    private function getConflictAnalysisReport(int $sessionId, ?int $termId): array
    {
        $slots = TimetableSlot::whereNotNull('teacher_id')
            ->whereNotNull('subject_id')
            ->where('is_free', false)
            ->whereHas('setting', fn($q) => $this->scopeSettingQuery($q, $sessionId, $termId))
            ->with(['period', 'teacher', 'setting.schoolclass.armRelation', 'subject'])
            ->get();

        $conflicts      = [];
        $teacherSlotMap = [];

        foreach ($slots as $slot) {
            $key = $slot->teacher_id . '_' . $slot->day . '_' . $slot->period_id;
            if (isset($teacherSlotMap[$key])) {
                $conflicts[] = [
                    'teacher'       => $slot->teacher->name ?? 'Unknown',
                    'teacher_email' => $slot->teacher->email ?? '',
                    'day'           => $slot->day,
                    'period'        => $slot->period->name ?? '',
                    'period_time'   => substr($slot->period->start_time ?? '', 0, 5) . ' - ' . substr($slot->period->end_time ?? '', 0, 5),
                    'class_a'       => $this->getClassName($teacherSlotMap[$key]->setting->schoolclass),
                    'subject_a'     => $teacherSlotMap[$key]->subject->subject ?? '',
                    'class_b'       => $this->getClassName($slot->setting->schoolclass),
                    'subject_b'     => $slot->subject->subject ?? '',
                ];
            } else {
                $teacherSlotMap[$key] = $slot;
            }
        }

        return [
            'summary' => [
                'total_conflicts'   => count($conflicts),
                'affected_teachers' => collect($conflicts)->pluck('teacher')->unique()->count(),
                'affected_days'     => collect($conflicts)->pluck('day')->unique()->implode(', ') ?: '—',
            ],
            'conflicts' => $conflicts,
        ];
    }

    private function getSubjectDistributionReport(int $sessionId, ?int $termId): array
    {
        $slots = TimetableSlot::whereNotNull('subject_id')
            ->whereHas('setting', fn($q) => $this->scopeSettingQuery($q, $sessionId, $termId))
            ->with(['subject', 'setting.schoolclass.armRelation'])
            ->get();

        $subjectCount = [];
        foreach ($slots as $slot) {
            $subjectName = $slot->subject->subject ?? 'Unknown';
            $className   = $this->getClassName($slot->setting->schoolclass);
            $key         = $subjectName . '||' . $className;

            $subjectCount[$key] = ($subjectCount[$key] ?? 0) + 1;
        }

        $report = [];
        foreach ($subjectCount as $key => $count) {
            [$subject, $class] = explode('||', $key, 2);
            $report[] = [
                'subject'          => $subject,
                'class'            => $class,
                'periods_per_week' => $count,
            ];
        }

        usort($report, fn($a, $b) => strcmp($a['subject'], $b['subject']) ?: strcmp($a['class'], $b['class']));

        return $report;
    }

    // =========================================================================
    // SUMMARY STATS
    // =========================================================================
    private function computeReportSummary(string $reportType, $data): array
    {
        $data = is_array($data) ? $data : [];

        return match ($reportType) {
            'teacher_workload' => [
                ['label' => 'Teachers',         'value' => count($data)],
                ['label' => 'Avg Periods/Week', 'value' => count($data) ? round(collect($data)->avg('total_periods'), 1) : 0],
                ['label' => 'Highest Load',     'value' => count($data) ? collect($data)->max('total_periods') : 0],
            ],
            'room_utilization' => [
                ['label' => 'Rooms',            'value' => count($data)],
                ['label' => 'Avg Utilization',  'value' => count($data) ? round(collect($data)->avg(fn($r) => (float) rtrim($r['average_utilization'], '%')), 1) . '%' : '0%'],
                ['label' => 'Total Bookings',   'value' => collect($data)->sum('total_bookings')],
            ],
            'class_schedule' => [
                ['label' => 'Classes',          'value' => count($data)],
                ['label' => 'Avg Completion',   'value' => count($data) ? round(collect($data)->avg(fn($r) => (float) rtrim($r['completion_percent'], '%')), 1) . '%' : '0%'],
                ['label' => 'Total Free Slots', 'value' => collect($data)->sum('free_slots')],
            ],
            'conflict_analysis' => [
                ['label' => 'Total Conflicts',   'value' => $data['summary']['total_conflicts'] ?? 0],
                ['label' => 'Affected Teachers', 'value' => $data['summary']['affected_teachers'] ?? 0],
                ['label' => 'Affected Days',     'value' => $data['summary']['affected_days'] ?? '—'],
            ],
            'subject_distribution' => [
                ['label' => 'Subject-Class Pairs', 'value' => count($data)],
                ['label' => 'Unique Subjects',      'value' => collect($data)->pluck('subject')->unique()->count()],
                ['label' => 'Unique Classes',       'value' => collect($data)->pluck('class')->unique()->count()],
            ],
            default => [],
        };
    }

    // =========================================================================
    // EXPORT HELPERS
    // =========================================================================

    private function safeFilename(string $name): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_\-]+/', '_', str_replace(['—', '/'], '-', $name)), '_');
    }

    private function exportToCsv(TimetableReport $report, $data)
    {
        $filename = $this->safeFilename($report->report_name) . '.csv';
        $flatData = $this->flattenForCsv($data, $report->report_type);

        $handle = fopen('php://temp', 'w+');

        if (!empty($flatData)) {
            fputcsv($handle, array_keys($flatData[0]));
            foreach ($flatData as $row) {
                fputcsv($handle, array_map(fn($v) => is_array($v) ? implode(', ', $v) : $v, $row));
            }
        } else {
            fputcsv($handle, ['message']);
            fputcsv($handle, ['No data available for this report and scope.']);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filePath = 'reports/' . $filename;
        Storage::put($filePath, $csv);
        $report->update(['file_path' => $filePath]);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function exportToPdf(TimetableReport $report, $data)
    {
        $flatData   = $this->flattenForCsv($data, $report->report_type);
        $summary    = $this->computeReportSummary($report->report_type, $data);
        $schoolInfo = SchoolInformation::getActiveSchool();

        $viewData = [
            'report'      => $report,
            'flatData'    => $flatData,
            'summary'     => $summary,
            'schoolInfo'  => $schoolInfo,
            'generatedAt' => now()->format('d M Y, H:i'),
        ];

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return response()->view('timetable.exports.report-pdf', $viewData);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('timetable.exports.report-pdf', $viewData)
            ->setPaper('a4', 'portrait');

        return $pdf->download($this->safeFilename($report->report_name) . '.pdf');
    }

    private function flattenForCsv($data, string $reportType): array
    {
        if (empty($data)) return [];

        if ($reportType === 'conflict_analysis') {
            return $data['conflicts'] ?? [];
        }

        return is_array($data) ? $data : [];
    }
}