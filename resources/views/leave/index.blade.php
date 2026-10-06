{{-- resources/views/leave/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php $S = \App\Services\Leave\LeaveService::STATUS; $fmt = fn ($d) => rtrim(rtrim(number_format((float) $d, 1), '0'), '.');
     $rem = class_exists(\App\Services\Leave\LeaveReminderService::class) ? app(\App\Services\Leave\LeaveReminderService::class) : null;
     $current = $mine->first(fn ($r) => $r->status === 'approved' && empty($r->resumed_at) && \Carbon\Carbon::parse($r->start_date)->lte(today()) && \Carbon\Carbon::parse($r->end_date)->gte(today()->subDays(14))); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="My Leave" icon="ri-calendar-event-line" :subtitle="'Leave balances for ' . $year . ' and your requests'">
        <x-slot:actions>
            @if($queue)<a href="{{ route('leave.approvals') }}" class="cb-hero-btn"><i class="ri-inbox-line"></i>Approvals ({{ $queue }})</a>@endif
            @canany(['View leave records', 'Manage leave types'])<a href="{{ route('leave.records') }}" class="cb-hero-btn"><i class="ri-table-line"></i>Leave records</a>@endcanany
        </x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    @if($current && $rem)
        @php $resume = $rem->resumeDate($current->end_date); $left = \Carbon\Carbon::parse($current->end_date)->gte(today()) ? app(\App\Services\Leave\LeaveService::class)->workingDays(today(), $current->end_date) : 0; @endphp
        <div class="cb-card mb-3" style="border-left:4px solid #0f766e">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="small text-muted">You are on {{ strtolower($current->type) }}</div>
                    @if($left > 0)<div class="fs-4 fw-bold">{{ $fmt($left) }} working day{{ $left == 1 ? '' : 's' }} left</div>@else<div class="fs-4 fw-bold">Your leave has ended</div>@endif
                    <div>Resume on <b>{{ $resume->format('l j F Y') }}</b></div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if(today()->gte($resume->copy()->subDay()))
                        <form method="POST" action="{{ route('leave.resume', $current->id) }}">@csrf<button class="action-btn btn-primary-cb"><i class="ri-login-circle-line"></i>I'm back at work</button></form>
                    @endif
                    <button type="button" class="action-btn btn-open" data-bs-toggle="collapse" data-bs-target="#extendForm"><i class="ri-calendar-2-line"></i>Ask for more days</button>
                </div>
            </div>
            <form method="POST" action="{{ route('leave.extend', $current->id) }}" enctype="multipart/form-data" class="collapse row g-2 mt-2" id="extendForm">@csrf
                <div class="col-md-3"><label class="small">New last day</label><input type="date" name="end_date" class="form-control form-control-sm" min="{{ \Carbon\Carbon::parse($current->end_date)->addDay()->toDateString() }}" required></div>
                <div class="col-md-5"><label class="small">Reason</label><input name="reason" class="form-control form-control-sm" required minlength="5"></div>
                <div class="col-md-2"><label class="small">Document</label><input type="file" name="attachment" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png"></div>
                <div class="col-md-2 d-flex align-items-end"><button class="action-btn btn-go w-100 justify-content-center">Send</button></div>
            </form>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach($balances as $b)
            <div class="col-xl-3 col-md-4 col-6">
                <div class="cb-card h-100" style="border-top:4px solid {{ preg_match('/^#[0-9a-fA-F]{3,8}$/', $b->color) ? $b->color : '#0f766e' }}">
                    <div class="small text-muted">{{ $b->name }} {{ $b->paid ? '' : '(unpaid)' }}</div>
                    @if($b->limited)
                        <div class="fs-3 fw-bold">{{ $fmt($b->remaining) }} <span class="fs-6 text-muted fw-normal">of {{ $fmt($b->entitled) }} days left</span></div>
                        <div class="progress-track mt-1"><div class="progress-fill" style="width:{{ $b->entitled > 0 ? min(100, round(($b->approved + $b->pending) / $b->entitled * 100)) : 0 }}%"></div></div>
                    @else
                        <div class="fs-3 fw-bold">{{ $fmt($b->approved) }} <span class="fs-6 text-muted fw-normal">days taken</span></div>
                    @endif
                    @if($b->pending > 0)<div class="small text-warning mt-1">{{ $fmt($b->pending) }} day(s) waiting for approval</div>@endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="My requests" icon="ri-list-check-2" :count="$mine->count()" :flush="true">
                @if($mine->isEmpty())
                    <div class="empty-state"><i class="ri-calendar-event-line"></i><h6>No leave requests yet</h6></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Dates</th><th>Type</th><th class="text-end">Days</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($mine as $r)
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($r->start_date)->format('d M') }} – {{ \Carbon\Carbon::parse($r->end_date)->format('d M Y') }}
                                    @if($r->relief_name)<div class="small text-muted">Cover: {{ $r->relief_name }}</div>@endif
                                    @if($r->status === 'approved' && $rem)<div class="small text-muted">Resume {{ $rem->resumeDate($r->end_date)->format('D j M') }}@if(!empty($r->resumed_at)) · <span class="text-success">back {{ \Carbon\Carbon::parse($r->resumed_at)->format('j M') }}</span>@endif</div>@endif
                                    @if(!empty($r->extension_of))<div class="small text-muted">Extension</div>@endif</td>
                                <td>{{ $r->type }}</td>
                                <td class="text-end">{{ $fmt($r->days) }}</td>
                                <td><span class="status-pill {{ $S[$r->status][1] ?? 'st-muted' }}">{{ $S[$r->status][0] ?? $r->status }}</span>
                                    @if($r->approver_note || $r->hod_note)<div class="small text-muted">“{{ $r->approver_note ?: $r->hod_note }}”</div>@endif</td>
                                <td class="text-end">
                                    @if(in_array($r->status, ['pending_hod', 'pending_principal']) || ($r->status === 'approved' && \Carbon\Carbon::parse($r->start_date)->isFuture()))
                                        <form method="POST" action="{{ route('leave.cancel', $r->id) }}" onsubmit="return confirm('Cancel this leave request?')">@csrf<button class="btn btn-sm btn-light" title="Cancel"><i class="ri-close-line"></i></button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                @endif
            </x-cb.card>
        </div>
        <div class="col-xl-5">
            <x-cb.card title="Request leave" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('leave.store') }}" enctype="multipart/form-data">@csrf
                    <label class="small">Type of leave</label>
                    <select name="leave_type_id" class="form-select form-select-sm mb-2" required>
                        @foreach($types as $t)<option value="{{ $t->id }}" @selected(old('leave_type_id') == $t->id)>{{ $t->name }}{{ $t->requires_document ? ' (document needed)' : '' }}{{ $t->paid ? '' : ' — unpaid' }}</option>@endforeach
                    </select>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="small">From</label><input type="date" name="start_date" class="form-control form-control-sm" value="{{ old('start_date') }}" required></div>
                        <div class="col-6"><label class="small">To</label><input type="date" name="end_date" class="form-control form-control-sm" value="{{ old('end_date') }}" required></div>
                    </div>
                    <label class="form-check small mb-2"><input type="checkbox" class="form-check-input" name="half_day" value="1" @checked(old('half_day'))> <span class="form-check-label">Half day (single-day leave only)</span></label>
                    <label class="small">Reason</label><textarea name="reason" class="form-control form-control-sm mb-2" rows="3" required minlength="5">{{ old('reason') }}</textarea>
                    <label class="small">Handover notes for your cover (lessons, pending work)</label>
                    <textarea name="handover_note" class="form-control form-control-sm mb-2" rows="2" placeholder="e.g. JSS2 Maths — continue chapter 5; SS1 test papers in the staff room">{{ old('handover_note') }}</textarea>
                    <label class="small">Who will cover your classes / duties?</label>
                    <select name="relief_staff_id" class="form-select form-select-sm mb-2"><option value="">—</option>@foreach($colleagues as $id => $n)<option value="{{ $id }}" @selected(old('relief_staff_id') == $id)>{{ $n }}</option>@endforeach</select>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="small">Phone while away</label><input name="contact_phone" class="form-control form-control-sm" value="{{ old('contact_phone', $s->phonenumber ?? '') }}"></div>
                        <div class="col-6"><label class="small">Document</label><input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm"></div>
                    </div>
                    <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-send-plane-line"></i>Send request</button>
                    <div class="small text-muted mt-2">Only working days count (weekends and school holidays are skipped). You'll get reminders of the days left and your resumption date by SMS / WhatsApp / email. {{ $hasHod ? 'Your HOD reviews it first, then the principal.' : 'It goes to the principal for approval.' }}</div>
                </form>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
