@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Arrival alerts" icon="ri-notification-3-line" subtitle="When the job giver and the person receiving the parcel hear that your rider is close. Each alert goes out once per stop." />
    @include('provider._flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Set alerts" icon="ri-edit-line">
                @if(!$canEdit)
                    <p class="text-muted small mb-0">Ask an owner or admin to change these.</p>
                @else
                <form method="POST" action="{{ route('provider.alerts.save') }}">@csrf
                    <div class="fw-semibold small mb-1">Minutes before the rider arrives</div>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        @foreach($choices as $m)
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="eta_minutes[]" value="{{ $m }}" id="eta_{{ $m }}" @checked(in_array($m, $cfg['eta_minutes'], true))><label class="form-check-label small" for="eta_{{ $m }}">{{ $m }} min</label></div>
                        @endforeach
                    </div>

                    <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="near" value="1" id="near" @checked($cfg['near_m'])><label class="form-check-label small" for="near">When the rider is within</label></div>
                    <div class="input-group input-group-sm mb-3" style="max-width:220px"><input type="number" class="form-control" name="near_m" min="100" max="2000" step="50" value="{{ $cfg['near_m'] ?? 500 }}"><span class="input-group-text">metres</span></div>

                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="arrived" value="1" id="arrived" @checked($cfg['arrived'])><label class="form-check-label small" for="arrived">When the rider arrives at the drop-off</label></div>

                    <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="delay" value="1" id="delay" @checked($cfg['delay_minutes'])><label class="form-check-label small" for="delay">When the rider is running late by</label></div>
                    <div class="input-group input-group-sm mb-3" style="max-width:220px"><input type="number" class="form-control" name="delay_minutes" min="5" max="60" value="{{ $cfg['delay_minutes'] ?? 10 }}"><span class="input-group-text">minutes or more</span></div>

                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="sms" value="1" id="sms" @checked($cfg['sms'])><label class="form-check-label small" for="sms">Text the person receiving the parcel (texts cost money; the job giver always gets an app notification)</label></div>

                    <button class="btn btn-primary w-100">Save alerts</button>
                </form>
                @endif
            </x-cb.card>
        </div>
        <div class="col-lg-5">
            <x-cb.card title="How it works" icon="ri-information-line">
                <ul class="small mb-0">
                    <li>Arrival times are shown as a range, like "10 to 15 min", worked out from the road route and the rider's last position.</li>
                    <li>If the rider's phone has not sent a position for a couple of minutes, the range widens and is marked as a rough guess.</li>
                    <li>If two alerts are due at once (the rider is already 3 minutes away when they set off), only the closer one is sent.</li>
                    <li>The receiver's text has a link where they can tell the rider "I need 10 more minutes" or "Call me when you arrive". The rider sees it on the job.</li>
                    <li>Every alert sent is listed on the job page.</li>
                </ul>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
