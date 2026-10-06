<?php

namespace Tests\Feature\Logistics;

use App\Modules\Dispatch\DispatchService;
use App\Modules\Payments\PaymentService;
use App\Modules\Payments\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/**
 * Card payments end to end through the public webhook. QUEUE_CONNECTION=sync in phpunit.xml runs the
 * ProcessGatewayEvent job inline. If the route is blocked by the school portal's FeatureRouteGuard or
 * MaintenanceMode middleware, whitelist 'webhook/*' there.
 */
class PaystackWebhookTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private const SECRET = 'sk_test_unit_secret';

    private int $customerId;
    private int $providerOp;
    private int $providerUser;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.mode' => 'sandbox', 'services.paystack.test_secret_key' => self::SECRET]);
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.test/abc', 'access_code' => 'x', 'reference' => 'r']])]);

        $this->customerId = $this->makeUser('Ada Customer')->id;
        [$this->providerOp, $owner] = $this->makeProvider();
        $this->providerUser = $owner->id;
    }

    private function sendWebhook(array $payload, ?string $secret = self::SECRET)
    {
        $raw = json_encode($payload);
        $sig = hash_hmac('sha512', $raw, $secret ?? 'wrong');

        return $this->call('POST', '/webhook/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $raw);
    }

    private function charge(string $reference, int $amount, int $id = 111): array
    {
        return ['event' => 'charge.success', 'data' => ['id' => $id, 'reference' => $reference, 'amount' => $amount, 'currency' => 'NGN', 'channel' => 'card', 'authorization' => ['authorization_code' => 'AUTH_x']]];
    }

    public function test_a_forged_signature_is_rejected_and_stored_nowhere(): void
    {
        $this->sendWebhook($this->charge('LG-X', 100), 'someone-elses-secret')->assertStatus(401);
        $this->assertSame(0, DB::table('gateway_events')->count());
    }

    public function test_a_confirmed_card_payment_holds_escrow_and_creates_the_order(): void
    {
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $pay = app(PaymentService::class)->initiateForAgreement($id, $this->customerId);
        $this->assertSame(300_000, $pay['amount']);

        $this->sendWebhook($this->charge($pay['reference'], 300_000))->assertOk();

        $this->assertSame('succeeded', DB::table('payment_intents')->where('reference', $pay['reference'])->value('status'));
        $this->assertSame(300_000, $this->escrowBalance($id));
        $this->assertSame(1, DB::table('orders')->where('agreement_id', $id)->count());
        $this->assertSame('paid', DB::table('agreements')->find($id)->status);
        $this->assertNotNull(DB::table('gateway_events')->value('processed_at'));
        $this->assertLedgerBalanced();
    }

    public function test_a_replayed_webhook_changes_nothing(): void
    {
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $pay = app(PaymentService::class)->initiateForAgreement($id, $this->customerId);
        $payload = $this->charge($pay['reference'], 300_000);

        $this->sendWebhook($payload)->assertOk();
        $before = DB::table('ledger_transactions')->count();
        $this->sendWebhook($payload)->assertOk();

        $this->assertSame($before, DB::table('ledger_transactions')->count());
        $this->assertSame(1, DB::table('orders')->where('agreement_id', $id)->count());
        $this->assertSame(1, DB::table('gateway_events')->count());
    }

    public function test_a_charge_for_the_wrong_amount_does_not_fund_escrow(): void
    {
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $pay = app(PaymentService::class)->initiateForAgreement($id, $this->customerId);

        $this->sendWebhook($this->charge($pay['reference'], 1_000))->assertOk();

        $this->assertSame('failed', DB::table('payment_intents')->where('reference', $pay['reference'])->value('status'));
        $this->assertSame(0, DB::table('escrow_holds')->count());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(1, DB::table('risk_events')->where('type', 'payment_amount_mismatch')->count());
    }

    public function test_an_unknown_reference_is_ignored_safely(): void
    {
        $this->sendWebhook($this->charge('LG-DOES-NOT-EXIST', 5_000))->assertOk();
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('ledger_transactions')->count());
    }

    public function test_a_confirmed_top_up_credits_the_wallet_once(): void
    {
        $top = app(WalletService::class)->startTopUp($this->customerId, 500_000);
        $payload = $this->charge($top['reference'], 500_000, 222);

        $this->sendWebhook($payload)->assertOk();
        $this->sendWebhook($payload)->assertOk();

        $this->assertSame(500_000, $this->walletBalance('customer', $this->customerId));
        $this->assertSame('completed', DB::table('wallet_topups')->value('status'));
        $this->assertLedgerBalanced();
    }

    public function test_an_unpaid_agreement_expires_and_cannot_be_paid_afterwards(): void
    {
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        DB::table('agreements')->where('id', $id)->update(['locked_at' => now()->subHours(25)]);

        $this->artisan('agreements:expire-unpaid')->assertSuccessful();

        $this->assertSame('cancelled', DB::table('agreements')->find($id)->status);
        $this->expectException(\RuntimeException::class);
        app(PaymentService::class)->initiateForAgreement($id, $this->customerId);
    }
}
