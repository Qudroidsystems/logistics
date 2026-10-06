{{-- resources/views/finance/staff/payments-edit.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.form-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}
.form-section h5 {
    font-size: 16px;
    font-weight: 600;
    color: #1e3a5f;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold" style="color: #1e3a5f;">
                        <i class="ri-edit-line me-2"></i>{{ $pagetitle }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('staff.payments.index') }}">Staff Payments</a></li>
                            <li class="breadcrumb-item active">Edit Payment</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('staff.payments.index') }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <form id="paymentForm" action="{{ route('staff.payments.update', $payment->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="form-section">
                    <h5><i class="ri-information-line me-2"></i>Payment Information</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Staff Member</label>
                            <input type="text" class="form-control bg-light" value="{{ $payment->staff->user->name ?? 'N/A' }} ({{ $payment->staff->employmentid ?? 'No ID' }})" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Type</label>
                            <input type="text" class="form-control bg-light" value="{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference</label>
                            <input type="text" class="form-control bg-light" value="{{ $payment->payment_reference }}" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="bank_transfer" {{ $payment->payment_method == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="cash" {{ $payment->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="cheque" {{ $payment->payment_method == 'cheque' ? 'selected' : '' }}>Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ $payment->payment_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="amount" class="form-control" step="0.01" value="{{ $payment->amount }}" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Purpose <span class="text-danger">*</span></label>
                            <input type="text" name="purpose" class="form-control" value="{{ $payment->purpose }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $payment->notes }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section" id="bankDetailsSection" style="{{ $payment->payment_method == 'bank_transfer' ? 'display: block;' : 'display: none;' }}">
                    <h5><i class="ri-bank-line me-2"></i>Bank Transfer Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ $payment->bank_name }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Account Number</label>
                            <input type="text" name="account_number" class="form-control" value="{{ $payment->account_number }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Account Name</label>
                            <input type="text" name="account_name" class="form-control" value="{{ $payment->account_name }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Transaction Reference</label>
                            <input type="text" name="transaction_ref" class="form-control" value="{{ $payment->transaction_ref }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="form-section bg-light">
                    <h5><i class="ri-lightbulb-line me-2"></i>Quick Tips</h5>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2">✓ Update payment details as needed</li>
                        <li class="mb-2">✓ Cannot change staff or payment type after creation</li>
                        <li class="mb-2">✓ For bank transfers, update bank details</li>
                        <li class="mb-2">✓ Note: Paid payments cannot be edited</li>
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
                        <i class="ri-save-line me-1"></i>Update Payment
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
    const paymentMethod = document.getElementById('paymentMethod');
    const bankDetailsSection = document.getElementById('bankDetailsSection');

    if (paymentMethod) {
        paymentMethod.addEventListener('change', function() {
            bankDetailsSection.style.display = this.value === 'bank_transfer' ? 'block' : 'none';
        });
    }

    const form = document.getElementById('paymentForm');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

        const formData = new FormData(this);

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = '{{ route("staff.payments.index") }}';
                });
            } else {
                let errorMsg = data.message || 'Validation error';
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join('\n');
                }
                Swal.fire('Error!', errorMsg, 'error');
            }
        } catch (error) {
            Swal.fire('Error!', 'Network error. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});
</script>
@endsection
