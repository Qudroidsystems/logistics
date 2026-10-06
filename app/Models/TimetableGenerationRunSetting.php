<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetableGenerationRunSetting extends Model
{
    use HasFactory;

    protected $table = 'timetable_generation_run_settings';

    protected $fillable = [
        'run_id',
        'source_setting_id',
        'schoolclass_id',
        'class_name',
        'setting_snapshot',
        'periods_snapshot',
        'constraints_snapshot',
        'priorities_snapshot',
        'slots_snapshot',
        'placed',
        'unplaced',
        'room_shortfall',
    ];

    protected $casts = [
        'run_id'               => 'integer',
        'source_setting_id'    => 'integer',
        'schoolclass_id'       => 'integer',
        'setting_snapshot'     => 'array',
        'periods_snapshot'     => 'array',
        'constraints_snapshot' => 'array',
        'priorities_snapshot'  => 'array',
        'slots_snapshot'       => 'array',
        'placed'               => 'integer',
        'unplaced'             => 'integer',
        'room_shortfall'       => 'integer',
    ];

    public function run()
    {
        return $this->belongsTo(TimetableGenerationRun::class, 'run_id', 'id');
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclass_id', 'id');
    }
}