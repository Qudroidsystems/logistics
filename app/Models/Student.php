<?php

namespace App\Models;

use App\Models\ParentRegistration;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Studentclass;
use App\Models\Studentpicture;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $table = 'studentRegistration';

    protected $fillable = [
        'userid', 'title', 'firstname', 'lastname', 'othername',
        'nationality', 'gender', 'phone_number', 'future_ambition',
        'home_address2', 'placeofbirth', 'dateofbirth', 'age',
        'religion', 'state', 'local', 'last_school', 'last_class',
        'registeredBy', 'statusId', 'batchid', 'student_category',
        'student_status', 'nin_number', 'blood_group', 'mother_tongue',
        'reason_for_leaving', 'admissionNo', 'admission_date',
        'admissionYear', 'permanent_address', 'sport_house', 'email', 'city',

        // Added — these columns exist in studentRegistration but were missing
        // from $fillable, so mass-assignment was silently dropping them.
        'genotype',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies_medical_conditions',
    ];

    protected $casts = [
        // dateofbirth is a varchar(255) column and may hold 'N/A' for
        // partially-filled imports. Casting it to 'date' makes
        // Carbon::parse('N/A') throw on access, so the cast is removed.
        // If you migrate the column to a real DATE NULL, re-enable this cast.
        // 'dateofbirth'    => 'date',
        'admission_date' => 'date',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // ── Existing relationships ────────────────────────────────────────────

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'student_id', 'id');
    }

    public function picture()
    {
        return $this->hasOne(Studentpicture::class, 'studentid', 'id');
    }

    public function schoolClass()
    {
        return $this->hasOne(Studentclass::class, 'studentId', 'id');
    }

    public function class()
    {
        return $this->hasOneThrough(
            Schoolclass::class, Studentclass::class,
            'studentId', 'id', 'id', 'schoolclassid'
        );
    }

    public function term()
    {
        return $this->hasOneThrough(
            Schoolterm::class, Studentclass::class,
            'studentId', 'id', 'id', 'termid'
        );
    }

    public function session()
    {
        return $this->hasOneThrough(
            Schoolsession::class, Studentclass::class,
            'studentId', 'id', 'id', 'sessionid'
        );
    }

    public function parent()
    {
        return $this->hasOne(ParentRegistration::class, 'studentId', 'id');
    }

    public function classHistory()
    {
        return $this->hasMany(Studentclass::class, 'studentId', 'id')
            ->with(['schoolclass.armRelation', 'term', 'session', 'promotion'])
            ->orderByDesc('sessionid')
            ->orderByDesc('termid');
    }

    public function promotion()
    {
        return $this->hasOne(PromotionStatus::class, 'studentId', 'id')
            ->whereColumn('schoolclassid', 'studentclass.schoolclassid')
            ->whereColumn('sessionid', 'studentclass.sessionid')
            ->whereColumn('termid', 'studentclass.termid');
    }

    // ── StudentCurrentTerm relationships ──────────────────────────────────

    public function currentTerm()
    {
        return $this->hasOne(StudentCurrentTerm::class, 'studentId', 'id')
            ->where('is_current', true);
    }

    public function currentTerms()
    {
        return $this->hasMany(StudentCurrentTerm::class, 'studentId', 'id');
    }

    public function currentClass()
    {
        return $this->hasOneThrough(
            Schoolclass::class, StudentCurrentTerm::class,
            'studentId', 'id', 'id', 'schoolclassId'
        )->where('student_current_term.is_current', true);
    }

    public function currentTermRelation()
    {
        return $this->hasOneThrough(
            Schoolterm::class, StudentCurrentTerm::class,
            'studentId', 'id', 'id', 'termId'
        )->where('student_current_term.is_current', true);
    }

    public function currentSession()
    {
        return $this->hasOneThrough(
            Schoolsession::class, StudentCurrentTerm::class,
            'studentId', 'id', 'id', 'sessionId'
        )->where('student_current_term.is_current', true);
    }

    public function getCurrentTermInfo()
    {
        $currentTerm = $this->currentTerm;
        if (!$currentTerm) return null;

        if (!$currentTerm->relationLoaded('schoolClass')) {
            $currentTerm->load(['schoolClass.armRelation', 'term', 'session']);
        }

        return [
            'student_id'         => $this->id,
            'current_class_id'   => $currentTerm->schoolclassId,
            'current_class'      => $currentTerm->schoolClass?->schoolclass,
            'current_class_arm'  => $currentTerm->schoolClass?->armRelation?->arm,
            'current_term_id'    => $currentTerm->termId,
            'current_term'       => $currentTerm->term?->name,
            'current_session_id' => $currentTerm->sessionId,
            'current_session'    => $currentTerm->session?->name,
            'is_current'         => $currentTerm->is_current,
        ];
    }

    public function hasCurrentTerm()
    {
        return $this->currentTerm()->exists();
    }

    public function scopeWithCurrentTerm($query)
    {
        return $query->whereHas('currentTerm');
    }

    public function scopeByCurrentClass($query, $classId)
    {
        return $query->whereHas('currentTerm', fn($q) => $q->where('schoolclassId', $classId));
    }

    public function scopeByCurrentTerm($query, $termId)
    {
        return $query->whereHas('currentTerm', fn($q) => $q->where('termId', $termId));
    }

    public function scopeByCurrentSession($query, $sessionId)
    {
        return $query->whereHas('currentTerm', fn($q) => $q->where('sessionId', $sessionId));
    }

    // ── Scholarship relationship ──────────────────────────────────────────

    public function scholarshipAssignments()
    {
        return $this->hasMany(ScholarshipAssignment::class, 'student_id', 'id');
    }
}