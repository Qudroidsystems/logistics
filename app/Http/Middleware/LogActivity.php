<?php

namespace App\Http\Middleware;

use App\Services\Activity\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Records every change (non-GET request) by a signed-in user, and keeps the
 * user's "last seen" time up to date for the who's-online list.
 */
class LogActivity
{
    /** Background / noisy endpoints that aren't worth logging. */
    protected array $skip = ['notifications.feed', 'notifications.read-all', 'presence.ping', 'management.fee-status', 'system.deadline.status'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $user = $request->user();
            if (!$user) return $response;

            // Last seen (at most once a minute per user).
            if (Cache::add('seen:' . $user->id, 1, 60)) {
                DB::table('users')->where('id', $user->id)->update([
                    'last_seen_at' => now(),
                    'last_seen_url' => $request->isMethod('GET') && !$request->ajax() ? mb_substr($request->path(), 0, 250) : DB::raw('last_seen_url'),
                ]);
            }

            if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) return $response;
            if ($request->routeIs(...$this->skip) || $request->is('webhook/*', 'logout')) return $response;

            [$event, $desc] = ActivityLogger::describe($request);
            $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null;
            if ($status >= 400) $desc .= ' (failed)';
            ActivityLogger::log($user->id, $event, $desc, $request, ActivityLogger::inputs($request), $status);
        } catch (\Throwable $e) {
            // never break the request
        }

        return $response;
    }
}
