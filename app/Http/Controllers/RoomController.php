<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\RoomClassSubject;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{
    const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function index()
    {
        $pagetitle = 'Room Management';
        $rooms = Room::orderBy('room_name')->paginate(15);
        $roomTypes = ['classroom', 'laboratory', 'auditorium', 'library', 'sports', 'other'];

        return view('rooms.index', compact('pagetitle', 'rooms', 'roomTypes'));
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_code' => 'required|string|max:50|unique:rooms,room_code',
                'room_name' => 'required|string|max:100',
                'type' => 'required|in:classroom,laboratory,auditorium,library,sports,other',
                'capacity' => 'required|integer|min:1',
                'facilities' => 'nullable|array',
                'building' => 'nullable|string|max:100',
                'floor' => 'nullable|string|max:50',
                'is_active' => 'sometimes|boolean',
                'notes' => 'nullable|string',
            ]);

            $validated['is_active'] = $request->input('is_active', true);

            $room = Room::create($validated);

            return response()->json([
                'success' => true,
                'room' => $room,
                'message' => 'Room created successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Room creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $room = Room::findOrFail($id);

            $currentWeekSlots = TimetableSlot::where('room_id', $id)
                ->with(['period', 'subject', 'teacher'])
                ->get()
                ->map(function($slot) {
                    return [
                        'day' => $slot->day,
                        'period' => $slot->period ? [
                            'name' => $slot->period->name,
                            'start_time' => $slot->period->start_time,
                            'end_time' => $slot->period->end_time
                        ] : null,
                        'subject' => $slot->subject ? ['subject' => $slot->subject->subject_name] : null,
                        'teacher' => $slot->teacher ? ['name' => $slot->teacher->name] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'room' => $room,
                'current_bookings' => $currentWeekSlots
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $room = Room::findOrFail($id);

            $validated = $request->validate([
                'room_code' => 'required|string|max:50|unique:rooms,room_code,' . $id,
                'room_name' => 'required|string|max:100',
                'type' => 'required|in:classroom,laboratory,auditorium,library,sports,other',
                'capacity' => 'required|integer|min:1',
                'facilities' => 'nullable|array',
                'building' => 'nullable|string|max:100',
                'floor' => 'nullable|string|max:50',
                'is_active' => 'sometimes|boolean',
                'notes' => 'nullable|string',
            ]);

            $room->update($validated);

            return response()->json([
                'success' => true,
                'room' => $room,
                'message' => 'Room updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update room: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $room = Room::findOrFail($id);

            $isUsed = TimetableSlot::where('room_id', $id)->exists();
            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete room that is currently in use in a timetable'
                ], 422);
            }

            $hasFutureBookings = RoomBooking::where('room_id', $id)
                ->where('date', '>=', now()->toDateString())
                ->exists();

            if ($hasFutureBookings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete room with upcoming bookings'
                ], 422);
            }

            // Clean up any mappings — cascading FKs on the pivot would
            // handle this automatically, but explicit is safer if the
            // schema changes.
            RoomClassSubject::where('room_id', $id)->delete();

            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room'
            ], 500);
        }
    }

    public function book(Request $request, $roomId = null): JsonResponse
    {
        try {
            $roomId = $roomId ?? $request->input('room_id');

            $validated = $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'purpose' => 'required|string|max:500',
                'recurring_type' => 'sometimes|in:none,weekly,biweekly',
            ]);

            $room = Room::find($roomId);
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found'
                ], 404);
            }

            $isAvailable = $this->checkRoomAvailability(
                $roomId,
                $validated['date'],
                $validated['start_time'],
                $validated['end_time']
            );

            if (!$isAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room is not available at this time'
                ], 422);
            }

            $booking = RoomBooking::create([
                'room_id' => $roomId,
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'purpose' => $validated['purpose'],
                'recurring_type' => $validated['recurring_type'] ?? 'none',
                'booked_by' => Auth::id(),
                'status' => 'confirmed'
            ]);

            return response()->json([
                'success' => true,
                'booking' => $booking,
                'message' => 'Room booked successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Booking failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to book room: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelBooking($bookingId): JsonResponse
    {
        try {
            $booking = RoomBooking::findOrFail($bookingId);

            if ($booking->booked_by !== Auth::id() && !Auth::user()->can('Manage room bookings')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($booking->date < now()->toDateString()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a past booking'
                ], 422);
            }

            $booking->delete();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking'
            ], 500);
        }
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_id' => 'required|exists:rooms,id',
                'date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
            ]);

            $isAvailable = $this->checkRoomAvailability(
                $validated['room_id'],
                $validated['date'],
                $validated['start_time'],
                $validated['end_time']
            );

            return response()->json([
                'success' => true,
                'available' => $isAvailable
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => 'Failed to check availability'
            ], 500);
        }
    }

    private function checkRoomAvailability($roomId, $date, $startTime, $endTime): bool
    {
        $dayOfWeek = date('l', strtotime($date));

        $timetableConflict = TimetableSlot::where('room_id', $roomId)
            ->where('day', $dayOfWeek)
            ->whereHas('period', function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($timetableConflict) return false;

        $bookingConflict = RoomBooking::where('room_id', $roomId)
            ->where('date', $date)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        return !$bookingConflict;
    }

    // =========================================================================
    // ROOM-CLASS-SUBJECT MAPPINGS
    // =========================================================================

    public function mappings(int $roomId): JsonResponse
    {
        try {
            Room::findOrFail($roomId);

            $rows = RoomClassSubject::with(['schoolclass', 'subject', 'session', 'term'])
                ->where('room_id', $roomId)
                ->orderBy('schoolclass_id')
                ->orderBy('subject_id')
                ->get()
                ->map(fn($m) => [
                    'id'              => $m->id,
                    'schoolclass_id'  => $m->schoolclass_id,
                    'class_name'      => trim(($m->schoolclass?->schoolclass ?? '')
                                       . ' ' . ($m->schoolclass?->arm ?? '')),
                    'subject_id'      => $m->subject_id,
                    'subject_name'    => $m->subject?->subject,
                    'session_id'      => $m->session_id,
                    'session_name'    => $m->session?->session,
                    'term_id'         => $m->term_id,
                    'term_name'       => $m->term?->term,
                    'note'            => $m->note,
                ]);

            return response()->json(['success' => true, 'mappings' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeMapping(Request $request, int $roomId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'schoolclass_id' => 'required|exists:schoolclass,id',
                'subject_id'     => 'nullable|exists:subject,id',
                'session_id'     => 'required|exists:schoolsession,id',
                'term_id'        => 'nullable|exists:schoolterm,id',
                'note'           => 'nullable|string|max:190',
            ]);

            $validated['room_id'] = $roomId;

            $exists = RoomClassSubject::where('room_id', $roomId)
                ->where('schoolclass_id', $validated['schoolclass_id'])
                ->where('subject_id', $validated['subject_id'] ?? null)
                ->where('session_id', $validated['session_id'])
                ->where('term_id', $validated['term_id'] ?? null)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'That room is already mapped to this class/subject for the same session and term.',
                ], 422);
            }

            $row = RoomClassSubject::create($validated);

            return response()->json(['success' => true, 'mapping' => $row]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyMapping(int $mappingId): JsonResponse
    {
        try {
            RoomClassSubject::findOrFail($mappingId)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function mappingCounts(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');

        $q = RoomClassSubject::query();
        if ($sessionId) $q->where('session_id', $sessionId);

        $counts = $q->select('room_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        return response()->json(['success' => true, 'counts' => $counts]);
    }

    /**
     * Lightweight {id, label} list of active rooms, for select dropdowns
     * (Generation Wizard's Quick-Map-Room modal, wizard room-mapping panel,
     * bulk-map modal, etc). Mirrors the api.classes-list / api.subjects-list
     * lookup endpoints' response shape.
     */
    public function listJson(): JsonResponse
    {
        $rooms = Room::where('is_active', true)
            ->orderBy('room_name')
            ->get(['id', 'room_name', 'room_code'])
            ->map(fn($r) => [
                'id'    => $r->id,
                'label' => $r->room_code ? "{$r->room_name} ({$r->room_code})" : $r->room_name,
            ]);

        return response()->json(['success' => true, 'data' => $rooms]);
    }

    /**
     * Batched per-room stats for the rooms grid/table view — the same three
     * counters as statsDetail()'s totals (upcoming bookings, mapped
     * classes/subjects, timetable uses), computed for every room in one
     * round trip instead of one statsDetail() request per room.
     */
    public function roomStats(): JsonResponse
    {
        $bookingCounts = RoomBooking::where('date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->select('room_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        $mappingCounts = RoomClassSubject::select('room_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        $useCounts = TimetableSlot::whereNotNull('room_id')
            ->select('room_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        $stats = Room::pluck('id')->mapWithKeys(fn($id) => [$id => [
            'upcoming_bookings' => $bookingCounts[$id] ?? 0,
            'mapped_classes'    => $mappingCounts[$id] ?? 0,
            'timetable_uses'    => $useCounts[$id] ?? 0,
        ]]);

        return response()->json(['success' => true, 'stats' => $stats]);
    }

    /**
     * Bulk activate/deactivate a set of rooms in one request (rooms index
     * page's selection toolbar).
     */
    public function bulkActivate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_ids'   => 'required|array|min:1',
                'room_ids.*' => 'integer|exists:rooms,id',
                'is_active'  => 'required|boolean',
            ]);

            $updated = Room::whereIn('id', $validated['room_ids'])
                ->update(['is_active' => $validated['is_active']]);

            return response()->json([
                'success' => true,
                'message' => $updated . ' room(s) updated.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk delete a set of rooms. Applies the same per-room guardrails as
     * destroy() (in use in a timetable, or has upcoming bookings), but
     * skips a blocked room instead of failing the whole batch — the
     * frontend surfaces the skipped list via the `blocked` key.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_ids'   => 'required|array|min:1',
                'room_ids.*' => 'integer|exists:rooms,id',
            ]);

            $rooms   = Room::whereIn('id', $validated['room_ids'])->get();
            $blocked = [];
            $deleted = 0;

            foreach ($rooms as $room) {
                if (TimetableSlot::where('room_id', $room->id)->exists()) {
                    $blocked[] = ['id' => $room->id, 'name' => $room->room_name, 'reason' => 'in_use_in_timetable'];
                    continue;
                }

                $hasFutureBookings = RoomBooking::where('room_id', $room->id)
                    ->where('date', '>=', now()->toDateString())
                    ->exists();

                if ($hasFutureBookings) {
                    $blocked[] = ['id' => $room->id, 'name' => $room->room_name, 'reason' => 'has_upcoming_bookings'];
                    continue;
                }

                RoomClassSubject::where('room_id', $room->id)->delete();
                $room->delete();
                $deleted++;
            }

            if ($blocked) {
                return response()->json([
                    'success' => false,
                    'message' => $deleted . ' room(s) deleted; ' . count($blocked) . ' skipped.',
                    'blocked' => $blocked,
                    'deleted' => $deleted,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $deleted . ' room(s) deleted successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply the same class/subject/session/term mapping to every room in
     * room_ids in one shot (rooms index page's bulk-map modal). Uses
     * updateOrCreate per room so re-running it against an already-mapped
     * room updates the note instead of colliding with the scope_key unique
     * index (see RoomClassSubject::booted()).
     */
    public function bulkMap(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_ids'       => 'required|array|min:1',
                'room_ids.*'     => 'integer|exists:rooms,id',
                'schoolclass_id' => 'required|exists:schoolclass,id',
                'subject_id'     => 'nullable|exists:subject,id',
                'session_id'     => 'required|exists:schoolsession,id',
                'term_id'        => 'nullable|exists:schoolterm,id',
                'note'           => 'nullable|string|max:190',
            ]);

            $mapped = 0;
            foreach ($validated['room_ids'] as $roomId) {
                RoomClassSubject::updateOrCreate(
                    [
                        'room_id'        => $roomId,
                        'schoolclass_id' => $validated['schoolclass_id'],
                        'subject_id'     => $validated['subject_id'] ?? null,
                        'session_id'     => $validated['session_id'],
                        'term_id'        => $validated['term_id'] ?? null,
                    ],
                    ['note' => $validated['note'] ?? null]
                );
                $mapped++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Mapped ' . $mapped . ' room(s) to the selected class.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

        // =========================================================================
    // PER-ROOM STATS DETAIL
    //
    // Returns the full lists behind the three counters shown on the rooms
    // page: upcoming bookings, mapped classes/subjects, and timetable uses.
    // The frontend caps each list at 10 for display; we cap at 50 here so
    // the "Show all N" link can reveal more without a second fetch.
    // =========================================================================
    public function statsDetail(int $roomId): JsonResponse
    {
        try {
            $room = Room::findOrFail($roomId);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Room not found.'], 404);
        }

        // ── Upcoming bookings ──────────────────────────────────────────
        $bookings = RoomBooking::where('room_id', $roomId)
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(50)
            ->get()
            ->map(fn($b) => [
                'id'         => $b->id,
                'date'       => \Carbon\Carbon::parse($b->date)->format('d M Y'),
                'date_h'     => \Carbon\Carbon::parse($b->date)->diffForHumans(),
                'start_time' => $this->formatTimeOnly($b->start_time),
                'end_time'   => $this->formatTimeOnly($b->end_time),
                'purpose'    => $b->purpose,
                'recurring'  => $b->recurring_type ?? 'none',
            ]);

        // ── Mapped classes / subjects ──────────────────────────────────
        $mappings = RoomClassSubject::with(['schoolclass', 'subject', 'session', 'term'])
            ->where('room_id', $roomId)
            ->orderBy('schoolclass_id')
            ->orderBy('subject_id')
            ->limit(50)
            ->get()
            ->map(function ($m) {
                $className = trim(
                    ($m->schoolclass?->schoolclass ?? '') . ' ' .
                    ($m->schoolclass?->arm ?? '')
                );
                return [
                    'id'           => $m->id,
                    'class_name'   => $className ?: 'Unknown Class',
                    'subject_name' => $m->subject?->subject,   // null = generic
                    'session_name' => $m->session?->session,
                    'term_name'    => $m->term?->term,
                    'note'         => $m->note,
                ];
            });

        // ── Timetable uses ─────────────────────────────────────────────
        // Slot → setting → class. Group by setting so the payload is
        // compact, then flatten for the client.
        $slots = TimetableSlot::where('room_id', $roomId)
            ->with(['subject', 'teacher', 'period', 'setting.schoolclass', 'setting.session', 'setting.term'])
            ->orderBy('day')
            ->orderBy('period_id')
            ->limit(50)
            ->get()
            ->map(function ($s) {
                $className = trim(
                    ($s->setting?->schoolclass?->schoolclass ?? '') . ' ' .
                    ($s->setting?->schoolclass?->arm ?? '')
                );
                return [
                    'id'           => $s->id,
                    'day'          => $s->day,
                    'period_name'  => $s->period?->name,
                    'period_time'  => $this->formatTimeOnly($s->period?->start_time)
                                    . ' – '
                                    . $this->formatTimeOnly($s->period?->end_time),
                    'subject_name' => $s->subject?->subject,
                    'teacher_name' => $s->teacher?->name,
                    'class_name'   => $className ?: 'Unknown Class',
                    'session_name' => $s->setting?->session?->session,
                    'term_name'    => $s->setting?->term?->term,
                ];
            });

        return response()->json([
            'success'  => true,
            'room'     => [
                'id'        => $room->id,
                'room_name' => $room->room_name,
                'room_code' => $room->room_code,
            ],
            'bookings' => $bookings,
            'mappings' => $mappings,
            'uses'     => $slots,
            'totals'   => [
                'bookings' => RoomBooking::where('room_id', $roomId)
                    ->where('date', '>=', now()->toDateString())
                    ->where('status', 'confirmed')
                    ->count(),
                'mappings' => RoomClassSubject::where('room_id', $roomId)->count(),
                'uses'     => TimetableSlot::where('room_id', $roomId)->count(),
            ],
        ]);
    }

    /**
     * Small helper: normalise a time value ("10:00:00" or Carbon) to "HH:MM".
     * Used by statsDetail only, so it lives here rather than the trait
     * helpers we keep elsewhere.
     */
    private function formatTimeOnly($time): string
    {
        if (!$time) return '';
        if ($time instanceof \Carbon\Carbon) return $time->format('H:i');
        return substr((string) $time, 0, 5);
    }
}