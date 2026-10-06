{{-- resources/views/activity/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $E = ['login' => ['Signed in', 'st-paid', 'ri-login-circle-line'], 'logout' => ['Signed out', 'st-muted', 'ri-logout-circle-line'], 'login_failed' => ['Failed sign-in', 'st-danger', 'ri-error-warning-line'],
          'create' => ['Created', 'st-info', 'ri-add-circle-line'], 'update' => ['Updated', 'st-pending', 'ri-edit-line'], 'delete' => ['Deleted', 'st-danger', 'ri-delete-bin-line'], 'action' => ['Action', 'st-muted', 'ri-flashlight-line']];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$person ? $person->name . ' — Activity' : 'Staff Activity Log'" icon="ri-history-line"
               :subtitle="$person ? ('Last seen ' . ($person->last_seen_at ? \Carbon\Carbon::parse($person->last_seen_at)->diffForHumans() : 'never') . ($person->last_login_ip ? ' · last sign-in from ' . $person->last_login_ip : '')) : 'Who signed in, when, from where — and every change they made.'">
        <x-slot:actions>
            <a href="{{ route('online-staff.index') }}" class="cb-hero-btn"><i class="ri-user-follow-line"></i>Who's online</a>
            <a href="{{ route('activity.export', request()->query()) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Export</a>
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Sign-ins today" :value="$stats['logins']" icon="ri-login-circle-line" accent="teal" :hint="$stats['people'] . ' people'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Changes today" :value="$stats['changes']" icon="ri-edit-line" accent="violet" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Failed sign-ins today" :value="$stats['failed']" icon="ri-error-warning-line" :accent="$stats['failed'] > 5 ? 'rose' : 'amber'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Entries shown" :value="number_format($logs->total())" icon="ri-list-check-2" accent="sky" /></div>
    </div>

    <x-cb.card title="Activity" icon="ri-history-line" :count="$logs->total()" :flush="true">
        <form class="cb-toolbar gap-2 flex-wrap" method="GET">
            <select name="user" class="cb-select" style="max-width:220px" aria-label="Staff"><option value="">Everyone</option>@foreach($users as $id => $n)<option value="{{ $id }}" @selected(request('user') == $id)>{{ $n }}</option>@endforeach</select>
            <select name="event" class="cb-select" aria-label="Event"><option value="">All events</option>@foreach($E as $k => [$l])<option value="{{ $k }}" @selected(request('event') === $k)>{{ $l }}</option>@endforeach</select>
            <input type="date" name="from" class="form-control form-control-sm" style="width:150px" value="{{ request('from') }}" aria-label="From">
            <input type="date" name="to" class="form-control form-control-sm" style="width:150px" value="{{ request('to') }}" aria-label="To">
            <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="What, name or IP">
            <button class="action-btn btn-open"><i class="ri-filter-3-line"></i>Filter</button>
            @if(request()->query())<a href="{{ route('activity.index') }}" class="small">Clear</a>@endif
        </form>
        @if($logs->isEmpty())
            <div class="empty-state"><i class="ri-history-line"></i><h6>Nothing recorded</h6></div>
        @else
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>When</th><th>Who</th><th>Event</th><th>What</th><th>From</th></tr></thead>
                <tbody>
                @foreach($logs as $l)
                    @php [$el, $ec, $ei] = $E[$l->event] ?? [$l->event, 'st-muted', 'ri-flashlight-line']; $props = json_decode($l->properties ?? 'null', true); @endphp
                    <tr>
                        <td class="text-nowrap small" title="{{ $l->created_at }}">{{ \Carbon\Carbon::parse($l->created_at)->format('d M, h:i A') }}<div class="text-muted">{{ \Carbon\Carbon::parse($l->created_at)->diffForHumans() }}</div></td>
                        <td>@if($l->user_id)<a href="{{ route('activity.index', ['user' => $l->user_id]) }}">{{ $l->name ?? 'User #' . $l->user_id }}</a>@else<span class="text-muted">Unknown</span>@endif</td>
                        <td><span class="status-pill {{ $ec }}"><i class="{{ $ei }}"></i> {{ $el }}</span></td>
                        <td class="small">{{ $l->description }}
                            @if($props)<details class="mt-1"><summary class="text-muted">details</summary><div class="text-muted" style="max-width:420px;word-break:break-word">@foreach($props as $k => $v)<span class="me-2"><b>{{ $k }}</b>: {{ is_scalar($v) ? $v : json_encode($v) }}</span>@endforeach<div>{{ $l->method }} {{ \Illuminate\Support\Str::limit($l->url, 90) }} {{ $l->status ? '· ' . $l->status : '' }}</div></div></details>@endif
                        </td>
                        <td class="small text-muted text-nowrap">{{ $l->ip }}<div>{{ $l->device }}</div></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="p-3">{{ $logs->links() }}</div>
        @endif
    </x-cb.card>
    <div class="small text-muted">Passwords, account numbers, PINs and codes typed into forms are never stored. Page views aren't logged — only sign-ins and changes.</div>
</div>
</div>
</div>
@endsection
