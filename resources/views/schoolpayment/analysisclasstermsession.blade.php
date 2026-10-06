@extends('layouts.master')

@section('content')
<!-- Main content container -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Display validation errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Error!</strong> There were some problems with your input.<br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Display success/status messages -->
            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?: session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Analysis Book Section -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    Analysis Book for Class {{ $schoolclass[0]->schoolclass }} {{ $schoolclass[0]->schoolarm }}, {{ $schoolterm[0]->schoolterm }} {{ $schoolsession[0]->schoolsession }} Academic Session
                                </h5>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by student name or admission number..." style="min-width: 200px;" {{ $student->isEmpty() ? 'disabled' : '' }}>
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <a href="{{ route('analysis.index') }}" class="btn btn-primary">
                                    <i class="ri-arrow-left-line"></i> Back to Analysis
                                </a>
                                <div>
                                    @if ($student->isNotEmpty())
                                        <a href="{{ route('analysis.exportPDF', ['class_id' => $schoolclass[0]->id ?? 0, 'termid_id' => $schoolterm[0]->id ?? 0, 'session_id' => $schoolsession[0]->id ?? 0, 'action' => 'download']) }}" class="btn btn-primary me-2">
                                            <i class="ri-download-line me-1"></i> Download PDF
                                        </a>
                                    @else
                                        <button class="btn btn-primary me-2" disabled title="No data available">
                                            <i class="ri-download-line me-1"></i> Download PDF
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- No Data Alert -->
                            <div class="alert alert-info text-center" id="noDataAlert" style="display: {{ $student->isEmpty() ? 'block' : 'none' }};">
                                <i class="ri-information-line me-2"></i>
                                No student records available for the selected class, term, and session.
                            </div>

                            <!-- Analysis Table -->
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0" id="analysisTable">
                                    <thead class="table-active">
                                        <tr>
                                            <th style="width: 50px;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                    <label class="form-check-label" for="checkAll"></label>
                                                </div>
                                            </th>
                                            <th style="width: 50px;" class="sort cursor-pointer" data-sort="sn">SN</th>
                                            <th class="sort cursor-pointer" data-sort="student-name">Student Name</th>
                                            <th class="sort cursor-pointer" data-sort="admission-no">Admission Number</th>
                                            @foreach ($student_bill_info as $bill)
                                                <th class="sort cursor-pointer" data-sort="bill-{{ $bill->schoolbillid }}">{{ $bill->title }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody id="analysisTableBody" class="list form-check-all">
                                        @php $i = 0; @endphp
                                        @forelse ($student as $stu)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input student-checkbox" type="checkbox" name="chk_child" data-id="{{ $stu->stid }}">
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </td>
                                                <td class="sn">{{ ++$i }}</td>
                                                <td class="student-name" data-student-name="{{ $stu->firstname }} {{ $stu->lastname }}">
                                                    <div class="d-flex align-items-center">
                                                        <!-- Student Avatar -->
                                                        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                                            <?php
                                                                $image = $stu->picture ?? 'unnamed.png';
                                                            ?>
                                                            <img src="{{ Storage::url('images/studentavatar/' . $image) }}" alt="{{ $stu->firstname }} {{ $stu->lastname }}" class="w-100" />
                                                        </div>
                                                        <!-- Student Name -->
                                                        <div class="d-flex flex-column">
                                                            <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $stu->firstname }} {{ $stu->lastname }}</a>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="admission-no" data-admission-no="{{ $stu->admissionno }}">{{ $stu->admissionno }}</td>
                                                @foreach ($student_bill_info as $bill)
                                                    @php
                                                        $paymentFound = false;
                                                        $amountPaid = 0;
                                                        $balance = $bill->amount ?? 0;
                                                    @endphp
                                                    @foreach ($studentpaymentbillbook as $paymentBook)
                                                        @if (
                                                            (int)$paymentBook->school_bill_id === (int)$bill->schoolbillid &&
                                                            (int)$paymentBook->student_id === (int)$stu->stid
                                                        )
                                                            @php
                                                                $paymentFound = true;
                                                                $amountPaid = (int)$paymentBook->amount_paid;
                                                                $balance = $paymentBook->amount_owed;
                                                            @endphp
                                                            @break
                                                        @endif
                                                    @endforeach
                                                    <td>
                                                        @if ($paymentFound)
                                                            <span style="color: green">
                                                                ₦ {{ number_format($amountPaid) }}
                                                                <br>
                                                                <small style="color: rgb(77, 22, 165)">Outstanding: ₦ {{ number_format($balance) }}</small>
                                                            </span>
                                                        @else
                                                            <span style="color: rgb(235, 61, 27)">Not Paid</span>
                                                        @endif
                                                    </td>
                                                    @php
                                                        if (!isset($totalBill[$bill->schoolbillid])) {
                                                            $totalBill[$bill->schoolbillid] = 0;
                                                            $totalBillBalance[$bill->schoolbillid] = 0;
                                                        }
                                                        $totalBill[$bill->schoolbillid] += $amountPaid;
                                                        $totalBillBalance[$bill->schoolbillid] += $balance;
                                                    @endphp
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr id="noDataRow">
                                                <td colspan="{{ 4 + $student_bill_info->count() }}" class="text-center">No student records available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="4">Totals</td>
                                            @foreach ($student_bill_info as $bill)
                                                <td>
                                                    ₦ {{ number_format($totalBill[$bill->schoolbillid] ?? 0) }}
                                                    <br>
                                                    <small style="color: rgb(77, 22, 165)">Outstanding: ₦ {{ number_format($totalBillBalance[$bill->schoolbillid] ?? 0) }}</small>
                                                </td>
                                            @endforeach
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom CSS -->
            <style>
                .card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
                }
                .table-active th {
                    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
                    color: white;
                }
                .sort:hover {
                    background-color: #e5e7eb;
                    cursor: pointer;
                }
                .table-responsive {
                    border-radius: 8px;
                    overflow: hidden;
                }
                @media (max-width: 767px) {
                    .card-body {
                        padding: 1rem !important;
                    }
                }
            </style>

            <!-- JavaScript -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM fully loaded, initializing scripts');

                    // Search Functionality
                    const searchInput = document.getElementById('searchInput');
                    const clearSearch = document.getElementById('clearSearch');
                    const tableBody = document.getElementById('analysisTableBody');
                    const noDataAlert = document.getElementById('noDataAlert');

                    if (searchInput && tableBody && noDataAlert) {
                        searchInput.addEventListener('input', function() {
                            const query = this.value.trim().toLowerCase();
                            const rows = tableBody.querySelectorAll('tr:not(#noDataRow)');
                            let hasVisibleRows = false;

                            rows.forEach(row => {
                                const studentName = (row.querySelector('.student-name')?.getAttribute('data-student-name') || '').toLowerCase();
                                const admissionNo = (row.querySelector('.admission-no')?.getAttribute('data-admission-no') || '').toLowerCase();
                                const isMatch = studentName.includes(query) || admissionNo.includes(query);
                                row.style.display = isMatch ? '' : 'none';
                                if (isMatch) hasVisibleRows = true;
                            });

                            noDataAlert.style.display = hasVisibleRows ? 'none' : 'block';
                        });

                        clearSearch.addEventListener('click', function() {
                            searchInput.value = '';
                            const rows = tableBody.querySelectorAll('tr:not(#noDataRow)');
                            rows.forEach(row => row.style.display = '');
                            noDataAlert.style.display = rows.length > 0 ? 'none' : 'block';
                        });
                    }

                    // Checkbox Select All
                    const checkAll = document.getElementById('checkAll');
                    const checkboxes = document.querySelectorAll('.student-checkbox');

                    if (checkAll) {
                        checkAll.addEventListener('change', function() {
                            checkboxes.forEach(checkbox => {
                                checkbox.checked = this.checked;
                            });
                        });

                        checkboxes.forEach(checkbox => {
                            checkbox.addEventListener('change', function() {
                                checkAll.checked = Array.from(checkboxes).every(c => c.checked);
                            });
                        });
                    }
                });
            </script>
        </div>
    </div>
</div>
@endsection