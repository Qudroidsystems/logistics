<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketing\LoyaltyService;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Loyalty points earned on deliveries and spent at payment, and gift cards bought, issued and spent. */
class LoyaltyAndGiftCardsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
        $this->customer = $this->makeUser('Ada');
        $this->fundCustomer($this->customer->id, 5_000_000);
        LoyaltyService::save(['enabled' => true, 'earn_per_100' => 1, 'point_value' => 100, 'min_redeem' => 500, 'max_share_bp' => 5000], $this->owner->id);
    }

    private function pub(int $agreement): string
    {
        return (string) DB::table('agreements')->where('id', $agreement)->value('public_id');
    }

    private function points(?User $u = null): int
    {
        return app(LoyaltyService::class)->balance(($u ?? $this->customer)->id);
    }

    public function test_points_pay_part_of_a_delivery_and_more_are_earned_when_it_pays_out(): void
    {
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        Sanctum::actingAs($this->customer);
        // Too few points to spend yet: nothing comes off.
        app(LoyaltyService::class)->adjust($this->customer->id, 400, 'Welcome', $this->owner->id);
        $this->postJson('/api/v1/customer/agreements/'.$this->pub($a).'/points', ['use' => true])->assertOk()->assertJsonPath('payable', 300_000);

        app(LoyaltyService::class)->adjust($this->customer->id, 2_000, 'Apology', $this->owner->id);
        // 2,400 points are worth ₦2,400, but points pay at most half the delivery price.
        $this->postJson('/api/v1/customer/agreements/'.$this->pub($a).'/points', ['use' => true])->assertOk()
            ->assertJsonPath('payable', 150_000)->assertJsonPath('discount_lines.0.kind', 'loyalty')->assertJsonPath('points.balance', 2_400);

        $this->actingAs($this->customer)->get('/account/pay/'.$this->pub($a))->assertOk()->assertSee('Loyalty points')->assertSee('Stop using')->assertSee('Gift card');
        $before = $this->walletBalance('customer', $this->customer->id);
        app(PaymentService::class)->payWithWallet($a, $this->customer->id);
        $this->assertSame(150_000, $before - $this->walletBalance('customer', $this->customer->id));
        $this->assertSame(900, $this->points());

        app(EscrowService::class)->release($a);
        // ₦1,500 paid for the delivery earns 15 points.
        $this->assertSame(915, $this->points());
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->customer->id)->where('type', 'loyalty.earned')->count());
        $this->getJson('/api/v1/customer/loyalty')->assertOk()->assertJsonPath('balance', 915)->assertJsonPath('worth', 91_500)->assertJsonPath('can_spend', true);
        $this->assertLedgerBalanced();
    }

    public function test_a_refund_gives_the_points_back(): void
    {
        app(LoyaltyService::class)->adjust($this->customer->id, 1_000, 'Promo', $this->owner->id);
        $a = $this->lockedAgreement($this->customer->id, $this->op, $this->owner->id, 300_000);
        Sanctum::actingAs($this->customer);
        $this->postJson('/api/v1/customer/agreements/'.$this->pub($a).'/points', ['use' => true])->assertOk()->assertJsonPath('payable', 200_000);
        $before = $this->walletBalance('customer', $this->customer->id);
        app(PaymentService::class)->payWithWallet($a, $this->customer->id);
        $this->assertSame(0, $this->points());

        app(EscrowService::class)->refund($a);
        $this->assertSame(1_000, $this->points());
        $this->assertSame($before, $this->walletBalance('customer', $this->customer->id));
        $this->assertLedgerBalanced();
    }

    public function test_a_customer_buys_a_gift_card_and_a_friend_spends_it(): void
    {
        Sanctum::actingAs($this->customer);
        $this->postJson('/api/v1/customer/gift-cards', ['amount' => 50_000])->assertStatus(422);   // under ₦1,000
        $card = $this->postJson('/api/v1/customer/gift-cards', ['amount' => 500_000, 'recipient_name' => 'Bola', 'recipient_phone' => '08030000009', 'message' => 'Happy birthday'])
            ->assertCreated()->json();
        $this->assertMatchesRegularExpression('/^GC[A-Z2-9]{2}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $card['code']);
        $this->assertSame(4_500_000, $this->walletBalance('customer', $this->customer->id));
        $this->assertSame(1, DB::table('sms_log')->where('template_key', 'gift_card.received')->count());
        $this->assertNull(DB::table('gift_cards')->where('code_hash', $card['code'])->first());   // only the hash is kept
        $this->assertSame('Ending '.$card['last4'].' for Bola', $this->getJson('/api/v1/customer/gift-cards')->json('data.0.label'));

        $friend = $this->makeUser('Bola');
        $this->fundCustomer($friend->id, 1_000_000);
        $a = $this->lockedAgreement($friend->id, $this->op, $this->owner->id, 800_000);
        Sanctum::actingAs($friend);
        $this->postJson('/api/v1/customer/gift-cards/check', ['code' => strtolower(str_replace('-', ' ', $card['code']))])->assertOk()->assertJsonPath('balance', 500_000);
        $this->postJson('/api/v1/customer/agreements/'.$this->pub($a).'/gift-card', ['code' => 'GC00-0000-0000'])->assertStatus(422);
        $this->postJson('/api/v1/customer/agreements/'.$this->pub($a).'/gift-card', ['code' => $card['code']])->assertOk()
            ->assertJsonPath('payable', 300_000)->assertJsonPath('discount_lines.0.kind', 'gift_card');

        $promo = $this->platformBalance('promo_expense');
        app(PaymentService::class)->payWithWallet($a, $friend->id);
        $this->assertSame(700_000, $this->walletBalance('customer', $friend->id));
        $this->assertSame(0, (int) DB::table('gift_cards')->value('balance'));
        // The card paid back the platform's top-up, so it cost the platform nothing.
        $this->assertSame($promo, $this->platformBalance('promo_expense'));
        $this->postJson('/api/v1/customer/gift-cards/check', ['code' => $card['code']])->assertStatus(422)->assertJsonPath('message', 'That gift card has been used up.');

        // Cancelled and refunded: the friend's own money comes back to their wallet, the card's part to the card.
        app(EscrowService::class)->refund($a);
        $this->assertSame(1_000_000, $this->walletBalance('customer', $friend->id));
        $this->assertSame(500_000, (int) DB::table('gift_cards')->value('balance'));
        $this->assertSame($promo, $this->platformBalance('promo_expense'));
        $this->assertLedgerBalanced();
    }

    public function test_staff_set_the_rates_and_give_away_a_card(): void
    {
        $staff = $this->makeUser('Staff');
        foreach (['View promo code', 'Update promo code', 'Create promo code'] as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $staff->givePermissionTo(['View promo code', 'Update promo code', 'Create promo code']);
        $this->actingAs($staff)->get('/ops/promos')->assertOk()->assertSee('Loyalty points')->assertSee('No gift cards yet');
        $this->actingAs($staff)->post('/ops/loyalty', ['enabled' => '1', 'earn_per_100' => 2, 'point_value_naira' => '0.5', 'min_redeem' => 100, 'max_share_percent' => 25])
            ->assertSessionHas('success');
        $this->assertSame(['enabled' => true, 'earn_per_100' => 2, 'point_value' => 50, 'min_redeem' => 100, 'max_share_bp' => 2500], LoyaltyService::settings());

        $promo = $this->platformBalance('promo_expense');
        $r = $this->actingAs($staff)->post('/ops/gift-cards', ['amount_naira' => 2000, 'recipient_name' => 'Winner', 'recipient_email' => 'win@example.com']);
        $this->assertStringContainsString('Copy the code now', session('success'));
        $this->assertSame(200_000, $this->platformBalance('promo_expense') - $promo);
        $card = DB::table('gift_cards')->first();
        $this->assertSame(['issued', 200_000], [$card->source, (int) $card->balance]);
        $this->actingAs($staff)->post("/ops/gift-cards/{$card->id}/toggle")->assertSessionHas('success');
        $this->assertSame('disabled', DB::table('gift_cards')->value('status'));
        $this->actingAs($staff)->get('/ops/promos')->assertOk()->assertSee('Winner')->assertSee('stopped');
        $this->assertLedgerBalanced();
    }
}
