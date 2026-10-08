@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Hello, '.explode(' ', auth()->user()->name)[0]" icon="ri-home-4-line" subtitle="Get things delivered, run errands, and track it all in one place.">
        <x-slot:actions><a href="{{ route('account.request.new') }}" class="action-btn btn-primary-cb"><i class="ri-add-line"></i>Ask for a delivery</a></x-slot:actions>
    </x-cb.hero>
    @include('account._flash')

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Wallet" :value="$naira($balance)" icon="ri-wallet-3-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Open requests" :value="$openRequests" icon="ri-chat-quote-line" accent="violet" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="On the way" :value="count($active)" icon="ri-route-line" accent="sky" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="To confirm" :value="count($toConfirm)" icon="ri-checkbox-circle-line" accent="amber" /></div>
    </div>

    @if(count($toPay))
    <x-cb.card title="Waiting for your payment" icon="ri-bank-card-line" class="mb-3" :flush="true">
        <table class="table align-middle mb-0"><tbody>
        @foreach($toPay as $p)<tr><td>{{ $p->provider }} <span class="text-muted small">{{ $p->number }}</span></td><td class="text-end">{{ $naira($p->price) }}</td><td class="text-end"><a href="{{ route('account.pay', $p->public_id) }}" class="btn btn-sm btn-primary">Pay now</a></td></tr>@endforeach
        </tbody></table>
    </x-cb.card>
    @endif

    @if(count($toConfirm))
    <x-cb.card title="Delivered. Is everything right?" icon="ri-checkbox-circle-line" class="mb-3" :flush="true">
        <table class="table align-middle mb-0"><tbody>
        @foreach($toConfirm as $o)<tr><td>{{ $o->order_number }} <span class="text-muted small">{{ $o->provider }}</span></td><td class="text-end"><a href="{{ route('account.order', $o->public_id) }}" class="btn btn-sm btn-success">Confirm or report</a></td></tr>@endforeach
        </tbody></table>
    </x-cb.card>
    @endif

    <x-cb.card title="On the way" icon="ri-route-line" :flush="true">
        <table class="table align-middle mb-0"><tbody>
        @forelse($active as $o)
            <tr><td><a href="{{ route('account.order', $o->public_id) }}" class="fw-semibold">{{ $o->order_number }}</a><div class="small text-muted">{{ $o->provider }}</div></td><td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $o->status) }}</span></td><td class="text-end">{{ $naira($o->total) }}</td></tr>
        @empty
            <tr><td class="text-center text-muted py-4">Nothing on the way. <a href="{{ route('account.request.new') }}">Ask for a delivery</a>.</td></tr>
        @endforelse
        </tbody></table>
    </x-cb.card>
</div></div></div>
@endsection
