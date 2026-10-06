<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color:#1f2937; margin:0; padding:0; }
    .header-table { width:100%; border-collapse:collapse; margin-bottom: 18px; }
    .header-table td { vertical-align: top; padding:0; }
    .logo-img { max-height: 55px; max-width: 180px; }
    .school-name { font-size:18px; font-weight:bold; color:#1e3a5f; }
    .doc-title { font-size:20px; font-weight:bold; color:#1e3a5f; text-align:right; }
    .doc-sub { font-size:11px; color:#6b7280; text-align:right; }
    .divider { border-bottom:2px solid #1e3a5f; margin: 8px 0 16px; }
    .info-table { width:100%; border-collapse:collapse; margin-bottom:16px; }
    .info-table td { padding:6px 10px; font-size:11px; border:1px solid #e5e7eb; }
    .info-label { color:#6b7280; font-size:9px; text-transform:uppercase; }
    .info-value { font-weight:bold; color:#1e3a5f; }
    table.items { width:100%; border-collapse:collapse; margin-top:10px; }
    table.items th { background:#1e3a5f; color:#fff; padding:8px; font-size:10px; text-transform:uppercase; text-align:left; }
    table.items td { padding:7px 8px; border-bottom:1px solid #e5e7eb; font-size:10px; }
    .text-right { text-align:right; }
    .totals-table { width:45%; margin-left:55%; margin-top:14px; border-collapse:collapse; }
    .totals-table td { padding:6px 8px; font-size:11px; }
    .totals-table .label { color:#4b5563; }
    .totals-table .value { text-align:right; font-weight:bold; color:#1e3a5f; }
    .grand-row td { border-top:2px solid #1e3a5f; font-size:13px; padding-top:10px; }
    .status-paid { color:#16a34a; font-weight:bold; }
    .status-pending { color:#d97706; font-weight:bold; }
    .footer-note { margin-top:30px; font-size:9px; color:#9ca3af; text-align:center; }
    .naira { font-family: 'DejaVu Sans', sans-serif; }
</style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width:60%;">
                @if($schoolInfo && $schoolInfo->logo_base64)
                    <img src="{{ $schoolInfo->logo_base64 }}" class="logo-img" alt="School Logo">
                @else
                    <div class="school-name">{{ $schoolInfo->school_name ?? 'SCHOOL NAME' }}</div>
                @endif
                <div style="font-size:10px;color:#6b7280;margin-top:4px;">
                    {{ $schoolInfo->school_address ?? '' }}<br>
                    {{ $schoolInfo->school_email ?? '' }}
                    @if($schoolInfo && $schoolInfo->formatted_phones) | {{ $schoolInfo->formatted_phones }} @endif
                </div>
            </td>
            <td style="width:40%;">
                <div class="doc-title">PAYMENT STATEMENT</div>
                <div class="doc-sub">#{{ $statementNumber }}</div>
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
                <div class="info-label">Class</div>
                <div class="info-value">{{ $student->schoolclass ?? '' }} {{ $student->arm ?? '' }}</div>
            </td>
            <td style="width:25%;">
                <div class="info-label">Term / Session</div>
                <div class="info-value">{{ $schoolterm }} &middot; {{ $schoolsession }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Bill</th>
                <th class="text-right">Bill Amount</th>
                <th class="text-right">Amount Paid</th>
                <th class="text-right">Balance</th>
                <th>Method</th>
                <th>Received By</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($studentpaymentbill as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <strong>{{ $p->title }}</strong>
                    @if($p->description)<br><span style="color:#6b7280;">{{ $p->description }}</span>@endif
                </td>
                <td class="text-right"><span class="naira">&#8358;</span>{{ number_format($p->amount, 2) }}</td>
                <td class="text-right"><span class="naira">&#8358;</span>{{ number_format($p->amount_paid, 2) }}</td>
                <td class="text-right"><span class="naira">&#8358;</span>{{ number_format($p->balance, 2) }}</td>
                <td>{{ $p->payment_method ?? 'N/A' }}</td>
                <td>{{ $p->received_by ?? 'N/A' }}</td>
                <td>{{ $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('d M Y') : 'N/A' }}</td>
                <td class="{{ $p->payment_status === 'Completed' ? 'status-paid' : 'status-pending' }}">{{ $p->payment_status ?? 'Pending' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;padding:20px;color:#9ca3af;">No payment records found for this term/session.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Total Bill Amount</td>
            <td class="value"><span class="naira">&#8358;</span>{{ number_format($totalSchoolBill, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total Amount Paid</td>
            <td class="value"><span class="naira">&#8358;</span>{{ number_format($totalPaid, 2) }}</td>
        </tr>
        <tr class="grand-row">
            <td class="label">Outstanding Balance</td>
            <td class="value"><span class="naira">&#8358;</span>{{ number_format($totalOutstanding, 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        This is a computer-generated statement and does not require a signature.<br>
        Generated on {{ \Carbon\Carbon::now()->format('d F, Y \a\t H:i') }}
    </div>

</body>
</html>