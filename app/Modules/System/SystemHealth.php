<?php

namespace App\Modules\System;

use App\Modules\Maps\Geocoder;
use App\Modules\Routing\RoutingEngine;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Is the background work running? Every scheduled command's start, finish and failure is recorded
 * (scheduled_task_runs). system:watch (every 10 minutes) compares them with the schedule, checks the queue, saves a
 * snapshot for Ops > System and tells staff with "View system health" when something is late or failing.
 * If system:watch itself stops, the page says the scheduler is not running.
 */
class SystemHealth
{
    /** A task is late when its last due time passed this many minutes ago without a finished run. */
    public const GRACE_MINUTES = 15;

    /** A queued job waiting longer than this means no worker is running (or it is stuck). */
    public const QUEUE_STALE_MINUTES = 10;

    /** The same problem is reported again at most this often. */
    public const ALERT_EVERY_HOURS = 6;

    public static function taskName(Event $task): string
    {
        if ($task->description) {
            return mb_substr($task->description, 0, 120);
        }
        $cmd = (string) $task->command;
        if (preg_match("/artisan'?\\s+(.+)$/", $cmd, $m)) {
            $cmd = $m[1];
        }

        return mb_substr(trim(str_replace("'", '', $cmd)), 0, 120);
    }

    // ---------------------------------------------------------------- recording (event listeners)

    public static function started(Event $task): void
    {
        self::write(self::taskName($task), ['last_started_at' => now(), 'last_status' => 'running']);
    }

    public static function finished(Event $task, float $runtime): void
    {
        $failed = $task->exitCode !== null && $task->exitCode !== 0;
        self::write(self::taskName($task), ['last_finished_at' => now(), 'last_status' => $failed ? 'failed' : 'ok', 'runtime_ms' => (int) round($runtime * 1000),
            'last_error' => $failed ? 'Exit code '.$task->exitCode : null]);
    }

    public static function failed(Event $task, Throwable $e): void
    {
        self::write(self::taskName($task), ['last_finished_at' => now(), 'last_status' => 'failed', 'last_error' => mb_substr($e->getMessage(), 0, 500)]);
    }

    private static function write(string $name, array $values): void
    {
        try {
            DB::table('scheduled_task_runs')->upsert([$values + ['name' => $name, 'created_at' => now(), 'updated_at' => now()]], ['name'], array_merge(array_keys($values), ['updated_at']));
        } catch (Throwable $e) {
            Log::warning("Could not record scheduled task {$name}: {$e->getMessage()}");
        }
    }

    // ---------------------------------------------------------------- checking

    /** Pure: the state of one task. @return 'ok'|'late'|'failed'|'running'|'never' */
    public static function state(string $expression, ?CarbonImmutable $lastFinished, ?string $lastStatus, ?CarbonImmutable $lastStarted, CarbonImmutable $now): string
    {
        $due = CarbonImmutable::instance((new CronExpression($expression))->getPreviousRunDate($now->toDateTime(), 0, true));
        if ($lastStatus === 'running' && $lastStarted && $lastStarted->gt($now->subMinutes(60))) {
            return 'running';
        }
        if ($lastStatus === 'failed' && $lastFinished && $lastFinished->gte($due->subMinute())) {
            return 'failed';
        }
        if ($lastFinished && $lastFinished->gte($due->subMinute())) {
            return 'ok';
        }
        if ($due->gt($now->subMinutes(self::GRACE_MINUTES))) {
            // Just became due; give it time to run.
            return $lastFinished ? 'ok' : 'never';
        }

        return $lastFinished ? 'late' : 'never';
    }

    /** Runs in the console (the schedule is only loaded there). Saves the snapshot and alerts. Returns the problems. */
    public function check(Schedule $schedule, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $runs = DB::table('scheduled_task_runs')->get()->keyBy('name');
        $tasks = [];
        foreach ($schedule->events() as $e) {
            $name = self::taskName($e);
            $r = $runs[$name] ?? null;
            $p = fn ($v) => $v ? CarbonImmutable::parse($v) : null;
            $state = self::state($e->expression, $p($r?->last_finished_at), $r?->last_status, $p($r?->last_started_at), $now);
            $tasks[] = [
                'name' => $name, 'expression' => $e->expression, 'state' => $state,
                'last_finished_at' => $r?->last_finished_at, 'last_error' => $r?->last_error, 'runtime_ms' => $r?->runtime_ms,
                'next_due' => CarbonImmutable::instance((new CronExpression($e->expression))->getNextRunDate($now->toDateTime()))->toIso8601String(),
            ];
        }
        $queue = $this->queue($now);
        $snapshot = ['checked_at' => $now->toIso8601String(), 'tasks' => $tasks, 'queue' => $queue];
        Cache::forever('system_health.snapshot', $snapshot);

        $problems = [];
        foreach ($tasks as $t) {
            if ($t['state'] === 'late') {
                $problems["late:{$t['name']}"] = "\"{$t['name']}\" has not run since it was due.";
            } elseif ($t['state'] === 'failed') {
                $problems["failed:{$t['name']}"] = "\"{$t['name']}\" failed: ".($t['last_error'] ?: 'see the logs').'.';
            }
        }
        if ($queue['oldest_minutes'] !== null && $queue['oldest_minutes'] >= self::QUEUE_STALE_MINUTES) {
            $problems['queue'] = "{$queue['waiting']} background job(s) have been waiting {$queue['oldest_minutes']} minutes: the queue worker may have stopped.";
        }
        if ($queue['failed_24h'] > 0) {
            $problems['failed_jobs'] = "{$queue['failed_24h']} background job(s) failed in the last 24 hours.";
        }
        $this->alert($problems);

        return $problems;
    }

    /** @return array{waiting:int, oldest_minutes:?int, failed_24h:int, failed_total:int} */
    public function queue(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $oldest = DB::table('jobs')->whereNull('reserved_at')->min('available_at');

        return [
            'waiting' => (int) DB::table('jobs')->count(),
            'oldest_minutes' => $oldest ? max(0, (int) floor(($now->timestamp - (int) $oldest) / 60)) : null,
            'failed_24h' => (int) DB::table('failed_jobs')->where('failed_at', '>', $now->subDay())->count(),
            'failed_total' => (int) DB::table('failed_jobs')->count(),
        ];
    }

    private function alert(array $problems): void
    {
        $fresh = array_filter($problems, fn ($k) => Cache::add('system_health.alerted.'.md5($k), 1, now()->addHours(self::ALERT_EVERY_HOURS)), ARRAY_FILTER_USE_KEY);
        if (! $fresh) {
            return;
        }
        try {
            $staff = \App\Models\User::permission('View system health')->where('is_disabled', false)->pluck('id');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            Log::warning('System problems, but the "View system health" permission is not seeded: '.implode(' ', $fresh));

            return;
        }
        foreach ($staff as $id) {
            app(\App\Modules\Notifications\NotificationService::class)->notify((int) $id, 'system.problem', ['problems' => implode(' ', $fresh)], '/ops/system');
        }
    }

    /** What the page shows (read from the snapshot, plus live figures and configuration). */
    public function page(): array
    {
        $snap = Cache::get('system_health.snapshot');
        $checked = $snap ? CarbonImmutable::parse($snap['checked_at']) : null;

        return [
            'checked_at' => $checked,
            // system:watch runs every 10 minutes; no snapshot for 25 means the scheduler (cron) is not running.
            'scheduler_running' => $checked !== null && $checked->gt(now()->subMinutes(25)),
            'tasks' => $snap['tasks'] ?? [],
            'queue' => $this->queue(),
            'failed' => DB::table('failed_jobs')->orderByDesc('failed_at')->limit(20)->get(['id', 'uuid', 'queue', 'payload', 'exception', 'failed_at'])
                ->map(fn ($f) => ['id' => $f->id, 'uuid' => $f->uuid, 'queue' => $f->queue, 'job' => json_decode((string) $f->payload, true)['displayName'] ?? 'Job',
                    'error' => mb_substr(strtok((string) $f->exception, "\n"), 0, 300), 'failed_at' => $f->failed_at])->all(),
            'config' => [
                ['Routing (prices and ETAs)', RoutingEngine::activeDriver(), RoutingEngine::activeDriver() !== 'straight', 'Set ROUTING_DRIVER to google, mapbox or osrm with its key.'],
                ['Address search', app(Geocoder::class)->driver(), app(Geocoder::class)->driver() !== 'nominatim', 'Set GEOCODER to google or mapbox with its key; nominatim is for testing only.'],
                ['Map tiles', parse_url(Geocoder::tiles()['url'], PHP_URL_HOST) ?: '?', ! str_contains(Geocoder::tiles()['url'], 'tile.openstreetmap.org'), 'Set MAP_TILE_URL or MAPBOX_PUBLIC_TOKEN.'],
                ['Push notifications', (string) config('services.push.driver', 'log'), config('services.push.driver') === 'fcm', 'Set PUSH_DRIVER=fcm with the Firebase service account.'],
                ['Email', (string) config('mail.default'), ! in_array(config('mail.default'), ['log', 'array'], true), 'Set MAIL_MAILER to a real mail service.'],
                ['Queue', (string) config('queue.default'), config('queue.default') !== 'sync', 'Run a queue worker (php artisan queue:work) under a process manager.'],
                ['Debug mode', config('app.debug') ? 'on' : 'off', ! config('app.debug'), 'Set APP_DEBUG=false in production.'],
            ],
        ];
    }
}
