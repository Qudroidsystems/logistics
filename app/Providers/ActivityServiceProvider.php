<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Identity\Security\Audit;
use App\Services\Activity\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/** Logs logins / logouts / failed logins, and tells the database who is acting for the data-change audit trail. */
class ActivityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Data-change audit: who is acting is kept on the database connection (see Audit).
        Event::listen(ConnectionEstablished::class, fn (ConnectionEstablished $e) => $e->connectionName === config('database.default') && $e->connection->getDriverName() === 'pgsql' ? Audit::apply() : null);
        Event::listen(JobProcessing::class, fn () => Audit::forSystem());
        Event::listen(CommandStarting::class, fn () => Audit::forSystem());

        Event::listen(Login::class, function (Login $e) {
            $u = $e->user;
            Audit::actingAs($u->id, $u->current_operator_id ? (int) $u->current_operator_id : null);
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
