<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRep extends Model
{
    protected $table = 'class_reps';

    protected $fillable = [
        'student_id', 'schoolclass_id', 'session_id', 'term_id', 'assigned_by',
    ];

    public function student() { return $this->belongsTo(Student::class, 'student_id'); }
}
