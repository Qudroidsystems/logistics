{{-- resources/views/activity/online.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Who's Online" icon="ri-user-follow-line" :subtitle="'Staff active in the last ' . $minutes . ' minutes · refreshes every minute'">
        <x-slot:actions>@can('View activity log')<a href="{{ route('activity.index') }}" class="cb-hero-btn"><i class="ri-history-line"></i>Activity log</a>@endcan</x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6"><x-cb.stat label="Staff online now" :value="$online->count()" icon="ri-user-follow-line" accent="green" /></div>
        <div class="col-md-4 col-6"><x-cb.stat label="Staff signed in today" :value="$todayLogins->count()" icon="ri-login-circle-line" accent="teal" /></div>
        <div class="col-md-4 col-6"><x-cb.stat label="Students & parents online" :value="$others" icon="ri-group-line" accent="sky" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="Online now" icon="ri-radio-button-line" :count="$online->count()" :flush="true">
                @if($online->isEmpty())
                    <div class="empty-state"><i class="ri-user-unfollow-line"></i><h6>Nobody else is online</h6></div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($online as $u)
                            <div class="list-group-item d-flex gap-3 align-items-center">
                                <span class="ol-dot"></span>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between"><strong>{{ $u->name }}</strong><small class="text-muted">{{ \Carbon\Carbon::parse($u->last_seen_at)->diffForHumans() }}</small></div>
                                    <div class="small text-muted">{{ $roles[$u->id] ?? 'Staff' }} · {{ $devices[$u->id] ?? '—' }} · {{ $u->last_login_ip }}</div>
                                    @if($u->last_seen_url)<div class="small text-muted text-truncate">On: /{{ $u->last_seen_url }}</div>@endif
                                </div>
                                @can('View activity log')<a href="{{ route('activity.index', ['user' => $u->id]) }}" class="action-btn btn-open" title="Activity"><i class="ri-history-line"></i></a>@endcan
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-5">
            <x-cb.card title="Signed in today" icon="ri-calendar-check-line" :count="$todayLogins->count()" :flush="true">
                <table class="table table-sm align-middle mb-0"><thead><tr><th>Staff</th><th>First in</th><th>Latest</th><th class="text-end">Times</th></tr></thead><tbody>
                    @forelse($todayLogins as $t)<tr><td>{{ $t->name }}</td><td class="small">{{ \Carbon\Carbon::parse($t->first_in)->format('h:i A') }}</td><td class="small">{{ \Carbon\Carbon::parse($t->last_in)->format('h:i A') }}</td><td class="text-end">{{ $t->times }}</td></tr>
                    @empty<tr><td colspan="4" class="text-muted text-center py-3">No staff sign-ins yet today.</td></tr>@endforelse
                </tbody></table>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
<style>.ol-dot{width:10px;height:10px;border-radius:50%;background:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.2);flex-shrink:0}</style>
<script>setTimeout(() => location.reload(), 60000);</script>
@endsection
