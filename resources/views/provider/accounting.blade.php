@extends('layouts.master')

@php
    use App\Modules\Accounting\AccountingSync;
    $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, 2);
    $linked = $conn && $conn->status !== 'disconnected';
@endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Accounting" icon="ri-book-2-line" subtitle="Link QuickBooks or Xero and every day's wallet money is sent there as one journal: jobs paid out, platform fees, rider pay, payouts to your bank and cash you collected." />
    @include('provider._flash')

    @if(! $linked)
        <x-cb.card title="Link your books" icon="ri-links-line">
            <p class="text-muted">You will sign in to your accounting software and approve the link. Only journals are written; nothing is read except your list of accounts.</p>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(AccountingSync::SERVICES as $key => $name)
                    <form method="POST" action="{{ route('provider.accounting.connect', $key) }}">@csrf
                        <button class="btn btn-primary" @disabled(! $configured[$key])>Link {{ $name }}</button>
                    </form>
                @endforeach
            </div>
            @if(! array_filter($configured))<p class="small text-muted mt-2 mb-0">Not switched on for this platform yet.</p>@endif
        </x-cb.card>
    @else
        <div class="row g-3">
            <div class="col-lg-7">
                <x-cb.card title="Linked to {{ AccountingSync::SERVICES[$conn->service] }}{{ $conn->tenant_name ? ': '.$conn->tenant_name : '' }}" icon="ri-links-line">
                    @if($conn->status === 'error')<div class="alert alert-danger py-2">Sending stopped: {{ $conn->error }}. Fix it, then press Send now.</div>@endif
                    @if($loadError)<div class="alert alert-warning py-2">{{ $loadError }}</div>@endif
                    <form method="POST" action="{{ route('provider.accounting.save') }}">@csrf
                        <p class="small text-muted">Choose where each kind of money goes. Set up your app wallet as its own bank account in your books first.</p>
                        @foreach(AccountingSync::ACCOUNTS as $key => $label)
                            <div class="row mb-2 align-items-center">
                                <label class="col-sm-5 col-form-label col-form-label-sm" for="acc-{{ $key }}">{{ $label }}</label>
                                <div class="col-sm-7">
                                    <select class="form-select form-select-sm" name="accounts[{{ $key }}]" id="acc-{{ $key }}">
                                        <option value="">Choose…</option>
                                        @foreach($books as $b)
                                            <option value="{{ $b['id'] }}" @selected(($mapping[$key] ?? null) == $b['id'])>{{ $b['name'] }} ({{ $b['type'] }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                        @error('accounts.*')<div class="text-danger small">{{ $message }}</div>@enderror
                        <div class="row mb-3 align-items-center">
                            <label class="col-sm-5 col-form-label col-form-label-sm" for="sync_from">Send days from</label>
                            <div class="col-sm-7"><input type="date" class="form-control form-control-sm" name="sync_from" id="sync_from" value="{{ \Illuminate\Support\Carbon::parse($conn->sync_from)->toDateString() }}" max="{{ today()->toDateString() }}"></div>
                        </div>
                        <button class="btn btn-primary btn-sm">Save</button>
                    </form>
                </x-cb.card>
            </div>
            <div class="col-lg-5">
                <x-cb.card title="Sending" icon="ri-send-plane-line">
                    <p class="mb-2">Sent up to: <strong>{{ $conn->synced_through ? \Illuminate\Support\Carbon::parse($conn->synced_through)->format('d M Y') : 'nothing yet' }}</strong></p>
                    <p class="small text-muted">Yesterday's journal goes overnight. Today is sent tomorrow, once the day is complete.</p>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('provider.accounting.sync') }}">@csrf<button class="btn btn-sm btn-primary" @disabled(! AccountingSync::mapped($conn))>Send now</button></form>
                        <form method="POST" action="{{ route('provider.accounting.disconnect') }}" onsubmit="return confirm('Unlink your books? Nothing more will be sent.')">@csrf<button class="btn btn-sm btn-outline-danger">Unlink</button></form>
                    </div>
                </x-cb.card>
                <x-cb.card title="Recent days" icon="ri-history-line" :count="count($history)">
                    @forelse($history as $h)
                        <div class="d-flex justify-content-between small border-bottom py-1">
                            <span>{{ \Illuminate\Support\Carbon::parse($h->day)->format('d M Y') }}</span>
                            <span>
                                @if($h->status === 'sent'){{ $naira($h->total) }} <span class="badge bg-success-subtle text-success">sent</span>
                                @elseif($h->status === 'empty')<span class="text-muted">no money moved</span>
                                @else<span class="badge bg-danger-subtle text-danger" title="{{ $h->error }}">failed</span>@endif
                            </span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Nothing sent yet.</p>
                    @endforelse
                </x-cb.card>
            </div>
        </div>
    @endif
</div></div></div>
@endsection
