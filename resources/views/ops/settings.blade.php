@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Settings" icon="ri-settings-4-line" subtitle="The numbers that shape money and timing. Changes apply to new agreements." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Platform fee" icon="ri-percent-line">
                <p class="small text-muted">What the platform keeps from each delivery price. The fee is never more than the price itself.</p>
                <form method="POST" action="{{ route('ops.settings.fee') }}">@csrf
                    <label class="form-label">Percentage of the delivery price</label>
                    <div class="input-group mb-2"><input type="number" step="0.01" min="0" max="50" name="percent" class="form-control" value="{{ old('percent', $feePercent) }}" required @disabled(!$canFee)><span class="input-group-text">%</span></div>
                    <label class="form-label">Minimum fee per job</label>
                    <div class="input-group mb-3"><span class="input-group-text">₦</span><input type="number" step="0.01" min="0" name="min_naira" class="form-control" value="{{ old('min_naira', $feeMin) }}" required @disabled(!$canFee)></div>
                    @if($canFee)<button class="btn btn-primary">Save fee</button>@else<span class="text-muted small">You can view this but not change it.</span>@endif
                </form>
                @if(count($otherRules))
                <hr>
                <div class="small fw-semibold mb-1">Other fee rules in force</div>
                <table class="table table-sm mb-0"><tbody>
                @foreach($otherRules as $r)
                    <tr><td class="small">{{ $r->operator_id ? 'One provider' : ($r->operator_type ? str_replace('_', ' ', $r->operator_type) : 'Any provider') }}{{ $r->city_id ? ', one city' : '' }}{{ $r->service_type_id ? ', one service' : '' }}</td><td class="small text-end">{{ str_replace('_', ' ', $r->basis) }} {{ $r->rate_bp ? rtrim(rtrim(number_format($r->rate_bp / 100, 2), '0'), '.').'%' : '' }}</td></tr>
                @endforeach
                </tbody></table>
                <div class="form-text">These win over the platform fee where they match. They are read-only here.</div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-lg-6">
            <x-cb.card title="Timing and limits" icon="ri-time-line">
                <form method="POST" action="{{ route('ops.settings.rules') }}">@csrf
                    <label class="form-label">Hours a customer has to confirm delivery</label>
                    <input type="number" min="1" max="168" name="window_hours" class="form-control mb-1" value="{{ old('window_hours', $window) }}" required @disabled(!$canRules)>
                    <div class="form-text mb-3">After this, payment is released to the provider unless the customer reports a problem.</div>

                    <label class="form-label">Most a shopper may take up front</label>
                    <div class="input-group mb-1"><input type="number" step="0.01" min="0" max="100" name="advance_percent" class="form-control" value="{{ old('advance_percent', $advance) }}" required @disabled(!$canRules)><span class="input-group-text">% of the goods budget</span></div>
                    <div class="form-text mb-3">Higher means shoppers can buy more without their own cash, and more is at risk if they do not return with receipts.</div>

                    <label class="form-label">Seconds between live tracking updates</label>
                    <input type="number" min="3" max="60" name="tracking_seconds" class="form-control mb-1" value="{{ old('tracking_seconds', $tracking) }}" required @disabled(!$canRules)>
                    <div class="form-text mb-3">Shorter feels livelier but uses more data and server load.</div>

                    @if($canRules)<button class="btn btn-primary">Save settings</button>@else<span class="text-muted small">You can view these but not change them.</span>@endif
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
