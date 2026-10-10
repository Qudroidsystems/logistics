<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Api\Provider\ProviderPayoutController;
use App\Http\Controllers\Api\Provider\TeamController;
use App\Http\Controllers\Controller;
use App\Modules\Dispatch\ManualAssignmentService;
use App\Modules\Marketplace\CancellationService;
use App\Modules\Payments\BankAccountService;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Providers\ProviderOnboardingService;
use App\Modules\Settlements\PayoutService;
use App\Modules\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The provider's own workspace in the browser: dashboard, jobs, wallet and payouts, team.
 * Pages call the same code as the provider JSON API, so a rule lives in one place; roles decide what each
 * team member may see, the same as in the API.
 */
class WorkspaceController extends Controller
{
    /** Team roles allowed on each area. */
    protected const AREAS = [
        'jobs' => ['owner', 'admin', 'dispatcher'],
        'requests' => ['owner', 'admin', 'dispatcher'],
        'money' => ['owner', 'admin', 'finance'],
        'team' => ['owner', 'admin', 'driver_manager'],
    ];

    public const BANKS = [
        '044' => 'Access Bank', '058' => 'GTBank', '011' => 'First Bank', '033' => 'UBA', '057' => 'Zenith Bank', '070' => 'Fidelity Bank',
        '214' => 'FCMB', '232' => 'Sterling Bank', '032' => 'Union Bank', '035' => 'Wema Bank', '076' => 'Polaris Bank', '221' => 'Stanbic IBTC',
        '050' => 'Ecobank', '082' => 'Keystone Bank', '50211' => 'Kuda', '50515' => 'Moniepoint', '999992' => 'OPay', '999991' => 'PalmPay',
    ];

    public function __construct(private ProviderPayoutController $payouts, private TeamController $team)
    {
    }

    // ---------------------------------------------------------------- context

    /** @return array{0:object,1:string} the operator the user is acting for and their role there. */
    protected function ctx(Request $request, ?string $area = null): array
    {
        $user = $request->user();
        $opId = (int) $user->current_operator_id;
        $m = $opId ? DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')
            ->where(['m.operator_id' => $opId, 'm.user_id' => $user->id, 'm.status' => 'active'])->whereIn('o.type', ProviderOnboardingService::TYPES)
            ->first(['o.id', 'o.public_id', 'o.type', 'o.display_name', 'o.status', 'm.role']) : null;
        abort_unless($m, 403, 'This account is not linked to a provider.');
        if ($area) {
            abort_unless(in_array($m->role, self::AREAS[$area], true), 403, 'Your role does not include this page.');
        }

        return [$m, $m->role];
    }

    protected function isProvider(Request $request): bool
    {
        return (int) $request->user()->current_operator_id > 0 && DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')
            ->where(['m.operator_id' => $request->user()->current_operator_id, 'm.user_id' => $request->user()->id, 'm.status' => 'active'])
            ->whereIn('o.type', ProviderOnboardingService::TYPES)->exists();
    }

    /** Which sidebar items this role sees, shared with every provider view. */
    protected function view(string $name, Request $request, array $data = [], ?string $area = null)
    {
        [$op, $role] = $this->ctx($request, $area);
        $nav = array_map(fn ($roles) => in_array($role, $roles, true), self::AREAS);

        return view($name, $data + ['op' => $op, 'role' => $role, 'nav' => $nav, 'pagetitle' => $data['pagetitle'] ?? 'Provider workspace']);
    }

    // ---------------------------------------------------------------- dashboard

    public function dashboard(Request $request, ProviderOnboardingService $onboarding, AccountResolver $accounts)
    {
        if (! $this->isProvider($request)) {
            return redirect()->route('provider.start');
        }
        [$op] = $this->ctx($request);
        $profile = DB::table('provider_profiles')->where('operator_id', $op->id)->first();
        $score = DB::table('provider_scores')->where('operator_id', $op->id)->first();
        $app = DB::table('provider_applications')->where('operator_id', $op->id)->first(['status', 'review_note']);

        return $this->view('provider.dashboard', $request, [
            'profile' => $profile, 'score' => $score, 'app' => $app,
            'missing' => in_array($app->status ?? 'draft', ['draft', 'needs_changes'], true) ? $onboarding->missing($op->id) : [],
            'balance' => (int) DB::table('ledger_accounts')->where('id', $accounts->wallet('operator', $op->id, $op->id))->value('balance'),
            'active' => DB::table('shipments')->where('operator_id', $op->id)->whereIn('status', ['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'])->count(),
            'done' => DB::table('shipments')->where('operator_id', $op->id)->whereIn('status', ['delivered', 'completed'])->count(),
            'pagetitle' => 'Dashboard',
        ]);
    }

    // ---------------------------------------------------------------- jobs

    public function jobs(Request $request)
    {
        [$op] = $this->ctx($request, 'jobs');
        $status = $request->query('status', 'active');
        $rows = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.operator_id', $op->id)
            ->when($status === 'active', fn ($q) => $q->whereNotIn('s.status', ['delivered', 'completed', 'cancelled']))
            ->when(! in_array($status, ['active', 'all'], true), fn ($q) => $q->where('s.status', $status))
            ->orderByDesc('s.id')->limit(100)->get(['s.public_id', 's.status', 's.tracking_code', 'o.order_number', 'o.total', 's.created_at']);

        return $this->view('provider.jobs', $request, ['rows' => $rows, 'status' => $status, 'pagetitle' => 'Jobs'], 'jobs');
    }

    public function job(Request $request, string $shipment)
    {
        [$op] = $this->ctx($request, 'jobs');
        $s = DB::table('shipments')->where('public_id', $shipment)->where('operator_id', $op->id)->first();
        abort_unless($s, 404);

        return $this->view('provider.job', $request, [
            's' => $s, 'order' => DB::table('orders')->where('id', $s->order_id)->first(['order_number', 'total', 'payment_status']),
            'timeline' => DB::table('shipment_events')->where('shipment_id', $s->id)->orderBy('seq')->get(['seq', 'type', 'to_status', 'actor_type', 'created_at']),
            'cancellable' => ! in_array($s->status, ['picked_up', 'in_transit', 'at_dropoff', 'delivered', 'completed', 'cancelled'], true),
            'assignable' => ManualAssignmentService::canAssign($s->status),
            'drivers' => ManualAssignmentService::canAssign($s->status) ? app(ManualAssignmentService::class)->drivers($op->id) : [],
            'driver' => DB::table('assignments as a')->join('driver_profiles as d', 'd.id', '=', 'a.driver_profile_id')->join('users as u', 'u.id', '=', 'd.user_id')
                ->where('a.shipment_id', $s->id)->whereIn('a.status', ['assigned', 'accepted', 'en_route', 'active'])->first(['u.id as user_id', 'u.name', 'a.status']),
            'proofs' => DB::table('proofs')->where('shipment_id', $s->id)->orderBy('id')->get(['public_id', 'type', 'file_path', 'otp_verified', 'recipient_name', 'created_at']),
            'terms' => $this->failedTerms((int) $s->order_id),
            'packages' => app(\App\Modules\Tracking\ParcelCodes::class)->forShipment((int) $s->id),
            'pagetitle' => 'Job',
        ], 'jobs');
    }

    /** The agreement's failed-delivery terms in plain sentences, for the job page. @return string[] */
    private function failedTerms(int $orderId): array
    {
        $json = DB::table('orders as o')->join('agreements as a', 'a.id', '=', 'o.agreement_id')->where('o.id', $orderId)->value('a.failed_delivery_policy');

        return \App\Modules\Marketplace\FailedDeliveryPolicy::describe($json ? json_decode($json, true) : null);
    }

    // ---------------------------------------------------------------- dispatch board

    /** One screen for the dispatcher: paid jobs still waiting for a driver, who is free, and what is on the road. */
    public function board(Request $request)
    {
        [$op] = $this->ctx($request, 'jobs');

        $base = fn () => DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.operator_id', $op->id)
            ->select('s.id', 's.public_id', 's.status', 's.needs_manual_dispatch', 's.updated_at', 'o.order_number', 'o.total');

        $waiting = $base()->where('o.payment_status', 'paid')->whereIn('s.status', ManualAssignmentService::UNASSIGNED)
            ->orderByDesc('s.needs_manual_dispatch')->orderBy('s.updated_at')->limit(100)->get();
        $moving = $base()->whereIn('s.status', ['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'])
            ->orderBy('s.updated_at')->limit(100)->get();

        $ids = $waiting->pluck('id')->merge($moving->pluck('id'))->all();
        $stops = DB::table('shipment_stops')->whereIn('shipment_id', $ids)->orderBy('seq')->get(['shipment_id', 'type', 'line1'])->groupBy('shipment_id');
        $drivers = DB::table('assignments as a')->join('driver_profiles as d', 'd.id', '=', 'a.driver_profile_id')->join('users as u', 'u.id', '=', 'd.user_id')
            ->whereIn('a.shipment_id', $moving->pluck('id'))->whereIn('a.status', ['assigned', 'accepted', 'en_route', 'active'])->pluck('u.name', 'a.shipment_id');
        $issues = DB::table('shipment_events')->whereIn('shipment_id', $moving->pluck('id'))->where('type', 'driver_issue')->where('created_at', '>', now()->subDay())
            ->selectRaw('shipment_id, count(*) as n')->groupBy('shipment_id')->pluck('n', 'shipment_id');
        $line = fn ($id, $type) => optional(collect($stops[$id] ?? [])->firstWhere('type', $type))->line1
            ?? ($type === 'dropoff' ? optional(collect($stops[$id] ?? [])->last())->line1 : null);

        return $this->view('provider.board', $request, [
            'waiting' => $waiting, 'moving' => $moving, 'driverOf' => $drivers, 'issues' => $issues, 'line' => $line,
            'drivers' => app(ManualAssignmentService::class)->drivers($op->id), 'pagetitle' => 'Dispatch board',
        ], 'jobs');
    }

    public function assignJob(Request $request, string $shipment)
    {
        $this->ctx($request, 'jobs');

        return $this->back(app(\App\Http\Controllers\Api\Provider\DriverAssignmentController::class)->assign($request, $shipment), 'Driver assigned.');
    }

    public function cancelJob(Request $request, string $shipment, CancellationService $svc)
    {
        $this->ctx($request, 'jobs');

        return $this->back($this->payouts->cancelShipment($request, $shipment, $svc), 'Job cancelled. The customer has been refunded.', route('provider.jobs'));
    }

    // ---------------------------------------------------------------- wallet

    public function wallet(Request $request, AccountResolver $accounts)
    {
        [$op] = $this->ctx($request, 'money');

        return $this->view('provider.wallet', $request, [
            'w' => $this->payouts->wallet($request, $accounts)->getData(true),
            'payouts' => $this->payouts->payouts($request)->getData(true),
            'banks' => $this->payouts->bankAccounts($request)->getData(true),
            'bankList' => app(\App\Modules\Payments\BankDirectory::class)->all(),
            'pagetitle' => 'Wallet',
        ], 'money');
    }

    public function requestPayout(Request $request, PayoutService $svc)
    {
        $this->ctx($request, 'money');
        $request->validate(['amount_naira' => 'required|numeric|min:1', 'bank_account_id' => 'required|string']);
        $request->merge(['amount' => (int) round(((float) $request->input('amount_naira')) * 100)]);

        return $this->back($this->payouts->request($request, $svc), 'Payout requested. Staff will approve it shortly.');
    }

    public function addBank(Request $request, BankAccountService $banks)
    {
        $this->ctx($request, 'money');
        $d = $request->validate(['bank_code' => 'required|string|max:12', 'account_number' => 'required|digits:10']);
        $request->merge(['bank_name' => app(\App\Modules\Payments\BankDirectory::class)->name($d['bank_code'])]);

        return $this->back($this->payouts->addBankAccount($request, $banks), 'Bank account added.');
    }

    // ---------------------------------------------------------------- team

    public function team(Request $request, TeamService $svc)
    {
        [$op, $role] = $this->ctx($request, 'team');

        return $this->view('provider.team', $request, [
            'members' => $svc->members($op->id), 'invites' => $svc->invitations($op->id),
            'manageable' => TeamService::manageable($role),
            'vehicles' => DB::table('vehicles')->where('operator_id', $op->id)->whereNull('deleted_at')->get(['id', 'public_id', 'plate']),
            'me' => $request->user()->id, 'pagetitle' => 'Team',
        ], 'team');
    }

    public function invite(Request $request)
    {
        $this->ctx($request, 'team');

        return $this->back($this->team->invite($request), 'Invitation sent.');
    }

    public function revokeInvite(Request $request, string $invitation)
    {
        $this->ctx($request, 'team');

        return $this->back($this->team->revoke($request, $invitation), 'Invitation withdrawn.');
    }

    public function changeRole(Request $request, int $user)
    {
        $this->ctx($request, 'team');

        return $this->back($this->team->changeRole($request, $user), 'Role changed.');
    }

    public function removeMember(Request $request, int $user)
    {
        $this->ctx($request, 'team');

        return $this->back($this->team->remove($request, $user), 'Removed from the team.');
    }

    public function driverStatus(Request $request, int $user)
    {
        $this->ctx($request, 'team');

        return $this->back($this->team->driverStatus($request, $user), 'Driver updated.');
    }

    public function driverPay(Request $request, int $user)
    {
        $this->ctx($request, 'team');

        return $this->back($this->team->driverPay($request, $user), 'Driver pay updated.');
    }

    public function assignVehicle(Request $request, int $user)
    {
        $this->ctx($request, 'team');

        return $this->back($this->team->assignVehicle($request, $user), 'Vehicle assigned.');
    }

    // ----------------------------------------------------------------

    protected function back(JsonResponse $res, string $ok, ?string $to = null)
    {
        if ($res->getStatusCode() >= 400) {
            $data = $res->getData(true);
            $msg = $data['message'] ?? 'That could not be done.';

            return back()->withInput()->with('error', $msg);
        }

        return ($to ? redirect($to) : back())->with('success', $ok);
    }
}
