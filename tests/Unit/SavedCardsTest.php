<?php

namespace Tests\Unit;

use App\Modules\Payments\Gateways\PaystackGateway;
use App\Modules\Payments\Gateways\StripeGateway;
use Tests\TestCase;

/** Reading a reusable card from Stripe and Paystack answers, and what an off-session charge answer means. */
class SavedCardsTest extends TestCase
{
    public function test_stripe_card_from_a_payment_set_up_for_later(): void
    {
        $pi = ['id' => 'pi_1', 'customer' => 'cus_9', 'setup_future_usage' => 'off_session', 'receipt_email' => 'a@b.test',
            'payment_method' => ['id' => 'pm_7', 'type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242', 'exp_month' => 4, 'exp_year' => 2029, 'fingerprint' => 'fp']]];
        $c = StripeGateway::cardFromIntent($pi);
        $this->assertSame(['pm_7', 'cus_9', 'Visa', '4242', 4, 2029], [$c['token'], $c['customer_ref'], $c['brand'], $c['last4'], $c['exp_month'], $c['exp_year']]);
        // Not set up for later use, or no customer: nothing to keep.
        $this->assertNull(StripeGateway::cardFromIntent(['setup_future_usage' => null] + $pi));
        $this->assertNull(StripeGateway::cardFromIntent(['customer' => null] + $pi));
    }

    public function test_stripe_off_session_answers(): void
    {
        $ok = StripeGateway::offSessionResult(['id' => 'pi_2', 'status' => 'succeeded', 'amount_received' => 5000, 'currency' => 'cad'], 200);
        $this->assertSame(['success', 5000, 'CAD', 'pi_2'], [$ok['status'], $ok['amount'], $ok['currency'], $ok['raw']['payment_intent']]);
        $declined = StripeGateway::offSessionResult(['error' => ['message' => 'Your card was declined.', 'payment_intent' => ['id' => 'pi_3', 'status' => 'requires_payment_method', 'amount' => 5000]]], 402);
        $this->assertSame(['failed', 'Your card was declined.', 'pi_3'], [$declined['status'], $declined['message'], $declined['raw']['payment_intent']]);
        $this->assertSame('pending', StripeGateway::offSessionResult(['error' => ['message' => 'oops']], 500)['status']);
        $this->assertSame('pending', StripeGateway::offSessionResult(['id' => 'pi_4', 'status' => 'processing'], 200)['status']);
    }

    public function test_paystack_reusable_authorization(): void
    {
        $gw = app(PaystackGateway::class);
        $raw = ['customer' => ['email' => 'a@b.test', 'customer_code' => 'CUS_1'],
            'authorization' => ['authorization_code' => 'AUTH_x', 'reusable' => true, 'channel' => 'card', 'brand' => 'visa ', 'last4' => '4081', 'exp_month' => '12', 'exp_year' => '2030', 'signature' => 'SIG']];
        $c = $gw->savedCard($raw);
        $this->assertSame(['AUTH_x', 'a@b.test', 'Visa', '4081', 12, 2030], [$c['token'], $c['email'], $c['brand'], $c['last4'], $c['exp_month'], $c['exp_year']]);
        $raw['authorization']['reusable'] = false;
        $this->assertNull($gw->savedCard($raw));
        $this->assertNull($gw->savedCard(['authorization' => ['authorization_code' => 'A', 'reusable' => true, 'channel' => 'bank']]));
    }
}
