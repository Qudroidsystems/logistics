<?php
// app/Models/CompulsorySubjectClass.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompulsorySubjectClass extends Model
{
    use HasFactory;

    protected $table = 'compulsory_subject_classes';

    protected $fillable = [
        'schoolclassid',
        'subjectId',
        'termid',
        'sessionid',
        'min_grade',
    ];

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassid', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subjectId', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
    }
}
