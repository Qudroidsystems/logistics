<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsQuestionBank extends Model
{
    protected $table = 'lms_question_bank';

    protected $fillable = [
        'subject_id', 'tag', 'question', 'type', 'options', 'correct',
        'accepted_answers', 'explanation', 'image_path', 'points', 'created_by',
    ];

    protected $casts = [
        'options'          => 'array',
        'correct'          => 'array',
        'accepted_answers' => 'array',
    ];

    public function subject() { return $this->belongsTo(Subject::class, 'subject_id'); }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . ltrim($this->image_path, '/')) : null;
    }

    /** Fields to copy into an lms_quiz_questions row when imported into a quiz. */
    public function toQuizFields(): array
    {
        return [
            'question'         => $this->question,
            'type'             => $this->type,
            'options'          => $this->options,
            'correct'          => $this->correct,
            'accepted_answers' => $this->accepted_answers,
            'explanation'      => $this->explanation,
            'image_path'       => $this->image_path,
            'points'           => $this->points,
        ];
    }
}
