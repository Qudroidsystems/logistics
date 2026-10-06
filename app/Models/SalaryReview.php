<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryReview extends Model
{
    protected $table = 'salary_reviews';

    protected $fillable = ['name', 'type', 'effective_from', 'percent', 'grade_ids', 'components', 'status', 'arrears_period_id', 'summary', 'created_by', 'approved_by', 'approved_at', 'applied_at'];

    public const TYPES = ['step_increment' => 'Step increment (everyone moves up one step)', 'percent_raise' => 'Percentage raise to the salary scale'];

    protected $casts = ['effective_from' => 'date', 'grade_ids' => 'array', 'components' => 'array', 'summary' => 'array', 'approved_at' => 'datetime', 'applied_at' => 'datetime'];
}
