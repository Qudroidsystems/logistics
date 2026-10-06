{{-- resources/views/finance/my-pay/cooperative.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $T = \App\Models\CoopTransaction::TYPES; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Cooperative Savings" icon="ri-safe-2-line" subtitle="Your savings with the staff cooperative, deducted monthly from salary." />
    @include('finance.my-pay._nav', ['section' => 'cooperative'])

    <div class="row g-3 mb-3">
        <div class="col-md-4"><x-cb.stat label="Savings balance" :value="$m($balance)" icon="ri-safe-2-line" accent="green" /></div>
        <div class="col-md-4"><x-cb.stat label="Monthly contribution" :value="$member ? $m($member->monthly_contribution) : 'Not a member'" icon="ri-calendar-line" accent="teal" /></div>
        <div class="col-md-4"><x-cb.stat label="Member since" :value="$member?->joined_on?->format('M Y') ?? '—'" icon="ri-user-star-line" accent="amber" :hint="$member && $member->status !== 'active' ? ucfirst($member->status) : null" /></div>
    </div>

    <x-cb.card title="Statement" icon="ri-file-list-3-line" :count="$rows->count()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-safe-2-line"></i><h6>No transactions</h6><p>{{ $member ? 'Your first contribution will appear after the next payroll.' : 'Ask the bursary or cooperative secretary to join.' }}</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Date</th><th>Type</th><th>Details</th><th class="text-end">Amount</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                @foreach($rows as $r)
                    <tr><td>{{ $r->txn_date->format('d M Y') }}</td><td>{{ $T[$r->type] ?? $r->type }}</td><td class="small">{{ $r->period->period_name ?? $r->reference }} {{ $r->note }}</td>
                        <td class="text-end {{ $r->amount < 0 ? 'text-danger' : 'text-success' }}">{{ $m($r->amount) }}</td><td class="text-end fw-bold">{{ $m($r->running) }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
@endsection
