<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Academicinfo1 extends Model
{
    use HasFactory;
    protected $table = "staffacademicinfo1";
    protected $primaryKey = "staffid";

    protected $fillable = [
        'staffid',
       'employmentno',
       'institution',
       'discipline',
       'qualification',
       'year',
       'yearteaching',
    ];
}
