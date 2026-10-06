<?php
// app/Services/Payment/PaystackService.php

namespace App\Services\Payment;

use App\Models\OnlinePayment;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl;

    public function __construct()
    {
        $gateway = PaymentGateway::where('provider_key', 'paystack')
            ->where('is_active', true)
            ->first();

        if ($gateway) {
            $this->secretKey = $gateway->mode === 'live'
                ? $gateway->secret_key
                : ($gateway->config['test_secret_key'] ?? $gateway->secret_key);
            $this->publicKey = $gateway->mode === 'live'
                ? $gateway->public_key
                : ($gateway->config['test_public_key'] ?? $gateway->public_key);
            $this->baseUrl = 'https://api.paystack.co';
        }
    }

    /**
     * Initialize a payment transaction
     */
    public function initializePayment($student, $amount, $email, $metadata = [], $phone = null)
    {
        try {
            $reference = $this->generateReference();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', [
                'amount' => $amount * 100, // Paystack uses kobo
                'email' => $email,
                'reference' => $reference,
                'metadata' => array_merge($metadata, [
                    'student_id' => $student->id,
                    'student_name' => $student->firstname . ' ' . $student->lastname,
                ]),
                'callback_url' => route('payment.callback'),
            ]);

            if ($response->successful() && $response->json('status')) {
                $data = $response->json('data');

                return [
                    'success' => true,
                    'authorization_url' => $data['authorization_url'],
                    'reference' => $reference,
                    'gateway_response' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Payment initialization failed',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack initialization error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment gateway error. Please try again.',
            ];
        }
    }

    /**
     * Verify a payment transaction
     */
    public function verifyPayment($reference)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transaction/verify/' . $reference);

            if ($response->successful() && $response->json('status')) {
                $data = $response->json('data');

                $onlinePayment = OnlinePayment::where('reference', $reference)->first();
                if ($onlinePayment) {
                    $onlinePayment->update([
                        'status' => $data['status'] === 'success' ? 'success' : 'failed',
                        'fee_charged' => ($data['fees'] ?? 0) / 100,
                        'net_amount' => ($data['amount'] - ($data['fees'] ?? 0)) / 100,
                        'gateway_response' => $data,
                        'payment_date' => now(),
                    ]);
                }

                return [
                    'success' => $data['status'] === 'success',
                    'data' => $data,
                    'online_payment' => $onlinePayment,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verification error. Please contact support.',
            ];
        }
    }

    /**
     * Initiate bank transfer payment
     */
    public function initiateBankTransfer($student, $amount, $email)
    {
        try {
            $reference = $this->generateReference();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', [
                'amount' => $amount * 100,
                'email' => $email,
                'reference' => $reference,
                'channels' => ['bank_transfer'],
                'callback_url' => route('payment.callback'),
            ]);

            if ($response->successful() && $response->json('status')) {
                $data = $response->json('data');

                return [
                    'success' => true,
                    'reference' => $reference,
                    'bank_name' => $data['bank']['name'] ?? 'Paystack Transfer',
                    'account_number' => $data['bank']['account_number'] ?? '',
                    'account_name' => $data['bank']['account_name'] ?? '',
                    'amount' => $amount,
                    'expires_at' => now()->addHours(48),
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Bank transfer initiation failed',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack bank transfer error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Bank transfer initiation failed',
            ];
        }
    }

    /**
     * Get list of banks
     */
    public function getBanks()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank');

            if ($response->successful() && $response->json('status')) {
                return $response->json('data');
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Paystack get banks error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate unique transaction reference
     */
    private function generateReference()
    {
        return 'SCHOOL_' . time() . '_' . uniqid();
    }
}
