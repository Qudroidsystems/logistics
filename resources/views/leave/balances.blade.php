{{-- resources/views/leave/balances.blade.php --}}
@extends('layouts.master')

@section('content')
@php $q = fn($extra) => array_merge(request()->query(), $extra); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Leave Balances" icon="ri-scales-3-line" subtitle="Every staff member's entitlement, days taken and days remaining for {{ $year }}." :back="route('leave.records')" back-label="Records">
        <x-slot name="actions">
            <a href="{{ route('leave.board') }}" class="action-btn btn-go"><i class="ri-team-line"></i>Who's Away</a>
        </x-slot>
    </x-cb.hero>

    @if($carryTypes)
    <div class="cb-banner info">
        <i class="ri-information-line"></i>
        <div>
            <strong>Carry-over:</strong> {{ implode(', ', $carryTypes) }} allow unused days to roll into the next year.
            An administrator runs <code>php artisan leave:carry-over</code> at the start of a new year (add <code>--dry-run</code> to preview).
            Carried-over days show up as an adjustment inside each staff member's entitlement below.
        </div>
    </div>
    @endif

    <x-cb.card title="Filter" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">Search staff</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Name"></div>
            <div class="col-md-3"><label class="form-label small">Department</label>
                <select name="department" class="form-select"><option value="">All departments</option>
                    @foreach($departments as $d)<option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">Year</label>
                <select name="year" class="form-select">
                    @for($y = now()->year + 1; $y >= now()->year - 4; $y--)<option value="{{ $y }}" @selected($year===$y)>{{ $y }}</option>@endfor</select></div>
            <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i> Apply</button></div>
        </form>
    </x-cb.card>

    <x-cb.card title="Balances ({{ $year }})" icon="ri-scales-3-line" :count="$staff->total()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-user-search-line"></i><h6>No staff</h6><p>No staff match these filters.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0 cb-bal-table">
                <thead><tr>
                    <th style="min-width:180px">Staff</th>
                    @foreach($types as $t)
                        <th class="text-center" style="min-width:96px">
                            <span class="cb-dot" style="background: {{ $t->color ?: '#0f766e' }}"></span> {{ $t->name }}
                            <div class="small text-muted fw-normal">{{ rtrim(rtrim(number_format($t->days_per_year,1),'0'),'.') }} d/yr</div>
                        </th>
                    @endforeach
                </tr></thead>
                <tbody>
                @foreach($rows as $st)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $st->name }}</div>
                            @if($st->department)<div class="small text-muted">{{ $st->department }}</div>@endif
                        </td>
                        @foreach($types as $t)
                            @php
                                $b = $st->balances->get($t->id);
                                $rem = $b && $b->remaining !== null ? $b->remaining : null;
                                $ent = $b ? (float) $b->entitled : (float) $t->days_per_year;
                                $used = $b ? (float) $b->approved : 0;
                                $pct = $ent > 0 ? max(0, min(100, round(($used / $ent) * 100))) : 0;
                                $low = $rem !== null && $ent > 0 && $rem <= $ent * 0.15;
                                $carried = $b ? (float) $b->entitled - (float) $t->days_per_year : 0;
                            @endphp
                            <td class="text-center">
                                @if(!$b)
                                    <span class="text-muted small">—</span>
                                @else
                                    <div class="fw-semibold {{ $low ? 'text-danger' : '' }}">{{ rtrim(rtrim(number_format($rem ?? 0,1),'0'),'.') }}<span class="text-muted small"> / {{ rtrim(rtrim(number_format($ent,1),'0'),'.') }}</span></div>
                                    <div class="progress cb-bal-bar"><div class="progress-bar {{ $low ? 'bg-danger' : '' }}" style="width: {{ $pct }}%"></div></div>
                                    @if($b->pending > 0)<div class="small text-warning" title="Pending approval">+{{ rtrim(rtrim(number_format($b->pending,1),'0'),'.') }} pending</div>@endif
                                    @if($carried > 0.01)<div class="small text-success" title="Carried over / adjusted">incl. +{{ rtrim(rtrim(number_format($carried,1),'0'),'.') }}</div>@endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>

    @if($staff->hasPages())<div class="mt-3">{{ $staff->links() }}</div>@endif
</div>
</div>
</div>

@once
<style>
.cb-bal-table th{font-size:.78rem;vertical-align:middle}
.cb-bal-bar{height:5px;border-radius:4px;margin-top:3px;background:#eef2f7}
.cb-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:2px}
</style>
@endonce
@endsection
