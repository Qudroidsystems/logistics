<?php

namespace App\Modules\Payments;

use App\Jobs\ProcessGatewayEvent;
use App\Modules\Notifications\NotificationService;
use App\Modules\Payments\Gateways\ETransferGateway;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Interac e-Transfer payments, from "send it here" to "received".
 *
 *   opened    the customer picked e-Transfer: they are emailed the instructions once
 *   sent      the customer says they sent it (optional, helps staff match it)
 *   confirm   staff saw the money arrive: the order goes ahead exactly as after a card payment, through the same
 *             ProcessGatewayEvent path. If it arrives after the order lapsed, the money goes to the customer's wallet.
 *   reject    staff could not match it (wrong amount, never came): the customer is told
 *   expire    still waiting after the hold time: the payment is closed and the customer is told
 */
class ETransferService
{
    public function __construct(private NotificationService $notifications, private WalletService $wallets) {}

    public function gateway(): ETransferGateway
    {
        return app(ETransferGateway::class);
    }

    /** The payment as the instructions page shows it, or null for an unknown reference. */
    public function forReference(string $reference): ?array
    {
        $i = DB::table('payment_intents as i')->leftJoin('etransfer_payments as e', 'e.payment_intent_id', '=', 'i.id')->leftJoin('agreements as a', 'a.id', '=', 'i.agreement_id')
            ->where('i.reference', $reference)->where('i.gateway', ETransferGateway::KEY)
            ->first(['i.id', 'i.reference', 'i.amount', 'i.status', 'i.created_at', 'i.agreement_id', 'a.number as agreement', 'e.customer_sent_at', 'e.confirmed_at', 'e.rejected_at', 'e.reason', 'e.applied_to']);
        if (! $i) {
            return null;
        }
        $ins = $this->gateway()->instructions();

        return [
            'reference' => $i->reference, 'amount' => (int) $i->amount, 'amount_text' => Money::format((int) $i->amount, true),
            'for' => $i->agreement ? 'Delivery '.$i->agreement : 'Wallet top-up',
            'state' => self::state($i), 'reason' => $i->reason, 'applied_to' => $i->applied_to,
            'customer_sent_at' => $i->customer_sent_at, 'deadline' => \Illuminate\Support\Carbon::parse($i->created_at)->addHours($ins['hold_hours']),
        ] + ['to' => $ins];
    }

    /** Pure: waiting | sent | received | rejected | expired */
    public static function state(object $i): string
    {
        return match (true) {
            (bool) $i->confirmed_at => 'received',
            (bool) $i->rejected_at => 'rejected',
            in_array($i->status, ['abandoned', 'failed'], true) => 'expired',
            (bool) $i->customer_sent_at => 'sent',
            default => 'waiting',
        };
    }

    public function opened(string $reference): void
    {
        $intent = DB::table('payment_intents')->where('reference', $reference)->first(['id', 'payer_id', 'amount']);
        if (! $intent) {
            return;
        }
        DB::table('etransfer_payments')->insertOrIgnore(['payment_intent_id' => $intent->id, 'created_at' => now(), 'updated_at' => now()]);
        $first = DB::table('etransfer_payments')->where('payment_intent_id', $intent->id)->whereNull('instructions_sent_at')
            ->update(['instructions_sent_at' => now(), 'updated_at' => now()]);
        if ($first && $intent->payer_id) {
            $ins = $this->gateway()->instructions();
            $this->notifications->notify((int) $intent->payer_id, 'payment.etransfer_instructions', [
                'amount' => Money::format((int) $intent->amount, true), 'email' => $ins['email'], 'reference' => $reference, 'hours' => $ins['hold_hours'],
            ], route('etransfer.show', $reference));
        }
    }

    /** The customer says they sent it. */
    public function markSent(string $reference, ?string $senderName): void
    {
        $intent = DB::table('payment_intents')->where('reference', $reference)->where('gateway', ETransferGateway::KEY)->whereIn('status', ['initiated', 'pending'])->first(['id']);
        if (! $intent) {
            throw new RuntimeException('This payment is no longer waiting for an e-Transfer.');
        }
        DB::table('etransfer_payments')->updateOrInsert(['payment_intent_id' => $intent->id], [
            'customer_sent_at' => now(), 'sender_name' => $senderName ? mb_substr(trim($senderName), 0, 120) : null, 'updated_at' => now(),
        ]);
    }

    /** Waiting e-Transfers for staff, the ones the customer says they sent first; plus late ones from the last two weeks. */
    public function queue(): array
    {
        return DB::table('payment_intents as i')->leftJoin('etransfer_payments as e', 'e.payment_intent_id', '=', 'i.id')
            ->leftJoin('users as u', 'u.id', '=', 'i.payer_id')->leftJoin('agreements as a', 'a.id', '=', 'i.agreement_id')
            ->where('i.gateway', ETransferGateway::KEY)->whereNull('e.confirmed_at')->whereNull('e.rejected_at')
            ->where(fn ($q) => $q->whereIn('i.status', ['initiated', 'pending'])->orWhere(fn ($w) => $w->where('i.status', 'abandoned')->where('i.created_at', '>', now()->subDays(14))))
            ->orderByRaw('e.customer_sent_at IS NULL')->orderBy('i.created_at')->limit(300)
            ->get(['i.id', 'i.reference', 'i.amount', 'i.status', 'i.created_at', 'u.name', 'u.email', 'u.phone_number', 'a.number as agreement', 'a.status as agreement_status', 'e.customer_sent_at', 'e.sender_name'])
            ->all();
    }

    /** Recently confirmed or rejected, for the record. */
    public function recent(int $limit = 30): array
    {
        return DB::table('etransfer_payments as e')->join('payment_intents as i', 'i.id', '=', 'e.payment_intent_id')->leftJoin('users as u', 'u.id', '=', 'i.payer_id')
            ->leftJoin('users as s', 's.id', '=', DB::raw('COALESCE(e.confirmed_by, e.rejected_by)'))
            ->where(fn ($q) => $q->whereNotNull('e.confirmed_at')->orWhereNotNull('e.rejected_at'))
            ->orderByDesc(DB::raw('COALESCE(e.confirmed_at, e.rejected_at)'))->limit($limit)
            ->get(['i.reference', 'i.amount', 'u.name', 'e.confirmed_at', 'e.rejected_at', 'e.received_amount', 'e.applied_to', 'e.interac_ref', 'e.reason', 's.name as staff'])->all();
    }

    /**
     * Staff saw the money arrive. Returns "paid" (the delivery or top-up went ahead) or "wallet" (it came after the order
     * lapsed, so it was put in the customer's wallet to spend or withdraw).
     */
    public function confirm(int $intentId, int $staffId, int $receivedAmount, ?string $interacRef = null): string
    {
        $intent = DB::table('payment_intents')->where('id', $intentId)->where('gateway', ETransferGateway::KEY)->first();
        if (! $intent) {
            throw new RuntimeException('No such e-Transfer payment.');
        }
        if ($intent->status === 'succeeded') {
            throw new RuntimeException('This payment was already marked received.');
        }
        if ($receivedAmount !== (int) $intent->amount) {
            throw new RuntimeException('The customer was asked for '.Money::format((int) $intent->amount, true).' but you entered '.Money::format($receivedAmount, true)
                .'. Only an exact amount can be matched; reject it and contact the customer, or send back the difference.');
        }
        $agreementOpen = ! $intent->agreement_id || DB::table('agreements')->where('id', $intent->agreement_id)->value('status') === 'locked';
        $toWallet = $intent->status === 'abandoned' || ! $agreementOpen;

        DB::table('etransfer_payments')->updateOrInsert(['payment_intent_id' => $intent->id], [
            'confirmed_at' => now(), 'confirmed_by' => $staffId, 'received_amount' => $receivedAmount, 'applied_to' => $toWallet ? 'wallet' : 'paid',
            'interac_ref' => $interacRef ? mb_substr(trim($interacRef), 0, 60) : null, 'updated_at' => now(),
        ]);

        if ($toWallet) {
            DB::transaction(function () use ($intent) {
                $wallet = $this->wallets->ensureWallet((int) $intent->payer_id);
                DB::table('wallet_topups')->insertOrIgnore(['wallet_id' => $wallet, 'payment_intent_id' => $intent->id, 'amount' => (int) $intent->amount, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
                DB::table('payment_intents')->where('id', $intent->id)->update(['status' => 'succeeded', 'paid_at' => now(), 'channel' => 'etransfer', 'updated_at' => now()]);
                $this->wallets->creditTopUp((int) $intent->id);
            });
            $this->notifications->notify((int) $intent->payer_id, 'payment.etransfer_to_wallet', ['amount' => Money::format((int) $intent->amount, true)], '/wallet');

            return 'wallet';
        }

        // The same path a card gateway's webhook takes: escrow, the order, then dispatch.
        $eventId = 'etransfer:'.$intent->reference;
        DB::table('gateway_events')->insertOrIgnore([
            'gateway' => ETransferGateway::KEY, 'event_id' => $eventId, 'type' => 'charge.success',
            'payload' => json_encode(['event' => 'charge.success', 'data' => ['reference' => $intent->reference, 'amount' => $receivedAmount, 'currency' => $intent->currency, 'channel' => 'etransfer']]),
            'signature_valid' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        ProcessGatewayEvent::dispatchSync((int) DB::table('gateway_events')->where(['gateway' => ETransferGateway::KEY, 'event_id' => $eventId])->value('id'));
        if (DB::table('payment_intents')->where('id', $intent->id)->value('status') !== 'succeeded') {
            DB::table('etransfer_payments')->where('payment_intent_id', $intent->id)->update(['confirmed_at' => null, 'confirmed_by' => null, 'applied_to' => null, 'updated_at' => now()]);
            throw new RuntimeException('The payment could not be applied. Check Operations > Risk events, or try again.');
        }

        return 'paid';
    }

    /** Staff could not match it. */
    public function reject(int $intentId, int $staffId, string $reason): void
    {
        $intent = DB::table('payment_intents')->where('id', $intentId)->where('gateway', ETransferGateway::KEY)->whereIn('status', ['initiated', 'pending', 'abandoned'])->first();
        if (! $intent) {
            throw new RuntimeException('This payment is not waiting for an e-Transfer.');
        }
        DB::table('etransfer_payments')->updateOrInsert(['payment_intent_id' => $intent->id], [
            'rejected_at' => now(), 'rejected_by' => $staffId, 'reason' => mb_substr(trim($reason), 0, 300), 'updated_at' => now(),
        ]);
        DB::table('payment_intents')->where('id', $intent->id)->update(['status' => 'failed', 'updated_at' => now()]);
        $this->notifications->notify((int) $intent->payer_id, 'payment.etransfer_rejected', ['amount' => Money::format((int) $intent->amount, true), 'reason' => $reason], route('etransfer.show', $intent->reference));
    }

    /** Closes e-Transfers still waiting after the hold time. Returns how many. */
    public function expire(): int
    {
        $hours = $this->gateway()->instructions()['hold_hours'];
        $late = DB::table('payment_intents as i')->leftJoin('etransfer_payments as e', 'e.payment_intent_id', '=', 'i.id')
            ->where('i.gateway', ETransferGateway::KEY)->whereIn('i.status', ['initiated', 'pending'])->whereNull('e.confirmed_at')
            ->where('i.created_at', '<', now()->subHours($hours))->limit(500)->get(['i.id', 'i.payer_id', 'i.amount', 'i.reference']);
        foreach ($late as $i) {
            $n = DB::table('payment_intents')->where('id', $i->id)->whereIn('status', ['initiated', 'pending'])->update(['status' => 'abandoned', 'updated_at' => now()]);
            if ($n && $i->payer_id) {
                $this->notifications->notify((int) $i->payer_id, 'payment.etransfer_expired', ['amount' => Money::format((int) $i->amount, true), 'reference' => $i->reference]);
            }
        }

        return $late->count();
    }

    /** Agreements with an e-Transfer still inside its hold time are not cancelled as unpaid. */
    public function holdsAgreement(int $agreementId): bool
    {
        return DB::table('payment_intents')->where('agreement_id', $agreementId)->where('gateway', ETransferGateway::KEY)
            ->whereIn('status', ['initiated', 'pending'])->where('created_at', '>', now()->subHours($this->gateway()->instructions()['hold_hours']))->exists();
    }
}
