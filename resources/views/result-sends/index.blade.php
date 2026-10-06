{{-- resources/views/result-sends/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Send Results to Parents" icon="ri-mail-send-line"
               subtitle="Report cards sent by email, WhatsApp and SMS, with delivery and download tracking.">
        <x-slot:actions>
            @can('Create result-sends')<a class="cb-hero-btn" href="{{ route('result-sends.create') }}"><i class="ri-add-line"></i>Send results</a>@endcan
            @can('Manage notification settings')<a class="cb-hero-btn" href="{{ route('notices.settings') }}"><i class="ri-settings-3-line"></i>Channel settings</a>@endcan
        </x-slot:actions>
    </x-cb.hero>

    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6"><x-cb.stat label="Batches sent" :value="$stats['batches']" icon="ri-stack-line" accent="sky" /></div>
        <div class="col-lg-3 col-6"><x-cb.stat label="Messages delivered" :value="number_format($stats['delivered'])" icon="ri-mail-check-line" accent="teal" /></div>
        <div class="col-lg-3 col-6"><x-cb.stat label="Failed messages" :value="number_format($stats['failed'])" icon="ri-error-warning-line" accent="rose" /></div>
        <div class="col-lg-3 col-6"><x-cb.stat label="Link downloads" :value="number_format($stats['downloads'])" icon="ri-download-2-line" accent="violet" /></div>
    </div>

    <x-cb.card title="History" icon="ri-history-line" :count="$sends->total()" :flush="true">
        @if($sends->isEmpty())
            <div class="empty-state"><i class="ri-mail-send-line"></i><h6>No results sent yet</h6><p>Send report cards to parents once results are vetted.</p></div>
        @else
            <div class="table-responsive">
                <table class="cb-table mb-0">
                    <thead><tr><th>Date</th><th>Term</th><th>Students</th><th>Channels</th><th class="text-end">Delivered</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($sends as $s)
                        <tr>
                            <td>{{ $s->created_at->format('j M Y, g:i a') }}<br><small class="text-muted">{{ $s->creator->name ?? '' }}</small></td>
                            <td>{{ $s->term->term ?? '' }} · {{ $s->session->session ?? '' }}</td>
                            <td>{{ $s->students - $s->skipped }} sent<br><small class="text-muted">{{ $s->skipped }} skipped</small></td>
                            <td>{{ collect($s->channels)->map(fn ($c) => $c === 'whatsapp' ? 'WhatsApp' : strtoupper($c))->join(', ') }}</td>
                            <td class="text-end">{{ number_format($s->messages_sent) }}@if($s->messages_failed)<br><small class="text-danger">{{ $s->messages_failed }} failed</small>@endif</td>
                            <td><span class="status-pill {{ \App\Models\ResultSend::STATUS[$s->status]['pill'] ?? 'st-muted' }}">{{ \App\Models\ResultSend::STATUS[$s->status]['label'] ?? $s->status }}</span></td>
                            <td class="text-end"><a class="action-btn btn-open" href="{{ route('result-sends.show', $s) }}"><i class="ri-eye-line"></i>View</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $sends->links() }}</div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
@endsection
