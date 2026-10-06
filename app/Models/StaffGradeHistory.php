<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffGradeHistory extends Model
{
    protected $table = 'staff_grade_history';

    protected $fillable = ['staff_id', 'grade_id', 'step', 'effective_from', 'reason', 'review_id', 'created_by'];

    protected $casts = ['effective_from' => 'date'];
}
