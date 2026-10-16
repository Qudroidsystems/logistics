@extends('layouts.master')

@php $naira = fn ($k) => \App\Modules\Cod\CodService::naira((int) $k); @endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Cash on delivery" icon="ri-hand-coin-line" subtitle="Goods money riders collect from receivers. Count it in when a rider brings it to the office, then pay it to the sender and record the reference." />
    @include('provider._flash')

    <div class="row g-3 mb-1">
        <div class="col-sm-6 col-lg-3"><x-cb.card><div class="text-muted small">With riders</div><div class="fs-4 fw-semibold">{{ $naira($totals['with_riders']) }}</div></x-cb.card></div>
        <div class="col-sm-6 col-lg-3"><x-cb.card><div class="text-muted small">In the office, to pay out</div><div class="fs-4 fw-semibold">{{ $naira($totals['in_office']) }}</div></x-cb.card></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Cash riders are holding" icon="ri-e-bike-2-line" :count="count($riders)">
                @forelse($riders as $r)
                    <form method="POST" action="{{ route('provider.cash.handin') }}" class="border rounded p-2 mb-2">@csrf
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="fw-semibold">{{ $r['rider'] }} @if($r['overdue'])<span class="badge bg-danger-subtle text-danger">overdue</span>@endif</div>
                            <div class="fw-semibold">{{ $naira($r['total']) }}</div>
                        </div>
                        @foreach($r['rows'] as $c)
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="ids[]" value="{{ $c->id }}" id="h{{ $c->id }}" checked>
                                <label class="form-check-label" for="h{{ $c->id }}">{{ $naira($c->amount) }} for <a href="{{ route('provider.job', $c->shipment) }}">{{ $c->order_number }}</a>, collected {{ \Illuminate\Support\Carbon::parse($c->collected_at)->format('d M H:i') }}@if($c->overdue) <span class="text-danger">(hand-in was due {{ \Illuminate\Support\Carbon::parse($c->remit_due_at)->format('d M H:i') }})</span>@endif</label>
                            </div>
                        @endforeach
                        <button class="btn btn-sm btn-primary mt-1">Count in the ticked cash</button>
                    </form>
                @empty
                    <div class="text-muted text-center py-3">No rider is holding cash.</div>
                @endforelse
            </x-cb.card>
        </div>
        <div class="col-lg-6">
            <x-cb.card title="To pay to senders" icon="ri-bank-line" :count="count($senders)">
                @forelse($senders as $s)
                    <form method="POST" action="{{ route('provider.cash.payout') }}" class="border rounded p-2 mb-2">@csrf
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="fw-semibold">{{ $s['sender'] ?? 'Sender' }} <span class="badge bg-light text-dark">{{ $s['kind'] === 'merchant' ? 'business' : 'customer' }}</span></div>
                            <div class="fw-semibold">{{ $naira($s['total']) }}</div>
                        </div>
                        @foreach($s['rows'] as $c)
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="ids[]" value="{{ $c->id }}" id="p{{ $c->id }}" checked>
                                <label class="form-check-label" for="p{{ $c->id }}">{{ $naira($c->amount) }} for <a href="{{ route('provider.job', $c->shipment) }}">{{ $c->order_number }}</a></label>
                            </div>
                        @endforeach
                        <div class="input-group input-group-sm mt-1">
                            <input name="reference" class="form-control" maxlength="80" required placeholder="Transfer reference, or &quot;cash&quot;">
                            <button class="btn btn-success">Record as paid</button>
                        </div>
                    </form>
                @empty
                    <div class="text-muted text-center py-3">Nothing waiting to be paid out.</div>
                @endforelse
            </x-cb.card>
        </div>
        <div class="col-12">
            <x-cb.card title="Paid in the last 30 days" icon="ri-check-double-line" :count="count($paid)" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Order</th><th>Sender</th><th>Rider</th><th class="text-end">Amount</th><th>Paid</th><th>Reference</th></tr></thead>
                    <tbody>
                    @forelse($paid as $c)
                        <tr><td><a href="{{ route('provider.job', $c->shipment) }}">{{ $c->order_number }}</a></td><td>{{ $c->sender }}</td><td>{{ $c->rider }}</td>
                            <td class="text-end">{{ $naira($c->amount) }}</td><td class="small">{{ \Illuminate\Support\Carbon::parse($c->paid_at)->format('d M H:i') }}</td><td class="small">{{ $c->paid_reference }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No payouts yet.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
