<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\Gateways\PaystackGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private PaystackGateway $paystack,
        private EscrowService $escrow,
        private \App\Modules\Marketplace\OrderFromAgreement $orders,
        private \App\Modules\Dispatch\DispatchService $dispatch,
    ) {
    }

    /** Customer pays a locked agreement by card/transfer. Returns the hosted checkout URL. */
    public function initiateForAgreement(int $agreementId, int $customerId): array
    {
        $a = DB::table('agreements')->where('id', $agreementId)->where('customer_id', $customerId)->first();
        if (! $a || $a->status !== 'locked') {
            throw new RuntimeException('Only a locked agreement can be paid.');
        }
        $total = $this->escrow->escrowTotal($a);

        // Reuse a pending intent so a double-tap never creates two charges.
        $intent = DB::table('payment_intents')->where('agreement_id', $agreementId)->whereIn('status', ['initiated', 'pending'])->first();
        if (! $intent) {
            $ref = 'LG-'.strtoupper(Str::random(18));
            $id = DB::table('payment_intents')->insertGetId([
                'public_id' => (string) Str::ulid(), 'operator_id' => $a->provider_operator_id,
                'agreement_id' => $agreementId, 'payer_id' => $customerId, 'gateway' => 'paystack',
                'amount' => $total, 'reference' => $ref, 'status' => 'initiated', 'is_test' => $a->is_test,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $intent = DB::table('payment_intents')->find($id);
        }

        $email = DB::table('users')->where('id', $customerId)->value('email');
        $init = $this->paystack->initialize($email, (int) $intent->amount, $intent->reference, route('payments.callback'), ['agreement' => $a->number]);
        DB::table('payment_intents')->where('id', $intent->id)->update(['status' => 'pending', 'updated_at' => now()]);

        return ['reference' => $intent->reference, 'authorization_url' => $init['authorization_url'], 'amount' => (int) $intent->amount];
    }

    /** Pays a locked agreement from the customer's wallet. Throws InsufficientFunds when the balance is short. */
    public function payWithWallet(int $agreementId, int $customerId): array
    {
        $made = DB::transaction(function () use ($agreementId, $customerId) {
            $a = DB::table('agreements')->where('id', $agreementId)->where('customer_id', $customerId)->lockForUpdate()->first();
            if (! $a || $a->status !== 'locked') {
                throw new RuntimeException('Only a locked agreement can be paid.');
            }
            $this->escrow->hold($agreementId, 'wallet', $customerId);
            DB::table('payment_intents')->where('agreement_id', $agreementId)->whereIn('status', ['initiated', 'pending'])->update(['status' => 'abandoned', 'updated_at' => now()]);
            $made = $this->orders->create($agreementId);
            DB::table('orders')->where('id', $made['order_id'])->update(['payment_method' => 'wallet', 'updated_at' => now()]);
            DB::table('shipments')->where('id', $made['shipment_id'])->update(['status' => 'awaiting_dispatch', 'updated_at' => now()]);

            return $made;
        });

        // After commit: a driver is never offered unpaid work.
        $this->dispatch->start($made['shipment_id']);

        return $made;
    }
}
