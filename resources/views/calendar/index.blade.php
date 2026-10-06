{{-- resources/views/calendar/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $prev = $anchor->copy()->subMonth()->format('Y-m');
    $next = $anchor->copy()->addMonth()->format('Y-m');
    $today = \Carbon\Carbon::today();
    $q = fn($extra) => array_merge(request()->query(), $extra);
    $weekdays = ['Sun'=>0,'Mon'=>1,'Tue'=>2,'Wed'=>3,'Thu'=>4,'Fri'=>5,'Sat'=>6];
    $feedUrl = \App\Http\Controllers\PublicCalendarController::feedToken();
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="School Calendar" icon="ri-calendar-2-line" subtitle="Activities and important dates across the session and term — with reminders by in-portal notice, email, SMS and WhatsApp.">
        <x-slot name="actions">
            @if($manage)
                <button class="action-btn btn-primary-cb" onclick="calNew()"><i class="ri-add-line"></i>Add event</button>
                <button class="action-btn btn-open" data-bs-toggle="modal" data-bs-target="#calCatModal"><i class="ri-price-tag-3-line"></i>Categories</button>
                <form method="POST" action="{{ route('calendar.sync-fees') }}" class="d-inline" onsubmit="return confirm('Refresh fee-deadline events from bills and instalment plans now?')">@csrf<button class="action-btn btn-open"><i class="ri-refresh-line"></i>Sync fees</button></form>
            @endif
            <a href="{{ route('calendar.public') }}" target="_blank" class="action-btn btn-go"><i class="ri-external-link-line"></i>Public page</a>
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    {{-- Filters --}}
    <x-cb.card title="Filter" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="month" value="{{ $anchor->format('Y-m') }}">
            <div class="col-md-3"><label class="form-label small">Category</label>
                <select name="category" class="form-select" onchange="this.form.submit()"><option value="">All categories</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small">Session</label>
                <select name="session" class="form-select" onchange="this.form.submit()"><option value="">All sessions</option>
                    @foreach($sessions as $id=>$name)<option value="{{ $id }}" @selected(request('session')==$id)>{{ $name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small">Term</label>
                <select name="term" class="form-select" onchange="this.form.submit()"><option value="">All terms</option>
                    @foreach($terms as $id=>$name)<option value="{{ $id }}" @selected(request('term')==$id)>{{ $name }}</option>@endforeach</select></div>
            <div class="col-md-3">
                <label class="form-label small d-block">Options</label>
                <a href="{{ route('calendar.index', $q(['holidays'=>request('holidays','1')==='0'?'1':'0'])) }}" class="action-btn {{ request('holidays','1')!=='0'?'btn-primary-cb':'btn-open' }}"><i class="ri-flag-line"></i>{{ request('holidays','1')!=='0'?'Holidays shown':'Holidays hidden' }}</a>
            </div>
        </form>
    </x-cb.card>

    <div class="row g-3">
        {{-- Upcoming --}}
        <div class="col-xl-4">
            <x-cb.card title="Upcoming" icon="ri-calendar-todo-line" :count="$upcoming->count()" :flush="true">
                @if($upcoming->isEmpty())
                    <div class="empty-state"><i class="ri-calendar-line"></i><h6>Nothing upcoming</h6><p>No events in the next several weeks.</p></div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($upcoming as $e)
                            <li class="list-group-item d-flex align-items-start gap-2 {{ $e->kind==='event' ? 'cal-click' : '' }}" @if($e->kind==='event') role="button" onclick="calShow({{ $e->event_id }})" @endif>
                                <span class="cb-dot mt-1" style="background: {{ $e->color }}"></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $e->title }}</div>
                                    <div class="small text-muted">
                                        {{ $e->start->isSameDay($e->end) ? $e->start->format('D, d M') : $e->start->format('d M').' – '.$e->end->format('d M') }}
                                        @if(!$e->all_day && $e->start_time) · {{ \Carbon\Carbon::parse($e->start_time)->format('g:i a') }}@endif
                                        @if($e->location) · {{ $e->location }}@endif
                                    </div>
                                </div>
                                @if($e->category)<span class="term-chip">{{ $e->category }}</span>@endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-cb.card>

            <x-cb.card title="Subscribe" icon="ri-rss-line">
                <p class="small text-muted mb-2">Add the public school calendar to Google or Apple Calendar so it stays in sync.</p>
                <div class="input-group input-group-sm">
                    <input class="form-control" id="calFeed" readonly value="{{ route('calendar.ical', ['token'=>$feedUrl]) }}">
                    <button class="btn btn-outline-secondary" type="button" onclick="calCopyFeed()"><i class="ri-file-copy-line"></i></button>
                </div>
            </x-cb.card>
        </div>

        {{-- Month calendar --}}
        <div class="col-xl-8">
            <x-cb.card title="{{ $anchor->format('F Y') }}" icon="ri-calendar-2-line">
                <x-slot name="tools">
                    <a href="{{ route('calendar.index', $q(['month'=>$prev])) }}" class="action-btn btn-open" title="Previous"><i class="ri-arrow-left-s-line"></i></a>
                    <a href="{{ route('calendar.index', $q(['month'=>now()->format('Y-m')])) }}" class="action-btn btn-open">Today</a>
                    <a href="{{ route('calendar.index', $q(['month'=>$next])) }}" class="action-btn btn-open" title="Next"><i class="ri-arrow-right-s-line"></i></a>
                </x-slot>
                <div class="cb-cal">
                    <div class="cb-cal-head">@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)<div>{{ $d }}</div>@endforeach</div>
                    @foreach($weeks as $week)
                        <div class="cb-cal-row">
                            @foreach($week as $cell)
                                @php $isToday = $cell->date->isSameDay($today); @endphp
                                <div class="cb-cal-cell {{ $cell->inMonth ? '' : 'muted' }} {{ $isToday ? 'today' : '' }}" @if($manage) ondblclick="calNew('{{ $cell->date->toDateString() }}')" @endif>
                                    <div class="cb-cal-date">{{ $cell->date->day }}</div>
                                    @foreach($cell->events->take(4) as $e)
                                        <div class="cb-cal-ev {{ $e->kind==='event' ? 'cal-click' : '' }}" style="border-left-color: {{ $e->color }}"
                                            title="{{ $e->title }}{{ $e->category ? ' — '.$e->category : '' }}"
                                            @if($e->kind==='event') role="button" onclick="calShow({{ $e->event_id }})" @endif>
                                            <span class="cb-dot" style="background: {{ $e->color }}"></span>{{ \Illuminate\Support\Str::limit($e->title, 14) }}
                                        </div>
                                    @endforeach
                                    @if($cell->events->count() > 4)<div class="cb-cal-more">+{{ $cell->events->count() - 4 }} more</div>@endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <div class="d-flex gap-3 mt-3 small text-muted flex-wrap">
                    @foreach($categories->where('is_active', true) as $c)<span><span class="cb-dot" style="background: {{ $c->color }}"></span> {{ $c->name }}</span>@endforeach
                </div>
            </x-cb.card>
        </div>
    </div>

    @if($manage && $events->isNotEmpty())
    <x-cb.card title="All events" icon="ri-list-check-2" :count="$events->count()" :flush="true">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Title</th><th>Category</th><th>Dates</th><th>Audience</th><th>Reminders</th><th>Source</th><th></th></tr></thead>
            <tbody>
            @foreach($events as $e)
                <tr>
                    <td><span class="cb-dot" style="background: {{ $e->displayColor() }}"></span> {{ $e->title }}</td>
                    <td class="small">{{ $e->category->name ?? '—' }}</td>
                    <td class="small">{{ $e->start_date->format('d M Y') }}{{ $e->start_date->ne($e->end_date) ? ' – '.$e->end_date->format('d M Y') : '' }}
                        @if(is_array($e->recurrence) && ($e->recurrence['freq'] ?? 'none')!=='none')<span class="term-chip">repeats</span>@endif</td>
                    <td class="small">{{ implode(', ', array_map('ucfirst', $e->audienceList())) ?: '—' }}</td>
                    <td class="small">{{ count($e->reminders ?? []) ? count($e->reminders).' set' : '—' }}</td>
                    <td>@if($e->source!=='manual')<span class="status-pill st-info">{{ $e->source }}</span>@else<span class="text-muted small">manual</span>@endif</td>
                    <td class="text-end">
                        <button class="action-btn btn-open" onclick="calShow({{ $e->id }})" title="View"><i class="ri-eye-line"></i></button>
                        <button class="action-btn btn-open" onclick="calEdit({{ $e->id }})" title="Edit"><i class="ri-pencil-line"></i></button>
                        <form method="POST" action="{{ route('calendar.destroy', $e->id) }}" class="d-inline" onsubmit="return confirm('Delete this event?')">@csrf @method('DELETE')<button class="action-btn btn-open" title="Delete"><i class="ri-delete-bin-line"></i></button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </x-cb.card>
    @endif
</div>
</div>
</div>

{{-- ─── Event detail modal ─── --}}
<div class="modal fade" id="calDetailModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="cdTitle">Event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="cdBody"><div class="text-center text-muted py-4"><i class="ri-loader-4-line"></i> Loading…</div></div>
</div></div></div>

@if($manage)
{{-- ─── Create / edit modal ─── --}}
<div class="modal fade" id="calEventModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form method="POST" id="calEventForm" action="{{ route('calendar.store') }}" enctype="multipart/form-data">@csrf
        <input type="hidden" name="_method" id="ceMethod" value="POST">
        <div class="modal-header"><h5 class="modal-title" id="ceHeading">Add event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row g-2">
                <div class="col-md-8"><label class="form-label small">Title *</label><input name="title" id="ceTitle" class="form-control" required maxlength="180"></div>
                <div class="col-md-4"><label class="form-label small">Category</label>
                    <select name="category_id" id="ceCategory" class="form-select"><option value="">—</option>
                        @foreach($categories->where('is_active',true) as $c)<option value="{{ $c->id }}" data-color="{{ $c->color }}">{{ $c->name }}</option>@endforeach</select></div>

                <div class="col-md-6"><label class="form-label small">Start date *</label><input type="date" name="start_date" id="ceStart" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label small">End date *</label><input type="date" name="end_date" id="ceEnd" class="form-control" required></div>

                <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="all_day" id="ceAllDay" value="1" checked onchange="ceToggleTimes()"><label class="form-check-label small" for="ceAllDay">All day</label></div></div>
                <div class="col-md-4"><label class="form-label small">Start time</label><input type="time" name="start_time" id="ceStartTime" class="form-control" disabled></div>
                <div class="col-md-4"><label class="form-label small">End time</label><input type="time" name="end_time" id="ceEndTime" class="form-control" disabled></div>

                <div class="col-md-6"><label class="form-label small">Location</label><input name="location" id="ceLocation" class="form-control" maxlength="160"></div>
                <div class="col-md-3"><label class="form-label small">Session</label><select name="session_id" id="ceSession" class="form-select"><option value="">—</option>@foreach($sessions as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small">Term</label><select name="term_id" id="ceTerm" class="form-select"><option value="">—</option>@foreach($terms as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>

                <div class="col-12"><label class="form-label small">Description</label><textarea name="description" id="ceDesc" class="form-control" rows="2" maxlength="5000"></textarea></div>

                <div class="col-md-7">
                    <label class="form-label small d-block">Who sees this</label>
                    @foreach($audiences as $k=>$label)
                        <div class="form-check form-check-inline"><input class="form-check-input ce-aud" type="checkbox" name="audiences[]" value="{{ $k }}" id="ceAud{{ $k }}"><label class="form-check-label small" for="ceAud{{ $k }}">{{ $label }}</label></div>
                    @endforeach
                </div>
                <div class="col-md-5 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="rsvp_enabled" id="ceRsvp" value="1"><label class="form-check-label small" for="ceRsvp">Ask parents to RSVP</label></div></div>

                {{-- Recurrence --}}
                <div class="col-12"><hr class="my-2"><div class="small fw-semibold text-muted">Repeat</div></div>
                <div class="col-md-4"><label class="form-label small">Frequency</label>
                    <select name="rec_freq" id="ceFreq" class="form-select" onchange="ceToggleRec()">
                        <option value="none">Does not repeat</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="yearly">Yearly</option>
                    </select></div>
                <div class="col-md-3 ce-rec d-none"><label class="form-label small">Every</label><input type="number" name="rec_interval" id="ceInterval" class="form-control" min="1" max="52" value="1"></div>
                <div class="col-md-5 ce-rec d-none"><label class="form-label small">Until</label><input type="date" name="rec_until" id="ceUntil" class="form-control"></div>
                <div class="col-12 ce-rec-week d-none"><label class="form-label small d-block">On days</label>
                    @foreach($weekdays as $lbl=>$num)<div class="form-check form-check-inline"><input class="form-check-input ce-byday" type="checkbox" name="rec_byday[]" value="{{ $num }}" id="ceDay{{ $num }}"><label class="form-check-label small" for="ceDay{{ $num }}">{{ $lbl }}</label></div>@endforeach
                </div>

                {{-- Reminders --}}
                <div class="col-12"><hr class="my-2"><div class="small fw-semibold text-muted">Reminders <span class="fw-normal">— sent to the audience above</span></div></div>
                <div class="col-12" id="ceReminders">
                    @for($i=0;$i<3;$i++)
                    <div class="row g-2 align-items-center mb-1 ce-rem-row">
                        <div class="col-auto small text-muted">Remind</div>
                        <div class="col-auto"><input type="number" name="reminders[{{ $i }}][days_before]" class="form-control form-control-sm ce-rem-days" min="0" max="60" style="width:80px" placeholder="days"></div>
                        <div class="col-auto small text-muted">day(s) before, via</div>
                        @foreach(['portal'=>'In-portal','email'=>'Email','sms'=>'SMS','whatsapp'=>'WhatsApp'] as $ch=>$cl)
                            <div class="col-auto"><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="reminders[{{ $i }}][channels][]" value="{{ $ch }}" id="ceRem{{ $i }}{{ $ch }}"><label class="form-check-label small" for="ceRem{{ $i }}{{ $ch }}">{{ $cl }}</label></div></div>
                        @endforeach
                    </div>
                    @endfor
                    <div class="small text-muted">Tip: leave "days" blank to skip a row. SMS and WhatsApp use message credit.</div>
                </div>

                <div class="col-12"><label class="form-label small">Attachment (optional)</label><input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"><div id="ceAttachList" class="small text-muted mt-1"></div></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save event</button></div>
    </form>
</div></div></div>

{{-- ─── Categories modal ─── --}}
<div class="modal fade" id="calCatModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Calendar categories</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <form method="POST" action="{{ route('calendar.categories.store') }}" class="row g-2 align-items-end mb-3">@csrf
            <div class="col-5"><label class="form-label small">Name</label><input name="name" class="form-control" required maxlength="80"></div>
            <div class="col-4"><label class="form-label small">Colour</label><input type="color" name="color" class="form-control form-control-color" value="#0f766e"></div>
            <div class="col-3"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-add-line"></i>Add</button></div>
        </form>
        <ul class="list-group">
            @foreach($categories as $c)
                <li class="list-group-item d-flex align-items-center gap-2">
                    <span class="cb-dot" style="background: {{ $c->color }}"></span>
                    <span class="flex-grow-1">{{ $c->name }} @unless($c->is_active)<span class="text-muted small">(hidden)</span>@endunless</span>
                    <form method="POST" action="{{ route('calendar.categories.destroy', $c->id) }}" onsubmit="return confirm('Delete this category? Its events stay but lose the category.')">@csrf @method('DELETE')<button class="action-btn btn-open"><i class="ri-delete-bin-line"></i></button></form>
                </li>
            @endforeach
        </ul>
    </div>
</div></div></div>
@endif

@once
<style>
.cb-dot{display:inline-block;width:9px;height:9px;border-radius:50%;flex:0 0 auto;margin-right:2px}
.cb-cal{border:1px solid var(--cb-border,#e5e7eb);border-radius:12px;overflow:hidden}
.cb-cal-head{display:grid;grid-template-columns:repeat(7,1fr);background:var(--cb-soft,#f8fafc);font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase}
.cb-cal-head>div{padding:8px 6px;text-align:center;border-right:1px solid var(--cb-border,#eef2f7)}
.cb-cal-head>div:last-child{border-right:0}
.cb-cal-row{display:grid;grid-template-columns:repeat(7,1fr);border-top:1px solid var(--cb-border,#eef2f7)}
.cb-cal-cell{min-height:104px;padding:5px 5px 8px;border-right:1px solid var(--cb-border,#eef2f7);background:#fff}
.cb-cal-cell:last-child{border-right:0}
.cb-cal-cell.muted{background:#fafbfc}.cb-cal-cell.muted .cb-cal-date{color:#cbd5e1}
.cb-cal-cell.today{background:#f0fdfa;box-shadow:inset 0 0 0 2px #99f6e4}
.cb-cal-date{font-size:.78rem;font-weight:600;color:#334155;margin-bottom:4px;text-align:right}
.cb-cal-ev{display:flex;align-items:center;font-size:.7rem;line-height:1.2;padding:2px 4px;margin-bottom:2px;border-radius:4px;background:#f8fafc;border-left:3px solid #94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cb-cal-ev.cal-click{cursor:pointer}.cb-cal-ev.cal-click:hover{background:#eef2f7}
.cal-click{cursor:pointer}
.cb-cal-more{font-size:.66rem;color:#64748b;padding:0 4px}
@media (max-width:640px){.cb-cal-cell{min-height:70px}.cb-cal-ev{font-size:.62rem}}
</style>
@endonce

<script>
(function(){
  window.calShowUrlBase = "{{ url('calendar/event') }}";
  window.calCsrf = "{{ csrf_token() }}";
})();
function ceToggleTimes(){var a=document.getElementById('ceAllDay').checked;document.getElementById('ceStartTime').disabled=a;document.getElementById('ceEndTime').disabled=a;}
function ceToggleRec(){var f=document.getElementById('ceFreq').value;var on=f!=='none';document.querySelectorAll('.ce-rec').forEach(function(x){x.classList.toggle('d-none',!on);});document.querySelectorAll('.ce-rec-week').forEach(function(x){x.classList.toggle('d-none',f!=='weekly');});}
function calCopyFeed(){var i=document.getElementById('calFeed');i.select();try{document.execCommand('copy');}catch(e){}}
@if($manage)
function calResetForm(){
  var f=document.getElementById('calEventForm');f.reset();
  document.getElementById('ceMethod').value='POST';
  f.action="{{ route('calendar.store') }}";
  document.getElementById('ceHeading').textContent='Add event';
  document.getElementById('ceAllDay').checked=true;ceToggleTimes();ceToggleRec();
  document.getElementById('ceAttachList').textContent='';
}
function calNew(date){
  calResetForm();
  if(date){document.getElementById('ceStart').value=date;document.getElementById('ceEnd').value=date;}
  new bootstrap.Modal(document.getElementById('calEventModal')).show();
}
function calEdit(id){
  fetch(calShowUrlBase+'/'+id,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(function(d){
    var e=d.event;calResetForm();
    document.getElementById('ceHeading').textContent='Edit event';
    document.getElementById('ceMethod').value='PUT';
    document.getElementById('calEventForm').action="{{ url('calendar') }}/"+e.id;
    document.getElementById('ceTitle').value=e.title||'';
    document.getElementById('ceCategory').value=e.category_id||'';
    document.getElementById('ceStart').value=e.start_date;document.getElementById('ceEnd').value=e.end_date;
    document.getElementById('ceAllDay').checked=!!e.all_day;ceToggleTimes();
    if(e.start_time){document.getElementById('ceStartTime').value=e.start_time.substring(0,5);}
    if(e.end_time){document.getElementById('ceEndTime').value=e.end_time.substring(0,5);}
    document.getElementById('ceLocation').value=e.location||'';
    document.getElementById('ceDesc').value=e.description||'';
    document.getElementById('ceRsvp').checked=!!e.rsvp_enabled;
    (e.audiences||[]).forEach(function(a){var el=document.getElementById('ceAud'+a);if(el)el.checked=true;});
    var rec=e.recurrence||{};document.getElementById('ceFreq').value=rec.freq||'none';
    if(rec.interval)document.getElementById('ceInterval').value=rec.interval;
    if(rec.until)document.getElementById('ceUntil').value=rec.until;
    (rec.byday||[]).forEach(function(n){var el=document.getElementById('ceDay'+n);if(el)el.checked=true;});
    ceToggleRec();
    var rems=e.reminders||[];
    document.querySelectorAll('.ce-rem-row').forEach(function(row,i){
      var r=rems[i];if(!r)return;
      row.querySelector('.ce-rem-days').value=r.days_before;
      (r.channels||[]).forEach(function(ch){var cb=row.querySelector('input[value="'+ch+'"]');if(cb)cb.checked=true;});
    });
    if(e.attachments&&e.attachments.length){document.getElementById('ceAttachList').innerHTML=e.attachments.map(a=>'<a href="'+a.url+'" target="_blank">'+a.name+'</a>').join(', ');}
    new bootstrap.Modal(document.getElementById('calEventModal')).show();
  });
}
@endif
function calShow(id){
  var m=new bootstrap.Modal(document.getElementById('calDetailModal'));
  document.getElementById('cdBody').innerHTML='<div class="text-center text-muted py-4">Loading…</div>';m.show();
  fetch(calShowUrlBase+'/'+id,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(function(d){
    var e=d.event;document.getElementById('cdTitle').textContent=e.title;
    var dates=e.start_date===e.end_date?e.start_date:(e.start_date+' – '+e.end_date);
    var html='';
    html+='<div class="mb-2"><span class="cb-dot" style="background:'+e.color+'"></span> '+(e.category||'Event')+'</div>';
    html+='<div class="small mb-1"><i class="ri-calendar-line"></i> '+dates+(e.all_day?'':(e.start_time?' · '+e.start_time.substring(0,5):''))+'</div>';
    if(e.location)html+='<div class="small mb-1"><i class="ri-map-pin-line"></i> '+e.location+'</div>';
    if(e.description)html+='<p class="mt-2">'+e.description.replace(/</g,'&lt;')+'</p>';
    if(e.attachments&&e.attachments.length){html+='<div class="small mt-2"><strong>Attachments:</strong> '+e.attachments.map(a=>'<a href="'+a.url+'" target="_blank">'+a.name+'</a>').join(', ')+'</div>';}
    if(e.rsvp_enabled){
      var mine=d.my_rsvp;var c=d.rsvp_counts||{};
      html+='<hr><div class="small fw-semibold mb-1">Will you attend?</div>';
      html+='<form method="POST" action="{{ url('calendar') }}/'+e.id+'/rsvp" class="d-flex gap-2"><input type="hidden" name="_token" value="'+window.calCsrf+'">';
      ['going','maybe','no'].forEach(function(v){var lbl=v==='going'?'Going':(v==='maybe'?'Maybe':'Not going');html+='<button name="response" value="'+v+'" class="action-btn '+(mine===v?'btn-primary-cb':'btn-open')+'">'+lbl+'</button>';});
      html+='</form>';
      html+='<div class="small text-muted mt-1">Going: '+(c.going||0)+' · Maybe: '+(c.maybe||0)+' · No: '+(c.no||0)+'</div>';
    }
    document.getElementById('cdBody').innerHTML=html;
  }).catch(function(){document.getElementById('cdBody').innerHTML='<div class="text-danger small">Could not load this event.</div>';});
}
</script>
@endsection
