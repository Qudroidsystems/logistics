<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Identity\Security\Totp;
use App\Modules\Identity\Security\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Two-step sign-in (browser and app), who must use it, and the data-change audit trail. */
class SecurityTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private function person(array $perms = [], string $password = 'Secret123!'): User
    {
        $u = User::factory()->create(['must_change_password' => false, 'password' => Hash::make($password)]);
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
        if ($perms) {
            $u->givePermissionTo($perms);
        }

        return $u;
    }

    /** Turns it on the way a person does, and returns the secret. */
    private function enable(User $u): string
    {
        $this->actingAs($u)->post('/security/two-factor')->assertRedirect('/security');
        $secret = Crypt::decryptString($u->fresh()->two_factor_secret);
        $this->post('/security/two-factor/confirm', ['code' => Totp::at($secret, Totp::step())])->assertRedirect('/security');
        auth()->logout();
        $this->flushSession();

        return $secret;
    }

    private function code(string $secret, int $offset = 0): string
    {
        return Totp::at($secret, Totp::step() + $offset);
    }

    public function test_codes_match_the_rfc_6238_test_vectors(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');
        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $secret);
        $this->assertSame('287082', Totp::at($secret, Totp::step(59)));
        $this->assertSame('081804', Totp::at($secret, Totp::step(1111111109)));
        $this->assertSame('005924', Totp::at($secret, Totp::step(1234567890)));
        $this->assertSame(Totp::step(1234567890), Totp::verify($secret, '005 924', null, 1, 1234567890));
        $this->assertNull(Totp::verify($secret, '005924', Totp::step(1234567890), 1, 1234567890), 'a used step cannot be replayed');
        $this->assertSame('12345678901234567890', Totp::base32Decode($secret));
    }

    public function test_setting_it_up_needs_a_working_code_and_gives_recovery_codes(): void
    {
        $u = $this->person();
        $this->actingAs($u)->get('/security')->assertOk()->assertSee('Two-step sign-in is off');
        $this->post('/security/two-factor')->assertRedirect('/security');
        $this->get('/security')->assertOk()->assertSee('Set up your authenticator app')->assertSee('<svg', false);

        $secret = Crypt::decryptString($u->fresh()->two_factor_secret);
        $this->post('/security/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertNull($u->fresh()->two_factor_confirmed_at);

        $this->post('/security/two-factor/confirm', ['code' => $this->code($secret)])->assertSessionHas('recovery_codes');
        $this->assertNotNull($u->fresh()->two_factor_confirmed_at);
        $this->assertCount(8, json_decode($u->fresh()->two_factor_recovery_codes, true));
        $this->assertStringNotContainsString($secret, $u->fresh()->toJson(), 'the secret never leaves in JSON');
        $this->assertSame(1, DB::table('audit_logs')->where(['action' => 'two_factor.enabled', 'subject_id' => $u->id, 'user_id' => $u->id])->count());
    }

    public function test_browser_sign_in_asks_for_the_code_after_the_password(): void
    {
        $u = $this->person();
        $secret = $this->enable($u);

        $this->post('/login', ['email' => $u->email, 'password' => 'Secret123!'])->assertRedirect('/two-factor/challenge');
        $this->assertGuest();
        $this->get('/two-factor/challenge')->assertOk()->assertSee('Enter your sign-in code');

        $this->post('/two-factor/challenge', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // The step used when it was turned on cannot be used again; the next one works.
        $this->post('/two-factor/challenge', ['code' => $this->code($secret, 1)])->assertRedirect();
        $this->assertAuthenticatedAs($u);

        // Replaying the same code on a new sign-in fails.
        auth()->logout();
        $this->flushSession();
        $this->post('/login', ['email' => $u->email, 'password' => 'Secret123!'])->assertRedirect('/two-factor/challenge');
        $this->post('/two-factor/challenge', ['code' => $this->code($secret, 1)])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_wrong_password_still_fails_and_too_many_wrong_codes_end_the_attempt(): void
    {
        $u = $this->person();
        $this->enable($u);
        $this->post('/login', ['email' => $u->email, 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertNull(session('login.two_factor'));

        $this->post('/login', ['email' => $u->email, 'password' => 'Secret123!'])->assertRedirect('/two-factor/challenge');
        for ($i = 0; $i < 4; $i++) {
            $this->post('/two-factor/challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        }
        $this->post('/two-factor/challenge', ['code' => '000000'])->assertRedirect('/login');
        $this->get('/two-factor/challenge')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_a_recovery_code_works_once(): void
    {
        $u = $this->person();
        $this->actingAs($u)->post('/security/two-factor');
        $secret = Crypt::decryptString($u->fresh()->two_factor_secret);
        $codes = $this->post('/security/two-factor/confirm', ['code' => $this->code($secret)])->getSession()->get('recovery_codes');
        auth()->logout();
        $this->flushSession();

        $this->post('/login', ['email' => $u->email, 'password' => 'Secret123!']);
        $this->post('/two-factor/challenge', ['code' => strtolower($codes[0])])->assertRedirect();
        $this->assertAuthenticatedAs($u);
        $this->assertSame(7, app(TwoFactorService::class)->recoveryCodesLeft($u->fresh()));

        auth()->logout();
        $this->flushSession();
        $this->post('/login', ['email' => $u->email, 'password' => 'Secret123!']);
        $this->post('/two-factor/challenge', ['code' => $codes[0]])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_turning_it_off_needs_the_password(): void
    {
        $u = $this->person();
        $this->enable($u);
        $this->actingAs($u)->delete('/security/two-factor', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertNotNull($u->fresh()->two_factor_confirmed_at);
        $this->delete('/security/two-factor', ['password' => 'Secret123!'])->assertRedirect('/security');
        $this->assertNull($u->fresh()->two_factor_confirmed_at);
        $this->assertNull($u->fresh()->two_factor_secret);
    }

    public function test_a_password_reset_link_does_not_skip_the_code(): void
    {
        $u = $this->person();
        $this->enable($u);
        $token = Password::broker()->createToken($u);
        $this->post('/password/reset', ['token' => $token, 'email' => $u->email, 'password' => 'NewSecret123!', 'password_confirmation' => 'NewSecret123!'])->assertRedirect();
        $this->assertGuest();
        $this->assertTrue(Hash::check('NewSecret123!', $u->fresh()->password));
    }

    public function test_staff_rule_sends_staff_to_set_it_up_but_not_customers(): void
    {
        $this->seedFoundation();
        $admin = $this->person(['Manage security settings']);
        $admin->assignRole(Role::findOrCreate('Operations Manager', 'web'));
        $customer = $this->person();

        app(TwoFactorService::class)->putSetting(TwoFactorService::KEY_STAFF, true, $admin->id);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/security');
        $this->getJson('/notifications')->assertForbidden()->assertJsonPath('redirect', route('security.index'));
        $this->get('/security')->assertOk()->assertSee('All platform staff must use two-step sign-in');
        // Required people cannot turn it off.
        $this->enable($admin);
        $this->actingAs($admin)->delete('/security/two-factor', ['password' => 'Secret123!'])->assertSessionHasErrors('password');
        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);
        $this->get('/admin/security')->assertOk()->assertSee('Sign-in security');

        $this->actingAs($customer)->get('/security')->assertOk()->assertDontSee('must use');
        $this->assertNull(app(TwoFactorService::class)->requiredBecause($customer));
    }

    public function test_a_provider_can_require_it_for_its_team_except_drivers(): void
    {
        $this->seedFoundation();
        [$op, $owner] = $this->makeProvider();
        $dispatcher = $this->person();
        $driver = $this->person();
        foreach ([[$dispatcher, 'dispatcher'], [$driver, 'driver']] as [$p, $role]) {
            DB::table('operator_members')->insert(['operator_id' => $op, 'user_id' => $p->id, 'role' => $role, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }

        // A dispatcher cannot change the rule; the owner must have it on first, so they cannot lock themselves out.
        $this->actingAs($dispatcher)->post('/provider/team/security', ['required' => 1])->assertForbidden();
        $this->actingAs($owner)->post('/provider/team/security', ['required' => 1])->assertSessionHas('error');
        $this->assertFalse(app(TwoFactorService::class)->setting(TwoFactorService::KEY_TEAM, $op));

        DB::table('users')->where('id', $owner->id)->update(['password' => Hash::make('Secret123!')]);
        $this->enable($owner->fresh());
        $this->actingAs($owner->fresh())->post('/provider/team/security', ['required' => 1])->assertSessionHas('success');
        $this->assertTrue(app(TwoFactorService::class)->setting(TwoFactorService::KEY_TEAM, $op));
        $this->get('/provider/team')->assertOk()->assertSee('Two-step sign-in')->assertSee('Stop requiring it')->assertSee('1 / 2');

        $tf = app(TwoFactorService::class);
        $this->assertStringContainsString('requires two-step sign-in', (string) $tf->requiredBecause($dispatcher));
        $this->assertNull($tf->requiredBecause($driver));
        $this->actingAs($dispatcher)->get('/provider')->assertRedirect('/security');
    }

    public function test_staff_can_reset_it_for_someone_who_lost_their_phone(): void
    {
        $u = $this->person();
        $this->enable($u);
        $viewer = $this->person(['Manage security settings']);
        $this->actingAs($viewer)->post("/admin/security/users/{$u->id}/reset")->assertForbidden();

        $resetter = $this->person(['Reset two-factor']);
        $this->actingAs($resetter)->post("/admin/security/users/{$u->id}/reset")->assertRedirect();
        $this->assertNull($u->fresh()->two_factor_confirmed_at);
        $this->assertSame(1, DB::table('audit_logs')->where(['action' => 'two_factor.reset_by_staff', 'subject_id' => $u->id, 'user_id' => $resetter->id])->count());
    }

    public function test_app_sign_in_needs_the_code_too(): void
    {
        $u = $this->person();
        $secret = $this->enable($u);

        $res = $this->postJson('/api/v1/auth/login', ['login' => $u->email, 'password' => 'Secret123!'])
            ->assertStatus(403)->assertJsonPath('error', 'two_factor_required')->assertJsonMissingPath('token');
        $challenge = $res->json('challenge');

        $this->postJson('/api/v1/auth/two-factor', ['challenge' => $challenge, 'code' => '000000'])->assertStatus(422);
        $this->postJson('/api/v1/auth/two-factor', ['challenge' => 'forged', 'code' => $this->code($secret, 1)])->assertStatus(401);
        $this->postJson('/api/v1/auth/two-factor', ['challenge' => $challenge, 'code' => $this->code($secret, 1)])
            ->assertOk()->assertJsonStructure(['token'])->assertJsonPath('user.two_factor', true);

        $expired = Crypt::encryptString(json_encode(['uid' => $u->id, 'exp' => time() - 1]));
        $this->postJson('/api/v1/auth/two-factor', ['challenge' => $expired, 'code' => $this->code($secret, 1)])->assertStatus(401);
    }

    public function test_data_changes_record_who_changed_what_with_secrets_hidden(): void
    {
        $this->seedFoundation();
        $admin = $this->person(['Manage security settings', 'View audit log', 'View activity log']);

        $this->actingAs($admin)->post('/admin/security', ['staff' => 1])->assertRedirect();

        $row = DB::table('audit_logs')->where('subject_type', 'platform_settings')->where('action', 'created')->whereRaw("after->>'key' = ?", [TwoFactorService::KEY_STAFF])->first();
        $this->assertNotNull($row);
        $this->assertSame($admin->id, (int) $row->user_id);
        $this->assertTrue(json_decode($row->after, true)['value']);
        $this->assertNotNull($row->request_id);
        // The activity log entry for the same request points at the same changes.
        $this->assertSame($row->request_id, DB::table('activity_logs')->where('user_id', $admin->id)->orderByDesc('id')->value('request_id'));

        // Only changed columns are kept; passwords show as hidden.
        DB::table('users')->where('id', $admin->id)->update(['password' => Hash::make('Other123!'), 'last_seen_at' => now()]);
        $upd = DB::table('audit_logs')->where(['subject_type' => 'users', 'subject_id' => $admin->id, 'action' => 'updated'])->orderByDesc('id')->first();
        $this->assertSame(['password' => '[hidden]'], json_decode($upd->after, true));
        // A change to nothing but "last seen" is not recorded.
        $before = DB::table('audit_logs')->count();
        DB::table('users')->where('id', $admin->id)->update(['last_seen_at' => now()->addMinute()]);
        $this->assertSame($before, DB::table('audit_logs')->count());

        $this->get('/audit-log')->assertOk()->assertSee('Data changes')->assertSee('Platform settings');
        $this->get('/audit-log?table=users&record='.$admin->id)->assertOk()->assertSee('[hidden]');
        $this->get('/audit-log/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($this->person())->get('/audit-log')->assertForbidden();
    }

    public function test_deleting_a_user_keeps_their_history(): void
    {
        $u = $this->person();
        $this->actingAs($u)->get('/security')->assertOk();
        // Something this person changed, then the person is removed (here by themselves).
        DB::table('platform_settings')->insert(['scope' => 'global', 'key' => 'test.audit', 'value' => json_encode(1), 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(1, DB::table('audit_logs')->where(['user_id' => $u->id, 'subject_type' => 'platform_settings'])->count());

        DB::table('users')->where('id', $u->id)->delete();
        $this->assertSame(1, DB::table('audit_logs')->where(['subject_type' => 'users', 'subject_id' => $u->id, 'action' => 'deleted'])->whereNull('user_id')->count());
        $this->assertSame(1, DB::table('audit_logs')->where('subject_type', 'platform_settings')->whereNull('user_id')->whereRaw("after->>'key' = 'test.audit'")->count());
    }
}
