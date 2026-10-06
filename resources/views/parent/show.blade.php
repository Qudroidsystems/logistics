@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Parent / Guardian Details</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('parent.index') }}">Parent Management</a></li>
                                <li class="breadcrumb-item active">View</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Parent / Guardian Record</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Student Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th width="40%">Student Name</th>
                                                    <td><strong>{{ $parent->firstname ?? '—' }} {{ $parent->lastname ?? '—' }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <th>Admission No</th>
                                                    <td><span class="badge bg-primary">{{ $parent->admissionNo ?? 'N/A' }}</span></td>
                                                </tr>
                                                <tr>
                                                    <th>Gender</th>
                                                    <td>{{ $parent->gender ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Class</th>
                                                    <td>{{ $parent->schoolclass ?? '—' }} {{ $parent->arm ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td>
                                                        <span class="badge {{ $parent->student_status == 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ $parent->student_status ?? '—' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="bi bi-envelope me-2"></i>Contact Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th width="40%">Parent Email</th>
                                                    <td>{{ $parent->parent_email ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Home Address</th>
                                                    <td>{{ $parent->parent_address ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Office Address</th>
                                                    <td>{{ $parent->office_address ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Term</th>
                                                    <td>{{ $parent->term_name ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Session</th>
                                                    <td>{{ $parent->session_name ?? '—' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Father's Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th width="40%">Full Name</th>
                                                    <td><strong>{{ $parent->father ?? '—' }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <th>Title</th>
                                                    <td>{{ $parent->father_title ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Phone</th>
                                                    <td>
                                                        {{ $parent->father_phone ?? '—' }}
                                                        @if($parent->father_phone)
                                                        <a href="tel:{{ $parent->father_phone }}" class="ms-2 text-primary">
                                                            <i class="bi bi-telephone"></i>
                                                        </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Occupation</th>
                                                    <td>{{ $parent->father_occupation ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>City</th>
                                                    <td>{{ $parent->father_city ?? '—' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Mother's Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th width="40%">Full Name</th>
                                                    <td><strong>{{ $parent->mother ?? '—' }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <th>Title</th>
                                                    <td>{{ $parent->mother_title ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Phone</th>
                                                    <td>
                                                        {{ $parent->mother_phone ?? '—' }}
                                                        @if($parent->mother_phone)
                                                        <a href="tel:{{ $parent->mother_phone }}" class="ms-2 text-primary">
                                                            <i class="bi bi-telephone"></i>
                                                        </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex gap-2">
                                @can('Update parent')
                                <a href="{{ route('parent.edit', $parent->id) }}" class="btn btn-warning">
                                    <i class="bi bi-pencil me-2"></i>Edit
                                </a>
                                @endcan
                                <a href="{{ route('parent.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection