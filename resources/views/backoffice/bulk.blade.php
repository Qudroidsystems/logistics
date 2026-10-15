@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Bulk booking" icon="ri-file-list-3-line" subtitle="Book many deliveries at once from a spreadsheet. We check every row first; nothing is booked until you confirm." :back="route('provider.booking.new')" back-label="New booking" />
    @include('provider._flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('provider.bulk.check') }}" enctype="multipart/form-data">@csrf
                <x-cb.card title="1. The spreadsheet" icon="ri-upload-2-line">
                    <p class="small text-muted mb-2">Save your sheet as CSV, one delivery per row, under the template's headings. Up to {{ $maxRows }} rows at a time.</p>
                    <input type="file" name="file" accept=".csv,text/csv" class="form-control mb-2" required>
                    <a href="{{ route('provider.bulk.template') }}" class="small"><i class="ri-download-2-line"></i> Download the template</a>
                </x-cb.card>

                <x-cb.card title="2. What applies to every row" icon="ri-settings-3-line">
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label small">Service</label>
                            <select name="service_type_id" class="form-select" required>@foreach($serviceTypes as $t)<option value="{{ $t->id }}" @selected(old('service_type_id') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label small">City</label>
                            <select name="city_id" class="form-select" required>@foreach($cities as $c)<option value="{{ $c->id }}" @selected(old('city_id') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label small">Vehicle <span class="text-muted">(optional)</span></label>
                            <select name="vehicle_type_id" class="form-select"><option value="">Any</option>@foreach($vehicleTypes as $v)<option value="{{ $v->id }}" @selected(old('vehicle_type_id') == $v->id)>{{ $v->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label small">Payment</label>
                            <select name="payment" class="form-select" required>@foreach($payments as $k => $v)@if($k !== 'account' || ! empty($businessAccounts))<option value="{{ $k }}" @selected(old('payment', 'wallet') === $k)>{{ $v }}</option>@endif @endforeach</select></div>
                        @if(! empty($businessAccounts))
                            <div class="col-md-6"><label class="form-label small">Business account <span class="text-muted">(for On account)</span></label>
                                <select name="business_account" class="form-select"><option value="">Choose</option>@foreach($businessAccounts as $ba)<option value="{{ $ba->public_id }}" @selected(old('business_account') === $ba->public_id)>{{ $ba->name }}</option>@endforeach</select></div>
                        @endif
                    </div>

                    <div class="fw-semibold small mt-3 mb-1">The customer paying</div>
                    <div class="form-text mb-1">Usually the business that sent the sheet. A row with its own customer columns is booked for that customer instead.</div>
                    <div class="row g-2">
                        <div class="col-md-4"><input name="customer[phone]" class="form-control" placeholder="Phone" value="{{ old('customer.phone') }}" inputmode="tel"></div>
                        <div class="col-md-4"><input name="customer[email]" type="email" class="form-control" placeholder="Email" value="{{ old('customer.email') }}"></div>
                        <div class="col-md-4"><input name="customer[name]" class="form-control" placeholder="Name" value="{{ old('customer.name') }}"></div>
                    </div>

                    <div class="fw-semibold small mt-3 mb-1">The usual pickup</div>
                    <div class="form-text mb-1">Where the rider collects the parcels. A row with its own pickup columns uses that instead.</div>
                    <div class="row g-2">
                        <div class="col-12"><input name="pickup[line1]" class="form-control" placeholder="Pickup address" value="{{ old('pickup.line1') }}"></div>
                        <div class="col-md-4"><input name="pickup[lat]" class="form-control" placeholder="Latitude, like 6.5244" value="{{ old('pickup.lat') }}" inputmode="decimal"></div>
                        <div class="col-md-4"><input name="pickup[lng]" class="form-control" placeholder="Longitude, like 3.3792" value="{{ old('pickup.lng') }}" inputmode="decimal"></div>
                        <div class="col-md-4"><input name="pickup[contact_phone]" class="form-control" placeholder="Pickup phone" value="{{ old('pickup.contact_phone') }}" inputmode="tel"></div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3">Check the spreadsheet</button>
                </x-cb.card>
            </form>
        </div>

        <div class="col-lg-5">
            <x-cb.card title="Columns" icon="ri-table-line">
                <ul class="small mb-0 ps-3">
                    @foreach($columns as $k => $help)<li><code>{{ $k }}</code>@if($help) {{ $help }}@endif</li>@endforeach
                </ul>
                <p class="small text-muted mt-2 mb-0">Latitude and longitude can be copied from Google Maps: press and hold the spot, then copy the two numbers.</p>
            </x-cb.card>

            <x-cb.card title="Recent uploads" icon="ri-history-line" :count="count($batches)" :flush="true">
                <div class="list-group list-group-flush">
                    @forelse($batches as $b)
                        <a href="{{ route('provider.bulk.show', $b->public_id) }}" class="list-group-item list-group-item-action small">
                            <div class="d-flex justify-content-between"><span class="fw-semibold text-truncate">{{ $b->filename ?? 'Upload' }}</span><span class="text-muted">{{ \Illuminate\Support\Carbon::parse($b->created_at)->diffForHumans() }}</span></div>
                            @if($b->status === 'booked'){{ $b->booked_count }} booked @if($b->failed_count)· {{ $b->failed_count }} failed @endif
                            @else {{ $b->ok_count }} of {{ $b->row_count }} ready, not booked yet @endif
                        </a>
                    @empty
                        <div class="list-group-item small text-muted">No uploads yet.</div>
                    @endforelse
                </div>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
