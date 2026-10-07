<?php

namespace Tests\Feature\Logistics;

use App\Jobs\SendPlainEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function code(): string
    {
        $job = Bus::dispatched(SendPlainEmail::class)->last();
        preg_match('/Your code is (\d{6})/', $job->body, $m);

        return $m[1];
    }

    public function test_code_verifies_the_email_once(): void
    {
        Bus::fake([SendPlainEmail::class]);
        $u = User::factory()->create(['email_verified_at' => null]);
        Sanctum::actingAs($u);

        $this->postJson('/api/v1/auth/email/send-code')->assertOk()->assertJsonPath('verified', false);
        $code = $this->code();
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/v1/auth/email/verify', ['code' => $wrong])->assertStatus(422);
        $this->assertNull($u->fresh()->email_verified_at);
        $this->postJson('/api/v1/auth/email/verify', ['code' => $code])->assertOk()->assertJsonPath('verified', true);
        $this->assertNotNull($u->fresh()->email_verified_at);
    }

    public function test_already_verified_sends_nothing(): void
    {
        Bus::fake([SendPlainEmail::class]);
        Sanctum::actingAs(User::factory()->create(['email_verified_at' => now()]));
        $this->postJson('/api/v1/auth/email/send-code')->assertOk()->assertJsonPath('verified', true);
        Bus::assertNotDispatched(SendPlainEmail::class);
    }

    public function test_five_wrong_tries_lock_the_code(): void
    {
        Bus::fake([SendPlainEmail::class]);
        Sanctum::actingAs(User::factory()->create(['email_verified_at' => null]));
        $this->postJson('/api/v1/auth/email/send-code')->assertOk();
        $code = $this->code();
        $wrong = $code === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/email/verify', ['code' => $wrong])->assertStatus(422);
        }
        $this->postJson('/api/v1/auth/email/verify', ['code' => $code])->assertStatus(429);
    }

    public function test_only_three_codes_per_hour(): void
    {
        Bus::fake([SendPlainEmail::class]);
        Sanctum::actingAs(User::factory()->create(['email_verified_at' => null]));
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/email/send-code')->assertOk();
        }
        $this->postJson('/api/v1/auth/email/send-code')->assertStatus(429);
    }
}
