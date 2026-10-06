{{-- resources/views/finance/assets/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); [$lbl, $cls] = $a->label(); $off = in_array($a->status, ['disposed', 'lost']); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$a->name" icon="ri-archive-drawer-line" :subtitle="$a->asset_tag . ' · ' . (\App\Models\FixedAsset::CLASSES[$a->account_code] ?? '')" :back="route('finance.assets')" back-label="Assets">
        <x-slot:actions><button type="button" class="cb-hero-btn" onclick="window.print()"><i class="ri-printer-line"></i>Print</button></x-slot:actions>
        <x-slot:pills><span class="cb-meta-pill"><span class="status-pill {{ $cls }}">{{ $lbl }}</span></span>@if($a->expense_voucher_id)<span class="cb-meta-pill"><a class="text-reset" href="{{ route('finance.expenses.show', $a->expense_voucher_id) }}"><i class="ri-receipt-line"></i>Purchase voucher</a></span>@endif</x-slot:pills>
    </x-cb.hero>
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Cost" :value="$m($a->cost)" icon="ri-price-tag-3-line" accent="teal" :hint="'Bought ' . $a->acquisition_date->format('d M Y')" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Depreciated" :value="$m($a->accumulated_depreciation)" icon="ri-arrow-down-line" accent="rose" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Book value" :value="$m($a->bookValue())" icon="ri-scales-3-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Per month" :value="$m($a->monthlyDepreciation())" icon="ri-calendar-line" accent="amber" :hint="$a->useful_life_months . '-month life'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="Depreciation history" icon="ri-history-line" :count="$a->depreciations->count()" :flush="true">
                <div class="table-responsive" style="max-height:420px"><table class="table table-sm mb-0"><thead><tr><th>Month</th><th class="text-end">Charge</th></tr></thead>
                    <tbody>@forelse($a->depreciations as $d)<tr><td>{{ \Carbon\Carbon::parse($d->period . '-01')->format('M Y') }}</td><td class="text-end">{{ $m($d->amount) }}</td></tr>@empty<tr><td colspan="2" class="text-muted text-center p-3">No depreciation yet.</td></tr>@endforelse</tbody>
                </table></div>
            </x-cb.card>
            @if($off)<div class="cb-banner warning"><i class="ri-delete-bin-line"></i><div>{{ $lbl }} on {{ $a->disposal_date?->format('d M Y') }}{{ $a->disposal_amount ? ' for ' . $m($a->disposal_amount) : '' }} — {{ $a->disposal_note }}</div></div>@endif
        </div>
        <div class="col-xl-5">
            @can('Manage assets')
            @if(!$off)
            <x-cb.card title="Details" icon="ri-edit-line">
                <form method="POST" action="{{ route('finance.assets.update', $a) }}" class="small">@csrf @method('PUT')
                    <input name="name" value="{{ $a->name }}" class="form-control form-control-sm mb-2" required>
                    <input name="serial_no" value="{{ $a->serial_no }}" class="form-control form-control-sm mb-2" placeholder="Serial no.">
                    <input name="location" value="{{ $a->location }}" class="form-control form-control-sm mb-2" placeholder="Location">
                    <select name="custodian_staff_id" class="form-select form-select-sm mb-2"><option value="">No custodian</option>@foreach($staff as $s)<option value="{{ $s->id }}" @selected($a->custodian_staff_id == $s->id)>{{ $s->name }}</option>@endforeach</select>
                    <select name="status" class="form-select form-select-sm mb-2"><option value="active" @selected($a->status === 'active')>In use</option><option value="under_repair" @selected($a->status === 'under_repair')>Under repair</option></select>
                    <textarea name="description" class="form-control form-control-sm mb-2" rows="2" placeholder="Notes">{{ $a->description }}</textarea>
                    <button class="action-btn btn-go w-100 justify-content-center">Save</button>
                </form>
            </x-cb.card>
            <x-cb.card title="Dispose / write off" icon="ri-delete-bin-line">
                <form method="POST" action="{{ route('finance.assets.dispose', $a) }}" class="small" onsubmit="return confirm('Take this asset off the register?')">@csrf
                    <select name="status" class="form-select form-select-sm mb-2"><option value="disposed">Sold / scrapped</option><option value="lost">Lost / stolen</option></select>
                    <div class="row g-2 mb-2"><div class="col-6"><input type="date" name="disposal_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></div><div class="col-6"><input type="number" step="0.01" min="0" name="disposal_amount" class="form-control form-control-sm" placeholder="Sold for ₦"></div></div>
                    <input name="disposal_note" class="form-control form-control-sm mb-2" placeholder="Reason / buyer" required>
                    <button class="action-btn btn-open w-100 justify-content-center">Take off register</button>
                </form>
            </x-cb.card>
            @endif
            @else
            <x-cb.card title="Details" icon="ri-information-line"><div class="small">Location: {{ $a->location ?: '—' }}<br>Custodian: {{ $custodian ?: '—' }}<br>Serial: {{ $a->serial_no ?: '—' }}<br>Vendor: {{ $a->vendor->name ?? '—' }}</div></x-cb.card>
            @endcan
        </div>
    </div>
</div>
</div>
</div>
@endsection
