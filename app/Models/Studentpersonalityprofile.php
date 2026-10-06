<?php

namespace App\Models;

use App\Models\Student;
use App\Models\StudentRegistration;
use Illuminate\Database\Eloquent\Model;

class Studentpersonalityprofile extends Model
{
    protected $table = 'studentpersonalityprofiles';

    protected $fillable = [
        'studentid',
        'staffid',
        'schoolclassid',
        'punctuality',
        'neatness',
        'leadership',
        'attitude',
        'reading',
        'honesty',
        'cooperation',
        'selfcontrol',
        'politeness',
        'physicalhealth',
        'stability',
        'gamesandsports',
        'principalscomment',
        'classteachercomment',
        'no_of_times_school_absent',
        'remark_on_other_activities',
        'guidancescomment',
        'termid',
        'sessionid',
        'attendance',
        'attentiveness_in_class',
        'class_participation',
        'relationship_with_others',
        'doing_assignment',
        'writing_skill',
        'reading_skill',
        'spoken_english_communication',
        'hand_writing',
        'club',
        'music',
        'signature'
    ];

    // In app/Models/Studentpersonalityprofile.php
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentid', 'id');
    }

    // Add this boot method to log model events
    protected static function boot()
    {
        parent::boot();

        // Log when model is being updated
        static::updating(function($model) {
            \Log::warning('Studentpersonalityprofile model UPDATING event', [
                'id' => $model->id,
                'studentid' => $model->studentid,
                'schoolclassid' => $model->schoolclassid,
                'sessionid' => $model->sessionid,
                'termid' => $model->termid,
                'principalscomment' => $model->principalscomment,
                'original_comment' => $model->getOriginal('principalscomment'),
                'isDirty' => $model->isDirty(),
                'dirty_attributes' => $model->getDirty(),
                'call_stack' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
            ]);
        });

        // Log when model was updated
        static::updated(function($model) {
            \Log::warning('Studentpersonalityprofile model UPDATED event', [
                'id' => $model->id,
                'studentid' => $model->studentid,
                'schoolclassid' => $model->schoolclassid,
                'sessionid' => $model->sessionid,
                'termid' => $model->termid,
                'changes' => $model->getChanges(),
                'updated_at' => $model->updated_at
            ]);
        });

        // Log when model is being created
        static::creating(function($model) {
            \Log::warning('Studentpersonalityprofile model CREATING event', [
                'studentid' => $model->studentid,
                'schoolclassid' => $model->schoolclassid,
                'sessionid' => $model->sessionid,
                'termid' => $model->termid,
                'call_stack' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
            ]);
        });

        // Log when model was created
        static::created(function($model) {
            \Log::warning('Studentpersonalityprofile model CREATED event', [
                'id' => $model->id,
                'studentid' => $model->studentid,
                'schoolclassid' => $model->schoolclassid,
                'sessionid' => $model->sessionid,
                'termid' => $model->termid,
                'created_at' => $model->created_at
            ]);
        });
    }
}
