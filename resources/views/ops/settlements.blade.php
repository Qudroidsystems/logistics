@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Settlements" icon="ri-bank-line" subtitle="Weekly statements for each provider: sales, commission and what is paid out." :back="route('ops.dashboard')" back-label="Operations">
        <x-slot:actions>
            @can('Run settlement')
            <form method="POST" action="{{ route('ops.settlements.run') }}" onsubmit="return confirm('Build draft statements for last week?')">@csrf<button class="action-btn btn-primary-cb"><i class="ri-play-circle-line"></i>Build last week</button></form>
            @endcan
        </x-slot:actions>
    </x-cb.hero>
    @include('ops._flash')
    <x-cb.card title="Statements" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach(['draft', 'approved', 'paid'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select></form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Provider</th><th>Period</th><th class="text-end">Gross</th><th class="text-end">Commission</th><th class="text-end">Adjustments</th><th class="text-end">Net</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['provider'] }}</td>
                    <td class="small">{{ $r['period_start'] }} to {{ $r['period_end'] }}</td>
                    <td class="text-end">{{ $naira($r['gross']) }}</td>
                    <td class="text-end">{{ $naira($r['commission']) }}</td>
                    <td class="text-end">{{ $naira($r['adjustments']) }}</td>
                    <td class="text-end fw-semibold">{{ $naira($r['net']) }}</td>
                    <td><span class="badge bg-{{ $r['status'] === 'draft' ? 'warning' : 'success' }}">{{ $r['status'] }}</span></td>
                    <td class="text-end">@if($r['status'] === 'draft')@can('Run settlement')<form method="POST" action="{{ route('ops.settlement.approve', $r['public_id']) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>@endcan @endif</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No statements yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
