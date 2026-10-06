{{-- resources/views/admin/scholarship/create.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --sch-primary: #1e3a5f;
    --sch-accent: #2563eb;
    --sch-border: #e2e8f0;
    --sch-radius: 12px;
}
.form-section {
    background: white;
    border: 1px solid var(--sch-border);
    border-radius: var(--sch-radius);
    padding: 24px;
    margin-bottom: 24px;
}
.form-section h5 {
    font-size: 16px; font-weight: 600; color: var(--sch-primary);
    margin-bottom: 20px; padding-bottom: 10px;
    border-bottom: 2px solid var(--sch-border);
}
.section-icon {
    width: 32px; height: 32px; background: var(--sch-primary);
    border-radius: 8px; display: inline-flex;
    align-items: center; justify-content: center; margin-right: 10px;
}
.section-icon i { color: white; font-size: 16px; }
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
                            <li class="breadcrumb-item active">Create</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('admin.scholarship.index') }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <form id="scholarshipForm" action="{{ route('admin.scholarship.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-information-line"></i></div>
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required
                                   placeholder="e.g., Academic Excellence Scholarship 2024">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Describe the scholarship criteria and benefits..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Scholarship Type <span class="text-danger">*</span></label>
                            <select name="scholarship_type_id" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($scholarshipTypes ?? [] as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-money-dollar-circle-line"></i></div>
                        <h5 class="mb-0">Scholarship Value</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Value Type <span class="text-danger">*</span></label>
                            <select name="value_type" id="valueType" class="form-select" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed_amount">Fixed Amount (₦)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="valueLabel">Value (%) <span class="text-danger">*</span></label>
                            <input type="number" name="value" id="value" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6" id="capAmountDiv" style="display: none;">
                            <label class="form-label fw-semibold">Maximum Amount (Cap)</label>
                            <input type="number" name="cap_amount" class="form-control" step="0.01"
                                   placeholder="Optional – limits the maximum discount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Budget Amount (Optional)</label>
                            <input type="number" name="budget_amount" class="form-control" step="0.01"
                                   placeholder="Total budget for this scholarship">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-calendar-line"></i></div>
                        <h5 class="mb-0">Effective Dates</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                            <input type="date" name="effective_from" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Effective To</label>
                            <input type="date" name="effective_to" class="form-control">
                            <small class="text-muted">Leave empty for ongoing</small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon bg-success"><i class="ri-lightbulb-line"></i></div>
                        <h5 class="mb-0">Quick Tips</h5>
                    </div>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2">✓ Percentage scholarships are calculated on each bill amount</li>
                        <li class="mb-2">✓ Fixed amount scholarships deduct a set amount from total fees</li>
                        <li class="mb-2">✓ Set a cap to limit percentage-based scholarships</li>
                        <li class="mb-2">✓ Draft scholarships are not visible to students</li>
                    </ul>
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
                        <i class="ri-save-line me-1"></i>Create Scholarship
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const valueType    = document.getElementById('valueType');
    const valueLabel   = document.getElementById('valueLabel');
    const capAmountDiv = document.getElementById('capAmountDiv');

    valueType.addEventListener('change', function () {
        if (this.value === 'percentage') {
            valueLabel.textContent = 'Value (%)';
            capAmountDiv.style.display = 'block';
        } else {
            valueLabel.textContent = 'Value (₦)';
            capAmountDiv.style.display = 'none';
        }
    });

    const form      = document.getElementById('scholarshipForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const originalText = submitBtn.innerHTML;
        submitBtn.disabled  = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':        document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With':    'XMLHttpRequest',   // required for $request->ajax()
                },
                body: new FormData(form),
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire({ icon: 'success', title: 'Success!', text: data.message });
                window.location.href = '{{ route("admin.scholarship.index") }}';
            } else {
                let msg = data.message || 'Something went wrong';
                if (data.errors) msg = Object.values(data.errors).flat().join('\n');
                Swal.fire({ icon: 'error', title: 'Error!', text: msg });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error!', text: 'Network error. Please try again.' });
        } finally {
            submitBtn.disabled  = false;
            submitBtn.innerHTML = originalText;
        }
    });
});
</script>
@endsection
