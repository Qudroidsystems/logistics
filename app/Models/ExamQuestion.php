<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    protected $table = 'exam_questions';

    protected $fillable = [
        'exam_paper_id', 'section', 'number', 'type', 'question', 'options',
        'answer', 'marks', 'difficulty', 'bloom', 'position',
    ];

    protected $casts = [
        'options' => 'array',
        'marks'   => 'decimal:2',
    ];

    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function topics()
    {
        return $this->belongsToMany(
            SubjectTopic::class, 'exam_question_topic',
            'exam_question_id', 'subject_topic_id'
        );
    }

    public function scores()
    {
        return $this->hasMany(ExamQuestionScore::class, 'exam_question_id');
    }
}
