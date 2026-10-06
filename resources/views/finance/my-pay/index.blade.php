{{-- resources/views/finance/my-pay/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $latest = $runs->first(); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$s->viewing_other ? $s->name . ' — Pay' : 'My Pay'" icon="ri-wallet-3-line" subtitle="Payslips, tax, pension and deductions — download any of them.">
        <x-slot:actions>
            <a href="{{ route('my-pay.certificate', ['year' => $year] + $q) }}" target="_blank" class="cb-hero-btn"><i class="ri-award-line"></i>Tax certificate {{ $year }}</a>
            <a href="{{ route('my-pay.statement', ['year' => $year] + $q) }}" target="_blank" class="cb-hero-btn"><i class="ri-file-text-line"></i>Earnings statement</a>
        </x-slot:actions>
    </x-cb.hero>
    @include('finance.my-pay._nav', ['section' => 'index'])

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat :label="'Gross pay ' . $year" :value="$m($ytd['gross'])" icon="ri-money-dollar-circle-line" accent="teal" :hint="$ytd['months'] . ' month(s)'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat :label="'PAYE ' . $year" :value="$m($ytd['paye'])" icon="ri-government-line" accent="violet" /></div>
        <div class="col-md-3 col-6"><x-cb.stat :label="'Pension ' . $year" :value="$m($ytd['pension'] + $ytd['employer_pension'])" icon="ri-shield-user-line" accent="amber" hint="your part + school's part" /></div>
        <div class="col-md-3 col-6"><x-cb.stat :label="'Net pay ' . $year" :value="$m($ytd['net'])" icon="ri-wallet-3-line" accent="green" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Payslips" icon="ri-file-list-3-line" :count="$runs->count()" :flush="true">
                @if($runs->isEmpty())
                    <div class="empty-state"><i class="ri-file-list-3-line"></i><h6>No payslips yet</h6><p>Payslips appear here once the bursary approves a payroll month.</p></div>
                @else
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Month</th><th class="text-end">Gross</th><th class="text-end">PAYE</th><th class="text-end">Net pay</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($runs as $r)
                            <tr>
                                <td><strong>{{ \Carbon\Carbon::parse($r->start_date)->format('F Y') }}</strong><div class="small text-muted">{{ $r->period_name }}</div></td>
                                <td class="text-end">{{ $m($r->total_earnings) }}</td>
                                <td class="text-end">{{ $m($r->paye_tax) }}</td>
                                <td class="text-end fw-bold">{{ $m($r->net_pay) }}</td>
                                <td>@if(in_array($r->period_status, ['locked', 'paid']))<span class="status-pill st-paid">Final</span>@else<span class="status-pill st-pending">Approved</span>@endif</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('my-pay.payslip', ['run' => $r->id] + $q) }}" class="action-btn btn-open"><i class="ri-eye-line"></i>View</a>
                                    <a href="{{ route('my-pay.payslip.pdf', ['run' => $r->id, 'download' => 1] + $q) }}" class="action-btn btn-open" title="Download PDF"><i class="ri-download-2-line"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
            @if($otherPayments->isNotEmpty())
                <x-cb.card title="Other payments" icon="ri-hand-coin-line" :count="$otherPayments->count()" :flush="true">
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th>Details</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($otherPayments as $op)
                            <tr><td class="text-nowrap">{{ $op->payment_date ? \Carbon\Carbon::parse($op->payment_date)->format('d M Y') : '—' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $op->payment_type)) }}</td><td class="small text-muted">{{ $op->purpose }}</td>
                                <td class="text-end fw-bold">{{ $m($op->amount) }}</td>
                                <td><span class="status-pill {{ $op->payment_status === 'paid' ? 'st-paid' : ($op->payment_status === 'reversed' ? 'st-muted' : 'st-pending') }}">{{ ucfirst($op->payment_status) }}</span></td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </x-cb.card>
            @endif
        </div>
        <div class="col-xl-4">
            <x-cb.card title="My pay details" icon="ri-bank-card-line">
                @if($profile)
                    <div class="small"><div class="text-muted">Bank</div>{{ $profile->bank_name ?: '—' }} {{ $profile->account_last4 ? '******' . $profile->account_last4 : '' }}
                        @if($profile->account_verified_at)<span class="text-success"><i class="ri-shield-check-line"></i></span>@endif</div>
                    <div class="small mt-2"><div class="text-muted">TIN · tax state</div>{{ $profile->tin ?: '—' }} · {{ $profile->tax_state ?: '—' }}</div>
                    <div class="small mt-2"><div class="text-muted">Pension</div>{{ $profile->pfa_name ?: '—' }} {{ $profile->rsa_pin ? '· ' . $profile->rsa_pin : '' }}</div>
                    <div class="small mt-2 text-muted">Something wrong? Contact the bursary — bank changes are confirmed to you by SMS.</div>
                @else
                    <div class="small text-muted">Your pay details haven't been set up yet.</div>
                @endif
            </x-cb.card>
            <x-cb.card title="Documents" icon="ri-folder-download-line">
                <div class="d-grid gap-2">
                    <a href="{{ route('my-pay.certificate', ['year' => $year] + $q) }}" target="_blank" class="action-btn btn-open"><i class="ri-award-line"></i>Annual tax certificate ({{ $year }})</a>
                    <a href="{{ route('my-pay.tax', ['year' => $year, 'format' => 'pdf'] + $q) }}" target="_blank" class="action-btn btn-open"><i class="ri-government-line"></i>Tax history PDF</a>
                    <a href="{{ route('my-pay.pension', ['year' => $year, 'format' => 'pdf'] + $q) }}" target="_blank" class="action-btn btn-open"><i class="ri-shield-user-line"></i>Pension statement PDF</a>
                    <a href="{{ route('my-pay.statement', ['year' => $year] + $q) }}" target="_blank" class="action-btn btn-open"><i class="ri-file-text-line"></i>Statement of earnings</a>
                </div>
                <div class="small text-muted mt-2">Each document can be checked by scanning its QR code where shown.</div>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
