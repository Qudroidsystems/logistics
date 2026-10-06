{{-- resources/views/report-approvals/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $S = \App\Models\ReportApproval::STATUSES;
    $rows = $statusFilter ? $board->where('status', $statusFilter) : $board;
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Report Card Approval" icon="ri-shield-check-line"
               subtitle="Class teachers submit their class's report cards; the principal approves and releases them.">
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-{{ $settings->is_active ? 'lock' : 'lock-unlock' }}-line"></i>{{ $settings->is_active ? 'Approval required before release' : 'Approval optional' }}</span>
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Classes with results" :value="$board->count()" icon="ri-building-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Awaiting approval" :value="$counts['submitted'] ?? 0" icon="ri-time-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Returned" :value="$counts['returned'] ?? 0" icon="ri-arrow-go-back-line" accent="rose" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Approved" :value="$counts['approved'] ?? 0" icon="ri-checkbox-circle-line" accent="green" /></div>
    </div>

    <x-cb.card title="Classes" icon="ri-list-check-2" :count="$rows->count()" :flush="true">
        <x-slot:tools>
            @can('Approve report cards')
                @if(($counts['submitted'] ?? 0) > 0)
                    <form method="POST" action="{{ route('report-approvals.approve-all') }}" onsubmit="return confirm('Approve all {{ $counts['submitted'] }} submitted class(es)?')">@csrf
                        <input type="hidden" name="term_id" value="{{ $termId }}"><input type="hidden" name="session_id" value="{{ $sessionId }}">
                        <button class="action-btn btn-go"><i class="ri-check-double-line"></i>Approve all submitted</button></form>
                @endif
            @endcan
        </x-slot:tools>
        <form class="cb-toolbar gap-2" method="GET">
            <select name="session_id" class="cb-select" onchange="this.form.submit()" aria-label="Session">
                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->id == $sessionId)>{{ $s->session }}</option>@endforeach
            </select>
            <select name="term_id" class="cb-select" onchange="this.form.submit()" aria-label="Term">
                @foreach($terms as $t)<option value="{{ $t->id }}" @selected($t->id == $termId)>{{ $t->term }}</option>@endforeach
            </select>
            <select name="status" class="cb-select" onchange="this.form.submit()" aria-label="Status">
                <option value="">All statuses</option>
                @foreach($S as $k => [$l])<option value="{{ $k }}" @selected($statusFilter === $k)>{{ $l }}</option>@endforeach
            </select>
        </form>

        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-file-list-3-line"></i><h6>No classes</h6><p>Classes appear here once scores are entered for the term.</p></div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Class</th><th class="text-end">Students</th><th style="min-width:160px">Scores vetted</th><th class="text-end">Teacher comments</th><th class="text-end">Principal comments</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($rows as $r)
                        @php [$sl, $sc] = $S[$r->status]; @endphp
                        <tr>
                            <td><strong>{{ $r->name }}</strong>@if($r->teacher)<div class="small text-muted">{{ $r->teacher }}</div>@endif</td>
                            <td class="text-end">{{ $r->students }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress-track flex-grow-1"><div class="progress-fill" style="width:{{ $r->vetted_pct }}%"></div></div>
                                    <small class="{{ $r->ready ? 'text-success' : 'text-muted' }}">{{ $r->vetted_pct }}%</small>
                                </div>
                                <small class="text-muted">{{ (int) $r->vetted }} / {{ $r->entries }} scores</small>
                            </td>
                            <td class="text-end {{ $r->t_comments < $r->students ? 'text-warning' : 'text-success' }}">{{ $r->t_comments }}/{{ $r->students }}</td>
                            <td class="text-end {{ $r->p_comments < $r->students ? 'text-warning' : 'text-success' }}">{{ $r->p_comments }}/{{ $r->students }}</td>
                            <td><span class="status-pill {{ $sc }}">{{ $sl }}</span>
                                @if($r->approval?->reviewed_at && in_array($r->status, ['approved', 'returned']))<div class="small text-muted">{{ $r->approval->reviewer?->name }} · {{ $r->approval->reviewed_at->format('d M') }}</div>
                                @elseif($r->status === 'submitted')<div class="small text-muted">{{ $r->approval->submitter?->name }} · {{ $r->approval->submitted_at?->format('d M') }}</div>@endif
                            </td>
                            <td class="text-end"><a href="{{ route('report-approvals.open', [$r->class_id, $termId, $sessionId]) }}" class="action-btn btn-open"><i class="ri-eye-line"></i>Open</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-cb.card>

    @can('Approve report cards')
        <x-cb.card title="Settings" icon="ri-settings-3-line">
            <form method="POST" action="{{ route('report-approvals.settings') }}">@csrf
                <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="enforce" value="1" @checked($settings->is_active)>
                    <span class="form-check-label"><strong>Require approval before release</strong> — students and parents only see a term's results (portal, parent app, sent report cards) after the class is approved.</span></label>
                <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="notify" value="1" @checked($settings->config['notify'] ?? true)>
                    <span class="form-check-label">Notify students and parents in the portal when a class is approved</span></label>
                <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="require_comments" value="1" @checked($settings->config['require_comments'] ?? false)>
                    <span class="form-check-label">Class teachers can only submit when every student has a class teacher comment</span></label>
                <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save settings</button>
                @if(!$settings->is_active)<span class="small text-muted ms-2">Switch this on only once earlier terms are approved, or students lose access to them.</span>@endif
            </form>
        </x-cb.card>
    @endcan
</div>
</div>
</div>
@endsection
