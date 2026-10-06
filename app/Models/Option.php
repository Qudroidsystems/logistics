<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id', 
        'option_text',
        'is_correct',
        'label', // Add this
    ];

    protected $casts = [
        'is_correct' => 'boolean', // Standardize to int 0/1 everywhere
    ];

    // Relationship back to Question if needed
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
