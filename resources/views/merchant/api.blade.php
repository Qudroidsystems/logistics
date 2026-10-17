@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="API and webhooks" icon="ri-key-2-line" subtitle="Connect your store to deliveries." :back="route('merchant.home')" back-label="Overview" />
    @include('merchant._flash')

    @if($newKey)
    <div class="cb-banner warning"><i class="ri-key-2-line"></i><div><strong>New API key. Copy it now, it will not be shown again.</strong><div class="mt-1"><input class="form-control" readonly onclick="this.select()" value="{{ $newKey }}"></div></div></div>
    @endif
    @if($newSecret)
    <div class="cb-banner warning"><i class="ri-shield-keyhole-line"></i><div><strong>Webhook signing secret. Copy it now, it will not be shown again.</strong><div class="mt-1"><input class="form-control" readonly onclick="this.select()" value="{{ $newSecret }}"></div></div></div>
    @endif
    @unless($canEdit)<div class="cb-banner info"><i class="ri-information-line"></i><div>You can look at this page, but only your account owner or an admin can change it.</div></div>@endunless

    <x-cb.card title="Go live" icon="ri-rocket-line" class="mb-3">
        @if($live['stage'] === 'approved')
            <p class="small mb-2">Your request was approved{{ !empty($live['request']->decision_note) ? ': '.$live['request']->decision_note : '.' }} Create your live key now. It is shown once.</p>
            @if($canEdit)
            <form method="POST" action="{{ route('merchant.live.key') }}" class="row g-2">@csrf
                <div class="col-md-8"><input name="name" class="form-control" placeholder="Key name, e.g. Production server" required></div>
                <div class="col-md-4"><button class="btn btn-success w-100">Create live key</button></div>
            </form>
            @endif
        @elseif($live['stage'] === 'pending')
            <p class="small mb-0"><span class="badge bg-warning text-dark">Waiting</span> We are reviewing your request. You will be told here and by email when it is decided.</p>
        @elseif($live['stage'] === 'blocked')
            <p class="small text-muted mb-0">{{ $live['message'] }}</p>
        @else
            @if(!empty($live['request']))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>Your last request was declined: {{ $live['request']->decision_note }}</div></div>@endif
            @if(!empty($live['has_live']))<p class="small text-muted">You already have a live key. Another one is for a second server or a replacement.</p>@endif
            @if($canEdit)
            <form method="POST" action="{{ route('merchant.live.request') }}">@csrf
                <label class="form-label small">Tell us about your launch (what you sell, roughly how many deliveries a day)</label>
                <textarea name="note" rows="3" class="form-control mb-2" maxlength="600" required minlength="10">{{ old('note') }}</textarea>
                <button class="btn btn-primary">Request a live key</button>
            </form>
            @else<p class="small text-muted mb-0">Your account owner or an admin can ask for a live key.</p>@endif
        @endif
    </x-cb.card>

    <x-cb.card title="API keys" icon="ri-key-2-line" :count="count($keys)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Key</th><th>Last used</th><th></th></tr></thead>
            <tbody>
            @forelse($keys as $k)
                <tr>
                    <td>{{ $k->name }}<div><span class="badge {{ $k->environment === 'live' ? 'bg-success' : 'bg-secondary' }}">{{ $k->environment === 'live' ? 'live' : 'test' }}</span>@if($k->revoked_at) <span class="badge bg-danger">revoked</span>@endif</div></td>
                    <td><code class="small">{{ $k->key_prefix }}…</code></td>
                    <td class="small text-muted">{{ $k->last_used_at ? \Illuminate\Support\Carbon::parse($k->last_used_at)->diffForHumans() : 'never' }}</td>
                    <td class="text-end">
                        @if($canEdit && ! $k->revoked_at)
                        <form method="POST" action="{{ route('merchant.key.revoke', $k->id) }}" onsubmit="return confirm('Revoke this key? Anything using it stops working.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Revoke</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No keys yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($canEdit)
        <div class="p-3 border-top">
            <form method="POST" action="{{ route('merchant.key.issue') }}" class="row g-2">@csrf
                <div class="col-md-8"><input name="name" class="form-control" placeholder="Key name, e.g. Checkout server (test)" required></div>
                <div class="col-md-4"><button class="btn btn-primary w-100">Create test key</button></div>
            </form>
            <div class="small text-muted mt-2">Test keys never move real money. Ask us for a live key once your integration works.</div>
        </div>
        @endif
    </x-cb.card>

    <x-cb.card title="Delivery updates (webhooks)" icon="ri-webhook-line" :count="count($hooks)" :flush="true" class="mt-3">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Address</th><th>Mode</th><th>Sends</th><th></th></tr></thead>
            <tbody>
            @forelse($hooks as $h)
                @php $ev = json_decode($h->events, true) ?: []; @endphp
                <tr>
                    <td class="small text-break">{{ $h->url }}</td>
                    <td><span class="badge {{ $h->is_test ? 'bg-secondary' : 'bg-success' }}">{{ $h->is_test ? 'test' : 'live' }}</span> @unless($h->active)<span class="badge bg-danger">off</span>@endunless</td>
                    <td class="small text-muted">{{ count($ev) ? count($ev).' kinds' : 'everything' }}</td>
                    <td class="text-end text-nowrap">
                        @if($canEdit)
                        <form method="POST" action="{{ route('merchant.hook.test', $h->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary" @disabled(! $h->active)>Send test</button></form>
                        <form method="POST" action="{{ route('merchant.hook.toggle', $h->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $h->active ? 'Switch off' : 'Switch on' }}</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No webhook address yet. You will not hear about delivery progress until you add one.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($canEdit)
        <div class="p-3 border-top">
            <form method="POST" action="{{ route('merchant.hook.add') }}" class="row g-2">@csrf
                <div class="col-md-8"><input name="url" class="form-control" placeholder="https://your-site.example/webhooks/delivery" value="{{ old('url') }}" required></div>
                <div class="col-md-4"><select name="environment" class="form-select"><option value="sandbox">Test</option><option value="live">Live</option></select></div>
                <div class="col-12">
                    <details><summary class="small text-muted">Only send some updates (default: everything)</summary>
                    <div class="mt-2">@foreach($events as $e)<label class="me-3 small"><input type="checkbox" name="events[]" value="{{ $e }}"> {{ $e }}</label>@endforeach</div></details>
                </div>
                <div class="col-12"><button class="btn btn-primary">Add webhook</button></div>
            </form>
        </div>
        @endif
    </x-cb.card>

    <x-cb.card title="Recent webhook deliveries" icon="ri-history-line" :count="count($log)" :flush="true" class="mt-3">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Event</th><th>Address</th><th>Result</th><th>When</th></tr></thead>
            <tbody>
            @forelse($log as $l)
                <tr>
                    <td class="small">{{ $l->event }}</td>
                    <td class="small text-break">{{ $l->url }}</td>
                    <td>
                        @if($l->delivered_at)<span class="badge bg-success">delivered</span>
                        @elseif($l->next_retry_at)<span class="badge bg-warning text-dark">retrying</span> <span class="small text-muted">{{ $l->response_status ?? 'no answer' }}</span>
                        @else<span class="badge bg-danger">failed</span> <span class="small text-muted">{{ $l->response_status ?? 'no answer' }}</span>@endif
                    </td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($l->created_at)->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">Nothing sent yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    <x-cb.card title="Connect your shop's checkout" icon="ri-code-s-slash-line" class="mt-3">
        @php $base = url('/api/v1/partner'); @endphp
        <p class="small text-muted mb-2">Every call sends your key as <code>Authorization: Bearer lgk_…</code>. Amounts are in <b>kobo</b>. Test keys never create real jobs.</p>
        <ol class="small mb-3">
            <li><b>Check the connection</b> — <code>GET {{ $base }}/me</code> returns your merchant code, status and the marketplace name to show your customers.</li>
            <li><b>Show delivery choices at checkout</b> — <code>POST {{ $base }}/quotes/options</code> with <code>pickup</code> and <code>dropoff</code> (<code>line1</code>, <code>lat</code>, <code>lng</code>, contact). The city and service are worked out for you; you get every provider and speed with its price, ETA, rating and an <code>option_id</code> (valid {{ \App\Modules\Pricing\QuoteService::QUOTE_TTL_MINUTES }} minutes).</li>
            <li><b>Book the one your customer chose</b> — <code>POST {{ $base }}/deliveries</code> with your <code>external_order_id</code>, the same addresses, the <code>option_id</code>, and <code>fee_payer</code>: <code>merchant</code> (paid through your account) or <code>receiver</code> (the rider collects the fee in cash at the door). Booking later? Send <code>max_price</code>: an expired option is priced again with the same provider and refused if it went above that.</li>
            <li><b>Follow it</b> — add a webhook above (signed with <code>X-Logistics-Signature: t=…,v1=hmac_sha256(secret, "t.body")</code>), or poll <code>GET {{ $base }}/deliveries/{external_order_id}</code>.</li>
        </ol>
<pre class="small bg-light p-2 rounded mb-0" style="white-space: pre-wrap;">curl -X POST {{ $base }}/quotes/options \
  -H "Authorization: Bearer $KEY" -H "Content-Type: application/json" \
  -d '{"pickup":{"line1":"12 Allen Ave, Ikeja","lat":6.6018,"lng":3.3515},
       "dropoff":{"line1":"3 Admiralty Way, Lekki","lat":6.4474,"lng":3.4728,"contact_name":"Ada","contact_phone":"0803..."},
       "weight_g":2500}'

→ {"marketplace":{"name":"…"},"options":[{"option_id":"01J…","price":250000,
     "provider":{"name":"Swift Haulage","rating":4.8},"service":{"name":"Same-day delivery"},
     "eta":{"label":"1 h – 3 h"},"tags":["cheapest"]}, …]}</pre>
        <p class="small text-muted mt-2 mb-0">Also: <code>GET {{ $base }}/catalog</code> (service types, cities, vehicle types), <code>POST /deliveries/{id}/cancel</code>, <code>POST /deliveries/{id}/return</code>, <code>POST /deliveries/batch</code>.</p>
    </x-cb.card>
</div></div></div>
@endsection
