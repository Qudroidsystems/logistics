<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** Name, tagline, logo and colours each mobile app shows on its splash. Edited by admins. */
class BrandSetting extends Model
{
    protected $fillable = ['app', 'name', 'tagline', 'logo_path', 'primary_color', 'secondary_color'];

    public const APPS = ['customer' => 'Customer app', 'driver' => 'Driver app'];

    public static function forApp(string $app): array
    {
        $app = array_key_exists($app, self::APPS) ? $app : 'customer';

        return Cache::remember("brand_settings.$app", 300, function () use ($app) {
            $row = Schema::hasTable('brand_settings') ? self::query()->where('app', $app)->first() : null;
            return [
                'app' => $app,
                'name' => $row?->name ?: config('app.name', 'Logistics'),
                'tagline' => $row?->tagline,
                'logo_url' => $row?->logo_path ? Storage::disk('public')->url($row->logo_path) : null,
                'primary_color' => $row?->primary_color ?: '#0F766E',
                'secondary_color' => $row?->secondary_color ?: '#115E59',
            ];
        });
    }

    protected static function booted(): void
    {
        static::saved(fn (self $m) => Cache::forget("brand_settings.{$m->app}"));
    }
}
