<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'question_text',
        'type',
        'image',
        'marks',        // Points for this question
        'order',        // For drag-drop ordering
        'is_reusable'   // Can be used in other exams
    ];

    protected $casts = [
        'is_reusable' => 'boolean',
        'order' => 'integer',
        'marks' => 'float'
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    // Question automatically gets class from exam
    public function getClassAttribute()
    {
        return $this->exam->schoolclass ?? null;
    }

    // For drag-drop ordering
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    // Reusable questions scope
    public function scopeReusable($query)
    {
        return $query->where('is_reusable', true);
    }

    // Get next order number for a specific exam
    public static function getNextOrder($examId)
    {
        return self::where('exam_id', $examId)->max('order') + 1;
    }
}
