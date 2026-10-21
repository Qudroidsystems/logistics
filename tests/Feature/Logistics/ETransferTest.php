<?php

namespace Tests\Feature\Logistics;

use App\Models\PaymentGateway;
use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Payments\ETransferService;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Paying by Interac e-Transfer: instructions, staff confirmation, rejection, late money to the wallet, expiry. */
class ETransferTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $customer;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        $this->customer = $this->makeUser('Ada');
        $gw = PaymentGateway::firstOrCreate(['provider_key' => 'etransfer'], ['name' => 'Interac e-Transfer', 'mode' => 'live', 'is_active' => true, 'config' => []]);
        $gw->update(['mode' => 'live', 'is_active' => true]);
        foreach (['recipient_email' => 'pay@cityhop.test', 'recipient_name' => 'CityHop', 'hold_hours' => '48'] as $k => $v) {
            $gw->putCredential($k, 'live', $v);
        }
        $gw->save();
        $this->staff = $this->makeUser('Finance');
        Permission::findOrCreate('Confirm e-transfer', 'web');
        $this->staff->givePermissionTo('Confirm e-transfer');
    }

    private function start(): array
    {
        $agreement = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        $r = app(PaymentService::class)->initiateForAgreement($agreement, $this->customer->id, 'etransfer');

        return [$agreement, $r];
    }

    public function test_the_customer_gets_instructions_and_staff_confirm_it(): void
    {
        [$agreement, $r] = $this->start();
        $this->assertStringContainsString('/pay/etransfer/'.$r['reference'], $r['authorization_url']);
        $this->get('/pay/etransfer/'.$r['reference'])->assertOk()->assertSee('pay@cityhop.test')->assertSee($r['reference']);
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->customer->id, 'type' => 'payment.etransfer_instructions'])->exists());

        $this->post('/pay/etransfer/'.$r['reference'].'/sent', ['sender_name' => 'Ada Obi'])->assertRedirect();
        $intent = DB::table('payment_intents')->where('reference', $r['reference'])->first();

        $this->actingAs($this->staff)->get('/ops/etransfers')->assertOk()->assertSee($r['reference'])->assertSee('Customer says sent');
        $this->actingAs($this->staff)->post("/ops/etransfers/{$intent->id}/confirm", ['amount' => number_format($r['amount'] / 100, 2, '.', '')])->assertSessionHas('success');

        $this->assertSame('succeeded', DB::table('payment_intents')->where('id', $intent->id)->value('status'));
        $this->assertTrue(DB::table('orders')->where('agreement_id', $agreement)->exists(), 'the order goes ahead as after a card payment');
    }

    public function test_a_wrong_amount_is_refused(): void
    {
        [, $r] = $this->start();
        $intent = DB::table('payment_intents')->where('reference', $r['reference'])->first();

        $this->actingAs($this->staff)->post("/ops/etransfers/{$intent->id}/confirm", ['amount' => '1.00'])->assertSessionHas('error');
        $this->assertSame('pending', DB::table('payment_intents')->where('id', $intent->id)->value('status'));
    }

    public function test_rejecting_tells_the_customer(): void
    {
        [, $r] = $this->start();
        $intent = DB::table('payment_intents')->where('reference', $r['reference'])->first();

        $this->actingAs($this->staff)->post("/ops/etransfers/{$intent->id}/reject", ['reason' => 'No e-Transfer with this reference'])->assertSessionHas('success');
        $this->assertSame('failed', DB::table('payment_intents')->where('id', $intent->id)->value('status'));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->customer->id, 'type' => 'payment.etransfer_rejected'])->exists());
    }

    public function test_money_that_arrives_late_goes_to_the_wallet(): void
    {
        [, $r] = $this->start();
        $intent = DB::table('payment_intents')->where('reference', $r['reference'])->first();
        DB::table('payment_intents')->where('id', $intent->id)->update(['created_at' => now()->subHours(50)]);

        $this->assertSame(1, app(ETransferService::class)->expire());
        $this->assertSame('abandoned', DB::table('payment_intents')->where('id', $intent->id)->value('status'));

        $this->assertSame('wallet', app(ETransferService::class)->confirm($intent->id, $this->staff->id, (int) $intent->amount));
        $this->assertSame((int) $intent->amount, $this->walletBalance('customer', $this->customer->id));
        $this->assertLedgerBalanced();
    }

    public function test_an_agreement_waiting_for_an_e_transfer_is_not_expired_as_unpaid(): void
    {
        [$agreement] = $this->start();
        DB::table('agreements')->where('id', $agreement)->update(['locked_at' => now()->subHours(30)]);

        app(\App\Modules\Marketplace\CancellationService::class)->expireUnpaid(24);
        $this->assertSame('locked', DB::table('agreements')->where('id', $agreement)->value('status'));
    }

    public function test_withdrawals_are_paid_by_e_transfer_when_set_up_that_way(): void
    {
        config(['payments.payout_method' => 'etransfer']);
        $this->fundCustomer($this->customer->id, 5_000_000);
        \Laravel\Sanctum\Sanctum::actingAs($this->customer);

        $this->getJson('/api/v1/banks')->assertOk()->assertJsonPath('0.code', 'INTERAC');
        $this->postJson('/api/v1/customer/bank-accounts', ['bank_code' => 'INTERAC', 'account_number' => 'Ada@Example.ca', 'account_name' => 'Ada Obi'])->assertCreated();
        $bank = DB::table('bank_accounts')->where(['owner_type' => 'customer', 'owner_id' => $this->customer->id])->first();
        $this->assertSame('ada@example.ca', \Illuminate\Support\Facades\Crypt::decryptString($bank->account_number));

        $this->postJson('/api/v1/customer/wallet/withdraw', ['amount' => 200_000, 'bank_account_id' => $bank->public_id])->assertOk();
        $payout = DB::table('payout_requests')->latest('id')->first();
        if ($payout->status === 'requested') {
            app(\App\Modules\Settlements\PayoutService::class)->approve($payout->id, $this->staff->id);
        }
        $this->assertSame('processing', DB::table('payout_requests')->where('id', $payout->id)->value('status'), 'waits for staff to send it');

        $admin = $this->makeUser('Payouts');
        Permission::findOrCreate('Approve withdrawal', 'web');
        $admin->givePermissionTo('Approve withdrawal');
        $this->actingAs($admin)->get('/ops/payouts?status=processing')->assertOk()->assertSee('ada@example.ca');
        $this->actingAs($admin)->post("/ops/payouts/{$payout->id}/sent")->assertRedirect();
        $this->assertSame('paid', DB::table('payout_requests')->where('id', $payout->id)->value('status'));
        $this->assertLedgerBalanced();
    }
}
