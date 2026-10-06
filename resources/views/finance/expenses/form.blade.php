{{-- resources/views/finance/expenses/form.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $M = \App\Models\ExpenseVoucher::METHODS;
    $val = fn ($k, $d = null) => old($k, $v?->$k ?? $d);
    $prText = $pr ? collect($pr->items)->map(fn ($i) => $i['qty'] . ' × ' . $i['description'])->implode('; ') : null;
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$v ? 'Edit ' . $v->voucher_no : 'New expense'" icon="ri-receipt-line" subtitle="Payment voucher" :back="$v ? route('finance.expenses.show', $v) : route('finance.expenses')" back-label="Back" />
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    @if($pr)<div class="cb-banner info"><i class="ri-shopping-cart-2-line"></i><div>From purchase request <b>{{ $pr->pr_no }}</b> — {{ $pr->title }}</div></div>@endif

    <form method="POST" enctype="multipart/form-data" action="{{ $v ? route('finance.expenses.update', $v) : route('finance.expenses.store') }}">@csrf @if($v) @method('PUT') @endif
        @if($pr)<input type="hidden" name="purchase_request_id" value="{{ $pr->id }}">@endif
        <div class="row g-3">
            <div class="col-xl-8">
                <x-cb.card title="Details" icon="ri-file-edit-line">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="{{ $val('expense_date') ? \Carbon\Carbon::parse($val('expense_date'))->toDateString() : now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-4"><label class="form-label">Category</label><select name="expense_category_id" class="form-select" required><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected($val('expense_category_id', $pr?->expense_category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Amount (₦)</label><input type="number" step="0.01" min="1" name="amount" class="form-control" value="{{ $val('amount', $pr?->estimated_total) }}" required><div class="form-text">Two approvals above ₦{{ number_format($settings['second_approval_above']) }}</div></div>
                        <div class="col-md-6"><label class="form-label">Vendor (optional)</label><select name="vendor_id" class="form-select" id="vendorSel"><option value="">—</option>@foreach($vendors as $vd)<option value="{{ $vd->id }}" data-name="{{ $vd->name }}" @selected($val('vendor_id', $pr?->vendor_id) == $vd->id)>{{ $vd->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Or refund a staff member</label><select name="staff_id" class="form-select" id="staffSel"><option value="">—</option>@foreach($staff as $s)<option value="{{ $s->id }}" data-name="{{ $s->name }}" @selected($val('staff_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Payee name</label><input type="text" name="payee_name" id="payee" class="form-control" maxlength="150" value="{{ $val('payee_name', $pr?->vendor?->name) }}" required></div>
                        <div class="col-md-6"><label class="form-label">How it will be paid</label><select name="payment_method" class="form-select">@foreach($M as $k => $l)<option value="{{ $k }}" @selected($val('payment_method', 'bank_transfer') === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" maxlength="500" required>{{ $val('description', $prText) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Receipt / invoice</label><input type="file" name="receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp"><div class="form-text">Required above ₦{{ number_format($settings['receipt_required_above']) }}. PDF or photo, max 5 MB.@if($v?->receipt_path) A file is already attached.@endif</div></div>
                        <div class="col-md-6"><label class="form-label">Invoice / reference no.</label><input type="text" name="reference" class="form-control" maxlength="80" value="{{ $val('reference') }}"></div>
                    </div>
                </x-cb.card>
            </div>
            <div class="col-xl-4">
                <x-cb.card title="Equipment?" icon="ri-archive-drawer-line">
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="capitalise" value="1" id="cap" @checked($val('capitalise')) onchange="document.getElementById('capBox').style.display=this.checked?'':'none'"><label for="cap" class="form-check-label">Add to the asset register when paid</label></div>
                    <div id="capBox" style="{{ $val('capitalise') ? '' : 'display:none' }}">
                        <select name="asset_account" class="form-select form-select-sm mb-2">@foreach(\App\Models\FixedAsset::CLASSES as $k => $l)<option value="{{ $k }}" @selected($val('asset_account') === $k)>{{ $l }}</option>@endforeach</select>
                        <input type="number" name="asset_life_months" min="1" class="form-control form-control-sm" placeholder="Useful life (months)" value="{{ $val('asset_life_months') }}">
                    </div>
                    <div class="small text-muted mt-2">For furniture, computers, vehicles and buildings. The cost is spread over its life as depreciation instead of hitting one month.</div>
                </x-cb.card>
                <div class="d-grid gap-2">
                    <button name="action" value="submit" class="action-btn btn-primary-cb justify-content-center"><i class="ri-send-plane-line"></i>Submit for approval</button>
                    <button name="action" value="draft" class="action-btn btn-open justify-content-center"><i class="ri-save-line"></i>Save draft</button>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
</div>
<script>
['vendorSel', 'staffSel'].forEach(id => document.getElementById(id).addEventListener('change', e => {
    const o = e.target.selectedOptions[0]; if (o && o.dataset.name) document.getElementById('payee').value = o.dataset.name;
    if (id === 'vendorSel' && e.target.value) document.getElementById('staffSel').value = '';
    if (id === 'staffSel' && e.target.value) document.getElementById('vendorSel').value = '';
}));
</script>
@endsection
