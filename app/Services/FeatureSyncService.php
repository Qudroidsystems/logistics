<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\FeatureSync;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Talks to the remote control portal.
 *  - pull(): GET the remote's flags and apply them here.
 *  - push(): send this portal's current flags to the remote (optional).
 * Both authenticate with the shared remote key (bearer + HMAC of the body).
 */
class FeatureSyncService
{
    /** A signature the remote can verify: sha256 HMAC of the raw body with the shared key. */
    public static function sign(string $body, string $key): string
    {
        return 'sha256=' . hash_hmac('sha256', $body, $key);
    }

    public function generateKey(): string
    {
        return 'csk_' . Str::random(48);
    }

    /** Pull flags from the remote portal and apply them. */
    public function pull(): array
    {
        $cfg = FeatureSync::current();
        $url = trim((string) $cfg->remote_url);
        $key = $cfg->remoteKey();
        if ($url === '' || !$key) {
            return ['ok' => false, 'message' => 'Remote URL or key is not set.'];
        }

        try {
            $res = Http::withToken($key)->acceptJson()->timeout(20)->get($url);
            if (!$res->successful()) {
                $this->note($cfg, 'HTTP ' . $res->status());
                return ['ok' => false, 'message' => 'Remote returned HTTP ' . $res->status() . '.'];
            }
            $json = $res->json() ?: [];
            // Accept {flags:{...}} or a bare {...} of key => 0/1.
            $flags = $json['flags'] ?? $json;
            if (!is_array($flags) || !$flags) {
                $this->note($cfg, 'No flags in response');
                return ['ok' => false, 'message' => 'The remote response had no flags.'];
            }
            $summary = FeatureFlag::applyRemote($flags, 'pull');
            $cfg->forceFill(['last_pulled_at' => now(), 'last_pull_status' => 'OK · ' . $summary['changed'] . ' changed'])->save();
            return ['ok' => true, 'message' => "Pulled {$summary['total']} flag(s); {$summary['changed']} changed.", 'summary' => $summary];
        } catch (\Throwable $e) {
            Log::warning('Feature flag pull failed', ['error' => $e->getMessage()]);
            $this->note($cfg, 'Error: ' . Str::limit($e->getMessage(), 120));
            return ['ok' => false, 'message' => 'Could not reach the remote portal.'];
        }
    }

    /** Send this portal's current flags to the remote (if it accepts pushes). */
    public function push(): array
    {
        $cfg = FeatureSync::current();
        $url = trim((string) $cfg->remote_url);
        $key = $cfg->remoteKey();
        if ($url === '' || !$key) {
            return ['ok' => false, 'message' => 'Remote URL or key is not set.'];
        }
        $body = json_encode(['flags' => FeatureFlag::map()]);
        try {
            $res = Http::withToken($key)->withHeaders(['X-Signature' => self::sign($body, $key)])
                ->acceptJson()->timeout(20)->withBody($body, 'application/json')->post($url);
            $cfg->forceFill(['last_pushed_at' => now()])->save();
            return ['ok' => $res->successful(), 'message' => $res->successful() ? 'Pushed.' : 'Remote returned HTTP ' . $res->status() . '.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach the remote portal.'];
        }
    }

    protected function note(FeatureSync $cfg, string $status): void
    {
        $cfg->forceFill(['last_pulled_at' => now(), 'last_pull_status' => $status])->save();
    }
}
