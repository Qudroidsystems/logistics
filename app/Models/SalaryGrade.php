<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryGrade extends Model
{
    protected $table = 'salary_grades';

    protected $fillable = ['code', 'name', 'description', 'max_step', 'sort', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
