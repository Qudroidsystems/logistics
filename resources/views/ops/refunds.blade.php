@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Refunds" icon="ri-refund-2-line" subtitle="Money returned to customers." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')
    <x-cb.card title="Refunds" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach(['pending', 'processing', 'succeeded', 'failed'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select></form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th class="text-end">Amount</th><th>Reason</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr><td>{{ $r['order_number'] }}</td><td class="text-end">{{ $naira($r['amount']) }}</td><td>{{ str_replace('_', ' ', $r['reason_code'] ?? '') }}</td><td><span class="badge bg-light text-dark">{{ $r['status'] }}</span></td><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r['created_at'])->format('d M Y') }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No refunds.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
