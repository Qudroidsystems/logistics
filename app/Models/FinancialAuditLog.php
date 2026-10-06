<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'auditable_type', 'auditable_id', 'model_label', 'event', 'user_id', 'user_name',
        'ref', 'amount', 'changes', 'summary', 'business_date', 'ip', 'created_at',
    ];

    protected $casts = [
        'changes' => 'array', 'amount' => 'float',
        'business_date' => 'date', 'created_at' => 'datetime',
    ];

    public const EVENTS = [
        'created' => ['Created', 'st-paid'],
        'updated' => ['Edited', 'st-info'],
        'deleted' => ['Deleted', 'st-danger'],
    ];

    public function label(): array
    {
        return self::EVENTS[$this->event] ?? [ucfirst((string) $this->event), 'st-muted'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
