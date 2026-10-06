<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolFirstTermMock extends Model
{
    use HasFactory;

    protected $table = 'schoolfirsttermmock';

    protected $fillable = [
        'schoolbroadsheetId', 'studentId', 'subjectclassid', 'staffid',
        'exam', 'total', 'grade', 'allsubjectstotalscores',
        'subjectpositionclass', 'cmin', 'cmax', 'avg', 'remark', 'termid', 'session',
    ];
}
