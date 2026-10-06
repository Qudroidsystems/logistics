{{-- resources/views/admin/feature-flags/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Module Access" icon="ri-toggle-line" subtitle="Turn portal modules on or off. A module's sidebar link shows only when it is ON and the user has the right privilege. These can be controlled from your remote portal.">
        <x-slot:actions>
            @if($cfg->remote_url)<form method="POST" action="{{ route('feature-flags.pull') }}" class="d-inline">@csrf<button class="cb-hero-btn"><i class="ri-download-cloud-2-line"></i>Pull now</button></form>@endif
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if(session('new_api_key'))
        <div class="cb-banner warning"><i class="ri-key-2-line"></i><div><strong>New API key (copy it now — it won't be shown in full again):</strong><br><code style="user-select:all">{{ session('new_api_key') }}</code></div></div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            @foreach($flags as $group => $items)
                <x-cb.card :title="$group ?: 'Other'" icon="ri-apps-2-line" :count="$items->count()" :flush="true">
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Module</th><th>Key</th><th class="text-center">State</th><th class="text-center">Controlled by</th></tr></thead>
                        <tbody>
                        @foreach($items as $f)
                            <tr>
                                <td>{{ $f->label }}<div class="small text-muted">{{ $f->description }}</div></td>
                                <td><code>{{ $f->key }}</code></td>
                                <td class="text-center">
                                    @if($f->remote_controlled)
                                        <span class="status-pill {{ $f->enabled ? 'st-paid' : 'st-danger' }}">{{ $f->enabled ? 'ON' : 'OFF' }}</span>
                                    @else
                                        <form method="POST" action="{{ route('feature-flags.toggle', $f) }}" class="d-inline">@csrf
                                            <input type="hidden" name="enabled" value="{{ $f->enabled ? 0 : 1 }}">
                                            <button class="action-btn {{ $f->enabled ? 'btn-go' : 'btn-open' }}">{{ $f->enabled ? 'ON' : 'OFF' }}</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('feature-flags.control', $f) }}" class="d-inline">@csrf
                                        <input type="hidden" name="remote_controlled" value="{{ $f->remote_controlled ? 0 : 1 }}">
                                        <button class="action-btn btn-open" title="Switch control">{{ $f->remote_controlled ? 'Remote' : 'Local' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </x-cb.card>
            @endforeach
            <div class="small text-muted">Flags are <strong>fail-open</strong>: if the remote portal can't be reached, modules stay at their last known state, and a module that has never been set counts as ON — so the portal is never accidentally emptied. A link still needs the user's permission as well.</div>
        </div>

        <div class="col-xl-4">
            <x-cb.card title="This portal's API (for the remote)" icon="ri-links-line">
                <p class="small text-muted">Give these to your control portal. The key authenticates it; write calls must also send an <code>X-Signature</code> HMAC of the body.</p>
                <div class="mb-2"><label class="form-label small mb-1">Read flags (GET)</label><input class="form-control form-control-sm" value="{{ $endpoints['read'] }}" readonly onclick="this.select()"></div>
                <div class="mb-2"><label class="form-label small mb-1">Set flags (POST)</label><input class="form-control form-control-sm" value="{{ $endpoints['write'] }}" readonly onclick="this.select()"></div>
                <div class="mb-2"><label class="form-label small mb-1">Health (GET → {"ok":1})</label><input class="form-control form-control-sm" value="{{ $endpoints['health'] }}" readonly onclick="this.select()"></div>
                <div class="mb-2"><label class="form-label small mb-1">API key</label>
                    <div class="input-group input-group-sm"><input class="form-control" value="{{ $cfg->maskedApiKey() ?? 'not set' }}" readonly>
                        <form method="POST" action="{{ route('feature-flags.regenerate-key') }}" onsubmit="return confirm('Generate a new key? The old one stops working immediately.')">@csrf<button class="btn btn-outline-secondary"><i class="ri-refresh-line"></i></button></form>
                    </div>
                </div>
                <div class="small text-muted">Example body: <code>{"flags":{"dashboard":1,"results":0}}</code></div>
            </x-cb.card>

            <x-cb.card title="Pull from a remote portal" icon="ri-download-cloud-2-line">
                <p class="small text-muted">Optional: instead of the remote pushing to us, this portal can pull the flags on a schedule.</p>
                <form method="POST" action="{{ route('feature-flags.sync-settings') }}">@csrf
                    <label class="form-label small">Remote flags URL</label>
                    <input type="url" name="remote_url" class="form-control form-control-sm mb-2" value="{{ $cfg->remote_url }}" placeholder="https://control.example.com/api/flags">
                    <label class="form-label small">Remote key (bearer)</label>
                    <input type="text" name="remote_key" class="form-control form-control-sm mb-2" placeholder="{{ $cfg->remoteKey() ? 'saved — leave blank to keep' : 'paste the key' }}">
                    <div class="form-check mb-2"><input type="hidden" name="auto_pull" value="0"><input class="form-check-input" type="checkbox" name="auto_pull" value="1" id="ap" @checked($cfg->auto_pull)><label for="ap" class="form-check-label">Pull automatically every hour</label></div>
                    <button class="action-btn btn-go w-100 justify-content-center"><i class="ri-save-line"></i>Save</button>
                </form>
                @if($cfg->last_pulled_at)<div class="small text-muted mt-2">Last pull: {{ $cfg->last_pulled_at->diffForHumans() }} · {{ $cfg->last_pull_status }}</div>@endif
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
