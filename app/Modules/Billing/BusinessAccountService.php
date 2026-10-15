<?php

namespace App\Modules\Billing;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A provider's business customers who book on credit and pay by monthly invoice.
 *
 * A delivery "on account" is booked like a cash job (the escrow is funded from the provider's side, see
 * EscrowService::hold 'offline'), so riders are paid and the job settles as usual; the provider collects the money
 * from the business when the invoice is paid. Before each booking the account is checked: it must be active, have no
 * overdue invoice, and stay within its credit limit counting everything not yet paid.
 */
class BusinessAccountService
{
    /** @return list<object> the provider's accounts with what each owes, active first */
    public function all(int $operatorId): array
    {
        $rows = DB::table('business_accounts')->where('operator_id', $operatorId)->orderByRaw("status = 'active' DESC")->orderBy('name')->get()->all();
        foreach ($rows as $r) {
            $r->owed = $this->owed((int) $r->id);
        }

        return $rows;
    }

    /** @return list<object> active accounts, for the booking forms */
    public function bookable(int $operatorId): array
    {
        return DB::table('business_accounts')->where('operator_id', $operatorId)->where('status', 'active')->orderBy('name')->get(['public_id', 'name'])->all();
    }

    public function find(int $operatorId, string $publicId): object
    {
        $a = DB::table('business_accounts')->where('operator_id', $operatorId)->where('public_id', $publicId)->first();
        if (! $a) {
            throw new RuntimeException('That business account was not found.');
        }

        return $a;
    }

    /** @param array{name:string, billing_email?:?string, billing_phone?:?string, billing_address?:?string, credit_limit?:?int, terms_days?:?int, merchant_code?:?string, note?:?string} $d */
    public function save(int $operatorId, array $d, ?string $publicId = null): string
    {
        $merchantId = null;
        if (! empty($d['merchant_code'])) {
            $merchantId = DB::table('merchants')->where('merchant_code', strtoupper(trim($d['merchant_code'])))->value('id');
            if (! $merchantId) {
                throw new RuntimeException('No partner business has the ID '.strtoupper(trim($d['merchant_code'])).'.');
            }
            $taken = DB::table('business_accounts')->where('operator_id', $operatorId)->where('merchant_id', $merchantId)
                ->when($publicId, fn ($q) => $q->where('public_id', '!=', $publicId))->value('name');
            if ($taken) {
                throw new RuntimeException("That partner business already bills to the account {$taken}.");
            }
        }
        $limit = $d['credit_limit'] ?? null;
        $row = [
            'name' => trim($d['name']), 'billing_email' => $d['billing_email'] ?? null, 'billing_phone' => $d['billing_phone'] ?? null,
            'billing_address' => $d['billing_address'] ?? null, 'credit_limit' => $limit !== null && $limit > 0 ? (int) $limit : null,
            'terms_days' => max(0, min(120, (int) ($d['terms_days'] ?? 30))), 'merchant_id' => $merchantId, 'note' => $d['note'] ?? null,
            'updated_at' => now(),
        ];
        if ($publicId) {
            $this->find($operatorId, $publicId);
            DB::table('business_accounts')->where('operator_id', $operatorId)->where('public_id', $publicId)->update($row);

            return $publicId;
        }
        $id = (string) Str::ulid();
        DB::table('business_accounts')->insert($row + ['public_id' => $id, 'operator_id' => $operatorId, 'status' => 'active', 'created_at' => now()]);

        return $id;
    }

    /** active | on_hold | closed. On hold or closed, nothing new can be booked on the account; what is owed stays owed. */
    public function setStatus(int $operatorId, string $publicId, string $status): void
    {
        if (! in_array($status, ['active', 'on_hold', 'closed'], true)) {
            throw new RuntimeException('Unknown account status.');
        }
        $this->find($operatorId, $publicId);
        DB::table('business_accounts')->where('operator_id', $operatorId)->where('public_id', $publicId)->update(['status' => $status, 'updated_at' => now()]);
    }

    /**
     * What the business owes: deliveries not yet billed plus invoices not yet paid, in kobo.
     *
     * @return array{unbilled:int, invoiced:int, total:int, overdue:int}
     */
    public function owed(int $accountId): array
    {
        $unbilled = (int) DB::table('orders as o')->join('escrow_holds as h', 'h.agreement_id', '=', 'o.agreement_id')
            ->where('o.business_account_id', $accountId)->whereNull('o.invoice_id')
            ->sum(DB::raw('GREATEST(h.amount - h.refunded_amount - h.subsidy, 0)'));
        $open = DB::table('invoices')->where('business_account_id', $accountId)->where('status', 'issued');
        $invoiced = (int) (clone $open)->sum('total');
        $overdue = (int) (clone $open)->where('due_on', '<', CarbonImmutable::now()->toDateString())->sum('total');

        return ['unbilled' => $unbilled, 'invoiced' => $invoiced, 'total' => $unbilled + $invoiced, 'overdue' => $overdue];
    }

    /**
     * The account a booking goes on, checked for this amount. Throws with a message for the staff member when the
     * account cannot take it.
     */
    public function chargeable(int $operatorId, string $publicId, int $amount): object
    {
        $a = DB::table('business_accounts')->where('operator_id', $operatorId)->where('public_id', $publicId)->lockForUpdate()->first();
        if (! $a) {
            throw new RuntimeException('Choose the business account to bill.');
        }
        $this->check($a, $amount);

        return $a;
    }

    /** The same checks, for an account already found (the partner API looks it up by merchant). */
    public function check(object $a, int $amount): void
    {
        if ($a->status !== 'active') {
            throw new RuntimeException("The account {$a->name} is ".($a->status === 'on_hold' ? 'on hold' : 'closed').'. Nothing new can be booked on it.');
        }
        $owed = $this->owed((int) $a->id);
        if ($owed['overdue'] > 0) {
            throw new RuntimeException("{$a->name} has an overdue invoice. Record the payment before booking more on account.");
        }
        if ($a->credit_limit !== null && $owed['total'] + $amount > (int) $a->credit_limit) {
            $left = max(0, (int) $a->credit_limit - $owed['total']);
            throw new RuntimeException("This booking would take {$a->name} over its credit limit. Credit left: ".self::naira($left).'.');
        }
    }

    public static function naira(int $kobo): string
    {
        return '₦'.number_format($kobo / 100, 2);
    }
}
