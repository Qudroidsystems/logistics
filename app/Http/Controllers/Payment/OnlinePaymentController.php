<?php
// app/Http/Controllers/Payment/OnlinePaymentController.php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SchoolBillModel;
use App\Models\SchoolBillTermSession;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class OnlinePaymentController extends Controller
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

        $this->middleware('auth')->except(['callback', 'webhook', 'verify']);
        $this->middleware('permission:Create payment', ['only' => ['index', 'initialize']]);
    }

    /**
     * Display the online payment page with available bills.
     */
    public function index(Request $request)
    {
        $pagetitle = 'Online Payment';

        // Get available gateways
        $gateways = PaymentGateway::where('is_active', true)->get();

        // If student ID is provided, get their bills
        $studentId = $request->query('student_id');
        $classId = $request->query('class_id');
        $termId = $request->query('term_id');
        $sessionId = $request->query('session_id');

        $student = null;
        $availableBills = [];
        $paymentSummary = null;

        if ($studentId && $classId && $termId && $sessionId) {
            $student = Student::findOrFail($studentId);
            $availableBills = $this->getStudentBills($studentId, $classId, $termId, $sessionId);
            $paymentSummary = $this->getPaymentSummary($studentId, $classId, $termId, $sessionId);
        }

        // Get recent payments
        $recentPayments = $this->getRecentPayments();

        return view('payment.online.index', compact(
            'pagetitle',
            'gateways',
            'student',
            'availableBills',
            'paymentSummary',
            'recentPayments',
            'classId',
            'termId',
            'sessionId'
        ));
    }

    /**
     * Get student bills for online payment (AJAX).
     */
    public function getStudentBillsAjax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:studentRegistration,id',
            'class_id' => 'required|exists:schoolclass,id',
            'term_id' => 'required|exists:schoolterm,id',
            'session_id' => 'required|exists:schoolsession,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $bills = $this->getStudentBills(
            $request->student_id,
            $request->class_id,
            $request->term_id,
            $request->session_id
        );

        $summary = $this->getPaymentSummary(
            $request->student_id,
            $request->class_id,
            $request->term_id,
            $request->session_id
        );

        return response()->json([
            'success' => true,
            'bills' => $bills,
            'summary' => $summary
        ]);
    }

    /**
     * Initialize online payment (AJAX).
     */
    public function initialize(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:studentRegistration,id',
                'class_id' => 'required|exists:schoolclass,id',
                'term_id' => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id',
                'bill_ids' => 'required|array|min:1',
                'bill_ids.*' => 'exists:school_bill,id',
                'amounts' => 'required|array|min:1',
                'amounts.*' => 'required|numeric|min:100',
                'email' => 'required|email',
                'phone' => 'nullable|string|max:20',
                'payment_method' => 'required|in:card,bank_transfer,ussd,qr',
                'gateway' => 'required|in:paystack,remita,flutterwave',
                'callback_url' => 'nullable|url',
            ]);

            $student = Student::find($request->student_id);

            // Calculate total amount
            $totalAmount = array_sum($request->amounts);

            if ($totalAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total payment amount must be greater than zero'
                ], 422);
            }

            // Validate amounts against outstanding balances
            $validationResult = $this->validatePaymentAmounts(
                $request->student_id,
                $request->class_id,
                $request->term_id,
                $request->session_id,
                $request->bill_ids,
                $request->amounts
            );

            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message'],
                    'errors' => $validationResult['errors'] ?? []
                ], 422);
            }

            // Get bill details with adjustments
            $billDetails = $this->getBillDetailsWithAdjustments(
                $request->student_id,
                $request->class_id,
                $request->term_id,
                $request->session_id,
                $request->bill_ids,
                $request->amounts
            );

            // Calculate total savings
            $totalSavings = array_sum(array_column($billDetails, 'savings'));

            // Store payment session data
            $sessionData = [
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'term_id' => $request->term_id,
                'session_id' => $request->session_id,
                'bill_ids' => $request->bill_ids,
                'amounts' => $request->amounts,
                'bill_details' => $billDetails,
                'total_amount' => $totalAmount,
                'total_savings' => $totalSavings,
                'email' => $request->email,
                'phone' => $request->phone,
                'payment_method' => $request->payment_method,
                'gateway' => $request->gateway,
                'callback_url' => $request->callback_url,
                'student_name' => $student->firstname . ' ' . $student->lastname,
                'admission_no' => $student->admissionNo,
                'timestamp' => now()->toIso8601String(),
                'ip_address' => $request->ip(),
            ];

            session(['online_payment' => $sessionData]);
            session(['online_payment_token' => hash('sha256', json_encode($sessionData))]);

            // Initialize payment based on selected gateway
            switch ($request->gateway) {
                case 'paystack':
                    $result = $this->initializePaystackPayment($student, $totalAmount, $request->email, $billDetails, $request->phone);
                    break;
                case 'remita':
                    $result = $this->initializeRemitaPayment($student, $totalAmount, $request->email, $billDetails, $request->phone);
                    break;
                case 'flutterwave':
                    $result = $this->initializeFlutterwavePayment($student, $totalAmount, $request->email, $billDetails, $request->phone);
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
                    'payment_method' => $request->payment_method,
                    'gateway_response' => json_encode($result['gateway_response'] ?? []),
                    'payment_data' => json_encode($sessionData),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                session(['online_payment_id' => $onlinePayment->id]);

                // If using Paystack, return authorization URL
                if ($request->gateway === 'paystack' && isset($result['authorization_url'])) {
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

                // For Remita, return RRR and redirect URL
                if ($request->gateway === 'remita' && isset($result['rrr'])) {
                    return response()->json([
                        'success' => true,
                        'rrr' => $result['rrr'],
                        'reference' => $result['reference'],
                        'online_payment_id' => $onlinePayment->id,
                        'amount' => $totalAmount,
                        'savings' => $totalSavings,
                        'payment_url' => $result['payment_url'],
                        'message' => 'Payment initialized successfully. Please complete payment on Remita.'
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'reference' => $result['reference'],
                    'online_payment_id' => $onlinePayment->id,
                    'amount' => $totalAmount,
                    'savings' => $totalSavings,
                    'message' => 'Payment initialized successfully.'
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
            Log::error('Online payment initialization error: ' . $e->getMessage(), [
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
    private function initializePaystackPayment($student, $amount, $email, $billDetails, $phone = null)
    {
        $metadata = [
            'payment_type' => 'online_payment',
            'student_id' => $student->id,
            'student_name' => $student->firstname . ' ' . $student->lastname,
            'student_admission' => $student->admissionNo,
            'bills' => array_map(function($bill) {
                return [
                    'bill_id' => $bill['bill_id'],
                    'title' => $bill['title'],
                    'amount' => $bill['payment_amount'],
                    'original_amount' => $bill['original_amount'],
                    'savings' => $bill['savings'],
                ];
            }, $billDetails),
            'bill_count' => count($billDetails),
            'total_savings' => array_sum(array_column($billDetails, 'savings')),
            'session_token' => session('online_payment_token'),
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
    private function initializeRemitaPayment($student, $amount, $email, $billDetails, $phone = null)
    {
        $gateway = PaymentGateway::where('provider_key', 'remita')->first();

        if (!$gateway) {
            return ['success' => false, 'message' => 'Remita gateway not configured'];
        }

        $merchantId = $gateway->mode === 'live'
            ? ($gateway->config['live_merchant_id'] ?? '')
            : ($gateway->config['test_merchant_id'] ?? '2547916');

        $apiKey = $gateway->mode === 'live'
            ? ($gateway->config['live_api_key'] ?? '')
            : ($gateway->config['test_api_key'] ?? '');

        $serviceTypeId = $gateway->config['service_type_id'] ?? '4430731';
        $reference = 'REM-' . date('YmdHis') . '-' . strtoupper(uniqid());

        $postData = [
            'merchantId' => $merchantId,
            'serviceTypeId' => $serviceTypeId,
            'orderId' => $reference,
            'amount' => $amount,
            'payerName' => $student->firstname . ' ' . $student->lastname,
            'payerEmail' => $email,
            'payerPhone' => $phone ?? $student->phone ?? '',
            'description' => 'School Fees Payment - ' . count($billDetails) . ' item(s)',
            'responseurl' => route('payment.online.callback'),
            'hash' => hash('sha512', $merchantId . $reference . $amount . $apiKey),
        ];

        $remitaUrl = $gateway->mode === 'live'
            ? 'https://login.remita.net/remita/external/api/invoice/v2.0/api/v2/bill/init'
            : 'https://remitademo.net/remita/external/api/invoice/v2.0/api/v2/bill/init';

        // Store pending transaction
        OnlinePayment::create([
            'reference' => $reference,
            'student_id' => $student->id,
            'payment_gateway_id' => $gateway->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_data' => json_encode(['bills' => $billDetails]),
        ]);

        return [
            'success' => true,
            'reference' => $reference,
            'rrr' => $reference,
            'payment_url' => $remitaUrl . '?' . http_build_query($postData),
            'fee_charged' => 0,
            'gateway_response' => $postData
        ];
    }

    /**
     * Initialize Flutterwave payment.
     */
    private function initializeFlutterwavePayment($student, $amount, $email, $billDetails, $phone = null)
    {
        $gateway = PaymentGateway::where('provider_key', 'flutterwave')->first();

        if (!$gateway) {
            return ['success' => false, 'message' => 'Flutterwave gateway not configured'];
        }

        $secretKey = $gateway->mode === 'live'
            ? $gateway->secret_key
            : ($gateway->config['test_secret_key'] ?? '');

        $publicKey = $gateway->mode === 'live'
            ? $gateway->public_key
            : ($gateway->config['test_public_key'] ?? '');

        $reference = 'FLW-' . date('YmdHis') . '-' . strtoupper(uniqid());

        $postData = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => 'NGN',
            'redirect_url' => route('payment.online.callback'),
            'payment_options' => 'card,banktransfer,ussd',
            'customer' => [
                'email' => $email,
                'phonenumber' => $phone ?? $student->phone ?? '',
                'name' => $student->firstname . ' ' . $student->lastname,
            ],
            'customizations' => [
                'title' => 'School Fees Payment',
                'description' => 'Payment for ' . count($billDetails) . ' fee item(s)',
                'logo' => asset('assets/images/logo.png'),
            ],
            'meta' => [
                'student_id' => $student->id,
                'student_name' => $student->firstname . ' ' . $student->lastname,
                'bills' => json_encode($billDetails),
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

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
                'payment_data' => json_encode(['bills' => $billDetails]),
            ]);

            $fee = ($amount * 0.015) + 100;

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
    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->input('reference') ?? $request->input('tx_ref');

        if (!$reference) {
            Log::error('Payment callback: No reference provided', ['request' => $request->all()]);
            return redirect()->route('payment.online.index')
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
                $result = $this->processSuccessfulPayment($verification['online_payment'] ?? null, $reference);

                if ($result['success']) {
                    return redirect()->route('payment.online.success', ['reference' => $reference])
                        ->with('success', 'Payment successful! Your receipt is ready.');
                } else {
                    return redirect()->route('payment.online.index')
                        ->with('error', 'Payment recorded but receipt generation failed. Please contact support.');
                }
            } else {
                $errorMessage = $verification['message'] ?? 'Payment verification failed';
                Log::error('Payment verification failed', ['reference' => $reference, 'error' => $errorMessage]);

                // Update online payment status
                OnlinePayment::where('reference', $reference)->update([
                    'status' => 'failed',
                    'gateway_response' => json_encode(['error' => $errorMessage])
                ]);

                return redirect()->route('payment.online.index')
                    ->with('error', 'Payment verification failed: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage(), [
                'reference' => $reference,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.online.index')
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

        if (!$gateway) {
            return ['success' => false, 'message' => 'Gateway not found'];
        }

        $merchantId = $gateway->mode === 'live'
            ? ($gateway->config['live_merchant_id'] ?? '')
            : ($gateway->config['test_merchant_id'] ?? '2547916');

        $apiKey = $gateway->mode === 'live'
            ? ($gateway->config['live_api_key'] ?? '')
            : ($gateway->config['test_api_key'] ?? '');

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'success') {
            if (($result['paid'] === true) || ($result['statusCode'] === '025')) {
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

        if (!$gateway) {
            return ['success' => false, 'message' => 'Gateway not found'];
        }

        $secretKey = $gateway->mode === 'live'
            ? $gateway->secret_key
            : ($gateway->config['test_secret_key'] ?? '');

        $flutterwaveUrl = "https://api.flutterwave.com/v3/transactions/{$reference}/verify";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $flutterwaveUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

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
            $paymentData = session('online_payment');

            if (!$paymentData && $onlinePayment) {
                $paymentData = json_decode($onlinePayment->payment_data, true);
            }

            if (!$paymentData) {
                Log::error('Payment session expired', ['reference' => $reference]);
                return [
                    'success' => false,
                    'message' => 'Payment session expired. Please contact support.'
                ];
            }

            // Create payment batch
            $batch = PaymentBatch::create([
                'batch_no' => PaymentBatch::generateBatchNumber(),
                'student_id' => $paymentData['student_id'],
                'payment_date' => now(),
                'total_amount' => $paymentData['total_amount'],
                'payment_method' => 'Online - ' . ucfirst($paymentData['gateway']),
                'reference_no' => $reference,
                'status' => 'completed',
                'notes' => 'Online payment via ' . ucfirst($paymentData['gateway']),
                'created_by' => $paymentData['student_id'],
            ]);

            $processedItems = [];
            $totalPaid = 0;
            $totalSavings = 0;

            foreach ($paymentData['bill_details'] as $item) {
                // Get or create payment record
                $payment = StudentBillPayment::firstOrCreate(
                    [
                        'student_id' => $paymentData['student_id'],
                        'school_bill_id' => $item['bill_id'],
                        'class_id' => $paymentData['class_id'],
                        'termid_id' => $paymentData['term_id'],
                        'session_id' => $paymentData['session_id'],
                    ],
                    [
                        'payment_method' => 'Online - ' . ucfirst($paymentData['gateway']),
                        'payment_status' => 'pending',
                        'total_paid' => 0,
                        'total_balance' => $item['adjusted_amount'],
                        'generated_by' => $paymentData['student_id'],
                        'delete_status' => '1',
                    ]
                );

                $amountToPay = $item['payment_amount'];
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
                    'class_id' => $paymentData['class_id'],
                    'termid_id' => $paymentData['term_id'],
                    'session_id' => $paymentData['session_id'],
                    'amount_paid' => $amountToPay,
                    'last_payment' => $amountToPay,
                    'amount_owed' => $balanceAfter,
                    'total_bill' => $item['adjusted_amount'],
                    'complete_payment' => $isComplete ? 1 : 0,
                    'generated_by' => $paymentData['student_id'],
                    'transaction_reference' => $reference,
                ]);

                // Update payment book
                StudentBillPaymentBook::updateOrCreate(
                    [
                        'student_id' => $paymentData['student_id'],
                        'school_bill_id' => $item['bill_id'],
                        'class_id' => $paymentData['class_id'],
                        'term_id' => $paymentData['term_id'],
                        'session_id' => $paymentData['session_id'],
                    ],
                    [
                        'amount_paid' => DB::raw("amount_paid + {$amountToPay}"),
                        'amount_owed' => $balanceAfter,
                        'payment_status' => $isComplete ? 'completed' : 'partial',
                        'generated_by' => $paymentData['student_id'],
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
                    'class_id' => $paymentData['class_id'],
                    'termid_id' => $paymentData['term_id'],
                    'session_id' => $paymentData['session_id'],
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
            $receipt = $this->generateReceipt($batch, $processedItems, $paymentData, $totalPaid, $totalSavings, $reference);

            // Update batch with receipt data
            $batch->update(['receipt_data' => json_encode($receipt)]);

            // Send email receipt
            $this->sendPaymentReceipt($paymentData['email'], $receipt, $paymentData);

            // Clear session
            session()->forget(['online_payment', 'online_payment_token', 'online_payment_id']);

            // Store payment info in session for success page
            session([
                'last_online_payment' => [
                    'amount' => $totalPaid,
                    'savings' => $totalSavings,
                    'reference' => $reference,
                    'batch_id' => $batch->id,
                    'student_id' => $paymentData['student_id'],
                    'class_id' => $paymentData['class_id'],
                    'term_id' => $paymentData['term_id'],
                    'session_id' => $paymentData['session_id'],
                ]
            ]);

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'reference' => $reference,
                'batch_id' => $batch->id
            ];
        });
    }

    /**
     * Show payment success page.
     */
    public function success($reference)
    {
        $payment = OnlinePayment::where('reference', $reference)->firstOrFail();
        $student = Student::find($payment->student_id);
        $batch = PaymentBatch::find($payment->batch_id);

        $receiptData = $batch ? json_decode($batch->receipt_data, true) : null;

        $pagetitle = 'Payment Successful';

        return view('payment.online.success', compact('payment', 'student', 'batch', 'receiptData', 'pagetitle'));
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
            'class_id' => $paymentData['class_id'],
            'term_id' => $paymentData['term_id'],
            'session_id' => $paymentData['session_id'],
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

            // Create directory if not exists
            if (!file_exists(dirname($pdfPath))) {
                mkdir(dirname($pdfPath), 0777, true);
            }

            $pdf->save($pdfPath);

            // Send email with attachment
            Mail::send('payment.receipt-email', ['receipt' => $receipt, 'paymentData' => $paymentData], function ($message) use ($email, $pdfPath, $receipt) {
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

        if (!$gateway) {
            return response()->json(['error' => 'Gateway not configured'], 500);
        }

        $secret = $gateway->mode === 'live'
            ? $gateway->secret_key
            : ($gateway->config['test_secret_key'] ?? '');

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

        if (!$gateway) {
            return response()->json(['error' => 'Gateway not configured'], 500);
        }

        $secretKey = $gateway->mode === 'live'
            ? $gateway->secret_key
            : ($gateway->config['test_secret_key'] ?? '');

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
     * Process successful webhook payment.
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
                session(['online_payment' => $paymentData]);

                // Process the payment
                $result = $this->processSuccessfulPayment($onlinePayment, $reference);

                // Clear temporary session
                session()->forget('online_payment');
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
     * Get student bills for payment.
     */
    private function getStudentBills($studentId, $classId, $termId, $sessionId)
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

        $bills = [];

        foreach ($assignedBills as $assignment) {
            $bill = $assignment->schoolBill;

            // Calculate late fee if applicable
            $lateFee = $bill->calculateLateFee();

            // Get adjusted amount with scholarship/discount
            $adjustment = $bill->getAdjustedAmountForStudent($studentId, $termId, $sessionId, $classId);

            // Add late fee
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

            $bills[] = [
                'id' => $bill->id,
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
                'is_mandatory' => $bill->is_mandatory,
            ];
        }

        return $bills;
    }

    /**
     * Get payment summary.
     */
    private function getPaymentSummary($studentId, $classId, $termId, $sessionId)
    {
        $bills = $this->getStudentBills($studentId, $classId, $termId, $sessionId);

        $totalOriginal = array_sum(array_column($bills, 'original_amount'));
        $totalScholarship = array_sum(array_column($bills, 'scholarship_deduction'));
        $totalDiscount = array_sum(array_column($bills, 'discount_deduction'));
        $totalAdjusted = array_sum(array_column($bills, 'adjusted_amount'));
        $totalPaid = array_sum(array_column($bills, 'paid_amount'));
        $totalOutstanding = array_sum(array_column($bills, 'balance'));
        $totalSavings = array_sum(array_column($bills, 'savings'));

        return [
            'total_original' => $totalOriginal,
            'total_scholarship_savings' => $totalScholarship,
            'total_discount_savings' => $totalDiscount,
            'total_savings' => $totalSavings,
            'total_adjusted' => $totalAdjusted,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'completion_rate' => $totalAdjusted > 0 ? round(($totalPaid / $totalAdjusted) * 100, 1) : 0,
            'bills_count' => count($bills),
            'completed_bills' => count(array_filter($bills, function($bill) {
                return $bill['balance'] <= 0;
            })),
        ];
    }

    /**
     * Validate payment amounts.
     */
    private function validatePaymentAmounts($studentId, $classId, $termId, $sessionId, $billIds, $amounts)
    {
        $bills = $this->getStudentBills($studentId, $classId, $termId, $sessionId);
        $billsMap = [];

        foreach ($bills as $bill) {
            $billsMap[$bill['id']] = [
                'balance' => $bill['balance'],
                'title' => $bill['title']
            ];
        }

        $errors = [];

        foreach ($billIds as $index => $billId) {
            if (!isset($billsMap[$billId])) {
                $errors["bill_ids.{$index}"] = ['Bill not found or already fully paid'];
            } elseif (isset($amounts[$index]) && $amounts[$index] > $billsMap[$billId]['balance']) {
                $errors["amounts.{$index}"] = [
                    "Payment amount for {$billsMap[$billId]['title']} cannot exceed outstanding balance of ₦" .
                    number_format($billsMap[$billId]['balance'], 2)
                ];
            } elseif (isset($amounts[$index]) && $amounts[$index] < 100) {
                $errors["amounts.{$index}"] = ['Minimum payment amount is ₦100'];
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
     * Get bill details with adjustments.
     */
    private function getBillDetailsWithAdjustments($studentId, $classId, $termId, $sessionId, $billIds, $amounts)
    {
        $billDetails = [];

        foreach ($billIds as $index => $billId) {
            $bill = SchoolBillModel::find($billId);
            $adjustment = $bill->getAdjustedAmountForStudent($studentId, $termId, $sessionId, $classId);

            // Get existing payment to calculate current balance
            $payment = StudentBillPayment::where('student_id', $studentId)
                ->where('school_bill_id', $billId)
                ->where('class_id', $classId)
                ->where('termid_id', $termId)
                ->where('session_id', $sessionId)
                ->first();

            $currentPaid = $payment ? $payment->total_paid : 0;
            $currentBalance = $adjustment['adjusted_amount'] - $currentPaid;
            $paymentAmount = min($amounts[$index] ?? $currentBalance, $currentBalance);

            $billDetails[] = [
                'bill_id' => $billId,
                'title' => $bill->title,
                'payment_amount' => $paymentAmount,
                'original_amount' => $adjustment['original_amount'],
                'adjusted_amount' => $adjustment['adjusted_amount'],
                'scholarship_deduction' => $adjustment['scholarship_deduction'],
                'discount_deduction' => $adjustment['discount_deduction'],
                'savings' => $adjustment['savings'],
                'current_paid' => $currentPaid,
                'current_balance' => $currentBalance,
            ];
        }

        return $billDetails;
    }

    /**
     * Get recent payments for the logged-in user.
     */
    private function getRecentPayments()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        // If user is staff/admin, show recent payments
        if ($user->hasRole('Staff') || $user->hasRole('Admin')) {
            return OnlinePayment::with(['student'])
                ->where('status', 'success')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        // If user is parent/student, show their payments
        $studentIds = [];

        if ($user->student_id) {
            $studentIds[] = $user->student_id;
        }

        // Get student IDs from parent relationship if applicable
        $parentId = $user->id;
        $students = \App\Models\Student::where('parent_id', $parentId)->get();

        foreach ($students as $student) {
            $studentIds[] = $student->id;
        }

        if (empty($studentIds)) {
            return [];
        }

        return OnlinePayment::whereIn('student_id', $studentIds)
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
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

        return 'paystack';
    }

    /**
     * Convert number to words.
     */
    private function convertNumberToWords($number)
    {
        $words = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $result = $words->format($number);

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
            'fee_charged' => $payment->fee_charged,
            'net_amount' => $payment->net_amount,
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
            $totalAmount = array_sum(array_column($paymentData['amounts'] ?? $paymentData['bill_details'], 'payment_amount' ?? 'amount'));

            // Re-initialize payment based on gateway
            switch ($paymentData['gateway']) {
                case 'paystack':
                    $result = $this->paystackService->initializePayment(
                        $student,
                        $totalAmount,
                        $paymentData['email'],
                        $paymentData['bill_details'] ?? [],
                        $paymentData['phone'] ?? null
                    );
                    break;
                case 'remita':
                    $result = $this->initializeRemitaPayment(
                        $student,
                        $totalAmount,
                        $paymentData['email'],
                        $paymentData['bill_details'] ?? [],
                        $paymentData['phone'] ?? null
                    );
                    break;
                case 'flutterwave':
                    $result = $this->initializeFlutterwavePayment(
                        $student,
                        $totalAmount,
                        $paymentData['email'],
                        $paymentData['bill_details'] ?? [],
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
                    'authorization_url' => $result['authorization_url'] ?? null,
                    'reference' => $result['reference'],
                    'rrr' => $result['rrr'] ?? null,
                    'payment_url' => $result['payment_url'] ?? null,
                    'message' => 'Payment retry initialized successfully'
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

    /**
     * Download receipt as PDF.
     */
    public function downloadReceipt($batchId)
    {
        $batch = PaymentBatch::with(['student', 'items.schoolBill'])->findOrFail($batchId);
        $receiptData = json_decode($batch->receipt_data, true);

        $pdf = PDF::loadView('payment.receipt-pdf', [
            'batch' => $batch,
            'receipt' => $receiptData,
            'student' => $batch->student
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('receipt_' . $batch->batch_no . '.pdf');
    }

    /**
     * Get payment analytics for dashboard (AJAX).
     */
    public function getPaymentAnalytics(Request $request)
    {
        $period = $request->query('period', 'month');

        $query = OnlinePayment::where('status', 'success');

        switch ($period) {
            case 'week':
                $query->where('created_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->subMonth());
                break;
            case 'quarter':
                $query->where('created_at', '>=', now()->subQuarter());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->subYear());
                break;
        }

        $totalAmount = $query->sum('amount');
        $totalCount = $query->count();
        $averageAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0;

        // Daily breakdown
        $dailyData = $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total')
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->limit(30)
        ->get();

        // Gateway breakdown
        $gatewayData = $query->select(
            'payment_gateway_id',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total')
        )
        ->with('gateway')
        ->groupBy('payment_gateway_id')
        ->get();

        // Payment method breakdown
        $methodData = $query->select(
            'payment_method',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total')
        )
        ->whereNotNull('payment_method')
        ->groupBy('payment_method')
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'average_amount' => $averageAmount,
                'daily_breakdown' => $dailyData,
                'gateway_breakdown' => $gatewayData,
                'method_breakdown' => $methodData,
            ]
        ]);
    }

    /**
     * Verify payment manually (admin).
     */
    public function verifyPayment(Request $request, $reference)
    {
        try {
            $onlinePayment = OnlinePayment::where('reference', $reference)->first();

            if (!$onlinePayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found'
                ], 404);
            }

            if ($onlinePayment->status === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already verified',
                    'status' => $onlinePayment->status
                ]);
            }

            $verification = $this->paystackService->verifyPayment($reference);

            if ($verification['success']) {
                $result = $this->processSuccessfulPayment($onlinePayment, $reference);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified and processed successfully',
                    'batch_id' => $result['batch_id'] ?? null
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $verification['message'] ?? 'Payment verification failed'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Manual payment verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Verification error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel pending payment.
     */
    public function cancelPayment($onlinePaymentId)
    {
        try {
            $onlinePayment = OnlinePayment::findOrFail($onlinePaymentId);

            if ($onlinePayment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payments can be cancelled'
                ], 400);
            }

            $onlinePayment->update([
                'status' => 'cancelled',
                'gateway_response' => json_encode(['cancelled_by' => Auth::id(), 'cancelled_at' => now()])
            ]);

            // Clear session if exists
            session()->forget(['online_payment', 'online_payment_token', 'online_payment_id']);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment cancellation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment'
            ], 500);
        }
    }

    /**
     * Get supported banks for payment (AJAX).
     */
    public function getSupportedBanks(Request $request)
    {
        $gateway = $request->query('gateway', 'paystack');

        if ($gateway === 'paystack') {
            $banks = $this->paystackService->getBanks();
            return response()->json([
                'success' => true,
                'banks' => $banks
            ]);
        }

        // Default bank list
        $defaultBanks = [
            ['code' => '000', 'name' => 'Access Bank'],
            ['code' => '001', 'name' => 'First Bank of Nigeria'],
            ['code' => '002', 'name' => 'GTBank'],
            ['code' => '003', 'name' => 'UBA'],
            ['code' => '004', 'name' => 'Zenith Bank'],
            ['code' => '005', 'name' => 'Fidelity Bank'],
            ['code' => '006', 'name' => 'Union Bank'],
            ['code' => '007', 'name' => 'Stanbic IBTC'],
            ['code' => '008', 'name' => 'Polaris Bank'],
            ['code' => '009', 'name' => 'Sterling Bank'],
            ['code' => '010', 'name' => 'Ecobank'],
            ['code' => '011', 'name' => 'Wema Bank'],
            ['code' => '012', 'name' => 'Heritage Bank'],
            ['code' => '013', 'name' => 'Keystone Bank'],
            ['code' => '014', 'name' => 'Jaiz Bank'],
            ['code' => '015', 'name' => 'Providus Bank'],
            ['code' => '016', 'name' => 'Titan Trust Bank'],
            ['code' => '017', 'name' => 'Globus Bank'],
        ];

        return response()->json([
            'success' => true,
            'banks' => $defaultBanks
        ]);
    }

    /**
     * Initiate bank transfer payment (Paystack).
     */
    public function initiateBankTransfer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:studentRegistration,id',
                'amount' => 'required|numeric|min:100',
                'email' => 'required|email',
                'bill_id' => 'required|exists:school_bill,id',
                'class_id' => 'required|exists:schoolclass,id',
                'term_id' => 'required|exists:schoolterm,id',
                'session_id' => 'required|exists:schoolsession,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $student = Student::find($request->student_id);

            // Store payment details
            $sessionData = [
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'term_id' => $request->term_id,
                'session_id' => $request->session_id,
                'bill_id' => $request->bill_id,
                'amount' => $request->amount,
                'email' => $request->email,
                'payment_type' => 'bank_transfer',
                'timestamp' => now()->toIso8601String(),
            ];

            session(['bank_transfer_payment' => $sessionData]);

            // Get bank transfer details from Paystack
            $result = $this->paystackService->initiateBankTransfer($student, $request->amount, $request->email);

            if ($result['success']) {
                // Create online payment record
                $onlinePayment = OnlinePayment::create([
                    'reference' => $result['reference'],
                    'student_id' => $request->student_id,
                    'payment_gateway_id' => PaymentGateway::where('provider_key', 'paystack')->first()->id,
                    'amount' => $request->amount,
                    'status' => 'pending',
                    'payment_method' => 'bank_transfer',
                    'gateway_response' => json_encode($result['data'] ?? []),
                    'payment_data' => json_encode($sessionData),
                    'ip_address' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'reference' => $result['reference'],
                    'bank_details' => [
                        'bank_name' => $result['bank_name'],
                        'account_number' => $result['account_number'],
                        'account_name' => $result['account_name'],
                        'amount' => $request->amount,
                    ],
                    'expires_at' => $result['expires_at'],
                    'online_payment_id' => $onlinePayment->id,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate bank transfer'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Bank transfer initiation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate bank transfer'
            ], 500);
        }
    }

    /**
     * Check bank transfer status (AJAX polling).
     */
    public function checkBankTransferStatus($reference)
    {
        try {
            $onlinePayment = OnlinePayment::where('reference', $reference)->first();

            if (!$onlinePayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            if ($onlinePayment->status === 'success') {
                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Payment completed successfully'
                ]);
            }

            // Verify with Paystack
            $verification = $this->paystackService->verifyPayment($reference);

            if ($verification['success']) {
                $this->processSuccessfulPayment($onlinePayment, $reference);

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Payment completed successfully'
                ]);
            }

            // Check if expired (48 hours)
            if ($onlinePayment->created_at->diffInHours(now()) > 48) {
                $onlinePayment->update(['status' => 'failed']);
                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Bank transfer window has expired'
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => 'Payment still pending'
            ]);

        } catch (\Exception $e) {
            Log::error('Bank transfer status check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Status check failed'
            ], 500);
        }
    }

    /**
     * Get payment transaction details (AJAX).
     */
    public function getTransactionDetails($reference)
    {
        try {
            $payment = OnlinePayment::where('reference', $reference)
                ->with(['student', 'gateway'])
                ->firstOrFail();

            $batch = PaymentBatch::find($payment->batch_id);
            $receiptData = $batch ? json_decode($batch->receipt_data, true) : null;

            return response()->json([
                'success' => true,
                'transaction' => [
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'date' => $payment->payment_date ?? $payment->created_at,
                    'method' => $payment->payment_method,
                    'gateway' => $payment->gateway->name ?? 'N/A',
                    'fee' => $payment->fee_charged,
                    'net_amount' => $payment->net_amount,
                ],
                'student' => [
                    'name' => $payment->student->firstname . ' ' . $payment->student->lastname,
                    'admission_no' => $payment->student->admissionNo,
                ],
                'receipt' => $receiptData,
            ]);

        } catch (\Exception $e) {
            Log::error('Get transaction details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction details'
            ], 500);
        }
    }
}
