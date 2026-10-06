@extends('layouts.master')
@section('content')
<?php
use Spatie\Permission\Models\Role;
?>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Student Profile</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('student.index') }}">Students</a></li>
                                <li class="breadcrumb-item active">Profile</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <div class="row">
                <div class="col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-center border-bottom border-dashed pb-4">
                                @if($student->picture)
                                    <img src="{{ asset('storage/' . $student->picture) }}" alt="Student Picture" class="avatar-lg rounded-circle p-1 img-thumbnail">
                                @else
                                    <div class="avatar-lg rounded-circle p-1 img-thumbnail bg-light text-primary d-flex align-items-center justify-content-center">
                                        {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="mt-3">
                                    <h5>{{ $student->first_name }} {{ $student->last_name }}</h5>
                                    <p class="text-muted">Student ID: {{ $student->student_id }}</p>
                                </div>
                                <div class="d-flex gap-2 mt-4">
                                    <a href="{{ route('student.edit', $student->id) }}" class="btn btn-primary w-50"><i class="ri-edit-box-line align-baseline me-1"></i> Edit Profile</a>
                                    <a href="{{ route('student.index') }}" class="btn btn-subtle-secondary w-50"><i class="bi bi-arrow-left align-baseline me-1"></i> Back</a>
                                </div>
                            </div>

                            <div class="border-bottom border-dashed py-4">
                                <h5 class="card-title mb-3">Personal Information</h5>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm align-middle mb-0">
                                        <tbody>
                                            <tr>
                                                <th class="ps-0" scope="row">Full Name</th>
                                                <td class="text-muted text-end">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</td>
                                            </tr>
                                            <tr>
                                                <th class="ps-0" scope="row">Gender</th>
                                                <td class="text-muted text-end">{{ $student->gender }}</td>
                                            </tr>
                                            <tr>
                                                <th class="ps-0" scope="row">Date of Birth</th>
                                                <td class="text-muted text-end">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M, Y') : 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="ps-0" scope="row">Blood Group</th>
                                                <td class="text-muted text-end">{{ $student->blood_group ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="ps-0" scope="row">Admission Date</th>
                                                <td class="text-muted text-end">{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d M, Y') : 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="ps-0" scope="row">Address</th>
                                                <td class="text-muted text-end">{{ $student->address ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="border-bottom border-dashed py-4">
                                <h5 class="card-title mb-3">Parent/Guardian Information</h5>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm align-middle mb-0">
                                        <tbody>
                                            @if($student->parent_firstName || $student->parent_lastName)
                                                <tr>
                                                    <th class="ps-0" scope="row">Name</th>
                                                    <td class="text-muted text-end">{{ $student->parent_firstName }} {{ $student->parent_lastName }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0" scope="row">Phone</th>
                                                    <td class="text-muted text-end">{{ $student->phoneNumber ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0" scope="row">Email</th>
                                                    <td class="text-muted text-end">{{ $student->parent_email ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0" scope="row">Address</th>
                                                    <td class="text-muted text-end">{{ $student->parent_address ?? 'N/A' }}</td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <td colspan="2" class="text-muted text-end">No parent information available</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="py-4">
                                <h5 class="card-title mb-3">Class & House Information</h5>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm align-middle mb-0">
                                        <tbody>
                                            @if($student->schoolclass)
                                                <tr>
                                                    <th class="ps-0" scope="row">Class</th>
                                                    <td class="text-muted text-end">{{ $student->schoolclass }} {{ $student->arm }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0" scope="row">Session</th>
                                                    <td class="text-muted text-end">{{ $student->session }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="ps-0" scope="row">Term</th>
                                                    <td class="text-muted text-end">{{ $student->term }}</td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <td colspan="2" class="text-muted text-end">No class information available</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th class="ps-0" scope="row">House</th>
                                                <td class="text-muted text-end">{{ $student->schoolhouse ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div><!--end card-->
                </div><!--end col-->

                <div class="col-xl-9">
                    <div class="row align-items-center g-3 mb-3">
                        <div class="col-md">
                            <ul class="nav nav-pills arrow-navtabs nav-secondary gap-2 flex-grow-1" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#overview-tab" role="tab">
                                        Overview
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#billing" role="tab">
                                        Billing Information
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#personality" role="tab">
                                        Personality Profile
                                    </a>
                                </li>
                            </ul>
                        </div><!--end col-->
                        <div class="col-md-auto">
                            <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary"><i class="ri-edit-box-line align-bottom"></i> Edit Profile</a>
                        </div><!--end col-->
                    </div><!--end row-->

                    <div class="tab-content">
                        <div class="tab-pane active" id="overview-tab" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-4">
                                        <h5 class="card-title mb-3">Student Overview</h5>
                                        <p class="text-muted mb-2">
                                            <strong>Student Name:</strong> {{ $student->first_name }} {{ $student->last_name }} {{ $student->middle_name }}<br>
                                            <strong>Student ID:</strong> {{ $student->student_id }}<br>
                                            <strong>Status:</strong> {{ $student->status ?? 'Active' }}
                                        </p>
                                        <p class="text-muted mb-0">
                                            <strong>Admission Details:</strong> Admitted on {{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d M, Y') : 'N/A' }} to {{ $student->schoolclass ?? 'N/A' }}.
                                            @if($student->schoolhouse)
                                                Assigned to {{ $student->schoolhouse }} house.
                                            @endif
                                        </p>
                                    </div>
                                    <div class="mb-4">
                                        <h5 class="card-title mb-3">Academic Information</h5>
                                        <div class="table-responsive">
                                            <table class="table table-borderless table-sm align-middle mb-0">
                                                <tbody>
                                                    <tr>
                                                        <th class="ps-0" scope="row">Current Class</th>
                                                        <td class="text-muted">{{ $student->schoolclass ?? 'N/A' }} {{ $student->arm ?? '' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0" scope="row">Session</th>
                                                        <td class="text-muted">{{ $student->session ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0" scope="row">Term</th>
                                                        <td class="text-muted">{{ $student->term ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0" scope="row">House</th>
                                                        <td class="text-muted">{{ $student->schoolhouse ?? 'N/A' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!--end tab-pane-->

                        <div class="tab-pane fade" id="billing" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Billing Information</h5>
                                </div>
                                <div class="card-body">
                                    @if($billPayments->isEmpty() && $billPaymentBooks->isEmpty())
                                        <p class="text-muted">No billing information available.</p>
                                    @else
                                        <h6 class="fs-md mb-3">Bill Payments</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Bill Name</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                        <th>Due Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($billPayments as $bill)
                                                        <tr>
                                                            <td>{{ $bill->schoolBill->bill_name ?? 'N/A' }}</td>
                                                            <td>{{ number_format($bill->amount, 2) }}</td>
                                                            <td>{{ $bill->status }}</td>
                                                            <td>{{ $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('d M, Y') : 'N/A' }}</td>
                                                            <td>
                                                                <a href="{{ route('bill-payments.show', $bill->id) }}" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> View</a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <h6 class="fs-md mb-3 mt-4">Payment Books</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Book Name</th>
                                                        <th>Amount</th>
                                                        <th>Issue Date</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($billPaymentBooks as $book)
                                                        <tr>
                                                            <td>{{ $book->book_name ?? 'N/A' }}</td>
                                                            <td>{{ number_format($book->amount, 2) }}</td>
                                                            <td>{{ $book->issue_date ? \Carbon\Carbon::parse($book->issue_date)->format('d M, Y') : 'N/A' }}</td>
                                                            <td>{{ $book->status ?? 'N/A' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div><!--end tab-pane-->

                        <div class="tab-pane fade" id="personality" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Personality Profile</h5>
                                </div>
                                <div class="card-body">
                                    @if($student->traits || $student->strengths || $student->weaknesses || $student->comments)
                                        <div class="mb-4">
                                            <h6 class="fs-md">Traits</h6>
                                            <p class="text-muted">{{ $student->traits ?? 'N/A' }}</p>
                                        </div>
                                        <div class="mb-4">
                                            <h6 class="fs-md">Strengths</h6>
                                            <p class="text-muted">{{ $student->strengths ?? 'N/A' }}</p>
                                        </div>
                                        <div class="mb-4">
                                            <h6 class="fs-md">Weaknesses</h6>
                                            <p class="text-muted">{{ $student->weaknesses ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <h6 class="fs-md">Comments</h6>
                                            <p class="text-muted">{{ $student->comments ?? 'N/A' }}</p>
                                        </div>
                                    @else
                                        <p class="text-muted">No personality profile available.</p>
                                    @endif
                                </div>
                            </div>
                        </div><!--end tab-pane-->
                    </div>
                </div><!--end col-->
            </div><!--end row-->
        </div><!-- container-fluid -->
    </div><!-- End Page-content -->
@endsection