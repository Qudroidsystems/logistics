@extends('layouts.master')

@section('content')
@php $label = fn ($r) => ucwords(str_replace('_', ' ', $r)); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Team" icon="ri-team-line" subtitle="People who work for {{ $op->display_name }}." :back="route('provider.dashboard')" back-label="Dashboard" />
    @include('provider._flash')

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Members" icon="ri-user-3-line" :count="count($members)" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Person</th><th>Role</th><th>Driver</th><th></th></tr></thead>
                    <tbody>
                    @foreach($members as $m)
                        @php $canEdit = $m->role !== 'owner' && $m->user_id !== $me && in_array($m->role, $manageable, true); @endphp
                        <tr>
                            <td class="fw-semibold">{{ $m->name }}<div class="small text-muted fw-normal">{{ $m->email }}</div></td>
                            <td>
                                @if($canEdit)
                                <form method="POST" action="{{ route('provider.team.role', $m->user_id) }}" class="d-flex gap-1">@csrf @method('PUT')
                                    <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                        @foreach($manageable as $r)<option value="{{ $r }}" @selected($m->role === $r)>{{ $label($r) }}</option>@endforeach
                                    </select>
                                </form>
                                @else {{ $label($m->role) }} @endif
                            </td>
                            <td>
                                @if($m->role === 'driver')
                                    <span class="badge bg-{{ $m->driver_status === 'active' ? 'success' : ($m->driver_status === 'suspended' ? 'danger' : 'warning') }}">{{ $m->driver_status ?? 'applied' }}</span>
                                    @if($canEdit)
                                    <form method="POST" action="{{ route('provider.team.driver', $m->user_id) }}" class="d-inline">@csrf @method('PUT')
                                        @if($m->driver_status !== 'active')<button name="status" value="active" class="btn btn-sm btn-success">Approve</button>
                                        @else<button name="status" value="suspended" class="btn btn-sm btn-outline-danger">Suspend</button>@endif
                                    </form>
                                    <form method="POST" action="{{ route('provider.team.pay', $m->user_id) }}" class="d-flex gap-1 align-items-center mt-1">@csrf @method('PUT')
                                        <input type="number" name="percent" min="0" max="100" step="0.5" value="{{ $m->pay_share_bp ? rtrim(rtrim(number_format($m->pay_share_bp / 100, 2, '.', ''), '0'), '.') : 0 }}" class="form-control form-control-sm" style="width:5.5rem" title="Share of each job's net paid to this driver (0 = you pay them yourself)">
                                        <span class="small text-muted">% of job</span>
                                        <button class="btn btn-sm btn-outline-primary">Save</button>
                                    </form>
                                    @if($m->driver_status === 'active' && count($vehicles))
                                    <form method="POST" action="{{ route('provider.team.vehicle', $m->user_id) }}" class="d-flex gap-1 mt-1">@csrf @method('PUT')
                                        <select name="vehicle_id" class="form-select form-select-sm">
                                            @foreach($vehicles as $v)<option value="{{ $v->public_id }}" @selected($m->current_vehicle_id == $v->id)>{{ $v->plate }}</option>@endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary">Assign</button>
                                    </form>
                                    @endif
                                    @endif
                                @endif
                            </td>
                            <td class="text-end">
                                @if($canEdit)
                                <form method="POST" action="{{ route('provider.team.remove', $m->user_id) }}" onsubmit="return confirm('Remove {{ addslashes($m->name) }} from the team?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </x-cb.card>
        </div>

        <div class="col-lg-4">
            <x-cb.card title="Invite someone" icon="ri-mail-send-line">
                <form method="POST" action="{{ route('provider.team.invite') }}">@csrf
                    <input type="email" name="email" class="form-control mb-2" placeholder="their@email.com" value="{{ old('email') }}" required>
                    <select name="role" class="form-select mb-3" required>
                        @foreach($manageable as $r)<option value="{{ $r }}" @selected(old('role') === $r)>{{ $label($r) }}</option>@endforeach
                    </select>
                    <button class="btn btn-primary w-100">Send invitation</button>
                    <div class="form-text">They get an email with a code. The code works for 7 days, once, and only for the address you enter.</div>
                </form>
            </x-cb.card>

            @if(count($invites))
            <x-cb.card title="Waiting to join" icon="ri-time-line" class="mt-3">
                @foreach($invites as $i)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ $i->email }}<div class="small text-muted">{{ $label($i->role) }}</div></span>
                        <form method="POST" action="{{ route('provider.team.revoke', $i->public_id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-secondary">Withdraw</button></form>
                    </div>
                @endforeach
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
