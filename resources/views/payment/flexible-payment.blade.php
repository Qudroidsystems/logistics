{{-- resources/views/payment/flexible-payment.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
.bill-item {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s;
}
.bill-item:hover { border-color: #2563eb; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold">Flexible Payment - {{ $student->firstname }} {{ $student->lastname }}</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between">
                        <h5 class="mb-0">Select Bills to Pay</h5>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="selectAll">
                            <label class="form-check-label">Select All</label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @foreach($bills as $bill)
                        <div class="bill-item m-3" data-bill-id="{{ $bill['bill_id'] }}">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <input type="checkbox" class="form-check-input bill-checkbox"
                                           data-bill-id="{{ $bill['bill_id'] }}" data-outstanding="{{ $bill['outstanding'] }}">
                                </div>
                                <div class="col">
                                    <h6 class="mb-1">{{ $bill['title'] }}</h6>
                                    <small class="text-muted">{{ $bill['description'] }}</small>
                                    <div class="mt-2">
                                        <del class="text-muted">₦{{ number_format($bill['original_amount'], 2) }}</del>
                                        <strong class="text-primary ms-2">₦{{ number_format($bill['adjusted_amount'], 2) }}</strong>
                                        @if($bill['savings'] > 0)
                                            <span class="badge bg-success ms-2">Save ₦{{ number_format($bill['savings'], 2) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control payment-amount"
                                           data-bill-id="{{ $bill['bill_id'] }}" data-max="{{ $bill['outstanding'] }}"
                                           placeholder="Amount" disabled>
                                    <small class="text-muted">Balance: ₦{{ number_format($bill['outstanding'], 2) }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Payment Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email for Receipt</label>
                        <input type="email" id="email" class="form-control" value="{{ $student->email ?? '' }}" required>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Selected Bills:</span>
                            <strong id="selectedCount">0</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Total to Pay:</span>
                            <strong class="text-primary fs-5" id="totalAmount">₦0</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Total Savings:</span>
                            <strong class="text-success" id="totalSavings">₦0</strong>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" id="payOfflineBtn">
                            <i class="ri-building-line me-2"></i>Pay Offline
                        </button>
                        <button class="btn btn-success" id="payOnlineBtn">
                            <i class="ri-bank-card-line me-2"></i>Pay Online
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
let selectedBills = [];

$(document).ready(function() {
    $('#selectAll').on('change', function() {
        $('.bill-checkbox').prop('checked', this.checked).trigger('change');
    });

    $('.bill-checkbox').on('change', function() {
        const amountInput = $(this).closest('.bill-item').find('.payment-amount');
        if ($(this).is(':checked')) {
            amountInput.prop('disabled', false).val(amountInput.data('max'));
        } else {
            amountInput.prop('disabled', true).val('');
        }
        updateSummary();
    });

    $('.payment-amount').on('input', function() {
        updateSummary();
    });

    function updateSummary() {
        selectedBills = [];
        let total = 0, savings = 0, count = 0;

        $('.bill-checkbox:checked').each(function() {
            const billCard = $(this).closest('.bill-item');
            const billId = $(this).data('billId');
            const amount = parseFloat(billCard.find('.payment-amount').val()) || 0;
            const billSavings = parseFloat(billCard.find('.badge.bg-success').text().replace('Save ₦', '')) || 0;

            if (amount > 0) {
                selectedBills.push({ bill_id: billId, amount: amount });
                total += amount;
                savings += billSavings;
                count++;
            }
        });

        $('#selectedCount').text(count);
        $('#totalAmount').text('₦' + total.toLocaleString());
        $('#totalSavings').text('₦' + savings.toLocaleString());
    }

    $('#payOfflineBtn').on('click', function() {
        if (selectedBills.length === 0) {
            Swal.fire('Error', 'Please select at least one bill', 'error');
            return;
        }
        // Process offline payment logic here
        Swal.fire('Success', 'Payment processed successfully!', 'success');
    });

    $('#payOnlineBtn').on('click', function() {
        if (selectedBills.length === 0) {
            Swal.fire('Error', 'Please select at least one bill', 'error');
            return;
        }
        // Redirect to online payment
        Swal.fire('Info', 'Redirecting to payment gateway...', 'info');
    });
});
</script>
@endsection
