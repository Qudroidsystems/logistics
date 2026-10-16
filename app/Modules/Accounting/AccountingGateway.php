<?php

namespace App\Modules\Accounting;

/** One accounting service (QuickBooks Online or Xero) as the sync needs it. */
interface AccountingGateway
{
    public function configured(): bool;

    /** Where to send the provider to approve the link. */
    public function authorizeUrl(string $state, string $redirect): string;

    /**
     * Swap the approval code for tokens and find the company.
     *
     * @return array{access_token:string, refresh_token:string, expires_in:int, tenant_id:string, tenant_name:?string}
     */
    public function connect(string $code, string $redirect, array $query): array;

    /** @return array{access_token:string, refresh_token:string, expires_in:int} */
    public function refresh(string $refreshToken): array;

    /** @return list<array{id:string, name:string, type:string}> accounts that can be chosen in the mapping */
    public function accounts(object $connection, string $token): array;

    /**
     * Post one balanced journal. Amounts are kobo; each line is debit or credit.
     *
     * @param  list<array{account:string, debit:int, credit:int, description:string}>  $lines
     * @return string the journal's id in their books
     */
    public function journal(object $connection, string $token, string $date, string $memo, array $lines): string;
}
