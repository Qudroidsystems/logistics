<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Verification\CustodyLog;
use App\Modules\Verification\ExceptionService;
use App\Modules\Verification\VerificationPolicy;
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
        'marketing' => ['owner', 'admin'],
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
        // Someone who also runs a shop (or switched elsewhere) lands on their provider business, not a 403.
        $opId = (int) (\App\Support\Workspaces::ensureProvider($user) ?? $user->current_operator_id);
        $m = $opId ? DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')
            ->where(['m.operator_id' => $opId, 'm.user_id' => $user->id, 'm.status' => 'active'])->whereIn('o.type', \App\Support\Workspaces::providerTypes())
            ->first(['o.id', 'o.public_id', 'o.type', 'o.display_name', 'o.status', 'm.role']) : null;
        abort_unless($m, 403, 'This account is not linked to a provider.');
        if ($area) {
            abort_unless(in_array($m->role, self::AREAS[$area], true), 403, 'Your role does not include this page.');
        }

        return [$m, $m->role];
    }

    protected function isProvider(Request $request): bool
    {
        return \App\Support\Workspaces::ensureProvider($request->user()) !== null;
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
            'today' => $this->today((int) $op->id),
            'recentJobs' => DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->leftJoin('merchants as mc', 'mc.id', '=', 'o.merchant_id')
                ->leftJoin('assignments as a', fn ($j) => $j->on('a.shipment_id', '=', 's.id')->whereIn('a.status', ['accepted', 'en_route', 'active', 'completed']))
                ->leftJoin('driver_profiles as dp', 'dp.id', '=', 'a.driver_profile_id')->leftJoin('users as du', 'du.id', '=', 'dp.user_id')
                ->where('s.operator_id', $op->id)->orderByDesc('s.id')->limit(8)
                ->get(['s.public_id', 's.tracking_code', 's.status', 's.created_at', 'o.order_number', 'o.delivery_fee', 'mc.display_name as shop', 'du.name as rider']),
            'commission' => $this->commissionFor($op),
            'pagetitle' => 'Dashboard',
        ]);
    }

    /** Today's work for the dashboard: what is waiting, who is out, money in and out. */
    private function today(int $opId): array
    {
        $start = now()->startOfDay();
        $ship = fn () => DB::table('shipments')->where('operator_id', $opId);

        return [
            'waiting' => $ship()->whereIn('status', ['awaiting_dispatch', 'offered'])->count(),
            'waiting_long' => $ship()->whereIn('status', ['awaiting_dispatch', 'offered'])->where('updated_at', '<', now()->subMinutes(15))->count(),
            'manual' => $ship()->where('needs_manual_dispatch', true)->whereIn('status', ['awaiting_dispatch', 'offered'])->count(),
            'late' => $ship()->whereIn('status', ['assigned', 'heading_to_pickup', 'at_pickup', 'picked_up', 'in_transit', 'at_dropoff'])->whereNotNull('deliver_by')->where('deliver_by', '<', now())->count(),
            'new_today' => $ship()->where('created_at', '>=', $start)->count(),
            'delivered_today' => $ship()->where('status', 'delivered')->where('updated_at', '>=', $start)->count(),
            'shop_jobs_today' => DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.operator_id', $opId)
                ->whereNotNull('o.merchant_id')->where('s.created_at', '>=', $start)->count(),
            'riders_online' => DB::table('driver_profiles')->where('operator_id', $opId)->where('status', 'active')->whereIn('availability', ['online', 'on_job'])
                ->whereNull('deleted_at')->where('last_seen_at', '>=', now()->subMinutes(15))->count(),
            'riders' => DB::table('driver_profiles')->where('operator_id', $opId)->where('status', 'active')->whereNull('deleted_at')->count(),
            'requests' => DB::table('request_invitations as i')->join('service_requests as r', 'r.id', '=', 'i.request_id')
                ->where('i.operator_id', $opId)->where('i.status', 'sent')->whereIn('r.status', ['open', 'negotiating'])->count(),
            'exceptions' => DB::table('delivery_exceptions as e')->join('shipments as s', 's.id', '=', 'e.shipment_id')
                ->where('s.operator_id', $opId)->whereNull('e.resolved_at')->count(),
            'earned_today' => (int) DB::table('commissions')->where('operator_id', $opId)->where('created_at', '>=', $start)->sum('operator_net'),
            'payouts_waiting' => (int) DB::table('payout_requests')->where('operator_id', $opId)->whereIn('status', ['requested', 'approved', 'processing'])->sum('amount'),
            'docs_expiring' => DB::table('kyc_documents')->where('operator_id', $opId)->where('status', 'approved')->whereNotNull('expires_on')
                ->where('expires_on', '<=', now()->addDays(30)->toDateString())->count(),
        ];
    }

    /** The platform fee that applies to this provider's jobs now (special fee, fee for its kind, or the platform fee). */
    private function commissionFor(object $op): ?array
    {
        $type = (string) DB::table('operators')->where('id', $op->id)->value('type');
        $r = app(\App\Modules\Marketplace\CommissionResolver::class)->resolve((int) $op->id, $type, 0, null, 1_000_000);
        $row = $r['rule_id'] ? DB::table('commission_rules')->find($r['rule_id']) : null;

        return $row ? ['percent' => rtrim(rtrim(number_format($row->rate_bp / 100, 2), '0'), '.'), 'min' => (int) $row->min_fee, 'special' => (bool) $row->operator_id] : null;
    }

    /** Weekly statements from the platform: sales, commission taken and what was paid in; plus the fee on each recent job. */
    public function statements(Request $request)
    {
        [$op] = $this->ctx($request, 'money');

        return $this->view('provider.statements', $request, [
            'statements' => DB::table('settlements')->where('operator_id', $op->id)->orderByDesc('period_end')->limit(52)->get(),
            'jobs' => DB::table('commissions as c')->join('shipments as s', 's.id', '=', 'c.shipment_id')->join('orders as o', 'o.id', '=', 's.order_id')
                ->where('c.operator_id', $op->id)->orderByDesc('c.id')->limit(50)
                ->get(['c.gross_amount', 'c.platform_fee', 'c.operator_net', 'c.driver_net', 'c.status', 'c.created_at', 's.tracking_code', 'o.order_number']),
            'month' => DB::table('commissions')->where('operator_id', $op->id)->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('COUNT(*) n, COALESCE(SUM(gross_amount),0) gross, COALESCE(SUM(platform_fee),0) fee, COALESCE(SUM(operator_net),0) net')->first(),
            'commission' => $this->commissionFor($op),
            'pagetitle' => 'Statements',
        ], 'money');
    }

    /** What customers said about the business. */
    public function reviews(Request $request)
    {
        [$op] = $this->ctx($request);
        $rows = DB::table('ratings as r')->join('shipments as s', 's.id', '=', 'r.shipment_id')->join('orders as o', 'o.id', '=', 's.order_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.rater_id')
            ->where('r.ratee_type', 'operator')->where('r.ratee_id', $op->id)->orderByDesc('r.id')->limit(100)
            ->get(['r.score', 'r.tags', 'r.comment', 'r.moderation_status', 'r.created_at', 'o.order_number', 'u.name']);
        $sum = DB::table('ratings')->where('ratee_type', 'operator')->where('ratee_id', $op->id)->where('moderation_status', 'approved')
            ->selectRaw('COUNT(*) n, AVG(score) avg')->selectRaw('COUNT(*) FILTER (WHERE score >= 4) good')->first();
        $spread = DB::table('ratings')->where('ratee_type', 'operator')->where('ratee_id', $op->id)->where('moderation_status', 'approved')
            ->groupBy('score')->selectRaw('score, COUNT(*) AS n')->pluck('n', 'score');

        return $this->view('provider.reviews', $request, ['rows' => $rows, 'sum' => $sum, 'spread' => $spread, 'pagetitle' => 'Reviews']);
    }

    // ---------------------------------------------------------------- jobs

    public function jobs(Request $request)
    {
        [$op] = $this->ctx($request, 'jobs');
        $status = $request->query('status', 'active');
        $search = trim((string) $request->query('q', ''));
        $rows = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->leftJoin('users as c', 'c.id', '=', 'o.customer_id')->where('s.operator_id', $op->id)
            ->when($status === 'active', fn ($q) => $q->whereNotIn('s.status', ['delivered', 'completed', 'cancelled']))
            ->when(! in_array($status, ['active', 'all'], true), fn ($q) => $q->where('s.status', $status))
            // Order number, tracking code, customer, or any stop's address, contact name or phone.
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $digits = preg_replace('/\D+/', '', $search);
                $q->where(fn ($w) => $w->whereRaw('LOWER(o.order_number) LIKE ?', [$like])->orWhereRaw('LOWER(s.tracking_code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(c.name) LIKE ?', [$like])->orWhereRaw('LOWER(c.email) LIKE ?', [$like])
                    ->orWhereExists(fn ($e) => $e->select(DB::raw(1))->from('shipment_stops as st')->whereColumn('st.shipment_id', 's.id')
                        ->where(fn ($x) => $x->whereRaw('LOWER(st.line1) LIKE ?', [$like])->orWhereRaw('LOWER(st.contact_name) LIKE ?', [$like])
                            ->when(strlen($digits) >= 4, fn ($p) => $p->orWhere('st.contact_phone', 'like', '%'.substr($digits, -10).'%')))));
            })
            ->orderByDesc('s.id')->limit($search !== '' ? 200 : 100)->get(['s.public_id', 's.status', 's.tracking_code', 'o.order_number', 'o.total', 's.created_at', 'c.name as customer']);

        return $this->view('provider.jobs', $request, ['rows' => $rows, 'status' => $status, 'search' => $search, 'pagetitle' => 'Jobs'], 'jobs');
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
            'proofs' => DB::table('proofs')->where('shipment_id', $s->id)->orderBy('id')->get(['public_id', 'type', 'file_path', 'signature_path', 'otp_verified', 'recipient_name', 'id_type', 'id_last4', 'checks', 'created_at']),
            'terms' => $this->failedTerms((int) $s->order_id),
            'agreedProofs' => app(VerificationPolicy::class)->forShipment((int) $s->id),
            'exceptions' => app(ExceptionService::class)->forShipment((int) $s->id),
            'custody' => app(CustodyLog::class)->verify((int) $s->id),
            'custodyEvents' => app(CustodyLog::class)->events((int) $s->id),
            'chat' => app(\App\Modules\Messaging\ChatService::class)->forStaff((int) $s->id),
            'calls' => \Illuminate\Support\Facades\DB::table('call_logs')->where('shipment_id', $s->id)->orderBy('id')->get(['purpose', 'outcome', 'duration_s', 'created_at']),
            'alertsSent' => app(\App\Modules\Eta\ArrivalAlerts::class)->forShipment((int) $s->id),
            'eta' => app(\App\Modules\Eta\EtaService::class)->forShipment((int) $s->id),
            'receiverReply' => \App\Modules\Eta\ReceiverReplies::current(DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->orderByDesc('seq')->first()),
            'packages' => app(\App\Modules\Tracking\ParcelCodes::class)->forShipment((int) $s->id),
            'handover' => app(\App\Modules\Returns\PickupPointService::class)->forJob($s),
            'returnInfo' => app(\App\Modules\Returns\ReturnService::class)->forJob($s),
            'returnPoints' => array_filter(app(\App\Modules\Returns\PickupPointService::class)->all((int) $op->id), fn ($p) => $p->status === 'active'),
            'pagetitle' => 'Job',
        ], 'jobs');
    }

    /** A dispatcher approves or declines a driver's request to skip one of the agreed proofs. */
    public function decideException(Request $request, string $shipment, string $exception, ExceptionService $exceptions)
    {
        [$op] = $this->ctx($request, 'jobs');
        $d = $request->validate(['decision' => 'required|in:approve,decline', 'note' => 'nullable|string|max:255']);
        try {
            $exceptions->decide($op->id, $exception, $d['decision'] === 'approve', $request->user()->id, $d['note'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $d['decision'] === 'approve' ? 'Approved. The driver can finish without that proof.' : 'Declined. The driver has been told.');
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
        $live = DB::table('assignments as a')->join('driver_profiles as d', 'd.id', '=', 'a.driver_profile_id')->join('users as u', 'u.id', '=', 'd.user_id')
            ->whereIn('a.shipment_id', $moving->pluck('id'))->whereIn('a.status', ['assigned', 'accepted', 'en_route', 'active'])->get(['a.shipment_id', 'u.id as user_id', 'u.name']);
        $drivers = $live->pluck('name', 'shipment_id');
        $issues = DB::table('shipment_events')->whereIn('shipment_id', $moving->pluck('id'))->where('type', 'driver_issue')->where('created_at', '>', now()->subDay())
            ->selectRaw('shipment_id, count(*) as n')->groupBy('shipment_id')->pluck('n', 'shipment_id');
        $line = fn ($id, $type) => optional(collect($stops[$id] ?? [])->firstWhere('type', $type))->line1
            ?? ($type === 'dropoff' ? optional(collect($stops[$id] ?? [])->last())->line1 : null);

        return $this->view('provider.board', $request, [
            'waiting' => $waiting, 'moving' => $moving, 'driverOf' => $drivers, 'issues' => $issues, 'line' => $line,
            'lanes' => $live->groupBy('user_id')->map(fn ($rows) => $rows->pluck('shipment_id')->all())->all(),
            'drivers' => app(ManualAssignmentService::class)->drivers($op->id), 'pagetitle' => 'Dispatch board',
        ], 'jobs');
    }

    public function assignJob(Request $request, string $shipment)
    {
        $this->ctx($request, 'jobs');
        $res = app(\App\Http\Controllers\Api\Provider\DriverAssignmentController::class)->assign($request, $shipment);

        // The drag-and-drop board posts in the background and wants the answer, not a page.
        return $request->expectsJson() ? $res : $this->back($res, 'Driver assigned.');
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

    /** Change a rider's @handle (customers can ask for a rider by it). */
    public function driverHandle(Request $request, int $user, \App\Modules\Directory\DirectoryService $dir)
    {
        [$op] = $this->ctx($request, 'team');
        $request->validate(['handle' => 'required|string|max:31']);
        $d = DB::table('driver_profiles')->where('operator_id', $op->id)->where('user_id', $user)->whereNull('deleted_at')->first(['id']);
        abort_unless($d, 404);
        try {
            $h = $dir->validate((string) $request->input('handle'), (int) $d->id);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
        DB::table('driver_profiles')->where('id', $d->id)->update(['handle' => $h, 'updated_at' => now()]);

        return back()->with('success', "Rider handle saved: @{$h}.");
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
