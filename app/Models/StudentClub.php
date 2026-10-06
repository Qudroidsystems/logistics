<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Mirrors App\Models\Studenthouse exactly: primaryKey is studentid, so a
 * student has at most one club record at a time (overwritten across terms
 * via updateOrCreate keyed on studentid), matching how House selection
 * already behaves in this app.
 */
class StudentClub extends Model
{
    use HasFactory;

    protected $table = "studentclubs";
    protected $primaryKey = "studentid";

    /** Mirror the student-form pick into activity_memberships (students can have many). */
    protected static function booted(): void
    {
        static::saved(function ($m) {
            try {
                \App\Models\ActivityMembership::syncFromForm('club', $m->studentid, $m->clubid, $m->getOriginal('clubid'), $m->sessionid);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Membership sync failed', ['error' => $e->getMessage()]);
            }
        });
    }

    protected $fillable = [
        'studentid',
        'clubid',
        'termid',
        'sessionid',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentid');
    }

    public function club()
    {
        return $this->belongsTo(Club::class, 'clubid');
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