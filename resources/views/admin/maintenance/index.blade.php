{{-- resources/views/admin/maintenance/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Maintenance Mode" icon="ri-tools-line" subtitle="Take the portal offline for everyone except the people you choose — and put it back, all from this page.">
        <x-slot:pills>
            @if($m->is_active)
                <span class="cb-meta-pill"><span class="status-pill st-danger">Portal is OFFLINE</span></span>
                @if($m->activated_at)<span class="cb-meta-pill"><i class="ri-time-line"></i>Since {{ $m->activated_at->format('d M Y, H:i') }}{{ $activator ? ' · ' . $activator : '' }}</span>@endif
            @elseif($m->isScheduledPending())
                <span class="cb-meta-pill"><span class="status-pill st-warning">Scheduled</span></span>
                <span class="cb-meta-pill"><i class="ri-calendar-event-line"></i>{{ $m->scheduled_at->format('d M Y, H:i') }}</span>
            @else
                <span class="cb-meta-pill"><span class="status-pill st-paid">Portal is ONLINE</span></span>
            @endif
        </x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    @if($m->is_active)
        <div class="cb-banner warning"><i class="ri-alert-line"></i><div><strong>Maintenance mode is on.</strong> Everyone except you, other Super Admins, and the roles ticked below sees the maintenance page. You can turn it off at any time with the button on the right.</div></div>
    @endif

    <form method="POST" action="{{ route('maintenance.save') }}">@csrf
        <div class="row g-3">
            <div class="col-xl-8">
                <x-cb.card title="What visitors see" icon="ri-eye-line">
                    <label class="form-label">Heading</label>
                    <input type="text" name="title" class="form-control mb-3" maxlength="150" value="{{ old('title', $m->title) }}" placeholder="We'll be back shortly">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control mb-3" rows="3" maxlength="2000" placeholder="Explain what's happening and when you expect to be back.">{{ old('message', $m->message) }}</textarea>
                    <label class="form-label">Contact line (optional)</label>
                    <input type="text" name="contact_info" class="form-control" maxlength="255" value="{{ old('contact_info', $m->contact_info) }}" placeholder="e.g. Call the school office on 080… / bursar@school.ng">
                    <div class="form-text">Shown to anyone who can't get in, so they know how to reach the school.</div>
                </x-cb.card>

                <x-cb.card title="Who keeps access" icon="ri-team-line">
                    <p class="small text-muted">Super Admins and anyone who can manage maintenance always keep full access — they can never be locked out. Tick any other roles that should keep working while maintenance is on (for example your own bursary or IT role).</p>
                    <div class="row">
                        @foreach($roles as $r)
                            <div class="col-md-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_role_ids[]" value="{{ $r->id }}" id="role{{ $r->id }}"
                                        @checked(in_array($r->id, old('allow_role_ids', $m->allow_role_ids ?? [])))
                                        @disabled($r->name === 'Super Admin')>
                                    <label class="form-check-label" for="role{{ $r->id }}">{{ $r->name }}@if($r->name === 'Super Admin') <span class="small text-muted">(always)</span>@endif</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-cb.card>

                <x-cb.card title="Schedule a switch-on (optional)" icon="ri-calendar-schedule-line">
                    <p class="small text-muted">Plan a time for the portal to go into maintenance mode automatically. Admins see a countdown beforehand, and you can change or cancel it here at any time — it just flips the same switch you control above.</p>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5"><label class="form-label">Date &amp; time</label><input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', $m->scheduled_at?->format('Y-m-d\TH:i')) }}" min="{{ now()->format('Y-m-d\TH:i') }}"></div>
                        <div class="col-md-4"><label class="form-label">Note (optional)</label><input type="text" name="scheduled_note" class="form-control" maxlength="255" value="{{ old('scheduled_note', $m->scheduled_note) }}" placeholder="e.g. End-of-term upgrade"></div>
                        <div class="col-md-3 d-flex gap-2">
                            <button name="action" value="schedule" class="action-btn btn-open w-100 justify-content-center"><i class="ri-calendar-check-line"></i>Schedule</button>
                        </div>
                    </div>
                    @if($m->isScheduledPending())
                        <div class="mt-3 p-3 rounded" style="background:linear-gradient(135deg,#1e293b,#334155);color:#e2e8f0">
                            <div class="small text-uppercase" style="letter-spacing:.08em;opacity:.75">Portal goes into maintenance in</div>
                            <div id="mtBigCountdown" data-at="{{ $m->scheduled_at->toIso8601String() }}"
                                 style="font-size:2rem;font-weight:700;font-variant-numeric:tabular-nums;line-height:1.2">—</div>
                            <div class="small" style="opacity:.85">at <strong>{{ $m->scheduled_at->format('l, d M Y \a\t g:i A') }}</strong>{{ $m->scheduled_note ? ' · ' . $m->scheduled_note : '' }}</div>
                            <button name="action" value="clear_schedule" class="action-btn btn-open mt-2"><i class="ri-close-line"></i>Cancel schedule</button>
                        </div>
                        <script>
                        (function () {
                            var el = document.getElementById('mtBigCountdown'); if (!el) return;
                            var at = new Date(el.dataset.at).getTime();
                            function tick() {
                                var d = at - Date.now();
                                if (d <= 0) { el.textContent = 'Switching on…'; return; }
                                var days = Math.floor(d / 8.64e7),
                                    h = Math.floor((d % 8.64e7) / 3.6e6),
                                    m = Math.floor((d % 3.6e6) / 6e4),
                                    s = Math.floor((d % 6e4) / 1000),
                                    pad = function (n) { return (n < 10 ? '0' : '') + n; };
                                el.textContent = (days > 0 ? days + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
                            }
                            tick(); setInterval(tick, 1000);
                        })();
                        </script>
                    @endif
                </x-cb.card>
            </div>

            <div class="col-xl-4">
                <x-cb.card title="Switch" icon="ri-toggle-line">
                    @if($m->is_active)
                        <div class="text-center mb-3"><span class="status-pill st-danger" style="font-size:1rem;padding:8px 16px">Portal is OFFLINE</span></div>
                        <button name="action" value="turn_off" class="action-btn btn-primary-cb w-100 justify-content-center py-2"><i class="ri-play-circle-line"></i>Turn maintenance OFF</button>
                        <div class="small text-muted mt-2 text-center">Brings the portal back for everyone immediately.</div>
                    @else
                        <div class="text-center mb-3"><span class="status-pill st-paid" style="font-size:1rem;padding:8px 16px">Portal is ONLINE</span></div>
                        <button name="action" value="turn_on" class="action-btn btn-open w-100 justify-content-center py-2" onclick="return confirm('Take the portal offline now for everyone except the allowed roles?')"><i class="ri-pause-circle-line"></i>Turn maintenance ON now</button>
                        <div class="small text-muted mt-2 text-center">You'll still have full access and can turn it back off here.</div>
                    @endif
                    <hr>
                    <button name="action" value="save" class="action-btn btn-go w-100 justify-content-center"><i class="ri-save-line"></i>Save message &amp; roles</button>
                </x-cb.card>

                <x-cb.card title="Good to know" icon="ri-information-line">
                    <ul class="small ps-3 mb-0">
                        <li>The sign-in page always works, so you can log in and turn maintenance off even from another device.</li>
                        <li>Payment gateway confirmations keep working, so payments already in progress still complete.</li>
                        <li>Nothing here needs a server command — it's all controlled from this page.</li>
                    </ul>
                </x-cb.card>
            </div>
        </div>
    </form>
</div>
</div>
</div>
@endsection
