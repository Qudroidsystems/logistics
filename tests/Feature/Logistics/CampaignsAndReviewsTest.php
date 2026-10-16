<?php

namespace Tests\Feature\Logistics;

use App\Jobs\SendPlainEmail;
use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Marketing\CampaignService;
use App\Modules\Marketing\ReviewRequestService;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Email and SMS campaigns to customers who agreed to offers, and Google review requests after deliveries. */
class CampaignsAndReviewsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake([SendPlainEmail::class]);
        $this->seedFoundation();
        $this->mock(DispatchService::class, fn ($m) => $m->shouldReceive('start')->andReturn(1));
        [$this->op, $this->owner] = $this->makeProvider();
    }

    /** A customer with a phone, opted in or not, who has paid this provider for $paid deliveries. */
    private function customer(string $name, bool $offers, int $paid = 0, ?int $op = null, string $phone = '0803'): User
    {
        $u = $this->makeUser($name);
        DB::table('users')->where('id', $u->id)->update(['phone_number' => $phone.random_int(1000000, 9999999)]);
        CampaignService::setOptIn($u->id, $offers);
        if ($paid) {
            $this->fundCustomer($u->id, 5_000_000);
            for ($i = 0; $i < $paid; $i++) {
                $a = $this->lockedAgreement($u->id, $op ?? $this->op, $this->owner->id, 200_000);
                app(PaymentService::class)->payWithWallet($a, $u->id);
            }
        }

        return $u->fresh();
    }

    public function test_only_customers_who_agreed_are_reached_and_a_provider_reaches_only_its_own(): void
    {
        $this->customer('Ada Obi', true, 1);
        $this->customer('Bola', false, 1);      // said no
        $this->customer('Chi', true);           // never booked
        [$other, $otherOwner] = $this->makeProvider('company', 'Other Co');
        $this->customer('Dayo', true, 1, $other);
        $lapsed = $this->customer('Efe', true, 1);
        DB::table('orders')->where('customer_id', $lapsed->id)->update(['created_at' => now()->subDays(90)]);

        $svc = app(CampaignService::class);
        $this->assertSame([2, 1, 1, 0], [$svc->count($this->op, 'all', 'sms'), $svc->count($this->op, 'recent', 'sms'), $svc->count($this->op, 'lapsed', 'sms'), $svc->count($this->op, 'new', 'sms')]);
        $this->assertSame([4, 1], [$svc->count(null, 'all', 'sms'), $svc->count(null, 'new', 'sms')]);

        $this->actingAs($this->owner)->get('/provider/campaigns')->assertOk()->assertSee('Who you can reach')->assertSee('Google reviews');
    }

    public function test_a_provider_writes_an_sms_tests_it_and_sends_it_in_batches(): void
    {
        $ada = $this->customer('Ada Obi', true, 1);
        $bola = $this->customer('Bola Ade', true, 1);
        DB::table('promo_codes')->insert(['code' => 'BACK10', 'type' => 'fixed', 'value' => 50_000, 'per_user_limit' => 1, 'operator_id' => $this->op, 'funded_by' => 'operator', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $this->owner->id)->update(['phone_number' => '08039990000']);
        $this->owner = $this->owner->fresh();

        $this->actingAs($this->owner)->post('/provider/campaigns', ['name' => 'Come back', 'channel' => 'sms', 'audience' => 'all', 'body' => 'Hi {first_name}, use {code} for ₦500 off.', 'promo_code' => 'nope'])
            ->assertSessionHas('error', 'That is not one of your live promo codes.');
        $this->actingAs($this->owner)->post('/provider/campaigns', ['name' => 'Come back', 'channel' => 'sms', 'audience' => 'all', 'body' => 'Hi {first_name}, use {code} for ₦500 off.', 'promo_code' => 'back10'])
            ->assertRedirect('/provider/campaigns');
        $c = DB::table('marketing_campaigns')->first();
        $this->assertSame([$this->op, 'draft'], [(int) $c->operator_id, $c->status]);

        $this->actingAs($this->owner)->post("/provider/campaigns/{$c->id}/test")->assertSessionHas('success');
        $this->assertSame(1, DB::table('sms_log')->where('template_key', 'campaign')->whereNull('user_id')->count());
        $this->assertSame('Swift Haulage: Hi Ada, use BACK10 for ₦500 off. Stop offers in the app settings.', app(CampaignService::class)->render($c, $ada));

        $this->actingAs($this->owner)->post("/provider/campaigns/{$c->id}/send")->assertSessionHas('success');
        $this->artisan('campaigns:send')->assertSuccessful();
        $this->assertSame(['sent', 2], [DB::table('marketing_campaigns')->value('status'), (int) DB::table('marketing_campaigns')->value('sent')]);
        $this->assertSame(1, DB::table('sms_log')->where('template_key', 'campaign')->where('user_id', $ada->id)->count());

        // A sent campaign cannot be changed or sent again.
        $this->actingAs($this->owner)->post("/provider/campaigns/{$c->id}/send")->assertSessionHas('error');
        $this->actingAs($this->owner)->post("/provider/campaigns/{$c->id}", ['name' => 'X', 'channel' => 'sms', 'audience' => 'all', 'body' => 'x'])->assertSessionHas('error', 'Only a draft can be changed.');

        // Another provider cannot touch it.
        [, $otherOwner] = $this->makeProvider('company', 'Other Co');
        $this->actingAs($otherOwner)->post("/provider/campaigns/{$c->id}/test")->assertSessionHas('error', 'Campaign not found.');
    }

    public function test_a_platform_email_carries_a_working_stop_link_and_people_who_stopped_are_skipped(): void
    {
        $ada = $this->customer('Ada Obi', true, 1);
        $bola = $this->customer('Bola Ade', true);
        $id = app(CampaignService::class)->save(null, ['name' => 'News', 'channel' => 'email', 'audience' => 'all', 'subject' => 'Big news', 'body' => 'Hello {first_name}!'], $this->owner->id);
        app(CampaignService::class)->schedule(null, $id, null);
        app(CampaignService::class)->run();   // makes the list and sends
        Queue::assertPushed(SendPlainEmail::class, 2);
        $mail = null;
        Queue::assertPushed(SendPlainEmail::class, function ($j) use ($ada, &$mail) {
            if ($j->to === $ada->email) {
                $mail = $j;
            }

            return true;
        });
        $this->assertSame('Big news', $mail->subject);
        preg_match('#https?://\S+/unsubscribe/\S+#', $mail->body, $m);
        $this->get('/unsubscribe/'.$ada->id)->assertForbidden();    // unsigned
        $this->get($m[0])->assertOk()->assertSee('You will not get offers any more');
        $this->assertFalse((bool) DB::table('customer_profiles')->where('user_id', $ada->id)->value('marketing_opt_in'));

        // Scheduled for later: nothing goes until it is due; stopped people are skipped.
        $later = app(CampaignService::class)->save(null, ['name' => 'Later', 'channel' => 'email', 'audience' => 'all', 'subject' => 'Soon', 'body' => 'Hi'], $this->owner->id);
        app(CampaignService::class)->schedule(null, $later, now()->addHour()->toDateTimeString());
        $this->assertSame(0, app(CampaignService::class)->run());
        $this->travel(2)->hours();
        $this->assertSame(1, app(CampaignService::class)->run());
        $this->assertSame(1, DB::table('campaign_recipients')->where('campaign_id', $later)->count());
    }

    public function test_customers_say_yes_or_no_to_offers_in_the_app(): void
    {
        $this->postJson('/api/v1/auth/register', ['name' => 'Ngozi', 'email' => 'ngozi@example.com', 'password' => 'secret-pass-1', 'offers' => true])->assertCreated();
        $u = User::where('email', 'ngozi@example.com')->first();
        Sanctum::actingAs($u);
        $this->getJson('/api/v1/notifications/preferences')->assertOk()->assertJsonPath('offers', true);
        $this->putJson('/api/v1/notifications/preferences', ['offers' => false])->assertOk()->assertJsonPath('offers', false);
        $this->assertNotNull(DB::table('customer_profiles')->where('user_id', $u->id)->value('marketing_changed_at'));
    }

    public function test_customers_are_asked_for_a_google_review_once_in_ninety_days(): void
    {
        $this->actingAs($this->owner)->post('/provider/reviews', ['enabled' => '1', 'url' => '', 'delay_hours' => 2])->assertSessionHasErrors('url');
        $this->actingAs($this->owner)->post('/provider/reviews', ['enabled' => '1', 'url' => 'https://g.page/r/swift/review', 'delay_hours' => 2, 'sms' => '1'])->assertSessionHas('success');

        $ada = $this->customer('Ada Obi', false, 2);   // reviews are not marketing: asked even without offers
        $agreements = DB::table('agreements')->where('customer_id', $ada->id)->orderBy('id')->pluck('id');
        app(EscrowService::class)->release($agreements[0]);
        $this->assertSame(1, DB::table('review_requests')->where('status', 'waiting')->count());
        $this->artisan('reviews:send')->assertSuccessful();
        $this->assertSame(0, DB::table('notifications')->where('type', 'review.request')->count());   // not due yet

        $this->travel(3)->hours();
        $this->artisan('reviews:send')->assertSuccessful();
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $ada->id)->where('type', 'review.request')->count());
        $this->assertSame(1, DB::table('sms_log')->where('template_key', 'review.request')->count());

        app(EscrowService::class)->release($agreements[1]);
        $this->travel(3)->hours();
        app(ReviewRequestService::class)->run();
        $this->assertSame(['skipped', 'Asked recently'], array_values((array) DB::table('review_requests')->orderByDesc('id')->first(['status', 'note'])));
        $this->assertSame(1, DB::table('notifications')->where('type', 'review.request')->count());
    }

    public function test_staff_see_the_platform_campaign_page(): void
    {
        $u = $this->makeUser('Staff');
        foreach (['View promo code', 'Create promo code'] as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $u->givePermissionTo(['View promo code', 'Create promo code']);
        $this->actingAs($u)->get('/ops/campaigns')->assertOk()->assertSee('New campaign')->assertDontSee('Google reviews');
        $this->actingAs($u)->post('/ops/campaigns', ['name' => 'Hi', 'channel' => 'email', 'audience' => 'all', 'body' => 'Hello'])->assertSessionHas('error', 'An email needs a subject.');
        $this->actingAs($u)->post('/ops/campaigns', ['name' => 'Hi', 'channel' => 'email', 'audience' => 'all', 'subject' => 'Hello', 'body' => 'Hello'])->assertRedirect('/ops/campaigns');
        $id = (int) DB::table('marketing_campaigns')->value('id');
        $this->actingAs($u)->post("/ops/campaigns/{$id}/send")->assertSessionHas('error', 'Nobody in this audience has agreed to receive offers yet.');
        $this->actingAs($u)->get('/ops/campaigns?edit='.$id)->assertOk()->assertSee('Edit campaign');
    }
}
