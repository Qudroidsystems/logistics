<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetablePeriodAllocation extends Model
{
    use HasFactory;

    protected $table = 'timetable_period_allocations';

    protected $fillable = [
        'set_id',
        'schoolclass_id',
        'subject_id',
        'periods_per_week',
        'allow_double_period',
        'max_double_periods_per_week',
    ];

    protected $casts = [
        'set_id'                      => 'integer',
        'schoolclass_id'              => 'integer',
        'subject_id'                  => 'integer',
        'periods_per_week'            => 'integer',
        'allow_double_period'         => 'boolean',
        'max_double_periods_per_week' => 'integer',
    ];

    public function set()
    {
        return $this->belongsTo(TimetablePeriodAllocationSet::class, 'set_id', 'id');
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclass_id', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }
}
