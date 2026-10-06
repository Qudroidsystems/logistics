<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobProgress extends Model
{
    protected $fillable = [
        'job_id',
        'total_operations',
        'completed_operations',
        'status',
        'errors',
    ];
}