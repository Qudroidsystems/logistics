<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsDiscussion extends Model
{
    protected $table = 'lms_discussions';

    protected $fillable = [
        'course_id', 'lesson_id', 'user_id', 'parent_id', 'body', 'is_pinned', 'is_resolved',
    ];

    protected $casts = [
        'is_pinned'   => 'boolean',
        'is_resolved' => 'boolean',
    ];

    public function course()  { return $this->belongsTo(LmsCourse::class, 'course_id'); }
    public function lesson()  { return $this->belongsTo(LmsLesson::class, 'lesson_id'); }
    public function author()  { return $this->belongsTo(User::class, 'user_id'); }
    public function replies() { return $this->hasMany(LmsDiscussion::class, 'parent_id')->oldest(); }
}
