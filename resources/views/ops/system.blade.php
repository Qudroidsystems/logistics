@extends('layouts.master')

@section('content')
@php
    $badge = ['ok' => ['bg-success-subtle text-success', 'On time'], 'running' => ['bg-info-subtle text-info', 'Running'], 'late' => ['bg-danger-subtle text-danger', 'Late'],
        'failed' => ['bg-danger-subtle text-danger', 'Failed'], 'never' => ['bg-warning-subtle text-warning', 'Not run yet']];
    $bad = collect($tasks)->whereIn('state', ['late', 'failed'])->count();
    $tz = config('app.timezone');
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="System" icon="ri-pulse-line" subtitle="Is the background work running? Renewals, recurring trips, e-Transfer expiry, dispatch of booked jobs, reminders and backups all depend on the scheduler and the queue worker. Staff with this page are alerted when something stops." :back="route('ops.dashboard')" back-label="Operations">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ $checked_at ? 'Checked '.$checked_at->diffForHumans() : 'Never checked' }}</span>
            <span class="cb-meta-pill">{{ $bad ? $bad.' task(s) need attention' : 'All tasks on time' }}</span>
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    @unless($scheduler_running)
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div><strong>The scheduler is not running.</strong> Nothing below has been checked in the last 25 minutes, so renewals, recurring trips and e-Transfer expiry are probably not happening. Add this line to the server's crontab: <code>* * * * * cd {{ base_path() }} &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></div></div>
    @endunless

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><x-cb.stat label="Jobs waiting" :value="$queue['waiting']" icon="ri-stack-line" accent="sky" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Oldest waiting" :value="$queue['oldest_minutes'] === null ? 'None' : $queue['oldest_minutes'].' min'" icon="ri-time-line" :accent="($queue['oldest_minutes'] ?? 0) >= 10 ? 'rose' : 'teal'" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Failed, last 24 h" :value="$queue['failed_24h']" icon="ri-error-warning-line" :accent="$queue['failed_24h'] ? 'rose' : 'teal'" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Failed, kept" :value="$queue['failed_total']" icon="ri-archive-line" accent="sky" /></div>
    </div>
    @if(($queue['oldest_minutes'] ?? 0) >= 10)
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>Jobs have waited {{ $queue['oldest_minutes'] }} minutes: the queue worker may have stopped. Run <code>php artisan queue:work</code> under a process manager (Supervisor or systemd) so it restarts by itself.</div></div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Scheduled tasks" icon="ri-calendar-schedule-line" :count="count($tasks)" :flush="true" class="mb-3">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Task</th><th>Status</th><th>Last finished</th><th>Next due</th></tr></thead>
                    <tbody>
                    @forelse(collect($tasks)->sortBy(fn ($t) => ['failed' => 0, 'late' => 1, 'never' => 2, 'running' => 3, 'ok' => 4][$t['state']] ?? 5) as $t)
                        <tr>
                            <td><span class="font-monospace small">{{ $t['name'] }}</span><div class="small text-muted">{{ $t['expression'] }}</div>
                                @if($t['state'] === 'failed' && $t['last_error'])<div class="small text-danger">{{ $t['last_error'] }}</div>@endif</td>
                            <td><span class="badge {{ $badge[$t['state']][0] ?? 'bg-light' }}">{{ $badge[$t['state']][1] ?? $t['state'] }}</span></td>
                            <td class="small">{{ $t['last_finished_at'] ? \Illuminate\Support\Carbon::parse($t['last_finished_at'])->setTimezone($tz)->format('j M, H:i') : '-' }}
                                @if($t['runtime_ms'])<div class="text-muted">{{ number_format($t['runtime_ms'] / 1000, 1) }} s</div>@endif</td>
                            <td class="small">{{ \Illuminate\Support\Carbon::parse($t['next_due'])->setTimezone($tz)->format('j M, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No check has run yet. Once the scheduler runs, <code>system:watch</code> fills this in every 10 minutes.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </x-cb.card>

            <x-cb.card title="Errors" icon="ri-bug-line" :count="count($errorReports)" :flush="true" class="mb-3">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    @forelse($errorReports as $er)
                        <tr>
                            <td><div class="fw-semibold small">{{ class_basename($er->class) }} <span class="badge bg-light text-dark">{{ $er->count }}×</span></div>
                                <div class="small text-danger">{{ \Illuminate\Support\Str::limit($er->message, 200) }}</div>
                                <div class="small text-muted font-monospace">{{ $er->file }}:{{ $er->line }}@if($er->url) · {{ $er->url }}@endif</div>
                                <div class="small text-muted">Last {{ \Illuminate\Support\Carbon::parse($er->last_seen_at)->diffForHumans() }} · first {{ \Illuminate\Support\Carbon::parse($er->first_seen_at)->diffForHumans() }}</div>
                                @if($er->trace)<details class="small"><summary>Trace</summary><pre class="mb-0 small" style="white-space: pre-wrap">{{ $er->trace }}</pre></details>@endif</td>
                            <td class="text-end"><form method="POST" action="{{ route('ops.system.error.resolve', $er->id) }}">@csrf <button class="btn btn-sm btn-outline-success">Fixed</button></form></td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-muted py-4">No errors. Unexpected errors in the website, the apps' API and background jobs show here, grouped by where they happen.</td></tr>
                    @endforelse
                </table></div>
            </x-cb.card>

            <x-cb.card title="Failed background jobs" icon="ri-error-warning-line" :count="count($failed)" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    @forelse($failed as $f)
                        <tr>
                            <td><div class="fw-semibold small">{{ class_basename($f['job']) }}</div><div class="small text-danger">{{ $f['error'] }}</div>
                                <div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($f['failed_at'])->diffForHumans() }} · {{ $f['queue'] }}</div></td>
                            <td class="text-end text-nowrap">
                                <form method="POST" action="{{ route('ops.system.retry', $f['uuid']) }}" class="d-inline">@csrf <button class="btn btn-sm btn-outline-primary">Retry</button></form>
                                <form method="POST" action="{{ route('ops.system.forget', $f['uuid']) }}" class="d-inline" onsubmit="return confirm('Remove this failed job?')">@csrf <button class="btn btn-sm btn-outline-secondary">Remove</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-muted py-4">No failed jobs.</td></tr>
                    @endforelse
                </table></div>
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card title="Setup" icon="ri-settings-3-line" :flush="true">
                <ul class="list-group list-group-flush">
                    @foreach($config as [$label, $value, $ok, $fix])
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between"><span>{{ $label }}</span><span class="badge {{ $ok ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $value }}</span></div>
                            @unless($ok)<div class="small text-muted mt-1">{{ $fix }}</div>@endunless
                        </li>
                    @endforeach
                </ul>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
