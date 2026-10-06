<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BroadsheetSubAssessmentScore extends Model
{
    use HasFactory;

    protected $table = 'broadsheet_sub_assessment_scores';

    protected $fillable = [
        'broadsheet_id',
        'sub_assessment_id',
        'assessment_id',
        'score',
    ];

    public function broadsheet()
    {
        return $this->belongsTo(Broadsheets::class);
    }

    public function subAssessment()
    {
        return $this->belongsTo(SubAssessment::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}