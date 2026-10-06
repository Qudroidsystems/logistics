<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Studenthouse extends Model
{
    use HasFactory;
    protected $table ="studenthouses";
    protected $primaryKey = "studentid";
    protected $fillable = [

        'studentid',
        'schoolhouseid',
        'termid',
        'sessionid',
    ];
}
