@php
    $tfSvc = app(\App\Modules\Identity\Security\TwoFactorService::class);
    $tfRequired = $tfSvc->setting(\App\Modules\Identity\Security\TwoFactorService::KEY_TEAM, (int) $op->id);
    $tfManagers = collect($members)->where('role', '!=', 'driver');
    $tfOn = \Illuminate\Support\Facades\DB::table('users')->whereIn('id', $tfManagers->pluck('user_id'))->whereNotNull('two_factor_confirmed_at')->pluck('id')->all();
    $tfMissing = $tfManagers->reject(fn ($m) => in_array($m->user_id, $tfOn));
@endphp
<x-cb.card title="Two-step sign-in" icon="ri-shield-keyhole-line" class="mt-3">
    <div class="mb-2"><strong>{{ count($tfOn) }} / {{ $tfManagers->count() }}</strong> <span class="text-muted">of your managers have it on.</span></div>
    @if($tfMissing->isNotEmpty())<div class="small text-muted mb-2">Not yet: {{ $tfMissing->pluck('name')->join(', ') }}</div>@endif
    @if(in_array($role, ['owner', 'admin'], true))
    <form method="POST" action="{{ route('provider.team.security') }}">@csrf
        <input type="hidden" name="required" value="{{ $tfRequired ? 0 : 1 }}">
        <button class="btn btn-sm {{ $tfRequired ? 'btn-outline-secondary' : 'btn-outline-primary' }} w-100">{{ $tfRequired ? 'Stop requiring it' : 'Require it for my team' }}</button>
    </form>
    <div class="form-text">{{ $tfRequired ? 'Required for everyone on the team except drivers.' : 'Everyone except drivers would have to set it up before using this workspace.' }} <a href="{{ route('security.index') }}">Your own setting</a></div>
    @endif
</x-cb.card>
