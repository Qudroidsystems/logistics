@extends('layouts.master')

@section('content')
@php
    $movingById = collect($moving)->keyBy('id');
    $reassignable = \App\Modules\Dispatch\ManualAssignmentService::REASSIGNABLE;
@endphp
<style>
    .dz-list { min-height: 64px; border-radius: 8px; padding: 6px; transition: background .15s; }
    .dz-list.dz-over, .dz-list.sortable-ghost-parent { background: rgba(10, 179, 156, .08); }
    .dz-job { background: var(--vz-card-bg, #fff); border: 1px solid var(--vz-border-color, #e9ebec); border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; cursor: grab; touch-action: none; }
    .dz-job.locked { cursor: default; opacity: .85; }
    .dz-job.sortable-ghost { opacity: .4; }
    .dz-job.dz-saving { opacity: .6; pointer-events: none; }
    .dz-lane { border: 1px solid var(--vz-border-color, #e9ebec); border-radius: 10px; height: 100%; }
    .dz-lane-head { padding: 8px 10px; border-bottom: 1px solid var(--vz-border-color, #e9ebec); }
    .dz-list[data-lane="driver"]:empty::before { content: 'Drop a job here'; display: block; text-align: center; color: #878a99; font-size: 12px; padding: 18px 0; }
    .dz-lane.full .dz-lane-head { background: rgba(240, 101, 72, .07); }
</style>
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Dispatch board" icon="ri-layout-grid-line" subtitle="Drag a job onto a driver to give it to them. Drag between drivers to swap before pickup." :back="route('provider.dashboard')" back-label="Dashboard">
        <x-slot:actions><a class="cb-hero-btn" href="{{ route('provider.booking.new') }}"><i class="ri-add-line"></i> New booking</a></x-slot:actions>
    </x-cb.hero>
    @include('provider._flash')
    <div id="dzMsg" class="cb-banner warning d-none" role="alert"><i class="ri-error-warning-line"></i><div></div></div>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-cb.card title="Needs a driver" icon="ri-user-received-line" :count="count($waiting)">
                <div class="dz-list" id="dzWaiting" data-lane="waiting">
                @forelse($waiting as $j)
                    <div class="dz-job" data-job="{{ $j->public_id }}" data-assign="{{ route('provider.job.assign', $j->public_id) }}">
                        <div class="d-flex justify-content-between"><a href="{{ route('provider.job', $j->public_id) }}" class="fw-semibold">{{ $j->order_number }}</a>
                            <span class="small text-muted">{{ \Illuminate\Support\Carbon::parse($j->updated_at)->diffForHumans(null, true) }}</span></div>
                        <div class="small">{{ $line($j->id, 'pickup') }} to {{ $line($j->id, 'dropoff') }}</div>
                        @if($j->needs_manual_dispatch)<span class="badge bg-warning text-dark">Handed back or no match</span>@endif
                        {{-- Without a mouse or touch screen, the job can still be given out here. --}}
                        <form method="POST" action="{{ route('provider.job.assign', $j->public_id) }}" class="d-flex gap-1 mt-1 dz-fallback">@csrf
                            <select name="driver_user_id" class="form-select form-select-sm" required aria-label="Give {{ $j->order_number }} to">
                                <option value="">Choose a driver</option>
                                @foreach($drivers as $d)<option value="{{ $d['user_id'] }}">{{ $d['name'] }} · {{ $d['active_jobs'] }}/{{ $d['max_active_jobs'] }}</option>@endforeach
                            </select>
                            <button class="btn btn-sm btn-outline-primary">Assign</button>
                        </form>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 dz-empty">Nothing is waiting for a driver.</div>
                @endforelse
                </div>
            </x-cb.card>
        </div>

        <div class="col-lg-8">
            <x-cb.card title="Drivers" icon="ri-steering-2-line" :count="count($drivers)">
                <div class="row g-2">
                @forelse($drivers as $d)
                    @php $jobs = collect($lanes[$d['user_id']] ?? [])->map(fn ($id) => $movingById[$id] ?? null)->filter(); @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="dz-lane {{ $d['active_jobs'] >= $d['max_active_jobs'] ? 'full' : '' }}" data-driver="{{ $d['user_id'] }}">
                            <div class="dz-lane-head d-flex justify-content-between align-items-center">
                                <div><div class="fw-semibold">{{ $d['name'] }}</div>
                                    <span class="badge {{ $d['availability'] === 'online' ? 'bg-success' : ($d['availability'] === 'on_job' ? 'bg-info' : 'bg-light text-dark') }}">{{ str_replace('_', ' ', $d['availability']) }}</span></div>
                                <span class="small text-muted"><span class="dz-load">{{ $d['active_jobs'] }}</span> / <span class="dz-max">{{ $d['max_active_jobs'] }}</span></span>
                            </div>
                            <div class="dz-list" data-lane="driver" data-driver="{{ $d['user_id'] }}">
                                @foreach($jobs as $j)
                                    @php $movable = in_array($j->status, $reassignable, true); @endphp
                                    <div class="dz-job {{ $movable ? '' : 'locked' }}" data-job="{{ $j->public_id }}" data-assign="{{ route('provider.job.assign', $j->public_id) }}">
                                        <div class="d-flex justify-content-between"><a href="{{ route('provider.job', $j->public_id) }}" class="fw-semibold">{{ $j->order_number }}</a>
                                            <span class="badge bg-light text-dark">{{ str_replace('_', ' ', $j->status) }}</span></div>
                                        <div class="small">{{ $line($j->id, 'pickup') }} to {{ $line($j->id, 'dropoff') }}</div>
                                        @if(($issues[$j->id] ?? 0) > 0)<span class="badge bg-danger">Driver reported a problem</span>@endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-4">No approved drivers yet. Approve one on the Team page.</div>
                @endforelse
                </div>
            </x-cb.card>
        </div>
    </div>

    <x-cb.card title="On the road" icon="ri-route-line" :count="count($moving)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th>Driver</th><th>Status</th><th>Route</th></tr></thead>
            <tbody>
            @forelse($moving as $j)
                <tr>
                    <td><a href="{{ route('provider.job', $j->public_id) }}" class="fw-semibold">{{ $j->order_number }}</a>
                        @if(($issues[$j->id] ?? 0) > 0)<div><span class="badge bg-danger">Driver reported a problem</span></div>@endif</td>
                    <td>{{ $driverOf[$j->id] ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', $j->status) }}</span></td>
                    <td class="small">{{ $line($j->id, 'pickup') }} to {{ $line($j->id, 'dropoff') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No jobs on the road.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>

<script src="{{ asset('theme/layouts/assets/libs/sortablejs/Sortable.min.js') }}"></script>
<script>
(function () {
    if (!window.Sortable) { return; } // the dropdowns still work
    var token = document.querySelector('meta[name="csrf-token"]').content, busy = false, lastMove = 0;
    var msg = document.getElementById('dzMsg');
    function say(text) { msg.querySelector('div').textContent = text; msg.classList.remove('d-none'); setTimeout(function () { msg.classList.add('d-none'); }, 8000); }
    function load(lane, by) { if (!lane) { return; } var el = lane.closest('.dz-lane').querySelector('.dz-load'), n = parseInt(el.textContent, 10) + by; el.textContent = n;
        lane.closest('.dz-lane').classList.toggle('full', n >= parseInt(lane.closest('.dz-lane').querySelector('.dz-max').textContent, 10)); }
    document.querySelectorAll('.dz-fallback').forEach(function (f) { f.classList.add('d-none'); });

    var opts = {
        group: {name: 'dispatch', pull: true, put: true}, animation: 150, filter: '.locked, a, select, button', preventOnFilter: false,
        draggable: '.dz-job', ghostClass: 'sortable-ghost', delay: 120, delayOnTouchOnly: true,
        onStart: function () { busy = true; }, onEnd: function () { busy = false; lastMove = Date.now(); },
        onAdd: function (e) {
            var card = e.item, from = e.from, to = e.to, driver = to.dataset.driver;
            card.classList.add('dz-saving');
            fetch(card.dataset.assign, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({driver_user_id: parseInt(driver, 10), reason: 'moved on the dispatch board'})})
                .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return {ok: r.ok, j: j}; }); })
                .then(function (res) {
                    card.classList.remove('dz-saving');
                    if (!res.ok) { throw new Error(res.j.message || 'That driver could not take the job.'); }
                    load(to, 1); if (from.dataset.lane === 'driver') { load(from, -1); }
                })
                .catch(function (err) {
                    card.classList.remove('dz-saving');
                    from.insertBefore(card, from.children[e.oldIndex] || null); // put it back where it was
                    say(err.message);
                });
        }
    };
    // Jobs never go back to "Needs a driver" by dragging; a driver hands a job back from their phone.
    Sortable.create(document.getElementById('dzWaiting'), Object.assign({}, opts, {group: {name: 'dispatch', pull: true, put: false}, sort: false}));
    document.querySelectorAll('.dz-list[data-lane="driver"]').forEach(function (el) { Sortable.create(el, opts); });

    // Keep the board fresh, but never while someone is moving a job.
    setInterval(function () { if (!busy && Date.now() - lastMove > 15000 && !document.querySelector('.dz-saving')) { location.reload(); } }, 45000);
})();
</script>
@endsection
