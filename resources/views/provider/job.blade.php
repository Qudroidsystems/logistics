@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => Money::symbol().number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Order '.$order->order_number" icon="ri-route-line" :subtitle="'Tracking '.$s->tracking_code" :back="route('provider.jobs')" back-label="Jobs">
        <x-slot:pills>
            <span class="cb-meta-pill">{{ str_replace('_', ' ', $s->status) }}</span>
            <span class="cb-meta-pill">{{ $naira($order->total) }}</span>
        </x-slot:pills>
        @php $editable = \App\Modules\Backoffice\JobEditService::editable($s->status); @endphp
        @if($editable['pickup'] || $editable['dropoff'])
            <x-slot:actions><a class="cb-hero-btn" href="{{ route('provider.job.edit', $s->public_id) }}"><i class="ri-edit-2-line"></i> Edit details</a></x-slot:actions>
        @endif
    </x-cb.hero>
    @include('provider._flash')
    <div class="row g-3">
        <div class="col-lg-8">
            @include('partials.booking-details', ['bookingShipmentId' => $s->id])
            <x-cb.card title="If the delivery fails" icon="ri-file-list-3-line" class="mb-3">
                @foreach($terms as $line)<p class="small mb-2">{{ $line }}</p>@endforeach
                <div class="small text-muted">Agreed with the customer when the job was booked.</div>
            </x-cb.card>
            <x-cb.card title="How the hand-over is confirmed" icon="ri-shield-check-line" class="mb-3">
                @php $L = \App\Modules\Verification\VerificationPolicy::LABELS; @endphp
                <div class="small"><strong>Pickup:</strong> {{ $agreedProofs['pickup'] ? implode(', ', array_map(fn ($m) => $L[$m] ?? $m, $agreedProofs['pickup'])) : 'being at the pickup' }}</div>
                <div class="small"><strong>Drop-off:</strong> {{ $agreedProofs['dropoff'] ? implode(', ', array_map(fn ($m) => $L[$m] ?? $m, $agreedProofs['dropoff'])) : 'being at the drop-off' }}</div>
                @if($agreedProofs['authorised_receivers'])<div class="small"><strong>Only to:</strong> {{ implode(', ', $agreedProofs['authorised_receivers']) }}</div>@endif
                <div class="small text-muted mt-1">Agreed with the customer when the job was booked.</div>
                @foreach($exceptions as $e)
                    <div class="border rounded p-2 mt-2 small">
                        <div class="d-flex justify-content-between"><strong>Driver cannot provide: {{ strtolower($e['label']) }}</strong>
                            <span class="badge {{ $e['status'] === 'approved' ? 'bg-success' : ($e['status'] === 'declined' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ ucfirst($e['status']) }}</span></div>
                        <div class="text-muted">“{{ $e['reason'] }}”</div>
                        @if($e['status'] === 'pending')
                            <form method="POST" action="{{ route('provider.job.exception', [$s->public_id, $e['id']]) }}" class="d-flex gap-2 mt-2">@csrf
                                <input name="note" class="form-control form-control-sm" maxlength="255" placeholder="Note for the driver (optional)">
                                <button name="decision" value="approve" class="btn btn-sm btn-success">Approve</button>
                                <button name="decision" value="decline" class="btn btn-sm btn-outline-danger">Decline</button>
                            </form>
                        @elseif($e['decision_note'])<div>Note: {{ $e['decision_note'] }}</div>@endif
                    </div>
                @endforeach
            </x-cb.card>
            @include('partials.proofs')
            <x-cb.card title="Arrival and alerts" icon="ri-notification-3-line" class="mb-3">
                @if($eta)<div class="small mb-1">Rider is <strong>{{ $eta['label'] }}</strong> from the {{ $eta['stop_type'] === 'pickup' ? 'pickup' : 'drop-off' }}@if($eta['stale']) <span class="text-warning">(last position is old, rough guess)</span>@endif.</div>@endif
                @if($receiverReply)<div class="small mb-2">Receiver said: <strong>{{ $receiverReply['label'] }}</strong>@if($receiverReply['note']) "{{ $receiverReply['note'] }}"@endif</div>@endif
                @forelse($alertsSent as $a)
                    <div class="small d-flex justify-content-between border-bottom py-1"><span>{{ $a['label'] }} ({{ $a['stop_type'] === 'pickup' ? 'pickup' : 'drop-off' }})@if($a['status'] === 'skipped') <span class="text-muted">skipped, a closer alert went out</span>@elseif($a['channels']) <span class="text-muted">by {{ implode(' and ', array_map(fn ($c) => $c === 'sms' ? 'text' : 'app', $a['channels'])) }}</span>@endif</span><span class="text-muted">{{ \Illuminate\Support\Carbon::parse($a['at'])->format('j M H:i') }}</span></div>
                @empty
                    <span class="small text-muted">No alerts yet. <a href="{{ route('provider.alerts') }}">Choose when they go out</a>.</span>
                @endforelse
            </x-cb.card>
            <x-cb.card title="Custody trail" icon="ri-links-line" class="mb-3">
                @if($custody['events'] === 0)
                    <span class="small text-muted">No scans or proofs yet.</span>
                @elseif($custody['ok'])
                    <div class="small text-success mb-2"><i class="ri-shield-check-line"></i> {{ $custody['events'] }} events, none changed since they were recorded.</div>
                @else
                    <div class="small text-danger mb-2"><i class="ri-alarm-warning-line"></i> The trail does not add up from event {{ $custody['broken_at'] }}. A record was changed after it was written. Contact support.</div>
                @endif
                @foreach($custodyEvents as $ev)
                    <div class="small d-flex justify-content-between border-bottom py-1"><span>{{ $ev['seq'] }}. {{ $ev['kind'] === 'scan' ? 'Parcel scanned ('.($ev['payload']['stage'] ?? '').')' : ($ev['kind'] === 'proof' ? 'Proof at '.($ev['payload']['stop_type'] ?? 'stop') : 'Exception '.($ev['payload']['status'] ?? '')) }}</span><span class="text-muted">{{ \Illuminate\Support\Carbon::parse($ev['at'])->format('j M H:i') }} · <code title="{{ $ev['hash'] }}">{{ substr($ev['hash'], 0, 8) }}</code></span></div>
                @endforeach
            </x-cb.card>
            @if(count($chat) || count($calls))
            <x-cb.card title="Rider and customer chat" icon="ri-chat-3-line" class="mb-3">
                @foreach($chat as $m)
                    <div class="small border-bottom py-1"><strong>{{ $m['side'] }}</strong> <span class="text-muted">{{ \Illuminate\Support\Carbon::parse($m['at'])->format('j M H:i') }}</span>@if($m['flagged']) <span class="badge bg-warning-subtle text-warning" title="Has a phone number in it">number shared</span>@endif<br>{{ $m['body'] }}</div>
                @endforeach
                @foreach($calls as $c)
                    <div class="small text-muted py-1"><i class="ri-phone-line"></i> {{ \Illuminate\Support\Carbon::parse($c->created_at)->format('j M H:i') }} · {{ $c->purpose === 'inbound' ? 'Call to the shared number' : ($c->purpose === 'driver' ? 'Customer called the rider' : 'Rider called the '.(['customer' => 'customer', 'pickup' => 'sender', 'dropoff' => 'receiver'][$c->purpose] ?? $c->purpose)) }} · {{ $c->outcome }}@if($c->duration_s) · {{ gmdate('i:s', $c->duration_s) }}@endif</div>
                @endforeach
            </x-cb.card>
            @endif
            <x-cb.card title="Parcels" icon="ri-qr-code-line" class="mb-3">
                @forelse($packages as $p)
                    <div class="d-flex justify-content-between border-bottom py-1"><span>{{ $p['seq'] }}. {{ $p['description'] }}@if($p['quantity'] > 1) ×{{ $p['quantity'] }}@endif @if($p['fragile'])<span class="badge bg-warning-subtle text-warning">fragile</span>@endif</span><code>{{ $p['barcode'] }}</code></div>
                @empty
                    <span class="text-muted">No parcels recorded.</span>
                @endforelse
                @if(count($packages))<a class="btn btn-sm btn-outline-primary mt-2" target="_blank" href="{{ route('provider.job.labels', $s->public_id) }}"><i class="ri-printer-line"></i> Print labels</a>@endif
            </x-cb.card>
            <x-cb.card title="Timeline" icon="ri-time-line" :flush="true">
                <table class="table mb-0"><tbody>
                @forelse($timeline as $e)
                    <tr><td>{{ $e->seq }}</td><td>{{ str_replace('_', ' ', $e->type) }}</td><td class="text-muted small">{{ $e->actor_type }}</td><td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($e->created_at)->format('d M H:i') }}</td></tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No events yet.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>
        <div class="col-lg-4">
            <x-cb.card title="Chat with the rider" icon="ri-message-3-line" class="mb-3">
                <div id="dcBox" class="mb-2" style="max-height:280px;overflow-y:auto"><div class="small text-muted">Loading…</div></div>
                <div id="dcQuick" class="d-flex flex-wrap gap-1 mb-2"></div>
                <form id="dcForm" class="d-flex gap-2" hidden>
                    <input id="dcText" class="form-control form-control-sm" maxlength="1000" placeholder="Message the rider" autocomplete="off">
                    <button class="btn btn-sm btn-primary">Send</button>
                </form>
                <div id="dcNote" class="small text-muted"></div>
            </x-cb.card>
            @include('provider._job-returns')
            @if($assignable)
            <x-cb.card :title="$driver ? 'Change the driver' : 'Who will do this job?'" icon="ri-user-received-line" class="mb-3">
                @if($driver)<p class="mb-2">Now with <strong>{{ $driver->name }}</strong> ({{ $driver->status }}).</p>@endif
                @if(count($drivers))
                <form method="POST" action="{{ route('provider.job.assign', $s->public_id) }}">@csrf
                    <select name="driver_user_id" class="form-select mb-2" required>
                        <option value="">Choose a driver</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d['user_id'] }}" @disabled($driver && $driver->user_id == $d['user_id'])>{{ $d['name'] }} · {{ $d['active_jobs'] }}/{{ $d['max_active_jobs'] }} jobs · {{ str_replace('_', ' ', $d['availability']) }}</option>
                        @endforeach
                    </select>
                    @if($driver)<input name="reason" class="form-control mb-2" placeholder="Why the change? (optional)" maxlength="120">@endif
                    <button class="btn btn-primary w-100">{{ $driver ? 'Reassign' : 'Assign' }}</button>
                    <div class="form-text">The driver is told straight away. Any automatic offers for this job stop.</div>
                </form>
                @else
                    <p class="text-muted mb-0">No approved drivers yet. @if($op->type === 'company')Approve a driver on the Team page.@else Your own account must be approved before you can take jobs.@endif</p>
                @endif
            </x-cb.card>
            @endif

            @if($cancellable)
            <x-cb.card title="Can't do this job?" icon="ri-close-circle-line">
                <p class="small text-muted">You can back out until you have collected the goods. The customer is refunded in full and this counts against your cancellation record.</p>
                <form method="POST" action="{{ route('provider.job.cancel', $s->public_id) }}" onsubmit="return confirm('Cancel this job? The customer will be refunded.')">@csrf
                    <select name="reason" class="form-select mb-2" required>
                        <option value="">Choose a reason</option>
                        @foreach(['vehicle_problem' => 'Vehicle problem', 'unavailable' => 'No longer available', 'cannot_reach' => 'Cannot reach the address', 'other' => 'Other'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                    <button class="btn btn-outline-danger w-100">Cancel job</button>
                </form>
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
<script>
(function () {
    var URL = @json(route('provider.job.chat', $s->public_id)), SEND = @json(route('provider.job.chat.send', $s->public_id)), TOKEN = @json(csrf_token());
    var box = document.getElementById('dcBox'), form = document.getElementById('dcForm'), text = document.getElementById('dcText'), quick = document.getElementById('dcQuick'), note = document.getElementById('dcNote');
    var last = 0, shown = {};
    function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }
    function add(m) {
        if (shown[m.id]) return;
        shown[m.id] = true; last = Math.max(last, m.id);
        if (box.dataset.empty !== '0') { box.innerHTML = ''; box.dataset.empty = '0'; }
        var at = m.at ? new Date(m.at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : '';
        box.insertAdjacentHTML('beforeend', '<div class="d-flex ' + (m.mine ? 'justify-content-end' : '') + ' mb-1"><div class="px-2 py-1 rounded ' + (m.mine ? 'bg-primary text-white' : 'bg-light') + '" style="max-width:85%">' +
            '<div class="small">' + esc(m.body) + '</div><div class="small opacity-75">' + (m.sender && !m.mine ? esc(m.sender) + ' · ' : '') + esc(at) + '</div></div></div>');
        box.scrollTop = box.scrollHeight;
    }
    function load() {
        fetch(URL + (last ? '?after=' + last : ''), {headers: {'Accept': 'application/json'}, cache: 'no-store'})
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (t) {
                if (!t) return;
                if (!t.messages.length && !last) { box.dataset.empty = '1'; box.innerHTML = '<div class="small text-muted">No messages yet. ' + (t.open ? 'The rider sees what you send here.' : '') + '</div>'; }
                t.messages.forEach(add);
                form.hidden = !t.open;
                note.textContent = t.closed_note || '';
                quick.innerHTML = (t.quick || []).map(function (q) { return '<button type="button" class="btn btn-sm btn-outline-secondary" data-q="' + esc(q) + '">' + esc(q) + '</button>'; }).join('');
            });
    }
    function send(body) {
        body = body.trim();
        if (!body) return;
        fetch(SEND, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest'}, body: JSON.stringify({body: body})})
            .then(function (r) { return r.json().then(function (j) { return {ok: r.ok, j: j}; }); })
            .then(function (res) { if (!res.ok) { note.textContent = res.j.message || 'Not sent.'; } load(); });
    }
    form.addEventListener('submit', function (e) { e.preventDefault(); var v = text.value; text.value = ''; send(v); });
    quick.addEventListener('click', function (e) { var q = e.target.getAttribute('data-q'); if (q) send(q); });
    load();
    setInterval(function () { if (!document.hidden) load(); }, 6000);
})();
</script>
@endsection
