<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Gateways\PaystackGateway;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BankAccountService
{
    public function __construct(private PaystackGateway $paystack)
    {
    }

    /** The account name comes from the bank, never from the client. Throws RuntimeException if the bank cannot confirm it. */
    public function add(string $ownerType, int $ownerId, string $bankCode, ?string $bankName, string $accountNumber): array
    {
        $name = $this->paystack->resolveAccount($accountNumber, $bankCode);
        $first = ! DB::table('bank_accounts')->where(['owner_type' => $ownerType, 'owner_id' => $ownerId])->whereNull('deleted_at')->exists();
        DB::table('bank_accounts')->insert([
            'public_id' => (string) Str::ulid(), 'owner_type' => $ownerType, 'owner_id' => $ownerId, 'bank_code' => $bankCode, 'bank_name' => $bankName,
            'account_number' => Crypt::encryptString($accountNumber), 'account_number_last4' => substr($accountNumber, -4), 'account_name' => $name,
            'verified_at' => now(), 'is_default' => $first, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['account_name' => $name, 'last4' => substr($accountNumber, -4)];
    }
}
