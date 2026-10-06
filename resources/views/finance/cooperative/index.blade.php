{{-- resources/views/finance/cooperative/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $T = \App\Models\CoopTransaction::TYPES; $canManage = auth()->user()->can('Manage cooperative'); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Staff Cooperative" icon="ri-safe-2-line" subtitle="Monthly savings deducted through payroll, withdrawals, dividends and cooperative loans.">
        <x-slot:actions><a href="{{ route('payroll.loans', ['type' => 'cooperative']) }}" class="cb-hero-btn"><i class="ri-hand-coin-line"></i>Cooperative loans</a></x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Total savings" :value="$m($stats['fund'])" icon="ri-safe-2-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Active members" :value="$stats['members']" icon="ri-group-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Monthly inflow" :value="$m($stats['monthly'])" icon="ri-calendar-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Lent out" :value="$m($stats['coop_loans'])" icon="ri-hand-coin-line" accent="rose" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Members" icon="ri-group-line" :count="$members->count()" :flush="true">
                <form class="cb-toolbar gap-2" method="GET">
                    <input type="search" name="q" class="cb-search" placeholder="Search name" value="{{ request('q') }}">
                    <select name="status" class="cb-select" onchange="this.form.submit()"><option value="">All</option>@foreach(['active' => 'Active', 'suspended' => 'Suspended', 'left' => 'Left'] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
                </form>
                @if($members->isEmpty())
                    <div class="empty-state"><i class="ri-group-line"></i><h6>No members yet</h6><p>Add staff on the right; their monthly savings are deducted from the next payroll.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Member</th><th class="text-end">Monthly</th><th class="text-end">Saved</th><th class="text-end">Balance</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($members as $mb) @php $b = $balances[$mb->staff_id] ?? null; @endphp
                            <tr>
                                <td><strong>{{ $mb->staff_name }}</strong><div class="small text-muted">since {{ $mb->joined_on?->format('M Y') }}</div></td>
                                <td class="text-end">
                                    @if($canManage)
                                        <form method="POST" action="{{ route('payroll.coop.member') }}" class="d-inline-flex gap-1">@csrf<input type="hidden" name="staff_id" value="{{ $mb->staff_id }}"><input type="number" step="0.01" min="0" name="monthly_contribution" value="{{ $mb->monthly_contribution }}" class="form-control form-control-sm text-end" style="width:110px"><button class="action-btn btn-open" title="Save"><i class="ri-save-line"></i></button></form>
                                    @else {{ $m($mb->monthly_contribution) }} @endif
                                </td>
                                <td class="text-end">{{ $m($b->saved ?? 0) }}</td>
                                <td class="text-end fw-bold">{{ $m($b->bal ?? 0) }}</td>
                                <td>
                                    @if($canManage)
                                        <form method="POST" action="{{ route('payroll.coop.status', $mb->id) }}">@csrf<select name="status" class="cb-select" onchange="this.form.submit()">@foreach(['active' => 'Active', 'suspended' => 'Suspended', 'left' => 'Left'] as $k => $l)<option value="{{ $k }}" @selected($mb->status === $k)>{{ $l }}</option>@endforeach</select></form>
                                    @else <span class="status-pill {{ $mb->status === 'active' ? 'st-paid' : 'st-muted' }}">{{ ucfirst($mb->status) }}</span> @endif
                                </td>
                                <td class="text-end"><a href="{{ route('payroll.coop.show', $mb->staff_id) }}" class="action-btn btn-go"><i class="ri-file-list-3-line"></i>Statement</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            @if($canManage)
            <x-cb.card title="Add member" icon="ri-user-add-line">
                <form method="POST" action="{{ route('payroll.coop.member') }}" class="small">@csrf
                    <select name="staff_id" class="form-select form-select-sm mb-2" required><option value="">Staff member…</option>@foreach($nonMembers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                    <input type="number" step="0.01" min="0" name="monthly_contribution" class="form-control form-control-sm mb-2" placeholder="Monthly savings ₦" required>
                    <input type="number" step="0.01" min="0" name="opening_balance" class="form-control form-control-sm mb-2" placeholder="Opening balance ₦ (existing savings)">
                    <button class="action-btn btn-go w-100 justify-content-center"><i class="ri-user-add-line"></i>Add</button>
                </form>
            </x-cb.card>
            <x-cb.card title="Withdrawal / adjustment" icon="ri-exchange-funds-line">
                <form method="POST" action="{{ route('payroll.coop.transaction') }}" class="small">@csrf
                    <select name="staff_id" class="form-select form-select-sm mb-2" required><option value="">Member…</option>@foreach($members as $mb)<option value="{{ $mb->staff_id }}">{{ $mb->staff_name }}</option>@endforeach</select>
                    <div class="row g-2 mb-2"><div class="col-6"><select name="type" class="form-select form-select-sm"><option value="withdrawal">Withdrawal</option><option value="contribution">Extra saving</option><option value="adjustment">Adjustment (±)</option></select></div><div class="col-6"><input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required></div></div>
                    <input type="date" name="txn_date" class="form-control form-control-sm mb-2" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="Note / reason" required>
                    <button class="action-btn btn-open w-100 justify-content-center">Record</button>
                </form>
            </x-cb.card>
            <x-cb.card title="Share a dividend" icon="ri-gift-line">
                <form method="POST" action="{{ route('payroll.coop.dividend') }}" class="small" onsubmit="return confirm('Share this amount across all members by savings balance?')">@csrf
                    <input type="number" step="0.01" min="1" name="pool" class="form-control form-control-sm mb-2" placeholder="Total to share ₦" required>
                    <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="e.g. 2026 year-end dividend">
                    <button class="action-btn btn-open w-100 justify-content-center">Share by balance</button>
                </form>
            </x-cb.card>
            @endif
            <x-cb.card title="Recent" icon="ri-history-line" :flush="true">
                <ul class="list-group list-group-flush small">
                    @forelse($recent as $r)<li class="list-group-item d-flex justify-content-between"><span>{{ $names[$r->staff_id] ?? '—' }}<div class="text-muted">{{ $T[$r->type] ?? $r->type }} · {{ $r->txn_date->format('d M') }}</div></span><span class="{{ $r->amount < 0 ? 'text-danger' : 'text-success' }}">{{ $m($r->amount) }}</span></li>@empty<li class="list-group-item text-muted">Nothing yet.</li>@endforelse
                </ul>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
