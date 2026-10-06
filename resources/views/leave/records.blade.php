{{-- resources/views/leave/records.blade.php --}}
@extends('layouts.master')

@section('content')
@php $S = \App\Services\Leave\LeaveService::STATUS; $fmt = fn ($d) => rtrim(rtrim(number_format((float) $d, 1), '0'), '.'); $col = fn ($c) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $c) ? $c : '#0f766e'; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Leave Records" icon="ri-table-line" subtitle="Everyone's leave, who is away this week, leave types and balance adjustments." :back="route('leave.index')" back-label="My leave">
        <x-slot name="actions">
            @if(Route::has('leave.board'))<a href="{{ route('leave.board') }}" class="action-btn btn-go"><i class="ri-team-line"></i>Who's Away</a>@endif
            @can('View leave records')<a href="{{ route('leave.records.export', request()->query()) }}" class="action-btn btn-primary-cb"><i class="ri-download-2-line"></i>Export CSV</a>@endcan
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><x-cb.stat label="On leave today" :value="$stats['today']" icon="ri-user-unfollow-line" accent="amber" /></div>
        <div class="col-md-4"><x-cb.stat label="Waiting for a decision" :value="$stats['pending']" icon="ri-time-line" accent="violet" /></div>
        <div class="col-md-4"><x-cb.stat :label="'Days approved in ' . now()->year" :value="$fmt($stats['year_days'])" icon="ri-calendar-check-line" accent="teal" /></div>
    </div>

    @if($notBack->isNotEmpty())
        <div class="cb-banner warning"><i class="ri-user-unfollow-line"></i>
            <div class="flex-grow-1"><b>Not confirmed back from leave:</b>
                @foreach($notBack as $nb)
                    <span class="d-inline-flex align-items-center gap-1 me-2">{{ $nb->name }} (ended {{ \Carbon\Carbon::parse($nb->end_date)->format('j M') }})
                        @canany(['Approve leave', 'View leave records'])<form method="POST" action="{{ route('leave.mark-resumed', $nb->id) }}" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0">mark back</button></form>@endcanany</span>
                @endforeach
            </div>
        </div>
    @endif

    <x-cb.card title="This week" icon="ri-calendar-2-line">
        <div class="row g-2">
            @foreach($week as $d)
                <div class="col"><div class="border rounded p-2 h-100 {{ $d['date']->isToday() ? 'border-primary' : '' }} {{ $d['date']->isWeekend() ? 'bg-light' : '' }}">
                    <div class="small fw-bold">{{ $d['date']->format('D d') }}</div>
                    @forelse($d['people'] as $p)<div class="small text-truncate" title="{{ $p->name }} — {{ $p->type }}"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $col($p->color) }}"></span> {{ $p->name }}</div>
                    @empty<div class="small text-muted">—</div>@endforelse
                </div></div>
            @endforeach
        </div>
    </x-cb.card>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="All requests" icon="ri-list-check-2" :count="$rows->total()" :flush="true">
                <form class="cb-toolbar gap-2 flex-wrap" method="GET">
                    <input type="search" name="search" class="cb-search" value="{{ request('search') }}" placeholder="Staff name">
                    <input type="month" name="month" class="form-control form-control-sm" style="width:160px" value="{{ request('month') }}">
                    <select name="type" class="cb-select"><option value="">All types</option>@foreach($types as $t)<option value="{{ $t->id }}" @selected(request('type') == $t->id)>{{ $t->name }}</option>@endforeach</select>
                    <select name="status" class="cb-select"><option value="">All statuses</option>@foreach($S as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
                    <button class="action-btn btn-open"><i class="ri-filter-3-line"></i>Filter</button>
                </form>
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Staff</th><th>Type</th><th>Dates</th><th class="text-end">Days</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($rows as $r)
                        <tr><td><strong>{{ $r->name }}</strong><div class="small text-muted">{{ $r->department }}</div></td>
                            <td><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $col($r->color) }}"></span> {{ $r->type }}</td>
                            <td class="small text-nowrap">{{ \Carbon\Carbon::parse($r->start_date)->format('d M') }} – {{ \Carbon\Carbon::parse($r->end_date)->format('d M Y') }}</td>
                            <td class="text-end">{{ $fmt($r->days) }}</td>
                            <td><span class="status-pill {{ $S[$r->status][1] ?? 'st-muted' }}">{{ $S[$r->status][0] ?? $r->status }}</span> @if($r->attachment)<a href="{{ route('leave.attachment', $r->id) }}" target="_blank" title="Document"><i class="ri-attachment-line"></i></a>@endif</td></tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-4">No records.</td></tr>@endforelse
                    </tbody>
                </table></div>
                <div class="p-3">{{ $rows->links() }}</div>
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            @can('Manage leave types')
                <x-cb.card title="Leave types" icon="ri-list-settings-line" :flush="true">
                    @foreach($types as $t)
                        <details class="px-3 py-2 border-bottom"><summary class="small"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $col($t->color) }}"></span> <b>{{ $t->name }}</b> — {{ (float) $t->days_per_year > 0 ? $fmt($t->days_per_year) . ' days/yr' : 'no limit' }}{{ $t->paid ? '' : ', unpaid' }}{{ $t->is_active ? '' : ' (off)' }}</summary>
                            @include('leave.partials.type-form', ['t' => $t, 'action' => route('leave.types.update', $t->id)])
                        </details>
                    @endforeach
                    <details class="px-3 py-2"><summary class="small"><b>+ New type</b></summary>@include('leave.partials.type-form', ['t' => null, 'action' => route('leave.types.store')])</details>
                </x-cb.card>
                @if($reminders)
                <x-cb.card title="Leave reminders" icon="ri-notification-3-line">
                    <form method="POST" action="{{ route('leave.reminders') }}">@csrf
                        @php $rc = $reminders->config; @endphp
                        <label class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked($reminders->is_active)> <span class="form-check-label"><b>Send leave reminders</b> (daily at 7am)</span></label>
                        <div class="small mb-1">Channels</div>
                        <div class="d-flex gap-3 mb-2 small">@foreach(['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $k => $l)<label class="form-check"><input type="checkbox" class="form-check-input" name="channels[]" value="{{ $k }}" @checked(in_array($k, $rc['channels'] ?? []))> <span class="form-check-label">{{ $l }}</span></label>@endforeach</div>
                        <label class="small">Countdown when this many working days are left</label>
                        <input name="countdown" class="form-control form-control-sm mb-2" value="{{ implode(',', $rc['countdown'] ?? []) }}" placeholder="5,3">
                        <label class="small">Flag as "not back" after (working days)</label>
                        <input name="overdue_after_days" type="number" min="1" max="10" class="form-control form-control-sm mb-2" value="{{ $rc['overdue_after_days'] ?? 1 }}">
                        <label class="form-check small"><input type="checkbox" class="form-check-input" name="notify_cover" value="1" @checked($rc['notify_cover'] ?? true)> <span class="form-check-label">Remind the colleague covering</span></label>
                        <label class="form-check small"><input type="checkbox" class="form-check-input" name="notify_managers_on_resume" value="1" @checked($rc['notify_managers_on_resume'] ?? true)> <span class="form-check-label">Tell HOD/principal on resumption day</span></label>
                        <label class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="auto_resume_on_login" value="1" @checked($rc['auto_resume_on_login'] ?? true)> <span class="form-check-label">Count signing in to the portal as "back"</span></label>
                        <button class="action-btn btn-go"><i class="ri-save-line"></i>Save</button>
                    </form>
                </x-cb.card>
                @endif
                <x-cb.card title="Adjust a balance" icon="ri-scales-3-line">
                    <form method="POST" action="{{ route('leave.adjust') }}">@csrf
                        <select name="staff_id" class="form-select form-select-sm mb-2" required><option value="">Staff…</option>@foreach($staff as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><select name="leave_type_id" class="form-select form-select-sm">@foreach($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
                            <div class="col-3"><input name="year" type="number" class="form-control form-control-sm" value="{{ now()->year }}"></div>
                            <div class="col-3"><input name="days" type="number" step="0.5" class="form-control form-control-sm" placeholder="±days" required></div>
                        </div>
                        <input name="note" class="form-control form-control-sm mb-2" placeholder="Reason, e.g. carried over from 2025" required>
                        <button class="action-btn btn-go"><i class="ri-check-line"></i>Save</button>
                    </form>
                </x-cb.card>
            @endcan
        </div>
    </div>
</div>
</div>
</div>
@endsection
