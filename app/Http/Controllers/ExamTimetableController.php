<?php
// app/Http/Controllers/ExamTimetableController.php

namespace App\Http\Controllers;

use App\Models\ExamTimetable;
use App\Models\ExamSlot;
use App\Models\Subject;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExamTimetableController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View exam timetable', ['only' => ['index', 'show', 'export']]);
        $this->middleware('permission:Create exam timetable', ['only' => ['store', 'addSlot']]);
        $this->middleware('permission:Edit exam timetable', ['only' => ['update', 'publish']]);
        $this->middleware('permission:Delete exam timetable', ['only' => ['destroy', 'removeSlot']]);
    }

    public function index()
    {
        $pagetitle = 'Exam Timetable Management';
        $examTimetables = ExamTimetable::with(['session', 'term'])->orderBy('created_at', 'desc')->paginate(10);
        $sessions = Schoolsession::orderByDesc('id')->get();
        $terms = Schoolterm::all();
        $subjects = Subject::orderBy('subject')->get();
        $classes = Schoolclass::orderBy('schoolclass')->get();
        $rooms = Room::where('is_active', true)->get();

        return view('exam-timetable.index', compact(
            'pagetitle', 'examTimetables', 'sessions', 'terms', 'subjects', 'classes', 'rooms'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'session_id' => 'required|exists:schoolsession,id',
            'term_id' => 'required|exists:schoolterm,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'exam_type' => 'required|in:mid_term,end_of_term,mock,entrance,other',
            'instructions' => 'nullable|string',
        ]);

        try {
            $examTimetable = ExamTimetable::create($validated);
            return response()->json(['success' => true, 'exam_timetable' => $examTimetable, 'message' => 'Exam timetable created successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create exam timetable: ' . $e->getMessage()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $examTimetable = ExamTimetable::with(['session', 'term', 'slots.subject', 'slots.class', 'slots.venue', 'slots.supervisor'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'exam_timetable' => $examTimetable]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $examTimetable = ExamTimetable::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'exam_type' => 'required|in:mid_term,end_of_term,mock,entrance,other',
            'instructions' => 'nullable|string',
        ]);

        try {
            $examTimetable->update($validated);
            return response()->json(['success' => true, 'exam_timetable' => $examTimetable, 'message' => 'Exam timetable updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update exam timetable'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $examTimetable = ExamTimetable::findOrFail($id);

        // Delete all associated slots first
        $examTimetable->slots()->delete();
        $examTimetable->delete();

        return response()->json(['success' => true, 'message' => 'Exam timetable deleted successfully']);
    }

    public function addSlot(Request $request, int $examTimetableId): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subject,id',
            'class_id' => 'required|exists:schoolclass,id',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'venue_id' => 'nullable|exists:rooms,id',
            'supervisor_id' => 'nullable|exists:users,id',
            'total_marks' => 'nullable|numeric|min:0',
        ]);

        $examTimetable = ExamTimetable::findOrFail($examTimetableId);

        // Check if date is within timetable range
        $examDate = Carbon::parse($validated['exam_date']);
        if ($examDate < Carbon::parse($examTimetable->start_date) || $examDate > Carbon::parse($examTimetable->end_date)) {
            return response()->json(['success' => false, 'message' => 'Exam date is outside the timetable range'], 422);
        }

        // Check for conflicts
        $conflict = ExamSlot::where('exam_timetable_id', $examTimetableId)
            ->where('exam_date', $validated['exam_date'])
            ->where(function($q) use ($validated) {
                $q->where('venue_id', $validated['venue_id'])
                  ->orWhere('supervisor_id', $validated['supervisor_id']);
            })
            ->exists();

        if ($conflict) {
            return response()->json(['success' => false, 'message' => 'Venue or supervisor has a conflict at this time'], 422);
        }

        $duration = Carbon::parse($validated['start_time'])->diffInMinutes(Carbon::parse($validated['end_time']));

        $slot = ExamSlot::create([
            'exam_timetable_id' => $examTimetableId,
            'subject_id' => $validated['subject_id'],
            'class_id' => $validated['class_id'],
            'exam_date' => $validated['exam_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'venue_id' => $validated['venue_id'] ?? null,
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'duration_minutes' => $duration,
            'total_marks' => $validated['total_marks'] ?? 100,
        ]);

        return response()->json(['success' => true, 'slot' => $slot, 'message' => 'Exam slot added successfully']);
    }

    public function removeSlot(int $slotId): JsonResponse
    {
        $slot = ExamSlot::findOrFail($slotId);
        $slot->delete();

        return response()->json(['success' => true, 'message' => 'Exam slot removed successfully']);
    }

    public function publish(int $id): JsonResponse
    {
        $examTimetable = ExamTimetable::findOrFail($id);
        $examTimetable->update(['status' => 'published']);

        // TODO: Send notifications to teachers and students
        // $this->sendExamTimetableNotifications($examTimetable);

        return response()->json(['success' => true, 'message' => 'Exam timetable published successfully']);
    }

    public function export(int $id)
    {
        $examTimetable = ExamTimetable::with(['slots.subject', 'slots.class', 'slots.venue', 'slots.supervisor'])->findOrFail($id);

        $filename = "exam_timetable_{$examTimetable->name}_{$examTimetable->session->session}.csv";
        $handle = fopen('php://temp', 'w+');

        // Headers
        fputcsv($handle, ['Date', 'Time', 'Subject', 'Class', 'Venue', 'Supervisor', 'Duration', 'Total Marks']);

        // Data rows
        foreach ($examTimetable->slots->sortBy('exam_date') as $slot) {
            fputcsv($handle, [
                $slot->exam_date,
                $slot->start_time . ' - ' . $slot->end_time,
                $slot->subject->subject ?? 'N/A',
                $slot->class->schoolclass ?? 'N/A',
                $slot->venue->room_name ?? 'TBA',
                $slot->supervisor->name ?? 'TBA',
                $slot->duration_minutes . ' mins',
                $slot->total_marks,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
