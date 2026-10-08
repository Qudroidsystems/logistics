<?php

namespace App\Modules\Notifications\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Firebase Cloud Messaging (HTTP v1). Needs a service-account JSON file (FCM_CREDENTIALS = its path, kept outside git)
 * and nothing else: the project id is read from the file. Works for Android and iOS tokens from the Flutter firebase_messaging plugin.
 */
class FcmGateway implements PushGateway
{
    public function send(string $token, string $title, string $body, array $data = []): array
    {
        try {
            $cred = $this->credentials();
            $payload = ['message' => [
                'token' => $token,
                'notification' => ['title' => $title, 'body' => $body],
                'data' => array_map(fn ($v) => (string) $v, array_filter($data, fn ($v) => $v !== null)),
                'android' => ['priority' => 'HIGH', 'notification' => ['channel_id' => 'default']],
                'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
            ]];
            $res = Http::withToken($this->accessToken($cred))->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$cred['project_id']}/messages:send", $payload);
            if ($res->successful()) {
                return ['ok' => true, 'invalid' => false, 'error' => null];
            }
            $status = (string) data_get($res->json(), 'error.status', '');
            $dead = in_array($status, ['NOT_FOUND', 'UNREGISTERED'], true) || ($res->status() === 400 && $status === 'INVALID_ARGUMENT');

            return ['ok' => false, 'invalid' => $dead, 'error' => mb_substr($status.' '.data_get($res->json(), 'error.message', 'HTTP '.$res->status()), 0, 300)];
        } catch (Throwable $e) {
            return ['ok' => false, 'invalid' => false, 'error' => mb_substr($e->getMessage(), 0, 300)];
        }
    }

    /** @return array{project_id:string,client_email:string,private_key:string} */
    private function credentials(): array
    {
        $path = config('services.fcm.credentials');
        if (! $path || ! is_file($path)) {
            throw new RuntimeException('FCM_CREDENTIALS is not set or the file is missing.');
        }
        $c = json_decode((string) file_get_contents($path), true);
        if (! is_array($c) || empty($c['private_key']) || empty($c['client_email']) || empty($c['project_id'])) {
            throw new RuntimeException('FCM credentials file is not a valid service account key.');
        }

        return $c;
    }

    private function accessToken(array $c): string
    {
        return Cache::remember('fcm.access_token', 3000, function () use ($c) {
            $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $now = time();
            $head = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $b64(json_encode([
                'iss' => $c['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
            ]));
            if (! openssl_sign("$head.$claims", $sig, $c['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Could not sign the FCM token request.');
            }
            $res = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => "$head.$claims.".$b64($sig),
            ]);
            if (! $res->successful() || ! $res->json('access_token')) {
                throw new RuntimeException('FCM authentication failed: '.mb_substr((string) $res->body(), 0, 200));
            }

            return $res->json('access_token');
        });
    }
}
