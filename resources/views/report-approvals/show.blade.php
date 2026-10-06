{{-- resources/views/report-approvals/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php [$sl, $sc] = \App\Models\ReportApproval::STATUSES[$a->status]; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$label" icon="ri-shield-check-line" subtitle="Check the report cards, then submit or approve."
               :back="route('report-approvals.index', ['term_id' => $a->term_id, 'session_id' => $a->session_id])" back-label="All classes">
        <x-slot:pills><span class="cb-meta-pill"><i class="ri-flag-line"></i>{{ $sl }}</span></x-slot:pills>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    @if($a->status === 'returned' && $a->review_note)
        <div class="cb-banner warning"><i class="ri-arrow-go-back-line"></i><div><strong>Returned by {{ $a->reviewer?->name }}:</strong> {{ $a->review_note }}</div></div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="col-sm-4 col-6"><x-cb.stat label="Students" :value="$r->students ?? 0" icon="ri-team-line" accent="teal" /></div>
                <div class="col-sm-4 col-6"><x-cb.stat label="Scores vetted" :value="($r->vetted_pct ?? 0) . '%'" icon="ri-checkbox-circle-line" :accent="($r->ready ?? false) ? 'green' : 'amber'" :hint="($r ? (int) $r->vetted . ' of ' . $r->entries : '')" /></div>
                <div class="col-sm-4 col-6"><x-cb.stat label="Teacher comments" :value="($r->t_comments ?? 0) . '/' . ($r->students ?? 0)" icon="ri-chat-3-line" accent="violet" /></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="cb-card h-100">
                @if(in_array($a->status, ['pending', 'returned']) && $canSubmit)
                    <form method="POST" action="{{ route('report-approvals.submit', $a) }}">@csrf
                        <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Note to the principal (optional)"></textarea>
                        @if($r && !$r->ready)
                            <label class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="force" value="1"> <span class="form-check-label">Submit anyway ({{ $r->entries - $r->vetted }} scores not vetted)</span></label>
                        @endif
                        <button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-send-plane-line"></i>Submit for approval</button>
                    </form>
                @endif
                @if($canApprove && $a->status !== 'approved')
                    <form method="POST" action="{{ route('report-approvals.approve', $a) }}" class="{{ in_array($a->status, ['pending', 'returned']) && $canSubmit ? 'mt-3' : '' }}">@csrf
                        <input name="note" class="form-control form-control-sm mb-2" placeholder="Approval note (optional)">
                        <button class="action-btn btn-go w-100 justify-content-center"><i class="ri-checkbox-circle-line"></i>Approve &amp; release</button>
                    </form>
                    @if($a->status === 'submitted')
                        <form method="POST" action="{{ route('report-approvals.return', $a) }}" class="mt-2">@csrf
                            <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="What needs fixing?" required></textarea>
                            <button class="action-btn btn-open w-100 justify-content-center"><i class="ri-arrow-go-back-line"></i>Return to class teacher</button>
                        </form>
                    @endif
                @endif
                @if($canApprove && $a->status === 'approved')
                    <div class="small text-muted mb-2">Approved by {{ $a->reviewer?->name }} on {{ $a->reviewed_at?->format('d M Y, h:i A') }}.</div>
                    <form method="POST" action="{{ route('report-approvals.reopen', $a) }}" onsubmit="return confirm('Withdraw the approval? Results may be hidden again.')">@csrf
                        <input name="note" class="form-control form-control-sm mb-2" placeholder="Reason (optional)">
                        <button class="action-btn btn-open w-100 justify-content-center"><i class="ri-lock-line"></i>Withdraw approval</button>
                    </form>
                @endif
                @if($a->status === 'submitted' && !$canApprove)
                    <div class="small text-muted">Submitted {{ $a->submitted_at?->diffForHumans() }} — waiting for the principal.</div>
                @endif
                @if(!$canSubmit && !$canApprove)
                    <div class="small text-muted">Only the class teacher or the principal can act on this class.</div>
                @endif
            </div>
        </div>
    </div>

    <x-cb.card title="Students" icon="ri-team-line" :count="$students->count()" :flush="true">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th class="text-end">Average</th><th>Vetted</th><th>Class teacher comment</th><th>Principal comment</th><th></th></tr></thead>
                <tbody>
                @foreach($students as $st)
                    @php $c = $comments[$st->id] ?? null; @endphp
                    <tr>
                        <td><strong>{{ $st->lastname }} {{ $st->firstname }}</strong><div class="small text-muted">{{ $st->admissionNo }}</div></td>
                        <td class="text-end">{{ $st->average }}</td>
                        <td><span class="status-pill {{ $st->vetted >= $st->entries ? 'st-paid' : 'st-pending' }}">{{ (int) $st->vetted }}/{{ $st->entries }}</span></td>
                        <td class="small" style="max-width:260px">{!! trim($c->classteachercomment ?? '') !== '' ? e(\Illuminate\Support\Str::limit($c->classteachercomment, 90)) : '<span class="text-warning">Missing</span>' !!}</td>
                        <td class="small" style="max-width:260px">{!! trim($c->principalscomment ?? '') !== '' ? e(\Illuminate\Support\Str::limit($c->principalscomment, 90)) : '<span class="text-muted">—</span>' !!}</td>
                        <td class="text-end"><a target="_blank" href="{{ route('report-approvals.preview', [$a, $st->id]) }}" class="action-btn btn-open"><i class="ri-file-pdf-2-line"></i>Preview</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-cb.card>

    @if(!empty($a->history))
        <x-cb.card title="History" icon="ri-history-line" :flush="true">
            <ul class="list-group list-group-flush">
                @foreach(array_reverse($a->history) as $h)
                    <li class="list-group-item small"><strong>{{ ucfirst($h['action']) }}</strong> by {{ $userNames[$h['by']] ?? 'someone' }} · {{ \Carbon\Carbon::parse($h['at'])->format('d M Y, h:i A') }}
                        @if(!empty($h['note']))<div class="text-muted">“{{ $h['note'] }}”</div>@endif</li>
                @endforeach
            </ul>
        </x-cb.card>
    @endif
</div>
</div>
</div>
@endsection
