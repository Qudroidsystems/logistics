<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Academicinfo3 extends Model
{
    use HasFactory;
    protected $table = "staffacademicinfo3";
    protected $primaryKey = "staffid";

    protected $fillable = [
        'staffid',
       'employmentno',
       'institution3',
       'discipline3',
       'qualification3',
       'year3',
       'yearteaching3',
    ];
}
