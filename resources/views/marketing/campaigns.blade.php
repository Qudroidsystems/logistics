@extends('layouts.master')

@section('content')
@php
    $statusLabel = ['draft' => 'Draft', 'scheduled' => 'Scheduled', 'sending' => 'Sending', 'sent' => 'Sent', 'cancelled' => 'Stopped'];
    $statusColour = ['draft' => 'secondary', 'scheduled' => 'info', 'sending' => 'warning', 'sent' => 'success', 'cancelled' => 'light'];
    $e = $edit;
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Campaigns" icon="ri-megaphone-line" :subtitle="'Email and SMS offers to '.$scope.'. Each message says how to stop them.'" />
    @include('provider._flash')

    <div class="row g-3">
        <div class="col-lg-7">
            <x-cb.card title="Your campaigns" icon="ri-megaphone-line" :count="count($campaigns)" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Campaign</th><th>To</th><th class="text-end">Sent</th><th></th></tr></thead>
                    <tbody>
                    @forelse($campaigns as $c)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $c->name }} <span class="badge bg-{{ $statusColour[$c->status] }}-subtle text-{{ $statusColour[$c->status] === 'light' ? 'muted' : $statusColour[$c->status] }}">{{ $statusLabel[$c->status] }}</span></div>
                                <div class="small text-muted">{{ strtoupper($c->channel) }}{{ $c->promo_code ? ' · code '.$c->promo_code : '' }}@if($c->status === 'scheduled') · {{ \Illuminate\Support\Carbon::parse($c->scheduled_at)->format('d M H:i') }}@endif</div>
                            </td>
                            <td class="small">{{ $audiences[$c->audience] ?? $c->audience }}</td>
                            <td class="text-end small">@if($c->recipients){{ $c->sent }} of {{ $c->recipients }}@if($c->failed)<div class="text-danger">{{ $c->failed }} failed</div>@endif @else — @endif</td>
                            <td class="text-end text-nowrap">
                                @if($c->status === 'draft')
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route($prefix, ['edit' => $c->id]) }}">Edit</a>
                                    <form method="POST" action="{{ route($prefix.'.action', [$c->id, 'test']) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Test</button></form>
                                    <form method="POST" action="{{ route($prefix.'.action', [$c->id, 'send']) }}" class="d-inline" onsubmit="return confirm('Send {{ addslashes($c->name) }} to {{ $counts[$c->audience][$c->channel] }} people?')">@csrf<button class="btn btn-sm btn-primary">Send</button></form>
                                @endif
                                @if(in_array($c->status, ['draft', 'scheduled', 'sending'], true))
                                    <form method="POST" action="{{ route($prefix.'.action', [$c->id, 'cancel']) }}" class="d-inline">@csrf<button class="btn btn-sm btn-link text-danger">Stop</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No campaigns yet.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </x-cb.card>

            <x-cb.card title="Who you can reach" icon="ri-group-line" class="mt-3" :flush="true">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Audience</th><th class="text-end">By email</th><th class="text-end">By SMS</th></tr></thead>
                    <tbody>@foreach($audiences as $k => $label)<tr><td class="small">{{ $label }}</td><td class="text-end">{{ number_format($counts[$k]['email']) }}</td><td class="text-end">{{ number_format($counts[$k]['sms']) }}</td></tr>@endforeach</tbody>
                </table>
                <div class="small text-muted p-2">Only customers who said yes to offers are counted. They can say yes in the app's notification settings.</div>
            </x-cb.card>
        </div>

        <div class="col-lg-5">
            <x-cb.card :title="$e ? 'Edit campaign' : 'New campaign'" icon="ri-edit-line">
                <form method="POST" action="{{ $e ? route($prefix.'.update', $e->id) : route($prefix.'.store') }}" class="row g-2">@csrf
                    <div class="col-12"><label class="form-label small">Name (only you see this)</label><input name="name" class="form-control" maxlength="120" required value="{{ old('name', $e->name ?? '') }}" placeholder="December offer"></div>
                    <div class="col-6"><label class="form-label small">Send by</label>
                        <select name="channel" class="form-select" id="campChannel">@foreach(['email' => 'Email', 'sms' => 'SMS'] as $k => $v)<option value="{{ $k }}" @selected(old('channel', $e->channel ?? 'email') === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label small">To</label>
                        <select name="audience" class="form-select">@foreach($audiences as $k => $v)<option value="{{ $k }}" @selected(old('audience', $e->audience ?? 'all') === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-12" id="campSubject"><label class="form-label small">Email subject</label><input name="subject" class="form-control" maxlength="150" value="{{ old('subject', $e->subject ?? '') }}"></div>
                    <div class="col-12"><label class="form-label small">Message</label><textarea name="body" class="form-control" rows="6" required id="campBody">{{ old('body', $e->body ?? '') }}</textarea>
                        <div class="form-text">Write {first_name} for the customer's first name and {code} for the promo code. <span id="campPages"></span></div></div>
                    <div class="col-12"><label class="form-label small">Promo code to include (optional)</label><input name="promo_code" class="form-control text-uppercase" maxlength="32" value="{{ old('promo_code', $e->promo_code ?? '') }}"></div>
                    <div class="col-12"><button class="btn btn-primary">{{ $e ? 'Save changes' : 'Save draft' }}</button> @if($e)<a href="{{ route($prefix) }}" class="btn btn-link">Cancel</a>@endif</div>
                </form>
            </x-cb.card>

            @if($reviews !== null)
            <x-cb.card title="Google reviews" icon="ri-google-line" class="mt-3">
                <p class="small text-muted">After each finished delivery, the customer gets a message asking for a Google review. Everyone is asked, at most once every 90 days, as Google requires.</p>
                <div class="small mb-2">{{ $reviewStats->sent }} asked · {{ $reviewStats->waiting }} waiting</div>
                <form method="POST" action="{{ route('provider.reviews.save') }}" class="row g-2">@csrf
                    <div class="col-12"><select name="enabled" class="form-select"><option value="1" @selected($reviews['enabled'])>Ask for reviews</option><option value="0" @selected(! $reviews['enabled'])>Don't ask</option></select></div>
                    <div class="col-12"><label class="form-label small">Your Google review link</label><input name="url" type="url" class="form-control" maxlength="300" value="{{ old('url', $reviews['url']) }}" placeholder="https://g.page/r/…/review"></div>
                    <div class="col-6"><label class="form-label small">Hours after delivery</label><input name="delay_hours" type="number" min="0" max="168" class="form-control" value="{{ $reviews['delay_hours'] }}"></div>
                    <div class="col-6 d-flex align-items-end"><div class="form-check"><input type="hidden" name="sms" value="0"><input class="form-check-input" type="checkbox" name="sms" value="1" id="revSms" @checked($reviews['sms'])><label class="form-check-label small" for="revSms">Also send an SMS</label></div></div>
                    <div class="col-12"><button class="btn btn-outline-primary">Save</button></div>
                </form>
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
<script>
(function () {
    var ch = document.getElementById('campChannel'), subj = document.getElementById('campSubject'), body = document.getElementById('campBody'), pages = document.getElementById('campPages');
    function sync() {
        var sms = ch.value === 'sms';
        subj.style.display = sms ? 'none' : '';
        pages.textContent = sms ? body.value.length + ' characters, ' + Math.max(1, Math.ceil((body.value.length + 40) / 160)) + ' SMS page(s) each, with the stop note.' : '';
    }
    ch.addEventListener('change', sync); body.addEventListener('input', sync); sync();
})();
</script>
@endsection
