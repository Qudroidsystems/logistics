<?php

namespace App\Console\Commands;

use App\Models\FeatureSync;
use App\Services\FeatureSyncService;
use Illuminate\Console\Command;

class PullFeatureFlags extends Command
{
    protected $signature = 'features:pull {--force : Pull even if auto-pull is off}';
    protected $description = 'Pull module feature flags from the remote control portal.';

    public function handle(FeatureSyncService $sync): int
    {
        $cfg = FeatureSync::current();
        if (!$cfg->auto_pull && !$this->option('force')) {
            $this->line('Auto-pull is off; nothing to do.');
            return self::SUCCESS;
        }
        $r = $sync->pull();
        $this->line(($r['ok'] ? '✅ ' : '⚠️  ') . $r['message']);
        return self::SUCCESS;
    }
}
