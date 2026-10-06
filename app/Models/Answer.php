<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exam_id',
        'question_id',
        'option_id',
        'short_answer', // Use short_answer for short answer text
     
    ];

    /**
     * Relationship to the Option model (for MCQ/True/False).
     */
    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id', 'id');
    }

    /**
     * Relationship to the Question model.
     */
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'id');
    }

    /**
     * Relationship to the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relationship to the Exam model.
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'id');
    }

    /**
     * Get the answer text (handles both option-based and short answers).
     */
    public function getAnswerTextAttribute()
    {
        if ($this->option_id && $this->option) {
            return $this->option->option_text;
        }
        return $this->short_answer;
    }

    /**
     * Check if this is a short answer type.
     */
    public function isShortAnswer()
    {
        return !is_null($this->short_answer);
    }

    /**
     * Check if this is an option-based answer.
     */
    public function isOptionAnswer()
    {
        return !is_null($this->option_id);
    }
}
