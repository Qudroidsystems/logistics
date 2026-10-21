<?php

namespace Tests\Feature\Logistics;

use App\Jobs\SendPlainEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Web sign-up, and joining a company's team from an invitation on the web and from the apps. */
class SignUpAndInvitationsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Bus::fake([SendPlainEmail::class]);
        [$this->op, $this->owner] = $this->makeProvider();
    }

    private function invite(string $email, string $role): string
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/provider/team/invitations', ['email' => $email, 'role' => $role])->assertCreated();
        preg_match('/inv_[A-Za-z0-9]{32}/', Bus::dispatched(SendPlainEmail::class)->last()->body, $m);

        return $m[0];
    }

    public function test_web_sign_up_makes_a_customer_and_lands_by_choice(): void
    {
        $this->get('/register')->assertOk()->assertSee('Create your account');

        $this->post('/register', [
            'name' => 'Ada Driver', 'email' => 'Ada@Example.com', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass', 'intent' => 'team',
        ])->assertRedirect(route('account.invitations'));

        $u = User::where('email', 'ada@example.com')->first();
        $this->assertNotNull($u, 'the email is stored in lower case');
        $this->assertTrue(DB::table('customer_profiles')->where('user_id', $u->id)->exists(), 'same account as the app sign-up');
        $this->assertAuthenticatedAs($u);
    }

    public function test_web_sign_up_refuses_a_taken_phone(): void
    {
        User::factory()->create(['phone_number' => '08012345678']);

        $this->post('/register', [
            'name' => 'Bola', 'email' => 'bola@example.com', 'phone' => '+2348012345678', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_invited_driver_accepts_on_the_web(): void
    {
        $this->invite('rider@example.com', 'driver');
        $u = User::factory()->create(['email' => 'rider@example.com']);
        $inv = DB::table('operator_invitations')->where('email', 'rider@example.com')->value('public_id');

        $this->actingAs($u)->get('/account/invitations')->assertOk()->assertSee($inv, false);
        $this->actingAs($u)->post('/account/invitations/accept', ['invitation' => $inv])
            ->assertRedirect(route('account.invitations'))->assertSessionHas('success');

        $this->assertSame('driver', DB::table('operator_members')->where(['operator_id' => $this->op, 'user_id' => $u->id])->value('role'));
        $this->assertSame('applied', DB::table('driver_profiles')->where(['operator_id' => $this->op, 'user_id' => $u->id])->value('status'), 'the company approves them next');
    }

    public function test_a_code_for_someone_else_is_refused_on_the_web(): void
    {
        $token = $this->invite('someone@example.com', 'dispatcher');
        $other = User::factory()->create(['email' => 'other@example.com']);

        $this->actingAs($other)->post('/account/invitations/accept', ['token' => $token])->assertSessionHas('error');
        $this->assertFalse(DB::table('operator_members')->where(['operator_id' => $this->op, 'user_id' => $other->id])->exists());
    }

    public function test_the_apps_list_open_invitations(): void
    {
        $this->invite('rider2@example.com', 'driver');
        $u = User::factory()->create(['email' => 'rider2@example.com']);
        Sanctum::actingAs($u);

        $this->getJson('/api/v1/team/invitations')->assertOk()->assertJsonPath('invitations.0.role', 'driver')->assertJsonCount(1, 'invitations');
    }
}
