<?php
// app/Models/TimetableReport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableReport extends Model
{
    protected $table = 'timetable_reports';

    protected $fillable = [
        'report_name',
        'report_type',
        'session_id',
        'term_id',
        'filters',
        'data',
        'file_path',
        'generated_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'data' => 'array',
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }
}
