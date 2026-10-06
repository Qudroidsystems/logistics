<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;
    
    protected $table = 'grades';
    
    protected $fillable = [
        'classcategory_id',
        'total_score',
        'grade',
        'is_senior',
    ];

    /**
     * Relationship with Classcategory
     */
    public function classcategory()
    {
        return $this->belongsTo(Classcategory::class, 'classcategory_id');
    }

    /**
     * Calculate grade based on total score and class type
     */
    public function calculateGrade($totalScore, $isSenior = false)
    {
        if ($isSenior) {
            return $this->calculateSeniorGrade($totalScore);
        }
        return $this->calculateJuniorGrade($totalScore);
    }

    /**
     * Calculate grade for junior classes
     */
    private function calculateJuniorGrade($totalScore)
    {
        if ($totalScore >= 70 && $totalScore <= 100) {
            return 'A';
        } elseif ($totalScore >= 60) {
            return 'B';
        } elseif ($totalScore >= 50) {
            return 'C';
        } elseif ($totalScore >= 40) {
            return 'D';
        } else {
            return 'F';
        }
    }

    /**
     * Calculate grade for senior classes
     */
    private function calculateSeniorGrade($totalScore)
    {
        if ($totalScore >= 75 && $totalScore <= 100) {
            return 'A1';
        } elseif ($totalScore >= 70) {
            return 'B2';
        } elseif ($totalScore >= 65) {
            return 'B3';
        } elseif ($totalScore >= 60) {
            return 'C4';
        } elseif ($totalScore >= 55) {
            return 'C5';
        } elseif ($totalScore >= 50) {
            return 'C6';
        } elseif ($totalScore >= 45) {
            return 'D7';
        } elseif ($totalScore >= 40) {
            return 'E8';
        } else {
            return 'F9';
        }
    }

    /**
     * Scope to filter by class type
     */
    public function scopeSenior($query)
    {
        return $query->where('is_senior', true);
    }

    public function scopeJunior($query)
    {
        return $query->where('is_senior', false);
    }
}