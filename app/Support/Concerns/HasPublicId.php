<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

/** Fills the ULID `public_id` column on create; routes can bind on it. */
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
