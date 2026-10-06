{{-- resources/views/notices/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="School Notices" icon="ri-megaphone-line"
               subtitle="Tell parents and staff about tests, exams, holidays, midterm, meetings and more — by SMS, WhatsApp and email.">
        <x-slot:actions>
            @can('Create notices')
                <a class="cb-hero-btn" href="{{ route('notices.create') }}"><i class="ri-add-line"></i>New notice</a>
            @endcan
            @can('Manage notification settings')
                <a class="cb-hero-btn" href="{{ route('notices.settings') }}"><i class="ri-settings-3-line"></i>Settings</a>
            @endcan
        </x-slot:actions>
        <x-slot:pills>
            @foreach($channels as $c)
                <span class="cb-meta-pill"><i class="{{ $c['enabled'] ? 'ri-checkbox-circle-line' : 'ri-close-circle-line' }}"></i>{{ $c['label'] }}: {{ !$c['enabled'] ? 'off' : ($c['live'] ? 'on' : 'log only') }}</span>
            @endforeach
        </x-slot:pills>
    </x-cb.hero>

    @foreach(['success' => 'info', 'error' => 'warning'] as $f => $cls)
        @if(session($f))<div class="cb-banner {{ $cls }}"><i class="ri-information-line"></i><div>{{ session($f) }}</div></div>@endif
    @endforeach

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6"><x-cb.stat label="Scheduled" :value="$stats['scheduled']" icon="ri-time-line" accent="sky" /></div>
        <div class="col-lg-3 col-6"><x-cb.stat label="Sent this month" :value="$stats['month']" icon="ri-send-plane-line" accent="teal" /></div>
        <div class="col-lg-3 col-6"><x-cb.stat label="Messages delivered" :value="number_format($stats['delivered'])" icon="ri-mail-check-line" accent="green" hint="This month" /></div>
        <div class="col-lg-3 col-6"><x-cb.stat label="Failed messages" :value="number_format($stats['failed'])" icon="ri-error-warning-line" accent="rose" hint="This month" /></div>
    </div>

    <x-cb.card title="Notices" icon="ri-list-check" :count="$notices->total()" :flush="true">
        <form method="GET" class="cb-toolbar">
            <div class="cb-search"><i class="ri-search-line"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search by title" aria-label="Search"></div>
            <select name="type" class="cb-select" onchange="this.form.submit()" aria-label="Type">
                <option value="">All types</option>
                @foreach(\App\Models\SchoolNotice::TYPES as $k => $t)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $t['label'] }}</option>@endforeach
            </select>
            <select name="status" class="cb-select" onchange="this.form.submit()" aria-label="Status">
                <option value="">All statuses</option>
                @foreach(\App\Models\SchoolNotice::STATUS as $k => $s)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $s['label'] }}</option>@endforeach
            </select>
        </form>

        @if($notices->isEmpty())
            <div class="empty-state"><i class="ri-megaphone-line"></i><h6>No notices yet</h6><p>Create one to inform parents about an upcoming test, exam or holiday.</p></div>
        @else
            <div class="table-responsive">
                <table class="cb-table mb-0">
                    <thead><tr><th>Notice</th><th>Event date</th><th>Channels</th><th>Status</th><th class="text-end">Delivered</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach($notices as $n)
                        <tr>
                            <td><div class="fw-semibold"><i class="{{ \App\Models\SchoolNotice::TYPES[$n->type]['icon'] ?? 'ri-megaphone-line' }} me-1"></i>{{ $n->title }}</div><small class="text-muted">{{ $n->typeLabel() }}</small></td>
                            <td>{{ $n->event_date?->format('D j M Y') ?? '—' }}</td>
                            <td>@foreach($n->channels ?? [] as $c)<span class="status-pill st-muted me-1">{{ ['whatsapp' => 'WhatsApp', 'portal' => 'Portal'][$c] ?? strtoupper($c) }}</span>@endforeach</td>
                            <td>
                                <span class="status-pill {{ \App\Models\SchoolNotice::STATUS[$n->status]['pill'] ?? 'st-muted' }}">{{ \App\Models\SchoolNotice::STATUS[$n->status]['label'] ?? $n->status }}</span>
                                @if($n->status === 'scheduled' && $n->send_at)<br><small class="text-muted">{{ $n->send_at->format('j M, g:i a') }}</small>@endif
                            </td>
                            <td class="text-end">{{ number_format($n->sent_count) }}@if($n->failed_count)<br><small class="text-danger">{{ $n->failed_count }} failed</small>@endif</td>
                            <td class="text-end text-nowrap">
                                <a class="action-btn btn-open" href="{{ route('notices.show', $n) }}"><i class="ri-eye-line"></i>View</a>
                                @if($n->isEditable())
                                    @can('Create notices')<a class="action-btn btn-go" href="{{ route('notices.edit', $n) }}"><i class="ri-edit-line"></i>Edit</a>@endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $notices->links() }}</div>
        @endif
    </x-cb.card>

</div>
</div>
</div>
@endsection
