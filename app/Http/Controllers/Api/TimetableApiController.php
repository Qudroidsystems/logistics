<?php
// app/Http/Controllers/Api/TimetableApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimetableSlot;
use App\Models\TimetableSetting;
use App\Models\SubstituteAssignment;
use App\Models\TimetableNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TimetableApiController extends Controller
{
    public function getMyTimetable(Request $request)
    {
        $teacherId = Auth::id();
        $sessionId = $request->session_id ?? $this->getCurrentSessionId();

        $slots = TimetableSlot::where('teacher_id', $teacherId)
            ->whereHas('setting', fn($q) => $q->where('session_id', $sessionId))
            ->with(['period', 'subject', 'setting.schoolclass', 'room'])
            ->get()
            ->groupBy('day')
            ->map(function ($daySlots) {
                return $daySlots->map(function ($slot) {
                    return [
                        'id' => $slot->id,
                        'period_name' => $slot->period->name,
                        'start_time' => $slot->period->start_time,
                        'end_time' => $slot->period->end_time,
                        'subject' => $slot->subject->subject ?? null,
                        'subject_code' => $slot->subject->subject_code ?? null,
                        'class' => $slot->setting->schoolclass->schoolclass ?? null,
                        'room' => $slot->room,
                        'is_break' => $slot->period->is_break,
                    ];
                });
            });

        return response()->json([
            'success' => true,
            'data' => $slots,
            'last_updated' => now()->toIso8601String(),
        ]);
    }

    public function getTodaySchedule(Request $request)
    {
        $teacherId = Auth::id();
        $today = Carbon::now()->format('l');

        $slots = TimetableSlot::where('teacher_id', $teacherId)
            ->where('day', $today)
            ->whereHas('setting', fn($q) => $q->where('is_active', true))
            ->with(['period', 'subject', 'setting.schoolclass', 'room'])
            ->orderBy('period.order')
            ->get()
            ->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'period' => $slot->period->name,
                    'time' => $slot->period->start_time . ' - ' . $slot->period->end_time,
                    'subject' => $slot->subject->subject ?? 'Free Period',
                    'class' => $slot->setting->schoolclass->schoolclass ?? null,
                    'room' => $slot->room,
                    'is_current' => $this->isCurrentPeriod($slot->period),
                ];
            });

        return response()->json([
            'success' => true,
            'date' => Carbon::now()->toDateString(),
            'day' => $today,
            'schedule' => $slots,
        ]);
    }

    public function getUpcomingClasses(Request $request)
    {
        $teacherId = Auth::id();
        $limit = $request->limit ?? 5;

        $now = Carbon::now();
        $currentDay = $now->format('l');

        $slots = TimetableSlot::where('teacher_id', $teacherId)
            ->whereNotNull('subject_id')
            ->whereHas('setting', fn($q) => $q->where('is_active', true))
            ->with(['period', 'subject', 'setting.schoolclass', 'room'])
            ->get()
            ->filter(function ($slot) use ($now, $currentDay) {
                if ($slot->day === $currentDay) {
                    $slotTime = Carbon::createFromFormat('H:i:s', $slot->period->start_time);
                    return $slotTime->greaterThan($now);
                }
                // For future days, return all slots
                $dayOrder = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5];
                $currentDayOrder = $dayOrder[$currentDay] ?? 0;
                $slotDayOrder = $dayOrder[$slot->day] ?? 0;
                return $slotDayOrder > $currentDayOrder;
            })
            ->sortBy(function ($slot) {
                $dayOrder = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5];
                return $dayOrder[$slot->day] * 100 + $slot->period->order;
            })
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'upcoming_classes' => $slots,
        ]);
    }

    public function markAttendance(Request $request)
    {
        $validated = $request->validate([
            'slot_id' => 'required|exists:timetable_slots,id',
            'status' => 'required|in:present,absent,late',
            'notes' => 'nullable|string|max:500',
        ]);

        $slot = TimetableSlot::findOrFail($validated['slot_id']);

        // Verify teacher owns this slot
        if ($slot->teacher_id != Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Store attendance (you'll need an attendance table)
        // This is a placeholder for attendance tracking
        $attendance = Attendance::updateOrCreate(
            [
                'slot_id' => $slot->id,
                'date' => Carbon::now()->toDateString(),
            ],
            [
                'teacher_id' => Auth::id(),
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'marked_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'attendance' => $attendance,
        ]);
    }

    private function isCurrentPeriod($period): bool
    {
        $now = Carbon::now();
        $start = Carbon::createFromFormat('H:i:s', $period->start_time);
        $end = Carbon::createFromFormat('H:i:s', $period->end_time);

        return $now->between($start, $end);
    }

    private function getCurrentSessionId()
    {
        return \App\Models\Schoolsession::where('status', 'Current')->value('id');
    }
}
