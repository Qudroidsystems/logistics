<?php

namespace App\Modules\Accounting;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Xero: OAuth 2 with Xero Identity, then the Accounting API for the organisation (tenant) the provider approved. */
class XeroGateway implements AccountingGateway
{
    private const AUTH = 'https://login.xero.com/identity/connect/authorize';

    private const TOKEN = 'https://identity.xero.com/connect/token';

    private const API = 'https://api.xero.com/api.xro/2.0';

    public function configured(): bool
    {
        return (bool) config('services.xero.client_id') && (bool) config('services.xero.client_secret');
    }

    public function authorizeUrl(string $state, string $redirect): string
    {
        return self::AUTH.'?'.http_build_query([
            'response_type' => 'code', 'client_id' => config('services.xero.client_id'), 'redirect_uri' => $redirect,
            'scope' => config('services.xero.scopes'), 'state' => $state,
        ]);
    }

    public function connect(string $code, string $redirect, array $query): array
    {
        $t = $this->token(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect]);
        $orgs = Http::withToken($t['access_token'])->acceptJson()->get('https://api.xero.com/connections');
        $org = collect($orgs->json() ?? [])->firstWhere('tenantType', 'ORGANISATION');
        if (! $org) {
            throw new RuntimeException('Xero did not give access to an organisation. Try connecting again and pick one.');
        }

        return $t + ['tenant_id' => (string) $org['tenantId'], 'tenant_name' => $org['tenantName'] ?? null];
    }

    public function refresh(string $refreshToken): array
    {
        return $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]);
    }

    public function accounts(object $connection, string $token): array
    {
        $r = $this->api($connection, $token)->get(self::API.'/Accounts', ['where' => 'Status=="ACTIVE"']);
        $this->check($r);

        return collect($r->json('Accounts') ?? [])->filter(fn ($a) => ! empty($a['Code']))
            ->map(fn ($a) => ['id' => (string) $a['Code'], 'name' => (string) $a['Name'], 'type' => (string) ($a['Type'] ?? '')])->sortBy('name')->values()->all();
    }

    public function journal(object $connection, string $token, string $date, string $memo, array $lines): string
    {
        // Xero: positive LineAmount is a debit, negative a credit.
        $r = $this->api($connection, $token)->post(self::API.'/ManualJournals', ['ManualJournals' => [[
            'Narration' => mb_substr($memo, 0, 4000), 'Date' => $date, 'Status' => 'POSTED', 'LineAmountTypes' => 'NoTax',
            'JournalLines' => array_map(fn ($l) => [
                'LineAmount' => round(($l['debit'] - $l['credit']) / 100, 2), 'AccountCode' => $l['account'], 'Description' => mb_substr($l['description'], 0, 4000),
            ], $lines),
        ]]]);
        $this->check($r);

        return (string) $r->json('ManualJournals.0.ManualJournalID');
    }

    private function api(object $connection, string $token)
    {
        return Http::withToken($token)->acceptJson()->withHeaders(['xero-tenant-id' => $connection->tenant_id]);
    }

    private function token(array $form): array
    {
        $r = Http::asForm()->acceptJson()->withBasicAuth((string) config('services.xero.client_id'), (string) config('services.xero.client_secret'))->post(self::TOKEN, $form);
        if (! $r->ok() || ! $r->json('access_token')) {
            throw new RuntimeException('Xero refused the sign-in ('.($r->json('error') ?? $r->status()).'). Connect again.');
        }

        return ['access_token' => $r->json('access_token'), 'refresh_token' => $r->json('refresh_token'), 'expires_in' => (int) ($r->json('expires_in') ?? 1800)];
    }

    private function check($r): void
    {
        if ($r->status() === 401) {
            throw new TokenExpired;
        }
        if (! $r->successful()) {
            $msg = $r->json('Elements.0.ValidationErrors.0.Message') ?? $r->json('Detail') ?? $r->json('Message') ?? 'error '.$r->status();
            throw new RuntimeException('Xero said: '.mb_substr((string) $msg, 0, 300));
        }
    }
}
