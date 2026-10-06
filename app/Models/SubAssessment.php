<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubAssessment extends Model
{
    use HasFactory;

    protected $table = 'sub_assessments';

    protected $fillable = [
        'assessment_id',
        'name',
        'max_score',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
    ];

    /**
     * Relationship to Assessment
     */
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}