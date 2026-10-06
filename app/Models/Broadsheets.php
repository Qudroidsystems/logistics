<?php
// app/Models/Broadsheets.php

namespace App\Models;

use App\Jobs\AutoUnlockScoresheet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Broadsheets extends Model
{
    use HasFactory;

    protected $table = 'broadsheets';

    protected $fillable = [
        'broadsheet_record_id',
        'term_id',
        'subjectclass_id',
        'staff_id',
        'exam',
        'total',
        'bf',
        'cum',
        'grade',
        'all_subjects_total_score',
        'subject_position_class',
        'subject_position_class_total',
        'arm_position',
        'arm_position_cum',
        'cmin',
        'cmax',
        'avg',
        'remark',
        'submiitedby',
        'vettedby',
        'vettedstatus',
        'entered_by',
        'entered_at',
        'last_modified_by',
        'last_modified_at',
        'entry_source',
        'is_locked',
        'locked_by',
        'locked_at',
        'lock_reason',
        'scheduled_unlock_at',
        'unlock_scheduled_by',
    ];

    protected $casts = [
        'ca1' => 'float',
        'ca2' => 'float',
        'ca3' => 'float',
        'exam' => 'float',
        'total' => 'float',
        'bf' => 'decimal:2',
        'cum' => 'decimal:2',
        'cmin' => 'float',
        'cmax' => 'float',
        'avg' => 'float',
        'subject_position_class_total' => 'integer',
        'arm_position' => 'integer',
        'arm_position_cum' => 'integer',
        'entered_at' => 'datetime',
        'last_modified_at' => 'datetime',
        'locked_at' => 'datetime',
        'scheduled_unlock_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    // Relationships
    public function broadsheetRecord()
    {
        return $this->belongsTo(BroadsheetRecord::class, 'broadsheet_record_id', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function subjectclass()
    {
        return $this->belongsTo(Subjectclass::class, 'subjectclass_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function assessmentScores()
    {
        return $this->hasMany(BroadsheetAssessmentScore::class, 'broadsheet_id');
    }

    public function subAssessmentScores()
    {
        return $this->hasMany(BroadsheetSubAssessmentScore::class, 'broadsheet_id');
    }

    // Audit relationships
    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function lastModifiedBy()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlockScheduledBy()
    {
        return $this->belongsTo(User::class, 'unlock_scheduled_by');
    }

    // Lock Helper Methods
    public function isEditableByTeacher(): bool
    {
        // Check if this specific record is locked
        if ($this->is_locked) {
            return false;
        }

        // Check if the subjectclass has teacher editing disabled
        $subjectClass = $this->subjectclass;
        if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
            return false;
        }

        // Check for global lock on this subjectclass/term/session
        $globalLock = ScoresheetLock::where([
            'subjectclass_id' => $this->subjectclass_id,
            'term_id' => $this->term_id,
            'session_id' => $this->session_id,
            'is_active' => true,
        ])->exists();

        return !$globalLock;
    }

    public function lock(string $reason = null, $userId = null)
    {
        $this->is_locked = true;
        $this->locked_by = $userId ?? auth()->id();
        $this->locked_at = now();
        $this->lock_reason = $reason;
        $this->save();

        return $this;
    }

    public function unlock()
    {
        $this->is_locked = false;
        $this->locked_by = null;
        $this->locked_at = null;
        $this->lock_reason = null;
        $this->save();

        return $this;
    }

    public function scheduleUnlock($datetime, $userId = null)
    {
        $this->scheduled_unlock_at = $datetime;
        $this->unlock_scheduled_by = $userId ?? auth()->id();
        $this->save();

        // Dispatch scheduled job
        dispatch(new AutoUnlockScoresheet($this->id))->delay($datetime);

        return $this;
    }

    public function cancelScheduledUnlock()
    {
        $this->scheduled_unlock_at = null;
        $this->unlock_scheduled_by = null;
        $this->save();

        return $this;
    }

    public function hasScheduledUnlock(): bool
    {
        return !is_null($this->scheduled_unlock_at);
    }
}
