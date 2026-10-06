<?php
// app/Http/Controllers/HolidayController.php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\TimetableOverride;
use App\Models\TimetableSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View holidays', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create holidays', ['only' => ['store']]);
        $this->middleware('permission:Edit holidays', ['only' => ['update']]);
        $this->middleware('permission:Delete holidays', ['only' => ['destroy']]);
    }

    public function index()
    {
        $pagetitle = 'Holiday Management';
        $holidays = Holiday::orderBy('start_date', 'desc')->paginate(15);
        $upcomingHolidays = Holiday::where('start_date', '>=', now())->orderBy('start_date')->take(5)->get();

        return view('holidays.index', compact('pagetitle', 'holidays', 'upcomingHolidays'));
    }

    /**
     * GET /holidays/{id}
     * Returns a single holiday record as JSON (used by the edit modal).
     */
    public function show(int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);
        return response()->json(['success' => true, 'holiday' => $holiday]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:public_holiday,school_holiday,exam_period,special_event',
            'affects_timetable' => 'boolean',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $holiday = Holiday::create($validated);

            if ($validated['affects_timetable'] ?? false) {
                $this->createHolidayOverrides($holiday);
            }

            DB::commit();
            return response()->json(['success' => true, 'holiday' => $holiday, 'message' => 'Holiday created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create holiday: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:public_holiday,school_holiday,exam_period,special_event',
            'affects_timetable' => 'boolean',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Remove old overrides for this holiday's date range
            TimetableOverride::where('override_date', '>=', $holiday->start_date)
                ->where('override_date', '<=', $holiday->end_date)
                ->where('override_type', 'holiday')
                ->delete();

            $holiday->update($validated);

            if ($validated['affects_timetable'] ?? false) {
                $this->createHolidayOverrides($holiday);
            }

            DB::commit();
            return response()->json(['success' => true, 'holiday' => $holiday, 'message' => 'Holiday updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update holiday'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);

        TimetableOverride::where('override_date', '>=', $holiday->start_date)
            ->where('override_date', '<=', $holiday->end_date)
            ->where('override_type', 'holiday')
            ->delete();

        $holiday->delete();
        return response()->json(['success' => true, 'message' => 'Holiday deleted successfully']);
    }

    public function applyToTimetable(int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);

        try {
            $this->createHolidayOverrides($holiday);
            return response()->json(['success' => true, 'message' => 'Holiday applied to all timetables']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to apply holiday: ' . $e->getMessage()], 500);
        }
    }

    private function createHolidayOverrides($holiday): void
    {
        $startDate = Carbon::parse($holiday->start_date);
        $endDate = Carbon::parse($holiday->end_date);
        $currentDate = $startDate->copy();

        $settings = TimetableSetting::where('is_active', true)->get();

        while ($currentDate <= $endDate) {
            $dayOfWeek = $currentDate->format('l');

            foreach ($settings as $setting) {
                $activeDays = $setting->active_days ?? TimetableController::DAYS;
                if (!in_array($dayOfWeek, $activeDays)) {
                    $currentDate->addDay();
                    continue;
                }

                TimetableOverride::updateOrCreate(
                    [
                        'setting_id' => $setting->id,
                        'override_date' => $currentDate->toDateString(),
                    ],
                    [
                        'override_type' => 'holiday',
                        'title' => $holiday->name,
                        'description' => $holiday->description,
                        'cancel_all_classes' => true,
                        'cancellation_reason' => $holiday->name,
                        'status' => 'approved',
                        'created_by' => Auth::id(),
                    ]
                );
            }

            $currentDate->addDay();
        }
    }
}
