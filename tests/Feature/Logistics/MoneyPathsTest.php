<?php

namespace Tests\Feature\Logistics;

use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketplace\CancellationService;
use App\Modules\Marketplace\DeliveryService;
use App\Modules\Marketplace\DisputeService;
use App\Modules\Marketplace\ShoppingService;
use App\Modules\Payments\Ledger\InsufficientFunds;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/**
 * Money paths: pay -> deliver -> confirm, cancellation by stage, disputes, shopping with an advance.
 * Needs PostgreSQL with PostGIS (see phpunit.xml); the schema uses partitions and triggers SQLite cannot run.
 */
class MoneyPathsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $customerId;
    private int $providerOp;
    private int $providerUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        // Dispatch is covered separately; here a paid shipment just has to exist.
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));

        $customer = $this->makeUser('Ada Customer');
        [$this->providerOp, $owner] = $this->makeProvider();
        $this->customerId = $customer->id;
        $this->providerUser = $owner->id;
    }

    private function payAndGetShipment(int $agreementId): int
    {
        app(PaymentService::class)->payWithWallet($agreementId, $this->customerId);

        return (int) DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $agreementId)->value('shipments.id');
    }

    // ---------------------------------------------------------------- pay

    public function test_locking_freezes_the_platform_fee_from_the_default_rule(): void
    {
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $a = DB::table('agreements')->find($id);

        $this->assertSame('locked', $a->status);
        $this->assertSame(36_000, (int) $a->platform_fee);       // 12% of 300,000 kobo
        $this->assertSame(264_000, (int) $a->provider_net);
    }

    public function test_changing_terms_clears_signatures_and_blocks_stale_acceptance(): void
    {
        $svc = app(\App\Modules\Marketplace\AgreementService::class);
        $id = $svc->propose($this->customerId, $this->providerOp, [
            'terms' => ['pickup' => ['line1' => 'a', 'lat' => 7.8, 'lng' => 5.9], 'dropoff' => ['line1' => 'b', 'lat' => 7.9, 'lng' => 5.9]],
            'price' => 200_000, 'service_type_id' => $this->serviceTypeId(),
        ], $this->customerId);
        $svc->accept($id, 'customer', 1);
        $svc->revise($id, ['pickup' => ['line1' => 'a', 'lat' => 7.8, 'lng' => 5.9], 'dropoff' => ['line1' => 'b', 'lat' => 7.9, 'lng' => 5.9]], 250_000, $this->providerUser, 'provider');

        $this->assertNull(DB::table('agreements')->find($id)->customer_signed_at);
        $this->expectException(RuntimeException::class);
        $svc->accept($id, 'provider', 1); // still the old version
    }

    public function test_wallet_payment_moves_money_into_escrow_and_creates_the_order(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);

        $shipmentId = $this->payAndGetShipment($id);

        $this->assertSame(700_000, $this->walletBalance('customer', $this->customerId));
        $this->assertSame(300_000, $this->escrowBalance($id));
        $this->assertSame('awaiting_dispatch', DB::table('shipments')->find($shipmentId)->status);
        $this->assertSame('paid', DB::table('agreements')->find($id)->status);
        $this->assertSame(2, DB::table('shipment_stops')->where('shipment_id', $shipmentId)->count());
        $this->assertLedgerBalanced();
    }

    public function test_wallet_payment_fails_cleanly_when_balance_is_short(): void
    {
        $this->fundCustomer($this->customerId, 100_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);

        try {
            app(PaymentService::class)->payWithWallet($id, $this->customerId);
            $this->fail('Expected InsufficientFunds');
        } catch (InsufficientFunds) {
            $this->assertSame(100_000, $this->walletBalance('customer', $this->customerId));
            $this->assertSame('locked', DB::table('agreements')->find($id)->status);
            $this->assertSame(0, DB::table('orders')->where('agreement_id', $id)->count());
        }
    }

    public function test_paying_twice_does_not_charge_twice(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $this->payAndGetShipment($id);

        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->payWithWallet($id, $this->customerId);
    }

    // ----------------------------------------------------- deliver + confirm

    public function test_confirming_delivery_pays_provider_and_platform_exactly(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser, 300_000, 0, 20_000);
        $shipmentId = $this->payAndGetShipment($id);

        app(DeliveryService::class)->markDelivered($shipmentId);
        $this->assertNotNull(DB::table('escrow_holds')->where('agreement_id', $id)->value('release_after'));

        app(DeliveryService::class)->confirm($shipmentId, $this->customerId, 5);

        $this->assertSame(264_000 + 20_000, $this->walletBalance('operator', $this->providerOp)); // net + tip
        $this->assertSame(36_000, $this->platformBalance('platform_commission'));
        $this->assertSame(0, $this->escrowBalance($id));
        $this->assertSame('completed', DB::table('agreements')->find($id)->status);
        $this->assertSame(1, DB::table('commissions')->where('shipment_id', $shipmentId)->count());
        $this->assertLedgerBalanced();
    }

    public function test_release_cannot_run_twice(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);
        app(DeliveryService::class)->markDelivered($shipmentId);
        app(DeliveryService::class)->confirm($shipmentId, $this->customerId);

        $this->expectException(RuntimeException::class);
        app(DeliveryService::class)->confirm($shipmentId, $this->customerId);
    }

    public function test_another_customer_cannot_confirm(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);
        app(DeliveryService::class)->markDelivered($shipmentId);

        $this->expectException(RuntimeException::class);
        app(DeliveryService::class)->confirm($shipmentId, $this->makeUser('Stranger')->id);
    }

    public function test_silence_past_the_window_auto_releases(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);
        app(DeliveryService::class)->markDelivered($shipmentId);
        DB::table('escrow_holds')->where('agreement_id', $id)->update(['release_after' => now()->subMinute()]);

        $this->artisan('escrow:auto-release')->assertSuccessful();

        $this->assertSame(264_000, $this->walletBalance('operator', $this->providerOp));
        $this->assertSame('auto_confirmed', DB::table('delivery_confirmations')->where('shipment_id', $shipmentId)->value('status'));
    }

    // ---------------------------------------------------------- cancellation

    public function test_cancelling_before_a_driver_is_assigned_refunds_everything(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);

        $r = app(CancellationService::class)->cancelShipment($shipmentId, 'customer', $this->customerId, 'changed_mind');

        $this->assertSame(['stage' => 'before_assignment', 'fee' => 0, 'refunded' => 300_000], $r);
        $this->assertSame(1_000_000, $this->walletBalance('customer', $this->customerId));
        $this->assertSame(0, $this->walletBalance('operator', $this->providerOp));
        $this->assertSame('cancelled', DB::table('shipments')->find($shipmentId)->status);
        $this->assertLedgerBalanced();
    }

    public function test_customer_cancelling_after_assignment_pays_the_policy_fee(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);
        DB::table('shipments')->where('id', $shipmentId)->update(['status' => 'assigned']);

        $r = app(CancellationService::class)->cancelShipment($shipmentId, 'customer', $this->customerId, 'changed_mind');

        $this->assertSame(30_000, $r['fee']);                                  // 10% of the price
        $this->assertSame(270_000, $r['refunded']);
        $this->assertSame(30_000 - 3_600, $this->walletBalance('operator', $this->providerOp)); // fee less platform's 12%
        $this->assertSame(3_600, $this->platformBalance('platform_commission'));
        $this->assertSame(970_000, $this->walletBalance('customer', $this->customerId));
        $this->assertLedgerBalanced();
    }

    public function test_provider_cancelling_refunds_in_full_and_is_logged(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);
        DB::table('shipments')->where('id', $shipmentId)->update(['status' => 'assigned']);

        app(CancellationService::class)->cancelShipment($shipmentId, 'provider', $this->providerUser, 'vehicle_broke_down');

        $this->assertSame(1_000_000, $this->walletBalance('customer', $this->customerId));
        $this->assertSame(1, DB::table('risk_events')->where('type', 'cancel_abuse')->count());
    }

    public function test_a_picked_up_job_cannot_be_cancelled(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);
        DB::table('shipments')->where('id', $shipmentId)->update(['status' => 'in_transit']);

        $this->expectException(RuntimeException::class);
        app(CancellationService::class)->cancelShipment($shipmentId, 'customer', $this->customerId, 'changed_mind');
    }

    // ---------------------------------------------------------------- disputes

    public function test_objection_freezes_escrow_and_a_partial_decision_splits_it(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);
        app(DeliveryService::class)->markDelivered($shipmentId);

        $disputeId = app(DeliveryService::class)->object($shipmentId, $this->customerId, 'damage', 'Box was crushed');
        $this->assertSame('frozen', DB::table('escrow_holds')->where('agreement_id', $id)->value('status'));

        $staff = $this->makeUser('Staff');
        app(DisputeService::class)->decide($disputeId, 'partial', $staff->id, 150_000); // provider keeps half the price

        $fee = intdiv(36_000 * 150_000, 300_000); // 18,000
        $this->assertSame(150_000 - $fee, $this->walletBalance('operator', $this->providerOp));
        $this->assertSame($fee, $this->platformBalance('platform_commission'));
        $this->assertSame(700_000 + 150_000, $this->walletBalance('customer', $this->customerId));
        $this->assertSame(0, $this->escrowBalance($id));
        $this->assertLedgerBalanced();
    }

    public function test_a_full_refund_decision_returns_everything(): void
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        $shipmentId = $this->payAndGetShipment($id);
        app(DeliveryService::class)->markDelivered($shipmentId);
        $disputeId = app(DeliveryService::class)->object($shipmentId, $this->customerId, 'lost', 'Never arrived');

        app(DisputeService::class)->decide($disputeId, 'full_refund', $this->makeUser('Staff')->id);

        $this->assertSame(1_000_000, $this->walletBalance('customer', $this->customerId));
        $this->assertSame(0, $this->walletBalance('operator', $this->providerOp));
    }

    // ------------------------------------------------------------ shopping

    public function test_shopping_with_an_advance_settles_spend_and_returns_the_rest(): void
    {
        [$shopperOp, $shopperOwner] = $this->makeProvider('market_shopper', 'Mama Put Shoppers');
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $shopperOp, $shopperOwner->id, 100_000, 500_000, 0, [
            'errand' => 'shopping', 'list' => [['item' => 'Rice 50kg', 'qty' => 1]],
        ]);
        $shipmentId = $this->payAndGetShipment($id); // escrow holds 600,000

        $shopping = app(ShoppingService::class);
        $shopping->advance($id, $shopperOp, $shopperOwner->id, 250_000);          // exactly the 50% default cap
        $this->assertSame(250_000, $this->walletBalance('operator', $shopperOp));
        $shopping->addReceipt($id, $shopperOp, 'Bodija Market', 400_000, 'receipts/1.jpg');

        app(DeliveryService::class)->markDelivered($shipmentId);
        app(DeliveryService::class)->confirm($shipmentId, $this->customerId);

        // Shopper: advance + (net 88,000 + spent 400,000 - advance 250,000) = net + spent.
        $this->assertSame(88_000 + 400_000, $this->walletBalance('operator', $shopperOp));
        $this->assertSame(12_000, $this->platformBalance('platform_commission'));
        $this->assertSame(1_000_000 - 600_000 + 100_000, $this->walletBalance('customer', $this->customerId)); // unspent 100,000 back
        $this->assertSame(0, $this->escrowBalance($id));
        $this->assertLedgerBalanced();
    }

    public function test_advance_is_capped_and_receipts_cannot_exceed_the_budget(): void
    {
        [$shopperOp, $shopperOwner] = $this->makeProvider('market_shopper', 'Quick Shoppers');
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $shopperOp, $shopperOwner->id, 100_000, 500_000, 0, ['errand' => 'shopping', 'list' => []]);
        $this->payAndGetShipment($id);
        $shopping = app(ShoppingService::class);

        try {
            $shopping->advance($id, $shopperOp, $shopperOwner->id, 250_001);
            $this->fail('Advance over the cap should be refused.');
        } catch (RuntimeException) {
            $this->assertSame(0, $this->walletBalance('operator', $shopperOp));
        }

        $this->expectException(RuntimeException::class);
        $shopping->addReceipt($id, $shopperOp, 'Bodija Market', 500_001, 'receipts/2.jpg');
    }

    public function test_a_rejected_receipt_is_not_paid_out(): void
    {
        [$shopperOp, $shopperOwner] = $this->makeProvider('market_shopper', 'Careful Shoppers');
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $shopperOp, $shopperOwner->id, 100_000, 500_000, 0, ['errand' => 'shopping', 'list' => []]);
        $shipmentId = $this->payAndGetShipment($id);
        $shopping = app(ShoppingService::class);
        $good = $shopping->addReceipt($id, $shopperOp, 'Vendor A', 300_000, 'r/a.jpg');
        $bad = $shopping->addReceipt($id, $shopperOp, 'Vendor B', 150_000, 'r/b.jpg');
        $shopping->reviewReceipt($bad, $this->customerId, false);

        $this->assertSame(300_000, $shopping->spend($id));
        app(DeliveryService::class)->markDelivered($shipmentId);
        app(DeliveryService::class)->confirm($shipmentId, $this->customerId);

        $this->assertSame(88_000 + 300_000, $this->walletBalance('operator', $shopperOp));
        $this->assertSame(1_000_000 - 600_000 + 200_000, $this->walletBalance('customer', $this->customerId));
    }

    public function test_extra_budget_needs_the_customer_to_have_the_money(): void
    {
        [$shopperOp, $shopperOwner] = $this->makeProvider('market_shopper', 'Budget Shoppers');
        $this->fundCustomer($this->customerId, 600_000);
        $id = $this->lockedAgreement($this->customerId, $shopperOp, $shopperOwner->id, 100_000, 500_000, 0, ['errand' => 'shopping', 'list' => []]);
        $this->payAndGetShipment($id);
        $shopping = app(ShoppingService::class);
        $amendment = $shopping->requestAmendment($id, $shopperOp, 100_000, 'Price of rice went up');

        try {
            $shopping->approveAmendment($amendment, $this->customerId);
            $this->fail('Customer wallet is empty.');
        } catch (InsufficientFunds) {
            $this->assertSame(500_000, (int) DB::table('agreements')->find($id)->goods_budget);
        }

        $this->fundCustomer($this->customerId, 100_000);
        $shopping->approveAmendment($amendment, $this->customerId);
        $this->assertSame(600_000, (int) DB::table('agreements')->find($id)->goods_budget);
        $this->assertSame(700_000, $this->escrowBalance($id));
        $this->assertLedgerBalanced();
    }

    public function test_cancelling_after_an_advance_is_refused(): void
    {
        [$shopperOp, $shopperOwner] = $this->makeProvider('market_shopper', 'Advance Shoppers');
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $shopperOp, $shopperOwner->id, 100_000, 500_000, 0, ['errand' => 'shopping', 'list' => []]);
        $shipmentId = $this->payAndGetShipment($id);
        app(ShoppingService::class)->advance($id, $shopperOp, $shopperOwner->id, 100_000);

        $this->expectException(RuntimeException::class);
        app(CancellationService::class)->cancelShipment($shipmentId, 'customer', $this->customerId, 'changed_mind');
    }
}
