<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamVetComment extends Model
{
    protected $table = 'exam_vet_comments';

    protected $fillable = [
        'exam_paper_id', 'exam_question_id', 'user_id', 'comment', 'action', 'resolved',
    ];

    protected $casts = ['resolved' => 'boolean'];

    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
