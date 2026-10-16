<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\NotificationPreferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($this->state($request->user()->id));
    }

    public function update(Request $request)
    {
        // Offers (campaigns) are a yes/no of their own: they are never sent without it.
        if ($request->has('offers') && ! $request->has('category')) {
            $o = $request->validate(['offers' => 'required|boolean']);
            \App\Modules\Marketing\CampaignService::setOptIn($request->user()->id, (bool) $o['offers']);

            return response()->json($this->state($request->user()->id));
        }
        $d = $request->validate(['category' => 'required|string|max:30', 'in_app' => 'nullable|boolean', 'email' => 'nullable|boolean']);
        if (! NotificationPreferences::canMute($d['category'])) {
            return response()->json(['error' => 'not_changeable', 'message' => 'That kind of message is always sent.'], 422);
        }
        NotificationPreferences::set($request->user()->id, $d['category'], $d['in_app'] ?? null, $d['email'] ?? null);

        return response()->json($this->state($request->user()->id));
    }

    private function state(int $userId): array
    {
        return ['preferences' => NotificationPreferences::forUser($userId), 'offers' => (bool) DB::table('customer_profiles')->where('user_id', $userId)->value('marketing_opt_in')];
    }
}
