<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Academicinfo2 extends Model
{
    use HasFactory;
    protected $table = "staffacademicinfo2";
    protected $primaryKey = "staffid";

    protected $fillable = [
        'staffid',
       'employmentno',
       'institution2',
       'discipline2',
       'qualification2',
       'year2',
       'yearteaching2',
    ];
}
