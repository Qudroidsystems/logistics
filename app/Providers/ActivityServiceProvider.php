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

/** Logs logins / logouts / failed logins for the audit trail. */
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

}
