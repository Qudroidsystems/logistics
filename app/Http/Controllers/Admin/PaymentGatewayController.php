<?php
// app/Http/Controllers/Admin/PaymentGatewayController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Support\PaymentGatewayCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finance › Payment Gateways: admins enter each gateway's test and live
 * credentials here (no .env editing needed). Secrets are encrypted at rest
 * and never sent back to the browser.
 */
class PaymentGatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage payment gateways');
    }

    public function index()
    {
        // Make sure every known gateway has a row (switched off by default).
        foreach (PaymentGatewayCatalog::PROVIDERS as $key => $def) {
            PaymentGateway::firstOrCreate(
                ['provider_key' => $key],
                ['name' => $def['name'], 'mode' => 'sandbox', 'is_active' => false, 'config' => []]
            );
        }

        $order    = array_keys(PaymentGatewayCatalog::PROVIDERS);
        $gateways = PaymentGateway::whereIn('provider_key', $order)->get()
            ->sortBy(fn ($g) => array_search($g->provider_key, $order))->values();

        return view('admin.payment-gateways.index', [
            'pagetitle' => 'Payment Gateways',
            'gateways'  => $gateways,
            'urls'      => [
                'paystack_webhook'  => route('webhook.paystack'),
                'paystack_callback' => route('online-fees.callback'),
                'opay_webhook'      => route('webhook.opay'),
            ],
        ]);
    }

    /** Save mode, on/off and credentials. Blank credential inputs keep the saved value. */
    public function updateConfig(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        $fields  = PaymentGatewayCatalog::fields($gateway->provider_key);

        $data = $request->validate([
            'mode'            => 'required|in:sandbox,live',
            'is_active'       => 'nullable|boolean',
            'credentials'     => 'nullable|array',
            'credentials.*'   => 'nullable|array',
            'credentials.*.*' => 'nullable|string|max:1000',
            'clear'           => 'nullable|array',
            'clear.*'         => 'nullable|array',
        ]);

        $errors = [];
        foreach (['test', 'live'] as $set) {
            foreach ($fields as $field => $def) {
                $value = trim((string) ($data['credentials'][$set][$field] ?? ''));

                if (!empty($data['clear'][$set][$field])) {
                    $gateway->putCredential($field, $set, null);
                    continue;
                }
                if ($value === '') {
                    continue; // keep what is saved
                }
                $prefix = $def['prefix'][$set] ?? null;
                if ($prefix && !str_starts_with($value, $prefix)) {
                    $errors[] = ($set === 'live' ? 'Live' : 'Test') . " {$def['label']} should start with \"{$prefix}\".";
                    continue;
                }
                if (str_contains(strtolower($value), 'xxxx')) {
                    $errors[] = ($set === 'live' ? 'Live' : 'Test') . " {$def['label']} looks like a placeholder.";
                    continue;
                }
                $gateway->putCredential($field, $set, $value);
            }
        }

        $gateway->mode      = $data['mode'];
        $gateway->is_active = (bool) ($data['is_active'] ?? false);

        if ($gateway->is_active && !$gateway->isConfigured()) {
            $errors[] = 'Add the ' . ($gateway->mode === 'live' ? 'live' : 'test') . ' keys before switching '
                . $gateway->name . ' on in ' . ($gateway->mode === 'live' ? 'Live' : 'Sandbox') . ' mode.';
        }

        if ($errors) {
            return $this->reply($request, false, implode(' ', $errors), 422);
        }

        // Old copies of keys in plain columns are no longer needed.
        $gateway->secret_key = null;
        $gateway->public_key = $gateway->credential('public_key', PaymentGatewayCatalog::set($gateway->mode)); // public, safe
        $gateway->save();

        return $this->reply($request, true, $gateway->name . ' settings saved.', 200, $this->summary($gateway));
    }

    public function toggleGateway(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);

        if (!$gateway->is_active && !$gateway->isConfigured()) {
            return $this->reply($request, false, 'Add the ' . ($gateway->mode === 'live' ? 'live' : 'test') . ' keys first.', 422);
        }

        $gateway->update(['is_active' => !$gateway->is_active]);

        return $this->reply($request, true, $gateway->name . ' is now ' . ($gateway->is_active ? 'on' : 'off') . '.', 200, $this->summary($gateway));
    }

    /** Check the saved keys against the provider. ?set=test|live (default: current mode). */
    public function testGateway(Request $request, $gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        $set     = in_array($request->input('set'), ['test', 'live'], true) ? $request->input('set') : PaymentGatewayCatalog::set($gateway->mode);
        $label   = $set === 'live' ? 'live' : 'test';

        if (!$gateway->isConfigured($set)) {
            return response()->json(['success' => false, 'message' => "No complete {$label} keys saved for {$gateway->name}."]);
        }

        try {
            $result = match ($gateway->provider_key) {
                'paystack'    => $this->testBearer('https://api.paystack.co/bank?perPage=1', $gateway->credential('secret_key', $set)),
                'flutterwave' => $this->testBearer('https://api.flutterwave.com/v3/banks/NG', $gateway->credential('secret_key', $set)),
                'stripe'      => $this->testBearer('https://api.stripe.com/v1/balance', $gateway->credential('secret_key', $set)),
                'monnify'     => $this->testMonnify($gateway, $set),
                'opay'        => \App\Services\Payment\OpayGateway::testKeys($set, $gateway->credential('merchant_id', $set), $gateway->credential('secret_key', $set)),
                default       => ['success' => true, 'message' => "{$label} details are saved. {$gateway->name} has no automatic connection test; verify with a small payment."],
            };
        } catch (\Throwable $e) {
            Log::warning('Gateway test failed', ['gateway' => $gateway->provider_key, 'error' => $e->getMessage()]);
            $result = ['success' => false, 'message' => 'Could not reach ' . $gateway->name . '. Check the server\'s internet connection.'];
        }

        if ($result['success'] && empty($result['plain'])) {
            $result['message'] = "{$gateway->name} accepted the {$label} keys.";
        }
        return response()->json($result);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    protected function testBearer(string $url, string $secret): array
    {
        $res = Http::withToken($secret)->acceptJson()->timeout(20)->get($url);
        if ($res->successful()) {
            return ['success' => true];
        }
        $msg = $res->json('message') ?: $res->json('error.message') ?: ('HTTP ' . $res->status());
        return ['success' => false, 'message' => 'Rejected: ' . $msg, 'plain' => true];
    }

    protected function testMonnify(PaymentGateway $g, string $set): array
    {
        $base = $set === 'live' ? 'https://api.monnify.com' : 'https://sandbox.monnify.com';
        $res  = Http::withBasicAuth($g->credential('api_key', $set), $g->credential('secret_key', $set))
            ->acceptJson()->timeout(20)->post($base . '/api/v1/auth/login');

        return $res->successful() && $res->json('requestSuccessful')
            ? ['success' => true]
            : ['success' => false, 'message' => 'Rejected: ' . ($res->json('responseMessage') ?: 'HTTP ' . $res->status()), 'plain' => true];
    }

    protected function summary(PaymentGateway $g): array
    {
        $masked = [];
        foreach (['test', 'live'] as $set) {
            foreach (PaymentGatewayCatalog::fields($g->provider_key) as $field => $def) {
                $masked[$set][$field] = $g->maskedCredential($field, $set);
            }
        }
        return [
            'id'              => $g->id,
            'is_active'       => (bool) $g->is_active,
            'mode'            => $g->mode,
            'test_configured' => $g->isConfigured('test'),
            'live_configured' => $g->isConfigured('live'),
            'masked'          => $masked,
        ];
    }

    protected function reply(Request $request, bool $ok, string $message, int $status = 200, array $extra = [])
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $message, 'gateway' => $extra ?: null], $status);
        }
        return back()->with($ok ? 'success' : 'error', $message);
    }
}
