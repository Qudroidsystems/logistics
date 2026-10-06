<?php

namespace App\Modules\Payments\Ledger;

use Illuminate\Support\Facades\DB;

/** Finds or creates the ledger account for an owner and purpose. */
class AccountResolver
{
    /** Account type (normal side) for each purpose. Wallets and escrow are platform liabilities. */
    private const TYPES = [
        'customer_wallet' => 'liability',
        'driver_wallet' => 'liability',
        'vendor_wallet' => 'liability',
        'operator_wallet' => 'liability',
        'merchant_wallet' => 'liability',
        'order_escrow' => 'liability',
        'shopper_float' => 'liability',
        'refund_reserve' => 'liability',
        'tax_payable' => 'liability',
        'payout_in_transit' => 'liability',
        'gateway_clearing' => 'asset',
        'cod_clearing' => 'asset',
        'platform_commission' => 'revenue',
        'platform_revenue' => 'revenue',
        'promo_expense' => 'expense',
    ];

    public function for(string $ownerType, ?int $ownerId, string $purpose, int $operatorId, string $currency = 'NGN'): int
    {
        $existing = DB::table('ledger_accounts')
            ->where(['owner_type' => $ownerType, 'owner_id' => $ownerId, 'purpose' => $purpose, 'currency' => $currency])
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $type = self::TYPES[$purpose] ?? 'liability';

        return (int) DB::table('ledger_accounts')->insertGetId([
            'operator_id' => $operatorId,
            'code' => $purpose,
            'type' => $type,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'purpose' => $purpose,
            'currency' => $currency,
            'allow_negative' => in_array($type, ['asset', 'expense'], true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function platform(string $purpose): int
    {
        return $this->for('platform', 1, $purpose, 1);
    }

    /** The wallet account of a user or operator (provider wallets belong to the operator). */
    public function wallet(string $ownerType, int $ownerId, int $operatorId): int
    {
        $purpose = match ($ownerType) {
            'customer' => 'customer_wallet',
            'driver' => 'driver_wallet',
            'vendor' => 'vendor_wallet',
            'merchant' => 'merchant_wallet',
            default => 'operator_wallet',
        };

        return $this->for($ownerType, $ownerId, $purpose, $operatorId);
    }
}
