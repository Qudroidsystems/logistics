<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * A single on/off module flag. Read it everywhere through FeatureFlag::enabled('key').
 * Missing keys are treated as ON (fail-open), so nothing disappears by accident.
 */
class FeatureFlag extends Model
{
    protected $table = 'feature_flags';

    protected $fillable = ['key', 'label', 'group', 'enabled', 'remote_controlled', 'description', 'synced_at'];

    protected $casts = [
        'enabled' => 'boolean',
        'remote_controlled' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public const CACHE_KEY = 'feature_flags_map';

    /** [key => bool] of every flag, cached. */
    public static function map(): array
    {
        if (!Schema::hasTable('feature_flags')) {
            return [];
        }
        return Cache::remember(self::CACHE_KEY, 120, fn () => self::query()->pluck('enabled', 'key')->map(fn ($v) => (bool) $v)->all());
    }

    /** Is a module on? Unknown keys are ON (fail-open). */
    public static function enabled(string $key): bool
    {
        $map = self::map();
        return array_key_exists($key, $map) ? $map[$key] : true;
    }

    /** True if ANY of the keys is on. */
    public static function anyEnabled(array $keys): bool
    {
        foreach ($keys as $k) {
            if (self::enabled($k)) {
                return true;
            }
        }
        return false;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forget());
        static::deleted(fn () => self::forget());
    }

    /**
     * Apply a batch of {key: 0|1|true|false} from the remote. Only existing,
     * remote-controlled keys are touched; unknown keys are created as
     * remote-controlled so the remote can define new ones. Returns a summary.
     */
    public static function applyRemote(array $values, ?string $source = 'remote'): array
    {
        $changed = 0; $skipped = [];
        foreach ($values as $key => $raw) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            $on = self::truthy($raw);
            $flag = self::firstOrNew(['key' => $key]);
            if ($flag->exists && !$flag->remote_controlled) {
                $skipped[] = $key; // an admin marked this one local-only
                continue;
            }
            if (!$flag->exists) {
                $flag->label = ucwords(str_replace(['_', '-'], ' ', $key));
                $flag->remote_controlled = true;
            }
            if ($flag->enabled !== $on || !$flag->exists) {
                $changed++;
            }
            $flag->enabled = $on;
            $flag->synced_at = now();
            $flag->save();
        }
        self::forget();
        return ['changed' => $changed, 'skipped' => $skipped, 'total' => count($values)];
    }

    public static function truthy($v): bool
    {
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return (int) $v === 1;
        return in_array(strtolower((string) $v), ['1', 'true', 'on', 'yes', 'enabled'], true);
    }
}
