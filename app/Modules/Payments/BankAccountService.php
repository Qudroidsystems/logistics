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

    /**
     * The account name comes from the bank, never from the client. Throws RuntimeException if the bank cannot confirm it.
     * For Interac e-Transfer payouts ($bankCode INTERAC) the "account number" is the person's email or mobile and the name
     * is what they type; staff check both when they send the money.
     */
    public function add(string $ownerType, int $ownerId, string $bankCode, ?string $bankName, string $accountNumber, ?string $accountName = null): array
    {
        if ($bankCode === BankDirectory::INTERAC) {
            return $this->addETransfer($ownerType, $ownerId, $accountNumber, (string) $accountName);
        }
        $name = $this->paystack->resolveAccount($accountNumber, $bankCode);
        $first = ! DB::table('bank_accounts')->where(['owner_type' => $ownerType, 'owner_id' => $ownerId])->whereNull('deleted_at')->exists();
        DB::table('bank_accounts')->insert([
            'public_id' => (string) Str::ulid(), 'owner_type' => $ownerType, 'owner_id' => $ownerId, 'bank_code' => $bankCode, 'bank_name' => $bankName,
            'account_number' => Crypt::encryptString($accountNumber), 'account_number_last4' => substr($accountNumber, -4), 'account_name' => $name,
            'verified_at' => now(), 'is_default' => $first, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['account_name' => $name, 'last4' => substr($accountNumber, -4)];
    }

    private function addETransfer(string $ownerType, int $ownerId, string $recipient, string $name): array
    {
        $recipient = trim($recipient);
        $name = trim($name);
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $recipient = strtolower($recipient);
        } elseif (($phone = \App\Support\Phone::international($recipient)) !== null) {
            $recipient = '+'.$phone;
        } else {
            throw new \RuntimeException('Enter the email or mobile number your bank uses for Interac e-Transfer.');
        }
        if (mb_strlen($name) < 2) {
            throw new \RuntimeException('Enter your full name as your bank shows it.');
        }
        $first = ! DB::table('bank_accounts')->where(['owner_type' => $ownerType, 'owner_id' => $ownerId])->whereNull('deleted_at')->exists();
        DB::table('bank_accounts')->insert([
            'public_id' => (string) Str::ulid(), 'owner_type' => $ownerType, 'owner_id' => $ownerId, 'bank_code' => BankDirectory::INTERAC, 'bank_name' => 'Interac e-Transfer',
            'account_number' => Crypt::encryptString($recipient), 'account_number_last4' => mb_substr($recipient, -4), 'account_name' => mb_substr($name, 0, 120),
            'verified_at' => now(), 'is_default' => $first, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['account_name' => $name, 'last4' => mb_substr($recipient, -4)];
    }
}
