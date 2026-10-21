@extends('layouts.master')

@section('content')
@php
    $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0);
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : '—';
    $dueCount = collect($vehicles)->filter(fn ($v) => count($v['due']))->count();
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Vehicles" icon="ri-car-line" subtitle="Papers, servicing and fuel for every vehicle. You get a reminder 30 days and 7 days before insurance, roadworthiness, a service or a driver's licence is due, on the day, and once if it passes." :back="route('provider.dashboard')" back-label="Dashboard">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ count($vehicles) }} {{ count($vehicles) === 1 ? 'vehicle' : 'vehicles' }}</span>
            @if($dueCount)<span class="cb-meta-pill">{{ $dueCount }} need attention</span>@endif
        </x-slot:pills>
        @if(Route::has('provider.onboarding'))<x-slot:actions><a class="cb-hero-btn" href="{{ route('provider.onboarding') }}#vehicles"><i class="ri-add-line me-1"></i>Add a vehicle</a></x-slot:actions>@endif
    </x-cb.hero>
    @include('provider._flash')

    <x-cb.card title="Fleet" icon="ri-car-line" :count="count($vehicles)" :flush="true" class="mb-3">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Vehicle</th><th>Driver</th><th>Papers and service</th><th>Capacity</th><th class="text-end">Fuel this month</th>@if($canEdit)<th></th>@endif</tr></thead>
            <tbody>
            @forelse($vehicles as $v)
                <tr>
                    <td><div class="fw-semibold">{{ $v['plate'] }}</div><div class="small text-muted">{{ $v['name'] }} · {{ $v['status'] }}@if($v['odometer_km'] !== null) · {{ number_format($v['odometer_km']) }} km @endif</div></td>
                    <td>{{ $v['driver'] ?? '—' }}</td>
                    <td>
                        @forelse($v['due'] as $d)
                            <div><span class="badge bg-{{ $d['level'] === 'danger' ? 'danger' : ($d['level'] === 'warning' ? 'warning' : 'info') }}-subtle text-{{ $d['level'] === 'danger' ? 'danger' : ($d['level'] === 'warning' ? 'warning' : 'info') }}">{{ $d['label'] }}</span></div>
                        @empty
                            <span class="small text-success">Nothing due in the next 30 days</span>
                        @endforelse
                        <div class="small text-muted">Insurance {{ $date($v['insurance_expires_on']) }} · Roadworthy {{ $date($v['roadworthy_expires_on']) }}</div>
                        <div class="small text-muted">Last service {{ $date($v['last_serviced_on']) }}@if($v['next_service_on']) · next {{ $date($v['next_service_on']) }}@endif @if($v['next_service_km']) · at {{ number_format($v['next_service_km']) }} km @endif</div>
                    </td>
                    <td class="small">
                        @if($v['capacity']['kg'])<div>{{ $v['capacity']['kg'] }} kg</div>@endif
                        @if($v['capacity']['parcels'])<div>{{ $v['capacity']['parcels'] }} parcels</div>@endif
                        @if($v['capacity']['volume_l'])<div class="text-muted">{{ $v['capacity']['volume_l'] }} L space</div>@endif
                    </td>
                    <td class="text-end small">
                        @if($v['fuel_month']['fills'])<div class="fw-semibold">{{ $naira($v['fuel_month']['cost']) }}</div><div class="text-muted">{{ $v['fuel_month']['litres'] }} L · {{ $v['fuel_month']['fills'] }} {{ $v['fuel_month']['fills'] === 1 ? 'fill' : 'fills' }}</div>@else<span class="text-muted">None</span>@endif
                    </td>
                    @if($canEdit)
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#veh-{{ $v['id'] }}">Update</button>
                    </td>
                    @endif
                </tr>
                @if($canEdit)
                <tr class="collapse" id="veh-{{ $v['id'] }}"><td colspan="6" class="bg-light">
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <h6 class="mb-2">Papers, service plan and capacity</h6>
                            <form method="POST" action="{{ route('provider.vehicles.care', $v['id']) }}" class="row g-2">@csrf @method('PUT')
                                <div class="col-6"><label class="form-label small mb-0">Insurance expires</label><input type="date" name="insurance_expires_on" class="form-control form-control-sm" value="{{ $v['insurance_expires_on'] }}"></div>
                                <div class="col-6"><label class="form-label small mb-0">Roadworthiness expires</label><input type="date" name="roadworthy_expires_on" class="form-control form-control-sm" value="{{ $v['roadworthy_expires_on'] }}"></div>
                                <div class="col-6"><label class="form-label small mb-0">Service every (days)</label><input type="number" name="service_interval_days" min="1" class="form-control form-control-sm" value="{{ $v['service_interval_days'] }}" placeholder="e.g. 90"></div>
                                <div class="col-6"><label class="form-label small mb-0">Service every (km)</label><input type="number" name="service_interval_km" min="100" class="form-control form-control-sm" value="{{ $v['service_interval_km'] }}" placeholder="e.g. 5000"></div>
                                <div class="col-6"><label class="form-label small mb-0">Next service date</label><input type="date" name="next_service_on" class="form-control form-control-sm" value="{{ $v['next_service_on'] }}"></div>
                                <div class="col-6"><label class="form-label small mb-0">Next service at (km)</label><input type="number" name="next_service_km" min="0" class="form-control form-control-sm" value="{{ $v['next_service_km'] }}"></div>
                                <div class="col-6"><label class="form-label small mb-0">Odometer now (km)</label><input type="number" name="odometer_km" min="0" class="form-control form-control-sm" value="{{ $v['odometer_km'] }}"></div>
                                <div class="col-6"><label class="form-label small mb-0">Carries up to (parcels)</label><input type="number" name="capacity_parcels" min="1" class="form-control form-control-sm" value="{{ $v['capacity']['parcels'] }}"></div>
                                <div class="col-12"><button class="btn btn-sm btn-primary">Save</button></div>
                            </form>
                        </div>
                        <div class="col-lg-3">
                            <h6 class="mb-2">Log a service</h6>
                            <form method="POST" action="{{ route('provider.vehicles.service', $v['id']) }}" class="row g-2">@csrf
                                <div class="col-12"><input type="date" name="serviced_on" class="form-control form-control-sm" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
                                <div class="col-6"><input type="number" name="odometer_km" min="0" class="form-control form-control-sm" placeholder="Odometer km"></div>
                                <div class="col-6"><input type="number" name="cost_naira" min="0" step="0.01" class="form-control form-control-sm" placeholder="Cost ₦"></div>
                                <div class="col-12"><input name="description" maxlength="300" class="form-control form-control-sm" placeholder="What was done (optional)"></div>
                                <div class="col-12"><button class="btn btn-sm btn-outline-primary">Record service</button></div>
                            </form>
                        </div>
                        <div class="col-lg-4">
                            <h6 class="mb-2">Log fuel</h6>
                            <form method="POST" action="{{ route('provider.vehicles.fuel', $v['id']) }}" class="row g-2">@csrf
                                <div class="col-6"><input type="date" name="filled_on" class="form-control form-control-sm" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
                                <div class="col-6"><input type="number" name="litres" min="0.1" step="0.01" class="form-control form-control-sm" placeholder="Litres" required></div>
                                <div class="col-6"><input type="number" name="cost_naira" min="1" step="0.01" class="form-control form-control-sm" placeholder="Cost ₦" required></div>
                                <div class="col-6"><input type="number" name="odometer_km" min="0" class="form-control form-control-sm" placeholder="Odometer km"></div>
                                <div class="col-12"><input name="note" maxlength="200" class="form-control form-control-sm" placeholder="Note (optional)"></div>
                                <div class="col-12"><button class="btn btn-sm btn-outline-primary">Record fuel</button></div>
                            </form>
                        </div>
                    </div>
                </td></tr>
                @endif
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No vehicles yet. Add them under Profile and setup.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Recent fuel" icon="ri-gas-station-line" :count="count($history['fuel'])" :flush="true">
                <table class="table table-sm align-middle mb-0"><tbody>
                @forelse($history['fuel'] as $f)
                    <tr><td>{{ $date($f['on']) }}</td><td class="fw-semibold">{{ $f['plate'] }}</td><td>{{ rtrim(rtrim(number_format($f['litres'], 2), '0'), '.') }} L</td><td class="text-end">{{ $naira($f['cost']) }}</td>
                        <td class="small text-muted">{{ $f['driver'] }}@if($f['odometer_km']) · {{ number_format($f['odometer_km']) }} km @endif @if($f['note']) · {{ $f['note'] }}@endif</td></tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No fuel recorded yet.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>
        <div class="col-lg-6">
            <x-cb.card title="Recent services" icon="ri-tools-line" :count="count($history['service'])" :flush="true">
                <table class="table table-sm align-middle mb-0"><tbody>
                @forelse($history['service'] as $s)
                    <tr><td>{{ $date($s['on']) }}</td><td class="fw-semibold">{{ $s['plate'] }}</td><td class="small">{{ $s['description'] ?: 'Service' }}@if($s['odometer_km']) · {{ number_format($s['odometer_km']) }} km @endif</td><td class="text-end">{{ $s['cost'] ? $naira($s['cost']) : '' }}</td></tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No services recorded yet.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
