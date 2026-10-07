<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\NotificationPreferences;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['preferences' => NotificationPreferences::forUser($request->user()->id)]);
    }

    public function update(Request $request)
    {
        $d = $request->validate(['category' => 'required|string|max:30', 'in_app' => 'nullable|boolean', 'email' => 'nullable|boolean']);
        if (! NotificationPreferences::canMute($d['category'])) {
            return response()->json(['error' => 'not_changeable', 'message' => 'That kind of message is always sent.'], 422);
        }
        NotificationPreferences::set($request->user()->id, $d['category'], $d['in_app'] ?? null, $d['email'] ?? null);

        return response()->json(['preferences' => NotificationPreferences::forUser($request->user()->id)]);
    }
}
