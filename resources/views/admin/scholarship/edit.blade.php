{{-- resources/views/admin/scholarship/edit.blade.php --}}
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
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500;
}
.status-active    { background: #dcfce7; color: #16a34a; }
.status-draft     { background: #fef3c7; color: #d97706; }
.status-expired   { background: #fee2e2; color: #dc2626; }
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
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.scholarship.index') }}" class="btn btn-light me-2">
                        <i class="ri-arrow-left-line me-1"></i>Back to List
                    </a>
                    <span class="status-badge status-{{ $scholarship->status }}">
                        <i class="ri-{{ $scholarship->status == 'active' ? 'check-circle-line' : ($scholarship->status == 'draft' ? 'edit-line' : 'close-circle-line') }}"></i>
                        {{ ucfirst($scholarship->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form id="scholarshipForm" action="{{ route('admin.scholarship.update', $scholarship->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="form-section">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon"><i class="ri-information-line"></i></div>
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Scholarship No</label>
                            <input type="text" class="form-control bg-light" value="{{ $scholarship->scholarship_no }}" readonly disabled>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   value="{{ old('title', $scholarship->title) }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $scholarship->description) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Scholarship Type <span class="text-danger">*</span></label>
                            <select name="scholarship_type_id" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($scholarshipTypes ?? [] as $type)
                                    <option value="{{ $type->id }}" {{ $scholarship->scholarship_type_id == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft"      {{ $scholarship->status == 'draft'      ? 'selected' : '' }}>Draft</option>
                                <option value="active"     {{ $scholarship->status == 'active'     ? 'selected' : '' }}>Active</option>
                                <option value="suspended"  {{ $scholarship->status == 'suspended'  ? 'selected' : '' }}>Suspended</option>
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
                                <option value="percentage"   {{ $scholarship->value_type == 'percentage'   ? 'selected' : '' }}>Percentage (%)</option>
                                <option value="fixed_amount" {{ $scholarship->value_type == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount (₦)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="valueLabel">
                                {{ $scholarship->value_type == 'percentage' ? 'Value (%)' : 'Value (₦)' }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="value" id="value" class="form-control"
                                   step="0.01" value="{{ $scholarship->value }}" required>
                        </div>
                        <div class="col-md-6" id="capAmountDiv"
                             style="{{ $scholarship->value_type == 'percentage' ? 'display:block' : 'display:none' }}">
                            <label class="form-label fw-semibold">Maximum Amount (Cap)</label>
                            <input type="number" name="cap_amount" class="form-control"
                                   step="0.01" value="{{ $scholarship->cap_amount }}"
                                   placeholder="Optional – limits the maximum discount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Budget Amount (Optional)</label>
                            <input type="number" name="budget_amount" class="form-control"
                                   step="0.01" value="{{ $scholarship->budget_amount }}"
                                   placeholder="Total budget for this scholarship">
                            @if($scholarship->budget_amount > 0)
                                @php $utilizedPercent = ($scholarship->utilized_amount / $scholarship->budget_amount) * 100; @endphp
                                <small class="text-muted">
                                    Utilized: ₦{{ number_format($scholarship->utilized_amount, 2) }}
                                    ({{ round($utilizedPercent, 1) }}%)
                                </small>
                            @endif
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
                            <input type="date" name="effective_from" class="form-control"
                                   value="{{ $scholarship->effective_from->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Effective To</label>
                            <input type="date" name="effective_to" class="form-control"
                                   value="{{ $scholarship->effective_to ? $scholarship->effective_to->format('Y-m-d') : '' }}">
                            <small class="text-muted">Leave empty for ongoing</small>
                        </div>
                    </div>
                </div>

                <div class="form-section bg-light">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon bg-success"><i class="ri-bar-chart-line"></i></div>
                        <h5 class="mb-0">Statistics</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-2 bg-white rounded">
                                <div class="fs-4 fw-bold text-primary">{{ $scholarship->assignments_count ?? 0 }}</div>
                                <div class="small text-muted">Total Assignments</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-2 bg-white rounded">
                                <div class="fs-4 fw-bold text-success">{{ $scholarship->assignments->where('status', 'active')->count() }}</div>
                                <div class="small text-muted">Active</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="small text-muted">Total Value Awarded</div>
                        <div class="fw-bold">₦{{ number_format($scholarship->utilized_amount, 2) }}</div>
                    </div>
                </div>

                <div class="form-section bg-light">
                    <div class="d-flex align-items-center mb-3">
                        <div class="section-icon bg-warning"><i class="ri-lightbulb-line"></i></div>
                        <h5 class="mb-0">Quick Tips</h5>
                    </div>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2">✓ Changing value type will affect existing assignments</li>
                        <li class="mb-2">✓ Deactivating a scholarship will revoke active assignments</li>
                        <li class="mb-2">✓ Budget cannot be less than already utilised amount</li>
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
                    <button type="submit" class="btn {{ $scholarship->status == 'active' ? 'btn-warning' : 'btn-primary' }}" id="submitBtn">
                        <i class="ri-save-line me-1"></i>Update Scholarship
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
        valueLabel.firstChild.textContent = this.value === 'percentage' ? 'Value (%) ' : 'Value (₦) ';
        capAmountDiv.style.display = this.value === 'percentage' ? 'block' : 'none';
    });

    const form      = document.getElementById('scholarshipForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const originalText = submitBtn.innerHTML;
        submitBtn.disabled  = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',   // required for $request->ajax()
                },
                body: formData,
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
