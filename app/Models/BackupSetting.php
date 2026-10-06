<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $fillable = [
        'enabled', 'frequency', 'day_of_week', 'day_of_month', 'run_time',
        'email', 'email_attach', 'keep_last', 'last_run_at', 'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean', 'email_attach' => 'boolean',
        'day_of_week' => 'integer', 'day_of_month' => 'integer', 'keep_last' => 'integer',
        'last_run_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create(['enabled' => false, 'frequency' => 'daily', 'run_time' => '02:00', 'keep_last' => 14]);
    }
}
