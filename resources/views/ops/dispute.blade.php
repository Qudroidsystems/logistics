@extends('layouts.master')

@section('content')
@php
    $naira = fn ($k) => '₦'.number_format(((int) $k) / 100);
    $d = $x['dispute'];
    $isOpen = in_array($d['status'], ['open', 'evidence']);
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Dispute" icon="ri-scales-3-line" :subtitle="str_replace('_', ' ', $d['type']).' · '.$d['status']" :back="route('ops.disputes')" back-label="Disputes" />
    @include('ops._flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Evidence" icon="ri-file-list-3-line">
                @forelse($d['evidence'] as $k => $v)
                    <div class="mb-2"><div class="small text-muted text-uppercase">{{ str_replace('_', ' ', $k) }}</div><div>{{ is_array($v) ? json_encode($v) : $v }}</div></div>
                @empty <span class="text-muted">No evidence was added.</span> @endforelse
            </x-cb.card>
            @if(count($x['receipts']))
            <x-cb.card title="Shopping receipts" icon="ri-receipt-line" class="mt-3" :flush="true">
                <table class="table mb-0"><thead><tr><th>Vendor</th><th class="text-end">Amount</th><th>Customer verified</th></tr></thead><tbody>
                @foreach($x['receipts'] as $r)<tr><td>{{ $r['vendor_name'] }}</td><td class="text-end">{{ $naira($r['amount']) }}</td><td>{{ $r['verified_by_customer'] ? 'Yes' : 'No' }}</td></tr>@endforeach
                </tbody></table>
            </x-cb.card>
            @endif
            <x-cb.card title="Delivery timeline" icon="ri-time-line" class="mt-3" :flush="true">
                <table class="table mb-0"><tbody>
                @foreach($x['timeline'] as $e)<tr><td>{{ str_replace('_', ' ', $e['type']) }}</td><td>{{ $e['actor_type'] }}</td><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($e['created_at'])->format('d M H:i') }}</td></tr>@endforeach
                </tbody></table>
            </x-cb.card>
        </div>

        <div class="col-lg-5">
            <x-cb.card title="Money at stake" icon="ri-safe-2-line">
                @if($x['escrow'])
                    <div class="fs-4 fw-semibold">{{ $naira($x['escrow']['amount']) }}</div>
                    <div class="text-muted small">Escrow {{ $x['escrow']['status'] }}@if($x['escrow']['frozen_reason']) · {{ $x['escrow']['frozen_reason'] }}@endif</div>
                @else <span class="text-muted">No escrow found.</span> @endif
            </x-cb.card>

            @if($isOpen)
                @can('Resolve dispute')
                <x-cb.card title="Decide" icon="ri-hammer-line" class="mt-3">
                    <form method="POST" action="{{ route('ops.dispute.decide', $id) }}" onsubmit="return confirm('This moves real money and cannot be undone. Continue?')">@csrf
                        @foreach(['full_release' => 'Pay the provider in full', 'partial' => 'Split the payment', 'full_refund' => 'Refund the customer in full'] as $k => $label)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="decision" id="d_{{ $k }}" value="{{ $k }}" @checked(old('decision') === $k) required onchange="document.getElementById('shareBox').classList.toggle('d-none', this.value !== 'partial')">
                                <label class="form-check-label" for="d_{{ $k }}">{{ $label }}</label>
                            </div>
                        @endforeach
                        <div id="shareBox" class="mb-3 {{ old('decision') === 'partial' ? '' : 'd-none' }}">
                            <label class="form-label small">Provider receives (₦)</label>
                            <input type="number" step="0.01" min="0" name="provider_share_naira" class="form-control" value="{{ old('provider_share_naira') }}">
                            <div class="form-text">The customer gets the rest.</div>
                        </div>
                        <button class="btn btn-danger w-100">Record decision</button>
                    </form>
                </x-cb.card>
                @endcan
            @else
                <x-cb.card title="Decision" icon="ri-hammer-line" class="mt-3">{{ str_replace('_', ' ', $d['decision'] ?? 'none') }}</x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
