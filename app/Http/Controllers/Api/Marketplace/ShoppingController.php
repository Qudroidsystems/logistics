<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Marketplace\ShoppingService;
use App\Modules\Payments\Ledger\InsufficientFunds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ShoppingController extends Controller
{
    use ResolvesOperator;

    // ---------- shopper (provider side) ----------

    public function advance(Request $request, string $agreement, ShoppingService $svc)
    {
        $op = $this->operatorId($request, ['owner', 'admin']);
        $d = $request->validate(['amount' => 'required|integer|min:1']);
        $a = $this->providerAgreement($agreement, $op);

        return $this->run(fn () => ['advance_id' => $svc->advance((int) $a->id, $op, $request->user()->id, (int) $d['amount'])], 201);
    }

    public function receipt(Request $request, string $agreement, ShoppingService $svc)
    {
        $op = $this->operatorId($request, ['owner', 'admin', 'dispatcher']);
        $d = $request->validate(['vendor_name' => 'required|string|max:120', 'amount' => 'required|integer|min:1', 'photo_path' => 'required|string|max:255', 'items' => 'nullable|array|max:50']);
        $a = $this->providerAgreement($agreement, $op);

        return $this->run(fn () => ['receipt_id' => $svc->addReceipt((int) $a->id, $op, $d['vendor_name'], (int) $d['amount'], $d['photo_path'], $d['items'] ?? [])], 201);
    }

    public function requestAmendment(Request $request, string $agreement, ShoppingService $svc)
    {
        $op = $this->operatorId($request, ['owner', 'admin', 'dispatcher']);
        $d = $request->validate(['extra_amount' => 'required|integer|min:1', 'reason' => 'nullable|string|max:255']);
        $a = $this->providerAgreement($agreement, $op);

        return $this->run(fn () => ['amendment_id' => $svc->requestAmendment((int) $a->id, $op, (int) $d['extra_amount'], $d['reason'] ?? null)], 201);
    }

    // ---------- customer ----------

    public function show(Request $request, string $agreement, ShoppingService $svc)
    {
        $a = DB::table('agreements')->where('public_id', $agreement)->where('customer_id', $request->user()->id)->first();
        abort_unless($a, 404);

        return response()->json($svc->summary((int) $a->id));
    }

    public function reviewReceipt(Request $request, int $receipt, ShoppingService $svc)
    {
        $d = $request->validate(['ok' => 'required|boolean']);

        return $this->run(function () use ($svc, $receipt, $request, $d) {
            $svc->reviewReceipt($receipt, $request->user()->id, (bool) $d['ok']);

            return ['ok' => true];
        });
    }

    public function approveAmendment(Request $request, int $amendment, ShoppingService $svc)
    {
        return $this->run(function () use ($svc, $amendment, $request) {
            $svc->approveAmendment($amendment, $request->user()->id);

            return ['ok' => true];
        });
    }

    public function declineAmendment(Request $request, int $amendment, ShoppingService $svc)
    {
        return $this->run(function () use ($svc, $amendment, $request) {
            $svc->declineAmendment($amendment, $request->user()->id);

            return ['ok' => true];
        });
    }

    private function providerAgreement(string $publicId, int $operatorId): object
    {
        $a = DB::table('agreements')->where('public_id', $publicId)->where('provider_operator_id', $operatorId)->first();
        abort_unless($a, 404);

        return $a;
    }

    private function run(callable $fn, int $status = 200)
    {
        try {
            return response()->json($fn(), $status);
        } catch (InsufficientFunds) {
            return response()->json(['error' => 'insufficient_funds', 'message' => 'Your wallet balance is too low. Top up first.'], 422);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }
    }
}
