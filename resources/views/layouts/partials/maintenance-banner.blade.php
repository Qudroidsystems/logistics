{{-- Maintenance status + scheduled-downtime countdown.
     The countdown is shown to EVERYONE who is signed in, so the school gets
     advance warning. The manage/cancel controls show only to admins. --}}
@php
    $mb = null;
    try {
        if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('maintenance_settings')) {
            $mb = \App\Models\MaintenanceSetting::current();
        }
    } catch (\Throwable $e) { $mb = null; }
    $canManage = false;
    try { $canManage = $mb && auth()->user()->can('Manage maintenance mode'); } catch (\Throwable $e) {}
@endphp
@if($mb && $mb->is_active && $canManage)
    <div class="alert alert-danger d-flex align-items-center justify-content-between flex-wrap gap-2 m-3 mb-0" role="alert">
        <span><i class="ri-tools-line me-1"></i><strong>Maintenance mode is ON.</strong> Only allowed users can use the portal right now.</span>
        <a href="{{ route('maintenance.settings') }}" class="btn btn-sm btn-light">Manage / turn off</a>
    </div>
@elseif($mb && $mb->isScheduledPending())
    <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 m-3 mb-0" role="alert">
        <span>
            <i class="ri-calendar-event-line me-1"></i>
            <strong>Planned maintenance:</strong> the portal will be unavailable from
            <strong>{{ $mb->scheduled_at->format('D, d M Y \a\t g:i A') }}</strong>
            (<span id="mtCountdown" data-at="{{ $mb->scheduled_at->toIso8601String() }}">{{ $mb->scheduled_at->diffForHumans() }}</span>).
            {{ $mb->scheduled_note ? ' ' . e($mb->scheduled_note) : 'Please save your work and finish up before then.' }}
        </span>
        @if($canManage)<a href="{{ route('maintenance.settings') }}" class="btn btn-sm btn-light">Change / cancel</a>@endif
    </div>
    <script>
    (function () {
        var el = document.getElementById('mtCountdown'); if (!el) return;
        var at = new Date(el.dataset.at).getTime();
        function tick() {
            var d = at - Date.now();
            if (d <= 0) { el.textContent = 'any moment now'; return; }
            var days = Math.floor(d / 8.64e7),
                h = Math.floor((d % 8.64e7) / 3.6e6),
                m = Math.floor((d % 3.6e6) / 6e4),
                s = Math.floor((d % 6e4) / 1000);
            el.textContent = 'in ' + (days > 0 ? days + 'd ' : '') + (days > 0 || h > 0 ? h + 'h ' : '') + m + 'm ' + s + 's';
        }
        tick(); setInterval(tick, 1000);
    })();
    </script>
@endif
