<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectTopic extends Model
{
    protected $table = 'subject_topics';

    protected $fillable = [
        'subject_id', 'class_level', 'term_id', 'week_no', 'title',
        'description', 'position', 'is_active', 'created_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function subject()  { return $this->belongsTo(Subject::class, 'subject_id'); }
    public function term()     { return $this->belongsTo(Schoolterm::class, 'term_id'); }
    public function progress() { return $this->hasMany(TopicProgress::class, 'subject_topic_id'); }
}
