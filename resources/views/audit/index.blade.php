@extends('layouts.master')

@section('content')
@php
    $A = ['created' => ['Created', 'st-info', 'ri-add-circle-line'], 'updated' => ['Changed', 'st-pending', 'ri-edit-line'], 'deleted' => ['Deleted', 'st-danger', 'ri-delete-bin-line'], 'restored' => ['Restored', 'st-paid', 'ri-arrow-go-back-line'],
          'two_factor.enabled' => ['Two-step on', 'st-paid', 'ri-shield-check-line'], 'two_factor.disabled' => ['Two-step off', 'st-danger', 'ri-shield-line'],
          'two_factor.reset_by_staff' => ['Two-step reset', 'st-danger', 'ri-restart-line'], 'two_factor.recovery_code_used' => ['Recovery code used', 'st-pending', 'ri-key-2-line'],
          'two_factor.recovery_codes_renewed' => ['New recovery codes', 'st-muted', 'ri-key-2-line']];
    $label = fn ($t) => ucfirst(str_replace('_', ' ', (string) $t));
    $show = function ($v) { if ($v === null) return '—'; if (is_bool($v)) return $v ? 'yes' : 'no'; $s = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_SLASHES); return \Illuminate\Support\Str::limit($s, 120); };
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Data changes" icon="ri-file-list-3-line" subtitle="Who created, changed or deleted what, with the old and new values.">
        <x-slot:actions>
            @can('View activity log')<a href="{{ route('activity.index') }}" class="cb-hero-btn"><i class="ri-history-line"></i>Activity log</a>@endcan
            <a href="{{ route('audit.export', request()->query()) }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i>Export</a>
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Changes today" :value="$stats['today']" icon="ri-edit-line" accent="violet" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="People today" :value="$stats['people']" icon="ri-user-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Deleted today" :value="$stats['deleted']" icon="ri-delete-bin-line" :accent="$stats['deleted'] > 0 ? 'rose' : 'sky'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Sign-in security today" :value="$stats['security']" icon="ri-shield-keyhole-line" accent="amber" /></div>
    </div>

    <x-cb.card title="Changes" icon="ri-file-list-3-line" :count="$logs->total()" :flush="true">
        <form class="cb-toolbar gap-2 flex-wrap" method="GET">
            <select name="user" class="cb-select" style="max-width:200px" aria-label="Person"><option value="">Everyone</option>@foreach($users as $id => $n)<option value="{{ $id }}" @selected(request('user') == $id)>{{ $n }}</option>@endforeach</select>
            <select name="table" class="cb-select" style="max-width:200px" aria-label="Record type"><option value="">All records</option>@foreach($tables as $t)<option value="{{ $t }}" @selected(request('table') === $t)>{{ $label($t) }}</option>@endforeach</select>
            <select name="action" class="cb-select" aria-label="Action"><option value="">All actions</option>@foreach($A as $k => [$l])<option value="{{ $k }}" @selected(request('action') === $k)>{{ $l }}</option>@endforeach</select>
            <input type="number" name="record" class="form-control form-control-sm" style="width:110px" value="{{ request('record') }}" placeholder="Record #" aria-label="Record number">
            <input type="date" name="from" class="form-control form-control-sm" style="width:150px" value="{{ request('from') }}" aria-label="From">
            <input type="date" name="to" class="form-control form-control-sm" style="width:150px" value="{{ request('to') }}" aria-label="To">
            <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Name, IP or value">
            <button class="action-btn btn-open"><i class="ri-filter-3-line"></i>Filter</button>
            @if(request()->query())<a href="{{ route('audit.index') }}" class="small">Clear</a>@endif
        </form>
        @if($logs->isEmpty())
            <div class="empty-state"><i class="ri-file-list-3-line"></i><h6>No changes recorded</h6></div>
        @else
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>What changed</th></tr></thead>
                <tbody>
                @foreach($logs as $l)
                    @php
                        [$al, $ac, $ai] = $A[$l->action] ?? [$label($l->action), 'st-muted', 'ri-flashlight-line'];
                        $before = json_decode($l->before ?? 'null', true) ?? []; $after = json_decode($l->after ?? 'null', true) ?? [];
                        $keys = array_values(array_unique(array_merge(array_keys($before), array_keys($after))));
                    @endphp
                    <tr>
                        <td class="text-nowrap small" title="{{ $l->created_at }}">{{ \Carbon\Carbon::parse($l->created_at)->format('d M, h:i A') }}<div class="text-muted">{{ \Carbon\Carbon::parse($l->created_at)->diffForHumans() }}</div></td>
                        <td class="small">@if($l->user_id)<a href="{{ route('audit.index', ['user' => $l->user_id]) }}">{{ $l->name ?? 'User #'.$l->user_id }}</a>@else<span class="text-muted">System</span>@endif
                            @if($l->operator_name)<div class="text-muted">{{ $l->operator_name }}</div>@endif</td>
                        <td><span class="status-pill {{ $ac }}"><i class="{{ $ai }}"></i> {{ $al }}</span></td>
                        <td class="small text-nowrap">@if($l->subject_type)<a href="{{ route('audit.index', ['table' => $l->subject_type, 'record' => $l->subject_id]) }}">{{ $label($l->subject_type) }} #{{ $l->subject_id }}</a>@endif</td>
                        <td class="small" style="max-width:520px;word-break:break-word">
                            @foreach(array_slice($keys, 0, 6) as $k)
                                <div><b>{{ $label($k) }}</b>:
                                    @if($l->action === 'updated' || (array_key_exists($k, $before) && array_key_exists($k, $after)))<span class="text-muted text-decoration-line-through">{{ $show($before[$k] ?? null) }}</span> → {{ $show($after[$k] ?? null) }}
                                    @else {{ $show($after[$k] ?? $before[$k] ?? null) }} @endif</div>
                            @endforeach
                            @if(count($keys) > 6)
                                <details><summary class="text-muted">{{ count($keys) - 6 }} more</summary>
                                    @foreach(array_slice($keys, 6) as $k)<div><b>{{ $label($k) }}</b>: @if(array_key_exists($k, $before) && array_key_exists($k, $after)){{ $show($before[$k]) }} → @endif{{ $show($after[$k] ?? $before[$k] ?? null) }}</div>@endforeach
                                </details>
                            @endif
                            <div class="text-muted">@if($l->ip){{ $l->ip }}@endif @if($l->request_id)· <a href="{{ route('audit.index', ['request' => $l->request_id]) }}" class="text-muted">same request</a>@endif</div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="p-3">{{ $logs->links() }}</div>
        @endif
    </x-cb.card>
    <div class="small text-muted">Passwords, secrets, tokens, account numbers and gateway keys show as [hidden]. GPS positions, ETAs and "last seen" times are not recorded here.</div>
</div></div></div>
@endsection
