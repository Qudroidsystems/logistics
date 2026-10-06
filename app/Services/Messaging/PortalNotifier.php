<?php

namespace App\Services\Messaging;

use App\Models\User;
use App\Notifications\PortalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Writes in-portal notifications (the bell). Bulk-inserts straight into the
 * notifications table so a whole-school notice stays fast. A `key` makes a
 * notification idempotent per user (e.g. "notice:12").
 */
class PortalNotifier
{
    public const ICONS = [
        'notice'   => 'ri-megaphone-line',
        'result'   => 'ri-file-list-3-line',
        'payment'  => 'ri-money-dollar-circle-line',
        'absence'  => 'ri-user-unfollow-line',
        'fees'     => 'ri-bill-line',
        'birthday' => 'ri-cake-2-line',
        'system'   => 'ri-notification-3-line',
    ];

    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('notifications');
    }

    /** @param int[] $userIds */
    public static function toUsers(array $userIds, string $title, string $body, ?string $url = null, string $type = 'system', ?string $key = null): int
    {
        if (!self::available()) return 0;
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (!$userIds) return 0;

        try {
            if ($key) {
                $already = DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->whereIn('notifiable_id', $userIds)
                    ->where('data', 'like', '%"key":' . json_encode($key) . '%')
                    ->pluck('notifiable_id')->map(fn ($v) => (int) $v)->all();
                $userIds = array_values(array_diff($userIds, $already));
            }

            $data = json_encode([
                'title' => mb_substr($title, 0, 150),
                'body'  => mb_substr($body, 0, 1000),
                'url'   => $url,
                'type'  => $type,
                'icon'  => self::ICONS[$type] ?? self::ICONS['system'],
                'key'   => $key,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $now = now();
            foreach (array_chunk($userIds, 500) as $chunk) {
                DB::table('notifications')->insert(array_map(fn ($uid) => [
                    'id' => (string) Str::uuid(), 'type' => PortalNotification::class,
                    'notifiable_type' => User::class, 'notifiable_id' => $uid,
                    'data' => $data, 'read_at' => null, 'created_at' => $now, 'updated_at' => $now,
                ], $chunk));
            }
            return count($userIds);
        } catch (\Throwable $e) {
            Log::warning('Portal notification failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /** Student portal accounts (users.student_id) for these students. */
    public static function toStudents(array $studentIds, string $title, string $body, ?string $url = null, string $type = 'system', ?string $key = null): int
    {
        if (!$studentIds) return 0;
        $users = User::whereIn('student_id', array_map('intval', $studentIds))->pluck('id')->all();
        $users = array_values(array_unique(array_merge($users, \App\Services\Parents\ParentAccountService::parentUserIds($studentIds))));
        return self::toUsers($users, $title, $body, $url, $type, $key);
    }

    /** Staff accounts (no student link, not student/parent role). */
    public static function staffIds(): array
    {
        return User::whereNull('student_id')
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', ['Student', 'Parent', 'student', 'parent']))
            ->pluck('id')->all();
    }
}
