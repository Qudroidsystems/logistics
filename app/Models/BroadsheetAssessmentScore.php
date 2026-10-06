<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BroadsheetAssessmentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'broadsheet_id',
        'assessment_id',
        'score',
    ];

    public function broadsheet()
    {
        return $this->belongsTo(Broadsheet::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}