@extends('layouts.master')

@php $naira = fn ($k) => \App\Modules\Billing\BusinessAccountService::naira((int) $k); @endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Business accounts" icon="ri-briefcase-4-line" subtitle="Businesses that book on credit. Their deliveries are billed on one invoice each month." />
    @include('provider._flash')

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Accounts" icon="ri-building-line" :flush="true">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Business</th><th class="text-end">Not yet billed</th><th class="text-end">Invoiced, unpaid</th><th class="text-end">Credit limit</th></tr></thead>
                    <tbody>
                    @forelse($accounts as $a)
                        <tr>
                            <td><a href="{{ route('provider.accounts.show', $a->public_id) }}" class="fw-semibold">{{ $a->name }}</a>
                                @if($a->status !== 'active')<span class="badge bg-secondary">{{ str_replace('_', ' ', $a->status) }}</span>@endif
                                @if($a->owed['overdue'] > 0)<span class="badge bg-danger">overdue</span>@endif
                                <div class="small text-muted">Pays within {{ $a->terms_days }} days @if($a->merchant_id)· partner API @endif</div></td>
                            <td class="text-end">{{ $naira($a->owed['unbilled']) }}</td>
                            <td class="text-end">{{ $naira($a->owed['invoiced']) }}</td>
                            <td class="text-end">{{ $a->credit_limit !== null ? $naira($a->credit_limit) : 'No limit' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No business accounts yet. Add your first one.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-cb.card>
        </div>
        <div class="col-lg-4">
            <x-cb.card title="Add an account" icon="ri-add-line" class="mb-3">
                <form method="POST" action="{{ route('provider.accounts.store') }}" class="row g-1">@csrf
                    @include('provider._business-account-fields', ['v' => null])
                    <div class="col-12"><button class="btn btn-primary w-100 mt-2">Add account</button></div>
                </form>
            </x-cb.card>
            <x-cb.card title="How it works" icon="ri-information-line">
                <ul class="small mb-0">
                    <li>On a new booking or a bulk upload, choose <em>On account</em> and the business. The job goes to dispatch straight away.</li>
                    <li>A booking is refused when the account is on hold, has an overdue invoice, or would go over its credit limit.</li>
                    <li>On the 1st of each month every account is billed for last month's finished deliveries. You can also bill an account now from its page.</li>
                    <li>Download or email each invoice, and record the payment when the money arrives.</li>
                </ul>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
