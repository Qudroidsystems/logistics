{{-- resources/views/schoolpayment/studentinvoicepdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color:#1f2937; margin:0; padding:20px; }
    * { box-sizing: border-box; }

    .header-table { width:100%; border-collapse:collapse; margin-bottom: 14px; }
    .header-table td { vertical-align: top; padding:0; }
    .logo-img { max-height: 55px; max-width: 180px; }
    .school-name { font-size:18px; font-weight:bold; color:#1e3a5f; }
    .school-meta { font-size:9px; color:#6b7280; margin-top:4px; line-height:1.6; }
    .doc-title { font-size:22px; font-weight:bold; color:#1e3a5f; text-align:right; letter-spacing:1px; }
    .doc-sub { font-size:11px; color:#6b7280; text-align:right; margin-top:2px; }
    .doc-sub-invoice { font-size:13px; color:#0d6efd; text-align:right; margin-top:2px; font-weight:bold; }

    .divider { border-bottom:2px solid #1e3a5f; margin: 8px 0 16px; }

    .info-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
    .info-table td { padding:8px 10px; font-size:11px; border:1px solid #e5e7eb; vertical-align: top; }
    .info-label { color:#6b7280; font-size:9px; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:3px; }
    .info-value { font-weight:bold; color:#1e3a5f; }

    .meta-table { width:100%; border-collapse:collapse; margin-bottom:16px; }
    .meta-table td { width:25%; padding:8px 10px; border:1px solid #e5e7eb; }
    .meta-label { color:#6b7280; font-size:9px; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:3px; }
    .meta-value { font-weight:bold; color:#1e3a5f; font-size:13px; }
    .badge-paid { color:#16a34a; }
    .badge-pending { color:#d97706; }

    table.items { width:100%; border-collapse:collapse; margin-top:6px; }
    table.items th { background:#1e3a5f; color:#fff; padding:8px; font-size:9px; text-transform:uppercase; text-align:left; letter-spacing:0.3px; }
    table.items td { padding:7px 8px; border-bottom:1px solid #e5e7eb; font-size:10px; vertical-align: top; }
    .text-right { text-align:right; }
    .text-center { text-align:center; }
    .muted { color:#6b7280; }
    .savings-note { color:#16a34a; font-size:9px; }
    .status-paid { color:#16a34a; font-weight:bold; }
    .status-pending { color:#d97706; font-weight:bold; }
    .text-success { color:#16a34a; }
    .text-danger { color:#dc2626; }

    .totals-table { width:45%; margin-left:55%; margin-top:14px; border-collapse:collapse; }
    .totals-table td { padding:6px 8px; font-size:11px; }
    .totals-table .label { color:#4b5563; }
    .totals-table .value { text-align:right; font-weight:bold; color:#1e3a5f; }
    .totals-table .savings-row .label,
    .totals-table .savings-row .value { color:#16a34a; }
    .grand-row td { border-top:2px solid #1e3a5f; font-size:14px; padding-top:10px; }
    .grand-row .value { color:#1e3a5f; }

    .payment-info { margin-top:24px; }
    .payment-info-title { font-size:9px; text-transform:uppercase; letter-spacing:0.5px; color:#6b7280; margin-bottom:8px; }

    .footer-note { margin-top:36px; font-size:9px; color:#9ca3af; text-align:center; border-top:1px solid #e5e7eb; padding-top:10px; }
    .footer-note p { margin:3px 0; }

    .signature-row { width:100%; margin-top:40px; }
    .signature-line { border-top:1px solid #1e3a5f; width:180px; margin-top:35px; }
    .signature-text { font-weight:bold; margin-top:6px; }
    .signature-sub { font-size:9px; color:#6b7280; }
    
    .naira { font-family: 'DejaVu Sans', sans-serif; }
    
    .stamp-container { display:inline-block; margin-bottom:10px; }
    .stamp-container img { max-height:70px; max-width:120px; }
</style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width:55%;">
                @if($schoolInfo && $schoolInfo->logo_base64)
                    <img src="{{ $schoolInfo->logo_base64 }}" class="logo-img" alt="School Logo">
                @else
                    <div class="school-name">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</div>
                @endif
                <div class="school-meta">
                    {{ $schoolInfo->school_address ?? '' }}<br>
                    {{ $schoolInfo->school_email ?? '' }}
                    @if($schoolInfo && $schoolInfo->formatted_phones)
                        &nbsp;|&nbsp;{{ $schoolInfo->formatted_phones }}
                    @endif
                </div>
            </td>
            <td style="width:45%;">
                <div class="doc-title">INVOICE</div>
                <div class="doc-sub-invoice">#{{ $invoiceNumber }}</div>
                <div class="doc-sub">{{ \Carbon\Carbon::now()->format('d F, Y') }}</div>
            </td>
        </tr>
    </table>
    <div class="divider"></div>

    @php $student = $studentdata->first() ?? null; @endphp
    <table class="info-table">
        <tr>
            <td style="width:25%;">
                <div class="info-label">Student Name</div>
                <div class="info-value">{{ $studentFullName ?? trim(($student->firstname ?? '').' '.($student->lastname ?? '')) }}</div>
            </td>
            <td style="width:25%;">
                <div class="info-label">Admission No</div>
                <div class="info-value">{{ $student->admissionNo ?? 'N/A' }}</div>
            </td>
            <td style="width:25%;">
                <div class="info-label">Class &amp; Arm</div>
                <div class="info-value">{{ $student->schoolclass ?? '' }} {{ $student->arm ?? '' }}</div>
            </td>
            <td style="width:25%;">
                <div class="info-label">Term / Session</div>
                <div class="info-value">{{ $schoolterm ?? 'N/A' }} &middot; {{ $schoolsession ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <div class="meta-label">Invoice Date</div>
                <div class="meta-value">{{ \Carbon\Carbon::now()->format('d F, Y') }}</div>
            </td>
            <td>
                <div class="meta-label">Due Date</div>
                <div class="meta-value">{{ \Carbon\Carbon::now()->addDays(7)->format('d F, Y') }}</div>
            </td>
            <td>
                <div class="meta-label">Payment Status</div>
                <div class="meta-value {{ $totalOutstanding == 0 ? 'badge-paid' : 'badge-pending' }}">
                    {{ $totalOutstanding == 0 ? 'FULLY PAID' : 'PARTIALLY PAID' }}
                </div>
            </td>
            <td>
                <div class="meta-label">Total Bill Amount</div>
                <div class="meta-value"><span class="naira">&#8358;</span>{{ number_format($totalBillAmount, 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:26%;">Bill Description</th>
                <th class="text-right" style="width:11%;">Amount</th>
                <th class="text-right" style="width:11%;">Previous Paid</th>
                <th class="text-right" style="width:11%;">Today's Payment</th>
                <th class="text-right" style="width:11%;">Total Paid</th>
                <th style="width:14%;">Method</th>
                <th class="text-right" style="width:12%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 1; @endphp
            @forelse($studentpaymentbill as $sp)
            <tr>
                <td class="text-center">{{ $counter++ }}</td>
                <td>
                    <strong>{{ $sp->title }}</strong>
                    @if($sp->description)
                        <br><span class="muted">{{ $sp->description }}</span>
                    @endif
                    @if(isset($sp->total_savings) && $sp->total_savings > 0)
                        <br><span class="savings-note">&#10003; Savings: <span class="naira">&#8358;</span>{{ number_format($sp->total_savings, 2) }}</span>
                    @endif
                </td>
                <td class="text-right"><span class="naira">&#8358;</span>{{ number_format($sp->amount, 2) }}</td>
                <td class="text-right"><span class="naira">&#8358;</span>{{ number_format($sp->previousPaid, 2) }}</td>
                <td class="text-right text-success"><span class="naira">&#8358;</span>{{ number_format($sp->todayPaid, 2) }}</td>
                <td class="text-right"><span class="naira">&#8358;</span>{{ number_format($sp->amountPaid, 2) }}</td>
                <td>{{ $sp->paymentMethod ?? 'N/A' }}</td>
                <td class="text-right {{ $sp->balance > 0 ? 'text-danger' : 'text-success' }}">
                    <span class="naira">&#8358;</span>{{ number_format($sp->balance, 2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding:20px;color:#9ca3af;">
                    No payment records found for this student.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal (Bill Amount)</td>
            <td class="value"><span class="naira">&#8358;</span>{{ number_format($totalBillAmount, 2) }}</td>
        </tr>
        @if(isset($totalSavings) && $totalSavings > 0)
        <tr class="savings-row">
            <td class="label">Total Savings Applied</td>
            <td class="value">-<span class="naira">&#8358;</span>{{ number_format($totalSavings, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Total Previous Payments</td>
            <td class="value"><span class="naira">&#8358;</span>{{ number_format($totalPreviousPaid, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Today's Payment</td>
            <td class="value text-success"><span class="naira">&#8358;</span>{{ number_format($totalTodayPaid, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total Amount Paid</td>
            <td class="value"><span class="naira">&#8358;</span>{{ number_format($totalPaid, 2) }}</td>
        </tr>
        <tr class="grand-row">
            <td class="label"><strong>Outstanding Balance</strong></td>
            <td class="value"><strong><span class="naira">&#8358;</span>{{ number_format($totalOutstanding, 2) }}</strong></td>
        </tr>
    </table>

    @if($studentpaymentbill->isNotEmpty())
    @php $lastPayment = $studentpaymentbill->first(); @endphp
    <div class="payment-info">
        <div class="payment-info-title">Latest Payment Information</div>
        <table class="info-table">
            <tr>
                <td style="width:33%;">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">{{ $lastPayment->paymentMethod ?? 'N/A' }}</div>
                </td>
                <td style="width:33%;">
                    <div class="info-label">Received By</div>
                    <div class="info-value">{{ $lastPayment->receivedBy ?? 'School Administration' }}</div>
                </td>
                <td style="width:34%;">
                    <div class="info-label">Payment Date</div>
                    <div class="info-value">{{ $lastPayment->paymentDate ? \Carbon\Carbon::parse($lastPayment->paymentDate)->format('d F, Y') : date('d F, Y') }}</div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <table class="signature-row">
        <tr>
            <td style="width:60%;"></td>
            <td style="width:40%;text-align:right;">
                @if($schoolInfo && $schoolInfo->stamp_base64)
                    <div class="stamp-container">
                        <img src="{{ $schoolInfo->stamp_base64 }}" alt="School Stamp">
                    </div>
                @endif
                <div class="signature-line" style="margin-left:auto;"></div>
                <div class="signature-text">Authorized Signatory</div>
                <div class="signature-sub">{{ $schoolInfo->school_name ?? 'School Name' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        <p>This is a computer-generated invoice and requires no signature.</p>
        <p>Generated on {{ \Carbon\Carbon::now()->format('d F, Y \a\t H:i') }}</p>
        <p>Invoice #{{ $invoiceNumber }}</p>
    </div>

</body>
</html>