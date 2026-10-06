<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCurrentTerm extends Model
{
    use HasFactory;

    protected $table = "student_current_term";

    protected $fillable = [
        'studentId',
        'schoolclassId',
        'termId',
        'sessionId',
        'is_current'
    ];

    /**
     * Relationship with Student
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentId');
    }

    /**
     * Relationship with Class
     */
    public function schoolClass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassId');
    }

    /**
     * Relationship with Term
     */
    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termId');
    }

    /**
     * Relationship with Session
     */
    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionId');
    }

    /**
     * Scope to get current terms
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get by student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('studentId', $studentId);
    }

    /**
     * Scope to get by class and session
     */
    public function scopeByClassAndSession($query, $classId, $sessionId)
    {
        return $query->where('schoolclassId', $classId)
                     ->where('sessionId', $sessionId);
    }

    /**
     * Register a new term for a student
     * Creates a new record if term doesn't exist for this session
     * Otherwise updates the existing term record
     */
    public static function registerTerm($studentId, $classId, $termId, $sessionId, $isCurrent = true)
    {
        // Check if this term already exists for this student in this session
        $existingTerm = self::where('studentId', $studentId)
                           ->where('termId', $termId)
                           ->where('sessionId', $sessionId)
                           ->first();

        if ($existingTerm) {
            // Update the existing term
            $existingTerm->update([
                'schoolclassId' => $classId,
                'is_current' => $isCurrent
            ]);

            // If this is being set as current, update others
            if ($isCurrent) {
                self::where('studentId', $studentId)
                    ->where('id', '!=', $existingTerm->id)
                    ->update(['is_current' => false]);
            }

            return $existingTerm;
        }

        // If setting as current, make all other terms for this student not current
        if ($isCurrent) {
            self::where('studentId', $studentId)
                ->update(['is_current' => false]);
        }

        // Create new term registration
        return self::create([
            'studentId' => $studentId,
            'schoolclassId' => $classId,
            'termId' => $termId,
            'sessionId' => $sessionId,
            'is_current' => $isCurrent
        ]);
    }

    /**
     * Set as current term for student
     * This will automatically update previous current term
     */
    public function setAsCurrent()
    {
        // First, remove current flag from any existing current term for this student
        StudentCurrentTerm::where('studentId', $this->studentId)
                         ->where('is_current', true)
                         ->update(['is_current' => false]);

        // Then set this as current
        $this->is_current = true;
        $this->save();

        return $this;
    }

    /**
     * Get current term for a specific student
     */
    public static function getCurrentForStudent($studentId)
    {
        return self::with(['student', 'schoolClass', 'term', 'session'])
                   ->where('studentId', $studentId)
                   ->where('is_current', true)
                   ->first();
    }

    /**
     * Get the active term for student based on system active term
     */
    public static function getActiveTermForStudent($studentId)
    {
        // Get system active term and session
        $activeTerm = Schoolterm::where('status', true)->first();
        $activeSession = Schoolsession::where('status', 'Current')->first();

        if (!$activeTerm || !$activeSession) {
            return null;
        }

        // Find the student's term record for the active system term
        return self::with(['student', 'schoolClass', 'term', 'session'])
                   ->where('studentId', $studentId)
                   ->where('termId', $activeTerm->id)
                   ->where('sessionId', $activeSession->id)
                   ->first();
    }

    /**
     * Get students by class, term, and session
     */
    public static function getStudentsByClassTermSession($classId, $termId, $sessionId)
    {
        return self::with('student')
                   ->where('schoolclassId', $classId)
                   ->where('termId', $termId)
                   ->where('sessionId', $sessionId)
                   ->get();
    }

    /**
     * Check if student has a current term record
     */
    public static function hasCurrentTerm($studentId)
    {
        return self::where('studentId', $studentId)
                   ->where('is_current', true)
                   ->exists();
    }

    /**
     * Check if term already exists for student in the same session
     * Prevents duplicate term registration
     */
    public static function termExistsForSession($studentId, $termId, $sessionId)
    {
        return self::where('studentId', $studentId)
                   ->where('termId', $termId)
                   ->where('sessionId', $sessionId)
                   ->exists();
    }

    /**
     * Get all terms for a student in a specific session
     */
    public static function getStudentTermsInSession($studentId, $sessionId)
    {
        return self::with(['term', 'schoolClass'])
                   ->where('studentId', $studentId)
                   ->where('sessionId', $sessionId)
                   ->orderBy('termId', 'asc')
                   ->get();
    }
}
