{{-- resources/views/finance/audit/dashboard.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $t = $summary['totals'];
    $monthly = $summary['monthly'];
    $cats = $summary['expense_by_cat'];
    $actors = $summary['top_actors'];
    $vol = $summary['change_volume'];
    $ec = $summary['exception_counts'];
    $naira = fn($v) => '₦' . number_format((float)$v, 0);
    // chart scale
    $maxM = 1;
    foreach ($monthly as $m) { $maxM = max($maxM, $m['income'], $m['expense']); }
    $maxCat = 1; foreach ($cats as $c) { $maxCat = max($maxCat, $c['total']); }
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Financial Audit" icon="ri-shield-check-line" subtitle="An auditor's view of the books — exceptions to review, who changed what, and income vs expenditure.">
        <x-slot name="actions">
            <a href="{{ route('finance.audit.exceptions') }}" class="action-btn btn-primary-cb"><i class="ri-error-warning-line"></i>Exceptions ({{ $ec['open'] }})</a>
            <a href="{{ route('finance.audit.trail') }}" class="action-btn btn-go"><i class="ri-history-line"></i>Audit trail</a>
            <a href="{{ route('finance.audit.users') }}" class="action-btn btn-go"><i class="ri-group-line"></i>Who did what</a>
        </x-slot>
    </x-cb.hero>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-auto"><label class="form-label small">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
        <div class="col-auto"><label class="form-label small">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
        <div class="col-auto"><button class="action-btn btn-primary-cb"><i class="ri-filter-3-line"></i>Apply</button></div>
    </form>

    <div class="row g-3 mb-1">
        <div class="col-6 col-lg-3"><x-cb.stat label="Income (period)" :value="$naira($t['income'])" icon="ri-arrow-down-circle-line" accent="success" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Expenditure (period)" :value="$naira($t['expense'])" icon="ri-arrow-up-circle-line" accent="warning" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Net" :value="$naira($t['net'])" icon="ri-scales-3-line" :accent="$t['net']>=0?'teal':'rose'" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Open exceptions" :value="$ec['open']" icon="ri-error-warning-line" :accent="$ec['open']>0?'rose':'teal'" hint="need review" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Income vs expenditure (12 months)" icon="ri-bar-chart-2-line">
                @if(empty($monthly))
                    <div class="empty-state"><i class="ri-bar-chart-line"></i><h6>No posted entries yet</h6><p>Charts appear once the ledger has posted journals.</p></div>
                @else
                    @php $bw = 100 / max(1,count($monthly)); @endphp
                    <svg viewBox="0 0 700 240" style="width:100%;height:auto" role="img" aria-label="Income vs expenditure">
                        @for($i=0;$i<count($monthly);$i++)
                            @php
                                $m=$monthly[$i]; $x=$i*(700/count($monthly));
                                $gw=(700/count($monthly)); $bwid=$gw*0.32;
                                $ih=($m['income']/$maxM)*180; $eh=($m['expense']/$maxM)*180;
                            @endphp
                            <rect x="{{ $x+$gw*0.16 }}" y="{{ 200-$ih }}" width="{{ $bwid }}" height="{{ $ih }}" fill="#16a34a" rx="2"><title>{{ $m['label'] }} income {{ $naira($m['income']) }}</title></rect>
                            <rect x="{{ $x+$gw*0.52 }}" y="{{ 200-$eh }}" width="{{ $bwid }}" height="{{ $eh }}" fill="#f59e0b" rx="2"><title>{{ $m['label'] }} expense {{ $naira($m['expense']) }}</title></rect>
                            <text x="{{ $x+$gw/2 }}" y="216" text-anchor="middle" font-size="11" fill="#94a3b8">{{ $m['label'] }}</text>
                        @endfor
                        <line x1="0" y1="200" x2="700" y2="200" stroke="#e5e7eb"/>
                    </svg>
                    <div class="d-flex gap-3 small text-muted"><span><span class="cb-dot" style="background:#16a34a"></span>Income</span><span><span class="cb-dot" style="background:#f59e0b"></span>Expenditure</span></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card title="Change volume (period)" icon="ri-edit-2-line">
                <div class="d-flex justify-content-around text-center py-2">
                    <div><div class="h4 mb-0 text-success">{{ $vol['created'] }}</div><div class="small text-muted">Created</div></div>
                    <div><div class="h4 mb-0 text-info">{{ $vol['updated'] }}</div><div class="small text-muted">Edited</div></div>
                    <div><div class="h4 mb-0 text-danger">{{ $vol['deleted'] }}</div><div class="small text-muted">Deleted</div></div>
                </div>
            </x-cb.card>
            <x-cb.card title="Exceptions by type" icon="ri-alert-line" :flush="true">
                @if(empty($ec['by_kind']))
                    <div class="empty-state"><i class="ri-checkbox-circle-line"></i><h6>All clear</h6><p>No open exceptions.</p></div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($ec['by_kind'] as $k=>$n)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="{{ route('finance.audit.exceptions', ['kind'=>$k]) }}">{{ $kinds[$k] ?? $k }}</a>
                                <span class="status-pill st-danger">{{ $n }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-cb.card>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <x-cb.card title="Expenditure by category" icon="ri-pie-chart-2-line">
                @if(empty($cats))
                    <div class="empty-state"><i class="ri-pie-chart-line"></i><h6>No expenses</h6></div>
                @else
                    @foreach($cats as $c)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small"><span>{{ $c['name'] }}</span><span class="text-muted">{{ $naira($c['total']) }}</span></div>
                            <div class="progress" style="height:7px"><div class="progress-bar" style="width: {{ round(($c['total']/$maxCat)*100) }}%;background:#b45309"></div></div>
                        </div>
                    @endforeach
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-6">
            <x-cb.card title="Most financial activity (by user)" icon="ri-user-star-line" :flush="true">
                @if(empty($actors))
                    <div class="empty-state"><i class="ri-user-line"></i><h6>No activity</h6></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>User</th><th class="text-end">Actions</th><th class="text-end">Value touched</th></tr></thead>
                        <tbody>@foreach($actors as $a)<tr><td>{{ $a['name'] }}</td><td class="text-end">{{ $a['actions'] }}</td><td class="text-end">{{ $naira($a['total']) }}</td></tr>@endforeach</tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
    </div>

    <x-cb.card title="Open exceptions to review" icon="ri-error-warning-line" :count="$openExceptions->count()" :flush="true">
        <x-slot name="tools"><a href="{{ route('finance.audit.exceptions') }}" class="action-btn btn-open">View all</a></x-slot>
        @if($openExceptions->isEmpty())
            <div class="empty-state"><i class="ri-shield-check-line"></i><h6>Nothing to review</h6><p>No open audit exceptions right now.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Type</th><th>Finding</th><th>Date</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                @foreach($openExceptions as $x)
                    <tr>
                        <td><span class="status-pill {{ $x['severity']==='high'?'st-danger':($x['severity']==='medium'?'st-pending':'st-info') }}">{{ $kinds[$x['kind']] ?? $x['kind'] }}</span></td>
                        <td>{{ $x['title'] }}<div class="small text-muted">{{ \Illuminate\Support\Str::limit($x['detail'], 90) }}</div></td>
                        <td class="small">{{ $x['date'] }}</td>
                        <td class="text-end">{{ $x['amount'] !== null ? $naira($x['amount']) : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
</div></div></div>

@once<style>.cb-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:4px}</style>@endonce
@endsection
