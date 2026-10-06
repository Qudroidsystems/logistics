{{-- resources/views/notifications/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Notifications" icon="ri-notification-3-line" subtitle="School notices, results, payments and alerts for your account.">
        @if($unread)
            <x-slot:actions>
                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
                    <button class="cb-hero-btn"><i class="ri-check-double-line"></i>Mark all as read</button></form>
            </x-slot:actions>
        @endif
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-information-line"></i><div>{{ session('success') }}</div></div>@endif

    <x-cb.card title="Inbox" icon="ri-inbox-line" :count="$notifications->total()" :flush="true">
        <div class="cb-toolbar">
            <div class="term-chips">
                <a class="term-chip {{ request('filter') !== 'unread' ? 'active' : '' }}" href="{{ route('notifications.index') }}">All</a>
                <a class="term-chip {{ request('filter') === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['filter' => 'unread']) }}">Unread ({{ $unread }})</a>
            </div>
        </div>
        @if($notifications->isEmpty())
            <div class="empty-state"><i class="ri-notification-off-line"></i><h6>No notifications</h6><p>You're all caught up.</p></div>
        @else
            <div class="list-group list-group-flush">
                @foreach($notifications as $n)
                    <a href="{{ route('notifications.open', $n->id) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $n->read_at ? '' : 'pn-unread' }}">
                        <span class="pn-icon"><i class="{{ $n->data['icon'] ?? 'ri-notification-3-line' }}"></i></span>
                        <span class="flex-grow-1">
                            <span class="d-flex justify-content-between gap-2"><strong>{{ $n->data['title'] ?? 'Notification' }}</strong><small class="text-muted text-nowrap">{{ $n->created_at->diffForHumans() }}</small></span>
                            <span class="d-block small text-muted" style="white-space:pre-line">{{ $n->data['body'] ?? '' }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
            <div class="p-3">{{ $notifications->links() }}</div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
<style>
.pn-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(13,148,136,.1); color: var(--cb-teal); display: inline-flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.pn-unread { background: rgba(13,148,136,.05); } .pn-unread strong::before { content: '● '; color: var(--cb-teal); }
</style>
@endsection
