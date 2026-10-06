<?php
// app/Models/Classcategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classcategory extends Model
{
    use HasFactory;

    // Explicitly define the table name (plural)
    protected $table = 'classcategories';

    protected $fillable = [
        'category',
        'is_senior',
        'promotion_pass_average',
    ];

    protected $casts = [
        'is_senior'              => 'boolean',
        'promotion_pass_average' => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'classcategory_id');
    }

    public function schoolClasses()
    {
        return $this->belongsToMany(
            Schoolclass::class,
            'schoolclass_classcategory',
            'classcategory_id',
            'schoolclass_id'
        );
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'classcategory_id');
    }

    // ── Grade calculation ────────────────────────────────────────────────────

    public function calculateGrade($totalScore)
    {
        return $this->is_senior
            ? $this->calculateSeniorGrade($totalScore)
            : $this->calculateJuniorGrade($totalScore);
    }

    private function calculateJuniorGrade($totalScore)
    {
        if ($totalScore >= 70) return 'A';
        if ($totalScore >= 60) return 'B';
        if ($totalScore >= 50) return 'C';
        if ($totalScore >= 40) return 'D';
        return 'F';
    }

    private function calculateSeniorGrade($totalScore)
    {
        if ($totalScore >= 75) return 'A1';
        if ($totalScore >= 70) return 'B2';
        if ($totalScore >= 65) return 'B3';
        if ($totalScore >= 60) return 'C4';
        if ($totalScore >= 55) return 'C5';
        if ($totalScore >= 50) return 'C6';
        if ($totalScore >= 45) return 'D7';
        if ($totalScore >= 40) return 'E8';
        return 'F9';
    }

    public function getGradeScaleAttribute(): array
    {
        return $this->is_senior
            ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9']
            : ['A', 'B', 'C', 'D', 'F'];
    }

    public function getPassingGradesAttribute(): array
    {
        return $this->is_senior
            ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8']
            : ['A', 'B', 'C', 'D'];
    }

    public function getGradeTypeAttribute(): string
    {
        return $this->is_senior ? 'Senior' : 'Junior';
    }

    public function getTotalMaxScoreAttribute()
    {
        return $this->assessments->sum('max_score');
    }

    public function hasPassAverageThreshold(): bool
    {
        return $this->promotion_pass_average !== null;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeSenior($query)
    {
        return $query->where('is_senior', true);
    }

    public function scopeJunior($query)
    {
        return $query->where('is_senior', false);
    }

    public function scopeWithPassAverage($query)
    {
        return $query->whereNotNull('promotion_pass_average');
    }
}
