<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>School-Wide Payment Analysis</title>
    <style>
        body {
            font-family: DejaVuSans, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .school-name {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 5px;
            Color: green;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .term-session {
            font-size: 14px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary-section {
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: center;
        }
        .summary-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .stat-box {
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            width: 23%;
        }
        .paid {
            color: green;
            font-weight: bold;
        }
        .partial {
            color: orange;
            font-weight: bold;
        }
        .unpaid {
            color: red;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 15px;
            color: #666;
        }
        .align-right {
            text-align: right;
        }
        .nested-table {
            margin: 0;
        }
        .nested-table th, .nested-table td {
            border: none;
            padding: 2px;
        }
        .progress-bar-container {
            width: 100%;
            background-color: #e0e0e0;
            height: 10px;
            border-radius: 5px;
            margin-top: 2px;
        }
        .progress-bar {
            height: 10px;
            border-radius: 5px;
            background-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">TRINITY INTERNATIONAL COMPREHENSIVE SCHOOL ONDO</div>
        <div class="report-title">SCHOOL-WIDE PAYMENT ANALYSIS REPORT</div>
        <div class="term-session">{{ $schoolterm->schoolterm }} - {{ $schoolsession->schoolsession }}</div>
    </div>

    <div class="summary-section">
        <div class="section-title">OVERALL PAYMENT SUMMARY</div>
        <div class="summary-stats">
            <div class="stat-box">
                <div>Total Students</div>
                <div style="font-size: 14px; font-weight: bold;">{{ $schoolTotals['totalStudents'] }}</div>
            </div>
            <div class="stat-box">
                <div>Total Expected</div>
                <div style="font-size: 14px; font-weight: bold;">{{ number_format($schoolTotals['totalBilled'], 2) }}</div>
            </div>
            <div class="stat-box">
                <div>Total Collected</div>
                <div style="font-size: 14px; font-weight: bold;">{{ number_format($schoolTotals['totalPaid'], 2) }}</div>
            </div>
            <div class="stat-box">
                <div>Collection Rate</div>
                <div style="font-size: 14px; font-weight: bold;">{{ number_format($overallPercentage, 1) }}%</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: {{ min(100, $overallPercentage) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="summary-section">
        <div class="section-title">CLASS PAYMENT SUMMARY</div>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Students</th>
                    <th>Total Expected</th>
                    <th>Total Collected</th>
                    <th>Outstanding</th>
                    <th>Collection %</th>
                    <th>Fully Paid</th>
                    <th>Partial</th>
                    <th>Unpaid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allClassesData as $classData)
                <tr>
                    <td>
                        <a href="{{ route('analysis.exportPDF', ['class_id' => $classData['class_id'], 'termid_id' => $termid_id, 'session_id' => $session_id, 'action' => 'view']) }}" 
                            class="btn btn-info me-2" target="_blank">
                                <i class="ki-duotone ki-file-search fs-2"></i>  {{ $classData['className'] }}
                        </a>
                       
                    </td>
                    <td>{{ $classData['studentCount'] }}</td>
                    <td class="align-right">{{ number_format($classData['totalBilled'], 2) }}</td>
                    <td class="align-right">{{ number_format($classData['totalPaid'], 2) }}</td>
                    <td class="align-right">{{ number_format($classData['totalBalance'], 2) }}</td>
                    <td class="align-right">{{ number_format($classData['collectionPercentage'], 1) }}%</td>
                    <td class="paid align-right">{{ $classData['paidCount'] }}</td>
                    <td class="partial align-right">{{ $classData['partialCount'] }}</td>
                    <td class="unpaid align-right">{{ $classData['unpaidCount'] }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td>{{ $schoolTotals['totalStudents'] }}</td>
                    <td class="align-right">{{ number_format($schoolTotals['totalBilled'], 2) }}</td>
                    <td class="align-right">{{ number_format($schoolTotals['totalPaid'], 2) }}</td>
                    <td class="align-right">{{ number_format($schoolTotals['totalBalance'], 2) }}</td>
                    <td class="align-right">{{ number_format($overallPercentage, 1) }}%</td>
                    <td class="align-right">{{ $schoolTotals['paidCount'] }}</td>
                    <td class="align-right">{{ $schoolTotals['partialCount'] }}</td>
                    <td class="align-right">{{ $schoolTotals['unpaidCount'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="summary-section">
        <div class="section-title">BILL COLLECTION SUMMARY</div>
        <table>
            <thead>
                <tr>
                    <th>Bill Title</th>
                    <th>Total Expected</th>
                    <th>Total Collected</th>
                    <th>Outstanding</th>
                    <th>Collection %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($billSummary as $bill)
                <tr>
                    <td>{{ $bill['title'] }}</td>
                    <td class="align-right">{{ number_format($bill['totalExpected'], 2) }}</td>
                    <td class="align-right">{{ number_format($bill['totalCollected'], 2) }}</td>
                    <td class="align-right">{{ number_format($bill['totalOutstanding'], 2) }}</td>
                    <td class="align-right">{{ number_format($bill['percentage'], 1) }}%</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="align-right">{{ number_format($schoolTotals['totalBilled'], 2) }}</td>
                    <td class="align-right">{{ number_format($schoolTotals['totalPaid'], 2) }}</td>
                    <td class="align-right">{{ number_format($schoolTotals['totalBalance'], 2) }}</td>
                    <td class="align-right">{{ number_format($overallPercentage, 1) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="summary-section">
        <div class="section-title">PAYMENT STATUS OVERVIEW</div>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="paid">Fully Paid</td>
                    <td>{{ $schoolTotals['paidCount'] }}</td>
                    <td>{{ $schoolTotals['totalStudents'] > 0 ? number_format(($schoolTotals['paidCount'] / $schoolTotals['totalStudents']) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td class="partial">Partially Paid</td>
                    <td>{{ $schoolTotals['partialCount'] }}</td>
                    <td>{{ $schoolTotals['totalStudents'] > 0 ? number_format(($schoolTotals['partialCount'] / $schoolTotals['totalStudents']) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td class="unpaid">Not Paid</td>
                    <td>{{ $schoolTotals['unpaidCount'] }}</td>
                    <td>{{ $schoolTotals['totalStudents'] > 0 ? number_format(($schoolTotals['unpaidCount'] / $schoolTotals['totalStudents']) * 100, 1) : 0 }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ date('Y-m-d H:i:s') }} | School-Wide Payment Analysis Report |  Generated by viteschool | Developed by <a href="qudroidsystems.com"> Qudroid Systems</a>
</body>
</html>