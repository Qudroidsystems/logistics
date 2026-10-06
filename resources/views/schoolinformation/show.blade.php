@extends('layouts.master')

@section('content')
<style>
    :root {
        --school-primary: #1e3a5f;
        --school-accent: #2563eb;
        --school-border: #e2e8f0;
        --school-radius: 12px;
    }

    .school-hero {
        background: linear-gradient(135deg, var(--school-primary) 0%, #2563eb 60%, #4f46e5 100%);
        border-radius: var(--school-radius);
        padding: 28px 32px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .school-hero h1 {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        margin: 0;
    }
    .school-hero p {
        font-size: 13px;
        color: rgba(255,255,255,.75);
        margin: 5px 0 0;
    }
    .detail-card {
        background: #fff;
        border: 1px solid var(--school-border);
        border-radius: var(--school-radius);
        padding: 20px;
        height: 100%;
    }
    .detail-label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 5px;
        font-weight: 600;
    }
    .detail-value {
        font-size: 14px;
        color: #1f2937;
        font-weight: 500;
    }
    .phone-badge {
        display: inline-block;
        background: #f3f4f6;
        padding: 4px 10px;
        border-radius: 20px;
        margin: 2px;
        font-size: 12px;
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="school-hero">
                <h1><i class="ri-school-line me-2"></i>{{ $school->school_name }}</h1>
                <p>Complete school profile and asset management</p>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <i class="ri-information-line me-2"></i>School Details
                            </h5>
                            <div>
                                @can('Update schoolinformation')
                                    <a href="{{ route('admin.school-info.index') }}?edit={{ $school->id }}" class="btn btn-soft-secondary btn-sm">
                                        <i class="ri-pencil-line"></i> Edit
                                    </a>
                                @endcan
                                <a href="{{ route('admin.school-info.index') }}" class="btn btn-soft-primary btn-sm">
                                    <i class="ri-arrow-left-line"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Logos and Stamp -->
                                <div class="col-md-12 mb-4">
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <h6 class="fw-semibold mb-3">School Logo</h6>
                                            @if($school->getLogoUrlAttribute())
                                                <img src="{{ $school->getLogoUrlAttribute() }}" alt="School Logo" class="img-fluid rounded" style="max-height: 150px;">
                                            @else
                                                <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                                    <span class="text-muted">No logo uploaded</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <h6 class="fw-semibold mb-3">App / Website Logo</h6>
                                            @if($school->getAppLogoUrlAttribute())
                                                <img src="{{ $school->getAppLogoUrlAttribute() }}" alt="App Logo" class="img-fluid rounded" style="max-height: 150px;">
                                            @else
                                                <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                                    <span class="text-muted">No app logo uploaded</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <h6 class="fw-semibold mb-3">School Stamp</h6>
                                            @if($school->getStampUrlAttribute())
                                                <img src="{{ $school->getStampUrlAttribute() }}" alt="School Stamp" class="img-fluid rounded" style="max-height: 150px;">
                                            @else
                                                <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                                    <span class="text-muted">No stamp uploaded</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <h6 class="fw-semibold mb-3"><i class="ri-building-line me-2"></i>Basic Information</h6>
                                        <div class="mb-3">
                                            <div class="detail-label">School Name</div>
                                            <div class="detail-value">{{ $school->school_name }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Email Address</div>
                                            <div class="detail-value">{{ $school->school_email }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Phone Numbers</div>
                                            <div class="detail-value">
                                                @php
                                                    $phones = is_array($school->school_phones) ? $school->school_phones : json_decode($school->school_phones ?? '[]', true);
                                                @endphp
                                                @if(!empty($phones))
                                                    @foreach($phones as $phone)
                                                        <span class="phone-badge"><i class="ri-phone-line me-1"></i>{{ $phone }}</span>
                                                    @endforeach
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Address</div>
                                            <div class="detail-value">{{ $school->school_address }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Website</div>
                                            <div class="detail-value">
                                                @if($school->school_website)
                                                    <a href="{{ $school->school_website }}" target="_blank">{{ $school->school_website }}</a>
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Motto / Slogan</div>
                                            <div class="detail-value">{{ $school->school_motto ?: '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-card">
                                        <h6 class="fw-semibold mb-3"><i class="ri-calendar-line me-2"></i>Operational Information</h6>
                                        <div class="mb-3">
                                            <div class="detail-label">Status</div>
                                            <div class="detail-value">
                                                <span class="badge bg-{{ $school->is_active ? 'success' : 'secondary' }}">
                                                    {{ $school->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Times School Opened</div>
                                            <div class="detail-value">{{ $school->no_of_times_school_opened }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Date School Opened</div>
                                            <div class="detail-value">{{ $school->date_school_opened ? $school->date_school_opened->format('d F Y') : '-' }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Date School Closed</div>
                                            <div class="detail-value">{{ $school->date_school_closed ? $school->date_school_closed->format('d F Y') : '-' }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Next Term Begins</div>
                                            <div class="detail-value">{{ $school->date_next_term_begins ? $school->date_next_term_begins->format('d F Y') : '-' }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Date Created</div>
                                            <div class="detail-value">{{ $school->created_at->format('d F Y h:i A') }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="detail-label">Last Updated</div>
                                            <div class="detail-value">{{ $school->updated_at->format('d F Y h:i A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if we need to open edit modal from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');

    if (editId) {
        // Store in session and redirect to index (which will handle opening edit modal)
        sessionStorage.setItem('editSchoolId', editId);
        window.location.href = "{{ route('admin.school-info.index') }}";
    }
});
</script>
@endsection
