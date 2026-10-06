@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- View-Only Banner for Students -->
            @if(auth()->user()->hasRole('student'))
                <div class="alert alert-info alert-border-left border-info mb-4" role="alert">
                    <i class="ri-information-fill me-2"></i>
                    <strong>View Only Mode</strong><br>
                    As a student, you can view your profile details but cannot edit them here. Contact admin or staff for any updates.
                </div>
            @endif

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">User Overview - {{ $user->name }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active">Overview</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xxl-12">

                    <!-- Tabs & Navigation -->
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-4">
                        <ul class="nav nav-pills arrow-navtabs nav-secondary gap-2 flex-grow-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails">
                                    <i class="ri-user-line me-1"></i> Profile & Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#activityTimeline">
                                    <i class="ri-history-line me-1"></i> Activity Timeline
                                </a>
                            </li>
                            @if($user->isStaff())
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#employmentInfo">
                                    <i class="ri-briefcase-line me-1"></i> Employment
                                </a>
                            </li>
                            @endif
                            @if($user->isStudent())
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#studentInfo">
                                    <i class="ri-user-star-line me-1"></i> Student Info
                                </a>
                            </li>
                            @endif
                        </ul>
                        <div class="flex-shrink-0 ms-auto">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Users
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- Personal Details -->
                                <div class="tab-pane active" id="personalDetails" role="tabpanel">
                                    <div class="text-center mb-5">
                                        <div class="position-relative d-inline-block">
                                            <div class="avatar-xxl">
                                                @php
                                                    $avatarUrl = asset('images/default-avatar.png');
                                                    $hasAvatar = false;

                                                    if ($user->avatar) {
                                                        if ($user->isStaff()) {
                                                            $avatarUrl = asset('storage/staff_avatars/' . $user->avatar);
                                                            $hasAvatar = true;
                                                        } elseif ($user->isStudent()) {
                                                            $avatarUrl = asset('storage/student_avatars/' . $user->avatar);
                                                            $hasAvatar = true;
                                                        } else {
                                                            $avatarUrl = asset('storage/avatars/' . $user->avatar);
                                                            $hasAvatar = true;
                                                        }
                                                    }
                                                    elseif ($user->isStaff() && $staffPicture?->picture) {
                                                        $avatarUrl = asset('storage/staff_avatars/' . $staffPicture->picture);
                                                        $hasAvatar = true;
                                                    }
                                                    elseif ($user->isStudent() && $studentPicture?->picture) {
                                                        $avatarUrl = asset('storage/student_avatars/' . $studentPicture->picture);
                                                        $hasAvatar = true;
                                                    }

                                                    $initials = strtoupper(
                                                        substr($user->first_name ?? ($user->name ? explode(' ', $user->name)[0] : 'U'), 0, 1) .
                                                        substr($user->last_name ?? (explode(' ', $user->name)[1] ?? ''), 0, 1)
                                                    );
                                                @endphp

                                                @if($hasAvatar)
                                                    <img src="{{ $avatarUrl }}?t={{ time() }}"
                                                         alt="Profile"
                                                         class="rounded-circle img-thumbnail"
                                                         style="width: 150px; height: 150px; object-fit: cover;">
                                                @else
                                                    <div class="avatar-title rounded-circle bg-light text-primary fs-1"
                                                         style="width: 150px; height: 150px; line-height: 150px;">
                                                        {{ $initials }}
                                                    </div>
                                                @endif
                                            </div>
                                            <h4 class="mt-3 mb-1">{{ $user->name }}</h4>
                                            <p class="text-muted mb-0">{{ $user->email }}</p>
                                            <div class="mt-2">
                                                @foreach($user->roles as $role)
                                                    <span class="badge bg-info me-1">{{ $role->name }}</span>
                                                @endforeach
                                                @if($user->isStudent() && $studentData?->admissionNo)
                                                    <span class="badge bg-success">Admission: {{ $studentData->admissionNo }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- View-only personal information -->
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="mb-3 border-bottom pb-2">Personal Information</h5></div>

                                            <div class="col-md-6">
                                                <label>First Name</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->firstname ?? $studentData?->firstname ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Last Name</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->lastname ?? $studentData?->lastname ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Other Names</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->othernames ?? $studentData?->othername ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Phone Number</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->phone ?? $studentData?->phone_number ?? $user->phone_number ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Gender</label>
                                                <input type="text" class="form-control" value="{{ ucfirst($userbio?->gender ?? $studentData?->gender ?? 'N/A') }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Nationality</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->nationality ?? $studentData?->nationality ?? 'N/A' }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Date of Birth</label>
                                                <input type="text" class="form-control" value="{{ $userbio?->dob ?? ($studentData?->dateofbirth ? \Carbon\Carbon::parse($studentData->dateofbirth)->format('d M Y') : 'N/A') }}" readonly>
                                            </div>
                                            <div class="col-12">
                                                <label>Address</label>
                                                <textarea class="form-control" rows="3" readonly>{{ $userbio?->address ?? $studentData?->home_address ?? 'N/A' }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                <!-- Activity Timeline -->
                                <div class="tab-pane" id="activityTimeline" role="tabpanel">
                                    <h5 class="mb-4">Account Activity Timeline</h5>
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->created_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Account Created</h6>
                                                <p class="text-muted mb-0">User account was registered on the platform</p>
                                            </div>
                                        </div>

                                        @if($user->email_verified_at)
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->email_verified_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Email Verified</h6>
                                                <p class="text-muted mb-0">Email address was confirmed</p>
                                            </div>
                                        </div>
                                        @endif

                                        @if($userbio?->updated_at)
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $userbio->updated_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Profile Updated</h6>
                                                <p class="text-muted mb-0">Personal information was last modified</p>
                                            </div>
                                        </div>
                                        @endif

                                        @if($user->roles->count())
                                        <div class="timeline-item">
                                            <div class="timeline-date">{{ $user->updated_at->format('d M Y') }}</div>
                                            <div class="timeline-content">
                                                <h6>Role Assignment</h6>
                                                <p class="text-muted mb-0">Assigned roles: {{ $user->roles->pluck('name')->implode(', ') }}</p>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="timeline-item">
                                            <div class="timeline-date">—</div>
                                            <div class="timeline-content">
                                                <h6>Last Login</h6>
                                                <p class="text-muted mb-0">Login activity tracking coming soon</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employment Info (Staff Only) -->
                                @if($user->isStaff())
                                <div class="tab-pane" id="employmentInfo" role="tabpanel">
                                    @if($staffInfo)
                                    <div class="row g-3">
                                        <div class="col-12"><h5 class="mb-3 border-bottom pb-2">Employment Information</h5></div>
                                        <div class="col-md-6"><label>Employment ID</label><input type="text" class="form-control" value="{{ $staffInfo?->employmentid ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Job Title</label><input type="text" class="form-control" value="{{ $staffInfo?->title ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Work Phone</label><input type="text" class="form-control" value="{{ $staffInfo?->phonenumber ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Marital Status</label><input type="text" class="form-control" value="{{ ucfirst($staffInfo?->maritalstatus ?? 'N/A') }}" readonly></div>
                                        <div class="col-md-6"><label>Number of Children</label><input type="text" class="form-control" value="{{ $staffInfo?->numberofchildren ?? '0' }}" readonly></div>
                                        <div class="col-md-6"><label>Spouse Phone</label><input type="text" class="form-control" value="{{ $staffInfo?->spousenumber ?? 'N/A' }}" readonly></div>
                                        <div class="col-12"><label>Residential Address</label><textarea class="form-control" rows="3" readonly>{{ $staffInfo?->address ?? 'N/A' }}</textarea></div>
                                        <div class="col-md-6"><label>State</label><input type="text" class="form-control" value="{{ $staffInfo?->state ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Local Government</label><input type="text" class="form-control" value="{{ $staffInfo?->local ?? 'N/A' }}" readonly></div>
                                        <div class="col-12"><label>Religion</label><input type="text" class="form-control" value="{{ $staffInfo?->religion ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Date of Birth</label><input type="text" class="form-control" value="{{ $staffInfo?->dateofbirth ? \Carbon\Carbon::parse($staffInfo->dateofbirth)->format('d M Y') : 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Email</label><input type="text" class="form-control" value="{{ $staffInfo?->email ?? $user->email }}" readonly></div>
                                    </div>
                                    @else
                                    <div class="text-center py-5 text-muted">
                                        <i class="ri-information-line fs-1"></i>
                                        <h5 class="mt-3">No Employment Information</h5>
                                        <p>Employment information has not been added for this staff member.</p>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <!-- Student Info -->
                                @if($user->isStudent())
                                <div class="tab-pane" id="studentInfo" role="tabpanel">
                                    @if($studentData)
                                    <div class="row g-3">
                                        <div class="col-12"><h5 class="mb-3 border-bottom pb-2">Student Information</h5></div>
                                        <div class="col-md-6"><label>Admission Number</label><input type="text" class="form-control" value="{{ $studentData?->admissionNo ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Admission Date</label><input type="text" class="form-control" value="{{ $studentData?->admission_date ? \Carbon\Carbon::parse($studentData->admission_date)->format('d M Y') : 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Phone Number</label><input type="text" class="form-control" value="{{ $studentData?->phone_number ?? $user->phone_number ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Email</label><input type="text" class="form-control" value="{{ $user->email }}" readonly></div>
                                        <div class="col-md-6"><label>Religion</label><input type="text" class="form-control" value="{{ $studentData?->religion ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Nationality</label><input type="text" class="form-control" value="{{ $studentData?->nationality ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>State</label><input type="text" class="form-control" value="{{ $studentData?->state ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Local Government</label><input type="text" class="form-control" value="{{ $studentData?->local ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Date of Birth</label><input type="text" class="form-control" value="{{ $studentData?->dateofbirth ? \Carbon\Carbon::parse($studentData->dateofbirth)->format('d M Y') : 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Age</label><input type="text" class="form-control" value="{{ $studentData?->age ?? 'N/A' }}" readonly></div>
                                        <div class="col-12"><label>Home Address</label><textarea class="form-control" rows="3" readonly>{{ $studentData?->home_address ?? 'N/A' }}</textarea></div>

                                        @if($parentData)
                                        <div class="col-12 mt-4"><h5 class="mb-3 border-bottom pb-2">Parent/Guardian Information</h5></div>
                                        <div class="col-md-6"><label>Father's Name</label><input type="text" class="form-control" value="{{ $parentData?->father ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Mother's Name</label><input type="text" class="form-control" value="{{ $parentData?->mother ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Father's Phone</label><input type="text" class="form-control" value="{{ $parentData?->father_phone ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Mother's Phone</label><input type="text" class="form-control" value="{{ $parentData?->mother_phone ?? 'N/A' }}" readonly></div>
                                        <div class="col-md-6"><label>Father's Occupation</label><input type="text" class="form-control" value="{{ $parentData?->father_occupation ?? 'N/A' }}" readonly></div>
                                        <div class="col-12"><label>Home Address</label><textarea class="form-control" rows="3" readonly>{{ $parentData?->parent_address ?? 'N/A' }}</textarea></div>
                                        @else
                                        <div class="col-12 mt-4">
                                            <div class="alert alert-info">
                                                No parent/guardian information recorded.
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    @else
                                    <div class="text-center py-5 text-muted">
                                        <i class="ri-information-line fs-1"></i>
                                        <h5 class="mt-3">No Student Information</h5>
                                        <p>Student information has not been added for this user.</p>
                                    </div>
                                    @endif
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 18px;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #e9ecef;
}
.timeline-item {
    position: relative;
    margin-bottom: 30px;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 8px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #4e73df;
    border: 3px solid #fff;
    box-shadow: 0 0 0 4px #e3e8ff;
}
.timeline-date {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 5px;
}
.timeline-content h6 {
    margin: 0 0 5px 0;
    color: #495057;
}
.avatar-xxl {
    width: 150px;
    height: 150px;
}
.avatar-xxl img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.border-bottom {
    border-bottom: 2px solid var(--bs-border-color) !important;
}
</style>
@endsection
