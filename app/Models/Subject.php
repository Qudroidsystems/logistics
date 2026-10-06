<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;
    protected $table = "subject";

    protected $fillable = [
        'subject',
        'subject_code',
        'remark',
    ];

    public function broadsheetRecords()
    {
        return $this->hasMany(BroadsheetRecord::class, 'subject_id', 'id');
    }
    /**
     * Relationship to SubjectTeacher
     */
    public function subjectTeachers()
    {
        return $this->hasMany(SubjectTeacher::class, 'subjectid', 'id');
    }

    /**
     * Relationship to SubjectTeacher with staff details
     */
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'subjectteacher', 'subjectid', 'staffid')
            ->distinct();
    }

    /**
     * Get the classes where this subject is taught
     */
    public function classes()
    {
        return $this->belongsToMany(Schoolclass::class, 'subjectclass', 'subjectid', 'schoolclassid')
            ->via('subjectTeachers');
    }
}
