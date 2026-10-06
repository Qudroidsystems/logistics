<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Class Financial Analysis Report</title>
    <style>
        @page {
            margin: 26px 22px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #2d3748;
        }

        /* ── Letterhead ─────────────────────────────────────────── */
        .letterhead {
            width: 100%;
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .letterhead table { width: 100%; border-collapse: collapse; }
        .letterhead td { border: none; padding: 0; vertical-align: middle; }
        .lh-logo-cell { width: 70px; text-align: left; }
        .lh-logo-cell img { width: 60px; height: 60px; object-fit: contain; }
        .lh-center-cell { text-align: center; }
        .lh-stamp-cell { width: 70px; text-align: right; }
        .lh-stamp-cell img { width: 55px; height: 55px; object-fit: contain; opacity: .9; }

        .school-name {
            font-size: 19px;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: .3px;
            margin: 0 0 3px;
        }
        .school-address { font-size: 9px; color: #6b7280; margin: 0 0 2px; }
        .school-motto { font-size: 9px; font-style: italic; color: #6b7280; margin: 0; }

        .report-title-bar {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: .5px;
            background: #1e3a5f;
            color: #fff;
            padding: 7px 10px;
            margin: 12px 0 8px;
            text-align: center;
            border-radius: 3px;
        }
        .class-info-chip {
            text-align: center;
            font-size: 10.5px;
            font-weight: bold;
            color: #1e3a5f;
            padding: 6px;
            background: #eef3fb;
            border: 1px solid #d6e2f2;
            border-radius: 3px;
        }
        .class-info-chip span.sep { color: #9ca3af; margin: 0 8px; font-weight: normal; }

        /* ── Stat cards ─────────────────────────────────────────── */
        .stat-cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 14px 0; }
        .stat-cards td {
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 9px 6px;
            text-align: center;
            width: 16.6%;
        }
        .stat-label { font-size: 8.5px; color: #6b7280; text-transform: uppercase; letter-spacing: .3px; }
        .stat-value { font-size: 15px; font-weight: bold; margin-top: 3px; }

        /* ── Main table ─────────────────────────────────────────── */
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a5f;
            margin: 16px 0 6px;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #c5d3e8;
        }
        .main-table { width: 100%; border-collapse: collapse; font-size: 7.6px; }
        .main-table thead { display: table-header-group; }
        .main-table tfoot { display: table-footer-group; }
        .main-table tr { page-break-inside: avoid; }
        .main-table th {
            background: #1e3a5f;
            color: #fff;
            padding: 5px 3px;
            border: 0.5px solid #34557f;
            font-weight: bold;
            text-align: center;
        }
        .main-table td {
            border: 0.5px solid #d7e1ef;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
        }
        .main-table tbody tr:nth-child(even) { background: #f8fafc; }
        .bill-group-divider td { border-left: 1.5px solid #94a9c9; }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7.4px;
        }
        .status-paid    { background: #dcfce7; color: #166534; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-unpaid  { background: #fee2e2; color: #991b1b; }

        .benefit-badge {
            display: inline-block;
            padding: 1.5px 4px;
            border-radius: 3px;
            font-size: 6.6px;
            font-weight: bold;
            margin: 0 1px;
        }
        .benefit-scholarship { background: #fef3c7; color: #b45309; }
        .benefit-discount    { background: #ede9fe; color: #6d28d9; }

        .avatar-cell { width: 32px; }
        .avatar-img {
            width: 26px; height: 26px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #d7e1ef;
        }
        .avatar-placeholder {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            line-height: 26px;
            margin: 0 auto;
        }

        .progress-container { width: 42px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin: 0 auto; }
        .progress-fill { height: 4px; border-radius: 6px; }
        .progress-high { background: #16a34a; }
        .progress-medium { background: #d97706; }
        .progress-low { background: #dc2626; }
        .progress-text { font-size: 6.6px; color: #6b7280; display: block; margin-top: 1px; }

        .cell-muted { color: #b0b8c4; }

        .main-table tfoot tr { font-weight: bold; background: #eef1f6; }
        .main-table tfoot td { border-top: 1.5px solid #94a9c9; }

        /* ── Secondary summary tables ───────────────────────────── */
        .summary-table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-top: 4px; }
        .summary-table th {
            background: #eef3fb;
            color: #1e3a5f;
            border: 0.5px solid #d6e2f2;
            padding: 5px;
            font-weight: bold;
        }
        .summary-table td { border: 0.5px solid #e2e8f0; padding: 5px; text-align: center; }
        .summary-table tbody tr:nth-child(even) { background: #fafbfc; }
        .summary-table tfoot td { font-weight: bold; background: #eef1f6; border-top: 1.5px solid #94a9c9; }

        .two-col { width: 100%; border-collapse: collapse; }
        .two-col td { border: none; padding: 0; vertical-align: top; width: 50%; }
        .two-col td:first-child { padding-right: 10px; }
        .two-col td:last-child { padding-left: 10px; }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        /* ── Sign-off / footer ──────────────────────────────────── */
        .signoff {
            width: 100%;
            margin-top: 22px;
            border-collapse: collapse;
        }
        .signoff td { border: none; padding: 0; vertical-align: bottom; }
        .signoff-stamp { text-align: right; width: 90px; }
        .signoff-stamp img { width: 65px; height: 65px; object-fit: contain; opacity: .85; }
        .signoff-line {
            border-top: 1px solid #9ca3af;
            width: 160px;
            margin-top: 30px;
            padding-top: 3px;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }

        .footer {
            margin-top: 14px;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>

<!-- ── Letterhead ──────────────────────────────────────────────── -->
<div class="letterhead">
    <table>
        <tr>
            <td class="lh-logo-cell">
                @if($schoolInfo && $schoolInfo->logo_base64)
                    <img src="{{ $schoolInfo->logo_base64 }}" alt="Logo">
                @endif
            </td>
            <td class="lh-center-cell">
                <p class="school-name">{{ $schoolInfo->school_name ?? 'TRINITY COMPREHENSIVE INTERNATIONAL SCHOOL ONDO' }}</p>
                @if($schoolInfo && $schoolInfo->school_address)
                    <p class="school-address">{{ $schoolInfo->school_address }}</p>
                @endif
                @if($schoolInfo && $schoolInfo->school_motto)
                    <p class="school-motto">"{{ $schoolInfo->school_motto }}"</p>
                @endif
            </td>
            <td class="lh-stamp-cell">
                @if($schoolInfo && $schoolInfo->stamp_base64)
                    <img src="{{ $schoolInfo->stamp_base64 }}" alt="Stamp">
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="report-title-bar">CLASS FINANCIAL ANALYSIS REPORT</div>
<div class="class-info-chip">
    Class: {{ $schoolClass->schoolclass ?? '' }} {{ $schoolClass->arm ?? '' }}
    <span class="sep">|</span>
    Term: {{ $schoolTerm ?? '' }}
    <span class="sep">|</span>
    Session: {{ $schoolSession ?? '' }}
</div>

<!-- ── Stat cards ──────────────────────────────────────────────── -->
<table class="stat-cards">
    <tr>
        <td><div class="stat-label">Students</div><div class="stat-value" style="color:#1e3a5f;">{{ $students->count() }}</div></td>
        <td><div class="stat-label">Total Billed</div><div class="stat-value" style="color:#16a34a;">₦{{ number_format($totalPaidSum + $totalBalanceSum, 2) }}</div></td>
        <td><div class="stat-label">Total Paid</div><div class="stat-value" style="color:#2563eb;">₦{{ number_format($totalPaidSum, 2) }}</div></td>
        <td><div class="stat-label">Outstanding</div><div class="stat-value" style="color:#dc2626;">₦{{ number_format($totalBalanceSum, 2) }}</div></td>
        <td><div class="stat-label">Collection Rate</div><div class="stat-value" style="color:#d97706;">{{ $collectionRate }}%</div></td>
        <td><div class="stat-label">Savings Granted</div><div class="stat-value" style="color:#6d28d9;">₦{{ number_format($totalSavingsSum, 2) }}</div></td>
    </tr>
</table>

<!-- ── Main Student Table ──────────────────────────────────────── -->
<div class="section-heading">Student Payment Detail</div>
<table class="main-table">
    <thead>
        <tr>
            <th rowspan="2">#</th>
            <th rowspan="2">Photo</th>
            <th rowspan="2">Adm No</th>
            <th rowspan="2">Student Name</th>
            <th rowspan="2">Gender</th>
            <th rowspan="2">Benefits</th>
            @foreach($studentBillInfo as $bill)
                <th colspan="2" class="bill-group-divider">{{ $bill->title }}<br><span style="font-weight:normal;">(₦{{ number_format($bill->amount, 2) }})</span></th>
            @endforeach
            <th rowspan="2">Total Paid</th>
            <th rowspan="2">Outstanding</th>
            <th rowspan="2">Progress</th>
            <th rowspan="2">Status</th>
        </tr>
        <tr>
            @foreach($studentBillInfo as $bill)
                <th class="bill-group-divider">Paid</th>
                <th>Balance</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php $counter = 1; @endphp
        @foreach($students as $std)
            @php
                $hasScholarship = $scholarshipAssignments->has($std->stid);
                $hasDiscount    = $discountAssignments->has($std->stid);
                $status         = $studentTotals[$std->stid]['status'];
                $totalPaid      = $studentTotals[$std->stid]['totalPaid'];
                $totalBalance   = $studentTotals[$std->stid]['totalBalance'];
                $totalBilledForStudent = $studentTotals[$std->stid]['totalBilled'];
                $completion     = $totalBilledForStudent > 0 ? round(($totalPaid / $totalBilledForStudent) * 100, 1) : 0;
                $progressClass  = $completion >= 70 ? 'progress-high' : ($completion >= 40 ? 'progress-medium' : 'progress-low');
                $billsForStudent = $studentBillDetails[$std->stid] ?? [];
                $fullName = trim($std->firstname . ' ' . $std->lastname);
                $initials = strtoupper(substr($std->firstname ?? '', 0, 1) . substr($std->lastname ?? '', 0, 1)) ?: 'ST';
            @endphp
            <tr>
                <td class="text-center">{{ $counter++ }}</td>
                <td class="text-center avatar-cell">
                    @if(!empty($std->avatar_base64))
                        <img src="{{ $std->avatar_base64 }}" class="avatar-img" alt="{{ $fullName }}">
                    @else
                        <div class="avatar-placeholder">{{ $initials }}</div>
                    @endif
                </td>
                <td class="text-center">{{ $std->admissionno }}</td>
                <td class="text-left">{{ $std->lastname }} {{ $std->firstname }} {{ $std->othername }}</td>
                <td class="text-center">{{ $std->gender }}</td>
                <td class="text-center">
                    @if($hasScholarship)<span class="benefit-badge benefit-scholarship">SCH</span>@endif
                    @if($hasDiscount)<span class="benefit-badge benefit-discount">DISC</span>@endif
                    @if(!$hasScholarship && !$hasDiscount)<span class="cell-muted">—</span>@endif
                </td>
                @foreach($studentBillInfo as $bill)
                    @php $detail = $billsForStudent[$bill->schoolbillid] ?? null; @endphp
                    @if($detail === null)
                        <td class="cell-muted bill-group-divider">N/A</td>
                        <td class="cell-muted">N/A</td>
                    @else
                        <td class="text-end bill-group-divider">{{ $detail['paid'] > 0 ? number_format($detail['paid'], 2) : '—' }}</td>
                        <td class="text-end">{{ $detail['balance'] > 0 ? number_format($detail['balance'], 2) : '—' }}</td>
                    @endif
                @endforeach
                <td class="text-end">₦{{ number_format($totalPaid, 2) }}</td>
                <td class="text-end">₦{{ number_format($totalBalance, 2) }}</td>
                <td class="text-center">
                    <div class="progress-container">
                        <div class="progress-fill {{ $progressClass }}" style="width: {{ $completion }}%"></div>
                    </div>
                    <span class="progress-text">{{ $completion }}%</span>
                </td>
                <td class="text-center">
                    @if($status == 'paid')
                        <span class="status-badge status-paid">Fully Paid</span>
                    @elseif($status == 'partial')
                        <span class="status-badge status-partial">Partial</span>
                    @else
                        <span class="status-badge status-unpaid">Unpaid</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="text-end">TOTALS</td>
            @foreach($studentBillInfo as $bill)
                @php $bs = $billSummary[$bill->schoolbillid] ?? ['collected' => 0, 'expected' => 0]; @endphp
                <td class="text-end bill-group-divider">₦{{ number_format($bs['collected'], 2) }}</td>
                <td class="text-end">₦{{ number_format(max(0, $bs['expected'] - $bs['collected']), 2) }}</td>
            @endforeach
            <td class="text-end">₦{{ number_format($totalPaidSum, 2) }}</td>
            <td class="text-end">₦{{ number_format($totalBalanceSum, 2) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<!-- ── Collection Summary + Status Overview, side by side ───────── -->
<table class="two-col">
    <tr>
        <td>
            <div class="section-heading">Collection Summary by Bill</div>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Bill</th>
                        <th>List Price</th>
                        <th>Expected*</th>
                        <th>Collected</th>
                        <th>Outstanding</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($studentBillInfo as $bill)
                        @php
                            $bs = $billSummary[$bill->schoolbillid] ?? ['expected' => 0, 'collected' => 0, 'unit_amount' => $bill->amount];
                            $outstanding = max(0, $bs['expected'] - $bs['collected']);
                            $pct = $bs['expected'] > 0 ? ($bs['collected'] / $bs['expected']) * 100 : 0;
                        @endphp
                        <tr>
                            <td class="text-left">{{ $bill->title }}</td>
                            <td class="text-end">₦{{ number_format($bs['unit_amount'], 2) }}</td>
                            <td class="text-end">₦{{ number_format($bs['expected'], 2) }}</td>
                            <td class="text-end">₦{{ number_format($bs['collected'], 2) }}</td>
                            <td class="text-end">₦{{ number_format($outstanding, 2) }}</td>
                            <td class="text-end">{{ number_format($pct, 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text-left">TOTAL</td>
                        <td></td>
                        <td class="text-end">₦{{ number_format($totalPaidSum + $totalBalanceSum, 2) }}</td>
                        <td class="text-end">₦{{ number_format($totalPaidSum, 2) }}</td>
                        <td class="text-end">₦{{ number_format($totalBalanceSum, 2) }}</td>
                        <td class="text-end">{{ $collectionRate }}%</td>
                    </tr>
                </tfoot>
            </table>
            <p style="font-size:7px; color:#9ca3af; margin-top:4px;">
                *Expected reflects each student's adjusted bill (after scholarship/discount), not list price times headcount.
            </p>
        </td>
        <td>
            <div class="section-heading">Payment Status Overview</div>
            @php
                $statusCounts = ['paid' => 0, 'partial' => 0, 'unpaid' => 0];
                foreach ($studentTotals as $total) { $statusCounts[$total['status']]++; }
                $totalStdCount = count($studentTotals);
            @endphp
            <table class="summary-table">
                <thead>
                    <tr><th>Status</th><th>Count</th><th>Percentage</th></tr>
                </thead>
                <tbody>
                    <tr><td class="text-left"><span class="status-badge status-paid">Fully Paid</span></td><td>{{ $statusCounts['paid'] }}</td><td>{{ $totalStdCount > 0 ? number_format(($statusCounts['paid'] / $totalStdCount) * 100, 1) : 0 }}%</td></tr>
                    <tr><td class="text-left"><span class="status-badge status-partial">Partial</span></td><td>{{ $statusCounts['partial'] }}</td><td>{{ $totalStdCount > 0 ? number_format(($statusCounts['partial'] / $totalStdCount) * 100, 1) : 0 }}%</td></tr>
                    <tr><td class="text-left"><span class="status-badge status-unpaid">Unpaid</span></td><td>{{ $statusCounts['unpaid'] }}</td><td>{{ $totalStdCount > 0 ? number_format(($statusCounts['unpaid'] / $totalStdCount) * 100, 1) : 0 }}%</td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<!-- ── Sign-off ────────────────────────────────────────────────── -->
<table class="signoff">
    <tr>
        <td><div class="signoff-line">Bursar / Accounts Officer</div></td>
        <td class="signoff-stamp">
            @if($schoolInfo && $schoolInfo->stamp_base64)
                <img src="{{ $schoolInfo->stamp_base64 }}" alt="Official Stamp">
            @endif
        </td>
    </tr>
</table>

<div class="footer">
    Generated on {{ $generatedAt }} | Page {PAGE_NUM} of {PAGE_COUNT}<br>
    Generated by ViteSchool | Developed by Qudroid Systems
</div>

</body>
</html>