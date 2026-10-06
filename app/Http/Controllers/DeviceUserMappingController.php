<?php

namespace App\Http\Controllers;

use App\Models\DeviceAttendanceLog;
use App\Models\DeviceUserMapping;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages the PIN → student/staff mapping table that lets the device
 * ingest pipeline (see App\Services\DeviceAttendanceProcessor) know who a
 * given (device_serial, device_pin) punch belongs to.
 *
 * Three ways an admin creates mappings:
 *  1. Single manual add (one PIN, one person) — store()
 *  2. Bulk manual assign (many people, sequential PINs) — bulkManualAssign()
 *  3. CSV import (device_pin, person_type, identifier columns) — bulkImport()
 *
 * Plus a queue view (unmapped()) for punches that arrived before a PIN was
 * mapped to anyone, so they can be resolved after the fact.
 */
class DeviceUserMappingController extends Controller
{
    private const SEARCH_PER_PAGE = 20;

    public function __construct()
    {
        $this->middleware('permission:View device-mappings',   ['only' => ['index', 'unmapped', 'search']]);
        $this->middleware('permission:Create device-mappings', ['only' => ['store', 'bulkImport', 'bulkManualAssign', 'quickAssign']]);
        $this->middleware('permission:Delete device-mappings', ['only' => ['destroy']]);
    }

    // =========================================================================
    // MAPPINGS TABLE
    // =========================================================================

    public function index(Request $request)
    {
        $query = DeviceUserMapping::query()->latest();

        if ($request->filled('type')) {
            $query->where('person_type', $request->type);
        }
        if ($request->filled('q')) {
            $query->where('device_pin', 'like', "%{$request->q}%");
        }

        $mappings = $query->paginate(50)->withQueryString();

        $studentIds = $mappings->where('person_type', 'student')->pluck('person_id');
        $staffIds   = $mappings->where('person_type', 'staff')->pluck('person_id');

        $students = Student::with('picture')->whereIn('id', $studentIds)->get()->keyBy('id');
        $staff    = Staff::with('user')->whereIn('id', $staffIds)->get()->keyBy('id');

        $mappings->getCollection()->transform(function ($m) use ($students, $staff) {
            if ($m->person_type === 'student') {
                $p = $students->get($m->person_id);
                $m->display_name = $p ? "{$p->lastname} {$p->firstname} ({$p->admissionNo})" : 'Unknown student';
                $m->photo_url    = $p?->picture?->picture ? asset('storage/student_avatars/' . $p->picture->picture) : null;
            } else {
                $p = $staff->get($m->person_id);
                $m->display_name = $p ? ($p->full_name . " ({$p->employmentid})") : 'Unknown staff';
                $m->photo_url    = $p?->user?->avatar_url;
            }
            return $m;
        });

        $studentCount  = DeviceUserMapping::where('person_type', 'student')->count();
        $staffCount    = DeviceUserMapping::where('person_type', 'staff')->count();
        $unmappedCount = $this->unmappedPinsQuery()->count();

        $pagetitle = 'Device PIN Mappings';
        return view('attendance.admin.device-mappings', compact(
            'mappings', 'studentCount', 'staffCount', 'unmappedCount', 'pagetitle'
        ));
    }

    // =========================================================================
    // AJAX PERSON SEARCH (backs the Select2 widgets in both blades)
    // =========================================================================

    public function search(Request $request)
    {
        $type    = $request->input('type', 'student');
        $q       = trim((string) $request->input('q', ''));
        $page    = max((int) $request->input('page', 1), 1);
        // picker=1 is used by the Bulk Assign modal's table, which fetches
        // once and filters client-side — same pattern as the Mass Student
        // modal's loadStudents(). Everything else (Select2 dropdowns) keeps
        // the normal small page size.
        $perPage = $request->boolean('picker') ? 500 : self::SEARCH_PER_PAGE;

        if ($type === 'staff') {
            // Staff accounts live on `users` (role = 'Staff'); `staffbioinfo`
            // is a separate details table that's often missing for staff who
            // were created before HR data entry caught up. Source the people
            // from User so we find everyone who's actually a staff member,
            // and explicitly exclude anyone who's also a student (dual-role
            // edge case) — "staff who are not students".
            $query = User::query()
                ->staff() // scopeStaff(): whereHas('roles', fn($q) => $q->where('name', 'Staff'))
                ->whereDoesntHave('roles', fn($r) => $r->where('name', 'Student'))
                ->whereNull('student_id')
                ->with('staffemploymentDetails')
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->where(function ($w) use ($q) {
                        $w->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%")
                          ->orWhereHas('staffemploymentDetails', fn($sq) => $sq
                              ->where('employmentid', 'like', "%{$q}%")
                              ->orWhere('department', 'like', "%{$q}%"));
                    });
                })
                ->orderBy('name');

            $total = (clone $query)->count();
            $users = $query->forPage($page, $perPage)->get();

            // staffbioinfo.id is what the rest of the pipeline (StaffAttendance,
            // StaffAttendanceController, DeviceAttendanceProcessor) keys off,
            // so a staff user without that row can't be mapped safely.
            //
            // Fix: auto-provision the staffbioinfo row on the fly instead of
            // filtering these users out. `id` and `userid` are the only
            // NOT NULL columns with no default on staffbioinfo, so this is
            // safe — everything else (employmentid, department, etc.) can be
            // filled in later from the staff profile screen.
            //
            // The try/catch + null-safe operators + final filter below are
            // kept as a defensive fallback: if provisioning ever fails (race
            // condition, a future schema change reintroducing a required
            // column, etc.) we log it and drop just that one row instead of
            // breaking the picker for everyone else.
            $results = $users
                ->map(function ($u) {
                    $staff = $u->staffemploymentDetails;

                    if (!$staff) {
                        try {
                            $staff = Staff::firstOrCreate(
                                ['userid' => $u->id],
                                [
                                    'email'  => $u->email,
                                    'gender' => $u->gender,
                                ]
                            );
                        } catch (\Exception $e) {
                            Log::error(
                                'Failed to auto-provision staffbioinfo for user ' . $u->id . ': ' . $e->getMessage()
                            );
                            $staff = null;
                        }
                    }

                    return [
                        'id'       => $staff?->id,
                        'text'     => $u->name . ($staff?->employmentid ? " ({$staff->employmentid})" : ''),
                        'photo'    => $u->avatar_url,
                        'subtitle' => $staff?->job_title ?? $staff?->position ?? 'Staff',
                        'meta'     => [
                            'Staff ID'   => $staff?->employmentid ?: ('U-' . $u->id),
                            'Department' => $staff?->department ?? '—',
                        ],
                    ];
                })
                ->filter(fn($r) => $r['id'] !== null)
                ->values();
        } else {
            $query = Student::query()
                ->with(['picture', 'schoolClass.schoolclass.armRelation'])
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->where(function ($w) use ($q) {
                        $w->where('firstname', 'like', "%{$q}%")
                          ->orWhere('lastname', 'like', "%{$q}%")
                          ->orWhere('admissionNo', 'like', "%{$q}%");
                    });
                })
                ->orderBy('lastname');

            $total = (clone $query)->count();
            $rows  = $query->forPage($page, $perPage)->get();

            $results = $rows->map(function ($s) {
                $schoolclass = $s->schoolClass?->schoolclass;   // Schoolclass model, via Studentclass
                $className   = $schoolclass?->schoolclass;       // class name column, e.g. "JSS1"
                $armName     = $schoolclass?->armRelation?->arm; // adjust if Schoolarm's name column differs

                return [
                    'id'       => $s->id,
                    'text'     => "{$s->lastname} {$s->firstname} ({$s->admissionNo})",
                    'photo'    => $s->picture?->picture ? asset('storage/student_avatars/' . $s->picture->picture) : null,
                    'subtitle' => 'Student',
                    'meta'     => [
                        'Admission No' => $s->admissionNo ?? '—',
                        'Class'        => trim(($className ?? '') . ' ' . ($armName ?? '')) ?: '—',
                    ],
                ];
            })->values();
        }

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    // =========================================================================
    // CREATE MAPPINGS
    // =========================================================================

    // Single manual add: one device_pin, one person.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'device_pin'    => 'required|integer',
            'person_type'   => 'required|in:student,staff',
            'person_id'     => 'required|integer',
        ]);

        try {
            $mapping = DeviceUserMapping::updateOrCreate(
                ['device_serial' => $validated['device_serial'], 'device_pin' => $validated['device_pin']],
                ['person_type' => $validated['person_type'], 'person_id' => $validated['person_id'], 'active' => true]
            );

            $this->reprocessPendingLogs($validated['device_serial'], $validated['device_pin']);

            return response()->json(['success' => true, 'message' => 'Mapping saved.', 'data' => $mapping]);
        } catch (\Exception $e) {
            Log::error('DeviceUserMapping store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Bulk manual assign: many people, one device, sequential PINs starting
    // at `starting_pin`. Skips over any PIN already taken on that device.
    public function bulkManualAssign(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'starting_pin'  => 'required|integer|min:1',
            'person_type'   => 'required|in:student,staff',
            'person_ids'    => 'required|array|min:1',
            'person_ids.*'  => 'required|integer',
        ]);

        $created = [];
        $pin     = $validated['starting_pin'];

        DB::beginTransaction();
        try {
            foreach ($validated['person_ids'] as $personId) {
                while (DeviceUserMapping::where('device_serial', $validated['device_serial'])
                        ->where('device_pin', $pin)->exists()) {
                    $pin++;
                }

                DeviceUserMapping::create([
                    'device_serial' => $validated['device_serial'],
                    'device_pin'    => $pin,
                    'person_type'   => $validated['person_type'],
                    'person_id'     => $personId,
                    'active'        => true,
                ]);

                $created[] = ['person_id' => $personId, 'pin' => $pin];
                $pin++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk manual assign error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        foreach ($created as $row) {
            $this->reprocessPendingLogs($validated['device_serial'], $row['pin']);
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' mapping(s) created, starting at PIN ' . $validated['starting_pin'] . '.',
            'data'    => $created,
        ]);
    }

    // CSV bulk import. Expected columns: device_pin, person_type, identifier
    // identifier = admissionNo for students, employmentid for staff.
    public function bulkImport(Request $request)
    {
        $request->validate([
            'device_serial' => 'required|string',
            'csv_file'      => 'required|file|mimes:csv,txt',
        ]);

        $path   = $request->file('csv_file')->getRealPath();
        $handle = fopen($path, 'r');
        fgetcsv($handle); // skip header row

        $created = 0;
        $failed  = 0;
        $errors  = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                [$pin, $type, $identifier] = array_pad($row, 3, null);
                $pin        = trim((string) $pin);
                $type       = strtolower(trim((string) $type));
                $identifier = trim((string) $identifier);

                if (!$pin || !in_array($type, ['student', 'staff']) || !$identifier) {
                    $failed++;
                    $errors[] = 'Invalid row: ' . implode(',', $row);
                    continue;
                }

                $person = $type === 'student'
                    ? Student::where('admissionNo', $identifier)->first()
                    : Staff::where('employmentid', $identifier)->first();

                if (!$person) {
                    $failed++;
                    $errors[] = "No {$type} found for identifier '{$identifier}'";
                    continue;
                }

                DeviceUserMapping::updateOrCreate(
                    ['device_serial' => $request->device_serial, 'device_pin' => $pin],
                    ['person_type' => $type, 'person_id' => $person->id, 'active' => true]
                );
                $created++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            Log::error('Bulk import error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => "Imported {$created} mapping(s), {$failed} failed.",
            'errors'  => array_slice($errors, 0, 20),
        ]);
    }

    public function destroy($id)
    {
        DeviceUserMapping::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Mapping removed.']);
    }

    // =========================================================================
    // UNMAPPED PIN QUEUE
    // =========================================================================

    private function unmappedPinsQuery()
    {
        return DeviceAttendanceLog::where('processing_status', 'unmapped')
            ->select('device_serial', 'device_pin')
            ->selectRaw('COUNT(*) as punch_count')
            ->selectRaw('MAX(punch_time) as last_seen')
            ->groupBy('device_serial', 'device_pin');
    }

    public function unmapped()
    {
        $unmapped  = $this->unmappedPinsQuery()->orderByDesc('last_seen')->get();
        $pagetitle = 'Unmapped Device PINs';
        return view('attendance.admin.device-unmapped', compact('unmapped', 'pagetitle'));
    }

    public function quickAssign(Request $request)
    {
        $validated = $request->validate([
            'device_serial' => 'required|string',
            'device_pin'    => 'required|integer',
            'person_type'   => 'required|in:student,staff',
            'person_id'     => 'required|integer',
        ]);

        DeviceUserMapping::updateOrCreate(
            ['device_serial' => $validated['device_serial'], 'device_pin' => $validated['device_pin']],
            ['person_type' => $validated['person_type'], 'person_id' => $validated['person_id'], 'active' => true]
        );

        $this->reprocessPendingLogs($validated['device_serial'], $validated['device_pin']);

        return response()->json(['success' => true, 'message' => 'Assigned and past punches reprocessed.']);
    }

    // Re-runs any logs that arrived before this PIN had a mapping (or that
    // previously errored) now that we know who they belong to.
    private function reprocessPendingLogs(string $deviceSerial, int $pin): void
    {
        $logs = DeviceAttendanceLog::where('device_serial', $deviceSerial)
            ->where('device_pin', $pin)
            ->whereIn('processing_status', ['unmapped', 'pending', 'error'])
            ->get();

        $processor = app(\App\Services\DeviceAttendanceProcessor::class);
        foreach ($logs as $log) {
            $processor->process($log);
        }
    }
}