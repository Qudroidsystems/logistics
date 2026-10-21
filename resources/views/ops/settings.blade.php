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
                <hr>
                <div class="fw-semibold mb-1">Special fees</div>
                <p class="small text-muted mb-2">A different fee for one provider, or for every provider of one kind. Optionally only in one city or for one service. The most specific match wins over the platform fee.</p>
                @if(count($otherRules))
                <div class="table-responsive"><table class="table table-sm align-middle mb-2"><tbody>
                @foreach($otherRules as $r)
                    <tr class="{{ $r->active ? '' : 'text-muted' }}">
                        <td class="small">
                            <strong>{{ $r->provider ?? ($r->operator_type ? 'Every '.str_replace('_', ' ', $r->operator_type) : 'Any provider') }}</strong>
                            <div>{{ collect([$r->city ? 'in '.$r->city : null, $r->service ? $r->service : null])->filter()->implode(' · ') ?: 'everywhere, every service' }}</div>
                        </td>
                        <td class="small text-end">{{ $r->basis === 'percent_of_delivery_fee' ? rtrim(rtrim(number_format($r->rate_bp / 100, 2), '0'), '.').'%' : str_replace('_', ' ', $r->basis) }}@if($r->min_fee)<div>min ₦{{ number_format($r->min_fee / 100) }}</div>@endif</td>
                        <td class="text-end">
                            @if($canFee)<form method="POST" action="{{ route('ops.settings.commission.toggle', $r->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-{{ $r->active ? 'secondary' : 'success' }}">{{ $r->active ? 'Switch off' : 'Switch on' }}</button></form>@else<span class="badge bg-{{ $r->active ? 'success' : 'secondary' }}">{{ $r->active ? 'on' : 'off' }}</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody></table></div>
                @endif
                @if($canFee)
                <form method="POST" action="{{ route('ops.settings.commission') }}" class="row g-2">@csrf
                    <div class="col-12">
                        <select name="applies_to" class="form-select form-select-sm" onchange="document.getElementById('cr-prov').classList.toggle('d-none', this.value!=='provider');document.getElementById('cr-type').classList.toggle('d-none', this.value!=='type');">
                            <option value="provider">For one provider</option><option value="type">For every provider of one kind</option>
                        </select>
                    </div>
                    <div class="col-12" id="cr-prov"><select name="operator_id" class="form-select form-select-sm"><option value="">Choose the provider…</option>@foreach($ruleProviders as $p)<option value="{{ $p->id }}">{{ $p->display_name }} ({{ str_replace('_', ' ', $p->type) }})</option>@endforeach</select></div>
                    <div class="col-12 d-none" id="cr-type"><select name="operator_type" class="form-select form-select-sm">@foreach($ruleTypes as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>@endforeach</select></div>
                    <div class="col-6"><select name="city_id" class="form-select form-select-sm"><option value="">Every city</option>@foreach($ruleCities as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div class="col-6"><select name="service_type_id" class="form-select form-select-sm"><option value="">Every service</option>@foreach($ruleServices as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
                    <div class="col-6"><div class="input-group input-group-sm"><input type="number" step="0.01" min="0" max="50" name="percent" class="form-control" placeholder="Fee" required><span class="input-group-text">%</span></div></div>
                    <div class="col-6"><div class="input-group input-group-sm"><span class="input-group-text">min ₦</span><input type="number" step="0.01" min="0" name="min_naira" class="form-control" placeholder="0"></div></div>
                    <div class="col-12"><button class="btn btn-sm btn-primary">Save special fee</button> <span class="form-text">Saving the same provider/kind, city and service again replaces its fee.</span></div>
                </form>
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

                    <label class="form-label">Seconds a driver has to answer a job offer</label>
                    <input type="number" min="10" max="300" name="offer_seconds" class="form-control mb-3" value="{{ old('offer_seconds', $offerSeconds) }}" required @disabled(!$canRules)>

                    <label class="form-label">Drivers asked before a job goes to the dispatcher</label>
                    <input type="number" min="1" max="20" name="max_attempts" class="form-control mb-3" value="{{ old('max_attempts', $maxAttempts) }}" required @disabled(!$canRules)>

                    <hr>
                    <h6 class="mb-1">If a delivery fails</h6>
                    <div class="form-text mb-3">When the receiver cannot be reached, refuses the parcel or the address is wrong. These terms are copied onto every new agreement, shown to the customer before they pay, and cannot change afterwards.</div>

                    <label class="form-label">Minutes the driver waits at the drop-off first</label>
                    <input type="number" min="0" max="120" name="failed_wait_minutes" class="form-control mb-3" value="{{ old('failed_wait_minutes', $failed['wait_minutes']) }}" required @disabled(!$canRules)>

                    <label class="form-label">Share of the delivery price the customer still pays</label>
                    <div class="input-group mb-3"><input type="number" step="0.01" min="0" max="100" name="failed_fee_percent" class="form-control" value="{{ old('failed_fee_percent', $failed['customer_fee_bp'] / 100) }}" required @disabled(!$canRules)><span class="input-group-text">%</span></div>

                    <label class="form-label">Does the driver bring the parcel back to the sender?</label>
                    <select name="failed_return" class="form-select mb-3" @disabled(!$canRules)>
                        <option value="yes" @selected(old('failed_return', $failed['return_to_sender'] ? 'yes' : 'no') === 'yes')>Yes, back to the pickup address</option>
                        <option value="no" @selected(old('failed_return', $failed['return_to_sender'] ? 'yes' : 'no') === 'no')>No, the provider sorts it out</option>
                    </select>

                    <label class="form-label">Extra the customer pays for the return trip</label>
                    <div class="input-group mb-1"><input type="number" step="0.01" min="0" max="100" name="failed_return_fee_percent" class="form-control" value="{{ old('failed_return_fee_percent', $failed['return_fee_bp'] / 100) }}" @disabled(!$canRules)><span class="input-group-text">% of the price</span></div>
                    <div class="form-text mb-3">Only applies when the parcel is returned. Zero means the provider covers the return. Together with the share above it can never be more than 100%.</div>

                    <label class="form-label">Is the driver paid for a failed trip?</label>
                    <select name="failed_driver_paid" class="form-select mb-3" @disabled(!$canRules)>
                        <option value="yes" @selected(old('failed_driver_paid', $failed['driver_paid'] ? 'yes' : 'no') === 'yes')>Yes, their usual share of what the company earns</option>
                        <option value="no" @selected(old('failed_driver_paid', $failed['driver_paid'] ? 'yes' : 'no') === 'no')>No</option>
                    </select>

                    @if($canRules)<button class="btn btn-primary">Save settings</button>@else<span class="text-muted small">You can view these but not change them.</span>@endif
                </form>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
