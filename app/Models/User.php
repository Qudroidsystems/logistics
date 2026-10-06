<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\PermissionRegistrar;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'student_id',
        'phone_number',
        'gender',
        'date_of_birth',
        'profile_image',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'must_change_password' => 'boolean',
        'is_disabled' => 'boolean',
        'last_login_at' => 'datetime',
        'credentials_sent_at' => 'datetime',
    ];

    /** This user's staff record (staffbioinfo). */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class, 'userid', 'id');
    }

    /** Parent portal: children linked to this parent account. */
    public function parentChildren(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'user_id', 'student_id')
            ->withPivot('relationship', 'source')->withTimestamps();
    }

    // Staff picture relationship
    public function staffPicture(): HasOne
    {
        return $this->hasOne(Staffpicture::class, 'staffId', 'id');
    }

    // Staff employment details
    public function staffemploymentDetails(): HasOne
    {
        return $this->hasOne(Staff::class, 'userid', 'id');
    }

    // Bio information
    public function bio(): HasOne
    {
        return $this->hasOne(BioModel::class, 'user_id');
    }

    // Qualifications (for staff)
    public function qualifications(): HasMany
    {
        return $this->hasMany(StaffQualification::class, 'user_id');
    }

    // Student record (if user is a student)
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'id', 'student_id');
    }

    // ADD THIS RELATIONSHIP - Subject teachings for teachers
    public function subjectTeachings(): HasMany
    {
        return $this->hasMany(SubjectTeacher::class, 'staffid', 'id');
    }

    // Alternative relationship if you need to get subjects directly
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subjectteacher', 'staffid', 'subjectid')
                    ->withPivot('termid', 'sessionid')
                    ->withTimestamps();
    }

    // Check if user has Staff role
    public function isStaff(): bool
    {
        return $this->hasRole('Staff');
    }

    // Check if user has Student role
    public function isStudent(): bool
    {
        return $this->hasRole('Student') || !is_null($this->student_id);
    }

    // Get user's first name
    public function getFirstNameAttribute(): string
    {
        $parts = explode(' ', trim($this->name));
        return $parts[0] ?? '';
    }

    // Get user's last name
    public function getLastNameAttribute(): string
    {
        $parts = explode(' ', trim($this->name));
        return isset($parts[1]) ? $parts[1] : '';
    }

    // Get avatar URL (safe fallback)
    public function getAvatarUrlAttribute(): string
    {
        // Staff picture
        if ($this->isStaff() && $this->staffPicture?->picture) {
            return asset('storage/staff_avatars/' . $this->staffPicture->picture);
        }

        // Student picture
        if ($this->isStudent() && $this->student_id) {
            $studentPicture = \App\Models\Studentpicture::where('studentid', $this->student_id)->first();
            if ($studentPicture?->picture) {
                return asset('storage/student_avatars/' . $studentPicture->picture);
            }
        }

        // General avatar column
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }

        // Legacy profile_image
        if ($this->profile_image) {
            return asset('storage/' . $this->profile_image);
        }

        // Default fallback
        $initials = strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&color=7F9CF5&background=EBF4FF";
    }

    // Get admission number (for students)
    public function getAdmissionNoAttribute(): ?string
    {
        return $this->isStudent() && $this->student?->admissionNo ? $this->student->admissionNo : null;
    }

    // Check if user is active
    public function isActive(): bool
    {
        return true; // Customize if needed
    }

    // Get formatted date of birth
    public function getFormattedDobAttribute(): ?string
    {
        return $this->date_of_birth?->format('d M Y');
    }

    // Scope for staff users
    public function scopeStaff($query)
    {
        return $query->whereHas('roles', fn($q) => $q->where('name', 'Staff'));
    }

    // Scope for student users
    public function scopeStudents($query)
    {
        return $query->whereHas('roles', fn($q) => $q->where('name', 'Student'));
    }
}
