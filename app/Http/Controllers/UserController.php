<?php

namespace App\Http\Controllers;

use App\Exports\StaffUserBatchTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\StaffUsersImport;
use App\Models\BioModel;
use App\Models\Student;
use App\Models\Studentpicture;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;  // ← correct (Facade)
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View user|Create user|Update user|Delete user', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create user', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update user', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete user', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $pagetitle = "User Management";
        $data = User::latest()->get();
        $roles = Role::pluck('name', 'name')->toArray();
        $role_permissions = Role::all();

        $role_counts = [];
        foreach ($roles as $role) {
            $role_counts[$role] = User::role($role)->count();
        }
        $role_counts['No Role'] = User::doesntHave('roles')->count();

        return view('users.index', compact('data', 'roles', 'role_permissions', 'pagetitle', 'role_counts'));
    }

    public function create(): View
    {
        $title = "Create User";
        $roles = Role::pluck('name', 'name')->all();
        return view('users.create', compact('roles', 'title'));
    }

    public function store(Request $request): JsonResponse
    {
        Log::debug("Creating user", $request->all());

        try {
            if (!auth()->user()->hasPermissionTo('Create user')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have the right permissions',
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
                'roles' => 'required|array',
                'roles.*' => 'exists:roles,name',
                'phone_number' => 'nullable|string|regex:/^\+[1-9]\d{1,14}$/',
            ]);

            $input = $request->all();
            $plainPassword = $input['password'];
            $input['password'] = Hash::make($input['password']);

            $user = User::create($input);
            $user->syncRoles($request->input('roles'));

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'phone_number' => $user->phone_number,
                    'password' => $plainPassword,
                ],
            ], 201);

        } catch (ValidationException $e) {
            Log::error("Validation error creating user: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Create user error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
            ], 500);
        }
    }

    public function show($id): View
    {
        $pagetitle = "User Overview";

        $user = User::with([
            'roles',
            'bio',
            'student',
            'staffemploymentDetails',
            'staffPicture',
        ])->findOrFail($id);

        $userbio = $user->bio;
        $staffInfo = $user->staffemploymentDetails;
        $staffPicture = $user->staffPicture;
        $studentPicture = null;

        if ($user->isStudent() && $user->student_id) {
            $studentPicture = Studentpicture::where('studentid', $user->student_id)->first();
        }

        $isStudentUser = $user->hasRole('Student');
        $studentData = $user->student;
        $currentClass = null;
        $parentData = null;

        if ($isStudentUser && $studentData) {
            $currentClass = $studentData->currentClass;
            $parentData = $studentData->parent;
        }

        return view('users.overview', compact(
            'user', 'userbio', 'staffInfo', 'staffPicture', 'studentPicture',
            'pagetitle', 'isStudentUser', 'studentData', 'currentClass', 'parentData'
        ));
    }

    public function edit($id): View
    {
        if (auth()->user()->hasRole('Student')) {
            abort(403, 'Students are not allowed to edit profiles.');
        }

        $user = User::findOrFail($id);
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();

        return view('users.edit', compact('user', 'roles', 'userRole'));
    }

    public function update(Request $request, $id): JsonResponse
    {
        if (auth()->user()->hasRole('Student')) {
            return response()->json([
                'success' => false,
                'message' => 'Students are not allowed to edit profiles.',
            ], 403);
        }

        Log::debug("Updating user ID: {$id}", $request->all());

        try {
            if (!auth()->user()->hasPermissionTo('Update user')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have the right permissions',
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|min:8|confirmed',
                'roles' => 'required|array',
                'roles.*' => 'exists:roles,name',
                'phone_number' => 'nullable|string|regex:/^\+[1-9]\d{1,14}$/',
            ]);

            $input = $request->all();
            $plainPassword = !empty($input['password']) ? $input['password'] : null;

            if (!empty($input['password'])) {
                $input['password'] = Hash::make($input['password']);
            } else {
                $input = Arr::except($input, ['password']);
            }

            $user = User::findOrFail($id);
            $user->update($input);
            $user->syncRoles($request->input('roles'));

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'phone_number' => $user->phone_number,
                    'password' => $plainPassword,
                ],
            ], 200);

        } catch (ValidationException $e) {
            Log::error("Validation error updating user: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Update user error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        Log::debug("Attempting to delete user ID: {$id}");

        try {
            $user = User::findOrFail($id);
            $isStudent = $user->hasRole('Student');

            BioModel::where('user_id', $id)->delete();
            $user->roles()->detach();
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => $isStudent
                    ? 'User account removed. Student record remains in Student Management.'
                    : 'User deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error("Delete user error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
            ], 500);
        }
    }

    public function roles(): JsonResponse
    {
        $roles = Role::pluck('name')->all();
        return response()->json(['roles' => $roles]);
    }

    // ============================================================
    // EMAIL GENERATION HELPERS
    // ============================================================

    private function cleanString($string)
    {
        if (empty($string)) return 'user';

        $string = Str::ascii($string);
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9]/', '', $string);

        if (empty($string)) {
            return 'student';
        }

        return $string;
    }

    /**
     * Builds the canonical firstname.lastname@csskabba.ng email for a
     * student, ALWAYS derived from the student's current firstname/lastname
     * — this is the single source of truth other methods should call
     * whenever they need "what should this student's email be right now."
     *
     * @param  object      $student        Row/model with firstname/lastname/admissionNo/id
     * @param  int|null    $excludeUserId   When regenerating for an EXISTING
     *                                      account (reset/reprint), pass that
     *                                      user's own id so the uniqueness
     *                                      check doesn't collide with itself
     *                                      and needlessly append a number.
     */
    private function generateStudentEmail($student, $excludeUserId = null)
    {
        $domain = '@csskabba.ng';

        $firstname = $this->cleanString($student->firstname);
        $lastname = $this->cleanString($student->lastname);

        if (!empty($firstname) && !empty($lastname)) {
            $baseEmail = $firstname . '.' . $lastname;
        } elseif (!empty($firstname)) {
            $baseEmail = $firstname;
        } elseif (!empty($lastname)) {
            $baseEmail = $lastname;
        } else {
            $baseEmail = !empty($student->admissionNo)
                ? $this->cleanString($student->admissionNo)
                : 'student_' . $student->id;
        }

        $baseEmail = trim($baseEmail, '.');
        $baseEmail = preg_replace('/\.+/', '.', $baseEmail);

        $email = $baseEmail . $domain;

        $counter = 1;
        while ($this->emailTakenByAnotherUser($email, $excludeUserId)) {
            $email = $baseEmail . $counter . $domain;
            $counter++;
        }

        return $email;
    }

    /**
     * True if $email belongs to some OTHER user than $excludeUserId.
     * Used so regenerating an existing account's own email doesn't
     * falsely register as "taken" and get a stray number appended.
     */
    private function emailTakenByAnotherUser($email, $excludeUserId = null)
    {
        $query = User::where('email', $email);
        if (!empty($excludeUserId)) {
            $query->where('id', '!=', $excludeUserId);
        }
        return $query->exists();
    }

    private function generateUsername($student)
    {
        $baseUsername = !empty($student->admissionNo)
            ? $this->cleanString($student->admissionNo)
            : 'student_' . $student->id;

        $username = $baseUsername;

        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    private function generateRandomPassword()
    {
        return strtoupper(Str::random(4)) . rand(100, 999) . strtolower(Str::random(3));
    }

    /**
     * Given an existing $user and their linked $student record, brings the
     * user's `name` and `email` back in line with the student's CURRENT
     * firstname/lastname if either has drifted (e.g. the student was
     * renamed after the account was first created). Returns the array of
     * fields that actually need updating (empty if already in sync) — the
     * caller decides when to persist (e.g. bundled into the same ->update()
     * call as a password reset).
     */
    private function syncedAccountFields($user, $student)
    {
        $updates = [];

        $currentName = trim("{$student->firstname} {$student->lastname}");
        if ($currentName !== '' && $user->name !== $currentName) {
            $updates['name'] = $currentName;
        }

        $expectedEmail = $this->generateStudentEmail($student, $user->id);
        if ($user->email !== $expectedEmail) {
            $updates['email'] = $expectedEmail;
        }

        return $updates;
    }

    // ============================================================
    // SINGLE STUDENT CREATION
    // ============================================================

    public function storeStudent(Request $request): JsonResponse
    {
        Log::debug("Creating student user", $request->all());

        try {
            if (!auth()->user()->hasPermissionTo('Create user')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have the right permissions',
                ], 403);
            }

            $validated = $request->validate([
                'student_id' => 'required|exists:studentRegistration,id',
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'username' => 'nullable|string|unique:users,username|max:255',
                'password' => 'required|min:8|confirmed',
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:roles,name',
            ]);

            $student = Student::findOrFail($validated['student_id']);

            // NOTE: the frontend now always sends a freshly-computed email
            // (built from the student's current name at selection time), but
            // we still regenerate here as the authoritative fallback/guard
            // rather than trusting client input blindly.
            $email = $request->input('email');
            if (empty($email)) {
                $email = $this->generateStudentEmail($student);
            }

            $username = $request->input('username');
            if (empty($username)) {
                $username = $this->generateUsername($student);
            } else {
                $username = $this->cleanString($username);
            }

            $plainPassword = $validated['password'];

            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'username' => $username,
                'student_id' => $student->id,
                'password' => Hash::make($plainPassword),
            ]);

            $user->syncRoles($request->input('roles'));

            BioModel::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'othernames' => $student->othername ?? '',
                    'phone' => $student->phone_number ?? '',
                    'address' => $student->home_address2 ?? '',
                    'gender' => $student->gender ?? '',
                    'maritalstatus' => '',
                    'nationality' => $student->nationality ?? '',
                    'dob' => $student->dateofbirth ?? '',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Student user created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'password' => $plainPassword,
                ],
            ], 201);

        } catch (ValidationException $e) {
            Log::error("Validation error creating student user: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Create student user error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student user: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // MASS STUDENT OPERATIONS
    // ============================================================

    public function massCreateStudents(Request $request): JsonResponse
    {
        Log::debug("Mass operation on student users", $request->all());

        try {
            if (!auth()->user()->hasPermissionTo('Create user')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have the right permissions',
                ], 403);
            }

            $validated = $request->validate([
                'students' => 'required|array|min:1',
                'students.*.student_id' => 'required|exists:studentRegistration,id',
                'action_type' => 'required|in:create,reset,revoke,reprint',
                'password_type' => 'required_if:action_type,create,reset|in:same,individual',
                'shared_password' => 'required_if:password_type,same|nullable|string|min:6',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name',
            ]);

            DB::beginTransaction();

            $created = [];
            $reset = [];
            $revoked = [];
            $reprinted = [];
            $skipped = [];
            $errors = [];

            foreach ($validated['students'] as $entry) {
                $studentId = $entry['student_id'];

                $student = DB::table('studentRegistration')->where('id', $studentId)->first();

                if (!$student) {
                    $errors[] = "Student ID {$studentId} not found.";
                    continue;
                }

                $existingUser = User::where('student_id', $studentId)->first();

                switch ($validated['action_type']) {
                    case 'create':
                        if ($existingUser) {
                            $skipped[] = trim("{$student->firstname} {$student->lastname} (already has account)");
                            continue 2;
                        }
                        $result = $this->createStudentAccount($student, $validated);
                        if ($result['success']) {
                            $created[] = $result['data'];
                        } else {
                            $errors[] = $result['error'];
                        }
                        break;

                    case 'reset':
                        if (!$existingUser) {
                            $skipped[] = trim("{$student->firstname} {$student->lastname} (no account to reset)");
                            continue 2;
                        }
                        $result = $this->resetStudentPassword($existingUser, $student, $validated);
                        if ($result['success']) {
                            $reset[] = $result['data'];
                        } else {
                            $errors[] = $result['error'];
                        }
                        break;

                    case 'revoke':
                        if (!$existingUser) {
                            $skipped[] = trim("{$student->firstname} {$student->lastname} (no account to revoke)");
                            continue 2;
                        }
                        $result = $this->revokeStudentAccount($existingUser, $student);
                        if ($result['success']) {
                            $revoked[] = $result['data'];
                        } else {
                            $errors[] = $result['error'];
                        }
                        break;

                    case 'reprint':
                        if (!$existingUser) {
                            $skipped[] = trim("{$student->firstname} {$student->lastname} (no account to reprint)");
                            continue 2;
                        }
                        $result = $this->reprintStudentCredentials($existingUser, $student);
                        if ($result['success']) {
                            $reprinted[] = $result['data'];
                        } else {
                            $errors[] = $result['error'];
                        }
                        break;
                }
            }

            DB::commit();

            $responseData = [
                'success' => true,
                'action_type' => $validated['action_type'],
                'created' => $created,
                'reset' => $reset,
                'revoked' => $revoked,
                'reprinted' => $reprinted,
                'skipped' => $skipped,
                'errors' => $errors,
                'created_count' => count($created),
                'reset_count' => count($reset),
                'revoked_count' => count($revoked),
                'reprinted_count' => count($reprinted),
            ];

            $responseData['message'] = $this->buildActionResultMessage($responseData);

            return response()->json($responseData, 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error("Validation error mass operation: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Mass operation error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to process: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function createStudentAccount($student, $validated)
    {
        try {
            $email = $this->generateStudentEmail($student);
            $username = $this->generateUsername($student);

            $plainPassword = $validated['password_type'] === 'same'
                ? $validated['shared_password']
                : $this->generateRandomPassword();

            $user = User::create([
                'name' => trim("{$student->firstname} {$student->lastname}"),
                'email' => $email,
                'username' => $username,
                'student_id' => $student->id,
                'password' => Hash::make($plainPassword),
            ]);

            $user->syncRoles(['Student']);

            BioModel::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'othernames' => $student->othername ?? '',
                    'phone' => $student->phone_number ?? '',
                    'address' => $student->home_address2 ?? '',
                    'gender' => $student->gender ?? '',
                    'maritalstatus' => '',
                    'nationality' => $student->nationality ?? '',
                    'dob' => $student->dateofbirth ?? '',
                ]
            );

            return [
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'student_id' => $student->id,
                    'name' => $user->name,
                    'email' => $email,
                    'username' => $username,
                    'password' => $plainPassword,
                    'admissionNo' => $student->admissionNo ?? '',
                    'class_name' => $student->class_name ?? '',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Failed to create account for {$student->firstname} {$student->lastname}: " . $e->getMessage()
            ];
        }
    }

    private function resetStudentPassword($user, $student, $validated)
    {
        try {
            $plainPassword = $validated['password_type'] === 'same'
                ? $validated['shared_password']
                : $this->generateRandomPassword();

            // FIX: previously this only ever updated the password, then
            // returned $user->name / $user->email as-is — whatever was
            // stored at account-creation time, forever. If the student was
            // renamed afterward, resets kept regenerating passwords for an
            // email built from their OLD name. Now every reset also re-syncs
            // name/email to the student's CURRENT record in the same update.
            $updates = array_merge(
                ['password' => Hash::make($plainPassword)],
                $this->syncedAccountFields($user, $student)
            );

            $user->update($updates);

            if (!$user->hasRole('Student')) {
                $user->syncRoles(['Student']);
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'student_id' => $student->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'password' => $plainPassword,
                    'admissionNo' => $student->admissionNo ?? '',
                    'class_name' => $student->class_name ?? '',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Failed to reset password for {$student->firstname} {$student->lastname}: " . $e->getMessage()
            ];
        }
    }

    private function revokeStudentAccount($user, $student)
    {
        try {
            $name = $user->name;
            $user->roles()->detach();
            $user->delete();

            return [
                'success' => true,
                'data' => [
                    'student_id' => $student->id,
                    'name' => $name,
                    'admissionNo' => $student->admissionNo ?? '',
                    'class_name' => $student->class_name ?? '',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Failed to revoke account: " . $e->getMessage()
            ];
        }
    }

    private function reprintStudentCredentials($user, $student)
    {
        try {
            // Same self-healing sync as reset: a reprint should always show
            // the CURRENT name/email, not whatever was stored historically.
            $updates = $this->syncedAccountFields($user, $student);
            if (!empty($updates)) {
                $user->update($updates);
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'student_id' => $student->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'admissionNo' => $student->admissionNo ?? '',
                    'class_name' => $student->class_name ?? '',
                    'note' => 'Password not shown for security.',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Failed to retrieve credentials for {$student->firstname} {$student->lastname}"
            ];
        }
    }

    private function buildActionResultMessage($data)
    {
        $parts = [];
        if ($data['created_count'] > 0) $parts[] = $data['created_count'] . ' account(s) created';
        if ($data['reset_count'] > 0) $parts[] = $data['reset_count'] . ' password(s) reset';
        if ($data['revoked_count'] > 0) $parts[] = $data['revoked_count'] . ' account(s) revoked';
        if ($data['reprinted_count'] > 0) $parts[] = $data['reprinted_count'] . ' credential(s) reprinted';

        $message = implode(', ', $parts) . ' successfully.';
        if (count($data['skipped']) > 0) $message .= ' ' . count($data['skipped']) . ' skipped.';

        return $message;
    }

    // ============================================================
    // SINGLE STUDENT PASSWORD RESET
    // ============================================================

    public function resetSingleStudentPassword(Request $request, $id): JsonResponse
    {
        try {
            if (!auth()->user()->hasPermissionTo('Update user')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions.',
                ], 403);
            }

            $user = User::findOrFail($id);

            if (!$user->hasRole('Student')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a student.',
                ], 422);
            }

            $plainPassword = $this->generateRandomPassword();
            $updates = ['password' => Hash::make($plainPassword)];

            // FIX: this endpoint (the single "reset password" key icon on
            // the main Users table) previously never touched name/email at
            // all, so a renamed student's account kept showing their old
            // name's email forever. Now it re-syncs from the linked student
            // record — same fix as the mass-reset path.
            $student = $user->student;
            if ($student) {
                $updates = array_merge($updates, $this->syncedAccountFields($user, $student));
            }

            $user->update($updates);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
                'password' => $plainPassword,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Reset password error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password.',
            ], 500);
        }
    }

    public function revokeStudentPassword(Request $request): JsonResponse
    {
        Log::debug("Resetting student password(s)", $request->all());

        try {
            if (!auth()->user()->hasPermissionTo('Update user')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions.',
                ], 403);
            }

            $studentIds = $request->input('student_ids', []);

            if (empty($studentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No student IDs provided.',
                ], 422);
            }

            $users = User::whereIn('student_id', $studentIds)->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found for the selected students.',
                ], 404);
            }

            $plainPassword = 'ChangeMe@123';
            $newPassword = Hash::make($plainPassword);
            $count = 0;
            $revoked = [];

            foreach ($users as $user) {
                if (!$user->hasRole('Student')) {
                    continue;
                }

                $user->update(['password' => $newPassword]);
                $count++;

                $revoked[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'admissionNo' => $user->student?->admissionNo ?? '',
                    'password' => $plainPassword,
                ];
            }

            $message = $count > 0
                ? "{$count} student password(s) reset successfully. New password: {$plainPassword}"
                : "No student passwords were reset.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $count,
                'revoked' => $revoked,
            ]);

        } catch (\Exception $e) {
            Log::error("revokeStudentPassword error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset passwords: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // GET STUDENTS FOR MODALS - FIXED WITH UNIQUE CLASSES
    // ============================================================

    public function getStudents(Request $request): JsonResponse
{
    try {
        $search = trim($request->get('search', ''));
        $limit = min((int) $request->get('limit', 2000), 5000);
        $classId = $request->get('class_id');
        $armId = $request->get('arm_id');
        $hasAccount = $request->get('has_account');

        $query = DB::table('studentRegistration as sr')
            ->leftJoin('studentclass as sc', function ($join) {
                $join->on('sc.studentId', '=', 'sr.id')
                    ->whereRaw('sc.id = (SELECT id FROM studentclass WHERE studentId = sr.id ORDER BY id DESC LIMIT 1)');
            })
            ->leftJoin('schoolclass as cls', 'cls.id', '=', 'sc.schoolclassid')
            ->leftJoin('schoolarm as arm', 'arm.id', '=', 'cls.arm')
            ->leftJoin('users as u', 'u.student_id', '=', 'sr.id')
            ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 'sr.id')  // ADD THIS LINE
            ->whereNotNull('sr.admissionNo')
            ->select(
                'sr.id',
                'sr.admissionNo',
                'sr.firstname',
                'sr.lastname',
                'sr.email',
                'sr.phone_number',
                'cls.id as class_id',
                'cls.schoolclass as class_name',
                'arm.id as arm_id',
                'arm.arm as arm_name',
                DB::raw('CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END as has_account'),
                DB::raw('u.id as user_id'),
                DB::raw('u.username as username'),
                'sp.picture as picture'  // ADD THIS LINE - get the picture filename
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sr.admissionNo', 'like', "%{$search}%")
                    ->orWhere('sr.firstname', 'like', "%{$search}%")
                    ->orWhere('sr.lastname', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(sr.firstname, ' ', sr.lastname) LIKE ?", ["%{$search}%"]);
            });
        }

        if ($classId) {
            $query->where('cls.id', $classId);
        }

        if ($armId) {
            $query->where('arm.id', $armId);
        }

        if ($hasAccount === 'yes') {
            $query->whereNotNull('u.id');
        } elseif ($hasAccount === 'no') {
            $query->whereNull('u.id');
        }

        $students = $query
            ->orderBy('sr.lastname')
            ->orderBy('sr.firstname')
            ->limit($limit)
            ->get();

        // Get UNIQUE class names
        $classes = DB::table('schoolclass as cls')
            ->join('schoolarm as arm', 'arm.id', '=', 'cls.arm')
            ->select('cls.id', DB::raw("CONCAT(cls.schoolclass, ' ', arm.arm) as name"))
            ->orderByRaw("cls.schoolclass, arm.arm")
            ->get();

        // Get all arms
        $arms = DB::table('schoolarm')
            ->select('id', 'arm as name')
            ->orderBy('arm')
            ->get();

        // Get class-arm relationships
        $classArms = DB::table('schoolclass')
            ->join('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id as class_id', 'schoolclass.schoolclass as class_name', 'schoolarm.id as arm_id', 'schoolarm.arm as arm_name')
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        return response()->json([
            'success' => true,
            'students' => $students->map(fn ($s) => [
                'id' => $s->id,
                'admissionNo' => $s->admissionNo,
                'name' => trim("{$s->firstname} {$s->lastname}"),
                'firstname' => $s->firstname,
                'lastname' => $s->lastname,
                'email' => $s->email ?? '',
                'phone_number' => $s->phone_number ?? '',
                'class_id' => $s->class_id,
                'class_name' => $s->class_name ?? '',
                'arm_id' => $s->arm_id,
                'arm_name' => $s->arm_name ?? '',
                'has_account' => (bool) $s->has_account,
                'user_id' => $s->user_id,
                'username' => $s->username,
                'picture' => $s->picture,  // ADD THIS LINE
                'photo_url' => $s->picture && $s->picture != 'unnamed.jpg' && $s->picture != ''
                    ? asset('storage/images/student_avatars/' . $s->picture)
                    : null,  // ADD THIS LINE - generate full URL
            ])->values()->toArray(),
            'classes' => $classes,
            'arms' => $arms,
            'class_arms' => $classArms,
        ]);

    } catch (\Exception $e) {
        Log::error("getStudents error: {$e->getMessage()}");
        return response()->json([
            'success' => false,
            'message' => 'Failed to load students: ' . $e->getMessage(),
        ], 500);
    }
}


    // ============================================================
    // EXTRA METHODS
    // ============================================================

    public function allUsers(Request $request): JsonResponse
    {
        $users = User::with('roles')->get();
        return response()->json(['users' => $users]);
    }

    public function paginate(Request $request): JsonResponse
    {
        $users = User::with('roles')->paginate(15);
        return response()->json($users);
    }

    public function createFromStudentForm(): View
    {
        return view('users.create-from-student');
    }

    public function createFromStudent(Request $request): JsonResponse
    {
        return $this->storeStudent($request);
    }

    public function getStudentCredentials(Request $request): JsonResponse
    {
        $studentId = $request->input('student_id');
        $user = User::where('student_id', $studentId)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No user found for this student.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'credentials' => [
                'email' => $user->email,
                'username' => $user->username,
            ],
        ]);
    }

    public function bulkReprintCredentials(Request $request): JsonResponse
    {
        $studentIds = $request->input('student_ids', []);
        $users = User::whereIn('student_id', $studentIds)->get();

        $credentials = [];
        foreach ($users as $user) {
            $credentials[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'admissionNo' => $user->student?->admissionNo ?? '',
            ];
        }

        return response()->json([
            'success' => true,
            'credentials' => $credentials,
        ]);
    }


    /**
 * Download blank template for bulk Staff user creation
 */
public function generateStaffTemplate(Request $request)
{
    $request->validate([
        'rows' => 'nullable|integer|min:1|max:200',
    ]);

    $rows     = (int) $request->input('rows', 30);
    $filename = 'Staff_Users_Batch_Template_' . now()->format('Ymd-His') . '.xlsx';

    return Excel::download(
        new StaffUserBatchTemplateExport($rows),
        $filename
    );
}

/**
 * Import Staff users from the filled template
 */
public function importStaffUsers(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'filesheet' => 'required|mimes:xlsx,xls,csv|max:10240',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422);
    }

    if (!auth()->user()->hasPermissionTo('Create user')) {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to create users.',
        ], 403);
    }

    try {
        $import = new StaffUsersImport();
        Excel::import($import, $request->file('filesheet'));

        $created  = $import->getCreated();
        $skipped  = $import->getSkipped();
        $failures = $import->failures();

        $message = count($created) . ' staff user(s) created successfully.';
        if (count($skipped) > 0) {
            $message .= ' ' . count($skipped) . ' row(s) skipped.';
        }

        return response()->json([
            'success'       => true,
            'message'       => $message,
            'created'       => $created,
            'skipped'       => $skipped,
            'failures'      => collect($failures)->map(fn ($f) => [
                'row'       => $f->row(),
                'attribute' => $f->attribute(),
                'errors'    => $f->errors(),
            ])->values(),
            'created_count' => count($created),
            'skipped_count' => count($skipped),
        ]);
    } catch (\Exception $e) {
        Log::error('Staff user import failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Import failed: ' . $e->getMessage(),
        ], 500);
    }
}
}
