@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => Money::symbol().number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content" data-live-page><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Payouts" icon="ri-bank-card-line" subtitle="Providers (and customers) withdrawing their wallet to a bank account. Approve to send the money; reject to put it back in their wallet." :back="route('dashboard')" back-label="Overview" />
    @include('ops._flash')

    <ul class="nav nav-pills mb-3 flex-wrap gap-1">
        @foreach($statuses as $k => $label)
            <li class="nav-item"><a class="nav-link {{ $status === $k ? 'active' : '' }}" href="{{ route('ops.payouts', ['status' => $k]) }}">{{ $label }}
                @if(isset($counts[$k]))<span class="badge bg-{{ $status === $k ? 'light text-dark' : 'secondary' }} ms-1">{{ $counts[$k]->n }}</span>@endif</a></li>
        @endforeach
    </ul>

    <x-cb.card :title="$statuses[$status].(isset($counts[$status]) ? ' · '.$naira($counts[$status]->total) : '')" icon="ri-list-check-2" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Who</th><th>Bank account</th><th class="text-end">Amount</th><th>Asked</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>
                        @if($r->provider)
                            @if(Route::has('ops.provider'))<a href="{{ route('ops.provider', $r->provider_public_id) }}" class="fw-semibold">{{ $r->provider }}</a>@else<span class="fw-semibold">{{ $r->provider }}</span>@endif
                            <div class="small text-muted">Provider</div>
                        @else
                            <span class="fw-semibold">{{ $r->account_name }}</span><div class="small text-muted">Customer wallet</div>
                        @endif
                    </td>
                    <td class="small">{{ $r->bank_name ?? '—' }} ····{{ $r->account_number_last4 }}<div class="text-muted">{{ $r->account_name }}</div></td>
                    <td class="text-end fw-semibold">{{ $naira($r->amount) }}@if($r->fee)<div class="small text-muted">fee {{ $naira($r->fee) }}</div>@endif</td>
                    <td class="small">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M H:i') }}@if($r->approved_by_name)<div class="text-muted">by {{ $r->approved_by_name }}</div>@endif @if($r->failure_reason)<div class="text-danger">{{ $r->failure_reason }}</div>@endif</td>
                    <td class="text-end text-nowrap">
                        @if($r->status === 'requested' && auth()->user()->can('Approve withdrawal'))
                            <form method="POST" action="{{ route('ops.payout.approve', $r->id) }}" class="d-inline" onsubmit="return confirm('Send {{ $naira($r->amount) }} to this bank account?')">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                            <form method="POST" action="{{ route('ops.payout.reject', $r->id) }}" class="d-inline" onsubmit="var x=prompt('Why are you rejecting this payout? They will see this.'); if(!x){return false;} this.querySelector('[name=reason]').value=x;">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                        @else
                            <span class="badge bg-{{ in_array($r->status, ['paid']) ? 'success' : (in_array($r->status, ['failed', 'rejected']) ? 'danger' : 'info') }}">{{ $statuses[$r->status] ?? $r->status }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Nothing here.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@include('partials.live-forms')
@endsection
