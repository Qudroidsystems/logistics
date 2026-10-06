<?php
// app/Models/Subjectclass.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subjectclass extends Model
{
    use HasFactory;

    protected $table = "subjectclass";

    protected $fillable = [
        'schoolclassid',
        'subjectid',
        'subjectteacherid',
        'termid',
        'sessionid',
        'session',       // Changed from sessionid to session
        'status',
        'teacher_editing_enabled',
        'teacher_editing_disabled_at',
        'teacher_editing_disabled_by',
    ];

    protected $casts = [
        'teacher_editing_enabled' => 'boolean',
        'teacher_editing_disabled_at' => 'datetime',
    ];

    public function subjectTeacher()
    {
        return $this->belongsTo(SubjectTeacher::class, 'subjectteacherid', 'id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassid', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subjectid', 'id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staffid');
    }

    public function teacherEditingDisabledBy()
    {
        return $this->belongsTo(User::class, 'teacher_editing_disabled_by');
    }

    public function broadsheets()
    {
        return $this->hasMany(Broadsheets::class, 'subjectclass_id', 'id');
    }

    public function scoresheetLocks()
    {
        return $this->hasMany(ScoresheetLock::class, 'subjectclass_id', 'id');
    }

    public function isTeacherEditingEnabled(): bool
    {
        return (bool) $this->teacher_editing_enabled;
    }

    public function disableTeacherEditing($userId = null, $reason = null)
    {
        $this->teacher_editing_enabled = false;
        $this->teacher_editing_disabled_at = now();
        $this->teacher_editing_disabled_by = $userId ?? auth()->id();
        $this->save();

        $this->broadsheets()->update([
            'is_locked' => true,
            'locked_by' => $userId ?? auth()->id(),
            'locked_at' => now(),
            'lock_reason' => $reason ?: 'Teacher editing disabled by admin',
        ]);

        return $this;
    }

    public function enableTeacherEditing()
    {
        $this->teacher_editing_enabled = true;
        $this->teacher_editing_disabled_at = null;
        $this->teacher_editing_disabled_by = null;
        $this->save();

        $this->broadsheets()
            ->where('is_locked', true)
            ->update([
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
                'lock_reason' => null,
            ]);

        return $this;
    }

    public function getActiveGlobalLock($termId, $sessionId)
    {
        return $this->scoresheetLocks()
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->first();
    }

    public function hasActiveGlobalLock($termId, $sessionId): bool
    {
        return $this->scoresheetLocks()
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->exists();
    }

    public function getLockedBroadsheets($termId = null, $sessionId = null)
    {
        $query = $this->broadsheets()->where('is_locked', true);

        if ($termId) {
            $query->where('term_id', $termId);
        }
        if ($sessionId) {
            $query->whereHas('broadsheetRecord', function($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            });
        }

        return $query->get();
    }

    public function getLockStats($termId = null, $sessionId = null)
    {
        $query = $this->broadsheets();

        if ($termId) {
            $query->where('term_id', $termId);
        }
        if ($sessionId) {
            $query->whereHas('broadsheetRecord', function($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            });
        }

        $total = $query->count();
        $locked = $query->where('is_locked', true)->count();

        return [
            'total' => $total,
            'locked' => $locked,
            'unlocked' => $total - $locked,
            'percentage_locked' => $total > 0 ? round(($locked / $total) * 100) : 0,
        ];
    }

    public function getFullClassNameAttribute()
    {
        $arm = $this->schoolClass?->arm?->arm ?? '';
        return trim($this->schoolClass?->schoolclass . ' ' . $arm);
    }

    public function getSubjectDisplayAttribute()
    {
        return $this->subject?->subject . ' (' . $this->subject?->subject_code . ')';
    }
}
