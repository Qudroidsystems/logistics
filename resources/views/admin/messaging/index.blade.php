@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Messaging" icon="ri-message-2-line" subtitle="Who sends your texts, WhatsApp messages and emails. Secrets are stored encrypted; leave a secret blank to keep the saved one.">
        <x-slot:pills>
            <span class="cb-meta-pill">Delivery texts: {{ $smsDriver }} (SMS_DRIVER)</span>
            <span class="cb-meta-pill">WhatsApp for delivery texts: {{ $whatsappMode }} (SMS_WHATSAPP)</span>
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    <div class="row g-3">
        @foreach($channels as $key => $c)
            @php $s = $c['setting']; @endphp
            <div class="col-xl-4">
                <x-cb.card :title="$labels[$key]" icon="{{ $key === 'whatsapp' ? 'ri-whatsapp-line' : ($key === 'email' ? 'ri-mail-line' : 'ri-chat-1-line') }}">
                    <div class="mb-2">
                        @if($c['problem'])<span class="badge bg-warning-subtle text-warning">Not ready</span><div class="small text-muted mt-1">{{ $c['problem'] }}</div>
                        @elseif($s->driver === 'log')<span class="badge bg-info-subtle text-info">Testing: messages are only logged</span>
                        @else<span class="badge bg-success-subtle text-success">Live</span>@endif
                    </div>
                    <form method="POST" action="{{ route('admin.messaging.save', $key) }}" class="row g-2">@csrf
                        <div class="col-12"><label class="form-label small mb-0">Provider</label>
                            <select name="driver" class="form-select form-select-sm" onchange="this.form.querySelectorAll('[data-driver]').forEach(function (b) { b.hidden = b.dataset.driver !== this.value; }, this)">
                                @foreach($c['drivers'] as $dk => $dv)<option value="{{ $dk }}" @selected($s->driver === $dk)>{{ $dv['label'] }}</option>@endforeach
                            </select>
                        </div>
                        @foreach($c['drivers'] as $dk => $dv)
                            <div class="col-12" data-driver="{{ $dk }}" @if($s->driver !== $dk) hidden @endif>
                                @foreach($dv['fields'] as $f => $def)
                                    <label class="form-label small mb-0 mt-1">{{ $def['label'] }}@if(!empty($def['required'])) <span class="text-danger">*</span>@endif</label>
                                    @if(!empty($def['options']))
                                        <select name="config[{{ $f }}]" class="form-select form-select-sm">@foreach($def['options'] as $ok => $ov)<option value="{{ $ok }}" @selected($s->driver === $dk && $s->value($f) === $ok)>{{ $ov }}</option>@endforeach</select>
                                    @elseif(!empty($def['secret']))
                                        <input type="password" name="config[{{ $f }}]" class="form-control form-control-sm" autocomplete="new-password" placeholder="{{ $s->driver === $dk && $s->value($f) ? $s->masked($f).' (saved)' : '' }}">
                                    @else
                                        <input name="config[{{ $f }}]" class="form-control form-control-sm" value="{{ $s->driver === $dk ? ($s->config[$f] ?? '') : '' }}" placeholder="{{ $def['default'] ?? '' }}" @if(!empty($def['max'])) maxlength="{{ $def['max'] }}" @endif>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                        <div class="col-12 form-check form-switch ms-2 mt-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="on-{{ $key }}" @checked($s->is_active)><label class="form-check-label small" for="on-{{ $key }}">On</label></div>
                        <div class="col-12"><button class="btn btn-sm btn-primary">Save</button></div>
                    </form>
                    <form method="POST" action="{{ route('admin.messaging.test', $key) }}" class="d-flex gap-2 mt-3 pt-3 border-top">@csrf
                        <input name="to" class="form-control form-control-sm" placeholder="{{ $key === 'email' ? 'you@example.com' : config('region.phone_example') }}" required>
                        <button class="btn btn-sm btn-outline-primary text-nowrap">Send test</button>
                    </form>
                    @if($key === 'whatsapp')<div class="form-text">Needs an approved template with exactly two body variables: the person's first name and the message.</div>@endif
                </x-cb.card>
            </div>
        @endforeach
    </div>
</div></div></div>
@endsection
