@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Sign-in security" icon="ri-shield-keyhole-line" subtitle="Who must use two-step sign-in, and who has it on.">
        <x-slot:actions>
            <a href="{{ route('security.index') }}" class="cb-hero-btn"><i class="ri-user-settings-line"></i>My sign-in security</a>
            @can('View audit log')<a href="{{ route('audit.index') }}" class="cb-hero-btn"><i class="ri-file-list-3-line"></i>Data changes</a>@endcan
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-4">
            <x-cb.card title="Rules" icon="ri-settings-3-line">
                <form method="POST" action="{{ route('admin.security.save') }}">@csrf
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="staff" value="1" id="r-staff" @checked($staffRequired) @disabled(!$canManage)>
                        <label class="form-check-label" for="r-staff"><strong>All platform staff</strong><div class="small text-muted">Everyone with a staff role.</div></label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="partners" value="1" id="r-partners" @checked($partnersRequired) @disabled(!$canManage)>
                        <label class="form-check-label" for="r-partners"><strong>Everyone managing a provider or merchant</strong><div class="small text-muted">Owners, admins, dispatchers, finance and support. Drivers are never asked: they sign in on the driver app.</div></label>
                    </div>
                    @if($canManage)<button class="btn btn-primary w-100">Save rules</button>@endif
                    <div class="form-text">When a rule is on, people it covers are asked to set it up on their next page. Each provider can also require it for its own team from its Team page.</div>
                </form>
            </x-cb.card>
            <x-cb.card title="Partner managers" icon="ri-team-line" class="mt-3">
                <div class="fs-4 fw-semibold">{{ (int) $partners->with_two_factor }} / {{ (int) $partners->people }}</div>
                <div class="small text-muted">have two-step sign-in on</div>
            </x-cb.card>
        </div>
        <div class="col-lg-8">
            <x-cb.card title="Staff" icon="ri-user-star-line" :count="count($staff)" :flush="true">
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Person</th><th>Two-step sign-in</th><th></th></tr></thead>
                    <tbody>
                    @foreach($staff as $s)
                        <tr>
                            <td>{{ $s->name }}<div class="small text-muted">{{ $s->email }}</div></td>
                            <td>@if($s->two_factor_confirmed_at)<span class="status-pill st-paid"><i class="ri-shield-check-line"></i> On</span>@else<span class="status-pill st-muted">Off</span>@endif</td>
                            <td class="text-end">
                                @if($canReset && $s->two_factor_confirmed_at && $s->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.security.reset', $s->id) }}" onsubmit="return confirm('Reset two-step sign-in for {{ addslashes($s->name) }}? Only do this if you have confirmed it is really them.')">@csrf
                                    <button class="action-btn btn-open"><i class="ri-restart-line"></i>Reset</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </x-cb.card>
            <div class="small text-muted mt-2">Reset is for someone who lost their phone and their recovery codes. It is recorded in Data changes.</div>
        </div>
    </div>
</div></div></div>
@endsection
