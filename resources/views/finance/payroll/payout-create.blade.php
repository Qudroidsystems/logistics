{{-- resources/views/finance/payroll/payout-create.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Pay staff · ' . $period->period_name" icon="ri-send-plane-line"
               :subtitle="$provider === 'paystack' ? 'Paystack Transfers (' . ($gw->mode() === 'live' ? 'LIVE' : 'test mode') . ')' : 'Manual bank upload'"
               :back="route('payroll.payouts')" back-label="Salary payments">
        <x-slot:actions>
            <a href="{{ route('payroll.payouts.create', [$period, 'provider' => $provider === 'paystack' ? 'manual' : 'paystack']) }}" class="cb-hero-btn"><i class="ri-swap-line"></i>Use {{ $provider === 'paystack' ? 'bank upload' : 'Paystack' }} instead</a>
        </x-slot:actions>
    </x-cb.hero>

    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($provider === 'paystack' && !$gw->isReady())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>Paystack is not ready: {{ $gw->problem() }}. Use bank upload, or set it up under Payment Gateways.</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><x-cb.stat label="Ready to pay" :value="count($r['ready']) . ' staff'" icon="ri-user-follow-line" accent="green" /></div>
        <div class="col-md-4"><x-cb.stat label="Total" :value="$m($r['total'])" icon="ri-money-dollar-circle-line" accent="teal" /></div>
        <div class="col-md-4"><x-cb.stat label="Can't pay yet" :value="count($r['blocked'])" icon="ri-user-unfollow-line" :accent="count($r['blocked']) ? 'amber' : 'green'" :hint="$r['already'] ? $r['already'] . ' already paid / in progress' : null" /></div>
    </div>

    <form method="POST" action="{{ route('payroll.payouts.prepare', $period) }}">@csrf
        <input type="hidden" name="provider" value="{{ $provider }}">
        <div class="row g-3">
            <div class="col-xl-8">
                <x-cb.card title="Staff to pay" icon="ri-team-line" :count="count($r['ready'])" :flush="true">
                    @if(!$r['ready'])
                        <div class="empty-state"><i class="ri-checkbox-circle-line"></i><h6>Nobody left to pay</h6><p>Everyone is either paid, in a batch already, or blocked (see right).</p></div>
                    @else
                        <div class="table-responsive"><table class="table align-middle mb-0">
                            <thead><tr><th style="width:36px"><input type="checkbox" class="form-check-input" checked onclick="document.querySelectorAll('.pick').forEach(c=>c.checked=this.checked)"></th><th>Staff</th><th>Bank</th><th class="text-end">Net pay</th></tr></thead>
                            <tbody>
                            @foreach($r['ready'] as $x)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input pick" name="runs[]" value="{{ $x['run']->id }}" checked></td>
                                    <td>{{ $x['name'] }}</td>
                                    <td class="small">{{ $x['profile']->bank_name }} · {{ $x['profile']->maskedAccount() }}<div class="text-muted">{{ $x['profile']->account_name }}</div></td>
                                    <td class="text-end fw-bold">{{ $m($x['run']->net_pay) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table></div>
                    @endif
                </x-cb.card>
            </div>
            <div class="col-xl-4">
                <x-cb.card title="Prepare batch" icon="ri-stack-line">
                    <p class="small text-muted">Preparing does not move money. A different person with “Release salary payments” must release the batch.</p>
                    <button class="action-btn btn-primary-cb w-100 justify-content-center" @disabled(!$r['ready'] || ($provider === 'paystack' && !$gw->isReady()))><i class="ri-stack-line"></i>Prepare batch</button>
                </x-cb.card>
                @if($r['blocked'])
                <x-cb.card title="Can't pay yet" icon="ri-error-warning-line" :count="count($r['blocked'])" :flush="true">
                    <ul class="list-group list-group-flush">
                        @foreach($r['blocked'] as $x)
                            <li class="list-group-item d-flex justify-content-between align-items-center small">
                                <span>{{ $x['name'] }}<div class="text-danger">{{ $x['why'] }}</div></span>
                                @can('Manage staff pay profiles')<a href="{{ route('payroll.profiles.edit', $x['run']->staff_id) }}" class="action-btn btn-open">Fix</a>@endcan
                            </li>
                        @endforeach
                    </ul>
                </x-cb.card>
                @endif
            </div>
        </div>
    </form>
</div>
</div>
</div>
@endsection
