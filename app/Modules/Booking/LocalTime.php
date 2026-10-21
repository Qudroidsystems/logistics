<?php

namespace App\Modules\Booking;

use Carbon\Carbon;

/**
 * Timestamps are written as the app's wall-clock time (config('app.timezone')) while the database session runs on UTC, so a
 * value read back carries "+00" but means local time. This gives the apps the moment with the right offset.
 */
class LocalTime
{
    public static function iso(?string $stored): ?string
    {
        return $stored ? Carbon::parse(substr($stored, 0, 19), config('app.timezone'))->toIso8601String() : null;
    }
}
