@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => '₦'.number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$m->display_name" icon="ri-store-3-line" :subtitle="'Merchant code '.$m->merchant_code" :back="route('ops.merchants')" back-label="Merchants">
        <x-slot:pills><span class="cb-meta-pill">{{ $m->status }}</span></x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    @if($newKey)
    <div class="cb-banner warning"><i class="ri-key-2-line"></i><div><strong>New API key. Copy it now, it will not be shown again.</strong><div class="mt-1"><input class="form-control" readonly onclick="this.select()" value="{{ $newKey }}"></div></div></div>
    @endif
    @if($newSecret)
    <div class="cb-banner warning"><i class="ri-shield-keyhole-line"></i><div><strong>Webhook signing secret. Copy it now, it will not be shown again.</strong><div class="mt-1"><input class="form-control" readonly onclick="this.select()" value="{{ $newSecret }}"></div></div></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <x-cb.card title="Details" icon="ri-information-line">
                <form method="POST" action="{{ route('ops.merchant.update', $m->id) }}">@csrf @method('PUT')
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select mb-2">@foreach($statuses as $s)<option value="{{ $s }}" @selected($m->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                    <label class="form-label small">How they pay for deliveries</label>
                    <select name="settlement_mode" class="form-select mb-2">@foreach($settlement as $k => $v)<option value="{{ $k }}" @selected($m->settlement_mode === $k)>{{ $v }}</option>@endforeach</select>
                    <label class="form-label small">Support email</label><input name="support_email" class="form-control mb-2" value="{{ old('support_email', $m->support_email) }}">
                    <label class="form-label small">Support phone</label><input name="support_phone" class="form-control mb-3" value="{{ old('support_phone', $m->support_phone) }}">
                    @can('Update vendor')<button class="btn btn-primary w-100">Save</button>@endcan
                </form>
                @if($m->website)<div class="small text-muted mt-2">{{ $m->website }}</div>@endif
            </x-cb.card>

            <x-cb.card title="Account owner" icon="ri-user-star-line" class="mb-3">
                @if($owner)<div class="small mb-2"><strong>{{ $owner->name }}</strong><div class="text-muted">{{ $owner->email }}</div></div>@else<div class="small text-warning mb-2">No owner yet. Deliveries cannot be booked until someone owns this merchant.</div>@endif
                @can('Update vendor')
                <form method="POST" action="{{ route('ops.merchant.owner', $m->id) }}" class="d-flex gap-2">@csrf
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="Email of an existing account" value="{{ old('email') }}" required>
                    <button class="btn btn-sm btn-primary">Set owner</button>
                </form>
                <div class="small text-muted mt-1">They sign in to the merchant page, and deliveries and prepaid-wallet payments run under their account.</div>
                @endcan
            </x-cb.card>

            <x-cb.card title="Recent orders" icon="ri-shopping-bag-3-line" :flush="true">
                <table class="table mb-0"><tbody>
                @forelse($recent as $o)
                    <tr><td class="small"><strong>{{ $o->order_number }}</strong><div class="text-muted">{{ $o->external_order_id }}</div></td><td class="text-end small">{{ $naira($o->total) }}<div class="text-muted">{{ $o->payment_status }}</div></td></tr>
                @empty
                    <tr><td class="text-center text-muted py-3">No orders yet.</td></tr>
                @endforelse
                </tbody></table>
            </x-cb.card>
        </div>

        <div class="col-lg-7">
            @if($liveRequest)
            <x-cb.card title="Live key request" icon="ri-rocket-line" class="mb-3">
                <div class="small mb-2"><span class="badge bg-{{ $liveRequest->status === 'pending' ? 'warning text-dark' : ($liveRequest->status === 'declined' ? 'danger' : 'success') }}">{{ $liveRequest->status }}</span>
                    <span class="text-muted">from {{ $liveRequest->by }}, {{ \Illuminate\Support\Carbon::parse($liveRequest->created_at)->diffForHumans() }}</span></div>
                <div class="small mb-2" style="white-space:pre-line">{{ $liveRequest->note }}</div>
                @if($liveRequest->decision_note)<div class="small text-muted mb-2">Reply: {{ $liveRequest->decision_note }}</div>@endif
                @if($liveRequest->status === 'pending')@can('Update vendor')
                <form method="POST" action="{{ route('ops.merchant.live.decide', [$m->id, $liveRequest->id]) }}">@csrf
                    <input name="note" class="form-control form-control-sm mb-2" maxlength="300" placeholder="Note to the merchant (needed to decline)">
                    <button name="decision" value="approve" class="btn btn-sm btn-success">Approve</button>
                    <button name="decision" value="decline" class="btn btn-sm btn-outline-danger">Decline</button>
                </form>
                @endcan @endif
            </x-cb.card>
            @endif

            <x-cb.card title="API keys" icon="ri-key-2-line" :count="count($keys)" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Name</th><th>Key</th><th>Used</th><th></th></tr></thead>
                    <tbody>
                    @forelse($keys as $k)
                        <tr>
                            <td>{{ $k->name }}<div><span class="badge {{ $k->environment === 'live' ? 'bg-success' : 'bg-secondary' }}">{{ $k->environment }}</span>@if($k->revoked_at) <span class="badge bg-danger">revoked</span>@endif</div></td>
                            <td><code class="small">{{ $k->key_prefix }}…</code></td>
                            <td class="small text-muted">{{ $k->last_used_at ? \Illuminate\Support\Carbon::parse($k->last_used_at)->diffForHumans() : 'never' }}</td>
                            <td class="text-end">
                                @if(!$k->revoked_at)@can('Update vendor')
                                <form method="POST" action="{{ route('ops.merchant.key.revoke', [$m->id, $k->id]) }}" onsubmit="return confirm('Revoke this key? Anything using it stops working.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Revoke</button></form>
                                @endcan @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No keys yet.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                @can('Update vendor')
                <div class="p-3 border-top">
                    <form method="POST" action="{{ route('ops.merchant.key.issue', $m->id) }}" class="row g-2">@csrf
                        <div class="col-md-5"><input name="name" class="form-control" placeholder="Key name, e.g. Checkout server" required></div>
                        <div class="col-md-4"><select name="environment" class="form-select"><option value="sandbox">Sandbox (test)</option><option value="live" @disabled($m->status !== 'verified')>Live</option></select></div>
                        <div class="col-md-3"><button class="btn btn-primary w-100">Create key</button></div>
                    </form>
                </div>
                @endcan
            </x-cb.card>

            <x-cb.card title="Delivery updates (webhooks)" icon="ri-webhook-line" :count="count($hooks)" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Address</th><th>Mode</th><th>Sends</th><th></th></tr></thead>
                    <tbody>
                    @forelse($hooks as $h)
                        @php $ev = json_decode($h->events, true) ?: []; @endphp
                        <tr>
                            <td class="small text-break">{{ $h->url }}</td>
                            <td><span class="badge {{ $h->is_test ? 'bg-secondary' : 'bg-success' }}">{{ $h->is_test ? 'sandbox' : 'live' }}</span> @unless($h->active)<span class="badge bg-danger">off</span>@endunless</td>
                            <td class="small text-muted">{{ count($ev) ? count($ev).' kinds' : 'everything' }}</td>
                            <td class="text-end">@can('Update vendor')<form method="POST" action="{{ route('ops.merchant.hook.toggle', [$m->id, $h->id]) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $h->active ? 'Switch off' : 'Switch on' }}</button></form>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No webhook address yet. The merchant will not hear about delivery progress until one is added.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                @can('Update vendor')
                <div class="p-3 border-top">
                    <form method="POST" action="{{ route('ops.merchant.hook.add', $m->id) }}" class="row g-2">@csrf
                        <div class="col-md-8"><input name="url" class="form-control" placeholder="https://their-site.example/webhooks/delivery" value="{{ old('url') }}" required></div>
                        <div class="col-md-4"><select name="environment" class="form-select"><option value="sandbox">Sandbox</option><option value="live">Live</option></select></div>
                        <div class="col-12">
                            <details><summary class="small text-muted">Only send some updates (default: everything)</summary>
                            <div class="mt-2">@foreach($events as $e)<label class="me-3 small"><input type="checkbox" name="events[]" value="{{ $e }}"> {{ $e }}</label>@endforeach</div></details>
                        </div>
                        <div class="col-12"><button class="btn btn-primary">Add webhook</button></div>
                    </form>
                </div>
                @endcan
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
