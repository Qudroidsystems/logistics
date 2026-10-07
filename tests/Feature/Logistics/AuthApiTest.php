<?php

namespace Tests\Feature\Logistics;

use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** Token sign-in for the apps: register, log in by email or phone, who am I, log out, change password. */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $over = [])
    {
        return $this->postJson('/api/v1/auth/register', $over + [
            'name' => 'Ada Okoro', 'email' => 'ada@example.com', 'phone' => '+2348012345678', 'password' => 'correct-horse', 'device_name' => 'test-phone',
        ]);
    }

    public function test_phone_numbers_are_normalised(): void
    {
        $this->assertSame('08012345678', AuthController::normalizePhone('+234 801 234 5678'));
        $this->assertSame('08012345678', AuthController::normalizePhone('2348012345678'));
        $this->assertSame('08012345678', AuthController::normalizePhone('0801-234-5678'));
    }

    public function test_register_returns_a_working_token_and_creates_a_customer_profile(): void
    {
        $res = $this->register()->assertCreated()->assertJsonPath('customer', true)->assertJsonPath('user.phone', '08012345678');
        $token = $res->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.email', 'ada@example.com');
        $this->assertSame(1, DB::table('customer_profiles')->count());
        $this->assertNotSame('correct-horse', User::first()->password, 'the password is hashed');
    }

    public function test_duplicate_email_or_phone_is_refused(): void
    {
        $this->register()->assertCreated();
        $this->register(['phone' => '08099999999'])->assertStatus(422);                       // same email
        $this->register(['email' => 'other@example.com', 'phone' => '08012345678'])->assertStatus(422); // same phone, other format
        $this->register(['email' => 'short@example.com', 'phone' => '123'])->assertStatus(422);
        $this->register(['email' => 'weak@example.com', 'phone' => null, 'password' => 'short'])->assertStatus(422);
    }

    public function test_login_by_email_and_by_phone(): void
    {
        $this->register()->assertCreated();

        $this->postJson('/api/v1/auth/login', ['login' => 'ADA@example.com', 'password' => 'correct-horse'])->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/v1/auth/login', ['login' => '2348012345678', 'password' => 'correct-horse'])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_wrong_credentials_give_the_same_answer_whether_or_not_the_account_exists(): void
    {
        $this->register()->assertCreated();

        $wrongPassword = $this->postJson('/api/v1/auth/login', ['login' => 'ada@example.com', 'password' => 'nope-nope-nope'])->assertStatus(401);
        $noSuchUser = $this->postJson('/api/v1/auth/login', ['login' => 'ghost@example.com', 'password' => 'nope-nope-nope'])->assertStatus(401);
        $this->assertSame($wrongPassword->json(), $noSuchUser->json());
    }

    public function test_repeated_failures_are_throttled_then_cleared_on_success(): void
    {
        $this->register()->assertCreated();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['login' => 'ada@example.com', 'password' => 'bad-bad-bad'])->assertStatus(401);
        }
        $this->postJson('/api/v1/auth/login', ['login' => 'ada@example.com', 'password' => 'correct-horse'])->assertStatus(429);   // even the right password waits

        RateLimiter::clear('api-login:ada@example.com|127.0.0.1');
        $this->postJson('/api/v1/auth/login', ['login' => 'ada@example.com', 'password' => 'correct-horse'])->assertOk();
    }

    public function test_disabled_accounts_cannot_sign_in(): void
    {
        $this->register()->assertCreated();
        DB::table('users')->where('email', 'ada@example.com')->update(['status' => 'banned']);

        $this->postJson('/api/v1/auth/login', ['login' => 'ada@example.com', 'password' => 'correct-horse'])->assertStatus(403);
    }

    public function test_protected_routes_need_a_token_and_logout_revokes_it(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/notifications')->assertUnauthorized();

        $token = $this->register()->json('token');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_me_lists_provider_memberships(): void
    {
        $token = $this->register()->json('token');
        $this->withToken($token)->postJson('/api/v1/provider/register', ['type' => 'independent_driver', 'legal_name' => 'Ada Okoro', 'display_name' => 'Ada Rider'])->assertCreated();

        $me = $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();
        $this->assertSame('independent_driver', $me->json('operators.0.type'));
        $this->assertSame('owner', $me->json('operators.0.role'));
        $this->assertTrue($me->json('operators.0.current'));
    }

    public function test_changing_the_password_signs_out_other_devices_only(): void
    {
        $first = $this->register()->json('token');
        $second = $this->postJson('/api/v1/auth/login', ['login' => 'ada@example.com', 'password' => 'correct-horse', 'device_name' => 'tablet'])->json('token');

        $this->withToken($first)->postJson('/api/v1/auth/password', ['current_password' => 'wrong', 'password' => 'a-new-password'])->assertStatus(422);
        $this->withToken($first)->postJson('/api/v1/auth/password', ['current_password' => 'correct-horse', 'password' => 'a-new-password'])->assertOk();

        $this->assertTrue(Hash::check('a-new-password', User::first()->password));
        $this->assertSame(1, DB::table('personal_access_tokens')->count(), 'only the device that changed it stays signed in');
        $this->postJson('/api/v1/auth/login', ['login' => 'ada@example.com', 'password' => 'a-new-password'])->assertOk();
    }
}
