<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInstalmentAssignment extends Model
{
    protected $fillable = ['plan_id', 'student_id', 'assigned_by', 'note'];

    public function plan()
    {
        return $this->belongsTo(FeeInstalmentPlan::class, 'plan_id');
    }
}
