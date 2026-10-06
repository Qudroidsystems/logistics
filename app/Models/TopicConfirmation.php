<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicConfirmation extends Model
{
    protected $table = 'topic_confirmations';

    protected $fillable = ['topic_progress_id', 'student_id', 'action', 'note'];

    public function progress() { return $this->belongsTo(TopicProgress::class, 'topic_progress_id'); }
    public function student()  { return $this->belongsTo(Student::class, 'student_id'); }
}
