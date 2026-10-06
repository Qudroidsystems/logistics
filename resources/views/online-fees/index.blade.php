{{-- resources/views/online-fees/index.blade.php — bursary view of online (Paystack) fee payments --}}
@extends('layouts.master')

@section('content')
@php
    $k2n  = fn ($k) => '₦' . number_format(((int) $k) / 100, 2);
    $pill = ['success' => 'st-paid', 'pending' => 'st-pending', 'failed' => 'st-danger', 'abandoned' => 'st-muted', 'amount_mismatch' => 'st-warning'];
    $canPay    = auth()->user()->can('Create online-fee-payments');
    $canVerify = auth()->user()->can('Update online-fee-payments');
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Online Fee Payments" icon="ri-secure-payment-line"
               subtitle="School fees paid online (Paystack or OPay) by students or by the bursary on their behalf.">
        <x-slot:actions>
            @if($canPay)
                <button type="button" class="cb-hero-btn" data-bs-toggle="modal" data-bs-target="#payForModal"><i class="ri-user-add-line"></i>Pay for a student</button>
            @endif
            @if(Route::has('admin.payment-gateways.index'))
                <a class="cb-hero-btn" href="{{ route('admin.payment-gateways.index') }}"><i class="ri-settings-3-line"></i>Gateway settings</a>
            @endif
        </x-slot:actions>
    </x-cb.hero>

    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach
    @if(!$gatewayReady)
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $gatewayProblem ?? 'No online gateway is active.' }} Fix this in Gateway settings (or .env) before students can pay online.</div></div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg col-md-4 col-6"><x-cb.stat label="Received today" :value="$k2n($stats['today'])" icon="ri-money-dollar-circle-line" accent="teal" /></div>
        <div class="col-lg col-md-4 col-6"><x-cb.stat label="This month" :value="$k2n($stats['month'])" icon="ri-calendar-check-line" accent="green" :hint="$stats['count'] . ' payment' . ($stats['count'] == 1 ? '' : 's')" /></div>
        <div class="col-lg col-md-4 col-6"><x-cb.stat label="Awaiting confirmation" :value="$stats['pending']" icon="ri-time-line" accent="amber" /></div>
        <div class="col-lg col-md-6 col-6"><x-cb.stat label="Needs review" :value="$stats['review']" icon="ri-error-warning-line" accent="rose" hint="Mismatched or unapplied amounts" /></div>
    </div>

    <x-cb.card title="Transactions" icon="ri-list-check" :count="$payments->total()" :flush="true">
        <form method="GET" class="cb-toolbar">
            <div class="cb-search"><i class="ri-search-line"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Reference, name or admission no" aria-label="Search">
            </div>
            <select name="status" class="cb-select" aria-label="Status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(\App\Models\OnlineFeePayment::STATUS_LABELS as $k => $v)
                    <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                @endforeach
                <option value="review" @selected(request('status') === 'review')>Needs review</option>
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="cb-select" aria-label="From">
            <input type="date" name="to" value="{{ request('to') }}" class="cb-select" aria-label="To">
            <button class="action-btn btn-go"><i class="ri-filter-3-line"></i>Filter</button>
            @if(request()->hasAny(['q', 'status', 'from', 'to']))<a href="{{ route('online-fees.index') }}" class="action-btn btn-open">Clear</a>@endif
        </form>

        @if($payments->isEmpty())
            <div class="empty-state"><i class="ri-secure-payment-line"></i><h6>No online payments</h6><p>Payments made through Paystack or OPay will appear here.</p></div>
        @else
            <div class="table-responsive">
                <table class="cb-table mb-0">
                    <thead><tr>
                        <th>Date</th><th>Student</th><th>Reference</th><th>Paid by</th>
                        <th class="text-end">Amount</th><th>Status</th><th class="text-end">Actions</th>
                    </tr></thead>
                    <tbody>
                    @foreach($payments as $p)
                        <tr>
                            <td>{{ ($p->paid_at ?? $p->created_at)->format('d M Y') }}<br><small class="text-muted">{{ ($p->paid_at ?? $p->created_at)->format('g:i a') }}</small></td>
                            <td><div class="fw-semibold">{{ trim($p->lastname . ' ' . $p->firstname) }}</div><small class="text-muted">{{ $p->admissionNo }}</small></td>
                            <td><code>{{ $p->reference }}</code>@if($p->mode !== 'live')<br><small class="text-muted">test mode</small>@endif</td>
                            <td>{{ ucfirst($p->payer_type) }}<br><small class="text-muted">{{ $p->channel ? ucwords(str_replace('_', ' ', $p->channel)) : '' }}</small></td>
                            <td class="text-end fw-semibold">{{ $k2n($p->amount_kobo) }}
                                @if($p->arrears_kobo > 0)<br><small class="text-muted">incl. {{ $k2n($p->arrears_kobo) }} arrears</small>@endif</td>
                            <td>
                                <span class="status-pill {{ $pill[$p->status] ?? 'st-muted' }}">{{ $p->statusLabel() }}</span>
                                @if($p->needs_review && $p->status !== 'amount_mismatch')<br><span class="status-pill st-warning mt-1">Review</span>@endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('online-fees.show', $p->reference) }}" class="action-btn btn-open"><i class="ri-eye-line"></i>View</a>
                                @if($canVerify && !$p->posted_at && in_array($p->status, ['pending', 'abandoned', 'failed']))
                                    <form method="POST" action="{{ route('online-fees.verify', $p->reference) }}" class="d-inline">@csrf
                                        <button class="action-btn btn-go" title="Ask the gateway for the latest status"><i class="ri-refresh-line"></i>Re-check</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $payments->links() }}</div>
        @endif
    </x-cb.card>

</div>
</div>
</div>

@if($canPay)
<div class="modal fade" id="payForModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="ri-user-search-line me-2"></i>Pay for a student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <input type="search" id="pfSearch" class="form-control" placeholder="Type a name or admission number" autocomplete="off">
                <div id="pfResults" class="list-group mt-2" style="max-height:320px;overflow:auto"></div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const url = @json(route('online-fees.students'));
    const box = document.getElementById('pfResults');
    let t;
    document.getElementById('pfSearch').addEventListener('input', function () {
        clearTimeout(t);
        const q = this.value.trim();
        if (q.length < 2) { box.innerHTML = ''; return; }
        t = setTimeout(async () => {
            const res = await fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
            const rows = await res.json().catch(() => []);
            box.innerHTML = '';
            if (!rows.length) { box.innerHTML = '<div class="list-group-item text-muted">No student found</div>'; return; }
            rows.forEach(r => {
                const a = document.createElement('a');
                a.className = 'list-group-item list-group-item-action';
                a.href = r.url;
                const n = document.createElement('div'); n.className = 'fw-semibold'; n.textContent = r.name;
                const s = document.createElement('small'); s.className = 'text-muted'; s.textContent = [r.adm, r.class].filter(Boolean).join(' · ');
                a.append(n, s);
                box.appendChild(a);
            });
        }, 300);
    });
})();
</script>
@endif
@endsection
