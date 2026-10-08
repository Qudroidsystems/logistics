<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Payments\BankAccountService;
use App\Modules\Payments\Ledger\InsufficientFunds;
use App\Modules\Payments\PaymentService;
use App\Modules\Payments\WalletService;
use App\Modules\Settlements\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerWalletController extends Controller
{
    public function show(Request $request, WalletService $wallet)
    {
        $uid = $request->user()->id;

        return response()->json([
            'balance' => $wallet->balance($uid), 'currency' => 'NGN',
            'pending_withdrawals' => (int) DB::table('payout_requests as p')->join('wallets as w', 'w.id', '=', 'p.wallet_id')
                ->where(['w.owner_type' => 'customer', 'w.owner_id' => $uid])->whereIn('p.status', ['requested', 'approved', 'processing'])->sum('p.amount'),
        ]);
    }

    public function topUp(Request $request, WalletService $wallet)
    {
        $d = $request->validate(['amount' => 'required|integer|min:1']);

        return $this->run(fn () => $wallet->startTopUp($request->user()->id, (int) $d['amount']), 201);
    }

    /** Nigerian banks for the "add account" picker: [{code, name}]. */
    public function banks(\App\Modules\Payments\BankDirectory $directory)
    {
        $out = [];
        foreach ($directory->all() as $code => $name) {
            $out[] = ['code' => (string) $code, 'name' => $name];
        }

        return response()->json($out);
    }

    public function bankAccounts(Request $request)
    {
        return response()->json(DB::table('bank_accounts')->where(['owner_type' => 'customer', 'owner_id' => $request->user()->id])->whereNull('deleted_at')
            ->get(['public_id', 'bank_name', 'account_number_last4', 'account_name', 'is_default']));
    }

    public function addBankAccount(Request $request, BankAccountService $banks)
    {
        $d = $request->validate(['bank_code' => 'required|string|max:12', 'bank_name' => 'nullable|string|max:80', 'account_number' => 'required|digits:10']);

        return $this->run(fn () => $banks->add('customer', $request->user()->id, $d['bank_code'], $d['bank_name'] ?? app(\App\Modules\Payments\BankDirectory::class)->name($d['bank_code']), $d['account_number']), 201);
    }

    public function withdraw(Request $request, PayoutService $payouts)
    {
        $d = $request->validate(['amount' => 'required|integer|min:1', 'bank_account_id' => 'required|string']);
        $bank = DB::table('bank_accounts')->where('public_id', $d['bank_account_id'])->where(['owner_type' => 'customer', 'owner_id' => $request->user()->id])->first();
        abort_unless($bank, 404);

        return $this->run(function () use ($payouts, $request, $d, $bank) {
            $id = $payouts->requestForCustomer($request->user()->id, (int) $d['amount'], (int) $bank->id);

            return DB::table('payout_requests')->where('id', $id)->first(['public_id', 'amount', 'status']);
        }, 201);
    }

    /** method=card returns a Paystack link; method=wallet pays now from the balance. */
    public function payAgreement(Request $request, string $agreement, PaymentService $payments)
    {
        $d = $request->validate(['method' => 'required|in:card,wallet']);
        $a = DB::table('agreements')->where('public_id', $agreement)->where('customer_id', $request->user()->id)->first();
        abort_unless($a, 404);

        return $this->run(function () use ($payments, $request, $a, $d) {
            if ($d['method'] === 'card') {
                return $payments->initiateForAgreement((int) $a->id, $request->user()->id);
            }
            $made = $payments->payWithWallet((int) $a->id, $request->user()->id);

            return ['status' => 'paid', 'order_id' => $made['order_id']];
        });
    }

    private function run(callable $fn, int $status = 200)
    {
        try {
            return response()->json($fn(), $status);
        } catch (InsufficientFunds) {
            return response()->json(['error' => 'insufficient_funds', 'message' => 'Your wallet balance is too low.'], 422);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }
    }
}
