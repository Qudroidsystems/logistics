@extends('layouts.master')

@section('content')
@php $L = \App\Modules\Verification\VerificationPolicy::LABELS; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Delivery checks" icon="ri-shield-check-line" subtitle="What your drivers must collect before a pickup or drop-off counts as done. Customers see these on your offers and can add more." />
    @include('provider._flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Your checks now" icon="ri-list-check-2" :flush="true">
                <table class="table align-middle mb-0"><thead><tr><th>Applies to</th><th>Pickup</th><th>Drop-off</th><th></th></tr></thead><tbody>
                    <tr>
                        <td class="fw-semibold">Every service (default)</td>
                        <td class="small">{{ $default['pickup'] ? implode(', ', array_map(fn ($m) => $L[$m], $default['pickup'])) : 'Being there' }}</td>
                        <td class="small">{{ implode(', ', array_map(fn ($m) => $L[$m], $default['dropoff'])) }}</td>
                        <td>@if(!isset($saved['default']))<span class="badge bg-light text-dark">Platform default</span>@endif</td>
                    </tr>
                    @foreach($saved as $sid => $p)
                        @continue($sid === 'default')
                        <tr>
                            <td class="fw-semibold">{{ $serviceTypes[$sid] ?? 'Service '.$sid }}</td>
                            <td class="small">{{ $p['pickup'] ? implode(', ', array_map(fn ($m) => $L[$m], $p['pickup'])) : 'Being there' }}</td>
                            <td class="small">{{ implode(', ', array_map(fn ($m) => $L[$m], $p['dropoff'])) }}</td>
                            <td class="text-end">@if($canEdit)<form method="POST" action="{{ route('provider.verification.remove', $sid) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Remove</button></form>@endif</td>
                        </tr>
                    @endforeach
                </tbody></table>
            </x-cb.card>
            <x-cb.card title="How it works" icon="ri-information-line" class="mt-3">
                <ul class="small mb-0">
                    <li>When a customer accepts your offer, your checks plus anything they asked for are written into the agreement. Neither side can drop one later.</li>
                    <li>The delivery code is random, works once, expires after {{ \App\Modules\Verification\DeliveryCodes::CODE_TTL_HOURS }} hours and locks after {{ \App\Modules\Verification\DeliveryCodes::MAX_ATTEMPTS }} wrong tries. The receiver gets it by text when a driver is assigned.</li>
                    <li>"Scan every parcel" stops a driver finishing while any parcel on the job is unscanned.</li>
                    <li>If a driver cannot provide a check, they ask for an exception with a reason. It appears on the job page for you to approve or decline.</li>
                    <li>Every scan, proof and decision is chained with a hash, so a record changed later shows up on the job page.</li>
                </ul>
            </x-cb.card>
        </div>
        <div class="col-lg-5">
            <x-cb.card title="Set checks" icon="ri-edit-line">
                @if(!$canEdit)
                    <p class="text-muted small mb-0">Ask an owner or admin to change these.</p>
                @else
                <form method="POST" action="{{ route('provider.verification.save') }}">@csrf
                    <label class="form-label small">Applies to</label>
                    <select name="service_type_id" class="form-select mb-3">
                        <option value="">Every service (default)</option>
                        @foreach($serviceTypes as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                    <div class="fw-semibold small mb-1">At pickup</div>
                    @foreach($pickupOptions as $k => $label)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="pickup[]" value="{{ $k }}" id="pu_{{ $k }}" @checked(in_array($k, $default['pickup'], true))><label class="form-check-label small" for="pu_{{ $k }}">{{ $label }}</label></div>
                    @endforeach
                    <div class="fw-semibold small mb-1 mt-3">At drop-off <span class="text-muted fw-normal">(at least one)</span></div>
                    @foreach($dropoffOptions as $k => $label)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="dropoff[]" value="{{ $k }}" id="do_{{ $k }}" @checked(in_array($k, $default['dropoff'], true))><label class="form-check-label small" for="do_{{ $k }}">{{ $label }}</label></div>
                    @endforeach
                    <button class="btn btn-primary w-100 mt-3">Save checks</button>
                    <div class="small text-muted mt-1">Jobs already agreed keep the checks they were agreed with.</div>
                </form>
                @endif
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
