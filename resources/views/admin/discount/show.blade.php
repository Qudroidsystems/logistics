{{-- resources/views/admin/discount/show.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --disc-primary: #1e3a5f;
    --disc-success: #16a34a;
    --disc-warning: #d97706;
    --disc-border: #e2e8f0;
}

.detail-card {
    background: white;
    border: 1px solid var(--disc-border);
    border-radius: 12px;
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
    color: var(--disc-primary);
}
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 500;
}
.status-active { background: #dcfce7; color: #16a34a; }
.status-draft { background: #fef3c7; color: #d97706; }
.status-expired { background: #fee2e2; color: #dc2626; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold" style="color: var(--disc-primary);">
                        <i class="ri-discount-line me-2"></i>{{ $pagetitle }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.discount.index') }}">Discounts</a></li>
                            <li class="breadcrumb-item active">Details</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.discount.index') }}" class="btn btn-light me-2">
                        <i class="ri-arrow-left-line me-1"></i>Back to List
                    </a>
                    <a href="{{ route('admin.discount.edit', $discount->id) }}" class="btn btn-primary">
                        <i class="ri-pencil-line me-1"></i>Edit Discount
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Discount Information --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--disc-primary);">
                    <i class="ri-information-line me-2"></i>Discount Information
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-label">Discount No</div>
                        <div class="detail-value"><code>{{ $discount->discount_no }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-{{ $discount->status }}">
                                <i class="ri-{{ $discount->status == 'active' ? 'check-circle-line' : ($discount->status == 'draft' ? 'edit-line' : 'close-circle-line') }}"></i>
                                {{ ucfirst($discount->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="detail-label">Title</div>
                        <div class="detail-value">{{ $discount->title }}</div>
                    </div>
                    <div class="col-md-12">
                        <div class="detail-label">Description</div>
                        <div class="detail-value">{{ $discount->description ?? 'No description provided.' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Discount Type</div>
                        <div class="detail-value">{{ $discount->type->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Applicable To</div>
                        <div class="detail-value">
                            @if($discount->applicable_to == 'all_bills')
                                All Bills
                            @elseif($discount->applicable_to == 'specific_bills')
                                Selected Bills
                            @else
                                Selected Categories
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Value Information --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--disc-primary);">
                    <i class="ri-money-dollar-circle-line me-2"></i>Value Information
                </h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="detail-label">Value Type</div>
                        <div class="detail-value">{{ ucfirst(str_replace('_', ' ', $discount->value_type)) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-label">Value</div>
                        <div class="detail-value">
                            @if($discount->value_type == 'percentage')
                                {{ $discount->value }}%
                            @else
                                ₦{{ number_format($discount->value, 2) }}
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-label">Maximum Amount</div>
                        <div class="detail-value">{{ $discount->max_amount ? '₦' . number_format($discount->max_amount, 2) : 'No limit' }}</div>
                    </div>
                </div>
            </div>

            {{-- Conditions --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--disc-primary);">
                    <i class="ri-git-branch-line me-2"></i>Conditions
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-label">Condition Type</div>
                        <div class="detail-value">{{ ucfirst(str_replace('_', ' ', $discount->condition_type)) }}</div>
                    </div>
                    @if($discount->condition_type != 'none')
                    <div class="col-md-6">
                        <div class="detail-label">Condition Value</div>
                        <div class="detail-value">
                            @if($discount->condition_type == 'early_payment')
                                {{ $discount->condition_value }}% off
                            @elseif($discount->condition_type == 'min_amount')
                                ₦{{ number_format($discount->condition_value, 2) }}
                            @elseif($discount->condition_type == 'sibling_count')
                                {{ $discount->condition_value }} siblings
                            @endif
                        </div>
                    </div>
                    @endif
                    @if($discount->condition_type == 'early_payment')
                    <div class="col-md-6">
                        <div class="detail-label">Days Before Due Date</div>
                        <div class="detail-value">{{ $discount->days_before_due }} days</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Stacking Rules --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--disc-primary);">
                    <i class="ri-stack-line me-2"></i>Stacking Rules
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-label">Stackable with Scholarship</div>
                        <div class="detail-value">{{ $discount->stackable_with_scholarship ? 'Yes' : 'No' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Stackable with Other Discounts</div>
                        <div class="detail-value">{{ $discount->stackable_with_other_discounts ? 'Yes' : 'No' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Stacking Priority</div>
                        <div class="detail-value">{{ $discount->stacking_priority ?? 1 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Effective Dates --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--disc-primary);">
                    <i class="ri-calendar-line me-2"></i>Effective Dates
                </h5>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="detail-label">Effective From</div>
                        <div class="detail-value">{{ $discount->effective_from->format('d F, Y') }}</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Effective To</div>
                        <div class="detail-value">{{ $discount->effective_to ? $discount->effective_to->format('d F, Y') : 'Ongoing' }}</div>
                    </div>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--disc-primary);">
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
            </div>

            {{-- Meta Information --}}
            <div class="detail-card">
                <h5 class="fw-semibold mb-3" style="color: var(--disc-primary);">
                    <i class="ri-information-line me-2"></i>Meta Information
                </h5>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="detail-label">Created By</div>
                        <div class="detail-value">{{ $discount->createdBy->name ?? 'Unknown' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Created At</div>
                        <div class="detail-value">{{ $discount->created_at->format('d F, Y H:i') }}</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value">{{ $discount->updated_at->format('d F, Y H:i') }}</div>
                    </div>
                    @if($discount->approved_by)
                    <div class="col-12">
                        <div class="detail-label">Approved By</div>
                        <div class="detail-value">{{ $discount->approvedBy->name ?? 'Unknown' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Approved At</div>
                        <div class="detail-value">{{ $discount->approved_at ? $discount->approved_at->format('d F, Y H:i') : 'N/A' }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Assignments List --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="ri-user-settings-line me-2"></i>Discount Assignments</h5>
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
                                <td colspan="7" class="text-center py-4 text-muted">No assignments found for this discount.</td>
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
