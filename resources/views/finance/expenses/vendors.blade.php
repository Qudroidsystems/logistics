{{-- resources/views/finance/expenses/vendors.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); $e = $edit; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Vendors & Suppliers" icon="ri-store-2-line" subtitle="Who the school buys from, with contact and bank details." :back="route('finance.expenses')" back-label="Expenses" />
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Vendors" icon="ri-store-2-line" :count="$vendors->count()" :flush="true">
                <form class="cb-toolbar" method="GET"><input type="search" name="q" class="cb-search" placeholder="Search" value="{{ request('q') }}"></form>
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Name</th><th>Contact</th><th>Bank</th><th class="text-end">Paid to date</th><th></th></tr></thead>
                    <tbody>
                    @forelse($vendors as $vd)
                        <tr class="{{ $vd->is_active ? '' : 'text-muted' }}">
                            <td><strong>{{ $vd->name }}</strong><div class="small text-muted">{{ $vd->category }}{{ $vd->tin ? ' · TIN ' . $vd->tin : '' }}</div></td>
                            <td class="small">{{ $vd->contact_person }}<div>{{ $vd->phone }} {{ $vd->email }}</div></td>
                            <td class="small">{{ $vd->bank_name }}{{ $vd->account_number ? ' · ****' . substr($vd->account_number, -4) : '' }}</td>
                            <td class="text-end">{{ $m($vd->vouchers_sum_amount) }}<div class="small text-muted">{{ $vd->vouchers_count }} voucher(s)</div></td>
                            <td class="text-end"><a href="{{ route('finance.vendors', ['edit' => $vd->id]) }}" class="action-btn btn-open"><i class="ri-edit-line"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted p-4">No vendors yet.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card :title="$e ? 'Edit vendor' : 'Add vendor'" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('finance.vendors.save') }}" class="small">@csrf
                    @if($e)<input type="hidden" name="id" value="{{ $e->id }}">@endif
                    @foreach(['name' => 'Name *', 'category' => 'What they supply', 'contact_person' => 'Contact person', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address', 'bank_name' => 'Bank', 'account_number' => 'Account number (10 digits)', 'account_name' => 'Account name', 'tin' => 'TIN'] as $k => $l)
                        <input type="text" name="{{ $k }}" class="form-control form-control-sm mb-2" placeholder="{{ $l }}" value="{{ old($k, $e?->$k) }}" @if($k === 'name') required @endif>
                    @endforeach
                    <div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="act" @checked(old('is_active', $e?->is_active ?? true))><label for="act" class="form-check-label">Active</label></div>
                    <button class="action-btn btn-go w-100 justify-content-center"><i class="ri-save-line"></i>Save</button>
                    @if($e)<a href="{{ route('finance.vendors') }}" class="d-block text-center mt-2">Add new instead</a>@endif
                </form>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
