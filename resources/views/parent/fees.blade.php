{{-- resources/views/parent/fees.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $naira   = fn ($v) => '₦' . number_format((float) $v, 2);
    $bills   = $s['bills'] ?? collect();
    $totals  = $s['totals'] ?? ['adjusted' => 0, 'paid' => 0, 'outstanding' => 0, 'savings' => 0];
    $arrears = $s['arrears'] ?? [];
    $history = $s['paymentHistory'] ?? collect();
    $owing   = ($totals['outstanding'] ?? 0) > 0 || !empty($arrears['has_arrears']);
    $pill    = ['paid' => 'st-paid', 'covered' => 'st-info', 'partial' => 'st-pending', 'unpaid' => 'st-danger'];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Fees — ' . $child->firstname . ' ' . $child->lastname" icon="ri-wallet-3-line"
               subtitle="Bills, payments and balance for the selected term." :back="route('parent.dashboard')" back-label="My children">
        @if($owing)
            <x-slot:actions>
                <a href="{{ route('parent.pay', ['student' => $child->id, 'term_id' => $termId, 'session_id' => $sessionId]) }}" class="cb-hero-btn"><i class="ri-secure-payment-line"></i>Pay online</a>
            </x-slot:actions>
        @endif
    </x-cb.hero>

    @include('parent.partials.nav', ['section' => 'fees'])

    <form class="cb-card mb-3" method="GET">
        <div class="cb-toolbar gap-2">
            <select name="session_id" class="cb-select" onchange="this.form.submit()" aria-label="Session">
                @foreach($sessions as $ss)<option value="{{ $ss->id }}" @selected($ss->id == $sessionId)>{{ $ss->session }}</option>@endforeach
            </select>
            <select name="term_id" class="cb-select" onchange="this.form.submit()" aria-label="Term">
                @foreach($terms as $t)<option value="{{ $t->id }}" @selected($t->id == $termId)>{{ $t->term }}</option>@endforeach
            </select>
            @if(!empty($s['class']))<span class="term-chip">{{ $s['class']->schoolclass }}</span>@endif
        </div>
    </form>

    @if(!empty($arrears['has_arrears']))
        <div class="cb-banner warning"><i class="ri-alarm-warning-line"></i>
            <div class="flex-grow-1"><strong>Outstanding from other terms: {{ $naira($arrears['total_arrears']) }}</strong>
                <div class="small">These must be cleared first — the online checkout puts them at the top.</div>
                <div class="mt-1 d-flex flex-wrap gap-2">
                    @foreach($arrears['groups'] ?? [] as $g)
                        <a class="status-pill st-warning text-decoration-none" href="{{ route('parent.fees', ['student' => $child->id, 'session_id' => $g['session_id'], 'term_id' => $g['term_id']]) }}">
                            {{ $g['term_name'] }} · {{ $g['session_name'] }}: {{ $naira($g['outstanding']) }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @include('instalments.partials.schedule', ['studentId' => $child->id, 'termId' => $termId, 'sessionId' => $sessionId, 'payable' => $totals['adjusted'] ?? null, 'paid' => $totals['paid'] ?? null])

    @if($bills->isEmpty())
        <div class="cb-card"><div class="empty-state"><i class="ri-bill-line"></i><h6>No bills for this term</h6><p>{{ $s['statementError'] ?? 'No fee bills have been set for this term yet.' }}</p></div></div>
    @else
        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6"><x-cb.stat label="Total payable" :value="$naira($totals['adjusted'])" icon="ri-file-list-3-line" accent="teal" /></div>
            <div class="col-md-3 col-6"><x-cb.stat label="Paid" :value="$naira($totals['paid'])" icon="ri-checkbox-circle-line" accent="green" /></div>
            <div class="col-md-3 col-6"><x-cb.stat label="Outstanding" :value="$naira($totals['outstanding'])" icon="ri-time-line" :accent="$totals['outstanding'] > 0 ? 'rose' : 'green'" /></div>
            <div class="col-md-3 col-6"><x-cb.stat label="Scholarship & discount" :value="$naira($totals['savings'])" icon="ri-gift-line" accent="violet" /></div>
        </div>

        <x-cb.card title="Bills" icon="ri-bill-line" :count="$bills->count()" :flush="true">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Bill</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($bills as $b)
                        <tr>
                            <td><strong>{{ $b['title'] }}</strong>
                                @if($b['total_savings'] > 0)<div class="small text-success">Saved {{ $naira($b['total_savings']) }}</div>@endif
                                @if($b['due_date'])<div class="small {{ $b['is_overdue'] ? 'text-danger' : 'text-muted' }}">Due {{ $b['due_date'] }}</div>@endif</td>
                            <td class="text-end">{{ $naira($b['adjusted_amount']) }}</td>
                            <td class="text-end text-success">{{ $naira($b['amount_paid']) }}</td>
                            <td class="text-end fw-bold {{ $b['balance'] > 0 ? 'text-danger' : '' }}">{{ $naira($b['balance']) }}</td>
                            <td><span class="status-pill {{ $pill[$b['status']] ?? 'st-muted' }}">{{ ucfirst($b['status']) }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-cb.card>
    @endif

    <x-cb.card title="Payment history" icon="ri-history-line" :count="$history->count()" :flush="true">
        @if($history->isEmpty())
            <div class="empty-state"><i class="ri-history-line"></i><h6>No payments this term</h6></div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Date</th><th>Bill</th><th class="text-end">Amount</th><th class="text-end">Balance after</th><th>Method</th></tr></thead>
                    <tbody>
                    @foreach($history as $p)
                        <tr>
                            <td>{{ $p->paid_at?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $p->bill_title ?? '—' }}</td>
                            <td class="text-end fw-bold {{ $p->amount_paid < 0 ? 'text-danger' : 'text-success' }}">{{ $naira($p->amount_paid) }}</td>
                            <td class="text-end">{{ $naira($p->balance_after) }}</td>
                            <td><span class="term-badge">{{ ucfirst($p->method) }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
@endsection
