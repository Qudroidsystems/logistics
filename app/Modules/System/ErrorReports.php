<?php

namespace App\Modules\System;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Built-in error tracking. Every unexpected exception (Laravel already skips validation errors, 404s, expired sessions
 * and the like) is grouped by where it happened and counted; Ops > System lists them, and staff who can see that page
 * are told when a new kind appears or a resolved one comes back. Recording never throws: a broken database must not
 * turn one error into two.
 */
class ErrorReports
{
    /** Pure: the same exception class at the same line is the same problem, whatever the message's details. */
    public static function fingerprint(Throwable $e): string
    {
        $file = self::relative($e->getFile());

        return sha1(get_class($e).'|'.$file.'|'.$e->getLine());
    }

    public static function relative(string $file): string
    {
        $base = rtrim(base_path(), '/').'/';

        return str_starts_with($file, $base) ? substr($file, strlen($base)) : $file;
    }

    /** Pure: a short trace of our own frames (vendor frames folded), for the report. */
    public static function trace(Throwable $e, int $max = 25): string
    {
        $out = [];
        foreach ($e->getTrace() as $f) {
            $file = isset($f['file']) ? self::relative($f['file']) : '[internal]';
            if (str_starts_with($file, 'vendor/')) {
                if (end($out) !== '…vendor') {
                    $out[] = '…vendor';
                }

                continue;
            }
            $out[] = $file.':'.($f['line'] ?? '?').' '.($f['class'] ?? '').($f['type'] ?? '').($f['function'] ?? '');
            if (count($out) >= $max) {
                break;
            }
        }

        return implode("\n", $out);
    }

    public function record(Throwable $e): void
    {
        try {
            $fp = self::fingerprint($e);
            $req = app()->runningInConsole() ? null : request();
            $now = now();
            $row = [
                'fingerprint' => $fp, 'class' => mb_substr(get_class($e), 0, 200), 'message' => mb_substr($e->getMessage() ?: '(no message)', 0, 500),
                'file' => mb_substr(self::relative($e->getFile()), 0, 300), 'line' => $e->getLine(),
                'url' => $req ? mb_substr($req->method().' '.$req->path(), 0, 300) : mb_substr('console '.implode(' ', array_slice((array) ($_SERVER['argv'] ?? []), 1, 3)), 0, 300),
                'method' => $req?->method(), 'user_id' => $req?->user()?->id, 'trace' => self::trace($e),
            ];
            // A savepoint: if the caller's transaction is already aborted this fails (and is caught) without harming it.
            $before = DB::transaction(function () use ($fp, $row, $now) {
                $before = DB::table('error_reports')->where('fingerprint', $fp)->first(['id', 'status']);
                if ($before) {
                    DB::table('error_reports')->where('id', $before->id)->update([
                        'count' => DB::raw('count + 1'), 'message' => $row['message'], 'url' => $row['url'], 'user_id' => $row['user_id'], 'status' => 'open', 'last_seen_at' => $now,
                    ]);
                } else {
                    DB::table('error_reports')->insertOrIgnore($row + ['count' => 1, 'status' => 'open', 'first_seen_at' => $now, 'last_seen_at' => $now]);
                }

                return $before;
            });
            if (! $before || $before->status === 'resolved') {
                $this->alert($row, (bool) $before);
            }
        } catch (Throwable $inner) {
            Log::warning('Could not record an error report: '.$inner->getMessage());
        }
    }

    private function alert(array $row, bool $returned): void
    {
        if (! Cache::add('error_reports.alerted.'.$row['fingerprint'], 1, now()->addHours(6))) {
            return;
        }
        try {
            $staff = \App\Models\User::permission('View system health')->where('is_disabled', false)->pluck('id');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return;
        }
        $what = ($returned ? 'An error came back: ' : 'New error: ').class_basename($row['class']).' at '.$row['file'].':'.$row['line'].' ('.mb_substr($row['message'], 0, 120).').';
        foreach ($staff as $id) {
            app(\App\Modules\Notifications\NotificationService::class)->notify((int) $id, 'system.problem', ['problems' => $what], '/ops/system');
        }
    }

    /** @return array<int, object> open errors, most recent first */
    public function open(int $limit = 50): array
    {
        return DB::table('error_reports')->where('status', 'open')->orderByDesc('last_seen_at')->limit($limit)->get()->all();
    }

    public function resolve(int $id): void
    {
        DB::table('error_reports')->where('id', $id)->update(['status' => 'resolved']);
    }

    /** Daily: forget errors resolved (or not seen) for 30 days. */
    public function prune(): int
    {
        return DB::table('error_reports')->where('last_seen_at', '<', now()->subDays(30))->delete();
    }
}
