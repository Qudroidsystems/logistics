@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Wallet and payouts" icon="ri-wallet-3-line" subtitle="Your earnings land here after the customer confirms delivery." :back="route('provider.dashboard')" back-label="Dashboard" />
    @include('provider._flash')

    <div class="row g-3 mb-3">
        <div class="col-6"><x-cb.stat label="Available to withdraw" :value="$naira($w['balance'])" icon="ri-wallet-3-line" accent="teal" /></div>
        <div class="col-6"><x-cb.stat label="Payouts on the way" :value="$naira($w['pending_payouts'])" icon="ri-send-plane-line" accent="sky" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <x-cb.card title="Withdraw" icon="ri-bank-line">
                @if(count($banks))
                <form method="POST" action="{{ route('provider.payout') }}">@csrf
                    <label class="form-label small">Amount (₦)</label>
                    <input type="number" step="0.01" min="1" name="amount_naira" class="form-control mb-2" value="{{ old('amount_naira') }}" required>
                    <label class="form-label small">Pay into</label>
                    <select name="bank_account_id" class="form-select mb-3" required>
                        @foreach($banks as $b)<option value="{{ $b['public_id'] }}">{{ $b['bank_name'] }} ····{{ $b['account_number_last4'] }} · {{ $b['account_name'] }}</option>@endforeach
                    </select>
                    <button class="btn btn-primary w-100">Request payout</button>
                </form>
                @else
                    <p class="text-muted mb-0">Add a bank account first.</p>
                @endif
            </x-cb.card>

            <x-cb.card title="Bank accounts" icon="ri-bank-card-line" class="mt-3">
                @foreach($banks as $b)
                    <div class="d-flex justify-content-between mb-2"><span>{{ $b['bank_name'] }} ····{{ $b['account_number_last4'] }}<div class="small text-muted">{{ $b['account_name'] }}</div></span>@if($b['verified_at'])<span class="badge bg-success align-self-start">verified</span>@endif</div>
                @endforeach
                <form method="POST" action="{{ route('provider.bank') }}" class="mt-2">@csrf
                    <select name="bank_code" class="form-select mb-2" required>
                        <option value="">Choose bank</option>
                        @foreach($bankList as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
                    </select>
                    <input name="account_number" class="form-control mb-2" placeholder="10-digit account number" maxlength="10" inputmode="numeric" required>
                    <button class="btn btn-outline-primary w-100">Add account</button>
                    <div class="form-text">We check the account with the bank. Payouts only go to the name the bank returns.</div>
                </form>
            </x-cb.card>
        </div>
        <div class="col-lg-7">
            <x-cb.card title="Payout history" icon="ri-history-line" :flush="true">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Requested</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($payouts as $p)
                        <tr><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($p['created_at'])->format('d M Y H:i') }}</td><td class="text-end">{{ $naira($p['amount']) }}</td>
                            <td><span class="badge bg-{{ ['paid' => 'success', 'failed' => 'danger'][$p['status']] ?? 'light text-dark' }}">{{ $p['status'] }}</span>@if($p['failure_reason'])<div class="small text-muted">{{ $p['failure_reason'] }}</div>@endif</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No payouts yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
