<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\Admin\AdminOpsController;
use App\Http\Controllers\Api\Admin\AdminProviderController;
use App\Http\Controllers\Controller;
use App\Modules\Marketplace\DisputeService;
use App\Modules\Ratings\ProviderScoreService;
use App\Modules\Settlements\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The staff console: browser pages over the same code the JSON admin API uses, so a rule (who may decide a dispute,
 * what approval needs) lives in exactly one place. Permissions are enforced on the routes in routes/ops.php.
 */
class OpsConsoleController extends Controller
{
    public function __construct(private AdminOpsController $ops, private AdminProviderController $providers)
    {
    }

    public function dashboard()
    {
        return view('ops.dashboard', ['d' => $this->ops->dashboard()->getData(true)]);
    }

    // ---------------------------------------------------------------- orders

    public function orders(Request $request)
    {
        return view('ops.orders', ['rows' => $this->ops->orders($request)->getData(true), 'f' => $request->only('q', 'status', 'payment_status')]);
    }

    public function order(string $order)
    {
        return view('ops.order', ['o' => $this->ops->order($order)->getData(true)]);
    }

    // ---------------------------------------------------------------- disputes

    public function disputes(Request $request)
    {
        return view('ops.disputes', ['rows' => $this->ops->disputes($request)->getData(true), 'status' => $request->query('status', 'open')]);
    }

    public function dispute(string $dispute)
    {
        return view('ops.dispute', ['x' => $this->ops->dispute($dispute)->getData(true), 'id' => $dispute]);
    }

    public function decide(Request $request, string $dispute, DisputeService $svc)
    {
        $request->validate(['decision' => 'required|in:full_release,partial,full_refund', 'provider_share_naira' => 'required_if:decision,partial|nullable|numeric|min:0']);
        // Staff type naira; the money code only ever sees whole kobo.
        $request->merge(['provider_share' => $request->filled('provider_share_naira') ? (int) round(((float) $request->input('provider_share_naira')) * 100) : null]);

        return $this->back($this->ops->decideDispute($request, $dispute, $svc), 'Decision recorded and money moved.', route('ops.disputes'));
    }

    // ---------------------------------------------------------------- provider applications

    public function applications(Request $request)
    {
        return view('ops.applications', ['rows' => $this->providers->applications($request)->getData(true), 'status' => $request->query('status', 'submitted')]);
    }

    public function application(string $operator)
    {
        return view('ops.application', ['a' => $this->providers->application($operator)->getData(true), 'id' => $operator]);
    }

    public function document(string $doc)
    {
        return $this->providers->document($doc);
    }

    public function approveDocument(Request $request, string $doc)
    {
        return $this->back($this->providers->approveDocument($request, $doc), 'Document approved.');
    }

    public function rejectDocument(Request $request, string $doc)
    {
        return $this->back($this->providers->rejectDocument($request, $doc), 'Document rejected.');
    }

    public function decideApplication(Request $request, string $operator)
    {
        $action = $request->validate(['action' => 'required|in:approve,request_changes,reject', 'note' => 'nullable|string|max:500'])['action'];
        abort_unless($request->user()->can($action === 'approve' ? 'Approve kyc' : 'Reject kyc'), 403);
        $res = match ($action) {
            'approve' => $this->providers->approve($request, $operator),
            'request_changes' => $this->providers->requestChanges($request, $operator),
            'reject' => $this->providers->reject($request, $operator),
        };

        return $this->back($res, ['approve' => 'Application approved. The provider is now live.', 'request_changes' => 'Sent back for changes.', 'reject' => 'Application rejected.'][$action]);
    }

    // ---------------------------------------------------------------- money

    public function refunds(Request $request)
    {
        return view('ops.refunds', ['rows' => $this->ops->refunds($request)->getData(true), 'status' => $request->query('status', '')]);
    }

    public function settlements(Request $request)
    {
        return view('ops.settlements', ['rows' => $this->ops->settlements($request)->getData(true), 'status' => $request->query('status', '')]);
    }

    public function approveSettlement(string $settlement, SettlementService $svc)
    {
        $this->ops->approveSettlement($settlement, $svc);

        return back()->with('success', 'Settlement approved.');
    }

    public function runSettlements(SettlementService $svc)
    {
        $r = $this->ops->runSettlements($svc)->getData(true);

        return back()->with('success', "Draft statements built for {$r['providers']} provider(s), {$r['period'][0]} to {$r['period'][1]}.");
    }

    // ---------------------------------------------------------------- providers

    public function providersList(Request $request)
    {
        $this->needAny($request, ['View vendor', 'View driver', 'View shopper']);

        return view('ops.providers', ['rows' => $this->ops->providers($request)->getData(true), 'f' => $request->only('q', 'status')]);
    }

    public function refreshScore(Request $request, string $operator, ProviderScoreService $scores)
    {
        $this->needAny($request, ['View vendor', 'View driver', 'View shopper']);
        $this->ops->refreshProvider($operator, $scores);

        return back()->with('success', 'Score recalculated.');
    }

    public function suspend(Request $request, string $operator)
    {
        $this->needAny($request, ['Suspend vendor', 'Suspend driver', 'Suspend shopper']);

        return $this->back($this->providers->suspend($request, $operator), 'Provider suspended.');
    }

    public function reinstate(Request $request, string $operator)
    {
        $this->needAny($request, ['Suspend vendor', 'Suspend driver', 'Suspend shopper']);

        return $this->back($this->providers->reinstate($operator), 'Provider reinstated.');
    }

    // ---------------------------------------------------------------- ratings and risk

    public function ratings(Request $request)
    {
        return view('ops.ratings', ['rows' => $this->ops->ratings($request)->getData(true), 'f' => $request->only('max_score', 'status')]);
    }

    public function moderate(Request $request, int $rating, ProviderScoreService $scores)
    {
        $this->ops->moderateRating($request, $rating, $scores);

        return back()->with('success', 'Rating updated.');
    }

    public function risk(Request $request)
    {
        return view('ops.risk', ['rows' => $this->ops->riskEvents($request)->getData(true), 'status' => $request->query('status', 'open')]);
    }

    public function reviewRisk(Request $request, int $event)
    {
        $this->ops->reviewRiskEvent($request, $event);

        return back()->with('success', 'Marked.');
    }

    /** Spatie's route middleware cannot express "any of these", so the controller checks. */
    private function needAny(Request $request, array $permissions): void
    {
        abort_unless($request->user()->hasAnyPermission($permissions), 403);
    }

    // ----------------------------------------------------------------

    /** Turns a JSON result from the shared code into a redirect with a flash message. */
    private function back(JsonResponse $res, string $ok, ?string $to = null)
    {
        if ($res->getStatusCode() >= 400) {
            $msg = $res->getData(true)['message'] ?? 'That could not be done.';

            return back()->withInput()->with('error', $msg);
        }

        return ($to ? redirect($to) : back())->with('success', $ok);
    }
}
