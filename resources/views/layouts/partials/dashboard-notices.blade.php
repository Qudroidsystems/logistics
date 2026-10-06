{{-- resources/views/layouts/partials/dashboard-notices.blade.php — latest notifications card --}}
@php
    $dnItems = collect();
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
            $dnItems = auth()->user()->notifications()->limit(5)->get();
        }
    } catch (\Throwable $e) {}
@endphp
@if($dnItems->isNotEmpty())
<div class="card mb-3" style="border-radius:14px;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="ri-megaphone-line me-1"></i>School notices &amp; updates</h6>
        <a href="{{ route('notifications.index') }}" class="small">View all</a>
    </div>
    <div class="list-group list-group-flush">
        @foreach($dnItems as $n)
            <a href="{{ route('notifications.open', $n->id) }}" class="list-group-item list-group-item-action d-flex gap-2 {{ $n->read_at ? '' : 'fw-semibold' }}">
                <i class="{{ $n->data['icon'] ?? 'ri-notification-3-line' }}" style="color:#0d9488;font-size:18px;"></i>
                <span class="flex-grow-1"><span class="d-block">{{ $n->data['title'] ?? 'Notification' }}</span>
                    <small class="text-muted fw-normal">{{ mb_strimwidth($n->data['body'] ?? '', 0, 110, '…') }}</small></span>
                <small class="text-muted fw-normal text-nowrap">{{ $n->created_at->diffForHumans() }}</small>
            </a>
        @endforeach
    </div>
</div>
@endif
