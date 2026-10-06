<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Marketplace\CancellationService;
use App\Modules\Payments\Gateways\PaystackGateway;
use App\Modules\Payments\Ledger\AccountResolver;
use App\Modules\Settlements\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProviderPayoutController extends Controller
{
    use ResolvesOperator;

    public function wallet(Request $request, AccountResolver $accounts)
    {
        $op = $this->operatorId($request);
        $balance = (int) DB::table('ledger_accounts')->where('id', $accounts->wallet('operator', $op, $op))->value('balance');
        $inTransit = (int) DB::table('payout_requests')->where('operator_id', $op)->whereIn('status', ['requested', 'approved', 'processing'])->sum('amount');

        return response()->json(['balance' => $balance, 'pending_payouts' => $inTransit, 'currency' => 'NGN']);
    }

    public function payouts(Request $request)
    {
        $op = $this->operatorId($request);

        return response()->json(DB::table('payout_requests')->where('operator_id', $op)->orderByDesc('id')->limit(50)
            ->get(['public_id', 'amount', 'status', 'failure_reason', 'created_at', 'updated_at']));
    }

    public function request(Request $request, PayoutService $payouts)
    {
        $op = $this->operatorId($request, ['owner', 'admin', 'finance']);
        $d = $request->validate(['amount' => 'required|integer|min:1', 'bank_account_id' => 'required|string']);
        $bank = DB::table('bank_accounts')->where('public_id', $d['bank_account_id'])->where('owner_type', 'operator')->where('owner_id', $op)->first();
        abort_unless($bank, 404);

        try {
            $id = $payouts->request($op, $request->user()->id, (int) $d['amount'], (int) $bank->id);
        } catch (\App\Modules\Payments\Ledger\InsufficientFunds) {
            return response()->json(['error' => 'insufficient_funds', 'message' => 'Your wallet balance is lower than this amount.'], 422);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_request', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['payout' => DB::table('payout_requests')->where('id', $id)->first(['public_id', 'amount', 'status'])], 201);
    }

    public function bankAccounts(Request $request)
    {
        $op = $this->operatorId($request);

        return response()->json(DB::table('bank_accounts')->where('owner_type', 'operator')->where('owner_id', $op)->whereNull('deleted_at')
            ->get(['public_id', 'bank_name', 'account_number_last4', 'account_name', 'verified_at', 'is_default']));
    }

    /** The account name comes from the bank, never from the client, so a payout can only go to the named account holder. */
    public function addBankAccount(Request $request, \App\Modules\Payments\BankAccountService $banks)
    {
        $op = $this->operatorId($request, ['owner', 'admin', 'finance']);
        $d = $request->validate(['bank_code' => 'required|string|max:12', 'bank_name' => 'nullable|string|max:80', 'account_number' => 'required|digits:10']);
        try {
            return response()->json($banks->add('operator', $op, $d['bank_code'], $d['bank_name'] ?? null, $d['account_number']), 201);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'unverified', 'message' => $e->getMessage()], 422);
        }
    }

    /** A provider backs out of a job that has not been collected yet. The customer is refunded in full. */
    public function cancelShipment(Request $request, string $shipment, CancellationService $svc)
    {
        $op = $this->operatorId($request, ['owner', 'admin', 'dispatcher']);
        $row = DB::table('shipments')->where('public_id', $shipment)->where('operator_id', $op)->first();
        abort_unless($row, 404);
        $d = $request->validate(['reason' => 'required|string|max:40']);
        try {
            return response()->json($svc->cancelShipment((int) $row->id, 'provider', $request->user()->id, $d['reason']));
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_cancel', 'message' => $e->getMessage()], 422);
        }
    }
}
