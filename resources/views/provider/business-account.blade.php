@extends('layouts.master')

@php $naira = fn ($k) => \App\Modules\Billing\BusinessAccountService::naira((int) $k); @endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$a->name" icon="ri-briefcase-4-line" :subtitle="'Business account · pays within '.$a->terms_days.' days'.($a->status !== 'active' ? ' · '.str_replace('_', ' ', $a->status) : '')" />
    @include('provider._flash')
    <p><a href="{{ route('provider.accounts') }}" class="small">&larr; All business accounts</a></p>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Not yet billed" :value="$naira($owed['unbilled'])" icon="ri-time-line" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Invoiced, unpaid" :value="$naira($owed['invoiced'])" icon="ri-file-list-3-line" accent="amber" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Overdue" :value="$naira($owed['overdue'])" icon="ri-alarm-warning-line" accent="rose" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Credit left" :value="$a->credit_limit !== null ? $naira(max(0, $a->credit_limit - $owed['total'])) : 'No limit'" icon="ri-bank-card-line" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Invoices" icon="ri-file-text-line" class="mb-3" :flush="true">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Invoice</th><th>Period</th><th class="text-end">Amount</th><th>Due</th><th style="min-width:260px"></th></tr></thead>
                    <tbody>
                    @forelse($invoices as $i)
                        <tr>
                            <td class="fw-semibold">{{ $i->number }}
                                @if($i->status === 'paid')<span class="badge bg-success">paid</span>@elseif($i->overdue)<span class="badge bg-danger">overdue</span>@else<span class="badge bg-warning text-dark">unpaid</span>@endif
                                @if($i->sent_at)<div class="small text-muted">Emailed {{ \Illuminate\Support\Carbon::parse($i->sent_at)->format('j M') }}</div>@endif</td>
                            <td class="small">{{ \Illuminate\Support\Carbon::parse($i->period_start)->format('j M') }} to {{ \Illuminate\Support\Carbon::parse($i->period_end)->format('j M Y') }}</td>
                            <td class="text-end">{{ $naira($i->total) }}</td>
                            <td class="small">{{ \Illuminate\Support\Carbon::parse($i->due_on)->format('j M Y') }}@if($i->paid_at && $i->status === 'paid')<div class="text-muted">Paid {{ \Illuminate\Support\Carbon::parse($i->paid_at)->format('j M') }}@if($i->paid_reference) · {{ $i->paid_reference }}@endif</div>@endif</td>
                            <td>
                                <a href="{{ route('provider.invoices.pdf', $i->public_id) }}" class="btn btn-sm btn-outline-primary">PDF</a>
                                @if($a->billing_email)
                                    <form method="POST" action="{{ route('provider.invoices.send', $i->public_id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Email</button></form>
                                @endif
                                @if($i->status !== 'paid')
                                    <form method="POST" action="{{ route('provider.invoices.paid', $i->public_id) }}" class="d-flex gap-1 mt-1">@csrf
                                        <input name="reference" class="form-control form-control-sm" placeholder="Payment reference (optional)" maxlength="120">
                                        <button class="btn btn-sm btn-success text-nowrap">Mark paid</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No invoices yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-cb.card>

            <x-cb.card title="Deliveries not yet billed" icon="ri-route-line" :flush="true">
                <div class="px-3 pt-3 d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Finished deliveries go on the next invoice. Jobs still on the road wait for the one after.</span>
                    <form method="POST" action="{{ route('provider.accounts.issue', $a->public_id) }}" onsubmit="return confirm('Bill the finished deliveries now?')">@csrf<button class="btn btn-sm btn-primary">Bill now</button></form>
                </div>
                <table class="table align-middle mb-0 mt-2">
                    <thead><tr><th>Order</th><th>Booked</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    @forelse($unbilled as $o)
                        <tr>
                            <td><a href="{{ route('provider.job', $o->public_id) }}">{{ $o->order_number }}</a></td>
                            <td class="small">{{ \Illuminate\Support\Carbon::parse($o->created_at)->format('j M, H:i') }}</td>
                            <td class="small">{{ str_replace('_', ' ', $o->status) }}@unless(in_array($o->status, $finished, true)) <span class="text-muted">(not finished)</span>@endunless</td>
                            <td class="text-end">{{ $naira($o->owed) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Nothing waiting to be billed.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-cb.card>
        </div>
        <div class="col-lg-4">
            <x-cb.card title="Account details" icon="ri-edit-line" class="mb-3">
                <form method="POST" action="{{ route('provider.accounts.update', $a->public_id) }}" class="row g-1">@csrf @method('PUT')
                    @include('provider._business-account-fields', ['v' => $a])
                    <div class="col-12"><button class="btn btn-primary w-100 mt-2">Save</button></div>
                </form>
            </x-cb.card>
            <x-cb.card title="Status" icon="ri-toggle-line">
                <form method="POST" action="{{ route('provider.accounts.status', $a->public_id) }}" class="d-flex gap-1">@csrf
                    <select name="status" class="form-select form-select-sm">
                        @foreach(['active' => 'Active: can book', 'on_hold' => 'On hold: no new bookings', 'closed' => 'Closed'] as $k => $label)<option value="{{ $k }}" @selected($a->status === $k)>{{ $label }}</option>@endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-secondary">Change</button>
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
