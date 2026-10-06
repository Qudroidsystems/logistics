{{-- resources/views/admin/scholarship/show.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sch-primary: #1e3a5f;
    --sch-accent: #2563eb;
    --sch-success: #16a34a;
    --sch-warning: #d97706;
    --sch-danger: #dc2626;
    --sch-border: #e2e8f0;
    --sch-radius: 12px;
}

.detail-card {
    background: white;
    border: 1px solid var(--sch-border);
    border-radius: var(--sch-radius);
    padding: 20px;
    margin-bottom: 24px;
}
.detail-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}
.detail-value {
    font-size: 16px;
    font-weight: 600;
    color: var(--sch-primary);
}
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 500;
}
.status-active { background: #dcfce7; color: #16a34a; }
.status-draft { background: #fef3c7; color: #d97706; }
.status-expired { background: #fee2e2; color: #dc2626; }
.status-suspended { background: #f3f4f6; color: #6b7280; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold" style="color: var(--sch-primary);">
                        <i class="ri-graduation-cap-line me-2"></i>{{ $pagetitle }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.scholarship.index') }}">Scholarships</a></li>
                            <li class="breadcrumb-item active">Details</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.scholarship.index') }}" class="btn btn-light me-2">
                        <i class="ri-arrow-left-line me-1"></i>Back to List
                    </a>
                    <a href="{{ route('admin.scholarship.edit', $scholarship->id) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i>Edit Scholarship
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Scholarship Information --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--sch-primary);">
                    <i class="ri-information-line me-2"></i>Scholarship Information
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-label">Scholarship No</div>
                        <div class="detail-value"><code>{{ $scholarship->scholarship_no }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-{{ $scholarship->status }}">
                                <i class="ri-{{ $scholarship->status == 'active' ? 'check-circle-line' : ($scholarship->status == 'draft' ? 'edit-line' : 'close-circle-line') }}"></i>
                                {{ ucfirst($scholarship->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="detail-label">Title</div>
                        <div class="detail-value">{{ $scholarship->title }}</div>
                    </div>
                    <div class="col-md-12">
                        <div class="detail-label">Description</div>
                        <div class="detail-value">{{ $scholarship->description ?? 'No description provided.' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Scholarship Type</div>
                        <div class="detail-value">{{ $scholarship->type->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Application Required</div>
                        <div class="detail-value">{{ $scholarship->requires_application ? 'Yes - Manual Application Required' : 'No - Auto-assigned to eligible students' }}</div>
                    </div>
                </div>
            </div>

            {{-- Value Information --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--sch-primary);">
                    <i class="ri-money-dollar-circle-line me-2"></i>Value Information
                </h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="detail-label">Value Type</div>
                        <div class="detail-value">{{ ucfirst(str_replace('_', ' ', $scholarship->value_type)) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-label">Value</div>
                        <div class="detail-value">
                            @if($scholarship->value_type == 'percentage')
                                {{ $scholarship->value }}%
                            @else
                                ₦{{ number_format($scholarship->value, 2) }}
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-label">Maximum Amount (Cap)</div>
                        <div class="detail-value">{{ $scholarship->cap_amount ? '₦' . number_format($scholarship->cap_amount, 2) : 'No cap' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Budget Amount</div>
                        <div class="detail-value">{{ $scholarship->budget_amount ? '₦' . number_format($scholarship->budget_amount, 2) : 'Unlimited' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Utilized Amount</div>
                        <div class="detail-value">₦{{ number_format($scholarship->utilized_amount, 2) }}</div>
                    </div>
                    @if($scholarship->budget_amount > 0)
                    <div class="col-md-12">
                        <div class="progress" style="height: 8px;">
                            @php $percent = ($scholarship->utilized_amount / $scholarship->budget_amount) * 100; @endphp
                            <div class="progress-bar bg-warning" style="width: {{ $percent }}%"></div>
                        </div>
                        <small class="text-muted">{{ round($percent, 1) }}% of budget utilized</small>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Effective Dates --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--sch-primary);">
                    <i class="ri-calendar-line me-2"></i>Effective Dates
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-label">Effective From</div>
                        <div class="detail-value">{{ $scholarship->effective_from->format('d F, Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Effective To</div>
                        <div class="detail-value">{{ $scholarship->effective_to ? $scholarship->effective_to->format('d F, Y') : 'Ongoing' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Statistics Card --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--sch-primary);">
                    <i class="ri-bar-chart-line me-2"></i>Statistics
                </h5>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="fs-3 fw-bold text-primary">{{ $assignments->total() }}</div>
                            <div class="small text-muted">Total Assignments</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="fs-3 fw-bold text-success">{{ $activeCount }}</div>
                            <div class="small text-muted">Active</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top">
                    <div class="detail-label">Total Value Awarded</div>
                    <div class="fs-4 fw-bold text-success">₦{{ number_format($totalAmount, 2) }}</div>
                </div>
                @if($scholarship->max_recipients)
                <div class="mt-2">
                    <div class="detail-label">Max Recipients</div>
                    <div class="detail-value">{{ $scholarship->max_recipients }} students</div>
                </div>
                @endif
                @if($scholarship->renewal_frequency)
                <div class="mt-2">
                    <div class="detail-label">Renewal Frequency</div>
                    <div class="detail-value">Every {{ $scholarship->renewal_frequency }} months</div>
                </div>
                @endif
            </div>

            {{-- Meta Information --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--sch-primary);">
                    <i class="ri-information-line me-2"></i>Meta Information
                </h5>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="detail-label">Created By</div>
                        <div class="detail-value">{{ $scholarship->createdBy->name ?? 'Unknown' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Created At</div>
                        <div class="detail-value">{{ $scholarship->created_at->format('d F, Y H:i') }}</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value">{{ $scholarship->updated_at->format('d F, Y H:i') }}</div>
                    </div>
                    @if($scholarship->approved_by)
                    <div class="col-12">
                        <div class="detail-label">Approved By</div>
                        <div class="detail-value">{{ $scholarship->approvedBy->name ?? 'Unknown' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Approved At</div>
                        <div class="detail-value">{{ $scholarship->approved_at ? $scholarship->approved_at->format('d F, Y H:i') : 'N/A' }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Assignments List --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="ri-user-star-line me-2"></i>Scholarship Assignments</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Admission No</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Effective Period</th>
                            <th>Assigned Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $index => $assignment)
                            @php
                                $student = $assignment->student;
                            @endphp
                            <tr>
                                <td>{{ $assignments->firstItem() + $index }}</td>
                                <td>{{ $student->firstname ?? '' }} {{ $student->lastname ?? '' }}</td>
                                <td>{{ $student->admissionNo ?? 'N/A' }}</td>
                                <td>
                                    @if($assignment->value_type == 'percentage')
                                        {{ $assignment->value }}%
                                    @else
                                        ₦{{ number_format($assignment->value, 2) }}
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $assignment->status }}">
                                        {{ ucfirst($assignment->status) }}
                                    </span>
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($assignment->effective_from)->format('d M Y') }}
                                    @if($assignment->effective_to)
                                        → {{ \Carbon\Carbon::parse($assignment->effective_to)->format('d M Y') }}
                                    @else
                                        → Ongoing
                                    @endif
                                </td>
                                <td>{{ $assignment->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No assignments found for this scholarship.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($assignments->hasPages())
        <div class="card-footer bg-white">
            {{ $assignments->links() }}
        </div>
        @endif
    </div>

</div>
</div>
</div>
@endsection
