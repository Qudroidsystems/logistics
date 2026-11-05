<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

/**
 * App sign-ins (Sanctum tokens) that have not been used for APP_TOKEN_IDLE_DAYS (60) are removed, so a lost or
 * sold phone does not stay signed in forever. Phones in daily use are never signed out: every request refreshes last_used_at.
 */
class PruneIdleTokens extends Command
{
    protected $signature = 'tokens:prune-idle {--days= : Override APP_TOKEN_IDLE_DAYS}';

    protected $description = 'Sign out app sessions that have not been used for a while';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: config('services.app_tokens.idle_days', 60)));
        $n = DB::table('personal_access_tokens')->whereRaw('COALESCE(last_used_at, created_at) < ?', [now()->subDays($days)])->delete();
        $this->info("{$n} idle app sign-in(s) removed (unused for {$days} days).");

        return self::SUCCESS;
    }
}
