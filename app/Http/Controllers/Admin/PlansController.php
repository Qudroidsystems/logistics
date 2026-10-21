<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Subscriptions\SubscriptionService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Operations > Business plans: what each plan costs and includes, and who is on one. */
class PlansController extends Controller
{
    private function check(Request $request): void
    {
        abort_unless($request->user()->can('Manage business plans'), 403, 'You cannot manage business plans.');
    }

    public function index(Request $request, SubscriptionService $plans)
    {
        $this->check($request);
        $status = $request->query('status', 'live');

        return view('ops.plans', [
            'plans' => $plans->plans(true),
            'subs' => DB::table('subscriptions as s')->join('subscription_plans as p', 'p.id', '=', 's.plan_id')->join('users as u', 'u.id', '=', 's.user_id')
                ->when($status === 'live', fn ($q) => $q->whereIn('s.status', SubscriptionService::LIVE))->when($status !== 'live' && $status !== 'all', fn ($q) => $q->where('s.status', $status))
                ->orderByRaw("s.status = 'past_due' DESC")->orderBy('s.current_period_end')->limit(300)
                ->get(['s.*', 'p.name as plan', 'u.name as customer', 'u.email']),
            'counts' => DB::table('subscriptions')->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'mrr' => (int) DB::table('subscriptions')->where('status', 'active')->sum('period_price'),
            'status' => $status, 'pagetitle' => 'Business plans',
        ]);
    }

    public function update(Request $request, int $plan)
    {
        $this->check($request);
        $d = $request->validate([
            'name' => 'required|string|max:60', 'tagline' => 'nullable|string|max:160', 'price' => 'required|string|max:12',
            'included_deliveries' => 'required|integer|min:0|max:100000', 'overage_price' => 'nullable|string|max:12', 'max_km' => 'nullable|integer|min:1|max:1000',
            'priority_dispatch' => 'nullable|boolean', 'contact_only' => 'nullable|boolean', 'active' => 'nullable|boolean', 'features' => 'nullable|string|max:1000', 'sort' => 'nullable|integer|min:0|max:100',
        ]);
        $price = Money::toMinor($d['price']);
        if ($price === null || $price < 0) {
            return back()->with('error', 'Enter the monthly price.');
        }
        $over = isset($d['overage_price']) && trim($d['overage_price']) !== '' ? Money::toMinor($d['overage_price']) : null;
        $n = DB::table('subscription_plans')->where('id', $plan)->update([
            'name' => $d['name'], 'tagline' => $d['tagline'] ?? null, 'price' => $price, 'included_deliveries' => (int) $d['included_deliveries'],
            'overage_price' => $over, 'max_km' => $d['max_km'] ?? null, 'priority_dispatch' => (bool) ($d['priority_dispatch'] ?? false),
            'contact_only' => (bool) ($d['contact_only'] ?? false), 'active' => (bool) ($d['active'] ?? false), 'sort' => (int) ($d['sort'] ?? 0),
            'features' => json_encode(array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($d['features'] ?? '')))))),
            'updated_at' => now(),
        ]);
        abort_unless($n, 404);

        return back()->with('success', 'Plan saved. Customers on it get the new price and allowance from their next renewal.');
    }
}
