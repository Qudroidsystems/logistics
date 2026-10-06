<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectRegistrationStatus extends Model
{
    use HasFactory;

    protected $table = "subjectRegistrationStatus";

    protected $fillable = [
        'studentid',
        'subjectclassid',
        'staffid',
        'termid',
        'sessionid',
        'Status',
        'broadsheetid',
    ];

    // Relationships
    public function subjectclass()
    {
        return $this->belongsTo(Subjectclass::class, 'subjectclassid', 'id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentid', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staffid', 'id');
    }

    public function broadsheet()
    {
        return $this->belongsTo(Broadsheets::class, 'broadsheetid', 'id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('Status', 'active');
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('studentid', $studentId);
    }

    public function scopeForTerm($query, $termId)
    {
        return $query->where('termid', $termId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('sessionid', $sessionId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->whereHas('subjectclass', function($q) use ($classId) {
            $q->where('schoolclassid', $classId);
        });
    }
}
