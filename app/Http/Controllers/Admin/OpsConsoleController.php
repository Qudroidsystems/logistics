<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\Admin\AdminOpsController;
use App\Http\Controllers\Api\Admin\AdminProviderController;
use App\Http\Controllers\Controller;
use App\Modules\Marketplace\DisputeService;
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
