@extends('layouts.master')

@section('content')
@php $naira = fn ($k) => $k === null ? '—' : Money::symbol().number_format(((int) $k) / 100, ((int) $k) % 100 ? 2 : 0); $market = \App\Support\Platform::marketplace(); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Promo codes and discounts" icon="ri-coupon-3-line" subtitle="Codes customers type before paying, standing discounts for business customers, and invites. The platform pays for these; a provider's own codes come out of that provider's share." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')

    <x-cb.card title="Promo codes" icon="ri-coupon-3-line" :count="count($promos)" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Code</th><th>Discount</th><th>Limits</th>@if($market)<th>Provider</th>@endif<th>Valid</th><th class="text-end">Used</th><th></th></tr></thead>
            <tbody>
            @forelse($promos as $p)
                <tr class="{{ $p->active ? '' : 'text-muted' }}">
                    <td class="fw-semibold">{{ $p->code }} @unless($p->active)<span class="badge bg-light text-dark">paused</span>@endunless</td>
                    <td class="small">{{ $types[$p->type] ?? $p->type }}: {{ $p->type === 'fixed' ? $naira($p->value) : ($p->type === 'free_delivery' ? 'whole delivery price' : rtrim(rtrim(number_format($p->value / 100, 2), '0'), '.').'%') }}@if($p->cap !== null) (max {{ $naira($p->cap) }})@endif</td>
                    <td class="small">{{ $p->per_user_limit }} per customer{{ $p->usage_limit ? ', '.$p->usage_limit.' in total' : '' }}{{ $p->min_order !== null ? ', min '.$naira($p->min_order) : '' }}{{ $p->budget !== null ? ', budget '.$naira($p->budget).' ('.$naira($p->budget_spent).' spent)' : '' }}</td>
                    @if($market)<td class="small">{{ $p->provider ?: 'Any' }}@if($p->funded_by === 'operator')<div class="text-muted">their own code</div>@endif</td>@endif
                    <td class="small">{{ $p->valid_from ? \Illuminate\Support\Carbon::parse($p->valid_from)->format('d M') : 'now' }} to {{ $p->valid_to ? \Illuminate\Support\Carbon::parse($p->valid_to)->format('d M Y') : 'no end' }}</td>
                    <td class="text-end">{{ $p->used }}</td>
                    <td class="text-end">@can('Update promo code')<form method="POST" action="{{ route('ops.promo.toggle', $p->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $p->active ? 'Pause' : 'Switch on' }}</button></form>@endcan</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No promo codes yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>

    @can('Create promo code')
    <div class="row g-3 mt-1">
    <div class="col-lg-6">
    <x-cb.card title="New promo code" icon="ri-add-circle-line">
        <form method="POST" action="{{ route('ops.promo.store') }}" class="row g-2">@csrf
            <div class="col-md-6"><label class="form-label small">Code</label><input name="code" class="form-control text-uppercase" maxlength="32" required value="{{ old('code') }}" placeholder="WELCOME10"></div>
            <div class="col-md-6"><label class="form-label small">Kind</label><select name="type" class="form-select">@foreach($types as $k => $v)<option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label small">Percent or {{ Money::symbol() }} amount</label><input name="value" type="number" step="0.01" min="0" class="form-control" value="{{ old('value') }}"></div>
            <div class="col-md-4"><label class="form-label small">Most off one job ({{ Money::symbol() }})</label><input name="cap_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('cap_naira') }}"></div>
            <div class="col-md-4"><label class="form-label small">Minimum price ({{ Money::symbol() }})</label><input name="min_order_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('min_order_naira') }}"></div>
            <div class="col-md-4"><label class="form-label small">Uses per customer</label><input name="per_user_limit" type="number" min="1" class="form-control" value="{{ old('per_user_limit', 1) }}"></div>
            <div class="col-md-4"><label class="form-label small">Total uses</label><input name="usage_limit" type="number" min="1" class="form-control" value="{{ old('usage_limit') }}"></div>
            <div class="col-md-4"><label class="form-label small">Budget ({{ Money::symbol() }})</label><input name="budget_naira" type="number" step="0.01" min="0" class="form-control" value="{{ old('budget_naira') }}"></div>
            <div class="col-md-6"><label class="form-label small">Starts</label><input name="valid_from" type="datetime-local" class="form-control" value="{{ old('valid_from') }}"></div>
            <div class="col-md-6"><label class="form-label small">Ends</label><input name="valid_to" type="datetime-local" class="form-control" value="{{ old('valid_to') }}"></div>
            @if($market)<div class="col-12"><label class="form-label small">Only with this provider (optional)</label><select name="operator_id" class="form-select"><option value="">Any provider</option>@foreach($operators as $o)<option value="{{ $o->id }}">{{ $o->display_name }}</option>@endforeach</select></div>@endif
            <div class="col-12"><button class="btn btn-primary">Create code</button></div>
        </form>
    </x-cb.card>
    </div>
    <div class="col-lg-6">
    <x-cb.card title="Corporate discounts" icon="ri-building-4-line" :count="count($corporate)">
        <table class="table table-sm align-middle">
            @forelse($corporate as $c)
                <tr class="{{ $c->active ? '' : 'text-muted' }}"><td><div class="fw-semibold">{{ $c->name }}</div><div class="small">{{ $c->merchant ?: $c->user_email }}{{ $market && $c->provider ? ' · with '.$c->provider : '' }}</div></td>
                    <td class="text-end">{{ rtrim(rtrim(number_format($c->rate_bp / 100, 2), '0'), '.') }}%@if($c->cap !== null)<div class="small text-muted">max {{ $naira($c->cap) }}</div>@endif</td>
                    <td class="text-end">@can('Update promo code')<form method="POST" action="{{ route('ops.corporate.toggle', $c->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $c->active ? 'Pause' : 'Switch on' }}</button></form>@endcan</td></tr>
            @empty
                <tr><td class="text-muted">None yet. A corporate discount comes off every delivery for that business or customer, automatically.</td></tr>
            @endforelse
        </table>
        <form method="POST" action="{{ route('ops.corporate.store') }}" class="row g-2">@csrf
            <div class="col-md-8"><input name="name" class="form-control" placeholder="Name, e.g. Acme Ltd account" required value="{{ old('name') }}"></div>
            <div class="col-md-4"><input name="rate" type="number" step="0.01" min="0.01" max="100" class="form-control" placeholder="% off" required value="{{ old('rate') }}"></div>
            <div class="col-md-6"><select name="merchant_id" class="form-select"><option value="">Business account (API merchant)…</option>@foreach($merchants as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><input name="user_email" type="email" class="form-control" placeholder="or a customer's email" value="{{ old('user_email') }}"></div>
            <div class="col-md-6"><input name="cap_naira" type="number" step="0.01" min="0" class="form-control" placeholder="Most off one job ({{ Money::symbol() }})"></div>
            <div class="col-md-6"><input name="valid_to" type="date" class="form-control" title="Ends (optional)"></div>
            @if($market)<div class="col-12"><select name="operator_id" class="form-select"><option value="">With any provider</option>@foreach($operators as $o)<option value="{{ $o->id }}">{{ $o->display_name }}</option>@endforeach</select></div>@endif
            <div class="col-12"><button class="btn btn-outline-primary">Add corporate discount</button></div>
        </form>
    </x-cb.card>
    </div>
    </div>
    @endcan

    <div class="row g-3 mt-1">
    <div class="col-lg-5">
    <x-cb.card title="Invite a friend" icon="ri-user-add-line">
        <p class="small text-muted">Each customer has a code in the app. A friend who signs up with it gets money off their first delivery; the customer who invited them gets a wallet credit once that delivery is done.</p>
        <div class="small mb-2">{{ $referralTotals->invited }} invited · {{ $referralTotals->rewarded }} credited · {{ $naira($referralTotals->paid) }} paid out</div>
        @can('Update promo code')
        <form method="POST" action="{{ route('ops.referrals.save') }}" class="row g-2">@csrf
            <div class="col-12"><select name="enabled" class="form-select"><option value="1" @selected($referral['enabled'])>Invites are on</option><option value="0" @selected(! $referral['enabled'])>Invites are off</option></select></div>
            <div class="col-6"><label class="form-label small">Friend gets off their first delivery ({{ Money::symbol() }})</label><input name="friend_naira" type="number" step="0.01" min="0" class="form-control" value="{{ $referral['friend_discount'] / 100 }}"></div>
            <div class="col-6"><label class="form-label small">Inviter's wallet credit ({{ Money::symbol() }})</label><input name="reward_naira" type="number" step="0.01" min="0" class="form-control" value="{{ $referral['reward'] / 100 }}"></div>
            <div class="col-6"><label class="form-label small">First delivery must cost at least ({{ Money::symbol() }})</label><input name="min_price_naira" type="number" step="0.01" min="0" class="form-control" value="{{ $referral['min_price'] / 100 }}"></div>
            <div class="col-6"><label class="form-label small">Most credits per customer a month</label><input name="monthly_cap" type="number" min="0" class="form-control" value="{{ $referral['monthly_cap'] }}"></div>
            <div class="col-12"><button class="btn btn-outline-primary">Save</button></div>
        </form>
        @endcan
    </x-cb.card>
    </div>
    <div class="col-lg-7">
    <x-cb.card title="Recent invites" icon="ri-team-line" :flush="true">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Invited by</th><th>Friend</th><th>Status</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @forelse($referrals as $r)
                <tr><td class="small">{{ $r->referrer }}</td><td class="small">{{ $r->friend }}</td><td class="small">{{ $r->status === 'pending' ? 'waiting for first delivery' : ($r->status === 'void' ? 'no credit'.($r->note ? ': '.strtolower($r->note) : '') : 'credited') }}</td><td class="text-end small">{{ $r->reward ? $naira($r->reward) : '—' }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No invites yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-cb.card>
    </div>
    </div>

    <div class="row g-3 mt-1">
    <div class="col-lg-5">
    <x-cb.card title="Loyalty points" icon="ri-medal-line">
        <p class="small text-muted">Customers earn points on every finished delivery they pay for, and spend them at payment for money off the delivery price. The platform pays for points spent.</p>
        <div class="small mb-2">{{ number_format($loyaltyTotals->earned) }} earned · {{ number_format($loyaltyTotals->spent) }} spent · {{ number_format($loyaltyTotals->outstanding) }} unspent (worth {{ $naira($loyaltyTotals->outstanding * $loyalty['point_value']) }})</div>
        @can('Update promo code')
        <form method="POST" action="{{ route('ops.loyalty.save') }}" class="row g-2">@csrf
            <div class="col-12"><select name="enabled" class="form-select"><option value="1" @selected($loyalty['enabled'])>Points are on</option><option value="0" @selected(! $loyalty['enabled'])>Points are off</option></select></div>
            <div class="col-6"><label class="form-label small">Points for each {{ Money::symbol() }}100 paid</label><input name="earn_per_100" type="number" min="0" class="form-control" value="{{ $loyalty['earn_per_100'] }}"></div>
            <div class="col-6"><label class="form-label small">One point is worth ({{ Money::symbol() }})</label><input name="point_value_naira" type="number" step="0.01" min="0.01" class="form-control" value="{{ $loyalty['point_value'] / 100 }}"></div>
            <div class="col-6"><label class="form-label small">Points needed to spend any</label><input name="min_redeem" type="number" min="0" class="form-control" value="{{ $loyalty['min_redeem'] }}"></div>
            <div class="col-6"><label class="form-label small">Most of one delivery points pay (%)</label><input name="max_share_percent" type="number" min="1" max="100" class="form-control" value="{{ intdiv($loyalty['max_share_bp'], 100) }}"></div>
            <div class="col-12"><button class="btn btn-outline-primary">Save</button></div>
        </form>
        @endcan
    </x-cb.card>
    @can('Create promo code')
    <x-cb.card title="Give a gift card" icon="ri-gift-line">
        <form method="POST" action="{{ route('ops.giftcards.issue') }}" class="row g-2">@csrf
            <div class="col-6"><label class="form-label small">Amount ({{ Money::symbol() }})</label><input name="amount_naira" type="number" step="0.01" min="1" class="form-control" required value="{{ old('amount_naira') }}"></div>
            <div class="col-6"><label class="form-label small">Expires (optional)</label><input name="expires_at" type="date" class="form-control" value="{{ old('expires_at') }}"></div>
            <div class="col-12"><label class="form-label small">For</label><input name="recipient_name" class="form-control" maxlength="120" placeholder="Name" value="{{ old('recipient_name') }}"></div>
            <div class="col-6"><input name="recipient_phone" class="form-control" maxlength="24" placeholder="Phone, to text the code" value="{{ old('recipient_phone') }}"></div>
            <div class="col-6"><input name="recipient_email" type="email" class="form-control" maxlength="190" placeholder="Email, to send the code" value="{{ old('recipient_email') }}"></div>
            <div class="col-12"><input name="message" class="form-control" maxlength="300" placeholder="Message (optional)" value="{{ old('message') }}"></div>
            <div class="col-12"><button class="btn btn-primary">Create gift card</button> <span class="small text-muted">The platform pays for it.</span></div>
        </form>
    </x-cb.card>
    @endcan
    </div>
    <div class="col-lg-7">
    <x-cb.card title="Gift cards" icon="ri-gift-2-line" :flush="true">
        <div class="small text-muted px-3 pt-2">{{ $giftTotals->cards }} active · {{ $naira($giftTotals->unspent) }} unspent</div>
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Card</th><th>From</th><th>For</th><th class="text-end">Value</th><th class="text-end">Left</th><th></th></tr></thead>
            <tbody>
            @forelse($giftCards as $g)
                <tr class="{{ $g->status === 'active' ? '' : 'text-muted' }}">
                    <td class="small">…{{ $g->last4 }} @if($g->status !== 'active')<span class="badge bg-light text-dark">stopped</span>@endif @if($g->expires_at)<div class="text-muted">until {{ \Illuminate\Support\Carbon::parse($g->expires_at)->format('d M Y') }}</div>@endif</td>
                    <td class="small">{{ $g->source === 'issued' ? 'Staff' : ($g->buyer ?? 'Customer') }}</td>
                    <td class="small">{{ $g->recipient_name ?? '—' }}</td>
                    <td class="text-end small">{{ $naira($g->initial_value) }}</td><td class="text-end small">{{ $naira($g->balance) }}</td>
                    <td class="text-end">@can('Update promo code')<form method="POST" action="{{ route('ops.giftcards.toggle', $g->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $g->status === 'active' ? 'Stop' : 'Allow' }}</button></form>@endcan</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No gift cards yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-cb.card>
    </div>
    </div>
</div></div></div>
@endsection
