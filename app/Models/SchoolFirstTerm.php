<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolFirstTerm extends Model
{
    use HasFactory;

    protected $table = 'schoolfirstterm';

    protected $fillable = [
        'schoolbroadsheetId',
        'studentId',
        'subjectclassid',
        'staffid',
        'ca1',
        'ca2',
        'ca3',
        'exam',
        'total',
        'grade',
        'allsubjectstotalscores',
        'subjectpositionclass',
        'cmin',
        'cmax',
        'avg',
        'remark',
        'termid',
        'session',
    ];
}
