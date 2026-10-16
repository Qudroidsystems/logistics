<?php

namespace App\Modules\Backoffice;

use App\Models\User;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Dispatch\ManualAssignmentService;
use App\Modules\Marketplace\AgreementService;
use App\Modules\Marketplace\OrderFromAgreement;
use App\Modules\Partner\RouteEstimator;
use App\Modules\Payments\Escrow\EscrowService;
use App\Modules\Payments\Ledger\InsufficientFunds;
use App\Modules\Payments\PaymentService;
use App\Modules\Pricing\NoPricingPlan;
use App\Modules\Pricing\QuoteService;
use App\Modules\Providers\ProviderOnboardingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Staff take a booking for a customer (a phone call, a walk-in, a WhatsApp message) and enter it for them.
 *
 * It goes through the same agreement, escrow and order code as a booking the customer makes themselves, so the
 * delivery looks and behaves the same everywhere afterwards. The staff member signs the terms on the customer's
 * behalf, and that is written into the terms (booked_by) so it can always be seen.
 *
 * Payment, chosen by the staff member:
 *   link           the customer pays online; the order appears when the payment lands, as for any customer booking
 *   wallet         taken from the customer's wallet now
 *   cash           the provider has the money in hand (or will collect it at pickup)
 *   bank_transfer  the money was sent straight to the provider's account
 *   account        billed to one of the provider's business accounts on the monthly invoice (BusinessAccountService checks
 *                  the account is active, has nothing overdue and stays within its credit limit)
 * For cash and bank transfer the escrow is funded from the provider's cash-collected account, so the books show the
 * provider holding the customer's money; see EscrowService::hold and settleOffline.
 */
class ManualBookingService
{
    public const PAYMENTS = ['link' => 'Send the customer a payment link', 'wallet' => 'Customer wallet', 'cash' => 'Cash', 'bank_transfer' => 'Bank transfer to us', 'account' => 'On account (monthly invoice)'];

    public function __construct(
        private AgreementService $agreements,
        private PaymentService $payments,
        private EscrowService $escrow,
        private OrderFromAgreement $orders,
        private DispatchService $dispatch,
        private QuoteService $quotes,
        private RouteEstimator $routes,
        private ManualAssignmentService $assign,
    ) {
    }

    /** Cash, bank transfer or on account: the provider funds the escrow and the job goes to dispatch now. */
    private function offline(int $agreementId, array $d, int $staffUserId, int $operatorId, int $total): array
    {
        return DB::transaction(function () use ($agreementId, $d, $staffUserId, $operatorId, $total) {
            $account = $d['payment'] === 'account'
                ? app(\App\Modules\Billing\BusinessAccountService::class)->chargeable($operatorId, (string) $d['business_account'], $total)
                : null;
            $this->escrow->hold($agreementId, 'offline', $staffUserId);
            $made = $this->orders->create($agreementId);
            DB::table('orders')->where('id', $made['order_id'])->update(['payment_method' => $d['payment'], 'business_account_id' => $account->id ?? null, 'updated_at' => now()]);
            DB::table('shipments')->where('id', $made['shipment_id'])->update(['status' => 'awaiting_dispatch', 'updated_at' => now()]);

            return $made;
        });
    }

    /**
     * The price from the provider's own price list, for the booking form.
     *
     * @return array{total:int, quote_id:?int, distance_m:int, duration_s:int}
     */
    public function price(int $operatorId, array $d): array
    {
        $d = $this->toPoint($operatorId, $d);
        $route = $this->routes->estimate((float) $d['pickup']['lat'], (float) $d['pickup']['lng'], (float) $d['dropoff']['lat'], (float) $d['dropoff']['lng']);
        try {
            $q = $this->quotes->quote([
                'operator_id' => $operatorId, 'service_type_id' => (int) $d['service_type_id'], 'city_id' => (int) $d['city_id'],
                'vehicle_type_id' => $d['vehicle_type_id'] ?? null, 'weight_g' => (int) ($d['weight_g'] ?? 0),
                'stops' => [['type' => 'pickup'], ['type' => 'dropoff']], 'cod_amount' => (int) ($d['cod_amount'] ?? 0),
            ] + $route);
        } catch (NoPricingPlan) {
            throw new RuntimeException('There is no price list for this service and city. Type the price instead.');
        }

        return ['total' => (int) $q['total'], 'quote_id' => $q['quote_id'] ?? null] + $route;
    }

    /**
     * Finds the customer by phone, then email, or opens an account for them. A new account gets a random password;
     * the customer signs in later with "forgot password" on their phone or email.
     */
    public function customer(array $c): int
    {
        $phone = ! empty($c['phone']) ? \App\Http\Controllers\Api\AuthController::normalizePhone((string) $c['phone']) : null;
        $email = ! empty($c['email']) ? strtolower(trim((string) $c['email'])) : null;
        if (! $phone && ! $email) {
            throw new RuntimeException('Give the customer\'s phone number or email.');
        }

        $byPhone = $phone ? DB::table('users')->where('phone_number', $phone)->value('id') : null;
        $byEmail = $email ? DB::table('users')->where('email', $email)->value('id') : null;
        if ($byPhone && $byEmail && $byPhone !== $byEmail) {
            throw new RuntimeException('That phone number and that email belong to two different accounts. Use one of them.');
        }
        if ($id = $byPhone ?: $byEmail) {
            return (int) $id;
        }
        if (empty($c['name'])) {
            throw new RuntimeException('This is a new customer. Add their name.');
        }

        return DB::transaction(function () use ($c, $phone, $email) {
            $u = User::create([
                'name' => trim((string) $c['name']),
                // Email is required by the users table; a phone-only customer gets an address that can never receive mail.
                'email' => $email ?: preg_replace('/\D/', '', (string) $phone).'@phone.invalid',
                'phone_number' => $phone,
                'password' => Hash::make(Str::random(40)),
            ]);
            DB::table('customer_profiles')->insertOrIgnore([
                'public_id' => (string) Str::ulid(), 'user_id' => $u->id, 'referral_code' => strtoupper(Str::random(8)), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return (int) $u->id;
        });
    }

    /**
     * @param  array{customer:array, service_type_id:int, city_id:int, vehicle_type_id?:?int, pickup:array, dropoff:array,
     *               packages?:array, price?:?int, payment:string, driver_user_id?:?int, note?:?string}  $d
     * @return array{agreement_id:int, agreement:string, order_id:?int, shipment_id:?int, shipment:?string, payment_url:?string, customer_id:int, message:string}
     */
    public function book(int $operatorId, array $d, int $staffUserId): array
    {
        $op = DB::table('operators')->where('id', $operatorId)->first(['id', 'type', 'status']);
        if (! $op || ! in_array($op->type, ProviderOnboardingService::TYPES, true) || $op->status !== 'active') {
            throw new RuntimeException('That provider cannot take bookings right now.');
        }
        if (! isset(self::PAYMENTS[$d['payment']])) {
            throw new RuntimeException('Choose how the customer is paying.');
        }
        $d = $this->toPoint($operatorId, $d);
        if ($d['payment'] === 'account') {
            if (empty($d['business_account'])) {
                throw new RuntimeException('Choose the business account to bill.');
            }
            // The business is the customer unless staff named someone else.
            $acc = app(\App\Modules\Billing\BusinessAccountService::class)->find($operatorId, (string) $d['business_account']);
            if (empty($d['customer']['phone']) && empty($d['customer']['email'])) {
                $d['customer'] = ['name' => $acc->name, 'phone' => $acc->billing_phone, 'email' => $acc->billing_email];
            }
        }

        $customerId = $this->customer($d['customer']);
        if ($d['payment'] === 'link' && str_ends_with((string) DB::table('users')->where('id', $customerId)->value('email'), '@phone.invalid')) {
            throw new RuntimeException('A payment link needs the customer\'s email address. Add it, or choose another way to pay.');
        }

        if (isset($d['price']) && $d['price'] !== null) {
            $route = $this->routes->estimate((float) $d['pickup']['lat'], (float) $d['pickup']['lng'], (float) $d['dropoff']['lat'], (float) $d['dropoff']['lng']);
            $price = ['total' => (int) $d['price'], 'quote_id' => null] + $route;
        } else {
            $price = $this->price($operatorId, $d);
        }
        if ($price['total'] < 100) {
            throw new RuntimeException('The price must be at least ₦1.');
        }
        if (isset($acc)) {
            // Checked again, locked, when the order is made; this stops a booking the account cannot take before anything is saved.
            app(\App\Modules\Billing\BusinessAccountService::class)->check($acc, (int) $price['total']);
        }

        $stop = fn (array $s) => array_filter([
            'line1' => trim((string) $s['line1']), 'lat' => (float) $s['lat'], 'lng' => (float) $s['lng'],
            'contact_name' => $s['contact_name'] ?? null, 'contact_phone' => $s['contact_phone'] ?? null,
            'landmark' => $s['landmark'] ?? null, 'instructions' => $s['instructions'] ?? null, 'pickup_point_id' => $s['pickup_point_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $agreementId = DB::transaction(function () use ($operatorId, $customerId, $d, $price, $stop, $staffUserId) {
            $id = $this->agreements->propose($customerId, $operatorId, [
                'terms' => array_filter([
                    'pickup' => $stop($d['pickup']), 'dropoff' => $stop($d['dropoff']),
                    'packages' => array_values($d['packages'] ?? []), 'vehicle_type_id' => $d['vehicle_type_id'] ?? null,
                    'quote_id' => $price['quote_id'], 'channel' => 'manual', 'booked_by' => $staffUserId, 'note' => $d['note'] ?? null,
                    'return_of' => $d['return_of'] ?? null, 'return_reason' => $d['return_reason'] ?? null,
                    'cod_amount' => ($d['cod_amount'] ?? 0) > 0 ? (int) $d['cod_amount'] : null,
                ], fn ($v) => $v !== null),
                'price' => $price['total'], 'distance_m' => $price['distance_m'],
                'service_type_id' => (int) $d['service_type_id'], 'city_id' => (int) $d['city_id'],
            ], $staffUserId);
            $v = (int) DB::table('agreements')->where('id', $id)->value('terms_version');
            $this->agreements->accept($id, 'customer', $v);
            $this->agreements->accept($id, 'provider', $v);

            return $id;
        });

        $out = ['agreement_id' => $agreementId, 'agreement' => (string) DB::table('agreements')->where('id', $agreementId)->value('number'),
            'order_id' => null, 'shipment_id' => null, 'shipment' => null, 'payment_url' => null, 'customer_id' => $customerId];

        if ($d['payment'] === 'link') {
            try {
                $out['payment_url'] = $this->payments->initiateForAgreement($agreementId, $customerId)['authorization_url'];
            } catch (Throwable $e) {
                Log::warning("Manual booking {$agreementId}: payment link not created: {$e->getMessage()}");
            }

            return $out + ['message' => $out['payment_url']
                ? 'Booking saved. Send the customer the payment link; the job reaches dispatch as soon as they pay.'
                : 'Booking saved, but the payment link could not be made right now. The customer can pay from their account.'];
        }

        if ($d['payment'] === 'wallet') {
            try {
                $made = $this->payments->payWithWallet($agreementId, $customerId);
            } catch (InsufficientFunds) {
                app(\App\Modules\Marketplace\CancellationService::class)->cancelUnpaid($agreementId, $customerId);
                throw new RuntimeException('The customer\'s wallet does not have enough money for this booking.');
            }
            $started = true;
        } else {
            try {
                $made = $this->offline($agreementId, $d, $staffUserId, $operatorId, (int) $price['total']);
            } catch (RuntimeException $e) {
                // Another booking used the last of the account's credit in the meantime.
                app(\App\Modules\Marketplace\CancellationService::class)->cancelUnpaid($agreementId, $customerId);
                throw $e;
            }
            $started = false;
        }
        DB::table('orders')->where('id', $made['order_id'])->update(['channel' => 'manual', 'updated_at' => now()]);

        $out['order_id'] = $made['order_id'];
        $out['shipment_id'] = $made['shipment_id'];
        $out['shipment'] = (string) DB::table('shipments')->where('id', $made['shipment_id'])->value('public_id');

        // A chosen driver gets the job straight away; otherwise the usual automatic offers go out.
        if (! empty($d['driver_user_id'])) {
            try {
                $this->assign->assign($operatorId, $out['shipment'], (int) $d['driver_user_id'], $staffUserId, 'booked by dispatch');

                return $out + ['message' => 'Booking saved and given to the driver you chose.'];
            } catch (RuntimeException $e) {
                $note = ' The driver could not be given the job ('.rtrim($e->getMessage(), '.').'), so it was offered to drivers instead.';
            }
        }
        if (! $started) {
            $this->dispatch->start($made['shipment_id']);
        }

        return $out + ['message' => 'Booking saved and sent to dispatch.'.($note ?? '')];
    }

    /** A drop-off given as one of the provider's pickup points (dropoff.pickup_point = its code) becomes that point's address. */
    private function toPoint(int $operatorId, array $d): array
    {
        if (! empty($d['dropoff']['pickup_point'])) {
            if (($d['cod_amount'] ?? 0) > 0) {
                // Staff at a point hand parcels over by code; nobody there collects the goods money.
                throw new RuntimeException('Cash on delivery cannot go to a pickup point. Deliver it to the receiver, or remove the cash amount.');
            }
            $d['dropoff'] = app(\App\Modules\Returns\PickupPointService::class)->asDropoff($operatorId, (string) $d['dropoff']['pickup_point'], $d['dropoff']);
        }

        return $d;
    }
}
