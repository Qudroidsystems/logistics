{{-- resources/views/finance/assets/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); $C = \App\Models\FixedAsset::CLASSES; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Fixed Assets" icon="ri-archive-drawer-line" subtitle="Everything the school owns — where it is, who has it, what it's worth after depreciation.">
        <x-slot:actions><a href="{{ route('finance.assets.export') }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Register (Excel)</a></x-slot:actions>
        <x-slot:pills>@if($lastRun)<span class="cb-meta-pill"><i class="ri-history-line"></i>Depreciated up to {{ \Carbon\Carbon::parse($lastRun . '-01')->format('M Y') }}</span>@endif</x-slot:pills>
    </x-cb.hero>
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Assets in use" :value="$summary['count']" icon="ri-archive-drawer-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Cost" :value="$m($summary['cost'])" icon="ri-price-tag-3-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Book value" :value="$m($summary['nbv'])" icon="ri-scales-3-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Depreciation / month" :value="$m($summary['monthly'])" icon="ri-arrow-down-line" accent="rose" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Register" icon="ri-list-check-2" :count="$assets->total()" :flush="true">
                <form class="cb-toolbar gap-2 flex-wrap" method="GET">
                    <input type="search" name="q" class="cb-search" placeholder="Tag, name, serial, location" value="{{ request('q') }}">
                    <select name="class" class="cb-select" onchange="this.form.submit()"><option value="">All classes</option>@foreach($C as $k => $l)<option value="{{ $k }}" @selected(request('class') === $k)>{{ $l }}</option>@endforeach</select>
                    <select name="status" class="cb-select" onchange="this.form.submit()"><option value="">In use</option>@foreach(\App\Models\FixedAsset::STATUS as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
                </form>
                @if($assets->isEmpty())
                    <div class="empty-state"><i class="ri-archive-drawer-line"></i><h6>No assets</h6><p>Add existing items on the right; new purchases are added automatically from expense vouchers marked as equipment.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Asset</th><th>Location / custodian</th><th class="text-end">Cost</th><th class="text-end">Book value</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($assets as $a) @php [$l, $c] = $a->label(); @endphp
                            <tr><td><strong>{{ $a->name }}</strong><div class="small text-muted">{{ $a->asset_tag }} · {{ $C[$a->account_code] ?? '' }}{{ $a->serial_no ? ' · SN ' . $a->serial_no : '' }}</div></td>
                                <td class="small">{{ $a->location ?: '—' }}<div class="text-muted">{{ $names[$a->custodian_staff_id] ?? '' }}</div></td>
                                <td class="text-end">{{ $m($a->cost) }}<div class="small text-muted">{{ $a->acquisition_date->format('M Y') }}</div></td>
                                <td class="text-end fw-bold">{{ $m($a->bookValue()) }}</td>
                                <td><span class="status-pill {{ $c }}">{{ $l }}</span></td>
                                <td class="text-end"><a href="{{ route('finance.assets.show', $a) }}" class="action-btn btn-go"><i class="ri-eye-line"></i>Open</a></td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                    <div class="p-3">{{ $assets->links() }}</div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card title="By class" icon="ri-pie-chart-line">
                @foreach($summary['by'] as $code => $x)<div class="d-flex justify-content-between small mb-1"><span>{{ $x['label'] }} ({{ $x['count'] }})</span><span>{{ $m($x['nbv']) }}</span></div>@endforeach
            </x-cb.card>
            @can('Manage assets')
            <x-cb.card title="Run depreciation" icon="ri-arrow-down-circle-line">
                <form method="POST" action="{{ route('finance.assets.depreciate') }}" class="d-flex gap-2">@csrf
                    <input type="month" name="period" class="form-control form-control-sm" value="{{ now()->subMonthNoOverflow()->format('Y-m') }}" max="{{ now()->format('Y-m') }}" required>
                    <button class="action-btn btn-go">Run</button>
                </form>
                <div class="small text-muted mt-1">Runs automatically on the 1st of each month for the month before. Safe to run twice — each asset is charged once per month.</div>
            </x-cb.card>
            <x-cb.card title="Add an existing asset" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('finance.assets.store') }}" class="small">@csrf
                    <input name="name" class="form-control form-control-sm mb-2" placeholder="Name e.g. HP ProBook 450 G8" required>
                    <select name="account_code" class="form-select form-select-sm mb-2">@foreach($C as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <div class="row g-2 mb-2"><div class="col-6"><input type="date" name="acquisition_date" class="form-control form-control-sm" max="{{ now()->toDateString() }}" required title="Date bought"></div><div class="col-6"><input type="number" step="0.01" name="cost" class="form-control form-control-sm" placeholder="Cost ₦" required></div></div>
                    <div class="row g-2 mb-2"><div class="col-6"><input type="number" name="useful_life_months" class="form-control form-control-sm" placeholder="Life (months)"></div><div class="col-6"><input type="number" step="0.01" name="accumulated_depreciation" class="form-control form-control-sm" placeholder="Depreciated so far ₦"></div></div>
                    <input name="serial_no" class="form-control form-control-sm mb-2" placeholder="Serial no.">
                    <input name="location" class="form-control form-control-sm mb-2" placeholder="Location e.g. ICT Lab 2">
                    <select name="custodian_staff_id" class="form-select form-select-sm mb-2"><option value="">Custodian…</option>@foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                    <div class="form-check mb-2"><input type="hidden" name="post_opening" value="0"><input class="form-check-input" type="checkbox" name="post_opening" value="1" id="po" checked><label for="po" class="form-check-label">Record in the books as an opening balance</label></div>
                    <button class="action-btn btn-go w-100 justify-content-center">Add asset</button>
                </form>
            </x-cb.card>
            @endcan
        </div>
    </div>
</div>
</div>
</div>
@endsection
