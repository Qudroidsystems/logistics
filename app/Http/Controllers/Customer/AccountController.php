<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Api\Customer\CancellationController;
use App\Http\Controllers\Api\Customer\CustomerWalletController;
use App\Http\Controllers\Api\Customer\DeliveryController;
use App\Http\Controllers\Api\Marketplace\NegotiationController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\WorkspaceController;
use App\Modules\Marketplace\CancellationService;
use App\Modules\Marketplace\DeliveryService;
use App\Modules\Marketplace\NegotiationService;
use App\Modules\Payments\BankAccountService;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\PaymentService;
use App\Modules\Payments\WalletService;
use App\Modules\Ratings\RatingService;
use App\Modules\Settlements\PayoutService;
use App\Modules\Tracking\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The customer's own area in the browser: ask for a delivery, compare and negotiate offers, pay, follow the order,
 * confirm or report a problem, and manage the wallet. Pages call the same code as the customer JSON API, so every
 * rule lives in one place. Money amounts are typed in naira and converted to whole kobo here.
 */
class AccountController extends Controller
{
    public function __construct(private NegotiationController $neg, private CustomerWalletController $wallet)
    {
    }

    private function view(string $name, array $data = [])
    {
        return view($name, $data + ['pagetitle' => 'My account']);
    }

    private static function kobo($naira): ?int
    {
        return ($naira === null || $naira === '') ? null : (int) round(((float) $naira) * 100);
    }

    /** Turns a JSON result from the shared code into a redirect with a flash message (or the data, for the caller). */
    private function done(JsonResponse $res, string $ok, ?string $to = null)
    {
        if ($res->getStatusCode() >= 400) {
            return back()->withInput()->with('error', $res->getData(true)['message'] ?? 'That could not be done.');
        }

        return ($to ? redirect($to) : back())->with('success', $ok);
    }

    // ---------------------------------------------------------------- dashboard

    public function dashboard(Request $request, WalletService $wallet)
    {
        $uid = $request->user()->id;

        return $this->view('account.dashboard', [
            'balance' => $wallet->balance($uid),
            'toPay' => DB::table('agreements as a')->join('operators as o', 'o.id', '=', 'a.provider_operator_id')->where('a.customer_id', $uid)->where('a.status', 'locked')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('orders')->whereColumn('orders.agreement_id', 'a.id'))
                ->orderByDesc('a.id')->limit(5)->get(['a.public_id', 'a.number', 'a.price', 'o.display_name as provider']),
            'openRequests' => DB::table('service_requests')->where('customer_id', $uid)->whereIn('status', ['open', 'negotiating'])->count(),
            'active' => $this->orderQuery($uid)->whereNotIn('s.status', ['delivered', 'completed', 'cancelled', 'confirmed'])->limit(5)->get(),
            'toConfirm' => $this->orderQuery($uid)->where('s.status', 'delivered')->limit(5)->get(),
            'pagetitle' => 'My account',
        ]);
    }

    // ---------------------------------------------------------------- finding a provider

    public function providers(Request $request)
    {
        $serviceTypes = DB::table('service_types')->where('active', true)->orderBy('id')->get(['id', 'name']);
        $sid = (int) $request->query('service_type_id', $serviceTypes->first()->id ?? 0);
        $rows = $sid ? $this->neg->providers($request->merge(['service_type_id' => $sid]))->getData(true) : [];

        return $this->view('account.providers', ['rows' => $rows, 'serviceTypes' => $serviceTypes, 'sid' => $sid, 'pagetitle' => 'Find a provider']);
    }

    // ---------------------------------------------------------------- requests

    public function requests(Request $request)
    {
        return $this->view('account.requests', ['rows' => $this->neg->myRequests($request)->getData(true), 'pagetitle' => 'My requests']);
    }

    public function newRequest(Request $request)
    {
        $provider = (int) $request->query('provider', 0);

        return $this->view('account.request-new', [
            'serviceTypes' => DB::table('service_types')->where('active', true)->orderBy('id')->get(['id', 'name']),
            'cities' => DB::table('cities')->orderBy('name')->selectRaw('id, name, ST_Y(centre::geometry) AS lat, ST_X(centre::geometry) AS lng')->get(),
            'vehicleTypes' => DB::table('vehicle_types')->where('active', true)->orderBy('id')->get(['id', 'name']),
            'provider' => $provider ? DB::table('operators')->where('id', $provider)->first(['id', 'display_name']) : null,
            'pagetitle' => 'Ask for a delivery',
        ]);
    }

    public function createRequest(Request $request)
    {
        $items = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $request->input('items')))));
        $in = [
            'type' => $request->input('type'), 'service_type_id' => $request->input('service_type_id'), 'city_id' => $request->input('city_id'),
            'vehicle_type_id' => $request->input('vehicle_type_id') ?: null,
            'pickup' => $request->input('pickup', []), 'dropoff' => $request->input('dropoff', []),
            'packages' => array_map(fn ($d) => ['description' => $d], $request->input('type') === 'shopping' ? [] : $items),
            'budget_min' => self::kobo($request->input('budget_min_naira')), 'budget_max' => self::kobo($request->input('budget_max_naira')),
            'needed_by' => $request->input('needed_by') ?: null,
            'visibility' => $request->filled('provider') ? 'direct' : 'open', 'operator_ids' => $request->filled('provider') ? [(int) $request->input('provider')] : null,
        ];
        if ($request->input('type') === 'shopping') {
            $in['errand'] = ['list' => $items, 'budget_cap' => self::kobo($request->input('budget_cap_naira')), 'substitution_policy' => $request->input('substitution_policy', 'ask')];
        }
        $res = $this->neg->createRequest($request->replace(array_filter($in, fn ($v) => $v !== null)), app(NegotiationService::class));
        if ($res->getStatusCode() >= 400) {
            return back()->withInput()->with('error', $res->getData(true)['message'] ?? 'That could not be done.');
        }

        return redirect()->route('account.request', $res->getData(true)['request_id'])->with('success', 'Request sent. Offers will appear here as providers reply.');
    }

    public function request(Request $request, string $serviceRequest)
    {
        $req = DB::table('service_requests')->where('public_id', $serviceRequest)->where('customer_id', $request->user()->id)->first();
        abort_unless($req, 404);

        return $this->view('account.request', [
            'req' => $req, 'stops' => json_decode($req->stops, true),
            'offers' => $this->neg->offers($request, $serviceRequest)->getData(true),
            'pagetitle' => 'Request',
        ]);
    }

    // ---------------------------------------------------------------- negotiation

    public function thread(Request $request, string $thread)
    {
        $t = DB::table('negotiation_threads as t')->join('operators as o', 'o.id', '=', 't.operator_id')->join('service_requests as r', 'r.id', '=', 't.request_id')
            ->where('t.public_id', $thread)->where('t.customer_id', $request->user()->id)->first(['t.public_id', 't.status', 'o.display_name as provider', 'r.public_id as request', 'r.type', 'r.distance_m']);
        abort_unless($t, 404);
        $data = $this->neg->customerThread($request, $thread)->getData(true);
        $messages = array_map(fn ($m) => $m + ['offer' => is_string($m['terms'] ?? null) ? json_decode($m['terms'], true) : ($m['terms'] ?? null)], $data['messages']);
        $latest = collect($messages)->where('kind', 'counter_offer')->last();

        return $this->view('account.thread', [
            't' => $t, 'messages' => $messages, 'me' => $request->user()->id,
            'latest' => $latest, 'canAccept' => $t->status === 'open' && $latest && (int) $latest['sender_id'] !== $request->user()->id,
            'pagetitle' => 'Negotiation',
        ]);
    }

    public function say(Request $request, string $thread)
    {
        return $this->done($this->neg->say($request, $thread, app(NegotiationService::class)), 'Sent.');
    }

    public function counter(Request $request, string $thread)
    {
        $request->validate(['price_naira' => 'required|numeric|min:1']);
        $request->merge(['price' => self::kobo($request->input('price_naira')), 'tip' => self::kobo($request->input('tip_naira')) ?? 0, 'message' => $request->input('message')]);

        return $this->done($this->neg->customerCounter($request, $thread, app(NegotiationService::class)), 'Your counter-offer is sent.');
    }

    public function accept(Request $request, string $thread)
    {
        $res = $this->neg->customerAccept($request, $thread, app(NegotiationService::class));
        if ($res->getStatusCode() >= 400) {
            return back()->with('error', $res->getData(true)['message'] ?? 'That could not be done.');
        }

        return redirect()->route('account.pay', $res->getData(true)['agreement'])->with('success', 'Agreed. Pay now to start the job.');
    }

    public function reject(Request $request, string $thread)
    {
        return $this->done($this->neg->customerReject($request, $thread, app(NegotiationService::class)), 'Declined.', route('account.requests'));
    }

    // ---------------------------------------------------------------- paying

    public function pay(Request $request, string $agreement, WalletService $wallet, EscrowService $escrow)
    {
        $a = DB::table('agreements as a')->join('operators as o', 'o.id', '=', 'a.provider_operator_id')->where('a.public_id', $agreement)->where('a.customer_id', $request->user()->id)
            ->first(['a.*', 'o.display_name as provider']);
        abort_unless($a, 404);
        $order = DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')->where('o.agreement_id', $a->id)->value('s.public_id');
        if ($order) {
            return redirect()->route('account.order', $order);
        }
        $total = $escrow->escrowTotal($a);

        return $this->view('account.pay', ['a' => $a, 'total' => $total, 'balance' => $wallet->balance($request->user()->id), 'pagetitle' => 'Pay']);
    }

    public function doPay(Request $request, string $agreement, PaymentService $payments)
    {
        $res = $this->wallet->payAgreement($request, $agreement, $payments);
        if ($res->getStatusCode() >= 400) {
            return back()->with('error', $res->getData(true)['message'] ?? 'That could not be done.');
        }
        $d = $res->getData(true);
        if (isset($d['authorization_url'])) {
            return redirect()->away($d['authorization_url']);   // Paystack's hosted checkout
        }
        $shipment = DB::table('shipments')->where('order_id', $d['order_id'])->value('public_id');

        return redirect()->route('account.order', $shipment)->with('success', 'Paid. We are finding your rider.');
    }

    // ---------------------------------------------------------------- orders

    private function orderQuery(int $uid)
    {
        return DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')->join('operators as p', 'p.id', '=', 's.operator_id')->where('o.customer_id', $uid)
            ->orderByDesc('o.id')->select('s.public_id', 's.status', 's.tracking_code', 'o.order_number', 'o.total', 'o.created_at', 'p.display_name as provider');
    }

    public function orders(Request $request)
    {
        $status = $request->query('status', 'all');
        $q = $this->orderQuery($request->user()->id)->limit(100);
        if ($status === 'active') {
            $q->whereNotIn('s.status', ['delivered', 'completed', 'cancelled', 'confirmed']);
        } elseif ($status !== 'all') {
            $q->where('s.status', $status);
        }

        return $this->view('account.orders', ['rows' => $q->get(), 'status' => $status, 'pagetitle' => 'My orders']);
    }

    public function order(Request $request, string $shipment, CancellationService $cancel, TrackingService $tracking)
    {
        $uid = $request->user()->id;
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->join('operators as p', 'p.id', '=', 's.operator_id')
            ->where('s.public_id', $shipment)->where('o.customer_id', $uid)
            ->first(['s.id', 's.public_id', 's.status', 's.tracking_code', 'o.order_number', 'o.total', 'o.agreement_id', 'p.display_name as provider', 's.created_at']);
        abort_unless($s, 404);

        $preview = $this->safeJson(fn () => app(CancellationController::class)->preview($request, $shipment, $cancel));
        $rated = DB::table('ratings')->where(['shipment_id' => $s->id, 'rater_type' => 'customer'])->exists();
        $code = in_array($s->status, ['delivered', 'completed', 'cancelled', 'confirmed', 'created', 'awaiting_dispatch'], true) ? null
            : $this->safeJson(fn () => app(\App\Http\Controllers\Api\Customer\TrackingController::class)->deliveryCode($request, $shipment, $tracking))['code'] ?? null;

        return $this->view('account.order', [
            's' => $s, 'timeline' => DB::table('shipment_events')->where('shipment_id', $s->id)->orderBy('seq')->get(['type', 'created_at']),
            'preview' => $preview, 'code' => $code, 'rated' => $rated,
            'canConfirm' => $s->status === 'delivered', 'canRate' => in_array($s->status, ['confirmed', 'completed'], true) && ! $rated,
            'pagetitle' => 'Order',
        ]);
    }

    private function safeJson(callable $fn): array
    {
        try {
            $r = $fn();

            return $r instanceof JsonResponse && $r->getStatusCode() < 400 ? $r->getData(true) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function cancel(Request $request, string $shipment, CancellationService $svc)
    {
        return $this->done(app(CancellationController::class)->cancel($request, $shipment, $svc), 'Order cancelled.');
    }

    public function confirm(Request $request, string $shipment, DeliveryService $delivery, RatingService $ratings)
    {
        $request->merge(['rating' => $request->filled('rating') ? (int) $request->input('rating') : null]);

        return $this->done(app(DeliveryController::class)->confirm($request, $shipment, $delivery, $ratings), 'Thank you. The provider has been paid.');
    }

    public function object(Request $request, string $shipment, DeliveryService $delivery)
    {
        return $this->done(app(DeliveryController::class)->object($request, $shipment, $delivery), 'We have opened a dispute and are holding the money while we look into it.');
    }

    public function rate(Request $request, string $shipment, RatingService $ratings)
    {
        return $this->done(app(DeliveryController::class)->rate($request, $shipment, $ratings), 'Thanks for rating.');
    }

    // ---------------------------------------------------------------- wallet

    public function walletPage(Request $request, WalletService $wallet)
    {
        return $this->view('account.wallet', [
            'w' => $this->wallet->show($request, $wallet)->getData(true), 'banks' => $this->wallet->bankAccounts($request)->getData(true),
            'bankList' => app(\App\Modules\Payments\BankDirectory::class)->all(),
            'history' => DB::table('payout_requests as p')->join('wallets as w', 'w.id', '=', 'p.wallet_id')->where(['w.owner_type' => 'customer', 'w.owner_id' => $request->user()->id])
                ->orderByDesc('p.id')->limit(20)->get(['p.amount', 'p.status', 'p.created_at']),
            'pagetitle' => 'Wallet',
        ]);
    }

    public function topUp(Request $request, WalletService $wallet)
    {
        $request->validate(['amount_naira' => 'required|numeric|min:100']);
        $res = $this->wallet->topUp($request->merge(['amount' => self::kobo($request->input('amount_naira'))]), $wallet);
        if ($res->getStatusCode() >= 400) {
            return back()->withInput()->with('error', $res->getData(true)['message'] ?? 'That could not be done.');
        }

        return redirect()->away($res->getData(true)['authorization_url']);
    }

    public function addBank(Request $request, BankAccountService $banks)
    {
        $d = $request->validate(['bank_code' => 'required|string|max:12', 'account_number' => 'required|digits:10']);
        $request->merge(['bank_name' => app(\App\Modules\Payments\BankDirectory::class)->name($d['bank_code'])]);

        return $this->done($this->wallet->addBankAccount($request, $banks), 'Bank account added.');
    }

    public function withdraw(Request $request, PayoutService $payouts)
    {
        $request->validate(['amount_naira' => 'required|numeric|min:1', 'bank_account_id' => 'required|string']);

        return $this->done($this->wallet->withdraw($request->merge(['amount' => self::kobo($request->input('amount_naira'))]), $payouts), 'Withdrawal requested.');
    }
}
