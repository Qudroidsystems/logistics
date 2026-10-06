<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schoolarm extends Model
{
    use HasFactory;
    protected $table = "schoolarm";

    protected $fillable = [
        'arm',
        'description',
    ];
}
