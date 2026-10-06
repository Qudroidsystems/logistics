<?php

namespace App\Http\Controllers;

use App\Mail\TimetableNotificationMail;
use App\Models\CompulsorySubjectClass;
use App\Models\Holiday;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\RoomClassSubject;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subject;
use App\Models\Subjectclass;
use App\Models\SubjectTeacher;
use App\Models\SubjectRegistrationStatus;
use App\Models\SubstituteAssignment;
use App\Models\TeacherAvailability;
use App\Models\TimetableConstraint;
use App\Models\TimetableNotification;
use App\Models\TimetablePeriod;
use App\Models\TimetablePeriodLimit;
use App\Models\TimetablePeriodAllocation;
use App\Models\TimetablePeriodAllocationSet;
use App\Models\TimetableReport;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use App\Models\TimetableSubjectPriority;
use App\Models\User;
use App\Models\Schoolarm;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\TimetableGenerationRun;
use App\Models\TimetableGenerationRunSetting;

class TimetableController extends Controller
{
    const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    const DAYS_MAP = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5];

    const DAY_COLORS = [
        'Monday'    => '#1565C0',
        'Tuesday'   => '#6A1B9A',
        'Wednesday' => '#1B5E20',
        'Thursday'  => '#E65100',
        'Friday'    => '#880E4F',
    ];

    const SUBJECT_PALETTE = [
        '#DBEAFE','#D1FAE5','#FEF3C7','#FCE7F3','#E0E7FF',
        '#DCFCE7','#FEE2E2','#EDE9FE','#F0F9FF','#FFF7ED',
    ];

    const PAPER_SIZES = [
        'a0','a1','a2','a3','a4','a5','a6','a7','a8','a9','a10',
        'b0','b1','b2','b3','b4','b5','b6','b7','b8','b9','b10',
        'c0','c1','c2','c3','c4','c5','c6','c7','c8','c9','c10',
        'ra0','ra1','ra2','ra3','ra4',
        'sra0','sra1','sra2','sra3','sra4',
        'letter','legal','ledger','tabloid','executive','folio',
    ];

    const PAPER_LABELS = [
        'a0' => ['A0', '841 × 1189 mm', 'Wall chart — entire school'],
        'a1' => ['A1', '594 × 841 mm',  'Poster — whole-school master'],
        'a2' => ['A2', '420 × 594 mm',  'Large — several classes at once'],
        'a3' => ['A3', '297 × 420 mm',  'Standard — recommended'],
        'a4' => ['A4', '210 × 297 mm',  'Small — single class only'],
        'a5' => ['A5', '148 × 210 mm',  'Pocket'],
        'b0' => ['B0', '1000 × 1414 mm','Oversized poster'],
        'b1' => ['B1', '707 × 1000 mm', 'Large poster'],
        'b2' => ['B2', '500 × 707 mm',  'Medium poster'],
        'b3' => ['B3', '353 × 500 mm',  'Between A4 and A3'],
        'b4' => ['B4', '250 × 353 mm',  'Slightly larger than A4'],
        'letter'    => ['Letter',    '8.5 × 11 in',   'US standard'],
        'legal'     => ['Legal',     '8.5 × 14 in',   'US legal'],
        'ledger'    => ['Ledger',    '11 × 17 in',    'US tabloid landscape'],
        'tabloid'   => ['Tabloid',   '11 × 17 in',    'US tabloid portrait'],
        'executive' => ['Executive', '7.25 × 10.5 in','US executive'],
        'folio'     => ['Folio',     '8.5 × 13 in',   'US folio'],
    ];

    const DEFAULT_PAPER = 'a3';

    const PAPER_BODY_SCALE = [
        'a0' => 2.0, 'a1' => 1.6, 'a2' => 1.3,
        'b0' => 2.0, 'b1' => 1.6, 'b2' => 1.3,
    ];

    const EDITING_LOCK_TTL_MINUTES = 3;
    const PREVIEW_TTL_MINUTES      = 30;

    public function __construct()
    {
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:View timetable|Create timetable|Edit timetable|Delete timetable|Generate timetable', ['only' => ['index', 'getSetting', 'getGrid', 'heartbeat', 'releaseEditing', 'getSavedTimetables']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Create timetable', ['only' => ['setup', 'saveSettings']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Edit timetable', ['only' => ['saveSlot', 'bulkUpdateSlots', 'cloneSetting', 'resolveConflict']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Delete timetable', ['only' => ['deleteSetting']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Generate timetable', ['only' => ['autoGenerate', 'autoGenerateWholeSchool', 'applyGenerationTemplate', 'getTeacherAssignments', 'getGenerationWizardData', 'previewGeneration']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:View my timetable', ['only' => ['teacherView', 'exportTeacherTimetable']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Manage timetable settings', ['only' => ['saveSettings', 'rebuildPeriodsFromAnchors', 'saveHalfDays', 'saveFreePeriods']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Manage timetable constraints', ['only' => ['saveConstraints', 'getPeriodAllocationGrid', 'listPeriodAllocationSets', 'getPeriodAllocationSetDetail', 'savePeriodAllocationSet', 'deletePeriodAllocationSet']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:View timetable reports', ['only' => ['workloadDashboard', 'generateAnalytics']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Export timetable', ['only' => ['export', 'exportWholeSchool', 'exportWholeSchoolWeb', 'exportMergedGrid', 'mergedGridWeb']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Request substitute', ['only' => ['requestSubstitute']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Approve substitute', ['only' => ['approveSubstitute']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:View substitute requests', ['only' => ['getSubstituteRequests']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Manage teacher availability', ['only' => ['saveTeacherAvailability', 'getTeacherAvailability']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Check timetable conflicts', ['only' => ['checkConflicts', 'checkConflictsScope']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Send timetable notifications', ['only' => ['sendNotifications', 'publishAndNotify']]);
        // [TEMP-DISABLED-FOR-TESTING] $this->middleware('permission:Publish timetable', ['only' => ['publishSetting', 'unpublishSetting', 'publishAndNotify', 'publishAndSaveSnapshot']]);
    }

    // =========================================================================
    // PAPER-SIZE HELPERS
    // =========================================================================
    private function resolvePaper(?string $size, string $orientation): array
    {
        $size      = strtolower($size ?? '');
        $size      = in_array($size, self::PAPER_SIZES, true) ? $size : self::DEFAULT_PAPER;
        $direction = $orientation === 'vertical' ? 'portrait' : 'landscape';
        return [$size, $direction];
    }

    private function paperBodyScale(string $paperSize): float
    {
        return self::PAPER_BODY_SCALE[strtolower($paperSize)] ?? 1.0;
    }

    // =========================================================================
    // TIME / ARM HELPERS
    // =========================================================================
    private function formatTime(?string $time): string
    {
        if (!$time) return '';
        return substr($time, 0, 5);
    }

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

    private function isCombinedSession($a, $b): bool
    {
        return $a && $b
            && $a->teacher_id && $b->teacher_id && $a->teacher_id == $b->teacher_id
            && $a->subject_id && $b->subject_id && $a->subject_id == $b->subject_id
            && $a->room_id && $b->room_id && $a->room_id == $b->room_id;
    }

    private function periodTimeSignature(?TimetablePeriod $period): ?string
    {
        if (!$period || !$period->start_time || !$period->end_time) return null;
        return substr($period->start_time, 0, 5) . '-' . substr($period->end_time, 0, 5);
    }

    private function crossSettingSlotsAtTime(TimetablePeriod $period, string $day, int $sessionId, ?int $termId, ?int $excludeSettingId = null)
    {
        return TimetableSlot::query()
            ->join('timetable_periods', 'timetable_periods.id', '=', 'timetable_slots.period_id')
            ->where('timetable_slots.day', $day)
            ->whereRaw('substr(timetable_periods.start_time, 1, 5) = ?', [substr($period->start_time, 0, 5)])
            ->whereRaw('substr(timetable_periods.end_time, 1, 5) = ?', [substr($period->end_time, 0, 5)])
            ->where('timetable_slots.is_free', false)
            ->whereNotNull('timetable_slots.subject_id')
            ->when($excludeSettingId, fn($q) => $q->where('timetable_slots.setting_id', '!=', $excludeSettingId))
            ->whereHas('setting', function ($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
                else         $q->whereNull('term_id');
            })
            ->select('timetable_slots.*');
    }

    private function buildSuggestionText(string $teacherName, string $className, array $alternatives): string
    {
        if (empty($alternatives)) {
            return "No free slots found for {$teacherName} in {$className}. Consider reviewing the schedule or assigning a substitute.";
        }
        $top  = $alternatives[0];
        $more = count($alternatives) > 1 ? ' (' . (count($alternatives) - 1) . ' more available)' : '';
        return "Suggested: Move to {$top['day']}, {$top['period_name']} ({$top['period_time']}){$more}.";
    }

    private function findAlternativeSlots($teacherId, $currentPeriodId, $currentDay, $currentSetting): array
    {
        $alternatives = [];
        $settingId    = $currentSetting->id;
        $sessionId    = $currentSetting->session_id;
        $termId       = $currentSetting->term_id;
        $days         = $currentSetting->active_days ?? self::DAYS;

        $periods = TimetablePeriod::where('setting_id', $settingId)
            ->where('type', 'lesson')
            ->orderBy('order')
            ->get();

        $teacherBusyKeys = TimetableSlot::whereHas('setting', function($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
                else         $q->whereNull('term_id');
            })
            ->where('teacher_id', $teacherId)
            ->where('is_free', false)
            ->whereNotNull('subject_id')
            ->with('period:id,start_time,end_time')
            ->get(['day', 'period_id'])
            ->filter(fn($s) => $s->period)
            ->mapWithKeys(fn($s) => [$s->day . '|' . $this->periodTimeSignature($s->period) => true]);

        foreach ($days as $day) {
            foreach ($periods as $period) {
                if ($day === $currentDay && $period->id == $currentPeriodId) continue;
                if ($teacherBusyKeys->has($day . '|' . $this->periodTimeSignature($period))) continue;
                $slot       = TimetableSlot::where('setting_id', $settingId)
                    ->where('period_id', $period->id)->where('day', $day)->first();
                $isSlotFree = !$slot || $slot->is_free || !$slot->subject_id;
                if ($isSlotFree) {
                    $alternatives[] = [
                        'day'         => $day,
                        'period_id'   => $period->id,
                        'period_name' => $period->name,
                        'period_time' => $this->formatTime($period->start_time) . ' – ' . $this->formatTime($period->end_time),
                        'is_available'=> true,
                    ];
                    if (count($alternatives) >= 5) break 2;
                }
            }
        }
        return $alternatives;
    }

    private function findAlternativeRooms($excludeRoomId, $periodId, $day, $sessionId, $termId): array
    {
        $period = TimetablePeriod::find($periodId);
        if (!$period) return [];

        return Room::where('is_active', true)
            ->when($excludeRoomId, fn($q) => $q->where('id', '!=', $excludeRoomId))
            ->whereNotIn('id', function($q) use ($period, $day, $sessionId, $termId) {
                $q->select('timetable_slots.room_id')
                  ->from('timetable_slots')
                  ->join('timetable_periods', 'timetable_periods.id', '=', 'timetable_slots.period_id')
                  ->where('timetable_slots.day', $day)
                  ->whereRaw('substr(timetable_periods.start_time, 1, 5) = ?', [substr($period->start_time, 0, 5)])
                  ->whereRaw('substr(timetable_periods.end_time, 1, 5) = ?', [substr($period->end_time, 0, 5)])
                  ->whereNotNull('timetable_slots.room_id')
                  ->where('timetable_slots.is_free', false)
                  ->whereIn('timetable_slots.setting_id', function($q2) use ($sessionId, $termId) {
                      $q2->select('id')->from('timetable_settings')
                         ->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                      if ($termId) $q2->where('term_id', $termId);
                  });
            })
            ->limit(5)->get(['id', 'room_name', 'room_code', 'type', 'capacity'])
            ->map(fn($r) => [
                'id'       => $r->id,
                'label'    => $r->room_name
                            . ($r->room_code ? ' (' . $r->room_code . ')' : '')
                            . ($r->capacity  ? ' · ' . $r->capacity . ' seats' : ''),
                'type'     => $r->type,
                'capacity' => $r->capacity,
            ])->toArray();
    }

    private function publishedLockResponse(TimetableSetting $setting): ?JsonResponse
    {
        if (!$setting->is_published) return null;

        return response()->json([
            'success'      => false,
            'is_locked'    => true,
            'message'      => 'This timetable is published and locked. Unpublish it first to make changes — teachers will need to be re-notified afterward.',
            'published_at' => optional($setting->published_at)->format('d M Y, H:i'),
            'published_by' => $setting->publisher?->name,
        ], 423);
    }

    private function versionConflictResponse(TimetableSetting $setting, ?string $expectedUpdatedAt): ?JsonResponse
    {
        if (!$expectedUpdatedAt) return null;
        if ($setting->updated_at->eq(Carbon::parse($expectedUpdatedAt))) return null;

        return response()->json([
            'success'              => false,
            'has_version_conflict' => true,
            'message'              => 'This timetable was changed by ' . ($setting->updater?->name ?? 'someone else')
                . ' at ' . $setting->updated_at->format('d M Y, H:i:s') . '. Reload to see the latest version.',
            'current_updated_at'   => $setting->updated_at->toISOString(),
        ], 409);
    }

    private function editingRecentlyResponse(TimetableSetting $setting, string $actionMessage): ?JsonResponse
    {
        if (!$setting->editing_by || $setting->editing_by == Auth::id()) return null;
        if (!$setting->editing_at || $setting->editing_at->diffInMinutes(now()) > self::EDITING_LOCK_TTL_MINUTES) return null;

        return response()->json([
            'success'         => false,
            'is_being_edited' => true,
            'message'         => ($setting->editor?->name ?? 'Someone') . ' is currently editing this timetable (started '
                . $setting->editing_at->diffForHumans() . "). {$actionMessage}",
        ], 409);
    }

    private function getHolidayForDate(Carbon $date, ?int $sessionId, ?int $termId = null): ?Holiday
    {
        return Holiday::whereDate('date', $date->toDateString())
            ->where(function ($q) use ($sessionId) {
                $q->whereNull('session_id')->orWhere('session_id', $sessionId);
            })
            ->where(function ($q) use ($termId) {
                $q->whereNull('term_id')->orWhere('term_id', $termId);
            })
            ->orderByDesc('is_full_day')
            ->first();
    }

    // =========================================================================
    // ICS CALENDAR FEED
    // =========================================================================
    public function exportIcs(int $teacherId)
    {
        $teacher = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->findOrFail($teacherId);

        $sessionId = Schoolsession::where('status', 'Current')->value('id')
            ?? Schoolsession::latest('id')->value('id');
        $session = Schoolsession::findOrFail($sessionId);

        $slots = TimetableSlot::where('teacher_id', $teacher->id)
            ->whereNotNull('subject_id')
            ->whereHas('setting', fn($q) => $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false))
            ->with(['period', 'subject', 'setting.schoolclass', 'room'])
            ->get();

        $ics = $this->buildIcsFeed($teacher, $session, $slots);

        return response($ics, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="timetable-' . $teacher->id . '.ics"')
            ->header('Cache-Control', 'no-cache');
    }

    private function buildIcsFeed(User $teacher, Schoolsession $session, $slots): string
    {
        $tz        = config('app.timezone', 'UTC');
        $startDate = now()->startOfWeek(Carbon::MONDAY);
        $endDate   = $startDate->copy()->addMonths(18);

        $byDayCode = ['Monday'=>'MO','Tuesday'=>'TU','Wednesday'=>'WE','Thursday'=>'TH','Friday'=>'FR','Saturday'=>'SA','Sunday'=>'SU'];

        $holidays = Holiday::where('is_full_day', true)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->where(fn($q) => $q->whereNull('session_id')->orWhere('session_id', $session->id))
            ->get();

        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0',
            'PRODID:-//' . config('app.name', 'School') . '//Timetable//EN',
            'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            $this->icsFold('X-WR-CALNAME:' . $this->icsEscape(($teacher->name ?? 'My') . ' Timetable')),
            'X-WR-TIMEZONE:' . $tz,
            'REFRESH-INTERVAL;VALUE=DURATION:PT12H',
            'X-PUBLISHED-TTL:PT12H',
        ];

        foreach ($slots as $slot) {
            $day     = $slot->day;
            $dayCode = $byDayCode[$day] ?? null;
            if (!$dayCode || !$slot->period) continue;

            $first = $startDate->copy();
            while ($first->format('l') !== $day) $first->addDay();

            [$sh, $sm] = explode(':', substr($slot->period->start_time, 0, 5));
            [$eh, $em] = explode(':', substr($slot->period->end_time, 0, 5));

            $dtStart = $first->copy()->setTime((int) $sh, (int) $sm);
            $dtEnd   = $first->copy()->setTime((int) $eh, (int) $em);

            $className = $this->getClassName($slot->setting?->schoolclass);
            $roomName  = $slot->room?->room_name;

            $descParts = array_filter([
                $className ? "Class: {$className}" : null,
                $roomName  ? "Room: {$roomName}"    : null,
                $slot->is_double ? 'Double period'  : null,
            ]);

            $exdates = [];
            foreach ($holidays as $h) {
                $hDate = Carbon::parse($h->date);
                if ($hDate->format('l') === $day && $hDate->gte($first)) {
                    $exdates[] = $hDate->copy()->setTime((int) $sh, (int) $sm)->format('Ymd\THis');
                }
            }

            $uid = 'slot-' . $slot->id . '-' . $session->id . '@' . parse_url(config('app.url'), PHP_URL_HOST);

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;TZID=' . $tz . ':' . $dtStart->format('Ymd\THis');
            $lines[] = 'DTEND;TZID=' . $tz . ':' . $dtEnd->format('Ymd\THis');
            $lines[] = $this->icsFold('RRULE:FREQ=WEEKLY;BYDAY=' . $dayCode . ';UNTIL=' . $endDate->copy()->utc()->format('Ymd\THis\Z'));
            if ($exdates) $lines[] = $this->icsFold('EXDATE;TZID=' . $tz . ':' . implode(',', $exdates));
            $lines[] = $this->icsFold('SUMMARY:' . $this->icsEscape(($slot->subject?->subject ?? 'Class') . ' — ' . $className));
            if ($descParts) $lines[] = $this->icsFold('DESCRIPTION:' . $this->icsEscape(implode('\n', $descParts)));
            if ($roomName)  $lines[] = $this->icsFold('LOCATION:' . $this->icsEscape($roomName));
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines) . "\r\n";
    }

    private function icsEscape(string $text): string
    {
        return str_replace(["\\", ",", ";", "\n"], ["\\\\", "\\,", "\\;", "\\n"], $text);
    }

    private function icsFold(string $line): string
    {
        $folded = '';
        while (mb_strlen($line, '8bit') > 75) {
            $folded .= mb_strcut($line, 0, 75, '8bit') . "\r\n ";
            $line = mb_strcut($line, 75, null, '8bit');
        }
        return $folded . $line;
    }

    // =========================================================================
    // DAY/PERIOD META HELPERS
    // =========================================================================
    private function computeDayPeriodMeta(TimetableSetting $setting): array
    {
        $days        = $setting->active_days ?? self::DAYS;
        $periods     = $setting->periods->sortBy('order')->values();
        $halfDays    = $setting->half_days ?? [];
        $assemblyDay = $setting->assembly_day;

        $meta = [];
        foreach ($days as $day) {
            $teachingIndex = 0;
            $cutoff        = $halfDays[$day] ?? null;

            foreach ($periods as $period) {
                $effectiveType = $period->type;
                if ($period->type === 'assembly') {
                    $effectiveType = ($day === $assemblyDay) ? 'assembly' : 'lesson';
                }

                $applicable = true;
                if ($effectiveType === 'lesson') {
                    $teachingIndex++;
                    if ($cutoff && $teachingIndex > $cutoff) $applicable = false;
                }

                $meta[$day][$period->id] = ['applicable' => $applicable, 'effective_type' => $effectiveType];
            }
        }
        return $meta;
    }

    private function isBreakAdjacent(TimetablePeriod $period, $allPeriods): bool
    {
        $ordered = $allPeriods->sortBy('order')->values();
        $idx     = $ordered->search(fn($p) => $p->id === $period->id);
        if ($idx === false) return false;

        $isNonLesson = fn($p) => $p && in_array($p->type, ['short_break', 'long_break', 'assembly']);
        return $isNonLesson($ordered->get($idx - 1)) || $isNonLesson($ordered->get($idx + 1));
    }

    private function buildPeriodsFromTemplate(array $template): array
    {
        $periods = [];
        $lessonCount     = (int) $template['lessons_per_day'];
        $shortBreakAfter = $template['short_break_after'] ?? null;
        $longBreakAfter  = $template['long_break_after']  ?? null;
        $hasAssembly     = !empty($template['assembly_first_period']);

        $lessonNumber = 0;
        if ($hasAssembly) {
            $periods[] = ['name' => 'Assembly', 'type' => 'assembly'];
        }
        for ($i = 1; $i <= $lessonCount; $i++) {
            $lessonNumber++;
            $periods[] = ['name' => "Period {$lessonNumber}", 'type' => 'lesson'];
            if ($shortBreakAfter && $i == $shortBreakAfter) {
                $periods[] = ['name' => 'Short Break', 'type' => 'short_break'];
            }
            if ($longBreakAfter && $i == $longBreakAfter) {
                $periods[] = ['name' => 'Long Break', 'type' => 'long_break'];
            }
        }
        return $periods;
    }

    // =========================================================================
    // ADVANCED RULES + MORNING CUTOFF
    // =========================================================================
    private function resolveAdvancedRules(TimetableSetting $setting): array
    {
        $stored = $setting->advanced_rules ?? [];
        return [
            'cap_mode'             => $stored['cap_mode']            ?? 'hard',
            'morning_cutoff'       => $stored['morning_cutoff']      ?? 'half',
            'morning_cutoff_n'     => $stored['morning_cutoff_n']    ?? 3,
            'protected_mode'       => $stored['protected_mode']      ?? 'drop_unprotected',
            'strict_room_mapping'  => (bool) ($stored['strict_room_mapping'] ?? false),
            'strict_room_mode'     => $stored['strict_room_mode']    ?? 'teacher_only',
            'priorities_active'    => (bool) ($stored['priorities_active'] ?? true),
            'join_double_periods'  => (bool) ($stored['join_double_periods'] ?? true),
        ];
    }

    private function resolveMorningCutoff(array $rules, int $lessonCount): int
    {
        if ($rules['morning_cutoff'] === 'fixed') {
            return max(1, min($lessonCount, (int) $rules['morning_cutoff_n']));
        }
        return (int) max(1, ceil($lessonCount / 2));
    }

    // =========================================================================
    // STRICT ROOM MAP
    // =========================================================================
    private function buildStrictRoomMap(int $classId, int $sessionId, ?int $termId): array
    {
        $rows = RoomClassSubject::with('room')
            ->where('schoolclass_id', $classId)
            ->where('session_id', $sessionId)
            ->where(function ($q) use ($termId) {
                $q->whereNull('term_id');
                if ($termId) $q->orWhere('term_id', $termId);
            })
            ->whereHas('room', fn($q) => $q->where('is_active', true))
            ->get();

        $map = ['*' => []];
        foreach ($rows as $row) {
            $key = $row->subject_id ?? '*';
            $map[$key][] = $row->room_id;
        }
        return $map;
    }

    // =========================================================================
    // ROOM PICKER
    // =========================================================================
    private function pickRoomForLesson(
        bool $includeRooms,
        array $strictRoomMap,
        array $availableRoomIds,
        int $subjectId,
        string $day,
        string $timeSig,
        array &$roomOccupied
    ): array {
        if (!$includeRooms) return ['room_id' => null, 'no_mapping' => false];

        if (!empty($strictRoomMap)) {
            $candidates = $strictRoomMap[$subjectId] ?? $strictRoomMap['*'] ?? [];
            if (empty($candidates)) {
                return ['room_id' => null, 'no_mapping' => true];
            }
            foreach ($candidates as $roomId) {
                if (!in_array($timeSig, $roomOccupied[$roomId][$day] ?? [])) {
                    return ['room_id' => $roomId, 'no_mapping' => false];
                }
            }
            return ['room_id' => null, 'no_mapping' => false];
        }

        foreach ($availableRoomIds as $roomId) {
            if (!in_array($timeSig, $roomOccupied[$roomId][$day] ?? [])) {
                return ['room_id' => $roomId, 'no_mapping' => false];
            }
        }
        return ['room_id' => null, 'no_mapping' => false];
    }

    // =========================================================================
    // PERIOD LIMITS
    // =========================================================================
    private function loadPeriodLimits(int $sessionId, ?int $termId): array
    {
        $rows = TimetablePeriodLimit::forScope($sessionId, $termId)->get();

        $out = [
            'teacher_total' => [],
            'teacher_class' => [],
            'teacher_day'   => [],
            'class_total'   => [],
        ];

        foreach ($rows as $row) {
            switch ($row->scope) {
                case 'teacher_total':
                    $out['teacher_total'][$row->teacher_id] = $row->max_periods;
                    break;
                case 'teacher_class':
                    $out['teacher_class'][$row->teacher_id . ':' . $row->schoolclass_id] = $row->max_periods;
                    break;
                case 'teacher_day':
                    $out['teacher_day'][$row->teacher_id . ':' . $row->day] = $row->max_periods;
                    break;
                case 'class_total':
                    $out['class_total'][$row->schoolclass_id] = $row->max_periods;
                    break;
            }
        }
        return $out;
    }

    private function passesPeriodLimits(
        array $limits,
        int $teacherId,
        int $classId,
        string $day,
        array $teacherWeekTotal,
        array $teacherClassTotal,
        array $teacherDayTotal,
        int $classWeekTotal,
        string $capMode
    ): bool {
        if ($capMode === 'soft') return true;

        if (isset($limits['teacher_total'][$teacherId])
            && ($teacherWeekTotal[$teacherId] ?? 0) >= $limits['teacher_total'][$teacherId]) {
            return false;
        }
        if (isset($limits['teacher_class'][$teacherId . ':' . $classId])
            && ($teacherClassTotal[$teacherId . ':' . $classId] ?? 0) >= $limits['teacher_class'][$teacherId . ':' . $classId]) {
            return false;
        }
        if (isset($limits['teacher_day'][$teacherId . ':' . $day])
            && ($teacherDayTotal[$teacherId . ':' . $day] ?? 0) >= $limits['teacher_day'][$teacherId . ':' . $day]) {
            return false;
        }
        if (isset($limits['class_total'][$classId])
            && $classWeekTotal >= $limits['class_total'][$classId]) {
            return false;
        }
        return true;
    }

    private function softCapPenalty(
        array $limits,
        int $teacherId,
        int $classId,
        string $day,
        array $teacherWeekTotal,
        array $teacherClassTotal,
        array $teacherDayTotal,
        int $classWeekTotal
    ): int {
        $penalty = 0;

        if (isset($limits['teacher_total'][$teacherId])) {
            $over = (($teacherWeekTotal[$teacherId] ?? 0) + 1) - $limits['teacher_total'][$teacherId];
            if ($over > 0) $penalty += 40 * $over;
        }
        if (isset($limits['teacher_class'][$teacherId . ':' . $classId])) {
            $over = (($teacherClassTotal[$teacherId . ':' . $classId] ?? 0) + 1) - $limits['teacher_class'][$teacherId . ':' . $classId];
            if ($over > 0) $penalty += 30 * $over;
        }
        if (isset($limits['teacher_day'][$teacherId . ':' . $day])) {
            $over = (($teacherDayTotal[$teacherId . ':' . $day] ?? 0) + 1) - $limits['teacher_day'][$teacherId . ':' . $day];
            if ($over > 0) $penalty += 20 * $over;
        }
        if (isset($limits['class_total'][$classId])) {
            $over = ($classWeekTotal + 1) - $limits['class_total'][$classId];
            if ($over > 0) $penalty += 25 * $over;
        }
        return $penalty;
    }

    // =========================================================================
    // INDEX
    // =========================================================================
    public function index()
    {
        $pagetitle = 'Timetable Management';

        $this->sweepExpiredPreviews();

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')->get();

        $schoolsessions = Schoolsession::orderByDesc('id')->get();
        $schoolterms    = Schoolterm::all();

        $subjectsWithTeachers = SubjectTeacher::with(['subject', 'staff'])->get()
            ->map(fn($st) => [
                'subject_id'   => $st->subjectid,
                'subject_name' => $st->subject->subject ?? 'Unknown',
                'teacher_id'   => $st->staffid,
                'teacher_name' => $st->staff->name ?? 'Unknown',
            ]);

        $settings = TimetableSetting::with(['session', 'term', 'creator', 'updater'])
            ->join('schoolclass', 'schoolclass.id', '=', 'timetable_settings.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select([
                'timetable_settings.*',
                'schoolclass.schoolclass as _class_name',
                'schoolarm.arm as _arm_name',
            ])
            ->where('timetable_settings.is_active', true)
            ->where('timetable_settings.is_preview', false)
            ->orderByDesc('timetable_settings.updated_at')
            ->get()
            ->each(function ($s) {
                $s->resolved_class_name = trim(($s->_class_name ?? '') . ' ' . ($s->_arm_name ?? ''));
            });

        return view('timetable.index', compact(
            'pagetitle', 'schoolclasses', 'schoolsessions', 'schoolterms', 'settings', 'subjectsWithTeachers'
        ));
    }

    // =========================================================================
    // EDITING PRESENCE
    // =========================================================================
    public function heartbeat(int $settingId): JsonResponse
    {
        DB::table('timetable_settings')->where('id', $settingId)->update([
            'editing_by' => Auth::id(),
            'editing_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function releaseEditing(int $settingId): JsonResponse
    {
        DB::table('timetable_settings')->where('id', $settingId)
            ->where('editing_by', Auth::id())
            ->update(['editing_by' => null, 'editing_at' => null]);
        return response()->json(['success' => true]);
    }

    // =========================================================================
    // GET SETTING
    // =========================================================================
    public function getSetting(int $settingId): JsonResponse
    {
        $setting = TimetableSetting::with(['periods', 'constraints.subject', 'session', 'term', 'editor', 'subjectPriorities.subject'])->findOrFail($settingId);

        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.id', $setting->schoolclass_id)->first();
        $setting->setRelation('schoolclass', $schoolclass);

        $editingInfo = null;
        if ($setting->editing_by && $setting->editing_by != Auth::id() && $setting->editing_at
            && $setting->editing_at->diffInMinutes(now()) <= self::EDITING_LOCK_TTL_MINUTES) {
            $editingInfo = [
                'user_name' => $setting->editor?->name ?? 'Another user',
                'since'     => $setting->editing_at->diffForHumans(),
            ];
        }

        DB::table('timetable_settings')->where('id', $setting->id)->update([
            'editing_by' => Auth::id(),
            'editing_at' => now(),
        ]);

        $availableSubjects = SubjectTeacher::where('sessionid', $setting->session_id)
            ->when($setting->term_id, fn($q) => $q->where('termid', $setting->term_id))
            ->whereHas('subjectclass', fn($q) => $q->where('schoolclassid', $setting->schoolclass_id))
            ->with(['subject', 'staff'])
            ->get()
            ->map(fn($st) => [
                'subject_id'   => $st->subjectid,
                'subject_name' => $st->subject->subject ?? 'Unknown',
                'subject_code' => $st->subject->subject_code ?? '',
                'teacher_id'   => $st->staffid,
                'teacher_name' => $st->staff->name ?? 'Unknown',
                'term_name'    => $setting->term?->term ?? 'All Terms',
            ]);

        return response()->json([
            'success'            => true,
            'setting'            => $setting,
            'available_subjects' => $availableSubjects,
            'editing_info'       => $editingInfo,
        ]);
    }

    // =========================================================================
    // GET GRID
    // =========================================================================
    public function getGrid(int $settingId): JsonResponse
    {
        $setting = TimetableSetting::with(['periods', 'session', 'term'])->findOrFail($settingId);

        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.id', $setting->schoolclass_id)->first();
        $setting->setRelation('schoolclass', $schoolclass);

        $payload = $this->buildGridPayloadForSetting($setting);

        $allTeachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->with('staffPicture')->get()
            ->map(fn($t) => [
                'id'      => $t->id,
                'name'    => $t->name,
                'email'   => $t->email,
                'picture' => $t->staffPicture
                    ? asset('storage/staff_avatars/' . $t->staffPicture->picture)
                    : asset('storage/staff_avatars/default.png'),
            ]);

        $rooms = Room::where('is_active', true)->orderBy('room_name')
            ->get(['id', 'room_code', 'room_name', 'type', 'capacity'])
            ->map(fn($r) => [
                'id'       => $r->id,
                'value'    => $r->id,
                'name'     => $r->room_name,
                'code'     => $r->room_code,
                'type'     => $r->type,
                'capacity' => $r->capacity,
                'label'    => trim($r->room_name
                    . ($r->room_code ? ' (' . $r->room_code . ')' : '')
                    . ($r->capacity  ? ' · ' . $r->capacity . ' seats' : '')),
            ]);

        $todayDayName = date('l');
        $todayHoliday = in_array($todayDayName, self::DAYS, true)
            ? $this->getHolidayForDate(now(), $setting->session_id, $setting->term_id)
            : null;

        return response()->json(array_merge($payload, [
            'success'         => true,
            'setting'         => $setting,
            'teachers'        => $allTeachers,
            'rooms'           => $rooms,
            'day_period_meta' => $this->computeDayPeriodMeta($setting),
            'today_day_name'  => $todayDayName,
            'today_holiday'   => $todayHoliday ? [
                'title'       => $todayHoliday->title,
                'is_full_day' => (bool) $todayHoliday->is_full_day,
                'cutoff_time' => $todayHoliday->cutoff_time,
            ] : null,
        ]));
    }

    private function buildGridPayloadForSetting(TimetableSetting $setting): array
    {
        $slots = TimetableSlot::where('setting_id', $setting->id)
            ->with(['subject', 'teacher', 'teacher.staffPicture', 'period', 'room'])
            ->get();

        $grid = [];
        foreach ($slots as $slot) {
            $teacherPicture = null;
            if ($slot->teacher && $slot->teacher->staffPicture) {
                $teacherPicture = asset('storage/staff_avatars/' . $slot->teacher->staffPicture->picture);
            }
            $grid[$slot->period_id][$slot->day] = [
                'id'              => $slot->id,
                'subject_id'      => $slot->subject_id,
                'subject'         => $slot->subject?->subject,
                'subject_code'    => $slot->subject?->subject_code,
                'teacher_id'      => $slot->teacher_id,
                'teacher'         => $slot->teacher?->name,
                'teacher_picture' => $teacherPicture,
                'room_id'         => $slot->room_id,
                'room_name'       => $slot->room_id ? ($slot->room?->room_name ?? '') : '',
                'room_code'       => $slot->room_id ? ($slot->room?->room_code ?? '') : '',
                'is_double'       => $slot->is_double,
                'is_free'         => $slot->is_free,
                'notes'           => $slot->notes,
            ];
        }

        return [
            'periods'    => $setting->periods,
            'grid'       => $grid,
            'days'       => $setting->active_days ?? self::DAYS,
            'class_name' => $this->getClassName($setting->schoolclass),
        ];
    }

    // =========================================================================
    // SETUP
    // =========================================================================
    public function setup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'session_id'     => 'required|exists:schoolsession,id',
            'term_id'        => 'nullable|exists:schoolterm,id',
        ]);

        $setting = TimetableSetting::firstOrCreate(
            array_filter($validated),
            [
                'school_day_start'             => '08:00',
                'school_day_end'               => '14:30',
                'period_duration_minutes'      => 40,
                'short_break_duration_minutes' => 20,
                'long_break_duration_minutes'  => 40,
                'is_active'                    => true,
                'active_days'                  => self::DAYS,
                'created_by'                   => Auth::id(),
                'updated_by'                   => Auth::id(),
            ]
        );

        return response()->json(['success' => true, 'setting_id' => $setting->id, 'setting' => $setting]);
    }

    // =========================================================================
    // SAVE SETTINGS
    // =========================================================================
    public function saveSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'                   => 'required|exists:timetable_settings,id',
            'expected_updated_at'          => 'nullable|date',
            'school_day_start'             => 'required|date_format:H:i',
            'school_day_end'               => 'required|date_format:H:i',
            'period_duration_minutes'      => 'required|integer|min:20|max:90',
            'short_break_duration_minutes' => 'required|integer|min:5|max:60',
            'long_break_duration_minutes'  => 'required|integer|min:10|max:90',
            'active_days'                  => 'required|array|min:1',
            'periods'                      => 'required|array|min:1',
            'periods.*.name'               => 'required|string|max:60',
            'periods.*.type'               => 'required|in:lesson,short_break,long_break,assembly,free',
        ]);

        try {
            DB::beginTransaction();
            $setting = TimetableSetting::findOrFail($validated['setting_id']);
            if ($lock = $this->publishedLockResponse($setting)) { DB::rollBack(); return $lock; }
            if ($conflict = $this->versionConflictResponse($setting, $validated['expected_updated_at'] ?? null)) { DB::rollBack(); return $conflict; }

            $isNew = $setting->periods()->count() === 0;

            $setting->update([
                'school_day_start'             => $validated['school_day_start'],
                'school_day_end'               => $validated['school_day_end'],
                'period_duration_minutes'      => $validated['period_duration_minutes'],
                'short_break_duration_minutes' => $validated['short_break_duration_minutes'],
                'long_break_duration_minutes'  => $validated['long_break_duration_minutes'],
                'active_days'                  => $validated['active_days'],
                'updated_by'                   => Auth::id(),
            ]);

            if ($isNew && !$setting->created_by) {
                $setting->created_by = Auth::id();
                $setting->saveQuietly();
            }

            TimetablePeriod::where('setting_id', $setting->id)->delete();

            $start = Carbon::createFromFormat('H:i', $validated['school_day_start']);
            $order = 0;

            foreach ($validated['periods'] as $p) {
                $order++;
                $duration = match($p['type']) {
                    'short_break' => $validated['short_break_duration_minutes'],
                    'long_break'  => $validated['long_break_duration_minutes'],
                    default       => $validated['period_duration_minutes'],
                };
                $end = (clone $start)->addMinutes($duration);
                TimetablePeriod::create([
                    'setting_id'       => $setting->id,
                    'order'            => $order,
                    'name'             => $p['name'],
                    'type'             => $p['type'],
                    'start_time'       => $start->format('H:i'),
                    'end_time'         => $end->format('H:i'),
                    'duration_minutes' => $duration,
                    'is_break'         => in_array($p['type'], ['short_break', 'long_break', 'assembly']),
                ]);
                $start = $end;
            }

            DB::commit();
            $this->logTimetableChange(Auth::id(), 'update', 'TimetableSetting', $setting->id, null, $setting->fresh()->toArray());
            return response()->json(['success' => true, 'setting' => $setting->load('periods')]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('saveSettings failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // REBUILD PERIODS FROM ANCHORS
    // =========================================================================
    public function rebuildPeriodsFromAnchors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'                    => 'required|exists:timetable_settings,id',
            'lessons_per_day'               => 'required|integer|min:1|max:12',
            'short_break_after_period'      => 'nullable|integer|min:1',
            'long_break_after_period'       => 'nullable|integer|min:1',
            'assembly_day'                  => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'short_break_duration_minutes'  => 'required|integer|min:5|max:60',
            'long_break_duration_minutes'   => 'required|integer|min:10|max:90',
            'period_duration_minutes'       => 'required|integer|min:20|max:90',
            'school_day_start'              => 'required|date_format:H:i',
            'max_lessons_per_day'           => 'nullable|integer|min:1',
            'free_periods_per_week'         => 'nullable|integer|min:0',
            'deprioritize_break_adjacent'   => 'boolean',
        ]);

        $setting = TimetableSetting::findOrFail($validated['setting_id']);
        if ($lock = $this->publishedLockResponse($setting)) return $lock;

        $setting->update([
            'lessons_per_day'              => $validated['lessons_per_day'],
            'short_break_after_period'     => $validated['short_break_after_period'] ?? null,
            'long_break_after_period'      => $validated['long_break_after_period'] ?? null,
            'assembly_day'                 => $validated['assembly_day'] ?? null,
            'period_duration_minutes'      => $validated['period_duration_minutes'],
            'short_break_duration_minutes' => $validated['short_break_duration_minutes'],
            'long_break_duration_minutes'  => $validated['long_break_duration_minutes'],
            'school_day_start'             => $validated['school_day_start'],
            'max_lessons_per_day'          => $validated['max_lessons_per_day'] ?? null,
            'free_periods_per_week'        => $validated['free_periods_per_week'] ?? 0,
            'deprioritize_break_adjacent'  => $validated['deprioritize_break_adjacent'] ?? true,
            'updated_by'                   => Auth::id(),
        ]);

        $periods = $this->buildPeriodsFromTemplate([
            'lessons_per_day'       => $validated['lessons_per_day'],
            'short_break_after'     => $validated['short_break_after_period'] ?? null,
            'long_break_after'      => $validated['long_break_after_period'] ?? null,
            'assembly_first_period' => !empty($validated['assembly_day']),
        ]);

        TimetablePeriod::where('setting_id', $setting->id)->delete();
        $start = Carbon::createFromFormat('H:i', $validated['school_day_start']);
        $order = 0;
        foreach ($periods as $p) {
            $order++;
            $duration = match ($p['type']) {
                'short_break' => $validated['short_break_duration_minutes'],
                'long_break'  => $validated['long_break_duration_minutes'],
                default       => $validated['period_duration_minutes'],
            };
            $end = (clone $start)->addMinutes($duration);
            TimetablePeriod::create([
                'setting_id' => $setting->id, 'order' => $order,
                'name' => $p['name'], 'type' => $p['type'],
                'start_time' => $start->format('H:i'), 'end_time' => $end->format('H:i'),
                'duration_minutes' => $duration,
                'is_break' => in_array($p['type'], ['short_break', 'long_break', 'assembly']),
            ]);
            $start = $end;
        }

        return response()->json(['success' => true, 'setting' => $setting->fresh(), 'periods' => $setting->periods()->get()]);
    }

    // =========================================================================
    // HALF-DAYS / FREE PERIODS
    // =========================================================================
    public function saveHalfDays(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'          => 'required|exists:timetable_settings,id',
            'half_days'           => 'nullable|array',
            'half_days.*.day'     => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'half_days.*.lessons' => 'required|integer|min:1',
        ]);

        $setting = TimetableSetting::findOrFail($validated['setting_id']);
        if ($lock = $this->publishedLockResponse($setting)) return $lock;

        $map = collect($validated['half_days'] ?? [])->mapWithKeys(fn($h) => [$h['day'] => $h['lessons']])->toArray();
        $setting->update(['half_days' => $map ?: null, 'updated_by' => Auth::id()]);

        return response()->json(['success' => true, 'half_days' => $setting->fresh()->half_days]);
    }

    public function saveFreePeriods(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'                        => 'required|exists:timetable_settings,id',
            'free_periods_per_week'             => 'nullable|integer|min:0|max:20',
            'preferred_free_slots'              => 'nullable|array',
            'preferred_free_slots.*.day'        => 'required_with:preferred_free_slots|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'preferred_free_slots.*.period_id'  => 'required_with:preferred_free_slots|exists:timetable_periods,id',
        ]);

        $setting = TimetableSetting::findOrFail($validated['setting_id']);
        if ($lock = $this->publishedLockResponse($setting)) return $lock;

        $ownPeriodIds = TimetablePeriod::where('setting_id', $setting->id)->pluck('id')->all();
        $slots = collect($validated['preferred_free_slots'] ?? [])
            ->filter(fn($s) => in_array((int) $s['period_id'], $ownPeriodIds, true))
            ->map(fn($s) => ['day' => $s['day'], 'period_id' => (int) $s['period_id']])
            ->unique(fn($s) => $s['day'] . '_' . $s['period_id'])
            ->values()->all();

        $setting->update([
            'preferred_free_slots'  => $slots ?: null,
            'free_periods_per_week' => $validated['free_periods_per_week'] ?? $setting->free_periods_per_week,
            'updated_by'            => Auth::id(),
        ]);

        return response()->json([
            'success'                => true,
            'preferred_free_slots'   => $setting->fresh()->preferred_free_slots,
            'free_periods_per_week'  => $setting->free_periods_per_week,
        ]);
    }

    // =========================================================================
    // WIZARD DATA
    // =========================================================================
    public function getGenerationWizardData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id'          => 'required|exists:schoolsession,id',
            'term_id'             => 'nullable|exists:schoolterm,id',
            'schoolclass_ids'     => 'required|array|min:1',
            'schoolclass_ids.*'   => 'exists:schoolclass,id',
            'include_unassigned'  => 'boolean',
        ]);

        $sessionId = (int) $validated['session_id'];
        $termId    = $validated['term_id'] ?? null;
        $classIds  = $validated['schoolclass_ids'];
        $includeUnassigned = $validated['include_unassigned'] ?? false;

        $settings = TimetableSetting::where('session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('term_id', $termId))
            ->when(!$termId, fn($q) => $q->whereNull('term_id'))
            ->whereIn('schoolclass_id', $classIds)
            ->where('is_preview', false)
            ->with(['constraints', 'subjectPriorities'])
            ->get()
            ->keyBy('schoolclass_id');

        $classes = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->whereIn('schoolclass.id', $classIds)
            ->get()
            ->keyBy('id');

        // Driven from `subjectclass` rows, NOT SubjectTeacher->subjectclass:
        // that relation is a hasOne, but one subjectteacher row is commonly
        // linked to several classes (same teacher/subject for JSS1A, 1B,
        // 1C...). Grouping by the hasOne put the pairing under just ONE of
        // those classes, so the others showed no/too few subjects here, got
        // no constraints from the wizard, and came out of generation empty.
        $subjectTeachers = Subjectclass::with(['subjectTeacher.subject', 'subjectTeacher.staff'])
            ->whereIn('schoolclassid', $classIds)
            ->whereHas('subjectTeacher', function ($q) use ($sessionId, $termId) {
                $q->where('sessionid', $sessionId);
                if ($termId) $q->where('termid', $termId);
            })
            ->get()
            ->groupBy(fn($sc) => (int) $sc->schoolclassid)
            ->map(fn($rows) => $rows->pluck('subjectTeacher')->filter()->unique('subjectid')->values());

        // Every subject-teacher pairing for this session/term, regardless
        // of which class(es) it may already be linked to via `subjectclass`
        // (read-only here, never touches that table) -- the per-class
        // exclusion below (`$seenSubjectIds`) is what keeps a subject off
        // a class that already has it, while still letting it surface as
        // available for every OTHER class, including ones it's already
        // allocated to elsewhere. Shown only when the admin opts in — this
        // is what lets a period allocation set built for a subject that's
        // not yet on THIS class actually drive generation here.
        $pendingSubjectTeachers = collect();
        if ($includeUnassigned) {
            $pendingSubjectTeachers = SubjectTeacher::with(['subject', 'staff'])
                ->where('sessionid', $sessionId)
                ->when($termId, fn($q) => $q->where('termid', $termId))
                ->get()
                ->unique('subjectid')
                ->values();
        }

        $compulsory = CompulsorySubjectClass::where('sessionid', $sessionId)
            ->when($termId, fn($q) => $q->where('termid', $termId))
            ->whereIn('schoolclassid', $classIds)
            ->get()
            ->keyBy(fn($c) => $c->schoolclassid . ':' . $c->subjectId);

        $roomMappings = RoomClassSubject::with('room')
            ->where('session_id', $sessionId)
            ->where(function ($q) use ($termId) {
                $q->whereNull('term_id');
                if ($termId) $q->orWhere('term_id', $termId);
            })
            ->whereIn('schoolclass_id', $classIds)
            ->get();

        $roomsByClassAndSubject = [];
        $roomsByClassGeneric    = [];
        foreach ($roomMappings as $m) {
            $room = [
                'id'   => $m->room_id,
                'name' => $m->room?->room_name,
                'code' => $m->room?->room_code,
            ];
            if ($m->subject_id) {
                $roomsByClassAndSubject[$m->schoolclass_id . ':' . $m->subject_id][] = $room;
            } else {
                $roomsByClassGeneric[$m->schoolclass_id][] = $room;
            }
        }

        $classPayload = [];

        foreach ($classIds as $classId) {
            $setting   = $settings->get($classId);
            $classMeta = $classes->get($classId);
            $className = trim(($classMeta?->schoolclass ?? '') . ' ' . ($classMeta?->arm_name ?? ''));

            $constraintsBySubject = $setting ? $setting->constraints->keyBy('subject_id') : collect();
            $prioritiesBySubject  = $setting ? $setting->subjectPriorities->keyBy('subject_id') : collect();

            $subjectRows = [];
            $seenSubjectIds = [];

            foreach (($subjectTeachers->get($classId) ?? collect()) as $st) {
                $subjectId  = $st->subjectid;
                $seenSubjectIds[$subjectId] = true;
                $constraint = $constraintsBySubject->get($subjectId);
                $priority   = $prioritiesBySubject->get($subjectId);
                $compKey    = $classId . ':' . $subjectId;

                $subjectRows[] = [
                    'subject_id'    => $subjectId,
                    'subject_name'  => $st->subject?->subject ?? 'Unknown',
                    'subject_code'  => $st->subject?->subject_code,
                    'teacher_id'    => $st->staffid,
                    'teacher_name'  => $st->staff?->name ?? 'Unassigned',
                    'is_compulsory' => $compulsory->has($compKey),
                    'is_pending'    => false,

                    'periods_per_week'            => $constraint?->periods_per_week ?? 2,
                    'allow_double_period'         => (bool) ($constraint?->allow_double_period ?? false),
                    'max_double_periods_per_week' => $constraint?->max_double_periods_per_week ?? 1,

                    'priority_level'       => $priority?->priority_level ?? 3,
                    'use_priority'         => (bool) ($priority?->use_priority ?? false),
                    'affects_ordering'     => (bool) ($priority?->affects_ordering ?? true),
                    'affects_slot_quality' => (bool) ($priority?->affects_slot_quality ?? false),
                    'is_protected'         => (bool) ($priority?->is_protected ?? false),

                    'mapped_rooms_subject' => $roomsByClassAndSubject[$compKey] ?? [],
                    'mapped_rooms_generic' => $roomsByClassGeneric[$classId] ?? [],
                ];
            }

            foreach ($pendingSubjectTeachers as $st) {
                $subjectId = $st->subjectid;
                if (isset($seenSubjectIds[$subjectId])) continue;

                $constraint = $constraintsBySubject->get($subjectId);
                $priority   = $prioritiesBySubject->get($subjectId);
                $compKey    = $classId . ':' . $subjectId;

                $subjectRows[] = [
                    'subject_id'    => $subjectId,
                    'subject_name'  => $st->subject?->subject ?? 'Unknown',
                    'subject_code'  => $st->subject?->subject_code,
                    'teacher_id'    => $st->staffid,
                    'teacher_name'  => $st->staff?->name ?? 'Unassigned',
                    'is_compulsory' => $compulsory->has($compKey),
                    'is_pending'    => true,

                    'periods_per_week'            => $constraint?->periods_per_week ?? 2,
                    'allow_double_period'         => (bool) ($constraint?->allow_double_period ?? false),
                    'max_double_periods_per_week' => $constraint?->max_double_periods_per_week ?? 1,

                    'priority_level'       => $priority?->priority_level ?? 3,
                    'use_priority'         => (bool) ($priority?->use_priority ?? false),
                    'affects_ordering'     => (bool) ($priority?->affects_ordering ?? true),
                    'affects_slot_quality' => (bool) ($priority?->affects_slot_quality ?? false),
                    'is_protected'         => (bool) ($priority?->is_protected ?? false),

                    'mapped_rooms_subject' => $roomsByClassAndSubject[$compKey] ?? [],
                    'mapped_rooms_generic' => $roomsByClassGeneric[$classId] ?? [],
                ];
            }

            usort($subjectRows, function ($a, $b) {
                if ($a['is_compulsory'] !== $b['is_compulsory']) return $b['is_compulsory'] <=> $a['is_compulsory'];
                return strcmp($a['subject_name'], $b['subject_name']);
            });

            $classPayload[] = [
                'schoolclass_id' => $classId,
                'class_name'     => $className ?: 'Class #' . $classId,
                'setting_id'     => $setting?->id,
                'setting_exists' => (bool) $setting,
                'subjects'       => $subjectRows,
            ];
        }

        return response()->json([
            'success'         => true,
            'classes'         => $classPayload,
            'priority_levels' => TimetableSubjectPriority::LEVELS,
        ]);
    }

    // =========================================================================
    // APPLY GENERATION TEMPLATE
    // =========================================================================
    public function applyGenerationTemplate(Request $request): JsonResponse
    {
        // subject_priority_payload / period_limits_payload can repeat the
        // same field names (schoolclass_id, subject_id, periods_per_week,
        // ...) once per class/subject row -- for a whole-school run that's
        // easily 100+ repeats, which some hosting WAFs (Comodo's ruleset on
        // cPanel, in particular) flag as too many / duplicate arguments and
        // block the request with a 406 before it ever reaches here. The
        // frontend sends each as a single JSON-encoded string instead when
        // present; decode it back into a native array here so validation
        // below is unchanged. Same handling as allocations_json in
        // savePeriodAllocationSet().
        foreach (['subject_priority_payload', 'period_limits_payload'] as $jsonField) {
            if ($request->has("{$jsonField}_json") && !$request->has($jsonField)) {
                $decoded = json_decode((string) $request->input("{$jsonField}_json"), true);
                $request->merge([$jsonField => is_array($decoded) ? $decoded : []]);
            }
        }

        $validated = $request->validate([
            'session_id'              => 'required|exists:schoolsession,id',
            'term_id'                 => 'nullable|exists:schoolterm,id',
            'schoolclass_ids'         => 'nullable|array',
            'schoolclass_ids.*'       => 'exists:schoolclass,id',
            'school_day_start'        => 'required|date_format:H:i',
            'school_day_end'          => 'required|date_format:H:i',
            'period_duration_minutes' => 'required|integer|min:20|max:90',
            'short_break_duration'    => 'required|integer|min:5|max:60',
            'long_break_duration'     => 'required|integer|min:10|max:90',
            'lessons_per_day'         => 'required|integer|min:1|max:12',
            'short_break_after'       => 'nullable|integer|min:1',
            'long_break_after'        => 'nullable|integer|min:1',
            'assembly_first_period'   => 'boolean',
            'assembly_day'            => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'active_days'             => 'required|array|min:1',
            'free_periods_per_week'   => 'nullable|integer|min:0',
            'free_periods_map'        => 'nullable|array',
            'free_periods_map.*'      => 'integer|min:0|max:20',
            'max_lessons_per_day'     => 'nullable|integer|min:1',
            'half_days'               => 'nullable|array',
            'half_days.*.day'         => 'required_with:half_days|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'half_days.*.lessons'     => 'required_with:half_days|integer|min:1',
            'deprioritize_break_adjacent' => 'boolean',

            'subject_priority_payload'                                 => 'nullable|array',
            'subject_priority_payload.*.schoolclass_id'                => 'required_with:subject_priority_payload|exists:schoolclass,id',
            'subject_priority_payload.*.subject_id'                    => 'required_with:subject_priority_payload|exists:subject,id',
            'subject_priority_payload.*.periods_per_week'              => 'required_with:subject_priority_payload|integer|min:1|max:20',
            'subject_priority_payload.*.allow_double_period'           => 'boolean',
            'subject_priority_payload.*.max_double_periods_per_week'   => 'integer|min:0|max:5',
            'subject_priority_payload.*.use_priority'                  => 'boolean',
            'subject_priority_payload.*.priority_level'                => 'integer|min:1|max:5',
            'subject_priority_payload.*.affects_ordering'              => 'boolean',
            'subject_priority_payload.*.affects_slot_quality'          => 'boolean',
            'subject_priority_payload.*.is_protected'                  => 'boolean',

            'period_limits_payload'                            => 'nullable|array',
            'period_limits_payload.*.scope'                    => 'required_with:period_limits_payload|in:teacher_total,teacher_class,teacher_day,class_total',
            'period_limits_payload.*.teacher_id'               => 'nullable|exists:users,id',
            'period_limits_payload.*.schoolclass_id'           => 'nullable|exists:schoolclass,id',
            'period_limits_payload.*.day'                      => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'period_limits_payload.*.max_periods'              => 'required_with:period_limits_payload|integer|min:1|max:60',

            'advanced_rules'                                   => 'nullable|array',
            'advanced_rules.cap_mode'                          => 'nullable|in:hard,soft',
            'advanced_rules.morning_cutoff'                    => 'nullable|in:half,fixed',
            'advanced_rules.morning_cutoff_n'                  => 'nullable|integer|min:1|max:12',
            'advanced_rules.protected_mode'                    => 'nullable|in:drop_unprotected,evict,both',
            'advanced_rules.strict_room_mapping'               => 'boolean',
            'advanced_rules.strict_room_mode'                  => 'nullable|in:teacher_only,refuse',
            'advanced_rules.priorities_active'                 => 'boolean',
            'advanced_rules.join_double_periods'               => 'boolean',
        ]);

        $classIds = $validated['schoolclass_ids'] ?? Schoolclass::pluck('id')->toArray();

        if (empty($classIds)) {
            return response()->json(['success' => false, 'message' => 'No classes found for this scope.'], 404);
        }

        $periodTemplate = $this->buildPeriodsFromTemplate($validated);
        $halfDaysMap = isset($validated['half_days'])
            ? collect($validated['half_days'])->mapWithKeys(fn($h) => [$h['day'] => $h['lessons']])->toArray()
            : null;
        $results = [];
        $advancedRules = $validated['advanced_rules'] ?? null;

        DB::beginTransaction();
        try {
            if (!empty($validated['period_limits_payload'])) {
                $sessionId = (int) $validated['session_id'];
                $termId    = $validated['term_id'] ?? null;

                TimetablePeriodLimit::where('session_id', $sessionId)
                    ->when($termId, fn($q) => $q->where('term_id', $termId))
                    ->when(!$termId, fn($q) => $q->whereNull('term_id'))
                    ->delete();

                foreach ($validated['period_limits_payload'] as $limit) {
                    TimetablePeriodLimit::create([
                        'session_id'     => $sessionId,
                        'term_id'        => $termId,
                        'scope'          => $limit['scope'],
                        'teacher_id'     => $limit['teacher_id']     ?? null,
                        'schoolclass_id' => $limit['schoolclass_id'] ?? null,
                        'day'            => $limit['day']            ?? null,
                        'max_periods'    => $limit['max_periods'],
                    ]);
                }
            }

            foreach ($classIds as $classId) {
                $setting = TimetableSetting::firstOrCreate(
                    [
                        'schoolclass_id' => $classId,
                        'session_id'     => $validated['session_id'],
                        'term_id'        => $validated['term_id'] ?? null,
                    ],
                    ['is_active' => true, 'created_by' => Auth::id(), 'updated_by' => Auth::id()]
                );

                if ($lock = $this->publishedLockResponse($setting)) {
                    $results[] = ['schoolclass_id' => $classId, 'skipped' => true, 'reason' => 'published/locked'];
                    continue;
                }

                $setting->update([
                    'school_day_start'             => $validated['school_day_start'],
                    'school_day_end'               => $validated['school_day_end'],
                    'period_duration_minutes'      => $validated['period_duration_minutes'],
                    'short_break_duration_minutes' => $validated['short_break_duration'],
                    'long_break_duration_minutes'  => $validated['long_break_duration'],
                    'active_days'                  => $validated['active_days'],
                    'free_periods_per_week'        => $validated['free_periods_map'][$classId]
                                                        ?? $validated['free_periods_per_week']
                                                        ?? null,
                    'max_lessons_per_day'          => $validated['max_lessons_per_day'] ?? null,
                    'lessons_per_day'              => $validated['lessons_per_day'],
                    'short_break_after_period'     => $validated['short_break_after'] ?? null,
                    'long_break_after_period'      => $validated['long_break_after'] ?? null,
                    'assembly_day'                 => $validated['assembly_day'] ?? null,
                    'half_days'                    => $halfDaysMap,
                    'deprioritize_break_adjacent'  => $validated['deprioritize_break_adjacent'] ?? true,
                    'advanced_rules'               => $advancedRules,
                    'updated_by'                   => Auth::id(),
                ]);

                if (!empty($validated['subject_priority_payload'])) {
                    $rowsForThisClass = collect($validated['subject_priority_payload'])
                        ->where('schoolclass_id', $classId);

                    foreach ($rowsForThisClass as $row) {
                        TimetableConstraint::updateOrCreate(
                            ['setting_id' => $setting->id, 'subject_id' => $row['subject_id']],
                            [
                                'periods_per_week'              => $row['periods_per_week'],
                                'allow_double_period'           => !empty($row['allow_double_period']),
                                'max_double_periods_per_week'   => $row['max_double_periods_per_week'] ?? 1,
                                'is_compulsory'                 => true,
                                'avoid_consecutive_double_days' => true,
                            ]
                        );

                        TimetableSubjectPriority::updateOrCreate(
                            ['setting_id' => $setting->id, 'subject_id' => $row['subject_id']],
                            [
                                'use_priority'         => !empty($row['use_priority']),
                                'priority_level'       => $row['priority_level'] ?? 3,
                                'affects_ordering'     => !empty($row['affects_ordering']),
                                'affects_slot_quality' => !empty($row['affects_slot_quality']),
                                'is_protected'         => !empty($row['is_protected']),
                            ]
                        );
                    }

                    // The wizard's subject list is the source of truth for
                    // this class: drop constraints left over from earlier
                    // runs for subjects no longer listed (unassigned, or a
                    // pending subject the admin didn't tick), otherwise they
                    // keep eating this class's periods.
                    if ($rowsForThisClass->isNotEmpty()) {
                        $keepSubjectIds = $rowsForThisClass->pluck('subject_id')->all();
                        TimetableConstraint::where('setting_id', $setting->id)
                            ->whereNotIn('subject_id', $keepSubjectIds)->delete();
                        TimetableSubjectPriority::where('setting_id', $setting->id)
                            ->whereNotIn('subject_id', $keepSubjectIds)->delete();
                    }
                }

                TimetablePeriod::where('setting_id', $setting->id)->delete();

                $start = Carbon::createFromFormat('H:i', $validated['school_day_start']);
                $order = 0;
                foreach ($periodTemplate as $p) {
                    $order++;
                    $duration = match ($p['type']) {
                        'short_break' => $validated['short_break_duration'],
                        'long_break'  => $validated['long_break_duration'],
                        default       => $validated['period_duration_minutes'],
                    };
                    $end = (clone $start)->addMinutes($duration);
                    TimetablePeriod::create([
                        'setting_id' => $setting->id, 'order' => $order,
                        'name' => $p['name'], 'type' => $p['type'],
                        'start_time' => $start->format('H:i'), 'end_time' => $end->format('H:i'),
                        'duration_minutes' => $duration,
                        'is_break' => in_array($p['type'], ['short_break', 'long_break', 'assembly']),
                    ]);
                    $start = $end;
                }

                $results[] = ['schoolclass_id' => $classId, 'setting_id' => $setting->id, 'skipped' => false];
            }

            DB::commit();
            return response()->json(['success' => true, 'applied_to' => count($results), 'results' => $results]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PERIOD ALLOCATION (reusable class × subject × periods/week presets)
    // =========================================================================

    /**
     * Class/subject/teacher grid for building or editing a period allocation
     * set. Loads subject-class assignments for the requested classes,
     * scoped to the session (required) and term (optional — omitted means
     * "any term in this session") picked in the modal.
     *
     * subjectclass.termid / subjectclass.session are NOT used for this —
     * SubjectClassController never populates them (see its store()), so
     * they're always null in practice. The real session/term for an
     * assignment lives on the related subjectteacher row, the same source
     * the Subject Class management screens filter/display by, so we filter
     * through that relationship instead.
     */
    public function getPeriodAllocationGrid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id'          => 'required|exists:schoolsession,id',
            'term_id'             => 'nullable|exists:schoolterm,id',
            'schoolclass_ids'     => 'nullable|array',
            'schoolclass_ids.*'   => 'exists:schoolclass,id',
            'include_unassigned'  => 'boolean',
        ]);

        try {
            $classIds  = $validated['schoolclass_ids'] ?? null;
            $sessionId = $validated['session_id'];
            $termId    = $validated['term_id'] ?? null;
            $includeUnassigned = $validated['include_unassigned'] ?? false;

            $subjectClasses = Subjectclass::with(['subject', 'subjectTeacher.staff'])
                ->when($classIds, fn($q) => $q->whereIn('schoolclassid', $classIds))
                ->whereHas('subjectTeacher', function ($q) use ($sessionId, $termId) {
                    $q->where('sessionid', $sessionId);
                    if ($termId) {
                        $q->where('termid', $termId);
                    }
                })
                ->get()
                ->groupBy(fn($sc) => (int) $sc->schoolclassid);

            // Every subject-teacher pairing that exists for this session/term,
            // regardless of which class(es) it may already be linked to via
            // `subjectclass` (this block only ever reads that table, never
            // writes it) — per-class exclusion (a subject already on THIS
            // class) happens below, per class card, so a subject already
            // allocated to Class A still surfaces as available for Class B.
            // Shown only when the admin ticks "Also show subjects not yet
            // allocated to this class", so periods can be planned for them
            // ahead of (or in addition to) the formal Subject-Class
            // assignment.
            $pendingSubjects = collect();
            if ($includeUnassigned) {
                $pendingSubjects = SubjectTeacher::with(['subject', 'staff'])
                    ->where('sessionid', $sessionId)
                    ->when($termId, fn($q) => $q->where('termid', $termId))
                    ->get()
                    ->unique('subjectid')
                    ->map(fn($st) => [
                        'subject_id'   => (int) $st->subjectid,
                        'subject_name' => $st->subject?->subject ?? 'Unknown',
                        'subject_code' => $st->subject?->subject_code,
                        'teacher_id'   => $st->staffid,
                        'teacher_name' => $st->staff?->name ?? 'Unassigned',
                        'is_pending'   => true,
                    ])
                    ->sortBy('subject_name')
                    ->values();
            }

            if ($subjectClasses->isEmpty() && $pendingSubjects->isEmpty()) {
                return response()->json(['success' => true, 'classes' => []]);
            }

            // Which classes to build cards for: an explicit filter always
            // wins; otherwise every class that already has something
            // assigned, plus — only when the full subject-teacher pool is
            // being shown with no explicit filter — every class in the
            // school, since any of those subjects could still be allocated
            // to a class that has nothing assigned yet.
            $classIdsForMeta = $classIds
                ?: (($includeUnassigned && $pendingSubjects->isNotEmpty())
                    ? Schoolclass::pluck('id')->all()
                    : $subjectClasses->keys()->all());

            if (empty($classIdsForMeta)) {
                return response()->json(['success' => true, 'classes' => []]);
            }

            $classes = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
                ->whereIn('schoolclass.id', $classIdsForMeta)
                ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
                ->get();

            $classPayload = [];
            foreach ($classes as $classMeta) {
                $classId      = $classMeta->id;
                $rowsForClass = $subjectClasses->get($classId) ?? collect();

                $className = trim(($classMeta->schoolclass ?? '') . ' ' . ($classMeta->arm_name ?? ''));

                $subjectRows = $rowsForClass->unique('subjectid')
                    ->map(fn($sc) => [
                        'subject_id'   => (int) $sc->subjectid,
                        'subject_name' => $sc->subject?->subject ?? 'Unknown',
                        'subject_code' => $sc->subject?->subject_code,
                        'teacher_id'   => $sc->subjectTeacher?->staffid,
                        'teacher_name' => $sc->subjectTeacher?->staff?->name ?? 'Unassigned',
                        'is_pending'   => false,
                    ])
                    ->sortBy('subject_name')
                    ->values();

                $assignedSubjectIds = $subjectRows->pluck('subject_id')->all();

                // Same full subject-teacher pool on every class card, minus
                // whichever of those subjects THIS class already has via a
                // normal Subject-Class assignment (avoids a duplicate row /
                // a DOM id clash) — a subject assigned to another class is
                // NOT excluded here, so it still shows up as available.
                $pendingForClass = $pendingSubjects
                    ->reject(fn($p) => in_array($p['subject_id'], $assignedSubjectIds, true))
                    ->values();

                $classPayload[] = [
                    'schoolclass_id'   => $classId,
                    'class_name'       => $className ?: 'Class #' . $classId,
                    'subjects'         => $subjectRows,
                    'pending_subjects' => $pendingForClass,
                ];
            }

            return response()->json(['success' => true, 'classes' => $classPayload]);
        } catch (\Throwable $e) {
            Log::error('getPeriodAllocationGrid failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Saved period-allocation sets for a session/term, for the picker in
     * both the standalone Period Allocation modal and the Generation
     * Wizard's "use a saved allocation" control.
     */
    public function listPeriodAllocationSets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:schoolsession,id',
            'term_id'    => 'nullable|exists:schoolterm,id',
        ]);

        try {
            $sessionId = (int) $validated['session_id'];
            $termId    = $validated['term_id'] ?? null;

            $sets = TimetablePeriodAllocationSet::forScope($sessionId, $termId)
                ->withCount('allocations')
                ->with('updater:id,name')
                ->orderByDesc('updated_at')
                ->get();

            $classCounts = TimetablePeriodAllocation::whereIn('set_id', $sets->pluck('id'))
                ->select('set_id', DB::raw('COUNT(DISTINCT schoolclass_id) as cnt'))
                ->groupBy('set_id')
                ->pluck('cnt', 'set_id');

            return response()->json([
                'success' => true,
                'sets'    => $sets->map(fn($s) => [
                    'id'               => $s->id,
                    'name'             => $s->name,
                    'description'      => $s->description,
                    'term_id'          => $s->term_id,
                    'is_all_terms'     => is_null($s->term_id),
                    'allocation_count' => $s->allocations_count,
                    'class_count'      => (int) ($classCounts[$s->id] ?? 0),
                    'updated_at'       => $s->updated_at->format('d M Y, H:i'),
                    'updated_by'       => $s->updater?->name,
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            Log::error('listPeriodAllocationSets failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * A single set's rows, for prefilling either the Period Allocation
     * modal (to edit it) or the Generation Wizard's Subjects & Priority
     * panel (to apply it before generating).
     */
    public function getPeriodAllocationSetDetail(int $setId): JsonResponse
    {
        try {
            $set = TimetablePeriodAllocationSet::with('allocations')->findOrFail($setId);

            $subjectIds = $set->allocations->pluck('subject_id')->unique()->values();
            $classIds   = $set->allocations->pluck('schoolclass_id')->unique()->values();

            $subjects = Subject::whereIn('id', $subjectIds)->get()->keyBy('id');

            // Which (class, subject) pairs already have a real Subject-Class
            // assignment, and who's currently teaching each subject for
            // this set's session/term — used only to label a row for the
            // admin (pending vs. already assigned); never written to.
            $linkedPairs = Subjectclass::whereIn('schoolclassid', $classIds)
                ->whereIn('subjectid', $subjectIds)
                ->with('subjectTeacher.staff')
                ->get();
            $linkedByPair = $linkedPairs->keyBy(fn($sc) => $sc->schoolclassid . ':' . $sc->subjectid);

            $teachersBySubject = SubjectTeacher::with('staff')
                ->where('sessionid', $set->session_id)
                ->when($set->term_id, fn($q) => $q->where('termid', $set->term_id))
                ->whereIn('subjectid', $subjectIds)
                ->get()
                ->groupBy('subjectid');

            return response()->json([
                'success' => true,
                'set' => [
                    'id'          => $set->id,
                    'session_id'  => $set->session_id,
                    'term_id'     => $set->term_id,
                    'name'        => $set->name,
                    'description' => $set->description,
                ],
                'allocations' => $set->allocations->map(function ($a) use ($subjects, $linkedByPair, $teachersBySubject) {
                    $pairKey = $a->schoolclass_id . ':' . $a->subject_id;
                    $linked  = $linkedByPair->get($pairKey);
                    $teacher = $linked?->subjectTeacher ?? $teachersBySubject->get($a->subject_id)?->first();

                    return [
                        'schoolclass_id'              => $a->schoolclass_id,
                        'subject_id'                  => $a->subject_id,
                        'periods_per_week'            => $a->periods_per_week,
                        'allow_double_period'         => $a->allow_double_period,
                        'max_double_periods_per_week' => $a->max_double_periods_per_week,
                        'subject_name'                => $subjects->get($a->subject_id)?->subject ?? 'Unknown',
                        'teacher_name'                => $teacher?->staff?->name ?? 'Unassigned',
                        'is_pending'                  => !$linked,
                    ];
                })->values(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'That period allocation set no longer exists.'], 404);
        } catch (\Throwable $e) {
            Log::error('getPeriodAllocationSetDetail failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create or update a named period-allocation set. A set's session/term
     * scope is fixed at creation — updating only ever changes its name,
     * description and rows.
     */
    public function savePeriodAllocationSet(Request $request): JsonResponse
    {
        // 'allocations' is sent as a single JSON-encoded string
        // (allocations_json) instead of a native JSON array of objects.
        // A large set can have 50+ rows, each repeating the same field
        // names (schoolclass_id, subject_id, periods_per_week, ...) — on
        // some hosts, a WAF (Comodo's ruleset on cPanel, in particular)
        // flags that shape as too many / duplicate arguments and blocks
        // the request with a 406 before it ever reaches this method.
        // Decoding it back into 'allocations' here keeps everything below
        // — validation, the dupe check, the insert — unchanged.
        if ($request->has('allocations_json') && !$request->has('allocations')) {
            $decodedAllocations = json_decode((string) $request->input('allocations_json'), true);
            $request->merge(['allocations' => is_array($decodedAllocations) ? $decodedAllocations : []]);
        }

        $validated = $request->validate([
            'set_id'                               => 'nullable|exists:timetable_period_allocation_sets,id',
            'session_id'                           => 'required|exists:schoolsession,id',
            'term_id'                              => 'nullable|exists:schoolterm,id',
            'name'                                 => 'required|string|max:150',
            'description'                          => 'nullable|string|max:1000',
            'allocations'                          => 'required|array|min:1',
            'allocations.*.schoolclass_id'         => 'required|exists:schoolclass,id',
            'allocations.*.subject_id'             => 'required|exists:subject,id',
            'allocations.*.periods_per_week'       => 'required|integer|min:1|max:20',
            'allocations.*.allow_double_period'    => 'boolean',
            'allocations.*.max_double_periods_per_week' => 'integer|min:0|max:5',
        ]);

        // Two rows can't target the same (class, subject) pair — the unique
        // index would reject the bulk insert below, so fail fast with a
        // clear message instead of a raw DB error.
        $dupeKey = collect($request->input('allocations', []))
            ->map(fn($r) => ($r['schoolclass_id'] ?? '') . ':' . ($r['subject_id'] ?? ''))
            ->duplicates();
        if ($dupeKey->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'The same class/subject appears more than once in this set — each class/subject pair can only have one periods-per-week value.',
            ], 422);
        }

        $sessionId = (int) $validated['session_id'];
        $termId    = $validated['term_id'] ?? null;

        try {
            // forScope() OR-includes the session's "All Terms" sets alongside
            // this specific term, so this also stops a term-specific set from
            // shadowing an all-terms one with the same name (they'd otherwise
            // both show up side by side in the same picker).
            $nameTaken = TimetablePeriodAllocationSet::forScope($sessionId, $termId)
                ->where('name', $validated['name'])
                ->when(!empty($validated['set_id']), fn($q) => $q->where('id', '!=', $validated['set_id']))
                ->exists();

            if ($nameTaken) {
                return response()->json([
                    'success' => false,
                    'message' => 'A period allocation set named "' . $validated['name'] . '" already exists for this session/term. Choose a different name.',
                ], 422);
            }

            DB::beginTransaction();

            if (!empty($validated['set_id'])) {
                $set = TimetablePeriodAllocationSet::findOrFail($validated['set_id']);
                if ((int) $set->session_id !== $sessionId || $set->term_id != $termId) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'That set belongs to a different session/term.'], 422);
                }
                $set->update([
                    'name'        => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'updated_by'  => Auth::id(),
                ]);
            } else {
                $set = TimetablePeriodAllocationSet::create([
                    'session_id'  => $sessionId,
                    'term_id'     => $termId,
                    'name'        => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'created_by'  => Auth::id(),
                    'updated_by'  => Auth::id(),
                ]);
            }

            TimetablePeriodAllocation::where('set_id', $set->id)->delete();

            $now  = now();
            $rows = collect($validated['allocations'])
                ->map(fn($r) => [
                    'set_id'                      => $set->id,
                    'schoolclass_id'              => $r['schoolclass_id'],
                    'subject_id'                   => $r['subject_id'],
                    'periods_per_week'             => $r['periods_per_week'],
                    'allow_double_period'          => !empty($r['allow_double_period']),
                    'max_double_periods_per_week'  => $r['max_double_periods_per_week'] ?? 1,
                    'created_at'                   => $now,
                    'updated_at'                   => $now,
                ])->values()->all();

            if (!empty($rows)) {
                TimetablePeriodAllocation::insert($rows);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'set_id'  => $set->id,
                'message' => 'Period allocation set saved.',
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('savePeriodAllocationSet failed', ['error' => $e->getMessage()]);

            $message = $e instanceof \Illuminate\Database\QueryException
                ? 'A database error occurred while saving. If this is your first time using Period Allocation, make sure the migration has been run (php artisan migrate).'
                : $e->getMessage();

            return response()->json(['success' => false, 'message' => $message], 500);
        }
    }

    public function deletePeriodAllocationSet(int $setId): JsonResponse
    {
        try {
            $set = TimetablePeriodAllocationSet::findOrFail($setId);
            $set->delete(); // cascades to timetable_period_allocations via FK

            return response()->json(['success' => true]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'That period allocation set no longer exists.'], 404);
        } catch (\Throwable $e) {
            Log::error('deletePeriodAllocationSet failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // SAVE CONSTRAINTS
    // =========================================================================
    public function saveConstraints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'                                    => 'required|exists:timetable_settings,id',
            'expected_updated_at'                            => 'nullable|date',
            'constraints'                                    => 'required|array',
            'constraints.*.subject_id'                       => 'required|exists:subject,id',
            'constraints.*.periods_per_week'                 => 'required|integer|min:1|max:10',
            'constraints.*.allow_double'                      => 'boolean',
            'constraints.*.max_double'                        => 'integer|min:0|max:5',
            'constraints.*.preferred_days'                    => 'nullable|array',
            'constraints.*.avoid_days'                        => 'nullable|array',
            'constraints.*.preferred_periods'                 => 'nullable|array',
            'constraints.*.is_compulsory'                     => 'boolean',
            'constraints.*.avoid_consecutive_double_days'     => 'boolean',
        ]);

        $setting = TimetableSetting::findOrFail($validated['setting_id']);
        if ($lock = $this->publishedLockResponse($setting)) return $lock;
        if ($conflict = $this->versionConflictResponse($setting, $validated['expected_updated_at'] ?? null)) return $conflict;

        DB::transaction(function () use ($validated) {
            TimetableConstraint::where('setting_id', $validated['setting_id'])->delete();
            foreach ($validated['constraints'] as $c) {
                TimetableConstraint::create([
                    'setting_id'                     => $validated['setting_id'],
                    'subject_id'                     => $c['subject_id'],
                    'periods_per_week'               => $c['periods_per_week'],
                    'allow_double_period'            => $c['allow_double'] ?? false,
                    'max_double_periods_per_week'    => $c['max_double'] ?? 1,
                    'preferred_days'                 => $c['preferred_days'] ?? null,
                    'avoid_days'                     => $c['avoid_days'] ?? null,
                    'preferred_periods'              => $c['preferred_periods'] ?? null,
                    'is_compulsory'                  => $c['is_compulsory'] ?? true,
                    'avoid_consecutive_double_days'  => $c['avoid_consecutive_double_days'] ?? true,
                ]);
            }
        });

        $setting->touch();

        return response()->json(['success' => true, 'updated_at' => $setting->fresh()->updated_at->toISOString()]);
    }

    // =========================================================================
    // REAL-TIME SLOT CONFLICT CHECK
    // =========================================================================
    public function checkSlotConflict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id' => 'required|exists:timetable_settings,id',
            'period_id'  => 'required|exists:timetable_periods,id',
            'day'        => 'required|string',
            'teacher_id' => 'nullable|integer',
            'room_id'    => 'nullable|integer',
            'subject_id' => 'nullable|integer',
        ]);

        $setting   = TimetableSetting::findOrFail($validated['setting_id']);
        $sessionId = $setting->session_id;
        $termId    = $setting->term_id;
        $period    = TimetablePeriod::find($validated['period_id']);

        $conflicts = [];
        $warnings  = [];

        if (!empty($validated['teacher_id'])) {
            $teacherConflict = $period
                ? $this->crossSettingSlotsAtTime($period, $validated['day'], $sessionId, $termId, $validated['setting_id'])
                    ->where('timetable_slots.teacher_id', $validated['teacher_id'])
                    ->with(['setting', 'subject'])
                    ->first()
                : null;

            if ($teacherConflict) {
                $sc = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                    ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
                    ->where('schoolclass.id', $teacherConflict->setting->schoolclass_id)->first();
                $teacherConflict->setting->setRelation('schoolclass', $sc);

                $isCombined = !empty($validated['room_id']) && !empty($validated['subject_id'])
                    && $teacherConflict->room_id
                    && (int) $validated['room_id'] === (int) $teacherConflict->room_id
                    && (int) $validated['subject_id'] === (int) $teacherConflict->subject_id;

                if ($isCombined) {
                    $warnings[] = [
                        'type'    => 'combined_session',
                        'icon'    => '👥',
                        'message' => "Combined session — same teacher, subject and room as "
                            . $this->getClassName($teacherConflict->setting->schoolclass) . " at this time. Not a clash.",
                    ];
                } else {
                    $teacher      = User::find($validated['teacher_id']);
                    $className    = $this->getClassName($teacherConflict->setting->schoolclass);
                    $alternatives = $this->findAlternativeSlots(
                        $validated['teacher_id'], $validated['period_id'], $validated['day'], $setting
                    );

                    $conflicts[] = [
                        'type'         => 'teacher',
                        'severity'     => 'error',
                        'icon'         => '👨‍🏫',
                        'message'      => ($teacher?->name ?? 'This teacher')
                            . " is already teaching {$teacherConflict->subject?->subject} in {$className} at this time.",
                        'detail'       => "{$validated['day']} · {$period?->name} · "
                            . $this->formatTime($period?->start_time ?? '') . ' – '
                            . $this->formatTime($period?->end_time ?? ''),
                        'alternatives' => $alternatives,
                    ];
                }
            }

            $dailyCount = TimetableSlot::where('teacher_id', $validated['teacher_id'])
                ->where('day', $validated['day'])
                ->whereHas('setting', fn($q) => $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false))
                ->where('is_free', false)->whereNotNull('subject_id')->count();

            if ($dailyCount >= 4) {
                $teacher = $teacher ?? User::find($validated['teacher_id']);
                $warnings[] = [
                    'type'    => 'workload',
                    'icon'    => '⚠️',
                    'message' => ($teacher?->name ?? 'This teacher')
                        . " already has {$dailyCount} period(s) on {$validated['day']}. High workload.",
                ];
            }
        }

        if (!empty($validated['room_id'])) {
            $roomConflict = $period
                ? $this->crossSettingSlotsAtTime($period, $validated['day'], $sessionId, $termId, $validated['setting_id'])
                    ->where('timetable_slots.room_id', $validated['room_id'])
                    ->with(['setting', 'subject', 'teacher'])
                    ->first()
                : null;

            if ($roomConflict) {
                $sc = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                    ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
                    ->where('schoolclass.id', $roomConflict->setting->schoolclass_id)->first();
                $roomConflict->setting->setRelation('schoolclass', $sc);

                $isCombinedRoom = !empty($validated['teacher_id']) && !empty($validated['subject_id'])
                    && $roomConflict->teacher_id
                    && (int) $validated['teacher_id'] === (int) $roomConflict->teacher_id
                    && (int) $validated['subject_id'] === (int) $roomConflict->subject_id;

                if (!$isCombinedRoom) {
                    $room     = Room::find($validated['room_id']);
                    $altRooms = $this->findAlternativeRooms(
                        $validated['room_id'], $validated['period_id'],
                        $validated['day'], $sessionId, $termId
                    );

                    $conflicts[] = [
                        'type'             => 'room',
                        'severity'         => 'error',
                        'icon'             => '🏫',
                        'message'          => ($room?->room_name ?? 'This room')
                            . " is already used for {$roomConflict->subject?->subject}"
                            . " in " . $this->getClassName($roomConflict->setting->schoolclass)
                            . " (taught by " . ($roomConflict->teacher?->name ?? '—') . ") at this time.",
                        'alternative_rooms'=> $altRooms,
                    ];
                }
            }

            if ($period) {
                $bookingConflict = RoomBooking::where('room_id', $validated['room_id'])
                    ->where('day', $validated['day'])
                    ->where('status', 'confirmed')
                    ->where(function($q) use ($period) {
                        $q->where('start_time', '<', $period->end_time)
                          ->where('end_time', '>', $period->start_time);
                    })->first();

                if ($bookingConflict) {
                    $room = $room ?? Room::find($validated['room_id']);
                    $conflicts[] = [
                        'type'     => 'room_booking',
                        'severity' => 'warning',
                        'icon'     => '📅',
                        'message'  => ($room?->room_name ?? 'This room')
                            . " has an existing booking"
                            . ($bookingConflict->purpose ? " ({$bookingConflict->purpose})" : '')
                            . " on {$validated['day']} from "
                            . $this->formatTime($bookingConflict->start_time)
                            . " to " . $this->formatTime($bookingConflict->end_time) . ".",
                        'alternative_rooms' => $this->findAlternativeRooms(
                            $validated['room_id'], $validated['period_id'],
                            $validated['day'], $sessionId, $termId
                        ),
                    ];
                }
            }
        }

        return response()->json([
            'success'   => true,
            'conflicts' => $conflicts,
            'warnings'  => $warnings,
            'has_error' => count(array_filter($conflicts, fn($c) => $c['severity'] === 'error')) > 0,
        ]);
    }

    // =========================================================================
    // SAVE SLOT
    // =========================================================================
    public function saveSlot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'          => 'required|exists:timetable_settings,id',
            'expected_updated_at' => 'nullable|date',
            'period_id'           => 'required|exists:timetable_periods,id',
            'day'                 => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'subject_id'          => 'nullable|exists:subject,id',
            'teacher_id'          => 'nullable|exists:users,id',
            'room_id'             => 'nullable|exists:rooms,id',
            'is_double'           => 'boolean',
            'is_free'             => 'boolean',
            'notes'               => 'nullable|string|max:191',
            'force_save'          => 'boolean',
        ]);

        $currentSetting = TimetableSetting::findOrFail($validated['setting_id']);
        if ($lock = $this->publishedLockResponse($currentSetting)) return $lock;
        if ($conflict = $this->versionConflictResponse($currentSetting, $validated['expected_updated_at'] ?? null)) return $conflict;

        $sessionId = $currentSetting->session_id;
        $termId    = $currentSetting->term_id;
        $period    = TimetablePeriod::find($validated['period_id']);

        $currentSchoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.id', $currentSetting->schoolclass_id)->first();
        $currentSetting->setRelation('schoolclass', $currentSchoolclass);
        $currentClassName = $this->getClassName($currentSetting->schoolclass);

        if (empty($validated['force_save'])) {

            if (!empty($validated['teacher_id']) && $period) {
                $conflict = $this->crossSettingSlotsAtTime($period, $validated['day'], $sessionId, $termId, $validated['setting_id'])
                    ->where('timetable_slots.teacher_id', $validated['teacher_id'])
                    ->with(['setting', 'subject', 'period', 'teacher'])
                    ->first();

                if ($conflict) {
                    $isCombined = !empty($validated['room_id']) && !empty($validated['subject_id'])
                        && $conflict->room_id
                        && (int) $validated['room_id'] === (int) $conflict->room_id
                        && (int) $validated['subject_id'] === (int) $conflict->subject_id;

                    if (!$isCombined) {
                        $conflictSC = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
                            ->where('schoolclass.id', $conflict->setting->schoolclass_id)->first();
                        $conflict->setting->setRelation('schoolclass', $conflictSC);

                        $alternatives      = $this->findAlternativeSlots(
                            $validated['teacher_id'], $validated['period_id'], $validated['day'], $currentSetting
                        );
                        $conflictClassName = $this->getClassName($conflict->setting?->schoolclass);
                        $teacherName       = $conflict->teacher?->name ?? 'This teacher';
                        $subjectName       = $conflict->subject?->subject ?? 'another subject';
                        $periodName        = $conflict->period?->name ?? '';
                        $periodTime        = $this->formatTime($conflict->period?->start_time ?? '')
                                           . ' – ' . $this->formatTime($conflict->period?->end_time ?? '');

                        return response()->json([
                            'success'               => false,
                            'has_conflict'          => true,
                            'conflict_type'         => 'teacher_double_booking',
                            'message'               => "{$teacherName} is already teaching {$subjectName} in {$conflictClassName} on {$conflict->day}, {$periodName} ({$periodTime}).",
                            'conflict_details'      => [
                                'teacher'           => $teacherName,
                                'conflicting_class' => $conflictClassName,
                                'current_class'     => $currentClassName,
                                'subject'           => $subjectName,
                                'day'               => $conflict->day,
                                'period'            => $periodName,
                                'time'              => $periodTime,
                            ],
                            'alternatives'          => $alternatives,
                            'resolution_suggestion' => $this->buildSuggestionText($teacherName, $currentClassName, $alternatives),
                            'can_override'          => true,
                        ], 409);
                    }
                }
            }

            if (!empty($validated['room_id']) && $period) {
                $roomSlotConflict = $this->crossSettingSlotsAtTime($period, $validated['day'], $sessionId, $termId, $validated['setting_id'])
                    ->where('timetable_slots.room_id', $validated['room_id'])
                    ->with(['setting', 'subject', 'period', 'teacher'])
                    ->first();

                if ($roomSlotConflict) {
                    $isCombinedRoom = !empty($validated['teacher_id']) && !empty($validated['subject_id'])
                        && $roomSlotConflict->teacher_id
                        && (int) $validated['teacher_id'] === (int) $roomSlotConflict->teacher_id
                        && (int) $validated['subject_id'] === (int) $roomSlotConflict->subject_id;

                    if (!$isCombinedRoom) {
                        $conflictSC = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
                            ->where('schoolclass.id', $roomSlotConflict->setting->schoolclass_id)->first();
                        $roomSlotConflict->setting->setRelation('schoolclass', $conflictSC);

                        $room              = Room::find($validated['room_id']);
                        $roomName          = $room?->room_name ?? 'This room';
                        $conflictClassName = $this->getClassName($roomSlotConflict->setting?->schoolclass);
                        $subjectName       = $roomSlotConflict->subject?->subject ?? '—';
                        $teacherName       = $roomSlotConflict->teacher?->name ?? '—';
                        $periodName        = $roomSlotConflict->period?->name ?? '—';
                        $periodTime        = $this->formatTime($roomSlotConflict->period?->start_time ?? '')
                                           . ' – ' . $this->formatTime($roomSlotConflict->period?->end_time ?? '');

                        $alternativeRooms = $this->findAlternativeRooms(
                            $validated['room_id'], $validated['period_id'],
                            $validated['day'], $sessionId, $termId
                        );

                        return response()->json([
                            'success'          => false,
                            'has_conflict'     => true,
                            'conflict_type'    => 'room_double_booking',
                            'message'          => "{$roomName} is already in use for {$subjectName} ({$conflictClassName}, taught by {$teacherName}) on {$validated['day']}, {$periodName} ({$periodTime}).",
                            'conflict_details' => [
                                'room'              => $roomName,
                                'conflicting_class' => $conflictClassName,
                                'subject'           => $subjectName,
                                'teacher'           => $teacherName,
                                'day'               => $validated['day'],
                                'period'            => $periodName,
                                'time'              => $periodTime,
                            ],
                            'alternative_rooms' => $alternativeRooms,
                            'can_override'      => true,
                        ], 409);
                    }
                }

                if ($period) {
                    $bookedConflict = RoomBooking::where('room_id', $validated['room_id'])
                        ->where('day', $validated['day'])
                        ->where('status', 'confirmed')
                        ->where(function($q) use ($period) {
                            $q->where('start_time', '<', $period->end_time)
                              ->where('end_time', '>', $period->start_time);
                        })->first();

                    if ($bookedConflict) {
                        $room = Room::find($validated['room_id']);
                        return response()->json([
                            'success'       => false,
                            'has_conflict'  => true,
                            'conflict_type' => 'room_booking_conflict',
                            'message'       => ($room?->room_name ?? 'This room')
                                . " has an existing booking"
                                . ($bookedConflict->purpose ? " ({$bookedConflict->purpose})" : '')
                                . " on {$validated['day']} ({$this->formatTime($period->start_time)} – {$this->formatTime($period->end_time)}).",
                            'can_override'  => true,
                        ], 409);
                    }
                }
            }
        }

        $slot = TimetableSlot::updateOrCreate(
            [
                'setting_id' => $validated['setting_id'],
                'period_id'  => $validated['period_id'],
                'day'        => $validated['day'],
            ],
            [
                'subject_id' => $validated['subject_id'] ?? null,
                'teacher_id' => $validated['teacher_id'] ?? null,
                'room_id'    => $validated['room_id'] ?? null,
                'notes'      => $validated['notes'] ?? null,
                'is_double'  => $validated['is_double'] ?? false,
                'is_free'    => empty($validated['subject_id']),
            ]
        );

        try { $this->logTimetableChange(Auth::id(), 'update', 'TimetableSlot', $slot->id); }
        catch (\Exception $e) { Log::warning('Audit log failed: ' . $e->getMessage()); }

        try {
            if ($slot->wasChanged('teacher_id') && $slot->teacher_id) {
                $this->scheduleNotification($slot->teacher_id, $slot->id, 'change_alert');
            }
        } catch (\Exception $e) { Log::warning('Notification failed: ' . $e->getMessage()); }

        $currentSetting->touch();

        return response()->json([
            'success'            => true,
            'slot'               => $slot->load(['subject', 'teacher', 'room']),
            'setting_updated_at' => $currentSetting->fresh()->updated_at->toISOString(),
        ]);
    }

    // =========================================================================
    // RESOLVE CONFLICT
    // =========================================================================
    public function resolveConflict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'       => 'required|exists:timetable_settings,id',
            'day'              => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'period_id'        => 'required|exists:timetable_periods,id',
            'action'           => 'required|in:move,change_room',
            'target_day'       => 'required_if:action,move|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'target_period_id' => 'required_if:action,move|exists:timetable_periods,id',
            'target_room_id'   => 'required_if:action,change_room|exists:rooms,id',
        ]);

        $setting = TimetableSetting::findOrFail($validated['setting_id']);
        if ($lock = $this->publishedLockResponse($setting)) return $lock;

        $slot = TimetableSlot::where('setting_id', $setting->id)
            ->where('day', $validated['day'])
            ->where('period_id', $validated['period_id'])
            ->whereNotNull('subject_id')
            ->where('is_free', false)
            ->first();

        if (!$slot) {
            return response()->json([
                'success' => false,
                'message' => 'That slot could not be found — it may already have been moved or edited since this conflict was detected. Refresh and try again.',
            ], 404);
        }

        $sessionId = $setting->session_id;
        $termId    = $setting->term_id;

        if ($validated['action'] === 'move') {
            $targetPeriod = TimetablePeriod::where('id', $validated['target_period_id'])
                ->where('setting_id', $setting->id)->first();
            if (!$targetPeriod) {
                return response()->json(['success' => false, 'message' => 'That destination period does not belong to this class\'s timetable.'], 422);
            }

            $destinationOccupied = TimetableSlot::where('setting_id', $setting->id)
                ->where('day', $validated['target_day'])
                ->where('period_id', $validated['target_period_id'])
                ->whereNotNull('subject_id')
                ->where('is_free', false)
                ->exists();
            if ($destinationOccupied) {
                return response()->json(['success' => false, 'message' => 'That slot is already taken in this class\'s own timetable — pick a different alternative.'], 409);
            }

            if ($slot->teacher_id) {
                $clash = $this->crossSettingSlotsAtTime($targetPeriod, $validated['target_day'], $sessionId, $termId, $setting->id)
                    ->where('timetable_slots.teacher_id', $slot->teacher_id)->first();
                if ($clash && !$this->isCombinedSession($slot, $clash)) {
                    return response()->json(['success' => false, 'message' => 'Moving here would create a new teacher conflict elsewhere — pick a different alternative.'], 409);
                }
            }
            if ($slot->room_id) {
                $roomClash = $this->crossSettingSlotsAtTime($targetPeriod, $validated['target_day'], $sessionId, $termId, $setting->id)
                    ->where('timetable_slots.room_id', $slot->room_id)->first();
                if ($roomClash && !$this->isCombinedSession($slot, $roomClash)) {
                    return response()->json(['success' => false, 'message' => 'Moving here would create a new room conflict elsewhere — pick a different alternative.'], 409);
                }
            }

            $oldDay = $slot->day;
            $oldPeriodId = $slot->period_id;

            DB::transaction(function () use ($slot, $setting, $validated, $oldDay, $oldPeriodId) {
                $slot->update([
                    'day'       => $validated['target_day'],
                    'period_id' => $validated['target_period_id'],
                ]);

                TimetableSlot::updateOrCreate(
                    ['setting_id' => $setting->id, 'day' => $oldDay, 'period_id' => $oldPeriodId],
                    ['subject_id' => null, 'teacher_id' => null, 'room_id' => null, 'is_double' => false, 'is_free' => true, 'notes' => null]
                );
            });

            $message = "Moved to {$validated['target_day']}, {$targetPeriod->name}.";
        } else {
            $period = TimetablePeriod::find($validated['period_id']);
            $room   = Room::find($validated['target_room_id']);

            $roomClash = $this->crossSettingSlotsAtTime($period, $validated['day'], $sessionId, $termId, $setting->id)
                ->where('timetable_slots.room_id', $validated['target_room_id'])->first();
            if ($roomClash) {
                return response()->json(['success' => false, 'message' => 'That room is already taken at this time — pick a different alternative.'], 409);
            }

            $slot->update(['room_id' => $validated['target_room_id']]);
            $message = "Room changed to " . ($room?->room_name ?? 'the selected room') . ".";
        }

        try { $this->logTimetableChange(Auth::id(), 'update', 'TimetableSlot', $slot->id, null, ['conflict_resolved' => $message]); }
        catch (\Exception $e) { Log::warning('Audit log failed: ' . $e->getMessage()); }

        $setting->touch();

        return response()->json([
            'success' => true,
            'message' => $message,
            'slot'    => $slot->fresh()->load(['subject', 'teacher', 'room', 'period']),
        ]);
    }

    // =========================================================================
    // CHECK CONFLICTS
    // =========================================================================
    public function checkConflicts(int $settingId): JsonResponse
    {
        $setting = TimetableSetting::findOrFail($settingId);
        return response()->json(
            $this->buildConflictReport((int) $setting->session_id, $setting->term_id)
        );
    }

    private function buildConflictReport(int $sessionId, ?int $termId): array
    {
        $slots = TimetableSlot::whereHas('setting', function ($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
                else         $q->whereNull('term_id');
            })
            ->whereNotNull('teacher_id')
            ->where('is_free', false)
            ->whereNotNull('subject_id')
            ->with(['period', 'subject', 'setting', 'teacher', 'teacher.staffPicture', 'room'])
            ->get();

        $classIds      = $slots->pluck('setting.schoolclass_id')->unique()->filter();
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->whereIn('schoolclass.id', $classIds)->get()->keyBy('id');

        foreach ($slots as $slot) {
            if ($slot->setting && isset($schoolclasses[$slot->setting->schoolclass_id])) {
                $slot->setting->setRelation('schoolclass', $schoolclasses[$slot->setting->schoolclass_id]);
            }
        }

        $conflicts = [];

        $teacherGrouped = $slots->filter(fn($s) => $s->period)
            ->groupBy(fn($s) => $s->teacher_id . '|' . $s->day . '|' . $this->periodTimeSignature($s->period));

        foreach ($teacherGrouped as $group) {
            if ($group->count() < 2) continue;

            $first       = $group->first();
            $teacherName = $first->teacher?->name ?? '—';
            $periodName  = $first->period?->name ?? '—';
            $periodTime  = $this->formatTime($first->period?->start_time ?? '')
                         . ' – ' . $this->formatTime($first->period?->end_time ?? '');
            $classes     = $group->map(fn($s) => $this->getClassName($s->setting?->schoolclass))->unique()->values();
            $subjects    = $group->map(fn($s) => $s->subject?->subject ?? '—')->unique()->values();
            $classLevels = $group->map(fn($s) => $s->setting?->schoolclass?->schoolclass ?? '')->unique();
            $isCrossArm  = $classLevels->count() === 1 && $classes->count() > 1;

            $groupArr = $group->values();
            for ($i = 0; $i < $groupArr->count(); $i++) {
                for ($j = $i + 1; $j < $groupArr->count(); $j++) {
                    $a = $groupArr[$i];
                    $b = $groupArr[$j];

                    if ($this->isCombinedSession($a, $b)) continue;

                    $classA = $this->getClassName($a->setting?->schoolclass);
                    $classB = $this->getClassName($b->setting?->schoolclass);

                    $alternativesForA = $this->findAlternativeSlots(
                        $a->teacher_id, $a->period_id, $a->day, $a->setting
                    );

                    $conflicts[] = [
                        'type'                  => $isCrossArm ? 'cross_arm_conflict' : 'teacher_conflict',
                        'conflict_category'     => 'teacher',
                        'day'                   => $first->day,
                        'period'                => $periodName,
                        'period_time'           => $periodTime,
                        'period_id'             => $a->period_id,
                        'teacher'               => $teacherName,
                        'teacher_id'            => $first->teacher_id,
                        'teacher_picture'       => $first->teacher?->staffPicture
                            ? asset('storage/staff_avatars/' . $first->teacher->staffPicture->picture)
                            : null,
                        'subject_a'             => $a->subject?->subject ?? '—',
                        'subject_b'             => $b->subject?->subject ?? '—',
                        'class_a'               => $classA,
                        'class_b'               => $classB,
                        'is_cross_arm'          => $isCrossArm,
                        'setting_a_id'          => $a->setting_id,
                        'setting_b_id'          => $b->setting_id,
                        'all_classes'           => $classes,
                        'all_subjects'          => $subjects,
                        'alternatives'          => $alternativesForA,
                        'resolution_suggestion' => $this->buildSuggestionText($teacherName, $classA, $alternativesForA),
                    ];
                }
            }
        }

        $allSlotsWithRoom = TimetableSlot::whereHas('setting', function ($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
                else         $q->whereNull('term_id');
            })
            ->whereNotNull('room_id')
            ->where('is_free', false)
            ->whereNotNull('subject_id')
            ->with(['period', 'subject', 'setting', 'teacher', 'room'])
            ->get();

        $roomClassIds = $allSlotsWithRoom->pluck('setting.schoolclass_id')->unique()->filter();
        $roomClasses  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->whereIn('schoolclass.id', $roomClassIds)->get()->keyBy('id');

        foreach ($allSlotsWithRoom as $slot) {
            if ($slot->setting && isset($roomClasses[$slot->setting->schoolclass_id])) {
                $slot->setting->setRelation('schoolclass', $roomClasses[$slot->setting->schoolclass_id]);
            }
        }

        $roomGrouped = $allSlotsWithRoom->filter(fn($s) => $s->period)
            ->groupBy(fn($s) => $s->room_id . '|' . $s->day . '|' . $this->periodTimeSignature($s->period));

        foreach ($roomGrouped as $group) {
            if ($group->count() < 2) continue;

            $first      = $group->first();
            $room       = $first->room;
            $periodName = $first->period?->name ?? '—';
            $periodTime = $this->formatTime($first->period?->start_time ?? '')
                        . ' – ' . $this->formatTime($first->period?->end_time ?? '');
            $classes    = $group->map(fn($s) => $this->getClassName($s->setting?->schoolclass))->unique()->values();
            $subjects   = $group->map(fn($s) => $s->subject?->subject ?? '—')->unique()->values();

            $groupArr = $group->values();
            for ($i = 0; $i < $groupArr->count(); $i++) {
                for ($j = $i + 1; $j < $groupArr->count(); $j++) {
                    $a = $groupArr[$i];
                    $b = $groupArr[$j];

                    if ($a->teacher_id && $b->teacher_id && $a->teacher_id == $b->teacher_id
                        && $a->subject_id && $b->subject_id && $a->subject_id == $b->subject_id) {
                        continue;
                    }

                    $classA = $this->getClassName($a->setting?->schoolclass);
                    $classB = $this->getClassName($b->setting?->schoolclass);

                    $altRoomsForA = $this->findAlternativeRooms(
                        $a->room_id, $a->period_id, $a->day, $sessionId, $termId
                    );

                    $conflicts[] = [
                        'type'                  => 'room_conflict',
                        'conflict_category'     => 'room',
                        'day'                   => $first->day,
                        'period'                => $periodName,
                        'period_time'           => $periodTime,
                        'period_id'             => $a->period_id,
                        'teacher'               => '🏫 ' . ($room?->room_name ?? 'Unknown Room'),
                        'teacher_id'            => null,
                        'teacher_picture'       => null,
                        'subject_a'             => $a->subject?->subject ?? '—',
                        'subject_b'             => $b->subject?->subject ?? '—',
                        'class_a'               => $classA,
                        'class_b'               => $classB,
                        'is_cross_arm'          => false,
                        'setting_a_id'          => $a->setting_id,
                        'setting_b_id'          => $b->setting_id,
                        'all_classes'           => $classes,
                        'all_subjects'          => $subjects,
                        'alternatives'          => [],
                        'alternative_rooms'     => $altRoomsForA,
                        'resolution_suggestion' => "Room conflict: {$classA} and {$classB} are both assigned to "
                            . ($room?->room_name ?? 'the same room')
                            . " on {$first->day}, {$periodName}. Assign one class to a different room.",
                    ];
                }
            }
        }

        // Subjects that repeat for the same class on the same day are their
        // own class of problem, distinct from a teacher/room double-booking
        // -- e.g. a subject placed in Period 2 AND Period 3 across a break,
        // or three times in one day. Reported through the same $conflicts
        // list (conflict_category 'subject_spread') so both existing entry
        // points -- the per-class Conflicts tab and this Check
        // Conflicts/Anomalies scope modal -- surface them automatically.
        $conflicts = array_merge($conflicts, $this->detectSubjectSpreadAnomalies($sessionId, $termId));

        return [
            'success'        => true,
            'conflicts'      => $conflicts,
            'conflict_count' => count($conflicts),
            'has_conflicts'  => count($conflicts) > 0,
            'checked_at'     => now()->format('d M Y, H:i:s'),
        ];
    }

    /**
     * Flags a subject that repeats for the same class on the same day in a
     * way a real school day should never allow:
     *  - it appears more than twice in one day (never legitimately more
     *    than a single double period), or
     *  - it appears exactly twice but the two periods aren't genuinely
     *    back-to-back in wall-clock time (a break, or some other gap, sits
     *    between them) -- the same period->end_time === nextPeriod->
     *    start_time contiguity rule getNextLessonPeriod()/
     *    getPreviousLessonPeriod() use during generation, applied here as
     *    an audit over whatever slots already exist (covers timetables
     *    generated before that fix, and slots edited by hand afterward).
     */
    private function detectSubjectSpreadAnomalies(int $sessionId, ?int $termId): array
    {
        $slots = TimetableSlot::whereHas('setting', function ($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
                else         $q->whereNull('term_id');
            })
            ->where('is_free', false)
            ->whereNotNull('subject_id')
            ->with(['period', 'subject', 'setting', 'teacher'])
            ->get();

        $classIds      = $slots->pluck('setting.schoolclass_id')->unique()->filter();
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->whereIn('schoolclass.id', $classIds)->get()->keyBy('id');

        foreach ($slots as $slot) {
            if ($slot->setting && isset($schoolclasses[$slot->setting->schoolclass_id])) {
                $slot->setting->setRelation('schoolclass', $schoolclasses[$slot->setting->schoolclass_id]);
            }
        }

        $anomalies = [];

        $grouped = $slots->filter(fn($s) => $s->period)
            ->groupBy(fn($s) => $s->setting_id . '|' . $s->day . '|' . $s->subject_id);

        foreach ($grouped as $group) {
            if ($group->count() < 2) continue;

            $sorted = $group->sortBy(fn($s) => $s->period->start_time)->values();
            $first  = $sorted->first();

            $className   = $this->getClassName($first->setting?->schoolclass);
            $subjectName = $first->subject?->subject ?? '—';
            $teacherName = $first->teacher?->name ?? '—';

            $periodsInfo = $sorted->map(fn($s) => [
                'period_id'   => $s->period_id,
                'period_name' => $s->period->name ?? '—',
                'period_time' => $this->formatTime($s->period->start_time ?? '')
                    . ' – ' . $this->formatTime($s->period->end_time ?? ''),
            ])->values();

            $contiguous = true;
            for ($i = 1; $i < $sorted->count(); $i++) {
                if ($sorted[$i - 1]->period->end_time !== $sorted[$i]->period->start_time) {
                    $contiguous = false;
                    break;
                }
            }

            if ($sorted->count() > 2) {
                $anomalies[] = [
                    'type'                  => 'subject_spread_excess',
                    'conflict_category'     => 'subject_spread',
                    'day'                   => $first->day,
                    'class_a'               => $className,
                    'subject_a'             => $subjectName,
                    'subject_id'            => $first->subject_id,
                    'teacher'               => $teacherName,
                    'periods'               => $periodsInfo,
                    'setting_a_id'          => $first->setting_id,
                    'resolution_suggestion' => "{$subjectName} is scheduled {$sorted->count()} times on {$first->day} for {$className} — more than a double period should ever be. Move the extra period(s) to another day.",
                ];
            } elseif (!$contiguous) {
                $anomalies[] = [
                    'type'                  => 'subject_spread_nonadjacent',
                    'conflict_category'     => 'subject_spread',
                    'day'                   => $first->day,
                    'class_a'               => $className,
                    'subject_a'             => $subjectName,
                    'subject_id'            => $first->subject_id,
                    'teacher'               => $teacherName,
                    'periods'               => $periodsInfo,
                    'setting_a_id'          => $first->setting_id,
                    'resolution_suggestion' => "{$subjectName} appears twice on {$first->day} for {$className}, but {$periodsInfo[0]['period_name']} and {$periodsInfo[1]['period_name']} aren't back-to-back — a break (or gap) separates them. Either make them a genuine adjacent double period, or move one to a different day.",
                ];
            }
        }

        return $anomalies;
    }

    public function checkConflictsScope(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:schoolsession,id',
            'term_id'    => 'nullable|exists:schoolterm,id',
        ]);

        return response()->json(
            $this->buildConflictReport((int) $validated['session_id'], $validated['term_id'] ?? null)
        );
    }

    /**
     * Resolves a 'subject_spread' anomaly (a subject repeating for the same
     * class on the same day more than a legitimate double period allows, or
     * twice but not genuinely back-to-back) by FREEING the extra slot(s)
     * rather than moving/rescheduling anything.
     *
     * Freeing is the only resolution that is provably safe to do
     * automatically: it only removes an existing assignment, so it can
     * never create a new teacher/room double-booking and can never create a
     * new subject-spread anomaly (the class simply gets a free period back,
     * which the admin can fill in manually or via the next generation run).
     *
     * Keep rule: if any two of the subject's same-day slots are genuinely
     * time-contiguous (a legitimate double period), that pair is kept and
     * every other occurrence is freed. Otherwise only the earliest
     * occurrence is kept and every later one is freed.
     */
    public function resolveSubjectSpread(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'           => 'required|exists:timetable_settings,id',
            'day'                  => 'required|string',
            'subject_id'           => 'required|exists:subject,id',
            'expected_updated_at'  => 'nullable|date',
        ]);

        $setting = TimetableSetting::findOrFail($validated['setting_id']);

        if ($lock = $this->publishedLockResponse($setting)) return $lock;
        if ($conflict = $this->versionConflictResponse($setting, $validated['expected_updated_at'] ?? null)) return $conflict;

        $slots = TimetableSlot::where('setting_id', $setting->id)
            ->where('day', $validated['day'])
            ->where('subject_id', $validated['subject_id'])
            ->where('is_free', false)
            ->with('period')
            ->get()
            ->filter(fn($s) => $s->period)
            ->sortBy(fn($s) => $s->period->start_time)
            ->values();

        if ($slots->count() < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Nothing to resolve — this subject no longer repeats abnormally on this day for this class.',
            ], 422);
        }

        // Look for a genuinely contiguous adjacent pair anywhere in the
        // sorted list (mirrors the end_time === start_time rule used during
        // generation and detection) and keep the first such pair found.
        $keepIds = [];
        for ($i = 1; $i < $slots->count(); $i++) {
            if ($slots[$i - 1]->period->end_time === $slots[$i]->period->start_time) {
                $keepIds = [$slots[$i - 1]->id, $slots[$i]->id];
                break;
            }
        }
        if (empty($keepIds)) {
            $keepIds = [$slots->first()->id];
        }

        $freed = [];

        try {
            DB::beginTransaction();

            // Re-check the lock/version inside the transaction to guard
            // against a race with another admin action between the checks
            // above and this write.
            $setting = TimetableSetting::lockForUpdate()->findOrFail($setting->id);
            if ($lock = $this->publishedLockResponse($setting)) { DB::rollBack(); return $lock; }

            foreach ($slots as $slot) {
                if (in_array($slot->id, $keepIds, true)) continue;
                $slot->update([
                    'subject_id' => null,
                    'teacher_id' => null,
                    'room_id'    => null,
                    'is_double'  => false,
                    'is_free'    => true,
                ]);
                $freed[] = [
                    'period_id'   => $slot->period_id,
                    'period_name' => $slot->period->name ?? '—',
                ];
            }

            $setting->touch();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Could not resolve this anomaly: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success'          => true,
            'freed_count'      => count($freed),
            'freed_periods'    => $freed,
            'updated_at'       => $setting->fresh()->updated_at,
            'message'          => count($freed) . ' period(s) freed.',
        ]);
    }

    private function countConflictsForScope(int $sessionId, ?int $termId): array
    {
        $scopeFilter = function ($q) use ($sessionId, $termId) {
            $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
            if ($termId) $q->where('term_id', $termId);
            else         $q->whereNull('term_id');
        };

        $teacherConflicts = TimetableSlot::whereHas('setting', $scopeFilter)
            ->whereNotNull('teacher_id')
            ->where('is_free', false)
            ->whereNotNull('subject_id')
            ->with('period:id,start_time,end_time')
            ->get(['id', 'teacher_id', 'day', 'period_id', 'subject_id', 'room_id'])
            ->filter(fn($s) => $s->period)
            ->groupBy(fn($s) => $s->teacher_id . '|' . $s->day . '|' . $this->periodTimeSignature($s->period))
            ->sum(function ($group) {
                $arr = $group->values();
                $count = 0;
                for ($i = 0; $i < $arr->count(); $i++) {
                    for ($j = $i + 1; $j < $arr->count(); $j++) {
                        if (!$this->isCombinedSession($arr[$i], $arr[$j])) $count++;
                    }
                }
                return $count;
            });

        $roomConflicts = TimetableSlot::whereHas('setting', $scopeFilter)
            ->whereNotNull('room_id')
            ->where('is_free', false)
            ->whereNotNull('subject_id')
            ->with('period:id,start_time,end_time')
            ->get(['id', 'room_id', 'day', 'period_id', 'teacher_id', 'subject_id'])
            ->filter(fn($s) => $s->period)
            ->groupBy(fn($s) => $s->room_id . '|' . $s->day . '|' . $this->periodTimeSignature($s->period))
            ->sum(function ($group) {
                $arr = $group->values();
                $count = 0;
                for ($i = 0; $i < $arr->count(); $i++) {
                    for ($j = $i + 1; $j < $arr->count(); $j++) {
                        $a = $arr[$i];
                        $b = $arr[$j];
                        $same = $a->teacher_id && $b->teacher_id && $a->teacher_id == $b->teacher_id
                            && $a->subject_id && $b->subject_id && $a->subject_id == $b->subject_id;
                        if (!$same) $count++;
                    }
                }
                return $count;
            });

        return [
            'teacher_conflicts' => $teacherConflicts,
            'room_conflicts'    => $roomConflicts,
            'total'             => $teacherConflicts + $roomConflicts,
        ];
    }

    // =========================================================================
    // AUTO-GENERATE
    // =========================================================================
    public function autoGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'          => 'required|exists:timetable_settings,id',
            'expected_updated_at' => 'nullable|date',
            'include_rooms'       => 'boolean',
            'seed'                => 'nullable|integer',
            'generation_name'     => 'nullable|string|max:150',
            'generation_notes'    => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $setting = TimetableSetting::with(['periods', 'constraints.subject', 'subjectPriorities'])->findOrFail($validated['setting_id']);
            if ($lock = $this->publishedLockResponse($setting)) { DB::rollBack(); return $lock; }
            if ($conflict = $this->versionConflictResponse($setting, $validated['expected_updated_at'] ?? null)) { DB::rollBack(); return $conflict; }

            TimetableSlot::where('setting_id', $setting->id)->delete();

            $includeRooms = $validated['include_rooms'] ?? false;

            $usedSeed = $validated['seed'] ?? random_int(1, 2147483647);
            mt_srand($usedSeed);

            $crossOccupied = [];
            TimetableSlot::whereHas('setting', function ($q) use ($setting) {
                    $q->where('session_id', $setting->session_id)->where('is_active', true)->where('is_preview', false)->where('id', '!=', $setting->id);
                    if ($setting->term_id) $q->where('term_id', $setting->term_id);
                    else                   $q->whereNull('term_id');
                })
                ->whereNotNull('teacher_id')->where('is_free', false)
                ->with('period:id,start_time,end_time')
                ->get(['teacher_id', 'period_id', 'day'])
                ->each(function ($occ) use (&$crossOccupied) {
                    if (!$occ->period) return;
                    $crossOccupied[$occ->teacher_id][$occ->day][] = $this->periodTimeSignature($occ->period);
                });

            $roomOccupied = [];
            if ($includeRooms) {
                TimetableSlot::whereHas('setting', function ($q) use ($setting) {
                        $q->where('session_id', $setting->session_id)->where('is_active', true)->where('is_preview', false)->where('id', '!=', $setting->id);
                        if ($setting->term_id) $q->where('term_id', $setting->term_id);
                        else                   $q->whereNull('term_id');
                    })
                    ->whereNotNull('room_id')->where('is_free', false)
                    ->with('period:id,start_time,end_time')
                    ->get(['room_id', 'period_id', 'day'])
                    ->each(function ($occ) use (&$roomOccupied) {
                        if (!$occ->period) return;
                        $roomOccupied[$occ->room_id][$occ->day][] = $this->periodTimeSignature($occ->period);
                    });
            }

            $stats = $this->runAutoGenerateCore($setting, $crossOccupied, $includeRooms, $roomOccupied);

            $setting->update([
                'generation_seed'  => $usedSeed,
                'generation_name'  => $validated['generation_name']  ?? $setting->generation_name,
                'generation_notes' => $validated['generation_notes'] ?? $setting->generation_notes,
                'generated_by'     => Auth::id(),
                'generated_at'     => now(),
            ]);

            mt_srand();

            DB::commit();
            return response()->json([
                'success'            => true,
                'message'            => 'Timetable generated successfully.',
                'stats'              => $stats,
                'used_seed'          => $usedSeed,
                'setting_updated_at' => $setting->fresh()->updated_at->toISOString(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            mt_srand();
            Log::error('autoGenerate failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function autoGenerateWholeSchool(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id'        => 'required|exists:schoolsession,id',
            'term_id'           => 'nullable|exists:schoolterm,id',
            'schoolclass_ids'   => 'nullable|array',
            'schoolclass_ids.*' => 'exists:schoolclass,id',
            'force_unpublish'   => 'boolean',
            'include_rooms'     => 'boolean',
            'seed'              => 'nullable|integer',
            'generation_name'   => 'required|string|min:2|max:150',
            'generation_notes'  => 'nullable|string|max:1000',
        ]);

        $settings = TimetableSetting::with(['periods', 'constraints.subject', 'subjectPriorities', 'schoolclass'])
            ->where('session_id', $validated['session_id'])
            ->where('is_active', true)
            ->where('is_preview', false)
            ->when($validated['term_id'] ?? null, fn($q) => $q->where('term_id', $validated['term_id']))
            ->when(empty($validated['term_id']), fn($q) => $q->whereNull('term_id'))
            ->when($validated['schoolclass_ids'] ?? null, fn($q) => $q->whereIn('schoolclass_id', $validated['schoolclass_ids']))
            ->get();

        if ($settings->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No timetables found for this scope. Create class timetables first (or run the Generation Wizard, which creates them automatically).'], 404);
        }

        $publishedLocked = $settings->filter(fn($s) => $s->is_published);
        if ($publishedLocked->isNotEmpty() && empty($validated['force_unpublish'])) {
            return response()->json([
                'success'          => false,
                'has_locked'       => true,
                'message'          => $publishedLocked->count() . ' of these timetables are published and locked.',
                'locked_classes'   => $publishedLocked->map(fn($s) => $this->getClassName($s->schoolclass))->values(),
            ], 423);
        }

        $ordered = $settings->shuffle()->sortByDesc(function ($s) {
            return $s->constraints->sum(fn($c) => ($c->is_compulsory ? 100 : 0) + $c->periods_per_week);
        })->values();

        $includeRooms = $validated['include_rooms'] ?? false;
        $results = [];

        try {
            DB::beginTransaction();

            TimetableSlot::whereIn('setting_id', $settings->pluck('id'))->delete();

            if (!empty($validated['force_unpublish'])) {
                TimetableSetting::whereIn('id', $publishedLocked->pluck('id'))
                    ->update(['is_published' => false, 'published_at' => null, 'published_by' => null]);
            }

            $usedSeed = $validated['seed'] ?? random_int(1, 2147483647);
            mt_srand($usedSeed);

            $crossOccupied = [];
            $roomOccupied  = [];
            $teacherLoad   = [];

            // Teachers already booked in classes OUTSIDE this run (e.g. a
            // "selected classes" run) must stay blocked, exactly like the
            // single-class autoGenerate() does.
            TimetableSlot::whereHas('setting', function ($q) use ($validated, $settings) {
                    $q->where('session_id', $validated['session_id'])->where('is_active', true)->where('is_preview', false)
                      ->whereNotIn('id', $settings->pluck('id'));
                    if (!empty($validated['term_id'])) $q->where('term_id', $validated['term_id']);
                    else                                $q->whereNull('term_id');
                })
                ->whereNotNull('teacher_id')->where('is_free', false)
                ->with('period:id,start_time,end_time')
                ->get(['teacher_id', 'period_id', 'day'])
                ->each(function ($occ) use (&$crossOccupied) {
                    if (!$occ->period) return;
                    $crossOccupied[$occ->teacher_id][$occ->day][] = $this->periodTimeSignature($occ->period);
                });

            if ($includeRooms) {
                TimetableSlot::whereHas('setting', function ($q) use ($validated, $settings) {
                        $q->where('session_id', $validated['session_id'])->where('is_active', true)->where('is_preview', false)
                          ->whereNotIn('id', $settings->pluck('id'));
                        if (!empty($validated['term_id'])) $q->where('term_id', $validated['term_id']);
                        else                                $q->whereNull('term_id');
                    })
                    ->whereNotNull('room_id')->where('is_free', false)
                    ->with('period:id,start_time,end_time')
                    ->get(['room_id', 'period_id', 'day'])
                    ->each(function ($occ) use (&$roomOccupied) {
                        if (!$occ->period) return;
                        $roomOccupied[$occ->room_id][$occ->day][] = $this->periodTimeSignature($occ->period);
                    });
            }

            // PHASE 1: every class places its REQUIRED periods_per_week first.
            // Previously each class also ran its overflow fill (handing its
            // teachers extra periods beyond the configured minimum) before
            // the next class had placed anything -- so classes processed
            // later found shared teachers already booked and ended up with
            // missing subjects / Free cells.
            $finalizers = [];
            foreach ($ordered as $setting) {
                $phase1 = $this->runAutoGenerateCore($setting, $crossOccupied, $includeRooms, $roomOccupied, $teacherLoad, true);
                $finalizers[$setting->id] = $phase1['finalize'];
            }

            // PHASE 2: overflow fill + free marking + protected eviction.
            foreach ($ordered as $setting) {
                $stats = ($finalizers[$setting->id])();
                $setting->update([
                    'generation_seed'  => $usedSeed,
                    'generation_name'  => $validated['generation_name']  ?? $setting->generation_name,
                    'generation_notes' => $validated['generation_notes'] ?? $setting->generation_notes,
                    'generated_by'     => Auth::id(),
                    'generated_at'     => now(),
                ]);
                $results[] = [
                    'setting_id'     => $setting->id,
                    'schoolclass_id' => $setting->schoolclass_id,
                    'class_name'     => $this->getClassName($setting->schoolclass),
                    'placed'         => $stats['placed'],
                    'unplaced'       => $stats['unplaced_subjects'],
                    'room_shortfall' => $stats['room_shortfall_count'] ?? 0,
                    'lesson_slots'   => $stats['lesson_slots'] ?? 0,
                    'free_slots'     => $stats['free_slots'] ?? 0,
                    'subjects'       => $stats['subjects_configured'] ?? 0,
                    'no_teacher'     => $stats['subjects_without_teacher'] ?? [],
                    'issue'          => $this->describeGenerationIssue($setting, $stats),
                ];
            }

            $conflictSummary = $this->countConflictsForScope(
                $validated['session_id'],
                $validated['term_id'] ?? null
            );

            mt_srand();

            DB::commit();
            return response()->json([
                'success'          => true,
                'message'          => 'Generated timetables for ' . count($results) . ' class(es).',
                'classes'          => $results,
                'had_shortfalls'   => collect($results)->contains(fn($r) => !empty($r['unplaced'])),
                'include_rooms'    => $includeRooms,
                'used_seed'        => $usedSeed,
                'conflict_summary' => $conflictSummary,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            mt_srand();
            Log::error('autoGenerateWholeSchool failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PREVIEW GENERATION
    // =========================================================================
    public function previewGeneration(Request $request): JsonResponse
    {
        // Same WAF workaround as applyGenerationTemplate() -- see the
        // comment there.
        foreach (['subject_priority_payload', 'period_limits_payload'] as $jsonField) {
            if ($request->has("{$jsonField}_json") && !$request->has($jsonField)) {
                $decoded = json_decode((string) $request->input("{$jsonField}_json"), true);
                $request->merge([$jsonField => is_array($decoded) ? $decoded : []]);
            }
        }

        $validated = $request->validate([
            'setting_id'               => 'required|exists:timetable_settings,id',
            'include_rooms'            => 'boolean',
            'seed'                     => 'nullable|integer',
            'advanced_rules'           => 'nullable|array',
            'subject_priority_payload' => 'nullable|array',
            'period_limits_payload'    => 'nullable|array',
            'orientation'              => 'nullable|in:horizontal,vertical',
        ]);

        $real = TimetableSetting::with(['periods', 'constraints.subject', 'subjectPriorities'])
            ->findOrFail($validated['setting_id']);

        if ($lock = $this->publishedLockResponse($real)) return $lock;

        $shadow = null;

        try {
            DB::beginTransaction();

            $this->sweepExpiredPreviews();

            $shadow = $this->replicateSettingDeep($real, [
                'is_preview'         => true,
                'preview_expires_at' => now()->addMinutes(self::PREVIEW_TTL_MINUTES),
                'is_active'          => false,
            ])['setting'];

            if (!empty($validated['subject_priority_payload'])) {
                $this->applyPriorityOverridesToShadow($shadow, $validated['subject_priority_payload']);
            }
            if (!empty($validated['advanced_rules'])) {
                $shadow->advanced_rules = $validated['advanced_rules'];
                $shadow->saveQuietly();
            }

            $originalLimits = null;
            if (!empty($validated['period_limits_payload'])) {
                $originalLimits = TimetablePeriodLimit::forScope(
                    $real->session_id, $real->term_id
                )->get()->map(fn($l) => $l->only([
                    'scope', 'teacher_id', 'schoolclass_id', 'day', 'max_periods',
                ]))->all();

                TimetablePeriodLimit::forScope($real->session_id, $real->term_id)->delete();
                foreach ($validated['period_limits_payload'] as $row) {
                    TimetablePeriodLimit::create([
                        'session_id'     => $real->session_id,
                        'term_id'        => $real->term_id,
                        'scope'          => $row['scope'],
                        'teacher_id'     => $row['teacher_id']     ?? null,
                        'schoolclass_id' => $row['schoolclass_id'] ?? null,
                        'day'            => $row['day']            ?? null,
                        'max_periods'    => $row['max_periods'],
                    ]);
                }
            }

            $includeRooms = $validated['include_rooms'] ?? false;
            $usedSeed = $validated['seed'] ?? random_int(1, 2147483647);
            mt_srand($usedSeed);

            $crossOccupied = [];
            TimetableSlot::whereHas('setting', function ($q) use ($real) {
                    $q->where('session_id', $real->session_id)
                      ->where('is_active', true)
                      ->where('id', '!=', $real->id)
                      ->where('is_preview', false);
                    if ($real->term_id) $q->where('term_id', $real->term_id);
                    else                $q->whereNull('term_id');
                })
                ->whereNotNull('teacher_id')->where('is_free', false)
                ->with('period:id,start_time,end_time')
                ->get(['teacher_id', 'period_id', 'day'])
                ->each(function ($occ) use (&$crossOccupied) {
                    if (!$occ->period) return;
                    $crossOccupied[$occ->teacher_id][$occ->day][] = $this->periodTimeSignature($occ->period);
                });

            $roomOccupied = [];
            if ($includeRooms) {
                TimetableSlot::whereHas('setting', function ($q) use ($real) {
                        $q->where('session_id', $real->session_id)
                          ->where('is_active', true)
                          ->where('id', '!=', $real->id)
                          ->where('is_preview', false);
                        if ($real->term_id) $q->where('term_id', $real->term_id);
                        else                $q->whereNull('term_id');
                    })
                    ->whereNotNull('room_id')->where('is_free', false)
                    ->with('period:id,start_time,end_time')
                    ->get(['room_id', 'period_id', 'day'])
                    ->each(function ($occ) use (&$roomOccupied) {
                        if (!$occ->period) return;
                        $roomOccupied[$occ->room_id][$occ->day][] = $this->periodTimeSignature($occ->period);
                    });
            }

            $stats = $this->runAutoGenerateCore($shadow, $crossOccupied, $includeRooms, $roomOccupied);

            $shadow->refresh()->load(['periods', 'session', 'term']);
            $grid = $this->buildGridPayloadForSetting($shadow);

            mt_srand();

            DB::commit();

            TimetableSlot::where('setting_id', $shadow->id)->delete();
            TimetablePeriod::where('setting_id', $shadow->id)->delete();
            TimetableConstraint::where('setting_id', $shadow->id)->delete();
            TimetableSubjectPriority::where('setting_id', $shadow->id)->delete();
            $shadow->delete();

            if ($originalLimits !== null) {
                TimetablePeriodLimit::forScope($real->session_id, $real->term_id)->delete();
                foreach ($originalLimits as $row) {
                    TimetablePeriodLimit::create(array_merge($row, [
                        'session_id' => $real->session_id,
                        'term_id'    => $real->term_id,
                    ]));
                }
            }

            return response()->json([
                'success'   => true,
                'preview'   => true,
                'grid'      => $grid,
                'stats'     => $stats,
                'used_seed' => $usedSeed,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            mt_srand();
            Log::error('previewGeneration failed', ['error' => $e->getMessage()]);

            if ($shadow) {
                try {
                    TimetableSlot::where('setting_id', $shadow->id)->delete();
                    TimetablePeriod::where('setting_id', $shadow->id)->delete();
                    TimetableConstraint::where('setting_id', $shadow->id)->delete();
                    TimetableSubjectPriority::where('setting_id', $shadow->id)->delete();
                    $shadow->delete();
                } catch (\Exception $e2) { /* swallow */ }
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sweepExpiredPreviewsNow(): void
    {
        $this->sweepExpiredPreviews();
    }

    private function applyPriorityOverridesToShadow(TimetableSetting $shadow, array $payload): void
    {
        foreach ($payload as $row) {
            TimetableConstraint::updateOrCreate(
                ['setting_id' => $shadow->id, 'subject_id' => $row['subject_id']],
                [
                    'periods_per_week'              => $row['periods_per_week'],
                    'allow_double_period'           => !empty($row['allow_double_period']),
                    'max_double_periods_per_week'   => $row['max_double_periods_per_week'] ?? 1,
                    'is_compulsory'                 => true,
                    'avoid_consecutive_double_days' => true,
                ]
            );

            TimetableSubjectPriority::updateOrCreate(
                ['setting_id' => $shadow->id, 'subject_id' => $row['subject_id']],
                [
                    'use_priority'         => !empty($row['use_priority']),
                    'priority_level'       => $row['priority_level'] ?? 3,
                    'affects_ordering'     => !empty($row['affects_ordering']),
                    'affects_slot_quality' => !empty($row['affects_slot_quality']),
                    'is_protected'         => !empty($row['is_protected']),
                ]
            );
        }
        $shadow->load('constraints', 'subjectPriorities');
    }

    private function sweepExpiredPreviews(): void
    {
        $expired = TimetableSetting::expiredPreviews()->pluck('id');
        if ($expired->isEmpty()) return;

        TimetableSlot::whereIn('setting_id', $expired)->delete();
        TimetablePeriod::whereIn('setting_id', $expired)->delete();
        TimetableConstraint::whereIn('setting_id', $expired)->delete();
        TimetableSubjectPriority::whereIn('setting_id', $expired)->delete();
        TimetableSetting::whereIn('id', $expired)->delete();
    }

    // =========================================================================
    // CORE GENERATOR
    // =========================================================================
    /**
     * Plain-language reason a class came out empty / thin, so the wizard
     * can say WHY instead of just "0 placed".
     */
    private function describeGenerationIssue(TimetableSetting $setting, array $stats): ?string
    {
        if (($stats['lesson_slots'] ?? 0) === 0) {
            return 'No lesson periods on this timetable -- re-apply the period structure.';
        }
        if (($stats['subjects_configured'] ?? 0) === 0) {
            $anyTerm = SubjectTeacher::where('sessionid', $setting->session_id)
                ->whereHas('subjectclass', fn($q) => $q->where('schoolclassid', $setting->schoolclass_id))
                ->exists();
            return $anyTerm && $setting->term_id
                ? 'No subjects assigned to this class for the selected term (assignments exist for another term).'
                : 'No subjects assigned to this class for this session -- add them under Subject Class.';
        }
        $free = $stats['free_slots'] ?? 0;
        $budgetFree = max(0, ($stats['lesson_slots'] ?? 0) - ($stats['placement_budget'] ?? 0));
        if ($free > $budgetFree) {
            return ($free - $budgetFree) . ' slot(s) left Free because no teacher was available (clashes, availability or period limits).';
        }
        return null;
    }

    private function runAutoGenerateCore(
        TimetableSetting $setting,
        array &$crossOccupied,
        bool $includeRooms = false,
        array &$roomOccupied = [],
        array &$teacherLoad = [],
        bool $deferFinalize = false
    ): array {
        $lessonPeriods = $setting->periods->where('type', 'lesson')->values();
        $days          = $setting->active_days ?? self::DAYS;
        $classId       = $setting->schoolclass_id;
        $sessionId     = $setting->session_id;
        $termId        = $setting->term_id;

        $constraints = $this->ensureConstraintsExist($setting);
        $priorities  = $setting->subjectPriorities->keyBy('subject_id');
        $rules       = $this->resolveAdvancedRules($setting);

        $dayMeta     = $this->computeDayPeriodMeta($setting);
        $slotPool    = collect($this->buildWeightedSlotPool($days, $setting, $dayMeta))->shuffle()->values()->all();
        $totalSlots  = count($slotPool);
        $freeTarget  = $setting->free_periods_per_week ?? 0;

        $forcedFreeKeys = [];
        foreach (($setting->preferred_free_slots ?? []) as $pf) {
            if (!empty($pf['day']) && !empty($pf['period_id'])) {
                $forcedFreeKeys[$pf['day'] . '_' . $pf['period_id']] = true;
            }
        }

        $placementBudget = max(0, $totalSlots - max($freeTarget, count($forcedFreeKeys)));

        $subjectTeachers = SubjectTeacher::where('sessionid', $sessionId)
            ->when($termId, fn($q) => $q->where('termid', $termId))
            ->whereHas('subjectclass', fn($q) => $q->where('schoolclassid', $classId))
            ->with(['subject', 'staff'])
            ->get()
            ->groupBy('subjectid');

        // A subject this timetable's constraints ask for but that has no
        // `subjectclass` row for THIS class yet (planned via a period
        // allocation set or the Generation Wizard before the formal
        // Subject-Class assignment) still needs a teacher to schedule
        // lessons with. Fall back to a SubjectTeacher record for the same
        // subject/session/term — read-only, never touches `subjectclass` —
        // regardless of whether that pairing is already linked to some
        // OTHER class; a subject already taught elsewhere is exactly the
        // common case here (e.g. added to a second class via the Period
        // Allocation modal), not an edge case to exclude.
        $pendingConstraintSubjectIds = $constraints->pluck('subject_id')
            ->diff($subjectTeachers->keys())
            ->values();

        if ($pendingConstraintSubjectIds->isNotEmpty()) {
            $pendingSubjectTeachersForClass = SubjectTeacher::where('sessionid', $sessionId)
                ->when($termId, fn($q) => $q->where('termid', $termId))
                ->whereIn('subjectid', $pendingConstraintSubjectIds)
                ->with(['subject', 'staff'])
                ->get()
                ->groupBy('subjectid');

            foreach ($pendingSubjectTeachersForClass as $subjectId => $rows) {
                $subjectTeachers->put($subjectId, $rows);
            }
        }

        $availableRoomIds = [];
        $strictRoomMap    = [];
        if ($includeRooms) {
            if ($rules['strict_room_mapping']) {
                $strictRoomMap = $this->buildStrictRoomMap($classId, $sessionId, $termId);
            } else {
                $classSize = $this->estimateClassSize($classId, $sessionId, $termId);
                $rooms     = Room::where('is_active', true)->get(['id', 'room_name', 'capacity']);
                $availableRoomIds = $classSize
                    ? $rooms->sortBy(fn($r) => ($r->capacity && $r->capacity >= $classSize) ? $r->capacity : (100000 + ($r->capacity ?? 99999)))->pluck('id')->toArray()
                    : $rooms->sortBy('room_name')->pluck('id')->toArray();
            }
        }

        $availabilityMap = $this->loadTeacherAvailability($subjectTeachers);
        $limits          = $this->loadPeriodLimits($sessionId, $termId);

        $teacherDaySlot = [];
        $placed = [];
        $unplacedSubjects = [];
        $roomShortfallCount = 0;
        $roomRefusedSubjects = 0;
        $noRoomPlacementCount = 0;
        $lessonsPlacedByDay = [];
        $maxPerDay = $setting->max_lessons_per_day ?? null;

        // Teacher totals are shared across classes when the caller passes
        // $teacherLoad (whole-school runs), so teacher_total / teacher_day
        // period limits apply school-wide instead of resetting per class.
        $teacherLoad['week']  = $teacherLoad['week']  ?? [];
        $teacherLoad['class'] = $teacherLoad['class'] ?? [];
        $teacherLoad['day']   = $teacherLoad['day']   ?? [];
        $teacherWeekTotal  = &$teacherLoad['week'];
        $teacherClassTotal = &$teacherLoad['class'];
        $teacherDayTotal   = &$teacherLoad['day'];
        $classWeekTotal    = 0;
        $subjectDayPeriods = [];

        $requirements = $constraints
            ->shuffle()
            ->sortBy(function ($c) use ($priorities, $rules) {
                $p = $priorities->get($c->subject_id);
                $rank = 3;
                if ($rules['priorities_active'] && $p && $p->use_priority && $p->affects_ordering) {
                    $rank = $p->priority_level;
                }
                return sprintf('%d-%03d', $rank, $c->periods_per_week);
            })
            ->values();

        $morningCutoff = $this->resolveMorningCutoff($rules, $lessonPeriods->count());

        foreach ($requirements as $constraint) {
            $subjectId = $constraint->subject_id;
            $needed = $constraint->periods_per_week;
            $allowDouble = $constraint->allow_double_period;
            $maxDouble = $constraint->max_double_periods_per_week;
            $preferDays = $constraint->preferred_days ?? [];
            $avoidDays = $constraint->avoid_days ?? [];
            $avoidConsecutiveDoubles = $constraint->avoid_consecutive_double_days ?? true;

            $priorityCfg = $priorities->get($subjectId);
            $priorityActive = $rules['priorities_active'] && $priorityCfg && $priorityCfg->use_priority;

            $teacherEntry = $subjectTeachers->get($subjectId)?->first();
            $teacherId = $teacherEntry?->staffid;

            $alreadyPlaced = $this->countSubjectPlaced($placed, $subjectId);
            if ($alreadyPlaced >= $needed) continue;

            $candidates = [];
            foreach ($slotPool as $slot) {
                $day = $slot['day'];
                $periodId = $slot['period_id'];
                $timeSig = $slot['time_sig'];
                $key = $day . '_' . $periodId;

                if (isset($placed[$key])) continue;
                if (isset($forcedFreeKeys[$key])) continue;

                $existingToday = $subjectDayPeriods[$subjectId][$day] ?? [];
                if (!empty($existingToday)) {
                    if (!$rules['join_double_periods']) {
                        continue;
                    }
                    $adjacentOk = false;
                    foreach ($existingToday as $existingPeriodId) {
                        $next = $this->getNextLessonPeriod($lessonPeriods, $existingPeriodId);
                        $prev = $this->getPreviousLessonPeriod($lessonPeriods, $existingPeriodId);
                        if (($next && $next->id === $periodId) || ($prev && $prev->id === $periodId)) {
                            $adjacentOk = true;
                            break;
                        }
                    }
                    if (!$adjacentOk) continue;
                }

                if ($teacherId) {
                    if (in_array($periodId, $teacherDaySlot[$teacherId][$day] ?? [])) continue;
                    if (in_array($timeSig, $crossOccupied[$teacherId][$day] ?? [])) continue;
                    if (!$this->isTeacherAvailableForPeriod($teacherId, $day, $periodId, $setting, $availabilityMap)) continue;

                    if (!$this->passesPeriodLimits(
                        $limits, $teacherId, $classId, $day,
                        $teacherWeekTotal, $teacherClassTotal, $teacherDayTotal, $classWeekTotal,
                        $rules['cap_mode']
                    )) {
                        continue;
                    }
                }

                if ($maxPerDay && ($lessonsPlacedByDay[$day] ?? 0) >= $maxPerDay) continue;

                $score = 0;
                if (in_array($day, $preferDays)) $score += 20;
                if (in_array($day, $avoidDays)) $score -= 15;
                if ($setting->deprioritize_break_adjacent && !empty($slot['is_break_adjacent'])) $score -= 5;

                $currentDayLoad = $lessonsPlacedByDay[$day] ?? 0;
                $score -= ($currentDayLoad * 2);

                if ($priorityActive && $priorityCfg->affects_slot_quality) {
                    $isMorning = ($slot['period_order'] ?? 0) <= $morningCutoff;
                    if ($priorityCfg->priority_level <= 2) {
                        $score += $isMorning ? 15 : -10;
                    } elseif ($priorityCfg->priority_level >= 4) {
                        $score += $isMorning ? -5 : 5;
                    }
                }

                if ($teacherId && $rules['cap_mode'] === 'soft') {
                    $score -= $this->softCapPenalty(
                        $limits, $teacherId, $classId, $day,
                        $teacherWeekTotal, $teacherClassTotal, $teacherDayTotal, $classWeekTotal
                    );
                }

                $score += mt_rand(-3, 3);

                $candidates[] = [
                    'slot' => $slot,
                    'score' => $score,
                    'key' => $key
                ];
            }

            usort($candidates, fn($a, $b) => $b['score'] - $a['score']);

            $placedThisSubject = $alreadyPlaced;
            $doubleCount = 0;
            $usedDoubleDays = [];

            foreach ($candidates as $candidate) {
                if ($placedThisSubject >= $needed) break;
                if (count($placed) >= $placementBudget) break 2;

                $slot = $candidate['slot'];
                $day = $slot['day'];
                $periodId = $slot['period_id'];
                $timeSig = $slot['time_sig'];
                $key = $candidate['key'];

                if (isset($placed[$key])) continue;
                if ($maxPerDay && ($lessonsPlacedByDay[$day] ?? 0) >= $maxPerDay) continue;

                // =================================================================
                // GUARD: re-validate the same-day / adjacency rule against the
                // LIVE state of $subjectDayPeriods at placement time. $candidates
                // was built in a single pass, before any of this subject's
                // periods were actually placed — so several non-adjacent
                // same-day slots (e.g. Period 1, Period 4, Period 8) could all
                // pass the check simultaneously during candidate-building and
                // then get placed back-to-back here, producing a subject/
                // teacher that appears more than once on the same day in
                // unrelated periods instead of as a genuine, adjacent double
                // period.
                // =================================================================
                $existingTodayNow = $subjectDayPeriods[$subjectId][$day] ?? [];
                if (!empty($existingTodayNow)) {
                    if (!$rules['join_double_periods']) {
                        continue;
                    }
                    $adjacentOkNow = false;
                    foreach ($existingTodayNow as $existingPeriodId) {
                        $next = $this->getNextLessonPeriod($lessonPeriods, $existingPeriodId);
                        $prev = $this->getPreviousLessonPeriod($lessonPeriods, $existingPeriodId);
                        if (($next && $next->id === $periodId) || ($prev && $prev->id === $periodId)) {
                            $adjacentOkNow = true;
                            break;
                        }
                    }
                    if (!$adjacentOkNow) continue;
                }
                // =================================================================

                $roomPick = $this->pickRoomForLesson(
                    $includeRooms, $strictRoomMap, $availableRoomIds, $subjectId,
                    $day, $timeSig, $roomOccupied
                );
                $roomId = $roomPick['room_id'];

                if ($includeRooms && $rules['strict_room_mapping'] && $roomPick['no_mapping']) {
                    if ($rules['strict_room_mode'] === 'refuse') {
                        $roomRefusedSubjects++;
                        continue;
                    }
                    $noRoomPlacementCount++;
                } elseif ($includeRooms && !$roomId) {
                    $roomShortfallCount++;
                    $noRoomPlacementCount++;
                }

                TimetableSlot::create([
                    'setting_id' => $setting->id,
                    'period_id' => $periodId,
                    'day' => $day,
                    'subject_id' => $subjectId,
                    'teacher_id' => $teacherId,
                    'room_id' => $roomId,
                    'is_double' => false,
                    'is_free' => false,
                ]);

                $placed[$key] = $subjectId;
                $lessonsPlacedByDay[$day] = ($lessonsPlacedByDay[$day] ?? 0) + 1;
                $classWeekTotal++;
                $placedThisSubject++;

                $subjectDayPeriods[$subjectId][$day][] = $periodId;
                if (count($subjectDayPeriods[$subjectId][$day]) > 1 && $rules['join_double_periods']) {
                    TimetableSlot::where('setting_id', $setting->id)
                        ->where('day', $day)
                        ->whereIn('period_id', $subjectDayPeriods[$subjectId][$day])
                        ->update(['is_double' => true]);
                }

                if ($teacherId) {
                    $teacherDaySlot[$teacherId][$day][] = $periodId;
                    $crossOccupied[$teacherId][$day][] = $timeSig;
                    $teacherWeekTotal[$teacherId] = ($teacherWeekTotal[$teacherId] ?? 0) + 1;
                    $teacherClassTotal[$teacherId . ':' . $classId] = ($teacherClassTotal[$teacherId . ':' . $classId] ?? 0) + 1;
                    $teacherDayTotal[$teacherId . ':' . $day] = ($teacherDayTotal[$teacherId . ':' . $day] ?? 0) + 1;
                }
                if ($roomId) {
                    $roomOccupied[$roomId][$day][] = $timeSig;
                }

                if ($allowDouble && $doubleCount < $maxDouble && $placedThisSubject < $needed) {
                    $cooldownOk = true;
                    if ($avoidConsecutiveDoubles) {
                        foreach ($usedDoubleDays as $usedDay) {
                            if (abs((self::DAYS_MAP[$day] ?? 0) - (self::DAYS_MAP[$usedDay] ?? 0)) <= 1) {
                                $cooldownOk = false;
                                break;
                            }
                        }
                    }

                    if ($cooldownOk) {
                        $nextPeriod = $this->getNextLessonPeriod($lessonPeriods, $periodId);
                        $nextApplicable = $nextPeriod
                            && ($dayMeta[$day][$nextPeriod->id]['effective_type'] ?? null) === 'lesson'
                            && ($dayMeta[$day][$nextPeriod->id]['applicable'] ?? false);

                        if ($nextPeriod && $nextApplicable) {
                            $nextKey = $day . '_' . $nextPeriod->id;
                            $nextTimeSig = $this->periodTimeSignature($nextPeriod);

                            $teacherConflict = $teacherId && (
                                in_array($nextPeriod->id, $teacherDaySlot[$teacherId][$day] ?? []) ||
                                in_array($nextTimeSig, $crossOccupied[$teacherId][$day] ?? [])
                            );

                            $teacherAvailableNext = !$teacherId
                                || $this->isTeacherAvailableForPeriod(
                                    $teacherId, $day, $nextPeriod->id, $setting, $availabilityMap
                                );

                            $capsOkForNext = true;
                            if ($teacherId) {
                                $capsOkForNext = $this->passesPeriodLimits(
                                    $limits, $teacherId, $classId, $day,
                                    $teacherWeekTotal, $teacherClassTotal, $teacherDayTotal, $classWeekTotal,
                                    $rules['cap_mode']
                                );
                            }

                            if (!isset($placed[$nextKey]) && !isset($forcedFreeKeys[$nextKey])
                                && !$teacherConflict && $teacherAvailableNext && $capsOkForNext) {

                                $nextRoomPick = $this->pickRoomForLesson(
                                    $includeRooms, $strictRoomMap, $availableRoomIds, $subjectId,
                                    $day, $nextTimeSig, $roomOccupied
                                );
                                $nextRoomId = $nextRoomPick['room_id'];

                                if ($includeRooms && $rules['strict_room_mapping']
                                    && $nextRoomPick['no_mapping']
                                    && $rules['strict_room_mode'] === 'refuse') {
                                    continue;
                                }

                                if ($includeRooms && !$nextRoomId && $roomId) {
                                    $roomShortfallCount++;
                                }

                                TimetableSlot::create([
                                    'setting_id' => $setting->id,
                                    'period_id' => $nextPeriod->id,
                                    'day' => $day,
                                    'subject_id' => $subjectId,
                                    'teacher_id' => $teacherId,
                                    'room_id' => $nextRoomId,
                                    'is_double' => true,
                                    'is_free' => false,
                                ]);

                                $placed[$nextKey] = $subjectId;
                                $lessonsPlacedByDay[$day] = ($lessonsPlacedByDay[$day] ?? 0) + 1;
                                $classWeekTotal++;
                                $placedThisSubject++;

                                $subjectDayPeriods[$subjectId][$day][] = $nextPeriod->id;

                                if ($teacherId) {
                                    $teacherDaySlot[$teacherId][$day][] = $nextPeriod->id;
                                    $crossOccupied[$teacherId][$day][] = $nextTimeSig;
                                    $teacherWeekTotal[$teacherId]++;
                                    $teacherClassTotal[$teacherId . ':' . $classId]++;
                                    $teacherDayTotal[$teacherId . ':' . $day]++;
                                }
                                if ($nextRoomId) {
                                    $roomOccupied[$nextRoomId][$day][] = $nextTimeSig;
                                }

                                $doubleCount++;
                                $usedDoubleDays[] = $day;
                            }
                        }
                    }
                }
            }

            if ($placedThisSubject < $needed) {
                $unplacedSubjects[] = [
                    'subject' => $constraint->subject?->subject ?? "Subject #{$subjectId}",
                    'needed' => $needed,
                    'placed' => $placedThisSubject,
                ];
            }
        }

        // Subjects with a constraint but no teacher at all -- they still get
        // placed (teacher-less), but the admin needs to know why.
        $subjectsWithoutTeacher = $requirements
            ->filter(fn($c) => !$subjectTeachers->get($c->subject_id)?->first()?->staffid)
            ->map(fn($c) => $c->subject?->subject ?? ('Subject #' . $c->subject_id))
            ->values()->all();

        $finalize = function () use (
            &$placed, &$placementBudget, &$requirements, &$subjectTeachers, &$slotPool, &$forcedFreeKeys, &$subjectDayPeriods, &$rules, &$lessonPeriods, &$teacherDaySlot, &$crossOccupied, &$setting, &$availabilityMap, &$limits, &$classId, &$teacherWeekTotal, &$teacherClassTotal, &$teacherDayTotal, &$classWeekTotal, &$maxPerDay, &$lessonsPlacedByDay, &$includeRooms, &$strictRoomMap, &$availableRoomIds, &$roomOccupied, &$roomShortfallCount, &$noRoomPlacementCount, &$unplacedSubjects, &$priorities, &$constraints, &$roomRefusedSubjects, &$totalSlots, $subjectsWithoutTeacher
        ) {
        // =====================================================================
        // OVERFLOW FILL: $placementBudget already reserves exactly
        // max($freeTarget, count($forcedFreeKeys)) slots as intentionally
        // free (see its definition above) -- everything else in the week is
        // supposed to be a lesson. But the loop above only ever gives each
        // subject up to its OWN configured periods_per_week; if the class's
        // constraints (auto-generated or manually saved) simply don't add
        // up to a full week, slots were falling straight through to the
        // "mark everything unplaced as free" loop below even though the
        // admin never asked for any free periods. That produced unexplained
        // Free cells that had nothing to do with free_periods_per_week.
        //
        // So: as long as there's still room in $placementBudget, keep
        // cycling through the same subjects/teachers and let them absorb
        // extra periods beyond their configured minimum -- one slot per
        // subject per pass, so the extra periods spread out rather than
        // piling onto a single subject -- using the exact same guards
        // (same-day/adjacency rule, teacher availability & conflicts,
        // period limits, per-day cap, strict room mapping) as the main
        // placement loop. Only slots that truly can't be given to ANY
        // subject under those guards are left for the free-marking loop
        // below -- a genuine scheduling conflict, not a config gap.
        // =====================================================================
        if (count($placed) < $placementBudget && $requirements->isNotEmpty()) {
            $overflowProgress = true;
            while ($overflowProgress && count($placed) < $placementBudget) {
                $overflowProgress = false;

                foreach ($requirements as $constraint) {
                    if (count($placed) >= $placementBudget) break;

                    $subjectId = $constraint->subject_id;
                    $preferDays = $constraint->preferred_days ?? [];
                    $avoidDays = $constraint->avoid_days ?? [];

                    $teacherEntry = $subjectTeachers->get($subjectId)?->first();
                    $teacherId = $teacherEntry?->staffid;

                    $bestSlot = null;
                    $bestScore = null;

                    foreach ($slotPool as $slot) {
                        $day = $slot['day'];
                        $periodId = $slot['period_id'];
                        $timeSig = $slot['time_sig'];
                        $key = $day . '_' . $periodId;

                        if (isset($placed[$key]) || isset($forcedFreeKeys[$key])) continue;

                        $existingToday = $subjectDayPeriods[$subjectId][$day] ?? [];
                        if (!empty($existingToday)) {
                            if (!$rules['join_double_periods']) continue;
                            $adjacentOk = false;
                            foreach ($existingToday as $existingPeriodId) {
                                $next = $this->getNextLessonPeriod($lessonPeriods, $existingPeriodId);
                                $prev = $this->getPreviousLessonPeriod($lessonPeriods, $existingPeriodId);
                                if (($next && $next->id === $periodId) || ($prev && $prev->id === $periodId)) {
                                    $adjacentOk = true;
                                    break;
                                }
                            }
                            if (!$adjacentOk) continue;
                        }

                        if ($teacherId) {
                            if (in_array($periodId, $teacherDaySlot[$teacherId][$day] ?? [])) continue;
                            if (in_array($timeSig, $crossOccupied[$teacherId][$day] ?? [])) continue;
                            if (!$this->isTeacherAvailableForPeriod($teacherId, $day, $periodId, $setting, $availabilityMap)) continue;
                            if (!$this->passesPeriodLimits(
                                $limits, $teacherId, $classId, $day,
                                $teacherWeekTotal, $teacherClassTotal, $teacherDayTotal, $classWeekTotal,
                                $rules['cap_mode']
                            )) continue;
                        }

                        if ($maxPerDay && ($lessonsPlacedByDay[$day] ?? 0) >= $maxPerDay) continue;

                        $score = 0;
                        if (in_array($day, $preferDays)) $score += 20;
                        if (in_array($day, $avoidDays)) $score -= 15;
                        $score -= (($lessonsPlacedByDay[$day] ?? 0) * 2);
                        $score += mt_rand(-3, 3);

                        if ($bestScore === null || $score > $bestScore) {
                            $bestScore = $score;
                            $bestSlot = $slot;
                        }
                    }

                    if (!$bestSlot) continue;

                    $day = $bestSlot['day'];
                    $periodId = $bestSlot['period_id'];
                    $timeSig = $bestSlot['time_sig'];
                    $key = $day . '_' . $periodId;

                    $roomPick = $this->pickRoomForLesson(
                        $includeRooms, $strictRoomMap, $availableRoomIds, $subjectId,
                        $day, $timeSig, $roomOccupied
                    );
                    $roomId = $roomPick['room_id'];

                    if ($includeRooms && $rules['strict_room_mapping'] && $roomPick['no_mapping']) {
                        if ($rules['strict_room_mode'] === 'refuse') {
                            continue;
                        }
                    } elseif ($includeRooms && !$roomId) {
                        $roomShortfallCount++;
                        $noRoomPlacementCount++;
                    }

                    TimetableSlot::create([
                        'setting_id' => $setting->id,
                        'period_id' => $periodId,
                        'day' => $day,
                        'subject_id' => $subjectId,
                        'teacher_id' => $teacherId,
                        'room_id' => $roomId,
                        'is_double' => false,
                        'is_free' => false,
                    ]);

                    $placed[$key] = $subjectId;
                    $lessonsPlacedByDay[$day] = ($lessonsPlacedByDay[$day] ?? 0) + 1;
                    $classWeekTotal++;

                    $subjectDayPeriods[$subjectId][$day][] = $periodId;
                    if (count($subjectDayPeriods[$subjectId][$day]) > 1 && $rules['join_double_periods']) {
                        TimetableSlot::where('setting_id', $setting->id)
                            ->where('day', $day)
                            ->whereIn('period_id', $subjectDayPeriods[$subjectId][$day])
                            ->update(['is_double' => true]);
                    }

                    if ($teacherId) {
                        $teacherDaySlot[$teacherId][$day][] = $periodId;
                        $crossOccupied[$teacherId][$day][] = $timeSig;
                        $teacherWeekTotal[$teacherId] = ($teacherWeekTotal[$teacherId] ?? 0) + 1;
                        $teacherClassTotal[$teacherId . ':' . $classId] = ($teacherClassTotal[$teacherId . ':' . $classId] ?? 0) + 1;
                        $teacherDayTotal[$teacherId . ':' . $day] = ($teacherDayTotal[$teacherId . ':' . $day] ?? 0) + 1;
                    }
                    if ($roomId) {
                        $roomOccupied[$roomId][$day][] = $timeSig;
                    }

                    $overflowProgress = true;
                }
            }
        }

        foreach ($slotPool as $slot) {
            $key = $slot['day'] . '_' . $slot['period_id'];
            if (!isset($placed[$key])) {
                TimetableSlot::create([
                    'setting_id' => $setting->id,
                    'period_id' => $slot['period_id'],
                    'day' => $slot['day'],
                    'subject_id' => null,
                    'teacher_id' => null,
                    'is_free' => true,
                ]);
            }
        }

        if ($rules['priorities_active'] && $rules['protected_mode'] !== 'drop_unprotected' && !empty($unplacedSubjects)) {
            foreach ($unplacedSubjects as &$unp) {
                $subjectId = null;
                foreach ($requirements as $c) {
                    if (($c->subject?->subject ?? null) === $unp['subject']) { $subjectId = $c->subject_id; break; }
                }
                if (!$subjectId) continue;

                $p = $priorities->get($subjectId);
                if (!$p || !$p->use_priority || !$p->is_protected) continue;

                $evicted = $this->tryEvictForProtected(
                    $setting, $subjectId, $p, $unp,
                    $constraints, $priorities, $rules,
                    $slotPool, $forcedFreeKeys,
                    $teacherDaySlot, $crossOccupied, $roomOccupied,
                    $strictRoomMap, $availableRoomIds, $includeRooms,
                    $subjectDayPeriods, $lessonPeriods
                );
                $unp['placed'] += $evicted;
            }
            unset($unp);

            $unplacedSubjects = array_values(array_filter(
                $unplacedSubjects,
                fn($u) => ($u['placed'] ?? 0) < ($u['needed'] ?? 0)
            ));
        }

        return [
            'placed' => count($placed),
            'unplaced_subjects' => $unplacedSubjects,
            'rooms_included' => $includeRooms,
            'room_shortfall_count' => $roomShortfallCount,
            'no_room_placement_count' => $noRoomPlacementCount,
            'room_refused_count' => $roomRefusedSubjects,
            'lesson_slots' => $totalSlots,
            'free_slots' => max(0, $totalSlots - count($placed)),
            'placement_budget' => $placementBudget,
            'required_total' => (int) $requirements->sum('periods_per_week'),
            'subjects_configured' => $requirements->count(),
            'subjects_without_teacher' => $subjectsWithoutTeacher,
            'strict_room_mapping' => $rules['strict_room_mapping'],
        ];
        };

        // Whole-school runs place every class's REQUIRED periods first and
        // only then run the overflow fill / free marking for each class --
        // see autoGenerateWholeSchool(). Returning the finalizer lets the
        // caller defer it without duplicating all of this state.
        if ($deferFinalize) {
            return ['deferred' => true, 'finalize' => $finalize, 'placed_required' => count($placed)];
        }
        return $finalize();
    }

    /**
     * Attempt to place a protected, still-unplaced subject by evicting a
     * lower-priority occupant from a slot. Guards the same-day / adjacency
     * rule against the live state of $subjectDayPeriods (shared with the
     * main placement loop) so eviction can't scatter a protected subject
     * into a second, non-adjacent slot on a day it's already teaching.
     */
    private function tryEvictForProtected(
        TimetableSetting $setting,
        int $protectedSubjectId,
        TimetableSubjectPriority $protectedPriority,
        array &$unplaced,
        $constraints,
        $priorities,
        array $rules,
        array $slotPool,
        array $forcedFreeKeys,
        array &$teacherDaySlot,
        array &$crossOccupied,
        array &$roomOccupied,
        array $strictRoomMap,
        array $availableRoomIds,
        bool $includeRooms,
        array &$subjectDayPeriods,
        $lessonPeriods
    ): int {
        $needed = ($unplaced['needed'] ?? 0) - ($unplaced['placed'] ?? 0);
        if ($needed <= 0) return 0;

        $teacherEntry = SubjectTeacher::where('sessionid', $setting->session_id)
            ->when($setting->term_id, fn($q) => $q->where('termid', $setting->term_id))
            ->where('subjectid', $protectedSubjectId)
            ->first();
        $teacherId = $teacherEntry?->staffid;

        $placedCount = 0;

        foreach ($slotPool as $slot) {
            if ($placedCount >= $needed) break;

            $day = $slot['day'];
            $periodId = $slot['period_id'];
            $timeSig = $slot['time_sig'];
            $key = $day . '_' . $periodId;

            if (isset($forcedFreeKeys[$key])) continue;

            // =====================================================================
            // GUARD: don't place a second, non-adjacent occurrence of the
            // protected subject on a day it's already teaching. Mirrors the
            // same-day/adjacency check used in the main placement loop —
            // without it, eviction could scatter a protected subject across
            // multiple periods on one day (e.g. Period 1 and Period 6)
            // instead of only ever doubling into an adjacent period.
            // =====================================================================
            $existingToday = $subjectDayPeriods[$protectedSubjectId][$day] ?? [];
            if (!empty($existingToday)) {
                if (!$rules['join_double_periods']) {
                    continue;
                }
                $adjacentOk = false;
                foreach ($existingToday as $existingPeriodId) {
                    $next = $this->getNextLessonPeriod($lessonPeriods, $existingPeriodId);
                    $prev = $this->getPreviousLessonPeriod($lessonPeriods, $existingPeriodId);
                    if (($next && $next->id === $periodId) || ($prev && $prev->id === $periodId)) {
                        $adjacentOk = true;
                        break;
                    }
                }
                if (!$adjacentOk) continue;
            }
            // =====================================================================

            $occupant = TimetableSlot::where('setting_id', $setting->id)
                ->where('period_id', $periodId)
                ->where('day', $day)
                ->whereNotNull('subject_id')
                ->where('is_free', false)
                ->first();

            if (!$occupant) continue;
            if ($occupant->subject_id === $protectedSubjectId) continue;

            $occupantPriority = $priorities->get($occupant->subject_id);
            $occupantLevel = ($rules['priorities_active'] && $occupantPriority && $occupantPriority->use_priority)
                ? $occupantPriority->priority_level
                : 3;

            if ($occupantLevel <= $protectedPriority->priority_level) {
                continue;
            }

            if ($teacherId) {
                if (in_array($timeSig, $crossOccupied[$teacherId][$day] ?? [])) continue;
            }

            $roomPick = $this->pickRoomForLesson(
                $includeRooms, $strictRoomMap, $availableRoomIds,
                $protectedSubjectId, $day, $timeSig, $roomOccupied
            );

            if ($includeRooms && $rules['strict_room_mapping']
                && $roomPick['no_mapping'] && $rules['strict_room_mode'] === 'refuse') {
                continue;
            }

            $occupant->update([
                'subject_id' => null,
                'teacher_id' => null,
                'room_id' => null,
                'is_double' => false,
                'is_free' => true,
            ]);

            TimetableSlot::updateOrCreate(
                ['setting_id' => $setting->id, 'period_id' => $periodId, 'day' => $day],
                [
                    'subject_id' => $protectedSubjectId,
                    'teacher_id' => $teacherId,
                    'room_id' => $roomPick['room_id'],
                    'is_double' => false,
                    'is_free' => false,
                ]
            );

            // Record this placement so later iterations in this same
            // eviction pass (and any later evictions for this subject)
            // see it too.
            $subjectDayPeriods[$protectedSubjectId][$day][] = $periodId;

            if ($teacherId) {
                $teacherDaySlot[$teacherId][$day][] = $periodId;
                $crossOccupied[$teacherId][$day][] = $timeSig;
            }
            if ($roomPick['room_id']) {
                $roomOccupied[$roomPick['room_id']][$day][] = $timeSig;
            }

            $placedCount++;
        }

        return $placedCount;
    }

    private function countSubjectPlaced(array $placed, int $subjectId): int
    {
        $count = 0;
        foreach ($placed as $sid) {
            if ($sid === $subjectId) $count++;
        }
        return $count;
    }

    private function loadTeacherAvailability($subjectTeachers): array
    {
        $availabilityMap = [];
        $teacherIds = $subjectTeachers->flatten()->pluck('staffid')->filter()->unique()->values();
        if ($teacherIds->isNotEmpty()) {
            TeacherAvailability::whereIn('teacher_id', $teacherIds)->get()
                ->each(function ($a) use (&$availabilityMap) {
                    $availabilityMap[$a->teacher_id][$a->day][] = [
                        'start' => $a->start_time,
                        'end' => $a->end_time,
                        'is_available' => (bool) $a->is_available,
                    ];
                });
        }
        return $availabilityMap;
    }

    private function estimateClassSize(int $classId, int $sessionId, ?int $termId): ?int
    {
        $subjectclassIds = Subjectclass::where('schoolclassid', $classId)->pluck('id');
        if ($subjectclassIds->isEmpty()) return null;

        $max = SubjectRegistrationStatus::whereIn('subjectclassid', $subjectclassIds)
            ->where('sessionid', $sessionId)
            ->when($termId, fn($q) => $q->where('termid', $termId))
            ->select(DB::raw('COUNT(DISTINCT studentid) as cnt'))
            ->groupBy('subjectclassid')
            ->pluck('cnt')
            ->max();

        return $max ? (int) $max : null;
    }

    private function ensureConstraintsExist(TimetableSetting $setting): \Illuminate\Support\Collection
    {
        $constraints = $setting->constraints->keyBy('subject_id');

        if ($constraints->isNotEmpty()) {
            return $constraints;
        }

        $subjectTeachers = SubjectTeacher::where('sessionid', $setting->session_id)
            ->when($setting->term_id, fn ($q) => $q->where('termid', $setting->term_id))
            ->whereHas('subjectclass', fn ($q) => $q->where('schoolclassid', $setting->schoolclass_id))
            ->get()
            ->unique('subjectid')
            ->values();

        if ($subjectTeachers->isEmpty()) {
            return $constraints;
        }

        $dayMeta = $this->computeDayPeriodMeta($setting);
        $totalLessonSlots = 0;
        foreach ($dayMeta as $periodsForDay) {
            foreach ($periodsForDay as $meta) {
                if ($meta['applicable'] && $meta['effective_type'] === 'lesson') {
                    $totalLessonSlots++;
                }
            }
        }

        $freeTarget = $setting->free_periods_per_week ?? 0;
        $forcedFreeCount = count($setting->preferred_free_slots ?? []);
        $budget = max(0, $totalLessonSlots - max($freeTarget, $forcedFreeCount));

        if ($budget === 0) {
            $budget = max(1, count($subjectTeachers) * 2);
        }

        $subjectCount = $subjectTeachers->count();

        // Distribute the full $budget across the mapped subjects instead of
        // capping each subject's share at a fixed ceiling. The previous
        // "$base = max(1, min($base, 8))" clamp threw away any leftover
        // budget whenever base*subjectCount (+ remainder) fell short of
        // $budget -- for a class with only a few subjects mapped against a
        // large weekly $budget, most of the week's slots were never handed
        // to any subject. Those unassigned slots then fell through to the
        // unconditional "mark every unplaced slot free" tail of
        // runAutoGenerateCore(), producing "Free" periods even when
        // free_periods_per_week was 0. Spreading $budget round-robin here
        // (first pass guarantees every subject at least 1 period where the
        // budget allows it, further passes hand out what's left) keeps
        // every slot in $budget assigned to a real subject.
        $periodsPerWeekList = array_fill(0, $subjectCount, 0);
        $remainingBudget = $budget;
        for ($i = 0; $i < $subjectCount && $remainingBudget > 0; $i++) {
            $periodsPerWeekList[$i] = 1;
            $remainingBudget--;
        }
        while ($remainingBudget > 0 && $subjectCount > 0) {
            $progressed = false;
            for ($i = 0; $i < $subjectCount && $remainingBudget > 0; $i++) {
                $periodsPerWeekList[$i]++;
                $remainingBudget--;
                $progressed = true;
            }
            if (!$progressed) break;
        }

        $created = 0;
        foreach ($subjectTeachers->values() as $i => $st) {
            if (TimetableConstraint::where('setting_id', $setting->id)
                    ->where('subject_id', $st->subjectid)
                    ->exists()) {
                continue;
            }

            $periodsPerWeek = $periodsPerWeekList[$i] ?? 1;

            TimetableConstraint::create([
                'setting_id'                    => $setting->id,
                'subject_id'                    => $st->subjectid,
                'periods_per_week'              => $periodsPerWeek,
                'allow_double_period'           => false,
                'max_double_periods_per_week'   => 1,
                'is_compulsory'                 => true,
                'avoid_consecutive_double_days' => true,
            ]);
            $created++;
        }

        if ($created > 0) {
            $setting->load('constraints.subject');
        }

        return $setting->constraints->keyBy('subject_id');
    }

    private function isTeacherAvailableForPeriod(int $teacherId, string $day, int $periodId, TimetableSetting $setting, array $availabilityMap): bool
    {
        $windows = $availabilityMap[$teacherId][$day] ?? null;
        if (!$windows) return true;

        $period = $setting->periods->firstWhere('id', $periodId);
        if (!$period) return true;

        foreach ($windows as $w) {
            if (!$w['is_available']) continue;
            if ($period->start_time >= $w['start'] && $period->end_time <= $w['end']) return true;
        }
        return false;
    }

    private function buildWeightedSlotPool($days, TimetableSetting $setting, array $dayMeta): array
    {
        $pool = [];
        foreach ($days as $day) {
            foreach ($setting->periods as $period) {
                $m = $dayMeta[$day][$period->id] ?? null;
                if (!$m || $m['effective_type'] !== 'lesson' || !$m['applicable']) continue;

                $pool[] = [
                    'day'               => $day,
                    'period_id'         => $period->id,
                    'period_order'      => $period->order,
                    'time_sig'          => $this->periodTimeSignature($period),
                    'is_break_adjacent' => $this->isBreakAdjacent($period, $setting->periods),
                ];
            }
        }
        return $pool;
    }

    private function getNextLessonPeriod($lessonPeriods, int $currentPeriodId)
    {
        // "Next" only counts if it starts the instant the current period
        // ends. $lessonPeriods is filtered to type=lesson, so a break
        // period between two lessons is invisible to a plain
        // next-item-in-the-list walk -- e.g. Period 2 ending at 09:50 and
        // Period 3 starting at 10:05 would be treated as adjacent even
        // though a Short Break separates them. That let a subject get
        // "doubled" (or, via repeated calls, tripled) across a real break
        // -- e.g. a subject placed in Period 1, 2 AND 3 on the same day --
        // instead of only ever joining periods that are genuinely
        // back-to-back in wall-clock time.
        $found = false;
        $current = null;
        foreach ($lessonPeriods as $p) {
            if ($found) {
                return ($current && $current->end_time === $p->start_time) ? $p : null;
            }
            if ($p->id === $currentPeriodId) {
                $found = true;
                $current = $p;
            }
        }
        return null;
    }

    private function getPreviousLessonPeriod($lessonPeriods, int $currentPeriodId)
    {
        // Mirrors getNextLessonPeriod()'s time-contiguity check -- see the
        // comment there.
        $prev = null;
        foreach ($lessonPeriods as $p) {
            if ($p->id === $currentPeriodId) {
                return ($prev && $prev->end_time === $p->start_time) ? $prev : null;
            }
            $prev = $p;
        }
        return null;
    }

    // =========================================================================
    // TEACHER ASSIGNMENTS / VIEW
    // =========================================================================
    public function getTeacherAssignments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:schoolsession,id',
            'term_id'    => 'nullable|exists:schoolterm,id',
        ]);

        try {
            $query = Subjectclass::query()
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->where('subjectteacher.sessionid', $validated['session_id']);

            if (!empty($validated['term_id'])) {
                $query->where('subjectteacher.termid', $validated['term_id']);
            }

            $assignments = $query->select([
                'subjectclass.id as subjectclass_id',
                'subjectclass.subjectteacherid',
                'subject.id as subject_id',
                'subject.subject as subject_name',
                'subject.subject_code as subject_code',
                'schoolclass.id as schoolclass_id',
                'schoolclass.schoolclass as class_name',
                'schoolarm.arm as arm_name',
                'users.id as teacher_id',
                'users.name as teacher_name',
            ])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->orderBy('subject.subject')
            ->get();

            if ($assignments->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'teachers' => [],
                    'unassigned' => [],
                    'message' => 'No subject-class assignments found for this session/term.'
                ]);
            }

            $subjectclassIds = $assignments->pluck('subjectclass_id')->unique()->values();
            $studentCounts = SubjectRegistrationStatus::whereIn('subjectclassid', $subjectclassIds)
                ->where('sessionid', $validated['session_id'])
                ->when(!empty($validated['term_id']), fn($q, $termId) => $q->where('termid', $termId))
                ->select(['subjectclassid', DB::raw('COUNT(DISTINCT studentid) as cnt')])
                ->groupBy('subjectclassid')
                ->pluck('cnt', 'subjectclassid');

            $teacherIds = $assignments->pluck('teacher_id')->filter()->unique()->values();
            $teacherPictures = [];
            if ($teacherIds->isNotEmpty()) {
                $pictures = DB::table('staffpicture')
                    ->whereIn('staffid', $teacherIds)
                    ->get(['staffid', 'picture']);

                foreach ($pictures as $pic) {
                    $teacherPictures[$pic->staffid] = $pic->picture;
                }
            }

            $byTeacher = [];
            $unassigned = [];

            foreach ($assignments as $row) {
                $className = trim(($row->class_name ?? '') . ' ' . ($row->arm_name ?? ''));

                $data = [
                    'subjectclass_id' => $row->subjectclass_id,
                    'subject_id' => $row->subject_id,
                    'subject_name' => $row->subject_name ?? 'Unknown Subject',
                    'subject_code' => $row->subject_code ?? '',
                    'schoolclass_id' => $row->schoolclass_id,
                    'class_name' => $className ?: 'Unknown Class',
                    'registered_count' => (int) ($studentCounts[$row->subjectclass_id] ?? 0),
                ];

                if ($row->teacher_id) {
                    if (!isset($byTeacher[$row->teacher_id])) {
                        $byTeacher[$row->teacher_id] = [
                            'teacher_id' => $row->teacher_id,
                            'teacher_name' => $row->teacher_name ?? 'Unknown Teacher',
                            'teacher_picture' => isset($teacherPictures[$row->teacher_id])
                                ? asset('storage/staff_avatars/' . $teacherPictures[$row->teacher_id])
                                : asset('storage/staff_avatars/default.png'),
                            'assignments' => [],
                        ];
                    }
                    $byTeacher[$row->teacher_id]['assignments'][] = $data;
                } else {
                    $unassigned[] = $data;
                }
            }

            return response()->json([
                'success' => true,
                'teachers' => array_values($byTeacher),
                'unassigned' => $unassigned,
            ]);

        } catch (\Exception $e) {
            Log::error('getTeacherAssignments failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'validated' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load teacher assignments: ' . $e->getMessage(),
                'teachers' => [],
                'unassigned' => [],
            ], 500);
        }
    }

    public function teacherView(Request $request)
    {
        $teacherId = Auth::id();
        $pagetitle = 'My Timetable';

        $sessionId = $request->input('session_id')
            ?? Schoolsession::where('status', 'Current')->value('id')
            ?? Schoolsession::latest('id')->value('id');

        $termId = $request->input('term_id')
            ?? Schoolterm::where('status', true)->value('id')
            ?? Schoolterm::latest('id')->value('id');

        $classId = $request->input('class_id');

        $teacher = User::with('staffPicture')->find($teacherId);
        $teacherPicture = $teacher?->staffPicture
            ? asset('storage/staff_avatars/' . $teacher->staffPicture->picture)
            : null;

        $teacherClasses = SubjectTeacher::where('staffid', $teacherId)
            ->whereHas('subjectclass', fn($q) => $q->whereNotNull('schoolclassid'))
            ->with(['subjectclass.schoolclass', 'subjectclass.schoolclass.armRelation'])
            ->get()
            ->pluck('subjectclass.schoolclass')
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($class) {
                $class->full_name = $this->getClassName($class);
                return $class;
            });

        $slotsQuery = TimetableSlot::where('teacher_id', $teacherId)
            ->whereHas('setting', function($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
            })
            ->whereNotNull('subject_id')
            ->with(['period', 'subject', 'setting.schoolclass', 'setting.schoolclass.armRelation', 'setting.term', 'room']);

        if ($classId) {
            $slotsQuery->whereHas('setting', function($q) use ($classId) {
                $q->where('schoolclass_id', $classId);
            });
        }

        $slotsCollection = $slotsQuery->get();

        foreach ($slotsCollection as $slot) {
            $slot->class_full_name = $this->getClassName($slot->setting?->schoolclass);
            $slot->time_sig = $slot->period ? $this->periodTimeSignature($slot->period) : null;
        }

        $slots = $slotsCollection->groupBy('day');

        $combinedMap = [];
        foreach ($slotsCollection as $slot) {
            if (!$slot->time_sig) continue;
            $key = $slot->teacher_id . '|' . $slot->day . '|' . $slot->time_sig;
            $combinedMap[$key][] = $slot;
        }

        foreach ($combinedMap as $key => $slotGroup) {
            $count = count($slotGroup);
            if ($count <= 1) {
                $slotGroup[0]->combined_count = 1;
                $slotGroup[0]->conflict_count = 0;
                continue;
            }

            foreach ($slotGroup as $i => $slotA) {
                $conflictWithAnother = false;
                foreach ($slotGroup as $j => $slotB) {
                    if ($i === $j) continue;
                    if (!$this->isCombinedSession($slotA, $slotB)) {
                        $conflictWithAnother = true;
                        break;
                    }
                }
                $slotA->combined_count = $count;
                $slotA->conflict_count = $conflictWithAnother ? $count : 0;
            }
        }

        $conflictGroups = collect($combinedMap)
            ->map(fn($group) => collect($group))
            ->filter(fn($group) => $group->contains(fn($s) => ($s->conflict_count ?? 0) > 0));

        $allPeriods = TimetablePeriod::with('setting')
            ->whereIn(
                'setting_id',
                TimetableSetting::where('session_id', $sessionId)->where('is_preview', false)->pluck('id')
            )
            ->orderBy('start_time')
            ->get()
            ->unique(fn($p) => substr($p->start_time, 0, 5) . '|' . substr($p->end_time, 0, 5))
            ->values();

        $periodDayMeta = [];
        $metaCache = [];
        foreach ($allPeriods as $period) {
            if (!$period->setting) continue;
            $sid = $period->setting_id;
            if (!isset($metaCache[$sid])) {
                $metaCache[$sid] = $this->computeDayPeriodMeta($period->setting->load('periods'));
            }
            foreach (self::DAYS as $day) {
                $periodDayMeta[$period->id][$day] = $metaCache[$sid][$day][$period->id]
                    ?? ['applicable' => true, 'effective_type' => $period->type];
            }
        }

        $sessions = Schoolsession::orderByDesc('id')->get();
        $terms = Schoolterm::all();
        $days = self::DAYS;
        $upcomingSlots = $this->getUpcomingSlots($teacherId, $sessionId, $termId);
        $weeklySummary = $this->getWeeklySummary($teacherId, $sessionId, $termId);
        $todayDayName = date('l');
        $todaySlots = $slots[$todayDayName] ?? collect();

        // Only meaningful Mon-Fri (self::DAYS) since the grid has no
        // weekend columns to flag. Scoped to the session/term currently
        // being viewed, same as every other getHolidayForDate() call.
        $todayHoliday = in_array($todayDayName, self::DAYS, true)
            ? $this->getHolidayForDate(now(), $sessionId, $termId)
            : null;

        $icsUrl = URL::signedRoute('timetable.ics', ['teacherId' => $teacherId], now()->addYears(10));
        $webcalUrl = preg_replace('/^https?:\/\//', 'webcal://', $icsUrl);

        return view('timetable.teacher', compact(
            'pagetitle', 'slots', 'days', 'allPeriods', 'sessions', 'terms',
            'sessionId', 'termId', 'classId', 'teacherClasses',
            'upcomingSlots', 'weeklySummary', 'teacherPicture',
            'periodDayMeta', 'icsUrl', 'webcalUrl', 'todaySlots', 'conflictGroups',
            'todayDayName', 'todayHoliday'
        ));
    }

    private function getUpcomingSlots(int $teacherId, int $sessionId, ?int $termId = null): array
    {
        $dayMap     = ['monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 'thursday' => 3, 'friday' => 4];
        $today      = strtolower(now()->format('l'));
        $todayIndex = $dayMap[$today] ?? 0;
        $now        = Carbon::now();

        $slots = TimetableSlot::where('teacher_id', $teacherId)
            ->whereHas('setting', function ($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
            })
            ->whereNotNull('subject_id')
            ->with(['period', 'subject', 'setting.schoolclass', 'setting.schoolclass.armRelation', 'setting.term', 'room'])->get();

        return $slots
            ->map(function ($slot) use ($dayMap, $todayIndex, $now, $today) {
                $dIdx      = $dayMap[strtolower($slot->day)] ?? 0;
                $daysAhead = $dIdx >= $todayIndex ? $dIdx - $todayIndex : ($dIdx + 7 - $todayIndex);

                if ($daysAhead === 0 && strtolower($slot->day) === $today) {
                    try {
                        if (Carbon::createFromFormat('H:i:s', $slot->period->start_time)->lessThanOrEqualTo($now)) {
                            $daysAhead = 7;
                        }
                    } catch (\Exception $e) { }
                }

                return ['slot' => $slot, 'occur_date' => $now->copy()->addDays($daysAhead)->startOfDay(), 'sort' => $daysAhead];
            })
            ->filter(function ($item) {
                $slot    = $item['slot'];
                $holiday = $this->getHolidayForDate($item['occur_date'], $slot->setting?->session_id, $slot->setting?->term_id);
                if (!$holiday) return true;
                if ($holiday->is_full_day) return false;

                if ($holiday->cutoff_time) {
                    try {
                        $periodStart = Carbon::createFromFormat('H:i:s', $slot->period->start_time);
                        $cutoff      = Carbon::createFromFormat('H:i:s', $holiday->cutoff_time);
                        if ($periodStart->greaterThanOrEqualTo($cutoff)) return false;
                    } catch (\Exception $e) { }
                }
                return true;
            })
            ->sortBy('sort')
            ->take(6)
            ->map(fn($item) => [
                'day'     => $item['slot']->day,
                'date'    => $item['occur_date']->format('D, d M'),
                'period'  => $item['slot']->period?->name,
                'time'    => $this->formatTime($item['slot']->period?->start_time ?? '') . ' – ' . $this->formatTime($item['slot']->period?->end_time ?? ''),
                'subject' => $item['slot']->subject?->subject,
                'class'   => $this->getClassName($item['slot']->setting?->schoolclass),
                'room'    => $item['slot']->room?->room_name,
            ])
            ->values()->toArray();
    }

    private function getWeeklySummary(int $teacherId, int $sessionId, ?int $termId = null): array
    {
        $slots = TimetableSlot::where('teacher_id', $teacherId)
            ->whereHas('setting', function ($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
            })
            ->whereNotNull('subject_id')
            ->with(['period', 'subject', 'setting.schoolclass', 'setting.schoolclass.armRelation', 'room'])->get();

        $summary = [];
        foreach (self::DAYS as $day) {
            $ds = $slots->where('day', $day);
            $summary[$day] = [
                'count'    => $ds->count(),
                'subjects' => $ds->pluck('subject.subject')->filter()->unique()->values()->toArray(),
                'classes'  => $ds->map(fn($s) => $this->getClassName($s->setting?->schoolclass))->filter()->unique()->values()->toArray(),
            ];
        }
        return $summary;
    }

    public function exportTeacherTimetable(Request $request)
    {
        $teacherId = Auth::id();
        $sessionId = $request->input('session_id') ?? Schoolsession::where('status', 'Current')->value('id');
        $termId = $request->input('term_id')
            ?? Schoolterm::where('status', true)->value('id')
            ?? Schoolterm::latest('id')->value('id');
        $classId = $request->input('class_id');

        $query = TimetableSlot::where('teacher_id', $teacherId)
            ->whereHas('setting', function($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
            })
            ->whereNotNull('subject_id')
            ->with(['period', 'subject', 'setting.schoolclass', 'setting.schoolclass.armRelation', 'room']);

        if ($classId) {
            $query->whereHas('setting', fn($q) => $q->where('schoolclass_id', $classId));
        }

        $slots = $query->get();

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Day', 'Period', 'Start', 'End', 'Subject', 'Class', 'Room', 'Double']);

        foreach ($slots as $slot) {
            fputcsv($handle, [
                $slot->day,
                $slot->period?->name,
                substr($slot->period?->start_time ?? '', 0, 5),
                substr($slot->period?->end_time ?? '', 0, 5),
                $slot->subject?->subject,
                $this->getClassName($slot->setting?->schoolclass),
                $slot->room?->room_name,
                $slot->is_double ? 'Yes' : 'No',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="my-timetable-' . date('Y-m-d') . '.csv"');
    }

    private function scheduleNotification(int $teacherId, int $slotId, string $type): void
    {
        $teacher = User::find($teacherId);
        if (!$teacher || !$teacher->email) return;
        $slot = TimetableSlot::with(['period', 'subject', 'room'])->find($slotId);
        if (!$slot) return;

        TimetableNotification::create([
            'teacher_id'   => $teacherId,
            'slot_id'      => $slotId,
            'type'         => $type,
            'email'        => $teacher->email,
            'scheduled_at' => now(),
            'status'       => 'pending',
            'payload'      => json_encode([
                'day'     => $slot->day,
                'period'  => $slot->period?->name,
                'subject' => $slot->subject?->subject,
                'room'    => $slot->room?->room_name,
            ]),
        ]);
    }

    private function logTimetableChange(int $userId, string $action, string $modelType, ?int $modelId, $oldValues = null, $newValues = null): void
    {
        DB::table('timetable_audit_logs')->insert([
            'user_id'    => $userId,
            'action'     => $action,
            'model_type' => $modelType,
            'model_id'   => $modelId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    // EXPORT HELPERS
    // =========================================================================
    private function exportCsv($setting, $periods, $days, $grid, $className, $sessionName, $dayMeta)
    {
        $handle = fopen('php://temp', 'w+');
        $header = ['Period'];
        foreach ($days as $d) $header[] = $d;
        fputcsv($handle, $header);

        foreach ($periods as $period) {
            $row = [$period->name . ' (' . $this->formatTime($period->start_time) . '–' . $this->formatTime($period->end_time) . ')'];
            foreach ($days as $day) {
                $meta = $dayMeta[$day][$period->id] ?? null;
                if (!$meta || $meta['effective_type'] !== 'lesson' || !$meta['applicable']) {
                    $row[] = '—';
                    continue;
                }
                $slot = $grid[$period->id][$day] ?? null;
                if (!$slot || $slot['is_free']) {
                    $row[] = 'FREE';
                } else {
                    $parts = array_filter([$slot['subject'] ?? '', $slot['teacher'] ?? '', $slot['room'] ?? '']);
                    $row[] = implode(' | ', $parts);
                }
            }
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        $filename = 'timetable-' . str_replace(' ', '-', $className) . '-' . date('Y-m-d') . '.csv';
        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function exportPdf(
        $setting, $periods, $days, $grid, $subjectColors,
        $className, $sessionName, $termName, $orientation, $dayMeta,
        string $paperSize = self::DEFAULT_PAPER,
        string $paperDir  = 'landscape'
    ) {
        $pdf = Pdf::loadView('timetable.exports.pdf', array_merge(
            compact(
                'setting', 'periods', 'days', 'grid', 'subjectColors',
                'className', 'sessionName', 'termName', 'orientation', 'dayMeta'
            ),
            [
                'paperSize' => $paperSize,
                'bodyScale' => $this->paperBodyScale($paperSize),
                'schoolInfo' => SchoolInformation::getActiveSchool(),
                'dayColors' => self::DAY_COLORS,
                'generatedAt' => now()->format('d M Y, H:i'),
            ]
        ))->setPaper($paperSize, $paperDir);

        return $pdf->stream('timetable-' . str_replace(' ', '-', $className) . '-' . $paperSize . '.pdf');
    }

    private function exportWholeSchoolPdf(
        array $allTimetables,
        ?SchoolInformation $schoolInfo,
        ?Schoolsession $session,
        ?Schoolterm $term,
        string $orientation,
        array $overallStats = [],
        string $paperSize = self::DEFAULT_PAPER,
        string $paperDir  = 'landscape'
    ) {
        $sessionName = $session->session ?? 'Session';
        $termName    = $term?->term ?? 'All Terms';

        $pdf = Pdf::loadView('timetable.exports.whole-school', [
            'allTimetables' => $allTimetables,
            'schoolInfo'    => $schoolInfo,
            'sessionName'   => $sessionName,
            'termName'      => $termName,
            'orientation'   => $orientation,
            'dayColors'     => self::DAY_COLORS,
            'generatedAt'   => now()->format('d M Y, H:i'),
            'overallStats'  => $overallStats,
            'paperSize'     => $paperSize,
            'paperDir'      => $paperDir,
            'bodyScale'     => $this->paperBodyScale($paperSize),
        ])->setPaper($paperSize, $paperDir);

        $filename = 'whole-school-timetable-'
                  . str_replace([' ', '/'], '-', $sessionName)
                  . '-' . $paperSize . '.pdf';
        return $pdf->stream($filename);
    }

    // =========================================================================
    // STAFF ANALYTICS
    // =========================================================================
    private function buildStaffAnalytics(int $sessionId, ?int $termId): array
    {
        $slots = TimetableSlot::whereHas('setting', function ($q) use ($sessionId, $termId) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false);
                if ($termId) $q->where('term_id', $termId);
                else         $q->whereNull('term_id');
            })
            ->whereNotNull('teacher_id')
            ->where('is_free', false)
            ->whereNotNull('subject_id')
            ->with(['period', 'subject', 'teacher', 'teacher.staffPicture', 'room', 'setting.schoolclass', 'setting.schoolclass.armRelation'])
            ->get();

        $emptySummary = [
            'total_staff'           => 0,
            'total_periods'         => 0,
            'avg_periods_per_staff' => 0,
            'busiest_staff'         => null,
            'most_conflicted_staff' => null,
        ];

        if ($slots->isEmpty()) {
            return ['staff' => [], 'summary' => $emptySummary];
        }

        $conflictSlotIds = [];
        $grouped = $slots->filter(fn($s) => $s->period)
            ->groupBy(fn($s) => $s->teacher_id . '|' . $s->day . '|' . $this->periodTimeSignature($s->period));

        foreach ($grouped as $group) {
            if ($group->count() < 2) continue;
            $arr = $group->values();
            for ($i = 0; $i < $arr->count(); $i++) {
                for ($j = $i + 1; $j < $arr->count(); $j++) {
                    if (!$this->isCombinedSession($arr[$i], $arr[$j])) {
                        $conflictSlotIds[$arr[$i]->id] = true;
                        $conflictSlotIds[$arr[$j]->id] = true;
                    }
                }
            }
        }

        $staff = [];

        foreach ($slots->groupBy('teacher_id') as $teacherId => $teacherSlots) {
            $teacher = $teacherSlots->first()->teacher;
            if (!$teacher) continue;

            $classes   = [];
            $subjects  = [];
            $rooms     = [];
            $dailyLoad = array_fill_keys(self::DAYS, 0);
            $conflictCount = 0;

            foreach ($teacherSlots as $slot) {
                $className = $this->getClassName($slot->setting?->schoolclass);
                $classes[$className] = ($classes[$className] ?? 0) + 1;

                $subjectName = $slot->subject?->subject ?? 'Unknown';
                $subjects[$subjectName] = ($subjects[$subjectName] ?? 0) + 1;

                if ($slot->room) {
                    $roomName = $slot->room->room_name;
                    $rooms[$roomName] = ($rooms[$roomName] ?? 0) + 1;
                }

                if (isset($dailyLoad[$slot->day])) $dailyLoad[$slot->day]++;
                if (isset($conflictSlotIds[$slot->id])) $conflictCount++;
            }

            arsort($classes);
            arsort($subjects);
            arsort($rooms);

            $maxLoad    = max($dailyLoad);
            $busiestDay = $maxLoad > 0 ? array_search($maxLoad, $dailyLoad) : null;

            $staff[] = [
                'id'             => (int) $teacherId,
                'name'           => $teacher->name,
                'email'          => $teacher->email,
                'picture'        => $teacher->staffPicture
                    ? asset('storage/staff_avatars/' . $teacher->staffPicture->picture)
                    : asset('storage/staff_avatars/default.png'),
                'total_periods'  => $teacherSlots->count(),
                'class_count'    => count($classes),
                'subject_count'  => count($subjects),
                'classes'        => $classes,
                'subjects'       => $subjects,
                'rooms'          => $rooms,
                'daily_load'     => $dailyLoad,
                'busiest_day'    => $busiestDay,
                'conflict_count' => $conflictCount,
            ];
        }

        usort($staff, fn($a, $b) => $b['total_periods'] <=> $a['total_periods']);

        $totalStaff   = count($staff);
        $totalPeriods = array_sum(array_column($staff, 'total_periods'));
        $busiest      = $staff[0] ?? null;
        $mostConflicted = collect($staff)->sortByDesc('conflict_count')->first();

        return [
            'staff'   => $staff,
            'summary' => [
                'total_staff'           => $totalStaff,
                'total_periods'         => $totalPeriods,
                'avg_periods_per_staff' => $totalStaff > 0 ? round($totalPeriods / $totalStaff, 1) : 0,
                'busiest_staff'         => $busiest ? ['name' => $busiest['name'], 'periods' => $busiest['total_periods']] : null,
                'most_conflicted_staff' => ($mostConflicted && $mostConflicted['conflict_count'] > 0)
                    ? ['name' => $mostConflicted['name'], 'conflicts' => $mostConflicted['conflict_count']]
                    : null,
            ],
        ];
    }

    private function buildClassStats(array $grid, $periods, array $days, array $dayMeta): array
    {
        $totalSlots = 0; $filled = 0;
        $subjects = []; $teachers = []; $rooms = [];

        foreach ($days as $day) {
            foreach ($periods as $period) {
                $meta = $dayMeta[$day][$period->id] ?? null;
                if (!$meta || $meta['effective_type'] !== 'lesson' || !$meta['applicable']) continue;

                $totalSlots++;
                $slot = $grid[$period->id][$day] ?? null;
                if ($slot && !($slot['is_free'] ?? true)) {
                    $filled++;
                    if (!empty($slot['subject']) && $slot['subject'] !== '—') $subjects[$slot['subject']] = true;
                    if (!empty($slot['teacher']))                             $teachers[$slot['teacher']] = true;
                    if (!empty($slot['room']))                                $rooms[$slot['room']]       = true;
                }
            }
        }

        return [
            'total_slots'   => $totalSlots,
            'filled_slots'  => $filled,
            'free_slots'    => max(0, $totalSlots - $filled),
            'fill_rate'     => $totalSlots > 0 ? (int) round(($filled / $totalSlots) * 100) : 0,
            'subject_count' => count($subjects),
            'teacher_count' => count($teachers),
            'room_count'    => count($rooms),
            'teacher_names' => array_keys($teachers),
        ];
    }

    private function buildWholeSchoolExportData($sessionId, $termId): array
    {
        $settings = TimetableSetting::with(['session', 'term', 'periods'])
            ->join('schoolclass', 'schoolclass.id', '=', 'timetable_settings.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['timetable_settings.*', 'schoolclass.schoolclass as _class_name', 'schoolarm.arm as _arm_name'])
            ->where('timetable_settings.session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('timetable_settings.term_id', $termId))
            ->where('timetable_settings.is_active', true)
            ->where('timetable_settings.is_preview', false)
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')->get();

        $schoolInfo = SchoolInformation::getActiveSchool();
        $session    = Schoolsession::find($sessionId);
        $term       = $termId ? Schoolterm::find($termId) : null;

        if ($settings->isEmpty()) {
            return [[], $schoolInfo, $session, $term, [], ['staff' => [], 'summary' => []]];
        }

        $allTimetables = [];

        foreach ($settings as $setting) {
            $className = trim(($setting->_class_name ?? '') . ' ' . ($setting->_arm_name ?? '')) ?: 'Unknown Class';
            $slots     = TimetableSlot::where('setting_id', $setting->id)
                ->with(['subject', 'teacher', 'period', 'room'])->get();

            $grid = [];
            foreach ($slots as $slot) {
                $grid[$slot->period_id][$slot->day] = [
                    'subject'    => $slot->subject?->subject ?? ($slot->is_free ? 'FREE' : '—'),
                    'teacher'    => $slot->teacher?->name ?? '',
                    'teacher_id' => $slot->teacher_id,
                    'room'       => ($slot->room_id && $slot->room) ? $slot->room->room_name : '',
                    'is_free'    => $slot->is_free ?? !$slot->subject_id,
                ];
            }

            $days    = $setting->active_days ?? self::DAYS;
            $dayMeta = $this->computeDayPeriodMeta($setting);

            $allTimetables[] = [
                'setting_id' => $setting->id,
                'class_name' => $className,
                'periods'    => $setting->periods,
                'grid'       => $grid,
                'days'       => $days,
                'day_meta'   => $dayMeta,
                'stats'      => $this->buildClassStats($grid, $setting->periods, $days, $dayMeta),
            ];
        }

        $overallStats = [
            'total_classes'   => count($allTimetables),
            'total_teachers'  => collect($allTimetables)->pluck('stats.teacher_names')->flatten()->filter()->unique()->count(),
            'avg_fill_rate'   => (int) round(collect($allTimetables)->avg(fn($t) => $t['stats']['fill_rate'])),
            'total_conflicts' => $this->countConflictsForScope((int) $sessionId, $termId ? (int) $termId : null)['total'],
        ];

        $staffAnalytics = $this->buildStaffAnalytics((int) $sessionId, $termId ? (int) $termId : null);

        return [$allTimetables, $schoolInfo, $session, $term, $overallStats, $staffAnalytics];
    }

    private function buildMergedGridData($sessionId, $termId): array
    {
        $settings = TimetableSetting::with(['periods'])
            ->join('schoolclass', 'schoolclass.id', '=', 'timetable_settings.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['timetable_settings.*', 'schoolclass.schoolclass as _class_name', 'schoolarm.arm as _arm_name'])
            ->where('timetable_settings.session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('timetable_settings.term_id', $termId))
            ->where('timetable_settings.is_active', true)
            ->where('timetable_settings.is_preview', false)
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
            ->get();

        $schoolInfo = SchoolInformation::getActiveSchool();
        $session    = Schoolsession::find($sessionId);
        $term       = $termId ? Schoolterm::find($termId) : null;

        if ($settings->isEmpty()) {
            return ['rows' => [], 'days' => [], 'classColors' => [], 'classList' => [],
                    'schoolInfo' => $schoolInfo, 'sessionName' => $session->session ?? 'Session',
                    'termName' => $term?->term ?? 'All Terms', 'generatedAt' => now()->format('d M Y, H:i'),
                    'dayColors' => self::DAY_COLORS,
                    'staffAnalytics' => ['staff' => [], 'summary' => []]];
        }

        $classColorPalette = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899','#14B8A6','#84CC16','#6366F1','#D946EF'];

        $classData    = [];
        $classColors  = [];
        $colorIdx     = 0;
        $allDaysUnion = [];
        $timeSlotMap  = [];

        foreach ($settings as $setting) {
            $className = trim(($setting->_class_name ?? '') . ' ' . ($setting->_arm_name ?? '')) ?: 'Unknown Class';

            if (array_key_exists($className, $classColors)) {
                continue;
            }

            $classColors[$className] = $classColorPalette[$colorIdx++ % count($classColorPalette)];

            $slots = TimetableSlot::where('setting_id', $setting->id)->with(['subject', 'teacher', 'room'])->get();
            $grid  = [];
            foreach ($slots as $slot) {
                $grid[$slot->period_id][$slot->day] = [
                    'subject'    => $slot->subject?->subject,
                    'teacher'    => $slot->teacher?->name,
                    'teacher_id' => $slot->teacher_id,
                    'room'       => $slot->room?->room_name,
                    'is_free'    => $slot->is_free ?? !$slot->subject_id,
                    'is_double'  => $slot->is_double,
                ];
            }

            $days    = $setting->active_days ?? self::DAYS;
            $allDaysUnion = array_unique(array_merge($allDaysUnion, $days));
            $dayMeta = $this->computeDayPeriodMeta($setting);

            foreach ($setting->periods as $period) {
                $key = substr($period->start_time, 0, 5) . '-' . substr($period->end_time, 0, 5);
                if (!isset($timeSlotMap[$key])) {
                    $timeSlotMap[$key] = [
                        'start' => substr($period->start_time, 0, 5),
                        'end'   => substr($period->end_time, 0, 5),
                        'names' => [],
                    ];
                }
                $timeSlotMap[$key]['names'][] = $period->name;
            }

            $classData[$className] = ['grid' => $grid, 'days' => $days, 'dayMeta' => $dayMeta, 'periods' => $setting->periods];
        }

        $dayOrder = self::DAYS;
        usort($allDaysUnion, fn($a, $b) => array_search($a, $dayOrder) <=> array_search($b, $dayOrder));
        uasort($timeSlotMap, fn($a, $b) => strcmp($a['start'], $b['start']));

        $mergedRows = [];
        foreach ($timeSlotMap as $info) {
            $label = collect($info['names'])->countBy()->sortDesc()->keys()->first() ?? 'Period';

            $rowEntries = [];
            foreach ($allDaysUnion as $day) {
                $entries = []; $anyBreak = false; $applicable = false;

                foreach ($classData as $className => $cd) {
                    if (!in_array($day, $cd['days'])) continue;
                    $matchedPeriod = $cd['periods']->first(fn($p) =>
                        substr($p->start_time, 0, 5) === $info['start'] && substr($p->end_time, 0, 5) === $info['end']
                    );
                    if (!$matchedPeriod) continue;

                    $meta = $cd['dayMeta'][$day][$matchedPeriod->id] ?? null;
                    if (!$meta || !$meta['applicable']) continue;
                    $applicable = true;

                    if ($meta['effective_type'] !== 'lesson') { $anyBreak = true; continue; }

                    $slotInfo = $cd['grid'][$matchedPeriod->id][$day] ?? null;
                    if (!$slotInfo || $slotInfo['is_free']) continue;

                    $entries[] = [
                        'class'       => $className,
                        'subject'     => $slotInfo['subject'] ?? '—',
                        'teacher'     => $slotInfo['teacher'] ?? '',
                        'teacher_id'  => $slotInfo['teacher_id'] ?? null,
                        'room'        => $slotInfo['room'] ?? '',
                        'color'       => $classColors[$className],
                        'is_conflict' => false,
                        'is_double'   => $slotInfo['is_double'] ?? false,
                    ];
                }

                $byTeacherInCell = collect($entries)->filter(fn($e) => $e['teacher_id'])->groupBy('teacher_id');
                foreach ($byTeacherInCell as $tId => $group) {
                    if ($group->count() > 1) {
                        foreach ($entries as &$entryRef) {
                            if ($entryRef['teacher_id'] == $tId) $entryRef['is_conflict'] = true;
                        }
                        unset($entryRef);
                    }
                }

                $rowEntries[$day] = [
                    'entries'     => $entries,
                    'is_break'    => $anyBreak && empty($entries),
                    'applicable'  => $applicable,
                ];
            }

            $mergedRows[] = ['label' => $label, 'time' => $info['start'] . ' – ' . $info['end'], 'days' => $rowEntries];
        }

        $this->markDoublePeriodEntries($mergedRows, $allDaysUnion);

        return [
            'rows'           => $mergedRows,
            'days'           => $allDaysUnion,
            'classColors'    => $classColors,
            'classList'      => array_keys($classColors),
            'schoolInfo'     => $schoolInfo,
            'sessionName'    => $session->session ?? 'Session',
            'termName'       => $term?->term ?? 'All Terms',
            'generatedAt'    => now()->format('d M Y, H:i'),
            'dayColors'      => self::DAY_COLORS,
            'staffAnalytics' => $this->buildStaffAnalytics((int) $sessionId, $termId ? (int) $termId : null),
        ];
    }

    /**
     * For the overlay ("days as rows") merged grid, mark each entry that is
     * part of a double period as either the first half ('start') or second
     * half ('continue'), by matching it against the same class/subject/
     * teacher/room in the immediately following period row, same day.
     * A td in this layout can stack several classes' entries at once, so
     * this marks individual entries rather than merging whole table cells.
     */
    private function markDoublePeriodEntries(array &$rows, array $days): void
    {
        $rowCount = count($rows);
        for ($i = 0; $i < $rowCount - 1; $i++) {
            foreach ($days as $day) {
                $currentEntries = $rows[$i]['days'][$day]['entries'] ?? [];
                $nextEntries    = $rows[$i + 1]['days'][$day]['entries'] ?? [];
                if (empty($currentEntries) || empty($nextEntries)) continue;

                foreach ($currentEntries as $ci => $curEntry) {
                    if (empty($curEntry['is_double']) || !empty($curEntry['double_role'])) continue;

                    foreach ($nextEntries as $ni => $nextEntry) {
                        if (empty($nextEntry['is_double']) || !empty($nextEntry['double_role'])) continue;

                        $matches = $curEntry['class'] === $nextEntry['class']
                            && $curEntry['subject'] === $nextEntry['subject']
                            && ($curEntry['teacher_id'] ?? null) === ($nextEntry['teacher_id'] ?? null)
                            && $curEntry['room'] === $nextEntry['room'];

                        if ($matches) {
                            $rows[$i]['days'][$day]['entries'][$ci]['double_role']     = 'start';
                            $rows[$i + 1]['days'][$day]['entries'][$ni]['double_role'] = 'continue';
                            break;
                        }
                    }
                }
            }
        }
    }

    // =========================================================================
    // MERGED GRID — CLASSES AS COLUMNS
    // =========================================================================
    private function buildMergedGridByClassColumns($sessionId, $termId): array
    {
        $settings = TimetableSetting::with(['periods'])
            ->join('schoolclass', 'schoolclass.id', '=', 'timetable_settings.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['timetable_settings.*', 'schoolclass.schoolclass as _class_name', 'schoolarm.arm as _arm_name'])
            ->where('timetable_settings.session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('timetable_settings.term_id', $termId))
            ->where('timetable_settings.is_active', true)
            ->where('timetable_settings.is_preview', false)
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
            ->get();

        $schoolInfo = SchoolInformation::getActiveSchool();
        $session    = Schoolsession::find($sessionId);
        $term       = $termId ? Schoolterm::find($termId) : null;

        if ($settings->isEmpty()) {
            return [
                'rows' => [], 'classList' => [], 'classColors' => [], 'days' => [],
                'schoolInfo' => $schoolInfo,
                'sessionName' => $session->session ?? 'Session',
                'termName'    => $term?->term ?? 'All Terms',
                'generatedAt' => now()->format('d M Y, H:i'),
                'dayColors'   => self::DAY_COLORS,
            ];
        }

        $palette = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899','#14B8A6','#84CC16','#6366F1','#D946EF'];
        $classList   = [];
        $classColors = [];
        $classData   = [];
        $colorIdx    = 0;

        foreach ($settings as $setting) {
            $className = trim(($setting->_class_name ?? '') . ' ' . ($setting->_arm_name ?? '')) ?: 'Unknown Class';

            if (in_array($className, $classList, true)) {
                continue;
            }
            $classList[] = $className;
            $classColors[$className] = $palette[$colorIdx++ % count($palette)];

            $slots = TimetableSlot::where('setting_id', $setting->id)->with(['subject', 'teacher', 'room'])->get();
            $grid = [];
            foreach ($slots as $slot) {
                $grid[$slot->period_id][$slot->day] = [
                    'subject'    => $slot->subject?->subject,
                    'teacher'    => $slot->teacher?->name,
                    'teacher_id' => $slot->teacher_id,
                    'room'       => $slot->room?->room_name,
                    'is_free'    => $slot->is_free ?? !$slot->subject_id,
                ];
            }

            $classData[$className] = [
                'grid'    => $grid,
                'days'    => $setting->active_days ?? self::DAYS,
                'dayMeta' => $this->computeDayPeriodMeta($setting),
                'periods' => $setting->periods,
            ];
        }

        $rows = [];
        foreach (self::DAYS as $day) {
            $anyClassActiveToday = collect($classData)->contains(fn($cd) => in_array($day, $cd['days']));
            if (!$anyClassActiveToday) continue;

            $seenTimes = [];
            foreach ($classData as $cd) {
                if (!in_array($day, $cd['days'])) continue;
                foreach ($cd['periods'] as $p) {
                    $key = substr($p->start_time, 0, 5) . '-' . substr($p->end_time, 0, 5);
                    if (isset($seenTimes[$key])) continue;
                    $seenTimes[$key] = [
                        'label' => $p->name,
                        'start' => substr($p->start_time, 0, 5),
                        'end'   => substr($p->end_time, 0, 5),
                    ];
                }
            }
            uasort($seenTimes, fn($a, $b) => strcmp($a['start'], $b['start']));

            foreach ($seenTimes as $info) {
                $cells = [];
                foreach ($classData as $className => $cd) {
                    if (!in_array($day, $cd['days'])) {
                        $cells[$className] = ['state' => 'na'];
                        continue;
                    }
                    $matched = $cd['periods']->first(fn($p) =>
                        substr($p->start_time, 0, 5) === $info['start'] && substr($p->end_time, 0, 5) === $info['end']
                    );
                    if (!$matched) {
                        $cells[$className] = ['state' => 'na'];
                        continue;
                    }

                    $meta = $cd['dayMeta'][$day][$matched->id] ?? null;
                    if (!$meta || !$meta['applicable']) {
                        $cells[$className] = ['state' => 'na'];
                        continue;
                    }
                    if ($meta['effective_type'] !== 'lesson') {
                        $cells[$className] = ['state' => 'break'];
                        continue;
                    }

                    $slotInfo = $cd['grid'][$matched->id][$day] ?? null;
                    if (!$slotInfo || $slotInfo['is_free']) {
                        $cells[$className] = ['state' => 'free'];
                        continue;
                    }
                    $cells[$className] = array_merge($slotInfo, ['state' => 'lesson']);
                }

                $rows[] = [
                    'day'   => $day,
                    'label' => $info['label'],
                    'time'  => $info['start'] . ' – ' . $info['end'],
                    'cells' => $cells,
                ];
            }
        }

        return [
            'rows'        => $rows,
            'classList'   => $classList,
            'classColors' => $classColors,
            'days'        => self::DAYS,
            'schoolInfo'  => $schoolInfo,
            'sessionName' => $session->session ?? 'Session',
            'termName'    => $term?->term ?? 'All Terms',
            'generatedAt' => now()->format('d M Y, H:i'),
            'dayColors'   => self::DAY_COLORS,
        ];
    }

    // =========================================================================
    // MERGED GRID — CLASSES AS ROWS
    // =========================================================================
    private function buildMergedGridByClassRows($sessionId, $termId): array
    {
        $settings = TimetableSetting::with(['periods'])
            ->join('schoolclass', 'schoolclass.id', '=', 'timetable_settings.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['timetable_settings.*', 'schoolclass.schoolclass as _class_name', 'schoolarm.arm as _arm_name'])
            ->where('timetable_settings.session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('timetable_settings.term_id', $termId))
            ->where('timetable_settings.is_active', true)
            ->where('timetable_settings.is_preview', false)
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
            ->get();

        $schoolInfo = SchoolInformation::getActiveSchool();
        $session    = Schoolsession::find($sessionId);
        $term       = $termId ? Schoolterm::find($termId) : null;

        if ($settings->isEmpty()) {
            return [
                'rows' => [], 'classList' => [], 'classColors' => [], 'days' => self::DAYS,
                'schoolInfo'  => $schoolInfo,
                'sessionName' => $session->session ?? 'Session',
                'termName'    => $term?->term ?? 'All Terms',
                'generatedAt' => now()->format('d M Y, H:i'),
                'dayColors'   => self::DAY_COLORS,
            ];
        }

        $palette     = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899','#14B8A6','#84CC16','#6366F1','#D946EF'];
        $classList   = [];
        $classColors = [];
        $classData   = [];
        $colorIdx    = 0;

        foreach ($settings as $setting) {
            $className = trim(($setting->_class_name ?? '') . ' ' . ($setting->_arm_name ?? '')) ?: 'Unknown Class';

            if (in_array($className, $classList, true)) {
                continue;
            }
            $classList[] = $className;
            $classColors[$className] = $palette[$colorIdx++ % count($palette)];

            $slots = TimetableSlot::where('setting_id', $setting->id)->with(['subject', 'teacher', 'room'])->get();
            $grid = [];
            foreach ($slots as $slot) {
                $grid[$slot->period_id][$slot->day] = [
                    'subject'    => $slot->subject?->subject,
                    'teacher'    => $slot->teacher?->name,
                    'teacher_id' => $slot->teacher_id,
                    'room'       => $slot->room?->room_name,
                    'is_free'    => $slot->is_free ?? !$slot->subject_id,
                    'is_double'  => $slot->is_double,
                ];
            }

            $classData[$className] = [
                'grid'    => $grid,
                'days'    => $setting->active_days ?? self::DAYS,
                'dayMeta' => $this->computeDayPeriodMeta($setting),
                'periods' => $setting->periods,
            ];
        }

        $timeSlotMap = [];
        foreach ($classData as $cd) {
            foreach ($cd['periods'] as $p) {
                $key = substr($p->start_time, 0, 5) . '-' . substr($p->end_time, 0, 5);
                if (!isset($timeSlotMap[$key])) {
                    $timeSlotMap[$key] = [
                        'label' => $p->name,
                        'start' => substr($p->start_time, 0, 5),
                        'end'   => substr($p->end_time, 0, 5),
                    ];
                }
            }
        }
        uasort($timeSlotMap, fn($a, $b) => strcmp($a['start'], $b['start']));

        $rows = [];
        foreach ($classData as $className => $cd) {
            $rowCount = count($timeSlotMap);

            foreach ($timeSlotMap as $info) {
                $cells = [];

                foreach (self::DAYS as $day) {
                    if (!in_array($day, $cd['days'])) {
                        $cells[$day] = ['state' => 'na'];
                        continue;
                    }
                    $matched = $cd['periods']->first(fn($p) =>
                        substr($p->start_time, 0, 5) === $info['start'] && substr($p->end_time, 0, 5) === $info['end']
                    );
                    if (!$matched) {
                        $cells[$day] = ['state' => 'na'];
                        continue;
                    }
                    $meta = $cd['dayMeta'][$day][$matched->id] ?? null;
                    if (!$meta || !$meta['applicable']) {
                        $cells[$day] = ['state' => 'na'];
                        continue;
                    }
                    if ($meta['effective_type'] !== 'lesson') {
                        $cells[$day] = ['state' => 'break'];
                        continue;
                    }
                    $slot = $cd['grid'][$matched->id][$day] ?? null;
                    if (!$slot || $slot['is_free']) {
                        $cells[$day] = ['state' => 'free'];
                        continue;
                    }
                    $cells[$day] = array_merge($slot, ['state' => 'lesson']);
                }

                $rows[] = [
                    'class'       => $className,
                    'class_color' => $classColors[$className],
                    'label'       => $info['label'],
                    'time'        => $info['start'] . ' – ' . $info['end'],
                    'cells'       => $cells,
                    'is_first_row'=> false,
                    'rowspan'     => $rowCount,
                ];
            }
        }

        $seen = [];
        foreach ($rows as &$row) {
            if (!isset($seen[$row['class']])) {
                $row['is_first_row'] = true;
                $seen[$row['class']] = true;
            }
        }
        unset($row);

        return [
            'rows'        => $rows,
            'days'        => self::DAYS,
            'classList'   => $classList,
            'classColors' => $classColors,
            'schoolInfo'  => $schoolInfo,
            'sessionName' => $session->session ?? 'Session',
            'termName'    => $term?->term ?? 'All Terms',
            'generatedAt' => now()->format('d M Y, H:i'),
            'dayColors'   => self::DAY_COLORS,
        ];
    }

    // =========================================================================
    // MERGED GRID — DAYS AS ROWS, GROUPED BY CLASS  ★ NEW ★
    // =========================================================================
    /**
     * Produces one row per (day, class), giving each class its own visually
     * distinct row inside the day block. This is what you get when you want
     * "Days as Rows" but with a Class column between Day and Period.
     *
     * Structure:
     *   - $daySections: [{ day, day_color, rowspan, class_rows: [
     *         { class, class_color, cells: [ {state:'lesson'|'break'|'free'|'na', ...} ] }
     *     ]}]
     *   - $timeSlots:  distinct { label, start, end } columns
     */
    private function buildMergedGridByDayClassRows($sessionId, $termId): array
    {
        $settings = TimetableSetting::with(['periods'])
            ->join('schoolclass', 'schoolclass.id', '=', 'timetable_settings.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['timetable_settings.*', 'schoolclass.schoolclass as _class_name', 'schoolarm.arm as _arm_name'])
            ->where('timetable_settings.session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('timetable_settings.term_id', $termId))
            ->where('timetable_settings.is_active', true)
            ->where('timetable_settings.is_preview', false)
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
            ->get();

        $schoolInfo = SchoolInformation::getActiveSchool();
        $session    = Schoolsession::find($sessionId);
        $term       = $termId ? Schoolterm::find($termId) : null;

        if ($settings->isEmpty()) {
            return [
                'daySections' => [],
                'timeSlots'   => [],
                'days'        => [],
                'classList'   => [],
                'classColors' => [],
                'schoolInfo'  => $schoolInfo,
                'sessionName' => $session->session ?? 'Session',
                'termName'    => $term?->term ?? 'All Terms',
                'generatedAt' => now()->format('d M Y, H:i'),
                'dayColors'   => self::DAY_COLORS,
            ];
        }

        $palette     = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899','#14B8A6','#84CC16','#6366F1','#D946EF'];
        $classList   = [];
        $classColors = [];
        $classData   = [];
        $colorIdx    = 0;
        $daysUnion   = [];

        foreach ($settings as $setting) {
            $className = trim(($setting->_class_name ?? '') . ' ' . ($setting->_arm_name ?? '')) ?: 'Unknown Class';

            if (in_array($className, $classList, true)) {
                continue;
            }
            $classList[] = $className;
            $classColors[$className] = $palette[$colorIdx++ % count($palette)];

            $slots = TimetableSlot::where('setting_id', $setting->id)->with(['subject', 'teacher', 'room'])->get();
            $grid  = [];
            foreach ($slots as $slot) {
                $grid[$slot->period_id][$slot->day] = [
                    'subject'    => $slot->subject?->subject,
                    'teacher'    => $slot->teacher?->name,
                    'teacher_id' => $slot->teacher_id,
                    'room'       => $slot->room?->room_name,
                    'is_free'    => $slot->is_free ?? !$slot->subject_id,
                    'is_double'  => $slot->is_double,
                ];
            }

            $days = $setting->active_days ?? self::DAYS;
            $daysUnion = array_unique(array_merge($daysUnion, $days));

            $classData[$className] = [
                'grid'    => $grid,
                'days'    => $days,
                'dayMeta' => $this->computeDayPeriodMeta($setting),
                'periods' => $setting->periods,
            ];
        }

        // Union of every distinct period time across all classes.
        $timeSlotMap = [];
        foreach ($classData as $cd) {
            foreach ($cd['periods'] as $p) {
                $key = substr($p->start_time, 0, 5) . '-' . substr($p->end_time, 0, 5);
                if (!isset($timeSlotMap[$key])) {
                    $timeSlotMap[$key] = [
                        'label' => $p->name,
                        'start' => substr($p->start_time, 0, 5),
                        'end'   => substr($p->end_time, 0, 5),
                    ];
                }
            }
        }
        uasort($timeSlotMap, fn($a, $b) => strcmp($a['start'], $b['start']));

        // Sort days canonically.
        $dayOrder = self::DAYS;
        usort($daysUnion, fn($a, $b) => array_search($a, $dayOrder) <=> array_search($b, $dayOrder));

        // Build one section per day, with one row per class.
        $daySections = [];
        foreach ($daysUnion as $day) {
            $classRows = [];
            foreach ($classData as $className => $cd) {
                $cells = [];
                foreach ($timeSlotMap as $info) {
                    if (!in_array($day, $cd['days'])) {
                        $cells[] = ['state' => 'na'];
                        continue;
                    }
                    $matched = $cd['periods']->first(fn($p) =>
                        substr($p->start_time, 0, 5) === $info['start']
                        && substr($p->end_time, 0, 5) === $info['end']
                    );
                    if (!$matched) {
                        $cells[] = ['state' => 'na'];
                        continue;
                    }
                    $meta = $cd['dayMeta'][$day][$matched->id] ?? null;
                    if (!$meta || !$meta['applicable']) {
                        $cells[] = ['state' => 'na'];
                        continue;
                    }
                    if ($meta['effective_type'] !== 'lesson') {
                        $cells[] = ['state' => 'break'];
                        continue;
                    }
                    $slot = $cd['grid'][$matched->id][$day] ?? null;
                    if (!$slot || $slot['is_free']) {
                        $cells[] = ['state' => 'free'];
                        continue;
                    }
                    $cells[] = array_merge($slot, ['state' => 'lesson']);
                }

                $classRows[] = [
                    'class'       => $className,
                    'class_color' => $classColors[$className],
                    'cells'       => $cells,
                ];
            }
            $daySections[] = [
                'day'        => $day,
                'day_color'  => self::DAY_COLORS[$day] ?? '#334155',
                'class_rows' => $classRows,
                'rowspan'    => count($classRows),
            ];
        }

        return [
            'daySections' => $daySections,
            'timeSlots'   => array_values($timeSlotMap),
            'days'        => $daysUnion,
            'classList'   => $classList,
            'classColors' => $classColors,
            'schoolInfo'  => $schoolInfo,
            'sessionName' => $session->session ?? 'Session',
            'termName'    => $term?->term ?? 'All Terms',
            'generatedAt' => now()->format('d M Y, H:i'),
            'dayColors'   => self::DAY_COLORS,
        ];
    }

    // =========================================================================
    // EXPORT — SINGLE CLASS
    // =========================================================================
    public function export(Request $request, int $settingId)
    {
        $validated = $request->validate([
            'format'      => 'nullable|in:csv,pdf',
            'orientation' => 'nullable|in:horizontal,vertical',
            'paper'       => 'nullable|in:' . implode(',', self::PAPER_SIZES),
        ]);

        $format      = $validated['format'] ?? 'csv';
        $orientation = $validated['orientation'] ?? 'horizontal';
        [$paperSize, $paperDir] = $this->resolvePaper($validated['paper'] ?? null, $orientation);

        $setting = TimetableSetting::with(['periods', 'session', 'term'])->findOrFail($settingId);

        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->where('schoolclass.id', $setting->schoolclass_id)->first();
        $setting->setRelation('schoolclass', $schoolclass);

        $className   = $this->getClassName($setting->schoolclass);
        $sessionName = $setting->session->session ?? 'Session';
        $termName    = $setting->term?->term ?? 'All Terms';
        $days        = $setting->active_days ?? self::DAYS;
        $dayMeta     = $this->computeDayPeriodMeta($setting);

        $slots = TimetableSlot::where('setting_id', $settingId)
            ->with(['subject', 'teacher', 'room'])->get();

        $grid = [];
        foreach ($slots as $slot) {
            $grid[$slot->period_id][$slot->day] = [
                'subject'    => $slot->subject?->subject ?? ($slot->is_free ? 'FREE' : '—'),
                'teacher'    => $slot->teacher?->name ?? '',
                'teacher_id' => $slot->teacher_id,
                'room'       => $slot->room?->room_name ?? '',
                'is_free'    => $slot->is_free ?? !$slot->subject_id,
            ];
        }

        if ($format === 'csv') {
            return $this->exportCsv($setting, $setting->periods, $days, $grid, $className, $sessionName, $dayMeta);
        }

        $subjectColors = [];
        $palette = self::SUBJECT_PALETTE;
        $i = 0;
        foreach ($slots as $slot) {
            if ($slot->subject_id && !isset($subjectColors[$slot->subject_id])) {
                $subjectColors[$slot->subject_id] = $palette[$i++ % count($palette)];
            }
        }

        return $this->exportPdf(
            $setting, $setting->periods, $days, $grid, $subjectColors,
            $className, $sessionName, $termName, $orientation, $dayMeta,
            $paperSize, $paperDir
        );
    }

    public function exportWholeSchool(Request $request)
    {
        $validated = $request->validate([
            'session_id'  => 'required|exists:schoolsession,id',
            'term_id'     => 'nullable|exists:schoolterm,id',
            'orientation' => 'nullable|in:horizontal,vertical',
            'paper'       => 'nullable|in:' . implode(',', self::PAPER_SIZES),
        ]);

        $orientation = $validated['orientation'] ?? 'horizontal';
        [$paperSize, $paperDir] = $this->resolvePaper($validated['paper'] ?? null, $orientation);

        [$allTimetables, $schoolInfo, $session, $term, $overallStats] =
            $this->buildWholeSchoolExportData($validated['session_id'], $validated['term_id'] ?? null);

        if (empty($allTimetables)) return response()->json(['error' => 'No timetables found'], 404);

        return $this->exportWholeSchoolPdf(
            $allTimetables, $schoolInfo, $session, $term,
            $orientation, $overallStats, $paperSize, $paperDir
        );
    }

    public function exportWholeSchoolWeb(Request $request)
    {
        $validated = $request->validate([
            'session_id'  => 'required|exists:schoolsession,id',
            'term_id'     => 'nullable|exists:schoolterm,id',
            'orientation' => 'nullable|in:horizontal,vertical',
            'paper'       => 'nullable|in:' . implode(',', self::PAPER_SIZES),
        ]);

        $orientation = $validated['orientation'] ?? 'horizontal';

        [$allTimetables, $schoolInfo, $session, $term, $overallStats, $staffAnalytics] =
            $this->buildWholeSchoolExportData($validated['session_id'], $validated['term_id'] ?? null);

        if (empty($allTimetables)) abort(404, 'No timetables found for this session/term.');

        return view('timetable.exports.whole-school-web', array_merge(
            compact('allTimetables', 'schoolInfo', 'session', 'term', 'overallStats', 'staffAnalytics'),
            [
                'pagetitle'   => 'Whole School Timetable',
                'orientation' => $orientation,
                'sessionName' => $session->session ?? 'Session',
                'termName'    => $term?->term ?? 'All Terms',
                'dayColors'   => self::DAY_COLORS,
                'generatedAt' => now()->format('d M Y, H:i'),
            ]
        ));
    }

    public function exportMergedGrid(Request $request)
    {
        $validated = $request->validate([
            'session_id'  => 'required|exists:schoolsession,id',
            'term_id'     => 'nullable|exists:schoolterm,id',
            'orientation' => 'nullable|in:horizontal,vertical',
            'paper'       => 'nullable|in:' . implode(',', self::PAPER_SIZES),
            'layout'      => 'nullable|in:overlay,overlay_horizontal,overlay_vertical,class_columns,class_rows,class_grid,days_as_rows_by_class',
        ]);

        $orientation = $validated['orientation'] ?? 'horizontal';
        [$paperSize, $paperDir] = $this->resolvePaper($validated['paper'] ?? null, $orientation);

        $layout = $validated['layout'] ?? 'overlay_horizontal';
        $layout = match ($layout) {
            'overlay'    => 'overlay_horizontal',
            'class_grid' => 'class_columns',
            default      => $layout,
        };

        $sessionId = $validated['session_id'];
        $termId    = $validated['term_id'] ?? null;

        switch ($layout) {
            case 'overlay_vertical':
                $data = $this->buildMergedGridData($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-vertical';
                break;

            case 'class_columns':
                $data = $this->buildMergedGridByClassColumns($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-class-columns';
                break;

            case 'class_rows':
                $data = $this->buildMergedGridByClassRows($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-class-rows';
                break;

            case 'days_as_rows_by_class':
                $data = $this->buildMergedGridByDayClassRows($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-days-by-class';
                break;

            case 'overlay_horizontal':
            default:
                $data = $this->buildMergedGridData($sessionId, $termId);
                $view = 'timetable.exports.merged-grid';
                break;
        }

        if (empty($data['rows']) && empty($data['daySections'])) return response()->json(['error' => 'No timetables found'], 404);

        $data['orientation'] = $orientation;
        $data['paperSize']   = $paperSize;
        $data['paperDir']    = $paperDir;
        $data['bodyScale']   = $this->paperBodyScale($paperSize);

        $pdf = Pdf::loadView($view, $data)->setPaper($paperSize, $paperDir);

        $filename = 'merged-timetable-'
                  . str_replace([' ', '/'], '-', $data['sessionName'])
                  . '-' . str_replace('_', '-', $layout) . '-' . $paperSize . '.pdf';
        return $pdf->stream($filename);
    }

    public function mergedGridWeb(Request $request)
    {
        $validated = $request->validate([
            'session_id'  => 'required|exists:schoolsession,id',
            'term_id'     => 'nullable|exists:schoolterm,id',
            'orientation' => 'nullable|in:horizontal,vertical',
            'paper'       => 'nullable|in:' . implode(',', self::PAPER_SIZES),
            'layout'      => 'nullable|in:overlay,overlay_horizontal,overlay_vertical,class_columns,class_rows,class_grid,days_as_rows_by_class',
        ]);

        $orientation = $validated['orientation'] ?? 'horizontal';
        $layout      = $validated['layout'] ?? 'overlay_horizontal';
        $layout      = match ($layout) {
            'overlay'    => 'overlay_horizontal',
            'class_grid' => 'class_columns',
            default      => $layout,
        };

        $sessionId = $validated['session_id'];
        $termId    = $validated['term_id'] ?? null;

        switch ($layout) {
            case 'overlay_vertical':
                $data = $this->buildMergedGridData($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-vertical-web';
                break;

            case 'class_columns':
                $data = $this->buildMergedGridByClassColumns($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-class-columns-web';
                break;

            case 'class_rows':
                $data = $this->buildMergedGridByClassRows($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-class-rows-web';
                break;

            case 'days_as_rows_by_class':
                $data = $this->buildMergedGridByDayClassRows($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-days-by-class-web';
                break;

            case 'overlay_horizontal':
            default:
                $data = $this->buildMergedGridData($sessionId, $termId);
                $view = 'timetable.exports.merged-grid-web';
                break;
        }

        if (empty($data['rows']) && empty($data['daySections'])) abort(404, 'No timetables found for this session/term.');

        return view($view, array_merge($data, [
            'pagetitle'   => 'Merged Timetable',
            'orientation' => $orientation,
            'layout'      => $layout,
        ]));
    }

    // =========================================================================
    // NOTIFICATIONS / PUBLISH / SUBSTITUTE / AVAILABILITY
    // =========================================================================
    public function sendNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id' => 'required|exists:timetable_settings,id',
            'type'       => 'required|in:daily_summary,weekly_preview,change_alert',
        ]);

        $setting = TimetableSetting::with([
            'slots.teacher', 'slots.teacher.staffPicture',
            'slots.subject', 'slots.period', 'slots.room',
            'schoolclass', 'session', 'term',
        ])->findOrFail($validated['setting_id']);

        if ($validated['type'] === 'daily_summary') {
            $todayHoliday = $this->getHolidayForDate(now(), $setting->session_id, $setting->term_id);
            if ($todayHoliday && $todayHoliday->is_full_day) {
                return response()->json(['success' => true, 'message' => "Skipped — today is a holiday ({$todayHoliday->title}).", 'sent' => 0]);
            }
        }

        $sent = $this->dispatchNotifications($setting, $validated['type']);

        return response()->json(['success' => true, 'message' => "Notifications sent to {$sent} teacher(s).", 'sent' => $sent]);
    }

    public function publishSetting(int $settingId): JsonResponse
    {
        $setting = TimetableSetting::findOrFail($settingId);
        $setting->update(['is_published' => true, 'published_at' => now(), 'published_by' => Auth::id()]);
        $this->logTimetableChange(Auth::id(), 'update', 'TimetableSetting', $setting->id, null, ['is_published' => true]);

        return response()->json(['success' => true, 'message' => 'Timetable published and locked.']);
    }

    public function unpublishSetting(int $settingId): JsonResponse
    {
        $setting = TimetableSetting::findOrFail($settingId);
        $setting->update(['is_published' => false, 'published_at' => null, 'published_by' => null]);
        $this->logTimetableChange(Auth::id(), 'update', 'TimetableSetting', $setting->id, null, ['is_published' => false]);

        return response()->json(['success' => true, 'message' => 'Timetable unpublished. It can now be edited.']);
    }

    public function publishAndNotify(Request $request): JsonResponse
    {
        $validated = $request->validate(['setting_id' => 'required|exists:timetable_settings,id']);

        $setting = TimetableSetting::with([
            'slots.teacher', 'slots.teacher.staffPicture',
            'slots.subject', 'slots.period', 'slots.room',
            'schoolclass', 'session', 'term',
        ])->findOrFail($validated['setting_id']);

        $setting->update(['is_published' => true, 'published_at' => now(), 'published_by' => Auth::id()]);
        $this->logTimetableChange(Auth::id(), 'update', 'TimetableSetting', $setting->id, null, ['is_published' => true]);

        $sent = $this->dispatchNotifications($setting, 'weekly_preview');

        return response()->json([
            'success' => true,
            'message' => "Timetable published and notifications sent to {$sent} teacher(s).",
            'sent'    => $sent,
        ]);
    }

    public function publishAndSaveSnapshot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id' => 'required|exists:timetable_settings,id',
            'name'       => 'required|string|min:2|max:150',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $setting = TimetableSetting::with(['periods', 'constraints', 'slots'])->findOrFail($validated['setting_id']);

        DB::beginTransaction();
        try {
            $setting->update(['is_published' => true, 'published_at' => now(), 'published_by' => Auth::id()]);
            $this->logTimetableChange(Auth::id(), 'update', 'TimetableSetting', $setting->id, null, ['is_published' => true]);

            $result   = $this->replicateSettingDeep($setting, [
                'is_published'           => true,
                'published_at'           => now(),
                'published_by'           => Auth::id(),
                'is_snapshot'            => true,
                'snapshot_of_setting_id' => $setting->id,
                'generation_name'        => $validated['name'],
                'generation_notes'       => $validated['notes'] ?? $setting->generation_notes,
                'generation_seed'        => $setting->generation_seed,
                'generated_by'           => $setting->generated_by ?? Auth::id(),
                'generated_at'           => $setting->generated_at ?? now(),
            ]);
            $snapshot = $result['setting'];

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => "Saved and locked as \"{$validated['name']}\".",
                'snapshot_id' => $snapshot->id,
                'setting_id'  => $setting->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('publishAndSaveSnapshot failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSavedTimetables(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id'     => 'nullable|exists:schoolsession,id',
            'term_id'        => 'nullable|exists:schoolterm,id',
            'schoolclass_id' => 'nullable|exists:schoolclass,id',
        ]);

        $snapshots = TimetableSetting::with(['session', 'term', 'generator'])
            ->where('is_snapshot', true)
            ->when($validated['session_id'] ?? null, fn($q, $v) => $q->where('session_id', $v))
            ->when($validated['term_id'] ?? null, fn($q, $v) => $q->where('term_id', $v))
            ->when($validated['schoolclass_id'] ?? null, fn($q, $v) => $q->where('schoolclass_id', $v))
            ->orderByDesc('generated_at')
            ->get();

        $classIds = $snapshots->pluck('schoolclass_id')->unique()->filter();
        $classes  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->whereIn('schoolclass.id', $classIds)->get()->keyBy('id');

        $list = $snapshots->map(function ($s) use ($classes) {
            return [
                'id'                => $s->id,
                'name'              => $s->generation_name,
                'notes'             => $s->generation_notes,
                'class_name'        => $this->getClassName($classes[$s->schoolclass_id] ?? null),
                'session'           => $s->session?->session,
                'term'              => $s->term?->term,
                'generated_by'      => $s->generator?->name,
                'generated_at'      => optional($s->generated_at)->format('d M Y, H:i'),
                'seed'              => $s->generation_seed,
                'source_setting_id' => $s->snapshot_of_setting_id,
            ];
        });

        return response()->json(['success' => true, 'saved_timetables' => $list]);
    }

    private function dispatchNotifications(TimetableSetting $setting, string $type): int
    {
        $byTeacher = $setting->slots->whereNotNull('teacher_id')->groupBy('teacher_id');
        $sent = 0;

        foreach ($byTeacher as $teacherId => $teacherSlots) {
            $teacher = $teacherSlots->first()->teacher;
            if (!$teacher || !$teacher->email) continue;

            $notifData = [
                'teacher'         => $teacher->name,
                'teacher_picture' => $teacher->staffPicture ? asset('storage/staff_avatars/' . $teacher->staffPicture->picture) : null,
                'class'           => $this->getClassName($setting->schoolclass),
                'session'         => $setting->session->session ?? '',
                'term'            => $setting->term?->term ?? '',
                'slots'           => $teacherSlots->map(fn($s) => [
                    'day'     => $s->day,
                    'period'  => $s->period?->name,
                    'time'    => $this->formatTime($s->period?->start_time ?? '') . ' – ' . $this->formatTime($s->period?->end_time ?? ''),
                    'subject' => $s->subject?->subject,
                    'room'    => $s->room?->room_name,
                ])->toArray(),
                'type'      => $type,
                'generated' => now()->format('d M Y H:i'),
            ];

            try {
                Mail::to($teacher->email)->send(new TimetableNotificationMail($notifData));
                foreach ($teacherSlots as $slot) {
                    TimetableNotification::create([
                        'teacher_id'   => $teacherId, 'slot_id'    => $slot->id,
                        'type'         => $type, 'email' => $teacher->email,
                        'scheduled_at' => now(), 'sent_at'   => now(),
                        'status'       => 'sent', 'payload'   => json_encode($notifData),
                    ]);
                }
                $sent++;
            } catch (\Exception $e) {
                Log::error('Timetable notification failed', ['teacher_id' => $teacherId, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    public function deleteSetting(Request $request, int $settingId): JsonResponse
    {
        $setting = TimetableSetting::findOrFail($settingId);

        if ($conflict = $this->versionConflictResponse($setting, $request->input('expected_updated_at'))) {
            return $conflict;
        }

        try { $this->logTimetableChange(Auth::id(), 'delete', 'TimetableSetting', $settingId, null, $setting->toArray()); }
        catch (\Exception $e) { Log::warning('Audit log failed: ' . $e->getMessage()); }
        $setting->delete();
        return response()->json(['success' => true]);
    }

    private function replicateSettingDeep(TimetableSetting $source, array $overrides = []): array
    {
        $new = $source->replicate();
        $new->is_published = false;
        $new->published_at = null;
        $new->published_by = null;
        $new->editing_by   = null;
        $new->editing_at   = null;
        $new->created_by   = Auth::id();
        $new->updated_by   = Auth::id();
        foreach ($overrides as $key => $value) {
            $new->{$key} = $value;
        }
        $new->save();

        $periodMap = [];
        foreach ($source->periods as $period) {
            $newPeriod             = $period->replicate();
            $newPeriod->setting_id = $new->id;
            $newPeriod->save();
            $periodMap[$period->id] = $newPeriod->id;
        }

        foreach ($source->constraints as $constraint) {
            $newC = $constraint->replicate();
            $newC->setting_id = $new->id;
            $newC->save();
        }

        foreach ($source->subjectPriorities as $priority) {
            $newP = $priority->replicate();
            $newP->setting_id = $new->id;
            $newP->save();
        }

        $slotIds = [];
        foreach ($source->slots as $slot) {
            if (!isset($periodMap[$slot->period_id])) continue;
            $newSlot = $slot->replicate();
            $newSlot->setting_id = $new->id;
            $newSlot->period_id  = $periodMap[$slot->period_id];
            $newSlot->save();
            $slotIds[] = $newSlot->id;
        }

        return ['setting' => $new, 'slot_ids' => $slotIds];
    }

    public function cloneSetting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'     => 'required|exists:timetable_settings,id',
            'new_session_id' => 'nullable|exists:schoolsession,id',
            'new_term_id'    => 'nullable|exists:schoolterm,id',
            'force'          => 'boolean',
        ]);

        $oldSetting = TimetableSetting::with(['periods', 'constraints', 'subjectPriorities', 'slots', 'editor'])->findOrFail($validated['setting_id']);

        if (empty($validated['force'])
            && $editingWarning = $this->editingRecentlyResponse($oldSetting, 'Clone anyway?')) {
            return $editingWarning;
        }

        DB::beginTransaction();
        try {
            $result     = $this->replicateSettingDeep($oldSetting, [
                'session_id' => $validated['new_session_id'] ?? $oldSetting->session_id,
                'term_id'    => $validated['new_term_id'] ?? $oldSetting->term_id,
            ]);
            $newSetting = $result['setting'];
            $newSlotIds = $result['slot_ids'];

            $conflicts = $this->detectTeacherConflictsForSetting($newSetting, $newSlotIds);

            DB::commit();
            return response()->json([
                'success'       => true,
                'setting_id'    => $newSetting->id,
                'conflicts'     => $conflicts,
                'has_conflicts' => count($conflicts) > 0,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function detectTeacherConflictsForSetting(TimetableSetting $setting, array $slotIds): array
    {
        if (empty($slotIds)) return [];

        $newSlots = TimetableSlot::whereIn('id', $slotIds)
            ->whereNotNull('teacher_id')->where('is_free', false)
            ->with(['period', 'subject', 'teacher'])->get();

        if ($newSlots->isEmpty()) return [];

        $sessionId = $setting->session_id;
        $termId    = $setting->term_id;

        $others = TimetableSlot::whereHas('setting', function ($q) use ($sessionId, $termId, $setting) {
                $q->where('session_id', $sessionId)->where('is_active', true)->where('is_preview', false)->where('id', '!=', $setting->id);
                if ($termId) $q->where('term_id', $termId);
                else         $q->whereNull('term_id');
            })
            ->whereIn('teacher_id', $newSlots->pluck('teacher_id')->unique())
            ->where('is_free', false)->whereNotNull('subject_id')
            ->with(['setting.schoolclass', 'subject', 'teacher', 'period'])
            ->get()
            ->filter(fn($s) => $s->period)
            ->groupBy(fn($s) => $s->teacher_id . '|' . $s->day . '|' . $this->periodTimeSignature($s->period));

        $conflicts = [];
        foreach ($newSlots as $newSlot) {
            if (!$newSlot->period) continue;
            $key = $newSlot->teacher_id . '|' . $newSlot->day . '|' . $this->periodTimeSignature($newSlot->period);
            if (!$others->has($key)) continue;

            $clashing = $others->get($key)->first();

            $isCombined = $newSlot->room_id && $clashing->room_id
                && $newSlot->room_id == $clashing->room_id
                && $newSlot->subject_id == $clashing->subject_id;

            if ($isCombined) continue;

            $newSlot->update(['notes' => trim(($newSlot->notes ? $newSlot->notes . "\n" : '')
                . "⚠️ Clone conflict: {$newSlot->teacher?->name} is already teaching in "
                . $this->getClassName($clashing->setting?->schoolclass) . " at this time.")]);

            $conflicts[] = [
                'teacher'             => $newSlot->teacher?->name ?? 'Unknown',
                'day'                 => $newSlot->day,
                'period'              => $newSlot->period?->name,
                'period_time'         => $this->formatTime($newSlot->period?->start_time ?? '') . ' – ' . $this->formatTime($newSlot->period?->end_time ?? ''),
                'subject_here'        => $newSlot->subject?->subject,
                'conflicting_class'   => $this->getClassName($clashing->setting?->schoolclass),
                'conflicting_subject' => $clashing->subject?->subject,
            ];
        }

        return $conflicts;
    }

    public function bulkUpdateSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_id'           => 'required|exists:timetable_settings,id',
            'updates'              => 'required|array',
            'updates.*.period_id'  => 'required|exists:timetable_periods,id',
            'updates.*.day'        => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'updates.*.subject_id' => 'nullable|exists:subject,id',
            'updates.*.teacher_id' => 'nullable|exists:users,id',
        ]);

        $setting = TimetableSetting::findOrFail($validated['setting_id']);
        if ($lock = $this->publishedLockResponse($setting)) return $lock;

        DB::beginTransaction();
        try {
            foreach ($validated['updates'] as $update) {
                TimetableSlot::updateOrCreate(
                    ['setting_id' => $validated['setting_id'], 'period_id' => $update['period_id'], 'day' => $update['day']],
                    ['subject_id' => $update['subject_id'] ?? null, 'teacher_id' => $update['teacher_id'] ?? null, 'is_free' => empty($update['subject_id'])]
                );
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function requestSubstitute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slot_id'               => 'required|exists:timetable_slots,id',
            'substitute_teacher_id' => 'required|exists:users,id',
            'reason'                => 'required|string|max:500',
            'assignment_date'       => 'required|date|after_or_equal:today',
        ]);

        $slot = TimetableSlot::with('setting')->findOrFail($validated['slot_id']);
        if ($slot->teacher_id != Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You can only request substitutes for your own classes'], 403);
        }

        $assignmentDate = Carbon::parse($validated['assignment_date']);
        $holiday = $this->getHolidayForDate($assignmentDate, $slot->setting?->session_id, $slot->setting?->term_id);
        if ($holiday && $holiday->is_full_day) {
            return response()->json([
                'success' => false,
                'message' => "That date ({$assignmentDate->format('d M Y')}) is a holiday — {$holiday->title}. No substitute needed.",
            ], 422);
        }

        $substitute = SubstituteAssignment::create([
            'original_teacher_id'   => Auth::id(),
            'substitute_teacher_id' => $validated['substitute_teacher_id'],
            'slot_id'               => $slot->id,
            'assignment_date'       => $validated['assignment_date'],
            'reason'                => $validated['reason'],
            'status'                => 'pending',
        ]);

        return response()->json(['success' => true, 'substitute' => $substitute]);
    }

    public function approveSubstitute(Request $request, int $substituteId): JsonResponse
    {
        $substitute = SubstituteAssignment::findOrFail($substituteId);
        if (!Auth::user()->can('Approve substitute')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $substitute->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
        $slot         = $substitute->slot;
        $originalName = $substitute->originalTeacher->name ?? 'Unknown';
        $slot->update([
            'teacher_id' => $substitute->substitute_teacher_id,
            'notes'      => ($slot->notes ? $slot->notes . "\n" : '') . "[SUBSTITUTE] Original: {$originalName}, Date: {$substitute->assignment_date}",
        ]);
        return response()->json(['success' => true]);
    }

    public function getSubstituteRequests(Request $request): JsonResponse
    {
        $requests = SubstituteAssignment::with(['originalTeacher', 'substituteTeacher', 'slot.period', 'slot.subject'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date,   fn($q) => $q->whereDate('assignment_date', $request->date))
            ->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);
        return response()->json(['success' => true, 'requests' => $requests]);
    }

    public function getAvailableSubstitutes(Request $request): JsonResponse
    {
        $substitutes = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->with('staffPicture')->get()
            ->map(fn($t) => [
                'id'      => $t->id,
                'name'    => $t->name,
                'email'   => $t->email,
                'picture' => $t->staffPicture
                    ? asset('storage/staff_avatars/' . $t->staffPicture->picture)
                    : asset('storage/staff_avatars/default.png'),
            ]);
        return response()->json(['success' => true, 'substitutes' => $substitutes]);
    }

    public function saveTeacherAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id'                  => 'required|exists:users,id',
            'availability'                => 'required|array',
            'availability.*.day'          => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'availability.*.start_time'   => 'required|date_format:H:i',
            'availability.*.end_time'     => 'required|date_format:H:i|after:start_time',
            'availability.*.is_available' => 'boolean',
        ]);
        foreach ($validated['availability'] as $avail) {
            TeacherAvailability::updateOrCreate(
                ['teacher_id' => $validated['teacher_id'], 'day' => $avail['day']],
                ['start_time' => $avail['start_time'], 'end_time' => $avail['end_time'], 'is_available' => $avail['is_available'] ?? true]
            );
        }
        return response()->json(['success' => true]);
    }

    public function getTeacherAvailability(int $teacherId): JsonResponse
    {
        return response()->json(['success' => true, 'availability' => TeacherAvailability::where('teacher_id', $teacherId)->get()]);
    }

    public function workloadDashboard(Request $request): JsonResponse
    {
        $sessionId = $request->session_id ?? Schoolsession::where('status', 'Current')->value('id');
        $teachers  = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->with('staffPicture')->get();

        $workloadData = [];
        foreach ($teachers as $teacher) {
            $slots = TimetableSlot::where('teacher_id', $teacher->id)
                ->whereHas('setting', fn($q) => $q->where('session_id', $sessionId)->where('is_preview', false))
                ->with(['setting.schoolclass', 'setting.schoolclass.armRelation', 'subject'])->get();

            $dailyLoad = [];
            foreach (self::DAYS as $day) $dailyLoad[$day] = $slots->where('day', $day)->count();

            $workloadData[] = [
                'teacher_id'       => $teacher->id,
                'teacher_name'     => $teacher->name,
                'teacher_picture'  => $teacher->staffPicture ? asset('storage/staff_avatars/' . $teacher->staffPicture->picture) : null,
                'periods_assigned' => $slots->count(),
                'classes_taught'   => $slots->map(fn($s) => $this->getClassName($s->setting?->schoolclass))->filter()->unique()->values(),
                'subjects_taught'  => $slots->pluck('subject.subject')->filter()->unique()->values(),
                'daily_load'       => $dailyLoad,
            ];
        }
        usort($workloadData, fn($a, $b) => $b['periods_assigned'] - $a['periods_assigned']);
        return response()->json(['success' => true, 'workload' => $workloadData]);
    }

    public function getClassSubjects(Request $request): JsonResponse
    {
        $subjectTeachers = SubjectTeacher::where('sessionid', $request->input('session_id'))
            ->when($request->input('term_id'), fn($q) => $q->where('termid', $request->input('term_id')))
            ->whereHas('subjectclass', fn($q) => $q->where('schoolclassid', $request->input('class_id')))
            ->with(['subject', 'staff', 'staff.staffPicture'])->get()
            ->map(fn($st) => [
                'subject_id'      => $st->subjectid,
                'subject_name'    => $st->subject?->subject,
                'subject_code'    => $st->subject?->subject_code,
                'teacher_id'      => $st->staffid,
                'teacher_name'    => $st->staff?->name,
                'teacher_picture' => $st->staff && $st->staff->staffPicture
                    ? asset('storage/staff_avatars/' . $st->staff->staffPicture->picture)
                    : asset('storage/staff_avatars/default.png'),
            ]);
        return response()->json(['success' => true, 'data' => $subjectTeachers]);
    }

    // =========================================================================
    // SAVED GENERATION RUNS
    // =========================================================================

    public function saveGenerationRun(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:150',
            'description'         => 'nullable|string|max:2000',
            'session_id'          => 'required|exists:schoolsession,id',
            'term_id'             => 'nullable|exists:schoolterm,id',
            'schoolclass_ids'     => 'nullable|array',
            'schoolclass_ids.*'   => 'exists:schoolclass,id',
            'wizard_input'        => 'required|array',
            'advanced_rules'      => 'nullable|array',
            'seed'                => 'nullable|integer',
        ]);

        $sessionId = (int) $validated['session_id'];
        $termId    = $validated['term_id'] ?? null;

        $settings = TimetableSetting::with(['periods', 'constraints', 'subjectPriorities', 'slots'])
            ->where('session_id', $sessionId)
            ->when($termId, fn($q) => $q->where('term_id', $termId))
            ->when(!$termId, fn($q) => $q->whereNull('term_id'))
            ->when($validated['schoolclass_ids'] ?? null, fn($q) => $q->whereIn('schoolclass_id', $validated['schoolclass_ids']))
            ->where('is_active', true)
            ->where('is_preview', false)
            ->get();

        if ($settings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No settings found for that scope. Generate a timetable first.',
            ], 422);
        }

        $classIds = $settings->pluck('schoolclass_id')->unique();
        $classes  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->whereIn('schoolclass.id', $classIds)
            ->get()
            ->keyBy('id');

        DB::beginTransaction();
        try {
            $totalPlaced = 0;
            $hadShortfalls = false;

            $run = TimetableGenerationRun::create([
                'name'           => $validated['name'],
                'description'    => $validated['description'] ?? null,
                'session_id'     => $sessionId,
                'term_id'        => $termId,
                'wizard_input'   => $validated['wizard_input'],
                'advanced_rules' => $validated['advanced_rules'] ?? null,
                'seed'           => $validated['seed'] ?? null,
                'status'         => 'success',
                'created_by'     => Auth::id(),
            ]);

            foreach ($settings as $setting) {
                $className = trim(($classes[$setting->schoolclass_id]->schoolclass ?? '')
                                 . ' ' . ($classes[$setting->schoolclass_id]->arm_name ?? ''));
                $className = $className ?: 'Class #' . $setting->schoolclass_id;

                $placed = $setting->slots->where('is_free', false)->whereNotNull('subject_id')->count();
                $noRoom = $setting->slots->where('is_free', false)->whereNotNull('subject_id')->whereNull('room_id')->count();

                $totalPlaced += $placed;
                if ($noRoom > 0) $hadShortfalls = true;

                TimetableGenerationRunSetting::create([
                    'run_id'                 => $run->id,
                    'source_setting_id'      => $setting->id,
                    'schoolclass_id'         => $setting->schoolclass_id,
                    'class_name'             => $className,
                    'setting_snapshot'       => $setting->toArray(),
                    'periods_snapshot'       => $setting->periods->toArray(),
                    'constraints_snapshot'   => $setting->constraints->toArray(),
                    'priorities_snapshot'    => $setting->subjectPriorities->toArray(),
                    'slots_snapshot'         => $setting->slots->toArray(),
                    'placed'                 => $placed,
                    'unplaced'               => 0,
                    'room_shortfall'         => $noRoom,
                ]);
            }

            $run->update([
                'class_count'     => $settings->count(),
                'total_placed'    => $totalPlaced,
                'total_shortfall' => 0,
                'total_conflicts' => 0,
                'status'          => $hadShortfalls ? 'shortfalls' : 'success',
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'run_id'   => $run->id,
                'run_code' => $run->run_code,
                'message'  => 'Generation run saved.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('saveGenerationRun failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function listGenerationRuns(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'          => 'nullable|string|max:150',
            'session_id' => 'nullable|exists:schoolsession,id',
            'term_id'    => 'nullable|exists:schoolterm,id',
            'class_id'   => 'nullable|exists:schoolclass,id',
            'creator_id' => 'nullable|exists:users,id',
            'status'     => 'nullable|in:success,shortfalls,reverted',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
            'per_page'   => 'nullable|integer|min:5|max:100',
        ]);

        $query = TimetableGenerationRun::with(['session', 'term', 'creator'])
            ->search($validated['q'] ?? null)
            ->status($validated['status'] ?? null)
            ->betweenDates($validated['date_from'] ?? null, $validated['date_to'] ?? null)
            ->when($validated['session_id'] ?? null, fn($q, $v) => $q->where('session_id', $v))
            ->when($validated['term_id'] ?? null,    fn($q, $v) => $q->where('term_id', $v))
            ->when($validated['creator_id'] ?? null, fn($q, $v) => $q->where('created_by', $v));

        if (!empty($validated['class_id'])) {
            $query->whereHas('snapshots', fn($q) =>
                $q->where('schoolclass_id', $validated['class_id'])
            );
        }

        $paginated = $query->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 20);

        $items = $paginated->getCollection()->map(function ($run) {
            return [
                'id'              => $run->id,
                'run_code'        => $run->run_code,
                'name'            => $run->name,
                'description'     => $run->description,
                'session'         => $run->session?->session,
                'term'            => $run->term?->term,
                'creator'         => $run->creator?->name,
                'class_count'     => $run->class_count,
                'total_placed'    => $run->total_placed,
                'total_shortfall' => $run->total_shortfall,
                'status'          => $run->status,
                'created_at'      => $run->created_at->format('d M Y, H:i'),
                'created_at_h'    => $run->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success'    => true,
            'runs'       => $items,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function showGenerationRun(Request $request, string $identifier): JsonResponse
    {
        $query = TimetableGenerationRun::with(['session', 'term', 'creator', 'snapshots.schoolclass']);
        if (ctype_digit($identifier)) {
            $query->where('id', (int) $identifier);
        } else {
            $query->where('run_code', $identifier);
        }

        $run = $query->first();
        if (!$run) {
            return response()->json(['success' => false, 'message' => 'Run not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'run'     => [
                'id'              => $run->id,
                'run_code'        => $run->run_code,
                'name'            => $run->name,
                'description'     => $run->description,
                'session'         => $run->session?->session,
                'term'            => $run->term?->term,
                'creator'         => $run->creator?->name,
                'class_count'     => $run->class_count,
                'total_placed'    => $run->total_placed,
                'total_shortfall' => $run->total_shortfall,
                'status'          => $run->status,
                'seed'            => $run->seed,
                'wizard_input'    => $run->wizard_input,
                'advanced_rules'  => $run->advanced_rules,
                'created_at'      => $run->created_at->format('d M Y, H:i'),
            ],
            'classes' => $run->snapshots->map(function ($s) {
                return [
                    'id'              => $s->id,
                    'schoolclass_id'  => $s->schoolclass_id,
                    'class_name'      => $s->class_name,
                    'placed'          => $s->placed,
                    'unplaced'        => $s->unplaced,
                    'room_shortfall'  => $s->room_shortfall,
                    'setting'         => $s->setting_snapshot,
                    'periods'         => $s->periods_snapshot,
                    'slots'           => $s->slots_snapshot,
                ];
            }),
        ]);
    }

    public function deleteGenerationRun(int $runId): JsonResponse
    {
        try {
            $run = TimetableGenerationRun::findOrFail($runId);
            $run->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function restoreGenerationRun(Request $request, int $runId): JsonResponse
    {
        $validated = $request->validate([
            'schoolclass_ids'   => 'nullable|array',
            'schoolclass_ids.*' => 'exists:schoolclass,id',
            'force_overwrite'   => 'boolean',
            'unpublish_locked'  => 'boolean',
        ]);

        $run = TimetableGenerationRun::with(['snapshots'])->findOrFail($runId);

        $snapshots = $run->snapshots;
        if (!empty($validated['schoolclass_ids'])) {
            $snapshots = $snapshots->whereIn('schoolclass_id', $validated['schoolclass_ids']);
        }

        if ($snapshots->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No matching classes in this run.'], 422);
        }

        $forceOverwrite  = !empty($validated['force_overwrite']);
        $unpublishLocked = !empty($validated['unpublish_locked']);

        $restored = [];
        $skipped  = [];

        DB::beginTransaction();
        try {
            foreach ($snapshots as $snapshot) {
                $live = TimetableSetting::where('schoolclass_id', $snapshot->schoolclass_id)
                    ->where('session_id', $run->session_id)
                    ->when($run->term_id, fn($q) => $q->where('term_id', $run->term_id))
                    ->when(!$run->term_id, fn($q) => $q->whereNull('term_id'))
                    ->where('is_preview', false)
                    ->first();

                if ($live && !$forceOverwrite) {
                    $runSavedAt = $run->created_at;
                    if ($live->updated_at && $live->updated_at->gt($runSavedAt)) {
                        $skipped[] = [
                            'schoolclass_id' => $snapshot->schoolclass_id,
                            'class_name'     => $snapshot->class_name,
                            'reason'         => 'edited_since_run',
                            'last_updated'   => $live->updated_at->format('d M Y, H:i'),
                            'updated_by'     => $live->updater?->name,
                        ];
                        continue;
                    }
                }

                if ($live && $live->is_published) {
                    if (!$unpublishLocked && !$forceOverwrite) {
                        $skipped[] = [
                            'schoolclass_id' => $snapshot->schoolclass_id,
                            'class_name'     => $snapshot->class_name,
                            'reason'         => 'published_locked',
                            'published_at'   => optional($live->published_at)->format('d M Y, H:i'),
                        ];
                        continue;
                    }
                    $live->update([
                        'is_published' => false,
                        'published_at' => null,
                        'published_by' => null,
                    ]);
                }

                if (!$live) {
                    $settingData = $snapshot->setting_snapshot;
                    unset($settingData['id'], $settingData['created_at'], $settingData['updated_at']);
                    $settingData['is_published'] = false;
                    $settingData['published_at'] = null;
                    $settingData['published_by'] = null;
                    $settingData['is_active']    = true;
                    $settingData['is_preview']   = false;
                    $settingData['created_by']   = Auth::id();
                    $settingData['updated_by']   = Auth::id();
                    $live = TimetableSetting::create($settingData);
                }

                TimetablePeriod::where('setting_id', $live->id)->delete();
                TimetableConstraint::where('setting_id', $live->id)->delete();
                TimetableSubjectPriority::where('setting_id', $live->id)->delete();
                TimetableSlot::where('setting_id', $live->id)->delete();

                $periodMap = [];
                foreach ($snapshot->periods_snapshot as $periodData) {
                    $oldId = $periodData['id'];
                    unset($periodData['id'], $periodData['setting_id'], $periodData['created_at'], $periodData['updated_at']);
                    $periodData['setting_id'] = $live->id;
                    $newPeriod = TimetablePeriod::create($periodData);
                    $periodMap[$oldId] = $newPeriod->id;
                }

                foreach ($snapshot->constraints_snapshot as $cData) {
                    unset($cData['id'], $cData['setting_id'], $cData['created_at'], $cData['updated_at']);
                    $cData['setting_id'] = $live->id;
                    TimetableConstraint::create($cData);
                }

                foreach ($snapshot->priorities_snapshot as $pData) {
                    unset($pData['id'], $pData['setting_id'], $pData['created_at'], $pData['updated_at']);
                    $pData['setting_id'] = $live->id;
                    TimetableSubjectPriority::create($pData);
                }

                foreach ($snapshot->slots_snapshot as $sData) {
                    if (!isset($periodMap[$sData['period_id']])) continue;
                    $sData['period_id']  = $periodMap[$sData['period_id']];
                    unset($sData['id'], $sData['setting_id'], $sData['created_at'], $sData['updated_at']);
                    $sData['setting_id'] = $live->id;
                    TimetableSlot::create($sData);
                }

                $settingMeta = $snapshot->setting_snapshot;
                $live->update([
                    'school_day_start'             => $settingMeta['school_day_start']             ?? $live->school_day_start,
                    'school_day_end'               => $settingMeta['school_day_end']               ?? $live->school_day_end,
                    'period_duration_minutes'      => $settingMeta['period_duration_minutes']      ?? $live->period_duration_minutes,
                    'short_break_duration_minutes' => $settingMeta['short_break_duration_minutes'] ?? $live->short_break_duration_minutes,
                    'long_break_duration_minutes'  => $settingMeta['long_break_duration_minutes']  ?? $live->long_break_duration_minutes,
                    'active_days'                  => $settingMeta['active_days']                  ?? $live->active_days,
                    'half_days'                    => $settingMeta['half_days']                    ?? null,
                    'advanced_rules'               => $settingMeta['advanced_rules']               ?? null,
                    'updated_by'                   => Auth::id(),
                ]);

                $restored[] = [
                    'schoolclass_id' => $snapshot->schoolclass_id,
                    'class_name'     => $snapshot->class_name,
                ];
            }

            $run->update([
                'last_restored_at' => now(),
                'last_restored_by' => Auth::id(),
                'restore_count'    => ($run->restore_count ?? 0) + 1,
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'restored' => $restored,
                'skipped'  => $skipped,
                'message'  => sprintf(
                    'Restored %d class(es).%s',
                    count($restored),
                    count($skipped) ? ' ' . count($skipped) . ' class(es) skipped — see details.' : ''
                ),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('restoreGenerationRun failed', ['run_id' => $runId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function compareGenerationRuns(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'run_a_id' => 'required|exists:timetable_generation_runs,id',
            'run_b_id' => 'required|exists:timetable_generation_runs,id|different:run_a_id',
        ]);

        $runA = TimetableGenerationRun::with('snapshots')->findOrFail($validated['run_a_id']);
        $runB = TimetableGenerationRun::with('snapshots')->findOrFail($validated['run_b_id']);

        $inputDiff = $this->diffRunArrays(
            $this->flattenRunInputForDiff($runA),
            $this->flattenRunInputForDiff($runB)
        );

        $classIdsA = $runA->snapshots->pluck('schoolclass_id')->unique();
        $classIdsB = $runB->snapshots->pluck('schoolclass_id')->unique();
        $shared    = $classIdsA->intersect($classIdsB)->values();
        $onlyInA   = $classIdsA->diff($classIdsB)->values();
        $onlyInB   = $classIdsB->diff($classIdsA)->values();

        $classNames = [];
        foreach (Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->whereIn('schoolclass.id', $classIdsA->merge($classIdsB))
            ->get() as $c) {
            $classNames[$c->id] = trim($c->schoolclass . ' ' . ($c->arm_name ?? ''));
        }

        $classDiffs = [];
        foreach ($shared as $classId) {
            $snapA = $runA->snapshots->firstWhere('schoolclass_id', $classId);
            $snapB = $runB->snapshots->firstWhere('schoolclass_id', $classId);

            $classDiffs[] = $this->diffRunClassGrids(
                $classId,
                $classNames[$classId] ?? "Class #$classId",
                $snapA,
                $snapB
            );
        }

        $summary = [
            'identical_cells' => 0,
            'differing_cells' => 0,
            'only_in_a_cells' => 0,
            'only_in_b_cells' => 0,
        ];
        foreach ($classDiffs as $cd) {
            foreach ($cd['cells'] as $cell) {
                $summary[$cell['state'] . '_cells'] = ($summary[$cell['state'] . '_cells'] ?? 0) + 1;
            }
        }

        return response()->json([
            'success' => true,
            'run_a'   => [
                'id'         => $runA->id,
                'run_code'   => $runA->run_code,
                'name'       => $runA->name,
                'created_at' => $runA->created_at->format('d M Y, H:i'),
                'creator'    => $runA->creator?->name,
                'classes'    => $classIdsA->count(),
                'placed'     => $runA->total_placed,
            ],
            'run_b'   => [
                'id'         => $runB->id,
                'run_code'   => $runB->run_code,
                'name'       => $runB->name,
                'created_at' => $runB->created_at->format('d M Y, H:i'),
                'creator'    => $runB->creator?->name,
                'classes'    => $classIdsB->count(),
                'placed'     => $runB->total_placed,
            ],
            'input_diff'     => $inputDiff,
            'only_in_a'      => $onlyInA->map(fn($id) => ['id' => $id, 'name' => $classNames[$id] ?? "Class #$id"])->values(),
            'only_in_b'      => $onlyInB->map(fn($id) => ['id' => $id, 'name' => $classNames[$id] ?? "Class #$id"])->values(),
            'shared_classes' => $classDiffs,
            'summary'        => $summary,
        ]);
    }

    private function flattenRunInputForDiff(TimetableGenerationRun $run): array
    {
        $flat = [];
        foreach (($run->wizard_input ?? []) as $key => $value) {
            $flat["wizard.$key"] = is_array($value) ? json_encode($value) : (string) $value;
        }
        foreach (($run->advanced_rules ?? []) as $key => $value) {
            $flat["advanced.$key"] = is_array($value) ? json_encode($value) : (string) $value;
        }
        return $flat;
    }

    private function diffRunArrays(array $a, array $b): array
    {
        $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
        $diffs = [];
        foreach ($keys as $key) {
            $va = $a[$key] ?? null;
            $vb = $b[$key] ?? null;
            if ($va !== $vb) {
                $diffs[] = ['key' => $key, 'a' => $va, 'b' => $vb];
            }
        }
        return $diffs;
    }

    private function diffRunClassGrids(int $classId, string $className, $snapA, $snapB): array
    {
        $periodsA = collect($snapA?->periods_snapshot ?? [])->keyBy('id');
        $periodsB = collect($snapB?->periods_snapshot ?? [])->keyBy('id');

        $slotsByKey = function ($snapshot) {
            $map = [];
            foreach (($snapshot?->slots_snapshot ?? []) as $slot) {
                $map[$slot['period_id'] . '|' . $slot['day']] = $slot;
            }
            return $map;
        };
        $slotsA = $slotsByKey($snapA);
        $slotsB = $slotsByKey($snapB);

        $cells = [];
        $allKeys = array_unique(array_merge(array_keys($slotsA), array_keys($slotsB)));

        foreach ($allKeys as $key) {
            $sa = $slotsA[$key] ?? null;
            $sb = $slotsB[$key] ?? null;

            $sig = function ($s) {
                if (!$s) return null;
                return [
                    'subject_id' => $s['subject_id'] ?? null,
                    'teacher_id' => $s['teacher_id'] ?? null,
                    'room_id'    => $s['room_id']    ?? null,
                    'is_free'    => (bool) ($s['is_free'] ?? false),
                    'is_double'  => (bool) ($s['is_double'] ?? false),
                ];
            };

            $saSig = $sig($sa);
            $sbSig = $sig($sb);

            if ($saSig === $sbSig) {
                $state = 'identical';
            } elseif ($saSig && !$sbSig) {
                $state = 'only_in_a';
            } elseif ($sbSig && !$saSig) {
                $state = 'only_in_b';
            } else {
                $state = 'differing';
            }

            [$periodId, $day] = explode('|', $key);

            $cells[] = [
                'period_id' => (int) $periodId,
                'day'       => $day,
                'state'     => $state,
                'a'         => $saSig,
                'b'         => $sbSig,
            ];
        }

        return [
            'schoolclass_id' => $classId,
            'class_name'     => $className,
            'cells'          => $cells,
            'periods_a'      => $periodsA->values(),
            'periods_b'      => $periodsB->values(),
        ];
    }

    public function exportGenerationRun(Request $request, int $runId)
    {
        $validated = $request->validate([
            'format'        => 'nullable|in:pdf,web',
            'mode'          => 'nullable|in:per_class,merged',
            'orientation'   => 'nullable|in:horizontal,vertical',
            'paper'         => 'nullable|in:' . implode(',', self::PAPER_SIZES),
            'include_meta'  => 'boolean',
            'include_rules' => 'boolean',
            'layout'        => 'nullable|in:overlay,overlay_horizontal,overlay_vertical,class_columns,class_rows,class_grid,days_as_rows_by_class',
        ]);

        $run = TimetableGenerationRun::with(['session', 'term', 'creator', 'snapshots.schoolclass'])->findOrFail($runId);

        $format      = $validated['format'] ?? 'pdf';
        $mode        = $validated['mode'] ?? 'per_class';
        $orientation = $validated['orientation'] ?? 'horizontal';
        [$paperSize, $paperDir] = $this->resolvePaper($validated['paper'] ?? null, $orientation);

        $includeMeta  = $validated['include_meta']  ?? true;
        $includeRules = $validated['include_rules'] ?? false;
        $layout       = $validated['layout'] ?? 'overlay_horizontal';
        $layout       = match ($layout) {
            'overlay'    => 'overlay_horizontal',
            'class_grid' => 'class_columns',
            default      => $layout,
        };

        $schoolInfo = SchoolInformation::getActiveSchool();

        if ($mode === 'merged') {
            $data = $this->buildMergedGridDataFromRun($run, $schoolInfo);
            $data['orientation'] = $orientation;
            $data['paperSize']   = $paperSize;
            $data['bodyScale']   = $this->paperBodyScale($paperSize);
            $data['runMeta']     = $includeMeta  ? $this->buildRunMetaBlock($run) : null;
            $data['runRules']    = $includeRules ? ($run->advanced_rules ?? null)  : null;

            $mergedView = match ($layout) {
                'overlay_vertical' => $format === 'web' ? 'timetable.exports.merged-grid-vertical-web' : 'timetable.exports.merged-grid-vertical',
                'class_columns'    => $format === 'web' ? 'timetable.exports.merged-grid-class-columns-web' : 'timetable.exports.merged-grid-class-columns',
                'class_rows'       => $format === 'web' ? 'timetable.exports.merged-grid-class-rows-web' : 'timetable.exports.merged-grid-class-rows',
                'days_as_rows_by_class' => $format === 'web' ? 'timetable.exports.merged-grid-days-by-class-web' : 'timetable.exports.merged-grid-days-by-class',
                default            => $format === 'web' ? 'timetable.exports.merged-grid-web' : 'timetable.exports.merged-grid',
            };

            if ($format === 'web') {
                return view($mergedView, array_merge($data, [
                    'pagetitle'   => 'Merged Timetable — ' . $run->name,
                    'orientation' => $orientation,
                    'layout'      => $layout,
                ]));
            }

            $pdf = Pdf::loadView($mergedView, $data)
                ->setPaper($paperSize, $paperDir);
            $filename = 'run-' . $run->run_code . '-merged-' . str_replace('_', '-', $layout) . '-' . $paperSize . '.pdf';
            return $pdf->stream($filename);
        }

        $allTimetables = $this->buildTimetablesFromRun($run);
        $overallStats  = [
            'total_classes'   => $run->class_count,
            'total_teachers'  => collect($allTimetables)->pluck('stats.teacher_names')->flatten()->filter()->unique()->count(),
            'avg_fill_rate'   => (int) round(collect($allTimetables)->avg(fn($t) => $t['stats']['fill_rate'])),
            'total_conflicts' => 0,
        ];

        $viewData = [
            'allTimetables' => $allTimetables,
            'schoolInfo'    => $schoolInfo,
            'sessionName'   => $run->session?->session ?? '',
            'termName'      => $run->term?->term ?? 'All Terms',
            'orientation'   => $orientation,
            'dayColors'     => self::DAY_COLORS,
            'generatedAt'   => now()->format('d M Y, H:i'),
            'overallStats'  => $overallStats,
            'paperSize'     => $paperSize,
            'paperDir'      => $paperDir,
            'bodyScale'     => $this->paperBodyScale($paperSize),
            'runMeta'       => $includeMeta  ? $this->buildRunMetaBlock($run) : null,
            'runRules'      => $includeRules ? ($run->advanced_rules ?? null)  : null,
        ];

        if ($format === 'web') {
            $pagetitle = 'Whole School Timetable — ' . $run->name;
            return view('timetable.exports.whole-school-web', array_merge($viewData, [
                'pagetitle'      => $pagetitle,
                'staffAnalytics' => ['staff' => [], 'summary' => []],
            ]));
        }

        $pdf = Pdf::loadView('timetable.exports.whole-school', $viewData)
            ->setPaper($paperSize, $paperDir);
        $filename = 'run-' . $run->run_code . '-' . $paperSize . '.pdf';
        return $pdf->stream($filename);
    }

    private function buildTimetablesFromRun(TimetableGenerationRun $run): array
    {
        $result = [];
        foreach ($run->snapshots as $snapshot) {
            $periods = collect($snapshot->periods_snapshot)->map(fn($p) => (object) $p);
            $slots   = collect($snapshot->slots_snapshot);

            $grid = [];
            foreach ($slots as $slot) {
                $subject = !empty($slot['subject_id']) ? Subject::find($slot['subject_id']) : null;
                $teacher = !empty($slot['teacher_id']) ? User::find($slot['teacher_id'])    : null;
                $room    = !empty($slot['room_id'])    ? Room::find($slot['room_id'])       : null;

                $grid[$slot['period_id']][$slot['day']] = [
                    'subject'    => $subject?->subject ?? ($slot['is_free'] ? 'FREE' : '—'),
                    'teacher'    => $teacher?->name ?? '',
                    'teacher_id' => $slot['teacher_id'],
                    'room'       => $room?->room_name ?? '',
                    'is_free'    => $slot['is_free'] ?? false,
                ];
            }

            $days    = $snapshot->setting_snapshot['active_days'] ?? self::DAYS;
            $dayMeta = $this->computeDayPeriodMetaFromSnapshot($snapshot);

            $result[] = [
                'setting_id' => $snapshot->source_setting_id,
                'class_name' => $snapshot->class_name,
                'periods'    => $periods,
                'grid'       => $grid,
                'days'       => $days,
                'day_meta'   => $dayMeta,
                'stats'      => [
                    'total_slots'   => $snapshot->placed + $snapshot->unplaced + $snapshot->room_shortfall,
                    'filled_slots'  => $snapshot->placed,
                    'free_slots'    => max(0, $snapshot->placed + $snapshot->room_shortfall),
                    'fill_rate'     => $snapshot->placed > 0
                        ? (int) round(($snapshot->placed / max(1, $snapshot->placed + $snapshot->unplaced)) * 100)
                        : 0,
                    'subject_count' => collect($slots)->pluck('subject_id')->filter()->unique()->count(),
                    'teacher_count' => collect($slots)->pluck('teacher_id')->filter()->unique()->count(),
                    'room_count'    => collect($slots)->pluck('room_id')->filter()->unique()->count(),
                    'teacher_names' => [],
                ],
            ];
        }
        return $result;
    }

    private function computeDayPeriodMetaFromSnapshot(TimetableGenerationRunSetting $snapshot): array
    {
        $periods     = collect($snapshot->periods_snapshot);
        $activeDays  = $snapshot->setting_snapshot['active_days'] ?? self::DAYS;
        $halfDays    = $snapshot->setting_snapshot['half_days'] ?? [];
        $assemblyDay = $snapshot->setting_snapshot['assembly_day'] ?? null;

        $meta = [];
        foreach ($activeDays as $day) {
            $teachingIndex = 0;
            $cutoff        = $halfDays[$day] ?? null;

            foreach ($periods as $p) {
                $type = $p['type'] ?? 'lesson';
                $effectiveType = $type;
                if ($type === 'assembly') {
                    $effectiveType = ($day === $assemblyDay) ? 'assembly' : 'lesson';
                }

                $applicable = true;
                if ($effectiveType === 'lesson') {
                    $teachingIndex++;
                    if ($cutoff && $teachingIndex > $cutoff) $applicable = false;
                }

                $meta[$day][$p['id']] = ['applicable' => $applicable, 'effective_type' => $effectiveType];
            }
        }
        return $meta;
    }

    private function buildMergedGridDataFromRun(TimetableGenerationRun $run, $schoolInfo): array
    {
        $classColorPalette = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899','#14B8A6','#84CC16','#6366F1','#D946EF'];

        $classData    = [];
        $classColors  = [];
        $colorIdx     = 0;
        $allDaysUnion = [];
        $timeSlotMap  = [];

        foreach ($run->snapshots as $snapshot) {
            if (array_key_exists($snapshot->class_name, $classColors)) {
                continue;
            }

            $classColors[$snapshot->class_name] = $classColorPalette[$colorIdx++ % count($classColorPalette)];

            $periods = collect($snapshot->periods_snapshot);
            $slots   = collect($snapshot->slots_snapshot);

            $grid = [];
            foreach ($slots as $slot) {
                $grid[$slot['period_id']][$slot['day']] = [
                    'subject'    => $slot['subject_id'] ? (Subject::find($slot['subject_id'])?->subject) : null,
                    'teacher'    => $slot['teacher_id'] ? (User::find($slot['teacher_id'])?->name)    : null,
                    'teacher_id' => $slot['teacher_id'],
                    'room'       => $slot['room_id']    ? (Room::find($slot['room_id'])?->room_name)   : null,
                    'is_free'    => $slot['is_free'] ?? false,
                    'is_double'  => $slot['is_double'] ?? false,
                ];
            }

            $days = $snapshot->setting_snapshot['active_days'] ?? self::DAYS;
            $allDaysUnion = array_unique(array_merge($allDaysUnion, $days));
            $dayMeta = $this->computeDayPeriodMetaFromSnapshot($snapshot);

            foreach ($periods as $p) {
                $key = substr($p['start_time'], 0, 5) . '-' . substr($p['end_time'], 0, 5);
                if (!isset($timeSlotMap[$key])) {
                    $timeSlotMap[$key] = [
                        'start' => substr($p['start_time'], 0, 5),
                        'end'   => substr($p['end_time'], 0, 5),
                        'names' => [],
                    ];
                }
                $timeSlotMap[$key]['names'][] = $p['name'];
            }

            $classData[$snapshot->class_name] = [
                'grid'    => $grid,
                'days'    => $days,
                'dayMeta' => $dayMeta,
                'periods' => $periods,
            ];
        }

        $dayOrder = self::DAYS;
        usort($allDaysUnion, fn($a, $b) => array_search($a, $dayOrder) <=> array_search($b, $dayOrder));
        uasort($timeSlotMap, fn($a, $b) => strcmp($a['start'], $b['start']));

        $mergedRows = [];
        foreach ($timeSlotMap as $info) {
            $label = collect($info['names'])->countBy()->sortDesc()->keys()->first() ?? 'Period';
            $rowEntries = [];
            foreach ($allDaysUnion as $day) {
                $entries = []; $anyBreak = false; $applicable = false;

                foreach ($classData as $className => $cd) {
                    if (!in_array($day, $cd['days'])) continue;
                    $matchedPeriod = $cd['periods']->first(fn($p) =>
                        substr($p['start_time'], 0, 5) === $info['start'] && substr($p['end_time'], 0, 5) === $info['end']
                    );
                    if (!$matchedPeriod) continue;

                    $meta = $cd['dayMeta'][$day][$matchedPeriod['id']] ?? null;
                    if (!$meta || !$meta['applicable']) continue;
                    $applicable = true;

                    if ($meta['effective_type'] !== 'lesson') { $anyBreak = true; continue; }

                    $slotInfo = $cd['grid'][$matchedPeriod['id']][$day] ?? null;
                    if (!$slotInfo || $slotInfo['is_free']) continue;

                    $entries[] = [
                        'class'       => $className,
                        'subject'     => $slotInfo['subject'] ?? '—',
                        'teacher'     => $slotInfo['teacher'] ?? '',
                        'teacher_id'  => $slotInfo['teacher_id'] ?? null,
                        'room'        => $slotInfo['room'] ?? '',
                        'color'       => $classColors[$className],
                        'is_conflict' => false,
                        'is_double'   => $slotInfo['is_double'] ?? false,
                    ];
                }

                $rowEntries[$day] = [
                    'entries'     => $entries,
                    'is_break'    => $anyBreak && empty($entries),
                    'applicable'  => $applicable,
                ];
            }

            $mergedRows[] = ['label' => $label, 'time' => $info['start'] . ' – ' . $info['end'], 'days' => $rowEntries];
        }

        $this->markDoublePeriodEntries($mergedRows, $allDaysUnion);

        return [
            'rows'           => $mergedRows,
            'days'           => $allDaysUnion,
            'classColors'    => $classColors,
            'classList'      => array_keys($classColors),
            'schoolInfo'     => $schoolInfo,
            'sessionName'    => $run->session?->session ?? '',
            'termName'       => $run->term?->term ?? 'All Terms',
            'generatedAt'    => now()->format('d M Y, H:i'),
            'dayColors'      => self::DAY_COLORS,
            'staffAnalytics' => ['staff' => [], 'summary' => []],
        ];
    }

    private function buildRunMetaBlock(TimetableGenerationRun $run): array
    {
        return [
            'name'        => $run->name,
            'run_code'    => $run->run_code,
            'description' => $run->description,
            'creator'     => $run->creator?->name,
            'created_at'  => $run->created_at->format('d M Y, H:i'),
            'seed'        => $run->seed,
            'status'      => $run->status,
        ];
    }
}