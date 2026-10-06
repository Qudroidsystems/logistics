{{-- resources/views/result-sends/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $names = ['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'];
    $iPill = ['pending' => 'st-info', 'generating' => 'st-pending', 'sending' => 'st-pending', 'done' => 'st-paid', 'skipped' => 'st-muted', 'failed' => 'st-danger'];
    $dPill = ['queued' => 'st-info', 'sending' => 'st-pending', 'sent' => 'st-paid', 'failed' => 'st-danger', 'skipped' => 'st-muted'];
    $st    = \App\Models\ResultSend::STATUS[$send->status] ?? ['label' => $send->status, 'pill' => 'st-muted'];
    $toSend = $send->students - ($itemCounts['skipped'] ?? 0);
    $doneN  = ($itemCounts['done'] ?? 0) + ($itemCounts['failed'] ?? 0);
    $pct    = $toSend > 0 ? min(100, round($doneN / $toSend * 100)) : 100;
    $canAct = auth()->user()->can('Create result-sends');
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero :title="($send->term->term ?? '') . ' report cards'" icon="ri-mail-send-line"
               :subtitle="($send->session->session ?? '') . ' · ' . $classNames->join(', ')"
               :back="route('result-sends.index')" back-label="History">
        @if($canAct)
            <x-slot:actions>
                @if($send->messages_failed || ($itemCounts['failed'] ?? 0))
                    <form method="POST" action="{{ route('result-sends.resend', $send) }}" class="d-inline">@csrf
                        <button class="cb-hero-btn"><i class="ri-restart-line"></i>Resend failed</button></form>
                @endif
                @if($busy)
                    <form method="POST" action="{{ route('result-sends.cancel', $send) }}" class="d-inline" onsubmit="return confirm('Stop sending the remaining report cards?')">@csrf
                        <button class="cb-hero-btn"><i class="ri-stop-circle-line"></i>Stop</button></form>
                @endif
            </x-slot:actions>
        @endif
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-flag-line"></i>{{ $st['label'] }}</span>
            <span class="cb-meta-pill"><i class="ri-send-plane-line"></i>{{ collect($send->channels)->map(fn ($c) => $names[$c] ?? $c)->join(', ') }}</span>
            <span class="cb-meta-pill"><i class="ri-time-line"></i>Links valid {{ $send->link_days }} days</span>
            @if($send->creator)<span class="cb-meta-pill"><i class="ri-user-line"></i>{{ $send->creator->name }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach

    @if($busy)
        <div class="cb-card"><div class="cb-card-body">
            <div class="d-flex justify-content-between small mb-1"><span>Generating and sending report cards…</span><span>{{ $doneN }} / {{ $toSend }}</span></div>
            <div class="progress-track"><div class="progress-fill" style="width: {{ $pct }}%"></div></div>
            <small class="text-muted">This page refreshes by itself. Large classes continue in the background every minute.</small>
        </div></div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6"><x-cb.stat label="Report cards" :value="$toSend" icon="ri-file-list-3-line" accent="sky" :hint="($itemCounts['skipped'] ?? 0) . ' skipped'" /></div>
        @foreach($send->channels ?? [] as $c)
            @php $t = $byChannel[$c] ?? collect(); @endphp
            <div class="col-lg-3 col-6"><x-cb.stat :label="($names[$c] ?? $c) . ' delivered'" :value="number_format($t['sent'] ?? 0) . ' / ' . number_format($t->sum())"
                :icon="$c === 'email' ? 'ri-mail-line' : ($c === 'sms' ? 'ri-message-2-line' : 'ri-whatsapp-line')" :accent="($t['failed'] ?? 0) ? 'rose' : 'teal'" :hint="($t['failed'] ?? 0) . ' failed'" /></div>
        @endforeach
    </div>

    <x-cb.card title="Students" icon="ri-group-line" :count="$items->total()" :flush="true">
        <form method="GET" class="cb-toolbar">
            <select name="status" class="cb-select" onchange="this.form.submit()" aria-label="Status">
                <option value="">All</option>
                @foreach(['done' => 'Sent', 'failed' => 'Failed', 'pending' => 'Waiting', 'skipped' => 'Skipped'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
            </select>
        </form>
        <div class="table-responsive">
            <table class="cb-table mb-0">
                <thead><tr><th>Student</th><th>Status</th><th>Messages</th><th>Link</th><th></th></tr></thead>
                <tbody>
                @foreach($items as $it)
                    <tr>
                        <td><div class="fw-semibold">{{ trim(($it->student->lastname ?? '') . ' ' . ($it->student->firstname ?? '')) }}</div><small class="text-muted">{{ $it->student->admissionNo ?? '' }}</small></td>
                        <td><span class="status-pill {{ $iPill[$it->status] ?? 'st-muted' }}">{{ ['done' => 'Sent', 'pending' => 'Waiting', 'generating' => 'Generating', 'sending' => 'Sending'][$it->status] ?? ucfirst($it->status) }}</span>
                            @if($it->skip_reason || $it->error)<br><small class="{{ $it->status === 'failed' ? 'text-danger' : 'text-muted' }}">{{ $it->skip_reason ?: $it->error }}</small>@endif</td>
                        <td>
                            @forelse($it->deliveries as $d)
                                <div class="small"><span class="status-pill {{ $dPill[$d->status] ?? 'st-muted' }}">{{ $names[$d->channel] ?? $d->channel }}</span>
                                    {{ $d->recipient_name }} <span class="text-muted">{{ $d->recipient }}</span>
                                    @if($d->error)<br><span class="text-danger">{{ $d->error }}</span>@endif</div>
                            @empty
                                <small class="text-muted">—</small>
                            @endforelse
                        </td>
                        <td>@if($it->token)<small>{{ $it->downloads }} download{{ $it->downloads == 1 ? '' : 's' }}<br><span class="text-muted">until {{ $it->link_expires_at?->format('j M') }}</span></small>@else — @endif</td>
                        <td class="text-end">@if($it->pdf_path)<a class="action-btn btn-open" target="_blank" href="{{ route('result-sends.pdf', $it) }}"><i class="ri-file-pdf-line"></i>PDF</a>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $items->links() }}</div>
    </x-cb.card>

    <x-cb.card title="Message" icon="ri-chat-3-line">
        <div style="white-space:pre-wrap;font-size:13px">{{ $send->message }}</div>
        @if($send->sms_text)<hr><small class="text-muted d-block mb-1">SMS</small><div style="font-size:13px">{{ $send->sms_text }}</div>@endif
    </x-cb.card>
</div>
</div>
</div>
@if($busy)<script>setTimeout(() => window.location.reload(), 8000);</script>@endif
@endsection
