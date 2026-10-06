{{-- resources/views/finance/cooperative/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $T = \App\Models\CoopTransaction::TYPES; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Cooperative · ' . $name" icon="ri-safe-2-line" subtitle="Savings statement" :back="route('payroll.coop')" back-label="Cooperative">
        <x-slot:actions><button type="button" class="cb-hero-btn" onclick="window.print()"><i class="ri-printer-line"></i>Print</button></x-slot:actions>
    </x-cb.hero>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><x-cb.stat label="Balance" :value="$m($balance)" icon="ri-safe-2-line" accent="green" /></div>
        <div class="col-md-4"><x-cb.stat label="Monthly" :value="$member ? $m($member->monthly_contribution) : '—'" icon="ri-calendar-line" accent="teal" /></div>
        <div class="col-md-4"><x-cb.stat label="Status" :value="$member ? ucfirst($member->status) : 'Not a member'" icon="ri-user-line" accent="amber" /></div>
    </div>
    <x-cb.card title="Transactions" icon="ri-file-list-3-line" :count="$rows->count()" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Date</th><th>Type</th><th>Details</th><th class="text-end">Amount</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr><td>{{ $r->txn_date->format('d M Y') }}</td><td>{{ $T[$r->type] ?? $r->type }}</td><td class="small">{{ $r->period->period_name ?? $r->reference }} {{ $r->note }}</td>
                    <td class="text-end {{ $r->amount < 0 ? 'text-danger' : 'text-success' }}">{{ $m($r->amount) }}</td><td class="text-end fw-bold">{{ $m($r->running) }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted p-4">No transactions yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div>
</div>
</div>
@endsection
