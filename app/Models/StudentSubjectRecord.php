<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentSubjectRecord extends Model
{
    use HasFactory;

    protected $table = 'student_subject_register_record';

    protected $fillable = [
        'studentId',
        'subjectclassid',
        'staffid',
        'session',

    ];
}
