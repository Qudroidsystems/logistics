<?php

namespace Tests\Feature\Logistics;

use App\Jobs\SendPlainEmail;
use App\Models\User;
use App\Modules\Team\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Company teams (invite, accept, roles, drivers, vehicles, removal) and the emailed-code password reset. */
class TeamAndPasswordResetTest extends TestCase
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

    /** Invites by email and returns the code that was emailed. */
    private function invite(User $as, string $email, string $role): string
    {
        Sanctum::actingAs($as);
        $this->postJson('/api/v1/provider/team/invitations', ['email' => $email, 'role' => $role])->assertCreated();
        $job = Bus::dispatched(SendPlainEmail::class)->last();
        $this->assertSame($email, $job->to);
        preg_match('/inv_[A-Za-z0-9]{32}/', $job->body, $m);
        $this->assertNotEmpty($m, 'the email carries the code');

        return $m[0];
    }

    private function join(string $email, string $role, string $name = 'New Person'): User
    {
        $token = $this->invite($this->owner, $email, $role);
        $u = User::factory()->create(['email' => $email, 'name' => $name]);
        Sanctum::actingAs($u);
        $this->postJson('/api/v1/team/invitations/accept', ['token' => $token])->assertOk()->assertJsonPath('operator.type', 'company');

        return $u->fresh();
    }

    // ---------------------------------------------------------------- rules

    public function test_who_may_manage_whom(): void
    {
        $this->assertSame(TeamService::ROLES, TeamService::manageable('owner'));
        $this->assertFalse(TeamService::canManage('admin', 'admin'), 'an admin cannot create or change another admin');
        $this->assertTrue(TeamService::canManage('admin', 'dispatcher'));
        $this->assertTrue(TeamService::canManage('driver_manager', 'driver'));
        $this->assertFalse(TeamService::canManage('driver_manager', 'dispatcher'));
        $this->assertFalse(TeamService::canManage('dispatcher', 'driver'));
        $this->assertFalse(TeamService::canManage('owner', 'owner'), 'ownership is never granted by invitation');
    }

    // ---------------------------------------------------------------- invite and accept

    public function test_invited_person_joins_with_the_emailed_code(): void
    {
        $u = $this->join('dispatch@example.com', 'dispatcher', 'Dayo Dispatch');

        $this->assertSame($this->op, (int) $u->current_operator_id);
        $this->assertSame('dispatcher', DB::table('operator_members')->where(['operator_id' => $this->op, 'user_id' => $u->id])->value('role'));
        $this->assertNotNull(DB::table('operator_invitations')->where('email', 'dispatch@example.com')->value('accepted_at'));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->owner->id, 'type' => 'team.joined'])->exists(), 'the owner is told');
    }

    public function test_the_code_only_works_for_the_invited_email_and_only_once(): void
    {
        $token = $this->invite($this->owner, 'right@example.com', 'finance');

        Sanctum::actingAs(User::factory()->create(['email' => 'wrong@example.com']));
        $this->postJson('/api/v1/team/invitations/accept', ['token' => $token])->assertStatus(422);
        $this->postJson('/api/v1/team/invitations/accept', ['token' => 'inv_doesnotexist'])->assertStatus(422);

        Sanctum::actingAs(User::factory()->create(['email' => 'RIGHT@example.com']));   // email match ignores case
        $this->postJson('/api/v1/team/invitations/accept', ['token' => $token])->assertOk();
        $this->postJson('/api/v1/team/invitations/accept', ['token' => $token])->assertStatus(422);
    }

    public function test_expired_invitations_are_refused(): void
    {
        $token = $this->invite($this->owner, 'late@example.com', 'support');
        DB::table('operator_invitations')->update(['expires_at' => now()->subMinute()]);

        Sanctum::actingAs(User::factory()->create(['email' => 'late@example.com']));
        $this->postJson('/api/v1/team/invitations/accept', ['token' => $token])->assertStatus(422);
    }

    public function test_an_existing_user_can_accept_from_their_invitation_list(): void
    {
        $existing = User::factory()->create(['email' => 'known@example.com']);
        $this->invite($this->owner, 'known@example.com', 'finance');
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $existing->id, 'type' => 'team.invited'])->exists());

        Sanctum::actingAs($existing);
        $list = $this->getJson('/api/v1/team/invitations')->assertOk();
        $this->assertCount(1, $list->json());
        $this->postJson('/api/v1/team/invitations/accept', ['invitation' => $list->json('0.public_id')])->assertOk();
    }

    public function test_inviting_rules_are_enforced(): void
    {
        $admin = $this->join('admin@example.com', 'admin');
        $dispatcher = $this->join('disp@example.com', 'dispatcher');

        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/provider/team/invitations', ['email' => 'second-admin@example.com', 'role' => 'admin'])->assertStatus(422);
        $this->postJson('/api/v1/provider/team/invitations', ['email' => 'driver@example.com', 'role' => 'driver'])->assertCreated();
        $this->postJson('/api/v1/provider/team/invitations', ['email' => 'disp@example.com', 'role' => 'finance'])->assertStatus(422);   // already on the team

        Sanctum::actingAs($dispatcher);
        $this->getJson('/api/v1/provider/team')->assertForbidden();
        $this->postJson('/api/v1/provider/team/invitations', ['email' => 'x@example.com', 'role' => 'driver'])->assertForbidden();
    }

    public function test_a_new_invite_replaces_the_old_one_and_can_be_revoked(): void
    {
        $first = $this->invite($this->owner, 'twice@example.com', 'support');
        $this->invite($this->owner, 'twice@example.com', 'finance');
        $this->assertSame(1, DB::table('operator_invitations')->where('email', 'twice@example.com')->count());

        Sanctum::actingAs(User::factory()->create(['email' => 'twice@example.com']));
        $this->postJson('/api/v1/team/invitations/accept', ['token' => $first])->assertStatus(422);   // the old code is dead

        Sanctum::actingAs($this->owner);
        $pid = DB::table('operator_invitations')->value('public_id');
        $this->deleteJson("/api/v1/provider/team/invitations/{$pid}")->assertOk();
        $this->assertSame(0, DB::table('operator_invitations')->count());
    }

    // ---------------------------------------------------------------- members and drivers

    public function test_roles_change_and_removal_cuts_access(): void
    {
        $u = $this->join('fin@example.com', 'finance');

        Sanctum::actingAs($this->owner);
        $this->putJson("/api/v1/provider/team/members/{$u->id}/role", ['role' => 'dispatcher'])->assertOk();
        $this->assertSame('dispatcher', DB::table('operator_members')->where('user_id', $u->id)->value('role'));
        $this->putJson("/api/v1/provider/team/members/{$this->owner->id}/role", ['role' => 'admin'])->assertStatus(422);   // the owner role is fixed
        $this->deleteJson("/api/v1/provider/team/members/{$this->owner->id}")->assertStatus(422);

        $this->deleteJson("/api/v1/provider/team/members/{$u->id}")->assertOk();
        $this->assertNull(DB::table('users')->where('id', $u->id)->value('current_operator_id'));

        Sanctum::actingAs($u);
        $this->getJson('/api/v1/provider/wallet')->assertForbidden();   // an ordinary provider route: access is gone
    }

    public function test_driver_approval_and_vehicle_assignment(): void
    {
        $d1 = $this->join('d1@example.com', 'driver', 'Driver One');
        $d2 = $this->join('d2@example.com', 'driver', 'Driver Two');
        $vehicleType = (int) DB::table('vehicle_types')->where('code', 'motorbike')->value('id');
        $vehicle = (string) Str::ulid();
        $vid = DB::table('vehicles')->insertGetId([
            'public_id' => $vehicle, 'operator_id' => $this->op, 'vehicle_type_id' => $vehicleType, 'plate' => 'KGL111AA', 'ownership' => 'operator_owned', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertSame('applied', DB::table('driver_profiles')->where('user_id', $d1->id)->value('status'));

        Sanctum::actingAs($this->owner);
        // An unapproved driver cannot be given a vehicle.
        $this->putJson("/api/v1/provider/team/members/{$d1->id}/vehicle", ['vehicle_id' => $vehicle])->assertStatus(422);

        $this->putJson("/api/v1/provider/team/members/{$d1->id}/driver-status", ['status' => 'active'])->assertOk();
        $this->putJson("/api/v1/provider/team/members/{$d2->id}/driver-status", ['status' => 'active'])->assertOk();
        $this->putJson("/api/v1/provider/team/members/{$d1->id}/vehicle", ['vehicle_id' => $vehicle])->assertOk();
        $this->assertSame($vid, (int) DB::table('driver_profiles')->where('user_id', $d1->id)->value('current_vehicle_id'));

        // The same bike moves to the second driver; the first no longer has it.
        $this->putJson("/api/v1/provider/team/members/{$d2->id}/vehicle", ['vehicle_id' => $vehicle])->assertOk();
        $this->assertNull(DB::table('driver_profiles')->where('user_id', $d1->id)->value('current_vehicle_id'));
        $this->assertSame($vid, (int) DB::table('driver_profiles')->where('user_id', $d2->id)->value('current_vehicle_id'));
        $this->assertSame(1, DB::table('vehicle_assignments')->where('vehicle_id', $vid)->whereNull('ended_at')->count());

        $this->putJson("/api/v1/provider/team/members/{$d2->id}/driver-status", ['status' => 'suspended'])->assertOk();
        $this->assertSame('offline', DB::table('driver_profiles')->where('user_id', $d2->id)->value('availability'));

        $this->deleteJson("/api/v1/provider/team/members/{$d1->id}")->assertOk();
        $this->assertSame('offboarded', DB::table('driver_profiles')->where('user_id', $d1->id)->value('status'));
    }

    public function test_a_vehicle_from_another_provider_cannot_be_assigned(): void
    {
        $d = $this->join('d@example.com', 'driver');
        [$otherOp] = $this->makeProvider('company', 'Other Haulage');
        $foreign = (string) Str::ulid();
        DB::table('vehicles')->insert([
            'public_id' => $foreign, 'operator_id' => $otherOp, 'vehicle_type_id' => (int) DB::table('vehicle_types')->value('id'), 'plate' => 'ZZZ999', 'ownership' => 'operator_owned',
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        Sanctum::actingAs($this->owner);
        $this->putJson("/api/v1/provider/team/members/{$d->id}/driver-status", ['status' => 'active'])->assertOk();

        $this->putJson("/api/v1/provider/team/members/{$d->id}/vehicle", ['vehicle_id' => $foreign])->assertStatus(422);
    }

    // ---------------------------------------------------------------- password reset

    private function requestCode(string $email): string
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $email])->assertOk();
        $job = Bus::dispatched(SendPlainEmail::class)->last();
        preg_match('/Your code is (\d{6})/', $job->body, $m);

        return $m[1];
    }

    public function test_forgot_password_answers_the_same_for_unknown_emails_and_sends_nothing(): void
    {
        User::factory()->create(['email' => 'real@example.com']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'real@example.com'])->assertOk()->json();
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@example.com'])->assertOk()->json();

        $this->assertSame($known, $unknown);
        Bus::assertDispatchedTimes(SendPlainEmail::class, 1);
    }

    public function test_reset_with_the_code_changes_the_password_and_signs_everyone_out(): void
    {
        $user = User::factory()->create(['email' => 'forgot@example.com', 'password' => Hash::make('old-password-1')]);
        $user->createToken('phone');
        $code = $this->requestCode('forgot@example.com');
        $this->assertNotSame($code, DB::table('password_reset_tokens')->where('email', 'forgot@example.com')->value('token'), 'stored hashed');

        $this->postJson('/api/v1/auth/reset-password', ['email' => 'forgot@example.com', 'code' => $code, 'password' => 'brand-new-pass'])->assertOk();

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
        $this->postJson('/api/v1/auth/reset-password', ['email' => 'forgot@example.com', 'code' => $code, 'password' => 'another-pass-2'])->assertStatus(422);   // one use
        $this->postJson('/api/v1/auth/login', ['login' => 'forgot@example.com', 'password' => 'brand-new-pass'])->assertOk();
    }

    public function test_wrong_expired_and_repeated_codes_are_refused(): void
    {
        User::factory()->create(['email' => 'f2@example.com']);
        $code = $this->requestCode('f2@example.com');
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/v1/auth/reset-password', ['email' => 'f2@example.com', 'code' => $wrong, 'password' => 'brand-new-pass'])->assertStatus(422);

        DB::table('password_reset_tokens')->where('email', 'f2@example.com')->update(['created_at' => now()->subMinutes(31)]);
        $this->postJson('/api/v1/auth/reset-password', ['email' => 'f2@example.com', 'code' => $code, 'password' => 'brand-new-pass'])->assertStatus(422);

        // Five wrong tries lock guessing even if the next one is right.
        RateLimiter::clear('pwd-reset:f2@example.com');
        DB::table('password_reset_tokens')->where('email', 'f2@example.com')->update(['created_at' => now()]);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/reset-password', ['email' => 'f2@example.com', 'code' => $wrong, 'password' => 'brand-new-pass'])->assertStatus(422);
        }
        $this->postJson('/api/v1/auth/reset-password', ['email' => 'f2@example.com', 'code' => $code, 'password' => 'brand-new-pass'])->assertStatus(429);
    }

    public function test_only_three_codes_per_hour_per_email(): void
    {
        User::factory()->create(['email' => 'spam@example.com']);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'spam@example.com'])->assertOk();
        }
        Bus::assertDispatchedTimes(SendPlainEmail::class, 3);
    }
}
