{{-- resources/views/finance/payroll/runs.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('payroll.periods') }}" class="btn btn-light">
                <i class="ri-arrow-left-line me-1"></i>Back to Periods
            </a>
            <h4 class="mt-3">{{ $pagetitle }}</h4>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card text-center p-3"><h4>{{ $summary['total_staff'] }}</h4><small>Staff</small></div></div>
        <div class="col-md-3"><div class="card text-center p-3"><h4 class="text-primary">₦{{ number_format($summary['total_gross'], 2) }}</h4><small>Gross Pay</small></div></div>
        <div class="col-md-3"><div class="card text-center p-3"><h4 class="text-danger">₦{{ number_format($summary['total_deductions'], 2) }}</h4><small>Deductions</small></div></div>
        <div class="col-md-3"><div class="card text-center p-3"><h4 class="text-success">₦{{ number_format($summary['total_net'], 2) }}</h4><small>Net Pay</small></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Staff Payroll Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover w-100" id="runsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Staff Name</th>
                            <th>Staff ID</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#runsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("payroll.runs", $period->id) }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'staff_name', name: 'staff_name' },
            { data: 'staff_id', name: 'staff_id' },
            { data: 'gross_pay', name: 'gross_pay' },
            { data: 'deductions', name: 'deductions' },
            { data: 'net_pay', name: 'net_pay' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
});
</script>
@endsection
