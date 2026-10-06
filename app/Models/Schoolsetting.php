<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schoolsetting extends Model
{
    use HasFactory;
    protected $table = "studentpersonalityprofile";
    //protected $primaryKey = "studentid";

    protected $fillable = [
        'schoolstarts',
        'schoolends',
        'schoolopened',
        'termid',
        'sessionid',
    ];
}
