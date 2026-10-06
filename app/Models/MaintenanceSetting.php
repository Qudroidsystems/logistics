<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row settings for admin-controlled maintenance mode.
 * Use MaintenanceSetting::current() everywhere (cached).
 */
class MaintenanceSetting extends Model
{
    protected $table = 'maintenance_settings';

    protected $fillable = [
        'is_active', 'title', 'message', 'contact_info', 'allow_role_ids', 'retry_after',
        'scheduled_at', 'scheduled_note', 'activated_at', 'activated_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_role_ids' => 'array',
        'scheduled_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public const CACHE_KEY = 'maintenance_settings_row';

    public static function current(): self
    {
        if (!Schema::hasTable('maintenance_settings')) {
            return new self(['is_active' => false]);
        }
        return Cache::remember(self::CACHE_KEY, 60, fn () => self::query()->firstOrCreate(['id' => 1]));
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forget());
    }

    /** A planned switch-on that hasn't happened yet. */
    public function isScheduledPending(): bool
    {
        return !$this->is_active && $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    /** A schedule whose time has arrived but the flag hasn't been flipped yet. */
    public function isScheduleDue(): bool
    {
        return !$this->is_active && $this->scheduled_at && $this->scheduled_at->isPast();
    }
}
