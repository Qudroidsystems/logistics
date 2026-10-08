@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Merchants" icon="ri-store-3-line" subtitle="Stores and apps that order deliveries through the partner API." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <x-cb.card title="Merchants" icon="ri-list-check" :count="count($rows)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Settlement</th><th class="text-end">Live keys</th><th class="text-end">Orders</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><code>{{ $r->merchant_code }}</code></td>
                    <td class="fw-semibold">{{ $r->display_name }}</td>
                    <td><span class="badge bg-light text-dark">{{ $r->status }}</span></td>
                    <td class="small">{{ $settlement[$r->settlement_mode] ?? $r->settlement_mode }}</td>
                    <td class="text-end">{{ $r->keys_active }}</td>
                    <td class="text-end">{{ $r->orders }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('ops.merchant', $r->id) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No merchants yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    @can('Create vendor')
    <x-cb.card title="Register a merchant" icon="ri-add-circle-line">
        <form method="POST" action="{{ route('ops.merchant.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6"><label class="form-label">Business or app name</label><input name="display_name" class="form-control" value="{{ old('display_name') }}" required></div>
            <div class="col-md-6"><label class="form-label">Website</label><input name="website" class="form-control" placeholder="https://" value="{{ old('website') }}"></div>
            <div class="col-md-4"><label class="form-label">Support email</label><input name="support_email" class="form-control" value="{{ old('support_email') }}"></div>
            <div class="col-md-4"><label class="form-label">Support phone</label><input name="support_phone" class="form-control" value="{{ old('support_phone') }}"></div>
            <div class="col-md-4"><label class="form-label">How they pay for deliveries</label>
                <select name="settlement_mode" class="form-select">@foreach($settlement as $k => $v)<option value="{{ $k }}" @selected(old('settlement_mode', 'pay_at_checkout') === $k)>{{ $v }}</option>@endforeach</select>
            </div>
            <div class="col-12"><button class="btn btn-primary">Register merchant</button><span class="text-muted small ms-2">They get a merchant code and can be given API keys on the next page.</span></div>
        </form>
    </x-cb.card>
    @endcan
</div></div></div>
@endsection
