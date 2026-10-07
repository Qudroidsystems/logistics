<?php

namespace App\Modules\Notifications;

use Illuminate\Support\Facades\DB;

/**
 * What a user has switched off. Account-critical categories (provider status, payouts) can never be muted:
 * missing a suspension or a failed payout is worse than an extra email.
 */
class NotificationPreferences
{
    /** category => label, for the categories a user may mute. */
    public const MUTABLE = [
        'delivery' => 'Delivery updates',
        'offer' => 'Requests and offers',
        'rating' => 'Ratings',
        'team' => 'Team',
    ];

    /** Pure: the category an event belongs to ("delivery.assigned" => "delivery"; request.* belongs with offers). */
    public static function categoryOf(string $event): string
    {
        $prefix = explode('.', $event)[0];

        return $prefix === 'request' ? 'offer' : $prefix;
    }

    public static function canMute(string $category): bool
    {
        return isset(self::MUTABLE[$category]);
    }

    /** @return array{0:bool,1:bool} in-app allowed, email allowed */
    public static function channels(int $userId, string $event): array
    {
        $cat = self::categoryOf($event);
        if (! self::canMute($cat)) {
            return [true, true];
        }
        $row = DB::table('user_notification_preferences')->where(['user_id' => $userId, 'category' => $cat])->first(['in_app', 'email']);

        return $row ? [(bool) $row->in_app, (bool) $row->email] : [true, true];
    }

    /** @return array<int,array{category:string,label:string,in_app:bool,email:bool,locked:bool}> */
    public static function forUser(int $userId): array
    {
        $saved = DB::table('user_notification_preferences')->where('user_id', $userId)->get()->keyBy('category');
        $out = [];
        foreach (self::MUTABLE as $cat => $label) {
            $r = $saved[$cat] ?? null;
            $out[] = ['category' => $cat, 'label' => $label, 'in_app' => $r ? (bool) $r->in_app : true, 'email' => $r ? (bool) $r->email : true, 'locked' => false];
        }
        $out[] = ['category' => 'provider', 'label' => 'Provider account status', 'in_app' => true, 'email' => true, 'locked' => true];
        $out[] = ['category' => 'payout', 'label' => 'Payouts', 'in_app' => true, 'email' => true, 'locked' => true];

        return $out;
    }

    public static function set(int $userId, string $category, ?bool $inApp, ?bool $email): void
    {
        $cur = DB::table('user_notification_preferences')->where(['user_id' => $userId, 'category' => $category])->first();
        $vals = ['in_app' => $inApp ?? ($cur ? (bool) $cur->in_app : true), 'email' => $email ?? ($cur ? (bool) $cur->email : true), 'updated_at' => now()];
        if ($cur) {
            DB::table('user_notification_preferences')->where('id', $cur->id)->update($vals);
        } else {
            DB::table('user_notification_preferences')->insert($vals + ['user_id' => $userId, 'category' => $category, 'created_at' => now()]);
        }
    }
}
