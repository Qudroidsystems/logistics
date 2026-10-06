<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamBankQuestion extends Model
{
    protected $table = 'exam_bank_questions';

    protected $fillable = [
        'subject_id', 'class_level', 'type', 'question', 'options', 'answer',
        'marks', 'difficulty', 'bloom', 'created_by', 'is_active', 'times_used',
    ];

    protected $casts = [
        'options'   => 'array',
        'marks'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topics()
    {
        return $this->belongsToMany(
            SubjectTopic::class, 'exam_bank_question_topic',
            'exam_bank_question_id', 'subject_topic_id'
        );
    }
}
