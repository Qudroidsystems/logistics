<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\GiftCardService;
use App\Modules\Marketing\LoyaltyService;
use App\Modules\Payments\Ledger\InsufficientFunds;
use Illuminate\Http\Request;
use RuntimeException;

/** The customer's loyalty points, and gift cards they buy or check. */
class RewardsController extends Controller
{
    public function loyalty(Request $request, LoyaltyService $loyalty)
    {
        return response()->json($loyalty->forCustomer($request->user()->id));
    }

    public function giftCards(Request $request, GiftCardService $cards)
    {
        return response()->json(['data' => $cards->boughtBy($request->user()->id), 'min' => GiftCardService::MIN, 'max' => GiftCardService::MAX]);
    }

    /** Buy a card with money in the wallet. The code is in this response only, and is texted or emailed to the recipient. */
    public function buy(Request $request, GiftCardService $cards)
    {
        $d = $request->validate([
            'amount' => 'required|integer|min:1', 'recipient_name' => 'nullable|string|max:120', 'recipient_phone' => 'nullable|string|max:24',
            'recipient_email' => 'nullable|email:rfc|max:190', 'message' => 'nullable|string|max:300',
        ]);
        try {
            $card = $cards->buy($request->user()->id, (int) $d['amount'], [
                'name' => $d['recipient_name'] ?? null, 'phone' => $d['recipient_phone'] ?? null, 'email' => $d['recipient_email'] ?? null, 'message' => $d['message'] ?? null,
            ]);
        } catch (InsufficientFunds) {
            return response()->json(['error' => 'rejected', 'message' => 'Your wallet does not have enough money. Top it up first.'], 422);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }

        return response()->json($card, 201);
    }

    public function check(Request $request, GiftCardService $cards)
    {
        $d = $request->validate(['code' => 'required|string|max:40']);
        try {
            return response()->json($cards->check($d['code']));
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }
    }
}
