<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Payment Analysis</title>
    <style>
        body {
            font-family: DejaVuSans, Arial, sans-serif;
            font-size: 8px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            padding: 2px;
        }
        td {
            padding: 2px;
            text-align: center;
        }
        .student-row:nth-child(even) {
            background-color: #f9f9f9;
        }
        .payment-status-paid {
            color: green;
            font-weight: bold;
        }
        .payment-status-partial {
            color: orange;
            font-weight: bold;
        }
        .payment-status-unpaid {
            color: red;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 15px;
        }
        .summary-section {
            margin-top: 15px;
        }
        .cell-amount {
            text-align: right;
        }
        .text-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
          <!--begin::Heading-->
          <h1 class="fs-2 fw-semibold my-2" style="color: rgb(29, 37, 195)">
                             TRINITY COMPREHENSIVE INTERNATIONAL SCHOOL ONDO
                            </h1>
                            <!--end::Heading-->
        <h2>STUDENT PAYMENT ANALYSIS REPORT</h2>
        <h3>
            Class: {{ $schoolclass->first()->schoolclass ?? '' }} {{ $schoolclass->first()->schoolarm ?? '' }} | 
            Term: {{ $schoolterm->first()->schoolterm ?? '' }} | 
            Session: {{ $schoolsession->first()->schoolsession ?? '' }}
        </h3>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">Admission No.</th>
                <th rowspan="2">Student Name</th>
                <th rowspan="2">Gender</th>
                @foreach($student_bill_info as $bill)
                    <th colspan="2">{{ $bill->title }}<br>({{ number_format($bill->amount, 2) }})</th>
                @endforeach
                <th rowspan="2">Total Paid</th>
                <th rowspan="2">Outstanding</th>
                <th rowspan="2">Status</th>
            </tr>
            <tr>
                @foreach($student_bill_info as $bill)
                    <th>Paid</th>
                    <th>Balance</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $counter = 1; @endphp
            @foreach($student as $std)
                <tr class="student-row">
                    <td>{{ $counter++ }}</td>
                    <td>{{ $std->admissionno }}</td>
                    <td style="text-align: left;">{{ $std->lastname }} {{ $std->firstname }} {{ $std->othername }}</td>
                    <td>{{ $std->gender }}</td>
                    
                    @foreach($student_bill_info as $bill)
                        @php
                            $billPayment = $studentpaymentbill->where('schoolbillid', $bill->schoolbillid)
                                                            ->where('stid', $std->stid)
                                                            ->first();
                            $amountPaid = $billPayment ? ($billPayment->totalAmountPaid ?? 0) : 0;
                            $balance = $billPayment ? ($billPayment->balance ?? $bill->amount) : $bill->amount;
                            $status = $amountPaid > 0 ? ($balance > 0 ? 'partial' : 'paid') : 'unpaid';
                        @endphp
                        <td class="cell-amount">
                            @if($amountPaid > 0)
                                {{ number_format($amountPaid, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="cell-amount">
                            @if($balance > 0)
                                {{ number_format($balance, 2) }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                    
                    <td class="cell-amount">{{ number_format($studentTotals[$std->stid]['totalPaid'], 2) }}</td>
                    <td class="cell-amount">{{ number_format($studentTotals[$std->stid]['totalBalance'], 2) }}</td>
                    <td class="payment-status-{{ $studentTotals[$std->stid]['status'] }}">
                        {{ ucfirst($studentTotals[$std->stid]['status']) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="text-bold">
                <th colspan="4">Summary</th>
                @foreach($student_bill_info as $bill)
                    @php
                        $billTotalPaid = $studentpaymentbill->where('schoolbillid', $bill->schoolbillid)->sum('totalAmountPaid') ?? 0;
                        $billTotalOwed = ($student->count() * $bill->amount) - $billTotalPaid;
                        $denominatorAmount = ($student->count() * $bill->amount);
                        $collectionPercentage = $denominatorAmount > 0 ? ($billTotalPaid / $denominatorAmount) * 100 : 0;
                    @endphp
                    <th class="cell-amount">{{ number_format($billTotalPaid, 2) }}</th>
                    <th class="cell-amount">{{ number_format($billTotalOwed, 2) }}</th>
                @endforeach
                @php
                    $overallTotalPaid = array_sum(array_column($studentTotals, 'totalPaid'));
                    $overallTotalBill = $student->count() * $student_bill_info->sum('amount');
                    $overallBalance = $overallTotalBill - $overallTotalPaid;
                    $overallPercentage = $overallTotalBill > 0 ? ($overallTotalPaid / $overallTotalBill) * 100 : 0;
                @endphp
                <th class="cell-amount">{{ number_format($overallTotalPaid, 2) }}</th>
                <th class="cell-amount">{{ number_format($overallBalance, 2) }}</th>
                <th>{{ number_format($overallPercentage, 1) }}%</th>
            </tr>
        </tfoot>
    </table>
    
    <div class="summary-section">
        <h3>Payment Collection Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Bill Title</th>
                    <th>Amount per Student</th>
                    <th>Total Expected</th>
                    <th>Total Collected</th>
                    <th>Outstanding</th>
                    <th>Collection %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($student_bill_info as $bill)
                    @php
                        $billTotalPaid = $studentpaymentbill->where('schoolbillid', $bill->schoolbillid)->sum('totalAmountPaid') ?? 0;
                        $totalExpected = $student->count() * $bill->amount;
                        $billTotalOwed = $totalExpected - $billTotalPaid;
                        $collectionPercentage = $totalExpected > 0 ? ($billTotalPaid / $totalExpected) * 100 : 0;
                    @endphp
                    <tr>
                        <td>{{ $bill->title }}</td>
                        <td class="cell-amount">{{ number_format($bill->amount, 2) }}</td>
                        <td class="cell-amount">{{ number_format($totalExpected, 2) }}</td>
                        <td class="cell-amount">{{ number_format($billTotalPaid, 2) }}</td>
                        <td class="cell-amount">{{ number_format($billTotalOwed, 2) }}</td>
                        <td>{{ number_format($collectionPercentage, 1) }}%</td>
                    </tr>
                @endforeach
                <tr class="text-bold">
                    <td>TOTAL</td>
                    <td class="cell-amount">{{ number_format($student_bill_info->sum('amount'), 2) }}</td>
                    <td class="cell-amount">{{ number_format($overallTotalBill, 2) }}</td>
                    <td class="cell-amount">{{ number_format($overallTotalPaid, 2) }}</td>
                    <td class="cell-amount">{{ number_format($overallBalance, 2) }}</td>
                    <td>{{ number_format($overallPercentage, 1) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="summary-section">
        <h3>Payment Status Overview</h3>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusCounts = [
                        'paid' => 0,
                        'partial' => 0,
                        'unpaid' => 0
                    ];
                    
                    foreach ($studentTotals as $total) {
                        $statusCounts[$total['status']]++;
                    }
                    
                    $totalStudents = count($studentTotals);
                @endphp
                <tr>
                    <td class="payment-status-paid">Fully Paid</td>
                    <td>{{ $statusCounts['paid'] }}</td>
                    <td>{{ $totalStudents > 0 ? number_format(($statusCounts['paid'] / $totalStudents) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td class="payment-status-partial">Partially Paid</td>
                    <td>{{ $statusCounts['partial'] }}</td>
                    <td>{{ $totalStudents > 0 ? number_format(($statusCounts['partial'] / $totalStudents) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td class="payment-status-unpaid">Not Paid</td>
                    <td>{{ $statusCounts['unpaid'] }}</td>
                    <td>{{ $totalStudents > 0 ? number_format(($statusCounts['unpaid'] / $totalStudents) * 100, 1) : 0 }}%</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p>Generated on {{ date('Y-m-d H:i:s') }} | Page: {PAGE_NUM} of {PAGE_COUNT}</p>
        <p> Generated by viteschool | Developed by <a href="qudroidsystems.com">Qudroid Systems</a></p>
    </div>
</body>
</html>