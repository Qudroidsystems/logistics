<?php
// app/Http/Controllers/Payment/FlexibleOnlinePaymentController.php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SchoolBillModel;
use App\Models\SchoolBillTermSession;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\StudentBillPayment;
use App\Models\StudentBillPaymentRecord;
use App\Models\StudentBillPaymentBook;
use App\Models\OnlinePayment;
use App\Models\PaymentGateway;
use App\Models\PaymentBatch;
use App\Models\PaymentBatchItem;
use App\Services\Payment\PaystackService;
use App\Services\Scholarship\ScholarshipService;
use App\Services\Discount\DiscountService;
use App\Mail\PaymentReceiptMail;
use App\Mail\PaymentConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class FlexibleOnlinePaymentController extends Controller
{
    protected $paystackService;
    protected $scholarshipService;
    protected $discountService;

    public function __construct(
        PaystackService $paystackService,
        ScholarshipService $scholarshipService,
        DiscountService $discountService
    ) {
        $this->paystackService = $paystackService;
        $this->scholarshipService = $scholarshipService;
        $this->discountService = $discountService;

        $this->middleware('auth');
        $this->middleware('permission:Create payment', ['only' => ['showFlexiblePayment', 'initializeFlexiblePayment']]);
    }

    /**
     * Show the flexible payment page for a student.
     */
    public function showFlexiblePayment($studentId, $classId, $termId, $sessionId)
    {
        $student = Student::with(['studentClass' => function($q) use ($classId, $termId, $sessionId) {
            $q->where('schoolclassid', $classId)
              ->where('termid', $termId)
              ->where('sessionid', $sessionId);
        }])->findOrFail($studentId);

        $schoolClass = Schoolclass::with('armRelation')->findOrFail($classId);
        $term = Schoolterm::findOrFail($termId);
        $session = Schoolsession::findOrFail($sessionId);

        // Get available bills with balances and adjustments
        $availableBills = $this->getAvailableBillsForPayment($studentId, $classId, $termId, $sessionId);

        // Get overall payment status
        $paymentStatus = $this->getStudentPaymentStatus($studentId, $classId, $termId, $sessionId);

        // Get payment history
        $paymentHistory = $this->getStudentPaymentHistory($studentId, $classId, $termId, $sessionId);

        // Get active payment gateways
        $gateways = PaymentGateway::where('is_active', true)->get();

        // Get school information for receipt
        $schoolInfo = \App\Models\SchoolInformation::first();

        $pagetitle = 'Flexible Payment - ' . $student->firstname . ' ' . $student->lastname;

        return view('payment.flexible-payment', compact(
            'student',
            'schoolClass',
            'term',
            'session',
            'availableBills',
            'paymentStatus',
            'paymentHistory',
            'classId',
            'termId',
            'sessionId',
            'gateways',
            'schoolInfo'
        ));
    }

    /**
     * Initialize online payment with flexible amounts (AJAX).
     */
    public function initializeFlexiblePayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:studentRegistration,id',
                'class_id' => 'required|exists:schoolclass,id',
                'term_id' => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id',
                'payment_items' => 'required|array|min:1',
                'payment_items.*.bill_id' => 'required|exists:school_bill,id',
                'payment_items.*.amount' => 'required|numeric|min:0.01',
                'email' => 'required|email',
                'gateway' => 'required|in:paystack,remita,flutterwave',
                'phone' => 'nullable|string|max:20',
                'callback_url' => 'nullable|url',
            ]);

            $student = Student::find($request->student_id);
            $totalAmount = array_sum(array_column($request->payment_items, 'amount'));

            if ($totalAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total payment amount must be greater than zero'
                ], 422);
            }

            // Validate that amounts don't exceed balances
            $validationResult = $this->validatePaymentAmounts(
                $request->student_id,
                $request->class_id,
                $request->term_id,
                $request->session_id,
                $request->payment_items
            );

            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message'],
                    'errors' => $validationResult['errors'] ?? []
                ], 422);
            }

            // Get adjusted amounts with scholarships/discounts
            $adjustedItems = $this->getAdjustedPaymentItems(
                $request->student_id,
                $request->class_id,
                $request->term_id,
                $request->session_id,
                $request->payment_items
            );

            // Calculate total savings
            $totalSavings = array_sum(array_column($adjustedItems, 'savings'));

            // Store payment session data
            $sessionData = [
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'term_id' => $request->term_id,
                'session_id' => $request->session_id,
                'payment_items' => $adjustedItems,
                'total_amount' => $totalAmount,
                'total_savings' => $totalSavings,
                'email' => $request->email,
                'phone' => $request->phone,
                'gateway' => $request->gateway,
                'callback_url' => $request->callback_url,
                'student_name' => $student->firstname . ' ' . $student->lastname,
                'admission_no' => $student->admissionNo,
                'timestamp' => now()->toIso8601String(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // Encrypt session data for security
            $encryptedSessionData = encrypt(json_encode($sessionData));
            session(['flexible_payment' => $sessionData]);
            session(['flexible_payment_token' => hash('sha256', $encryptedSessionData)]);

            // Initialize payment based on selected gateway
            switch ($request->gateway) {
                case 'paystack':
                    $result = $this->initializePaystackPayment($student, $totalAmount, $request->email, $adjustedItems, $request->phone);
                    break;
                case 'remita':
                    $result = $this->initializeRemitaPayment($student, $totalAmount, $request->email, $adjustedItems, $request->phone);
                    break;
                case 'flutterwave':
                    $result = $this->initializeFlutterwavePayment($student, $totalAmount, $request->email, $adjustedItems, $request->phone);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported payment gateway'
                    ], 400);
            }

            if ($result['success']) {
                // Create online payment record
                $onlinePayment = OnlinePayment::create([
                    'reference' => $result['reference'],
                    'student_id' => $request->student_id,
                    'payment_gateway_id' => PaymentGateway::where('provider_key', $request->gateway)->first()->id,
                    'amount' => $totalAmount,
                    'fee_charged' => $result['fee_charged'] ?? 0,
                    'net_amount' => $totalAmount - ($result['fee_charged'] ?? 0),
                    'status' => 'pending',
                    'gateway_response' => json_encode($result['gateway_response'] ?? []),
                    'payment_data' => json_encode($sessionData),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Update session with online payment ID
                session(['flexible_payment_id' => $onlinePayment->id]);

                return response()->json([
                    'success' => true,
                    'authorization_url' => $result['authorization_url'],
                    'reference' => $result['reference'],
                    'online_payment_id' => $onlinePayment->id,
                    'amount' => $totalAmount,
                    'savings' => $totalSavings,
                    'message' => 'Payment initialized successfully. Redirecting to payment gateway...'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment initialization failed'
            ], 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Flexible payment initialization error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Initialize Paystack payment.
     */
    private function initializePaystackPayment($student, $amount, $email, $items, $phone = null)
    {
        $metadata = [
            'payment_type' => 'flexible',
            'student_id' => $student->id,
            'student_name' => $student->firstname . ' ' . $student->lastname,
            'student_admission' => $student->admissionNo,
            'items' => array_map(function($item) {
                return [
                    'bill_id' => $item['bill_id'],
                    'title' => $item['title'],
                    'amount' => $item['amount'],
                    'original_amount' => $item['original_amount'],
                    'savings' => $item['savings'],
                ];
            }, $items),
            'item_count' => count($items),
            'total_savings' => array_sum(array_column($items, 'savings')),
            'session_token' => session('flexible_payment_token'),
        ];

        $result = $this->paystackService->initializePayment($student, $amount, $email, $metadata, $phone);

        // Calculate Paystack fee (1.5% + ₦100)
        $fee = ($amount * 0.015) + 100;
        $result['fee_charged'] = min($fee, $amount * 0.05); // Cap at 5%

        return $result;
    }

    /**
     * Initialize Remita payment.
     */
    private function initializeRemitaPayment($student, $amount, $email, $items, $phone = null)
    {
        $gateway = PaymentGateway::where('provider_key', 'remita')->first();
        $merchantId = $gateway->mode === 'live' ? $gateway->config['live_merchant_id'] : $gateway->config['test_merchant_id'];
        $apiKey = $gateway->mode === 'live' ? $gateway->config['live_api_key'] : $gateway->config['test_api_key'];

        $reference = 'REM-' . date('YmdHis') . '-' . strtoupper(uniqid());

        $postData = [
            'merchantId' => $merchantId,
            'serviceTypeId' => $gateway->config['service_type_id'] ?? '4430731',
            'orderId' => $reference,
            'amount' => $amount,
            'payerName' => $student->firstname . ' ' . $student->lastname,
            'payerEmail' => $email,
            'payerPhone' => $phone ?? $student->phone ?? '',
            'description' => 'School Fees Payment - ' . count($items) . ' item(s)',
            'responseurl' => route('payment.callback'),
            'hash' => hash('sha512', $merchantId . $reference . $amount . $apiKey),
        ];

        // Store pending transaction in database
        OnlinePayment::create([
            'reference' => $reference,
            'student_id' => $student->id,
            'payment_gateway_id' => $gateway->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_data' => json_encode(['items' => $items]),
        ]);

        // Remita RRR generation URL
        $remitaUrl = $gateway->mode === 'live'
            ? 'https://login.remita.net/remita/external/api/invoice/v2.0/api/v2/bill/init'
            : 'https://remitademo.net/remita/external/api/invoice/v2.0/api/v2/bill/init';

        return [
            'success' => true,
            'authorization_url' => $remitaUrl . '?' . http_build_query($postData),
            'reference' => $reference,
            'fee_charged' => 0,
            'gateway_response' => $postData
        ];
    }

    /**
     * Initialize Flutterwave payment.
     */
    private function initializeFlutterwavePayment($student, $amount, $email, $items, $phone = null)
    {
        $gateway = PaymentGateway::where('provider_key', 'flutterwave')->first();
        $secretKey = $gateway->mode === 'live' ? $gateway->secret_key : $gateway->config['test_secret_key'];
        $publicKey = $gateway->mode === 'live' ? $gateway->public_key : $gateway->config['test_public_key'];

        $reference = 'FLW-' . date('YmdHis') . '-' . strtoupper(uniqid());

        $postData = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => 'NGN',
            'redirect_url' => route('payment.callback'),
            'payment_options' => 'card,banktransfer,ussd',
            'customer' => [
                'email' => $email,
                'phonenumber' => $phone ?? $student->phone ?? '',
                'name' => $student->firstname . ' ' . $student->lastname,
            ],
            'customizations' => [
                'title' => 'School Fees Payment',
                'description' => 'Payment for ' . count($items) . ' fee item(s)',
                'logo' => asset('assets/images/logo.png'),
            ],
            'meta' => [
                'student_id' => $student->id,
                'student_name' => $student->firstname . ' ' . $student->lastname,
                'items' => json_encode($items),
            ]
        ];

        $flutterwaveUrl = 'https://api.flutterwave.com/v3/payments';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $flutterwaveUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'success') {
            // Store pending transaction
            OnlinePayment::create([
                'reference' => $reference,
                'student_id' => $student->id,
                'payment_gateway_id' => $gateway->id,
                'amount' => $amount,
                'status' => 'pending',
                'gateway_response' => $response,
                'payment_data' => json_encode(['items' => $items]),
            ]);

            $fee = ($amount * 0.015) + 100; // Flutterwave charges ~1.5%

            return [
                'success' => true,
                'authorization_url' => $result['data']['link'],
                'reference' => $reference,
                'fee_charged' => min($fee, $amount * 0.05),
                'gateway_response' => $result
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Flutterwave initialization failed',
        ];
    }

    /**
     * Handle payment callback from gateway.
     */
    public function handlePaymentCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->input('reference');

        if (!$reference) {
            Log::error('Payment callback: No reference provided', ['request' => $request->all()]);
            return redirect()->route('payment.index')
                ->with('error', 'Invalid payment reference. Please contact support.');
        }

        // Get gateway from reference prefix
        $gateway = $this->detectGatewayFromReference($reference);

        try {
            switch ($gateway) {
                case 'paystack':
                    $verification = $this->paystackService->verifyPayment($reference);
                    break;
                case 'remita':
                    $verification = $this->verifyRemitaPayment($reference);
                    break;
                case 'flutterwave':
                    $verification = $this->verifyFlutterwavePayment($reference);
                    break;
                default:
                    throw new \Exception('Unknown payment gateway for reference: ' . $reference);
            }

            if ($verification['success']) {
                return $this->processSuccessfulPayment($verification['online_payment'] ?? null, $reference);
            } else {
                $errorMessage = $verification['message'] ?? 'Payment verification failed';
                Log::error('Payment verification failed', ['reference' => $reference, 'error' => $errorMessage]);

                // Update online payment status
                OnlinePayment::where('reference', $reference)->update([
                    'status' => 'failed',
                    'gateway_response' => json_encode(['error' => $errorMessage])
                ]);

                return redirect()->route('payment.index')
                    ->with('error', 'Payment verification failed: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage(), [
                'reference' => $reference,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.index')
                ->with('error', 'Payment processing error. Please contact support.');
        }
    }

    /**
     * Verify Remita payment.
     */
    private function verifyRemitaPayment($reference)
    {
        $onlinePayment = OnlinePayment::where('reference', $reference)->first();

        if (!$onlinePayment) {
            return ['success' => false, 'message' => 'Payment record not found'];
        }

        $gateway = PaymentGateway::find($onlinePayment->payment_gateway_id);
        $merchantId = $gateway->mode === 'live' ? $gateway->config['live_merchant_id'] : $gateway->config['test_merchant_id'];
        $apiKey = $gateway->mode === 'live' ? $gateway->config['live_api_key'] : $gateway->config['test_api_key'];

        $remitaUrl = $gateway->mode === 'live'
            ? "https://login.remita.net/remita/external/api/invoice/v2.0/api/v2/bill/status/{$merchantId}/{$reference}"
            : "https://remitademo.net/remita/external/api/invoice/v2.0/api/v2/bill/status/{$merchantId}/{$reference}";

        $hash = hash('sha512', $merchantId . $reference . $apiKey);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remitaUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: remitaConsumerKey=' . $merchantId . ', remitaConsumerToken=' . $hash,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'success') {
            if ($result['paid'] === true || $result['statusCode'] === '025') {
                return [
                    'success' => true,
                    'online_payment' => $onlinePayment,
                    'data' => $result
                ];
            }
        }

        return ['success' => false, 'message' => 'Payment not verified'];
    }

    /**
     * Verify Flutterwave payment.
     */
    private function verifyFlutterwavePayment($reference)
    {
        $onlinePayment = OnlinePayment::where('reference', $reference)->first();

        if (!$onlinePayment) {
            return ['success' => false, 'message' => 'Payment record not found'];
        }

        $gateway = PaymentGateway::find($onlinePayment->payment_gateway_id);
        $secretKey = $gateway->mode === 'live' ? $gateway->secret_key : $gateway->config['test_secret_key'];

        $flutterwaveUrl = "https://api.flutterwave.com/v3/transactions/{$reference}/verify";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $flutterwaveUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'success') {
            $data = $result['data'];
            if ($data['status'] === 'successful') {
                return [
                    'success' => true,
                    'online_payment' => $onlinePayment,
                    'data' => $data
                ];
            }
        }

        return ['success' => false, 'message' => 'Payment not verified'];
    }

    /**
     * Process successful payment and create all records.
     */
    protected function processSuccessfulPayment($onlinePayment, $reference)
    {
        return DB::transaction(function () use ($onlinePayment, $reference) {
            // Try to get payment data from session first, then from database
            $pendingPayment = session('flexible_payment');

            if (!$pendingPayment && $onlinePayment) {
                $pendingPayment = json_decode($onlinePayment->payment_data, true);
            }

            if (!$pendingPayment) {
                Log::error('Payment session expired', ['reference' => $reference]);
                return redirect()->route('payment.index')
                    ->with('error', 'Payment session expired. Please contact support with your reference: ' . $reference);
            }

            // Create payment batch
            $batch = PaymentBatch::create([
                'batch_no' => PaymentBatch::generateBatchNumber(),
                'student_id' => $pendingPayment['student_id'],
                'payment_date' => now(),
                'total_amount' => $pendingPayment['total_amount'],
                'payment_method' => 'Online - ' . ucfirst($pendingPayment['gateway']),
                'reference_no' => $reference,
                'status' => 'completed',
                'notes' => 'Online payment via ' . ucfirst($pendingPayment['gateway']),
                'created_by' => $pendingPayment['student_id'],
            ]);

            $processedItems = [];
            $totalPaid = 0;
            $totalSavings = 0;

            foreach ($pendingPayment['payment_items'] as $item) {
                // Get or create payment record
                $payment = StudentBillPayment::firstOrCreate(
                    [
                        'student_id' => $pendingPayment['student_id'],
                        'school_bill_id' => $item['bill_id'],
                        'class_id' => $pendingPayment['class_id'],
                        'termid_id' => $pendingPayment['term_id'],
                        'session_id' => $pendingPayment['session_id'],
                    ],
                    [
                        'payment_method' => 'Online - ' . ucfirst($pendingPayment['gateway']),
                        'payment_status' => 'pending',
                        'total_paid' => 0,
                        'total_balance' => $item['adjusted_amount'],
                        'generated_by' => $pendingPayment['student_id'],
                        'delete_status' => '1',
                    ]
                );

                $amountToPay = min($item['amount'], $payment->total_balance);

                if ($amountToPay <= 0) {
                    continue;
                }

                $balanceBefore = $payment->total_balance;
                $balanceAfter = $balanceBefore - $amountToPay;
                $newTotalPaid = $payment->total_paid + $amountToPay;
                $isComplete = $balanceAfter <= 0;

                // Update payment record
                $payment->update([
                    'total_paid' => $newTotalPaid,
                    'total_balance' => $balanceAfter,
                    'payment_status' => $isComplete ? 'completed' : 'partial',
                    'last_payment_date' => now(),
                ]);

                // Create payment record detail
                $paymentRecord = StudentBillPaymentRecord::create([
                    'student_bill_payment_id' => $payment->id,
                    'class_id' => $pendingPayment['class_id'],
                    'termid_id' => $pendingPayment['term_id'],
                    'session_id' => $pendingPayment['session_id'],
                    'amount_paid' => $amountToPay,
                    'last_payment' => $amountToPay,
                    'amount_owed' => $balanceAfter,
                    'total_bill' => $item['adjusted_amount'],
                    'complete_payment' => $isComplete ? 1 : 0,
                    'generated_by' => $pendingPayment['student_id'],
                    'transaction_reference' => $reference,
                ]);

                // Update payment book
                $paymentBook = StudentBillPaymentBook::updateOrCreate(
                    [
                        'student_id' => $pendingPayment['student_id'],
                        'school_bill_id' => $item['bill_id'],
                        'class_id' => $pendingPayment['class_id'],
                        'term_id' => $pendingPayment['term_id'],
                        'session_id' => $pendingPayment['session_id'],
                    ],
                    [
                        'amount_paid' => DB::raw("amount_paid + {$amountToPay}"),
                        'amount_owed' => $balanceAfter,
                        'payment_status' => $isComplete ? 'completed' : 'partial',
                        'generated_by' => $pendingPayment['student_id'],
                        'original_amount' => $item['original_amount'],
                        'scholarship_deduction' => $item['scholarship_deduction'],
                        'discount_deduction' => $item['discount_deduction'],
                        'adjusted_amount' => $item['adjusted_amount'],
                    ]
                );

                // Create batch item
                PaymentBatchItem::create([
                    'payment_batch_id' => $batch->id,
                    'school_bill_id' => $item['bill_id'],
                    'class_id' => $pendingPayment['class_id'],
                    'termid_id' => $pendingPayment['term_id'],
                    'session_id' => $pendingPayment['session_id'],
                    'original_amount' => $item['original_amount'],
                    'scholarship_deduction' => $item['scholarship_deduction'],
                    'discount_deduction' => $item['discount_deduction'],
                    'adjusted_amount' => $item['adjusted_amount'],
                    'amount_paid' => $amountToPay,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'student_bill_payment_id' => $payment->id,
                ]);

                $totalPaid += $amountToPay;
                $totalSavings += $item['savings'];

                $processedItems[] = [
                    'bill_id' => $item['bill_id'],
                    'title' => $item['title'],
                    'original_amount' => $item['original_amount'],
                    'adjusted_amount' => $item['adjusted_amount'],
                    'savings' => $item['savings'],
                    'amount_paid' => $amountToPay,
                    'balance_after' => $balanceAfter,
                    'is_completed' => $isComplete,
                ];
            }

            // Update online payment record
            if ($onlinePayment) {
                $onlinePayment->update([
                    'student_bill_payment_id' => $payment->id ?? null,
                    'status' => 'success',
                    'payment_date' => now(),
                    'batch_id' => $batch->id,
                    'gateway_response' => json_encode(['processed' => true, 'items' => count($processedItems)]),
                ]);
            }

            // Generate receipt data
            $receipt = $this->generateReceipt($batch, $processedItems, $pendingPayment, $totalPaid, $totalSavings, $reference);

            // Update batch with receipt data
            $batch->update(['receipt_data' => json_encode($receipt)]);

            // Send email receipt
            $this->sendPaymentReceipt($pendingPayment['email'], $receipt, $pendingPayment);

            // Clear session
            session()->forget(['flexible_payment', 'flexible_payment_token', 'flexible_payment_id']);

            // Store payment info in session for success page
            session([
                'last_payment' => [
                    'amount' => $totalPaid,
                    'savings' => $totalSavings,
                    'reference' => $reference,
                    'batch_id' => $batch->id,
                    'student_id' => $pendingPayment['student_id'],
                    'class_id' => $pendingPayment['class_id'],
                    'term_id' => $pendingPayment['term_id'],
                    'session_id' => $pendingPayment['session_id'],
                ]
            ]);

            // Redirect to success page
            return redirect()->route('payment.online.success', ['reference' => $reference])
                ->with('success', 'Payment successful! Your receipt is ready.');
        });
    }

    /**
     * Show payment success page.
     */
    public function paymentSuccess($reference)
    {
        $payment = OnlinePayment::where('reference', $reference)->firstOrFail();
        $student = Student::find($payment->student_id);
        $batch = PaymentBatch::find($payment->batch_id);

        $receiptData = $batch ? json_decode($batch->receipt_data, true) : null;

        $pagetitle = 'Payment Successful';

        return view('payment.online-success', compact('payment', 'student', 'batch', 'receiptData', 'pagetitle'));
    }

    /**
     * Generate receipt data.
     */
    private function generateReceipt($batch, $items, $paymentData, $totalPaid, $totalSavings, $reference)
    {
        $student = Student::find($paymentData['student_id']);
        $schoolInfo = \App\Models\SchoolInformation::first();

        return [
            'receipt_no' => 'RCP-' . $batch->batch_no,
            'transaction_ref' => $reference,
            'date' => now()->format('d F, Y H:i:s'),
            'student_name' => $student->firstname . ' ' . $student->lastname,
            'admission_no' => $student->admissionNo,
            'class' => $paymentData['class_id'],
            'term' => $paymentData['term_id'],
            'session' => $paymentData['session_id'],
            'payment_method' => 'Online - ' . ucfirst($paymentData['gateway']),
            'items' => $items,
            'total_amount' => $totalPaid,
            'total_savings' => $totalSavings,
            'amount_in_words' => $this->convertNumberToWords($totalPaid),
            'school_name' => $schoolInfo->school_name ?? 'School Name',
            'school_address' => $schoolInfo->school_address ?? '',
            'school_phone' => $schoolInfo->school_phone ?? '',
            'school_email' => $schoolInfo->school_email ?? '',
        ];
    }

    /**
     * Send payment receipt email.
     */
    private function sendPaymentReceipt($email, $receipt, $paymentData)
    {
        try {
            // Generate PDF receipt
            $pdf = PDF::loadView('payment.receipt-pdf', ['receipt' => $receipt]);
            $pdfPath = storage_path('app/receipts/receipt_' . $receipt['receipt_no'] . '.pdf');
            $pdf->save($pdfPath);

            // Send email with attachment
            Mail::send('payment.receipt-email', ['receipt' .$receipt, 'paymentData' => $paymentData], function ($message) use ($email, $pdfPath, $receipt) {
                $message->to($email)
                        ->subject('Payment Receipt - ' . $receipt['receipt_no'])
                        ->attach($pdfPath, [
                            'as' => 'receipt_' . $receipt['receipt_no'] . '.pdf',
                            'mime' => 'application/pdf',
                        ]);
            });

            Log::info('Payment receipt sent to: ' . $email, ['receipt_no' => $receipt['receipt_no']]);

            // Clean up temp file
            @unlink($pdfPath);

        } catch (\Exception $e) {
            Log::error('Failed to send payment receipt email: ' . $e->getMessage(), ['email' => $email]);
        }
    }

    /**
     * Webhook handler for payment gateways.
     */
    public function webhook(Request $request, $gateway)
    {
        Log::info('Webhook received for gateway: ' . $gateway, ['payload' => $request->all()]);

        try {
            switch ($gateway) {
                case 'paystack':
                    return $this->handlePaystackWebhook($request);
                case 'remita':
                    return $this->handleRemitaWebhook($request);
                case 'flutterwave':
                    return $this->handleFlutterwaveWebhook($request);
                default:
                    return response()->json(['status' => 'error', 'message' => 'Unknown gateway'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Handle Paystack webhook.
     */
    protected function handlePaystackWebhook(Request $request)
    {
        // Verify signature
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        $gateway = PaymentGateway::where('provider_key', 'paystack')->first();
        $secret = $gateway->mode === 'live' ? $gateway->secret_key : ($gateway->config['test_secret_key'] ?? '');

        if (!$signature || !hash_equals($signature, hash_hmac('sha512', $payload, $secret))) {
            Log::warning('Invalid Paystack webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->json()->all();

        switch ($event['event']) {
            case 'charge.success':
                $this->processSuccessfulWebhookPayment($event['data']);
                break;
            case 'charge.failed':
                $this->handleFailedWebhookPayment($event['data']);
                break;
            case 'charge.dispute.create':
                Log::warning('Payment dispute created', $event['data']);
                break;
            case 'charge.dispute.reminder':
                Log::info('Payment dispute reminder', $event['data']);
                break;
            case 'charge.dispute.resolve':
                Log::info('Payment dispute resolved', $event['data']);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Remita webhook.
     */
    protected function handleRemitaWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Remita webhook payload', $payload);

        if (isset($payload['RRR']) && isset($payload['status'])) {
            $reference = $payload['RRR'];
            $status = $payload['status'];

            if ($status === 'success' || $status === 'successful') {
                $this->processSuccessfulWebhookPayment(['reference' => $reference]);
            } else {
                $this->handleFailedWebhookPayment(['reference' => $reference]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Flutterwave webhook.
     */
    protected function handleFlutterwaveWebhook(Request $request)
    {
        $payload = $request->all();

        // Verify signature
        $gateway = PaymentGateway::where('provider_key', 'flutterwave')->first();
        $secretKey = $gateway->mode === 'live' ? $gateway->secret_key : $gateway->config['test_secret_key'];

        $signature = $request->header('verif-hash');
        if ($signature !== $secretKey) {
            Log::warning('Invalid Flutterwave webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if (isset($payload['event']) && $payload['event'] === 'charge.completed') {
            $data = $payload['data'];
            if ($data['status'] === 'successful') {
                $this->processSuccessfulWebhookPayment($data);
            } else {
                $this->handleFailedWebhookPayment($data);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Process webhook payment.
     */
    protected function processSuccessfulWebhookPayment($data)
    {
        $reference = $data['reference'] ?? ($data['tx_ref'] ?? null);

        if (!$reference) {
            Log::error('Webhook: No reference found in data', ['data' => $data]);
            return;
        }

        $onlinePayment = OnlinePayment::where('reference', $reference)->first();

        if ($onlinePayment && $onlinePayment->status !== 'success') {
            // Get the stored payment data
            $paymentData = json_decode($onlinePayment->payment_data, true);

            if ($paymentData) {
                // Restore session temporarily
                session(['flexible_payment' => $paymentData]);

                // Process the payment
                $result = $this->processSuccessfulPayment($onlinePayment, $reference);

                // Clear temporary session
                session()->forget('flexible_payment');
            } else {
                Log::error('Webhook: No payment data found for reference', ['reference' => $reference]);

                // Mark as success but with note
                $onlinePayment->update([
                    'status' => 'success',
                    'payment_date' => now(),
                    'gateway_response' => json_encode(['webhook' => true, 'data' => $data]),
                ]);
            }
        }
    }

    /**
     * Handle failed webhook payment.
     */
    protected function handleFailedWebhookPayment($data)
    {
        $reference = $data['reference'] ?? ($data['tx_ref'] ?? null);

        if ($reference) {
            OnlinePayment::where('reference', $reference)->update([
                'status' => 'failed',
                'gateway_response' => json_encode(['error' => true, 'data' => $data]),
            ]);
        }
    }

    /**
     * Get available bills for payment with scholarship/discount applied.
     */
    private function getAvailableBillsForPayment($studentId, $classId, $termId, $sessionId)
    {
        // Get assigned bills
        $assignedBills = SchoolBillTermSession::where('class_id', $classId)
            ->where('termid_id', $termId)
            ->where('session_id', $sessionId)
            ->whereHas('schoolBill', function($q) {
                $q->where('is_active', true);
            })
            ->with('schoolBill')
            ->orderBy('school_bill.due_date')
            ->get();

        $availableBills = [];

        foreach ($assignedBills as $assignment) {
            $bill = $assignment->schoolBill;

            // Calculate late fee if applicable
            $lateFee = $bill->calculateLateFee();

            // Get adjusted amount with scholarship/discount
            $adjustment = $bill->getAdjustedAmountForStudent($studentId, $termId, $sessionId, $classId);

            // Add late fee to adjusted amount if applicable
            $adjustment['adjusted_amount'] += $lateFee;
            $adjustment['original_amount'] += $lateFee;

            // Get existing payment
            $payment = StudentBillPayment::where('student_id', $studentId)
                ->where('school_bill_id', $bill->id)
                ->where('class_id', $classId)
                ->where('termid_id', $termId)
                ->where('session_id', $sessionId)
                ->first();

            $paidAmount = $payment ? $payment->total_paid : 0;
            $balance = max(0, $adjustment['adjusted_amount'] - $paidAmount);

            if ($balance > 0) {
                $availableBills[] = [
                    'bill_id' => $bill->id,
                    'title' => $bill->title,
                    'description' => $bill->description,
                    'category' => $bill->category,
                    'due_date' => $bill->due_date,
                    'late_fee' => $lateFee,
                    'original_amount' => $adjustment['original_amount'],
                    'scholarship_deduction' => $adjustment['scholarship_deduction'],
                    'discount_deduction' => $adjustment['discount_deduction'],
                    'adjusted_amount' => $adjustment['adjusted_amount'],
                    'savings' => $adjustment['savings'],
                    'paid_amount' => $paidAmount,
                    'balance' => $balance,
                    'payment_progress' => $adjustment['adjusted_amount'] > 0 ? round(($paidAmount / $adjustment['adjusted_amount']) * 100, 1) : 0,
                    'is_selected' => false,
                    'payment_amount' => 0,
                ];
            }
        }

        return $availableBills;
    }

    /**
     * Get student payment status summary.
     */
    private function getStudentPaymentStatus($studentId, $classId, $termId, $sessionId)
    {
        $availableBills = $this->getAvailableBillsForPayment($studentId, $classId, $termId, $sessionId);

        $totalOriginal = array_sum(array_column($availableBills, 'original_amount'));
        $totalScholarship = array_sum(array_column($availableBills, 'scholarship_deduction'));
        $totalDiscount = array_sum(array_column($availableBills, 'discount_deduction'));
        $totalAdjusted = array_sum(array_column($availableBills, 'adjusted_amount'));
        $totalPaid = array_sum(array_column($availableBills, 'paid_amount'));
        $totalOutstanding = array_sum(array_column($availableBills, 'balance'));
        $totalSavings = array_sum(array_column($availableBills, 'savings'));

        return [
            'total_original' => $totalOriginal,
            'total_scholarship_savings' => $totalScholarship,
            'total_discount_savings' => $totalDiscount,
            'total_savings' => $totalSavings,
            'total_adjusted' => $totalAdjusted,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'completion_rate' => $totalAdjusted > 0 ? round(($totalPaid / $totalAdjusted) * 100, 1) : 0,
            'has_outstanding' => $totalOutstanding > 0,
            'bills_count' => count($availableBills),
            'completed_bills' => count(array_filter($availableBills, function($bill) {
                return $bill['balance'] <= 0;
            })),
        ];
    }

    /**
     * Get student payment history.
     */
    private function getStudentPaymentHistory($studentId, $classId, $termId, $sessionId)
    {
        $payments = StudentBillPayment::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('termid_id', $termId)
            ->where('session_id', $sessionId)
            ->where('delete_status', '0')
            ->with(['schoolBill', 'paymentRecords' => function($q) {
                $q->orderBy('created_at', 'desc');
            }, 'generatedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $history = [];

        foreach ($payments as $payment) {
            foreach ($payment->paymentRecords as $record) {
                $history[] = [
                    'id' => $record->id,
                    'date' => $record->created_at,
                    'bill_title' => $payment->schoolBill->title,
                    'amount_paid' => $record->amount_paid,
                    'payment_method' => $payment->payment_method,
                    'received_by' => $payment->generatedBy->name ?? 'System',
                    'status' => $record->complete_payment ? 'Completed' : 'Partial',
                    'balance' => $record->amount_owed,
                    'invoice_no' => $record->invoiceNo,
                    'transaction_reference' => $record->transaction_reference,
                ];
            }
        }

        return $history;
    }

    /**
     * Validate payment amounts against outstanding balances.
     */
    private function validatePaymentAmounts($studentId, $classId, $termId, $sessionId, $items)
    {
        $availableBills = $this->getAvailableBillsForPayment($studentId, $classId, $termId, $sessionId);
        $billsMap = [];

        foreach ($availableBills as $bill) {
            $billsMap[$bill['bill_id']] = [
                'balance' => $bill['balance'],
                'title' => $bill['title']
            ];
        }

        $errors = [];

        foreach ($items as $index => $item) {
            if (!isset($billsMap[$item['bill_id']])) {
                $errors["payment_items.{$index}.bill_id"] = ['Bill not found or already fully paid'];
            } elseif ($item['amount'] > $billsMap[$item['bill_id']]['balance']) {
                $errors["payment_items.{$index}.amount"] = [
                    "Payment amount for {$billsMap[$item['bill_id']]['title']} cannot exceed outstanding balance of ₦" .
                    number_format($billsMap[$item['bill_id']]['balance'], 2)
                ];
            } elseif ($item['amount'] < 100) {
                $errors["payment_items.{$index}.amount"] = ['Minimum payment amount is ₦100'];
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Payment validation failed',
                'errors' => $errors
            ];
        }

        return ['success' => true];
    }

    /**
     * Get adjusted payment items with scholarship/discount applied.
     */
    private function getAdjustedPaymentItems($studentId, $classId, $termId, $sessionId, $items)
    {
        $adjustedItems = [];

        foreach ($items as $item) {
            $bill = SchoolBillModel::find($item['bill_id']);
            $adjustment = $bill->getAdjustedAmountForStudent($studentId, $termId, $sessionId, $classId);

            // Get existing payment to calculate current balance
            $payment = StudentBillPayment::where('student_id', $studentId)
                ->where('school_bill_id', $item['bill_id'])
                ->where('class_id', $classId)
                ->where('termid_id', $termId)
                ->where('session_id', $sessionId)
                ->first();

            $currentPaid = $payment ? $payment->total_paid : 0;
            $currentBalance = $adjustment['adjusted_amount'] - $currentPaid;

            $adjustedItems[] = [
                'bill_id' => $item['bill_id'],
                'title' => $bill->title,
                'amount' => min($item['amount'], $currentBalance),
                'original_amount' => $adjustment['original_amount'],
                'adjusted_amount' => $adjustment['adjusted_amount'],
                'scholarship_deduction' => $adjustment['scholarship_deduction'],
                'discount_deduction' => $adjustment['discount_deduction'],
                'savings' => $adjustment['savings'],
                'current_paid' => $currentPaid,
                'current_balance' => $currentBalance,
            ];
        }

        return $adjustedItems;
    }

    /**
     * Detect gateway from reference prefix.
     */
    private function detectGatewayFromReference($reference)
    {
        $reference = strtolower($reference);

        if (strpos($reference, 'paystk') !== false || strpos($reference, 'paystack') !== false) {
            return 'paystack';
        }
        if (strpos($reference, 'rem') !== false || strpos($reference, 'remita') !== false) {
            return 'remita';
        }
        if (strpos($reference, 'flw') !== false || strpos($reference, 'flutterwave') !== false) {
            return 'flutterwave';
        }

        // Default to paystack
        return 'paystack';
    }

    /**
     * Convert number to words.
     */
    private function convertNumberToWords($number)
    {
        $words = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $result = $words->format($number);

        // Extract Naira and Kobo
        $parts = explode('.', number_format($number, 2, '.', ''));
        $naira = (int)$parts[0];
        $kobo = (int)$parts[1];

        $nairaWords = ucfirst($words->format($naira));
        $result = $nairaWords . ' Naira';

        if ($kobo > 0) {
            $koboWords = $words->format($kobo);
            $result .= ' and ' . $koboWords . ' Kobo';
        }

        return $result . ' Only';
    }

    /**
     * Get payment status via AJAX.
     */
    public function getPaymentStatus($reference)
    {
        $payment = OnlinePayment::where('reference', $reference)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'payment_date' => $payment->payment_date,
            'reference' => $payment->reference,
        ]);
    }

    /**
     * Retry failed payment via AJAX.
     */
    public function retryPayment($onlinePaymentId)
    {
        try {
            $onlinePayment = OnlinePayment::findOrFail($onlinePaymentId);

            if ($onlinePayment->status !== 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed payments can be retried'
                ], 400);
            }

            $paymentData = json_decode($onlinePayment->payment_data, true);
            $student = Student::find($onlinePayment->student_id);

            // Calculate total amount
            $totalAmount = array_sum(array_column($paymentData['payment_items'], 'amount'));

            // Re-initialize payment based on gateway
            switch ($paymentData['gateway']) {
                case 'paystack':
                    $result = $this->paystackService->initializePayment(
                        $student,
                        $totalAmount,
                        $paymentData['email'],
                        $paymentData['payment_items'],
                        $paymentData['phone'] ?? null
                    );
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Retry not supported for this gateway'
                    ], 400);
            }

            if ($result['success']) {
                // Update existing record
                $onlinePayment->update([
                    'reference' => $result['reference'],
                    'status' => 'pending',
                    'gateway_response' => json_encode($result['gateway_response'] ?? []),
                    'payment_data' => json_encode($paymentData),
                ]);

                return response()->json([
                    'success' => true,
                    'authorization_url' => $result['authorization_url'],
                    'reference' => $result['reference']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to retry payment'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment retry error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
