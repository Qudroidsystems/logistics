<?php

namespace App\Modules\Marketplace;

use App\Modules\Payments\Escrow\EscrowService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Market-shopper errands. The customer's goods money sits in escrow. The shopper may draw an advance
 * (up to a share of it) to pay vendors, uploads a receipt for each purchase, and may ask for more budget.
 * At confirmation the escrow pays out what was actually spent and returns the rest to the customer.
 */
class ShoppingService
{
    public const DEFAULT_ADVANCE_MAX_BP = 5000; // up to 50% of the goods budget up front

    public function __construct(private EscrowService $escrow)
    {
    }

    public function advance(int $agreementId, int $operatorId, int $userId, int $amount): int
    {
        $a = $this->shoppingAgreement($agreementId, $operatorId);
        $cap = intdiv((int) $a->goods_budget * $this->advanceMaxBp(), 10_000);
        $issued = (int) DB::table('shopper_advances')->where('agreement_id', $agreementId)->where('status', 'issued')->sum('amount');
        if ($amount <= 0 || $issued + $amount > $cap) {
            throw new RuntimeException('Advance is limited to N'.number_format(max($cap - $issued, 0) / 100).' more for this errand.');
        }

        return $this->escrow->issueAdvance($agreementId, $amount, $userId);
    }

    public function addReceipt(int $agreementId, int $operatorId, string $vendor, int $amount, string $photoPath, array $items = []): int
    {
        $a = $this->shoppingAgreement($agreementId, $operatorId);
        $req = DB::table('shopping_requests')->where('agreement_id', $agreementId)->first();
        $spent = (int) DB::table('shopping_receipts')->where('shopping_request_id', $req->id)->sum('amount');
        if ($amount <= 0) {
            throw new InvalidArgumentException('Receipt amount must be positive.');
        }
        if ($spent + $amount > (int) $a->goods_budget) {
            throw new RuntimeException('This purchase goes over the budget. Ask the customer for more budget first.');
        }

        return DB::table('shopping_receipts')->insertGetId([
            'shopping_request_id' => $req->id, 'vendor_name' => $vendor, 'items' => json_encode($items), 'amount' => $amount, 'photo_path' => $photoPath, 'uploaded_at' => now(),
        ]);
    }

    /** The customer checks a receipt against what they asked for. A rejected receipt stops counting toward spend. */
    public function reviewReceipt(int $receiptId, int $customerId, bool $ok): void
    {
        $r = DB::table('shopping_receipts as r')->join('shopping_requests as s', 's.id', '=', 'r.shopping_request_id')->join('agreements as a', 'a.id', '=', 's.agreement_id')
            ->where('r.id', $receiptId)->where('a.customer_id', $customerId)->whereIn('a.status', ['paid', 'in_progress', 'delivered'])->select('r.id')->first();
        if (! $r) {
            throw new RuntimeException('Receipt not found or already settled.');
        }
        DB::table('shopping_receipts')->where('id', $receiptId)->update(['verified_by_customer' => $ok]);
    }

    public function requestAmendment(int $agreementId, int $operatorId, int $extra, ?string $reason): int
    {
        $this->shoppingAgreement($agreementId, $operatorId);
        $req = DB::table('shopping_requests')->where('agreement_id', $agreementId)->first();
        if ($extra <= 0) {
            throw new InvalidArgumentException('Extra amount must be positive.');
        }

        return DB::table('budget_amendments')->insertGetId(['shopping_request_id' => $req->id, 'extra_amount' => $extra, 'reason' => $reason, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** Approving moves the extra money from the customer's wallet into escrow, so it must be topped up first. */
    public function approveAmendment(int $amendmentId, int $customerId): void
    {
        DB::transaction(function () use ($amendmentId, $customerId) {
            $m = DB::table('budget_amendments as b')->join('shopping_requests as s', 's.id', '=', 'b.shopping_request_id')->join('agreements as a', 'a.id', '=', 's.agreement_id')
                ->where('b.id', $amendmentId)->where('a.customer_id', $customerId)->whereNull('b.approved_by_customer_at')
                ->lockForUpdate()->select('b.id', 'b.extra_amount', 'a.id as agreement_id', 's.id as request_id', 's.budget_cap')->first();
            if (! $m) {
                throw new RuntimeException('Request not found or already answered.');
            }
            $this->escrow->addToHold((int) $m->agreement_id, (int) $m->extra_amount, $customerId);
            DB::table('shopping_requests')->where('id', $m->request_id)->update(['budget_cap' => (int) $m->budget_cap + (int) $m->extra_amount, 'updated_at' => now()]);
            DB::table('budget_amendments')->where('id', $amendmentId)->update(['approved_by_customer_at' => now(), 'updated_at' => now()]);
        });
    }

    public function declineAmendment(int $amendmentId, int $customerId): void
    {
        $n = DB::table('budget_amendments')->where('id', $amendmentId)->whereNull('approved_by_customer_at')
            ->whereIn('shopping_request_id', DB::table('shopping_requests')->whereIn('agreement_id', DB::table('agreements')->where('customer_id', $customerId)->select('id'))->select('id'))
            ->delete();
        if (! $n) {
            throw new RuntimeException('Request not found or already answered.');
        }
    }

    /** Receipts the customer has not rejected, capped at the budget. This is what settlement pays. */
    public function spend(int $agreementId): int
    {
        $req = DB::table('shopping_requests')->where('agreement_id', $agreementId)->first();
        if (! $req) {
            return 0;
        }
        $sum = (int) DB::table('shopping_receipts')->where('shopping_request_id', $req->id)->where(fn ($q) => $q->whereNull('verified_by_customer')->orWhere('verified_by_customer', true))->sum('amount');
        $budget = (int) DB::table('agreements')->where('id', $agreementId)->value('goods_budget');

        return min($sum, $budget);
    }

    public function summary(int $agreementId): array
    {
        $req = DB::table('shopping_requests')->where('agreement_id', $agreementId)->first();

        return [
            'budget' => (int) DB::table('agreements')->where('id', $agreementId)->value('goods_budget'),
            'advanced' => (int) DB::table('shopper_advances')->where('agreement_id', $agreementId)->where('status', 'issued')->sum('amount'),
            'spent' => $this->spend($agreementId),
            'receipts' => $req ? DB::table('shopping_receipts')->where('shopping_request_id', $req->id)->get(['id', 'vendor_name', 'amount', 'photo_path', 'verified_by_customer', 'uploaded_at']) : [],
            'amendments' => $req ? DB::table('budget_amendments')->where('shopping_request_id', $req->id)->get(['id', 'extra_amount', 'reason', 'approved_by_customer_at']) : [],
        ];
    }

    private function shoppingAgreement(int $agreementId, int $operatorId): object
    {
        $a = DB::table('agreements')->where('id', $agreementId)->where('provider_operator_id', $operatorId)->first();
        $isShopping = $a && DB::table('shopping_requests')->where('agreement_id', $agreementId)->exists();
        if (! $a || ! $isShopping || ! in_array($a->status, ['paid', 'in_progress'], true)) {
            throw new RuntimeException('This errand is not open for shopping.');
        }

        return $a;
    }

    private function advanceMaxBp(): int
    {
        $v = DB::table('platform_settings')->whereNull('operator_id')->where('key', 'shopper.advance_max_bp')->value('value');

        return $v === null ? self::DEFAULT_ADVANCE_MAX_BP : (int) (json_decode($v, true) ?? $v);
    }
}
