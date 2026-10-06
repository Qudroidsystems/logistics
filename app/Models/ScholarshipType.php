<?php
// app/Models/ScholarshipType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipType extends Model
{
    use HasFactory;

    protected $table = 'scholarship_types';

    protected $fillable = [
        'name', 'code', 'type', 'application', 'description', 'criteria', 'is_active'
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function scholarships()
    {
        return $this->hasMany(Scholarship::class, 'scholarship_type_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
