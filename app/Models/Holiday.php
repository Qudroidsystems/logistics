<?php
// app/Models/Holiday.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $table = 'holidays';

    protected $fillable = ['date', 'title', 'is_full_day', 'cutoff_time', 'session_id', 'term_id', 'created_by'];

    protected $casts = [
        'date'        => 'date',
        'is_full_day' => 'boolean',
    ];

    public function session(): BelongsTo { return $this->belongsTo(Schoolsession::class, 'session_id'); }
    public function term(): BelongsTo    { return $this->belongsTo(Schoolterm::class, 'term_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}