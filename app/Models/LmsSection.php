<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsSection extends Model
{
    protected $table = 'lms_sections';

    protected $fillable = ['course_id', 'title', 'description', 'position', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public function course()  { return $this->belongsTo(LmsCourse::class, 'course_id'); }
    public function lessons() { return $this->hasMany(LmsLesson::class, 'section_id')->orderBy('position'); }
}
