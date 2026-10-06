<?php

namespace Tests\Feature\Logistics;

use App\Jobs\ProcessPayout;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketplace\DeliveryService;
use App\Modules\Payments\Ledger\InsufficientFunds;
use App\Modules\Payments\PaymentService;
use App\Modules\Settlements\PayoutService;
use App\Modules\Settlements\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $customerId;
    private int $providerOp;
    private int $providerUser;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.mode' => 'sandbox', 'services.paystack.test_secret_key' => 'sk_test_unit_secret']);
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        Http::fake([
            'api.paystack.co/transferrecipient' => Http::response(['status' => true, 'data' => ['recipient_code' => 'RCP_test']]),
            'api.paystack.co/transfer' => Http::response(['status' => true, 'data' => ['status' => 'pending']]),
        ]);

        $this->customerId = $this->makeUser('Ada Customer')->id;
        [$this->providerOp, $owner] = $this->makeProvider();
        $this->providerUser = $owner->id;
    }

    /** One completed job leaves the provider with 264,000 kobo and the platform with 36,000. */
    private function earnOneJob(): int
    {
        $this->fundCustomer($this->customerId, 1_000_000);
        $id = $this->lockedAgreement($this->customerId, $this->providerOp, $this->providerUser);
        app(PaymentService::class)->payWithWallet($id, $this->customerId);
        $shipmentId = (int) DB::table('shipments')->join('orders', 'orders.id', '=', 'shipments.order_id')->where('orders.agreement_id', $id)->value('shipments.id');
        app(DeliveryService::class)->markDelivered($shipmentId);
        app(DeliveryService::class)->confirm($shipmentId, $this->customerId);

        return $shipmentId;
    }

    private function bank(bool $verified = true): int
    {
        return DB::table('bank_accounts')->insertGetId([
            'public_id' => (string) Str::ulid(), 'owner_type' => 'operator', 'owner_id' => $this->providerOp, 'bank_code' => '058', 'bank_name' => 'GTBank',
            'account_number' => Crypt::encryptString('0123456789'), 'account_number_last4' => '6789', 'account_name' => 'SWIFT HAULAGE LTD',
            'verified_at' => $verified ? now() : null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_requesting_a_payout_reserves_the_money_at_once(): void
    {
        Queue::fake();
        $this->earnOneJob();

        $id = app(PayoutService::class)->request($this->providerOp, $this->providerUser, 200_000, $this->bank());

        $this->assertSame(64_000, $this->walletBalance('operator', $this->providerOp));
        $this->assertSame(200_000, $this->platformBalance('payout_in_transit'));
        $this->assertSame('approved', DB::table('payout_requests')->find($id)->status);
        Queue::assertPushed(ProcessPayout::class);
        $this->assertLedgerBalanced();
    }

    public function test_a_payout_larger_than_the_balance_is_refused_and_leaves_no_trace(): void
    {
        $this->earnOneJob();
        try {
            app(PayoutService::class)->request($this->providerOp, $this->providerUser, 300_000, $this->bank());
            $this->fail('Expected InsufficientFunds');
        } catch (InsufficientFunds) {
            $this->assertSame(264_000, $this->walletBalance('operator', $this->providerOp));
            $this->assertSame(0, DB::table('payout_requests')->count());
        }
    }

    public function test_payout_guards(): void
    {
        $this->earnOneJob();
        $svc = app(PayoutService::class);
        $bank = $this->bank();

        foreach ([
            fn () => $svc->request($this->providerOp, $this->providerUser, 50_000, $bank),                    // under the N1,000 minimum
            fn () => $svc->request($this->providerOp, $this->providerUser, 200_000, $this->bank(false)),      // unverified account
            fn () => $svc->request($this->providerOp, $this->makeUser('Stranger')->id, 200_000, $bank),       // not a member
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Expected the payout to be refused.');
            } catch (RuntimeException) {
                $this->assertSame(264_000, $this->walletBalance('operator', $this->providerOp));
            }
        }
    }

    public function test_sending_then_confirming_closes_the_payout(): void
    {
        Queue::fake();
        $this->earnOneJob();
        $svc = app(PayoutService::class);
        $id = $svc->request($this->providerOp, $this->providerUser, 200_000, $this->bank());

        $svc->send($id);
        $p = DB::table('payout_requests')->find($id);
        $this->assertSame('processing', $p->status);
        $this->assertSame('PO-'.$p->public_id, $p->gateway_transfer_ref);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/transfer') && $r['amount'] === 200_000 && $r['reference'] === $p->gateway_transfer_ref);

        $svc->complete($p->gateway_transfer_ref);
        $svc->complete($p->gateway_transfer_ref); // a repeated webhook is harmless

        $this->assertSame('paid', DB::table('payout_requests')->find($id)->status);
        $this->assertSame(0, $this->platformBalance('payout_in_transit'));
        $this->assertSame(64_000, $this->walletBalance('operator', $this->providerOp));
        $this->assertLedgerBalanced();
    }

    public function test_a_failed_transfer_returns_the_money_to_the_wallet(): void
    {
        Queue::fake();
        $this->earnOneJob();
        $svc = app(PayoutService::class);
        $id = $svc->request($this->providerOp, $this->providerUser, 200_000, $this->bank());
        $svc->send($id);

        $svc->fail(DB::table('payout_requests')->find($id)->gateway_transfer_ref, 'Account closed');
        $svc->fail($id, 'again'); // idempotent

        $this->assertSame('failed', DB::table('payout_requests')->find($id)->status);
        $this->assertSame(264_000, $this->walletBalance('operator', $this->providerOp));
        $this->assertSame(0, $this->platformBalance('payout_in_transit'));
    }

    public function test_large_payouts_wait_for_staff_and_can_be_rejected(): void
    {
        Queue::fake();
        DB::table('platform_settings')->updateOrInsert(['operator_id' => null, 'scope' => 'global', 'scope_id' => null, 'key' => 'payout.auto_approve_max_kobo'], ['value' => json_encode(100_000), 'created_at' => now(), 'updated_at' => now()]);
        $this->earnOneJob();
        $svc = app(PayoutService::class);

        $id = $svc->request($this->providerOp, $this->providerUser, 200_000, $this->bank());
        $this->assertSame('requested', DB::table('payout_requests')->find($id)->status);
        Queue::assertNotPushed(ProcessPayout::class);

        $svc->reject($id, $this->makeUser('Finance')->id, 'Name mismatch');
        $this->assertSame(264_000, $this->walletBalance('operator', $this->providerOp));
        $this->assertSame('failed', DB::table('payout_requests')->find($id)->status);
    }

    public function test_weekly_statement_counts_each_job_once(): void
    {
        $this->earnOneJob();
        $svc = app(SettlementService::class);
        $day = now()->toDateString();

        $id = $svc->generate($this->providerOp, $day, $day);
        $again = $svc->generate($this->providerOp, $day, $day);

        $s = DB::table('settlements')->find($id);
        $this->assertSame($id, $again);
        $this->assertSame([300_000, 36_000, 264_000], [(int) $s->gross, (int) $s->commission, (int) $s->net]);
        $this->assertSame(1, DB::table('settlement_items')->where('settlement_id', $id)->count());
    }
}
