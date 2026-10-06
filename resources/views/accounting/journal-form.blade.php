{{-- resources/views/accounting/journal-form.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="New journal entry" icon="ri-book-2-line" subtitle="For opening balances, corrections, bank charges, transfers between accounts and anything not recorded elsewhere." :back="route('accounting.journals')" back-label="Journal" />
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    @if($closed)<div class="cb-banner info"><i class="ri-lock-line"></i><div>Books are closed up to {{ \Carbon\Carbon::parse($closed)->format('d M Y') }}.</div></div>@endif

    <form method="POST" action="{{ route('accounting.journals.store') }}" id="jf">@csrf
        <x-cb.card title="Entry" icon="ri-file-edit-line">
            <div class="row g-3 mb-3">
                <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Type</label><select name="entry_type" class="form-select">@foreach(['journal' => 'General journal', 'opening' => 'Opening balance', 'adjustment' => 'Adjustment / correction', 'contra' => 'Transfer between bank & cash', 'receipt' => 'Other receipt', 'payment' => 'Other payment'] as $k => $l)<option value="{{ $k }}" @selected(old('entry_type') === $k)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Description</label><input type="text" name="description" value="{{ old('description') }}" class="form-control" maxlength="500" required placeholder="e.g. Opening bank balance at 1 Sept 2026"></div>
            </div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th style="width:38%">Account</th><th>Narration</th><th style="width:150px" class="text-end">Debit ₦</th><th style="width:150px" class="text-end">Credit ₦</th><th></th></tr></thead>
                <tbody id="jl"></tbody>
                <tfoot><tr><td><button type="button" class="btn btn-sm btn-light" onclick="row()"><i class="ri-add-line"></i> Line</button></td><td class="text-end"><b>Totals</b></td><td class="text-end fw-bold" id="tdr">0.00</td><td class="text-end fw-bold" id="tcr">0.00</td><td></td></tr>
                       <tr><td colspan="5" class="text-end small" id="bal"></td></tr></tfoot>
            </table></div>
        </x-cb.card>
        <div class="d-flex gap-2 justify-content-end">
            <button name="action" value="draft" class="action-btn btn-open"><i class="ri-save-line"></i>Save draft</button>
            <button name="action" value="post" class="action-btn btn-primary-cb" id="postBtn"><i class="ri-check-double-line"></i>Post</button>
        </div>
    </form>
</div>
</div>
</div>
<template id="acctOpts"><option value="">Choose account…</option>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->account_code }} · {{ $a->account_name }}</option>@endforeach</template>
<script>
let i = 0;
const opts = document.getElementById('acctOpts').innerHTML;
function row(v) {
    v = v || {}; const n = i++, tr = document.createElement('tr');
    tr.innerHTML = `<td><select name="lines[${n}][account_id]" class="form-select form-select-sm" required>${opts}</select></td>
        <td><input name="lines[${n}][narration]" class="form-control form-control-sm"></td>
        <td><input name="lines[${n}][debit]" type="number" step="0.01" min="0" class="form-control form-control-sm text-end dr"></td>
        <td><input name="lines[${n}][credit]" type="number" step="0.01" min="0" class="form-control form-control-sm text-end cr"></td>
        <td><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove();calc()">&times;</button></td>`;
    document.getElementById('jl').appendChild(tr);
    if (v.account_id) tr.querySelector('select').value = v.account_id;
    ['debit', 'credit', 'narration'].forEach(k => { if (v[k]) tr.querySelector(`[name$="[${k}]"]`).value = v[k]; });
}
function calc() {
    let d = 0, c = 0;
    document.querySelectorAll('.dr').forEach(e => d += +e.value || 0); document.querySelectorAll('.cr').forEach(e => c += +e.value || 0);
    const f = x => x.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('tdr').textContent = f(d); document.getElementById('tcr').textContent = f(c);
    const diff = Math.round((d - c) * 100) / 100, b = document.getElementById('bal');
    b.innerHTML = diff === 0 && d > 0 ? '<span class="text-success">✓ Balanced</span>' : '<span class="text-danger">Difference ₦' + f(Math.abs(diff)) + '</span>';
    document.getElementById('postBtn').disabled = !(diff === 0 && d > 0);
}
document.getElementById('jf').addEventListener('input', calc);
const old = @json(old('lines', []));
(Object.values(old).length ? Object.values(old) : [{}, {}]).forEach(row);
calc();
</script>
@endsection
