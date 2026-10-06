{{-- resources/views/admin/scholarship/applications.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sch-primary: #1e3a5f;
    --sch-success: #16a34a;
    --sch-warning: #d97706;
    --sch-danger: #dc2626;
    --sch-info: #2563eb;
    --sch-border: #e2e8f0;
}

.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500;
}
.status-draft { background: #f3f4f6; color: #6b7280; }
.status-submitted { background: #dbeafe; color: #2563eb; }
.status-under_review { background: #fef3c7; color: #d97706; }
.status-approved { background: #dcfce7; color: #16a34a; }
.status-rejected { background: #fee2e2; color: #dc2626; }
.status-revoked { background: #f3f4f6; color: #6b7280; }

.application-card {
    background: white;
    border: 1px solid var(--sch-border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.2s;
}
.application-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold" style="color: var(--sch-primary);">
                        <i class="ri-file-list-line me-2"></i>{{ $pagetitle }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.scholarship.index') }}">Scholarships</a></li>
                            <li class="breadcrumb-item active">Applications</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('admin.scholarship.index') }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Back to Scholarships
                </a>
            </div>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.scholarship.applications') }}">
                All <span class="badge bg-secondary ms-1">{{ array_sum($statusCounts) }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'submitted' ? 'active' : '' }}" href="{{ route('admin.scholarship.applications', ['status' => 'submitted']) }}">
                Submitted <span class="badge bg-info ms-1">{{ $statusCounts['submitted'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'under_review' ? 'active' : '' }}" href="{{ route('admin.scholarship.applications', ['status' => 'under_review']) }}">
                Under Review <span class="badge bg-warning ms-1">{{ $statusCounts['under_review'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'approved' ? 'active' : '' }}" href="{{ route('admin.scholarship.applications', ['status' => 'approved']) }}">
                Approved <span class="badge bg-success ms-1">{{ $statusCounts['approved'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'rejected' ? 'active' : '' }}" href="{{ route('admin.scholarship.applications', ['status' => 'rejected']) }}">
                Rejected <span class="badge bg-danger ms-1">{{ $statusCounts['rejected'] ?? 0 }}</span>
            </a>
        </li>
    </ul>

    {{-- Search Bar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="search-box">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by student name or admission number...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Applications List --}}
    <div class="row" id="applicationsContainer">
        @forelse($applications as $application)
        <div class="col-md-6 col-lg-4">
            <div class="application-card" data-search="{{ strtolower($application->student->firstname ?? '') }} {{ strtolower($application->student->lastname ?? '') }} {{ $application->student->admissionNo ?? '' }}">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="mb-1 fw-bold">{{ $application->scholarship->title ?? 'N/A' }}</h6>
                        <small class="text-muted">{{ $application->scholarship->scholarship_no ?? 'N/A' }}</small>
                    </div>
                    <span class="status-badge status-{{ $application->status }}">
                        {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                    </span>
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <div class="small text-muted">Student</div>
                            <div class="fw-semibold">{{ $application->student->firstname ?? '' }} {{ $application->student->lastname ?? '' }}</div>
                            <div class="small text-muted">Adm: {{ $application->student->admissionNo ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted">Submitted Date</div>
                    <div>{{ $application->submitted_at ? \Carbon\Carbon::parse($application->submitted_at)->format('d M, Y H:i') : 'Not submitted' }}</div>
                </div>

                @if($application->motivation_letter)
                <div class="mb-3">
                                    <button class="btn btn-sm btn-outline-secondary view-letter" data-letter="{{ $application->motivation_letter }}">
                        <i class="ri-file-text-line me-1"></i>View Letter
                    </button>
                </div>
                @endif

                <div class="d-flex gap-2 mt-3">
                    @if($application->status == 'submitted' || $application->status == 'under_review')
                    <button class="btn btn-sm btn-success approve-btn flex-grow-1" data-id="{{ $application->id }}">
                        <i class="ri-check-line me-1"></i>Approve
                    </button>
                    <button class="btn btn-sm btn-danger reject-btn flex-grow-1" data-id="{{ $application->id }}">
                        <i class="ri-close-line me-1"></i>Reject
                    </button>
                    @endif
                    @if($application->status == 'approved')
                        <span class="badge bg-success w-100 py-2">✓ Approved</span>
                    @endif
                    @if($application->status == 'rejected')
                        <span class="badge bg-danger w-100 py-2">✗ Rejected</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="ri-inbox-line ri-2x d-block mb-2"></i>
                No scholarship applications found.
            </div>
        </div>
        @endforelse
    </div>

    @if($applications->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $applications->links() }}
    </div>
    @endif

</div>
</div>
</div>

{{-- Motivation Letter Modal --}}
<div class="modal fade" id="letterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="ri-file-text-line me-2"></i>Motivation Letter</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="letterContent" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ri-close-line me-2"></i>Reject Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Please provide a reason for rejecting this application:</p>
                <textarea id="rejectReason" class="form-control" rows="3" placeholder="Enter rejection reason..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">Reject Application</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;
let currentApplicationId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const cards = document.querySelectorAll('.application-card');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        cards.forEach(card => {
            const searchData = card.dataset.search || '';
            if (searchData.includes(searchTerm) || searchTerm === '') {
                card.closest('.col-md-6, .col-lg-4').style.display = '';
            } else {
                card.closest('.col-md-6, .col-lg-4').style.display = 'none';
            }
        });
    });

    // View letter
    document.querySelectorAll('.view-letter').forEach(btn => {
        btn.addEventListener('click', function() {
            const letter = this.dataset.letter;
            document.getElementById('letterContent').innerHTML = letter.replace(/\n/g, '<br>');
            new bootstrap.Modal(document.getElementById('letterModal')).show();
        });
    });

    // Approve application
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const applicationId = this.dataset.id;

            const result = await Swal.fire({
                title: 'Approve Application?',
                text: 'This will grant the scholarship to the student.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Yes, approve'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`/admin/scholarship/application/${applicationId}/approve`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                    });
                    const data = await response.json();
                    if (data.success) {
                        Swal.fire('Approved!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error!', 'Something went wrong', 'error');
                }
            }
        });
    });

    // Reject application
    let rejectId = null;
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            rejectId = this.dataset.id;
            $('#rejectModal').modal('show');
        });
    });

    document.getElementById('confirmRejectBtn').addEventListener('click', async function() {
        if (!rejectId) return;
        const reason = document.getElementById('rejectReason').value;
        if (!reason) {
            Swal.fire('Error!', 'Please provide a reason for rejection', 'error');
            return;
        }

        try {
            const response = await fetch(`/admin/scholarship/application/${rejectId}/reject`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ rejection_reason: reason })
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('Rejected!', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'Something went wrong', 'error');
        }
        $('#rejectModal').modal('hide');
    });
});
</script>
@endsection
