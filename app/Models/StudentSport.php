<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Mirrors App\Models\Studenthouse exactly: primaryKey is studentid, so a
 * student has at most one sport record at a time (overwritten across terms
 * via updateOrCreate keyed on studentid), matching how House selection
 * already behaves in this app.
 */
class StudentSport extends Model
{
    use HasFactory;

    protected $table = "studentsports";
    protected $primaryKey = "studentid";

    /** Mirror the student-form pick into activity_memberships (students can have many). */
    protected static function booted(): void
    {
        static::saved(function ($m) {
            try {
                \App\Models\ActivityMembership::syncFromForm('sport', $m->studentid, $m->sportid, $m->getOriginal('sportid'), $m->sessionid);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Membership sync failed', ['error' => $e->getMessage()]);
            }
        });
    }

    protected $fillable = [
        'studentid',
        'sportid',
        'termid',
        'sessionid',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentid');
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class, 'sportid');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }
}