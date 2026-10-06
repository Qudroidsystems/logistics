{{-- resources/views/student/payments/receipt-pdf.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans', sans-serif; font-size:12px; color:#1a1a2e; background:#fff; }

.page { padding:30px 36px; }

/* ── Header ── */
.header { background:#0f1c35; padding:24px 28px; border-radius:10px; margin-bottom:20px; color:#fff; display:table; width:100%; }
.header-left  { display:table-cell; vertical-align:middle; width:70%; }
.header-right { display:table-cell; vertical-align:middle; text-align:right; }
.school-name  { font-size:16px; font-weight:700; color:#fff; }
.school-sub   { font-size:10px; color:rgba(255,255,255,.65); margin-top:3px; }
.receipt-label { font-size:22px; font-weight:700; color:#c9a84c; }
.receipt-no    { font-size:10px; color:rgba(255,255,255,.7); margin-top:2px; }

/* ── Student row ── */
.student-row { display:table; width:100%; margin-bottom:18px; border:1px solid #e3e7f0; border-radius:10px; padding:16px 18px; background:#f9f7f2; }
.stu-photo-cell { display:table-cell; vertical-align:top; width:72px; }
.stu-photo { width:60px; height:60px; border-radius:50%; border:3px solid #0f1c35; object-fit:cover; }
.stu-info-cell  { display:table-cell; vertical-align:top; padding-left:14px; }
.stu-name { font-size:15px; font-weight:700; color:#0f1c35; margin-bottom:4px; }
.stu-meta { font-size:11px; color:#6b7280; }
.stu-meta span { margin-right:16px; }

/* ── Meta grid ── */
.meta-grid { display:table; width:100%; margin-bottom:18px; }
.meta-cell { display:table-cell; width:25%; padding-right:10px; }
.meta-box  { border:1px solid #e3e7f0; border-radius:8px; padding:10px 12px; background:#fff; }
.meta-label { font-size:9px; text-transform:uppercase; color:#6b7280; letter-spacing:.04em; margin-bottom:4px; }
.meta-value { font-size:13px; font-weight:700; color:#0f1c35; }

/* ── Table ── */
.bill-table { width:100%; border-collapse:collapse; margin-bottom:18px; }
.bill-table thead th { background:#0f1c35; color:#fff; padding:9px 12px; font-size:10px; text-transform:uppercase; letter-spacing:.04em; text-align:left; }
.bill-table thead th:first-child { border-radius:6px 0 0 6px; }
.bill-table thead th:last-child  { border-radius:0 6px 6px 0; text-align:right; }
.bill-table tbody td { padding:9px 12px; font-size:11px; border-bottom:1px solid #e3e7f0; }
.bill-table tbody tr:last-child td { border-bottom:none; }
.bill-table tbody tr:nth-child(even) td { background:#f9f7f2; }
.text-right { text-align:right; }
.text-center{ text-align:center; }
.text-success { color:#16a34a; }
.text-danger  { color:#dc2626; }
.savings-note { font-size:9px; color:#7c3aed; }

/* ── Totals ── */
.totals-panel { border:1px solid #e3e7f0; border-radius:10px; padding:16px 18px; background:#f9f7f2; margin-bottom:18px; }
.totals-row   { display:table; width:100%; padding:5px 0; border-bottom:1px solid #e3e7f0; }
.totals-row:last-child { border-bottom:none; }
.totals-label { display:table-cell; font-size:12px; font-weight:600; color:#374151; }
.totals-value { display:table-cell; text-align:right; font-size:13px; font-weight:700; color:#0f1c35; }
.totals-grand { background:#0f1c35; border-radius:8px; padding:12px 16px; margin-top:10px; display:table; width:100%; }
.totals-grand .totals-label,
.totals-grand .totals-value { color:#fff; font-size:14px; }

/* ── Status ── */
.status-pill { padding:4px 12px; border-radius:20px; font-size:10px; font-weight:700; display:inline-block; }
.status-paid    { background:#d1fae5; color:#065f46; }
.status-partial { background:#dbeafe; color:#1e40af; }

/* ── Footer ── */
.footer { border-top:1px dashed #ccc; padding-top:14px; text-align:center; font-size:10px; color:#6b7280; }
.footer strong { color:#0f1c35; }

.sig-row { display:table; width:100%; margin-top:20px; }
.sig-cell { display:table-cell; width:50%; }
.sig-line { border-top:2px solid #0f1c35; width:160px; margin-top:30px; }
.sig-label{ font-size:10px; color:#374151; margin-top:4px; font-weight:700; }
</style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <div class="header">
        <div class="header-left">
            @if(!empty($logoBase64))
                <img src="{{ $logoBase64 }}" alt="Logo" style="height:44px; margin-bottom:6px;">
            @endif
            <div class="school-name">{{ $schoolInfo->school_name ?? 'School Name' }}</div>
            <div class="school-sub">{{ $schoolInfo->school_address ?? '' }}</div>
        </div>
        <div class="header-right">
            <div class="receipt-label">FEE RECEIPT</div>
            <div class="receipt-no">{{ $receiptNo }}</div>
            <div class="receipt-no" style="margin-top:4px;">{{ $generatedAt }}</div>
        </div>
    </div>

    {{-- STUDENT INFO --}}
    <div class="student-row">
        <div class="stu-photo-cell">
            @if(!empty($pictureBase64))
                <img src="{{ $pictureBase64 }}" class="stu-photo" alt="Photo">
            @endif
        </div>
        <div class="stu-info-cell">
            <div class="stu-name">{{ strtoupper($student->lastname) }} {{ $student->firstname }}</div>
            <div class="stu-meta">
                <span>Adm No: {{ $student->admissionNo }}</span>
                <span>Class: {{ $className }}</span>
                <span>{{ $termName }} Term &middot; {{ $sessionName }} Session</span>
            </div>
        </div>
    </div>

    {{-- META GRID --}}
    <div class="meta-grid">
        <div class="meta-cell">
            <div class="meta-box">
                <div class="meta-label">Total Billed</div>
                <div class="meta-value">₦{{ number_format($totals['adjusted'], 2) }}</div>
            </div>
        </div>
        <div class="meta-cell">
            <div class="meta-box">
                <div class="meta-label">Amount Paid</div>
                <div class="meta-value" style="color:#16a34a">₦{{ number_format($totals['paid'], 2) }}</div>
            </div>
        </div>
        <div class="meta-cell">
            <div class="meta-box">
                <div class="meta-label">Outstanding</div>
                <div class="meta-value" style="color:{{ $totals['outstanding'] > 0 ? '#dc2626' : '#16a34a' }}">
                    ₦{{ number_format($totals['outstanding'], 2) }}
                </div>
            </div>
        </div>
        <div class="meta-cell" style="padding-right:0;">
            <div class="meta-box">
                <div class="meta-label">Total Savings</div>
                <div class="meta-value" style="color:#7c3aed">₦{{ number_format($totals['savings'], 2) }}</div>
            </div>
        </div>
    </div>

    {{-- BILL TABLE --}}
    <table class="bill-table">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:35%;">Bill Description</th>
                <th>Bill Amount</th>
                <th>Savings</th>
                <th>Adjusted</th>
                <th>Paid</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bills as $idx => $bill)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>
                    <strong>{{ $bill['title'] }}</strong>
                    @if($bill['description'])
                        <br><span style="font-size:9px; color:#6b7280;">{{ $bill['description'] }}</span>
                    @endif
                </td>
                <td>₦{{ number_format($bill['original_amount'], 2) }}</td>
                <td>
                    @if($bill['savings'] > 0)
                        <span class="savings-note">-₦{{ number_format($bill['savings'], 2) }}</span>
                    @else
                        <span style="color:#ccc;">—</span>
                    @endif
                </td>
                <td>₦{{ number_format($bill['adjusted_amount'], 2) }}</td>
                <td class="text-success">₦{{ number_format($bill['amount_paid'], 2) }}</td>
                <td class="text-right {{ $bill['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                    ₦{{ number_format($bill['balance'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <div class="totals-panel">
        <div class="totals-row">
            <span class="totals-label">Subtotal (Original)</span>
            <span class="totals-value">₦{{ number_format($totals['original'], 2) }}</span>
        </div>
        @if($totals['savings'] > 0)
        <div class="totals-row">
            <span class="totals-label" style="color:#7c3aed;">Total Savings Applied</span>
            <span class="totals-value" style="color:#7c3aed;">-₦{{ number_format($totals['savings'], 2) }}</span>
        </div>
        @endif
        <div class="totals-row">
            <span class="totals-label">Net Payable</span>
            <span class="totals-value">₦{{ number_format($totals['adjusted'], 2) }}</span>
        </div>
        <div class="totals-row">
            <span class="totals-label" style="color:#16a34a;">Amount Paid</span>
            <span class="totals-value" style="color:#16a34a;">₦{{ number_format($totals['paid'], 2) }}</span>
        </div>
        <div class="totals-grand">
            <span class="totals-label">Outstanding Balance</span>
            <span class="totals-value">₦{{ number_format($totals['outstanding'], 2) }}</span>
        </div>
    </div>

    {{-- STATUS --}}
    <div style="text-align:center; margin-bottom:16px;">
        @if($totals['outstanding'] <= 0)
            <span class="status-pill status-paid">✓ FULLY PAID</span>
        @elseif($totals['paid'] > 0)
            <span class="status-pill status-partial">⬤ PARTIALLY PAID</span>
        @else
            <span class="status-pill status-partial" style="background:#fee2e2;color:#b91c1c;">○ NOT YET PAID</span>
        @endif
    </div>

    {{-- TRANSACTIONS (from the payment ledger) --}}
    @if(isset($paymentHistory) && $paymentHistory->isNotEmpty())
    <table class="bill-table">
        <thead>
            <tr>
                <th style="width:14%;">Date</th>
                <th>Bill</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paymentHistory as $p)
            <tr>
                <td>{{ $p->paid_at?->format('d M Y') ?? '—' }}</td>
                <td>{{ $p->bill_title ?? '—' }}@if($p->status === 'reversal') <span style="color:#6d28d9;font-size:9px;">(reversal)</span>@endif</td>
                <td>{{ ucfirst($p->method) }}</td>
                <td style="font-size:9px;">{{ $p->reference ?: ($p->invoice_no ?: '—') }}</td>
                <td class="text-right {{ $p->amount_paid < 0 ? 'text-danger' : 'text-success' }}">₦{{ number_format($p->amount_paid, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- SIGNATURES --}}
    <div class="sig-row">
        <div class="sig-cell">
            <div class="sig-line"></div>
            <div class="sig-label">Student / Parent Signature</div>
        </div>
        <div class="sig-cell" style="text-align:right;">
            <div class="sig-line" style="margin-left:auto;"></div>
            <div class="sig-label">Authorized by {{ $schoolInfo->school_name ?? 'School' }}</div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="footer" style="margin-top:18px;">
        <p>This is a computer-generated receipt. For enquiries contact: <strong>{{ $schoolInfo->school_email ?? '' }}</strong> | <strong>{{ $schoolInfo->school_phone ?? '' }}</strong></p>
        <p style="margin-top:4px;">{{ $schoolInfo->school_name ?? '' }} &mdash; Thank you for your payment.</p>
    </div>

</div>
</body>
</html>
