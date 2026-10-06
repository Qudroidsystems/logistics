<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\FeatureSync;
use App\Services\FeatureSyncService;
use Illuminate\Http\Request;

/** Admin view of module flags + the remote-sync settings. */
class FeatureFlagController extends Controller
{
    public function __construct(protected FeatureSyncService $sync)
    {
        $this->middleware('permission:Manage feature flags');
    }

    public function index()
    {
        $cfg = FeatureSync::current();
        return view('admin.feature-flags.index', [
            'pagetitle' => 'Module Access (Feature Flags)',
            'flags' => FeatureFlag::orderBy('group')->orderBy('label')->get()->groupBy('group'),
            'cfg' => $cfg,
            'endpoints' => [
                'read' => url('/api/feature-flags'),
                'write' => url('/api/feature-flags/sync'),
                'health' => url('/api/feature-flags/health'),
            ],
        ]);
    }

    /** Toggle a flag that is NOT remote-controlled (local override). */
    public function toggle(Request $request, FeatureFlag $flag)
    {
        if ($flag->remote_controlled) {
            return back()->with('error', $flag->label . ' is controlled by the remote portal. Mark it local first to toggle it here.');
        }
        $flag->update(['enabled' => $request->boolean('enabled')]);
        return back()->with('success', $flag->label . ' is now ' . ($flag->enabled ? 'ON' : 'OFF') . '.');
    }

    /** Switch a flag between remote-controlled and local. */
    public function setControl(Request $request, FeatureFlag $flag)
    {
        $flag->update(['remote_controlled' => $request->boolean('remote_controlled')]);
        return back()->with('success', $flag->label . ' is now ' . ($flag->remote_controlled ? 'controlled by the remote portal' : 'controlled locally') . '.');
    }

    public function saveSync(Request $request)
    {
        $data = $request->validate([
            'remote_url' => 'nullable|url|max:255',
            'remote_key' => 'nullable|string|max:255',
            'auto_pull' => 'nullable|boolean',
        ]);
        $cfg = FeatureSync::current();
        $cfg->remote_url = $data['remote_url'] ?? null;
        $cfg->auto_pull = $request->boolean('auto_pull');
        if (!empty($data['remote_key'])) {
            $cfg->setRemoteKey($data['remote_key']);   // blank keeps the saved one
        }
        $cfg->save();
        return back()->with('success', 'Sync settings saved.');
    }

    /** Create (or replace) the key the remote uses to call this portal. */
    public function regenerateKey()
    {
        $cfg = FeatureSync::current();
        $key = $this->sync->generateKey();
        $cfg->setApiKey($key);
        $cfg->save();
        return back()->with('success', 'New API key generated.')->with('new_api_key', $key);
    }

    public function pullNow()
    {
        $r = $this->sync->pull();
        return back()->with($r['ok'] ? 'success' : 'error', $r['message']);
    }
}
