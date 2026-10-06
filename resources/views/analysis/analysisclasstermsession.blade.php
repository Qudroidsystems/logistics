@extends('layouts.master')
@section('content')

<!--begin::Main-->
<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
    <!--begin::Content wrapper-->
    <div class="d-flex flex-column flex-column-fluid">
        <!--begin::Toolbar-->
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <!--begin::Toolbar container-->
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <!--begin::Page title-->
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        Analysis Book for Term and Session
                    </h1>
                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('analysis.index') }}" class="text-muted text-hover-primary">School Bills for Term and Sessions</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">Analysis for Term and Session</li>
                    </ul>
                    <!--end::Breadcrumb-->
                </div>
                <!--end::Page title-->

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> There were some problems with your input.<br><br>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (\Session::has('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ \Session::get('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (\Session::has('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ \Session::get('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>
            <!--end::Toolbar container-->
        </div>
        <!--end::Toolbar-->

        <!--begin::Content-->
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <!--begin::Content container-->
            <div id="kt_app_content_container" class="app-container container-xxl">
                <!--begin::Toolbar-->
                <div class="d-flex flex-wrap flex-stack my-5">
                    <h2 class="fs-2 fw-semibold my-2" style="color: rgb(29, 37, 195)">
                        ANALYSIS BOOK FOR CLASS {{ $schoolclass[0]->schoolclass }} {{ $schoolclass[0]->schoolarm }}, {{ $schoolterm[0]->schoolterm }} {{ $schoolsession[0]->schoolsession }} ACADEMIC SESSION
                    </h2>
                </div>
                <!--end::Toolbar-->

                @if ($student->isEmpty())
                    <div class="alert alert-warning">
                        No students found for the selected class, term, and session.
                    </div>
                @else
                    <div class="d-flex justify-content-end mb-5">
                        <a href="{{ route('analysis.exportPDF', ['class_id' => request()->class_id, 'termid_id' => request()->termid_id, 'session_id' => request()->session_id, 'action' => 'view']) }}" 
                           class="btn btn-info me-2" target="_blank">
                            <i class="ki-duotone ki-file-search fs-2"></i> View PDF
                        </a>
                        <a href="{{ route('analysis.viewPDF', ['class_id' => request()->class_id, 'termid_id' => request()->termid_id, 'session_id' => request()->session_id, 'action' => 'download']) }}" 
                           class="btn btn-primary">
                            <i class="ki-duotone ki-file-down fs-2"></i> Download PDF
                        </a>
                    </div>

                    <!--begin::Card-->
                    <div class="card">
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Search-->
                                <div class="d-flex align-items-center position-relative my-1" data-kt-view-roles-table-toolbar="base">
                                    <i class="ki-duotone ki-magnifier fs-1 position-absolute ms-6"><span class="path1"></span><span class="path2"></span></i>
                                    <input type="text" data-kt-roles-table-filter="search" class="form-control form-control-solid w-250px ps-15" placeholder="Search ..." />
                                </div>
                                <!--end::Search-->
                                <!--begin::Group actions-->
                                <div class="d-flex justify-content-end align-items-center d-none" data-kt-view-roles-table-toolbar="selected">
                                    <div class="fw-bold me-5">
                                        <span class="me-2" data-kt-view-roles-table-select="selected_count"></span> Selected
                                    </div>
                                    <button type="button" class="btn btn-danger" data-kt-view-roles-table-select="delete_selected">
                                        Delete Selected
                                    </button>
                                </div>
                                <!--end::Group actions-->
                            </div>
                            <!--end::Card toolbar-->

                            <!--begin::Table-->
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_roles_view_table">
                                <thead>
                                    <tr class="text-start fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-10px pe-2">
                                            <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_roles_view_table .form-check-input" value="1" />
                                            </div>
                                        </th>
                                        <th class="min-w-125px">Student Name</th>
                                        <th class="min-w-125px">Admission Number</th>
                                        @foreach ($student_bill_info as $bill)
                                            <th class="min-w-125px">{{ $bill->title }}</th>
                                        @endforeach
                                        <th class="min-w-125px">Total Paid</th>
                                        <th class="min-w-125px">Total Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-600">
                                    @php
                                        // Index payments for faster lookup
                                        $paymentsByStudentAndBill = $studentpaymentbillbook->keyBy(function ($payment) {
                                            return $payment->student_id . '-' . $payment->school_bill_id;
                                        });
                                        $totalBill = [];
                                        $totalBillBalance = [];
                                    @endphp
                                    @foreach ($student as $stu)
                                        <tr>
                                            <td>
                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" value="1" />
                                                </div>
                                            </td>
                                            <td class="d-flex align-items-center">
                                                <!--begin::Avatar-->
                                                <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                                    <a href="#">
                                                        <div class="symbol-label">
                                                            <?php
                                                            $image = $stu->picture ?? 'unnamed.png';
                                                            ?>
                                                            <img src="{{ Storage::url('images/studentavatar/' . $image) }}" alt="{{ $stu->firstname }} {{ $stu->lastname }}" class="w-100" />
                                                        </div>
                                                    </a>
                                                </div>
                                                <!--end::Avatar-->
                                                <!--begin::User details-->
                                                <div class="d-flex flex-column">
                                                    <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $stu->firstname }} {{ $stu->lastname }}</a>
                                                </div>
                                                <!--end::User details-->
                                            </td>
                                            <td>{{ $stu->admissionno }}</td>
                                            @php
                                                $studentTotalPaid = 0;
                                                $studentTotalBalance = 0;
                                            @endphp
                                            @foreach ($student_bill_info as $bill)
                                                @php
                                                    $payment = $paymentsByStudentAndBill->get($stu->stid . '-' . $bill->schoolbillid);
                                                    $paymentFound = $payment !== null;
                                                    $amountPaid = $payment ? (int)$payment->amount_paid : 0;
                                                    $balance = $payment ? $payment->amount_owed : ($bill->amount ?? 0);
                                                    $studentTotalPaid += $amountPaid;
                                                    $studentTotalBalance += $balance;
                                                    if (!isset($totalBill[$bill->schoolbillid])) {
                                                        $totalBill[$bill->schoolbillid] = 0;
                                                        $totalBillBalance[$bill->schoolbillid] = 0;
                                                    }
                                                    $totalBill[$bill->schoolbillid] += $amountPaid;
                                                    $totalBillBalance[$bill->schoolbillid] += $balance;
                                                @endphp
                                                @if ($paymentFound)
                                                    <td style="color: green">
                                                        ₦ {{ number_format($amountPaid) }}
                                                        <br>
                                                        <small style="color: rgb(77, 22, 165)">Outstanding: ₦ {{ number_format($balance) }}</small>
                                                    </td>
                                                @else
                                                    <td style="color: rgb(235, 61, 27)">
                                                        Not Paid
                                                        <br>
                                                        <small style="color: rgb(77, 22, 165)">Outstanding: ₦ {{ number_format($bill->amount ?? 0) }}</small>
                                                    </td>
                                                @endif
                                            @endforeach
                                            <td>₦ {{ number_format($studentTotalPaid) }}</td>
                                            <td>₦ {{ number_format($studentTotalBalance) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="text-start fw-bold fs-7 text-uppercase gs-0">
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        @foreach ($student_bill_info as $bill)
                                            <th>
                                                ₦ {{ number_format($totalBill[$bill->schoolbillid] ?? 0) }}
                                                <br>
                                                <small style="color: rgb(77, 22, 165)">Outstanding: ₦ {{ number_format($totalBillBalance[$bill->schoolbillid] ?? 0) }}</small>
                                            </th>
                                        @endforeach
                                        <th>₦ {{ number_format(array_sum($totalBill)) }}</th>
                                        <th>₦ {{ number_format(array_sum($totalBillBalance)) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                            <!--end::Table-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->

                    <!--begin::Chart-->
                    <div class="card mt-5">
                        <div class="card-header">
                            <h3 class="card-title">Payment Analysis by Bill</h3>
                        </div>
                        <div class="card-body">
                            @php
                                $billTitles = $student_bill_info->pluck('title')->toArray();
                                $totalPaidData = array_values($totalBill);
                                $totalOutstandingData = array_values($totalBillBalance);
                            @endphp
                            ```chartjs
                            {
                                "type": "bar",
                                "data": {
                                    "labels": {{ json_encode($billTitles) }},
                                    "datasets": [
                                        {
                                            "label": "Total Paid",
                                            "data": {{ json_encode($totalPaidData) }},
                                            "backgroundColor": "rgba(75, 192, 192, 0.7)",
                                            "borderColor": "rgba(75, 192, 192, 1)",
                                            "borderWidth": 1
                                        },
                                        {
                                            "label": "Total Outstanding",
                                            "data": {{ json_encode($totalOutstandingData) }},
                                            "backgroundColor": "rgba(255, 99, 132, 0.7)",
                                            "borderColor": "rgba(255, 99, 132, 1)",
                                            "borderWidth": 1
                                        }
                                    ]
                                },
                                "options": {
                                    "scales": {
                                        "y": {
                                            "beginAtZero": true,
                                            "title": {
                                                "display": true,
                                                "text": "Amount (₦)"
                                            }
                                        },
                                        "x": {
                                            "title": {
                                                "display": true,
                                                "text": "Bill Type"
                                            }
                                        }
                                    },
                                    "plugins": {
                                        "legend": {
                                            "position": "top"
                                        },
                                        "title": {
                                            "display": true,
                                            "text": "Payment Analysis by Bill"
                                        }
                                    }
                                }
                            }
                                                    <canvas id="paymentChart" height="100"></canvas>
                        </div> <!-- Close card-body -->
                    </div> <!-- Close card -->
                    <!--end::Chart-->

                @endif
            </div>
            <!--end::Content container-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Content wrapper-->
</div>
<!--end::Main-->

<!--begin::Scripts-->

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Optional: Set global font if you want to apply to all charts
    Chart.defaults.font.family = 'Poppins';
    Chart.defaults.font.size = 14;

    const ctx = document.getElementById('paymentChart').getContext('2d');
    const paymentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($billTitles) !!},
            datasets: [
                {
                    label: 'Total Paid',
                    data: {!! json_encode($totalPaidData) !!},
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Total Outstanding',
                    data: {!! json_encode($totalOutstandingData) !!},
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            font: {
                family: 'Poppins',
                size: 14
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₦)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Bill Type'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Payment Analysis by Bill'
                }
            }
        }
    });
</script>
@endsection

@endsection
