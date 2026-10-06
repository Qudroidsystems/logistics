<?php
// app/Models/PromotionSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class PromotionSetting extends Model
{
    use HasFactory;

    protected $table = 'promotion_settings';

    protected $fillable = [
        'schoolclass_id',
        'session_id',
        'term_id',
        'rule_set_name',
        'priority',
        'promoted_label',
        'trial_label',
        'see_principal_label',
        'repeat_label',
        'rule_logic',
        'promotion_pass_average',
        'promotion_rules',
        'is_active',
        'is_default',
        'template_id'
    ];

    protected $casts = [
        'promotion_rules' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'promotion_pass_average' => 'float',  // FIXED: Changed from 'decimal:2' to 'float' to properly handle 0
        'priority' => 'integer',
    ];

    // Relationships
    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclass_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function template()
    {
        return $this->belongsTo(PromotionRuleTemplate::class, 'template_id');
    }

    // Scope for active rule sets
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for default rule set
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Get rule sets for a specific class
    public static function getRuleSetsForClass($classId, $sessionId = null, $termId = null)
    {
        $query = self::where('schoolclass_id', $classId)
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc');

        if ($sessionId) {
            $query->where(function($q) use ($sessionId) {
                $q->where('session_id', $sessionId)->orWhereNull('session_id');
            });
        }

        if ($termId) {
            $query->where(function($q) use ($termId) {
                $q->where('term_id', $termId)->orWhereNull('term_id');
            });
        }

        return $query->get();
    }
}
