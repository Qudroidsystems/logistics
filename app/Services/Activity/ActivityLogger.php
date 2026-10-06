<?php

namespace App\Services\Activity;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Writes the staff activity log. Never throws — logging must not break a page. */
class ActivityLogger
{
    /** Form fields that are never stored. */
    public const SECRET = ['password', 'password_confirmation', 'current_password', 'token', '_token', 'secret', 'api_key', 'access_token',
                           'account_number', 'pin', 'otp', 'code', 'card', 'cvv', 'bvn', 'nin'];

    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('activity_logs');
    }

    public static function log(?int $userId, string $event, string $description, ?Request $request = null, array $props = [], ?int $status = null): void
    {
        if (!self::available()) return;
        try {
            $request ??= request();
            $ua = (string) $request?->userAgent();
            DB::table('activity_logs')->insert([
                'user_id' => $userId, 'event' => $event, 'description' => Str::limit($description, 250, '…'),
                'route' => $request?->route()?->getName(), 'method' => $request?->method(),
                'url' => Str::limit((string) $request?->fullUrl(), 495, ''), 'status' => $status,
                'ip' => $request?->ip(), 'device' => self::device($ua), 'user_agent' => Str::limit($ua, 250, ''),
                'properties' => $props ? json_encode($props) : null, 'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Activity log failed', ['error' => $e->getMessage()]);
        }
    }

    /** "Chrome on Windows", "Safari on iPhone"… */
    public static function device(string $ua): string
    {
        $os = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone', str_contains($ua, 'iPad') => 'iPad', str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows', str_contains($ua, 'Mac OS') => 'Mac', str_contains($ua, 'Linux') => 'Linux', default => 'Unknown',
        };
        $br = match (true) {
            str_contains($ua, 'Edg/') => 'Edge', str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera', str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox', str_contains($ua, 'Safari/') => 'Safari', default => 'Browser',
        };
        return "$br on $os";
    }

    /** Safe summary of submitted fields (no secrets, no files, short values only). */
    public static function inputs(Request $request): array
    {
        $out = [];
        foreach ($request->except(array_merge(self::SECRET, ['_method'])) as $k => $v) {
            if (count($out) >= 25) break;
            $lk = strtolower((string) $k);
            foreach (self::SECRET as $s) if (str_contains($lk, $s)) continue 2;
            if (is_array($v)) { $out[$k] = '[' . count($v) . ' item' . (count($v) === 1 ? '' : 's') . ']'; continue; }
            if (is_object($v)) { $out[$k] = '[file]'; continue; }
            $out[$k] = Str::limit((string) $v, 80);
        }
        return $out;
    }

    /** Human description from the route name, e.g. staff.payments.store → "Created: staff payments". */
    public static function describe(Request $request): array
    {
        $name = (string) $request->route()?->getName();
        $method = strtoupper($request->method());
        $action = $name ? Str::afterLast($name, '.') : '';
        $event = match (true) {
            $method === 'DELETE' || in_array($action, ['destroy', 'delete', 'remove', 'unlink', 'unassign'], true) => 'delete',
            in_array($method, ['PUT', 'PATCH'], true) || in_array($action, ['update', 'save', 'toggle'], true) => 'update',
            in_array($action, ['store', 'create', 'add', 'assign'], true) => 'create',
            default => 'action',
        };
        $area = $name ? Str::of(Str::beforeLast($name, '.'))->replace(['.', '-', '_'], ' ')->trim()->toString() : trim($request->path(), '/');
        $verb = ['create' => 'Created', 'update' => 'Updated', 'delete' => 'Deleted', 'action' => ucfirst(str_replace(['-', '_'], ' ', $action ?: 'action'))][$event];
        return [$event, trim("$verb: " . ($area ?: $request->path()))];
    }
}
