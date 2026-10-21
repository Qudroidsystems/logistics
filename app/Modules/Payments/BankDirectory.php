<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Gateways\PaystackGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The banks people can pay out to. Paystack's own list is the truth (codes change and new banks appear), kept for a day.
 * If Paystack cannot be reached or no key is set, a short list of the common banks keeps the forms working.
 */
class BankDirectory
{
    public const FALLBACK = [
        '044' => 'Access Bank', '058' => 'GTBank', '011' => 'First Bank', '033' => 'UBA', '057' => 'Zenith Bank', '070' => 'Fidelity Bank',
        '214' => 'FCMB', '232' => 'Sterling Bank', '032' => 'Union Bank', '035' => 'Wema Bank', '076' => 'Polaris Bank', '221' => 'Stanbic IBTC',
        '050' => 'Ecobank', '082' => 'Keystone Bank', '50211' => 'Kuda', '50515' => 'Moniepoint', '999992' => 'OPay', '999991' => 'PalmPay',
    ];

    private const CACHE_KEY = 'paystack.banks.ng';

    public function __construct(private PaystackGateway $paystack)
    {
    }

    /** @return array<string,string> bank code => bank name, sorted by name */
    /** The pseudo "bank" for payouts by Interac e-Transfer; the account number is the person's email or mobile. */
    public const INTERAC = 'INTERAC';

    public static function etransfer(): bool
    {
        return config('payments.payout_method') === 'etransfer';
    }

    /** Validation for adding a payout account: a 10-digit NUBAN at a bank, or an e-Transfer email/mobile with the holder's name. */
    public static function rules(\Illuminate\Http\Request $request): array
    {
        return $request->input('bank_code') === self::INTERAC
            ? ['bank_code' => 'required|string|max:12', 'bank_name' => 'nullable|string|max:80', 'account_number' => 'required|string|max:190', 'account_name' => 'required|string|min:2|max:120']
            : ['bank_code' => 'required|string|max:12', 'bank_name' => 'nullable|string|max:80', 'account_number' => 'required|digits:10'];
    }

    public function all(): array
    {
        if (self::etransfer()) {
            return [self::INTERAC => 'Interac e-Transfer'];
        }
        $banks = Cache::get(self::CACHE_KEY);
        if (! is_array($banks) || $banks === []) {
            $banks = $this->fetch();
            if ($banks !== []) {
                Cache::put(self::CACHE_KEY, $banks, now()->addDay());
            }
        }

        return $banks !== [] ? $banks : self::FALLBACK;
    }

    public function name(?string $code): ?string
    {
        return $code ? ($this->all()[$code] ?? null) : null;
    }

    /** @return array<string,string> empty when Paystack cannot give us the list */
    private function fetch(): array
    {
        try {
            $res = Http::withToken($this->paystack->secret())->acceptJson()->timeout(10)
                ->get('https://api.paystack.co/bank', ['country' => 'nigeria', 'perPage' => 200, 'use_cursor' => 'false']);
            if (! $res->ok() || ! $res->json('status')) {
                return [];
            }
            $banks = [];
            foreach ((array) $res->json('data') as $b) {
                if (! empty($b['code']) && ! empty($b['name']) && ($b['active'] ?? true) && ! ($b['is_deleted'] ?? false) && ($b['currency'] ?? 'NGN') === 'NGN') {
                    $banks[(string) $b['code']] = (string) $b['name'];
                }
            }
            asort($banks);

            return $banks;
        } catch (Throwable) {
            return [];
        }
    }
}
