<?php

namespace App\Modules\Accounting;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** QuickBooks Online: OAuth 2 with Intuit, then the accounting API for the company (realm) the provider picked. */
class QuickBooksGateway implements AccountingGateway
{
    private const AUTH = 'https://appcenter.intuit.com/connect/oauth2';

    private const TOKEN = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';

    public function configured(): bool
    {
        return (bool) config('services.quickbooks.client_id') && (bool) config('services.quickbooks.client_secret');
    }

    public function authorizeUrl(string $state, string $redirect): string
    {
        return self::AUTH.'?'.http_build_query([
            'client_id' => config('services.quickbooks.client_id'), 'response_type' => 'code', 'scope' => 'com.intuit.quickbooks.accounting',
            'redirect_uri' => $redirect, 'state' => $state,
        ]);
    }

    public function connect(string $code, string $redirect, array $query): array
    {
        $realm = (string) ($query['realmId'] ?? '');
        if ($realm === '') {
            throw new RuntimeException('QuickBooks did not say which company to use. Try connecting again.');
        }
        $t = $this->token(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect]);
        $info = Http::withToken($t['access_token'])->acceptJson()->get($this->base($realm)."/companyinfo/{$realm}", ['minorversion' => 75]);

        return $t + ['tenant_id' => $realm, 'tenant_name' => $info->ok() ? ($info->json('CompanyInfo.CompanyName') ?? null) : null];
    }

    public function refresh(string $refreshToken): array
    {
        return $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]);
    }

    public function accounts(object $connection, string $token): array
    {
        $r = Http::withToken($token)->acceptJson()->get($this->base($connection->tenant_id).'/query', [
            'query' => 'select Id, Name, AccountType from Account where Active = true maxresults 500', 'minorversion' => 75,
        ]);
        $this->check($r);

        return collect($r->json('QueryResponse.Account') ?? [])->map(fn ($a) => ['id' => (string) $a['Id'], 'name' => (string) $a['Name'], 'type' => (string) ($a['AccountType'] ?? '')])->sortBy('name')->values()->all();
    }

    public function journal(object $connection, string $token, string $date, string $memo, array $lines): string
    {
        $r = Http::withToken($token)->acceptJson()->post($this->base($connection->tenant_id).'/journalentry?minorversion=75', [
            'TxnDate' => $date, 'PrivateNote' => mb_substr($memo, 0, 4000),
            'Line' => array_map(fn ($l) => [
                'Amount' => round(($l['debit'] ?: $l['credit']) / 100, 2), 'Description' => mb_substr($l['description'], 0, 4000),
                'DetailType' => 'JournalEntryLineDetail',
                'JournalEntryLineDetail' => ['PostingType' => $l['debit'] ? 'Debit' : 'Credit', 'AccountRef' => ['value' => $l['account']]],
            ], $lines),
        ]);
        $this->check($r);

        return (string) $r->json('JournalEntry.Id');
    }

    private function base(string $realm): string
    {
        $host = config('services.quickbooks.environment') === 'production' ? 'https://quickbooks.api.intuit.com' : 'https://sandbox-quickbooks.api.intuit.com';

        return "{$host}/v3/company/{$realm}";
    }

    private function token(array $form): array
    {
        $r = Http::asForm()->acceptJson()->withBasicAuth((string) config('services.quickbooks.client_id'), (string) config('services.quickbooks.client_secret'))->post(self::TOKEN, $form);
        if (! $r->ok() || ! $r->json('access_token')) {
            throw new RuntimeException('QuickBooks refused the sign-in ('.($r->json('error') ?? $r->status()).'). Connect again.');
        }

        return ['access_token' => $r->json('access_token'), 'refresh_token' => $r->json('refresh_token'), 'expires_in' => (int) ($r->json('expires_in') ?? 3600)];
    }

    private function check($r): void
    {
        if ($r->status() === 401) {
            throw new TokenExpired;
        }
        if (! $r->successful()) {
            $msg = $r->json('Fault.Error.0.Detail') ?? $r->json('Fault.Error.0.Message') ?? 'error '.$r->status();
            throw new RuntimeException('QuickBooks said: '.mb_substr((string) $msg, 0, 300));
        }
    }
}
