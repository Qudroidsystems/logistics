<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assessment extends Model
{
    use HasFactory;

    protected $table = 'assessments';

    protected $fillable = [
        'classcategory_id',
        'name',
        'max_score',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
    ];

    /**
     * Relationship to Classcategory
     */
    public function classcategory()
    {
        return $this->belongsTo(Classcategory::class, 'classcategory_id');
    }

    /**
     * Relationship to SubAssessments
     */
    public function subAssessments()
    {
        return $this->hasMany(SubAssessment::class);
    }

    /**
     * Accessor for sub max scores
     */
    public function getSubMaxScoresAttribute()
    {
        return $this->subAssessments->pluck('max_score')->toArray();
    }
}