{{-- resources/views/finance/staff/payments-create.blade.php --}}
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
                        <i class="ri-wallet-line me-2"></i>{{ $pagetitle }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('staff.payments.index') }}">Staff Payments</a></li>
                            <li class="breadcrumb-item active">Record Payment</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('staff.payments.index') }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <form id="paymentForm" action="{{ route('staff.payments.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="form-section">
                    <h5><i class="ri-information-line me-2"></i>Payment Information</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Staff Member <span class="text-danger">*</span></label>
                            <select name="staff_id" class="form-select" required>
                                <option value="">-- Select Staff --</option>
                                @foreach($staff as $s)
                                    <option value="{{ $s->id }}">{{ $s->user->name ?? 'N/A' }} ({{ $s->employmentid ?? 'No ID' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Type <span class="text-danger">*</span></label>
                            <select name="payment_type" id="paymentType" class="form-select" required>
                                <option value="">-- Select Type --</option>
                                <option value="salary">Salary</option>
                                <option value="bonus">Bonus</option>
                                <option value="loan_disbursement">Loan Disbursement</option>
                                <option value="reimbursement">Reimbursement</option>
                                <option value="advance">Salary Advance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="paymentMethod" class="form-select" required>
                                <option value="">-- Select Method --</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" name="amount" class="form-control" step="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Purpose <span class="text-danger">*</span></label>
                            <input type="text" name="purpose" class="form-control" required placeholder="e.g., January Salary, Performance Bonus, etc.">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section" id="bankDetailsSection" style="display: none;">
                    <h5><i class="ri-bank-line me-2"></i>Bank Transfer Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="e.g., GTBank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Account Number</label>
                            <input type="text" name="account_number" class="form-control" placeholder="Account number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Account Name</label>
                            <input type="text" name="account_name" class="form-control" placeholder="Account holder name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Transaction Reference</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Transaction/Reference number">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="form-section bg-light">
                    <h5><i class="ri-lightbulb-line me-2"></i>Quick Tips</h5>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2">✓ Select the correct staff member</li>
                        <li class="mb-2">✓ Choose the appropriate payment type</li>
                        <li class="mb-2">✓ For bank transfers, provide full bank details</li>
                        <li class="mb-2">✓ Add reference numbers for easy tracking</li>
                        <li class="mb-2">✓ Keep receipts for verification</li>
                    </ul>
                </div>

                <div class="form-section bg-light mt-3">
                    <h5><i class="ri-attachment-line me-2"></i>Receipt/Proof</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Attachment</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.png">
                        <small class="text-muted">Supported formats: PDF, JPG, PNG (Max 2MB)</small>
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
                        <i class="ri-save-line me-1"></i>Record Payment
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
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethod = document.getElementById('paymentMethod');
    const bankDetailsSection = document.getElementById('bankDetailsSection');

    paymentMethod.addEventListener('change', function() {
        bankDetailsSection.style.display = this.value === 'bank_transfer' ? 'block' : 'none';
    });

    const form = document.getElementById('paymentForm');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

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
                    title: 'Success!',
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
