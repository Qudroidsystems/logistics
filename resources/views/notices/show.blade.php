{{-- resources/views/notices/show.blade.php — notice detail + delivery report --}}
@extends('layouts.master')

@section('content')
@php
    $st    = \App\Models\SchoolNotice::STATUS[$notice->status] ?? ['label' => $notice->status, 'pill' => 'st-muted'];
    $names = ['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'portal' => 'In-portal'];
    $dPill = ['pending' => 'st-info', 'processing' => 'st-pending', 'done' => 'st-paid', 'cancelled' => 'st-muted', 'failed' => 'st-danger'];
    $mPill = ['queued' => 'st-info', 'sending' => 'st-pending', 'sent' => 'st-paid', 'failed' => 'st-danger', 'skipped' => 'st-muted'];
    $aud   = $notice->audience ?? [];
    $scopeLabel = ['school' => 'Whole school', 'classes' => count($aud['class_ids'] ?? []) . ' class(es)', 'categories' => count($aud['category_ids'] ?? []) . ' class categor(ies)', 'students' => count($aud['student_ids'] ?? []) . ' selected student(s)', 'none' => 'No parents'][$aud['scope'] ?? 'school'] ?? '';
    $failedTotal = $totals->sum(fn ($t) => $t['failed'] ?? 0);
    $canEdit = auth()->user()->can('Create notices');
@endphp

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero :title="$notice->title" icon="ri-megaphone-line" :subtitle="$notice->typeLabel() . ($notice->event_date ? ' · ' . $notice->event_date->format('D j M Y') . ($notice->event_end_date && !$notice->event_end_date->eq($notice->event_date) ? ' – ' . $notice->event_end_date->format('D j M Y') : '') : '')"
               :back="route('notices.index')" back-label="All notices">
        @if($canEdit)
            <x-slot:actions>
                @if($notice->isEditable())
                    <a class="cb-hero-btn" href="{{ route('notices.edit', $notice) }}"><i class="ri-edit-line"></i>Edit</a>
                @endif
                @if($notice->dispatches->where('status', 'pending')->isNotEmpty())
                    <form method="POST" action="{{ route('notices.cancel', $notice) }}" class="d-inline" onsubmit="return confirm('Cancel all scheduled sends and reminders for this notice?')">@csrf
                        <button class="cb-hero-btn"><i class="ri-close-circle-line"></i>Cancel scheduled</button></form>
                @endif
                @if($failedTotal)
                    <form method="POST" action="{{ route('notices.resend', $notice) }}" class="d-inline">@csrf
                        <button class="cb-hero-btn"><i class="ri-restart-line"></i>Resend {{ $failedTotal }} failed</button></form>
                @endif
                <form method="POST" action="{{ route('notices.duplicate', $notice) }}" class="d-inline">@csrf
                    <button class="cb-hero-btn"><i class="ri-file-copy-line"></i>Duplicate</button></form>
            </x-slot:actions>
        @endif
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-flag-line"></i>{{ $st['label'] }}</span>
            <span class="cb-meta-pill"><i class="ri-group-line"></i>{{ $scopeLabel }}{{ !empty($aud['include_staff']) ? ' + staff' : '' }}</span>
            <span class="cb-meta-pill"><i class="ri-send-plane-line"></i>{{ collect($notice->channels)->map(fn ($c) => $names[$c] ?? $c)->join(', ') }}</span>
            @if($notice->creator)<span class="cb-meta-pill"><i class="ri-user-line"></i>{{ $notice->creator->name }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach

    <div class="row g-3 mb-4">
        @foreach(array_diff($notice->channels ?? [], ['portal']) as $c)
            @php $t = $totals[$c] ?? collect(); $all = $t->sum(); @endphp
            <div class="col-md-4">
                <x-cb.stat :label="($names[$c] ?? $c) . ' delivered'" :value="number_format($t['sent'] ?? 0) . ' / ' . number_format($all)" :icon="$c === 'email' ? 'ri-mail-line' : ($c === 'sms' ? 'ri-message-2-line' : 'ri-whatsapp-line')"
                           :accent="($t['failed'] ?? 0) ? 'rose' : 'teal'" :hint="($t['failed'] ?? 0) . ' failed · ' . (($t['queued'] ?? 0) + ($t['sending'] ?? 0)) . ' waiting · ' . ($t['skipped'] ?? 0) . ' skipped'" />
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <x-cb.card title="Schedule" icon="ri-calendar-schedule-line" :flush="true">
                <ul class="list-unstyled mb-0">
                    @forelse($notice->dispatches as $d)
                        <li class="d-flex justify-content-between align-items-start gap-2 px-3 py-2 border-bottom">
                            <div><div class="fw-semibold">{{ $d->label ?? ucfirst($d->kind) }}</div>
                                <small class="text-muted">{{ $d->run_at->format('D j M Y, g:i a') }}</small>
                                @if($d->total)<br><small class="text-muted">{{ $d->sent }} sent · {{ $d->failed }} failed of {{ $d->total }}</small>@endif
                                @if($d->error)<br><small class="text-danger">{{ $d->error }}</small>@endif
                            </div>
                            <span class="status-pill {{ $dPill[$d->status] ?? 'st-muted' }}">{{ ucfirst($d->status) }}</span>
                        </li>
                    @empty
                        <li class="px-3 py-3 text-muted small">Not sent or scheduled yet.</li>
                    @endforelse
                </ul>
            </x-cb.card>

            <x-cb.card title="Message" icon="ri-chat-3-line">
                <div style="white-space:pre-wrap;font-size:13px">{{ $notice->message }}</div>
                @if($notice->sms_text)
                    <hr><small class="text-muted d-block mb-1">SMS version</small>
                    <div style="white-space:pre-wrap;font-size:13px">{{ $notice->sms_text }}</div>
                @endif
            </x-cb.card>
        </div>

        <div class="col-xl-8">
            <x-cb.card title="Delivery report" icon="ri-list-check-2" :count="$deliveries->total()" :flush="true">
                <form method="GET" class="cb-toolbar">
                    <div class="cb-search"><i class="ri-search-line"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Name, phone or email" aria-label="Search"></div>
                    <select name="channel" class="cb-select" onchange="this.form.submit()" aria-label="Channel">
                        <option value="">All channels</option>
                        @foreach($notice->channels ?? [] as $c)<option value="{{ $c }}" @selected(request('channel') === $c)>{{ $names[$c] ?? $c }}</option>@endforeach
                    </select>
                    <select name="status" class="cb-select" onchange="this.form.submit()" aria-label="Status">
                        <option value="">All statuses</option>
                        @foreach(['sent' => 'Sent', 'failed' => 'Failed', 'queued' => 'Waiting', 'skipped' => 'Skipped'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
                    </select>
                </form>
                @if($deliveries->isEmpty())
                    <div class="empty-state"><i class="ri-inbox-line"></i><h6>{{ $notice->status === 'scheduled' ? 'Waiting for the scheduled time' : 'No messages yet' }}</h6>
                        <p>{{ $busy ? 'Messages are being prepared. Refresh in a moment.' : 'Messages appear here once the notice is sent.' }}</p></div>
                @else
                    <div class="table-responsive">
                        <table class="cb-table mb-0">
                            <thead><tr><th>Recipient</th><th>Channel</th><th>Status</th><th>Time</th></tr></thead>
                            <tbody>
                            @foreach($deliveries as $m)
                                <tr>
                                    <td><div class="fw-semibold">{{ $m->recipient_name ?: '—' }}</div><small class="text-muted">{{ $m->recipient }}{{ $m->audience_type === 'staff' ? ' · staff' : '' }}</small></td>
                                    <td>{{ $names[$m->channel] ?? $m->channel }}</td>
                                    <td><span class="status-pill {{ $mPill[$m->status] ?? 'st-muted' }}">{{ ['queued' => 'Waiting', 'sending' => 'Sending'][$m->status] ?? ucfirst($m->status) }}</span>
                                        @if($m->error)<br><small class="{{ $m->status === 'failed' ? 'text-danger' : 'text-muted' }}">{{ $m->error }}</small>@endif</td>
                                    <td><small>{{ ($m->sent_at ?? $m->updated_at)->format('j M, g:i a') }}</small></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">{{ $deliveries->links() }}</div>
                @endif
            </x-cb.card>
        </div>
    </div>

</div>
</div>
</div>

@if($busy)
<script>setTimeout(() => window.location.reload(), 6000);</script>
@endif
@endsection
