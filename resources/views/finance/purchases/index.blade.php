{{-- resources/views/finance/purchases/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Purchase Requests" icon="ri-shopping-cart-2-line" subtitle="Ask for what your department needs; approved requests become expense vouchers." />
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    @if($seeAll)
    <div class="row g-3 mb-3">
        @foreach(['submitted' => 'amber', 'approved' => 'teal', 'ordered' => 'rose', 'received' => 'green'] as $k => $acc)
            <div class="col-md-3 col-6"><a class="text-reset" href="{{ route('finance.purchases', ['status' => $k]) }}"><x-cb.stat :label="\App\Models\PurchaseRequest::STATUS[$k][0]" :value="($counts[$k]->n ?? 0)" icon="ri-shopping-cart-2-line" :accent="$acc" :hint="$m($counts[$k]->amt ?? 0)" /></a></div>
        @endforeach
    </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="Requests" icon="ri-list-check-2" :count="$requests->total()" :flush="true">
                <form class="cb-toolbar gap-2" method="GET">
                    <select name="status" class="cb-select" onchange="this.form.submit()"><option value="">All</option>@foreach(\App\Models\PurchaseRequest::STATUS as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
                    @if($seeAll)<select name="scope" class="cb-select" onchange="this.form.submit()"><option value="">Everyone</option><option value="mine" @selected(request('scope') === 'mine')>Mine</option></select>@endif
                </form>
                @if($requests->isEmpty())
                    <div class="empty-state"><i class="ri-shopping-cart-2-line"></i><h6>No requests</h6><p>Use the form to request items.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Request</th><th>By</th><th class="text-end">Estimate</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($requests as $r) @php [$l, $c] = $r->label(); @endphp
                            <tr><td><strong>{{ $r->title }}</strong><div class="small text-muted">{{ $r->pr_no }} · {{ count($r->items) }} item(s){{ $r->needed_by ? ' · needed ' . $r->needed_by->format('d M') : '' }}</div></td>
                                <td class="small">{{ $r->requester->name ?? '—' }}<div class="text-muted">{{ $r->department }}</div></td>
                                <td class="text-end fw-bold">{{ $m($r->estimated_total) }}</td>
                                <td><span class="status-pill {{ $c }}">{{ $l }}</span></td>
                                <td class="text-end"><a href="{{ route('finance.purchases.show', $r) }}" class="action-btn btn-go"><i class="ri-eye-line"></i>Open</a></td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                    <div class="p-3">{{ $requests->links() }}</div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-5">
            <x-cb.card title="New request" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('finance.purchases.store') }}" id="prForm">@csrf
                    <input type="text" name="title" class="form-control mb-2" placeholder="What is it for? e.g. Chemistry lab reagents, 2nd term" required maxlength="180">
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input type="text" name="department" class="form-control form-control-sm" placeholder="Department"></div>
                        <div class="col-6"><input type="date" name="needed_by" class="form-control form-control-sm" min="{{ now()->toDateString() }}" title="Needed by"></div>
                        <div class="col-6"><select name="expense_category_id" class="form-select form-select-sm"><option value="">Category</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                        <div class="col-6"><select name="vendor_id" class="form-select form-select-sm"><option value="">Suggested vendor</option>@foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
                    </div>
                    <table class="table table-sm mb-1"><thead><tr><th>Item</th><th style="width:70px">Qty</th><th style="width:110px">Unit ₦</th><th></th></tr></thead><tbody id="items"></tbody></table>
                    <div class="d-flex justify-content-between align-items-center mb-2"><button type="button" class="btn btn-sm btn-light" onclick="addRow()"><i class="ri-add-line"></i> Item</button><b id="prTotal">₦0.00</b></div>
                    <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Why it's needed (optional)"></textarea>
                    <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-send-plane-line"></i>Submit request</button>
                </form>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
<script>
let n = 0;
function addRow() {
    const i = n++, tr = document.createElement('tr');
    tr.innerHTML = `<td><input name="items[${i}][description]" class="form-control form-control-sm" required></td><td><input name="items[${i}][qty]" type="number" step="0.01" min="0.01" value="1" class="form-control form-control-sm" required></td><td><input name="items[${i}][unit_price]" type="number" step="0.01" min="0" class="form-control form-control-sm" required></td><td><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove();tot()">&times;</button></td>`;
    document.getElementById('items').appendChild(tr);
}
function tot() {
    let t = 0; document.querySelectorAll('#items tr').forEach(r => { const q = r.querySelector('[name$="[qty]"]').value || 0, p = r.querySelector('[name$="[unit_price]"]').value || 0; t += q * p; });
    document.getElementById('prTotal').textContent = '₦' + t.toLocaleString('en-NG', {minimumFractionDigits: 2});
}
document.getElementById('prForm').addEventListener('input', tot);
addRow();
</script>
@endsection
