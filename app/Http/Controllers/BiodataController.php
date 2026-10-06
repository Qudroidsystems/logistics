<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BioModel;
use App\Models\Staff;
use App\Models\Staffpicture;
use App\Models\StaffQualification;
use App\Models\Student;
use App\Models\Studentpicture;
use App\Models\ParentRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BiodataController extends Controller
{
    // Validation rules constants
    const AVATAR_RULES = [
        'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
    ];

    const PROFILE_RULES = [
        'fname' => 'required|string|max:100',
        'lname' => 'required|string|max:100',
        'oname' => 'nullable|string|max:100',
        'phone' => 'nullable|string|max:20',
        'gender' => 'nullable|in:male,female,other',
        'maritalstatus' => 'nullable|string|max:20',
        'nationality' => 'nullable|string|max:100',
        'dob' => 'nullable|date',
        'address' => 'nullable|string|max:500',
    ];

    /**
     * Display profile settings page
     */
    public function show(string $id)
    {
        $user = User::with([
            'bio',
            'qualifications',
            'staffemploymentDetails',
            'staffPicture',
            'roles'
        ])->findOrFail($id);

        $studentData = null;
        $parentData = null;
        $studentPicture = null;
        $currentClass = null;
        $classHistory = null;

        if ($user->isStudent() && $user->student_id) {
            $studentData = Student::with(['parent', 'picture'])->find($user->student_id);

            if ($studentData) {
                $parentData = $studentData->parent;
                $studentPicture = $studentData->picture;

                $currentClass = \App\Models\Studentclass::where('studentId', $studentData->id)
                    ->with(['schoolclass.armRelation', 'term', 'session'])
                    ->latest()
                    ->first();

                $classHistory = \App\Models\Studentclass::where('studentId', $studentData->id)
                    ->with(['schoolclass.armRelation', 'term', 'session'])
                    ->orderByDesc('sessionid')
                    ->orderByDesc('termid')
                    ->get();
            }
        }

        $userbio = $user->bio;
        $staffInfo = $user->staffemploymentDetails;
        $qualifications = $user->qualifications;
        $staffPicture = $user->staffPicture;

        $isStaff = $user->isStaff();
        $isStudent = $user->isStudent();

        $pagetitle = $user->name . " - Profile Settings";

        return view('users.settings', compact(
            'user',
            'userbio',
            'staffInfo',
            'qualifications',
            'staffPicture',
            'studentData',
            'parentData',
            'studentPicture',
            'currentClass',
            'classHistory',
            'isStaff',
            'isStudent',
            'pagetitle'
        ));
    }

    /**
     * Update personal information
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            ...self::PROFILE_RULES,
        ]);

        try {
            $user = User::findOrFail($request->id);

            // Update full name
            $fullName = trim($request->fname . ' ' . ($request->oname ? $request->oname . ' ' : '') . $request->lname);
            $user->name = $fullName;
            $user->save();

            // Update bio
            BioModel::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'firstname' => $request->fname,
                    'lastname' => $request->lname,
                    'othernames' => $request->oname ?? '',
                    'phone' => $request->phone ?? '',
                    'gender' => $request->gender ?? '',
                    'maritalstatus' => $request->maritalstatus ?? '',
                    'nationality' => $request->nationality ?? '',
                    'dob' => $request->dob,
                    'address' => $request->address ?? '',
                ]
            );

            return back()->with('success', 'Personal information updated successfully!');
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update profile.');
        }
    }

    /**
     * Update avatar via AJAX
     */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            ...self::AVATAR_RULES,
        ]);

        if ($validator->fails()) {
            return $this->avatarResponse(false, $validator->errors()->first());
        }

        try {
            $user = User::findOrFail($request->id);
            $file = $request->file('avatar');

            // Generate unique filename
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Get user configuration
            $config = $this->getUserAvatarConfig($user);
            $directory = storage_path('app/public/' . $config['path']);

            // Ensure directory exists
            $this->ensureDirectoryExists($directory);

            // Delete old avatar
            $this->deleteOldAvatar($user, $directory, $config);

            // Save new file
            $fullPath = $directory . '/' . $filename;
            $file->move($directory, $filename);

            // Verify file was saved
            if (!file_exists($fullPath)) {
                throw new \Exception("File was not saved to disk.");
            }

            clearstatcache(true, $fullPath);

            // Update database records
            $this->updateAvatarRecords($user, $filename, $config);

            // Generate URL
            $avatarUrl = asset('storage/' . $config['path'] . '/' . $filename);

            // Debug info (only in local environment)
            $debug = [];
            if (config('app.env') === 'local') {
                $debug = [
                    'file_saved' => file_exists($fullPath),
                    'file_size' => filesize($fullPath),
                    'directory' => $config['path'],
                ];
            }

            return $this->avatarResponse(true, 'Profile picture updated successfully!', $avatarUrl, $filename, $debug);

        } catch (\Exception $e) {
            Log::error('Avatar upload error: ' . $e->getMessage(), [
                'user_id' => $request->id ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->avatarResponse(false, 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Get avatar URL for user
     */
    public static function getAvatarUrl($user)
    {
        if (!$user) {
            return asset('images/default-avatar.png');
        }

        // First check user's avatar field
        if ($user->avatar) {
            if ($user->isStaff()) {
                return asset('storage/staff_avatars/' . $user->avatar);
            } elseif ($user->isStudent()) {
                return asset('storage/student_avatars/' . $user->avatar);
            } else {
                return asset('storage/avatars/' . $user->avatar);
            }
        }

        // Check for picture in related models
        if ($user->isStaff() && $user->staffPicture && $user->staffPicture->picture) {
            return asset('storage/staff_avatars/' . $user->staffPicture->picture);
        }

        if ($user->isStudent() && $user->picture && $user->picture->picture) {
            return asset('storage/student_avatars/' . $user->picture->picture);
        }

        return asset('images/default-avatar.png');
    }

    /**
     * Get avatar configuration based on user type
     */
    private function getUserAvatarConfig($user)
    {
        if ($user->isStaff()) {
            return [
                'path' => 'staff_avatars',
                'model' => Staffpicture::class,
                'foreignKey' => 'staffId',
                'id' => $user->id,
            ];
        } elseif ($user->isStudent() && $user->student_id) {
            return [
                'path' => 'student_avatars',
                'model' => Studentpicture::class,
                'foreignKey' => 'studentid',
                'id' => $user->student_id,
            ];
        } else {
            return [
                'path' => 'avatars',
                'model' => null,
                'id' => $user->id,
            ];
        }
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists($directory)
    {
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Delete old avatar file
     */
    private function deleteOldAvatar($user, $directory, $config)
    {
        if ($config['model']) {
            $oldRecord = $config['model']::where($config['foreignKey'], $config['id'])->first();
            if ($oldRecord && $oldRecord->picture) {
                $oldPath = $directory . '/' . $oldRecord->picture;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
        } elseif ($user->avatar) {
            $oldPath = $directory . '/' . $user->avatar;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
    }

    /**
     * Update avatar records in database
     */
    private function updateAvatarRecords($user, $filename, $config)
    {
        // Update specific model if exists
        if ($config['model']) {
            $config['model']::updateOrCreate(
                [$config['foreignKey'] => $config['id']],
                ['picture' => $filename]
            );
        }

        // Always update user's avatar field
        $user->avatar = $filename;
        $user->save();
    }

    /**
     * Format avatar response
     */
    private function avatarResponse($success, $message, $avatarUrl = null, $filename = null, $debug = [])
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($avatarUrl) {
            $response['avatar_url'] = $avatarUrl;
        }

        if ($filename) {
            $response['filename'] = $filename;
        }

        // Only include debug info in local environment
        if (config('app.env') === 'local' && !empty($debug)) {
            $response['debug'] = $debug;
        }

        return response()->json($response, $success ? 200 : 500);
    }

    /**
     * Update student info
     */
    public function updateStudentInfo(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'state' => 'required|string|max:100',
            'local' => 'required|string|max:100',
            'home_address' => 'required|string|max:500',
            'emergency_contact' => 'required|string|max:20',
        ]);

        try {
            $user = Auth::user();
            $user->phone_number = $request->phone_number;
            $user->email = $request->email;
            $user->save();

            Student::where('id', $user->student_id)->update([
                'phone_number' => $request->phone_number,
                'state' => $request->state,
                'local' => $request->local,
                'home_address' => $request->home_address,
            ]);

            return back()->with('success', 'Student information updated!');
        } catch (\Exception $e) {
            Log::error('Student info update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update student info.');
        }
    }

    /**
     * Update parent info
     */
    public function updateParentInfo(Request $request)
    {
        $request->validate([
            'father' => 'required|string|max:100',
            'mother' => 'required|string|max:100',
            'father_phone' => 'required|string|max:20',
            'mother_phone' => 'required|string|max:20',
            'father_occupation' => 'required|string|max:100',
            'religion' => 'required|string|max:100',
            'parent_address' => 'required|string|max:500',
        ]);

        try {
            $user = Auth::user();
            ParentRegistration::updateOrCreate(
                ['studentId' => $user->student_id],
                $request->only([
                    'father', 'mother', 'father_phone', 'mother_phone',
                    'father_title', 'mother_title', 'father_occupation',
                    'religion', 'parent_address', 'office_address'
                ])
            );

            return back()->with('success', 'Parent information updated!');
        } catch (\Exception $e) {
            Log::error('Parent info update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update parent info.');
        }
    }

    /**
     * Update employment info (staff)
     */
    public function updateEmploymentInfo(Request $request)
    {
        if (!Auth::user()->isStaff()) {
            return back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'employmentid' => 'required|string|max:100',
            'title' => 'required|string|max:100',
            'phonenumber' => 'required|string|max:20',
            'maritalstatus' => 'required|in:single,married',
            'address' => 'required|string|max:500',
            'state' => 'required|string|max:100',
            'local' => 'required|string|max:100',
            'religion' => 'required|string|max:100',
        ]);

        try {
            Staff::updateOrCreate(
                ['userid' => Auth::id()],
                $request->only([
                    'employmentid', 'title', 'phonenumber', 'maritalstatus',
                    'numberofchildren', 'spousenumber', 'address', 'state', 'local', 'religion'
                ])
            );

            return back()->with('success', 'Employment info updated!');
        } catch (\Exception $e) {
            Log::error('Employment update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update employment info.');
        }
    }

    /**
     * Add qualification (staff)
     */
    public function storeQualification(Request $request)
    {
        if (!Auth::user()->isStaff()) {
            return back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'institution' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'field_of_study' => 'required|string|max:255',
            'year_obtained' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $data = $request->only(['institution', 'qualification', 'field_of_study', 'year_obtained', 'remarks']);
            $data['user_id'] = Auth::id();

            if ($request->hasFile('certificate')) {
                $file = $request->file('certificate');
                $filename = 'cert_' . Auth::id() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('qualifications', $filename, 'public');
                $data['certificate_file'] = $path;
            }

            StaffQualification::create($data);
            return back()->with('success', 'Qualification added successfully!');
        } catch (\Exception $e) {
            Log::error('Add qualification error: ' . $e->getMessage());
            return back()->with('error', 'Failed to add qualification.');
        }
    }

    /**
     * Delete qualification
     */
    public function deleteQualification($id)
    {
        try {
            $qual = StaffQualification::findOrFail($id);
            if ($qual->user_id !== Auth::id()) {
                return back()->with('error', 'Unauthorized.');
            }

            if ($qual->certificate_file) {
                Storage::disk('public')->delete($qual->certificate_file);
            }

            $qual->delete();
            return back()->with('success', 'Qualification deleted!');
        } catch (\Exception $e) {
            Log::error('Delete qualification error: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete qualification.');
        }
    }

    /**
     * AJAX: Update email
     */
    public function ajaxemailupdate(Request $request)
    {
        Log::info('Email update request received', $request->all());

        $validator = Validator::make($request->all(), [
            'userid' => 'required|exists:users,id',
            'emailaddress' => 'required|email|unique:users,email,' . $request->userid,
            'emailaddress_confirmation' => 'required|same:emailaddress',
        ]);

        if ($validator->fails()) {
            Log::error('Email validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->userid);
            $oldEmail = $user->email;
            $user->email = $request->emailaddress;
            $user->save();

            Log::info("Email updated from {$oldEmail} to {$user->email} for user {$user->id}");

            return response()->json([
                'success' => true,
                'message' => 'Email updated successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Email update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: Update password
     */
    public function ajaxpasswordupdate(Request $request)
    {
        Log::info('Password update request received', ['user_id' => $request->userid]);

        $validator = Validator::make($request->all(), [
            'userid' => 'required|exists:users,id',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            Log::error('Password validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->userid);
            $user->password = Hash::make($request->password);
            $user->save();

            Log::info("Password updated for user {$user->id}");

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Password update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password: ' . $e->getMessage()
            ], 500);
        }
    }
}
