<?php

namespace App\Modules\Identity\Security;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Two-step sign-in with an authenticator app. The secret is kept encrypted with the app key; recovery codes are kept
 * only as hashes and shown once. A used code's time step is remembered so the same code cannot be used twice.
 *
 * Who must use it is set by staff (all platform staff, all partner managers) and by each provider or merchant for its
 * own team. Drivers are never forced: they sign in on the driver app, which has no code screen yet.
 */
class TwoFactorService
{
    public const RECOVERY_CODES = 8;

    public const KEY_STAFF = 'security.two_factor.staff';

    public const KEY_PARTNERS = 'security.two_factor.partners';

    public const KEY_TEAM = 'security.two_factor.team';

    public function enabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null && $user->two_factor_secret !== null;
    }

    /** Start (or restart) setup: a new secret that only counts once a code from it is confirmed. */
    public function begin(User $user): string
    {
        abort_if($this->enabled($user), 409, 'Two-step sign-in is already on.');
        $secret = Totp::newSecret();
        DB::table('users')->where('id', $user->id)->update(['two_factor_secret' => Crypt::encryptString($secret), 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null]);
        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_confirmed_at = null;

        return $secret;
    }

    /** The secret waiting to be confirmed, if setup was started. */
    public function pendingSecret(User $user): ?string
    {
        return ! $this->enabled($user) && $user->two_factor_secret ? $this->secret($user) : null;
    }

    /** @return array<int,string>|null recovery codes to show once, or null when the code is wrong */
    public function confirm(User $user, string $code): ?array
    {
        $secret = $this->pendingSecret($user);
        if (! $secret || ($step = Totp::verify($secret, $code)) === null) {
            return null;
        }
        $codes = $this->newRecoveryCodes();
        DB::table('users')->where('id', $user->id)->update([
            'two_factor_confirmed_at' => now(), 'two_factor_last_step' => $step,
            'two_factor_recovery_codes' => json_encode(array_map([$this, 'hash'], $codes)),
            // Signed-in "remember me" cookies made before this stop working.
            'remember_token' => Str::random(60),
        ]);
        $user->refresh();
        Audit::record('two_factor.enabled', 'users', $user->id, null, ['two_factor' => 'on']);

        return $codes;
    }

    public function disable(User $user, string $reason = 'two_factor.disabled'): void
    {
        DB::table('users')->where('id', $user->id)->update(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null, 'two_factor_last_step' => null]);
        $user->refresh();
        Audit::record($reason, 'users', $user->id, ['two_factor' => 'on'], ['two_factor' => 'off']);
    }

    /** @return array<int,string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        abort_unless($this->enabled($user), 409, 'Two-step sign-in is off.');
        $codes = $this->newRecoveryCodes();
        DB::table('users')->where('id', $user->id)->update(['two_factor_recovery_codes' => json_encode(array_map([$this, 'hash'], $codes))]);
        Audit::record('two_factor.recovery_codes_renewed', 'users', $user->id);

        return $codes;
    }

    public function recoveryCodesLeft(User $user): int
    {
        return count(json_decode((string) $user->two_factor_recovery_codes, true) ?: []);
    }

    /**
     * Check a code typed at sign-in: a 6-digit app code, or a recovery code (used up on success).
     *
     * @return string|null 'app' or 'recovery' on success
     */
    public function verify(User $user, string $input): ?string
    {
        if (! $this->enabled($user)) {
            return null;
        }
        $input = trim($input);
        $digits = preg_replace('/\D/', '', $input);
        if (strlen($digits) === Totp::DIGITS && strlen(preg_replace('/[\s-]/', '', $input)) === Totp::DIGITS) {
            $step = Totp::verify($this->secret($user), $digits, $user->two_factor_last_step !== null ? (int) $user->two_factor_last_step : null);
            if ($step === null) {
                return null;
            }
            // Only one sign-in can claim a time step, even if two arrive at once.
            $claimed = DB::table('users')->where('id', $user->id)
                ->where(fn ($q) => $q->whereNull('two_factor_last_step')->orWhere('two_factor_last_step', '<', $step))
                ->update(['two_factor_last_step' => $step]);

            return $claimed ? 'app' : null;
        }

        $hash = $this->hash($input);

        return DB::transaction(function () use ($user, $hash) {
            $row = DB::table('users')->where('id', $user->id)->lockForUpdate()->first(['two_factor_recovery_codes']);
            $codes = json_decode((string) $row->two_factor_recovery_codes, true) ?: [];
            $i = array_search($hash, $codes, true);
            if ($i === false) {
                return null;
            }
            array_splice($codes, $i, 1);
            DB::table('users')->where('id', $user->id)->update(['two_factor_recovery_codes' => json_encode($codes)]);
            Audit::record('two_factor.recovery_code_used', 'users', $user->id, null, ['codes_left' => count($codes)]);

            return 'recovery';
        });
    }

    // ---------------------------------------------------------------- who must use it

    /** Why this person must turn it on, or null when it is their choice. */
    public function requiredBecause(User $user): ?string
    {
        if ($this->setting(self::KEY_STAFF) && $user->roles()->exists()) {
            return 'All platform staff must use two-step sign-in.';
        }
        $teams = DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')
            ->where('m.user_id', $user->id)->where('m.status', 'active')->where('m.role', '!=', 'driver')
            ->get(['o.id', 'o.display_name']);
        if ($teams->isEmpty()) {
            return null;
        }
        if ($this->setting(self::KEY_PARTNERS)) {
            return 'Everyone who manages a provider or merchant account must use two-step sign-in.';
        }
        foreach ($teams as $t) {
            if ($this->setting(self::KEY_TEAM, $t->id)) {
                return $t->display_name.' requires two-step sign-in for its team.';
            }
        }

        return null;
    }

    public function setting(string $key, ?int $operatorId = null): bool
    {
        $v = DB::table('platform_settings')->where('key', $key)
            ->when($operatorId, fn ($q) => $q->where('operator_id', $operatorId)->where('scope', 'operator'), fn ($q) => $q->whereNull('operator_id')->where('scope', 'global'))
            ->value('value');

        return (bool) json_decode((string) $v, true);
    }

    public function putSetting(string $key, bool $on, int $byUserId, ?int $operatorId = null): void
    {
        $where = $operatorId
            ? ['operator_id' => $operatorId, 'scope' => 'operator', 'scope_id' => $operatorId, 'key' => $key]
            : ['operator_id' => null, 'scope' => 'global', 'scope_id' => null, 'key' => $key];
        $exists = DB::table('platform_settings')->where($where)->exists();
        DB::table('platform_settings')->updateOrInsert($where, ['value' => json_encode($on), 'updated_by' => $byUserId, 'updated_at' => now()] + ($exists ? [] : ['created_at' => now()]));
        Cache::forever('two-factor-rules-version', (int) Cache::get('two-factor-rules-version', 0) + 1);
    }

    /** Cache key for "must this person use it", which changes whenever any rule changes. */
    public static function requiredCacheKey(int $userId): string
    {
        return 'two-factor-required:'.$userId.':'.Cache::get('two-factor-rules-version', 0);
    }

    // ----------------------------------------------------------------

    private function secret(User $user): string
    {
        return Crypt::decryptString($user->two_factor_secret);
    }

    /** @return array<int,string> like "K7PQ2-M4XZT" */
    private function newRecoveryCodes(): array
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTVWXYZ23456789';

        return array_map(function () use ($alphabet) {
            $s = '';
            for ($i = 0; $i < 10; $i++) {
                $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            return substr($s, 0, 5).'-'.substr($s, 5);
        }, range(1, self::RECOVERY_CODES));
    }

    private function hash(string $code): string
    {
        return hash('sha256', strtoupper(preg_replace('/[\s-]/', '', $code)));
    }
}
