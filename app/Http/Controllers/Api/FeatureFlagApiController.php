<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Http\Middleware\FeatureRouteGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * The remote control portal talks to these endpoints (behind the
 * remote.portal middleware). GET reads the current 1/0 map; POST sets it.
 */
class FeatureFlagApiController extends Controller
{
    /** Current flags as {key: 0|1}. This is what the remote reads. */
    public function index()
    {
        return response()->json([
            'ok' => true,
            'flags' => collect(FeatureFlag::map())->map(fn ($v) => $v ? 1 : 0),
            'at' => now()->toIso8601String(),
        ]);
    }

    /** Simple heartbeat: always returns 1 so the remote knows we're alive. */
    public function health()
    {
        return response()->json(['ok' => 1]);
    }

    /**
     * Set flags. Accepts either:
     *   { "flags": { "dashboard": 1, "results": 0 } }
     * or a single flag:
     *   { "key": "dashboard", "value": 1 }
     */
    public function sync(Request $request)
    {
        $data = $request->validate([
            'flags' => 'required_without:key|array',
            'key' => 'required_without:flags|string|max:60',
            'value' => 'required_with:key',
        ]);

        $flags = $data['flags'] ?? [$data['key'] => $data['value']];
        $summary = FeatureFlag::applyRemote($flags, 'api');

        return response()->json([
            'ok' => true,
            'applied' => $summary['changed'],
            'skipped' => $summary['skipped'],
            'flags' => collect(FeatureFlag::map())->map(fn ($v) => $v ? 1 : 0),
        ]);
    }

    /**
     * Full control catalog for the remote console: every feature flag with its
     * label/group/state PLUS the actual route names each flag governs, so the
     * remote can render a complete panel and see all routes it controls.
     */
    public function catalog()
    {
        $map = FeatureRouteGuard::moduleRouteMap();          // route-name prefix => key
        $allow = FeatureRouteGuard::allowList();             // never-blocked prefixes

        // Group every NAMED route under the flag key that governs it.
        $routesByKey = [];
        foreach (Route::getRoutes()->getRoutesByName() as $name => $route) {
            $key = null;
            foreach ($map as $prefix => $k) {
                if ($name === $prefix || str_starts_with($name, $prefix)) { $key = $k; break; }
            }
            if ($key) $routesByKey[$key][] = $name;
        }

        $flags = FeatureFlag::query()->orderBy('group')->orderBy('label')->get()
            ->map(function ($f) use ($routesByKey) {
                $routes = $routesByKey[$f->key] ?? [];
                sort($routes);
                return [
                    'key' => $f->key,
                    'label' => $f->label,
                    'group' => $f->group,
                    'enabled' => $f->enabled ? 1 : 0,
                    'remote_controlled' => $f->remote_controlled ? 1 : 0,
                    'routes' => array_values($routes),
                    'route_count' => count($routes),
                ];
            })->values();

        return response()->json([
            'ok' => true,
            'flags' => $flags,
            'always_allowed' => array_values($allow),
            'note' => 'Set a flag with POST /sync {flags:{key:0|1}}. A flag governs every route name in its "routes" list.',
            'at' => now()->toIso8601String(),
        ]);
    }
}
