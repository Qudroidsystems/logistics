<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInstalmentPlan extends Model
{
    protected $fillable = [
        'name', 'description', 'session_id', 'term_id', 'applies_to', 'class_ids',
        'schedule', 'results_when_on_track', 'is_active', 'created_by',
    ];

    protected $casts = [
        'class_ids'             => 'array',
        'schedule'              => 'array',
        'results_when_on_track' => 'boolean',
        'is_active'             => 'boolean',
    ];

    public const APPLIES = [
        'selected' => 'Only students I add',
        'classes'  => 'Every student in chosen classes',
        'all'      => 'Every student this term',
    ];

    public function assignments()
    {
        return $this->hasMany(FeeInstalmentAssignment::class, 'plan_id');
    }

    /** Schedule sorted by due date. */
    public function steps(): array
    {
        $s = array_values($this->schedule ?? []);
        usort($s, fn ($a, $b) => strcmp((string) ($a['due_date'] ?? ''), (string) ($b['due_date'] ?? '')));
        return $s;
    }
}
