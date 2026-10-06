<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingMethod extends Model
{
    protected $table = 'teaching_methods';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
