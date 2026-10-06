<?php

namespace App\Modules\Partner;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * API keys look like  lgk_live_ab12cd34.<48 random chars>.  The prefix is stored in clear for lookup;
 * only a SHA-256 of the secret part is stored, so a database leak does not leak working keys.
 * The full key is shown once, at creation.
 */
class ApiClientService
{
    public function issue(int $operatorId, ?int $merchantId, string $name, string $environment = 'sandbox', array $scopes = ['quotes', 'deliveries', 'webhooks']): array
    {
        $prefix = 'lgk_'.($environment === 'live' ? 'live' : 'test').'_'.Str::lower(Str::random(8));
        $secret = Str::random(48);

        DB::table('api_clients')->insert([
            'public_id' => (string) Str::ulid(), 'operator_id' => $operatorId, 'merchant_id' => $merchantId, 'name' => $name,
            'environment' => $environment, 'key_prefix' => $prefix, 'key_hash' => hash('sha256', $secret),
            'scopes' => json_encode($scopes), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['key' => "{$prefix}.{$secret}", 'prefix' => $prefix];
    }

    /** @return object|null the api_clients row, or null when the key is unknown, wrong or revoked */
    public function authenticate(string $bearer, ?string $ip = null): ?object
    {
        if (! str_contains($bearer, '.')) {
            return null;
        }
        [$prefix, $secret] = explode('.', $bearer, 2);
        $client = DB::table('api_clients')->where('key_prefix', $prefix)->whereNull('revoked_at')->first();
        if (! $client || ! hash_equals($client->key_hash, hash('sha256', $secret))) {
            return null;
        }
        $allow = $client->ip_allowlist ? json_decode($client->ip_allowlist, true) : [];
        if ($allow && $ip && ! in_array($ip, $allow, true)) {
            return null;
        }
        DB::table('api_clients')->where('id', $client->id)->update(['last_used_at' => now()]);

        return $client;
    }

    public function revoke(int $clientId): void
    {
        DB::table('api_clients')->where('id', $clientId)->update(['revoked_at' => now(), 'updated_at' => now()]);
    }
}
