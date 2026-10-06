{{-- resources/views/management/dashboard.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $naira = fn ($v) => '₦' . number_format((float) $v, 2);
    $short = function ($v) { $v = (float) $v; return $v >= 1e6 ? '₦' . round($v / 1e6, 1) . 'm' : ($v >= 1e3 ? '₦' . round($v / 1e3) . 'k' : '₦' . number_format($v)); };
    $maxDay = max(1, collect($collections)->max('amount'));
    $week = collect($collections)->slice(-7)->sum('amount');
    $t = $fees['totals'] ?? null;
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Management Dashboard" icon="ri-dashboard-3-line" :subtitle="'Today at a glance — ' . now()->format('l, j F Y')">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <select name="session_id" class="cb-select" onchange="this.form.submit()" aria-label="Session">
                    @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->id == $sessionId)>{{ $s->session }}</option>@endforeach
                </select>
                <select name="term_id" class="cb-select" onchange="this.form.submit()" aria-label="Term">
                    @foreach($terms as $tm)<option value="{{ $tm->id }}" @selected($tm->id == $termId)>{{ $tm->term }}</option>@endforeach
                </select>
            </form>
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-information-line"></i><div>{{ session('success') }}</div></div>@endif

    {{-- Needs attention --}}
    @if($actions)
        <x-cb.card title="Needs attention" icon="ri-alarm-warning-line" :count="count($actions)" :flush="true">
            <div class="list-group list-group-flush">
                @foreach($actions as [$level, $icon, $text, $url])
                    @php $cls = ['danger' => 'text-danger', 'warning' => 'text-warning', 'info' => 'text-primary'][$level] ?? ''; @endphp
                    @if($url)
                        <a href="{{ $url }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2"><i class="{{ $icon }} {{ $cls }} fs-5"></i><span class="flex-grow-1">{{ $text }}</span><i class="ri-arrow-right-s-line text-muted"></i></a>
                    @else
                        <div class="list-group-item d-flex align-items-center gap-2"><i class="{{ $icon }} {{ $cls }} fs-5"></i><span class="flex-grow-1">{{ $text }}</span></div>
                    @endif
                @endforeach
            </div>
        </x-cb.card>
    @endif

    {{-- Headline --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-6"><x-cb.stat label="Active students" :value="number_format($enrol['active'])" icon="ri-team-line" accent="teal" :hint="$enrol['male'] . ' boys · ' . $enrol['female'] . ' girls'" /></div>
        <div class="col-xl-3 col-6"><x-cb.stat label="Attendance today" :value="$att['rate'] === null ? '—' : $att['rate'] . '%'" icon="ri-calendar-check-line" :accent="$att['rate'] !== null && $att['rate'] < 85 ? 'amber' : 'green'" :hint="$att['marked_classes'] . ' of ' . $att['classes'] . ' classes marked'" /></div>
        <div class="col-xl-3 col-6"><x-cb.stat label="Fees collected (term)" :value="$t ? $t['rate'] . '%' : '…'" icon="ri-money-dollar-circle-line" accent="violet" :hint="$t ? $short($t['paid']) . ' of ' . $short($t['payable']) : 'Calculating'" /></div>
        <div class="col-xl-3 col-6"><x-cb.stat label="Scores vetted" :value="$res['pct'] . '%'" icon="ri-checkbox-circle-line" :accent="$res['pct'] >= 100 ? 'green' : 'sky'" :hint="$res['approved'] . ' of ' . $res['classes'] . ' classes approved'" /></div>
    </div>

    <div class="row g-3">
        {{-- Money --}}
        <div class="col-xl-8">
            <x-cb.card title="Fees this term" icon="ri-wallet-3-line">
                <x-slot:tools>
                    <a href="{{ route('management.dashboard', ['term_id' => $termId, 'session_id' => $sessionId, 'refresh' => 1]) }}" class="action-btn btn-open"><i class="ri-refresh-line"></i>Recalculate</a>
                </x-slot:tools>
                @if(!$t)
                    <div class="empty-state" id="feeWait"><i class="ri-loader-4-line"></i><h6>Working out fee figures…</h6>
                        <p>This uses each student's full statement so the numbers match the bursary exactly. It takes a minute on first load.</p></div>
                @else
                    <div class="row g-3 mb-3">
                        <div class="col-md-3 col-6"><div class="small text-muted">Expected</div><div class="fs-5 fw-bold">{{ $naira($t['payable']) }}</div><div class="small text-muted">after {{ $short($t['savings']) }} scholarships/discounts</div></div>
                        <div class="col-md-3 col-6"><div class="small text-muted">Collected</div><div class="fs-5 fw-bold text-success">{{ $naira($t['paid']) }}</div></div>
                        <div class="col-md-3 col-6"><div class="small text-muted">Outstanding (term)</div><div class="fs-5 fw-bold text-danger">{{ $naira($t['outstanding']) }}</div></div>
                        <div class="col-md-3 col-6"><div class="small text-muted">Old arrears</div><div class="fs-5 fw-bold">{{ $naira($t['arrears']) }}</div></div>
                    </div>
                    <div class="progress-track mb-1" role="img" aria-label="Collected {{ $t['rate'] }} percent"><div class="progress-fill" style="width:{{ min(100, $t['rate']) }}%"></div></div>
                    <div class="small text-muted mb-3">{{ $t['fully_paid'] }} of {{ $t['students'] }} students fully paid · {{ $t['debtors'] }} owing
                        @if($t['plan_students']) · {{ $t['plan_students'] }} on instalment plans ({{ $t['plan_behind'] }} behind, {{ $naira($t['plan_overdue']) }} overdue)@endif
                        · updated {{ \Carbon\Carbon::parse($fees['at'])->diffForHumans() }}</div>

                    <h6 class="small text-uppercase text-muted mb-2">Collection by class (lowest first)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Class</th><th style="min-width:160px">Collected</th><th class="text-end">Outstanding</th><th class="text-end">Owing</th></tr></thead>
                            <tbody>
                            @foreach(array_slice($fees['classes'], 0, 10) as $c)
                                <tr>
                                    <td>{{ $c['name'] }}</td>
                                    <td><div class="d-flex align-items-center gap-2"><div class="progress-track flex-grow-1"><div class="progress-fill" style="width:{{ min(100, $c['rate']) }}%"></div></div><small>{{ $c['rate'] }}%</small></div></td>
                                    <td class="text-end">{{ $naira($c['outstanding']) }}</td>
                                    <td class="text-end">{{ $c['debtors'] }}/{{ $c['students'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-cb.card>

            <x-cb.card title="Money received — last 14 days" icon="ri-bar-chart-2-line">
                <x-slot:tools><span class="small text-muted">Last 7 days: <strong>{{ $naira($week) }}</strong></span></x-slot:tools>
                <div class="md-bars" role="img" aria-label="Daily payments received over the last 14 days">
                    @foreach($collections as $d)
                        <div class="md-bar" tabindex="0" data-tip="{{ \Carbon\Carbon::parse($d['date'])->format('D j M') }}: {{ $naira($d['amount']) }}{{ $d['online'] ? ' (online ' . $naira($d['online']) . ')' : '' }}">
                            <div class="md-bar-fill" style="height:{{ $d['amount'] > 0 ? max(2, round($d['amount'] / $maxDay * 100)) : 0 }}%"></div>
                            <div class="md-bar-label">{{ $d['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="md-tip" id="mdTip" hidden></div>
                <details class="mt-2 small"><summary class="text-muted">Show as table</summary>
                    <table class="table table-sm mt-2 mb-0"><thead><tr><th>Date</th><th class="text-end">Received</th><th class="text-end">Of which online</th></tr></thead><tbody>
                        @foreach(array_reverse($collections) as $d)<tr><td>{{ \Carbon\Carbon::parse($d['date'])->format('D j M') }}</td><td class="text-end">{{ $naira($d['amount']) }}</td><td class="text-end">{{ $naira($d['online']) }}</td></tr>@endforeach
                    </tbody></table>
                </details>
            </x-cb.card>
        </div>

        {{-- Side column --}}
        <div class="col-xl-4">
            <x-cb.card title="Today" icon="ri-sun-line">
                <div class="d-flex justify-content-between mb-1"><span>Present</span><strong class="text-success">{{ number_format($att['present']) }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Late</span><strong class="text-warning">{{ number_format($att['late']) }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Absent</span><strong class="text-danger">{{ number_format($att['absent']) }}</strong></div>
                @if($att['staff_present'] !== null)<div class="d-flex justify-content-between mb-2"><span>Staff signed in</span><strong>{{ $att['staff_present'] }}</strong></div>@endif
                @if($att['unmarked'])
                    <div class="small text-muted">Not marked yet: {{ implode(', ', array_slice($att['unmarked'], 0, 12)) }}{{ count($att['unmarked']) > 12 ? ' +' . (count($att['unmarked']) - 12) . ' more' : '' }}</div>
                @elseif($att['classes'])
                    <div class="small text-success">Every class has marked attendance.</div>
                @endif
            </x-cb.card>

            <x-cb.card title="Report cards" icon="ri-file-list-3-line">
                <div class="d-flex justify-content-between mb-1"><span>Classes with results</span><strong>{{ $res['classes'] }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Scores vetted</span><strong>{{ number_format($res['vetted']) }} / {{ number_format($res['entries']) }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Awaiting approval</span><strong class="{{ $res['submitted'] ? 'text-warning' : '' }}">{{ $res['submitted'] }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Approved</span><strong class="text-success">{{ $res['approved'] }}</strong></div>
                @canany(['View report-approvals', 'Approve report cards'])<a href="{{ route('report-approvals.index', ['term_id' => $termId, 'session_id' => $sessionId]) }}" class="action-btn btn-open"><i class="ri-shield-check-line"></i>Open approvals</a>@endcanany
            </x-cb.card>

            <x-cb.card title="Messages (7 days)" icon="ri-message-3-line">
                <div class="d-flex justify-content-between mb-1"><span>Sent</span><strong class="text-success">{{ number_format($msg['sent']) }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Failed</span><strong class="{{ $msg['failed'] ? 'text-danger' : '' }}">{{ number_format($msg['failed']) }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>Waiting</span><strong>{{ number_format($msg['queued']) }}</strong></div>
                @if($msg['sms_balance'])<div class="d-flex justify-content-between mb-1"><span>SMS balance</span><strong>{{ $naira($msg['sms_balance']['balance']) }}</strong></div>@endif
                @if($msg['parents'])
                    <div class="d-flex justify-content-between"><span>Parents using the portal</span><strong>{{ $msg['parents']['active'] ?? '—' }} / {{ $msg['parents']['accounts'] }}</strong></div>
                @endif
            </x-cb.card>

            <x-cb.card title="People" icon="ri-group-line">
                <div class="d-flex justify-content-between mb-1"><span>Placed in a class</span><strong>{{ number_format($enrol['placed']) }}</strong></div>
                <div class="d-flex justify-content-between mb-1"><span>New in last 30 days</span><strong>{{ number_format($enrol['new30']) }}</strong></div>
                <div class="d-flex justify-content-between"><span>Staff accounts</span><strong>{{ number_format($enrol['staff']) }}</strong></div>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>

<style>
.md-bars{display:flex;align-items:flex-end;gap:6px;height:180px;padding-top:8px;border-bottom:1px solid var(--bs-border-color,#e5e7eb)}
.md-bar{flex:1;height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;position:relative;cursor:default;outline:none}
.md-bar-fill{width:100%;max-width:28px;background:#0f766e;border-radius:4px 4px 0 0;transition:opacity .15s}
.md-bar:hover .md-bar-fill,.md-bar:focus .md-bar-fill{opacity:.8}
.md-bar-label{position:absolute;bottom:-20px;font-size:10px;color:var(--bs-secondary-color,#6b7280);white-space:nowrap}
.md-bars{margin-bottom:24px}
.md-tip{position:fixed;z-index:1080;background:var(--bs-body-bg,#fff);color:var(--bs-body-color,#111);border:1px solid var(--bs-border-color,#e5e7eb);box-shadow:0 6px 18px rgba(0,0,0,.12);border-radius:8px;padding:6px 10px;font-size:12px;pointer-events:none}
@media (max-width:576px){.md-bar-label{display:none}}
[data-bs-theme="dark"] .md-bar-fill{background:#2dd4bf}
</style>
<script>
(function () {
    const tip = document.getElementById('mdTip');
    document.querySelectorAll('.md-bar').forEach(b => {
        const show = e => { tip.textContent = b.dataset.tip; tip.hidden = false;
            const r = b.getBoundingClientRect(); tip.style.left = Math.min(window.innerWidth - tip.offsetWidth - 8, r.left + r.width / 2 - tip.offsetWidth / 2) + 'px'; tip.style.top = (r.top - tip.offsetHeight - 8) + 'px'; };
        b.addEventListener('mouseenter', show); b.addEventListener('focus', show);
        b.addEventListener('mouseleave', () => tip.hidden = true); b.addEventListener('blur', () => tip.hidden = true);
    });
    @if(!$t)
    const poll = setInterval(async () => {
        try {
            const r = await fetch(@json(route('management.fee-status', ['term_id' => $termId, 'session_id' => $sessionId])), {headers: {'Accept': 'application/json'}});
            if ((await r.json()).ready) { clearInterval(poll); location.reload(); }
        } catch (e) {}
    }, 5000);
    @endif
})();
</script>
@endsection
