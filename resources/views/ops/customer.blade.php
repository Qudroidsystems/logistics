@extends('layouts.master')

@section('content')
@php $tz = config('app.timezone'); $m = fn ($v) => Money::format((int) $v, true); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$c->name" icon="ri-user-heart-line" :subtitle="$c->email.($c->phone_number ? ' · '.\App\Support\Phone::display($c->phone_number) : '')" :back="route('ops.customers')" back-label="Customers">
        <x-slot:pills>
            <span class="cb-meta-pill">Joined {{ \Illuminate\Support\Carbon::parse($c->joined)->format('j M Y') }}</span>
            @if($c->last_login_at)<span class="cb-meta-pill">Last seen {{ \Illuminate\Support\Carbon::parse($c->last_login_at)->diffForHumans() }}</span>@endif
            @if($c->is_disabled)<span class="cb-meta-pill text-danger">Suspended</span>@endif
            @if($c->deleted_at)<span class="cb-meta-pill">Account deleted</span>@endif
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Orders" :value="$stats->n" icon="ri-shopping-bag-3-line" accent="sky" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Spent (paid)" :value="$m($stats->spent)" icon="ri-money-dollar-circle-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Cancelled" :value="$cancelled" icon="ri-close-circle-line" :accent="$cancelled ? 'amber' : 'teal'" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Wallet" :value="$balance === null ? 'Hidden' : $m($balance)" icon="ri-wallet-3-line" accent="violet" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Orders" icon="ri-shopping-bag-3-line" :count="count($orders)" :flush="true" class="mb-3">
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Order</th><th>Provider</th><th>Status</th><th class="text-end">Total</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($orders as $o)
                        <tr><td><a href="{{ route('ops.order', $o->public_id) }}">{{ $o->order_number }}</a><div class="small text-muted">{{ $o->tracking_code }}</div></td>
                            <td class="small">{{ $o->provider }}</td><td class="small">{{ str_replace('_', ' ', (string) $o->status) }}@if($o->payment_status !== 'paid') · {{ $o->payment_status }}@endif</td>
                            <td class="text-end">{{ $m($o->total) }}</td><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($o->created_at)->setTimezone($tz)->format('j M Y') }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No orders.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </x-cb.card>

            <div class="row g-3">
                <div class="col-md-6">
                    <x-cb.card title="Help requests" icon="ri-customer-service-2-line" :count="count($tickets)" :flush="true" class="h-100">
                        <div class="list-group list-group-flush">
                            @forelse($tickets as $t)<a class="list-group-item list-group-item-action small" href="{{ route('ops.support.show', $t->public_id) }}">{{ $t->number }} · {{ $t->subject }} <span class="text-muted">({{ str_replace('_', ' ', $t->status) }})</span></a>
                            @empty<div class="list-group-item small text-muted">None.</div>@endforelse
                        </div>
                    </x-cb.card>
                </div>
                <div class="col-md-6">
                    <x-cb.card title="Requests for offers" icon="ri-chat-quote-line" :count="count($requests)" :flush="true" class="h-100">
                        <div class="list-group list-group-flush">
                            @forelse($requests as $r)<div class="list-group-item small">{{ ucfirst($r->type) }} · {{ $r->status }}@if($r->reference_price) · going rate {{ $m($r->reference_price) }}@endif <span class="text-muted">{{ \Illuminate\Support\Carbon::parse($r->created_at)->diffForHumans() }}</span></div>
                            @empty<div class="list-group-item small text-muted">None.</div>@endforelse
                        </div>
                    </x-cb.card>
                </div>
            </div>

            @if($balance !== null)
            <x-cb.card title="Wallet activity" icon="ri-exchange-funds-line" :flush="true" class="mt-3">
                <div class="table-responsive"><table class="table table-sm mb-0">
                    @forelse($ledger as $e)
                        <tr><td class="small">{{ $e->description ?: str_replace('_', ' ', $e->kind) }}</td>
                            <td class="text-end small {{ $e->direction === 'credit' ? 'text-success' : '' }}">{{ $e->direction === 'credit' ? '+' : '−' }}{{ $m($e->amount) }}</td>
                            <td class="text-end small text-muted">{{ $e->balance_after !== null ? $m($e->balance_after) : '' }}</td>
                            <td class="small text-muted text-end">{{ \Illuminate\Support\Carbon::parse($e->created_at)->setTimezone($tz)->format('j M, H:i') }}</td></tr>
                    @empty
                        <tr><td class="text-muted small py-3 text-center">No wallet activity.</td></tr>
                    @endforelse
                </table></div>
            </x-cb.card>
            @endif
        </div>

        <div class="col-xl-4">
            <x-cb.card title="Business plan" icon="ri-vip-crown-2-line" class="mb-3">
                @if($plan)
                    <div class="fw-semibold">{{ $plan['name'] }} · {{ str_replace('_', ' ', $plan['status']) }}</div>
                    <div class="small">{{ $plan['used'] }} of {{ $plan['included'] }} deliveries used · renews {{ \Illuminate\Support\Carbon::parse($plan['period_end'])->format('j M') }}@if($plan['cancel_at_period_end']) (cancelling)@endif</div>
                @else
                    <div class="small text-muted">No plan.</div>
                @endif
                @if(count($schedules))
                    <hr><div class="small fw-semibold mb-1">Recurring deliveries</div>
                    @foreach($schedules as $s)<div class="small">{{ $s['name'] }} · {{ $s['when'] }} <span class="text-muted">({{ $s['status'] }})</span></div>@endforeach
                @endif
            </x-cb.card>

            <x-cb.card title="Staff notes" icon="ri-sticky-note-line" class="mb-3">
                <form method="POST" action="{{ route('ops.customer.note', $c->public_id) }}" class="mb-2">@csrf
                    <textarea name="body" class="form-control form-control-sm mb-1" rows="2" maxlength="2000" required placeholder="Visible to staff only"></textarea>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check small"><input type="hidden" name="pinned" value="0"><input class="form-check-input" type="checkbox" name="pinned" value="1" id="pin"><label class="form-check-label" for="pin">Pin to top</label></div>
                        <button class="btn btn-sm btn-primary">Add note</button>
                    </div>
                </form>
                @forelse($notes as $n)
                    <div class="border-top pt-2 mt-2 small">@if($n->pinned)<i class="ri-pushpin-fill text-warning"></i> @endif<span style="white-space: pre-wrap">{{ $n->body }}</span>
                        <div class="text-muted">{{ $n->author ?: 'System' }} · {{ \Illuminate\Support\Carbon::parse($n->created_at)->setTimezone($tz)->format('j M Y, H:i') }}</div></div>
                @empty
                    <div class="small text-muted">No notes yet.</div>
                @endforelse
            </x-cb.card>

            @if($canSuspend && ! $c->deleted_at)
            <x-cb.card title="Account" icon="ri-shield-user-line">
                @if($c->is_disabled)
                    <form method="POST" action="{{ route('ops.customer.suspend', $c->public_id) }}">@csrf<input type="hidden" name="suspend" value="0"><button class="btn btn-sm btn-outline-success w-100">Lift suspension</button></form>
                @else
                    <form method="POST" action="{{ route('ops.customer.suspend', $c->public_id) }}" onsubmit="return confirm('Suspend this customer? They are signed out everywhere and cannot book.')">@csrf
                        <input type="hidden" name="suspend" value="1">
                        <input name="reason" class="form-control form-control-sm mb-1" placeholder="Why (kept as a note)" maxlength="300" required>
                        <button class="btn btn-sm btn-outline-danger w-100">Suspend</button>
                    </form>
                @endif
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
