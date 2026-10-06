<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryGradeStep extends Model
{
    protected $table = 'salary_grade_steps';

    protected $fillable = ['grade_id', 'step', 'effective_from', 'basic', 'housing', 'transport', 'meal', 'medical', 'utility', 'other', 'review_id'];

    protected $casts = ['effective_from' => 'date'];
}
