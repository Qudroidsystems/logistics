<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamQuestionScore extends Model
{
    protected $table = 'exam_question_scores';

    protected $fillable = ['exam_question_id', 'student_id', 'score'];

    protected $casts = ['score' => 'decimal:2'];

    public function question()
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }
}
