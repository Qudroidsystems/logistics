{{-- resources/views/finance/expenses/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $chg = $stats['last_month'] > 0 ? round(($stats['month'] - $stats['last_month']) / $stats['last_month'] * 100) : null; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Expenses" icon="ri-receipt-line" subtitle="Payment vouchers with receipts, approvals and budget checks — every payment posts to the ledger.">
        <x-slot:actions>
            @can('Create expenses')<a href="{{ route('finance.expenses.create') }}" class="cb-hero-btn"><i class="ri-add-line"></i>New expense</a>@endcan
            <a href="{{ route('finance.expenses.export', request()->query()) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Excel</a>
            @can('Create expenses')<a href="{{ route('finance.vendors') }}" class="cb-hero-btn"><i class="ri-store-2-line"></i>Vendors</a>@endcan
            @canany(['Manage chart of accounts', 'Manage budgets'])<a href="{{ route('finance.expense-categories') }}" class="cb-hero-btn"><i class="ri-price-tag-3-line"></i>Categories</a>@endcanany
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Spent this month" :value="$m($stats['month'])" icon="ri-money-dollar-circle-line" accent="teal" :hint="$chg !== null ? ($chg >= 0 ? '+' : '') . $chg . '% vs last month' : null" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Awaiting approval" :value="$stats['awaiting']" icon="ri-hourglass-line" :accent="$stats['awaiting'] ? 'amber' : 'green'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Approved, to pay" :value="$m($stats['to_pay'])" icon="ri-send-plane-line" accent="rose" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Last month" :value="$m($stats['last_month'])" icon="ri-history-line" accent="green" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <x-cb.card title="Vouchers" icon="ri-file-list-3-line" :count="$vouchers->total()" :flush="true">
                <form class="cb-toolbar gap-2 flex-wrap" method="GET">
                    <input type="search" name="q" class="cb-search" placeholder="Voucher, payee, description" value="{{ request('q') }}">
                    <select name="status" class="cb-select" onchange="this.form.submit()"><option value="">All</option><option value="mine" @selected(request('status') === 'mine')>Raised by me</option>@foreach(\App\Models\ExpenseVoucher::STATUS as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
                    <select name="category" class="cb-select" onchange="this.form.submit()"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                    <input type="month" name="month" class="form-control form-control-sm" style="width:150px" value="{{ request('month') }}" onchange="this.form.submit()">
                </form>
                @if($vouchers->isEmpty())
                    <div class="empty-state"><i class="ri-receipt-line"></i><h6>No expenses found</h6><p>Raise a voucher for anything the school pays for.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Voucher</th><th>Payee</th><th>Category</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($vouchers as $v) @php [$l, $c] = $v->label(); @endphp
                            <tr>
                                <td><strong>{{ $v->voucher_no }}</strong><div class="small text-muted">{{ $v->expense_date->format('d M Y') }} · {{ $v->requester->name ?? '—' }}</div></td>
                                <td>{{ $v->payee_name }}<div class="small text-muted">{{ \Illuminate\Support\Str::limit($v->description, 60) }}</div></td>
                                <td class="small">{{ $v->category->name ?? '—' }}@if($v->capitalise)<div><span class="status-pill st-info">asset</span></div>@endif</td>
                                <td class="text-end fw-bold">{{ $m($v->amount) }}@if($v->receipt_path)<div><i class="ri-attachment-2 text-muted" title="Receipt attached"></i></div>@endif</td>
                                <td><span class="status-pill {{ $c }}">{{ $l }}</span>@if($v->status === 'submitted' && $v->needs_second_approval)<div class="small text-muted">{{ $v->approved_by ? '1 of 2 approvals' : 'needs 2 approvals' }}</div>@endif</td>
                                <td class="text-end"><a href="{{ route('finance.expenses.show', $v) }}" class="action-btn btn-go"><i class="ri-eye-line"></i>Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                    <div class="p-3">{{ $vouchers->links() }}</div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-3">
            <x-cb.card title="Top spending · {{ now()->format('M') }}" icon="ri-pie-chart-line">
                @php $top = max(1, (float) $byCat->max()); @endphp
                @forelse($byCat as $cid => $amt)
                    <div class="d-flex justify-content-between small mb-1"><span>{{ $catNames[$cid] ?? 'Uncategorised' }}</span><span>{{ $m($amt) }}</span></div>
                    <div class="progress-track mb-2"><div class="progress-fill" style="width:{{ round($amt / $top * 100) }}%"></div></div>
                @empty
                    <div class="small text-muted">Nothing paid yet this month.</div>
                @endforelse
            </x-cb.card>
            <div class="d-grid gap-2">
                <a href="{{ route('finance.purchases') }}" class="action-btn btn-open justify-content-center"><i class="ri-shopping-cart-2-line"></i>Purchase requests</a>
                @canany(['Manage budgets', 'View financial reports'])<a href="{{ route('finance.budgets') }}" class="action-btn btn-open justify-content-center"><i class="ri-scales-3-line"></i>Budget vs actual</a>@endcanany
                @canany(['Manage assets', 'View financial reports'])<a href="{{ route('finance.assets') }}" class="action-btn btn-open justify-content-center"><i class="ri-archive-drawer-line"></i>Asset register</a>@endcanany
            </div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
