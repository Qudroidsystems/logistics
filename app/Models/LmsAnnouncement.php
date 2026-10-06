<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsAnnouncement extends Model
{
    protected $table = 'lms_announcements';

    protected $fillable = ['course_id', 'title', 'body', 'notify', 'created_by'];

    protected $casts = ['notify' => 'boolean'];

    public function course() { return $this->belongsTo(LmsCourse::class, 'course_id'); }
    public function author() { return $this->belongsTo(User::class, 'created_by'); }
}
