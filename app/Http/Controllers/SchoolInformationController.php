<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolInformationController extends Controller
{
    // Uncomment when permission middleware is configured:
    // public function __construct()
    // {
    //     $this->middleware('permission:View schoolinformation|Create schoolinformation|Update schoolinformation|Delete schoolinformation', ['only' => ['index', 'show']]);
    //     $this->middleware('permission:Create schoolinformation', ['only' => ['create', 'store']]);
    //     $this->middleware('permission:Update schoolinformation', ['only' => ['edit', 'update']]);
    //     $this->middleware('permission:Delete schoolinformation', ['only' => ['destroy']]);
    // }

    // ────────────────────────────────────────────────────────────────────────
    // INDEX
    // ────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $pagetitle = 'School Information Management';

        $data = SchoolInformation::latest()->paginate(10);

        $status_counts = [
            'Active'   => SchoolInformation::where('is_active', true)->count(),
            'Inactive' => SchoolInformation::where('is_active', false)->count(),
        ];

        return view('schoolinformation.index', compact('data', 'pagetitle', 'status_counts'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    // ────────────────────────────────────────────────────────────────────────
    // CREATE (blade form – optional, most usage goes through the modal)
    // ────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $title = 'Create School Information';
        return view('schoolinformation.create', compact('title'));
    }

    // ────────────────────────────────────────────────────────────────────────
    // STORE
    // ────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        Log::debug('SchoolInformation@store called', [
            'has_files'    => array_keys($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
        ]);

        try {
            $validated = $request->validate([
                'school_name'               => 'required|string|max:255',
                'school_address'            => 'required|string|max:500',
                'school_phones'             => 'required|array|min:1',
                'school_phones.*'           => 'required|string|max:20',
                'school_email'              => 'required|email:rfc,dns|unique:school_information,school_email',
                'school_logo'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'app_logo'                  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'school_stamp'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'school_motto'              => 'nullable|string|max:255',
                'school_website'            => 'nullable|url|max:255',
                'no_of_times_school_opened' => 'required|integer|min:0',
                'date_school_opened'        => 'nullable|date',
                'date_school_closed'        => 'nullable|date|after_or_equal:date_school_opened',
                'date_next_term_begins'     => 'nullable|date',
                'is_active'                 => 'sometimes|boolean',
            ], [
                'school_phones.required'    => 'At least one phone number is required.',
                'school_phones.*.required'  => 'Each phone number must not be empty.',
                'date_school_closed.after_or_equal' => 'School closed date must be on or after the opened date.',
            ]);

            // ── File uploads ────────────────────────────────────────────────
            $validated['school_logo']  = $this->uploadFile($request, 'school_logo',  'school_logos');
            $validated['app_logo']     = $this->uploadFile($request, 'app_logo',     'app_logos');
            $validated['school_stamp'] = $this->uploadFile($request, 'school_stamp', 'school_stamps');

            // ── Active flag (only one school active at a time) ──────────────
            $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);
            if ($isActive) {
                SchoolInformation::where('is_active', true)->update(['is_active' => false]);
            }
            $validated['is_active'] = $isActive;

            $school = SchoolInformation::create($validated);

            Log::info("School created: ID {$school->id} — {$school->school_name}");

            return response()->json([
                'success' => true,
                'message' => 'School information created successfully.',
                'school'  => $this->schoolSummary($school),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error("School store error: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create school: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // SHOW
    // ────────────────────────────────────────────────────────────────────────

    public function show($id): View
    {
        $pagetitle = 'School Information Overview';
        $school    = SchoolInformation::findOrFail($id);
        return view('schoolinformation.show', compact('school', 'pagetitle'));
    }

    // ────────────────────────────────────────────────────────────────────────
    // EDIT (blade form – optional)
    // ────────────────────────────────────────────────────────────────────────

    public function edit($id): View
    {
        $school = SchoolInformation::findOrFail($id);
        return view('schoolinformation.edit', compact('school'));
    }

    // ────────────────────────────────────────────────────────────────────────
    // EDIT JSON  (called by the modal via fetch)
    // ────────────────────────────────────────────────────────────────────────

    public function editJson($id): JsonResponse
    {
        try {
            $school = SchoolInformation::findOrFail($id);

            // Decode phones if stored as JSON string
            $phones = $school->school_phones;
            if (is_string($phones)) {
                $phones = json_decode($phones, true) ?? [];
            }

            return response()->json([
                'success' => true,
                'school'  => [
                    'id'                        => $school->id,
                    'school_name'               => $school->school_name,
                    'school_address'            => $school->school_address,
                    'school_phones'             => $phones,
                    'school_email'              => $school->school_email,
                    'school_motto'              => $school->school_motto,
                    'school_website'            => $school->school_website,
                    'no_of_times_school_opened' => $school->no_of_times_school_opened,
                    'date_school_opened'        => $school->date_school_opened
                                                    ? $school->date_school_opened->format('Y-m-d')
                                                    : null,
                    'date_school_closed'        => $school->date_school_closed
                                                    ? $school->date_school_closed->format('Y-m-d')
                                                    : null,
                    'date_next_term_begins'     => $school->date_next_term_begins
                                                    ? $school->date_next_term_begins->format('Y-m-d')
                                                    : null,
                    'is_active'                 => (bool) $school->is_active,
                    'logo_url'                  => $school->getLogoUrlAttribute(),
                    'app_logo_url'              => $school->getAppLogoUrlAttribute(),
                    'stamp_url'                 => $school->getStampUrlAttribute(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error("editJson error for ID {$id}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to load school data: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // UPDATE
    // ────────────────────────────────────────────────────────────────────────

    public function update(Request $request, $id): JsonResponse
    {
        Log::debug("SchoolInformation@update ID={$id}", [
            'has_files'    => array_keys($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
            '_method'      => $request->input('_method'),
        ]);

        try {
            $school = SchoolInformation::findOrFail($id);

            $validated = $request->validate([
                'school_name'               => 'required|string|max:255',
                'school_address'            => 'required|string|max:500',
                'school_phones'             => 'required|array|min:1',
                'school_phones.*'           => 'required|string|max:20',
                'school_email'              => "required|email|unique:school_information,school_email,{$id}",
                'school_logo'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'app_logo'                  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'school_stamp'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'school_motto'              => 'nullable|string|max:255',
                'school_website'            => 'nullable|url|max:255',
                'no_of_times_school_opened' => 'required|integer|min:0',
                'date_school_opened'        => 'nullable|date',
                'date_school_closed'        => 'nullable|date|after_or_equal:date_school_opened',
                'date_next_term_begins'     => 'nullable|date',
                'is_active'                 => 'sometimes|boolean',
            ], [
                'date_school_closed.after_or_equal' => 'School closed date must be on or after the opened date.',
            ]);

            // ── File uploads: replace only if a new file is supplied ────────
            $validated['school_logo']  = $this->replaceFile($request, 'school_logo',  'school_logos',  $school->school_logo);
            $validated['app_logo']     = $this->replaceFile($request, 'app_logo',     'app_logos',     $school->app_logo);
            $validated['school_stamp'] = $this->replaceFile($request, 'school_stamp', 'school_stamps', $school->school_stamp);

            // ── Active flag ─────────────────────────────────────────────────
            $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);
            if ($isActive && !$school->is_active) {
                SchoolInformation::where('is_active', true)
                    ->where('id', '!=', $id)
                    ->update(['is_active' => false]);
            }
            $validated['is_active'] = $isActive;

            $school->update($validated);

            Log::info("School updated: ID {$id}");

            return response()->json([
                'success' => true,
                'message' => 'School information updated successfully.',
                'school'  => $this->schoolSummary($school->fresh()),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error("School update error ID={$id}: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update school: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // DESTROY
    // ────────────────────────────────────────────────────────────────────────

    public function destroy($id): JsonResponse
    {
        try {
            $school = SchoolInformation::findOrFail($id);

            $this->deleteStoredFile($school->school_logo);
            $this->deleteStoredFile($school->app_logo);
            $this->deleteStoredFile($school->school_stamp);

            $school->delete();

            Log::info("School deleted: ID {$id}");

            return response()->json([
                'success' => true,
                'message' => 'School information deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error("School destroy error ID={$id}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // BULK DESTROY
    // ────────────────────────────────────────────────────────────────────────

    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No schools selected for deletion.',
                ], 400);
            }

            $deleted = 0;
            SchoolInformation::whereIn('id', $ids)->each(function ($school) use (&$deleted) {
                $this->deleteStoredFile($school->school_logo);
                $this->deleteStoredFile($school->app_logo);
                $this->deleteStoredFile($school->school_stamp);
                $school->delete();
                $deleted++;
            });

            return response()->json([
                'success' => true,
                'message' => "{$deleted} school(s) deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            Log::error("Bulk delete error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schools: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Upload a new file and return its storage path, or null if none supplied.
     */
    private function uploadFile(Request $request, string $field, string $directory): ?string
    {
        if ($request->hasFile($field) && $request->file($field)->isValid()) {
            $file = $request->file($field);
            Log::debug("Uploading {$field}: {$file->getClientOriginalName()} ({$file->getSize()} bytes)");
            return $file->store($directory, 'public');
        }
        return null;
    }

    /**
     * Replace an existing file with a new upload, deleting the old one.
     * Returns the new path if uploaded, otherwise preserves the existing path.
     */
    private function replaceFile(Request $request, string $field, string $directory, ?string $existing): ?string
    {
        if ($request->hasFile($field) && $request->file($field)->isValid()) {
            // Delete old file
            $this->deleteStoredFile($existing);

            $file = $request->file($field);
            Log::debug("Replacing {$field}: {$file->getClientOriginalName()} ({$file->getSize()} bytes)");
            return $file->store($directory, 'public');
        }

        // No new file supplied — keep whatever was there before
        return $existing;
    }

    /**
     * Safely delete a file from the public disk.
     */
    private function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Return a small array summary of a school (used in JSON responses).
     */
    private function schoolSummary(SchoolInformation $school): array
    {
        return [
            'id'          => $school->id,
            'school_name' => $school->school_name,
            'school_email'=> $school->school_email,
            'is_active'   => $school->is_active,
            'logo_url'    => $school->getLogoUrlAttribute(),
        ];
    }
}
