@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Wallet" icon="ri-wallet-3-line" subtitle="Money you can spend on deliveries, or take back out." :back="route('account.dashboard')" back-label="Home" />
    @include('account._flash')
    <div class="row g-3 mb-3">
        <div class="col-6"><x-cb.stat label="Balance" :value="$naira($w['balance'])" icon="ri-wallet-3-line" accent="teal" /></div>
        <div class="col-6"><x-cb.stat label="Withdrawals on the way" :value="$naira($w['pending_withdrawals'])" icon="ri-send-plane-line" accent="sky" /></div>
    </div>
    <div class="row g-3">
        <div class="col-lg-5">
            <x-cb.card title="Add money" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('account.wallet.topup') }}">@csrf
                    <label class="form-label small">Amount (₦)</label><input type="number" step="0.01" min="100" name="amount_naira" class="form-control mb-2" value="{{ old('amount_naira') }}" required>
                    <button class="btn btn-primary w-100">Continue to payment</button>
                </form>
            </x-cb.card>
            <x-cb.card title="Take money out" icon="ri-bank-line" class="mt-3">
                @if(count($banks))
                <form method="POST" action="{{ route('account.wallet.withdraw') }}">@csrf
                    <label class="form-label small">Amount (₦)</label><input type="number" step="0.01" min="1" name="amount_naira" class="form-control mb-2" required>
                    <select name="bank_account_id" class="form-select mb-2" required>@foreach($banks as $b)<option value="{{ $b['public_id'] }}">{{ $b['bank_name'] }} ····{{ $b['account_number_last4'] }} · {{ $b['account_name'] }}</option>@endforeach</select>
                    <button class="btn btn-outline-primary w-100">Withdraw</button>
                </form>
                @else <p class="text-muted mb-0">Add a bank account first.</p> @endif
            </x-cb.card>
            <x-cb.card title="Bank account" icon="ri-bank-card-line" class="mt-3">
                <form method="POST" action="{{ route('account.wallet.bank') }}">@csrf
                    <select name="bank_code" class="form-select mb-2" required><option value="">Choose bank</option>@foreach($bankList as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select>
                    <input name="account_number" class="form-control mb-2" placeholder="10-digit account number" maxlength="10" inputmode="numeric" required>
                    <button class="btn btn-outline-primary w-100">Add account</button>
                    <div class="form-text">We check the account with the bank. Withdrawals only go to the name the bank returns.</div>
                </form>
            </x-cb.card>
        </div>
        <div class="col-lg-7">
            <x-cb.card title="Withdrawals" icon="ri-history-line" :flush="true">
                <table class="table mb-0"><thead><tr><th>Requested</th><th class="text-end">Amount</th><th>Status</th></tr></thead><tbody>
                @forelse($history as $h)<tr><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d M Y H:i') }}</td><td class="text-end">{{ $naira($h->amount) }}</td><td><span class="badge bg-light text-dark">{{ $h->status }}</span></td></tr>
                @empty<tr><td colspan="3" class="text-center text-muted py-4">None yet.</td></tr>@endforelse
                </tbody></table>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
