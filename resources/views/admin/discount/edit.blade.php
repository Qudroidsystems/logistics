{{-- resources/views/admin/discount/edit.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --disc-primary: #1e3a5f;
    --disc-border: #e2e8f0;
    --disc-radius: 12px;
}

.form-section {
    background: white;
    border: 1px solid var(--disc-border);
    border-radius: var(--disc-radius);
    padding: 24px;
    margin-bottom: 24px;
}
.form-section h5 {
    font-size: 16px;
    font-weight: 600;
    color: var(--disc-primary);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--disc-border);
}
.section-icon {
    width: 32px; height: 32px;
    background: var(--disc-primary);
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
}
.section-icon i { color: white; font-size: 16px; }
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500;
}
.status-active { background: #dcfce7; color: #16a34a; }
.status-draft { background: #fef3c7; color: #d97706; }
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
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.discount.index') }}" class="btn btn-light me-2">
                        <i class="ri-arrow-left-line me-1"></i>Back to List
                    </a>
                    <span class="status-badge status-{{ $discount->status }}">
                        <i class="ri-{{ $discount->status == 'active' ? 'check-circle-line' : 'edit-line' }}"></i>
                        {{ ucfirst($discount->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form id="discountForm" action="{{ route('admin.discount.update', $discount->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                {{-- Basic Information --}}
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-information-line"></i></div>
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Discount No</label>
                            <input type="text" class="form-control bg-light" value="{{ $discount->discount_no }}" readonly disabled>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $discount->title) }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $discount->description) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                            <select name="discount_type_id" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($discountTypes ?? [] as $type)
                                    <option value="{{ $type->id }}" {{ $discount->discount_type_id == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" {{ $discount->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ $discount->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ $discount->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Discount Value --}}
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-money-dollar-circle-line"></i></div>
                        <h5 class="mb-0">Discount Value</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Value Type <span class="text-danger">*</span></label>
                            <select name="value_type" id="valueType" class="form-select" required>
                                <option value="percentage" {{ $discount->value_type == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                <option value="fixed_amount" {{ $discount->value_type == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount (₦)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="valueLabel">{{ $discount->value_type == 'percentage' ? 'Value (%)' : 'Value (₦)' }} <span class="text-danger">*</span></label>
                            <input type="number" name="value" id="value" class="form-control" step="0.01" value="{{ $discount->value }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Maximum Amount</label>
                            <input type="number" name="max_amount" class="form-control" step="0.01" value="{{ $discount->max_amount }}" placeholder="Optional">
                        </div>
                    </div>
                </div>

                {{-- Applicability --}}
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-apps-line"></i></div>
                        <h5 class="mb-0">Applicability</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Applies To <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 mb-3">
                                <div class="form-check">
                                    <input type="radio" name="applicable_to" id="allBills" value="all_bills" class="form-check-input" {{ $discount->applicable_to == 'all_bills' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allBills">All Bills</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="applicable_to" id="specificBills" value="specific_bills" class="form-check-input" {{ $discount->applicable_to == 'specific_bills' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="specificBills">Specific Bills</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="applicable_to" id="specificCategories" value="specific_categories" class="form-check-input" {{ $discount->applicable_to == 'specific_categories' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="specificCategories">Specific Categories</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12" id="billSelectionDiv" style="{{ $discount->applicable_to == 'specific_bills' ? 'display: block;' : 'display: none;' }}">
                            <label class="form-label fw-semibold">Select Bills</label>
                            <div class="border rounded-3 p-3" style="max-height: 200px; overflow-y: auto;">
                                @php
                                    $applicableBillIds = json_decode($discount->applicable_bill_ids, true) ?? [];
                                @endphp
                                @foreach($bills ?? [] as $bill)
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="applicable_bill_ids[]" value="{{ $bill->id }}" class="form-check-input" id="bill_{{ $bill->id }}" {{ in_array($bill->id, $applicableBillIds) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="bill_{{ $bill->id }}">{{ $bill->title }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-12" id="categorySelectionDiv" style="{{ $discount->applicable_to == 'specific_categories' ? 'display: block;' : 'display: none;' }}">
                            <label class="form-label fw-semibold">Select Categories</label>
                            <div class="border rounded-3 p-3" style="max-height: 200px; overflow-y: auto;">
                                @php
                                    $applicableCategories = json_decode($discount->applicable_categories, true) ?? [];
                                @endphp
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="applicable_categories[]" value="TUITION" class="form-check-input" id="cat_tuition" {{ in_array('TUITION', $applicableCategories) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cat_tuition">Tuition Fee</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="applicable_categories[]" value="DEV_LEVY" class="form-check-input" id="cat_dev" {{ in_array('DEV_LEVY', $applicableCategories) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cat_dev">Development Levy</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="applicable_categories[]" value="ICT" class="form-check-input" id="cat_ict" {{ in_array('ICT', $applicableCategories) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cat_ict">ICT Fee</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="applicable_categories[]" value="SPORTS" class="form-check-input" id="cat_sports" {{ in_array('SPORTS', $applicableCategories) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cat_sports">Sports Fee</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="applicable_categories[]" value="LIBRARY" class="form-check-input" id="cat_library" {{ in_array('LIBRARY', $applicableCategories) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cat_library">Library Fee</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conditions --}}
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-git-branch-line"></i></div>
                        <h5 class="mb-0">Conditions</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Condition Type</label>
                            <select name="condition_type" id="conditionType" class="form-select">
                                <option value="none" {{ $discount->condition_type == 'none' ? 'selected' : '' }}>No Condition</option>
                                <option value="early_payment" {{ $discount->condition_type == 'early_payment' ? 'selected' : '' }}>Early Payment</option>
                                <option value="min_amount" {{ $discount->condition_type == 'min_amount' ? 'selected' : '' }}>Minimum Amount</option>
                                <option value="sibling_count" {{ $discount->condition_type == 'sibling_count' ? 'selected' : '' }}>Sibling Count</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="conditionValueDiv" style="{{ $discount->condition_type != 'none' ? 'display: block;' : 'display: none;' }}">
                            <label class="form-label fw-semibold" id="conditionLabel">
                                @if($discount->condition_type == 'early_payment') Discount Percentage (%)
                                @elseif($discount->condition_type == 'min_amount') Minimum Amount (₦)
                                @elseif($discount->condition_type == 'sibling_count') Number of Siblings
                                @else Condition Value
                                @endif
                            </label>
                            <input type="number" name="condition_value" id="conditionValue" class="form-control" step="0.01" value="{{ $discount->condition_value }}">
                        </div>
                        <div class="col-md-6" id="daysBeforeDueDiv" style="{{ $discount->condition_type == 'early_payment' ? 'display: block;' : 'display: none;' }}">
                            <label class="form-label fw-semibold">Days Before Due Date</label>
                            <input type="number" name="days_before_due" class="form-control" value="{{ $discount->days_before_due }}" placeholder="e.g., 7">
                        </div>
                    </div>
                </div>

                {{-- Stacking Rules --}}
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-stack-line"></i></div>
                        <h5 class="mb-0">Stacking Rules</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="stackable_with_scholarship" value="1" class="form-check-input" id="stackScholarship" {{ $discount->stackable_with_scholarship ? 'checked' : '' }}>
                                <label class="form-check-label" for="stackScholarship">Stackable with Scholarship</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="stackable_with_other_discounts" value="1" class="form-check-input" id="stackOther" {{ $discount->stackable_with_other_discounts ? 'checked' : '' }}>
                                <label class="form-check-label" for="stackOther">Stackable with Other Discounts</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Stacking Priority</label>
                            <input type="number" name="stacking_priority" class="form-control" value="{{ $discount->stacking_priority ?? 1 }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Effective Dates --}}
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-calendar-line"></i></div>
                        <h5 class="mb-0">Effective Dates</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                            <input type="date" name="effective_from" class="form-control" value="{{ $discount->effective_from->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Effective To</label>
                            <input type="date" name="effective_to" class="form-control" value="{{ $discount->effective_to ? $discount->effective_to->format('Y-m-d') : '' }}">
                            <small class="text-muted">Leave empty for ongoing</small>
                        </div>
                    </div>
                </div>

                {{-- Statistics --}}
                <div class="form-section bg-light">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon bg-success"><i class="ri-bar-chart-line"></i></div>
                        <h5 class="mb-0">Statistics</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-2 bg-white rounded">
                                <div class="fs-4 fw-bold text-primary">{{ $discount->assignments_count ?? 0 }}</div>
                                <div class="small text-muted">Total Assignments</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-2 bg-white rounded">
                                <div class="fs-4 fw-bold text-success">{{ $discount->assignments->where('status', 'active')->count() }}</div>
                                <div class="small text-muted">Active</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="form-section text-end">
                    <button type="button" class="btn btn-light me-2" onclick="window.history.back()">
                        <i class="ri-close-line me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ri-save-line me-1"></i>Update Discount
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const valueType = document.getElementById('valueType');
    const valueLabel = document.getElementById('valueLabel');

    valueType.addEventListener('change', function() {
        valueLabel.textContent = this.value === 'percentage' ? 'Value (%)' : 'Value (₦)';
    });

    // Applicability toggles
    const allBillsRadio = document.getElementById('allBills');
    const specificBillsRadio = document.getElementById('specificBills');
    const specificCategoriesRadio = document.getElementById('specificCategories');
    const billSelectionDiv = document.getElementById('billSelectionDiv');
    const categorySelectionDiv = document.getElementById('categorySelectionDiv');

    allBillsRadio.addEventListener('change', function() {
        billSelectionDiv.style.display = 'none';
        categorySelectionDiv.style.display = 'none';
    });
    specificBillsRadio.addEventListener('change', function() {
        billSelectionDiv.style.display = 'block';
        categorySelectionDiv.style.display = 'none';
    });
    specificCategoriesRadio.addEventListener('change', function() {
        billSelectionDiv.style.display = 'none';
        categorySelectionDiv.style.display = 'block';
    });

    // Condition type toggles
    const conditionType = document.getElementById('conditionType');
    const conditionValueDiv = document.getElementById('conditionValueDiv');
    const daysBeforeDueDiv = document.getElementById('daysBeforeDueDiv');
    const conditionLabel = document.getElementById('conditionLabel');

    conditionType.addEventListener('change', function() {
        conditionValueDiv.style.display = this.value !== 'none' ? 'block' : 'none';
        daysBeforeDueDiv.style.display = this.value === 'early_payment' ? 'block' : 'none';

        if (this.value === 'early_payment') {
            conditionLabel.textContent = 'Discount Percentage (%)';
        } else if (this.value === 'min_amount') {
            conditionLabel.textContent = 'Minimum Amount (₦)';
        } else if (this.value === 'sibling_count') {
            conditionLabel.textContent = 'Number of Siblings';
        }
    });

    // Form submission
    const form = document.getElementById('discountForm');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = '{{ route("admin.discount.index") }}';
                });
            } else {
                let errorMsg = data.message || 'Something went wrong';
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join('\n');
                }
                Swal.fire({ icon: 'error', title: 'Error!', text: errorMsg });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error!', text: 'Network error. Please try again.' });
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});
</script>
@endsection
