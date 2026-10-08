@extends('layouts.master')

@section('content')
@php
    $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0);
    $nice = fn ($t) => ucfirst(str_replace('_', ' ', $t));
    $list = fn ($v) => implode(', ', is_array($v) ? $v : (array) json_decode($v ?? '[]', true));
    $isRider = $op->type !== 'company';
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Profile and setup" icon="ri-settings-3-line" subtitle="Everything our team needs to approve you." :back="route('provider.dashboard')" back-label="Dashboard">
        <x-slot:pills><span class="cb-meta-pill">Application: {{ $nice($app->status ?? 'draft') }}</span></x-slot:pills>
    </x-cb.hero>
    @include('provider._flash')

    @if(($app->status ?? '') === 'needs_changes' && $app->review_note)
        <div class="cb-banner warning"><i class="ri-chat-1-line"></i><div>Our team asked for changes: {{ $app->review_note }}</div></div>
    @endif
    @if($locked)
        <div class="cb-banner info"><i class="ri-lock-line"></i><div>{{ ($app->status ?? '') === 'approved' ? 'Your application is approved.' : 'Your application is being reviewed.' }} You can change your profile and availability, but documents, prices and areas are locked.</div></div>
    @endif

    {{-- checklist --}}
    <x-cb.card title="Checklist" icon="ri-list-check-2" class="mb-3">
        @if(count($missing))
            <ul class="mb-0">@foreach($missing as $m)<li>{{ ucfirst($m) }}</li>@endforeach</ul>
        @else
            <div class="text-success"><i class="ri-checkbox-circle-line"></i> Everything is in place.</div>
        @endif
        @if(! $locked)
        <form method="POST" action="{{ route('provider.submit') }}" class="mt-3" onsubmit="return confirm('Submit for review? You will not be able to change documents or prices while we review.')">@csrf
            <button class="btn btn-success" @disabled(count($missing))>Submit for review</button>
        </form>
        @endif
    </x-cb.card>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Your public profile" icon="ri-user-star-line">
                <form method="POST" action="{{ route('provider.profile.save') }}">@csrf
                    <label class="form-label small">Name customers see</label>
                    <input name="display_name" class="form-control mb-2" value="{{ old('display_name', $op->display_name) }}" maxlength="120">
                    <label class="form-label small">Headline</label>
                    <input name="headline" class="form-control mb-2" value="{{ old('headline', $profile->headline ?? '') }}" maxlength="140" placeholder="Fast, careful deliveries across the city">
                    <label class="form-label small">About</label>
                    <textarea name="about" rows="3" class="form-control mb-2" maxlength="3000">{{ old('about', $profile->about ?? '') }}</textarea>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small">Years operating</label><input type="number" min="0" max="100" name="years_operating" class="form-control" value="{{ old('years_operating', $profile->years_operating ?? '') }}"></div>
                        <div class="col-6"><label class="form-label small">Smallest job (₦)</label><input type="number" step="0.01" min="0" name="min_job_value_naira" class="form-control" value="{{ old('min_job_value_naira', isset($profile->min_job_value) ? $profile->min_job_value / 100 : '') }}"></div>
                    </div>
                    <label class="form-label small mt-2">Languages <span class="text-muted">(comma separated)</span></label>
                    <input name="languages" class="form-control mb-2" value="{{ old('languages', $list($profile->languages ?? null)) }}">
                    <label class="form-label small">Areas you cover <span class="text-muted">(comma separated)</span></label>
                    <input name="coverage" class="form-control mb-3" value="{{ old('coverage', $list($profile->coverage ?? null)) }}">
                    <button class="btn btn-primary">Save profile</button>
                </form>
                <hr>
                <form method="POST" action="{{ route('provider.availability') }}" class="d-flex gap-2 align-items-center">@csrf
                    <label class="small mb-0">Right now I am</label>
                    <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        @foreach(['accepting' => 'taking jobs', 'busy' => 'busy', 'away' => 'away'] as $k => $v)<option value="{{ $k }}" @selected(($profile->availability_status ?? 'accepting') === $k)>{{ $v }}</option>@endforeach
                    </select>
                </form>
            </x-cb.card>

            <x-cb.card title="Where you work" icon="ri-map-pin-line" class="mt-3">
                <form method="POST" action="{{ route('provider.areas.save') }}">@csrf
                    <div style="max-height:220px;overflow:auto">
                    @foreach($zones as $z)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="zone_ids[]" value="{{ $z['id'] }}" id="z{{ $z['id'] }}" @checked($z['selected']) @disabled($locked)><label class="form-check-label" for="z{{ $z['id'] }}">{{ $z['name'] }}</label></div>
                    @endforeach
                    </div>
                    @if(! $locked)<button class="btn btn-primary mt-3">Save areas</button>@endif
                </form>
            </x-cb.card>
        </div>

        <div class="col-lg-6">
            <x-cb.card title="Documents" icon="ri-file-shield-2-line" :count="count($docs)">
                @foreach($docs as $d)
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span>{{ $nice($d->doc_type) }}@if($d->number_last4) <span class="text-muted small">····{{ $d->number_last4 }}</span>@endif
                            @if($d->rejection_reason)<div class="small text-danger">{{ $d->rejection_reason }}</div>@endif</span>
                        <span class="badge bg-{{ ['approved' => 'success', 'rejected' => 'danger'][$d->status] ?? 'warning' }}">{{ $d->status }}</span>
                    </div>
                @endforeach
                @if(! $locked)
                <form method="POST" action="{{ route('provider.document.upload') }}" enctype="multipart/form-data" class="mt-3 border-top pt-3">@csrf
                    <select name="doc_type" class="form-select mb-2" required>
                        <option value="">What are you uploading?</option>
                        @foreach($required as $t)<option value="{{ $t }}">{{ $nice($t) }}</option>@endforeach
                        @if($isRider)@foreach(['vehicle_registration', 'insurance', 'roadworthiness'] as $t)<option value="{{ $t }}">{{ $nice($t) }} (vehicle)</option>@endforeach @endif
                    </select>
                    @if($isRider && count($vehicles))
                    <select name="vehicle_id" class="form-select mb-2"><option value="">For which vehicle? (vehicle documents only)</option>
                        @foreach($vehicles as $v)<option value="{{ $v['public_id'] }}">{{ $v['plate'] }}</option>@endforeach
                    </select>
                    @endif
                    <input name="number" class="form-control mb-2" placeholder="Document number (optional)" maxlength="40">
                    <input type="date" name="expires_on" class="form-control mb-2" min="{{ now()->addDay()->toDateString() }}" title="Expiry date, if it has one">
                    <input type="file" name="file" class="form-control mb-2" accept=".jpg,.jpeg,.png,.pdf" required>
                    <button class="btn btn-outline-primary w-100">Upload</button>
                    <div class="form-text">JPG, PNG or PDF, up to 5 MB. Only our review team can open your documents.</div>
                </form>
                @endif
            </x-cb.card>

            @if($isRider)
            <x-cb.card title="Vehicles" icon="ri-motorbike-line" class="mt-3" :count="count($vehicles)">
                @foreach($vehicles as $v)<div class="mb-1">{{ $v['plate'] }} <span class="text-muted small">{{ $v['vehicle_type'] }} · {{ trim(($v['make'] ?? '').' '.($v['model'] ?? '')) }}</span></div>@endforeach
                @if(! $locked)
                <form method="POST" action="{{ route('provider.vehicle.add') }}" class="mt-3 border-top pt-3">@csrf
                    <select name="vehicle_type_id" class="form-select mb-2" required>
                        <option value="">Vehicle type</option>
                        @foreach($vehicleTypes as $t)<option value="{{ $t['id'] }}">{{ $t['name'] }}</option>@endforeach
                    </select>
                    <input name="plate" class="form-control mb-2" placeholder="Plate number" maxlength="24" required>
                    <div class="row g-2 mb-2"><div class="col"><input name="make" class="form-control" placeholder="Make"></div><div class="col"><input name="model" class="form-control" placeholder="Model"></div></div>
                    <button class="btn btn-outline-primary w-100">Add vehicle</button>
                </form>
                @endif
            </x-cb.card>
            @endif

            <x-cb.card title="Your prices" icon="ri-price-tag-3-line" class="mt-3" :count="count($cards)">
                @foreach($cards as $c)
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span>{{ $serviceTypes[$c['service_type_id']]['name'] ?? 'Service' }}
                            <div class="small text-muted">Base {{ $naira($c['base']) }} + {{ $naira($c['per_km']) }}/km @if($c['per_kg']) + {{ $naira($c['per_kg']) }}/kg @endif @if($c['negotiable'])· negotiable @endif</div></span>
                        @if(! $locked)<form method="POST" action="{{ route('provider.ratecard.delete', $c['id']) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">Remove</button></form>@endif
                    </div>
                @endforeach
                @if(! $locked)
                <form method="POST" action="{{ route('provider.ratecard.save') }}" class="mt-3 border-top pt-3">@csrf
                    <select name="service_type_id" class="form-select mb-2" required>
                        <option value="">Service</option>
                        @foreach($serviceTypes as $t)<option value="{{ $t['id'] }}">{{ $t['name'] }}</option>@endforeach
                    </select>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><input type="number" step="0.01" min="0" name="base_naira" class="form-control" placeholder="Base ₦" required></div>
                        <div class="col-4"><input type="number" step="0.01" min="0" name="per_km_naira" class="form-control" placeholder="Per km ₦" required></div>
                        <div class="col-4"><input type="number" step="0.01" min="0" name="per_kg_naira" class="form-control" placeholder="Per kg ₦"></div>
                    </div>
                    <input type="number" step="0.01" min="0" name="min_fee_naira" class="form-control mb-2" placeholder="Smallest charge ₦ (optional)">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="negotiable" value="1" id="neg" checked><label class="form-check-label" for="neg">Customers can negotiate this price</label></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="instant_book" value="1" id="inst"><label class="form-check-label" for="inst">Customers can book instantly at this price</label></div>
                    <button class="btn btn-outline-primary w-100">Add price</button>
                </form>
                @endif
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
