<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketing\ReferralService;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** A provider's own promo codes (paid from their share) and invite-a-friend credits (paid by the platform). */
class PromosAndReferralsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        $this->customer = $this->makeUser('Ada');
        $this->fundCustomer($this->customer->id, 5_000_000);
    }

    private function pub(int $agreement): string
    {
        return (string) DB::table('agreements')->where('id', $agreement)->value('public_id');
    }

    public function test_a_provider_creates_its_own_code_and_pays_for_it_from_its_share(): void
    {
        $this->actingAs($this->owner)->get('/provider/promo-codes')->assertOk()->assertSee('No codes yet');
        $this->actingAs($this->owner)->post('/provider/promo-codes', ['code' => 'swift500', 'type' => 'fixed', 'value' => 500])->assertSessionHas('success');
        $this->assertSame(['operator_id' => $this->op, 'funded_by' => 'operator', 'value' => 50_000],
            (array) DB::table('promo_codes')->where('code', 'SWIFT500')->first(['operator_id', 'funded_by', 'value']));

        // Another provider's customers can't use it.
        [$other, $otherOwner] = $this->makeProvider('company', 'Other Co');
        $elsewhere = $this->lockedAgreement($this->customer->id, $other, $otherOwner->id, 300_000);
        Sanctum::actingAs($this->customer);
        $this->postJson('/api/v1/customer/agreements/'.$this->pub($elsewhere).'/promo', ['code' => 'SWIFT500'])->assertStatus(422);

        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        $this->postJson('/api/v1/customer/agreements/'.$this->pub($a).'/promo', ['code' => 'SWIFT500'])->assertOk()->assertJsonPath('payable', 250_000);
        $before = $this->walletBalance('customer', $this->customer->id);
        app(PaymentService::class)->payWithWallet($a, $this->customer->id);
        $this->assertSame(250_000, $before - $this->walletBalance('customer', $this->customer->id));

        $g = DB::table('agreements')->where('id', $a)->first();
        $provider = $this->walletBalance('operator', $this->op);
        $fees = $this->platformBalance('platform_commission');
        $promo = $this->platformBalance('promo_expense');
        $paid = app(EscrowService::class)->release($a);

        // The provider gets its usual share less the ₦500; the platform still earns its fee and gets back the ₦500 it topped up.
        $this->assertSame((int) $g->provider_net - 50_000, $paid['to_provider']);
        $this->assertSame((int) $g->provider_net - 50_000, $this->walletBalance('operator', $this->op) - $provider);
        $this->assertSame((int) $g->platform_fee, $this->platformBalance('platform_commission') - $fees);
        $this->assertSame($promo - 50_000, $this->platformBalance('promo_expense'));
        $this->assertLedgerBalanced();
        $hold = DB::table('escrow_holds')->where('agreement_id', $a)->first();
        $this->assertSame((int) $hold->amount, (int) $hold->released_amount + (int) $hold->refunded_amount);

        $this->actingAs($this->owner)->get('/provider/promo-codes')->assertOk()->assertSee('SWIFT500')->assertSee('₦500 off');
    }

    public function test_a_dispute_split_charges_the_provider_its_code_in_proportion(): void
    {
        DB::table('promo_codes')->insert(['code' => 'HALF', 'type' => 'fixed', 'value' => 100_000, 'per_user_limit' => 1, 'operator_id' => $this->op, 'funded_by' => 'operator', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 400_000);
        Sanctum::actingAs($this->customer);
        $this->postJson('/api/v1/customer/agreements/'.$this->pub($a).'/promo', ['code' => 'HALF'])->assertOk();
        app(PaymentService::class)->payWithWallet($a, $this->customer->id);
        $promo = $this->platformBalance('promo_expense');

        $r = app(EscrowService::class)->releasePartial($a, 200_000);
        // Half the job paid, so half the code (₦500) comes from the provider; the rest of the top-up returns to the platform.
        $g = DB::table('agreements')->where('id', $a)->first();
        $fee = intdiv((int) $g->platform_fee * 200_000, 400_000);
        $this->assertSame(200_000 - $fee - 50_000, $r['to_provider']);
        $this->assertSame($promo - 100_000, $this->platformBalance('promo_expense'));
        $this->assertLedgerBalanced();
    }

    public function test_an_invited_friend_saves_on_the_first_delivery_and_the_inviter_is_credited(): void
    {
        ReferralService::save(['enabled' => true, 'reward' => 70_000, 'friend_discount' => 40_000, 'min_price' => 100_000, 'monthly_cap' => 5], $this->owner->id);
        Sanctum::actingAs($this->customer);
        $mine = $this->getJson('/api/v1/customer/referrals')->assertOk()->assertJsonPath('enabled', true)->json();
        $this->assertStringContainsString($mine['code'], $mine['share']);

        $this->postJson('/api/v1/auth/register', ['name' => 'Bola Ade', 'email' => 'bola@example.com', 'password' => 'secret-pass-1', 'referral_code' => strtolower($mine['code'])])->assertCreated();
        $friend = User::where('email', 'bola@example.com')->first();
        $this->assertSame($this->customer->id, (int) DB::table('customer_profiles')->where('user_id', $friend->id)->value('referred_by'));
        // A made-up code doesn't stop anyone signing up.
        $this->postJson('/api/v1/auth/register', ['name' => 'Chi', 'email' => 'chi@example.com', 'password' => 'secret-pass-1', 'referral_code' => 'NOPE'])->assertCreated();
        $this->assertSame(1, DB::table('referrals')->count());

        // The friend's first delivery: ₦400 off, paid by the platform.
        $this->fundCustomer($friend->id, 1_000_000);
        $a = $this->lockedAgreement($friend->id, $this->op, $this->owner->id, 300_000);
        Sanctum::actingAs($friend->fresh());
        $this->getJson('/api/v1/customer/agreements/'.$this->pub($a).'/payment')->assertOk()->assertJsonPath('payable', 260_000);
        $before = $this->walletBalance('customer', $friend->id);
        app(PaymentService::class)->payWithWallet($a, $friend->id);
        $this->assertSame(260_000, $before - $this->walletBalance('customer', $friend->id));
        $provider = $this->walletBalance('operator', $this->op);
        $inviter = $this->walletBalance('customer', $this->customer->id);
        app(EscrowService::class)->release($a);
        $this->assertSame((int) DB::table('agreements')->where('id', $a)->value('provider_net'), $this->walletBalance('operator', $this->op) - $provider);

        // Done: the inviter gets ₦700, once.
        $this->assertSame(70_000, $this->walletBalance('customer', $this->customer->id) - $inviter);
        $this->assertSame('rewarded', DB::table('referrals')->value('status'));
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->customer->id)->where('type', 'referral.rewarded')->count());
        $this->assertLedgerBalanced();

        // The second delivery has no friend discount.
        $b = $this->lockedAgreement($friend->id, $this->op, $this->owner->id, 300_000);
        $this->getJson('/api/v1/customer/agreements/'.$this->pub($b).'/payment')->assertJsonPath('payable', 300_000);

        Sanctum::actingAs($this->customer->fresh());
        $this->getJson('/api/v1/customer/referrals')->assertJsonPath('earned', 70_000)->assertJsonPath('friends.0.name', 'Bola')->assertJsonPath('friends.0.label', 'You earned ₦700');
    }

    public function test_invites_do_nothing_while_off_and_respect_the_monthly_limit(): void
    {
        $code = DB::table('customer_profiles')->where('user_id', $this->customer->id)->value('referral_code')
            ?? app(ReferralService::class)->forCustomer($this->customer->id)['code'];
        $this->postJson('/api/v1/auth/register', ['name' => 'Off', 'email' => 'off@example.com', 'password' => 'secret-pass-1', 'referral_code' => $code])->assertCreated();
        $this->assertSame(0, DB::table('referrals')->count(), 'off by default');

        ReferralService::save(['enabled' => true, 'monthly_cap' => 0], $this->owner->id);
        $this->postJson('/api/v1/auth/register', ['name' => 'Capped', 'email' => 'cap@example.com', 'password' => 'secret-pass-1', 'referral_code' => $code])->assertCreated();
        $friend = User::where('email', 'cap@example.com')->first();
        $this->fundCustomer($friend->id, 1_000_000);
        $a = $this->lockedAgreement($friend->id, $this->op, $this->owner->id, 300_000);
        app(PaymentService::class)->payWithWallet($a, $friend->id);
        $inviter = $this->walletBalance('customer', $this->customer->id);
        app(EscrowService::class)->release($a);
        $this->assertSame($inviter, $this->walletBalance('customer', $this->customer->id));
        $this->assertSame(['void', 'Monthly limit reached'], array_values((array) DB::table('referrals')->first(['status', 'note'])));
    }

    public function test_staff_set_the_invite_amounts(): void
    {
        $u = $this->makeUser('Staff');
        foreach (['View promo code', 'Update promo code'] as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $u->givePermissionTo(['View promo code', 'Update promo code']);
        $this->actingAs($u)->get('/ops/promos')->assertOk()->assertSee('Invite a friend')->assertSee('Invites are off');
        $this->actingAs($u)->post('/ops/referrals', ['enabled' => '1', 'reward_naira' => 1000, 'friend_naira' => 500, 'min_price_naira' => 1500, 'monthly_cap' => 10])->assertSessionHas('success');
        $this->assertSame(['enabled' => true, 'reward' => 100_000, 'friend_discount' => 50_000, 'min_price' => 150_000, 'monthly_cap' => 10], ReferralService::settings());
    }
}
