@extends('layouts.master')

@section('content')
@php $apps = \App\Modules\Drivers\DriverApplicationService::class; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Driver applications" icon="ri-steering-2-line" subtitle="People who applied on the Drive with us page. Check their documents, tick the checks, and approve them into the fleet." :back="route('ops.dashboard')" back-label="Operations">
        <x-slot:pills>
            <span class="cb-meta-pill">Fleet: {{ $fleet ?: 'not set' }}</span>
            <span class="cb-meta-pill"><a class="text-reset" href="{{ route('drive') }}" target="_blank">Public form</a></span>
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')
    @unless($ready)<div class="alert alert-warning">Set <code>DRIVE_OPERATOR_ID</code> (or <code>INSTANT_OPERATOR_ID</code>) to the operator approved drivers join. Until then the public form says you're not taking drivers.</div>@endunless

    <div class="mb-3 d-flex gap-2">
        @foreach(['new' => 'To review', 'approved' => 'Approved', 'declined' => 'Declined'] as $k => $v)
            <a href="{{ route('ops.driver-apps', ['status' => $k]) }}" class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-light' }}">{{ $v }} <span class="badge bg-white text-dark ms-1">{{ (int) ($counts[$k] ?? 0) }}</span></a>
        @endforeach
    </div>

    @forelse($rows as $a)
        <x-cb.card :title="$a->name" icon="ri-user-line" class="mb-3">
            <x-slot:tools><span class="small text-muted">Applied {{ \Illuminate\Support\Carbon::parse($a->created_at)->diffForHumans() }}</span></x-slot:tools>
            <div class="row g-3">
                <div class="col-lg-4 small">
                    <div>{{ $a->email }}</div>
                    <div>{{ \App\Support\Phone::display($a->phone) }} @if($a->sms_consent)<span class="badge bg-success-subtle text-success">texts OK</span>@else<span class="badge bg-light text-muted">no texts</span>@endif</div>
                    <div class="text-muted">{{ $a->city }} {{ $a->subdivision }} {{ $a->postal_code }}</div>
                    <div class="mt-2"><strong>Experience:</strong> {{ $apps::EXPERIENCE[$a->experience] ?? $a->experience }}</div>
                    @php $av = json_decode((string) $a->availability, true) ?: []; @endphp
                    @if($av)<div><strong>Available:</strong> {{ implode(', ', array_map(fn ($k) => $apps::AVAILABILITY[$k] ?? $k, $av)) }}</div>@endif
                    @if($a->experience_notes)<div class="fst-italic mt-1">“{{ $a->experience_notes }}”</div>@endif
                </div>
                <div class="col-lg-4 small">
                    <div><strong>Licence:</strong> class {{ $a->licence_class }}, ending {{ $a->licence_number_last4 }}, expires {{ $a->licence_expires_on }}</div>
                    @if($a->plate)
                        <div class="mt-1"><strong>Vehicle:</strong> {{ $a->vehicle_year }} {{ $a->vehicle_make }} {{ $a->vehicle_model }} ({{ $a->vehicle_type }}), plate {{ $a->plate }}</div>
                        <div><strong>Insurance:</strong> {{ $a->insurance_company }}, policy ending {{ $a->insurance_policy_last4 }}, expires {{ $a->insurance_expires_on }}</div>
                    @else
                        <div class="mt-1 text-muted">Needs a vehicle</div>
                    @endif
                    <div class="mt-2 d-flex flex-wrap gap-1">
                        @foreach($apps::FILES as $kind => $label)
                            @if($a->{$kind.'_file'})<a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('ops.driver-app.file', [$a->id, $kind]) }}"><i class="ri-file-line"></i> {{ $label }}</a>@endif
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-4">
                    @if($a->status === 'new' && $canDecide)
                        <form method="POST" action="{{ route('ops.driver-app.approve', $a->id) }}" class="mb-2">@csrf
                            @foreach($apps::CHECKS as $k => $label)
                                @if($a->plate || ! in_array($k, ['insurance', 'registration'], true))
                                <div class="form-check small"><input class="form-check-input" type="checkbox" name="checks[]" value="{{ $k }}" id="c{{ $a->id }}{{ $k }}"><label class="form-check-label" for="c{{ $a->id }}{{ $k }}">{{ $label }}</label></div>
                                @endif
                            @endforeach
                            <input name="note" class="form-control form-control-sm my-2" placeholder="Note (optional)" maxlength="500">
                            <button class="btn btn-sm btn-success w-100">Approve into the fleet</button>
                        </form>
                        <form method="POST" action="{{ route('ops.driver-app.decline', $a->id) }}" onsubmit="return confirm('Decline this application?')">@csrf
                            <textarea name="note" class="form-control form-control-sm mb-1" rows="2" placeholder="Reason (sent to them by email)" maxlength="500" required></textarea>
                            <div class="form-check small mb-1"><input type="hidden" name="tell" value="0"><input class="form-check-input" type="checkbox" name="tell" value="1" id="t{{ $a->id }}" checked><label class="form-check-label" for="t{{ $a->id }}">Email them</label></div>
                            <button class="btn btn-sm btn-outline-danger w-100">Decline</button>
                        </form>
                    @elseif($a->status !== 'new')
                        <span class="badge {{ $a->status === 'approved' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ ucfirst($a->status) }}</span>
                        <span class="small text-muted">{{ $a->decided_at ? \Illuminate\Support\Carbon::parse($a->decided_at)->diffForHumans() : '' }}</span>
                        @if($a->decision_note)<div class="small mt-1">{{ $a->decision_note }}</div>@endif
                    @endif
                </div>
            </div>
        </x-cb.card>
    @empty
        <div class="text-muted">Nothing here.</div>
    @endforelse
</div></div></div>
@endsection
