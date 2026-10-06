<?php
// app/Models/PromotionRuleTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromotionRuleTemplate extends Model
{
    use HasFactory;

    protected $table = 'promotion_rule_templates';

    protected $fillable = [
        'name', 'description', 'grade_scale', 'rules', 'created_by',
    ];

    protected $casts = [
        'rules' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function settings()
    {
        return $this->hasMany(PromotionSetting::class, 'template_id');
    }

    public function getIsSeniorAttribute(): bool
    {
        return $this->grade_scale === 'senior';
    }

    public function getRuleCountAttribute(): int
    {
        return count($this->rules ?? []);
    }
}
