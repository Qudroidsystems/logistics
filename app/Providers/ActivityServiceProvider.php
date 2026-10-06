<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/** Logs logins / logouts / failed logins and tells watchers when staff sign in. */
class ActivityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $e) {
            $u = $e->user;
            ActivityLogger::log($u->id, 'login', 'Signed in');
            try {
                $upd = ['last_seen_at' => now()];
                if (Schema::hasColumn('users', 'last_login_ip')) $upd['last_login_ip'] = request()->ip();
                if (Schema::hasColumn('users', 'last_login_at')) $upd['last_login_at'] = now();
                DB::table('users')->where('id', $u->id)->update($upd);
            } catch (\Throwable $ex) {}
            $this->notifyWatchers($u);
        });

        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                ActivityLogger::log($e->user->id, 'logout', 'Signed out');
                try { DB::table('users')->where('id', $e->user->id)->update(['last_seen_at' => null]); } catch (\Throwable $ex) {}
            }
        });

        Event::listen(Failed::class, function (Failed $e) {
            $login = (string) ($e->credentials['email'] ?? '');
            ActivityLogger::log($e->user?->id, 'login_failed', 'Failed sign-in' . ($login ? ' as ' . $login : ''), null, ['login' => mb_substr($login, 0, 80)]);
        });
    }

    /** Bell notification to users allowed to see who's online — staff sign-ins only, at most once per person every 2 hours. */
    protected function notifyWatchers($user): void
    {
        try {
            if (!class_exists(\App\Services\Messaging\PortalNotifier::class)) return;
            if (!empty($user->student_id) || (method_exists($user, 'hasRole') && $user->hasRole(['Student', 'Parent']))) return;
            if (!Cache::add('login-notice:' . $user->id, 1, now()->addHours(2))) return;

            $watchers = Cache::remember('online-watchers', 600, function () {
                try { return User::permission('View online staff')->pluck('id')->all(); } catch (\Throwable $e) { return []; }
            });
            $watchers = array_values(array_diff($watchers, [$user->id]));
            if (!$watchers) return;

            \App\Services\Messaging\PortalNotifier::toUsers($watchers, $user->name . ' signed in',
                'At ' . now()->format('h:i A') . ' from ' . ActivityLogger::device((string) request()->userAgent()) . ' (' . request()->ip() . ').',
                route('online-staff.index'), 'system', 'login:' . $user->id . ':' . now()->format('YmdH'));
        } catch (\Throwable $e) {}
    }
}
