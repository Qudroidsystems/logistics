{{-- resources/views/admin/backups/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $human = function ($b) { $b=(int)$b; if($b<=0) return '0 B'; $u=['B','KB','MB','GB']; $i=0; while($b>=1024 && $i<3){$b/=1024;$i++;} return round($b, $b<10&&$i>0?1:0).' '.$u[$i]; };
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Database Backups" icon="ri-database-2-line" subtitle="Take a backup now, or schedule automatic backups and have them emailed to you.">
        <x-slot name="actions">
            <form method="POST" action="{{ route('admin.backups.run') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<i class=\'ri-loader-4-line\'></i> Backing up…';">@csrf
                <input type="hidden" name="email" value="{{ $settings->email ? 1 : 0 }}">
                <button class="action-btn btn-primary-cb"><i class="ri-download-cloud-2-line"></i>Back up now</button>
            </form>
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="row g-3 mb-1">
        <div class="col-6 col-lg-3"><x-cb.stat label="Backups stored" :value="$stats['count']" icon="ri-file-copy-2-line" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Total size" :value="$human($stats['total'])" icon="ri-hard-drive-2-line" accent="violet" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Last backup" :value="$stats['last'] ? $stats['last']->created_at->diffForHumans() : 'Never'" icon="ri-time-line" accent="sky" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Next scheduled" :value="$stats['next'] ?? ($settings->enabled ? 'Soon' : 'Off')" icon="ri-calendar-schedule-line" :accent="$settings->enabled?'amber':'muted'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <x-cb.card title="Automatic schedule" icon="ri-calendar-schedule-line">
                <form method="POST" action="{{ route('admin.backups.settings') }}">@csrf
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="enabled" id="bkEnabled" value="1" @checked($settings->enabled)>
                        <label class="form-check-label fw-semibold" for="bkEnabled">Enable automatic backups</label>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Frequency</label>
                            <select name="frequency" id="bkFreq" class="form-select" onchange="bkFreqUI()">
                                @foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly'] as $k=>$v)<option value="{{ $k }}" @selected($settings->frequency===$k)>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label small">Time</label><input type="time" name="run_time" class="form-control" value="{{ $settings->run_time }}"></div>
                        <div class="col-6 bk-weekly {{ $settings->frequency==='weekly'?'':'d-none' }}">
                            <label class="form-label small">Day of week</label>
                            <select name="day_of_week" class="form-select">
                                @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i=>$d)<option value="{{ $i }}" @selected((int)$settings->day_of_week===$i)>{{ $d }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-6 bk-monthly {{ $settings->frequency==='monthly'?'':'d-none' }}">
                            <label class="form-label small">Day of month</label>
                            <input type="number" name="day_of_month" class="form-control" min="1" max="28" value="{{ $settings->day_of_month }}">
                        </div>
                    </div>
                    <hr class="my-3">
                    <label class="form-label small">Email backups to</label>
                    <input type="email" name="email" class="form-control mb-2" value="{{ $settings->email }}" placeholder="accountant@school.com">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="email_attach" id="bkAttach" value="1" @checked($settings->email_attach)>
                        <label class="form-check-label small" for="bkAttach">Attach the file to the email (only if under 15 MB)</label>
                    </div>
                    <label class="form-label small">Keep the last</label>
                    <div class="input-group mb-3" style="max-width:200px"><input type="number" name="keep_last" class="form-control" min="1" max="365" value="{{ $settings->keep_last }}"><span class="input-group-text">backups</span></div>
                    <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-save-line"></i>Save schedule</button>
                    <div class="small text-muted mt-2">Automatic backups need the cron scheduler running (the same one used for reminders). MySQL/MariaDB only.</div>
                </form>
            </x-cb.card>
        </div>

        <div class="col-xl-7">
            <x-cb.card title="Backups" icon="ri-archive-2-line" :count="$backups->total()" :flush="true">
                @if($backups->isEmpty())
                    <div class="empty-state"><i class="ri-database-2-line"></i><h6>No backups yet</h6><p>Use "Back up now" to create your first backup.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>File</th><th>Size</th><th>Type</th><th>When</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        @foreach($backups as $b)
                            <tr>
                                <td><i class="ri-file-zip-line text-muted me-1"></i>{{ $b->filename }}
                                    @if($b->status!=='success')<span class="status-pill st-danger ms-1">failed</span>@endif
                                    @if($b->emailed_to)<div class="small text-muted"><i class="ri-mail-send-line"></i> emailed</div>@endif
                                    @if($b->status!=='success' && $b->note)<div class="small text-danger">{{ \Illuminate\Support\Str::limit($b->note,80) }}</div>@endif
                                </td>
                                <td class="small">{{ $b->humanSize() }}</td>
                                <td><span class="status-pill {{ $b->type==='scheduled'?'st-info':'st-muted' }}">{{ $b->type }}</span><div class="small text-muted">{{ $b->method }}</div></td>
                                <td class="small">{{ optional($b->created_at)->format('d M Y H:i') }}</td>
                                <td class="text-end">
                                    @if($b->status==='success' && $b->exists())
                                    <a href="{{ route('admin.backups.download', $b) }}" class="action-btn btn-open" title="Download"><i class="ri-download-2-line"></i></a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.backups.destroy', $b) }}" class="d-inline" onsubmit="return confirm('Delete this backup file permanently?')">@csrf @method('DELETE')<button class="action-btn btn-open" title="Delete"><i class="ri-delete-bin-line"></i></button></form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
            @if($backups->hasPages())<div class="mt-3">{{ $backups->links() }}</div>@endif
        </div>
    </div>
</div></div></div>

<script>
function bkFreqUI(){var f=document.getElementById('bkFreq').value;
    document.querySelectorAll('.bk-weekly').forEach(e=>e.classList.toggle('d-none',f!=='weekly'));
    document.querySelectorAll('.bk-monthly').forEach(e=>e.classList.toggle('d-none',f!=='monthly'));}
</script>
@endsection
