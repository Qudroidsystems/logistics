{{-- resources/views/finance/audit/exceptions.blade.php --}}
@extends('layouts.master')

@section('content')
@php $naira = fn($v) => $v!==null ? '₦'.number_format((float)$v,2) : '—';
     $sevPill = fn($s) => $s==='high'?'st-danger':($s==='medium'?'st-pending':'st-info');
     $canClear = auth()->user()->can('Clear audit exceptions');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Audit Exceptions" icon="ri-error-warning-line" subtitle="Findings an auditor should review: imbalances, closed-period edits, deletions, duplicates, large or self-approved transactions." :back="route('finance.audit.dashboard')" back-label="Audit" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ route('finance.audit.exceptions', array_merge(request()->except('kind'))) }}" class="action-btn {{ !$activeKind?'btn-primary-cb':'btn-open' }}">All</a>
        @foreach($kinds as $k=>$label)
            <a href="{{ route('finance.audit.exceptions', array_merge(request()->query(), ['kind'=>$k])) }}" class="action-btn {{ $activeKind===$k?'btn-primary-cb':'btn-open' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('finance.audit.exceptions', array_merge(request()->query(), ['include_cleared'=>$includeCleared?0:1])) }}" class="action-btn btn-open ms-auto">
            <i class="ri-{{ $includeCleared?'eye-off':'eye' }}-line"></i>{{ $includeCleared?'Hide cleared':'Show cleared' }}
        </a>
    </div>

    @if($groups->isEmpty())
        <x-cb.card title="All clear" icon="ri-shield-check-line">
            <div class="empty-state"><i class="ri-shield-check-line"></i><h6>No exceptions</h6><p>Nothing needs your review with the current filter.</p></div>
        </x-cb.card>
    @else
        @foreach($groups as $kind => $items)
            <x-cb.card :title="$kinds[$kind] ?? $kind" icon="ri-alert-line" :count="$items->count()" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Finding</th><th>Date</th><th class="text-end">Amount</th><th>Status</th>@if($canClear)<th class="text-end">Review</th>@endif</tr></thead>
                    <tbody>
                    @foreach($items as $x)
                        <tr>
                            <td><span class="cb-dot" style="background: {{ $x['severity']==='high'?'#dc2626':($x['severity']==='medium'?'#d97706':'#0ea5e9') }}"></span>{{ $x['title'] }}
                                <div class="small text-muted">{{ $x['detail'] }}</div>
                                @if(!empty($x['review_note']))<div class="small text-info"><i class="ri-chat-1-line"></i> {{ $x['review_note'] }}</div>@endif</td>
                            <td class="small">{{ $x['date'] }}</td>
                            <td class="text-end">{{ $naira($x['amount']) }}</td>
                            <td>@php $st=\App\Models\FinancialAuditReview::STATUS[$x['status']] ?? [$x['status'],'st-muted']; @endphp<span class="status-pill {{ $st[1] }}">{{ $st[0] }}</span></td>
                            @if($canClear)
                            <td class="text-end">
                                <form method="POST" action="{{ route('finance.audit.exceptions.review') }}" class="d-inline-flex gap-1 align-items-center">@csrf
                                    <input type="hidden" name="kind" value="{{ $x['kind'] }}">
                                    <input type="hidden" name="key" value="{{ $x['key'] }}">
                                    <input name="note" class="form-control form-control-sm" style="width:150px" placeholder="Note (optional)" value="{{ $x['review_note'] }}">
                                    <button name="status" value="cleared" class="action-btn btn-open" title="Mark reviewed/cleared"><i class="ri-check-line"></i></button>
                                    <button name="status" value="flagged" class="action-btn btn-open" title="Flag"><i class="ri-flag-line"></i></button>
                                </form>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </x-cb.card>
        @endforeach
    @endif
</div></div></div>
@once<style>.cb-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:4px}</style>@endonce
@endsection
