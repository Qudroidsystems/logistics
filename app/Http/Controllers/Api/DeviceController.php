<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** The app registers its push token here (after sign-in, and again whenever Firebase rotates it) and removes it on sign-out. */
class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $d = $request->validate([
            'device_id' => 'required|string|min:8|max:128',
            'push_token' => 'required|string|max:400',
            'platform' => 'nullable|in:android,ios,web',
            'os' => 'nullable|string|max:32', 'app_version' => 'nullable|string|max:24',
        ]);
        $uid = $request->user()->id;

        // A token belongs to one account: if the phone changed hands, the previous owner stops receiving its pushes.
        DB::table('devices')->where('push_token', $d['push_token'])->where('user_id', '!=', $uid)->update(['push_token' => null]);
        DB::table('devices')->updateOrInsert(
            ['user_id' => $uid, 'device_fingerprint' => $d['device_id']],
            ['platform' => $d['platform'] ?? null, 'os' => $d['os'] ?? null, 'app_version' => $d['app_version'] ?? null, 'push_token' => $d['push_token'], 'last_seen_at' => now()]
        );

        return response()->json(['ok' => true]);
    }

    public function unregister(Request $request, string $deviceId)
    {
        DB::table('devices')->where('user_id', $request->user()->id)->where('device_fingerprint', $deviceId)->update(['push_token' => null]);

        return response()->json(['ok' => true]);
    }
}
