{{-- resources/views/finance/payroll/items.blade.php --}}
@extends('layouts.master')

@section('content')
@php $C = \App\Models\PayItem::CALCS; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Allowances & Deductions" icon="ri-list-settings-line" subtitle="Reusable pay items: give them to staff every month, for a range of months, or once (e.g. a bonus).">
        <x-slot:actions><a href="{{ route('payroll.scales') }}" class="cb-hero-btn"><i class="ri-stack-line"></i>Salary scales</a></x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="Pay items" icon="ri-list-check-2" :count="$items->count()" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Item</th><th>Type</th><th>Amount</th><th>Rules</th><th class="text-end">Staff now</th><th></th></tr></thead>
                    <tbody>
                    @foreach($items as $i)
                        <tr class="{{ $i->is_active ? '' : 'opacity-50' }}">
                            <td><strong>{{ $i->name }}</strong><div class="small text-muted">{{ $i->code }}</div></td>
                            <td><span class="status-pill {{ $i->type === 'earning' ? 'st-paid' : 'st-danger' }}">{{ ucfirst($i->type) }}</span></td>
                            <td class="small">{{ $i->calc === 'fixed' ? ($i->default_amount > 0 ? '₦' . number_format($i->default_amount, 2) : 'Set per staff') : rtrim(rtrim(number_format($i->default_rate, 4), '0'), '.') . ' ' . $C[$i->calc] }}</td>
                            <td class="small">
                                @if($i->type === 'earning')<span class="term-chip">{{ $i->taxable ? 'Taxed' : 'Not taxed' }}</span>@if($i->pensionable)<span class="term-chip">Pension</span>@endif @endif
                                @if($i->one_off)<span class="term-chip" title="Taxed as a lump sum, not ×12">One-off</span>@endif
                            </td>
                            <td class="text-end">{{ $usage[$i->id] ?? 0 }}</td>
                            <td class="text-end">@can('Manage salary structures')<button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#it{{ $i->id }}" aria-label="Edit"><i class="ri-edit-line"></i></button>@endcan</td>
                        </tr>
                        @can('Manage salary structures')
                        <tr class="collapse" id="it{{ $i->id }}"><td colspan="6" class="bg-light">
                            @include('finance.payroll.partials.item-form', ['item' => $i, 'action' => route('payroll.items.update', $i), 'method' => 'PUT'])
                        </td></tr>
                        @endcan
                    @endforeach
                    </tbody>
                </table></div>
            </x-cb.card>
            @can('Manage salary structures')
            <x-cb.card title="New pay item" icon="ri-add-line">
                @include('finance.payroll.partials.item-form', ['item' => new \App\Models\PayItem(['type' => 'earning', 'calc' => 'fixed', 'taxable' => true, 'is_active' => true]), 'action' => route('payroll.items.store'), 'method' => 'POST'])
            </x-cb.card>
            @endcan
        </div>

        <div class="col-xl-5">
            @can('Manage salary structures')
            <x-cb.card title="Give an item to staff" icon="ri-user-add-line">
                <form method="POST" action="{{ route('payroll.items.assign') }}">@csrf
                    <select name="pay_item_id" class="form-select form-select-sm mb-2" required aria-label="Item">
                        <option value="">Choose item…</option>
                        @foreach($items->where('is_active', true) as $i)<option value="{{ $i->id }}">{{ $i->name }} ({{ $i->type }})</option>@endforeach
                    </select>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input name="amount" type="number" step="0.01" min="0" class="form-control form-control-sm" placeholder="Amount ₦ (fixed items)" aria-label="Amount"></div>
                        <div class="col-6"><input name="rate" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" placeholder="Rate % (% items)" aria-label="Rate"></div>
                    </div>
                    <div class="d-flex gap-3 mb-2 small">
                        <label class="form-check"><input class="form-check-input" type="radio" name="mode" value="once" checked> <span class="form-check-label">One month</span></label>
                        <label class="form-check"><input class="form-check-input" type="radio" name="mode" value="range"> <span class="form-check-label">Range</span></label>
                        <label class="form-check"><input class="form-check-input" type="radio" name="mode" value="ongoing"> <span class="form-check-label">Every month</span></label>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="small">From</label><input name="from_month" type="month" class="form-control form-control-sm" value="{{ now()->format('Y-m') }}" required></div>
                        <div class="col-6"><label class="small">To (range)</label><input name="to_month" type="month" class="form-control form-control-sm"></div>
                    </div>
                    <input name="note" class="form-control form-control-sm mb-2" placeholder="Note shown on payslip, e.g. WAEC invigilation" maxlength="255">
                    <input type="search" class="form-control form-control-sm mb-1" placeholder="Filter staff…" id="stFilter">
                    <label class="form-check small mb-1"><input type="checkbox" class="form-check-input" id="stAll"> <span class="form-check-label">Tick all shown</span></label>
                    <div class="border rounded p-2 mb-2" style="max-height:260px;overflow:auto" id="stList">
                        @foreach($staff as $s)<label class="form-check st-row" data-q="{{ strtolower($s->name . ' ' . $s->employmentid) }}"><input class="form-check-input" type="checkbox" name="staff_ids[]" value="{{ $s->id }}"> <span class="form-check-label">{{ $s->name }} <small class="text-muted">{{ $s->employmentid }}</small></span></label>@endforeach
                    </div>
                    <button class="action-btn btn-go"><i class="ri-check-line"></i>Give to ticked staff</button>
                </form>
            </x-cb.card>
            @endcan
            <div class="small text-muted">One-off items (bonus, arrears, 13th month) are taxed once as a lump sum — only the extra tax they cause is charged that month, instead of treating them as if paid every month.</div>
        </div>
    </div>
</div>
</div>
</div>
<script>
(function () {
    const f = document.getElementById('stFilter'); if (!f) return;
    f.addEventListener('input', () => document.querySelectorAll('.st-row').forEach(r => r.style.display = r.dataset.q.includes(f.value.toLowerCase()) ? '' : 'none'));
    document.getElementById('stAll').addEventListener('change', e => document.querySelectorAll('.st-row').forEach(r => { if (r.style.display !== 'none') r.querySelector('input').checked = e.target.checked; }));
})();
</script>
@endsection
