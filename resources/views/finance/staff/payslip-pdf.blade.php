{{-- resources/views/finance/staff/payslip-pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payrollRun->staff->user->name ?? 'Staff' }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .payslip-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 20px;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a5f;
            margin: 10px 0 5px;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px 10px;
            border: 1px solid #e2e8f0;
        }
        .info-table td:first-child {
            width: 35%;
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            background-color: #1e3a5f;
            color: white;
            padding: 8px 12px;
            margin: 15px 0 10px;
        }
        .earnings-table, .deductions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .earnings-table th, .deductions-table th {
            background-color: #e9ecef;
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .earnings-table td, .deductions-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        .earnings-table td:last-child, .deductions-table td:last-child {
            text-align: right;
        }
        .earnings-table tr:last-child td, .deductions-table tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .net-pay {
            background-color: #d4edda;
            padding: 12px;
            text-align: center;
            margin-top: 20px;
            border-radius: 5px;
        }
        .net-pay .label {
            font-size: 14px;
            font-weight: normal;
        }
        .net-pay .amount {
            font-size: 22px;
            font-weight: bold;
            color: #155724;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #999;
        }
        .signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 200px;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 20px;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .payslip-container {
                border: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        {{-- Header --}}
        <div class="header">
            @if(isset($schoolInfo) && $schoolInfo && $schoolInfo->logo_url)
                <img src="{{ public_path(str_replace('/storage/', 'storage/', $schoolInfo->logo_url)) }}" class="logo" alt="Logo" onerror="this.style.display='none'">
            @endif
            <div class="title">{{ $schoolInfo->school_name ?? 'School Name' }}</div>
            <div class="subtitle">{{ $schoolInfo->school_address ?? 'School Address' }}</div>
            <div class="subtitle">Tel: {{ $schoolInfo->school_phone ?? 'N/A' }} | Email: {{ $schoolInfo->school_email ?? 'N/A' }}</div>
            <div class="title" style="font-size: 18px; margin-top: 15px;">PAYSLIP</div>
            <div class="subtitle">Period: {{ $payrollRun->payrollPeriod->period_name }} ({{ $payrollRun->payrollPeriod->year }})</div>
        </div>

        {{-- Employee Information --}}
        <table class="info-table">
            <tr>
                <td>Employee Name</td>
                <td><strong>{{ $payrollRun->staff->user->name ?? 'N/A' }}</strong></td>
                <td>Employee ID</td>
                <td><strong>{{ $payrollRun->staff->employmentid ?? 'N/A' }}</strong></td>
            </tr>
            <tr>
                <td>Department</td>
                <td>{{ $payrollRun->staff->department ?? 'N/A' }}</td>
                <td>Position</td>
                <td>{{ $payrollRun->staff->position ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Bank Name</td>
                <td>{{ $payrollRun->bank_name ?? $payrollRun->staff->bank_name ?? 'N/A' }}</td>
                <td>Account Number</td>
                <td>{{ $payrollRun->account_number ?? $payrollRun->staff->account_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Payment Date</td>
                <td>{{ $payrollRun->payrollPeriod->payment_date->format('d F, Y') }}</td>
                <td>Payment Status</td>
                <td>
                    @if($payrollRun->payment_status == 'paid')
                        <span style="color: green;">✓ Paid</span>
                    @else
                        <span style="color: orange;">Pending</span>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Earnings and Deductions --}}
        <div style="display: flex; gap: 20px;">
            {{-- Earnings Section --}}
            <div style="flex: 1;">
                <div class="section-title">EARNINGS</div>
                <table class="earnings-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount (₦)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($earnings as $earning)
                        <tr>
                            <td>{{ $earning['name'] }}</td>
                            <td>{{ number_format($earning['amount'], 2) }}</td>
                        </tr>
                        @endforeach
                        <tr class="total-row">
                            <td><strong>Total Earnings</strong></td>
                            <td><strong>{{ number_format($payrollRun->total_earnings, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Deductions Section --}}
            <div style="flex: 1;">
                <div class="section-title">DEDUCTIONS</div>
                <table class="deductions-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount (₦)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deductions as $deduction)
                        <tr>
                            <td>{{ $deduction['name'] }}</td>
                            <td>{{ number_format($deduction['amount'], 2) }}</td>
                        </tr>
                        @endforeach
                        <tr class="total-row">
                            <td><strong>Total Deductions</strong></td>
                            <td><strong>{{ number_format($payrollRun->total_deductions, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Employer Contributions --}}
        @if(!empty($employerContributions) && ($employerContributions[0]['amount'] > 0 || $employerContributions[1]['amount'] > 0))
        <div class="section-title" style="margin-top: 15px;">EMPLOYER CONTRIBUTIONS</div>
        <table class="earnings-table">
            <thead>
                <tr><th>Description</th><th>Amount (₦)</th></tr>
            </thead>
            <tbody>
                @foreach($employerContributions as $contribution)
                <tr>
                    <td>{{ $contribution['name'] }}</td>
                    <td>{{ number_format($contribution['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Net Pay --}}
        <div class="net-pay">
            <div class="label">NET PAY</div>
            <div class="amount">₦ {{ number_format($payrollRun->net_pay, 2) }}</div>
            <div class="label" style="font-size: 11px; margin-top: 5px;">
                {{ ucfirst(str_replace('_', ' ', $payrollRun->payment_method ?? 'Bank Transfer')) }} Transfer
            </div>
        </div>

        {{-- Amount in Words --}}
        <div style="text-align: center; margin-top: 15px; font-size: 11px;">
            <strong>Amount in Words:</strong>
            {{ $payrollRun->net_pay > 0 ? number_format($payrollRun->net_pay, 2) . ' Naira Only' : 'Zero Naira Only' }}
        </div>

        {{-- Signatures --}}
        <div class="signature">
            <div class="signature-line">
                <div>Employee Signature</div>
            </div>
            <div class="signature-line">
                <div>Authorized Signatory</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            This is a computer-generated document and requires no signature.<br>
            Generated on: {{ now()->format('d F, Y H:i:s') }}
        </div>
    </div>
</body>
</html>
