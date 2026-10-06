<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schoolsession extends Model
{
    use HasFactory;
    protected $table ="schoolsession";

    protected $fillable = ['session', 'status',];
}

