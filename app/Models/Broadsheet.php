<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broadsheet extends Model
{
    use HasFactory;
    protected $table = "broadsheet";

    protected $fillable = [
        'studentId',
        'subjectclassid',
        'staffid',
        'ca1',
        'ca2',
        'ca3',
        'tca',
        'exam',
        'total',
        'bf',
        'cum',
        'grade',
        'subjectpositionarm',
        'subjectpositionclass',
        'positionarm',
        'positionclass',
        'remark',
        'termid',
        'session',

    ];



}
